/**
 * Apollo Onboarding Chat JavaScript
 * Conversational onboarding based on CodePen design
 * Features: typing animation, step flow, validation, token generation
 */

class ApolloOnboardingChat {
    constructor() {
        this.data = this.loadInitialData();
        this.config = this.data.config;
        this.progress = this.data.progress || {};
        this.currentStep = this.data.currentStep || 'ask_name';
        this.userId = this.data.userId;
        this.nonce = this.data.nonce;
        
        this.steps = this.defineSteps();
        this.responses = {};
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startOnboarding();
    }
    
    loadInitialData() {
        const dataScript = document.getElementById('onboardingData');
        if (dataScript) {
            try {
                return JSON.parse(dataScript.textContent);
            } catch (e) {
                console.error('Error parsing onboarding data:', e);
            }
        }
        return {};
    }
    
    defineSteps() {
        return {
            ask_name: {
                question: 'Qual é o seu nome?',
                type: 'text_input',
                validation: { min_length: 2 },
                field: 'name'
            },
            ask_industry: {
                question: 'Você trabalha na indústria da música/eventos?',
                type: 'single_choice',
                options: ['Yes', 'No', 'Future yes!'],
                field: 'industry'
            },
            ask_roles: {
                question: 'Quais são seus roles? (pode escolher vários)',
                type: 'multi_choice',
                condition: (responses) => ['Yes', 'Future yes!'].includes(responses.industry),
                options: [
                    'DJ', 'PRODUCER', 'CULTURAL PRODUCER', 'MUSIC PRODUCER',
                    'PHOTOGRAPHER', 'VISUALS & DIGITAL ART', 'BAR TEAM',
                    'FINANCE TEAM', 'GOVERNMENT', 'BUSINESS PERSON',
                    'HOSTESS', 'PROMOTER', 'INFLUENCER'
                ],
                field: 'roles'
            },
            ask_memberships: {
                question: 'De quais locais/núcleos você faz parte?',
                type: 'dynamic_multi_choice',
                field: 'member_of'
            },
            ask_contacts: {
                question: 'Como podemos te contactar?',
                type: 'contact_form',
                fields: ['whatsapp', 'instagram'],
                validation: {
                    whatsapp: { required: true, mask: '+55 (##) #####-####' },
                    instagram: { required: true, regex: /^@?[a-zA-Z0-9._]{1,30}$/ }
                }
            },
            verification_rules: {
                question: 'Verificação Instagram',
                type: 'verification_info',
                field: 'verification'
            },
            summary_submit: {
                question: 'Confirmar dados',
                type: 'summary',
                field: 'submit'
            }
        };
    }
    
