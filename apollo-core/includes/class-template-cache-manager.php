<?php
/**
 * Apollo Template Cache Manager
 *
 * PHASE 7: Performance Optimization - Template Caching
 * Implements intelligent caching for ViewModel data and rendered templates.
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Cache Manager class.
 *
 * Provides caching functionality for template rendering and ViewModel data.
 *
 * @since 4.0.0
 */
class Template_Cache_Manager {

	private const CACHE_GROUP         = 'apollo_templates';
	private const CACHE_EXPIRY        = HOUR_IN_SECONDS * 6; // 6 hours.
	private const RENDER_CACHE_EXPIRY = MINUTE_IN_SECONDS * 30; // 30 minutes.

	/**
	 * Get cached ViewModel data.
	 *
	 * @param string   $cache_key Cache key identifier.
	 * @param callable $callback  Callback to generate data if not cached.
	 * @return mixed Cached data or callback result.
	 */
	public static function get_viewmodel_cache( string $cache_key, callable $callback ) {
		$full_key = self::generate_cache_key( 'viewmodel', $cache_key );

		$cached = wp_cache_get( $full_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = $callback();
		wp_cache_set( $full_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRY );

		return $data;
	}

	/**
	 * Get cached rendered template.
	 *
	 * @param string   $template_name Template name.
	 * @param array    $data          Data to render.
	 * @param callable $callback      Callback to render if not cached.
	 * @return string Rendered template HTML.
	 */
	public static function get_render_cache( string $template_name, array $data, callable $callback ): string {
		$cache_key = self::generate_render_cache_key( $template_name, $data );

		$cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$rendered = $callback();
		wp_cache_set( $cache_key, $rendered, self::CACHE_GROUP, self::RENDER_CACHE_EXPIRY );

		return $rendered;
	}

	/**
	 * Clear all template caches
	 */
	public static function clear_all_caches(): void {
		wp_cache_flush_group( self::CACHE_GROUP );
	}

	/**
	 * Clear cache for specific template type.
	 *
	 * @param string $template_type Template type to clear cache for.
	 * @return void
	 */
	public static function clear_template_cache( string $template_type ): void {
		/**
		 * WordPress object cache instance.
		 *
		 * @var object $wp_object_cache WordPress object cache.
		 */
		global $wp_object_cache;

		if ( ! isset( $wp_object_cache->cache[ self::CACHE_GROUP ] ) ) {
			return;
		}

		$keys_to_delete = array();
		foreach ( $wp_object_cache->cache[ self::CACHE_GROUP ] as $key => $value ) {
			if ( strpos( $key, "template_{$template_type}_" ) === 0 ) {
				$keys_to_delete[] = $key;
			}
		}

		foreach ( $keys_to_delete as $key ) {
			wp_cache_delete( $key, self::CACHE_GROUP );
		}
	}

	/**
	 * Generate cache key for ViewModel data.
	 *
	 * @param string $type       Cache type.
	 * @param string $identifier Cache identifier.
	 * @return string Generated cache key.
	 */
	private static function generate_cache_key( string $type, string $identifier ): string {
		return sanitize_key( "{$type}_{$identifier}_" . md5( $identifier ) );
	}

	/**
	 * Generate cache key for rendered templates.
	 *
	 * @param string $template_name Template name.
	 * @param array  $data          Template data.
	 * @return string Generated cache key.
	 */
	private static function generate_render_cache_key( string $template_name, array $data ): string {
		// Create a hash of the data that affects rendering.
		$relevant_data = self::extract_cache_relevant_data( $data );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Used for cache key generation.
		$data_hash = md5( serialize( $relevant_data ) );

		return sanitize_key( "render_{$template_name}_{$data_hash}" );
	}

	/**
	 * Extract only the data that affects template rendering.
	 *
	 * @param array $data Full template data.
	 * @return array Relevant data for caching.
	 */
	private static function extract_cache_relevant_data( array $data ): array {
		$relevant_keys = array(
			'id',
			'title',
			'content',
			'excerpt',
			'status',
			'type',
			'author',
			'date',
			'modified',
			'slug',
			'permalink',
			'thumbnail',
			'categories',
			'tags',
			'meta',
		);

		$relevant = array();
		foreach ( $relevant_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$relevant[ $key ] = $data[ $key ];
			}
		}

		return $relevant;
	}

