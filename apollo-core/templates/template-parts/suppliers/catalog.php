<?php

declare(strict_types=1);
/**
 * Apollo Suppliers/Fornecedores Catalog Template
 *
 * Catalog page for suppliers (equipment, venues, services)
 * Based on: fornece-suppliers-page.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type string $category     Filter by category
 *     @type string $search       Search query
 *     @type int    $per_page     Items per page
 * }
 */

defined('ABSPATH') || exit;

// Parameters.
$category = $args['category'] ?? ($_GET['category'] ?? '');
$search   = $args['search'] ?? ($_GET['s'] ?? '');
$per_page = $args['per_page'] ?? 12;
$paged    = max(1, get_query_var('paged', 1));

// Categories.
$categories = array(
	'som'        => array('label' => 'Som & PA', 'icon' => 'i-speaker-v'),
	'iluminacao' => array('label' => 'Iluminação', 'icon' => 'i-flashlight-v'),
	'dj'         => array('label' => 'Equipamento DJ', 'icon' => 'i-disc-v'),
	'locais'     => array('label' => 'Locais', 'icon' => 'i-building-v'),
	'producao'   => array('label' => 'Produção', 'icon' => 'i-tools-v'),
	'staff'      => array('label' => 'Staff', 'icon' => 'i-group-v'),
	'seguranca'  => array('label' => 'Segurança', 'icon' => 'i-shield-check-v'),
	'bar'        => array('label' => 'Bar & Drinks', 'icon' => 'i-goblet-v'),
	'transporte' => array('label' => 'Transporte', 'icon' => 'i-truck-v'),
	'foto-video' => array('label' => 'Foto & Vídeo', 'icon' => 'i-camera-v'),
);

// Query suppliers.
$query_args = array(
	'post_type'      => 'apollo_supplier',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'post_status'    => 'publish',
);

if ($category) {
	$query_args['meta_query'][] = array(
		'key'   => '_supplier_category',
		'value' => $category,
	);
}

if ($search) {
	$query_args['s'] = $search;
}

$suppliers = new WP_Query($query_args);

