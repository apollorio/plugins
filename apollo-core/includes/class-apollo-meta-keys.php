<?php
/**
 * Apollo Meta Keys Registry
 *
 * Centralized constants for all meta keys across Apollo plugins.
 * Provides canonical key mapping and deprecation tracking.
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
 * Class Apollo_Meta_Keys
 *
 * Registry of all meta key constants with deprecation mapping.
 */
final class Apollo_Meta_Keys {

	// =========================================================================
	// EVENT META KEYS (event_listing)
	// =========================================================================

	// Core Event Fields
	public const EVENT_TITLE      = '_event_title';
	public const EVENT_BANNER     = '_event_banner';
	public const EVENT_VIDEO_URL  = '_event_video_url';
	public const EVENT_START_DATE = '_event_start_date';
	public const EVENT_END_DATE   = '_event_end_date';
	public const EVENT_START_TIME = '_event_start_time';
	public const EVENT_END_TIME   = '_event_end_time';

	// Location Fields (CANONICAL)
	public const EVENT_LOCATION  = '_event_location';
	public const EVENT_COUNTRY   = '_event_country';
	public const EVENT_CITY      = '_event_city';
	public const EVENT_ADDRESS   = '_event_address';
	public const EVENT_LATITUDE  = '_event_latitude';  // CANONICAL
	public const EVENT_LONGITUDE = '_event_longitude'; // CANONICAL

	// Tickets & Links
	public const EVENT_TICKETS_EXT    = '_tickets_ext';
	public const EVENT_CUPOM          = '_cupom_ario';
	public const EVENT_LINK_INSTAGRAM = '_event_link_instagram';
	public const EVENT_LINK_RA        = '_event_link_ra';
	public const EVENT_LINK_SHOTGUN   = '_event_link_shotgun';
	public const EVENT_LINK_SYMPLA    = '_event_link_sympla';
	public const EVENT_TICKET_URL     = '_event_ticket_url';

	// Relationships
	public const EVENT_DJ_IDS    = '_event_dj_ids';
	public const EVENT_LOCAL_IDS = '_event_local_ids';
	public const EVENT_TIMETABLE = '_event_dj_slots'; // CANONICAL (not _event_timetable)

	// Images
	public const EVENT_PROMO_IMAGES = '_3_imagens_promo';
	public const EVENT_FINAL_IMAGE  = '_imagem_final';

	// Status & Counters
	public const EVENT_FEATURED   = '_event_featured';
	public const EVENT_GESTAO     = '_event_gestao';
	public const EVENT_SEASON_ID  = '_event_season_id';
	public const EVENT_FAVORITES  = '_favorites_count';
	public const EVENT_INTERESTED = '_event_interested_users';

	// =========================================================================
	// DEPRECATED EVENT META KEYS (kept for migration)
	// =========================================================================

	public const DEPRECATED_EVENT_LAT       = '_event_lat';
	public const DEPRECATED_EVENT_LNG       = '_event_lng';
	public const DEPRECATED_EVENT_TIMETABLE = '_event_timetable';
	public const DEPRECATED_EVENT_DJS       = '_event_djs';
	public const DEPRECATED_EVENT_LOCAL     = '_event_local';

	// =========================================================================
	// DJ META KEYS (event_dj)
	// =========================================================================

	public const DJ_NAME    = '_dj_name';
	public const DJ_BIO     = '_dj_bio';
	public const DJ_IMAGE   = '_dj_image';
	public const DJ_WEBSITE = '_dj_website';

	// Social Links
	public const DJ_INSTAGRAM = '_dj_instagram';
	public const DJ_FACEBOOK  = '_dj_facebook';
	public const DJ_TWITTER   = '_dj_twitter';
	public const DJ_TIKTOK    = '_dj_tiktok';

	// Music Platforms
	public const DJ_SOUNDCLOUD       = '_dj_soundcloud';
	public const DJ_MIXCLOUD         = '_dj_mixcloud';
	public const DJ_SPOTIFY          = '_dj_spotify';
	public const DJ_YOUTUBE          = '_dj_youtube';
	public const DJ_BANDCAMP         = '_dj_bandcamp';
	public const DJ_BEATPORT         = '_dj_beatport';
	public const DJ_RESIDENT_ADVISOR = '_dj_resident_advisor';

