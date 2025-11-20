<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ThemeREX Addons Preset control.
 *
 * A base control for creating a preset selector.
 * Displays a field with a popup to select a preset with a style settings for the current widget.
 *
 * Creating new control in the editor (inside `Widget_Base::register_controls()` method):
 *
 *    $this->add_control(
 *    	'presets',
 *    	[
 *    		'label' => __( 'Preset', 'plugin-domain' ),
 *    		'type' => 'trx_preset',
 *   		'default' => '',
 *          'options' => [ 'preset 1 title' => [ preset 1 settings ], ... ],
 *    	]
 *    );
 *
 * @since 2.35.1
 *
 * @param string $label       Optional. The label that appears above of the
 *                            field. Default is empty.
 * @param string $description Optional. The description that appears below the
 *                            field. Default is empty.
 * @param string $default     Optional. Default preset name. Default is empty.
 * @param array  $options     Optional. A list of predefined presets - will be
 *                            added to the list of user presets. Default is empty.
 * @param string $separator   Optional. Set the position of the control separator.
 *                            Available values are 'default', 'before', 'after'
 *                            and 'none'. 'default' will position the separator
 *                            depending on the control type. 'before' / 'after'
 *                            will position the separator before/after the
 *                            control. 'none' will hide the separator. Default
 *                            is 'default'.
 * @param bool   $show_label  Optional. Whether to display the label. Default is
 *                            true.
 * @param bool   $label_block Optional. Whether to display the label in a
 *                            separate line. Default is false.
 */
class Trx_Addons_Elementor_Control_Trx_Preset extends \Elementor\Control_Select {

