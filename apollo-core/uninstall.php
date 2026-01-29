<?php
/**
 * Apollo Core Uninstall
 *
 * Handles complete cleanup when the plugin is deleted.
 * Provides user choice for data retention vs deletion.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Check if user enabled "Delete Data on Uninstall" in settings
$options = get_option( 'apollo_core_settings' );
if ( empty( $options['cleanup_data_on_delete'] ) ) {
	// User chose to preserve data - exit without deleting
	return;
}

// 2. Nuclear cleanup - only if user explicitly opted-in

// A. Delete Custom Tables
$tables_to_drop = array(
	$wpdb->prefix . 'apollo_activity_log',
	$wpdb->prefix . 'apollo_relationships',
	$wpdb->prefix . 'apollo_event_queue',
	$wpdb->prefix . 'apollo_newsletter_subscribers',
	$wpdb->prefix . 'apollo_newsletter_campaigns',
	$wpdb->prefix . 'apollo_mod_log',
	$wpdb->prefix . 'apollo_audit_log',
	$wpdb->prefix . 'apollo_analytics_pageviews',
	$wpdb->prefix . 'apollo_analytics_interactions',
	$wpdb->prefix . 'apollo_analytics_sessions',
	$wpdb->prefix . 'apollo_analytics_user_stats',
	$wpdb->prefix . 'apollo_analytics_content_stats',
	$wpdb->prefix . 'apollo_analytics_heatmap',
	$wpdb->prefix . 'apollo_analytics_settings',
	$wpdb->prefix . 'apollo_email_queue',
	$wpdb->prefix . 'apollo_email_log',
	$wpdb->prefix . 'apollo_email_security_log',
	$wpdb->prefix . 'apollo_notifications',
	$wpdb->prefix . 'apollo_notification_preferences',
	$wpdb->prefix . 'apollo_form_submissions',
	$wpdb->prefix . 'apollo_form_analytics',
	$wpdb->prefix . 'apollo_quiz_schemas',
	$wpdb->prefix . 'apollo_document_signs',
	$wpdb->prefix . 'apollo_push_tokens',
	$wpdb->prefix . 'apollo_workflow_log',
	$wpdb->prefix . 'apollo_mod_queue',
	$wpdb->prefix . 'apollo_verifications',
	$wpdb->prefix . 'apollo_ad_analytics',
	$wpdb->prefix . 'apollo_supplier_views',
);

foreach ( $tables_to_drop as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// B. Delete Options
$options_to_delete = array(
	'apollo_db_version',
	'apollo_core_migration_version',
	'apollo_email_flows',
	'apollo_email_templates',
	'apollo_home_page_id',
	'apollo_modules',
	'apollo_memberships',
	'apollo_mod_settings',
	'apollo_limits',
	'apollo_core_settings',
	'apollo_quiz_schemas',
	'apollo_form_schemas',
	'apollo_social_profiles',
	'apollo_schema_suite_version',
	'apollo_core_schema_version',
	'apollo_libretranslate_url',
	'apollo_i18n_strict_mode',
	'apollo_coauthors_settings',
	'apollo_push_enabled',
	'apollo_activated',
	'apollo_activation_time',
	'apollo_version',
	'apollo_deactivation_time',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// C. Delete User Meta (be careful - only Apollo-specific meta)
$user_meta_keys = array(
	'onboarding_complete',
	'apollo_preferred_language',
	'last_activity',
	'cpf',
	'instagram',
	'twitter',
	'facebook',
	'linkedin',
	'soundcloud',
);

foreach ( $user_meta_keys as $meta_key ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			$meta_key
		)
	);
}

// D. Delete Post Meta (be careful - only Apollo-specific meta)
$post_meta_keys = array(
	'_event_start_date',
	'_event_end_date',
	'_event_venue',
	'_event_price',
	'_event_capacity',
	'_event_description',
	'_event_organizer',
	'_event_website',
	'_event_ticket_url',
	'_event_featured_image',
	'_event_gallery',
	'_event_video_url',
	'_event_audio_url',
	'_event_social_links',
	'_event_tags',
	'_event_category',
	'_event_type',
	'_event_season',
	'_event_sounds',
	'_event_dj_slots',
	'_event_lat',
	'_event_lng',
	'_event_location_name',
	'_event_address',
	'_event_city',
	'_event_state',
	'_event_country',
	'_event_postal_code',
	'_event_timezone',
	'_event_recurring',
	'_event_recurrence_pattern',
	'_event_max_recurrences',
	'_event_parent_event',
	'_event_child_events',
	'_event_canceled',
	'_event_cancellation_reason',
	'_event_refunded',
	'_event_refund_policy',
	'_event_age_restriction',
	'_event_ticket_types',
	'_event_early_bird_price',
	'_event_early_bird_deadline',
	'_event_group_price',
	'_event_group_min_size',
	'_event_vip_price',
	'_event_vip_perks',
	'_event_merchandise',
	'_event_sponsors',
	'_event_media_partners',
	'_event_press_kit',
	'_event_reviews',
	'_event_rating',
	'_event_total_ratings',
	'_event_featured',
	'_event_promoted',
	'_event_views',
	'_event_clicks',
	'_event_conversions',
	'_event_roi',
	'_event_analytics',
	'_apollo_supplier_name',
	'_apollo_supplier_description',
	'_apollo_supplier_website',
	'_apollo_supplier_email',
	'_apollo_supplier_phone',
	'_apollo_supplier_address',
	'_apollo_supplier_city',
	'_apollo_supplier_state',
	'_apollo_supplier_country',
	'_apollo_supplier_postal_code',
	'_apollo_supplier_lat',
	'_apollo_supplier_lng',
	'_apollo_supplier_logo',
	'_apollo_supplier_images',
	'_apollo_supplier_social_links',
	'_apollo_supplier_category',
	'_apollo_supplier_region',
	'_apollo_supplier_neighborhood',
	'_apollo_supplier_event_type',
	'_apollo_supplier_type',
	'_apollo_supplier_mode',
	'_apollo_supplier_badge',
	'_apollo_supplier_featured',
	'_apollo_supplier_verified',
	'_apollo_supplier_rating',
	'_apollo_supplier_total_reviews',
	'_apollo_supplier_views',
	'_apollo_supplier_clicks',
	'_apollo_supplier_conversions',
	'_classified_title',
	'_classified_description',
	'_classified_price',
	'_classified_currency',
	'_classified_category',
	'_classified_location',
	'_classified_images',
	'_classified_featured',
	'_classified_expires',
	'_classified_status',
	'_classified_views',
	'_classified_clicks',
	'_classified_contact_email',
	'_classified_contact_phone',
	'_apollo_doc_signatures',
	'_apollo_doc_state',
	'_apollo_doc_pdf_id',
	'_apollo_doc_hash',
	'_apollo_doc_file_id',
);

foreach ( $post_meta_keys as $meta_key ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
			$meta_key
		)
	);
}

// E. Clear any remaining transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_apollo_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_apollo_%'" );

// F. Clear scheduled cron jobs
wp_clear_scheduled_hook( 'apollo_maintenance_cron' );
wp_clear_scheduled_hook( 'apollo_analytics_daily_aggregate' );
wp_clear_scheduled_hook( 'apollo_newsletter_send_scheduled' );
wp_clear_scheduled_hook( 'apollo_relationship_integrity_check' );
wp_clear_scheduled_hook( 'apollo_cdn_monitor_health' );
wp_clear_scheduled_hook( 'apollo_email_log_cleanup' );
wp_clear_scheduled_hook( 'apollo_cache_warmup' );
wp_clear_scheduled_hook( 'apollo_process_email_queue' );

// Log the complete uninstall
error_log( 'Apollo Core completely uninstalled at ' . current_time( 'mysql' ) . ' - all data deleted' );
