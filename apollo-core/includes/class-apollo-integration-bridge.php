<?php
/**
 * Apollo Integration Bridge
 *
 * Provides structured integration points for companion plugins (Events, Social, Rio).
 * This class extends the functional integration-bridge.php with OOP architecture.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Integration_Bridge
 *
 * Centralized integration hub for Apollo plugin ecosystem.
 * Defines action/filter hooks for companion plugins to attach.
 */
class Apollo_Integration_Bridge {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Integration_Bridge|null
	 */
	private static ?Apollo_Integration_Bridge $instance = null;

	/**
	 * Registered companion plugins
	 *
	 * @var array<string, array>
	 */
	private array $registered_plugins = array();

	/**
	 * Template data cache
	 *
	 * @var array<string, mixed>
	 */
	private array $template_data_cache = array();

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Integration_Bridge
	 */
	public static function get_instance(): Apollo_Integration_Bridge {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton pattern)
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Fire integration ready action.
		add_action( 'plugins_loaded', array( $this, 'fire_integration_ready' ), 20 );

		// Template integration hooks.
		add_action( 'apollo_core_before_template', array( $this, 'prepare_template_data' ), 10, 2 );
		add_action( 'apollo_core_after_template', array( $this, 'cleanup_template_data' ), 10, 1 );
	}

	/**
	 * Fire integration ready action
	 *
	 * @return void
	 */
	public function fire_integration_ready(): void {
		/**
		 * Action: apollo_integration_bridge_ready
		 *
		 * Fired when the Integration Bridge is ready for companion plugins.
		 * Companion plugins should use this hook to register themselves.
		 *
		 * @param Apollo_Integration_Bridge $bridge The bridge instance.
		 */
		do_action( 'apollo_integration_bridge_ready', $this );
	}

	// =========================================================================
	// PLUGIN REGISTRATION
	// =========================================================================

	/**
	 * Register a companion plugin
	 *
	 * @param string $plugin_slug Plugin identifier (events, social, rio).
	 * @param array  $config      Plugin configuration.
	 * @return bool
	 */
	public function register_plugin( string $plugin_slug, array $config = array() ): bool {
		$defaults = array(
			'version'      => '1.0.0',
			'file'         => '',
			'path'         => '',
			'url'          => '',
			'capabilities' => array(),
			'supports'     => array(),
			'hooks'        => array(),
		);

		$this->registered_plugins[ $plugin_slug ] = wp_parse_args( $config, $defaults );

		/**
		 * Action: apollo_plugin_registered
		 *
		 * Fired when a companion plugin registers with Core.
		 *
		 * @param string $plugin_slug Plugin identifier.
		 * @param array  $config      Plugin configuration.
		 */
		do_action( 'apollo_plugin_registered', $plugin_slug, $this->registered_plugins[ $plugin_slug ] );

		return true;
	}

	/**
	 * Check if a companion plugin is registered
	 *
	 * @param string $plugin_slug Plugin identifier.
	 * @return bool
	 */
	public function is_plugin_registered( string $plugin_slug ): bool {
		return isset( $this->registered_plugins[ $plugin_slug ] );
	}

	/**
	 * Check if a companion plugin is active
	 *
	 * @param string $plugin_slug Plugin identifier (events, social, rio).
	 * @return bool
	 */
	public function is_plugin_active( string $plugin_slug ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_files = array(
			'events' => 'apollo-events-manager/apollo-events-manager.php',
			'social' => 'apollo-social/apollo-social.php',
			'rio'    => 'apollo-rio/apollo-rio.php',
		);

		if ( ! isset( $plugin_files[ $plugin_slug ] ) ) {
			return false;
		}

		return is_plugin_active( $plugin_files[ $plugin_slug ] );
	}

	/**
	 * Get registered plugin data
	 *
	 * @param string $plugin_slug Plugin identifier.
	 * @return array|null
	 */
	public function get_plugin_data( string $plugin_slug ): ?array {
		return $this->registered_plugins[ $plugin_slug ] ?? null;
	}

	/**
	 * Get all registered plugins
	 *
	 * @return array<string, array>
	 */
	public function get_registered_plugins(): array {
		return $this->registered_plugins;
	}

	// =========================================================================
	// EVENT DISPLAY HOOKS
	// =========================================================================

	/**
	 * Fire before event display hook
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Display arguments.
	 * @return void
	 */
	public function before_event_display( int $event_id, array $args = array() ): void {
		/**
		 * Action: apollo_core_before_event_display
		 *
		 * Fired before an event is displayed. Companion plugins can add
		 * content or modify data before rendering.
		 *
		 * @param int   $event_id Event post ID.
		 * @param array $args     Display arguments.
		 */
		do_action( 'apollo_core_before_event_display', $event_id, $args );
	}

	/**
	 * Fire after event display hook
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Display arguments.
	 * @return void
	 */
	public function after_event_display( int $event_id, array $args = array() ): void {
		/**
		 * Action: apollo_core_after_event_display
		 *
		 * Fired after an event is displayed. Companion plugins can add
		 * additional content after the main event output.
		 *
		 * @param int   $event_id Event post ID.
		 * @param array $args     Display arguments.
		 */
		do_action( 'apollo_core_after_event_display', $event_id, $args );
	}

	// =========================================================================
	// SOCIAL BUTTONS HOOKS
	// =========================================================================

	/**
	 * Get social buttons HTML
	 *
	 * @param int    $post_id Post ID.
	 * @param string $context Context (event, dj, local, post).
	 * @param array  $args    Display arguments.
	 * @return string
	 */
	public function get_social_buttons( int $post_id, string $context = 'post', array $args = array() ): string {
		$defaults = array(
			'show_share'    => true,
			'show_like'     => true,
			'show_favorite' => true,
			'show_comment'  => false,
			'size'          => 'medium',
			'style'         => 'default',
		);

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter: apollo_core_social_buttons
		 *
		 * Allows companion plugins to provide social button HTML.
		 * Apollo Social should hook into this to render its buttons.
		 *
		 * @param string $html    The HTML output (empty by default).
		 * @param int    $post_id Post ID.
		 * @param string $context Context (event, dj, local, post).
		 * @param array  $args    Display arguments.
		 */
		return apply_filters( 'apollo_core_social_buttons', '', $post_id, $context, $args );
	}

	/**
	 * Render social buttons
	 *
	 * @param int    $post_id Post ID.
	 * @param string $context Context.
	 * @param array  $args    Arguments.
	 * @return void
	 */
	public function render_social_buttons( int $post_id, string $context = 'post', array $args = array() ): void {
		echo $this->get_social_buttons( $post_id, $context, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// =========================================================================
	// PWA / RIO OPTIMIZATION HOOKS
	// =========================================================================

	/**
	 * Apply Rio optimization to content
	 *
	 * @param string $content HTML content.
	 * @param string $context Context (page, event, template).
	 * @param array  $args    Optimization arguments.
	 * @return string
	 */
	public function apply_rio_optimization( string $content, string $context = 'page', array $args = array() ): string {
		$defaults = array(
			'lazy_load_images' => true,
			'defer_scripts'    => true,
			'preconnect'       => true,
			'service_worker'   => true,
		);

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter: apollo_core_rio_optimize
		 *
		 * Allows Apollo Rio to apply PWA/performance optimizations.
		 *
		 * @param string $content Optimized HTML content.
		 * @param string $context Context (page, event, template).
		 * @param array  $args    Optimization arguments.
		 */
		return apply_filters( 'apollo_core_rio_optimize', $content, $context, $args );
	}

	// =========================================================================
	// TEMPLATE DATA MANAGEMENT
	// =========================================================================

	/**
	 * Prepare template data before rendering
	 *
	 * @param string $template Template name.
	 * @param array  $data     Template data.
	 * @return void
	 */
	public function prepare_template_data( string $template, array $data ): void {
		$this->template_data_cache[ $template ] = $data;

		/**
		 * Action: apollo_core_template_data_prepared
		 *
		 * Fired after template data is prepared for rendering.
		 *
		 * @param string $template Template name.
		 * @param array  $data     Template data.
		 */
		do_action( 'apollo_core_template_data_prepared', $template, $data );
	}

	/**
	 * Cleanup template data after rendering
	 *
	 * @param string $template Template name.
	 * @return void
	 */
	public function cleanup_template_data( string $template ): void {
		unset( $this->template_data_cache[ $template ] );
	}

	/**
	 * Get cached template data
	 *
	 * @param string $template Template name.
	 * @return array|null
	 */
	public function get_template_data( string $template ): ?array {
		return $this->template_data_cache[ $template ] ?? null;
	}

	// =========================================================================
	// DATA TRANSFORMATION FILTERS
	// =========================================================================

	/**
	 * Transform event data for display
	 *
	 * @param array $event_data Raw event data.
	 * @param int   $event_id   Event post ID.
	 * @return array
	 */
	public function transform_event_data( array $event_data, int $event_id ): array {
		/**
		 * Filter: apollo_core_event_data_transform
		 *
		 * Allows companion plugins to transform event data before display.
		 *
		 * @param array $event_data Transformed event data.
		 * @param int   $event_id   Event post ID.
		 */
		return apply_filters( 'apollo_core_event_data_transform', $event_data, $event_id );
	}

	/**
	 * Transform user data for display
	 *
	 * @param array $user_data Raw user data.
	 * @param int   $user_id   User ID.
	 * @return array
	 */
	public function transform_user_data( array $user_data, int $user_id ): array {
		/**
		 * Filter: apollo_core_user_data_transform
		 *
		 * Allows companion plugins to transform user data before display.
		 *
		 * @param array $user_data Transformed user data.
		 * @param int   $user_id   User ID.
		 */
		return apply_filters( 'apollo_core_user_data_transform', $user_data, $user_id );
	}

	// =========================================================================
	// TEMPLATE RENDERING WITH HOOKS
	// =========================================================================

	/**
	 * Render event with integration hooks
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Display arguments.
	 * @return string
	 */
	public function render_event( int $event_id, array $args = array() ): string {
		ob_start();

		// Fire before hook.
		$this->before_event_display( $event_id, $args );

		// Get event data.
		$event_data = $this->get_event_data( $event_id );

		// Transform data.
		$event_data = $this->transform_event_data( $event_data, $event_id );

		// Load template via Template Loader.
		if ( class_exists( '\Apollo_Template_Loader' ) ) {
			\Apollo_Template_Loader::load( 'core-event-single', $event_data );
		}

		// Fire after hook.
		$this->after_event_display( $event_id, $args );

		$output = ob_get_clean();

		// Apply Rio optimization if available.
		return $this->apply_rio_optimization( $output, 'event', $args );
	}

	/**
	 * Get event data for template
	 *
	 * @param int $event_id Event post ID.
	 * @return array
	 */
	private function get_event_data( int $event_id ): array {
		$post = get_post( $event_id );

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return array();
		}

		return array(
			'event_id'    => $event_id,
			'title'       => get_the_title( $event_id ),
			'content'     => apply_filters( 'the_content', $post->post_content ),
			'excerpt'     => get_the_excerpt( $event_id ),
			'thumbnail'   => get_the_post_thumbnail_url( $event_id, 'large' ),
			'banner'      => get_post_meta( $event_id, '_event_banner', true ),
			'start_date'  => get_post_meta( $event_id, '_event_start_date', true ),
			'end_date'    => get_post_meta( $event_id, '_event_end_date', true ),
			'start_time'  => get_post_meta( $event_id, '_event_start_time', true ),
			'end_time'    => get_post_meta( $event_id, '_event_end_time', true ),
			'location'    => get_post_meta( $event_id, '_event_location', true ),
			'address'     => get_post_meta( $event_id, '_event_address', true ),
			'city'        => get_post_meta( $event_id, '_event_city', true ),
			'latitude'    => get_post_meta( $event_id, '_event_latitude', true ),
			'longitude'   => get_post_meta( $event_id, '_event_longitude', true ),
			'dj_ids'      => get_post_meta( $event_id, '_event_dj_ids', true ) ?: array(),
			'local_ids'   => get_post_meta( $event_id, '_event_local_ids', true ) ?: array(),
			'tickets_url' => get_post_meta( $event_id, '_tickets_ext', true ),
			'featured'    => get_post_meta( $event_id, '_event_featured', true ),
			'categories'  => wp_get_object_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) ),
			'tags'        => wp_get_object_terms( $event_id, 'event_listing_tag', array( 'fields' => 'names' ) ),
			'sounds'      => wp_get_object_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) ),
			'permalink'   => get_permalink( $event_id ),
			'author_id'   => $post->post_author,
		);
	}

	// =========================================================================
	// DEACTIVATION CLEANUP
	// =========================================================================

	/**
	 * Cleanup on deactivation
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear transients.
		delete_transient( 'apollo_integration_bridge_plugins' );

		/**
		 * Action: apollo_integration_bridge_deactivated
		 *
		 * Fired when the Integration Bridge is deactivated.
		 * Companion plugins should cleanup their bridge registrations.
		 */
		do_action( 'apollo_integration_bridge_deactivated' );
	}
}

