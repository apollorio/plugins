<?php
declare(strict_types=1);
namespace Apollo\Modules\MyData;

final class MyDataRepository {

	public static function getMyActivity( int $userId, int $limit = 20, int $offset = 0 ): array {
		return\Apollo\Modules\Activity\ActivityRepository::getFeed(
			array(
				'user_id' => $userId,
				'limit'   => $limit,
				'offset'  => $offset,
				'privacy' => null,
			)
		);
	}

	public static function getMyFriends( int $userId, int $limit = 50, int $offset = 0 ): array {
		$friends = \Apollo\Modules\Connections\ConnectionsRepository::getFriends( $userId, $limit, $offset );
		foreach ( $friends as &$f ) {
			$f['is_online']  = \Apollo\Modules\Members\OnlineUsersRepository::isOnline( (int) $f['ID'] );
			$f['avatar_url'] = get_avatar_url( $f['ID'], array( 'size' => 100 ) );
		}
		return $friends;
	}

	public static function getMyCloseFriends( int $userId ): array {
		$friends = \Apollo\Modules\Connections\ConnectionsRepository::getCloseFriends( $userId );
		foreach ( $friends as &$f ) {
			$f['is_online']  = \Apollo\Modules\Members\OnlineUsersRepository::isOnline( (int) $f['ID'] );
			$f['avatar_url'] = get_avatar_url( $f['ID'], array( 'size' => 100 ) );
		}
		return $friends;
	}

	public static function getMyGroups( int $userId ): array {
		return\Apollo\Modules\Groups\GroupsRepository::getUserGroups( $userId );
	}

	public static function getMyAchievements( int $userId ): array {
		return\Apollo\Modules\Gamification\AchievementsRepository::getUserAchievements( $userId );
	}

	public static function getMyPoints( int $userId ): array {
		return array(
			'balance'    => \Apollo\Modules\Gamification\PointsRepository::getBalance( $userId, 'default' ),
			'rank'       => \Apollo\Modules\Gamification\RanksRepository::getUserRank( $userId ),
			'progress'   => \Apollo\Modules\Gamification\RanksRepository::getProgressToNextRank( $userId ),
			'recent_log' => \Apollo\Modules\Gamification\PointsRepository::getLog( $userId, 'default', 10 ),
		);
	}

	public static function getMyMentions( int $userId, bool $unreadOnly = false, int $limit = 20 ): array {
		return\Apollo\Modules\Activity\ActivityRepository::getMentions( $userId, $unreadOnly, $limit );
	}

	public static function getMyFavorites( int $userId, ?string $type = null, int $limit = 50 ): array {
		return\Apollo\Modules\Activity\ActivityRepository::getFavorites( $userId, $type, $limit );
	}

	public static function getMyAdverts( int $userId, int $limit = 20, int $offset = 0 ): array {
		$args  = array(
			'author'         => $userId,
			'post_type'      => 'advert',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'post_status'    => array( 'publish', 'pending', 'draft' ),
		);
		$query = new\WP_Query( $args );
		return $query->posts ?: array();
	}

	public static function getMyEvents( int $userId, int $limit = 20, int $offset = 0 ): array {
		$args  = array(
			'author'         => $userId,
			'post_type'      => 'event_listing',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'post_status'    => array( 'publish', 'pending', 'draft' ),
		);
		$query = new\WP_Query( $args );
		return $query->posts ?: array();
	}

	public static function getMyInterestedEvents( int $userId, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$eventIds = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key='apollo_interested_events'",
				$userId
			)
		);
		if ( empty( $eventIds ) ) {
			return array();
		}
		$ids = maybe_unserialize( $eventIds[0] ?? '' );
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return array();
		}
		$args  = array(
			'post_type'      => 'event_listing',
			'post__in'       => $ids,
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'post_status'    => 'publish',
		);
		$query = new\WP_Query( $args );
		return $query->posts ?: array();
	}

	public static function markEventInterested( int $userId, int $eventId ): bool {
		$interested = (array) get_user_meta( $userId, 'apollo_interested_events', true );
		if ( ! in_array( $eventId, $interested ) ) {
			$interested[] = $eventId;
			return update_user_meta( $userId, 'apollo_interested_events', array_values( array_unique( $interested ) ) ) !== false;
		}
		return true;
	}

	public static function unmarkEventInterested( int $userId, int $eventId ): bool {
		$interested = (array) get_user_meta( $userId, 'apollo_interested_events', true );
		$interested = array_diff( $interested, array( $eventId ) );
		return update_user_meta( $userId, 'apollo_interested_events', array_values( $interested ) ) !== false;
	}

	public static function getMyNotifications( int $userId, int $limit = 20 ): array {
		$notifications  = array();
		$pendingFriends = \Apollo\Modules\Connections\ConnectionsRepository::getPendingRequests( $userId );
		foreach ( $pendingFriends as $pr ) {
			$notifications[] = array(
				'type'       => 'friend_request',
				'data'       => $pr,
				'created_at' => $pr['created_at'] ?? current_time( 'mysql' ),
			);
		}
		$mentions = self::getMyMentions( $userId, true, 10 );
		foreach ( $mentions as $m ) {
			$notifications[] = array(
				'type'       => 'mention',
				'data'       => $m,
				'created_at' => $m['created_at'],
			);
		}
		usort( $notifications, fn( $a, $b )=>strtotime( $b['created_at'] ) - strtotime( $a['created_at'] ) );
		return array_slice( $notifications, 0, $limit );
	}

	public static function getMyStats( int $userId ): array {
		return array(
			'friends_count'        => \Apollo\Modules\Connections\ConnectionsRepository::countFriends( $userId ),
			'groups_count'         => count( self::getMyGroups( $userId ) ),
			'achievements_count'   => count( self::getMyAchievements( $userId ) ),
			'points'               => \Apollo\Modules\Gamification\PointsRepository::getBalance( $userId, 'default' ),
			'rank'                 => \Apollo\Modules\Gamification\RanksRepository::getUserRank( $userId ),
			'adverts_count'        => count( self::getMyAdverts( $userId, 100 ) ),
			'events_count'         => count( self::getMyEvents( $userId, 100 ) ),
			'profile_completeness' => \Apollo\Modules\Profiles\ProfileFieldsRepository::calculateCompleteness( $userId ),
		);
	}
}
