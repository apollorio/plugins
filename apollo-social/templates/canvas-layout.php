<?php
/**
 * Template: Canvas Layout
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: social - layout - official (aside navbar botton-responsive-bar).html
 * Uses ViewModel data transformation and shared partials
 */

// Create ViewModel for canvas layout
$viewModel     = Apollo_ViewModel_Factory::create_from_data( null, 'canvas_layout' );
$template_data = $viewModel->get_canvas_layout_data();

// Load shared partials
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $template_data['title'] ?? get_bloginfo( 'name' ) ); ?></title>

	<!-- Load shared assets -->
	<?php $template_loader->load_partial( 'assets' ); ?>

	<!-- Additional layout-specific styles -->
	<style>
		/* Canvas layout styles */
		.apollo-canvas {
			font-family: var(--font-family, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
			line-height: 1.6;
			color: var(--text-primary, #333);
			background: var(--bg-main, #fff);
		}

		.apollo-canvas * {
			box-sizing: border-box;
		}

		#apollo-canvas-wrapper {
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		/* Header styles */
		.apollo-header {
			background: var(--bg-surface, #f5f5f5);
			border-bottom: 1px solid var(--border-color, #e0e2e4);
			position: sticky;
			top: 0;
			z-index: 100;
		}

		.apollo-nav {
			max-width: 1200px;
			margin: 0 auto;
			padding: 0 2rem;
			display: flex;
			justify-content: space-between;
			align-items: center;
			height: 60px;
		}

		.apollo-brand a {
			font-size: 1.25rem;
			font-weight: 700;
			color: var(--primary, #007bff);
			text-decoration: none;
		}

		.apollo-menu {
			display: flex;
			list-style: none;
			margin: 0;
			padding: 0;
			gap: 2rem;
		}

		.apollo-menu li a {
			color: var(--text-primary, #333);
			text-decoration: none;
			font-weight: 500;
			transition: color 0.2s ease;
		}

		.apollo-menu li a:hover {
			color: var(--primary, #007bff);
		}

		/* Main content */
		.apollo-content {
			flex: 1;
			max-width: 1200px;
			margin: 0 auto;
			width: 100%;
			padding: 2rem;
		}

		/* Breadcrumbs */
		.apollo-breadcrumbs {
			margin-bottom: 2rem;
			font-size: 0.875rem;
			color: var(--text-secondary, #666);
		}

		.apollo-breadcrumbs span:not(:last-child)::after {
			content: " > ";
			margin: 0 0.5rem;
			color: var(--text-secondary, #666);
		}

		/* Page content */
		.apollo-page-content {
			background: var(--bg-main, #fff);
			border-radius: var(--radius-main, 12px);
			padding: 2rem;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}

		/* Footer */
		.apollo-footer {
			background: var(--bg-surface, #f5f5f5);
			border-top: 1px solid var(--border-color, #e0e2e4);
			padding: 2rem;
			text-align: center;
			color: var(--text-secondary, #666);
			margin-top: auto;
		}

		.apollo-footer p {
			margin: 0;
		}

		/* Mobile responsive adjustments */
		@media (max-width: 768px) {
			.apollo-nav {
				padding: 0 1rem;
				flex-direction: column;
				height: auto;
				padding-top: 1rem;
				padding-bottom: 1rem;
			}

			.apollo-menu {
				flex-direction: column;
				gap: 1rem;
				margin-top: 1rem;
			}

			.apollo-content {
				padding: 1rem;
			}

			.apollo-page-content {
				padding: 1.5rem;
			}

			.apollo-breadcrumbs {
				font-size: 0.75rem;
			}
		}

		@media (max-width: 480px) {
			.apollo-nav {
				padding: 0 0.5rem;
			}

			.apollo-menu {
				gap: 0.75rem;
			}

			.apollo-content {
				padding: 0.5rem;
			}

			.apollo-page-content {
				padding: 1rem;
				border-radius: var(--radius-small, 8px);
			}
		}
	</style>

	<?php wp_head(); ?>
</head>

<body class="apollo-canvas apollo-social-canvas">
	<div id="apollo-canvas-wrapper">
		<!-- Header Navigation -->
		<?php if ( $template_data['show_header'] ) : ?>
			<header class="apollo-header">
				<nav class="apollo-nav">
					<div class="apollo-brand">
						<a href="<?php echo esc_url( $template_data['brand_url'] ?? home_url() ); ?>">
							<?php echo esc_html( $template_data['brand_name'] ?? 'Apollo Social' ); ?>
						</a>
					</div>

					<?php if ( ! empty( $template_data['navigation'] ) ) : ?>
						<ul class="apollo-menu">
							<?php foreach ( $template_data['navigation'] as $item ) : ?>
								<li>
									<a href="<?php echo esc_url( $item['url'] ); ?>" <?php echo $item['active'] ? 'class="active"' : ''; ?>>
										<?php echo esc_html( $item['label'] ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</nav>
			</header>
		<?php endif; ?>

		<!-- Main Content -->
		<main id="apollo-main" class="apollo-content">
			<?php if ( ! empty( $template_data['breadcrumbs'] ) ) : ?>
				<nav class="apollo-breadcrumbs">
					<?php foreach ( $template_data['breadcrumbs'] as $crumb ) : ?>
						<span><?php echo esc_html( $crumb ); ?></span>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<div class="apollo-page-content">
				<?php echo $template_data['content'] ?? ''; ?>
			</div>
		</main>

		<!-- Footer -->
		<?php if ( $template_data['show_footer'] ) : ?>
			<footer class="apollo-footer">
				<p><?php echo esc_html( $template_data['footer_text'] ?? '&copy; ' . date( 'Y' ) . ' Apollo Social. Todos os direitos reservados.' ); ?></p>
			</footer>
		<?php endif; ?>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
