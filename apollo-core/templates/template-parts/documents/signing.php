<?php

declare(strict_types=1);
/**
 * Apollo Document Signing Template
 *
 * Document signature request page for visitors
 * Based on: visitor-sign-doc-page-and-email.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Get document data from URL token
$sign_token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
$document   = null;
$signer     = null;
$is_signed  = false;
$is_expired = false;
$error      = '';

if (empty($sign_token)) {
	$error = 'Token de assinatura não encontrado.';
} else {
	// Get document by token
	$doc_query = new WP_Query(array(
		'post_type'      => 'apollo_document',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'   => '_sign_token',
				'value' => $sign_token,
			),
		),
	));

	if ($doc_query->have_posts()) {
		$doc_query->the_post();
		$document = get_post();
		wp_reset_postdata();

		// Get signer info from token
		$signers = get_post_meta($document->ID, '_document_signers', true);
		if (is_array($signers)) {
			foreach ($signers as $s) {
				if (isset($s['token']) && $s['token'] === $sign_token) {
					$signer = $s;
					break;
				}
			}
		}

		if (! $signer) {
			$error = 'Assinatura não encontrada.';
		} else {
			$is_signed = ! empty($signer['signed_at']);

			// Check expiration
			$expires = get_post_meta($document->ID, '_sign_expires', true);
			if ($expires && strtotime($expires) < time() && ! $is_signed) {
				$is_expired = true;
			}
		}
	} else {
		$error = 'Documento não encontrado.';
	}
}

// Document meta
$doc_title      = $document ? $document->post_title : '';
$doc_content    = $document ? $document->post_content : '';
$doc_type       = $document ? get_post_meta($document->ID, '_document_type', true) : '';
$sender_id      = $document ? $document->post_author : 0;
$sender         = $sender_id ? get_userdata($sender_id) : null;
$sender_name    = $sender ? $sender->display_name : 'Apollo';
$sender_avatar  = $sender_id ? get_avatar_url($sender_id, array('size' => 48)) : '';
$created_date   = $document ? get_the_date('d/m/Y', $document) : '';

// Signer info
$signer_name   = $signer ? $signer['name'] : '';
$signer_email  = $signer ? $signer['email'] : '';
$signed_at     = $signer && isset($signer['signed_at']) ? $signer['signed_at'] : '';
$signer_ip     = $signer && isset($signer['ip']) ? $signer['ip'] : '';

// Document type labels
$type_labels = array(
	'general'   => 'Documento',
	'contract'  => 'Contrato',
	'rider'     => 'Rider Técnico',
	'invoice'   => 'Nota/Fatura',
	'proposal'  => 'Proposta',
	'terms'     => 'Termos e Condições',
);
$type_label = isset($type_labels[$doc_type]) ? $type_labels[$doc_type] : 'Documento';

?>
<div class="apollo-doc-signing">

	<!-- Header -->
	<header class="signing-header">
		<div class="header-content">
			<a href="<?php echo esc_url(home_url()); ?>" class="logo">
				<img src="https://assets.apollo.rio.br/img/Apollo-Logo.png" alt="Apollo" height="32">
			</a>
			<span class="header-badge">
				<i class="i-file-shield-v" aria-hidden="true"></i>
				Assinatura Digital
			</span>
		</div>
	</header>

	<main class="signing-main">

		<?php if ($error) : ?>
			<!-- Error State -->
			<div class="signing-card error-card">
				<div class="error-icon">
					<i class="i-error-warning-v" aria-hidden="true"></i>
				</div>
				<h1>Documento não disponível</h1>
				<p><?php echo esc_html($error); ?></p>
				<a href="<?php echo esc_url(home_url()); ?>" class="btn-primary">
					Ir para Apollo
				</a>
			</div>

		<?php elseif ($is_expired) : ?>
			<!-- Expired State -->
			<div class="signing-card expired-card">
				<div class="expired-icon">
					<i class="i-time-v" aria-hidden="true"></i>
				</div>
				<h1>Link expirado</h1>
				<p>O prazo para assinatura deste documento expirou. Entre em contato com o remetente para solicitar um novo link.</p>
				<div class="document-info">
					<span class="label">Documento:</span>
					<span class="value"><?php echo esc_html($doc_title); ?></span>
				</div>
				<div class="document-info">
					<span class="label">Remetente:</span>
					<span class="value"><?php echo esc_html($sender_name); ?></span>
				</div>
			</div>

		<?php elseif ($is_signed) : ?>
			<!-- Already Signed State -->
			<div class="signing-card success-card">
				<div class="success-icon">
					<i class="i-checkbox-circle-v" aria-hidden="true"></i>
				</div>
				<h1>Documento assinado!</h1>
				<p>Este documento já foi assinado com sucesso.</p>

				<div class="signature-details">
					<div class="detail-row">
						<span class="label">Assinado por:</span>
						<span class="value"><?php echo esc_html($signer_name); ?></span>
					</div>
					<div class="detail-row">
						<span class="label">E-mail:</span>
						<span class="value"><?php echo esc_html($signer_email); ?></span>
					</div>
					<div class="detail-row">
						<span class="label">Data:</span>
						<span class="value"><?php echo esc_html(date_i18n('d/m/Y \à\s H:i', strtotime($signed_at))); ?></span>
					</div>
					<?php if ($signer_ip) : ?>
						<div class="detail-row">
							<span class="label">IP:</span>
							<span class="value"><?php echo esc_html($signer_ip); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="document-preview-mini">
					<div class="doc-header">
						<span class="doc-type"><?php echo esc_html($type_label); ?></span>
						<span class="doc-title"><?php echo esc_html($doc_title); ?></span>
					</div>
					<a href="#" class="btn-download" title="Baixar cópia assinada">
						<i class="i-download-v" aria-hidden="true"></i>
						Baixar cópia assinada
					</a>
				</div>
			</div>

		<?php else : ?>
			<!-- Signing Form -->
			<div class="signing-container">

				<!-- Document Preview -->
				<div class="document-preview" id="document-preview">
					<div class="preview-header">
						<div class="doc-meta">
							<span class="doc-type-badge"><?php echo esc_html($type_label); ?></span>
							<h1 class="doc-title"><?php echo esc_html($doc_title); ?></h1>
							<div class="doc-info">
								<span>
									<i class="i-user-v" aria-hidden="true"></i>
									<?php echo esc_html($sender_name); ?>
								</span>
								<span>
									<i class="i-calendar-v" aria-hidden="true"></i>
									<?php echo esc_html($created_date); ?>
								</span>
							</div>
						</div>
					</div>

					<div class="preview-content">
						<?php echo wp_kses_post($doc_content); ?>
					</div>

					<div class="preview-footer">
						<p class="scroll-hint" id="scroll-hint">
							<i class="i-arrow-down-v" aria-hidden="true"></i>
							Role para baixo para ver todo o documento
						</p>
					</div>
				</div>

				<!-- Signature Form -->
				<div class="signature-form" id="signature-form">
					<div class="form-header">
						<h2>Assinar Documento</h2>
						<p>Você foi convidado(a) por <strong><?php echo esc_html($sender_name); ?></strong> para assinar este documento.</p>
					</div>

					<form id="sign-form" method="post">
						<input type="hidden" name="action" value="apollo_sign_document">
						<input type="hidden" name="token" value="<?php echo esc_attr($sign_token); ?>">
						<?php wp_nonce_field('apollo_sign_document', 'sign_nonce'); ?>

						<!-- Signer Identification -->
						<div class="form-section">
							<h3>
								<i class="i-user-v" aria-hidden="true"></i>
								Identificação
							</h3>

							<div class="form-group">
								<label for="signer-name">Nome completo *</label>
								<input
									type="text"
									id="signer-name"
									name="signer_name"
									value="<?php echo esc_attr($signer_name); ?>"
									title="Nome completo"
									required
									placeholder="Digite seu nome completo">
							</div>

							<div class="form-group">
								<label for="signer-email">E-mail *</label>
								<input
									type="email"
									id="signer-email"
									name="signer_email"
									value="<?php echo esc_attr($signer_email); ?>"
									title="E-mail"
									required
									readonly
									placeholder="seu@email.com">
								<small>E-mail pré-definido pelo remetente</small>
							</div>

							<div class="form-group">
								<label for="signer-cpf">CPF (opcional)</label>
								<input
									type="text"
									id="signer-cpf"
									name="signer_cpf"
									title="CPF (opcional)"
									placeholder="000.000.000-00"
									maxlength="14">
							</div>
						</div>

						<!-- Signature Pad -->
						<div class="form-section">
							<h3>
								<i class="i-edit-v" aria-hidden="true"></i>
								Assinatura
							</h3>

							<div class="signature-tabs">
								<button type="button" class="sig-tab active" data-tab="draw" title="Desenhar assinatura">
									<i class="i-brush-v" aria-hidden="true"></i>
									Desenhar
								</button>
								<button type="button" class="sig-tab" data-tab="type" title="Digitar assinatura">
									<i class="i-text-v" aria-hidden="true"></i>
									Digitar
								</button>
								<button type="button" class="sig-tab" data-tab="upload" title="Carregar assinatura">
									<i class="i-upload-v" aria-hidden="true"></i>
									Carregar
								</button>
							</div>

							<!-- Draw Signature -->
							<div class="sig-panel active" id="panel-draw">
								<canvas id="signature-canvas" width="400" height="150" title="Área de assinatura" aria-label="Área de assinatura"></canvas>
								<button type="button" class="clear-sig" id="clear-canvas" title="Limpar assinatura">
									<i class="i-eraser-v" aria-hidden="true"></i>
									Limpar
								</button>
							</div>

							<!-- Type Signature -->
							<div class="sig-panel" id="panel-type">
								<label for="typed-signature" class="sr-only">Digite sua assinatura</label>
								<input
									type="text"
									id="typed-signature"
									class="typed-sig-input"
									placeholder="Digite seu nome"
									title="Assinatura digitada">
								<div class="signature-fonts">
									<label class="font-option" for="sig-font-cursive">
										<input id="sig-font-cursive" type="radio" name="sig_font" value="cursive" title="Fonte cursiva" checked>
										<span style="font-family: 'Brush Script MT', cursive;">Assinatura</span>
									</label>
									<label class="font-option" for="sig-font-script">
										<input id="sig-font-script" type="radio" name="sig_font" value="script" title="Fonte script">
										<span style="font-family: 'Lucida Handwriting', cursive;">Assinatura</span>
									</label>
									<label class="font-option" for="sig-font-serif">
										<input id="sig-font-serif" type="radio" name="sig_font" value="serif" title="Fonte serif">
										<span style="font-family: 'Times New Roman', serif; font-style: italic;">Assinatura</span>
									</label>
								</div>
								<div class="typed-preview" id="typed-preview"></div>
							</div>

							<!-- Upload Signature -->
							<div class="sig-panel" id="panel-upload">
								<div class="upload-zone" id="upload-zone" title="Carregar assinatura">
									<i class="i-upload-cloud-v" aria-hidden="true"></i>
									<p>Arraste uma imagem ou clique para selecionar</p>
									<small>PNG ou JPG, fundo transparente recomendado</small>
									<label for="sig-upload" class="sr-only">Carregar assinatura</label>
									<input type="file" id="sig-upload" accept="image/png, image/jpeg" title="Selecionar arquivo de assinatura" hidden>
								</div>
								<div class="upload-preview" id="upload-preview" hidden>
									<img id="uploaded-sig" src="" alt="Assinatura">
									<button type="button" class="remove-upload" id="remove-upload" title="Remover assinatura enviada">
										<i class="i-close-v" aria-hidden="true"></i>
									</button>
								</div>
							</div>

							<input type="hidden" name="signature_data" id="signature-data">
						</div>

						<!-- Agreement -->
						<div class="form-section">
							<label class="agreement-checkbox" for="agree-terms">
								<input type="checkbox" id="agree-terms" name="agree_terms" title="Concordo com os termos" required>
								<span>
									Li e concordo com os termos do documento acima e confirmo que esta assinatura eletrônica tem validade jurídica conforme a <a href="https://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/l14063.htm" target="_blank" rel="noopener">Lei 14.063/2020</a>.
								</span>
							</label>
						</div>

						<!-- Submit -->
						<div class="form-actions">
							<button type="submit" class="btn-sign" id="btn-sign" title="Assinar documento" disabled>
								<i class="i-check-v" aria-hidden="true"></i>
								Assinar Documento
							</button>
						</div>

						<p class="security-note">
							<i class="i-shield-check-v" aria-hidden="true"></i>
							Sua assinatura será registrada com data, hora, IP e hash de verificação.
						</p>
					</form>
				</div>

			</div>
		<?php endif; ?>

	</main>

	<!-- Footer -->
	<footer class="signing-footer">
		<div class="footer-content">
			<p>&copy; <?php echo date('Y'); ?> Apollo. Assinatura digital segura.</p>
			<div class="footer-links">
				<a href="#">Política de Privacidade</a>
				<a href="#">Termos de Uso</a>
				<a href="#">Suporte</a>
			</div>
		</div>
	</footer>

</div>

<style>
	.sr-only {
		position: absolute !important;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}
	/* Apollo Document Signing Styles */
	.apollo-doc-signing {
		min-height: 100vh;
		display: flex;
		flex-direction: column;
		background: var(--ap-bg-page);
	}

	/* Header */
	.signing-header {
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		padding: 1rem 2rem;
	}

	.header-content {
		max-width: 1200px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.logo img {
		height: 32px;
	}

	.header-badge {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.8rem;
		font-weight: 600;
		color: #f97316;
		background: rgba(249, 115, 22, 0.1);
		padding: 0.5rem 1rem;
		border-radius: 999px;
	}

	/* Main */
	.signing-main {
		flex: 1;
		padding: 2rem;
	}

	/* Status Cards */
	.signing-card {
		max-width: 500px;
		margin: 2rem auto;
		padding: 2.5rem;
		background: #fff;
		border-radius: 1.5rem;
		text-align: center;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
	}

	.error-icon,
	.expired-icon,
	.success-icon {
		width: 80px;
		height: 80px;
		margin: 0 auto 1.5rem;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 2.5rem;
	}

	.error-icon {
		background: rgba(220, 38, 38, 0.1);
		color: #dc2626;
	}

	.expired-icon {
		background: rgba(217, 119, 6, 0.1);
		color: #d97706;
	}

	.success-icon {
		background: rgba(16, 185, 129, 0.1);
		color: #10b981;
	}

	.signing-card h1 {
		font-size: 1.5rem;
		margin: 0 0 0.75rem;
	}

	.signing-card p {
		color: var(--ap-text-muted);
		margin: 0 0 1.5rem;
	}

	.btn-primary {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.75rem 1.5rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border-radius: 999px;
		font-weight: 600;
		text-decoration: none;
		transition: all 0.2s;
	}

	.btn-primary:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	.document-info {
		display: flex;
		justify-content: space-between;
		padding: 0.75rem 0;
		border-bottom: 1px solid var(--ap-border-default);
		font-size: 0.9rem;
	}

	.document-info:last-child {
		border: none;
	}

	.document-info .label {
		color: var(--ap-text-muted);
	}

	.signature-details {
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		padding: 1rem;
		margin: 1.5rem 0;
		text-align: left;
	}

	.detail-row {
		display: flex;
		justify-content: space-between;
		padding: 0.5rem 0;
		font-size: 0.85rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.detail-row:last-child {
		border: none;
	}

	.detail-row .label {
		color: var(--ap-text-muted);
	}

	.detail-row .value {
		font-weight: 600;
	}

	.document-preview-mini {
		background: var(--ap-bg-surface);
		border-radius: 0.75rem;
		padding: 1rem;
		margin-top: 1.5rem;
	}

	.doc-header {
		text-align: left;
		margin-bottom: 1rem;
	}

	.doc-type {
		font-size: 0.7rem;
		color: #f97316;
		text-transform: uppercase;
		font-weight: 700;
	}

	.doc-title {
		font-weight: 600;
		font-size: 0.95rem;
	}

	.btn-download {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		width: 100%;
		padding: 0.75rem;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.85rem;
		color: var(--ap-text-default);
		text-decoration: none;
		transition: all 0.2s;
	}

	.btn-download:hover {
		border-color: #f97316;
		color: #f97316;
	}

	/* Signing Container */
	.signing-container {
		max-width: 1200px;
		margin: 0 auto;
		display: grid;
		grid-template-columns: 1fr;
		gap: 2rem;
	}

	@media (min-width: 992px) {
		.signing-container {
			grid-template-columns: 1fr 400px;
		}
	}

	/* Document Preview */
	.document-preview {
		background: #fff;
		border-radius: 1rem;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
		display: flex;
		flex-direction: column;
		max-height: 80vh;
		overflow: hidden;
	}

	.preview-header {
		padding: 1.5rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.doc-type-badge {
		display: inline-block;
		font-size: 0.7rem;
		color: #f97316;
		background: rgba(249, 115, 22, 0.1);
		padding: 0.25rem 0.75rem;
		border-radius: 999px;
		text-transform: uppercase;
		font-weight: 700;
		margin-bottom: 0.5rem;
	}

	.preview-header .doc-title {
		font-size: 1.35rem;
		font-weight: 700;
		margin: 0 0 0.75rem;
	}

	.doc-info {
		display: flex;
		gap: 1.5rem;
		font-size: 0.85rem;
		color: var(--ap-text-muted);
	}

	.doc-info span {
		display: flex;
		align-items: center;
		gap: 0.35rem;
	}

	.preview-content {
		flex: 1;
		padding: 2rem;
		overflow-y: auto;
		font-size: 0.95rem;
		line-height: 1.7;
	}

	.preview-content h1,
	.preview-content h2,
	.preview-content h3 {
		margin-top: 1.5rem;
		margin-bottom: 0.75rem;
	}

	.preview-content p {
		margin: 0 0 1rem;
	}

	.preview-content ul,
	.preview-content ol {
		margin: 0 0 1rem;
		padding-left: 1.5rem;
	}

	.preview-footer {
		padding: 1rem 1.5rem;
		border-top: 1px solid var(--ap-border-default);
		text-align: center;
	}

	.scroll-hint {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		animation: bounce 2s infinite;
	}

	.scroll-hint.hidden {
		opacity: 0;
	}

	@keyframes bounce {

		0%,
		100% {
			transform: translateY(0);
		}

		50% {
			transform: translateY(5px);
		}
	}

	/* Signature Form */
	.signature-form {
		background: #fff;
		border-radius: 1rem;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
		padding: 1.5rem;
		height: fit-content;
		position: sticky;
		top: 2rem;
	}

	.form-header {
		margin-bottom: 1.5rem;
	}

	.form-header h2 {
		font-size: 1.25rem;
		margin: 0 0 0.5rem;
	}

	.form-header p {
		font-size: 0.85rem;
		color: var(--ap-text-muted);
		margin: 0;
	}

	.form-section {
		margin-bottom: 1.5rem;
	}

	.form-section h3 {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.9rem;
		font-weight: 700;
		margin: 0 0 1rem;
		color: var(--ap-text-default);
	}

	.form-section h3 i {
		color: #f97316;
	}

	.form-group {
		margin-bottom: 1rem;
	}

	.form-group label {
		display: block;
		font-size: 0.8rem;
		font-weight: 600;
		margin-bottom: 0.35rem;
	}

	.form-group input {
		width: 100%;
		padding: 0.65rem 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.9rem;
	}

	.form-group input:focus {
		outline: none;
		border-color: #f97316;
	}

	.form-group input[readonly] {
		background: var(--ap-bg-surface);
		cursor: not-allowed;
	}

	.form-group small {
		display: block;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
		margin-top: 0.35rem;
	}

	/* Signature Tabs */
	.signature-tabs {
		display: flex;
		gap: 0.5rem;
		margin-bottom: 1rem;
	}

	.sig-tab {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.35rem;
		padding: 0.65rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		background: #fff;
		font-size: 0.8rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.sig-tab:hover {
		border-color: #f97316;
	}

	.sig-tab.active {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.sig-panel {
		display: none;
	}

	.sig-panel.active {
		display: block;
	}

	/* Draw Signature */
	#signature-canvas {
		width: 100%;
		height: 150px;
		border: 2px dashed var(--ap-border-default);
		border-radius: 0.5rem;
		cursor: crosshair;
		touch-action: none;
	}

	.clear-sig {
		display: flex;
		align-items: center;
		gap: 0.35rem;
		margin-top: 0.5rem;
		padding: 0.35rem 0.75rem;
		background: transparent;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.35rem;
		font-size: 0.75rem;
		cursor: pointer;
	}

	.clear-sig:hover {
		background: var(--ap-bg-surface);
	}

	/* Type Signature */
	.typed-sig-input {
		width: 100%;
		padding: 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 1rem;
		margin-bottom: 0.75rem;
	}

	.signature-fonts {
		display: flex;
		gap: 0.5rem;
		margin-bottom: 0.75rem;
	}

	.font-option {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.5rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		cursor: pointer;
		font-size: 0.9rem;
		transition: all 0.2s;
	}

	.font-option:has(input:checked) {
		border-color: #f97316;
		background: rgba(249, 115, 22, 0.05);
	}

	.font-option input {
		display: none;
	}

	.typed-preview {
		min-height: 60px;
		padding: 1rem;
		background: var(--ap-bg-surface);
		border-radius: 0.5rem;
		font-size: 1.5rem;
		text-align: center;
	}

	/* Upload Signature */
	.upload-zone {
		border: 2px dashed var(--ap-border-default);
		border-radius: 0.5rem;
		padding: 2rem;
		text-align: center;
		cursor: pointer;
		transition: all 0.2s;
	}

	.upload-zone:hover {
		border-color: #f97316;
		background: rgba(249, 115, 22, 0.02);
	}

	.upload-zone i {
		font-size: 2rem;
		color: var(--ap-text-muted);
		margin-bottom: 0.5rem;
	}

	.upload-zone p {
		margin: 0 0 0.25rem;
		font-size: 0.9rem;
	}

	.upload-zone small {
		color: var(--ap-text-muted);
		font-size: 0.75rem;
	}

	.upload-preview {
		position: relative;
		padding: 1rem;
		background: var(--ap-bg-surface);
		border-radius: 0.5rem;
		text-align: center;
	}

	.upload-preview img {
		max-width: 100%;
		max-height: 100px;
	}

	.remove-upload {
		position: absolute;
		top: 0.5rem;
		right: 0.5rem;
		width: 24px;
		height: 24px;
		border-radius: 50%;
		background: #dc2626;
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.75rem;
	}

	/* Agreement */
	.agreement-checkbox {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		font-size: 0.8rem;
		line-height: 1.5;
		cursor: pointer;
	}

	.agreement-checkbox input {
		width: 18px;
		height: 18px;
		flex-shrink: 0;
		margin-top: 0.1rem;
		accent-color: #f97316;
	}

	.agreement-checkbox a {
		color: #f97316;
		text-decoration: underline;
	}

	/* Form Actions */
	.form-actions {
		margin-top: 1.5rem;
	}

	.btn-sign {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 1rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 999px;
		font-size: 1rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-sign:hover:not(:disabled) {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	.btn-sign:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.security-note {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		margin-top: 1rem;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	.security-note i {
		color: #10b981;
	}

	/* Footer */
	.signing-footer {
		background: #fff;
		border-top: 1px solid var(--ap-border-default);
		padding: 1.5rem 2rem;
	}

	.footer-content {
		max-width: 1200px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
	}

	.footer-content p {
		margin: 0;
	}

	.footer-links {
		display: flex;
		gap: 1.5rem;
	}

	.footer-links a {
		color: var(--ap-text-muted);
		text-decoration: none;
	}

	.footer-links a:hover {
		color: #f97316;
	}

	/* Dark Mode */
	body.dark-mode .signing-header,
	body.dark-mode .signing-card,
	body.dark-mode .document-preview,
	body.dark-mode .signature-form,
	body.dark-mode .signing-footer {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .form-group input,
	body.dark-mode .typed-sig-input {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
		color: var(--ap-text-default);
	}

	body.dark-mode #signature-canvas,
	body.dark-mode .upload-zone {
		border-color: var(--ap-border-default);
	}

	/* Responsive */
	@media (max-width: 640px) {

		.signing-header,
		.signing-main,
		.signing-footer {
			padding: 1rem;
		}

		.signing-card {
			padding: 1.5rem;
			margin: 1rem auto;
		}

		.footer-content {
			flex-direction: column;
			gap: 1rem;
			text-align: center;
		}

		.signature-tabs {
			flex-wrap: wrap;
		}

		.sig-tab {
			flex-basis: calc(50% - 0.25rem);
		}

		.sig-tab:last-child {
			flex-basis: 100%;
		}
	}
</style>

<script>
	(function() {
		const form = document.getElementById('sign-form');
		if (!form) return;

		const canvas = document.getElementById('signature-canvas');
		const ctx = canvas ? canvas.getContext('2d') : null;
		const signatureData = document.getElementById('signature-data');
		const btnSign = document.getElementById('btn-sign');
		const agreeTerms = document.getElementById('agree-terms');
		const scrollHint = document.getElementById('scroll-hint');
		const previewContent = document.querySelector('.preview-content');

		// Signature tabs
		document.querySelectorAll('.sig-tab').forEach(tab => {
			tab.addEventListener('click', () => {
				document.querySelectorAll('.sig-tab').forEach(t => t.classList.remove('active'));
				document.querySelectorAll('.sig-panel').forEach(p => p.classList.remove('active'));
				tab.classList.add('active');
				document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
				updateSignatureData();
			});
		});

		// Drawing on canvas
		if (canvas && ctx) {
			let isDrawing = false;
			let lastX = 0;
			let lastY = 0;

			ctx.strokeStyle = '#000';
			ctx.lineWidth = 2;
			ctx.lineCap = 'round';
			ctx.lineJoin = 'round';

			function getPos(e) {
				const rect = canvas.getBoundingClientRect();
				const scaleX = canvas.width / rect.width;
				const scaleY = canvas.height / rect.height;
				if (e.touches) {
					return {
						x: (e.touches[0].clientX - rect.left) * scaleX,
						y: (e.touches[0].clientY - rect.top) * scaleY
					};
				}
				return {
					x: (e.clientX - rect.left) * scaleX,
					y: (e.clientY - rect.top) * scaleY
				};
			}

			function startDraw(e) {
				isDrawing = true;
				const pos = getPos(e);
				lastX = pos.x;
				lastY = pos.y;
			}

			function draw(e) {
				if (!isDrawing) return;
				e.preventDefault();
				const pos = getPos(e);
				ctx.beginPath();
				ctx.moveTo(lastX, lastY);
				ctx.lineTo(pos.x, pos.y);
				ctx.stroke();
				lastX = pos.x;
				lastY = pos.y;
			}

			function endDraw() {
				if (isDrawing) {
					isDrawing = false;
					updateSignatureData();
				}
			}

			canvas.addEventListener('mousedown', startDraw);
			canvas.addEventListener('mousemove', draw);
			canvas.addEventListener('mouseup', endDraw);
			canvas.addEventListener('mouseout', endDraw);
			canvas.addEventListener('touchstart', startDraw);
			canvas.addEventListener('touchmove', draw);
			canvas.addEventListener('touchend', endDraw);

			document.getElementById('clear-canvas')?.addEventListener('click', () => {
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				updateSignatureData();
			});
		}

		// Typed signature
		const typedInput = document.getElementById('typed-signature');
		const typedPreview = document.getElementById('typed-preview');
		const fontOptions = document.querySelectorAll('[name="sig_font"]');

		function updateTypedPreview() {
			const text = typedInput?.value || '';
			const font = document.querySelector('[name="sig_font"]:checked')?.value || 'cursive';
			const fontMap = {
				cursive: "'Brush Script MT', cursive",
				script: "'Lucida Handwriting', cursive",
				serif: "'Times New Roman', serif"
			};
			if (typedPreview) {
				typedPreview.textContent = text;
				typedPreview.style.fontFamily = fontMap[font];
				if (font === 'serif') typedPreview.style.fontStyle = 'italic';
				else typedPreview.style.fontStyle = 'normal';
			}
		}

		typedInput?.addEventListener('input', () => {
			updateTypedPreview();
			updateSignatureData();
		});

		fontOptions.forEach(opt => {
			opt.addEventListener('change', () => {
				updateTypedPreview();
				updateSignatureData();
			});
		});

		// Upload signature
		const uploadZone = document.getElementById('upload-zone');
		const sigUpload = document.getElementById('sig-upload');
		const uploadPreview = document.getElementById('upload-preview');
		const uploadedSig = document.getElementById('uploaded-sig');
		let uploadedDataUrl = '';

		uploadZone?.addEventListener('click', () => sigUpload?.click());

		uploadZone?.addEventListener('dragover', (e) => {
			e.preventDefault();
			uploadZone.classList.add('dragging');
		});

		uploadZone?.addEventListener('dragleave', () => {
			uploadZone.classList.remove('dragging');
		});

		uploadZone?.addEventListener('drop', (e) => {
			e.preventDefault();
			uploadZone.classList.remove('dragging');
			const file = e.dataTransfer.files[0];
			if (file && file.type.startsWith('image/')) {
				handleUpload(file);
			}
		});

		sigUpload?.addEventListener('change', () => {
			const file = sigUpload.files[0];
			if (file) handleUpload(file);
		});

		function handleUpload(file) {
			const reader = new FileReader();
			reader.onload = (e) => {
				uploadedDataUrl = e.target.result;
				uploadedSig.src = uploadedDataUrl;
				uploadZone.hidden = true;
				uploadPreview.hidden = false;
				updateSignatureData();
			};
			reader.readAsDataURL(file);
		}

		document.getElementById('remove-upload')?.addEventListener('click', () => {
			uploadedDataUrl = '';
			uploadedSig.src = '';
			uploadZone.hidden = false;
			uploadPreview.hidden = true;
			sigUpload.value = '';
			updateSignatureData();
		});

		// Update signature data
		function updateSignatureData() {
			const activeTab = document.querySelector('.sig-tab.active')?.dataset.tab;
			let data = '';

			if (activeTab === 'draw' && canvas) {
				data = canvas.toDataURL('image/png');
				// Check if canvas is blank
				const blankCanvas = document.createElement('canvas');
				blankCanvas.width = canvas.width;
				blankCanvas.height = canvas.height;
				if (data === blankCanvas.toDataURL('image/png')) {
					data = '';
				}
			} else if (activeTab === 'type') {
				const text = typedInput?.value || '';
				const font = document.querySelector('[name="sig_font"]:checked')?.value || 'cursive';
				if (text) {
					data = JSON.stringify({
						type: 'text',
						text,
						font
					});
				}
			} else if (activeTab === 'upload') {
				data = uploadedDataUrl;
			}

			if (signatureData) signatureData.value = data;
			validateForm();
		}

		// Form validation
		function validateForm() {
			const nameValid = document.getElementById('signer-name')?.value.trim().length >= 3;
			const emailValid = document.getElementById('signer-email')?.value.includes('@');
			const sigValid = signatureData?.value.length > 0;
			const agreed = agreeTerms?.checked;

			if (btnSign) {
				btnSign.disabled = !(nameValid && emailValid && sigValid && agreed);
			}
		}

		document.getElementById('signer-name')?.addEventListener('input', validateForm);
		agreeTerms?.addEventListener('change', validateForm);

		// CPF mask
		const cpfInput = document.getElementById('signer-cpf');
		cpfInput?.addEventListener('input', function() {
			let v = this.value.replace(/\D/g, '');
			v = v.replace(/(\d{3})(\d)/, '$1.$2');
			v = v.replace(/(\d{3})(\d)/, '$1.$2');
			v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
			this.value = v;
		});

		// Scroll hint
		if (previewContent && scrollHint) {
			previewContent.addEventListener('scroll', () => {
				const atBottom = previewContent.scrollHeight - previewContent.scrollTop <= previewContent.clientHeight + 50;
				if (atBottom) {
					scrollHint.classList.add('hidden');
				}
			});
		}

		// Form submission
		form.addEventListener('submit', function(e) {
			e.preventDefault();

			if (typeof apolloAjax === 'undefined') {
				alert('Assinatura registrada com sucesso! (demo)');
				return;
			}

			btnSign.disabled = true;
			btnSign.innerHTML = '<i class="i-loader-v" style="animation: spin 1s linear infinite;"></i> Assinando...';

			const formData = new FormData(form);

			fetch(apolloAjax.ajaxurl, {
					method: 'POST',
					body: formData
				})
				.then(r => r.json())
				.then(data => {
					if (data.success) {
						location.reload();
					} else {
						alert(data.data || 'Erro ao assinar documento.');
						btnSign.disabled = false;
						btnSign.innerHTML = '<i class="i-check-v"></i> Assinar Documento';
					}
				})
				.catch(() => {
					alert('Erro de conexão. Tente novamente.');
					btnSign.disabled = false;
					btnSign.innerHTML = '<i class="i-check-v"></i> Assinar Documento';
				});
		});
	})();
</script>
