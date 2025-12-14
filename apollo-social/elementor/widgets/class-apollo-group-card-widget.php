<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Apollo\Infrastructure\Rendering\GroupPageRenderer;

/**
 * Apollo Group Card Widget
 */
class Apollo_Group_Card_Widget extends Widget_Base {

	public function get_name() {
		return 'apollo_group_card';
	}

	public function get_title() {
		return __( 'Apollo Group Card', 'apollo-social' );
	}

	public function get_icon() {
		return 'eicon-post-content';
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
			'group_slug',
			array(
				'label'       => __( 'Group Slug', 'apollo-social' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'desenvolvedores-php', 'apollo-social' ),
			)
		);

		$this->add_control(
			'group_type',
			array(
				'label'   => __( 'Group Type', 'apollo-social' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'comunidade',
				'options' => array(
					'comunidade' => __( 'Comunidade', 'apollo-social' ),
					'nucleo'     => __( 'Núcleo', 'apollo-social' ),
					'season'     => __( 'Season', 'apollo-social' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$template_data = array(
			'type'  => $settings['group_type'],
			'param' => $settings['group_slug'],
		);

		$renderer = new GroupPageRenderer();
		$output   = $renderer->render( $template_data );

		echo '<div class="apollo-elementor-widget apollo-group-card-widget">';
		echo '<div class="apollo-group-card">';
		echo '<h3>' . esc_html( $output['group']['name'] ) . '</h3>';
		echo '<p>' . esc_html( $output['group']['description'] ) . '</p>';
		echo '<p>Tipo: ' . ucfirst( $output['group']['type'] ) . '</p>';
		echo '<p>Membros: ' . intval( $output['group']['members_count'] ) . '</p>';

		$cta_text = $this->getCTAText( $settings['group_type'] );
		echo '<a href="/' . $settings['group_type'] . '/' . $settings['group_slug'] . '/" class="apollo-cta-button">' . $cta_text . '</a>';

		echo '</div>';
		echo '</div>';
	}

	private function getCTAText( $type ) {
		switch ( $type ) {
			case 'comunidade':
				return 'Entrar na Comunidade';
			case 'nucleo':
				return 'Pedir Convite';
			case 'season':
				return 'Participar da Season';
			default:
				return 'Ver Grupo';
		}
	}
}
