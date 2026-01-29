<?php

declare(strict_types=1);
/**
 * Apollo Event Grid Component
 *
 * Grid layout for displaying multiple event cards
 * Based on: grid_listing_events.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type WP_Query|array $events    Events query or array of event IDs
 *     @type int            $columns   Number of columns (default: 3)
 *     @type string         $layout    'grid' | 'masonry' | 'slider' (default: 'grid')
 *     @type bool           $ajax      Enable AJAX loading (default: true)
 *     @type int            $per_page  Events per page (default: 12)
 *     @type string         $card_size Card size: 'small' | 'medium' | 'large'
 * }
 */

defined('ABSPATH') || exit;

// Default args.
$defaults = array(
	'events'    => null,
	'columns'   => 3,
	'layout'    => 'grid',
	'ajax'      => true,
	'per_page'  => 12,
	'card_size' => 'medium',
	'filters'   => true,
	'title'     => '',
);

$args = wp_parse_args($args ?? array(), $defaults);

// Get events.
$events = $args['events'];
if (null === $events) {
	$events = new WP_Query(
		array(
			'post_type'      => 'event_listing',
			'posts_per_page' => $args['per_page'],
			'post_status'    => 'publish',
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_date',
					'value'   => current_time('Y-m-d'),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);
}

// Get genres for filter.
$genres = get_terms(
	array(
		'taxonomy'   => 'event_genre',
		'hide_empty' => true,
	)
);

// Unique grid ID for AJAX.
$grid_id = 'ap-event-grid-' . wp_unique_id();

?>
<section class="ap-event-grid-section" id="<?php echo esc_attr($grid_id); ?>">

	<?php if ($args['title']) : ?>
		<header class="ap-event-grid__header">
			<h2 class="ap-event-grid__title"><?php echo esc_html($args['title']); ?></h2>
		</header>
	<?php endif; ?>

	<?php if ($args['filters'] && ! empty($genres) && ! is_wp_error($genres)) : ?>
		<!-- Filters Bar -->
		<div class="ap-event-grid__filters">
			<div class="ap-filter-chips">
				<button type="button" class="ap-filter-chip is-active" data-filter="all">
					Todos
				</button>
				<?php foreach ($genres as $genre) : ?>
					<button type="button" class="ap-filter-chip" data-filter="<?php echo esc_attr($genre->slug); ?>">
						<?php echo esc_html($genre->name); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="ap-filter-controls">
				<div class="ap-filter-search">
					<i class="i-search-v" aria-hidden="true"></i>
					<input
						type="search"
						class="ap-filter-input"
						placeholder="Buscar eventos..."
						data-action="search-events">
				</div>

				<div class="ap-filter-view">
					<button type="button" class="ap-view-btn is-active" data-view="grid" aria-label="Visualização em grade">
						<i class="i-grid-v" aria-hidden="true"></i>
					</button>
					<button type="button" class="ap-view-btn" data-view="list" aria-label="Visualização em lista">
						<i class="i-list-check-v" aria-hidden="true"></i>
					</button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Events Grid -->
	<div
		class="ap-event-grid ap-event-grid--<?php echo esc_attr($args['layout']); ?> ap-event-grid--cols-<?php echo esc_attr($args['columns']); ?>"
		data-grid-id="<?php echo esc_attr($grid_id); ?>"
		data-columns="<?php echo esc_attr($args['columns']); ?>"
		data-per-page="<?php echo esc_attr($args['per_page']); ?>">
		<?php
		if ($events instanceof WP_Query) {
			if ($events->have_posts()) {
				while ($events->have_posts()) {
					$events->the_post();
					apollo_get_template_part(
						'template-parts/events/card',
						null,
						array(
							'event' => get_post(),
							'size'  => $args['card_size'],
						)
					);
				}
				wp_reset_postdata();
			} else {
		?>
				<div class="ap-event-grid__empty">
					<i class="i-calendar-event-v i-3x" aria-hidden="true"></i>
					<h3>Nenhum evento encontrado</h3>
					<p>Não há eventos programados para as próximas datas.</p>
				</div>
		<?php
			}
		} elseif (is_array($events)) {
			foreach ($events as $event_id) {
				apollo_get_template_part(
					'template-parts/events/card',
					null,
					array(
						'event' => $event_id,
						'size'  => $args['card_size'],
					)
				);
			}
		}
		?>
	</div>

	<?php if ($args['ajax'] && $events instanceof WP_Query && $events->max_num_pages > 1) : ?>
		<!-- Load More -->
		<div class="ap-event-grid__loadmore">
			<button
				type="button"
				class="ap-btn ap-btn--outline ap-btn--loadmore"
				data-action="load-more-events"
				data-page="1"
				data-max-pages="<?php echo esc_attr($events->max_num_pages); ?>">
				<span class="ap-btn__text">Carregar mais eventos</span>
				<span class="ap-btn__loader" hidden>
					<i class="i-loader-4-v i-spin" aria-hidden="true"></i>
				</span>
			</button>
		</div>
	<?php endif; ?>

</section>

<style>
	/* Event Grid Section */
	.ap-event-grid-section {
		width: 100%;
		max-width: 1400px;
		margin: 0 auto;
		padding: 2rem 1rem;
	}

	.ap-event-grid__header {
		margin-bottom: 1.5rem;
	}

	.ap-event-grid__title {
		font-size: 1.5rem;
		font-weight: 800;
		color: var(--ap-text-primary);
		margin: 0;
	}

	/* Filters */
	.ap-event-grid__filters {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		align-items: center;
		gap: 1rem;
		margin-bottom: 1.5rem;
		padding-bottom: 1rem;
		border-bottom: 1px solid var(--ap-border-light);
	}

	.ap-filter-chips {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
	}

	.ap-filter-chip {
		padding: 6px 14px;
		background: var(--ap-bg-surface);
		border: 1px solid transparent;
		border-radius: 20px;
		font-size: 0.75rem;
		font-weight: 600;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
	}

	.ap-filter-chip:hover {
		background: var(--ap-bg-muted);
		color: var(--ap-text-primary);
	}

	.ap-filter-chip.is-active {
		background: var(--ap-text-primary);
		color: #fff;
	}

	.ap-filter-controls {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.ap-filter-search {
		position: relative;
		display: flex;
		align-items: center;
	}

	.ap-filter-search i {
		position: absolute;
		left: 12px;
		color: var(--ap-text-muted);
		font-size: 1rem;
	}

	.ap-filter-input {
		padding: 8px 12px 8px 36px;
		border: 1px solid var(--ap-border-default);
		border-radius: 8px;
		font-size: 0.875rem;
		background: var(--ap-bg-main);
		color: var(--ap-text-primary);
		min-width: 200px;
		transition: border-color 0.2s;
	}

	.ap-filter-input:focus {
		outline: none;
		border-color: var(--ap-orange-500);
	}

	.ap-filter-view {
		display: flex;
		gap: 4px;
		padding: 4px;
		background: var(--ap-bg-surface);
		border-radius: 8px;
	}

	.ap-view-btn {
		width: 32px;
		height: 32px;
		border: none;
		background: transparent;
		border-radius: 6px;
		color: var(--ap-text-muted);
		cursor: pointer;
		transition: all 0.2s;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.ap-view-btn:hover {
		color: var(--ap-text-primary);
	}

	.ap-view-btn.is-active {
		background: #fff;
		color: var(--ap-text-primary);
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	/* Grid Layout */
	.ap-event-grid {
		display: grid;
		gap: 1.5rem;
	}

	.ap-event-grid--cols-2 {
		grid-template-columns: repeat(2, 1fr);
	}

	.ap-event-grid--cols-3 {
		grid-template-columns: repeat(3, 1fr);
	}

	.ap-event-grid--cols-4 {
		grid-template-columns: repeat(4, 1fr);
	}

	@media (max-width: 1024px) {

		.ap-event-grid--cols-4,
		.ap-event-grid--cols-3 {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (max-width: 640px) {

		.ap-event-grid--cols-4,
		.ap-event-grid--cols-3,
		.ap-event-grid--cols-2 {
			grid-template-columns: 1fr;
		}

		.ap-filter-input {
			min-width: 150px;
		}
	}

	/* Empty State */
	.ap-event-grid__empty {
		grid-column: 1 / -1;
		text-align: center;
		padding: 4rem 2rem;
		color: var(--ap-text-muted);
	}

	.ap-event-grid__empty i {
		color: var(--ap-border-default);
		margin-bottom: 1rem;
	}

	.ap-event-grid__empty h3 {
		font-size: 1.25rem;
		font-weight: 700;
		color: var(--ap-text-primary);
		margin-bottom: 0.5rem;
	}

	/* Load More */
	.ap-event-grid__loadmore {
		display: flex;
		justify-content: center;
		margin-top: 2rem;
	}

	.ap-btn--loadmore {
		padding: 12px 32px;
		font-size: 0.875rem;
		font-weight: 600;
		border: 1px solid var(--ap-border-default);
		border-radius: 8px;
		background: transparent;
		color: var(--ap-text-primary);
		cursor: pointer;
		transition: all 0.2s;
	}

	.ap-btn--loadmore:hover {
		background: var(--ap-bg-surface);
		border-color: var(--ap-text-primary);
	}

	.ap-btn--loadmore:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.ap-btn__loader {
		display: inline-flex;
		margin-left: 8px;
	}

	/* Dark Mode */
	body.dark-mode .ap-filter-chip.is-active {
		background: #f8fafc;
		color: #0f172a;
	}

	body.dark-mode .ap-view-btn.is-active {
		background: var(--ap-bg-card);
	}
</style>

<script>
	(function() {
		const section = document.getElementById('<?php echo esc_js($grid_id); ?>');
		if (!section) return;

		// Filter chips
		const chips = section.querySelectorAll('.ap-filter-chip');
		chips.forEach(chip => {
			chip.addEventListener('click', function() {
				chips.forEach(c => c.classList.remove('is-active'));
				this.classList.add('is-active');

				const filter = this.dataset.filter;
				filterEvents(filter);
			});
		});

		// View toggle
		const viewBtns = section.querySelectorAll('.ap-view-btn');
		const grid = section.querySelector('.ap-event-grid');

		viewBtns.forEach(btn => {
			btn.addEventListener('click', function() {
				viewBtns.forEach(b => b.classList.remove('is-active'));
				this.classList.add('is-active');

				const view = this.dataset.view;
				grid.classList.toggle('ap-event-grid--list', view === 'list');
			});
		});

		// Search
		const searchInput = section.querySelector('.ap-filter-input');
		if (searchInput) {
			let debounce;
			searchInput.addEventListener('input', function() {
				clearTimeout(debounce);
				debounce = setTimeout(() => {
					searchEvents(this.value);
				}, 300);
			});
		}

		// Load more
		const loadMoreBtn = section.querySelector('[data-action="load-more-events"]');
		if (loadMoreBtn) {
			loadMoreBtn.addEventListener('click', loadMoreEvents);
		}

		function filterEvents(filter) {
			const cards = grid.querySelectorAll('.ap-event-card');
			cards.forEach(card => {
				if (filter === 'all') {
					card.style.display = '';
				} else {
					const genres = card.dataset.genres || '';
					card.style.display = genres.includes(filter) ? '' : 'none';
				}
			});
		}

		function searchEvents(query) {
			const cards = grid.querySelectorAll('.ap-event-card');
			const q = query.toLowerCase();

			cards.forEach(card => {
				const title = card.querySelector('.ap-event-card__title')?.textContent.toLowerCase() || '';
				const venue = card.querySelector('.ap-event-card__venue')?.textContent.toLowerCase() || '';

				const match = title.includes(q) || venue.includes(q);
				card.style.display = match ? '' : 'none';
			});
		}

		async function loadMoreEvents() {
			const btn = loadMoreBtn;
			const page = parseInt(btn.dataset.page) + 1;
			const maxPages = parseInt(btn.dataset.maxPages);

			if (page > maxPages) return;

			btn.disabled = true;
			btn.querySelector('.ap-btn__text').hidden = true;
			btn.querySelector('.ap-btn__loader').hidden = false;

			try {
				const response = await fetch(apolloAjax.ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams({
						action: 'apollo_load_more_events',
						page: page,
						nonce: apolloAjax.nonce
					})
				});

				const data = await response.json();

				if (data.success && data.data.html) {
					grid.insertAdjacentHTML('beforeend', data.data.html);
					btn.dataset.page = page;

					if (page >= maxPages) {
						btn.remove();
					}
				}
			} catch (error) {
				console.error('Load more error:', error);
			} finally {
				btn.disabled = false;
				btn.querySelector('.ap-btn__text').hidden = false;
				btn.querySelector('.ap-btn__loader').hidden = true;
			}
		}
	})();
</script>
