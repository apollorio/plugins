<?php
// Info Block
?>
<section class="section">
	<h2 class="section-title">
	<i class="ri-brain-ai-3-fill"></i> Info
	</h2>
	<div class="info-card">
	<p class="info-text"><?php echo esc_html( $event_description ); ?></p>
	</div>
	<div class="music-tags-marquee">
	<div class="music-tags-track">
		<?php foreach ( $sounds as $sound ) : ?>
		<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
		<?php endforeach; ?>
	</div>
	</div>
</section>
