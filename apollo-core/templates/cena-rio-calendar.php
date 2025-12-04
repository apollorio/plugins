<?php
// phpcs:ignoreFile
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

// Check if user has permission to view
if ( ! Apollo_Cena_Rio_Roles::user_can_submit() ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
}

$current_user = wp_get_current_user();
$can_moderate = Apollo_Cena_Rio_Roles::user_can_moderate();

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Tailwind (CDN for dev).
		wp_enqueue_script(
			'tailwindcss',
			'https://cdn.tailwindcss.com',
			array(),
			'3.4.0',
			false
		);

		// Leaflet.
		wp_enqueue_style(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);
		wp_enqueue_script(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		// Inline calendar-specific styles.
		$calendar_css = '
			:root{
				--bg:#f8fafc;
				--muted:#64748b;
				--accent:#f97316;
				--accent-strong:#ea580c;
				--confirmed:#10b981;
				--card:#ffffff;
				--border:#e6eef6;
				--shadow: 0 8px 30px rgba(15,23,42,0.06);
			}
			html,body{height:100%;margin:0;background:var(--bg);font-family:Inter,system-ui,Arial;}

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

