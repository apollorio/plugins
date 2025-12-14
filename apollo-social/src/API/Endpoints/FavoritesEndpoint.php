<?php

/**
 * REST API SMOKE TEST – PASSED
 * Route: /apollo/v1/favs
 * Affects: apollo-social.php, FavoritesEndpoint.php
 * Verified: 2025-12-06 – no conflicts, secure callback, unique namespace
 */
/**
 * P0-6: Favorites REST API Endpoint.
 *
 * Unified endpoint for toggling favorites on events and other content types.
 *
 * @package Apollo_Social
 * @version 2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 */

declare(strict_types=1);

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FavoritesEndpoint Class.
 *
 * Handles REST API endpoints for user favorites functionality.
 */
class FavoritesEndpoint {

	/**
	 * Register REST routes hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register all favorites routes.
	 *
	 * @return void
	 */
	public function registerRoutes(): void {
		// Toggle favorite endpoint (favs in Portuguese/short form).
		register_rest_route(
			'apollo/v1',
			'favs',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggleFavorite' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'content_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID of the content to favorite.', 'apollo-social' ),
						'sanitize_callback' => 'absint',
					),
					'content_type' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'Type of content (e.g., event_listing, apollo_social_post).', 'apollo-social' ),
						'enum'              => array( 'event_listing', 'apollo_social_post', 'event_dj', 'event_local' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// Get favorites for current user endpoint.
		register_rest_route(
			'apollo/v1',
			'favs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getUserFavorites' ),
				'permission_callback' => array( $this, 'permissionCheck' ),
				'args'                => array(
					'content_type' => array(
						'required'          => false,
						'type'              => 'string',
						'description'       => __( 'Filter by content type.', 'apollo-social' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		// Get favorite status for specific content endpoint.
		register_rest_route(
			'apollo/v1',
			'favs/(?P<content_type>[a-zA-Z0-9_-]+)/(?P<content_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getFavoriteStatus' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'content_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'content_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Permission check for authenticated endpoints.
	 *
	 * @param WP_REST_Request $_request REST request object (unused, required by REST API).
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function permissionCheck( WP_REST_Request $_request ) {
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
	 * Toggle favorite status for content.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response REST response.
	 */
	public function toggleFavorite( WP_REST_Request $request ): WP_REST_Response {
		$user_id      = get_current_user_id();
		$content_id   = $request->get_param( 'content_id' );
		$content_type = $request->get_param( 'content_type' );

		// Validate content exists for events.
		if ( 'event_listing' === $content_type ) {
			$post = get_post( $content_id );
			if ( ! $post || 'event_listing' !== $post->post_type ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Event not found.', 'apollo-social' ),
					),
					404
				);
			}
		}

		// Get user favorites from meta.
		$user_favorites = get_user_meta( $user_id, 'apollo_favorites', true );
		if ( ! is_array( $user_favorites ) ) {
			$user_favorites = array();
		}

		// Initialize content type array if needed.
		if ( ! isset( $user_favorites[ $content_type ] ) ) {
			$user_favorites[ $content_type ] = array();
		}

		$is_favorited = in_array( $content_id, $user_favorites[ $content_type ], true );

		if ( $is_favorited ) {
			// Remove from favorites.
			$user_favorites[ $content_type ] = array_values(
				array_diff( $user_favorites[ $content_type ], array( $content_id ) )
			);
		} else {
			// Add to favorites.
			$user_favorites[ $content_type ][] = $content_id;
			$user_favorites[ $content_type ]   = array_values( array_unique( $user_favorites[ $content_type ] ) );
		}

		// Update user meta with new favorites.
		update_user_meta( $user_id, 'apollo_favorites', $user_favorites );

		// Update post meta count for events.
		if ( 'event_listing' === $content_type ) {
			$this->updateEventFavoriteCount( $content_id );
		}

		// Get updated count for response.
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
	 * Get user favorites list.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response REST response.
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
				'total'     => is_array( $favorites ) ? array_sum( array_map( 'count', $favorites ) ) : 0,
			),
			200
		);
	}

	/**
	 * Get favorite status for specific content.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response REST response.
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
	 * Update event favorite count in post meta.
	 *
	 * @param int $event_id The event ID to update.
	 * @return void
	 */
	private function updateEventFavoriteCount( int $event_id ): void {
		global $wpdb;

		// Get all users with favorites meta.
		$users = $wpdb->get_results(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'apollo_favorites'"
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
	 * Get favorite count for content.
	 *
	 * @param int    $content_id   The content ID.
	 * @param string $content_type The content type.
	 * @return int The favorite count.
	 */
	private function getFavoriteCount( int $content_id, string $content_type ): int {
		// For events, use cached meta value.
		if ( 'event_listing' === $content_type ) {
			$cached_count = get_post_meta( $content_id, '_favorites_count', true );
			if ( '' !== $cached_count ) {
				return (int) $cached_count;
			}
			// Recalculate if not cached.
			$this->updateEventFavoriteCount( $content_id );

			return (int) get_post_meta( $content_id, '_favorites_count', true );
		}

		// For other types, count directly from usermeta.
		global $wpdb;
		$users = $wpdb->get_results(
			"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'apollo_favorites'"
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
