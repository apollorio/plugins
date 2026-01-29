<?php

declare(strict_types=1);
/**
 * Apollo Single DJ Page Template
 *
 * DJ profile page with bio, social links, music player, upcoming events
 * Based on: single-dj-page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int|WP_Post $dj DJ post object or ID
 * }
 */

defined('ABSPATH') || exit;

// Get DJ data.
$dj = $args['dj'] ?? get_post();
if (is_numeric($dj)) {
	$dj = get_post($dj);
}

if (! $dj || 'event_dj' !== $dj->post_type) {
	echo '<div class="ap-error">DJ não encontrado.</div>';
	return;
}

// DJ Meta.
$dj_name        = $dj->post_title;
$dj_bio         = $dj->post_content;
$dj_photo       = get_the_post_thumbnail_url($dj->ID, 'large') ?: 'https://assets.apollo.rio.br/i/placeholder-dj.jpg';
$dj_genres      = wp_get_post_terms($dj->ID, 'event_genre', array('fields' => 'names'));
$dj_city        = get_post_meta($dj->ID, '_dj_city', true) ?: 'Rio de Janeiro, RJ';
$dj_tagline     = get_post_meta($dj->ID, '_dj_tagline', true);

// Social links.
$social_instagram = get_post_meta($dj->ID, '_dj_instagram', true);
$social_soundcloud = get_post_meta($dj->ID, '_dj_soundcloud', true);
$social_spotify   = get_post_meta($dj->ID, '_dj_spotify', true);
$social_ra        = get_post_meta($dj->ID, '_dj_resident_advisor', true);

// SoundCloud embed.
$soundcloud_track = get_post_meta($dj->ID, '_dj_soundcloud_track', true);
$soundcloud_playlist = get_post_meta($dj->ID, '_dj_soundcloud_playlist', true);

// Stats.
$followers = (int) get_post_meta($dj->ID, '_dj_followers', true);
$events_played = (int) get_post_meta($dj->ID, '_dj_events_count', true);
$tracks_count = (int) get_post_meta($dj->ID, '_dj_tracks_count', true);

// Related events query.
$upcoming_events = new WP_Query(array(
	'post_type'      => 'event_listing',
	'posts_per_page' => 4,
	'meta_query'     => array(
		array(
			'key'     => '_event_date',
			'value'   => current_time('Y-m-d'),
			'compare' => '>=',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_event_djs',
			'value'   => $dj->ID,
			'compare' => 'LIKE',
		),
	),
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_key'       => '_event_date',
));

// Past events for gallery.
$past_events = new WP_Query(array(
	'post_type'      => 'event_listing',
	'posts_per_page' => 6,
	'meta_query'     => array(
		array(
			'key'     => '_event_date',
			'value'   => current_time('Y-m-d'),
			'compare' => '<',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_event_djs',
			'value'   => $dj->ID,
			'compare' => 'LIKE',
		),
	),
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'meta_key'       => '_event_date',
));

