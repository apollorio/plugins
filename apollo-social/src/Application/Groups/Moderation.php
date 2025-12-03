<?php

namespace Apollo\Application\Groups;

/**
 * Group Moderation Use Cases
 *
 * Handles group creation, approval, and rejection workflows.
 */
class Moderation {

	/**
	 * Submit group for review
	 */
	public function submitForReview( int $group_id, array $data = array() ): array {
		global $wpdb;

		$groups_table     = $wpdb->prefix . 'apollo_groups';
		$moderation_table = $wpdb->prefix . 'apollo_moderation_queue';

		// Update group status
		$wpdb->update(
			$groups_table,
			array(
				'status'     => 'pending_review',
				'updated_at' => \current_time( 'mysql' ),
			),
			array( 'id' => $group_id )
		);

		// Add to moderation queue
		$wpdb->insert(
			$moderation_table,
			array(
				'entity_id'       => $group_id,
				'entity_type'     => 'group',
				'submitter_id'    => \get_current_user_id(),
				'submission_data' => json_encode( $data ),
				'status'          => 'pending',
				'priority'        => $this->calculatePriority( $data ),
				'submitted_at'    => \current_time( 'mysql' ),
				'metadata'        => json_encode(
					array(
						'ip_address'        => $this->getClientIp(),
						'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
						'submission_reason' => $data['reason'] ?? 'Criação de grupo',
					)
				),
			)
		);

		// Notify moderators
		$this->notifyModerators( $group_id, 'group', $data );

		return array(
			'success' => true,
			'message' => 'Grupo enviado para moderação',
			'status'  => 'pending_review',
		);
	}

