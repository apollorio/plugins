<?php
// phpcs:ignoreFile
declare(strict_types=1);

/**
 * Apollo Canvas Template
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$canvas_data = apply_filters( 'apollo_canvas_template_data', array() );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $canvas_data['title'] ?? get_the_title() ); ?> | <?php bloginfo( 'name' ); ?></title>

	<!-- Preconnect to CDNs -->
	<link rel="preconnect" href="https://cdn.jsdelivr.net">
	<link rel="preconnect" href="https://assets.apollo.rio.br">

	<?php wp_head(); ?>

	<style>
		/* Canvas Mode Isolation */
		body.apollo-canvas-mode {
			margin: 0 !important;
			padding: 0 !important;
			background: #fafafa !important;
		}

		/* Hide theme elements */
		body.apollo-canvas-mode header:not(.apollo-header),
		body.apollo-canvas-mode footer:not(.apollo-footer),
		body.apollo-canvas-mode .site-header,
		body.apollo-canvas-mode .site-footer,
		body.apollo-canvas-mode nav:not(.apollo-nav),
		body.apollo-canvas-mode .sidebar,
		body.apollo-canvas-mode .widget-area {
			display: none !important;
		}

		/* Show Apollo elements */
		body.apollo-canvas-mode .apollo-canvas-main {
			display: block !important;
		}
	</style>
</head>
<body <?php body_class( 'apollo-canvas-mode' ); ?>>

<main class="apollo-canvas-main" id="apollo-canvas-main">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php the_content(); ?>
			</article>
			<?php
		endwhile;
	endif;
	?>
</main>

<?php wp_footer(); ?>

</body>
</html>

