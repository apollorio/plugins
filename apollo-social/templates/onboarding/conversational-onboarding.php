<?php
/**
 * Conversational Onboarding Template
 * STRICT MODE: 100% UNI.CSS conformance
 * Full-screen onboarding wizard with chat-like interface
 *
 * @package Apollo_Social
 * @subpackage Onboarding
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Base JS.
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			array(),
			'2.0.0',
			true
		);

		// Onboarding-specific inline styles.
		$onboarding_css = '
			.ap-onboarding-page {
				min-height: 100vh;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 20px;
				background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
			}
			.ap-onboarding-container {
				background: var(--ap-bg-card);
				border-radius: var(--ap-radius-2xl);
				box-shadow: var(--ap-shadow-xl);
				max-width: 480px;
				width: 100%;
				height: 600px;
				display: flex;
				flex-direction: column;
				overflow: hidden;
			}
			.ap-onboarding-header {
				background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
				color: white;
				padding: 20px;
			}
		';
		wp_add_inline_style( 'apollo-uni-css', $onboarding_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

$nonce = wp_create_nonce( 'apollo_onboarding' );
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bem-vindo ao Apollo Social - Onboarding</title>
	<?php wp_head(); ?>

	<style>
	/* Onboarding specific styles extending UNI.CSS */
		display: flex;
		align-items: center;
		gap: 15px;
	}

	.ap-onboarding-avatar {
		width: 50px;
		height: 50px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.2);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24px;
		border: 3px solid rgba(255, 255, 255, 0.3);
	}

	.ap-onboarding-title {
		font-size: var(--ap-text-lg);
		font-weight: 600;
		margin: 0 0 4px 0;
	}

	.ap-onboarding-status {
		font-size: var(--ap-text-sm);
		opacity: 0.9;
	}

	.ap-onboarding-progress {
		height: 4px;
		background: rgba(255, 255, 255, 0.2);
		overflow: hidden;
	}

	.ap-onboarding-progress-fill {
		height: 100%;
		background: var(--ap-color-success);
		transition: width 0.5s ease;
		width: 0%;
	}

	.ap-onboarding-messages {
		flex: 1;
		padding: 20px;
		overflow-y: auto;
		background: var(--ap-bg-surface);
	}

	.ap-onboarding-message {
		margin-bottom: 20px;
		animation: fadeInUp 0.4s ease;
	}

	.ap-onboarding-bubble {
		background: var(--ap-bg-card);
		border-radius: var(--ap-radius-xl) var(--ap-radius-xl) var(--ap-radius-xl) 4px;
		padding: 16px 20px;
		box-shadow: var(--ap-shadow-sm);
		max-width: 85%;
	}

	.ap-onboarding-bubble h4 {
		color: var(--ap-text-primary);
		font-size: var(--ap-text-md);
		margin: 0 0 8px 0;
	}

	.ap-onboarding-bubble p {
		color: var(--ap-text-secondary);
		line-height: 1.5;
		margin: 0;
	}

	.ap-onboarding-input {
		background: var(--ap-bg-card);
		border-top: 1px solid var(--ap-border-light);
		padding: 20px;
	}

	.ap-onboarding-btn-grid {
		display: grid;
		gap: 10px;
	}

	.ap-onboarding-btn-grid.two-cols {
		grid-template-columns: 1fr 1fr;
	}

	.ap-onboarding-btn-grid.three-cols {
		grid-template-columns: repeat(3, 1fr);
	}

	.ap-onboarding-choice {
		padding: 12px 16px;
		border: 2px solid var(--ap-border-default);
		border-radius: var(--ap-radius-lg);
		background: var(--ap-bg-card);
		color: var(--ap-text-secondary);
		font-size: var(--ap-text-sm);
		font-weight: 500;
		cursor: pointer;
		transition: var(--ap-transition-fast);
		text-align: center;
		font-family: var(--ap-font-primary);
	}

	.ap-onboarding-choice:hover {
		border-color: var(--ap-orange-500);
		background: var(--ap-orange-50);
	}

	.ap-onboarding-choice.selected {
		border-color: var(--ap-orange-500);
		background: var(--ap-orange-500);
		color: white;
	}

	.ap-onboarding-interest-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 8px;
		margin-bottom: 15px;
	}

	.ap-onboarding-interest {
		padding: 10px 12px;
		border: 2px solid var(--ap-border-default);
		border-radius: var(--ap-radius-md);
		background: var(--ap-bg-card);
		color: var(--ap-text-secondary);
		font-size: var(--ap-text-sm);
		cursor: pointer;
		transition: var(--ap-transition-fast);
		text-align: center;
		font-family: var(--ap-font-primary);
	}

	.ap-onboarding-interest:hover {
		border-color: var(--ap-orange-500);
		background: var(--ap-orange-50);
	}

	.ap-onboarding-interest.selected {
		border-color: var(--ap-color-success);
		background: var(--ap-color-success);
		color: white;
	}

	.ap-onboarding-primary-btn {
		width: 100%;
		padding: 14px;
		background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
		color: white;
		border: none;
		border-radius: var(--ap-radius-lg);
		font-size: var(--ap-text-md);
		font-weight: 600;
		cursor: pointer;
		transition: var(--ap-transition-fast);
		margin-top: 10px;
		font-family: var(--ap-font-primary);
	}

	.ap-onboarding-primary-btn:hover {
		transform: translateY(-2px);
		box-shadow: var(--ap-shadow-lg);
	}

	.ap-onboarding-primary-btn:disabled {
		opacity: 0.6;
		cursor: not-allowed;
		transform: none;
		box-shadow: none;
	}

	.ap-onboarding-verification {
		background: var(--ap-color-info-bg);
		border: 1px solid var(--ap-color-info);
		border-radius: var(--ap-radius-lg);
		padding: 16px;
		margin-bottom: 15px;
	}

	.ap-onboarding-verification h5 {
		color: var(--ap-color-info);
		margin: 0 0 10px 0;
		font-size: var(--ap-text-sm);
	}

	.ap-onboarding-error {
		background: var(--ap-color-error-bg);
		border: 1px solid var(--ap-color-error);
		color: var(--ap-color-error);
		padding: 12px;
		border-radius: var(--ap-radius-md);
		margin-bottom: 15px;
		font-size: var(--ap-text-sm);
	}

	.ap-onboarding-completion {
		text-align: center;
		padding: 20px;
	}

	.ap-onboarding-completion-icon {
		font-size: 72px;
		margin-bottom: 20px;
	}

	.ap-onboarding-badges {
		display: flex;
		justify-content: center;
		gap: 10px;
		margin: 20px 0;
		flex-wrap: wrap;
	}

	.ap-onboarding-loading {
		display: none;
		text-align: center;
		padding: 20px;
		color: var(--ap-text-muted);
	}

	.ap-onboarding-spinner {
		width: 32px;
		height: 32px;
		border: 3px solid var(--ap-border-default);
		border-top: 3px solid var(--ap-orange-500);
		border-radius: 50%;
		animation: spin 1s linear infinite;
		margin: 0 auto 10px;
	}

	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}

	@keyframes fadeInUp {
		from { opacity: 0; transform: translateY(20px); }
		to { opacity: 1; transform: translateY(0); }
	}

	@media (max-width: 480px) {
		.ap-onboarding-page {
			padding: 0;
		}

		.ap-onboarding-container {
			height: 100vh;
			border-radius: 0;
			max-width: none;
		}

		.ap-onboarding-interest-grid {
			grid-template-columns: 1fr;
		}

		.ap-onboarding-btn-grid.three-cols {
			grid-template-columns: 1fr;
		}
	}
	</style>