	/**
	 * Approve group
	 */
	public function approveGroup( int $group_id, int $moderator_id, string $notes = '' ): array {
		global $wpdb;

		$groups_table     = $wpdb->prefix . 'apollo_groups';
		$moderation_table = $wpdb->prefix . 'apollo_moderation_queue';

		// Update group status
		$wpdb->update(
			$groups_table,
			array(
				'status'       => 'published',
				'published_at' => \current_time( 'mysql' ),
				'updated_at'   => \current_time( 'mysql' ),
			),
			array( 'id' => $group_id )
		);

		// Update moderation record
		$wpdb->update(
			$moderation_table,
			array(
				'status'          => 'approved',
				'moderator_id'    => $moderator_id,
				'reviewed_at'     => \current_time( 'mysql' ),
				'moderator_notes' => $notes,
			),
			array(
				'entity_id'   => $group_id,
				'entity_type' => 'group',
				'status'      => 'pending',
			)
		);

		// Get group data for notifications
		$group = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $group_id ),
			ARRAY_A
		);

		// Notify submitter
		$this->notifySubmitter( $group['creator_id'], 'approved', $group['name'], $notes );

		// Award badges
		$this->awardApprovalBadges( $group['creator_id'], 'group' );

		return array(
			'success' => true,
			'message' => 'Grupo aprovado com sucesso',
		);
	}

	/**
	 * Reject group with sanitized reason
	 */
	public function rejectGroup( int $group_id, int $moderator_id, string $reason ): array {
		global $wpdb;

		$groups_table     = $wpdb->prefix . 'apollo_groups';
		$moderation_table = $wpdb->prefix . 'apollo_moderation_queue';

		// Sanitize rejection reason - allow only <br> and <span class="apollo-reason">
		$sanitized_reason = $this->sanitizeRejectionReason( $reason );

		$wpdb->query( 'START TRANSACTION' );

		try {
			// Update group status
			$group_updated = $wpdb->update(
				$groups_table,
				array(
					'status'     => 'rejected',
					'updated_at' => \current_time( 'mysql' ),
				),
				array( 'id' => $group_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			// Update moderation record
			$queue_updated = $wpdb->update(
				$moderation_table,
				array(
					'status'          => 'rejected',
					'moderator_id'    => $moderator_id,
					'reviewed_at'     => \current_time( 'mysql' ),
					'moderator_notes' => $sanitized_reason,
					'metadata'        => json_encode(
						array(
							'rejection_reason' => $sanitized_reason,
							'moderator_ip'     => $this->getClientIp(),
							'action_timestamp' => time(),
						)
					),
				),
				array(
					'entity_id'   => $group_id,
					'entity_type' => 'group',
					'status'      => 'pending',
				),
				array( '%s', '%d', '%s', '%s', '%s' ),
				array( '%d', '%s', '%s' )
			);

			if ( $group_updated !== false && $queue_updated !== false ) {
				$wpdb->query( 'COMMIT' );

				// Get group data for notifications
				$group = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $group_id ),
					ARRAY_A
				);

				// Notify submitter with standard rejection message
				$this->notifyRejection( $group['creator_id'], $group['name'], $sanitized_reason );

				return array(
					'success' => true,
					'message' => 'Grupo rejeitado',
					'reason'  => $sanitized_reason,
				);
			} else {
				$wpdb->query( 'ROLLBACK' );
				return array(
					'success' => false,
					'message' => 'Erro ao rejeitar grupo',
				);
			}//end if
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return array(
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage(),
			);
		}//end try
	}

	/**
	 * Check if group requires approval
	 */
	public function requiresApproval( string $group_type, array $data = array() ): bool {
		// Community and Núcleo always require approval
		if ( in_array( $group_type, array( 'comunidade', 'nucleo' ) ) ) {
			return true;
		}

		// Check user role permissions
		$user = \wp_get_current_user();

		// Administrators and editors can publish directly
		if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles ) ) {
			return false;
		}

		// Others need approval
		return true;
	}

	/**
	 * Calculate moderation priority
	 */
	private function calculatePriority( array $data ): string {
		// High priority for urgent requests
		if ( ! empty( $data['is_urgent'] ) ) {
			return 'high';
		}

		// High priority for large communities
		if ( ! empty( $data['expected_members'] ) && $data['expected_members'] > 100 ) {
			return 'high';
		}

		// Medium priority for events
		if ( ! empty( $data['has_events'] ) ) {
			return 'medium';
		}

		return 'normal';
	}

	/**
	 * Notify moderators
	 */
	private function notifyModerators( int $entity_id, string $entity_type, array $data ): void {
		// Get moderators
		$moderators = \get_users(
			array(
				'capability' => 'apollo_moderate_groups',
				'fields'     => 'ID',
			)
		);

		foreach ( $moderators as $moderator_id ) {
			$this->createNotification(
				$moderator_id,
				array(
					'type'        => 'moderation_request',
					'entity_id'   => $entity_id,
					'entity_type' => $entity_type,
					'message'     => 'Novo ' . $entity_type . ' aguardando aprovação',
					'action_url'  => '/apollo/admin/moderation',
				)
			);
		}
	}

	/**
	 * Notify submitter
	 */
	private function notifySubmitter( int $user_id, string $decision, string $entity_name, string $notes ): void {
		if ( $decision === 'approved' ) {
			$message = "Parabéns! Seu grupo '{$entity_name}' foi aprovado e já está disponível na plataforma.";

			if ( $notes ) {
				$message .= "\n\nNotas do moderador: {$notes}";
			}
		} else {
			$message  = "Apollo rejeitou a inclusão do grupo '{$entity_name}'.<br>";
			$message .= "Motivo: <span class=\"apollo-reason\">{$notes}</span>";
		}

		$this->createNotification(
			$user_id,
			array(
				'type'        => 'moderation_result',
				'decision'    => $decision,
				'entity_name' => $entity_name,
				'message'     => $message,
				'action_url'  => $decision === 'approved' ? '/apollo/groups/' . urlencode( $entity_name ) : '/apollo/groups/create',
			)
		);
	}

	/**
	 * Award approval badges
	 */
	private function awardApprovalBadges( int $user_id, string $entity_type ): void {
		$badge_map = array(
			'group'  => 'community_builder',
			'nucleo' => 'nucleo_founder',
		);

		if ( isset( $badge_map[ $entity_type ] ) ) {
			\do_action(
				'apollo_award_badge',
				$user_id,
				$badge_map[ $entity_type ],
				array(
					'reason'      => 'group_approved',
					'entity_type' => $entity_type,
				)
			);
		}
	}

	/**
	 * Create notification
	 */
	private function createNotification( int $user_id, array $data ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_notifications';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'type'       => $data['type'],
				'title'      => $data['message'],
				'content'    => $data['message'],
				'data'       => json_encode( $data ),
				'read'       => 0,
				'created_at' => \current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get client IP
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
			if ( ! empty( $_SERVER[ $header ] ) ) {
				return $_SERVER[ $header ];
			}
		}

		return 'unknown';
	}

	/**
	 * Sanitize rejection reason - allow only <br> and <span class="apollo-reason">
	 */
	private function sanitizeRejectionReason( string $reason ): string {
		// Allow only <br> and <span class="apollo-reason">
		$allowed_tags = array(
			'br'   => array(),
			'span' => array( 'class' => true ),
		);

		$sanitized = \wp_kses( $reason, $allowed_tags );

		// Ensure only apollo-reason class is allowed for spans
		$sanitized = preg_replace(
			'/<span[^>]*class="[^"]*"[^>]*>/',
			'<span class="apollo-reason">',
			$sanitized
		);

		// Remove empty spans
		$sanitized = preg_replace( '/<span[^>]*><\/span>/', '', $sanitized );

		return trim( $sanitized );
	}

	/**
	 * Send rejection notification with standard Apollo message
	 */
	private function notifyRejection( int $user_id, string $group_name, string $reason ): void {
		// Standard Apollo rejection message format
		$message = "Apollo rejeitou sua inclusão..<br>Motivo: <span class=\"apollo-reason\">{$reason}</span>";

		$this->createNotification(
			$user_id,
			array(
				'type'         => 'group_rejected',
				'title'        => 'Grupo rejeitado',
				'message'      => $message,
				'group_name'   => $group_name,
				'reason'       => $reason,
				'timestamp'    => current_time( 'mysql' ),
				'can_resubmit' => true,
			)
		);

		// Also store in user meta for immediate display
		update_user_meta(
			$user_id,
			'apollo_latest_rejection',
			array(
				'message'    => $message,
				'group_name' => $group_name,
				'timestamp'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get rejection notice for display (STANDARD MESSAGE)
	 */
	public function getRejectionNotice( int $group_id ): ?array {
		global $wpdb;

		$rejection = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT moderator_notes as reason, reviewed_at, moderator_id
             FROM {$wpdb->prefix}apollo_moderation_queue 
             WHERE entity_id = %d AND entity_type = 'group' AND status = 'rejected'
             ORDER BY reviewed_at DESC LIMIT 1",
				$group_id
			)
		);

		if ( ! $rejection ) {
			return null;
		}

		return array(
			'message'      => "Apollo rejeitou sua inclusão..<br>Motivo: <span class=\"apollo-reason\">{$rejection->reason}</span>",
			'reviewed_at'  => $rejection->reviewed_at,
			'moderator_id' => $rejection->moderator_id,
			'can_resubmit' => true,
		);
	}
}
