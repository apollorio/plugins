<?php

declare(strict_types=1);
/**
 * Document Sign Page
 * File: template-parts/documents/sign.php
 * REST: GET /documents/{id}, POST /signatures/{id}/sign
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$doc_id = get_query_var( 'doc_id' );
$doc    = apollo_get_document( $doc_id );
if ( ! $doc ) {
	echo '<div class="error-state">Documento não encontrado.</div>';
	return;
}

$user_id    = get_current_user_id();
$has_signed = apollo_user_has_signed( $doc_id, $user_id );
$signatures = $doc['signatures'];
?>

<div class="apollo-document-sign">

	<div class="doc-preview-container">
		<?php if ( $doc['file_url'] ) : ?>
		<iframe src="<?php echo esc_url( $doc['file_url'] ); ?>" class="doc-preview-frame" title="Pré-visualização do documento"></iframe>
		<?php else : ?>
		<div class="doc-content-preview"><?php echo wp_kses_post( $doc['content'] ); ?></div>
		<?php endif; ?>
	</div>

	<div class="sign-panel">
		<div class="doc-info">
			<h2><?php echo esc_html( $doc['title'] ); ?></h2>
			<p class="doc-author">Enviado por <?php echo esc_html( $doc['author']->display_name ); ?></p>
			<p class="doc-date"><?php echo date_i18n( 'd/m/Y H:i', strtotime( $doc['created'] ) ); ?></p>
		</div>

		<?php if ( $doc['requires_signature'] ) : ?>
		<div class="signatories-list">
			<h3>Signatários</h3>
			<?php
			foreach ( $doc['signatories'] as $signatory ) :
				$signed    = false;
				$sign_date = '';
				foreach ( $signatures as $sig ) {
					if ( $sig->user_id == $signatory->ID ) {
						$signed    = true;
						$sign_date = $sig->signed_at;
						break;
					}
				}
				?>
			<div class="signatory-item <?php echo $signed ? 'signed' : 'pending'; ?>">
				<img src="<?php echo apollo_get_user_avatar( $signatory->ID, 40 ); ?>" alt="Avatar de <?php echo esc_attr( $signatory->display_name ); ?>">
				<div class="signatory-info">
					<span class="name"><?php echo esc_html( $signatory->display_name ); ?></span>
					<?php if ( $signed ) : ?>
					<span class="status signed"><i class="ri-check-line"></i> Assinado <?php echo date_i18n( 'd/m/Y', strtotime( $sign_date ) ); ?></span>
					<?php else : ?>
					<span class="status pending"><i class="ri-time-line"></i> Pendente</span>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! $has_signed && $doc['requires_signature'] ) : ?>
		<div class="sign-form-container">
			<h3>Sua Assinatura</h3>
			<form id="sign-form">
				<div class="form-group">
					<label for="signature-pad">Desenhe sua assinatura</label>
					<div class="signature-pad-wrapper">
						<canvas id="signature-pad" width="400" height="150" title="Área de assinatura" aria-label="Área de assinatura"></canvas>
						<button type="button" class="btn btn-sm btn-outline" id="clear-signature" title="Limpar assinatura">Limpar</button>
					</div>
				</div>
				<div class="form-group">
					<label for="agree-doc"><input id="agree-doc" type="checkbox" name="agree" required> Li e concordo com o documento</label>
				</div>
				<button type="submit" class="btn btn-primary btn-lg btn-full" title="Assinar documento">
					<i class="ri-quill-pen-line"></i> Assinar Documento
				</button>
				<input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
				<input type="hidden" name="signature_data" id="signature-data">
				<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
			</form>
		</div>
		<?php elseif ( $has_signed ) : ?>
		<div class="signed-confirmation">
			<i class="ri-checkbox-circle-fill"></i>
			<p>Você já assinou este documento.</p>
			<a href="<?php echo home_url( '/documentos' ); ?>" class="btn btn-outline" title="Voltar para documentos">Voltar</a>
		</div>
		<?php endif; ?>
	</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const canvas = document.getElementById('signature-pad');
	if (!canvas) return;

	const ctx = canvas.getContext('2d');
	let drawing = false;

	canvas.addEventListener('mousedown', () => drawing = true);
	canvas.addEventListener('mouseup', () => { drawing = false; ctx.beginPath(); });
	canvas.addEventListener('mousemove', draw);

	function draw(e) {
		if (!drawing) return;
		ctx.lineWidth = 2;
		ctx.lineCap = 'round';
		ctx.strokeStyle = '#1e293b';
		ctx.lineTo(e.offsetX, e.offsetY);
		ctx.stroke();
		ctx.beginPath();
		ctx.moveTo(e.offsetX, e.offsetY);
	}

	document.getElementById('clear-signature').addEventListener('click', function() {
		ctx.clearRect(0, 0, canvas.width, canvas.height);
	});

	document.getElementById('sign-form').addEventListener('submit', async function(e) {
		e.preventDefault();
		document.getElementById('signature-data').value = canvas.toDataURL();

		try {
			await Apollo.documents.sign(<?php echo $doc_id; ?>, {
				signature: canvas.toDataURL()
			});
			Apollo.ui.toast('Documento assinado!', 'success');
			setTimeout(() => location.reload(), 1500);
		} catch (err) {
			Apollo.ui.toast('Erro ao assinar', 'error');
		}
	});
});
</script>
<script src="https://cdn.apollo.rio.br/"></script>
