/**
 * Apollo Events Manager - Tracking Module JavaScript (Frontend)
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Tracking
     */
    class ApolloTracking {
        constructor() {
            this.config = window.apolloTracking || {};
            this.init();
        }

        init() {
            if (!this.config.eventId) {
                return;
            }

            this.bindEvents();
            this.trackTimeOnPage();
        }

        bindEvents() {
            $(document)
                .on('click', '.apollo-ticket-btn, .apollo-buy-btn, [href*="ticket"], [href*="ingresso"]',
                    this.handleTicketClick.bind(this))
                .on('click', '.apollo-share-btn, [data-share]',
                    this.handleShareClick.bind(this))
                .on('click', '.apollo-interest-btn',
                    this.handleInterestClick.bind(this));
        }

        /**
         * Track ticket click
         */
        handleTicketClick(e) {
            this.track('ticket_click');
        }

        /**
         * Track share click
         */
        handleShareClick(e) {
            const platform = $(e.currentTarget).data('share') ||
                             $(e.currentTarget).data('platform') ||
                             'unknown';

            this.track('share', { platform: platform });
        }

        /**
         * Track interest click
         */
        handleInterestClick(e) {
            this.track('interest');
        }

        /**
         * Track time on page
         */
        trackTimeOnPage() {
            let startTime = Date.now();
            let tracked30s = false;
            let tracked60s = false;

            const checkTime = () => {
                const elapsed = (Date.now() - startTime) / 1000;

                if (elapsed >= 30 && !tracked30s) {
                    this.track('time_30s');
                    tracked30s = true;
                }

                if (elapsed >= 60 && !tracked60s) {
                    this.track('time_60s');
                    tracked60s = true;
                }
            };

            setInterval(checkTime, 5000);
        }

        /**
         * Send tracking request
         */
        track(action, data = {}) {
            const payload = {
                action: 'apollo_track_event',
                nonce: this.config.nonce,
                event_id: this.config.eventId,
                track_action: action,
                ...data
            };

            // Use sendBeacon if available for better performance
            if (navigator.sendBeacon) {
                const formData = new FormData();
                Object.keys(payload).forEach(key => {
                    formData.append(key, payload[key]);
                });
                navigator.sendBeacon(this.config.ajaxUrl, formData);
            } else {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: payload
                });
            }
        }
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        new ApolloTracking();
    });

    // Export to global scope
    window.ApolloTracking = ApolloTracking;

})(jQuery);
