<?php
/**
 * Action Buttons
 * File: template-parts/sign/actions.php
 */

$document_id = $args['document_id'];
$user_signed = $args['user_signed'];
$doc_url     = get_post_meta( $document_id, 'document_url', true );
?>

<div class="flex flex-col gap-3 mt-8">
	<?php if ( ! $user_signed ) : ?>
	<button id="btn-sign-doc" data-doc-id="<?php echo $document_id; ?>" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold text-[14px] transition-all active:scale-[0.98] shadow-md">
		<i class="ri-quill-pen-line text-[18px]"></i>
		Assinar Documento
	</button>
	<?php else : ?>
	<div class="w-full p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-center">
		<i class="ri-checkbox-circle-fill text-emerald-600 text-2xl mb-2"></i>
		<p class="text-[13px] font-semibold text-emerald-700">Documento assinado com sucesso</p>
	</div>
	<?php endif; ?>
	
	<?php if ( $doc_url ) : ?>
	<a href="<?php echo esc_url( $doc_url ); ?>" target="_blank" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-50 text-slate-900 rounded-lg font-semibold text-[14px] transition-colors">
		<i class="ri-eye-line text-[18px]"></i>
		Visualizar PDF
	</a>
	<?php endif; ?>
	
	<?php if ( ! $user_signed ) : ?>
	<button id="btn-refuse-doc" class="w-full flex items-center justify-center gap-2 px-6 py-3 text-slate-500 hover:text-slate-900 rounded-lg font-medium text-[13px] transition-colors">
		<i class="ri-close-line text-[16px]"></i>
		Recusar Assinatura
	</button>
	<?php endif; ?>
</div>

<?php
/**
 * Footer Note
 * File: template-parts/sign/footer-note.php - inline below for efficiency
 */
?>

<div class="mt-6 pt-4 border-t border-slate-200/60">
	<p class="text-[11px] text-slate-400 text-center leading-relaxed">
		Ao assinar, você concorda com os <a href="<?php echo home_url( '/terms' ); ?>" class="text-slate-600 hover:text-slate-900 font-medium">termos de uso</a> e confirma a autenticidade do documento.
	</p>
</div>
