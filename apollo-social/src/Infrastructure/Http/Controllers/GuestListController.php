<?php
/**
 * Guest List REST Controller
 *
 * Manages guest lists for events, including adding guests,
 * check-in functionality, and promoter allocations.
 *
 * @package Apollo\Infrastructure\Http\Controllers
 * @since 2.1.0
 */

namespace Apollo\Infrastructure\Http\Controllers;

/**
 * Guest List REST Controller
 *
 * Endpoints:
 * - GET  /lista/{event_id}              - Get guest list for event
 * - POST /lista/{event_id}/add          - Add guest to list
 * - POST /lista/{event_id}/checkin      - Check-in guest
 * - DELETE /lista/{event_id}/{guest_id} - Remove guest
 * - GET  /lista/{event_id}/stats        - Get list statistics
 * - POST /lista/{event_id}/alocar       - Allocate spots to promoter
 * - GET  /lista/minhas                  - My guest list allocations
 */
class GuestListController extends BaseController {

	/**
	 * Table name for guest list
	 */
	private function getTableName(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_guest_list';
	}

	/**
	 * Table name for allocations
	 */
	private function getAllocationsTableName(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_guest_allocations';
	}

	/**
	 * GET /apollo/v1/lista/{event_id}
	 *
	 * Get guest list for an event
	 */
	public function index( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$user_id  = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check permission (author, admin, or promoter with allocation)
		$can_view = $this->canManageGuestList( $event, $user_id );
		if ( ! $can_view ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para ver esta lista.',
				),
				403
			);
		}

		$this->ensureTablesExist();

		global $wpdb;
		$table = $this->getTableName();

		// Get filters
		$status     = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		$promoter   = (int) $request->get_param( 'promoter_id' );
		$search     = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$checked_in = $request->get_param( 'checked_in' );

		$where  = array( 'event_id = %d' );
		$params = array( $event_id );

		// If user is promoter (not author/admin), only show their guests
		if ( ! current_user_can( 'manage_options' ) && (int) $event->post_author !== $user_id ) {
			$where[]  = 'added_by = %d';
			$params[] = $user_id;
		} elseif ( $promoter ) {
			$where[]  = 'added_by = %d';
			$params[] = $promoter;
		}

		if ( $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( $search ) {
			$where[]  = '(guest_name LIKE %s OR guest_email LIKE %s OR guest_phone LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( $checked_in !== null ) {
			if ( $checked_in === 'true' || $checked_in === '1' ) {
				$where[] = 'checked_in_at IS NOT NULL';
			} else {
				$where[] = 'checked_in_at IS NULL';
			}
		}

		$where_clause = implode( ' AND ', $where );

		$guests = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC",
				$params
			)
		);

		$guests_data = array();
		foreach ( $guests as $guest ) {
			$added_by_user = get_userdata( $guest->added_by );
			$guests_data[] = array(
				'id'            => (int) $guest->id,
				'guest_name'    => $guest->guest_name,
				'guest_email'   => $guest->guest_email,
				'guest_phone'   => $guest->guest_phone,
				'guest_user_id' => $guest->guest_user_id ? (int) $guest->guest_user_id : null,
				'plus_one'      => (int) $guest->plus_one,
				'status'        => $guest->status,
				'notes'         => $guest->notes,
				'added_by'      => array(
					'id'   => (int) $guest->added_by,
					'name' => $added_by_user ? $added_by_user->display_name : 'Unknown',
				),
				'checked_in_at' => $guest->checked_in_at,
				'checked_in_by' => $guest->checked_in_by ? (int) $guest->checked_in_by : null,
				'created_at'    => $guest->created_at,
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'guests' => $guests_data,
					'total'  => count( $guests_data ),
				),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/lista/{event_id}/add
	 *
	 * Add guest to event list
	 */
	public function add( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$user_id  = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check permission
		if ( ! $this->canManageGuestList( $event, $user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para adicionar nesta lista.',
				),
				403
			);
		}

		// Check allocation limits for promoters
		if ( ! current_user_can( 'manage_options' ) && (int) $event->post_author !== $user_id ) {
			$remaining = $this->getRemainingAllocation( $event_id, $user_id );
			if ( $remaining <= 0 ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Você atingiu seu limite de convidados para este evento.',
					),
					403
				);
			}
		}

		$guest_name    = sanitize_text_field( $request->get_param( 'name' ) );
		$guest_email   = sanitize_email( $request->get_param( 'email' ) ?? '' );
		$guest_phone   = sanitize_text_field( $request->get_param( 'phone' ) ?? '' );
		$guest_user_id = (int) $request->get_param( 'user_id' );
		$plus_one      = max( 0, min( 5, (int) $request->get_param( 'plus_one' ) ) );
		$notes         = sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' );

		if ( empty( $guest_name ) && ! $guest_user_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Nome do convidado é obrigatório.',
				),
				400
			);
		}

		// If user_id provided, get name from user
		if ( $guest_user_id ) {
			$guest_user = get_userdata( $guest_user_id );
			if ( $guest_user ) {
				$guest_name  = $guest_name ?: $guest_user->display_name;
				$guest_email = $guest_email ?: $guest_user->user_email;
			}
		}

		$this->ensureTablesExist();

		global $wpdb;
		$table = $this->getTableName();

		// Check for duplicate
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_id = %d AND (guest_email = %s OR guest_user_id = %d) AND status != 'cancelled'",
				$event_id,
				$guest_email,
				$guest_user_id
			)
		);

		if ( $existing && ( $guest_email || $guest_user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Este convidado já está na lista.',
				),
				400
			);
		}

		$result = $wpdb->insert(
			$table,
			array(
				'event_id'      => $event_id,
				'guest_name'    => $guest_name,
				'guest_email'   => $guest_email,
				'guest_phone'   => $guest_phone,
				'guest_user_id' => $guest_user_id ?: null,
				'plus_one'      => $plus_one,
				'status'        => 'confirmed',
				'notes'         => $notes,
				'added_by'      => $user_id,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao adicionar convidado.',
				),
				500
			);
		}

		$guest_id = $wpdb->insert_id;

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Convidado adicionado!',
				'data'    => array(
					'id'         => $guest_id,
					'guest_name' => $guest_name,
					'plus_one'   => $plus_one,
				),
			),
			201
		);
	}

	/**
	 * POST /apollo/v1/lista/{event_id}/checkin
	 *
	 * Check-in a guest
	 */
	public function checkin( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$guest_id = (int) $request->get_param( 'guest_id' );
		$user_id  = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Check permission (only author/admin can check-in)
		if ( (int) $event->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Apenas organizadores podem fazer check-in.',
				),
				403
			);
		}

		global $wpdb;
		$table = $this->getTableName();

		$guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND event_id = %d",
				$guest_id,
				$event_id
			)
		);

		if ( ! $guest ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Convidado não encontrado.',
				),
				404
			);
		}

		if ( $guest->checked_in_at ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Convidado já fez check-in.',
					'data'    => array(
						'checked_in_at' => $guest->checked_in_at,
						'already'       => true,
					),
				),
				200
			);
		}

		$wpdb->update(
			$table,
			array(
				'checked_in_at' => current_time( 'mysql' ),
				'checked_in_by' => $user_id,
				'status'        => 'attended',
			),
			array( 'id' => $guest_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Check-in realizado!',
				'data'    => array(
					'guest_name'    => $guest->guest_name,
					'plus_one'      => (int) $guest->plus_one,
					'checked_in_at' => current_time( 'mysql' ),
				),
			),
			200
		);
	}

	/**
	 * DELETE /apollo/v1/lista/{event_id}/{guest_id}
	 *
	 * Remove guest from list
	 */
	public function remove( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$guest_id = (int) $request->get_param( 'guest_id' );
		$user_id  = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		global $wpdb;
		$table = $this->getTableName();

		$guest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND event_id = %d",
				$guest_id,
				$event_id
			)
		);

		if ( ! $guest ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Convidado não encontrado.',
				),
				404
			);
		}

		// Check permission (author, admin, or the promoter who added)
		$can_remove = current_user_can( 'manage_options' ) ||
					  (int) $event->post_author === $user_id ||
					  (int) $guest->added_by === $user_id;

		if ( ! $can_remove ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não pode remover este convidado.',
				),
				403
			);
		}

		// Soft delete (cancel)
		$wpdb->update(
			$table,
			array( 'status' => 'cancelled' ),
			array( 'id' => $guest_id ),
			array( '%s' ),
			array( '%d' )
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Convidado removido da lista.',
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/lista/{event_id}/stats
	 *
	 * Get guest list statistics
	 */
	public function stats( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$user_id  = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		if ( ! $this->canManageGuestList( $event, $user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Sem permissão.',
				),
				403
			);
		}

		global $wpdb;
		$table = $this->getTableName();

		// Total confirmed
		$total_guests = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND status != 'cancelled'",
				$event_id
			)
		);

		// Total plus ones
		$total_plus_ones = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(plus_one) FROM {$table} WHERE event_id = %d AND status != 'cancelled'",
				$event_id
			)
		);

		// Checked in
		$checked_in = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND checked_in_at IS NOT NULL",
				$event_id
			)
		);

		// By promoter
		$by_promoter = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT added_by, COUNT(*) as count, SUM(plus_one) as plus_ones
				FROM {$table}
				WHERE event_id = %d AND status != 'cancelled'
				GROUP BY added_by",
				$event_id
			)
		);

		$promoter_stats = array();
		foreach ( $by_promoter as $p ) {
			$promoter_user = get_userdata( $p->added_by );
			$promoter_stats[] = array(
				'promoter_id'   => (int) $p->added_by,
				'promoter_name' => $promoter_user ? $promoter_user->display_name : 'Unknown',
				'guests_count'  => (int) $p->count,
				'plus_ones'     => (int) $p->plus_ones,
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'total_guests'    => $total_guests,
					'total_plus_ones' => $total_plus_ones,
					'total_people'    => $total_guests + $total_plus_ones,
					'checked_in'      => $checked_in,
					'pending_checkin' => $total_guests - $checked_in,
					'by_promoter'     => $promoter_stats,
				),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/lista/{event_id}/alocar
	 *
	 * Allocate guest list spots to a promoter
	 */
	public function alocar( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id    = (int) $request->get_param( 'event_id' );
		$promoter_id = (int) $request->get_param( 'promoter_id' );
		$spots       = (int) $request->get_param( 'spots' );
		$user_id     = get_current_user_id();

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Evento não encontrado.',
				),
				404
			);
		}

		// Only author/admin can allocate
		if ( (int) $event->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Apenas organizadores podem alocar vagas.',
				),
				403
			);
		}

		if ( ! $promoter_id || $spots < 0 ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Dados inválidos.',
				),
				400
			);
		}

		$this->ensureTablesExist();

		global $wpdb;
		$table = $this->getAllocationsTableName();

		// Check if allocation exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_id = %d AND promoter_id = %d",
				$event_id,
				$promoter_id
			)
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'allocated_spots' => $spots,
					'updated_at'      => current_time( 'mysql' ),
				),
				array( 'id' => $existing ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'event_id'        => $event_id,
					'promoter_id'     => $promoter_id,
					'allocated_spots' => $spots,
					'allocated_by'    => $user_id,
					'created_at'      => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%d', '%s' )
			);
		}

		$promoter = get_userdata( $promoter_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf( '%d vagas alocadas para %s.', $spots, $promoter ? $promoter->display_name : 'promoter' ),
				'data'    => array(
					'promoter_id' => $promoter_id,
					'spots'       => $spots,
				),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/lista/minhas
	 *
	 * Get my guest list allocations
	 */
	public function minhas( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		$this->ensureTablesExist();

		global $wpdb;
		$alloc_table = $this->getAllocationsTableName();
		$guest_table = $this->getTableName();

		// Get all allocations for this promoter
		$allocations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*,
					(SELECT COUNT(*) FROM {$guest_table} g WHERE g.event_id = a.event_id AND g.added_by = a.promoter_id AND g.status != 'cancelled') as used_spots
				FROM {$alloc_table} a
				WHERE a.promoter_id = %d
				ORDER BY a.created_at DESC",
				$user_id
			)
		);

		$data = array();
		foreach ( $allocations as $alloc ) {
			$event = get_post( $alloc->event_id );
			if ( ! $event ) {
				continue;
			}

			$data[] = array(
				'event_id'        => (int) $alloc->event_id,
				'event_title'     => $event->post_title,
				'event_date'      => get_post_meta( $alloc->event_id, '_event_date', true ),
				'allocated_spots' => (int) $alloc->allocated_spots,
				'used_spots'      => (int) $alloc->used_spots,
				'remaining'       => (int) $alloc->allocated_spots - (int) $alloc->used_spots,
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Check if user can manage guest list for event
	 */
	private function canManageGuestList( \WP_Post $event, int $user_id ): bool {
		// Admin or author
		if ( current_user_can( 'manage_options' ) || (int) $event->post_author === $user_id ) {
			return true;
		}

		// Check if user has allocation
		global $wpdb;
		$table = $this->getAllocationsTableName();

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return false;
		}

		$allocation = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_id = %d AND promoter_id = %d",
				$event->ID,
				$user_id
			)
		);

		return (bool) $allocation;
	}

	/**
	 * Get remaining allocation for promoter
	 */
	private function getRemainingAllocation( int $event_id, int $user_id ): int {
		global $wpdb;

		$alloc_table = $this->getAllocationsTableName();
		$guest_table = $this->getTableName();

		$allocation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT allocated_spots FROM {$alloc_table} WHERE event_id = %d AND promoter_id = %d",
				$event_id,
				$user_id
			)
		);

		if ( ! $allocation ) {
			return 0;
		}

		$used = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$guest_table} WHERE event_id = %d AND added_by = %d AND status != 'cancelled'",
				$event_id,
				$user_id
			)
		);

		return max( 0, (int) $allocation->allocated_spots - $used );
	}

	/**
	 * Ensure tables exist
	 */
	private function ensureTablesExist(): void {
		global $wpdb;

		$guest_table = $this->getTableName();
		$alloc_table = $this->getAllocationsTableName();
		$charset     = $wpdb->get_charset_collate();

		// Guest list table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$guest_table}'" ) !== $guest_table ) {
			$sql = "CREATE TABLE {$guest_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id BIGINT(20) UNSIGNED NOT NULL,
				guest_name VARCHAR(255) NOT NULL,
				guest_email VARCHAR(255) DEFAULT NULL,
				guest_phone VARCHAR(50) DEFAULT NULL,
				guest_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
				plus_one TINYINT(3) UNSIGNED DEFAULT 0,
				status ENUM('pending', 'confirmed', 'attended', 'no_show', 'cancelled') DEFAULT 'confirmed',
				notes TEXT,
				added_by BIGINT(20) UNSIGNED NOT NULL,
				checked_in_at DATETIME DEFAULT NULL,
				checked_in_by BIGINT(20) UNSIGNED DEFAULT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_event (event_id),
				KEY idx_status (status),
				KEY idx_added_by (added_by),
				KEY idx_guest_email (guest_email),
				KEY idx_checkin (checked_in_at)
			) {$charset};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Allocations table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$alloc_table}'" ) !== $alloc_table ) {
			$sql = "CREATE TABLE {$alloc_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id BIGINT(20) UNSIGNED NOT NULL,
				promoter_id BIGINT(20) UNSIGNED NOT NULL,
				allocated_spots INT UNSIGNED DEFAULT 0,
				allocated_by BIGINT(20) UNSIGNED NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY unique_alloc (event_id, promoter_id),
				KEY idx_event (event_id),
				KEY idx_promoter (promoter_id)
			) {$charset};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}
}
