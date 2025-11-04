/**
 * Apollo Events Portal - Modal System
 * Lightweight and efficient event modal handler
 * CORRIGIDO: Usa action 'apollo_load_event_modal'
 */
(function() {
    'use strict';
    
    const MODAL_ID = 'apollo-event-modal';
    const MODAL_CLASS_OPEN = 'is-open';
    const BODY_CLASS_LOCKED = 'apollo-modal-open';
    
    // Cache do modal
    let modal = null;
    
    /**
     * Inicializa o modal
     */
    function initModal() {
        modal = document.getElementById(MODAL_ID);
        if (!modal) {
            console.error('Modal container #apollo-event-modal não encontrado');
            return false;
        }
        return true;
    }
    
    /**
     * Abre o modal
     */
    function openModal(html) {
        if (!modal) return;
        
        modal.innerHTML = html;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add(MODAL_CLASS_OPEN);
        document.documentElement.classList.add(BODY_CLASS_LOCKED);
        
        // Adicionar listeners de fechar após inserir HTML
        modal.querySelectorAll('[data-apollo-close]').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });
        
        // Fechar ao clicar no overlay
        const overlay = modal.querySelector('.apollo-event-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Fecha o modal
     */
    function closeModal() {
        if (!modal) return;
        
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove(MODAL_CLASS_OPEN);
        document.documentElement.classList.remove(BODY_CLASS_LOCKED);
        
        // Limpa conteúdo após animação
        setTimeout(() => {
            modal.innerHTML = '';
        }, 300);
        
        // Remover listener ESC
        document.removeEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Handler de tecla ESC
     */
    function handleEscapeKey(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains(MODAL_CLASS_OPEN)) {
            closeModal();
        }
    }
    
    /**
     * Inicialização quando DOM estiver pronto
     */
    function init() {
        // Verificar se modal existe
        if (!initModal()) {
            return;
        }
        
        // Verificar se apollo_events_ajax está disponível
        if (typeof apollo_events_ajax === 'undefined') {
            console.error('apollo_events_ajax não está definido. Verifique wp_localize_script.');
            return;
        }
        
        // Container de eventos
        const container = document.querySelector('.event_listings');
        if (!container) {
            console.warn('.event_listings não encontrado');
            return;
        }
        
        // Event delegation para cliques nos cards
        container.addEventListener('click', function(e) {
            const card = e.target.closest('.event_listing');
            if (!card) return;
            
            e.preventDefault();
            console.log('[Apollo] Click detectado em card');
            
            const eventId = card.getAttribute('data-event-id');
            if (!eventId) {
                console.warn('Card sem data-event-id');
                return;
            }
            
            // Feedback visual de loading
            card.classList.add('is-loading');
            
            // Abrir modal com loading
            openModal('<div class="apollo-loading" style="padding:40px;text-align:center;color:#fff;">Carregando...</div>');
            
            // Fetch AJAX
            console.log('[Apollo] Enviando AJAX para carregar evento', eventId);
            fetch(apollo_events_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'apollo_load_event_modal',
                    nonce: apollo_events_ajax.nonce,
                    event_id: eventId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                card.classList.remove('is-loading');
                console.log('[Apollo] Resposta AJAX recebida', data);
                
                if (data.success && data.data && data.data.html) {
                    openModal(data.data.html);
                    console.log('[Apollo] Modal aberto');
                } else {
                    const errorMsg = data.data && data.data.message ? data.data.message : 'Erro ao carregar evento.';
                    openModal('<div class="apollo-error" style="padding:40px;text-align:center;color:#fff;">' + errorMsg + '</div>');
                    console.error('AJAX error:', data);
                }
            })
            .catch(error => {
                card.classList.remove('is-loading');
                console.error('AJAX error:', error);
                openModal('<div class="apollo-error" style="padding:40px;text-align:center;color:#fff;">Erro de conexão. Tente novamente.</div>');
            });
        });
    }
    
    // Auto-inicializa quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