// =========================================================================
// HELPER FUNCTIONS
// =========================================================================

/**
 * Get Integration Bridge instance
 *
 * @return Apollo_Integration_Bridge
 */
function apollo_integration_bridge(): Apollo_Integration_Bridge {
	return Apollo_Integration_Bridge::get_instance();
}

/**
 * Check if a companion plugin is active (wrapper)
 *
 * @param string $plugin_slug Plugin identifier.
 * @return bool
 */
function apollo_companion_active( string $plugin_slug ): bool {
	return apollo_integration_bridge()->is_plugin_active( $plugin_slug );
}

/**
 * Render social buttons (wrapper)
 *
 * @param int    $post_id Post ID.
 * @param string $context Context.
 * @param array  $args    Arguments.
 * @return void
 */
function apollo_social_buttons( int $post_id, string $context = 'post', array $args = array() ): void {
	apollo_integration_bridge()->render_social_buttons( $post_id, $context, $args );
}

/**
 * Get event with integration hooks (wrapper)
 *
 * @param int   $event_id Event ID.
 * @param array $args     Arguments.
 * @return string
 */
function apollo_render_event( int $event_id, array $args = array() ): string {
	return apollo_integration_bridge()->render_event( $event_id, $args );
}

// Initialize on plugins_loaded.
add_action(
	'plugins_loaded',
	function () {
		Apollo_Integration_Bridge::get_instance();
	},
	10
);
