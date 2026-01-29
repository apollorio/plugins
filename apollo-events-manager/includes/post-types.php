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

namespace Apollo\Events;

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

use Apollo_Core\Apollo_Identifiers as ID;

/**
 * Apollo Post Types Class
 * Handles registration of all custom post types and taxonomies
 */
class Apollo_Post_Types
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', [ $this, 'register_post_types' ], 0);
        add_action('init', [ $this, 'register_taxonomies' ], 0);
        add_action('init', [ $this, 'setup_meta_relationships' ], 10);
        add_action('init', [ $this, 'register_meta_fields' ], 10);
    }

    /**
     * Register Custom Post Types
     */
    public function register_post_types()
    {

        // ============================================
        // EVENT LISTING CPT
        // ============================================
        $event_labels = [
            'name'                  => __('Eventos', 'apollo-events-manager'),
            'singular_name'         => __('Evento', 'apollo-events-manager'),
            'add_new'               => __('Adicionar Novo', 'apollo-events-manager'),
            'add_new_item'          => __('Adicionar Novo Evento', 'apollo-events-manager'),
            'edit_item'             => __('Editar Evento', 'apollo-events-manager'),
            'new_item'              => __('Novo Evento', 'apollo-events-manager'),
            'view_item'             => __('Ver Evento', 'apollo-events-manager'),
            'view_items'            => __('Ver Eventos', 'apollo-events-manager'),
            'search_items'          => __('Buscar Eventos', 'apollo-events-manager'),
            'not_found'             => __('Nenhum evento encontrado', 'apollo-events-manager'),
            'not_found_in_trash'    => __('Nenhum evento na lixeira', 'apollo-events-manager'),
            'all_items'             => __('Todos os Eventos', 'apollo-events-manager'),
            'archives'              => __('Arquivo de Eventos', 'apollo-events-manager'),
            'attributes'            => __('Atributos do Evento', 'apollo-events-manager'),
            'insert_into_item'      => __('Inserir no evento', 'apollo-events-manager'),
            'uploaded_to_this_item' => __('Enviado para este evento', 'apollo-events-manager'),
        ];

        $event_args = [
            'labels'             => $event_labels,
            'description'        => __('Eventos do Apollo::Rio', 'apollo-events-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => 'evento',
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true,
            ],
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'has_archive'           => 'eventos',
            'hierarchical'          => false,
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-analytics',
            'supports'              => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'author', 'revisions' ],
            'show_in_rest'          => true,
            'rest_base'             => 'events',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];

        // Only register if not already registered (prevents conflicts with Apollo Core fallback)
        if ( ! post_type_exists( ID::CPT_EVENT_LISTING ) ) {
            register_post_type( ID::CPT_EVENT_LISTING, $event_args );
        }

        // ============================================
        // EVENT DJ CPT
        // ============================================
        $dj_labels = [
            'name'               => __('DJs', 'apollo-events-manager'),
            'singular_name'      => __('DJ', 'apollo-events-manager'),
            'add_new'            => __('Adicionar Novo', 'apollo-events-manager'),
            'add_new_item'       => __('Adicionar Novo DJ', 'apollo-events-manager'),
            'edit_item'          => __('Editar DJ', 'apollo-events-manager'),
            'new_item'           => __('Novo DJ', 'apollo-events-manager'),
            'view_item'          => __('Ver DJ', 'apollo-events-manager'),
            'search_items'       => __('Buscar DJs', 'apollo-events-manager'),
            'not_found'          => __('Nenhum DJ encontrado', 'apollo-events-manager'),
            'not_found_in_trash' => __('Nenhum DJ na lixeira', 'apollo-events-manager'),
            'all_items'          => __('Todos os DJs', 'apollo-events-manager'),
        ];

        $dj_args = [
            'labels'             => $dj_labels,
            'description'        => __('DJs do Apollo::Rio', 'apollo-events-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => 'dj',
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'has_archive'     => true,
            'hierarchical'    => false,
            'menu_position'   => 6,
            'menu_icon'       => 'dashicons-admin-users',
            'supports'        => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'show_in_rest'    => true,
            'rest_base'       => 'djs',
        ];

        // Only register if not already registered (prevents conflicts with Apollo Core fallback)
        if ( ! post_type_exists( ID::CPT_EVENT_DJ ) ) {
            register_post_type( ID::CPT_EVENT_DJ, $dj_args );
        }

        // ============================================
        // EVENT LOCAL CPT
        // ============================================
        $local_labels = [
            'name'               => __('Locais', 'apollo-events-manager'),
            'singular_name'      => __('Local', 'apollo-events-manager'),
            'add_new'            => __('Adicionar Novo', 'apollo-events-manager'),
            'add_new_item'       => __('Adicionar Novo Local', 'apollo-events-manager'),
            'edit_item'          => __('Editar Local', 'apollo-events-manager'),
            'new_item'           => __('Novo Local', 'apollo-events-manager'),
            'view_item'          => __('Ver Local', 'apollo-events-manager'),
            'search_items'       => __('Buscar Locais', 'apollo-events-manager'),
            'not_found'          => __('Nenhum local encontrado', 'apollo-events-manager'),
            'not_found_in_trash' => __('Nenhum local na lixeira', 'apollo-events-manager'),
            'all_items'          => __('Todos os Locais', 'apollo-events-manager'),
        ];

        $local_args = [
            'labels'             => $local_labels,
            'description'        => __('Locais de eventos do Apollo::Rio', 'apollo-events-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => 'local',
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'has_archive'     => true,
            'hierarchical'    => false,
            'menu_position'   => 7,
            'menu_icon'       => 'dashicons-location',
            'supports'        => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'show_in_rest'    => true,
            'rest_base'       => 'locals',
        ];

        // Only register if not already registered (prevents conflicts with Apollo Core fallback)
        if ( ! post_type_exists( ID::CPT_EVENT_LOCAL ) ) {
            register_post_type( ID::CPT_EVENT_LOCAL, $local_args );
        }

        // Log registration
        if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
            error_log('✅ Apollo CPTs registered: event_listing, event_dj, event_local');
        }
    }

    /**
     * Register Taxonomies
     */
    public function register_taxonomies()
    {

        // ============================================
        // EVENT LISTING CATEGORY
        // ============================================
        $category_labels = [
            'name'              => __('Categorias', 'apollo-events-manager'),
            'singular_name'     => __('Categoria', 'apollo-events-manager'),
            'search_items'      => __('Buscar Categorias', 'apollo-events-manager'),
            'all_items'         => __('Todas as Categorias', 'apollo-events-manager'),
            'parent_item'       => __('Categoria Pai', 'apollo-events-manager'),
            'parent_item_colon' => __('Categoria Pai:', 'apollo-events-manager'),
            'edit_item'         => __('Editar Categoria', 'apollo-events-manager'),
            'update_item'       => __('Atualizar Categoria', 'apollo-events-manager'),
            'add_new_item'      => __('Adicionar Nova Categoria', 'apollo-events-manager'),
            'new_item_name'     => __('Nome da Nova Categoria', 'apollo-events-manager'),
            'menu_name'         => __('Categorias', 'apollo-events-manager'),
        ];

        register_taxonomy(
            ID::TAX_EVENT_CATEGORY,
            ID::CPT_EVENT_LISTING,
            [
                'labels'            => $category_labels,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'show_in_rest'      => true,
                'rest_base'         => 'event-categories',
                'rewrite'           => [
                    'slug'       => 'categoria-evento',
                    'with_front' => false,
                ],
            ]
        );

        // ============================================
        // EVENT LISTING TYPE
        // ============================================
        $type_labels = [
            'name'              => __('Tipos', 'apollo-events-manager'),
            'singular_name'     => __('Tipo', 'apollo-events-manager'),
            'search_items'      => __('Buscar Tipos', 'apollo-events-manager'),
            'all_items'         => __('Todos os Tipos', 'apollo-events-manager'),
            'parent_item'       => __('Tipo Pai', 'apollo-events-manager'),
            'parent_item_colon' => __('Tipo Pai:', 'apollo-events-manager'),
            'edit_item'         => __('Editar Tipo', 'apollo-events-manager'),
            'update_item'       => __('Atualizar Tipo', 'apollo-events-manager'),
            'add_new_item'      => __('Adicionar Novo Tipo', 'apollo-events-manager'),
            'new_item_name'     => __('Nome do Novo Tipo', 'apollo-events-manager'),
            'menu_name'         => __('Tipos', 'apollo-events-manager'),
        ];

        register_taxonomy(
            ID::TAX_EVENT_TYPE,
            ID::CPT_EVENT_LISTING,
            [
                'labels'            => $type_labels,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'show_in_rest'      => true,
                'rest_base'         => 'event-types',
                'rewrite'           => [
                    'slug'       => 'tipo-evento',
                    'with_front' => false,
                ],
            ]
        );

        // ============================================
        // EVENT LISTING TAG
        // ============================================
        $tag_labels = [
            'name'          => __('Tags', 'apollo-events-manager'),
            'singular_name' => __('Tag', 'apollo-events-manager'),
            'search_items'  => __('Buscar Tags', 'apollo-events-manager'),
            'all_items'     => __('Todas as Tags', 'apollo-events-manager'),
            'edit_item'     => __('Editar Tag', 'apollo-events-manager'),
            'update_item'   => __('Atualizar Tag', 'apollo-events-manager'),
            'add_new_item'  => __('Adicionar Nova Tag', 'apollo-events-manager'),
            'new_item_name' => __('Nome da Nova Tag', 'apollo-events-manager'),
            'menu_name'     => __('Tags', 'apollo-events-manager'),
        ];

        register_taxonomy(
            ID::TAX_EVENT_TAG,
            ID::CPT_EVENT_LISTING,
            [
                'labels'            => $tag_labels,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'show_in_rest'      => true,
                'rest_base'         => 'event-tags',
                'rewrite'           => [
                    'slug'       => 'tag-evento',
                    'with_front' => false,
                ],
            ]
        );

        // ============================================
        // EVENT SOUNDS
        // ============================================
        $sounds_labels = [
            'name'              => __('Sons', 'apollo-events-manager'),
            'singular_name'     => __('Som', 'apollo-events-manager'),
            'search_items'      => __('Buscar Sons', 'apollo-events-manager'),
            'all_items'         => __('Todos os Sons', 'apollo-events-manager'),
            'parent_item'       => __('Som Pai', 'apollo-events-manager'),
            'parent_item_colon' => __('Som Pai:', 'apollo-events-manager'),
            'edit_item'         => __('Editar Som', 'apollo-events-manager'),
            'update_item'       => __('Atualizar Som', 'apollo-events-manager'),
            'add_new_item'      => __('Adicionar Novo Som', 'apollo-events-manager'),
            'new_item_name'     => __('Nome do Novo Som', 'apollo-events-manager'),
            'menu_name'         => __('Sons', 'apollo-events-manager'),
        ];

        register_taxonomy(
            ID::TAX_EVENT_SOUNDS,
            ID::CPT_EVENT_LISTING,
            [
                'labels'            => $sounds_labels,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'show_in_rest'      => true,
                'rest_base'         => 'event-sounds',
                'rewrite'           => [
                    'slug'       => 'som',
                    'with_front' => false,
                ],
            ]
        );

        // ============================================
        // EVENT SEASONS (TEMPORADA)
        // ============================================
        $season_labels = [
            'name'              => __( 'Temporadas', 'apollo-events-manager' ),
            'singular_name'     => __( 'Temporada', 'apollo-events-manager' ),
            'search_items'      => __( 'Buscar Temporadas', 'apollo-events-manager' ),
            'all_items'         => __( 'Todas', 'apollo-events-manager' ),
            'parent_item'       => __( 'Temporada Pai', 'apollo-events-manager' ),
            'parent_item_colon' => __( 'Temporada Pai:', 'apollo-events-manager' ),
            'edit_item'         => __( 'Editar Temporada', 'apollo-events-manager' ),
            'update_item'       => __( 'Atualizar Temporada', 'apollo-events-manager' ),
            'add_new_item'      => __( 'Adicionar Nova Temporada', 'apollo-events-manager' ),
            'new_item_name'     => __( 'Nome da Nova Temporada', 'apollo-events-manager' ),
            'menu_name'         => __( 'Temporadas', 'apollo-events-manager' ),
        ];

        // Get connected post types
        $season_post_types = [ 'event_listing' ];

        // Connect to classifieds if apollo-social is active
        if ( class_exists( '\Apollo\Social\Modules\Classifieds\ClassifiedsModule' ) ) {
            $season_post_types[] = 'apollo_classified';
        }

        register_taxonomy(
            ID::TAX_EVENT_SEASON,
            $season_post_types,
            [
                'labels'            => $season_labels,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'show_in_rest'      => true,
                'rest_base'         => 'event-seasons',
                'rewrite'           => [
                    'slug'       => 'temporada',
                    'with_front' => false,
                ],
                'meta_box_cb'       => [ __CLASS__, 'season_meta_box_callback' ],
                'show_in_quick_edit' => false,
            ]
        );

        // Insert default season terms on first run
        self::insert_default_seasons();

        // Log registration
        if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
            error_log( '✅ Apollo Taxonomies registered: event_listing_category, event_listing_type, event_listing_tag, event_sounds, event_season' );
        }
    }

    /**
     * Setup Meta Relationships
     * Critical for many-to-many and many-to-one relationships
     */
    public function setup_meta_relationships()
    {

        // Link event to DJs (many-to-many)
        // Stores serialized array of DJ post IDs
        add_post_type_support(ID::CPT_EVENT_LISTING, 'custom-fields');

        // Link event to Local (many-to-one)
        // Stores single Local post ID
        add_post_type_support(ID::CPT_EVENT_LISTING, 'custom-fields');

        // Timetable structure (array of DJ schedules)
        // Format: array( array('dj' => ID, 'start' => 'HH:MM', 'end' => 'HH:MM') )
        add_post_type_support(ID::CPT_EVENT_LISTING, 'custom-fields');

        // Log setup
        if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
            error_log('✅ Apollo Meta Relationships configured');
        }
    }

    /**
     * Insert default season terms
     * Called during taxonomy registration
     *
     * @return void
     */
    public static function insert_default_seasons() {
        // Check if already inserted
        $seasons_inserted = get_option( 'apollo_event_seasons_inserted', false );

        if ( $seasons_inserted ) {
            return;
        }

        $default_seasons = [
            'verao-26'    => 'Verão\'26',
            'carnaval-26' => 'Carnaval',
            'rir-26'      => 'RiR\'26',
            'bey-26'      => 'Bey\'26',
        ];

        foreach ( $default_seasons as $slug => $name ) {
            if ( ! term_exists( $slug, ID::TAX_EVENT_SEASON ) ) {
                wp_insert_term(
                    $name,
                    ID::TAX_EVENT_SEASON,
                    [
                        'slug' => $slug,
                    ]
                );
            }
        }

        // Mark as inserted
        update_option( 'apollo_event_seasons_inserted', true, false );

        if ( defined( 'APOLLO_DEBUG' ) && APOLLO_DEBUG ) {
            error_log( '✅ Apollo: Default seasons inserted' );
        }
    }

    /**
     * Custom meta box callback for seasons taxonomy
     * Shows "Todas" as default option
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public static function season_meta_box_callback( $post ) {
        $taxonomy = ID::TAX_EVENT_SEASON;
        $tax_obj  = get_taxonomy( $taxonomy );

        if ( ! $tax_obj ) {
            return;
        }

        $terms        = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ] );
        $post_terms   = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
        $current_term = ! empty( $post_terms ) ? $post_terms[0] : 0;

        ?>
        <div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">
            <input type="hidden" name="tax_input[<?php echo esc_attr( $taxonomy ); ?>][]" value="0" />
            <div class="tabs-panel">
                <ul class="categorychecklist form-no-clear">
                    <li>
                        <label class="selectit">
                            <input type="radio"
                                   name="tax_input[<?php echo esc_attr( $taxonomy ); ?>][]"
                                   value="0"
                                   <?php checked( 0, $current_term ); ?> />
                            <?php esc_html_e( 'Todas', 'apollo-events-manager' ); ?>
                        </label>
                    </li>
                    <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                        <?php foreach ( $terms as $term ) : ?>
                            <li id="<?php echo esc_attr( $taxonomy ); ?>-<?php echo esc_attr( $term->term_id ); ?>">
                                <label class="selectit">
                                    <input type="radio"
                                           name="tax_input[<?php echo esc_attr( $taxonomy ); ?>][]"
                                           value="<?php echo esc_attr( $term->term_id ); ?>"
                                           <?php checked( $term->term_id, $current_term ); ?> />
                                    <?php echo esc_html( $term->name ); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Register Meta Fields
     * Makes custom fields available in REST API and validates data types
     */
    public function register_meta_fields()
    {

        // ============================================
        // EVENT META FIELDS
        // ============================================
        $event_meta_fields = [
            '_event_title'  => 'string',
            '_event_banner' => 'string',
            // URL or attachment ID
                            '_event_video_url' => 'string',
            '_event_start_date'                => 'string',
            // YYYY-MM-DD HH:MM:SS
                            '_event_end_date' => 'string',
            '_event_start_time'               => 'string',
            // HH:MM:SS
                            '_event_end_time' => 'string',
            '_event_location'                 => 'string',
            '_event_country'                  => 'string',
            '_tickets_ext'                    => 'string',
            // External ticket URL
                            '_cupom_ario' => 'integer',
            // 0 or 1
                            '_event_dj_ids' => 'string',
            // Serialized array
                            '_event_local_ids' => 'integer',
            // Single Local ID
                            '_event_timetable' => 'string',
            // Serialized array
                            '_3_imagens_promo' => 'string',
            // Serialized array of image URLs
                            '_imagem_final' => 'string',
            // Serialized array
                            '_favorites_count' => 'integer',
            '_event_season_id'                => 'integer',
            // Season taxonomy term ID (correlation)
        ];

        foreach ($event_meta_fields as $meta_key => $type) {
            register_post_meta(
                ID::CPT_EVENT_LISTING,
                $meta_key,
                [
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => $type,
                    'sanitize_callback' => ($type === 'integer') ? 'absint' : 'sanitize_text_field',
                    'auth_callback'     => function () {
                        return current_user_can('edit_event_listings');
                    },
                ]
            );
        }

        // ============================================
        // LOCAL META FIELDS
        // ============================================
        $local_meta_fields = [
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
        ];

        foreach ($local_meta_fields as $meta_key => $type) {
            register_post_meta(
                ID::CPT_EVENT_LOCAL,
                $meta_key,
                [
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => $type,
                    'sanitize_callback' => 'sanitize_text_field',
                ]
            );
        }

        // ============================================
        // DJ META FIELDS
        // ============================================
        $dj_meta_fields = [
            // Basic Info
            '_dj_name'  => 'string',
            '_dj_bio'   => 'string',
            '_dj_image' => 'string',
            // URL or attachment ID

                            // Social Media & Streaming Platforms
                            '_dj_website' => 'string',
            '_dj_instagram'               => 'string',
            '_dj_facebook'                => 'string',
            '_dj_soundcloud'              => 'string',
            '_dj_bandcamp'                => 'string',
            // NEW: Bandcamp profile
                            '_dj_spotify' => 'string',
            // NEW: Spotify artist profile
                            '_dj_youtube' => 'string',
            // NEW: YouTube channel
                            '_dj_mixcloud' => 'string',
            // NEW: Mixcloud profile
                            '_dj_beatport' => 'string',
            // NEW: Beatport artist page
                            '_dj_resident_advisor' => 'string',
            // NEW: Resident Advisor profile
                            '_dj_twitter' => 'string',
            // NEW: Twitter/X handle
                            '_dj_tiktok' => 'string',
            // NEW: TikTok profile

                            // Professional Content
                            '_dj_original_project_1' => 'string',
            // Original Project 1
                            '_dj_original_project_2' => 'string',
            // Original Project 2
                            '_dj_original_project_3' => 'string',
            // Original Project 3
                            '_dj_set_url' => 'string',
            // DJ Set URL (SoundCloud, YouTube, etc)
                            '_dj_media_kit_url' => 'string',
            // Media Kit download URL
                            '_dj_rider_url' => 'string',
            // Rider download URL
                            '_dj_mix_url' => 'string',
        // DJ Mix URL
        ];

        foreach ($dj_meta_fields as $meta_key => $type) {
            register_post_meta(
                ID::CPT_EVENT_DJ,
                $meta_key,
                [
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => $type,
                    'sanitize_callback' => 'sanitize_text_field',
                ]
            );
        }

        // Log registration
        if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
            error_log('✅ Apollo Meta Fields registered');
        }
    }

    /**
     * Flush rewrite rules on activation
     * Called by activation hook in main plugin file
     */
    public static function flush_rewrite_rules_on_activation()
    {
        // ✅ Verificar se rewrite rules já foram flushadas recentemente (últimos 5 minutos)
        $last_flush = get_transient('apollo_rewrite_rules_last_flush');
        if ($last_flush && (time() - $last_flush) < 300) {
            // Já foi flushado recentemente, pular
            error_log('✅ Apollo: Rewrite rules já foram flushadas recentemente, pulando...');

            return;
        }

        $instance = new self();
        $instance->register_post_types();
        $instance->register_taxonomies();
        flush_rewrite_rules(false);
        // Don't force hard flush

        // Marcar timestamp do flush
        set_transient('apollo_rewrite_rules_last_flush', time(), 600);
        // 10 minutos

        if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
            error_log('✅ Apollo Rewrite Rules flushed on activation');
        }
    }
}

