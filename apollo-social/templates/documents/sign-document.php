<?php
/**
 * Template: Página de Assinatura Individual
 * URL: /sign/{token}
 * 
 * Permite assinar documento via link público (sem login)
 * Validações: CPF + Nome Completo (ICP-Brasil)
 */

use Apollo\Modules\Documents\DocumentsManager;

// Verificar token na URL
$token = get_query_var('signature_token');

if (empty($token)) {
    wp_die('Token inválido', 'Erro', ['response' => 400]);
}

$doc_manager = new DocumentsManager();
global $wpdb;

$signatures_table = $wpdb->prefix . 'apollo_document_signatures';
$documents_table = $wpdb->prefix . 'apollo_documents';

// Buscar signature request
$signature = $wpdb->get_row($wpdb->prepare(
    "SELECT s.*, d.* 
     FROM {$signatures_table} s
     INNER JOIN {$documents_table} d ON s.document_id = d.id
     WHERE s.verification_token = %s",
    $token
), ARRAY_A);

if (!$signature) {
    wp_die('Link de assinatura inválido ou expirado', 'Erro', ['response' => 404]);
}

// Verificar se já foi assinado
if ($signature['status'] === 'signed') {
    wp_die('Este documento já foi assinado', 'Aviso', ['response' => 200]);
}

// Processar assinatura (POST)
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signer_data = [
        'name' => sanitize_text_field($_POST['signer_name'] ?? ''),
        'cpf' => sanitize_text_field($_POST['signer_cpf'] ?? ''),
        'email' => sanitize_email($_POST['signer_email'] ?? '')
    ];
    
    $signature_canvas = $_POST['signature_data'] ?? '';
    
    $result = $doc_manager->signDocument($token, $signer_data, $signature_canvas);
    
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['error'];
    }
}

get_header();
?>

