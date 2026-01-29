<?php
/**
 * Single Supplier Template - Canvas Mode
 *
 * Displays a single supplier page with full modal open.
 * Redirects to the list with the supplier modal open.
 *
 * @package Apollo\Templates\CenaRio
 * @since   1.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the supplier ID from the global set by the router.
$supplier_id = isset( $GLOBALS['apollo_supplier_id'] ) ? absint( $GLOBALS['apollo_supplier_id'] ) : 0;

if ( 0 === $supplier_id ) {
	wp_safe_redirect( home_url( '/fornece/' ) );
	exit;
}

// Store the ID for the list template to use.
$GLOBALS['apollo_supplier_id'] = $supplier_id;

// Include the list template which will handle the modal auto-open.
require __DIR__ . '/suppliers-list.php';
