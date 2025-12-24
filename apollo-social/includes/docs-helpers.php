<?php

/**
 * Apollo Documents - Helper Functions
 *
 * Wrapper functions for document operations.
 * Provides compatibility with aprio_* naming convention.
 *
 * @package Apollo_Social
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render print view for a document
 *
 * Wrapper for DocumentsPrintView::render().
 * Provides aprio_* naming convention compatibility.
 *
 * @param int $doc_id Document post ID.
 * @return string Full HTML string for print/PDF.
 */
function aprio_docs_render_print_view( int $doc_id ): string {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsPrintView' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsPrintView.php';
	}

	return \Apollo\Modules\Documents\DocumentsPrintView::render( $doc_id );
}

/**
 * Generate PDF for a document
 *
 * Wrapper for DocumentsPdfService::generate_pdf().
 *
 * @param int $doc_id Document post ID.
 * @return array|WP_Error Array with success data or WP_Error.
 */
function aprio_docs_generate_pdf( int $doc_id ) {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsPdfService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsPdfService.php';
	}

	return \Apollo\Modules\Documents\DocumentsPdfService::generate_pdf( $doc_id );
}

/**
 * Get PDF URL for a document
 *
 * @param int $doc_id Document post ID.
 * @return string|null PDF URL or null if not generated.
 */
function aprio_docs_get_pdf_url( int $doc_id ): ?string {
	if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsPdfService' ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Modules/Documents/DocumentsPdfService.php';
	}

	return \Apollo\Modules\Documents\DocumentsPdfService::get_pdf_url( $doc_id );
}
