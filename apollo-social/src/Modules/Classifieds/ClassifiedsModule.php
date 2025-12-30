<?php
/**
 * Classifieds Module
 *
 * Implements a trust-focused classifieds system for:
 * - Ingressos (ticket resale/request)
 * - Acomodação (accommodation offer/request)
 *
 * No payments on-platform - connection only via internal chat.
 *
 * @package Apollo\Modules\Classifieds
 * @since 2.2.0
 */

namespace Apollo\Modules\Classifieds;

/**
 * ClassifiedsModule
 *
 * Registers CPT, taxonomies, meta fields, and REST endpoints
 * for the Apollo Classifieds system.
 */
class ClassifiedsModule {

	/**
	 * Custom Post Type slug
	 */
	public const POST_TYPE = 'apollo_classified';

	/**
	 * Taxonomy slugs
	 */
	public const TAX_DOMAIN = 'classified_domain';
	public const TAX_INTENT = 'classified_intent';

	/**
	 * Meta field keys
	 */
	public const META_KEYS = array(
		'price'          => '_classified_price',
		'currency'       => '_classified_currency',
		'location'       => '_classified_location_text',
		'contact_pref'   => '_classified_contact_pref',
		'event_date'     => '_classified_event_date',     // Tickets: YYYYMMDD
		'event_title'    => '_classified_event_title',
		'start_date'     => '_classified_start_date',     // Accommodation: YYYYMMDD
		'end_date'       => '_classified_end_date',
		'capacity'       => '_classified_capacity',
		'gallery'        => '_classified_gallery',
		'views_count'    => '_classified_views',
		'safety_ack'     => '_classified_safety_acknowledged',
	);

	/**
	 * Domain terms (pt-BR)
	 */
	public const DOMAINS = array(
		'ingressos'   => 'Ingressos',
		'acomodacao'  => 'Acomodação',
	);

	/**
	 * Intent terms (pt-BR)
	 */
	public const INTENTS = array(
		'ofereco' => 'Ofereço',
		'procuro' => 'Procuro',
	);

	/**
	 * Initialize the module
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_post_type' ), 5 );
		add_action( 'init', array( self::class, 'register_taxonomies' ), 6 );
		add_action( 'init', array( self::class, 'register_meta_fields' ), 7 );
		add_action( 'init', array( self::class, 'insert_default_terms' ), 20 );

		// REST API
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );

		// Admin columns
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( self::class, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( self::class, 'admin_column_content' ), 10, 2 );

		// Single template
		add_filter( 'single_template', array( self::class, 'single_template' ) );
		add_filter( 'archive_template', array( self::class, 'archive_template' ) );

		// Track views
		add_action( 'wp', array( self::class, 'track_view' ) );
	}

	/**
	 * Register the classified CPT
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Anúncios', 'apollo-social' ),
			'singular_name'      => __( 'Anúncio', 'apollo-social' ),
			'menu_name'          => __( 'Classificados', 'apollo-social' ),
			'add_new'            => __( 'Novo Anúncio', 'apollo-social' ),
			'add_new_item'       => __( 'Adicionar Novo Anúncio', 'apollo-social' ),
			'edit_item'          => __( 'Editar Anúncio', 'apollo-social' ),
			'new_item'           => __( 'Novo Anúncio', 'apollo-social' ),
			'view_item'          => __( 'Ver Anúncio', 'apollo-social' ),
			'search_items'       => __( 'Buscar Anúncios', 'apollo-social' ),
			'not_found'          => __( 'Nenhum anúncio encontrado', 'apollo-social' ),
			'not_found_in_trash' => __( 'Nenhum anúncio na lixeira', 'apollo-social' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'anuncio', 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => 'anuncios',
			'hierarchical'       => false,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
			'taxonomies'         => array( self::TAX_DOMAIN, self::TAX_INTENT ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register taxonomies
	 */
	public static function register_taxonomies(): void {
		// Domain taxonomy (ingressos, acomodacao)
		register_taxonomy(
			self::TAX_DOMAIN,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Categoria', 'apollo-social' ),
					'singular_name' => __( 'Categoria', 'apollo-social' ),
					'menu_name'     => __( 'Categorias', 'apollo-social' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'tipo' ),
				'show_admin_column' => true,
			)
		);

