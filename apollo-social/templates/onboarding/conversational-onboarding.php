<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao Apollo Social - Onboarding</title>
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

        .onboarding-container {
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
        }

        .message {
            margin-bottom: 20px;
            animation: fadeInUp 0.4s ease;
        }

        .message-bubble {
            background: white;
            border-radius: 18px 18px 18px 4px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 85%;
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

        .message-content h4 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .message-text {
            color: #475569;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .input-section {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 20px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .button-grid {
            display: grid;
            gap: 10px;
            margin-bottom: 15px;
        }

        .button-grid.two-cols {
            grid-template-columns: 1fr 1fr;
        }

        .button-grid.three-cols {
            grid-template-columns: repeat(3, 1fr);
        }

        .choice-btn {
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

        .choice-btn:hover {
            border-color: #4f46e5;
            background: #f0f0ff;
        }

        .choice-btn.selected {
            border-color: #4f46e5;
            background: #4f46e5;
            color: white;
        }

        .interest-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }

        .interest-btn {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            color: #374151;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .interest-btn:hover {
            border-color: #4f46e5;
            background: #f0f0ff;
        }

        .interest-btn.selected {
            border-color: #10b981;
            background: #10b981;
            color: white;
        }

        .primary-btn {
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
            margin-top: 10px;
        }

        .primary-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }

        .primary-btn:disabled {
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

        .verification-section h5 {
            color: #0ea5e9;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .verification-instructions {
            color: #0369a1;
            font-size: 13px;
            line-height: 1.4;
        }

        .verification-instructions ol {
            margin: 10px 0 10px 20px;
        }

        .verification-instructions li {
            margin-bottom: 4px;
        }

        .qr-code {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 8px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border: 2px solid #e5e7eb;
        }

        .platform-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .platform-link {
            flex: 1;
            padding: 8px;
            background: #0ea5e9;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }

        .platform-link:hover {
            background: #0284c7;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .completion-section {
            text-align: center;
            padding: 20px;
        }

        .completion-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .badges-earned {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #6b7280;
        }

        .loading .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            .onboarding-container {
                height: 100vh;
                border-radius: 0;
                max-width: none;
            }
            
            .interest-grid {
                grid-template-columns: 1fr;
            }
            
            .button-grid.three-cols {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
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
            <!-- Messages will be loaded here dynamically -->
        </div>

        <!-- Input Section -->
        <div class="input-section" id="inputSection">
            <!-- Input will be loaded here dynamically -->
        </div>

        <!-- Loading -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
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
                this.selectedPlatform = null;
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
                const messagesContainer = document.getElementById('chatMessages');
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message';
                
                messageDiv.innerHTML = `
                    <div class="message-avatar">🤖</div>
                    <div class="message-bubble">
                        <div class="message-content">
                            <h4>${stepData.title}</h4>
                            <div class="message-text">${stepData.message}</div>
                        </div>
                    </div>
                `;
                
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            renderInput(stepData) {
                const inputSection = document.getElementById('inputSection');
                let inputHTML = '';

                switch (stepData.input_type) {
                    case 'text':
                        inputHTML = this.renderTextInput(stepData);
                        break;
                    case 'location':
                        inputHTML = this.renderLocationInput(stepData);
                        break;
                    case 'multi_select':
                        inputHTML = this.renderMultiSelectInput(stepData);
                        break;
                    case 'select':
                        inputHTML = this.renderSelectInput(stepData);
                        break;
                    case 'verification_code':
                        inputHTML = this.renderVerificationInput(stepData);
                        break;
                    default:
                        inputHTML = this.renderButtonInput(stepData);
                }

                inputSection.innerHTML = inputHTML;
                this.attachEventListeners(stepData);
            }

            renderTextInput(stepData) {
                return `
                    <div class="input-group">
                        <input type="text" id="textInput" placeholder="${stepData.input_placeholder}" maxlength="100">
                    </div>
                    <button class="primary-btn" onclick="apollo.submitStep()">Continuar</button>
                `;
            }

            renderLocationInput(stepData) {
                return `
                    <div class="input-group">
                        <input type="text" id="locationInput" placeholder="${stepData.input_placeholder}">
                    </div>
                    <div class="button-grid two-cols">
                        <button class="choice-btn" onclick="apollo.detectLocation()">🌍 Detectar</button>
                        <button class="choice-btn" onclick="apollo.skipStep()">⏭️ Pular</button>
                    </div>
                    <button class="primary-btn" onclick="apollo.submitStep()">Continuar</button>
                `;
            }

            renderMultiSelectInput(stepData) {
                const options = Object.entries(stepData.options || {});
                const optionsHTML = options.map(([key, label]) => 
                    `<button class="interest-btn" data-value="${key}" onclick="apollo.toggleInterest('${key}')">${label}</button>`
                ).join('');

                return `
                    <div class="interest-grid">
                        ${optionsHTML}
                    </div>
                    <button class="primary-btn" onclick="apollo.submitStep()">Continuar</button>
                `;
            }

            renderSelectInput(stepData) {
                const options = Object.entries(stepData.options || {});
                const optionsHTML = options.map(([key, label]) => 
                    `<button class="choice-btn" data-value="${key}" onclick="apollo.selectOption('${key}')">${label}</button>`
                ).join('');

                return `
                    <div class="button-grid">
                        ${optionsHTML}
                    </div>
                    <button class="primary-btn" onclick="apollo.submitStep()">Continuar</button>
                `;
            }

            renderVerificationInput(stepData) {
                return `
                    <div class="verification-section" id="verificationSection">
                        <!-- Verification instructions will be loaded here -->
                    </div>
                    <div class="input-group">
                        <label>Código de Verificação</label>
                        <input type="text" id="verificationCode" placeholder="${stepData.input_placeholder}" maxlength="6">
                    </div>
                    <button class="primary-btn" onclick="apollo.submitStep()">Verificar Código</button>
                `;
            }

            renderButtonInput(stepData) {
                const buttons = stepData.buttons || [];
                const buttonsHTML = buttons.map(button => 
                    `<button class="choice-btn" onclick="apollo.handleButtonClick('${button}')">${button}</button>`
                ).join('');

                const gridClass = buttons.length <= 2 ? 'two-cols' : 'three-cols';

                return `
                    <div class="button-grid ${gridClass}">
                        ${buttonsHTML}
                    </div>
                `;
            }

            attachEventListeners(stepData) {
                // Enter key listener for text inputs
                const textInputs = document.querySelectorAll('input[type="text"]');
                textInputs.forEach(input => {
                    input.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            this.submitStep();
                        }
                    });
                });

                // Auto-focus first input
                const firstInput = document.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            }

            toggleInterest(interestKey) {
                const btn = document.querySelector(`[data-value="${interestKey}"]`);
                
                if (this.selectedInterests.includes(interestKey)) {
                    this.selectedInterests = this.selectedInterests.filter(i => i !== interestKey);
                    btn.classList.remove('selected');
                } else {
                    this.selectedInterests.push(interestKey);
                    btn.classList.add('selected');
                }
            }

            selectOption(optionKey) {
                // Clear previous selections
                document.querySelectorAll('.choice-btn').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // Select current option
                document.querySelector(`[data-value="${optionKey}"]`).classList.add('selected');
                this.selectedOption = optionKey;
            }

            handleButtonClick(buttonText) {
                if (buttonText.includes('começar') || buttonText.includes('Vamos')) {
                    this.submitStep({ action: 'start' });
                } else if (buttonText.includes('WhatsApp')) {
                    this.selectedPlatform = 'whatsapp';
                    this.submitStep({ platform: 'whatsapp' });
                } else if (buttonText.includes('Instagram')) {
                    this.selectedPlatform = 'instagram';
                    this.submitStep({ platform: 'instagram' });
                } else if (buttonText.includes('Twitter')) {
                    this.selectedPlatform = 'twitter';
                    this.submitStep({ platform: 'twitter' });
                } else if (buttonText.includes('Pular') || buttonText.includes('depois')) {
                    this.skipStep();
                } else if (buttonText.includes('Dashboard')) {
                    window.location.href = '/apollo/dashboard';
                } else if (buttonText.includes('Grupos')) {
                    window.location.href = '/apollo/groups';
                } else if (buttonText.includes('Eventos')) {
                    window.location.href = '/apollo/events';
                } else {
                    this.submitStep({ action: buttonText });
                }
            }

            async submitStep(customData = {}) {
                this.showLoading(true);

                let responseData = { ...customData };

                // Collect input data based on step type
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
                            this.currentStep = response.data.step_data.input_type ? 'custom' : Object.keys(this.stepData)[0];
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
                const messagesContainer = document.getElementById('chatMessages');
                const badgesHTML = (data.badges_earned || []).map(badge => 
                    `<div class="badge">${badge.icon} ${badge.name}</div>`
                ).join('');

                const completionDiv = document.createElement('div');
                completionDiv.className = 'message';
                completionDiv.innerHTML = `
                    <div class="completion-section">
                        <div class="completion-icon">🎉</div>
                        <h3>Parabéns! Onboarding concluído!</h3>
                        <p>Seu perfil foi configurado com sucesso.</p>
                        ${badgesHTML ? `<div class="badges-earned">${badgesHTML}</div>` : ''}
                    </div>
                `;

                messagesContainer.appendChild(completionDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                // Hide input section
                document.getElementById('inputSection').style.display = 'none';
                
                // Update progress to 100%
                this.updateProgress(100);
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = '/apollo/dashboard';
                }, 3000);
            }

            skipStep() {
                this.submitStep({ skipped: true });
            }

            async detectLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            try {
                                // Simple location detection (you can enhance this)
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                
                                // Set a generic location for now
                                document.getElementById('locationInput').value = 'Localização detectada automaticamente';
                            } catch (error) {
                                this.showError('Erro ao detectar localização');
                            }
                        },
                        () => {
                            this.showError('Acesso à localização negado');
                        }
                    );
                } else {
                    this.showError('Geolocalização não suportada');
                }
            }

            updateProgress(progress) {
                const progressFill = document.getElementById('progressFill');
                progressFill.style.width = progress + '%';
            }

            updateChatStatus() {
                const chatStatus = document.getElementById('chatStatus');
                chatStatus.textContent = 'Online - Configurando seu perfil';
            }

            showLoading(show) {
                const loading = document.getElementById('loading');
                const inputSection = document.getElementById('inputSection');
                
                if (show) {
                    loading.style.display = 'block';
                    inputSection.style.display = 'none';
                } else {
                    loading.style.display = 'none';
                    inputSection.style.display = 'block';
                }
            }

            showError(message) {
                const inputSection = document.getElementById('inputSection');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = message;
                
                // Remove existing errors
                const existingError = inputSection.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                inputSection.insertBefore(errorDiv, inputSection.firstChild);
                
                // Remove error after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }

            async apiCall(action, data) {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: action,
                        nonce: '<?php echo wp_create_nonce("apollo_onboarding"); ?>',
                        ...data
                    })
                });
                
                return await response.json();
            }
        }

        // Initialize onboarding
        const apollo = new ApolloOnboarding();
    </script>
</body>
</html>