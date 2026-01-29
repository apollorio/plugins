<?php
/**
 * Group Status Badge Component
 * DESIGN LIBRARY: Matches approved HTML from 'status-badge.html'
 * Uses uni.css classes for consistent styling
 *
 * @package Apollo_Social
 * @version 2.0.0 - Design Library Conformance
 */

$status_configs = array(
	'draft'          => array(
		'bg'      => 'bg-slate-100',
		'text'    => 'text-slate-600',
		'icon'    => 'ri-edit-box-line',
		'label'   => 'Rascunho',
		'tooltip' => __( 'Grupo ainda não publicado', 'apollo-social' ),
	),
	'pending'        => array(
		'bg'      => 'bg-amber-50',
		'text'    => 'text-amber-700',
		'icon'    => 'ri-time-line',
		'label'   => 'Aguardando',
		'tooltip' => __( 'Aguardando aprovação da moderação', 'apollo-social' ),
	),
	'pending_review' => array(
		'bg'      => 'bg-sky-50',
		'text'    => 'text-sky-700',
		'icon'    => 'ri-search-eye-line',
		'label'   => 'Em Análise',
		'tooltip' => __( 'Grupo em análise pela equipe', 'apollo-social' ),
	),
	'published'      => array(
		'bg'      => 'bg-emerald-50',
		'text'    => 'text-emerald-700',
		'icon'    => 'ri-checkbox-circle-line',
		'label'   => 'Publicado',
		'tooltip' => __( 'Grupo ativo e visível', 'apollo-social' ),
	),
	'rejected'       => array(
		'bg'      => 'bg-red-50',
		'text'    => 'text-red-700',
		'icon'    => 'ri-close-circle-line',
		'label'   => 'Rejeitado',
		'tooltip' => __( 'Grupo rejeitado pela moderação', 'apollo-social' ),
	),
);

$config = $status_configs[ $status ] ?? array(
	'bg'      => 'bg-slate-100',
	'text'    => 'text-slate-600',
	'icon'    => 'ri-question-line',
	'label'   => 'Desconhecido',
	'tooltip' => __( 'Status desconhecido', 'apollo-social' ),
);
?>

<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold <?php echo esc_attr( $config['bg'] . ' ' . $config['text'] ); ?>"
	data-component="status-badge"
	data-status="<?php echo esc_attr( $status ); ?>"
	data-ap-tooltip="<?php echo esc_attr( $config['tooltip'] ); ?>">
	<i class="<?php echo esc_attr( $config['icon'] ); ?> text-[11px]"></i>
	<?php echo esc_html( $config['label'] ); ?>
</span>

<?php if ( $status === 'rejected' && ! empty( $rejection_notice ) ) : ?>
<div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-xl text-[12px]" data-ap-tooltip="<?php esc_attr_e( 'Motivo da rejeição', 'apollo-social' ); ?>">
	<div class="text-red-800 leading-relaxed">
		<?php
		echo wp_kses(
			$rejection_notice['message'],
			array(
				'br'     => array(),
				'span'   => array( 'class' => true ),
				'strong' => array(),
			)
		);
		?>
	</div>
	<?php if ( ! empty( $rejection_notice['can_resubmit'] ) ) : ?>
	<button type="button"
		class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-[11px] font-semibold rounded-full hover:bg-red-700 transition-colors"
		data-group-id="<?php echo esc_attr( $group_id ?? '' ); ?>"
		data-ap-tooltip="<?php esc_attr_e( 'Revisar e reenviar para moderação', 'apollo-social' ); ?>">
		<i class="ri-refresh-line text-xs"></i>
		Revisar e Reenviar
	</button>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php if ( $status === 'pending' || $status === 'pending_review' ) : ?>
<p class="mt-1 text-[11px] text-slate-500 italic" data-ap-tooltip="<?php esc_attr_e( 'Informação sobre moderação', 'apollo-social' ); ?>">
	<?php if ( $status === 'pending' ) : ?>
		Seu grupo está na fila de moderação.
	<?php else : ?>
		Seu grupo está sendo revisado pela equipe Apollo.
	<?php endif; ?>
</p>
<?php endif; ?>
