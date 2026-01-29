<?php
/**
 * Apollo Social Share Elementor Widget
 *
 * Displays social sharing buttons.
 *
 * @package Apollo_Social
 * @subpackage Elementor\Widgets
 * @since 1.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure dependencies are loaded.
if ( ! class_exists( 'Apollo_Base_Widget' ) ) {
	return;
}

/**
 * Class Apollo_Social_Share_Widget
 *
 * Elementor widget for displaying social share buttons.
 */
class Apollo_Social_Share_Widget extends Apollo_Base_Widget {

	/**
	 * Widget category.
	 *
	 * @var string
	 */
	protected string $widget_category = 'apollo-social';

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-social-share';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Compartilhar Social', 'apollo-social' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-share';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'social', 'share', 'compartilhar', 'redes', 'facebook', 'twitter', 'whatsapp' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Networks Section.
		$this->start_controls_section(
			'section_networks',
			array(
				'label' => esc_html__( 'Redes Sociais', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'network',
			array(
				'label'   => esc_html__( 'Rede', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'facebook',
				'options' => array(
					'facebook'  => 'Facebook',
					'twitter'   => 'Twitter/X',
					'whatsapp'  => 'WhatsApp',
					'telegram'  => 'Telegram',
					'linkedin'  => 'LinkedIn',
					'pinterest' => 'Pinterest',
					'email'     => 'Email',
					'copy'      => __( 'Copiar Link', 'apollo-social' ),
				),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Rótulo', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'dynamic' => array(
					'active' => true,
				),
			)
		);

		$repeater->add_control(
			'custom_color',
			array(
				'label' => esc_html__( 'Cor Personalizada', 'apollo-social' ),
				'type'  => \Elementor\Controls_Manager::COLOR,
			)
		);

		$this->add_control(
			'networks',
			array(
				'label'       => esc_html__( 'Redes', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'network' => 'facebook' ),
					array( 'network' => 'twitter' ),
					array( 'network' => 'whatsapp' ),
					array( 'network' => 'linkedin' ),
					array( 'network' => 'copy' ),
				),
				'title_field' => '{{{ network.charAt(0).toUpperCase() + network.slice(1) }}}',
			)
		);

		$this->end_controls_section();

		// Content Section.
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Conteúdo', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'share_url',
			array(
				'label'       => esc_html__( 'URL para Compartilhar', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Deixe vazio para usar URL atual', 'apollo-social' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'share_title',
			array(
				'label'       => esc_html__( 'Título', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Deixe vazio para usar título da página', 'apollo-social' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'share_description',
			array(
				'label'       => esc_html__( 'Descrição', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'placeholder' => esc_html__( 'Texto adicional para compartilhamento', 'apollo-social' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->end_controls_section();

		// Layout Section.
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => esc_html__( 'Estilo', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'icons',
				'options' => array(
					'icons'        => __( 'Apenas Ícones', 'apollo-social' ),
					'icons_labels' => __( 'Ícones + Rótulos', 'apollo-social' ),
					'buttons'      => __( 'Botões Completos', 'apollo-social' ),
					'minimal'      => __( 'Minimal', 'apollo-social' ),
				),
			)
		);

		$this->add_control(
			'alignment',
			array(
				'label'   => esc_html__( 'Alinhamento', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => array(
					'left'   => array(
						'title' => __( 'Esquerda', 'apollo-social' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Centro', 'apollo-social' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Direita', 'apollo-social' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default' => 'left',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => esc_html__( 'Mostrar Contagem', 'apollo-social' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_label',
			array(
				'label'        => esc_html__( 'Mostrar Rótulo "Compartilhar"', 'apollo-social' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Style Section - Buttons.
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => esc_html__( 'Botões', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'color_scheme',
			array(
				'label'   => esc_html__( 'Esquema de Cores', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'official',
				'options' => array(
					'official' => __( 'Cores Oficiais', 'apollo-social' ),
					'custom'   => __( 'Cor Única', 'apollo-social' ),
					'gradient' => __( 'Gradiente', 'apollo-social' ),
				),
			)
		);

		$this->add_control(
			'custom_color',
			array(
				'label'     => esc_html__( 'Cor', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6366f1',
				'condition' => array(
					'color_scheme' => 'custom',
				),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Tamanho do Ícone', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 48,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-share__btn i' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_size',
			array(
				'label'      => esc_html__( 'Tamanho do Botão', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 32,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 44,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-share__btn--icon-only' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'style' => 'icons',
				),
			)
		);

		$this->add_control(
			'button_gap',
			array(
				'label'      => esc_html__( 'Espaçamento', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 8,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-share__list' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-share__btn' => 'border-radius: {{SIZE}}{{UNIT}};',
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

		$networks  = $settings['networks'] ?? array();
		$style     = $settings['style'] ?? 'icons';
		$alignment = $settings['alignment'] ?? 'left';

		// Get share data.
		$share_url   = $settings['share_url'] ?: get_permalink();
		$share_title = $settings['share_title'] ?: get_the_title();
		$share_desc  = $settings['share_description'] ?? '';

		$encoded_url   = rawurlencode( $share_url );
		$encoded_title = rawurlencode( $share_title );
		$encoded_desc  = rawurlencode( $share_desc );

		// Network configurations.
		$network_config = array(
			'facebook'  => array(
				'icon'  => 'ri-facebook-fill',
				'label' => 'Facebook',
				'color' => '#1877F2',
				'url'   => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
			),
			'twitter'   => array(
				'icon'  => 'ri-twitter-x-fill',
				'label' => 'Twitter',
				'color' => '#000000',
				'url'   => "https://twitter.com/intent/tweet?url={$encoded_url}&text={$encoded_title}",
			),
			'whatsapp'  => array(
				'icon'  => 'ri-whatsapp-fill',
				'label' => 'WhatsApp',
				'color' => '#25D366',
				'url'   => "https://api.whatsapp.com/send?text={$encoded_title}%20{$encoded_url}",
			),
			'telegram'  => array(
				'icon'  => 'ri-telegram-fill',
				'label' => 'Telegram',
				'color' => '#0088cc',
				'url'   => "https://t.me/share/url?url={$encoded_url}&text={$encoded_title}",
			),
			'linkedin'  => array(
				'icon'  => 'ri-linkedin-fill',
				'label' => 'LinkedIn',
				'color' => '#0A66C2',
				'url'   => "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}",
			),
			'pinterest' => array(
				'icon'  => 'ri-pinterest-fill',
				'label' => 'Pinterest',
				'color' => '#E60023',
				'url'   => "https://pinterest.com/pin/create/button/?url={$encoded_url}&description={$encoded_title}",
			),
			'email'     => array(
				'icon'  => 'ri-mail-fill',
				'label' => 'Email',
				'color' => '#6366f1',
				'url'   => "mailto:?subject={$encoded_title}&body={$encoded_desc}%20{$encoded_url}",
			),
			'copy'      => array(
				'icon'  => 'ri-file-copy-line',
				'label' => __( 'Copiar', 'apollo-social' ),
				'color' => '#64748b',
				'url'   => '#',
			),
		);

		$wrapper_classes = array(
			'apollo-share',
			"apollo-share--{$style}",
			"apollo-share--align-{$alignment}",
		);

		if ( ( $settings['color_scheme'] ?? 'official' ) !== 'official' ) {
			$wrapper_classes[] = "apollo-share--{$settings['color_scheme']}";
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			data-url="<?php echo esc_attr( $share_url ); ?>"
			data-title="<?php echo esc_attr( $share_title ); ?>">

			<?php if ( $this->get_toggle_value( $settings, 'label' ) ) : ?>
				<span class="apollo-share__label">
					<i class="ri-share-line"></i>
					<?php esc_html_e( 'Compartilhar', 'apollo-social' ); ?>
				</span>
			<?php endif; ?>

			<ul class="apollo-share__list">
				<?php
				foreach ( $networks as $network ) :
					$network_key = $network['network'] ?? '';
					if ( ! isset( $network_config[ $network_key ] ) ) {
						continue;
					}

					$config       = $network_config[ $network_key ];
					$custom_label = $network['label'] ?? '';
					$custom_color = $network['custom_color'] ?? '';
					$color        = $custom_color ?: $config['color'];

					$btn_classes = array(
						'apollo-share__btn',
						"apollo-share__btn--{$network_key}",
					);

					if ( $style === 'icons' ) {
						$btn_classes[] = 'apollo-share__btn--icon-only';
					}

					$style_attr = '';
					if ( ( $settings['color_scheme'] ?? 'official' ) === 'official' || $custom_color ) {
						$style_attr = "background-color: {$color};";
					} elseif ( ( $settings['color_scheme'] ?? 'official' ) === 'custom' ) {
						$style_attr = "background-color: {$settings['custom_color']};";
					}
					?>
					<li class="apollo-share__item">
						<a href="<?php echo esc_url( $config['url'] ); ?>"
							class="<?php echo esc_attr( implode( ' ', $btn_classes ) ); ?>"
							<?php
							if ( $style_attr ) :
								?>
								style="<?php echo esc_attr( $style_attr ); ?>"<?php endif; ?>
							<?php if ( $network_key === 'copy' ) : ?>
								data-action="copy"
							<?php else : ?>
								target="_blank" rel="noopener noreferrer"
							<?php endif; ?>
							title="<?php echo esc_attr( $custom_label ?: $config['label'] ); ?>">
							<i class="<?php echo esc_attr( $config['icon'] ); ?>"></i>
							<?php if ( $style !== 'icons' ) : ?>
								<span class="apollo-share__btn-text">
									<?php echo esc_html( $custom_label ?: $config['label'] ); ?>
								</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $this->get_toggle_value( $settings, 'count' ) ) : ?>
				<?php
				$share_count = (int) get_post_meta( get_the_ID(), '_apollo_share_count', true );
				?>
				<span class="apollo-share__count">
					<?php
					printf(
						esc_html( _n( '%s compartilhamento', '%s compartilhamentos', $share_count, 'apollo-social' ) ),
						number_format_i18n( $share_count )
					);
					?>
				</span>
			<?php endif; ?>
		</div>

		<script>
		(function() {
			const wrapper = document.querySelector('[data-url="<?php echo esc_js( $share_url ); ?>"]');
			if (!wrapper) return;

			const copyBtn = wrapper.querySelector('[data-action="copy"]');
			if (copyBtn) {
				copyBtn.addEventListener('click', function(e) {
					e.preventDefault();
					navigator.clipboard.writeText('<?php echo esc_js( $share_url ); ?>').then(() => {
						const icon = this.querySelector('i');
						icon.className = 'ri-check-line';
						setTimeout(() => {
							icon.className = 'ri-file-copy-line';
						}, 2000);
					});
				});
			}
		})();
		</script>
		<?php
	}
}
