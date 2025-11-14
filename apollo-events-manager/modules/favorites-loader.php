<?php
/**
 * Apollo Events Manager - Favorites Module Loader
 * 
 * Loads the favorites/bookmarks module if available
 */

if (!defined('ABSPATH')) exit;

$favorites_file = __DIR__ . '/favorites/wpem-bookmarks.php';

if (file_exists($favorites_file)) {
    require_once $favorites_file;
    
    if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
        error_log('Apollo Events Manager: Favorites module loaded');
    }
}




