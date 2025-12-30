/**
 * Apollo Events Blocks - Frontend JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * Countdown Timer
     */
    class ApolloCountdown {
        constructor(element) {
            this.element = element;
            this.targetDate = new Date(element.dataset.date).getTime();
            this.daysEl = element.querySelector('[data-unit="days"]');
            this.hoursEl = element.querySelector('[data-unit="hours"]');
            this.minutesEl = element.querySelector('[data-unit="minutes"]');
            this.secondsEl = element.querySelector('[data-unit="seconds"]');

            this.update();
            this.interval = setInterval(() => this.update(), 1000);
        }

        update() {
            const now = new Date().getTime();
            const diff = this.targetDate - now;

            if (diff <= 0) {
                clearInterval(this.interval);
                this.setValues(0, 0, 0, 0);
                this.element.classList.add('apollo-countdown--expired');
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            this.setValues(days, hours, minutes, seconds);
        }

        setValues(days, hours, minutes, seconds) {
            if (this.daysEl) this.daysEl.textContent = this.pad(days);
            if (this.hoursEl) this.hoursEl.textContent = this.pad(hours);
            if (this.minutesEl) this.minutesEl.textContent = this.pad(minutes);
            if (this.secondsEl) this.secondsEl.textContent = this.pad(seconds);
        }

        pad(num) {
            return num.toString().padStart(2, '0');
        }
    }

    /**
     * Calendar Navigation
     */
    class ApolloCalendar {
        constructor(element) {
            this.element = element;
            this.bindEvents();
        }

        bindEvents() {
            const navBtns = this.element.querySelectorAll('.apollo-calendar__nav-btn');

            navBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const month = btn.dataset.month;
                    const year = btn.dataset.year;
                    this.loadMonth(month, year);
                });
            });
        }

        async loadMonth(month, year) {
            // For static rendering, reload page with new month
            const url = new URL(window.location.href);
            url.searchParams.set('apollo_month', month);
            url.searchParams.set('apollo_year', year);
            window.location.href = url.toString();
        }
    }

    /**
     * Search Enhancement
     */
    class ApolloSearch {
        constructor(element) {
            this.element = element;
            this.form = element.querySelector('.apollo-search__form');
            this.input = element.querySelector('.apollo-search__input');

            this.init();
        }

        init() {
            // Auto-submit on filter change
            const filters = this.element.querySelectorAll('.apollo-search__date, .apollo-search__select');

            filters.forEach(filter => {
                filter.addEventListener('change', () => {
                    // Optional: auto-submit
                    // this.form.submit();
                });
            });

            // Clear empty params before submit
            this.form.addEventListener('submit', (e) => {
                const inputs = this.form.querySelectorAll('input, select');

                inputs.forEach(input => {
                    if (!input.value) {
                        input.disabled = true;
                    }
                });
            });
        }
    }

    /**
     * Event Card Hover Effects
     */
    class ApolloEventCard {
        constructor(element) {
            this.element = element;
            this.image = element.querySelector('.apollo-event-card__image img');

            if (this.image) {
                this.addParallax();
            }
        }

        addParallax() {
            this.element.addEventListener('mousemove', (e) => {
                const rect = this.element.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;

                this.image.style.transform = `scale(1.05) translate(${x * 5}px, ${y * 5}px)`;
            });

            this.element.addEventListener('mouseleave', () => {
                this.image.style.transform = '';
            });
        }
    }

    /**
     * Initialize all blocks
     */
    function init() {
        // Countdowns
        document.querySelectorAll('.apollo-countdown').forEach(el => {
            new ApolloCountdown(el);
        });

        // Calendars
        document.querySelectorAll('.apollo-calendar').forEach(el => {
            new ApolloCalendar(el);
        });

        // Search
        document.querySelectorAll('.apollo-search').forEach(el => {
            new ApolloSearch(el);
        });

        // Event Cards
        document.querySelectorAll('.apollo-event-card').forEach(el => {
            new ApolloEventCard(el);
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