</head>
<body class="ap-onboarding-page">
	<div class="ap-onboarding-container">
		<!-- Header -->
		<div class="ap-onboarding-header">
			<div class="ap-onboarding-avatar">🚀</div>
			<div>
				<h2 class="ap-onboarding-title">Apollo Assistant</h2>
				<div class="ap-onboarding-status" id="chatStatus">Iniciando conversa...</div>
			</div>
		</div>

		<!-- Progress -->
		<div class="ap-onboarding-progress">
			<div class="ap-onboarding-progress-fill" id="progressFill"></div>
		</div>

		<!-- Messages -->
		<div class="ap-onboarding-messages" id="chatMessages">
			<!-- Messages loaded dynamically -->
		</div>

		<!-- Input Section -->
		<div class="ap-onboarding-input" id="inputSection">
			<!-- Input loaded dynamically -->
		</div>

		<!-- Loading -->
		<div class="ap-onboarding-loading" id="loading">
			<div class="ap-onboarding-spinner"></div>
			<p>Processando...</p>
		</div>
	</div>

	<script>
	class ApolloOnboarding {
		constructor() {
			this.sessionId = null;
			this.currentStep = null;
			this.stepData = null;
			this.selectedInterests = [];
			this.selectedOption = null;
			this.init();
		}

		async init() {
			try {
				const response = await this.apiCall('apollo_start_onboarding', {});

				if (response.success) {
					this.sessionId = response.data.session_id;
					this.loadStep(response.data.step_data, response.data.progress);
				} else {
					this.showError('Erro ao iniciar onboarding');
				}
			} catch (error) {
				this.showError('Erro de conexão');
			}
		}

		loadStep(stepData, progress) {
			this.stepData = stepData;
			this.updateProgress(progress);
			this.updateChatStatus();
			this.addMessage(stepData);
			this.renderInput(stepData);
		}

		addMessage(stepData) {
			const container = document.getElementById('chatMessages');
			const msg = document.createElement('div');
			msg.className = 'ap-onboarding-message';
			msg.innerHTML = `
				<div class="ap-avatar ap-avatar-sm" style="background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600)); color: white; margin-bottom: 8px;">
					<i class="ri-robot-2-line"></i>
				</div>
				<div class="ap-onboarding-bubble">
					<h4>${stepData.title}</h4>
					<p>${stepData.message}</p>
				</div>
			`;
			container.appendChild(msg);
			container.scrollTop = container.scrollHeight;
		}

		renderInput(stepData) {
			const section = document.getElementById('inputSection');
			let html = '';

			switch (stepData.input_type) {
				case 'text':
					html = this.renderTextInput(stepData);
					break;
				case 'location':
					html = this.renderLocationInput(stepData);
					break;
				case 'multi_select':
					html = this.renderMultiSelectInput(stepData);
					break;
				case 'select':
					html = this.renderSelectInput(stepData);
					break;
				case 'verification_code':
					html = this.renderVerificationInput(stepData);
					break;
				default:
					html = this.renderButtonInput(stepData);
			}

			section.innerHTML = html;
			this.attachEventListeners(stepData);
		}

		renderTextInput(stepData) {
			return `
				<div class="ap-form-group">
					<input type="text" id="textInput" class="ap-form-input"
							placeholder="${stepData.input_placeholder}" maxlength="100"
							data-ap-tooltip="Digite sua resposta">
				</div>
				<button class="ap-onboarding-primary-btn" onclick="apollo.submitStep()" data-ap-tooltip="Enviar resposta">
					Continuar
				</button>
			`;
		}

		renderLocationInput(stepData) {
			return `
				<div class="ap-form-group">
					<input type="text" id="locationInput" class="ap-form-input"
							placeholder="${stepData.input_placeholder}"
							data-ap-tooltip="Digite sua localização">
				</div>
				<div class="ap-onboarding-btn-grid two-cols">
					<button class="ap-onboarding-choice" onclick="apollo.detectLocation()" data-ap-tooltip="Detectar localização automaticamente">
						🌍 Detectar
					</button>
					<button class="ap-onboarding-choice" onclick="apollo.skipStep()" data-ap-tooltip="Pular esta etapa">
						⏭️ Pular
					</button>
				</div>
				<button class="ap-onboarding-primary-btn" onclick="apollo.submitStep()">Continuar</button>
			`;
		}

		renderMultiSelectInput(stepData) {
			const options = Object.entries(stepData.options || {});
			const optionsHTML = options.map(([key, label]) =>
				`<button class="ap-onboarding-interest" data-value="${key}" onclick="apollo.toggleInterest('${key}')">${label}</button>`
			).join('');

			return `
				<div class="ap-onboarding-interest-grid">${optionsHTML}</div>
				<button class="ap-onboarding-primary-btn" onclick="apollo.submitStep()">Continuar</button>
			`;
		}

		renderSelectInput(stepData) {
			const options = Object.entries(stepData.options || {});
			const optionsHTML = options.map(([key, label]) =>
				`<button class="ap-onboarding-choice" data-value="${key}" onclick="apollo.selectOption('${key}')">${label}</button>`
			).join('');

			return `
				<div class="ap-onboarding-btn-grid">${optionsHTML}</div>
				<button class="ap-onboarding-primary-btn" onclick="apollo.submitStep()">Continuar</button>
			`;
		}

		renderVerificationInput(stepData) {
			return `
				<div class="ap-onboarding-verification">
					<h5><i class="ri-shield-check-line"></i> Verificação</h5>
					<p style="font-size: var(--ap-text-sm); color: var(--ap-text-secondary);">
						Um código foi enviado para verificação.
					</p>
				</div>
				<div class="ap-form-group">
					<label class="ap-form-label">Código de Verificação</label>
					<input type="text" id="verificationCode" class="ap-form-input"
							placeholder="${stepData.input_placeholder}" maxlength="6"
							data-ap-tooltip="Digite o código recebido">
				</div>
				<button class="ap-onboarding-primary-btn" onclick="apollo.submitStep()">Verificar Código</button>
			`;
		}

		renderButtonInput(stepData) {
			const buttons = stepData.buttons || [];
			const buttonsHTML = buttons.map(button =>
				`<button class="ap-onboarding-choice" onclick="apollo.handleButtonClick('${button}')">${button}</button>`
			).join('');
			const gridClass = buttons.length <= 2 ? 'two-cols' : 'three-cols';

			return `<div class="ap-onboarding-btn-grid ${gridClass}">${buttonsHTML}</div>`;
		}

		attachEventListeners(stepData) {
			const inputs = document.querySelectorAll('input[type="text"]');
			inputs.forEach(input => {
				input.addEventListener('keypress', (e) => {
					if (e.key === 'Enter') this.submitStep();
				});
			});

			const firstInput = document.querySelector('input');
			if (firstInput) firstInput.focus();
		}

		toggleInterest(key) {
			const btn = document.querySelector(`[data-value="${key}"]`);
			if (this.selectedInterests.includes(key)) {
				this.selectedInterests = this.selectedInterests.filter(i => i !== key);
				btn.classList.remove('selected');
			} else {
				this.selectedInterests.push(key);
				btn.classList.add('selected');
			}
		}

		selectOption(key) {
			document.querySelectorAll('.ap-onboarding-choice').forEach(btn => btn.classList.remove('selected'));
			document.querySelector(`[data-value="${key}"]`).classList.add('selected');
			this.selectedOption = key;
		}

		handleButtonClick(buttonText) {
			if (buttonText.includes('começar') || buttonText.includes('Vamos')) {
				this.submitStep({ action: 'start' });
			} else if (buttonText.includes('Pular') || buttonText.includes('depois')) {
				this.skipStep();
			} else if (buttonText.includes('Dashboard')) {
				window.location.href = '/painel';
			} else {
				this.submitStep({ action: buttonText });
			}
		}

		async submitStep(customData = {}) {
			this.showLoading(true);

			let responseData = { ...customData };

			switch (this.stepData.input_type) {
				case 'text':
					const textInput = document.getElementById('textInput');
					responseData.value = textInput ? textInput.value.trim() : '';
					break;
				case 'location':
					const locationInput = document.getElementById('locationInput');
					responseData.location = locationInput ? locationInput.value.trim() : '';
					break;
				case 'multi_select':
					responseData.selected = this.selectedInterests;
					break;
				case 'select':
					responseData.visibility = this.selectedOption;
					break;
				case 'verification_code':
					const codeInput = document.getElementById('verificationCode');
					responseData.code = codeInput ? codeInput.value.trim() : '';
					break;
			}

			try {
				const response = await this.apiCall('apollo_process_onboarding_step', {
					session_id: this.sessionId,
					step: this.currentStep,
					response_data: responseData
				});

				this.showLoading(false);

				if (response.success) {
					if (response.data.completed) {
						this.showCompletion(response.data);
					} else if (response.data.error) {
						this.showError(response.data.error);
					} else {
						this.loadStep(response.data.step_data, response.data.progress);
					}
				} else {
					this.showError(response.data.error || 'Erro ao processar resposta');
				}
			} catch (error) {
				this.showLoading(false);
				this.showError('Erro de conexão');
			}
		}

		showCompletion(data) {
			const container = document.getElementById('chatMessages');
			const badgesHTML = (data.badges_earned || []).map(badge =>
				`<span class="ap-badge ap-badge-success"><i class="${badge.icon}"></i> ${badge.name}</span>`
			).join('');

			const completionDiv = document.createElement('div');
			completionDiv.className = 'ap-onboarding-message';
			completionDiv.innerHTML = `
				<div class="ap-onboarding-completion">
					<div class="ap-onboarding-completion-icon">🎉</div>
					<h3 class="ap-heading-3">Parabéns! Onboarding concluído!</h3>
					<p class="ap-text-muted">Seu perfil foi configurado com sucesso.</p>
					${badgesHTML ? `<div class="ap-onboarding-badges">${badgesHTML}</div>` : ''}
				</div>
			`;

			container.appendChild(completionDiv);
			container.scrollTop = container.scrollHeight;
			document.getElementById('inputSection').style.display = 'none';
			this.updateProgress(100);

			setTimeout(() => { window.location.href = '/painel'; }, 3000);
		}

		skipStep() {
			this.submitStep({ skipped: true });
		}

		async detectLocation() {
			if (navigator.geolocation) {
				navigator.geolocation.getCurrentPosition(
					(position) => {
						document.getElementById('locationInput').value = 'Localização detectada automaticamente';
					},
					() => { this.showError('Acesso à localização negado'); }
				);
			} else {
				this.showError('Geolocalização não suportada');
			}
		}

		updateProgress(progress) {
			document.getElementById('progressFill').style.width = progress + '%';
		}

		updateChatStatus() {
			document.getElementById('chatStatus').textContent = 'Online - Configurando seu perfil';
		}

		showLoading(show) {
			document.getElementById('loading').style.display = show ? 'block' : 'none';
			document.getElementById('inputSection').style.display = show ? 'none' : 'block';
		}

		showError(message) {
			const section = document.getElementById('inputSection');
			const existing = section.querySelector('.ap-onboarding-error');
			if (existing) existing.remove();

			const errorDiv = document.createElement('div');
			errorDiv.className = 'ap-onboarding-error';
			errorDiv.innerHTML = `<i class="ri-error-warning-line"></i> ${message}`;
			section.insertBefore(errorDiv, section.firstChild);

			setTimeout(() => { errorDiv.remove(); }, 5000);
		}

		async apiCall(action, data) {
			const response = await fetch('/wp-admin/admin-ajax.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: action,
					nonce: '<?php echo esc_js( $nonce ); ?>',
					...data
				})
			});
			return await response.json();
		}
	}

	const apollo = new ApolloOnboarding();
	</script>
</body>
</html>
