<?php
/**
 * Apollo Home Hub Elementor Widget
 *
 * Displays the HUB::rio section with DJ profile card preview.
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
 * Class Apollo_Home_Hub_Widget
 *
 * HUB section widget.
 */
class Apollo_Home_Hub_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-home-hub';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Apollo HUB Section', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-person';
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
		return array( 'apollo', 'hub', 'profile', 'links', 'dj' );
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
			'section_title',
			array(
				'label'   => esc_html__( 'Título da Seção', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'HUB::rio',
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Descrição', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Uma página simples para todos os seus links. O Apollo é a mão extra que apoia toda a indústria, centralizando sua presença digital em um único ponto de contato.', 'apollo-core' ),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'   => esc_html__( 'Texto do CTA', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Criar minha conta', 'apollo-core' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'   => esc_html__( 'Link do CTA', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array(
					'url' => '#',
				),
			)
		);

		$this->end_controls_section();

		// Preview Card Section.
		$this->start_controls_section(
			'preview_section',
			array(
				'label' => esc_html__( 'Card de Preview', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'preview_type',
			array(
				'label'   => esc_html__( 'Tipo', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'DJ & Producer',
			)
		);

		$this->add_control(
			'preview_name',
			array(
				'label'   => esc_html__( 'Nome', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Discarada',
			)
		);

		$this->add_control(
			'preview_handle',
			array(
				'label'   => esc_html__( 'Handle/Username', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '@anaclara_rio',
			)
		);

		$this->add_control(
			'preview_avatar',
			array(
				'label'   => esc_html__( 'Avatar', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array(
					'url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop',
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
		$settings   = $this->get_settings_for_display();
		$avatar_url = $settings['preview_avatar']['url'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop';
		$cta_url    = $settings['cta_link']['url'] ?? '#';
		?>
		<section id="hub" class="hub-section">
			<div class="hub-bg-circle"></div>
			<div class="container">
				<div class="hub-grid">
					<div class="hub-card-wrapper reveal-up">
						<div class="hub-card" id="hubCard">
							<div class="hub-profile">
								<div class="hub-avatar">
									<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Avatar', 'apollo-core' ); ?>">
								</div>
							</div>
							<div class="hub-content-card">
								<p class="hub-artist">
									<span class="hub-card-sup-title"><?php echo esc_html( $settings['preview_type'] ); ?></span>
								</p>
								<h3 class="hub-card-title"><?php echo esc_html( $settings['preview_name'] ); ?></h3>
								<p class="hub-handle-meta">
									<i class="ph-bold ph-at"></i>
									<span><?php echo esc_html( $settings['preview_handle'] ); ?></span>
								</p>
							</div>
							<div class="hub-links">
								<div class="hub-link hub-link-primary">
									<span><?php esc_html_e( 'SoundCloud Set', 'apollo-core' ); ?></span>
									<i class="ri-arrow-right-up-long-line"></i>
								</div>
								<div class="hub-link hub-link-secondary">
									<span><?php esc_html_e( 'Agenda 2025', 'apollo-core' ); ?></span>
									<i class="ri-arrow-right-up-long-line"></i>
								</div>
								<div class="hub-link hub-link-secondary">
									<span><?php esc_html_e( 'Press Kit', 'apollo-core' ); ?></span>
									<i class="ri-arrow-right-up-long-line"></i>
								</div>
							</div>
							<div class="hub-footer">
								<span><?php esc_html_e( 'Powered by Apollo', 'apollo-core' ); ?></span>
							</div>
						</div>
					</div>
					<div class="hub-content reveal-up delay-100">
						<div class="hub-status">
							<span class="hub-pulse"></span>
							<span class="hub-status-text"><?php esc_html_e( 'Ferramenta', 'apollo-core' ); ?></span>
						</div>
						<h2><?php echo esc_html( $settings['section_title'] ); ?></h2>
						<p class="hub-description"><?php echo esc_html( $settings['description'] ); ?></p>
						<a href="<?php echo esc_url( $cta_url ); ?>" class="hub-cta smooth-transition">
							<?php echo esc_html( $settings['cta_text'] ); ?>
							<i class="ri-arrow-right-up-long-line"></i>
						</a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
