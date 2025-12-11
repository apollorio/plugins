<?php
/**
 * Sidebar cards with stats, quick actions and footer links.
 *
 * @var array $stats
 * @var array $quick_actions
 * @var array $shortcuts
 */

$stats = wp_parse_args(
	$stats ?? array(),
	array(
		'created' => '24',
		'signed'  => '18',
		'pending' => '6',
	)
);

$quick_actions = $quick_actions ?? array(
	array('icon' => 'ri-add-line', 'label' => 'Novo documento em branco', 'href' => '/doc/new'),
	array('icon' => 'ri-upload-2-line', 'label' => 'Subir PDF para assinatura', 'href' => '#'),
	array('icon' => 'ri-download-2-line', 'label' => 'Exportar como PDF', 'href' => '#'),
);

$shortcuts = $shortcuts ?? array(
	array('label' => 'Privacidade', 'href' => '#'),
	array('label' => 'Termos', 'href' => '#'),
);
?>
<aside class="hidden lg:block space-y-4">
	<div class="aprioEXP-card-shell p-4">
		<div class="flex items-center justify-between mb-2">
			<h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500">Resumo de assinaturas</h2>
			<span class="text-[10px] text-slate-400">Últimos 30 dias</span>
		</div>

		<div class="space-y-3 text-[12px]">
			<div class="flex items-center justify-between">
				<span class="text-slate-600">Documentos criados</span>
				<span class="font-semibold text-slate-900"><?php echo esc_html( $stats['created'] ); ?></span>
			</div>
			<div class="flex items-center justify-between">
				<span class="text-slate-600">Assinaturas concluídas</span>
				<span class="font-semibold text-emerald-600"><?php echo esc_html( $stats['signed'] ); ?></span>
			</div>
			<div class="flex items-center justify-between">
				<span class="text-slate-600">Pendentes aguardando</span>
				<span class="font-semibold text-amber-600"><?php echo esc_html( $stats['pending'] ); ?></span>
			</div>
		</div>

		<hr class="my-3 border-slate-100">

		<div class="space-y-2 text-[11px]">
			<span class="text-[10px] font-bold text-slate-400 uppercase">Modelos rápidos</span>
			<div class="flex flex-wrap gap-1.5">
				<button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
					Contrato padrão DJ
				</button>
				<button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
					Termo de Comunidade
				</button>
				<button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
					Checklist Operacional
				</button>
			</div>
		</div>
	</div>

	<div class="aprioEXP-card-shell p-4">
		<h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500 mb-3">Ações rápidas</h2>
		<div class="space-y-2 text-[12px]">
			<?php foreach ( $quick_actions as $action ) : ?>
				<a href="<?php echo esc_url( $action['href'] ?? '#' ); ?>" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-50">
					<span class="flex items-center gap-2">
						<i class="<?php echo esc_attr( $action['icon'] ?? 'ri-arrow-right-s-line' ); ?> text-slate-500 text-sm"></i>
						<span><?php echo esc_html( $action['label'] ?? '' ); ?></span>
					</span>
					<i class="ri-arrow-right-s-line text-slate-400 text-xs"></i>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="flex flex-wrap gap-2 text-[10px] text-slate-400 px-2">
		<?php foreach ( $shortcuts as $index => $link ) : ?>
			<a href="<?php echo esc_url( $link['href'] ); ?>" class="hover:underline"><?php echo esc_html( $link['label'] ); ?></a>
			<?php if ( $index < count( $shortcuts ) - 1 ) : ?>
				<span>·</span>
			<?php endif; ?>
		<?php endforeach; ?>
		<span>© <?php echo esc_html( date_i18n( 'Y' ) ); ?> Apollo Rio</span>
	</div>
</aside>
