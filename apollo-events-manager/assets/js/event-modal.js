/**
 * Apollo Events - Modal/Lightbox Handler
 * Micromodal-based event modal system
 */

(function() {
    'use strict';

    const MODAL_ID = 'apollo-event-modal';
    const LOADING_MARKUP =
        '<div class="flex items-center justify-center p-8">' +
        '<i class="ri-loader-4-line animate-spin text-4xl"></i>' +
        '</div>';

    function setBodyScrollLocked(locked) {
        document.body.style.overflow = locked ? 'hidden' : '';
    }

    if (typeof MicroModal !== 'undefined') {
        MicroModal.init({
            disableScroll: true,
            disableFocus: false,
            awaitOpenAnimation: true,
            awaitCloseAnimation: true,
            debugMode: false
        });
    }

    document.addEventListener('modal:open', function(event) {
        if (event && event.detail && event.detail.modalId === MODAL_ID) {
            setBodyScrollLocked(true);
        }
    });

    document.addEventListener('modal:close', function(event) {
        if (event && event.detail && event.detail.modalId === MODAL_ID) {
            setBodyScrollLocked(false);
        }
    });

    document.addEventListener('click', function(event) {
        if (
            event.target &&
            event.target.hasAttribute('data-micromodal-close')
        ) {
            const modal = document.getElementById(MODAL_ID);
            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
            setBodyScrollLocked(false);
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById(MODAL_ID);
            if (modal && modal.classList.contains('is-open')) {
                setBodyScrollLocked(false);
            }
        }
    });

    document.addEventListener('click', function(event) {
        const trigger = event.target.closest('[data-event-modal]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        const eventId = trigger.dataset.eventModal || trigger.dataset.eventId;

        if (!eventId) {
            console.error('Evento sem ID');
            return;
        }

        loadEventModal(eventId);
    });

    function resolveAjaxUrl() {
        if (
            typeof apollo_events_ajax !== 'undefined' &&
            (apollo_events_ajax.url || apollo_events_ajax.ajax_url)
        ) {
            return apollo_events_ajax.url || apollo_events_ajax.ajax_url;
        }

        if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        }

        return '';
    }

    function resolveNonce() {
        if (typeof apollo_events_ajax !== 'undefined') {
            return apollo_events_ajax.nonce || '';
        }

        if (typeof apolloEvents !== 'undefined') {
            return apolloEvents.nonce || '';
        }

        return '';
    }

    function loadEventModal(eventId) {
        let modal = document.getElementById(MODAL_ID);
        if (!modal) {
            createModalContainer();
            modal = document.getElementById(MODAL_ID);
        }

        const modalContent = document.getElementById(
            'apollo-event-modal-content'
        );
        if (!modalContent) {
            return;
        }

        modalContent.innerHTML = LOADING_MARKUP;

        const ajaxUrl = resolveAjaxUrl();
        if (!ajaxUrl) {
            console.error('AJAX URL não definida');
            modalContent.innerHTML =
                '<div class="p-8 text-center text-destructive">' +
                'Configuração AJAX ausente' +
                '</div>';
            return;
        }

        const nonceValue = resolveNonce();

        if (typeof MicroModal !== 'undefined') {
            MicroModal.show(MODAL_ID);
        } else {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            setBodyScrollLocked(true);
        }

        const params = new URLSearchParams({
            action: 'apollo_get_event_modal',
            event_id: eventId,
            _ajax_nonce: nonceValue
        });

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params
        })
            .then(function(response) {
                return response.text();
            })
            .then(function(text) {
                const trimmed = text.trim();
                if (trimmed === '-1') {
                    const nonceError = new Error('nonce_invalid');
                    nonceError.raw = trimmed;
                    throw nonceError;
                }

                try {
                    return JSON.parse(text);
                } catch (error) {
                    const parseError = new Error('invalid_json');
                    parseError.raw = text;
                    throw parseError;
                }
            })
            .then(function(resp) {
                if (resp.success && resp.data && resp.data.html) {
                    modalContent.innerHTML = resp.data.html;
                    return;
                }

                modalContent.innerHTML =
                    '<div class="p-8 text-center text-destructive">' +
                    'Erro ao carregar evento' +
                    '</div>';
                console.error('Erro ao carregar modal:', resp);
            })
            .catch(function(error) {
                let message = 'Erro de conexão';

                if (error && error.message === 'nonce_invalid') {
                    message = 'Sessão expirada. Recarregue a página.';
                }

                modalContent.innerHTML =
                    '<div class="p-8 text-center text-destructive">' +
                    message +
                    '</div>';
                console.error('Erro ao carregar modal:', error);
            });
    }

    function createModalContainer() {
        const modal = document.createElement('div');
        modal.id = MODAL_ID;
        modal.className = 'modal micromodal-slide';
        modal.setAttribute('aria-hidden', 'true');

        modal.innerHTML =
            '<div class="modal__overlay" tabindex="-1" data-micromodal-close">' +
            '<div class="modal__container max-w-4xl" role="dialog" aria-modal="true">' +
            '<button class="modal__close" aria-label="Fechar" data-micromodal-close>' +
            '<i class="ri-close-line"></i>' +
            '</button>' +
            '<div id="apollo-event-modal-content" class="modal__content">' +
            '<!-- Conteúdo dinâmico aqui -->' +
            '</div>' +
            '</div>' +
            '</div>';

        modal.addEventListener('click', function(event) {
            if (event.target.hasAttribute('data-micromodal-close')) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                setBodyScrollLocked(false);
            }
        });

        document.body.appendChild(modal);
    }

    window.openEventModal = loadEventModal;

})();
