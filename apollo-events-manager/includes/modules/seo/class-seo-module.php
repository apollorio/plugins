<?php
/**
 * SEO Module
 *
 * Search engine optimization for events.
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
 * Class SEO_Module
 *
 * SEO enhancements with meta tags and schema markup.
 *
 * @since 2.0.0
 */
class SEO_Module extends Abstract_Module {

    /**
     * Get module unique identifier.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_id(): string {
        return 'seo';
    }

    /**
     * Get module name.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_name(): string {
        return __( 'SEO', 'apollo-events' );
    }

    /**
     * Get module description.
     *
     * @since 2.0.0
     * @return string
     */
    public function get_description(): string {
        return __( 'Otimização para mecanismos de busca com meta tags e schema markup.', 'apollo-events' );
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
        $this->register_hooks();
        $this->register_assets();
    }

    /**
     * Register hooks.
     *
     * @since 2.0.0
     * @return void
     */
    private function register_hooks(): void {
        // Meta tags.
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );
        add_action( 'wp_head', array( $this, 'output_open_graph' ), 2 );
        add_action( 'wp_head', array( $this, 'output_twitter_cards' ), 3 );
        add_action( 'wp_head', array( $this, 'output_schema_markup' ), 5 );

        // Canonical URL.
        add_action( 'wp_head', array( $this, 'output_canonical' ), 1 );

        // Title modifications.
        add_filter( 'document_title_parts', array( $this, 'modify_title' ) );

        // Admin metabox.
        add_action( 'add_meta_boxes', array( $this, 'add_seo_metabox' ) );
        add_action( 'save_post_event_listing', array( $this, 'save_seo_meta' ) );

