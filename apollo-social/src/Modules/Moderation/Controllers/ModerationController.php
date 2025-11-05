<?php

namespace Apollo\Modules\Moderation\Controllers;

use Apollo\Modules\Moderation\Services\ModerationService;

/**
 * Moderation Dashboard Controller
 * 
 * Handles moderation dashboard and AJAX requests.
 */
class ModerationController
{
    private ModerationService $moderationService;

    public function __construct()
    {
        $this->moderationService = new ModerationService();
        
        // Register AJAX handlers
        \add_action('wp_ajax_apollo_moderate_approve', [$this, 'handleApprove']);
        \add_action('wp_ajax_apollo_moderate_reject', [$this, 'handleReject']);
        \add_action('wp_ajax_apollo_moderation_queue', [$this, 'getModerationQueue']);
        \add_action('wp_ajax_apollo_moderation_stats', [$this, 'getModerationStats']);
    }

    /**
     * Render moderation dashboard
     */
    public function renderDashboard(): void
    {
        // Check permissions
        if (!current_user_can('apollo_moderate')) {
            \wp_die('Acesso negado');
        }

        $queue = $this->moderationService->getModerationQueue(['status' => 'pending']);
        $stats = $this->moderationService->getModerationStats();
        
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Painel de Moderação - Apollo Social</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8fafc;
                    color: #334155;
                }

                .dashboard-header {
                    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                    color: white;
                    padding: 30px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }

                .dashboard-header h1 {
                    font-size: 32px;
                    margin-bottom: 10px;
                }

                .dashboard-container {
                    max-width: 1400px;
                    margin: 0 auto;
                    padding: 30px;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-bottom: 40px;
                }

                .stat-card {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    border-left: 4px solid #3b82f6;
                }

                .stat-card h3 {
                    color: #64748b;
                    font-size: 14px;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .stat-card .stat-value {
                    font-size: 36px;
                    font-weight: 700;
                    color: #1e293b;
                    margin-bottom: 5px;
                }

                .stat-card .stat-label {
                    color: #64748b;
                    font-size: 14px;
                }

                .moderation-queue {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    overflow: hidden;
                }

                .queue-header {
                    background: #f1f5f9;
                    padding: 20px 30px;
                    border-bottom: 1px solid #e2e8f0;
                }

                .queue-header h2 {
                    color: #1e293b;
                    margin-bottom: 10px;
                }

                .queue-filters {
                    display: flex;
                    gap: 15px;
                    flex-wrap: wrap;
                }

                .filter-select {
                    padding: 8px 15px;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    background: white;
                    font-size: 14px;
                }

                .queue-item {
                    padding: 25px 30px;
                    border-bottom: 1px solid #f1f5f9;
                    transition: background 0.2s;
                }

                .queue-item:hover {
                    background: #f8fafc;
                }

                .item-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 15px;
                }

                .item-info h4 {
                    color: #1e293b;
                    font-size: 18px;
                    margin-bottom: 5px;
                }

                .item-meta {
                    display: flex;
                    gap: 15px;
                    color: #64748b;
                    font-size: 14px;
                }

                .priority-badge {
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                }

                .priority-high {
                    background: #fecaca;
                    color: #dc2626;
                }

                .priority-medium {
                    background: #fed7aa;
                    color: #ea580c;
                }

                .priority-normal {
                    background: #d1fae5;
                    color: #059669;
                }

                .item-content {
                    margin-bottom: 20px;
                    color: #475569;
                    line-height: 1.6;
                }

