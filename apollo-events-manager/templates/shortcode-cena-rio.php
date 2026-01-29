<?php
/**
 * Template: Cena Rio Calendar/Agenda
 * APPROVED DESIGN: social - cena-rio - agenda.html
 *
 * Structure:
 * - .app container (flex)
 * - aside.leftbar (desktop sidebar)
 * - main.content
 *   - header.topbar (month navigation)
 *   - section.workspace (grid: calendar + map + events)
 *   - div.bottom-nav (mobile only)
 *
 * @package Apollo_Events_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize template loader.
if ( class_exists( 'Apollo_Template_Loader' ) ) {
	$template_loader = new Apollo_Template_Loader();
} else {
	$template_loader = null;
}

// Get current user data.
$user      = wp_get_current_user();
$user_data = array(
	'display_name' => $user->display_name ? $user->display_name : __( 'Visitante', 'apollo-events-manager' ),
	'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
	'username'     => $user->user_login ? $user->user_login : '',
	'initials'     => strtoupper( substr( $user->display_name ? $user->display_name : 'V', 0, 2 ) ),
);

// Calendar state.
$current_month = (int) gmdate( 'm' );
$current_year  = (int) gmdate( 'Y' );
$months_pt     = array( '', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro' );
$month_label   = strtoupper( $months_pt[ $current_month ] . ' ' . $current_year );

// Navigation items for sidebar.
$nav_items = array(
	array(
		'icon'  => 'ri-building-3-line',
		'label' => 'Feed',
		'url'   => home_url( '/feed/' ),
		'slug'  => 'feed',
	),
	array(
		'icon'  => 'ri-calendar-event-line',
		'label' => 'Eventos',
		'url'   => home_url( '/eventos/' ),
		'slug'  => 'eventos',
	),
	array(
		'icon'  => 'ri-user-community-fill',
		'label' => 'Comunidades',
		'url'   => home_url( '/comunidades/' ),
		'slug'  => 'comunidades',
	),
	array(
		'icon'  => 'ri-team-fill',
		'label' => 'Núcleos',
		'url'   => home_url( '/nucleos/' ),
		'slug'  => 'nucleos',
	),
	array(
		'icon'  => 'ri-megaphone-line',
		'label' => 'Classificados',
		'url'   => home_url( '/anuncios/' ),
		'slug'  => 'anuncios',
	),
	array(
		'icon'  => 'ri-file-text-line',
		'label' => 'Docs & Contratos',
		'url'   => home_url( '/docs/' ),
		'slug'  => 'docs',
	),
	array(
		'icon'  => 'ri-user-smile-fill',
		'label' => 'Perfil',
		'url'   => home_url( '/perfil/' ),
		'slug'  => 'perfil',
	),
);

// Cena::rio specific menu items.
$cenario_items = array(
	array(
		'icon'   => 'ri-calendar-line',
		'label'  => 'Agenda',
		'url'    => home_url( '/cena-rio/' ),
		'slug'   => 'agenda',
		'active' => true,
	),
	array(
		'icon'  => 'ri-bar-chart-grouped-line',
		'label' => 'Fornecedores',
		'url'   => home_url( '/fornecedores/' ),
		'slug'  => 'fornecedores',
	),
	array(
		'icon'  => 'ri-file-text-line',
		'label' => 'Documentos',
		'url'   => home_url( '/cena-rio/docs/' ),
		'slug'  => 'cena-docs',
	),
);

// Check if user can access Cena::rio.
$can_access_cenario = current_user_can( 'manage_options' ) || is_user_logged_in();
?>
<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
	<title>Cena::rio · Calendário Avançado</title>

	<style>
		/* ============================================================
			CSS VARIABLES - Design Tokens
			============================================================ */
		:root {
			--bg: #f8fafc;
			--muted: #64748b;
			--accent: #f97316;
			--accent-strong: #ea580c;
			--confirmed: #10b981;
			--published: #3b82f6;
			--card: #ffffff;
			--border: #e6eef6;
			--shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
			--nav-height: 70px;
		}

		/* ============================================================
			BASE STYLES
			============================================================ */
		html, body {
			height: 100%;
			margin: 0;
			background: var(--bg);
			font-family: Inter, system-ui, Arial;
		}

		/* ============================================================
			LAYOUT: APP SHELL
			============================================================ */
		.app {
			display: flex;
			min-height: 100vh;
			gap: 0;
		}

		/* ============================================================
			SIDEBAR: DESKTOP LEFT NAVIGATION
			============================================================ */
		aside.leftbar {
			width: 14rem;
			background: #fff;
			border-right: 1px solid #eef2f7;
			display: flex;
			flex-direction: column;
			z-index: 40;
		}

		@media (max-width: 980px) {
			aside.leftbar { display: none; }
		}

		.sidebar-header {
			height: 4rem;
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 0 1.5rem;
			border-bottom: 1px solid #eef2f7;
		}

		.sidebar-logo {
			height: 2.25rem;
			width: 2.25rem;
			border-radius: 0.75rem;
			background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
		}

		.sidebar-nav {
			flex: 1;
			padding: 1.5rem 1rem;
			overflow-y: auto;
		}

		.sidebar-label {
			padding: 0 0.5rem;
			margin-bottom: 0.5rem;
			font-size: 0.6rem;
			font-weight: 500;
			text-transform: uppercase;
			letter-spacing: 0.12em;
			color: #94a3b8;
		}

		.sidebar-link {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 0.625rem 0.75rem;
			margin-bottom: 0.25rem;
			border-radius: 0.75rem;
			border-left: 2px solid transparent;
			font-size: 0.8125rem;
			color: #64748b;
			text-decoration: none;
			transition: all 0.2s;
		}

		.sidebar-link:hover {
			background-color: #f8fafc;
			color: #0f172a;
		}

		.sidebar-link.active {
			background-color: #f1f5f9;
			color: #0f172a;
			border-left-color: #0f172a;
			font-weight: 600;
		}

		.sidebar-link i { font-size: 1.125rem; }

		.sidebar-user {
			padding: 1rem;
			border-top: 1px solid #eef2f7;
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}

		.sidebar-avatar {
			width: 2rem;
			height: 2rem;
			border-radius: 9999px;
			background: #fed7aa;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 0.75rem;
			font-weight: 700;
			color: #ea580c;
		}

		/* ============================================================
			MAIN CONTENT AREA
			============================================================ */
		main.content {
			flex: 1;
			display: flex;
			flex-direction: column;
			min-height: 100vh;
		}

		/* ============================================================
			HEADER: TOP BAR
			============================================================ */
		header.topbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 12px 20px;
			background: rgba(255, 255, 255, 0.9);
			border-bottom: 1px solid #eef2f7;
			position: sticky;
			top: 0;
			z-index: 60;
			backdrop-filter: blur(6px);
		}

		/* ============================================================
			WORKSPACE: CALENDAR + MAP + EVENTS GRID
			============================================================ */
		.workspace {
			display: grid;
			grid-template-columns: minmax(0, 320px) minmax(0, 1fr);
			grid-template-rows: auto auto;
			grid-template-areas:
				"calendar map"
				"events events";
			gap: 20px;
			padding: 20px;
			max-width: 1400px;
			margin: 0 auto;
			width: 100%;
			box-sizing: border-box;
		}

		@media (max-width: 1100px) {
			.workspace {
				grid-template-columns: 1fr;
				grid-template-rows: auto auto auto;
				grid-template-areas:
					"calendar"
					"events"
					"map";
				padding-bottom: 80px;
			}
		}

		/* ============================================================
			CALENDAR CARD
			============================================================ */
		.calendar-card {
			grid-area: calendar;
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: 12px;
			padding: 12px;
			box-shadow: var(--shadow);
		}

		.weekdays {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 6px;
			text-align: center;
			color: var(--muted);
			font-weight: 700;
			font-size: 12px;
			margin-top: 8px;
		}

		.calendar-grid {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 6px;
			margin-top: 8px;
		}

		.day-btn {
			height: 38px;
			border-radius: 10px;
			border: 1px solid transparent;
			background: transparent;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 400;
			font-size: 12px;
			color: #0f172a;
			cursor: pointer;
			position: relative;
		}

		.day-btn.disabled { opacity: 0.35; cursor: default; }
		.day-btn.selected {
			background: #0f172a;
			color: #fff;
			box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
		}

		.day-dots {
			position: absolute;
			top: 4px;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 3px;
		}

		.day-dot {
			width: 7px;
			height: 7px;
			border-radius: 999px;
			opacity: 0.95;
			background: var(--accent);
		}

		.day-dot.confirmed { background: var(--confirmed); }
		.day-dot.published { background: var(--published); }

		/* ============================================================
			MAP CARD
			============================================================ */
		.map-card {
			grid-area: map;
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: 12px;
			padding: 0;
			box-shadow: var(--shadow);
			overflow: hidden;
			min-height: 260px;
		}

		#footer-map {
			width: 100%;
			height: 100%;
			min-height: 260px;
		}

		/* ============================================================
			EVENTS PANEL
			============================================================ */
		.events-panel {
			grid-area: events;
			min-height: 400px;
		}

		.events-grid {
			display: flex;
			flex-direction: column;
			gap: 12px;
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
			transition: transform .12s ease, box-shadow .12s ease;
			gap: 16px;
		}

		.event-card.expected { border-left: 4px solid var(--accent); }
		.event-card.confirmed { border-left: 4px solid var(--confirmed); }

		.event-info { flex: 1; min-width: 0; }
		.event-title { font-weight: 700; font-size: 16px; color: #0f172a; }
		.event-meta { font-size: 14px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 8px; }

		.event-controls { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
		.event-actions { display: flex; gap: 8px; align-items: center; }

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

		/* ============================================================
			BOTTOM NAVIGATION (MOBILE)
			============================================================ */
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
			background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.98));
			backdrop-filter: blur(8px);
			z-index: 1200;
			box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.06);
		}

		.bottom-nav .nav-btn {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 2px;
			font-size: 11px;
			color: #64748b;
			width: 56px;
			background: none;
			border: none;
			cursor: pointer;
		}

		.bottom-nav .nav-btn.active { color: #0f172a; }
		.bottom-nav .nav-btn i { font-size: 1.25rem; }

		@media (min-width: 980px) {
			.bottom-nav { display: none; }
		}

		/* ============================================================
			BUTTONS
			============================================================ */
		.btn {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border-radius: 8px;
			border: 1px solid transparent;
			background: #111827;
			color: #fff;
			font-weight: 600;
			cursor: pointer;
			font-size: 13px;
			transition: opacity 0.2s;
		}

		.btn:hover { opacity: 0.9; }
		.btn.ghost { background: transparent; color: #475569; border-color: var(--border); }
		.btn.ghost:hover { background: #f1f5f9; color: #0f172a; }
		.btn.small { padding: 6px 12px; font-size: 12px; }

		/* ============================================================
			LEGEND
			============================================================ */
		.legend {
			display: flex;
			gap: 12px;
			align-items: center;
			color: #475569;
			font-size: 13px;
			flex-wrap: wrap;
		}

		.legend-item { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
		.legend-dots { display: flex; gap: 3px; }
		.legend-dot { width: 7px; height: 7px; border-radius: 999px; background: var(--accent); }
		.legend-dot.confirmed { background: var(--confirmed); }
		.legend-dot.published { background: var(--published); }

		/* ============================================================
			UTILITIES
			============================================================ */
		.muted { color: var(--muted); }
		:focus { outline: 3px solid rgba(99, 102, 241, 0.12); outline-offset: 2px; }
	</style>
</head>

<body class="h-full">

<!-- ======================================================================
	APP SHELL CONTAINER
	====================================================================== -->
<div class="app">

	<!-- ==================================================================
		SIDEBAR: DESKTOP LEFT NAVIGATION
		================================================================== -->
	<aside class="leftbar">
		<!-- Header -->
		<div class="sidebar-header">
			<div class="sidebar-logo">
				<i class="ri-slack-fill text-white text-[22px]"></i>
			</div>
			<div class="flex flex-col leading-tight">
				<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
				<span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
			</div>
		</div>

		<!-- Navigation -->
		<nav class="sidebar-nav">
			<div class="sidebar-label">Navegação</div>
			<?php foreach ( $nav_items as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="sidebar-link">
					<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
					<span><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>

			<?php if ( $can_access_cenario ) : ?>
				<div class="sidebar-label" style="margin-top: 1.5rem;">Cena::rio</div>
				<?php foreach ( $cenario_items as $item ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>"
						class="sidebar-link <?php echo ! empty( $item['active'] ) ? 'active' : ''; ?>"
						<?php echo ! empty( $item['active'] ) ? 'aria-current="page"' : ''; ?>>
						<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>

			<div class="sidebar-label" style="margin-top: 1.5rem;">Acesso Rápido</div>
			<a href="<?php echo esc_url( home_url( '/ajustes/' ) ); ?>" class="sidebar-link">
				<i class="ri-settings-6-line"></i>
				<span>Ajustes</span>
			</a>
		</nav>

		<!-- User Block -->
		<div class="sidebar-user">
			<div class="sidebar-avatar"><?php echo esc_html( $user_data['initials'] ); ?></div>
			<div class="flex flex-col leading-tight">
				<span class="text-[14px] font-bold text-slate-900"><?php echo esc_html( $user_data['display_name'] ); ?></span>
				<?php if ( $user_data['username'] ) : ?>
					<span class="text-[12px] text-slate-500"><?php echo esc_html( $user_data['username'] ); ?></span>
				<?php endif; ?>
			</div>
			<a href="<?php echo esc_url( wp_logout_url() ); ?>" class="ml-auto text-slate-400 hover:text-slate-600" title="<?php esc_attr_e( 'Sair', 'apollo-events-manager' ); ?>">
				<i class="ri-logout-box-r-line text-[18px]"></i>
			</a>
		</div>
	</aside>

	<!-- ==================================================================
		MAIN CONTENT
		================================================================== -->
	<main class="content">

		<!-- ================================================================
			HEADER: TOP BAR
			================================================================ -->
		<header class="topbar">
			<div style="display:flex;align-items:center;gap:12px">
				<div>
					<div class="text-lg font-bold">Calendário Mensal</div>
					<div class="text-sm muted">Planejamento da cena · Cena::rio</div>
				</div>
			</div>

			<div style="display:flex;gap:8px;align-items:center">
				<button id="prev-month" class="btn ghost small" aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-left-s-line"></i>
				</button>
				<div id="month-label" style="font-weight:800;min-width:140px;text-align:center"><?php echo esc_html( $month_label ); ?></div>
				<button id="next-month" class="btn ghost small" aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events-manager' ); ?>">
					<i class="ri-arrow-right-s-line"></i>
				</button>
			</div>
		</header>

		<!-- ================================================================
			WORKSPACE: CALENDAR + MAP + EVENTS
			================================================================ -->
		<section class="workspace">

			<!-- CALENDAR WIDGET -->
			<aside class="calendar-card" aria-label="<?php esc_attr_e( 'Calendário', 'apollo-events-manager' ); ?>">
				<div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
					<div style="font-weight:700"><?php esc_html_e( 'Calendário', 'apollo-events-manager' ); ?></div>
					<div class="legend">
						<div class="legend-item">
							<div class="legend-dots"><span class="legend-dot"></span></div>
							<span class="text-[10px]"><?php esc_html_e( 'Previsto', 'apollo-events-manager' ); ?></span>
						</div>
						<div class="legend-item">
							<div class="legend-dots"><span class="legend-dot confirmed"></span></div>
							<span class="text-[10px]"><?php esc_html_e( 'Confirmado', 'apollo-events-manager' ); ?></span>
						</div>
						<div class="legend-item">
							<div class="legend-dots"><span class="legend-dot published"></span></div>
							<span class="text-[10px]"><?php esc_html_e( 'Público', 'apollo-events-manager' ); ?></span>
						</div>
					</div>
				</div>

				<div class="weekdays" aria-hidden="true">
					<div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
				</div>

				<div id="calendar-grid" class="calendar-grid" role="grid" aria-label="<?php esc_attr_e( 'Calendário mensal', 'apollo-events-manager' ); ?>"></div>

				<div style="margin-top:12px;display:flex;gap:8px;justify-content:space-between;align-items:center">
					<button id="btn-add-event" class="btn"><i class="ri-add-line"></i> <?php esc_html_e( 'Novo Evento', 'apollo-events-manager' ); ?></button>
					<div style="font-size:10px;color:var(--muted)"><?php esc_html_e( 'Toque em um dia', 'apollo-events-manager' ); ?></div>
				</div>
			</aside>

			<!-- MAP WIDGET -->
			<div class="map-card" aria-label="<?php esc_attr_e( 'Mapa de eventos', 'apollo-events-manager' ); ?>">
				<div id="footer-map"></div>
			</div>

			<!-- EVENTS PANEL -->
			<section class="events-panel">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
					<div>
						<div id="selected-day" style="font-weight:800; font-size: 1.1rem;"><?php esc_html_e( 'Todos os eventos', 'apollo-events-manager' ); ?></div>
						<div class="muted"><?php esc_html_e( 'Lista de produções', 'apollo-events-manager' ); ?></div>
					</div>
					<div style="display:flex;gap:8px;align-items:center">
						<button id="toggle-map" class="btn ghost small">
							<i class="ri-map-pin-line"></i> <?php esc_html_e( 'Centralizar no Mapa', 'apollo-events-manager' ); ?>
						</button>
					</div>
				</div>

				<div id="events-grid" class="events-grid" aria-live="polite">
					<!-- Events will be populated by JavaScript -->
				</div>
			</section>

		</section>

		<!-- ================================================================
			BOTTOM NAVIGATION (MOBILE ONLY)
			================================================================ -->
		<div class="bottom-nav" role="navigation" aria-label="<?php esc_attr_e( 'Navegação inferior', 'apollo-events-manager' ); ?>">
			<button class="nav-btn active" onclick="location.href='<?php echo esc_url( home_url( '/cena-rio/' ) ); ?>'">
				<i class="ri-calendar-line"></i><span><?php esc_html_e( 'Agenda', 'apollo-events-manager' ); ?></span>
			</button>
			<button class="nav-btn" onclick="location.href='<?php echo esc_url( home_url( '/fornecedores/' ) ); ?>'">
				<i class="ri-bar-chart-grouped-line"></i><span><?php esc_html_e( 'Pro', 'apollo-events-manager' ); ?></span>
			</button>
			<div style="position:relative;top:-18px">
				<button id="fab-add" class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg">
					<i class="ri-add-line text-3xl"></i>
				</button>
			</div>
			<button class="nav-btn" onclick="location.href='<?php echo esc_url( home_url( '/cena-rio/docs/' ) ); ?>'">
				<i class="ri-file-text-line"></i><span><?php esc_html_e( 'Docs', 'apollo-events-manager' ); ?></span>
			</button>
			<button class="nav-btn" onclick="location.href='<?php echo esc_url( home_url( '/ajustes/' ) ); ?>'">
				<i class="ri-settings-3-line"></i><span><?php esc_html_e( 'Ajustes', 'apollo-events-manager' ); ?></span>
			</button>
		</div>

	</main>

</div>

<?php wp_footer(); ?>

<!-- ======================================================================
	SCRIPTS: Calendar, Events, and Map Logic
	====================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	/* =================================================================
		DATA: Sample Events (Replace with AJAX/PHP data in production)
		================================================================= */
	const STORAGE_KEY = 'cenario_events_v3';
	const DEFAULT_EVENTS = {
		"2025-01-15": [
			{ id: "e1", title: "Festa Eletrônica Underground", venue: "Lapa", time: "23:00", tag: "Techno", status: "expected", lat: -22.9122, lng: -43.1806 },
		],
		"2025-01-22": [
			{ id: "e2", title: "DJ Set na Cobertura", venue: "Ipanema", time: "20:00", tag: "House", status: "confirmed", lat: -22.9838, lng: -43.2096 },
		],
		"2025-01-28": [
			{ id: "e3", title: "Festival Rio Beats", venue: "Zona Portuária", time: "16:00", tag: "Festival", status: "published", lat: -22.8968, lng: -43.1805 },
		]
	};

	function loadEvents() {
		try {
			const raw = localStorage.getItem(STORAGE_KEY);
			if (!raw) return JSON.parse(JSON.stringify(DEFAULT_EVENTS));
			return JSON.parse(raw);
		} catch (e) { return JSON.parse(JSON.stringify(DEFAULT_EVENTS)); }
	}

	let events = loadEvents();

	/* =================================================================
		STATE: Calendar View
		================================================================= */
	const weekDays = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
	const months = ["jan", "fev", "mar", "abr", "mai", "jun", "jul", "ago", "set", "out", "nov", "dez"];
	const monthsFull = ["JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"];
	let viewYear = new Date().getFullYear();
	let viewMonth = new Date().getMonth();
	let selectedDate = null;

	/* =================================================================
		DOM REFS
		================================================================= */
	const gridEl = document.getElementById('calendar-grid');
	const monthLabelEl = document.getElementById('month-label');
	const selectedDayEl = document.getElementById('selected-day');
	const eventsGridEl = document.getElementById('events-grid');
	const prevMonthBtn = document.getElementById('prev-month');
	const nextMonthBtn = document.getElementById('next-month');
	const btnAdd = document.getElementById('btn-add-event');
	const fabAdd = document.getElementById('fab-add');
	const footerMapEl = document.getElementById('footer-map');

	/* =================================================================
		MAP: Leaflet Initialization
		================================================================= */
	let map, markersLayer;
	function initMap() {
		try {
			if (!footerMapEl || typeof L === 'undefined') return;
			map = L.map('footer-map', { zoomControl: true }).setView([-22.9068, -43.1729], 12);
			// STRICT MODE: Use central tileset provider
			if (window.ApolloMapTileset) {
				window.ApolloMapTileset.apply(map);
				window.ApolloMapTileset.ensureAttribution(map);
			} else {
				console.warn('[Apollo] ApolloMapTileset not loaded, using fallback');
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; OpenStreetMap'
				}).addTo(map);
			}
			markersLayer = L.layerGroup().addTo(map);
		} catch (e) {
			console.warn('Leaflet init failed', e);
			if (footerMapEl) footerMapEl.style.display = 'none';
		}
	}

	function updateMapMarkers() {
		if (!markersLayer) return;
		markersLayer.clearLayers();
		const coords = [];
		Object.keys(events).forEach(k => {
			(events[k] || []).forEach(ev => {
				if (!selectedDate || selectedDate === k) {
					if (ev.lat && ev.lng) {
						const color = ev.status === 'confirmed' ? '#10b981' : ev.status === 'published' ? '#3b82f6' : '#f97316';
						const circle = L.circleMarker([ev.lat, ev.lng], {
							radius: 8,
							color: color,
							fillColor: color,
							fillOpacity: 0.9
						});
						circle.bindPopup('<strong>' + escapeHtml(ev.title) + '</strong><br>' + escapeHtml(ev.venue));
						circle.addTo(markersLayer);
						coords.push([ev.lat, ev.lng]);
					}
				}
			});
		});
		if (coords.length && map) {
			map.fitBounds(L.latLngBounds(coords).pad(0.3));
		}
	}

	/* =================================================================
		CALENDAR: Render Functions
		================================================================= */
	function updateMonthLabel() {
		if (monthLabelEl) {
			monthLabelEl.textContent = monthsFull[viewMonth] + ' ' + viewYear;
		}
	}

	function renderCalendar() {
		if (!gridEl) return;
		gridEl.innerHTML = '';
		const firstDay = new Date(viewYear, viewMonth, 1).getDay();
		const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

		// Empty cells before first day
		for (let i = 0; i < firstDay; i++) {
			const empty = document.createElement('button');
			empty.className = 'day-btn disabled';
			empty.disabled = true;
			gridEl.appendChild(empty);
		}

		// Day cells
		for (let d = 1; d <= daysInMonth; d++) {
			const dateStr = viewYear + '-' + String(viewMonth + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
			const dayEvents = events[dateStr] || [];
			const btn = document.createElement('button');
			btn.className = 'day-btn';
			btn.textContent = d;
			btn.dataset.date = dateStr;

			if (selectedDate === dateStr) {
				btn.classList.add('selected');
			}

			// Add event dots
			if (dayEvents.length > 0) {
				const dotsDiv = document.createElement('div');
				dotsDiv.className = 'day-dots';
				dayEvents.slice(0, 3).forEach(ev => {
					const dot = document.createElement('span');
					dot.className = 'day-dot';
					if (ev.status === 'confirmed') dot.classList.add('confirmed');
					if (ev.status === 'published') dot.classList.add('published');
					dotsDiv.appendChild(dot);
				});
				btn.appendChild(dotsDiv);
			}

			btn.addEventListener('click', () => selectDate(dateStr));
			gridEl.appendChild(btn);
		}
	}

	function selectDate(dateStr) {
		selectedDate = selectedDate === dateStr ? null : dateStr;
		renderCalendar();
		renderEvents();
		updateMapMarkers();

		if (selectedDayEl) {
			if (selectedDate) {
				const d = new Date(selectedDate + 'T12:00:00');
				selectedDayEl.textContent = d.toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' });
			} else {
				selectedDayEl.textContent = 'Todos os eventos';
			}
		}
	}

	/* =================================================================
		EVENTS: Render Functions
		================================================================= */
	function renderEvents() {
		if (!eventsGridEl) return;
		eventsGridEl.innerHTML = '';

		let eventsToShow = [];
		if (selectedDate && events[selectedDate]) {
			eventsToShow = events[selectedDate];
		} else {
			Object.values(events).forEach(arr => {
				eventsToShow = eventsToShow.concat(arr);
			});
		}

		if (eventsToShow.length === 0) {
			eventsGridEl.innerHTML = '<div style="text-align:center;padding:2rem;color:#64748b;">Nenhum evento encontrado.</div>';
			return;
		}

		eventsToShow.forEach(ev => {
			const card = document.createElement('article');
			card.className = 'event-card ' + (ev.status || 'expected');
			card.innerHTML = `
				<div class="event-info">
					<div class="event-title">${escapeHtml(ev.title)}</div>
					<div class="event-meta">
						<i class="ri-time-line"></i> ${escapeHtml(ev.time)} ·
						<i class="ri-map-pin-line"></i> ${escapeHtml(ev.venue)}
					</div>
				</div>
				<div class="event-controls">
					<span class="text-xs px-2 py-1 rounded-full ${ev.status === 'confirmed' ? 'bg-green-100 text-green-700' : ev.status === 'published' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'}">${ev.status === 'confirmed' ? 'Confirmado' : ev.status === 'published' ? 'Público' : 'Previsto'}</span>
					<div class="event-actions">
						<button class="btn ghost small"><i class="ri-edit-line"></i></button>
						<button class="btn ghost small"><i class="ri-delete-bin-line"></i></button>
					</div>
				</div>
			`;
			eventsGridEl.appendChild(card);
		});
	}

	/* =================================================================
		UTILITIES
		================================================================= */
	function escapeHtml(str) {
		if (!str) return '';
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	/* =================================================================
		EVENT LISTENERS
		================================================================= */
	if (prevMonthBtn) {
		prevMonthBtn.addEventListener('click', () => {
			viewMonth--;
			if (viewMonth < 0) { viewMonth = 11; viewYear--; }
			updateMonthLabel();
			renderCalendar();
		});
	}

	if (nextMonthBtn) {
		nextMonthBtn.addEventListener('click', () => {
			viewMonth++;
			if (viewMonth > 11) { viewMonth = 0; viewYear++; }
			updateMonthLabel();
			renderCalendar();
		});
	}

	if (btnAdd) {
		btnAdd.addEventListener('click', () => {
			alert('<?php echo esc_js( __( 'Criar novo evento - Funcionalidade em desenvolvimento', 'apollo-events-manager' ) ); ?>');
		});
	}

	if (fabAdd) {
		fabAdd.addEventListener('click', () => {
			alert('<?php echo esc_js( __( 'Criar novo evento - Funcionalidade em desenvolvimento', 'apollo-events-manager' ) ); ?>');
		});
	}

	/* =================================================================
		INIT
		================================================================= */
	updateMonthLabel();
	renderCalendar();
	renderEvents();
	initMap();
	setTimeout(updateMapMarkers, 500);
});
</script>

</body>
</html>
