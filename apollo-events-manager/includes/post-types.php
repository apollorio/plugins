<?php
// phpcs:ignoreFile
/**
 * Register Custom Post Types and Taxonomies
 * Apollo Events Manager - Independent CPT Registration
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo Post Types Class
 * Handles registration of all custom post types and taxonomies
 */
class Apollo_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 0 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 0 );
		add_action( 'init', array( $this, 'setup_meta_relationships' ), 10 );
		add_action( 'init', array( $this, 'register_meta_fields' ), 10 );
	}

	/**
	 * Register Custom Post Types
	 */
	public function register_post_types() {

		// ============================================
		// EVENT LISTING CPT
		// ============================================
		$event_labels = array(
			'name'                  => __( 'Eventos', 'apollo-events-manager' ),
			'singular_name'         => __( 'Evento', 'apollo-events-manager' ),
			'add_new'               => __( 'Adicionar Novo', 'apollo-events-manager' ),
			'add_new_item'          => __( 'Adicionar Novo Evento', 'apollo-events-manager' ),
			'edit_item'             => __( 'Editar Evento', 'apollo-events-manager' ),
			'new_item'              => __( 'Novo Evento', 'apollo-events-manager' ),
			'view_item'             => __( 'Ver Evento', 'apollo-events-manager' ),
			'view_items'            => __( 'Ver Eventos', 'apollo-events-manager' ),
			'search_items'          => __( 'Buscar Eventos', 'apollo-events-manager' ),
			'not_found'             => __( 'Nenhum evento encontrado', 'apollo-events-manager' ),
			'not_found_in_trash'    => __( 'Nenhum evento na lixeira', 'apollo-events-manager' ),
			'all_items'             => __( 'Todos os Eventos', 'apollo-events-manager' ),
			'archives'              => __( 'Arquivo de Eventos', 'apollo-events-manager' ),
			'attributes'            => __( 'Atributos do Evento', 'apollo-events-manager' ),
			'insert_into_item'      => __( 'Inserir no evento', 'apollo-events-manager' ),
			'uploaded_to_this_item' => __( 'Enviado para este evento', 'apollo-events-manager' ),
		);

		$event_args = array(
			'labels'                => $event_labels,
			'description'           => __( 'Eventos do Apollo::Rio', 'apollo-events-manager' ),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'evento',
				'with_front' => false,
				'feeds'      => true,
				'pages'      => true,
			),
			'capability_type'       => 'post',
			'capabilities'          => array(
				'edit_post'          => 'edit_event_listing',
				'read_post'          => 'read_event_listing',
				'delete_post'        => 'delete_event_listing',
				'edit_posts'         => 'edit_event_listings',
				'edit_others_posts'  => 'edit_others_event_listings',
				'publish_posts'      => 'publish_event_listings',
				'read_private_posts' => 'read_private_event_listings',
			),
			'map_meta_cap'          => true,
			'has_archive'           => 'eventos',
			'hierarchical'          => false,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-calendar-alt',
			'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'author', 'revisions' ),
			'show_in_rest'          => true,
			'rest_base'             => 'events',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		register_post_type( 'event_listing', $event_args );

		// ============================================
		// EVENT DJ CPT
		// ============================================
		$dj_labels = array(
			'name'               => __( 'DJs', 'apollo-events-manager' ),
			'singular_name'      => __( 'DJ', 'apollo-events-manager' ),
			'add_new'            => __( 'Adicionar Novo', 'apollo-events-manager' ),
			'add_new_item'       => __( 'Adicionar Novo DJ', 'apollo-events-manager' ),
			'edit_item'          => __( 'Editar DJ', 'apollo-events-manager' ),
			'new_item'           => __( 'Novo DJ', 'apollo-events-manager' ),
			'view_item'          => __( 'Ver DJ', 'apollo-events-manager' ),
			'search_items'       => __( 'Buscar DJs', 'apollo-events-manager' ),
			'not_found'          => __( 'Nenhum DJ encontrado', 'apollo-events-manager' ),
			'not_found_in_trash' => __( 'Nenhum DJ na lixeira', 'apollo-events-manager' ),
			'all_items'          => __( 'Todos os DJs', 'apollo-events-manager' ),
		);

		$dj_args = array(
			'labels'             => $dj_labels,
			'description'        => __( 'DJs do Apollo::Rio', 'apollo-events-manager' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => 'dj',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 6,
			'menu_icon'          => 'dashicons-admin-users',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'show_in_rest'       => true,
			'rest_base'          => 'djs',
		);

		register_post_type( 'event_dj', $dj_args );

		// ============================================
		// EVENT LOCAL CPT
		// ============================================
		$local_labels = array(
			'name'               => __( 'Locais', 'apollo-events-manager' ),
			'singular_name'      => __( 'Local', 'apollo-events-manager' ),
			'add_new'            => __( 'Adicionar Novo', 'apollo-events-manager' ),
			'add_new_item'       => __( 'Adicionar Novo Local', 'apollo-events-manager' ),
			'edit_item'          => __( 'Editar Local', 'apollo-events-manager' ),
			'new_item'           => __( 'Novo Local', 'apollo-events-manager' ),
			'view_item'          => __( 'Ver Local', 'apollo-events-manager' ),
			'search_items'       => __( 'Buscar Locais', 'apollo-events-manager' ),
			'not_found'          => __( 'Nenhum local encontrado', 'apollo-events-manager' ),
			'not_found_in_trash' => __( 'Nenhum local na lixeira', 'apollo-events-manager' ),
			'all_items'          => __( 'Todos os Locais', 'apollo-events-manager' ),
		);

		$local_args = array(
			'labels'             => $local_labels,
			'description'        => __( 'Locais de eventos do Apollo::Rio', 'apollo-events-manager' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => 'local',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 7,
			'menu_icon'          => 'dashicons-location',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'show_in_rest'       => true,
			'rest_base'          => 'locals',
		);

		register_post_type( 'event_local', $local_args );

		// Log registration
		if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			error_log( '✅ Apollo CPTs registered: event_listing, event_dj, event_local' );
		}
	}

	/**
	 * Register Taxonomies
	 */
	public function register_taxonomies() {

		// ============================================
		// EVENT LISTING CATEGORY
		// ============================================
		$category_labels = array(
			'name'              => __( 'Categorias', 'apollo-events-manager' ),
			'singular_name'     => __( 'Categoria', 'apollo-events-manager' ),
			'search_items'      => __( 'Buscar Categorias', 'apollo-events-manager' ),
			'all_items'         => __( 'Todas as Categorias', 'apollo-events-manager' ),
			'parent_item'       => __( 'Categoria Pai', 'apollo-events-manager' ),
			'parent_item_colon' => __( 'Categoria Pai:', 'apollo-events-manager' ),
			'edit_item'         => __( 'Editar Categoria', 'apollo-events-manager' ),
			'update_item'       => __( 'Atualizar Categoria', 'apollo-events-manager' ),
			'add_new_item'      => __( 'Adicionar Nova Categoria', 'apollo-events-manager' ),
			'new_item_name'     => __( 'Nome da Nova Categoria', 'apollo-events-manager' ),
			'menu_name'         => __( 'Categorias', 'apollo-events-manager' ),
		);

		register_taxonomy(
			'event_listing_category',
			'event_listing',
			array(
				'labels'            => $category_labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rest_base'         => 'event-categories',
				'rewrite'           => array(
					'slug'       => 'categoria-evento',
					'with_front' => false,
				),
			)
		);

		// ============================================
		// EVENT LISTING TYPE
		// ============================================
		$type_labels = array(
			'name'              => __( 'Tipos', 'apollo-events-manager' ),
			'singular_name'     => __( 'Tipo', 'apollo-events-manager' ),
			'search_items'      => __( 'Buscar Tipos', 'apollo-events-manager' ),
			'all_items'         => __( 'Todos os Tipos', 'apollo-events-manager' ),
			'parent_item'       => __( 'Tipo Pai', 'apollo-events-manager' ),
			'parent_item_colon' => __( 'Tipo Pai:', 'apollo-events-manager' ),
			'edit_item'         => __( 'Editar Tipo', 'apollo-events-manager' ),
			'update_item'       => __( 'Atualizar Tipo', 'apollo-events-manager' ),
			'add_new_item'      => __( 'Adicionar Novo Tipo', 'apollo-events-manager' ),
			'new_item_name'     => __( 'Nome do Novo Tipo', 'apollo-events-manager' ),
			'menu_name'         => __( 'Tipos', 'apollo-events-manager' ),
		);

		register_taxonomy(
			'event_listing_type',
			'event_listing',
			array(
				'labels'            => $type_labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rest_base'         => 'event-types',
				'rewrite'           => array(
					'slug'       => 'tipo-evento',
					'with_front' => false,
				),
			)
		);

		// ============================================
		// EVENT LISTING TAG
		// ============================================
		$tag_labels = array(
			'name'          => __( 'Tags', 'apollo-events-manager' ),
			'singular_name' => __( 'Tag', 'apollo-events-manager' ),
			'search_items'  => __( 'Buscar Tags', 'apollo-events-manager' ),
			'all_items'     => __( 'Todas as Tags', 'apollo-events-manager' ),
			'edit_item'     => __( 'Editar Tag', 'apollo-events-manager' ),
			'update_item'   => __( 'Atualizar Tag', 'apollo-events-manager' ),
			'add_new_item'  => __( 'Adicionar Nova Tag', 'apollo-events-manager' ),
			'new_item_name' => __( 'Nome da Nova Tag', 'apollo-events-manager' ),
			'menu_name'     => __( 'Tags', 'apollo-events-manager' ),
		);

		register_taxonomy(
			'event_listing_tag',
			'event_listing',
			array(
				'labels'            => $tag_labels,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rest_base'         => 'event-tags',
				'rewrite'           => array(
					'slug'       => 'tag-evento',
					'with_front' => false,
				),
			)
		);

		// ============================================
		// EVENT SOUNDS
		// ============================================
		$sounds_labels = array(
			'name'              => __( 'Sons', 'apollo-events-manager' ),
			'singular_name'     => __( 'Som', 'apollo-events-manager' ),
			'search_items'      => __( 'Buscar Sons', 'apollo-events-manager' ),
			'all_items'         => __( 'Todos os Sons', 'apollo-events-manager' ),
			'parent_item'       => __( 'Som Pai', 'apollo-events-manager' ),
			'parent_item_colon' => __( 'Som Pai:', 'apollo-events-manager' ),
			'edit_item'         => __( 'Editar Som', 'apollo-events-manager' ),
			'update_item'       => __( 'Atualizar Som', 'apollo-events-manager' ),
			'add_new_item'      => __( 'Adicionar Novo Som', 'apollo-events-manager' ),
			'new_item_name'     => __( 'Nome do Novo Som', 'apollo-events-manager' ),
			'menu_name'         => __( 'Sons', 'apollo-events-manager' ),
		);

		register_taxonomy(
			'event_sounds',
			'event_listing',
			array(
				'labels'            => $sounds_labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rest_base'         => 'event-sounds',
				'rewrite'           => array(
					'slug'       => 'som',
					'with_front' => false,
				),
			)
		);

		// Log registration
		if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			error_log( '✅ Apollo Taxonomies registered: event_listing_category, event_listing_type, event_listing_tag, event_sounds' );
		}
	}

	/**
	 * Setup Meta Relationships
	 * Critical for many-to-many and many-to-one relationships
	 */
	public function setup_meta_relationships() {

		// Link event to DJs (many-to-many)
		// Stores serialized array of DJ post IDs
		add_post_type_support( 'event_listing', 'custom-fields' );

		// Link event to Local (many-to-one)
		// Stores single Local post ID
		add_post_type_support( 'event_listing', 'custom-fields' );

		// Timetable structure (array of DJ schedules)
		// Format: array( array('dj' => ID, 'start' => 'HH:MM', 'end' => 'HH:MM') )
		add_post_type_support( 'event_listing', 'custom-fields' );

		// Log setup
		if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			error_log( '✅ Apollo Meta Relationships configured' );
		}
	}

	/**
	 * Register Meta Fields
	 * Makes custom fields available in REST API and validates data types
	 */
	public function register_meta_fields() {

		// ============================================
		// EVENT META FIELDS
		// ============================================
		$event_meta_fields = array(
			'_event_title'                     => 'string',
			'_event_banner'                    => 'string', 
			// URL or attachment ID
							'_event_video_url' => 'string',
			'_event_start_date'                => 'string', 
			// YYYY-MM-DD HH:MM:SS
							'_event_end_date'  => 'string',
			'_event_start_time'                => 'string', 
			// HH:MM:SS
							'_event_end_time'  => 'string',
			'_event_location'                  => 'string',
			'_event_country'                   => 'string',
			'_tickets_ext'                     => 'string', 
			// External ticket URL
							'_cupom_ario'      => 'integer', 
			// 0 or 1
							'_event_dj_ids'    => 'string', 
			// Serialized array
							'_event_local_ids' => 'integer', 
			// Single Local ID
							'_event_timetable' => 'string', 
			// Serialized array
							'_3_imagens_promo' => 'string', 
			// Serialized array of image URLs
							'_imagem_final'    => 'string', 
			// Serialized array
							'_favorites_count' => 'integer',
		);

		foreach ( $event_meta_fields as $meta_key => $type ) {
			register_post_meta(
				'event_listing',
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => ( $type === 'integer' ) ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_event_listings' );
					},
				)
			);
		}

		// ============================================
		// LOCAL META FIELDS
		// ============================================
		$local_meta_fields = array(
			'_local_name'        => 'string',
			'_local_description' => 'string',
			'_local_address'     => 'string',
			'_local_city'        => 'string',
			'_local_state'       => 'string',
			'_local_latitude'    => 'string',
			'_local_longitude'   => 'string',
			'_local_lat'         => 'string',
			'_local_lng'         => 'string',
			'_local_website'     => 'string',
			'_local_facebook'    => 'string',
			'_local_instagram'   => 'string',
			'_local_image_1'     => 'string',
			'_local_image_2'     => 'string',
			'_local_image_3'     => 'string',
			'_local_image_4'     => 'string',
			'_local_image_5'     => 'string',
		);

		foreach ( $local_meta_fields as $meta_key => $type ) {
			register_post_meta(
				'event_local',
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}

		// ============================================
		// DJ META FIELDS
		// ============================================
		$dj_meta_fields = array(
			// Basic Info
			'_dj_name'                               => 'string',
			'_dj_bio'                                => 'string',
			'_dj_image'                              => 'string', 
			// URL or attachment ID

							// Social Media & Streaming Platforms
							'_dj_website'            => 'string',
			'_dj_instagram'                          => 'string',
			'_dj_facebook'                           => 'string',
			'_dj_soundcloud'                         => 'string',
			'_dj_bandcamp'                           => 'string', 
			// NEW: Bandcamp profile
							'_dj_spotify'            => 'string', 
			// NEW: Spotify artist profile
							'_dj_youtube'            => 'string', 
			// NEW: YouTube channel
							'_dj_mixcloud'           => 'string', 
			// NEW: Mixcloud profile
							'_dj_beatport'           => 'string', 
			// NEW: Beatport artist page
							'_dj_resident_advisor'   => 'string', 
			// NEW: Resident Advisor profile
							'_dj_twitter'            => 'string', 
			// NEW: Twitter/X handle
							'_dj_tiktok'             => 'string', 
			// NEW: TikTok profile

							// Professional Content
							'_dj_original_project_1' => 'string', 
			// Original Project 1
							'_dj_original_project_2' => 'string', 
			// Original Project 2
							'_dj_original_project_3' => 'string', 
			// Original Project 3
							'_dj_set_url'            => 'string', 
			// DJ Set URL (SoundCloud, YouTube, etc)
							'_dj_media_kit_url'      => 'string', 
			// Media Kit download URL
							'_dj_rider_url'          => 'string', 
			// Rider download URL
							'_dj_mix_url'            => 'string', 
		// DJ Mix URL
		);

		foreach ( $dj_meta_fields as $meta_key => $type ) {
			register_post_meta(
				'event_dj',
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}

		// Log registration
		if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			error_log( '✅ Apollo Meta Fields registered' );
		}
	}

	/**
	 * Flush rewrite rules on activation
	 * Called by activation hook in main plugin file
	 */
	public static function flush_rewrite_rules_on_activation() {
		// ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
		$last_flush = get_transient( 'apollo_rewrite_rules_last_flush' );
		if ( $last_flush && ( time() - $last_flush ) < 300 ) {
			// Já foi flushado recentemente, pular
			error_log( '✅ Apollo: Rewrite rules já foram flushadas recentemente, pulando...' );
			return;
		}

		$instance = new self();
		$instance->register_post_types();
		$instance->register_taxonomies();
		flush_rewrite_rules( false ); 
		// Don't force hard flush

		// Marcar timestamp do flush
		set_transient( 'apollo_rewrite_rules_last_flush', time(), 600 ); 
		// 10 minutos

		if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
			error_log( '✅ Apollo Rewrite Rules flushed on activation' );
		}
	}
}

// Initialize only if not called during activation hook
// During activation, we call flush_rewrite_rules_on_activation() directly
if ( ! defined( 'APOLLO_EVENTS_MANAGER_ACTIVATING' ) ) {
	new Apollo_Post_Types();
}
