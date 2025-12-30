/**
 * Apollo Calendar JavaScript
 * Navigation and interactivity for calendars
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Calendar Class
     */
    class ApolloCalendar {
        constructor(element) {
            this.$el = $(element);
            this.$grid = this.$el.find('.apollo-calendar__grid');
            this.$title = this.$el.find('.apollo-calendar__title');

            this.month = parseInt(this.$el.data('month'), 10) || new Date().getMonth() + 1;
            this.year = parseInt(this.$el.data('year'), 10) || new Date().getFullYear();
            this.startDate = this.$el.data('start-date') || null;
            this.category = this.$el.data('category') || '';
            this.localId = this.$el.data('local') || '';
            this.isWeekView = this.$el.hasClass('apollo-calendar--week');
            this.isLoading = false;

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Previous navigation
            this.$el.find('.apollo-calendar__nav--prev').on('click', () => {
                this.navigate(-1);
            });

            // Next navigation
            this.$el.find('.apollo-calendar__nav--next').on('click', () => {
                this.navigate(1);
            });

            // Keyboard navigation
            this.$el.on('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    this.navigate(-1);
                } else if (e.key === 'ArrowRight') {
                    this.navigate(1);
                }
            });
        }

        navigate(direction) {
            if (this.isLoading) {
                return;
            }

            if (this.isWeekView) {
                // Navigate weeks
                const currentStart = new Date(this.startDate);
                currentStart.setDate(currentStart.getDate() + (direction * 7));
                this.startDate = this.formatDate(currentStart);
            } else {
                // Navigate months
                this.month += direction;

                if (this.month < 1) {
                    this.month = 12;
                    this.year--;
                } else if (this.month > 12) {
                    this.month = 1;
                    this.year++;
                }
            }

            this.loadCalendar();
        }

        loadCalendar() {
            this.isLoading = true;
            this.$el.addClass('is-loading');

            const data = {
                action: 'apollo_calendar_navigate',
                nonce: apolloCalendar.nonce,
                month: this.month,
                year: this.year,
                category: this.category,
                local_id: this.localId,
                type: this.isWeekView ? 'week' : 'month',
                start_date: this.startDate
            };

            $.ajax({
                url: apolloCalendar.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.$grid.html(response.data.html);
                        this.$title.text(response.data.title);

                        // Update data attributes
                        if (this.isWeekView) {
                            this.$el.data('start-date', this.startDate);
                        } else {
                            this.$el.data('month', this.month);
                            this.$el.data('year', this.year);
                        }

                        // Trigger custom event
                        $(document).trigger('apollo:calendar:navigated', [response.data]);
                    }
                },
                error: () => {
                    console.error('Failed to load calendar');
                },
                complete: () => {
                    this.isLoading = false;
                    this.$el.removeClass('is-loading');
                }
            });
        }

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    }

    /**
     * Apollo Mini Calendar Class
     */
    class ApolloMiniCalendar {
        constructor(element) {
            this.$el = $(element);
            this.$days = this.$el.find('.apollo-mini-calendar__days');
            this.$title = this.$el.find('.apollo-mini-calendar__title');

            this.month = parseInt(this.$el.data('month'), 10) || new Date().getMonth() + 1;
            this.year = parseInt(this.$el.data('year'), 10) || new Date().getFullYear();

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            this.$el.find('.apollo-mini-calendar__nav').on('click', (e) => {
                const direction = $(e.currentTarget).data('dir') === 'prev' ? -1 : 1;
                this.navigate(direction);
            });
        }

        navigate(direction) {
            this.month += direction;

            if (this.month < 1) {
                this.month = 12;
                this.year--;
            } else if (this.month > 12) {
                this.month = 1;
                this.year++;
            }

            // Update title
            this.$title.text(apolloCalendar.months[this.month] + ' ' + this.year);

            // For now, just update the data. Full AJAX could be added later
            this.$el.data('month', this.month);
            this.$el.data('year', this.year);

            // Trigger event for external handlers
            $(document).trigger('apollo:mini-calendar:navigated', {
                month: this.month,
                year: this.year
            });
        }
    }

    /**
     * Initialize calendars
     */
    $(document).ready(function() {
        $('.apollo-calendar').each(function() {
            new ApolloCalendar(this);
        });

        $('.apollo-mini-calendar').each(function() {
            new ApolloMiniCalendar(this);
        });
    });

    // Expose to window for external use
    window.ApolloCalendar = ApolloCalendar;
    window.ApolloMiniCalendar = ApolloMiniCalendar;

})(jQuery);