	// Professional
	public const DJ_SET_URL   = '_dj_set_url';
	public const DJ_MIX_URL   = '_dj_mix_url';
	public const DJ_MEDIA_KIT = '_dj_media_kit_url';
	public const DJ_RIDER     = '_dj_rider_url';

	// Projects
	public const DJ_PROJECT_1 = '_dj_original_project_1';
	public const DJ_PROJECT_2 = '_dj_original_project_2';
	public const DJ_PROJECT_3 = '_dj_original_project_3';

	// =========================================================================
	// LOCAL/VENUE META KEYS (event_local)
	// =========================================================================

	public const LOCAL_NAME        = '_local_name';
	public const LOCAL_DESCRIPTION = '_local_description';
	public const LOCAL_ADDRESS     = '_local_address';
	public const LOCAL_CITY        = '_local_city';
	public const LOCAL_STATE       = '_local_state';
	public const LOCAL_LATITUDE    = '_local_latitude';  // CANONICAL
	public const LOCAL_LONGITUDE   = '_local_longitude'; // CANONICAL
	public const LOCAL_WEBSITE     = '_local_website';
	public const LOCAL_FACEBOOK    = '_local_facebook';
	public const LOCAL_INSTAGRAM   = '_local_instagram';

	// Gallery
	public const LOCAL_IMAGE_1 = '_local_image_1';
	public const LOCAL_IMAGE_2 = '_local_image_2';
	public const LOCAL_IMAGE_3 = '_local_image_3';
	public const LOCAL_IMAGE_4 = '_local_image_4';
	public const LOCAL_IMAGE_5 = '_local_image_5';

	// Deprecated Local Keys
	public const DEPRECATED_LOCAL_LAT = '_local_lat';
	public const DEPRECATED_LOCAL_LNG = '_local_lng';

	// =========================================================================
	// CLASSIFIED META KEYS (apollo_classified)
	// =========================================================================

	public const CLASSIFIED_PRICE        = '_classified_price';
	public const CLASSIFIED_CURRENCY     = '_classified_currency';
	public const CLASSIFIED_LOCATION     = '_classified_location_text';
	public const CLASSIFIED_CONTACT_PREF = '_classified_contact_pref';
	public const CLASSIFIED_EVENT_DATE   = '_classified_event_date';
	public const CLASSIFIED_EVENT_TITLE  = '_classified_event_title';
	public const CLASSIFIED_START_DATE   = '_classified_start_date';
	public const CLASSIFIED_END_DATE     = '_classified_end_date';
	public const CLASSIFIED_CAPACITY     = '_classified_capacity';
	public const CLASSIFIED_GALLERY      = '_classified_gallery';
	public const CLASSIFIED_VIEWS        = '_classified_views';
	public const CLASSIFIED_SAFETY_ACK   = '_classified_safety_acknowledged';
	public const CLASSIFIED_SEASON_ID    = '_classified_season_id';
	public const CLASSIFIED_REPORTS      = '_classified_reports';

	// =========================================================================
	// SUPPLIER META KEYS (apollo_supplier)
	// =========================================================================

	public const SUPPLIER_NAME        = '_supplier_name';
	public const SUPPLIER_DESCRIPTION = '_supplier_description';
	public const SUPPLIER_PHONE       = '_supplier_phone';
	public const SUPPLIER_EMAIL       = '_supplier_email';
	public const SUPPLIER_WEBSITE     = '_supplier_website';
	public const SUPPLIER_INSTAGRAM   = '_supplier_instagram';
	public const SUPPLIER_FACEBOOK    = '_supplier_facebook';
	public const SUPPLIER_ADDRESS     = '_supplier_address';
	public const SUPPLIER_LATITUDE    = '_supplier_latitude';
	public const SUPPLIER_LONGITUDE   = '_supplier_longitude';
	public const SUPPLIER_FEATURED    = '_supplier_featured';
	public const SUPPLIER_VERIFIED    = '_supplier_verified';

