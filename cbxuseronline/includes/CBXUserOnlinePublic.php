<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/public
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXUserOnlinePublic {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;


	public $setting;


	public function __construct() {
		$this->plugin_name = CBX_USERONLINE_PLUGIN_NAME;
		$this->version     = CBX_USERONLINE_PLUGIN_VERSION;

		$this->setting = new CBXUseronlineSetting();
	}

	public function init_shortcodes() {
		//add shortcode
		add_shortcode( 'cbxuseronline', [ $this, 'cbxuseronline_shortcode' ] );
	}//end method init_shortcodes

	/**
	 * Shortcode function for cbxuseronline
	 *
	 * @param $atts attributes
	 *
	 * return mixed
	 */
	public function cbxuseronline_shortcode( $atts ) {
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// Attributes
		$atts = shortcode_atts(
			[
				'count'            => 1, //show user count
				'count_individual' => 1, //show individual count as per user type  member, guest and bot
				'member_count'     => 1, //show member user type count
				'guest_count'      => 1, //show guest user type count
				'bot_count'        => 1, //show bot user type count
				'page'             => 0, //show count for current page or not
				'mobile'           => 1, //show user mobile or desktop login information
				'memberlist'       => 1, //show member list
				'mostuseronline'   => 1, //most user online date and count,
				'linkusername'     => 1  //link member

			], $atts, 'cbxuseronline' );


		$atts['page'] = absint( $atts['page'] );
		$atts['page'] = ( $atts['page'] ) ? (isset($_SERVER['REQUEST_URI'])? sanitize_text_field( wp_unslash($_SERVER['REQUEST_URI']) ) : '') : '';

		$atts['count']            = absint( $atts['count'] );
		$atts['count_individual'] = absint( $atts['count_individual'] );
		$atts['member_count']     = absint( $atts['member_count'] );
		$atts['guest_count']      = absint( $atts['guest_count'] );
		$atts['bot_count']        = absint( $atts['bot_count'] );
		$atts['mobile']           = absint( $atts['mobile'] );
		$atts['memberlist']       = absint( $atts['memberlist'] );
		$atts['mostuseronline']   = absint( $atts['mostuseronline'] );
		$atts['linkusername']     = absint( $atts['linkusername'] );


		$scope = "shortcode";

		return CBXUseronlineHelper::cbxuseronline_display( $atts, $scope );
	}//end method cbxuseronline_shortcode


	private static function get_title() {
		if ( is_admin() && function_exists( 'get_admin_page_title' ) ) {
			$page_title = ' &raquo; ' . esc_html__( 'Admin', 'cbxuseronline' ) . ' &raquo; ' . get_admin_page_title();
		} else {
			$page_title = wp_title( '&raquo;', false );
			if ( empty( $page_title ) ) {
				$page_title = ' &raquo; ' . (isset($_SERVER['REQUEST_URI'])? sanitize_text_field( wp_unslash($_SERVER['REQUEST_URI']) ) : '');
			} elseif ( is_singular() ) {
				$page_title = ' &raquo; ' . esc_html__( 'Archive', 'cbxuseronline' ) . ' ' . $page_title;
			}
		}

		return get_bloginfo( 'name' ) . $page_title;
	}//end method get_title

	/**
	 * Log user visit
	 *
	 * @since 1.0.0
	 *
	 */
	public function log_visit( $page_url = '', $page_title = '' ) {
		global $wpdb;
		$settings = $this->setting;

		$current_user = wp_get_current_user();
		if ( absint($current_user->ID) ) {
			$ignore_user_roles   = $settings->get_field( 'ignore_user_roles', 'cbxuseronline_basics', [] );
			if(is_array($ignore_user_roles) && count($ignore_user_roles)){
				$user_roles = ( array ) $current_user->roles;

				if( count(array_intersect($ignore_user_roles, $user_roles))) return;
			}
		}

		if ( empty( $page_url ) ) {
			$page_url = isset($_SERVER['REQUEST_URI'])? sanitize_text_field( wp_unslash($_SERVER['REQUEST_URI']) ) : '';
		}

		if ( empty( $page_title ) ) {
			$page_title = self::get_title();
		}


		$referral     = CBXUseronlineHelper::get_referral();
		$user_ip      = CBXUseronlineHelper::get_ipaddress();
		$user_agent   = CBXUseronlineHelper::get_useragent();
		$bots         = CBXUseronlineHelper::get_bots();


		$bot_found = false;
		$user_id   = '';

		foreach ( $bots as $name => $lookfor ) {
			if ( stristr( $user_agent, $lookfor ) !== false ) {

				$user_id   = isset($_COOKIE[ CBX_USERONLINE_COOKIE_NAME ])? sanitize_text_field(wp_unslash($_COOKIE[ CBX_USERONLINE_COOKIE_NAME ])) : '';
				$user_name = $name;
				$username  = $lookfor;
				$user_type = 'bot';
				$bot_found = true;

				break;
			}
		}


		// If No Bot Is Found, Then We Check Members And Guests
		if ( ! $bot_found ) {
			if ( $current_user->ID ) {
				// Check For Member
				$user_id   = absint($current_user->ID);
				$user_name = $current_user->display_name;
				$user_type = 'user';
				$where     = $wpdb->prepare( "WHERE user_id = %d", $user_id );
			} elseif ( isset( $_COOKIE[ CBX_USERONLINE_COOKIE_NAME ] ) ) {
				$user_id = isset($_COOKIE[ CBX_USERONLINE_COOKIE_NAME ])? sanitize_text_field(wp_unslash($_COOKIE[ CBX_USERONLINE_COOKIE_NAME ])) : '';

				$user_name = ( ! empty( $_COOKIE[ 'comment_author_' . COOKIEHASH ] ) ) ? trim( wp_strip_all_tags( wp_unslash($_COOKIE[ 'comment_author_' . COOKIEHASH ]) ) ) : esc_html__( 'Guest', 'cbxuseronline' );
				$user_type = 'guest';
			}
		} else {
			//we will only log user's visit
			return;
		}

		$mobile = ( CBXUseronlineHelper::is_mobile() ) ? 1 : 0;


		// Current GMT Timestamp
		$timestamp = current_time( 'mysql' );

		$online_user_table = CBXUseronlineHelper::get_tablename();

		$userid = $user_id;

		$cbxuseronline_basics = get_option( 'cbxuseronline_basics', [] );
		$refresh_time         = isset( $cbxuseronline_basics['refreshtime'] ) ? intval( $cbxuseronline_basics['refreshtime'] ) : 3600;


		// Purge table
		do_action( 'cbxuseronline_record_refreshed_before' );

		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$real_purge = $wpdb->query( $wpdb->prepare( "DELETE FROM $online_user_table WHERE userid = %s OR timestamp < DATE_SUB(%s, INTERVAL %d SECOND)", $userid, $timestamp, $refresh_time ) );

		if ( $real_purge !== false ) {
			do_action( 'cbxuseronline_record_refreshed' );
		} else {
			do_action( 'cbxuseronline_record_refreshed_failed' );
		}


		// Insert Users
		$data = compact( 'timestamp', 'user_type', 'userid', 'user_name', 'user_ip', 'user_agent', 'page_title', 'page_url', 'referral', 'mobile' );
		$data = stripslashes_deep( $data );


		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace( $online_user_table, $data );

		// Count Users Online
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$mostonline_now = intval( $wpdb->get_var( "SELECT COUNT( * ) FROM $online_user_table" ) );

		$mostonline_old = get_option( 'cbxuseronline_mostonline' );
		if ( $mostonline_old === false || ( $mostonline_now > intval( $mostonline_old['count'] ) ) ) {
			update_option( 'cbxuseronline_mostonline', [
				'count' => $mostonline_now,
				'date'  => current_time( 'timestamp' )
			] );
		}
	}//end method log_visit

	/**
	 * Remove user from logged in log table on logout
	 */
	function remove_user_log() {
		global $wpdb;
		$online_user_table = CBXUseronlineHelper::get_tablename();

		$userinfo = wp_get_current_user();
		$user_id  = absint($userinfo->ID);

		//on logout user delete user's log from online table
		if ( $user_id > 0 ) {
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM $online_user_table WHERE userid=%d", $user_id ) );
		}
	}//end method remove_user_log


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_register_style( 'cbxuseronline-public', plugin_dir_url( __FILE__ ) . '../assets/css/cbxuseronline-public.css', [], $this->version);
		wp_enqueue_style( 'cbxuseronline-public' );
	}//end method enqueue_styles

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		//wp_register_script('cbxuseronline-public', plugin_dir_url(__FILE__) . 'js/cbxuseronline-public.js', array('jquery'), $this->version, false);
		//wp_enqueue_script('cbxuseronline-public');
	}//end method enqueue_scripts

	public function widgets_init() {
		register_widget( 'CBXOnlineWidget' );
	}//end method widgets_init


	/**
	 * Init elementor widget
	 *
	 * @throws Exception
	 */
	public function init_elementor_widgets() {
		//include the file
		if ( ! class_exists( 'CBXOnlineElementorWidget' ) ) {
			require_once CBX_USERONLINE_PLUGIN_ROOT_PATH . 'widgets/elementor-widget/class-cbxonline-elementor-widget.php';
		}

		//register the widget
		\Elementor\Plugin::instance()->widgets_manager->register( new CBXOnlineElementorWidget\Widgets\CBXOnlineElementorWidget() );

	}//end method widgets_registered


	/**
	 * Add new category to elementor
	 *
	 * @param $elements_manager
	 */
	public function add_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'cbxuseronline',
			[
				'title' => esc_html__( 'CBX User Online', 'cbxuseronline' ),
				'icon'  => 'fa fa-plug',
			]
		);
	}//end method add_elementor_widget_categories

	/**
	 * Load Elementor Custom Icon
	 */
	function elementor_icon_loader() {
		wp_register_style( 'cbx_useronline_elementor_icon',
			CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/css/cbxuseronline-elementor.css', false,
			$this->version );
		wp_enqueue_style( 'cbx_useronline_elementor_icon' );
	}//end method elementor_icon_loader


	/**
	 * // Before VC Init
	 */
	public function vc_before_init_actions() {
		if ( ! class_exists( 'CBXOnlineWPBWidget' ) ) {
			require_once CBX_USERONLINE_PLUGIN_ROOT_PATH . 'widgets/vc-widget/class-cbxonline-wpb-widget.php';
		}
	}// end method vc_before_init_actions


	/**
	 * Record any user last login and create a meta
	 *
	 * @param $user_login
	 * @param $user
	 *
	 * @since 1.0.6
	 *
	 */
	public function record_last_login( $user_login, $user ) {
		$user_id = $user->ID;

		$record_second_last_login = $this->setting->get_option( 'record_second_last_login', 'cbxuseronline_integration', '' );

		$lastlogin_data = get_user_meta( $user_id, '_cbxuseronline_lastlogin_data', true );
		if ( ! is_array( $lastlogin_data ) ) {
			$lastlogin_data = [];
		}


		$login_count = isset( $lastlogin_data['login_count'] ) ? intval( $lastlogin_data['login_count'] ) : 0;
		$login_count ++;
		$lastlogin_data['login_count']  = $login_count;
		$lastlogin_data['login_mobile'] = CBXUseronlineHelper::is_mobile() ? 'mobile' : 'desktop';
		$lastlogin_data['ip_address']   = CBXUseronlineHelper::get_ipaddress();


		do_action( 'cbxuseronline_last_login_before_update', $user_login, $user );

		//update meta field for login data
		update_user_meta( $user_id, '_cbxuseronline_lastlogin_data', $lastlogin_data );


		if ( $record_second_last_login == 'on' ) {
			$lastlogin_time = get_user_meta( $user_id, '_cbxuseronline_lastlogin_time', true );
			if ( $lastlogin_time !== false ) {
				update_user_meta( $user_id, '_cbxuseronline_second_lastlogin_time', $lastlogin_time );
			}
		}

		//update meta field for last login time
		update_user_meta( $user_id, '_cbxuseronline_lastlogin_time', current_time( 'timestamp' ) );

		do_action( 'cbxuseronline_last_login', $user_login, $user );

	}//end method record_last_login

}//end class CBXUserOnlinePublic