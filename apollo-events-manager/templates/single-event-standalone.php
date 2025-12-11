<?php
// phpcs:ignoreFile

/**
 * SINGLE EVENT PAGE TEMPLATE - STRICT MODE 100% DESIGN CONFORMANCE
 * Route: /evento/{slug}/
 * Design Reference: PAGE-FOR-CPT EVENT LISTING single page stylesheet by unicss
 *
 * STRICT MODE AUDIT: 2025-11-25
 * - mobile-container as root wrapper ✓
 * - hero-media with video-cover ✓
 * - avatars-explosion for RSVP row ✓
 * - dj-link class on lineup names ✓
 * - music-tags-marquee infinite loop ✓
 * - Registros comment section ✓
 * - Forced tooltips on ALL placeholders ✓
 *
 * @package Apollo_Events_Manager
 */

defined( 'ABSPATH' ) || exit;

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
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

// Detect if loaded inside modal
$is_modal = defined( 'APOLLO_MODAL_CONTEXT' ) && constant( 'APOLLO_MODAL_CONTEXT' );

// Get event ID
$event_id = isset( $event_id ) ? $event_id : get_the_ID();

// Get data helper
if ( ! class_exists( 'Apollo_Event_Data_Helper' ) ) {
	require_once APOLLO_APRIO_PATH . 'includes/helpers/event-data-helper.php';
}
$event_data = Apollo_Event_Data_Helper::get_event_data( $event_id );

// Bail if no data
if ( ! $event_data ) {
	if ( ! $is_modal ) {
		wp_die( __( 'Evento não encontrado', 'apollo-events-manager' ) );
	}
	return;
}

// Prepare video embed
$video_embed = '';
if ( ! empty( $event_data['video_url'] ) ) {
	$video_url = $event_data['video_url'];
	if ( strpos( $video_url, 'youtube.com' ) !== false || strpos( $video_url, 'youtu.be' ) !== false ) {
		preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $m );
		$yt_id = isset( $m[1] ) ? $m[1] : '';
		if ( $yt_id ) {
			$video_embed = 'https://www.youtube.com/embed/' . $yt_id . '?autoplay=1&mute=1&loop=1&playlist=' . $yt_id . '&controls=0&showinfo=0&modestbranding=1';
		}
	}
}

// Get favorites data
$favorites_data = function_exists( 'apollo_get_event_favorites_snapshot' )
	? apollo_get_event_favorites_snapshot( $event_id )
	: array(
		'count'   => 0,
		'avatars' => array(),
	);

// Get event comments for Registros section
$comments = get_comments(
	array(
		'post_id' => $event_id,
		'status'  => 'approve',
		'number'  => 20,
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	)
);

