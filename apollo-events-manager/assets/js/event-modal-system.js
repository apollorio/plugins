/**
 * Apollo Event Modal System - Global Handler
 * ==========================================
 * Path: apollo-events-manager/assets/js/event-modal-system.js
 *
 * Global system that enables popup modal behavior for ALL event cards
 * across all pages. When clicking an event card, opens fullscreen modal.
 * Direct URL access opens as normal page.
 *
 * Features:
 * - Works on ANY page with .a-eve-card elements
 * - Fullscreen modal with exit button (z-index 99999)
 * - URL state management (shareable links)
 * - ESC key to close
 * - Open in new page option
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // ==========================================================================
    // GLOBAL CONFIGURATION
    // ==========================================================================

    const EventModalSystem = {

        config: {
            cardSelector: '.a-eve-card[data-idx], .a-eve-card[data-event-id], .apollo-event-card[data-event-id], .apollo-card--event[data-event-id], .apollo-featured-event-card[data-event-id]',
            modalId: 'apollo-global-event-modal',
            zIndex: 99999,
            animationDuration: 400
        },

        state: {
            isOpen: false,
            currentEventId: null,
            currentEventUrl: null,
            scrollPosition: 0,
            modalElement: null,
            returnUrl: null
        },

        /**
         * Initialize the modal system globally
         */
        init: function() {
            // Don't initialize if already on eventos page (has its own system)
            if ($('#apollo-eventos-page').length && typeof apolloEventosData !== 'undefined') {
                return;
            }

            // Create modal element if not exists
            this.createModalElement();

            // Bind event handlers
            this.bindEvents();

            // Check URL for event parameter on load
            this.checkUrlForEvent();

            console.log('[Apollo] Event Modal System initialized globally');
        },

        /**
         * Create the modal DOM element
         */
        createModalElement: function() {
            if ($('#' + this.config.modalId).length) {
                this.state.modalElement = $('#' + this.config.modalId);
                return;
            }

            const modalHtml = `
                <div class="apollo-event-modal apollo-global-modal" id="${this.config.modalId}" aria-hidden="true">
                    <button type="button" class="modal-close" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                        <span>Fechar</span>
                    </button>
                    <button type="button" class="modal-open-page" aria-label="Abrir em nova página">
                        <i class="ri-external-link-line"></i>
                    </button>
                    <div class="modal-content">
                        <div class="modal-loader">
                            <div class="loader-spinner"></div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            this.state.modalElement = $('#' + this.config.modalId);

            // Add modal styles if not already present
            this.injectModalStyles();
        },

        /**
         * Inject modal CSS if not already loaded
         */
        injectModalStyles: function() {
            if ($('#apollo-global-modal-styles').length) {
                return;
            }

            const styles = `
                <style id="apollo-global-modal-styles">
                    .apollo-global-modal {
                        position: fixed;
                        inset: 0;
                        z-index: ${this.config.zIndex};
                        background: var(--ap-eventos-bg, #fff);
                        opacity: 0;
                        visibility: hidden;
                        transition: opacity 0.4s ease, visibility 0.4s ease;
                        overflow-y: auto;
                        -webkit-overflow-scrolling: touch;
                    }
                    .dark .apollo-global-modal,
                    body.dark-mode .apollo-global-modal,
                    [data-theme="dark"] .apollo-global-modal {
                        background: var(--ap-eventos-bg, #131517);
                    }
                    .apollo-global-modal[aria-hidden="false"] {
                        opacity: 1;
                        visibility: visible;
                    }
                    .apollo-global-modal .modal-content {
                        min-height: 100vh;
                        animation: modalSlideUp 0.4s ease;
                    }
                    @keyframes modalSlideUp {
                        from { opacity: 0; transform: translateY(30px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    .apollo-global-modal .modal-close {
                        position: fixed;
                        top: 20px;
                        left: 20px;
                        z-index: ${this.config.zIndex + 1};
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        padding: 12px 20px;
                        background: var(--ap-eventos-bg, #fff);
                        border: 1px solid var(--ap-eventos-border, #e0e2e4);
                        border-radius: 14px;
                        color: var(--ap-eventos-text, rgba(19,21,23,.85));
                        font-size: 0.9rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        font-family: inherit;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    }
                    .dark .apollo-global-modal .modal-close,
                    body.dark-mode .apollo-global-modal .modal-close {
                        background: #1a1c1e;
                        border-color: #333537;
                        color: #fdfdfdfa;
                    }
                    .apollo-global-modal .modal-close i {
                        font-size: 1.2rem;
                    }
                    .apollo-global-modal .modal-close:hover {
                        background: var(--ap-eventos-text, rgba(19,21,23,.85));
                        color: var(--ap-eventos-bg, #fff);
                        border-color: var(--ap-eventos-text, rgba(19,21,23,.85));
                    }
                    .apollo-global-modal .modal-open-page {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: ${this.config.zIndex + 1};
                        width: 48px;
                        height: 48px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: var(--ap-eventos-bg, #fff);
                        border: 1px solid var(--ap-eventos-border, #e0e2e4);
                        border-radius: 50%;
                        color: var(--ap-eventos-text-secondary, rgba(19,21,23,.7));
                        font-size: 1.2rem;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    }
                    .dark .apollo-global-modal .modal-open-page,
                    body.dark-mode .apollo-global-modal .modal-open-page {
                        background: #1a1c1e;
                        border-color: #333537;
                        color: rgba(255,255,255,.7);
                    }
                    .apollo-global-modal .modal-open-page:hover {
                        color: var(--ap-eventos-text, rgba(19,21,23,.85));
                        border-color: var(--ap-eventos-text, rgba(19,21,23,.85));
                    }
                    .apollo-global-modal .modal-loader {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                    }
                    .apollo-global-modal .loader-spinner {
                        width: 48px;
                        height: 48px;
                        border: 3px solid var(--ap-eventos-border, #e0e2e4);
                        border-top-color: var(--ap-eventos-accent, #FF6925);
                        border-radius: 50%;
                        animation: spin 0.8s linear infinite;
                    }
                    @keyframes spin {
                        to { transform: rotate(360deg); }
                    }
                    body.apollo-modal-open {
                        overflow: hidden;
                        position: fixed;
                        width: 100%;
                    }
                    @media (max-width: 768px) {
                        .apollo-global-modal .modal-close {
                            top: 15px;
                            left: 15px;
                            padding: 10px 16px;
                        }
                        .apollo-global-modal .modal-close span {
                            display: none;
                        }
                        .apollo-global-modal .modal-open-page {
                            top: 15px;
                            right: 15px;
                            width: 44px;
                            height: 44px;
                        }
                    }
                </style>
            `;

            $('head').append(styles);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Event card clicks
            $(document).on('click', this.config.cardSelector, function(e) {
                e.preventDefault();

                const $card = $(this);
                const eventId = $card.data('event-id');
                const eventUrl = $card.attr('href');

                if (eventId) {
                    self.open(eventId, eventUrl);
                }
            });

            // Close button
            $(document).on('click', '#' + this.config.modalId + ' .modal-close', function() {
                self.close();
            });

            // Open in new page button
            $(document).on('click', '#' + this.config.modalId + ' .modal-open-page', function() {
                self.openInNewPage();
            });

            // ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.state.isOpen) {
                    self.close();
                }
            });

            // Backdrop click
            $(document).on('click', '#' + this.config.modalId, function(e) {
                if ($(e.target).is('#' + self.config.modalId)) {
                    self.close();
                }
            });

            // Browser back/forward
            $(window).on('popstate', function(e) {
                const state = e.originalEvent.state;

                if (state && state.apolloEventModal && state.eventId) {
                    self.open(state.eventId, '', true);
                } else if (self.state.isOpen) {
                    self.close(true);
                }
            });
        },

        /**
         * Check URL for event parameter on page load
         */
        checkUrlForEvent: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const eventId = urlParams.get('event');

            if (eventId) {
                this.open(parseInt(eventId, 10), '', true);
            }
        },

        /**
         * Open modal with event content
         */
        open: function(eventId, eventUrl, skipHistory) {
            const self = this;
            const $modal = this.state.modalElement;
            const $content = $modal.find('.modal-content');

            // Store state
            this.state.currentEventId = eventId;
            this.state.currentEventUrl = eventUrl;
            this.state.scrollPosition = window.scrollY;
            this.state.isOpen = true;
            this.state.returnUrl = window.location.href.split('?')[0];

            // Lock body scroll
            $('body').addClass('apollo-modal-open');

            // Show modal with loader
            $modal.attr('aria-hidden', 'false');
            $content.html('<div class="modal-loader"><div class="loader-spinner"></div></div>');

            // Update URL if not from popstate
            if (!skipHistory) {
                const separator = window.location.href.indexOf('?') > -1 ? '&' : '?';
                const newUrl = this.state.returnUrl + '?event=' + eventId;
                history.pushState({ apolloEventModal: true, eventId: eventId }, '', newUrl);
            }

            // Load event content via AJAX
            this.loadEventContent(eventId);
        },

        /**
         * Close modal
         */
        close: function(skipHistory) {
            const $modal = this.state.modalElement;

            // Hide modal
            $modal.attr('aria-hidden', 'true');

            // Restore body scroll
            $('body').removeClass('apollo-modal-open');

            // Restore scroll position
            window.scrollTo(0, this.state.scrollPosition);

            // Update URL if not from popstate
            if (!skipHistory && this.state.returnUrl) {
                history.pushState({}, '', this.state.returnUrl);
            }

            // Clear state
            this.state.currentEventId = null;
            this.state.currentEventUrl = null;
            this.state.isOpen = false;

            // Clear content after animation
            const $content = $modal.find('.modal-content');
            setTimeout(function() {
                $content.html('');
            }, this.config.animationDuration);
        },

        /**
         * Open current event in new page
         */
        openInNewPage: function() {
            if (this.state.currentEventUrl) {
                window.open(this.state.currentEventUrl, '_blank');
            } else if (this.state.currentEventId && typeof apolloEventModalData !== 'undefined') {
                window.open(apolloEventModalData.singleBase + this.state.currentEventId + '/', '_blank');
            }
        },

        /**
         * Load event content via AJAX
         */
        loadEventContent: function(eventId) {
            const self = this;
            const $content = this.state.modalElement.find('.modal-content');

            // Get AJAX URL and nonce - check multiple sources
            let ajaxUrl = '/wp-admin/admin-ajax.php';
            let nonce = '';

            if (typeof apolloEventModalData !== 'undefined' && apolloEventModalData.ajaxUrl) {
                ajaxUrl = apolloEventModalData.ajaxUrl;
                nonce = apolloEventModalData.nonce || '';
            } else if (typeof apolloEventosData !== 'undefined' && apolloEventosData.ajaxUrl) {
                ajaxUrl = apolloEventosData.ajaxUrl;
                nonce = apolloEventosData.nonce || '';
            } else if (typeof ajaxurl !== 'undefined') {
                ajaxUrl = ajaxurl;
            }

            // Warn if nonce is missing
            if (!nonce) {
                console.warn('[Apollo] Event Modal: Nonce not available, AJAX may fail');
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_get_event_single_html',
                    event_id: eventId,
                    nonce: nonce,
                    context: 'modal'
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $content.html(response.data.html);

                        // Update state with permalink
                        if (response.data.permalink) {
                            self.state.currentEventUrl = response.data.permalink;
                        }

                        // Trigger event
                        $(document).trigger('apollo:globalEventModalLoaded', [eventId, response.data]);
                    } else {
                        $content.html('<div class="modal-error" style="text-align:center;padding:100px 20px;"><i class="ri-error-warning-line" style="font-size:3rem;color:#999;"></i><h3 style="margin-top:20px;color:#333;">Erro ao carregar evento</h3></div>');
                    }
                },
                error: function() {
                    $content.html('<div class="modal-error" style="text-align:center;padding:100px 20px;"><i class="ri-error-warning-line" style="font-size:3rem;color:#999;"></i><h3 style="margin-top:20px;color:#333;">Erro ao carregar evento</h3></div>');
                }
            });
        }
    };

    // ==========================================================================
    // INITIALIZATION
    // ==========================================================================

    $(document).ready(function() {
        // Initialize global modal system
        EventModalSystem.init();
    });

    // Expose globally for debugging
    window.ApolloEventModalSystem = EventModalSystem;

})(jQuery);
