<?php
namespace Apollo\Modules\Core;

use Apollo\Infrastructure\PostTypes\SocialPostType;

/**
 * Core Service Provider
 *
 * Registers core functionality like routes, Canvas Mode and base hooks.
 */
class CoreServiceProvider
{
    /**
     * Register core services
     */
    public function register()
    {
        // FASE 2: Registrar CPT de posts sociais
        $social_post_type = new SocialPostType();
        $social_post_type->register();
    }

    /**
     * Boot core services
     */
    public function boot()
    {
        // Setup routes, install output guards, register rewrites
    }
}