<?php

declare(strict_types=1);
/**
 * Apollo Event Card Component
 *
 * Reusable card component for events listing and grids
 * Based on: event-card.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param WP_Post|int $event  Event post object or ID
 * @param array       $args   Optional arguments:
 *                            - size: 'small' | 'medium' | 'large' (default: 'medium')
 *                            - show_meta: bool (default: true)
 *                            - show_actions: bool (default: true)
 *                            - lazy: bool (default: true)
 */

defined('ABSPATH') || exit;

// Get event data.
$event = $args['event'] ?? get_post();
if (is_numeric($event)) {
	$event = get_post($event);
}

if (! $event || 'event_listing' !== $event->post_type) {
	return;
}

// Card settings.
$size         = $args['size'] ?? 'medium';
$show_meta    = $args['show_meta'] ?? true;
$show_actions = $args['show_actions'] ?? true;
$lazy         = $args['lazy'] ?? true;

// Event data.
$event_id    = $event->ID;
$title       = get_the_title($event);
$permalink   = get_permalink($event);
$excerpt     = wp_trim_words(get_the_excerpt($event), 15, '...');
$thumbnail   = get_the_post_thumbnail_url($event, 'medium_large') ?: APOLLO_CDN_BASE . '/img/placeholder-event.webp';

// Meta fields (ACF or meta).
$event_date  = get_post_meta($event_id, '_event_date', true);
$event_time  = get_post_meta($event_id, '_event_time', true);
$event_venue = get_post_meta($event_id, '_event_venue', true);
$event_city  = get_post_meta($event_id, '_event_city', true) ?: 'Rio de Janeiro';

// Format date.
$formatted_date = '';
if ($event_date) {
	$timestamp      = strtotime($event_date);
	$formatted_date = wp_date('d M', $timestamp);
	$day_name       = wp_date('D', $timestamp);
}

// Taxonomies.
$genres = get_the_terms($event_id, 'event_genre');
$genre_name = $genres && ! is_wp_error($genres) ? $genres[0]->name : '';

// RSVP count.
$rsvp_count = (int) get_post_meta($event_id, '_rsvp_count', true);

// Size classes.
$size_classes = array(
	'small'  => 'ap-event-card--small',
	'medium' => 'ap-event-card--medium',
	'large'  => 'ap-event-card--large',
);
$size_class = $size_classes[$size] ?? $size_classes['medium'];

?>
<article class="ap-event-card <?php echo esc_attr($size_class); ?>" data-event-id="<?php echo esc_attr($event_id); ?>">

	<!-- Card Image -->
	<a href="<?php echo esc_url($permalink); ?>" class="ap-event-card__image">
		<img
			src="<?php echo $lazy ? esc_url(APOLLO_CDN_BASE . '/img/placeholder-loader.svg') : esc_url($thumbnail); ?>"
			<?php if ($lazy) : ?>
			data-src="<?php echo esc_url($thumbnail); ?>"
			loading="lazy"
			<?php endif; ?>
			alt="<?php echo esc_attr($title); ?>"
			class="ap-event-card__img">

		<?php if ($formatted_date) : ?>
			<div class="ap-event-card__date-badge">
				<span class="ap-event-card__date-day"><?php echo esc_html(wp_date('d', $timestamp)); ?></span>
				<span class="ap-event-card__date-month"><?php echo esc_html(wp_date('M', $timestamp)); ?></span>
			</div>
		<?php endif; ?>

		<?php if ($genre_name) : ?>
			<span class="ap-event-card__genre-tag"><?php echo esc_html($genre_name); ?></span>
		<?php endif; ?>
	</a>

	<!-- Card Body -->
	<div class="ap-event-card__body">
		<h3 class="ap-event-card__title">
			<a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
		</h3>

		<?php if ($show_meta) : ?>
			<div class="ap-event-card__meta">
				<?php if ($event_venue) : ?>
					<span class="ap-event-card__venue">
						<i class="i-map-pin-2-v" aria-hidden="true"></i>
						<?php echo esc_html($event_venue); ?>
					</span>
				<?php endif; ?>

				<?php if ($event_time) : ?>
					<span class="ap-event-card__time">
						<i class="i-time-v" aria-hidden="true"></i>
						<?php echo esc_html($event_time); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ($excerpt && 'large' === $size) : ?>
			<p class="ap-event-card__excerpt"><?php echo esc_html($excerpt); ?></p>
		<?php endif; ?>
	</div>

	<!-- Card Footer -->
	<?php if ($show_actions) : ?>
		<div class="ap-event-card__footer">
			<div class="ap-event-card__rsvp">
				<?php if ($rsvp_count > 0) : ?>
					<div class="ap-event-card__avatars">
						<?php for ($i = 0; $i < min(3, $rsvp_count); $i++) : ?>
							<span class="ap-event-card__avatar"></span>
						<?php endfor; ?>
						<?php if ($rsvp_count > 3) : ?>
							<span class="ap-event-card__avatar-count">+<?php echo esc_html($rsvp_count - 3); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<span class="ap-event-card__interested"><?php echo esc_html($rsvp_count); ?> interessados</span>
			</div>

			<button
				type="button"
				class="ap-event-card__action ap-btn-interest"
				data-action="toggle-interest"
				data-event-id="<?php echo esc_attr($event_id); ?>"
				aria-label="Marcar interesse">
				<i class="i-heart-v" aria-hidden="true"></i>
			</button>
		</div>
	<?php endif; ?>

