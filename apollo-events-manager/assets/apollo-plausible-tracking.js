/**
 * Apollo Events - Plausible Tracking Integration
 * 
 * Adds Plausible custom event tracking to event portal interactions.
 * This file hooks into existing event handlers WITHOUT modifying them.
 * 
 * @package Apollo_Events_Manager
 * @since 2.1.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize tracking when document is ready
     */
    $(document).ready(function() {
        
        // Only proceed if apolloTrackPlausible is available
        if (typeof window.apolloTrackPlausible !== 'function') {
            console.debug('apolloTrackPlausible not available, skipping event tracking');
            return;
        }
        
        // Track event card clicks
        $(document).on('click', '.event_listing', function(e) {
            var $card = $(this);
            var eventId = $card.data('event-id') || $card.attr('data-event-id');
            var category = $card.data('category') || $card.attr('data-category') || '';
            var month = $card.data('month') || $card.attr('data-month') || '';
            
            if (eventId) {
                apolloTrackPlausible('event_card_click', {
                    event_id: eventId,
                    category: category,
                    month: month
                });
            }
        });
        
        // Track modal/lightbox open
        // This triggers when the lightbox content is successfully loaded
        $(document).on('DOMNodeInserted', '#eventLightboxBody', function() {
            var $body = $(this);
            // Try to find event ID from the loaded content
            var eventId = $body.find('[data-event-id]').first().data('event-id') || 
                         $body.find('.event-single').first().data('event-id');
            
            if (eventId) {
                apolloTrackPlausible('event_modal_open', {
                    event_id: eventId
                });
            }
        });
        
        // Track favorite/bookmark actions
        $(document).on('click', '.favorite-button, .bookmark-button, [data-action="favorite"]', function(e) {
            var $btn = $(this);
            var eventId = $btn.data('event-id') || $btn.closest('[data-event-id]').data('event-id');
            
            if (eventId) {
                apolloTrackPlausible('event_favorited', {
                    event_id: eventId
                });
            }
        });
        
        // Track layout toggle (grid/list view)
        $(document).on('click', '#wpem-event-toggle-layout, .wpem-event-layout-icon', function(e) {
            var $btn = $(this);
            var currentLayout = $btn.hasClass('wpem-active-layout') ? 'list' : 'grid';
            
            apolloTrackPlausible('event_layout_toggle', {
                layout: currentLayout
            });
        });
        
        // Track category filter changes
        $(document).on('click', '.event-category, .menutag', function(e) {
            var $btn = $(this);
            var slug = $btn.data('slug') || $btn.attr('data-slug');
            
            if (slug && slug !== 'all') {
                apolloTrackPlausible('event_filter_change', {
                    filter_type: 'category',
                    value: slug
                });
            }
        });
        
        // Track month/date filter changes
        $(document).on('click', '#datePrev, #dateNext, .date-arrow', function(e) {
            var $btn = $(this);
            var direction = $btn.attr('id') === 'datePrev' ? 'prev' : 'next';
            var displayDate = $('#dateDisplay').text() || '';
            
            apolloTrackPlausible('event_filter_change', {
                filter_type: 'month',
                value: displayDate,
                direction: direction
            });
        });
        
        // Track search usage
        var searchTimeout;
        $(document).on('keyup', '#eventSearchInput', function(e) {
            clearTimeout(searchTimeout);
            var $input = $(this);
            
            searchTimeout = setTimeout(function() {
                var query = $input.val();
                if (query.length >= 3) {
                    apolloTrackPlausible('event_search', {
                        query_length: query.length
                    });
                }
            }, 1000); // Wait 1s after user stops typing
        });
        
        // Track share button clicks (if present)
        $(document).on('click', '.btn.share, [data-action="share"]', function(e) {
            var $btn = $(this);
            var eventId = $btn.data('event-id') || $btn.closest('[data-event-id]').data('event-id');
            
            apolloTrackPlausible('event_share_click', {
                event_id: eventId || 'unknown'
            });
        });
        
        console.debug('Apollo Plausible event tracking initialized');
    });
    
})(jQuery);
