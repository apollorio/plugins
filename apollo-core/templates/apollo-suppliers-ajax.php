<?php
/**
 * Apollo Suppliers AJAX & Enqueue
 * File: inc/apollo-suppliers-ajax.php
 */

// AJAX: Search Suppliers
add_action( 'wp_ajax_apollo_search_suppliers', 'apollo_ajax_search_suppliers' );

function apollo_ajax_search_suppliers() {
	check_ajax_referer( 'apollo_suppliers_nonce', 'nonce' );

	$term     = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'all';

	$suppliers = apollo_search_suppliers( $term, $category );

	ob_start();

	if ( ! empty( $suppliers ) ) :
		foreach ( $suppliers as $supplier ) :
			$logo     = get_post_meta( $supplier->ID, 'supplier_logo', true );
		$cat      = get_the_terms( $supplier->ID, 'apollo_supplier_category' );
			$tags     = get_the_terms( $supplier->ID, 'apollo_supplier_tag' );
			$rating   = get_post_meta( $supplier->ID, '_supplier_rating', true ) ?: '5.0';
			$verified = get_post_meta( $supplier->ID, '_supplier_verified', true );
			?>
<div class="ap-supplier-card" data-id="<?php echo $supplier->ID; ?>">
	<div class="ap-supplier-logo-wrapper">
		<img src="<?php echo esc_url( $logo ?: 'https://ui-avatars.com/api/?name=' . urlencode( $supplier->post_title ) ); ?>"
			class="ap-supplier-logo">
			<?php if ( $verified ) : ?>
		<div class="ap-verified-badge"><i class="ri-check-line"></i></div>
		<?php endif; ?>
	</div>
	<div class="ap-supplier-info">
		<h3 class="ap-supplier-name"><?php echo esc_html( $supplier->post_title ); ?></h3>
		<div class="ap-supplier-meta">
			<?php if ( $cat && ! is_wp_error( $cat ) ) : ?>
			<span class="ap-supplier-category"><?php echo esc_html( $cat[0]->name ); ?></span>
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

	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'  => $html,
			'count' => count( $suppliers ),
		)
	);
}

// Enqueue Assets
add_action( 'wp_enqueue_scripts', 'apollo_enqueue_suppliers_assets' );

function apollo_enqueue_suppliers_assets() {
	if ( is_page_template( 'page-suppliers-catalog.php' ) ) {
		wp_enqueue_style(
			'apollo-suppliers',
			get_template_directory_uri() . '/assets/css/apollo-suppliers.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'apollo-suppliers',
			get_template_directory_uri() . '/assets/js/apollo-suppliers.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'apollo-suppliers',
			'apolloSuppliersData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'apollo_suppliers_nonce' ),
				'supplierUrl' => home_url( '/supplier/' ),
			)
		);
	}
}
