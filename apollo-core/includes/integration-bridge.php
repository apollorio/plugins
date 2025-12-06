<?php
/**
 * Apollo Core Integration Bridge
 *
 * Provides centralized hooks, utilities, and integration points for all Apollo plugins.
 * This file ensures Core acts as the "brain and heart" of the Apollo ecosystem.
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// SHARED UTILITY FUNCTIONS
// ============================================================================

if ( ! function_exists( 'apollo_log_missing_file' ) ) {
	/**
	 * Log missing file errors in debug mode
	 *
	 * @param string $path File path that was not found
	 * @return void
	 */
	function apollo_log_missing_file( string $path ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Apollo: Missing file: ' . $path );
		}
	}
}

if ( ! function_exists( 'apollo_is_debug_mode' ) ) {
	/**
	 * Check if Apollo debug mode is enabled
	 *
	 * @return bool
	 */
	function apollo_is_debug_mode(): bool {
		return defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG;
	}
}

if ( ! function_exists( 'apollo_get_asset_url' ) ) {
	/**
	 * Get Apollo asset URL (unified assets source)
	 *
	 * @param string $asset Asset filename
	 * @return string Full URL to asset
	 */
	function apollo_get_asset_url( string $asset ): string {
		return 'https://assets.apollo.rio.br/' . ltrim( $asset, '/' );
	}
}

if ( ! function_exists( 'apollo_get_plugin_data' ) ) {
	/**
	 * Get data about an Apollo plugin
	 *
	 * @param string $plugin Plugin identifier (core, social, events, rio)
	 * @return array Plugin data or empty array
	 */
	function apollo_get_plugin_data( string $plugin ): array {
		$plugins = array(
			'core'   => array(
				'slug'             => 'apollo-core',
				'file'             => 'apollo-core/apollo-core.php',
				'version_constant' => 'APOLLO_CORE_VERSION',
			),
			'social' => array(
				'slug'             => 'apollo-social',
				'file'             => 'apollo-social/apollo-social.php',
				'version_constant' => 'APOLLO_SOCIAL_VERSION',
			),
			'events' => array(
				'slug'             => 'apollo-events-manager',
				'file'             => 'apollo-events-manager/apollo-events-manager.php',
				'version_constant' => 'APOLLO_WPEM_VERSION',
			),
			'rio'    => array(
				'slug'             => 'apollo-rio',
				'file'             => 'apollo-rio/apollo-rio.php',
				'version_constant' => 'APOLLO_RIO_VERSION',
			),
		);

		return $plugins[ $plugin ] ?? array();
	}
}//end if

if ( ! function_exists( 'apollo_is_plugin_active' ) ) {
	/**
	 * Check if a specific Apollo plugin is active
	 *
	 * @param string $plugin Plugin identifier (core, social, events, rio)
	 * @return bool
	 */
	function apollo_is_plugin_active( string $plugin ): bool {
		$data = apollo_get_plugin_data( $plugin );

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $data['file'] );
	}
}//end if

// ============================================================================
// INTEGRATION HOOKS - Fire when Core loads for other plugins to hook into
// ============================================================================

/**
 * Fire Core ready action
 * Other plugins can hook into 'apollo_core_ready' to register their extensions
 */
add_action(
	'plugins_loaded',
	function () {
		/**
		 * Action: apollo_core_ready
		 * Fired when Apollo Core is fully loaded and ready
		 * Use this hook to register integrations with Core
		 */
		do_action( 'apollo_core_ready' );
	},
	15
);

/**
 * Fire REST API ready action
 */
add_action(
	'rest_api_init',
	function () {
		/**
		 * Action: apollo_rest_api_ready
		 * Fired when REST API is ready for Apollo routes
		 */
		do_action( 'apollo_rest_api_ready' );
	},
	5
);

// ============================================================================
// CPT REGISTRATION HOOKS
// ============================================================================

if ( ! function_exists( 'apollo_register_cpt' ) ) {
	/**
	 * Register a CPT through Core (for consistency)
	 *
	 * @param string $post_type Post type slug
	 * @param array  $args Post type arguments
	 * @return WP_Post_Type|WP_Error
	 */
	function apollo_register_cpt( string $post_type, array $args ) {
		/**
		 * Filter: apollo_cpt_args_{$post_type}
		 * Allows modifying CPT arguments before registration
		 */
		$args = apply_filters( "apollo_cpt_args_{$post_type}", $args );

		$result = register_post_type( $post_type, $args );

		/**
		 * Action: apollo_cpt_registered
		 * Fired after a CPT is registered through Core
		 */
		do_action( 'apollo_cpt_registered', $post_type, $args );

		return $result;
	}
}//end if

// ============================================================================
// TEMPLATE HOOKS
// ============================================================================

