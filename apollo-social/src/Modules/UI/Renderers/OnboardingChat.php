<?php

namespace Apollo\Modules\UI\Renderers;

/**
 * Onboarding Chat Renderer
 *
 * Renders conversational onboarding experience.
 */
class OnboardingChat {

	private array $steps = array(
		1 => array(
			'question'    => 'Hey, qual seu nome?',
			'type'        => 'text',
			'placeholder' => 'Digite seu nome',
			'required'    => true,
			'field'       => 'name',
		),
		2 => array(
			'question' => 'Você é da indústria de Música/Eventos/Cultura Eletrônica no Rio?',
			'type'     => 'buttons',
			'options'  => array( 'Yes', 'No', 'Future yes!' ),
			'field'    => 'industry_member',
		),
		3 => array(
			'question'  => 'Em que frentes você atua? (múltipla escolha)',
			'type'      => 'multi_select',
			'options'   => array(
				'DJ',
				'PRODUCER',
				'CULTURAL PRODUCER',
				'MUSIC PRODUCER',
				'PHOTOGRAPHER',
				'VISUALS & DIGITAL ART',
				'BAR TEAM',
				'FINANCE TEAM',
				'GOVERNMENT',
				'BUSINESS PERSON',
				'HOSTESS',
				'PROMOTER',
				'INFLUENCER',
			),
			'field'     => 'industry_roles',
			'condition' => 'industry_member:Yes,Future yes!',
		),
		4 => array(
			'question'                  => 'É membro de algum Núcleo / Club / DJ Bar?',
			'type'                      => 'multi_select',
			'options'                   => array(),
			// Will be loaded dynamically
								'field' => 'member_of',
			'condition'                 => 'industry_member:Yes,Future yes!',
		),
		5 => array(
			'question'    => 'Qual seu WhatsApp?',
			'type'        => 'phone',
			'placeholder' => '(11) 99999-9999',
			'required'    => true,
			'field'       => 'whatsapp',
		),
		6 => array(
			'question'    => 'Qual seu Instagram?',
			'type'        => 'instagram',
			'placeholder' => '@seuusuario',
			'required'    => true,
			'field'       => 'instagram',
		),
		7 => array(
			'question' => 'Perfeito! Vamos verificar seu Instagram.',
			'type'     => 'verification',
			'field'    => 'verification',
		),
	);

	/**
	 * Render onboarding chat
	 */
	public function render(): void {
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Bem-vindo ao Apollo Social</title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 20px;
				}

				.chat-container {
					background: white;
					border-radius: 20px;
					box-shadow: 0 20px 60px rgba(0,0,0,0.15);
					max-width: 480px;
					width: 100%;
					height: 600px;
					display: flex;
					flex-direction: column;
					overflow: hidden;
				}

				.chat-header {
					background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
					color: white;
					padding: 20px;
					display: flex;
					align-items: center;
					gap: 15px;
				}

				.apollo-avatar {
					width: 50px;
					height: 50px;
					border-radius: 50%;
					background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
					display: flex;
					align-items: center;
					justify-content: center;
					font-size: 24px;
					border: 3px solid rgba(255,255,255,0.3);
				}

				.chat-info h2 {
					font-size: 18px;
					margin-bottom: 4px;
				}

				.chat-status {
					font-size: 14px;
					opacity: 0.9;
				}

				.progress-bar {
					height: 4px;
					background: rgba(255,255,255,0.2);
					overflow: hidden;
				}

				.progress-fill {
					height: 100%;
					background: #10b981;
					transition: width 0.5s ease;
					width: 0%;
				}

				.chat-messages {
					flex: 1;
					padding: 20px;
					overflow-y: auto;
					background: #f8fafc;
					display: flex;
					flex-direction: column;
					gap: 20px;
				}

				.message {
					display: flex;
					flex-direction: column;
					animation: fadeInUp 0.6s ease;
				}

				.message-bubble {
					background: white;
					border-radius: 18px 18px 18px 4px;
					padding: 16px 20px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					max-width: 85%;
					position: relative;
				}

				.message-avatar {
					width: 32px;
					height: 32px;
					border-radius: 50%;
					background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
					display: flex;
					align-items: center;
					justify-content: center;
					color: white;
					font-size: 16px;
					margin-bottom: 8px;
				}

