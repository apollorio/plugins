<?php

/**
 * VerifyInstagram.
 *
 * Handles Instagram verification via DM (no upload).
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
 * VerifyInstagram class.
 *
 * Handles Instagram verification via DM (no upload).
 */
class VerifyInstagram {

	/**
	 * Request DM verification.
	 *
	 * @param int $user_id The user ID.
	 * @return array Result with success status.
	 */
	public function requestDmVerification( int $user_id ): array {
		try {
			// Validate user and verification status.
			$validation = $this->validateDmRequest( $user_id );
			if ( ! $validation['valid'] ) {
				return array(
					'success' => false,
					'message' => $validation['message'],
				);
			}

			// Get or generate token.
			$instagram    = get_user_meta( $user_id, 'apollo_instagram', true );
			$verify_token = $this->buildVerifyToken( $instagram );

			// Update verification status to dm_requested.
			$this->updateVerificationStatus( $user_id, 'dm_requested', $verify_token );

			// Log event.
			$this->logVerificationEvent(
				$user_id,
				'verification_dm_requested',
				array(
					'token'     => $verify_token,
					'instagram' => $instagram,
				)
			);

			return array(
				'success'     => true,
				'token'       => $verify_token,
				'ig_username' => $instagram,
				'status'      => 'dm_requested',
				'phrase'      => $this->buildVerificationPhrase( $instagram, $verify_token ),
			);

		} catch ( \Exception $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'VerifyInstagram::requestDmVerification error: ' . $e->getMessage() );

			return array(
				'success' => false,
				'message' => 'Erro interno. Tente novamente.',
			);
		}//end try
	}

	/**
	 * Confirm verification (admin/mod).
	 *
	 * @param int $user_id     The user ID.
	 * @param int $reviewer_id The reviewer user ID.
	 * @return array Result with success status.
	 */
	public function confirmVerification( int $user_id, int $reviewer_id ): array {
		try {
			// Validate user exists.
			$user = get_user_by( 'ID', $user_id );
			if ( ! $user ) {
				return array(
					'success' => false,
					'message' => 'Usuário não encontrado',
				);
			}

			// Update verification status.
			$this->updateVerificationStatus( $user_id, 'verified', null, $reviewer_id );

			// Send email.
			$this->sendAccountReleasedEmail( $user_id );

			// Log event.
			$this->logVerificationEvent(
				$user_id,
				'verification_approved',
				array(
					'reviewer_id' => $reviewer_id,
				)
			);

			return array(
				'success' => true,
				'message' => 'Verificação confirmada com sucesso',
			);

		} catch ( \Exception $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'VerifyInstagram::confirmVerification error: ' . $e->getMessage() );

			return array(
				'success' => false,
				'message' => 'Erro interno. Tente novamente.',
			);
		}//end try
	}

	/**
	 * Cancel verification (admin/mod).
	 *
	 * @param int    $user_id     The user ID.
	 * @param int    $reviewer_id The reviewer user ID.
	 * @param string $reason      The rejection reason.
	 * @return array Result with success status.
	 */
	public function cancelVerification( int $user_id, int $reviewer_id, string $reason = '' ): array {
		try {
			// Validate user exists.
			$user = get_user_by( 'ID', $user_id );
			if ( ! $user ) {
				return array(
					'success' => false,
					'message' => 'Usuário não encontrado',
				);
			}

			// Determine new status.
			$new_status = ! empty( $reason ) ? 'rejected' : 'awaiting_instagram_verify';

			// Update verification status.
			$this->updateVerificationStatus( $user_id, $new_status, null, $reviewer_id, $reason );

			// Log event.
			$event = ! empty( $reason ) ? 'verification_rejected' : 'verification_canceled';
			$this->logVerificationEvent(
				$user_id,
				$event,
				array(
					'reviewer_id' => $reviewer_id,
					'reason'      => $reason,
				)
			);

			return array(
				'success' => true,
				'message' => 'rejected' === $new_status ? 'Verificação rejeitada' : 'Verificação cancelada',
			);

		} catch ( \Exception $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'VerifyInstagram::cancelVerification error: ' . $e->getMessage() );

			return array(
				'success' => false,
				'message' => 'Erro interno. Tente novamente.',
			);
		}//end try
	}

	/**
	 * Get verification status for user.
	 *
	 * @param int $user_id The user ID.
	 * @return array The verification status data.
	 */
	public function getVerificationStatus( int $user_id ): array {
		global $wpdb;

		$verification_table = $wpdb->prefix . 'apollo_verifications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance critical status check.
		$verification = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . 'apollo_verifications WHERE user_id = %d ORDER BY id DESC LIMIT 1',
				$user_id
			),
			ARRAY_A
		);

		if ( ! $verification ) {
			return array(
				'status'  => 'not_found',
				'message' => 'Verificação não encontrada',
			);
		}

		$instagram    = get_user_meta( $user_id, 'apollo_instagram', true );
		$verify_token = $verification['verify_token'] ?? $this->buildVerifyToken( $instagram );

		return array(
			'status'             => $verification['verify_status'],
			'instagram_username' => $instagram,
			'verify_token'       => $verify_token,
			'submitted_at'       => $verification['submitted_at'],
			'reviewed_at'        => $verification['reviewed_at'],
			'reviewer_id'        => $verification['reviewer_id'],
			'rejection_reason'   => $verification['rejection_reason'],
			'phrase'             => $this->buildVerificationPhrase( $instagram, $verify_token ),
		);
	}

	/**
	 * Validate DM request.
	 *
	 * @param int $user_id The user ID.
	 * @return array Validation result.
	 */
	private function validateDmRequest( int $user_id ): array {
		// Check if user exists and is onboarded.
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return array(
				'valid'   => false,
				'message' => 'Usuário não encontrado',
			);
		}

		// Check if onboarding is completed.
		$onboarded = get_user_meta( $user_id, 'apollo_onboarded', true );
		if ( ! $onboarded ) {
			return array(
				'valid'   => false,
				'message' => 'Complete o onboarding primeiro',
			);
		}

		// Check verification record exists.
		global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance critical status check.
		$verification = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT verify_status FROM ' . $wpdb->prefix . 'apollo_verifications WHERE user_id = %d ORDER BY id DESC LIMIT 1',
				$user_id
			)
		);

		if ( ! $verification ) {
			return array(
				'valid'   => false,
				'message' => 'Registro de verificação não encontrado',
			);
		}

		// Check if already verified.
		if ( 'verified' === $verification->verify_status ) {
			return array(
				'valid'   => false,
				'message' => 'Conta já verificada',
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Update verification status.
	 *
	 * @param int         $user_id     The user ID.
	 * @param string      $status      The new status.
	 * @param string|null $token       The verification token.
	 * @param int|null    $reviewer_id The reviewer user ID.
	 * @param string      $reason      The rejection reason.
	 * @return void
	 */
	private function updateVerificationStatus( int $user_id, string $status, ?string $token = null, ?int $reviewer_id = null, string $reason = '' ): void {
		global $wpdb;

		$verification_table = $wpdb->prefix . 'apollo_verifications';

		$update_data = array(
			'verify_status' => $status,
		);

		if ( null !== $token ) {
			$update_data['verify_token'] = $token;
		}

		if ( 'verified' === $status || 'rejected' === $status ) {
			$update_data['reviewed_at'] = current_time( 'mysql' );
			if ( $reviewer_id ) {
				$update_data['reviewer_id'] = $reviewer_id;
			}
		}

		if ( 'rejected' === $status && ! empty( $reason ) ) {
			$update_data['rejection_reason'] = $reason;
		}

		if ( 'dm_requested' === $status ) {
			$update_data['submitted_at'] = current_time( 'mysql' );
		}

		// Build format array for each field.
		$formats = array();
		foreach ( $update_data as $key => $value ) {
			if ( 'reviewer_id' === $key ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Status update.
		$wpdb->update(
			$verification_table,
			$update_data,
			array( 'user_id' => $user_id ),
			$formats,
			array( '%d' )
		);

		// Update user meta.
		update_user_meta( $user_id, 'apollo_verify_status', $status );
		if ( $token ) {
			update_user_meta( $user_id, 'apollo_verify_token', $token );
		}
	}

	/**
	 * Build verification token (deterministic: YYYYMMDD_username).
	 *
	 * @param string $instagram The Instagram username.
	 * @return string The verification token.
	 */
	private function buildVerifyToken( string $instagram ): string {
		$now      = new \DateTimeImmutable( 'now', new \DateTimeZone( 'America/Sao_Paulo' ) );
		$date_str = $now->format( 'Ymd' );
		$username = strtolower( trim( $instagram, '@' ) );

		return $date_str . '_' . $username;
	}

	/**
	 * Build verification phrase.
	 *
	 * @param string $instagram The Instagram username.
	 * @param string $token     The verification token.
	 * @return string The verification phrase.
	 */
	private function buildVerificationPhrase( string $instagram, string $token ): string {
		$username = trim( $instagram, '@' );

		return "eu sou @{$username} no apollo :: {$token}";
	}

	/**
	 * Send account released email.
	 *
	 * @param int $user_id The user ID.
	 * @return void
	 */
	private function sendAccountReleasedEmail( int $user_id ): void {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		$subject  = 'Apollo — Account Released';
		$message  = "EMAIL ACCOUN RELEASED! WELCOME TO OUR WORLD, WELCOME TO APOLLO!\n\n";
		$message .= "Sua conta foi verificada e liberada. Bem-vindo ao Apollo!\n\n";
		$message .= 'Acesse: ' . home_url() . "\n\n";
		$message .= 'Equipe Apollo';

		wp_mail( $user->user_email, $subject, $message );

		// Log email sent.
		$this->logVerificationEvent( $user_id, 'account_released_email_sent', array() );
	}

	/**
	 * Log verification event.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $event   The event name.
	 * @param array  $data    Additional event data.
	 * @return void
	 */
	private function logVerificationEvent( int $user_id, string $event, array $data ): void {
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
			return;
		}

		$ip      = $this->getClientIp();
		$ip_hash = hash( 'sha256', $ip );
		$ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$ua_hash = hash( 'sha256', $ua );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Audit logging.
		$wpdb->insert(
			$audit_table,
			array(
				'user_id'     => $user_id,
				'action'      => $event,
				'entity_type' => 'verification',
				'entity_id'   => $user_id,
				'metadata'    => wp_json_encode(
					array(
						'ip_hash' => $ip_hash,
						'ua_hash' => $ua_hash,
						'data'    => $data,
					)
				),
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @return string The client IP address or 'unknown'.
	 */
	private function getClientIp(): string {
		$ip_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) && ! empty( $_SERVER[ $header ] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP address validation.
				return sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			}
		}

		return 'unknown';
	}
}
