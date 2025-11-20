<?php
defined( 'ABSPATH' ) || exit();

/**
 * Adds a new parameter type for Elementor widgets
 */
class TrxAddonsElementorParameterTypePreset extends TRX_Addons_Elementor_Control_Type {
	
	protected $type = 'trx_preset';

	const PRESETS_OPTION = 'trx_addons_elementor_widgets_presets';

	public function __construct() {
		parent::__construct();
		
		add_filter( 'trx_addons_cpt_list_options', [ $this, 'cpt_list_options' ], 10, 2 );

		add_action( 'init', [ $this, 'init_hooks' ] );
	}

	/**
	 * Initialize hooks for the presets functionality
	 * 
	 * @hooked init
	 */
	public function init_hooks() {

		if ( (int)trx_addons_get_option( 'allow_presets_for_elementor_editor' ) == 1 ) {

			add_action( 'elementor/element/after_section_end', [ $this, 'add_control_to_widgets'], 10, 3 );
	
			if ( is_admin() ) {
				add_action( 'wp_ajax_trx_addons_action_create_preset', [ $this, 'create_preset' ] );
				add_action( 'wp_ajax_trx_addons_action_update_preset', [ $this, 'create_preset' ] );
				add_action( 'wp_ajax_trx_addons_action_delete_preset', [ $this, 'delete_preset' ] );

				add_action( 'trx_addons_action_importer_export', [ $this, 'importer_export'], 10, 1 );
				add_action( 'trx_addons_action_importer_export_fields',	[ $this, 'importer_export_fields'], 11, 1 );

				add_action( 'init', [ $this, 'create_presets_on_first_load' ], 100 );

				add_action( 'wp_ajax_trx_addons_elementor_presets_create', [ $this, 'callback_ajax_trx_addons_elementor_presets_create'] );
			}
		} else {

			// Disable registration of the presets control type
			$this->enabled = false;

		}
	}

