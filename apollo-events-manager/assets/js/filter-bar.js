/**
 * Apollo Filter Bar JavaScript
 * AJAX filtering for events
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Filter Bar Class
     */
    class ApolloFilterBar {
        constructor(element) {
            this.$el = $(element);
            this.$form = this.$el.find('.apollo-filter-form, .apollo-filter-sidebar__form');
            this.targetSelector = this.$el.data('target');
            this.$target = this.targetSelector ? $(this.targetSelector) : null;
            this.isLoading = false;
            this.debounceTimer = null;

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Form submit
            this.$form.on('submit', (e) => {
                if (this.$target && this.$target.length) {
                    e.preventDefault();
                    this.filterEvents();
                }
            });

            // Live search (debounced)
            this.$form.find('input[name="s"]').on('input', () => {
                this.debounceFilter();
            });

            // Select change
            this.$form.find('select').on('change', () => {
                if (this.$target && this.$target.length) {
                    this.filterEvents();
                }
            });

            // Checkbox/radio change
            this.$form.find('input[type="checkbox"], input[type="radio"]').on('change', () => {
                if (this.$target && this.$target.length) {
                    this.filterEvents();
                }
            });

            // Date change
            this.$form.find('input[type="date"]').on('change', () => {
                if (this.$target && this.$target.length) {
                    this.filterEvents();
                }
            });
        }

        debounceFilter() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                if (this.$target && this.$target.length) {
                    this.filterEvents();
                }
            }, 500);
        }

        filterEvents() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            this.showLoading();

            const formData = this.$form.serialize();

            $.ajax({
                url: apolloFilterBar.ajax_url,
                type: 'POST',
                data: {
                    action: 'apollo_filter_events',
                    nonce: apolloFilterBar.nonce,
                    filters: formData
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        this.$target.html(response.data.html);
                        this.updateURL(formData);

                        // Trigger custom event for other scripts
                        $(document).trigger('apollo:events:filtered', [response.data]);
                    } else {
                        this.showError(apolloFilterBar.i18n.no_events);
                    }
                },
                error: () => {
                    this.showError(apolloFilterBar.i18n.error);
                },
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }

        showLoading() {
            if (this.$target) {
                this.$target.addClass('apollo-loading');
                this.$target.css('opacity', '0.5');
            }
        }

        hideLoading() {
            if (this.$target) {
                this.$target.removeClass('apollo-loading');
                this.$target.css('opacity', '1');
            }
        }

        showError(message) {
            if (this.$target) {
                this.$target.html(
                    '<div class="apollo-filter-message apollo-filter-message--error">' +
                    '<i class="ri-error-warning-line"></i> ' + message +
                    '</div>'
                );
            }
        }

        updateURL(formData) {
            if (window.history && window.history.pushState) {
                const url = new URL(window.location);
                const params = new URLSearchParams(formData);

                // Clear old params
                url.search = '';

                // Add new params (skip empty)
                params.forEach((value, key) => {
                    if (value) {
                        url.searchParams.set(key, value);
                    }
                });

                window.history.pushState({}, '', url);
            }
        }
    }

    /**
     * Initialize filter bars
     */
    $(document).ready(function() {
        $('.apollo-filter-bar, .apollo-filter-sidebar').each(function() {
            new ApolloFilterBar(this);
        });
    });

    // Expose to window for external use
    window.ApolloFilterBar = ApolloFilterBar;

})(jQuery);
