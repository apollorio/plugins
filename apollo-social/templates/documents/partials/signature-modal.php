<?php
/**
 * Partial: Signature Modal
 *
 * Modal de assinatura digital com opções ICP-Brasil/gov.br
 * Incluído via get_template_part() ou include
 *
 * @package Apollo\Modules\Documents
 * @since   2.0.0
 * @see     uni.css for .ap-* classes
 */

declare( strict_types=1 );

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Expected variables from parent template.
$document_id    = $document_id ?? 0;
$document_title = $document_title ?? '';
$rest_nonce     = wp_create_nonce( 'wp_rest' );
$rest_url       = rest_url( 'apollo-social/v1/documents/' . $document_id . '/sign' );
$backends_url   = rest_url( 'apollo-social/v1/signatures/backends' );
?>
<!-- Signature Modal Overlay -->
<div id="apollo-signature-modal"
	class="ap-modal-overlay"
	role="dialog"
	aria-modal="true"
	aria-labelledby="signature-modal-title"
	aria-describedby="signature-modal-desc"
	data-ap-modal="signature"
	style="display: none;">

	<!-- Modal Container -->
	<div class="ap-modal ap-modal--md" role="document">
		<!-- Modal Header -->
		<div class="ap-modal__header">
			<div class="ap-modal__title-group">
				<i class="ri-quill-pen-line ap-modal__icon"></i>
				<div>
					<h2 id="signature-modal-title" class="ap-modal__title">
						<?php esc_html_e( 'Assinar Documento', 'apollo-social' ); ?>
					</h2>
					<p id="signature-modal-desc" class="ap-modal__subtitle">
						<?php esc_html_e( 'Assinatura digital com validade jurídica', 'apollo-social' ); ?>
					</p>
				</div>
			</div>
			<button type="button"
					class="ap-modal__close"
					data-ap-close-modal="signature"
					aria-label="<?php esc_attr_e( 'Fechar modal', 'apollo-social' ); ?>">
				<i class="ri-close-line"></i>
			</button>
		</div>

		<!-- Modal Body -->
		<div class="ap-modal__body">
			<!-- Document Summary Card -->
			<div class="ap-card ap-card--muted ap-mb-4">
				<div class="ap-card__body ap-flex ap-items-center ap-gap-3">
					<div class="ap-icon-box ap-icon-box--lg ap-icon-box--primary">
						<i class="ri-file-text-line"></i>
					</div>
					<div class="ap-flex-1">
						<p class="ap-text-sm ap-text-muted ap-mb-0">
							<?php esc_html_e( 'Documento a assinar', 'apollo-social' ); ?>
						</p>
						<h4 class="ap-text-base ap-font-semibold ap-mb-0" id="sign-doc-title">
							<?php echo esc_html( $document_title ); ?>
						</h4>
					</div>
					<span class="ap-badge ap-badge--warning ap-badge--sm" id="sign-status-badge">
						<span class="ap-badge__dot"></span>
						<?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
					</span>
				</div>
			</div>

			<!-- ICP-Brasil Info Tooltip -->
			<div class="ap-alert ap-alert--info ap-mb-4">
				<i class="ri-information-line ap-alert__icon"></i>
				<div class="ap-alert__content">
					<strong><?php esc_html_e( 'Sobre assinatura digital ICP-Brasil', 'apollo-social' ); ?></strong>
					<p class="ap-text-sm ap-mb-0">
						<?php esc_html_e( 'A assinatura digital ICP-Brasil tem validade jurídica equivalente à assinatura manuscrita, conforme MP 2.200-2/2001 e Lei 14.063/2020.', 'apollo-social' ); ?>
					</p>
				</div>
			</div>

			<!-- Terms Checkboxes -->
			<div class="ap-stack ap-stack--sm ap-mb-4" id="sign-terms-section">
				<label class="ap-checkbox-card" for="chk-read-document">
					<input type="checkbox"
							id="chk-read-document"
							name="terms[]"
							value="read"
							class="ap-checkbox__input"
							data-ap-sign-term>
					<span class="ap-checkbox-card__box">
						<i class="ri-check-line"></i>
					</span>
					<span class="ap-checkbox-card__label">
						<?php esc_html_e( 'Li e concordo com o conteúdo integral do documento.', 'apollo-social' ); ?>
					</span>
				</label>

				<label class="ap-checkbox-card" for="chk-authorize-sign">
					<input type="checkbox"
							id="chk-authorize-sign"
							name="terms[]"
							value="authorize"
							class="ap-checkbox__input"
							data-ap-sign-term>
					<span class="ap-checkbox-card__box">
						<i class="ri-check-line"></i>
					</span>
					<span class="ap-checkbox-card__label">
						<?php esc_html_e( 'Autorizo o uso da minha assinatura digital neste documento.', 'apollo-social' ); ?>
					</span>
				</label>
			</div>

			<!-- Error Message (hidden by default) -->
			<div class="ap-alert ap-alert--error ap-mb-4"
				id="sign-error-alert"
				role="alert"
				aria-live="assertive"
				style="display: none;">
				<i class="ri-error-warning-line ap-alert__icon" aria-hidden="true"></i>
				<span id="sign-error-message">
					<?php esc_html_e( 'Marque as opções acima para continuar.', 'apollo-social' ); ?>
				</span>
			</div>

			<!-- Signature Providers -->
			<div class="ap-stack ap-stack--md" id="sign-providers-section">
				<h5 class="ap-text-sm ap-font-semibold ap-text-muted ap-uppercase ap-tracking-wide">
					<?php esc_html_e( 'Escolha o método de assinatura', 'apollo-social' ); ?>
				</h5>

				<!-- gov.br Button -->
				<button type="button"
						class="ap-btn ap-btn--lg ap-btn--block ap-btn--primary"
						id="btn-sign-govbr"
						data-ap-sign-provider="govbr"
						data-ap-tooltip="<?php esc_attr_e( 'Assinar usando sua conta gov.br com certificado em nuvem', 'apollo-social' ); ?>"
						disabled>
					<i class="ri-shield-check-line"></i>
					<span><?php esc_html_e( 'Assinar com gov.br', 'apollo-social' ); ?></span>
				</button>

				<!-- ICP-Brasil Button -->
				<button type="button"
						class="ap-btn ap-btn--lg ap-btn--block ap-btn--outline"
						id="btn-sign-icp"
						data-ap-sign-provider="icp"
						data-ap-tooltip="<?php esc_attr_e( 'Assinar usando certificado digital A1 ou A3 (token/smartcard)', 'apollo-social' ); ?>"
						disabled>
					<i class="ri-key-2-line"></i>
					<span><?php esc_html_e( 'Certificado ICP-Brasil', 'apollo-social' ); ?></span>
				</button>

				<!-- Local Stub (dev only) -->
				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<button type="button"
						class="ap-btn ap-btn--lg ap-btn--block ap-btn--ghost"
						id="btn-sign-stub"
						data-ap-sign-provider="local-stub"
						data-ap-tooltip="<?php esc_attr_e( 'Backend de teste local (apenas desenvolvimento)', 'apollo-social' ); ?>"
						disabled>
					<i class="ri-bug-line"></i>
					<span><?php esc_html_e( 'Stub Local (Dev)', 'apollo-social' ); ?></span>
				</button>
				<?php endif; ?>
			</div>

			<!-- Success Result (hidden by default) -->
			<div class="ap-card ap-card--success ap-mt-4"
				id="sign-success-card"
				role="status"
				aria-live="polite"
				style="display: none;">
				<div class="ap-card__body">
					<div class="ap-flex ap-items-center ap-gap-3 ap-mb-3">
						<div class="ap-icon-box ap-icon-box--lg ap-icon-box--success">
							<i class="ri-checkbox-circle-fill"></i>
						</div>
						<div>
							<h4 class="ap-text-base ap-font-bold ap-text-success ap-mb-0">
								<?php esc_html_e( 'Documento Assinado!', 'apollo-social' ); ?>
							</h4>
							<p class="ap-text-sm ap-text-muted ap-mb-0">
								<?php esc_html_e( 'Sua assinatura foi registrada com sucesso.', 'apollo-social' ); ?>
							</p>
						</div>
					</div>

					<div class="ap-divider ap-my-3"></div>

					<dl class="ap-dl ap-dl--sm">
						<div class="ap-dl__row">
							<dt><?php esc_html_e( 'Data/Hora:', 'apollo-social' ); ?></dt>
							<dd id="sign-result-timestamp">--</dd>
						</div>
						<div class="ap-dl__row">
							<dt><?php esc_html_e( 'Código:', 'apollo-social' ); ?></dt>
							<dd id="sign-result-code">--</dd>
						</div>
						<div class="ap-dl__row">
							<dt><?php esc_html_e( 'Hash:', 'apollo-social' ); ?></dt>
							<dd id="sign-result-hash" class="ap-text-mono ap-text-truncate">--</dd>
						</div>
					</dl>
				</div>
			</div>
		</div>

		<!-- Modal Footer -->
		<div class="ap-modal__footer">
			<p class="ap-text-xs ap-text-muted ap-text-center ap-mb-0">
				<i class="ri-lock-line"></i>
				<?php esc_html_e( 'Ambiente seguro Apollo::rio · Seus dados estão protegidos', 'apollo-social' ); ?>
			</p>
		</div>
	</div>
