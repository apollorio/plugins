<?php
/**
 * Apollo Native SEO
 *
 * Self-contained SEO solution with meta tags, Open Graph, Schema.org, and XML sitemaps.
 * NO external plugins, NO API keys, NO external services required.
 *
 * @package Apollo_Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Native_SEO
 *
 * Comprehensive SEO management without external dependencies.
 */
class Native_SEO {

	/** @var string Option name for SEO settings. */
	private const SETTINGS_OPTION = 'apollo_seo_settings';

	/**
	 * Initialize the SEO system.
	 */
	public static function init(): void {
		// Add meta tags to head.
		add_action( 'wp_head', array( __CLASS__, 'render_meta_tags' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'render_open_graph' ), 2 );
		add_action( 'wp_head', array( __CLASS__, 'render_twitter_cards' ), 3 );
		add_action( 'wp_head', array( __CLASS__, 'render_schema_org' ), 4 );

		// Admin meta boxes.
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );

		// Add sitemap routes.
		add_action( 'init', array( __CLASS__, 'register_sitemap_routes' ) );

		// Admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Auto-generate meta from content.
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'auto_generate_meta' ), 10, 2 );

		// Show status notice.
		add_action( 'admin_notices', array( __CLASS__, 'show_status_notice' ) );

		// Robots.txt optimization.
		add_filter( 'robots_txt', array( __CLASS__, 'optimize_robots_txt' ), 10, 2 );
	}

	/**
	 * Show status notice on Apollo admin pages.
	 */
	public static function show_status_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'apollo' ) === false ) {
			return;
		}

		echo '<div class="notice notice-info is-dismissible apollo-seo-status">';
		echo '<p><span class="dashicons dashicons-search" style="color:#0073aa;"></span> ';
		echo '<strong>' . esc_html__( 'Apollo SEO:', 'apollo-core' ) . '</strong> ';
		echo esc_html__( 'Native SEO with meta tags, Open Graph, Schema.org & XML Sitemap.', 'apollo-core' );
		echo ' <span style="color:#46b450;">✓</span>';
		echo '</p></div>';
	}

	/**
	 * Get SEO settings.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		return wp_parse_args(
			get_option( self::SETTINGS_OPTION, array() ),
			array(
				'enable_meta'       => true,
				'enable_og'         => true,
				'enable_twitter'    => true,
				'enable_schema'     => true,
				'enable_sitemap'    => true,
				'default_og_image'  => '',
				'twitter_username'  => '',
				'organization_name' => get_bloginfo( 'name' ),
				'organization_logo' => '',
				'separator'         => '|',
				'title_format'      => '%post_title% %sep% %site_title%',
				'home_title'        => '',
				'home_description'  => '',
				'noindex_archives'  => false,
				'noindex_author'    => false,
				'noindex_date'      => true,
				'noindex_search'    => true,
			)
		);
	}

	/**
	 * Render meta tags.
	 */
	public static function render_meta_tags(): void {
		$settings = self::get_settings();

		if ( ! $settings['enable_meta'] ) {
			return;
		}

		echo "<!-- Apollo Native SEO -->\n";

		// Meta description.
		$description = self::get_meta_description();
		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		// Canonical URL.
		$canonical = self::get_canonical_url();
		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}

		// Robots meta.
		$robots = self::get_robots_meta();
		if ( $robots ) {
			printf( '<meta name="robots" content="%s">' . "\n", esc_attr( $robots ) );
		}

		// Author for single posts.
		if ( is_singular() && get_the_author() ) {
			printf( '<meta name="author" content="%s">' . "\n", esc_attr( get_the_author() ) );
		}
	}

	/**
	 * Render Open Graph tags.
	 */
	public static function render_open_graph(): void {
		$settings = self::get_settings();

		if ( ! $settings['enable_og'] ) {
			return;
		}

		echo "<!-- Open Graph -->\n";

		// Basic OG tags.
		printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

		if ( is_singular() ) {
			global $post;

			printf( '<meta property="og:type" content="article">' . "\n" );
			printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( self::get_og_title() ) );
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( self::get_meta_description() ) );
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( get_permalink() ) );

			// Article specific.
			printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c' ) ) );
			printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c' ) ) );

			if ( get_the_author() ) {
				printf( '<meta property="article:author" content="%s">' . "\n", esc_attr( get_the_author() ) );
			}

			// Featured image.
			$image = self::get_og_image();
			if ( $image ) {
				printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
				printf( '<meta property="og:image:width" content="1200">' . "\n" );
				printf( '<meta property="og:image:height" content="630">' . "\n" );
			}
		} elseif ( is_front_page() || is_home() ) {
			printf( '<meta property="og:type" content="website">' . "\n" );
			printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( get_bloginfo( 'description' ) ) );
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( home_url( '/' ) ) );

			if ( $settings['default_og_image'] ) {
				printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $settings['default_og_image'] ) );
			}
		} else {
			printf( '<meta property="og:type" content="website">' . "\n" );
			printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( self::get_canonical_url() ) );
		}
	}

	/**
	 * Render Twitter Card tags.
	 */
	public static function render_twitter_cards(): void {
		$settings = self::get_settings();

		if ( ! $settings['enable_twitter'] ) {
			return;
		}

		echo "<!-- Twitter Cards -->\n";

		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( self::get_og_title() ) );
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( self::get_meta_description() ) );

		$image = self::get_og_image();
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		}

		if ( $settings['twitter_username'] ) {
			printf( '<meta name="twitter:site" content="@%s">' . "\n", esc_attr( ltrim( $settings['twitter_username'], '@' ) ) );
		}
	}

	/**
	 * Render Schema.org structured data.
	 */
	public static function render_schema_org(): void {
		$settings = self::get_settings();

		if ( ! $settings['enable_schema'] ) {
			return;
		}

		$schema = array();

		// Organization schema.
		$org_schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => $settings['organization_name'] ?: get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
		);

		if ( $settings['organization_logo'] ) {
			$org_schema['logo'] = $settings['organization_logo'];
		}

		$schema[] = $org_schema;

		// Website schema.
		$website_schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
		$schema[]       = $website_schema;

		// Single post/page schema.
		if ( is_singular() ) {
			global $post;

			$article_schema = array(
				'@context'         => 'https://schema.org',
				'@type'            => is_page() ? 'WebPage' : 'Article',
				'headline'         => get_the_title(),
				'description'      => self::get_meta_description(),
				'url'              => get_permalink(),
				'datePublished'    => get_the_date( 'c' ),
				'dateModified'     => get_the_modified_date( 'c' ),
				'mainEntityOfPage' => array(
					'@type' => 'WebPage',
					'@id'   => get_permalink(),
				),
			);

			// Author.
			$author = get_the_author();
			if ( $author ) {
				$article_schema['author'] = array(
					'@type' => 'Person',
					'name'  => $author,
					'url'   => get_author_posts_url( get_the_author_meta( 'ID' ) ),
				);
			}

			// Publisher.
			$article_schema['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $settings['organization_name'] ?: get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			);

			if ( $settings['organization_logo'] ) {
				$article_schema['publisher']['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $settings['organization_logo'],
				);
			}

			// Featured image.
			if ( has_post_thumbnail() ) {
				$article_schema['image'] = get_the_post_thumbnail_url( null, 'large' );
			}

			$schema[] = $article_schema;

			// Breadcrumb schema.
			$breadcrumbs = self::get_breadcrumb_schema();
			if ( $breadcrumbs ) {
				$schema[] = $breadcrumbs;
			}
		}

		// Event schema for event posts.
		if ( is_singular( 'event_listing' ) ) {
			$event_schema = self::get_event_schema();
			if ( $event_schema ) {
				$schema[] = $event_schema;
			}
		}

		// Output all schemas.
		foreach ( $schema as $item ) {
			echo '<script type="application/ld+json">' . "\n";
			echo wp_json_encode( $item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			echo "\n</script>\n";
		}
	}

	/**
	 * Get breadcrumb schema.
	 *
	 * @return array|null
	 */
	private static function get_breadcrumb_schema(): ?array {
		global $post;

		if ( ! $post ) {
			return null;
		}

		$items = array();
		$pos   = 1;

		// Home.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => __( 'Home', 'apollo-core' ),
			'item'     => home_url( '/' ),
		);

		// Category for posts.
		if ( is_single() && 'post' === $post->post_type ) {
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $categories[0]->name,
					'item'     => get_category_link( $categories[0]->term_id ),
				);
			}
		}

		// Parent pages.
		if ( is_page() && $post->post_parent ) {
			$parents = get_post_ancestors( $post->ID );
			$parents = array_reverse( $parents );

			foreach ( $parents as $parent_id ) {
				$parent  = get_post( $parent_id );
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $parent->post_title,
					'item'     => get_permalink( $parent_id ),
				);
			}
		}

		// Current page.
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'name'     => get_the_title(),
			'item'     => get_permalink(),
		);

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * Get event schema for event listings.
	 *
	 * @return array|null
	 */
	private static function get_event_schema(): ?array {
		global $post;

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return null;
		}

		$start_date = get_post_meta( $post->ID, '_event_start_date', true );
		$end_date   = get_post_meta( $post->ID, '_event_end_date', true );

		if ( ! $start_date ) {
			return null;
		}

		$schema = array(
			'@context'  => 'https://schema.org',
			'@type'     => 'Event',
			'name'      => get_the_title(),
			'startDate' => $start_date,
			'url'       => get_permalink(),
		);

		if ( $end_date ) {
			$schema['endDate'] = $end_date;
		}

		// Location.
		$venue = get_post_meta( $post->ID, '_event_venue', true );
		if ( $venue ) {
			$schema['location'] = array(
				'@type' => 'Place',
				'name'  => $venue,
			);

			$address = get_post_meta( $post->ID, '_event_address', true );
			if ( $address ) {
				$schema['location']['address'] = $address;
			}
		}

		// Description.
		$description = self::get_meta_description();
		if ( $description ) {
			$schema['description'] = $description;
		}

		// Image.
		if ( has_post_thumbnail() ) {
			$schema['image'] = get_the_post_thumbnail_url( null, 'large' );
		}

		// Organizer.
		$organizer = get_post_meta( $post->ID, '_event_organizer', true );
		if ( $organizer ) {
			$schema['organizer'] = array(
				'@type' => 'Organization',
				'name'  => $organizer,
			);
		}

		return $schema;
	}

	/**
	 * Get meta description.
	 *
	 * @return string
	 */
	private static function get_meta_description(): string {
		global $post;

		// Check for custom meta description.
		if ( is_singular() && $post ) {
			$custom = get_post_meta( $post->ID, '_apollo_seo_description', true );
			if ( $custom ) {
				return $custom;
			}

			// Use excerpt or content.
			$content = $post->post_excerpt ?: $post->post_content;
			$content = wp_strip_all_tags( $content );
			$content = preg_replace( '/\s+/', ' ', $content );
			return wp_trim_words( $content, 25, '' );
		}

		if ( is_front_page() || is_home() ) {
			$settings = self::get_settings();
			return $settings['home_description'] ?: get_bloginfo( 'description' );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && ! empty( $term->description ) ) {
				return wp_trim_words( $term->description, 25, '' );
			}
		}

		return get_bloginfo( 'description' );
	}

	/**
	 * Get OG title.
	 *
	 * @return string
	 */
	private static function get_og_title(): string {
		global $post;

		if ( is_singular() && $post ) {
			$custom = get_post_meta( $post->ID, '_apollo_seo_title', true );
			if ( $custom ) {
				return $custom;
			}
			return get_the_title();
		}

		return wp_get_document_title();
	}

	/**
	 * Get OG image.
	 *
	 * @return string
	 */
	private static function get_og_image(): string {
		global $post;

		if ( is_singular() && $post ) {
			// Custom OG image.
			$custom = get_post_meta( $post->ID, '_apollo_seo_image', true );
			if ( $custom ) {
				return $custom;
			}

			// Featured image.
			if ( has_post_thumbnail() ) {
				return (string) get_the_post_thumbnail_url( null, 'large' );
			}
		}

		// Default OG image.
		$settings = self::get_settings();
		return $settings['default_og_image'] ?? '';
	}

	/**
	 * Get canonical URL.
	 *
	 * @return string
	 */
	private static function get_canonical_url(): string {
		global $post;

		if ( is_singular() && $post ) {
			$custom = get_post_meta( $post->ID, '_apollo_seo_canonical', true );
			if ( $custom ) {
				return $custom;
			}
			return get_permalink();
		}

		if ( is_front_page() || is_home() ) {
			return home_url( '/' );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term ) {
				return get_term_link( $term );
			}
		}

		return (string) home_url( add_query_arg( array() ) );
	}

	/**
	 * Get robots meta.
	 *
	 * @return string
	 */
	private static function get_robots_meta(): string {
		global $post;

		$settings = self::get_settings();
		$robots   = array();

		// Check custom noindex.
		if ( is_singular() && $post ) {
			$noindex = get_post_meta( $post->ID, '_apollo_seo_noindex', true );
			if ( $noindex ) {
				$robots[] = 'noindex';
			}
		}

		// Archive settings.
		if ( is_date() && $settings['noindex_date'] ) {
			$robots[] = 'noindex';
		}

		if ( is_author() && $settings['noindex_author'] ) {
			$robots[] = 'noindex';
		}

		if ( is_search() && $settings['noindex_search'] ) {
			$robots[] = 'noindex';
		}

		if ( empty( $robots ) ) {
			return 'index, follow';
		}

		return implode( ', ', $robots ) . ', follow';
	}

	/**
	 * Add SEO meta boxes.
	 */
	public static function add_meta_boxes(): void {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'apollo_seo_meta',
				__( 'Apollo SEO', 'apollo-core' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render SEO meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_seo_meta', 'apollo_seo_nonce' );

		$title       = get_post_meta( $post->ID, '_apollo_seo_title', true );
		$description = get_post_meta( $post->ID, '_apollo_seo_description', true );
		$noindex     = get_post_meta( $post->ID, '_apollo_seo_noindex', true );
		$canonical   = get_post_meta( $post->ID, '_apollo_seo_canonical', true );
		$og_image    = get_post_meta( $post->ID, '_apollo_seo_image', true );
		?>
		<style>
			.apollo-seo-field { margin-bottom: 15px; }
			.apollo-seo-field label { display: block; font-weight: 600; margin-bottom: 5px; }
			.apollo-seo-field input[type="text"],
			.apollo-seo-field textarea { width: 100%; }
			.apollo-seo-preview {
				background: #f5f5f5;
				padding: 15px;
				border-radius: 4px;
				margin-top: 15px;
			}
			.apollo-seo-preview-title {
				color: #1a0dab;
				font-size: 18px;
				margin: 0 0 5px;
			}
			.apollo-seo-preview-url {
				color: #006621;
				font-size: 14px;
				margin: 0 0 5px;
			}
			.apollo-seo-preview-desc {
				color: #545454;
				font-size: 13px;
				margin: 0;
			}
			.apollo-seo-counter {
				font-size: 12px;
				color: #666;
				text-align: right;
			}
			.apollo-seo-counter.warning { color: #f0ad4e; }
			.apollo-seo-counter.danger { color: #dc3545; }
		</style>

		<div class="apollo-seo-field">
			<label for="apollo_seo_title"><?php esc_html_e( 'SEO Title', 'apollo-core' ); ?></label>
			<input type="text" id="apollo_seo_title" name="apollo_seo_title"
				value="<?php echo esc_attr( $title ); ?>" maxlength="70"
				placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
			<div class="apollo-seo-counter"><span id="title-count"><?php echo strlen( $title ); ?></span>/60</div>
		</div>

		<div class="apollo-seo-field">
			<label for="apollo_seo_description"><?php esc_html_e( 'Meta Description', 'apollo-core' ); ?></label>
			<textarea id="apollo_seo_description" name="apollo_seo_description"
				rows="3" maxlength="160"
				placeholder="<?php esc_attr_e( 'Auto-generated from content if empty', 'apollo-core' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
			<div class="apollo-seo-counter"><span id="desc-count"><?php echo strlen( $description ); ?></span>/155</div>
		</div>

		<div class="apollo-seo-field">
			<label for="apollo_seo_canonical"><?php esc_html_e( 'Canonical URL', 'apollo-core' ); ?></label>
			<input type="url" id="apollo_seo_canonical" name="apollo_seo_canonical"
				value="<?php echo esc_url( $canonical ); ?>"
				placeholder="<?php echo esc_url( get_permalink( $post ) ); ?>">
		</div>

		<div class="apollo-seo-field">
			<label for="apollo_seo_image"><?php esc_html_e( 'Social Image URL', 'apollo-core' ); ?></label>
			<input type="url" id="apollo_seo_image" name="apollo_seo_image"
				value="<?php echo esc_url( $og_image ); ?>"
				placeholder="<?php esc_attr_e( 'Uses featured image if empty', 'apollo-core' ); ?>">
		</div>

		<div class="apollo-seo-field">
			<label>
				<input type="checkbox" name="apollo_seo_noindex" value="1" <?php checked( $noindex, '1' ); ?>>
				<?php esc_html_e( 'Hide from search engines (noindex)', 'apollo-core' ); ?>
			</label>
		</div>

		<div class="apollo-seo-preview">
			<h4 style="margin: 0 0 10px;"><?php esc_html_e( 'Google Preview', 'apollo-core' ); ?></h4>
			<p class="apollo-seo-preview-title" id="preview-title"><?php echo esc_html( $title ?: get_the_title( $post ) ); ?></p>
			<p class="apollo-seo-preview-url"><?php echo esc_url( get_permalink( $post ) ); ?></p>
			<p class="apollo-seo-preview-desc" id="preview-desc"><?php echo esc_html( $description ?: wp_trim_words( $post->post_content, 25 ) ); ?></p>
		</div>

		<script>
		jQuery(function($) {
			function updateCounter(input, counter, max) {
				var len = $(input).val().length;
				$(counter).text(len);
				$(counter).parent().removeClass('warning danger');
				if (len > max) {
					$(counter).parent().addClass('danger');
				} else if (len > max - 10) {
					$(counter).parent().addClass('warning');
				}
			}

			$('#apollo_seo_title').on('input', function() {
				updateCounter(this, '#title-count', 60);
				$('#preview-title').text($(this).val() || '<?php echo esc_js( get_the_title( $post ) ); ?>');
			});

			$('#apollo_seo_description').on('input', function() {
				updateCounter(this, '#desc-count', 155);
				$('#preview-desc').text($(this).val() || '<?php echo esc_js( wp_trim_words( $post->post_content, 25 ) ); ?>');
			});
		});
		</script>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function save_meta_box( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['apollo_seo_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['apollo_seo_nonce'], 'apollo_seo_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save fields.
		$fields = array(
			'apollo_seo_title'       => '_apollo_seo_title',
			'apollo_seo_description' => '_apollo_seo_description',
			'apollo_seo_canonical'   => '_apollo_seo_canonical',
			'apollo_seo_image'       => '_apollo_seo_image',
		);

		foreach ( $fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				if ( $value ) {
					update_post_meta( $post_id, $meta_key, $value );
				} else {
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		// Noindex checkbox.
		$noindex = isset( $_POST['apollo_seo_noindex'] ) ? '1' : '';
		if ( $noindex ) {
			update_post_meta( $post_id, '_apollo_seo_noindex', '1' );
		} else {
			delete_post_meta( $post_id, '_apollo_seo_noindex' );
		}
	}

	/**
	 * Register sitemap routes.
	 */
	public static function register_sitemap_routes(): void {
		$settings = self::get_settings();

		if ( ! $settings['enable_sitemap'] ) {
			return;
		}

		add_rewrite_rule( '^apollo-sitemap\.xml$', 'index.php?apollo_sitemap=index', 'top' );
		add_rewrite_rule( '^apollo-sitemap-posts\.xml$', 'index.php?apollo_sitemap=posts', 'top' );
		add_rewrite_rule( '^apollo-sitemap-pages\.xml$', 'index.php?apollo_sitemap=pages', 'top' );
		add_rewrite_rule( '^apollo-sitemap-events\.xml$', 'index.php?apollo_sitemap=events', 'top' );

		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'apollo_sitemap';
				return $vars;
			}
		);

		add_action( 'template_redirect', array( __CLASS__, 'serve_sitemap' ) );
	}

	/**
	 * Serve sitemap.
	 */
	public static function serve_sitemap(): void {
		$sitemap = get_query_var( 'apollo_sitemap' );

		if ( ! $sitemap ) {
			return;
		}

		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

		switch ( $sitemap ) {
			case 'index':
				self::render_sitemap_index();
				break;
			case 'posts':
				self::render_sitemap_posts( 'post' );
				break;
			case 'pages':
				self::render_sitemap_posts( 'page' );
				break;
			case 'events':
				self::render_sitemap_posts( 'event_listing' );
				break;
		}

		exit;
	}

	/**
	 * Render sitemap index.
	 */
	private static function render_sitemap_index(): void {
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$sitemaps = array( 'posts', 'pages' );

		if ( post_type_exists( 'event_listing' ) ) {
			$sitemaps[] = 'events';
		}

		foreach ( $sitemaps as $map ) {
			$lastmod = self::get_sitemap_lastmod( $map );
			echo '<sitemap>' . "\n";
			echo '<loc>' . esc_url( home_url( "/apollo-sitemap-{$map}.xml" ) ) . '</loc>' . "\n";
			if ( $lastmod ) {
				echo '<lastmod>' . esc_html( $lastmod ) . '</lastmod>' . "\n";
			}
			echo '</sitemap>' . "\n";
		}

		echo '</sitemapindex>';
	}

	/**
	 * Get sitemap last modification date.
	 *
	 * @param string $type Sitemap type.
	 * @return string
	 */
	private static function get_sitemap_lastmod( string $type ): string {
		$post_type = 'posts' === $type ? 'post' : ( 'pages' === $type ? 'page' : 'event_listing' );

		$post = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		return ! empty( $post ) ? get_the_modified_date( 'c', $post[0] ) : '';
	}

	/**
	 * Render posts sitemap.
	 *
	 * @param string $post_type Post type.
	 */
	private static function render_sitemap_posts( string $post_type ): void {
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Add home page for posts sitemap.
		if ( 'post' === $post_type ) {
			echo '<url>' . "\n";
			echo '<loc>' . esc_url( home_url( '/' ) ) . '</loc>' . "\n";
			echo '<changefreq>daily</changefreq>' . "\n";
			echo '<priority>1.0</priority>' . "\n";
			echo '</url>' . "\n";
		}

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 1000,
				'post_status'    => 'publish',
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( $posts as $post ) {
			// Skip noindexed posts.
			if ( get_post_meta( $post->ID, '_apollo_seo_noindex', true ) ) {
				continue;
			}

			$priority   = 'page' === $post_type ? '0.8' : '0.6';
			$changefreq = 'monthly';

			// Recent posts get higher priority.
			$modified = strtotime( $post->post_modified );
			$week_ago = strtotime( '-1 week' );
			if ( $modified > $week_ago ) {
				$priority   = '0.8';
				$changefreq = 'daily';
			}

			echo '<url>' . "\n";
			echo '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>' . "\n";
			echo '<lastmod>' . esc_html( get_the_modified_date( 'c', $post ) ) . '</lastmod>' . "\n";
			echo '<changefreq>' . esc_html( $changefreq ) . '</changefreq>' . "\n";
			echo '<priority>' . esc_html( $priority ) . '</priority>' . "\n";
			echo '</url>' . "\n";
		}

		echo '</urlset>';
	}

	/**
	 * Optimize robots.txt.
	 *
	 * @param string $output  Current robots.txt content.
	 * @param bool   $public  Whether the site is public.
	 * @return string
	 */
	public static function optimize_robots_txt( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$settings = self::get_settings();

		if ( ! $settings['enable_sitemap'] ) {
			return $output;
		}

		// Add sitemap reference.
		$sitemap_url = home_url( '/apollo-sitemap.xml' );

		if ( strpos( $output, 'Sitemap:' ) === false ) {
			$output .= "\n\nSitemap: " . $sitemap_url;
		}

		return $output;
	}

	/**
	 * Auto-generate meta from content.
	 *
	 * @param array $data    Post data.
	 * @param array $postarr Post array.
	 * @return array
	 */
	public static function auto_generate_meta( array $data, array $postarr ): array {
		// Only for published content.
		if ( 'publish' !== $data['post_status'] ) {
			return $data;
		}

		$post_id = $postarr['ID'] ?? 0;

		if ( ! $post_id ) {
			return $data;
		}

		// Auto-generate description if empty.
		$description = get_post_meta( $post_id, '_apollo_seo_description', true );
		if ( empty( $description ) && ! empty( $data['post_content'] ) ) {
			$auto_desc = wp_strip_all_tags( $data['post_excerpt'] ?: $data['post_content'] );
			$auto_desc = preg_replace( '/\s+/', ' ', $auto_desc );
			$auto_desc = wp_trim_words( $auto_desc, 25, '' );

			// Store as transient for quick access.
			set_transient( 'apollo_seo_auto_desc_' . $post_id, $auto_desc, DAY_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Add admin menu.
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'apollo-control',
			__( 'SEO Settings', 'apollo-core' ),
			__( 'SEO', 'apollo-core' ),
			'manage_options',
			'apollo-seo',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting( 'apollo_seo', self::SETTINGS_OPTION );
	}

	/**
	 * Render admin page.
	 */
	public static function render_admin_page(): void {
		$settings = self::get_settings();

		if ( isset( $_POST['apollo_seo_settings'] ) && check_admin_referer( 'apollo_seo_settings' ) ) {
			$new_settings = array_map( 'sanitize_text_field', wp_unslash( $_POST['apollo_seo'] ?? array() ) );
			$new_settings = array_merge( $settings, $new_settings );

			// Handle checkboxes.
			$checkboxes = array( 'enable_meta', 'enable_og', 'enable_twitter', 'enable_schema', 'enable_sitemap', 'noindex_archives', 'noindex_author', 'noindex_date', 'noindex_search' );
			foreach ( $checkboxes as $cb ) {
				$new_settings[ $cb ] = isset( $_POST['apollo_seo'][ $cb ] );
			}

			update_option( self::SETTINGS_OPTION, $new_settings );
			$settings = $new_settings;

			// Flush rewrite rules for sitemap.
			flush_rewrite_rules();

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'apollo-core' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo SEO Settings', 'apollo-core' ); ?></h1>

			<div class="card" style="max-width: 800px;">
				<h2><?php esc_html_e( 'Status', 'apollo-core' ); ?></h2>
				<p>
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
					<strong><?php esc_html_e( 'Native SEO: Active', 'apollo-core' ); ?></strong>
				</p>
				<p><?php esc_html_e( 'No external plugins required. Complete SEO solution built-in.', 'apollo-core' ); ?></p>
				<ul>
					<li>✓ <?php esc_html_e( 'Meta tags (title, description)', 'apollo-core' ); ?></li>
					<li>✓ <?php esc_html_e( 'Open Graph for Facebook/LinkedIn', 'apollo-core' ); ?></li>
					<li>✓ <?php esc_html_e( 'Twitter Cards', 'apollo-core' ); ?></li>
					<li>✓ <?php esc_html_e( 'Schema.org JSON-LD', 'apollo-core' ); ?></li>
					<li>✓ <?php esc_html_e( 'XML Sitemaps', 'apollo-core' ); ?></li>
				</ul>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'apollo_seo_settings' ); ?>
				<input type="hidden" name="apollo_seo_settings" value="1">

				<table class="form-table" style="max-width: 800px;">
					<tr>
						<th colspan="2"><h2><?php esc_html_e( 'Features', 'apollo-core' ); ?></h2></th>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Enable Features', 'apollo-core' ); ?></th>
						<td>
							<label><input type="checkbox" name="apollo_seo[enable_meta]" value="1" <?php checked( $settings['enable_meta'] ); ?>> <?php esc_html_e( 'Meta Tags', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[enable_og]" value="1" <?php checked( $settings['enable_og'] ); ?>> <?php esc_html_e( 'Open Graph', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[enable_twitter]" value="1" <?php checked( $settings['enable_twitter'] ); ?>> <?php esc_html_e( 'Twitter Cards', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[enable_schema]" value="1" <?php checked( $settings['enable_schema'] ); ?>> <?php esc_html_e( 'Schema.org', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[enable_sitemap]" value="1" <?php checked( $settings['enable_sitemap'] ); ?>> <?php esc_html_e( 'XML Sitemap', 'apollo-core' ); ?></label>
						</td>
					</tr>

					<tr>
						<th colspan="2"><h2><?php esc_html_e( 'Home Page', 'apollo-core' ); ?></h2></th>
					</tr>
					<tr>
						<th><label for="home_title"><?php esc_html_e( 'Home Title', 'apollo-core' ); ?></label></th>
						<td><input type="text" id="home_title" name="apollo_seo[home_title]" value="<?php echo esc_attr( $settings['home_title'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="home_description"><?php esc_html_e( 'Home Description', 'apollo-core' ); ?></label></th>
						<td><textarea id="home_description" name="apollo_seo[home_description]" class="large-text" rows="3" placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $settings['home_description'] ); ?></textarea></td>
					</tr>

					<tr>
						<th colspan="2"><h2><?php esc_html_e( 'Social Media', 'apollo-core' ); ?></h2></th>
					</tr>
					<tr>
						<th><label for="default_og_image"><?php esc_html_e( 'Default Social Image', 'apollo-core' ); ?></label></th>
						<td><input type="url" id="default_og_image" name="apollo_seo[default_og_image]" value="<?php echo esc_url( $settings['default_og_image'] ); ?>" class="large-text" placeholder="https://example.com/image.jpg"></td>
					</tr>
					<tr>
						<th><label for="twitter_username"><?php esc_html_e( 'Twitter Username', 'apollo-core' ); ?></label></th>
						<td><input type="text" id="twitter_username" name="apollo_seo[twitter_username]" value="<?php echo esc_attr( $settings['twitter_username'] ); ?>" class="regular-text" placeholder="@username"></td>
					</tr>

					<tr>
						<th colspan="2"><h2><?php esc_html_e( 'Organization (Schema.org)', 'apollo-core' ); ?></h2></th>
					</tr>
					<tr>
						<th><label for="organization_name"><?php esc_html_e( 'Organization Name', 'apollo-core' ); ?></label></th>
						<td><input type="text" id="organization_name" name="apollo_seo[organization_name]" value="<?php echo esc_attr( $settings['organization_name'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="organization_logo"><?php esc_html_e( 'Logo URL', 'apollo-core' ); ?></label></th>
						<td><input type="url" id="organization_logo" name="apollo_seo[organization_logo]" value="<?php echo esc_url( $settings['organization_logo'] ); ?>" class="large-text"></td>
					</tr>

					<tr>
						<th colspan="2"><h2><?php esc_html_e( 'Indexing', 'apollo-core' ); ?></h2></th>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Noindex Archives', 'apollo-core' ); ?></th>
						<td>
							<label><input type="checkbox" name="apollo_seo[noindex_date]" value="1" <?php checked( $settings['noindex_date'] ); ?>> <?php esc_html_e( 'Date Archives', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[noindex_author]" value="1" <?php checked( $settings['noindex_author'] ); ?>> <?php esc_html_e( 'Author Archives', 'apollo-core' ); ?></label><br>
							<label><input type="checkbox" name="apollo_seo[noindex_search]" value="1" <?php checked( $settings['noindex_search'] ); ?>> <?php esc_html_e( 'Search Results', 'apollo-core' ); ?></label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'apollo-core' ); ?></button>
				</p>
			</form>

			<?php if ( $settings['enable_sitemap'] ) : ?>
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Sitemap URLs', 'apollo-core' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/apollo-sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_url( home_url( '/apollo-sitemap.xml' ) ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/apollo-sitemap-posts.xml' ) ); ?>" target="_blank"><?php echo esc_url( home_url( '/apollo-sitemap-posts.xml' ) ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/apollo-sitemap-pages.xml' ) ); ?>" target="_blank"><?php echo esc_url( home_url( '/apollo-sitemap-pages.xml' ) ); ?></a></li>
				</ul>
				<p><em><?php esc_html_e( 'Note: If sitemaps show 404, go to Settings → Permalinks and click Save.', 'apollo-core' ); ?></em></p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

// Initialize.
Native_SEO::init();
