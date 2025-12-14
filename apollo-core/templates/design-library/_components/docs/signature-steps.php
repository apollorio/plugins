<?php
/**
 * Signature action timeline, highlighting current step.
 *
 * @var array $steps
 */

$steps = $steps ?? [
	[
		'label'   => 'Prepare o documento',
		'time'    => 'Hoje, 09:12',
		'context' => 'Upload e campos marcados automaticamente.',
		'variant' => 'done',
	],
	[
		'label'   => 'Assine digitalmente',
		'time'    => 'Prazo: Hoje, 18:00',
		'context' => 'Utilize assinatura ICP válida ou assinatura simples.',
		'variant' => 'current',
	],
	[
		'label'   => 'Envie para o parceiro',
		'time'    => 'Após assinatura',
		'context' => 'Fluxo automático com auditoria completa.',
		'variant' => 'up-next',
	],
];
?>
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white rounded-apcard p-5 shadow-lg">
	<header class="flex items-center justify-between mb-4">
		<div>
			<p class="text-xs uppercase tracking-[0.2em] text-amber-200 font-semibold">Próxima Ação</p>
			<h3 class="text-lg font-black">Finalize o fluxo de assinatura</h3>
		</div>
		<button class="px-3 py-1.5 text-xs font-bold rounded-full bg-white/15 hover:bg-white/25 transition">
			Ver auditoria
		</button>
	</header>
	<ol class="space-y-4">
		<?php
		foreach ( $steps as $index => $step ) :
			$variant = $step['variant'] ?? 'up-next';
			$colors  = [
				'done'    => 'bg-emerald-400 text-slate-900',
				'current' => 'bg-white text-slate-900 animate-pulse',
				'up-next' => 'bg-white/20 text-white',
			];
			?>
			<li class="flex items-start gap-3">
				<span class="h-7 w-7 flex items-center justify-center rounded-full text-[11px] font-black <?php echo esc_attr( $colors[ $variant ] ?? $colors['up-next'] ); ?>">
					<?php echo esc_html( $index + 1 ); ?>
				</span>
				<div class="flex-1">
					<p class="text-xs font-bold tracking-wide uppercase <?php echo 'current' === $variant ? 'text-amber-300' : 'text-slate-100'; ?>">
						<?php echo esc_html( $step['label'] ?? '' ); ?>
					</p>
					<p class="text-[11px] text-slate-300">
						<?php echo esc_html( $step['time'] ?? '' ); ?>
					</p>
					<p class="text-[11px] text-slate-200 mt-1">
						<?php echo esc_html( $step['context'] ?? '' ); ?>
					</p>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<div class="mt-5 flex flex-wrap gap-2">
		<button class="flex-1 text-xs font-bold uppercase tracking-wide bg-amber-400 text-slate-900 rounded-lg py-2 shadow-sm">
			Assinar agora
		</button>
		<button class="px-3 py-2 text-xs font-medium text-white/80 border border-white/20 rounded-lg">
			Baixar PDF
		</button>
	</div>
</section>
