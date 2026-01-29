<?php
/**
 * Apollo Classifieds Grid Elementor Widget
 *
 * Displays classifieds/listings in various layouts.
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
 * Class Apollo_Classifieds_Grid_Widget
 *
 * Elementor widget for displaying classifieds grid.
 */
class Apollo_Classifieds_Grid_Widget extends Apollo_Base_Widget {

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
		return 'apollo-classifieds-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Grid de Classificados', 'apollo-social' );
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
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'classifieds', 'classificados', 'listings', 'grid', 'anúncios', 'marketplace' );
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
				'label' => esc_html__( 'Layout', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'     => __( 'Grid', 'apollo-social' ),
					'list'     => __( 'Lista', 'apollo-social' ),
					'masonry'  => __( 'Masonry', 'apollo-social' ),
					'carousel' => __( 'Carousel', 'apollo-social' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => esc_html__( 'Colunas', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '3',
				'options'   => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'condition' => array(
					'layout!' => 'list',
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Quantidade', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 9,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->register_carousel_controls();

		$this->end_controls_section();

		// Query Section.
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Filtros', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Classified category.
		$this->register_taxonomy_control( 'classified_domain', __( 'Categorias', 'apollo-social' ) );

		// Classified type.
		$this->add_control(
			'ad_type',
			array(
				'label'    => esc_html__( 'Tipo de Anúncio', 'apollo-social' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => array(
					'sell'     => __( 'Venda', 'apollo-social' ),
					'buy'      => __( 'Compra', 'apollo-social' ),
					'rent'     => __( 'Aluguel', 'apollo-social' ),
					'service'  => __( 'Serviço', 'apollo-social' ),
					'job'      => __( 'Vaga', 'apollo-social' ),
					'exchange' => __( 'Troca', 'apollo-social' ),
					'free'     => __( 'Doação', 'apollo-social' ),
				),
			)
		);

		// Condition.
		$this->add_control(
			'condition',
			array(
				'label'    => esc_html__( 'Condição', 'apollo-social' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => array(
					'new'       => __( 'Novo', 'apollo-social' ),
					'like_new'  => __( 'Seminovo', 'apollo-social' ),
					'good'      => __( 'Bom Estado', 'apollo-social' ),
					'used'      => __( 'Usado', 'apollo-social' ),
					'for_parts' => __( 'Para Peças', 'apollo-social' ),
				),
			)
		);

		// Price range.
		$this->add_control(
			'min_price',
			array(
				'label' => esc_html__( 'Preço Mínimo', 'apollo-social' ),
				'type'  => \Elementor\Controls_Manager::NUMBER,
				'min'   => 0,
			)
		);

		$this->add_control(
			'max_price',
			array(
				'label' => esc_html__( 'Preço Máximo', 'apollo-social' ),
				'type'  => \Elementor\Controls_Manager::NUMBER,
				'min'   => 0,
			)
		);

		$this->add_control(
			'featured_only',
			array(
				'label'        => esc_html__( 'Apenas Destaques', 'apollo-social' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'user_id',
			array(
				'label'       => esc_html__( 'Anunciante', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'ID do usuário ou "current" para usuário atual.', 'apollo-social' ),
			)
		);

		$this->register_order_controls(
			array(
				'date'      => __( 'Data', 'apollo-social' ),
				'title'     => __( 'Título', 'apollo-social' ),
				'price'     => __( 'Preço', 'apollo-social' ),
				'views'     => __( 'Visualizações', 'apollo-social' ),
				'relevance' => __( 'Relevância', 'apollo-social' ),
			)
		);

		$this->end_controls_section();

		// Display Section.
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Elementos', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_display_toggles(
			array(
				'image'      => __( 'Mostrar Imagem', 'apollo-social' ),
				'title'      => __( 'Mostrar Título', 'apollo-social' ),
				'price'      => __( 'Mostrar Preço', 'apollo-social' ),
				'location'   => __( 'Mostrar Local', 'apollo-social' ),
				'date'       => __( 'Mostrar Data', 'apollo-social' ),
				'author'     => __( 'Mostrar Anunciante', 'apollo-social' ),
				'category'   => __( 'Mostrar Categoria', 'apollo-social' ),
				'badge'      => __( 'Mostrar Badge (Destaque/Novo)', 'apollo-social' ),
				'condition'  => __( 'Mostrar Condição', 'apollo-social' ),
				'views'      => __( 'Mostrar Visualizações', 'apollo-social' ),
				'favorites'  => __( 'Botão Favoritar', 'apollo-social' ),
				'pagination' => __( 'Mostrar Paginação', 'apollo-social' ),
			),
			array(
				'image'      => true,
				'title'      => true,
				'price'      => true,
				'location'   => true,
				'date'       => true,
				'author'     => false,
				'category'   => true,
				'badge'      => true,
				'condition'  => true,
				'views'      => false,
				'favorites'  => true,
				'pagination' => true,
			)
		);

		$this->end_controls_section();

		// Style Section - Cards.
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Cards', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-classified' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .apollo-classified',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-classified' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .apollo-classified',
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'      => esc_html__( 'Espaçamento', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-classifieds' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Image.
		$this->start_controls_section(
			'section_style_image',
			array(
				'label'     => esc_html__( 'Imagem', 'apollo-social' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_aspect_ratio',
			array(
				'label'     => esc_html__( 'Proporção', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '4/3',
				'options'   => array(
					'1/1'  => __( 'Quadrado (1:1)', 'apollo-social' ),
					'4/3'  => __( 'Padrão (4:3)', 'apollo-social' ),
					'16/9' => __( 'Widescreen (16:9)', 'apollo-social' ),
					'3/4'  => __( 'Retrato (3:4)', 'apollo-social' ),
					'auto' => __( 'Original', 'apollo-social' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .apollo-classified__image' => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-classified__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Typography.
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Tipografia', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Título', 'apollo-social' ),
				'selector' => '{{WRAPPER}} .apollo-classified__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Cor do Título', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-classified__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => esc_html__( 'Cor do Preço', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#10b981',
				'selectors' => array(
					'{{WRAPPER}} .apollo-classified__price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'label'    => esc_html__( 'Preço', 'apollo-social' ),
				'selector' => '{{WRAPPER}} .apollo-classified__price',
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

		$layout  = $settings['layout'] ?? 'grid';
		$columns = (int) ( $settings['columns'] ?? 3 );
		$limit   = (int) ( $settings['limit'] ?? 9 );

		// Build query.
		$args = array(
			'post_type'      => 'apollo_classified',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
		);

		// Order.
		$orderby = $settings['orderby'] ?? 'date';
		$order   = $settings['order'] ?? 'DESC';

		switch ( $orderby ) {
			case 'price':
				$args['meta_key'] = '_classified_price';
				$args['orderby']  = 'meta_value_num';
				break;
			case 'views':
				$args['meta_key'] = '_classified_views';
				$args['orderby']  = 'meta_value_num';
				break;
			case 'title':
				$args['orderby'] = 'title';
				break;
			default:
				$args['orderby'] = 'date';
		}
		$args['order'] = $order;

		// Meta query.
		$meta_query = array();

		// Ad type filter.
		if ( ! empty( $settings['ad_type'] ) ) {
			$meta_query[] = array(
				'key'     => '_classified_type',
				'value'   => $settings['ad_type'],
				'compare' => 'IN',
			);
		}

		// Condition filter.
		if ( ! empty( $settings['condition'] ) ) {
			$meta_query[] = array(
				'key'     => '_classified_condition',
				'value'   => $settings['condition'],
				'compare' => 'IN',
			);
		}

		// Price range.
		if ( ! empty( $settings['min_price'] ) ) {
			$meta_query[] = array(
				'key'     => '_classified_price',
				'value'   => (float) $settings['min_price'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( ! empty( $settings['max_price'] ) ) {
			$meta_query[] = array(
				'key'     => '_classified_price',
				'value'   => (float) $settings['max_price'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		// Featured only.
		if ( $this->get_toggle_value( $settings, 'featured_only' ) ) {
			$meta_query[] = array(
				'key'   => '_classified_featured',
				'value' => '1',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		// Taxonomy filter.
		$categories = $settings['classified_domain'] ?? array();
		if ( ! empty( $categories ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'classified_domain',
					'field'    => 'term_id',
					'terms'    => $categories,
				),
			);
		}

		// Author filter.
		$user_id = $settings['user_id'] ?? '';
		if ( $user_id === 'current' ) {
			$args['author'] = get_current_user_id();
		} elseif ( is_numeric( $user_id ) && $user_id > 0 ) {
			$args['author'] = (int) $user_id;
		}

		// Pagination.
		if ( $this->get_toggle_value( $settings, 'pagination' ) ) {
			$args['paged'] = max( 1, get_query_var( 'paged' ) );
		}

		$query = new WP_Query( $args );

		$wrapper_classes = array(
			'apollo-classifieds',
			"apollo-classifieds--{$layout}",
		);

		if ( $layout !== 'list' ) {
			$wrapper_classes[] = "apollo-classifieds--cols-{$columns}";
		}

		if ( $layout === 'carousel' ) {
			$wrapper_classes[] = 'swiper';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			<?php if ( $layout === 'carousel' ) : ?>
				data-autoplay="<?php echo $this->get_toggle_value( $settings, 'autoplay' ) ? 'true' : 'false'; ?>"
				data-autoplay-delay="<?php echo esc_attr( $settings['autoplay_delay']['size'] ?? 5000 ); ?>"
				data-loop="<?php echo $this->get_toggle_value( $settings, 'loop' ) ? 'true' : 'false'; ?>"
				data-slides="<?php echo esc_attr( $columns ); ?>"
			<?php endif; ?>>

			<?php if ( $layout === 'carousel' ) : ?>
				<div class="swiper-wrapper">
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					?>
					<?php $this->render_classified_item( $settings, $layout ); ?>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<?php $this->render_empty_state(); ?>
			<?php endif; ?>

			<?php if ( $layout === 'carousel' ) : ?>
				</div>
				<?php if ( $this->get_toggle_value( $settings, 'navigation' ) ) : ?>
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				<?php endif; ?>
				<?php if ( $this->get_toggle_value( $settings, 'pagination_dots' ) ) : ?>
					<div class="swiper-pagination"></div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $layout !== 'carousel' && $this->get_toggle_value( $settings, 'pagination' ) && $query->max_num_pages > 1 ) : ?>
				<div class="apollo-classifieds__pagination">
					<?php
					echo paginate_links(
						array(
							'total'   => $query->max_num_pages,
							'current' => max( 1, get_query_var( 'paged' ) ),
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single classified item.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $layout   Current layout.
	 * @return void
	 */
	private function render_classified_item( array $settings, string $layout ): void {
		$post_id   = get_the_ID();
		$price     = get_post_meta( $post_id, '_classified_price', true );
		$ad_type   = get_post_meta( $post_id, '_classified_type', true );
		$condition = get_post_meta( $post_id, '_classified_condition', true );
		$location  = get_post_meta( $post_id, '_classified_location', true );
		$views     = (int) get_post_meta( $post_id, '_classified_views', true );
		$featured  = get_post_meta( $post_id, '_classified_featured', true );
		$gallery   = get_post_meta( $post_id, '_classified_gallery', true );

		// Check if is new (less than 7 days).
		$is_new = ( current_time( 'timestamp' ) - get_the_time( 'U' ) ) < ( 7 * DAY_IN_SECONDS );

		// Categories.
		$categories = get_the_terms( $post_id, 'classified_domain' );

		// Condition labels.
		$condition_labels = array(
			'new'       => __( 'Novo', 'apollo-social' ),
			'like_new'  => __( 'Seminovo', 'apollo-social' ),
			'good'      => __( 'Bom Estado', 'apollo-social' ),
			'used'      => __( 'Usado', 'apollo-social' ),
			'for_parts' => __( 'Para Peças', 'apollo-social' ),
		);

		$item_classes = array(
			'apollo-classified',
			"apollo-classified--{$layout}",
		);

		if ( $featured ) {
			$item_classes[] = 'apollo-classified--featured';
		}

		if ( $layout === 'carousel' ) {
			$item_classes[] = 'swiper-slide';
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">

			<?php if ( $this->get_toggle_value( $settings, 'image' ) ) : ?>
				<div class="apollo-classified__image-wrapper">
					<a href="<?php the_permalink(); ?>" class="apollo-classified__image-link">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'apollo-classified__image' ) ); ?>
						<?php else : ?>
							<div class="apollo-classified__image apollo-classified__image--placeholder">
								<i class="ri-image-line"></i>
							</div>
						<?php endif; ?>
					</a>

					<?php if ( $this->get_toggle_value( $settings, 'badge' ) ) : ?>
						<div class="apollo-classified__badges">
							<?php if ( $featured ) : ?>
								<span class="apollo-classified__badge apollo-classified__badge--featured">
									<i class="ri-star-fill"></i>
									<?php esc_html_e( 'Destaque', 'apollo-social' ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $is_new ) : ?>
								<span class="apollo-classified__badge apollo-classified__badge--new">
									<?php esc_html_e( 'Novo', 'apollo-social' ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'favorites' ) ) : ?>
						<button class="apollo-classified__favorite" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<i class="ri-heart-line"></i>
						</button>
					<?php endif; ?>

					<?php if ( ! empty( $gallery ) && is_array( $gallery ) && count( $gallery ) > 1 ) : ?>
						<span class="apollo-classified__gallery-count">
							<i class="ri-camera-line"></i>
							<?php echo count( $gallery ); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="apollo-classified__content">
				<?php if ( $this->get_toggle_value( $settings, 'category' ) && $categories && ! is_wp_error( $categories ) ) : ?>
					<div class="apollo-classified__category">
						<?php echo esc_html( $categories[0]->name ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $this->get_toggle_value( $settings, 'title' ) ) : ?>
					<h3 class="apollo-classified__title">
						<a href="<?php the_permalink(); ?>">
							<?php the_title(); ?>
						</a>
					</h3>
				<?php endif; ?>

				<div class="apollo-classified__meta">
					<?php if ( $this->get_toggle_value( $settings, 'condition' ) && $condition ) : ?>
						<span class="apollo-classified__condition apollo-classified__condition--<?php echo esc_attr( $condition ); ?>">
							<?php echo esc_html( $condition_labels[ $condition ] ?? $condition ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'location' ) && $location ) : ?>
						<span class="apollo-classified__location">
							<i class="ri-map-pin-line"></i>
							<?php echo esc_html( $location ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="apollo-classified__footer">
					<?php if ( $this->get_toggle_value( $settings, 'price' ) ) : ?>
						<span class="apollo-classified__price">
							<?php if ( $price ) : ?>
								R$ <?php echo esc_html( number_format( (float) $price, 2, ',', '.' ) ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Consultar', 'apollo-social' ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>

					<div class="apollo-classified__footer-meta">
						<?php if ( $this->get_toggle_value( $settings, 'date' ) ) : ?>
							<span class="apollo-classified__date">
								<i class="ri-time-line"></i>
								<?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $this->get_toggle_value( $settings, 'views' ) ) : ?>
							<span class="apollo-classified__views">
								<i class="ri-eye-line"></i>
								<?php echo esc_html( number_format_i18n( $views ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $this->get_toggle_value( $settings, 'author' ) ) : ?>
					<div class="apollo-classified__author">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
						<span><?php the_author(); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render empty state.
	 *
	 * @return void
	 */
	private function render_empty_state(): void {
		?>
		<div class="apollo-classifieds__empty">
			<i class="ri-store-2-line"></i>
			<h3><?php esc_html_e( 'Nenhum anúncio encontrado', 'apollo-social' ); ?></h3>
			<p><?php esc_html_e( 'Não há classificados com os filtros selecionados.', 'apollo-social' ); ?></p>
		</div>
		<?php
	}
}
