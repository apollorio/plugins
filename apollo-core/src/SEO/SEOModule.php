<?php
/**
 * Apollo SEO Module
 * Ultra-Pro WordPress Structure: SEO Pillar
 *
 * Implements advanced schema.org JSON-LD with performance optimizations.
 * Uses MutationObserver for dynamic updates, CDN for assets.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO Optimization Module with Ultra-Pro Schema
 */
final class SEOModule {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Schema cache.
	 *
	 * @var array
	 */
	private array $schema_cache = [];

	/**
	 * CDN base URL.
	 */
	private const CDN_BASE = 'https://cdn.apollo.rio.br';

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize SEO optimizations.
	 *
	 * @return void
	 */
	public function init(): void {
		// Inject schema in head (non-blocking)
		add_action( 'wp_head', [ $this, 'injectSchemaJsonLd' ], 1 );

		// Dynamic schema updates via MutationObserver
		add_action( 'wp_footer', [ $this, 'injectMutationObserverScript' ], 999 );

		// Meta tags optimization
		add_action( 'wp_head', [ $this, 'optimizeMetaTags' ], 0 );

		// Sitemap generation
		add_action( 'init', [ $this, 'registerSitemapRoutes' ] );

		// Robots.txt optimization
		add_filter( 'robots_txt', [ $this, 'optimizeRobotsTxt' ], 10, 2 );
	}

	/**
	 * Inject optimized JSON-LD schema in head.
	 *
	 * @return void
	 */
	public function injectSchemaJsonLd(): void {
		$schema = $this->generateSchemaJsonLd();

		if ( empty( $schema ) ) {
			return;
		}

		// Minified JSON-LD (no whitespace/comments)
		$json_ld = json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		// Compress if supported
		if ( function_exists( 'gzencode' ) ) {
			$json_ld = gzencode( $json_ld );
			header( 'Content-Encoding: gzip' );
		}

		echo '<script type="application/ld+json">' . $json_ld . '</script>' . "\n";
	}

	/**
	 * Generate comprehensive schema.org JSON-LD.
	 *
	 * @return array
	 */
	private function generateSchemaJsonLd(): array {
		$cache_key = 'schema_' . ( is_singular() ? get_the_ID() : 'archive' );

		if ( isset( $this->schema_cache[ $cache_key ] ) ) {
			return $this->schema_cache[ $cache_key ];
		}

		$schema = [];

		// Organization schema (always present)
		$schema[] = $this->getOrganizationSchema();

		// WebSite schema
		$schema[] = $this->getWebSiteSchema();

		// Breadcrumb schema
		if ( function_exists( 'yoast_breadcrumb' ) || function_exists( 'breadcrumb_trail' ) ) {
			$schema[] = $this->getBreadcrumbSchema();
		}

		// Article/Product/Event schemas based on content
		if ( is_singular() ) {
			$post_type = get_post_type();
			switch ( $post_type ) {
				case 'post':
					$schema[] = $this->getArticleSchema();
					break;
				case 'product':
					$schema[] = $this->getProductSchema();
					break;
				case 'event_listing':
					$schema[] = $this->getEventSchema();
					break;
			}
		}

		// Search results schema
		if ( is_search() ) {
			$schema[] = $this->getSearchResultsSchema();
		}

		$this->schema_cache[ $cache_key ] = $schema;

		return $schema;
	}

	/**
	 * Get organization schema.
	 *
	 * @return array
	 */
	private function getOrganizationSchema(): array {
		return [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url(),
			'logo'     => $this->getLogoUrl(),
			'sameAs'   => $this->getSocialProfiles(),
		];
	}

