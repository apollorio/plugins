<?php
/**
 * Apollo Events Grid Elementor Widget
 *
 * Displays event_listing posts in a grid layout using the OFFICIAL Event Card template.
 * This is the STANDARD event card for ALL Apollo plugins.
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
 * Class Apollo_Events_Grid_Widget
 *
 * Elementor widget for displaying events grid with OFFICIAL card template.
 */
class Apollo_Events_Grid_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'apollo-events-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Apollo Events Grid', 'apollo-core' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return array( 'apollo', 'apollo-events' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'events', 'grid', 'eventos', 'event_listing' );
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
			'posts_per_page',
			array(
				'label'   => esc_html__( 'Quantidade de Eventos', 'apollo-core' ),
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
					'5' => '5',
					'6' => '6',
				),
				'selectors' => array(
					'{{WRAPPER}} .events-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_control(
			'show_featured_only',
			array(
				'label'        => esc_html__( 'Apenas Destaques', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => esc_html__( 'Sim', 'apollo-core' ),
				'label_off'    => esc_html__( 'Não', 'apollo-core' ),
				'return_value' => 'yes',
			)
		);

		// Category filter.
		$categories = get_terms(
			array(
				'taxonomy'   => 'event_listing_category',
				'hide_empty' => false,
			)
		);

		$category_options = array( '' => esc_html__( 'Todas', 'apollo-core' ) );
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$category_options[ $cat->slug ] = $cat->name;
			}
		}

		$this->add_control(
			'category',
			array(
				'label'   => esc_html__( 'Categoria', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $category_options,
			)
		);

		// Event sounds (genre) filter.
		$sounds = get_terms(
			array(
				'taxonomy'   => 'event_sounds',
				'hide_empty' => false,
			)
		);

		$sound_options = array( '' => esc_html__( 'Todos', 'apollo-core' ) );
		if ( ! is_wp_error( $sounds ) ) {
			foreach ( $sounds as $sound ) {
				$sound_options[ $sound->slug ] = $sound->name;
			}
		}

		$this->add_control(
			'sound',
			array(
				'label'   => esc_html__( 'Gênero Musical', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $sound_options,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Ordenar por', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'meta_value',
				'options' => array(
					'date'       => esc_html__( 'Data de publicação', 'apollo-core' ),
					'meta_value' => esc_html__( 'Data do evento', 'apollo-core' ),
					'title'      => esc_html__( 'Título', 'apollo-core' ),
					'rand'       => esc_html__( 'Aleatório', 'apollo-core' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Ordem', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => array(
					'ASC'  => esc_html__( 'Crescente', 'apollo-core' ),
					'DESC' => esc_html__( 'Decrescente', 'apollo-core' ),
				),
			)
		);

		$this->add_control(
			'upcoming_only',
			array(
				'label'        => esc_html__( 'Apenas Futuros', 'apollo-core' ),
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
				'label' => esc_html__( 'Estilo do Card', 'apollo-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'      => esc_html__( 'Espaçamento', 'apollo-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .events-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Borda do Card', 'apollo-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array(
					'size' => 16,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .a-eve-card' => 'border-radius: {{SIZE}}{{UNIT}};',
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
			'post_type'      => 'event_listing',
			'posts_per_page' => (int) $settings['posts_per_page'],
			'post_status'    => 'publish',
			'orderby'        => $settings['orderby'],
			'order'          => $settings['order'],
		);

		// Meta key for ordering by event date.
		if ( 'meta_value' === $settings['orderby'] ) {
			$args['meta_key'] = '_event_start_date';
			$args['orderby']  = 'meta_value';
		}

		// Filter by featured.
		if ( 'yes' === $settings['show_featured_only'] ) {
			$args['meta_query'][] = array(
				'key'     => '_event_featured',
				'value'   => '1',
				'compare' => '=',
			);
		}

		// Filter by category.
		if ( ! empty( $settings['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'slug',
				'terms'    => $settings['category'],
			);
		}

		// Filter by sound/genre.
		if ( ! empty( $settings['sound'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_sounds',
				'field'    => 'slug',
				'terms'    => $settings['sound'],
			);
		}

		// Filter upcoming events only.
		if ( 'yes' === $settings['upcoming_only'] ) {
			$today = gmdate( 'Y-m-d' );
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			echo '<p class="apollo-no-events">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-core' ) . '</p>';
			return;
		}

		// Render events grid using OFFICIAL Event Card template.
		echo '<div class="events-grid">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$this->render_official_event_card( get_the_ID() );
		}

		echo '</div>';

		wp_reset_postdata();
	}

	/**
	 * Render OFFICIAL Event Card template.
	 *
	 * This is the STANDARD event card for ALL Apollo plugins.
	 * Uses existing meta keys from event_listing CPT.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	public static function render_official_event_card( int $event_id ): void {
		// Get event meta using existing meta keys.
		$start_date  = get_post_meta( $event_id, '_event_start_date', true ) ?: '';
		$start_time  = get_post_meta( $event_id, '_event_start_time', true ) ?: '';
		$location    = get_post_meta( $event_id, '_event_location', true ) ?: '';
		$banner      = get_post_meta( $event_id, '_event_banner', true );
		$tickets_url = get_post_meta( $event_id, '_tickets_ext', true );
		$event_city  = get_post_meta( $event_id, '_event_city', true );
		$event_addr  = get_post_meta( $event_id, '_event_address', true );

		// Parse dates.
		$day   = $start_date ? date_i18n( 'd', strtotime( $start_date ) ) : '';
		$month = $start_date ? date_i18n( 'M', strtotime( $start_date ) ) : '';

		// Get event tags.
		$tags = wp_get_post_terms( $event_id, 'event_listing_tag' );

		// Get DJs display.
		$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
		$dj_names = '';
		if ( ! empty( $dj_ids ) ) {
			$dj_ids_array = is_array( $dj_ids ) ? $dj_ids : explode( ',', $dj_ids );
			$dj_names_array = array();
			foreach ( $dj_ids_array as $dj_id ) {
				$dj_id = absint( trim( $dj_id ) );
				if ( $dj_id ) {
					$dj_name = get_post_meta( $dj_id, '_dj_name', true ) ?: get_the_title( $dj_id );
					if ( $dj_name ) {
						$dj_names_array[] = $dj_name;
					}
				}
			}
			$dj_names = implode( ', ', array_slice( $dj_names_array, 0, 3 ) );
			if ( count( $dj_names_array ) > 3 ) {
				$dj_names .= ' +' . ( count( $dj_names_array ) - 3 );
			}
		}

		// Get image.
		$img_url = $banner ?: get_the_post_thumbnail_url( $event_id, 'medium' );

		// Render OFFICIAL card structure.
		?>
		<a
			href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"
			class="a-eve-card reveal-up"
			data-cpt="event_listing"
			data-event-id="<?php echo esc_attr( $event_id ); ?>"
		>
			<div class="a-eve-date">
				<span class="a-eve-date-day"><?php echo esc_html( $day ); ?></span>
				<span class="a-eve-date-month"><?php echo esc_html( $month ); ?></span>
			</div>
			<div class="a-eve-media">
				<?php if ( $img_url ) : ?>
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>" loading="lazy">
				<?php else : ?>
					<div class="a-eve-placeholder"><i class="ri-calendar-event-line"></i></div>
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
				<?php if ( $dj_names ) : ?>
					<p class="a-eve-meta">
						<i class="ri-sound-module-fill"></i>
						<span><?php echo esc_html( $dj_names ); ?></span>
					</p>
				<?php endif; ?>
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
