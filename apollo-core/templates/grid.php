<?php
/**
 * Suppliers Grid
 * File: template-parts/suppliers/grid.php
 */

$suppliers = apollo_get_suppliers();

if ( ! empty( $suppliers ) ) :
	foreach ( $suppliers as $supplier ) :
		$logo     = get_post_meta( $supplier->ID, '_supplier_logo', true );
		$category = get_the_terms( $supplier->ID, 'apollo_supplier_category' );
		$tags     = get_the_terms( $supplier->ID, 'apollo_supplier_tag' );
		$rating   = get_post_meta( $supplier->ID, '_supplier_rating', true ) ?: '5.0';
		$verified = get_post_meta( $supplier->ID, '_supplier_verified', true );
		?>
<div class="ap-supplier-card" data-id="<?php echo $supplier->ID; ?>">
	<div class="ap-supplier-logo-wrapper">
		<img src="<?php echo esc_url( $logo ?: 'https://ui-avatars.com/api/?name=' . urlencode( $supplier->post_title ) ); ?>"
			class="ap-supplier-logo"
			alt="<?php echo esc_attr( $supplier->post_title ); ?>">
		<?php if ( $verified ) : ?>
		<div class="ap-verified-badge"><i class="ri-check-line"></i></div>
		<?php endif; ?>
	</div>

	<div class="ap-supplier-info">
		<h3 class="ap-supplier-name"><?php echo esc_html( $supplier->post_title ); ?></h3>

		<div class="ap-supplier-meta">
			<?php if ( $category && ! is_wp_error( $category ) ) : ?>
			<span class="ap-supplier-category"><?php echo esc_html( $category[0]->name ); ?></span>
			<span style="color: #cbd5e1;">•</span>
			<?php endif; ?>
			<div class="ap-supplier-rating">
				<i class="ri-star-fill ap-star"></i>
				<span class="ap-rating-value"><?php echo esc_html( $rating ); ?></span>
			</div>
		</div>

		<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
		<div class="ap-supplier-tags">
			<?php foreach ( array_slice( $tags, 0, 2 ) as $tag ) : ?>
			<span class="ap-tag"><?php echo esc_html( $tag->name ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="ap-supplier-arrow"><i class="ri-arrow-right-s-line"></i></div>
</div>
		<?php
	endforeach;
else :
	echo '<p class="ap-no-results">Nenhum fornecedor encontrado.</p>';
endif;
?>
