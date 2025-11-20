/**
 * Apollo Events Portal - Modal System
 * Lightweight and efficient event modal handler
 * CORRIGIDO: Usa action 'apollo_load_event_modal'
 */
(function() {
    'use strict';

    function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function(resolve, reject) {
            const tempInput = document.createElement('textarea');
            tempInput.value = text;
            tempInput.setAttribute('readonly', '');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            tempInput.select();

            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(tempInput);
                if (successful) {
                    resolve();
                } else {
                    reject(new Error('copy_failed'));
                }
            } catch (error) {
                document.body.removeChild(tempInput);
                reject(error);
            }
        });
    }

    function showTemporaryState(element, className) {
        if (!element) {
            return;
        }

        element.classList.add(className);
        window.setTimeout(function() {
            element.classList.remove(className);
        }, 2000);
    }

    function resolveCouponCode(sourceElement) {
        // Try to get code from data attribute first (most reliable)
        if (sourceElement instanceof Element) {
            const couponDetail = sourceElement.closest('.apollo-coupon-detail');
            if (couponDetail && couponDetail.dataset && couponDetail.dataset.couponCode) {
                return couponDetail.dataset.couponCode;
            }
        }
        
        // Fallback: try to find coupon detail in context
        const scope = sourceElement instanceof Element
            ? sourceElement.closest('.apollo-event-modal-content, .mobile-container')
            : null;
        const context = scope || document;
        const detail = sourceElement instanceof Element && sourceElement.closest('.apollo-coupon-detail')
            ? sourceElement.closest('.apollo-coupon-detail')
            : context.querySelector('.apollo-coupon-detail');

        if (detail) {
            // Try data attribute first
            if (detail.dataset && detail.dataset.couponCode) {
                return detail.dataset.couponCode;
            }
            
            // Fallback: get from strong element
            const strongEl = detail.querySelector('strong');
            if (strongEl) {
                const code = strongEl.textContent.trim();
                if (code !== '') {
                    return code;
                }
            }
        }
        
        return 'APOLLO';
    }

    // Override or define copyPromoCode function
    window.copyPromoCode = function(buttonElement) {
        // Use button element if provided, otherwise try activeElement
        const sourceEl = buttonElement instanceof Element 
            ? buttonElement 
            : (document.activeElement instanceof HTMLElement ? document.activeElement : null);
        
        const code = resolveCouponCode(sourceEl);

        copyTextToClipboard(code)
            .then(function() {
                // Show visual feedback
                if (sourceEl) {
                    showTemporaryState(sourceEl, 'copied');
                    
                    // Also update icon temporarily
                    const icon = sourceEl.querySelector('i');
                    if (icon) {
                        const originalClass = icon.className;
                        icon.className = 'ri-check-line';
                        setTimeout(function() {
                            icon.className = originalClass;
                        }, 2000);
                    }
                }
                
                // Optional: show console log for debugging
                if (window.console && window.console.log) {
                    console.log('✅ Código copiado: ' + code);
                }
            })
            .catch(function(error) {
                if (window.console && window.console.error) {
                    console.error('❌ Erro ao copiar código:', error);
                }
                
                // Fallback: use execCommand for older browsers
                try {
                    const tempInput = document.createElement('textarea');
                    tempInput.value = code;
                    tempInput.style.position = 'fixed';
                    tempInput.style.opacity = '0';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    tempInput.focus();
                    tempInput.select();
                    const success = document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    
                    if (success) {
                        if (sourceEl) {
                            showTemporaryState(sourceEl, 'copied');
                        }
                        if (window.console && window.console.log) {
                            console.log('✅ Código copiado (fallback): ' + code);
                        }
                    } else {
                        window.alert('Copie o código: ' + code);
                    }
                } catch (fallbackError) {
                    window.alert('Copie o código: ' + code);
                }
            });
    };

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
    const LAYOUT_STORAGE_KEY = 'apollo_events_layout';
    const LAYOUT_CLASS_LIST = 'apollo-layout-list';
    const LAYOUT_CLASS_GRID = 'apollo-layout-grid';
    const LOADING_HTML =
        '<div class="apollo-loading" style="padding:40px;text-align:center;' +
        'color:#fff;">Carregando...</div>';

    let modal = null;
    let modalOptions = {};
    let bodyScrollLocked = false;
    let scrollPosition = 0;
    let currentLayoutMode = null;
    let filterChangeTimer = null;

    function lockBodyScroll() {
        if (bodyScrollLocked) {
            return;
        }

        scrollPosition = window.scrollY || window.pageYOffset || 0;
        document.documentElement.classList.add('apollo-modal-open');
        document.body.classList.add('apollo-modal-open');
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + scrollPosition + 'px';
        document.body.style.width = '100%';
        bodyScrollLocked = true;
    }

    function unlockBodyScroll() {
        if (!bodyScrollLocked) {
            return;
        }

        document.documentElement.classList.remove('apollo-modal-open');
        document.body.classList.remove('apollo-modal-open');
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, scrollPosition);
        bodyScrollLocked = false;
    }

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
        const normalized = mode === 'grid' ? 'grid' : 'list';
        const root = document.documentElement;
        root.classList.remove(LAYOUT_CLASS_LIST, LAYOUT_CLASS_GRID);

        const active = normalized === 'grid' ? LAYOUT_CLASS_GRID : LAYOUT_CLASS_LIST;
        root.classList.add(active);

        // Apply class to event_listings container for CSS targeting
        const eventListings = document.querySelector('.event_listings');
        if (eventListings) {
            if (normalized === 'list') {
                eventListings.classList.add('list-view');
            } else {
                eventListings.classList.remove('list-view');
            }
        }

        const toggle = document.getElementById('wpem-event-toggle-layout');
        if (toggle) {
            toggle.dataset.layout = normalized;
            toggle.setAttribute('aria-pressed', normalized === 'list' ? 'true' : 'false');
            toggle.classList.toggle('is-grid', normalized === 'grid');
            
            // Update icon based on layout
            const icon = toggle.querySelector('i');
            if (icon) {
                if (normalized === 'list') {
                    icon.className = 'ri-list-check-2';
                    toggle.setAttribute('title', 'Events List View');
                } else {
                    icon.className = 'ri-grid-fill';
                    toggle.setAttribute('title', 'Events Grid View');
                }
            }
        }

        if (currentLayoutMode !== normalized) {
            currentLayoutMode = normalized;
            document.dispatchEvent(
                new CustomEvent('apollo:layout-changed', {
                    detail: { layout: normalized }
                })
            );
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

    // Initialize layout toggle button event listener
    function initLayoutToggle() {
        const toggle = document.getElementById('wpem-event-toggle-layout');
        if (toggle) {
            // Remove any existing onclick to avoid conflicts
            toggle.removeAttribute('onclick');
            
            // Add event listener
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                handleLayoutToggle(this);
            });
            
            // Initialize layout on page load
            initLayoutPreference();
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLayoutToggle);
    } else {
        initLayoutToggle();
    }

    function dispatchFilterChanged(detail) {
        document.dispatchEvent(
            new CustomEvent('apollo:filter-changed', {
                detail: detail || {}
            })
        );
    }

    function scheduleFilterChanged(detail) {
        if (filterChangeTimer) {
            clearTimeout(filterChangeTimer);
        }
        filterChangeTimer = window.setTimeout(function() {
            dispatchFilterChanged(detail);
        }, 150);
    }

    function initFilterChangeEvents() {
        const categoryButtons = document.querySelectorAll('.event-category');
        categoryButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                scheduleFilterChanged({
                    source: 'category',
                    slug: button.dataset.slug || ''
                });
            });
        });

        const datePrev = document.getElementById('datePrev');
        const dateNext = document.getElementById('dateNext');
        [datePrev, dateNext].forEach(function(btn) {
            if (!btn) {
                return;
            }
            btn.addEventListener('click', function() {
                scheduleFilterChanged({
                    source: 'date',
                    direction: btn === datePrev ? 'prev' : 'next'
                });
            });
        });

        const searchForm = document.getElementById('eventSearchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                scheduleFilterChanged({ source: 'search' });
            });

            const searchInput = searchForm.querySelector('input[name="search_keywords"]');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    scheduleFilterChanged({ source: 'search' });
                });
            }
        }
    }

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

    function openModal(html, options) {
        if (!modal) {
            return;
        }

        modalOptions = options || {};
        modal.innerHTML = html;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add(MODAL_CLASS_OPEN);
        lockBodyScroll();

        modal.querySelectorAll('[data-apollo-close]').forEach(function(btn) {
            btn.addEventListener('click', closeModal);
        });

        const overlay = modal.querySelector('.apollo-event-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }

        enhanceModalContent();

        document.dispatchEvent(new Event('apollo:favorites:refresh'));
        document.addEventListener('keydown', handleEscapeKey);
    }

    function closeModal() {
        if (!modal) {
            return;
        }

        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove(MODAL_CLASS_OPEN);
        modalOptions = {};
        unlockBodyScroll();

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

    function enhanceModalContent() {
        if (!modal || !modalOptions || modalOptions.loading) {
            return;
        }

        const modalContent = modal.querySelector('.apollo-event-modal-content');
        if (!modalContent) {
            return;
        }

        modalContent.classList.add('apollo-modal-mobile');
        const container = modalContent.querySelector('.mobile-container');
        if (container) {
            container.classList.add('apollo-modal-view');
        }

        setupShareButton(modalContent);
        setupRouteButton(modalContent);
        initializeModalMap(modalContent);
    }

    function setupShareButton(root) {
        const shareBtn = root.querySelector('[data-share-button]');
        if (!shareBtn || shareBtn.dataset.apolloShareReady === '1') {
            return;
        }

        shareBtn.dataset.apolloShareReady = '1';

        shareBtn.addEventListener('click', function() {
            const container = root.querySelector('.mobile-container');
            const eventUrl = (modalOptions && modalOptions.eventUrl) ||
                (container ? container.getAttribute('data-apollo-event-url') : '') ||
                window.location.href;
            const titleElement = container ? container.querySelector('.hero-title') : null;
            const eventTitle = titleElement ? titleElement.textContent.trim() : document.title;

            if (navigator.share) {
                navigator.share({
                    title: eventTitle || 'Apollo Events',
                    text: eventTitle || 'Confira este evento na Apollo',
                    url: eventUrl
                }).catch(function(error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    copyTextToClipboard(eventUrl)
                        .then(function() {
                            showTemporaryState(shareBtn, 'copied');
                        })
                        .catch(function() {
                            window.prompt('Copie o link do evento:', eventUrl);
                        });
                });
                return;
            }

            copyTextToClipboard(eventUrl)
                .then(function() {
                    showTemporaryState(shareBtn, 'copied');
                })
                .catch(function() {
                    window.prompt('Copie o link do evento:', eventUrl);
                });
        });
    }

    function setupRouteButton(root) {
        const routeBtn = root.querySelector('#route-btn');
        if (!routeBtn || routeBtn.dataset.apolloRouteReady === '1') {
            return;
        }

        routeBtn.dataset.apolloRouteReady = '1';

        routeBtn.addEventListener('click', function() {
            const container = root.querySelector('.mobile-container');
            if (!container) {
                window.alert('Localização indisponível para este evento.');
                return;
            }

            const lat = parseFloat(container.getAttribute('data-local-lat') || '');
            const lng = parseFloat(container.getAttribute('data-local-lng') || '');

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                window.alert('Localização indisponível para este evento.');
                return;
            }

            const originInput = root.querySelector('#origin-input');
            const origin = originInput ? originInput.value.trim() : '';

            let mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(lat + ',' + lng);
            if (origin) {
                mapsUrl += '&origin=' + encodeURIComponent(origin);
            }

            window.open(mapsUrl, '_blank', 'noopener');
        });
    }

    function initializeModalMap(root) {
        const mapEl = root.querySelector('#eventMap');
        if (!mapEl || mapEl.dataset.apolloMapInitialized === '1') {
            return;
        }

        const lat = parseFloat(mapEl.dataset.lat || mapEl.getAttribute('data-lat') || '');
        const lng = parseFloat(mapEl.dataset.lng || mapEl.getAttribute('data-lng') || '');

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        if (typeof L === 'undefined') {
            console.warn('Leaflet não está disponível para renderizar o mapa.');
            return;
        }

        mapEl.dataset.apolloMapInitialized = '1';
        mapEl.innerHTML = '';

        const map = L.map(mapEl).setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        const container = root.querySelector('.mobile-container');
        const localName = container ? container.getAttribute('data-local-name') : '';
        L.marker([lat, lng]).addTo(map).bindPopup(localName || '');
    }

    function init() {
        console.log('Apollo Events Portal: Initializing...');
        
        if (!initModal()) {
            console.error('Apollo Events Portal: Modal initialization failed');
            return;
        }
        
        console.log('Apollo Events Portal: Modal initialized successfully');

        initLayoutPreference();
        initFilterChangeEvents();

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

        console.log('Apollo Events Portal: Click listener attached to .event_listings');

        container.addEventListener('click', function(event) {
            const card = event.target.closest('.event_listing');
            if (!card) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            
            console.log('Apollo Events Portal: Card clicked, event ID:', card.getAttribute('data-event-id'));

            const eventId = card.getAttribute('data-event-id');
            if (!eventId) {
                console.warn('Card sem data-event-id');
                return;
            }

            const eventUrl = card.getAttribute('href') || card.dataset.eventUrl || '';

            card.classList.add('is-loading');
            openModal(LOADING_HTML, { loading: true });

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
                        openModal(data.data.html, { eventUrl: eventUrl });
                        return;
                    }

                    const message = data && data.data && data.data.message ?
                        data.data.message :
                        'Erro ao carregar evento.';

                    openModal(buildErrorHtml(message), {});
                    console.error('AJAX error:', data);
                })
                .catch(function(error) {
                    let message = 'Erro de conexão. Tente novamente.';

                    if (error && error.message === 'nonce_invalid') {
                        message = 'Sessão expirada. Recarregue a página.';
                    }

                    console.error('AJAX error:', error);
                    openModal(buildErrorHtml(message), {});
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
