<?php
/**
 * Apollo Events Manager - Cache helpers.
 */

if (!defined('ABSPATH')) {
	return;
}

if (!function_exists('apollo_cache_flush_group')) {
    /**
     * Flush a specific Apollo cache group.
     *
     * Falls back to a version bump strategy when object cache does not support
     * group-level flushing.
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
}

if (!function_exists('aem_events_transient_key')) {
    /**
     * Identifier used to cache upcoming events listings.
     */
    function aem_events_transient_key()
    {
        return 'apollo_events:list:futuro';
    }
}

if (!function_exists('aem_events_flush_cache')) {
    /**
     * Remove cached listings whenever an event changes.
     */
    function aem_events_flush_cache()
    {
        delete_transient(aem_events_transient_key());
        apollo_cache_flush_group('apollo_events');
    }
}

if (!function_exists('aem_handle_event_cache_invalidation')) {
    /**
     * Hooked into post updates to invalidate the event cache immediately.
     */
    function aem_handle_event_cache_invalidation($post_id, $post)
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (!$post || $post->post_type !== 'event_listing') {
            return;
        }

        aem_events_flush_cache();
    }

    add_action('save_post_event_listing', 'aem_handle_event_cache_invalidation', 20, 2);
    add_action('before_delete_post', function ($post_id) {
        if (get_post_type($post_id) === 'event_listing') {
            aem_events_flush_cache();
        }
    });
}