if ( ! function_exists( 'apollo_get_template' ) ) {
	/**
	 * Get template from Apollo plugins (respects hierarchy)
	 *
	 * @param string $template_name Template filename
	 * @param string $plugin Plugin to look in first (core, social, events, rio)
	 * @return string|false Template path or false if not found
	 */
	function apollo_get_template( string $template_name, string $plugin = 'core' ) {
		$paths = array();

		// Theme can override templates
		$paths[] = get_stylesheet_directory() . '/apollo/' . $template_name;
		$paths[] = get_template_directory() . '/apollo/' . $template_name;

		// Plugin-specific templates
		$plugin_dirs = array(
			'core'   => defined( 'APOLLO_CORE_PLUGIN_DIR' ) ? APOLLO_CORE_PLUGIN_DIR : '',
			'social' => defined( 'APOLLO_SOCIAL_PLUGIN_DIR' ) ? APOLLO_SOCIAL_PLUGIN_DIR : '',
			'events' => defined( 'APOLLO_WPEM_PATH' ) ? APOLLO_WPEM_PATH : '',
			'rio'    => defined( 'APOLLO_RIO_PATH' ) ? APOLLO_RIO_PATH : '',
		);

		// Prioritize specified plugin
		if ( ! empty( $plugin_dirs[ $plugin ] ) ) {
			$paths[] = $plugin_dirs[ $plugin ] . 'templates/' . $template_name;
		}

		// Then check all other plugins
		foreach ( $plugin_dirs as $key => $dir ) {
			if ( $key !== $plugin && ! empty( $dir ) ) {
				$paths[] = $dir . 'templates/' . $template_name;
			}
		}

		/**
		 * Filter: apollo_template_paths
		 * Allows adding custom template paths
		 */
		$paths = apply_filters( 'apollo_template_paths', $paths, $template_name, $plugin );

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}
}//end if

// ============================================================================
// CANVAS MODE INTEGRATION
// ============================================================================

if ( ! function_exists( 'apollo_is_canvas_mode' ) ) {
	/**
	 * Check if current page is in Canvas mode
	 *
	 * @return bool
	 */
	function apollo_is_canvas_mode(): bool {
		return (bool) apply_filters( 'apollo_is_canvas_mode', false );
	}
}

if ( ! function_exists( 'apollo_enqueue_canvas_assets' ) ) {
	/**
	 * Enqueue Canvas mode assets (uni.css, etc)
	 *
	 * Uses the centralized Apollo_Global_Assets class for consistent asset management.
	 *
	 * @return void
	 */
	function apollo_enqueue_canvas_assets(): void {
		// Use the Global Assets Manager if available
		if ( class_exists( 'Apollo_Global_Assets' ) ) {
			Apollo_Global_Assets::enqueue_all();
		} else {
			// Fallback to direct enqueue
			wp_enqueue_style(
				'apollo-uni-css',
				apollo_get_asset_url( 'uni.css' ),
				array(),
				defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : '1.0.0'
			);
		}

		/**
		 * Action: apollo_canvas_assets_enqueued
		 * Fired after Canvas assets are enqueued
		 */
		do_action( 'apollo_canvas_assets_enqueued' );
	}
}//end if

if ( ! function_exists( 'apollo_enqueue_global_assets' ) ) {
	/**
	 * Enqueue global Apollo assets (UNI.CSS design system)
	 *
	 * This is the recommended function for plugins to use when
	 * they need to load Apollo's global design system assets.
	 *
	 * @param string $scope 'all', 'css', or 'js'
	 * @return void
	 */
	function apollo_enqueue_global_assets( string $scope = 'all' ): void {
		if ( ! class_exists( 'Apollo_Global_Assets' ) ) {
			return;
		}

		switch ( $scope ) {
			case 'css':
				Apollo_Global_Assets::enqueue_css();
				break;
			case 'js':
				Apollo_Global_Assets::enqueue_js();
				break;
			default:
				Apollo_Global_Assets::enqueue_all();
				break;
		}
	}
}//end if

if ( ! function_exists( 'apollo_is_using_cdn' ) ) {
	/**
	 * Check if Apollo is using CDN for assets
	 *
	 * @return bool
	 */
	function apollo_is_using_cdn(): bool {
		if ( class_exists( 'Apollo_Global_Assets' ) ) {
			return Apollo_Global_Assets::is_using_cdn();
		}
		return true;
		// Default to CDN
	}
}

if ( ! function_exists( 'apollo_set_use_cdn' ) ) {
	/**
	 * Set whether to use CDN or local assets
	 *
	 * @param bool $use_cdn True for CDN, false for local
	 * @return void
	 */
	function apollo_set_use_cdn( bool $use_cdn ): void {
		if ( class_exists( 'Apollo_Global_Assets' ) ) {
			Apollo_Global_Assets::set_use_cdn( $use_cdn );
		}
	}
}

// ============================================================================
// USER/MEMBERSHIP INTEGRATION
// ============================================================================

if ( ! function_exists( 'apollo_get_user_membership_type' ) ) {
	/**
	 * Get user's membership type
	 *
	 * @param int|null $user_id User ID (default: current user)
	 * @return string Membership type or empty string
	 */
	function apollo_get_user_membership_type( ?int $user_id = null ): string {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		$type = get_user_meta( $user_id, 'apollo_membership_type', true );

		/**
		 * Filter: apollo_user_membership_type
		 * Allows modifying the membership type
		 */
		return (string) apply_filters( 'apollo_user_membership_type', $type, $user_id );
	}
}//end if

