<?php
declare(strict_types=1);
namespace Apollo\Infrastructure\Database;

final class SocialSchema {

	public static function install(): void {
		self::createUserTagsTable();
		self::createUserTagRelationsTable();
		self::createProfileFieldsTable();
		self::createProfileFieldGroupsTable();
		self::createProfileFieldValuesTable();
		self::createProfileTabsTable();
		self::createConnectionsTable();
		self::createCloseFriendsTable();
		self::createOnlineUsersTable();
		self::createPointsTable();
		self::createPointsLogTable();
		self::createRanksTable();
		self::createAchievementsTable();
		self::createUserAchievementsTable();
		self::createCompetitionsTable();
		self::createCompetitionParticipantsTable();
		self::createActivityTable();
		self::createMentionsTable();
		self::createFavoritesTable();
		self::createNoticesTable();
		self::createUserNoticesTable();
		self::createContentRestrictionsTable();
		self::createUserRolesTable();
		self::createEmailQueueTable();
		self::createTestimonialsTable();
		self::createTeamMembersTable();
		self::createMapPinsTable();
		self::createMediaOffloadTable();
		self::createForumTopicsTable();
		self::createForumRepliesTable();
		self::createUserSettingsTable();
		self::createSpammerListTable();
		self::createPendingUsersTable();
		self::createGroupsTable();
		self::createGroupMembersTable();
		self::createGroupMetaTable();
		self::createGroupTypesTable();
		self::createGroupInvitesTable();
		self::createMemberTypesTable();
		self::createBadgesTable();
		self::createUserBadgesTable();
		self::createReportsTable();
		self::createMessageThreadsTable();
		self::createMessagesTable();
		self::createMessageParticipantsTable();
		self::createInvitationsTable();
		self::createActivityCommentsTable();
		self::createActivityLikesTable();
	}

	public static function upgrade(string $from, string $to): void {
		self::install();
	}

	public static function uninstall(): void {
		global $wpdb;
		$tables = [
			'apollo_user_tags','apollo_user_tag_relations','apollo_profile_fields',
			'apollo_profile_field_groups','apollo_profile_field_values','apollo_profile_tabs',
			'apollo_connections','apollo_close_friends','apollo_online_users','apollo_points',
			'apollo_points_log','apollo_ranks','apollo_achievements','apollo_user_achievements',
			'apollo_competitions','apollo_competition_participants','apollo_activity',
			'apollo_mentions','apollo_favorites','apollo_notices','apollo_user_notices',
			'apollo_content_restrictions','apollo_user_roles','apollo_email_queue',
			'apollo_testimonials','apollo_team_members','apollo_map_pins','apollo_media_offload',
			'apollo_forum_topics','apollo_forum_replies','apollo_user_settings',
			'apollo_spammer_list','apollo_pending_users','apollo_groups','apollo_group_members',
			'apollo_group_meta','apollo_group_types','apollo_group_invites','apollo_member_types',
			'apollo_badges','apollo_user_badges','apollo_reports','apollo_message_threads',
			'apollo_messages','apollo_message_participants','apollo_invitations',
			'apollo_activity_comments','apollo_activity_likes'
		];
		foreach ($tables as $t) {
			$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");
		}
	}

