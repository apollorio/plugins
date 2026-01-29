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

	/**
	 * External domains that need preconnect hints for performance.
	 *
	 * @var array
	 */
	private static $preconnect_domains = array(
		'https://assets.apollo.rio.br',
		'https://cdn.jsdelivr.net',
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
	);

	public function __construct()
	{
		$this->loadConfig();
		$this->registerPreconnectHints();
	}

	/**
	 * Register preconnect hints for external domains.
	 * Fires early in wp_head for maximum performance benefit.
	 */
	private function registerPreconnectHints(): void
	{
		add_action('wp_head', array($this, 'outputPreconnectHints'), 1);
	}

	/**
	 * Output preconnect link tags for external domains.
	 * NOTE: base.js from CDN now handles all CSS loading automatically.
	 */
	public function outputPreconnectHints(): void
	{
		// Preconnect hints are now handled by Apollo_Base_Assets class
		// to avoid duplicates. This method is kept for compatibility.
		return;
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
			$this->config = array();
		}
	}

	/**
	 * Check if current route is a Canvas/Apollo route
	 */
	private function isApolloRoute(): bool
	{
		$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

		if (empty($request_uri)) {
			return false;
		}

		// FASE 1: Lista completa de rotas Apollo (incluindo documentos)
		$apollo_routes = array(
			'/a/',
			'/comunidade/',
			'/nucleo/',
			'/season/',
			'/membership',
			'/uniao/',
			'/anuncio/',
			'/feed/',
			'/chat/',
			'/painel/',
			'/cena/',
			'/cena-rio/',
			'/eco/',
			'/ecoa/',
			'/id/',
			'/clubber/',
			// FASE 1: Rotas de documentos
			'/doc/',
			'/pla/',
			'/sign/',
			'/documentos/',
			'/enviar/',
		);

		foreach ($apollo_routes as $route) {
			if (\strpos($request_uri, $route) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue Canvas Mode assets
	 * STRONG FILTERING: Only Apollo plugins assets allowed
	 */
	public function enqueueCanvas()
	{
		// Install strong asset filter BEFORE enqueuing
		$this->installApolloOnlyFilter();

		if (! empty($this->config['enqueue_canvas_css'])) {
			$this->enqueueCanvasCSS();
		}

		if (! empty($this->config['enqueue_canvas_js'])) {
			$this->enqueueCanvasJS();
		}

		// Inject Plausible Analytics if on Apollo routes
		if ($this->isApolloRoute()) {
			$this->injectPlausibleAnalytics();
		}
	}

	/**
	 * Install strong filter to allow ONLY Apollo plugins assets
	 */
	private function installApolloOnlyFilter()
	{
		// Filter styles - remove all non-Apollo styles
		add_action('wp_print_styles', array($this, 'filterApolloOnlyStyles'), 999);

		// Filter scripts - remove all non-Apollo scripts
		add_action('wp_print_scripts', array($this, 'filterApolloOnlyScripts'), 999);
		add_action('wp_print_footer_scripts', array($this, 'filterApolloOnlyScripts'), 999);
	}

	/**
	 * Filter styles - keep ONLY Apollo plugins
	 */
	public function filterApolloOnlyStyles()
	{
		global $wp_styles;

		if (! is_object($wp_styles) || ! isset($wp_styles->queue)) {
			return;
		}

		$allowed_handles = array(
			'apollo-uni-css',
			// P0-4: uni.css from assets.apollo.rio.br
			'apollo-canvas-mode',
			'apollo-modules',
			'apollo-feed',
			'apollo-chat',
			'apollo-user-profile',
			'apollo-users-directory',
			'apollo-hold-to-confirm',
			'apollo-dashboard',
			'apollo-cena',
			'apollo-feed-css',
		);

		$allowed_patterns = array(
			'/apollo-',
			'assets.apollo.rio.br',
			'remixicon',
			'cdn.jsdelivr.net/npm/motion',
			// Motion.dev library
		);

		foreach ($wp_styles->queue as $handle) {
			$keep = false;

			// Check if handle is in allowed list
			if (in_array($handle, $allowed_handles)) {
				$keep = true;
			}

			// Check if handle starts with apollo-
			if (\strpos($handle, 'apollo-') === 0) {
				$keep = true;
			}

			// Check if src contains allowed patterns
			if (isset($wp_styles->registered[$handle]) && is_object($wp_styles->registered[$handle])) {
				$src = $wp_styles->registered[$handle]->src ?? '';
				foreach ($allowed_patterns as $pattern) {
					if (\strpos($src, $pattern) !== false) {
						$keep = true;

						break;
					}
				}
			}

			// Remove if not Apollo
			if (! $keep) {
				wp_dequeue_style($handle);
				wp_deregister_style($handle);
			}
		} //end foreach
	}

	/**
	 * Filter scripts - keep ONLY Apollo plugins
	 */
	public function filterApolloOnlyScripts()
	{
		global $wp_scripts;

		if (! is_object($wp_scripts) || ! isset($wp_scripts->queue)) {
			return;
		}

		$allowed_handles = array(
			'apollo-canvas',
			'apollo-feed',
			'apollo-chat',
			'apollo-user-profile',
			'apollo-users-directory',
			'apollo-hold-to-confirm',
			'motion',
		);

		$allowed_patterns = array(
			'/apollo-',
			'assets.apollo.rio.br',
			'cdn.jsdelivr.net/npm/motion',
			// Motion.dev library
			'unpkg.com/@motionone',
			// Motion.dev alternative CDN
		);

		foreach ($wp_scripts->queue as $handle) {
			$keep = false;

			// Check if handle is in allowed list
			if (in_array($handle, $allowed_handles, true)) {
				$keep = true;
			}

			// Check if handle starts with apollo-
			if (\strpos($handle, 'apollo-') === 0) {
				$keep = true;
			}

			// Check if src contains allowed patterns
			if (isset($wp_scripts->registered[$handle]) && is_object($wp_scripts->registered[$handle])) {
				$src = $wp_scripts->registered[$handle]->src ?? '';
				foreach ($allowed_patterns as $pattern) {
					if (\strpos($src, $pattern) !== false) {
						$keep = true;

						break;
					}
				}
			}

			// Remove if not Apollo
			if (! $keep) {
				wp_dequeue_script($handle);
				wp_deregister_script($handle);
			}
		} //end foreach
	}

	/**
	 * P0-4: Enqueue Canvas CSS
	 * STRICT MODE: base.js from CDN handles uni.css loading automatically.
	 * This method only loads Apollo-specific overrides.
	 */
	private function enqueueCanvasCSS()
	{
		// base.js automatically loads uni.css - DO NOT enqueue it here
		// Just ensure base assets are loaded
		if (function_exists('apollo_ensure_base_assets')) {
			apollo_ensure_base_assets();
		}

		// Load local canvas-mode.css for Apollo-specific overrides only
		$css_file = APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/canvas-mode.css';
		$css_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/css/canvas-mode.css';

		if (file_exists($css_path)) {
			wp_enqueue_style(
				'apollo-canvas-mode',
				$css_file,
				array(), // No dependencies - base.js loads uni.css automatically
				APOLLO_SOCIAL_VERSION
			);

			// Add inline styles to override theme completely
			$inline_css = '
                body.apollo-canvas-mode {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: var(--ap-bg-main, #fafafa) !important;
                }
                /* Hide all theme elements */
                body.apollo-canvas-mode > *:not(.apollo-header):not(.apollo-canvas-main):not(.apollo-footer):not(script):not(style) {
                    display: none !important;
                }
                body.apollo-canvas-mode .apollo-header,
                body.apollo-canvas-mode .apollo-canvas-main,
                body.apollo-canvas-mode .apollo-footer {
                    display: block !important;
                }
            ';
			wp_add_inline_style('apollo-canvas-mode', $inline_css);
		} //end if

		// Also enqueue modules CSS if exists
		$modules_css  = APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/modules.css';
		$modules_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/css/modules.css';

		if (file_exists($modules_path)) {
			wp_enqueue_style(
				'apollo-modules',
				$modules_css,
				array(), // No dependencies
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
				array('jquery'),
				APOLLO_SOCIAL_VERSION,
				true
			);

			// Add localized data
			wp_localize_script(
				'apollo-canvas',
				'apolloCanvas',
				array(
					'ajaxUrl'   => admin_url('admin-ajax.php'),
					'nonce'     => wp_create_nonce('apollo_canvas'),
					'pluginUrl' => APOLLO_SOCIAL_PLUGIN_URL,
				)
			);
		}

		// Enqueue Hold to Confirm system (required for all forms)
		$this->enqueueHoldToConfirm();
	}

	/**
	 * Enqueue Hold to Confirm system
	 */
	private function enqueueHoldToConfirm()
	{
		// Enqueue Motion library (required dependency)
		wp_enqueue_script(
			'motion',
			'https://cdn.jsdelivr.net/npm/motion@latest/dist/motion.umd.js',
			array(),
			APOLLO_SOCIAL_VERSION,
			true
		);

		// Enqueue Hold to Confirm CSS
		$css_file = APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/hold-to-confirm.css';
		$css_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/css/hold-to-confirm.css';

		if (file_exists($css_path)) {
			wp_enqueue_style(
				'apollo-hold-to-confirm',
				$css_file,
				array(),
				APOLLO_SOCIAL_VERSION
			);
		}

		// Enqueue Hold to Confirm JS
		$js_file = APOLLO_SOCIAL_PLUGIN_URL . 'assets/js/hold-to-confirm.js';
		$js_path = APOLLO_SOCIAL_PLUGIN_DIR . 'assets/js/hold-to-confirm.js';

		if (file_exists($js_path)) {
			wp_enqueue_script(
				'apollo-hold-to-confirm',
				$js_file,
				array('motion'),
				APOLLO_SOCIAL_VERSION,
				true
			);
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
				array('apollo-canvas'),
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
				array('apollo-canvas'),
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
		if (! empty($this->config['block_theme_css'])) {
			add_action('wp_print_styles', array($this, 'dequeueThemeStyles'), 100);
		}

		if (! empty($this->config['block_theme_js'])) {
			add_action('wp_print_scripts', array($this, 'dequeueThemeScripts'), 100);
		}
	}

	/**
	 * Inject Plausible Analytics script (Script-Only Mode)
	 */
	private function injectPlausibleAnalytics()
	{
		$analytics_config = config('analytics');

		// Check if analytics enabled and Canvas injection enabled
		if (! ($analytics_config['enabled'] ?? false) || 'plausible' !== $analytics_config['driver'] || ! ($analytics_config['inject_on_canvas'] ?? false)) {
			return;
		}

		$script_config = $analytics_config['script_config'] ?? array();
		$domain        = isset($script_config['domain']) ? sanitize_text_field($script_config['domain']) : '';
		$script_url    = isset($script_config['script_url']) ? esc_url_raw($script_config['script_url']) : 'https://plausible.io/js/plausible.js';

		if (empty($domain) || ! filter_var($script_url, FILTER_VALIDATE_URL)) {
			return;
		}

		// Simple script injection without WordPress enqueue system
		echo '<script defer data-domain="' . esc_attr($domain) . '" src="' . esc_url($script_url) . '"></script>' . "\n";

		// Add Apollo Analytics helper functions
		$this->addApolloAnalyticsJS();
	}

	/**
	 * Add Apollo-specific analytics JavaScript functions
	 */
	private function addApolloAnalyticsJS()
	{
		$analytics_config = config('analytics');
		$events_config    = $analytics_config['events'] ?? array();

		// Generate safe JavaScript code (static, no user input)
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
                    action: action
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
            // Defensive check: only track if analytics is available
            if (typeof window.apolloAnalytics === 'undefined' || !window.apolloAnalytics.trackGroupView) {
                return;
            }

            var path = window.location.pathname;
            var apolloRoutes = ['/a/', '/comunidade/', '/nucleo/', '/season/', '/membro/', '/membership', '/uniao/', '/anuncio/'];

            for (var i = 0; i < apolloRoutes.length; i++) {
                if (path.indexOf(apolloRoutes[i]) !== -1) {
                    if (path.indexOf('/comunidade/') !== -1) {
                        var slug = path.split('/comunidade/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('comunidade', slug);
                    }
                    else if (path.indexOf('/nucleo/') !== -1) {
                        var slug = path.split('/nucleo/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('nucleo', slug);
                    }
                    else if (path.indexOf('/membro/') !== -1) {
                        var slug = path.split('/membro/')[1]?.split('/')[0];
                        if (slug) window.apolloAnalytics.trackGroupView('membro', slug);
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

		// Output safe JavaScript (static code, no user input)
		echo wp_kses_post($js_code);
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
