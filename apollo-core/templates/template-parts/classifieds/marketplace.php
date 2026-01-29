<?php

declare(strict_types=1);
/**
 * Classifieds / Marketplace
 * File: template-parts/classifieds/marketplace.php
 * REST: GET /anuncios, GET /anuncio/{id}, POST /anuncio/add
 */

$page       = max( 1, (int) ( $_GET['pg'] ?? 1 ) );
$category   = sanitize_text_field( $_GET['cat'] ?? '' );
$search     = sanitize_text_field( $_GET['s'] ?? '' );
$ads        = apollo_get_classifieds(
	array(
		'per_page' => 24,
		'page'     => $page,
		'category' => $category,
		'search'   => $search,
	)
);
$categories = get_terms(
	array(
		'taxonomy'   => 'classified_domain',
		'hide_empty' => false,
	)
);
$user_id    = get_current_user_id();
?>

<div class="apollo-marketplace">

	<div class="marketplace-header">
		<h2>Classificados</h2>
		<?php if ( $user_id ) : ?>
		<a href="<?php echo home_url( '/anunciar' ); ?>" class="btn btn-primary">
			<i class="ri-add-line"></i> Anunciar
		</a>
		<?php endif; ?>
	</div>

	<div class="filters-bar">
		<form class="search-form" method="get">
			<div class="search-input-wrapper">
				<i class="ri-search-line"></i>
				<label for="classifieds-search" class="sr-only">Buscar anúncios</label>
				<input type="text" id="classifieds-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Buscar...', 'apollo' ); ?>" title="Buscar anúncios">
			</div>
			<label for="classifieds-category" class="sr-only">Filtrar por categoria</label>
			<select id="classifieds-category" name="cat" class="filter-select" title="Filtrar por categoria">
				<option value="">Todas categorias</option>
				<?php foreach ( $categories as $cat ) : ?>
				<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
					<?php echo esc_html( $cat->name ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="btn btn-outline" title="Filtrar anúncios">Filtrar</button>
		</form>
	</div>

	<div class="ads-grid accommodations-grid">
		<?php if ( ! empty( $ads ) ) : ?>
			<?php foreach ( $ads as $ad ) : ?>
				<?php apollo_classified_card( $ad->ID, array( 'context' => 'grid', 'show_views' => true, 'show_created' => true ) ); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="empty-state col-span-full">
				<i class="ri-store-line"></i>
				<p>Nenhum anúncio encontrado.</p>
			</div>
		<?php endif; ?>
	</div>

	<nav class="pagination">
		<?php if ( $page > 1 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page - 1 ); ?>" class="btn btn-outline">&larr; Anterior</a>
		<?php endif; ?>
		<?php if ( count( $ads ) >= 24 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page + 1 ); ?>" class="btn btn-outline">Próximo &rarr;</a>
		<?php endif; ?>
	</nav>

</div>
<style>
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
</style>
<script src="https://cdn.apollo.rio.br/"></script>
