<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXUserOnline {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.2.10
	 */
	private static $instance = null;

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


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = CBX_USERONLINE_PLUGIN_NAME;
		$this->version     = CBX_USERONLINE_PLUGIN_VERSION;


		$this->load_dependencies();


		$this->define_common_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}//end constructor

	/**
	 * Singleton Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @return self Main instance.
	 * @see run_cbxuseronline()
	 * @since  1.2.10
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}//end method instance

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - CBXUserOnline_Loader. Orchestrates the hooks of the plugin.
	 * - CBXUserOnline_i18n. Defines internationalization functionality.
	 * - CBXUserOnlineAdmin. Defines all hooks for the admin area.
	 * - CBXUserOnlinePublic. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/cbxuseronline-tpl-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CBXUserOnlineHelper.php';     //helper method , call all statically
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/cbxuseronline-functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CBXUseronlineSetting.php';   //add the setting api
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'widgets/classic-widget/cbxonline-widget.php'; //widget
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CBXUserOnlineAdmin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CBXUserOnlinePublic.php';
	}//end method load_dependencies

	private function init_cookie() {

	}//end method init_cookie

	/**
	 * All the common hooks
	 *
	 * @since    1.2.10
	 * @access   private
	 */
	private function define_common_hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'house_keepings' ] );


		add_action( 'init', [ 'CBXUseronlineHelper', 'init_cookie' ] );
	}//end method define_common_hooks

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.1.1
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'cbxuseronline', false, CBX_USERONLINE_PLUGIN_ROOT_PATH . 'languages/' );
	}//end method load_plugin_textdomain

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new CBXUserOnlineAdmin();

		add_action( 'admin_enqueue_scripts', [ $plugin_admin, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $plugin_admin, 'enqueue_scripts' ] );

		// Initialize admin settings
		add_action( 'admin_init', [ $plugin_admin, 'init_settings' ], 0 );
		//add_action( 'admin_init', [$plugin_admin, 'plugin_reset'], 1 );
		// Add the options page and menu item.
		add_action( 'admin_menu', [ $plugin_admin, 'admin_pages' ] );


		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, [ $plugin_admin, 'add_action_links' ] );
		add_filter( 'plugin_row_meta', [ $plugin_admin, 'custom_plugin_row_meta' ], 10, 2 );


		//ajax refresh database
		add_action( 'wp_ajax_cbxuseronline_online_user_record_clean', [ $plugin_admin, 'online_user_record_clean' ] );

		//last login  from v1.0.6
		add_filter( 'manage_users_columns', [ $plugin_admin, 'users_columns_lastlogin' ] );
		add_filter( 'manage_users_sortable_columns', [ $plugin_admin, 'users_sortable_columns_lastlogin' ] );
		add_filter( 'manage_users_custom_column', [ $plugin_admin, 'users_custom_column_lastlogin' ], 10, 3 );
		add_action( 'pre_get_users', [ $plugin_admin, 'pre_get_users_lastlogin' ] );

		//update manager
		//add_action( 'upgrader_process_complete', [ $plugin_admin, 'plugin_upgrader_process_complete' ], 10, 2 );
		add_action( 'plugins_loaded', [ $plugin_admin, 'plugin_upgrader_process_complete' ] );
		add_action( 'admin_notices', [ $plugin_admin, 'plugin_activate_upgrade_notices' ] );
		add_action( 'after_plugin_row_cbxuseronlineproaddon/cbxuseronlineproaddon.php', [ $plugin_admin, 'custom_message_after_plugin_row_proaddon' ], 10, 2 );

		//ajax plugin reset
		add_action( 'wp_ajax_cbxuseronline_settings_reset_load', [ $plugin_admin, 'settings_reset_load' ] );
		add_action( 'wp_ajax_cbxuseronline_settings_reset', [ $plugin_admin, 'plugin_reset' ] );
		add_action('cbxuseronline_plugin_reset', [$plugin_admin, 'plugin_reset_extend']);
	}//end method define_admin_hooks


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new CBXUserOnlinePublic();

		add_action( 'init', [ $plugin_public, 'init_shortcodes' ] );

		//add user's visit to table
		add_action( 'admin_head', [ $plugin_public, 'log_visit' ] );

		//last login  from v1.0.6
		add_action( 'wp_login', [ $plugin_public, 'record_last_login' ], 10, 2 );


		add_action( 'wp_enqueue_scripts', [ $plugin_public, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $plugin_public, 'enqueue_scripts' ] );

		//add user's visit to table
		add_action( 'wp_head', [ $plugin_public, 'log_visit' ] );

		add_action( 'widgets_init', [ $plugin_public, 'widgets_init' ] );

		add_action( 'clear_auth_cookie', [ $plugin_public, 'remove_user_log' ], 10 );

		//elementor
		add_action( 'elementor/widgets/widgets_registered', [ $plugin_public, 'init_elementor_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ $plugin_public, 'add_elementor_widget_categories' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $plugin_public, 'elementor_icon_loader' ], 99999 );

		//visual composer widget
		add_action( 'vc_before_init', [ $plugin_public, 'vc_before_init_actions' ] );
	}//end method define_public_hooks


	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}//end method get_plugin_name

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}//end method get_version

	/**
	 * Do some housekeeping
	 *
	 * @return void
	 */
	public function house_keepings(){
		//on core plugin activation check pro addon compatibility
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( defined( 'CBX_USERONLINEPROADDON_PLUGIN_NAME' ) || in_array( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ) {
			//plugin is activated

			$plugin_version = CBX_USERONLINEPROADDON_PLUGIN_VERSION;

			if ( version_compare( $plugin_version, '1.0.15', '<' ) ) {
				deactivate_plugins( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php' );
				set_transient( 'cbxuseronline_deactivated_notice', 1 );
			}
		}//end checking pro addon compatibility
	}//end method house_keepings
}//end class CBXUserOnline