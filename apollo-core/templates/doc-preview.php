<?php
/**
 * Template Part: Document Preview
 * File: template-parts/doc-preview.php
 */

global $post;
$doc_id          = get_post_meta( $post->ID, 'document_code', true ) ?: 'APR-DOC-' . date( 'Y' ) . '-' . str_pad( $post->ID, 5, '0', STR_PAD_LEFT );
$doc_category    = get_post_meta( $post->ID, 'document_category', true ) ?: 'Geral';
$doc_subcategory = get_post_meta( $post->ID, 'document_subcategory', true );
$created_date    = get_the_date( 'd \d\e M. \d\e Y' );
$page_count      = get_post_meta( $post->ID, 'document_pages', true ) ?: 2;
$doc_content     = get_the_content();
?>

<div class="flex-1 flex flex-col gap-4 min-w-0">
	<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 md:p-6 flex-1 flex flex-col min-h-[500px]">
		<!-- Document Header Info -->
		<div class="flex flex-col gap-4 mb-6 border-b border-slate-100 pb-6">
			<div class="flex items-start justify-between gap-4">
				<div>
					<div class="flex items-center gap-2 mb-1">
						<span id="doc-category" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase tracking-wide">
							<?php echo esc_html( $doc_category ); ?>
							<?php if ( $doc_subcategory ) : ?>
								· <?php echo esc_html( $doc_subcategory ); ?>
							<?php endif; ?>
						</span>
						<span id="doc-id" class="text-[10px] text-slate-400 font-mono"><?php echo esc_html( $doc_id ); ?></span>
					</div>
					<h2 id="doc-title" class="text-lg md:text-xl font-bold text-slate-900 leading-snug">
						<?php echo esc_html( get_the_title() ); ?>
					</h2>
				</div>
			</div>

			<div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
				<span id="doc-meta-main" class="flex items-center gap-1.5">
					<i class="ri-time-line"></i> Criado em <?php echo $created_date; ?>
				</span>
				<span id="doc-meta-second" class="flex items-center gap-1.5">
					<i class="ri-file-list-2-line"></i> <?php echo $page_count; ?> página<?php echo $page_count > 1 ? 's' : ''; ?>
				</span>
			</div>
		</div>

		<!-- PDF Preview Container -->
		<div class="flex-1 bg-slate-50 rounded-xl border border-slate-200 relative overflow-hidden flex flex-col">
			<!-- Toolbar -->
			<div class="flex items-center justify-between px-4 py-2 bg-white border-b border-slate-200">
				<div class="flex items-center gap-2">
					<span class="h-2.5 w-2.5 rounded-full bg-red-400/80"></span>
					<span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
					<span class="h-2.5 w-2.5 rounded-full bg-green-400/80"></span>
				</div>
				<span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Preview Visual</span>
			</div>

			<!-- Document Content -->
			<div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
				<div class="max-w-[600px] mx-auto bg-white shadow-lg border border-slate-100 min-h-[600px] p-8 md:p-12 text-slate-800 text-sm leading-relaxed space-y-4">
					<!-- Header -->
					<div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
						<div>
							<p class="text-[10px] uppercase tracking-widest text-slate-400">Apollo::Rio · Documento Oficial</p>
							<h3 class="font-bold text-base"><?php echo esc_html( get_the_title() ); ?></h3>
						</div>
						<div class="h-8 w-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center shadow-sm shadow-orange-500/50">
							<i class="ri-slack-fill text-white text-sm"></i>
						</div>
					</div>

					<!-- Content -->
					<div class="document-content">
						<?php echo wpautop( $doc_content ); ?>
					</div>

					<div class="mt-8 pt-4 border-t border-dashed border-slate-200">
						<p class="text-[10px] text-slate-400 text-center">Fim do documento (Preview)</p>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
