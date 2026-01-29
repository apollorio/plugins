<?php
/**
 * Apollo Identifiers - Single Source of Truth
 *
 * Centralizes all shared identifiers across the Apollo ecosystem to prevent
 * duplications, collisions, and hard-coded strings scattered across plugins.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo_Identifiers class
 *
 * Provides constants and helpers for all shared identifiers.
 * Use these instead of hard-coded strings throughout the ecosystem.
 */
final class Apollo_Identifiers {

	// =========================================================================
	// REST API
	// =========================================================================

	/**
	 * REST namespace
	 */
	public const REST_NAMESPACE = 'apollo/v1';

	/**
	 * REST routes (canonical patterns, without namespace)
	 */
	public const REST_ROUTE_EVENTOS       = 'eventos';
	public const REST_ROUTE_EVENTO_SINGLE = 'evento/(?P<id>\d+)';
	public const REST_ROUTE_CATEGORIAS    = 'categorias';
	public const REST_ROUTE_LOCAIS        = 'locais';
	public const REST_ROUTE_MEUS_EVENTOS  = 'meus-eventos';
	public const REST_ROUTE_NAVBAR_APPS   = 'navbar/apps';

	// =========================================================================
	// POST TYPES
	// =========================================================================

	/**
	 * Post type slugs (CPT names registered with WordPress)
	 */
	public const CPT_EVENT_LISTING    = 'event_listing';
	public const CPT_EVENT_DJ         = 'event_dj';
	public const CPT_EVENT_LOCAL      = 'event_local';
	public const CPT_CLASSIFIED       = 'apollo_classified';
	public const CPT_SUPPLIER         = 'apollo_supplier';
	public const CPT_SOCIAL_POST      = 'apollo_social_post';
	public const CPT_USER_PAGE        = 'user_page';
	public const CPT_DOCUMENT         = 'apollo_document';
	public const CPT_CENA_DOCUMENT    = 'cena_document';
	public const CPT_CENA_EVENT_PLAN  = 'cena_event_plan';
	public const CPT_EMAIL_TEMPLATE   = 'apollo_email_template';
	public const CPT_EVENT_STAT       = 'apollo_event_stat';
	public const CPT_HOME_SECTION     = 'apollo_home_section';

	/**
	 * Rewrite slugs (URL-facing)
	 */
	public const REWRITE_EVENT_LISTING = 'evento';
	public const REWRITE_EVENT_DJ      = 'dj';
	public const REWRITE_EVENT_LOCAL   = 'local';
	public const REWRITE_CLASSIFIED    = 'anuncio';
	public const REWRITE_SOCIAL_POST   = 'post-social';
	public const REWRITE_USER_PAGE     = 'user-page';

	/**
	 * Archive slugs
	 */
	public const ARCHIVE_EVENTS      = 'eventos';
	public const ARCHIVE_CLASSIFIEDS = 'anuncios';

	// =========================================================================
	// TAXONOMIES
	// =========================================================================

	/**
	 * Event taxonomies (apollo-events-manager)
	 */
	public const TAX_EVENT_CATEGORY = 'event_listing_category';
	public const TAX_EVENT_TYPE     = 'event_listing_type';
	public const TAX_EVENT_TAG      = 'event_listing_tag';
	public const TAX_EVENT_SOUNDS   = 'event_sounds';
	public const TAX_EVENT_SEASON   = 'event_season';

	/**
	 * Classified taxonomies (apollo-social)
	 */
	public const TAX_CLASSIFIED_DOMAIN = 'classified_domain';
	public const TAX_CLASSIFIED_INTENT = 'classified_intent';

	/**
	 * Supplier taxonomies (apollo-social)
	 */
	public const TAX_SUPPLIER_CATEGORY     = 'apollo_supplier_category';
	public const TAX_SUPPLIER_REGION       = 'apollo_supplier_region';
	public const TAX_SUPPLIER_NEIGHBORHOOD = 'apollo_supplier_neighborhood';
	public const TAX_SUPPLIER_EVENT_TYPE   = 'apollo_supplier_event_type';
	public const TAX_SUPPLIER_TYPE         = 'apollo_supplier_type';
	public const TAX_SUPPLIER_MODE         = 'apollo_supplier_mode';
	public const TAX_SUPPLIER_BADGE        = 'apollo_supplier_badge';

	// =========================================================================
	// GROUPS (Custom Tables)
	// =========================================================================

