<?php
/**
 * Apollo Signature Audit Log.
 *
 * FASE 4: Verificação Legal, Hash e Protocolo Offline.
 *
 * Sistema de auditoria para rastreabilidade de assinaturas.
 * - Registro de todas as ações de assinatura.
 * - Hash e protocolo para verificação offline.
 * - Carimbo de tempo do servidor.
 *
 * @package Apollo\Modules\Signatures
 * @since   2.0.0
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
 * phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
 */

declare(strict_types=1);

namespace Apollo\Modules\Signatures;

/**
 * Audit Log Manager
 */
class AuditLog {

	/** @var string Tabela de logs */
	private string $table_name;

	/** @var string Tabela de protocolos */
	private string $protocol_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name     = $wpdb->prefix . 'apollo_signature_audit';
		$this->protocol_table = $wpdb->prefix . 'apollo_signature_protocols';
	}

	/**
	 * Create audit tables
	 */
	public function createTables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Tabela de logs de auditoria
		$sql1 = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            action enum('created','viewed','edited','finalized','signature_requested','signed','verified','rejected','revoked') NOT NULL,
            actor_id bigint(20) unsigned NULL,
            actor_type enum('user','system','external') NOT NULL DEFAULT 'user',
            actor_name varchar(255),
            actor_cpf varchar(14),
            actor_email varchar(255),
            details longtext,
            document_hash varchar(64),
            signature_hash varchar(64),
            ip_address varchar(50),
            user_agent text,
            geo_location varchar(255),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            timestamp_unix bigint(20) unsigned,
            PRIMARY KEY (id),
            KEY document_idx (document_id),
            KEY action_idx (action),
            KEY actor_idx (actor_id),
            KEY timestamp_idx (timestamp),
            KEY hash_idx (document_hash)
        ) $charset_collate;";

		// Tabela de protocolos de verificação
		$sql2 = "CREATE TABLE {$this->protocol_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            protocol_code varchar(32) NOT NULL UNIQUE,
            document_id bigint(20) unsigned NOT NULL,
            document_hash varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_unix bigint(20) unsigned,
            expires_at datetime,
            verification_count int DEFAULT 0,
            last_verified_at datetime,
            status enum('active','expired','revoked') DEFAULT 'active',
            metadata longtext,
            PRIMARY KEY (id),
            UNIQUE KEY protocol_idx (protocol_code),
            KEY document_idx (document_id),
            KEY hash_idx (document_hash),
            KEY status_idx (status)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	/**
	 * Log action
	 *
	 * @param int    $document_id Document ID
	 * @param string $action Action type
	 * @param array  $data Additional data
	 * @return int|false Log ID or false
	 */
	public function log( int $document_id, string $action, array $data = [] ): int|false {
		global $wpdb;

		$user_id = get_current_user_id();
		$user    = $user_id ? get_userdata( $user_id ) : null;

		$insert_data = [
			'document_id'    => $document_id,
			'action'         => $action,
			'actor_id'       => $data['actor_id'] ?? $user_id,
			'actor_type'     => $data['actor_type'] ?? 'user',
			'actor_name'     => $data['actor_name'] ?? ( $user ? $user->display_name : null ),
			'actor_cpf'      => $data['actor_cpf'] ?? null,
			'actor_email'    => $data['actor_email'] ?? ( $user ? $user->user_email : null ),
			'details'        => isset( $data['details'] ) ? json_encode( $data['details'] ) : null,
			'document_hash'  => $data['document_hash'] ?? null,
			'signature_hash' => $data['signature_hash'] ?? null,
			'ip_address'     => $this->getClientIp(),
			'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
			'geo_location'   => $this->getGeoLocation(),
			'timestamp_unix' => time(),
		];

		$result = $wpdb->insert( $this->table_name, $insert_data );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Log signature action
	 *
	 * @param int    $document_id Document ID
	 * @param array  $signer Signer data
	 * @param string $signature_hash Hash of the signature
	 * @param string $document_hash Hash of the document
	 * @return int|false Log ID
	 */
	public function logSignature(
		int $document_id,
		array $signer,
		string $signature_hash,
		string $document_hash
	): int|false {
		return $this->log(
			$document_id,
			'signed',
			[
				'actor_name'     => $signer['name'] ?? '',
				'actor_cpf'      => $signer['cpf'] ?? '',
				'actor_email'    => $signer['email'] ?? '',
				'signature_hash' => $signature_hash,
				'document_hash'  => $document_hash,
				'details'        => [
					'signature_type'     => $signer['type'] ?? 'electronic',
					'certificate_serial' => $signer['certificate_serial'] ?? null,
					'timestamp'          => current_time( 'mysql' ),
				],
			]
		);
	}

	/**
	 * Generate protocol code for document
	 *
	 * @param int    $document_id Document ID
	 * @param string $document_hash Document hash
	 * @return array Protocol info
	 */
	public function generateProtocol( int $document_id, string $document_hash ): array {
		global $wpdb;

		// Check if protocol already exists
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->protocol_table}
                 WHERE document_id = %d AND status = 'active'",
				$document_id
			),
			ARRAY_A
		);

		if ( $existing ) {
			return [
				'success'       => true,
				'protocol_code' => $existing['protocol_code'],
				'existing'      => true,
			];
		}

		// Generate unique protocol code
		// Format: APR-DOC-YYYY-XXXXX (e.g., APR-DOC-2025-A1B2C)
		$year          = date( 'Y' );
		$random        = strtoupper( substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 5 ) );
		$protocol_code = "APR-DOC-{$year}-{$random}";

		// Ensure uniqueness
		$attempts = 0;
		while ( $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->protocol_table} WHERE protocol_code = %s",
				$protocol_code
			)
		) > 0 && $attempts < 10 ) {
			$random        = strtoupper( substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 5 ) );
			$protocol_code = "APR-DOC-{$year}-{$random}";
			++$attempts;
		}

		$now     = time();
		$expires = strtotime( '+5 years' );

		$result = $wpdb->insert(
			$this->protocol_table,
			[
				'protocol_code' => $protocol_code,
				'document_id'   => $document_id,
				'document_hash' => $document_hash,
				'created_unix'  => $now,
				'expires_at'    => date( 'Y-m-d H:i:s', $expires ),
				'metadata'      => json_encode(
					[
						'created_by'  => get_current_user_id(),
						'server_time' => date( 'Y-m-d H:i:s' ),
						'timezone'    => wp_timezone_string(),
					]
				),
			]
		);

		if ( $result ) {
			// Log protocol creation
			$this->log(
				$document_id,
				'created',
				[
					'details'       => [ 'protocol_code' => $protocol_code ],
					'document_hash' => $document_hash,
				]
			);

			return [
				'success'          => true,
				'protocol_code'    => $protocol_code,
				'created_at'       => date( 'Y-m-d H:i:s' ),
				'expires_at'       => date( 'Y-m-d H:i:s', $expires ),
				'verification_url' => site_url( "/verificar/{$protocol_code}" ),
			];
		}

		return [
			'success' => false,
			'error'   => 'Falha ao gerar protocolo',
		];
	}

	/**
	 * Verify document by protocol
	 *
	 * @param string      $protocol_code Protocol code
	 * @param string|null $provided_hash Optional hash to compare
	 * @return array Verification result
	 */
	public function verifyByProtocol( string $protocol_code, ?string $provided_hash = null ): array {
		global $wpdb;

		$protocol = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->protocol_table} WHERE protocol_code = %s",
				strtoupper( $protocol_code )
			),
			ARRAY_A
		);

		if ( ! $protocol ) {
			return [
				'valid' => false,
				'error' => 'Protocolo não encontrado',
			];
		}

		// Check status
		if ( $protocol['status'] === 'revoked' ) {
			return [
				'valid'    => false,
				'error'    => 'Protocolo foi revogado',
				'protocol' => $protocol_code,
			];
		}

		if ( $protocol['status'] === 'expired' || strtotime( $protocol['expires_at'] ) < time() ) {
			return [
				'valid'    => false,
				'error'    => 'Protocolo expirado',
				'protocol' => $protocol_code,
			];
		}

		// Get document info
		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, file_id, title, pdf_hash, status FROM {$documents_table} WHERE id = %d",
				$protocol['document_id']
			),
			ARRAY_A
		);

		// Update verification count
		$wpdb->update(
			$this->protocol_table,
			[
				'verification_count' => $protocol['verification_count'] + 1,
				'last_verified_at'   => current_time( 'mysql' ),
			],
			[ 'id' => $protocol['id'] ]
		);

		// Log verification
		$this->log(
			$protocol['document_id'],
			'verified',
			[
				'details' => [ 'protocol_code' => $protocol_code ],
			]
		);

		// Get signature history
		$signatures = $this->getDocumentSignatures( $protocol['document_id'] );

		// Hash verification
		$hash_valid = true;
		if ( $provided_hash ) {
			$hash_valid = $provided_hash === $protocol['document_hash'];
		}

		return [
			'valid'       => true,
			'protocol'    => [
				'code'               => $protocol['protocol_code'],
				'created_at'         => $protocol['created_at'],
				'expires_at'         => $protocol['expires_at'],
				'verification_count' => $protocol['verification_count'] + 1,
			],
			'document'    => [
				'id'      => $document['id'] ?? null,
				'file_id' => $document['file_id'] ?? null,
				'title'   => $document['title'] ?? 'Documento não encontrado',
				'status'  => $document['status'] ?? 'unknown',
			],
			'hash'        => [
				'stored'   => $protocol['document_hash'],
				'provided' => $provided_hash,
				'match'    => $hash_valid,
			],
			'signatures'  => $signatures,
			'verified_at' => current_time( 'mysql' ),
		];
	}

	/**
	 * Verify document by hash
	 *
	 * @param string $hash Document hash
	 * @return array Verification result
	 */
	public function verifyByHash( string $hash ): array {
		global $wpdb;

		// Search in protocols
		$protocol = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->protocol_table} WHERE document_hash = %s AND status = 'active'",
				$hash
			),
			ARRAY_A
		);

		if ( $protocol ) {
			return $this->verifyByProtocol( $protocol['protocol_code'] );
		}

		// Search in documents directly
		$documents_table = $wpdb->prefix . 'apollo_documents';
		$document        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$documents_table} WHERE pdf_hash = %s",
				$hash
			),
			ARRAY_A
		);

		if ( $document ) {
			return [
				'valid'    => true,
				'document' => [
					'id'      => $document['id'],
					'file_id' => $document['file_id'],
					'title'   => $document['title'],
					'status'  => $document['status'],
				],
				'hash'     => [
					'stored' => $document['pdf_hash'],
					'match'  => true,
				],
				'note'     => 'Documento encontrado mas sem protocolo de verificação',
			];
		}

		return [
			'valid' => false,
			'error' => 'Nenhum documento encontrado com este hash',
		];
	}

	/**
	 * Get document signatures
	 *
	 * @param int $document_id Document ID
	 * @return array Signatures
	 */
	public function getDocumentSignatures( int $document_id ): array {
		global $wpdb;

		$signatures_table = $wpdb->prefix . 'apollo_document_signatures';

		$signatures = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$signatures_table} WHERE document_id = %d ORDER BY signed_at DESC",
				$document_id
			),
			ARRAY_A
		);

		// Mask CPFs
		foreach ( $signatures as &$sig ) {
			if ( ! empty( $sig['signer_cpf'] ) ) {
				$cpf                      = $sig['signer_cpf'];
				$sig['signer_cpf_masked'] = substr( $cpf, 0, 3 ) . '.***.***-' . substr( $cpf, -2 );
			}
		}

		return $signatures;
	}

	/**
	 * Get audit log for document
	 *
	 * @param int   $document_id Document ID
	 * @param array $args Query args
	 * @return array Logs
	 */
	public function getDocumentLogs( int $document_id, array $args = [] ): array {
		global $wpdb;

		$where  = [ 'document_id = %d' ];
		$params = [ $document_id ];

		// Action filter
		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$params[] = $args['action'];
		}

		// Date range
		if ( ! empty( $args['from_date'] ) ) {
			$where[]  = 'timestamp >= %s';
			$params[] = $args['from_date'];
		}

		if ( ! empty( $args['to_date'] ) ) {
			$where[]  = 'timestamp <= %s';
			$params[] = $args['to_date'];
		}

		$where_sql = implode( ' AND ', $where );

		// Pagination
		$per_page = (int) ( $args['per_page'] ?? 50 );
		$page     = (int) ( $args['page'] ?? 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
                 WHERE {$where_sql}
                 ORDER BY timestamp DESC
                 LIMIT %d OFFSET %d",
				[ ...$params, $per_page, $offset ]
			),
			ARRAY_A
		);

		// Parse JSON details
		foreach ( $logs as &$log ) {
			if ( $log['details'] ) {
				$log['details'] = json_decode( $log['details'], true );
			}
		}

		return $logs;
	}

	/**
	 * Generate verification report
	 *
	 * @param int $document_id Document ID
	 * @return array Report data
	 */
	public function generateVerificationReport( int $document_id ): array {
		global $wpdb;

		$documents_table = $wpdb->prefix . 'apollo_documents';

		// Get document
		$document = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$documents_table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $document ) {
			return [
				'success' => false,
				'error'   => 'Documento não encontrado',
			];
		}

		// Get protocol
		$protocol = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->protocol_table} WHERE document_id = %d AND status = 'active'",
				$document_id
			),
			ARRAY_A
		);

		// Get signatures
		$signatures = $this->getDocumentSignatures( $document_id );

		// Get audit logs
		$logs = $this->getDocumentLogs( $document_id );

		// Build report
		return [
			'success'                          => true,
			'generated_at'                     => current_time( 'mysql' ),
			'document'                         => [
				'id'           => $document['id'],
				'file_id'      => $document['file_id'],
				'title'        => $document['title'],
				'type'         => $document['type'],
				'status'       => $document['status'],
				'created_at'   => $document['created_at'],
				'finalized_at' => $document['finalized_at'],
				'pdf_hash'     => $document['pdf_hash'],
			],
			'protocol'                         => $protocol ? [
				'code'               => $protocol['protocol_code'],
				'created_at'         => $protocol['created_at'],
				'expires_at'         => $protocol['expires_at'],
				'status'             => $protocol['status'],
				'verification_count' => $protocol['verification_count'],
			] : null,
			'signatures'                       => array_map(
				function ( $sig ) {
					return [
						'party'      => $sig['signer_party'],
						'name'       => $sig['signer_name'],
						'cpf_masked' => $sig['signer_cpf_masked'] ?? '',
						'email'      => $sig['signer_email'],
						'status'     => $sig['status'],
						'signed_at'  => $sig['signed_at'],
						'ip_address' => $sig['ip_address'],
					];
				},
				$signatures
			),
			'signatures_summary'               => [
				'total'   => count( $signatures ),
				'signed'  => count( array_filter( $signatures, fn( $s ) => $s['status'] === 'signed' ) ),
				'pending' => count( array_filter( $signatures, fn( $s ) => $s['status'] === 'pending' ) ),
			],
			'audit_trail'                      => array_slice( $logs, 0, 20 ),
			// Last 20 events
							'verification_url' => $protocol ? site_url( "/verificar/{$protocol['protocol_code']}" ) : null,
		];
	}

	/**
	 * Revoke protocol
	 *
	 * @param string $protocol_code Protocol code
	 * @param string $reason Reason for revocation
	 * @return array Result
	 */
	public function revokeProtocol( string $protocol_code, string $reason = '' ): array {
		global $wpdb;

		$protocol = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->protocol_table} WHERE protocol_code = %s",
				$protocol_code
			),
			ARRAY_A
		);

		if ( ! $protocol ) {
			return [
				'success' => false,
				'error'   => 'Protocolo não encontrado',
			];
		}

		if ( $protocol['status'] === 'revoked' ) {
			return [
				'success' => false,
				'error'   => 'Protocolo já foi revogado',
			];
		}

		$result = $wpdb->update(
			$this->protocol_table,
			[ 'status' => 'revoked' ],
			[ 'id' => $protocol['id'] ]
		);

		if ( $result !== false ) {
			// Log revocation
			$this->log(
				$protocol['document_id'],
				'revoked',
				[
					'details' => [
						'protocol_code' => $protocol_code,
						'reason'        => $reason,
					],
				]
			);

			return [
				'success' => true,
				'message' => 'Protocolo revogado',
			];
		}

		return [
			'success' => false,
			'error'   => 'Erro ao revogar protocolo',
		];
	}

	/**
	 * Get client IP
	 */
	private function getClientIp(): string {
		$ip_keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get geolocation from IP (simplified)
	 */
	private function getGeoLocation(): string {
		// This could be enhanced with a GeoIP service
		// For now, just return timezone
		return wp_timezone_string();
	}

	/**
	 * Generate printable verification certificate
	 *
	 * @param int $document_id Document ID
	 * @return string HTML certificate
	 */
	public function generateVerificationCertificate( int $document_id ): string {
		$report = $this->generateVerificationReport( $document_id );

		if ( ! $report['success'] ) {
			return '<p>Erro ao gerar certificado: ' . esc_html( $report['error'] ) . '</p>';
		}

		$doc        = $report['document'];
		$protocol   = $report['protocol'];
		$signatures = $report['signatures'];

		$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Verificação - {$doc['file_id']}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #0f172a; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin: 25px 0; }
        .section h2 { font-size: 14px; text-transform: uppercase; color: #666; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px; }
        .field { margin: 10px 0; display: flex; }
        .field-label { font-weight: bold; width: 180px; color: #555; }
        .field-value { flex: 1; }
        .signature-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .status-signed { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; }
        .hash { font-family: monospace; font-size: 11px; word-break: break-all; background: #f1f5f9; padding: 10px; border-radius: 4px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #888; text-align: center; }
        .qr-placeholder { width: 100px; height: 100px; background: #f0f0f0; margin: 20px auto; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CERTIFICADO DE VERIFICAÇÃO</h1>
        <p>Apollo Documents · Sistema de Assinatura Digital</p>
    </div>

    <div class="section">
        <h2>Informações do Documento</h2>
        <div class="field">
            <span class="field-label">Título:</span>
            <span class="field-value">{$doc['title']}</span>
        </div>
        <div class="field">
            <span class="field-label">Identificador:</span>
            <span class="field-value">{$doc['file_id']}</span>
        </div>
        <div class="field">
            <span class="field-label">Status:</span>
            <span class="field-value">{$doc['status']}</span>
        </div>
        <div class="field">
            <span class="field-label">Finalizado em:</span>
            <span class="field-value">{$doc['finalized_at']}</span>
        </div>
    </div>
HTML;

		if ( $protocol ) {
			$html .= <<<HTML
    <div class="section">
        <h2>Protocolo de Verificação</h2>
        <div class="field">
            <span class="field-label">Código do Protocolo:</span>
            <span class="field-value"><strong>{$protocol['code']}</strong></span>
        </div>
        <div class="field">
            <span class="field-label">Emitido em:</span>
            <span class="field-value">{$protocol['created_at']}</span>
        </div>
        <div class="field">
            <span class="field-label">Válido até:</span>
            <span class="field-value">{$protocol['expires_at']}</span>
        </div>
        <div class="field">
            <span class="field-label">Verificações:</span>
            <span class="field-value">{$protocol['verification_count']} vezes</span>
        </div>
    </div>
HTML;
		}//end if

		$html .= '<div class="section"><h2>Assinaturas</h2>';

		foreach ( $signatures as $sig ) {
			$status_class = $sig['status'] === 'signed' ? 'status-signed' : 'status-pending';
			$html        .= <<<HTML
        <div class="signature-box">
            <div class="field">
                <span class="field-label">Nome:</span>
                <span class="field-value">{$sig['name']}</span>
            </div>
            <div class="field">
                <span class="field-label">CPF:</span>
                <span class="field-value">{$sig['cpf_masked']}</span>
            </div>
            <div class="field">
                <span class="field-label">Status:</span>
                <span class="field-value {$status_class}">{$sig['status']}</span>
            </div>
            <div class="field">
                <span class="field-label">Assinado em:</span>
                <span class="field-value">{$sig['signed_at']}</span>
            </div>
        </div>
HTML;
		}//end foreach

		$html .= '</div>';

		if ( $doc['pdf_hash'] ) {
			$html .= <<<HTML
    <div class="section">
        <h2>Verificação de Integridade</h2>
        <p>Hash SHA-256 do documento:</p>
        <div class="hash">{$doc['pdf_hash']}</div>
        <p style="font-size: 11px; color: #888; margin-top: 10px;">
            Este hash pode ser usado para verificar que o documento não foi alterado após a assinatura.
        </p>
    </div>
HTML;
		}

		$html .= <<<HTML
    <div class="footer">
        <p>Certificado gerado em {$report['generated_at']}</p>
        <p>Para verificar a autenticidade deste documento, acesse: {$report['verification_url']}</p>
        <p>© Apollo Social · Sistema de Documentos e Assinaturas</p>
    </div>
</body>
</html>
HTML;

		return $html;
	}
}
