<?php
/**
 * Suppliers List Template - Canvas Mode
 *
 * Displays the Cena-Rio suppliers catalog with filters and modal-based detail view.
 * Standalone HTML template (no get_header/get_footer).
 *
 * @package Apollo\Templates\CenaRio
 * @since   1.0.0
 */

declare( strict_types = 1 );

use Apollo\Modules\Suppliers\SuppliersModule;
use Apollo\Domain\Suppliers\SupplierService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get service and data.
$service   = SuppliersModule::get_service();
$suppliers = $service->get_suppliers();
$count     = count( $suppliers );

// Check for deep-link to specific supplier.
$open_supplier_id = isset( $GLOBALS['apollo_supplier_id'] ) ? absint( $GLOBALS['apollo_supplier_id'] ) : 0;

// Get filter options.
$categories     = SupplierService::get_category_labels();
$regions        = SupplierService::get_region_labels();
$event_types    = SupplierService::get_event_type_labels();
$supplier_types = SupplierService::get_supplier_type_labels();
$modes          = SupplierService::get_mode_labels();
$badges         = SupplierService::get_badge_labels();

// Current user info.
$apollo_user        = wp_get_current_user();
$apollo_user_avatar = get_avatar_url( $apollo_user->ID, array( 'size' => 64 ) );
$apollo_user_name   = $apollo_user->display_name ? $apollo_user->display_name : 'Usuário';
$apollo_user_login  = $apollo_user->user_login ? $apollo_user->user_login : 'user';

// Enqueue assets via WordPress.
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

		// Base JS (loads uni.css, icons, etc.).
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			array(),
			'2.0.0',
			true
		);
	},
	5
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full w-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
	<title>Cena::Rio • Fornecedores - Apollo::Rio</title>
	<?php wp_head(); ?>
	<style>
		:root {
			--ap-font-primary: "Urbanist", system-ui, -apple-system, sans-serif;
			--ap-bg-main: #ffffff;
			--ap-bg-surface: #f8fafc;
			--ap-text-primary: rgba(19, 21, 23, 0.65);
			--ap-text-secondary: rgba(15, 23, 42, 0.88);
			--ap-text-muted: rgba(19, 21, 23, 0.4);
			--ap-orange-500: #FF6925;
			--ap-orange-600: #E55A1E;
			--nav-height: 70px;
		}

		*, *::before, *::after {
			box-sizing: border-box;
			-webkit-tap-highlight-color: transparent;
		}

		body {
			font-family: var(--ap-font-primary);
			background-color: var(--ap-bg-surface);
			color: var(--ap-text-secondary);
			overflow-y: auto;
			overflow-x: hidden;
			min-height: 100vh;
			margin: 0;
			padding: 0;
		}

		::-webkit-scrollbar { width: 6px; }
		::-webkit-scrollbar-track { background: transparent; }
		::-webkit-scrollbar-thumb { background-color: rgba(148, 163, 184, 0.4); border-radius: 999px; }
		.no-scrollbar::-webkit-scrollbar { display: none; }
		.pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }

		/* Navbar */
		.navbar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 1000;
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0 1rem;
			height: var(--nav-height);
			background: rgba(255, 255, 255, 0.85);
			backdrop-filter: blur(16px);
			-webkit-backdrop-filter: blur(16px);
		}

		.nav-btn {
			width: 42px;
			height: 42px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--ap-text-primary);
			transition: all 0.2s ease;
			background: transparent;
			border: none;
			cursor: pointer;
		}

		.nav-btn:hover {
			background: rgba(0, 0, 0, 0.05);
			color: var(--ap-orange-500);
		}

		/* Sidebar */
		.aprio-sidebar-nav a {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 10px 12px;
			margin-bottom: 4px;
			border-radius: 12px;
			border-left: 2px solid transparent;
			font-size: 13px;
			color: #64748b;
			text-decoration: none;
			transition: all 0.2s;
		}

		.aprio-sidebar-nav a:hover {
			background-color: #f8fafc;
			color: #0f172a;
		}

		.aprio-sidebar-nav a[aria-current="page"] {
			background-color: #f1f5f9;
			color: #0f172a;
			border-left-color: #0f172a;
			font-weight: 600;
		}

		/* Supplier Cards */
		.supplier-card {
			background: white;
			border: 1px solid #e2e8f0;
			border-radius: 24px;
			padding: 16px;
			transition: all 0.3s;
			cursor: pointer;
		}

		.supplier-card:hover {
			transform: translateY(-3px);
			box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
		}

		.supplier-card.is-hidden {
			display: none !important;
		}

		/* Filter Chips */
		.filter-chip {
			padding: 8px 16px;
			background: rgba(0, 0, 0, 0.04);
			border-radius: 99px;
			font-size: 11px;
			font-weight: 600;
			color: #64748b;
			cursor: pointer;
			transition: all 0.2s;
			border: 1px solid transparent;
		}

		.filter-chip:hover {
			background: rgba(0, 0, 0, 0.08);
			color: #334155;
		}

		.filter-chip.active {
			background: #0f172a;
			color: white;
		}

		/* Modal */
		.supplier-modal-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.6);
			backdrop-filter: blur(4px);
			-webkit-backdrop-filter: blur(4px);
			z-index: 2000;
			opacity: 0;
			visibility: hidden;
			transition: all 0.3s ease;
		}

		.supplier-modal-overlay.is-active {
			opacity: 1;
			visibility: visible;
		}

		.supplier-modal {
			position: fixed;
			bottom: 0;
			left: 50%;
			transform: translateX(-50%) translateY(100%);
			width: 100%;
			max-width: 42rem;
			max-height: 85vh;
			background: white;
			border-radius: 32px 32px 0 0;
			box-shadow: 0 -10px 50px rgba(0, 0, 0, 0.15);
			z-index: 2001;
			display: flex;
			flex-direction: column;
			transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
		}

		.supplier-modal-overlay.is-active .supplier-modal {
			transform: translateX(-50%) translateY(0);
		}

		/* Mobile Bottom Nav */
		.mobile-bottom-nav {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 40;
			background: white;
			border-top: 1px solid #e2e8f0;
		}

		@media (min-width: 768px) {
			.mobile-bottom-nav { display: none; }
		}
	</style>
