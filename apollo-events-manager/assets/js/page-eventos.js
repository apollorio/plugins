/**
 * Apollo Eventos Page - JavaScript Controller
 * ============================================
 * Path: apollo-events-manager/assets/js/page-eventos.js
 *
 * Handles:
 * - Event card click → fullscreen modal popup
 * - URL state management (shareable links)
 * - Filters (category, search, date)
 * - AJAX loading of single event content
 *
 * URL Logic:
 * - /eventos/?event={id} → Opens modal with event
 * - Direct URL /evento/{slug}/ → Opens as full page
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // ==========================================================================
    // CONFIGURATION
    // ==========================================================================

    const config = {
        selectors: {
            page: '#apollo-eventos-page',
            grid: '#eventsGrid',
            modal: '#apolloEventModal',
            modalContent: '#modalContent',
            modalClose: '#modalClose',
            modalOpenPage: '#modalOpenPage',
            eventCard: '.a-eve-card',
            filterTag: '.filter-tag',
            searchInput: '#eventosSearch',
            dateArrow: '.date-arrow',
            dateDisplay: '.date-display',
            resetFilters: '.btn-reset-filters'
        },
        classes: {
            modalOpen: 'modal-open',
            active: 'active',
            hidden: 'hidden',
            loading: 'loading'
        },
        animationDuration: 400
    };

    // ==========================================================================
    // STATE
    // ==========================================================================

    let state = {
        currentEventId: null,
        currentEventUrl: null,
        scrollPosition: 0,
        isModalOpen: false,
        searchTimeout: null
    };

    // ==========================================================================
    // MODAL CONTROLLER
    // ==========================================================================

    const Modal = {
        /**
         * Open modal with event content
         * @param {number} eventId - Event post ID
         * @param {string} eventUrl - Event permalink
         */
        open: function(eventId, eventUrl) {
            const $modal = $(config.selectors.modal);
            const $content = $(config.selectors.modalContent);

            // Store state
            state.currentEventId = eventId;
            state.currentEventUrl = eventUrl;
            state.scrollPosition = window.scrollY;
            state.isModalOpen = true;

            // Lock body scroll
            $('body').addClass(config.classes.modalOpen);

            // Show modal with loader
            $modal.attr('aria-hidden', 'false');
            $content.html('<div class="modal-loader"><div class="loader-spinner"></div></div>');

            // Update URL without reload
            const newUrl = apolloEventosData.baseUrl + '?event=' + eventId;
            history.pushState({ eventId: eventId, modal: true }, '', newUrl);

            // Load event content via AJAX
            this.loadEventContent(eventId);
        },

        /**
         * Close modal and restore state
         */
        close: function() {
            const $modal = $(config.selectors.modal);

            // Hide modal
            $modal.attr('aria-hidden', 'true');

            // Restore body scroll
            $('body').removeClass(config.classes.modalOpen);

            // Restore scroll position
            window.scrollTo(0, state.scrollPosition);

            // Update URL back to base
            history.pushState({}, '', apolloEventosData.baseUrl);

            // Clear state
            state.currentEventId = null;
            state.currentEventUrl = null;
            state.isModalOpen = false;

            // Clear content after animation
            setTimeout(function() {
                $(config.selectors.modalContent).html('');
            }, config.animationDuration);
        },

        /**
         * Open current event in new page
         */
        openInNewPage: function() {
            if (state.currentEventUrl) {
                window.open(state.currentEventUrl, '_blank');
            }
        },

        /**
         * Load event content via AJAX
         * @param {number} eventId - Event post ID
         */
        loadEventContent: function(eventId) {
            const $content = $(config.selectors.modalContent);

            $.ajax({
                url: apolloEventosData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_get_event_single_html',
                    event_id: eventId,
                    nonce: apolloEventosData.nonce,
                    context: 'modal'
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $content.html(response.data.html);

                        // Update open in new page URL
                        if (response.data.permalink) {
                            state.currentEventUrl = response.data.permalink;
                        }

                        // Trigger event for other scripts
                        $(document).trigger('apollo:eventModalLoaded', [eventId, response.data]);
                    } else {
                        $content.html('<div class="eventos-empty"><i class="ri-error-warning-line"></i><h3>' + apolloEventosData.i18n.error + '</h3></div>');
                    }
                },
                error: function() {
                    $content.html('<div class="eventos-empty"><i class="ri-error-warning-line"></i><h3>' + apolloEventosData.i18n.error + '</h3></div>');
                }
            });
        }
    };

    // ==========================================================================
    // FILTERS CONTROLLER
    // ==========================================================================

    const Filters = {
        /**
         * Apply category filter
         * @param {string} category - Category slug
         */
        setCategory: function(category) {
            const url = new URL(window.location.href);

            if (category) {
                url.searchParams.set('categoria', category);
            } else {
                url.searchParams.delete('categoria');
            }

            // Reset to page 1
            url.searchParams.delete('paged');

            window.location.href = url.toString();
        },

        /**
         * Apply search filter
         * @param {string} query - Search query
         */
        setSearch: function(query) {
            const url = new URL(window.location.href);

            if (query) {
                url.searchParams.set('busca', query);
            } else {
                url.searchParams.delete('busca');
            }

            url.searchParams.delete('paged');

            window.location.href = url.toString();
        },

        /**
         * Navigate months
         * @param {string} direction - 'prev' or 'next'
         */
        navigateMonth: function(direction) {
            const $display = $(config.selectors.dateDisplay);
            let month = parseInt($display.data('month'));
            let year = parseInt($display.data('year'));

            if (direction === 'prev') {
                month--;
                if (month < 1) {
                    month = 12;
                    year--;
                }
            } else {
                month++;
                if (month > 12) {
                    month = 1;
                    year++;
                }
            }

            const url = new URL(window.location.href);
            url.searchParams.set('mes', month);
            url.searchParams.set('ano', year);
            url.searchParams.delete('paged');

            window.location.href = url.toString();
        },

        /**
         * Reset all filters
         */
        reset: function() {
            window.location.href = apolloEventosData.baseUrl;
        }
    };

    // ==========================================================================
    // EVENT HANDLERS
    // ==========================================================================

    function initEventHandlers() {
        const $page = $(config.selectors.page);

        // Event card click - open modal
        $page.on('click', config.selectors.eventCard, function(e) {
            e.preventDefault();

            const $card = $(this);
            const eventId = $card.data('event-id');
            const eventUrl = $card.attr('href');

            if (eventId) {
                Modal.open(eventId, eventUrl);
            }
        });

        // Modal close button
        $(config.selectors.modalClose).on('click', function() {
            Modal.close();
        });

        // Modal open in new page button
        $(config.selectors.modalOpenPage).on('click', function() {
            Modal.openInNewPage();
        });

        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && state.isModalOpen) {
                Modal.close();
            }
        });

        // Close modal on backdrop click
        $(config.selectors.modal).on('click', function(e) {
            if ($(e.target).is(config.selectors.modal)) {
                Modal.close();
            }
        });

        // Filter tags
        $page.on('click', config.selectors.filterTag, function() {
            const category = $(this).data('category');
            Filters.setCategory(category);
        });

        // Search input with debounce
        $(config.selectors.searchInput).on('input', function() {
            const query = $(this).val().trim();

            clearTimeout(state.searchTimeout);

            state.searchTimeout = setTimeout(function() {
                if (query.length >= 3 || query.length === 0) {
                    Filters.setSearch(query);
                }
            }, 500);
        });

        // Search on Enter
        $(config.selectors.searchInput).on('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(state.searchTimeout);
                Filters.setSearch($(this).val().trim());
            }
        });

        // Date navigation
        $page.on('click', config.selectors.dateArrow, function() {
            const direction = $(this).data('direction');
            Filters.navigateMonth(direction);
        });

        // Reset filters
        $(config.selectors.resetFilters).on('click', function() {
            Filters.reset();
        });

        // Handle browser back/forward
        $(window).on('popstate', function(e) {
            const eventState = e.originalEvent.state;

            if (eventState && eventState.modal && eventState.eventId) {
                Modal.open(eventState.eventId, '');
            } else if (state.isModalOpen) {
                Modal.close();
            }
        });
    }

    // ==========================================================================
    // REVEAL ANIMATION
    // ==========================================================================

    function initRevealAnimation() {
        const cards = document.querySelectorAll('.a-eve-card.reveal-up');

        if (!cards.length) return;

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        cards.forEach(function(card) {
            observer.observe(card);
        });
    }

    // ==========================================================================
    // INITIALIZATION
    // ==========================================================================

    function init() {
        // Mark page as modal-enabled
        $(config.selectors.page).attr('data-modal-enabled', 'true');

        // Init handlers
        initEventHandlers();
        initRevealAnimation();

        // Check for popup event ID on load
        if (apolloEventosData.popupEventId) {
            Modal.open(apolloEventosData.popupEventId, '');
        }

        // Trigger ready event
        $(document).trigger('apollo:eventosPageReady');
    }

    // DOM Ready
    $(document).ready(init);

})(jQuery);
