<?php
/**
 * Interest Module
 *
 * Handles "I'm interested" functionality, favorites, and user interest tracking.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Interest_Module
 *
 * Provides interest/favorites functionality for events.
 *
 * @since 2.0.0
 */
class Interest_Module extends Abstract_Module {

	/**
	 * Meta key for storing interested users.
	 *
	 * @var string
	 */
	const META_KEY = '_event_interested_users';

	/**
	 * User meta key for storing user's interested events.
	 *
	 * @var string
	 */
	const USER_META_KEY = '_user_interested_events';

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'interest';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Interesse/Favoritos', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Permite usuários marcar interesse em eventos, salvar favoritos e acompanhar eventos.', 'apollo-events' );
	}

	/**
	 * Get module version.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_version(): string {
		return '2.0.0';
	}

	/**
	 * Check if module is enabled by default.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		$this->register_shortcodes();
		$this->register_assets();
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_ajax_handlers(): void {
		add_action( 'wp_ajax_apollo_toggle_interest', array( $this, 'ajax_toggle_interest' ) );
		add_action( 'wp_ajax_nopriv_apollo_toggle_interest', array( $this, 'ajax_login_required' ) );
		add_action( 'wp_ajax_apollo_get_interest_count', array( $this, 'ajax_get_interest_count' ) );
		add_action( 'wp_ajax_nopriv_apollo_get_interest_count', array( $this, 'ajax_get_interest_count' ) );
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_interest_button', array( $this, 'render_interest_button' ) );
		add_shortcode( 'apollo_interest_count', array( $this, 'render_interest_count' ) );
		add_shortcode( 'apollo_user_interests', array( $this, 'render_user_interests' ) );
		add_shortcode( 'apollo_interested_users', array( $this, 'render_interested_users' ) );
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_register_style(
			'apollo-interest',
			$plugin_url . '/assets/css/interest.css',
			array(),
			$this->get_version()
		);

		wp_register_script(
			'apollo-interest',
			$plugin_url . '/assets/js/interest.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-interest',
			'apolloInterest',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'apollo_events_nonce' ),
				'isLoggedIn' => is_user_logged_in(),
				'loginUrl'   => wp_login_url( get_permalink() ),
				'i18n'       => array(
					'interested'    => __( 'Tenho Interesse', 'apollo-events' ),
					'notInterested' => __( 'Remover Interesse', 'apollo-events' ),
					'loginRequired' => __( 'Faça login para marcar interesse', 'apollo-events' ),
					'error'         => __( 'Erro ao processar sua solicitação', 'apollo-events' ),
					'person'        => __( 'pessoa interessada', 'apollo-events' ),
					'people'        => __( 'pessoas interessadas', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Render interest button shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_interest_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'   => get_the_ID(),
				'show_count' => 'true',
				'size'       => 'medium',
				'style'      => 'default',
			),
			$atts,
			'apollo_interest_button'
		);

		$event_id   = absint( $atts['event_id'] );
		$show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
		$size       = sanitize_key( $atts['size'] );
		$style      = sanitize_key( $atts['style'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-interest' );
		wp_enqueue_script( 'apollo-interest' );

		$is_interested = $this->is_user_interested( $event_id );
		$count         = $this->get_interest_count( $event_id );

		$button_class  = 'apollo-interest-btn';
		$button_class .= ' apollo-interest-btn--' . $size;
		$button_class .= ' apollo-interest-btn--' . $style;

		if ( $is_interested ) {
			$button_class .= ' is-interested';
		}

		ob_start();
		?>
		<div class="apollo-interest-wrapper" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<button type="button" class="<?php echo esc_attr( $button_class ); ?>"
					data-event-id="<?php echo esc_attr( $event_id ); ?>"
					aria-pressed="<?php echo $is_interested ? 'true' : 'false'; ?>">
				<span class="apollo-interest-btn__icon">
					<i class="<?php echo $is_interested ? 'fas' : 'far'; ?> fa-heart"></i>
				</span>
				<span class="apollo-interest-btn__text">
					<?php echo $is_interested ? esc_html__( 'Interessado', 'apollo-events' ) : esc_html__( 'Tenho Interesse', 'apollo-events' ); ?>
				</span>
				<?php if ( $show_count && $count > 0 ) : ?>
					<span class="apollo-interest-btn__count"><?php echo esc_html( $count ); ?></span>
				<?php endif; ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render interest count shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_interest_count( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'format'   => 'full',
			),
			$atts,
			'apollo_interest_count'
		);

		$event_id = absint( $atts['event_id'] );
		$format   = sanitize_key( $atts['format'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-interest' );

		$count = $this->get_interest_count( $event_id );

		ob_start();
		?>
		<span class="apollo-interest-count" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<?php if ( 'number' === $format ) : ?>
				<?php echo esc_html( $count ); ?>
			<?php else : ?>
				<i class="fas fa-heart"></i>
				<span class="apollo-interest-count__number"><?php echo esc_html( $count ); ?></span>
				<span class="apollo-interest-count__label">
					<?php echo esc_html( _n( 'pessoa interessada', 'pessoas interessadas', $count, 'apollo-events' ) ); ?>
				</span>
			<?php endif; ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render user interests shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_user_interests( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-login-required">' . esc_html__( 'Faça login para ver seus eventos de interesse.', 'apollo-events' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'user_id'  => get_current_user_id(),
				'limit'    => 12,
				'layout'   => 'grid',
				'columns'  => 3,
				'upcoming' => 'true',
			),
			$atts,
			'apollo_user_interests'
		);

		$user_id  = absint( $atts['user_id'] );
		$limit    = absint( $atts['limit'] );
		$layout   = sanitize_key( $atts['layout'] );
		$columns  = absint( $atts['columns'] );
		$upcoming = filter_var( $atts['upcoming'], FILTER_VALIDATE_BOOLEAN );

		wp_enqueue_style( 'apollo-interest' );
		wp_enqueue_style( 'apollo-lists' );
		wp_enqueue_script( 'apollo-interest' );

		$event_ids = $this->get_user_interested_events( $user_id );

		if ( empty( $event_ids ) ) {
			return '<div class="apollo-empty-state"><p>' . esc_html__( 'Você ainda não marcou interesse em nenhum evento.', 'apollo-events' ) . '</p></div>';
		}

		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => $limit,
			'post__in'       => $event_ids,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
		);

		if ( $upcoming ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
		}

		$query = new \WP_Query( $args );

		ob_start();
		?>
		<div class="apollo-user-interests">
			<div class="apollo-events-grid apollo-events-grid--cols-<?php echo esc_attr( $columns ); ?>">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$this->render_interest_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
				else :
					?>
					<p class="apollo-no-events"><?php esc_html_e( 'Nenhum evento próximo encontrado.', 'apollo-events' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render interested users shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_interested_users( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'limit'    => 12,
				'show_all' => 'false',
			),
			$atts,
			'apollo_interested_users'
		);

		$event_id = absint( $atts['event_id'] );
		$limit    = absint( $atts['limit'] );
		$show_all = filter_var( $atts['show_all'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-interest' );

		$user_ids = $this->get_interested_users( $event_id );
		$total    = count( $user_ids );

		if ( empty( $user_ids ) ) {
			return '<p class="apollo-no-interest">' . esc_html__( 'Nenhum usuário marcou interesse ainda.', 'apollo-events' ) . '</p>';
		}

		if ( ! $show_all ) {
			$user_ids = array_slice( $user_ids, 0, $limit );
		}

		ob_start();
		?>
		<div class="apollo-interested-users" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<div class="apollo-interested-users__avatars">
				<?php
				foreach ( $user_ids as $user_id ) :
					$user = get_userdata( $user_id );
					if ( ! $user ) {
						continue;
					}
					?>
					<div class="apollo-interested-users__avatar" title="<?php echo esc_attr( $user->display_name ); ?>">
						<?php echo get_avatar( $user_id, 40 ); ?>
					</div>
				<?php endforeach; ?>

				<?php if ( $total > $limit && ! $show_all ) : ?>
					<div class="apollo-interested-users__more">
						+<?php echo esc_html( $total - $limit ); ?>
					</div>
				<?php endif; ?>
			</div>

			<p class="apollo-interested-users__count">
				<?php
				printf(
					/* translators: %s: Number of interested users */
					esc_html( _n( '%s pessoa interessada', '%s pessoas interessadas', $total, 'apollo-events' ) ),
					esc_html( number_format_i18n( $total ) )
				);
				?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render interest card for an event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function render_interest_card( int $event_id ): void {
		$start_date = get_post_meta( $event_id, '_event_start_date', true );
		$local_ids  = get_post_meta( $event_id, '_event_local_ids', true );
		$location   = '';

		if ( ! empty( $local_ids ) && is_array( $local_ids ) ) {
			$location = get_the_title( $local_ids[0] );
		}

		$day   = '';
		$month = '';
		if ( $start_date ) {
			$timestamp = strtotime( $start_date );
			$day       = date_i18n( 'd', $timestamp );
			$month     = date_i18n( 'M', $timestamp );
		}
		?>
		<article class="apollo-event-card apollo-interest-card">
			<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="apollo-event-card__link">
				<div class="apollo-event-card__image">
					<?php if ( has_post_thumbnail( $event_id ) ) : ?>
						<?php echo get_the_post_thumbnail( $event_id, 'medium_large' ); ?>
					<?php else : ?>
						<div class="apollo-event-card__placeholder">
							<i class="fas fa-calendar-alt"></i>
						</div>
					<?php endif; ?>

					<?php if ( $day && $month ) : ?>
						<div class="apollo-event-card__date-badge">
							<span class="apollo-event-card__date-day"><?php echo esc_html( $day ); ?></span>
							<span class="apollo-event-card__date-month"><?php echo esc_html( $month ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="apollo-event-card__content">
					<h3 class="apollo-event-card__title"><?php echo esc_html( get_the_title( $event_id ) ); ?></h3>

					<?php if ( $location ) : ?>
						<p class="apollo-event-card__meta">
							<i class="fas fa-map-marker-alt"></i>
							<?php echo esc_html( $location ); ?>
						</p>
					<?php endif; ?>
				</div>
			</a>

			<div class="apollo-interest-card__actions">
				<?php
				echo $this->render_interest_button(
					array(
						'event_id'   => $event_id,
						'show_count' => 'false',
						'size'       => 'small',
					)
				);
				?>
			</div>
		</article>
		<?php
	}

	/**
	 * AJAX handler for toggling interest.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_toggle_interest(): void {
		check_ajax_referer( 'apollo_events_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login necessário', 'apollo-events' ) ), 401 );
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
		}

		$user_id       = get_current_user_id();
		$is_interested = $this->is_user_interested( $event_id, $user_id );

		if ( $is_interested ) {
			$this->remove_interest( $event_id, $user_id );
			$action = 'removed';
		} else {
			$this->add_interest( $event_id, $user_id );
			$action = 'added';
		}

		$new_count = $this->get_interest_count( $event_id );

		wp_send_json_success(
			array(
				'action'         => $action,
				'interested'     => ! $is_interested,
				'count'          => $new_count,
				'countFormatted' => number_format_i18n( $new_count ),
			)
		);
	}

	/**
	 * AJAX handler for login required.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_login_required(): void {
		wp_send_json_error(
			array(
				'message'  => __( 'Faça login para marcar interesse', 'apollo-events' ),
				'loginUrl' => wp_login_url( wp_get_referer() ),
			),
			401
		);
	}

	/**
	 * AJAX handler for getting interest count.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_get_interest_count(): void {
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;

		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
		}

		$count = $this->get_interest_count( $event_id );

		wp_send_json_success(
			array(
				'count'          => $count,
				'countFormatted' => number_format_i18n( $count ),
			)
		);
	}

	/**
	 * Check if a user is interested in an event.
	 *
	 * @since 2.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $user_id  User ID. Defaults to current user.
	 * @return bool
	 */
	public function is_user_interested( int $event_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$interested_users = $this->get_interested_users( $event_id );
		return in_array( $user_id, $interested_users, true );
	}

	/**
	 * Get interest count for an event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function get_interest_count( int $event_id ): int {
		$interested_users = $this->get_interested_users( $event_id );
		return count( $interested_users );
	}

	/**
	 * Get interested users for an event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function get_interested_users( int $event_id ): array {
		$interested_users = get_post_meta( $event_id, self::META_KEY, true );
		return is_array( $interested_users ) ? $interested_users : array();
	}

	/**
	 * Get user's interested events.
	 *
	 * @since 2.0.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_user_interested_events( int $user_id ): array {
		$events = get_user_meta( $user_id, self::USER_META_KEY, true );
		return is_array( $events ) ? $events : array();
	}

	/**
	 * Add interest to an event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 * @return bool
	 */
	public function add_interest( int $event_id, int $user_id ): bool {
		// Add user to event's interested list.
		$interested_users = $this->get_interested_users( $event_id );
		if ( ! in_array( $user_id, $interested_users, true ) ) {
			$interested_users[] = $user_id;
			update_post_meta( $event_id, self::META_KEY, $interested_users );
		}

		// Add event to user's interested list.
		$user_events = $this->get_user_interested_events( $user_id );
		if ( ! in_array( $event_id, $user_events, true ) ) {
			$user_events[] = $event_id;
			update_user_meta( $user_id, self::USER_META_KEY, $user_events );
		}

		/**
		 * Fires after a user marks interest in an event.
		 *
		 * @since 2.0.0
		 * @param int $event_id Event ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'apollo_interest_added', $event_id, $user_id );

		return true;
	}

	/**
	 * Remove interest from an event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 * @return bool
	 */
	public function remove_interest( int $event_id, int $user_id ): bool {
		// Remove user from event's interested list.
		$interested_users = $this->get_interested_users( $event_id );
		$key              = array_search( $user_id, $interested_users, true );
		if ( false !== $key ) {
			unset( $interested_users[ $key ] );
			$interested_users = array_values( $interested_users );
			update_post_meta( $event_id, self::META_KEY, $interested_users );
		}

		// Remove event from user's interested list.
		$user_events = $this->get_user_interested_events( $user_id );
		$key         = array_search( $event_id, $user_events, true );
		if ( false !== $key ) {
			unset( $user_events[ $key ] );
			$user_events = array_values( $user_events );
			update_user_meta( $user_id, self::USER_META_KEY, $user_events );
		}

		/**
		 * Fires after a user removes interest from an event.
		 *
		 * @since 2.0.0
		 * @param int $event_id Event ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'apollo_interest_removed', $event_id, $user_id );

		return true;
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'show_count_on_cards'  => array(
				'type'        => 'boolean',
				'label'       => __( 'Exibir contagem nos cards', 'apollo-events' ),
				'description' => __( 'Exibe o número de interessados nos cards de eventos.', 'apollo-events' ),
				'default'     => true,
			),
			'show_avatars'         => array(
				'type'        => 'boolean',
				'label'       => __( 'Exibir avatares', 'apollo-events' ),
				'description' => __( 'Exibe avatares dos usuários interessados.', 'apollo-events' ),
				'default'     => true,
			),
			'avatars_limit'        => array(
				'type'        => 'number',
				'label'       => __( 'Limite de avatares', 'apollo-events' ),
				'description' => __( 'Número máximo de avatares a exibir.', 'apollo-events' ),
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			),
			'enable_notifications' => array(
				'type'        => 'boolean',
				'label'       => __( 'Habilitar notificações', 'apollo-events' ),
				'description' => __( 'Enviar notificações quando houver atualizações no evento.', 'apollo-events' ),
				'default'     => false,
			),
		);
	}
}
