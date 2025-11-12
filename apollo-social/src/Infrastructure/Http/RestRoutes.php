<?php
namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\Http\Controllers\GroupsController;
use Apollo\Infrastructure\Http\Controllers\MembershipsController;
use Apollo\Infrastructure\Http\Controllers\ClassifiedsController;
use Apollo\Infrastructure\Http\Controllers\UsersController;
use Apollo\Infrastructure\Adapters\WPAdvertsAdapter;

/**
 * REST API Routes Registration
 */
class RestRoutes
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }
    
    /**
     * Register all REST API routes
     */
    public function registerRoutes(): void
    {
        // Groups routes
        register_rest_route('apollo/v1', '/groups', [
            'methods' => 'GET',
            'callback' => [new GroupsController(), 'index'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/groups', [
            'methods' => 'POST',
            'callback' => [new GroupsController(), 'create'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/join', [
            'methods' => 'POST',
            'callback' => [new GroupsController(), 'join'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/invite', [
            'methods' => 'POST',
            'callback' => [new GroupsController(), 'invite'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/groups/(?P<id>\d+)/approve-invite', [
            'methods' => 'POST',
            'callback' => [new GroupsController(), 'approveInvite'],
            'permission_callback' => '__return_true'
        ]);
        
        // Unions routes
        register_rest_route('apollo/v1', '/unions', [
            'methods' => 'GET',
            'callback' => [new MembershipsController(), 'index'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/unions/(?P<id>\d+)/toggle-badges', [
            'methods' => 'POST',
            'callback' => [new MembershipsController(), 'toggleBadges'],
            'permission_callback' => '__return_true'
        ]);
        
        // Classifieds routes (WPAdverts integration)
        register_rest_route('apollo/v1', '/classifieds', [
            'methods' => 'GET',
            'callback' => [$this, 'restGetClassifieds'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('apollo/v1', '/classifieds/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'restGetClassified'],
            'permission_callback' => '__return_true'
        ]);
        
        // Keep existing ClassifiedsController for backward compatibility
        register_rest_route('apollo/v1', '/classifieds', [
            'methods' => 'POST',
            'callback' => [new ClassifiedsController(), 'create'],
            'permission_callback' => '__return_true'
        ]);
        
        // Users routes
        register_rest_route('apollo/v1', '/users/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [new UsersController(), 'show'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * REST: Get classifieds list (WPAdverts)
     */
    public function restGetClassifieds(\WP_REST_Request $request): \WP_REST_Response
    {
        $per_page = intval($request->get_param('per_page')) ?: 10;
        $page = intval($request->get_param('page')) ?: 1;
        $search = sanitize_text_field($request->get_param('search') ?: '');
        
        $args = [
            'posts_per_page' => $per_page,
            'paged' => $page,
        ];
        
        if ($search) {
            $args['s'] = $search;
        }
        
        $result = WPAdvertsAdapter::listAds($args);
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => $result
        ], 200);
    }
    
    /**
     * REST: Get single classified (WPAdverts)
     */
    public function restGetClassified(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = intval($request->get_param('id'));
        
        if (!$id) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Invalid ad ID'
            ], 400);
        }
        
        $ad = WPAdvertsAdapter::getAd($id);
        
        if (!$ad) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Ad not found'
            ], 404);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => $ad
        ], 200);
    }
}