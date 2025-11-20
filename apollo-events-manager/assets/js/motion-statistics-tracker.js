/**
 * Automatic Event Statistics Tracker
 * 
 * Tracks event views automatically on modal and standalone pages
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    // Get nonce from inline script or global object
    const getNonce = () => {
        return window.apollo_stats_nonce || 
               (window.apollo_events_ajax && window.apollo_events_ajax.nonce) || 
               '';
    };

    // Get AJAX URL
    const getAjaxUrl = () => {
        return window.apollo_events_ajax && window.apollo_events_ajax.ajax_url || 
               '/wp-admin/admin-ajax.php';
    };

    /**
     * Track event view
     */
    function trackEventView(eventId, type) {
        if (!eventId) {
            return;
        }

        const nonce = getNonce();
        const ajaxUrl = getAjaxUrl();

        if (!nonce) {
            console.warn('Apollo: Nonce not available for tracking');
            return;
        }

        // Send tracking request
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'apollo_track_event_view',
                event_id: eventId,
                type: type,
                nonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Apollo: Event view tracked', data.data);
            }
        })
        .catch(error => {
            console.error('Apollo: Failed to track event view', error);
        });
    }

    /**
     * Track modal view when modal opens
     */
    function trackModalView() {
        // Listen for modal open event
        document.addEventListener('apollo:modal:opened', function(e) {
            const eventId = e.detail && e.detail.eventId;
            if (eventId) {
                trackEventView(eventId, 'popup');
            }
        });

        // Fallback: detect modal container with data-event-id
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            // Check if modal was opened
                            const modal = node.querySelector('[data-motion-modal="true"][data-apollo-modal="1"]') || 
                                         (node.matches && node.matches('[data-motion-modal="true"][data-apollo-modal="1"]') ? node : null);
                            
                            if (modal) {
                                // Extract event ID from container
                                const container = modal.closest('[data-apollo-event-url]') || modal;
                                const eventUrl = container.getAttribute('data-apollo-event-url');
                                
                                if (eventUrl) {
                                    // Try to extract event ID from URL
                                    const match = eventUrl.match(/evento\/([^\/]+)/);
                                    if (match) {
                                        // Get event ID from post by slug
                                        // For now, just track if we have a data-event-id
                                        const eventId = container.getAttribute('data-event-id');
                                        if (eventId) {
                                            trackEventView(eventId, 'popup');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Track page view on single event page
     */
    function trackPageView() {
        // Check if we're on a single event page
        const container = document.querySelector('[data-motion-modal="true"]:not([data-apollo-modal="1"])');
        
        if (container) {
            const eventUrl = container.getAttribute('data-apollo-event-url');
            
            if (eventUrl) {
                // Try to get event ID from URL or data attribute
                const eventId = container.getAttribute('data-event-id');
                
                if (eventId) {
                    trackEventView(eventId, 'page');
                } else {
                    // Try to extract from body class or post ID
                    const bodyClasses = document.body.className;
                    const match = bodyClasses.match(/postid-(\d+)/);
                    
                    if (match) {
                        trackEventView(match[1], 'page');
                    }
                }
            }
        }
    }

    /**
     * Initialize tracking
     */
    function init() {
        // Track modal views
        trackModalView();
        
        // Track page view on single event pages
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', trackPageView);
        } else {
            trackPageView();
        }
    }

    // Start tracking
    init();
})();

