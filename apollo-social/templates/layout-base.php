<?php
/**
 * Template: Base Layout
 *
 * PHASE 5: Migrated to ViewModel Architecture.
 * Matches approved design: social - layout - official (aside navbar botton-responsive-bar).html
 * Uses ViewModel data transformation and shared partials.
 *
 * Z-INDEX STRATEGY:
 * - Navbar/Bottom Nav: z-50 (z-index: 50)
 * - Sidebar: z-40 (z-index: 40)
 * - Sidebar Overlay: z-39 (z-index: 39)
 *
 * RESPONSIVE STRATEGY:
 * - Mobile (< 1024px): Fixed bottom nav, hidden sidebar (slide in)
 * - Desktop (>= 1024px): Fixed left sidebar, no bottom nav
 *
 * @package Apollo_Social
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Detect mobile for server-side optimizations.
$is_mobile = wp_is_mobile();

// Create ViewModel for base layout.
$view_model    = Apollo_ViewModel_Factory::create_from_data( null, 'base_layout' );
$template_data = $view_model->get_base_layout_data();

// Load shared partials.
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );
$template_loader->load_partial( 'header-nav' );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $template_data['title'] ?? get_bloginfo( 'name' ) ); ?></title>

	<!-- Load shared assets. -->
	<?php $template_loader->load_partial( 'assets' ); ?>

	<!-- Layout-specific styles. -->
	<style>
		/* Base layout with sidebar */
		.apollo-base-layout {
			font-family: var(--font-family, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
			line-height: 1.6;
			color: var(--text-primary, #333);
			background: var(--bg-surface, #f5f5f5);
			min-height: 100vh;
		}

		.layout-wrapper {
			display: flex;
			min-height: 100vh;
		}

		/* Sidebar */
		.sidebar {
			width: 280px;
			background: var(--bg-main, #fff);
			border-right: 1px solid var(--border-color, #e0e2e4);
			position: fixed;
			top: 0;
			left: 0;
			height: 100vh;
			overflow-y: auto;
			z-index: 40;
			transform: translateX(0);
			transition: transform 0.3s ease;
		}

		.sidebar.collapsed {
			transform: translateX(-100%);
		}

		.sidebar-header {
			padding: 2rem 1.5rem;
			border-bottom: 1px solid var(--border-color, #e0e2e4);
		}

		.sidebar-brand {
			font-size: 1.5rem;
			font-weight: 700;
			color: var(--primary, #007bff);
			text-decoration: none;
			display: block;
		}

		.sidebar-nav {
			padding: 1rem 0;
		}

		.nav-section {
			margin-bottom: 2rem;
		}

		.nav-section-title {
			padding: 0.5rem 1.5rem;
			font-size: 0.875rem;
			font-weight: 600;
			color: var(--text-secondary, #666);
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}

		.nav-item {
			display: block;
		}

		.nav-link {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 0.75rem 1.5rem;
			color: var(--text-primary, #333);
			text-decoration: none;
			transition: all 0.2s ease;
			border-left: 3px solid transparent;
		}

		.nav-link:hover {
			background: var(--bg-surface, #f5f5f5);
			border-left-color: var(--primary, #007bff);
		}

		.nav-link.active {
			background: var(--primary-light, rgba(0,123,255,0.1));
			border-left-color: var(--primary, #007bff);
			color: var(--primary, #007bff);
		}

		.nav-icon {
			font-size: 1.25rem;
			width: 20px;
			text-align: center;
		}

		/* Main content area */
		.main-content {
			flex: 1;
			margin-left: 280px;
			display: flex;
			flex-direction: column;
			min-height: 100vh;
		}

		.main-content.expanded {
			margin-left: 0;
		}

		/* Top bar */
		.top-bar {
			background: var(--bg-main, #fff);
			border-bottom: 1px solid var(--border-color, #e0e2e4);
			padding: 1rem 2rem;
			display: flex;
			justify-content: space-between;
			align-items: center;
			position: sticky;
			top: 0;
			z-index: 50;
		}

		.menu-toggle {
			background: none;
			border: none;
			font-size: 1.25rem;
			color: var(--text-primary, #333);
			cursor: pointer;
			padding: 0.5rem;
			border-radius: var(--radius-main, 12px);
			transition: all 0.2s ease;
		}

		.menu-toggle:hover {
			background: var(--bg-surface, #f5f5f5);
		}

		.top-bar-title {
			font-size: 1.25rem;
			font-weight: 600;
			color: var(--text-primary, #333);
		}

		.user-menu {
			display: flex;
			align-items: center;
			gap: 1rem;
		}

		.user-avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			object-fit: cover;
		}

		.user-avatar.placeholder {
			background: var(--bg-surface, #f5f5f5);
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--text-secondary, #666);
		}

		/* Content area */
		.content-area {
			flex: 1;
			padding: 2rem;
			max-width: 1200px;
			margin: 0 auto;
			width: 100%;
		}

		.page-header {
			margin-bottom: 2rem;
		}

		.page-title {
			font-size: 2rem;
			font-weight: 700;
			color: var(--text-primary, #333);
			margin-bottom: 0.5rem;
		}

		.page-subtitle {
			color: var(--text-secondary, #666);
			font-size: 1.1rem;
		}

		/* Bottom navigation (mobile) */
		.bottom-nav {
			display: none;
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background: var(--bg-main, #fff);
			border-top: 1px solid var(--border-color, #e0e2e4);
			padding: 0.75rem;
			z-index: 50;
		}

		.bottom-nav-items {
			display: flex;
			justify-content: space-around;
			align-items: center;
		}

		.bottom-nav-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.25rem;
			text-decoration: none;
			color: var(--text-secondary, #666);
			font-size: 0.75rem;
			padding: 0.5rem;
			border-radius: var(--radius-main, 12px);
			transition: all 0.2s ease;
		}

		.bottom-nav-item:hover,
		.bottom-nav-item.active {
			color: var(--primary, #007bff);
			background: var(--primary-light, rgba(0,123,255,0.1));
		}

		.bottom-nav-icon {
			font-size: 1.25rem;
		}

		/* Mobile responsive adjustments */
		@media (max-width: 1024px) {
			.sidebar {
				transform: translateX(-100%);
			}

			.sidebar.open {
				transform: translateX(0);
			}

			.main-content {
				margin-left: 0;
			}

			.bottom-nav {
				display: block;
			}

			.content-area {
				padding: 1rem;
				padding-bottom: 5rem; /* Account for bottom nav */
			}
		}

		@media (max-width: 768px) {
			.top-bar {
				padding: 1rem;
			}

			.top-bar-title {
				font-size: 1.1rem;
			}

			.content-area {
				padding: 1rem 0.5rem;
			}

			.page-title {
				font-size: 1.5rem;
			}
		}

		/* Overlay for mobile sidebar */
		.sidebar-overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0,0,0,0.5);
			z-index: 39;
		}

		.sidebar-overlay.active {
			display: block;
		}

		@media (max-width: 1024px) {
			.sidebar-overlay.active {
				display: block;
			}
		}
	</style>

	<?php wp_head(); ?>
</head>

<body class="apollo-base-layout<?php echo $is_mobile ? ' is-mobile' : ' is-desktop'; ?>">
	<!-- Sidebar overlay for mobile -->
	<div class="sidebar-overlay" id="sidebar-overlay"></div>

	<div class="layout-wrapper">
		<!-- Sidebar (hidden on mobile by default) -->
		<?php if ( ! $is_mobile ) : ?>
		<aside class="sidebar" id="sidebar">
			<div class="sidebar-header">
				<a href="<?php echo esc_url( $template_data['brand_url'] ?? home_url() ); ?>" class="sidebar-brand">
					<?php echo esc_html( $template_data['brand_name'] ?? 'Apollo Social' ); ?>
				</a>
			</div>

			<nav class="sidebar-nav">
				<?php if ( ! empty( $template_data['navigation'] ) ) : ?>
					<?php foreach ( $template_data['navigation'] as $section ) : ?>
						<div class="nav-section">
							<?php if ( $section['title'] ) : ?>
								<div class="nav-section-title"><?php echo esc_html( $section['title'] ); ?></div>
							<?php endif; ?>

							<?php foreach ( $section['items'] as $item ) : ?>
								<div class="nav-item">
									<a href="<?php echo esc_url( $item['url'] ); ?>"
										class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>">
										<i class="<?php echo esc_attr( $item['icon'] ); ?> nav-icon"></i>
										<span><?php echo esc_html( $item['label'] ); ?></span>
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</nav>
		</aside>
		<?php else : ?>
		<!-- Mobile: Sidebar slides in on menu toggle -->
		<aside class="sidebar" id="sidebar" aria-hidden="true">
			<div class="sidebar-header">
				<a href="<?php echo esc_url( $template_data['brand_url'] ?? home_url() ); ?>" class="sidebar-brand">
					<?php echo esc_html( $template_data['brand_name'] ?? 'Apollo Social' ); ?>
				</a>
			</div>

			<nav class="sidebar-nav">
				<?php if ( ! empty( $template_data['navigation'] ) ) : ?>
					<?php foreach ( $template_data['navigation'] as $section ) : ?>
						<div class="nav-section">
							<?php if ( $section['title'] ) : ?>
								<div class="nav-section-title"><?php echo esc_html( $section['title'] ); ?></div>
							<?php endif; ?>

							<?php foreach ( $section['items'] as $item ) : ?>
								<div class="nav-item">
									<a href="<?php echo esc_url( $item['url'] ); ?>"
										class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>">
										<i class="<?php echo esc_attr( $item['icon'] ); ?> nav-icon"></i>
										<span><?php echo esc_html( $item['label'] ); ?></span>
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</nav>
		</aside>
		<?php endif; ?>

		<!-- Main content -->
		<div class="main-content" id="main-content">
			<!-- Top bar -->
			<header class="top-bar">
				<button class="menu-toggle" id="menu-toggle">
					<i class="ri-menu-line"></i>
				</button>

				<div class="top-bar-title">
					<?php echo esc_html( $template_data['page_title'] ?? get_the_title() ); ?>
				</div>

				<div class="user-menu">
					<?php if ( $template_data['current_user'] ) : ?>
						<?php if ( $template_data['current_user']['avatar'] ) : ?>
							<img src="<?php echo esc_url( $template_data['current_user']['avatar'] ); ?>"
								alt="<?php echo esc_attr( $template_data['current_user']['name'] ); ?>"
								class="user-avatar">
						<?php else : ?>
							<div class="user-avatar placeholder">
								<i class="ri-user-line"></i>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</header>

			<!-- Content area -->
			<main class="content-area">
				<?php if ( $template_data['show_page_header'] ) : ?>
					<header class="page-header">
						<h1 class="page-title"><?php echo esc_html( $template_data['page_title'] ?? get_the_title() ); ?></h1>
						<?php if ( $template_data['page_subtitle'] ) : ?>
							<p class="page-subtitle"><?php echo esc_html( $template_data['page_subtitle'] ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<div class="page-content">
					<?php echo wp_kses_post( $template_data['content'] ?? '' ); ?>
				</div>
			</main>
		</div>

		<!-- Bottom navigation (mobile) -->
		<?php if ( ! empty( $template_data['bottom_navigation'] ) ) : ?>
			<nav class="bottom-nav">
				<div class="bottom-nav-items">
					<?php foreach ( $template_data['bottom_navigation'] as $item ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"
							class="bottom-nav-item <?php echo $item['active'] ? 'active' : ''; ?>">
							<i class="<?php echo esc_attr( $item['icon'] ); ?> bottom-nav-icon"></i>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</nav>
		<?php endif; ?>
	</div>

	<script>
		// Mobile sidebar toggle
		document.addEventListener('DOMContentLoaded', function() {
			const menuToggle = document.getElementById('menu-toggle');
			const sidebar = document.getElementById('sidebar');
			const sidebarOverlay = document.getElementById('sidebar-overlay');
			const mainContent = document.getElementById('main-content');

			function toggleSidebar() {
				sidebar.classList.toggle('open');
				sidebarOverlay.classList.toggle('active');
				mainContent.classList.toggle('expanded');
			}

			menuToggle.addEventListener('click', toggleSidebar);
			sidebarOverlay.addEventListener('click', toggleSidebar);

			// Close sidebar on window resize if desktop
			window.addEventListener('resize', function() {
				if (window.innerWidth > 1024) {
					sidebar.classList.remove('open');
					sidebarOverlay.classList.remove('active');
					mainContent.classList.remove('expanded');
				}
			});
		});
	</script>

	<?php wp_footer(); ?>
</body>
</html>
