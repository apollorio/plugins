<?php
/**
 * Template Part: Document Header
 * File: template-parts/doc-header.php
 */

global $post;
$doc_status = get_post_meta( $post->ID, 'document_status', true ) ?: 'pending';
$doc_title  = get_the_title();

$status_config = array(
	'pending' => array(
		'label'      => 'Pendente',
		'class'      => 'border-amber-200 bg-amber-50 text-amber-700',
		'icon_class' => 'bg-amber-400',
	),
	'signed'  => array(
		'label'      => 'Assinado',
		'class'      => 'border-emerald-200 bg-emerald-50 text-emerald-700',
		'icon_class' => 'bg-emerald-500',
	),
	'expired' => array(
		'label'      => 'Expirado',
		'class'      => 'border-red-200 bg-red-50 text-red-700',
		'icon_class' => 'bg-red-400',
	),
);

$status = $status_config[ $doc_status ] ?? $status_config['pending'];
?>

<header class="flex-none z-30 relative">
	<div class="px-4 h-16 flex items-center justify-between max-w-7xl mx-auto w-full">
		<!-- Left: Back + Title -->
		<div class="flex items-center gap-3">
			<button type="button" onclick="history.back()" class="h-8 w-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors">
				<i class="ri-arrow-left-line text-lg"></i>
			</button>

			<!-- Mobile Title -->
			<div class="flex flex-col leading-tight md:hidden">
				<span class="text-[10px] uppercase tracking-wider text-slate-400">Documento</span>
				<span class="text-[13px] font-semibold text-slate-900">Assinar Digitalmente</span>
			</div>

			<!-- Desktop Title -->
			<div class="hidden md:flex flex-col leading-tight ml-2">
				<h1 class="text-xl font-bold text-slate-900"><?php echo esc_html( $doc_title ); ?></h1>
				<p class="text-[12px] text-slate-500">Fluxo seguro, auditável e disponível para toda a rede Apollo</p>
			</div>
		</div>

		<!-- Right: Status -->
		<div class="flex items-center gap-3">
			<span id="doc-status-pill-header" class="hidden md:inline-flex items-center gap-1.5 rounded-full border <?php echo $status['class']; ?> px-3 py-1 text-xs font-medium">
				<span class="inline-flex h-2 w-2 rounded-full <?php echo $status['icon_class']; ?> <?php echo $doc_status === 'pending' ? 'animate-pulse' : ''; ?>"></span>
				<?php echo $status['label']; ?>
			</span>
		</div>
	</div>
</header>
