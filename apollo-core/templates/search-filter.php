<?php
/**
 * Suppliers Search & Filter
 * File: template-parts/suppliers/search-filter.php
 */

$categories = apollo_get_supplier_categories();
?>

<div class="ap-search-bar">
	<div class="ap-search-input-wrapper">
		<i class="ri-search-2-line ap-search-icon"></i>
		<input type="text"
				id="ap-search-input"
				placeholder="<?php esc_attr_e( 'Buscar nome, equipamento ou serviço...', 'apollo' ); ?>"
				class="ap-search-input">
	</div>

	<div class="ap-filter-chips">
		<button class="ap-filter-chip ap-active" data-cat="all">Todos</button>
		<?php foreach ( $categories as $cat ) : ?>
		<button class="ap-filter-chip" data-cat="<?php echo esc_attr( $cat['slug'] ); ?>">
			<?php echo esc_html( $cat['name'] ); ?>
		</button>
		<?php endforeach; ?>
	</div>
</div>
