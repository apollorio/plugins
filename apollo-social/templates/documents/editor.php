<?php
/**
 * Template: Editor de Documentos/Planilhas
 * Usado por /doc/new, /doc/{id}, /pla/new, /pla/{id}
 */

$type_label = $type === 'documento' ? 'Documento' : 'Planilha';
$icon = $type === 'documento' ? '📄' : '📊';
$is_new = $mode === 'new';
$doc_title = $is_new ? "Novo {$type_label}" : ($document['title'] ?? '');
$doc_content = $is_new ? '' : ($document['content'] ?? '');

get_header();
?>

<style>
    .apollo-editor {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .editor-header {
        background: white;
        border-bottom: 2px solid #e2e8f0;
        padding: 15px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        z-index: 10;
    }
    
    .editor-title-section {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
    }
    
    .editor-icon {
        font-size: 32px;
    }
    
    .editor-title-input {
        border: none;
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s;
        flex: 1;
        max-width: 600px;
    }
    
    .editor-title-input:focus {
        outline: none;
        background: #f7fafc;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .editor-actions {
        display: flex;
        gap: 10px;
    }
    
    .editor-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
    }
    
    .btn-save {
        background: #667eea;
        color: white;
    }
    
    .btn-save:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-prepare {
        background: #48bb78;
        color: white;
    }
    
    .btn-prepare:hover {
        background: #38a169;
    }
    
    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
    }
    
    .btn-secondary:hover {
        background: #cbd5e0;
    }
    
    .save-status {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: none;
    }
    
    .save-status.saving {
        background: #fef3c7;
        color: #92400e;
        display: inline-block;
    }
    
    .save-status.saved {
        background: #d1fae5;
        color: #065f46;
        display: inline-block;
    }
    
    .editor-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    
    .editor-toolbar {
        background: #f7fafc;
        border-right: 2px solid #e2e8f0;
        padding: 20px;
        width: 250px;
        overflow-y: auto;
    }
    
    .toolbar-section {
        margin-bottom: 25px;
    }
    
    .toolbar-section h3 {
        margin: 0 0 12px 0;
        font-size: 13px;
        font-weight: 700;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .toolbar-btn {
        display: block;
        width: 100%;
        padding: 10px;
        margin-bottom: 8px;
        border: 2px solid #e2e8f0;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #2d3748;
    }
    
    .toolbar-btn:hover {
        border-color: #667eea;
        background: #edf2f7;
        transform: translateX(5px);
    }
    
    .editor-canvas {
        flex: 1;
        padding: 40px;
        overflow-y: auto;
        background: #edf2f7;
    }
    
    .editor-page {
        background: white;
        max-width: 900px;
        margin: 0 auto;
        padding: 60px 80px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        min-height: 1000px;
    }
    
    .editor-content {
        border: none;
        width: 100%;
        min-height: 800px;
        font-size: 16px;
        line-height: 1.6;
        color: #2d3748;
        resize: none;
        font-family: Georgia, 'Times New Roman', serif;
    }
    
    .editor-content:focus {
        outline: none;
    }
    
    .spreadsheet-grid {
        width: 100%;
        border-collapse: collapse;
    }
    
    .spreadsheet-grid th {
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        padding: 8px;
        font-weight: 600;
        font-size: 12px;
        color: #718096;
        text-align: center;
    }
    
    .spreadsheet-grid td {
        border: 1px solid #e2e8f0;
        padding: 0;
    }
    
    .spreadsheet-cell {
        border: none;
        width: 100%;
        padding: 8px;
        font-size: 14px;
        font-family: monospace;
    }
    
    .spreadsheet-cell:focus {
        outline: 2px solid #667eea;
        background: #f0f8ff;
    }
</style>

<div class="apollo-editor">
    
    <div class="editor-header">
        <div class="editor-title-section">
            <span class="editor-icon"><?php echo $icon; ?></span>
            <input type="text" 
                   class="editor-title-input" 
                   id="document-title"
                   value="<?php echo esc_attr($doc_title); ?>" 
                   placeholder="Título do <?php echo strtolower($type_label); ?>">
            <span class="save-status" id="save-status"></span>
        </div>
        
        <div class="editor-actions">
            <button class="editor-btn btn-secondary" onclick="window.location.href='/sign'">
                ← Voltar
            </button>
            <button class="editor-btn btn-save" id="save-btn">
                💾 Salvar
            </button>
            <?php if (!$is_new): ?>
            <button class="editor-btn btn-prepare" id="prepare-btn">
                ✍️ Preparar para Assinatura
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="editor-body">
        
        <div class="editor-toolbar">
            
            <?php if ($type === 'documento'): ?>
            <div class="toolbar-section">
                <h3>Formatação</h3>
                <button class="toolbar-btn" onclick="formatText('bold')">
                    <strong>B</strong> Negrito
                </button>
                <button class="toolbar-btn" onclick="formatText('italic')">
                    <em>I</em> Itálico
                </button>
                <button class="toolbar-btn" onclick="formatText('underline')">
                    <u>U</u> Sublinhado
                </button>
            </div>
            
            <div class="toolbar-section">
                <h3>Elementos</h3>
                <button class="toolbar-btn" onclick="insertHeading()">
                    📌 Título
                </button>
                <button class="toolbar-btn" onclick="insertList()">
                    📋 Lista
                </button>
                <button class="toolbar-btn" onclick="insertTable()">
                    📊 Tabela
                </button>
            </div>
            <?php endif; ?>
            
            <?php if ($type === 'planilha'): ?>
            <div class="toolbar-section">
                <h3>Planilha</h3>
                <button class="toolbar-btn" onclick="addRow()">
                    ➕ Adicionar Linha
                </button>
                <button class="toolbar-btn" onclick="addColumn()">
                    ➕ Adicionar Coluna
                </button>
                <button class="toolbar-btn" onclick="deleteRow()">
                    ➖ Remover Linha
                </button>
            </div>
            
            <div class="toolbar-section">
                <h3>Funções</h3>
                <button class="toolbar-btn" onclick="insertFormula('SUM')">
                    Σ SOMA
                </button>
                <button class="toolbar-btn" onclick="insertFormula('AVG')">
                    μ MÉDIA
                </button>
                <button class="toolbar-btn" onclick="insertFormula('COUNT')">
                    # CONTAR
                </button>
            </div>
            <?php endif; ?>
            
            <div class="toolbar-section">
                <h3>Informações</h3>
                <div style="font-size: 12px; color: #718096; line-height: 1.5;">
                    <p><strong>Tipo:</strong> <?php echo $type_label; ?></p>
                    <?php if (!$is_new): ?>
                    <p><strong>ID:</strong> <?php echo esc_html($document['file_id']); ?></p>
                    <p><strong>Criado:</strong> <?php echo date_i18n('d/m/Y', strtotime($document['created_at'])); ?></p>
                    <p><strong>Atualizado:</strong> <?php echo date_i18n('d/m/Y', strtotime($document['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <div class="editor-canvas">
            <div class="editor-page">
                
                <?php if ($type === 'documento'): ?>
                <textarea class="editor-content" 
                          id="document-content" 
                          placeholder="Comece a escrever seu documento..."><?php echo esc_textarea($doc_content); ?></textarea>
                <?php endif; ?>
                
                <?php if ($type === 'planilha'): ?>
                <table class="spreadsheet-grid" id="spreadsheet-grid">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <?php for ($col = 0; $col < 10; $col++): ?>
                            <th><?php echo chr(65 + $col); ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody id="spreadsheet-body">
                        <?php for ($row = 1; $row <= 20; $row++): ?>
                        <tr>
                            <th><?php echo $row; ?></th>
                            <?php for ($col = 0; $col < 10; $col++): ?>
                            <td>
                                <input type="text" 
                                       class="spreadsheet-cell" 
                                       data-row="<?php echo $row; ?>" 
                                       data-col="<?php echo $col; ?>">
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
    
</div>

<script>
(function() {
    const titleInput = document.getElementById('document-title');
    const contentInput = document.getElementById('document-content');
    const saveBtn = document.getElementById('save-btn');
    const saveStatus = document.getElementById('save-status');
    const prepareBtn = document.getElementById('prepare-btn');
    
    let saveTimeout;
    let isDirty = false;
    
    // Auto-save ao editar
    function markDirty() {
        isDirty = true;
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(autoSave, 2000);
    }
    
    if (titleInput) {
        titleInput.addEventListener('input', markDirty);
    }
    
    if (contentInput) {
        contentInput.addEventListener('input', markDirty);
    }
    
    // Auto-save
    function autoSave() {
        if (!isDirty) return;
        
        saveStatus.textContent = '⏳ Salvando...';
        saveStatus.className = 'save-status saving';
        
        const formData = new FormData();
        formData.append('title', titleInput.value);
        formData.append('content', contentInput ? contentInput.value : getSpreadsheetData());
        formData.append('ajax', '1');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            saveStatus.textContent = '✅ Salvo';
            saveStatus.className = 'save-status saved';
            isDirty = false;
            
            setTimeout(() => {
                saveStatus.style.display = 'none';
            }, 2000);
        })
        .catch(error => {
            console.error('Erro ao salvar:', error);
        });
    }
    
    // Save manual
    if (saveBtn) {
        saveBtn.addEventListener('click', autoSave);
    }
    
    // Preparar para assinatura
    if (prepareBtn) {
        prepareBtn.addEventListener('click', function() {
            if (confirm('Preparar este documento para assinatura? Ele será convertido para PDF.')) {
                alert('Funcionalidade em desenvolvimento. O documento será convertido para PDF e adicionado à lista de assinaturas.');
            }
        });
    }
    
    // Funções de formatação (documento)
    window.formatText = function(format) {
        document.execCommand(format, false, null);
    };
    
    window.insertHeading = function() {
        if (contentInput) {
            const cursor = contentInput.selectionStart;
            const text = contentInput.value;
            contentInput.value = text.slice(0, cursor) + '\n\n## Título\n\n' + text.slice(cursor);
            markDirty();
        }
    };
    
    window.insertList = function() {
        if (contentInput) {
            const cursor = contentInput.selectionStart;
            const text = contentInput.value;
            contentInput.value = text.slice(0, cursor) + '\n\n- Item 1\n- Item 2\n- Item 3\n\n' + text.slice(cursor);
            markDirty();
        }
    };
    
    window.insertTable = function() {
        if (contentInput) {
            const cursor = contentInput.selectionStart;
            const text = contentInput.value;
            const table = '\n\n| Coluna 1 | Coluna 2 |\n|----------|----------|\n| Valor 1  | Valor 2  |\n\n';
            contentInput.value = text.slice(0, cursor) + table + text.slice(cursor);
            markDirty();
        }
    };
    
    // Funções de planilha
    window.addRow = function() {
        const tbody = document.getElementById('spreadsheet-body');
        if (!tbody) return;
        
        const rowCount = tbody.rows.length + 1;
        const newRow = tbody.insertRow();
        
        const th = document.createElement('th');
        th.textContent = rowCount;
        newRow.appendChild(th);
        
        for (let col = 0; col < 10; col++) {
            const td = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'spreadsheet-cell';
            input.dataset.row = rowCount;
            input.dataset.col = col;
            td.appendChild(input);
            newRow.appendChild(td);
        }
        
        markDirty();
    };
    
    window.addColumn = function() {
        alert('Adicionar coluna: Em desenvolvimento');
    };
    
    window.deleteRow = function() {
        const tbody = document.getElementById('spreadsheet-body');
        if (tbody && tbody.rows.length > 0) {
            tbody.deleteRow(-1);
            markDirty();
        }
    };
    
    window.insertFormula = function(formula) {
        alert(`Inserir fórmula ${formula}: Em desenvolvimento`);
    };
    
    function getSpreadsheetData() {
        const cells = document.querySelectorAll('.spreadsheet-cell');
        const data = [];
        
        cells.forEach(cell => {
            if (cell.value) {
                data.push({
                    row: cell.dataset.row,
                    col: cell.dataset.col,
                    value: cell.value
                });
            }
        });
        
        return JSON.stringify(data);
    }
    
    // Salvar ao sair
    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
})();
</script>

<?php get_footer(); ?>
