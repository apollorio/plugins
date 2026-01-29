<?php
/**
 * Apollo Home Manifesto Widget
 *
 * Elementor widget for the Manifesto/Mission section on Home page.
 *
 * @package Apollo_Core
 * @subpackage Elementor\Widgets
 * @since 2.1.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Home_Manifesto_Widget
 *
 * Renders the Manifesto/Mission section for the Home page.
 */
class Apollo_Home_Manifesto_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo_home_manifesto';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return __( 'Apollo Manifesto', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-text-area';
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
		return array( 'manifesto', 'mission', 'about', 'apollo', 'text' );
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
				'label' => __( 'Conteúdo', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_label',
			array(
				'label'       => __( 'Etiqueta da Seção', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'A Missão', 'apollo-core' ),
				'placeholder' => __( 'Ex: Manifesto, Sobre, Missão...', 'apollo-core' ),
			)
		);

		$this->add_control(
			'manifesto_text',
			array(
				'label'       => __( 'Texto do Manifesto', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::WYSIWYG,
				'default'     => __( 'O Apollo::rio é um projeto estruturante e territorial. Atuamos como a ferramenta de cultura digital do Rio de Janeiro, orientados à economia criativa e à difusão do acesso. Conectamos cenas, mapeamos dinâmicas culturais e impulsionamos toda a cadeia produtiva da cultura brasileira — dos artistas ao público, dos fornecedores aos produtores.', 'apollo-core' ),
				'placeholder' => __( 'Digite o texto do manifesto aqui...', 'apollo-core' ),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => __( 'Mostrar Imagem', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Sim', 'apollo-core' ),
				'label_off'    => __( 'Não', 'apollo-core' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'manifesto_image',
			array(
				'label'     => __( 'Imagem', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'default'   => array(
					'url' => '',
				),
				'condition' => array(
					'show_image' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Additional Points Section.
		$this->start_controls_section(
			'points_section',
			array(
				'label' => __( 'Pontos Chave', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_points',
			array(
				'label'        => __( 'Mostrar Pontos Chave', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Sim', 'apollo-core' ),
				'label_off'    => __( 'Não', 'apollo-core' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'point_icon',
			array(
				'label'   => __( 'Ícone', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-check',
					'library' => 'fa-solid',
				),
			)
		);

		$repeater->add_control(
			'point_title',
			array(
				'label'       => __( 'Título', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Ponto Chave', 'apollo-core' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'point_description',
			array(
				'label'       => __( 'Descrição', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'key_points',
			array(
				'label'       => __( 'Pontos', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'point_title'       => __( 'Ferramenta Digital', 'apollo-core' ),
						'point_description' => __( 'Plataforma de cultura digital do Rio de Janeiro.', 'apollo-core' ),
					),
					array(
						'point_title'       => __( 'Economia Criativa', 'apollo-core' ),
						'point_description' => __( 'Orientados à economia criativa e à difusão do acesso.', 'apollo-core' ),
					),
					array(
						'point_title'       => __( 'Cadeia Produtiva', 'apollo-core' ),
						'point_description' => __( 'Dos artistas ao público, dos fornecedores aos produtores.', 'apollo-core' ),
					),
				),
				'title_field' => '{{{ point_title }}}',
				'condition'   => array(
					'show_points' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Style Section.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Estilo', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Cor da Etiqueta', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#F79552',
				'selectors' => array(
					'{{WRAPPER}} .section-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Cor do Texto', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array(
					'{{WRAPPER}} .manifesto-text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => __( 'Tipografia da Etiqueta', 'apollo-core' ),
				'selector' => '{{WRAPPER}} .section-label',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => __( 'Tipografia do Texto', 'apollo-core' ),
				'selector' => '{{WRAPPER}} .manifesto-text',
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Cor de Fundo', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .manifesto' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'apollo-core' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .manifesto' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
		?>
		<section id="manifesto" class="manifesto container">
			<div class="manifesto-grid">
				<div class="manifesto-label-col">
					<h3 class="section-label"><?php echo esc_html( $settings['section_label'] ); ?></h3>

					<?php if ( 'yes' === $settings['show_image'] && ! empty( $settings['manifesto_image']['url'] ) ) : ?>
						<div class="manifesto-image">
							<img src="<?php echo esc_url( $settings['manifesto_image']['url'] ); ?>" alt="<?php echo esc_attr( $settings['section_label'] ); ?>">
						</div>
					<?php endif; ?>
				</div>

				<div class="manifesto-text-col">
					<div class="manifesto-text">
						<?php echo wp_kses_post( $settings['manifesto_text'] ); ?>
					</div>

					<?php if ( 'yes' === $settings['show_points'] && ! empty( $settings['key_points'] ) ) : ?>
						<div class="manifesto-points">
							<?php foreach ( $settings['key_points'] as $point ) : ?>
								<div class="manifesto-point">
									<?php if ( ! empty( $point['point_icon']['value'] ) ) : ?>
										<span class="manifesto-point-icon">
											<?php \Elementor\Icons_Manager::render_icon( $point['point_icon'], array( 'aria-hidden' => 'true' ) ); ?>
										</span>
									<?php endif; ?>
									<div class="manifesto-point-content">
										<h4 class="manifesto-point-title"><?php echo esc_html( $point['point_title'] ); ?></h4>
										<?php if ( ! empty( $point['point_description'] ) ) : ?>
											<p class="manifesto-point-desc"><?php echo esc_html( $point['point_description'] ); ?></p>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render widget output in the editor.
	 *
	 * @return void
	 */
	protected function content_template(): void {
		?>
		<#
		#>
		<section id="manifesto" class="manifesto container">
			<div class="manifesto-grid">
				<div class="manifesto-label-col">
					<h3 class="section-label">{{{ settings.section_label }}}</h3>

					<# if ( 'yes' === settings.show_image && settings.manifesto_image.url ) { #>
						<div class="manifesto-image">
							<img src="{{{ settings.manifesto_image.url }}}" alt="{{{ settings.section_label }}}">
						</div>
					<# } #>
				</div>

				<div class="manifesto-text-col">
					<div class="manifesto-text">
						{{{ settings.manifesto_text }}}
					</div>

					<# if ( 'yes' === settings.show_points && settings.key_points.length ) { #>
						<div class="manifesto-points">
							<# _.each( settings.key_points, function( point ) { #>
								<div class="manifesto-point">
									<# if ( point.point_icon.value ) { #>
										<span class="manifesto-point-icon">
											<i class="{{{ point.point_icon.value }}}"></i>
										</span>
									<# } #>
									<div class="manifesto-point-content">
										<h4 class="manifesto-point-title">{{{ point.point_title }}}</h4>
										<# if ( point.point_description ) { #>
											<p class="manifesto-point-desc">{{{ point.point_description }}}</p>
										<# } #>
									</div>
								</div>
							<# }); #>
						</div>
					<# } #>
				</div>
			</div>
		</section>
		<?php
	}
}
