<?php
/**
 * P0-6: Favorites REST API Endpoint
 *
 * Unified endpoint for toggling favorites on events and other content types.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FavoritesEndpoint {

	/**
	 * Register REST routes
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register routes
	 */
	public function registerRoutes(): void {
		// Toggle favorite
		register_rest_route(
			'apollo/v1',
			'/favorites',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggleFavorite' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'content_id'   => array(
						'required'    => true,
						'type'        => 'integer',
						'description' => __( 'ID of the content to favorite.', 'apollo-social' ),
					),
					'content_type' => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Type of content (e.g., event_listing, apollo_social_post).', 'apollo-social' ),
						'enum'        => array( 'event_listing', 'apollo_social_post', 'event_dj', 'event_local' ),
					),
				),
			)
		);

		// Get favorites for current user
		register_rest_route(
			'apollo/v1',
			'/favorites',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getUserFavorites' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'content_type' => array(
						'required'    => false,
						'type'        => 'string',
						'description' => __( 'Filter by content type.', 'apollo-social' ),
					),
				),
			)
		);

		// Get favorite status for specific content
		register_rest_route(
			'apollo/v1',
			'/favorites/(?P<content_type>[a-zA-Z0-9_-]+)/(?P<content_id>\d+)',
			array(
				'methods'                              => WP_REST_Server::READABLE,
				'callback'                             => array( $this, 'getFavoriteStatus' ),
				'permission_callback'                  => '__return_true', 
				// Publicly readable
												'args' => array(
													'content_id'   => array(
														'required' => true,
														'type' => 'integer',
													),
													'content_type' => array(
														'required' => true,
														'type' => 'string',
													),
												),
			)
		);
	}

	/**
	 * Permission check
	 */
	public function permissionCheck( WP_REST_Request $request ): bool {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to manage favorites.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * P0-6: Toggle favorite status
	 */
	public function toggleFavorite( WP_REST_Request $request ): WP_REST_Response {
		$user_id      = get_current_user_id();
		$content_id   = $request->get_param( 'content_id' );
		$content_type = $request->get_param( 'content_type' );

		// Validate content exists
		if ( $content_type === 'event_listing' ) {
			$post = get_post( $content_id );
			if ( ! $post || $post->post_type !== 'event_listing' ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Event not found.', 'apollo-social' ),
					),
					404
				);
			}
		}

		// Get user favorites
		$user_favorites = get_user_meta( $user_id, 'apollo_favorites', true );
		if ( ! is_array( $user_favorites ) ) {
			$user_favorites = array();
		}

		// Structure: ['event_listing' => [1, 2, 3], 'apollo_social_post' => [5, 6]]
		if ( ! isset( $user_favorites[ $content_type ] ) ) {
			$user_favorites[ $content_type ] = array();
		}

		$is_favorited = in_array( $content_id, $user_favorites[ $content_type ], true );

		if ( $is_favorited ) {
			// Remove favorite
			$user_favorites[ $content_type ] = array_values(
				array_diff( $user_favorites[ $content_type ], array( $content_id ) )
			);
		} else {
			// Add favorite
			$user_favorites[ $content_type ][] = $content_id;
			$user_favorites[ $content_type ]   = array_values( array_unique( $user_favorites[ $content_type ] ) );
		}

		// Update user meta
		update_user_meta( $user_id, 'apollo_favorites', $user_favorites );

		// Update post meta count (for events)
		if ( $content_type === 'event_listing' ) {
			$this->updateEventFavoriteCount( $content_id );
		}

		// Get updated count
		$favorite_count = $this->getFavoriteCount( $content_id, $content_type );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'favorited'      => ! $is_favorited,
				'favorite_count' => $favorite_count,
			),
			200
		);
	}

	/**
	 * P0-6: Get user favorites
	 */
	public function getUserFavorites( WP_REST_Request $request ): WP_REST_Response {
		$user_id             = get_current_user_id();
		$content_type_filter = $request->get_param( 'content_type' );

		$user_favorites = get_user_meta( $user_id, 'apollo_favorites', true );
		if ( ! is_array( $user_favorites ) ) {
			$user_favorites = array();
		}

		if ( $content_type_filter ) {
			$favorites = isset( $user_favorites[ $content_type_filter ] )
				? $user_favorites[ $content_type_filter ]
				: array();
		} else {
			$favorites = $user_favorites;
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'favorites' => $favorites,
				'total'     => array_sum( array_map( 'count', $favorites ) ),
			),
			200
		);
	}

	/**
	 * P0-6: Get favorite status for content
	 */
	public function getFavoriteStatus( WP_REST_Request $request ): WP_REST_Response {
		$content_id   = $request->get_param( 'content_id' );
		$content_type = $request->get_param( 'content_type' );
		$user_id      = get_current_user_id();

		$is_favorited = false;
		if ( $user_id ) {
			$user_favorites = get_user_meta( $user_id, 'apollo_favorites', true );
			if ( is_array( $user_favorites ) && isset( $user_favorites[ $content_type ] ) ) {
				$is_favorited = in_array( $content_id, $user_favorites[ $content_type ], true );
			}
		}

		$favorite_count = $this->getFavoriteCount( $content_id, $content_type );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'favorited'      => $is_favorited,
				'favorite_count' => $favorite_count,
			),
			200
		);
	}

	/**
	 * P0-6: Update event favorite count in post meta
	 */
	private function updateEventFavoriteCount( int $event_id ): void {
		global $wpdb;

		// Count users who favorited this event
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} 
            WHERE meta_key = 'apollo_favorites' 
            AND meta_value LIKE %s",
				'%"event_listing";a:%' . $wpdb->esc_like( ':' . $event_id . ';' ) . '%'
			)
		);

		// Also check serialized format
		$users = $wpdb->get_results(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta} 
            WHERE meta_key = 'apollo_favorites'"
		);

		$actual_count = 0;
		foreach ( $users as $user ) {
			$favorites = maybe_unserialize( $user->meta_value );
			if ( is_array( $favorites ) && isset( $favorites['event_listing'] ) ) {
				if ( in_array( $event_id, $favorites['event_listing'], true ) ) {
					++$actual_count;
				}
			}
		}

		update_post_meta( $event_id, '_favorites_count', $actual_count );
	}

	/**
	 * P0-6: Get favorite count for content
	 */
	private function getFavoriteCount( int $content_id, string $content_type ): int {
		// For events, use cached meta
		if ( $content_type === 'event_listing' ) {
			$cached_count = get_post_meta( $content_id, '_favorites_count', true );
			if ( $cached_count !== '' ) {
				return (int) $cached_count;
			}
			// Recalculate if not cached
			$this->updateEventFavoriteCount( $content_id );
			return (int) get_post_meta( $content_id, '_favorites_count', true );
		}

		// For other types, count directly
		global $wpdb;
		$users = $wpdb->get_results(
			"SELECT meta_value FROM {$wpdb->usermeta} 
            WHERE meta_key = 'apollo_favorites'"
		);

		$count = 0;
		foreach ( $users as $user ) {
			$favorites = maybe_unserialize( $user->meta_value );
			if ( is_array( $favorites ) && isset( $favorites[ $content_type ] ) ) {
				if ( in_array( $content_id, $favorites[ $content_type ], true ) ) {
					++$count;
				}
			}
		}

		return $count;
	}
}
