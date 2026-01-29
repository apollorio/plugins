<?php

/**
 * MAPA Template - Apollo Social
 *
 * Luxury-grade map page with filters and Leaflet integration.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

// Include map provider for centralized tileset.
if (class_exists('Apollo_Map_Provider')) {
	$tileset = Apollo_Map_Provider::get_tileset('default');
} else {
	$tileset = array(
		'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		'attribution' => '&copy; OpenStreetMap contributors',
	);
}

// Map configuration.
$map_config = array(
	'lat'  => -22.9068,
	'lng'  => -43.1729,
	'zoom' => 12,
);

// Get locations from REST API or use demo data.
$locations = apply_filters('apollo_mapa_locations', array());
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Mapa - <?php bloginfo('name'); ?></title>

	<!-- Apollo Uni CSS -->
	<link rel="stylesheet" href="<?php echo esc_url(content_url('/plugins/apollo-core/assets/css/uni.css')); ?>">

	<!-- Remix Icons -->
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet" />

	<!-- Leaflet CSS -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

	<!-- Google Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

	<!-- Apollo Navbar CSS -->
	<link rel="stylesheet" href="<?php echo esc_url(content_url('/plugins/apollo-core/assets/css/navbar.css')); ?>">

	<style>
		/* UNIVERSAL FIX: Override WordPress admin-bar margin-top */
		@media screen {

			html,
			html.admin-bar {
				margin-top: 0 !important;
			}
		}

		@media screen and (max-width: 782px) {

			html,
			html.admin-bar {
				margin-top: 0 !important;
			}
		}

		body.admin-bar {
			margin-top: 0 !important;
		}

		#wpadminbar {
			position: fixed !important;
			top: 0 !important;
		}

		:root {
			--ap-bg-main: #ffffff;
			--ap-bg-elevated: #ffffff;
			--ap-border-light: rgba(0, 0, 0, 0.06);
			--ap-text-primary: #1a1a1a;
			--ap-text-secondary: #475569;
			--ap-text-muted: #94a3b8;
			--ap-accent: #0f172a;
			--ap-orange-500: #FF6925;
			--ap-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
			--ap-shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
			--ap-shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.08);
			--ap-radius-md: 12px;
			--ap-radius-lg: 16px;
			--ap-radius-xl: 20px;
		}

		html,
		body {
			margin: 0 !important;
			padding: 0 !important;
			width: 100vw !important;
			min-height: 100vh !important;
			background: #ffffff !important;
			overflow-x: hidden;
		}

		body {
			font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			padding-top: 0px !important;
		}

		/* ========================================
		   MAP CONTAINER - FULL VIEWPORT
		   ======================================== */
		.map-wrapper {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			background: #ffffff;
			z-index: 1;
		}

		#apollo-map {
			width: 100%;
			height: 100%;
			background: #ffffff;
		}

		/* Lock map scroll/zoom */
		.leaflet-container {
			touch-action: none;
		}

		/* Hide default Leaflet controls */
		.leaflet-control-zoom,
		.leaflet-control-attribution {
			display: none !important;
		}

		/* Custom Leaflet styles */
		.leaflet-control-container .leaflet-top,
		.leaflet-control-container .leaflet-bottom {
			z-index: 800;
		}

		.leaflet-popup-content-wrapper {
			background: var(--ap-bg-elevated);
			border-radius: var(--ap-radius-lg);
			padding: 0;
			box-shadow: var(--ap-shadow-lg);
			border: 1px solid var(--ap-border-light);
			overflow: hidden;
		}

		.leaflet-popup-content {
			margin: 0;
			font-family: inherit;
		}

		.leaflet-popup-tip {
			background: var(--ap-bg-elevated);
		}

		.map-popup {
			padding: 16px;
			min-width: 200px;
		}

		.map-popup-title {
			font-size: 15px;
			font-weight: 600;
			color: var(--ap-text-primary);
			margin-bottom: 4px;
		}

		.map-popup-category {
			font-size: 12px;
			color: var(--ap-text-muted);
			display: flex;
			align-items: center;
			gap: 6px;
		}

		/* Custom marker */
		.apollo-marker {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			border: 3px solid white;
			box-shadow: var(--ap-shadow-md);
			display: flex;
			align-items: center;
			justify-content: center;
			color: white;
			font-size: 14px;
		}

		/* ========================================
		   FILTER TRIGGER
		   ======================================== */
		@keyframes silver-glint {

			0%,
			85% {
				color: #abb1b3b3;
				filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
				transform: scale(1);
			}

			90% {
				color: #ff110040;
				filter: drop-shadow(0 0 8px rgba(171, 177, 179, 0.2));
				transform: scale(1.1);
			}

			95% {
				color: #fc983a66;
				transform: scale(1.05);
			}

			100% {
				color: #abb1b3b3;
				transform: scale(1);
			}
		}

		.trigger-container {
			position: fixed;
			bottom: 24px;
			left: 24px;
			z-index: 900;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		.icon-trigger {
			font-size: 40px;
			color: #abb1b3b3;
			cursor: pointer;
			transition: all 0.3s ease;
			animation: silver-glint 4s infinite ease-in-out;
			line-height: 1;
		}

		.icon-trigger:hover {
			color: #64748bb3;
			filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
			animation: none;
		}

		/* ========================================
		   FILTER MODAL - GLASSMORPHISM LIQUID
		   ======================================== */
		.glass-panel {
			position: relative;
			background: linear-gradient(135deg,
					rgba(255, 255, 255, 0.95) 0%,
					rgba(255, 255, 255, 0.85) 100%);
			backdrop-filter: blur(40px) saturate(180%);
			-webkit-backdrop-filter: blur(40px) saturate(180%);
			border: 1px solid rgba(255, 255, 255, 0.8);
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
				0 0 0 1px rgba(255, 255, 255, 0.5) inset,
				0 2px 4px rgba(255, 105, 37, 0.1);
			overflow: hidden;
		}

		.glass-panel::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: 200%;
			background: radial-gradient(circle,
					rgba(255, 105, 37, 0.08) 0%,
					transparent 70%);
			animation: liquid-pulse 8s ease-in-out infinite;
			pointer-events: none;
			z-index: 0;
		}

		.glass-panel::after {
			content: '';
			position: absolute;
			bottom: -30%;
			right: -30%;
			width: 150%;
			height: 150%;
			background: radial-gradient(circle,
					rgba(249, 115, 22, 0.06) 0%,
					transparent 60%);
			animation: liquid-pulse 10s ease-in-out infinite reverse;
			pointer-events: none;
			z-index: 0;
		}

		@keyframes liquid-pulse {

			0%,
			100% {
				transform: translate(0, 0) scale(1);
				opacity: 0.6;
			}

			33% {
				transform: translate(10%, -10%) scale(1.1);
				opacity: 0.8;
			}

			66% {
				transform: translate(-10%, 10%) scale(0.9);
				opacity: 0.7;
			}
		}

		.custom-scroll::-webkit-scrollbar {
			width: 4px;
		}

		.custom-scroll::-webkit-scrollbar-track {
			background: rgba(0, 0, 0, 0.02);
		}

		.custom-scroll::-webkit-scrollbar-thumb {
			background: rgba(0, 0, 0, 0.1);
			border-radius: 10px;
		}

		.category-item {
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			position: relative;
			z-index: 1;
			background: rgba(255, 255, 255, 0.4);
			backdrop-filter: blur(10px);
		}

		.category-item::before {
			content: '';
			position: absolute;
			left: 0;
			top: 0;
			width: 0;
			height: 100%;
			background: linear-gradient(90deg,
					rgba(255, 105, 37, 0.15) 0%,
					rgba(249, 115, 22, 0.08) 100%);
			transition: width 0.3s ease;
			z-index: -1;
		}

		.category-item:hover {
			background: rgba(255, 255, 255, 0.7);
			transform: translateX(4px) scale(1.02);
			box-shadow: 0 8px 20px rgba(255, 105, 37, 0.15),
				0 4px 12px rgba(0, 0, 0, 0.08);
		}

		.category-item:hover::before {
			width: 4px;
		}

		.category-item.active {
			background: linear-gradient(90deg,
					rgba(255, 105, 37, 0.2) 0%,
					rgba(255, 255, 255, 0.6) 100%);
			border-left: 4px solid #FF6925;
			box-shadow: 0 4px 16px rgba(255, 105, 37, 0.2);
		}

		.modal-enter {
			opacity: 0;
			transform: scale(0.9) translateY(30px);
			filter: blur(10px);
		}

		.modal-enter-active {
			opacity: 1;
			transform: scale(1) translateY(0);
			filter: blur(0);
			transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1),
				transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
				filter 0.4s ease;
		}

		.modal-exit-active {
			opacity: 0;
			transform: scale(0.95) translateY(20px);
			filter: blur(8px);
			transition: opacity 0.3s ease,
				transform 0.3s cubic-bezier(0.4, 0, 1, 1),
				filter 0.3s ease;
		}

		.icon-circle {
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1),
				0 2px 6px rgba(0, 0, 0, 0.06),
				0 0 0 1px rgba(255, 255, 255, 0.3) inset;
			position: relative;
			overflow: hidden;
		}

		.icon-circle::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: calc(100vh - 56px);
			height: 200%;
			background: linear-gradient(45deg,
					transparent 40%,
					rgba(255, 255, 255, 0.3) 50%,
					transparent 60%);
			transition: transform 0.6s ease;
			transform: translateX(-100%);
		}

		.category-item:hover .icon-circle::before {
			transform: translateX(100%);
		}

		/* ========================================
		   RESPONSIVE - LUXURY MOBILE GRADE
		   ======================================== */
		@media (max-width: 768px) {
			body {
				padding-top: 0px !important;
			}

			.map-wrapper {
				top: 56px;
			}

			.trigger-container {
				bottom: 24px;
				right: 20px;
			}

			.icon-trigger {
				width: 54px;
				height: 54px;
				font-size: 22px;
			}

			#modalContent {
				width: 95%;
				max-width: none;
				margin: 0 10px;
			}

			.glass-panel {
				border-radius: 24px;
			}
		}

		@media (max-width: 480px) {
			body {
				padding-top: 0px !important;
			}

			.map-wrapper {
				height: calc(100vh - 52px);
				top: 52px;
			}

			.trigger-container {
				bottom: 20px;
				right: 16px;
			}

			#modal {
				align-items: flex-end;
				padding-bottom: env(safe-area-inset-bottom, 16px);
			}

			#modalContent {
				width: 100%;
				max-width: none;
				margin: 0;
				border-bottom-left-radius: 0;
				border-bottom-right-radius: 0;
				max-height: 85vh;
			}
		}

		/* Safe area for notch devices */
		@supports (padding-top: env(safe-area-inset-top)) {
			body {
				padding-top: calc(0px + env(safe-area-inset-top)) !important;
			}

			height: calc(100vh - 60px - env(safe-area-inset-top) - env(safe-area-inset-bottom));

			.map-wrapper {
				top: calc(60px + env(safe-area-inset-top));
			}
		}
	</style>
