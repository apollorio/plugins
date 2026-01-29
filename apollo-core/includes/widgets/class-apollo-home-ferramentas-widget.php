<?php
/**
 * Apollo Home Ferramentas Elementor Widget
 *
 * Displays the tools/ferramentas section with accordion.
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
 * Class Apollo_Home_Ferramentas_Widget
 *
 * Ferramentas section widget with accordion.
 */
class Apollo_Home_Ferramentas_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-home-ferramentas';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Apollo Ferramentas', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-accordion';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return array( 'apollo' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'tools', 'ferramentas', 'accordion', 'roster' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Intro Section.
		$this->start_controls_section(
			'intro_section',
			array(
				'label' => esc_html__( 'Introdução', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_label',
			array(
				'label'   => esc_html__( 'Label', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Ferramenta', 'apollo-core' ),
			)
		);

		$this->add_control(
			'section_title',
			array(
				'label'   => esc_html__( 'Título', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'DJ Global Roster',
			)
		);

		$this->add_control(
			'section_description',
			array(
				'label'   => esc_html__( 'Descrição', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Conectando sons locais a plataformas globais. Curadoria internacional sem intermediários.', 'apollo-core' ),
			)
		);

		$this->end_controls_section();

		// Tools Accordion Section.
		$this->start_controls_section(
			'tools_section',
			array(
				'label' => esc_html__( 'Ferramentas', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'tool_title',
			array(
				'label'   => esc_html__( 'Título', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Ferramenta', 'apollo-core' ),
			)
		);

		$repeater->add_control(
			'tool_description',
			array(
				'label'   => esc_html__( 'Descrição', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);

		$repeater->add_control(
			'tool_image_1',
			array(
				'label' => esc_html__( 'Imagem 1', 'apollo-core' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);

		$repeater->add_control(
			'tool_image_2',
			array(
				'label' => esc_html__( 'Imagem 2', 'apollo-core' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);

		$repeater->add_control(
			'tool_iframe',
			array(
				'label' => esc_html__( 'URL do iFrame', 'apollo-core' ),
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->add_control(
			'tools',
			array(
				'label'   => esc_html__( 'Lista de Ferramentas', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => array(
					array(
						'tool_title'       => __( "Plano, apollo's image creative studio", 'apollo-core' ),
						'tool_description' => __( 'Estúdio criativo para produção de material visual. Crie flyers, capas e identidades visuais para seus eventos e releases.', 'apollo-core' ),
					),
					array(
						'tool_title'       => __( 'Doc & Assina::rio, seu contrato fácil', 'apollo-core' ),
						'tool_description' => __( 'Contratos digitais simplificados para a indústria criativa. Assinatura eletrônica válida juridicamente.', 'apollo-core' ),
					),
					array(
						'tool_title'       => __( 'Cena::rio, ferramentas da indústria cultural', 'apollo-core' ),
						'tool_description' => __( 'Suite completa de ferramentas para produtores, promoters e artistas. Gestão de eventos, bilheteria e analytics.', 'apollo-core' ),
					),
					array(
						'tool_title'       => __( 'Repasses de Ingressos & Acomodações', 'apollo-core' ),
						'tool_description' => __( 'Marketplace seguro para revenda de ingressos e hospedagem compartilhada durante eventos.', 'apollo-core' ),
					),
				),
				'title_field' => '{{{ tool_title }}}',
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
		?>
		<section id="roster" class="tools-section container">
			<div class="tools-grid">
				<div class="tools-intro reveal-up">
					<div class="hub-status" style="margin-bottom:16px">
						<span class="hub-pulse"></span>
						<span class="hub-status-text"><?php echo esc_html( $settings['section_label'] ); ?></span>
					</div>
					<h2><?php echo esc_html( $settings['section_title'] ); ?></h2>
					<p class="card-text"><?php echo esc_html( $settings['section_description'] ); ?></p>
				</div>
				<div class="reveal-up delay-100">
					<h3 class="section-label" style="margin-bottom:24px">
						<?php esc_html_e( 'Ferramentas', 'apollo-core' ); ?>
					</h3>
					<div class="accordion">
						<?php foreach ( $settings['tools'] as $index => $tool ) : ?>
							<div class="accordion-item">
								<button class="accordion-trigger" type="button">
									<span class="accordion-title"><?php echo esc_html( $tool['tool_title'] ); ?></span>
									<span class="accordion-icon"></span>
								</button>
								<div class="accordion-content">
									<div class="accordion-inner">
										<?php if ( ! empty( $tool['tool_description'] ) ) : ?>
											<p><?php echo esc_html( $tool['tool_description'] ); ?></p>
										<?php endif; ?>
										<?php if ( ! empty( $tool['tool_image_1']['url'] ) || ! empty( $tool['tool_image_2']['url'] ) ) : ?>
											<div class="accordion-images">
												<?php if ( ! empty( $tool['tool_image_1']['url'] ) ) : ?>
													<img src="<?php echo esc_url( $tool['tool_image_1']['url'] ); ?>" alt="">
												<?php endif; ?>
												<?php if ( ! empty( $tool['tool_image_2']['url'] ) ) : ?>
													<img src="<?php echo esc_url( $tool['tool_image_2']['url'] ); ?>" alt="">
												<?php endif; ?>
											</div>
										<?php endif; ?>
										<?php if ( ! empty( $tool['tool_iframe']['url'] ) ) : ?>
											<div class="accordion-iframe">
												<iframe src="<?php echo esc_url( $tool['tool_iframe']['url'] ); ?>" allowfullscreen></iframe>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
