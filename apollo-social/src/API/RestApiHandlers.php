<?php
declare(strict_types=1);
namespace Apollo\Api;

final class RestApiHandlers {

	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'registerRoutes' ) );
	}

	public static function registerRoutes(): void {
		$ns = 'apollo/v1';
		register_rest_route(
			$ns,
			'/members',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getMembers' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/members/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getMember' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/members/online',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getOnlineMembers' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			$ns,
			'/activity',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getActivity' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/activity',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'postActivity' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		// NOTE: /groups endpoints removed - use /comunas and /nucleos via GroupsModule
		// See: src/Modules/Groups/GroupsModule.php::registerEndpoints()
		register_rest_route(
			$ns,
			'/leaderboard',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getLeaderboard' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/competitions',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getCompetitions' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/map/pins',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getMapPins' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/me',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getMe' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			$ns,
			'/me/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'getMyStats' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}

	public static function getMembers( \WP_REST_Request $req ): \WP_REST_Response {
		$args    = array(
			'search' => $req->get_param( 'search' ) ?: '',
			'limit'  => min( 100, (int) ( $req->get_param( 'limit' ) ?: 20 ) ),
			'offset' => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		$members = \Apollo\Modules\Members\MembersDirectoryRepository::search( $args );
		$total   = \Apollo\Modules\Members\MembersDirectoryRepository::count( $args );
		return new\WP_REST_Response(
			array(
				'members' => $members,
				'total'   => $total,
			),
			200
		);
	}

	public static function getMember( \WP_REST_Request $req ): \WP_REST_Response {
		$id   = (int) $req->get_param( 'id' );
		$user = get_userdata( $id );
		if ( ! $user ) {
			return new\WP_REST_Response( array( 'error' => 'User not found' ), 404 );
		}
		$data = array(
			'id'          => $user->ID,
			'name'        => $user->display_name,
			'avatar'      => get_avatar_url( $user->ID, array( 'size' => 200 ) ),
			'is_verified' => \Apollo\Modules\Members\VerifiedUsersRepository::isVerified( $id ),
			'is_online'   => \Apollo\Modules\Members\OnlineUsersRepository::isOnline( $id ),
		);
		return new\WP_REST_Response( $data, 200 );
	}

	public static function getOnlineMembers( \WP_REST_Request $req ): \WP_REST_Response {
		$limit = min( 100, (int) ( $req->get_param( 'limit' ) ?: 50 ) );
		$users = \Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers( $limit );
		return new\WP_REST_Response(
			array(
				'users' => $users,
				'count' => count( $users ),
			),
			200
		);
	}

	public static function getActivity( \WP_REST_Request $req ): \WP_REST_Response {
		$args     = array(
			'user_id'   => $req->get_param( 'user_id' ) ? (int) $req->get_param( 'user_id' ) : null,
			'component' => $req->get_param( 'component' ) ?: '',
			'limit'     => min( 100, (int) ( $req->get_param( 'limit' ) ?: 20 ) ),
			'offset'    => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		$activity = \Apollo\Modules\Activity\ActivityRepository::getFeed( $args );
		return new\WP_REST_Response( array( 'activity' => $activity ), 200 );
	}

	public static function postActivity( \WP_REST_Request $req ): \WP_REST_Response {
		$userId  = get_current_user_id();
		$content = sanitize_textarea_field( $req->get_param( 'content' ) ?: '' );
		if ( empty( $content ) ) {
			return new\WP_REST_Response( array( 'error' => 'Content required' ), 400 );
		}
		$id = \Apollo\Modules\Activity\ActivityRepository::create(
			array(
				'user_id'   => $userId,
				'action'    => 'status_update',
				'component' => 'activity',
				'type'      => 'status',
				'content'   => $content,
				'privacy'   => sanitize_key( $req->get_param( 'privacy' ) ?: 'public' ),
			)
		);
		return new\WP_REST_Response( array( 'id' => $id ), 201 );
	}

	/**
	 * @deprecated Use /apollo/v1/comunas or /apollo/v1/nucleos instead
	 */
	public static function getGroups( \WP_REST_Request $req ): \WP_REST_Response {
		_doing_it_wrong( __METHOD__, 'Use /apollo/v1/comunas or /apollo/v1/nucleos instead.', '2.3.0' );
		$args   = array(
			'search' => $req->get_param( 'search' ) ?: '',
			'status' => 'public',
			'limit'  => min( 100, (int) ( $req->get_param( 'limit' ) ?: 20 ) ),
			'offset' => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		$groups = \Apollo\Modules\Groups\GroupsRepository::search( $args );
		return new\WP_REST_Response( array( 'groups' => $groups ), 200 );
	}

	/**
	 * @deprecated Use /apollo/v1/comunas/{id} or /apollo/v1/nucleos/{id} instead
	 */
	public static function getGroup( \WP_REST_Request $req ): \WP_REST_Response {
		_doing_it_wrong( __METHOD__, 'Use /apollo/v1/comunas/{id} or /apollo/v1/nucleos/{id} instead.', '2.3.0' );
		$id    = (int) $req->get_param( 'id' );
		$group = \Apollo\Modules\Groups\GroupsRepository::get( $id );
		if ( ! $group ) {
			return new\WP_REST_Response( array( 'error' => 'Group not found' ), 404 );
		}
		if ( $group['status'] === 'hidden' && ! is_user_logged_in() ) {
			return new\WP_REST_Response( array( 'error' => 'Not allowed' ), 403 );
		}
		return new\WP_REST_Response( $group, 200 );
	}

	public static function getLeaderboard( \WP_REST_Request $req ): \WP_REST_Response {
		$limit       = min( 100, (int) ( $req->get_param( 'limit' ) ?: 50 ) );
		$leaderboard = \Apollo\Modules\Gamification\PointsRepository::getLeaderboard( 'default', $limit );
		return new\WP_REST_Response( array( 'leaderboard' => $leaderboard ), 200 );
	}

	public static function getCompetitions( \WP_REST_Request $req ): \WP_REST_Response {
		$active   = \Apollo\Modules\Gamification\CompetitionsRepository::getActive();
		$upcoming = \Apollo\Modules\Gamification\CompetitionsRepository::getUpcoming();
		return new\WP_REST_Response(
			array(
				'active'   => $active,
				'upcoming' => $upcoming,
			),
			200
		);
	}

	public static function getMapPins( \WP_REST_Request $req ): \WP_REST_Response {
		$cat     = $req->get_param( 'category' ) ?: '';
		$pins    = $cat ? \Apollo\Modules\Maps\MapPinsRepository::getByCategory( $cat ) : \Apollo\Modules\Maps\MapPinsRepository::getAll();
		$geojson = $req->get_param( 'format' ) === 'geojson';
		return new\WP_REST_Response( $geojson ? \Apollo\Modules\Maps\MapPinsRepository::toGeoJson( $pins ) : array( 'pins' => $pins ), 200 );
	}

	public static function getMe( \WP_REST_Request $req ): \WP_REST_Response {
		$userId = get_current_user_id();
		$user   = get_userdata( $userId );
		return new\WP_REST_Response(
			array(
				'id'          => $userId,
				'name'        => $user->display_name,
				'email'       => $user->user_email,
				'avatar'      => get_avatar_url( $userId, array( 'size' => 200 ) ),
				'is_verified' => \Apollo\Modules\Members\VerifiedUsersRepository::isVerified( $userId ),
				'settings'    => \Apollo\Modules\Members\UserSettingsRepository::getWithDefaults( $userId ),
			),
			200
		);
	}

	public static function getMyStats( \WP_REST_Request $req ): \WP_REST_Response {
		$userId = get_current_user_id();
		$stats  = \Apollo\Modules\MyData\MyDataRepository::getMyStats( $userId );
		return new\WP_REST_Response( $stats, 200 );
	}
}
