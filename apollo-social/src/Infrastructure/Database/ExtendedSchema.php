<?php
declare(strict_types=1);
namespace Apollo\Infrastructure\Database;

final class ExtendedSchema {

	public static function install(): void {
		self::createBookmarksTable();
		self::createBookmarkCollectionsTable();
		self::createPollsTable();
		self::createPollOptionsTable();
		self::createPollVotesTable();
		self::createStoriesTable();
		self::createStoryViewsTable();
		self::createStoryRepliesTable();
		self::createHashtagsTable();
		self::createHashtagUsageTable();
		self::createHashtagFollowsTable();
		self::createReactionsTable();
		self::createModerationQueueTable();
		self::createModerationActionsTable();
		self::createModerationRulesTable();
		self::createProfileViewsTable();
		self::createAuditLogsTable();
		self::createReferralsTable();
		self::createReferralRewardsTable();
		self::createDataExportsTable();
		self::create2faSessionsTable();
		self::create2faBackupCodesTable();
		self::create2faTrustedDevicesTable();
	}

	public static function upgrade(string $from, string $to): void {
		self::install();
	}

	private static function createBookmarksTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_bookmarks';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			collection_id bigint(20) unsigned DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_object_uk (user_id,object_type,object_id),
			KEY user_idx (user_id),
			KEY collection_idx (collection_id),
			KEY object_idx (object_type,object_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createBookmarkCollectionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_bookmark_collections';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			is_private tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY slug_idx (slug)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createPollsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_polls';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			question text NOT NULL,
			multiple_choice tinyint(1) NOT NULL DEFAULT 0,
			anonymous tinyint(1) NOT NULL DEFAULT 0,
			visibility varchar(20) DEFAULT 'public',
			activity_id bigint(20) unsigned DEFAULT NULL,
			group_id bigint(20) unsigned DEFAULT NULL,
			total_votes int(11) NOT NULL DEFAULT 0,
			status enum('active','closed','expired') NOT NULL DEFAULT 'active',
			expires_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY activity_idx (activity_id),
			KEY group_idx (group_id),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createPollOptionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_poll_options';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			poll_id bigint(20) unsigned NOT NULL,
			option_text varchar(255) NOT NULL,
			votes int(11) NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY poll_idx (poll_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createPollVotesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_poll_votes';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			poll_id bigint(20) unsigned NOT NULL,
			option_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY poll_user_idx (poll_id,user_id),
			KEY option_idx (option_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createStoriesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_stories';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			media_url varchar(500) NOT NULL,
			media_type enum('image','video') NOT NULL DEFAULT 'image',
			caption text,
			background_color varchar(7) DEFAULT NULL,
			text_overlay text,
			link_url varchar(500) DEFAULT NULL,
			location varchar(255) DEFAULT NULL,
			visibility enum('public','friends','close_friends') NOT NULL DEFAULT 'friends',
			duration int(11) NOT NULL DEFAULT 5,
			view_count int(11) NOT NULL DEFAULT 0,
			is_highlighted tinyint(1) NOT NULL DEFAULT 0,
			highlight_name varchar(100) DEFAULT NULL,
			is_deleted tinyint(1) NOT NULL DEFAULT 0,
			expires_at datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY expires_idx (expires_at),
			KEY highlight_idx (is_highlighted,highlight_name)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createStoryViewsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_story_views';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			story_id bigint(20) unsigned NOT NULL,
			viewer_id bigint(20) unsigned NOT NULL,
			viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY story_viewer_uk (story_id,viewer_id),
			KEY story_idx (story_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createStoryRepliesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_story_replies';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			story_id bigint(20) unsigned NOT NULL,
			story_owner_id bigint(20) unsigned NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			message text NOT NULL,
			type enum('text','emoji','reaction') NOT NULL DEFAULT 'text',
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY story_idx (story_id),
			KEY owner_idx (story_owner_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createHashtagsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_hashtags';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			use_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY use_count_idx (use_count)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createHashtagUsageTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_hashtag_usage';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			hashtag_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY hashtag_object_uk (hashtag_id,object_type,object_id),
			KEY hashtag_idx (hashtag_id),
			KEY object_idx (object_type,object_id),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createHashtagFollowsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_hashtag_follows';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			hashtag_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_hashtag_uk (user_id,hashtag_id),
			KEY user_idx (user_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createReactionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_reactions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			reaction_type enum('like','love','haha','wow','sad','angry','care') NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_object_uk (user_id,object_type,object_id),
			KEY object_idx (object_type,object_id),
			KEY reaction_idx (reaction_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createModerationQueueTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_moderation_queue';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			content_preview text,
			reason text,
			status enum('pending','approved','rejected','escalated') NOT NULL DEFAULT 'pending',
			moderator_id bigint(20) unsigned DEFAULT NULL,
			moderator_notes text,
			reviewed_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status_idx (status),
			KEY content_idx (content_type,content_id),
			KEY author_idx (author_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createModerationActionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_moderation_actions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			queue_id bigint(20) unsigned DEFAULT NULL,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			moderator_id bigint(20) unsigned NOT NULL,
			action varchar(50) NOT NULL,
			notes text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY moderator_idx (moderator_id),
			KEY author_idx (author_id),
			KEY action_idx (action)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createModerationRulesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_moderation_rules';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type enum('keyword','regex','url') NOT NULL,
			pattern varchar(500) NOT NULL,
			action enum('queue','block','flag') NOT NULL DEFAULT 'queue',
			priority int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type_idx (type),
			KEY active_idx (is_active)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createProfileViewsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_views';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			profile_user_id bigint(20) unsigned NOT NULL,
			viewer_id bigint(20) unsigned DEFAULT 0,
			ip_address varchar(45) DEFAULT NULL,
			viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY profile_idx (profile_user_id),
			KEY viewer_idx (viewer_id),
			KEY viewed_idx (viewed_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createAuditLogsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_audit_logs';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT 0,
			action varchar(100) NOT NULL,
			object_type varchar(50) DEFAULT NULL,
			object_id bigint(20) unsigned DEFAULT 0,
			data longtext,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(500) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY action_idx (action),
			KEY object_idx (object_type,object_id),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createReferralsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_referrals';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			referrer_id bigint(20) unsigned NOT NULL,
			referred_id bigint(20) unsigned NOT NULL,
			referral_code varchar(20) NOT NULL,
			status enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
			completed_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY referred_uk (referred_id),
			KEY referrer_idx (referrer_id),
			KEY code_idx (referral_code),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createReferralRewardsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_referral_rewards';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			reward_type varchar(50) NOT NULL,
			amount int(11) NOT NULL DEFAULT 0,
			related_user_id bigint(20) unsigned DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY type_idx (reward_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function createDataExportsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_data_exports';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			data_types longtext,
			status enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
			download_url varchar(500) DEFAULT NULL,
			file_path varchar(500) DEFAULT NULL,
			requested_at datetime DEFAULT CURRENT_TIMESTAMP,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create2faSessionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_2fa_sessions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			token_hash varchar(255) NOT NULL,
			expires_at datetime NOT NULL,
			used_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY expires_idx (expires_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create2faBackupCodesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_2fa_backup_codes';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			code_hash varchar(255) NOT NULL,
			used_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private static function create2faTrustedDevicesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_2fa_trusted_devices';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			device_hash varchar(255) NOT NULL,
			device_name varchar(100) DEFAULT NULL,
			last_used datetime DEFAULT NULL,
			expires_at datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY device_idx (device_hash),
			KEY expires_idx (expires_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