if ( ! function_exists( 'apollo_user_can_sign_documents' ) ) {
	/**
	 * Check if user can sign documents (has CPF)
	 *
	 * @param int|null $user_id User ID (default: current user)
	 * @return bool
	 */
	function apollo_user_can_sign_documents( ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$can_sign = get_user_meta( $user_id, 'apollo_can_sign_documents', true );

		/**
		 * Filter: apollo_user_can_sign_documents
		 * Allows modifying the document signing permission
		 */
		return (bool) apply_filters( 'apollo_user_can_sign_documents', $can_sign, $user_id );
	}
}//end if

// ============================================================================
// NOTIFICATION SYSTEM
// ============================================================================

if ( ! function_exists( 'apollo_add_notification' ) ) {
	/**
	 * Add a notification to the Apollo notification system
	 *
	 * @param int    $user_id Target user ID
	 * @param string $type Notification type
	 * @param string $message Notification message
	 * @param array  $data Additional data
	 * @return int|false Notification ID or false on failure
	 */
	function apollo_add_notification( int $user_id, string $type, string $message, array $data = array() ) {
		/**
		 * Filter: apollo_notification_before_create
		 * Allows modifying notification before creation
		 */
		$notification = apply_filters(
			'apollo_notification_before_create',
			array(
				'user_id'    => $user_id,
				'type'       => $type,
				'message'    => $message,
				'data'       => $data,
				'created_at' => current_time( 'mysql' ),
				'read'       => false,
			)
		);

		// Store notification (implementation depends on storage method)
		$notifications = get_user_meta( $user_id, 'apollo_notifications', true );
		if ( ! is_array( $notifications ) ) {
			$notifications = array();
		}

		$notification['id'] = count( $notifications ) + 1;
		$notifications[]    = $notification;

		update_user_meta( $user_id, 'apollo_notifications', $notifications );

		/**
		 * Action: apollo_notification_created
		 * Fired after a notification is created
		 */
		do_action( 'apollo_notification_created', $notification, $user_id );

		return $notification['id'];
	}
}//end if

// ============================================================================
// ECOSYSTEM STATUS
// ============================================================================

if ( ! function_exists( 'apollo_get_ecosystem_status' ) ) {
	/**
	 * Get the status of all Apollo plugins
	 *
	 * @return array Status of each plugin
	 */
	function apollo_get_ecosystem_status(): array {
		return array(
			'core'   => array(
				'active'                          => true,
				// Core is running if this code executes
										'version' => defined( 'APOLLO_CORE_VERSION' ) ? APOLLO_CORE_VERSION : 'unknown',
			),
			'social' => array(
				'active'  => apollo_is_plugin_active( 'social' ),
				'version' => defined( 'APOLLO_SOCIAL_VERSION' ) ? APOLLO_SOCIAL_VERSION : 'unknown',
			),
			'events' => array(
				'active'  => apollo_is_plugin_active( 'events' ),
				'version' => defined( 'APOLLO_WPEM_VERSION' ) ? APOLLO_WPEM_VERSION : 'unknown',
			),
			'rio'    => array(
				'active'  => apollo_is_plugin_active( 'rio' ),
				'version' => defined( 'APOLLO_RIO_VERSION' ) ? APOLLO_RIO_VERSION : 'unknown',
			),
		);
	}
}//end if

// ============================================================================
// SHARED TAXONOMIES REGISTRY (Events ↔ Social)
// ============================================================================

/**
 * Centralized taxonomy registry for cross-plugin access
 */
if ( ! function_exists( 'apollo_get_shared_taxonomies' ) ) {
	/**
	 * Get all shared Apollo taxonomies
	 *
	 * @return array Taxonomy definitions
	 */
	function apollo_get_shared_taxonomies(): array {
		return apply_filters(
			'apollo_shared_taxonomies',
			array(
				// From Events Manager
				'event_sounds'           => array(
					'plugin'     => 'events',
					'post_types' => array( 'event_listing', 'event_dj' ),
					'label'      => __( 'Sons/Gêneros', 'apollo-core' ),
					'rest_base'  => 'event-sounds',
				),
				'event_listing_category' => array(
					'plugin'     => 'events',
					'post_types' => array( 'event_listing' ),
					'label'      => __( 'Categorias de Evento', 'apollo-core' ),
					'rest_base'  => 'event-categories',
				),
				'event_listing_type'     => array(
					'plugin'     => 'events',
					'post_types' => array( 'event_listing' ),
					'label'      => __( 'Tipos de Evento', 'apollo-core' ),
					'rest_base'  => 'event-types',
				),
				'event_listing_tag'      => array(
					'plugin'     => 'events',
					'post_types' => array( 'event_listing' ),
					'label'      => __( 'Tags de Evento', 'apollo-core' ),
					'rest_base'  => 'event-tags',
				),
				// From Social
				'apollo_post_category'   => array(
					'plugin'     => 'social',
					'post_types' => array( 'apollo_social_post' ),
					'label'      => __( 'Categorias de Post', 'apollo-core' ),
					'rest_base'  => 'post-categories',
				),
			)
		);
	}
}//end if