				.message-text {
					color: #374151;
					line-height: 1.5;
					font-size: 16px;
				}

				.typing-indicator {
					display: none;
					align-items: center;
					gap: 8px;
					padding: 16px 20px;
					background: white;
					border-radius: 18px 18px 18px 4px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					max-width: 85%;
					margin-top: 8px;
				}

				.typing-dots {
					display: flex;
					gap: 4px;
				}

				.typing-dot {
					width: 8px;
					height: 8px;
					border-radius: 50%;
					background: #9ca3af;
					animation: typingPulse 1.4s infinite;
				}

				.typing-dot:nth-child(2) {
					animation-delay: 0.2s;
				}

				.typing-dot:nth-child(3) {
					animation-delay: 0.4s;
				}

				@keyframes typingPulse {
					0%, 60%, 100% {
						opacity: 0.3;
						transform: scale(1);
					}
					30% {
						opacity: 1;
						transform: scale(1.2);
					}
				}

				.input-section {
					background: white;
					border-top: 1px solid #e2e8f0;
					padding: 20px;
				}

				.input-group {
					margin-bottom: 15px;
				}

				.input-group input {
					width: 100%;
					padding: 12px 16px;
					border: 2px solid #e5e7eb;
					border-radius: 12px;
					font-size: 16px;
					transition: border-color 0.3s;
				}

				.input-group input:focus {
					outline: none;
					border-color: #4f46e5;
					box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
				}

				.options-grid {
					display: grid;
					gap: 10px;
					margin-bottom: 15px;
				}

				.options-grid.single {
					grid-template-columns: 1fr;
				}

				.options-grid.double {
					grid-template-columns: repeat(2, 1fr);
				}

				.options-grid.triple {
					grid-template-columns: repeat(3, 1fr);
				}

				.option-btn {
					padding: 12px 16px;
					border: 2px solid #e5e7eb;
					border-radius: 12px;
					background: white;
					color: #374151;
					font-size: 14px;
					font-weight: 500;
					cursor: pointer;
					transition: all 0.3s;
					text-align: center;
				}

				.option-btn:hover {
					border-color: #4f46e5;
					background: #f0f0ff;
				}

				.option-btn.selected {
					border-color: #4f46e5;
					background: #4f46e5;
					color: white;
				}

				.continue-btn {
					width: 100%;
					padding: 14px;
					background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
					color: white;
					border: none;
					border-radius: 12px;
					font-size: 16px;
					font-weight: 600;
					cursor: pointer;
					transition: all 0.3s;
				}

				.continue-btn:hover {
					transform: translateY(-2px);
					box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
				}

				.continue-btn:disabled {
					opacity: 0.6;
					cursor: not-allowed;
					transform: none;
					box-shadow: none;
				}

				.verification-section {
					background: #f0f9ff;
					border: 1px solid #0ea5e9;
					border-radius: 12px;
					padding: 16px;
					margin-bottom: 15px;
				}

				.verification-section h4 {
					color: #0ea5e9;
					margin-bottom: 10px;
					font-size: 16px;
				}

				.verification-token {
					background: white;
					border: 2px solid #0ea5e9;
					border-radius: 8px;
					padding: 12px;
					text-align: center;
					font-family: monospace;
					font-size: 18px;
					font-weight: bold;
					color: #0369a1;
					margin: 10px 0;
				}

				.verification-instructions {
					color: #0369a1;
					font-size: 14px;
					line-height: 1.5;
				}

				.cena-rio-notice {
					background: #fef3c7;
					border: 1px solid #f59e0b;
					border-radius: 12px;
					padding: 16px;
					margin: 15px 0;
				}

				.cena-rio-notice h4 {
					color: #d97706;
					margin-bottom: 8px;
					display: flex;
					align-items: center;
					gap: 8px;
				}

				.cena-rio-notice p {
					color: #92400e;
					font-size: 14px;
				}

				@keyframes fadeInUp {
					from {
						opacity: 0;
						transform: translateY(20px);
					}
					to {
						opacity: 1;
						transform: translateY(0);
					}
				}

