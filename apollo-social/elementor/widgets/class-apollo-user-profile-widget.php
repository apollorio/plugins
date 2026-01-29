<?php
/**
 * Apollo User Profile Elementor Widget
 *
 * Displays user profile card.
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
 * Class Apollo_User_Profile_Widget
 *
 * Elementor widget for displaying user profile.
 */
class Apollo_User_Profile_Widget extends Apollo_Base_Widget {

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
		return 'apollo-user-profile';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Perfil do Usuário', 'apollo-social' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'user', 'profile', 'perfil', 'autor', 'membro' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Content Section.
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Conteúdo', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Get users for select.
		$users = get_users(
			array(
				'number' => 100,
				'fields' => array( 'ID', 'display_name' ),
			)
		);

		$user_options = array( '' => __( 'Usuário Atual', 'apollo-social' ) );
		foreach ( $users as $user ) {
			$user_options[ $user->ID ] = $user->display_name;
		}

		$this->add_control(
			'user_id',
			array(
				'label'       => esc_html__( 'Usuário', 'apollo-social' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $user_options,
				'default'     => '',
				'description' => esc_html__( 'Deixe vazio para usar o usuário atual.', 'apollo-social' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'apollo-social' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card'       => __( 'Card', 'apollo-social' ),
					'horizontal' => __( 'Horizontal', 'apollo-social' ),
					'compact'    => __( 'Compacto', 'apollo-social' ),
					'hero'       => __( 'Hero Banner', 'apollo-social' ),
					'minimal'    => __( 'Minimal', 'apollo-social' ),
				),
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
				'cover'      => __( 'Mostrar Capa', 'apollo-social' ),
				'avatar'     => __( 'Mostrar Avatar', 'apollo-social' ),
				'name'       => __( 'Mostrar Nome', 'apollo-social' ),
				'username'   => __( 'Mostrar @username', 'apollo-social' ),
				'bio'        => __( 'Mostrar Bio', 'apollo-social' ),
				'location'   => __( 'Mostrar Localização', 'apollo-social' ),
				'website'    => __( 'Mostrar Website', 'apollo-social' ),
				'stats'      => __( 'Mostrar Estatísticas', 'apollo-social' ),
				'follow_btn' => __( 'Botão Seguir', 'apollo-social' ),
				'social'     => __( 'Mostrar Redes Sociais', 'apollo-social' ),
				'badges'     => __( 'Mostrar Badges', 'apollo-social' ),
			),
			array(
				'cover'      => true,
				'avatar'     => true,
				'name'       => true,
				'username'   => true,
				'bio'        => true,
				'location'   => false,
				'website'    => false,
				'stats'      => true,
				'follow_btn' => true,
				'social'     => false,
				'badges'     => false,
			)
		);

		$this->end_controls_section();

		// Stats Section.
		$this->start_controls_section(
			'section_stats',
			array(
				'label'     => esc_html__( 'Estatísticas', 'apollo-social' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'show_stats' => 'yes',
				),
			)
		);

		$this->add_control(
			'stats_items',
			array(
				'label'    => esc_html__( 'Itens', 'apollo-social' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => array(
					'followers'   => __( 'Seguidores', 'apollo-social' ),
					'following'   => __( 'Seguindo', 'apollo-social' ),
					'posts'       => __( 'Posts', 'apollo-social' ),
					'events'      => __( 'Eventos', 'apollo-social' ),
					'reviews'     => __( 'Avaliações', 'apollo-social' ),
					'classifieds' => __( 'Classificados', 'apollo-social' ),
					'likes'       => __( 'Curtidas', 'apollo-social' ),
				),
				'default'  => array( 'followers', 'following', 'posts' ),
			)
		);

		$this->end_controls_section();

		// Style Section - Card.
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card', 'apollo-social' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .apollo-profile',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-profile' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .apollo-profile',
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
						'min' => 48,
						'max' => 200,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 96,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-profile__avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'avatar_border',
				'selector' => '{{WRAPPER}} .apollo-profile__avatar',
			)
		);

