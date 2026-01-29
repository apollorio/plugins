<?php
declare(strict_types=1);
namespace Apollo\Modules\Connections;

defined( 'ABSPATH' ) || exit;
final class BubbleSystem {
	public const MAX_BUBBLE        = 15;
	public const MAX_CLOSE_FRIENDS = 10;
	private static ?self $instance = null;
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'registerEndpoints' ) );
	}
	public function addToBubble( int $user_id, int $friend_id ): array {
		if ( $user_id === $friend_id ) {
			return array(
				'success' => false,
				'error'   => 'cannot_add_self',
			);
		}
		if ( ! get_userdata( $friend_id ) ) {
			return array(
				'success' => false,
				'error'   => 'user_not_found',
			);
		}
		$bubble = $this->getBubble( $user_id );
		if ( count( $bubble ) >= self::MAX_BUBBLE ) {
			return array(
				'success' => false,
				'error'   => 'bubble_full',
				'max'     => self::MAX_BUBBLE,
			);
		}
		if ( in_array( $friend_id, $bubble, true ) ) {
			return array(
				'success' => false,
				'error'   => 'already_in_bubble',
			);
		}
		$bubble[] = $friend_id;
		update_user_meta( $user_id, '_apollo_bubble', $bubble );
		$this->addMutual( $user_id, $friend_id );
		do_action( 'apollo_bubble_added', $user_id, $friend_id );
		return array(
			'success'      => true,
			'bubble_count' => count( $bubble ),
		);
	}
	public function removeFromBubble( int $user_id, int $friend_id ): array {
		$bubble = $this->getBubble( $user_id );
		$key    = array_search( $friend_id, $bubble, true );
		if ( $key === false ) {
			return array(
				'success' => false,
				'error'   => 'not_in_bubble',
			);
		}
		unset( $bubble[ $key ] );
		$bubble = array_values( $bubble );
		update_user_meta( $user_id, '_apollo_bubble', $bubble );
		$close = $this->getCloseFriends( $user_id );
		if ( in_array( $friend_id, $close, true ) ) {
			$this->removeCloseFriend( $user_id, $friend_id );
		}
		do_action( 'apollo_bubble_removed', $user_id, $friend_id );
		return array(
			'success'      => true,
			'bubble_count' => count( $bubble ),
		);
	}
	public function getBubble( int $user_id ): array {
		return get_user_meta( $user_id, '_apollo_bubble', true ) ?: array();
	}
	public function addCloseFriend( int $user_id, int $friend_id ): array {
		$bubble = $this->getBubble( $user_id );
		if ( ! in_array( $friend_id, $bubble, true ) ) {
			$add = $this->addToBubble( $user_id, $friend_id );
			if ( ! $add['success'] ) {
				return $add;
			}
		}
		$close = $this->getCloseFriends( $user_id );
		if ( count( $close ) >= self::MAX_CLOSE_FRIENDS ) {
			return array(
				'success' => false,
				'error'   => 'close_friends_full',
				'max'     => self::MAX_CLOSE_FRIENDS,
			);
		}
		if ( in_array( $friend_id, $close, true ) ) {
			return array(
				'success' => false,
				'error'   => 'already_close_friend',
			);
		}
		$close[] = $friend_id;
		update_user_meta( $user_id, '_apollo_close_friends', $close );
		do_action( 'apollo_close_friend_added', $user_id, $friend_id );
		return array(
			'success'     => true,
			'close_count' => count( $close ),
		);
	}
	public function removeCloseFriend( int $user_id, int $friend_id ): array {
		$close = $this->getCloseFriends( $user_id );
		$key   = array_search( $friend_id, $close, true );
		if ( $key === false ) {
			return array(
				'success' => false,
				'error'   => 'not_close_friend',
			);
		}
		unset( $close[ $key ] );
		update_user_meta( $user_id, '_apollo_close_friends', array_values( $close ) );
		do_action( 'apollo_close_friend_removed', $user_id, $friend_id );
		return array( 'success' => true );
	}
	public function getCloseFriends( int $user_id ): array {
		return get_user_meta( $user_id, '_apollo_close_friends', true ) ?: array();
	}
	public function getFollowers( int $user_id ): array {
		return get_user_meta( $user_id, '_apollo_followers', true ) ?: array();
	}
	public function getFollowing( int $user_id ): array {
		return get_user_meta( $user_id, '_apollo_following', true ) ?: array();
	}
	public function follow( int $user_id, int $target_id ): array {
		if ( $user_id === $target_id ) {
			return array(
				'success' => false,
				'error'   => 'cannot_follow_self',
			);
		}
		$following = $this->getFollowing( $user_id );
		if ( in_array( $target_id, $following, true ) ) {
			return array(
				'success' => false,
				'error'   => 'already_following',
			);
		}
		$following[] = $target_id;
		update_user_meta( $user_id, '_apollo_following', $following );
		$followers   = $this->getFollowers( $target_id );
		$followers[] = $user_id;
		update_user_meta( $target_id, '_apollo_followers', $followers );
		do_action( 'apollo_user_followed', $user_id, $target_id );
		return array(
			'success'         => true,
			'following_count' => count( $following ),
		);
	}
	public function unfollow( int $user_id, int $target_id ): array {
		$following = $this->getFollowing( $user_id );
		$key       = array_search( $target_id, $following, true );
		if ( $key === false ) {
			return array(
				'success' => false,
				'error'   => 'not_following',
			);
		}
		unset( $following[ $key ] );
		update_user_meta( $user_id, '_apollo_following', array_values( $following ) );
		$followers = $this->getFollowers( $target_id );
		$fkey      = array_search( $user_id, $followers, true );
		if ( $fkey !== false ) {
			unset( $followers[ $fkey ] );
			update_user_meta( $target_id, '_apollo_followers', array_values( $followers ) );
		}
		do_action( 'apollo_user_unfollowed', $user_id, $target_id );
		return array( 'success' => true );
	}
	public function getMutualFriends( int $user_id, int $other_id ): array {
		$my_bubble    = $this->getBubble( $user_id );
		$their_bubble = $this->getBubble( $other_id );
		return array_values( array_intersect( $my_bubble, $their_bubble ) );
	}
	public function getConnectionStats( int $user_id ): array {
		return array(
			'bubble_count'        => count( $this->getBubble( $user_id ) ),
			'bubble_max'          => self::MAX_BUBBLE,
			'close_friends_count' => count( $this->getCloseFriends( $user_id ) ),
			'close_friends_max'   => self::MAX_CLOSE_FRIENDS,
			'followers_count'     => count( $this->getFollowers( $user_id ) ),
			'following_count'     => count( $this->getFollowing( $user_id ) ),
		);
	}
	public function getBubbleWithDetails( int $user_id ): array {
		$bubble = $this->getBubble( $user_id );
		$close  = $this->getCloseFriends( $user_id );
		$result = array();
		foreach ( $bubble as $friend_id ) {
			$user = get_userdata( $friend_id );
			if ( ! $user ) {
				continue;
			}
			$result[] = array(
				'id'              => $friend_id,
				'name'            => $user->display_name,
				'avatar'          => get_avatar_url( $friend_id, array( 'size' => 100 ) ),
				'is_close_friend' => in_array( $friend_id, $close, true ),
				'online'          => $this->isOnline( $friend_id ),
				'profile_url'     => home_url( "/id/{$friend_id}" ),
			);
		}
		return $result;
	}
	private function addMutual( int $user_id, int $friend_id ): void {
		$their_bubble = $this->getBubble( $friend_id );
		if ( in_array( $user_id, $their_bubble, true ) ) {
			update_user_meta( $user_id, '_apollo_mutual_' . $friend_id, true );
			update_user_meta( $friend_id, '_apollo_mutual_' . $user_id, true );
		}
	}
	private function isOnline( int $user_id ): bool {
		$last = get_user_meta( $user_id, 'apollo_last_active', true );
		return $last && strtotime( $last ) > ( time() - 900 );
	}
	public function registerEndpoints(): void {
		register_rest_route(
			'apollo/v1',
			'/bubble',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response(
					array(
						'bubble' => $this->getBubbleWithDetails( get_current_user_id() ),
						'stats'  => $this->getConnectionStats( get_current_user_id() ),
					),
					200
				),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/bubble/add',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->addToBubble( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/bubble/remove',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->removeFromBubble( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/close-friends',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response(
					array(
						'close_friends' => $this->getCloseFriends( get_current_user_id() ),
						'max'           => self::MAX_CLOSE_FRIENDS,
					),
					200
				),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/close-friends/add',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->addCloseFriend( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/close-friends/remove',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->removeCloseFriend( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/follow',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->follow( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/unfollow',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->unfollow( get_current_user_id(), (int) $r->get_param( 'user_id' ) ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/connections/stats',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getConnectionStats( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}
}
add_action( 'plugins_loaded', fn() => BubbleSystem::instance()->init(), 15 );
