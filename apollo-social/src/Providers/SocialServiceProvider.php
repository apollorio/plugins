<?php
declare(strict_types=1);
namespace Apollo\Providers;

final class SocialServiceProvider {

	public static function boot(): void {
		add_action( 'init', array( self::class, 'init' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAdminAssets' ) );
		\Apollo\Api\AjaxHandlers::register();
		\Apollo\Api\RestApiHandlers::register();
		\Apollo\Modules\Integration\IntegrationHooksRepository::registerTriggers();
		\Apollo\Modules\Integration\IntegrationHooksRepository::registerAdditionalTriggers();
		add_action( 'apollo_daily_cron', array( self::class, 'dailyCron' ) );
		add_action( 'apollo_hourly_cron', array( self::class, 'hourlyCron' ) );
		if ( ! wp_next_scheduled( 'apollo_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_daily_cron' );
		}
		if ( ! wp_next_scheduled( 'apollo_hourly_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'apollo_hourly_cron' );
		}
	}

	public static function init(): void {
		self::registerShortcodes();
	}

	public static function registerShortcodes(): void {
		add_shortcode( 'apollo_members_directory', array( self::class, 'membersDirectoryShortcode' ) );
		add_shortcode( 'apollo_activity_feed', array( self::class, 'activityFeedShortcode' ) );
		add_shortcode( 'apollo_groups_directory', array( self::class, 'groupsDirectoryShortcode' ) );
		add_shortcode( 'apollo_leaderboard', array( self::class, 'leaderboardShortcode' ) );
		add_shortcode( 'apollo_online_users', array( self::class, 'onlineUsersShortcode' ) );
		add_shortcode( 'apollo_my_profile', array( self::class, 'myProfileShortcode' ) );
		add_shortcode( 'apollo_team_members', array( self::class, 'teamMembersShortcode' ) );
		add_shortcode( 'apollo_testimonials', array( self::class, 'testimonialsShortcode' ) );
		add_shortcode( 'apollo_map', array( self::class, 'mapShortcode' ) );
		add_shortcode( 'apollo_notices', array( self::class, 'noticesShortcode' ) );
	}

	public static function enqueueAssets(): void {
		if ( is_user_logged_in() ) {
			wp_localize_script(
				'jquery',
				'apolloSocial',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'restUrl' => rest_url( 'apollo/v1/' ),
					'nonce'   => wp_create_nonce( 'apollo_nonce' ),
					'userId'  => get_current_user_id(),
				)
			);
		}
	}

	public static function enqueueAdminAssets(): void {
		wp_localize_script(
			'jquery',
			'apolloSocialAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_admin_nonce' ),
			)
		);
	}

	public static function dailyCron(): void {
		\Apollo\Modules\Members\OnlineUsersRepository::cleanup( 1440 );
		\Apollo\Modules\Email\EmailQueueRepository::cleanup( 30 );
		\Apollo\Modules\Gamification\CompetitionsRepository::activateScheduled();
		\Apollo\Modules\Gamification\CompetitionsRepository::endFinished();
	}

	public static function hourlyCron(): void {
		\Apollo\Modules\Email\EmailQueueRepository::process( 50 );
	}

