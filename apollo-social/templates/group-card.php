<?php
/**
 * Group Card Template
 * DESIGN LIBRARY: Matches approved HTML from 'card-group-G0.html'
 * Uses uni.css classes for consistent styling
 *
 * @package Apollo_Social
 * @version 2.0.0 - Design Library Conformance
 */

?>

<article class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow"
	data-component="card-group"
	data-type="G0"
	data-group-id="<?php echo esc_attr($group['id']); ?>"
	data-ap-tooltip="<?php echo esc_attr($group['title'] ?? $group['name']); ?>">

	<div class="p-5">
		<div class="flex items-start justify-between gap-3 mb-3">
			<h3 class="text-[15px] font-bold text-slate-900 leading-tight">
				<a href="/grupo/<?php echo esc_attr($group['slug']); ?>/"
					class="hover:text-orange-600 transition-colors"
					data-ap-tooltip="Ver grupo">
					<?php echo esc_html($group['title'] ?? $group['name']); ?>
				</a>
			</h3>

			<?php
            // Include status badge.
            require 'group-status-badge.php';
?>
		</div>

		<?php if (! empty($group['description'])) : ?>
			<p class="text-[13px] text-slate-600 leading-relaxed mb-3" data-ap-tooltip="Descrição do grupo">
				<?php echo esc_html(wp_trim_words($group['description'], 25)); ?>
			</p>
		<?php endif; ?>

		<div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 mb-4">
			<span class="inline-flex items-center gap-1" data-ap-tooltip="Tipo de grupo">
				<i class="ri-community-line text-xs"></i>
				<?php echo esc_html(ucfirst($group['type'] ?? 'group')); ?>
			</span>

			<?php if (! empty($group['created_at'])) : ?>
				<span class="inline-flex items-center gap-1" data-ap-tooltip="Data de criação">
					<i class="ri-calendar-line text-xs"></i>
					Criado em <?php echo esc_html(wp_date('d/m/Y', strtotime($group['created_at']), new DateTimeZone('America/Sao_Paulo'))); ?>
				</span>
			<?php endif; ?>

			<?php if (! empty($group['workflow_meta']['submitted_at'])) : ?>
				<span class="inline-flex items-center gap-1" data-ap-tooltip="Data de envio">
					<i class="ri-send-plane-line text-xs"></i>
					Enviado <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($group['workflow_meta']['submitted_at']), new DateTimeZone('America/Sao_Paulo'))); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<div class="flex items-center gap-2 px-5 py-3 bg-slate-50 border-t border-slate-100">
		<?php if ('draft' === $status) : ?>
			<a href="/grupo/editar/<?php echo esc_attr($group['id']); ?>/"
				class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-900 text-white text-[12px] font-semibold rounded-full hover:bg-slate-800 transition-colors"
				data-ap-tooltip="Continuar editando">
				<i class="ri-edit-line text-xs"></i>
				Continuar
			</a>
			<button type="button"
				class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-500 text-white text-[12px] font-semibold rounded-full hover:bg-orange-600 transition-colors apollo-submit-btn"
				data-group-id="<?php echo esc_attr($group['id']); ?>"
				data-ap-tooltip="Enviar para revisão">
				<i class="ri-send-plane-2-line text-xs"></i>
				Enviar
			</button>
		<?php elseif ('rejected' === $status) : ?>
			<a href="/grupo/editar/<?php echo esc_attr($group['id']); ?>/"
				class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-500 text-white text-[12px] font-semibold rounded-full hover:bg-amber-600 transition-colors"
				data-ap-tooltip="Editar e reenviar">
				<i class="ri-refresh-line text-xs"></i>
				Revisar e Reenviar
			</a>
		<?php elseif ('published' === $status) : ?>
			<a href="/grupo/<?php echo esc_attr($group['slug']); ?>/"
				class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 text-white text-[12px] font-semibold rounded-full hover:bg-emerald-600 transition-colors"
				data-ap-tooltip="Ver grupo">
				<i class="ri-eye-line text-xs"></i>
				Ver Grupo
			</a>
			<a href="/grupo/editar/<?php echo esc_attr($group['id']); ?>/"
				class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-200 text-slate-700 text-[12px] font-semibold rounded-full hover:bg-slate-300 transition-colors"
				data-ap-tooltip="Editar grupo">
				<i class="ri-edit-line text-xs"></i>
				Editar
			</a>
		<?php endif; ?>
	</div>
</article>
