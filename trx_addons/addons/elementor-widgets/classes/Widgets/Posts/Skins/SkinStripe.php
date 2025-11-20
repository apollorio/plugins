<?php
namespace TrxAddons\ElementorWidgets\Widgets\Posts\Skins;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Controls_Manager;

/**
 * Skin 'Stripe' for Posts widget
 */
class SkinStripe extends BaseSkin {

	/**
	 * Retrieve Skin ID.
	 *
	 * @return string Skin ID.
	 */
	public function get_id() {
		return 'stripe';
	}

	/**
	 * Retrieve Skin title.
	 *
	 * @return string Skin title.
	 */
	public function get_title() {
		return __( 'Stripe', 'trx_addons' );
	}

	/**
	 * Add skin controls to the parent widget.
	 *
	 * @return void
	 */
	protected function _register_controls_actions() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		parent::_register_controls_actions();
		// Change default values for some parameters
		add_action( 'elementor/element/before_section_end', array( $this, 'set_default_values' ), 10, 3 );
		add_action( 'elementor/element/trx_elm_posts/section_skin_field/before_section_end', array( $this, 'set_default_columns' ) );
		// Add controls to edit a image width
		add_action( 'elementor/element/trx_elm_posts/stripe_section_image_style/after_section_start', array( $this, 'add_style_image_controls' ) );
	}

	/**
	 * Change default values for some parameters for the skin 'Stripe':
	 * - show excerpt
	 * - show button
	 * - clear a margin bottom for meta info
	 * 
	 * @hooked to elementor/element/before_section_end
	 *
	 * @param object $element  Element object
	 * @param string $section_id  Section ID. Must be 'section_layout'
	 * @param array $args  Section arguments. Not used
	 */
	public function set_default_values( $element, $section_id, $args ) {
		if ( is_object( $element ) ) {
			$el_name = $element->get_name();
			if ( $this->parent->get_name() == $el_name ) {
				if ( 'stripe_section_post_excerpt' === $section_id ) {
					$element->update_control( 'stripe_show_excerpt', array(
						'default' => 'yes',
					) );
				} else if ( 'stripe_section_button' === $section_id ) {
					$element->update_control( 'stripe_show_button', array(
						'default' => 'yes',
					) );
				} else if ( 'stripe_section_layout_style' === $section_id ) {
					$element->update_control( 'stripe_parts_gap', array(
						'label' => __( 'Content Columns Gap', 'trx_addons' ),
					) );
				} else if ( 'stripe_section_meta_style' === $section_id ) {
					$element->update_control( 'stripe_meta_margin_bottom', array(
						'default' => array( 'size' => '' ),
					) );
				}
			}
		}
	}

	/**
	 * Change default columns for the skin 'Stripe'
	 * 
	 * @hooked to elementor/element/trx_elm_posts/section_skin_field/before_section_end
	 *
	 * @param object $element  Element object
	 */
	public function set_default_columns( $element ) {
		$element->update_control( 'stripe_columns', array(
			'default' => 1,
		) );
		$element->update_control( 'stripe_columns_tablet', array(
			'default' => 1,
		) );
	}

	/**
	 * Style Tab: Image
	 */
	public function add_style_image_controls() {

		$this->add_control(
			'image_vertical_align',
			[
				'label'                 => __( 'Vertical Alignment', 'trx_addons' ),
				'type'                  => Controls_Manager::CHOOSE,
				'default'               => 'stretch',
				'options'               => [
					'top'          => [
						'title'    => __( 'Top', 'trx_addons' ),
						'icon'     => 'eicon-v-align-top',
					],
					'middle'       => [
						'title'    => __( 'Center', 'trx_addons' ),
						'icon'     => 'eicon-v-align-middle',
					],
					'bottom'       => [
						'title'    => __( 'Bottom', 'trx_addons' ),
						'icon'     => 'eicon-v-align-bottom',
					],
					'stretch'      => [
						'title'    => __( 'Stretch', 'trx_addons' ),
						'icon'     => 'eicon-v-align-stretch',
					],
				],
				'selectors'             => [
					'{{WRAPPER}} .trx-addons-posts-item' => 'align-items: {{VALUE}};',
				],
				'selectors_dictionary'  => [
					'top'          => 'flex-start',
					'middle'       => 'center',
					'bottom'       => 'flex-end',
					'stretch'      => 'stretch',
				],
				'condition'             => [
					$this->get_control_id( 'show_thumbnail' ) => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label'     => __( 'Width (in %)', 'trx_addons' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'selectors' => array(
					'{{WRAPPER}} .trx-addons-posts' => '--trx-addons-posts-item-thumbnail-width: {{SIZE}}%;'
				),
				'condition' => array(
					$this->get_control_id( 'show_thumbnail' ) => 'yes',
				),
			)
		);
	}
}
