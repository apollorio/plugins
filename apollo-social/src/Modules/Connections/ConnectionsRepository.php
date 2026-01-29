<?php
/**
 * Apollo Connections Repository
 *
 * APOLLO ARCHITECTURE:
 * - ALL users are automatically connected/friends (no friend requests)
 * - Users can BLOCK others (hide content, prevent interaction)
 * - Users can add up to 10 Close Friends (Bolha) for prioritized content
 *
 * @package Apollo\Modules\Connections
 */
declare(strict_types=1);
namespace Apollo\Modules\Connections;

final class ConnectionsRepository {
	/**
	 * Table for blocks only (no friend requests - everyone is connected).
	 */
	private const TABLE_BLOCKS      = 'apollo_user_blocks';
	private const CLOSE             = 'apollo_close_friends';
	private const MAX_CLOSE_FRIENDS = 10;

	/**
	 * Legacy table name for backwards compatibility.
	 * @deprecated Use TABLE_BLOCKS instead.
	 */
	private const TABLE = 'apollo_connections';

	// =========================================================================
	// BLOCK SYSTEM (Users can block others)
	// =========================================================================

	/**
	 * Block a user.
	 *
	 * @param int $userId    The user doing the blocking.
	 * @param int $blockedId The user being blocked.
	 * @return bool Success.
	 */
	public static function block( int $userId, int $blockedId ): bool {
		if ( $userId === $blockedId ) {
			return false;
		}

		// Remove from close friends if blocked.
		self::removeCloseFriend( $userId, $blockedId );

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		// Check if already blocked.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND friend_id = %d AND status = 'blocked'",
				$userId,
				$blockedId
			)
		);

		if ( $existing ) {
			return true; // Already blocked.
		}

		// Remove any existing relationship.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
				$userId,
				$blockedId,
				$blockedId,
				$userId
			)
		);

		// Insert block.
		$result = $wpdb->insert(
			$table,
			array(
				'user_id'      => $userId,
				'friend_id'    => $blockedId,
				'status'       => 'blocked',
				'initiated_by' => $userId,
			)
		);

		if ( $result ) {
			do_action( 'apollo_user_blocked', $userId, $blockedId );
		}

		return $result !== false;
	}

	/**
	 * Unblock a user.
	 *
	 * @param int $userId    The user doing the unblocking.
	 * @param int $blockedId The user being unblocked.
	 * @return bool Success.
	 */
	public static function unblock( int $userId, int $blockedId ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'   => $userId,
				'friend_id' => $blockedId,
				'status'    => 'blocked',
			)
		);

		if ( $result ) {
			do_action( 'apollo_user_unblocked', $userId, $blockedId );
		}

		return $result !== false;
	}

	/**
	 * Check if a user has blocked another user.
	 *
	 * @param int $userId    The user who may have blocked.
	 * @param int $blockedId The user who may be blocked.
	 * @return bool True if blocked.
	 */
	public static function isBlocked( int $userId, int $blockedId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::TABLE . " WHERE user_id = %d AND friend_id = %d AND status = 'blocked'",
				$userId,
				$blockedId
			)
		);
	}

	/**
	 * Check if either user has blocked the other.
	 *
	 * @param int $userId1 First user.
	 * @param int $userId2 Second user.
	 * @return bool True if there's a block in either direction.
	 */
	public static function hasBlockBetween( int $userId1, int $userId2 ): bool {
		return self::isBlocked( $userId1, $userId2 ) || self::isBlocked( $userId2, $userId1 );
	}

	/**
	 * Get list of users blocked by a user.
	 *
	 * @param int $userId The user.
	 * @return array List of blocked user IDs.
	 */
	public static function getBlockedUsers( int $userId ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT friend_id FROM {$wpdb->prefix}" . self::TABLE . " WHERE user_id = %d AND status = 'blocked'",
				$userId
			)
		) ?: array();
	}

	/**
	 * Get list of users who blocked this user.
	 *
	 * @param int $userId The user.
	 * @return array List of user IDs who blocked this user.
	 */
	public static function getBlockedByUsers( int $userId ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}" . self::TABLE . " WHERE friend_id = %d AND status = 'blocked'",
				$userId
			)
		) ?: array();
	}

	// =========================================================================
	// CLOSE FRIENDS / BOLHA SYSTEM (Max 10 users)
	// =========================================================================

	/**
	 * Add a user to close friends (Bolha).
	 *
	 * @param int $userId   The user adding a close friend.
	 * @param int $friendId The friend to add.
	 * @return bool Success.
	 */
	public static function addCloseFriend( int $userId, int $friendId ): bool {
		if ( $userId === $friendId ) {
			return false;
		}

		// Cannot add blocked users.
		if ( self::hasBlockBetween( $userId, $friendId ) ) {
			return false;
		}

		global $wpdb;
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::CLOSE . " WHERE user_id = %d",
				$userId
			)
		);

		if ( $count >= self::MAX_CLOSE_FRIENDS ) {
			return false;
		}

		$result = $wpdb->replace(
			$wpdb->prefix . self::CLOSE,
			array(
				'user_id'   => $userId,
				'friend_id' => $friendId,
			)
		);

		if ( $result ) {
			do_action( 'apollo_close_friend_added', $userId, $friendId );
		}

		return $result !== false;
	}

	/**
	 * Remove a user from close friends (Bolha).
	 *
	 * @param int $userId   The user removing a close friend.
	 * @param int $friendId The friend to remove.
	 * @return bool Success.
	 */
	public static function removeCloseFriend( int $userId, int $friendId ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . self::CLOSE,
			array(
				'user_id'   => $userId,
				'friend_id' => $friendId,
			)
		);

		if ( $result ) {
			do_action( 'apollo_close_friend_removed', $userId, $friendId );
		}

		return $result !== false;
	}

	/**
	 * Get close friends (Bolha) for a user.
	 *
	 * @param int $userId The user.
	 * @return array List of close friend IDs.
	 */
	public static function getCloseFriends( int $userId ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT friend_id FROM {$wpdb->prefix}" . self::CLOSE . " WHERE user_id = %d",
				$userId
			)
		) ?: array();
	}

	/**
	 * Check if a user is a close friend.
	 *
	 * @param int $userId   The user.
	 * @param int $friendId The potential close friend.
	 * @return bool True if close friend.
	 */
	public static function isCloseFriend( int $userId, int $friendId ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}" . self::CLOSE . " WHERE user_id = %d AND friend_id = %d",
				$userId,
				$friendId
			)
		);
	}

	/**
	 * Get the Bolha (alias for getCloseFriends).
	 *
	 * @param int $userId The user.
	 * @return array List of close friend IDs.
	 */
	public static function getBubble( int $userId ): array {
		return self::getCloseFriends( $userId );
	}

	/**
	 * Get close friends count.
	 *
	 * @param int $userId The user.
	 * @return int Count.
	 */
	public static function getCloseFriendsCount( int $userId ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}" . self::CLOSE . " WHERE user_id = %d",
				$userId
			)
		);
	}

	/**
	 * Get max close friends limit.
	 *
	 * @return int Max close friends.
	 */
	public static function getMaxCloseFriends(): int {
		return self::MAX_CLOSE_FRIENDS;
	}

	/**
	 * Check if user can add more close friends.
	 *
	 * @param int $userId The user.
	 * @return bool True if can add more.
	 */
	public static function canAddCloseFriend( int $userId ): bool {
		return self::getCloseFriendsCount( $userId ) < self::MAX_CLOSE_FRIENDS;
	}

	// =========================================================================
	// APOLLO ARCHITECTURE: ALL USERS ARE CONNECTED
	// =========================================================================

	/**
	 * Check if two users are "friends" (always true in Apollo, unless blocked).
	 *
	 * In Apollo, all users are automatically connected. This method returns
	 * false only if there's a block between users.
	 *
	 * @param int $userId   First user.
	 * @param int $friendId Second user.
	 * @return bool True if not blocked.
	 */
	public static function areFriends( int $userId, int $friendId ): bool {
		// In Apollo, everyone is a friend unless blocked.
		return ! self::hasBlockBetween( $userId, $friendId );
	}

	/**
	 * Check if user can interact with another user.
	 *
	 * @param int $userId   The user trying to interact.
	 * @param int $targetId The target user.
	 * @return bool True if can interact.
	 */
	public static function canInteract( int $userId, int $targetId ): bool {
		return ! self::hasBlockBetween( $userId, $targetId );
	}

	// =========================================================================
	// DEPRECATED METHODS (kept for backwards compatibility)
	// =========================================================================

	/**
	 * @deprecated Not used in Apollo architecture. All users are connected.
	 */
	public static function sendRequest( int $userId, int $friendId ): bool {
		_doing_it_wrong(
			__METHOD__,
			'Apollo does not use friend requests. All users are automatically connected.',
			'2.0.0'
		);
		return true; // Pretend success for backwards compat.
	}

	/**
	 * @deprecated Not used in Apollo architecture. All users are connected.
	 */
	public static function acceptRequest( int $userId, int $friendId ): bool {
		_doing_it_wrong(
			__METHOD__,
			'Apollo does not use friend requests. All users are automatically connected.',
			'2.0.0'
		);
		return true;
	}

	/**
	 * @deprecated Not used in Apollo architecture. All users are connected.
	 */
	public static function rejectRequest( int $userId, int $friendId ): bool {
		_doing_it_wrong(
			__METHOD__,
			'Apollo does not use friend requests. All users are automatically connected.',
			'2.0.0'
		);
		return true;
	}

	/**
	 * @deprecated Use block() instead. Users cannot unfriend in Apollo.
	 */
	public static function unfriend( int $userId, int $friendId ): bool {
		_doing_it_wrong(
			__METHOD__,
			'Apollo does not support unfriending. Use block() instead.',
			'2.0.0'
		);
		return false;
	}

	/**
	 * @deprecated Not used in Apollo architecture.
	 */
	public static function getPendingRequests( int $userId ): array {
		return array(); // No pending requests in Apollo.
	}

	/**
	 * @deprecated Not used in Apollo architecture.
	 */
	public static function getSentRequests( int $userId ): array {
		return array(); // No sent requests in Apollo.
	}

	/**
	 * @deprecated Not meaningful in Apollo. All users are connected.
	 */
	public static function getFriends( int $userId, int $limit = 50, int $offset = 0 ): array {
		_doing_it_wrong(
			__METHOD__,
			'Apollo does not maintain a friends list. All users are connected. Use getCloseFriends() for Bolha.',
			'2.0.0'
		);
		return array();
	}

	/**
	 * @deprecated Not meaningful in Apollo. Use getCloseFriendsCount() instead.
	 */
	public static function countFriends( int $userId ): int {
		return self::getCloseFriendsCount( $userId );
	}

	/**
	 * @deprecated Legacy status method. Use isBlocked() or isCloseFriend() instead.
	 */
	public static function getStatus( int $userId, int $friendId ): ?string {
		if ( self::isBlocked( $userId, $friendId ) ) {
			return 'blocked';
		}
		if ( self::isCloseFriend( $userId, $friendId ) ) {
			return 'close_friend';
		}
		return 'connected'; // Everyone is connected in Apollo.
	}

	/**
	 * Remove all connections for a user (used when deleting account).
	 *
	 * @param int $userId The user.
	 * @return bool Success.
	 */
	public static function removeAllConnections( int $userId ): bool {
		global $wpdb;

		// Remove close friends.
		$wpdb->delete( $wpdb->prefix . self::CLOSE, array( 'user_id' => $userId ) );
		$wpdb->delete( $wpdb->prefix . self::CLOSE, array( 'friend_id' => $userId ) );

		// Remove blocks.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}" . self::TABLE . " WHERE user_id = %d OR friend_id = %d",
				$userId,
				$userId
			)
		);

		return true;
	}
}
