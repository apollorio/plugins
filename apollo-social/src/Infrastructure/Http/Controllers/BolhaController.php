<?php
/**
 * Bolha (Friend Circles) REST Controller
 *
 * Handles friend relationships between users in the Apollo platform.
 * "Bolha" = intimate social bubble/circle in Portuguese.
 *
 * @package Apollo\Infrastructure\Http\Controllers
 * @since 2.1.0
 */

namespace Apollo\Infrastructure\Http\Controllers;

/**
 * Bolha REST Controller
 *
 * Endpoints:
 * - POST /bolha/pedir      - Send friend request
 * - POST /bolha/aceitar    - Accept friend request
 * - POST /bolha/rejeitar   - Reject friend request
 * - POST /bolha/remover    - Remove friend from circle
 * - GET  /bolha/listar     - List friends in my circle
 * - GET  /bolha/pedidos    - List pending friend requests
 * - GET  /bolha/status/{id} - Check friendship status with user
 * - POST /bolha/cancelar   - Cancel pending request
 */
class BolhaController extends BaseController {

	/**
	 * Table name for friend relationships
	 */
	private function getTableName(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_bolha';
	}

	/**
	 * POST /apollo/v1/bolha/pedir
	 *
	 * Send a friend request to another user
	 */
	public function pedir( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = get_current_user_id();
		$friend_id = (int) $request->get_param( 'user_id' );

		if ( ! $friend_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		// Cannot friend yourself
		if ( $user_id === $friend_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não pode adicionar você mesmo.',
				),
				400
			);
		}

