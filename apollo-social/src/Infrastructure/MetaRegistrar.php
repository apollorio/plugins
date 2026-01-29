<?php
/**
 * Apollo Meta Keys Registrar
 *
 * Centralized registration of all post meta and user meta keys.
 * Eliminates "ghost" meta keys and provides type safety.
 *
 * @package Apollo\Social\Infrastructure
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Social\Infrastructure;

/**
 * Registers all Apollo meta keys with proper sanitization and authorization.
 */
final class MetaRegistrar {

	/**
	 * User meta keys with their configuration.
	 *
	 * @var array<string, array{type: string, description: string, sanitize_callback: string|callable, show_in_rest: bool|array}>
	 */
	private const USER_META = array(
		// Profile
		'_apollo_bio'                    => array(
			'type'              => 'string',
			'description'       => 'User biography/description',
			'sanitize_callback' => 'sanitize_textarea_field',
			'show_in_rest'      => true,
		),
		'_apollo_avatar_url'             => array(
			'type'              => 'string',
			'description'       => 'Custom avatar URL',
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
		),
		'_apollo_cover_image'            => array(
			'type'              => 'string',
			'description'       => 'Profile cover image URL',
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
		),
		'_apollo_display_name'           => array(
			'type'              => 'string',
			'description'       => 'Custom display name',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'_apollo_social_links'           => array(
			'type'              => 'object',
			'description'       => 'Social media links',
			'sanitize_callback' => array( self::class, 'sanitizeSocialLinks' ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'instagram' => array( 'type' => 'string' ),
						'twitter'   => array( 'type' => 'string' ),
						'facebook'  => array( 'type' => 'string' ),
						'linkedin'  => array( 'type' => 'string' ),
						'youtube'   => array( 'type' => 'string' ),
						'tiktok'    => array( 'type' => 'string' ),
						'spotify'   => array( 'type' => 'string' ),
						'soundcloud' => array( 'type' => 'string' ),
					),
				),
			),
		),

