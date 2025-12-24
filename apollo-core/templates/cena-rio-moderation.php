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

// Local asset base URL.
$asset_base = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL . 'assets/'
	: plugin_dir_url( __DIR__ ) . 'assets/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Moderação Cena::Rio - Apollo::rio</title>

	<!-- Apollo Design System - Local Assets -->
	<link rel="icon" href="<?php echo esc_url( $asset_base . 'img/neon-green.webp' ); ?>" type="image/webp">
	<link rel="stylesheet" href="<?php echo esc_url( $asset_base . 'core/uni.css' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( $asset_base . 'vendor/remixicon/remixicon.css' ); ?>"

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

			/* Moderation Specific */
			--success-color: #10b981;
			--error-color: #dc2626;
			--warning-color: #f59e0b;
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

	<!-- ======================= HEADER ======================= -->
	<header class="site-header">
		<div class="menu-h-apollo-blur"></div>
		<a href="https://apollo.rio.br/" alt="Logo Apollo::rio" title="Apollo::rio">
			<img src="https://vertente.apollo.rio.br/i.png" class="menu-apollo-logo">
		</a>
		<nav class="main-nav">
			<a class="a-hover off"><span id="agoraH">--:--</span> RJ</a>
			<a href="#" class="ario-eve" title="Portal de Eventos">Eventos<i class="ri-arrow-right-up-line"></i></a>

			<!-- User Menu Dropdown -->
			<div class="menu-h-lista">
				<button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
				<div class="list">
					<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
					<hr>
					<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
					<div class="item ok"><i class="ri-logout-box-r-line"></i> Logout</div>
				</div>
			</div>
		</nav>
	</header>
	<!-- ===================== END HEADER ===================== -->

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

			// --- User Menu Dropdown ---.
			const userMenuTrigger = document.getElementById('userMenuTrigger');
			const userMenu = userMenuTrigger.parentElement;
			userMenuTrigger.addEventListener('click', (e) => {
				e.stopPropagation();
				userMenu.classList.toggle('open');
			});
			// Close on click outside.
			document.addEventListener('click', (e) => {
				if (!userMenu.contains(e.target)) {
					userMenu.classList.remove('open');
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

</body>
</html>

