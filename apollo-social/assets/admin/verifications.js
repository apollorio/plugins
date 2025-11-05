/**
 * Apollo Verifications Admin JavaScript
 * Handles verification management, modals, and interactions
 */

class ApolloVerificationsAdmin {
    constructor() {
        this.currentModal = null;
        this.currentFilters = {};
        this.cache = new Map();
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadInitialData();
        this.setupKeyboardNavigation();
    }
    
    bindEvents() {
        // Filter form submission
        const filterForm = document.getElementById('apollo-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
        
        // Real-time search
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }
        
        // Status filter change
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.applyFilters();
            });
        }
        
        // Card interactions
        this.bindCardEvents();
        
        // Modal events
        this.bindModalEvents();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
        
        // Auto-refresh
        this.setupAutoRefresh();
    }
    
    bindCardEvents() {
        // View details
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-view-details') || e.target.closest('.btn-view-details')) {
                e.preventDefault();
                const userId = e.target.closest('[data-user-id]')?.dataset.userId;
                if (userId) {
                    this.openVerificationModal(userId);
                }
            }
        });
        
        // Quick approve
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-quick-approve') || e.target.closest('.btn-quick-approve')) {
                e.preventDefault();
                const userId = e.target.closest('[data-user-id]')?.dataset.userId;
                if (userId) {
                    this.quickApprove(userId);
                }
            }
        });
        
        // Quick reject
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-quick-reject') || e.target.closest('.btn-quick-reject')) {
                e.preventDefault();
                const userId = e.target.closest('[data-user-id]')?.dataset.userId;
                if (userId) {
                    this.quickReject(userId);
                }
            }
        });
    }
    
    bindModalEvents() {
        // Close modal
        document.addEventListener('click', (e) => {
            if (e.target.matches('.apollo-modal-close') || 
                e.target.closest('.apollo-modal-close') ||
                (e.target.matches('.apollo-modal') && !e.target.closest('.apollo-modal-content'))) {
                this.closeModal();
            }
        });
        
        // Approve button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-modal-approve') || e.target.closest('.btn-modal-approve')) {
                e.preventDefault();
                const userId = document.querySelector('.apollo-modal')?.dataset.userId;
                if (userId) {
                    this.approveVerification(userId);
                }
            }
        });
        
        // Reject button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-modal-reject') || e.target.closest('.btn-modal-reject')) {
                e.preventDefault();
                const userId = document.querySelector('.apollo-modal')?.dataset.userId;
                const reason = document.getElementById('rejection-reason')?.value;
                if (userId) {
                    this.rejectVerification(userId, reason);
                }
            }
        });
    }
    
    async loadInitialData() {
        try {
            await this.loadVerifications();
            await this.loadStats();
        } catch (error) {
            console.error('Failed to load initial data:', error);
            this.showNotification('Erro ao carregar dados iniciais', 'error');
        }
    }
    
    async loadVerifications(filters = {}) {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'apollo_get_verifications',
                nonce: apolloAdmin.nonce,
                ...filters
            });
            
            const response = await fetch(apolloAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderVerifications(data.data.verifications);
                this.updatePagination(data.data.pagination);
            } else {
                throw new Error(data.message || 'Erro ao carregar verificações');
            }
        } catch (error) {
            console.error('Error loading verifications:', error);
            this.showNotification('Erro ao carregar verificações', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadStats() {
        try {
            const params = new URLSearchParams({
                action: 'apollo_get_verification_stats',
                nonce: apolloAdmin.nonce
            });
            
            const response = await fetch(apolloAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateStats(data.data);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }
    
    renderVerifications(verifications) {
        const container = document.getElementById('verifications-grid');
        if (!container) return;
        
        if (verifications.length === 0) {
            container.innerHTML = `
                <div class="apollo-empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>Nenhuma verificação encontrada</h3>
                    <p>Não há verificações para os filtros selecionados.</p>
                </div>
            `;
            return;
        }
        
        const cards = verifications.map(verification => this.createVerificationCard(verification));
        container.innerHTML = cards.join('');
    }
    
    createVerificationCard(verification) {
        const statusClass = `status-${verification.verify_status.replace('_', '-')}`;
        const statusLabel = this.getStatusLabel(verification.verify_status);
        const userInitials = this.getUserInitials(verification.display_name || verification.user_login);
        const hasAssets = verification.verify_assets && verification.verify_assets.length > 0;
        
        return `
            <div class="apollo-verification-card ${statusClass}" data-user-id="${verification.user_id}">
                <div class="card-header">
                    <div class="user-info">
                        <div class="user-avatar">${userInitials}</div>
                        <div class="user-details">
                            <h4 class="user-name">${this.escapeHtml(verification.display_name || verification.user_login)}</h4>
                            <div class="user-meta">
                                <span>@${this.escapeHtml(verification.instagram_username)}</span>
                                <span class="separator">•</span>
                                <span>${this.formatDate(verification.submitted_at)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="status-badge ${statusClass}">
                        ${statusLabel}
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="verification-info">
                        <div class="info-row">
                            <span class="label">WhatsApp:</span>
                            <span class="value">${this.escapeHtml(verification.whatsapp_number)}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Token:</span>
                            <span class="value token">${this.escapeHtml(verification.verify_token)}</span>
                        </div>
                        ${verification.metadata?.industry ? `
                            <div class="info-row">
                                <span class="label">Indústria:</span>
                                <span class="value">${this.escapeHtml(verification.metadata.industry)}</span>
                            </div>
                        ` : ''}
                    </div>
                    
                    ${hasAssets ? `
                        <div class="verification-assets">
                            <h5>Assets (${verification.verify_assets.length})</h5>
                            <div class="assets-preview">
                                ${verification.verify_assets.slice(0, 3).map((asset, index) => `
                                    <div class="asset-thumbnail" title="${this.escapeHtml(asset.original_name)}">
                                        📷
                                    </div>
                                `).join('')}
                                ${verification.verify_assets.length > 3 ? `
                                    <div class="asset-more">+${verification.verify_assets.length - 3}</div>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                <div class="card-actions">
                    <button class="apollo-btn small primary btn-view-details">
                        <span class="btn-icon">👁️</span>
                        Ver Detalhes
                    </button>
                    ${verification.verify_status === 'assets_submitted' ? `
                        <button class="apollo-btn small success btn-quick-approve">
                            <span class="btn-icon">✅</span>
                            Aprovar
                        </button>
                        <button class="apollo-btn small danger btn-quick-reject">
                            <span class="btn-icon">❌</span>
                            Rejeitar
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    async openVerificationModal(userId) {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'apollo_get_verification_details',
                nonce: apolloAdmin.nonce,
                user_id: userId
            });
            
            const response = await fetch(apolloAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showVerificationModal(data.data);
            } else {
                throw new Error(data.message || 'Erro ao carregar detalhes');
            }
        } catch (error) {
            console.error('Error loading verification details:', error);
            this.showNotification('Erro ao carregar detalhes da verificação', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    showVerificationModal(verification) {
        const modal = document.createElement('div');
        modal.className = 'apollo-modal';
        modal.dataset.userId = verification.user_id;
        
        const statusClass = `status-${verification.verify_status.replace('_', '-')}`;
        const statusLabel = this.getStatusLabel(verification.verify_status);
        const hasAssets = verification.verify_assets && verification.verify_assets.length > 0;
        
        modal.innerHTML = `
            <div class="apollo-modal-content">
                <div class="apollo-modal-header">
                    <h3>Verificação - ${this.escapeHtml(verification.display_name || verification.user_login)}</h3>
                    <button class="apollo-modal-close" aria-label="Fechar">×</button>
                </div>
                
                <div class="apollo-modal-body">
                    <div class="verification-details">
                        <div class="user-profile">
                            <h4>Perfil do Usuário</h4>
                            <p><strong>Nome:</strong> ${this.escapeHtml(verification.metadata?.name || 'N/A')}</p>
                            <p><strong>Username:</strong> ${this.escapeHtml(verification.user_login)}</p>
                            <p><strong>Email:</strong> ${this.escapeHtml(verification.user_email)}</p>
                            <p><strong>Instagram:</strong> <code>@${this.escapeHtml(verification.instagram_username)}</code></p>
                            <p><strong>WhatsApp:</strong> <code>${this.escapeHtml(verification.whatsapp_number)}</code></p>
                            <p><strong>Token:</strong> <code>${this.escapeHtml(verification.verify_token)}</code></p>
                            <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${statusLabel}</span></p>
                            <p><strong>Submetido em:</strong> ${this.formatDateTime(verification.submitted_at)}</p>
                            ${verification.reviewed_at ? `
                                <p><strong>Revisado em:</strong> ${this.formatDateTime(verification.reviewed_at)}</p>
                            ` : ''}
                        </div>
                        
                        ${verification.metadata ? `
                            <div class="metadata-section">
                                <h5>Dados do Onboarding</h5>
                                <p><strong>Indústria:</strong> ${this.escapeHtml(verification.metadata.industry || 'N/A')}</p>
                                ${verification.metadata.roles ? `
                                    <p><strong>Funções:</strong> ${JSON.parse(verification.metadata.roles || '[]').join(', ')}</p>
                                ` : ''}
                                ${verification.metadata.member_of ? `
                                    <p><strong>Membro de:</strong> ${JSON.parse(verification.metadata.member_of || '[]').join(', ')}</p>
                                ` : ''}
                                <p><strong>IP:</strong> <code>${this.escapeHtml(verification.metadata.ip_address || 'N/A')}</code></p>
                                <p><strong>User Agent:</strong> <small>${this.escapeHtml(verification.metadata.user_agent || 'N/A')}</small></p>
                            </div>
                        ` : ''}
                        
                        ${hasAssets ? `
                            <div class="assets-section">
                                <h5>Assets de Verificação (${verification.verify_assets.length})</h5>
                                <div class="assets-grid">
                                    ${verification.verify_assets.map((asset, index) => `
                                        <div class="asset-detail">
                                            <p><strong>Arquivo ${index + 1}:</strong> ${this.escapeHtml(asset.original_name)}</p>
                                            <p><strong>Tamanho:</strong> ${this.formatFileSize(asset.file_size)}</p>
                                            <p><strong>Dimensões:</strong> ${asset.dimensions?.width || 0} × ${asset.dimensions?.height || 0}px</p>
                                            <p><strong>Tipo:</strong> ${this.escapeHtml(asset.mime_type)}</p>
                                            <p><strong>Upload:</strong> ${this.formatDateTime(asset.uploaded_at)}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        ${verification.rejection_reason ? `
                            <div class="rejection-section">
                                <h5>Motivo da Rejeição</h5>
                                <p>${this.escapeHtml(verification.rejection_reason)}</p>
                            </div>
                        ` : ''}
                        
                        ${verification.verify_status === 'assets_submitted' ? `
                            <div class="modal-actions">
                                <div class="reject-section">
                                    <label for="rejection-reason">Motivo da rejeição (opcional):</label>
                                    <textarea id="rejection-reason" class="apollo-textarea" placeholder="Digite o motivo da rejeição..."></textarea>
                                    <button class="apollo-btn danger btn-modal-reject">
                                        <span class="btn-icon">❌</span>
                                        Rejeitar Verificação
                                    </button>
                                </div>
                                <div>
                                    <button class="apollo-btn success btn-modal-approve">
                                        <span class="btn-icon">✅</span>
                                        Aprovar Verificação
                                    </button>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        this.currentModal = modal;
        
        // Focus management
        setTimeout(() => {
            const closeButton = modal.querySelector('.apollo-modal-close');
            if (closeButton) closeButton.focus();
        }, 100);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    closeModal() {
        if (this.currentModal) {
            document.body.removeChild(this.currentModal);
            this.currentModal = null;
            document.body.style.overflow = '';
        }
    }
    
    async quickApprove(userId) {
        if (!confirm('Tem certeza que deseja aprovar esta verificação?')) {
            return;
        }
        
        await this.approveVerification(userId);
    }
    
    async quickReject(userId) {
        const reason = prompt('Motivo da rejeição (opcional):');
        if (reason === null) return; // User cancelled
        
        await this.rejectVerification(userId, reason);
    }
    
    async approveVerification(userId) {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'apollo_approve_verification',
                nonce: apolloAdmin.nonce,
                user_id: userId
            });
            
            const response = await fetch(apolloAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Verificação aprovada com sucesso!', 'success');
                this.closeModal();
                await this.loadVerifications(this.currentFilters);
                await this.loadStats();
            } else {
                throw new Error(data.message || 'Erro ao aprovar verificação');
            }
        } catch (error) {
            console.error('Error approving verification:', error);
            this.showNotification('Erro ao aprovar verificação', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async rejectVerification(userId, reason = '') {
        try {
            this.showLoading(true);
            
            const params = new URLSearchParams({
                action: 'apollo_reject_verification',
                nonce: apolloAdmin.nonce,
                user_id: userId,
                reason: reason
            });
            
            const response = await fetch(apolloAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Verificação rejeitada', 'warning');
                this.closeModal();
                await this.loadVerifications(this.currentFilters);
                await this.loadStats();
            } else {
                throw new Error(data.message || 'Erro ao rejeitar verificação');
            }
        } catch (error) {
            console.error('Error rejecting verification:', error);
            this.showNotification('Erro ao rejeitar verificação', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    applyFilters() {
        const form = document.getElementById('apollo-filter-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const filters = Object.fromEntries(formData.entries());
        
        // Remove empty values
        Object.keys(filters).forEach(key => {
            if (!filters[key]) delete filters[key];
        });
        
        this.currentFilters = filters;
        this.loadVerifications(filters);
    }
    
    performSearch(query) {
        this.currentFilters.search = query;
        this.loadVerifications(this.currentFilters);
    }
    
    updateStats(stats) {
        document.getElementById('stat-awaiting').textContent = stats.awaiting_instagram_verify || 0;
        document.getElementById('stat-submitted').textContent = stats.assets_submitted || 0;
        document.getElementById('stat-verified').textContent = stats.verified || 0;
        document.getElementById('stat-rejected').textContent = stats.rejected || 0;
        document.getElementById('stat-total').textContent = stats.total || 0;
    }
    
    updatePagination(pagination) {
        // Implementation for pagination if needed
    }
    
    setupAutoRefresh() {
        // Auto-refresh every 30 seconds for pending verifications
        setInterval(() => {
            if (!this.currentModal) { // Don't refresh if modal is open
                this.loadStats();
                
                // Only refresh list if showing pending items
                if (this.currentFilters.status !== 'verified' && this.currentFilters.status !== 'rejected') {
                    this.loadVerifications(this.currentFilters);
                }
            }
        }, 30000);
    }
    
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Close modal with Escape
            if (e.key === 'Escape' && this.currentModal) {
                this.closeModal();
            }
            
            // Quick filter shortcuts
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case '1':
                        e.preventDefault();
                        this.setStatusFilter('awaiting_instagram_verify');
                        break;
                    case '2':
                        e.preventDefault();
                        this.setStatusFilter('assets_submitted');
                        break;
                    case '3':
                        e.preventDefault();
                        this.setStatusFilter('verified');
                        break;
                    case '4':
                        e.preventDefault();
                        this.setStatusFilter('rejected');
                        break;
                    case '0':
                        e.preventDefault();
                        this.setStatusFilter('');
                        break;
                    case 'f':
                        e.preventDefault();
                        document.getElementById('search-input')?.focus();
                        break;
                }
            }
        });
    }
    
    handleKeyboardShortcuts(e) {
        // Implementation for additional keyboard shortcuts
    }
    
    setStatusFilter(status) {
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.value = status;
            this.applyFilters();
        }
    }
    
    showLoading(show) {
        const existingLoader = document.querySelector('.apollo-global-loader');
        
        if (show && !existingLoader) {
            const loader = document.createElement('div');
            loader.className = 'apollo-global-loader';
            loader.innerHTML = '<div class="apollo-loading">Carregando...</div>';
            loader.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 10px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 9999;
            `;
            document.body.appendChild(loader);
        } else if (!show && existingLoader) {
            document.body.removeChild(existingLoader);
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `apollo-notification apollo-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#5cb85c' : type === 'error' ? '#d9534f' : type === 'warning' ? '#f0ad4e' : '#5bc0de'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10001;
            max-width: 300px;
            word-wrap: break-word;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 5000);
    }
    
    // Utility methods
    getStatusLabel(status) {
        const labels = {
            'awaiting_instagram_verify': 'Aguardando',
            'assets_submitted': 'Submetido',
            'verified': 'Verificado',
            'rejected': 'Rejeitado'
        };
        return labels[status] || status;
    }
    
    getUserInitials(name) {
        if (!name) return '?';
        return name.split(' ')
            .map(word => word.charAt(0))
            .slice(0, 2)
            .join('')
            .toUpperCase();
    }
    
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }
    
    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof apolloAdmin !== 'undefined') {
        window.apolloVerificationsAdmin = new ApolloVerificationsAdmin();
    }
});