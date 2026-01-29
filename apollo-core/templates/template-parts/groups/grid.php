<?php

declare(strict_types=1);
/**
 * Apollo Communities Listing Grid Template
 *
 * Grid listing of all communities/nucleos with filters
 * Based on: comuna_and_nucleao_listing_all.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Get filter parameters
$search    = sanitize_text_field($_GET['s'] ?? '');
$genre     = sanitize_text_field($_GET['genre'] ?? '');
$type      = sanitize_text_field($_GET['type'] ?? '');
$location  = sanitize_text_field($_GET['location'] ?? '');
$sort_by   = sanitize_text_field($_GET['sort'] ?? 'newest');

// Pagination
$per_page = 12;
$paged    = max(1, get_query_var('paged', 1));

// Build query
$query_args = array(
	'post_type'      => 'apollo_community',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'post_status'    => 'publish',
);

// Search filter
if ($search) {
	$query_args['s'] = $search;
}

// Genre filter
if ($genre) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'community_genre',
			'field'    => 'slug',
			'terms'    => $genre,
		),
	);
}

// Type filter (community type: nucleo, commune, collective)
if ($type) {
	$query_args['meta_query'][] = array(
		'key'   => '_community_type',
		'value' => $type,
	);
}

// Sorting
switch ($sort_by) {
	case 'popular':
		$query_args['meta_key'] = '_member_count';
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	case 'active':
		$query_args['meta_key'] = '_last_activity';
		$query_args['orderby']  = 'meta_value';
		$query_args['order']    = 'DESC';
		break;
	case 'alphabetical':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;
	default: // newest
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

$communities = new WP_Query($query_args);

// Get genres for filter
$genres = get_terms(array(
	'taxonomy'   => 'community_genre',
	'hide_empty' => true,
));

// Community types
$types = array(
	''          => 'Todos os tipos',
	'nucleo'    => 'Núcleo',
	'commune'   => 'Comuna',
	'collective' => 'Coletivo',
	'crew'      => 'Crew',
	'label'     => 'Selo',
);

// Sort options
$sort_options = array(
	'newest'      => 'Mais recentes',
	'popular'     => 'Mais membros',
	'active'      => 'Mais ativos',
	'alphabetical' => 'A-Z',
);

// Featured communities (pinned/highlighted)
$featured = new WP_Query(array(
	'post_type'      => 'apollo_community',
	'posts_per_page' => 3,
	'meta_key'       => '_is_featured',
	'meta_value'     => '1',
	'post_status'    => 'publish',
));

?>
<div class="communities-page-grid">

	<!-- Header Section -->
	<header class="page-header">
		<div class="header-inner">
			<div class="header-text">
				<h1>Comunidades</h1>
				<p>Encontre seu núcleo, comuna ou coletivo e conecte-se com a cena underground</p>
			</div>

			<!-- Search -->
			<form class="search-form" action="" method="get">
				<div class="search-box">
					<i class="i-search-v" aria-hidden="true"></i>
					<input
						type="text"
						name="s"
						value="<?php echo esc_attr($search); ?>"
						placeholder="Buscar comunidades...">
					<button type="submit">
						<i class="i-arrow-right-v" aria-hidden="true"></i>
					</button>
				</div>
			</form>
		</div>
	</header>

	<!-- Featured Communities -->
	<?php if ($featured->have_posts() && empty($search) && empty($genre) && empty($type)) : ?>
		<section class="featured-row">
			<div class="featured-container">
				<h2 class="row-title">
					<i class="i-star-v" aria-hidden="true"></i>
					Em Destaque
				</h2>
				<div class="featured-cards">
					<?php while ($featured->have_posts()) : $featured->the_post();
						$comm_id      = get_the_ID();
						$cover_image  = get_the_post_thumbnail_url($comm_id, 'medium_large');
						$member_count = (int) get_post_meta($comm_id, '_member_count', true);
						$event_count  = (int) get_post_meta($comm_id, '_event_count', true);
						$comm_type    = get_post_meta($comm_id, '_community_type', true);
						$comm_genres  = wp_get_post_terms($comm_id, 'community_genre', array('fields' => 'names'));
					?>
						<a href="<?php the_permalink(); ?>" class="feat-card">
							<div class="feat-cover" style="background-image: url('<?php echo esc_url($cover_image); ?>');">
								<div class="feat-gradient"></div>
								<span class="type-tag"><?php echo esc_html($types[$comm_type] ?? 'Comunidade'); ?></span>
							</div>
							<div class="feat-content">
								<h3><?php the_title(); ?></h3>
								<div class="feat-stats">
									<span><i class="i-group-v" aria-hidden="true"></i> <?php echo $member_count; ?> membros</span>
									<span><i class="i-calendar-v" aria-hidden="true"></i> <?php echo $event_count; ?> eventos</span>
								</div>
								<?php if (! empty($comm_genres)) : ?>
									<div class="feat-genres">
										<?php foreach (array_slice($comm_genres, 0, 2) as $g) : ?>
											<span><?php echo esc_html($g); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</a>
					<?php endwhile;
					wp_reset_postdata(); ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- Filters Bar -->
	<section class="filter-bar">
		<div class="filter-container">

			<!-- Filter Pills -->
			<div class="filter-row">
				<span class="filter-heading">Filtrar por:</span>

				<!-- Type Filter -->
				<div class="filter-select">
					<button type="button" class="select-btn <?php echo $type ? 'active' : ''; ?>">
						<i class="i-community-v" aria-hidden="true"></i>
						<?php echo $types[$type] ?? 'Tipo'; ?>
						<i class="i-arrow-down-s-v chevron" aria-hidden="true"></i>
					</button>
					<div class="select-menu">
						<?php foreach ($types as $key => $label) : ?>
							<a href="<?php echo $key ? add_query_arg('type', $key) : remove_query_arg('type'); ?>" class="menu-item <?php echo $type === $key ? 'active' : ''; ?>">
								<?php echo esc_html($label); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Genre Filter -->
				<div class="filter-select">
					<button type="button" class="select-btn <?php echo $genre ? 'active' : ''; ?>">
						<i class="i-music-2-v" aria-hidden="true"></i>
						<?php echo $genre ? esc_html(get_term_by('slug', $genre, 'community_genre')->name ?? 'Gênero') : 'Gênero'; ?>
						<i class="i-arrow-down-s-v chevron" aria-hidden="true"></i>
					</button>
					<div class="select-menu">
						<a href="<?php echo remove_query_arg('genre'); ?>" class="menu-item <?php echo empty($genre) ? 'active' : ''; ?>">
							Todos os gêneros
						</a>
						<?php foreach ($genres as $g) : ?>
							<a href="<?php echo add_query_arg('genre', $g->slug); ?>" class="menu-item <?php echo $genre === $g->slug ? 'active' : ''; ?>">
								<?php echo esc_html($g->name); ?>
								<span class="cnt"><?php echo $g->count; ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Sort & Count -->
			<div class="filter-right">
				<span class="count-label">
					<?php echo $communities->found_posts; ?> comunidades
				</span>

				<div class="filter-select">
					<button type="button" class="select-btn sort">
						<i class="i-sort-desc-v" aria-hidden="true"></i>
						<?php echo $sort_options[$sort_by] ?? 'Ordenar'; ?>
					</button>
					<div class="select-menu right-align">
						<?php foreach ($sort_options as $key => $label) : ?>
							<a href="<?php echo add_query_arg('sort', $key); ?>" class="menu-item <?php echo $sort_by === $key ? 'active' : ''; ?>">
								<?php echo esc_html($label); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Active Filters -->
		<?php if ($search || $genre || $type) : ?>
			<div class="active-row">
				<span class="active-heading">Filtros ativos:</span>

				<?php if ($search) : ?>
					<a href="<?php echo remove_query_arg('s'); ?>" class="active-chip">
						"<?php echo esc_html($search); ?>"
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($type) : ?>
					<a href="<?php echo remove_query_arg('type'); ?>" class="active-chip">
						<?php echo esc_html($types[$type]); ?>
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ($genre) : ?>
					<a href="<?php echo remove_query_arg('genre'); ?>" class="active-chip">
						<?php echo esc_html(get_term_by('slug', $genre, 'community_genre')->name ?? $genre); ?>
						<i class="i-close-v" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<a href="<?php echo remove_query_arg(array('s', 'genre', 'type')); ?>" class="clear-link">
					Limpar todos
				</a>
			</div>
		<?php endif; ?>
	</section>

	<!-- Communities Grid -->
	<main class="grid-section">
		<?php if ($communities->have_posts()) : ?>
			<div class="comm-grid">
				<?php while ($communities->have_posts()) : $communities->the_post();
					$comm_id      = get_the_ID();
					$cover_image  = get_the_post_thumbnail_url($comm_id, 'medium');
					$logo         = get_post_meta($comm_id, '_community_logo', true);
					$logo_url     = $logo ? wp_get_attachment_image_url($logo, 'thumbnail') : '';
					$member_count = (int) get_post_meta($comm_id, '_member_count', true);
					$event_count  = (int) get_post_meta($comm_id, '_event_count', true);
					$comm_type    = get_post_meta($comm_id, '_community_type', true);
					$comm_genres  = wp_get_post_terms($comm_id, 'community_genre', array('fields' => 'names'));
					$excerpt      = wp_trim_words(get_the_excerpt(), 15);
					$is_verified  = get_post_meta($comm_id, '_is_verified', true);

					// Privacy
					$privacy = get_post_meta($comm_id, '_community_privacy', true);
					$is_private = 'private' === $privacy;
				?>
					<article class="comm-card">
						<a href="<?php the_permalink(); ?>" class="comm-link">
							<div class="comm-cover" style="background-image: url('<?php echo esc_url($cover_image); ?>');">
								<span class="type-chip"><?php echo esc_html($types[$comm_type] ?? 'Comunidade'); ?></span>
								<?php if ($is_private) : ?>
									<span class="private-icon">
										<i class="i-lock-v" aria-hidden="true"></i>
									</span>
								<?php endif; ?>
							</div>

							<div class="comm-body">
								<div class="comm-head">
									<?php if ($logo_url) : ?>
										<img src="<?php echo esc_url($logo_url); ?>" alt="" class="comm-logo">
									<?php else : ?>
										<div class="logo-ph">
											<i class="i-community-v" aria-hidden="true"></i>
										</div>
									<?php endif; ?>
									<div class="title-block">
										<h3>
											<?php the_title(); ?>
											<?php if ($is_verified) : ?>
												<i class="i-verified-badge-v badge-verified" aria-hidden="true" title="Verificado"></i>
											<?php endif; ?>
										</h3>
										<?php if (! empty($comm_genres)) : ?>
											<span class="genre-sub"><?php echo esc_html(implode(', ', array_slice($comm_genres, 0, 2))); ?></span>
										<?php endif; ?>
									</div>
								</div>

								<p class="comm-excerpt"><?php echo esc_html($excerpt); ?></p>

								<div class="comm-meta">
									<span class="meta">
										<i class="i-group-v" aria-hidden="true"></i>
										<?php echo $member_count; ?>
									</span>
									<span class="meta">
										<i class="i-calendar-v" aria-hidden="true"></i>
										<?php echo $event_count; ?>
									</span>
								</div>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<!-- Pagination -->
			<?php if ($communities->max_num_pages > 1) : ?>
				<nav class="paging">
					<?php
					echo paginate_links(array(
						'total'     => $communities->max_num_pages,
						'current'   => $paged,
						'prev_text' => '<i class="i-arrow-left-v" aria-hidden="true"></i> Anterior',
						'next_text' => 'Próximo <i class="i-arrow-right-v" aria-hidden="true"></i>',
					));
					?>
				</nav>
			<?php endif; ?>

		<?php else : ?>
			<!-- No Results -->
			<div class="empty-state">
				<div class="empty-icon">
					<i class="i-community-v" aria-hidden="true"></i>
				</div>
				<h2>Nenhuma comunidade encontrada</h2>
				<p>Tente ajustar seus filtros ou criar uma nova comunidade.</p>
				<div class="empty-actions">
					<a href="<?php echo remove_query_arg(array('s', 'genre', 'type')); ?>" class="btn-outline">
						Ver todas
					</a>
					<a href="<?php echo esc_url(home_url('/criar-comunidade')); ?>" class="btn-fill">
						<i class="i-add-v" aria-hidden="true"></i>
						Criar Comunidade
					</a>
				</div>
			</div>
		<?php endif; ?>
	</main>

	<?php wp_reset_postdata(); ?>

	<!-- CTA Section -->
	<section class="cta-block">
		<div class="cta-inner">
			<h2>Quer criar sua própria comunidade?</h2>
			<p>Reúna sua galera, organize eventos e faça parte do movimento underground.</p>
			<a href="<?php echo esc_url(home_url('/criar-comunidade')); ?>" class="cta-button">
				<i class="i-add-v" aria-hidden="true"></i>
				Criar Nova Comunidade
			</a>
		</div>
	</section>

</div>

<style>
	/* Communities Page Grid Styles */
	.communities-page-grid {
		width: 100%;
		min-height: 100vh;
		background: var(--ap-bg-page);
	}

	/* Header */
	.page-header {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		padding: 2.5rem 1.5rem 3rem;
	}

	@media (min-width: 768px) {
		.page-header {
			padding: 3.5rem 2rem 4rem;
		}
	}

	.header-inner {
		max-width: 1280px;
		margin: 0 auto;
		text-align: center;
	}

	.header-text {
		color: #fff;
		margin-bottom: 2rem;
	}

	.header-text h1 {
		font-size: 2.25rem;
		font-weight: 900;
		margin: 0 0 0.5rem;
	}

	@media (min-width: 768px) {
		.header-text h1 {
			font-size: 3rem;
		}
	}

	.header-text p {
		font-size: 1rem;
		opacity: 0.85;
		margin: 0;
	}

	.search-form {
		max-width: 540px;
		margin: 0 auto;
	}

	.search-box {
		display: flex;
		align-items: center;
		background: #fff;
		border-radius: 999px;
		padding: 0.35rem 0.5rem 0.35rem 1.25rem;
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
	}

	.search-box>i {
		color: var(--ap-text-muted);
		font-size: 1.25rem;
		margin-right: 0.75rem;
	}

	.search-box input {
		flex: 1;
		border: none;
		outline: none;
		font-size: 1rem;
		padding: 0.75rem 0;
		background: transparent;
	}

	.search-box button {
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

	.search-box button:hover {
		transform: scale(1.05);
	}

	/* Featured Row */
	.featured-row {
		max-width: 1280px;
		margin: 0 auto;
		padding: 2rem 1.5rem;
	}

	.row-title {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 1.15rem;
		font-weight: 700;
		margin: 0 0 1.25rem;
	}

	.row-title i {
		color: #f97316;
	}

	.featured-cards {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
		gap: 1.25rem;
	}

	.feat-card {
		display: flex;
		flex-direction: column;
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
		transition: all 0.2s;
	}

	.feat-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
	}

	.feat-cover {
		height: 160px;
		background-size: cover;
		background-position: center;
		position: relative;
	}

	.feat-gradient {
		position: absolute;
		inset: 0;
		background: linear-gradient(to bottom, transparent 40%, rgba(0, 0, 0, 0.6) 100%);
	}

	.type-tag {
		position: absolute;
		top: 0.75rem;
		left: 0.75rem;
		background: rgba(255, 255, 255, 0.9);
		backdrop-filter: blur(8px);
		padding: 0.25rem 0.75rem;
		border-radius: 999px;
		font-size: 0.7rem;
		font-weight: 600;
		text-transform: uppercase;
	}

	.feat-content {
		padding: 1.25rem;
	}

	.feat-content h3 {
		font-size: 1.1rem;
		font-weight: 700;
		margin: 0 0 0.5rem;
	}

	.feat-stats {
		display: flex;
		gap: 1rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin-bottom: 0.75rem;
	}

	.feat-stats span {
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	.feat-genres {
		display: flex;
		gap: 0.35rem;
	}

	.feat-genres span {
		font-size: 0.7rem;
		padding: 0.2rem 0.5rem;
		background: var(--ap-bg-surface);
		border-radius: 4px;
	}

	/* Filter Bar */
	.filter-bar {
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		padding: 1rem 1.5rem;
		position: sticky;
		top: 0;
		z-index: 50;
	}

	.filter-container {
		max-width: 1280px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.filter-row {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		flex-wrap: wrap;
	}

	.filter-heading {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		font-weight: 600;
	}

	.filter-select {
		position: relative;
	}

	.select-btn {
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

	.select-btn:hover {
		border-color: #f97316;
	}

	.select-btn.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.select-btn .chevron {
		font-size: 1rem;
		transition: transform 0.2s;
	}

	.filter-select.open .chevron {
		transform: rotate(180deg);
	}

	.select-menu {
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

	.select-menu.right-align {
		left: auto;
		right: 0;
	}

	.filter-select.open .select-menu {
		display: block;
	}

	.menu-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0.5rem 0.75rem;
		border-radius: 0.5rem;
		font-size: 0.85rem;
		transition: background 0.15s;
	}

	.menu-item:hover {
		background: var(--ap-bg-surface);
	}

	.menu-item.active {
		background: #1e293b;
		color: #fff;
	}

	.menu-item .cnt {
		font-size: 0.75rem;
		opacity: 0.6;
	}

	.filter-right {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.count-label {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
	}

	/* Active Filters */
	.active-row {
		max-width: 1280px;
		margin: 0.75rem auto 0;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		flex-wrap: wrap;
	}

	.active-heading {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.active-chip {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		padding: 0.25rem 0.65rem;
		background: var(--ap-bg-surface);
		border-radius: 999px;
		font-size: 0.8rem;
		transition: all 0.2s;
	}

	.active-chip:hover {
		background: #fee2e2;
		color: #dc2626;
	}

	.active-chip i {
		font-size: 0.7rem;
	}

	.clear-link {
		font-size: 0.75rem;
		color: #dc2626;
		text-decoration: underline;
		margin-left: 0.5rem;
	}

	/* Grid Section */
	.grid-section {
		max-width: 1280px;
		margin: 0 auto;
		padding: 2rem 1.5rem;
	}

	.comm-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 1.25rem;
	}

	.comm-card {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
		transition: all 0.2s;
	}

	.comm-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
	}

	.comm-link {
		display: block;
	}

	.comm-cover {
		height: 120px;
		background-size: cover;
		background-position: center;
		background-color: var(--ap-bg-surface);
		position: relative;
	}

	.type-chip {
		position: absolute;
		top: 0.5rem;
		left: 0.5rem;
		background: rgba(255, 255, 255, 0.9);
		backdrop-filter: blur(8px);
		padding: 0.2rem 0.5rem;
		border-radius: 4px;
		font-size: 0.65rem;
		font-weight: 600;
		text-transform: uppercase;
	}

	.private-icon {
		position: absolute;
		top: 0.5rem;
		right: 0.5rem;
		width: 28px;
		height: 28px;
		background: rgba(0, 0, 0, 0.5);
		backdrop-filter: blur(8px);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-size: 0.8rem;
	}

	.comm-body {
		padding: 1rem 1.25rem 1.25rem;
	}

	.comm-head {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		margin-bottom: 0.75rem;
	}

	.comm-head .comm-logo {
		width: 48px;
		height: 48px;
		border-radius: 0.5rem;
		object-fit: cover;
		border: 2px solid #fff;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		margin-top: -2rem;
	}

	.logo-ph {
		width: 48px;
		height: 48px;
		border-radius: 0.5rem;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
		font-size: 1.25rem;
		margin-top: -2rem;
		border: 2px solid #fff;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	}

	.title-block {
		flex: 1;
		min-width: 0;
	}

	.title-block h3 {
		font-size: 0.95rem;
		font-weight: 700;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	.title-block .badge-verified {
		color: #3b82f6;
		font-size: 0.9rem;
	}

	.genre-sub {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.comm-excerpt {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		line-height: 1.5;
		margin: 0 0 0.75rem;
	}

	.comm-meta {
		display: flex;
		gap: 1rem;
	}

	.comm-meta .meta {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	/* Pagination */
	.paging {
		display: flex;
		justify-content: center;
		gap: 0.5rem;
		margin-top: 2rem;
	}

	.paging .page-numbers {
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

	.paging .page-numbers:hover {
		border-color: #f97316;
	}

	.paging .page-numbers.current {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	/* Empty State */
	.empty-state {
		text-align: center;
		padding: 4rem 2rem;
		background: #fff;
		border-radius: 1.25rem;
		border: 1px dashed var(--ap-border-default);
	}

	.empty-icon {
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

	.empty-state h2 {
		font-size: 1.25rem;
		margin: 0 0 0.5rem;
	}

	.empty-state p {
		font-size: 0.9rem;
		color: var(--ap-text-muted);
		margin: 0 0 1.5rem;
	}

	.empty-actions {
		display: flex;
		justify-content: center;
		gap: 0.75rem;
		flex-wrap: wrap;
	}

	.btn-outline {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.65rem 1.25rem;
		background: var(--ap-bg-surface);
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		transition: all 0.2s;
	}

	.btn-outline:hover {
		border-color: #f97316;
	}

	.btn-fill {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.65rem 1.25rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		transition: all 0.2s;
	}

	.btn-fill:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	/* CTA Block */
	.cta-block {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		padding: 3rem 1.5rem;
	}

	.cta-inner {
		max-width: 600px;
		margin: 0 auto;
		text-align: center;
		color: #fff;
	}

	.cta-inner h2 {
		font-size: 1.5rem;
		font-weight: 800;
		margin: 0 0 0.75rem;
	}

	.cta-inner p {
		font-size: 0.95rem;
		opacity: 0.85;
		margin: 0 0 1.5rem;
	}

	.cta-button {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.85rem 1.5rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border-radius: 999px;
		font-size: 0.95rem;
		font-weight: 700;
		transition: all 0.2s;
	}

	.cta-button:hover {
		transform: translateY(-2px);
		box-shadow: 0 12px 30px rgba(249, 115, 22, 0.4);
	}

	/* Dark Mode */
	body.dark-mode .page-header,
	body.dark-mode .cta-block {
		background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
	}

	body.dark-mode .filter-bar,
	body.dark-mode .feat-card,
	body.dark-mode .comm-card,
	body.dark-mode .empty-state {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .search-box,
	body.dark-mode .select-btn:not(.active),
	body.dark-mode .select-menu,
	body.dark-mode .paging .page-numbers:not(.current),
	body.dark-mode .btn-outline {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .search-box input {
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.communities-page-grid');
		if (!page) return;

		// Filter selects
		const selects = page.querySelectorAll('.filter-select');
		selects.forEach(sel => {
			const btn = sel.querySelector('.select-btn');

			btn.addEventListener('click', (e) => {
				e.stopPropagation();
				// Close others
				selects.forEach(s => {
					if (s !== sel) s.classList.remove('open');
				});
				sel.classList.toggle('open');
			});
		});

		// Close selects on click outside
		document.addEventListener('click', () => {
			selects.forEach(s => s.classList.remove('open'));
		});
	})();
</script>
