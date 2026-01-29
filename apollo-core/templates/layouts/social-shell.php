<?php
/**
 * Social Layout Shell - Main Wrapper Template
 *
 * Based on: approved templates/apollo-social/social - layout - official (aside navbar botton-responsive-bar).html
 *
 * This is the main layout shell for all apollo-social pages.
 * Structure:
 * - Fixed navbar (top)
 * - Desktop sidebar (aside)
 * - Main content area (slot)
 * - Mobile bottom navigation bar
 *
 * @package ApolloCore\Templates\Layouts
 *
 * Expected $args:
 * @var string $main_content     The main content HTML (injected by render_layout)
 * @var string $page_title       Page title for <title> tag
 * @var array  $user             Current user data (from ViewModel)
 * @var array  $nav_items        Sidebar navigation items
 * @var string $active_section   Current active section slug
 * @var bool   $show_sidebar     Whether to show sidebar (default true)
 * @var array  $notifications    Notification items for dropdown
 * @var array  $apps             App grid items for dropdown
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure Canvas Mode is enabled for this template.
if ( class_exists( 'Apollo_Canvas_Mode' ) && ! Apollo_Canvas_Mode::is_enabled() ) {
	Apollo_Canvas_Mode::enable(
		array(
			'strict'  => true,
			'context' => 'social-layout',
		)
	);
}

// Extract with defaults.
$page_title     = $page_title ?? get_the_title();
$user           = $user ?? array();
$nav_items      = $nav_items ?? array();
$active_section = $active_section ?? '';
$show_sidebar   = $show_sidebar ?? true;
$notifications  = $notifications ?? array();
$apps           = $apps ?? array();
$main_content   = $main_content ?? '';

// Get current user data if not provided.
if ( empty( $user ) && is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	$user         = array(
		'id'           => $current_user->ID,
		'display_name' => $current_user->display_name,
		'avatar_url'   => get_avatar_url( $current_user->ID, array( 'size' => 96 ) ),
		'username'     => $current_user->user_login,
	);
}

// Default navigation items if not provided.
if ( empty( $nav_items ) ) {
	$nav_items = array(
		'navegacao'     => array(
			'label' => __( 'Navegação', 'apollo-core' ),
			'items' => array(
				array(
					'slug'  => 'dashboard',
					'icon'  => 'ri-dashboard-line',
					'label' => __( 'Dashboard', 'apollo-core' ),
					'url'   => home_url( '/dashboard/' ),
				),
				array(
					'slug'  => 'feed',
					'icon'  => 'ri-home-4-line',
					'label' => __( 'Feed', 'apollo-core' ),
					'url'   => home_url( '/feed/' ),
				),
				array(
					'slug'  => 'comunidades',
					'icon'  => 'ri-group-line',
					'label' => __( 'Comunidades', 'apollo-core' ),
					'url'   => home_url( '/comunidades/' ),
				),
			),
		),
		'cenario'       => array(
			'label' => __( 'Cena::rio', 'apollo-core' ),
			'items' => array(
				array(
					'slug'  => 'eventos',
					'icon'  => 'ri-calendar-event-line',
					'label' => __( 'Eventos', 'apollo-core' ),
					'url'   => home_url( '/eventos/' ),
				),
				array(
					'slug'  => 'agenda',
					'icon'  => 'ri-calendar-2-line',
					'label' => __( 'Agenda', 'apollo-core' ),
					'url'   => home_url( '/agenda/' ),
				),
				array(
					'slug'  => 'fornecedores',
					'icon'  => 'ri-store-2-line',
					'label' => __( 'Fornecedores', 'apollo-core' ),
					'url'   => home_url( '/fornecedores/' ),
				),
			),
		),
		'acesso_rapido' => array(
			'label' => __( 'Acesso Rápido', 'apollo-core' ),
			'items' => array(
				array(
					'slug'  => 'anuncios',
					'icon'  => 'ri-megaphone-line',
					'label' => __( 'Anúncios', 'apollo-core' ),
					'url'   => home_url( '/anuncios/' ),
				),
				array(
					'slug'  => 'studio',
					'icon'  => 'ri-palette-line',
					'label' => __( 'Creative Studio', 'apollo-core' ),
					'url'   => home_url( '/studio/' ),
				),
			),
		),
	);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
	<title><?php echo esc_html( $page_title ); ?> - Apollo::rio</title>

	<?php
	// Apollo Design System - CDN with local fallback
	$cdn_base   = 'https://assets.apollo.rio.br/';
	$local_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
		? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
		: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/core/';
	$local_vendor = defined( 'APOLLO_CORE_PLUGIN_URL' )
		? APOLLO_CORE_PLUGIN_URL . 'assets/vendor/'
		: plugin_dir_url( dirname( __DIR__ ) ) . 'assets/vendor/';
	?>
	<!-- Apollo CDN Loader - auto-loads styles/index.css and icon.js -->
	<script src="https://assets.apollo.rio.br/index.min.js"></script>

	<?php wp_head(); ?>

	<style>
		/* CSS Variables - Apollo Design Tokens */
		:root {
			--ap-font-primary: "Roboto", Roboto, system-ui, -apple-system, sans-serif;
			--ap-bg-main: #ffffff;
			--ap-bg-surface: #f8fafc;
			--ap-text-primary: rgba(19, 21, 23, 0.85);
			--ap-text-secondary: rgba(15, 23, 42, 0.88);
			--ap-text-muted: rgba(19, 21, 23, 0.4);
			--ap-orange-500: #FF6925;
			--ap-orange-600: #E55A1E;
			--ap-border: #e2e8f0;
			--nav-height: 70px;
			--sidebar-width: 256px;
			--glass-surface: rgba(255, 255, 255, 0.92);
			--glass-blur: blur(16px);
		}

		body.dark-mode {
			--ap-bg-main: #131517;
			--ap-bg-surface: #1e293b;
			--ap-text-primary: #f8fafc;
			--ap-text-secondary: #e2e8f0;
			--ap-border: #334155;
		}

		*, *::before, *::after {
			box-sizing: border-box;
			-webkit-tap-highlight-color: transparent;
		}

		body {
			font-family: var(--ap-font-primary);
			background-color: var(--ap-bg-main);
			color: var(--ap-text-secondary);
			overflow-y: auto;
			overflow-x: hidden;
			min-height: 100vh;
			margin: 0;
			padding: 0;
		}

		/* Scrollbar */
		::-webkit-scrollbar { width: 6px; }
		::-webkit-scrollbar-track { background: transparent; }
		::-webkit-scrollbar-thumb { background-color: rgba(148, 163, 184, 0.4); border-radius: 999px; }

		/* Layout Grid */
		.apollo-layout-shell {
			display: flex;
			flex-direction: column;
			min-height: 100vh;
		}

		.apollo-layout-body {
			display: flex;
			flex: 1;
			padding-top: var(--nav-height);
		}

		.apollo-main-content {
			flex: 1;
			min-width: 0;
			padding: 1.5rem;
			padding-bottom: calc(80px + env(safe-area-inset-bottom, 20px));
		}

		@media (min-width: 769px) {
			.apollo-main-content {
				margin-left: var(--sidebar-width);
				padding-bottom: 2rem;
			}
		}
	</style>
