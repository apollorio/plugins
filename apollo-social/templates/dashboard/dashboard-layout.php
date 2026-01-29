<?php
/**
 * Apollo Dashboard Layout - Matches Approved Design
 *
 * Based on approved design: dashboard - overview.html
 * Uses .app container with sidebar + main content layout.
 *
 * @package ApolloSocial
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load components
$components_dir = __DIR__ . '/components/';
require_once $components_dir . 'sidebar-provider.php';
require_once $components_dir . 'app-sidebar.php';
require_once $components_dir . 'site-header.php';
require_once $components_dir . 'section-cards.php';
require_once $components_dir . 'data-table.php';

/**
 * Render the complete dashboard page.
 *
 * @param array $args {
 *     Page configuration.
 *     @type string $title        Page title.
 *     @type array  $breadcrumbs  Breadcrumb items.
 *     @type array  $cards        Stats cards data.
 *     @type array  $table        Data table configuration.
 *     @type string $content      Custom content (alternative to cards/table).
 *     @type array  $sidebar_nav  Custom sidebar navigation.
 * }
 */
function apollo_render_dashboard_page( array $args = array() ) {
	$defaults = array(
		'title'       => 'Dashboard',
		'breadcrumbs' => array(
			array(
				'label' => 'Início',
				'url'   => home_url( '/' ),
			),
			array(
				'label' => 'Dashboard',
				'url'   => '',
			),
		),
		'cards'       => array(),
		'table'       => array(),
		'content'     => '',
		'sidebar_nav' => array(),
	);
	$args     = wp_parse_args( $args, $defaults );

	// Start output
	apollo_dashboard_head();
	?>

	<body class="ap-dashboard-body">
		<div id="app" class="ap-dashboard">

		<!-- ====================================================================
			[HEADER] Top Navigation Bar
			==================================================================== -->
		<header class="ap-dash-header">
			<div class="ap-dash-header-inner">
			<!-- Logo -->
			<div class="ap-dash-logo">
				<a href="/" class="ap-logo-link">
				<div class="ap-logo-icon">
					<i class="ri-rocket-2-fill"></i>
				</div>
				<span class="ap-logo-text">Apollo<span class="ap-logo-accent">::</span>Dashboard</span>
				</a>
			</div>

			<!-- Right Actions -->
			<div class="ap-dash-actions">
				<!-- Dark Mode Toggle -->
				<button class="ap-btn-icon ap-dark-mode-toggle" data-ap-tooltip="Alternar tema">
				<i class="ri-moon-line"></i>
				</button>

				<!-- Notifications - Opens navbar modal -->
				<button class="ap-btn-icon" data-apollo-notif-trigger data-ap-tooltip="Notificações">
				<i class="ri-notification-3-line"></i>
				</button>

				<!-- User Menu -->
				<div class="ap-user-menu">
				<button class="ap-user-menu-trigger">
					<div class="ap-avatar ap-avatar-sm">
					<?php echo get_avatar( get_current_user_id(), 32 ); ?>
					</div>
				</button>
				</div>
			</div>
			</div>
		</header>

		<!-- Main App Layout -->
		<div class="ap-dash-layout">

			<!-- ====================================================================
				[SIDEBAR] Left Navigation
				==================================================================== -->
			<?php
			apollo_render_app_sidebar(
				array(
					'nav_items' => ! empty( $args['sidebar_nav'] ) ? $args['sidebar_nav'] : apollo_get_default_nav_items(),
				)
			);
			?>

			<!-- Mobile Sidebar Toggle -->
			<button class="ap-mobile-sidebar-toggle" id="mobileSidebarToggle">
			<i class="ri-menu-line"></i>
			</button>

			<!-- ====================================================================
				[CONTENT] Main Content Area
				==================================================================== -->
			<main class="ap-dash-content">

			<!-- ================================================================
				DASHBOARD CONTENT
				================================================================ -->
			<div class="ap-page-header">
				<h1 class="ap-heading-1"><?php echo esc_html( $args['title'] ); ?></h1>
				<p class="ap-text-muted">Bem-vindo(a) de volta, <?php echo esc_html( wp_get_current_user()->display_name ); ?></p>
			</div>

			<?php if ( ! empty( $args['content'] ) ) : ?>
				<!-- Custom Content -->
				<div class="ap-section">
                <?php echo $args['content']; // phpcs:ignore -- Custom content ?>
				</div>
			<?php else : ?>

				<!-- Stats Grid -->
				<?php if ( ! empty( $args['cards'] ) ) : ?>
				<div class="ap-stats-grid">
					<?php foreach ( $args['cards'] as $card ) : ?>
					<div class="ap-stat-card">
						<div class="ap-stat-header">
						<div class="ap-stat-info">
							<p class="ap-stat-label"><?php echo esc_html( $card['label'] ); ?></p>
							<p class="ap-stat-value"><?php echo esc_html( $card['value'] ); ?></p>
						</div>
						<div class="ap-stat-icon ap-stat-icon-<?php echo esc_attr( $card['color'] ?? 'blue' ); ?>">
							<i class="<?php echo esc_attr( $card['icon'] ); ?>"></i>
						</div>
						</div>
						<div class="ap-stat-footer">
						<span class="ap-stat-change ap-stat-change-<?php echo $card['change_type'] ?? 'up'; ?>">
							<i class="ri-arrow-<?php echo $card['change_type'] === 'down' ? 'down' : 'up'; ?>-line"></i>
							<?php echo esc_html( $card['change'] ); ?>
						</span>
						<span class="ap-stat-period"><?php echo esc_html( $card['period'] ); ?></span>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<!-- Chart Area (Optional) -->
				<?php if ( ! empty( $args['chart'] ) ) : ?>
				<div class="ap-card ap-card-chart">
					<div class="ap-card-header">
					<h2 class="ap-card-title"><?php echo esc_html( $args['chart']['title'] ); ?></h2>
					<select class="ap-select-mini">
						<option>Últimos 7 dias</option>
						<option>Últimos 30 dias</option>
						<option>Últimos 90 dias</option>
					</select>
					</div>
					<div class="ap-card-body">
					<canvas id="progressChart" height="200"></canvas>
					</div>
				</div>
				<?php endif; ?>

				<!-- Data Table -->
				<?php if ( ! empty( $args['table'] ) ) : ?>
					<?php apollo_render_data_table( $args['table'] ); ?>
				<?php endif; ?>

			<?php endif; ?>

			</main>
		</div>
		</div>

	<?php
	apollo_dashboard_footer();
}

