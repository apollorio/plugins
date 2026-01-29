<?php
/**
 * Template Name: Single Document (Sign)
 * Description: Document signing page with sidebar navigation
 */

get_header(); // Navbar loaded via wp_footer hook from class-apollo-navbar-apps.php

global $post;
$document_id = get_the_ID();
$doc_data    = apollo_get_document_data( $document_id );
?>

<div class="min-h-screen flex bg-slate-50">

	<?php get_template_part( 'template-parts/sidebar', 'doc' ); ?>

	<div class="flex-1 flex flex-col h-full relative overflow-hidden bg-slate-50/50">

		<?php get_template_part( 'template-parts/doc', 'header' ); ?>

		<main class="flex-1 overflow-y-auto scroll-smooth relative p-4 md:p-6" id="mainContainer">

			<?php get_template_part( 'template-parts/doc', 'breadcrumb' ); ?>

			<div class="max-w-7xl mx-auto w-full h-full flex flex-col lg:flex-row gap-6 pb-20 md:pb-0">

				<?php get_template_part( 'template-parts/doc', 'preview' ); ?>

				<?php get_template_part( 'template-parts/doc', 'signature-panel' ); ?>

			</div>
		</main>

		<?php get_template_part( 'template-parts/navigation', 'mobile' ); ?>

	</div>
</div>

<?php get_footer(); ?>
