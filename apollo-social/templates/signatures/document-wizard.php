<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Documento - Apollo Signatures</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .wizard-header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .wizard-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .wizard-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .progress-bar {
            height: 4px;
            background: #ecf0f1;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: #3498db;
            transition: width 0.3s ease;
        }

        .wizard-content {
            padding: 40px;
        }

        .step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .template-card {
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .template-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .template-card.selected {
            border-color: #3498db;
            background: #ebf3ff;
        }

        .template-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .template-card p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .track-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .track-card {
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .track-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
        }

        .track-card.selected {
            border-color: #3498db;
            background: #ebf3ff;
        }

        .track-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .track-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .track-card .track-subtitle {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .track-card .track-description {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .track-card .track-legal {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #6c757d;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            text-align: center;
            padding: 40px;
        }

        .success-icon {
            font-size: 64px;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .success-message h2 {
            color: #27ae60;
            margin-bottom: 15px;
        }

        .success-message p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .signature-link {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .signature-link input {
            flex: 1;
            border: none;
            background: none;
            font-family: monospace;
            color: #495057;
        }

        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .compliance-info {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        .compliance-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .compliance-info p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #bdc3c7;
            transition: background-color 0.3s ease;
        }

        .step-dot.active {
            background: #3498db;
        }

        .step-dot.completed {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1>🖊️ Criar Documento para Assinatura</h1>
            <p>Trilhos A e B conforme Lei 14.063/2020</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 20%"></div>
        </div>

        <div class="wizard-content">
            <div class="step-indicator">
                <div class="step-dot active" data-step="1"></div>
                <div class="step-dot" data-step="2"></div>
                <div class="step-dot" data-step="3"></div>
                <div class="step-dot" data-step="4"></div>
                <div class="step-dot" data-step="5"></div>
            </div>

            <!-- Step 1: Choose Template -->
            <div class="step active" id="step1">
                <h2 class="step-title">
                    📄 Escolher Modelo
                </h2>
                <p class="step-subtitle">Selecione um modelo de documento para personalizar</p>

                <div class="template-grid">
                    <div class="template-card" data-template="contract">
                        <h3>Contrato de Prestação de Serviços</h3>
                        <p>Modelo completo para prestação de serviços com cláusulas padrão</p>
                    </div>
                    <div class="template-card" data-template="nda">
                        <h3>Acordo de Confidencialidade (NDA)</h3>
                        <p>Termo de confidencialidade para proteção de informações</p>
                    </div>
                    <div class="template-card" data-template="authorization">
                        <h3>Autorização de Uso de Imagem</h3>
                        <p>Autorização para uso de imagem e voz em materiais</p>
                    </div>
                    <div class="template-card" data-template="partnership">
                        <h3>Termo de Parceria</h3>
                        <p>Acordo de parceria entre organizações ou empresas</p>
                    </div>
                </div>

                <div class="buttons">
                    <span></span>
                    <button class="btn btn-primary" id="nextStep1" disabled>Próximo</button>
                </div>
            </div>

            <!-- Step 2: Fill Template Data -->
            <div class="step" id="step2">
                <h2 class="step-title">
                    ✏️ Preencher Dados
                </h2>
                <p class="step-subtitle">Complete as informações do documento</p>

                <form id="templateForm">
                    <div class="form-group">
                        <label class="form-label">Título do Documento</label>
                        <input type="text" class="form-input" name="title" placeholder="Ex: Contrato de Prestação de Serviços - Projeto XYZ">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nome do Contratante</label>
                        <input type="text" class="form-input" name="contractor_name" placeholder="Nome da empresa ou pessoa contratante">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nome do Contratado</label>
                        <input type="text" class="form-input" name="contracted_name" placeholder="Nome da empresa ou pessoa contratada">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Valor do Contrato</label>
                        <input type="text" class="form-input" name="contract_value" placeholder="R$ 0,00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Início</label>
                        <input type="date" class="form-input" name="start_date">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observações Adicionais</label>
                        <textarea class="form-textarea" name="notes" placeholder="Informações específicas para este documento..."></textarea>
                    </div>
                </form>

                <div class="buttons">
                    <button class="btn btn-secondary" id="prevStep2">Voltar</button>
                    <button class="btn btn-primary" id="nextStep2">Próximo</button>
                </div>
            </div>

            <!-- Step 3: Signer Information -->
            <div class="step" id="step3">
                <h2 class="step-title">
                    👤 Dados do Signatário
                </h2>
                <p class="step-subtitle">Informe os dados da pessoa que irá assinar</p>

                <form id="signerForm">
                    <div class="form-group">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" class="form-input" name="signer_name" placeholder="Nome completo do signatário" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-input" name="signer_email" placeholder="email@exemplo.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">CPF ou CNPJ</label>
                        <input type="text" class="form-input" name="signer_document" placeholder="000.000.000-00 ou 00.000.000/0001-00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telefone (opcional)</label>
                        <input type="tel" class="form-input" name="signer_phone" placeholder="(11) 99999-9999">
                    </div>
                </form>

                <div class="buttons">
                    <button class="btn btn-secondary" id="prevStep3">Voltar</button>
                    <button class="btn btn-primary" id="nextStep3">Próximo</button>
                </div>
            </div>

            <!-- Step 4: Choose Signature Track -->
            <div class="step" id="step4">
                <h2 class="step-title">
                    ⚖️ Escolher Trilho de Assinatura
                </h2>
                <p class="step-subtitle">Selecione o nível de segurança jurídica necessário</p>

                <div class="track-selection">
                    <div class="track-card" data-track="track_a">
                        <div class="track-icon">⚡</div>
                        <h3>Trilho A - Rápido</h3>
                        <div class="track-subtitle">Assinatura Avançada</div>
                        <div class="track-description">
                            Ideal para contratos comerciais, termos de uso e documentos internos. 
                            Processo rápido e simples via DocuSeal.
                        </div>
                        <div class="track-legal">
                            <strong>Base Legal:</strong> Lei 14.063/2020 Art. 10 § 2º<br>
                            <strong>Validade:</strong> Presunção de autenticidade
                        </div>
                    </div>

                    <div class="track-card" data-track="track_b">
                        <div class="track-icon">🛡️</div>
                        <h3>Trilho B - Qualificado</h3>
                        <div class="track-subtitle">Assinatura ICP-Brasil</div>
                        <div class="track-description">
                            Para documentos oficiais, cartórios e órgãos públicos. 
                            Requer certificado digital ICP-Brasil.
                        </div>
                        <div class="track-legal">
                            <strong>Base Legal:</strong> Lei 14.063/2020 + MP 2.200-2/2001<br>
                            <strong>Validade:</strong> Equivale à manuscrita
                        </div>
                    </div>
                </div>

                <div class="compliance-info">
                    <h4>ℹ️ Informações de Compliance</h4>
                    <p><strong>Trilho A:</strong> Adequado para 90% dos casos comerciais. Aceito por empresas privadas e com presunção legal de validade.</p>
                    <p><strong>Trilho B:</strong> Obrigatório para cartórios, órgãos públicos e contratos de alto valor. Equivale juridicamente à assinatura manuscrita.</p>
                </div>

                <div class="buttons">
                    <button class="btn btn-secondary" id="prevStep4">Voltar</button>
                    <button class="btn btn-primary" id="nextStep4" disabled>Próximo</button>
                </div>
            </div>

            <!-- Step 5: Success -->
            <div class="step" id="step5">
                <div class="success-message">
                    <div class="success-icon">✅</div>
                    <h2>Documento Criado com Sucesso!</h2>
                    <p>O documento foi gerado e está pronto para assinatura.</p>

                    <div class="signature-link">
                        <input type="text" id="signingUrl" readonly>
                        <button class="copy-btn" onclick="copySigningUrl()">Copiar</button>
                    </div>

                    <div class="compliance-info">
                        <h4>🔒 Próximos Passos</h4>
                        <p>1. Compartilhe o link de assinatura com o signatário</p>
                        <p>2. O signatário receberá um e-mail com instruções</p>
                        <p>3. Você será notificado quando a assinatura for concluída</p>
                        <p>4. O documento assinado ficará disponível para download</p>
                    </div>

                    <div class="buttons">
                        <a href="/apollo/documentos" class="btn btn-secondary">Ver Documentos</a>
                        <button class="btn btn-primary" onclick="startNew()">Criar Novo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let selectedTemplate = null;
        let selectedTrack = null;
        let formData = {};

        // Step navigation
        function updateProgressBar() {
            const progress = (currentStep / 5) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            
            // Update step dots
            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                const stepNum = index + 1;
                dot.classList.remove('active', 'completed');
                
                if (stepNum < currentStep) {
                    dot.classList.add('completed');
                } else if (stepNum === currentStep) {
                    dot.classList.add('active');
                }
            });
        }

        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            currentStep = step;
            updateProgressBar();
        }

        // Template selection
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedTemplate = this.dataset.template;
                document.getElementById('nextStep1').disabled = false;
            });
        });

        // Track selection
        document.querySelectorAll('.track-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.track-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedTrack = this.dataset.track;
                document.getElementById('nextStep4').disabled = false;
            });
        });

        // Navigation buttons
        document.getElementById('nextStep1').addEventListener('click', () => showStep(2));
        document.getElementById('prevStep2').addEventListener('click', () => showStep(1));
        document.getElementById('nextStep2').addEventListener('click', () => {
            // Collect form data
            const form = document.getElementById('templateForm');
            const formDataObj = new FormData(form);
            for (let [key, value] of formDataObj.entries()) {
                formData[key] = value;
            }
            showStep(3);
        });

        document.getElementById('prevStep3').addEventListener('click', () => showStep(2));
        document.getElementById('nextStep3').addEventListener('click', () => {
            // Validate signer form
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
            // Submit the document creation
            createDocument();
        });

        function createDocument() {
            // Show loading state
            const btn = document.getElementById('nextStep4');
            btn.disabled = true;
            btn.textContent = 'Criando...';

            // Simulate API call
            const documentData = {
                template: selectedTemplate,
                track: selectedTrack,
                data: formData
            };

            // In real implementation, make AJAX call to backend
            console.log('Creating document:', documentData);

            // Simulate success after 2 seconds
            setTimeout(() => {
                const signingUrl = `https://apollo.example.com/sign/${Math.random().toString(36).substr(2, 9)}`;
                document.getElementById('signingUrl').value = signingUrl;
                showStep(5);
            }, 2000);
        }

        function copySigningUrl() {
            const input = document.getElementById('signingUrl');
            input.select();
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copiado!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }

        function startNew() {
            location.reload();
        }

        // Initialize
        updateProgressBar();
    </script>
</body>
</html>