	// =========================================================================
	// BUILDER META KEYS (apollo_home)
	// =========================================================================

	public const BUILDER_CONTENT    = '_apollo_builder_content';
	public const BUILDER_CSS        = '_apollo_builder_css';
	public const BUILDER_BACKGROUND = '_apollo_builder_background';
	public const BUILDER_TRAX       = '_apollo_builder_trax';

	// =========================================================================
	// CENA RIO META KEYS (cena_document, cena_event_plan)
	// =========================================================================

	public const CENA_IS_LIBRARY = '_cena_is_library';
	public const CENA_PLAN_DATE  = '_cena_plan_date';

	// =========================================================================
	// EMAIL TEMPLATE META KEYS (apollo_email_temp)
	// =========================================================================

	public const EMAIL_TEMPLATE_SLUG     = '_apollo_template_slug';
	public const EMAIL_FLOW_DEFAULT      = '_apollo_flow_default';
	public const EMAIL_TEMPLATE_LANGUAGE = '_apollo_template_language';

	// =========================================================================
	// DEPRECATION MAPPING
	// =========================================================================

	/**
	 * Map of deprecated keys to their canonical replacements.
	 *
	 * @var array<string, string>
	 */
	private const DEPRECATION_MAP = array(
		'_event_lat'       => '_event_latitude',
		'_event_lng'       => '_event_longitude',
		'_event_timetable' => '_event_dj_slots',
		'_event_djs'       => '_event_dj_ids',
		'_event_local'     => '_event_local_ids',
		'_local_lat'       => '_local_latitude',
		'_local_lng'       => '_local_longitude',
	);

