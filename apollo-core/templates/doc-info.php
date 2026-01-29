<?php
/**
 * Document Info Card
 * File: template-parts/sign/doc-info.php
 */

$doc_data     = $args['doc_data'];
$file_meta    = get_post_meta( $doc_data['id'], 'document_file_meta', true ) ?: array(
	'size'     => '2.4 MB',
	'pages'    => 12,
	'filename' => 'documento.pdf',
);
$requested_by = get_post_meta( $doc_data['id'], 'requested_by_name', true ) ?: 'Sistema Apollo';
$deadline     = get_post_meta( $doc_data['id'], 'signature_deadline', true );
?>

<div class="mb-6 p-5 bg-slate-50 rounded-xl border border-slate-200">
	<div class="flex items-start gap-4 mb-4">
		<div class="flex-shrink-0">
			<div class="w-14 h-14 rounded-lg bg-white border border-slate-200 flex items-center justify-center">
				<i class="ri-file-pdf-line text-red-500 text-[22px]"></i>
			</div>
		</div>
		<div class="flex-1 min-w-0">
			<h3 class="text-[15px] font-semibold text-slate-900 mb-1 truncate"><?php echo esc_html( $file_meta['filename'] ); ?></h3>
			<p class="text-[12px] text-slate-500"><?php echo esc_html( $file_meta['size'] ); ?> · <?php echo esc_html( $file_meta['pages'] ); ?> páginas</p>
		</div>
	</div>
	
	<div class="space-y-3 pt-3 border-t border-slate-200/60">
		<div class="flex items-center gap-3">
			<i class="ri-user-3-line text-slate-400 text-[14px]"></i>
			<span class="text-[13px] text-slate-600">Solicitado por: <span class="font-semibold text-slate-900"><?php echo esc_html( $requested_by ); ?></span></span>
		</div>
		<?php if ( $deadline ) : ?>
		<div class="flex items-center gap-3">
			<i class="ri-calendar-line text-slate-400 text-[14px]"></i>
			<span class="text-[13px] text-slate-600">Data limite: <span class="font-semibold text-slate-900"><?php echo date( 'd/m/Y', strtotime( $deadline ) ); ?></span></span>
		</div>
		<?php endif; ?>
	</div>
</div>
