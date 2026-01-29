<?php
/**
 * Template Part: Document Signature Panel
 * File: template-parts/doc-signature-panel.php
 */

global $post;
$current_user  = wp_get_current_user();
$signers       = get_post_meta( $post->ID, 'document_signers', true ) ?: array();
$user_signed   = apollo_check_user_signed( $post->ID, $current_user->ID );
$total_signers = count( $signers );
$signed_count  = apollo_count_signed( $post->ID );
?>

<div class="w-full lg:w-[380px] shrink-0 flex flex-col gap-4">
	<!-- Signers Status Card -->
	<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
		<div class="flex items-center justify-between mb-4">
			<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Fluxo de Assinaturas</h3>
			<span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
				<span id="sign-count-label"><?php echo $signed_count; ?>/<?php echo $total_signers; ?></span>
			</span>
		</div>

		<div class="space-y-3">
			<!-- Current User -->
			<?php
			$user_initial = strtoupper( substr( $current_user->display_name, 0, 2 ) );
			$user_cpf     = get_user_meta( $current_user->ID, 'cpf', true );
			$masked_cpf   = $user_cpf ? apollo_mask_cpf( $user_cpf ) : '***.***.***-**';
			?>
			<div id="signer-you-card" class="flex items-center justify-between p-3 rounded-xl <?php echo $user_signed ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-100'; ?> border">
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-xs font-bold text-white shadow-sm">
						<?php echo $user_initial; ?>
					</div>
					<div>
						<p class="text-xs font-bold text-slate-900">Você</p>
						<p class="text-[10px] text-slate-500"><?php echo $masked_cpf; ?></p>
					</div>
				</div>
				<span id="signer-you-status" class="flex items-center gap-1 text-[10px] font-bold <?php echo $user_signed ? 'text-emerald-700 bg-white' : 'text-amber-600 bg-white'; ?> px-2 py-1 rounded-full shadow-sm">
					<?php if ( $user_signed ) : ?>
						<i class="ri-check-line"></i> Assinado
					<?php else : ?>
						<span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pendente
					<?php endif; ?>
				</span>
			</div>

			<!-- Other Signers -->
			<?php
			foreach ( $signers as $signer ) :
				if ( $signer['user_id'] == $current_user->ID ) {
					continue;
				}
				$signer_user    = get_userdata( $signer['user_id'] );
				$signer_initial = $signer_user ? strtoupper( substr( $signer_user->display_name, 0, 2 ) ) : 'PT';
				$signer_name    = $signer_user ? $signer_user->display_name : $signer['name'];
				$is_signed      = ! empty( $signer['signed_at'] );
				?>
			<div class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-100">
				<div class="flex items-center gap-3">
					<div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500">
						<?php echo $signer_initial; ?>
					</div>
					<div>
						<p class="text-xs font-bold text-slate-700"><?php echo esc_html( $signer_name ); ?></p>
						<p class="text-[10px] text-slate-400"><?php echo $signer['type'] === 'external' ? 'Externo' : 'Apollo'; ?></p>
					</div>
				</div>
				<span class="flex items-center gap-1 text-[10px] font-bold <?php echo $is_signed ? 'text-emerald-600 bg-emerald-50' : 'text-slate-400 bg-slate-50'; ?> px-2 py-1 rounded-full">
					<?php if ( $is_signed ) : ?>
						<i class="ri-check-line"></i> Assinado
					<?php else : ?>
						<i class="ri-time-line"></i> Aguardando
					<?php endif; ?>
				</span>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Signature Action Card -->
	<?php if ( ! $user_signed ) : ?>
	<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 flex-1 flex flex-col">
		<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Realizar Assinatura</h3>

		<!-- Progress Stepper -->
		<div class="flex items-center gap-2 mb-6">
			<div data-step="1" class="flex-1 h-1 rounded-full bg-slate-900 transition-colors"></div>
			<div data-step="2" class="flex-1 h-1 rounded-full bg-slate-200 transition-colors"></div>
			<div data-step="3" class="flex-1 h-1 rounded-full bg-slate-200 transition-colors"></div>
		</div>

		<div class="space-y-4 flex-1">
			<!-- Terms Checkboxes -->
			<div class="space-y-3">
				<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer group">
					<input id="chk-terms" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
					<span class="text-xs text-slate-600 group-hover:text-slate-900">
						Li e concordo com o conteúdo do documento.
					</span>
				</label>
				<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer group">
					<input id="chk-rep" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
					<span class="text-xs text-slate-600 group-hover:text-slate-900">
						Autorizo o uso da minha assinatura digital.
					</span>
				</label>
				<p id="sign-error" class="hidden text-[11px] text-red-500 font-medium px-1 flex items-center gap-1">
					<i class="ri-error-warning-line"></i> Marque as opções acima para continuar.
				</p>
			</div>

			<!-- Action Buttons -->
			<div class="space-y-3 pt-2">
				<button id="btn-sign-govbr" data-provider="govbr" class="w-full flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-lg shadow-slate-900/10 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
					<i class="ri-shield-check-line text-lg"></i>
					Assinar com gov.br
				</button>

				<button id="btn-sign-icp" data-provider="icp" class="w-full flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold py-3 rounded-xl active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
					<i class="ri-key-2-line text-lg"></i>
					Certificado ICP-Brasil
				</button>
			</div>
		</div>

		<!-- Result Block -->
		<div id="sign-result" class="hidden mt-4 p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
			<div class="flex items-center gap-2 mb-2 text-emerald-800 font-bold text-sm">
				<i class="ri-checkbox-circle-fill text-lg"></i>
				Assinado com Sucesso
			</div>
			<div class="text-[10px] text-emerald-700 space-y-1 font-mono">
				<p id="signed-at">Data: --</p>
				<p id="signed-code">Cod: --</p>
				<p id="signed-hash" class="truncate">Hash: --</p>
			</div>
		</div>

		<div class="mt-4 text-[10px] text-slate-400 text-center">
			Ambiente seguro Apollo::rio · disponível para toda a comunidade
		</div>
	</section>
	<?php else : ?>
	<!-- Already Signed -->
	<section class="bg-emerald-50 rounded-3xl shadow-sm border border-emerald-200 p-5">
		<div class="flex items-center gap-2 mb-3 text-emerald-800 font-bold text-sm">
			<i class="ri-checkbox-circle-fill text-lg"></i>
			Documento Assinado
		</div>
		<?php
		$signature = apollo_get_user_signature( $post->ID, $current_user->ID );
		if ( $signature ) :
			?>
		<div class="text-[10px] text-emerald-700 space-y-1 font-mono">
			<p>Data: <?php echo date( 'd/m/Y H:i', strtotime( $signature['signed_at'] ) ); ?></p>
			<p>Cod: <?php echo esc_html( $signature['code'] ); ?></p>
			<p class="truncate">Hash: <?php echo esc_html( $signature['hash'] ); ?></p>
		</div>
		<?php endif; ?>
	</section>
	<?php endif; ?>
</div>
