<?php
/**
 * Tab: Documents
 * File: template-parts/user/tab-docs.php
 */

$user_id = get_current_user_id();
$docs    = apollo_get_user_pending_documents( $user_id, 10 );
?>

<div class="section-header">
	<div class="section-title">Documentos Pendentes</div>
</div>

<div class="cards-grid">
	<?php if ( ! empty( $docs ) ) : ?>
		<?php
		foreach ( $docs as $doc ) :
			$doc_type   = get_post_meta( $doc->ID, 'document_type', true ) ?: 'Documento';
			$doc_format = get_post_meta( $doc->ID, 'document_format', true ) ?: 'PDF';
			?>
		<article class="apollo-card">
			<div class="card-top">
				<span class="card-meta"><?php echo esc_html( $doc_type ); ?></span>
				<span class="status-badge status-maybe">Pendente</span>
			</div>
			<h3 class="card-title"><?php echo esc_html( $doc->post_title ); ?></h3>
			<p class="card-text"><?php echo wp_trim_words( $doc->post_excerpt, 15 ); ?></p>
			<div class="card-footer">
				<span class="mini-tag"><?php echo esc_html( $doc_format ); ?></span>
				<a href="<?php echo apollo_get_signature_url( $doc->ID ); ?>" class="link-action">Assinar <i class="ri-quill-pen-line"></i></a>
			</div>
		</article>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="card-text">Nenhum documento pendente.</p>
	<?php endif; ?>
</div>
