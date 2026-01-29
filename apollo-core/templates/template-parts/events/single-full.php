<?php

declare(strict_types=1);
/**
 * Apollo Single Event Template - Full Page
 *
 * Complete single event page with all sections and functionality
 * Based on: single-event-page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

$event_id = get_the_ID();
if (! $event_id) {
	return;
}

// Event Meta Data
$event_date       = get_post_meta($event_id, '_event_date', true);
$event_time_start = get_post_meta($event_id, '_event_time_start', true);
$event_time_end   = get_post_meta($event_id, '_event_time_end', true);
$event_venue      = get_post_meta($event_id, '_event_venue', true);
$event_venue_id   = get_post_meta($event_id, '_event_venue_id', true);
$event_address    = get_post_meta($event_id, '_event_address', true);
$event_coords     = get_post_meta($event_id, '_event_coordinates', true);
$event_price_type = get_post_meta($event_id, '_event_price_type', true);
$event_price      = get_post_meta($event_id, '_event_price', true);
$ticket_link      = get_post_meta($event_id, '_event_ticket_link', true);
$event_privacy    = get_post_meta($event_id, '_event_privacy', true);
$event_capacity   = get_post_meta($event_id, '_event_capacity', true);
$age_restriction  = get_post_meta($event_id, '_event_age_restriction', true);
$dress_code       = get_post_meta($event_id, '_event_dress_code', true);

// Event Producer
$producer_id   = get_post_meta($event_id, '_event_producer', true);
$producer      = $producer_id ? get_userdata($producer_id) : null;
$producer_name = $producer ? $producer->display_name : '';
$producer_avatar = $producer ? get_avatar_url($producer_id, array('size' => 80)) : '';

// Associated Community
$community_id = get_post_meta($event_id, '_event_community', true);
$community    = $community_id ? get_post($community_id) : null;

// Genres
$genres = wp_get_post_terms($event_id, 'event_genre', array('fields' => 'names'));
$genres = is_array($genres) ? $genres : array();

// DJs / Artists
$dj_ids = get_post_meta($event_id, '_event_djs', true);
$djs    = $dj_ids ? array_filter(array_map('get_post', explode(',', $dj_ids))) : array();

// Event Stats
$interested_count = (int) get_post_meta($event_id, '_event_interested_count', true);
$confirmed_count  = (int) get_post_meta($event_id, '_event_confirmed_count', true);
$views_count      = (int) get_post_meta($event_id, '_event_views', true);

// User interaction states
$current_user_id  = get_current_user_id();
$user_interested  = get_post_meta($event_id, '_interested_users', true);
$user_interested  = is_array($user_interested) ? $user_interested : array();
$is_interested    = $current_user_id && in_array($current_user_id, $user_interested, true);
$user_confirmed   = get_post_meta($event_id, '_confirmed_users', true);
$user_confirmed   = is_array($user_confirmed) ? $user_confirmed : array();
$is_confirmed     = $current_user_id && in_array($current_user_id, $user_confirmed, true);

// Cover Image
$cover_image = get_the_post_thumbnail_url($event_id, 'large');
if (! $cover_image) {
	$cover_image = 'https://assets.apollo.rio.br/i/placeholder-event.jpg';
}

// Gallery
$gallery = get_post_meta($event_id, '_event_gallery', true);
$gallery = is_array($gallery) ? $gallery : array();

// Date formatting
$date_obj = $event_date ? DateTime::createFromFormat('Y-m-d', $event_date) : null;

?>
<article class="single-event-full" data-event-id="<?php echo esc_attr($event_id); ?>">

	<!-- Cover Header -->
	<header class="event-hero" style="background-image: url('<?php echo esc_url($cover_image); ?>');">
		<div class="hero-overlay"></div>

		<div class="hero-content">
			<!-- Breadcrumb / Back -->
			<nav class="event-nav">
				<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="nav-back">
					<i class="i-arrow-left-v" aria-hidden="true"></i>
					Voltar
				</a>
			</nav>

			<!-- Main Info Card -->
			<div class="hero-main-info">
				<div class="date-badge">
					<span class="weekday"><?php echo $date_obj ? $date_obj->format('l') : ''; ?></span>
					<span class="day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
					<span class="month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
				</div>

				<div class="event-details">
					<h1 class="title"><?php the_title(); ?></h1>

					<div class="quick-meta">
						<span class="meta">
							<i class="i-time-v" aria-hidden="true"></i>
							<?php echo esc_html($event_time_start); ?>
							<?php if ($event_time_end) : ?>
								- <?php echo esc_html($event_time_end); ?>
							<?php endif; ?>
						</span>
						<span class="meta">
							<i class="i-map-pin-v" aria-hidden="true"></i>
							<?php echo esc_html($event_venue); ?>
						</span>
					</div>

					<?php if (! empty($genres)) : ?>
						<div class="genre-tags">
							<?php foreach ($genres as $g) : ?>
								<span class="genre"><?php echo esc_html($g); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Hero Actions -->
			<div class="hero-actions">
				<button type="button" class="action-btn share-trigger" title="Compartilhar">
					<i class="i-share-v" aria-hidden="true"></i>
				</button>
				<button type="button" class="action-btn bookmark-trigger <?php echo $is_interested ? 'active' : ''; ?>" title="Salvar">
					<i class="i-bookmark-v" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</header>

	<!-- Stats Bar -->
	<div class="stats-bar">
		<div class="stats-wrap">
			<div class="stat">
				<i class="i-heart-v" aria-hidden="true"></i>
				<span class="val interested-val"><?php echo number_format($interested_count); ?></span>
				<span class="label">Interessados</span>
			</div>
			<div class="stat">
				<i class="i-check-v" aria-hidden="true"></i>
				<span class="val confirmed-val"><?php echo number_format($confirmed_count); ?></span>
				<span class="label">Confirmados</span>
			</div>
			<div class="stat">
				<i class="i-eye-v" aria-hidden="true"></i>
				<span class="val"><?php echo number_format($views_count); ?></span>
				<span class="label">Visualizações</span>
			</div>
		</div>
	</div>

	<!-- Main Content -->
	<div class="content-grid">
		<main class="main-col">

			<!-- About Section -->
			<section class="section about-box">
				<h2 class="section-head">
					<i class="i-information-v" aria-hidden="true"></i>
					Sobre o Evento
				</h2>
				<div class="section-body">
					<?php the_content(); ?>
				</div>
			</section>

			<!-- Line-up Section -->
			<?php if (! empty($djs)) : ?>
				<section class="section lineup-box">
					<h2 class="section-head">
						<i class="i-disc-v" aria-hidden="true"></i>
						Line-up
					</h2>
					<div class="lineup-list">
						<?php foreach ($djs as $dj) :
							$dj_avatar = get_the_post_thumbnail_url($dj->ID, 'thumbnail');
							$dj_genres = wp_get_post_terms($dj->ID, 'dj_genre', array('fields' => 'names'));
						?>
							<a href="<?php echo get_permalink($dj->ID); ?>" class="dj-item">
								<div class="dj-photo" style="background-image: url('<?php echo esc_url($dj_avatar); ?>');"></div>
								<div class="dj-details">
									<h4><?php echo esc_html($dj->post_title); ?></h4>
									<?php if (! empty($dj_genres)) : ?>
										<p><?php echo esc_html(implode(', ', array_slice($dj_genres, 0, 2))); ?></p>
									<?php endif; ?>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Gallery Section -->
			<?php if (! empty($gallery)) : ?>
				<section class="section gallery-box">
					<h2 class="section-head">
						<i class="i-gallery-v" aria-hidden="true"></i>
						Galeria
					</h2>
					<div class="gallery-mosaic">
						<?php foreach ($gallery as $image_id) :
							$image_url = wp_get_attachment_image_url($image_id, 'medium');
							$image_full = wp_get_attachment_image_url($image_id, 'full');
						?>
							<a href="<?php echo esc_url($image_full); ?>" class="gallery-thumb" data-lightbox="event">
								<img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy">
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Location Section -->
			<section class="section location-box">
				<h2 class="section-head">
					<i class="i-map-pin-v" aria-hidden="true"></i>
					Local
				</h2>
				<div class="location-content">
					<div class="venue-info">
						<?php if ($event_venue_id) :
							$venue = get_post($event_venue_id);
							$venue_image = get_the_post_thumbnail_url($event_venue_id, 'thumbnail');
						?>
							<div class="venue-thumb" style="background-image: url('<?php echo esc_url($venue_image); ?>');"></div>
							<div class="venue-text">
								<h4><a href="<?php echo get_permalink($event_venue_id); ?>"><?php echo esc_html($event_venue); ?></a></h4>
								<p><?php echo esc_html($event_address); ?></p>
							</div>
						<?php else : ?>
							<div class="venue-text">
								<h4><?php echo esc_html($event_venue); ?></h4>
								<p><?php echo esc_html($event_address); ?></p>
							</div>
						<?php endif; ?>
						<a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($event_address); ?>" class="directions-btn" target="_blank" rel="noopener">
							<i class="i-route-v" aria-hidden="true"></i>
							Como chegar
						</a>
					</div>

					<?php if ($event_coords) :
						$coords = explode(',', $event_coords);
					?>
						<div class="map-wrap">
							<div id="event-map-full" data-lat="<?php echo esc_attr($coords[0] ?? ''); ?>" data-lng="<?php echo esc_attr($coords[1] ?? ''); ?>"></div>
						</div>
					<?php endif; ?>
				</div>
			</section>

		</main>

		<!-- Sidebar -->
		<aside class="sidebar-col">

			<!-- Action Card -->
			<div class="card action-card">
				<div class="price-display">
					<?php if ('free' === $event_price_type) : ?>
						<span class="price free">Gratuito</span>
					<?php elseif ('donation' === $event_price_type) : ?>
						<span class="price">Contribuição</span>
					<?php else : ?>
						<span class="price">R$ <?php echo esc_html(number_format($event_price, 2, ',', '.')); ?></span>
						<span class="note">entrada</span>
					<?php endif; ?>
				</div>

				<div class="action-btns">
					<?php if ($ticket_link) : ?>
						<a href="<?php echo esc_url($ticket_link); ?>" class="btn-buy" target="_blank" rel="noopener">
							<i class="i-ticket-v" aria-hidden="true"></i>
							Comprar Ingresso
						</a>
					<?php endif; ?>

					<button type="button" class="btn-action interest <?php echo $is_interested ? 'active' : ''; ?>" data-type="interest">
						<i class="<?php echo $is_interested ? 'i-heart-fill-v' : 'i-heart-v'; ?>" aria-hidden="true"></i>
						<?php echo $is_interested ? 'Interessado' : 'Tenho Interesse'; ?>
					</button>

					<button type="button" class="btn-action confirm <?php echo $is_confirmed ? 'active' : ''; ?>" data-type="confirm">
						<i class="i-check-v" aria-hidden="true"></i>
						<?php echo $is_confirmed ? 'Presença Confirmada' : 'Confirmar Presença'; ?>
					</button>
				</div>
			</div>

			<!-- Details Card -->
			<div class="card details-card">
				<h3>Detalhes</h3>
				<ul class="details">
					<li>
						<i class="i-calendar-v" aria-hidden="true"></i>
						<div>
							<strong>Data</strong>
							<span><?php echo $date_obj ? $date_obj->format('d/m/Y') : 'A definir'; ?></span>
						</div>
					</li>
					<li>
						<i class="i-time-v" aria-hidden="true"></i>
						<div>
							<strong>Horário</strong>
							<span>
								<?php echo esc_html($event_time_start); ?>
								<?php if ($event_time_end) : ?> até <?php echo esc_html($event_time_end); ?><?php endif; ?>
							</span>
						</div>
					</li>
					<?php if ($event_capacity) : ?>
						<li>
							<i class="i-group-v" aria-hidden="true"></i>
							<div>
								<strong>Capacidade</strong>
								<span><?php echo esc_html($event_capacity); ?> pessoas</span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ($age_restriction) : ?>
						<li>
							<i class="i-user-v" aria-hidden="true"></i>
							<div>
								<strong>Faixa Etária</strong>
								<span><?php echo esc_html($age_restriction); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<?php if ($dress_code) : ?>
						<li>
							<i class="i-shirt-v" aria-hidden="true"></i>
							<div>
								<strong>Dress Code</strong>
								<span><?php echo esc_html($dress_code); ?></span>
							</div>
						</li>
					<?php endif; ?>
					<li>
						<i class="i-lock-v" aria-hidden="true"></i>
						<div>
							<strong>Privacidade</strong>
							<span>
								<?php
								$privacy_labels = array(
									'public'  => 'Evento Público',
									'private' => 'Apenas Convidados',
									'members' => 'Apenas Membros',
								);
								echo esc_html($privacy_labels[$event_privacy] ?? 'Público');
								?>
							</span>
						</div>
					</li>
				</ul>
			</div>

			<!-- Organizer Card -->
			<div class="card organizer-card">
				<h3>Organizador</h3>
				<?php if ($producer) : ?>
					<a href="<?php echo esc_url(home_url('/membro/' . $producer->user_login)); ?>" class="org-link">
						<img src="<?php echo esc_url($producer_avatar); ?>" alt="<?php echo esc_attr($producer_name); ?>" class="org-avatar">
						<div class="org-info">
							<h4><?php echo esc_html($producer_name); ?></h4>
							<span>Produtor</span>
						</div>
						<i class="i-arrow-right-s-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($community) :
					$community_thumb = get_the_post_thumbnail_url($community->ID, 'thumbnail');
				?>
					<a href="<?php echo get_permalink($community->ID); ?>" class="org-link community">
						<img src="<?php echo esc_url($community_thumb); ?>" alt="<?php echo esc_attr($community->post_title); ?>" class="org-avatar">
						<div class="org-info">
							<h4><?php echo esc_html($community->post_title); ?></h4>
							<span>Comunidade</span>
						</div>
						<i class="i-arrow-right-s-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<button type="button" class="contact-btn">
					<i class="i-chat-1-v" aria-hidden="true"></i>
					Entrar em contato
				</button>
			</div>

			<!-- Attendees Preview -->
			<div class="card attendees-card">
				<h3>Quem vai</h3>
				<div class="avatars-row">
					<?php
					$confirmed_preview = array_slice($user_confirmed, 0, 6);
					foreach ($confirmed_preview as $user_id) :
						$avatar = get_avatar_url($user_id, array('size' => 40));
					?>
						<img src="<?php echo esc_url($avatar); ?>" alt="" class="avatar">
					<?php endforeach; ?>
					<?php if (count($user_confirmed) > 6) : ?>
						<span class="more">+<?php echo count($user_confirmed) - 6; ?></span>
					<?php endif; ?>
				</div>
				<?php if ($confirmed_count > 0) : ?>
					<a href="#attendees" class="view-link">Ver todos os <?php echo $confirmed_count; ?> confirmados</a>
				<?php else : ?>
					<p class="empty-msg">Seja o primeiro a confirmar presença!</p>
				<?php endif; ?>
			</div>

		</aside>
	</div>

	<!-- Related Events -->
	<?php
	$related_events = new WP_Query(array(
		'post_type'      => 'event_listing',
		'posts_per_page' => 4,
		'post__not_in'   => array($event_id),
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => '_event_date',
				'value'   => current_time('Y-m-d'),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'tax_query'      => ! empty($genres) ? array(
			array(
				'taxonomy' => 'event_genre',
				'field'    => 'name',
				'terms'    => $genres,
			),
		) : array(),
	));

	if ($related_events->have_posts()) :
	?>
		<section class="related-section">
			<div class="related-head">
				<h2>Eventos Relacionados</h2>
				<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="all-link">Ver todos</a>
			</div>
			<div class="related-list">
				<?php while ($related_events->have_posts()) : $related_events->the_post();
					$rel_date = get_post_meta(get_the_ID(), '_event_date', true);
					$rel_date_obj = DateTime::createFromFormat('Y-m-d', $rel_date);
					$rel_venue = get_post_meta(get_the_ID(), '_event_venue', true);
					$rel_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
				?>
					<a href="<?php the_permalink(); ?>" class="related-item">
						<div class="rel-image" style="background-image: url('<?php echo esc_url($rel_thumb); ?>');">
							<div class="rel-date">
								<span class="d"><?php echo $rel_date_obj ? $rel_date_obj->format('d') : '--'; ?></span>
								<span class="m"><?php echo $rel_date_obj ? strtoupper($rel_date_obj->format('M')) : '---'; ?></span>
							</div>
						</div>
						<div class="rel-info">
							<h4><?php the_title(); ?></h4>
							<p><i class="i-map-pin-v" aria-hidden="true"></i> <?php echo esc_html($rel_venue); ?></p>
						</div>
					</a>
				<?php endwhile; ?>
			</div>
		</section>
	<?php
		wp_reset_postdata();
	endif;
	?>

</article>

<style>
	/* Single Event Full Styles */
	.single-event-full {
		width: 100%;
		background: var(--ap-bg-page);
	}

	/* Hero Cover */
	.event-hero {
		height: 50vh;
		min-height: 400px;
		max-height: 500px;
		background-size: cover;
		background-position: center;
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: flex-end;
	}

	.hero-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.8) 100%);
	}

	.hero-content {
		position: relative;
		z-index: 1;
		padding: 2rem 1.5rem;
		color: #fff;
	}

	@media (min-width: 768px) {
		.hero-content {
			padding: 2.5rem 3rem;
		}
	}

	.event-nav {
		margin-bottom: 1.5rem;
	}

	.nav-back {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		color: rgba(255, 255, 255, 0.85);
		font-size: 0.85rem;
		transition: color 0.2s;
	}

	.nav-back:hover {
		color: #fff;
	}

	.hero-main-info {
		display: flex;
		align-items: flex-start;
		gap: 1.25rem;
	}

	.date-badge {
		background: #fff;
		border-radius: 0.75rem;
		padding: 0.75rem 1rem;
		text-align: center;
		color: #0f172a;
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
	}

	.date-badge .weekday {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: #f97316;
		margin-bottom: 0.15rem;
	}

	.date-badge .day {
		display: block;
		font-size: 1.75rem;
		font-weight: 800;
		line-height: 1;
	}

	.date-badge .month {
		display: block;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: #64748b;
	}

	.event-details {
		flex: 1;
	}

	.event-details .title {
		font-size: 1.75rem;
		font-weight: 900;
		margin: 0 0 0.65rem;
		line-height: 1.15;
	}

	@media (min-width: 768px) {
		.event-details .title {
			font-size: 2.5rem;
		}
	}

	.quick-meta {
		display: flex;
		flex-wrap: wrap;
		gap: 1rem;
		margin-bottom: 0.75rem;
	}

	.quick-meta .meta {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.9rem;
		opacity: 0.9;
	}

	.genre-tags {
		display: flex;
		gap: 0.5rem;
		flex-wrap: wrap;
	}

	.genre-tags .genre {
		font-size: 0.75rem;
		padding: 0.25rem 0.75rem;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 999px;
		backdrop-filter: blur(8px);
	}

	.hero-actions {
		position: absolute;
		top: 1rem;
		right: 1rem;
		display: flex;
		gap: 0.5rem;
	}

	.action-btn {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.2);
		backdrop-filter: blur(8px);
		border: none;
		color: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		transition: all 0.2s;
	}

	.action-btn:hover {
		background: rgba(255, 255, 255, 0.3);
		transform: scale(1.05);
	}

	.action-btn.active {
		background: #f97316;
	}

	/* Stats Bar */
	.stats-bar {
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		padding: 0.75rem 1.5rem;
	}

	.stats-wrap {
		max-width: 1200px;
		margin: 0 auto;
		display: flex;
		justify-content: center;
		gap: 2.5rem;
	}

	@media (min-width: 768px) {
		.stats-wrap {
			gap: 4rem;
		}
	}

	.stat {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.85rem;
	}

	.stat i {
		color: var(--ap-text-muted);
	}

	.stat .val {
		font-weight: 700;
		color: var(--ap-text-default);
	}

	.stat .label {
		color: var(--ap-text-muted);
	}

	/* Content Grid */
	.content-grid {
		max-width: 1200px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 1fr;
		gap: 2rem;
		padding: 2rem 1.5rem;
	}

	@media (min-width: 992px) {
		.content-grid {
			grid-template-columns: 1fr 360px;
		}
	}

	/* Main Column */
	.main-col {
		min-width: 0;
	}

	.section {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.5rem;
		margin-bottom: 1.5rem;
	}

	.section-head {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 1.1rem;
		font-weight: 700;
		margin: 0 0 1rem;
		padding-bottom: 0.75rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.section-head i {
		color: #f97316;
	}

	.section-body {
		font-size: 0.95rem;
		line-height: 1.7;
		color: var(--ap-text-default);
	}

	.section-body p {
		margin: 0 0 1rem;
	}

	.section-body p:last-child {
		margin: 0;
	}

	/* Lineup */
	.lineup-list {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
		gap: 1rem;
	}

	.dj-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		text-align: center;
		padding: 1.25rem 1rem;
		background: var(--ap-bg-surface);
		border-radius: 1rem;
		transition: all 0.2s;
	}

	.dj-item:hover {
		background: var(--ap-bg-page);
		transform: translateY(-2px);
	}

	.dj-photo {
		width: 80px;
		height: 80px;
		border-radius: 50%;
		background-size: cover;
		background-position: center;
		margin-bottom: 0.75rem;
		border: 3px solid #fff;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.dj-details h4 {
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 0.25rem;
	}

	.dj-details p {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin: 0;
	}

	/* Gallery */
	.gallery-mosaic {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		gap: 0.75rem;
	}

	.gallery-thumb {
		aspect-ratio: 1;
		border-radius: 0.75rem;
		overflow: hidden;
	}

	.gallery-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s;
	}

	.gallery-thumb:hover img {
		transform: scale(1.05);
	}

	/* Location */
	.venue-info {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		margin-bottom: 1rem;
		flex-wrap: wrap;
	}

	.venue-thumb {
		width: 60px;
		height: 60px;
		border-radius: 0.5rem;
		background-size: cover;
		background-position: center;
		flex-shrink: 0;
	}

	.venue-text {
		flex: 1;
		min-width: 150px;
	}

	.venue-text h4 {
		font-size: 0.95rem;
		font-weight: 700;
		margin: 0 0 0.25rem;
	}

	.venue-text h4 a:hover {
		color: #f97316;
	}

	.venue-text p {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin: 0;
	}

	.directions-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.5rem 1rem;
		background: #1e293b;
		color: #fff;
		border-radius: 999px;
		font-size: 0.8rem;
		font-weight: 600;
		transition: background 0.2s;
	}

	.directions-btn:hover {
		background: #0f172a;
	}

	.map-wrap {
		height: 200px;
		border-radius: 0.75rem;
		overflow: hidden;
		background: var(--ap-bg-surface);
	}

	#event-map-full {
		width: 100%;
		height: 100%;
	}

	/* Sidebar */
	.sidebar-col {
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
	}

	.card {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
	}

	.card h3 {
		font-size: 1rem;
		font-weight: 700;
		margin: 0 0 1rem;
	}

	/* Action Card */
	.price-display {
		text-align: center;
		padding-bottom: 1rem;
		margin-bottom: 1rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.price-display .price {
		font-size: 1.75rem;
		font-weight: 800;
		color: var(--ap-text-default);
	}

	.price-display .price.free {
		color: #10b981;
	}

	.price-display .note {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		margin-left: 0.25rem;
	}

	.action-btns {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.btn-buy {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.85rem 1.25rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border-radius: 999px;
		font-size: 0.9rem;
		font-weight: 700;
		transition: transform 0.2s, box-shadow 0.2s;
		border: none;
	}

	.btn-buy:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	.btn-action {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.75rem 1.25rem;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-action.interest:hover {
		border-color: #ef4444;
		color: #ef4444;
	}

	.btn-action.interest.active {
		background: #fef2f2;
		border-color: #ef4444;
		color: #ef4444;
	}

	.btn-action.confirm:hover {
		border-color: #10b981;
		color: #10b981;
	}

	.btn-action.confirm.active {
		background: #ecfdf5;
		border-color: #10b981;
		color: #10b981;
	}

	/* Details List */
	.details {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.details li {
		display: flex;
		gap: 0.75rem;
		padding: 0.75rem 0;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.details li:last-child {
		border: none;
		padding-bottom: 0;
	}

	.details li:first-child {
		padding-top: 0;
	}

	.details li>i {
		width: 20px;
		text-align: center;
		color: #f97316;
		margin-top: 0.15rem;
	}

	.details li>div strong {
		display: block;
		font-size: 0.8rem;
		font-weight: 600;
	}

	.details li>div span {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
	}

	/* Organizer Card */
	.org-link {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem;
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		margin-bottom: 0.75rem;
		transition: background 0.2s;
	}

	.org-link:hover {
		background: var(--ap-bg-page);
	}

	.org-avatar {
		width: 48px;
		height: 48px;
		border-radius: 50%;
		object-fit: cover;
	}

	.org-link.community .org-avatar {
		border-radius: 0.5rem;
	}

	.org-info {
		flex: 1;
	}

	.org-info h4 {
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 0.15rem;
	}

	.org-info span {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.org-link>i {
		color: var(--ap-text-muted);
	}

	.contact-btn {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.65rem 1rem;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.contact-btn:hover {
		border-color: #1e293b;
	}

	/* Attendees */
	.avatars-row {
		display: flex;
		flex-wrap: wrap;
		margin-bottom: 0.75rem;
	}

	.avatars-row .avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 2px solid #fff;
		margin-left: -0.5rem;
	}

	.avatars-row .avatar:first-child {
		margin-left: 0;
	}

	.avatars-row .more {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		border: 2px solid #fff;
		margin-left: -0.5rem;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.7rem;
		font-weight: 600;
	}

	.view-link {
		font-size: 0.8rem;
		color: #f97316;
	}

	.empty-msg {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		margin: 0;
	}

	/* Related Events */
	.related-section {
		max-width: 1200px;
		margin: 0 auto;
		padding: 0 1.5rem 3rem;
	}

	.related-head {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1.25rem;
	}

	.related-head h2 {
		font-size: 1.25rem;
		font-weight: 800;
		margin: 0;
	}

	.all-link {
		font-size: 0.85rem;
		color: #f97316;
		font-weight: 600;
	}

	.related-list {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
		gap: 1.25rem;
	}

	.related-item {
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
		transition: all 0.2s;
	}

	.related-item:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
	}

	.rel-image {
		height: 140px;
		background-size: cover;
		background-position: center;
		position: relative;
	}

	.rel-date {
		position: absolute;
		top: 0.5rem;
		left: 0.5rem;
		background: #fff;
		border-radius: 0.5rem;
		padding: 0.35rem 0.5rem;
		text-align: center;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	}

	.rel-date .d {
		display: block;
		font-size: 1rem;
		font-weight: 800;
		line-height: 1;
	}

	.rel-date .m {
		display: block;
		font-size: 0.55rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
	}

	.rel-info {
		padding: 1rem;
	}

	.rel-info h4 {
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 0.35rem;
		line-height: 1.3;
	}

	.rel-info p {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.25rem;
	}

	/* Dark Mode */
	body.dark-mode .stats-bar,
	body.dark-mode .section,
	body.dark-mode .card,
	body.dark-mode .related-item {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .date-badge,
	body.dark-mode .rel-date {
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
	}

	body.dark-mode .btn-action,
	body.dark-mode .contact-btn {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.single-event-full');
		if (!page) return;

		const eventId = page.dataset.eventId;

		// Interest/Confirm buttons
		page.querySelectorAll('[data-type]').forEach(btn => {
			btn.addEventListener('click', function() {
				const type = this.dataset.type;
				const isActive = this.classList.toggle('active');
				const icon = this.querySelector('i');

				if (type === 'interest') {
					icon.className = isActive ? 'i-heart-fill-v' : 'i-heart-v';
					this.textContent = isActive ? 'Interessado' : 'Tenho Interesse';
					this.prepend(icon);

					const counter = page.querySelector('.interested-val');
					if (counter) {
						let count = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
						counter.textContent = (isActive ? count + 1 : Math.max(0, count - 1)).toLocaleString('pt-BR');
					}
				} else if (type === 'confirm') {
					this.textContent = isActive ? 'Presença Confirmada' : 'Confirmar Presença';
					this.prepend(icon);

					const counter = page.querySelector('.confirmed-val');
					if (counter) {
						let count = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
						counter.textContent = (isActive ? count + 1 : Math.max(0, count - 1)).toLocaleString('pt-BR');
					}
				}

				// AJAX call
				if (typeof apolloAjax !== 'undefined') {
					const endpoint = type === 'interest' ?
						(isActive ? 'apollo_add_interest' : 'apollo_remove_interest') :
						(isActive ? 'apollo_confirm_attendance' : 'apollo_cancel_attendance');

					fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: endpoint,
							event_id: eventId,
							nonce: apolloAjax.nonce
						})
					});
				}
			});
		});

		// Share trigger
		const shareBtn = page.querySelector('.share-trigger');
		if (shareBtn) {
			shareBtn.addEventListener('click', () => {
				if (navigator.share) {
					navigator.share({
						title: document.querySelector('.title')?.textContent || 'Evento Apollo',
						url: window.location.href
					});
				} else {
					navigator.clipboard.writeText(window.location.href).then(() => {
						alert('Link copiado!');
					});
				}
			});
		}

		// Bookmark trigger
		const bookmarkBtn = page.querySelector('.bookmark-trigger');
		if (bookmarkBtn) {
			bookmarkBtn.addEventListener('click', function() {
				this.classList.toggle('active');
				const interestBtn = page.querySelector('[data-type="interest"]');
				if (interestBtn && !interestBtn.classList.contains('active') && this.classList.contains('active')) {
					interestBtn.click();
				}
			});
		}
	})();
</script>