	public const GROUP_TYPE_COMUNA = 'comuna';
	public const GROUP_TYPE_NUCLEO = 'nucleo';
	public const GROUP_TYPE_SEASON = 'season';

	// =========================================================================
	// CUSTOM TABLES (base names, WITHOUT prefix)
	// =========================================================================

	public const TABLE_GROUPS              = 'apollo_groups';
	public const TABLE_GROUP_MEMBERS       = 'apollo_group_members';
	public const TABLE_DOCUMENTS           = 'apollo_documents';
	public const TABLE_DOCUMENT_PERMISSIONS = 'apollo_document_permissions';
	public const TABLE_SIGNATURES          = 'apollo_signatures';
	public const TABLE_SIGNATURE_TEMPLATES = 'apollo_signature_templates';
	public const TABLE_SIGNATURE_AUDIT     = 'apollo_signature_audit';
	public const TABLE_SIGNATURE_PROTOCOLS = 'apollo_signature_protocols';
	public const TABLE_SUBSCRIPTIONS       = 'apollo_subscriptions';
	public const TABLE_SUBSCRIPTION_ORDERS = 'apollo_subscription_orders';
	public const TABLE_SUBSCRIPTION_PLANS  = 'apollo_subscription_plans';
	public const TABLE_MEDIA_UPLOADS       = 'apollo_media_uploads';
	public const TABLE_LIKES               = 'apollo_likes';
	public const TABLE_FORUMS              = 'apollo_forums';
	public const TABLE_FORUM_TOPICS        = 'apollo_forum_topics';
	public const TABLE_FORUM_REPLIES       = 'apollo_forum_replies';
	public const TABLE_ACTIVITY            = 'apollo_activity';
	public const TABLE_NEWSLETTER          = 'apollo_newsletter';
	public const TABLE_PUSH_TOKENS         = 'apollo_push_tokens';
	public const TABLE_WORKFLOW_LOG        = 'apollo_workflow_log';
	public const TABLE_MOD_QUEUE           = 'apollo_mod_queue';
	public const TABLE_VERIFICATIONS       = 'apollo_verifications';
	public const TABLE_AUDIT_LOG           = 'apollo_audit_log';
	public const TABLE_ANALYTICS_EVENTS    = 'apollo_analytics_events';
	public const TABLE_ADS                 = 'apollo_ads';
	public const TABLE_SUPPLIER_VIEWS      = 'apollo_supplier_views';

	// =========================================================================
	// ADMIN MENU SLUGS
	// =========================================================================

	public const MENU_CONTROL_PANEL = 'apollo-control';
	public const MENU_MODERATION    = 'apollo-moderation';
	public const MENU_ANALYTICS     = 'apollo-analytics';
	public const MENU_COMMUNICATION = 'apollo-communication';
	public const MENU_EMAIL_SETTINGS = 'apollo-email-settings';
	public const MENU_EMAIL_FLOWS   = 'apollo-email-flows';
	public const MENU_NEWSLETTER    = 'apollo-newsletter';
	public const MENU_PUSH          = 'apollo-push';
	public const MENU_SEO           = 'apollo-seo';
	public const MENU_NAVBAR_APPS   = 'apollo-navbar-apps';
	public const MENU_SHORTCODES    = 'apollo-shortcodes';
	public const MENU_SNIPPETS      = 'apollo-snippets';

	// =========================================================================
	// SHORTCODE TAGS
	// =========================================================================

	public const SHORTCODE_NEWSLETTER         = 'apollo_newsletter';
	public const SHORTCODE_HOME_HERO          = 'apollo_home_hero';
	public const SHORTCODE_HOME_MANIFESTO     = 'apollo_home_manifesto';
	public const SHORTCODE_HOME_EVENTS        = 'apollo_home_events';
	public const SHORTCODE_HOME_CLASSIFIEDS   = 'apollo_home_classifieds';
	public const SHORTCODE_HOME_HUB           = 'apollo_home_hub';
	public const SHORTCODE_HOME_FERRAMENTAS   = 'apollo_home_ferramentas';
	public const SHORTCODE_EVENT_CARD         = 'apollo_event_card';
	public const SHORTCODE_USER_STATS         = 'apollo_user_stats';
	public const SHORTCODE_TOP_SOUNDS         = 'apollo_top_sounds';
	public const SHORTCODE_INTERESSE_DASHBOARD = 'apollo_interesse_dashboard';
	public const SHORTCODE_CENA_MOD_QUEUE     = 'apollo_cena_mod_queue';
	public const SHORTCODE_CENA_SUBMIT_EVENT  = 'apollo_cena_submit_event';