	/**
	 * Get website schema.
	 *
	 * @return array
	 */
	private function getWebSiteSchema(): array {
		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url(),
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			],
		];
	}

	/**
	 * Get article schema for posts.
	 *
	 * @return array
	 */
	private function getArticleSchema(): array {
		global $post;

		return [
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => get_the_title(),
			'description'   => $this->getExcerpt(),
			'author'        => $this->getAuthorSchema(),
			'publisher'     => $this->getOrganizationSchema(),
			'datePublished' => get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'image'         => $this->getFeaturedImageUrl(),
			'mainEntityOfPage' => [
				'@type' => 'WebPage',
				'@id'   => get_permalink(),
			],
		];
	}

	/**
	 * Get product schema.
	 *
	 * @return array
	 */
	private function getProductSchema(): array {
		global $post;

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => get_the_title(),
			'description' => $this->getExcerpt(),
			'image'    => $this->getFeaturedImageUrl(),
			'offers'   => [
				'@type' => 'Offer',
				'price' => $this->getProductPrice(),
				'priceCurrency' => 'BRL',
				'availability' => 'https://schema.org/InStock',
			],
		];

		return $schema;
	}

	/**
	 * Get event schema.
	 *
	 * @return array
	 */
	private function getEventSchema(): array {
		global $post;

		return [
			'@context'    => 'https://schema.org',
			'@type'       => 'Event',
			'name'        => get_the_title(),
			'description' => $this->getExcerpt(),
			'startDate'   => get_post_meta( get_the_ID(), 'event_date', true ),
			'location'    => [
				'@type'   => 'Place',
				'name'    => get_post_meta( get_the_ID(), 'venue_name', true ),
				'address' => get_post_meta( get_the_ID(), 'venue_address', true ),
			],
			'image' => $this->getFeaturedImageUrl(),
		];
	}

	/**
	 * Get breadcrumb schema.
	 *
	 * @return array
	 */
	private function getBreadcrumbSchema(): array {
		$items = [];
		$position = 1;

		// Home
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => 'Home',
			'item'     => home_url(),
		];

		if ( is_category() ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => single_cat_title( '', false ),
				'item'     => get_category_link( get_queried_object_id() ),
			];
		} elseif ( is_single() ) {
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				$items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $categories[0]->name,
					'item'     => get_category_link( $categories[0]->term_id ),
				];
			}

			$items[] = [
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => get_the_title(),
				'item'     => get_permalink(),
			];
		}

		return [
			'@context'      => 'https://schema.org',
			'@type'         => 'BreadcrumbList',
			'itemListElement' => $items,
		];
	}

	/**
	 * Get search results schema.
	 *
	 * @return array
	 */
	private function getSearchResultsSchema(): array {
		global $wp_query;

		return [
			'@context'      => 'https://schema.org',
			'@type'         => 'SearchResultsPage',
			'name'          => 'Search Results for "' . get_search_query() . '"',
			'description'   => 'Search results for "' . get_search_query() . '" - ' . $wp_query->found_posts . ' results found',
			'url'           => get_search_link( get_search_query() ),
		];
	}

	/**
	 * Inject MutationObserver script for dynamic schema updates.
	 *
	 * @return void
	 */
	public function injectMutationObserverScript(): void {
		$script = $this->getMutationObserverScript();
		echo '<script>' . $script . '</script>';
	}

	/**
	 * Get MutationObserver script for dynamic content updates.
	 *
	 * @return string
	 */
	private function getMutationObserverScript(): string {
		return "
		(function() {
			if (typeof window.apolloSchemaLoaded === 'undefined') {
				window.apolloSchemaLoaded = true;

				var observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(mutation) {
						if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
							// Check if new content needs schema update
							var needsUpdate = false;
							mutation.addedNodes.forEach(function(node) {
								if (node.nodeType === 1 && (node.matches('.post, .product, .event') || node.querySelector('.post, .product, .event'))) {
									needsUpdate = true;
								}
							});

							if (needsUpdate) {
								// Trigger schema regeneration (debounced)
								if (window.apolloSchemaTimeout) {
									clearTimeout(window.apolloSchemaTimeout);
								}
								window.apolloSchemaTimeout = setTimeout(function() {
									window.location.reload(); // Simple reload for schema update
								}, 1000);
							}
						}
					});
				});

				observer.observe(document.body, {
					childList: true,
					subtree: true
				});
			}
		})();
		";
	}

	/**
	 * Optimize meta tags.
	 *
	 * @return void
	 */
	public function optimizeMetaTags(): void {
		// Remove duplicate meta tags
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );

		// Add optimized meta tags
		if ( is_singular() ) {
			$this->addOpenGraphTags();
			$this->addTwitterCardTags();
		}
	}

	/**
	 * Add Open Graph tags.
	 *
	 * @return void
	 */
	private function addOpenGraphTags(): void {
		echo '<meta property="og:type" content="' . ( is_single() ? 'article' : 'website' ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

		if ( has_post_thumbnail() ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( $image ) {
				echo '<meta property="og:image" content="' . esc_url( $image[0] ) . '">' . "\n";
			}
		}
	}

	/**
	 * Add Twitter Card tags.
	 *
	 * @return void
	 */
	private function addTwitterCardTags(): void {
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $this->getExcerpt() ) . '">' . "\n";

		if ( has_post_thumbnail() ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( $image ) {
				echo '<meta name="twitter:image" content="' . esc_url( $image[0] ) . '">' . "\n";
			}
		}
	}

	/**
	 * Register sitemap routes.
	 *
	 * @return void
	 */
	public function registerSitemapRoutes(): void {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?sitemap=1', 'top' );
		add_rewrite_rule( '^sitemap-([^/]+)\.xml$', 'index.php?sitemap=$matches[1]', 'top' );
	}

	/**
	 * Optimize robots.txt.
	 *
	 * @param string $output Robots.txt content.
	 * @param bool   $public Whether the site is public.
	 * @return string
	 */
	public function optimizeRobotsTxt( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$apollo_rules = "\n# Apollo SEO Rules\n";
		$apollo_rules .= "Sitemap: " . home_url( '/sitemap.xml' ) . "\n";
		$apollo_rules .= "Disallow: /wp-admin/\n";
		$apollo_rules .= "Disallow: /wp-includes/\n";
		$apollo_rules .= "Disallow: /wp-content/plugins/\n";
		$apollo_rules .= "Allow: /wp-content/uploads/\n";

		return $output . $apollo_rules;
	}

	/**
	 * Helper: Get logo URL.
	 *
	 * @return string
	 */
	private function getLogoUrl(): string {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $logo ) {
				return $logo[0];
			}
		}
		return self::CDN_BASE . '/logo.png';
	}

	/**
	 * Helper: Get social profiles.
	 *
	 * @return array
	 */
	private function getSocialProfiles(): array {
		$profiles = [];
		$social_options = get_option( 'apollo_social_profiles', [] );

		if ( ! empty( $social_options['facebook'] ) ) {
			$profiles[] = $social_options['facebook'];
		}
		if ( ! empty( $social_options['twitter'] ) ) {
			$profiles[] = $social_options['twitter'];
		}
		if ( ! empty( $social_options['instagram'] ) ) {
			$profiles[] = $social_options['instagram'];
		}

		return $profiles;
	}

	/**
	 * Helper: Get excerpt.
	 *
	 * @return string
	 */
	private function getExcerpt(): string {
		if ( has_excerpt() ) {
			return get_the_excerpt();
		}

		$content = get_the_content();
		$content = wp_strip_all_tags( $content );
		return wp_trim_words( $content, 30 );
	}

	/**
	 * Helper: Get author schema.
	 *
	 * @return array
	 */
	private function getAuthorSchema(): array {
		$author_id = get_the_author_meta( 'ID' );

		return [
			'@type' => 'Person',
			'name'  => get_the_author(),
			'url'   => get_author_posts_url( $author_id ),
		];
	}

	/**
	 * Helper: Get featured image URL.
	 *
	 * @return string
	 */
	private function getFeaturedImageUrl(): string {
		if ( has_post_thumbnail() ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( $image ) {
				return $image[0];
			}
		}
		return self::CDN_BASE . '/default-image.jpg';
	}

	/**
	 * Helper: Get product price.
	 *
	 * @return string
	 */
	private function getProductPrice(): string {
		// Placeholder - integrate with WooCommerce if available
		return get_post_meta( get_the_ID(), '_price', true ) ?: '0.00';
	}
}
