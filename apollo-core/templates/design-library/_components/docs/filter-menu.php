<?php
/**
 * Filter chips menu for documents list.
 *
 * @var array  $filters
 * @var string $primary_cta
 */

$filters     = $filters ?? array(
	array(
		'slug'   => 'all',
		'label'  => 'Todos',
		'icon'   => 'ri-apps-2-line',
		'active' => true,
	),
	array(
		'slug'  => 'draft',
		'label' => 'Em rascunho',
		'icon'  => 'ri-edit-box-line',
	),
	array(
		'slug'  => 'waiting',
		'label' => 'Aguardando assinatura',
		'icon'  => 'ri-file-shield-2-line',
	),
	array(
		'slug'  => 'signed',
		'label' => 'Assinados',
		'icon'  => 'ri-check-double-line',
	),
);
$primary_cta = $primary_cta ?? 'Novo documento';
?>
<section class="aprioEXP-card-shell p-4">
	<div class="flex items-center justify-between mb-3">
		<div class="flex flex-col gap-1">
			<span class="text-[12px] font-semibold text-slate-700">Meus documentos</span>
			<span class="text-[11px] text-slate-500">Central de contratos, termos e checklists da cena</span>
		</div>
		<a href="/doc/new" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 text-white text-[12px] font-medium hover:bg-slate-800">
			<i class="ri-add-line text-sm"></i>
			<?php echo esc_html( $primary_cta ); ?>
		</a>
	</div>

	<div class="flex flex-wrap gap-2 mb-4">
		<?php
		foreach ( $filters as $filter ) :
			$classes = 'menutag' . ( ! empty( $filter['active'] ) ? '" data-active="true' : '' );
			?>
			<button class="menutag<?php echo ! empty( $filter['active'] ) ? '" data-active="true' : ''; ?>" data-filter="<?php echo esc_attr( $filter['slug'] ); ?>">
				<i class="<?php echo esc_attr( $filter['icon'] ?? 'ri-checkbox-blank-circle-line' ); ?> text-xs"></i>
				<?php echo esc_html( $filter['label'] ?? '' ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="space-y-1.5 text-[12px]" id="documents-list">
		<?php if ( ! empty( $documents ) ) : ?>
			<?php foreach ( $documents as $document ) : ?>
				<?php apollo_core_component( 'docs/document-row', array( 'document' => $document ) ); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="text-center text-slate-400 py-4">Sem documentos cadastrados.</p>
		<?php endif; ?>
	</div>
</section>
