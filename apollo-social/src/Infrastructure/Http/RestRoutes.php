<?php
namespace Apollo\Infrastructure\Http;

use Apollo\Infrastructure\Http\Controllers\GroupsController;
use Apollo\Infrastructure\Http\Controllers\MembershipsController;
use Apollo\Infrastructure\Http\Controllers\ClassifiedsController;
use Apollo\Infrastructure\Http\Controllers\UsersController;

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
        
        // Classifieds routes
        register_rest_route('apollo/v1', '/classifieds', [
            'methods' => 'GET',
            'callback' => [new ClassifiedsController(), 'index'],
            'permission_callback' => '__return_true'
        ]);
        
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
}