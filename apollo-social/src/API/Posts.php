<?php
/**
 * REST API: Posts Endpoint
 *
 * Provides post data for Plano editor library
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST endpoints for posts
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'apollo/v1',
			'/posts/(?P<type>[a-zA-Z0-9_-]+)/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => 'apollo_rest_get_post',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => [
					'type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $param ) {
							return in_array( $param, [ 'anuncio', 'event_listing', 'event_dj', 'event_local' ], true );
						},
					],
					'id'   => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}
);

/**
 * Get post data by type and ID
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function apollo_rest_get_post( $request ) {
	$post_type = sanitize_key( $request->get_param( 'type' ) );
	$post_id   = absint( $request->get_param( 'id' ) );

	// Build query args based on post type
	$query_args = [
		'post_type'      => $post_type,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	];

	// Special handling for anuncio (classifieds)
	if ( $post_type === 'anuncio' ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for anuncio post type lookup by meta_key.
		$query_args['meta_query'] = [
			[
				'key'   => 'id',
				'value' => $post_id,
			],
		];
	} else {
		$query_args['p'] = $post_id;
	}

	$posts = get_posts( $query_args );

	if ( empty( $posts ) ) {
		return new WP_Error(
			'post_not_found',
			esc_html__( 'Post não encontrado', 'apollo-social' ),
			[ 'status' => 404 ]
		);
	}

	$post = $posts[0];

	// Build response data based on post type
	$data = [
		'id'    => $post->ID,
		'title' => get_the_title( $post->ID ),
		'type'  => $post_type,
	];

	// Add type-specific data
	switch ( $post_type ) {
		case 'event_listing':
			$data['start_date'] = get_post_meta( $post->ID, '_event_start_date', true );
			$data['end_date']   = get_post_meta( $post->ID, '_event_end_date', true );
			$data['banner']     = get_post_meta( $post->ID, '_event_banner', true );
			$local_id           = get_post_meta( $post->ID, '_event_local_ids', true );
			if ( $local_id ) {
				$local_post = get_post( absint( $local_id ) );
				if ( $local_post ) {
					$data['local'] = [
						'name'    => get_the_title( $local_post->ID ),
						'address' => get_post_meta( $local_post->ID, '_local_address', true ),
					];
				}
			}
			break;

		case 'event_dj':
			$data['photo'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
			break;

		case 'event_local':
			$data['address']   = get_post_meta( $post->ID, '_local_address', true );
			$data['latitude']  = get_post_meta( $post->ID, '_local_latitude', true );
			$data['longitude'] = get_post_meta( $post->ID, '_local_longitude', true );
			break;

		case 'anuncio':
			$data['description'] = wp_trim_words( $post->post_content, 50 );
			$data['image']        = get_the_post_thumbnail_url( $post->ID, 'medium' );
			break;
	}

	return rest_ensure_response( $data );
}