/**
 * ============================================================================
 * SOUNDS SYSTEM CLARIFICATION (AVOID DUPLICITY!)
 * ============================================================================
 *
 * TAXONOMY (for Events/DJs): `event_sounds`
 * - Used for: event_listing, event_dj CPTs
 * - Managed via: wp-admin → Events → Sons
 * - Functions: apollo_get_sounds_taxonomy(), apollo_get_sounds()
 *
 * USER META (for User Preferences): `apollo_sounds`
 * - Used for: User registration preferences
 * - Stores: Array of taxonomy term slugs the user prefers
 * - Functions: apollo_save_user_sounds(), apollo_get_user_sounds()
 *
 * NO DUPLICITY: The taxonomy holds the master list, user meta stores preferences.
 * ============================================================================
 */

if ( ! function_exists( 'apollo_get_sounds_taxonomy' ) ) {
	/**
	 * Get the sounds/genres taxonomy name
	 *
	 * IMPORTANT: This returns the TAXONOMY name (event_sounds), NOT a user meta key.
	 * The user meta key for preferences is 'apollo_sounds' which stores term slugs.
	 *
	 * @return string Taxonomy name: 'event_sounds'
	 */
	function apollo_get_sounds_taxonomy(): string {
		return 'event_sounds';
	}
}

if ( ! function_exists( 'apollo_get_sounds' ) ) {
	/**
	 * Get all available sounds/genres from the event_sounds taxonomy
	 *
	 * This is the MASTER LIST of sounds. Used by:
	 * - Events plugin for event categorization
	 * - Social plugin for user registration preferences
	 *
	 * Users select from this list during registration, stored in 'apollo_sounds' user meta.
	 *
	 * @param bool $hide_empty Hide empty terms
	 * @return array Array of [slug => name]
	 */
	function apollo_get_sounds( bool $hide_empty = false ): array {
		$taxonomy = apollo_get_sounds_taxonomy();

		if ( ! taxonomy_exists( $taxonomy ) ) {
			// Return fallback if events plugin not active
			// This ensures Social plugin can work independently
			return apply_filters(
				'apollo_fallback_sounds',
				array(
					'house'       => 'House',
					'techno'      => 'Techno',
					'trance'      => 'Trance',
					'drum-bass'   => 'Drum & Bass',
					'psytrance'   => 'Psytrance',
					'minimal'     => 'Minimal',
					'progressive' => 'Progressive',
					'tech-house'  => 'Tech House',
					'deep-house'  => 'Deep House',
					'funk'        => 'Funk',
					'disco'       => 'Disco',
					'tribal'      => 'Tribal',
				)
			);
		}//end if

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => $hide_empty,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$sounds = array();
		foreach ( $terms as $term ) {
			$sounds[ $term->slug ] = $term->name;
		}

		return $sounds;
	}
}//end if

if ( ! function_exists( 'apollo_save_user_sounds' ) ) {
	/**
	 * Save user's preferred sounds/genres
	 *
	 * @param int   $user_id User ID
	 * @param array $sounds Array of sound slugs
	 * @return bool Success
	 */
	function apollo_save_user_sounds( int $user_id, array $sounds ): bool {
		$sounds = array_map( 'sanitize_text_field', $sounds );
		return (bool) update_user_meta( $user_id, 'apollo_sounds', $sounds );
	}
}

if ( ! function_exists( 'apollo_get_user_sounds' ) ) {
	/**
	 * Get user's preferred sounds/genres
	 *
	 * @param int $user_id User ID
	 * @return array Array of sound slugs
	 */
	function apollo_get_user_sounds( int $user_id ): array {
		$sounds = get_user_meta( $user_id, 'apollo_sounds', true );
		return is_array( $sounds ) ? $sounds : array();
	}
}

// ============================================================================
// SHARED CPT REGISTRY (Events ↔ Social)
// ============================================================================