				@media (max-width: 480px) {
					.chat-container {
						height: 100vh;
						border-radius: 0;
						max-width: none;
					}
					
					.options-grid.triple {
						grid-template-columns: 1fr;
					}
					
					.options-grid.double {
						grid-template-columns: 1fr;
					}
				}
			</style>
		</head>
		<body>
			<div class="chat-container">
				<!-- Chat Header -->
				<div class="chat-header">
					<div class="apollo-avatar">🚀</div>
					<div class="chat-info">
						<h2>Apollo Assistant</h2>
						<div class="chat-status" id="chatStatus">Iniciando conversa...</div>
					</div>
				</div>
				
				<!-- Progress Bar -->
				<div class="progress-bar">
					<div class="progress-fill" id="progressFill"></div>
				</div>

				<!-- Chat Messages -->
				<div class="chat-messages" id="chatMessages">
					<!-- Messages will be added here dynamically -->
				</div>

				<!-- Input Section -->
				<div class="input-section" id="inputSection">
					<!-- Input will be rendered here dynamically -->
				</div>
			</div>

			<script>
				class ApolloOnboarding {
					constructor() {
						this.currentStep = 1;
						this.userData = {};
						this.verificationToken = null;
						this.init();
					}

					init() {
						this.showMessage("Olá! 👋 Bem-vindo ao Apollo Social! Vou te ajudar a configurar seu perfil em alguns passos rápidos.");
						
						setTimeout(() => {
							this.loadStep(1);
						}, 1500);
					}

					async showMessage(text, delay = 0) {
						if (delay > 0) {
							await this.showTyping();
							await this.sleep(delay);
							this.hideTyping();
						}

						const messagesContainer = document.getElementById('chatMessages');
						
						const messageDiv = document.createElement('div');
						messageDiv.className = 'message';
						
						messageDiv.innerHTML = `
							<div class="message-avatar">🤖</div>
							<div class="message-bubble">
								<div class="message-text">${text}</div>
							</div>
						`;
						
						messagesContainer.appendChild(messageDiv);
						messagesContainer.scrollTop = messagesContainer.scrollHeight;
					}

					showTyping() {
						const messagesContainer = document.getElementById('chatMessages');
						
						const typingDiv = document.createElement('div');
						typingDiv.className = 'typing-indicator';
						typingDiv.id = 'typingIndicator';
						
						typingDiv.innerHTML = `
							<div style="color: #9ca3af; font-size: 14px;">Apollo está digitando</div>
							<div class="typing-dots">
								<div class="typing-dot"></div>
								<div class="typing-dot"></div>
								<div class="typing-dot"></div>
							</div>
						`;
						
						messagesContainer.appendChild(typingDiv);
						typingDiv.style.display = 'flex';
						messagesContainer.scrollTop = messagesContainer.scrollHeight;
					}

					hideTyping() {
						const typingIndicator = document.getElementById('typingIndicator');
						if (typingIndicator) {
							typingIndicator.remove();
						}
					}

					sleep(ms) {
						return new Promise(resolve => setTimeout(resolve, ms));
					}

					async loadStep(stepNumber) {
						this.currentStep = stepNumber;
						
						const step = this.getStepConfig(stepNumber);
						if (!step) {
							this.completeOnboarding();
							return;
						}

						// Check conditions
						if (step.condition && !this.checkCondition(step.condition)) {
							this.loadStep(stepNumber + 1);
							return;
						}

						// Update progress
						this.updateProgress();
						
						// Show question with typing effect
						await this.showMessage(step.question, 1500);
						
						// Show special notices
						if (stepNumber === 4) {
							this.showCenaRioNotice();
						}
						
						// Render input
						this.renderInput(step);
					}

					getStepConfig(stepNumber) {
						const steps = <?php echo json_encode( $this->steps ); ?>;
						return steps[stepNumber] || null;
					}

					checkCondition(condition) {
						const [field, values] = condition.split(':');
						const allowedValues = values.split(',');
						return allowedValues.includes(this.userData[field]);
					}

					updateProgress() {
						const totalSteps = Object.keys(this.getStepConfig(1) ? <?php echo json_encode( $this->steps ); ?> : {}).length;
						const progress = (this.currentStep / totalSteps) * 100;
						
						document.getElementById('progressFill').style.width = progress + '%';
						document.getElementById('chatStatus').textContent = `Passo ${this.currentStep} de ${totalSteps}`;
					}

