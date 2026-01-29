<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class MembersDirectoryRepository {

	public static function search( array $args = array() ): array {
		global $wpdb;
		$defaults = array(
			'search'           => '',
			'role'             => '',
			'tag'              => '',
			'online_only'      => false,
			'verified_only'    => false,
			'orderby'          => 'display_name',
			'order'            => 'ASC',
			'limit'            => 20,
			'offset'           => 0,
			'exclude_spammers' => true,
		);
		$a        = array_merge( $defaults, $args );
		$joins    = array();
		$where    = array( '1=1' );
		$params   = array();
		if ( $a['search'] ) {
			$where[] = '(u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $a['search'] ) . '%';
			$params  = array_merge( $params, array( $like, $like, $like ) );
		}
		if ( $a['role'] ) {
			$joins[]  = "INNER JOIN {$wpdb->usermeta} umr ON umr.user_id=u.ID AND umr.meta_key='{$wpdb->prefix}capabilities'";
			$where[]  = 'umr.meta_value LIKE %s';
			$params[] = '%"' . $a['role'] . '"%';
		}
		if ( $a['tag'] ) {
			$joins[]  = "INNER JOIN {$wpdb->prefix}apollo_user_tag_relations utr ON utr.user_id=u.ID";
			$where[]  = 'utr.tag_id=%d';
			$params[] = (int) $a['tag'];
		}
		if ( $a['online_only'] ) {
			$joins[] = "INNER JOIN {$wpdb->prefix}apollo_online_users ou ON ou.user_id=u.ID";
			$where[] = 'ou.last_activity>DATE_SUB(NOW(),INTERVAL 5 MINUTE)';
		}
		if ( $a['verified_only'] ) {
			$joins[] = "LEFT JOIN {$wpdb->usermeta} umv ON umv.user_id=u.ID AND umv.meta_key='apollo_verified'";
			$where[] = "umv.meta_value='1'";
		}
		if ( $a['exclude_spammers'] ) {
			$joins[] = "LEFT JOIN {$wpdb->prefix}apollo_spammer_list sl ON sl.user_id=u.ID";
			$where[] = 'sl.user_id IS NULL';
		}
		$j        = \implode( ' ', $joins );
		$w        = \implode( ' AND ', $where );
		$ob       = in_array( $a['orderby'], array( 'display_name', 'user_registered', 'ID' ) ) ? $a['orderby'] : 'display_name';
		$o        = \strtoupper( $a['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$params[] = $a['limit'];
		$params[] = $a['offset'];
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT u.ID,u.display_name,u.user_login,u.user_email,u.user_registered FROM {$wpdb->users} u {$j} WHERE {$w} ORDER BY u.{$ob} {$o} LIMIT %d OFFSET %d",
				...$params
			),
			ARRAY_A
		) ?: array();
	}

	public static function count( array $args = array() ): int {
		global $wpdb;
		$defaults = array(
			'search'           => '',
			'role'             => '',
			'tag'              => '',
			'online_only'      => false,
			'verified_only'    => false,
			'exclude_spammers' => true,
		);
		$a        = array_merge( $defaults, $args );
		$joins    = array();
		$where    = array( '1=1' );
		$params   = array();
		if ( $a['search'] ) {
			$where[] = '(u.display_name LIKE %s OR u.user_login LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $a['search'] ) . '%';
			$params  = array_merge( $params, array( $like, $like ) );
		}
		if ( $a['role'] ) {
			$joins[]  = "INNER JOIN {$wpdb->usermeta} umr ON umr.user_id=u.ID AND umr.meta_key='{$wpdb->prefix}capabilities'";
			$where[]  = 'umr.meta_value LIKE %s';
			$params[] = '%"' . $a['role'] . '"%';
		}
		if ( $a['tag'] ) {
			$joins[]  = "INNER JOIN {$wpdb->prefix}apollo_user_tag_relations utr ON utr.user_id=u.ID";
			$where[]  = 'utr.tag_id=%d';
			$params[] = (int) $a['tag'];
		}
		if ( $a['online_only'] ) {
			$joins[] = "INNER JOIN {$wpdb->prefix}apollo_online_users ou ON ou.user_id=u.ID";
			$where[] = 'ou.last_activity>DATE_SUB(NOW(),INTERVAL 5 MINUTE)';
		}
		if ( $a['verified_only'] ) {
			$joins[] = "LEFT JOIN {$wpdb->usermeta} umv ON umv.user_id=u.ID AND umv.meta_key='apollo_verified'";
			$where[] = "umv.meta_value='1'";
		}
		if ( $a['exclude_spammers'] ) {
			$joins[] = "LEFT JOIN {$wpdb->prefix}apollo_spammer_list sl ON sl.user_id=u.ID";
			$where[] = 'sl.user_id IS NULL';
		}
		$j = implode( ' ', $joins );
		$w = implode( ' AND ', $where );
		if ( empty( $params ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u {$j} WHERE {$w}" );
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u {$j} WHERE {$w}", ...$params ) );
	}

	public static function getWithProfiles( array $userIds ): array {
		if ( empty( $userIds ) ) {
			return array();
		}
		global $wpdb;
		$in    = \implode( ',', array_map( 'intval', $userIds ) );
		$users = $wpdb->get_results( "SELECT * FROM {$wpdb->users} WHERE ID IN ({$in})", ARRAY_A ) ?: array();
		foreach ( $users as &$u ) {
			$u['avatar_url']   = get_avatar_url( $u['ID'], array( 'size' => 150 ) );
			$u['is_online']    = OnlineUsersRepository::isOnline( (int) $u['ID'] );
			$u['is_verified']  = (bool) get_user_meta( $u['ID'], 'apollo_verified', true );
			$u['tags']         = \Apollo\Modules\Tags\TagsRepository::getUserTags( (int) $u['ID'] );
			$u['friend_count'] = \Apollo\Modules\Connections\ConnectionsRepository::countFriends( (int) $u['ID'] );
		}
		return $users;
	}
}
