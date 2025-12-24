<?php
// Promo Gallery Block
?>
<section class="section">
	<h2 class="section-title">
	<i class="ri-image-line"></i> Galeria
	</h2>
	<div class="promo-gallery-slider">
	<div class="promo-track" id="promoTrack">
		<?php foreach ( $promo_images as $image_url ) : ?>
		<div class="promo-slide"><img src="<?php echo esc_url( $image_url ); ?>" alt="Promo"></div>
		<?php endforeach; ?>
	</div>
	<?php if ( count( $promo_images ) > 1 ) : ?>
	<div class="promo-controls">
		<button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
		<button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
	</div>
	<?php endif; ?>
	</div>
</section>
