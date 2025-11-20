<?php
/**
 * Marquee Widget
 *
 * @package ThemeREX Addons
 * @since v2.35.3
 */

namespace TrxAddons\ElementorWidgets\Widgets\Marquee;

use TrxAddons\ElementorWidgets\BaseWidget;
use TrxAddons\ElementorWidgets\Utils as TrxAddonsUtils;

// Elementor Classes.
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Marquee Widget
 */
class MarqueeWidget extends BaseWidget {

	/**
	 * Register widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 */
	protected function register_controls() {
		/* Content Tab */
		$this->register_content_controls_general();
		$this->register_content_controls_marquee();

		/* Style Tab */
		$this->register_style_controls_general();
		$this->register_style_controls_highlight();
	}

	/**
	 * Register General Controls in Content tab
	 *
	 * @return void
	 */
	protected function register_content_controls_general() {

		$is_edit_mode = trx_addons_elm_is_edit_mode();

		/**
		 * Content Tab: Marquee Content
		 * -------------------------------------------------
		 */
		$this->start_controls_section(
			'section_marquee_content',
			array(
				'label' => __( 'Content', 'trx_addons' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'type',
			[
				'label' => esc_html__( 'Type', 'trx_addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text' => esc_html__( 'Text', 'trx_addons' ),
					'icon'  => esc_html__( 'Icon', 'trx_addons' ),
					'image' => esc_html__( 'Image', 'trx_addons' ),
					'gallery' => esc_html__( 'Gallery', 'trx_addons' ),
					'video' => esc_html__( 'Video', 'trx_addons' ),
				],
				'render_type' => 'template',
			]
		);

		$repeater->add_control(
			'text',
			[
				'label' => __( 'Text', 'trx_addons' ),
				'label_block' => true,
				'type' => Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Enter your text here', 'trx_addons' ),
				'default' => __( 'Enter your text here', 'trx_addons' ),
				'condition' => [
					'type' => 'text'
				],
			]
		);

		$repeater->add_control(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'trx_addons' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-circle',
					'library' => 'fa-solid',
				],
				'recommended' => [
					'fa-solid' => [
						'circle',
						'dot-circle',
						'square-full',
					],
					'fa-regular' => [
						'circle',
						'dot-circle',
						'square-full',
					],
				],
				'condition' => [
					'type' => 'icon'
				],
			]
		);

		$repeater->add_control(
			'image',
			[
				'label' => esc_html__( 'Choose Image', 'trx_addons' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'condition' => [
					'type' => 'image'
				],
			]
		);

		$repeater->add_control(
			'gallery',
			[
				'label' => esc_html__( 'Choose Images', 'trx_addons' ),
				'type' => Controls_Manager::GALLERY,
				'default' => [],
				'condition' => [
					'type' => 'gallery'
				],
			]
		);
		
		$repeater->add_control(
			'video',
			[
				'label' => __( 'Video', 'trx_addons' ),
				'type' => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
					'categories' => [
						TagsModule::MEDIA_CATEGORY,
					],
				],
				'media_types' => [
					'video',
				],
				'default' => [],
				'condition' => [
					'type' => 'video'
				],
			]
		);

		$repeater->add_control(
			'thumbnail_size',
			[
				'label' => __( 'Thumb size', 'trx_addons' ),
				'label_block' => false,
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => ! $is_edit_mode ? array() : trx_addons_get_list_thumbnail_sizes(),
				'default' => apply_filters( 'trx_addons_filter_thumb_size', trx_addons_get_thumb_size( 'tiny' ), 'marquee' ),
				'condition' => [
					'type' => ['image', 'gallery'],
				],
			]
		);

		$repeater->add_responsive_control(
			'height',
			[
				'label' => __('Height', 'trx_addons'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => '',
					'unit' => 'px',
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'rem' => [
						'min' => 0,
						'max' => 50,
						'step' => 0.1,
					],
					'em' => [
						'min' => 0,
						'max' => 50,
						'step' => 0.1,
					],
				],
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw', 'custom'],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon svg,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'height: {{SIZE}}{{UNIT}}; max-height:none;',
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'type' => ['icon', 'image', 'gallery', 'video'],
				],
			]
		);

