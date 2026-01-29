<?php
declare(strict_types=1);
/**
 * Cena::Rio - Moderation Template (Canvas Mode)
 *
 * Moderation queue interface for CENA-MOD users
 * This template uses Canvas Mode (no theme CSS)
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user has permission to moderate.
if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
}

$current_user = wp_get_current_user();

// CDN with local fallback
$cdn_base     = 'https://assets.apollo.rio.br/';
$local_base   = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/core/'
	: plugin_dir_url( __DIR__ ) . 'assets/core/';
$local_vendor = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/vendor/'
	: plugin_dir_url( __DIR__ ) . 'assets/vendor/';
$local_img    = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/img/'
	: plugin_dir_url( __DIR__ ) . 'assets/img/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Moderação Cena::Rio - Apollo::rio</title>

	<!-- Apollo CDN Loader - Auto-loads CSS, icons, dark mode, etc. -->
	<link rel="icon" href="<?php echo esc_url( $local_img . 'neon-green.webp' ); ?>" type="image/webp">
	<script src="https://assets.apollo.rio.br/index.min.js"></script>

	<style>
		/* ==========================================================================
			ROOT VARIABLES & THEME SETUP (Using Apollo Design System Tokens)
			========================================================================== */
		:root {
			--font-primary: var(--ap-font-sans, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif);
			--radius-main: var(--ap-radius-lg, 12px);
			--radius-sec: var(--ap-radius-xl, 20px);
			--transition-main: var(--ap-transition-smooth, all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1));

			/* Light Mode Palette - Apollo Tokens */
			--bg-main: var(--ap-bg, #fff);
			--bg-main-translucent: var(--ap-bg-translucent, rgba(255, 255, 255, .68));
			--header-blur-bg: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
			--text-main: var(--ap-text-muted, rgba(19, 21, 23, .7));
			--text-primary: var(--ap-text, rgba(19, 21, 23, .85));
			--text-secondary: var(--ap-text-muted, rgba(19, 21, 23, .7));
			--border-color: var(--ap-border, #e0e2e4);
			--border-color-2: var(--ap-border-light, rgba(224, 226, 228, 0.33));
			--card-border-light: var(--ap-border-light, rgba(0, 0, 0, 0.13));
			--card-shadow-light: var(--ap-shadow-sm, rgba(0, 0, 0, 0.05));
			--accent-color: var(--ap-orange, #FFA17F);
			--vermelho: var(--ap-red, #fe786d);
			--laranja: var(--ap-orange, #FFA17F);

			/* Moderation Specific - Apollo Status Tokens */
			--success-color: var(--ap-green, #10b981);
			--error-color: var(--ap-red, #dc2626);
			--warning-color: var(--ap-yellow, #f59e0b);
		}

		/* Dark Mode Palette - Apollo Dark Tokens */
		body.dark-mode {
			--bg-main: var(--ap-bg-dark, #131517);
			--bg-main-translucent: var(--ap-bg-dark-translucent, rgba(19, 21, 23, 0.68));
			--header-blur-bg: linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
			--text-main: var(--ap-text-light-muted, rgba(255, 255, 255, 0.57));
			--text-primary: var(--ap-text-light, rgba(253, 253, 253, 0.98));
			--text-secondary: var(--ap-text-light-muted, rgba(255, 255, 255, 0.57));
			--border-color: var(--ap-border-dark, #333537);
			--border-color-2: var(--ap-border-dark-light, rgba(224, 226, 228, 0.04));
			--card-border-light: var(--ap-border-dark-light, rgba(255, 255, 255, 0.1));
			--card-shadow-light: var(--ap-shadow-dark, rgba(0, 0, 0, 0.2));
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
			background-color: #f8fafc;
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

		/* ==========================================================================
			MODERATION LAYOUT STYLES
			========================================================================== */
		.main-container {
			max-width: 1200px;
			margin: 0 auto;
			padding: 100px 40px 50px 40px;
		}

		.topbar {
			background: var(--bg-main);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-main);
			padding: 24px;
			margin-bottom: 32px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			box-shadow: 0 10px 30px var(--card-shadow-light);
		}

		.topbar-content h1 {
			font-size: 24px;
			font-weight: 800;
			color: var(--text-primary);
			margin: 0;
		}

		.topbar-content p {
			margin: 4px 0 0 0;
			color: var(--text-secondary);
			font-size: 14px;
		}

		.topbar-actions {
			display: flex;
			gap: 12px;
			align-items: center;
		}

		.btn-secondary {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 12px 20px;
			background: var(--border-color-2);
			color: var(--text-secondary);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-main);
			font-weight: 600;
			text-decoration: none;
			transition: var(--transition-main);
		}

		.btn-secondary:hover {
			background: var(--border-color);
			color: var(--text-primary);
		}

		/* Alert Messages */
		.alert {
			padding: 16px;
			border-radius: var(--radius-main);
			border-left: 4px solid;
			margin-bottom: 24px;
			font-weight: 500;
		}

		.alert-success {
			background: #f0fdf4;
			border-left-color: var(--success-color);
			color: #065f46;
		}

		.alert-error {
			background: #fef2f2;
			border-left-color: var(--error-color);
			color: #991b1b;
		}

		/* Moderation Queue Container */
		.mod-queue-container {
			background: var(--bg-main);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-main);
			padding: 2rem;
			box-shadow: 0 10px 30px var(--card-shadow-light);
		}

		/* ==========================================================================
			RESPONSIVE DESIGN
			========================================================================== */
		@media (max-width: 768px) {
			.main-container {
				padding: 80px 20px 30px 20px;
			}

			.topbar {
				flex-direction: column;
				gap: 16px;
				text-align: center;
				padding: 20px;
			}

			.topbar-actions {
				width: 100%;
				justify-content: center;
			}

			.mod-queue-container {
				padding: 1.5rem;
			}
		}
	</style>
</head>
<body>

	<!-- NAVBAR: New navbar is loaded via wp_footer hook from class-apollo-navbar-apps.php -->

	<main class="main-container">
		<div class="topbar">
			<div class="topbar-content">
				<h1>Moderação Cena::Rio</h1>
				<p>Painel de aprovação de eventos</p>
			</div>
			<div class="topbar-actions">
				<a href="<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>" class="btn-secondary">
					<i class="ri-arrow-left-line"></i>
					Voltar ao Calendário
				</a>
			</div>
		</div>

		<?php
		// Sanitize and check for success messages.
		$approved = isset( $_GET['cena_approved'] ) ? sanitize_text_field( wp_unslash( $_GET['cena_approved'] ) ) : '';
		$rejected = isset( $_GET['cena_rejected'] ) ? sanitize_text_field( wp_unslash( $_GET['cena_rejected'] ) ) : '';

		if ( '1' === $approved ) {
			echo '<div class="alert alert-success">
                <strong>✓ Evento Aprovado!</strong><br>
                O evento foi publicado com sucesso no calendário.
            </div>';
		}

		if ( '1' === $rejected ) {
			echo '<div class="alert alert-error">
                <strong>Evento Rejeitado</strong><br>
                O evento foi movido para rascunho.
            </div>';
		}
		?>

		<div class="mod-queue-container">
			<?php
			// Render mod queue shortcode.
			echo do_shortcode( '[apollo_cena_mod_queue]' );
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