		// Intent taxonomy (ofereco, procuro)
		register_taxonomy(
			self::TAX_INTENT,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Tipo de Anúncio', 'apollo-social' ),
					'singular_name' => __( 'Tipo', 'apollo-social' ),
					'menu_name'     => __( 'Tipos', 'apollo-social' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'intencao' ),
				'show_admin_column' => true,
			)
		);
	}

	/**
	 * Register meta fields for REST API
	 */
	public static function register_meta_fields(): void {
		foreach ( self::META_KEYS as $key => $meta_key ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Insert default taxonomy terms
	 */
	public static function insert_default_terms(): void {
		// Domain terms
		foreach ( self::DOMAINS as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAX_DOMAIN ) ) {
				wp_insert_term( $name, self::TAX_DOMAIN, array( 'slug' => $slug ) );
			}
		}

		// Intent terms
		foreach ( self::INTENTS as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAX_INTENT ) ) {
				wp_insert_term( $name, self::TAX_INTENT, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes(): void {
		$namespace = 'apollo/v1';

		// Search/filter endpoint
		register_rest_route(
			$namespace,
			'/classificados',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_list_classifieds' ),
				'permission_callback' => '__return_true',
			)
		);

		// Single classified
		register_rest_route(
			$namespace,
			'/classificados/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_classified' ),
				'permission_callback' => '__return_true',
			)
		);

		// Create classified
		register_rest_route(
			$namespace,
			'/classificados',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_create_classified' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// Update classified
		register_rest_route(
			$namespace,
			'/classificados/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( self::class, 'rest_update_classified' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// Delete classified
		register_rest_route(
			$namespace,
			'/classificados/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( self::class, 'rest_delete_classified' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// Report classified
		register_rest_route(
			$namespace,
			'/classificados/(?P<id>\d+)/reportar',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_report_classified' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// Safety acknowledgement
		register_rest_route(
			$namespace,
			'/classificados/(?P<id>\d+)/safety-ack',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_safety_ack' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * REST: List classifieds with filters
	 */
	public static function rest_list_classifieds( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 50, max( 1, (int) ( $request->get_param( 'per_page' ) ?? 12 ) ) );
		$domain   = sanitize_text_field( $request->get_param( 'domain' ) ?? '' );
		$intent   = sanitize_text_field( $request->get_param( 'intent' ) ?? '' );
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$location = sanitize_text_field( $request->get_param( 'location' ) ?? '' );

		// Date filters
		$date_from = sanitize_text_field( $request->get_param( 'date_from' ) ?? '' );
		$date_to   = sanitize_text_field( $request->get_param( 'date_to' ) ?? '' );

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Search
		if ( $search ) {
			$args['s'] = $search;
		}

		// Taxonomy queries
		$tax_query = array();

		if ( $domain && array_key_exists( $domain, self::DOMAINS ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_DOMAIN,
				'field'    => 'slug',
				'terms'    => $domain,
			);
		}

		if ( $intent && array_key_exists( $intent, self::INTENTS ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_INTENT,
				'field'    => 'slug',
				'terms'    => $intent,
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		// Meta queries
		$meta_query = array();

		if ( $location ) {
			$meta_query[] = array(
				'key'     => self::META_KEYS['location'],
				'value'   => $location,
				'compare' => 'LIKE',
			);
		}

		// Date range filtering
		if ( $date_from || $date_to ) {
			// For tickets: filter by event_date
			// For accommodation: filter by overlap (start <= filter_end AND end >= filter_start)
			if ( $domain === 'ingressos' ) {
				// Ticket date filtering
				if ( $date_from ) {
					$meta_query[] = array(
						'key'     => self::META_KEYS['event_date'],
						'value'   => str_replace( '-', '', $date_from ),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					);
				}
				if ( $date_to ) {
					$meta_query[] = array(
						'key'     => self::META_KEYS['event_date'],
						'value'   => str_replace( '-', '', $date_to ),
						'compare' => '<=',
						'type'    => 'NUMERIC',
					);
				}
			} elseif ( $domain === 'acomodacao' ) {
				// Accommodation overlap filtering
				if ( $date_from && $date_to ) {
					$meta_query[] = array(
						'key'     => self::META_KEYS['start_date'],
						'value'   => str_replace( '-', '', $date_to ),
						'compare' => '<=',
						'type'    => 'NUMERIC',
					);
					$meta_query[] = array(
						'key'     => self::META_KEYS['end_date'],
						'value'   => str_replace( '-', '', $date_from ),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					);
				}
			}
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query;
		}

		$query       = new \WP_Query( $args );
		$classifieds = array();

		foreach ( $query->posts as $post ) {
			$classifieds[] = self::format_classified( $post );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'classifieds' => $classifieds,
					'total'       => $query->found_posts,
					'page'        => $page,
					'pages'       => $query->max_num_pages,
					'per_page'    => $per_page,
				),
			),
			200
		);
	}

	/**
	 * REST: Get single classified
	 */
	public static function rest_get_classified( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Anúncio não encontrado.', 'apollo-social' ) ),
				404
			);
		}

		// Check visibility
		if ( $post->post_status !== 'publish' ) {
			$user_id = get_current_user_id();
			if ( ! $user_id || ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) ) {
				return new \WP_REST_Response(
					array( 'success' => false, 'message' => __( 'Anúncio não encontrado.', 'apollo-social' ) ),
					404
				);
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => self::format_classified( $post, true ),
			),
			200
		);
	}

	/**
	 * REST: Create classified
	 */
	public static function rest_create_classified( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		// Rate limiting: max 5 per day
		$today_count = self::get_user_today_count( $user_id );
		if ( $today_count >= 5 && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Limite diário de anúncios atingido (máx. 5).', 'apollo-social' ) ),
				429
			);
		}

		$title       = sanitize_text_field( $request->get_param( 'title' ) );
		$description = wp_kses_post( $request->get_param( 'description' ) ?? '' );
		$domain      = sanitize_text_field( $request->get_param( 'domain' ) ?? '' );
		$intent      = sanitize_text_field( $request->get_param( 'intent' ) ?? '' );
		$price       = sanitize_text_field( $request->get_param( 'price' ) ?? '' );
		$location    = sanitize_text_field( $request->get_param( 'location' ) ?? '' );

		// Validate required
		if ( empty( $title ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Título é obrigatório.', 'apollo-social' ) ),
				400
			);
		}

		if ( ! array_key_exists( $domain, self::DOMAINS ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Categoria inválida.', 'apollo-social' ) ),
				400
			);
		}

		if ( ! array_key_exists( $intent, self::INTENTS ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Tipo de anúncio inválido.', 'apollo-social' ) ),
				400
			);
		}

		// Domain-specific validation
		if ( $domain === 'ingressos' ) {
			$event_date = sanitize_text_field( $request->get_param( 'event_date' ) ?? '' );
			if ( empty( $event_date ) ) {
				return new \WP_REST_Response(
					array( 'success' => false, 'message' => __( 'Data do evento é obrigatória para ingressos.', 'apollo-social' ) ),
					400
				);
			}
		} elseif ( $domain === 'acomodacao' ) {
			$start_date = sanitize_text_field( $request->get_param( 'start_date' ) ?? '' );
			$end_date   = sanitize_text_field( $request->get_param( 'end_date' ) ?? '' );
			if ( empty( $start_date ) || empty( $end_date ) ) {
				return new \WP_REST_Response(
					array( 'success' => false, 'message' => __( 'Check-in e check-out são obrigatórios para acomodação.', 'apollo-social' ) ),
					400
				);
			}
		}

		// Create post
		$post_data = array(
			'post_title'   => $title,
			'post_content' => $description,
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish', // Auto-publish for logged in users
			'post_author'  => $user_id,
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Erro ao criar anúncio.', 'apollo-social' ) ),
				500
			);
		}

		// Set taxonomies
		wp_set_object_terms( $post_id, $domain, self::TAX_DOMAIN );
		wp_set_object_terms( $post_id, $intent, self::TAX_INTENT );

		// Set meta
		update_post_meta( $post_id, self::META_KEYS['price'], $price );
		update_post_meta( $post_id, self::META_KEYS['currency'], 'BRL' );
		update_post_meta( $post_id, self::META_KEYS['location'], $location );
		update_post_meta( $post_id, self::META_KEYS['views_count'], 0 );

		// Domain-specific meta
		if ( $domain === 'ingressos' ) {
			$event_date  = sanitize_text_field( $request->get_param( 'event_date' ) ?? '' );
			$event_title = sanitize_text_field( $request->get_param( 'event_title' ) ?? '' );
			update_post_meta( $post_id, self::META_KEYS['event_date'], str_replace( '-', '', $event_date ) );
			update_post_meta( $post_id, self::META_KEYS['event_title'], $event_title );
		} elseif ( $domain === 'acomodacao' ) {
			$start_date = sanitize_text_field( $request->get_param( 'start_date' ) ?? '' );
			$end_date   = sanitize_text_field( $request->get_param( 'end_date' ) ?? '' );
			$capacity   = (int) $request->get_param( 'capacity' );
			update_post_meta( $post_id, self::META_KEYS['start_date'], str_replace( '-', '', $start_date ) );
			update_post_meta( $post_id, self::META_KEYS['end_date'], str_replace( '-', '', $end_date ) );
			update_post_meta( $post_id, self::META_KEYS['capacity'], $capacity );
		}

		$post = get_post( $post_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Anúncio publicado!', 'apollo-social' ),
				'data'    => self::format_classified( $post ),
			),
			201
		);
	}

	/**
	 * REST: Update classified
	 */
	public static function rest_update_classified( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Anúncio não encontrado.', 'apollo-social' ) ),
				404
			);
		}

		// Permission check
		if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Sem permissão.', 'apollo-social' ) ),
				403
			);
		}

		$update_data = array( 'ID' => $id );

		if ( $request->has_param( 'title' ) ) {
			$update_data['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
		}

		if ( $request->has_param( 'description' ) ) {
			$update_data['post_content'] = wp_kses_post( $request->get_param( 'description' ) );
		}

		if ( $request->has_param( 'status' ) ) {
			$status = sanitize_text_field( $request->get_param( 'status' ) );
			if ( in_array( $status, array( 'publish', 'draft', 'trash' ), true ) ) {
				$update_data['post_status'] = $status;
			}
		}

		wp_update_post( $update_data );

		// Update meta
		$meta_map = array(
			'price'       => 'price',
			'location'    => 'location',
			'event_date'  => 'event_date',
			'event_title' => 'event_title',
			'start_date'  => 'start_date',
			'end_date'    => 'end_date',
			'capacity'    => 'capacity',
		);

		foreach ( $meta_map as $param => $key ) {
			if ( $request->has_param( $param ) ) {
				$value = sanitize_text_field( $request->get_param( $param ) );
				// Convert dates to YYYYMMDD
				if ( strpos( $key, 'date' ) !== false && strpos( $value, '-' ) !== false ) {
					$value = str_replace( '-', '', $value );
				}
				update_post_meta( $id, self::META_KEYS[ $key ], $value );
			}
		}

		// Update taxonomies
		if ( $request->has_param( 'domain' ) ) {
			$domain = sanitize_text_field( $request->get_param( 'domain' ) );
			if ( array_key_exists( $domain, self::DOMAINS ) ) {
				wp_set_object_terms( $id, $domain, self::TAX_DOMAIN );
			}
		}

		if ( $request->has_param( 'intent' ) ) {
			$intent = sanitize_text_field( $request->get_param( 'intent' ) );
			if ( array_key_exists( $intent, self::INTENTS ) ) {
				wp_set_object_terms( $id, $intent, self::TAX_INTENT );
			}
		}

		$post = get_post( $id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Anúncio atualizado.', 'apollo-social' ),
				'data'    => self::format_classified( $post ),
			),
			200
		);
	}

	/**
	 * REST: Delete classified
	 */
	public static function rest_delete_classified( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Anúncio não encontrado.', 'apollo-social' ) ),
				404
			);
		}

		if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Sem permissão.', 'apollo-social' ) ),
				403
			);
		}

		wp_trash_post( $id );

		return new \WP_REST_Response(
			array( 'success' => true, 'message' => __( 'Anúncio removido.', 'apollo-social' ) ),
			200
		);
	}

	/**
	 * REST: Report classified
	 */
	public static function rest_report_classified( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );
		$reason  = sanitize_text_field( $request->get_param( 'reason' ) ?? '' );

		$post = get_post( $id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'Anúncio não encontrado.', 'apollo-social' ) ),
				404
			);
		}

		// Store report
		$reports = get_post_meta( $id, '_classified_reports', true );
		if ( ! is_array( $reports ) ) {
			$reports = array();
		}

		$reports[] = array(
			'user_id'    => $user_id,
			'reason'     => $reason,
			'created_at' => current_time( 'mysql' ),
		);

		update_post_meta( $id, '_classified_reports', $reports );

		// If too many reports, set to pending review
		if ( count( $reports ) >= 3 ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'pending',
				)
			);
		}

		return new \WP_REST_Response(
			array( 'success' => true, 'message' => __( 'Denúncia registrada. Obrigado!', 'apollo-social' ) ),
			200
		);
	}

	/**
	 * REST: Safety acknowledgement (track that user saw safety modal)
	 */
	public static function rest_safety_ack( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$id      = (int) $request->get_param( 'id' );

		// Store acknowledgement in user meta
		$ack_list = get_user_meta( $user_id, '_classified_safety_ack', true );
		if ( ! is_array( $ack_list ) ) {
			$ack_list = array();
		}

		if ( ! in_array( $id, $ack_list, true ) ) {
			$ack_list[] = $id;
			update_user_meta( $user_id, '_classified_safety_ack', $ack_list );
		}

		return new \WP_REST_Response(
			array( 'success' => true, 'acknowledged' => true ),
			200
		);
	}

	/**
	 * Format classified for API response
	 */
	private static function format_classified( \WP_Post $post, bool $full = false ): array {
		$id = $post->ID;

		// Get taxonomies
		$domains = wp_get_object_terms( $id, self::TAX_DOMAIN, array( 'fields' => 'slugs' ) );
		$intents = wp_get_object_terms( $id, self::TAX_INTENT, array( 'fields' => 'slugs' ) );

		$domain = ! empty( $domains ) ? $domains[0] : '';
		$intent = ! empty( $intents ) ? $intents[0] : '';

		// Get meta
		$price       = get_post_meta( $id, self::META_KEYS['price'], true );
		$location    = get_post_meta( $id, self::META_KEYS['location'], true );
		$views       = (int) get_post_meta( $id, self::META_KEYS['views_count'], true );
		$event_date  = get_post_meta( $id, self::META_KEYS['event_date'], true );
		$event_title = get_post_meta( $id, self::META_KEYS['event_title'], true );
		$start_date  = get_post_meta( $id, self::META_KEYS['start_date'], true );
		$end_date    = get_post_meta( $id, self::META_KEYS['end_date'], true );
		$capacity    = get_post_meta( $id, self::META_KEYS['capacity'], true );

		// Thumbnail
		$thumbnail = '';
		if ( has_post_thumbnail( $id ) ) {
			$thumbnail = get_the_post_thumbnail_url( $id, 'medium' );
		}

		// Author
		$author = get_userdata( $post->post_author );

		$data = array(
			'id'          => $id,
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'excerpt'     => wp_trim_words( $post->post_content, 25 ),
			'domain'      => $domain,
			'domain_label' => self::DOMAINS[ $domain ] ?? $domain,
			'intent'      => $intent,
			'intent_label' => self::INTENTS[ $intent ] ?? $intent,
			'price'       => $price,
			'price_formatted' => $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : '',
			'location'    => $location,
			'thumbnail'   => $thumbnail,
			'permalink'   => get_permalink( $id ),
			'views'       => $views,
			'status'      => $post->post_status,
			'created_at'  => $post->post_date,
			'author'      => array(
				'id'           => (int) $post->post_author,
				'display_name' => $author ? $author->display_name : '',
				'avatar_url'   => get_avatar_url( $post->post_author, array( 'size' => 64 ) ),
			),
		);

		// Domain-specific fields
		if ( $domain === 'ingressos' ) {
			$data['event_date'] = self::format_date( $event_date );
			$data['event_title'] = $event_title;
		} elseif ( $domain === 'acomodacao' ) {
			$data['start_date'] = self::format_date( $start_date );
			$data['end_date']   = self::format_date( $end_date );
			$data['period']     = self::format_date( $start_date ) . ' - ' . self::format_date( $end_date );
			$data['capacity']   = $capacity;
		}

		// Full content for single view
		if ( $full ) {
			$data['content']     = apply_filters( 'the_content', $post->post_content );
			$data['raw_content'] = $post->post_content;

			// Gallery
			$gallery = get_post_meta( $id, self::META_KEYS['gallery'], true );
			$data['gallery'] = is_array( $gallery ) ? $gallery : array();

			// Author extended
			if ( $author ) {
				$data['author']['bio']         = get_the_author_meta( 'description', $author->ID );
				$data['author']['member_since'] = date_i18n( 'F Y', strtotime( $author->user_registered ) );
			}
		}

		return $data;
	}

	/**
	 * Format YYYYMMDD date to DD/MM/YYYY
	 */
	private static function format_date( string $date ): string {
		if ( strlen( $date ) !== 8 ) {
			return $date;
		}

		$year  = substr( $date, 0, 4 );
		$month = substr( $date, 4, 2 );
		$day   = substr( $date, 6, 2 );

		return "{$day}/{$month}/{$year}";
	}

	/**
	 * Get user's classified count for today (rate limiting)
	 */
	private static function get_user_today_count( int $user_id ): int {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'author'         => $user_id,
			'date_query'     => array(
				array(
					'after'     => 'today',
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new \WP_Query( $args );

		return $query->found_posts;
	}

	/**
	 * Track view count
	 */
	public static function track_view(): void {
		if ( is_singular( self::POST_TYPE ) ) {
			$id    = get_the_ID();
			$views = (int) get_post_meta( $id, self::META_KEYS['views_count'], true );
			update_post_meta( $id, self::META_KEYS['views_count'], $views + 1 );
		}
	}

	/**
	 * Admin columns
	 */
	public static function admin_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( $key === 'title' ) {
				$new_columns['classified_domain'] = __( 'Categoria', 'apollo-social' );
				$new_columns['classified_intent'] = __( 'Tipo', 'apollo-social' );
				$new_columns['classified_price']  = __( 'Preço', 'apollo-social' );
			}
		}

		return $new_columns;
	}

	/**
	 * Admin column content
	 */
	public static function admin_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'classified_domain':
				$terms = wp_get_object_terms( $post_id, self::TAX_DOMAIN, array( 'fields' => 'names' ) );
				echo esc_html( implode( ', ', $terms ) );
				break;

			case 'classified_intent':
				$terms = wp_get_object_terms( $post_id, self::TAX_INTENT, array( 'fields' => 'names' ) );
				echo esc_html( implode( ', ', $terms ) );
				break;

			case 'classified_price':
				$price = get_post_meta( $post_id, self::META_KEYS['price'], true );
				echo $price ? 'R$ ' . esc_html( number_format( (float) $price, 2, ',', '.' ) ) : '—';
				break;
		}
	}

	/**
	 * Single template
	 */
	public static function single_template( string $template ): string {
		if ( is_singular( self::POST_TYPE ) ) {
			$custom = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/classifieds/single.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}

		return $template;
	}

	/**
	 * Archive template
	 */
	public static function archive_template( string $template ): string {
		if ( is_post_type_archive( self::POST_TYPE ) ) {
			$custom = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/classifieds/archive.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}

		return $template;
	}
}
