<?php
/**
 * Apollo Social Feed Elementor Widget
 *
 * Displays social activity feed.
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
 * Class Apollo_Social_Feed_Widget
 *
 * Elementor widget for displaying social feed.
 */
class Apollo_Social_Feed_Widget extends Apollo_Base_Widget {

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
		return 'apollo-social-feed';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Feed Social', 'apollo-social' );
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
		return array( 'apollo', 'social', 'feed', 'timeline', 'posts', 'activity' );
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
				'default' => 'timeline',
				'options' => array(
					'timeline' => __( 'Timeline', 'apollo-social' ),
					'grid'     => __( 'Grid', 'apollo-social' ),
					'compact'  => __( 'Compacto', 'apollo-social' ),
					'masonry'  => __( 'Masonry', 'apollo-social' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => esc_html__( 'Colunas', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '2',
				'options'   => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'condition' => array(
					'layout' => array( 'grid', 'masonry' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Quantidade', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->end_controls_section();

		// Filter Section.
		$this->start_controls_section(
			'section_filter',
			array(
				'label' => esc_html__( 'Filtros', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'feed_type',
			array(
				'label'   => esc_html__( 'Tipo de Feed', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'global',
				'options' => array(
					'global'    => __( 'Global (Todos)', 'apollo-social' ),
					'following' => __( 'Seguindo', 'apollo-social' ),
					'user'      => __( 'Usuário Específico', 'apollo-social' ),
					'group'     => __( 'Grupo', 'apollo-social' ),
				),
			)
		);

		// Get users for select.
		$users = get_users(
			array(
				'number' => 50,
				'fields' => array( 'ID', 'display_name' ),
			)
		);

		$user_options = array( '' => __( 'Selecione...', 'apollo-social' ) );
		foreach ( $users as $user ) {
			$user_options[ $user->ID ] = $user->display_name;
		}

		$this->add_control(
			'user_id',
			array(
				'label'     => esc_html__( 'Usuário', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::SELECT2,
				'options'   => $user_options,
				'condition' => array(
					'feed_type' => 'user',
				),
			)
		);

		$this->add_control(
			'activity_types',
			array(
				'label'    => esc_html__( 'Tipos de Atividade', 'apollo-social' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => array(
					'post'    => __( 'Posts', 'apollo-social' ),
					'photo'   => __( 'Fotos', 'apollo-social' ),
					'video'   => __( 'Vídeos', 'apollo-social' ),
					'event'   => __( 'Eventos', 'apollo-social' ),
					'review'  => __( 'Reviews', 'apollo-social' ),
					'comment' => __( 'Comentários', 'apollo-social' ),
					'like'    => __( 'Curtidas', 'apollo-social' ),
					'follow'  => __( 'Seguindo', 'apollo-social' ),
					'share'   => __( 'Compartilhamentos', 'apollo-social' ),
				),
				'default'  => array( 'post', 'photo', 'video' ),
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
				'avatar'     => __( 'Mostrar Avatar', 'apollo-social' ),
				'author'     => __( 'Mostrar Autor', 'apollo-social' ),
				'date'       => __( 'Mostrar Data', 'apollo-social' ),
				'content'    => __( 'Mostrar Conteúdo', 'apollo-social' ),
				'media'      => __( 'Mostrar Mídia', 'apollo-social' ),
				'actions'    => __( 'Mostrar Ações', 'apollo-social' ),
				'likes'      => __( 'Mostrar Curtidas', 'apollo-social' ),
				'comments'   => __( 'Mostrar Comentários', 'apollo-social' ),
				'shares'     => __( 'Mostrar Compartilhamentos', 'apollo-social' ),
				'pagination' => __( 'Mostrar Paginação', 'apollo-social' ),
			),
			array(
				'avatar'     => true,
				'author'     => true,
				'date'       => true,
				'content'    => true,
				'media'      => true,
				'actions'    => true,
				'likes'      => true,
				'comments'   => true,
				'shares'     => false,
				'pagination' => true,
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'     => esc_html__( 'Tipo de Paginação', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'load_more',
				'options'   => array(
					'load_more'  => __( 'Carregar Mais', 'apollo-social' ),
					'infinite'   => __( 'Scroll Infinito', 'apollo-social' ),
					'pagination' => __( 'Paginação Numérica', 'apollo-social' ),
				),
				'condition' => array(
					'show_pagination' => 'yes',
				),
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
					'{{WRAPPER}} .apollo-feed__item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .apollo-feed__item',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-feed__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .apollo-feed__item',
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-feed__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
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
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-feed' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Avatar.
		$this->start_controls_section(
			'section_style_avatar',
			array(
				'label'     => esc_html__( 'Avatar', 'apollo-social' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_avatar' => 'yes',
				),
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'      => esc_html__( 'Tamanho', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 24,
						'max' => 96,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 48,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-feed__avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'avatar_border',
				'selector' => '{{WRAPPER}} .apollo-feed__avatar',
			)
		);

		$this->add_control(
			'avatar_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
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
					'{{WRAPPER}} .apollo-feed__avatar' => 'border-radius: {{SIZE}}{{UNIT}};',
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

		$layout         = $settings['layout'] ?? 'timeline';
		$limit          = (int) ( $settings['limit'] ?? 10 );
		$columns        = (int) ( $settings['columns'] ?? 2 );
		$feed_type      = $settings['feed_type'] ?? 'global';
		$activity_types = $settings['activity_types'] ?? array( 'post', 'photo', 'video' );
		$user_id        = $settings['user_id'] ?? 0;

		// Get current user for "following" feed.
		$current_user_id = get_current_user_id();

		// Build query based on activity type.
		// This is a simplified example - in a real implementation,
		// this would query a custom activity table.
		$args = array(
			'post_type'      => 'apollo_activity',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Filter by activity types.
		if ( ! empty( $activity_types ) ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_activity_type',
					'value'   => $activity_types,
					'compare' => 'IN',
				),
			);
		}

		// Filter by user.
		if ( $feed_type === 'user' && $user_id ) {
			$args['author'] = $user_id;
		}

		// For "following" feed, get followed user IDs.
		if ( $feed_type === 'following' && $current_user_id ) {
			$following = get_user_meta( $current_user_id, '_apollo_following', true );
			if ( ! empty( $following ) && is_array( $following ) ) {
				$args['author__in'] = $following;
			} else {
				// No one being followed.
				$args['post__in'] = array( 0 );
			}
		}

		$activities = new WP_Query( $args );

		$wrapper_classes = array(
			'apollo-feed',
			"apollo-feed--{$layout}",
		);

		if ( $layout === 'grid' || $layout === 'masonry' ) {
			$wrapper_classes[] = "apollo-feed--cols-{$columns}";
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			data-layout="<?php echo esc_attr( $layout ); ?>"
			data-pagination="<?php echo esc_attr( $settings['pagination_type'] ?? 'load_more' ); ?>">

			<?php if ( $activities->have_posts() ) : ?>
				<?php
				while ( $activities->have_posts() ) :
					$activities->the_post();
					?>
					<?php $this->render_activity_item( $settings ); ?>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<?php $this->render_empty_state(); ?>
			<?php endif; ?>

			<?php if ( $this->get_toggle_value( $settings, 'pagination' ) && $activities->max_num_pages > 1 ) : ?>
				<div class="apollo-feed__pagination">
					<?php if ( ( $settings['pagination_type'] ?? 'load_more' ) === 'load_more' ) : ?>
						<button class="apollo-feed__load-more" data-page="1" data-max="<?php echo esc_attr( $activities->max_num_pages ); ?>">
							<i class="ri-refresh-line"></i>
							<?php esc_html_e( 'Carregar Mais', 'apollo-social' ); ?>
						</button>
					<?php elseif ( ( $settings['pagination_type'] ?? 'load_more' ) === 'pagination' ) : ?>
						<?php
						echo paginate_links(
							array(
								'total'   => $activities->max_num_pages,
								'current' => max( 1, get_query_var( 'paged' ) ),
							)
						);
						?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single activity item.
	 *
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_activity_item( array $settings ): void {
		$post_id        = get_the_ID();
		$author_id      = get_the_author_meta( 'ID' );
		$activity_type  = get_post_meta( $post_id, '_activity_type', true ) ?: 'post';
		$media_ids      = get_post_meta( $post_id, '_activity_media', true );
		$likes_count    = (int) get_post_meta( $post_id, '_activity_likes', true );
		$comments_count = (int) get_comments_number( $post_id );
		$shares_count   = (int) get_post_meta( $post_id, '_activity_shares', true );
		?>
		<article class="apollo-feed__item apollo-feed__item--<?php echo esc_attr( $activity_type ); ?>">
			<header class="apollo-feed__header">
				<?php if ( $this->get_toggle_value( $settings, 'avatar' ) ) : ?>
					<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-feed__avatar-link">
						<?php echo get_avatar( $author_id, 48, '', '', array( 'class' => 'apollo-feed__avatar' ) ); ?>
					</a>
				<?php endif; ?>

				<div class="apollo-feed__meta">
					<?php if ( $this->get_toggle_value( $settings, 'author' ) ) : ?>
						<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-feed__author">
							<?php echo esc_html( get_the_author() ); ?>
						</a>
					<?php endif; ?>

					<div class="apollo-feed__meta-secondary">
						<?php if ( $this->get_toggle_value( $settings, 'date' ) ) : ?>
							<time class="apollo-feed__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
							</time>
						<?php endif; ?>

						<span class="apollo-feed__type">
							<?php
							$type_labels = array(
								'post'    => __( 'publicou', 'apollo-social' ),
								'photo'   => __( 'compartilhou uma foto', 'apollo-social' ),
								'video'   => __( 'compartilhou um vídeo', 'apollo-social' ),
								'event'   => __( 'criou um evento', 'apollo-social' ),
								'review'  => __( 'fez uma avaliação', 'apollo-social' ),
								'comment' => __( 'comentou', 'apollo-social' ),
							);
							echo esc_html( $type_labels[ $activity_type ] ?? '' );
							?>
						</span>
					</div>
				</div>

				<button class="apollo-feed__options">
					<i class="ri-more-2-fill"></i>
				</button>
			</header>

			<?php if ( $this->get_toggle_value( $settings, 'content' ) && get_the_content() ) : ?>
				<div class="apollo-feed__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<?php if ( $this->get_toggle_value( $settings, 'media' ) && ! empty( $media_ids ) ) : ?>
				<?php $media_array = is_array( $media_ids ) ? $media_ids : explode( ',', $media_ids ); ?>
				<div class="apollo-feed__media apollo-feed__media--count-<?php echo esc_attr( min( count( $media_array ), 4 ) ); ?>">
					<?php foreach ( array_slice( $media_array, 0, 4 ) as $idx => $media_id ) : ?>
						<?php
						$attachment = get_post( $media_id );
						if ( ! $attachment ) {
							continue;
						}
						$is_video = strpos( $attachment->post_mime_type, 'video' ) !== false;
						?>
						<div class="apollo-feed__media-item">
							<?php if ( $is_video ) : ?>
								<video src="<?php echo esc_url( wp_get_attachment_url( $media_id ) ); ?>"
										class="apollo-feed__video"
										controls></video>
							<?php else : ?>
								<?php echo wp_get_attachment_image( $media_id, 'large', false, array( 'class' => 'apollo-feed__image' ) ); ?>
							<?php endif; ?>

							<?php if ( $idx === 3 && count( $media_array ) > 4 ) : ?>
								<div class="apollo-feed__media-more">
									+<?php echo count( $media_array ) - 4; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $this->get_toggle_value( $settings, 'actions' ) ) : ?>
				<footer class="apollo-feed__footer">
					<div class="apollo-feed__stats">
						<?php if ( $this->get_toggle_value( $settings, 'likes' ) ) : ?>
							<span class="apollo-feed__stat apollo-feed__stat--likes">
								<i class="ri-heart-fill"></i>
								<?php echo esc_html( number_format_i18n( $likes_count ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $this->get_toggle_value( $settings, 'comments' ) ) : ?>
							<span class="apollo-feed__stat apollo-feed__stat--comments">
								<i class="ri-chat-1-fill"></i>
								<?php echo esc_html( number_format_i18n( $comments_count ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $this->get_toggle_value( $settings, 'shares' ) ) : ?>
							<span class="apollo-feed__stat apollo-feed__stat--shares">
								<i class="ri-share-forward-fill"></i>
								<?php echo esc_html( number_format_i18n( $shares_count ) ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="apollo-feed__actions">
						<button class="apollo-feed__action apollo-feed__action--like" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<i class="ri-heart-line"></i>
							<?php esc_html_e( 'Curtir', 'apollo-social' ); ?>
						</button>

						<button class="apollo-feed__action apollo-feed__action--comment" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<i class="ri-chat-1-line"></i>
							<?php esc_html_e( 'Comentar', 'apollo-social' ); ?>
						</button>

						<button class="apollo-feed__action apollo-feed__action--share" data-post-id="<?php echo esc_attr( $post_id ); ?>">
							<i class="ri-share-forward-line"></i>
							<?php esc_html_e( 'Compartilhar', 'apollo-social' ); ?>
						</button>
					</div>
				</footer>
			<?php endif; ?>
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
		<div class="apollo-feed__empty">
			<i class="ri-newspaper-line"></i>
			<h3><?php esc_html_e( 'Nenhuma atividade encontrada', 'apollo-social' ); ?></h3>
			<p><?php esc_html_e( 'Ainda não há atividades para mostrar no feed.', 'apollo-social' ); ?></p>
		</div>
		<?php
	}
}
