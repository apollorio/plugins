<?php
/**
 * ViewModel Factory - Apollo Design System
 *
 * Factory class for creating appropriate ViewModel instances
 * based on data type and context.
 *
 * @package ApolloCore\ViewModels
 */

class Apollo_ViewModel_Factory {
	/**
	 * Create ViewModel for event data
	 *
	 * @param WP_Post|object $event Event data
	 * @return Apollo_Event_ViewModel
	 */
	public static function create_event_viewmodel( $event ) {
		return new Apollo_Event_ViewModel( $event );
	}

	/**
	 * Create ViewModel for user data
	 *
	 * @param WP_User|object $user User data
	 * @return Apollo_User_ViewModel
	 */
	public static function create_user_viewmodel( $user ) {
		return new Apollo_User_ViewModel( $user );
	}

	/**
	 * Create ViewModel for social content
	 *
	 * @param WP_Post|object $post Social post data
	 * @return Apollo_Social_ViewModel
	 */
	public static function create_social_viewmodel( $post ) {
		return new Apollo_Social_ViewModel( $post );
	}

	/**
	 * Create appropriate ViewModel based on data type
	 *
	 * @param mixed  $data Data to transform
	 * @param string $context Optional context hint
	 * @return Apollo_Base_ViewModel|null
	 */
	public static function create_from_data( $data, $context = '' ) {
		if ( ! $data ) {
			return null;
		}

		// Generate cache key based on data type and ID.
		$cache_key = self::generate_cache_key( $data, $context );

		// Use cache manager if available.
		if ( class_exists( 'Apollo_Template_Cache_Manager' ) ) {
			return Apollo_Template_Cache_Manager::get_viewmodel_cache(
				$cache_key,
				function () use ( $data, $context ) {
					return self::create_viewmodel_instance( $data, $context );
				}
			);
		}

		return self::create_viewmodel_instance( $data, $context );
	}

	/**
	 * Generate cache key for ViewModel data
	 *
	 * @param mixed  $data Data object
	 * @param string $context Context hint
	 * @return string
	 */
	private static function generate_cache_key( $data, $context = '' ) {
		$key_parts = array();

		if ( is_a( $data, 'WP_Post' ) ) {
			$key_parts[] = 'post';
			$key_parts[] = $data->post_type;
			$key_parts[] = $data->ID;
			$key_parts[] = $data->post_modified; // Include modification time for cache invalidation.
		} elseif ( is_a( $data, 'WP_User' ) ) {
			$key_parts[] = 'user';
			$key_parts[] = $data->ID;
			$key_parts[] = get_user_meta( $data->ID, 'last_update', true ) ?: $data->user_registered;
		} elseif ( is_object( $data ) && isset( $data->ID ) ) {
			$key_parts[] = 'object';
			$key_parts[] = get_class( $data );
			$key_parts[] = $data->ID;
		} else {
			$key_parts[] = 'data';
			$key_parts[] = md5( serialize( $data ) );
		}

		if ( $context ) {
			$key_parts[] = $context;
		}

		return implode( '_', $key_parts );
	}

	/**
	 * Create ViewModel instance (internal method for caching)
	 *
	 * @param mixed  $data Data to transform
	 * @param string $context Optional context hint
	 * @return Apollo_Base_ViewModel|null
	 */
	private static function create_viewmodel_instance( $data, $context = '' ) {
		// Detect data type.
		if ( is_a( $data, 'WP_Post' ) ) {
			$post_type = $data->post_type;

			switch ( $post_type ) {
				case 'event_listing':
					return self::create_event_viewmodel( $data );
				case 'social_post':
				case 'group':
					return self::create_social_viewmodel( $data );
				default:
					return self::create_social_viewmodel( $data );
			}
		}

		if ( is_a( $data, 'WP_User' ) ) {
			return self::create_user_viewmodel( $data );
		}

		// Context-based detection.
		if ( $context === 'event' ) {
			return self::create_event_viewmodel( $data );
		}

		if ( $context === 'user' ) {
			return self::create_user_viewmodel( $data );
		}

		if ( $context === 'social' ) {
			return self::create_social_viewmodel( $data );
		}

		// Fallback - try to detect from object properties.
		if ( is_object( $data ) ) {
			if ( isset( $data->post_type ) && $data->post_type === 'event_listing' ) {
				return self::create_event_viewmodel( $data );
			}

			if ( isset( $data->user_login ) || isset( $data->display_name ) ) {
				return self::create_user_viewmodel( $data );
			}
		}

		return null;
	}

	/**
	 * Transform data with specific ViewModel method
	 *
	 * @param mixed  $data Data to transform
	 * @param string $method Method to call on ViewModel
	 * @param array  $args Additional arguments for method
	 * @return mixed
	 */
	public static function transform_with_method( $data, $method = 'get_template_data', $args = array() ) {
		$viewmodel = self::create_from_data( $data );
		if ( ! $viewmodel || ! method_exists( $viewmodel, $method ) ) {
			return null;
		}

		return call_user_func_array( array( $viewmodel, $method ), $args );
	}
}
