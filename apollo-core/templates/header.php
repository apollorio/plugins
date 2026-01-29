<?php
/**
 * Sign Header
 * File: template-parts/sign/header.php
 */

$doc_data = $args['doc_data'];
?>

<div class="text-center mb-8">
	<div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 mb-4">
		<i class="ri-file-text-line text-white text-3xl"></i>
	</div>
	<h1 class="text-3xl font-extrabold text-slate-900 mb-2">Assinar Documento</h1>
	<p class="text-slate-500 text-[14px]"><?php echo esc_html( $doc_data['title'] ); ?></p>
</div>