	/**
	 * Retrieve preset control type.
	 *
	 * @since 2.35.1
	 * @access public
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return 'trx_preset';
	}

	/**
	 * Retrieve control's default settings.
	 *
	 * Get the default settings of the control, used while initializing the control.
	 *
	 * @since 2.35.1
	 * @access protected
	 *
	 * @return array Control default settings.
	 */
	protected function get_default_settings() {
		return [
			'label_block' => true,
		];
	}

	
	/**
	 * Enqueue control required scripts and styles.
	 *
	 * Used to register and enqueue custom scripts and styles used by this control.
	 *
	 * @since 2.35.1
	 * @access public
	 */
	public function enqueue() {
		wp_enqueue_script( 'trx_addons-elementor-control-trx-preset', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_API . 'elementor/extensions/type-trx-preset/trx-preset-control.js' ), array( 'jquery' ), null, true );
		wp_localize_script( 'trx_addons-elementor-control-trx-preset', 'trx_addons_elementor_control_preset_vars', array(
			'ajax_url'	 => esc_url( admin_url( 'admin-ajax.php' ) ),
			'ajax_nonce' => esc_attr( wp_create_nonce( admin_url( 'admin-ajax.php' ) ) ),
			'presets'    => TrxAddonsElementorParameterTypePreset::get_presets(),
			'select_preset_title' => esc_html__( '- Select Preset -', 'trx_addons' ),
			'excluded_options' => self::get_excluded_options()
		) );
	}

	/**
	 * Return an options list, which will be excluded from the presets.
	 * Most of them are repeater fields
	 *
	 * @since 2.35.1
	 * @access private
	 */
	private static function get_excluded_options() {
		$options = array( 
			'trx_addons_preset',		// Preset control itself
			'__globals__',				// Global settings
			'elType',				    // Element type
			'widgetType',				// Widget type
			'isInner',					// Is inner element
			'_element_id',				// Element ID
		);
		return apply_filters( 'trx_addons_filter_presets_excluded_options', $options );
	}

	/**
	 * Render a preset control output in the editor.
	 *
	 * Used to generate the control HTML in the editor using Underscore JS
	 * template. The variables for the class are available using `data` JS
	 * object.
	 *
	 * @since 2.35.1
	 * @access public
	 *
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field {{{ data.reset ? 'trx-addons-control-with-reset' : '' }}} {{{ ( data.select_class ) ? data.select_class : '' }}}"><#
			if ( data.label ) {
				#>
				<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
				<#
			}
			#><div class="elementor-control-input-wrapper">
				<select id="<?php echo esc_attr( $control_uid ); ?>" data-setting="{{ data.name }}"></select>
				<button type="button" class="elementor-button elementor-button-default trx-addons-button-save-preset" title="<?php esc_attr_e( 'Save Styles as Preset' ); ?>"><i class="eicon-plus-circle" aria-hidden="true"></i></button>
				<button type="button" class="elementor-button elementor-button-default trx-addons-button-update-preset" title="<?php esc_attr_e( 'Update/Rename Current Preset' ); ?>"><i class="eicon-edit" aria-hidden="true"></i></button>
				<button type="button" class="elementor-button elementor-button-default trx-addons-button-delete-preset" title="<?php esc_attr_e( 'Delete Current Preset' ); ?>"><i class="eicon-library-delete" aria-hidden="true"></i></button>
				<button type="button" class="elementor-button elementor-button-default trx-addons-button-reset-style" title="<?php esc_attr_e( 'Reset Widget Style to Default' ); ?>"><i class="eicon-undo" aria-hidden="true"></i></button>
			</div><#

			// Save Preset Dialog
			#><div class="elementor-controls-popover trx-addons-panel-popup trx-addons-panel-popup--save-preset">
				<div class="e-group-control-header">
					<span><?php esc_html_e( 'Create Preset', 'trx_addons' ); ?></span>
				</div>
				<div class="elementor-control">
					<input type="text" class="trx-addons-create-preset-name" placeholder="<?php esc_html_e( 'Enter your preset name', 'trx_addons' ); ?>" maxlength="100">
				</div>
				<div class="elementor-control">
					<label class="trx-addons-panel-checkbox">
						<input type="checkbox" class="trx-addons-create-preset-default" >
						<span><?php esc_html_e( 'Use as Default Preset', 'trx_addons' ); ?></span>
					</label>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-panel-buttons">
						<button class="elementor-button elementor-button-success trx-addons-save-preset-button-ok">
							<span class="trx-addons-button-state-icon">
								<i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
							</span>
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'Save', 'trx_addons' ); ?>
							</span>
						</button>
						<button class="elementor-button elementor-button-cancel trx-addons-save-preset-button-cancel">
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'Cancel', 'trx_addons' ); ?>
							</span>
						</button>
					</div>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-preset-message"></div>
				</div>
			</div><#

			// Update Preset Dialog
			#><div class="elementor-controls-popover trx-addons-panel-popup trx-addons-panel-popup--update-preset">
				<div class="e-group-control-header">
					<span><?php esc_html_e( 'Update Preset', 'trx_addons' ); ?></span>
				</div>
				<div class="elementor-control">
					<input type="text" class="trx-addons-update-preset-name" maxlength="100">
				</div>
				<div class="elementor-control">
					<label class="trx-addons-panel-checkbox">
						<input type="checkbox" class="trx-addons-update-preset-confirm" >
						<span><?php esc_html_e( 'Update widget settings', 'trx_addons' ); ?></span>
					</label>
				</div>
				<div class="elementor-control">
					<label class="trx-addons-panel-checkbox">
						<input type="checkbox" class="trx-addons-update-preset-default" >
						<span><?php esc_html_e( 'Use as Default Preset', 'trx_addons' ); ?></span>
					</label>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-panel-buttons">
						<button class="elementor-button elementor-button-success trx-addons-update-preset-button-ok">
							<span class="trx-addons-button-state-icon">
								<i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
							</span>
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'Save', 'trx_addons' ); ?>
							</span>
						</button>
						<button class="elementor-button elementor-button-cancel trx-addons-update-preset-button-cancel">
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'Cancel', 'trx_addons' ); ?>
							</span>
						</button>
					</div>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-preset-message"></div>
				</div>
			</div>
			<#

			// Delete Preset Dialog
			#><div class="elementor-controls-popover trx-addons-panel-popup trx-addons-panel-popup--delete-preset">
				<div class="e-group-control-header">
					<span><?php esc_html_e( 'Delete Preset', 'trx_addons' ); ?></span>
				</div>
				<div class="elementor-control">
					<p><?php esc_html_e( 'Do you want to delete preset?', 'trx_addons' ); ?></p>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-panel-buttons">
						<button class="elementor-button trx-addons-elementor-button-error trx-addons-delete-preset-button-ok">
							<span class="trx-addons-button-state-icon">
								<i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
							</span>
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'Yes, delete', 'trx_addons' ); ?>
							</span>
						</button>
						<button class="elementor-button elementor-button-cancel trx-addons-delete-preset-button-cancel">
							<span class="trx-addons-button-state-icon">
								<i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
							</span>
							<span class="trx-addons-button-label">
								<?php esc_html_e( 'No', 'trx_addons' ); ?>
							</span>
						</button>
					</div>
				</div>
				<div class="elementor-control">
					<div class="trx-addons-preset-message"></div>
				</div>
			</div>
		</div>
		<#
		if ( data.description ) {
			#>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
			<#
		}
		#>
		<?php
	}
}
