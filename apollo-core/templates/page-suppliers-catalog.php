<?php
/**
 * Template Name: Suppliers Catalog
 * Description: Cena::rio suppliers directory
 */

if ( ! current_user_can( 'cena-rio' ) ) {
	wp_redirect( home_url() );
	exit;
}

get_header( 'apollo' );
?>

<div class="ap-layout">
	<main class="ap-main-content">
		<div class="ap-breadcrumb">
			<div class="ap-breadcrumb-inner">
				<span class="ap-breadcrumb-active">Cena::rio</span>
				<span>/</span>
				<span>Fornecedores</span>
			</div>
		</div>
		
		<div class="ap-content-wrapper">
			<div class="ap-main-column">
				<?php get_template_part( 'template-parts/suppliers/search-filter' ); ?>
				
				<div class="ap-results-header">
					<h2 class="ap-results-title">Resultados</h2>
					<span id="ap-count-label" class="ap-results-count">Carregando</span>
				</div>
				
				<div id="ap-suppliers-grid" class="ap-suppliers-grid">
					<?php get_template_part( 'template-parts/suppliers/grid' ); ?>
				</div>
			</div>
		</div>
	</main>
</div>

<?php get_footer( 'apollo' ); ?>
