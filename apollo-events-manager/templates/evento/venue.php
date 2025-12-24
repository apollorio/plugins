<?php
// Venue Block
?>
<section class="section" id="route_ROUTE">
	<h2 class="section-title">
	<i class="ri-map-pin-2-line"></i> <?php echo esc_html( $venue_name ); ?>
	</h2>
	<p style="margin:0.5rem 0 1.5rem 0; font-size:0.95rem;">
	<?php echo esc_html( $venue_address ); ?>
	</p>
	<div class="local-images-slider">
	<div class="local-images-track" id="localTrack">
		<?php foreach ( $venue_images as $image_url ) : ?>
		<div class="local-image"><img src="<?php echo esc_url( $image_url ); ?>" alt="Venue"></div>
		<?php endforeach; ?>
	</div>
	<?php if ( count( $venue_images ) > 1 ) : ?>
	<div class="slider-nav" id="localDots"></div>
	<?php endif; ?>
	</div>
	<div class="map-view" id="mapView">
	<?php if ( $coords['lat'] && $coords['lng'] ) : ?>
		<div id="eventMap" style="width: 100%; height: 100%; border-radius: var(--radius-card);"></div>
	<?php else : ?>
		Mapa não disponível
	<?php endif; ?>
	</div>
	<div class="route-controls">
	<div class="route-input glass">
		<i class="ri-map-pin-line"></i>
		<input type="text" id="origin-input" placeholder="Seu endereço de partida">
	</div>
	<button id="route-btn" class="route-button"><i class="ri-send-plane-line"></i></button>
	</div>
</section>
