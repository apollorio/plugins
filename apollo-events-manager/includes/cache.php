<?php
/**
 * Apollo Events Manager - Cache helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flush a specific Apollo cache group.
 *
 * @param string $group Cache group name.
 *
 * @return void
 */
function apollo_cache_flush_group($group)
{
    if ($group === '') {
        return;
    }

    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group($group);
        return;
    }

    $version_key = 'apollo_cache_version';
    $version = (int) wp_cache_get($version_key, $group);
    wp_cache_set($version_key, $version + 1, $group);
}