	// =========================================================================
	// ASSET HANDLES
	// =========================================================================

	public const HANDLE_UNI_CSS      = 'apollo-uni-css';
	public const HANDLE_COMPAT_CSS   = 'apollo-compat-css';
	public const HANDLE_BASE_JS      = 'apollo-base-js';
	public const HANDLE_MOTION       = 'apollo-motion';
	public const HANDLE_CHARTJS      = 'apollo-chartjs';
	public const HANDLE_REMIXICON    = 'apollo-remixicon';

	// Legacy handles (aliases, kept for backward compatibility)
	public const HANDLE_FRAMER_MOTION   = 'framer-motion';
	public const HANDLE_CHARTJS_LEGACY  = 'chartjs';
	public const HANDLE_CHARTJS_LEGACY2 = 'chart-js';
	public const HANDLE_LEAFLET_CSS     = 'leaflet';
	public const HANDLE_LEAFLET_JS      = 'leaflet';
	public const HANDLE_DATATABLES_JS   = 'datatables-js';
	public const HANDLE_DATATABLES_CSS  = 'datatables-css';
	public const HANDLE_REMIXICON_LEGACY = 'remixicon';

	// =========================================================================
	// OPTION KEYS
	// =========================================================================

	public const OPTION_DB_VERSION           = 'apollo_db_version';
	public const OPTION_MIGRATION_VERSION    = 'apollo_core_migration_version';
	public const OPTION_EMAIL_FLOWS          = 'apollo_email_flows';
	public const OPTION_EMAIL_TEMPLATES      = 'apollo_email_templates';
	public const OPTION_HOME_PAGE_ID         = 'apollo_home_page_id';
	public const OPTION_MODULES              = 'apollo_modules';
	public const OPTION_MEMBERSHIPS          = 'apollo_memberships';
	public const OPTION_MOD_SETTINGS         = 'apollo_mod_settings';
	public const OPTION_LIMITS               = 'apollo_limits';

	// =========================================================================
	// META KEY PREFIXES
	// =========================================================================

	public const META_PREFIX_EVENT      = '_event_';
	public const META_PREFIX_CLASSIFIED = '_classified_';
	public const META_PREFIX_SUPPLIER   = '_apollo_supplier_';
	public const META_PREFIX_DOCUMENT   = '_apollo_doc_';
	public const META_PREFIX_USER       = 'apollo_';

	// =========================================================================
	// DOCUMENT META KEYS (canonical)
	// =========================================================================

	public const META_DOC_SIGNATURES        = '_apollo_doc_signatures';
	public const META_DOC_SIGNATURES_LEGACY = '_apollo_document_signatures';
	public const META_DOC_STATE             = '_apollo_doc_state';
	public const META_DOC_PDF_ID            = '_apollo_doc_pdf_id';
	public const META_DOC_HASH              = '_apollo_doc_hash';
	public const META_DOC_FILE_ID           = '_apollo_doc_file_id';

	// =========================================================================
	// CANONICAL OWNERS (which plugin owns what)
	// =========================================================================

	/**
	 * Canonical owner mapping for duplicate-prone identifiers.
	 * Format: identifier => owner_plugin
	 *
	 * CPT keys: use slug directly (e.g., 'event_listing')
	 * REST keys: use route pattern (e.g., 'eventos', 'evento/(?P<id>\d+)')
	 */
	public const CANONICAL_OWNERS = array(
		// CPTs.
		'event_listing'       => 'apollo-events-manager',
		'event_dj'            => 'apollo-events-manager',
		'event_local'         => 'apollo-events-manager',
		'apollo_classified'   => 'apollo-social',
		'apollo_supplier'     => 'apollo-social',
		'apollo_social_post'  => 'apollo-social',
		'user_page'           => 'apollo-social',
		'apollo_document'     => 'apollo-social',
		// REST routes (without leading slash, matches register_rest_route usage).
		'eventos'             => 'apollo-events-manager',
		'evento/(?P<id>\d+)'  => 'apollo-events-manager',
		'categorias'          => 'apollo-events-manager',
		'locais'              => 'apollo-events-manager',
		'meus-eventos'        => 'apollo-events-manager',
		'classifieds'         => 'apollo-social',
		'groups'              => 'apollo-social',
		'user-page'           => 'apollo-social',
	);

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Get full table name with WordPress prefix
	 *
	 * @param string $base_name Table base name (without prefix).
	 * @return string Full table name with prefix.
	 */
	public static function table( string $base_name ): string {
		global $wpdb;
		return $wpdb->prefix . $base_name;
	}

