<?php

declare(strict_types=1);
/**
 * Events Listing
 * File: template-parts/events/listing.php
 * REST: GET /eventos, GET /eventos/proximos, GET /eventos/passados
 */

$view     = sanitize_text_field( $_GET['view'] ?? 'upcoming' );
$page     = max( 1, (int) ( $_GET['pg'] ?? 1 ) );
$category = sanitize_text_field( $_GET['cat'] ?? '' );
$user_id  = get_current_user_id();

$events = ( $view === 'past' )
	? apollo_get_past_events(
		array(
			'per_page' => 12,
			'page'     => $page,
		)
	)
	: apollo_get_upcoming_events(
		array(
			'per_page' => 12,
			'page'     => $page,
			'category' => $category,
		)
	);

$categories = get_terms(
	array(
		'taxonomy'   => 'event_listing_category',
		'hide_empty' => true,
	)
);
?>

<div class="apollo-events-listing">

	<div class="listing-header">
		<div class="tabs-nav">
			<a href="?view=upcoming" class="tab-btn <?php echo $view === 'upcoming' ? 'active' : ''; ?>">Próximos</a>
			<a href="?view=past" class="tab-btn <?php echo $view === 'past' ? 'active' : ''; ?>">Passados</a>
			<?php if ( $user_id ) : ?>
			<a href="?view=my" class="tab-btn <?php echo $view === 'my' ? 'active' : ''; ?>">Meus Eventos</a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $categories ) ) : ?>
		<select class="filter-select" onchange="location.href='?view=<?php echo $view; ?>&cat='+this.value">
			<option value="">Todas categorias</option>
			<?php foreach ( $categories as $cat ) : ?>
			<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
				<?php echo esc_html( $cat->name ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php endif; ?>
	</div>

	<div class="events-grid">
		<?php if ( ! empty( $events ) ) : ?>
			<?php
			foreach ( $events as $event ) :
				$data = apollo_get_event( $event->ID );
				$rsvp = $user_id ? apollo_get_user_event_rsvp( $event->ID, $user_id ) : null;
				?>
			<article class="event-card">
				<a href="<?php echo get_permalink( $event->ID ); ?>" class="event-image">
					<?php if ( $data['thumbnail'] ) : ?>
					<img src="<?php echo esc_url( $data['thumbnail'] ); ?>" alt="">
					<?php else : ?>
					<div class="event-placeholder"><i class="ri-calendar-event-line"></i></div>
					<?php endif; ?>

					<div class="event-date-badge">
						<span class="day"><?php echo date( 'd', strtotime( $data['start_date'] ) ); ?></span>
						<span class="month"><?php echo date( 'M', strtotime( $data['start_date'] ) ); ?></span>
					</div>
				</a>

				<div class="event-body">
					<?php if ( ! empty( $data['categories'] ) ) : ?>
					<span class="event-category"><?php echo esc_html( $data['categories'][0]->name ); ?></span>
					<?php endif; ?>

					<h3 class="event-title">
						<a href="<?php echo get_permalink( $event->ID ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
					</h3>

					<div class="event-meta">
						<span><i class="ri-time-line"></i> <?php echo apollo_format_event_date( $data['start_date'], $data['start_time'] ); ?></span>
						<?php if ( $data['venue'] ) : ?>
						<span><i class="ri-map-pin-line"></i> <?php echo esc_html( $data['venue'] ); ?></span>
						<?php endif; ?>
					</div>

					<div class="event-stats">
						<span><i class="ri-user-line"></i> <?php echo $data['rsvp_count']; ?> confirmados</span>
						<span><i class="ri-heart-line"></i> <?php echo $data['interested_count']; ?> interessados</span>
					</div>
				</div>

				<div class="event-actions">
					<?php if ( $rsvp === 'going' ) : ?>
					<button class="btn btn-sm btn-success" disabled><i class="ri-check-line"></i> Confirmado</button>
					<?php elseif ( $user_id ) : ?>
					<button class="btn btn-sm btn-primary btn-rsvp" data-event-id="<?php echo $event->ID; ?>">
						<i class="ri-calendar-check-line"></i> Confirmar
					</button>
					<?php endif; ?>
					<button class="btn btn-sm btn-icon btn-interest <?php echo $rsvp === 'interested' ? 'active' : ''; ?>"
							data-event-id="<?php echo $event->ID; ?>" title="Tenho interesse">
						<i class="ri-heart-<?php echo $rsvp === 'interested' ? 'fill' : 'line'; ?>"></i>
					</button>
				</div>
			</article>
			<?php endforeach; ?>
		<?php else : ?>
		<div class="empty-state col-span-full">
			<i class="ri-calendar-line"></i>
			<p><?php echo $view === 'past' ? 'Nenhum evento passado.' : 'Nenhum evento próximo.'; ?></p>
		</div>
		<?php endif; ?>
	</div>

	<nav class="pagination">
		<?php if ( $page > 1 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page - 1 ); ?>" class="btn btn-outline">&larr; Anterior</a>
		<?php endif; ?>
		<?php if ( count( $events ) >= 12 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page + 1 ); ?>" class="btn btn-outline">Próximo &rarr;</a>
		<?php endif; ?>
	</nav>

</div>
<script src="https://cdn.apollo.rio.br/"></script>
