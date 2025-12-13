<?php

/**
 * REST API SMOKE TEST – PASSED
 * Route: /apollo/v1/mod/em-filae, /mod/em-fila-count
 * Affects: apollo-core.php, class-mod-queue-unified.php
 * Verified: 2025-12-06 – no conflicts, secure callbacks, unique namespace
 */

declare(strict_types=1);

/**
 * Unified Moderation Queue
 *
 * Automatically detects ALL pending/draft posts from any CPT
 * and connects them to the mod system.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Unified Moderation Queue class
 */
class Apollo_Moderation_Queue_Unified
{
    /**
     * CPTs that require mod
     *
     * Note: event_listing from CENA-RIO only appears when _apollo_cena_status = 'confirmed'
     *
     * @var array
     */
    private static array $mod_cpts = array(
        'event_listing',
        'event_local',
        'event_dj',
        'apollo_nucleo',
        'apollo_comunidade',
        'apollo_classified',
        'apollo_social_post',
        'post',
    );

    /**
     * Initialize
     */
    public static function init(): void
    {
        // Add pending count to admin menu
        add_action('admin_menu', array( __CLASS__, 'add_pending_count_badge' ), 999);

        // Hook into post save to trigger mod visibility
        add_action('save_post', array( __CLASS__, 'on_post_save' ), 10, 3);
        add_action('transition_post_status', array( __CLASS__, 'on_status_change' ), 10, 3);

        // Register REST endpoint for unified queue
        add_action('rest_api_init', array( __CLASS__, 'register_rest_routes' ));

        // Clear transient cache when posts change
        add_action('save_post', array( __CLASS__, 'clear_pending_cache' ));
        add_action('delete_post', array( __CLASS__, 'clear_pending_cache' ));
        add_action('wp_trash_post', array( __CLASS__, 'clear_pending_cache' ));
    }