if ( ! function_exists( 'apollo_get_shared_cpts' ) ) {
	/**
	 * Get all shared Apollo custom post types
	 *
	 * @return array CPT definitions
	 */
	function apollo_get_shared_cpts(): array {
		return apply_filters(
			'apollo_shared_cpts',
			array(
				// Events Manager CPTs
				'event_listing'      => array(
					'plugin'   => 'events',
					'label'    => __( 'Eventos', 'apollo-core' ),
					'singular' => __( 'Evento', 'apollo-core' ),
					'icon'     => 'dashicons-calendar-alt',
				),
				'event_dj'           => array(
					'plugin'   => 'events',
					'label'    => __( 'DJs', 'apollo-core' ),
					'singular' => __( 'DJ', 'apollo-core' ),
					'icon'     => 'dashicons-admin-users',
				),
				'event_local'        => array(
					'plugin'   => 'events',
					'label'    => __( 'Locais', 'apollo-core' ),
					'singular' => __( 'Local', 'apollo-core' ),
					'icon'     => 'dashicons-location',
				),
				// Social CPTs
				'apollo_social_post' => array(
					'plugin'   => 'social',
					'label'    => __( 'Posts Sociais', 'apollo-core' ),
					'singular' => __( 'Post', 'apollo-core' ),
					'icon'     => 'dashicons-format-status',
				),
				'apollo_home'        => array(
					'plugin'   => 'social',
					'label'    => __( 'Clubber Homes', 'apollo-core' ),
					'singular' => __( 'Clubber Home', 'apollo-core' ),
					'icon'     => 'dashicons-admin-home',
				),
				'apollo_document'    => array(
					'plugin'   => 'social',
					'label'    => __( 'Documentos', 'apollo-core' ),
					'singular' => __( 'Documento', 'apollo-core' ),
					'icon'     => 'dashicons-media-document',
				),
				'user_page'          => array(
					'plugin'   => 'social',
					'label'    => __( 'Páginas de Usuário', 'apollo-core' ),
					'singular' => __( 'Página', 'apollo-core' ),
					'icon'     => 'dashicons-id-alt',
				),
				'cena_rio_doc'       => array(
					'plugin'   => 'social',
					'label'    => __( 'Docs Cena::Rio', 'apollo-core' ),
					'singular' => __( 'Doc', 'apollo-core' ),
					'icon'     => 'dashicons-media-text',
				),
				'cena_rio_plan'      => array(
					'plugin'   => 'social',
					'label'    => __( 'Planos Cena::Rio', 'apollo-core' ),
					'singular' => __( 'Plano', 'apollo-core' ),
					'icon'     => 'dashicons-clipboard',
				),
			)
		);
	}
}//end if

// ============================================================================
// SHARED META KEYS REGISTRY (Events ↔ Social)
// ============================================================================