</head>
<body class="apollo-canvas" <?php echo $open_supplier_id > 0 ? 'data-open-supplier="' . esc_attr( $open_supplier_id ) . '"' : ''; ?>>

	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="flex items-center">
			<!-- Desktop Logo -->
			<div class="hidden md:flex h-16 items-center gap-3 ml-4">
				<div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-md">
					<i class="ri-slack-fill text-white text-[21px]"></i>
				</div>
				<div class="ml-2 flex flex-col leading-tight">
					<span class="text-[15px] font-bold text-slate-900 opacity-95">Apollo::rio</span>
					<span class="text-[8.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
				</div>
			</div>

			<!-- Mobile Logo -->
			<div class="md:hidden h-16 flex items-center gap-3 ml-0">
				<div class="h-9 w-9 rounded-[12px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center z-20 shadow-md">
					<i class="ri-slack-fill text-white text-[20px]"></i>
				</div>
			</div>
		</div>

		<div class="flex items-center gap-2">
			<div class="clock-pill hidden sm:block px-4 py-2 text-xs font-semibold text-slate-500" id="digital-clock"></div>

			<a href="<?php echo esc_url( home_url( '/notificacoes/' ) ); ?>" class="nav-btn" aria-label="Notificações">
				<i class="ri-notification-3-line text-xl"></i>
			</a>

			<a href="<?php echo esc_url( home_url( '/apps/' ) ); ?>" class="nav-btn" aria-label="Aplicativos">
				<i class="ri-grid-fill text-xl"></i>
			</a>

			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" class="nav-btn font-bold bg-slate-100 rounded-full" style="width:36px; height:36px; font-size:12px;" aria-label="Perfil">
				<?php echo esc_html( mb_strtoupper( mb_substr( $apollo_user_name, 0, 1 ) ) ); ?>
			</a>
		</div>
	</nav>

	<!-- MAIN LAYOUT -->
	<div class="flex pt-[70px]">

		<!-- SIDEBAR (Desktop) -->
		<aside class="hidden md:flex flex-col w-64 mr-6 ml-4 pb-2">
			<nav class="aprio-sidebar-nav flex-1 pl-4 pr-3 mt-1 pt-2 pb-2 overflow-y-auto no-scrollbar text-[13px]">
				<div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Navegação</div>

				<a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>">
					<i class="ri-building-3-line"></i>
					<span>Feed</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">
					<i class="ri-calendar-event-line"></i>
					<span>Eventos</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>">
					<i class="ri-user-community-fill"></i>
					<span>Comunidades</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>">
					<i class="ri-team-fill"></i>
					<span>Núcleos</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/classificados/' ) ); ?>">
					<i class="ri-megaphone-line"></i>
					<span>Classificados</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>">
					<i class="ri-file-text-line"></i>
					<span>Docs & Contratos</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>">
					<i class="ri-user-smile-fill"></i>
					<span>Perfil</span>
				</a>

				<div class="mt-6 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Cena::rio</div>

				<a href="<?php echo esc_url( home_url( '/agenda/' ) ); ?>">
					<i class="ri-calendar-line"></i>
					<span>Agenda</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/fornece/' ) ); ?>" aria-current="page">
					<i class="ri-bar-chart-grouped-line"></i>
					<span>Fornecedores</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/documentos/' ) ); ?>">
					<i class="ri-file-text-line"></i>
					<span>Documentos</span>
				</a>

				<div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Acesso Rápido</div>

				<a href="<?php echo esc_url( home_url( '/ajustes/' ) ); ?>">
					<i class="ri-settings-6-line"></i>
					<span>Ajustes</span>
				</a>
			</nav>

			<!-- User Card -->
			<div class="mt-auto pt-4 border-t border-slate-100 flex items-center gap-3 px-4">
				<div class="h-10 w-10 rounded-full bg-orange-100 overflow-hidden">
					<img src="<?php echo esc_url( $apollo_user_avatar ); ?>" class="object-cover w-full h-full" alt="">
				</div>
				<div class="flex flex-col">
					<span class="text-sm font-bold text-slate-900"><?php echo esc_html( $apollo_user_name ); ?></span>
					<span class="text-[10px] text-slate-500">@<?php echo esc_html( $apollo_user_login ); ?></span>
				</div>
			</div>
		</aside>

		<!-- MAIN CONTENT -->
		<main class="flex-1 px-4 md:px-6 pb-24 md:pb-6">

			<!-- Header -->
			<div class="flex items-center justify-between mb-6 mt-4">
				<div>
					<h1 class="text-2xl font-bold text-slate-900">Cena::Rio • Fornecedores</h1>
					<p class="text-sm text-slate-500 mt-1">
						<span id="countLabel"><?php echo esc_html( $count ); ?> fornecedores</span> no catálogo
					</p>
				</div>

				<?php if ( $service->user_can_manage() ) : ?>
				<a href="<?php echo esc_url( home_url( '/fornece/add/' ) ); ?>" class="hidden md:flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition">
					<i class="ri-add-line"></i>
					Adicionar
				</a>
				<?php endif; ?>
			</div>

			<!-- Search -->
			<div class="relative mb-4">
				<i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
				<input
					type="text"
					id="searchInput"
					placeholder="Buscar fornecedores..."
					class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
				>
			</div>

			<!-- Filters -->
			<div class="flex flex-wrap gap-2 mb-6 overflow-x-auto no-scrollbar py-1">
				<button class="filter-chip active" data-cat="all">Todos</button>
				<?php foreach ( $categories as $slug => $label ) : ?>
				<button class="filter-chip" data-cat="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>

			<!-- Suppliers Grid -->
			<div id="suppliersGrid" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
				<?php if ( empty( $suppliers ) ) : ?>
				<div class="col-span-full text-center py-12">
					<div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
						<i class="ri-store-3-line text-2xl text-slate-400"></i>
					</div>
					<p class="text-slate-500">Nenhum fornecedor encontrado.</p>
				</div>
				<?php else : ?>
					<?php foreach ( $suppliers as $supplier ) : ?>
						<?php include __DIR__ . '/partials/supplier-card.php'; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</main>
	</div>

	<!-- Mobile Bottom Navigation -->
	<div class="mobile-bottom-nav pb-safe">
		<div class="max-w-md mx-auto w-full px-6 py-2 flex items-end justify-between h-[60px]">
			<a href="<?php echo esc_url( home_url( '/agenda/' ) ); ?>" class="flex flex-col items-center justify-center w-14 gap-1">
				<i class="ri-calendar-line text-xl text-slate-400"></i>
				<span class="text-[10px] text-slate-400">Agenda</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/fornece/' ) ); ?>" class="flex flex-col items-center justify-center w-14 gap-1">
				<i class="ri-bar-chart-grouped-line text-xl text-slate-900"></i>
				<span class="text-[10px] font-bold text-slate-900">Pro</span>
			</a>
			<?php if ( $service->user_can_manage() ) : ?>
			<a href="<?php echo esc_url( home_url( '/fornece/add/' ) ); ?>" class="relative -top-6">
				<button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/30 active:scale-95 transition-transform">
					<i class="ri-add-line text-2xl"></i>
				</button>
			</a>
			<?php else : ?>
			<div class="w-14"></div>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="flex flex-col items-center justify-center w-14 gap-1">
				<i class="ri-file-text-line text-xl text-slate-400"></i>
				<span class="text-[10px] text-slate-400">Docs</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" class="flex flex-col items-center justify-center w-14 gap-1">
				<i class="ri-user-3-line text-xl text-slate-400"></i>
				<span class="text-[10px] text-slate-400">Perfil</span>
			</a>
		</div>
	</div>

	<!-- Modal Overlay -->
	<div id="supplierModalOverlay" class="supplier-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
		<div class="supplier-modal">
			<!-- Drag Handle -->
			<div class="w-full h-6 flex items-center justify-center absolute top-0 z-20 pointer-events-none">
				<div class="w-12 h-1.5 bg-slate-200 rounded-full mt-2"></div>
			</div>

			<!-- Banner -->
			<div class="relative h-48 bg-slate-900 shrink-0 rounded-t-[32px] overflow-hidden">
				<img id="modalBanner" class="w-full h-full object-cover opacity-80" src="" alt="">
				<button id="modalClose" class="absolute top-4 right-4 h-8 w-8 bg-black/30 backdrop-blur rounded-full text-white flex items-center justify-center hover:bg-black/50 transition" aria-label="Fechar">
					<i class="ri-close-line text-xl"></i>
				</button>
				<div class="absolute -bottom-8 left-6 h-20 w-20 bg-white rounded-2xl p-1 shadow-lg">
					<img id="modalLogo" class="w-full h-full object-contain rounded-xl" src="" alt="">
				</div>
			</div>

			<!-- Content -->
			<div class="flex-1 overflow-y-auto px-6 pt-12 pb-24">
				<div class="flex justify-between items-start mb-1">
					<h2 id="modalTitle" class="text-2xl font-bold text-slate-900 leading-tight"></h2>
					<div id="modalVerified" class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 hidden">
						<i class="ri-verified-badge-fill"></i> Verificado
					</div>
				</div>
				<div class="flex items-center gap-2 mb-4 text-sm">
					<div id="modalStars" class="text-yellow-400 flex"></div>
					<span id="modalRating" class="font-bold text-slate-700">0.0</span>
					<span class="text-slate-300">•</span>
					<span id="modalCategory" class="text-orange-600 font-medium uppercase text-xs tracking-wide"></span>
				</div>

				<div id="modalTags" class="flex flex-wrap gap-2 mb-6"></div>

				<h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Sobre</h3>
				<p id="modalDescription" class="text-slate-600 text-sm leading-relaxed mb-6"></p>

				<h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Contato</h3>
				<div id="modalContacts" class="flex flex-wrap gap-2 mb-6"></div>
			</div>

			<!-- Actions -->
			<div class="absolute bottom-0 w-full p-4 bg-white border-t border-slate-100 flex gap-3">
				<?php if ( is_user_logged_in() ) : ?>
				<button id="modalChat" class="h-12 w-12 flex items-center justify-center rounded-xl bg-orange-500 text-white hover:bg-orange-600 transition-colors shadow-lg shadow-orange-500/20" aria-label="Chat com Fornecedor" data-supplier-id="" onclick="ApolloChat.openSupplierConversation(this.dataset.supplierId);">
					<i class="ri-chat-1-line text-lg"></i>
				</button>
				<?php endif; ?>
				<button id="modalCTA" class="flex-1 bg-slate-900 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-slate-900/20 active:scale-95 transition-transform flex items-center justify-center gap-2">
					<i class="ri-whatsapp-line text-lg"></i> Orçamento
				</button>
				<button id="modalShare" class="h-12 w-12 flex items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Compartilhar">
					<i class="ri-share-forward-line text-lg"></i>
				</button>
			</div>
		</div>
	</div>

	<!-- Suppliers Data for JS -->
	<script id="suppliersData" type="application/json"><?php echo wp_json_encode( $suppliers ); ?></script>

	<?php wp_footer(); ?>

	<script>
	(function() {
		'use strict';

		// Guard: execute only once.
		if (window.__apollo_fornece_inited) return;
		window.__apollo_fornece_inited = true;

		// Clock.
		const clockEl = document.getElementById('digital-clock');
		if (clockEl) {
			function updateClock() {
				clockEl.textContent = new Date().toLocaleTimeString('pt-BR', { hour12: false });
			}
			updateClock();
			setInterval(updateClock, 1000);
		}

		// Data.
		const dataEl = document.getElementById('suppliersData');
		const suppliers = dataEl ? JSON.parse(dataEl.textContent) : [];

		// Elements.
		const grid = document.getElementById('suppliersGrid');
		const searchInput = document.getElementById('searchInput');
		const countLabel = document.getElementById('countLabel');
		const filterChips = document.querySelectorAll('.filter-chip');
		const modalOverlay = document.getElementById('supplierModalOverlay');
		const modalClose = document.getElementById('modalClose');

		// State.
		let currentCategory = 'all';
		let currentSearch = '';

		// Cache.
		const cardCache = new WeakSet();
		const supplierMap = new Map();
		suppliers.forEach(s => supplierMap.set(s.id, s));

		// Filter cards.
		function filterCards() {
			const cards = grid.querySelectorAll('.supplier-card');
			let visibleCount = 0;

			cards.forEach(card => {
				const id = parseInt(card.dataset.supplierId, 10);
				const supplier = supplierMap.get(id);
				if (!supplier) return;

				const matchesCat = currentCategory === 'all' || supplier.category === currentCategory;
				const matchesSearch = !currentSearch ||
					supplier.name.toLowerCase().includes(currentSearch) ||
					(supplier.tags && supplier.tags.some(t => t.toLowerCase().includes(currentSearch)));

				if (matchesCat && matchesSearch) {
					card.classList.remove('is-hidden');
					visibleCount++;
				} else {
					card.classList.add('is-hidden');
				}
			});

			countLabel.textContent = visibleCount + ' fornecedores';
		}

		// Filter chips.
		filterChips.forEach(chip => {
			chip.addEventListener('click', () => {
				filterChips.forEach(c => c.classList.remove('active'));
				chip.classList.add('active');
				currentCategory = chip.dataset.cat;
				filterCards();
			});
		});

		// Search.
		if (searchInput) {
			searchInput.addEventListener('input', (e) => {
				currentSearch = e.target.value.toLowerCase().trim();
				filterCards();
			});
		}

		// Modal.
		function openModal(supplier) {
			document.getElementById('modalTitle').textContent = supplier.name;
			document.getElementById('modalDescription').textContent = supplier.description || '';
			document.getElementById('modalCategory').textContent = supplier.category_label || supplier.category;
			document.getElementById('modalLogo').src = supplier.logo_url;
			document.getElementById('modalBanner').src = supplier.banner_url || 'https://images.unsplash.com/photo-1520166012956-add9ba083599?auto=format&fit=crop&w=800';

			// Verified badge.
			const verifiedEl = document.getElementById('modalVerified');
			if (supplier.is_verified) {
				verifiedEl.classList.remove('hidden');
			} else {
				verifiedEl.classList.add('hidden');
			}

			// Rating stars.
			const starsEl = document.getElementById('modalStars');
			let stars = '';
			const rating = supplier.rating_avg || 5;
			for (let i = 0; i < 5; i++) {
				stars += i < Math.round(rating) ? '<i class="ri-star-fill"></i>' : '<i class="ri-star-line text-slate-300"></i>';
			}
			starsEl.innerHTML = stars;
			document.getElementById('modalRating').textContent = rating.toFixed(1);

			// Tags.
			const tagsEl = document.getElementById('modalTags');
			tagsEl.innerHTML = (supplier.tags || []).slice(0, 4).map(t =>
				`<span class="px-3 py-1 rounded-full bg-slate-100 text-xs font-bold text-slate-600 uppercase border border-slate-200">${t}</span>`
			).join('');

			// Contacts.
			const contactsEl = document.getElementById('modalContacts');
			let contactsHtml = '';
			if (supplier.contact_whatsapp) {
				contactsHtml += `<a href="https://wa.me/${supplier.contact_whatsapp.replace(/\D/g,'')}" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-medium"><i class="ri-whatsapp-line"></i> WhatsApp</a>`;
			}
			if (supplier.contact_instagram) {
				contactsHtml += `<a href="https://instagram.com/${supplier.contact_instagram.replace('@','')}" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-pink-50 text-pink-700 text-sm font-medium"><i class="ri-instagram-line"></i> Instagram</a>`;
			}
			if (supplier.contact_email) {
				contactsHtml += `<a href="mailto:${supplier.contact_email}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium"><i class="ri-mail-line"></i> Email</a>`;
			}
			if (supplier.contact_phone) {
				contactsHtml += `<a href="tel:${supplier.contact_phone}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium"><i class="ri-phone-line"></i> ${supplier.contact_phone}</a>`;
			}
			if (supplier.contact_website) {
				contactsHtml += `<a href="${supplier.contact_website}" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium"><i class="ri-global-line"></i> Site</a>`;
			}
			contactsEl.innerHTML = contactsHtml;

			// CTA button.
			const ctaBtn = document.getElementById('modalCTA');
			if (supplier.has_linked_user) {
				ctaBtn.innerHTML = '<i class="ri-message-3-line text-lg"></i> Enviar Mensagem';
				ctaBtn.onclick = () => window.location.href = '<?php echo esc_url( home_url( '/chat/' ) ); ?>?to=' + supplier.linked_user_id;
			} else if (supplier.contact_whatsapp) {
				ctaBtn.innerHTML = '<i class="ri-whatsapp-line text-lg"></i> Orçamento';
				ctaBtn.onclick = () => window.open('https://wa.me/' + supplier.contact_whatsapp.replace(/\D/g,''), '_blank');
			} else if (supplier.contact_email) {
				ctaBtn.innerHTML = '<i class="ri-mail-line text-lg"></i> Contato';
				ctaBtn.onclick = () => window.location.href = 'mailto:' + supplier.contact_email;
			}

			// Update Chat button with supplier ID.
			const chatBtn = document.getElementById('modalChat');
			if (chatBtn) {
				chatBtn.dataset.supplierId = supplier.id;
			}

			// Update URL.
			history.pushState({ supplierId: supplier.id }, '', '<?php echo esc_url( home_url( '/fornece/' ) ); ?>' + supplier.id + '/');

			// Show modal.
			modalOverlay.classList.add('is-active');
			document.body.style.overflow = 'hidden';
		}

		function closeModal() {
			modalOverlay.classList.remove('is-active');
			document.body.style.overflow = '';
			history.pushState({}, '', '<?php echo esc_url( home_url( '/fornece/' ) ); ?>');
		}

		// Card clicks.
		grid.addEventListener('click', (e) => {
			const card = e.target.closest('.supplier-card');
			if (!card) return;
			const id = parseInt(card.dataset.supplierId, 10);
			const supplier = supplierMap.get(id);
			if (supplier) openModal(supplier);
		});

		// Close modal.
		modalClose.addEventListener('click', closeModal);
		modalOverlay.addEventListener('click', (e) => {
			if (e.target === modalOverlay) closeModal();
		});

		// ESC key.
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && modalOverlay.classList.contains('is-active')) {
				closeModal();
			}
		});

		// Back/forward.
		window.addEventListener('popstate', (e) => {
			if (e.state && e.state.supplierId) {
				const supplier = supplierMap.get(e.state.supplierId);
				if (supplier) openModal(supplier);
			} else {
				modalOverlay.classList.remove('is-active');
				document.body.style.overflow = '';
			}
		});

		// Deep link.
		const body = document.body;
		if (body.dataset.openSupplier) {
			const id = parseInt(body.dataset.openSupplier, 10);
			const supplier = supplierMap.get(id);
			if (supplier) {
				requestAnimationFrame(() => openModal(supplier));
			}
		}

		// Share button.
		document.getElementById('modalShare').addEventListener('click', () => {
			if (navigator.share) {
				navigator.share({
					title: document.getElementById('modalTitle').textContent,
					url: window.location.href
				});
			} else {
				navigator.clipboard.writeText(window.location.href);
				alert('Link copiado!');
			}
		});

	})();
	</script>
</body>
</html>
