<?php
/**
 * Banner Loader Partial
 * Fetches latest highlight/news post for dynamic banner injection.
 *
 * STRICT MODE: WP_Query with optimization, proper escaping, mobile-first.
 *
 * @package Apollo_Events_Manager
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get banner data from latest highlight post.
 *
 * @param string $category_name Category slug to filter (default: 'highlight').
 * @return array{img_url: string, title: string, excerpt: string, permalink: string, category: string}|null
 */
function apollo_get_banner_data( string $category_name = 'highlight' ): ?array {
	// Optimized query - no pagination count needed.
	$args = array(
		'posts_per_page'         => 1,
		'category_name'          => $category_name,
		'post_status'            => 'publish',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	$banner_query = new WP_Query( $args );

	if ( ! $banner_query->have_posts() ) {
		// Fallback to 'news' category if no highlights.
		if ( 'highlight' === $category_name ) {
			return apollo_get_banner_data( 'news' );
		}
		return null;
	}

	$banner_query->the_post();

	$post_id = get_the_ID();

	// Get featured image or fallback.
	$img_url = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( empty( $img_url ) ) {
		$img_url = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
	}

	// Get category for label.
	$categories = get_the_category( $post_id );
	$category   = ! empty( $categories ) ? $categories[0]->name : __( 'Destaque', 'apollo-events-manager' );

	// Build banner data array.
	$banner = array(
		'img_url'   => $img_url,
		'title'     => get_the_title(),
		'excerpt'   => has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 25, '...' ),
		'permalink' => get_permalink(),
		'category'  => $category,
	);

	wp_reset_postdata();

	return $banner;
}

/**
 * Render the banner HTML.
 *
 * @param array|null $banner Banner data array or null.
 * @return void
 */
function apollo_render_banner( ?array $banner = null ): void {
	if ( null === $banner ) {
		$banner = apollo_get_banner_data();
	}

	if ( null === $banner ) {
		return;
	}
	?>
	<section class="banner-ario-1-wrapper">
		<img
			src="<?php echo esc_url( $banner['img_url'] ); ?>"
			alt="<?php echo esc_attr( $banner['title'] ); ?>"
			class="ban-ario-1-img"
			loading="lazy"
		>
		<div class="ban-ario-1-content">
			<mark class="ban-ario-1-subtit"><?php echo esc_html( $banner['category'] ); ?></mark>
			<h3 class="ban-ario-1-title"><?php echo esc_html( $banner['title'] ); ?></h3>
			<p class="ban-ario-1-excerpt"><?php echo esc_html( $banner['excerpt'] ); ?></p>
			<a href="<?php echo esc_url( $banner['permalink'] ); ?>" class="ban-ario-1-cta">
				<?php esc_html_e( 'Saiba Mais', 'apollo-events-manager' ); ?>
				<i class="ri-arrow-right-line"></i>
			</a>
		</div>
	</section>

	<style>
		/* ═══════════════════════════════════════════════════════════════════════
			BANNER: Mobile-First with <mark> Category Label
			═══════════════════════════════════════════════════════════════════════ */
		.banner-ario-1-wrapper {
			display: flex;
			flex-direction: column;
			gap: 1.5rem;
			padding: 1.5rem;
			margin: 1.25rem 0;
			background: var(--border-color-2, rgba(224, 226, 228, 0.33));
			border: 1px solid var(--border-color, #e0e2e4);
			border-radius: var(--radius-sec, 20px);
			backdrop-filter: blur(8px);
			overflow: hidden;
		}

		.ban-ario-1-img {
			width: 100%;
			height: 200px;
			border-radius: var(--radius-main, 12px);
			object-fit: cover;
			transition: transform 0.3s ease;
		}

		.banner-ario-1-wrapper:hover .ban-ario-1-img {
			transform: scale(1.03);
		}

		.ban-ario-1-content {
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
		}

		/* Category Label - MUST use <mark> with orange styling */
		.ban-ario-1-subtit {
			display: inline-block;
			width: fit-content;
			padding: 0.25rem 0.75rem;
			margin: 0;
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #fd5c02;
			background-color: rgba(253, 92, 2, 0.1);
			border-radius: 4px;
		}

		.ban-ario-1-title {
			margin: 0;
			font-size: 1.5rem;
			font-weight: 700;
			line-height: 1.2;
			color: var(--text-primary, rgba(19, 21, 23, 0.85));
		}

		.ban-ario-1-excerpt {
			margin: 0;
			font-size: 0.875rem;
			line-height: 1.5;
			color: var(--text-secondary, rgba(19, 21, 23, 0.7));
		}

		.ban-ario-1-cta {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			width: fit-content;
			padding: 0.625rem 1.25rem;
			margin-top: 0.5rem;
			font-size: 0.875rem;
			font-weight: 600;
			color: #fff;
			background: linear-gradient(135deg, #fd5c02, #ff7a33);
			border-radius: 8px;
			text-decoration: none;
			transition: transform 0.2s, box-shadow 0.2s;
		}

		.ban-ario-1-cta:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(253, 92, 2, 0.3);
		}

		.ban-ario-1-cta i {
			font-size: 1rem;
			transition: transform 0.2s;
		}

		.ban-ario-1-cta:hover i {
			transform: translateX(3px);
		}

		/* Tablet & Desktop */
		@media (min-width: 768px) {
			.banner-ario-1-wrapper {
				flex-direction: row;
				align-items: center;
				gap: 3rem;
				padding: 3rem;
			}

			.ban-ario-1-img {
				width: 40%;
				max-width: 400px;
				height: 280px;
				flex-shrink: 0;
			}

			.ban-ario-1-content {
				flex: 1;
			}

			.ban-ario-1-title {
				font-size: 2rem;
			}
		}

		/* Dark Mode */
		body.dark-mode .banner-ario-1-wrapper {
			background: rgba(51, 53, 55, 0.5);
			border-color: #333537;
		}

		body.dark-mode .ban-ario-1-title {
			color: var(--text-primary, #fdfdfdfa);
		}

		body.dark-mode .ban-ario-1-excerpt {
			color: var(--text-secondary, #ffffff91);
		}
	</style>
	<?php
}
