<?php
/**
 * Reviews Module
 *
 * Handles event reviews, ratings, and moderation.
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
 * Class Reviews_Module
 *
 * Provides review and rating functionality for events.
 *
 * @since 2.0.0
 */
class Reviews_Module extends Abstract_Module {

	/**
	 * Meta key for event reviews.
	 *
	 * @var string
	 */
	const REVIEWS_META_KEY = '_event_reviews';

	/**
	 * Meta key for average rating.
	 *
	 * @var string
	 */
	const RATING_META_KEY = '_event_average_rating';

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'reviews';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Reviews', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Sistema de avaliações e comentários para eventos.', 'apollo-events' );
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
		add_action( 'wp_ajax_apollo_submit_review', array( $this, 'ajax_submit_review' ) );
		add_action( 'wp_ajax_nopriv_apollo_submit_review', array( $this, 'ajax_login_required' ) );
		add_action( 'wp_ajax_apollo_helpful_review', array( $this, 'ajax_mark_helpful' ) );
		add_action( 'wp_ajax_nopriv_apollo_helpful_review', array( $this, 'ajax_mark_helpful' ) );
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_event_reviews', array( $this, 'render_reviews' ) );
		add_shortcode( 'apollo_review_form', array( $this, 'render_review_form' ) );
		add_shortcode( 'apollo_rating_display', array( $this, 'render_rating' ) );
		add_shortcode( 'apollo_rating_summary', array( $this, 'render_rating_summary' ) );
		add_shortcode( 'apollo_user_reviews', array( $this, 'render_user_reviews' ) );
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
			'apollo-reviews',
			$plugin_url . '/assets/css/reviews.css',
			array(),
			$this->get_version()
		);

		wp_register_script(
			'apollo-reviews',
			$plugin_url . '/assets/js/reviews.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-reviews',
			'apolloReviews',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'apollo_reviews_nonce' ),
				'isLoggedIn' => is_user_logged_in(),
				'i18n'       => array(
					'submitting'      => __( 'Enviando...', 'apollo-events' ),
					'success'         => __( 'Avaliação enviada com sucesso!', 'apollo-events' ),
					'error'           => __( 'Erro ao enviar avaliação.', 'apollo-events' ),
					'loginRequired'   => __( 'Faça login para avaliar.', 'apollo-events' ),
					'alreadyReviewed' => __( 'Você já avaliou este evento.', 'apollo-events' ),
					'selectRating'    => __( 'Selecione uma nota', 'apollo-events' ),
					'helpful'         => __( 'Útil', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Render reviews shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_reviews( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'  => get_the_ID(),
				'limit'     => 10,
				'show_form' => 'true',
				'sort'      => 'newest',
			),
			$atts,
			'apollo_event_reviews'
		);

		$event_id  = absint( $atts['event_id'] );
		$limit     = absint( $atts['limit'] );
		$show_form = filter_var( $atts['show_form'], FILTER_VALIDATE_BOOLEAN );
		$sort      = sanitize_key( $atts['sort'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-reviews' );
		wp_enqueue_script( 'apollo-reviews' );

		$reviews       = $this->get_event_reviews( $event_id, $sort );
		$reviews       = array_slice( $reviews, 0, $limit );
		$average       = $this->get_average_rating( $event_id );
		$total         = count( $this->get_event_reviews( $event_id ) );
		$user_reviewed = $this->has_user_reviewed( $event_id );

		ob_start();
		?>
		<div class="apollo-reviews" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<?php if ( $show_form && ! $user_reviewed ) : ?>
				<?php echo $this->render_review_form( array( 'event_id' => $event_id ) ); ?>
			<?php elseif ( $user_reviewed ) : ?>
				<div class="apollo-reviews__already-reviewed">
					<i class="fas fa-check-circle"></i>
					<?php esc_html_e( 'Você já avaliou este evento.', 'apollo-events' ); ?>
				</div>
			<?php endif; ?>

			<div class="apollo-reviews__header">
				<h3 class="apollo-reviews__title">
					<?php esc_html_e( 'Avaliações', 'apollo-events' ); ?>
					<span class="apollo-reviews__count">(<?php echo esc_html( $total ); ?>)</span>
				</h3>

				<?php if ( $total > 0 ) : ?>
					<div class="apollo-reviews__sort">
						<select class="apollo-reviews__sort-select" data-event-id="<?php echo esc_attr( $event_id ); ?>">
							<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Mais recentes', 'apollo-events' ); ?></option>
							<option value="highest" <?php selected( $sort, 'highest' ); ?>><?php esc_html_e( 'Maior nota', 'apollo-events' ); ?></option>
							<option value="lowest" <?php selected( $sort, 'lowest' ); ?>><?php esc_html_e( 'Menor nota', 'apollo-events' ); ?></option>
							<option value="helpful" <?php selected( $sort, 'helpful' ); ?>><?php esc_html_e( 'Mais úteis', 'apollo-events' ); ?></option>
						</select>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( empty( $reviews ) ) : ?>
				<div class="apollo-reviews__empty">
					<i class="fas fa-star"></i>
					<p><?php esc_html_e( 'Seja o primeiro a avaliar este evento!', 'apollo-events' ); ?></p>
				</div>
			<?php else : ?>
				<div class="apollo-reviews__list">
					<?php foreach ( $reviews as $review ) : ?>
						<?php $this->render_review_item( $review ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single review item.
	 *
	 * @since 2.0.0
	 * @param array $review Review data.
	 * @return void
	 */
	private function render_review_item( array $review ): void {
		$user = get_userdata( $review['user_id'] );
		if ( ! $user ) {
			return;
		}

		$rating    = isset( $review['rating'] ) ? absint( $review['rating'] ) : 0;
		$content   = isset( $review['content'] ) ? $review['content'] : '';
		$date      = isset( $review['date'] ) ? $review['date'] : '';
		$helpful   = isset( $review['helpful'] ) ? absint( $review['helpful'] ) : 0;
		$review_id = isset( $review['id'] ) ? $review['id'] : '';
		?>
		<div class="apollo-review" data-review-id="<?php echo esc_attr( $review_id ); ?>">
			<div class="apollo-review__header">
				<div class="apollo-review__author">
					<div class="apollo-review__avatar">
						<?php echo get_avatar( $review['user_id'], 48 ); ?>
					</div>
					<div class="apollo-review__meta">
						<span class="apollo-review__name"><?php echo esc_html( $user->display_name ); ?></span>
						<span class="apollo-review__date">
							<?php echo esc_html( human_time_diff( strtotime( $date ), current_time( 'timestamp' ) ) ); ?>
							<?php esc_html_e( 'atrás', 'apollo-events' ); ?>
						</span>
					</div>
				</div>

				<div class="apollo-review__rating">
					<?php echo $this->render_stars( $rating ); ?>
				</div>
			</div>

			<?php if ( $content ) : ?>
				<div class="apollo-review__content">
					<?php echo wp_kses_post( wpautop( $content ) ); ?>
				</div>
			<?php endif; ?>

			<div class="apollo-review__footer">
				<button type="button" class="apollo-review__helpful" data-review-id="<?php echo esc_attr( $review_id ); ?>">
					<i class="far fa-thumbs-up"></i>
					<span class="apollo-review__helpful-text"><?php esc_html_e( 'Útil', 'apollo-events' ); ?></span>
					<?php if ( $helpful > 0 ) : ?>
						<span class="apollo-review__helpful-count">(<?php echo esc_html( $helpful ); ?>)</span>
					<?php endif; ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render review form shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_review_form( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
			),
			$atts,
			'apollo_review_form'
		);

		$event_id = absint( $atts['event_id'] );

		if ( ! $event_id ) {
			return '';
		}

		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-login-required">' .
					'<i class="fas fa-lock"></i> ' .
					esc_html__( 'Faça login para deixar uma avaliação.', 'apollo-events' ) .
					'</div>';
		}

		if ( $this->has_user_reviewed( $event_id ) ) {
			return '';
		}

		wp_enqueue_style( 'apollo-reviews' );
		wp_enqueue_script( 'apollo-reviews' );

		ob_start();
		?>
		<div class="apollo-review-form" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<h4 class="apollo-review-form__title">
				<i class="fas fa-edit"></i>
				<?php esc_html_e( 'Deixe sua avaliação', 'apollo-events' ); ?>
			</h4>

			<form class="apollo-review-form__form">
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">

				<div class="apollo-review-form__rating">
					<label class="apollo-review-form__label"><?php esc_html_e( 'Sua nota', 'apollo-events' ); ?></label>
					<div class="apollo-review-form__stars">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<button type="button" class="apollo-review-form__star" data-rating="<?php echo esc_attr( $i ); ?>">
								<i class="far fa-star"></i>
							</button>
						<?php endfor; ?>
					</div>
					<input type="hidden" name="rating" value="" required>
				</div>

				<div class="apollo-review-form__field">
					<label for="review-content-<?php echo esc_attr( $event_id ); ?>" class="apollo-review-form__label">
						<?php esc_html_e( 'Seu comentário', 'apollo-events' ); ?>
						<span class="apollo-review-form__optional">(<?php esc_html_e( 'opcional', 'apollo-events' ); ?>)</span>
					</label>
					<textarea
						id="review-content-<?php echo esc_attr( $event_id ); ?>"
						name="content"
						class="apollo-review-form__textarea"
						rows="4"
						placeholder="<?php esc_attr_e( 'Conte como foi sua experiência...', 'apollo-events' ); ?>"
					></textarea>
				</div>

				<button type="submit" class="apollo-btn apollo-btn--primary apollo-review-form__submit">
					<i class="fas fa-paper-plane"></i>
					<?php esc_html_e( 'Enviar Avaliação', 'apollo-events' ); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render rating display shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_rating( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'   => get_the_ID(),
				'show_count' => 'true',
				'size'       => 'medium',
			),
			$atts,
			'apollo_rating_display'
		);

		$event_id   = absint( $atts['event_id'] );
		$show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
		$size       = sanitize_key( $atts['size'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-reviews' );

		$average = $this->get_average_rating( $event_id );
		$total   = count( $this->get_event_reviews( $event_id ) );

		ob_start();
		?>
		<div class="apollo-rating-display apollo-rating-display--<?php echo esc_attr( $size ); ?>">
			<div class="apollo-rating-display__stars">
				<?php echo $this->render_stars( $average ); ?>
			</div>
			<span class="apollo-rating-display__value"><?php echo esc_html( number_format( $average, 1 ) ); ?></span>
			<?php if ( $show_count ) : ?>
				<span class="apollo-rating-display__count">
					(<?php printf( esc_html( _n( '%d avaliação', '%d avaliações', $total, 'apollo-events' ) ), $total ); ?>)
				</span>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render rating summary shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_rating_summary( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
			),
			$atts,
			'apollo_rating_summary'
		);

		$event_id = absint( $atts['event_id'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-reviews' );

		$reviews   = $this->get_event_reviews( $event_id );
		$total     = count( $reviews );
		$average   = $this->get_average_rating( $event_id );
		$breakdown = $this->get_rating_breakdown( $event_id );

		if ( $total === 0 ) {
			return '';
		}

		ob_start();
		?>
		<div class="apollo-rating-summary">
			<div class="apollo-rating-summary__overview">
				<div class="apollo-rating-summary__average">
					<span class="apollo-rating-summary__number"><?php echo esc_html( number_format( $average, 1 ) ); ?></span>
					<div class="apollo-rating-summary__stars">
						<?php echo $this->render_stars( $average ); ?>
					</div>
					<span class="apollo-rating-summary__total">
						<?php printf( esc_html( _n( '%d avaliação', '%d avaliações', $total, 'apollo-events' ) ), $total ); ?>
					</span>
				</div>
			</div>

			<div class="apollo-rating-summary__breakdown">
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<?php
					$count   = isset( $breakdown[ $i ] ) ? $breakdown[ $i ] : 0;
					$percent = $total > 0 ? ( $count / $total ) * 100 : 0;
					?>
					<div class="apollo-rating-summary__bar">
						<span class="apollo-rating-summary__bar-label">
							<?php echo esc_html( $i ); ?>
							<i class="fas fa-star"></i>
						</span>
						<div class="apollo-rating-summary__bar-track">
							<div class="apollo-rating-summary__bar-fill" style="width: <?php echo esc_attr( $percent ); ?>%"></div>
						</div>
						<span class="apollo-rating-summary__bar-count"><?php echo esc_html( $count ); ?></span>
					</div>
				<?php endfor; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render user reviews shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_user_reviews( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-login-required">' . esc_html__( 'Faça login para ver suas avaliações.', 'apollo-events' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'user_id' => get_current_user_id(),
				'limit'   => 10,
			),
			$atts,
			'apollo_user_reviews'
		);

		$user_id = absint( $atts['user_id'] );
		$limit   = absint( $atts['limit'] );

		wp_enqueue_style( 'apollo-reviews' );

		$user_reviews = $this->get_user_reviews( $user_id, $limit );

		if ( empty( $user_reviews ) ) {
			return '<div class="apollo-empty-state"><p>' . esc_html__( 'Você ainda não fez nenhuma avaliação.', 'apollo-events' ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="apollo-user-reviews">
			<?php foreach ( $user_reviews as $item ) : ?>
				<?php
				$event_id = $item['event_id'];
				$review   = $item['review'];
				?>
				<div class="apollo-user-review">
					<div class="apollo-user-review__event">
						<?php if ( has_post_thumbnail( $event_id ) ) : ?>
							<div class="apollo-user-review__image">
								<?php echo get_the_post_thumbnail( $event_id, 'thumbnail' ); ?>
							</div>
						<?php endif; ?>
						<div class="apollo-user-review__info">
							<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="apollo-user-review__title">
								<?php echo esc_html( get_the_title( $event_id ) ); ?>
							</a>
							<div class="apollo-user-review__rating">
								<?php echo $this->render_stars( $review['rating'] ); ?>
							</div>
						</div>
					</div>
					<?php if ( ! empty( $review['content'] ) ) : ?>
						<div class="apollo-user-review__content">
							<?php echo wp_kses_post( wpautop( $review['content'] ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render stars HTML.
	 *
	 * @since 2.0.0
	 * @param float $rating Rating value.
	 * @return string
	 */
	private function render_stars( float $rating ): string {
		$html = '<div class="apollo-stars">';

		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $rating >= $i ) {
				$html .= '<i class="fas fa-star"></i>';
			} elseif ( $rating >= $i - 0.5 ) {
				$html .= '<i class="fas fa-star-half-alt"></i>';
			} else {
				$html .= '<i class="far fa-star"></i>';
			}
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * AJAX handler for submitting review.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_submit_review(): void {
		check_ajax_referer( 'apollo_reviews_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login necessário', 'apollo-events' ) ), 401 );
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$rating   = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$content  = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
		}

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Nota inválida', 'apollo-events' ) ), 400 );
		}

		$user_id = get_current_user_id();

		if ( $this->has_user_reviewed( $event_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Você já avaliou este evento', 'apollo-events' ) ), 400 );
		}

		$review_id = $this->add_review( $event_id, $user_id, $rating, $content );

		if ( ! $review_id ) {
			wp_send_json_error( array( 'message' => __( 'Erro ao salvar avaliação', 'apollo-events' ) ), 500 );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Avaliação enviada com sucesso!', 'apollo-events' ),
				'reviewId' => $review_id,
			)
		);
	}

	/**
	 * AJAX handler for marking review as helpful.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_mark_helpful(): void {
		$event_id  = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$review_id = isset( $_POST['review_id'] ) ? sanitize_key( $_POST['review_id'] ) : '';

		if ( ! $event_id || ! $review_id ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos', 'apollo-events' ) ), 400 );
		}

		$reviews = $this->get_event_reviews( $event_id );

		foreach ( $reviews as $key => $review ) {
			if ( isset( $review['id'] ) && $review['id'] === $review_id ) {
				$reviews[ $key ]['helpful'] = isset( $reviews[ $key ]['helpful'] ) ? $reviews[ $key ]['helpful'] + 1 : 1;
				update_post_meta( $event_id, self::REVIEWS_META_KEY, $reviews );

				wp_send_json_success(
					array(
						'helpful' => $reviews[ $key ]['helpful'],
					)
				);
			}
		}

		wp_send_json_error( array( 'message' => __( 'Avaliação não encontrada', 'apollo-events' ) ), 404 );
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
				'message'  => __( 'Faça login para avaliar', 'apollo-events' ),
				'loginUrl' => wp_login_url( wp_get_referer() ),
			),
			401
		);
	}

	/**
	 * Get event reviews.
	 *
	 * @since 2.0.0
	 * @param int    $event_id Event ID.
	 * @param string $sort     Sort order.
	 * @return array
	 */
	public function get_event_reviews( int $event_id, string $sort = 'newest' ): array {
		$reviews = get_post_meta( $event_id, self::REVIEWS_META_KEY, true );
		$reviews = is_array( $reviews ) ? $reviews : array();

		switch ( $sort ) {
			case 'highest':
				usort(
					$reviews,
					function ( $a, $b ) {
						return ( $b['rating'] ?? 0 ) - ( $a['rating'] ?? 0 );
					}
				);
				break;
			case 'lowest':
				usort(
					$reviews,
					function ( $a, $b ) {
						return ( $a['rating'] ?? 0 ) - ( $b['rating'] ?? 0 );
					}
				);
				break;
			case 'helpful':
				usort(
					$reviews,
					function ( $a, $b ) {
						return ( $b['helpful'] ?? 0 ) - ( $a['helpful'] ?? 0 );
					}
				);
				break;
			case 'newest':
			default:
				usort(
					$reviews,
					function ( $a, $b ) {
						return strtotime( $b['date'] ?? '' ) - strtotime( $a['date'] ?? '' );
					}
				);
				break;
		}

		return $reviews;
	}

	/**
	 * Get average rating for event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return float
	 */
	public function get_average_rating( int $event_id ): float {
		$reviews = $this->get_event_reviews( $event_id );

		if ( empty( $reviews ) ) {
			return 0.0;
		}

		$total = array_sum( array_column( $reviews, 'rating' ) );
		return round( $total / count( $reviews ), 1 );
	}

	/**
	 * Get rating breakdown.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function get_rating_breakdown( int $event_id ): array {
		$reviews   = $this->get_event_reviews( $event_id );
		$breakdown = array(
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0,
			5 => 0,
		);

		foreach ( $reviews as $review ) {
			$rating = isset( $review['rating'] ) ? absint( $review['rating'] ) : 0;
			if ( $rating >= 1 && $rating <= 5 ) {
				++$breakdown[ $rating ];
			}
		}

		return $breakdown;
	}

	/**
	 * Check if user has reviewed event.
	 *
	 * @since 2.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $user_id  User ID.
	 * @return bool
	 */
	public function has_user_reviewed( int $event_id, ?int $user_id = null ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$reviews = $this->get_event_reviews( $event_id );

		foreach ( $reviews as $review ) {
			if ( isset( $review['user_id'] ) && absint( $review['user_id'] ) === $user_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add review to event.
	 *
	 * @since 2.0.0
	 * @param int    $event_id Event ID.
	 * @param int    $user_id  User ID.
	 * @param int    $rating   Rating (1-5).
	 * @param string $content  Review content.
	 * @return string|false Review ID or false.
	 */
	public function add_review( int $event_id, int $user_id, int $rating, string $content ): string|false {
		$reviews = $this->get_event_reviews( $event_id );

		$review_id = 'review_' . uniqid();

		$reviews[] = array(
			'id'      => $review_id,
			'user_id' => $user_id,
			'rating'  => $rating,
			'content' => $content,
			'date'    => current_time( 'mysql' ),
			'helpful' => 0,
		);

		$updated = update_post_meta( $event_id, self::REVIEWS_META_KEY, $reviews );

		if ( $updated ) {
			$this->update_average_rating( $event_id );

			/**
			 * Fires after a review is added.
			 *
			 * @since 2.0.0
			 * @param string $review_id Review ID.
			 * @param int    $event_id  Event ID.
			 * @param int    $user_id   User ID.
			 * @param int    $rating    Rating.
			 */
			do_action( 'apollo_review_added', $review_id, $event_id, $user_id, $rating );

			return $review_id;
		}

		return false;
	}

	/**
	 * Update average rating meta.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function update_average_rating( int $event_id ): void {
		$average = $this->get_average_rating( $event_id );
		update_post_meta( $event_id, self::RATING_META_KEY, $average );
	}

	/**
	 * Get user reviews.
	 *
	 * @since 2.0.0
	 * @param int $user_id User ID.
	 * @param int $limit   Limit.
	 * @return array
	 */
	public function get_user_reviews( int $user_id, int $limit = 10 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta}
                 WHERE meta_key = %s",
				self::REVIEWS_META_KEY
			),
			ARRAY_A
		);

		$user_reviews = array();

		foreach ( $results as $row ) {
			$reviews = maybe_unserialize( $row['meta_value'] );
			if ( ! is_array( $reviews ) ) {
				continue;
			}

			foreach ( $reviews as $review ) {
				if ( isset( $review['user_id'] ) && absint( $review['user_id'] ) === $user_id ) {
					$user_reviews[] = array(
						'event_id' => absint( $row['post_id'] ),
						'review'   => $review,
					);
				}
			}
		}

		usort(
			$user_reviews,
			function ( $a, $b ) {
				return strtotime( $b['review']['date'] ?? '' ) - strtotime( $a['review']['date'] ?? '' );
			}
		);

		return array_slice( $user_reviews, 0, $limit );
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'require_attendance' => array(
				'type'        => 'boolean',
				'label'       => __( 'Exigir participação', 'apollo-events' ),
				'description' => __( 'Só permite avaliar após o evento terminar.', 'apollo-events' ),
				'default'     => false,
			),
			'moderate_reviews'   => array(
				'type'        => 'boolean',
				'label'       => __( 'Moderar avaliações', 'apollo-events' ),
				'description' => __( 'Exige aprovação antes de publicar.', 'apollo-events' ),
				'default'     => false,
			),
			'allow_edit'         => array(
				'type'        => 'boolean',
				'label'       => __( 'Permitir edição', 'apollo-events' ),
				'description' => __( 'Permite usuários editarem suas avaliações.', 'apollo-events' ),
				'default'     => true,
			),
			'show_on_cards'      => array(
				'type'        => 'boolean',
				'label'       => __( 'Exibir nos cards', 'apollo-events' ),
				'description' => __( 'Mostra nota nos cards de eventos.', 'apollo-events' ),
				'default'     => true,
			),
		);
	}
}
