<?php
/**
 * Plugin support: Elementor ( Define class to create custom types of parameters )
 *
 * @package ThemeREX Addons
 * @since v2.35.1
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}

if ( ! class_exists('TRX_Addons_Elementor_Control_Type') ) {

	/**
	 * Define a base class (abstract) for the custom type of Elementor Controls.
	 * This class will be extended by the class for each new type.
	 */
	abstract class TRX_Addons_Elementor_Control_Type {

		protected $type = '';
		protected $enabled = true;

		public function __construct() {
			add_action( trx_addons_elementor_get_action_for_controls_registration(), [ $this, 'register_type' ] );
		}

		public function register_type( $controls_manager ) {
			if ( ! $this->enabled ) {
				return;
			}
			$control_filename = str_replace( '_', '-', $this->type );
			require_once TRX_ADDONS_PLUGIN_DIR . TRX_ADDONS_PLUGIN_API . "elementor/extensions/type-{$control_filename}/{$control_filename}.php";
			$class_name = 'Trx_Addons_Elementor_Control_' . ucwords( $this->type, '_' );
			if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
				$controls_manager->register( new $class_name() );	
			} else {
				$controls_manager->register_control( $control_id, new $class_name() );
			}
		}
	}
}
