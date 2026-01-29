<?php
/**
 * Apollo Meta Registrar
 *
 * Single point for all register_post_meta() calls.
 * Ensures consistent REST API exposure and authorization.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Meta_Registrar
 *
 * Unified meta registration for all Apollo CPTs.
 */
class Apollo_Meta_Registrar {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Meta_Registrar|null
	 */
	private static ?Apollo_Meta_Registrar $instance = null;

	/**
	 * Registered meta keys
	 *
	 * @var array<string, array<string>>
	 */
	private array $registered = array();

	/**
	 * Registration option key
	 *
	 * @var string
	 */
	private const REGISTRATION_OPTION = 'apollo_meta_registration_version';

	/**
	 * Current registration version
	 *
	 * @var string
	 */
	private const REGISTRATION_VERSION = '2.0.0';

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Meta_Registrar
	 */
	public static function get_instance(): Apollo_Meta_Registrar {
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
		// Register meta on init, after CPTs are registered.
		\add_action( 'init', array( $this, 'register_all_meta' ), 20 );

		// Add custom REST field for deprecated key compatibility.
		\add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	/**
	 * Register all meta fields for Apollo CPTs
	 *
	 * @return void
	 */
	public function register_all_meta(): void {
		$cpts = Apollo_Meta_Keys::get_registered_cpts();

		foreach ( $cpts as $post_type ) {
			$this->register_meta_for_cpt( $post_type );
		}

		/**
		 * Fires after all Apollo meta is registered.
		 *
		 * @param Apollo_Meta_Registrar $registrar The registrar instance.
		 */
		\do_action( 'apollo_meta_registered', $this );
	}

	/**
	 * Register all meta keys for a specific CPT
	 *
	 * @param string $post_type Post type slug.
	 * @return void
	 */
	public function register_meta_for_cpt( string $post_type ): void {
		$meta_keys = Apollo_Meta_Keys::get_all_for_cpt( $post_type );

		if ( empty( $meta_keys ) ) {
			return;
		}

		foreach ( $meta_keys as $key ) {
			$this->register_single_meta( $post_type, $key );
		}

		$this->registered[ $post_type ] = $meta_keys;
	}

	/**
	 * Register a single meta key
	 *
	 * @param string $post_type Post type slug.
	 * @param string $meta_key  Meta key to register.
	 * @return bool Success.
	 */
	public function register_single_meta( string $post_type, string $meta_key ): bool {
		$definition = Apollo_Meta_Keys::get_definition( $meta_key );

		$args = array(
			'type'              => $definition['type'] ?? 'string',
			'description'       => $this->get_meta_description( $meta_key ),
			'single'            => $definition['single'] ?? true,
			'default'           => $definition['default'] ?? '',
			'show_in_rest'      => $this->get_rest_schema( $meta_key, $definition ),
			'sanitize_callback' => $this->get_sanitize_callback( $definition ),
			'auth_callback'     => array( $this, 'authorize_meta_update' ),
		);

		/**
		 * Filter meta registration args.
		 *
		 * @param array  $args      Registration args.
		 * @param string $meta_key  Meta key.
		 * @param string $post_type Post type.
		 */
		$args = \apply_filters( 'apollo_meta_registration_args', $args, $meta_key, $post_type );

		return \register_post_meta( $post_type, $meta_key, $args );
	}

	/**
	 * Get REST schema for meta key
	 *
	 * @param string     $meta_key   Meta key.
	 * @param array|null $definition Meta definition.
	 * @return bool|array REST schema or false.
	 */
	private function get_rest_schema( string $meta_key, ?array $definition ): bool|array {
		// Keys that should not be exposed in REST.
		$private_keys = array(
			'_event_gestao',
			'_classified_reports',
		);

		if ( \in_array( $meta_key, $private_keys, true ) ) {
			return false;
		}

		$type = $definition['type'] ?? 'string';

		// Basic types can use true.
		if ( \in_array( $type, array( 'string', 'integer', 'number', 'boolean' ), true ) ) {
			return true;
		}

		// Array types need schema.
		if ( $type === 'array' ) {
			return array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'object',
					),
				),
			);
		}

		// Object types.
		if ( $type === 'object' ) {
			return array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			);
		}

		return true;
	}

	/**
	 * Get sanitize callback for meta
	 *
	 * @param array|null $definition Meta definition.
	 * @return callable|null Sanitize callback.
	 */
	private function get_sanitize_callback( ?array $definition ): ?callable {
		if ( ! $definition || ! isset( $definition['sanitize'] ) ) {
			return null;
		}

		$sanitize = $definition['sanitize'];

		// Built-in WordPress functions.
		$builtin = array(
			'sanitize_text_field',
			'sanitize_textarea_field',
			'esc_url_raw',
			'wp_kses_post',
			'absint',
			'intval',
			'floatval',
			'rest_sanitize_boolean',
		);

		if ( \in_array( $sanitize, $builtin, true ) ) {
			return $sanitize;
		}

		// Custom sanitizers.
		return match ( $sanitize ) {
			'array_map_intval'    => fn( $v ) => \is_array( $v ) ? \array_map( 'intval', $v ) : array(),
			'array_map_sanitize'  => fn( $v ) => \is_array( $v ) ? \array_map( 'sanitize_text_field', $v ) : array(),
			'wp_kses_post_deep'   => fn( $v ) => \is_array( $v ) ? \map_deep( $v, 'wp_kses_post' ) : array(),
			default               => null,
		};
	}

	/**
	 * Get description for meta key
	 *
	 * @param string $meta_key Meta key.
	 * @return string Description.
	 */
	private function get_meta_description( string $meta_key ): string {
		$descriptions = array(
			// Events
			'_event_title'          => __( 'Event display title', 'apollo-core' ),
			'_event_banner'         => __( 'Event banner image URL', 'apollo-core' ),
			'_event_video_url'      => __( 'Event video embed URL', 'apollo-core' ),
			'_event_start_date'     => __( 'Event start date (Y-m-d)', 'apollo-core' ),
			'_event_end_date'       => __( 'Event end date (Y-m-d)', 'apollo-core' ),
			'_event_start_time'     => __( 'Event start time (H:i)', 'apollo-core' ),
			'_event_end_time'       => __( 'Event end time (H:i)', 'apollo-core' ),
			'_event_location'       => __( 'Event location name', 'apollo-core' ),
			'_event_latitude'       => __( 'Event latitude coordinate', 'apollo-core' ),
			'_event_longitude'      => __( 'Event longitude coordinate', 'apollo-core' ),
			'_event_dj_ids'         => __( 'Associated DJ post IDs', 'apollo-core' ),
			'_event_local_ids'      => __( 'Associated venue post IDs', 'apollo-core' ),
			'_event_dj_slots'       => __( 'Event timetable with DJ slots', 'apollo-core' ),
			'_event_featured'       => __( 'Whether event is featured', 'apollo-core' ),
			'_favorites_count'      => __( 'Number of users who favorited', 'apollo-core' ),

			// DJs
			'_dj_name'              => __( 'DJ/Artist name', 'apollo-core' ),
			'_dj_bio'               => __( 'DJ biography', 'apollo-core' ),
			'_dj_image'             => __( 'DJ profile image URL', 'apollo-core' ),
			'_dj_instagram'         => __( 'DJ Instagram URL', 'apollo-core' ),
			'_dj_soundcloud'        => __( 'DJ SoundCloud URL', 'apollo-core' ),
			'_dj_spotify'           => __( 'DJ Spotify URL', 'apollo-core' ),

			// Locals
			'_local_name'           => __( 'Venue name', 'apollo-core' ),
			'_local_description'    => __( 'Venue description', 'apollo-core' ),
			'_local_address'        => __( 'Venue address', 'apollo-core' ),
			'_local_latitude'       => __( 'Venue latitude coordinate', 'apollo-core' ),
			'_local_longitude'      => __( 'Venue longitude coordinate', 'apollo-core' ),

			// Classifieds
			'_classified_price'     => __( 'Classified listing price', 'apollo-core' ),
			'_classified_currency'  => __( 'Price currency code', 'apollo-core' ),
			'_classified_views'     => __( 'Number of views', 'apollo-core' ),
			'_classified_season_id' => __( 'Associated event season term ID', 'apollo-core' ),

			// Suppliers
			'_supplier_name'        => __( 'Supplier business name', 'apollo-core' ),
			'_supplier_verified'    => __( 'Whether supplier is verified', 'apollo-core' ),
		);

		return $descriptions[ $meta_key ] ?? \sprintf(
			/* translators: %s: meta key */
			__( 'Apollo meta field: %s', 'apollo-core' ),
			$meta_key
		);
	}

	/**
	 * Authorization callback for meta updates
	 *
	 * @param bool   $allowed  Whether allowed.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool Whether update is authorized.
	 */
	public function authorize_meta_update( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		// Admin always allowed.
		if ( \user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Check edit capability for the post.
		if ( ! \user_can( $user_id, 'edit_post', $post_id ) ) {
			return false;
		}

		// Restricted keys - admin only.
		$admin_only_keys = array(
			'_event_featured',
			'_event_gestao',
			'_supplier_verified',
			'_classified_reports',
		);

		if ( \in_array( $meta_key, $admin_only_keys, true ) ) {
			return \user_can( $user_id, 'manage_options' );
		}

		// Counter keys - system only (not user editable).
		$system_only_keys = array(
			'_favorites_count',
			'_classified_views',
		);

		if ( \in_array( $meta_key, $system_only_keys, true ) ) {
			// Only allow programmatic updates, not direct REST updates.
			return \current_filter() !== 'rest_pre_insert_' . \get_post_type( $post_id );
		}

		return $allowed;
	}

	/**
	 * Register REST fields for deprecated key compatibility
	 *
	 * @return void
	 */
	public function register_rest_fields(): void {
		$deprecated = Apollo_Meta_Keys::get_deprecated_keys();

		foreach ( $deprecated as $old_key => $new_key ) {
			$post_type = $this->get_post_type_for_key( $old_key );

			if ( ! $post_type ) {
				continue;
			}

			// Register deprecated key as read-only alias.
			\register_rest_field(
				$post_type,
				\ltrim( $old_key, '_' ),
				array(
					'get_callback' => function ( $object ) use ( $new_key ) {
						return \get_post_meta( $object['id'], $new_key, true );
					},
					'schema'       => array(
						'description' => \sprintf(
							/* translators: %s: new key */
							__( 'DEPRECATED: Use %s instead.', 'apollo-core' ),
							$new_key
						),
						'type'        => 'string',
						'readonly'    => true,
					),
				)
			);
		}
	}

	/**
	 * Get post type for a meta key
	 *
	 * @param string $meta_key Meta key.
	 * @return string|null Post type or null.
	 */
	private function get_post_type_for_key( string $meta_key ): ?string {
		// Check canonical key.
		$canonical = Apollo_Meta_Keys::get_canonical( $meta_key );

		foreach ( Apollo_Meta_Keys::get_registered_cpts() as $post_type ) {
			$keys = Apollo_Meta_Keys::get_all_for_cpt( $post_type );
			if ( \in_array( $canonical, $keys, true ) ) {
				return $post_type;
			}
		}

		// Fallback for deprecated keys.
		$key_prefix_map = array(
			'_event_' => 'event_listing',
			'_dj_'    => 'event_dj',
			'_local_' => 'event_local',
		);

		foreach ( $key_prefix_map as $prefix => $post_type ) {
			if ( \str_starts_with( $meta_key, $prefix ) ) {
				return $post_type;
			}
		}

		return null;
	}

	/**
	 * Get registered meta for a post type
	 *
	 * @param string $post_type Post type.
	 * @return array<string> Registered meta keys.
	 */
	public function get_registered_for_cpt( string $post_type ): array {
		return $this->registered[ $post_type ] ?? array();
	}

	/**
	 * Check if a meta key is registered for a post type
	 *
	 * @param string $post_type Post type.
	 * @param string $meta_key  Meta key.
	 * @return bool Whether registered.
	 */
	public function is_registered( string $post_type, string $meta_key ): bool {
		$registered = $this->get_registered_for_cpt( $post_type );
		return \in_array( $meta_key, $registered, true );
	}

	/**
	 * Bulk update meta for a post
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Array of meta key => value.
	 * @return array<string, bool> Results keyed by meta key.
	 */
	public function bulk_update( int $post_id, array $meta ): array {
		$results = array();

		foreach ( $meta as $key => $value ) {
			$results[ $key ] = (bool) Apollo_Meta_Keys::update( $post_id, $key, $value );
		}

		return $results;
	}

	/**
	 * Get all meta for a post using canonical keys
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed> Meta values.
	 */
	public function get_all_meta( int $post_id ): array {
		$post_type = \get_post_type( $post_id );
		$keys      = Apollo_Meta_Keys::get_all_for_cpt( $post_type );
		$meta      = array();

		foreach ( $keys as $key ) {
			$meta[ $key ] = Apollo_Meta_Keys::get( $post_id, $key );
		}

		return $meta;
	}
}

// Initialize registrar.
Apollo_Meta_Registrar::get_instance();
