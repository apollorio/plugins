<?php

declare(strict_types=1);
/**
 * Single Event Page
 * File: template-parts/events/single.php
 * REST: GET /eventos/{id}, POST /eventos/{id}/confirmar, GET /eventos/{id}/convidados
 */

$event_id = get_the_ID();
$event    = apollo_get_event( $event_id );
if ( ! $event ) {
	echo '<div class="error-state">Evento não encontrado.</div>';
	return;
}

$user_id  = get_current_user_id();
$rsvp     = $user_id ? apollo_get_user_event_rsvp( $event_id, $user_id ) : null;
$is_owner = $user_id && $event['organizer']->ID == $user_id;
?>

<div class="apollo-event-single">

	<div class="event-hero" style="<?php echo $event['thumbnail'] ? 'background-image:url(' . esc_url( $event['thumbnail'] ) . ')' : ''; ?>">
		<div class="hero-overlay">
			<div class="event-date-large">
				<span class="day"><?php echo date( 'd', strtotime( $event['start_date'] ) ); ?></span>
				<span class="month"><?php echo strtoupper( date( 'M', strtotime( $event['start_date'] ) ) ); ?></span>
				<span class="year"><?php echo date( 'Y', strtotime( $event['start_date'] ) ); ?></span>
			</div>
		</div>
	</div>

	<div class="event-content-grid">

		<main class="event-main">

			<div class="event-header">
				<?php if ( ! empty( $event['categories'] ) ) : ?>
				<div class="event-categories">
					<?php foreach ( $event['categories'] as $cat ) : ?>
					<a href="<?php echo get_term_link( $cat ); ?>" class="category-tag"><?php echo esc_html( $cat->name ); ?></a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<h1 class="event-title"><?php echo esc_html( $event['title'] ); ?></h1>

				<div class="event-organizer">
					<img src="<?php echo get_avatar_url( $event['organizer']->ID, array( 'size' => 32 ) ); ?>" alt="">
					<span>Por <a href="<?php echo home_url( '/membro/' . $event['organizer']->user_nicename ); ?>">
						<?php echo esc_html( $event['organizer']->display_name ); ?>
					</a></span>
				</div>
			</div>

			<div class="event-description">
				<?php echo wp_kses_post( $event['content'] ); ?>
			</div>

			<?php if ( ! empty( $event['sounds'] ) ) : ?>
			<div class="event-sounds">
				<h3>Som</h3>
				<div class="tags-list">
					<?php foreach ( $event['sounds'] as $sound ) : ?>
					<span class="tag"><?php echo esc_html( $sound->name ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="event-attendees-section">
				<h3>Quem vai <span class="count">(<?php echo $event['rsvp_count']; ?>)</span></h3>
				<?php
				$attendees = apollo_get_event_attendees( $event_id, 12 );
				if ( ! empty( $attendees ) ) :
					?>
				<div class="attendees-preview">
					<?php foreach ( $attendees as $attendee ) : ?>
					<a href="<?php echo home_url( '/membro/' . $attendee->user_nicename ); ?>" class="attendee-avatar" title="<?php echo esc_attr( $attendee->display_name ); ?>">
						<img src="<?php echo apollo_get_user_avatar( $attendee->ID, 40 ); ?>" alt="">
					</a>
					<?php endforeach; ?>
					<?php if ( $event['rsvp_count'] > 12 ) : ?>
					<span class="more-count">+<?php echo $event['rsvp_count'] - 12; ?></span>
					<?php endif; ?>
				</div>
				<?php else : ?>
				<p class="empty-text">Ninguém confirmou presença ainda.</p>
				<?php endif; ?>
			</div>

		</main>

		<aside class="event-sidebar">

			<div class="sidebar-card actions-card">
				<div class="rsvp-buttons">
					<?php if ( $rsvp === 'going' ) : ?>
					<button class="btn btn-success btn-lg btn-full" disabled>
						<i class="ri-check-double-line"></i> Presença Confirmada
					</button>
					<button class="btn btn-outline btn-cancel-rsvp" data-event-id="<?php echo $event_id; ?>">Cancelar</button>
					<?php elseif ( $user_id ) : ?>
					<button class="btn btn-primary btn-lg btn-full btn-rsvp" data-event-id="<?php echo $event_id; ?>">
						<i class="ri-calendar-check-line"></i> Confirmar Presença
					</button>
					<?php else : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary btn-lg btn-full">
						Entrar para confirmar
					</a>
					<?php endif; ?>

					<?php if ( $user_id ) : ?>
					<button class="btn btn-outline btn-interest <?php echo $rsvp === 'interested' ? 'active' : ''; ?>"
							data-event-id="<?php echo $event_id; ?>">
						<i class="ri-heart-<?php echo $rsvp === 'interested' ? 'fill' : 'line'; ?>"></i>
						<?php echo $rsvp === 'interested' ? 'Interessado' : 'Tenho interesse'; ?>
					</button>
					<?php endif; ?>
				</div>

				<div class="share-buttons">
					<button class="btn btn-icon" onclick="navigator.share({title:'<?php echo esc_js( $event['title'] ); ?>',url:'<?php echo get_permalink(); ?>'})" title="Compartilhar">
						<i class="ri-share-line"></i>
					</button>
				</div>
			</div>

			<div class="sidebar-card info-card">
				<div class="info-item">
					<i class="ri-calendar-line"></i>
					<div>
						<strong><?php echo apollo_format_event_date( $event['start_date'] ); ?></strong>
						<?php if ( $event['end_date'] && $event['end_date'] !== $event['start_date'] ) : ?>
						<span> até <?php echo apollo_format_event_date( $event['end_date'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<div class="info-item">
					<i class="ri-time-line"></i>
					<div>
						<strong><?php echo esc_html( $event['start_time'] ); ?></strong>
						<?php if ( $event['end_time'] ) : ?>
						<span> - <?php echo esc_html( $event['end_time'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $event['venue'] ) : ?>
				<div class="info-item">
					<i class="ri-map-pin-line"></i>
					<div>
						<strong><?php echo esc_html( $event['venue'] ); ?></strong>
						<?php if ( $event['address'] ) : ?>
						<span class="address"><?php echo esc_html( $event['address'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $event['price'] ) : ?>
				<div class="info-item">
					<i class="ri-ticket-line"></i>
					<div>
						<strong><?php echo esc_html( $event['price'] ); ?></strong>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $is_owner ) : ?>
			<div class="sidebar-card admin-card">
				<h4>Gerenciar Evento</h4>
				<a href="<?php echo get_edit_post_link( $event_id ); ?>" class="btn btn-outline btn-sm btn-full">
					<i class="ri-edit-line"></i> Editar
				</a>
				<a href="<?php echo home_url( '/evento/' . $event_id . '/lista' ); ?>" class="btn btn-outline btn-sm btn-full">
					<i class="ri-list-check"></i> Ver Lista
				</a>
			</div>
			<?php endif; ?>

		</aside>

	</div>

</div>

<?php
function apollo_get_event_attendees( $event_id, $limit = 20 ) {
	global $wpdb;
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$wpdb->prefix}apollo_event_rsvp
         WHERE event_id = %d AND status = 'going'
         ORDER BY created_at DESC LIMIT %d",
			$event_id,
			$limit
		)
	);

	if ( empty( $user_ids ) ) {
		return array();
	}
	return get_users( array( 'include' => $user_ids ) );
}
?>
<script src="https://cdn.apollo.rio.br/"></script>