					renderInput(step) {
						const inputSection = document.getElementById('inputSection');
						let html = '';

						switch (step.type) {
							case 'text':
								html = this.renderTextInput(step);
								break;
							case 'phone':
								html = this.renderPhoneInput(step);
								break;
							case 'instagram':
								html = this.renderInstagramInput(step);
								break;
							case 'buttons':
								html = this.renderButtonsInput(step);
								break;
							case 'multi_select':
								html = this.renderMultiSelectInput(step);
								break;
							case 'verification':
								html = this.renderVerificationInput(step);
								break;
						}

						inputSection.innerHTML = html;
						this.attachEventListeners(step);
					}

					renderTextInput(step) {
						return `
							<div class="input-group">
								<input type="text" id="stepInput" placeholder="${step.placeholder}" maxlength="100">
							</div>
							<button class="continue-btn" onclick="apollo.nextStep()">Continuar</button>
						`;
					}

					renderPhoneInput(step) {
						return `
							<div class="input-group">
								<input type="tel" id="stepInput" placeholder="${step.placeholder}" 
										pattern="\\([0-9]{2}\\) [0-9]{4,5}-[0-9]{4}">
							</div>
							<button class="continue-btn" onclick="apollo.nextStep()">Continuar</button>
						`;
					}

					renderInstagramInput(step) {
						return `
							<div class="input-group">
								<input type="text" id="stepInput" placeholder="${step.placeholder}" 
										pattern="@[a-zA-Z0-9_.]+">
							</div>
							<button class="continue-btn" onclick="apollo.nextStep()">Continuar</button>
						`;
					}

					renderButtonsInput(step) {
						const gridClass = step.options.length <= 2 ? 'double' : 'triple';
						const optionsHtml = step.options.map(option => 
							`<button class="option-btn" data-value="${option}" onclick="apollo.selectOption('${option}')">${option}</button>`
						).join('');

						return `
							<div class="options-grid ${gridClass}">
								${optionsHtml}
							</div>
						`;
					}

					renderMultiSelectInput(step) {
						let options = step.options;
						
						// Load dynamic options for step 4 (locations)
						if (step.field === 'member_of') {
							options = this.getLocationOptions();
						}

						const optionsHtml = options.map(option => 
							`<button class="option-btn" data-value="${option}" onclick="apollo.toggleOption('${option}')">${option}</button>`
						).join('');

						return `
							<div class="options-grid single">
								${optionsHtml}
							</div>
							<button class="continue-btn" onclick="apollo.nextStep()">Continuar</button>
						`;
					}

					renderVerificationInput(step) {
						// Generate verification token
						this.generateVerificationToken();

						return `
							<div class="verification-section">
								<h4>🔐 Verificação Instagram</h4>
								<p>Para verificar sua conta, poste um story ou comentário com:</p>
								<div class="verification-token">eu sou ${this.userData.instagram} no apollo :: ${this.verificationToken}</div>
								<div class="verification-instructions">
									📱 Após postar, aguarde a verificação manual pela equipe Apollo.
								</div>
							</div>
							<button class="continue-btn" onclick="apollo.submitForVerification()">Enviar para Verificação</button>
						`;
					}

					selectOption(value) {
						// Clear previous selections for single select
						document.querySelectorAll('.option-btn').forEach(btn => {
							btn.classList.remove('selected');
						});
						
						// Select current option
						event.target.classList.add('selected');
						
						// Auto-advance for single select
						setTimeout(() => {
							const step = this.getStepConfig(this.currentStep);
							this.userData[step.field] = value;
							this.loadStep(this.currentStep + 1);
						}, 500);
					}

					toggleOption(value) {
						const btn = event.target;
						const step = this.getStepConfig(this.currentStep);
						
						if (!this.userData[step.field]) {
							this.userData[step.field] = [];
						}
						
						if (this.userData[step.field].includes(value)) {
							this.userData[step.field] = this.userData[step.field].filter(v => v !== value);
							btn.classList.remove('selected');
						} else {
							this.userData[step.field].push(value);
							btn.classList.add('selected');
						}
					}

