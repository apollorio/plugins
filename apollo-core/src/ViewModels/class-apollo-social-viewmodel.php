<?php
/**
 * Social Content ViewModel - Apollo Design System
 *
 * Transforms WordPress social content data into approved DOM structures
 * for feeds, posts, groups, and social interactions.
 *
 * @package ApolloCore\ViewModels
 */

class Apollo_Social_ViewModel extends Apollo_Base_ViewModel {
	/**
	 * Transform social post data for feed display
	 *
	 * @return void
	 */
	protected function transform() {
		if ( ! $this->data || ! is_object( $this->data ) ) {
			$this->add_error( 'data', 'Invalid social content data provided' );
			return;
		}

		$post      = $this->data;
		$post_id   = $post->ID ?? 0;
		$author_id = $post->post_author ?? 0;

		// Basic post information.
		$this->transformed_data = array(
			'post_id'   => $post_id,
			'content'   => wp_kses_post( $post->post_content ),
			'excerpt'   => $this->get_excerpt( $post->post_content, 200 ),
			'date'      => $this->format_date( $post->post_date, 'M j, Y' ),
			'url'       => $this->sanitize_url( get_permalink( $post_id ) ),
			'post_type' => $this->sanitize_attr( $post->post_type ),
		);

		// Author information.
		if ( $author_id ) {
			$author = get_userdata( $author_id );
			if ( $author ) {
				$this->transformed_data['author'] = array(
					'id'          => $author_id,
					'name'        => $this->sanitize_text( $author->display_name ),
					'avatar'      => $this->get_user_avatar( $author_id, 48 ),
					'profile_url' => $this->sanitize_url( get_author_posts_url( $author_id ) ),
				);
			}
		}

		// Media attachments.
		$this->transformed_data['media'] = $this->get_post_media( $post_id );

		// Engagement metrics.
		$this->transformed_data['engagement'] = array(
			'likes'      => intval( get_post_meta( $post_id, '_likes_count', true ) ?: 0 ),
			'comments'   => intval( get_post_meta( $post_id, '_comments_count', true ) ?: 0 ),
			'shares'     => intval( get_post_meta( $post_id, '_shares_count', true ) ?: 0 ),
			'user_liked' => $this->user_has_liked( $post_id ),
		);

		// Categories/Tags.
		$this->transformed_data['tags']       = $this->get_terms( $post_id, 'post_tag' );
		$this->transformed_data['categories'] = $this->get_terms( $post_id, 'category' );
	}

	/**
	 * Get post media attachments
	 *
	 * @param int $post_id
	 * @return array
	 */
	protected function get_post_media( $post_id ) {
		$media = array();

		// Featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			$media[] = array(
				'type' => 'image',
				'url'  => $this->get_featured_image( $post_id, 'large' ),
				'alt'  => $this->sanitize_attr( get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true ) ?: '' ),
			);
		}

		// Gallery images.
		$gallery = get_post_meta( $post_id, '_gallery_images', true );
		if ( $gallery && is_array( $gallery ) ) {
			foreach ( $gallery as $image_id ) {
				$media[] = array(
					'type' => 'image',
					'url'  => $this->sanitize_url( wp_get_attachment_image_url( $image_id, 'large' ) ),
					'alt'  => $this->sanitize_attr( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: '' ),
				);
			}
		}

		// Video URL.
		$video_url = get_post_meta( $post_id, '_video_url', true );
		if ( $video_url ) {
			$media[] = array(
				'type' => 'video',
				'url'  => $this->sanitize_url( $video_url ),
			);
		}

		return $media;
	}

	/**
	 * Check if current user has liked the post
	 *
	 * @param int $post_id
	 * @return bool
	 */
	protected function user_has_liked( $post_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id     = get_current_user_id();
		$liked_posts = get_user_meta( $user_id, '_liked_posts', true ) ?: array();

		return in_array( $post_id, $liked_posts );
	}

	/**
	 * Transform social data for group display
	 *
	 * @return array Extended data for group template
	 */
	public function get_group_data() {
		$base_data = $this->get_template_data();

		if ( ! $this->is_valid() ) {
			return $base_data;
		}

		$post    = $this->data;
		$post_id = $post->ID ?? 0;

		// Group-specific information.
		$base_data['group'] = array(
			'member_count' => intval( get_post_meta( $post_id, '_member_count', true ) ?: 0 ),
			'is_private'   => (bool) get_post_meta( $post_id, '_is_private', true ),
			'is_member'    => $this->user_is_member( $post_id ),
			'can_join'     => $this->user_can_join( $post_id ),
		);

		// Recent posts in group.
		$group_posts = get_posts(
			array(
				'post_type'      => 'social_post',
				'meta_query'     => array(
					array(
						'key'     => '_group_id',
						'value'   => $post_id,
						'compare' => '=',
					),
				),
				'posts_per_page' => 10,
			)
		);

		$base_data['recent_posts'] = array_map(
			function ( $post ) {
				$viewmodel = new self( $post );
				return $viewmodel->get_template_data();
			},
			$group_posts
		);

		return $base_data;
	}

	/**
	 * Check if current user is a member of the group
	 *
	 * @param int $group_id
	 * @return bool
	 */
	protected function user_is_member( $group_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id       = get_current_user_id();
		$member_groups = get_user_meta( $user_id, '_member_groups', true ) ?: array();

		return in_array( $group_id, $member_groups );
	}

	/**
	 * Check if current user can join the group
	 *
	 * @param int $group_id
	 * @return bool
	 */
	protected function user_can_join( $group_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$is_private = (bool) get_post_meta( $group_id, '_is_private', true );
		if ( $is_private ) {
			// For private groups, user needs invitation.
			return (bool) get_user_meta( get_current_user_id(), "_invited_to_group_{$group_id}", true );
		}

		return ! $this->user_is_member( $group_id );
	}

	/**
	 * Transform multiple posts for feed display
	 *
	 * @param array $posts Array of post objects
	 * @return array
	 */
	public static function transform_feed_posts( $posts ) {
		if ( ! is_array( $posts ) ) {
			return array();
		}

		return array_map(
			function ( $post ) {
				$viewmodel = new self( $post );
				return $viewmodel->get_template_data();
			},
			$posts
		);
	}

	/**
	 * Transform multiple groups for listing display
	 *
	 * @param array $groups Array of group objects
	 * @return array
	 */
	public static function transform_groups_listing( $groups ) {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		return array_map(
			function ( $group ) {
				$viewmodel = new self( $group );
				return $viewmodel->get_group_data();
			},
			$groups
		);
	}
}