	/**
	 * Warm up caches for popular content.
	 *
	 * @return void
	 */
	public static function warmup_popular_caches(): void {
		// Check if ViewModel factory exists.
		if ( ! class_exists( 'Apollo_ViewModel_Factory' ) && ! class_exists( '\Apollo_Core\Apollo_ViewModel_Factory' ) ) {
			return;
		}

		// Get popular events.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Used for cache warmup only.
		$popular_events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => 10,
				'meta_key'       => 'event_views',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			)
		);

		foreach ( $popular_events as $event ) {
			// Pre-cache ViewModel data.
			self::get_viewmodel_cache(
				"event_{$event->ID}",
				function () use ( $event ) {
					if ( class_exists( '\Apollo_Core\Apollo_ViewModel_Factory' ) ) {
						$view_model = \Apollo_Core\Apollo_ViewModel_Factory::create_from_data( $event, 'single_event' );
						return $view_model->get_single_event_data();
					}
					return array();
				}
			);
		}

		// Get popular users.
		$popular_users = get_users(
			array(
				'number'  => 10,
				'orderby' => 'post_count',
				'order'   => 'DESC',
			)
		);

		foreach ( $popular_users as $user ) {
			// Pre-cache user profile data.
			self::get_viewmodel_cache(
				"user_{$user->ID}",
				function () use ( $user ) {
					if ( class_exists( '\Apollo_Core\Apollo_ViewModel_Factory' ) ) {
						$view_model = \Apollo_Core\Apollo_ViewModel_Factory::create_from_data( $user, 'user_profile' );
						return $view_model->get_user_profile_data();
					}
					return array();
				}
			);
		}
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache statistics.
	 */
	public static function get_cache_stats(): array {
		/**
		 * WordPress object cache instance.
		 *
		 * @var object $wp_object_cache WordPress object cache.
		 */
		global $wp_object_cache;

		$stats = array(
			'cache_group'         => self::CACHE_GROUP,
			'cache_expiry'        => self::CACHE_EXPIRY,
			'render_cache_expiry' => self::RENDER_CACHE_EXPIRY,
			'cached_items'        => 0,
			'cache_hits'          => 0,
			'cache_misses'        => 0,
		);

		if ( isset( $wp_object_cache->cache[ self::CACHE_GROUP ] ) ) {
			$stats['cached_items'] = count( $wp_object_cache->cache[ self::CACHE_GROUP ] );
		}

		// Get cache performance stats if available.
		if ( method_exists( $wp_object_cache, 'getStats' ) ) {
			$cache_stats = $wp_object_cache->getStats();
			// Parse stats if available.
		}

		return $stats;
	}
}

// Hook into WordPress actions for cache management.
add_action(
	'save_post',
	function ( $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- WordPress requires this parameter.
		unset( $post_id ); // Mark as intentionally unused.
		// Clear caches when posts are updated.
		Template_Cache_Manager::clear_template_cache( 'event' );
		Template_Cache_Manager::clear_template_cache( 'user' );
	}
);

add_action(
	'profile_update',
	function ( $user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- WordPress requires this parameter.
		unset( $user_id ); // Mark as intentionally unused.
		// Clear user caches when profiles are updated.
		Template_Cache_Manager::clear_template_cache( 'user' );
	}
);

// Warm up caches on plugin activation.
register_activation_hook(
	__FILE__,
	function () {
		if ( wp_next_scheduled( 'apollo_cache_warmup' ) === false ) {
			wp_schedule_event( time(), 'daily', 'apollo_cache_warmup' );
		}
	}
);

// Hook for cache warmup.
add_action( 'apollo_cache_warmup', array( '\\Apollo_Core\\Template_Cache_Manager', 'warmup_popular_caches' ) );

// Admin interface for cache management.
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'tools.php',
			'Apollo Cache Manager',
			'Apollo Cache Manager',
			'manage_options',
			'apollo-cache-manager',
			function () {
				?>
			<div class="wrap">
				<h1>Apollo Template Cache Manager</h1>
				<p>Manage template caching for improved performance.</p>

				<div class="card">
					<h2>Cache Statistics</h2>
						<?php
						$stats = Template_Cache_Manager::get_cache_stats();
						echo '<pre>';
						foreach ( $stats as $key => $value ) {
							echo esc_html( $key ) . ': ' . esc_html( is_string( $value ) || is_numeric( $value ) ? $value : wp_json_encode( $value ) ) . "\n";
						}
						echo '</pre>';
						?>
				</div>

				<div class="card">
					<h2>Cache Actions</h2>
					<p>
						<button class="button button-primary" onclick="warmupCaches()">Warm Up Popular Caches</button>
						<button class="button button-secondary" onclick="clearAllCaches()">Clear All Caches</button>
					</p>
				</div>

				<script>
				function warmupCaches() {
					fetch(ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
							body: 'action=apollo_warmup_caches&nonce=' + '<?php echo esc_js( wp_create_nonce( 'apollo_cache_action' ) ); ?>'
					}).then(response => response.json())
					.then(data => alert(data.message));
				}

				function clearAllCaches() {
					if (confirm('Are you sure you want to clear all caches?')) {
						fetch(ajaxurl, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
								body: 'action=apollo_clear_caches&nonce=' + '<?php echo esc_js( wp_create_nonce( 'apollo_cache_action' ) ); ?>'
						}).then(response => response.json())
						.then(data => alert(data.message));
					}
				}
				</script>
			</div>
				<?php
			}
		);
	}
);

// AJAX handlers for cache management.
add_action(
	'wp_ajax_apollo_warmup_caches',
	function () {
		check_ajax_referer( 'apollo_cache_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		Template_Cache_Manager::warmup_popular_caches();

		wp_send_json(
			array(
				'success' => true,
				'message' => 'Cache warmup completed!',
			)
		);
	}
);

add_action(
	'wp_ajax_apollo_clear_caches',
	function () {
		check_ajax_referer( 'apollo_cache_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		Template_Cache_Manager::clear_all_caches();

		wp_send_json(
			array(
				'success' => true,
				'message' => 'All caches cleared!',
			)
		);
	}
);
