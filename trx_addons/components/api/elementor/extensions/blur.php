<?php
/**
 * Elementor extension: Add effect "Backdrop blur" to the sections, columns and containers
 *
 * @package ThemeREX Addons
 * @since v2.35.5
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}

if ( ! function_exists( 'trx_addons_elm_add_params_backdrop_blur' ) ) {
	add_action( 'elementor/element/before_section_end', 'trx_addons_elm_add_params_backdrop_blur', 10, 3 );
	/**
	 * Add a group of parameters 'Backdrop Blur' to the Elementor's sections, columns and containers
	 * 
	 * @hooked elementor/element/before_section_end
	 * 
	 * @param object $element  Element object
	 * @param string $section_id  Section ID
	 * @param array $args  Section arguments
	 */
	function trx_addons_elm_add_params_backdrop_blur( $element, $section_id, $args = array() ) {

		if ( ! is_object( $element ) ) {
			return;
		}
		
		$el_name = $element->get_name();

		// Add 'Hide bg image on XXX' to the rows
		if (   ( $el_name == 'section' && in_array( $section_id, array( 'section_background', 'section_background_overlay' ) ) )
			|| ( $el_name == 'column' && in_array( $section_id, array( 'section_style', 'section_background_overlay' ) ) )
			|| ( $el_name == 'container' && in_array( $section_id, array( 'section_background', 'section_background_overlay' ) ) )
			|| ( in_array( $el_name, array( 'common', 'common-optimized' ) ) && $section_id == '_section_background' )
		) {
			$suffix = $section_id == 'section_background_overlay' ? '_overlay' : '';
			$element->add_control( 'trx_addons_row_effect_blur' . $suffix, array_merge(
				array(
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'label' => __("Backdrop Blur ", 'trx_addons'),
					'label_on' => __( 'On', 'trx_addons' ),
					'label_off' => __( 'Off', 'trx_addons' ),
					'separator' => 'before',
					'return_value' => 'on',
					'prefix_class' => 'elementor-element-effect-blur-',
					'selectors' => array(
						( $section_id == '_section_background'
							? '{{WRAPPER}}.elementor-widget > .elementor-widget-container'
							: ( $section_id == 'section_background_overlay'
								? '{{WRAPPER}}:not(.elementor-widget):before'
								: '{{WRAPPER}}:not(.elementor-widget)'
								)
							) => 'backdrop-filter: blur( {{trx_addons_row_effect_blur_value.SIZE}}px );',
					),
				),
				$section_id == 'section_background_overlay'
					? array(
						'condition' => array(
							'overlay_blend_mode' => '',
							'background_overlay_opacity[size]' =>  array( '', 1 ),
						),
					)
					: array()
			) );

			$element->add_responsive_control( 'trx_addons_row_effect_blur_value' . $suffix, array(
									'label' => __( 'Blur value', 'trx_addons' ),
									'description' => __( 'Set the blur value for the backdrop filter. Attention! The effect will work if the current element (container) has a background color with transparency specified.', 'trx_addons' ),
									'type' => \Elementor\Controls_Manager::SLIDER,
									'default' => array(
										'size' => '10',
										'unit' => 'px'
									),
									'size_units' => array( 'px' ),
									'condition' => array_merge(
										array(
											'trx_addons_row_effect_blur' . $suffix => array( 'on' ),
										),
										$section_id == 'section_background_overlay'
											? array(
												'overlay_blend_mode' => '',
												'background_overlay_opacity[size]' =>  array( '', 1 ),
											)
											: array()
									),
								) );
		}
	}
}