	/**
	 * Post type to meta keys mapping.
	 *
	 * @var array<string, array<string>>
	 */
	private const CPT_META_MAP = array(
		'event_listing'     => array(
			self::EVENT_TITLE,
			self::EVENT_BANNER,
			self::EVENT_VIDEO_URL,
			self::EVENT_START_DATE,
			self::EVENT_END_DATE,
			self::EVENT_START_TIME,
			self::EVENT_END_TIME,
			self::EVENT_LOCATION,
			self::EVENT_COUNTRY,
			self::EVENT_CITY,
			self::EVENT_ADDRESS,
			self::EVENT_LATITUDE,
			self::EVENT_LONGITUDE,
			self::EVENT_TICKETS_EXT,
			self::EVENT_CUPOM,
			self::EVENT_DJ_IDS,
			self::EVENT_LOCAL_IDS,
			self::EVENT_TIMETABLE,
			self::EVENT_PROMO_IMAGES,
			self::EVENT_FINAL_IMAGE,
			self::EVENT_FEATURED,
			self::EVENT_GESTAO,
			self::EVENT_SEASON_ID,
			self::EVENT_FAVORITES,
			self::EVENT_INTERESTED,
			self::EVENT_LINK_INSTAGRAM,
			self::EVENT_LINK_RA,
			self::EVENT_LINK_SHOTGUN,
			self::EVENT_LINK_SYMPLA,
			self::EVENT_TICKET_URL,
		),
		'event_dj'          => array(
			self::DJ_NAME,
			self::DJ_BIO,
			self::DJ_IMAGE,
			self::DJ_WEBSITE,
			self::DJ_INSTAGRAM,
			self::DJ_FACEBOOK,
			self::DJ_TWITTER,
			self::DJ_TIKTOK,
			self::DJ_SOUNDCLOUD,
			self::DJ_MIXCLOUD,
			self::DJ_SPOTIFY,
			self::DJ_YOUTUBE,
			self::DJ_BANDCAMP,
			self::DJ_BEATPORT,
			self::DJ_RESIDENT_ADVISOR,
			self::DJ_SET_URL,
			self::DJ_MIX_URL,
			self::DJ_MEDIA_KIT,
			self::DJ_RIDER,
			self::DJ_PROJECT_1,
			self::DJ_PROJECT_2,
			self::DJ_PROJECT_3,
		),
		'event_local'       => array(
			self::LOCAL_NAME,
			self::LOCAL_DESCRIPTION,
			self::LOCAL_ADDRESS,
			self::LOCAL_CITY,
			self::LOCAL_STATE,
			self::LOCAL_LATITUDE,
			self::LOCAL_LONGITUDE,
			self::LOCAL_WEBSITE,
			self::LOCAL_FACEBOOK,
			self::LOCAL_INSTAGRAM,
			self::LOCAL_IMAGE_1,
			self::LOCAL_IMAGE_2,
			self::LOCAL_IMAGE_3,
			self::LOCAL_IMAGE_4,
			self::LOCAL_IMAGE_5,
		),
		'apollo_classified' => array(
			self::CLASSIFIED_PRICE,
			self::CLASSIFIED_CURRENCY,
			self::CLASSIFIED_LOCATION,
			self::CLASSIFIED_CONTACT_PREF,
			self::CLASSIFIED_EVENT_DATE,
			self::CLASSIFIED_EVENT_TITLE,
			self::CLASSIFIED_START_DATE,
			self::CLASSIFIED_END_DATE,
			self::CLASSIFIED_CAPACITY,
			self::CLASSIFIED_GALLERY,
			self::CLASSIFIED_VIEWS,
			self::CLASSIFIED_SAFETY_ACK,
			self::CLASSIFIED_SEASON_ID,
			self::CLASSIFIED_REPORTS,
		),
		'apollo_supplier'   => array(
			self::SUPPLIER_NAME,
			self::SUPPLIER_DESCRIPTION,
			self::SUPPLIER_PHONE,
			self::SUPPLIER_EMAIL,
			self::SUPPLIER_WEBSITE,
			self::SUPPLIER_INSTAGRAM,
			self::SUPPLIER_FACEBOOK,
			self::SUPPLIER_ADDRESS,
			self::SUPPLIER_LATITUDE,
			self::SUPPLIER_LONGITUDE,
			self::SUPPLIER_FEATURED,
			self::SUPPLIER_VERIFIED,
		),
		'apollo_home'       => array(
			self::BUILDER_CONTENT,
			self::BUILDER_CSS,
			self::BUILDER_BACKGROUND,
			self::BUILDER_TRAX,
		),
		'cena_document'     => array(
			self::CENA_IS_LIBRARY,
		),
		'cena_event_plan'   => array(
			self::CENA_PLAN_DATE,
		),
		'apollo_email_temp' => array(
			self::EMAIL_TEMPLATE_SLUG,
			self::EMAIL_FLOW_DEFAULT,
			self::EMAIL_TEMPLATE_LANGUAGE,
		),
	);

	/**
	 * Meta key type definitions for registration.
	 *
	 * @var array<string, array{type: string, single: bool, sanitize: string, default: mixed}>
	 */
	private const META_DEFINITIONS = array(
		// Events - strings
		'_event_title'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_banner'         => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_event_video_url'      => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_event_start_date'     => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_end_date'       => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_start_time'     => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_end_time'       => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_location'       => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_country'        => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_city'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_address'        => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_textarea_field',
			'default'  => '',
		),
		'_event_latitude'       => array(
			'type'     => 'number',
			'single'   => true,
			'sanitize' => 'floatval',
			'default'  => 0.0,
		),
		'_event_longitude'      => array(
			'type'     => 'number',
			'single'   => true,
			'sanitize' => 'floatval',
			'default'  => 0.0,
		),
		'_tickets_ext'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_cupom_ario'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_event_dj_ids'         => array(
			'type'     => 'array',
			'single'   => true,
			'sanitize' => 'array_map_intval',
			'default'  => array(),
		),
		'_event_local_ids'      => array(
			'type'     => 'array',
			'single'   => true,
			'sanitize' => 'array_map_intval',
			'default'  => array(),
		),
		'_event_dj_slots'       => array(
			'type'     => 'array',
			'single'   => true,
			'sanitize' => 'wp_kses_post_deep',
			'default'  => array(),
		),
		'_event_featured'       => array(
			'type'     => 'boolean',
			'single'   => true,
			'sanitize' => 'rest_sanitize_boolean',
			'default'  => false,
		),
		'_event_gestao'         => array(
			'type'     => 'array',
			'single'   => true,
			'sanitize' => 'array_map_sanitize',
			'default'  => array(),
		),
		'_event_season_id'      => array(
			'type'     => 'integer',
			'single'   => true,
			'sanitize' => 'absint',
			'default'  => 0,
		),
		'_favorites_count'      => array(
			'type'     => 'integer',
			'single'   => true,
			'sanitize' => 'absint',
			'default'  => 0,
		),