                .item-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                }

                .btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .btn-approve {
                    background: #10b981;
                    color: white;
                }

                .btn-approve:hover {
                    background: #059669;
                }

                .btn-reject {
                    background: #ef4444;
                    color: white;
                }

                .btn-reject:hover {
                    background: #dc2626;
                }

                .btn-details {
                    background: #6366f1;
                    color: white;
                }

                .btn-details:hover {
                    background: #4f46e5;
                }

                .empty-queue {
                    text-align: center;
                    padding: 60px 30px;
                    color: #64748b;
                }

                .empty-queue .icon {
                    font-size: 48px;
                    margin-bottom: 20px;
                }

                .modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 1000;
                }

                .modal-content {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 80%;
                    overflow-y: auto;
                }

                .modal-header {
                    padding: 20px 30px;
                    border-bottom: 1px solid #e2e8f0;
                }

                .modal-body {
                    padding: 30px;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #374151;
                }

                .form-group textarea {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-size: 14px;
                    resize: vertical;
                    min-height: 100px;
                }

                .form-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                    margin-top: 20px;
                }

                .btn-secondary {
                    background: #6b7280;
                    color: white;
                }

                .btn-secondary:hover {
                    background: #4b5563;
                }

                @media (max-width: 768px) {
                    .dashboard-container {
                        padding: 20px 15px;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .item-header {
                        flex-direction: column;
                        gap: 10px;
                    }
                    
                    .item-actions {
                        justify-content: flex-start;
                    }
                }
            </style>
        </head>
        <body>
            <div class="dashboard-header">
                <h1>🛡️ Painel de Moderação</h1>
                <p>Gerencie aprovações e revisões de conteúdo</p>
            </div>

            <div class="dashboard-container">
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Pendentes</h3>
                        <div class="stat-value"><?php echo esc_html($stats['pending']); ?></div>
                        <div class="stat-label">Aguardando revisão</div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Aprovados (30d)</h3>
                        <div class="stat-value"><?php echo esc_html($stats['approved']); ?></div>
                        <div class="stat-label">Itens aprovados</div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Rejeitados (30d)</h3>
                        <div class="stat-value"><?php echo esc_html($stats['rejected']); ?></div>
                        <div class="stat-label">Itens rejeitados</div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Tempo Médio</h3>
                        <div class="stat-value"><?php echo esc_html(round($stats['avg_processing_time'], 1)); ?>h</div>
                        <div class="stat-label">Para processamento</div>
                    </div>
                </div>

                <!-- Moderation Queue -->
                <div class="moderation-queue">
                    <div class="queue-header">
                        <h2>📋 Fila de Moderação</h2>
                        <div class="queue-filters">
                            <select class="filter-select" id="entityTypeFilter">
                                <option value="">Todos os tipos</option>
                                <option value="group">Grupos</option>
                                <option value="nucleo">Núcleos</option>
                                <option value="event">Eventos</option>
                                <option value="ad">Anúncios</option>
                            </select>
                            
                            <select class="filter-select" id="priorityFilter">
                                <option value="">Todas as prioridades</option>
                                <option value="high">Alta</option>
                                <option value="medium">Média</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                    </div>

                    <div id="queueContent">
                        <?php if (empty($queue)): ?>
                            <div class="empty-queue">
                                <div class="icon">🎉</div>
                                <h3>Nenhum item pendente!</h3>
                                <p>Todos os itens foram revisados.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($queue as $item): ?>
                                <div class="queue-item" data-id="<?php echo esc_attr($item['id']); ?>">
                                    <div class="item-header">
                                        <div class="item-info">
                                            <h4>
                                                <?php echo esc_html($item['entity_data']['name'] ?? $item['entity_data']['title'] ?? 'Item sem título'); ?>
                                            </h4>
                                            <div class="item-meta">
                                                <span>📁 <?php echo esc_html(ucfirst($item['entity_type'])); ?></span>
                                                <span>👤 <?php echo esc_html($item['submitter_data']['name'] ?? 'Usuário desconhecido'); ?></span>
                                                <span>📅 <?php echo esc_html(date('d/m/Y H:i', strtotime($item['submitted_at']))); ?></span>
                                            </div>
                                        </div>
                                        <span class="priority-badge priority-<?php echo esc_attr($item['priority']); ?>">
                                            <?php echo esc_html(ucfirst($item['priority'])); ?>
                                        </span>
                                    </div>

                                    <div class="item-content">
                                        <?php 
                                        $submission_data = json_decode($item['submission_data'], true);
                                        echo esc_html($submission_data['reason'] ?? 'Sem descrição disponível.');
                                        ?>
                                    </div>

                                    <div class="item-actions">
                                        <button class="btn btn-details" onclick="viewDetails(<?php echo esc_attr($item['id']); ?>)">
                                            👁️ Detalhes
                                        </button>
                                        <button class="btn btn-approve" onclick="showApprovalModal(<?php echo esc_attr($item['id']); ?>)">
                                            ✅ Aprovar
                                        </button>
                                        <button class="btn btn-reject" onclick="showRejectionModal(<?php echo esc_attr($item['id']); ?>)">
                                            ❌ Rejeitar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approval Modal -->
            <div id="approvalModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✅ Aprovar Item</h3>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Notas de Aprovação (opcional)</label>
                            <textarea id="approvalNotes" placeholder="Adicione comentários sobre a aprovação..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-secondary" onclick="closeModals()">Cancelar</button>
                            <button class="btn btn-approve" onclick="processApproval()">Confirmar Aprovação</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div id="rejectionModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>❌ Rejeitar Item</h3>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Motivo da Rejeição *</label>
                            <textarea id="rejectionReason" placeholder="Descreva o motivo da rejeição..." required></textarea>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-secondary" onclick="closeModals()">Cancelar</button>
                            <button class="btn btn-reject" onclick="processRejection()">Confirmar Rejeição</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let currentModerationId = null;

                function showApprovalModal(moderationId) {
                    currentModerationId = moderationId;
                    document.getElementById('approvalModal').style.display = 'block';
                }

                function showRejectionModal(moderationId) {
                    currentModerationId = moderationId;
                    document.getElementById('rejectionModal').style.display = 'block';
                }

                function closeModals() {
                    document.getElementById('approvalModal').style.display = 'none';
                    document.getElementById('rejectionModal').style.display = 'none';
                    currentModerationId = null;
                    
                    // Clear form fields
                    document.getElementById('approvalNotes').value = '';
                    document.getElementById('rejectionReason').value = '';
                }

                async function processApproval() {
                    const notes = document.getElementById('approvalNotes').value;
                    
                    try {
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'apollo_moderate_approve',
                                moderation_id: currentModerationId,
                                notes: notes,
                                nonce: '<?php echo wp_create_nonce('apollo_moderation'); ?>'
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('✅ Item aprovado com sucesso!');
                            location.reload();
                        } else {
                            alert('❌ Erro: ' + (result.data.error || 'Erro desconhecido'));
                        }
                        
                    } catch (error) {
                        alert('❌ Erro de conexão: ' + error.message);
                    }
                    
                    closeModals();
                }

                async function processRejection() {
                    const reason = document.getElementById('rejectionReason').value.trim();
                    
                    if (!reason) {
                        alert('Por favor, informe o motivo da rejeição.');
                        return;
                    }
                    
                    try {
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'apollo_moderate_reject',
                                moderation_id: currentModerationId,
                                reason: reason,
                                nonce: '<?php echo wp_create_nonce('apollo_moderation'); ?>'
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('❌ Item rejeitado com sucesso!');
                            location.reload();
                        } else {
                            alert('❌ Erro: ' + (result.data.error || 'Erro desconhecido'));
                        }
                        
                    } catch (error) {
                        alert('❌ Erro de conexão: ' + error.message);
                    }
                    
                    closeModals();
                }

                function viewDetails(moderationId) {
                    // Open detailed view in new tab or modal
                    window.open(`/apollo/moderation/details/${moderationId}`, '_blank');
                }

                // Filter functionality
                document.getElementById('entityTypeFilter').addEventListener('change', filterQueue);
                document.getElementById('priorityFilter').addEventListener('change', filterQueue);

                function filterQueue() {
                    const entityType = document.getElementById('entityTypeFilter').value;
                    const priority = document.getElementById('priorityFilter').value;
                    
                    const items = document.querySelectorAll('.queue-item');
                    
                    items.forEach(item => {
                        const itemData = {
                            entityType: item.querySelector('.item-meta span').textContent.replace('📁 ', '').toLowerCase(),
                            priority: item.querySelector('.priority-badge').textContent.toLowerCase()
                        };
                        
                        const showItem = (
                            (!entityType || itemData.entityType === entityType) &&
                            (!priority || itemData.priority === priority)
                        );
                        
                        item.style.display = showItem ? 'block' : 'none';
                    });
                }

                // Close modals when clicking outside
                window.addEventListener('click', function(event) {
                    const approvalModal = document.getElementById('approvalModal');
                    const rejectionModal = document.getElementById('rejectionModal');
                    
                    if (event.target === approvalModal || event.target === rejectionModal) {
                        closeModals();
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Handle approval AJAX request
     */
    public function handleApprove(): void
    {
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'apollo_moderation')) {
            \wp_die('Security check failed');
        }

        if (!\current_user_can('apollo_moderate')) {
            \wp_send_json_error(['error' => 'Permissão negada']);
        }

        $moderation_id = intval($_POST['moderation_id'] ?? 0);
        $notes = \sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$moderation_id) {
            \wp_send_json_error(['error' => 'ID de moderação inválido']);
        }

        $result = $this->moderationService->approve($moderation_id, \get_current_user_id(), $notes);

        if ($result['success']) {
            \wp_send_json_success($result);
        } else {
            \wp_send_json_error($result);
        }
    }

    /**
     * Handle rejection AJAX request
     */
    public function handleReject(): void
    {
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'apollo_moderation')) {
            \wp_die('Security check failed');
        }

        if (!\current_user_can('apollo_moderate')) {
            \wp_send_json_error(['error' => 'Permissão negada']);
        }

        $moderation_id = intval($_POST['moderation_id'] ?? 0);
        $reason = \sanitize_textarea_field($_POST['reason'] ?? '');

        if (!$moderation_id || !$reason) {
            \wp_send_json_error(['error' => 'Dados obrigatórios não fornecidos']);
        }

        $result = $this->moderationService->reject($moderation_id, \get_current_user_id(), $reason);

        if ($result['success']) {
            \wp_send_json_success($result);
        } else {
            \wp_send_json_error($result);
        }
    }

    /**
     * Get moderation queue via AJAX
     */
    public function getModerationQueue(): void
    {
        if (!\current_user_can('apollo_moderate')) {
            \wp_send_json_error(['error' => 'Permissão negada']);
        }

        $filters = [
            'status' => \sanitize_text_field($_POST['status'] ?? 'pending'),
            'entity_type' => \sanitize_text_field($_POST['entity_type'] ?? ''),
            'priority' => \sanitize_text_field($_POST['priority'] ?? '')
        ];

        $queue = $this->moderationService->getModerationQueue($filters);
        \wp_send_json_success($queue);
    }

    /**
     * Get moderation stats via AJAX
     */
    public function getModerationStats(): void
    {
        if (!\current_user_can('apollo_moderate')) {
            \wp_send_json_error(['error' => 'Permissão negada']);
        }

        $stats = $this->moderationService->getModerationStats();
        \wp_send_json_success($stats);
    }
}