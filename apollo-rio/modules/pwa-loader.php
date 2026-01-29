<?php

// phpcs:ignoreFile
/**
 * Apollo::Rio - PWA Module Loader
 *
 * Loads the Progressive Web App module if available
 */

if (! defined('ABSPATH')) {
    exit;
}

$pwa_file = __DIR__ . '/pwa/pwa.php';

if (file_exists($pwa_file)) {
    require_once $pwa_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Apollo::Rio: Missing PWA module file: ' . esc_html($pwa_file));
}
