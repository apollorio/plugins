<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Apollo\Infrastructure\Rendering\GroupDirectoryRenderer;

/**
 * Apollo Groups Directory Widget
 */
class Apollo_Groups_Directory_Widget extends Widget_Base {

	public function get_name() {
		return 'apollo_groups_directory';
	}

	public function get_title() {
		return __( 'Apollo Groups Directory', 'apollo-social' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'apollo-social' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'apollo-social' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'group_type',
			array(
				'label'   => __( 'Group Type', 'apollo-social' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''           => __( 'All Types', 'apollo-social' ),
					'comunidade' => __( 'Comunidade', 'apollo-social' ),
					'nucleo'     => __( 'Núcleo', 'apollo-social' ),
					'season'     => __( 'Season', 'apollo-social' ),
				),
			)
		);

		$this->add_control(
			'season_slug',
			array(
				'label'       => __( 'Season Slug', 'apollo-social' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'verao-2025', 'apollo-social' ),
				'condition'   => array(
					'group_type' => 'season',
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Limit', 'apollo-social' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$template_data = array(
			'type'   => $settings['group_type'],
			'season' => $settings['season_slug'],
			'limit'  => $settings['limit'],
		);

		$renderer = new GroupDirectoryRenderer();
		$output   = $renderer->render( $template_data );

		echo '<div class="apollo-elementor-widget apollo-groups-directory-widget">';
		echo $output['content'];
		echo '</div>';
	}
}
