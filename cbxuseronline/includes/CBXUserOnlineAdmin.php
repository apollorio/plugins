<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CBXUserOnline
 * @subpackage CBXUserOnline/admin
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXUserOnlineAdmin {
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

	/**
	 * Registers settings section and fields
	 */
	public function init_settings() {
		$sections = $this->get_settings_sections();
		$fields   = $this->get_settings_fields();

		//set sections and fields
		$this->setting->set_sections( $sections );
		$this->setting->set_fields( $fields );

		//initialize them
		$this->setting->admin_init();
	}//end init_settings

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		global $post_type, $post;

		$version = $this->version;
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';


		$css_url_part     = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/css/';
		$js_url_part      = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/js/';
		$vendors_url_part = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/vendors/';

		$css_path_part     = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/css/';
		$js_path_part      = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/js/';
		$vendors_path_part = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/vendors/';

		if ( $page == 'cbxuseronline-settings' ) {
			wp_register_style( 'awesome-notifications', $vendors_url_part . 'awesome-notifications/style.css', [], $version );
			wp_register_style( 'pickr', $vendors_url_part . 'pickr/classic.min.css', [], $version );
			wp_register_style( 'select2', $vendors_url_part . 'select2/select2.min.css', [], $version );

			wp_register_style( 'cbxuseronline-admin', $css_url_part . 'cbxuseronline-admin.css', [], $version );
			wp_register_style( 'cbxuseronline-setting', $css_url_part . 'cbxuseronline-setting.css',
				[ 'pickr', 'select2', 'awesome-notifications', 'cbxuseronline-admin' ], $version );

			wp_enqueue_style( 'pickr' );
			wp_enqueue_style( 'select2' );
			wp_enqueue_style( 'awesome-notifications' );

			wp_enqueue_style( 'cbxuseronline-admin' );//common admin styles
			wp_enqueue_style( 'cbxuseronline-setting' );
		}

		if ( $page == 'cbxuseronline-support' ) {
			wp_register_style( 'cbxuseronline-admin', $css_url_part . 'cbxuseronline-admin.css', [], $version );
			wp_enqueue_style( 'cbxuseronline-admin' );//common admin styles
		}


		if ( $page == 'cbxuseronline' ) {
			wp_register_style( 'awesome-notifications', $vendors_url_part . 'awesome-notifications/style.css', [], $version );
			wp_register_style( 'jquery-tablesorter', $vendors_url_part . 'tablesorter/css/theme.default.min.css', [], $version );
			wp_register_style( 'cbxuseronline-admin', $css_url_part . 'cbxuseronline-admin.css', [ 'jquery-tablesorter', 'awesome-notifications' ], $version );


			wp_enqueue_style( 'awesome-notifications' );
			wp_enqueue_style( 'jquery-tablesorter' );
			wp_enqueue_style( 'cbxuseronline-admin' );
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_style( 'cbxuseronline-handle', false );
		wp_enqueue_style( 'cbxuseronline-handle' );
		wp_add_inline_style( 'cbxuseronline-handle', '#adminmenu .toplevel_page_cbxuseronline .wp-menu-image img{
    max-width: 20px !important;
}' );
	}//end method enqueue_styles

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $post_type, $post;

		$ver = $this->version;

		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view    = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$css_url_part     = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/css/';
		$js_url_part      = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/js/';
		$vendors_url_part = CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/vendors/';

		$css_path_part     = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/css/';
		$js_path_part      = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/js/';
		$vendors_path_part = CBX_USERONLINE_PLUGIN_ROOT_PATH . 'assets/vendors/';

		$translation_placeholder =
			[
				'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				'ajax_fail'                => esc_html__( 'Request failed, please reload the page.', 'cbxuseronline' ),
				'nonce'                    => wp_create_nonce( "settingsnonce" ),
				'is_user_logged_in'        => is_user_logged_in() ? 1 : 0,
				'please_select'            => esc_html__( 'Please Select', 'cbxuseronline' ),
				'upload_title'             => esc_html__( 'Window Title', 'cbxuseronline' ),
				'search_placeholder'       => esc_html__( 'Search here', 'cbxuseronline' ),
				'teeny_setting'            => [
					'teeny'         => true,
					'media_buttons' => true,
					'editor_class'  => '',
					'textarea_rows' => 5,
					'quicktags'     => false,
					'menubar'       => false,
				],
				'copycmds'                 => [
					'copy'       => esc_html__( 'Copy', 'cbxuseronline' ),
					'copied'     => esc_html__( 'Copied', 'cbxuseronline' ),
					'copy_tip'   => esc_html__( 'Click to copy', 'cbxuseronline' ),
					'copied_tip' => esc_html__( 'Copied to clipboard', 'cbxuseronline' ),
				],
				'confirm_msg'              => esc_html__( 'Are you sure to remove this step?', 'cbxuseronline' ),
				'confirm_msg_all'          => esc_html__( 'Are you sure to remove all steps?', 'cbxuseronline' ),
				'confirm_yes'              => esc_html__( 'Yes', 'cbxuseronline' ),
				'confirm_no'               => esc_html__( 'No', 'cbxuseronline' ),
				'are_you_sure_global'      => esc_html__( 'Are you sure?', 'cbxuseronline' ),
				'are_you_sure_delete_desc' => esc_html__( 'Once you delete, it\'s gone forever. You can not revert it back.', 'cbxuseronline' ),
				'pickr_i18n'               => [
					// Strings visible in the UI
					'ui:dialog'       => esc_html__( 'color picker dialog', 'cbxuseronline' ),
					'btn:toggle'      => esc_html__( 'toggle color picker dialog', 'cbxuseronline' ),
					'btn:swatch'      => esc_html__( 'color swatch', 'cbxuseronline' ),
					'btn:last-color'  => esc_html__( 'use previous color', 'cbxuseronline' ),
					'btn:save'        => esc_html__( 'Save', 'cbxuseronline' ),
					'btn:cancel'      => esc_html__( 'Cancel', 'cbxuseronline' ),
					'btn:clear'       => esc_html__( 'Clear', 'cbxuseronline' ),

					// Strings used for aria-labels
					'aria:btn:save'   => esc_html__( 'save and close', 'cbxuseronline' ),
					'aria:btn:cancel' => esc_html__( 'cancel and close', 'cbxuseronline' ),
					'aria:btn:clear'  => esc_html__( 'clear and close', 'cbxuseronline' ),
					'aria:input'      => esc_html__( 'color input field', 'cbxuseronline' ),
					'aria:palette'    => esc_html__( 'color selection area', 'cbxuseronline' ),
					'aria:hue'        => esc_html__( 'hue selection slider', 'cbxuseronline' ),
					'aria:opacity'    => esc_html__( 'selection slider', 'cbxuseronline' ),
				],
				'awn_options'              => [
					'tip'           => esc_html__( 'Tip', 'cbxuseronline' ),
					'info'          => esc_html__( 'Info', 'cbxuseronline' ),
					'success'       => esc_html__( 'Success', 'cbxuseronline' ),
					'warning'       => esc_html__( 'Attention', 'cbxuseronline' ),
					'alert'         => esc_html__( 'Error', 'cbxuseronline' ),
					'async'         => esc_html__( 'Loading', 'cbxuseronline' ),
					'confirm'       => esc_html__( 'Confirmation', 'cbxuseronline' ),
					'confirmOk'     => esc_html__( 'OK', 'cbxuseronline' ),
					'confirmCancel' => esc_html__( 'Cancel', 'cbxuseronline' )
				],
				'validation'               => [
					'required'    => esc_html__( 'This field is required.', 'cbxuseronline' ),
					'remote'      => esc_html__( 'Please fix this field.', 'cbxuseronline' ),
					'email'       => esc_html__( 'Please enter a valid email address.', 'cbxuseronline' ),
					'url'         => esc_html__( 'Please enter a valid URL.', 'cbxuseronline' ),
					'date'        => esc_html__( 'Please enter a valid date.', 'cbxuseronline' ),
					'dateISO'     => esc_html__( 'Please enter a valid date ( ISO ).', 'cbxuseronline' ),
					'number'      => esc_html__( 'Please enter a valid number.', 'cbxuseronline' ),
					'digits'      => esc_html__( 'Please enter only digits.', 'cbxuseronline' ),
					'equalTo'     => esc_html__( 'Please enter the same value again.', 'cbxuseronline' ),
					'maxlength'   => esc_html__( 'Please enter no more than {0} characters.', 'cbxuseronline' ),
					'minlength'   => esc_html__( 'Please enter at least {0} characters.', 'cbxuseronline' ),
					'rangelength' => esc_html__( 'Please enter a value between {0} and {1} characters long.', 'cbxuseronline' ),
					'range'       => esc_html__( 'Please enter a value between {0} and {1}.', 'cbxuseronline' ),
					'max'         => esc_html__( 'Please enter a value less than or equal to {0}.', 'cbxuseronline' ),
					'min'         => esc_html__( 'Please enter a value greater than or equal to {0}.', 'cbxuseronline' ),
					'recaptcha'   => esc_html__( 'Please check the captcha.', 'cbxuseronline' ),
				],
				'global_setting_link_html' => '<a href="' . admin_url( 'admin.php?page=cbxuseronline-settings' ) . '"  class="button outline primary pull-right">' . esc_html__( 'Global Settings', 'cbxuseronline' ) . '</a>',
				'lang'                     => get_user_locale(),
				'search_text'              => esc_html__( 'Search', 'cbxuseronline' )
			];

		//setting page
		if ( $page == 'cbxuseronline-settings' ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_media();

			wp_register_script( 'awesome-notifications', $vendors_url_part . 'awesome-notifications/script.js', [], $ver, true );
			wp_register_script( 'pickr', $vendors_url_part . 'pickr/pickr.min.js', [], $ver, true );
			wp_register_script( 'select2', $vendors_url_part . 'select2/select2.min.js', [ 'jquery' ], $ver, true );
			wp_register_script( 'cbxuseronline-setting', $js_url_part . 'cbxuseronline-setting.js',
				[
					'jquery',
					'select2',
					'pickr',
					'awesome-notifications'
				],
				$ver, true );


			wp_localize_script( 'cbxuseronline-setting', 'cbxuseronline_setting', apply_filters( 'cbxuseronline_setting_vars', $translation_placeholder ) );

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'pickr' );
			wp_enqueue_script( 'select2' );
			wp_enqueue_script( 'awesome-notifications' );

			wp_enqueue_script( 'cbxuseronline-setting' );
		}//end  page cbxuseronline-settings

		if ( $page == 'cbxuseronline' ) {
			wp_register_script( 'awesome-notifications', $vendors_url_part . 'awesome-notifications/script.js', [], $ver, true );
			wp_register_script( 'jquery-tablesorter', $vendors_url_part . 'tablesorter/jquery.tablesorter.min.js', [ 'jquery' ], $ver, true );
			wp_register_script( 'cbxuseronline-admin', $js_url_part . 'cbxuseronline-admin.js',
				[ 'jquery', 'jquery-tablesorter', 'awesome-notifications' ], $ver, true );

			$cbxuseronline_admin_js_vars = [
				'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				'ajax_fail'                => esc_html__( 'Request failed, please reload the page.', 'cbxuseronline' ),
				'nonce'                    => wp_create_nonce( "cbxuseronlinenonce" ),
				'is_user_logged_in'        => is_user_logged_in() ? 1 : 0,
				'confirm_msg'              => esc_html__( 'Are you sure to remove this step?', 'cbxuseronline' ),
				'confirm_msg_all'          => esc_html__( 'Are you sure to remove all steps?', 'cbxuseronline' ),
				'confirm_yes'              => esc_html__( 'Yes', 'cbxuseronline' ),
				'confirm_no'               => esc_html__( 'No', 'cbxuseronline' ),
				'are_you_sure_global'      => esc_html__( 'Are you sure?', 'cbxuseronline' ),
				'are_you_sure_delete_desc' => esc_html__( 'Once you delete, it\'s gone forever. You can not revert it back.', 'cbxuseronline' ),
				'awn_options'              => [
					'tip'           => esc_html__( 'Tip', 'cbxuseronline' ),
					'info'          => esc_html__( 'Info', 'cbxuseronline' ),
					'success'       => esc_html__( 'Success', 'cbxuseronline' ),
					'warning'       => esc_html__( 'Attention', 'cbxuseronline' ),
					'alert'         => esc_html__( 'Error', 'cbxuseronline' ),
					'async'         => esc_html__( 'Loading', 'cbxuseronline' ),
					'confirm'       => esc_html__( 'Confirmation', 'cbxuseronline' ),
					'confirmOk'     => esc_html__( 'OK', 'cbxuseronline' ),
					'confirmCancel' => esc_html__( 'Cancel', 'cbxuseronline' )
				],
			];

			wp_localize_script( 'cbxuseronline-admin', 'cbxuseronline_admin', apply_filters( 'cbxuseronline_admin_vars', $cbxuseronline_admin_js_vars ) );

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-tablesorter' );
			wp_enqueue_script( 'awesome-notifications' );
			wp_enqueue_script( 'cbxuseronline-admin' );
		}
	}//end method enqueue_scripts

	/**
	 * Plugin setting(option panel) sections
	 *
	 * @return array|mixed|void
	 */
	function get_settings_sections() {
		return CBXUserOnlineHelper::settings_sections();
	}//end method get_settings_sections

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	function get_settings_fields() {

		$table_html = '<div id="cbxuseronline_resetinfo_wrap">' . esc_html__( 'Loading ...', 'cbxuseronline' ) . '</div>';

		$settings_fields = [

			'cbxuseronline_basics' => apply_filters( 'cbxuseronline_basics_fields', [
				'basics_heading' => [
					'name'    => 'basics_heading',
					'label'   => esc_html__( 'User Online Settings', 'cbxuseronline' ),
					'type'    => 'heading',
					'default' => '',
				],
				'refreshtime'    => [
					'name'              => 'refreshtime',
					'label'             => esc_html__( 'Refresh Time', 'cbxuseronline' ),
					'desc'              => esc_html__( 'User visit log purge time or refresh time in seconds', 'cbxuseronline' ),
					'type'              => 'number',
					'default'           => '3600',
					'sanitize_callback' => 'intval'
				],
				'ignore_user_roles'  => [
					'name'    => 'ignore_user_roles',
					'label'   => esc_html__( 'Ignore user having role(s)', 'cbxuseronline' ),
					'default' => [],
					'multi' => 1,
					'optgroup' => 0,
					'type'    => 'select',
					'options' => CBXUseronlineHelper::user_roles(true, false)
				],
				/*'refreshtimenow' => [
					'name'  => 'refreshtimenow',
					'label' => esc_html__( 'Refresh Now', 'cbxuseronline' ),
					'desc'  => esc_html__( 'Delete all login log records', 'cbxuseronline' ),
					'type'  => 'refreshtimenow'
				]*/

			] ),

			'cbxuseronline_integration' => apply_filters( 'cbxuseronline_integration_fields', [
				'integration_heading'      => [
					'name'    => 'integration_heading',
					'label'   => esc_html__( 'Integration Settings', 'cbxuseronline' ),
					'type'    => 'heading',
					'default' => '',
				],
				'record_last_login'        => [
					'name'    => 'record_last_login',
					'label'   => esc_html__( 'Record user last login', 'cbxuseronline' ),
					'desc'    => esc_html__( 'When user login it will record and will show as extra col in admin user listing', 'cbxuseronline' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				'record_second_last_login' => [
					'name'    => 'record_second_last_login',
					'label'   => esc_html__( 'Record Second last login', 'cbxuseronline' ),
					'desc'    => esc_html__( 'This feature is useful to detect interval for last two login time.', 'cbxuseronline' ),
					'default' => '',
					'type'    => 'checkbox',
				],

			] ),
			'cbxuseronline_tools'       => apply_filters( 'cbxuseronline_tools_fields', [
					'tools_heading'        => [
						'name'    => 'tools_heading',
						'label'   => esc_html__( 'Tools Settings', 'cbxuseronline' ),
						'type'    => 'heading',
						'default' => '',
					],
					'delete_global_config' => [
						'name'     => 'delete_global_config',
						'label'    => esc_html__( 'On Uninstall delete plugin data', 'cbxuseronline' ),
						'desc'     => '<p>' . esc_html__( 'Delete Global Config data and custom table created by this plugin on uninstall.', 'cbxuseronline' ) . ' ' . wp_kses(__( 'Details table information is <a href="#cbxuseronline_plg_gfig_info">here</a>', 'cbxuseronline' ), ['a' => ['href' => []]]) . '</p>' . '<p><strong>' . esc_html__( 'Please note that this process can not be undone and it is recommended to keep full database backup before doing this.', 'cbxuseronline' ) . '</strong></p>',
						'type'     => 'radio',
						'options'  => [
							'yes' => esc_html__( 'Yes', 'cbxuseronline' ),
							'no'  => esc_html__( 'No', 'cbxuseronline' ),
						],
						'default'  => 'no',
						'desc_tip' => true,
					],
					'reset_data'           => [
						'name'     => 'reset_data',
						'label'    => esc_html__( 'Reset all data', 'cbxuseronline' ),
						'desc'     => $table_html . '<p>' . esc_html__( 'Reset option values and all tables created by this plugin', 'cbxuseronline' ) . '<a data-busy="0" class="button secondary ml-20" id="reset_data_trigger"  href="#">' . esc_html__( 'Reset Data', 'cbxuseronline' ) . '</a></p>',
						'type'     => 'html',
						'default'  => 'off',
						'desc_tip' => true,
					]
				]
			)
		];

		$settings_fields = apply_filters( 'cbxuseronline_settings_fields', $settings_fields );

		return $settings_fields;
	}//end method get_settings_fields


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function admin_pages() {
		global $submenu;

		$dashboard_page_hook = add_menu_page(
			esc_html__( 'CBX Useronline Settings', 'cbxuseronline' ),
			esc_html__( 'CBX Useronline', 'cbxuseronline' ),
			'manage_options', 'cbxuseronline',
			[ $this, 'menu_dashboard' ],
			CBX_USERONLINE_PLUGIN_ROOT_URL . 'assets/images/widget_icons/useronline-icon-white.png'
		);

		do_action( 'cbxuseronline_admin_pages_start' );

		//add settings for this plugin
		$setting_page_hook = add_submenu_page( 'cbxuseronline',
			esc_html__( 'Global Setting', 'cbxuseronline' ),
			esc_html__( 'Global Setting', 'cbxuseronline' ),
			'manage_options',
			'cbxuseronline-settings',
			[ $this, 'menu_settings' ] );

		$support_page_hook = add_submenu_page( 'cbxuseronline',
			esc_html__( 'Helps & Updates', 'cbxuseronline' ),
			esc_html__( 'Helps & Updates', 'cbxuseronline' ),
			'manage_options', 'cbxuseronline-support',
			[ $this, 'menu_support' ] );

		do_action( 'cbxuseronline_admin_pages_end' );

		/*$ref_sub_menu = add_submenu_page( 'cbxuseronline', esc_html__( 'CBX Useronline Helps & Updates', 'cbxuseronline' ),
			esc_html__( 'Helps & Updates', 'cbxuseronline' ),
			'manage_options', 'cbxuseronline_doc',
			[ $this, 'admin_sub_menu_page' ] );*/

		//rename the main sub menu created from the parent menu
		if ( isset( $submenu['cbxuseronline'][0][0] ) ) {
			$submenu['cbxuseronline'][0][0] = esc_html__( 'User Online', 'cbxuseronline' );
		}

	}//end method admin_pages

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function menu_dashboard() {
		$settings = $this->setting;

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo cbxuseronline_get_template_html( 'dashboard-useronline.php', [ 'ref' => $this, 'settings' => $settings ] );
	}//end menu_dashboard

	/**
	 * Display settings page
	 *
	 * @global type $wpdb
	 */
	public function menu_settings() {
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo cbxuseronline_get_template_html( 'dashboard-settings.php', [
			'admin_ref' => $this,
			'settings'  => $this->setting
		] );
	}//end menu_settings

	/**
	 * Render the help & support page for this plugin.
	 *
	 * @since    1.0.8
	 */
	public function menu_support() {
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo cbxuseronline_get_template_html( 'dashboard-doc.php', [
			'ref'      => $this,
			'settings' => $this->setting
		] );
	}//end method menu_support

	/**
	 * Sub menu for displaying documentaiton page
	 */
	public function admin_sub_menu_page() {
		$settings = $this->setting;

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo cbxuseronline_get_template_html( 'dashboard-doc.php',
			[ 'ref' => $this, 'settings' => $settings ]
		);
	}//end admin_sub_menu_page


	/**
	 * Delete all logs for login
	 */
	public function online_user_record_clean() {
		check_admin_referer( 'cbxuseronlinenonce', 'security' );

		$msg            = [];
		$msg['url']     = admin_url( 'admin.php?page=cbxuseronline' );
		$msg['message'] = esc_html__( 'User online record log purged successfully.', 'cbxuseronline' );
		$msg['success'] = 1;

		if ( ! current_user_can( 'manage_options' ) ) {
			$msg['message'] = esc_html__( 'Sorry, you don\'t have enough permission', 'cbxuseronline' );
			$msg['success'] = 0;
			wp_send_json( $msg );
		}


		do_action( 'cbxuseronline_record_cleaned_before' );

		global $wpdb;
		$cbxuseronline_tablename = CBXUseronlineHelper::get_tablename();

		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$real_purge = $wpdb->query( "DELETE FROM $cbxuseronline_tablename" );

		if ( $real_purge !== false ) {
			do_action( 'cbxuseronline_record_cleaned' );
			$msg['success'] = 1;
			$msg['message'] = esc_html__( 'User online record log purged successfully.', 'cbxuseronline' );


		} else {
			do_action( 'cbxuseronline_record_cleaned_failed' );
			$msg['message'] = esc_html__( 'User online record log purged failed', 'cbxuseronline' );
			$msg['success'] = 0;
		}

		wp_send_json( $msg );
	}//end method online_user_record_clean

	/**
	 * Add new col headers in user listing
	 *
	 * @param $column
	 *
	 * @return mixed
	 * @since 1.0.6
	 *
	 */
	public function users_columns_lastlogin( $column ) {

		$record_last_login = $this->setting->get_option( 'record_last_login', 'cbxuseronline_integration', 'on' );

		if ( $record_last_login == 'on' ) {
			$column['last_login'] = esc_html__( 'Last login', 'cbxuseronline' );
		}

		return $column;
	}//end method users_columns_lastlogin

	/**
	 * Add new sortable col headers in user listing
	 *
	 * @param $column
	 *
	 * @return mixed
	 * @since 1.0.6
	 *
	 */
	public function users_sortable_columns_lastlogin( $column ) {

		$record_last_login = $this->setting->get_option( 'record_last_login', 'cbxuseronline_integration', 'on' );

		if ( $record_last_login == 'on' ) {
			$column['last_login'] = 'last_login';
		}

		return $column;
	}//end method users_sortable_columns_lastlogin

	/**
	 * Add last login date/value
	 *
	 * @param $val
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return mixed|string
	 * @since 1.0.6
	 *
	 */
	public function users_custom_column_lastlogin( $val, $column_name, $user_id ) {
		$record_last_login = $this->setting->get_option( 'record_last_login', 'cbxuseronline_integration', 'on' );

		if ( $record_last_login == 'on' ) {
			switch ( $column_name ) {
				case 'last_login' :

					$last_login = get_user_meta( $user_id, '_cbxuseronline_lastlogin_time', true );


					$lastlogin_data = get_user_meta( $user_id, '_cbxuseronline_lastlogin_data', true );
					if ( ! is_array( $lastlogin_data ) ) {
						$last_login_data = [];
					}

					$login_count  = isset( $lastlogin_data['login_count'] ) ? intval( $lastlogin_data['login_count'] ) : 0;
					$login_mobile = isset( $lastlogin_data['login_mobile'] ) ? esc_attr( $lastlogin_data['login_mobile'] ) : 'desktop';
					$ip_address   = isset( $lastlogin_data['ip_address'] ) ? esc_attr( $lastlogin_data['ip_address'] ) : '';

					if ( $last_login != '' ) {
						$last_login = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_login );
						$last_login .= '(' . $ip_address . ')';
						$last_login .= ' - ' . $login_mobile;
					}

					return $last_login;
					break;
				default:
			}
		}

		return $val;
	}//end method users_custom_column_lastlogin

	/**
	 * Sort users in admin user listing by last login
	 *
	 *
	 * @param $WP_User_Query
	 *
	 * @since 1.0.6
	 *
	 */
	public function pre_get_users_lastlogin( $WP_User_Query ) {
		if ( isset( $WP_User_Query->query_vars["orderby"] )
		     && ( "last_login" === $WP_User_Query->query_vars["orderby"] )
		) {
			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$WP_User_Query->query_vars["meta_key"] = "_cbxuseronline_lastlogin_time";
			$WP_User_Query->query_vars["orderby"]  = "meta_value";
		}
	}//end method pre_get_users_lastlogin


	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			[
				'settings' => '<a style="color: #6648fe !important; font-weight: bold;" href="' . esc_url(admin_url( 'admin.php?page=cbxuseronline-settings' )) . '">' . esc_html__( 'Settings', 'cbxuseronline' ) . '</a>'
			],
			$links
		);
	}

	/**
	 * Add Pro product link in plugin listing
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function custom_plugin_row_meta( $links, $file ) {
		if ( strpos( $file, 'cbxuseronline.php' ) !== false ) {

			if (defined( 'CBX_USERONLINEPROADDON_PLUGIN_NAME' ) || in_array( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$new_links['pro'] = '<a target="_blank" style="color: #6648fe !important; font-weight: bold;" href="https://codeboxr.com/contact-us/" aria-label="' . esc_attr__( 'Pro Support', 'cbxuseronline' ) . '">' . esc_html__( 'Pro Support', 'cbxuseronline' ) . '</a>';
			} else {
				$new_links['pro'] = '<a style="color: #6648fe !important; font-weight: bold;" href="https://codeboxr.com/product/cbx-user-online-for-wordpress/" target="_blank">' . esc_html__( 'Buy Pro', 'cbxuseronline' ) . '</a>';
			}


			$new_links['doc'] = '<a style="color: #6648fe !important; font-weight: bold;" href="https://codeboxr.com/doc/cbxuseronline-doc/" target="_blank">' . esc_html__( 'Documentation', 'cbxuseronline' ) . '</a>';

			$links = array_merge( $links, $new_links );
		}

		return $links;
	}//end method custom_plugin_row_meta

	/**
	 * If we need to do something in upgrader process is completed for this plugin
	 *
	 * @param $upgrader_object
	 * @param $options
	 */
	/*public function plugin_upgrader_process_complete( $upgrader_object, $options ) {
		if ( isset( $options['plugins'] ) && $options['action'] == 'update' && $options['type'] == 'plugin' ) {
			if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) && sizeof( $options['plugins'] ) > 0 ) {
				foreach ( $options['plugins'] as $each_plugin ) {
					if ( $each_plugin == CBX_USERONLINE_PLUGIN_BASE_NAME ) {
						//create tables
						CBXUserOnlineHelper::create_tables();

						set_transient( 'cbxuseronline_upgraded_notice', 1 );
						break;
					}
				}
			}
		}
	}//end method plugin_upgrader_process_complete*/

	/**
	 * If we need to do something in upgrader process is completed
	 *
	 */
	public function plugin_upgrader_process_complete() {
		$saved_version = get_option( 'cbxuseronline_version' );


		if ( $saved_version === false || version_compare( $saved_version, CBX_USERONLINE_PLUGIN_VERSION, '<' ) ) {
			//create tables
			CBXUserOnlineHelper::create_tables();

			do_action('cbxuseronline_upgradation_extra');


			set_transient( 'cbxuseronline_upgraded_notice', 1 );

			// Update the saved version
			update_option( 'cbxuseronline_version', CBX_USERONLINE_PLUGIN_VERSION );
		}
	}//end plugin_upgrader_process_complete

	/**
	 * Show plugin update
	 *
	 * @param $plugin_file
	 * @param $plugin_data
	 *
	 * @return void
	 */
	public function custom_message_after_plugin_row_proaddon( $plugin_file, $plugin_data ) {
		if ( $plugin_file !== 'cbxuseronlineproaddon/cbxuseronlineproaddon.php' ) {
			return;
		}

		if ( defined( 'CBX_USERONLINEPROADDON_PLUGIN_NAME' ) ) {
			return;
		}

		$pro_addon_version = CBXUseronlineHelper::get_any_plugin_version( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php' );

		$pro_latest_version  = '1.1.7';

		if ( $pro_addon_version != '' && version_compare( $pro_addon_version, $pro_latest_version, '<' ) ) {
			// Custom message to display
			$plugin_manual_update = 'https://codeboxr.com/manual-update-pro-addon/';

			/* translators:translators: %s: plugin setting url for licence */
			$custom_message = wp_kses( sprintf( __( '<strong>Note:</strong> CBX Changelog Pro Addon is custom plugin. This plugin can not be auto update from dashboard/plugin manager. For manual update please check <a target="_blank" href="%1$s">documentation</a>. <strong style="color: red;">It seems this plugin\'s current version is older than %2$s . To get the latest pro addon features, this plugin needs to upgrade to %2$s or later.</strong>', 'cbxuseronline' ),
				esc_url( $plugin_manual_update ), $pro_latest_version ), [ 'strong' => [ 'style' => [] ], 'a' => [ 'href' => [], 'target' => [] ] ] );

			// Output a row with custom content
			echo '<tr class="plugin-update-tr">
            <td colspan="3" class="plugin-update colspanchange">
                <div class="notice notice-warning inline">
                    ' . wp_kses_post( $custom_message ) . '
                </div>
            </td>
          </tr>';
		}
	}//end method custom_message_after_plugin_row_proaddon

	/**
	 * Show a notice to anyone who has just installed the plugin for the first time
	 * This notice shouldn't display to anyone who has just updated this plugin
	 */
	public function plugin_activate_upgrade_notices() {
		$activation_notice_shown = false;

		// Check the transient to see if we've just activated the plugin
		if ( get_transient( 'cbxuseronline_activated_notice' ) ) {
			echo '<div style="border-left-color:#24bb64;" class="notice notice-success is-dismissible">';
			echo '<div class="notice-notes">';
			/* translators: %s: User online core plugin version */
			echo '<p class="notice-note"><img style="float: left; display: inline-block; margin-right: 15px; width: 30px;" src="' . esc_url(CBX_USERONLINE_PLUGIN_ROOT_URL) . 'assets/images/icon_brand_48.png' . '"/>' . sprintf( wp_kses(__( 'Thanks for installing/deactivating <strong>CBX User Online & Last Login</strong> V%s - Codeboxr Team', 'cbxuseronline' ), ['strong' => []]), esc_attr(CBX_USERONLINE_PLUGIN_VERSION) ) . '</p>'; //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			/* translators: 1: Plugin setting url page 2. Documentation url */
			echo '<p class="notice-note">' . sprintf( wp_kses(__( 'Check <a href="%1$s">Plugin Setting</a> | <a href="%2$s" target="_blank"><span class="dashicons dashicons-external"></span> Documentation</a>', 'cbxuseronline' ), ['a' => ['href' => [], 'target' => []], 'span' => ['class' => []]]), esc_url(admin_url( 'admin.php?page=cbxuseronline' )), 'https://codeboxr.com/doc/cbxuseronline-doc/' ) . '</p>';
			echo '</div>';
			echo '</div>';

			// Delete the transient so we don't keep displaying the activation message
			delete_transient( 'cbxuseronline_activated_notice' );

			$this->pro_addon_compatibility_campaign();
		}//end activation notice

		// Check the transient to see if we've just activated the plugin
		if ( get_transient( 'cbxuseronline_upgraded_notice' ) ) {
			if(!$activation_notice_shown){
				echo '<div style="border-left-color:#24bb64;" class="notice notice-success is-dismissible">';
				echo '<div class="notice-notes">';
				/* translators: %s: User online core plugin version */
				echo '<p class="notice-note"><img style="float: left; display: inline-block; margin-right: 15px; width: 30px;" src="' . esc_url(CBX_USERONLINE_PLUGIN_ROOT_URL) . 'assets/images/icon_brand_48.png' . '"/>' . sprintf( wp_kses(__( 'Thanks for upgrading <strong>CBX User Online & Last Login</strong> V%s , enjoy the new features and bug fixes - Codeboxr Team', 'cbxuseronline' ), ['strong' => []]), esc_attr(CBX_USERONLINE_PLUGIN_VERSION) ) . '</p>'; //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
				/* translators: 1: Plugin setting url page 2. Documentation url */
				echo '<p class="notice-note">' . sprintf( wp_kses(__( 'Check <a href="%1$s">Plugin Setting</a> | <a href="%2$s" target="_blank"><span class="dashicons dashicons-external"></span> Documentation</a>', 'cbxuseronline' ), ['a' => ['href' => [], ['target' => []]]]), esc_url(admin_url( 'admin.php?page=cbxuseronline' )), 'https://codeboxr.com/doc/cbxuseronline-doc/' ) . '</p>';

				echo '</div>';
				echo '</div>';

				$this->pro_addon_compatibility_campaign();
			}


			// Delete the transient so we don't keep displaying the activation message
			delete_transient( 'cbxuseronline_upgraded_notice' );
		}//end upgrade notice


		if(get_transient('cbxuseronline_deactivated_notice')){
			echo '<div class="notice notice-error is-dismissible" style="border-color: red !important;">';
			echo '<div class="notice-notes">';
			echo '<p class="notice-note">' . wp_kses(__( 'Currently installed <strong>CBX User Online & Last Login Pro addon</strong> version 1.0.14(or earlier) is not compatible with the latest version of core plugin CBX User Online & Last Login V1.2.10 or later. - Codeboxr Team','cbxuseronline' ), ['strong' => []]). '</p>';
			echo '</div>';
			echo '</div>';

			delete_transient('cbxuseronline_deactivated_notice');
		}
	}//end method plugin_activate_upgrade_notices

	/**
	 * Check plugin compatibility and pro addon install campaign
	 */
	public function pro_addon_compatibility_campaign() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		//if the pro addon is active or installed
		if (defined( 'CBX_USERONLINEPROADDON_PLUGIN_NAME' ) || in_array( 'cbxuseronlineproaddon/cbxuseronlineproaddon.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			//plugin is activated

			//$plugin_version = CBX_USERONLINEPROADDON_PLUGIN_VERSION;


			/*if(version_compare($plugin_version,'x.x.x', '<=') ){
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'CBX User Online & Last Login Pro Addon Vx.x.x or any previous version is not compatible with CBX User Online & Last Login Vx.x.x or later. Please update CBX User Online & Last Login Pro Addon to version x.x.0 or later  - Codeboxr Team', 'cbxuseronline' ) . '</p></div>';
			}*/

			/* translators: 1: Pro addon url 2. Pro addon version */
			echo '<div style="border-left-color:#24bb64;" class="notice notice-success is-dismissible">';
			echo '<div class="notice-notes">';
			/* translators: 1: External plugin/product details url */
			echo '<p class="notice-note">' . sprintf( wp_kses(__( 'CBX User Online & Last Login <a target="_blank" href="%1$s">Pro Addon V %2$s</a> installed and activated  - Codeboxr Team', 'cbxuseronline' ), ['a' => ['href' => [], 'target' => []]]), 'https://codeboxr.com/product/cbx-user-online-for-wordpress/', esc_attr(CBX_USERONLINEPROADDON_PLUGIN_VERSION) ) . '</p>';
			echo '</div>';
			echo '</div>';
		} else {
			/* translators: %s: Pro addon url */
			echo '<div style="border-left-color:#24bb64;" class="notice notice-success is-dismissible">';
			echo '<div class="notice-notes">';
			/* translators: 1: External plugin/product details url */
			echo '<p class="notice-note">' . sprintf( wp_kses(__( 'CBX User Online & Last Login Pro Addon has extended features and more controls, <a target="_blank" href="%s">try it</a>  - Codeboxr Team', 'cbxuseronline' ), ['a' => ['href' => [], 'target' => []]]), 'https://codeboxr.com/product/cbx-user-online-for-wordpress/' ) . '</p>';
			echo '</div>';
			echo '</div>';
		}

	}//end method pro_addon_compatibility_campaign

	/**
	 * Pro Addon update message
	 */
	public function plugin_update_message_pro_addons() {
		/* translators: 1: Pro addon install guide url 2. Codeboxr.com my account url */
		echo ' ' . sprintf( wp_kses(__( 'Check how to <a style="color:#9c27b0 !important; font-weight: bold;" href="%1$s"><strong>Update manually</strong></a> , download latest version from <a style="color:#9c27b0 !important; font-weight: bold;" href="%2$s"><strong>My Account</strong></a> section of Codeboxr.com', 'cbxuseronline' ), ['a' => ['href' => [], 'style' => []], 'strong' => []]), 'https://codeboxr.com/manual-update-pro-addon/', 'https://codeboxr.com/my-account/' );
	}//end plugin_update_message_pro_addons

	/**
	 * Load setting html
	 *
	 * @return void
	 */
	public function settings_reset_load() {
		//security check
		check_ajax_referer( 'settingsnonce', 'security' );

		$msg            = [];
		$msg['html']    = '';
		$msg['message'] = esc_html__( 'CBX Useronline plugin reset setting html loaded successfully', 'cbxuseronline' );
		$msg['success'] = 1;

		if ( ! current_user_can( 'manage_options' ) ) {
			$msg['message'] = esc_html__( 'Sorry, you don\'t have enough permission', 'cbxuseronline' );
			$msg['success'] = 0;
			wp_send_json( $msg );
		}

		$msg['html'] = CBXUserOnlineHelper::setting_reset_html_table();

		wp_send_json( $msg );
	}//end method settings_reset_load

	/**
	 * Reset plugin data
	 */
	public function plugin_reset() {
		//security check
		check_ajax_referer( 'settingsnonce', 'security' );

		$url = admin_url( 'admin.php?page=cbxuseronline-settings' );

		$msg            = [];
		$msg['message'] = esc_html__( 'CBX Useronline setting reset successfully', 'cbxuseronline' );
		$msg['success'] = 1;
		$msg['url']     = $url;

		if ( ! current_user_can( 'manage_options' ) ) {
			$msg['message'] = esc_html__( 'Sorry, you don\'t have enough permission', 'cbxuseronline' );
			$msg['success'] = 0;
			wp_send_json( $msg );
		}

		//before hook
		do_action( 'cbxuseronline_plugin_reset_before' );


		$plugin_resets = wp_unslash( $_POST );

		//delete options
		do_action( 'cbxuseronline_plugin_options_deleted_before' );

		$reset_options = isset( $plugin_resets['reset_options'] ) ? $plugin_resets['reset_options'] : [];
		$option_values = ( is_array( $reset_options ) && sizeof( $reset_options ) > 0 ) ? array_values( $reset_options ) : array_values( CBXUserOnlineHelper::getAllOptionNamesValues() );

		foreach ( $option_values as $key => $option ) {
			do_action( 'cbxuseronline_plugin_option_delete_before', $option );
			delete_option( $option );
			do_action( 'cbxuseronline_plugin_option_delete_after', $option );
		}

		do_action( 'cbxuseronline_plugin_options_deleted_after' );
		do_action( 'cbxuseronline_plugin_options_deleted' );
		//end delete options


		//delete tables
		$reset_tables = isset( $plugin_resets['reset_tables'] ) ? $plugin_resets['reset_tables'] : [];
		$table_names  = ( is_array( $reset_tables ) && sizeof( $reset_tables ) > 0 ) ? array_values( $reset_tables ) : array_values( CBXUserOnlineHelper::getAllDBTablesList() );


		if ( is_array( $table_names ) && count( $table_names ) ) {
			do_action( 'cbxuseronline_plugin_tables_delete_before', $table_names );

			global $wpdb;

			foreach ($table_names as $table_name){
				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query_result = $wpdb->query(  "DROP TABLE IF EXISTS {$table_name}");
			}

			do_action( 'cbxuseronline_plugin_tables_deleted_after', $table_names );
			do_action( 'cbxuseronline_plugin_tables_deleted' );
		}
		//end delete tables

		//after hook
		do_action( 'cbxuseronline_plugin_reset_after' );

		//general hook
		do_action( 'cbxuseronline_plugin_reset' );

		wp_send_json( $msg );
	}//end method plugin_reset

	/**
	 * Create the tables and pages after plugin reset
	 *
	 * @return void
	 */
	public function plugin_reset_extend(){
		CBXUserOnlineHelper::create_tables();
	}//end method plugin_reset_extend

}//end class CBXUserOnlineAdmin