?>
<article class="dj-profile-page">

	<!-- Hero Section -->
	<header class="dj-hero">
		<div class="dj-hero-bg" style="background-image: url('<?php echo esc_url($dj_photo); ?>');"></div>
		<div class="dj-hero-overlay"></div>

		<div class="dj-hero-content">
			<div class="dj-hero-avatar">
				<img src="<?php echo esc_url($dj_photo); ?>" alt="<?php echo esc_attr($dj_name); ?>">
			</div>

			<div class="dj-hero-info">
				<div class="dj-meta-tags">
					<span class="dj-type-badge">
						<i class="i-disc-v" aria-hidden="true"></i>
						DJ / Producer
					</span>
					<span class="dj-location">
						<i class="i-map-pin-v" aria-hidden="true"></i>
						<?php echo esc_html($dj_city); ?>
					</span>
				</div>

				<h1 class="dj-name"><?php echo esc_html($dj_name); ?></h1>

				<?php if ($dj_tagline) : ?>
					<p class="dj-tagline"><?php echo esc_html($dj_tagline); ?></p>
				<?php endif; ?>

				<?php if (! empty($dj_genres)) : ?>
					<div class="dj-genres">
						<?php foreach ($dj_genres as $genre) : ?>
							<span class="genre-tag"><?php echo esc_html($genre); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="dj-hero-actions">
					<button class="btn-follow" type="button" data-dj-id="<?php echo esc_attr($dj->ID); ?>">
						<i class="i-add-v" aria-hidden="true"></i>
						Seguir
					</button>
					<button class="btn-share" type="button">
						<i class="i-share-v" aria-hidden="true"></i>
						Compartilhar
					</button>
				</div>
			</div>
		</div>
	</header>

	<!-- Stats Bar -->
	<section class="dj-stats-bar">
		<div class="dj-stat">
			<span class="stat-value"><?php echo number_format($followers, 0, ',', '.'); ?></span>
			<span class="stat-label">Seguidores</span>
		</div>
		<div class="dj-stat">
			<span class="stat-value"><?php echo esc_html($events_played); ?></span>
			<span class="stat-label">Eventos</span>
		</div>
		<div class="dj-stat">
			<span class="stat-value"><?php echo esc_html($tracks_count); ?></span>
			<span class="stat-label">Tracks</span>
		</div>
	</section>

	<!-- Main Content -->
	<div class="dj-main-layout">

		<!-- Left Column -->
		<div class="dj-main-content">

			<!-- Bio Section -->
			<section class="dj-section">
				<div class="section-header">
					<h2>
						<i class="i-user-3-v" aria-hidden="true"></i>
						Sobre
					</h2>
				</div>
				<div class="dj-bio">
					<?php echo wp_kses_post(wpautop($dj_bio)); ?>
				</div>
			</section>

			<!-- Music Player Section -->
			<?php if ($soundcloud_track || $soundcloud_playlist) : ?>
				<section class="dj-section">
					<div class="section-header">
						<h2>
							<i class="i-music-2-v" aria-hidden="true"></i>
							Música
						</h2>
						<?php if ($social_soundcloud) : ?>
							<a href="<?php echo esc_url($social_soundcloud); ?>" target="_blank" class="section-link">
								Ver no SoundCloud <i class="i-arrow-right-up-v" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
					</div>

					<div class="dj-music-player">
						<?php if ($soundcloud_playlist) : ?>
							<iframe
								class="soundcloud-embed"
								scrolling="no"
								frameborder="no"
								allow="autoplay"
								src="https://w.soundcloud.com/player/?url=<?php echo urlencode($soundcloud_playlist); ?>&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false&visual=true"></iframe>
						<?php elseif ($soundcloud_track) : ?>
							<iframe
								class="soundcloud-embed"
								scrolling="no"
								frameborder="no"
								allow="autoplay"
								src="https://w.soundcloud.com/player/?url=<?php echo urlencode($soundcloud_track); ?>&color=%23ff5500&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false"></iframe>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Upcoming Events -->
			<?php if ($upcoming_events->have_posts()) : ?>
				<section class="dj-section">
					<div class="section-header">
						<h2>
							<i class="i-calendar-event-v" aria-hidden="true"></i>
							Próximos Eventos
						</h2>
						<a href="<?php echo home_url('/events/?dj=' . $dj->ID); ?>" class="section-link">
							Ver todos <i class="i-arrow-right-up-v" aria-hidden="true"></i>
						</a>
					</div>

					<div class="dj-events-grid">
						<?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post();
							$event_date = get_post_meta(get_the_ID(), '_event_date', true);
							$event_time = get_post_meta(get_the_ID(), '_event_time', true);
							$event_venue = get_post_meta(get_the_ID(), '_event_venue', true);
							$event_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
							$date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
						?>
							<a href="<?php the_permalink(); ?>" class="dj-event-card">
								<div class="dj-event-thumb" style="background-image: url('<?php echo esc_url($event_thumb); ?>');"></div>
								<div class="dj-event-content">
									<div class="dj-event-date-badge">
										<span class="day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
										<span class="month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
									</div>
									<div class="dj-event-info">
										<h3><?php the_title(); ?></h3>
										<span class="dj-event-meta">
											<?php echo esc_html($event_time); ?> • <?php echo esc_html($event_venue); ?>
										</span>
									</div>
								</div>
							</a>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Past Events Gallery -->
			<?php if ($past_events->have_posts()) : ?>
				<section class="dj-section">
					<div class="section-header">
						<h2>
							<i class="i-gallery-v" aria-hidden="true"></i>
							Eventos Anteriores
						</h2>
					</div>

					<div class="dj-past-gallery">
						<?php while ($past_events->have_posts()) : $past_events->the_post();
							$event_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
						?>
							<a href="<?php the_permalink(); ?>" class="dj-gallery-item">
								<img src="<?php echo esc_url($event_thumb); ?>" alt="<?php the_title_attribute(); ?>">
								<div class="dj-gallery-overlay">
									<span><?php the_title(); ?></span>
								</div>
							</a>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<!-- Sidebar -->
		<aside class="dj-sidebar">

			<!-- Social Links -->
			<div class="dj-sidebar-block">
				<h3 class="sidebar-title">Redes Sociais</h3>
				<div class="dj-social-links">
					<?php if ($social_instagram) : ?>
						<a href="<?php echo esc_url($social_instagram); ?>" target="_blank" class="social-link social-instagram">
							<i class="ri-instagram-line"></i>
							Instagram
						</a>
					<?php endif; ?>

					<?php if ($social_soundcloud) : ?>
						<a href="<?php echo esc_url($social_soundcloud); ?>" target="_blank" class="social-link social-soundcloud">
							<i class="ri-soundcloud-line"></i>
							SoundCloud
						</a>
					<?php endif; ?>

					<?php if ($social_spotify) : ?>
						<a href="<?php echo esc_url($social_spotify); ?>" target="_blank" class="social-link social-spotify">
							<i class="ri-spotify-line"></i>
							Spotify
						</a>
					<?php endif; ?>

					<?php if ($social_ra) : ?>
						<a href="<?php echo esc_url($social_ra); ?>" target="_blank" class="social-link social-ra">
							<i class="i-globe-v" aria-hidden="true"></i>
							Resident Advisor
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Booking -->
			<div class="dj-sidebar-block dj-booking-block">
				<h3 class="sidebar-title">Booking</h3>
				<p class="booking-desc">Quer esse artista no seu evento?</p>
				<button class="btn-booking" type="button" data-dj-id="<?php echo esc_attr($dj->ID); ?>">
					<i class="i-mail-send-v" aria-hidden="true"></i>
					Solicitar Booking
				</button>
			</div>

			<!-- Similar DJs -->
			<?php
			$similar_djs = new WP_Query(array(
				'post_type'      => 'event_dj',
				'posts_per_page' => 4,
				'post__not_in'   => array($dj->ID),
				'tax_query'      => array(
					array(
						'taxonomy' => 'event_genre',
						'field'    => 'name',
						'terms'    => $dj_genres,
					),
				),
			));

			if ($similar_djs->have_posts()) : ?>
				<div class="dj-sidebar-block">
					<h3 class="sidebar-title">DJs Similares</h3>
					<div class="dj-similar-list">
						<?php while ($similar_djs->have_posts()) : $similar_djs->the_post();
							$similar_photo = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
							$similar_genres = wp_get_post_terms(get_the_ID(), 'event_genre', array('fields' => 'names'));
						?>
							<a href="<?php the_permalink(); ?>" class="dj-similar-item">
								<div class="dj-similar-avatar">
									<img src="<?php echo esc_url($similar_photo); ?>" alt="<?php the_title_attribute(); ?>">
								</div>
								<div class="dj-similar-info">
									<strong><?php the_title(); ?></strong>
									<span><?php echo implode(', ', array_slice($similar_genres, 0, 2)); ?></span>
								</div>
							</a>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				</div>
			<?php endif; ?>

		</aside>

	</div>

