/**
 * Apollo Events Manager - Tickets Module JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Tickets Manager
     */
    class ApolloTickets {
        constructor() {
            this.config = window.apolloTickets || {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.initQuantitySelectors();
        }

        bindEvents() {
            $(document)
                .on('click', '.apollo-ticket-btn:not(.apollo-ticket-btn--disabled)', this.handleTicketClick.bind(this))
                .on('click', '.apollo-ticket-quantity__btn', this.handleQuantityChange.bind(this))
                .on('submit', '.apollo-buy-form', this.handleBuySubmit.bind(this));
        }

        /**
         * Handle ticket button click - track clicks
         */
        handleTicketClick(e) {
            const $btn = $(e.currentTarget);
            const eventId = $btn.closest('[data-event-id]').data('event-id');
            const url = $btn.attr('href');

            // Track click if enabled
            if (this.config.trackClicks && eventId) {
                this.trackClick(eventId, url);
            }

            // Add visual feedback
            $btn.addClass('is-clicked');
            setTimeout(() => $btn.removeClass('is-clicked'), 300);
        }

        /**
         * Track ticket click
         */
        trackClick(eventId, url) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_track_ticket_click',
                    nonce: this.config.nonce,
                    event_id: eventId,
                    url: url
                }
            });
        }

        /**
         * Handle quantity change
         */
        handleQuantityChange(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const $container = $btn.closest('.apollo-ticket-quantity');
            const $input = $container.find('.apollo-ticket-quantity__input');
            const action = $btn.data('action');
            let value = parseInt($input.val(), 10) || 1;
            const min = parseInt($input.attr('min'), 10) || 1;
            const max = parseInt($input.attr('max'), 10) || 99;

            if (action === 'increase' && value < max) {
                value++;
            } else if (action === 'decrease' && value > min) {
                value--;
            }

            $input.val(value).trigger('change');
            this.updatePrice($container, value);
        }

        /**
         * Update price based on quantity
         */
        updatePrice($container, quantity) {
            const $priceDisplay = $container.closest('.apollo-ticket-type, .apollo-ticket-card')
                                            .find('.apollo-ticket-price-total');

            if ($priceDisplay.length) {
                const unitPrice = parseFloat($priceDisplay.data('unit-price')) || 0;
                const total = (unitPrice * quantity).toFixed(2);
                const formatted = this.formatCurrency(total);
                $priceDisplay.text(formatted);
            }
        }

        /**
         * Format currency
         */
        formatCurrency(value) {
            return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',');
        }

        /**
         * Handle buy form submit
         */
        handleBuySubmit(e) {
            const $form = $(e.target);
            const $btn = $form.find('.apollo-buy-btn');

            // Add loading state
            $btn.addClass('is-loading').prop('disabled', true);
            $btn.find('i').removeClass('fa-shopping-cart').addClass('fa-spinner fa-spin');

            // Let form submit naturally, but show feedback
            setTimeout(() => {
                this.showNotification(this.config.i18n.addedToCart, 'success');
            }, 500);
        }

        /**
         * Initialize quantity selectors
         */
        initQuantitySelectors() {
            $('.apollo-ticket-quantity__input').each(function() {
                const $input = $(this);
                const min = parseInt($input.attr('min'), 10) || 1;
                const max = parseInt($input.attr('max'), 10) || 99;

                $input.on('change', function() {
                    let value = parseInt($(this).val(), 10) || min;
                    value = Math.max(min, Math.min(max, value));
                    $(this).val(value);
                });
            });
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            // Remove existing notifications
            $('.apollo-ticket-notification').remove();

            const $notification = $(`
                <div class="apollo-ticket-notification apollo-ticket-notification--${type}">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `);

            $('body').append($notification);

            // Animate in
            setTimeout(() => $notification.addClass('is-visible'), 10);

            // Remove after delay
            setTimeout(() => {
                $notification.removeClass('is-visible');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
        }
    }

    /**
     * Ticket Status Updater
     * Auto-updates ticket status from server
     */
    class TicketStatusUpdater {
        constructor() {
            this.$containers = $('.apollo-ticket-status[data-auto-update="true"]');

            if (this.$containers.length) {
                this.startPolling();
            }
        }

        startPolling() {
            setInterval(() => this.updateStatuses(), 60000); // Every minute
        }

        updateStatuses() {
            const eventIds = [];

            this.$containers.each(function() {
                const eventId = $(this).data('event-id');
                if (eventId && !eventIds.includes(eventId)) {
                    eventIds.push(eventId);
                }
            });

            if (!eventIds.length) return;

            $.ajax({
                url: window.apolloTickets?.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_get_ticket_statuses',
                    event_ids: eventIds
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.applyStatuses(response.data);
                    }
                }
            });
        }

        applyStatuses(statuses) {
            const statusLabels = window.apolloTickets?.i18n || {};

            for (const eventId in statuses) {
                const status = statuses[eventId];
                const $el = $(`.apollo-ticket-status[data-event-id="${eventId}"]`);

                $el.removeClass('apollo-ticket-status--available apollo-ticket-status--last_units apollo-ticket-status--sold_out')
                   .addClass('apollo-ticket-status--' + status)
                   .text(statusLabels[status] || status);
            }
        }
    }

    /**
     * Countdown Timer for Ticket Sales
     */
    class TicketCountdown {
        constructor(element) {
            this.$element = $(element);
            this.targetDate = new Date(this.$element.data('countdown'));

            if (this.targetDate) {
                this.start();
            }
        }

        start() {
            this.update();
            this.interval = setInterval(() => this.update(), 1000);
        }

        update() {
            const now = new Date();
            const diff = this.targetDate - now;

            if (diff <= 0) {
                clearInterval(this.interval);
                this.$element.html('<span class="apollo-countdown__ended">Vendas encerradas</span>');
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            this.$element.html(`
                <div class="apollo-countdown">
                    <div class="apollo-countdown__item">
                        <span class="apollo-countdown__value">${this.pad(days)}</span>
                        <span class="apollo-countdown__label">dias</span>
                    </div>
                    <div class="apollo-countdown__item">
                        <span class="apollo-countdown__value">${this.pad(hours)}</span>
                        <span class="apollo-countdown__label">horas</span>
                    </div>
                    <div class="apollo-countdown__item">
                        <span class="apollo-countdown__value">${this.pad(minutes)}</span>
                        <span class="apollo-countdown__label">min</span>
                    </div>
                    <div class="apollo-countdown__item">
                        <span class="apollo-countdown__value">${this.pad(seconds)}</span>
                        <span class="apollo-countdown__label">seg</span>
                    </div>
                </div>
            `);
        }

        pad(num) {
            return num.toString().padStart(2, '0');
        }
    }

    /**
     * Ticket Selector (for multiple ticket types)
     */
    class TicketSelector {
        constructor(container) {
            this.$container = $(container);
            this.$types = this.$container.find('.apollo-ticket-type');
            this.$summary = this.$container.find('.apollo-ticket-selector__summary');
            this.selected = {};

            this.bindEvents();
        }

        bindEvents() {
            this.$types.on('change', '.apollo-ticket-quantity__input', (e) => {
                this.updateSelection(e);
            });
        }

        updateSelection(e) {
            const $input = $(e.target);
            const $type = $input.closest('.apollo-ticket-type');
            const typeId = $type.data('type-id');
            const quantity = parseInt($input.val(), 10) || 0;
            const price = parseFloat($type.data('price')) || 0;

            if (quantity > 0) {
                this.selected[typeId] = {
                    name: $type.find('.apollo-ticket-type__name').text(),
                    quantity: quantity,
                    price: price,
                    subtotal: quantity * price
                };
            } else {
                delete this.selected[typeId];
            }

            this.updateSummary();
        }

        updateSummary() {
            if (!this.$summary.length) return;

            let total = 0;
            let items = [];

            for (const typeId in this.selected) {
                const item = this.selected[typeId];
                total += item.subtotal;
                items.push(`${item.quantity}x ${item.name}`);
            }

            if (items.length) {
                this.$summary.html(`
                    <div class="apollo-ticket-selector__items">${items.join(', ')}</div>
                    <div class="apollo-ticket-selector__total">
                        <span>Total:</span>
                        <strong>R$ ${total.toFixed(2).replace('.', ',')}</strong>
                    </div>
                `).show();
            } else {
                this.$summary.hide();
            }
        }

        getSelection() {
            return this.selected;
        }

        getTotal() {
            return Object.values(this.selected).reduce((sum, item) => sum + item.subtotal, 0);
        }
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        // Initialize main tickets handler
        new ApolloTickets();

        // Initialize status updater
        new TicketStatusUpdater();

        // Initialize countdowns
        $('[data-countdown]').each(function() {
            new TicketCountdown(this);
        });

        // Initialize ticket selectors
        $('.apollo-ticket-selector').each(function() {
            new TicketSelector(this);
        });
    });

    // Export to global scope
    window.ApolloTickets = ApolloTickets;
    window.TicketCountdown = TicketCountdown;
    window.TicketSelector = TicketSelector;

})(jQuery);