if ( ! function_exists( 'apollo_get_shared_meta_keys' ) ) {
	/**
	 * Get all shared Apollo meta keys organized by context
	 *
	 * @return array Meta key definitions
	 */
	function apollo_get_shared_meta_keys(): array {
		return apply_filters(
			'apollo_shared_meta_keys',
			array(
				// ==== EVENT META KEYS ====
				'event' => array(
					'_event_start_date'  => array(
						'type'  => 'date',
						'label' => __( 'Data de Início', 'apollo-core' ),
					),
					'_event_end_date'    => array(
						'type'  => 'date',
						'label' => __( 'Data de Fim', 'apollo-core' ),
					),
					'_event_start_time'  => array(
						'type'  => 'time',
						'label' => __( 'Hora de Início', 'apollo-core' ),
					),
					'_event_end_time'    => array(
						'type'  => 'time',
						'label' => __( 'Hora de Fim', 'apollo-core' ),
					),
					'_event_location'    => array(
						'type'  => 'text',
						'label' => __( 'Endereço', 'apollo-core' ),
					),
					'_event_local_id'    => array(
						'type'  => 'post_id',
						'label' => __( 'Local (ID)', 'apollo-core' ),
					),
					'_event_dj_ids'      => array(
						'type'  => 'array',
						'label' => __( 'DJs (IDs)', 'apollo-core' ),
					),
					'_event_lineup'      => array(
						'type'  => 'array',
						'label' => __( 'Line-up', 'apollo-core' ),
					),
					'_event_description' => array(
						'type'  => 'textarea',
						'label' => __( 'Descrição', 'apollo-core' ),
					),
					'_tickets_ext'       => array(
						'type'  => 'url',
						'label' => __( 'Link de Ingressos', 'apollo-core' ),
					),
					'_3_imagens_promo'   => array(
						'type'  => 'array',
						'label' => __( 'Imagens Promo', 'apollo-core' ),
					),
					'_imagem_final'      => array(
						'type'  => 'attachment',
						'label' => __( 'Imagem Final', 'apollo-core' ),
					),
				),
				// ==== DJ META KEYS ====
				'dj'    => array(
					'_dj_image'              => array(
						'type'  => 'attachment',
						'label' => __( 'Avatar', 'apollo-core' ),
					),
					'_dj_banner'             => array(
						'type'  => 'attachment',
						'label' => __( 'Banner', 'apollo-core' ),
					),
					'_dj_website'            => array(
						'type'  => 'url',
						'label' => __( 'Website', 'apollo-core' ),
					),
					'_dj_instagram'          => array(
						'type'  => 'text',
						'label' => __( 'Instagram', 'apollo-core' ),
					),
					'_dj_facebook'           => array(
						'type'  => 'url',
						'label' => __( 'Facebook', 'apollo-core' ),
					),
					'_dj_soundcloud'         => array(
						'type'  => 'url',
						'label' => __( 'SoundCloud', 'apollo-core' ),
					),
					'_dj_spotify'            => array(
						'type'  => 'url',
						'label' => __( 'Spotify', 'apollo-core' ),
					),
					'_dj_youtube'            => array(
						'type'  => 'url',
						'label' => __( 'YouTube', 'apollo-core' ),
					),
					'_dj_mixcloud'           => array(
						'type'  => 'url',
						'label' => __( 'Mixcloud', 'apollo-core' ),
					),
					'_dj_beatport'           => array(
						'type'  => 'url',
						'label' => __( 'Beatport', 'apollo-core' ),
					),
					'_dj_bandcamp'           => array(
						'type'  => 'url',
						'label' => __( 'Bandcamp', 'apollo-core' ),
					),
					'_dj_resident_advisor'   => array(
						'type'  => 'url',
						'label' => __( 'Resident Advisor', 'apollo-core' ),
					),
					'_dj_twitter'            => array(
						'type'  => 'text',
						'label' => __( 'Twitter/X', 'apollo-core' ),
					),
					'_dj_tiktok'             => array(
						'type'  => 'text',
						'label' => __( 'TikTok', 'apollo-core' ),
					),
					'_dj_original_project_1' => array(
						'type'  => 'text',
						'label' => __( 'Projeto 1', 'apollo-core' ),
					),
					'_dj_original_project_2' => array(
						'type'  => 'text',
						'label' => __( 'Projeto 2', 'apollo-core' ),
					),
					'_dj_original_project_3' => array(
						'type'  => 'text',
						'label' => __( 'Projeto 3', 'apollo-core' ),
					),
					'_dj_set_url'            => array(
						'type'  => 'url',
						'label' => __( 'URL do Set', 'apollo-core' ),
					),
					'_dj_media_kit_url'      => array(
						'type'  => 'url',
						'label' => __( 'Media Kit', 'apollo-core' ),
					),
					'_dj_rider_url'          => array(
						'type'  => 'url',
						'label' => __( 'Rider', 'apollo-core' ),
					),
					'_dj_mix_url'            => array(
						'type'  => 'url',
						'label' => __( 'Mix/Playlist', 'apollo-core' ),
					),
				),
				// ==== LOCAL META KEYS ====
				'local' => array(
					'_local_address'   => array(
						'type'  => 'text',
						'label' => __( 'Endereço', 'apollo-core' ),
					),
					'_local_city'      => array(
						'type'  => 'text',
						'label' => __( 'Cidade', 'apollo-core' ),
					),
					'_local_state'     => array(
						'type'  => 'text',
						'label' => __( 'Estado', 'apollo-core' ),
					),
					'_local_zip'       => array(
						'type'  => 'text',
						'label' => __( 'CEP', 'apollo-core' ),
					),
					'_local_lat'       => array(
						'type'  => 'float',
						'label' => __( 'Latitude', 'apollo-core' ),
					),
					'_local_lng'       => array(
						'type'  => 'float',
						'label' => __( 'Longitude', 'apollo-core' ),
					),
					'_local_capacity'  => array(
						'type'  => 'number',
						'label' => __( 'Capacidade', 'apollo-core' ),
					),
					'_local_website'   => array(
						'type'  => 'url',
						'label' => __( 'Website', 'apollo-core' ),
					),
					'_local_instagram' => array(
						'type'  => 'text',
						'label' => __( 'Instagram', 'apollo-core' ),
					),
					'_local_phone'     => array(
						'type'  => 'text',
						'label' => __( 'Telefone', 'apollo-core' ),
					),
				),
				// ==== USER META KEYS (Shared between plugins) ====
				'user'  => array(
					'apollo_cpf'                   => array(
						'type'  => 'text',
						'label' => __( 'CPF', 'apollo-core' ),
					),
					'apollo_passport'              => array(
						'type'  => 'text',
						'label' => __( 'Passaporte', 'apollo-core' ),
					),
					'apollo_passport_country'      => array(
						'type'  => 'text',
						'label' => __( 'País do Passaporte', 'apollo-core' ),
					),
					'apollo_doc_type'              => array(
						'type'  => 'select',
						'label' => __( 'Tipo de Documento', 'apollo-core' ),
					),
					'apollo_sounds'                => array(
						'type'  => 'array',
						'label' => __( 'Gêneros Preferidos', 'apollo-core' ),
					),
					'apollo_can_sign_documents'    => array(
						'type'  => 'bool',
						'label' => __( 'Pode Assinar Docs', 'apollo-core' ),
					),
					'apollo_membership_type'       => array(
						'type'  => 'text',
						'label' => __( 'Tipo de Membro', 'apollo-core' ),
					),
					'apollo_membership_status'     => array(
						'type'  => 'text',
						'label' => __( 'Status do Membro', 'apollo-core' ),
					),
					'apollo_cultura_identities'    => array(
						'type'  => 'array',
						'label' => __( 'Identidades Culturais', 'apollo-core' ),
					),
					'apollo_cultura_registered_at' => array(
						'type'  => 'datetime',
						'label' => __( 'Data de Registro', 'apollo-core' ),
					),
					'apollo_membership_requested'  => array(
						'type'  => 'array',
						'label' => __( 'Memberships Solicitadas', 'apollo-core' ),
					),
					'apollo_membership_approved'   => array(
						'type'  => 'array',
						'label' => __( 'Memberships Aprovadas', 'apollo-core' ),
					),
					'apollo_notifications'         => array(
						'type'  => 'array',
						'label' => __( 'Notificações', 'apollo-core' ),
					),
				),
				// ==== GROUP META KEYS ====
				'group' => array(
					'_group_description'   => array(
						'type'  => 'textarea',
						'label' => __( 'Descrição', 'apollo-core' ),
					),
					'_group_cover'         => array(
						'type'  => 'attachment',
						'label' => __( 'Capa', 'apollo-core' ),
					),
					'_group_avatar'        => array(
						'type'  => 'attachment',
						'label' => __( 'Avatar', 'apollo-core' ),
					),
					'_group_members_count' => array(
						'type'  => 'number',
						'label' => __( 'Qtd. Membros', 'apollo-core' ),
					),
					'_group_events_count'  => array(
						'type'  => 'number',
						'label' => __( 'Qtd. Eventos', 'apollo-core' ),
					),
					'_group_is_private'    => array(
						'type'  => 'bool',
						'label' => __( 'Privado', 'apollo-core' ),
					),
					'_group_category'      => array(
						'type'  => 'text',
						'label' => __( 'Categoria', 'apollo-core' ),
					),
					'_group_location'      => array(
						'type'  => 'text',
						'label' => __( 'Localização', 'apollo-core' ),
					),
					'_group_members'       => array(
						'type'  => 'array',
						'label' => __( 'Membros', 'apollo-core' ),
					),
					'_group_moderators'    => array(
						'type'  => 'array',
						'label' => __( 'Moderadores', 'apollo-core' ),
					),
					'_group_memberships'   => array(
						'type'  => 'array',
						'label' => __( 'Memberships', 'apollo-core' ),
					),
				),
			)
		);
	}
}//end if