		// Check if user exists
		$friend = get_userdata( $friend_id );
		if ( ! $friend ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Usuário não encontrado.',
				),
				404
			);
		}

		// Check existing relationship
		$status = $this->getRelationshipStatus( $user_id, $friend_id );

		if ( $status === 'friends' ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Vocês já são amigos!',
					'data'    => array( 'status' => 'friends' ),
				),
				200
			);
		}

		if ( $status === 'pending_sent' ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Você já enviou uma solicitação. Aguarde a resposta.',
					'data'    => array( 'status' => 'pending_sent' ),
				),
				200
			);
		}

		if ( $status === 'pending_received' ) {
			// Auto-accept if they already sent us a request
			$this->acceptRequest( $friend_id, $user_id );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Vocês agora são amigos! (O outro usuário já havia te enviado uma solicitação)',
					'data'    => array( 'status' => 'friends' ),
				),
				200
			);
		}

		// Create friend request
		$result = $this->createRequest( $user_id, $friend_id );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao enviar solicitação.',
				),
				500
			);
		}

		// Send notification
		$this->sendRequestNotification( $user_id, $friend_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Solicitação enviada com sucesso!',
				'data'    => array( 'status' => 'pending_sent' ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/bolha/aceitar
	 *
	 * Accept a pending friend request
	 */
	public function aceitar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id      = get_current_user_id();
		$requester_id = (int) $request->get_param( 'user_id' );

		if ( ! $requester_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		// Check if there's a pending request from this user
		$status = $this->getRelationshipStatus( $user_id, $requester_id );

		if ( $status !== 'pending_received' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Não há solicitação pendente deste usuário.',
				),
				400
			);
		}

		// Accept the request
		$result = $this->acceptRequest( $requester_id, $user_id );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao aceitar solicitação.',
				),
				500
			);
		}

		// Send notification to requester
		$this->sendAcceptNotification( $user_id, $requester_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Vocês agora são amigos!',
				'data'    => array( 'status' => 'friends' ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/bolha/rejeitar
	 *
	 * Reject a pending friend request
	 */
	public function rejeitar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id      = get_current_user_id();
		$requester_id = (int) $request->get_param( 'user_id' );

		if ( ! $requester_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		// Check if there's a pending request from this user
		$status = $this->getRelationshipStatus( $user_id, $requester_id );

		if ( $status !== 'pending_received' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Não há solicitação pendente deste usuário.',
				),
				400
			);
		}

		// Reject the request
		$result = $this->rejectRequest( $requester_id, $user_id );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao rejeitar solicitação.',
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Solicitação rejeitada.',
				'data'    => array( 'status' => 'none' ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/bolha/remover
	 *
	 * Remove a friend from your circle
	 */
	public function remover( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = get_current_user_id();
		$friend_id = (int) $request->get_param( 'user_id' );

		if ( ! $friend_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		// Check if they are friends
		$status = $this->getRelationshipStatus( $user_id, $friend_id );

		if ( $status !== 'friends' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Vocês não são amigos.',
				),
				400
			);
		}

		// Remove friendship
		$result = $this->removeFriendship( $user_id, $friend_id );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao remover amigo.',
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Amigo removido da sua bolha.',
				'data'    => array( 'status' => 'none' ),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/bolha/listar
	 *
	 * List all friends in user's circle
	 */
	public function listar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$page    = max( 1, (int) $request->get_param( 'page' ) );
		$limit   = min( 50, max( 10, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );
		$offset  = ( $page - 1 ) * $limit;

		$friends = $this->getFriends( $user_id, $limit, $offset );
		$total   = $this->getFriendsCount( $user_id );

		$friends_data = array();
		foreach ( $friends as $friend ) {
			$user = get_userdata( $friend->friend_id );
			if ( $user ) {
				$friends_data[] = array(
					'id'           => $user->ID,
					'display_name' => $user->display_name,
					'username'     => $user->user_login,
					'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
					'since'        => $friend->accepted_at,
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'friends' => $friends_data,
					'total'   => $total,
					'page'    => $page,
					'pages'   => ceil( $total / $limit ),
				),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/bolha/pedidos
	 *
	 * List pending friend requests (received)
	 */
	public function pedidos( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$type    = $request->get_param( 'type' ) ?? 'received'; // 'received' or 'sent'

		$requests = $this->getPendingRequests( $user_id, $type );

		$requests_data = array();
		foreach ( $requests as $req ) {
			$other_id = ( $type === 'received' ) ? $req->user_id : $req->friend_id;
			$user     = get_userdata( $other_id );

			if ( $user ) {
				$requests_data[] = array(
					'id'           => $req->id,
					'user_id'      => $user->ID,
					'display_name' => $user->display_name,
					'username'     => $user->user_login,
					'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
					'sent_at'      => $req->created_at,
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'requests' => $requests_data,
					'type'     => $type,
				),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/bolha/status/{id}
	 *
	 * Get friendship status with a specific user
	 */
	public function status( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id  = get_current_user_id();
		$other_id = (int) $request->get_param( 'id' );

		if ( ! $other_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		$status = $this->getRelationshipStatus( $user_id, $other_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'user_id' => $other_id,
					'status'  => $status,
				),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/bolha/cancelar
	 *
	 * Cancel a pending friend request that I sent
	 */
	public function cancelar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id   = get_current_user_id();
		$friend_id = (int) $request->get_param( 'user_id' );

		if ( ! $friend_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do usuário é obrigatório.',
				),
				400
			);
		}

		// Check if I have a pending request to this user
		$status = $this->getRelationshipStatus( $user_id, $friend_id );

		if ( $status !== 'pending_sent' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Não há solicitação pendente para este usuário.',
				),
				400
			);
		}

		// Cancel the request
		$result = $this->cancelRequest( $user_id, $friend_id );

		if ( ! $result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao cancelar solicitação.',
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Solicitação cancelada.',
				'data'    => array( 'status' => 'none' ),
			),
			200
		);
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Get relationship status between two users
	 *
	 * @return string 'none', 'friends', 'pending_sent', 'pending_received', 'blocked'
	 */
	private function getRelationshipStatus( int $user_id, int $other_id ): string {
		global $wpdb;

		$this->ensureTableExists();
		$table = $this->getTableName();

		// Check for friendship (both directions)
		$friendship = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE ((user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d))
				LIMIT 1",
				$user_id,
				$other_id,
				$other_id,
				$user_id
			)
		);

		if ( ! $friendship ) {
			return 'none';
		}

		if ( $friendship->status === 'accepted' ) {
			return 'friends';
		}

		if ( $friendship->status === 'blocked' ) {
			return 'blocked';
		}

		if ( $friendship->status === 'pending' ) {
			// Check who sent the request
			if ( (int) $friendship->user_id === $user_id ) {
				return 'pending_sent';
			} else {
				return 'pending_received';
			}
		}

		return 'none';
	}

	/**
	 * Create a friend request
	 */
	private function createRequest( int $user_id, int $friend_id ): bool {
		global $wpdb;

		$this->ensureTableExists();
		$table = $this->getTableName();

		return (bool) $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'friend_id'  => $friend_id,
				'status'     => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Accept a friend request
	 */
	private function acceptRequest( int $requester_id, int $accepter_id ): bool {
		global $wpdb;

		$table = $this->getTableName();

		return (bool) $wpdb->update(
			$table,
			array(
				'status'      => 'accepted',
				'accepted_at' => current_time( 'mysql' ),
			),
			array(
				'user_id'   => $requester_id,
				'friend_id' => $accepter_id,
				'status'    => 'pending',
			),
			array( '%s', '%s' ),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Reject a friend request
	 */
	private function rejectRequest( int $requester_id, int $rejecter_id ): bool {
		global $wpdb;

		$table = $this->getTableName();

		return (bool) $wpdb->delete(
			$table,
			array(
				'user_id'   => $requester_id,
				'friend_id' => $rejecter_id,
				'status'    => 'pending',
			),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Cancel a pending request I sent
	 */
	private function cancelRequest( int $user_id, int $friend_id ): bool {
		global $wpdb;

		$table = $this->getTableName();

		return (bool) $wpdb->delete(
			$table,
			array(
				'user_id'   => $user_id,
				'friend_id' => $friend_id,
				'status'    => 'pending',
			),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Remove friendship (unfriend)
	 */
	private function removeFriendship( int $user_id, int $friend_id ): bool {
		global $wpdb;

		$table = $this->getTableName();

		// Delete both directions
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table}
				WHERE ((user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d))
				AND status = 'accepted'",
				$user_id,
				$friend_id,
				$friend_id,
				$user_id
			)
		);

		return true;
	}

	/**
	 * Get list of friends
	 */
	private function getFriends( int $user_id, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$table = $this->getTableName();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					CASE
						WHEN user_id = %d THEN friend_id
						ELSE user_id
					END as friend_id,
					accepted_at
				FROM {$table}
				WHERE (user_id = %d OR friend_id = %d)
				AND status = 'accepted'
				ORDER BY accepted_at DESC
				LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$user_id,
				$limit,
				$offset
			)
		);

		return $results ?: array();
	}

	/**
	 * Get total friends count
	 */
	private function getFriendsCount( int $user_id ): int {
		global $wpdb;

		$table = $this->getTableName();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table}
				WHERE (user_id = %d OR friend_id = %d)
				AND status = 'accepted'",
				$user_id,
				$user_id
			)
		);
	}

	/**
	 * Get pending friend requests
	 */
	private function getPendingRequests( int $user_id, string $type = 'received' ): array {
		global $wpdb;

		$table = $this->getTableName();

		if ( $type === 'received' ) {
			// Requests sent TO me
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table}
					WHERE friend_id = %d AND status = 'pending'
					ORDER BY created_at DESC",
					$user_id
				)
			);
		} else {
			// Requests I sent
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table}
					WHERE user_id = %d AND status = 'pending'
					ORDER BY created_at DESC",
					$user_id
				)
			);
		}

		return $results ?: array();
	}

	/**
	 * Send notification when friend request is sent
	 */
	private function sendRequestNotification( int $sender_id, int $recipient_id ): void {
		$sender = get_userdata( $sender_id );
		if ( ! $sender ) {
			return;
		}

		// Use Apollo notifications if available
		if ( function_exists( '\apollo_send_notification' ) ) {
			\apollo_send_notification(
				$recipient_id,
				'friend_request',
				sprintf( '%s quer te adicionar à bolha!', $sender->display_name ),
				array(
					'sender_id' => $sender_id,
					'type'      => 'friend_request',
				)
			);
		}
	}

	/**
	 * Send notification when friend request is accepted
	 */
	private function sendAcceptNotification( int $accepter_id, int $requester_id ): void {
		$accepter = get_userdata( $accepter_id );
		if ( ! $accepter ) {
			return;
		}

		// Use Apollo notifications if available
		if ( function_exists( '\apollo_send_notification' ) ) {
			\apollo_send_notification(
				$requester_id,
				'friend_accepted',
				sprintf( '%s aceitou seu pedido de amizade!', $accepter->display_name ),
				array(
					'accepter_id' => $accepter_id,
					'type'        => 'friend_accepted',
				)
			);
		}
	}

	/**
	 * Ensure the bolha table exists
	 */
	private function ensureTableExists(): void {
		global $wpdb;

		$table           = $this->getTableName();
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			return;
		}

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Who sent the request',
			friend_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Who receives the request',
			status ENUM('pending', 'accepted', 'rejected', 'blocked') DEFAULT 'pending',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			accepted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_friendship (user_id, friend_id),
			KEY idx_user (user_id),
			KEY idx_friend (friend_id),
			KEY idx_status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
