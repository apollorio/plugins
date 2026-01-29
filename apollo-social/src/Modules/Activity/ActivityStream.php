<?php
declare(strict_types=1);
namespace Apollo\Modules\Activity;

defined( 'ABSPATH' ) || exit;
final class ActivityStream {
	public const TYPE_POST           = 'post';
	public const TYPE_COMMENT        = 'comment';
	public const TYPE_LIKE           = 'like';
	public const TYPE_FOLLOW         = 'follow';
	public const TYPE_GROUP_JOIN     = 'group_join';
	public const TYPE_GROUP_CREATE   = 'group_create';
	public const TYPE_EVENT_CREATE   = 'event_create';
	public const TYPE_EVENT_INTEREST = 'event_interest';
	public const TYPE_CLASSIFIED     = 'classified';
	public const TYPE_BUBBLE         = 'bubble';
	public const TYPE_ACHIEVEMENT    = 'achievement';
	public const TYPE_RANK           = 'rank_up';
	public const TYPE_DOCUMENT       = 'document';
	public const TYPE_MENTION        = 'mention';
	public const TYPE_SHARE          = 'share';
	private static ?self $instance   = null;
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function init(): void {
		add_action( 'apollo_post_created', fn( $post_id, $user_id ) => $this->log( self::TYPE_POST, $user_id, $post_id ), 10, 2 );
		add_action( 'apollo_post_liked', fn( $post_id, $user_id ) => $this->log( self::TYPE_LIKE, $user_id, $post_id ), 10, 2 );
		add_action( 'apollo_comment_posted', fn( $comment_id, $user_id ) => $this->log( self::TYPE_COMMENT, $user_id, $comment_id ), 10, 2 );
		add_action( 'apollo_group_joined', fn( $group_id, $user_id ) => $this->log( self::TYPE_GROUP_JOIN, $user_id, $group_id ), 10, 2 );
		add_action( 'apollo_group_created', fn( $group_id, $user_id ) => $this->log( self::TYPE_GROUP_CREATE, $user_id, $group_id ), 10, 2 );
		add_action( 'apollo_event_created', fn( $event_id, $user_id ) => $this->log( self::TYPE_EVENT_CREATE, $user_id, $event_id ), 10, 2 );
		add_action( 'apollo_event_interested', fn( $event_id, $user_id ) => $this->log( self::TYPE_EVENT_INTEREST, $user_id, $event_id ), 10, 2 );
		add_action( 'apollo_classified_published', fn( $ad_id, $user_id ) => $this->log( self::TYPE_CLASSIFIED, $user_id, $ad_id ), 10, 2 );
		add_action( 'apollo_bubble_added', fn( $user_id, $friend_id ) => $this->log( self::TYPE_BUBBLE, $user_id, $friend_id ), 10, 2 );
		add_action( 'apollo_achievement_unlocked', fn( $user_id, $key ) => $this->log( self::TYPE_ACHIEVEMENT, $user_id, 0, array( 'achievement' => $key ) ), 10, 2 );
		add_action(
			'apollo_rank_changed',
			fn( $user_id, $old, $new ) => $this->log(
				self::TYPE_RANK,
				$user_id,
				0,
				array(
					'old' => $old,
					'new' => $new,
				)
			),
			10,
			3
		);
		add_action( 'apollo_document_signed', fn( $doc_id, $user_id ) => $this->log( self::TYPE_DOCUMENT, $user_id, $doc_id ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'registerEndpoints' ) );
	}
	public function log( string $type, int $user_id, int $object_id = 0, array $meta = array(), int $group_id = 0 ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'apollo_activity',
			array(
				'user_id'    => $user_id,
				'type'       => $type,
				'object_id'  => $object_id,
				'group_id'   => $group_id,
				'meta'       => $meta ? wp_json_encode( $meta ) : null,
				'created_at' => current_time( 'mysql', true ),
			)
		);
		$activity_id = (int) $wpdb->insert_id;
		do_action( 'apollo_activity_logged', $activity_id, $type, $user_id, $object_id, $meta );
		return $activity_id;
	}
	public function getSitewide( int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_activity WHERE group_id = 0 ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function getGroupActivity( int $group_id, int $limit = 20 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_activity WHERE group_id = %d ORDER BY created_at DESC LIMIT %d",
				$group_id,
				$limit
			),
			ARRAY_A
		);
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function getUserActivity( int $user_id, int $limit = 20 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_activity WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		);
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function getMentions( int $user_id, int $limit = 20 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_activity WHERE type = 'mention' AND JSON_EXTRACT(meta, '$.mentioned_user') = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		);
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function getFavorites( int $user_id, int $limit = 20 ): array {
		global $wpdb;
		$activity_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT activity_id FROM {$wpdb->prefix}apollo_activity_favorites WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			)
		);
		if ( empty( $activity_ids ) ) {
			return array();
		}
		$ids  = \implode( ',', array_map( 'intval', $activity_ids ) );
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}apollo_activity WHERE id IN ({$ids}) ORDER BY created_at DESC", ARRAY_A );
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function getFriendsActivity( int $user_id, int $limit = 20 ): array {
		global $wpdb;
		$bubble = get_user_meta( $user_id, '_apollo_bubble', true ) ?: array();
		if ( empty( $bubble ) ) {
			return array();
		}
		$ids  = \implode( ',', array_map( 'intval', $bubble ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_activity WHERE user_id IN ({$ids}) ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		return array_map( array( $this, 'formatActivity' ), $rows );
	}
	public function favoriteActivity( int $user_id, int $activity_id ): bool {
		global $wpdb;
		return (bool) $wpdb->insert(
			$wpdb->prefix . 'apollo_activity_favorites',
			array(
				'user_id'     => $user_id,
				'activity_id' => $activity_id,
				'created_at'  => current_time( 'mysql', true ),
			)
		);
	}
	public function unfavoriteActivity( int $user_id, int $activity_id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete(
			$wpdb->prefix . 'apollo_activity_favorites',
			array(
				'user_id'     => $user_id,
				'activity_id' => $activity_id,
			)
		);
	}
	private function formatActivity( array $row ): array {
		$user = get_userdata( (int) $row['user_id'] );
		$meta = $row['meta'] ? json_decode( $row['meta'], true ) : array();
		return array(
			'id'        => (int) $row['id'],
			'type'      => $row['type'],
			'user'      => array(
				'id'     => (int) $row['user_id'],
				'name'   => $user ? $user->display_name : 'Unknown',
				'avatar' => get_avatar_url( (int) $row['user_id'], array( 'size' => 50 ) ),
			),
			'object_id' => (int) $row['object_id'],
			'group_id'  => (int) $row['group_id'],
			'meta'      => $meta,
			'message'   => $this->generateMessage( $row['type'], $user ? $user->display_name : '', $meta ),
			'time'      => $row['created_at'],
			'time_ago'  => human_time_diff( strtotime( $row['created_at'] ), time() ),
		);
	}
	private function generateMessage( string $type, string $name, array $meta ): string {
		return match ( $type ) {
			self::TYPE_POST => "{$name} publicou um novo post",
			self::TYPE_COMMENT => "{$name} comentou",
			self::TYPE_LIKE => "{$name} curtiu",
			self::TYPE_FOLLOW => "{$name} começou a seguir",
			self::TYPE_GROUP_JOIN => "{$name} entrou no grupo",
			self::TYPE_GROUP_CREATE => "{$name} criou um novo grupo",
			self::TYPE_EVENT_CREATE => "{$name} criou um novo evento",
			self::TYPE_EVENT_INTEREST => "{$name} tem interesse em um evento",
			self::TYPE_CLASSIFIED => "{$name} publicou um anúncio",
			self::TYPE_BUBBLE => "{$name} adicionou um close friend",
			self::TYPE_ACHIEVEMENT => "{$name} desbloqueou: " . ( $meta['achievement'] ?? 'conquista' ),
			self::TYPE_RANK => "{$name} subiu para " . ( $meta['new'] ?? 'novo rank' ),
			self::TYPE_DOCUMENT => "{$name} assinou um documento",
			self::TYPE_MENTION => "{$name} mencionou você",
			self::TYPE_SHARE => "{$name} compartilhou",
			default => "{$name} fez uma ação",
		};
	}
	public function registerEndpoints(): void {
		register_rest_route(
			'apollo/v1',
			'/activity',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->getSitewide( (int) ( $r->get_param( 'limit' ) ?: 20 ), (int) ( $r->get_param( 'offset' ) ?: 0 ) ), 200 ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/activity/me',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getUserActivity( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/activity/friends',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getFriendsActivity( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/activity/mentions',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getMentions( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/activity/favorites',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getFavorites( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/activity/group/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->getGroupActivity( (int) $r->get_param( 'id' ) ), 200 ),
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'plugins_loaded', fn() => ActivityStream::instance()->init(), 15 );