    setupEventListeners() {
        // Global click handler for chips and buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('chip')) {
                this.handleChipClick(e.target);
            } else if (e.target.classList.contains('send-button')) {
                this.handleSendClick();
            } else if (e.target.classList.contains('continue-button')) {
                this.nextStep();
            }
        });
        
        // Enter key for text inputs
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && e.target.classList.contains('text-input')) {
                this.handleSendClick();
            }
        });
    }
    
    async startOnboarding() {
        // Show welcome message then start first step
        await this.delay(500);
        this.showStep(this.currentStep);
    }
    
    async showStep(stepName) {
        const step = this.steps[stepName];
        if (!step) return;
        
        // Check condition if exists
        if (step.condition && !step.condition(this.responses)) {
            this.nextStep();
            return;
        }
        
        // Show typing indicator
        this.showTyping();
        await this.delay(this.config.messages?.typing_delay || 1500);
        this.hideTyping();
        
        // Add bot message
        this.addBotMessage(step.question);
        
        // Generate input based on step type
        await this.delay(300);
        this.generateInput(step);
        
        // Update progress
        this.updateProgress(stepName);
    }
    
    generateInput(step) {
        const inputArea = document.getElementById('chatInputArea');
        let html = '';
        
        switch (step.type) {
            case 'text_input':
                html = this.generateTextInput(step);
                break;
            case 'single_choice':
                html = this.generateChoiceChips(step.options, false);
                break;
            case 'multi_choice':
                html = this.generateChoiceChips(step.options, true);
                break;
            case 'dynamic_multi_choice':
                html = this.generateDynamicChoices(step);
                break;
            case 'contact_form':
                html = this.generateContactForm(step);
                break;
            case 'verification_info':
                html = this.generateVerificationInfo();
                break;
            case 'summary':
                html = this.generateSummary();
                break;
        }
        
        inputArea.innerHTML = html;
        
        // Focus first input
        const firstInput = inputArea.querySelector('input, .chip');
        if (firstInput) {
            firstInput.focus();
        }
        
        // Apply masks if needed
        this.applyInputMasks();
    }
    
    generateTextInput(step) {
        return `
            <div class="input-group">
                <input type="text" 
                       class="text-input" 
                       placeholder="${step.placeholder || 'Digite sua resposta...'}"
                       data-field="${step.field}"
                       required="${step.validation?.required || false}">
                <button type="button" class="send-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        `;
    }
    
    generateChoiceChips(options, multiSelect = false) {
        const chipsHtml = options.map(option => 
            `<div class="chip ${multiSelect ? 'multi-select' : ''}" 
                  data-value="${option}" 
                  data-multi="${multiSelect}"
                  role="button" 
                  tabindex="0">
                ${option}
             </div>`
        ).join('');
        
        return `
            <div class="choice-chips">
                ${chipsHtml}
            </div>
            ${multiSelect ? '<button type="button" class="continue-button" style="margin-top: 12px; opacity: 0.5;" disabled>Continuar</button>' : ''}
        `;
    }
    
    async generateDynamicChoices(step) {
        // Load options from API
        const options = await this.loadMembershipOptions();
        
        let html = '<div class="choice-chips">';
        
        if (options.nucleos?.length) {
            html += '<div class="chip-category">Núcleos:</div>';
            options.nucleos.forEach(nucleo => {
                html += `<div class="chip multi-select" data-value="nucleo:${nucleo.id}" data-multi="true">${nucleo.title}</div>`;
            });
        }
        
        if (options.locais?.length) {
            html += '<div class="chip-category">Locais:</div>';
            options.locais.forEach(local => {
                html += `<div class="chip multi-select" data-value="local:${local.id}" data-multi="true">${local.title}</div>`;
            });
        }
        
        html += '</div>';
        html += '<button type="button" class="continue-button" style="margin-top: 12px;">Continuar</button>';
        
        return html;
    }
    
    generateContactForm(step) {
        return `
            <div class="contact-form">
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="tel" 
                           class="form-input whatsapp-input" 
                           placeholder="+55 (11) 99999-9999"
                           data-field="whatsapp"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="text" 
                           class="form-input instagram-input" 
                           placeholder="@seuusuario"
                           data-field="instagram"
                           required>
                </div>
                <button type="button" class="continue-button" style="margin-top: 12px;">Continuar</button>
            </div>
        `;
    }
    
    generateVerificationInfo() {
        const instagram = this.normalizeInstagram(this.responses.instagram);
        const token = this.buildVerifyToken(instagram);
        
        return `
            <div class="verification-info">
                <div class="verification-card">
                    <h4>Verificação Instagram</h4>
                    <p>Para verificar sua conta, poste um story ou envie um screenshot com:</p>
                    <div class="verification-text">
                        <strong>eu sou @${instagram} no apollo :: ${token}</strong>
                    </div>
                    <p><small>Seu token expira em 24h</small></p>
                </div>
                <div class="verification-upload">
                    <label class="upload-label">
                        <input type="file" accept="image/*" class="upload-input" style="display: none;">
                        📸 Upload Screenshot (opcional)
                    </label>
                </div>
                <button type="button" class="continue-button" style="margin-top: 12px;">Finalizar Cadastro</button>
            </div>
        `;
    }
    
    generateSummary() {
        const summary = this.buildSummary();
        return `
            <div class="summary-card">
                <h4>Confirme seus dados:</h4>
                <div class="summary-content">
                    ${summary}
                </div>
                <button type="button" class="continue-button confirm-submit" style="margin-top: 12px;">
                    ✨ Confirmar e Finalizar
                </button>
            </div>
        `;
    }
    
    handleChipClick(chip) {
        const isMulti = chip.dataset.multi === 'true';
        const value = chip.dataset.value;
        
        if (isMulti) {
            chip.classList.toggle('selected');
            this.updateContinueButton();
        } else {
            // Single select - clear others and select this one
            chip.parentElement.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
            chip.classList.add('selected');
            
            // Auto proceed for single choice
            setTimeout(() => {
                this.collectResponse();
                this.nextStep();
            }, 300);
        }
    }
    
    handleSendClick() {
        const input = document.querySelector('.text-input');
        if (!input) return;
        
        const value = input.value.trim();
        if (!value) return;
        
        // Validate input
        if (!this.validateInput(input)) return;
        
        // Add user message
        this.addUserMessage(value);
        
        // Store response
        const field = input.dataset.field;
        this.responses[field] = value;
        
        // Clear input area and proceed
        document.getElementById('chatInputArea').innerHTML = '';
        setTimeout(() => this.nextStep(), 500);
    }
    
    collectResponse() {
        const currentStepName = this.getCurrentStepName();
        const step = this.steps[currentStepName];
        
        if (step.type === 'single_choice' || step.type === 'multi_choice') {
            const selected = Array.from(document.querySelectorAll('.chip.selected'))
                .map(chip => chip.dataset.value);
            
            if (step.type === 'single_choice') {
                this.responses[step.field] = selected[0];
                this.addUserMessage(selected[0]);
            } else {
                this.responses[step.field] = selected;
                this.addUserMessage(selected.join(', '));
            }
        } else if (step.type === 'contact_form') {
            const whatsapp = document.querySelector('[data-field="whatsapp"]')?.value;
            const instagram = document.querySelector('[data-field="instagram"]')?.value;
            
            if (whatsapp) {
                this.responses.whatsapp = this.normalizeWhatsapp(whatsapp);
            }
            if (instagram) {
                this.responses.instagram = this.normalizeInstagram(instagram);
            }
            
            this.addUserMessage(`WhatsApp: ${whatsapp}, Instagram: ${instagram}`);
        }
    }
    
    validateInput(input) {
        const stepName = this.getCurrentStepName();
        const step = this.steps[stepName];
        const value = input.value.trim();
        
        if (step.validation?.min_length && value.length < step.validation.min_length) {
            this.showValidationError(`Mínimo ${step.validation.min_length} caracteres`);
            return false;
        }
        
        if (input.dataset.field === 'instagram') {
            const regex = /^@?[a-zA-Z0-9._]{1,30}$/;
            if (!regex.test(value)) {
                this.showValidationError('Instagram inválido');
                return false;
            }
        }
        
        return true;
    }
    
    showValidationError(message) {
        // Create temporary error message
        const error = document.createElement('div');
        error.className = 'validation-error';
        error.textContent = message;
        error.style.cssText = 'color: #dc2626; font-size: 12px; margin-top: 4px;';
        
        const inputArea = document.getElementById('chatInputArea');
        const existing = inputArea.querySelector('.validation-error');
        if (existing) existing.remove();
        
        inputArea.appendChild(error);
        
        setTimeout(() => error.remove(), 3000);
    }
    
    updateContinueButton() {
        const button = document.querySelector('.continue-button');
        const selected = document.querySelectorAll('.chip.selected');
        
        if (button) {
            button.disabled = selected.length === 0;
            button.style.opacity = selected.length > 0 ? '1' : '0.5';
        }
    }
    
    nextStep() {
        const stepOrder = Object.keys(this.steps);
        const currentIndex = stepOrder.indexOf(this.currentStep);
        
        if (currentIndex < stepOrder.length - 1) {
            this.currentStep = stepOrder[currentIndex + 1];
            setTimeout(() => this.showStep(this.currentStep), 500);
        } else {
            this.completeOnboarding();
        }
    }
    
    getCurrentStepName() {
        return this.currentStep;
    }
    
    async completeOnboarding() {
        this.showTyping();
        
        // Save progress
        await this.saveProgress();
        
        this.hideTyping();
        
        // Show completion message
        this.addBotMessage('Perfeito! Seu cadastro foi enviado para verificação. 🎉');
        
        await this.delay(1000);
        
        this.addBotMessage('Utilidades especiais chegando — siga @apollo no IG para ficar por dentro! 📱');
        
        // Redirect after delay
        setTimeout(() => {
            window.location.href = '/verificacao/';
        }, 3000);
    }
    
    async saveProgress() {
        try {
            const response = await fetch(`${window.apolloOnboarding?.apiUrl || '/wp-json/apollo/v1/'}onboarding/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify({
                    responses: this.responses,
                    verify_token: this.buildVerifyToken(this.normalizeInstagram(this.responses.instagram))
                })
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error saving progress:', error);
            this.addBotMessage('Erro ao salvar. Tente novamente.');
        }
    }
    
    addBotMessage(text) {
        const messagesArea = document.getElementById('chatMessages');
        const messageHtml = `
            <div class="message-bubble bot-message">
                <div class="message-content">
                    <p>${text}</p>
                </div>
                <div class="message-timestamp">
                    ${new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                </div>
            </div>
        `;
        
        messagesArea.insertAdjacentHTML('beforeend', messageHtml);
        this.scrollToBottom();
    }
    
    addUserMessage(text) {
        const messagesArea = document.getElementById('chatMessages');
        const messageHtml = `
            <div class="message-bubble user-message">
                <div class="message-content">
                    <p>${text}</p>
                </div>
                <div class="message-timestamp">
                    ${new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                </div>
            </div>
        `;
        
        messagesArea.insertAdjacentHTML('beforeend', messageHtml);
        this.scrollToBottom();
    }
    
    showTyping() {
        const indicator = document.getElementById('typingIndicator');
        indicator.style.display = 'block';
        this.scrollToBottom();
    }
    
    hideTyping() {
        const indicator = document.getElementById('typingIndicator');
        indicator.style.display = 'none';
    }
    
    scrollToBottom() {
        const messagesArea = document.getElementById('chatMessages');
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
    
    updateProgress(stepName) {
        const stepOrder = Object.keys(this.steps);
        const currentIndex = stepOrder.indexOf(stepName);
        const progress = ((currentIndex + 1) / stepOrder.length) * 100;
        
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        if (progressFill) {
            progressFill.style.width = `${progress}%`;
        }
        
        if (progressText) {
            progressText.textContent = `Passo ${currentIndex + 1} de ${stepOrder.length}`;
        }
    }
    
    applyInputMasks() {
        // WhatsApp mask
        const whatsappInput = document.querySelector('.whatsapp-input');
        if (whatsappInput) {
            whatsappInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 11) {
                    value = value.replace(/^(\d{2})(\d{2})(\d{5})(\d{4})/, '+55 ($2) $3-$4');
                }
                e.target.value = value;
            });
        }
        
        // Instagram @ prefix
        const instagramInput = document.querySelector('.instagram-input');
        if (instagramInput) {
            instagramInput.addEventListener('input', (e) => {
                let value = e.target.value;
                if (value && !value.startsWith('@')) {
                    e.target.value = '@' + value;
                }
            });
        }
    }
    
    normalizeInstagram(instagram) {
        return instagram ? instagram.replace(/^@/, '').toLowerCase() : '';
    }
    
    normalizeWhatsapp(whatsapp) {
        const digits = whatsapp.replace(/\D/g, '');
        return digits.length === 11 ? '+55' + digits : '+' + digits;
    }
    
    buildVerifyToken(username) {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        return `${yyyy}${mm}${dd}${username.toLowerCase()}`;
    }
    
    buildSummary() {
        const { name, industry, roles, member_of, whatsapp, instagram } = this.responses;
        
        let html = `<p><strong>Nome:</strong> ${name}</p>`;
        html += `<p><strong>Indústria:</strong> ${industry}</p>`;
        
        if (roles?.length) {
            html += `<p><strong>Roles:</strong> ${roles.join(', ')}</p>`;
        }
        
        if (member_of?.length) {
            html += `<p><strong>Membro de:</strong> ${member_of.join(', ')}</p>`;
        }
        
        html += `<p><strong>WhatsApp:</strong> ${whatsapp}</p>`;
        html += `<p><strong>Instagram:</strong> @${this.normalizeInstagram(instagram)}</p>`;
        
        return html;
    }
    
    async loadMembershipOptions() {
        try {
            const response = await fetch(`${window.apolloOnboarding?.apiUrl || '/wp-json/apollo/v1/'}onboarding/options`);
            return await response.json();
        } catch (error) {
            console.error('Error loading membership options:', error);
            return { nucleos: [], locais: [] };
        }
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ApolloOnboardingChat();
});