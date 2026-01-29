<?php
/**
 * CENA Template - Ultra Modern UI
 *
 * Rio Culture Industry Calendar
 * Maximum viewport utilization with split-view layout
 *
 * Route: /cena (industry only)
 *
 * @package Apollo_Social
 * @since 2.1.0
 */

defined( 'ABSPATH' ) || exit;

// Load CENA assets via proper WordPress enqueue
require_once APOLLO_SOCIAL_PLUGIN_DIR . 'includes/cena-enqueue.php';
apollo_cena_enqueue_assets();

// View data (if passed from your router, otherwise use defaults)
$user_data  = isset( $view['user'] ) ? $view['user'] : array();
$rest_url   = isset( $view['rest_url'] ) ? $view['rest_url'] : rest_url( 'apollo/v1/cena/' );
$rest_nonce = isset( $view['rest_nonce'] ) ? $view['rest_nonce'] : wp_create_nonce( 'wp_rest' );
$today      = isset( $view['today'] ) ? $view['today'] : wp_date( 'Y-m-d' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php esc_html_e( 'CENA', 'apollo-social' ); ?> - <?php bloginfo( 'name' ); ?></title>

	<?php
	// Preconnect hints for performance
	apollo_cena_preconnect_hints();

	/**
	 * wp_head() outputs:
	 * - All enqueued styles (Leaflet CSS, CENA CSS, fonts, icons)
	 * - Inline config script (window.apolloCenaConfig)
	 */
	wp_head();
	?>
</head>

<body class="apollo-cena-page">

	<div class="cena-app" style="padding-top: 70px;">

		<!-- ====== TOP BAR ====== -->
		<header class="cena-topbar">


			<div class="cena-actions">
				<div class="cena-quick-stats">
					<div class="cena-stat">
						<span class="cena-stat-dot confirmado"></span>
						<span class="cena-stat-value" data-stat="confirmado">0</span>
						<span>confirmados</span>
					</div>
					<div class="cena-stat">
						<span class="cena-stat-dot previsto"></span>
						<span class="cena-stat-value" data-stat="previsto">0</span>
						<span>previstos</span>
					</div>
					<div class="cena-stat">
						<span class="cena-stat-dot adiado"></span>
						<span class="cena-stat-value" data-stat="adiado">0</span>
						<span>adiados</span>
					</div>
				</div>



				<a href="/fornecedores/">
					<button class="cena-add-btn" id="btn-open-suppliers">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
							class="cena-add-btn-icon">
							<path
								d="M7 2V22H3V2H7ZM9 2H19.0049C20.1068 2 21 2.89821 21 3.9908V20.0092C21 21.1087 20.1074 22 19.0049 22H9V2ZM22 6H24V10H22V6ZM22 12H24V16H22V12ZM15 12C16.1046 12 17 11.1046 17 10C17 8.89543 16.1046 8 15 8C13.8954 8 13 8.89543 13 10C13 11.1046 13.8954 12 15 12ZM12 16H18C18 14.3431 16.6569 13 15 13C13.3431 13 12 14.3431 12 16Z">
							</path>
						</svg>
					</button></a>



				<button class="cena-add-btn" id="btn-add-event">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
						class="cena-add-btn-icon">
						<path
							d="M 7 2 L 7.011 2 L 7.011 1.999 L 9.011 1.999 L 9.011 3.999 L 15 3.999 L 15 2 L 15.011 2 L 15.011 1.999 L 17.011 1.999 L 17.011 3.999 L 21.011 3.999 C 21.564 3.999 22.011 4.447 22.011 4.999 L 22.011 7.999 C 22.011 8.05 22.007 8.099 22 8.148 L 22 13 L 20 13 L 20 8.999 L 4 8.999 L 4 20 L 10 20 L 10 22 L 3 22 C 2.448 22 2 21.553 2 21 L 2 5 C 2 4.477 2.402 4.047 2.914 4.004 C 2.946 4.001 2.978 3.999 3.011 3.999 L 7 3.999 Z M 16.286 17.286 L 16.286 14 L 17.714 14 L 17.714 17.286 L 21 17.286 L 21 18.714 L 17.714 18.714 L 17.714 22 L 16.286 22 L 16.286 18.714 L 13 18.714 L 13 17.286 Z" />
					</svg>
				</button>
				<a href="/mapa/">
					<button class="cena-add-btn" id="btn-add-event">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
							class="cena-add-btn-icon">
							<path
								d="M16.9497 11.9497C18.7347 10.1648 19.3542 7.65558 18.8081 5.36796L21.303 4.2987C21.5569 4.18992 21.8508 4.30749 21.9596 4.56131C21.9862 4.62355 22 4.69056 22 4.75827V19L15 22L9 19L2.69696 21.7013C2.44314 21.8101 2.14921 21.6925 2.04043 21.4387C2.01375 21.3765 2 21.3094 2 21.2417V7L5.12892 5.65904C4.70023 7.86632 5.34067 10.2402 7.05025 11.9497L12 16.8995L16.9497 11.9497ZM15.5355 10.5355L12 14.0711L8.46447 10.5355C6.51184 8.58291 6.51184 5.41709 8.46447 3.46447C10.4171 1.51184 13.5829 1.51184 15.5355 3.46447C17.4882 5.41709 17.4882 8.58291 15.5355 10.5355Z">
							</path>
						</svg>
					</button>
				</a>





				<div class="cena-month-nav">
					<button class="cena-month-btn" id="prev-month">
						<i class="ri-arrow-left-s-line"></i>
					</button>
					<span class="cena-current-month" id="current-month">Janeiro 2026</span>
					<button class="cena-month-btn" id="next-month">
						<i class="ri-arrow-right-s-line"></i>
					</button>
				</div>
			</div>
		</header>

		<!-- ====== MAIN CONTENT ====== -->
		<main class="cena-main">

			<!-- Sidebar -->
			<aside class="cena-sidebar">
				<!-- Mini Calendar -->
				<div class="cena-calendar-mini">
					<div class="cena-weekdays">
						<div class="cena-weekday">Dom</div>
						<div class="cena-weekday">Seg</div>
						<div class="cena-weekday">Ter</div>
						<div class="cena-weekday">Qua</div>
						<div class="cena-weekday">Qui</div>
						<div class="cena-weekday">Sex</div>
						<div class="cena-weekday">Sáb</div>
					</div>
					<div class="cena-days" id="calendar-days">
						<!-- JS renders -->
					</div>
				</div>

				<!-- Upcoming Events -->
				<div class="cena-upcoming">
					<div class="cena-section-title">Próximos Eventos</div>
					<div class="cena-upcoming-list" id="upcoming-list">
						<!-- JS renders -->
					</div>
				</div>
			</aside>

			<!-- Content -->
			<div class="cena-content">
				<!-- Map -->
				<div class="cena-map-container">
					<div id="event-map"></div>
					<div class="cena-map-controls">
						<button class="cena-map-btn" id="map-zoom-in" title="Zoom in">
							<i class="ri-add-line"></i>
						</button>
						<button class="cena-map-btn" id="map-zoom-out" title="Zoom out">
							<i class="ri-subtract-line"></i>
						</button>
						<button class="cena-map-btn" id="map-reset" title="Reset view">
							<i class="ri-focus-3-line"></i>
						</button>
					</div>
					<div class="cena-map-legend">
						<div class="cena-legend-item">
							<span class="cena-legend-dot confirmado"></span>
							Confirmado
						</div>
						<div class="cena-legend-item">
							<span class="cena-legend-dot previsto"></span>
							Previsto
						</div>
						<div class="cena-legend-item">
							<span class="cena-legend-dot adiado"></span>
							Adiado
						</div>
					</div>
				</div>

				<!-- Events Grid Area -->
				<div class="cena-events-area">
					<div class="cena-events-header">
						<h2 class="cena-events-title">Eventos Cena::rio <small
								style="font-size: 0.7em; opacity: 0.5; font-weight: 400;" id="events-count">0</small>
						</h2>
						<p class="cena-events-subtitle">Exclusivos somente a membros da indústria de cultura carioca.
						</p>
					</div>

					<!-- Filters -->
					<div class="cena-filters">
						<button class="cena-filter-pill active" data-filter="all">
							Todos
						</button>
						<button class="cena-filter-pill" data-filter="confirmado">
							<span class="cena-filter-dot" style="background: var(--cena-confirmado);"></span>
							Confirmados
						</button>
						<button class="cena-filter-pill" data-filter="previsto">
							<span class="cena-filter-dot" style="background: var(--cena-previsto);"></span>
							Previstos
						</button>
						<button class="cena-filter-pill" data-filter="adiado">
							<span class="cena-filter-dot" style="background: var(--cena-adiado);"></span>
							Adiados
						</button>
					<button class="cena-filter-pill" id="btn-add-event-filter" style="background: var(--cena-accent); color: white; border-color: var(--cena-accent);">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
							<path d="M 7 2 L 7.011 2 L 7.011 1.999 L 9.011 1.999 L 9.011 3.999 L 15 3.999 L 15 2 L 15.011 2 L 15.011 1.999 L 17.011 1.999 L 17.011 3.999 L 21.011 3.999 C 21.564 3.999 22.011 4.447 22.011 4.999 L 22.011 7.999 C 22.011 8.05 22.007 8.099 22 8.148 L 22 13 L 20 13 L 20 8.999 L 4 8.999 L 4 20 L 10 20 L 10 22 L 3 22 C 2.448 22 2 21.553 2 21 L 2 5 C 2 4.477 2.402 4.047 2.914 4.004 C 2.946 4.001 2.978 3.999 3.011 3.999 L 7 3.999 Z M 16.286 17.286 L 16.286 14 L 17.714 14 L 17.714 17.286 L 21 17.286 L 21 18.714 L 17.714 18.714 L 17.714 22 L 16.286 22 L 16.286 18.714 L 13 18.714 L 13 17.286 Z"></path>
						</svg>
						Novo evento
					</button>
						<!-- JS renders events here -->
					</div>
				</div>
			</div>
		</main>

		<div class="cena-modal-overlay" id="modal-overlay">
			<div class="cena-modal">
				<div class="cena-modal-header">
					<h2 class="cena-modal-title" id="modal-title">Novo Evento</h2>
					<button class="cena-modal-close" id="modal-close">
						<i class="ri-close-line"></i>
					</button>
				</div>
				<div class="cena-modal-body">
					<form id="event-form">
						<input type="hidden" id="ev-id" name="id">
						<input type="hidden" id="ev-status" name="status" value="previsto">
						<input type="hidden" id="ev-lat" name="lat">
						<input type="hidden" id="ev-lng" name="lng">

						<div class="cena-form-field">
							<label class="cena-form-label" for="ev-title">
								Título
								<span class="cena-tooltip"
									title="Nome do evento (obrigatório). Ex: Masterclass de Produção Musical">ⓘ</span>
							</label>
							<input class="cena-form-input" id="ev-title" name="title" type="text" required
								placeholder="Nome do evento">
						</div>

						<div class="cena-form-grid">
							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-date">
									Data
									<span class="cena-tooltip" title="Data do evento (obrigatório).">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-date" name="date" type="date" required>
							</div>
							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-time">
									Horário
									<span class="cena-tooltip" title="Hora de início (HH:MM)">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-time" name="time" type="time">
							</div>
						</div>

						<div class="cena-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">

							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-location">
									Local
									<span class="cena-tooltip" title="Endereço do evento.">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-location" name="location" type="text"
									placeholder="Ex: Lapa, Rio de Janeiro">
							</div>

							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-tags"
									style="display:flex; align-items:center; gap:4px;">
									<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
										fill="currentColor">
										<path
											d="M12 13.5351V3H20V5H14V17C14 19.2091 12.2091 21 10 21C7.79086 21 6 19.2091 6 17C6 14.7909 7.79086 13 10 13C10.7286 13 11.4117 13.1948 12 13.5351ZM10 19C11.1046 19 12 18.1046 12 17C12 15.8954 11.1046 15 10 15C8.89543 15 8 15.8954 8 17C8 18.1046 8.89543 19 10 19Z">
										</path>
									</svg>
									Sounds / DJ
									<span class="cena-tooltip" title="DJs ou Tags separados por vírgula.">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-tags" name="tags" type="text"
									placeholder="Ex: Techno, House">
							</div>
						</div>

						<div class="cena-form-field">
							<label class="cena-form-label" for="ev-type">
								Tipo
								<span class="cena-tooltip" title="Categoria do evento.">ⓘ</span>
							</label>
							<input class="cena-form-input" id="ev-type" name="type" type="text"
								placeholder="Masterclass, Festival, etc.">
						</div>

						<div class="cena-form-grid">
							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-author">
									Autor
									<span class="cena-tooltip" title="Username do criador.">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-author" name="author" type="text"
									placeholder="@username">
							</div>
							<div class="cena-form-field">
								<label class="cena-form-label" for="ev-coauthor">
									Coautor
									<span class="cena-tooltip" title="Username do colaborador.">ⓘ</span>
								</label>
								<input class="cena-form-input" id="ev-coauthor" name="coauthor" type="text"
									placeholder="@username">
							</div>
						</div>

						<div class="cena-form-field">
							<label class="cena-form-label">
								Status
								<span class="cena-tooltip" title="Estado do evento.">ⓘ</span>
							</label>
							<div class="cena-status-grid">
								<div class="cena-status-option previsto selected" data-status="previsto"
									title="Evento ainda não confirmado">
									<i class="ri-time-line"></i>
									Previsto
								</div>
								<div class="cena-status-option confirmado" data-status="confirmado"
									title="Evento confirmado e publicado">
									<i class="ri-check-double-line"></i>
									Confirmado
								</div>
								<div class="cena-status-option adiado" data-status="adiado"
									title="Evento adiado para outra data">
									<i class="ri-calendar-close-line"></i>
									Adiado
								</div>
								<div class="cena-status-option cancelado" data-status="cancelado"
									title="Evento cancelado">
									<i class="ri-close-circle-line"></i>
									Cancelado
								</div>
							</div>
						</div>

						<button type="submit" class="cena-form-submit">
							Salvar Evento
						</button>
					</form>
				</div>
			</div>
		</div>
		<?php
    /**
     * wp_footer() outputs:
     * - All enqueued scripts in footer (Leaflet JS, CENA JS)
     * - WordPress admin bar (if logged in)
     */
    wp_footer();
    ?>
</body>

</html>
