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

// Security: Must be logged in and have capability
if ( ! is_user_logged_in() || ! current_user_can( APOLLO_BUILDER_CAPABILITY ) ) {
	wp_die( __( 'Access denied', 'apollo-social' ), __( 'Error', 'apollo-social' ), array( 'response' => 403 ) );
}

$user_id = get_current_user_id();
$home    = Apollo_Home_CPT::get_or_create_home( $user_id );

if ( ! $home ) {
	wp_die( __( 'Could not load your home', 'apollo-social' ), __( 'Error', 'apollo-social' ), array( 'response' => 500 ) );
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
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

