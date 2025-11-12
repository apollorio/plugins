/**
 * Apollo Events - Modal/Lightbox Handler
 * Micromodal-based event modal system
 */

(function() {
    'use strict';
    
    // Inicializa Micromodal quando disponível
    if (typeof MicroModal !== 'undefined') {
        MicroModal.init({
            disableScroll: true,
            disableFocus: false,
            awaitOpenAnimation: true,
            awaitCloseAnimation: true,
            debugMode: false
        });
    }
    
    // Handler para abrir eventos via modal
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-event-modal]');
        if (!trigger) return;
        
        e.preventDefault();
        const eventId = trigger.dataset.eventModal || trigger.dataset.eventId;
        
        if (!eventId) {
            console.error('Evento sem ID');
            return;
        }
        
        loadEventModal(eventId);
    });
    
    function loadEventModal(eventId) {
        const modal = document.getElementById('apollo-event-modal');
        if (!modal) {
            createModalContainer();
        }
        
        const modalContent = document.getElementById('apollo-event-modal-content');
        if (!modalContent) return;
        
        // Loading state
        modalContent.innerHTML = '<div class="flex items-center justify-center p-8"><i class="ri-loader-4-line animate-spin text-4xl"></i></div>';
        
        // Open modal
        if (typeof MicroModal !== 'undefined') {
            MicroModal.show('apollo-event-modal');
        } else {
            document.getElementById('apollo-event-modal').classList.add('is-open');
        }
        
        // Fetch event content via AJAX
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=apollo_get_event_modal&event_id=' + eventId + '&nonce=' + apolloEvents.nonce
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success && resp.data.html) {
                modalContent.innerHTML = resp.data.html;
            } else {
                modalContent.innerHTML = '<div class="p-8 text-center text-destructive">Erro ao carregar evento</div>';
            }
        })
        .catch(err => {
            console.error('Erro ao carregar modal:', err);
            modalContent.innerHTML = '<div class="p-8 text-center text-destructive">Erro de conexão</div>';
        });
    }
    
    function createModalContainer() {
        const modal = document.createElement('div');
        modal.id = 'apollo-event-modal';
        modal.className = 'modal micromodal-slide';
        modal.setAttribute('aria-hidden', 'true');
        
        modal.innerHTML = `
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container max-w-4xl" role="dialog" aria-modal="true">
                    <button class="modal__close" aria-label="Fechar" data-micromodal-close>
                        <i class="ri-close-line"></i>
                    </button>
                    <div id="apollo-event-modal-content" class="modal__content">
                        <!-- Conteúdo dinâmico aqui -->
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    // Expor função globalmente para uso via atributo onclick (fallback)
    window.openEventModal = loadEventModal;
    
})();
