<?php
// phpcs:ignoreFile
declare(strict_types=1);
/**
 * Apollo PWA Page Builders - Main Class
 *
 * Template registration inspired by Blank Slate plugin pattern:
 * - Global API for template registration (apollo_rio_add_template)
 * - Robust template loading with theme override support
 * - WordPress version compatibility (accepts only 2 args in filters)
 *
 * @package Apollo_Rio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global template registry (Blank Slate pattern)
 * Stores templates in a filterable array
 */
$GLOBALS['apollo_rio_templates'] = array();

/**
 * Register a PWA page template
 *
 * Inspired by Blank Slate plugin pattern for robust template registration.
 * Use this during plugins_loaded to register templates.
 *
 * @param string $file Template filename (e.g., 'pagx_site.php')
 * @param string $label Display label (e.g., 'Site::rio')
 */
function apollo_rio_add_template( $file, $label ) {
	if ( ! isset( $GLOBALS['apollo_rio_templates'] ) ) {
		$GLOBALS['apollo_rio_templates'] = array();
	}
	$GLOBALS['apollo_rio_templates'][ $file ] = apply_filters( 'apollo_rio_template_label', $label, $file );
}

/**
 * Get all registered PWA templates
 *
 * @return array Array of registered templates [filename => label]
 */
function apollo_rio_get_templates() {
	return apply_filters( 'apollo_rio_templates', isset( $GLOBALS['apollo_rio_templates'] ) ? $GLOBALS['apollo_rio_templates'] : array() );
}

class Apollo_PWA_Page_Builders {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		// WordPress compatibility: accepted_args = 2 (minimum always passed)
		// Blank Slate pattern: accept only 2 args to avoid errors in older WP versions
		add_filter( 'theme_page_templates', array( $this, 'register_templates' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'load_template' ), 999 );
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_pwa_assets' ) );

		// Enqueue manifest.json in <head> for App::rio pages
		add_action( 'wp_head', array( $this, 'enqueue_manifest' ), 1 );

