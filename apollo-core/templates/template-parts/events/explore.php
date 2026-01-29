<?php

declare(strict_types=1);
/**
 * Apollo Explore Events Page Template
 *
 * Full page template for event discovery/exploration
 * Based on: explore-events-page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Get filter parameters.
$search     = sanitize_text_field($_GET['s'] ?? '');
$genre      = sanitize_text_field($_GET['genre'] ?? '');
$date_range = sanitize_text_field($_GET['date'] ?? '');
$location   = sanitize_text_field($_GET['location'] ?? '');
$price_type = sanitize_text_field($_GET['price'] ?? '');
$view_mode  = sanitize_text_field($_GET['view'] ?? 'grid');

// Pagination.
$per_page = 12;
$paged    = max(1, get_query_var('paged', 1));

// Build query.
$query_args = array(
	'post_type'      => 'event_listing',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'post_status'    => 'publish',
	'meta_query'     => array(
		'relation' => 'AND',
		array(
			'key'     => '_event_date',
			'value'   => current_time('Y-m-d'),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	),
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_key'       => '_event_date',
);

// Search filter.
if ($search) {
	$query_args['s'] = $search;
}

// Genre filter.
if ($genre) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'event_genre',
			'field'    => 'slug',
			'terms'    => $genre,
		),
	);
}

// Date range filter.
if ($date_range) {
	$today = current_time('Y-m-d');
	switch ($date_range) {
		case 'today':
			$query_args['meta_query'][] = array(
				'key'   => '_event_date',
				'value' => $today,
			);
			break;
		case 'tomorrow':
			$tomorrow = date('Y-m-d', strtotime('+1 day'));
			$query_args['meta_query'][] = array(
				'key'   => '_event_date',
				'value' => $tomorrow,
			);
			break;
		case 'weekend':
			$saturday = date('Y-m-d', strtotime('next saturday'));
			$sunday = date('Y-m-d', strtotime('next sunday'));
			$query_args['meta_query'][] = array(
				'key'     => '_event_date',
				'value'   => array($saturday, $sunday),
				'compare' => 'IN',
			);
			break;
		case 'week':
			$end_of_week = date('Y-m-d', strtotime('+7 days'));
			$query_args['meta_query'][] = array(
				'key'     => '_event_date',
				'value'   => $end_of_week,
				'compare' => '<=',
			);
			break;
		case 'month':
			$end_of_month = date('Y-m-d', strtotime('+30 days'));
			$query_args['meta_query'][] = array(
				'key'     => '_event_date',
				'value'   => $end_of_month,
				'compare' => '<=',
			);
			break;
	}
}

// Price filter.
if ($price_type) {
	$query_args['meta_query'][] = array(
		'key'   => '_event_price_type',
		'value' => $price_type,
	);
}

$events = new WP_Query($query_args);

// Get genres for filter.
$genres = get_terms(array(
	'taxonomy'   => 'event_genre',
	'hide_empty' => true,
));

// Date filter options.
$date_options = array(
	''         => 'Qualquer data',
	'today'    => 'Hoje',
	'tomorrow' => 'Amanhã',
	'weekend'  => 'Fim de semana',
	'week'     => 'Próximos 7 dias',
	'month'    => 'Próximos 30 dias',
);

// Price filter options.
$price_options = array(
	''         => 'Qualquer preço',
	'free'     => 'Gratuito',
	'paid'     => 'Pago',
	'donation' => 'Contribuição',
);

?>
<div class="explore-events-page">

	<!-- Header with Search -->
	<header class="explore-header">
		<div class="header-content">
			<div class="header-title-wrap">
				<h1>Explorar Eventos</h1>
				<p>Descubra os melhores eventos de música eletrônica no Rio</p>
			</div>

			<form class="header-search" action="" method="get">
				<div class="search-input-wrap">
					<i class="i-search-v" aria-hidden="true"></i>
					<label for="events-search" class="sr-only">Buscar eventos</label>
					<input
						id="events-search"
						type="text"
						name="s"
						value="<?php echo esc_attr($search); ?>"
						placeholder="Buscar eventos, DJs, locais..."
						title="Buscar eventos">
					<button type="submit" class="search-submit" title="Pesquisar">
						<i class="i-arrow-right-v" aria-hidden="true"></i>
					</button>
				</div>
			</form>
		</div>
	</header>

	<!-- Filters Bar -->
	<section class="explore-filters">
		<div class="filters-inner">

			<!-- Quick Filters -->
			<div class="quick-filters">
				<span class="filter-label">Filtros:</span>

				<div class="filter-dropdown">
					<button type="button" class="filter-btn <?php echo $genre ? 'active' : ''; ?>">
						<i class="i-music-2-v" aria-hidden="true"></i>
						<?php echo $genre ? esc_html(get_term_by('slug', $genre, 'event_genre')->name ?? 'Gênero') : 'Gênero'; ?>
						<i class="i-arrow-down-s-v" aria-hidden="true"></i>
					</button>
					<div class="filter-dropdown-menu">
						<a href="<?php echo remove_query_arg('genre'); ?>" class="dropdown-item <?php echo empty($genre) ? 'active' : ''; ?>">
							Todos os gêneros
						</a>
						<?php foreach ($genres as $g) : ?>
							<a href="<?php echo add_query_arg('genre', $g->slug); ?>" class="dropdown-item <?php echo $genre === $g->slug ? 'active' : ''; ?>">
								<?php echo esc_html($g->name); ?>
								<span class="count"><?php echo $g->count; ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="filter-dropdown">
					<button type="button" class="filter-btn <?php echo $date_range ? 'active' : ''; ?>">
						<i class="i-calendar-v" aria-hidden="true"></i>
						<?php echo $date_options[$date_range] ?? 'Data'; ?>
						<i class="i-arrow-down-s-v" aria-hidden="true"></i>
					</button>
					<div class="filter-dropdown-menu">
						<?php foreach ($date_options as $key => $label) : ?>
							<a href="<?php echo $key ? add_query_arg('date', $key) : remove_query_arg('date'); ?>" class="dropdown-item <?php echo $date_range === $key ? 'active' : ''; ?>">
								<?php echo esc_html($label); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="filter-dropdown">
					<button type="button" class="filter-btn <?php echo $price_type ? 'active' : ''; ?>">
						<i class="i-ticket-v" aria-hidden="true"></i>
						<?php echo $price_options[$price_type] ?? 'Preço'; ?>
						<i class="i-arrow-down-s-v" aria-hidden="true"></i>
					</button>
					<div class="filter-dropdown-menu">
						<?php foreach ($price_options as $key => $label) : ?>
							<a href="<?php echo $key ? add_query_arg('price', $key) : remove_query_arg('price'); ?>" class="dropdown-item <?php echo $price_type === $key ? 'active' : ''; ?>">
								<?php echo esc_html($label); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- View Toggle & Results -->
			<div class="filters-right">
				<span class="results-count">
					<?php echo $events->found_posts; ?> eventos
				</span>

				<div class="view-toggle">
					<a href="<?php echo add_query_arg('view', 'grid'); ?>" class="view-btn <?php echo 'grid' === $view_mode ? 'active' : ''; ?>" title="Visualização em grade">
						<i class="i-grid-v" aria-hidden="true"></i>
					</a>
					<a href="<?php echo add_query_arg('view', 'list'); ?>" class="view-btn <?php echo 'list' === $view_mode ? 'active' : ''; ?>" title="Visualização em lista">
						<i class="i-list-unordered-v" aria-hidden="true"></i>
					</a>
					<a href="<?php echo add_query_arg('view', 'calendar'); ?>" class="view-btn <?php echo 'calendar' === $view_mode ? 'active' : ''; ?>" title="Visualização em calendário">
						<i class="i-calendar-v" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		</div>

		<!-- Active Filters -->
		<?php if ($search || $genre || $date_range || $price_type) : ?>
			<div class="active-filters">
				<span class="active-label">Filtros ativos:</span>

				<?php if ($search) : ?>
					<a href="<?php echo remove_query_arg('s'); ?>" class="active-tag">
						"<?php echo esc_html($search); ?>"
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($genre) : ?>
					<a href="<?php echo remove_query_arg('genre'); ?>" class="active-tag">
						<?php echo esc_html(get_term_by('slug', $genre, 'event_genre')->name ?? $genre); ?>
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($date_range) : ?>
					<a href="<?php echo remove_query_arg('date'); ?>" class="active-tag">
						<?php echo esc_html($date_options[$date_range]); ?>
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($price_type) : ?>
					<a href="<?php echo remove_query_arg('price'); ?>" class="active-tag">
						<?php echo esc_html($price_options[$price_type]); ?>
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<a href="<?php echo remove_query_arg(array('s', 'genre', 'date', 'price')); ?>" class="clear-all">
					Limpar todos
				</a>
			</div>
		<?php endif; ?>
	</section>

	<!-- Events Content -->
	<main class="explore-content">
		<?php if ($events->have_posts()) : ?>

			<?php if ('grid' === $view_mode) : ?>
				<!-- Grid View -->
				<div class="events-grid">
					<?php while ($events->have_posts()) : $events->the_post();
						$event_id     = get_the_ID();
						$event_date   = get_post_meta($event_id, '_event_date', true);
						$event_time   = get_post_meta($event_id, '_event_time', true);
						$event_venue  = get_post_meta($event_id, '_event_venue', true);
						$event_price_type = get_post_meta($event_id, '_event_price_type', true);
						$event_genres = wp_get_post_terms($event_id, 'event_genre', array('fields' => 'names'));
						$event_thumb  = get_the_post_thumbnail_url($event_id, 'medium_large');
						$date_obj     = DateTime::createFromFormat('Y-m-d', $event_date);
						$interested   = (int) get_post_meta($event_id, '_event_interested_count', true);
					?>
						<article class="event-card">
							<a href="<?php the_permalink(); ?>" class="event-card-link">
								<div class="event-card-image" style="background-image: url('<?php echo esc_url($event_thumb); ?>');">
									<div class="event-date-badge">
										<span class="day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
										<span class="month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
									</div>
									<?php if ('free' === $event_price_type) : ?>
										<span class="event-price-badge">Grátis</span>
									<?php endif; ?>
								</div>

								<div class="event-card-body">
									<h3 class="event-title"><?php the_title(); ?></h3>

									<div class="event-meta">
										<span class="meta-item">
											<i class="i-time-v" aria-hidden="true"></i>
											<?php echo esc_html($event_time); ?>
										</span>
										<span class="meta-item">
											<i class="i-map-pin-v" aria-hidden="true"></i>
											<?php echo esc_html($event_venue); ?>
										</span>
									</div>

									<?php if (! empty($event_genres)) : ?>
										<div class="event-genres">
											<?php foreach (array_slice($event_genres, 0, 2) as $g) : ?>
												<span class="genre-tag"><?php echo esc_html($g); ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>

								<div class="event-card-footer">
									<div class="interested-count">
										<i class="i-heart-v" aria-hidden="true"></i>
										<?php echo $interested; ?> interessados
									</div>
									<button type="button" class="btn-interest" data-event-id="<?php echo $event_id; ?>">
										<i class="i-add-v" aria-hidden="true"></i>
									</button>
								</div>
							</a>
						</article>
					<?php endwhile; ?>
				</div>

			<?php elseif ('list' === $view_mode) : ?>
				<!-- List View -->
				<div class="events-list">
					<?php while ($events->have_posts()) : $events->the_post();
						$event_id     = get_the_ID();
						$event_date   = get_post_meta($event_id, '_event_date', true);
						$event_time   = get_post_meta($event_id, '_event_time', true);
						$event_venue  = get_post_meta($event_id, '_event_venue', true);
						$event_genres = wp_get_post_terms($event_id, 'event_genre', array('fields' => 'names'));
						$event_thumb  = get_the_post_thumbnail_url($event_id, 'thumbnail');
						$date_obj     = DateTime::createFromFormat('Y-m-d', $event_date);
					?>
						<article class="event-list-item">
							<div class="event-list-date">
								<span class="day"><?php echo $date_obj ? $date_obj->format('d') : '--'; ?></span>
								<span class="month"><?php echo $date_obj ? strtoupper($date_obj->format('M')) : '---'; ?></span>
								<span class="weekday"><?php echo $date_obj ? $date_obj->format('D') : '---'; ?></span>
							</div>

							<div class="event-list-thumb" style="background-image: url('<?php echo esc_url($event_thumb); ?>');"></div>

							<div class="event-list-content">
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<div class="event-list-meta">
									<span><i class="i-time-v" aria-hidden="true"></i> <?php echo esc_html($event_time); ?></span>
									<span><i class="i-map-pin-v" aria-hidden="true"></i> <?php echo esc_html($event_venue); ?></span>
								</div>
								<?php if (! empty($event_genres)) : ?>
									<div class="event-list-genres">
										<?php foreach ($event_genres as $g) : ?>
											<span><?php echo esc_html($g); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>

							<div class="event-list-actions">
								<a href="<?php the_permalink(); ?>" class="btn-details">Ver Detalhes</a>
								<button type="button" class="btn-interest" data-event-id="<?php echo $event_id; ?>">
									<i class="i-heart-v" aria-hidden="true"></i>
								</button>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

			<?php elseif ('calendar' === $view_mode) : ?>
				<!-- Calendar View (placeholder) -->
				<div class="events-calendar">
					<div class="calendar-placeholder">
						<i class="i-calendar-v" aria-hidden="true"></i>
						<p>Visualização de calendário em breve</p>
					</div>
				</div>
			<?php endif; ?>

			<!-- Pagination -->
			<?php if ($events->max_num_pages > 1) : ?>
				<nav class="explore-pagination">
					<?php
					echo paginate_links(array(
						'total'     => $events->max_num_pages,
						'current'   => $paged,
						'prev_text' => '<i class="i-arrow-left-v" aria-hidden="true"></i> Anterior',
						'next_text' => 'Próximo <i class="i-arrow-right-v" aria-hidden="true"></i>',
					));
					?>
				</nav>
			<?php endif; ?>

		<?php else : ?>
			<!-- No Results -->
			<div class="no-events">
				<div class="no-events-icon">
					<i class="i-calendar-close-v" aria-hidden="true"></i>
				</div>
				<h2>Nenhum evento encontrado</h2>
				<p>Tente ajustar seus filtros ou faça uma nova busca.</p>
				<a href="<?php echo remove_query_arg(array('s', 'genre', 'date', 'price')); ?>" class="btn-reset">
					Ver todos os eventos
				</a>
			</div>
		<?php endif; ?>
	</main>

	<?php wp_reset_postdata(); ?>

</div>

<style>
	.sr-only {
		position: absolute !important;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}
	/* Explore Events Page Styles */
	.explore-events-page {
		width: 100%;
		min-height: 100vh;
		background: var(--ap-bg-page);
	}

	/* Header */
	.explore-header {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		padding: 2.5rem 1.5rem 3rem;
	}

	@media (min-width: 768px) {
		.explore-header {
			padding: 3.5rem 2rem 4rem;
		}
	}

	.header-content {
		max-width: 1280px;
		margin: 0 auto;
	}

	.header-title-wrap {
		text-align: center;
		color: #fff;
		margin-bottom: 2rem;
	}

	.header-title-wrap h1 {
		font-size: 2.25rem;
		font-weight: 900;
		margin: 0 0 0.5rem;
		letter-spacing: -0.02em;
	}

	@media (min-width: 768px) {
		.header-title-wrap h1 {
			font-size: 3rem;
		}
	}

	.header-title-wrap p {
		font-size: 1rem;
		opacity: 0.85;
		margin: 0;
	}

	.header-search {
		max-width: 640px;
		margin: 0 auto;
	}

	.search-input-wrap {
		display: flex;
		align-items: center;
		background: #fff;
		border-radius: 999px;
		padding: 0.35rem 0.5rem 0.35rem 1.25rem;
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
	}

	.search-input-wrap>i {
		color: var(--ap-text-muted);
		font-size: 1.25rem;
		margin-right: 0.75rem;
	}

	.search-input-wrap input {
		flex: 1;
		border: none;
		outline: none;
		font-size: 1rem;
		padding: 0.75rem 0;
		background: transparent;
	}

	.search-submit {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		transition: transform 0.2s;
	}

	.search-submit:hover {
		transform: scale(1.05);
	}

	/* Filters */
	.explore-filters {
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		padding: 1rem 1.5rem;
		position: sticky;
		top: 0;
		z-index: 50;
	}

	.filters-inner {
		max-width: 1280px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.quick-filters {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		flex-wrap: wrap;
	}

	.filter-label {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		font-weight: 600;
	}

	.filter-dropdown {
		position: relative;
	}

	.filter-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		background: #fff;
		font-size: 0.85rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.filter-btn:hover {
		border-color: #f97316;
	}

	.filter-btn.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.filter-btn .i-arrow-down-s-v {
		font-size: 1rem;
		transition: transform 0.2s;
	}

	.filter-dropdown.open .i-arrow-down-s-v {
		transform: rotate(180deg);
	}

	.filter-dropdown-menu {
		position: absolute;
		top: calc(100% + 0.5rem);
		left: 0;
		min-width: 180px;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
		padding: 0.5rem;
		display: none;
		z-index: 100;
	}

	.filter-dropdown.open .filter-dropdown-menu {
		display: block;
	}

	.dropdown-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0.5rem 0.75rem;
		border-radius: 0.5rem;
		font-size: 0.85rem;
		transition: background 0.15s;
	}

	.dropdown-item:hover {
		background: var(--ap-bg-surface);
	}

	.dropdown-item.active {
		background: #1e293b;
		color: #fff;
	}

	.dropdown-item .count {
		font-size: 0.75rem;
		opacity: 0.6;
	}

	.filters-right {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.results-count {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
	}

	.view-toggle {
		display: flex;
		gap: 0.25rem;
		background: var(--ap-bg-surface);
		padding: 0.25rem;
		border-radius: 0.5rem;
	}

	.view-btn {
		width: 36px;
		height: 36px;
		border-radius: 0.35rem;
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
		transition: all 0.2s;
	}

	.view-btn:hover {
		color: var(--ap-text-default);
	}

	.view-btn.active {
		background: #fff;
		color: #f97316;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	}

	/* Active Filters */
	.active-filters {
		max-width: 1280px;
		margin: 0.75rem auto 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		flex-wrap: wrap;
	}

	.active-label {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.active-tag {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.25rem 0.65rem;
		background: var(--ap-bg-surface);
		border-radius: 999px;
		font-size: 0.8rem;
		transition: all 0.2s;
	}

	.active-tag:hover {
		background: #fee2e2;
		color: #dc2626;
	}

	.active-tag i {
		font-size: 0.7rem;
	}

	.clear-all {
		font-size: 0.75rem;
		color: #dc2626;
		text-decoration: underline;
		margin-left: 0.5rem;
	}

	/* Content */
	.explore-content {
		max-width: 1280px;
		margin: 0 auto;
		padding: 2rem 1.5rem;
	}

	/* Grid View */
	.events-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 1.25rem;
	}

	.event-card {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.event-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
	}

	.event-card-link {
		display: block;
	}

	.event-card-image {
		height: 180px;
		background-size: cover;
		background-position: center;
		position: relative;
	}

	.event-date-badge {
		position: absolute;
		top: 0.75rem;
		left: 0.75rem;
		background: #fff;
		border-radius: 0.75rem;
		padding: 0.5rem 0.75rem;
		text-align: center;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.event-date-badge .day {
		display: block;
		font-size: 1.25rem;
		font-weight: 800;
		line-height: 1;
	}

	.event-date-badge .month {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--ap-text-muted);
	}

	.event-price-badge {
		position: absolute;
		top: 0.75rem;
		right: 0.75rem;
		background: #10b981;
		color: #fff;
		font-size: 0.7rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		padding: 0.25rem 0.6rem;
		border-radius: 999px;
		font-weight: 600;
	}

	.event-card-body {
		padding: 1rem 1.25rem;
	}

	.event-title {
		font-size: 1rem;
		font-weight: 700;
		margin: 0 0 0.5rem;
		line-height: 1.3;
	}

	.event-meta {
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem;
		margin-bottom: 0.75rem;
	}

	.meta-item {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	.event-genres {
		display: flex;
		gap: 0.35rem;
	}

	.genre-tag {
		font-size: 0.7rem;
		padding: 0.2rem 0.5rem;
		background: var(--ap-bg-surface);
		border-radius: 4px;
		color: var(--ap-text-muted);
	}

	.event-card-footer {
		padding: 0.75rem 1.25rem;
		border-top: 1px solid var(--ap-border-default);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.interested-count {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	.btn-interest {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
	}

	.btn-interest:hover {
		border-color: #ef4444;
		color: #ef4444;
	}

	.btn-interest.active {
		background: #ef4444;
		border-color: #ef4444;
		color: #fff;
	}

	/* List View */
	.events-list {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.event-list-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		padding: 1rem;
		transition: all 0.2s;
	}

	.event-list-item:hover {
		transform: translateX(4px);
		box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
	}

	.event-list-date {
		min-width: 60px;
		text-align: center;
		background: #1e293b;
		color: #fff;
		padding: 0.75rem 0.5rem;
		border-radius: 0.75rem;
	}

	.event-list-date .day {
		display: block;
		font-size: 1.5rem;
		font-weight: 800;
		line-height: 1;
	}

	.event-list-date .month {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		opacity: 0.8;
	}

	.event-list-date .weekday {
		display: block;
		font-size: 0.6rem;
		text-transform: uppercase;
		opacity: 0.6;
		margin-top: 0.25rem;
	}

	.event-list-thumb {
		width: 80px;
		height: 80px;
		border-radius: 0.75rem;
		background-size: cover;
		background-position: center;
		flex-shrink: 0;
	}

	.event-list-content {
		flex: 1;
	}

	.event-list-content h3 {
		font-size: 1rem;
		font-weight: 700;
		margin: 0 0 0.35rem;
	}

	.event-list-content h3 a {
		color: inherit;
	}

	.event-list-content h3 a:hover {
		color: #f97316;
	}

	.event-list-meta {
		display: flex;
		gap: 1rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin-bottom: 0.35rem;
	}

	.event-list-meta span {
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	.event-list-genres span {
		font-size: 0.7rem;
		padding: 0.15rem 0.4rem;
		background: var(--ap-bg-surface);
		border-radius: 4px;
		margin-right: 0.35rem;
	}

	.event-list-actions {
		display: flex;
		gap: 0.5rem;
		align-items: center;
	}

	.btn-details {
		padding: 0.5rem 1rem;
		background: #1e293b;
		color: #fff;
		border-radius: 999px;
		font-size: 0.8rem;
		font-weight: 600;
		transition: background 0.2s;
	}

	.btn-details:hover {
		background: #0f172a;
	}

	/* Calendar View */
	.events-calendar {
		min-height: 400px;
	}

	.calendar-placeholder {
		text-align: center;
		padding: 4rem 2rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px dashed var(--ap-border-default);
	}

	.calendar-placeholder i {
		font-size: 3rem;
		color: var(--ap-text-muted);
		opacity: 0.5;
		margin-bottom: 1rem;
		display: block;
	}

	/* Pagination */
	.explore-pagination {
		display: flex;
		justify-content: center;
		gap: 0.5rem;
		margin-top: 2rem;
	}

	.explore-pagination .page-numbers {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.65rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		background: #fff;
		transition: all 0.2s;
	}

	.explore-pagination .page-numbers:hover {
		border-color: #f97316;
	}

	.explore-pagination .page-numbers.current {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	/* No Results */
	.no-events {
		text-align: center;
		padding: 4rem 2rem;
		background: #fff;
		border-radius: 1.25rem;
		border: 1px dashed var(--ap-border-default);
	}

	.no-events-icon {
		width: 80px;
		height: 80px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 1.5rem;
		font-size: 2rem;
		color: var(--ap-text-muted);
	}

	.no-events h2 {
		font-size: 1.25rem;
		margin: 0 0 0.5rem;
	}

	.no-events p {
		font-size: 0.9rem;
		color: var(--ap-text-muted);
		margin: 0 0 1.5rem;
	}

	.btn-reset {
		display: inline-flex;
		align-items: center;
		padding: 0.65rem 1.25rem;
		background: var(--ap-bg-surface);
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		transition: all 0.2s;
	}

	.btn-reset:hover {
		border-color: #f97316;
		color: #f97316;
	}

	/* Dark Mode */
	body.dark-mode .explore-filters,
	body.dark-mode .event-card,
	body.dark-mode .event-list-item,
	body.dark-mode .no-events,
	body.dark-mode .calendar-placeholder {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .search-input-wrap,
	body.dark-mode .filter-btn:not(.active),
	body.dark-mode .filter-dropdown-menu,
	body.dark-mode .event-date-badge,
	body.dark-mode .view-btn.active,
	body.dark-mode .btn-interest,
	body.dark-mode .explore-pagination .page-numbers:not(.current) {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .search-input-wrap input {
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.explore-events-page');
		if (!page) return;

		// Filter dropdowns
		const dropdowns = page.querySelectorAll('.filter-dropdown');
		dropdowns.forEach(dropdown => {
			const btn = dropdown.querySelector('.filter-btn');

			btn.addEventListener('click', (e) => {
				e.stopPropagation();
				// Close others
				dropdowns.forEach(d => {
					if (d !== dropdown) d.classList.remove('open');
				});
				dropdown.classList.toggle('open');
			});
		});

		// Close dropdowns on click outside
		document.addEventListener('click', () => {
			dropdowns.forEach(d => d.classList.remove('open'));
		});

		// Interest buttons
		page.querySelectorAll('.btn-interest').forEach(btn => {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const eventId = this.dataset.eventId;
				this.classList.toggle('active');

				// AJAX call
				if (typeof apolloAjax !== 'undefined') {
					const isInterested = this.classList.contains('active');
					fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: isInterested ? 'apollo_add_interest' : 'apollo_remove_interest',
							event_id: eventId,
							nonce: apolloAjax.nonce
						})
					});
				}
			});
		});
	})();
</script>