</head>

<body class="apollo-mapa-page">

	<!-- Map Container -->
	<div class="map-wrapper">
		<div id="apollo-map" data-lat="<?php echo esc_attr($map_config['lat']); ?>"
			data-lng="<?php echo esc_attr($map_config['lng']); ?>"
			data-zoom="<?php echo esc_attr($map_config['zoom']); ?>">
		</div>
	</div>

	<!-- Filter Trigger -->
	<div class="trigger-container">
		<i class="ri-menu-search-fill icon-trigger" id="openBtn" title="Filtrar"></i>
		<div class="mt-1 text-slate-400 text-[11px] font-light tracking-wide lowercase opacity-80 select-none">
			apollo.rio
		</div>
	</div>

	<!-- Backdrop -->
	<div id="backdrop"
		class="fixed inset-0 bg-slate-200/40 backdrop-blur-[2px] z-950 hidden transition-opacity duration-300 opacity-0"
		onclick="closeModal()">
	</div>

	<!-- Filter Modal -->
	<div id="modal" class="fixed inset-0 flex items-center justify-center z-1000 hidden pointer-events-none">
		<div class="glass-panel w-[90%] max-w-sm rounded-3xl overflow-hidden p-1 relative shadow-2xl pointer-events-auto modal-enter"
			id="modalContent">

			<button onclick="closeModal()"
				class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors z-10 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
				<i class="ri-close-line text-xl"></i>
			</button>

			<div class="p-6">
				<h3 class="text-lg font-semibold text-slate-800 mb-4 tracking-tight">Filtrar por categoria:</h3>

				<div id="categoryList" class="space-y-2 max-h-[60vh] overflow-y-auto custom-scroll pr-2">
					<!-- Categories rendered by JS -->
				</div>

				<div class="mt-4 pt-4 border-t border-slate-100">
					<button id="clearFilters"
						class="w-full py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">
						<i class="ri-refresh-line mr-1"></i> Limpar filtros
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Leaflet JS -->
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

	<script>
		(function() {
			'use strict';

			// Category configuration
			const categories = [{
					id: 'hotel',
					label: 'Hotel & Hostel',
					color: '#a855f7',
					icon: 'ri-hotel-fill'
				},
				{
					id: 'bar',
					label: 'Bar & Clubs',
					color: '#ff7f41',
					icon: 'ri-goblet-fill'
				},
				{
					id: 'cultura',
					label: 'Cultura::rio',
					color: '#ec4899',
					icon: 'ri-bank-fill'
				},
				{
					id: 'restaurante',
					label: 'Café & Restaurante',
					color: '#f43f5e',
					icon: 'ri-restaurant-fill'
				},
				{
					id: 'praia',
					label: 'Praia',
					color: '#fbbf24',
					icon: 'ri-sun-fill'
				},
				{
					id: 'natureza',
					label: 'Natureza',
					color: '#22c55e',
					icon: 'ri-tree-fill'
				},
				{
					id: 'publico',
					label: 'Público',
					color: '#14b8a6',
					icon: 'ri-layout-masonry-fill'
				},
				{
					id: 'mobilidade',
					label: 'Mobilidade',
					color: '#0681c0',
					icon: 'ri-subway-fill'
				},
				{
					id: 'policia',
					label: 'Polícia',
					color: '#6b7280',
					icon: 'ri-police-car-badge-fill'
				}
			];

			// State
			let map = null;
			let markers = [];
			let activeCategories = new Set();

			// DOM elements
			const mapContainer = document.getElementById('apollo-map');
			const openBtn = document.getElementById('openBtn');
			const backdrop = document.getElementById('backdrop');
			const modal = document.getElementById('modal');
			const modalContent = document.getElementById('modalContent');
			const listContainer = document.getElementById('categoryList');
			const clearFiltersBtn = document.getElementById('clearFilters');

			// Initialize map
			function initMap() {
				if (!mapContainer || typeof L === 'undefined') return;

				const lat = parseFloat(mapContainer.dataset.lat) || -22.9068;
				const lng = parseFloat(mapContainer.dataset.lng) || -43.1729;
				const zoom = parseInt(mapContainer.dataset.zoom) || 12;

				map = L.map(mapContainer, {
					center: [lat, lng],
					zoom: zoom,
					zoomControl: false,
					scrollWheelZoom: false,
					doubleClickZoom: false,
					touchZoom: false,
					boxZoom: false,
					keyboard: false,
					dragging: true
				});

				// Use centralized tileset or fallback to OSM
				const tileUrl = <?php echo wp_json_encode($tileset['url']); ?>;
				const tileAttribution = <?php echo wp_json_encode($tileset['attribution'] ?? ''); ?>;

				L.tileLayer(tileUrl, {
					attribution: tileAttribution,
					maxZoom: 19
				}).addTo(map);

				// Load demo markers
				loadDemoMarkers();
			}

			// Load demo markers
			function loadDemoMarkers() {
				const demoLocations = [{
						lat: -22.9882,
						lng: -43.1912,
						title: 'Arpoador',
						category: 'praia'
					},
					{
						lat: -22.9711,
						lng: -43.1822,
						title: 'Copacabana Palace',
						category: 'hotel'
					},
					{
						lat: -22.9133,
						lng: -43.1787,
						title: 'Lapa',
						category: 'bar'
					},
					{
						lat: -22.9519,
						lng: -43.2105,
						title: 'Jardim Botânico',
						category: 'natureza'
					},
					{
						lat: -22.9068,
						lng: -43.1729,
						title: 'Centro',
						category: 'cultura'
					}
				];

				demoLocations.forEach(loc => addMarker(loc));
			}

			// Add marker to map
			function addMarker(location) {
				if (!map) return;

				const cat = categories.find(c => c.id === location.category) || categories[0];

				const icon = L.divIcon({
					className: 'apollo-marker-wrapper',
					html: `<div class="apollo-marker" style="background-color: ${cat.color};">
				<i class="${cat.icon}"></i>
			</div>`,
					iconSize: [32, 32],
					iconAnchor: [16, 16]
				});

				const marker = L.marker([location.lat, location.lng], {
						icon
					})
					.bindPopup(`
				<div class="map-popup">
					<div class="map-popup-title">${location.title}</div>
					<div class="map-popup-category">
						<i class="${cat.icon}" style="color: ${cat.color}"></i>
						${cat.label}
					</div>
				</div>
			`)
					.addTo(map);

				marker._category = location.category;
				markers.push(marker);
			}

			// Render category list
			function renderList() {
				listContainer.innerHTML = categories.map(cat => {
					const isActive = activeCategories.has(cat.id);
					return `
				<div class="category-item flex items-center justify-between p-2.5 rounded-xl cursor-pointer group ${isActive ? 'active' : ''}"
					 data-category="${cat.id}">
					<div class="flex items-center gap-4">
						<div class="icon-circle relative flex items-center justify-center w-10 h-10 rounded-full shrink-0 transition-transform group-hover:scale-105"
							 style="background-color: ${cat.color};">
							<i class="${cat.icon} text-white text-lg"></i>
						</div>
						<span class="text-slate-600 text-base font-medium group-hover:text-slate-900 tracking-normal transition-colors">
							${cat.label}
						</span>
					</div>
					<i class="ri-${isActive ? 'checkbox-circle-fill text-green-500' : 'arrow-right-s-line text-slate-300 group-hover:text-slate-500'} transition-colors text-xl"></i>
				</div>
			`;
				}).join('');

				// Add click handlers
				listContainer.querySelectorAll('.category-item').forEach(item => {
					item.addEventListener('click', () => toggleCategory(item.dataset.category));
				});
			}

			// Toggle category filter
			function toggleCategory(categoryId) {
				if (activeCategories.has(categoryId)) {
					activeCategories.delete(categoryId);
				} else {
					activeCategories.add(categoryId);
				}
				renderList();
				filterMarkers();
			}

			// Filter markers based on active categories
			function filterMarkers() {
				markers.forEach(marker => {
					if (activeCategories.size === 0 || activeCategories.has(marker._category)) {
						marker.addTo(map);
					} else {
						marker.remove();
					}
				});
			}

			// Clear all filters
			function clearFilters() {
				activeCategories.clear();
				renderList();
				filterMarkers();
			}

			// Modal functions
			function openModal() {
				backdrop.classList.remove('hidden');
				modal.classList.remove('hidden');
				setTimeout(() => {
					backdrop.classList.remove('opacity-0');
					modalContent.classList.add('modal-enter-active');
				}, 10);
			}

			function closeModal() {
				backdrop.classList.add('opacity-0');
				modalContent.classList.remove('modal-enter-active');
				modalContent.classList.add('modal-exit-active');
				setTimeout(() => {
					backdrop.classList.add('hidden');
					modal.classList.add('hidden');
					modalContent.classList.remove('modal-exit-active');
				}, 300);
			}

			// Event listeners
			openBtn.addEventListener('click', openModal);
			clearFiltersBtn.addEventListener('click', clearFilters);

			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
					closeModal();
				}
			});

			// Initialize
			renderList();
			initMap();

			// Expose to window
			window.ApolloMapa = {
				map,
				openModal,
				closeModal,
				filterMarkers
			};
		})();
	</script>

	<!-- Apollo Navbar -->
	<?php
	// Direct include for RAW template (this page uses raw_html => TRUE)
	// Cannot use wp_footer() here as it causes admin-bar render errors
	$navbar_path = WP_PLUGIN_DIR . '/apollo-core/templates/partials/navbar.php';
	if (file_exists($navbar_path)) {
		include $navbar_path;
	}
	?>

	<!-- Apollo Navbar JS -->
	<script>
		var apolloNavbar = <?php
							$current_user = wp_get_current_user();
							echo wp_json_encode(array(
								'isLoggedIn' => is_user_logged_in() ? '1' : '0',
								'userId' => get_current_user_id(),
								'userName' => $current_user->user_login ?? '',
								'userInitial' => strtoupper(substr($current_user->user_login ?? 'G', 0, 1)),
								'logoutUrl' => wp_logout_url(home_url()),
								'profileUrl' => get_author_posts_url(get_current_user_id()),
							));
							?>;
	</script>
	<script src="<?php echo esc_url(content_url('/plugins/apollo-core/assets/js/navbar.js')); ?>" defer></script>
	<script fetchpriority="high" src="https://assets.apollo.rio.br/mapa.js"></script>
	<script fetchpriority="high" src="https://cdn.apollo.rio.br/"></script>
</body>

</html>
