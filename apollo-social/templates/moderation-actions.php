<?php
/**
 * Moderation Actions Template
 * Shows approve/reject buttons for editors and admins
 */
?>

<div class="apollo-moderation-actions" data-group-id="<?php echo esc_attr($group_id); ?>">
    <div class="apollo-moderation-header">
        <h4>Ações de Moderação</h4>
        <span class="apollo-group-status">Status: <?php echo esc_html($status); ?></span>
    </div>
    
    <div class="apollo-moderation-buttons">
        <button type="button" class="apollo-btn apollo-btn-success apollo-approve-btn"
                data-group-id="<?php echo esc_attr($group_id); ?>">
            ✓ Aprovar
        </button>
        
        <button type="button" class="apollo-btn apollo-btn-danger apollo-reject-btn"
                data-group-id="<?php echo esc_attr($group_id); ?>">
            ✗ Rejeitar
        </button>
    </div>
    
    <!-- Rejection reason modal (hidden by default) -->
    <div class="apollo-rejection-modal" id="apollo-rejection-modal-<?php echo esc_attr($group_id); ?>" style="display: none;">
        <div class="apollo-modal-content">
            <div class="apollo-modal-header">
                <h5>Rejeitar Grupo</h5>
                <span class="apollo-modal-close">&times;</span>
            </div>
            
            <div class="apollo-modal-body">
                <p>Motivo da rejeição:</p>
                <textarea id="apollo-rejection-reason-<?php echo esc_attr($group_id); ?>" 
                          class="apollo-rejection-textarea"
                          placeholder="Explique o motivo da rejeição..."
                          rows="4"></textarea>
                
                <div class="apollo-rejection-examples">
                    <p><strong>Exemplos de motivos:</strong></p>
                    <ul>
                        <li>Conteúdo inadequado</li>
                        <li>Informações incompletas</li>
                        <li>Não atende aos critérios da comunidade</li>
                        <li>Duplicação de grupo existente</li>
                    </ul>
                </div>
            </div>
            
            <div class="apollo-modal-footer">
                <button type="button" class="apollo-btn apollo-btn-secondary apollo-modal-cancel">
                    Cancelar
                </button>
                <button type="button" class="apollo-btn apollo-btn-danger apollo-confirm-reject"
                        data-group-id="<?php echo esc_attr($group_id); ?>">
                    Confirmar Rejeição
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.apollo-moderation-actions {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
    margin: 16px 0;
}

.apollo-moderation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.apollo-moderation-header h4 {
    margin: 0;
    font-size: 16px;
    color: #374151;
}

.apollo-group-status {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.apollo-moderation-buttons {
    display: flex;
    gap: 8px;
}

.apollo-btn-danger {
    background-color: #dc2626;
    color: white;
}

.apollo-btn-danger:hover {
    background-color: #b91c1c;
}

/* Modal Styles */
.apollo-rejection-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.apollo-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.apollo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.apollo-modal-header h5 {
    margin: 0;
    font-size: 18px;
    color: #111827;
}

.apollo-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
}

.apollo-modal-close:hover {
    color: #374151;
}

.apollo-modal-body {
    padding: 20px;
}

.apollo-rejection-textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 8px 12px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 16px;
}

.apollo-rejection-examples {
    font-size: 13px;
    color: #6b7280;
}

.apollo-rejection-examples ul {
    margin: 8px 0;
    padding-left: 20px;
}

.apollo-modal-footer {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* Responsive */
@media (max-width: 640px) {
    .apollo-moderation-buttons {
        flex-direction: column;
    }
    
    .apollo-modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .apollo-modal-footer {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve button handler
    document.querySelectorAll('.apollo-approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const groupId = this.dataset.groupId;
            
            if (confirm('Tem certeza que deseja aprovar este grupo?')) {
                apolloModerateGroup(groupId, 'approve');
            }
        });
    });
    
    // Reject button handler
    document.querySelectorAll('.apollo-reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const groupId = this.dataset.groupId;
            const modal = document.getElementById(`apollo-rejection-modal-${groupId}`);
            modal.style.display = 'flex';
        });
    });
    
    // Modal close handlers
    document.querySelectorAll('.apollo-modal-close, .apollo-modal-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.apollo-rejection-modal');
            modal.style.display = 'none';
        });
    });
    
    // Confirm rejection handler
    document.querySelectorAll('.apollo-confirm-reject').forEach(btn => {
        btn.addEventListener('click', function() {
            const groupId = this.dataset.groupId;
            const reason = document.getElementById(`apollo-rejection-reason-${groupId}`).value.trim();
            
            if (!reason) {
                alert('Por favor, informe o motivo da rejeição.');
                return;
            }
            
            apolloModerateGroup(groupId, 'reject', reason);
        });
    });
});

function apolloModerateGroup(groupId, action, reason = '') {
    // TODO: Implement AJAX call to moderation endpoint
    console.log('Moderating group:', groupId, action, reason);
    
    // For now, just show success message
    if (action === 'approve') {
        alert('Grupo aprovado com sucesso!');
    } else {
        alert('Grupo rejeitado com sucesso!');
        // Close modal
        document.getElementById(`apollo-rejection-modal-${groupId}`).style.display = 'none';
    }
    
    // TODO: Refresh page or update UI
    location.reload();
}
</script>