<?php
/**
 * Feed Page - Full Page with Layout Base
 *
 * Wrapper que usa layout-base.php e inclui feed.php no MAIN
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare content callback
$plugin_dir       = defined( 'APOLLO_SOCIAL_PLUGIN_DIR' ) ? APOLLO_SOCIAL_PLUGIN_DIR : dirname( __DIR__ );
$content_template = $plugin_dir . '/templates/feed/feed.php';

// Include layout base
// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
require $plugin_dir . '/templates/layout-base.php';