		// Membership & Roles
		'_apollo_membership_type'        => array(
			'type'              => 'string',
			'description'       => 'User membership type',
			'sanitize_callback' => 'sanitize_key',
			'show_in_rest'      => true,
		),
		'_apollo_membership_expires'     => array(
			'type'              => 'string',
			'description'       => 'Membership expiration date',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		),
		'_apollo_verified'               => array(
			'type'              => 'boolean',
			'description'       => 'User verification status',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		),
		'_apollo_verification_level'     => array(
			'type'              => 'string',
			'description'       => 'Verification level (basic, full, pro)',
			'sanitize_callback' => 'sanitize_key',
			'show_in_rest'      => true,
		),
		'_apollo_badges'                 => array(
			'type'              => 'array',
			'description'       => 'User badges/achievements',
			'sanitize_callback' => array( self::class, 'sanitizeBadges' ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		),

		// Gamification
		'_apollo_points'                 => array(
			'type'              => 'integer',
			'description'       => 'User points total',
			'sanitize_callback' => 'absint',
			'show_in_rest'      => true,
		),
		'_apollo_level'                  => array(
			'type'              => 'integer',
			'description'       => 'User level',
			'sanitize_callback' => 'absint',
			'show_in_rest'      => true,
		),
		'_apollo_xp'                     => array(
			'type'              => 'integer',
			'description'       => 'Experience points',
			'sanitize_callback' => 'absint',
			'show_in_rest'      => true,
		),

		// Privacy & Settings
		'_apollo_privacy_settings'       => array(
			'type'              => 'object',
			'description'       => 'User privacy settings',
			'sanitize_callback' => array( self::class, 'sanitizePrivacySettings' ),
			'show_in_rest'      => false,
		),
		'_apollo_notification_prefs'     => array(
			'type'              => 'object',
			'description'       => 'Notification preferences',
			'sanitize_callback' => array( self::class, 'sanitizeNotificationPrefs' ),
			'show_in_rest'      => false,
		),
		'_apollo_language'               => array(
			'type'              => 'string',
			'description'       => 'Preferred language',
			'sanitize_callback' => 'sanitize_key',
			'show_in_rest'      => true,
		),

		// Location
		'_apollo_city'                   => array(
			'type'              => 'string',
			'description'       => 'User city',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'_apollo_state'                  => array(
			'type'              => 'string',
			'description'       => 'User state/region',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'_apollo_country'                => array(
			'type'              => 'string',
			'description'       => 'User country',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'_apollo_coordinates'            => array(
			'type'              => 'object',
			'description'       => 'User location coordinates',
			'sanitize_callback' => array( self::class, 'sanitizeCoordinates' ),
			'show_in_rest'      => false,
		),

		// Onboarding
		'_apollo_onboarding_complete'    => array(
			'type'              => 'boolean',
			'description'       => 'Onboarding completion status',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => false,
		),
		'_apollo_onboarding_step'        => array(
			'type'              => 'integer',
			'description'       => 'Current onboarding step',
			'sanitize_callback' => 'absint',
			'show_in_rest'      => false,
		),
		'_apollo_first_login'            => array(
			'type'              => 'string',
			'description'       => 'First login timestamp',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		),

		// HUB Editor
		'_apollo_hub_avatar'             => array(
			'type'              => 'string',
			'description'       => 'HUB avatar URL',
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
		),
		'_apollo_hub_bio'                => array(
			'type'              => 'string',
			'description'       => 'HUB bio text',
			'sanitize_callback' => 'sanitize_textarea_field',
			'show_in_rest'      => true,
		),
		'_apollo_hub_blocks'             => array(
			'type'              => 'string',
			'description'       => 'HUB blocks JSON',
			'sanitize_callback' => array( self::class, 'sanitizeJson' ),
			'show_in_rest'      => false,
		),
		'_apollo_hub_bg'                 => array(
			'type'              => 'string',
			'description'       => 'HUB background',
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
		),
		'_apollo_hub_texture'            => array(
			'type'              => 'string',
			'description'       => 'HUB texture style',
			'sanitize_callback' => 'sanitize_key',
			'show_in_rest'      => true,
		),

		// Activity tracking
		'_apollo_last_active'            => array(
			'type'              => 'string',
			'description'       => 'Last activity timestamp',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		),
		'_apollo_login_count'            => array(
			'type'              => 'integer',
			'description'       => 'Total login count',
			'sanitize_callback' => 'absint',
			'show_in_rest'      => false,
		),
	);

	/**
	 * Post meta keys for various CPTs.
	 *
	 * @var array<string, array<string, array{type: string, description: string, sanitize_callback: string|callable, show_in_rest: bool|array}>>
	 */
	private const POST_META = array(
		// Event Listing meta
		'event_listing'      => array(
			'_event_start_date'       => array(
				'type'              => 'string',
				'description'       => 'Event start date',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_event_end_date'         => array(
				'type'              => 'string',
				'description'       => 'Event end date',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_event_location'         => array(
				'type'              => 'string',
				'description'       => 'Event location name',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_event_address'          => array(
				'type'              => 'string',
				'description'       => 'Event address',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
			),
			'_event_coordinates'      => array(
				'type'              => 'object',
				'description'       => 'Event coordinates',
				'sanitize_callback' => array( self::class, 'sanitizeCoordinates' ),
				'show_in_rest'      => false,
			),
			'_event_ticket_url'       => array(
				'type'              => 'string',
				'description'       => 'Ticket purchase URL',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
			),
			'_event_price'            => array(
				'type'              => 'number',
				'description'       => 'Event price',
				'sanitize_callback' => 'floatval',
				'show_in_rest'      => true,
			),
			'_event_capacity'         => array(
				'type'              => 'integer',
				'description'       => 'Event capacity',
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			),
		),

		// Document CPT meta
		'apollo_document'    => array(
			'_apollo_doc_file_id'     => array(
				'type'              => 'string',
				'description'       => 'Document file ID',
				'sanitize_callback' => 'sanitize_key',
				'show_in_rest'      => true,
			),
			'_apollo_doc_type'        => array(
				'type'              => 'string',
				'description'       => 'Document type',
				'sanitize_callback' => 'sanitize_key',
				'show_in_rest'      => true,
			),
			'_apollo_doc_state'       => array(
				'type'              => 'string',
				'description'       => 'Document state',
				'sanitize_callback' => 'sanitize_key',
				'show_in_rest'      => true,
			),
			'_apollo_doc_library'     => array(
				'type'              => 'string',
				'description'       => 'Document library',
				'sanitize_callback' => 'sanitize_key',
				'show_in_rest'      => true,
			),
			'_apollo_doc_signatures'  => array(
				'type'              => 'array',
				'description'       => 'Document signatures',
				'sanitize_callback' => array( self::class, 'sanitizeSignatures' ),
				'show_in_rest'      => false,
			),
			'_apollo_doc_hash'        => array(
				'type'              => 'string',
				'description'       => 'Document hash',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => false,
			),
			'_apollo_doc_protocol'    => array(
				'type'              => 'string',
				'description'       => 'Document protocol number',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
		),

		// Classified CPT meta
		'apollo_classified'  => array(
			'_apollo_classified_price'    => array(
				'type'              => 'number',
				'description'       => 'Classified price',
				'sanitize_callback' => 'floatval',
				'show_in_rest'      => true,
			),
			'_apollo_classified_location' => array(
				'type'              => 'string',
				'description'       => 'Classified location',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_apollo_classified_contact'  => array(
				'type'              => 'string',
				'description'       => 'Contact information',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			),
			'_apollo_classified_views'    => array(
				'type'              => 'integer',
				'description'       => 'View count',
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			),
		),

		// Home CPT meta (Builder)
		'apollo_home'        => array(
			'_apollo_builder_layout'  => array(
				'type'              => 'string',
				'description'       => 'Builder layout JSON',
				'sanitize_callback' => array( self::class, 'sanitizeJson' ),
				'show_in_rest'      => false,
			),
			'_apollo_builder_theme'   => array(
				'type'              => 'string',
				'description'       => 'Builder theme ID',
				'sanitize_callback' => 'sanitize_key',
				'show_in_rest'      => true,
			),
			'_apollo_builder_bg'      => array(
				'type'              => 'string',
				'description'       => 'Builder background',
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
			),
		),
	);

	/**
	 * Register all meta keys.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'registerUserMeta' ), 5 );
		add_action( 'init', array( self::class, 'registerPostMeta' ), 5 );
	}

	/**
	 * Register user meta keys.
	 *
	 * @return void
	 */
	public static function registerUserMeta(): void {
		foreach ( self::USER_META as $key => $config ) {
			register_meta(
				'user',
				$key,
				array(
					'type'              => $config['type'],
					'description'       => $config['description'],
					'single'            => true,
					'sanitize_callback' => $config['sanitize_callback'],
					'auth_callback'     => array( self::class, 'authCallbackUserMeta' ),
					'show_in_rest'      => $config['show_in_rest'],
				)
			);
		}
	}

	/**
	 * Register post meta keys.
	 *
	 * @return void
	 */
	public static function registerPostMeta(): void {
		foreach ( self::POST_META as $post_type => $meta_keys ) {
			foreach ( $meta_keys as $key => $config ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $config['type'],
						'description'       => $config['description'],
						'single'            => true,
						'sanitize_callback' => $config['sanitize_callback'],
						'auth_callback'     => array( self::class, 'authCallbackPostMeta' ),
						'show_in_rest'      => $config['show_in_rest'],
					)
				);
			}
		}
	}

	/**
	 * Authorization callback for user meta.
	 *
	 * @param bool   $allowed  Whether the user can add the meta.
	 * @param string $meta_key The meta key.
	 * @param int    $user_id  The user ID.
	 * @return bool
	 */
	public static function authCallbackUserMeta( bool $allowed, string $meta_key, int $user_id ): bool {
		// Users can edit their own meta
		if ( get_current_user_id() === $user_id ) {
			return true;
		}

		// Admins can edit any user meta
		return current_user_can( 'edit_users' );
	}

	/**
	 * Authorization callback for post meta.
	 *
	 * @param bool   $allowed  Whether the user can add the meta.
	 * @param string $meta_key The meta key.
	 * @param int    $post_id  The post ID.
	 * @return bool
	 */
	public static function authCallbackPostMeta( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	// =========================================================================
	// Sanitization Callbacks
	// =========================================================================

	/**
	 * Sanitize social links object.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizeSocialLinks( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		$allowed   = array( 'instagram', 'twitter', 'facebook', 'linkedin', 'youtube', 'tiktok', 'spotify', 'soundcloud' );

		foreach ( $allowed as $platform ) {
			if ( isset( $value[ $platform ] ) ) {
				$sanitized[ $platform ] = esc_url_raw( $value[ $platform ] );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize badges array.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizeBadges( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( 'sanitize_key', $value );
	}

	/**
	 * Sanitize privacy settings.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizePrivacySettings( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$defaults = array(
			'profile_visibility'   => 'public',
			'show_online_status'   => true,
			'allow_messages'       => true,
			'show_activity'        => true,
			'show_connections'     => true,
		);

		$sanitized = array();
		foreach ( $defaults as $key => $default ) {
			if ( isset( $value[ $key ] ) ) {
				$sanitized[ $key ] = is_bool( $default )
					? (bool) $value[ $key ]
					: sanitize_key( $value[ $key ] );
			} else {
				$sanitized[ $key ] = $default;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize notification preferences.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizeNotificationPrefs( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $key => $enabled ) {
			$sanitized[ sanitize_key( $key ) ] = (bool) $enabled;
		}

		return $sanitized;
	}

	/**
	 * Sanitize coordinates.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizeCoordinates( $value ): array {
		if ( ! is_array( $value ) ) {
			return array( 'lat' => 0.0, 'lng' => 0.0 );
		}

		return array(
			'lat' => isset( $value['lat'] ) ? (float) $value['lat'] : 0.0,
			'lng' => isset( $value['lng'] ) ? (float) $value['lng'] : 0.0,
		);
	}

	/**
	 * Sanitize signatures array.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array
	 */
	public static function sanitizeSignatures( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map(
			function ( $sig ) {
				return array(
					'id'             => sanitize_key( $sig['id'] ?? '' ),
					'signer_name'    => sanitize_text_field( $sig['signer_name'] ?? '' ),
					'signer_email'   => sanitize_email( $sig['signer_email'] ?? '' ),
					'signed_at'      => sanitize_text_field( $sig['signed_at'] ?? '' ),
					'method'         => sanitize_key( $sig['method'] ?? 'electronic' ),
				);
			},
			$value
		);
	}

	/**
	 * Sanitize JSON string.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string
	 */
	public static function sanitizeJson( $value ): string {
		if ( ! is_string( $value ) ) {
			return '{}';
		}

		// Validate JSON
		$decoded = json_decode( $value, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return '{}';
		}

		// Re-encode to ensure clean JSON
		return wp_json_encode( $decoded ) ?: '{}';
	}

	/**
	 * Get all registered user meta keys.
	 *
	 * @return array<string>
	 */
	public static function getUserMetaKeys(): array {
		return array_keys( self::USER_META );
	}

	/**
	 * Get all registered post meta keys for a post type.
	 *
	 * @param string $post_type The post type.
	 * @return array<string>
	 */
	public static function getPostMetaKeys( string $post_type ): array {
		return array_keys( self::POST_META[ $post_type ] ?? array() );
	}
}