// Initialize only if not called during activation hook
// During activation, we call flush_rewrite_rules_on_activation() directly
if (! defined('APOLLO_EVENTS_MANAGER_ACTIVATING')) {
    new Apollo_Post_Types();
}

/**
 * Check for rewrite rule conflicts and flush if necessary
 * This runs once after init to detect if another plugin registered event_listing with wrong slug
 */
add_action(
    'wp_loaded',
    function () {
        // Only run in admin and only once per session
        if (! is_admin() || get_transient('apollo_rewrite_conflict_checked')) {
            return;
        }

        // Mark as checked for this session
        set_transient('apollo_rewrite_conflict_checked', 1, 3600);

        // Get the registered rewrite rules
        $post_type_obj = get_post_type_object('event_listing');
        if (! $post_type_obj || ! isset($post_type_obj->rewrite['slug'])) {
            return;
        }

        // Check if the slug is correct (should be 'evento', not 'events')
        $current_slug = $post_type_obj->rewrite['slug'];
        if ($current_slug !== 'evento') {
            // Conflict detected! Log warning but do NOT flush at runtime.
            // Runtime flush causes performance issues and 404 race conditions.
            // Instead, set a flag for admin notice.
            if (defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
                error_log('⚠️ Apollo: Rewrite conflict detected! Current slug: ' . $current_slug . ', expected: evento. Go to Settings > Permalinks and save to fix.');
            }

            // Set admin notice flag instead of runtime flush
            update_option('apollo_rewrite_conflict_detected', true);

            // NOTE: flush_rewrite_rules removed from runtime context.
            // User should go to Settings > Permalinks and click Save to fix.
        }
    },
    999
);
