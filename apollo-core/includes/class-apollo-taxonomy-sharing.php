<?php
/**
 * Apollo Taxonomy Sharing
 *
 * Handles cross-plugin taxonomy sharing for the Apollo ecosystem.
 * Implements proper attachment mechanism using register_taxonomy_for_object_type().
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Resolves: event_season sharing between apollo-events-manager and apollo-social
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Taxonomy_Sharing
 *
 * Manages cross-plugin taxonomy sharing.
 */
class Apollo_Taxonomy_Sharing {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Taxonomy_Sharing|null
	 */
	private static ?Apollo_Taxonomy_Sharing $instance = null;

	/**
	 * Shared taxonomy definitions
	 *
	 * @var array<string, array{primary_cpt: string, shared_cpts: array, primary_plugin: string}>
	 */
	private const SHARED_TAXONOMIES = array(
		'event_season' => array(
			'primary_cpt'    => 'event_listing',
			'shared_cpts'    => array( 'apollo_classified' ),
			'primary_plugin' => 'apollo-events-manager',
			'description'    => 'Temporadas compartilhadas entre eventos e classificados',
		),
		'event_sounds' => array(
			'primary_cpt'    => 'event_listing',
			'shared_cpts'    => array( 'event_dj' ),
			'primary_plugin' => 'apollo-events-manager',
			'description'    => 'Estilos musicais compartilhados entre eventos e DJs',
		),
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Taxonomy_Sharing
	 */
	public static function get_instance(): Apollo_Taxonomy_Sharing {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Process sharing after taxonomies are ready.
		\add_action( 'apollo_taxonomies_ready', array( $this, 'process_sharing' ), 10 );

		// Fallback: Process on init priority 20 (after all registrations).
		\add_action( 'init', array( $this, 'process_sharing_fallback' ), 20 );

		// Hook into apollo-social to connect event_season.
		\add_action( 'apollo_social_loaded', array( $this, 'connect_social_taxonomies' ) );

		// Hook into apollo-events-manager to notify other plugins.
		\add_action( 'apollo_events_post_types_loaded', array( $this, 'notify_taxonomy_available' ) );
	}

	/**
	 * Process taxonomy sharing
	 *
	 * @param Apollo_Taxonomy_Registry|null $registry Optional registry instance.
	 * @return void
	 */
	public function process_sharing( ?Apollo_Taxonomy_Registry $registry = null ): void {
		foreach ( self::SHARED_TAXONOMIES as $taxonomy => $config ) {
			// Check if taxonomy exists (primary plugin is active).
			if ( ! \taxonomy_exists( $taxonomy ) ) {
				$this->log(
					\sprintf(
						'Shared taxonomy "%s" not available - primary plugin "%s" may be inactive.',
						$taxonomy,
						$config['primary_plugin']
					)
				);
				continue;
			}

			// Attach to each shared CPT.
			foreach ( $config['shared_cpts'] as $post_type ) {
				$this->attach_taxonomy_to_cpt( $taxonomy, $post_type );
			}
		}
	}

	/**
	 * Process sharing fallback (when apollo_taxonomies_ready doesn't fire)
	 *
	 * @return void
	 */
	public function process_sharing_fallback(): void {
		static $processed = false;

		if ( $processed ) {
			return;
		}

		// Only run if the proper hook hasn't fired.
		if ( \did_action( 'apollo_taxonomies_ready' ) ) {
			return;
		}

		$this->process_sharing();
		$processed = true;
	}

	/**
	 * Attach a taxonomy to a CPT
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	private function attach_taxonomy_to_cpt( string $taxonomy, string $post_type ): bool {
		// Check if post type exists.
		if ( ! \post_type_exists( $post_type ) ) {
			$this->log(
				\sprintf(
					'Cannot attach "%s" to "%s" - post type not registered.',
					$taxonomy,
					$post_type
				)
			);
			return false;
		}

		// Check if already attached.
		$tax_object = \get_taxonomy( $taxonomy );
		if ( $tax_object && \in_array( $post_type, (array) $tax_object->object_type, true ) ) {
			$this->log(
				\sprintf(
					'Taxonomy "%s" already attached to "%s".',
					$taxonomy,
					$post_type
				)
			);
			return true;
		}

		// Attach taxonomy.
		$result = \register_taxonomy_for_object_type( $taxonomy, $post_type );

		if ( $result ) {
			$this->log(
				\sprintf(
					'✅ Taxonomy "%s" attached to "%s".',
					$taxonomy,
					$post_type
				)
			);

			/**
			 * Action: apollo_taxonomy_attached
			 *
			 * Fired when a taxonomy is attached to a post type.
			 *
			 * @param string $taxonomy  Taxonomy slug.
			 * @param string $post_type Post type slug.
			 */
			\do_action( 'apollo_taxonomy_attached', $taxonomy, $post_type );
		}