		// Block theme interference completely
		add_action( 'template_redirect', array( $this, 'block_theme_interference' ), 1 );
		add_filter( 'wp_title', array( $this, 'remove_page_title' ), 999 );
		add_filter( 'document_title_parts', array( $this, 'remove_page_title_parts' ), 999 );
	}

	/**
	 * Register PWA page templates in WordPress template selector
	 *
	 * Blank Slate pattern: Accept only 2 args for maximum compatibility.
	 * WordPress may pass 2, 3, or 4 args depending on context/version.
	 *
	 * @param array    $post_templates Existing templates array
	 * @param WP_Theme $wp_theme Current theme object (may be null in some contexts)
	 * @return array Merged templates array
	 */
	public function register_templates( $post_templates, $wp_theme = null ) {
		// Merge Apollo templates with existing templates (Blank Slate pattern)
		$apollo_templates = apollo_rio_get_templates();
		return array_merge( $post_templates, $apollo_templates );
	}

	/**
	 * Load the correct template file when a PWA template is selected
	 *
	 * Blank Slate pattern with robust fallbacks:
	 * 1. Check if assigned template is absolute path (theme override)
	 * 2. Try locate_template() to allow theme to override plugin template
	 * 3. Fallback to plugin templates directory
	 *
	 * @param string $template Current template path
	 * @return string Template path to use
	 */
	public function load_template( $template ) {
		global $post;

		if ( ! $post ) {
			return $template;
		}

		// Get assigned template from post meta (Blank Slate pattern)
		$assigned_template = get_post_meta( $post->ID, '_wp_page_template', true );

		if ( empty( $assigned_template ) ) {
			return $template;
		}

		// Get registered Apollo templates
		$apollo_templates = apollo_rio_get_templates();

		// Check if assigned template is one of our registered templates
		if ( ! array_key_exists( $assigned_template, $apollo_templates ) ) {
			return $template;
		}

		// Step 1: Check if $assigned_template is an absolute path (theme override)
		if ( file_exists( $assigned_template ) && is_file( $assigned_template ) ) {
			return $assigned_template;
		}

		// Step 2: Try locate_template() to allow theme to override plugin template
		// This allows themes to place templates in /apollo-rio/ folder
		$theme_template = locate_template( 'apollo-rio/' . $assigned_template );
		if ( $theme_template ) {
			return $theme_template;
		}

		// Step 3: Fallback to plugin templates directory (Blank Slate pattern)
		$plugin_template = plugin_dir_path( __DIR__ ) . 'templates/' . $assigned_template;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// If none found, return original template
		return $template;
	}

	public function add_body_classes( $classes ) {
		global $post;

		if ( ! $post ) {
			return $classes;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		if ( array_key_exists( $template, $apollo_templates ) ) {
			$classes[] = 'apollo-pwa-template';
			$classes[] = str_replace( '.php', '', $template );

			if ( wp_is_mobile() ) {
				$classes[] = 'is-mobile';
			} else {
				$classes[] = 'is-desktop';
			}
		}

		return $classes;
	}

	/**
	 * Block all theme interference
	 * Only allows CSS/JS/PHP from apollo-social, apollo-events-manager, or apollo-rio
	 */
	public function block_theme_interference() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		if ( ! array_key_exists( $template, $apollo_templates ) ) {
			return; 
			// Not an Apollo canvas page
		}

		// Remove theme-specific actions while preserving WordPress core and Apollo plugins
		// Get current theme
		$theme      = wp_get_theme();
		$theme_name = $theme->get( 'Name' );
		$theme_slug = get_stylesheet();

		// Remove theme actions from wp_head (but keep WordPress core and Apollo plugins)
		global $wp_filter;
		$wp_head_callbacks = array();
		if ( isset( $wp_filter['wp_head'] ) && is_object( $wp_filter['wp_head'] ) ) {
			if ( isset( $wp_filter['wp_head']->callbacks ) && is_array( $wp_filter['wp_head']->callbacks ) ) {
				$wp_head_callbacks = $wp_filter['wp_head']->callbacks;
			}
		}
		if ( ! empty( $wp_head_callbacks ) ) {
			foreach ( $wp_head_callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;
					if ( $function && is_array( $function ) && isset( $function[0] ) ) {
						// Check if $function[0] is an object or string
						if ( is_object( $function[0] ) ) {
							$class_name = get_class( $function[0] );
						} elseif ( is_string( $function[0] ) ) {
							$class_name = $function[0];
						} else {
							continue;
						}
						// Remove if it's from theme (check class name or file path)
						if ( strpos( $class_name, $theme_slug ) !== false ) {
							remove_action( 'wp_head', $function, $priority );
						}
					}
				}
			}
		}//end if

		// Remove theme filters from the_content (but keep WordPress core)
		// Only remove if they modify output significantly
		remove_all_filters( 'the_content' );
		// Re-add WordPress core content filters (include do_shortcode)
		add_filter( 'the_content', 'do_blocks', 9 );
		add_filter( 'the_content', 'wptexturize' );
		add_filter( 'the_content', 'convert_smilies', 20 );
		add_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'shortcode_unautop' );
		add_filter( 'the_content', 'prepend_attachment' );
		add_filter( 'the_content', 'wp_filter_content_tags', 12 );
		add_filter( 'the_content', 'wp_replace_insecure_home_url' );
		add_filter( 'the_content', 'do_shortcode', 11 );

		// Remove theme filters from wp_title
		remove_all_filters( 'wp_title' );

		// Remove theme filters from document_title_parts
		remove_all_filters( 'document_title_parts' );

		// Block theme scripts/styles (keep only Apollo plugins)
		// Use wp_print_styles/wp_print_scripts hooks for late filtering
		add_action( 'wp_print_styles', array( $this, 'filter_enqueued_assets' ), 999 );
		add_action( 'wp_print_scripts', array( $this, 'filter_enqueued_assets' ), 999 );

		// Block theme output buffering
		add_action( 'get_header', array( $this, 'prevent_theme_header' ), 1 );
		add_action( 'get_footer', array( $this, 'prevent_theme_footer' ), 1 );
		add_action( 'get_sidebar', array( $this, 'prevent_theme_sidebar' ), 1 );

		// Remove specific theme hooks that might interfere
		remove_all_actions( 'trx_addons_action_before_header' );
		remove_all_actions( 'trx_addons_action_header' );
		remove_all_actions( 'trx_addons_action_after_header' );
		remove_all_actions( 'trx_addons_action_before_page_header' );
		remove_all_actions( 'trx_addons_action_page_header' );
		remove_all_actions( 'trx_addons_action_after_page_header' );
	}

	/**
	 * Filter enqueued assets - only allow Apollo plugins
	 * Runs late to catch all enqueued assets
	 */
	public function filter_enqueued_assets() {
		global $wp_styles, $wp_scripts;

		// List of allowed plugin handles (Apollo plugins only)
		$allowed_handles = array(
			'apollo-uni-css',
			'apollo-pwa-templates',
			'apollo-pwa-detect',
			'apollo-social',
			'apollo-events',
			'apollo-rio',
			'remixicon',
		);

		// Filter styles - check if wp_styles is initialized
		if ( isset( $wp_styles ) && is_object( $wp_styles ) && isset( $wp_styles->queue ) ) {
			$handles_to_remove = array();

			foreach ( $wp_styles->queue as $handle ) {
				if ( ! isset( $wp_styles->registered[ $handle ] ) ) {
					continue;
				}

				$src = '';
				if ( isset( $wp_styles->registered[ $handle ] ) && is_object( $wp_styles->registered[ $handle ] ) ) {
					$src = $wp_styles->registered[ $handle ]->src ?? '';
				}
				$is_apollo = false;

				// Check if handle starts with apollo-
				if ( strpos( $handle, 'apollo-' ) === 0 ) {
					$is_apollo = true;
				}

				// Check if src contains apollo plugin paths
				if ( strpos( $src, '/apollo-' ) !== false ||
					strpos( $src, 'assets.apollo.rio.br' ) !== false ||
					strpos( $src, 'remixicon' ) !== false ) {
					$is_apollo = true;
				}

				// Check if in allowed list
				if ( in_array( $handle, $allowed_handles ) ) {
					$is_apollo = true;
				}

				// Mark for removal if not Apollo
				if ( ! $is_apollo ) {
					$handles_to_remove[] = $handle;
				}
			}//end foreach

			// Remove non-Apollo styles
			foreach ( $handles_to_remove as $handle ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}//end if

		// Filter scripts - check if wp_scripts is initialized
		if ( isset( $wp_scripts ) && is_object( $wp_scripts ) && isset( $wp_scripts->queue ) ) {
			$handles_to_remove = array();

			foreach ( $wp_scripts->queue as $handle ) {
				if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
					continue;
				}

				$src = '';
				if ( isset( $wp_scripts->registered[ $handle ] ) && is_object( $wp_scripts->registered[ $handle ] ) ) {
					$src = $wp_scripts->registered[ $handle ]->src ?? '';
				}
				$is_apollo = false;

				// Check if handle starts with apollo-
				if ( strpos( $handle, 'apollo-' ) === 0 ) {
					$is_apollo = true;
				}

				// Check if src contains apollo plugin paths
				if ( strpos( $src, '/apollo-' ) !== false ||
					strpos( $src, 'assets.apollo.rio.br' ) !== false ) {
					$is_apollo = true;
				}

				// Check if in allowed list
				if ( in_array( $handle, $allowed_handles ) ) {
					$is_apollo = true;
				}

				// Mark for removal if not Apollo
				if ( ! $is_apollo ) {
					$handles_to_remove[] = $handle;
				}
			}//end foreach

			// Remove non-Apollo scripts
			foreach ( $handles_to_remove as $handle ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}//end if
	}

	/**
	 * Prevent theme header from loading
	 */
	public function prevent_theme_header( $name = null ) {
		return false; 
		// Return false to prevent theme header
	}

	/**
	 * Prevent theme footer from loading
	 */
	public function prevent_theme_footer( $name = null ) {
		return false; 
		// Return false to prevent theme footer
	}

	/**
	 * Prevent theme sidebar from loading
	 */
	public function prevent_theme_sidebar( $name = null ) {
		return false; 
		// Return false to prevent theme sidebar
	}

	/**
	 * Remove page title from wp_title
	 */
	public function remove_page_title( $title ) {
		global $post;

		if ( ! $post ) {
			return $title;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		if ( array_key_exists( $template, $apollo_templates ) ) {
			return ''; 
			// Remove title completely
		}

		return $title;
	}

	/**
	 * Remove page title from document_title_parts
	 */
	public function remove_page_title_parts( $parts ) {
		global $post;

		if ( ! $post ) {
			return $parts;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		if ( array_key_exists( $template, $apollo_templates ) ) {
			// Keep only site name, remove page title
			return array( 'title' => get_bloginfo( 'name' ) );
		}

		return $parts;
	}

	/**
	 * Enqueue manifest.json link in <head> for App::rio pages
	 */
	public function enqueue_manifest() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		// Only enqueue manifest for App::rio pages (pagx_app, pagx_appclean)
		if ( array_key_exists( $template, $apollo_templates ) && in_array( $template, array( 'pagx_app.php', 'pagx_appclean.php' ) ) ) {
			$manifest_url = plugins_url( 'manifest.json', __DIR__ );
			echo '<link rel="manifest" href="' . esc_url( $manifest_url ) . '">' . "\n";
		}
	}

	public function enqueue_pwa_assets() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$template         = get_post_meta( $post->ID, '_wp_page_template', true );
		$apollo_templates = apollo_rio_get_templates();

		// Skip enqueuing for pwa-redirector - it outputs its own complete HTML.
		if ( 'pwa-redirector.php' === $template ) {
			return;
		}

		if ( array_key_exists( $template, $apollo_templates ) ) {
			// Global Apollo CSS - required for ALL canvas pages
			wp_enqueue_style(
				'apollo-uni-css',
				'https://assets.apollo.rio.br/uni.css',
				array(), 
				// No dependencies - loads FIRST
				'2.0.0' 
				// Version for cache busting
			);

			wp_enqueue_script(
				'apollo-pwa-detect',
				plugins_url( 'assets/js/pwa-detect.js', __DIR__ ),
				array(),
				defined( 'APOLLO_VERSION' ) ? APOLLO_VERSION : '1.0.0',
				true
			);

			wp_enqueue_style(
				'apollo-pwa-templates',
				plugins_url( 'assets/css/pwa-templates.css', __DIR__ ),
				array( 'apollo-uni-css' ), 
				// Depend on global CSS
				defined( 'APOLLO_VERSION' ) ? APOLLO_VERSION : '1.0.0'
			);

			wp_localize_script(
				'apollo-pwa-detect',
				'apolloPWA',
				array(
					'template'      => $template,
					'isMobile'      => wp_is_mobile(),
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'apollo_pwa_' . $template ),
					'androidAppUrl' => get_option( 'apollo_android_app_url', '#' ),
				)
			);
		}//end if
	}
}

/**
 * Register Apollo PWA templates during init hook
 *
 * IMPORTANT: Translations must be loaded at 'init' or later (WP 6.7+).
 * Moving from plugins_loaded to init fixes the translation timing notice.
 *
 * @since 1.0.1 Moved from plugins_loaded to init for WP 6.7+ compatibility
 */
function apollo_rio_register_templates(): void {
	apollo_rio_add_template( 'pagx_site.php', __( 'Site::rio', 'apollo-rio' ) );
	apollo_rio_add_template( 'pagx_app.php', __( 'App::rio', 'apollo-rio' ) );
	apollo_rio_add_template( 'pagx_appclean.php', __( 'App::rio clean', 'apollo-rio' ) );
	apollo_rio_add_template( 'pwa-redirector.php', __( 'PWA Redirector', 'apollo-rio' ) );
}
add_action( 'init', 'apollo_rio_register_templates', 1 ); 
// Priority 1: early in init, after translations loaded

/**
 * Initialize Apollo PWA Page Builders
 */
function apollo_pwa_page_builders() {
	return Apollo_PWA_Page_Builders::get_instance();
}
add_action( 'plugins_loaded', 'apollo_pwa_page_builders', 10 ); 
// Priority 10: after template registration
