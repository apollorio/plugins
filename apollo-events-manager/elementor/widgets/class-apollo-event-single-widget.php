<?php
/**
 * Apollo Event Single Elementor Widget
 *
 * Displays a single event with full details.
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
 * Class Apollo_Event_Single_Widget
 *
 * Elementor widget for displaying a single event.
 */
class Apollo_Event_Single_Widget extends Apollo_Base_Widget {

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
		return 'apollo-event-single';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Evento Individual', 'apollo-events-manager' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-single-post';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo', 'event', 'evento', 'single', 'individual' );
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
				'label' => esc_html__( 'Conteúdo', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Get events for select.
		$events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$event_options = array( '' => __( 'Selecione um evento...', 'apollo-events-manager' ) );
		foreach ( $events as $event ) {
			$event_options[ $event->ID ] = $event->post_title;
		}

		$this->add_control(
			'event_id',
			array(
				'label'   => esc_html__( 'Evento', 'apollo-events-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $event_options,
				'default' => '',
			)
		);

		$this->add_control(
			'use_current',
			array(
				'label'        => esc_html__( 'Usar Evento Atual', 'apollo-events-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'description'  => esc_html__( 'Usar o evento do contexto atual (útil para templates).', 'apollo-events-manager' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'apollo-events-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'full',
				'options' => array(
					'full'    => __( 'Completo', 'apollo-events-manager' ),
					'compact' => __( 'Compacto', 'apollo-events-manager' ),
					'minimal' => __( 'Minimal', 'apollo-events-manager' ),
					'hero'    => __( 'Hero Banner', 'apollo-events-manager' ),
				),
			)
		);

		$this->end_controls_section();

		// Display Section.
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Elementos', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_display_toggles(
			array(
				'banner'    => __( 'Mostrar Banner', 'apollo-events-manager' ),
				'date'      => __( 'Mostrar Data/Hora', 'apollo-events-manager' ),
				'location'  => __( 'Mostrar Local', 'apollo-events-manager' ),
				'map'       => __( 'Mostrar Mapa', 'apollo-events-manager' ),
				'djs'       => __( 'Mostrar DJs/Artistas', 'apollo-events-manager' ),
				'timetable' => __( 'Mostrar Programação', 'apollo-events-manager' ),
				'tickets'   => __( 'Mostrar Ingressos', 'apollo-events-manager' ),
				'share'     => __( 'Mostrar Compartilhar', 'apollo-events-manager' ),
				'gallery'   => __( 'Mostrar Galeria', 'apollo-events-manager' ),
			),
			array(
				'banner'    => true,
				'date'      => true,
				'location'  => true,
				'map'       => true,
				'djs'       => true,
				'timetable' => true,
				'tickets'   => true,
				'share'     => true,
				'gallery'   => false,
			)
		);

		$this->end_controls_section();

		// Style Section - Banner.
		$this->start_controls_section(
			'section_style_banner',
			array(
				'label'     => esc_html__( 'Banner', 'apollo-events-manager' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_banner' => 'yes',
				),
			)
		);

		$this->add_control(
			'banner_height',
			array(
				'label'      => esc_html__( 'Altura', 'apollo-events-manager' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 200,
						'max' => 800,
					),
					'vh' => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 400,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apollo-event-single__banner' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'banner_overlay',
			array(
				'label'     => esc_html__( 'Overlay', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.4)',
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-single__banner::after' => 'background: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Content.
		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => esc_html__( 'Conteúdo', 'apollo-events-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Título', 'apollo-events-manager' ),
				'selector' => '{{WRAPPER}} .apollo-event-single__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Cor do Título', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-single__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => esc_html__( 'Cor de Destaque', 'apollo-events-manager' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6366f1',
				'selectors' => array(
					'{{WRAPPER}} .apollo-event-single__date-badge'  => 'background: {{VALUE}};',
					'{{WRAPPER}} .apollo-event-single__btn--primary' => 'background: {{VALUE}};',
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

		// Determine event ID.
		$event_id = 0;

		if ( $this->get_toggle_value( $settings, 'use_current' ) ) {
			$event_id = get_the_ID();
			if ( 'event_listing' !== get_post_type( $event_id ) ) {
				$event_id = 0;
			}
		} elseif ( ! empty( $settings['event_id'] ) ) {
			$event_id = (int) $settings['event_id'];
		}

		if ( ! $event_id ) {
			$this->render_placeholder(
				__( 'Selecione um evento para exibir.', 'apollo-events-manager' ),
				'ri-calendar-event-line'
			);
			return;
		}

		$event = get_post( $event_id );
		if ( ! $event || 'event_listing' !== $event->post_type ) {
			$this->render_placeholder(
				__( 'Evento não encontrado.', 'apollo-events-manager' ),
				'ri-calendar-event-line'
			);
			return;
		}

		// Get event meta.
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$end_date   = get_post_meta( $event_id, '_event_end_date', true );
		$start_time = get_post_meta( $event_id, '_event_start_time', true );
		$end_time   = get_post_meta( $event_id, '_event_end_time', true );
		$location   = get_post_meta( $event_id, '_event_location', true );
		$venue      = get_post_meta( $event_id, '_event_venue', true );
		$address    = get_post_meta( $event_id, '_event_address', true );
		$lat        = get_post_meta( $event_id, '_event_lat', true );
		$lng        = get_post_meta( $event_id, '_event_lng', true );
		$price      = get_post_meta( $event_id, '_event_price', true );
		$ticket_url = get_post_meta( $event_id, '_event_ticket_url', true );
		$djs        = get_post_meta( $event_id, '_event_djs', true );
		$timetable  = get_post_meta( $event_id, '_event_timetable', true );

		$layout = $settings['layout'] ?? 'full';

		$wrapper_classes = array(
			'apollo-event-single',
			"apollo-event-single--{$layout}",
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php if ( $this->get_toggle_value( $settings, 'banner' ) ) : ?>
				<div class="apollo-event-single__banner">
					<?php if ( has_post_thumbnail( $event_id ) ) : ?>
						<?php echo get_the_post_thumbnail( $event_id, 'full' ); ?>
					<?php else : ?>
						<div class="apollo-event-single__banner-placeholder">
							<i class="ri-calendar-event-line"></i>
						</div>
					<?php endif; ?>
					<div class="apollo-event-single__banner-content">
						<h1 class="apollo-event-single__title"><?php echo esc_html( $event->post_title ); ?></h1>
						<?php if ( $this->get_toggle_value( $settings, 'date' ) && $start_date ) : ?>
							<div class="apollo-event-single__date-inline">
								<i class="ri-calendar-line"></i>
								<?php
								echo esc_html( date_i18n( 'd \d\e F, Y', strtotime( $start_date ) ) );
								if ( $start_time ) {
									echo ' • ' . esc_html( $start_time );
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="apollo-event-single__body">
				<div class="apollo-event-single__main">
					<?php if ( ! $this->get_toggle_value( $settings, 'banner' ) ) : ?>
						<h1 class="apollo-event-single__title"><?php echo esc_html( $event->post_title ); ?></h1>
					<?php endif; ?>

					<?php if ( $event->post_content ) : ?>
						<div class="apollo-event-single__description">
							<?php echo wp_kses_post( apply_filters( 'the_content', $event->post_content ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'djs' ) && ! empty( $djs ) ) : ?>
						<div class="apollo-event-single__section">
							<h3 class="apollo-event-single__section-title">
								<i class="ri-disc-line"></i>
								<?php esc_html_e( 'Line-Up', 'apollo-events-manager' ); ?>
							</h3>
							<div class="apollo-event-single__djs">
								<?php
								$dj_ids = is_array( $djs ) ? $djs : explode( ',', $djs );
								foreach ( $dj_ids as $dj_id ) :
									$dj = get_post( (int) $dj_id );
									if ( ! $dj ) {
										continue;
									}
									?>
									<div class="apollo-event-single__dj">
										<?php if ( has_post_thumbnail( $dj->ID ) ) : ?>
											<?php echo get_the_post_thumbnail( $dj->ID, 'thumbnail' ); ?>
										<?php else : ?>
											<div class="apollo-event-single__dj-placeholder">
												<i class="ri-user-line"></i>
											</div>
										<?php endif; ?>
										<span><?php echo esc_html( $dj->post_title ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'timetable' ) && ! empty( $timetable ) ) : ?>
						<div class="apollo-event-single__section">
							<h3 class="apollo-event-single__section-title">
								<i class="ri-time-line"></i>
								<?php esc_html_e( 'Programação', 'apollo-events-manager' ); ?>
							</h3>
							<div class="apollo-event-single__timetable">
								<?php
								$schedule = is_array( $timetable ) ? $timetable : json_decode( $timetable, true );
								if ( is_array( $schedule ) ) :
									foreach ( $schedule as $item ) :
										?>
									<div class="apollo-event-single__timetable-item">
										<span class="apollo-event-single__timetable-time">
											<?php echo esc_html( $item['time'] ?? '' ); ?>
										</span>
										<span class="apollo-event-single__timetable-name">
											<?php echo esc_html( $item['name'] ?? $item['artist'] ?? '' ); ?>
										</span>
										<?php if ( ! empty( $item['stage'] ) ) : ?>
											<span class="apollo-event-single__timetable-stage">
												<?php echo esc_html( $item['stage'] ); ?>
											</span>
										<?php endif; ?>
									</div>
										<?php
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'map' ) && $lat && $lng ) : ?>
						<div class="apollo-event-single__section">
							<h3 class="apollo-event-single__section-title">
								<i class="ri-map-pin-line"></i>
								<?php esc_html_e( 'Localização', 'apollo-events-manager' ); ?>
							</h3>
							<div class="apollo-event-single__map"
								id="event-map-<?php echo esc_attr( $event_id ); ?>"
								data-lat="<?php echo esc_attr( $lat ); ?>"
								data-lng="<?php echo esc_attr( $lng ); ?>"
								data-title="<?php echo esc_attr( $venue ?: $event->post_title ); ?>"
							></div>
						</div>
					<?php endif; ?>
				</div>

				<aside class="apollo-event-single__sidebar">
					<?php if ( $this->get_toggle_value( $settings, 'date' ) && $start_date ) : ?>
						<div class="apollo-event-single__sidebar-card">
							<div class="apollo-event-single__date-badge">
								<span class="apollo-event-single__day">
									<?php echo esc_html( date_i18n( 'd', strtotime( $start_date ) ) ); ?>
								</span>
								<span class="apollo-event-single__month">
									<?php echo esc_html( date_i18n( 'M', strtotime( $start_date ) ) ); ?>
								</span>
								<span class="apollo-event-single__year">
									<?php echo esc_html( date_i18n( 'Y', strtotime( $start_date ) ) ); ?>
								</span>
							</div>
							<?php if ( $start_time ) : ?>
								<div class="apollo-event-single__time">
									<i class="ri-time-line"></i>
									<?php
									echo esc_html( $start_time );
									if ( $end_time ) {
										echo ' - ' . esc_html( $end_time );
									}
									?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'location' ) && ( $venue || $address ) ) : ?>
						<div class="apollo-event-single__sidebar-card">
							<h4><i class="ri-map-pin-2-fill"></i> <?php esc_html_e( 'Local', 'apollo-events-manager' ); ?></h4>
							<?php if ( $venue ) : ?>
								<p class="apollo-event-single__venue"><?php echo esc_html( $venue ); ?></p>
							<?php endif; ?>
							<?php if ( $address ) : ?>
								<p class="apollo-event-single__address"><?php echo esc_html( $address ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'tickets' ) && ( $price || $ticket_url ) ) : ?>
						<div class="apollo-event-single__sidebar-card">
							<h4><i class="ri-ticket-2-fill"></i> <?php esc_html_e( 'Ingressos', 'apollo-events-manager' ); ?></h4>
							<?php if ( $price ) : ?>
								<p class="apollo-event-single__price">
									<?php esc_html_e( 'A partir de', 'apollo-events-manager' ); ?>
									<strong>R$ <?php echo esc_html( number_format( (float) $price, 2, ',', '.' ) ); ?></strong>
								</p>
							<?php endif; ?>
							<?php if ( $ticket_url ) : ?>
								<a href="<?php echo esc_url( $ticket_url ); ?>"
									class="apollo-event-single__btn apollo-event-single__btn--primary"
									target="_blank" rel="noopener noreferrer">
									<i class="ri-ticket-line"></i>
									<?php esc_html_e( 'Comprar Ingresso', 'apollo-events-manager' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $this->get_toggle_value( $settings, 'share' ) ) : ?>
						<div class="apollo-event-single__sidebar-card">
							<h4><i class="ri-share-line"></i> <?php esc_html_e( 'Compartilhar', 'apollo-events-manager' ); ?></h4>
							<div class="apollo-event-single__share">
								<?php
								$share_url   = get_permalink( $event_id );
								$share_title = rawurlencode( $event->post_title );
								?>
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( $share_url ); ?>"
									class="apollo-event-single__share-btn apollo-event-single__share-btn--facebook"
									target="_blank" rel="noopener noreferrer">
									<i class="ri-facebook-fill"></i>
								</a>
								<a href="https://twitter.com/intent/tweet?url=<?php echo esc_url( $share_url ); ?>&text=<?php echo esc_attr( $share_title ); ?>"
									class="apollo-event-single__share-btn apollo-event-single__share-btn--twitter"
									target="_blank" rel="noopener noreferrer">
									<i class="ri-twitter-x-fill"></i>
								</a>
								<a href="https://api.whatsapp.com/send?text=<?php echo esc_attr( $share_title ); ?>%20<?php echo esc_url( $share_url ); ?>"
									class="apollo-event-single__share-btn apollo-event-single__share-btn--whatsapp"
									target="_blank" rel="noopener noreferrer">
									<i class="ri-whatsapp-fill"></i>
								</a>
							</div>
						</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
		<?php
	}
}
