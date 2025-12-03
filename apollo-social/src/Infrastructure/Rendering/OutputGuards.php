<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Output guards
 *
 * Prevents theme output when Canvas Mode is active.
 */
class OutputGuards {

	private $installed = false;

	/**
	 * Install output guards
	 * FASE 1: Reforçado para garantir isolamento completo do tema
	 */
	public function install() {
		if ( $this->installed ) {
			return;
		}

		// FASE 1: Remover ações do tema que podem interferir
		$this->removeThemeActions();

		// FASE 1: Bloquear assets do tema completamente (método robusto)
		$this->blockThemeAssetsCompletely();

		// FASE 1: Também usar método configurável para compatibilidade
		$this->blockThemeAssets();

		$this->installed = true;
	}

	/**
	 * Remove output guards
	 */
	public function remove() {
		// TODO: Restore theme actions if needed
		$this->installed = false;
	}

	/**
	 * Remove theme actions that add unwanted output
	 */
	private function removeThemeActions() {
		// Get theme slug to identify theme-specific hooks
		$theme_slug = get_stylesheet();

		// Remove theme-specific hooks instead of ALL hooks
		// This preserves hooks from other plugins
		global $wp_filter;

		if ( isset( $wp_filter['wp_head'] ) && is_object( $wp_filter['wp_head'] ) ) {
			$callbacks = $wp_filter['wp_head']->callbacks ?? array();
			foreach ( $callbacks as $priority => $hooks ) {
				foreach ( $hooks as $hook_id => $hook ) {
					$function = $hook['function'] ?? null;
					if ( $function && is_array( $function ) && isset( $function[0] ) ) {
						$class_name = get_class( $function[0] );
						// Remove only theme hooks
						if ( strpos( $class_name, $theme_slug ) !== false ) {
							remove_action( 'wp_head', $function, $priority );
						}
					}
				}
			}
		}

		// Re-add essential WordPress hooks (only if not already present)
		if ( ! has_action( 'wp_head', 'wp_enqueue_scripts' ) ) {
			add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
		}
		if ( ! has_action( 'wp_head', 'wp_print_styles' ) ) {
			add_action( 'wp_head', 'wp_print_styles', 8 );
		}
		if ( ! has_action( 'wp_head', 'wp_print_head_scripts' ) ) {
			add_action( 'wp_head', 'wp_print_head_scripts', 9 );
		}
		if ( ! has_action( 'wp_footer', 'wp_print_footer_scripts' ) ) {
			add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		}

		// Keep admin bar if allowed
		$canvas_config = $this->getCanvasConfig();
		if ( ! empty( $canvas_config['allow_admin_bar'] ) ) {
			if ( ! has_action( 'wp_head', '_admin_bar_bump_cb' ) ) {
				add_action( 'wp_head', '_admin_bar_bump_cb' );
			}
		}
	}

	/**
	 * Block theme assets
	 */
	private function blockThemeAssets() {
		$canvas_config = $this->getCanvasConfig();

		if ( ! empty( $canvas_config['block_theme_css'] ) ) {
			add_action( 'wp_print_styles', array( $this, 'dequeueThemeStyles' ), 100 );
		}

		if ( ! empty( $canvas_config['block_theme_js'] ) ) {
			add_action( 'wp_print_scripts', array( $this, 'dequeueThemeScripts' ), 100 );
			add_action( 'wp_print_footer_scripts', array( $this, 'dequeueThemeScripts' ), 100 );
		}
	}

