<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class RecentlyActiveRepository {

	public static function get( int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$t         = $wpdb->prefix . 'apollo_online_users';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name,u.user_login FROM {$t} o JOIN {$wpdb->users} u ON o.user_id=u.ID WHERE o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d OFFSET %d",
				$threshold,
				$limit,
				$offset
			),
			ARRAY_A
		) ?? array();
	}

	public static function getToday( int $limit = 50 ): array {
		global $wpdb;
		$t     = $wpdb->prefix . 'apollo_online_users';
		$today = current_time( 'Y-m-d' ) . ' 00:00:00';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name FROM {$t} o JOIN {$wpdb->users} u ON o.user_id=u.ID WHERE o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d",
				$today,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getThisWeek( int $limit = 100 ): array {
		global $wpdb;
		$t       = $wpdb->prefix . 'apollo_online_users';
		$weekAgo = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name FROM {$t} o JOIN {$wpdb->users} u ON o.user_id=u.ID WHERE o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d",
				$weekAgo,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function countActive( int $hours = 24 ): int {
		global $wpdb;
		$t         = $wpdb->prefix . 'apollo_online_users';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$hours} hours" ) );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE last_activity>=%s", $threshold ) );
	}

	public static function getWithActivity( int $limit = 10 ): array {
		global $wpdb;
		$o         = $wpdb->prefix . 'apollo_online_users';
		$a         = $wpdb->prefix . 'apollo_activity';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT o.user_id,o.last_activity,u.display_name,(SELECT content FROM {$a} WHERE user_id=o.user_id ORDER BY created_at DESC LIMIT 1) as last_activity_content FROM {$o} o JOIN {$wpdb->users} u ON o.user_id=u.ID WHERE o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d",
				$threshold,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getByLocation( string $city, int $limit = 20 ): array {
		global $wpdb;
		$o         = $wpdb->prefix . 'apollo_online_users';
		$pv        = $wpdb->prefix . 'apollo_profile_field_values';
		$pf        = $wpdb->prefix . 'apollo_profile_fields';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name FROM {$o} o JOIN {$wpdb->users} u ON o.user_id=u.ID JOIN {$pv} v ON o.user_id=v.user_id JOIN {$pf} f ON v.field_id=f.id WHERE o.last_activity>=%s AND f.slug='city' AND v.value=%s ORDER BY o.last_activity DESC LIMIT %d",
				$threshold,
				$city,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getFriendsActive( int $userId, int $limit = 10 ): array {
		global $wpdb;
		$o         = $wpdb->prefix . 'apollo_online_users';
		$c         = $wpdb->prefix . 'apollo_connections';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name FROM {$o} o JOIN {$wpdb->users} u ON o.user_id=u.ID JOIN {$c} c ON o.user_id=c.friend_id WHERE c.user_id=%d AND c.status='accepted' AND o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d",
				$userId,
				$threshold,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getGroupMembersActive( int $groupId, int $limit = 20 ): array {
		global $wpdb;
		$o         = $wpdb->prefix . 'apollo_online_users';
		$gm        = $wpdb->prefix . 'apollo_group_members';
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name,gm.role FROM {$o} o JOIN {$wpdb->users} u ON o.user_id=u.ID JOIN {$gm} gm ON o.user_id=gm.user_id WHERE gm.group_id=%d AND o.last_activity>=%s ORDER BY o.last_activity DESC LIMIT %d",
				$groupId,
				$threshold,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getNewAndActive( int $days = 7, int $limit = 10 ): array {
		global $wpdb;
		$o          = $wpdb->prefix . 'apollo_online_users';
		$threshold  = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		$registered = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.user_id,o.last_activity,u.display_name,u.user_registered FROM {$o} o JOIN {$wpdb->users} u ON o.user_id=u.ID WHERE o.last_activity>=%s AND u.user_registered>=%s ORDER BY u.user_registered DESC LIMIT %d",
				$threshold,
				$registered,
				$limit
			),
			ARRAY_A
		) ?? array();
	}

	public static function getActivityHeatmap( int $days = 7 ): array {
		global $wpdb;
		$t     = $wpdb->prefix . 'apollo_online_users';
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT HOUR(last_activity) as hour,DAYOFWEEK(last_activity) as day,COUNT(*) as count FROM {$t} WHERE last_activity>=%s GROUP BY HOUR(last_activity),DAYOFWEEK(last_activity)",
				$since
			),
			ARRAY_A
		) ?? array();
	}

	public static function getPeakHours( int $days = 30 ): array {
		global $wpdb;
		$t     = $wpdb->prefix . 'apollo_online_users';
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT HOUR(last_activity) as hour,COUNT(DISTINCT user_id) as unique_users FROM {$t} WHERE last_activity>=%s GROUP BY HOUR(last_activity) ORDER BY unique_users DESC",
				$since
			),
			ARRAY_A
		) ?? array();
	}

	public static function getRetentionStats(): array {
		global $wpdb;
		$t = $wpdb->prefix . 'apollo_online_users';
		return array(
			'daily'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$t} WHERE last_activity>=%s", gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) ) ) ),
			'weekly'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$t} WHERE last_activity>=%s", gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ) ) ),
			'monthly'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$t} WHERE last_activity>=%s", gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ) ) ),
			'total_users' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ),
		);
	}
}
