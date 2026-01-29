<?php
// Lineup Block
?>
<section class="section" id="route_LINE">
	<h2 class="section-title">
	<i class="ri-disc-line"></i> Line-up
	</h2>
	<div class="lineup-list">
	<?php
	foreach ( $dj_slots as $slot ) :
		$dj = get_post( $slot['dj_id'] );
		if ( ! $dj ) {
			continue;
		}
		$dj_name      = $dj->post_title;
		$dj_permalink = get_permalink( $dj->ID );
		$dj_image     = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' );
		?>
	<div class="lineup-card">
		<?php if ( $dj_image ) : ?>
		<img src="<?php echo esc_url( $dj_image ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>" class="lineup-avatar-img">
		<?php else : ?>
		<div class="lineup-avatar-fallback"><?php echo esc_html( apollo_initials( $dj_name ) ); ?></div>
		<?php endif; ?>
		<div class="lineup-info">
		<h3 class="lineup-name">
			<a href="<?php echo esc_url( $dj_permalink ); ?>" target="_blank" class="dj-link"><?php echo esc_html( $dj_name ); ?></a>
		</h3>
		<?php if ( $slot['start'] && $slot['end'] ) : ?>
		<div class="lineup-time">
			<i class="ri-time-line"></i>
			<span><?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?></span>
		</div>
		<?php endif; ?>
		</div>
	</div>
	<?php endforeach; ?>
	</div>
</section>
