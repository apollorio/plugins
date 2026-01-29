<?php
declare(strict_types=1);
/**
 * Cena::Rio - Calendar Template (Canvas Mode)
 *
 * Full calendar interface with map and event management
 * This template uses Canvas Mode (no theme CSS)
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user has permission to view.
if ( ! Apollo_Cena_Rio_Roles::user_can_submit() ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
}

$current_user = wp_get_current_user();
$can_moderate = Apollo_Cena_Rio_Roles::user_can_moderate();

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
	<title>Cena::Rio · Calendário Compacto - Apollo::rio</title>

	<!-- Apollo CDN Loader - Auto-loads CSS, icons, dark mode, etc. -->
	<link rel="icon" href="<?php echo esc_url( $local_img . 'neon-green.webp' ); ?>" type="image/webp">
	<script src="https://assets.apollo.rio.br/index.min.js"></script>

	<!-- Map Library - Leaflet (loaded separately as optional component) -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">

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

			/* Calendar Specific - Using Apollo Tokens */
			--bg-calendar: var(--ap-bg-muted, #f8fafc);
			--muted-calendar: var(--ap-text-muted, #64748b);
			--accent-calendar: var(--ap-orange, #f97316);
			--accent-strong: var(--ap-orange-600, #ea580c);
			--confirmed-calendar: var(--ap-green, #10b981);
			--card-calendar: var(--ap-bg, #ffffff);
			--border-calendar: var(--ap-border, #e6eef6);
			--shadow-calendar: var(--ap-shadow-lg, 0 8px 30px rgba(15,23,42,0.06));
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

			/* Calendar Dark Mode - Apollo Tokens */
			--bg-calendar: var(--ap-bg-dark-muted, #1a1d23);
			--card-calendar: var(--ap-bg-dark-subtle, #23262f);
			--border-calendar: var(--ap-border-dark, #374151);
			--shadow-calendar: var(--ap-shadow-dark-lg, 0 8px 30px rgba(0,0,0,0.3));
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
			background-color: var(--bg-calendar);
			transition: background-color 0.4s ease, color 0.4s ease;
			overflow-x: hidden !important;
			scroll-behavior: smooth;
			height: 100%;
		}

		a {
			text-decoration: none;
			color: var(--text-main);
		}

		a:hover {
			color: var(--text-primary);
		}

		/* ==========================================================================
			CALENDAR LAYOUT STYLES
			========================================================================== */
		.app {
			display: flex;
			min-height: 100vh;
			gap: 0;
		}

		.leftbar {
			width: 18rem;
			background: var(--card-calendar);
			border-right: 1px solid var(--border-calendar);
			display: flex;
			flex-direction: column;
			z-index: 40;
			position: sticky;
			top: 0;
			height: 100vh;
		}

		@media (max-width: 980px) {
			.leftbar {
				display: none;
			}
		}

		.content {
			flex: 1;
			display: flex;
			flex-direction: column;
			min-height: 100vh;
			overflow-x: hidden;
		}

		.topbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 12px 20px;
			background: var(--bg-main-translucent);
			border-bottom: 1px solid var(--border-calendar);
			position: sticky;
			top: 0;
			z-index: 60;
			backdrop-filter: blur(6px);
		}

		.workspace {
			display: grid;
			grid-template-columns: 260px 1fr;
			grid-template-rows: auto auto;
			grid-template-areas:
				"calendar map"
				"events events";
			gap: 24px;
			padding: 24px;
			max-width: 1400px;
			margin: 0 auto;
			width: 100%;
			box-sizing: border-box;
		}

		.calendar-card {
			grid-area: calendar;
			background: var(--card-calendar);
			border: 1px solid var(--border-calendar);
			border-radius: var(--radius-main);
			padding: 12px;
			box-shadow: var(--shadow-calendar);
			position: relative;
			z-index: 20;
			height: fit-content;
		}

		.map-card {
			grid-area: map;
			background: var(--card-calendar);
			border: 1px solid var(--border-calendar);
			border-radius: var(--radius-main);
			box-shadow: var(--shadow-calendar);
			overflow: hidden;
			min-height: 320px;
			height: 100%;
		}

		#footer-map {
			width: 100%;
			height: 100%;
			min-height: 320px;
		}

		.events-wrapper {
			grid-area: events;
			min-height: 200px;
		}

		/* Calendar Internals */
		.weekdays {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 4px;
			text-align: center;
			color: var(--muted-calendar);
			font-weight: 700;
			font-size: 11px;
			margin-top: 8px;
		}

		.grid {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 4px;
			margin-top: 4px;
		}

		.day-btn {
			height: 36px;
			border-radius: 8px;
			border: 1px solid transparent;
			background: transparent;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 600;
			font-size: 13px;
			color: var(--text-primary);
			cursor: pointer;
			position: relative;
		}

		.day-btn:hover {
			background: var(--border-color-2);
		}

		.day-btn.disabled {
			opacity: 0.35;
			cursor: default;
		}

		.day-btn.selected {
			background: var(--text-primary);
			color: var(--bg-main);
			box-shadow: 0 4px 12px var(--card-shadow-light);
		}

		.day-dot {
			position: absolute;
			bottom: 4px;
			left: 50%;
			transform: translateX(-50%);
			width: 4px;
			height: 4px;
			border-radius: 999px;
			opacity: 0.9;
		}

		.day-dot.expected {
			background: var(--accent-calendar);
		}

		.day-dot.confirmed {
			background: var(--accent-strong);
			width: 5px;
			height: 5px;
		}

		/* Events Styles */
		.events-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 16px;
		}

		.events-grid {
			display: block;
			width: 100%;
		}

		.event-card {
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			background: var(--card-calendar);
			border: 1px solid var(--border-calendar);
			border-radius: var(--radius-main);
			padding: 16px 20px;
			box-shadow: var(--shadow-calendar);
			margin-bottom: 12px;
			gap: 16px;
			box-sizing: border-box;
		}

		.event-card.expected {
			border-left: 4px solid var(--accent-calendar);
		}

		.event-card.confirmed {
			border-left: 4px solid var(--confirmed-calendar);
		}

		.event-info {
			flex: 1;
			min-width: 0;
		}

		.event-controls {
			display: flex;
			align-items: center;
			gap: 16px;
			flex-shrink: 0;
		}

		.event-title {
			font-weight: 700;
			font-size: 16px;
			color: var(--text-primary);
		}

		.event-meta {
			font-size: 14px;
			color: var(--text-secondary);
			margin-top: 4px;
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}

		.event-actions {
			display: flex;
			gap: 8px;
			align-items: center;
		}

		/* Bottom Navigation */
		.bottom-nav {
			position: fixed;
			left: 0;
			right: 0;
			bottom: env(safe-area-inset-bottom, 0);
			display: flex;
			justify-content: space-around;
			align-items: center;
			height: 64px;
			padding: 8px 12px;
			background: var(--bg-main-translucent);
			backdrop-filter: blur(8px);
			z-index: 1200;
			box-shadow: 0 -6px 20px var(--card-shadow-light);
		}

		.bottom-nav .nav-btn {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 2px;
			font-size: 11px;
			color: var(--text-secondary);
			width: 56px;
		}

		.bottom-nav .nav-btn.active {
			color: var(--text-primary);
		}

		@media (min-width: 980px) {
			.bottom-nav {
				display: none;
			}
		}

		/* ==========================================================================
			RESPONSIVE DESIGN
			========================================================================== */
		@media (max-width: 900px) {
			.workspace {
				display: flex;
				flex-direction: column;
				padding: 16px;
				gap: 20px;
			}

			.calendar-card {
				width: 100%;
				order: 1;
			}

			.events-wrapper {
				order: 2;
			}

			.map-card {
				order: 3;
				min-height: 250px;
			}

			.day-btn {
				height: 44px;
			}
		}

		@media (max-width: 640px) {
			.event-card {
				flex-direction: column;
				align-items: flex-start;
				gap: 12px;
			}

			.event-card .event-controls {
				width: 100%;
				justify-content: space-between;
				margin-top: 8px;
				padding-top: 8px;
				border-top: 1px solid var(--border-color);
			}
		}

		/* ==========================================================================
			UTILITY CLASSES
			========================================================================== */
		.muted {
			color: var(--muted-calendar);
		}

		.legend {
			display: flex;
			gap: 12px;
			align-items: center;
			color: var(--text-secondary);
			font-size: 11px;
		}

		.btn {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border-radius: 8px;
			border: 1px solid transparent;
			background: var(--text-primary);
			color: var(--bg-main);
			font-weight: 600;
			cursor: pointer;
			font-size: 13px;
			transition: var(--transition-main);
		}

		.btn:hover {
			opacity: 0.9;
		}

		.btn.ghost {
			background: transparent;
			color: var(--text-secondary);
			border-color: var(--border-calendar);
		}

		.btn.ghost:hover {
			background: var(--border-color-2);
			color: var(--text-primary);
		}

		.btn.small {
			padding: 6px 12px;
			font-size: 12px;
		}

		:focus {
			outline: 3px solid rgba(99, 102, 241, 0.12);
			outline-offset: 2px;
		}
	</style>
</head>
<body>

	<!-- ======================= LEFT SIDEBAR ======================= -->
	<aside class="leftbar">
		<div style="height: 4rem; display: flex; align-items: center; gap: 12px; padding: 0 24px; border-bottom: 1px solid var(--border-calendar);">
			<div style="height: 32px; width: 32px; border-radius: 50%; background: var(--text-primary); display: flex; align-items: center; justify-content: center; color: var(--bg-main);">
				<i class="ri-command-fill" style="font-size: 18px;"></i>
			</div>
			<span style="font-weight: 700; color: var(--text-primary); font-size: 18px;">Cena::Rio</span>
		</div>

		<nav style="flex: 1; padding: 0 16px; display: flex; flex-direction: column; gap: 4px; padding-top: 24px; overflow-y: auto;">
			<div style="padding: 0 8px; margin-bottom: 8px; font-size: 10px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em;">Menu</div>
			<a href="<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: var(--border-color-2); color: var(--text-primary); border-radius: 8px; font-weight: 600;">
				<i class="ri-calendar-line" style="font-size: 18px;"></i>
				<span style="font-size: 14px;">Calendário</span>
			</a>
			<?php if ( $can_moderate ) : ?>
			<a href="<?php echo esc_url( home_url( '/cena-rio/mod/' ) ); ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: var(--text-secondary); border-radius: 8px; transition: var(--transition-main);" class="hover-bg">
				<i class="ri-shield-check-line" style="font-size: 18px;"></i>
				<span style="font-weight: 500; font-size: 14px;">Moderação</span>
			</a>
			<?php endif; ?>
			<a href="#" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: var(--text-secondary); border-radius: 8px; transition: var(--transition-main);" class="hover-bg">
				<i class="ri-bar-chart-grouped-line" style="font-size: 18px;"></i>
				<span style="font-weight: 500; font-size: 14px;">Estatísticas</span>
			</a>
		</nav>

		<div style="padding: 12px; border-top: 1px solid var(--border-calendar);">
			<div style="display: flex; align-items: center; gap: 12px; padding: 0 8px;">
				<div style="height: 32px; width: 32px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white;">
					<?php echo esc_html( strtoupper( substr( $current_user->display_name, 0, 2 ) ) ); ?>
				</div>
				<div style="display: flex; flex-direction: column;">
					<span style="font-size: 12px; font-weight: 700; color: var(--text-primary);"><?php echo esc_html( $current_user->display_name ); ?></span>
					<span style="font-size: 10px; color: var(--text-secondary);"><?php echo esc_html( $current_user->user_email ); ?></span>
				</div>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="margin-left: auto; color: var(--text-secondary);" title="Logout">
					<i class="ri-logout-box-r-line" style="font-size: 18px;"></i>
				</a>
			</div>
		</div>
	</aside>

	<!-- ======================= MAIN CONTENT ======================= -->
	<main class="content">
		<!-- Topbar -->
		<header class="topbar">
			<div style="display: flex; align-items: center; gap: 12px;">
				<div>
					<div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">Calendário Cena::Rio</div>
					<div style="font-size: 14px; color: var(--text-secondary);">Planejamento da cena eletrônica</div>
				</div>
			</div>
			<div style="display: flex; gap: 8px; align-items: center;">
				<button id="prev-month" class="btn ghost small" aria-label="Mês anterior">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<div id="month-label" style="font-weight: 800; min-width: 120px; text-align: center; font-size: 14px; color: var(--text-primary);"></div>
				<button id="next-month" class="btn ghost small" aria-label="Próximo mês">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>
		</header>

		<!-- Workspace: Grid layout -->
		<section class="workspace">
			<!-- Calendar column (Top Left) -->
			<aside class="calendar-card" aria-label="Calendário">
				<div style="display: flex; justify-content: space-between; align-items: center;">
					<div style="font-weight: 700; font-size: 13px; color: var(--text-primary);">Navegação</div>
					<div class="legend">
						<div style="display: flex; align-items: center; gap: 4px;">
							<span style="width: 10px; height: 6px; background: rgba(249,115,22,0.3); border-radius: 6px;"></span>
						</div>
						<div style="display: flex; align-items: center; gap: 4px;">
							<span style="width: 10px; height: 6px; background: var(--confirmed-calendar); border-radius: 6px;"></span>
						</div>
					</div>
				</div>
				<div class="weekdays" aria-hidden="true">
					<div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div>
				</div>
				<div id="calendar-grid" class="grid" role="grid" aria-label="Calendário mensal"></div>
				<div style="margin-top: 12px; display: flex; gap: 8px; justify-content: center; align-items: center;">
					<div style="font-size: 11px; color: var(--text-secondary);">Selecione um dia</div>
				</div>
			</aside>

			<!-- Events Wrapper (Bottom Full Width on Desktop, Middle on Mobile) -->
			<div class="events-wrapper">
				<div class="events-header">
					<div>
						<div id="selected-day" style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary);">Todos os eventos</div>
						<div class="muted" style="font-size: 13px;">Lista de produções</div>
					</div>
					<div style="display: flex; gap: 8px; align-items: center;">
						<button id="btn-add-event" class="btn small">
							<i class="ri-add-line"></i> Novo
						</button>
					</div>
				</div>
				<div id="events-grid" class="events-grid" aria-live="polite">
					<!-- Event cards will be injected here -->
				</div>
			</div>

			<!-- Map (Top Right on Desktop, Bottom on Mobile) -->
			<div class="map-card">
				<div id="footer-map"></div>
			</div>
		</section>

		<!-- Mobile bottom nav -->
		<div class="bottom-nav" role="navigation" aria-label="Navegação inferior">
			<div class="nav-btn active">
				<i class="ri-calendar-line"></i>
				<span>Agenda</span>
			</div>
			<?php if ( $can_moderate ) : ?>
			<div class="nav-btn" onclick="location.href='<?php echo esc_js( esc_url( home_url( '/cena-rio/mod/' ) ) ); ?>'">
				<i class="ri-shield-check-line"></i>
				<span>Mod</span>
			</div>
			<?php endif; ?>
			<div style="position: relative; top: -18px;">
				<button onclick="openQuickAdd()" style="height: 56px; width: 56px; border-radius: 50%; background: var(--text-primary); color: var(--bg-main); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px var(--card-shadow-light);">
					<i class="ri-add-line" style="font-size: 24px;"></i>
				</button>
			</div>
			<div class="nav-btn">
				<i class="ri-bar-chart-grouped-line"></i>
				<span>Stats</span>
			</div>
			<div class="nav-btn" onclick='location.href="<?php echo esc_js( esc_url( wp_logout_url( home_url() ) ) ); ?>"'>
				<i class="ri-logout-box-r-line"></i>
				<span>Sair</span>
			</div>
		</div>
	</main>

	<!-- Modal root -->
	<div id="modal-root" style="display: none;"></div>

	<!-- ======================= DARK MODE TOGGLE ======================= -->
	<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Toggle dark mode">
		<i class="ri-sun-line"></i>
		<i class="ri-moon-line"></i>
	</div>

	<!-- Scripts - Local -->
	<script src="<?php echo esc_url( $asset_base . 'vendor/leaflet/leaflet.js' ); ?>"></script>
	<script src="<?php echo esc_url( $asset_base . 'core/base.js' ); ?>"></script>

	<!-- Apollo REST API config -->
	<script>
		window.apolloCenaRio = {
			restUrl: '<?php echo esc_js( rest_url( 'apollo/v1/' ) ); ?>',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
			canModerate: <?php echo $can_moderate ? 'true' : 'false'; ?>,
			currentUser: {
				id: <?php echo absint( $current_user->ID ); ?>,
				name: '<?php echo esc_js( $current_user->display_name ); ?>'
			}
		};
	</script>

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

			// --- Header Clock (if needed) ---.
			// Clock functionality can be added here if required.

		});
	</script>

	<script src="<?php echo esc_url( APOLLO_CORE_PLUGIN_URL . 'assets/js/cena-rio-calendar.js' ); ?>?v=<?php echo esc_attr( APOLLO_CORE_VERSION ); ?>"></script>

	<?php
	// Load the new official navbar via wp_footer hook
	wp_footer();
	?>
</body>
</html>

			/* Layout Structure */
			.app { display:flex; min-height:100vh; gap:0; }
			aside.leftbar { width:18rem; background:#fff; border-right:1px solid #eef2f7; display:flex; flex-direction:column; z-index:40; position:sticky; top:0; height:100vh; }
			@media (max-width: 980px){ aside.leftbar{display:none} }
			main.content { flex:1; display:flex; flex-direction:column; min-height:100vh; overflow-x: hidden; }
			/* Top header */
			header.topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 20px; background:rgba(255,255,255,0.9); border-bottom:1px solid #eef2f7; position:sticky; top:0; z-index:60; backdrop-filter: blur(6px); }
			/* --- REFACTORED WORKSPACE: Grid Layout --- */
			.workspace {
				display: grid;
				grid-template-columns: 260px 1fr; /* Fixed Calendar width, Map takes rest */
				grid-template-rows: auto auto; /* Two rows */
				grid-template-areas:
				"calendar map"
				"events events";
				gap: 24px;
				padding: 24px;
				max-width: 1400px;
				margin: 0 auto;
				width: 100%;
				box-sizing: border-box;
			}

			/* Calendar: Top Left */
			.calendar-card {
				grid-area: calendar;
				background: var(--card);
				border: 1px solid var(--border);
				border-radius: 12px;
		';
		wp_add_inline_style( 'apollo-uni-css', $calendar_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
	<title>Cena::rio · Calendário Compacto</title>
	<?php wp_head(); ?>
		padding: 12px;
		box-shadow: var(--shadow);
		position: relative;
		z-index: 20;
		height: fit-content;
	}
	/* Map: Top Right */
	.map-card {
		grid-area: map;
		background: var(--card);
		border: 1px solid var(--border);
		border-radius: 12px;
		box-shadow: var(--shadow);
		overflow: hidden;
		min-height: 320px; /* Ensure map has height */
		height: 100%;
	}
	#footer-map { width:100%; height:100%; min-height: 320px; }
	/* Events List: Bottom Full Width */
	.events-wrapper {
		grid-area: events;
		min-height: 200px;
	}
	/* Calendar Internals (Compact) */
	.weekdays { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; text-align:center; color:var(--muted); font-weight:700; font-size:11px; margin-top:8px; }
	.grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-top:4px; }

	.day-btn {
		height: 36px;
		border-radius: 8px;
		border: 1px solid transparent;
		background: transparent;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 600;
		font-size: 13px;
		color: #0f172a;
		cursor: pointer;
		position: relative;
	}
	.day-btn:hover { background: #f1f5f9; }
	.day-btn.disabled { opacity: 0.35; cursor: default; }
	.day-btn.selected { background: #0f172a; color: #fff; box-shadow: 0 4px 12px rgba(15,23,42,0.15); }

	.day-dot { position:absolute; bottom:4px; left:50%; transform:translateX(-50%); width:4px; height:4px; border-radius:999px; opacity:0.9; }
	.day-dot.expected { background:var(--accent); }
	.day-dot.confirmed { background:var(--accent-strong); width:5px; height:5px; }
	/* Header above events */
	.events-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 16px;
	}
	/* Events Grid */
	.events-grid {
		display: block;
		width: 100%;
	}
	.event-card {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		background: var(--card);
		border: 1px solid var(--border);
		border-radius: 12px;
		padding: 16px 20px;
		box-shadow: var(--shadow);
		margin-bottom: 12px;
		gap: 16px;
		box-sizing: border-box;
	}
	/* Responsive: Mobile Stack */
	@media (max-width: 900px) {
		.workspace {
		display: flex;
		flex-direction: column;
		padding: 16px;
		gap: 20px;
		}
		/* Order naturally follows DOM: Calendar -> Events -> Map */
		.calendar-card { width: 100%; order: 1; }
		.events-wrapper { order: 2; }
		.map-card { order: 3; min-height: 250px; }

		.day-btn { height: 44px; }
	}
	@media (max-width: 640px) {
		.event-card {
		flex-direction: column;
		align-items: flex-start;
		gap: 12px;
		}
		.event-card .event-controls {
		width: 100%;
		justify-content: space-between;
		margin-top: 8px;
		padding-top: 8px;
		border-top: 1px solid #f1f5f9;
		}
	}
	.event-card.expected { border-left: 4px solid var(--accent); }
	.event-card.confirmed { border-left: 4px solid var(--confirmed); }
	.event-info { flex: 1; min-width: 0; }
	.event-controls { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
	.event-title { font-weight: 700; font-size: 16px; color: #0f172a; }
	.event-meta { font-size: 14px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

	.event-actions { display: flex; gap: 8px; align-items: center; }
	/* Bottom nav */
	.bottom-nav { position:fixed; left:0; right:0; bottom: env(safe-area-inset-bottom, 0); display:flex; justify-content:space-around; align-items:center; height:64px; padding:8px 12px; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.98)); backdrop-filter: blur(8px); z-index:1200; box-shadow: 0 -6px 20px rgba(0,0,0,0.06); }
	.bottom-nav .nav-btn { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; font-size:11px; color:#64748b; width:56px; }
	.bottom-nav .nav-btn.active { color:#0f172a; }
	@media (min-width: 980px){ .bottom-nav { display:none } }
	/* Utilities */
	.muted{color:var(--muted)}
	.legend{display:flex;gap:12px;align-items:center;color:#475569;font-size:11px}
	.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; border:1px solid transparent; background:#111827; color:#fff; font-weight:600; cursor:pointer; font-size: 13px; transition: opacity 0.2s;}
	.btn:hover { opacity: 0.9; }
	.btn.ghost { background:transparent; color:#475569; border-color:var(--border) }
	.btn.ghost:hover { background: #f1f5f9; color: #0f172a; }
	.btn.small { padding: 6px 12px; font-size: 12px; }

	:focus { outline: 3px solid rgba(99,102,241,0.12); outline-offset: 2px; }
	</style>
</head>
<body class="h-full">
	<div class="app">
	<!-- LEFT SIDEBAR -->
	<aside class="leftbar">
		<div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
		<div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-white">
			<i class="ri-command-fill text-lg"></i>
		</div>
		<span class="font-bold text-slate-900 tracking-tight text-lg">Cena::Rio</span>
		</div>
		<nav class="flex-1 px-4 space-y-1 py-6 overflow-y-auto">
		<div class="px-2 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menu</div>
		<a href="<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>" class="flex items-center gap-3 px-3 py-2.5 bg-slate-100 text-slate-900 rounded-lg font-semibold"><i class="ri-calendar-line text-lg"></i><span class="text-sm">Calendário</span></a>
		<?php if ( $can_moderate ) : ?>
		<a href="<?php echo esc_url( home_url( '/cena-rio/mod/' ) ); ?>" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group"><i class="ri-shield-check-line text-lg"></i><span class="font-medium text-sm">Moderação</span></a>
		<?php endif; ?>
		<a href="#" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group"><i class="ri-bar-chart-grouped-line text-lg"></i><span class="font-medium text-sm">Estatísticas</span></a>
		</nav>
		<div class="p-3 border-t border-slate-100">
		<div class="flex items-center gap-3 px-2">
			<div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600"><?php echo esc_html( strtoupper( substr( $current_user->display_name, 0, 2 ) ) ); ?></div>
			<div class="flex flex-col leading-tight">
			<span class="text-xs font-bold text-slate-900"><?php echo esc_html( $current_user->display_name ); ?></span>
			<span class="text-[10px] text-slate-500"><?php echo esc_html( $current_user->user_email ); ?></span>
			</div>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="ml-auto text-slate-400 hover:text-slate-600" title="Logout"><i class="ri-logout-box-r-line text-lg"></i></a>
		</div>
		</div>
	</aside>
	<!-- MAIN -->
	<main class="content">
		<!-- Topbar -->
		<header class="topbar">
		<div style="display:flex;align-items:center;gap:12px">
			<div>
			<div class="text-lg font-bold">Calendário Cena::Rio</div>
			<div class="text-sm muted">Planejamento da cena eletrônica</div>
			</div>
		</div>
		<div style="display:flex;gap:8px;align-items:center">
			<button id="prev-month" class="btn ghost small" aria-label="Mês anterior"><i class="ri-arrow-left-s-line"></i></button>
			<div id="month-label" style="font-weight:800;min-width:120px;text-align:center;font-size:14px"></div>
			<button id="next-month" class="btn ghost small" aria-label="Próximo mês"><i class="ri-arrow-right-s-line"></i></button>
		</div>
		</header>
		<!-- Workspace: Grid layout -->
		<section class="workspace">
		<!-- Calendar column (Top Left) -->
		<aside class="calendar-card" aria-label="Calendário">
			<div style="display:flex;justify-content:space-between;align-items:center">
			<div style="font-weight:700;font-size:13px">Navegação</div>
			<div class="legend">
				<div class="sw"><span class="box" style="width:10px;height:6px;background:rgba(249,115,22,0.3);border-radius:6px;display:inline-block"></span></div>
				<div class="sw"><span class="box" style="width:10px;height:6px;background:var(--confirmed);border-radius:6px;display:inline-block"></span></div>
			</div>
			</div>
			<div class="weekdays" aria-hidden="true">
			<div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div>
			</div>
			<div id="calendar-grid" class="grid" role="grid" aria-label="Calendário mensal"></div>
			<div style="margin-top:12px;display:flex;gap:8px;justify-content:center;align-items:center">
			<div style="font-size:11px;color:var(--muted)">Selecione um dia</div>
			</div>
		</aside>
		<!-- Events Wrapper (Bottom Full Width on Desktop, Middle on Mobile) -->
		<div class="events-wrapper">
			<div class="events-header">
			<div>
				<div id="selected-day" style="font-weight:800; font-size: 1.1rem;">Todos os eventos</div>
				<div class="muted" style="font-size:13px">Lista de produções</div>
			</div>
			<div style="display:flex;gap:8px;align-items:center">
				<button id="btn-add-event" class="btn small"><i class="ri-add-line"></i> Novo</button>
			</div>
			</div>
			<div id="events-grid" class="events-grid" aria-live="polite">
			<!-- cards injected here -->
			</div>
		</div>
		<!-- Map (Top Right on Desktop, Bottom on Mobile) -->
		<div class="map-card">
			<div id="footer-map"></div>
		</div>
		</section>
		<!-- Mobile bottom nav -->
		<div class="bottom-nav" role="navigation" aria-label="Navegação inferior">
		<div class="nav-btn active"><i class="ri-calendar-line"></i><span>Agenda</span></div>
		<?php if ( $can_moderate ) : ?>
		<div class="nav-btn" onclick="location.href='<?php echo esc_js( esc_url( home_url( '/cena-rio/mod/' ) ) ); ?>'"><i class="ri-shield-check-line"></i><span>Mod</span></div>
		<?php endif; ?>
		<div style="position:relative;top:-18px">
			<button onclick="openQuickAdd()" class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg"><i class="ri-add-line text-3xl"></i></button>
		</div>
		<div class="nav-btn"><i class="ri-bar-chart-grouped-line"></i><span>Stats</span></div>
		<div class="nav-btn" onclick="location.href='<?php echo esc_js( esc_url( wp_logout_url( home_url() ) ) ); ?>'"><i class="ri-logout-box-r-line"></i><span>Sair</span></div>
		</div>
	</main>
	</div>
	<!-- Modal root -->
	<div id="modal-root" style="display:none"></div>

	<!-- Apollo REST API config -->
	<script>
	window.apolloCenaRio = {
		restUrl: '<?php echo esc_js( rest_url( 'apollo/v1/' ) ); ?>',
		nonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
		canModerate: <?php echo $can_moderate ? 'true' : 'false'; ?>,
		currentUser: {
		id: <?php echo absint( $current_user->ID ); ?>,
		name: '<?php echo esc_js( $current_user->display_name ); ?>'
		}
	};
	</script>

	<script src="<?php echo esc_url( APOLLO_CORE_PLUGIN_URL . 'assets/js/cena-rio-calendar.js' ); ?>?v=<?php echo esc_attr( APOLLO_CORE_VERSION ); ?>"></script>
	<?php wp_footer(); ?>
</body>
</html>