		$this->add_control(
			'avatar_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-profile__avatar' => 'border-radius: {{SIZE}}{{UNIT}};',
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
				'name'     => 'name_typography',
				'label'    => esc_html__( 'Nome', 'apollo-social' ),
				'selector' => '{{WRAPPER}} .apollo-profile__name',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => esc_html__( 'Cor do Nome', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'username_color',
			array(
				'label'     => esc_html__( 'Cor do @username', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile__username' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bio_color',
			array(
				'label'     => esc_html__( 'Cor da Bio', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile__bio' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Button.
		$this->start_controls_section(
			'section_style_button',
			array(
				'label'     => esc_html__( 'Botão Seguir', 'apollo-social' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_follow_btn' => 'yes',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => esc_html__( 'Cor de Fundo', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6366f1',
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile__follow-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Cor do Texto', 'apollo-social' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apollo-profile__follow-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'apollo-social' ),
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
					'{{WRAPPER}} .apollo-profile__follow-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
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

		// Get user.
		$user_id = $settings['user_id'] ?? 0;
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			$this->render_placeholder(
				__( 'Nenhum usuário selecionado ou logado.', 'apollo-social' ),
				'ri-user-line'
			);
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			$this->render_placeholder(
				__( 'Usuário não encontrado.', 'apollo-social' ),
				'ri-user-line'
			);
			return;
		}

		$layout = $settings['layout'] ?? 'card';

		// Get user meta.
		$bio      = get_user_meta( $user_id, 'description', true );
		$location = get_user_meta( $user_id, 'apollo_location', true );
		$website  = $user->user_url;
		$cover_id = get_user_meta( $user_id, 'apollo_cover_image', true );

		// Get stats.
		$followers_count = (int) get_user_meta( $user_id, '_apollo_followers_count', true );
		$following_count = (int) get_user_meta( $user_id, '_apollo_following_count', true );
		$posts_count     = (int) count_user_posts( $user_id );
		$events_count    = (int) count_user_posts( $user_id, 'event_listing' );

		// Check if current user is following.
		$current_user_id = get_current_user_id();
		$is_following    = false;
		if ( $current_user_id && $current_user_id !== $user_id ) {
			$following    = get_user_meta( $current_user_id, '_apollo_following', true );
			$is_following = is_array( $following ) && in_array( $user_id, $following );
		}

		// Get social links.
		$social_links = array(
			'facebook'   => get_user_meta( $user_id, 'apollo_facebook', true ),
			'instagram'  => get_user_meta( $user_id, 'apollo_instagram', true ),
			'twitter'    => get_user_meta( $user_id, 'apollo_twitter', true ),
			'youtube'    => get_user_meta( $user_id, 'apollo_youtube', true ),
			'soundcloud' => get_user_meta( $user_id, 'apollo_soundcloud', true ),
			'spotify'    => get_user_meta( $user_id, 'apollo_spotify', true ),
		);

		// Get badges.
		$badges = get_user_meta( $user_id, '_apollo_badges', true ) ?: array();

		$wrapper_classes = array(
			'apollo-profile',
			"apollo-profile--{$layout}",
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>">

			<?php if ( $this->get_toggle_value( $settings, 'cover' ) ) : ?>
				<div class="apollo-profile__cover">
					<?php if ( $cover_id ) : ?>
						<?php echo wp_get_attachment_image( $cover_id, 'large' ); ?>
					<?php else : ?>
						<div class="apollo-profile__cover-placeholder"></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="apollo-profile__body">
				<?php if ( $this->get_toggle_value( $settings, 'avatar' ) ) : ?>
					<div class="apollo-profile__avatar-wrapper">
						<?php echo get_avatar( $user_id, 192, '', '', array( 'class' => 'apollo-profile__avatar' ) ); ?>
						<?php if ( $this->get_toggle_value( $settings, 'badges' ) && ! empty( $badges ) ) : ?>
							<?php if ( in_array( 'verified', $badges ) ) : ?>
								<span class="apollo-profile__verified" title="<?php esc_attr_e( 'Verificado', 'apollo-social' ); ?>">
									<i class="ri-checkbox-circle-fill"></i>
								</span>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="apollo-profile__info">
					<?php if ( $this->get_toggle_value( $settings, 'name' ) ) : ?>
						<h3 class="apollo-profile__name">
							<?php echo esc_html( $user->display_name ); ?>
						</h3>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'username' ) ) : ?>
						<span class="apollo-profile__username">
							@<?php echo esc_html( $user->user_login ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'bio' ) && $bio ) : ?>
						<p class="apollo-profile__bio">
							<?php echo esc_html( $bio ); ?>
						</p>
					<?php endif; ?>

					<div class="apollo-profile__details">
						<?php if ( $this->get_toggle_value( $settings, 'location' ) && $location ) : ?>
							<span class="apollo-profile__detail">
								<i class="ri-map-pin-line"></i>
								<?php echo esc_html( $location ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $this->get_toggle_value( $settings, 'website' ) && $website ) : ?>
							<a href="<?php echo esc_url( $website ); ?>" class="apollo-profile__detail apollo-profile__website" target="_blank" rel="noopener noreferrer">
								<i class="ri-link"></i>
								<?php echo esc_html( wp_parse_url( $website, PHP_URL_HOST ) ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php if ( $this->get_toggle_value( $settings, 'social' ) ) : ?>
						<?php
						$social_icons = array(
							'facebook'   => 'ri-facebook-fill',
							'instagram'  => 'ri-instagram-fill',
							'twitter'    => 'ri-twitter-x-fill',
							'youtube'    => 'ri-youtube-fill',
							'soundcloud' => 'ri-soundcloud-fill',
							'spotify'    => 'ri-spotify-fill',
						);
						$has_social   = array_filter( $social_links );
						?>
						<?php if ( ! empty( $has_social ) ) : ?>
							<div class="apollo-profile__social">
								<?php foreach ( $social_links as $network => $url ) : ?>
									<?php if ( $url ) : ?>
										<a href="<?php echo esc_url( $url ); ?>" class="apollo-profile__social-link apollo-profile__social-link--<?php echo esc_attr( $network ); ?>" target="_blank" rel="noopener noreferrer">
											<i class="<?php echo esc_attr( $social_icons[ $network ] ?? 'ri-link' ); ?>"></i>
										</a>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php if ( $this->get_toggle_value( $settings, 'stats' ) ) : ?>
					<?php
					$stats_items = $settings['stats_items'] ?? array( 'followers', 'following', 'posts' );
					$stats_data  = array(
						'followers'   => array(
							'count' => $followers_count,
							'label' => __( 'Seguidores', 'apollo-social' ),
						),
						'following'   => array(
							'count' => $following_count,
							'label' => __( 'Seguindo', 'apollo-social' ),
						),
						'posts'       => array(
							'count' => $posts_count,
							'label' => __( 'Posts', 'apollo-social' ),
						),
						'events'      => array(
							'count' => $events_count,
							'label' => __( 'Eventos', 'apollo-social' ),
						),
						'reviews'     => array(
							'count' => 0,
							'label' => __( 'Avaliações', 'apollo-social' ),
						),
						'classifieds' => array(
							'count' => 0,
							'label' => __( 'Classificados', 'apollo-social' ),
						),
						'likes'       => array(
							'count' => 0,
							'label' => __( 'Curtidas', 'apollo-social' ),
						),
					);
					?>
					<div class="apollo-profile__stats">
						<?php foreach ( $stats_items as $stat_key ) : ?>
							<?php if ( isset( $stats_data[ $stat_key ] ) ) : ?>
								<div class="apollo-profile__stat">
									<span class="apollo-profile__stat-value">
										<?php echo esc_html( number_format_i18n( $stats_data[ $stat_key ]['count'] ) ); ?>
									</span>
									<span class="apollo-profile__stat-label">
										<?php echo esc_html( $stats_data[ $stat_key ]['label'] ); ?>
									</span>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $this->get_toggle_value( $settings, 'follow_btn' ) && $current_user_id && $current_user_id !== $user_id ) : ?>
					<div class="apollo-profile__actions">
						<button class="apollo-profile__follow-btn <?php echo $is_following ? 'apollo-profile__follow-btn--following' : ''; ?>"
								data-user-id="<?php echo esc_attr( $user_id ); ?>"
								data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
							<?php if ( $is_following ) : ?>
								<i class="ri-user-unfollow-line"></i>
								<?php esc_html_e( 'Seguindo', 'apollo-social' ); ?>
							<?php else : ?>
								<i class="ri-user-add-line"></i>
								<?php esc_html_e( 'Seguir', 'apollo-social' ); ?>
							<?php endif; ?>
						</button>

						<button class="apollo-profile__message-btn" data-user-id="<?php echo esc_attr( $user_id ); ?>">
							<i class="ri-mail-line"></i>
						</button>
					</div>
				<?php endif; ?>

				<?php if ( $this->get_toggle_value( $settings, 'badges' ) && ! empty( $badges ) ) : ?>
					<?php
					$badge_labels = array(
						'verified'   => array(
							'icon'  => 'ri-checkbox-circle-fill',
							'label' => __( 'Verificado', 'apollo-social' ),
						),
						'dj'         => array(
							'icon'  => 'ri-disc-fill',
							'label' => __( 'DJ', 'apollo-social' ),
						),
						'producer'   => array(
							'icon'  => 'ri-music-2-fill',
							'label' => __( 'Produtor', 'apollo-social' ),
						),
						'organizer'  => array(
							'icon'  => 'ri-calendar-event-fill',
							'label' => __( 'Organizador', 'apollo-social' ),
						),
						'venue'      => array(
							'icon'  => 'ri-store-3-fill',
							'label' => __( 'Estabelecimento', 'apollo-social' ),
						),
						'early_bird' => array(
							'icon'  => 'ri-flashlight-fill',
							'label' => __( 'Early Bird', 'apollo-social' ),
						),
					);
					?>
					<div class="apollo-profile__badges">
						<?php foreach ( $badges as $badge ) : ?>
							<?php if ( isset( $badge_labels[ $badge ] ) ) : ?>
								<span class="apollo-profile__badge apollo-profile__badge--<?php echo esc_attr( $badge ); ?>" title="<?php echo esc_attr( $badge_labels[ $badge ]['label'] ); ?>">
									<i class="<?php echo esc_attr( $badge_labels[ $badge ]['icon'] ); ?>"></i>
									<span><?php echo esc_html( $badge_labels[ $badge ]['label'] ); ?></span>
								</span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
