<?php
/**
 * Template: Listagem de Documentos para Assinatura
 * URL: /sign
 * 
 * Exibe lista de documentos pendentes/em assinatura/completos
 * com barra de progresso (50% = 1 assinatura, 100% = 2 assinaturas)
 */

use Apollo\Modules\Documents\DocumentsManager;

get_header();

$doc_manager = new DocumentsManager();
global $wpdb;

$documents_table = $wpdb->prefix . 'apollo_documents';
$signatures_table = $wpdb->prefix . 'apollo_document_signatures';

// Buscar documentos que requerem assinatura
$documents = $wpdb->get_results("
    SELECT 
        d.*,
        (SELECT COUNT(*) FROM {$signatures_table} WHERE document_id = d.id AND status = 'signed') as signed_count
    FROM {$documents_table} d
    WHERE d.requires_signatures = 1
    ORDER BY d.created_at DESC
", ARRAY_A);

?>

<style>
    .apollo-sign-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    .sign-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .sign-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
        font-weight: 700;
    }
    
    .sign-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .filters {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 10px 20px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .document-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .document-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: #667eea;
    }
    
    .document-header {
        padding: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-bottom: 2px solid #e0e6ed;
    }
    
    .document-type {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    
    .type-documento {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .type-planilha {
        background: #d1fae5;
        color: #065f46;
    }
    
    .document-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1a202c;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .document-body {
        padding: 20px;
    }
    
    .progress-section {
        margin-bottom: 20px;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #4a5568;
    }
    
    .progress-bar {
        height: 12px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #48bb78 0%, #38a169 100%);
        transition: width 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .progress-fill.partial {
        background: linear-gradient(90deg, #ed8936 0%, #dd6b20 100%);
    }
    
    .progress-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background-image: linear-gradient(
            -45deg,
            rgba(255, 255, 255, .2) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, .2) 50%,
            rgba(255, 255, 255, .2) 75%,
            transparent 75%,
            transparent
        );
        background-size: 50px 50px;
        animation: progress-animate 2s linear infinite;
    }
    
    @keyframes progress-animate {
        0% {
            background-position: 0 0;
        }
        100% {
            background-position: 50px 50px;
        }
    }
    
    .signatures-status {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .signature-item {
        flex: 1;
        padding: 15px;
        border-radius: 8px;
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        text-align: center;
    }
    
    .signature-item.signed {
        background: #f0fdf4;
        border-color: #86efac;
    }
    
    .signature-item.pending {
        background: #fef3c7;
        border-color: #fcd34d;
    }
    
    .signature-label {
        font-size: 12px;
        font-weight: 600;
        color: #718096;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .signature-icon {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .signature-name {
        font-size: 13px;
        font-weight: 600;
        color: #2d3748;
    }
    
    .document-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        display: inline-block;
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
    
    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }
    
    .btn-secondary:hover {
        background: #cbd5e0;
    }
    
    .btn-success {
        background: #48bb78;
        color: white;
    }
    
    .btn-success:hover {
        background: #38a169;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-ready {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-signing {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    
    .empty-state h3 {
        margin: 0 0 10px 0;
        font-size: 24px;
        color: #2d3748;
    }
    
    .empty-state p {
        margin: 0;
        color: #718096;
        font-size: 16px;
    }
    
    .upload-section {
        background: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .upload-section h2 {
        margin: 0 0 20px 0;
        font-size: 24px;
        color: #2d3748;
    }
    
    .upload-zone {
        border: 3px dashed #cbd5e0;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f7fafc;
    }
    
    .upload-zone:hover {
        border-color: #667eea;
        background: #edf2f7;
    }
    
    .upload-zone.dragover {
        border-color: #667eea;
        background: #e6f2ff;
    }
</style>

<div class="apollo-sign-container">
    
    <div class="sign-header">
        <h1>📝 Central de Assinaturas</h1>
        <p>Gerencie documentos e acompanhe o progresso de assinaturas</p>
    </div>
    
    <!-- Upload de novo documento -->
    <div class="upload-section">
        <h2>📤 Enviar Novo Documento para Assinatura</h2>
        <form id="upload-form" enctype="multipart/form-data" method="post">
            <div class="upload-zone" id="upload-zone">
                <div style="font-size: 48px; margin-bottom: 15px;">📄</div>
                <p style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                    Arraste o PDF aqui ou clique para selecionar
                </p>
                <p style="color: #718096; font-size: 14px;">
                    Apenas arquivos PDF são aceitos
                </p>
                <input type="file" id="pdf-upload" name="pdf_file" accept=".pdf" style="display: none;">
            </div>
            
            <div style="margin-top: 20px;">
                <input type="text" name="document_title" placeholder="Título do documento" 
                       style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Enviar e Preparar para Assinatura
                </button>
            </div>
        </form>
    </div>
    
    <!-- Filtros -->
    <div class="filters">
        <button class="filter-btn active" data-filter="all">Todos</button>
        <button class="filter-btn" data-filter="ready">Prontos</button>
        <button class="filter-btn" data-filter="signing">Em Assinatura</button>
        <button class="filter-btn" data-filter="completed">Concluídos</button>
    </div>
    
    <!-- Grid de documentos -->
    <?php if (empty($documents)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>Nenhum documento ainda</h3>
            <p>Envie seu primeiro documento PDF para começar</p>
        </div>
    <?php else: ?>
        <div class="documents-grid">
            <?php foreach ($documents as $doc): 
                $completion = $doc_manager->getCompletionPercentage($doc['id']);
                $is_partial = $completion > 0 && $completion < 100;
                $is_complete = $completion >= 100;
                
                // Buscar assinaturas
                $signatures = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$signatures_table} WHERE document_id = %d ORDER BY signer_party",
                    $doc['id']
                ), ARRAY_A);
            ?>
            <div class="document-card" data-status="<?php echo esc_attr($doc['status']); ?>">
                <div class="document-header">
                    <span class="document-type type-<?php echo esc_attr($doc['type']); ?>">
                        <?php echo $doc['type'] === 'documento' ? '📄 DOC' : '📊 PLAN'; ?>
                    </span>
                    <h3 class="document-title" title="<?php echo esc_attr($doc['title']); ?>">
                        <?php echo esc_html($doc['title']); ?>
                    </h3>
                </div>
                
                <div class="document-body">
                    <!-- Progresso -->
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Progresso</span>
                            <span><?php echo number_format($completion, 0); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $is_partial ? 'partial' : ''; ?>" 
                                 style="width: <?php echo $completion; ?>%;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status das assinaturas -->
                    <div class="signatures-status">
                        <?php 
                        $party_a = array_filter($signatures, fn($s) => $s['signer_party'] === 'party_a')[0] ?? null;
                        $party_b = array_filter($signatures, fn($s) => $s['signer_party'] === 'party_b')[0] ?? null;
                        ?>
                        
                        <div class="signature-item <?php echo ($party_a && $party_a['status'] === 'signed') ? 'signed' : 'pending'; ?>">
                            <div class="signature-label">Parte A</div>
                            <div class="signature-icon">
                                <?php echo ($party_a && $party_a['status'] === 'signed') ? '✅' : '⏳'; ?>
                            </div>
                            <div class="signature-name">
                                <?php 
                                if ($party_a && $party_a['status'] === 'signed') {
                                    echo esc_html($party_a['signer_name']);
                                } else {
                                    echo 'Aguardando';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="signature-item <?php echo ($party_b && $party_b['status'] === 'signed') ? 'signed' : 'pending'; ?>">
                            <div class="signature-label">Parte B</div>
                            <div class="signature-icon">
                                <?php echo ($party_b && $party_b['status'] === 'signed') ? '✅' : '⏳'; ?>
                            </div>
                            <div class="signature-name">
                                <?php 
                                if ($party_b && $party_b['status'] === 'signed') {
                                    echo esc_html($party_b['signer_name']);
                                } else {
                                    echo 'Aguardando';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status badge -->
                    <div style="margin-bottom: 15px;">
                        <span class="status-badge status-<?php echo esc_attr($doc['status']); ?>">
                            <?php 
                            $status_labels = [
                                'ready' => '🔵 Pronto',
                                'signing' => '🟡 Em assinatura',
                                'completed' => '🟢 Concluído'
                            ];
                            echo $status_labels[$doc['status']] ?? $doc['status'];
                            ?>
                        </span>
                    </div>
                    
                    <!-- Ações -->
                    <div class="document-actions">
                        <?php if ($is_complete): ?>
                            <a href="<?php echo esc_url($doc['pdf_path']); ?>" class="btn btn-success" download>
                                ⬇️ Download
                            </a>
                        <?php else: ?>
                            <a href="/sign/<?php echo esc_attr($doc['file_id']); ?>" class="btn btn-primary">
                                ✍️ Gerenciar
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($doc['pdf_path']); ?>" class="btn btn-secondary" target="_blank">
                            👁️ Visualizar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
</div>

<script>
(function() {
    // Filtros
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.document-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            cards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Upload zone drag & drop
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('pdf-upload');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/pdf') {
            fileInput.files = files;
            uploadZone.querySelector('p').textContent = `✅ ${files[0].name}`;
        }
    });
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadZone.querySelector('p').textContent = `✅ ${this.files[0].name}`;
        }
    });
})();
</script>

<?php get_footer(); ?>
