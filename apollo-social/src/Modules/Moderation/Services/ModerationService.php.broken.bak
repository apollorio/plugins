<?php

namespace Apollo\Modules\Moderation\Services;

/**
 * Moderation Service
 *
 * Handles approval/rejection workflows for groups, núcleos, and content.
 */
class ModerationService {

	/**
	 * Submit group/núcleo for mod
	 */
	public function submitForReview( int $entity_id, string $entity_type, array $submission_data ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod

		// Check if already in mod
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE entity_id = %d AND entity_type = %s AND status = 'pending'",
				$entity_id,
				$entity_type
			)
		);

		if ( $existing ) {
			return [
				'success' => false,
				'error'   => 'Entidade já está em processo de moderação',
			];
		}

		$mod
			'entity_id'                    => $entity_id,
			'entity_type'                  => $entity_type,
			// 'group', 'nucleo', 'event', 'ad'
							'submitter_id' => get_current_user_id(),
			'submission_data'              => json_encode( $submission_data ),
			'status'                       => 'pending',
			'priority'                     => $this->calculatePriority( $entity_type, $submission_data ),
			'submitted_at'                 => current_time( 'mysql' ),
			'metadata'                     => json_encode(
				[
					'ip_address'        => $this->getClientIp(),
					'user_agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
					'submission_reason' => $submission_data['reason'] ?? 'Criação/Atualização',
				]
			),
		];

		$result = $wpdb->insert( $table_name, $mod

		if ( $result ) {
			// Update entity status
			$this->updateEntityStatus( $entity_id, $entity_type, 'pending_review' );

			// Notify moderators
			$this->notifyModerators( $entity_id, $entity_type, $submission_data );

			return [
				'success'       => true,
				'mod
				'message'       => 'Enviado para moderação com sucesso',
			];
		}

		return [
			'success' => false,
			'error'   => 'Erro ao enviar para moderação',
		];
	}

	/**
	 * Approve mod
	 */
	public function approve( int $mod
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod

		$mod
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $mod
			ARRAY_A
		);

		if ( ! $modmod
			return [
				'success' => false,
				'error'   => 'Item de moderação não encontrado ou já processado',
			];
		}

		// Update mod
		$wpdb->update(
			$table_name,
			[
				'status'            => 'approved',
				'moderator_id'      => $moderator_id,
				'reviewed_at'       => current_time( 'mysql' ),
				'moderator_notes'   => $notes,
				'decision_metadata' => json_encode(
					[
						'ip_address'      => $this->getClientIp(),
						'approval_reason' => $notes,
					]
				),
			],
			[ 'id' => $mod
		);

		// Update entity status
		$this->updateEntityStatus( $modmod

		// Apply approved changes
		$this->applyApprovedChanges( $mod

		// Notify submitter
		$this->notifySubmitter( $mod

		// Award mod
		$this->awardModerationBadges( $modmod

		return [
			'success' => true,
			'message' => 'Item aprovado com sucesso',
		];
	}

	/**
	 * Reject mod
	 */
	public function reject( int $mod
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod

		$mod
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $mod
			ARRAY_A
		);

		if ( ! $modmod
			return [
				'success' => false,
				'error'   => 'Item de moderação não encontrado ou já processado',
			];
		}

		// Update mod
		$wpdb->update(
			$table_name,
			[
				'status'            => 'rejected',
				'moderator_id'      => $moderator_id,
				'reviewed_at'       => current_time( 'mysql' ),
				'moderator_notes'   => $reason,
				'decision_metadata' => json_encode(
					[
						'ip_address'       => $this->getClientIp(),
						'rejection_reason' => $reason,
					]
				),
			],
			[ 'id' => $mod
		);

		// Update entity status
		$this->updateEntityStatus( $modmod

		// Notify submitter
		$this->notifySubmitter( $mod

		return [
			'success' => true,
			'message' => 'Item rejeitado com sucesso',
		];
	}

	/**
	 * Get mod
	 */
	public function getModerationQueue( array $filters = [] ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod

		$where_conditions = [ '1=1' ];
		$where_values     = [];

		if ( ! empty( $filters['status'] ) ) {
			$where_conditions[] = 'status = %s';
			$where_values[]     = $filters['status'];
		}

		if ( ! empty( $filters['entity_type'] ) ) {
			$where_conditions[] = 'entity_type = %s';
			$where_values[]     = $filters['entity_type'];
		}

		if ( ! empty( $filters['priority'] ) ) {
			$where_conditions[] = 'priority = %s';
			$where_values[]     = $filters['priority'];
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY priority DESC, submitted_at ASC";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, ...$where_values );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Enrich with entity data
		foreach ( $results as &$item ) {
			$item['entity_data']    = $this->getEntityData( $item['entity_id'], $item['entity_type'] );
			$item['submitter_data'] = $this->getUserData( $item['submitter_id'] );

			if ( $item['moderator_id'] ) {
				$item['moderator_data'] = $this->getUserData( $item['moderator_id'] );
			}
		}

		return $results;
	}

	/**
	 * Get mod
	 */
	public function getModerationStats(): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_mod

		$stats = $wpdb->get_results(
			"
            SELECT 
                status,
                entity_type,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(HOUR, submitted_at, COALESCE(reviewed_at, NOW()))) as avg_processing_hours
            FROM {$table_name} 
            WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY status, entity_type
        ",
			ARRAY_A
		);

		$processed_stats = [
			'pending'             => 0,
			'approved'            => 0,
			'rejected'            => 0,
			'total'               => 0,
			'avg_processing_time' => 0,
			'by_entity_type'      => [],
		];

		foreach ( $stats as $stat ) {
			$processed_stats[ $stat['status'] ] += $stat['count'];
			$processed_stats['total']           += $stat['count'];

			$processed_stats['by_entity_type'][ $stat['entity_type'] ][ $stat['status'] ] = $stat['count'];

			if ( $stat['status'] !== 'pending' ) {
				$processed_stats['avg_processing_time'] += $stat['avg_processing_hours'] * $stat['count'];
			}
		}

		if ( $processed_stats['approved'] + $processed_stats['rejected'] > 0 ) {
			$processed_stats['avg_processing_time'] /= ( $processed_stats['approved'] + $processed_stats['rejected'] );
		}

		return $processed_stats;
	}

	/**
	 * Calculate priority based on entity type and data
	 */
	private function calculatePriority( string $entity_type, array $submission_data ): string {
		// High priority for critical content
		if ( in_array( $entity_type, [ 'group', 'nucleo' ] ) ) {
			if ( ! empty( $submission_data['is_urgent'] ) || ! empty( $submission_data['members_count'] ) && $submission_data['members_count'] > 100 ) {
				return 'high';
			}
		}

		// Medium priority for events
		if ( $entity_type === 'event' ) {
			$event_date = $submission_data['event_date'] ?? '';
			if ( $event_date && strtotime( $event_date ) < strtotime( '+7 days' ) ) {
				return 'high';
			}
			return 'medium';
		}

		return 'normal';
	}

	/**
	 * Update entity status
	 */
	private function updateEntityStatus( int $entity_id, string $entity_type, string $status ): void {
		global $wpdb;

		$table_map = [
			'group'  => $wpdb->prefix . 'apollo_groups',
			'nucleo' => $wpdb->prefix . 'apollo_nucleos',
			'event'  => $wpdb->prefix . 'apollo_events',
			'ad'     => $wpdb->prefix . 'apollo_ads',
		];

		if ( isset( $table_map[ $entity_type ] ) ) {
			$wpdb->update(
				$table_map[ $entity_type ],
				[
					'status'     => $status,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $entity_id ]
			);
		}
	}

	/**
	 * Apply approved changes to entity
	 */
	private function applyApprovedChanges( array $mod
		$submission_data = json_decode( $mod

		// Apply changes based on entity type
		switch ( $mod
			case 'group':
			case 'nucleo':
				$this->applyGroupChanges( $mod
				break;

			case 'event':
				$this->applyEventChanges( $mod
				break;

			case 'ad':
				$this->applyAdChanges( $mod
				break;
		}
	}

	/**
	 * Apply approved changes to group/núcleo
	 */
	private function applyGroupChanges( int $group_id, array $changes ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_groups';

		$allowed_changes = [
			'name',
			'description',
			'visibility',
			'join_policy',
			'location',
			'tags',
			'cover_image',
			'member_limit',
		];

		$update_data = [];
		foreach ( $allowed_changes as $field ) {
			if ( isset( $changes[ $field ] ) ) {
				$update_data[ $field ] = $changes[ $field ];
			}
		}

		if ( ! empty( $update_data ) ) {
			$update_data['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table_name, $update_data, [ 'id' => $group_id ] );
		}
	}

	/**
	 * Notify moderators of new submission
	 */
	private function notifyModerators( int $entity_id, string $entity_type, array $submission_data ): void {
		// Get moderators for this entity type
		$moderators = $this->getModeratorsForEntityType( $entity_type );

		$notification_data = [
			'type'         => 'mod
			'entity_id'    => $entity_id,
			'entity_type'  => $entity_type,
			'priority'     => $this->calculatePriority( $entity_type, $submission_data ),
			'submitter_id' => get_current_user_id(),
		];

		foreach ( $moderators as $moderator_id ) {
			$this->createNotification( $moderator_id, $notification_data );
		}
	}

	/**
	 * Award mod
	 */
	private function awardModerationBadges( int $entity_id, string $entity_type, string $decision ): void {
		$user_id = get_current_user_id();

		// Badge for approved content
		if ( $decision === 'approved' ) {
			$badge_rules = [
				'group'  => 'community_builder',
				'nucleo' => 'nucleo_founder',
				'event'  => 'event_organizer',
				'ad'     => 'marketplace_seller',
			];

			if ( isset( $badge_rules[ $entity_type ] ) ) {
				// Award badge (assuming badge system exists)
				do_action(
					'apollo_award_badge',
					$user_id,
					$badge_rules[ $entity_type ],
					[
						'reason'      => 'content_approved',
						'entity_id'   => $entity_id,
						'entity_type' => $entity_type,
					]
				);
			}
		}//end if
	}

	/**
	 * Get client IP address
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
			if ( ! empty( $_SERVER[ $header ] ) ) {
				return $_SERVER[ $header ];
			}
		}

		return 'unknown';
	}

	/**
	 * Get entity data
	 */
	private function getEntityData( int $entity_id, string $entity_type ): array {
		global $wpdb;

		$table_map = [
			'group'  => $wpdb->prefix . 'apollo_groups',
			'nucleo' => $wpdb->prefix . 'apollo_nucleos',
			'event'  => $wpdb->prefix . 'apollo_events',
			'ad'     => $wpdb->prefix . 'apollo_ads',
		];

		if ( isset( $table_map[ $entity_type ] ) ) {
			return $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table_map[$entity_type]} WHERE id = %d", $entity_id ),
				ARRAY_A
			) ?: [];
		}

		return [];
	}

	/**
	 * Get user data
	 */
	private function getUserData( int $user_id ): array {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return [];
		}

		return [
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'email'  => $user->user_email,
			'avatar' => get_avatar_url( $user->ID ),
		];
	}

	/**
	 * Get moderators for entity type
	 */
	private function getModeratorsForEntityType( string $entity_type ): array {
		// Get users with mod
		$moderators = get_users(
			[
				'capability' => 'apollo_moderate_' . $entity_type,
				'fields'     => 'ID',
			]
		);

		return array_map( 'intval', $moderators );
	}

	/**
	 * Create notification for user
	 */
	private function createNotification( int $user_id, array $data ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_notifications';

		$wpdb->insert(
			$table_name,
			[
				'user_id'    => $user_id,
				'type'       => $data['type'],
				'data'       => json_encode( $data ),
				'read'       => 0,
				'created_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Notify submitter of decision
	 */
	private function notifySubmitter( array $mod
		$notification_data = [
			'type'        => 'mod
			'decision'    => $decision,
			'entity_id'   => $mod
			'entity_type' => $mod
			'notes'       => $notes,
		];

		$this->createNotification( $mod
	}
}