        // Sitemap modifications.
        add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'modify_sitemap_query' ), 10, 2 );
    }

    /**
     * Register module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_assets(): void {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 2.0.0
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueue_admin_assets( string $hook ): void {
        global $post_type;

        if ( 'event_listing' !== $post_type ) {
            return;
        }

        wp_enqueue_style(
            'apollo-seo-admin',
            plugins_url( 'assets/css/seo-admin.css', dirname( dirname( __DIR__ ) ) ),
            array(),
            $this->get_version()
        );
    }

    /**
     * Output meta tags.
     *
     * @since 2.0.0
     * @return void
     */
    public function output_meta_tags(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        global $post;

        $meta_desc = $this->get_meta_description( $post );
        $meta_keys = $this->get_meta_keywords( $post );

        if ( $meta_desc ) {
            echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
        }

        if ( $meta_keys ) {
            echo '<meta name="keywords" content="' . esc_attr( $meta_keys ) . '">' . "\n";
        }

        // Robots.
        $robots = $this->get_robots_meta( $post );
        if ( $robots ) {
            echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
        }
    }

    /**
     * Output Open Graph tags.
     *
     * @since 2.0.0
     * @return void
     */
    public function output_open_graph(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        global $post;

        $event_date = get_post_meta( $post->ID, '_event_start_date', true );
        $image      = $this->get_event_image( $post->ID );
        $desc       = $this->get_meta_description( $post );

        $og_tags = array(
            'og:type'        => 'event',
            'og:title'       => get_the_title( $post ),
            'og:description' => $desc,
            'og:url'         => get_permalink( $post ),
            'og:site_name'   => get_bloginfo( 'name' ),
            'og:locale'      => get_locale(),
        );

        if ( $image ) {
            $og_tags['og:image']        = $image['url'];
            $og_tags['og:image:width']  = $image['width'] ?? '';
            $og_tags['og:image:height'] = $image['height'] ?? '';
            $og_tags['og:image:alt']    = get_the_title( $post );
        }

        if ( $event_date ) {
            $og_tags['event:start_time'] = wp_date( 'c', strtotime( $event_date ) );
        }

        foreach ( $og_tags as $property => $content ) {
            if ( $content ) {
                echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
            }
        }
    }

    /**
     * Output Twitter Card tags.
     *
     * @since 2.0.0
     * @return void
     */
    public function output_twitter_cards(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        global $post;

        $image = $this->get_event_image( $post->ID );
        $desc  = $this->get_meta_description( $post );

        $twitter_tags = array(
            'twitter:card'        => $image ? 'summary_large_image' : 'summary',
            'twitter:title'       => get_the_title( $post ),
            'twitter:description' => $desc,
        );

        if ( $image ) {
            $twitter_tags['twitter:image'] = $image['url'];
        }

        $twitter_site = get_option( 'apollo_twitter_site' );
        if ( $twitter_site ) {
            $twitter_tags['twitter:site'] = '@' . ltrim( $twitter_site, '@' );
        }

        foreach ( $twitter_tags as $name => $content ) {
            if ( $content ) {
                echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $content ) . '">' . "\n";
            }
        }
    }

    /**
     * Output Schema.org markup.
     *
     * @since 2.0.0
     * @return void
     */
    public function output_schema_markup(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        global $post;

        $schema = $this->generate_event_schema( $post );

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo "\n</script>\n";
    }

    /**
     * Generate Event schema.
     *
     * @since 2.0.0
     * @param \WP_Post $post Event post.
     * @return array
     */
    private function generate_event_schema( $post ): array {
        $event_date   = get_post_meta( $post->ID, '_event_start_date', true );
        $end_date     = get_post_meta( $post->ID, '_event_end_date', true );
        $ticket_url   = get_post_meta( $post->ID, '_event_ticket_url', true );
        $ticket_price = get_post_meta( $post->ID, '_event_ticket_price', true );
        $image        = $this->get_event_image( $post->ID );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Event',
            'name'        => get_the_title( $post ),
            'description' => wp_strip_all_tags( $post->post_excerpt ?: wp_trim_words( $post->post_content, 55 ) ),
            'url'         => get_permalink( $post ),
        );

        // Dates.
        if ( $event_date ) {
            $schema['startDate'] = wp_date( 'c', strtotime( $event_date ) );
        }

        if ( $end_date ) {
            $schema['endDate'] = wp_date( 'c', strtotime( $end_date ) );
        }

        // Image.
        if ( $image ) {
            $schema['image'] = array( $image['url'] );
        }

        // Location.
        $local_ids = get_post_meta( $post->ID, '_event_local_ids', true );
        if ( ! empty( $local_ids ) && is_array( $local_ids ) ) {
            $local    = get_post( $local_ids[0] );
            $address  = get_post_meta( $local_ids[0], '_local_address', true );
            $city     = get_post_meta( $local_ids[0], '_local_city', true );

            if ( $local ) {
                $schema['location'] = array(
                    '@type' => 'Place',
                    'name'  => $local->post_title,
                );

                if ( $address || $city ) {
                    $schema['location']['address'] = array(
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => $address ?: '',
                        'addressLocality' => $city ?: '',
                        'addressCountry'  => 'BR',
                    );
                }
            }
        } else {
            // Virtual event.
            $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            $schema['location'] = array(
                '@type' => 'VirtualLocation',
                'url'   => get_permalink( $post ),
            );
        }

        // Offers.
        if ( $ticket_price || $ticket_url ) {
            $schema['offers'] = array(
                '@type'         => 'Offer',
                'url'           => $ticket_url ?: get_permalink( $post ),
                'availability'  => 'https://schema.org/InStock',
            );

            if ( $ticket_price ) {
                $schema['offers']['price']         = floatval( $ticket_price );
                $schema['offers']['priceCurrency'] = 'BRL';
            } else {
                $schema['offers']['price'] = 0;
            }
        }

        // Organizer.
        $author = get_userdata( $post->post_author );
        if ( $author ) {
            $schema['organizer'] = array(
                '@type' => 'Organization',
                'name'  => $author->display_name,
                'url'   => get_author_posts_url( $author->ID ),
            );
        }

        // Performers (DJs).
        $dj_ids = get_post_meta( $post->ID, '_event_dj_ids', true );
        if ( ! empty( $dj_ids ) && is_array( $dj_ids ) ) {
            $schema['performer'] = array();
            foreach ( $dj_ids as $dj_id ) {
                $dj = get_post( $dj_id );
                if ( $dj ) {
                    $schema['performer'][] = array(
                        '@type' => 'MusicGroup',
                        'name'  => $dj->post_title,
                        'url'   => get_permalink( $dj_id ),
                    );
                }
            }
        }

        // Event status.
        $schema['eventStatus'] = 'https://schema.org/EventScheduled';

        /**
         * Filter event schema markup.
         *
         * @since 2.0.0
         * @param array    $schema Event schema.
         * @param \WP_Post $post   Event post.
         */
        return apply_filters( 'apollo_event_schema', $schema, $post );
    }

    /**
     * Output canonical URL.
     *
     * @since 2.0.0
     * @return void
     */
    public function output_canonical(): void {
        if ( ! is_singular( 'event_listing' ) ) {
            return;
        }

        // Remove default canonical if we're adding our own.
        remove_action( 'wp_head', 'rel_canonical' );

        $custom_canonical = get_post_meta( get_the_ID(), '_apollo_seo_canonical', true );
        $canonical        = $custom_canonical ?: get_permalink();

        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    }

    /**
     * Modify document title.
     *
     * @since 2.0.0
     * @param array $title Title parts.
     * @return array
     */
    public function modify_title( array $title ): array {
        if ( ! is_singular( 'event_listing' ) ) {
            return $title;
        }

        $custom_title = get_post_meta( get_the_ID(), '_apollo_seo_title', true );

        if ( $custom_title ) {
            $title['title'] = $custom_title;
        }

        return $title;
    }

    /**
     * Get meta description.
     *
     * @since 2.0.0
     * @param \WP_Post $post Event post.
     * @return string
     */
    private function get_meta_description( $post ): string {
        $custom = get_post_meta( $post->ID, '_apollo_seo_description', true );

        if ( $custom ) {
            return $custom;
        }

        if ( $post->post_excerpt ) {
            return wp_strip_all_tags( $post->post_excerpt );
        }

        return wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' );
    }

    /**
     * Get meta keywords.
     *
     * @since 2.0.0
     * @param \WP_Post $post Event post.
     * @return string
     */
    private function get_meta_keywords( $post ): string {
        $custom = get_post_meta( $post->ID, '_apollo_seo_keywords', true );

        if ( $custom ) {
            return $custom;
        }

        // Auto-generate from taxonomies.
        $terms = wp_get_post_terms( $post->ID, array( 'event_category', 'event_tag' ), array( 'fields' => 'names' ) );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            return implode( ', ', array_slice( $terms, 0, 10 ) );
        }

        return '';
    }

    /**
     * Get robots meta.
     *
     * @since 2.0.0
     * @param \WP_Post $post Event post.
     * @return string
     */
    private function get_robots_meta( $post ): string {
        $noindex  = get_post_meta( $post->ID, '_apollo_seo_noindex', true );
        $nofollow = get_post_meta( $post->ID, '_apollo_seo_nofollow', true );

        $robots = array();

        if ( $noindex ) {
            $robots[] = 'noindex';
        }

        if ( $nofollow ) {
            $robots[] = 'nofollow';
        }

        return implode( ', ', $robots );
    }

    /**
     * Get event image.
     *
     * @since 2.0.0
     * @param int $post_id Post ID.
     * @return array|null
     */
    private function get_event_image( int $post_id ): ?array {
        $thumbnail_id = get_post_thumbnail_id( $post_id );

        if ( ! $thumbnail_id ) {
            return null;
        }

        $image = wp_get_attachment_image_src( $thumbnail_id, 'large' );

        if ( ! $image ) {
            return null;
        }

        return array(
            'url'    => $image[0],
            'width'  => $image[1],
            'height' => $image[2],
        );
    }

    /**
     * Add SEO metabox.
     *
     * @since 2.0.0
     * @return void
     */
    public function add_seo_metabox(): void {
        add_meta_box(
            'apollo-seo',
            __( 'SEO', 'apollo-events' ),
            array( $this, 'render_seo_metabox' ),
            'event_listing',
            'normal',
            'low'
        );
    }

    /**
     * Render SEO metabox.
     *
     * @since 2.0.0
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_seo_metabox( $post ): void {
        wp_nonce_field( 'apollo_seo_save', 'apollo_seo_nonce' );

        $title       = get_post_meta( $post->ID, '_apollo_seo_title', true );
        $description = get_post_meta( $post->ID, '_apollo_seo_description', true );
        $keywords    = get_post_meta( $post->ID, '_apollo_seo_keywords', true );
        $canonical   = get_post_meta( $post->ID, '_apollo_seo_canonical', true );
        $noindex     = get_post_meta( $post->ID, '_apollo_seo_noindex', true );
        $nofollow    = get_post_meta( $post->ID, '_apollo_seo_nofollow', true );

        ?>
        <div class="apollo-seo-metabox">
            <div class="apollo-seo-preview">
                <p class="apollo-seo-preview__title"><?php esc_html_e( 'Prévia do Google', 'apollo-events' ); ?></p>
                <div class="apollo-seo-preview__box">
                    <div class="apollo-seo-preview__title-text" id="seo-preview-title">
                        <?php echo esc_html( $title ?: get_the_title( $post ) ); ?>
                    </div>
                    <div class="apollo-seo-preview__url">
                        <?php echo esc_url( get_permalink( $post ) ); ?>
                    </div>
                    <div class="apollo-seo-preview__desc" id="seo-preview-desc">
                        <?php echo esc_html( $description ?: wp_trim_words( $post->post_content, 25 ) ); ?>
                    </div>
                </div>
            </div>

            <table class="form-table">
                <tr>
                    <th><label for="apollo_seo_title"><?php esc_html_e( 'Título SEO', 'apollo-events' ); ?></label></th>
                    <td>
                        <input type="text" id="apollo_seo_title" name="apollo_seo_title"
                               value="<?php echo esc_attr( $title ); ?>" class="large-text"
                               placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
                        <p class="description">
                            <span id="seo-title-count"><?php echo esc_html( mb_strlen( $title ) ); ?></span>/60
                            <?php esc_html_e( 'caracteres', 'apollo-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label for="apollo_seo_description"><?php esc_html_e( 'Meta Descrição', 'apollo-events' ); ?></label></th>
                    <td>
                        <textarea id="apollo_seo_description" name="apollo_seo_description"
                                  rows="3" class="large-text"
                                  placeholder="<?php esc_attr_e( 'Descrição que aparecerá nos resultados de busca', 'apollo-events' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
                        <p class="description">
                            <span id="seo-desc-count"><?php echo esc_html( mb_strlen( $description ) ); ?></span>/160
                            <?php esc_html_e( 'caracteres', 'apollo-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label for="apollo_seo_keywords"><?php esc_html_e( 'Palavras-chave', 'apollo-events' ); ?></label></th>
                    <td>
                        <input type="text" id="apollo_seo_keywords" name="apollo_seo_keywords"
                               value="<?php echo esc_attr( $keywords ); ?>" class="large-text"
                               placeholder="<?php esc_attr_e( 'palavra1, palavra2, palavra3', 'apollo-events' ); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="apollo_seo_canonical"><?php esc_html_e( 'URL Canônica', 'apollo-events' ); ?></label></th>
                    <td>
                        <input type="url" id="apollo_seo_canonical" name="apollo_seo_canonical"
                               value="<?php echo esc_url( $canonical ); ?>" class="large-text"
                               placeholder="<?php echo esc_url( get_permalink( $post ) ); ?>">
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Indexação', 'apollo-events' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="apollo_seo_noindex" value="1" <?php checked( $noindex ); ?>>
                            <?php esc_html_e( 'Não indexar este evento (noindex)', 'apollo-events' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="apollo_seo_nofollow" value="1" <?php checked( $nofollow ); ?>>
                            <?php esc_html_e( 'Não seguir links (nofollow)', 'apollo-events' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <style>
            .apollo-seo-metabox { padding: 15px 0; }
            .apollo-seo-preview { margin-bottom: 20px; }
            .apollo-seo-preview__title { font-weight: 600; margin-bottom: 8px; }
            .apollo-seo-preview__box {
                padding: 15px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
            }
            .apollo-seo-preview__title-text {
                color: #1a0dab;
                font-size: 18px;
                line-height: 1.3;
                margin-bottom: 3px;
            }
            .apollo-seo-preview__url {
                color: #006621;
                font-size: 14px;
                margin-bottom: 3px;
            }
            .apollo-seo-preview__desc {
                color: #545454;
                font-size: 13px;
                line-height: 1.4;
            }
        </style>

        <script>
            jQuery(function($) {
                $('#apollo_seo_title').on('input', function() {
                    const val = $(this).val();
                    $('#seo-title-count').text(val.length);
                    $('#seo-preview-title').text(val || '<?php echo esc_js( get_the_title( $post ) ); ?>');
                });

                $('#apollo_seo_description').on('input', function() {
                    const val = $(this).val();
                    $('#seo-desc-count').text(val.length);
                    $('#seo-preview-desc').text(val || '<?php echo esc_js( wp_trim_words( $post->post_content, 25 ) ); ?>');
                });
            });
        </script>
        <?php
    }

    /**
     * Save SEO meta.
     *
     * @since 2.0.0
     * @param int $post_id Post ID.
     * @return void
     */
    public function save_seo_meta( int $post_id ): void {
        if ( ! isset( $_POST['apollo_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['apollo_seo_nonce'] ), 'apollo_seo_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'apollo_seo_title'       => 'text',
            'apollo_seo_description' => 'textarea',
            'apollo_seo_keywords'    => 'text',
            'apollo_seo_canonical'   => 'url',
            'apollo_seo_noindex'     => 'checkbox',
            'apollo_seo_nofollow'    => 'checkbox',
        );

        foreach ( $fields as $field => $type ) {
            $meta_key = '_' . $field;

            if ( 'checkbox' === $type ) {
                $value = isset( $_POST[ $field ] ) ? 1 : 0;
            } elseif ( 'url' === $type ) {
                $value = isset( $_POST[ $field ] ) ? esc_url_raw( wp_unslash( $_POST[ $field ] ) ) : '';
            } else {
                $value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            }

            if ( $value ) {
                update_post_meta( $post_id, $meta_key, $value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    /**
     * Modify sitemap query.
     *
     * @since 2.0.0
     * @param array  $args      Query args.
     * @param string $post_type Post type.
     * @return array
     */
    public function modify_sitemap_query( array $args, string $post_type ): array {
        if ( 'event_listing' !== $post_type ) {
            return $args;
        }

        // Exclude noindex posts.
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_apollo_seo_noindex',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_apollo_seo_noindex',
                'value'   => '1',
                'compare' => '!=',
            ),
        );

        return $args;
    }

    /**
     * Register shortcodes.
     *
     * @since 2.0.0
     * @return void
     */
    public function register_shortcodes(): void {
        // No shortcodes for this module.
    }

    /**
     * Enqueue module assets.
     *
     * @since 2.0.0
     * @return void
     */
    public function enqueue_assets(): void {
        // No frontend assets.
    }

    /**
     * Get settings schema.
     *
     * @since 2.0.0
     * @return array
     */
    public function get_settings_schema(): array {
        return array(
            'enable_schema'   => array(
                'type'        => 'boolean',
                'label'       => __( 'Habilitar Schema.org', 'apollo-events' ),
                'default'     => true,
            ),
            'enable_og'       => array(
                'type'        => 'boolean',
                'label'       => __( 'Habilitar Open Graph', 'apollo-events' ),
                'default'     => true,
            ),
            'enable_twitter'  => array(
                'type'        => 'boolean',
                'label'       => __( 'Habilitar Twitter Cards', 'apollo-events' ),
                'default'     => true,
            ),
            'twitter_site'    => array(
                'type'        => 'text',
                'label'       => __( '@Twitter do site', 'apollo-events' ),
                'default'     => '',
            ),
        );
    }
}
