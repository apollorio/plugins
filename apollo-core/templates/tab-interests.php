<?php
/**
 * Tab: Interests/Events
 * File: template-parts/user/tab-interests.php
 */

$user_id = get_current_user_id();
$events  = apollo_get_user_events(
	$user_id,
	array(
		'status' => array( 'going', 'maybe' ),
		'limit'  => 10,
	)
);
?>

<div class="section-header">
	<div>
		<div class="section-title">Próximos Eventos</div>
		<div class="section-desc">Eventos marcados como 'Ir' ou 'Talvez'.</div>
	</div>
	<button class="btn-pill-sm"><i class="ri-filter-3-line"></i> Filtrar</button>
</div>

<div class="cards-grid">
	<?php if ( ! empty( $events ) ) : ?>
		<?php
		foreach ( $events as $event ) :
			$status       = get_user_meta( $user_id, 'event_status_' . $event->ID, true );
			$status_class = $status === 'going' ? 'status-going' : 'status-maybe';
			$status_label = $status === 'going' ? 'Vou' : 'Talvez';
			$event_date   = get_post_meta( $event->ID, 'event_date', true );
			$event_time   = get_post_meta( $event->ID, 'event_time', true );
			$event_tags   = get_the_terms( $event->ID, 'event_tag' );
			?>
		<article class="apollo-card">
			<div class="card-top">
				<span class="card-meta"><?php echo apollo_format_event_datetime( $event_date, $event_time ); ?></span>
				<span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
			</div>
			<h3 class="card-title"><?php echo esc_html( $event->post_title ); ?></h3>
			<p class="card-text"><?php echo wp_trim_words( $event->post_excerpt, 20 ); ?></p>
			<div class="card-footer">
				<div class="card-tags">
					<?php if ( $event_tags && ! is_wp_error( $event_tags ) ) : ?>
						<?php foreach ( array_slice( $event_tags, 0, 2 ) as $tag ) : ?>
							<span class="mini-tag"><?php echo esc_html( $tag->name ); ?></span>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<a href="<?php echo get_permalink( $event->ID ); ?>" class="link-action">Ver <i class="ri-arrow-right-line"></i></a>
			</div>
		</article>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="card-text">Nenhum evento marcado ainda.</p>
	<?php endif; ?>
</div>
