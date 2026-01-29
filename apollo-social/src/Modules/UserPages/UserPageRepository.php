<?php

namespace Apollo\Modules\UserPages;

use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Persistence helpers for linking users to their user_page posts.
 *
 * @category ApolloSocial
 * @package  ApolloSocial\UserPages
 * @author   Apollo Platform <tech@apollo.rio.br>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://apollo.rio.br
 */
final class UserPageRepository {

	/**
	 * Locate the user_page post associated with the given user.
	 *
	 * @param int $userId Target user ID.
	 *
	 * @return WP_Post|null
	 */
	public static function get( int $userId ): ?WP_Post {
		if ( $userId <= 0 ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => UserPageRegistrar::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'meta_key'       => UserPageRegistrar::META_KEY,
				'meta_value'     => $userId,
				'posts_per_page' => 1,
				'fields'         => 'all',
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();

			return null;
		}

		$post = $query->posts[0] instanceof WP_Post ? $query->posts[0] : null;
		wp_reset_postdata();

		return $post;
	}

	/**
	 * Retrieve the user_page or create it when missing.
	 *
	 * @param int $userId Target user ID.
	 *
	 * @return WP_Post|null
	 */
	public static function getOrCreate( int $userId ): ?WP_Post {
		$existing = self::get( $userId );
		if ( $existing instanceof WP_Post ) {
			return $existing;
		}

		$user = get_userdata( $userId );
		if ( ! $user ) {
			return null;
		}

		$postId = wp_insert_post(
			array(
				'post_type'    => UserPageRegistrar::POST_TYPE,
				'post_status'  => 'publish',
				'post_author'  => $userId,
				'post_title'   => sprintf(
					__( 'Página de %s', 'apollo-social' ),
					$user->display_name
				),
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $postId ) ) {
			return null;
		}

		update_post_meta( $postId, UserPageRegistrar::META_KEY, $userId );

		return get_post( $postId ) ?: null;
	}
}