		return $result;
	}

	/**
	 * Connect taxonomies when apollo-social loads
	 *
	 * @return void
	 */
	public function connect_social_taxonomies(): void {
		// Connect event_season to classifieds.
		if ( \taxonomy_exists( 'event_season' ) && \post_type_exists( 'apollo_classified' ) ) {
			$this->attach_taxonomy_to_cpt( 'event_season', 'apollo_classified' );
		}
	}

	/**
	 * Notify other plugins when taxonomy is available
	 *
	 * @return void
	 */
	public function notify_taxonomy_available(): void {
		/**
		 * Action: apollo_event_season_available
		 *
		 * Fired when event_season taxonomy is registered and available.
		 * Other plugins can use this to attach to their CPTs.
		 */
		if ( \taxonomy_exists( 'event_season' ) ) {
			\do_action( 'apollo_event_season_available' );
		}

		/**
		 * Action: apollo_event_sounds_available
		 */
		if ( \taxonomy_exists( 'event_sounds' ) ) {
			\do_action( 'apollo_event_sounds_available' );
		}
	}

	/**
	 * Get shared taxonomy configuration
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array|null
	 */
	public function get_shared_config( string $taxonomy ): ?array {
		return self::SHARED_TAXONOMIES[ $taxonomy ] ?? null;
	}

	/**
	 * Get all shared taxonomies
	 *
	 * @return array
	 */
	public function get_all_shared(): array {
		return self::SHARED_TAXONOMIES;
	}

	/**
	 * Check if a taxonomy is shared
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool
	 */
	public function is_shared( string $taxonomy ): bool {
		return isset( self::SHARED_TAXONOMIES[ $taxonomy ] );
	}

	/**
	 * Get all post types using a shared taxonomy
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public function get_all_cpts_for_taxonomy( string $taxonomy ): array {
		if ( ! isset( self::SHARED_TAXONOMIES[ $taxonomy ] ) ) {
			// Fall back to WordPress API.
			$tax_object = \get_taxonomy( $taxonomy );
			return $tax_object ? (array) $tax_object->object_type : array();
		}

		$config   = self::SHARED_TAXONOMIES[ $taxonomy ];
		$all_cpts = array( $config['primary_cpt'] );
		$all_cpts = \array_merge( $all_cpts, $config['shared_cpts'] );

		// Filter to only registered post types.
		return \array_filter( $all_cpts, 'post_type_exists' );
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 * @return void
	 */
	private function log( string $message, string $level = 'debug' ): void {
		if ( ! \defined( 'APOLLO_DEBUG' ) || ! APOLLO_DEBUG ) {
			return;
		}

		$prefix = match ( $level ) {
			'error'   => '[Apollo Taxonomy Sharing ERROR]',
			'warning' => '[Apollo Taxonomy Sharing WARNING]',
			default   => '[Apollo Taxonomy Sharing]',
		};

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		\error_log( $prefix . ' ' . $message );
	}
}

// Initialize.
Apollo_Taxonomy_Sharing::get_instance();
