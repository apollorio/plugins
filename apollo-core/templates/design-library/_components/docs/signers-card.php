<?php
/**
 * List of signers with current status.
 *
 * @var array $signers
 * @var string $count_label
 */

$signers     = $signers ?? [
	[
		'name'    => 'Você',
		'avatar'  => 'VC',
		'subline' => 'CPF ***.***.***-**',
		'color'   => 'bg-amber-50 border border-amber-100',
		'badge'   => [
			'label' => 'Pendente',
			'class' => 'text-amber-600 bg-white',
			'icon'  => '<span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>',
		],
	],
	[
		'name'    => 'Parceiro / Terceiro',
		'avatar'  => 'PT',
		'subline' => 'Externo à plataforma',
		'color'   => 'bg-white border border-slate-100',
		'badge'   => [
			'label' => 'Assinado',
			'class' => 'bg-emerald-50 text-emerald-600',
			'icon'  => '<i class="ri-check-line"></i>',
		],
	],
];
$count_label = $count_label ?? '1/2';
?>
<section class="bg-white rounded-apcard shadow-sm border border-slate-200 p-5">
	<div class="flex items-center justify-between mb-4">
		<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
			Fluxo de Assinaturas
		</h3>
		<span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
			<?php echo esc_html( $count_label ); ?>
		</span>
	</div>
	<div class="space-y-3">
		<?php
		foreach ( $signers as $signer ) :
			$badge = $signer['badge'] ?? [];
			?>
			<div class="flex items-center justify-between p-3 rounded-xl <?php echo esc_attr( $signer['color'] ?? 'bg-white' ); ?>">
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-xs font-bold text-white shadow-sm">
						<?php echo esc_html( $signer['avatar'] ?? '??' ); ?>
					</div>
					<div>
						<p class="text-xs font-bold text-slate-900"><?php echo esc_html( $signer['name'] ?? '' ); ?></p>
						<p class="text-[10px] text-slate-500"><?php echo esc_html( $signer['subline'] ?? '' ); ?></p>
					</div>
				</div>
				<?php if ( ! empty( $badge ) ) : ?>
					<span class="flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full shadow-sm <?php echo esc_attr( $badge['class'] ?? '' ); ?>">
						<?php echo wp_kses_post( $badge['icon'] ?? '' ); ?> <?php echo esc_html( $badge['label'] ?? '' ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
