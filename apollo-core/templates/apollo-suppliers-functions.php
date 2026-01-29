<?php
/**
 * Apollo Suppliers Functions
 * File: inc/apollo-suppliers-functions.php
 */

/**
 * Get all suppliers
 */
function apollo_get_suppliers( $args = array() ) {
	$defaults = array(
		'post_type'      => 'apollo_supplier',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$args  = wp_parse_args( $args, $defaults );
	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Get supplier categories
 */
function apollo_get_supplier_categories() {
	return array(
		array(
			'slug' => 'sound',
			'name' => 'Som',
		),
		array(
			'slug' => 'light',
			'name' => 'Luz',
		),
		array(
			'slug' => 'visuals',
			'name' => 'Visuals',
		),
		array(
			'slug' => 'security',
			'name' => 'Segurança',
		),
		array(
			'slug' => 'bar',
			'name' => 'Bar',
		),
	);
}

/**
 * Search suppliers
 */
function apollo_search_suppliers( $search_term, $category = 'all' ) {
	$args = array(
		'post_type'      => 'apollo_supplier',
		'posts_per_page' => -1,
		's'              => $search_term,
	);

	if ( $category !== 'all' ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'apollo_supplier_category',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	$query = new WP_Query( $args );
	return $query->posts;
}

/**
 * Get suppliers by category
 */
function apollo_get_suppliers_by_category( $category ) {
	return get_posts(
		array(
			'post_type'      => 'apollo_supplier',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'apollo_supplier_category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			),
		)
	);
}

/**
 * Register supplier post type - DEPRECATED: Use apollo_supplier from apollo-social
 * This function is kept for backward compatibility but should not register the CPT
 */
function apollo_register_supplier_post_type() {
	// DEPRECATED: CPT 'supplier' is replaced by 'apollo_supplier' from apollo-social
	// Do not register here to avoid conflicts
	// The apollo-social plugin handles the registration of 'apollo_supplier'
}
// add_action( 'init', 'apollo_register_supplier_post_type' );
