<?php
/**
 * Apollo Events Grid Elementor Widget
 *
 * Displays a grid/list/carousel of events with filtering options.
 *
 * @package Apollo_Events_Manager
 * @subpackage Elementor\Widgets
 * @since 2.0.0
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
 * Class Apollo_Events_Grid_Widget
 *
 * Elementor widget for displaying events grid.
 */
class Apollo_Events_Grid_Widget extends Apollo_Base_Widget {

	/**
	 * Widget category.
	 *
	 * @var string
	 */
	protected string $widget_category = 'apollo-events';

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
		return esc_html__( 'Grid de Eventos', 'apollo-events-manager' );
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
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'events', 'eventos', 'grid', 'lista', 'carrossel' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Layout Section.
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_layout_controls(
			array(
				'layouts'       => array(
					'grid'     => __( 'Grade', 'apollo-events-manager' ),
					'list'     => __( 'Lista', 'apollo-events-manager' ),
					'carousel' => __( 'Carrossel', 'apollo-events-manager' ),
				),
				'default_cols'  => 3,
				'default_limit' => 6,
			)
		);

		$this->register_carousel_controls();

		$this->end_controls_section();

		// Query Section.
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Consulta', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_taxonomy_control(
			'event_listing_category',
			__( 'Categorias', 'apollo-events-manager' )
		);

		$this->register_taxonomy_control(
			'event_listing_type',
			__( 'Tipos', 'apollo-events-manager' )
		);

		$this->register_taxonomy_control(
			'event_sounds',
			__( 'Estilos Musicais', 'apollo-events-manager' )
		);

		$this->register_taxonomy_control(
			'event_season',
			__( 'Temporada', 'apollo-events-manager' )
		);

		$this->add_control(
			'featured',
			array(
				'label'        => esc_html__( 'Apenas Destaques', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'upcoming_only',
			array(
				'label'        => esc_html__( 'Apenas Futuros', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->register_order_controls(
			array(
				'event_date' => __( 'Data do Evento', 'apollo-events-manager' ),
				'date'       => __( 'Data de Publicação', 'apollo-events-manager' ),
				'title'      => __( 'Título', 'apollo-events-manager' ),
				'rand'       => __( 'Aleatório', 'apollo-events-manager' ),
			)
		);

		$this->end_controls_section();

		// Display Section.
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Exibição', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_display_toggles(
			array(
				'image'      => __( 'Mostrar Imagem', 'apollo-events-manager' ),
				'date'       => __( 'Mostrar Data', 'apollo-events-manager' ),
				'time'       => __( 'Mostrar Horário', 'apollo-events-manager' ),
				'location'   => __( 'Mostrar Local', 'apollo-events-manager' ),
				'category'   => __( 'Mostrar Categoria', 'apollo-events-manager' ),
				'excerpt'    => __( 'Mostrar Resumo', 'apollo-events-manager' ),
				'price'      => __( 'Mostrar Preço', 'apollo-events-manager' ),
				'pagination' => __( 'Mostrar Paginação', 'apollo-events-manager' ),
			),
			array(
				'image'      => true,
				'date'       => true,
				'time'       => true,
				'location'   => true,
				'category'   => false,
				'excerpt'    => false,
				'price'      => false,
				'pagination' => false,
			)
		);

		$this->end_controls_section();

		// Style Section - Card.
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .apollo-event-card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-events-manager' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-event-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .apollo-event-card',
			)
		);

		$this->end_controls_section();

		// Style Section - Typography.
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Tipografia', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Título', 'apollo-events-manager' ),
				'selector' => '{{WRAPPER}} .apollo-event-card__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Cor do Título', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-card__title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => esc_html__( 'Cor dos Metadados', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-card__meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Image.
		$this->start_controls_section(
			'section_style_image',
			array(
				'label'     => esc_html__( 'Imagem', 'apollo-events-manager' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_aspect_ratio',
			array(
				'label'     => esc_html__( 'Proporção', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '16/9',
				'options'   => array(
					'1/1'  => '1:1 Quadrado',
					'4/3'  => '4:3',
					'16/9' => '16:9',
					'21/9' => '21:9 Ultrawide',
				),
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-card__image' => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_overlay',
			array(
				'label'     => esc_html__( 'Overlay', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-card__image::after' => 'background: {{VALUE}};',
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
		$query_args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => (int) ( $settings['limit'] ?? 6 ),
		);

		// Handle ordering.
		$orderby = $settings['orderby'] ?? 'event_date';
		$order   = strtoupper( $settings['order'] ?? 'asc' );

		if ( 'event_date' === $orderby ) {
			$query_args['meta_key'] = '_event_start_date';
			$query_args['orderby']  = 'meta_value';
			$query_args['order']    = $order;
		} elseif ( 'rand' === $orderby ) {
			$query_args['orderby'] = 'rand';
		} else {
			$query_args['orderby'] = $orderby;
			$query_args['order']   = $order;
		}

		// Build tax query.
		$tax_query = array();

		if ( ! empty( $settings['event_listing_category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'term_id',
				'terms'    => $settings['event_listing_category'],
			);
		}

		if ( ! empty( $settings['event_listing_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_listing_type',
				'field'    => 'term_id',
				'terms'    => $settings['event_listing_type'],
			);
		}

		if ( ! empty( $settings['event_sounds'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_sounds',
				'field'    => 'term_id',
				'terms'    => $settings['event_sounds'],
			);
		}

		if ( ! empty( $settings['event_season'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_season',
				'field'    => 'term_id',
				'terms'    => $settings['event_season'],
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation']   = 'AND';
			$query_args['tax_query'] = $tax_query;
		}

		// Build meta query.
		$meta_query = array();

		// Upcoming events only.
		if ( $this->get_toggle_value( $settings, 'upcoming_only' ) ) {
			$meta_query[] = array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		// Featured events only.
		if ( $this->get_toggle_value( $settings, 'featured' ) ) {
			$meta_query[] = array(
				'key'   => '_event_featured',
				'value' => '1',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation']   = 'AND';
			$query_args['meta_query'] = $meta_query;
		}

		// Run query.
		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			$this->render_placeholder(
				__( 'Nenhum evento encontrado.', 'apollo-events-manager' ),
				'ri-calendar-event-line'
			);
			return;
		}

		// Prepare template data.
		$layout  = $settings['layout'] ?? 'grid';
		$columns = $settings['columns'] ?? 3;

		$wrapper_classes = array(
			'apollo-events-grid',
			"apollo-events-grid--{$layout}",
			"apollo-events-grid--cols-{$columns}",
		);

		// Start output.
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
			data-carousel="true"
			data-autoplay="<?php echo esc_attr( $this->get_toggle_value( $settings, 'autoplay' ) ? 'true' : 'false' ); ?>"
			data-speed="<?php echo esc_attr( $settings['autoplay_speed'] ?? 5000 ); ?>"
			data-loop="<?php echo esc_attr( $this->get_toggle_value( $settings, 'loop' ) ? 'true' : 'false' ); ?>"
			<?php endif; ?>
		>
			<?php if ( 'carousel' === $layout ) : ?>
			<div class="swiper">
				<div class="swiper-wrapper">
			<?php endif; ?>

			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$event_id = get_the_ID();

				// Get event meta.
				$start_date  = get_post_meta( $event_id, '_event_start_date', true );
				$start_time  = get_post_meta( $event_id, '_event_start_time', true );
				$location    = get_post_meta( $event_id, '_event_location', true );
				$venue       = get_post_meta( $event_id, '_event_venue', true );
				$price       = get_post_meta( $event_id, '_event_price', true );
				$is_featured = get_post_meta( $event_id, '_event_featured', true );

				// Categories.
				$categories    = get_the_terms( $event_id, 'event_listing_category' );
				$category_name = '';
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$category_name = $categories[0]->name;
				}

				// Card classes.
				$card_classes = array( 'apollo-event-card' );
				if ( $is_featured ) {
					$card_classes[] = 'apollo-event-card--featured';
				}

				if ( 'carousel' === $layout ) {
					$card_classes[] = 'swiper-slide';
				}
				?>
				<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" data-event-id="<?php echo absint( $event_id ); ?>">
					<?php if ( $this->get_toggle_value( $settings, 'image' ) ) : ?>
						<div class="apollo-event-card__image">
							<a href="<?php the_permalink(); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large' ); ?>
								<?php else : ?>
									<div class="apollo-event-card__no-image">
										<i class="ri-calendar-event-line"></i>
									</div>
								<?php endif; ?>
							</a>
							<?php if ( $is_featured ) : ?>
								<span class="apollo-event-card__badge">
									<i class="ri-star-fill"></i>
									<?php esc_html_e( 'Destaque', 'apollo-events-manager' ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $this->get_toggle_value( $settings, 'category' ) && $category_name ) : ?>
								<span class="apollo-event-card__category"><?php echo esc_html( $category_name ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="apollo-event-card__content">
						<?php if ( $this->get_toggle_value( $settings, 'date' ) && $start_date ) : ?>
							<div class="apollo-event-card__date-badge">
								<span class="apollo-event-card__day">
									<?php echo esc_html( date_i18n( 'd', strtotime( $start_date ) ) ); ?>
								</span>
								<span class="apollo-event-card__month">
									<?php echo esc_html( date_i18n( 'M', strtotime( $start_date ) ) ); ?>
								</span>
							</div>
						<?php endif; ?>

						<div class="apollo-event-card__info">
							<h3 class="apollo-event-card__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<div class="apollo-event-card__meta">
								<?php if ( $this->get_toggle_value( $settings, 'time' ) && $start_time ) : ?>
									<span class="apollo-event-card__time">
										<i class="ri-time-line"></i>
										<?php echo esc_html( $start_time ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $this->get_toggle_value( $settings, 'location' ) && ( $location || $venue ) ) : ?>
									<span class="apollo-event-card__location">
										<i class="ri-map-pin-line"></i>
										<?php echo esc_html( $venue ?: $location ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $this->get_toggle_value( $settings, 'price' ) && $price ) : ?>
									<span class="apollo-event-card__price">
										<i class="ri-ticket-line"></i>
										R$ <?php echo esc_html( number_format( (float) $price, 2, ',', '.' ) ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $this->get_toggle_value( $settings, 'excerpt' ) ) : ?>
								<p class="apollo-event-card__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endwhile; ?>

			<?php if ( 'carousel' === $layout ) : ?>
				</div>
				<?php if ( $this->get_toggle_value( $settings, 'navigation' ) ) : ?>
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				<?php endif; ?>
				<?php if ( $this->get_toggle_value( $settings, 'pagination' ) ) : ?>
					<div class="swiper-pagination"></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( 'carousel' !== $layout && $this->get_toggle_value( $settings, 'pagination' ) && $query->max_num_pages > 1 ) : ?>
			<nav class="apollo-events-pagination">
				<?php
				echo paginate_links(
					array(
						'total'     => $query->max_num_pages,
						'current'   => max( 1, get_query_var( 'paged' ) ),
						'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
						'next_text' => '<i class="ri-arrow-right-s-line"></i>',
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php
		wp_reset_postdata();
	}

	/**
	 * Render widget output in the editor.
	 *
	 * @return void
	 */
	protected function content_template(): void {
		?>
		<#
		var layout = settings.layout || 'grid';
		var columns = settings.columns || 3;
		#>
		<div class="apollo-events-grid apollo-events-grid--{{ layout }} apollo-events-grid--cols-{{ columns }}">
			<# for ( var i = 0; i < 3; i++ ) { #>
			<div class="apollo-event-card">
				<# if ( settings.show_image === 'yes' ) { #>
				<div class="apollo-event-card__image" style="aspect-ratio: {{ settings.image_aspect_ratio || '16/9' }}; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
					<i class="ri-calendar-event-line" style="font-size: 2rem; color: #94a3b8;"></i>
				</div>
				<# } #>
				<div class="apollo-event-card__content" style="padding: 1rem;">
					<# if ( settings.show_date === 'yes' ) { #>
					<div class="apollo-event-card__date-badge" style="display: inline-flex; flex-direction: column; align-items: center; background: #6366f1; color: #fff; padding: 0.5rem; border-radius: 8px; margin-right: 0.75rem; float: left;">
						<span style="font-size: 1.25rem; font-weight: 700;">{{ 15 + i }}</span>
						<span style="font-size: 0.7rem; text-transform: uppercase;">Jan</span>
					</div>
					<# } #>
					<h3 class="apollo-event-card__title" style="margin: 0 0 0.5rem; font-size: 1rem;">
						<a href="#" style="color: inherit; text-decoration: none;">Evento Exemplo {{ i + 1 }}</a>
					</h3>
					<div class="apollo-event-card__meta" style="font-size: 0.8125rem; color: #64748b;">
						<# if ( settings.show_time === 'yes' ) { #>
						<span><i class="ri-time-line"></i> 22:00</span>
						<# } #>
						<# if ( settings.show_location === 'yes' ) { #>
						<span style="margin-left: 0.5rem;"><i class="ri-map-pin-line"></i> Local Exemplo</span>
						<# } #>
					</div>
				</div>
			</div>
			<# } #>
		</div>
		<?php
	}
}