?>
<div class="suppliers-page">

	<!-- Hero Section -->
	<header class="suppliers-hero">
		<div class="hero-content">
			<span class="hero-label">
				<i class="i-store-3-v" aria-hidden="true"></i>
				Apollo Fornece
			</span>
			<h1>Catálogo de Fornecedores</h1>
			<p>Encontre tudo que você precisa para seu evento: som, luz, locais, staff e muito mais.</p>
		</div>
	</header>

	<!-- Search Bar -->
	<section class="suppliers-search-section">
		<form class="search-form" action="" method="get">
			<div class="search-input-wrap">
				<i class="i-search-v" aria-hidden="true"></i>
				<label for="suppliers-search" class="sr-only">Buscar fornecedores</label>
				<input
					type="text"
					id="suppliers-search"
					name="s"
					value="<?php echo esc_attr($search); ?>"
					placeholder="Buscar fornecedores, serviços, equipamentos..."
					title="Buscar fornecedores">
				<button type="submit" class="search-btn" title="Buscar fornecedores">
					Buscar
				</button>
			</div>
		</form>
	</section>

	<!-- Category Filters -->
	<section class="suppliers-categories">
		<div class="categories-scroll">
			<a href="<?php echo remove_query_arg('category'); ?>" class="category-card <?php echo empty($category) ? 'active' : ''; ?>">
				<div class="category-icon">
					<i class="i-apps-v" aria-hidden="true"></i>
				</div>
				<span>Todos</span>
			</a>
			<?php foreach ($categories as $slug => $cat) : ?>
				<a href="<?php echo add_query_arg('category', $slug); ?>" class="category-card <?php echo $category === $slug ? 'active' : ''; ?>">
					<div class="category-icon">
						<i class="<?php echo esc_attr($cat['icon']); ?>" aria-hidden="true"></i>
					</div>
					<span><?php echo esc_html($cat['label']); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Results Header -->
	<div class="results-header">
		<div class="results-count">
			<?php if ($suppliers->have_posts()) : ?>
				<strong><?php echo $suppliers->found_posts; ?></strong> fornecedores encontrados
				<?php if ($category && isset($categories[$category])) : ?>
					em <span class="category-tag"><?php echo esc_html($categories[$category]['label']); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<div class="results-sort">
			<label for="sortSuppliers">Ordenar:</label>
			<select id="sortSuppliers" title="Ordenar fornecedores">
				<option value="rating">Melhor avaliados</option>
				<option value="recent">Mais recentes</option>
				<option value="name">Nome A-Z</option>
			</select>
		</div>
	</div>

	<!-- Suppliers Grid -->
	<main class="suppliers-grid">
		<?php if ($suppliers->have_posts()) : ?>
			<?php while ($suppliers->have_posts()) : $suppliers->the_post();
				$supplier_id      = get_the_ID();
				$supplier_logo    = get_the_post_thumbnail_url($supplier_id, 'medium');
				$supplier_cat     = get_post_meta($supplier_id, '_supplier_category', true);
				$supplier_rating  = (float) get_post_meta($supplier_id, '_supplier_rating', true);
				$supplier_reviews = (int) get_post_meta($supplier_id, '_supplier_reviews_count', true);
				$supplier_city    = get_post_meta($supplier_id, '_supplier_city', true) ?: 'Rio de Janeiro';
				$supplier_phone   = get_post_meta($supplier_id, '_supplier_phone', true);
				$supplier_whats   = get_post_meta($supplier_id, '_supplier_whatsapp', true);
				$supplier_tags    = get_post_meta($supplier_id, '_supplier_tags', true);
				$supplier_verified = get_post_meta($supplier_id, '_supplier_verified', true);

				$cat_info = $categories[$supplier_cat] ?? null;
			?>
				<article class="supplier-card">
					<div class="supplier-card-header">
						<div class="supplier-logo">
							<?php if ($supplier_logo) : ?>
								<img src="<?php echo esc_url($supplier_logo); ?>" alt="<?php the_title_attribute(); ?>">
							<?php else : ?>
								<div class="supplier-logo-placeholder">
									<i class="<?php echo $cat_info ? esc_attr($cat_info['icon']) : 'i-store-3-v'; ?>" aria-hidden="true"></i>
								</div>
							<?php endif; ?>
						</div>

						<?php if ($supplier_verified) : ?>
							<span class="verified-badge" title="Fornecedor Verificado">
								<i class="i-verified-badge-v" aria-hidden="true"></i>
							</span>
						<?php endif; ?>

						<?php if ($cat_info) : ?>
							<span class="supplier-category-badge">
								<i class="<?php echo esc_attr($cat_info['icon']); ?>" aria-hidden="true"></i>
								<?php echo esc_html($cat_info['label']); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="supplier-card-body">
						<h3 class="supplier-name">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<div class="supplier-location">
							<i class="i-map-pin-v" aria-hidden="true"></i>
							<?php echo esc_html($supplier_city); ?>
						</div>

						<?php if ($supplier_rating) : ?>
							<div class="supplier-rating">
								<div class="rating-stars">
									<?php for ($i = 1; $i <= 5; $i++) : ?>
										<i class="<?php echo $i <= round($supplier_rating) ? 'i-star-fill-v' : 'i-star-v'; ?>" aria-hidden="true"></i>
									<?php endfor; ?>
								</div>
								<span class="rating-value"><?php echo number_format($supplier_rating, 1); ?></span>
								<span class="rating-count">(<?php echo $supplier_reviews; ?>)</span>
							</div>
						<?php endif; ?>

						<p class="supplier-excerpt">
							<?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
						</p>

						<?php if ($supplier_tags) : ?>
							<div class="supplier-tags">
								<?php
								$tags = is_array($supplier_tags) ? $supplier_tags : explode(',', $supplier_tags);
								foreach (array_slice($tags, 0, 3) as $tag) :
								?>
									<span class="tag"><?php echo esc_html(trim($tag)); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="supplier-card-footer">
						<a href="<?php the_permalink(); ?>" class="btn-view">
							Ver Detalhes
						</a>
						<?php if ($supplier_whats) : ?>
							<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $supplier_whats); ?>" target="_blank" class="btn-whatsapp" title="Conversar no WhatsApp" aria-label="Conversar no WhatsApp">
								<i class="ri-whatsapp-line"></i>
							</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<div class="no-results">
				<div class="no-results-icon">
					<i class="i-search-v" aria-hidden="true"></i>
				</div>
				<h3>Nenhum fornecedor encontrado</h3>
				<p>Tente ajustar seus filtros ou fazer uma nova busca.</p>
				<a href="<?php echo remove_query_arg(array('category', 's')); ?>" class="btn-reset">
					Ver todos os fornecedores
				</a>
			</div>
		<?php endif; ?>
	</main>

	<!-- Pagination -->
	<?php if ($suppliers->max_num_pages > 1) : ?>
		<nav class="suppliers-pagination">
			<?php
			echo paginate_links(array(
				'total'     => $suppliers->max_num_pages,
				'current'   => $paged,
				'prev_text' => '<i class="i-arrow-left-v" aria-hidden="true"></i>',
				'next_text' => '<i class="i-arrow-right-v" aria-hidden="true"></i>',
			));
			?>
		</nav>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>

	<!-- CTA Section -->
	<section class="suppliers-cta">
		<div class="cta-content">
			<h2>Você é um fornecedor?</h2>
			<p>Cadastre sua empresa no Apollo Fornece e conecte-se com produtores e organizadores de eventos.</p>
			<a href="<?php echo home_url('/cadastrar-fornecedor/'); ?>" class="btn-cta">
				<i class="i-add-v" aria-hidden="true"></i>
				Cadastrar Minha Empresa
			</a>
		</div>
	</section>

