<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Assets manager
 *
 * Manages CSS/JS loading for Canvas Mode and plugin features.
 */
class AssetsManager
{
    private $config;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load Canvas configuration
     */
    private function loadConfig()
    {
        $config_file = APOLLO_SOCIAL_PLUGIN_DIR . 'config/canvas.php';
        if (file_exists($config_file)) {
            $this->config = require $config_file;
        } else {
            $this->config = [];
        }
    }

    /**
     * Check if current route is a Canvas/Apollo route
     */
    private function isApolloRoute(): bool
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $apollo_routes = [
            '/a/',
            '/comunidade/',
            '/nucleo/',
            '/season/',
            '/membership',
            '/uniao/',
            '/anuncio/'
        ];
        
        foreach ($apollo_routes as $route) {
            if (strpos($request_uri, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Enqueue Canvas Mode assets
     */
    public function enqueueCanvas()
    {
        if (!empty($this->config['enqueue_canvas_css'])) {
            $this->enqueueCanvasCSS();
        }

        if (!empty($this->config['enqueue_canvas_js'])) {
            $this->enqueueCanvasJS();
        }

        // Inject Plausible Analytics if on Apollo routes
        if ($this->isApolloRoute()) {
            $this->injectPlausibleAnalytics();
        }
    }

    /**
     * Enqueue Canvas CSS
     */
    private function enqueueCanvasCSS()
    {
        $css_file = APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/canvas-mode.css';
        $css_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/css/canvas-mode.css';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'apollo-canvas-mode',
                $css_file,
                [],
                APOLLO_SOCIAL_VERSION
            );
            
            // Add inline styles to override theme
            $inline_css = '
                body.apollo-canvas {
                    margin: 0 !important;
                    padding: 0 !important;
                }
            ';
            wp_add_inline_style('apollo-canvas-mode', $inline_css);
        }

        // Also enqueue modules CSS
        $modules_css = APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/modules.css';
        $modules_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/css/modules.css';
        
        if (file_exists($modules_path)) {
            wp_enqueue_style(
                'apollo-modules',
                $modules_css,
                ['apollo-canvas'],
                APOLLO_SOCIAL_VERSION
            );
        }
    }

    /**
     * Enqueue Canvas JS
     */
    private function enqueueCanvasJS()
    {
        $js_file = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/canvas.js';
        $js_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/canvas.js';
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'apollo-canvas',
                $js_file,
                ['jquery'],
                APOLLO_SOCIAL_VERSION,
                true
            );

            // Add localized data
            wp_localize_script('apollo-canvas', 'apolloCanvas', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('apollo_canvas'),
                'pluginUrl' => APOLLO_SOCIAL_PLUGIN_URL,
            ]);
        }
    }

    /**
     * Enqueue module assets
     */
    public function enqueueModule($module)
    {
        // Module-specific CSS
        $css_file = APOLLO_SOCIAL_PLUGIN_URL . "assets/css/modules/{$module}.css";
        $css_path = APOLLO_SOCIAL_PLUGIN_DIR . "assets/css/modules/{$module}.css";
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                "apollo-module-{$module}",
                $css_file,
                ['apollo-canvas'],
                APOLLO_SOCIAL_VERSION
            );
        }

        // Module-specific JS
        $js_file = APOLLO_SOCIAL_PLUGIN_URL . "assets/js/modules/{$module}.js";
        $js_path = APOLLO_SOCIAL_PLUGIN_DIR . "assets/js/modules/{$module}.js";
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                "apollo-module-{$module}",
                $js_file,
                ['apollo-canvas'],
                APOLLO_SOCIAL_VERSION,
                true
            );
        }
    }

    /**
     * Block theme assets (called by OutputGuards)
     */
    public function blockThemeAssets()
    {
        if (!empty($this->config['block_theme_css'])) {
            add_action('wp_print_styles', [$this, 'dequeueThemeStyles'], 100);
        }

        if (!empty($this->config['block_theme_js'])) {
            add_action('wp_print_scripts', [$this, 'dequeueThemeScripts'], 100);
        }
    }

    /**
     * Inject Plausible Analytics script (Script-Only Mode)
     */
    private function injectPlausibleAnalytics()
    {
        $analytics_config = config('analytics');
        
        // Check if analytics enabled and Canvas injection enabled
        if (!($analytics_config['enabled'] ?? false) || 
            $analytics_config['driver'] !== 'plausible' ||
            !($analytics_config['inject_on_canvas'] ?? false)) {
            return;
        }

        $script_config = $analytics_config['script_config'] ?? [];
        $domain = $script_config['domain'] ?? '';
        $script_url = $script_config['script_url'] ?? 'https://plausible.io/js/plausible.js';
        
        if (empty($domain)) {
            return;
        }

        // Simple script injection without WordPress enqueue system
        echo '<script defer data-domain="' . \esc_attr($domain) . '" src="' . \esc_url($script_url) . '"></script>' . "\n";

        // Add Apollo Analytics helper functions
        $this->addApolloAnalyticsJS();
    }

    /**
     * Add Apollo-specific analytics JavaScript functions
     */
    private function addApolloAnalyticsJS()
    {
        $analytics_config = config('analytics');
        $events_config = $analytics_config['events'] ?? [];
        
        $js_code = "
        <script>
        window.apolloAnalytics = {
            track: function(eventName, props) {
                if (typeof plausible !== 'undefined') {
                    plausible(eventName, { props: props || {} });
                }
                console.log('Apollo Analytics:', eventName, props);
            },
            
            trackGroupView: function(groupType, groupSlug) {
                this.track('group_view', {
                    group_type: groupType,
                    group_slug: groupSlug
                });
            },
            
            trackGroupJoin: function(groupType, groupSlug) {
                this.track('group_join', {
                    group_type: groupType,
                    group_slug: groupSlug
                });
            },
            
            trackInviteSent: function(groupType, inviteType) {
                this.track('invite_sent', {
                    group_type: groupType,
                    invite_type: inviteType
                });
            },
            
            trackInviteApproved: function(groupType, inviteType) {
                this.track('invite_approved', {
                    group_type: groupType,
                    invite_type: inviteType
                });
            },
            
            trackUnionBadgesToggle: function(action) {
                this.track('union_badges_toggle', {
                    action: action // 'on' or 'off'
                });
            },
            
            trackChatMessage: function(groupType) {
                this.track('chat_message_sent', {
                    group_type: groupType
                });
            },
            
            trackAdView: function(adId, category, groupType) {
                this.track('ad_view', {
                    ad_id: adId,
                    category: category,
                    group_type: groupType
                });
            },
            
            trackAdCreate: function(category, groupType) {
                this.track('ad_create', {
                    category: category,
                    group_type: groupType
                });
            },
            
            trackAdPublish: function(category, groupType) {
                this.track('ad_publish', {
                    category: category,
                    group_type: groupType
                });
            },
            
            trackAdReject: function(category, reason) {
                this.track('ad_reject', {
                    category: category,
                    reason: reason
                });
            },
            
            trackAdCreateInvalidSeason: function(attemptedSeason, userSeason) {
                this.track('ad_create_invalid_season', {
                    attempted_season: attemptedSeason,
                    user_season: userSeason
                });
            },
            
            trackEventView: function(eventId, seasonSlug, groupType) {
                this.track('event_view', {
                    event_id: eventId,
                    season_slug: seasonSlug,
                    group_type: groupType
                });
            },
            
            trackEventFilterApplied: function(filterType, seasonSlug) {
                this.track('event_filter_applied', {
                    filter_type: filterType,
                    season_slug: seasonSlug
                });
            }
        };
        
        // Auto-track page views with additional context
        document.addEventListener('DOMContentLoaded', function() {
            // Get current route context
            var path = window.location.pathname;
            var apolloRoutes = ['/a/', '/comunidade/', '/nucleo/', '/season/', '/membership', '/uniao/', '/anuncio/'];
            
            for (var i = 0; i < apolloRoutes.length; i++) {
                if (path.indexOf(apolloRoutes[i]) !== -1) {
                    // Extract context from URL and track specific events
                    if (path.indexOf('/comunidade/') !== -1) {
                        var slug = path.split('/comunidade/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('comunidade', slug);
                    }
                    else if (path.indexOf('/nucleo/') !== -1) {
                        var slug = path.split('/nucleo/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('nucleo', slug);
                    }
                    else if (path.indexOf('/season/') !== -1) {
                        var slug = path.split('/season/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('season', slug);
                    }
                    break;
                }
            }
        });
        </script>
        ";
        
        echo $js_code;
    }

    /**
     * Dequeue theme styles (implementation needed)
     */
    public function dequeueThemeStyles()
    {
        // Implementation for blocking theme styles
    }

    /**
     * Dequeue theme scripts (implementation needed) 
     */
    public function dequeueThemeScripts()
    {
        // Implementation for blocking theme scripts
    }
}