<?php
declare(strict_types=1);
namespace Apollo\Modules\Widgets;

final class WidgetsRepository {

	public static function getWhosOnline( int $limit = 20 ): array {
		return\Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers( $limit );
	}

	public static function getWhosOnlineCount(): int {
		return\Apollo\Modules\Members\OnlineUsersRepository::countOnline();
	}

	public static function getRecentlyActive( int $minutes = 60, int $limit = 20 ): array {
		return\Apollo\Modules\Members\OnlineUsersRepository::getRecentlyActive( $minutes, $limit );
	}

	public static function getOnlineFriends( int $userId, int $limit = 10 ): array {
		return\Apollo\Modules\Members\OnlineUsersRepository::getOnlineFriends( $userId, $limit );
	}

	public static function getLeaderboard( string $type = 'points', int $limit = 10 ): array {
		if ( $type === 'points' ) {
			return\Apollo\Modules\Gamification\PointsRepository::getLeaderboard( 'default', $limit );
		}
		return array();
	}

	public static function getNewestMembers( int $limit = 10 ): array {
		global $wpdb;
		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID,display_name,user_email,user_registered FROM {$wpdb->users} ORDER BY user_registered DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: array();
		foreach ( $users as &$u ) {
			$u['avatar_url']  = get_avatar_url( $u['ID'], array( 'size' => 50 ) );
			$u['is_verified'] = \Apollo\Modules\Members\VerifiedUsersRepository::isVerified( (int) $u['ID'] );
		}
		return $users;
	}

	public static function getActiveNotices( int $userId ): array {
		return\Apollo\Modules\Notices\NoticesRepository::getActiveForUser( $userId );
	}

	public static function getUnreadMentionsCount( int $userId ): int {
		return\Apollo\Modules\Activity\ActivityRepository::countUnreadMentions( $userId );
	}

	public static function getPendingFriendRequests( int $userId ): array {
		return\Apollo\Modules\Connections\ConnectionsRepository::getPendingRequests( $userId );
	}

	public static function getPendingFriendRequestsCount( int $userId ): int {
		return count( self::getPendingFriendRequests( $userId ) );
	}

	public static function getUserStats( int $userId ): array {
		return array(
			'friends'      => \Apollo\Modules\Connections\ConnectionsRepository::countFriends( $userId ),
			'groups'       => count( \Apollo\Modules\Groups\GroupsRepository::getUserGroups( $userId ) ),
			'points'       => \Apollo\Modules\Gamification\PointsRepository::getBalance( $userId, 'default' ),
			'achievements' => count( \Apollo\Modules\Gamification\AchievementsRepository::getUserAchievements( $userId ) ),
			'rank'         => \Apollo\Modules\Gamification\RanksRepository::getUserRank( $userId ),
			'is_online'    => \Apollo\Modules\Members\OnlineUsersRepository::isOnline( $userId ),
			'is_verified'  => \Apollo\Modules\Members\VerifiedUsersRepository::isVerified( $userId ),
		);
	}

	public static function getDynamicMenuItems( int $userId ): array {
		$items          = array();
		$pendingFriends = self::getPendingFriendRequestsCount( $userId );
		if ( $pendingFriends > 0 ) {
			$items[] = array(
				'label' => 'Friend Requests',
				'url'   => '/members/requests/',
				'count' => $pendingFriends,
				'icon'  => 'users',
			);
		}
		$unreadMentions = self::getUnreadMentionsCount( $userId );
		if ( $unreadMentions > 0 ) {
			$items[] = array(
				'label' => 'Mentions',
				'url'   => '/activity/mentions/',
				'count' => $unreadMentions,
				'icon'  => 'at',
			);
		}
		return $items;
	}
}
