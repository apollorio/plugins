<?php

namespace Apollo\Modules\UserPages;

defined('ABSPATH') || exit;

/**
 * Service provider responsible for registering the user page infrastructure.
 *
 * @category ApolloSocial
 * @package  ApolloSocial\UserPages
 * @author   Apollo Platform <tech@apollo.rio.br>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://apollo.rio.br
 */
class UserPagesServiceProvider
{
    /**
     * Register WordPress hooks for the module.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('init', [UserPageRegistrar::class, 'registerPostType']);
        add_action('init', [UserPageRegistrar::class, 'registerRewriteRules'], 11);
        add_filter('query_vars', [UserPageRegistrar::class, 'registerQueryVar']);
        add_action(
            'pre_get_posts',
            [UserPageRouter::class, 'handleUserPageRequest']
        );
        add_filter(
            'template_include',
            [UserPageRouter::class, 'maybeUsePluginTemplate']
        );
    }

    /**
     * Callback executed on plugin activation so rewrite rules are flushed once.
     *
     * @return void
     */
    public static function activate(): void
    {
        UserPageRegistrar::registerPostType();
        UserPageRegistrar::registerRewriteRules();
        flush_rewrite_rules(false);
    }
}
