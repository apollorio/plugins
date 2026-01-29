<?php
/**
 * Apollo Home Widgets Loader
 *
 * Registers all Elementor widgets for Apollo Home page and standardizes
 * the Event Card template across all Apollo plugins.
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

/**
 * Class Apollo_Home_Widgets_Loader
 *
 * Loads and registers all Apollo Home Elementor widgets.
 */
class Apollo_Home_Widgets_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var Apollo_Home_Widgets_Loader|null
	 */
	private static ?Apollo_Home_Widgets_Loader $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Apollo_Home_Widgets_Loader
	 */
	public static function get_instance(): Apollo_Home_Widgets_Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Wait for Elementor to be ready.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10 );

		// Register home shortcodes as fallback.
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Initialize the loader.
	 *
	 * @return void
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ): void {
		// Bail if Elementor not loaded.
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		$widgets_dir = APOLLO_CORE_PLUGIN_DIR . 'includes/widgets/';

		// List of widget files and class names.
		$widgets = array(
			'class-apollo-events-grid-widget.php'      => 'Apollo_Events_Grid_Widget',
			'class-apollo-home-hero-widget.php'        => 'Apollo_Home_Hero_Widget',
			'class-apollo-classifieds-grid-widget.php' => 'Apollo_Classifieds_Grid_Widget',
			'class-apollo-home-hub-widget.php'         => 'Apollo_Home_Hub_Widget',
			'class-apollo-home-ferramentas-widget.php' => 'Apollo_Home_Ferramentas_Widget',
			'class-apollo-home-manifesto-widget.php'   => 'Apollo_Home_Manifesto_Widget',
		);

		foreach ( $widgets as $file => $class ) {
			$filepath = $widgets_dir . $file;
			if ( file_exists( $filepath ) ) {
				require_once $filepath;
				if ( class_exists( $class ) ) {
					$widgets_manager->register( new $class() );
				}
			}
		}
	}

	/**
	 * Register fallback shortcodes for non-Elementor viewing.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_home_hero', array( $this, 'shortcode_hero' ) );
		add_shortcode( 'apollo_home_manifesto', array( $this, 'shortcode_manifesto' ) );
		add_shortcode( 'apollo_home_events', array( $this, 'shortcode_events' ) );
		add_shortcode( 'apollo_home_classifieds', array( $this, 'shortcode_classifieds' ) );
		add_shortcode( 'apollo_home_hub', array( $this, 'shortcode_hub' ) );
		add_shortcode( 'apollo_home_ferramentas', array( $this, 'shortcode_ferramentas' ) );

		// Standard Event Card shortcode.
		add_shortcode( 'apollo_event_card', array( $this, 'shortcode_event_card' ) );
	}

	/**
	 * Hero shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_hero( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'title'       => __( 'Não apenas veja.', 'apollo-core' ),
				'subtitle'    => __( 'Venha viver junto!', 'apollo-core' ),
				'description' => __( 'Sua nova ferramenta de cultura digital e de cadeia produtiva da cultura do Rio de Janeiro.', 'apollo-core' ),
				'cta_text'    => __( 'Explorar', 'apollo-core' ),
				'cta_link'    => '#events',
			),
			$atts
		);

		ob_start();
		?>
		<section class="a-hero-aprio-hero">
			<h1 class="a-hero-aprio-hero-title">
				<?php echo esc_html( $atts['title'] ); ?><br>
				<span class="vetxt"><?php echo esc_html( $atts['subtitle'] ); ?></span>
			</h1>
			<p class="a-hero-aprio-hero-text"><?php echo esc_html( $atts['description'] ); ?></p>
			<a href="<?php echo esc_url( $atts['cta_link'] ); ?>" class="a-hero-aprio-hero-btn"><?php echo esc_html( $atts['cta_text'] ); ?></a>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Manifesto shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_manifesto( array $atts = array() ): string {
		ob_start();
		?>
		<section id="manifesto" class="manifesto container">
			<div class="manifesto-grid">
				<div>
					<h3 class="section-label"><?php esc_html_e( 'A Missão', 'apollo-core' ); ?></h3>
				</div>
				<div>
					<p class="manifesto-text">
						<?php esc_html_e( 'O Apollo::rio é um projeto estruturante e territorial. Atuamos como a ferramenta de cultura digital do Rio de Janeiro, orientados à economia criativa e à difusão do acesso.', 'apollo-core' ); ?>
					</p>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Events shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_events( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit' => 4,
			),
			$atts
		);

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => (int) $atts['limit'],
				'post_status'    => 'publish',
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => gmdate( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);

		ob_start();
		echo '<section id="events" class="events container"><div class="events-grid">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->render_official_event_card( get_the_ID() );
			}
		} else {
			echo '<p>' . esc_html__( 'Nenhum evento encontrado.', 'apollo-core' ) . '</p>';
		}

		echo '</div></section>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Classifieds shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_classifieds( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit' => 4,
			),
			$atts
		);

		$items = get_posts(
			array(
				'post_type'      => 'apollo_classified',
				'posts_per_page' => (int) $atts['limit'],
				'post_status'    => 'publish',
			)
		);

		ob_start();
		echo '<section class="classifieds container"><div class="accommodations-grid">';

		foreach ( $items as $item ) {
			apollo_classified_card( $item->ID, array( 'context' => 'grid' ) );
		}

		echo '</div></section>';

		return ob_get_clean();
	}

	/**
	 * Hub shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_hub( array $atts = array() ): string {
		ob_start();
		?>
		<section id="hub" class="hub-section">
			<div class="container">
				<div class="hub-content">
					<h2>HUB::rio</h2>
					<p class="hub-description"><?php esc_html_e( 'Uma página simples para todos os seus links.', 'apollo-core' ); ?></p>
					<?php if ( ! is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="hub-cta"><?php esc_html_e( 'Criar minha conta', 'apollo-core' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ferramentas shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_ferramentas( array $atts = array() ): string {
		ob_start();
		?>
		<section id="roster" class="tools-section container">
			<h2>DJ Global Roster</h2>
			<p><?php esc_html_e( 'Conectando sons locais a plataformas globais.', 'apollo-core' ); ?></p>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Single Event Card shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_event_card( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$event_id = absint( $atts['id'] );
		if ( ! $event_id || get_post_type( $event_id ) !== 'event_listing' ) {
			return '';
		}

		ob_start();
		$this->render_official_event_card( $event_id );
		return ob_get_clean();
	}

	/**
	 * Render OFFICIAL Event Card template.
	 *
	 * This is the STANDARD event card for ALL Apollo plugins.
	 * All Apollo plugins MUST use this method for event cards.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	public function render_official_event_card( int $event_id ): void {
		// Delegate to the widget's static method for consistency.
		if ( class_exists( 'Apollo_Events_Grid_Widget' ) && method_exists( 'Apollo_Events_Grid_Widget', 'render_official_event_card' ) ) {
			\Apollo_Events_Grid_Widget::render_official_event_card( $event_id );
			return;
		}

		// Fallback implementation.
		$start_date  = get_post_meta( $event_id, '_event_start_date', true ) ?: '';
		$location    = get_post_meta( $event_id, '_event_location', true ) ?: '';
		$banner      = get_post_meta( $event_id, '_event_banner', true );
		$tickets_url = get_post_meta( $event_id, '_tickets_ext', true );

		$day   = $start_date ? date_i18n( 'd', strtotime( $start_date ) ) : '';
		$month = $start_date ? date_i18n( 'M', strtotime( $start_date ) ) : '';

		$tags    = wp_get_post_terms( $event_id, 'event_listing_tag' );
		$img_url = $banner ?: get_the_post_thumbnail_url( $event_id, 'medium' );
		?>
		<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="a-eve-card" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<div class="a-eve-date">
				<span class="a-eve-date-day"><?php echo esc_html( $day ); ?></span>
				<span class="a-eve-date-month"><?php echo esc_html( $month ); ?></span>
			</div>
			<div class="a-eve-media">
				<?php if ( $img_url ) : ?>
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>" loading="lazy">
				<?php endif; ?>
				<div class="a-eve-tags">
					<?php
					if ( $tags && ! is_wp_error( $tags ) ) {
						foreach ( array_slice( $tags, 0, 3 ) as $tag ) {
							echo '<span class="a-eve-tag">' . esc_html( $tag->name ) . '</span>';
						}
					}
					?>
				</div>
			</div>
			<div class="a-eve-content">
				<h2 class="a-eve-title"><?php echo esc_html( get_the_title( $event_id ) ); ?></h2>
				<p class="a-eve-meta">
					<i class="ri-map-pin-2-line"></i>
					<span><?php echo esc_html( $location ); ?></span>
				</p>
				<?php if ( $tickets_url ) : ?>
					<p class="a-eve-cta">
						<span class="a-eve-cta-link"><?php esc_html_e( 'Comprar ingresso', 'apollo-core' ); ?></span>
					</p>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}
}

// Initialize.
add_action( 'plugins_loaded', array( 'Apollo_Home_Widgets_Loader', 'init' ), 15 );

/**
 * Global function to render the OFFICIAL Event Card.
 *
 * Use this function anywhere in Apollo plugins to render an event card.
 *
 * @param int $event_id Event post ID.
 * @return void
 */
function apollo_render_official_event_card( int $event_id ): void {
	Apollo_Home_Widgets_Loader::get_instance()->render_official_event_card( $event_id );
}