</article>

<style>
	/* Event Card Component Styles */
	.ap-event-card {
		--card-radius: var(--ap-radius-lg, 16px);
		--card-bg: var(--ap-bg-card, #fff);
		--card-border: var(--ap-border-default, #e2e8f0);
		--card-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);

		position: relative;
		display: flex;
		flex-direction: column;
		background: var(--card-bg);
		border: 1px solid var(--card-border);
		border-radius: var(--card-radius);
		overflow: hidden;
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.ap-event-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
	}

	/* Image */
	.ap-event-card__image {
		position: relative;
		aspect-ratio: 16 / 9;
		overflow: hidden;
	}

	.ap-event-card__img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s ease;
	}

	.ap-event-card:hover .ap-event-card__img {
		transform: scale(1.05);
	}

	/* Date Badge */
	.ap-event-card__date-badge {
		position: absolute;
		top: 12px;
		left: 12px;
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 8px 12px;
		background: rgba(255, 255, 255, 0.95);
		backdrop-filter: blur(8px);
		border-radius: 10px;
		border: 1px solid rgba(255, 255, 255, 0.5);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.ap-event-card__date-day {
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
		color: var(--ap-text-primary);
	}

	.ap-event-card__date-month {
		font-size: 0.65rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-orange-500);
	}

	/* Genre Tag */
	.ap-event-card__genre-tag {
		position: absolute;
		top: 12px;
		right: 12px;
		padding: 4px 10px;
		font-size: 0.65rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		color: #fff;
		background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.4));
		backdrop-filter: blur(8px);
		border-radius: 6px;
	}

	/* Body */
	.ap-event-card__body {
		padding: 16px;
		flex: 1;
	}

	.ap-event-card__title {
		font-size: 1rem;
		font-weight: 700;
		line-height: 1.3;
		margin: 0 0 8px;
	}

	.ap-event-card__title a {
		color: var(--ap-text-primary);
		text-decoration: none;
		transition: color 0.2s;
	}

	.ap-event-card__title a:hover {
		color: var(--ap-orange-500);
	}

	/* Meta */
	.ap-event-card__meta {
		display: flex;
		flex-wrap: wrap;
		gap: 12px;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.ap-event-card__meta span {
		display: flex;
		align-items: center;
		gap: 4px;
	}

	.ap-event-card__meta i {
		font-size: 1rem;
		opacity: 0.6;
	}

	/* Footer */
	.ap-event-card__footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 12px 16px;
		border-top: 1px solid var(--ap-border-light);
	}

	.ap-event-card__rsvp {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.ap-event-card__avatars {
		display: flex;
	}

	.ap-event-card__avatar {
		width: 24px;
		height: 24px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		border: 2px solid #fff;
		margin-left: -8px;
	}

	.ap-event-card__avatar:first-child {
		margin-left: 0;
	}

	.ap-event-card__avatar-count {
		width: 24px;
		height: 24px;
		border-radius: 50%;
		background: var(--ap-text-primary);
		color: #fff;
		font-size: 0.6rem;
		font-weight: 600;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-left: -8px;
		border: 2px solid #fff;
	}

	.ap-event-card__interested {
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.ap-event-card__action {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 1px solid var(--ap-border-default);
		background: transparent;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.ap-event-card__action:hover {
		background: var(--ap-orange-100);
		border-color: var(--ap-orange-500);
		color: var(--ap-orange-500);
	}

	.ap-event-card__action.is-active {
		background: var(--ap-orange-500);
		border-color: var(--ap-orange-500);
		color: #fff;
	}

	/* Size Variants */
	.ap-event-card--small {
		max-width: 280px;
	}

	.ap-event-card--small .ap-event-card__title {
		font-size: 0.875rem;
	}

	.ap-event-card--large .ap-event-card__image {
		aspect-ratio: 21 / 9;
	}

	.ap-event-card--large .ap-event-card__title {
		font-size: 1.25rem;
	}

	/* Dark Mode */
	body.dark-mode .ap-event-card {
		--card-bg: var(--ap-bg-card, #111827);
		--card-border: var(--ap-border-default, #1f2937);
	}

	body.dark-mode .ap-event-card__date-badge {
		background: rgba(15, 23, 42, 0.9);
		border-color: rgba(255, 255, 255, 0.1);
	}

	body.dark-mode .ap-event-card__date-day {
		color: #f8fafc;
	}
</style>
