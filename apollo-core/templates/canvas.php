<?php
declare(strict_types=1);

/**
 * Apollo Canvas Template
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$canvas_data = apply_filters( 'apollo_canvas_template_data', array() );

// CDN with local fallback
$cdn_base   = 'https://assets.apollo.rio.br/';
$local_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( __DIR__ ) . 'assets/core/';
$local_vendor = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/vendor/'
	: plugin_dir_url( __DIR__ ) . 'assets/vendor/';
$local_img = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/img/'
	: plugin_dir_url( __DIR__ ) . 'assets/img/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $canvas_data['title'] ?? get_the_title() ); ?> - Apollo::rio</title>

	<!-- Apollo Design System - CDN Loader (auto-loads CSS/JS) -->
	<link rel="icon" href="<?php echo esc_url( $local_img . 'neon-green.webp' ); ?>" type="image/webp">
	<script src="https://assets.apollo.rio.br/index.min.js"></script>

	<style>
		/* ==========================================================================
			ROOT VARIABLES & THEME SETUP (APOLLO DESIGN SYSTEM)
			========================================================================== */
		:root {
			--font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			--radius-main: 12px;
			--radius-sec: 20px;
			--transition-main: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);

			/* Light Mode Palette */
			--bg-main: #fff;
			--bg-main-translucent: rgba(255, 255, 255, .68);
			--header-blur-bg: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
			--text-main: rgba(19, 21, 23, .7);
			--text-primary: rgba(19, 21, 23, .85);
			--text-secondary: rgba(19, 21, 23, .7);
			--border-color: #e0e2e4;
			--border-color-2: #e0e2e454;
			--card-border-light: rgba(0, 0, 0, 0.13);
			--card-shadow-light: rgba(0, 0, 0, 0.05);
			--accent-color: #FFA17F;
			--vermelho: #fe786d;
			--laranja: #FFA17F;
		}

		/* Dark Mode Palette */
		body.dark-mode {
			--bg-main: #131517;
			--bg-main-translucent: rgba(19, 21, 23, 0.68);
			--header-blur-bg: linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
			--text-main: #ffffff91;
			--text-primary: #fdfdfdfa;
			--text-secondary: #ffffff91;
			--border-color: #333537;
			--border-color-2: #e0e2e40a;
			--card-border-light: rgba(255, 255, 255, 0.1);
			--card-shadow-light: rgba(0, 0, 0, 0.2);
		}

		/* ==========================================================================
			BASE & RESET STYLES
			========================================================================== */
		*, :before, :after {
			box-sizing: border-box;
		}
		* {
			margin: 0;
			padding: 0;
		}
		html, body {
			color: var(--text-secondary);
			font-family: var(--font-primary);
			font-size: 15px;
			font-weight: 400;
			line-height: 1.2rem;
			letter-spacing: 0.1px;
			background-color: var(--bg-main);
			transition: background-color 0.4s ease, color 0.4s ease;
			overflow-x: hidden !important;
			scroll-behavior: smooth;
		}

		a {
			text-decoration: none;
			color: var(--text-main);
		}

		a:hover {
			color: var(--text-primary);
		}

		.off:hover {
			color: var(--text-main);
			cursor: default!important;
		}

		/* ==========================================================================
			CANVAS SPECIFIC STYLES
			========================================================================== */
		.apollo-canvas-main {
			min-height: 100vh;
			padding: 100px 40px 50px 40px;
			max-width: 1320px;
			margin: 0 auto;
		}

		.canvas-content {
			background: var(--bg-main);
			border-radius: var(--radius-main);
			border: 1px solid var(--border-color);
			padding: 2rem;
			box-shadow: 0 10px 30px var(--card-shadow-light);
		}

		.canvas-title {
			font-size: 2.5rem;
			font-weight: 800;
			color: var(--text-primary);
			margin-bottom: 1.5rem;
			text-align: center;
		}

		.canvas-meta {
			display: flex;
			justify-content: center;
			gap: 2rem;
			margin-bottom: 2rem;
			font-size: 0.9rem;
			color: var(--text-secondary);
		}

		/* ==========================================================================
			RESPONSIVE DESIGN
			========================================================================== */
		@media (max-width: 768px) {
			.apollo-canvas-main {
				padding: 80px 20px 30px 20px;
			}

			.canvas-content {
				padding: 1.5rem;
			}

			.canvas-title {
				font-size: 2rem;
			}

			.canvas-meta {
				flex-direction: column;
				gap: 1rem;
				text-align: center;
			}
		}
	</style>
</head>
<body>

	<!-- NAVBAR: New navbar is loaded via wp_footer hook from class-apollo-navbar-apps.php -->

	<main class="apollo-canvas-main">
		<div class="canvas-content">
			<?php if ( ! empty( $canvas_data['title'] ) ) : ?>
				<h1 class="canvas-title"><?php echo esc_html( $canvas_data['title'] ); ?></h1>
			<?php endif; ?>

			<div class="canvas-meta">
				<span>Canvas Mode</span>
				<span><?php echo esc_html( get_the_title() ); ?></span>
				<span><?php echo esc_html( get_the_date() ); ?></span>
			</div>

			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<?php the_content(); ?>
					</article>
					<?php
				endwhile;
			endif;
			?>
		</div>
	</main>

	<!-- ======================= DARK MODE TOGGLE ======================= -->
	<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Toggle dark mode">
		<i class="ri-sun-line"></i>
		<i class="ri-moon-line"></i>
	</div>

	<!-- Apollo Base JS - Local -->
	<script src="<?php echo esc_url( $asset_base . 'core/base.js' ); ?>"></script>

	<script>
		document.addEventListener('DOMContentLoaded', () => {

			// --- Dark Mode Toggle ---.
			const darkModeToggle = document.getElementById('darkModeToggle');
			const body = document.body;

			// Check for saved preference.
			if (localStorage.getItem('theme') === 'dark') {
				body.classList.add('dark-mode');
			}

			darkModeToggle.addEventListener('click', () => {
				body.classList.toggle('dark-mode');
				// Save preference.
				if (body.classList.contains('dark-mode')) {
					localStorage.setItem('theme', 'dark');
				} else {
					localStorage.setItem('theme', 'light');
				}
			});



			// --- Header Clock ---.
			const agoraH = document.getElementById('agoraH');
			function updateClock() {
				const now = new Date();
				const hours = String(now.getHours()).padStart(2, '0');
				const minutes = String(now.getMinutes()).padStart(2, '0');
				agoraH.textContent = `${hours}:${minutes}`;
			}
			updateClock();
			setInterval(updateClock, 10000); // Update every 10 seconds.

		});
	</script>

	<?php
	// Load the new official navbar via wp_footer hook
	wp_footer();
	?>
</body>
</html>