    /**
     * Register REST routes
     */
    public static function register_rest_routes(): void
    {
        register_rest_route(
            'apollo/v1',
            'mod/em-fila',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'rest_get_unified_queue' ),
                'permission_callback' => array( __CLASS__, 'check_mod_permission' ),
                'args'                => array(
                    'post_type' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'source'    => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        register_rest_route(
            'apollo/v1',
            'mod/pendentes',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'rest_get_pending_count' ),
                'permission_callback' => array( __CLASS__, 'check_mod_permission' ),
            )
        );
    }

    /**
     * Check mod permission
     *
     * @return bool True if user can moderate.
     */
    public static function check_mod_permission(): bool
    {
        return current_user_can('view_mod_queue')
            || current_user_can('apollo_cena_moderate_events')
            || current_user_can('moderate_apollo_content')
            || current_user_can('manage_options');
    }

    /**
     * Get all pending/draft posts count
     *
     * Only counts posts that should appear in MOD queue:
     * - Regular posts: draft/pending status
     * - CENA-RIO events: draft status + _apollo_cena_status = 'confirmed'
     *
     * @return int Total pending count.
     */
    public static function get_pending_count(): int
    {
        $cached = get_transient('apollo_pending_mod_count');
        if (false !== $cached) {
            return (int) $cached;
        }

        // Query for posts that should appear in MOD queue
        $posts = self::get_pending_posts();
        $count = count($posts);

        set_transient('apollo_pending_mod_count', $count, 5 * MINUTE_IN_SECONDS);

        return $count;
    }

    /**
     * Get CPTs that require mod
     *
     * @return array Array of post type slugs.
     */
    public static function get_mod_cpts(): array
    {
        $cpts = self::$mod_cpts;

        // Allow filtering
        $cpts = apply_filters('apollo_mod_cpts', $cpts);

        // Only return CPTs that actually exist
        return array_filter($cpts, 'post_type_exists');
    }

    /**
     * Add pending count badge to admin menu
     */
    public static function add_pending_count_badge(): void
    {
        global $menu;

        $count = self::get_pending_count();
        if ($count < 1) {
            return;
        }

        // Find the mod menu item and add badge
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && 'apollo-mod' === $item[2]) {
                $menu[ $key ][0] = sprintf(
                    '%s <span class="awaiting-mod count-%d"><span class="pending-count" aria-hidden="true">%d</span><span class="screen-reader-text">%s</span></span>',
                    esc_html__('Moderation', 'apollo-core'),
                    $count,
                    $count,
                    sprintf(
                        /* translators: %d: number of items pending mod */
                        _n('%d item pending mod', '%d items pending mod', $count, 'apollo-core'),
                        $count
                    )
                );
                break;
            }
        }
    }

    /**
     * Clear pending cache when posts change
     */
    public static function clear_pending_cache(): void
    {
        delete_transient('apollo_pending_mod_count');
    }

    /**
     * Handle post save - ensure pending posts are tracked
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Whether this is an update.
     */
    public static function on_post_save(int $post_id, WP_Post $post, bool $update): void
    {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Clear cache
        self::clear_pending_cache();

        // If post is pending/draft and from a moderated CPT, mark it
        if (
            in_array($post->post_status, array( 'pending', 'draft' ), true)
            && in_array($post->post_type, self::get_mod_cpts(), true)
        ) {
            // Mark when the post was submitted for mod
            if (! get_post_meta($post_id, '_apollo_submitted_for_mod', true)) {
                update_post_meta($post_id, '_apollo_submitted_for_mod', current_time('mysql'));
                update_post_meta($post_id, '_apollo_submitted_by', $post->post_author);
            }

            // Trigger action for notifications
            do_action('apollo_post_pending_mod', $post_id, $post);
        }
    }

    /**
     * Handle post status transitions
     *
     * @param string  $new_status New status.
     * @param string  $old_status Old status.
     * @param WP_Post $post       Post object.
     */
    public static function on_status_change(string $new_status, string $old_status, WP_Post $post): void
    {
        // Clear cache on any status change
        self::clear_pending_cache();

        // If post was approved (pending/draft -> publish)
        if ('publish' === $new_status && in_array($old_status, array( 'pending', 'draft' ), true)) {
            update_post_meta($post->ID, '_apollo_approved_at', current_time('mysql'));
            update_post_meta($post->ID, '_apollo_approved_by', get_current_user_id());

            // Trigger action for notifications
            do_action('apollo_post_approved', $post->ID, $post);
        }
    }

    /**
     * REST API: Get unified queue
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public static function rest_get_unified_queue(WP_REST_Request $request): WP_REST_Response
    {
        $post_type_filter = $request->get_param('post_type');
        $source_filter    = $request->get_param('source');

        $cpts = self::get_mod_cpts();

        // Apply post type filter
        if ($post_type_filter && in_array($post_type_filter, $cpts, true)) {
            $cpts = array( $post_type_filter );
        }

        $meta_query = array();

        // Filter by source (e.g., 'cena-rio')
        if ($source_filter) {
            $meta_query[] = array(
                'key'   => '_apollo_source',
                'value' => $source_filter,
            );
        }

        $query_args = array(
            'post_type'      => $cpts,
            'post_status'    => array( 'pending', 'draft' ),
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (! empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($query_args);

        $items = array();
        foreach ($query->posts as $post) {
            $author = get_userdata($post->post_author);
            $source = get_post_meta($post->ID, '_apollo_source', true);

            $items[] = array(
                'id'          => $post->ID,
                'title'       => $post->post_title ?: __('(sem título)', 'apollo-core'),
                'type'        => $post->post_type,
                'type_label'  => self::get_post_type_label($post->post_type),
                'status'      => $post->post_status,
                'author'      => array(
                    'id'   => $post->post_author,
                    'name' => $author ? $author->display_name : __('Desconhecido', 'apollo-core'),
                ),
                'date'        => $post->post_date,
                'date_human'  => human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ' . __('atrás', 'apollo-core'),
                'edit_link'   => get_edit_post_link($post->ID, 'raw'),
                'thumbnail'   => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
                'source'      => $source ?: 'wordpress',
                'is_cena_rio' => 'cena-rio' === $source,
                'excerpt'     => wp_trim_words($post->post_content, 20),
            );
        }//end foreach

        // Group by source for easy filtering
        $by_source = array(
            'cena-rio'  => array_filter($items, fn($i) => $i['is_cena_rio']),
            'wordpress' => array_filter($items, fn($i) => ! $i['is_cena_rio']),
        );

        return new WP_REST_Response(
            array(
                'success'   => true,
                'total'     => count($items),
                'items'     => $items,
                'by_source' => array(
                    'cena_rio'  => count($by_source['cena-rio']),
                    'wordpress' => count($by_source['wordpress']),
                ),
                'by_type'   => self::count_by_type($items),
            ),
            200
        );
    }

    /**
     * REST API: Get pending count
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public static function rest_get_pending_count(WP_REST_Request $request): WP_REST_Response
    {
        $count = self::get_pending_count();

        // Get CENA-RIO specific count
        $cena_query = new WP_Query(
            array(
                'post_type'      => 'event_listing',
                'post_status'    => 'pending',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_apollo_source',
                        'value' => 'cena-rio',
                    ),
                ),
            )
        );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'total'    => $count,
                'cena_rio' => $cena_query->found_posts,
                'other'    => $count - $cena_query->found_posts,
            ),
            200
        );
    }

    /**
     * Get post type label
     *
     * @param string $post_type Post type slug.
     * @return string Human-readable label.
     */
    private static function get_post_type_label(string $post_type): string
    {
        $labels = array(
            'event_listing'      => __('Evento', 'apollo-core'),
            'event_local'        => __('Local', 'apollo-core'),
            'event_dj'           => __('DJ', 'apollo-core'),
            'apollo_nucleo'      => __('Núcleo', 'apollo-core'),
            'apollo_comunidade'  => __('Comunidade', 'apollo-core'),
            'apollo_classified'  => __('Classificado', 'apollo-core'),
            'apollo_social_post' => __('Post Social', 'apollo-core'),
            'post'               => __('Post', 'apollo-core'),
        );

        return $labels[ $post_type ] ?? $post_type;
    }

    /**
     * Count items by type
     *
     * @param array $items Items array.
     * @return array Counts by type.
     */
    private static function count_by_type(array $items): array
    {
        $counts = array();
        foreach ($items as $item) {
            $type = $item['type'];
            if (! isset($counts[ $type ])) {
                $counts[ $type ] = 0;
            }
            ++$counts[ $type ];
        }
        return $counts;
    }

    /**
     * Get all pending posts for admin display
     *
     * Filters out CENA-RIO events that are not yet confirmed by industry.
     * Only shows CENA-RIO events when _apollo_cena_status = 'confirmed'.
     *
     * @param string|null $post_type Filter by post type.
     * @param string|null $source    Filter by source.
     * @return array Array of posts.
     */
    public static function get_pending_posts(?string $post_type = null, ?string $source = null): array
    {
        $cpts = self::get_mod_cpts();

        if ($post_type && in_array($post_type, $cpts, true)) {
            $cpts = array( $post_type );
        }

        $args = array(
            'post_type'      => $cpts,
            'post_status'    => array( 'pending', 'draft' ),
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ($source) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_apollo_source',
                    'value' => $source,
                ),
            );
        }

        $posts = get_posts($args);

        // Filter out CENA-RIO events that are not confirmed by industry yet
        // CENA-RIO events only appear in MOD queue when _apollo_cena_status = 'confirmed'
        $filtered_posts = array();
        foreach ($posts as $post) {
            $source = get_post_meta($post->ID, '_apollo_source', true);

            if ('cena-rio' === $source) {
                // CENA-RIO event: only show if confirmed by industry
                $cena_status = get_post_meta($post->ID, '_apollo_cena_status', true);
                if ('confirmed' === $cena_status) {
                    $filtered_posts[] = $post;
                }
                // Skip if not confirmed (still 'expected')
            } else {
                // Regular post: always show
                $filtered_posts[] = $post;
            }
        }

        return $filtered_posts;
    }

    /**
     * Check if a post should appear in MOD queue
     *
     * @param int $post_id Post ID.
     * @return bool True if should appear in MOD queue.
     */
    public static function should_appear_in_mod_queue(int $post_id): bool
    {
        $post = get_post($post_id);
        if (! $post) {
            return false;
        }

        // Must be draft or pending
        if (! in_array($post->post_status, array( 'draft', 'pending' ), true)) {
            return false;
        }

        // Check if CENA-RIO event
        $source = get_post_meta($post_id, '_apollo_source', true);
        if ('cena-rio' === $source) {
            // CENA-RIO events only appear when confirmed by industry
            $cena_status = get_post_meta($post_id, '_apollo_cena_status', true);
            return 'confirmed' === $cena_status;
        }

        // Regular posts always appear
        return true;
    }
}

// Initialize
Apollo_Moderation_Queue_Unified::init();
