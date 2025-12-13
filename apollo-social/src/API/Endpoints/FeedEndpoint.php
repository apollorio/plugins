<?php
/**
 * P0-5: Feed REST API Endpoint
 *
 * Unified feed endpoint that aggregates posts from multiple sources.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Apollo\Infrastructure\Rendering\FeedRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FeedEndpoint {

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
		// Get unified feed
		register_rest_route(
			'apollo/v1',
			'explore',
			array(
				'methods'                              => WP_REST_Server::READABLE,
				'callback'                             => array( $this, 'getFeed' ),
				'permission_callback'                  => '__return_true',
				// Publicly readable
												'args' => array(
													'page' => array(
														'default'     => 1,
														'type'        => 'integer',
														'description' => __( 'Page number for pagination.', 'apollo-social' ),
													),
													'per_page' => array(
														'default'     => 20,
														'type'        => 'integer',
														'description' => __( 'Number of items per page.', 'apollo-social' ),
														'minimum'     => 1,
														'maximum'     => 100,
													),
													'type' => array(
														'default'     => 'all',
														'type'        => 'string',
														'enum'        => array( 'all', 'user_post', 'event', 'ad', 'news' ),
														'description' => __( 'Filter by content type.', 'apollo-social' ),
													),
												),
			)
		);
	}

	/**
	 * P0-5: Get unified feed
	 */
	public function getFeed( WP_REST_Request $request ): WP_REST_Response {
		$page        = $request->get_param( 'page' );
		$per_page    = $request->get_param( 'per_page' );
		$type_filter = $request->get_param( 'type' );

		$renderer = new FeedRenderer();
		$posts    = $renderer->getUnifiedFeedPosts( $page, $per_page );

		// Apply type filter if specified
		if ( $type_filter !== 'all' ) {
			$posts = array_filter(
				$posts,
				function ( $post ) use ( $type_filter ) {
					return $post['type'] === $type_filter;
				}
			);
			$posts = array_values( $posts );
			// Re-index
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'posts'      => $posts,
					'pagination' => array(
						'page'     => $page,
						'per_page' => $per_page,
						'total'    => count( $posts ),
					),
				),
			),
			200
		);
	}
}
