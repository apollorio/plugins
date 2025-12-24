<?php

namespace Apollo\Infrastructure\Providers;

use Apollo\Infrastructure\Admin\AnalyticsAdmin;
use Apollo\Infrastructure\Rendering\AssetsManager;

/**
 * Analytics Service Provider
 * Registers analytics functionality, admin pages and tracking
 */
class AnalyticsServiceProvider {

	private $analytics_admin;
	private $assets_manager;

	public function register() {
		// Load configuration helper if not loaded
		if ( ! function_exists( 'config' ) ) {
			require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/helpers.php';
		}

		// Initialize services
		$this->analytics_admin = new AnalyticsAdmin();
		$this->assets_manager  = new AssetsManager();
	}

	public function boot() {
		// Check if analytics is enabled
		$analytics_config = config( 'analytics' );

		if ( ! ( $analytics_config['enabled'] ?? false ) ) {
			return;
		}

		// Initialize analytics hooks
		$this->init_analytics_hooks();

		// Initialize Canvas injection
		$this->init_canvas_injection();
	}

	/**
	 * Initialize analytics-related hooks
	 */
	private function init_analytics_hooks() {
		// Add analytics to Canvas Mode assets
		add_action( 'apollo_canvas_assets', array( $this, 'enqueue_analytics_assets' ), 20 );

		// Add analytics tracking to specific actions
		add_action( 'apollo_group_viewed', array( $this, 'track_group_view' ), 10, 2 );
		add_action( 'apollo_group_joined', array( $this, 'track_group_join' ), 10, 2 );
		add_action( 'apollo_invite_sent', array( $this, 'track_invite_sent' ), 10, 3 );
		add_action( 'apollo_invite_approved', array( $this, 'track_invite_approved' ), 10, 3 );
		add_action( 'apollo_ad_viewed', array( $this, 'track_ad_view' ), 10, 4 );
		add_action( 'apollo_ad_created', array( $this, 'track_ad_create' ), 10, 3 );
		add_action( 'apollo_event_viewed', array( $this, 'track_event_view' ), 10, 4 );

		// Add AJAX handlers for analytics testing
		add_action( 'wp_ajax_apollo_test_analytics_connection', array( $this, 'ajax_test_analytics_connection' ) );
	}

	/**
	 * Initialize Canvas Mode injection
	 */
	private function init_canvas_injection() {
		// Hook into Canvas Mode rendering
		add_action( 'apollo_canvas_head', array( $this, 'inject_analytics_head' ) );
		add_action( 'apollo_canvas_footer', array( $this, 'inject_analytics_footer' ) );
	}

	/**
	 * Enqueue analytics assets for Canvas Mode
	 */
	public function enqueue_analytics_assets() {
		// This is called from AssetsManager during Canvas Mode
		// Analytics script injection is handled in inject_analytics_head
	}

	/**
	 * Inject analytics in Canvas head
	 */
	public function inject_analytics_head() {
		$analytics_config = config( 'analytics' );

		if ( $analytics_config['driver'] !== 'plausible' ) {
			return;
		}

		$plausible_config = $analytics_config['plausible'] ?? array();
		$domain           = $plausible_config['domain'] ?? '';
		$script_url       = $plausible_config['script_url'] ?? 'https://plausible.io/js/plausible.js';

		if ( empty( $domain ) ) {
			return;
		}

		// DNS prefetch and preconnect for performance
		$performance_config = $analytics_config['performance'] ?? array();

		if ( $performance_config['dns_prefetch'] ?? false ) {
			echo '<link rel="dns-prefetch" href="' . esc_url( parse_url( $script_url, PHP_URL_HOST ) ) . '">' . "\n";
		}

		if ( $performance_config['preconnect'] ?? false ) {
			echo '<link rel="preconnect" href="' . esc_url( parse_url( $script_url, PHP_URL_SCHEME ) . '://' . parse_url( $script_url, PHP_URL_HOST ) ) . '">' . "\n";
		}

		// Plausible script
		$defer = ( $performance_config['defer_script'] ?? true ) ? 'defer ' : '';
		echo '<script ' . $defer . 'data-domain="' . esc_attr( $domain ) . '" src="' . esc_url( $script_url ) . '"></script>' . "\n";
	}