// ============================================================================
// CROSS-PLUGIN DATA ACCESS HELPERS
// ============================================================================

if ( ! function_exists( 'apollo_get_dj_data' ) ) {
	/**
	 * Get DJ data from event_dj CPT
	 * Can be used from Social plugin to access Events data
	 *
	 * @param int $dj_id DJ post ID
	 * @return array DJ data
	 */
	function apollo_get_dj_data( int $dj_id ): array {
		if ( ! post_type_exists( 'event_dj' ) ) {
			return array();
		}

		$post = get_post( $dj_id );
		if ( ! $post || $post->post_type !== 'event_dj' ) {
			return array();
		}

		$meta_keys = apollo_get_shared_meta_keys()['dj'] ?? array();
		$data      = array(
			'id'        => $dj_id,
			'name'      => $post->post_title,
			'bio'       => $post->post_content,
			'slug'      => $post->post_name,
			'permalink' => get_permalink( $dj_id ),
		);

		// Get all DJ meta
		foreach ( array_keys( $meta_keys ) as $key ) {
			$data[ $key ] = get_post_meta( $dj_id, $key, true );
		}

		// Get sounds/genres
		$sounds         = wp_get_post_terms( $dj_id, 'event_sounds', array( 'fields' => 'names' ) );
		$data['sounds'] = is_wp_error( $sounds ) ? array() : $sounds;

		return apply_filters( 'apollo_dj_data', $data, $dj_id );
	}
}//end if

if ( ! function_exists( 'apollo_get_local_data' ) ) {
	/**
	 * Get Local/Venue data from event_local CPT
	 * Can be used from Social plugin to access Events data
	 *
	 * @param int $local_id Local post ID
	 * @return array Local data
	 */
	function apollo_get_local_data( int $local_id ): array {
		if ( ! post_type_exists( 'event_local' ) ) {
			return array();
		}

		$post = get_post( $local_id );
		if ( ! $post || $post->post_type !== 'event_local' ) {
			return array();
		}

		$meta_keys = apollo_get_shared_meta_keys()['local'] ?? array();
		$data      = array(
			'id'          => $local_id,
			'name'        => $post->post_title,
			'description' => $post->post_content,
			'slug'        => $post->post_name,
			'permalink'   => get_permalink( $local_id ),
		);

		// Get all local meta
		foreach ( array_keys( $meta_keys ) as $key ) {
			$data[ $key ] = get_post_meta( $local_id, $key, true );
		}

		return apply_filters( 'apollo_local_data', $data, $local_id );
	}
}//end if