</div>

<style>
	/* Suppliers Page Styles */
	.suppliers-page {
		width: 100%;
		background: var(--ap-bg-page);
	}

	/* Hero */
	.suppliers-hero {
		background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
		color: #fff;
		padding: 3rem 1.5rem;
		text-align: center;
	}

	@media (min-width: 768px) {
		.suppliers-hero {
			padding: 4rem 2rem;
		}
	}

	.hero-label {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.16em;
		background: rgba(249, 115, 22, 0.2);
		color: #fb923c;
		padding: 0.35rem 0.85rem;
		border-radius: 999px;
		margin-bottom: 1rem;
	}

	.suppliers-hero h1 {
		font-size: 2.25rem;
		font-weight: 900;
		margin: 0 0 0.75rem;
		letter-spacing: -0.02em;
	}

	@media (min-width: 768px) {
		.suppliers-hero h1 {
			font-size: 3rem;
		}
	}

	.suppliers-hero p {
		font-size: 1rem;
		opacity: 0.85;
		max-width: 600px;
		margin: 0 auto;
	}

	/* Search Section */
	.suppliers-search-section {
		max-width: 720px;
		margin: -1.5rem auto 0;
		padding: 0 1.5rem;
		position: relative;
		z-index: 10;
	}

	.search-form {
		background: #fff;
		border-radius: 1rem;
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
		overflow: hidden;
	}

	.search-input-wrap {
		display: flex;
		align-items: center;
		padding: 0.5rem;
	}

	.search-input-wrap>i {
		padding: 0 1rem;
		color: var(--ap-text-muted);
		font-size: 1.25rem;
	}

	.search-input-wrap input {
		flex: 1;
		border: none;
		outline: none;
		font-size: 1rem;
		padding: 0.75rem 0;
	}

	.search-btn {
		padding: 0.85rem 1.5rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 0.75rem;
		font-size: 0.9rem;
		font-weight: 600;
		cursor: pointer;
		transition: transform 0.2s;
	}

	.search-btn:hover {
		transform: translateY(-1px);
	}

	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}

	/* Categories */
	.suppliers-categories {
		padding: 2rem 1.5rem;
		overflow-x: auto;
	}

	.categories-scroll {
		display: flex;
		gap: 0.75rem;
		justify-content: center;
		min-width: max-content;
	}

	@media (max-width: 1024px) {
		.categories-scroll {
			justify-content: flex-start;
			padding-right: 1.5rem;
		}
	}

	.category-card {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
		padding: 1rem 1.25rem;
		background: #fff;
		border-radius: 1rem;
		border: 1px solid var(--ap-border-default);
		min-width: 90px;
		transition: all 0.2s;
	}

	.category-card:hover {
		border-color: #f97316;
		transform: translateY(-2px);
	}

	.category-card.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.category-icon {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		color: var(--ap-text-muted);
	}

	.category-card.active .category-icon {
		background: rgba(249, 115, 22, 0.2);
		color: #fb923c;
	}

	.category-card span {
		font-size: 0.75rem;
		font-weight: 600;
		white-space: nowrap;
	}

	/* Results Header */
	.results-header {
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 1.5rem 1rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.results-count {
		font-size: 0.9rem;
		color: var(--ap-text-muted);
	}

	.results-count strong {
		color: var(--ap-text-default);
	}

	.category-tag {
		display: inline-flex;
		background: var(--ap-bg-surface);
		padding: 0.15rem 0.5rem;
		border-radius: 4px;
		font-size: 0.8rem;
	}

	.results-sort {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.results-sort label {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	.results-sort select {
		padding: 0.4rem 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.85rem;
		background: #fff;
	}

	/* Suppliers Grid */
	.suppliers-grid {
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 1.5rem 2rem;
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
		gap: 1.25rem;
	}

	/* Supplier Card */
	.supplier-card {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		overflow: hidden;
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.supplier-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
	}

	.supplier-card-header {
		position: relative;
		padding: 1.5rem 1.5rem 0;
		display: flex;
		align-items: flex-start;
	}

	.supplier-logo {
		width: 72px;
		height: 72px;
		border-radius: 1rem;
		overflow: hidden;
		border: 1px solid var(--ap-border-default);
		background: #fff;
	}

	.supplier-logo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.supplier-logo-placeholder {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		background: var(--ap-bg-surface);
		font-size: 1.75rem;
		color: var(--ap-text-muted);
	}

	.verified-badge {
		position: absolute;
		top: 1rem;
		right: 1rem;
		width: 28px;
		height: 28px;
		border-radius: 50%;
		background: linear-gradient(135deg, #10b981, #059669);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.85rem;
	}

	.supplier-category-badge {
		position: absolute;
		bottom: 0;
		right: 1.5rem;
		transform: translateY(50%);
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.65rem;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		background: #1e293b;
		color: #fff;
		padding: 0.35rem 0.75rem;
		border-radius: 999px;
	}

	.supplier-card-body {
		padding: 1.5rem;
	}

	.supplier-name {
		font-size: 1.1rem;
		font-weight: 700;
		margin: 0 0 0.5rem;
	}

	.supplier-name a {
		color: inherit;
		transition: color 0.2s;
	}

	.supplier-name a:hover {
		color: #f97316;
	}

	.supplier-location {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin-bottom: 0.75rem;
	}

	.supplier-rating {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		margin-bottom: 0.75rem;
	}

	.rating-stars {
		display: flex;
		gap: 0.15rem;
	}

	.rating-stars i {
		font-size: 0.85rem;
		color: #fbbf24;
	}

	.rating-value {
		font-size: 0.85rem;
		font-weight: 700;
	}

	.rating-count {
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.supplier-excerpt {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		line-height: 1.5;
		margin: 0 0 0.75rem;
	}

	.supplier-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 0.35rem;
	}

	.supplier-tags .tag {
		font-size: 0.7rem;
		padding: 0.2rem 0.5rem;
		background: var(--ap-bg-surface);
		border-radius: 4px;
		color: var(--ap-text-muted);
	}

	.supplier-card-footer {
		padding: 1rem 1.5rem;
		border-top: 1px solid var(--ap-border-default);
		display: flex;
		gap: 0.75rem;
	}

	.btn-view {
		flex: 1;
		padding: 0.6rem 1rem;
		background: #1e293b;
		color: #fff;
		border-radius: 0.75rem;
		font-size: 0.8rem;
		font-weight: 600;
		text-align: center;
		transition: background 0.2s;
	}

	.btn-view:hover {
		background: #0f172a;
	}

	.btn-whatsapp {
		width: 44px;
		height: 44px;
		border-radius: 0.75rem;
		background: #25d366;
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		flex-shrink: 0;
		transition: background 0.2s;
	}

	.btn-whatsapp:hover {
		background: #128c7e;
	}

	/* No Results */
	.no-results {
		grid-column: 1 / -1;
		text-align: center;
		padding: 4rem 2rem;
		background: #fff;
		border-radius: 1.25rem;
		border: 1px dashed var(--ap-border-default);
	}

	.no-results-icon {
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

	.no-results h3 {
		font-size: 1.25rem;
		margin: 0 0 0.5rem;
	}

	.no-results p {
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

	/* Pagination */
	.suppliers-pagination {
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 1.5rem 2rem;
		display: flex;
		justify-content: center;
		gap: 0.5rem;
	}

	.suppliers-pagination .page-numbers {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 40px;
		height: 40px;
		padding: 0 0.5rem;
		border-radius: 0.5rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		font-size: 0.9rem;
		transition: all 0.2s;
	}

	.suppliers-pagination .page-numbers:hover {
		border-color: #f97316;
	}

	.suppliers-pagination .page-numbers.current {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	/* CTA Section */
	.suppliers-cta {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		padding: 3rem 1.5rem;
		text-align: center;
	}

	.cta-content {
		max-width: 600px;
		margin: 0 auto;
	}

	.suppliers-cta h2 {
		font-size: 1.75rem;
		font-weight: 800;
		margin: 0 0 0.75rem;
	}

	.suppliers-cta p {
		font-size: 1rem;
		opacity: 0.9;
		margin: 0 0 1.5rem;
	}

	.btn-cta {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.85rem 1.75rem;
		background: #fff;
		color: #ea580c;
		border-radius: 999px;
		font-size: 0.9rem;
		font-weight: 700;
		transition: transform 0.2s, box-shadow 0.2s;
	}

	.btn-cta:hover {
		transform: translateY(-2px);
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
	}

	/* Dark Mode */
	body.dark-mode .search-form,
	body.dark-mode .category-card:not(.active),
	body.dark-mode .supplier-card,
	body.dark-mode .no-results {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .search-input-wrap input {
		background: transparent;
		color: var(--ap-text-default);
	}

	body.dark-mode .results-sort select {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
		color: var(--ap-text-default);
	}

	body.dark-mode .suppliers-pagination .page-numbers {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}
</style>

<script>
	(function() {
		const page = document.querySelector('.suppliers-page');
		if (!page) return;

		// Sort select
		const sortSelect = document.getElementById('sortSuppliers');
		if (sortSelect) {
			sortSelect.addEventListener('change', function() {
				const url = new URL(window.location);
				url.searchParams.set('orderby', this.value);
				window.location.href = url.toString();
			});

			// Set current value from URL
			const params = new URLSearchParams(window.location.search);
			if (params.has('orderby')) {
				sortSelect.value = params.get('orderby');
			}
		}

		// Smooth scroll for categories
		const categoriesScroll = page.querySelector('.categories-scroll');
		if (categoriesScroll) {
			let isDown = false;
			let startX;
			let scrollLeft;

			categoriesScroll.addEventListener('mousedown', (e) => {
				isDown = true;
				startX = e.pageX - categoriesScroll.offsetLeft;
				scrollLeft = categoriesScroll.scrollLeft;
			});

			categoriesScroll.addEventListener('mouseleave', () => {
				isDown = false;
			});

			categoriesScroll.addEventListener('mouseup', () => {
				isDown = false;
			});

			categoriesScroll.addEventListener('mousemove', (e) => {
				if (!isDown) return;
				e.preventDefault();
				const x = e.pageX - categoriesScroll.offsetLeft;
				const walk = (x - startX) * 2;
				categoriesScroll.scrollLeft = scrollLeft - walk;
			});
		}
	})();
</script>
