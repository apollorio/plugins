<?php
namespace TrxAddons\ElementorWidgets\Widgets\Posts\Skins;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Controls_Manager;

/**
 * Skin 'List' for Posts widget
 */
class SkinList extends BaseSkin {

	/**
	 * Retrieve Skin ID.
	 *
	 * @return string Skin ID.
	 */
	public function get_id() {
		return 'list';
	}

	/**
	 * Retrieve Skin title.
	 *
	 * @return string Skin title.
	 */
	public function get_title() {
		return __( 'List', 'trx_addons' );
	}

	/**
	 * Add skin controls to the parent widget.
	 *
	 * @return void
	 */
	protected function _register_controls_actions() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		parent::_register_controls_actions();
		// Add controls to edit a image width
		add_action( 'elementor/element/trx_elm_posts/list_section_image_style/after_section_start', array( $this, 'add_style_image_controls' ) );
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
				'default'               => 'middle',
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
				],
				'selectors'             => [
					'{{WRAPPER}} .trx-addons-posts-item' => 'align-items: {{VALUE}};',
				],
				'selectors_dictionary'  => [
					'top'          => 'flex-start',
					'middle'       => 'center',
					'bottom'       => 'flex-end',
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
