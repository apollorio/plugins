<?php
/**
 * P0-7: DJ and Local Creation REST API Endpoint
 * 
 * Allows front-end creation of DJs and Locals via REST API.
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) exit;

class DJLocalEndpoint
{
    /**
     * Register REST routes
     */
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register routes
     */
    public function registerRoutes(): void
    {
        // Create DJ
        register_rest_route('apollo/v1', '/dj', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'createDJ'],
            'permission_callback' => [$this, 'permissionCheck'],
            'args' => [
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => __('DJ name.', 'apollo-social'),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'instagram' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('Instagram handle.', 'apollo-social'),
                ],
                'soundcloud' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('SoundCloud URL.', 'apollo-social'),
                ],
            ],
        ]);

        // Search DJs
        register_rest_route('apollo/v1', '/dj/search', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'searchDJs'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('Search term.', 'apollo-social'),
                ],
            ],
        ]);

        // Create Local
        register_rest_route('apollo/v1', '/local', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'createLocal'],
            'permission_callback' => [$this, 'permissionCheck'],
            'args' => [
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => __('Local name.', 'apollo-social'),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'address' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('Address.', 'apollo-social'),
                ],
                'city' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('City.', 'apollo-social'),
                ],
            ],
        ]);

        // Search Locals
        register_rest_route('apollo/v1', '/local/search', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'searchLocals'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('Search term.', 'apollo-social'),
                ],
            ],
        ]);
    }

    /**
     * Permission check
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to create DJs or Locals.', 'apollo-social'),
                ['status' => 401]
            );
        }

        // Allow any logged-in user to create DJs/Locals (can be moderated later)
        return true;
    }

    /**
     * P0-7: Create DJ
     */
    public function createDJ(WP_REST_Request $request): WP_REST_Response
    {
        if (!post_type_exists('event_dj')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('DJ post type not available.', 'apollo-social'),
            ], 400);
        }

        $name = $request->get_param('name');
        $instagram = $request->get_param('instagram');
        $soundcloud = $request->get_param('soundcloud');

        if (empty($name)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('DJ name is required.', 'apollo-social'),
            ], 400);
        }

        // Check for duplicates (case-insensitive)
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        $existing = get_posts([
            'post_type' => 'event_dj',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        foreach ($existing as $dj) {
            $existing_title = mb_strtolower(trim($dj->post_title), 'UTF-8');
            $existing_meta = mb_strtolower(trim(get_post_meta($dj->ID, '_dj_name', true)), 'UTF-8');

            if ($existing_title === $normalized || $existing_meta === $normalized) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => sprintf(__('DJ "%s" already exists.', 'apollo-social'), $dj->post_title),
                    'existing_id' => $dj->ID,
                ], 409);
            }
        }

        // Create DJ post
        $dj_id = wp_insert_post([
            'post_type' => 'event_dj',
            'post_title' => $name,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($dj_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Error creating DJ.', 'apollo-social'),
            ], 500);
        }

        // Save meta
        update_post_meta($dj_id, '_dj_name', $name);
        if ($instagram) {
            update_post_meta($dj_id, '_dj_instagram', sanitize_text_field($instagram));
        }
        if ($soundcloud) {
            update_post_meta($dj_id, '_dj_soundcloud', esc_url_raw($soundcloud));
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $dj_id,
                'name' => $name,
                'slug' => get_post($dj_id)->post_name,
            ],
        ], 201);
    }

    /**
     * P0-7: Search DJs
     */
    public function searchDJs(WP_REST_Request $request): WP_REST_Response
    {
        if (!post_type_exists('event_dj')) {
            return new WP_REST_Response([
                'success' => false,
                'data' => [],
            ], 200);
        }

        $search = $request->get_param('search');
        $args = [
            'post_type' => 'event_dj',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if ($search) {
            $args['s'] = $search;
        }

        $djs = get_posts($args);
        $results = [];

        foreach ($djs as $dj) {
            $results[] = [
                'id' => $dj->ID,
                'name' => $dj->post_title,
                'slug' => $dj->post_name,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results,
        ], 200);
    }

    /**
     * P0-7: Create Local
     */
    public function createLocal(WP_REST_Request $request): WP_REST_Response
    {
        if (!post_type_exists('event_local')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Local post type not available.', 'apollo-social'),
            ], 400);
        }

        $name = $request->get_param('name');
        $address = $request->get_param('address');
        $city = $request->get_param('city');

        if (empty($name)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Local name is required.', 'apollo-social'),
            ], 400);
        }

        // Check for duplicates
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        $existing = get_posts([
            'post_type' => 'event_local',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        foreach ($existing as $local) {
            $existing_title = mb_strtolower(trim($local->post_title), 'UTF-8');
            $existing_meta = mb_strtolower(trim(get_post_meta($local->ID, '_local_name', true)), 'UTF-8');

            if ($existing_title === $normalized || $existing_meta === $normalized) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => sprintf(__('Local "%s" already exists.', 'apollo-social'), $local->post_title),
                    'existing_id' => $local->ID,
                ], 409);
            }
        }

        // Create Local post
        $local_id = wp_insert_post([
            'post_type' => 'event_local',
            'post_title' => $name,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($local_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Error creating Local.', 'apollo-social'),
            ], 500);
        }

        // Save meta
        update_post_meta($local_id, '_local_name', $name);
        if ($address) {
            update_post_meta($local_id, '_local_address', sanitize_text_field($address));
        }
        if ($city) {
            update_post_meta($local_id, '_local_city', sanitize_text_field($city));
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $local_id,
                'name' => $name,
                'slug' => get_post($local_id)->post_name,
            ],
        ], 201);
    }

    /**
     * P0-7: Search Locals
     */
    public function searchLocals(WP_REST_Request $request): WP_REST_Response
    {
        if (!post_type_exists('event_local')) {
            return new WP_REST_Response([
                'success' => false,
                'data' => [],
            ], 200);
        }

        $search = $request->get_param('search');
        $args = [
            'post_type' => 'event_local',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if ($search) {
            $args['s'] = $search;
        }

        $locals = get_posts($args);
        $results = [];

        foreach ($locals as $local) {
            $results[] = [
                'id' => $local->ID,
                'name' => $local->post_title,
                'slug' => $local->post_name,
                'address' => get_post_meta($local->ID, '_local_address', true),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $results,
        ], 200);
    }
}