if ( ! $is_modal ) :
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>

	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
		<title><?php echo esc_html( $event_data['title'] ); ?> - Apollo::rio</title>
		<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
		<?php if ( $event_data['coords']['valid'] ) : ?>
			<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
			<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
		<?php endif; ?>
		<?php wp_head(); ?>
	</head>

	<body class="apollo-event-single">
	<?php endif; ?>

	<div class="mobile-container">
		<!-- Hero Media -->
		<div class="hero-media">
			<?php if ( $video_embed ) : ?>
				<div class="video-cover">
					<iframe
						src="<?php echo esc_url( $video_embed ); ?>"
						allow="autoplay; fullscreen"
						allowfullscreen
						frameborder="0"
						loading="lazy"></iframe>
				</div>
			<?php elseif ( $event_data['banner'] ) : ?>
				<img src="<?php echo esc_url( $event_data['banner'] ); ?>"
					alt="<?php echo esc_attr( $event_data['title'] ); ?>"
					class="hero-image" loading="eager">
			<?php else : ?>
				<div class="hero-placeholder" data-tooltip="Banner do evento - imagem principal">
					<i class="ri-image-line"></i>
				</div>
			<?php endif; ?>

			<div class="hero-overlay"></div>

			<div class="hero-content">
				<!-- Tags Section -->
				<section id="listing_types_tags_category">
					<?php if ( $event_data['special_badge'] ) : ?>
						<?php
						$badge      = $event_data['special_badge'];
						$badge_icon = 'ri-fire-fill';
						if ( $badge === 'Apollo recomenda' ) {
							$badge_icon = 'ri-award-fill';
						} elseif ( $badge === 'Destaque' ) {
							$badge_icon = 'ri-verified-badge-fill';
						}
						?>
						<span class="event-tag-pill"><i class="<?php echo esc_attr( $badge_icon ); ?>"></i> <?php echo esc_html( $badge ); ?></span>
					<?php endif; ?>

					<?php if ( ! empty( $event_data['categories'] ) ) : ?>
						<?php foreach ( $event_data['categories'] as $cat ) : ?>
							<span class="event-tag-pill"><i class="ri-brain-ai-3-fill"></i> <?php echo esc_html( $cat ); ?></span>
						<?php endforeach; ?>
					<?php else : ?>
						<span class="event-tag-pill" data-tooltip="Categoria do evento"><i class="ri-brain-ai-3-fill"></i> Categoria</span>
					<?php endif; ?>

					<?php if ( ! empty( $event_data['tags'] ) ) : ?>
						<?php foreach ( array_slice( $event_data['tags'], 0, 4 ) as $tag ) : ?>
							<span class="event-tag-pill"><i class="ri-price-tag-3-line"></i> <?php echo esc_html( $tag ); ?></span>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( $event_data['type'] ) : ?>
						<span class="event-tag-pill"><i class="ri-landscape-ai-fill"></i> <?php echo esc_html( $event_data['type'] ); ?></span>
					<?php endif; ?>
				</section>

				<h1 class="hero-title"><?php echo esc_html( $event_data['title'] ); ?></h1>

				<div class="hero-meta">
					<div class="hero-meta-item">
						<i class="ri-calendar-line"></i>
						<span><?php echo esc_html( $event_data['formatted_date'] ); ?></span>
					</div>
					<div class="hero-meta-item">
						<i class="ri-time-line"></i>
						<span id="Hora">
							<?php echo esc_html( $event_data['formatted_time'] ); ?>
							<?php if ( $event_data['end_time'] ) : ?>
								— <?php echo esc_html( $event_data['end_time'] ); ?>
							<?php endif; ?>
						</span>
						<font style="opacity:.7;font-weight:300;font-size:.81rem;vertical-align:bottom;">(GMT-03h00)</font>
					</div>
					<div class="hero-meta-item">
						<i class="ri-map-pin-line"></i>
						<span><?php echo esc_html( $event_data['venue_name'] ?: 'Local a confirmar' ); ?></span>
						<?php if ( $event_data['venue_region'] ) : ?>
							<span style="opacity:0.5">(<?php echo esc_html( $event_data['venue_region'] ); ?>)</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Event Body -->
		<div class="event-body">
			<!-- Quick Actions -->
			<div class="quick-actions">
				<a href="#route_TICKETS" class="quick-action">
					<div class="quick-action-icon">
						<i class="ri-ticket-2-line"></i>
					</div>
					<span class="quick-action-label">TICKETS</span>
				</a>
				<a href="#route_LINE" class="quick-action">
					<div class="quick-action-icon">
						<i class="ri-draft-line"></i>
					</div>
					<span class="quick-action-label">Line-up</span>
				</a>
				<a href="#route_ROUTE" class="quick-action">
					<div class="quick-action-icon">
						<i class="ri-treasure-map-line"></i>
					</div>
					<span class="quick-action-label">ROUTE</span>
				</a>
				<a href="#" class="quick-action apollo-favorite-trigger"
					id="favoriteTrigger"
					data-event-id="<?php echo esc_attr( $event_id ); ?>">
					<div class="quick-action-icon">
						<i class="ri-rocket-line"></i>
					</div>
					<span class="quick-action-label">Interesse</span>
				</a>
			</div>

			<!-- RSVP Row with Avatar Explosion -->
			<div class="rsvp-row">
				<div class="avatars-explosion">
					<?php
					$avatars     = $favorites_data['avatars'];
					$total_count = $favorites_data['count'];
					$displayed   = 0;

					if ( ! empty( $avatars ) ) :
						foreach ( array_slice( $avatars, 0, 10 ) as $avatar ) :
							++$displayed;
							?>
							<div class="avatar" style="background-image: url('<?php echo esc_url( $avatar ); ?>')"></div>
							<?php
						endforeach;
					else :
						// Placeholder avatars with tooltips
						for ( $i = 1; $i <= 5; $i++ ) :
							?>
							<div class="avatar"
								style="background-image: url('https://ui-avatars.com/api/?name=?&background=333&color=fff')"
								data-tooltip="Seja o primeiro a demonstrar interesse!"></div>
							<?php
						endfor;
					endif;

					$remaining = $total_count - $displayed;
					if ( $remaining > 0 ) :
						?>
						<div class="avatar-count">+<?php echo esc_html( $remaining ); ?></div>
					<?php endif; ?>

					<p class="interested-text" style="margin: 0 8px 0 20px;">
						<i class="ri-bar-chart-2-fill"></i>
						<span id="result"><?php echo esc_html( $total_count ); ?></span>
					</p>
				</div>
			</div>

			<!-- Info Section -->
			<section class="section">
				<h2 class="section-title">
					<i class="ri-brain-ai-3-fill"></i> Info
				</h2>
				<div class="info-card">
					<?php if ( $event_data['description'] ) : ?>
						<p class="info-text"><?php echo wp_kses_post( $event_data['description'] ); ?></p>
					<?php else : ?>
						<p class="info-text" data-tooltip="Descrição do evento - detalhes sobre a experiência">
							Informações do evento em breve.
						</p>
					<?php endif; ?>
				</div>

				<!-- Music Tags Marquee -->
				<div class="music-tags-marquee">
					<div class="music-tags-track">
						<?php
						$sounds = $event_data['sounds'];
						if ( ! empty( $sounds ) ) :
							// Replicate sounds to ensure infinite loop (minimum 8 spans)
							$sound_list   = is_array( $sounds ) ? $sounds : explode( ',', $sounds );
							$sound_list   = array_map( 'trim', $sound_list );
							$total_needed = 8;
							$repeated     = array();
							while ( count( $repeated ) < $total_needed ) {
								foreach ( $sound_list as $sound ) {
									$repeated[] = $sound;
									if ( count( $repeated ) >= $total_needed ) {
										break;
									}
								}
							}
							foreach ( $repeated as $sound ) :
								?>
								<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
								<?php
							endforeach;
						else :
							// Placeholder tags
							for ( $i = 0; $i < 8; $i++ ) :
								?>
								<span class="music-tag" data-tooltip="Gênero musical do evento">Sound</span>
								<?php
							endfor;
						endif;
						?>
					</div>
				</div>
			</section>

			<!-- Promo Gallery (max 5 Images) -->
			<?php
			$gallery = $event_data['gallery'];
			if ( ! empty( $gallery ) ) :
				?>
				<div class="promo-gallery-slider">
					<div class="promo-track" id="promoTrack">
						<?php foreach ( array_slice( $gallery, 0, 5 ) as $index => $img ) : ?>
							<div class="promo-slide" style="border-radius:12px">
								<img src="<?php echo esc_url( $img ); ?>"
									alt="<?php echo esc_attr( $event_data['title'] . ' - Imagem ' . ( $index + 1 ) ); ?>"
									loading="lazy">
							</div>
						<?php endforeach; ?>
					</div>
					<div class="promo-controls">
						<button class="promo-prev" type="button"><i class="ri-arrow-left-s-line"></i></button>
						<button class="promo-next" type="button"><i class="ri-arrow-right-s-line"></i></button>
					</div>
				</div>
			<?php else : ?>
				<div class="promo-gallery-slider">
					<div class="promo-track" id="promoTrack">
						<div class="promo-slide" style="border-radius:12px" data-tooltip="Galeria promocional do evento - até 5 imagens">
							<div style="height:200px;background:#222;display:flex;align-items:center;justify-content:center;border-radius:12px;">
								<i class="ri-image-2-line" style="font-size:3rem;opacity:0.3;"></i>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- DJ Lineup -->
			<section class="section" id="route_LINE">
				<h2 class="section-title">
					<i class="ri-disc-line"></i> Line-up
				</h2>
				<div class="lineup-list">
					<?php
					$lineup = $event_data['lineup'];
					if ( ! empty( $lineup ) ) :
						foreach ( $lineup as $dj ) :
							$dj_name  = is_array( $dj ) ? ( $dj['name'] ?? '' ) : ( is_object( $dj ) ? $dj->name : '' );
							$dj_photo = is_array( $dj ) ? ( $dj['photo'] ?? '' ) : ( is_object( $dj ) ? $dj->photo : '' );
							$dj_start = is_array( $dj ) ? ( $dj['start_time'] ?? '' ) : ( is_object( $dj ) ? $dj->start_time : '' );
							$dj_end   = is_array( $dj ) ? ( $dj['end_time'] ?? '' ) : ( is_object( $dj ) ? $dj->end_time : '' );
							$dj_slug  = is_array( $dj ) ? ( $dj['slug'] ?? '' ) : ( is_object( $dj ) ? $dj->slug : '' );

							if ( empty( $dj_name ) ) {
								continue;
							}
							?>
							<div class="lineup-card">
								<?php if ( $dj_photo ) : ?>
									<img src="<?php echo esc_url( $dj_photo ); ?>"
										alt="<?php echo esc_attr( $dj_name ); ?>"
										class="lineup-avatar-img"
										loading="lazy">
								<?php else : ?>
									<div class="lineup-avatar" data-tooltip="Foto do DJ">
										<?php echo esc_html( mb_substr( $dj_name, 0, 2 ) ); ?>
									</div>
								<?php endif; ?>
								<div class="lineup-info">
									<h3 class="lineup-name">
										<?php if ( $dj_slug ) : ?>
											<a href="<?php echo esc_url( home_url( '/dj/' . $dj_slug . '/' ) ); ?>"
												target="_blank"
												class="dj-link"><?php echo esc_html( $dj_name ); ?></a>
										<?php else : ?>
											<span class="dj-link"><?php echo esc_html( $dj_name ); ?></span>
										<?php endif; ?>
									</h3>
									<?php if ( $dj_start ) : ?>
										<div class="lineup-time">
											<i class="ri-time-line"></i>
											<span>
												<?php echo esc_html( $dj_start ); ?>
												<?php
												if ( $dj_end ) :
													?>
													- <?php echo esc_html( $dj_end ); ?><?php endif; ?>
											</span>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<?php
						endforeach;
					else :
						?>
						<div class="lineup-card" data-tooltip="Line-up do evento - artistas que irão se apresentar">
							<div class="lineup-avatar">??</div>
							<div class="lineup-info">
								<h3 class="lineup-name"><span class="dj-link">Line-up em breve</span></h3>
								<div class="lineup-time">
									<i class="ri-time-line"></i>
									<span>A confirmar</span>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- Venue Section -->
			<section class="section" id="route_ROUTE">
				<h2 class="section-title">
					<i class="ri-map-pin-2-line"></i>
					<?php echo esc_html( $event_data['venue_name'] ?: 'Local' ); ?>
				</h2>

				<?php if ( $event_data['venue_address'] ) : ?>
					<p class="local-endereco"><?php echo esc_html( $event_data['venue_address'] ); ?></p>
				<?php else : ?>
					<p class="local-endereco" data-tooltip="Endereço completo do local do evento">Endereço a confirmar</p>
				<?php endif; ?>

				<!-- Venue Images Slider (max 5) -->
				<?php
				$venue_images = $event_data['venue_images'];
				if ( ! empty( $venue_images ) ) :
					?>
					<div class="local-images-slider" style="min-height:450px;">
						<div class="local-images-track" id="localTrack" style="min-height:500px;">
							<?php foreach ( array_slice( $venue_images, 0, 5 ) as $img ) : ?>
								<div class="local-image" style="min-height:450px;">
									<img src="<?php echo esc_url( $img ); ?>"
										alt="<?php echo esc_attr( $event_data['venue_name'] ); ?>"
										loading="lazy">
								</div>
							<?php endforeach; ?>
						</div>
						<div class="slider-nav" id="localDots"></div>
					</div>
				<?php else : ?>
					<div class="local-images-slider" style="min-height:200px;" data-tooltip="Fotos do local - até 5 imagens">
						<div class="local-images-track" id="localTrack">
							<div class="local-image" style="min-height:200px;background:#222;display:flex;align-items:center;justify-content:center;border-radius:12px;">
								<i class="ri-building-4-line" style="font-size:3rem;opacity:0.3;"></i>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Map -->
				<?php if ( $event_data['coords']['valid'] ) : ?>
					<?php
					$osm_zoom_option   = (int) get_option( 'event_manager_osm_default_zoom', 14 );
					$osm_zoom          = ( $osm_zoom_option < 8 || $osm_zoom_option > 24 ) ? 14 : $osm_zoom_option;
					$osm_tile_style    = get_option( 'event_manager_osm_tile_style', 'default' );
					$osm_allowed_style = array( 'default', 'light', 'dark' );
					if ( ! in_array( $osm_tile_style, $osm_allowed_style, true ) ) {
						$osm_tile_style = 'default';
					}
					?>
					<div class="map-view"
						id="eventMap"
						style="margin:0 auto;z-index:0;width:100%;height:285px;border-radius:12px;overflow:hidden;"
						data-lat="<?php echo esc_attr( $event_data['coords']['lat'] ); ?>"
						data-lng="<?php echo esc_attr( $event_data['coords']['lng'] ); ?>"
						data-marker="<?php echo esc_attr( $event_data['venue_name'] ); ?>"></div>
					<script>
						(function() {
							function initMap() {
								var mapEl = document.getElementById('eventMap');
								if (!mapEl || typeof L === 'undefined') return;

								var lat = parseFloat(mapEl.dataset.lat);
								var lng = parseFloat(mapEl.dataset.lng);
								if (!lat || !lng) return;

								var defaultZoom = <?php echo (int) $osm_zoom; ?>;
								var tileStyle   = '<?php echo esc_js( $osm_tile_style ); ?>';

								function getTileConfig(style) {
									switch (style) {
										case 'light':
											return {
												url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
												attribution: '© OpenStreetMap © CARTO',
												maxZoom: 22
											};
										case 'dark':
											return {
												url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
												attribution: '© OpenStreetMap © CARTO',
												maxZoom: 22
											};
										default:
											return {
												url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
												attribution: '© OpenStreetMap',
												maxZoom: 19
											};
									}
								}

								var tileConfig = getTileConfig(tileStyle);

								try {
									if (mapEl._leaflet_id) {
										var m = L.map(mapEl);
										m.remove();
									}

									var m = L.map('eventMap', {
										zoomControl: false,
										scrollWheelZoom: false,
										dragging: false,
										touchZoom: false,
										doubleClickZoom: false,
										boxZoom: false,
										keyboard: false,
										attributionControl: false
									}).setView([lat, lng], defaultZoom);

									L.tileLayer(tileConfig.url, {
										maxZoom: tileConfig.maxZoom,
										attribution: tileConfig.attribution
									}).addTo(m);

									L.marker([lat, lng]).addTo(m).bindPopup(mapEl.dataset.marker);

									setTimeout(function() {
										m.invalidateSize();
									}, 100);
								} catch (e) {
									console.error('Map error:', e);
								}
							}

							if (document.readyState === 'loading') {
								document.addEventListener('DOMContentLoaded', initMap);
							} else {
								initMap();
							}
						})();
					</script>
				<?php else : ?>
					<div class="map-view"
						style="height:285px;background:#1a1a1a;border-radius:12px;display:flex;align-items:center;justify-content:center;"
						data-tooltip="Mapa do local - coordenadas GPS do evento">
						<p style="color:#666;text-align:center;">
							<i class="ri-map-pin-line" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
							<?php esc_html_e( 'Mapa disponível em breve', 'apollo-events-manager' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<!-- Route Input -->
				<?php if ( $event_data['coords']['valid'] ) : ?>
					<div class="route-controls" style="transform:translateY(-80px);padding:0 0.5rem;">
						<div class="route-input">
							<i class="ri-map-pin-line"></i>
							<input type="text"
								id="origin-input"
								placeholder="<?php esc_attr_e( 'Seu endereço de partida', 'apollo-events-manager' ); ?>">
						</div>
						<button id="route-btn" class="route-button" type="button">
							<i class="ri-send-plane-line"></i>
						</button>
					</div>
					<script>
						document.getElementById('route-btn')?.addEventListener('click', function() {
							var origin = document.getElementById('origin-input').value;
							if (origin) {
								var url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) +
									'&destination=<?php echo esc_attr( $event_data['coords']['lat'] ); ?>,<?php echo esc_attr( $event_data['coords']['lng'] ); ?>';
								window.open(url, '_blank');
							}
						});
					</script>
				<?php endif; ?>
			</section>

			<!-- Tickets Section -->
			<section class="section" id="route_TICKETS">
				<h2 class="section-title">
					<i class="ri-ticket-2-line"></i> <?php esc_html_e( 'Acessos', 'apollo-events-manager' ); ?>
				</h2>

				<div class="tickets-grid">
					<?php if ( $event_data['tickets_url'] ) : ?>
						<a href="<?php echo esc_url( $event_data['tickets_url'] ); ?>?ref=apollo.rio.br"
							class="ticket-card apollo-click-out-track"
							data-event-id="<?php echo esc_attr( $event_id ); ?>"
							target="_blank"
							rel="noopener noreferrer">
							<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
							<div class="ticket-info">
								<h3 class="ticket-name"><?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Seguir para Bilheteria Digital', 'apollo-events-manager' ); ?> →</span>
							</div>
						</a>
					<?php else : ?>
						<div class="ticket-card disabled" data-tooltip="Link para compra de ingressos">
							<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
							<div class="ticket-info">
								<h3 class="ticket-name"><?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Em breve', 'apollo-events-manager' ); ?></span>
							</div>
						</div>
					<?php endif; ?>

					<!-- Apollo Coupon -->
					<div class="apollo-coupon-detail" data-coupon-code="<?php echo esc_attr( $event_data['coupon'] ?: 'APOLLO' ); ?>">
						<i class="ri-coupon-3-line"></i>
						<span>
						<?php
						printf(
							esc_html__( 'Verifique se o cupom %s está ativo com desconto', 'apollo-events-manager' ),
							'<strong>' . esc_html( $event_data['coupon'] ?: 'APOLLO' ) . '</strong>'
						);
						?>
								</span>
						<button class="copy-code-mini" type="button" onclick="copyPromoCode(this)">
							<i class="ri-file-copy-fill"></i>
						</button>
					</div>

					<!-- Alternative Accesses -->
					<?php if ( $event_data['alternative_access_url'] ) : ?>
						<a href="<?php echo esc_url( $event_data['alternative_access_url'] ); ?>"
							class="ticket-card"
							target="_blank">
							<div class="ticket-icon"><i class="ri-list-check"></i></div>
							<div class="ticket-info">
								<h3 class="ticket-name"><?php esc_html_e( 'Acessos Diversos', 'apollo-events-manager' ); ?></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Seguir para Acessos Diversos', 'apollo-events-manager' ); ?> →</span>
							</div>
						</a>
					<?php else : ?>
						<div class="ticket-card disabled" data-tooltip="Formas alternativas de acesso ao evento">
							<div class="ticket-icon"><i class="ri-list-check"></i></div>
							<div class="ticket-info">
								<h3 class="ticket-name"><?php esc_html_e( 'Acessos Diversos', 'apollo-events-manager' ); ?></h3>
								<span class="ticket-cta"><?php esc_html_e( 'Não disponível', 'apollo-events-manager' ); ?></span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- REGISTROS Section (Comments) -->
			<section class="section" id="route_REGISTROS">
				<h2 class="section-title">
					<i class="ri-chat-history-line"></i> <?php esc_html_e( 'Registros', 'apollo-events-manager' ); ?>
				</h2>

				<div class="registros-container">
					<?php if ( ! empty( $comments ) ) : ?>
						<div class="registros-list">
							<?php
							foreach ( $comments as $comment ) :
								$author_avatar = get_avatar_url( $comment->comment_author_email, array( 'size' => 48 ) );
								$author_name   = $comment->comment_author;
								$comment_date  = mysql2date( 'd M Y', $comment->comment_date );
								?>
								<div class="registro-card">
									<div class="registro-avatar" style="background-image: url('<?php echo esc_url( $author_avatar ); ?>')"></div>
									<div class="registro-content">
										<div class="registro-header">
											<span class="registro-author"><?php echo esc_html( $author_name ); ?></span>
											<span class="registro-date"><?php echo esc_html( $comment_date ); ?></span>
										</div>
										<p class="registro-text"><?php echo wp_kses_post( $comment->comment_content ); ?></p>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="registros-empty" data-tooltip="Comentários e registros sobre o evento">
							<i class="ri-chat-smile-3-line" style="font-size:2.5rem;opacity:0.3;"></i>
							<p><?php esc_html_e( 'Nenhum registro ainda. Seja o primeiro a comentar!', 'apollo-events-manager' ); ?></p>
						</div>
					<?php endif; ?>

					<!-- Comment Form -->
					<?php if ( is_user_logged_in() ) : ?>
						<div class="registro-form">
							<form id="registroForm" method="post">
								<?php wp_nonce_field( 'apollo_event_comment', 'apollo_comment_nonce' ); ?>
								<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
								<div class="registro-input-wrap">
									<textarea name="registro_content"
										id="registroContent"
										placeholder="<?php esc_attr_e( 'Deixe seu registro sobre este evento...', 'apollo-events-manager' ); ?>"
										rows="2"
										maxlength="500"></textarea>
									<button type="submit" class="registro-submit">
										<i class="ri-send-plane-fill"></i>
									</button>
								</div>
							</form>
						</div>
					<?php else : ?>
						<div class="registro-login-prompt">
							<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="registro-login-btn">
								<i class="ri-user-line"></i>
								<?php esc_html_e( 'Faça login para deixar um registro', 'apollo-events-manager' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>

				<style>
					.registros-container {
						margin-top: 1rem;
					}

					.registros-list {
						display: flex;
						flex-direction: column;
						gap: 1rem;
						margin-bottom: 1.5rem;
					}

					.registro-card {
						display: flex;
						gap: 0.75rem;
						padding: 1rem;
						background: rgba(255, 255, 255, 0.03);
						border-radius: 12px;
						border: 1px solid rgba(255, 255, 255, 0.06);
					}

					.registro-avatar {
						width: 40px;
						height: 40px;
						border-radius: 50%;
						background-size: cover;
						background-position: center;
						flex-shrink: 0;
					}

					.registro-content {
						flex: 1;
					}

					.registro-header {
						display: flex;
						justify-content: space-between;
						align-items: center;
						margin-bottom: 0.25rem;
					}

					.registro-author {
						font-weight: 600;
						font-size: 0.875rem;
					}

					.registro-date {
						font-size: 0.75rem;
						opacity: 0.5;
					}

					.registro-text {
						font-size: 0.875rem;
						line-height: 1.5;
						margin: 0;
						opacity: 0.9;
					}

					.registros-empty {
						text-align: center;
						padding: 2rem;
						background: rgba(255, 255, 255, 0.02);
						border-radius: 12px;
						margin-bottom: 1rem;
					}

					.registros-empty p {
						margin: 0.5rem 0 0;
						opacity: 0.5;
						font-size: 0.875rem;
					}

					.registro-form {
						margin-top: 1rem;
					}

					.registro-input-wrap {
						display: flex;
						gap: 0.5rem;
						align-items: flex-end;
					}

					.registro-input-wrap textarea {
						flex: 1;
						background: rgba(255, 255, 255, 0.05);
						border: 1px solid rgba(255, 255, 255, 0.1);
						border-radius: 12px;
						padding: 0.75rem 1rem;
						color: inherit;
						font-size: 0.875rem;
						resize: none;
						font-family: inherit;
					}

					.registro-input-wrap textarea:focus {
						outline: none;
						border-color: var(--apollo-accent, #00ff88);
					}

					.registro-submit {
						width: 44px;
						height: 44px;
						border-radius: 50%;
						background: var(--apollo-accent, #00ff88);
						color: #000;
						border: none;
						cursor: pointer;
						display: flex;
						align-items: center;
						justify-content: center;
						font-size: 1.25rem;
						transition: transform 0.2s;
					}

					.registro-submit:hover {
						transform: scale(1.05);
					}

					.registro-login-prompt {
						text-align: center;
						margin-top: 1rem;
					}

					.registro-login-btn {
						display: inline-flex;
						align-items: center;
						gap: 0.5rem;
						padding: 0.75rem 1.5rem;
						background: rgba(255, 255, 255, 0.05);
						border-radius: 999px;
						color: inherit;
						text-decoration: none;
						font-size: 0.875rem;
						transition: background 0.2s;
					}

					.registro-login-btn:hover {
						background: rgba(255, 255, 255, 0.1);
					}
				</style>
			</section>

			<!-- Final Image -->
			<?php
			if ( $event_data['final_image'] ) :
				$final_url = is_numeric( $event_data['final_image'] ) ? wp_get_attachment_url( $event_data['final_image'] ) : $event_data['final_image'];
				if ( $final_url ) :
					?>
					<section class="section">
						<div class="secondary-image" style="margin-bottom:3rem;">
							<img src="<?php echo esc_url( $final_url ); ?>"
								alt="<?php echo esc_attr( $event_data['title'] ); ?>"
								loading="lazy">
						</div>
					</section>
					<?php
			endif;
			endif;
			?>

			<!-- Protection Notice -->
			<section class="section">
				<div class="respaldo_eve">
					<?php esc_html_e( '*A organização e execução deste evento cabem integralmente aos seus idealizadores.', 'apollo-events-manager' ); ?>
				</div>
			</section>
		</div>

		<!-- Bottom Bar -->
		<div class="bottom-bar">
			<a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
				<i class="ri-ticket-fill"></i>
				<span><?php esc_html_e( 'Tickets', 'apollo-events-manager' ); ?></span>
			</a>
			<button class="bottom-btn secondary" type="button" id="bottomShareBtn">
				<i class="ri-share-forward-line"></i>
			</button>
		</div>
	</div>

	<!-- Copy Promo Code Script -->
	<script>
		function copyPromoCode(btn) {
			var coupon = btn.closest('.apollo-coupon-detail')?.dataset?.couponCode || 'APOLLO';
			if (navigator.clipboard) {
				navigator.clipboard.writeText(coupon).then(function() {
					var icon = btn.querySelector('i');
					icon.className = 'ri-check-line';
					setTimeout(function() {
						icon.className = 'ri-file-copy-fill';
					}, 2000);
				});
			}
		}

		// Share functionality
		document.getElementById('bottomShareBtn')?.addEventListener('click', function() {
			var shareData = {
				title: '<?php echo esc_js( $event_data['title'] ); ?>',
				text: '<?php echo esc_js( $event_data['title'] . ' - ' . $event_data['formatted_date'] ); ?>',
				url: '<?php echo esc_url( get_permalink( $event_id ) ); ?>'
			};

			if (navigator.share) {
				navigator.share(shareData);
			} else if (navigator.clipboard) {
				navigator.clipboard.writeText(shareData.url);
				alert('Link copiado!');
			}
		});

		// Comment form submission
		document.getElementById('registroForm')?.addEventListener('submit', function(e) {
			e.preventDefault();
			var form = this;
			var content = document.getElementById('registroContent').value.trim();
			if (!content) return;

			var formData = new FormData(form);
			formData.append('action', 'apollo_submit_event_comment');

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: formData
				})
				.then(function(r) {
					return r.json();
				})
				.then(function(data) {
					if (data.success) {
						location.reload();
					} else {
						alert(data.data?.message || 'Erro ao enviar');
					}
				})
				.catch(function() {
					alert('Erro ao enviar');
				});
		});
	</script>

	<?php if ( ! $is_modal ) : ?>
		<script src="https://assets.apollo.rio.br/event-page.js"></script>
		<?php wp_footer(); ?>
	</body>

	</html>
<?php endif; ?>