/**
 * Output dashboard head/styles.
 */
/**
 * Output dashboard head/styles - Matches Approved Design
 */
function apollo_dashboard_head() {
	?>
	<!DOCTYPE html>
	<html lang="pt-BR">
	<head>
		<meta charset="UTF-8">
		<title>Apollo :: Dashboard</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- Apollo CDN Loader - auto-loads CSS, icons, dark-mode, etc -->
		<script src="https://assets.apollo.rio.br/index.min.js"></script>

		<!-- Chart.js for Analytics -->
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>

		<!-- Icon styles from approved design -->
		<style>
			i[class] {
				display: inline-block;
				width: 1em;
				height: 1em;
				background-color: currentColor;
				mask-size: contain;
				mask-repeat: no-repeat;
				mask-position: center;
				-webkit-mask-size: contain;
				-webkit-mask-repeat: no-repeat;
				-webkit-mask-position: center;
			}
		</style>
		<style>
			.ri-rocket-2-fill {
				mask-image: url(https://assets.apollo.rio.br/i/rocket-2-s.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/rocket-2-s.svg);
			}
		</style>
		<style>
			.ri-moon-line {
				mask-image: url(https://assets.apollo.rio.br/i/moon-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/moon-v.svg);
			}
		</style>
		<style>
			.ri-notification-3-line {
				mask-image: url(https://assets.apollo.rio.br/i/notification-3-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/notification-3-v.svg);
			}
		</style>
		<style>
			.ri-dashboard-3-line {
				mask-image: url(https://assets.apollo.rio.br/i/dashboard-3-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/dashboard-3-v.svg);
			}
		</style>
		<style>
			.ri-task-line {
				mask-image: url(https://assets.apollo.rio.br/i/task-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/task-v.svg);
			}
		</style>
		<style>
			.ri-bar-chart-2-line {
				mask-image: url(https://assets.apollo.rio.br/i/bar-chart-2-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/bar-chart-2-v.svg);
			}
		</style>
		<style>
			.ri-git-branch-line {
				mask-image: url(https://assets.apollo.rio.br/i/git-branch-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/git-branch-v.svg);
			}
		</style>
		<style>
			.ri-calendar-schedule-line {
				mask-image: url(https://assets.apollo.rio.br/i/calendar-schedule-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/calendar-schedule-v.svg);
			}
		</style>
		<style>
			.ri-team-line {
				mask-image: url(https://assets.apollo.rio.br/i/team-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/team-v.svg);
			}
		</style>
		<style>
			.ri-cpu-line {
				mask-image: url(https://assets.apollo.rio.br/i/cpu-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/cpu-v.svg);
			}
		</style>
		<style>
			.ri-menu-line {
				mask-image: url(https://assets.apollo.rio.br/i/menu-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/menu-v.svg);
			}
		</style>
		<style>
			.ri-calendar-event-line {
				mask-image: url(https://assets.apollo.rio.br/i/calendar-event-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/calendar-event-v.svg);
			}
		</style>
		<style>
			.ri-arrow-up-line {
				mask-image: url(https://assets.apollo.rio.br/i/arrow-up-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/arrow-up-v.svg);
			}
		</style>
		<style>
			.ri-checkbox-circle-line {
				mask-image: url(https://assets.apollo.rio.br/i/checkbox-circle-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/checkbox-circle-v.svg);
			}
		</style>
		<style>
			.ri-group-line {
				mask-image: url(https://assets.apollo.rio.br/i/group-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/group-v.svg);
			}
		</style>
		<style>
			.ri-alarm-warning-line {
				mask-image: url(https://assets.apollo.rio.br/i/alarm-warning-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/alarm-warning-v.svg);
			}
		</style>
		<style>
			.ri-error-warning-line {
				mask-image: url(https://assets.apollo.rio.br/i/error-warning-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/error-warning-v.svg);
			}
		</style>
		<style>
			.ri-file-text-line {
				mask-image: url(https://assets.apollo.rio.br/i/file-text-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/file-text-v.svg);
			}
		</style>
		<style>
			.ri-check-line {
				mask-image: url(https://assets.apollo.rio.br/i/check-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/check-v.svg);
			}
		</style>
		<style>
			.ri-chat-3-line {
				mask-image: url(https://assets.apollo.rio.br/i/chat-3-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/chat-3-v.svg);
			}
		</style>
		<style>
			.ri-user-add-line {
				mask-image: url(https://assets.apollo.rio.br/i/user-add-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/user-add-v.svg);
			}
		</style>
		<style>
			.ri-add-line {
				mask-image: url(https://assets.apollo.rio.br/i/add-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/add-v.svg);
			}
		</style>
		<style>
			.ri-search-line {
				mask-image: url(https://assets.apollo.rio.br/i/search-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/search-v.svg);
			}
		</style>
		<style>
			.ri-time-line {
				mask-image: url(https://assets.apollo.rio.br/i/time-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/time-v.svg);
			}
		</style>
		<style>
			.ri-line-chart-line {
				mask-image: url(https://assets.apollo.rio.br/i/line-chart-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/line-chart-v.svg);
			}
		</style>
		<style>
			.ri-money-dollar-circle-line {
				mask-image: url(https://assets.apollo.rio.br/i/money-dollar-circle-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/money-dollar-circle-v.svg);
			}
		</style>
		<style>
			.ri-download-line {
				mask-image: url(https://assets.apollo.rio.br/i/download-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/download-v.svg);
			}
		</style>
		<style>
			.ri-zoom-out-line {
				mask-image: url(https://assets.apollo.rio.br/i/zoom-out-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/zoom-out-v.svg);
			}
		</style>
		<style>
			.ri-zoom-in-line {
				mask-image: url(https://assets.apollo.rio.br/i/zoom-in-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/zoom-in-v.svg);
			}
		</style>
		<style>
			.ri-arrow-left-s-line {
				mask-image: url(https://assets.apollo.rio.br/i/arrow-left-s-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/arrow-left-s-v.svg);
			}
		</style>
		<style>
			.ri-arrow-right-s-line {
				mask-image: url(https://assets.apollo.rio.br/i/arrow-right-s-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/arrow-right-s-v.svg);
			}
		</style>
		<style>
			.ri-arrow-down-s-line {
				mask-image: url(https://assets.apollo.rio.br/i/arrow-down-s-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/arrow-down-s-v.svg);
			}
		</style>
		<style>
			.ri-vidicon-line {
				mask-image: url(https://assets.apollo.rio.br/i/vidicon-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/vidicon-v.svg);
			}
		</style>
		<style>
			.ri-phone-line {
				mask-image: url(https://assets.apollo.rio.br/i/phone-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/phone-v.svg);
			}
		</style>
		<style>
			.ri-emotion-line {
				mask-image: url(https://assets.apollo.rio.br/i/emotion-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/emotion-v.svg);
			}
		</style>
		<style>
			.ri-send-plane-fill {
				mask-image: url(https://assets.apollo.rio.br/i/send-plane-s.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/send-plane-s.svg);
			}
		</style>
		<style>
			.ri-close-line {
				mask-image: url(https://assets.apollo.rio.br/i/close-v.svg);
				-webkit-mask-image: url(https://assets.apollo.rio.br/i/close-v.svg);
			}
		</style>

		<?php wp_head(); ?>
	</head>
	<?php
}

/**
 * Output dashboard footer/scripts.
 */
function apollo_dashboard_footer() {
	?>
	<?php wp_footer(); ?>

	<!-- Dashboard JavaScript -->
	<script>
	(function() {
		'use strict';

		// Sidebar toggle
		document.querySelectorAll('.apollo-sidebar-trigger').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var provider = document.querySelector('.apollo-sidebar-provider');
				var currentState = provider.getAttribute('data-sidebar-state');
				var newState = currentState === 'expanded' ? 'collapsed' : 'expanded';
				provider.setAttribute('data-sidebar-state', newState);
				localStorage.setItem('apollo-sidebar-state', newState);
			});
		});

		// Restore sidebar state
		var savedState = localStorage.getItem('apollo-sidebar-state');
		if (savedState) {
			var provider = document.querySelector('.apollo-sidebar-provider');
			if (provider) {
				provider.setAttribute('data-sidebar-state', savedState);
			}
		}

		// Mobile sidebar
		document.querySelectorAll('[data-mobile-sidebar-open]').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var mobileSidebar = document.querySelector('.apollo-sidebar-mobile');
				if (mobileSidebar) {
					mobileSidebar.setAttribute('data-active', 'true');
					document.body.style.overflow = 'hidden';
				}
			});
		});

		document.querySelectorAll('[data-sidebar-close]').forEach(function(el) {
			el.addEventListener('click', function() {
				var mobileSidebar = document.querySelector('.apollo-sidebar-mobile');
				if (mobileSidebar) {
					mobileSidebar.removeAttribute('data-active');
					document.body.style.overflow = '';
				}
			});
		});

		// Dropdowns
		document.querySelectorAll('[data-dropdown]').forEach(function(dropdown) {
			var trigger = dropdown.querySelector('[data-dropdown-trigger]');
			var content = dropdown.querySelector('[data-dropdown-content]');

			if (trigger && content) {
				trigger.addEventListener('click', function(e) {
					e.stopPropagation();
					var isActive = content.getAttribute('data-active') === 'true';

					// Close all other dropdowns
					document.querySelectorAll('[data-dropdown-content]').forEach(function(d) {
						d.removeAttribute('data-active');
					});

					if (!isActive) {
						content.setAttribute('data-active', 'true');
					}
				});
			}
		});

		// User menu
		document.querySelectorAll('[data-user-menu]').forEach(function(menu) {
			var trigger = menu.querySelector('[data-user-menu-trigger]');
			var dropdown = menu.querySelector('[data-user-dropdown]');

			if (trigger && dropdown) {
				trigger.addEventListener('click', function(e) {
					e.stopPropagation();
					var isActive = dropdown.getAttribute('data-active') === 'true';
					dropdown.setAttribute('data-active', isActive ? 'false' : 'true');
				});
			}
		});

		// Close dropdowns on outside click
		document.addEventListener('click', function() {
			document.querySelectorAll('[data-dropdown-content], [data-user-dropdown]').forEach(function(d) {
				d.removeAttribute('data-active');
			});
		});

		// Theme toggle
		document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				document.documentElement.classList.toggle('dark');
				localStorage.setItem('apollo-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
			});
		});

		// Restore theme
		var savedTheme = localStorage.getItem('apollo-theme');
		if (savedTheme === 'dark') {
			document.documentElement.classList.add('dark');
		}

		// Table search
		document.querySelectorAll('[data-table-search]').forEach(function(input) {
			var tableId = input.getAttribute('data-table-search');
			var table = document.getElementById(tableId);

			if (table) {
				input.addEventListener('input', function() {
					var query = this.value.toLowerCase();
					var rows = table.querySelectorAll('tbody tr[data-row-index]');

					rows.forEach(function(row) {
						var text = row.textContent.toLowerCase();
						row.style.display = text.includes(query) ? '' : 'none';
					});
				});
			}
		});

		// Table sorting
		document.querySelectorAll('th[data-sortable]').forEach(function(th) {
			th.addEventListener('click', function() {
				var table = this.closest('table');
				var tbody = table.querySelector('tbody');
				var rows = Array.from(tbody.querySelectorAll('tr[data-row-index]'));
				var column = this.getAttribute('data-column');
				var colIndex = Array.from(this.parentElement.children).indexOf(this);
				var isAsc = this.getAttribute('data-sort') !== 'asc';

				// Reset all sort indicators
				table.querySelectorAll('th[data-sortable]').forEach(function(h) {
					h.removeAttribute('data-sort');
				});
				this.setAttribute('data-sort', isAsc ? 'asc' : 'desc');

				rows.sort(function(a, b) {
					var aVal = a.children[colIndex].textContent.trim();
					var bVal = b.children[colIndex].textContent.trim();

					// Try numeric sort
					var aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
					var bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));

					if (!isNaN(aNum) && !isNaN(bNum)) {
						return isAsc ? aNum - bNum : bNum - aNum;
					}

					return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
				});

				rows.forEach(function(row) {
					tbody.appendChild(row);
				});
			});
		});

	})();
	</script>
	</body>
	</html>
	<?php
}

/**
 * Render an interactive chart area.
 *
 * @param array $chart Chart configuration.
 */
function apollo_render_chart_area( array $chart ) {
	$title = $chart['title'] ?? 'Atividade';
	$data  = $chart['data'] ?? array();
	?>
	<div class="rounded-xl border bg-card p-4 lg:p-6">
		<div class="flex items-center justify-between mb-4">
			<div>
				<h3 class="text-lg font-semibold"><?php echo esc_html( $title ); ?></h3>
				<p class="text-sm text-muted-foreground">Últimos 30 dias</p>
			</div>
			<div class="flex items-center gap-2">
				<button class="inline-flex h-8 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-accent">
					<span>Período</span>
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m6 9 6 6 6-6"/>
					</svg>
				</button>
			</div>
		</div>
		<div class="h-64 flex items-center justify-center text-muted-foreground">
			<!-- Chart placeholder - integrate with Chart.js or similar -->
			<div class="text-center">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2 opacity-50">
					<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>
				</svg>
				<p class="text-sm">Gráfico de atividade</p>
			</div>
		</div>
	</div>
	<?php
}
