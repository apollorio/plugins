<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Local - Apollo Social</title>
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

        .signature-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .signature-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .signature-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .signature-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .signature-body {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: #374151;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .canvas-section {
            border: 3px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
        }

        .canvas-section.active {
            border-color: #4f46e5;
            background: #f0f0ff;
        }

        #signatureCanvas {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: crosshair;
            background: white;
            display: block;
            margin: 0 auto;
        }

        .canvas-controls {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-outline {
            background: transparent;
            color: #4f46e5;
            border: 2px solid #4f46e5;
        }

        .btn-outline:hover {
            background: #4f46e5;
            color: white;
        }

        .signature-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .signature-info h4 {
            color: #0ea5e9;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .signature-info ul {
            color: #0369a1;
            padding-left: 20px;
        }

        .signature-info li {
            margin-bottom: 5px;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #16a34a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .loading .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .signature-body {
                padding: 20px;
            }
            
            #signatureCanvas {
                width: 100%;
                height: 200px;
            }
            
            .canvas-controls {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="signature-container">
        <div class="signature-header">
            <h1>📝 Assinatura Local</h1>
            <p>Sistema de assinatura interno Apollo Social</p>
        </div>

        <div class="signature-body">
            <div id="errorContainer"></div>
            <div id="successContainer"></div>

            <!-- Informações do Signatário -->
            <div class="form-section">
                <h3>👤 Informações do Signatário</h3>
                
                <div class="form-group">
                    <label for="signerName">Nome Completo *</label>
                    <input type="text" id="signerName" required placeholder="Digite seu nome completo">
                </div>

                <div class="form-group">
                    <label for="signerEmail">Email *</label>
                    <input type="email" id="signerEmail" required placeholder="Digite seu email">
                </div>

                <div class="form-group">
                    <label for="signerDocument">Documento (opcional)</label>
                    <input type="text" id="signerDocument" placeholder="CPF, RG ou outro documento">
                </div>
            </div>

            <!-- Área de Assinatura -->
            <div class="form-section">
                <h3>✍️ Área de Assinatura</h3>
                
                <div class="signature-info">
                    <h4>🔒 Informações de Segurança</h4>
                    <ul>
                        <li>Sua assinatura será capturada como evidência digital</li>
                        <li>Geramos hash SHA-256 para verificação de integridade</li>
                        <li>Timestamp e IP são registrados para auditoria</li>
                        <li>Certificado local com validade de 10 anos</li>
                    </ul>
                </div>

                <div class="canvas-section" id="canvasSection">
                    <p>👆 Desenhe sua assinatura no quadro abaixo</p>
                    <canvas id="signatureCanvas" width="600" height="200"></canvas>
                    
                    <div class="canvas-controls">
                        <button type="button" class="btn btn-outline" onclick="clearSignature()">
                            🗑️ Limpar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="undoLastStroke()">
                            ↶ Desfazer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="form-section">
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary" onclick="processSignature()">
                        ✅ Processar Assinatura
                    </button>
                    <a href="javascript:history.back()" class="btn btn-outline">
                        ← Voltar
                    </a>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading" style="display: none;">
                <div class="spinner"></div>
                <p>Processando assinatura...</p>
            </div>
        </div>
    </div>

    <script>
        // Canvas setup
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let strokes = [];
        let currentStroke = [];
        let startTime = Date.now();

        // Setup canvas
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);

        // Touch events
        canvas.addEventListener('touchstart', handleTouch);
        canvas.addEventListener('touchmove', handleTouch);
        canvas.addEventListener('touchend', handleTouch);

        function startDrawing(e) {
            isDrawing = true;
            currentStroke = [];
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(x, y);
            currentStroke.push({x, y, timestamp: Date.now()});
            
            document.getElementById('canvasSection').classList.add('active');
        }

        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.lineTo(x, y);
            ctx.stroke();
            currentStroke.push({x, y, timestamp: Date.now()});
        }

        function stopDrawing() {
            if (!isDrawing) return;
            
            isDrawing = false;
            strokes.push([...currentStroke]);
            currentStroke = [];
        }

        function handleTouch(e) {
            e.preventDefault();
            
            const touch = e.touches[0] || e.changedTouches[0];
            const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                            e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            
            canvas.dispatchEvent(mouseEvent);
        }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            strokes = [];
            currentStroke = [];
            document.getElementById('canvasSection').classList.remove('active');
        }

        function undoLastStroke() {
            if (strokes.length === 0) return;
            
            strokes.pop();
            redrawCanvas();
        }

        function redrawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            strokes.forEach(stroke => {
                if (stroke.length === 0) return;
                
                ctx.beginPath();
                ctx.moveTo(stroke[0].x, stroke[0].y);
                
                stroke.forEach(point => {
                    ctx.lineTo(point.x, point.y);
                });
                
                ctx.stroke();
            });
        }

        function showError(message) {
            document.getElementById('errorContainer').innerHTML = 
                `<div class="error-message">❌ ${message}</div>`;
        }

        function showSuccess(message) {
            document.getElementById('successContainer').innerHTML = 
                `<div class="success-message">✅ ${message}</div>`;
        }

        function clearMessages() {
            document.getElementById('errorContainer').innerHTML = '';
            document.getElementById('successContainer').innerHTML = '';
        }

        async function processSignature() {
            clearMessages();
            
            // Validate form
            const name = document.getElementById('signerName').value.trim();
            const email = document.getElementById('signerEmail').value.trim();
            
            if (!name) {
                showError('Nome é obrigatório');
                return;
            }
            
            if (!email) {
                showError('Email é obrigatório');
                return;
            }
            
            if (strokes.length === 0) {
                showError('Por favor, desenhe sua assinatura');
                return;
            }
            
            // Show loading
            document.getElementById('loadingContainer').style.display = 'block';
            
            const signatureData = {
                signer_name: name,
                signer_email: email,
                signer_document: document.getElementById('signerDocument').value.trim(),
                stroke_data: strokes,
                canvas_width: canvas.width,
                canvas_height: canvas.height,
                duration: Date.now() - startTime,
                screen_resolution: `${screen.width}x${screen.height}`,
                device_pixel_ratio: window.devicePixelRatio || 1,
                template_id: new URLSearchParams(window.location.search).get('template_id')
            };
            
            try {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'apollo_process_local_signature',
                        signature_data: JSON.stringify(signatureData),
                        nonce: '<?php echo wp_create_nonce("apollo_local_signature"); ?>'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Assinatura processada com sucesso!');
                    
                    // Show certificate info
                    const certificate = result.data.certificate;
                    setTimeout(() => {
                        alert(`✅ Assinatura concluída!\n\nCertificado: ${certificate.certificate_id}\nURL de Verificação: ${certificate.verification_url}`);
                        
                        // Redirect or close
                        if (window.opener) {
                            window.close();
                        } else {
                            window.location.href = '/apollo/dashboard';
                        }
                    }, 1000);
                    
                } else {
                    showError(result.data.errors.join(', '));
                }
                
            } catch (error) {
                showError('Erro ao processar assinatura: ' + error.message);
            } finally {
                document.getElementById('loadingContainer').style.display = 'none';
            }
        }

        // Auto-fill from URL params
        window.addEventListener('load', () => {
            const params = new URLSearchParams(window.location.search);
            const email = params.get('signer_email');
            
            if (email) {
                document.getElementById('signerEmail').value = email;
            }
        });
    </script>
</body>
</html>