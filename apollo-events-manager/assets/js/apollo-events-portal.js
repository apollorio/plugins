/**
 * Apollo Events Portal - Modal System
 * Lightweight and efficient event modal handler
 * CORRIGIDO: Usa action 'apollo_load_event_modal'
 */
(function() {
    'use strict';

    const AJAX_ACTIONS = {
        apollo_get_event_modal: true,
        load_event_single: true,
        toggle_favorite: true,
        filter_events: true
    };

    function registerAjaxPrefilter() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        if (window.__apolloAjaxNoncePrefilterRegistered) {
            return;
        }
        window.__apolloAjaxNoncePrefilterRegistered = true;

        jQuery.ajaxPrefilter(function(options, originalOptions) {
            const ajaxConfig = window.apollo_events_ajax || {};
            const nonceValue = ajaxConfig.nonce || '';

            if (!nonceValue) {
                return;
            }

            const url = options.url || '';
            if (url.indexOf('admin-ajax.php') === -1) {
                return;
            }

            let actionName = '';

            const maybeApplyNonce = function(params) {
                actionName = params.get('action') || '';
                if (!AJAX_ACTIONS[actionName]) {
                    return false;
                }

                if (!params.has('_ajax_nonce')) {
                    params.set('_ajax_nonce', nonceValue);
                    return true;
                }

                return false;
            };

            if (typeof originalOptions.data === 'string' && originalOptions.data.length) {
                try {
                    const params = new URLSearchParams(originalOptions.data);
                    if (maybeApplyNonce(params)) {
                        const serialized = params.toString();
                        options.data = serialized;
                        originalOptions.data = serialized;
                    }
                    return;
                } catch (error) {
                    // Continue with other fallbacks below.
                }
            }

            if (originalOptions.data && typeof originalOptions.data === 'object') {
                actionName = originalOptions.data.action || '';
                if (!AJAX_ACTIONS[actionName]) {
                    return;
                }

                if (typeof FormData !== 'undefined' && originalOptions.data instanceof FormData) {
                    if (!originalOptions.data.has('_ajax_nonce')) {
                        originalOptions.data.append('_ajax_nonce', nonceValue);
                    }
                    options.data = originalOptions.data;
                    return;
                }

                if (!('_ajax_nonce' in originalOptions.data)) {
                    originalOptions.data._ajax_nonce = nonceValue;
                    options.data = originalOptions.data;
                }
                return;
            }

            if (options.data && typeof options.data === 'string') {
                try {
                    const params = new URLSearchParams(options.data);
                    if (maybeApplyNonce(params)) {
                        options.data = params.toString();
                    }
                } catch (error) {
                    // Ignore invalid data payloads.
                }
            }

            if (url.indexOf('action=') !== -1) {
                try {
                    const urlObject = new URL(url, window.location.origin);
                    if (maybeApplyNonce(urlObject.searchParams)) {
                        options.url = urlObject.pathname + '?' + urlObject.searchParams.toString();
                    }
                } catch (error) {
                    // Ignore malformed URLs.
                }
            }
        });
    }

    registerAjaxPrefilter();

    if (typeof jQuery === 'undefined') {
        window.addEventListener('load', registerAjaxPrefilter, { once: true });
    }

    const MODAL_ID = 'apollo-event-modal';
    const MODAL_CLASS_OPEN = 'is-open';
    const BODY_CLASS_LOCKED = 'apollo-modal-open';
    const LAYOUT_STORAGE_KEY = 'apollo_events_layout';
    const LAYOUT_CLASS_LIST = 'apollo-layout-list';
    const LAYOUT_CLASS_GRID = 'apollo-layout-grid';
    const LOADING_HTML =
        '<div class="apollo-loading" style="padding:40px;text-align:center;' +
        'color:#fff;">Carregando...</div>';

    let modal = null;

    function getStoredLayout() {
        try {
            const stored = window.localStorage.getItem(LAYOUT_STORAGE_KEY);
            return stored === 'grid' ? 'grid' : 'list';
        } catch (error) {
            return 'list';
        }
    }

    function storeLayout(mode) {
        try {
            window.localStorage.setItem(LAYOUT_STORAGE_KEY, mode);
        } catch (error) {
            // Storage indisponível, ignorar
        }
    }

    function applyLayout(mode) {
        const root = document.documentElement;
        root.classList.remove(LAYOUT_CLASS_LIST, LAYOUT_CLASS_GRID);

        const active = mode === 'grid' ? LAYOUT_CLASS_GRID : LAYOUT_CLASS_LIST;
        root.classList.add(active);

        const toggle = document.getElementById('wpem-event-toggle-layout');
        if (toggle) {
            toggle.dataset.layout = mode;
            toggle.setAttribute('aria-pressed', mode === 'list' ? 'true' : 'false');
            toggle.classList.toggle('is-grid', mode === 'grid');
        }
    }

    function initLayoutPreference() {
        const initial = getStoredLayout();
        applyLayout(initial);
    }

    function handleLayoutToggle(button) {
        const current = button && button.dataset.layout === 'grid' ? 'grid' : 'list';
        const next = current === 'grid' ? 'list' : 'grid';
        applyLayout(next);
        storeLayout(next);
    }

    window.toggleLayout = function(button) {
        handleLayoutToggle(button);
    };

    function initModal() {
        modal = document.getElementById(MODAL_ID);
        if (!modal) {
            console.error(
                'Modal container #apollo-event-modal não encontrado'
            );
            return false;
        }
        return true;
    }

    function buildErrorHtml(message) {
        return (
            '<div class="apollo-error" style="padding:40px;text-align:center;' +
            'color:#fff;">' +
            message +
            '</div>'
        );
    }

    function openModal(html) {
        if (!modal) {
            return;
        }

        modal.innerHTML = html;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add(MODAL_CLASS_OPEN);
        document.documentElement.classList.add(BODY_CLASS_LOCKED);
        document.body.style.overflow = 'hidden';

        modal.querySelectorAll('[data-apollo-close]').forEach(function(btn) {
            btn.addEventListener('click', closeModal);
        });

        const overlay = modal.querySelector('.apollo-event-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', handleEscapeKey);
    }

    function closeModal() {
        if (!modal) {
            return;
        }

        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove(MODAL_CLASS_OPEN);
        document.documentElement.classList.remove(BODY_CLASS_LOCKED);
        document.body.style.overflow = '';

        setTimeout(function() {
            modal.innerHTML = '';
        }, 300);

        document.removeEventListener('keydown', handleEscapeKey);
    }

    function handleEscapeKey(event) {
        if (event.key === 'Escape' && modal && modal.classList.contains(MODAL_CLASS_OPEN)) {
            closeModal();
        }
    }

    function init() {
        if (!initModal()) {
            return;
        }

        initLayoutPreference();

        // Attach layout toggle button listener
        const layoutToggleBtn = document.getElementById('wpem-event-toggle-layout');
        if (layoutToggleBtn) {
            layoutToggleBtn.addEventListener('click', function() {
                handleLayoutToggle(this);
            });
        }

        if (typeof apollo_events_ajax === 'undefined') {
            console.error(
                'apollo_events_ajax não está definido. Verifique wp_localize_script.'
            );
            return;
        }

        const ajaxUrl = apollo_events_ajax.url || apollo_events_ajax.ajax_url;
        if (!ajaxUrl) {
            console.error('URL do AJAX não está disponível.');
            return;
        }

        const container = document.querySelector('.event_listings');
        if (!container) {
            console.warn('.event_listings não encontrado');
            return;
        }

        container.addEventListener('click', function(event) {
            const card = event.target.closest('.event_listing');
            if (!card) {
                return;
            }

            event.preventDefault();

            const eventId = card.getAttribute('data-event-id');
            if (!eventId) {
                console.warn('Card sem data-event-id');
                return;
            }

            card.classList.add('is-loading');
            openModal(LOADING_HTML);

            const params = new URLSearchParams({
                action: 'apollo_get_event_modal',
                event_id: eventId,
                _ajax_nonce: apollo_events_ajax.nonce || ''
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
                .then(function(data) {
                    if (data.success && data.data && data.data.html) {
                        openModal(data.data.html);
                        return;
                    }

                    const message = data && data.data && data.data.message ?
                        data.data.message :
                        'Erro ao carregar evento.';

                    openModal(buildErrorHtml(message));
                    console.error('AJAX error:', data);
                })
                .catch(function(error) {
                    let message = 'Erro de conexão. Tente novamente.';

                    if (error && error.message === 'nonce_invalid') {
                        message = 'Sessão expirada. Recarregue a página.';
                    }

                    console.error('AJAX error:', error);
                    openModal(buildErrorHtml(message));
                })
                .finally(function() {
                    card.classList.remove('is-loading');
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
