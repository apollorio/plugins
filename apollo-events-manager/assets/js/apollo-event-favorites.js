/**
 * P0-6: Apollo Events Favorites - Unified REST API Integration
 * 
 * Integrates event templates with unified favorites REST API endpoint.
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

(function() {
    'use strict';

    const FAVORITE_SELECTOR = '[data-apollo-favorite], [data-event-favorite]';
    const REST_URL = window.apolloEventsData?.restUrl || '/wp-json/apollo/v1';
    const NONCE = window.apolloEventsData?.nonce || '';

    /**
     * P0-6: Initialize favorites system
     */
    function initFavorites() {
        bindFavoriteButtons();
        loadFavoriteStates();
    }

    /**
     * P0-6: Bind favorite button clicks
     */
    function bindFavoriteButtons() {
        document.addEventListener('click', function(e) {
            const button = e.target.closest(FAVORITE_SELECTOR);
            if (!button) return;

            e.preventDefault();
            e.stopPropagation();

            const eventId = button.dataset.eventId || button.dataset.apolloFavorite;
            if (!eventId) return;

            toggleFavorite(eventId, button);
        });
    }

    /**
     * P0-6: Toggle favorite via REST API
     */
    function toggleFavorite(eventId, button) {
        if (!eventId) return;

        // Check if user is logged in
        if (!window.apolloEventsData?.currentUserId) {
            alert('Você precisa estar logado para favoritar eventos.');
            return;
        }

        // Disable button during request
        button.disabled = true;
        const wasFavorited = button.dataset.favorited === 'true' || button.classList.contains('favorited');

        // Get current icon and count elements
        const icon = button.querySelector('i');
        const countElement = button.querySelector('.favorite-count') || button.querySelector('[data-favorite-count]');

        // Optimistic UI update
        updateButtonState(button, icon, !wasFavorited);

        // Make REST API request
        fetch(REST_URL + '/favorites', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE,
            },
            body: JSON.stringify({
                content_type: 'event_listing',
                content_id: parseInt(eventId, 10),
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button state
                updateButtonState(button, icon, data.favorited);
                
                // Update count
                if (countElement) {
                    countElement.textContent = data.favorite_count || 0;
                }

                // Trigger animation
                animateFavorite(button, data.favorited);

                // Update all buttons for this event
                updateAllEventButtons(eventId, data.favorited, data.favorite_count);
            } else {
                // Revert optimistic update
                updateButtonState(button, icon, wasFavorited);
                alert(data.message || 'Erro ao favoritar evento.');
            }
        })
        .catch(error => {
            console.error('Favorite toggle error:', error);
            // Revert optimistic update
            updateButtonState(button, icon, wasFavorited);
            alert('Erro ao favoritar evento. Tente novamente.');
        })
        .finally(() => {
            button.disabled = false;
        });
    }

    /**
     * P0-6: Update button visual state
     */
    function updateButtonState(button, icon, favorited) {
        if (!button || !icon) return;

        button.dataset.favorited = favorited ? 'true' : 'false';

        if (favorited) {
            button.classList.add('favorited');
            icon.classList.remove('ri-heart-line', 'ri-rocket-line');
            icon.classList.add('ri-heart-fill', 'ri-rocket-fill');
        } else {
            button.classList.remove('favorited');
            icon.classList.remove('ri-heart-fill', 'ri-rocket-fill');
            icon.classList.add('ri-heart-line', 'ri-rocket-line');
        }
    }

    /**
     * P0-6: Animate favorite action
     */
    function animateFavorite(button, favorited) {
        if (!button) return;

        // Add animation class
        button.classList.add('favorite-animating');
        
        // Use Motion.dev if available
        if (window.Motion && window.Motion.animate) {
            window.Motion.animate(button, {
                scale: [1, 1.2, 1],
            }, {
                duration: 0.3,
                easing: 'ease-out',
            }).then(() => {
                button.classList.remove('favorite-animating');
            });
        } else {
            // Fallback CSS animation
            setTimeout(() => {
                button.classList.remove('favorite-animating');
            }, 300);
        }
    }

    /**
     * P0-6: Update all buttons for same event
     */
    function updateAllEventButtons(eventId, favorited, count) {
        const allButtons = document.querySelectorAll(
            `[data-event-id="${eventId}"][data-apollo-favorite], [data-event-id="${eventId}"][data-event-favorite]`
        );

        allButtons.forEach(button => {
            const icon = button.querySelector('i');
            const countElement = button.querySelector('.favorite-count') || button.querySelector('[data-favorite-count]');
            
            updateButtonState(button, icon, favorited);
            
            if (countElement && count !== undefined) {
                countElement.textContent = count;
            }
        });
    }

    /**
     * P0-6: Load favorite states for visible events
     */
    function loadFavoriteStates() {
        if (!window.apolloEventsData?.currentUserId) return;

        const eventCards = document.querySelectorAll('[data-event-id]');
        if (eventCards.length === 0) return;

        const eventIds = Array.from(eventCards).map(card => card.dataset.eventId).filter(Boolean);
        if (eventIds.length === 0) return;

        // Batch check favorite status (limit to 20 at a time)
        const batches = [];
        for (let i = 0; i < eventIds.length; i += 20) {
            batches.push(eventIds.slice(i, i + 20));
        }

        batches.forEach(batch => {
            Promise.all(
                batch.map(eventId => 
                    fetch(`${REST_URL}/favorites/event_listing/${eventId}`, {
                        headers: {
                            'X-WP-Nonce': NONCE,
                        },
                    })
                    .then(res => res.json())
                    .then(data => ({ eventId, ...data }))
                    .catch(() => null)
                )
            ).then(results => {
                results.forEach(result => {
                    if (!result || !result.success) return;

                    const buttons = document.querySelectorAll(
                        `[data-event-id="${result.eventId}"][data-apollo-favorite], [data-event-id="${result.eventId}"][data-event-favorite]`
                    );

                    buttons.forEach(button => {
                        const icon = button.querySelector('i');
                        updateButtonState(button, icon, result.favorited);

                        const countElement = button.querySelector('.favorite-count') || button.querySelector('[data-favorite-count]');
                        if (countElement && result.favorite_count !== undefined) {
                            countElement.textContent = result.favorite_count;
                        }
                    });
                });
            });
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFavorites);
    } else {
        initFavorites();
    }
})();