	/**
	 * Add a section 'Presets' to each Elementor widget
	 * 
	 * @param $element  Elementor element object
	 * @param $section_id  Section ID to add presets to
	 * @param $args  Additional arguments
	 */
	public function add_control_to_widgets( $element, $section_id, $args ) {
		// static $processed_widgets = array();

		// $el_name = $element->get_name();
		// $el_type = $element->get_type();

		// if ( ! in_array( $el_type, array( 'widget', 'container', 'section', 'column' ) )
		// 	|| empty( $el_name )
		// 	|| $el_name == 'common'
		// 	|| isset( $processed_widgets[ $el_name ] )
		// 	|| empty( $args['tab'] )
		// 	|| ( $el_type == 'widget' && $args['tab'] == \Elementor\Controls_Manager::TAB_CONTENT )
		// 	|| ( $el_type != 'widget' && $args['tab'] == \Elementor\Controls_Manager::TAB_LAYOUT )
		// 	|| ! apply_filters( 'trx_addons_filter_add_elementor_presets', true, $el_name )
		// ) {
		// 	return;
		// }

		// $processed_widgets[ $el_name ] = true;

		if ( ! in_array( $element->get_name(), array( 'widget', 'container', 'section', 'column', 'common' ) ) || $section_id != 'section_custom_css_pro' ) {
			return;
		}

		$element->start_controls_section(
			'trx_addons_preset_section',
			array(
				'label' => esc_html__( 'Presets', 'trx_addons' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'trx_addons_preset',
			array(
				'label'       => esc_html__( 'Preset', 'trx_addons' ),
				'description' => esc_html__( 'Select a preset to apply it to the element (widget, container, section, etc.).', 'trx_addons' )
								. ' ' . esc_html__( 'The default preset (bolded in the list) will be applied automatically as soon as the element is inserted into the page.', 'trx_addons' ),
				'type'        => 'trx_preset',
				'options'     => [], // will be filled in js
				'default'     => '',
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Create/Update preset ajax handler
	 */
	public function create_preset() {

		trx_addons_verify_nonce();

		$answer = array(
			'error' => '',
			'success' => '',
			'id' => '',
			'preset' => ''
		);

		$preset_id   = trx_addons_get_value_gp( 'preset_id' );
		$preset_name = trx_addons_get_value_gp( 'preset_name' );
		$widget_type = trx_addons_get_value_gp( 'widget_type' );
		$options     = trx_addons_get_value_gp( 'options' );
		$default     = (int)trx_addons_get_value_gp( 'default' );

		if ( empty( $preset_name ) ) {
			$answer['error'] = esc_html__( 'Preset name is incorrect', 'trx_addons' );
		} else if ( empty( $widget_type ) ) {
			$answer['error'] = esc_html__( 'Widget type is empty', 'trx_addons' );
		} else {
			if ( ! empty( $options ) ) {
				$options = json_decode( $options );
			}

			$all_presets = self::get_presets();

			// Check name of the new preset
			foreach ( $all_presets as $p_id => $preset ) {
				if ( ( empty( $preset_id ) || $preset_id != $p_id ) && ! empty( $preset['title'] ) && $preset['title'] == $preset_name && $preset['widget_type'] == $widget_type ) {
					$answer['error'] = esc_html__( 'A preset with this name already exists', 'trx_addons' );
					break;
				}
			}
		}

		if ( empty( $answer['error'] ) ) {
			if ( empty( $preset_id ) ) {
				$preset_id = trx_addons_get_unique_id( $widget_type . '_' );
			}
			// If this preset set as default, remove default flag from all other presets
			if ( $default ) {
				foreach ( $all_presets as $p_id => $preset ) {
					if ( ! empty( $preset['default'] ) && $p_id != $preset_id && $preset['widget_type'] == $widget_type ) {
						unset( $all_presets[ $p_id ]['default'] );
					}
				}
			}
			if ( empty( $all_presets[ $preset_id ] ) ) {
				$all_presets[ $preset_id ] = self::apply_preset_defaults( [
					'title'        => $preset_name,
					'widget_type'  => $widget_type,
					'default'      => $default,
					'options'      => $options,
				] );
				$answer['success'] = esc_html__( 'Preset created', 'trx_addons' );
			} else {
				if ( ! empty( $options ) ) {
					$all_presets[ $preset_id ]['options'] = $options;
					$all_presets[ $preset_id ]['default'] = $default;
					$answer['success'] = esc_html__( 'Preset updated', 'trx_addons' );
				}
				if ( ! empty( $default ) ) {
					$all_presets[ $preset_id ]['default'] = $default;
					$answer['success'] .= ! empty( $answer['success'] ) ? ' ' . esc_html__( 'and set as default', 'trx_addons' ) : esc_html__( 'Preset set as default', 'trx_addons' );
				}
				if ( ! empty( $preset_name ) ) {
					if ( $all_presets[ $preset_id ]['title'] != $preset_name ) {
						$all_presets[ $preset_id ]['title'] = $preset_name;
						$answer['success'] .= ! empty( $answer['success'] ) ? ' ' . esc_html__( 'and renamed', 'trx_addons' ) : esc_html__( 'Preset renamed', 'trx_addons' );
					}
				}
			}
			self::save_presets( $all_presets );
			$answer['id'] = $preset_id;
			$answer['preset'] = $all_presets[ $preset_id ];
		}

		// Return response to the AJAX handler
		trx_addons_ajax_response( $answer );
	}

	/**
	 * Delete preset ajax handler
	 */
	public function delete_preset() {

		trx_addons_verify_nonce();

		$answer = array(
			'error' => '',
			'success' => '',
			'data' => array()
		);

		$preset_id = trx_addons_get_value_gp( 'preset_id' );
		if ( empty( $preset_id ) ) {
			$answer['error'] = esc_html__( 'Preset ID is empty', 'trx_addons' );
		} else {
			$all_presets = self::get_presets();
			if ( ! empty( $all_presets[ $preset_id ] ) ) {
				unset( $all_presets[ $preset_id ] );
			}
			self::save_presets( $all_presets );
			$answer['success'] = esc_html__( 'Preset was removed', 'trx_addons' );
		}

		trx_addons_ajax_response( $answer );
	}


	/**
	 * Return an option name with presets for the current theme
	 * 
	 * @return string  Option name with presets
	 */
	public static function get_presets_option_name() {
		$theme_slug = str_replace( '-', '_', get_template() );
		return self::PRESETS_OPTION . "_{$theme_slug}";
	}

	/**
	 * Get all saved presets. 
	 * 
	 * @param bool $decode  Whether to decode the options from JSON format or return them as string.
	 * 
	 * @return array|string  Array or string with presets
	 */
	public static function get_presets( $decode = true ) {
		$presets = get_option( self::get_presets_option_name(), '' );
		if ( ! is_array( $presets ) && $decode ) {
			if ( ! empty( $presets ) ) {
				$presets = json_decode( $presets, true );
			} else {
				$presets = array();
			}
		}
		return $presets;
	}

	/**
	 * Save presets sorted by title
	 * 
	 * @param $presets  Array of presets to save
	 */
	public static function save_presets( $presets ) {
		uasort( $presets, function( $a, $b ) {
			return strnatcasecmp ( $a['title'], $b['title'] );
		} );

		// Encode options to JSON format
		$presets = wp_json_encode( $presets );

		update_option( self::get_presets_option_name(), $presets );
	}


	/**
	 * Apply and validate default widget settings
	 * 
	 * @param $preset  New preset data
	 * 
	 * @return array  Preset data with defaults applied
	 */
	private static function apply_preset_defaults( $preset ) {
		return array_merge( array(
			'title'          => '',
			'widget_type'    => '',
			'default'        => 0,
			'options'        => array(),
			'plugin_version' => TRX_ADDONS_VERSION,
			'date_created'   => time(),
		), $preset );
	}


	// One-click import support
	//------------------------------------------------------------------------

	/**
	 * Export widget presets to the file while the action 'trx_addons_action_importer_export' is called
	 * 
	 * @hooked trx_addons_action_importer_export
	 * 
	 * @param string $importer  Importer/Exporter object
	 */
	public function importer_export( $importer ) {
		$presets = self::get_presets();
		if ( empty( $presets ) || ! is_array( $presets ) || count( $presets ) == 0 ) {
			return;
		}
		$output = wp_json_encode( $presets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		trx_addons_fpc( $importer->export_file_dir( 'presets.txt' ), $output );
	}

	/**
	 * Display exported file with components settings in the files list on the Importer/Exporter's tab
	 * 
	 * @hooked trx_addons_action_importer_export_fields
	 * 
	 * @param string $importer  Importer/Exporter object
	 */
	public function importer_export_fields( $importer ) {
		$importer->show_exporter_fields( array(
			'slug'	=> 'presets',
			'title' => esc_html__( 'Elementor Presets', 'trx_addons' ),
			'download' => 'trx_addons-presets.json'
		) );
	}


	// Create presets on Plugin Options button click
	//------------------------------------------------------------------------

	/**
	 * Create a theme specific presets on first load
	 * 
	 * @hooked init
	 */
	public function create_presets_on_first_load() {
		$presets = get_option( self::get_presets_option_name(), '' );
		if ( empty( $presets ) ) {
			$this->create_presets();
		}
	}

	/**
	 * Add a button "Create Presets" to the "Theme-specific -> Theme Elements" plugin options 
	 * 
	 * @hooked trx_addons_cpt_list_options, 10, 2
	 * 
	 * @param array $add_parameters  Additional parameters to add to the options
	 * @param string $type  Type of the options (e.g. 'layouts')
	 * 
	 * @return array  Modified options with the "Create Presets" button added
	 */
	public function cpt_list_options( $add_parameters = array(), $type = '' ) {
		if ( $type == 'layouts' ) {
			$add_parameters = array_merge( $add_parameters, array(
				'presets_create' => array(
					"title" => esc_html__('Create Presets', 'trx_addons'),
					"desc" => wp_kses_data( __('Press the button above to add/restore a set of presets for Elementor widgets. These presets have been automatically added after theme/skin activation. Use this button to restore deleted presets. If a preset with the same name already exists, it will be skipped.', 'trx_addons') ),
					"std" => 'trx_addons_elementor_presets_create',
					"type" => "button",
					"dependency" => array(
						"allow_presets_for_elementor_editor" => array(1),
					),
				)
			) );
		}
		return $add_parameters;
	}

	/**
	 * Callback for the 'Create Presets' button
	 * 
	 * @hooked wp_ajax_trx_addons_elementor_presets_create
	 */ 
	function callback_ajax_trx_addons_elementor_presets_create() {
		trx_addons_verify_nonce();

		$response = array(
			'error' => '',
			'success' => esc_html__( 'Elementor Presets are created successfully!', 'trx_addons' )
		);
		
		$this->create_presets();
		
		trx_addons_ajax_response( $response );
	}

	/**
	 * Create a theme-specific Elementor Presets
	 */	
	function create_presets() {
		$new_presets = apply_filters( 'trx_addons_filter_default_elementor_widgets_presets', array() );
		if ( count( $new_presets ) > 0 ) {
			$presets = self::get_presets();
			foreach ( $new_presets as $preset_id => $preset ) {
				if ( ! isset( $presets[ $preset_id ] ) ) {
					$presets[ $preset_id ] = self::apply_preset_defaults( $preset );
				}
			}
			self::save_presets( $presets );
		}
	}

}

new TrxAddonsElementorParameterTypePreset();