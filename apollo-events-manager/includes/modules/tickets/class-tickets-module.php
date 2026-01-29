<?php
/**
 * Tickets Module
 *
 * Handles event ticketing, external links, and WooCommerce integration.
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
 * Class Tickets_Module
 *
 * Provides ticket and purchase functionality for events.
 *
 * @since 2.0.0
 */
class Tickets_Module extends Abstract_Module {

	/**
	 * Meta key for ticket URL.
	 *
	 * @var string
	 */
	const TICKET_URL_META = '_event_ticket_url';

	/**
	 * Meta key for ticket price.
	 *
	 * @var string
	 */
	const TICKET_PRICE_META = '_event_ticket_price';

	/**
	 * Meta key for ticket types.
	 *
	 * @var string
	 */
	const TICKET_TYPES_META = '_event_ticket_types';

	/**
	 * Meta key for WooCommerce product.
	 *
	 * @var string
	 */
	const WOOCOMMERCE_PRODUCT_META = '_event_woo_product_id';

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'tickets';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Tickets', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Sistema de ingressos com links externos e integração WooCommerce.', 'apollo-events' );
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
		$this->register_meta_fields();

		// WooCommerce integration.
		if ( $this->is_woocommerce_active() ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_completed' ) );
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_event_to_cart_item' ), 10, 3 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'display_event_in_cart' ), 10, 2 );
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Register meta fields.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_meta_fields(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_ticket_metabox' ) );
		add_action( 'save_post_event_listing', array( $this, 'save_ticket_meta' ) );
	}

	/**
	 * Add ticket metabox.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_ticket_metabox(): void {
		add_meta_box(
			'apollo_event_tickets',
			__( 'Ingressos', 'apollo-events' ),
			array( $this, 'render_ticket_metabox' ),
			'event_listing',
			'side',
			'default'
		);
	}

	/**
	 * Render ticket metabox.
	 *
	 * @since 2.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_ticket_metabox( $post ): void {
		wp_nonce_field( 'apollo_ticket_nonce', 'apollo_ticket_nonce_field' );

		$ticket_url   = get_post_meta( $post->ID, self::TICKET_URL_META, true );
		$ticket_price = get_post_meta( $post->ID, self::TICKET_PRICE_META, true );
		$ticket_types = get_post_meta( $post->ID, self::TICKET_TYPES_META, true );
		$woo_product  = get_post_meta( $post->ID, self::WOOCOMMERCE_PRODUCT_META, true );
		?>
		<p>
			<label for="apollo_ticket_url"><?php esc_html_e( 'URL do Ingresso:', 'apollo-events' ); ?></label>
			<input type="url" id="apollo_ticket_url" name="apollo_ticket_url"
					value="<?php echo esc_url( $ticket_url ); ?>" class="widefat">
			<small><?php esc_html_e( 'Link externo (Sympla, Eventbrite, etc)', 'apollo-events' ); ?></small>
		</p>
		<p>
			<label for="apollo_ticket_price"><?php esc_html_e( 'Preço:', 'apollo-events' ); ?></label>
			<input type="text" id="apollo_ticket_price" name="apollo_ticket_price"
					value="<?php echo esc_attr( $ticket_price ); ?>" class="widefat"
					placeholder="R$ 50,00 - R$ 150,00">
		</p>

		<?php if ( $this->is_woocommerce_active() ) : ?>
			<p>
				<label for="apollo_woo_product"><?php esc_html_e( 'Produto WooCommerce:', 'apollo-events' ); ?></label>
				<select id="apollo_woo_product" name="apollo_woo_product" class="widefat">
					<option value=""><?php esc_html_e( '— Nenhum —', 'apollo-events' ); ?></option>
					<?php
					$products = wc_get_products(
						array(
							'limit'  => -1,
							'status' => 'publish',
						)
					);
					foreach ( $products as $product ) :
						?>
						<option value="<?php echo esc_attr( $product->get_id() ); ?>"
								<?php selected( $woo_product, $product->get_id() ); ?>>
							<?php echo esc_html( $product->get_name() ); ?> -
							<?php echo wp_kses_post( $product->get_price_html() ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save ticket meta.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_ticket_meta( int $post_id ): void {
		if ( ! isset( $_POST['apollo_ticket_nonce_field'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_ticket_nonce_field'] ) ), 'apollo_ticket_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['apollo_ticket_url'] ) ) {
			update_post_meta( $post_id, self::TICKET_URL_META, esc_url_raw( wp_unslash( $_POST['apollo_ticket_url'] ) ) );
		}

		if ( isset( $_POST['apollo_ticket_price'] ) ) {
			update_post_meta( $post_id, self::TICKET_PRICE_META, sanitize_text_field( wp_unslash( $_POST['apollo_ticket_price'] ) ) );
		}

		if ( isset( $_POST['apollo_woo_product'] ) ) {
			update_post_meta( $post_id, self::WOOCOMMERCE_PRODUCT_META, absint( $_POST['apollo_woo_product'] ) );
		}
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_ticket_button', array( $this, 'render_ticket_button' ) );
		add_shortcode( 'apollo_ticket_price', array( $this, 'render_ticket_price' ) );
		add_shortcode( 'apollo_ticket_types', array( $this, 'render_ticket_types' ) );
		add_shortcode( 'apollo_buy_button', array( $this, 'render_buy_button' ) );
		add_shortcode( 'apollo_ticket_card', array( $this, 'render_ticket_card' ) );
		add_shortcode( 'apollo_ticket_status', array( $this, 'render_ticket_status' ) );
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
			'apollo-tickets',
			$plugin_url . '/assets/css/tickets.css',
			array(),
			$this->get_version()
		);

		wp_register_script(
			'apollo-tickets',
			$plugin_url . '/assets/js/tickets.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-tickets',
			'apolloTickets',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_tickets_nonce' ),
				'i18n'    => array(
					'soldOut'     => __( 'Esgotado', 'apollo-events' ),
					'available'   => __( 'Disponível', 'apollo-events' ),
					'lastUnits'   => __( 'Últimas unidades', 'apollo-events' ),
					'addedToCart' => __( 'Adicionado ao carrinho!', 'apollo-events' ),
					'buyNow'      => __( 'Comprar Ingresso', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Render ticket button shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_ticket_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'text'     => __( 'Comprar Ingresso', 'apollo-events' ),
				'size'     => 'medium',
				'style'    => 'primary',
				'icon'     => 'true',
			),
			$atts,
			'apollo_ticket_button'
		);

		$event_id  = absint( $atts['event_id'] );
		$show_icon = filter_var( $atts['icon'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );

		$ticket_url = $this->get_ticket_url( $event_id );
		$status     = $this->get_ticket_status( $event_id );

		if ( ! $ticket_url ) {
			return '';
		}

		$classes = array(
			'apollo-ticket-btn',
			'apollo-ticket-btn--' . sanitize_key( $atts['size'] ),
			'apollo-ticket-btn--' . sanitize_key( $atts['style'] ),
		);

		if ( 'sold_out' === $status ) {
			$classes[]    = 'apollo-ticket-btn--disabled';
			$atts['text'] = __( 'Esgotado', 'apollo-events' );
		}

		ob_start();
		?>
		<a href="<?php echo esc_url( $ticket_url ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			<?php echo 'sold_out' === $status ? 'aria-disabled="true"' : ''; ?>>
			<?php if ( $show_icon ) : ?>
				<i class="fas fa-ticket-alt"></i>
			<?php endif; ?>
			<span><?php echo esc_html( $atts['text'] ); ?></span>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render ticket price shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_ticket_price( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'   => get_the_ID(),
				'show_from'  => 'true',
				'show_label' => 'true',
			),
			$atts,
			'apollo_ticket_price'
		);

		$event_id   = absint( $atts['event_id'] );
		$show_from  = filter_var( $atts['show_from'], FILTER_VALIDATE_BOOLEAN );
		$show_label = filter_var( $atts['show_label'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );

		$price = get_post_meta( $event_id, self::TICKET_PRICE_META, true );

		if ( ! $price ) {
			// Try to get from WooCommerce.
			$woo_product = get_post_meta( $event_id, self::WOOCOMMERCE_PRODUCT_META, true );
			if ( $woo_product && $this->is_woocommerce_active() ) {
				$product = wc_get_product( $woo_product );
				if ( $product ) {
					$price = $product->get_price_html();
				}
			}
		}

		if ( ! $price ) {
			return '';
		}

		ob_start();
		?>
		<div class="apollo-ticket-price">
			<?php if ( $show_label ) : ?>
				<span class="apollo-ticket-price__label">
					<?php if ( $show_from ) : ?>
						<?php esc_html_e( 'A partir de', 'apollo-events' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Ingresso:', 'apollo-events' ); ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
			<span class="apollo-ticket-price__value"><?php echo wp_kses_post( $price ); ?></span>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render ticket types shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_ticket_types( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'layout'   => 'cards',
			),
			$atts,
			'apollo_ticket_types'
		);

		$event_id = absint( $atts['event_id'] );
		$layout   = sanitize_key( $atts['layout'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );
		wp_enqueue_script( 'apollo-tickets' );

		$ticket_types = get_post_meta( $event_id, self::TICKET_TYPES_META, true );
		$ticket_types = is_array( $ticket_types ) ? $ticket_types : array();

		if ( empty( $ticket_types ) ) {
			// Create default from simple price.
			$price      = get_post_meta( $event_id, self::TICKET_PRICE_META, true );
			$ticket_url = get_post_meta( $event_id, self::TICKET_URL_META, true );

			if ( $price || $ticket_url ) {
				$ticket_types[] = array(
					'name'        => __( 'Ingresso', 'apollo-events' ),
					'description' => '',
					'price'       => $price,
					'url'         => $ticket_url,
					'status'      => 'available',
				);
			}
		}

		if ( empty( $ticket_types ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="apollo-ticket-types apollo-ticket-types--<?php echo esc_attr( $layout ); ?>">
			<?php foreach ( $ticket_types as $ticket ) : ?>
				<?php $this->render_ticket_type_card( $ticket, $event_id ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single ticket type card.
	 *
	 * @since 2.0.0
	 * @param array $ticket   Ticket data.
	 * @param int   $event_id Event ID.
	 * @return void
	 */
	private function render_ticket_type_card( array $ticket, int $event_id ): void {
		$status = isset( $ticket['status'] ) ? $ticket['status'] : 'available';
		$name   = isset( $ticket['name'] ) ? $ticket['name'] : '';
		$desc   = isset( $ticket['description'] ) ? $ticket['description'] : '';
		$price  = isset( $ticket['price'] ) ? $ticket['price'] : '';
		$url    = isset( $ticket['url'] ) ? $ticket['url'] : $this->get_ticket_url( $event_id );
		?>
		<div class="apollo-ticket-type apollo-ticket-type--<?php echo esc_attr( $status ); ?>">
			<div class="apollo-ticket-type__header">
				<h4 class="apollo-ticket-type__name"><?php echo esc_html( $name ); ?></h4>
				<?php if ( 'sold_out' === $status ) : ?>
					<span class="apollo-ticket-type__badge apollo-ticket-type__badge--sold-out">
						<?php esc_html_e( 'Esgotado', 'apollo-events' ); ?>
					</span>
				<?php elseif ( 'last_units' === $status ) : ?>
					<span class="apollo-ticket-type__badge apollo-ticket-type__badge--last-units">
						<?php esc_html_e( 'Últimas unidades', 'apollo-events' ); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( $desc ) : ?>
				<p class="apollo-ticket-type__description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>

			<div class="apollo-ticket-type__footer">
				<div class="apollo-ticket-type__price">
					<?php echo wp_kses_post( $price ); ?>
				</div>

				<?php if ( 'sold_out' !== $status && $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"
						class="apollo-ticket-type__btn"
						target="_blank"
						rel="noopener noreferrer">
						<i class="fas fa-ticket-alt"></i>
						<?php esc_html_e( 'Comprar', 'apollo-events' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render buy button shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_buy_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'text'     => __( 'Comprar', 'apollo-events' ),
				'quantity' => 1,
			),
			$atts,
			'apollo_buy_button'
		);

		$event_id = absint( $atts['event_id'] );
		$quantity = absint( $atts['quantity'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );
		wp_enqueue_script( 'apollo-tickets' );

		// Check for WooCommerce product first.
		$woo_product = get_post_meta( $event_id, self::WOOCOMMERCE_PRODUCT_META, true );

		if ( $woo_product && $this->is_woocommerce_active() ) {
			$product = wc_get_product( $woo_product );
			if ( $product ) {
				ob_start();
				?>
				<form class="apollo-buy-form" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $woo_product ); ?>">
					<input type="hidden" name="quantity" value="<?php echo esc_attr( $quantity ); ?>">
					<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
					<button type="submit" class="apollo-buy-btn">
						<i class="fas fa-shopping-cart"></i>
						<span><?php echo esc_html( $atts['text'] ); ?></span>
						<span class="apollo-buy-btn__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					</button>
				</form>
				<?php
				return ob_get_clean();
			}
		}

		// Fallback to external link.
		return $this->render_ticket_button( $atts );
	}

	/**
	 * Render ticket card shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_ticket_card( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'   => get_the_ID(),
				'show_event' => 'true',
			),
			$atts,
			'apollo_ticket_card'
		);

		$event_id   = absint( $atts['event_id'] );
		$show_event = filter_var( $atts['show_event'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );

		$ticket_url = $this->get_ticket_url( $event_id );
		$price      = get_post_meta( $event_id, self::TICKET_PRICE_META, true );
		$status     = $this->get_ticket_status( $event_id );
		$event_date = get_post_meta( $event_id, '_event_start_date', true );

		ob_start();
		?>
		<div class="apollo-ticket-card apollo-ticket-card--<?php echo esc_attr( $status ); ?>">
			<div class="apollo-ticket-card__stub">
				<div class="apollo-ticket-card__stub-inner">
					<i class="fas fa-ticket-alt"></i>
				</div>
			</div>

			<div class="apollo-ticket-card__body">
				<?php if ( $show_event ) : ?>
					<div class="apollo-ticket-card__event">
						<h4 class="apollo-ticket-card__title">
							<?php echo esc_html( get_the_title( $event_id ) ); ?>
						</h4>
						<?php if ( $event_date ) : ?>
							<span class="apollo-ticket-card__date">
								<i class="far fa-calendar-alt"></i>
								<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $event_date ) ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="apollo-ticket-card__info">
					<?php if ( $price ) : ?>
						<div class="apollo-ticket-card__price">
							<?php echo wp_kses_post( $price ); ?>
						</div>
					<?php endif; ?>

					<?php if ( 'sold_out' === $status ) : ?>
						<span class="apollo-ticket-card__status apollo-ticket-card__status--sold-out">
							<?php esc_html_e( 'Esgotado', 'apollo-events' ); ?>
						</span>
					<?php elseif ( $ticket_url ) : ?>
						<a href="<?php echo esc_url( $ticket_url ); ?>"
							class="apollo-ticket-card__buy"
							target="_blank"
							rel="noopener noreferrer">
							<?php esc_html_e( 'Comprar', 'apollo-events' ); ?>
							<i class="fas fa-arrow-right"></i>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="apollo-ticket-card__perforation"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render ticket status shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_ticket_status( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
			),
			$atts,
			'apollo_ticket_status'
		);

		$event_id = absint( $atts['event_id'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-tickets' );

		$status = $this->get_ticket_status( $event_id );
		$labels = array(
			'available'  => __( 'Disponível', 'apollo-events' ),
			'last_units' => __( 'Últimas unidades', 'apollo-events' ),
			'sold_out'   => __( 'Esgotado', 'apollo-events' ),
			'free'       => __( 'Grátis', 'apollo-events' ),
			'coming'     => __( 'Em breve', 'apollo-events' ),
		);

		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : '';

		return '<span class="apollo-ticket-status apollo-ticket-status--' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Get ticket URL for event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return string
	 */
	public function get_ticket_url( int $event_id ): string {
		$url = get_post_meta( $event_id, self::TICKET_URL_META, true );

		if ( ! $url && $this->is_woocommerce_active() ) {
			$woo_product = get_post_meta( $event_id, self::WOOCOMMERCE_PRODUCT_META, true );
			if ( $woo_product ) {
				$product = wc_get_product( $woo_product );
				if ( $product ) {
					$url = $product->get_permalink();
				}
			}
		}

		/**
		 * Filter ticket URL.
		 *
		 * @since 2.0.0
		 * @param string $url      Ticket URL.
		 * @param int    $event_id Event ID.
		 */
		return apply_filters( 'apollo_ticket_url', $url, $event_id );
	}

	/**
	 * Get ticket status for event.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return string
	 */
	public function get_ticket_status( int $event_id ): string {
		$status = get_post_meta( $event_id, '_event_ticket_status', true );

		if ( $status ) {
			return $status;
		}

		// Check WooCommerce stock.
		if ( $this->is_woocommerce_active() ) {
			$woo_product = get_post_meta( $event_id, self::WOOCOMMERCE_PRODUCT_META, true );
			if ( $woo_product ) {
				$product = wc_get_product( $woo_product );
				if ( $product ) {
					if ( ! $product->is_in_stock() ) {
						return 'sold_out';
					}
					if ( $product->get_stock_quantity() && $product->get_stock_quantity() < 10 ) {
						return 'last_units';
					}
				}
			}
		}

		// Check if free.
		$price = get_post_meta( $event_id, self::TICKET_PRICE_META, true );
		if ( '0' === $price || strtolower( $price ) === 'grátis' || strtolower( $price ) === 'free' ) {
			return 'free';
		}

		return 'available';
	}

	/**
	 * Handle WooCommerce order completed.
	 *
	 * @since 2.0.0
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function handle_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$event_id = $item->get_meta( '_event_id' );

			if ( $event_id ) {
				// Add user to RSVP.
				$user_id = $order->get_user_id();
				if ( $user_id ) {
					$rsvps = get_post_meta( $event_id, '_event_rsvps', true );
					$rsvps = is_array( $rsvps ) ? $rsvps : array();

					if ( ! isset( $rsvps[ $user_id ] ) ) {
						$rsvps[ $user_id ] = array(
							'status'   => 'confirmed',
							'order_id' => $order_id,
							'date'     => current_time( 'mysql' ),
						);

						update_post_meta( $event_id, '_event_rsvps', $rsvps );

						/**
						 * Fires after ticket purchase is confirmed.
						 *
						 * @since 2.0.0
						 * @param int $event_id Event ID.
						 * @param int $user_id  User ID.
						 * @param int $order_id Order ID.
						 */
						do_action( 'apollo_ticket_purchased', $event_id, $user_id, $order_id );
					}
				}
			}
		}
	}

	/**
	 * Add event to cart item data.
	 *
	 * @since 2.0.0
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array
	 */
	public function add_event_to_cart_item( array $cart_item_data, int $product_id, int $variation_id ): array {
		if ( isset( $_POST['event_id'] ) ) {
			$cart_item_data['event_id'] = absint( $_POST['event_id'] );
		}

		return $cart_item_data;
	}

	/**
	 * Display event in cart.
	 *
	 * @since 2.0.0
	 * @param array $item_data Cart item data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_event_in_cart( array $item_data, array $cart_item ): array {
		if ( isset( $cart_item['event_id'] ) ) {
			$event_id    = $cart_item['event_id'];
			$item_data[] = array(
				'key'   => __( 'Evento', 'apollo-events' ),
				'value' => get_the_title( $event_id ),
			);
		}

		return $item_data;
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'default_provider' => array(
				'type'        => 'select',
				'label'       => __( 'Provedor padrão', 'apollo-events' ),
				'description' => __( 'Plataforma de ingressos padrão.', 'apollo-events' ),
				'options'     => array(
					'external' => __( 'Link Externo', 'apollo-events' ),
					'woo'      => __( 'WooCommerce', 'apollo-events' ),
				),
				'default'     => 'external',
			),
			'show_price_cards' => array(
				'type'    => 'boolean',
				'label'   => __( 'Mostrar preço nos cards', 'apollo-events' ),
				'default' => true,
			),
			'track_clicks'     => array(
				'type'        => 'boolean',
				'label'       => __( 'Rastrear cliques', 'apollo-events' ),
				'description' => __( 'Registra cliques em links de ingressos.', 'apollo-events' ),
				'default'     => true,
			),
		);
	}
}
