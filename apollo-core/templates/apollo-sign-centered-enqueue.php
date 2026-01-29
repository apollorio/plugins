<?php
/**
 * Enqueue Sign Centered Assets
 * File: inc/apollo-sign-centered-enqueue.php
 */

add_action( 'wp_enqueue_scripts', 'apollo_enqueue_sign_centered_assets' );

function apollo_enqueue_sign_centered_assets() {
	if ( is_page_template( 'page-sign-centered.php' ) ) {
		wp_enqueue_style(
			'apollo-sign-centered',
			get_template_directory_uri() . '/assets/css/apollo-sign-centered.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'apollo-sign-centered',
			get_template_directory_uri() . '/assets/js/apollo-sign-centered.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'apollo-sign-centered',
			'apolloSignData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_doc_nonce' ),
				'homeUrl' => home_url(),
			)
		);
	}
}