	public static function membersDirectoryShortcode( array $atts = array() ): string {
		$a       = shortcode_atts(
			array(
				'limit'       => 20,
				'show_search' => 'true',
			),
			$atts
		);
		$members = \Apollo\Modules\Members\MembersDirectoryRepository::search( array( 'limit' => (int) $a['limit'] ) );
		ob_start();
		echo '<div class="apollo-members-directory" data-limit="' . esc_attr( $a['limit'] ) . '">';
		foreach ( $members as $m ) {
			echo '<div class="apollo-member-card" data-id="' . esc_attr( $m['ID'] ) . '">';
			echo '<img src="' . esc_url( get_avatar_url( $m['ID'], array( 'size' => 80 ) ) ) . '" alt="' . esc_attr( $m['display_name'] ) . '">';
			echo '<span class="name">' . esc_html( $m['display_name'] ) . '</span>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function activityFeedShortcode( array $atts = array() ): string {
		$a    = shortcode_atts(
			array(
				'limit'   => 20,
				'user_id' => null,
			),
			$atts
		);
		$args = array( 'limit' => (int) $a['limit'] );
		if ( $a['user_id'] ) {
			$args['user_id'] = (int) $a['user_id'];
		}
		$activity = \Apollo\Modules\Activity\ActivityRepository::getFeed( $args );
		ob_start();
		echo '<div class="apollo-activity-feed">';
		foreach ( $activity as $act ) {
			echo '<div class="apollo-activity-item" data-id="' . esc_attr( $act['id'] ) . '">';
			echo '<strong>' . esc_html( $act['display_name'] ?? '' ) . '</strong>: ';
			echo wp_kses_post( $act['content'] );
			echo '<small>' . esc_html( human_time_diff( strtotime( $act['created_at'] ) ) ) . ' ago</small>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function groupsDirectoryShortcode( array $atts = array() ): string {
		$a      = shortcode_atts( array( 'limit' => 20 ), $atts );
		$groups = \Apollo\Modules\Groups\GroupsRepository::search( array( 'limit' => (int) $a['limit'] ) );
		ob_start();
		echo '<div class="apollo-groups-directory">';
		foreach ( $groups as $g ) {
			echo '<div class="apollo-group-card" data-id="' . esc_attr( $g['id'] ) . '">';
			echo '<span class="name">' . esc_html( $g['name'] ) . '</span>';
			echo '<span class="members">' . esc_html( $g['member_count'] ) . ' members</span>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function leaderboardShortcode( array $atts = array() ): string {
		$a  = shortcode_atts(
			array(
				'limit' => 10,
				'type'  => 'points',
			),
			$atts
		);
		$lb = \Apollo\Modules\Gamification\PointsRepository::getLeaderboard( 'default', (int) $a['limit'] );
		ob_start();
		echo '<div class="apollo-leaderboard"><ol>';
		foreach ( $lb as $entry ) {
			echo '<li><span class="user">' . esc_html( $entry['display_name'] ?? '' ) . '</span><span class="points">' . number_format( (float) $entry['balance'] ) . '</span></li>';
		}
		echo '</ol></div>';
		return ob_get_clean();
	}

	public static function onlineUsersShortcode( array $atts = array() ): string {
		$a     = shortcode_atts( array( 'limit' => 20 ), $atts );
		$users = \Apollo\Modules\Members\OnlineUsersRepository::getOnlineUsers( (int) $a['limit'] );
		ob_start();
		echo '<div class="apollo-online-users">';
		echo '<span class="count">' . count( $users ) . ' online</span>';
		foreach ( $users as $u ) {
			echo '<span class="user">' . esc_html( $u['display_name'] ) . '</span>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function myProfileShortcode( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>Please log in to view your profile.</p>';
		}
		$userId = get_current_user_id();
		$stats  = \Apollo\Modules\MyData\MyDataRepository::getMyStats( $userId );
		ob_start();
		echo '<div class="apollo-my-profile">';
		echo '<p>Friends: ' . esc_html( $stats['friends_count'] ) . '</p>';
		echo '<p>Groups: ' . esc_html( $stats['groups_count'] ) . '</p>';
		echo '<p>Points: ' . number_format( (float) $stats['points'] ) . '</p>';
		echo '<p>Profile: ' . esc_html( $stats['profile_completeness'] ) . '% complete</p>';
		echo '</div>';
		return ob_get_clean();
	}

	public static function teamMembersShortcode( array $atts = array() ): string {
		$a       = shortcode_atts( array( 'department' => '' ), $atts );
		$members = $a['department'] ? \Apollo\Modules\Team\TeamMembersRepository::getByDepartment( $a['department'] ) : \Apollo\Modules\Team\TeamMembersRepository::getAll();
		ob_start();
		echo '<div class="apollo-team-members">';
		foreach ( $members as $m ) {
			echo '<div class="team-member">';
			if ( $m['photo_url'] ) {
				echo '<img src="' . esc_url( $m['photo_url'] ) . '" alt="' . esc_attr( $m['name'] ) . '">';
			}
			echo '<h4>' . esc_html( $m['name'] ) . '</h4>';
			echo '<span class="position">' . esc_html( $m['position'] ) . '</span>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function testimonialsShortcode( array $atts = array() ): string {
		$a     = shortcode_atts(
			array(
				'limit'    => 5,
				'featured' => 'false',
			),
			$atts
		);
		$items = $a['featured'] === 'true' ? \Apollo\Modules\Testimonials\TestimonialsRepository::getFeatured( (int) $a['limit'] ) : \Apollo\Modules\Testimonials\TestimonialsRepository::getApproved( (int) $a['limit'] );
		ob_start();
		echo '<div class="apollo-testimonials">';
		foreach ( $items as $t ) {
			echo '<blockquote>';
			echo wp_kses_post( $t['content'] );
			echo '<cite>' . esc_html( $t['author_name'] );
			if ( $t['author_title'] ) {
				echo ', ' . esc_html( $t['author_title'] );
			}
			echo '</cite></blockquote>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function mapShortcode( array $atts = array() ): string {
		$a       = shortcode_atts(
			array(
				'category' => '',
				'height'   => '400px',
			),
			$atts
		);
		$pins    = $a['category'] ? \Apollo\Modules\Maps\MapPinsRepository::getByCategory( $a['category'] ) : \Apollo\Modules\Maps\MapPinsRepository::getAll();
		$geojson = \Apollo\Modules\Maps\MapPinsRepository::toGeoJson( $pins );
		return '<div class="apollo-map" style="height:' . esc_attr( $a['height'] ) . '" data-pins="' . esc_attr( wp_json_encode( $geojson ) ) . '"></div>';
	}

	public static function noticesShortcode( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$userId  = get_current_user_id();
		$notices = \Apollo\Modules\Notices\NoticesRepository::getActiveForUser( $userId );
		if ( empty( $notices ) ) {
			return '';
		}
		ob_start();
		echo '<div class="apollo-notices">';
		foreach ( $notices as $n ) {
			echo '<div class="notice notice-' . esc_attr( $n['type'] ) . '" data-id="' . esc_attr( $n['id'] ) . '">';
			echo '<strong>' . esc_html( $n['title'] ) . '</strong>';
			echo wp_kses_post( $n['content'] );
			if ( $n['is_dismissible'] ) {
				echo '<button class="dismiss" data-notice="' . esc_attr( $n['id'] ) . '">×</button>';
			}
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}
}
