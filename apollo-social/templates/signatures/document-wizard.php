<?php
/**
 * Document Wizard Template
 * STRICT MODE: 100% UNI.CSS compliance
 * Step-by-step wizard for creating documents for signature
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue global assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
}
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

// Get document templates
$templates = array(
	'contract'      => array(
		'title'       => __( 'Contrato de Prestação de Serviços', 'apollo-social' ),
		'description' => __( 'Modelo completo para prestação de serviços com cláusulas padrão', 'apollo-social' ),
		'icon'        => 'ri-file-text-line',
	),
	'nda'           => array(
		'title'       => __( 'Acordo de Confidencialidade (NDA)', 'apollo-social' ),
		'description' => __( 'Termo de confidencialidade para proteção de informações', 'apollo-social' ),
		'icon'        => 'ri-shield-keyhole-line',
	),
	'authorization' => array(
		'title'       => __( 'Autorização de Uso de Imagem', 'apollo-social' ),
		'description' => __( 'Autorização para uso de imagem e voz em materiais', 'apollo-social' ),
		'icon'        => 'ri-camera-line',
	),
	'partnership'   => array(
		'title'       => __( 'Termo de Parceria', 'apollo-social' ),
		'description' => __( 'Acordo de parceria entre organizações ou empresas', 'apollo-social' ),
		'icon'        => 'ri-handshake-line',
	),
);

get_header();
?>

<!-- STRICT MODE: Document Wizard - UNI.CSS v5.2.0 -->
<div class="ap-page ap-bg-gradient-primary ap-min-h-screen ap-py-6">
	<div class="ap-container ap-max-w-3xl">

		<div class="ap-card ap-shadow-lg">
			<!-- Header -->
			<div class="ap-card-header ap-bg-dark ap-text-white ap-text-center ap-py-8">
				<h1 class="ap-heading-xl ap-flex ap-items-center ap-justify-center ap-gap-2">
					<i class="ri-edit-2-line"></i>
					<?php esc_html_e( 'Criar Documento para Assinatura', 'apollo-social' ); ?>
				</h1>
				<p class="ap-text-white-80 ap-mt-2">
					<?php esc_html_e( 'Trilhos A e B conforme Lei 14.063/2020', 'apollo-social' ); ?>
				</p>
			</div>

			<!-- Progress Bar -->
			<div class="ap-progress-bar ap-h-1">
				<div class="ap-progress-fill ap-bg-primary" id="progressFill" style="width: 20%"></div>
			</div>

			<div class="ap-card-body ap-p-8">
				<!-- Step Indicator -->
				<div class="ap-flex ap-justify-center ap-gap-2 ap-mb-8">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="ap-step-dot <?php echo $i === 1 ? 'ap-step-dot-active' : ''; ?>"
							data-step="<?php echo esc_attr( $i ); ?>"
							data-ap-tooltip="<?php echo esc_attr( sprintf( __( 'Etapa %d', 'apollo-social' ), $i ) ); ?>"></span>
					<?php endfor; ?>
				</div>

				<!-- Step 1: Choose Template -->
				<div class="ap-wizard-step ap-wizard-step-active" id="step1">
					<h2 class="ap-heading-lg ap-flex ap-items-center ap-gap-2 ap-mb-2">
						<i class="ri-file-list-3-line ap-text-primary"></i>
						<?php esc_html_e( 'Escolher Modelo', 'apollo-social' ); ?>
					</h2>
					<p class="ap-text-muted ap-mb-6">
						<?php esc_html_e( 'Selecione um modelo de documento para personalizar', 'apollo-social' ); ?>
					</p>

					<div class="ap-grid ap-grid-2 ap-gap-4">
						<?php foreach ( $templates as $key => $template ) : ?>
						<button type="button"
								class="ap-card ap-card-hover ap-card-selectable ap-text-left ap-p-5"
								data-template="<?php echo esc_attr( $key ); ?>"
								data-ap-tooltip="<?php echo esc_attr( $template['description'] ); ?>">
							<div class="ap-flex ap-items-start ap-gap-3">
								<div class="ap-avatar ap-avatar-md ap-bg-primary-100">
									<i class="<?php echo esc_attr( $template['icon'] ); ?> ap-text-primary"></i>
								</div>
								<div>
									<h3 class="ap-font-semibold ap-mb-1"><?php echo esc_html( $template['title'] ); ?></h3>
									<p class="ap-text-sm ap-text-muted"><?php echo esc_html( $template['description'] ); ?></p>
								</div>
							</div>
						</button>
						<?php endforeach; ?>
					</div>

					<div class="ap-flex ap-justify-end ap-mt-8 ap-pt-6 ap-border-t">
						<button class="ap-btn ap-btn-primary" id="nextStep1" disabled
								data-ap-tooltip="<?php esc_attr_e( 'Selecione um modelo primeiro', 'apollo-social' ); ?>">
							<?php esc_html_e( 'Próximo', 'apollo-social' ); ?>
							<i class="ri-arrow-right-line"></i>
						</button>
					</div>
				</div>

				<!-- Step 2: Fill Template Data -->
				<div class="ap-wizard-step" id="step2">
					<h2 class="ap-heading-lg ap-flex ap-items-center ap-gap-2 ap-mb-2">
						<i class="ri-edit-line ap-text-primary"></i>
						<?php esc_html_e( 'Preencher Dados', 'apollo-social' ); ?>
					</h2>
					<p class="ap-text-muted ap-mb-6">
						<?php esc_html_e( 'Complete as informações do documento', 'apollo-social' ); ?>
					</p>

					<form id="templateForm" class="ap-space-y-4">
						<div class="ap-form-group">
							<label for="doc-title" class="ap-form-label"><?php esc_html_e( 'Título do Documento', 'apollo-social' ); ?></label>
							<input type="text" id="doc-title" class="ap-form-input" name="title"
									placeholder="<?php esc_attr_e( 'Ex: Contrato de Prestação de Serviços - Projeto XYZ', 'apollo-social' ); ?>"
									data-ap-tooltip="<?php esc_attr_e( 'Nome identificador do documento', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'Título do Documento', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="contractor-name" class="ap-form-label"><?php esc_html_e( 'Nome do Contratante', 'apollo-social' ); ?></label>
							<input type="text" id="contractor-name" class="ap-form-input" name="contractor_name"
									placeholder="<?php esc_attr_e( 'Nome da empresa ou pessoa contratante', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'Nome do Contratante', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="contracted-name" class="ap-form-label"><?php esc_html_e( 'Nome do Contratado', 'apollo-social' ); ?></label>
							<input type="text" id="contracted-name" class="ap-form-input" name="contracted_name"
									placeholder="<?php esc_attr_e( 'Nome da empresa ou pessoa contratada', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'Nome do Contratado', 'apollo-social' ); ?>">
						</div>

						<div class="ap-grid ap-grid-2 ap-gap-4">
							<div class="ap-form-group">
								<label for="contract-value" class="ap-form-label"><?php esc_html_e( 'Valor do Contrato', 'apollo-social' ); ?></label>
					<input type="text" id="contract-value" class="ap-form-input" name="contract_value" placeholder="<?php esc_attr_e( 'R$ 0,00', 'apollo-social' ); ?>" title="<?php esc_attr_e( 'Valor do Contrato', 'apollo-social' ); ?>">
							</div>

							<div class="ap-form-group">
								<label for="start-date" class="ap-form-label"><?php esc_html_e( 'Data de Início', 'apollo-social' ); ?></label>
								<input type="date" id="start-date" class="ap-form-input" name="start_date" title="<?php esc_attr_e( 'Data de Início', 'apollo-social' ); ?>">
							</div>
						</div>

						<div class="ap-form-group">
							<label for="doc-notes" class="ap-form-label"><?php esc_html_e( 'Observações Adicionais', 'apollo-social' ); ?></label>
							<textarea id="doc-notes" class="ap-form-textarea" name="notes" rows="3"
										placeholder="<?php esc_attr_e( 'Informações específicas para este documento...', 'apollo-social' ); ?>"
										title="<?php esc_attr_e( 'Observações Adicionais', 'apollo-social' ); ?>"></textarea>

					<div class="ap-flex ap-justify-between ap-mt-8 ap-pt-6 ap-border-t">
						<button class="ap-btn ap-btn-secondary" id="prevStep2">
							<i class="ri-arrow-left-line"></i>
							<?php esc_html_e( 'Voltar', 'apollo-social' ); ?>
						</button>
						<button class="ap-btn ap-btn-primary" id="nextStep2">
							<?php esc_html_e( 'Próximo', 'apollo-social' ); ?>
							<i class="ri-arrow-right-line"></i>
						</button>
					</div>
				</div>

				<!-- Step 3: Signer Information -->
				<div class="ap-wizard-step" id="step3">
					<h2 class="ap-heading-lg ap-flex ap-items-center ap-gap-2 ap-mb-2">
						<i class="ri-user-line ap-text-primary"></i>
						<?php esc_html_e( 'Dados do Signatário', 'apollo-social' ); ?>
					</h2>
					<p class="ap-text-muted ap-mb-6">
						<?php esc_html_e( 'Informe os dados da pessoa que irá assinar', 'apollo-social' ); ?>
					</p>

					<form id="signerForm" class="ap-space-y-4">
						<div class="ap-form-group">
							<label for="signer-name" class="ap-form-label"><?php esc_html_e( 'Nome Completo', 'apollo-social' ); ?> *</label>
							<input type="text" id="signer-name" class="ap-form-input" name="signer_name"
									placeholder="<?php esc_attr_e( 'Nome completo do signatário', 'apollo-social' ); ?>" required
									data-ap-tooltip="<?php esc_attr_e( 'Nome como aparecerá no documento', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'Nome Completo', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="signer-email" class="ap-form-label"><?php esc_html_e( 'E-mail', 'apollo-social' ); ?> *</label>
							<input type="email" id="signer-email" class="ap-form-input" name="signer_email"
					placeholder="<?php esc_attr_e( 'email@exemplo.com', 'apollo-social' ); ?>" required
									data-ap-tooltip="<?php esc_attr_e( 'E-mail para envio do link de assinatura', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'E-mail', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="signer-document" class="ap-form-label"><?php esc_html_e( 'CPF ou CNPJ', 'apollo-social' ); ?></label>
							<input type="text" id="signer-document" class="ap-form-input" name="signer_document"
									placeholder="<?php esc_attr_e( '000.000.000-00 ou 00.000.000/0001-00', 'apollo-social' ); ?>"
									data-ap-tooltip="<?php esc_attr_e( 'Documento para validação', 'apollo-social' ); ?>"
									title="<?php esc_attr_e( 'CPF ou CNPJ', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="signer-phone" class="ap-form-label"><?php esc_html_e( 'Telefone (opcional)', 'apollo-social' ); ?></label>
				<input type="tel" id="signer-phone" class="ap-form-input" name="signer_phone" placeholder="<?php esc_attr_e( '(11) 99999-9999', 'apollo-social' ); ?>" title="<?php esc_attr_e( 'Telefone', 'apollo-social' ); ?>">

					<div class="ap-flex ap-justify-between ap-mt-8 ap-pt-6 ap-border-t">
						<button class="ap-btn ap-btn-secondary" id="prevStep3">
							<i class="ri-arrow-left-line"></i>
							<?php esc_html_e( 'Voltar', 'apollo-social' ); ?>
						</button>
						<button class="ap-btn ap-btn-primary" id="nextStep3">
							<?php esc_html_e( 'Próximo', 'apollo-social' ); ?>
							<i class="ri-arrow-right-line"></i>
						</button>
					</div>
				</div>

				<!-- Step 4: Choose Signature Track -->
				<div class="ap-wizard-step" id="step4">
					<h2 class="ap-heading-lg ap-flex ap-items-center ap-gap-2 ap-mb-2">
						<i class="ri-scales-3-line ap-text-primary"></i>
						<?php esc_html_e( 'Escolher Trilho de Assinatura', 'apollo-social' ); ?>
					</h2>
					<p class="ap-text-muted ap-mb-6">
						<?php esc_html_e( 'Selecione o nível de segurança jurídica necessário', 'apollo-social' ); ?>
					</p>

					<div class="ap-card ap-card-hover ap-card-selectable ap-p-6 ap-text-center" data-track="track_b">
						<div class="ap-avatar ap-avatar-xl ap-mx-auto ap-mb-4 ap-bg-primary-100">
							<i class="ri-shield-check-line ap-text-3xl ap-text-primary"></i>
						</div>
						<h3 class="ap-heading-md ap-mb-1"><?php esc_html_e( 'Assinatura Qualificada', 'apollo-social' ); ?></h3>
						<p class="ap-text-primary ap-font-semibold ap-mb-3"><?php esc_html_e( 'ICP-Brasil (GOV.BR)', 'apollo-social' ); ?></p>
						<p class="ap-text-sm ap-text-muted ap-mb-4">
							<?php esc_html_e( 'Para documentos oficiais, cartórios e órgãos públicos. Requer certificado digital ICP-Brasil ou login GOV.BR.', 'apollo-social' ); ?>
						</p>
						<div class="ap-alert ap-alert-info ap-text-xs ap-text-left">
							<strong><?php esc_html_e( 'Base Legal:', 'apollo-social' ); ?></strong> Lei 14.063/2020 + MP 2.200-2/2001<br>
							<strong><?php esc_html_e( 'Validade:', 'apollo-social' ); ?></strong> <?php esc_html_e( 'Equivale à assinatura manuscrita', 'apollo-social' ); ?>
						</div>
					</div>

					<div class="ap-alert ap-alert-primary ap-mt-6">
						<h4 class="ap-flex ap-items-center ap-gap-2 ap-font-semibold ap-mb-2">
							<i class="ri-information-line"></i>
							<?php esc_html_e( 'Informações de Compliance', 'apollo-social' ); ?>
						</h4>
						<p class="ap-text-sm">
							<strong><?php esc_html_e( 'Assinatura Qualificada:', 'apollo-social' ); ?></strong>
							<?php esc_html_e( 'Obrigatório para cartórios, órgãos públicos e contratos de alto valor. Equivale juridicamente à assinatura manuscrita com presunção absoluta de validade.', 'apollo-social' ); ?>
						</p>
					</div>

					<div class="ap-flex ap-justify-between ap-mt-8 ap-pt-6 ap-border-t">
						<button class="ap-btn ap-btn-secondary" id="prevStep4">
							<i class="ri-arrow-left-line"></i>
							<?php esc_html_e( 'Voltar', 'apollo-social' ); ?>
						</button>
						<button class="ap-btn ap-btn-primary" id="nextStep4" disabled
								data-ap-tooltip="<?php esc_attr_e( 'Selecione o trilho de assinatura', 'apollo-social' ); ?>">
							<?php esc_html_e( 'Criar Documento', 'apollo-social' ); ?>
							<i class="ri-check-line"></i>
						</button>
					</div>
				</div>

				<!-- Step 5: Success -->
				<div class="ap-wizard-step" id="step5">
					<div class="ap-text-center ap-py-8">
						<div class="ap-avatar ap-avatar-xl ap-mx-auto ap-mb-4 ap-bg-success-100">
							<i class="ri-checkbox-circle-line ap-text-4xl ap-text-success"></i>
						</div>
						<h2 class="ap-heading-lg ap-text-success ap-mb-2">
							<?php esc_html_e( 'Documento Criado com Sucesso!', 'apollo-social' ); ?>
						</h2>
						<p class="ap-text-muted ap-mb-6">
							<?php esc_html_e( 'O documento foi gerado e está pronto para assinatura.', 'apollo-social' ); ?>
						</p>

						<div class="ap-input-group ap-max-w-lg ap-mx-auto ap-mb-6">
							<input type="text" id="signingUrl" class="ap-form-input ap-font-mono ap-text-sm" readonly>
							<button class="ap-btn ap-btn-primary" onclick="copySigningUrl()"
									data-ap-tooltip="<?php esc_attr_e( 'Copiar link', 'apollo-social' ); ?>">
								<i class="ri-file-copy-line"></i>
								<?php esc_html_e( 'Copiar', 'apollo-social' ); ?>
							</button>
						</div>

						<div class="ap-alert ap-alert-info ap-text-left">
							<h4 class="ap-flex ap-items-center ap-gap-2 ap-font-semibold ap-mb-2">
								<i class="ri-lock-line"></i>
								<?php esc_html_e( 'Próximos Passos', 'apollo-social' ); ?>
							</h4>
							<ol class="ap-list-decimal ap-list-inside ap-text-sm ap-space-y-1">
								<li><?php esc_html_e( 'Compartilhe o link de assinatura com o signatário', 'apollo-social' ); ?></li>
								<li><?php esc_html_e( 'O signatário receberá um e-mail com instruções', 'apollo-social' ); ?></li>
								<li><?php esc_html_e( 'Você será notificado quando a assinatura for concluída', 'apollo-social' ); ?></li>
								<li><?php esc_html_e( 'O documento assinado ficará disponível para download', 'apollo-social' ); ?></li>
							</ol>
						</div>

						<div class="ap-flex ap-justify-center ap-gap-4 ap-mt-8">
							<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="ap-btn ap-btn-secondary">
								<i class="ri-folder-line"></i>
								<?php esc_html_e( 'Ver Documentos', 'apollo-social' ); ?>
							</a>
							<button class="ap-btn ap-btn-primary" onclick="location.reload()">
								<i class="ri-add-line"></i>
								<?php esc_html_e( 'Criar Novo', 'apollo-social' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	let currentStep = 1;
	let selectedTemplate = null;
	let selectedTrack = null;
	let formData = {};

	// Step navigation
	function updateProgressBar() {
		const progress = (currentStep / 5) * 100;
		document.getElementById('progressFill').style.width = progress + '%';

		document.querySelectorAll('.ap-step-dot').forEach((dot, index) => {
			const stepNum = index + 1;
			dot.classList.remove('ap-step-dot-active', 'ap-step-dot-completed');

			if (stepNum < currentStep) {
				dot.classList.add('ap-step-dot-completed');
			} else if (stepNum === currentStep) {
				dot.classList.add('ap-step-dot-active');
			}
		});
	}

	function showStep(step) {
		document.querySelectorAll('.ap-wizard-step').forEach(s => s.classList.remove('ap-wizard-step-active'));
		document.getElementById('step' + step).classList.add('ap-wizard-step-active');
		currentStep = step;
		updateProgressBar();
	}

	// Template selection
	document.querySelectorAll('[data-template]').forEach(card => {
		card.addEventListener('click', function() {
			document.querySelectorAll('[data-template]').forEach(c => c.classList.remove('ap-card-selected'));
			this.classList.add('ap-card-selected');
			selectedTemplate = this.dataset.template;
			document.getElementById('nextStep1').disabled = false;
		});
	});

	// Track selection
	document.querySelectorAll('[data-track]').forEach(card => {
		card.addEventListener('click', function() {
			document.querySelectorAll('[data-track]').forEach(c => c.classList.remove('ap-card-selected'));
			this.classList.add('ap-card-selected');
			selectedTrack = this.dataset.track;
			document.getElementById('nextStep4').disabled = false;
		});
	});

	// Navigation buttons
	document.getElementById('nextStep1').addEventListener('click', () => showStep(2));
	document.getElementById('prevStep2').addEventListener('click', () => showStep(1));
	document.getElementById('nextStep2').addEventListener('click', () => {
		const form = document.getElementById('templateForm');
		const formDataObj = new FormData(form);
		for (let [key, value] of formDataObj.entries()) {
			formData[key] = value;
		}
		showStep(3);
	});

	document.getElementById('prevStep3').addEventListener('click', () => showStep(2));
	document.getElementById('nextStep3').addEventListener('click', () => {
		const form = document.getElementById('signerForm');
		if (form.checkValidity()) {
			const formDataObj = new FormData(form);
			for (let [key, value] of formDataObj.entries()) {
				formData[key] = value;
			}
			showStep(4);
		} else {
			form.reportValidity();
		}
	});

	document.getElementById('prevStep4').addEventListener('click', () => showStep(3));
	document.getElementById('nextStep4').addEventListener('click', () => {
		createDocument();
	});

	function createDocument() {
		const btn = document.getElementById('nextStep4');
		btn.disabled = true;
		btn.innerHTML = '<i class="ri-loader-4-line ap-animate-spin"></i> <?php echo esc_js( __( 'Criando...', 'apollo-social' ) ); ?>';

		const documentData = {
			template: selectedTemplate,
			track: selectedTrack,
			data: formData
		};

		// API call would go here
		setTimeout(() => {
			const signingUrl = `${window.location.origin}/sign/${Math.random().toString(36).substr(2, 9)}`;
			document.getElementById('signingUrl').value = signingUrl;
			showStep(5);
		}, 2000);
	}
});

function copySigningUrl() {
	const input = document.getElementById('signingUrl');
	input.select();
	document.execCommand('copy');

	const btn = event.target.closest('button');
	const originalText = btn.innerHTML;
	btn.innerHTML = '<i class="ri-check-line"></i> <?php echo esc_js( __( 'Copiado!', 'apollo-social' ) ); ?>';
	setTimeout(() => {
		btn.innerHTML = originalText;
	}, 2000);
}
</script>

<?php get_footer(); ?>
