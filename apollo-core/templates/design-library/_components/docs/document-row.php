<?php
/**
 * Single document row entry.
 *
 * @var array $document
 */

$document      = $document ?? [];
$status        = $document['status'] ?? 'draft';
$icon_map      = [
	'contrato'  => 'ri-file-text-line',
	'checklist' => 'ri-clipboard-line',
];
$badge_classes = [
	'signed'  => 'bg-emerald-50 text-emerald-700',
	'draft'   => 'bg-amber-50 text-amber-700',
	'waiting' => 'bg-sky-50 text-sky-700',
];
$badge_icons   = [
	'signed'  => 'ri-shield-check-line',
	'draft'   => 'ri-time-line',
	'waiting' => 'ri-mail-send-line',
];
$badge_labels  = [
	'signed'  => 'Assinado',
	'draft'   => 'Em rascunho',
	'waiting' => 'Aguardando',
];
$badge_class   = $badge_classes[ $status ] ?? 'bg-slate-100 text-slate-600';
$badge_icon    = $badge_icons[ $status ] ?? 'ri-information-line';
$badge_label   = $badge_labels[ $status ] ?? ucfirst( $status );
?>
<button class="aprio-doc-row text-left" data-id="<?php echo esc_attr( $document['id'] ?? '' ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
	<div class="flex items-center gap-3 min-w-0">
		<div class="h-9 w-9 rounded-lg border border-slate-200 flex items-center justify-center bg-slate-50 text-slate-700">
			<i class="<?php echo esc_attr( $icon_map[ $document['type'] ?? '' ] ?? 'ri-file-lock-line' ); ?> text-base"></i>
		</div>
		<div class="min-w-0">
			<div class="flex items-center gap-2">
				<span class="font-semibold text-slate-800 truncate"><?php echo esc_html( $document['title'] ?? 'Documento' ); ?></span>
				<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] <?php echo esc_attr( $badge_class ); ?>">
					<i class="<?php echo esc_attr( $badge_icon ); ?> text-[10px]"></i>
					<?php echo esc_html( $badge_label ); ?>
				</span>
			</div>
			<div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 mt-0.5">
				<?php if ( ! empty( $document['event'] ) ) : ?>
					<span>Evento: <?php echo esc_html( $document['event'] ); ?></span>
					<span>·</span>
				<?php endif; ?>
				<span>Última atualização: <?php echo esc_html( $document['updated_at'] ?? '—' ); ?></span>
			</div>
		</div>
	</div>
	<div class="flex flex-col items-end gap-1 text-[11px] text-slate-500 pl-3">
		<span><?php echo esc_html( $document['signatures'] ?? '0' ); ?> assinaturas</span>
		<span class="inline-flex items-center gap-1 text-slate-400">
			Detalhes <i class="ri-arrow-right-s-line text-xs"></i>
		</span>
	</div>
</button>
