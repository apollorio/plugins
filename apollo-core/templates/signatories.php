<?php
/**
 * Signatories List
 * File: template-parts/sign/signatories.php
 */

$doc_data     = $args['doc_data'];
$user_signed  = $args['user_signed'];
$current_user = wp_get_current_user();
$signers      = $doc_data['signers'] ?: array();
?>

<div class="mb-6">
	<h4 class="text-[13px] font-bold text-slate-900 uppercase tracking-wider mb-3">Signatários</h4>
	
	<div class="space-y-3">
		<?php
		foreach ( $signers as $signer ) :
			$is_current_user = ( $signer['user_id'] == $current_user->ID );
			$signer_user     = $is_current_user ? $current_user : get_userdata( $signer['user_id'] );
			$signer_name     = $is_current_user ? 'Você' : $signer_user->display_name;
			$signer_email    = $signer_user->user_email;
			$initials        = strtoupper( substr( $signer_user->display_name, 0, 2 ) );
			$has_signed      = ! empty( $signer['signed_at'] );

			$bg_class      = $has_signed ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-100';
			$initial_bg    = $has_signed ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-200';
			$initial_color = $has_signed ? 'text-emerald-700' : 'text-amber-700';
			?>
		<div class="flex items-center justify-between p-3 <?php echo $bg_class; ?> border rounded-lg">
			<div class="flex items-center gap-3">
				<div class="w-8 h-8 rounded-full <?php echo $initial_bg; ?> border-2 flex items-center justify-center">
					<span class="text-[12px] font-bold <?php echo $initial_color; ?>"><?php echo $initials; ?></span>
				</div>
				<div>
					<p class="text-[13px] font-semibold text-slate-900"><?php echo esc_html( $signer_name ); ?></p>
					<p class="text-[11px] text-slate-500"><?php echo esc_html( $signer_email ); ?></p>
				</div>
			</div>
			<div class="inline-flex items-center gap-1.5 px-3 py-1 <?php echo $has_signed ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'; ?> border rounded-full">
				<i class="<?php echo $has_signed ? 'ri-checkbox-circle-fill text-emerald-600' : 'ri-time-line text-amber-600'; ?> text-[14px]"></i>
				<span class="text-[10px] font-bold <?php echo $has_signed ? 'text-emerald-700' : 'text-amber-700'; ?> uppercase tracking-widest">
					<?php echo $has_signed ? 'Assinado' : 'Pendente'; ?>
				</span>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</div>