if ( ! function_exists( 'apollo_get_event_data' ) ) {
	/**
	 * Get Event data from event_listing CPT
	 * Can be used from Social plugin to access Events data
	 *
	 * @param int $event_id Event post ID
	 * @return array Event data
	 */
	function apollo_get_event_data( int $event_id ): array {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return array();
		}

		$post = get_post( $event_id );
		if ( ! $post || $post->post_type !== 'event_listing' ) {
			return array();
		}

		$meta_keys = apollo_get_shared_meta_keys()['event'] ?? array();
		$data      = array(
			'id'          => $event_id,
			'title'       => $post->post_title,
			'description' => $post->post_content,
			'slug'        => $post->post_name,
			'permalink'   => get_permalink( $event_id ),
			'author_id'   => $post->post_author,
		);

		// Get all event meta
		foreach ( array_keys( $meta_keys ) as $key ) {
			$data[ $key ] = get_post_meta( $event_id, $key, true );
		}

		// Get taxonomies
		$sounds         = wp_get_post_terms( $event_id, 'event_sounds', array( 'fields' => 'all' ) );
		$data['sounds'] = is_wp_error( $sounds ) ? array() : $sounds;

		$categories         = wp_get_post_terms( $event_id, 'event_listing_category', array( 'fields' => 'all' ) );
		$data['categories'] = is_wp_error( $categories ) ? array() : $categories;

		$types         = wp_get_post_terms( $event_id, 'event_listing_type', array( 'fields' => 'all' ) );
		$data['types'] = is_wp_error( $types ) ? array() : $types;

		$tags         = wp_get_post_terms( $event_id, 'event_listing_tag', array( 'fields' => 'all' ) );
		$data['tags'] = is_wp_error( $tags ) ? array() : $tags;

		return apply_filters( 'apollo_event_data', $data, $event_id );
	}
}//end if

if ( ! function_exists( 'apollo_get_user_profile_data' ) ) {
	/**
	 * Get user profile data (unified from both plugins)
	 *
	 * @param int $user_id User ID
	 * @return array User profile data
	 */
	function apollo_get_user_profile_data( int $user_id ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		$meta_keys = apollo_get_shared_meta_keys()['user'] ?? array();
		$data      = array(
			'id'           => $user_id,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'registered'   => $user->user_registered,
			'roles'        => $user->roles,
		);

		// Get all user meta
		foreach ( array_keys( $meta_keys ) as $key ) {
			$data[ $key ] = get_user_meta( $user_id, $key, true );
		}

		// Get avatar
		$data['avatar_url'] = get_avatar_url( $user_id, array( 'size' => 256 ) );

		return apply_filters( 'apollo_user_profile_data', $data, $user_id );
	}
}//end if

// ============================================================================
// TAXONOMY EXTENSION (Add event_sounds to event_dj CPT)
// ============================================================================

add_action(
	'init',
	function () {
		// Add event_sounds taxonomy to event_dj CPT for DJ genres
		if ( taxonomy_exists( 'event_sounds' ) && post_type_exists( 'event_dj' ) ) {
			register_taxonomy_for_object_type( 'event_sounds', 'event_dj' );
		}
	},
	99
);

// ============================================================================
// CROSS-PLUGIN HOOKS
// ============================================================================

/**
 * Fire when a new user registers with cultural identity
 */
add_action(
	'apollo_membership_approved',
	function ( $user_id, $memberships, $admin_id ) {
		// Notify events plugin about approved DJ/Producer membership
		if ( in_array( 'dj_professional', $memberships ) || in_array( 'dj_amateur', $memberships ) ) {
			do_action( 'apollo_dj_membership_approved', $user_id, $memberships );
		}

		if ( in_array( 'event_producer_professional', $memberships ) || in_array( 'event_producer_active', $memberships ) ) {
			do_action( 'apollo_producer_membership_approved', $user_id, $memberships );
		}
	},
	10,
	3
);

/**
 * Link user to DJ CPT when membership approved
 */
add_action(
	'apollo_dj_membership_approved',
	function ( $user_id, $memberships ) {
		if ( ! post_type_exists( 'event_dj' ) ) {
			return;
		}

		// Check if user already has a DJ profile
		$existing = get_posts(
			array(
				'post_type'      => 'event_dj',
				'meta_key'       => '_dj_user_id',
				'meta_value'     => $user_id,
				'posts_per_page' => 1,
			)
		);

		if ( empty( $existing ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				// Create DJ profile for user
				$dj_id = wp_insert_post(
					array(
						'post_type'   => 'event_dj',
						'post_title'  => $user->display_name,
						'post_status' => 'draft',
						// Draft until user completes profile
																'post_author' => $user_id,
					)
				);

				if ( $dj_id && ! is_wp_error( $dj_id ) ) {
					update_post_meta( $dj_id, '_dj_user_id', $user_id );
					update_user_meta( $user_id, 'apollo_dj_profile_id', $dj_id );

					// Notify user
					apollo_add_notification(
						$user_id,
						'dj_profile_created',
						__( 'Seu perfil de DJ foi criado! Complete suas informações.', 'apollo-core' ),
						array( 'dj_id' => $dj_id )
					);
				}
			}//end if
		}//end if
	},
	10,
	2
);

// Log that integration bridge is loaded
if ( apollo_is_debug_mode() ) {
	error_log( 'Apollo Core Integration Bridge loaded' );
}