</div>

<!-- Hidden data for JS -->
<script type="application/json" id="apollo-signature-config">
<?php
echo wp_json_encode(
	array(
		'documentId'  => $document_id,
		'restUrl'     => $rest_url,
		'backendsUrl' => $backends_url,
		'nonce'       => $rest_nonce,
		'i18n'        => array(
			'signing'         => __( 'Assinando...', 'apollo-social' ),
			'signed'          => __( 'Assinado', 'apollo-social' ),
			'error'           => __( 'Erro ao assinar', 'apollo-social' ),
			'checkTerms'      => __( 'Marque as opções acima para continuar.', 'apollo-social' ),
			'connectionError' => __( 'Erro de conexão. Tente novamente.', 'apollo-social' ),
		),
	)
);
?>
</script>

<style>
/* Modal Styles - complementing uni.css */
.ap-modal-overlay {
	position: fixed;
	inset: 0;
	background: rgba(15, 23, 42, 0.6);
	backdrop-filter: blur(4px);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 1rem;
	opacity: 0;
	visibility: hidden;
	transition: opacity 0.2s, visibility 0.2s;
}

.ap-modal-overlay.ap-is-open {
	opacity: 1;
	visibility: visible;
}

.ap-modal-overlay[style*="display: none"] {
	opacity: 0;
	visibility: hidden;
}

