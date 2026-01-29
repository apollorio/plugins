<?php
/**
 * Template Name: Document Sign (Centered)
 * Description: Centered document signing with animated background
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$document_id  = get_query_var( 'document_id' ) ?: get_the_ID();
$current_user = wp_get_current_user();
$doc_data     = apollo_get_document_data( $document_id );
$user_signed  = apollo_check_user_signed( $document_id, $current_user->ID );

get_header( 'minimal' );
?>

<!-- Apollo Background -->
<?php get_template_part( 'template-parts/apollo-background' ); ?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen flex items-center justify-center p-4 md:p-8">
	<div class="w-full max-w-[600px] bg-white/95 backdrop-blur-xl rounded-3xl shadow-[0_8px_20px_-6px_rgba(15,23,42,0.05)] p-8 md:p-12">
		
		<?php get_template_part( 'template-parts/sign/header', null, array( 'doc_data' => $doc_data ) ); ?>
		
		<?php get_template_part( 'template-parts/sign/doc-info', null, array( 'doc_data' => $doc_data ) ); ?>
		
		<?php if ( ! $user_signed ) : ?>
			<?php get_template_part( 'template-parts/sign/status-banner' ); ?>
		<?php endif; ?>
		
		<?php
		get_template_part(
			'template-parts/sign/signatories',
			null,
			array(
				'doc_data'    => $doc_data,
				'user_signed' => $user_signed,
			)
		);
		?>
		
		<?php
		get_template_part(
			'template-parts/sign/actions',
			null,
			array(
				'document_id' => $document_id,
				'user_signed' => $user_signed,
			)
		);
		?>
		
		<?php get_template_part( 'template-parts/sign/footer-note' ); ?>
		
	</div>
</div>

<?php get_footer( 'minimal' ); ?>