		// DJ - strings
		'_dj_name'              => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_dj_bio'               => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'wp_kses_post',
			'default'  => '',
		),
		'_dj_image'             => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_website'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_instagram'         => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_facebook'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_twitter'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_tiktok'            => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_soundcloud'        => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_mixcloud'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_spotify'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_youtube'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_bandcamp'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_beatport'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_resident_advisor'  => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_set_url'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_mix_url'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_media_kit_url'     => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_dj_rider_url'         => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),

		// Local/Venue
		'_local_name'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_local_description'    => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'wp_kses_post',
			'default'  => '',
		),
		'_local_address'        => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_textarea_field',
			'default'  => '',
		),
		'_local_city'           => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_local_state'          => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => '',
		),
		'_local_latitude'       => array(
			'type'     => 'number',
			'single'   => true,
			'sanitize' => 'floatval',
			'default'  => 0.0,
		),
		'_local_longitude'      => array(
			'type'     => 'number',
			'single'   => true,
			'sanitize' => 'floatval',
			'default'  => 0.0,
		),
		'_local_website'        => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_local_facebook'       => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),
		'_local_instagram'      => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'esc_url_raw',
			'default'  => '',
		),

		// Classified
		'_classified_price'     => array(
			'type'     => 'number',
			'single'   => true,
			'sanitize' => 'floatval',
			'default'  => 0.0,
		),
		'_classified_currency'  => array(
			'type'     => 'string',
			'single'   => true,
			'sanitize' => 'sanitize_text_field',
			'default'  => 'BRL',
		),
		'_classified_views'     => array(
			'type'     => 'integer',
			'single'   => true,
			'sanitize' => 'absint',
			'default'  => 0,
		),
		'_classified_season_id' => array(
			'type'     => 'integer',
			'single'   => true,
			'sanitize' => 'absint',
			'default'  => 0,
		),
	);

	/**
	 * Get canonical key for a potentially deprecated key.
	 *
	 * @param string $key Meta key to check.
	 * @return string Canonical key (original if not deprecated).
	 */
	public static function get_canonical( string $key ): string {
		return self::DEPRECATION_MAP[ $key ] ?? $key;
	}

	/**
	 * Check if a key is deprecated.
	 *
	 * @param string $key Meta key to check.
	 * @return bool True if deprecated.
	 */
	public static function is_deprecated( string $key ): bool {
		return isset( self::DEPRECATION_MAP[ $key ] );
	}

	/**
	 * Get replacement for deprecated key.
	 *
	 * @param string $key Deprecated meta key.
	 * @return string|null Replacement key or null if not deprecated.
	 */
	public static function get_replacement( string $key ): ?string {
		return self::DEPRECATION_MAP[ $key ] ?? null;
	}

	/**
	 * Get all deprecated keys.
	 *
	 * @return array<string, string> Map of deprecated to canonical keys.
	 */
	public static function get_deprecated_keys(): array {
		return self::DEPRECATION_MAP;
	}

	/**
	 * Get all meta keys for a CPT.
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string> Array of meta keys.
	 */
	public static function get_all_for_cpt( string $post_type ): array {
		return self::CPT_META_MAP[ $post_type ] ?? array();
	}

	/**
	 * Get all registered CPTs with meta keys.
	 *
	 * @return array<string> Array of post type slugs.
	 */
	public static function get_registered_cpts(): array {
		return \array_keys( self::CPT_META_MAP );
	}

	/**
	 * Get meta definition for a key.
	 *
	 * @param string $key Meta key.
	 * @return array|null Definition array or null if not found.
	 */
	public static function get_definition( string $key ): ?array {
		return self::META_DEFINITIONS[ $key ] ?? null;
	}

	/**
	 * Get all meta definitions.
	 *
	 * @return array<string, array> All meta definitions.
	 */
	public static function get_all_definitions(): array {
		return self::META_DEFINITIONS;
	}

	/**
	 * Get post meta with automatic canonical key resolution.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (deprecated keys auto-resolved).
	 * @param bool   $single  Return single value.
	 * @return mixed Meta value.
	 */
	public static function get( int $post_id, string $key, bool $single = true ): mixed {
		$canonical = self::get_canonical( $key );

		// Try canonical first.
		$value = \get_post_meta( $post_id, $canonical, $single );

		// Fallback to deprecated if canonical empty and different.
		if ( empty( $value ) && $canonical !== $key ) {
			$value = \get_post_meta( $post_id, $key, $single );
		}

		return $value;
	}

	/**
	 * Update post meta using canonical key.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key (auto-resolved to canonical).
	 * @param mixed  $value   Meta value.
	 * @return int|bool Meta ID or false on failure.
	 */
	public static function update( int $post_id, string $key, mixed $value ): int|bool {
		$canonical  = self::get_canonical( $key );
		$definition = self::get_definition( $canonical );

		// Apply sanitization if defined.
		if ( $definition && isset( $definition['sanitize'] ) ) {
			$value = self::sanitize_value( $value, $definition['sanitize'] );
		}

		return \update_post_meta( $post_id, $canonical, $value );
	}

	/**
	 * Delete post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return bool True on success.
	 */
	public static function delete( int $post_id, string $key ): bool {
		$canonical = self::get_canonical( $key );
		return \delete_post_meta( $post_id, $canonical );
	}

	/**
	 * Sanitize value based on sanitize callback name.
	 *
	 * @param mixed  $value    Value to sanitize.
	 * @param string $callback Callback name.
	 * @return mixed Sanitized value.
	 */
	private static function sanitize_value( mixed $value, string $callback ): mixed {
		return match ( $callback ) {
			'sanitize_text_field'     => \sanitize_text_field( (string) $value ),
			'sanitize_textarea_field' => \sanitize_textarea_field( (string) $value ),
			'esc_url_raw'             => \esc_url_raw( (string) $value ),
			'wp_kses_post'            => \wp_kses_post( (string) $value ),
			'absint'                  => \absint( $value ),
			'intval'                  => \intval( $value ),
			'floatval'                => \floatval( $value ),
			'rest_sanitize_boolean'   => \rest_sanitize_boolean( $value ),
			'array_map_intval'        => \is_array( $value ) ? \array_map( 'intval', $value ) : array(),
			'array_map_sanitize'      => \is_array( $value ) ? \array_map( 'sanitize_text_field', $value ) : array(),
			'wp_kses_post_deep'       => \is_array( $value ) ? \map_deep( $value, 'wp_kses_post' ) : array(),
			default                   => $value,
		};
	}

	/**
	 * Get location keys (latitude/longitude) for a CPT.
	 *
	 * @param string $post_type Post type.
	 * @return array{lat: string, lng: string}|null Location keys or null.
	 */
	public static function get_location_keys( string $post_type ): ?array {
		return match ( $post_type ) {
			'event_listing'    => array(
				'lat' => self::EVENT_LATITUDE,
				'lng' => self::EVENT_LONGITUDE,
			),
			'event_local'      => array(
				'lat' => self::LOCAL_LATITUDE,
				'lng' => self::LOCAL_LONGITUDE,
			),
			'apollo_supplier'  => array(
				'lat' => self::SUPPLIER_LATITUDE,
				'lng' => self::SUPPLIER_LONGITUDE,
			),
			default            => null,
		};
	}
}