.ap-modal {
	background: var(--ap-bg-card, #fff);
	border-radius: var(--ap-radius-xl, 1rem);
	box-shadow: var(--ap-shadow-xl, 0 25px 50px -12px rgba(0, 0, 0, 0.25));
	max-height: 90vh;
	overflow: hidden;
	display: flex;
	flex-direction: column;
	transform: translateY(20px) scale(0.95);
	transition: transform 0.2s;
}

.ap-modal-overlay.ap-is-open .ap-modal {
	transform: translateY(0) scale(1);
}

.ap-modal--md {
	width: 100%;
	max-width: 480px;
}

.ap-modal__header {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	padding: 1.25rem 1.5rem;
	border-bottom: 1px solid var(--ap-border-light, #e5e7eb);
}

.ap-modal__title-group {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.ap-modal__icon {
	font-size: 1.5rem;
	color: var(--ap-color-primary, #3b82f6);
}

.ap-modal__title {
	margin: 0;
	font-size: 1.125rem;
	font-weight: 700;
	color: var(--ap-text-primary, #0f172a);
}

.ap-modal__subtitle {
	margin: 0.25rem 0 0;
	font-size: 0.75rem;
	color: var(--ap-text-muted, #64748b);
}

.ap-modal__close {
	background: none;
	border: none;
	padding: 0.5rem;
	margin: -0.25rem -0.5rem 0 0;
	cursor: pointer;
	border-radius: var(--ap-radius-md, 0.5rem);
	color: var(--ap-text-muted, #64748b);
	transition: background 0.15s, color 0.15s;
}

.ap-modal__close:hover {
	background: var(--ap-bg-muted, #f1f5f9);
	color: var(--ap-text-primary, #0f172a);
}

.ap-modal__close i {
	font-size: 1.25rem;
}

.ap-modal__body {
	padding: 1.5rem;
	overflow-y: auto;
	flex: 1;
}

.ap-modal__footer {
	padding: 1rem 1.5rem;
	border-top: 1px solid var(--ap-border-light, #e5e7eb);
	background: var(--ap-bg-muted, #f8fafc);
}

/* Checkbox Card */
.ap-checkbox-card {
	display: flex;
	align-items: flex-start;
	gap: 0.75rem;
	padding: 0.875rem 1rem;
	border: 1px solid var(--ap-border-light, #e5e7eb);
	border-radius: var(--ap-radius-md, 0.5rem);
	cursor: pointer;
	transition: border-color 0.15s, background 0.15s;
}

.ap-checkbox-card:hover {
	background: var(--ap-bg-muted, #f8fafc);
	border-color: var(--ap-border-default, #cbd5e1);
}

.ap-checkbox-card:has(.ap-checkbox__input:checked) {
	background: rgba(59, 130, 246, 0.05);
	border-color: var(--ap-color-primary, #3b82f6);
}

.ap-checkbox__input {
	position: absolute;
	opacity: 0;
	width: 0;
	height: 0;
}

.ap-checkbox-card__box {
	flex-shrink: 0;
	width: 1.25rem;
	height: 1.25rem;
	border: 2px solid var(--ap-border-default, #cbd5e1);
	border-radius: var(--ap-radius-sm, 0.25rem);
	display: flex;
	align-items: center;
	justify-content: center;
	transition: background 0.15s, border-color 0.15s;
	color: transparent;
	font-size: 0.75rem;
}

.ap-checkbox-card:has(.ap-checkbox__input:checked) .ap-checkbox-card__box {
	background: var(--ap-color-primary, #3b82f6);
	border-color: var(--ap-color-primary, #3b82f6);
	color: #fff;
}

.ap-checkbox-card__label {
	font-size: 0.875rem;
	color: var(--ap-text-secondary, #475569);
	line-height: 1.4;
}

/* Definition List */
.ap-dl {
	margin: 0;
}

.ap-dl--sm .ap-dl__row {
	font-size: 0.75rem;
}

.ap-dl__row {
	display: flex;
	justify-content: space-between;
	padding: 0.375rem 0;
}

.ap-dl__row dt {
	color: var(--ap-text-muted, #64748b);
	font-weight: 500;
}

.ap-dl__row dd {
	margin: 0;
	color: var(--ap-text-primary, #0f172a);
	font-weight: 600;
}

/* Card variants */
.ap-card--success {
	background: rgba(16, 185, 129, 0.05);
	border: 1px solid rgba(16, 185, 129, 0.2);
}

.ap-text-success {
	color: var(--ap-color-success, #10b981);
}

.ap-icon-box--success {
	background: rgba(16, 185, 129, 0.1);
	color: var(--ap-color-success, #10b981);
}

/* Utility classes that may be missing from uni.css */
.ap-mb-0 { margin-bottom: 0 !important; }
.ap-mb-3 { margin-bottom: 0.75rem !important; }
.ap-mb-4 { margin-bottom: 1rem !important; }
.ap-mt-4 { margin-top: 1rem !important; }
.ap-my-3 { margin-top: 0.75rem !important; margin-bottom: 0.75rem !important; }
.ap-gap-3 { gap: 0.75rem !important; }
.ap-flex { display: flex !important; }
.ap-flex-1 { flex: 1 !important; }
.ap-items-center { align-items: center !important; }
.ap-text-sm { font-size: 0.875rem !important; }
.ap-text-xs { font-size: 0.75rem !important; }
.ap-text-base { font-size: 1rem !important; }
.ap-text-muted { color: var(--ap-text-muted, #64748b) !important; }
.ap-text-mono { font-family: 'Fira Code', monospace !important; }
.ap-text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ap-text-center { text-align: center !important; }
.ap-font-semibold { font-weight: 600 !important; }
.ap-font-bold { font-weight: 700 !important; }
.ap-uppercase { text-transform: uppercase !important; }
.ap-tracking-wide { letter-spacing: 0.05em !important; }

.ap-stack { display: flex; flex-direction: column; }
.ap-stack--sm { gap: 0.5rem; }
.ap-stack--md { gap: 1rem; }

.ap-divider {
	height: 1px;
	background: var(--ap-border-light, #e5e7eb);
}

/* Icon box */
.ap-icon-box {
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: var(--ap-radius-md, 0.5rem);
}

.ap-icon-box--lg {
	width: 2.5rem;
	height: 2.5rem;
	font-size: 1.25rem;
}

.ap-icon-box--primary {
	background: rgba(59, 130, 246, 0.1);
	color: var(--ap-color-primary, #3b82f6);
}

/* Alert */
.ap-alert {
	display: flex;
	align-items: flex-start;
	gap: 0.75rem;
	padding: 0.875rem 1rem;
	border-radius: var(--ap-radius-md, 0.5rem);
}

.ap-alert--info {
	background: rgba(59, 130, 246, 0.05);
	border: 1px solid rgba(59, 130, 246, 0.15);
	color: var(--ap-text-secondary, #475569);
}

.ap-alert--info .ap-alert__icon {
	color: var(--ap-color-info, #3b82f6);
}

.ap-alert--error {
	background: rgba(239, 68, 68, 0.05);
	border: 1px solid rgba(239, 68, 68, 0.15);
	color: #dc2626;
}

.ap-alert--error .ap-alert__icon {
	color: #dc2626;
}

.ap-alert__icon {
	font-size: 1.25rem;
	flex-shrink: 0;
	margin-top: 0.125rem;
}

.ap-alert__content strong {
	display: block;
	margin-bottom: 0.25rem;
	color: var(--ap-text-primary, #0f172a);
}

/* Badge dot */
.ap-badge__dot {
	width: 0.375rem;
	height: 0.375rem;
	border-radius: 50%;
	background: currentColor;
}

.ap-badge--warning .ap-badge__dot {
	animation: pulse 2s infinite;
}

@keyframes pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}

/* Card muted */
.ap-card--muted {
	background: var(--ap-bg-muted, #f8fafc);
	border: 1px solid var(--ap-border-light, #e5e7eb);
}

.ap-card__body {
	padding: 1rem;
}

/* Button block */
.ap-btn--block {
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
}

.ap-btn--lg {
	padding: 0.875rem 1.5rem;
	font-size: 0.9375rem;
}

.ap-btn--ghost {
	background: transparent;
	color: var(--ap-text-muted, #64748b);
	border: 1px dashed var(--ap-border-default, #cbd5e1);
}

.ap-btn--ghost:hover {
	background: var(--ap-bg-muted, #f8fafc);
	color: var(--ap-text-primary, #0f172a);
}

.ap-btn:disabled {
	opacity: 0.5;
	cursor: not-allowed;
}
</style>