</head>
<body <?php body_class( 'apollo-social-shell' ); ?>>

<div class="apollo-layout-shell">

	<?php
	// Load navbar partial.
	Apollo_Template_Loader::load_partial(
		'social-navbar',
		array(
			'user'          => $user,
			'notifications' => $notifications,
			'apps'          => $apps,
		)
	);
	?>

	<div class="apollo-layout-body">

		<?php if ( $show_sidebar ) : ?>
			<?php
			// Load sidebar partial.
			Apollo_Template_Loader::load_partial(
				'social-sidebar',
				array(
					'nav_items'      => $nav_items,
					'active_section' => $active_section,
					'user'           => $user,
				)
			);
			?>
		<?php endif; ?>

		<main class="apollo-main-content" role="main">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is pre-rendered HTML
			echo $main_content;
			?>
		</main>

	</div>

	<?php
	// Load mobile bottom bar partial.
	Apollo_Template_Loader::load_partial(
		'social-bottom-bar',
		array(
			'active_section' => $active_section,
		)
	);
	?>

</div>

<?php
// Load dropdown menus partial (notifications, apps).
Apollo_Template_Loader::load_partial(
	'social-dropdowns',
	array(
		'notifications' => $notifications,
		'apps'          => $apps,
	)
);
?>

<?php wp_footer(); ?>

<script src="<?php echo esc_url( $asset_base . 'core/base.js' ); ?>" defer></script>
<script src="<?php echo esc_url( $asset_base . 'core/dark-mode.js' ); ?>" defer></script>
<script src="<?php echo esc_url( $asset_base . 'core/clock.js' ); ?>" defer></script>

</body>
</html>