	/**
	 * Get REST namespace
	 *
	 * @return string REST namespace.
	 */
	public static function rest_ns(): string {
		return self::REST_NAMESPACE;
	}

	/**
	 * Check if current plugin is the canonical owner of an identifier
	 *
	 * @param string $identifier The identifier to check.
	 * @param string $plugin     The plugin slug to check against.
	 * @return bool True if plugin is canonical owner.
	 */
	public static function is_canonical_owner( string $identifier, string $plugin ): bool {
		return isset( self::CANONICAL_OWNERS[ $identifier ] )
			&& self::CANONICAL_OWNERS[ $identifier ] === $plugin;
	}

	/**
	 * Get all CPT slugs
	 *
	 * @return array Array of CPT slugs.
	 */
	public static function get_all_cpts(): array {
		return array(
			self::CPT_EVENT_LISTING,
			self::CPT_EVENT_DJ,
			self::CPT_EVENT_LOCAL,
			self::CPT_CLASSIFIED,
			self::CPT_SUPPLIER,
			self::CPT_SOCIAL_POST,
			self::CPT_USER_PAGE,
			self::CPT_DOCUMENT,
			self::CPT_CENA_DOCUMENT,
			self::CPT_CENA_EVENT_PLAN,
			self::CPT_EMAIL_TEMPLATE,
			self::CPT_EVENT_STAT,
			self::CPT_HOME_SECTION,
		);
	}

	/**
	 * Get all custom table base names
	 *
	 * @return array Array of table base names.
	 */
	public static function get_all_tables(): array {
		return array(
			self::TABLE_GROUPS,
			self::TABLE_GROUP_MEMBERS,
			self::TABLE_DOCUMENTS,
			self::TABLE_DOCUMENT_PERMISSIONS,
			self::TABLE_SIGNATURES,
			self::TABLE_SIGNATURE_TEMPLATES,
			self::TABLE_SIGNATURE_AUDIT,
			self::TABLE_SIGNATURE_PROTOCOLS,
			self::TABLE_SUBSCRIPTIONS,
			self::TABLE_SUBSCRIPTION_ORDERS,
			self::TABLE_SUBSCRIPTION_PLANS,
			self::TABLE_MEDIA_UPLOADS,
			self::TABLE_LIKES,
			self::TABLE_FORUMS,
			self::TABLE_FORUM_TOPICS,
			self::TABLE_FORUM_REPLIES,
			self::TABLE_ACTIVITY,
			self::TABLE_NEWSLETTER,
			self::TABLE_PUSH_TOKENS,
			self::TABLE_WORKFLOW_LOG,
			self::TABLE_MOD_QUEUE,
			self::TABLE_VERIFICATIONS,
			self::TABLE_AUDIT_LOG,
			self::TABLE_ANALYTICS_EVENTS,
			self::TABLE_ADS,
			self::TABLE_SUPPLIER_VIEWS,
		);
	}

	/**
	 * Normalize a REST route string (ensure no leading/trailing slashes).
	 *
	 * @param string $route The route to normalize.
	 * @return string Normalized route.
	 */
	public static function rest_route( string $route ): string {
		return trim( $route, '/' );
	}

	/**
	 * Get the canonical owner of an identifier.
	 *
	 * @param string $identifier The identifier to look up.
	 * @return string|null Owner plugin slug, or null if not found.
	 */
	public static function owner( string $identifier ): ?string {
		return self::CANONICAL_OWNERS[ $identifier ] ?? null;
	}

	/**
	 * Check if a REST route is already registered.
	 *
	 * @param string $namespace The namespace.
	 * @param string $route     The route pattern.
	 * @return bool True if route exists.
	 */
	public static function rest_route_exists( string $namespace, string $route ): bool {
		if ( ! did_action( 'rest_api_init' ) ) {
			return false;
		}

		$server = rest_get_server();
		if ( ! $server ) {
			return false;
		}

		$routes      = $server->get_routes();
		$full_route  = '/' . $namespace . '/' . ltrim( $route, '/' );

		return isset( $routes[ $full_route ] );
	}
}
