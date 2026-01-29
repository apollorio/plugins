<?php

/**
 * Signatures Service.
 *
 * @package Apollo\Modules\Signatures\Services
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
 */

namespace Apollo\Modules\Signatures\Services;

use Apollo\Modules\Signatures\Models\DigitalSignature;
use Apollo\Modules\Signatures\Repositories\TemplatesRepository;
use Apollo\Modules\Signatures\Adapters\GovbrApi;

/**
 * Signatures Service
 *
 * Main service for digital signatures with multiple providers
 * Supports: GOV.BR (qualified), ICP-Brasil (qualified)
 *
 * @since 1.0.0
 */
class SignaturesService {

	// Signature tracks according to Lei 14.063/2020
	public const TRACK_B = 'track_b';
	// Trilho B: Assinatura qualificada (GOV.BR/ICP-Brasil)

	/** @var TemplatesRepository */
	private $templates_repository;

	/** @var RenderService */
	private $render_service;

	/** @var GovbrApi */
	private $govbr_api;

	/** @var string */
	private $signatures_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;

		$this->templates_repository = new TemplatesRepository();
		$this->render_service       = new RenderService();
		$this->govbr_api            = new GovbrApi();
		$this->signatures_table     = $wpdb->prefix . 'apollo_digital_signatures';
	}

	/**
	 * Create signatures table
	 *
	 * @return bool
	 */
	public function createSignaturesTable(): bool {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->signatures_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id bigint(20) NOT NULL,
            document_hash varchar(64) NOT NULL,
            signer_name varchar(255) NOT NULL,
            signer_email varchar(255) NOT NULL,
            signer_document varchar(20),
            signature_level enum('simple','advanced','qualified') NOT NULL,
            provider enum('govbr','icp_provider') NOT NULL,
            provider_envelope_id varchar(255),
            signing_url text,
            status enum('pending','signed','declined','expired','error') DEFAULT 'pending',
            metadata longtext,
            signed_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_template_id (template_id),
            KEY idx_signer_email (signer_email),
            KEY idx_signature_level (signature_level),
            KEY idx_provider (provider),
            KEY idx_status (status),
            KEY idx_created_by (created_by),
            UNIQUE KEY idx_document_hash (document_hash)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return true;
	}

	/**
	 * Create signature request
	 *
	 * @param int    $template_id
	 * @param array  $template_data
	 * @param array  $signer_info
	 * @param string $track
	 * @param array  $options
	 * @return DigitalSignature|false
	 */
	public function createSignatureRequest(
		int $template_id,
		array $template_data,
		array $signer_info,
		string $track = self::TRACK_B,
		array $options = array()
	): DigitalSignature|false {

		try {
			// Get template
			$template = $this->templates_repository->findById( $template_id );
			if ( ! $template ) {
				throw new \Exception( 'Template não encontrado' );
			}

			// Validate template data
			$validation_errors = $template->validateData( $template_data );
			if ( ! empty( $validation_errors ) ) {
				throw new \Exception( 'Dados inválidos: ' . implode( ', ', $validation_errors ) );
			}

			// Generate PDF document
			$pdf_options = array_merge(
				$options,
				array(
					'title'    => $template->name,
					'filename' => sprintf( 'documento_%s_%s.pdf', $template_id, uniqid() ),
				)
			);

			$pdf_path = $this->render_service->renderToPdf( $template, $template_data, $pdf_options );
			if ( ! $pdf_path ) {
				throw new \Exception( 'Erro ao gerar PDF' );
			}

			// Calculate document hash
			$document_hash = $this->render_service->getDocumentHash( $pdf_path );

			// Determine provider and signature level based on track
			[$provider, $signature_level] = $this->getProviderAndLevel( $track, $options );

			// Create signature record
			$signature_data = array(
				'template_id'     => $template_id,
				'document_hash'   => $document_hash,
				'signer_name'     => $signer_info['name'],
				'signer_email'    => $signer_info['email'],
				'signer_document' => $signer_info['document'] ?? '',
				'signature_level' => $signature_level,
				'provider'        => $provider,
				'metadata'        => json_encode(
					array(
						'template_data' => $template_data,
						'pdf_path'      => $pdf_path,
						'options'       => $options,
					)
				),
			);

			$signature = $this->createSignatureRecord( $signature_data );
			if ( ! $signature ) {
				throw new \Exception( 'Erro ao criar registro de assinatura' );
			}

			// Create envelope with provider
			$envelope_result = $this->createEnvelope( $signature, $pdf_path, $options );
			if ( ! $envelope_result ) {
				throw new \Exception( 'Erro ao criar envelope de assinatura' );
			}

			// Update signature with envelope info
			$this->updateSignature(
				$signature->id,
				array(
					'provider_envelope_id' => $envelope_result['envelope_id'],
					'signing_url'          => $envelope_result['signing_url'],
				)
			);

			// Reload signature with updated data
			return $this->findSignatureById( $signature->id );
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Apollo Signatures Error: ' . $e->getMessage() );
			}

			return false;
		}//end try
	}

	/**
	 * Get provider and signature level based on track
	 *
	 * @param string $track
	 * @param array  $options
	 * @return array [provider, signature_level]
	 */
	private function getProviderAndLevel( string $track, array $options = array() ): array {
		switch ( $track ) {
			case self::TRACK_B:
				// Trilho B: Assinatura qualificada
				$provider = $options['provider'] ?? 'govbr';

				if ( $provider === 'govbr' ) {
					return array( 'govbr', DigitalSignature::LEVEL_QUALIFIED );
				} else {
					return array( 'icp_provider', DigitalSignature::LEVEL_QUALIFIED );
				}

				// no break
			default:
				return array( 'govbr', DigitalSignature::LEVEL_QUALIFIED );
		}
	}

	/**
	 * Create envelope with appropriate provider
	 *
	 * @param DigitalSignature $signature
	 * @param string           $pdf_path
	 * @param array            $options
	 * @return array|false
	 */
	private function createEnvelope( DigitalSignature $signature, string $pdf_path, array $options = array() ): array|false {
		switch ( $signature->provider ) {
			case 'govbr':
				return $this->govbr_api->createEnvelope( $signature, $pdf_path, $options );

			case 'icp_provider':
				// Other ICP-Brasil providers use GOV.BR infrastructure
				return $this->govbr_api->createEnvelope( $signature, $pdf_path, $options );

			default:
				return $this->govbr_api->createEnvelope( $signature, $pdf_path, $options );
		}
	}

	/**
	 * Create signature record in database
	 *
	 * @param array $data
	 * @return DigitalSignature|false
	 */
	private function createSignatureRecord( array $data ): DigitalSignature|false {
		global $wpdb;

		$data['created_by'] = get_current_user_id();

		$result = $wpdb->insert( $this->signatures_table, $data );

		if ( $result === false ) {
			return false;
		}

		return $this->findSignatureById( $wpdb->insert_id );
	}

	/**
	 * Find signature by ID
	 *
	 * @param int $id
	 * @return DigitalSignature|null
	 */
	public function findSignatureById( int $id ): ?DigitalSignature {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->signatures_table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $result ? new DigitalSignature( $result ) : null;
	}

	/**
	 * Update signature
	 *
	 * @param int   $id
	 * @param array $data
	 * @return bool
	 */
	public function updateSignature( int $id, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->signatures_table,
			$data,
			array( 'id' => $id )
		);

		return $result !== false;
	}

	/**
	 * Process webhook from signature provider
	 *
	 * @param string $provider
	 * @param array  $payload
	 * @return bool
	 */
	public function processWebhook( string $provider, array $payload ): bool {
		try {
			switch ( $provider ) {
				case 'govbr':
					return $this->govbr_api->processWebhook( $payload );

				default:
					return $this->govbr_api->processWebhook( $payload );
			}
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Apollo Signatures Webhook Error: ' . $e->getMessage() );
			}

			return false;
		}
	}

	/**
	 * Update signature status from webhook
	 *
	 * @param string $envelope_id
	 * @param string $status
	 * @param array  $metadata
	 * @return bool
	 */
	public function updateSignatureStatus( string $envelope_id, string $status, array $metadata = array() ): bool {
		global $wpdb;

		$signature = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->signatures_table} WHERE provider_envelope_id = %s",
				$envelope_id
			),
			ARRAY_A
		);

		if ( ! $signature ) {
			return false;
		}

		$signature_obj = new DigitalSignature( $signature );
		$signature_obj->updateStatus( $status, $metadata );

		$update_data = array(
			'status'     => $signature_obj->status,
			'metadata'   => json_encode( $signature_obj->metadata ),
			'updated_at' => $signature_obj->updated_at,
		);

		if ( $signature_obj->signed_at ) {
			$update_data['signed_at'] = $signature_obj->signed_at;
		}

		$result = $wpdb->update(
			$this->signatures_table,
			$update_data,
			array( 'id' => $signature_obj->id )
		);

		// Award badge if signed
		if ( $signature_obj->isSigned() ) {
			$this->awardSignatureBadge( $signature_obj );
		}

		return $result !== false;
	}

	/**
	 * Award signature badge to user
	 *
	 * @param DigitalSignature $signature
	 */
	private function awardSignatureBadge( DigitalSignature $signature ): void {
		// Get user by email
		$user = get_user_by( 'email', $signature->signer_email );
		if ( ! $user ) {
			return;
		}

		// Check if badges are enabled
		$badges_enabled = get_option( 'apollo_signatures_badges_enabled', false );
		if ( ! $badges_enabled ) {
			return;
		}

		// Award badge through Apollo Badges system
		if ( function_exists( 'apollo_award_badge' ) ) {
			call_user_func(
				'apollo_award_badge',
				$user->ID,
				'contract_signed',
				array(
					'signature_id'    => $signature->id,
					'signature_level' => $signature->signature_level,
					'provider'        => $signature->provider,
					'signed_at'       => $signature->signed_at,
				)
			);
		}
	}

	/**
	 * Get signatures by user
	 *
	 * @param int   $user_id
	 * @param array $filters
	 * @return array
	 */
	public function getSignaturesByUser( int $user_id, array $filters = array() ): array {
		global $wpdb;

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		$where_clauses = array( 'signer_email = %s' );
		$values        = array( $user->user_email );

		if ( ! empty( $filters['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$values[]        = $filters['status'];
		}

		if ( ! empty( $filters['signature_level'] ) ) {
			$where_clauses[] = 'signature_level = %s';
			$values[]        = $filters['signature_level'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$order_by  = $filters['order_by'] ?? 'created_at DESC';
		$limit     = $filters['limit'] ?? 50;

		$sql = "SELECT * FROM {$this->signatures_table}
                WHERE {$where_sql}
                ORDER BY {$order_by}
                LIMIT %d";

		$values[] = $limit;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$values ),
			ARRAY_A
		);

		return array_map(
			function ( $row ) {
				return new DigitalSignature( $row );
			},
			$results ?: array()
		);
	}

	/**
	 * Get signature statistics
	 *
	 * @param array $filters
	 * @return array
	 */
	public function getSignatureStats( array $filters = array() ): array {
		global $wpdb;

		$where_clauses = array( '1=1' );
		$values        = array();

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$values[]        = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$values[]        = $filters['date_to'];
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Total signatures
		$total_sql = "SELECT COUNT(*) as total FROM {$this->signatures_table} WHERE {$where_sql}";
		$total     = $wpdb->get_var( empty( $values ) ? $total_sql : $wpdb->prepare( $total_sql, ...$values ) );

		// By status
		$status_sql = "SELECT status, COUNT(*) as count FROM {$this->signatures_table} WHERE {$where_sql} GROUP BY status";
		$by_status  = $wpdb->get_results(
			empty( $values ) ? $status_sql : $wpdb->prepare( $status_sql, ...$values ),
			ARRAY_A
		);

		// By level
		$level_sql = "SELECT signature_level, COUNT(*) as count FROM {$this->signatures_table} WHERE {$where_sql} GROUP BY signature_level";
		$by_level  = $wpdb->get_results(
			empty( $values ) ? $level_sql : $wpdb->prepare( $level_sql, ...$values ),
			ARRAY_A
		);

		return array(
			'total'     => (int) $total,
			'by_status' => $by_status ?: array(),
			'by_level'  => $by_level ?: array(),
		);
	}
}