	/**
	 * Dequeue theme styles
	 */
	public function dequeueThemeStyles() {
		global $wp_styles;

		if ( ! $wp_styles ) {
			return;
		}

		$theme_url       = get_template_directory_uri();
		$child_theme_url = get_stylesheet_directory_uri();

		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( isset( $style->src ) && is_string( $style->src ) ) {
				if ( strpos( $style->src, $theme_url ) !== false ||
					strpos( $style->src, $child_theme_url ) !== false ) {
					wp_dequeue_style( $handle );
				}
			}
		}
	}

	/**
	 * Dequeue theme scripts
	 */
	public function dequeueThemeScripts() {
		global $wp_scripts;

		if ( ! $wp_scripts ) {
			return;
		}

		$theme_url       = get_template_directory_uri();
		$child_theme_url = get_stylesheet_directory_uri();

		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( isset( $script->src ) && is_string( $script->src ) ) {
				if ( strpos( $script->src, $theme_url ) !== false ||
					strpos( $script->src, $child_theme_url ) !== false ) {
					wp_dequeue_script( $handle );
				}
			}
		}
	}

	/**
	 * Enhanced method to completely block theme assets in Canvas Mode
	 */
	public function blockThemeAssetsCompletely(): void {
		// Remove theme styles completely
		add_action( 'wp_print_styles', array( $this, 'removeAllThemeStyles' ), 999 );

		// Remove theme scripts
		add_action( 'wp_print_scripts', array( $this, 'removeAllThemeScripts' ), 999 );

		// Remove theme's header and footer hooks
		$this->removeThemeHooksCompletely();

		// Block theme customizer styles
		remove_action( 'wp_head', 'wp_custom_css_cb', 101 );

		// Block theme mod styles
		remove_action( 'wp_head', 'wp_theme_mod_custom_css', 101 );

		// Override body classes
		add_filter( 'body_class', array( $this, 'overrideBodyClasses' ), 999 );
	}

	/**
	 * Remove all theme stylesheets
	 */
	public function removeAllThemeStyles(): void {
		global $wp_styles;

		if ( ! is_object( $wp_styles ) ) {
			return;
		}

		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		foreach ( $wp_styles->queue as $handle ) {
			if ( isset( $wp_styles->registered[ $handle ] ) ) {
				$src = $wp_styles->registered[ $handle ]->src ?? '';

				// Remove if it's from active theme or child theme
				if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
			}
		}

		// Also remove common theme styles by handle patterns
		$theme_handles = array(
			'theme-style',
			'style',
			'main-style',
			'theme-css',
			'custom-style',
		);

		foreach ( $theme_handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * Remove theme scripts
	 */
	public function removeAllThemeScripts(): void {
		global $wp_scripts;

		if ( ! is_object( $wp_scripts ) ) {
			return;
		}

		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		foreach ( $wp_scripts->queue as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$src = $wp_scripts->registered[ $handle ]->src ?? '';

				// Remove if it's from active theme
				if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				}
			}
		}
	}

	/**
	 * P0-4: Remove theme hooks and actions completely
	 */
	private function removeThemeHooksCompletely(): void {
		// Remove theme setup hooks
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );

		// Remove theme customization hooks that might interfere
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		// P0-4: Remove all theme-specific wp_head hooks
		global $wp_filter;
		$theme_slug = get_stylesheet();

		if ( isset( $wp_filter['wp_head'] ) && is_object( $wp_filter['wp_head'] ) ) {
			$callbacks = $wp_filter['wp_head']->callbacks ?? array();
			foreach ( $callbacks as $priority => $hooks ) {
				foreach ( $hooks as $hook_id => $hook ) {
					$function = $hook['function'] ?? null;
					if ( $function ) {
						// Remove theme-specific hooks
						if ( is_array( $function ) && isset( $function[0] ) ) {
							$class_name = get_class( $function[0] );
							if ( strpos( $class_name, $theme_slug ) !== false ) {
								remove_action( 'wp_head', $function, $priority );
							}
						} elseif ( is_string( $function ) && function_exists( $function ) ) {
							// Check if function is theme-specific by reflection
							try {
								$reflection = new \ReflectionFunction( $function );
								$file       = $reflection->getFileName();
								if ( strpos( $file, get_template_directory() ) !== false ||
									strpos( $file, get_stylesheet_directory() ) !== false ) {
									remove_action( 'wp_head', $function, $priority );
								}
							} catch ( \ReflectionException $e ) {
								// Skip if reflection fails
							}
						}
					}//end if
				}//end foreach
			}//end foreach
		}//end if

		// P0-4: Remove all theme-specific wp_footer hooks
		if ( isset( $wp_filter['wp_footer'] ) && is_object( $wp_filter['wp_footer'] ) ) {
			$callbacks = $wp_filter['wp_footer']->callbacks ?? array();
			foreach ( $callbacks as $priority => $hooks ) {
				foreach ( $hooks as $hook_id => $hook ) {
					$function = $hook['function'] ?? null;
					if ( $function ) {
						if ( is_array( $function ) && isset( $function[0] ) ) {
							$class_name = get_class( $function[0] );
							if ( strpos( $class_name, $theme_slug ) !== false ) {
								remove_action( 'wp_footer', $function, $priority );
							}
						} elseif ( is_string( $function ) && function_exists( $function ) ) {
							try {
								$reflection = new \ReflectionFunction( $function );
								$file       = $reflection->getFileName();
								if ( strpos( $file, get_template_directory() ) !== false ||
									strpos( $file, get_stylesheet_directory() ) !== false ) {
									remove_action( 'wp_footer', $function, $priority );
								}
							} catch ( \ReflectionException $e ) {
								// Skip if reflection fails
							}
						}
					}
				}//end foreach
			}//end foreach
		}//end if
	}

	/**
	 * Override body classes to ensure clean Canvas Mode
	 */
	public function overrideBodyClasses( array $classes ): array {
		// Keep only essential WordPress classes and add Canvas classes
		$essential_classes = array_filter(
			$classes,
			function ( $class ) {
				return in_array(
					$class,
					array(
						'logged-in',
						'admin-bar',
						'wp-admin',
						'wp-core-ui',
					)
				);
			}
		);

		// Add Apollo Canvas classes
		$essential_classes[] = 'apollo-canvas';
		$essential_classes[] = 'apollo-social-canvas';

		return $essential_classes;
	}

	/**
	 * Get Canvas configuration
	 */
	private function getCanvasConfig() {
		static $config = null;

		if ( $config === null ) {
			$config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/canvas.php';
			if ( file_exists( $config_file ) ) {
				$config = require $config_file;
			} else {
				$config = array();
			}
		}

		return $config;
	}
}