		$repeater->add_responsive_control(
			'size',
			[
				'label' => __('or scale (%)', 'trx_addons'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 100,
					'unit' => '%',
				],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 1000,
					],
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => '--trx-addons-base-size: calc( {{SIZE}} / 100 );',
				],
				'condition' => [
					'type' => ['icon', 'image', 'gallery', 'video'],
					'height[size]' => '',
				],
			]
		);

		$repeater->add_responsive_control(
			'vertical_offset',
			[
				'label' => __('Vertical Offset', 'trx_addons'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => '',
					'unit' => 'px',
				],
				'range' => [
					'px' => [
						'min' => -100,
						'max' => 100,
						'step' => 1,
					],
					'rem' => [
						'min' => -10,
						'max' => 10,
						'step' => 0.1,
					],
					'em' => [
						'min' => -10,
						'max' => 10,
						'step' => 0.1,
					],
					'vw' => [
						'min' => -20,
						'max' => 20,
						'step' => 0.1,
					],
					'vh' => [
						'min' => -20,
						'max' => 20,
						'step' => 0.1,
					],
					// Unit '%' is not supported, because of unpredictable results with a negative margin
					// '%' => [
					// 	'min' => -100,
					// 	'max' => 100,
					// 	'step' => 0.1,
					// ],
				],
				'size_units' => ['px', 'em', 'rem', 'vh', 'vw', 'custom'],	//, '%'
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$repeater->add_control(
			'gallery_delay',
			[
				'label' => __('Gallery Delay (ms)', 'trx_addons'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 10000,
						'step' => 100,
					],
				],
				'condition' => [
					'type' => ['gallery'],
				],
			]
		);

		$repeater->add_control(
			'gallery_interval',
			[
				'label' => __('Gallery Interval (ms)', 'trx_addons'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 4000,
				],
				'range' => [
					'px' => [
						'min' => 100,
						'max' => 10000,
						'step' => 100,
					],
				],
				'condition' => [
					'type' => ['gallery'],
				],
			]
		);

		$repeater->add_control( 'marquee_rotation', array(
			'label' => __( 'Marquee Rotation', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'label_block' => false,
			'label_off' => __( 'Off', 'trx_addons' ),
			'label_on' => __( 'On', 'trx_addons' ),
			'default' => '',
		) );

		// $repeater->add_control(
		// 	'item_link',
		// 	[
		// 		'label' => __( 'Link', 'trx_addons' ),
		// 		'label_block' => true,
		// 		'type' => Controls_Manager::URL,
		// 		'dynamic'     => [
		// 			'active' => true,
		// 		],
		// 		'placeholder' => __('https://your-link.com', 'trx_addons'),
		// 	]
		// );

		$repeater->add_control(
			'decoration',
			[
				'label' => __( 'Custom Styling', 'trx_addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'item_typography',
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text',
				'condition' => [
					'type' => ['text']
				],
			]
		);

		$repeater->add_control(
			'color',
			[
				'label' => esc_html__( 'Color', 'trx_addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon svg' => 'color: {{VALUE}}; fill: {{VALUE}}', //icon as a font + svg

				],
				'condition' => [
					'type' => ['icon','text']
				],
			]
		);

		$repeater->add_control(
			'background_as_text',
			[
				'label' => esc_html__( 'Text as Clipping Mask', 'trx_addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => false,
				'render_type' => 'template',
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon svg' => '-webkit-background-clip: text;background-clip: text;-webkit-text-fill-color: transparent;',
				],
				'condition' => [
					'type' => ['icon','text'],
					'background_background' => ['classic','gradient']
				],
			],
		);

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'background',
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video'
			]
		);

		$repeater->add_control(
			'gradient',
			[
				'label' => esc_html__( 'Advanced Gradient', 'trx_addons' ),
				'label_block' => true,
				'type' => 'trx_gradient',
				'default' => '',
				'render_type' => 'template',
				'picker_options' => [
					'modes' => ['linear-gradient', 'radial-gradient'],
				],
				'condition' => [
					'background_background' => ['gradient']
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'background-image: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'animate_gradient',
			[
				'label' => esc_html__( 'Animate Gradient', 'trx_addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => false,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg' => 'background-size: 200% 100%; animation: trx-addons-marquee-animate-gradient 5s ease infinite;',
				],
				'condition' => [
					'type' => ['icon','text'],
					'background_background' => ['gradient']
				],
			]
		);

		$repeater->add_control(
			'backdrop_blur',
			[
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label' => __("Backdrop Blur ", 'trx_addons'),
				'label_on' => __( 'On', 'trx_addons' ),
				'label_off' => __( 'Off', 'trx_addons' ),
				'return_value' => 'on',
				'selectors' => array(
					'body.trx_addons_page_scrolled {{WRAPPER}}.sc_layouts_row_fixed_on' => 'backdrop-filter: blur( {{backdrop_blur_value.SIZE}}px );',
					'{{WRAPPER}}.sc_layouts_row_fixed_from_start.sc_layouts_row_fixed_on' => 'backdrop-filter: blur( {{backdrop_blur_value.SIZE}}px );',
				),
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon svg,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
					 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'backdrop-filter: blur( {{backdrop_blur_value.SIZE}}px );',
				],
			]
		);

		$repeater->add_responsive_control(
			'backdrop_blur_value',
			[
				'label' => __( 'Blur value', 'trx_addons' ),
				'description' => __( 'Set the blur value for the backdrop filter. Attention! The effect will work if the current item has a background color with transparency specified.', 'trx_addons' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'default' => array(
					'size' => '10',
					'unit' => 'px'
				),
				'size_units' => array( 'px' ),
				'condition' => array(
					'backdrop_blur' => array( 'on' ),
				),
			]
		);

		$repeater->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'        => 'border',
				'label'       => __( 'Border', 'trx_addons' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'separator' => 'before',
				'selector'    => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								  {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								  {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
								  {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								  {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								  {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video',
			)
		);

		$repeater->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'trx_addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
				'selectors'  => array(
								'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon  .trx-addons-marquee-item-icon-svg,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$repeater->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'      => 'text_stroke',
				'selector'  => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon svg',
				'condition' => array(
					'type' => array( 'text', 'icon' ),
				),
			]
		);

		$repeater->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'      => 'text_shadow',
				'label'     => __( 'Text Shadow', 'trx_addons' ),
				'selector'  => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg',
				'condition' => array(
					'type' => array( 'text', 'icon' ),
				),
			)
		);

		$repeater->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'box_shadow',
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video',
			]
		);

		$repeater->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'trx_addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
				'selectors'  => array(
								'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$repeater->add_responsive_control(
			'margin',
			array(
				'label'       => __( 'Margin', 'trx_addons' ),
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
				'selectors'   => array(
								'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-text,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon i,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-icon .trx-addons-marquee-item-icon-svg,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-image img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-gallery img,
								 {{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-item-video video' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$repeater->add_control(
			'heading_text_highlight',
			[
				'label' => __( 'Text Highlight', 'trx_addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'type' => 'text',
				]
			]
		);

		$repeater->add_control(
			'highlight_style',
			[
				'label' => __( 'Highlight Style', 'trx_addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'    => __( 'None', 'trx_addons' ),
					'color'   => __( 'Color', 'trx_addons' ),
					'stroke1' => __( 'Stroke 1', 'trx_addons' ),
					'stroke2' => __( 'Stroke 2', 'trx_addons' ),
					'stroke3' => __( 'Stroke 3', 'trx_addons' ),
					'stroke4' => __( 'Stroke 4', 'trx_addons' ),
					'stroke5' => __( 'Stroke 5', 'trx_addons' ),
					'stroke6' => __( 'Stroke 6', 'trx_addons' ),
					'stroke7' => __( 'Stroke 7', 'trx_addons' ),
					'stroke8' => __( 'Stroke 8', 'trx_addons' ),
					'stroke9' => __( 'Stroke 9', 'trx_addons' ),
				],
				'render_type' => 'template',
				'condition' => [
					'type' => 'text'
				],
			]
		);

		$repeater->add_control(
			'highlight_color',
			[
				'label' => esc_html__( 'Highlight Color', 'trx_addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#DF3232',
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} svg path' => 'stroke: {{VALUE}}', 
					'{{WRAPPER}} {{CURRENT_ITEM}}.trx-addons-marquee-color' => '--trx-addons-marquee-color: {{VALUE}};',
				],
				'condition' => [
					'highlight_style!' => ['none'],
					'type' => 'text',
				],
			]
		);

		$repeater->add_control(
			'highlight_width',
			[
				'label' => __( 'Highlight Width', 'trx_addons' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'default' => [
					'size' => '40',
					'unit' => 'px'
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} path' => 'stroke-width: {{SIZE}}',
				],
				'condition' => [
					'highlight_style!' => ['none', 'color'], // 'color' is added for compatibility with old versions
					'type' => 'text'
				],
			]
		);

		$repeater->add_control(
			'hightlight_offset',
			[
				'label' => __( 'Hightlight Offset', 'trx_addons' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'%' => [
						'min' => -100,
						'max' => 100,
					],
					'px' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'default' => [
					'size' => '0',
					'unit' => '%',
				],
				'size_units' => ['%', 'px'],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} svg' => 'bottom: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'highlight_style!' => ['none', 'color'], // 'color' is added for compatibility with old versions
					'type' => 'text'
				],
			]
		);

		$repeater->add_control(
			'hightlight_forward',
			[
				'label' => esc_html__( 'Bring Hightlight Forward', 'trx_addons' ),
				'type' => Controls_Manager::SWITCHER,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} svg,
					 {{WRAPPER}} {{CURRENT_ITEM}} .trx-addons-svg-wrapper .sc_item_animated_block' => 'z-index: 1;',
				],
				'condition' => [
					'highlight_style!' => ['none', 'color'],
					'type' => 'text'
				],
			]
		);

		$this->add_control(
			'content',
			[
				'label' => __( 'Content', 'trx_addons' ),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'text' => __( 'Enter your text here', 'trx_addons' ),
						'highlight_style' => 'none',
					],
				],
				'title_field' => '{{{ type == "text" ? text : type }}}',
				'render_type' => 'template',
			]
		);

		$this->add_control(
			'add_separator',
			[
				'label' => __( 'Add Separator', 'trx_addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '1',
				'return_value' => '1',
				'render_type' => 'template',
			]
		);

		$this->add_responsive_control( 'separator_size', array(
			'label' => __( 'Separator Size', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'default' => array(
				'size' => '',
				'unit' => 'px'
			),
			'range' => array(
				'px' => array(
					'min' => 0,
					'max' => 200
				),
				'em' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1
				),
				'rem' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1
				),
			),
			'size_units' => array( 'px', 'em', 'rem', 'vw', 'vh', 'custom' ),
			'selectors' => array(
				'{{WRAPPER}} .trx-addons-marquee-item-separator' => 'width: {{SIZE}}{{UNIT}};',
			),
			'condition' => array(
				'add_separator' => '1',
			),
		) );

		// $this->add_control(
		// 	'link',
		// 	[
		// 		'label' => __( 'Link', 'trx_addons' ),
		// 		'label_block' => true,
		// 		'type' => Controls_Manager::URL,
		// 		'dynamic'     => [
		// 			'active' => true,
		// 		],
		// 		'placeholder' => __('https://your-link.com', 'trx_addons'),
		// 	]
		// );

		$this->add_control(
			'tag',
			[
				'label' => __( 'HTML Tag', 'trx_addons' ),
				'type' => Controls_Manager::SELECT,
				'options' => trx_addons_get_list_sc_title_tags( '', true ),
				'default' => 'h2',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register Marquee Controls in Content tab
	 *
	 * @return void
	 */
	protected function register_content_controls_marquee() {

		/**
		 * Content Tab: Marquee
		 * -------------------------------------------------
		 */
		$this->start_controls_section(
			'section_marquee',
			array(
				'label' => __( 'Marquee', 'trx_addons' ),
			)
		);

		$this->add_control( 'show_in_preview', array(
			'label' => __( 'Show in Preview', 'trx_addons' ),
			'label_block' => false,
			'description' => __( 'Enable this option to play the marquee in Elementor editor.', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'separator' => 'after',
			'default' => '',
		) );

		$this->add_control( 'marquee_speed', array(
			'label' => __( 'Marquee speed', 'trx_addons' ),
			'type' => Controls_Manager::SLIDER,
			'default' => array(
				'size' => '5',
				'unit' => 'px'
			),
			'range' => array(
				'px' => array(
					'min' => 1,
					'max' => 15
				),
			),
			'size_units' => array( 'px' )
		) );

		$this->add_responsive_control( 'marquee_margin', array(
			'label' => __( 'Gap', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'default' => array(
				'size' => '',
				'unit' => 'px'
			),
			'range' => array(
				'px' => array(
					'min' => 0,
					'max' => 200
				),
				'em' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1
				),
				'%' => array(
					'min' => 0,
					'max' => 200
				),
			),
			'size_units' => array( 'px', 'em', '%' ),
			'selectors' => array(
				'{{WRAPPER}} .trx_addons_marquee_wrap:not(.trx_addons_marquee_reverse) .trx_addons_marquee_element' => is_rtl() ? 'padding-left: {{SIZE}}{{UNIT}};' : 'padding-right: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .trx_addons_marquee_wrap.trx_addons_marquee_reverse .trx_addons_marquee_element' => is_rtl() ? 'padding-right: {{SIZE}}{{UNIT}};' : 'padding-left: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'marquee_reverse', array(
			'label' => __( 'Reverse movement', 'trx_addons' ),
			'label_block' => false,
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
		) );

		$this->add_control( 'marquee_accelerate', array(
			'label' => __( 'Accelerate on wheel', 'trx_addons' ),
			'label_block' => false,
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'default' => '',
		) );

		$this->add_responsive_control( 'marquee_accelerate_coef', array(
			'label' => __( 'Multiplier', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SLIDER,
			'default' => array(
				'size' => 4,
				'unit' => 'px'
			),
			'range' => array(
				'px' => array(
					'min' => 2,
					'max' => 20
				),
			),
			'size_units' => array( 'px' ),
			'condition' => array(
				'marquee_accelerate' => 'yes',
			),
		) );

		$this->add_control( 'marquee_hover', array(
			'label' => __( 'Pause on hover', 'trx_addons' ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'label_block' => false,
			'default' => '',
		) );

		$this->end_controls_section();
	}

	/**
	 * Register Marquee Controls in Style tab
	 *
	 * @return void
	 */
	protected function register_style_controls_general() {
		/**
		 * Style Tab: Marquee
		 * -------------------------------------------------
		 */
		$this->start_controls_section(
			'section_style_text',
			[
				'label' => __( 'Headline', 'trx_addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'text_typography',
				'selector' => '{{WRAPPER}} .trx-addons-marquee',
			]
		);

		$this->add_control(
			'text_color',
			[
				'label' => esc_html__( 'Color', 'trx_addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .trx-addons-marquee' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register Highlight Controls in Style tab
	 *
	 * @return void
	 */
	protected function register_style_controls_highlight() {
		/**
		 * Style Tab: Highlight
		 * -------------------------------------------------
		 */
		$this->start_controls_section(
			'section_style_highlight',
			[
				'label' => __( 'Highlight Animation', 'trx_addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'highlight_animation',
			[
				'label' => __( 'Animation', 'trx_addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'trx_addons' ),
				'label_off' => __( 'Off', 'trx_addons' ),
				'default' => 'animate',
				'separator' => 'before',
				'return_value' =>'animate',
				'prefix_class' => 'trx-addons-',
			]
		);

		$this->add_control(
			'highlight_animation_delay',
			[
				'label' => __( 'Animation Delay', 'trx_addons' ) . ' (ms)',
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 100,
				'render_type' => 'template',
				'condition' => [
					'highlight_animation' => 'animate',
				],
			]
		);

		$this->add_control(
			'highlight_animation_duration',
			[
				'label' => __( 'Animation Duration', 'trx_addons' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 100,
				'render_type' => 'template',
				'default' => 2000,
				'condition' => [
					'highlight_animation' => 'animate',
				],
			]
		);

		$this->end_controls_section();
	}

	
	/*-----------------------------------------------------------------------------------*/
	/*	RENDER
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Render a widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$rtl = is_rtl();
		$marquee_dir = ! empty( $settings['marquee_reverse'] )
							? ( $rtl ? -1 : 1 )
							: ( $rtl ? 1 : -1 );
		$this->add_render_attribute( 'marquee_wrap', array(
			'class' => 'trx-addons-marquee-wrap trx_addons_marquee_wrap'
						. ( ( ! $rtl && $marquee_dir > 0 ) || ( $rtl && $marquee_dir < 0 ) ? ' trx_addons_marquee_reverse' : '' )
						. ( trx_addons_is_preview( 'elementor' ) && empty( $settings['show_in_preview'] ) ? ' trx_addons_in_preview_mode' : '' ),
			'data-marquee' => wp_json_encode( array(
				'dir' => $marquee_dir,
				'speed' => ! empty( $settings['marquee_speed'] ) ? (int)$settings['marquee_speed']['size'] : 5,
				'hover' => $settings['marquee_hover'] == 'yes',
				'accelerate' => $settings['marquee_accelerate'] == 'yes',
				'accelerate_coef' => ! empty( $settings['marquee_accelerate_coef'] ) ? min( 20, max( 2, (int)$settings['marquee_accelerate_coef']['size'] ) ) : 4,
			) )
		) );

		if ( ! empty( $settings['highlight_animation'] ) && (int)$settings['highlight_animation_duration'] > 0 ) {
			$this->add_render_attribute( 'marquee', array(
				'style' => '--trx-addons-marquee-highlight-animation-duration: ' . $settings['highlight_animation_duration'] . 'ms;',
			) );
		}

		?><div <?php $this->print_render_attribute_string( 'marquee_wrap' ) ?>><?php
		?><<?php echo esc_html( $settings['tag'] );
			echo ' class="trx-addons-marquee trx_addons_marquee_element trx_addons_marquee_element_with_offset' . ( ! trx_addons_is_preview( 'elementor' ) || ! empty( $settings['show_in_preview'] ) ? ' trx_addons_show_on_scroll' : '' ) . '"';
			echo ! empty( $settings['highlight_animation'] ) && (int)$settings['highlight_animation_delay'] > 0 ? ' data-delay="' . esc_attr( $settings['highlight_animation_delay'] ) . '"' : '';
			echo ' ' . wp_kses_post( $this->get_render_attribute_string( 'marquee' ) );
		?>><?php

		$idx = 0;
		foreach ( $settings['content'] as $item ) {

			$idx++;

			$link_url = '';
			if ( ! empty( $item['item_link']['url'] ) ) {
				$link_url = $item['item_link']['url'];
				$this->add_link_attributes( 'item-link-' . $idx, $item['item_link'] );
			} else if ( ! empty( $settings['link']['url'] ) ) {
				$link_url = $settings['link']['url'];
				$this->add_link_attributes( 'item-link-' . $idx, $settings['link'] );
			}
			$start_tag = $link_url ? '<a ' . wp_kses_post( $this->get_render_attribute_string( 'item-link-' . $idx ) ) : '<span';
			$end_tag = $link_url ? '</a>' : '</span>';

			$svg_markup = null;

			$rotation = ! empty( $item['marquee_rotation'] ) ? ' trx_addons_marquee_rotation' : '';

			switch ( $item['type'] ) {
				case 'text':
					if ( $item['highlight_style'] == 'none' || $item['highlight_style'] == 'color' ) {
						echo $start_tag . ' class="trx-addons-marquee-item trx-addons-marquee-item-text elementor-repeater-item-' . esc_attr( $item['_id'] )
								. ( ! empty( $item['background_as_text'] ) ? ' trx-addons-marquee-item-bg-as-text' : '' )
								. ( ! empty( $item['highlight_style'] != 'none' ) ? '  trx-addons-marquee-item-highlighted trx-addons-marquee-' . esc_attr( $item['highlight_style'] ) : '' )
								. $rotation
							. '">'
								. TrxAddonsUtils::esc_string( $item['text'] )
							. $end_tag;
					} else {
						$svg_markup = $this->get_svg( 'single', $item['highlight_style'] );
						echo $start_tag . ' class="trx-addons-marquee-item trx-addons-marquee-item-text elementor-repeater-item-' . esc_attr( $item['_id'] )
								. ( ! empty( $item['background_as_text'] ) ? ' trx-addons-marquee-item-bg-as-text' : '' )
								. '  trx-addons-marquee-item-highlighted trx-addons-marquee-' . esc_attr( $item['highlight_style'] )
								. $rotation
							. '">'
								. '<span class="trx-addons-marquee-text-wrap">'
									. '<span class="trx-addons-marquee-text">' . TrxAddonsUtils::esc_string( $item['text'] ) . '</span>'
									. TrxAddonsUtils::esc_svg( $svg_markup )
								. '</span>'
							. $end_tag;
					}
					break;
				case 'icon':
					ob_start();
					Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true', 'class' => 'char' ] );
					$icon = ob_get_clean();
					if ( strpos( $icon, '<svg' )  !== false ) {
						$icon = '<span class="trx-addons-marquee-item-icon-svg">' . $icon . '</span>';
					}
					echo $start_tag
							. ' class="trx-addons-marquee-item trx-addons-marquee-item-icon elementor-repeater-item-' . esc_attr( $item['_id'] ) . ( ! empty( $item['background_as_text'] ) ? ' trx-addons-marquee-item-bg-as-text' : '' ) . $rotation . '">'
						. $icon
						. $end_tag;
					break;
				case 'image':
					if ( ! empty( $item['image']['id'] ) || ! empty( $item['image']['url'] ) ) {
						echo $start_tag . ' class="trx-addons-marquee-item trx-addons-marquee-item-image elementor-repeater-item-' . esc_attr( $item['_id'] ) . $rotation . '">';
						echo trx_addons_get_attachment_img( $item['image'], trx_addons_get_thumb_size( $item['thumbnail_size'] ), array( 'filter' => 'marquee', 'class' => 'char' ) );
						echo $end_tag;
					}
					break;
				case 'gallery':
					if ( ! empty( $item['gallery'] ) && is_array( $item['gallery'] ) && count( $item['gallery'] ) > 0 ) {
						echo $start_tag . ' class="trx-addons-marquee-item trx-addons-marquee-item-gallery elementor-repeater-item-' . esc_attr( $item['_id'] ) . $rotation . '"'
								. ' data-gallery-interval="' . esc_attr( $item['gallery_interval']['size'] ) . '"'
								. ' data-gallery-delay="' . esc_attr( $item['gallery_delay']['size'] ) . '"'
							. '>';
						echo '<span class="trx-addons-marquee-item-gallery-wrap">';
						foreach ( $item['gallery'] as $gallery_item ) {
							if ( ! empty( $gallery_item['id'] ) || ! empty( $gallery_item['url'] ) ) {
								echo trx_addons_get_attachment_img( $gallery_item, trx_addons_get_thumb_size( $item['thumbnail_size'] ), array(
										'filter' => 'marquee',
										'class' => 'char',
										'loading' => 'eager',
										'decoding' => 'sync',
										'fetchpriority' => 'high',
									) );
							}
						}
						echo '</span>';
						echo $end_tag;
					}
					break;
				case 'video':
					if ( ! empty( $item['video']['url'] ) ) {
						echo $start_tag . ' class="trx-addons-marquee-item trx-addons-marquee-item-video elementor-repeater-item-' . esc_attr( $item['_id'] ) . $rotation . '">';
						echo '<video class="char no-mejs" src="' . esc_url( $item['video']['url'] ) . '" muted="muted" loop="loop" playsinline="playsinline" autoplay="autoplay" onloadeddata="this.muted=true; this.play();"></video>';
						echo $end_tag;
					}
					break;
				default:
					break;
			}
			
			if ( ! empty( $settings['add_separator'] ) ) {
				echo '<span class="trx-addons-marquee-item-separator"></span>';
			}
		}

		?></<?php echo esc_html( $settings['tag'] ); ?>></div><?php
	}

	/**
	 * Render a shape svg output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 * 
	 * @param string $return  Return type. Possible values are 'list' and 'single'.
	 * @param string $type    Shape type.
	 *
	 * @access protected
	 */
	protected function get_svg( $return, $type = '' ) {
		$paths = [
			'none'    => null,
			'stroke1' => "<path d='M15.2,133.3L15.2,133.3c121.9-7.6,244-9.9,366.1-6.8c34.6,0.9,69.1,2.3,103.7,4'/>",
			'stroke2' => "<path d='M479,122c-13.3-1-26.8-1-40.4-2.3c-31.8-1.2-63.7,0.4-95.3,0.6c-38.5,1.5-77,2.3-115.5,5.2
									c-41.6,1.2-83.3,5-124.9,5.2c-5.4,0.4-11,1-16.4,0.8c-21.9-0.4-44.1,1.9-65.6-3.5'/>",
			'stroke3' => "<path d='M15,133.4c19-12.7,48.1-11.4,69.2-8.2
									c6.3,1.1,12.9,2.1,19.2,3.4c16.5,3.2,33.5,6.3,50.6,5.5c12.7-0.6,24.9-3.4,36.7-6.1c11-2.5,22.4-5.1,34.2-5.9
									c24.3-1.9,48.5,3.4,71.9,8.4c27.6,6.1,53.8,11.8,80.4,6.8c9.9-1.9,19.2-5.3,28.3-8.4c8.2-3,16.9-5.9,25.9-8
									c20.3-4.4,45.8-1.1,53.6,12.2'/>",
			'stroke4' => "<path d='M18,122.6c42.3-4.6,87.4-5.1,130.3-1.6'/>
						<path d='M166.7,121.3c29.6,1.6,60,3.3,90.1,1.8c12.4-0.5,24.8-1.6,36.9-2.7c7.3-0.7,14.8-1.3,22.3-1.8
									c55.5-4.2,112.6-1.8,166,1.1'/>
						<path d='M57.8,133c30.8-0.7,62,1.1,92.1,2.7c30.5,1.8,62,3.6,93.2,2.7c20.4-0.5,41.1-2.4,61.1-4
									c37.6-3.1,76.5-6.4,113.7-2'/>",
			'stroke5' => "<path d='M53.4,135.8c-12.8-1.5-25.6-1.3-38.3,0.7'/>
						<path d='M111.2,136c-12.2-0.2-24.4-0.5-36.7-0.8'/>
						<path d='M163.3,135.2c-12.2,0.2-24.4,0.5-36.6,0.8'/>
						<path d='M217.8,134.7c-12.5,0.6-24.9,1.2-37.4,1.8'/>
						<path d='M274.7,135.5c-12.8,0.1-25.5,0.1-38.3,0.2'/>
						<path d='M327.6,135.1c-13.6-0.8-27.2-0.3-40.7,1.4'/>
						<path d='M378.8,134.7c-12.2,0.6-24.4,1.2-36.6,1.8'/>
						<path d='M432.5,136.4c-12.2-0.6-24.4-1.1-36.6-1.7'/>
						<path d='M487.9,136.1c-11.6-1.3-23.3-1.4-35-0.2'/>",
			'stroke6' => "<path d='M14.4,111.6c0,0,202.9-33.7,471.2,0c0,0-194-8.9-397.3,24.7c0,0,141.9-5.9,309.2,0'/>",
			'stroke7' => "<path d='M15.2 133.3H485'/>",
			'stroke8' => '<path d="M1.65186 148.981C1.65186 148.981 73.8781 98.5943 206.859 93.0135C339.841 87.4327 489.874 134.065 489.874 134.065"/>',
			'stroke9' => '<path d="M7 74.5C7 74.5 104 127 252 117C400 107 494.5 49 494.5 49C494.5 49 473.5 59 461.5 74.5C449.5 90 449.5 107 449.5 107"/>
						<path d="M20.5 101.5C20.5 101.5 93 133.5 180.5 142.5C268 151.5 347 127.5 347 127.5"/>'
		];

		if ( $return == 'list' ) {
			foreach ( $paths as $name => $path ) {
				if ( $name == 'none' ) {
					$svg[ $name ] = "";
				} else {
					$svg[ $name ] = '<span class="trx-addons-svg-wrapper"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none">' . $path . '</svg></span>';
				}
			}
		} else {
			if ( $type == 'none' ) {
				$svg = "";
			} else {
				$svg = '<span class="trx-addons-svg-wrapper"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none">' . $paths[ $type ] . '</svg></span>';
			}
		}

		return $svg;
	}

	/**
	 * Render a widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @access protected
	 */
	protected function content_template() {
		?><#
		var svgs = <?php echo wp_json_encode( $this->get_svg( 'list' ) ); ?>;

		var rtl = jQuery( 'body' ).hasClass( 'rtl' );
		var marquee_dir = settings.marquee_reverse
							? ( rtl ? -1 : 1 )
							: ( rtl ? 1 : -1 );
		view.addRenderAttribute( 'marquee_wrap', 'class', 'trx-addons-marquee-wrap trx_addons_marquee_wrap'
															+ ( ! settings.show_in_preview ? ' trx_addons_in_preview_mode' : '' )
															+ ( ( ! rtl && marquee_dir > 0 ) || ( rtl && marquee_dir < 0 ) ? ' trx_addons_marquee_reverse' : '' )
		);
		view.addRenderAttribute( 'marquee_wrap', 'data-marquee', JSON.stringify( {
			dir: marquee_dir,
			speed: settings.marquee_speed.size,
			hover: settings.marquee_hover == 'yes',
			accelerate: settings.marquee_accelerate == 'yes'
		} ) );

		if ( settings.highlight_animation && settings.highlight_animation_duration > 0 ) {
			view.addRenderAttribute( 'marquee', 'style', '--trx-addons-marquee-highlight-animation-duration: ' + settings.highlight_animation_duration + 'ms;' );
		}

		#><div {{{ view.getRenderAttributeString( 'marquee_wrap' ) }}}><#
		#><{{{ settings.tag }}} class="trx-addons-marquee trx_addons_marquee_element trx_addons_marquee_element_with_offset<#
										if ( settings.show_in_preview ) print( ' trx_addons_show_on_scroll' );
									#>"<#
			print( settings.highlight_animation && settings.highlight_animation_delay > 0 ? ' data-delay="' + settings.highlight_animation_delay + '"' : '' );
		#> {{{ view.getRenderAttributeString( 'marquee' ) }}}><#

		settings.content.forEach( function( item ) {

			var link_url = '',
				link_atts = '';
			if ( item.item_link && item.item_link.url != '' ) {
				link_url = item.item_link.url;
				link_atts = trx_addons_get_link_attributes( item.item_link );
			} else if ( settings.link && settings.link.url ) {
				link_url = settings.link.url;
				link_atts = trx_addons_get_link_attributes( settings.link );
			}
			var start_tag = link_url ? '<a href="' + link_url + '"' + link_atts : '<span';
			var end_tag = link_url ? '</a>' : '</span>';

			var rotation = item.marquee_rotation ? ' trx_addons_marquee_rotation' : '';

			switch ( item.type ) {
				case 'text':
					if ( item.highlight_style == 'none' || item.highlight_style == 'color' ) {	// 'color' is added for compatibility with old versions
						#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-text elementor-repeater-item-{{{ item._id }}}<#
							if ( item.background_as_text ) {
								print( ' trx-addons-marquee-item-bg-as-text' );
							}
							if ( item.highlight_style != 'none' ) {
								print( ' trx-addons-marquee-item-highlighted trx-addons-marquee-' + item.highlight_style );
							}
						#>{{{ rotation }}}">{{{ item.text }}}{{{ end_tag }}}<#
					} else {
						#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-text elementor-repeater-item-{{{ item._id }}} trx-addons-marquee-item-highlighted {{{ 'trx-addons-marquee-' + item.highlight_style }}}<#
							if ( item.background_as_text ) {
								print( ' trx-addons-marquee-item-bg-as-text' );
							}
						#>{{{ rotation }}}"><#
							#><span class="trx-addons-marquee-text-wrap"><#
								#><span class="trx-addons-marquee-text">{{{ item.text }}}</span><#
								#>{{{ svgs[ item.highlight_style ] }}}<#
							#></span><#
						#>{{{ end_tag }}}<#
					}
					break;
				case 'icon':
					var iconHTML = elementor.helpers.renderIcon( view, item.icon, { 'aria-hidden': true, 'class': 'char' }, 'i' , 'value' );
					if ( typeof iconHTML == 'object' ) {
						iconHTML = iconHTML.value || '';
					}
					if ( iconHTML && iconHTML.indexOf( '<svg' ) >= 0 ) {
						iconHTML = '<span class="trx-addons-marquee-item-icon-svg">' + iconHTML + '</span>';
					}
					#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-icon elementor-repeater-item-{{{ item._id }}}{{{ rotation }}}">{{{ iconHTML }}}{{{ end_tag }}}<#
					break;
				case 'image':
					var image = {
						id: item.image.id,
						url: item.image.url,
						size: item.thumbnail_size,
						// dimension: item.thumbnail_custom_dimension,
						model: view.getEditModel()
					};
					var image_url = elementor.imagesManager.getImageUrl( image );
					#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-image elementor-repeater-item-{{{ item._id }}}{{{ rotation }}}"><img class="char" src="{{{ image_url }}}" />{{{ end_tag }}}<#
					break;
				case 'gallery':
					var image = {
						size: item.thumbnail_size,
						// dimension: item.thumbnail_custom_dimension,
						model: view.getEditModel()
					};
					var image_url = '';
					#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-gallery elementor-repeater-item-{{{ item._id }}}{{{ rotation }}}"
						data-gallery-interval="{{{ item.gallery_interval.size }}}"
						data-gallery-delay="{{{ item.gallery_delay.size }}}"
					><#
						#><span class="trx-addons-marquee-item-gallery-wrap"><#
							for ( var i in item.gallery ) {
								if ( item.gallery[ i ].id || item.gallery[ i ].url ) {
									image.id = item.gallery[ i ].id;
									image.url = item.gallery[ i ].url;
								} else {
									continue;
								}
								image_url = elementor.imagesManager.getImageUrl( image );
								#><img class="char" src="{{{ image_url }}}" /><#
							}
						#></span><#
					#>{{{ end_tag }}}<#
					break;
				case 'video':
					#>{{{ start_tag }}} class="trx-addons-marquee-item trx-addons-marquee-item-video elementor-repeater-item-{{{ item._id }}}{{{ rotation }}}"><#
						#><video class="char no-mejs" src="{{{ item.video.url }}}" muted="muted" loop="loop" playsinline="playsinline" autoplay="autoplay" onloadeddata="this.muted=true; this.play();"></video><#
					#>{{{ end_tag }}}<#
					break;
				default:
					break;
			}

			if ( settings.add_separator ) {
				#><span class="trx-addons-marquee-item-separator"></span><#
			}

		} );
		#></{{{ settings.tag }}}><?php
		#></div><#
	}
}
