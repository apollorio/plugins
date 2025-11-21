/**
 * Apollo Events Portal - Main JavaScript
 * Handles modal, filters, search, and layout toggle
 * 
 * @package ApolloEventsManager
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Debug mode (fallback if not localized)
    const DEBUG = (typeof apolloPortalDebug !== 'undefined' && apolloPortalDebug === true) || 
                  (typeof window.apolloPortalDebug !== 'undefined' && window.apolloPortalDebug === true) ||
                  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

    /**
     * Portal Manager Class
     */
    const ApolloEventsPortal = {
        
        /**
         * Initialize portal functionality
         */
        init: function() {
            this.initModal();
            this.initFilters();
            this.initSearch();
            this.initLayoutToggle();
            this.initDatePicker();
            
            if (DEBUG) {
                console.log('Apollo Events Portal initialized');
            }
        },

        /**
         * Initialize modal functionality
         */
        initModal: function() {
            const self = this;
            
            // Click handler for event cards
            $(document).on('click', '.event_listing', function(e) {
                e.preventDefault();
                
                const eventId = $(this).data('event-id');
                if (!eventId) {
                    if (DEBUG) console.error('No event-id found on card');
                    return;
                }
                
                self.openModal(eventId);
            });
            
            // Close modal handlers
            $(document).on('click', '[data-apollo-close]', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    self.closeModal();
                }
            });
            
            // Close on overlay click
            $(document).on('click', '.apollo-event-modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
        },

        /**
         * Open modal with event details
         */
        openModal: function(eventId) {
            const self = this;
            const $modal = $('#apollo-event-modal');
            
            if (!$modal.length) {
                if (DEBUG) console.error('Modal container not found');
                // Create modal container if it doesn't exist
                $('body').append('<div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>');
                const $newModal = $('#apollo-event-modal');
                if ($newModal.length) {
                    return this.openModal(eventId);
                }
                return;
            }
            
            // Check if apolloPortalAjax is defined
            if (typeof apolloPortalAjax === 'undefined') {
                if (DEBUG) console.error('apolloPortalAjax not defined');
                alert('Erro: Scripts não carregados corretamente. Recarregue a página.');
                return;
            }
            
            // Show loading state
            $modal.html('<div class="apollo-modal-loading"><i class="ri-loader-4-line"></i> Carregando...</div>');
            $modal.attr('aria-hidden', 'false').addClass('open');
            $('body').addClass('apollo-modal-open');
            
            // AJAX request for event details
            $.ajax({
                url: apolloPortalAjax.ajaxurl || ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'apollo_get_event_modal',
                    event_id: eventId,
                    nonce: apolloPortalAjax.nonce || ''
                },
                success: function(response) {
                    if (response.success && response.data && response.data.html) {
                        $modal.html(response.data.html);
                        self.scrollToTop();
                        
                        // Trigger custom event
                        $(document).trigger('apollo:modal:opened', [eventId]);
                        
                        if (DEBUG) console.log('Modal opened for event:', eventId);
                    } else {
                        $modal.html('<div class="apollo-modal-error">Erro ao carregar detalhes do evento.</div>');
                        if (DEBUG) console.error('Modal AJAX error:', response);
                    }
                },
                error: function(xhr, status, error) {
                    $modal.html('<div class="apollo-modal-error">Erro ao carregar evento. Tente novamente.</div>');
                    if (DEBUG) console.error('Modal AJAX request failed:', error);
                }
            });
        },

        /**
         * Close modal
         */
        closeModal: function() {
            const $modal = $('#apollo-event-modal');
            $modal.attr('aria-hidden', 'true').removeClass('open');
            $('body').removeClass('apollo-modal-open');
            $modal.html('');
            
            $(document).trigger('apollo:modal:closed');
            
            if (DEBUG) console.log('Modal closed');
        },

        /**
         * Scroll to top of modal
         */
        scrollToTop: function() {
            const $modalContent = $('.apollo-event-modal-content');
            if ($modalContent.length) {
                $modalContent.scrollTop(0);
            }
        },

        /**
         * Initialize category filters
         */
        initFilters: function() {
            const self = this;
            
            // Category filter buttons
            $(document).on('click', '.event-category', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const slug = $button.data('slug') || 'all';
                
                // Update active state
                $('.event-category').removeClass('active');
                $button.addClass('active');
                
                // Filter events
                self.filterEvents('category', slug);
                
                if (DEBUG) console.log('Filter by category:', slug);
            });
            
            // Local filter buttons
            $(document).on('click', '.event-local-filter', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const slug = $button.data('slug') || '';
                
                // Update active state
                $('.event-local-filter').removeClass('active');
                $button.addClass('active');
                
                // Filter events
                self.filterEvents('local', slug);
                
                if (DEBUG) console.log('Filter by local:', slug);
            });
        },

        /**
         * Filter events by type and value
         */
        filterEvents: function(type, value) {
            const $events = $('.event_listing');
            let visibleCount = 0;
            
            $events.each(function() {
                const $event = $(this);
                let show = true;
                
                if (type === 'category') {
                    const eventCategory = $event.data('category') || 'general';
                    if (value !== 'all' && eventCategory !== value) {
                        show = false;
                    }
                } else if (type === 'local') {
                    const eventLocal = $event.data('local-slug') || '';
                    const normalizedEventLocal = eventLocal.toLowerCase().replace(/-/g, '');
                    const normalizedValue = value.toLowerCase().replace(/-/g, '');
                    if (normalizedValue && normalizedEventLocal !== normalizedValue) {
                        show = false;
                    }
                }
                
                if (show) {
                    $event.show();
                    visibleCount++;
                } else {
                    $event.hide();
                }
            });
            
            // Show/hide "no events" message
            const $noEvents = $('.no-events-found');
            if (visibleCount === 0) {
                if (!$noEvents.length) {
                    $('.event_listings').after('<p class="no-events-found">Nenhum evento encontrado com os filtros selecionados.</p>');
                }
            } else {
                $noEvents.remove();
            }
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            const self = this;
            let searchTimeout;
            
            $('#eventSearchInput').on('input keyup', function() {
                const $input = $(this);
                const query = $input.val().toLowerCase().trim();
                
                // Debounce search
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.searchEvents(query);
                }, 300);
            });
        },

        /**
         * Search events by query
         */
        searchEvents: function(query) {
            const $events = $('.event_listing');
            let visibleCount = 0;
            
            if (!query) {
                // Show all if search is empty
                $events.show();
                $('.no-events-found').remove();
                return;
            }
            
            $events.each(function() {
                const $event = $(this);
                const title = ($event.find('.event-li-title').text() || '').toLowerCase();
                const djText = ($event.find('.of-dj span').text() || '').toLowerCase();
                const locationText = ($event.find('.of-location span').text() || '').toLowerCase();
                
                const matches = title.indexOf(query) !== -1 ||
                              djText.indexOf(query) !== -1 ||
                              locationText.indexOf(query) !== -1;
                
                if (matches) {
                    $event.show();
                    visibleCount++;
                } else {
                    $event.hide();
                }
            });
            
            // Show/hide "no results" message
            const $noEvents = $('.no-events-found');
            if (visibleCount === 0) {
                if (!$noEvents.length) {
                    $('.event_listings').after('<p class="no-events-found">Nenhum resultado encontrado para "' + query + '".</p>');
                } else {
                    $noEvents.text('Nenhum resultado encontrado para "' + query + '".');
                }
            } else {
                $noEvents.remove();
            }
            
            if (DEBUG) console.log('Search query:', query, '- Results:', visibleCount);
        },

        /**
         * Initialize layout toggle
         */
        initLayoutToggle: function() {
            const self = this;
            const $toggle = $('#wpem-event-toggle-layout');
            const $listings = $('.event_listings');
            
            if (!$toggle.length) return;
            
            // Check initial state
            const initialLayout = $toggle.attr('data-layout') || 'list';
            if (initialLayout === 'card') {
                self.setCardLayout();
            } else {
                self.setListLayout();
            }
            
            // Toggle handler
            $toggle.on('click', function(e) {
                e.preventDefault();
                
                const currentLayout = $toggle.attr('data-layout') || 'list';
                const newLayout = currentLayout === 'list' ? 'card' : 'list';
                
                if (newLayout === 'card') {
                    self.setCardLayout();
                } else {
                    self.setListLayout();
                }
                
                if (DEBUG) console.log('Layout toggled to:', newLayout);
            });
        },

        /**
         * Set card layout
         */
        setCardLayout: function() {
            const $toggle = $('#wpem-event-toggle-layout');
            const $listings = $('.event_listings');
            
            $toggle.attr('data-layout', 'card').attr('aria-pressed', 'false');
            $listings.removeClass('list-view').addClass('card-view');
            
            // Update icon if needed
            const $icon = $toggle.find('i');
            if ($icon.length) {
                $icon.removeClass('ri-list-check-2').addClass('ri-building-3-fill');
            }
        },

        /**
         * Set list layout
         */
        setListLayout: function() {
            const $toggle = $('#wpem-event-toggle-layout');
            const $listings = $('.event_listings');
            
            $toggle.attr('data-layout', 'list').attr('aria-pressed', 'true');
            $listings.removeClass('card-view').addClass('list-view');
            
            // Update icon if needed
            const $icon = $toggle.find('i');
            if ($icon.length) {
                $icon.removeClass('ri-building-3-fill').addClass('ri-list-check-2');
            }
        },

        /**
         * Initialize date picker
         */
        initDatePicker: function() {
            const self = this;
            const $prev = $('#datePrev');
            const $next = $('#dateNext');
            const $display = $('#dateDisplay');
            
            if (!$prev.length || !$next.length || !$display.length) return;
            
            let currentMonth = new Date().getMonth();
            let currentYear = new Date().getFullYear();
            
            // Update display
            function updateDisplay() {
                const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                $display.text(monthNames[currentMonth]);
                self.filterEventsByMonth(currentMonth, currentYear);
            }
            
            // Previous month
            $prev.on('click', function(e) {
                e.preventDefault();
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                updateDisplay();
            });
            
            // Next month
            $next.on('click', function(e) {
                e.preventDefault();
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                updateDisplay();
            });
            
            // Initial display
            updateDisplay();
        },

        /**
         * Filter events by month
         */
        filterEventsByMonth: function(month, year) {
            const $events = $('.event_listing');
            let visibleCount = 0;
            
            $events.each(function() {
                const $event = $(this);
                const eventDateStr = $event.data('event-start-date') || '';
                const eventMonthStr = $event.data('month-str') || '';
                
                let show = true;
                
                if (eventDateStr) {
                    const eventDate = new Date(eventDateStr);
                    if (eventDate.getMonth() !== month || eventDate.getFullYear() !== year) {
                        show = false;
                    }
                } else if (eventMonthStr) {
                    const monthNames = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
                    const targetMonthName = monthNames[month];
                    if (eventMonthStr.toLowerCase() !== targetMonthName) {
                        show = false;
                    }
                }
                
                if (show) {
                    $event.show();
                    visibleCount++;
                } else {
                    $event.hide();
                }
            });
            
            // Show/hide "no events" message
            const $noEvents = $('.no-events-found');
            if (visibleCount === 0) {
                if (!$noEvents.length) {
                    $('.event_listings').after('<p class="no-events-found">Nenhum evento encontrado para este mês.</p>');
                }
            } else {
                $noEvents.remove();
            }
            
            if (DEBUG) console.log('Filter by month:', month, year, '- Results:', visibleCount);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        ApolloEventsPortal.init();
    });

})(jQuery);