					nextStep() {
						const step = this.getStepConfig(this.currentStep);
						const input = document.getElementById('stepInput');
						
						if (input) {
							let value = input.value.trim();
							
							if (step.required && !value) {
								alert('Este campo é obrigatório');
								return;
							}
							
							// Apply masks and validation
							if (step.type === 'phone') {
								value = this.formatPhone(value);
							} else if (step.type === 'instagram') {
								value = this.formatInstagram(value);
							}
							
							this.userData[step.field] = value;
						}
						
						this.loadStep(this.currentStep + 1);
					}

					formatPhone(value) {
						const digits = value.replace(/\D/g, '');
						if (digits.length >= 11) {
							return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
						}
						return value;
					}

					formatInstagram(value) {
						if (value && !value.startsWith('@')) {
							return '@' + value;
						}
						return value;
					}

					generateVerificationToken() {
						const today = new Date();
						const dateStr = today.getFullYear().toString() + 
										(today.getMonth() + 1).toString().padStart(2, '0') + 
										today.getDate().toString().padStart(2, '0');
						
						const username = (this.userData.instagram || '').replace('@', '').toLowerCase();
						this.verificationToken = dateStr + username;
					}

					getLocationOptions() {
						// This would be loaded dynamically from Apollo Events Manager + Núcleos
						return [
							'Circo Voador',
							'Marina da Glória',
							'Jockey Club',
							'Praia de Copacabana',
							'Lapa',
							'Santa Teresa',
							'Botafogo',
							'Ipanema',
							'Outro local'
						];
					}

					showCenaRioNotice() {
						const messagesContainer = document.getElementById('chatMessages');
						
						const noticeDiv = document.createElement('div');
						noticeDiv.className = 'cena-rio-notice';
						
						noticeDiv.innerHTML = `
							<h4>🌟 CENA::RIO</h4>
							<p>Terá utilidades especiais para membros da cena carioca. Siga o IG @apollo.rio para saber quando liberar para servir todos.</p>
						`;
						
						messagesContainer.appendChild(noticeDiv);
						messagesContainer.scrollTop = messagesContainer.scrollHeight;
					}

					async submitForVerification() {
						try {
							const response = await fetch('/wp-admin/admin-ajax.php', {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: new URLSearchParams({
									action: 'apollo_submit_onboarding',
									user_data: JSON.stringify(this.userData),
									verification_token: this.verificationToken,
									nonce: '<?php echo \wp_create_nonce( 'apollo_onboarding' ); ?>'
								})
							});

							const result = await response.json();

							if (result.success) {
								this.completeOnboarding();
							} else {
								alert('Erro: ' + (result.data || 'Erro desconhecido'));
							}

						} catch (error) {
							alert('Erro de conexão: ' + error.message);
						}
					}

					async completeOnboarding() {
						await this.showMessage("🎉 Perfeito! Seu perfil foi criado com sucesso.", 1000);
						await this.showMessage("Você está agora em estado 'awaiting_instagram_verify'. Nossa equipe verificará sua conta e você receberá uma notificação.", 2000);
						await this.showMessage("Enquanto isso, pode explorar a plataforma! Bem-vindo ao Apollo! 🚀", 1500);
						
						setTimeout(() => {
							window.location.href = '/apollo/dashboard';
						}, 3000);
					}

					attachEventListeners(step) {
						const input = document.getElementById('stepInput');
						if (input) {
							input.addEventListener('keypress', (e) => {
								if (e.key === 'Enter') {
									this.nextStep();
								}
							});
							
							// Auto-format inputs
							if (step.type === 'phone') {
								input.addEventListener('input', (e) => {
									const value = e.target.value.replace(/\D/g, '');
									if (value.length >= 11) {
										e.target.value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
									} else if (value.length >= 7) {
										e.target.value = value.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
									} else if (value.length >= 3) {
										e.target.value = value.replace(/(\d{2})(\d+)/, '($1) $2');
									}
								});
							} else if (step.type === 'instagram') {
								input.addEventListener('input', (e) => {
									let value = e.target.value;
									if (value && !value.startsWith('@')) {
										e.target.value = '@' + value;
									}
								});
							}
							
							input.focus();
						}
					}
				}

				// Initialize
				const apollo = new ApolloOnboarding();
			</script>
		</body>
		</html>
		<?php
	}
}
