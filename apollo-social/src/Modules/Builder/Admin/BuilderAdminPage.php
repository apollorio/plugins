<?php

namespace Apollo\Modules\Builder\Admin;

use Apollo\Modules\Builder\LayoutRepository;

class BuilderAdminPage {

	private LayoutRepository $repository;

	public function __construct( LayoutRepository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ), 35 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
		add_action( 'admin_init', array( $this, 'blockThemeAssetsInAdmin' ), 1 );
	}

	public function registerMenu(): void {
		// Changed to submenu under Apollo Cabin to organize the menu better
		add_submenu_page(
			'apollo-cabin',
			__( 'Apollo Builder', 'apollo-social' ),
			__( '🎨 Builder', 'apollo-social' ),
			'edit_users',
			'apollo-builder',
			array( $this, 'renderPage' )
		);
	}

	/**
	 * Block ALL theme assets in admin builder page
	 * FORCE: Only Apollo plugin assets allowed
	 */
	public function blockThemeAssetsInAdmin(): void {
		// Only on builder page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'apollo-builder' ) {
			return;
		}

		// Remove ALL theme stylesheets
		add_action( 'admin_print_styles', array( $this, 'removeThemeStylesFromAdmin' ), 999 );
		add_action( 'admin_print_scripts', array( $this, 'removeThemeScriptsFromAdmin' ), 999 );

		// Block theme fonts and inline styles
		add_action( 'admin_head', array( $this, 'blockThemeFontsAndStyles' ), 1 );
		add_filter( 'style_loader_tag', array( $this, 'filterThemeStyles' ), 999, 2 );
		add_filter( 'script_loader_tag', array( $this, 'filterThemeScripts' ), 999, 2 );

		// Remove theme's wp_head hooks
		add_action( 'admin_head', array( $this, 'removeThemeHeadHooks' ), 1 );
	}

	/**
	 * Remove theme styles from admin
	 */
	public function removeThemeStylesFromAdmin(): void {
		global $wp_styles;

		if ( ! is_object( $wp_styles ) ) {
			return;
		}

		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();
		$theme_dir       = get_template_directory();
		$child_theme_dir = get_stylesheet_directory();

		// Remove all theme styles
		foreach ( $wp_styles->registered as $handle => $style ) {
			$src       = $style->src ?? '';
			$file_path = '';

			// Check by URL
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
				continue;
			}

			// Check by file path if src is relative
			if ( ! empty( $src ) && ! preg_match( '/^(https?:)?\/\//', $src ) ) {
				$file_path = ABSPATH . ltrim( $src, '/' );
				if ( file_exists( $file_path ) ) {
					$real_path = realpath( $file_path );
					if ( $real_path && ( strpos( $real_path, $theme_dir ) !== false || strpos( $real_path, $child_theme_dir ) !== false ) ) {
						wp_dequeue_style( $handle );
						wp_deregister_style( $handle );
					}
				}
			}
		}

		// Remove common theme style handles
		$theme_handles = array( 'theme-style', 'style', 'main-style', 'theme-css', 'custom-style', 'twentytwentyfive-style' );
		foreach ( $theme_handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * Remove theme scripts from admin
	 */
	public function removeThemeScriptsFromAdmin(): void {
		global $wp_scripts;

		if ( ! is_object( $wp_scripts ) ) {
			return;
		}

		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		foreach ( $wp_scripts->registered as $handle => $script ) {
			$src = $script->src ?? '';
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	/**
	 * Block theme fonts and inline styles
	 */
	public function blockThemeFontsAndStyles(): void {
		// Remove theme font-face declarations
		add_action(
			'admin_print_styles',
			function () {
				global $wp_styles;
				if ( is_object( $wp_styles ) ) {
					foreach ( $wp_styles->registered as $handle => $style ) {
						if ( isset( $style->extra['after'] ) && is_array( $style->extra['after'] ) ) {
							foreach ( $style->extra['after'] as $key => $inline ) {
								// Remove @font-face from theme
								if ( is_string( $inline ) && ( strpos( $inline, '@font-face' ) !== false || strpos( $inline, 'font-family' ) !== false ) ) {
									$theme_uri = get_template_directory_uri();
									if ( strpos( $style->src ?? '', $theme_uri ) !== false ) {
										unset( $wp_styles->registered[ $handle ]->extra['after'][ $key ] );
									}
								}
							}
						}
					}
				}
			},
			999
		);
	}

	/**
	 * Filter theme styles from output
	 */
	public function filterThemeStyles( string $tag, string $handle ): string {
		global $wp_styles;

		if ( ! is_object( $wp_styles ) || ! isset( $wp_styles->registered[ $handle ] ) ) {
			return $tag;
		}

		$style           = $wp_styles->registered[ $handle ];
		$src             = $style->src ?? '';
		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		// Block if from theme
		if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
			return '';
		}

		// Block common theme handles
		$theme_handles = array( 'theme-style', 'style', 'main-style', 'theme-css', 'custom-style', 'twentytwentyfive-style' );
		if ( in_array( $handle, $theme_handles, true ) ) {
			return '';
		}

		return $tag;
	}

	/**
	 * Filter theme scripts from output
	 */
	public function filterThemeScripts( string $tag, string $handle ): string {
		global $wp_scripts;

		if ( ! is_object( $wp_scripts ) || ! isset( $wp_scripts->registered[ $handle ] ) ) {
			return $tag;
		}

		$script          = $wp_scripts->registered[ $handle ];
		$src             = $script->src ?? '';
		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
			return '';
		}

		return $tag;
	}

	/**
	 * Remove theme head hooks
	 */
	public function removeThemeHeadHooks(): void {
		global $wp_filter;
		$theme_slug = get_stylesheet();

		// Remove theme hooks from admin_head
		if ( isset( $wp_filter['admin_head'] ) && is_object( $wp_filter['admin_head'] ) ) {
			$callbacks = $wp_filter['admin_head']->callbacks ?? array();
			foreach ( $callbacks as $priority => $hooks ) {
				foreach ( $hooks as $hook_id => $hook ) {
					$function = $hook['function'] ?? null;
					if ( $function && is_array( $function ) && isset( $function[0] ) ) {
						$class_name = get_class( $function[0] );
						if ( strpos( $class_name, $theme_slug ) !== false || strpos( $class_name, 'TwentyTwentyFive' ) !== false ) {
							remove_action( 'admin_head', $function, $priority );
						}
					}
				}
			}
		}
	}

	public function enqueueAssets( string $hook ): void {
		if ( $hook !== 'apollo-cabin_page_apollo-builder' ) {
			return;
		}

		$pluginFile = APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php';

		// FORCE: Only Apollo assets
		wp_enqueue_style( 'apollo-uni-css' );
		wp_enqueue_style( 'apollo-social-builder' );

		// Builder CSS.
		wp_enqueue_style(
			'apollo-builder-css',
			plugins_url( 'assets/css/apollo-builder.css', $pluginFile ),
			array( 'apollo-uni-css' ),
			APOLLO_SOCIAL_VERSION
		);

		// interactjs - load in head (not footer) so it's available early.
		wp_enqueue_script(
			'interactjs',
			'https://cdn.jsdelivr.net/npm/interactjs@1.10.17/dist/interact.min.js',
			array(),
			'1.10.17',
			false // Head, not footer.
		);

		// Main builder script - this is apollo-builder.js for the editor.
		wp_enqueue_script(
			'apollo-builder-main',
			plugins_url( 'assets/js/apollo-builder.js', $pluginFile ),
			array( 'jquery', 'interactjs' ),
			APOLLO_SOCIAL_VERSION,
			true
		);

		// Enqueue the assets module (backgrounds & stickers).
		wp_enqueue_script( 'apollo-builder-assets' );

		// Localize config for admin builder.
		wp_localize_script(
			'apollo-builder-main',
			'apolloBuilder',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'apollo-social/v1/builder/layout' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'currentUser' => get_current_user_id(),
				'layout'      => $this->repository->getLayout( get_current_user_id() ),
			)
		);
	}

	public function renderPage(): void {
		$currentUser  = wp_get_current_user();
		$templatePath = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/apollo-builder.php';

		// Use the updated template if it exists, otherwise fall back to inline render.
		if ( file_exists( $templatePath ) ) {
			include $templatePath;

			return;
		}

		// Fallback inline render (legacy).
		?>
		<div class="wrap apollo-builder-wrap">
			<header class="apollo-builder-header">
				<h1 class="apollo-builder-title">
					<span class="ri-layout-masonry-fill"></span>
					<?php esc_html_e( 'Apollo Habbo Builder', 'apollo-social' ); ?>
				</h1>
				<div class="apollo-builder-toolbar">
					<button class="apollo-btn primary" id="apollo-builder-save">
						<?php esc_html_e( 'Salvar layout', 'apollo-social' ); ?>
					</button>
					<button class="apollo-btn ghost" id="apollo-builder-export">
						<?php esc_html_e( 'Exportar JSON', 'apollo-social' ); ?>
					</button>
					<label class="apollo-btn ghost">
						<?php esc_html_e( 'Importar JSON', 'apollo-social' ); ?>
						<input type="file" id="apollo-builder-import" accept="application/json" hidden>
					</label>
				</div>
			</header>

			<main class="apollo-builder-main">
				<aside class="apollo-builder-sidebar">
					<h2><?php esc_html_e( 'Widgets', 'apollo-social' ); ?></h2>
					<div class="apollo-widget-library" id="apollo-widget-library">
						<button class="apollo-widget-item" data-widget="apollo_sticky_note">
							<span>📝</span>
							<strong><?php esc_html_e( 'Sticky Note', 'apollo-social' ); ?></strong>
							<small><?php esc_html_e( 'Nota adesiva Tailwind/Shadcn', 'apollo-social' ); ?></small>
						</button>
					</div>
				</aside>

				<section class="apollo-builder-canvas">
					<div class="apollo-stage" id="apollo-builder-stage">
						<p class="apollo-stage-empty">
							<?php esc_html_e( 'Arraste widgets para cá e posicione-os livremente.', 'apollo-social' ); ?>
						</p>
					</div>
				</section>

				<aside class="apollo-builder-inspector">
					<h2><?php esc_html_e( 'Inspector', 'apollo-social' ); ?></h2>
					<div class="apollo-inspector-panel" id="apollo-inspector-panel">
						<p><?php esc_html_e( 'Selecione um widget para editar propriedades.', 'apollo-social' ); ?></p>
					</div>
				</aside>
			</main>
		</div>
		<?php
	}
}
