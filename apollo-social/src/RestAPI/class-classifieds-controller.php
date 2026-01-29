<?php
/**
 * Apollo Classifieds REST Controller
 *
 * REST API controller for classified ads endpoints.
 *
 * @package Apollo_Social
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\RestAPI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load base controller.
if ( ! class_exists( 'Apollo_Core\REST_API\Apollo_REST_Controller' ) ) {
	return;
}

/**
 * Class Classifieds_Controller
 *
 * Handles classified ads operations via REST API.
 *
 * @since 2.0.0
 */
class Classifieds_Controller extends \Apollo_Core\REST_API\Apollo_REST_Controller {

	/**
	 * REST base.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $rest_base = 'classifieds';

	/**
	 * Post type.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected string $post_type = 'apollo_classified';

	/**
	 * Register routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		// GET /classifieds - List classifieds.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_create_permission' ),
					'args'                => $this->get_classified_create_params(),
				),
			)
		);

		// GET/PUT/DELETE /classifieds/{id} - Single classified operations.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'ID do classificado.', 'apollo-social' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_update_permission' ),
					'args'                => $this->get_classified_update_params(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_delete_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'ID do classificado.', 'apollo-social' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// POST /classifieds/{id}/contact - Send contact message.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/contact',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_contact' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'args'                => array(
					'id'      => array(
						'description'       => __( 'ID do classificado.', 'apollo-social' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'message' => array(
						'description'       => __( 'Mensagem.', 'apollo-social' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// GET /classifieds/categories - List categories.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/categories',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_categories' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * Get classifieds collection.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$pagination = $this->get_pagination_params( $request );

		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => $pagination['per_page'],
			'offset'         => $pagination['offset'],
			'post_status'    => 'publish',
		);

		// Search.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $this->sanitize_text( $search );
		}

		// Order.
		$orderby = $request->get_param( 'orderby' ) ?? 'date';
		$order   = strtoupper( $request->get_param( 'order' ) ?? 'DESC' );

		switch ( $orderby ) {
			case 'price':
				$args['meta_key'] = '_classified_price';
				$args['orderby']  = 'meta_value_num';
				break;
			case 'title':
				$args['orderby'] = 'title';
				break;
			default:
				$args['orderby'] = 'date';
		}
		$args['order'] = $order;

		// Category filter.
		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'classified_domain',
					'field'    => 'term_id',
					'terms'    => $this->sanitize_int_array( $category ),
				),
			);
		}

		// Type filter (sell, buy, exchange).
		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			$args['meta_query'][] = array(
				'key'   => '_classified_type',
				'value' => $this->sanitize_text( $type ),
			);
		}

		// Price range.
		$min_price = $request->get_param( 'min_price' );
		$max_price = $request->get_param( 'max_price' );

		if ( $min_price !== null || $max_price !== null ) {
			$price_query = array(
				'key'  => '_classified_price',
				'type' => 'NUMERIC',
			);

			if ( $min_price !== null && $max_price !== null ) {
				$price_query['value']   = array( $this->sanitize_float( $min_price ), $this->sanitize_float( $max_price ) );
				$price_query['compare'] = 'BETWEEN';
			} elseif ( $min_price !== null ) {
				$price_query['value']   = $this->sanitize_float( $min_price );
				$price_query['compare'] = '>=';
			} else {
				$price_query['value']   = $this->sanitize_float( $max_price );
				$price_query['compare'] = '<=';
			}

			$args['meta_query'][] = $price_query;
		}

		// Author filter.
		$author = $request->get_param( 'author' );
		if ( $author ) {
			$args['author'] = $this->sanitize_int( $author );
		}

		// Set meta_query relation.
		if ( ! empty( $args['meta_query'] ) && count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		$query = new \WP_Query( $args );

		$classifieds = array();
		foreach ( $query->posts as $post ) {
			$classifieds[] = $this->prepare_classified_for_response( $post, $request );
		}

		return $this->paginated_response(
			$classifieds,
			$query->found_posts,
			$pagination['page'],
			$pagination['per_page'],
			$request
		);
	}

	/**
	 * Get single classified.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'classified_not_found',
				__( 'Classificado não encontrado.', 'apollo-social' ),
				404
			);
		}

		if ( $post->post_status !== 'publish' && ! current_user_can( 'edit_post', $id ) ) {
			return $this->error(
				'classified_not_published',
				__( 'Este classificado não está publicado.', 'apollo-social' ),
				403
			);
		}

		// Increment view count.
		$views = (int) get_post_meta( $id, '_classified_views', true );
		update_post_meta( $id, '_classified_views', $views + 1 );

		$classified = $this->prepare_classified_for_response( $post, $request, true );

		return $this->success( $classified );
	}

	/**
	 * Create classified.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$title   = $this->sanitize_text( $request->get_param( 'title' ) );
		$content = $this->sanitize_html( $request->get_param( 'content' ) );

		if ( empty( $title ) ) {
			return $this->error(
				'title_required',
				__( 'O título é obrigatório.', 'apollo-social' ),
				400
			);
		}

		// New classifieds require moderation.
		$status = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';

		$post_data = array(
			'post_type'    => $this->post_type,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $this->error(
				'create_failed',
				$post_id->get_error_message(),
				500
			);
		}

		// Save meta.
		$this->save_classified_meta( $post_id, $request );

		// Set categories.
		$categories = $request->get_param( 'categories' );
		if ( ! empty( $categories ) ) {
			wp_set_object_terms( $post_id, $this->sanitize_int_array( $categories ), 'classified_domain' );
		}

		// Set featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( $featured_image ) {
			set_post_thumbnail( $post_id, $this->sanitize_int( $featured_image ) );
		}

		// Save gallery.
		$gallery = $request->get_param( 'gallery' );
		if ( ! empty( $gallery ) ) {
			update_post_meta( $post_id, '_classified_gallery', $this->sanitize_int_array( $gallery ) );
		}

		$post       = get_post( $post_id );
		$classified = $this->prepare_classified_for_response( $post, $request );

		/**
		 * Fires after a classified is created.
		 *
		 * @since 2.0.0
		 *
		 * @param int $post_id The classified ID.
		 * @param \WP_REST_Request $request The request.
		 */
		do_action( 'apollo_classified_created', $post_id, $request );

