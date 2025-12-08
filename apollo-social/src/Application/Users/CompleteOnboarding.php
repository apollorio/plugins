<?php
/**
 * CompleteOnboarding.
 *
 * Finalizes the onboarding process and marks user as onboarded.
 *
 * @package Apollo\Application\Users
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */

namespace Apollo\Application\Users;

/**
 * CompleteOnboarding class.
 *
 * Finalizes the onboarding process and marks user as onboarded.
 */
class CompleteOnboarding {

	/**
	 * Complete onboarding process for user.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data    The completion data.
	 * @return array Result with success status.
	 */
	public function handle( int $user_id, array $data ): array {
		try {
			// Validate user and current progress.
			$validation = $this->validateCompletion( $user_id, $data );
			if ( ! $validation['valid'] ) {
				return [
					'success' => false,
					'message' => $validation['message'],
					'errors'  => $validation['errors'] ?? [],
				];
			}

			// Mark onboarding as completed.
			$this->markOnboardingComplete( $user_id );

			// Create verification record.
			$this->createVerificationRecord( $user_id, $data );

			// Set initial user role/permissions.
			$this->setupUserPermissions( $user_id );

			// Log completion.
			$this->logOnboardingEvent( $user_id, 'onboarding_completed', $data );

			// Send analytics event (if enabled).
			$this->trackAnalyticsEvent( $user_id, 'onboarding_completed', $data );

			return [
				'success'      => true,
				'message'      => 'Onboarding finalizado com sucesso',
				'redirect_url' => $this->getRedirectUrl( $user_id ),
				'user_status'  => 'awaiting_verification',
			];

		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'CompleteOnboarding error: ' . $e->getMessage() );

			return [
				'success' => false,
				'message' => 'Erro interno. Tente novamente.',
			];
		}//end try
	}

	/**
	 * Validate that onboarding can be completed.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data    The completion data (reserved for future use).
	 * @return array Validation result.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	private function validateCompletion( int $user_id, array $data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Reserved for future use.
		$errors = [];

		// Check if user exists.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return [
				'valid'   => false,
				'message' => 'Usuário não encontrado',
			];
		}

		// Check if user has onboarding progress.
		$progress = get_user_meta( $user_id, 'apollo_onboarding_progress', true );
		if ( ! $progress || ! is_array( $progress ) ) {
			return [
				'valid'   => false,
				'message' => 'Onboarding não iniciado',
			];
		}

		// Check if already completed.
		if ( isset( $progress['completed'] ) && $progress['completed'] ) {
			return [
				'valid'   => false,
				'message' => 'Onboarding já finalizado',
			];
		}

		// Validate required fields are present.
		$required_fields = [ 'name', 'industry', 'whatsapp', 'instagram' ];
		foreach ( $required_fields as $field ) {
			$value = get_user_meta( $user_id, "apollo_{$field}", true );
			if ( empty( $value ) ) {
				$errors[ $field ] = "Campo {$field} é obrigatório";
			}
		}

		// Validate verification token exists.
		$verify_token = get_user_meta( $user_id, 'apollo_verify_token', true );
		if ( empty( $verify_token ) ) {
			$errors['verification'] = 'Token de verificação não encontrado';
		}

		return [
			'valid'   => empty( $errors ),
			'message' => empty( $errors ) ? 'Validação passou' : 'Dados incompletos',
			'errors'  => $errors,
		];
	}

	/**
	 * Mark onboarding as completed.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	private function markOnboardingComplete( int $user_id ): void {
		$progress = get_user_meta( $user_id, 'apollo_onboarding_progress', true );

		if ( ! is_array( $progress ) ) {
			$progress = [];
		}

		$progress['completed']    = true;
		$progress['completed_at'] = current_time( 'mysql' );
		$progress['current_step'] = 'completed';

		update_user_meta( $user_id, 'apollo_onboarding_progress', $progress );

		// Set user as onboarded.
		update_user_meta( $user_id, 'apollo_onboarded', true );
		update_user_meta( $user_id, 'apollo_onboarded_at', current_time( 'mysql' ) );
	}

	/**
	 * Create verification record in database.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data    The completion data (reserved for future use).
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	private function createVerificationRecord( int $user_id, array $data ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Reserved for future use.
		global $wpdb;

		$verification_table = $wpdb->prefix . 'apollo_verifications';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
		$table_check = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $verification_table )
			)
		);
		if ( $table_check !== $verification_table ) {
			// Create table if it doesn't exist.
			$this->createVerificationTable();
		}

		$user_meta = $this->getUserOnboardingMeta( $user_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Verification record creation.
		$wpdb->insert(
			$verification_table,
			[
				'user_id'            => $user_id,
				'instagram_username' => $user_meta['instagram'],
				'whatsapp_number'    => $user_meta['whatsapp'],
				'verify_token'       => $user_meta['verify_token'],
				'verify_status'      => 'awaiting_instagram_verify',
				'submitted_at'       => current_time( 'mysql' ),
				'metadata'           => wp_json_encode(
					[
						'name'       => $user_meta['name'],
						'industry'   => $user_meta['industry'],
						'roles'      => $user_meta['roles'],
						'member_of'  => $user_meta['member_of'],
						'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
						'ip_address' => $this->getClientIp(),
					]
				),
			]
		);
	}

	/**
	 * Create verification table if it doesn't exist.
	 *
	 * @return void
	 */
	private function createVerificationTable(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_verifications';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            instagram_username varchar(30) NOT NULL,
            whatsapp_number varchar(20) NOT NULL,
            verify_token varchar(100) NOT NULL,
            verify_status enum('awaiting_instagram_verify','dm_requested','verified','rejected') DEFAULT 'awaiting_instagram_verify',
            verify_assets longtext DEFAULT NULL,
            submitted_at datetime NOT NULL,
            reviewed_at datetime DEFAULT NULL,
            reviewer_id bigint(20) DEFAULT NULL,
            rejection_reason text DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY instagram_username (instagram_username),
            KEY verify_status (verify_status),
            KEY verify_token (verify_token)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Setup initial user permissions and role.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	private function setupUserPermissions( int $user_id ): void {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		// Assign subscriber role if user has no role.
		if ( empty( $user->roles ) ) {
			$user->set_role( 'subscriber' );
		}

		// Add Apollo-specific capabilities for subscribers.
		$subscriber_caps = [
			'create_apollo_groups'  => true,
			'create_apollo_ads'     => true,
			'publish_apollo_groups' => true,
			'publish_apollo_ads'    => true,
			'read_apollo_content'   => true,
		];

		foreach ( $subscriber_caps as $cap => $grant ) {
			$user->add_cap( $cap, $grant );
		}
	}

	/**
	 * Get redirect URL after completion.
	 *
	 * @param int $user_id The user ID.
	 * @return string The redirect URL.
	 */
	private function getRedirectUrl( int $user_id ): string {
		// Check if there's a custom redirect in user meta.
		$custom_redirect = get_user_meta( $user_id, 'apollo_onboarding_redirect', true );
		if ( $custom_redirect ) {
			return $custom_redirect;
		}

		// Default to verification page.
		return '/verificacao/';
	}

	/**
	 * Get user onboarding meta.
	 *
	 * @param int $user_id The user ID.
	 * @return array The user meta.
	 */
	private function getUserOnboardingMeta( int $user_id ): array {
		$meta_keys = [
			'apollo_name'          => 'name',
			'apollo_industry'      => 'industry',
			'apollo_roles'         => 'roles',
			'apollo_member_of'     => 'member_of',
			'apollo_whatsapp'      => 'whatsapp',
			'apollo_instagram'     => 'instagram',
			'apollo_verify_token'  => 'verify_token',
			'apollo_verify_status' => 'verify_status',
		];

		$user_meta = [];
		foreach ( $meta_keys as $meta_key => $key ) {
			$value             = get_user_meta( $user_id, $meta_key, true );
			$user_meta[ $key ] = $value;
		}

		return $user_meta;
	}

	/**
	 * Log onboarding event.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $event   The event name.
	 * @param array  $data    Additional event data.
	 * @return void
	 */
	private function logOnboardingEvent( int $user_id, string $event, array $data = [] ): void {
		global $wpdb;

		$audit_table = $wpdb->prefix . 'apollo_audit_log';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
		$table_check = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $audit_table )
			)
		);
		if ( $table_check !== $audit_table ) {
			// Table doesn't exist yet.
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Audit logging.
		$wpdb->insert(
			$audit_table,
			[
				'user_id'     => $user_id,
				'action'      => $event,
				'entity_type' => 'user',
				'entity_id'   => $user_id,
				'metadata'    => wp_json_encode(
					[
						'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
						'ip_address'      => $this->getClientIp(),
						'completion_data' => $data,
					]
				),
				'created_at'  => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Track analytics event.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $event   The event name.
	 * @param array  $_data   Additional event data (reserved for future use).
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	private function trackAnalyticsEvent( int $user_id, string $event, array $_data ): void {
		// Check if analytics is enabled.
		$config_file = plugin_dir_path( __FILE__ ) . '../../../config/analytics.php';
		if ( ! file_exists( $config_file ) ) {
			return;
		}

		$analytics_config = include $config_file;
		if ( ! ( $analytics_config['enable_on_canvas'] ?? false ) ) {
			return;
		}

		// Get user meta for event properties.
		$user_meta = $this->getUserOnboardingMeta( $user_id );

		// Prepare event data.
		$event_data = [
			'user_id'    => $user_id,
			'event'      => $event,
			'properties' => [
				'industry'            => $user_meta['industry'],
				'roles_count'         => is_array( $user_meta['roles'] ) ? count( $user_meta['roles'] ) : 0,
				'has_memberships'     => ! empty( $user_meta['member_of'] ),
				'verification_method' => 'instagram',
				'onboarding_flow'     => 'chat',
			],
			'timestamp'  => current_time( 'mysql' ),
			'session_id' => session_id() ? session_id() : 'unknown',
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'ip_address' => $this->getClientIp(),
		];

		// Store in local analytics if no external service.
		$this->storeLocalAnalyticsEvent( $event_data );
	}

	/**
	 * Store analytics event locally.
	 *
	 * @param array $event_data The event data.
	 * @return void
	 */
	private function storeLocalAnalyticsEvent( array $event_data ): void {
		global $wpdb;

		$analytics_table = $wpdb->prefix . 'apollo_analytics_events';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check.
		$table_check = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $analytics_table )
			)
		);
		if ( $table_check !== $analytics_table ) {
			$this->createAnalyticsTable();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Analytics logging.
		$wpdb->insert(
			$analytics_table,
			[
				'user_id'          => $event_data['user_id'],
				'event_name'       => $event_data['event'],
				'event_properties' => wp_json_encode( $event_data['properties'] ),
				'session_id'       => $event_data['session_id'],
				'user_agent'       => $event_data['user_agent'],
				'ip_address'       => $event_data['ip_address'],
				'created_at'       => $event_data['timestamp'],
			]
		);
	}

	/**
	 * Create analytics table.
	 *
	 * @return void
	 */
	private function createAnalyticsTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'apollo_analytics_events';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            event_name varchar(100) NOT NULL,
            event_properties longtext DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_name (event_name),
            KEY created_at (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string The client IP address or 'unknown'.
	 */
	private function getClientIp(): string {
		$ip_headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) && ! empty( $_SERVER[ $header ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP address validation.
				return sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			}
		}

		return 'unknown';
	}

	/**
	 * Rate limiting check (1 submit per 60s per user/IP).
	 *
	 * @param int $user_id The user ID.
	 * @return array Rate limit check result.
	 */
	public function checkRateLimit( int $user_id ): array {
		$cache_key   = "apollo_onboarding_rate_limit_{$user_id}";
		$last_submit = wp_cache_get( $cache_key );

		if ( $last_submit && ( time() - $last_submit ) < 60 ) {
			return [
				'allowed'   => false,
				'wait_time' => 60 - ( time() - $last_submit ),
			];
		}

		// Set rate limit.
		wp_cache_set( $cache_key, time(), '', 60 );

		return [ 'allowed' => true ];
	}
}
