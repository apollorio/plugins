<?php
/**
 * Apollo Classifieds Grid Elementor Widget
 *
 * Displays apollo_classified posts in a grid layout for accommodations and tickets.
 *
 * @package Apollo_Core
 * @subpackage Elementor
 * @since 2.1.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure Elementor is loaded.
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Class Apollo_Classifieds_Grid_Widget
 *
 * Elementor widget for displaying classifieds.
 */
class Apollo_Classifieds_Grid_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-classifieds-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Apollo Classificados', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return array( 'apollo', 'apollo-social' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'classifieds', 'classificados', 'tickets', 'accommodations' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Content Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'Conteúdo', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'display_type',
			array(
				'label'   => esc_html__( 'Tipo de Exibição', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'mixed',
				'options' => array(
					'mixed'          => esc_html__( 'Todos', 'apollo-core' ),
					'accommodations' => esc_html__( 'Acomodações', 'apollo-core' ),
					'tickets'        => esc_html__( 'Repasse de Ingressos', 'apollo-core' ),
				),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'Quantidade', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 24,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'   => esc_html__( 'Colunas', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '4',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'selectors' => array(
					'{{WRAPPER}} .accommodations-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		// Domain filter.
		$domains = get_terms(
			array(
				'taxonomy'   => 'classified_domain',
				'hide_empty' => false,
			)
		);

		$domain_options = array( '' => esc_html__( 'Todos', 'apollo-core' ) );
		if ( ! is_wp_error( $domains ) ) {
			foreach ( $domains as $domain ) {
				$domain_options[ $domain->slug ] = $domain->name;
			}
		}

		$this->add_control(
			'domain',
			array(
				'label'   => esc_html__( 'Domínio', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $domain_options,
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => esc_html__( 'Mostrar Preço', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => esc_html__( 'Sim', 'apollo-core' ),
				'label_off'    => esc_html__( 'Não', 'apollo-core' ),
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Section.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Estilo', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'      => esc_html__( 'Espaçamento', 'apollo-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array(
					'size' => 16,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .accommodations-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();

		// Build query args.
		$args = array(
			'post_type'      => 'apollo_classified',
			'posts_per_page' => (int) $settings['count'],
			'post_status'    => 'publish',
		);

		// Filter by domain.
		if ( ! empty( $settings['domain'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'classified_domain',
				'field'    => 'slug',
				'terms'    => $settings['domain'],
			);
		}

		$items = get_posts( $args );

		if ( empty( $items ) ) {
			echo '<p class="apollo-no-items">' . esc_html__( 'Nenhum classificado encontrado.', 'apollo-core' ) . '</p>';
			return;
		}

		?>
		<div class="accommodations-grid">
			<?php
			foreach ( $items as $item ) {
				apollo_classified_card( $item->ID, array(
					'context' => 'grid',
					'show_contact' => false,
				) );
			}
			wp_reset_postdata();
			?>
		</div>
		<?php
	}
}