</article>

<style>
	/* DJ Profile Styles */
	.dj-profile-page {
		width: 100%;
		background: var(--ap-bg-page);
	}

	/* Hero */
	.dj-hero {
		position: relative;
		min-height: 400px;
		display: flex;
		align-items: flex-end;
		padding: 2rem 1.5rem;
		overflow: hidden;
	}

	@media (min-width: 768px) {
		.dj-hero {
			min-height: 480px;
			padding: 3rem 2rem;
		}
	}

	.dj-hero-bg {
		position: absolute;
		inset: 0;
		background-size: cover;
		background-position: center;
		filter: blur(20px);
		transform: scale(1.1);
	}

	.dj-hero-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.4) 50%, rgba(0, 0, 0, 0.2) 100%);
	}

	.dj-hero-content {
		position: relative;
		z-index: 1;
		max-width: 1200px;
		margin: 0 auto;
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		text-align: center;
		gap: 1.5rem;
	}

	@media (min-width: 768px) {
		.dj-hero-content {
			flex-direction: row;
			text-align: left;
			gap: 2rem;
		}
	}

	.dj-hero-avatar {
		width: 140px;
		height: 140px;
		border-radius: 50%;
		overflow: hidden;
		border: 4px solid rgba(255, 255, 255, 0.2);
		box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
		flex-shrink: 0;
	}

	@media (min-width: 768px) {
		.dj-hero-avatar {
			width: 180px;
			height: 180px;
		}
	}

	.dj-hero-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.dj-hero-info {
		color: #fff;
	}

	.dj-meta-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem;
		justify-content: center;
		margin-bottom: 0.75rem;
	}

	@media (min-width: 768px) {
		.dj-meta-tags {
			justify-content: flex-start;
		}
	}

	.dj-type-badge {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		background: rgba(249, 115, 22, 0.2);
		color: #fb923c;
		padding: 0.3rem 0.75rem;
		border-radius: 999px;
		border: 1px solid rgba(249, 115, 22, 0.3);
	}

	.dj-location {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.75rem;
		opacity: 0.8;
	}

	.dj-name {
		font-size: 2.5rem;
		font-weight: 900;
		margin: 0;
		letter-spacing: -0.03em;
		line-height: 1.1;
	}

	@media (min-width: 768px) {
		.dj-name {
			font-size: 3.5rem;
		}
	}

	.dj-tagline {
		font-size: 1rem;
		opacity: 0.85;
		margin: 0.5rem 0 0;
		max-width: 500px;
	}

	.dj-genres {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		margin-top: 1rem;
		justify-content: center;
	}

	@media (min-width: 768px) {
		.dj-genres {
			justify-content: flex-start;
		}
	}

	.genre-tag {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		padding: 0.25rem 0.65rem;
		border-radius: 4px;
		background: rgba(255, 255, 255, 0.15);
		border: 1px solid rgba(255, 255, 255, 0.2);
	}

	.dj-hero-actions {
		display: flex;
		gap: 0.75rem;
		margin-top: 1.5rem;
		justify-content: center;
	}

	@media (min-width: 768px) {
		.dj-hero-actions {
			justify-content: flex-start;
		}
	}

	.btn-follow,
	.btn-share {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.65rem 1.25rem;
		border-radius: 999px;
		font-size: 0.8rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-follow {
		background: #fff;
		color: #0f172a;
		border: none;
	}

	.btn-follow:hover {
		transform: translateY(-1px);
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
	}

	.btn-follow.following {
		background: transparent;
		color: #fff;
		border: 1px solid rgba(255, 255, 255, 0.3);
	}

	.btn-share {
		background: transparent;
		color: #fff;
		border: 1px solid rgba(255, 255, 255, 0.3);
	}

	.btn-share:hover {
		background: rgba(255, 255, 255, 0.1);
	}

	/* Stats Bar */
	.dj-stats-bar {
		display: flex;
		justify-content: center;
		gap: 3rem;
		padding: 1.5rem;
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.dj-stat {
		text-align: center;
	}

	.dj-stat .stat-value {
		display: block;
		font-size: 1.5rem;
		font-weight: 800;
		line-height: 1;
	}

	.dj-stat .stat-label {
		display: block;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: var(--ap-text-muted);
		margin-top: 0.25rem;
	}

	/* Main Layout */
	.dj-main-layout {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem 1.5rem;
		display: grid;
		grid-template-columns: 1fr;
		gap: 2rem;
	}

	@media (min-width: 1024px) {
		.dj-main-layout {
			grid-template-columns: 1fr 320px;
		}
	}

	/* Sections */
	.dj-section {
		margin-bottom: 2.5rem;
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1.25rem;
		padding-bottom: 0.75rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.section-header h2 {
		font-size: 1rem;
		font-weight: 800;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.section-header h2 i {
		color: var(--ap-text-muted);
	}

	.section-link {
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.12em;
		color: #f97316;
		display: flex;
		align-items: center;
		gap: 0.25rem;
	}

	/* Bio */
	.dj-bio {
		font-size: 0.95rem;
		line-height: 1.7;
		color: var(--ap-text-default);
	}

	.dj-bio p {
		margin: 0 0 1rem;
	}

	/* Music Player */
	.dj-music-player {
		border-radius: 1rem;
		overflow: hidden;
		background: var(--ap-bg-surface);
	}

	.soundcloud-embed {
		width: 100%;
		height: 300px;
		border: none;
	}

	/* Events Grid */
	.dj-events-grid {
		display: grid;
		grid-template-columns: 1fr;
		gap: 1rem;
	}

	@media (min-width: 640px) {
		.dj-events-grid {
			grid-template-columns: 1fr 1fr;
		}
	}

	.dj-event-card {
		display: block;
		border-radius: 1rem;
		overflow: hidden;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.dj-event-card:hover {
		transform: translateY(-2px);
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
	}

	.dj-event-thumb {
		height: 120px;
		background-size: cover;
		background-position: center;
	}

	.dj-event-content {
		padding: 1rem;
		display: flex;
		gap: 0.75rem;
		align-items: flex-start;
	}

	.dj-event-date-badge {
		min-width: 50px;
		text-align: center;
		background: #1e293b;
		color: #fff;
		padding: 0.5rem;
		border-radius: 0.5rem;
		flex-shrink: 0;
	}

	.dj-event-date-badge .day {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.dj-event-date-badge .month {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		opacity: 0.8;
	}

	.dj-event-info h3 {
		font-size: 0.95rem;
		font-weight: 700;
		margin: 0 0 0.25rem;
		line-height: 1.3;
	}

	.dj-event-meta {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Past Gallery */
	.dj-past-gallery {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 0.75rem;
	}

	@media (min-width: 640px) {
		.dj-past-gallery {
			grid-template-columns: repeat(3, 1fr);
		}
	}

	.dj-gallery-item {
		position: relative;
		aspect-ratio: 1;
		border-radius: 0.75rem;
		overflow: hidden;
	}

	.dj-gallery-item img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s;
	}

	.dj-gallery-item:hover img {
		transform: scale(1.05);
	}

	.dj-gallery-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 50%);
		display: flex;
		align-items: flex-end;
		padding: 0.75rem;
		opacity: 0;
		transition: opacity 0.3s;
	}

	.dj-gallery-item:hover .dj-gallery-overlay {
		opacity: 1;
	}

	.dj-gallery-overlay span {
		font-size: 0.75rem;
		color: #fff;
		font-weight: 600;
	}

	/* Sidebar */
	.dj-sidebar-block {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.25rem;
		margin-bottom: 1.25rem;
	}

	.sidebar-title {
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		margin: 0 0 1rem;
		font-weight: 800;
	}

	/* Social Links */
	.dj-social-links {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.social-link {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.65rem 0.85rem;
		border-radius: 0.75rem;
		font-size: 0.85rem;
		font-weight: 500;
		transition: all 0.2s;
		border: 1px solid var(--ap-border-default);
	}

	.social-link:hover {
		transform: translateX(4px);
	}

	.social-link i {
		font-size: 1.25rem;
	}

	.social-instagram:hover {
		background: linear-gradient(135deg, #f58529, #dd2a7b, #8134af);
		color: #fff;
		border-color: transparent;
	}

	.social-soundcloud:hover {
		background: #ff5500;
		color: #fff;
		border-color: transparent;
	}

	.social-spotify:hover {
		background: #1db954;
		color: #fff;
		border-color: transparent;
	}

	.social-ra:hover {
		background: #0f172a;
		color: #fff;
		border-color: transparent;
	}

	/* Booking */
	.dj-booking-block {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		color: #fff;
		border: none;
	}

	.dj-booking-block .sidebar-title {
		color: #fff;
	}

	.booking-desc {
		font-size: 0.85rem;
		opacity: 0.85;
		margin: 0 0 1rem;
	}

	.btn-booking {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.75rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 0.75rem;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: transform 0.2s;
	}

	.btn-booking:hover {
		transform: translateY(-1px);
	}

	/* Similar DJs */
	.dj-similar-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.dj-similar-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.5rem;
		border-radius: 0.75rem;
		transition: background 0.2s;
	}

	.dj-similar-item:hover {
		background: var(--ap-bg-surface);
	}

	.dj-similar-avatar {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		overflow: hidden;
		flex-shrink: 0;
	}

	.dj-similar-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.dj-similar-info strong {
		display: block;
		font-size: 0.85rem;
	}

	.dj-similar-info span {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	/* Dark Mode */
	body.dark-mode .dj-stats-bar,
	body.dark-mode .dj-event-card,
	body.dark-mode .dj-sidebar-block:not(.dj-booking-block) {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .dj-music-player {
		background: var(--ap-bg-card);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.dj-profile-page');
		if (!page) return;

		// Follow button
		const followBtn = page.querySelector('.btn-follow');
		if (followBtn) {
			followBtn.addEventListener('click', function() {
				const djId = this.dataset.djId;
				const isFollowing = this.classList.contains('following');

				// Toggle state
				this.classList.toggle('following');
				if (isFollowing) {
					this.innerHTML = '<i class="i-add-v" aria-hidden="true"></i> Seguir';
				} else {
					this.innerHTML = '<i class="i-check-v" aria-hidden="true"></i> Seguindo';
				}

				// AJAX call
				if (typeof apolloAjax !== 'undefined') {
					fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: isFollowing ? 'apollo_unfollow_dj' : 'apollo_follow_dj',
							dj_id: djId,
							nonce: apolloAjax.nonce
						})
					});
				}
			});
		}

		// Share button
		const shareBtn = page.querySelector('.btn-share');
		if (shareBtn) {
			shareBtn.addEventListener('click', function() {
				if (navigator.share) {
					navigator.share({
						title: document.title,
						url: window.location.href
					});
				} else {
					navigator.clipboard.writeText(window.location.href);
					// Show toast
					const toast = document.createElement('div');
					toast.className = 'ap-toast';
					toast.textContent = 'Link copiado!';
					document.body.appendChild(toast);
					setTimeout(() => toast.remove(), 2000);
				}
			});
		}

		// Booking button
		const bookingBtn = page.querySelector('.btn-booking');
		if (bookingBtn) {
			bookingBtn.addEventListener('click', function() {
				const djId = this.dataset.djId;
				// Open booking modal or redirect
				window.location.href = `?booking=${djId}`;
			});
		}
	})();
</script>