<style>
    .apollo-sign-page {
        max-width: 800px;
        margin: 40px auto;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .sign-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }
    
    .sign-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        text-align: center;
    }
    
    .sign-card-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 700;
    }
    
    .sign-card-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .sign-card-body {
        padding: 40px;
    }
    
    .document-info {
        background: #f7fafc;
        border-left: 4px solid #667eea;
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 8px;
    }
    
    .document-info h2 {
        margin: 0 0 10px 0;
        font-size: 20px;
        color: #2d3748;
    }
    
    .document-info p {
        margin: 0;
        color: #718096;
        font-size: 14px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-help {
        margin-top: 5px;
        font-size: 12px;
        color: #718096;
    }
    
    .canvas-container {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        background: #f7fafc;
        text-align: center;
    }
    
    .signature-canvas {
        border: 2px dashed #cbd5e0;
        border-radius: 8px;
        background: white;
        cursor: crosshair;
        display: block;
        margin: 0 auto 15px auto;
        touch-action: none;
    }
    
    .canvas-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:disabled {
        background: #cbd5e0;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }
    
    .btn-secondary:hover {
        background: #cbd5e0;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .alert-error {
        background: #fed7d7;
        color: #c53030;
        border: 2px solid #fc8181;
    }
    
    .alert-success {
        background: #c6f6d5;
        color: #22543d;
        border: 2px solid #68d391;
    }
    
    .icp-brasil-badge {
        margin-top: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        border-radius: 8px;
        text-align: center;
        border: 2px solid #e2e8f0;
    }
    
    .icp-brasil-badge img {
        height: 40px;
        margin-bottom: 10px;
    }
    
    .icp-brasil-badge p {
        margin: 0;
        font-size: 12px;
        color: #718096;
    }
    
    .requirements-list {
        background: #fffbeb;
        border: 2px solid #fcd34d;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .requirements-list h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #92400e;
    }
    
    .requirements-list ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .requirements-list li {
        margin-bottom: 8px;
        color: #78350f;
        font-size: 14px;
    }
</style>

<div class="apollo-sign-page">
    
    <div class="sign-card">
        <div class="sign-card-header">
            <h1>✍️ Assinatura de Documento</h1>
            <p>Sistema de Assinatura Digital - Apollo Social</p>
        </div>
        
        <div class="sign-card-body">
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo esc_html($success_message); ?>
                </div>
                <div style="text-align: center;">
                    <a href="/" class="btn btn-primary">Voltar ao Início</a>
                </div>
            <?php else: ?>
            
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo esc_html($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="document-info">
                    <h2>📄 <?php echo esc_html($signature['title']); ?></h2>
                    <p>
                        <strong>Tipo:</strong> 
                        <?php echo $signature['type'] === 'documento' ? 'Documento' : 'Planilha'; ?> •
                        <strong>Criado em:</strong> 
                        <?php echo date_i18n('d/m/Y H:i', strtotime($signature['created_at'])); ?>
                    </p>
                    <p style="margin-top: 10px;">
                        <a href="<?php echo esc_url($signature['pdf_path']); ?>" 
                           target="_blank" 
                           style="color: #667eea; text-decoration: none; font-weight: 600;">
                            👁️ Visualizar documento completo
                        </a>
                    </p>
                </div>
                
                <div class="requirements-list">
                    <h3>📋 Requisitos ICP-Brasil</h3>
                    <ul>
                        <li>✅ CPF válido (11 dígitos com verificação)</li>
                        <li>✅ Nome completo (mínimo 2 palavras, maioria com mais de 3 letras)</li>
                        <li>✅ E-mail válido para confirmação</li>
                        <li>✅ Assinatura manuscrita no canvas</li>
                    </ul>
                </div>
                
                <form method="post" id="signature-form">
                    
                    <div class="form-group">
                        <label class="form-label" for="signer_name">
                            Nome Completo *
                        </label>
                        <input type="text" 
                               id="signer_name" 
                               name="signer_name" 
                               class="form-input" 
                               placeholder="Ex: João Silva Santos"
                               required
                               value="<?php echo esc_attr($signature['signer_name'] ?? ''); ?>">
                        <div class="form-help">
                            Mínimo 2 palavras, a maioria com mais de 3 letras
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="signer_cpf">
                            CPF *
                        </label>
                        <input type="text" 
                               id="signer_cpf" 
                               name="signer_cpf" 
                               class="form-input" 
                               placeholder="000.000.000-00"
                               maxlength="14"
                               required
                               value="<?php echo esc_attr($signature['signer_cpf'] ?? ''); ?>">
                        <div class="form-help">
                            Será validado com algoritmo oficial do CPF
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="signer_email">
                            E-mail *
                        </label>
                        <input type="email" 
                               id="signer_email" 
                               name="signer_email" 
                               class="form-input" 
                               placeholder="seuemail@exemplo.com"
                               required
                               value="<?php echo esc_attr($signature['signer_email'] ?? ''); ?>">
                        <div class="form-help">
                            Você receberá confirmação da assinatura
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Assinatura Manuscrita *
                        </label>
                        <div class="canvas-container">
                            <canvas id="signature-canvas" 
                                    class="signature-canvas" 
                                    width="700" 
                                    height="200">
                            </canvas>
                            <div class="canvas-actions">
                                <button type="button" class="btn btn-secondary" id="clear-canvas">
                                    🗑️ Limpar
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="signature_data" id="signature-data">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submit-btn" style="width: 100%;" disabled>
                        ✍️ Confirmar Assinatura
                    </button>
                    
                </form>
                
                <div class="icp-brasil-badge">
                    <div style="font-size: 32px; margin-bottom: 10px;">🛡️</div>
                    <p>
                        <strong>Assinatura Digital Qualificada</strong><br>
                        Trilho B - GOV.BR ICP-Brasil<br>
                        Validade jurídica equivalente à assinatura manuscrita
                    </p>
                </div>
            
            <?php endif; ?>
            
        </div>
    </div>
    
</div>

<script>
(function() {
    const canvas = document.getElementById('signature-canvas');
    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('clear-canvas');
    const submitBtn = document.getElementById('submit-btn');
    const signatureDataInput = document.getElementById('signature-data');
    const cpfInput = document.getElementById('signer_cpf');
    
    let isDrawing = false;
    let hasDrawn = false;
    
    // Configurar canvas
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // Eventos de desenho (mouse)
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Eventos de desenho (touch)
    canvas.addEventListener('touchstart', handleTouchStart, {passive: false});
    canvas.addEventListener('touchmove', handleTouchMove, {passive: false});
    canvas.addEventListener('touchend', stopDrawing);
    
    function startDrawing(e) {
        isDrawing = true;
        const pos = getMousePos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }
    
    function draw(e) {
        if (!isDrawing) return;
        
        const pos = getMousePos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        
        hasDrawn = true;
        checkFormValidity();
    }
    
    function stopDrawing() {
        if (isDrawing) {
            isDrawing = false;
            saveSignature();
        }
    }
    
    function handleTouchStart(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }
    
    function handleTouchMove(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }
    
    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        return {
            x: (e.clientX - rect.left) * scaleX,
            y: (e.clientY - rect.top) * scaleY
        };
    }
    
    function saveSignature() {
        const dataURL = canvas.toDataURL('image/png');
        signatureDataInput.value = dataURL;
    }
    
    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        signatureDataInput.value = '';
        checkFormValidity();
    }
    
    clearBtn.addEventListener('click', clearCanvas);
    
    // Máscara CPF
    cpfInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        
        e.target.value = value;
    });
    
    // Validação do formulário
    function checkFormValidity() {
        const form = document.getElementById('signature-form');
        const isValid = form.checkValidity() && hasDrawn;
        submitBtn.disabled = !isValid;
    }
    
    // Monitorar mudanças nos inputs
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('input', checkFormValidity);
    });
    
    // Validação ao submeter
    document.getElementById('signature-form').addEventListener('submit', function(e) {
        if (!hasDrawn) {
            e.preventDefault();
            alert('Por favor, assine no campo de assinatura manuscrita.');
            return false;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Processando...';
    });
})();
</script>

<?php get_footer(); ?>