	private function createUserTagsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_tags';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			color varchar(7) DEFAULT '#6b7280',
			icon varchar(50) DEFAULT NULL,
			description text,
			is_system tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY is_system_idx (is_system)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserTagRelationsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_tag_relations';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			assigned_by bigint(20) unsigned DEFAULT NULL,
			assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_tag_uk (user_id,tag_id),
			KEY user_idx (user_id),
			KEY tag_idx (tag_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createProfileFieldsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_fields';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			type enum('text','textarea','select','radio','checkbox','date','url','email','phone','number','file','image') NOT NULL DEFAULT 'text',
			options longtext,
			placeholder varchar(255) DEFAULT NULL,
			default_value text,
			is_required tinyint(1) NOT NULL DEFAULT 0,
			is_public tinyint(1) NOT NULL DEFAULT 1,
			is_searchable tinyint(1) NOT NULL DEFAULT 0,
			is_editable tinyint(1) NOT NULL DEFAULT 1,
			visibility enum('everyone','members','friends','self','admin') NOT NULL DEFAULT 'everyone',
			validation_rules longtext,
			conditional_logic longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY group_idx (group_id),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createProfileFieldGroupsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_field_groups';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			context enum('user','comuna','nucleo','all') NOT NULL DEFAULT 'user',
			is_repeatable tinyint(1) NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY context_idx (context),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createProfileFieldValuesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_field_values';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			field_id bigint(20) unsigned NOT NULL,
			value longtext,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_field_uk (user_id,field_id),
			KEY user_idx (user_id),
			KEY field_idx (field_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createProfileTabsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_profile_tabs';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			icon varchar(50) DEFAULT NULL,
			content_type enum('fields','shortcode','template','custom') NOT NULL DEFAULT 'fields',
			content longtext,
			visibility enum('everyone','members','friends','self','admin') NOT NULL DEFAULT 'everyone',
			roles_allowed longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createConnectionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_connections';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			friend_id bigint(20) unsigned NOT NULL,
			status enum('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
			initiated_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			accepted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY connection_uk (user_id,friend_id),
			KEY user_idx (user_id),
			KEY friend_idx (friend_id),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createCloseFriendsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_close_friends';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			friend_id bigint(20) unsigned NOT NULL,
			added_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY bubble_uk (user_id,friend_id),
			KEY user_idx (user_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createOnlineUsersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_online_users';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			last_activity datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			current_page varchar(500) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			PRIMARY KEY (user_id),
			KEY last_activity_idx (last_activity)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createPointsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_points';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			points_type varchar(50) NOT NULL DEFAULT 'default',
			balance bigint(20) NOT NULL DEFAULT 0,
			total_earned bigint(20) NOT NULL DEFAULT 0,
			total_spent bigint(20) NOT NULL DEFAULT 0,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (user_id,points_type),
			KEY balance_idx (balance),
			KEY type_idx (points_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createPointsLogTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_points_log';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			points_type varchar(50) NOT NULL DEFAULT 'default',
			amount int(11) NOT NULL,
			balance_after bigint(20) NOT NULL,
			trigger_name varchar(100) NOT NULL,
			reference_type varchar(50) DEFAULT NULL,
			reference_id bigint(20) unsigned DEFAULT NULL,
			description varchar(255) DEFAULT NULL,
			admin_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY trigger_idx (trigger_name),
			KEY created_idx (created_at),
			KEY type_idx (points_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createRanksTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_ranks';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			points_type varchar(50) NOT NULL DEFAULT 'default',
			min_points bigint(20) NOT NULL DEFAULT 0,
			max_points bigint(20) DEFAULT NULL,
			icon varchar(100) DEFAULT NULL,
			color varchar(7) DEFAULT '#6b7280',
			badge_image_id bigint(20) unsigned DEFAULT NULL,
			perks longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY points_range_idx (min_points,max_points),
			KEY type_idx (points_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createAchievementsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_achievements';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			type enum('badge','milestone','special','competition') NOT NULL DEFAULT 'badge',
			trigger_name varchar(100) NOT NULL,
			trigger_count int(11) NOT NULL DEFAULT 1,
			trigger_conditions longtext,
			points_reward int(11) NOT NULL DEFAULT 0,
			icon varchar(100) DEFAULT NULL,
			badge_image_id bigint(20) unsigned DEFAULT NULL,
			is_secret tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY trigger_idx (trigger_name),
			KEY type_idx (type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserAchievementsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_achievements';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			achievement_id bigint(20) unsigned NOT NULL,
			progress int(11) NOT NULL DEFAULT 0,
			is_completed tinyint(1) NOT NULL DEFAULT 0,
			completed_at datetime DEFAULT NULL,
			notified tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY user_achievement_uk (user_id,achievement_id),
			KEY user_idx (user_id),
			KEY completed_idx (is_completed)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createCompetitionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_competitions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			type enum('leaderboard','challenge','race') NOT NULL DEFAULT 'leaderboard',
			metric varchar(100) NOT NULL,
			start_at datetime NOT NULL,
			end_at datetime NOT NULL,
			prizes longtext,
			rules longtext,
			status enum('upcoming','active','ended','cancelled') NOT NULL DEFAULT 'upcoming',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY status_idx (status),
			KEY dates_idx (start_at,end_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createCompetitionParticipantsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_competition_participants';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			competition_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			score bigint(20) NOT NULL DEFAULT 0,
			rank_position int(11) DEFAULT NULL,
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_update datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY comp_user_uk (competition_id,user_id),
			KEY score_idx (score DESC),
			KEY rank_idx (rank_position)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createActivityTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			action varchar(100) NOT NULL,
			component varchar(50) NOT NULL,
			type varchar(50) NOT NULL,
			content longtext,
			item_id bigint(20) unsigned DEFAULT NULL,
			secondary_item_id bigint(20) unsigned DEFAULT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			privacy enum('public','friends','private') NOT NULL DEFAULT 'public',
			is_spam tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY component_idx (component),
			KEY type_idx (type),
			KEY item_idx (item_id),
			KEY created_idx (created_at),
			KEY parent_idx (parent_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMentionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_mentions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			mentioned_by bigint(20) unsigned NOT NULL,
			activity_id bigint(20) unsigned NOT NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY is_read_idx (is_read),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createFavoritesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_favorites';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			item_type varchar(50) NOT NULL,
			item_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_item_uk (user_id,item_type,item_id),
			KEY user_idx (user_id),
			KEY item_idx (item_type,item_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createNoticesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_notices';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			content text NOT NULL,
			type enum('info','warning','success','error','promo') NOT NULL DEFAULT 'info',
			priority tinyint(1) NOT NULL DEFAULT 5,
			target_roles longtext,
			target_memberships longtext,
			target_conditions longtext,
			is_dismissible tinyint(1) NOT NULL DEFAULT 1,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			start_at datetime DEFAULT NULL,
			end_at datetime DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active_idx (is_active),
			KEY dates_idx (start_at,end_at),
			KEY priority_idx (priority)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserNoticesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_notices';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			notice_id bigint(20) unsigned NOT NULL,
			dismissed_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (user_id,notice_id),
			KEY notice_idx (notice_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createContentRestrictionsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_content_restrictions';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			restriction_type enum('role','membership','points','achievement','manual') NOT NULL,
			restriction_value varchar(255) NOT NULL,
			redirect_url varchar(500) DEFAULT NULL,
			message text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY content_uk (content_type,content_id,restriction_type,restriction_value),
			KEY content_idx (content_type,content_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserRolesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_roles';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			color varchar(7) DEFAULT '#6b7280',
			icon varchar(50) DEFAULT NULL,
			permissions longtext,
			is_system tinyint(1) NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createEmailQueueTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_email_queue';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			to_email varchar(255) NOT NULL,
			to_user_id bigint(20) unsigned DEFAULT NULL,
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			template varchar(100) DEFAULT NULL,
			template_data longtext,
			priority tinyint(1) NOT NULL DEFAULT 5,
			status enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
			attempts tinyint(1) NOT NULL DEFAULT 0,
			max_attempts tinyint(1) NOT NULL DEFAULT 3,
			scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime DEFAULT NULL,
			error_message text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status_idx (status),
			KEY scheduled_idx (scheduled_at),
			KEY priority_idx (priority)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createTestimonialsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_testimonials';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			supplier_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			rating tinyint(1) NOT NULL DEFAULT 5,
			title varchar(255) DEFAULT NULL,
			content text NOT NULL,
			status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			approved_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY supplier_idx (supplier_id),
			KEY user_idx (user_id),
			KEY status_idx (status),
			KEY rating_idx (rating)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createTeamMembersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_team_members';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			name varchar(255) NOT NULL,
			role_title varchar(255) DEFAULT NULL,
			bio text,
			photo_id bigint(20) unsigned DEFAULT NULL,
			social_links longtext,
			sort_order int(11) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_idx (user_id),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMapPinsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_map_pins';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			latitude decimal(10,8) NOT NULL,
			longitude decimal(11,8) NOT NULL,
			address varchar(500) DEFAULT NULL,
			pin_type varchar(50) DEFAULT 'default',
			icon varchar(100) DEFAULT NULL,
			color varchar(7) DEFAULT '#3b82f6',
			reference_type varchar(50) DEFAULT NULL,
			reference_id bigint(20) unsigned DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY coords_idx (latitude,longitude),
			KEY reference_idx (reference_type,reference_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMediaOffloadTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_media_offload';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			attachment_id bigint(20) unsigned NOT NULL,
			local_path varchar(500) NOT NULL,
			cdn_url varchar(500) NOT NULL,
			cdn_provider varchar(50) NOT NULL DEFAULT 'cloudflare',
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			mime_type varchar(100) NOT NULL,
			status enum('pending','uploaded','failed','deleted') NOT NULL DEFAULT 'pending',
			uploaded_at datetime DEFAULT NULL,
			last_accessed datetime DEFAULT NULL,
			access_count bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY attachment_uk (attachment_id),
			KEY status_idx (status),
			KEY cdn_idx (cdn_provider)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createForumTopicsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_forum_topics';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned DEFAULT NULL,
			user_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL,
			content longtext NOT NULL,
			status enum('open','closed','sticky','archived') NOT NULL DEFAULT 'open',
			is_pinned tinyint(1) NOT NULL DEFAULT 0,
			reply_count int(11) NOT NULL DEFAULT 0,
			last_reply_at datetime DEFAULT NULL,
			last_reply_by bigint(20) unsigned DEFAULT NULL,
			views int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_idx (group_id),
			KEY user_idx (user_id),
			KEY status_idx (status),
			KEY pinned_idx (is_pinned),
			KEY last_reply_idx (last_reply_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createForumRepliesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_forum_replies';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			topic_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			content longtext NOT NULL,
			is_solution tinyint(1) NOT NULL DEFAULT 0,
			likes_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY topic_idx (topic_id),
			KEY user_idx (user_id),
			KEY parent_idx (parent_id),
			KEY solution_idx (is_solution)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserSettingsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_settings';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			setting_key varchar(100) NOT NULL,
			setting_value longtext,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (user_id,setting_key),
			KEY setting_key_idx (setting_key)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createSpammerListTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_spammer_list';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			marked_by bigint(20) unsigned NOT NULL,
			reason text,
			evidence longtext,
			marked_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (user_id),
			KEY marked_by_idx (marked_by)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createPendingUsersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_pending_users';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			user_id bigint(20) unsigned NOT NULL,
			reason varchar(255) NOT NULL DEFAULT 'manual_approval',
			submitted_data longtext,
			reviewed_by bigint(20) unsigned DEFAULT NULL,
			review_notes text,
			status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			reviewed_at datetime DEFAULT NULL,
			PRIMARY KEY (user_id),
			KEY status_idx (status),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createGroupsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_groups';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description longtext,
			type_id bigint(20) unsigned DEFAULT NULL,
			status enum('public','private','hidden') NOT NULL DEFAULT 'public',
			creator_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			cover_image varchar(500) DEFAULT NULL,
			avatar varchar(500) DEFAULT NULL,
			enable_forum tinyint(1) NOT NULL DEFAULT 0,
			member_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY type_idx (type_id),
			KEY status_idx (status),
			KEY creator_idx (creator_id),
			KEY parent_idx (parent_id)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createGroupMembersTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_members';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role enum('member','mod','admin','owner') NOT NULL DEFAULT 'member',
			date_joined datetime DEFAULT CURRENT_TIMESTAMP,
			invited_by bigint(20) unsigned DEFAULT NULL,
			is_banned tinyint(1) NOT NULL DEFAULT 0,
			ban_reason text,
			PRIMARY KEY (id),
			UNIQUE KEY group_user_uk (group_id,user_id),
			KEY user_idx (user_id),
			KEY role_idx (role),
			KEY banned_idx (is_banned)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createGroupMetaTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_meta';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY group_key_idx (group_id,meta_key)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createGroupTypesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_types';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			directory_default tinyint(1) NOT NULL DEFAULT 0,
			show_in_create tinyint(1) NOT NULL DEFAULT 1,
			create_roles text,
			member_roles text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createGroupInvitesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_group_invites';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			inviter_id bigint(20) unsigned NOT NULL,
			message text,
			status enum('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			responded_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY invite_uk (group_id,user_id,status),
			KEY user_idx (user_id),
			KEY inviter_idx (inviter_id),
			KEY status_idx (status)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMemberTypesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_member_types';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			labels longtext,
			directory_default tinyint(1) NOT NULL DEFAULT 0,
			show_on_profile tinyint(1) NOT NULL DEFAULT 1,
			allowed_roles text,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY sort_idx (sort_order)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createBadgesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_badges';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			slug varchar(100) NOT NULL,
			description text,
			icon varchar(500) DEFAULT NULL,
			color varchar(7) DEFAULT '#f59e0b',
			rarity enum('common','uncommon','rare','epic','legendary') NOT NULL DEFAULT 'common',
			category varchar(50) DEFAULT NULL,
			points_value int(11) NOT NULL DEFAULT 0,
			is_secret tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			auto_award_criteria longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug_uk (slug),
			KEY rarity_idx (rarity),
			KEY category_idx (category),
			KEY active_idx (is_active)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createUserBadgesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_user_badges';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			badge_id bigint(20) unsigned NOT NULL,
			awarded_by bigint(20) unsigned DEFAULT NULL,
			reason varchar(255) DEFAULT NULL,
			is_featured tinyint(1) NOT NULL DEFAULT 0,
			awarded_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_badge_uk (user_id,badge_id),
			KEY badge_idx (badge_id),
			KEY featured_idx (is_featured),
			KEY awarded_idx (awarded_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createReportsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_reports';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			reporter_id bigint(20) unsigned NOT NULL,
			reported_user_id bigint(20) unsigned DEFAULT NULL,
			content_type varchar(50) NOT NULL,
			content_id bigint(20) unsigned NOT NULL,
			reason enum('spam','harassment','inappropriate','copyright','other') NOT NULL,
			description text,
			evidence longtext,
			status enum('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
			priority enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
			assigned_to bigint(20) unsigned DEFAULT NULL,
			resolution_notes text,
			resolved_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			resolved_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY reporter_idx (reporter_id),
			KEY reported_user_idx (reported_user_id),
			KEY content_idx (content_type,content_id),
			KEY status_idx (status),
			KEY priority_idx (priority),
			KEY assigned_idx (assigned_to)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMessageThreadsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_message_threads';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subject varchar(255) DEFAULT NULL,
			type enum('private','group') NOT NULL DEFAULT 'private',
			creator_id bigint(20) unsigned NOT NULL,
			last_message_id bigint(20) unsigned DEFAULT NULL,
			message_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY creator_idx (creator_id),
			KEY type_idx (type),
			KEY updated_idx (updated_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMessagesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_messages';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			thread_id bigint(20) unsigned NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			content longtext NOT NULL,
			attachments longtext,
			is_system tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			edited_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY thread_idx (thread_id),
			KEY sender_idx (sender_id),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createMessageParticipantsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_message_participants';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			thread_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			last_read_at datetime DEFAULT NULL,
			unread_count int(11) NOT NULL DEFAULT 0,
			is_muted tinyint(1) NOT NULL DEFAULT 0,
			is_starred tinyint(1) NOT NULL DEFAULT 0,
			is_deleted tinyint(1) NOT NULL DEFAULT 0,
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY thread_user_uk (thread_id,user_id),
			KEY user_idx (user_id),
			KEY starred_idx (is_starred),
			KEY deleted_idx (is_deleted)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createInvitationsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_invitations';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inviter_id bigint(20) unsigned NOT NULL,
			email varchar(255) NOT NULL,
			name varchar(255) DEFAULT NULL,
			group_id bigint(20) unsigned DEFAULT NULL,
			code varchar(64) NOT NULL,
			status enum('pending','accepted','expired','cancelled') NOT NULL DEFAULT 'pending',
			accepted_user_id bigint(20) unsigned DEFAULT NULL,
			resent_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			accepted_at datetime DEFAULT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY code_uk (code),
			KEY inviter_idx (inviter_id),
			KEY email_idx (email),
			KEY status_idx (status),
			KEY group_idx (group_id),
			KEY expires_idx (expires_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createActivityCommentsTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity_comments';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			activity_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			content text NOT NULL,
			likes_count int(11) NOT NULL DEFAULT 0,
			is_hidden tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY activity_idx (activity_id),
			KEY user_idx (user_id),
			KEY parent_idx (parent_id),
			KEY created_idx (created_at)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function createActivityLikesTable(): void {
		global $wpdb;
		$t=$wpdb->prefix.'apollo_activity_likes';$c=$wpdb->get_charset_collate();
		$sql="CREATE TABLE {$t} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			activity_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			reaction_type varchar(20) NOT NULL DEFAULT 'like',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY activity_user_uk (activity_id,user_id),
			KEY user_idx (user_id),
			KEY reaction_idx (reaction_type)
		) {$c};";
		require_once ABSPATH.'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
