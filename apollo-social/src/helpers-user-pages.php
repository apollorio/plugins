<?php

/**
 * User Pages Helper Functions
 * Functions for managing user pages in Apollo Social
 */

if ( ! function_exists( 'apollo_get_user_page' ) ) {
	/**
	 * Get user page by user ID
	 *
	 * @param int $user_id User ID
	 * @return \WP_Post|null User page post object or null
	 */
	function apollo_get_user_page( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return null;
		}

		// Check meta first
		$page_id = get_user_meta( $user_id, 'apollo_user_page_id', true );

		if ( $page_id ) {
			$page = get_post( absint( $page_id ) );
			if ( $page && $page->post_status === 'publish' ) {
				return $page;
			}
		}

		// Fallback: search by post meta
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'meta_key'       => '_apollo_user_id',
				'meta_value'     => $user_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $pages ) ) {
			return get_post( $pages[0] );
		}

		return null;
	}
}//end if

if ( ! function_exists( 'apollo_get_or_create_user_page' ) ) {
	/**
	 * Get or create user page
	 *
	 * @param int $user_id User ID
	 * @return \WP_Post User page post object
	 */
	function apollo_get_or_create_user_page( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return null;
		}

		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return null;
		}

		// Try to get existing page
		$page = apollo_get_user_page( $user_id );

		if ( $page ) {
			return $page;
		}

		// Create new page
		$page_data = array(
			'post_title'   => 'Perfil de ' . $user->display_name,
			'post_name'    => 'perfil-' . $user_id,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => $user_id,
			'post_content' => '<!-- Apollo User Page -->',
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return null;
		}

		// Save meta
		update_user_meta( $user_id, 'apollo_user_page_id', $page_id );
		update_post_meta( $page_id, '_apollo_user_id', $user_id );
		update_post_meta( $page_id, '_apollo_canvas_page', true );
		update_post_meta( $page_id, '_apollo_canvas_template', 'users/dashboard.php' );

		// Enable comments for depoimentos
		wp_update_post(
			array(
				'ID'             => $page_id,
				'comment_status' => 'open',
			)
		);

		return get_post( $page_id );
	}
}//end if
