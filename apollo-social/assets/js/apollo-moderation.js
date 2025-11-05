/**
 * Apollo Moderation JavaScript
 * Handles moderation actions via REST API with proper error handling
 */

class ApolloModeration {
    constructor() {
        this.apiBase = '/wp-json/apollo/v1';
        this.nonce = window.apolloNonce || '';
        this.init();
    }
    
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.bindEvents();
        });
    }
    
    bindEvents() {
        // Approve buttons
        document.querySelectorAll('.apollo-approve-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleApprove(e));
        });
        
        // Reject buttons
        document.querySelectorAll('.apollo-reject-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleReject(e));
        });
        
        // Confirm reject buttons
        document.querySelectorAll('.apollo-confirm-reject').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleConfirmReject(e));
        });
        
        // Resubmit buttons
        document.querySelectorAll('.apollo-resubmit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleResubmit(e));
        });
        
        // Submit for review buttons
        document.querySelectorAll('.apollo-submit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleSubmitForReview(e));
        });
        
        // Modal close handlers
        document.querySelectorAll('.apollo-modal-close, .apollo-modal-cancel').forEach(btn => {
            btn.addEventListener('click', (e) => this.closeModal(e));
        });
        
        // Click outside modal to close
        document.querySelectorAll('.apollo-rejection-modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(e);
                }
            });
        });
    }
    
    async handleApprove(e) {
        e.preventDefault();
        
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        
        if (!confirm('Tem certeza que deseja aprovar este grupo?')) {
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await this.apiCall(`/groups/${groupId}/approve`, 'POST');
            
            if (response.success) {
                this.showToast('Grupo aprovado com sucesso!', 'success');
                this.updateGroupStatus(groupId, 'published');
                this.hideModeration(groupId);
            } else {
                this.showToast(response.message || 'Erro ao aprovar grupo', 'error');
            }
        } catch (error) {
            console.error('Approve error:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    }
    
    handleReject(e) {
        e.preventDefault();
        
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        const modal = document.getElementById(`apollo-rejection-modal-${groupId}`);
        
        if (modal) {
            modal.style.display = 'flex';
            
            // Focus on textarea
            const textarea = modal.querySelector('.apollo-rejection-textarea');
            if (textarea) {
                setTimeout(() => textarea.focus(), 100);
            }
        }
    }
    
    async handleConfirmReject(e) {
        e.preventDefault();
        
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        const reasonTextarea = document.getElementById(`apollo-rejection-reason-${groupId}`);
        const reason = reasonTextarea ? reasonTextarea.value.trim() : '';
        
        if (!reason) {
            this.showToast('Por favor, informe o motivo da rejeição.', 'warning');
            if (reasonTextarea) reasonTextarea.focus();
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await this.apiCall(`/groups/${groupId}/reject`, 'POST', {
                reason: reason
            });
            
            if (response.success) {
                this.showToast('Grupo rejeitado com sucesso!', 'success');
                this.updateGroupStatus(groupId, 'rejected', response.standard_message);
                this.hideModeration(groupId);
                this.closeModalById(groupId);
            } else {
                this.showToast(response.message || 'Erro ao rejeitar grupo', 'error');
            }
        } catch (error) {
            console.error('Reject error:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    }
    
    async handleResubmit(e) {
        e.preventDefault();
        
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        
        if (!confirm('Deseja mover este grupo para rascunho para edição?')) {
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await this.apiCall(`/groups/${groupId}/resubmit`, 'POST');
            
            if (response.success) {
                this.showToast(response.message, 'success');
                
                // Redirect to edit page if provided
                if (response.redirect_url) {
                    setTimeout(() => {
                        window.location.href = response.redirect_url;
                    }, 1500);
                } else {
                    this.updateGroupStatus(groupId, 'draft');
                }
            } else {
                this.showToast(response.message || 'Erro ao reenviar grupo', 'error');
            }
        } catch (error) {
            console.error('Resubmit error:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    }
    
    async handleSubmitForReview(e) {
        e.preventDefault();
        
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        
        if (!confirm('Enviar este grupo para revisão?')) {
            return;
        }
        
        // TODO: Implement submit for review API endpoint
        this.showToast('Funcionalidade em desenvolvimento', 'info');
    }
    
    closeModal(e) {
        const modal = e.target.closest('.apollo-rejection-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    closeModalById(groupId) {
        const modal = document.getElementById(`apollo-rejection-modal-${groupId}`);
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    updateGroupStatus(groupId, newStatus, rejectionMessage = null) {
        // Update status badge
        const statusBadge = document.querySelector(`[data-group-id="${groupId}"] .apollo-status-badge`);
        if (statusBadge) {
            // Remove old status classes
            statusBadge.className = statusBadge.className.replace(/apollo-status-\w+/g, '');
            statusBadge.classList.add(`apollo-status-${newStatus}`);
            
            // Update label
            const label = statusBadge.querySelector('.apollo-status-label');
            if (label) {
                const labels = {
                    'draft': 'Rascunho',
                    'pending': 'Aguardando',
                    'pending_review': 'Em Análise',
                    'published': 'Publicado',
                    'rejected': 'Rejeitado'
                };
                label.textContent = labels[newStatus] || 'Desconhecido';
            }
            
            // Add rejection notice if rejected
            if (newStatus === 'rejected' && rejectionMessage) {
                this.addRejectionNotice(statusBadge, rejectionMessage, groupId);
            } else {
                // Remove existing rejection notice
                const existingNotice = statusBadge.querySelector('.apollo-rejection-notice');
                if (existingNotice) {
                    existingNotice.remove();
                }
            }
        }
        
        // Update action buttons
        this.updateActionButtons(groupId, newStatus);
    }
    
    addRejectionNotice(statusBadge, message, groupId) {
        // Remove existing notice
        const existingNotice = statusBadge.querySelector('.apollo-rejection-notice');
        if (existingNotice) {
            existingNotice.remove();
        }
        
        // Create new notice
        const notice = document.createElement('div');
        notice.className = 'apollo-rejection-notice';
        notice.innerHTML = `
            <div class="apollo-rejection-message">
                ${message}
            </div>
            <div class="apollo-rejection-actions">
                <button type="button" class="apollo-btn apollo-btn-secondary apollo-resubmit-btn" 
                        data-group-id="${groupId}">
                    Revisar e Reenviar
                </button>
            </div>
        `;
        
        statusBadge.appendChild(notice);
        
        // Bind event to new resubmit button
        const resubmitBtn = notice.querySelector('.apollo-resubmit-btn');
        if (resubmitBtn) {
            resubmitBtn.addEventListener('click', (e) => this.handleResubmit(e));
        }
    }
    
    updateActionButtons(groupId, status) {
        const card = document.querySelector(`[data-group-id="${groupId}"]`);
        const actionsDiv = card?.querySelector('.apollo-group-actions');
        
        if (!actionsDiv) return;
        
        // Clear existing buttons
        actionsDiv.innerHTML = '';
        
        // Add appropriate buttons based on status
        if (status === 'draft') {
            actionsDiv.innerHTML = `
                <a href="/grupo/editar/${groupId}/" class="apollo-btn apollo-btn-primary">
                    Continuar Editando
                </a>
                <button type="button" class="apollo-btn apollo-btn-secondary apollo-submit-btn" 
                        data-group-id="${groupId}">
                    Enviar para Revisão
                </button>
            `;
        } else if (status === 'published') {
            actionsDiv.innerHTML = `
                <a href="/grupo/${groupId}/" class="apollo-btn apollo-btn-success">
                    Ver Grupo
                </a>
                <a href="/grupo/editar/${groupId}/" class="apollo-btn apollo-btn-secondary">
                    Editar
                </a>
            `;
        }
        
        // Rebind events for new buttons
        this.bindEvents();
    }
    
    hideModeration(groupId) {
        const moderation = document.querySelector(`[data-group-id="${groupId}"] .apollo-moderation-actions`);
        if (moderation) {
            moderation.style.display = 'none';
        }
    }
    
    setButtonLoading(btn, loading) {
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Processando...';
            btn.classList.add('apollo-loading');
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || btn.textContent;
            btn.classList.remove('apollo-loading');
        }
    }
    
    showToast(message, type = 'info') {
        // Create toast if doesn't exist
        let toastContainer = document.getElementById('apollo-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'apollo-toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `;
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        const typeColors = {
            'success': '#10b981',
            'error': '#dc2626',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        
        toast.style.cssText = `
            background: ${typeColors[type] || typeColors.info};
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        toast.textContent = message;
        
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }
    
    async apiCall(endpoint, method = 'GET', data = null) {
        const url = `${this.apiBase}${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ApolloModeration();
    });
} else {
    new ApolloModeration();
}