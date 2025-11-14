/**
 * Apollo Events Favorites Toggle
 * Ensures all favorites AJAX requests include the localized nonce and updates UI state.
 */
(function() {
    'use strict';

    var FAVORITE_SELECTOR = '[data-apollo-favorite]';

    function getAjaxConfig() {
        if (typeof window.apollo_events_ajax === 'undefined') {
            console.error('[Apollo Favorites] apollo_events_ajax is not defined.');
            return null;
        }

        var ajaxUrl = window.apollo_events_ajax.url || window.apollo_events_ajax.ajax_url;
        var nonce = window.apollo_events_ajax.nonce || '';

        if (!ajaxUrl || !nonce) {
            console.error('[Apollo Favorites] Missing AJAX endpoint or nonce.');
            return null;
        }

        return { url: ajaxUrl, nonce: nonce };
    }

    function parseCount(value) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) && !isNaN(parsed) ? parsed : 0;
    }

    function collectDomTargets(selector) {
        if (!selector) {
            return [];
        }

        try {
            return Array.prototype.slice.call(document.querySelectorAll(selector));
        } catch (error) {
            console.error('[Apollo Favorites] Invalid selector:', selector, error);
            return [];
        }
    }

    function renderAvatarList(container, avatars) {
        if (!container) {
            return;
        }

        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        avatars.forEach(function(item) {
            if (!item) {
                return;
            }

            var avatar = document.createElement('div');
            avatar.className = 'avatar';

            if (item.id) {
                avatar.dataset.userId = String(item.id);
            }

            if (item.name) {
                avatar.setAttribute('title', item.name);
                avatar.setAttribute('aria-label', item.name);
            }

            if (item.avatar) {
                avatar.style.backgroundImage = 'url(' + JSON.stringify(item.avatar) + ')';
            } else if (item.initials) {
                avatar.textContent = item.initials;
                avatar.classList.add('avatar-initials');
            }

            container.appendChild(avatar);
        });
    }

    function updateAvatarContainers(selector, avatars, remaining, totalCount) {
        if (!selector) {
            return;
        }

        var containers = collectDomTargets(selector);
        if (!containers.length) {
            return;
        }

        containers.forEach(function(container) {
            // Find the parent rsvp-row (if exists) to toggle visibility
            var rsvpRow = container.closest('.rsvp-row');
            
            // Show rsvp-row only if totalCount > 1
            if (rsvpRow) {
                if (totalCount > 1) {
                    rsvpRow.style.display = '';
                } else {
                    rsvpRow.style.display = 'none';
                }
            }

            var listEl = container.querySelector('[data-apollo-avatar-list]') || container;
            renderAvatarList(listEl, Array.isArray(avatars) ? avatars : []);

            var countEl = container.querySelector('[data-apollo-avatar-count]');
            if (countEl) {
                var countValue = parseInt(remaining, 10);
                if (Number.isFinite(countValue) && countValue > 0) {
                    countEl.textContent = '+' + countValue;
                    countEl.style.display = '';
                } else {
                    countEl.textContent = '';
                    countEl.style.display = 'none';
                }
            }

            container.classList.toggle('is-empty', !(Array.isArray(avatars) && avatars.length));
        });
    }

    function updateCount(target, count) {
        var prefix = target.getAttribute('data-count-prefix') || '';
        var suffix = target.getAttribute('data-count-suffix') || '';
        var zeroText = target.getAttribute('data-count-zero') || '';

        if (count === 0 && zeroText) {
            target.textContent = zeroText;
            return;
        }

        target.textContent = prefix + count + suffix;
    }

    function setState(trigger, favorited) {
        trigger.dataset.favorited = favorited ? '1' : '0';
        trigger.classList.toggle('is-favorited', !!favorited);
        trigger.setAttribute('aria-pressed', favorited ? 'true' : 'false');

        var iconSelector = trigger.getAttribute('data-apollo-favorite-icon');
        var icon = iconSelector ? trigger.querySelector(iconSelector) : trigger.querySelector('i');
        var activeClass = trigger.getAttribute('data-apollo-favorite-icon-active') || 'ri-rocket-fill';
        var inactiveClass = trigger.getAttribute('data-apollo-favorite-icon-inactive') || 'ri-rocket-line';

        if (icon) {
            icon.classList.toggle(activeClass, !!favorited);
            icon.classList.toggle(inactiveClass, !favorited);
        }
    }

    function collectCountTargets(selector) {
        return collectDomTargets(selector);
    }

    function handleClick(event) {
        event.preventDefault();
        var trigger = event.currentTarget;
        var eventId = trigger.getAttribute('data-event-id');

        if (!eventId) {
            console.warn('[Apollo Favorites] Missing data-event-id attribute.');
            return;
        }

        var ajaxConfig = getAjaxConfig();
        if (!ajaxConfig) {
            return;
        }

        if (trigger.dataset.loading === '1') {
            return;
        }

    var countTargets = collectCountTargets(trigger.getAttribute('data-apollo-favorite-count'));
    var avatarSelector = trigger.getAttribute('data-apollo-favorite-avatars');

        var params = new URLSearchParams();
        params.set('action', 'toggle_favorite');
        params.set('event_id', String(eventId));
        params.set('_ajax_nonce', ajaxConfig.nonce);

        trigger.dataset.loading = '1';
        trigger.classList.add('is-processing');

        fetch(ajaxConfig.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'same-origin',
            body: params
        })
            .then(function(response) {
                return response.text().then(function(text) {
                    return { response: response, text: text };
                });
            })
            .then(function(payload) {
                var trimmed = payload.text.trim();
                if (trimmed === '-1') {
                    var nonceError = new Error('nonce_invalid');
                    nonceError.payload = payload;
                    throw nonceError;
                }

                var data;
                try {
                    data = JSON.parse(payload.text);
                } catch (error) {
                    error.message = 'invalid_json';
                    error.payload = payload;
                    throw error;
                }

                if (!payload.response.ok || !data || !data.success) {
                    var requestError = new Error('request_failed');
                    requestError.payload = data;
                    requestError.status = payload.response.status;
                    throw requestError;
                }

                var payload = data.data || {};
                var favorited = !!payload.fav;
                var count = parseCount(payload.count);
                var avatars = Array.isArray(payload.avatars) ? payload.avatars : [];
                var remaining = parseCount(payload.remaining);

                setState(trigger, favorited);
                if (countTargets.length) {
                    countTargets.forEach(function(target) {
                        updateCount(target, count);
                    });
                }

                if (avatarSelector) {
                    updateAvatarContainers(avatarSelector, avatars, remaining, count);
                }
            })
            .catch(function(error) {
                if (error.message === 'nonce_invalid') {
                    console.error('[Apollo Favorites] Nonce invalid, please reload the page.');
                    trigger.setAttribute('data-apollo-favorite-error', 'nonce');
                } else if (error.status === 401) {
                    trigger.setAttribute('data-apollo-favorite-error', 'auth');
                    alert('Entre na sua conta para salvar favoritos.');
                } else {
                    console.error('[Apollo Favorites] Request failed:', error);
                    trigger.setAttribute('data-apollo-favorite-error', 'request');
                }
            })
            .finally(function() {
                trigger.dataset.loading = '0';
                trigger.classList.remove('is-processing');
            });
    }

    function initFavorites() {
        var triggers = document.querySelectorAll(FAVORITE_SELECTOR);
        if (!triggers.length) {
            return;
        }

        triggers.forEach(function(trigger) {
            if (!trigger.getAttribute('data-event-id')) {
                return;
            }

            if (trigger.dataset.apolloFavoriteInitialized === '1') {
                return;
            }

            trigger.dataset.apolloFavoriteInitialized = '1';
            trigger.addEventListener('click', handleClick);

            var initialState = trigger.dataset.favorited === '1';
            setState(trigger, initialState);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFavorites);
    } else {
        initFavorites();
    }

    document.addEventListener('apollo:favorites:refresh', initFavorites);
})();
