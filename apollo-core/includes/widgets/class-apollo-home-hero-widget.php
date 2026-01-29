<?php
/**
 * Apollo Home Hero Elementor Widget
 *
 * Full-screen hero section with video background for Apollo Home page.
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
 * Class Apollo_Home_Hero_Widget
 *
 * Hero section widget with video background.
 */
class Apollo_Home_Hero_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-home-hero';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Apollo Hero Section', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-banner';
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
		return array( 'apollo', 'hero', 'banner', 'video', 'home' );
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
			'logo_icon',
			array(
				'label'   => esc_html__( 'Ícone Logo', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'ri-flashlight-line',
					'library' => 'remix-icons',
				),
			)
		);

		$this->add_control(
			'brand_text',
			array(
				'label'   => esc_html__( 'Texto da Marca', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'apollo::rio',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Título', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Não apenas veja.', 'apollo-core' ),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'   => esc_html__( 'Subtítulo', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Venha viver junto!', 'apollo-core' ),
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Descrição', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Sua nova ferramenta de cultura digital e de cadeia produtiva da cultura do Rio de Janeiro.', 'apollo-core' ),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'   => esc_html__( 'Texto do Botão', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Explorar', 'apollo-core' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'   => esc_html__( 'Link do Botão', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array(
					'url' => '#events',
				),
			)
		);

		$this->add_control(
			'login_text',
			array(
				'label'   => esc_html__( 'Texto Login', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Entrar', 'apollo-core' ),
			)
		);

		$this->end_controls_section();

		// Video Section.
		$this->start_controls_section(
			'video_section',
			array(
				'label' => esc_html__( 'Vídeo de Fundo', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'video_webm',
			array(
				'label'       => esc_html__( 'Vídeo WEBM', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://assets.apollo.rio.br/vid/v2.webm',
				'default'     => array(
					'url' => 'https://assets.apollo.rio.br/vid/v2.webm',
				),
			)
		);

		$this->add_control(
			'video_mp4',
			array(
				'label'       => esc_html__( 'Vídeo MP4', 'apollo-core' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://assets.apollo.rio.br/vid/v2.mp4',
				'default'     => array(
					'url' => 'https://assets.apollo.rio.br/vid/v2.mp4',
				),
			)
		);

		$this->add_control(
			'video_poster',
			array(
				'label' => esc_html__( 'Imagem de Fallback', 'apollo-core' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'overlay_opacity',
			array(
				'label'   => esc_html__( 'Opacidade do Overlay', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SLIDER,
				'default' => array(
					'size' => 0.5,
				),
				'range'   => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.1,
					),
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

		$video_webm   = $settings['video_webm']['url'] ?? 'https://assets.apollo.rio.br/vid/v2.webm';
		$video_mp4    = $settings['video_mp4']['url'] ?? 'https://assets.apollo.rio.br/vid/v2.mp4';
		$video_poster = $settings['video_poster']['url'] ?? '';
		$overlay      = $settings['overlay_opacity']['size'] ?? 0.5;
		$cta_url      = $settings['cta_link']['url'] ?? '#events';
		?>
<header class="a-hero-aprio-hero-header inverso">
	<!-- Garante que o CSS do hero está presente -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/css/home.css">
	<div class="a-hero-aprio-hero-brand">
		<i class="apollo"></i>
		<span class="txt-logo-hero"><?php echo esc_html( $settings['brand_text'] ); ?></span>
	</div>
	<?php if ( ! is_user_logged_in() ) : ?>
	<a href="<?php echo esc_url( wp_login_url() ); ?>"
		class="a-hero-aprio-hero-btn"><?php echo esc_html( $settings['login_text'] ); ?></a>
	<?php else : ?>
	<a href="<?php echo esc_url( home_url( '/painel/' ) ); ?>"
		class="a-hero-aprio-hero-btn"><?php esc_html_e( 'Painel', 'apollo-core' ); ?></a>
	<?php endif; ?>
</header>

<section class="a-hero-aprio-hero" style="--overlay-opacity: <?php echo esc_attr( $overlay ); ?>;">
	<video class="a-hero-hero-video" autoplay muted loop playsinline preload="auto"
		poster="<?php echo esc_url( $video_poster ); ?>">
		<source src="<?php echo esc_url( $video_webm ); ?>" type="video/webm">
		<source src="<?php echo esc_url( $video_mp4 ); ?>" type="video/mp4">
	</video>
	<h1 class="a-hero-aprio-hero-title reveal-up">
		<?php echo esc_html( $settings['title'] ); ?><br>
		<span class="vetxt"><?php echo esc_html( $settings['subtitle'] ); ?></span>
	</h1>
	<p class="a-hero-aprio-hero-text reveal-up" style="transition-delay: 0.1s;">
		<?php echo esc_html( $settings['description'] ); ?>
	</p>
	<div class="reveal-up" style="transition-delay: 0.2s;">
		<a href="<?php echo esc_url( $cta_url ); ?>"
			class="a-hero-aprio-hero-btn"><?php echo esc_html( $settings['cta_text'] ); ?></a>
	</div>
</section>
<?php
	}
}