		return $this->success(
			$classified,
			$status === 'pending'
				? __( 'Classificado enviado para moderação.', 'apollo-social' )
				: __( 'Classificado criado com sucesso.', 'apollo-social' ),
			201
		);
	}

	/**
	 * Update classified.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'classified_not_found',
				__( 'Classificado não encontrado.', 'apollo-social' ),
				404
			);
		}

		$post_data = array( 'ID' => $id );

		$title = $request->get_param( 'title' );
		if ( $title !== null ) {
			$post_data['post_title'] = $this->sanitize_text( $title );
		}

		$content = $request->get_param( 'content' );
		if ( $content !== null ) {
			$post_data['post_content'] = $this->sanitize_html( $content );
		}

		$updated = wp_update_post( $post_data, true );

		if ( is_wp_error( $updated ) ) {
			return $this->error(
				'update_failed',
				$updated->get_error_message(),
				500
			);
		}

		// Update meta.
		$this->save_classified_meta( $id, $request );

		// Update categories.
		$categories = $request->get_param( 'categories' );
		if ( $categories !== null ) {
			wp_set_object_terms( $id, $this->sanitize_int_array( $categories ), 'classified_domain' );
		}

		// Update featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( $featured_image !== null ) {
			if ( $featured_image ) {
				set_post_thumbnail( $id, $this->sanitize_int( $featured_image ) );
			} else {
				delete_post_thumbnail( $id );
			}
		}

		// Update gallery.
		$gallery = $request->get_param( 'gallery' );
		if ( $gallery !== null ) {
			update_post_meta( $id, '_classified_gallery', $this->sanitize_int_array( $gallery ) );
		}

		$post       = get_post( $id );
		$classified = $this->prepare_classified_for_response( $post, $request );

		return $this->success(
			$classified,
			__( 'Classificado atualizado com sucesso.', 'apollo-social' )
		);
	}

	/**
	 * Delete classified.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'classified_not_found',
				__( 'Classificado não encontrado.', 'apollo-social' ),
				404
			);
		}

		$deleted = wp_trash_post( $id );

		if ( ! $deleted ) {
			return $this->error(
				'delete_failed',
				__( 'Falha ao excluir o classificado.', 'apollo-social' ),
				500
			);
		}

		return $this->success(
			array(
				'id'      => $id,
				'deleted' => true,
			),
			__( 'Classificado excluído com sucesso.', 'apollo-social' )
		);
	}

	/**
	 * Send contact message.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function send_contact( $request ) {
		$id      = (int) $request->get_param( 'id' );
		$message = $this->sanitize_textarea( $request->get_param( 'message' ) );
		$post    = get_post( $id );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return $this->error(
				'classified_not_found',
				__( 'Classificado não encontrado.', 'apollo-social' ),
				404
			);
		}

		if ( empty( $message ) ) {
			return $this->error(
				'message_required',
				__( 'A mensagem é obrigatória.', 'apollo-social' ),
				400
			);
		}

		$author = get_userdata( (int) $post->post_author );
		$sender = wp_get_current_user();

		if ( ! $author || ! $author->user_email ) {
			return $this->error(
				'author_not_found',
				__( 'Não foi possível encontrar o anunciante.', 'apollo-social' ),
				500
			);
		}

		// Can't contact yourself.
		if ( $sender->ID === $author->ID ) {
			return $this->error(
				'cannot_contact_self',
				__( 'Você não pode enviar mensagem para seu próprio anúncio.', 'apollo-social' ),
				400
			);
		}

		// Rate limiting.
		$rate_key     = 'apollo_contact_' . $sender->ID . '_' . $id;
		$last_contact = get_transient( $rate_key );
		if ( $last_contact ) {
			return $this->error(
				'rate_limited',
				__( 'Aguarde antes de enviar outra mensagem.', 'apollo-social' ),
				429
			);
		}

		// Send email.
		$subject = sprintf(
			/* translators: %s: classified title */
			__( 'Contato sobre: %s', 'apollo-social' ),
			get_the_title( $post )
		);

		$body = sprintf(
			/* translators: 1: author name, 2: sender name, 3: classified title, 4: message, 5: sender email */
			__( "Olá %1\$s,\n\n%2\$s enviou uma mensagem sobre seu anúncio \"%3\$s\":\n\n%4\$s\n\nPara responder, envie um email para: %5\$s", 'apollo-social' ),
			$author->display_name,
			$sender->display_name,
			get_the_title( $post ),
			$message,
			$sender->user_email
		);

		$sent = wp_mail(
			$author->user_email,
			$subject,
			$body,
			array( 'Reply-To: ' . $sender->user_email )
		);

		if ( ! $sent ) {
			return $this->error(
				'email_failed',
				__( 'Falha ao enviar mensagem. Tente novamente.', 'apollo-social' ),
				500
			);
		}

		// Set rate limit (5 minutes).
		set_transient( $rate_key, time(), 5 * MINUTE_IN_SECONDS );

		return $this->success(
			array( 'sent' => true ),
			__( 'Mensagem enviada com sucesso.', 'apollo-social' )
		);
	}

	/**
	 * Get categories.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_categories( $request ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'classified_domain',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $this->success( array() );
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$categories[] = array(
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $term->count,
			);
		}

		return $this->success( $categories );
	}

	/**
	 * Prepare classified for response.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @param bool             $full    Include full content.
	 * @return array
	 */
	protected function prepare_classified_for_response( \WP_Post $post, \WP_REST_Request $request, bool $full = false ): array {
		$data = $this->prepare_post_for_response( $post, $request );

		// Add classified-specific meta.
		$data['classified_meta'] = array(
			'type'      => get_post_meta( $post->ID, '_classified_type', true ) ?: 'sell',
			'price'     => (float) get_post_meta( $post->ID, '_classified_price', true ),
			'currency'  => get_post_meta( $post->ID, '_classified_currency', true ) ?: 'BRL',
			'condition' => get_post_meta( $post->ID, '_classified_condition', true ),
			'location'  => get_post_meta( $post->ID, '_classified_location', true ),
			'phone'     => get_post_meta( $post->ID, '_classified_phone', true ),
			'whatsapp'  => get_post_meta( $post->ID, '_classified_whatsapp', true ),
			'views'     => (int) get_post_meta( $post->ID, '_classified_views', true ),
		);

		// Format price.
		$price                                      = $data['classified_meta']['price'];
		$data['classified_meta']['price_formatted'] = $price > 0
			? 'R$ ' . number_format( $price, 2, ',', '.' )
			: __( 'A combinar', 'apollo-social' );

		// Add categories.
		$data['categories'] = $this->prepare_terms_for_response( $post->ID, 'classified_domain' );

		// Add gallery.
		$gallery         = get_post_meta( $post->ID, '_classified_gallery', true );
		$data['gallery'] = array();
		if ( ! empty( $gallery ) && is_array( $gallery ) ) {
			foreach ( $gallery as $image_id ) {
				$image_url = wp_get_attachment_image_url( $image_id, 'medium' );
				if ( $image_url ) {
					$data['gallery'][] = array(
						'id'        => (int) $image_id,
						'url'       => $image_url,
						'thumbnail' => wp_get_attachment_image_url( $image_id, 'thumbnail' ),
						'full'      => wp_get_attachment_image_url( $image_id, 'full' ),
					);
				}
			}
		}

		// Add author info.
		$data['author_info'] = $this->prepare_author_for_response( (int) $post->post_author );

		// Full content only on single view.
		if ( ! $full ) {
			unset( $data['content'] );
			// Hide contact info in list view.
			unset( $data['classified_meta']['phone'] );
			unset( $data['classified_meta']['whatsapp'] );
		}

		return $data;
	}

	/**
	 * Save classified meta.
	 *
	 * @since 2.0.0
	 *
	 * @param int              $post_id The post ID.
	 * @param \WP_REST_Request $request The request object.
	 * @return void
	 */
	protected function save_classified_meta( int $post_id, \WP_REST_Request $request ): void {
		$meta_fields = array(
			'type'      => array( 'sanitize_text', '_classified_type' ),
			'price'     => array( 'sanitize_float', '_classified_price' ),
			'currency'  => array( 'sanitize_text', '_classified_currency' ),
			'condition' => array( 'sanitize_text', '_classified_condition' ),
			'location'  => array( 'sanitize_text', '_classified_location' ),
			'phone'     => array( 'sanitize_text', '_classified_phone' ),
			'whatsapp'  => array( 'sanitize_text', '_classified_whatsapp' ),
		);

		foreach ( $meta_fields as $param => $config ) {
			$value = $request->get_param( $param );
			if ( $value !== null ) {
				$sanitizer = $config[0];
				$meta_key  = $config[1];

				if ( method_exists( $this, $sanitizer ) ) {
					$value = $this->$sanitizer( $value );
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Get classified creation params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_classified_create_params(): array {
		return array(
			'title'          => array(
				'description'       => __( 'Título do anúncio.', 'apollo-social' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content'        => array(
				'description'       => __( 'Descrição do anúncio.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'type'           => array(
				'description' => __( 'Tipo de anúncio.', 'apollo-social' ),
				'type'        => 'string',
				'default'     => 'sell',
				'enum'        => array( 'sell', 'buy', 'exchange', 'service' ),
			),
			'price'          => array(
				'description' => __( 'Preço.', 'apollo-social' ),
				'type'        => 'number',
				'minimum'     => 0,
			),
			'currency'       => array(
				'description' => __( 'Moeda.', 'apollo-social' ),
				'type'        => 'string',
				'default'     => 'BRL',
			),
			'condition'      => array(
				'description' => __( 'Condição do item.', 'apollo-social' ),
				'type'        => 'string',
				'enum'        => array( 'new', 'like-new', 'used', 'for-parts' ),
			),
			'location'       => array(
				'description'       => __( 'Localização.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'          => array(
				'description'       => __( 'Telefone de contato.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'whatsapp'       => array(
				'description'       => __( 'WhatsApp.', 'apollo-social' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'categories'     => array(
				'description' => __( 'IDs das categorias.', 'apollo-social' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
			),
			'featured_image' => array(
				'description'       => __( 'ID da imagem destacada.', 'apollo-social' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'gallery'        => array(
				'description' => __( 'IDs das imagens da galeria.', 'apollo-social' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Get classified update params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_classified_update_params(): array {
		$params = $this->get_classified_create_params();

		$params['id'] = array(
			'description'       => __( 'ID do classificado.', 'apollo-social' ),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
		);

		$params['title']['required'] = false;

		return $params;
	}

	/**
	 * Get collection params.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params = parent::get_collection_params();

		$params['orderby']['enum'][] = 'price';

		$params['category'] = array(
			'description'       => __( 'Filtrar por categoria.', 'apollo-social' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => array( $this, 'sanitize_int_array' ),
		);

		$params['type'] = array(
			'description' => __( 'Filtrar por tipo.', 'apollo-social' ),
			'type'        => 'string',
			'enum'        => array( 'sell', 'buy', 'exchange', 'service' ),
		);

		$params['min_price'] = array(
			'description' => __( 'Preço mínimo.', 'apollo-social' ),
			'type'        => 'number',
			'minimum'     => 0,
		);

		$params['max_price'] = array(
			'description' => __( 'Preço máximo.', 'apollo-social' ),
			'type'        => 'number',
			'minimum'     => 0,
		);

		$params['author'] = array(
			'description'       => __( 'Filtrar por autor.', 'apollo-social' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);

		return $params;
	}
}