	/**
	 * Inject analytics helper functions in footer
	 */
	public function inject_analytics_footer() {
		$analytics_config = config( 'analytics' );
		$events_config    = $analytics_config['events'] ?? array();

		echo '<script>
        window.apolloAnalytics = {
            track: function(eventName, props) {
                if (typeof plausible !== "undefined") {
                    plausible(eventName, { props: props || {} });
                }
                console.log("Apollo Analytics:", eventName, props);
            },

            trackGroupView: function(groupType, groupSlug) {
                this.track("group_view", {
                    group_type: groupType,
                    group_slug: groupSlug
                });
            },

            trackGroupJoin: function(groupType, groupSlug) {
                this.track("group_join", {
                    group_type: groupType,
                    group_slug: groupSlug
                });
            },

            trackInviteSent: function(groupType, inviteType) {
                this.track("invite_sent", {
                    group_type: groupType,
                    invite_type: inviteType
                });
            },

            trackInviteApproved: function(groupType, inviteType) {
                this.track("invite_approved", {
                    group_type: groupType,
                    invite_type: inviteType
                });
            },

            trackUnionBadgesToggle: function(action) {
                this.track("union_badges_toggle", {
                    action: action
                });
            },

            trackChatMessage: function(groupType) {
                this.track("chat_message_sent", {
                    group_type: groupType
                });
            },

            trackAdView: function(adId, category, groupType) {
                this.track("ad_view", {
                    ad_id: adId,
                    category: category,
                    group_type: groupType
                });
            },

            trackAdCreate: function(category, groupType) {
                this.track("ad_create", {
                    category: category,
                    group_type: groupType
                });
            },

            trackAdPublish: function(category, groupType) {
                this.track("ad_publish", {
                    category: category,
                    group_type: groupType
                });
            },

            trackAdReject: function(category, reason) {
                this.track("ad_reject", {
                    category: category,
                    reason: reason
                });
            },

            trackAdCreateInvalidSeason: function(attemptedSeason, userSeason) {
                this.track("ad_create_invalid_season", {
                    attempted_season: attemptedSeason,
                    user_season: userSeason
                });
            },

            trackEventView: function(eventId, seasonSlug, groupType) {
                this.track("event_view", {
                    event_id: eventId,
                    season_slug: seasonSlug,
                    group_type: groupType
                });
            },

            trackEventFilterApplied: function(filterType, seasonSlug) {
                this.track("event_filter_applied", {
                    filter_type: filterType,
                    season_slug: seasonSlug
                });
            }
        };

        // Auto-track page views with context
        document.addEventListener("DOMContentLoaded", function() {
            var path = window.location.pathname;
            var apolloRoutes = ["/a/", "/comunidade/", "/nucleo/", "/season/", "/membro/", "/membership", "/uniao/", "/anuncio/"];

            for (var i = 0; i < apolloRoutes.length; i++) {
                if (path.indexOf(apolloRoutes[i]) !== -1) {
                    // Extract context and track specific page views
                    if (path.indexOf("/comunidade/") !== -1) {
                        var slug = path.split("/comunidade/")[1]?.split("/")[0];
                        if (slug) window.apolloAnalytics.trackGroupView("comunidade", slug);
                    }
                    else if (path.indexOf("/nucleo/") !== -1) {
                        var slug = path.split("/nucleo/")[1]?.split("/")[0];
                        if (slug) window.apolloAnalytics.trackGroupView("nucleo", slug);
                    }
                    else if (path.indexOf("/membro/") !== -1) {
                        var slug = path.split("/membro/")[1]?.split("/")[0];
                        if (slug) window.apolloAnalytics.trackGroupView("membro", slug);
                    }
                    else if (path.indexOf("/season/") !== -1) {
                        var slug = path.split("/season/")[1]?.split("/")[0];
                        if (slug) window.apolloAnalytics.trackGroupView("season", slug);
                    }
                    break;
                }
            }
        });
        </script>' . "\n";
	}

	/**
	 * Server-side tracking methods
	 */
	public function track_group_view( $group_type, $group_slug ) {
		// Could log to database or send to analytics API
		error_log( "Apollo Analytics: group_view - {$group_type}/{$group_slug}" );
	}

	public function track_group_join( $group_type, $group_slug ) {
		error_log( "Apollo Analytics: group_join - {$group_type}/{$group_slug}" );
	}

	public function track_invite_sent( $group_type, $invite_type, $user_id ) {
		error_log( "Apollo Analytics: invite_sent - {$group_type}/{$invite_type} by user {$user_id}" );
	}

	public function track_invite_approved( $group_type, $invite_type, $user_id ) {
		error_log( "Apollo Analytics: invite_approved - {$group_type}/{$invite_type} for user {$user_id}" );
	}

	public function track_ad_view( $ad_id, $category, $group_type, $user_id ) {
		error_log( "Apollo Analytics: ad_view - {$ad_id}/{$category}/{$group_type} by user {$user_id}" );
	}

	public function track_ad_create( $category, $group_type, $user_id ) {
		error_log( "Apollo Analytics: ad_create - {$category}/{$group_type} by user {$user_id}" );
	}

	public function track_event_view( $event_id, $season_slug, $group_type, $user_id ) {
		error_log( "Apollo Analytics: event_view - {$event_id}/{$season_slug}/{$group_type} by user {$user_id}" );
	}

	/**
	 * AJAX handler for testing analytics connection
	 */
	public function ajax_test_analytics_connection() {
		check_ajax_referer( 'apollo_analytics_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$domain   = sanitize_text_field( $_POST['domain'] ?? '' );
		$api_key  = sanitize_text_field( $_POST['api_key'] ?? '' );
		$api_base = esc_url_raw( $_POST['api_base'] ?? 'https://plausible.io' );

		if ( empty( $domain ) ) {
			wp_send_json_error( 'Domínio é obrigatório' );
		}

		// Test API connection if API key is provided
		if ( ! empty( $api_key ) ) {
			$test_url = $api_base . '/api/v1/stats/aggregate?site_id=' . urlencode( $domain ) . '&period=day&metrics=visitors';

			$response = wp_remote_get(
				$test_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
					),
					'timeout' => 10,
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( 'Erro na conexão: ' . $response->get_error_message() );
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			if ( $status_code >= 400 ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				wp_send_json_error( 'Erro da API: ' . ( $data['error'] ?? 'Status ' . $status_code ) );
			}
		}//end if

		wp_send_json_success( 'Configuração válida! ✅' );
	}
}
