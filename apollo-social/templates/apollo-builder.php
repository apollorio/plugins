<?php
/**
 * Template: Apollo Builder
 *
 * Full-screen builder interface for editing apollo_home posts.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

// Suppress deprecation warnings for this admin page
error_reporting( E_ALL & ~E_USER_DEPRECATED );

// Security: Must be logged in and have capability
if ( ! is_user_logged_in() || ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
	wp_die( __( 'Access denied', 'apollo-social' ), __( 'Error', 'apollo-social' ), array( 'response' => 403 ) );
}

/**
 * Block ALL theme assets in builder template
 */
function apollo_builder_block_theme_assets() {
	global $wp_styles, $wp_scripts;

	$theme_uri       = get_template_directory_uri();
	$child_theme_uri = get_stylesheet_directory_uri();
	$theme_dir       = get_template_directory();
	$child_theme_dir = get_stylesheet_directory();

	// Remove ALL theme styles
	if ( is_object( $wp_styles ) ) {
		foreach ( $wp_styles->registered as $handle => $style ) {
			$src = $style->src ?? '';
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
		// Remove common theme handles
		$theme_handles = array( 'theme-style', 'style', 'main-style', 'theme-css', 'custom-style', 'twentytwentyfive-style' );
		foreach ( $theme_handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	// Remove ALL theme scripts
	if ( is_object( $wp_scripts ) ) {
		foreach ( $wp_scripts->registered as $handle => $script ) {
			$src = $script->src ?? '';
			if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	// Add blocker style to override theme fonts
	add_action(
		'wp_head',
		function () {
			echo '<style id="apollo-builder-theme-blocker">';
			echo '/* FORCE: Block ALL theme fonts and styles */';
			echo 'body.apollo-builder-page { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important; }';
			echo 'body.apollo-builder-page * { font-family: inherit !important; }';
			echo '/* Override any theme font-face */';
			echo '@font-face { font-display: none !important; font-family: "Literata", "Manrope", "Fira Sans", "Fira Code", "Beiruti", "Vollkorn", "Platypi", "Ysabeau Office", "Roboto Slab" !important; }';
			echo '</style>';
		},
		999
	);
}

// Call immediately
apollo_builder_block_theme_assets();

$user_id = get_current_user_id();
$home    = Apollo_Home_CPT::get_or_create_home( $user_id );

if ( ! $home ) {
	wp_die( __( 'Could not load your home', 'apollo-social' ), __( 'Error', 'apollo-social' ), array( 'response' => 500 ) );
}

// Debug
error_log( 'Apollo Builder: Home ID = ' . $home->ID );
error_log( 'Apollo Builder: Home object = ' . print_r( $home, true ) );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="robots" content="noindex, nofollow">
	<?php
	// FORCE: Block ALL theme assets before wp_head (already done above)

	wp_enqueue_emoji_styles();
	wp_enqueue_admin_bar_header_styles();
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_admin_bar_header' );

	// FORCE: Intercept wp_head() completely to remove ALL theme fonts
	ob_start();
	wp_head();
	$head_output = ob_get_clean();

	// FORCE: Remove ALL theme fonts and styles with aggressive regex (multiline)
	// Remove wp-fonts-local style tags (multiline match)
	$head_output = preg_replace( '/<style[^>]*class=["\']wp-fonts-local["\'][^>]*>[\s\S]*?<\/style>/i', '', $head_output );

	// Remove all @font-face declarations for theme fonts (multiline, greedy)
	$theme_fonts_pattern = '(Literata|Manrope|Fira\s+Sans|Fira\s+Code|Beiruti|Vollkorn|Platypi|Ysabeau\s+Office|Roboto\s+Slab)';
	$head_output         = preg_replace( '/@font-face\s*\{[\s\S]*?font-family:\s*["\']?' . $theme_fonts_pattern . '["\']?[\s\S]*?\}/i', '', $head_output );

	// Remove entire style blocks containing theme fonts (multiline)
	$head_output = preg_replace( '/<style[^>]*>[\s\S]*?font-family:\s*["\']?' . $theme_fonts_pattern . '["\']?[\s\S]*?<\/style>/i', '', $head_output );

	// Remove any remaining font-face with theme fonts
	$head_output = preg_replace( '/@font-face\s*\{[\s\S]{0,500}?' . $theme_fonts_pattern . '[\s\S]{0,500}?\}/i', '', $head_output );

	// Add FORCE override style at the END of head
	$head_output .= '<style id="apollo-builder-force-reset">';
	$head_output .= '/* FORCE: Override ALL theme fonts - Apollo Builder Only */';
	$head_output .= 'body.apollo-builder-page, body.apollo-builder-page * { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; }';
	$head_output .= '/* Block theme font loading */';
	$head_output .= '@font-face { font-display: none !important; }';
	$head_output .= '</style>';

	echo $head_output;

	// Enqueue builder assets
	wp_enqueue_style( 'apollo-uni-css' );
	wp_enqueue_style( 'apollo-social-builder' );
	wp_enqueue_style(
		'apollo-builder-css',
		plugins_url( 'assets/css/apollo-builder.css', APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php' ),
		array( 'apollo-uni-css' ),
		APOLLO_SOCIAL_VERSION
	);

	// CRITICAL: Load interactjs BEFORE our scripts, in HEAD (not footer).
	wp_enqueue_script(
		'interactjs',
		'https://cdn.jsdelivr.net/npm/interactjs@1.10.17/dist/interact.min.js',
		array(),
		'1.10.17',
		false // Load in head, not footer.
	);

	// Main builder script - load in footer after DOM ready.
	wp_enqueue_script(
		'apollo-builder-main',
		plugins_url( 'assets/js/apollo-builder.js', APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php' ),
		array( 'jquery', 'interactjs' ),
		APOLLO_SOCIAL_VERSION,
		true
	);

	// NOTE: Do NOT double-enqueue runtime - it conflicts with apollo-builder-main.
	// The apollo-builder.js is the main file; builder.js (runtime) is for frontend only.
	// wp_enqueue_script('apollo-social-builder-runtime'); // DISABLED - conflicts.
	wp_enqueue_script( 'apollo-builder-assets' );

	// Get stickers and backgrounds for config.
	$stickers       = get_option( 'apollo_builder_stickers', array() );
	$textures       = get_option( 'apollo_builder_textures', array() );
	$current_bg     = get_post_meta( $home->ID, '_apollo_background', true );
	$current_trax   = get_post_meta( $home->ID, '_apollo_trax_url', true );
	$current_layout = Apollo_Home_CPT::get_layout( $home->ID );
	?>
	<script>
		// CRITICAL: Set config BEFORE scripts load.
		window.apolloBuilderConfig = {
			ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( wp_create_nonce( 'apollo_builder_save' ) ); ?>',
			restNonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
			restUrl: '<?php echo esc_url( rest_url( 'apollo-social/v1/builder' ) ); ?>',
			homePostId: <?php echo absint( $home->ID ); ?>,
			currentUser: <?php echo absint( get_current_user_id() ); ?>,
			currentLayout: <?php echo wp_json_encode( $current_layout ?: array( 'widgets' => array() ) ); ?>,
			currentBg: <?php echo wp_json_encode( $current_bg ?: '' ); ?>,
			currentTrax: <?php echo wp_json_encode( $current_trax ?: '' ); ?>,
			stickers: <?php echo wp_json_encode( $stickers ?: array() ); ?>,
			textures: <?php echo wp_json_encode( $textures ?: array() ); ?>,
			gridSize: 24,
			i18n: {
				profileCard: '<?php echo esc_js( __( 'Profile Card', 'apollo-social' ) ); ?>',
				badges: '<?php echo esc_js( __( 'Badges', 'apollo-social' ) ); ?>',
				groups: '<?php echo esc_js( __( 'Groups', 'apollo-social' ) ); ?>',
				guestbook: '<?php echo esc_js( __( 'Depoimentos', 'apollo-social' ) ); ?>',
				traxPlayer: '<?php echo esc_js( __( 'Trax Player', 'apollo-social' ) ); ?>',
				note: '<?php echo esc_js( __( 'Sticky Note', 'apollo-social' ) ); ?>',
				sticker: '<?php echo esc_js( __( 'Sticker', 'apollo-social' ) ); ?>',
				saving: '<?php echo esc_js( __( 'Saving...', 'apollo-social' ) ); ?>',
				saved: '<?php echo esc_js( __( 'Saved!', 'apollo-social' ) ); ?>',
				error: '<?php echo esc_js( __( 'Error', 'apollo-social' ) ); ?>',
				confirm_delete: '<?php echo esc_js( __( 'Remove this widget?', 'apollo-social' ) ); ?>',
				empty: '<?php echo esc_js( __( 'Drag widgets here', 'apollo-social' ) ); ?>',
				delete: '<?php echo esc_js( __( 'Remove', 'apollo-social' ) ); ?>'
			}
		};
		console.log('[Apollo Builder] Config loaded:', window.apolloBuilderConfig);
	</script>
	<title><?php printf( __( 'Editing: %s - Apollo Builder', 'apollo-social' ), esc_html( $home->post_title ) ); ?></title>
</head>
<body <?php body_class( 'apollo-builder-page no-scroll' ); ?>>

	<div id="apollo-builder-root"
		data-home-id="<?php echo absint( $home->ID ); ?>"
		data-user-id="<?php echo absint( $user_id ); ?>">

		<!-- Top Bar -->
		<header class="apollo-builder-topbar">
			<div class="topbar-left">
				<a href="<?php echo esc_url( home_url() ); ?>" class="builder-logo" title="<?php esc_attr_e( 'Apollo', 'apollo-social' ); ?>">
					<span class="dashicons dashicons-admin-home"></span>
					<span class="logo-text">Apollo</span>
				</a>
				<span class="builder-title"><?php _e( 'Builder', 'apollo-social' ); ?></span>
			</div>

			<div class="topbar-center">
				<span class="editing-label"><?php _e( 'Editing:', 'apollo-social' ); ?></span>
				<span class="home-title"><?php echo esc_html( $home->post_title ); ?></span>
			</div>

			<div class="topbar-right">
				<button type="button" class="btn-undo" title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'apollo-social' ); ?>" disabled>
					<span class="dashicons dashicons-undo"></span>
				</button>
				<button type="button" class="btn-redo" title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'apollo-social' ); ?>" disabled>
					<span class="dashicons dashicons-redo"></span>
				</button>

				<span class="topbar-divider"></span>

				<a href="<?php echo esc_url( get_permalink( $home ) ); ?>" class="btn-preview" target="_blank">
					<span class="dashicons dashicons-visibility"></span>
					<span class="btn-text"><?php _e( 'Preview', 'apollo-social' ); ?></span>
				</a>

				<button type="button" class="btn-save btn-primary" id="save-layout">
					<span class="dashicons dashicons-saved"></span>
					<span class="btn-text"><?php _e( 'Save', 'apollo-social' ); ?></span>
				</button>
			</div>
		</header>

		<!-- Main Container -->
		<div class="apollo-builder-main">

			<!-- Left Sidebar: Widgets -->
			<aside class="apollo-builder-sidebar sidebar-left">
				<div class="sidebar-header">
					<h3><?php _e( 'Widgets', 'apollo-social' ); ?></h3>
				</div>

				<div class="sidebar-content">
					<div class="widgets-palette" id="widgets-palette">
						<!-- Widgets will be populated by JS -->
						<p class="loading"><?php _e( 'Loading widgets...', 'apollo-social' ); ?></p>
					</div>
				</div>
			</aside>

			<!-- Canvas -->
			<main class="apollo-builder-canvas-wrapper">
				<div class="canvas-container" id="canvas-container">
					<div class="canvas-board" id="canvas-board">
						<?php
						// Background layer for animated backgrounds.
						?>
						<div class="canvas-background-layer" id="canvas-background-layer"></div>

						<?php
						// Widgets layer.
						?>
						<div class="canvas-widgets-layer" id="canvas-widgets-layer">
							<!-- Widgets will be rendered here -->
						</div>

						<?php
						// Stickers layer (on top).
						?>
						<div class="canvas-stickers-layer" id="canvas-stickers-layer">
							<!-- Stickers will be rendered here -->
						</div>
					</div>
				</div>
			</main>

			<!-- Right Sidebar: Settings -->
			<aside class="apollo-builder-sidebar sidebar-right" id="settings-panel">
				<div class="sidebar-header">
					<h3><?php esc_html_e( 'Configurações', 'apollo-social' ); ?></h3>
				</div>

				<div class="sidebar-content">
					<!-- Background Section -->
					<div class="settings-section" id="section-background">
						<h4>
							<span class="dashicons dashicons-format-image"></span>
							<?php esc_html_e( 'Background', 'apollo-social' ); ?>
						</h4>
						<p class="section-description">
							<?php esc_html_e( 'Escolha um fundo para sua página.', 'apollo-social' ); ?>
						</p>
						<button type="button"
								class="btn-secondary btn-block"
								id="open-background-modal"
								aria-haspopup="dialog">
							<span class="dashicons dashicons-art"></span>
							<?php esc_html_e( 'Escolher Background', 'apollo-social' ); ?>
						</button>
						<div class="current-background-preview" id="current-background-preview">
							<!-- Current background preview will be rendered here -->
						</div>
					</div>

					<!-- Stickers Section -->
					<div class="settings-section" id="section-stickers">
						<h4>
							<span class="dashicons dashicons-smiley"></span>
							<?php esc_html_e( 'Stickers', 'apollo-social' ); ?>
						</h4>
						<p class="section-description">
							<?php esc_html_e( 'Arraste stickers para o canvas.', 'apollo-social' ); ?>
						</p>

						<!-- Sticker category tabs -->
						<div class="stickers-category-tabs" id="stickers-category-tabs" role="tablist">
							<!-- Categories will be populated by JS -->
						</div>

						<!-- Stickers palette grid -->
						<div class="stickers-palette" id="stickers-palette" role="tabpanel">
							<p class="loading"><?php esc_html_e( 'Carregando stickers...', 'apollo-social' ); ?></p>
						</div>
					</div>

					<!-- Music Section -->
					<div class="settings-section" id="section-music">
						<h4>
							<span class="dashicons dashicons-format-audio"></span>
							<?php esc_html_e( 'Música', 'apollo-social' ); ?>
						</h4>
						<input type="url"
								id="trax-url-input"
								placeholder="<?php esc_attr_e( 'SoundCloud ou Spotify URL', 'apollo-social' ); ?>"
								class="trax-input">
						<button type="button" class="btn-secondary btn-small" id="save-trax">
							<?php esc_html_e( 'Atualizar', 'apollo-social' ); ?>
						</button>
					</div>

					<!-- Widget Settings (shown when widget selected) -->
					<div class="settings-section" id="section-widget" style="display:none;">
						<h4>
							<span class="dashicons dashicons-admin-settings"></span>
							<span class="widget-title"><?php esc_html_e( 'Widget', 'apollo-social' ); ?></span>
						</h4>
						<div class="widget-settings-form" id="widget-settings-form">
							<!-- Widget settings will be populated by JS -->
						</div>
					</div>
				</div>
			</aside>

		</div>

	</div>

	<!-- Templates for JS -->
	<script type="text/template" id="tpl-widget-palette-item">
		<div class="widget-palette-item" data-widget-type="{{name}}" draggable="true" title="{{tooltip}}">
			<span class="widget-icon {{icon}}"></span>
			<span class="widget-label">{{title}}</span>
		</div>
	</script>

	<script type="text/template" id="tpl-canvas-widget">
		<div class="canvas-widget"
			data-widget-id="{{id}}"
			data-widget-type="{{type}}"
			style="left:{{x}}px;top:{{y}}px;width:{{width}}px;height:{{height}}px;z-index:{{zIndex}};">
			<div class="widget-header">
				<span class="widget-title">{{title}}</span>
				<button type="button" class="widget-delete" title="<?php esc_attr_e( 'Remove', 'apollo-social' ); ?>" {{#unless canDelete}}disabled{{/unless}}>
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
			<div class="widget-content">
				{{content}}
			</div>
			<div class="widget-resize-handle"></div>
		</div>
	</script>

	<!-- Template: Sticker Palette Item -->
	<script type="text/template" id="tpl-sticker-palette-item">
		<div class="sticker-palette-item"
			data-sticker-id="{{id}}"
			draggable="true"
			title="{{label}}">
			<img src="{{preview_url}}"
				alt="{{label}}"
				class="sticker-preview-img"
				loading="lazy" />
			{{#if is_limited}}
			<span class="sticker-badge sticker-badge--limited"><?php esc_html_e( 'Limitado', 'apollo-social' ); ?></span>
			{{/if}}
		</div>
	</script>

	<!-- Template: Canvas Sticker -->
	<script type="text/template" id="tpl-canvas-sticker">
		<div class="canvas-sticker"
			data-sticker-id="{{asset}}"
			data-instance-id="{{id}}"
			style="transform: translate({{x}}px, {{y}}px) scale({{scale}}) rotate({{rotation}}deg); z-index: {{z_index}};">
			<img src="{{image_url}}"
				alt="{{label}}"
				width="{{width}}"
				height="{{height}}"
				draggable="false" />
			<button type="button"
					class="sticker-delete"
					title="<?php esc_attr_e( 'Remover sticker', 'apollo-social' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
	</script>

	<!-- Template: Background Card -->
	<script type="text/template" id="tpl-background-card">
		<div class="background-card {{#if selected}}background-card--selected{{/if}}"
			data-background-id="{{id}}"
			role="option"
			aria-selected="{{selected}}"
			tabindex="0">
			<div class="background-preview" style="{{css_value_preview}}">
				{{#if preview_url}}
				<img src="{{preview_url}}" alt="{{label}}" loading="lazy" />
				{{/if}}
			</div>
			<span class="background-label">{{label}}</span>
			{{#if is_limited}}
			<span class="background-badge background-badge--limited"><?php esc_html_e( 'Limitado', 'apollo-social' ); ?></span>
			{{/if}}
		</div>
	</script>

	<!-- Background Selection Modal -->
	<div id="background-modal"
		class="apollo-modal"
		role="dialog"
		aria-modal="true"
		aria-labelledby="background-modal-title"
		style="display: none;">
		<div class="apollo-modal__backdrop" data-close-modal></div>
		<div class="apollo-modal__container">
			<header class="apollo-modal__header">
				<h2 id="background-modal-title" class="apollo-modal__title">
					<span class="dashicons dashicons-art"></span>
					<?php esc_html_e( 'Escolher Background', 'apollo-social' ); ?>
				</h2>
				<button type="button"
						class="apollo-modal__close"
						data-close-modal
						aria-label="<?php esc_attr_e( 'Fechar', 'apollo-social' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</header>

			<div class="apollo-modal__body">
				<!-- Category Filter Tabs -->
				<nav class="background-category-tabs" id="background-category-tabs" role="tablist">
					<!-- Categories will be populated by JS -->
				</nav>

				<!-- Backgrounds Grid -->
				<div class="backgrounds-grid" id="backgrounds-grid" role="listbox">
					<p class="loading"><?php esc_html_e( 'Carregando backgrounds...', 'apollo-social' ); ?></p>
				</div>
			</div>

			<footer class="apollo-modal__footer">
				<button type="button" class="btn-secondary" data-close-modal>
					<?php esc_html_e( 'Cancelar', 'apollo-social' ); ?>
				</button>
				<button type="button" class="btn-primary" id="apply-background">
					<?php esc_html_e( 'Aplicar', 'apollo-social' ); ?>
				</button>
			</footer>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>

