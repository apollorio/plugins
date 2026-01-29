<?php
/**
 * Template Part: Document Breadcrumb
 * File: template-parts/doc-breadcrumb.php
 */

global $post;
$doc_title    = get_the_title();
$doc_category = get_post_meta( $post->ID, 'document_category', true ) ?: 'Geral';
?>

<div class="hidden md:flex items-center gap-2 text-[11px] text-slate-500 mb-4 max-w-7xl mx-auto px-1">
	<a href="<?php echo home_url( '/documents' ); ?>" class="hover:text-slate-900 cursor-pointer">Documentos</a>
	<span class="text-slate-300">/</span>
	<span class="hover:text-slate-900 cursor-pointer"><?php echo esc_html( $doc_category ); ?></span>
	<span class="text-slate-300">/</span>
	<span class="text-slate-900 font-medium truncate" id="bc-doc-title"><?php echo esc_html( $doc_title ); ?></span>
</div>
