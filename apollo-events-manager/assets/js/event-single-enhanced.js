/**
 * Event Single Page Enhanced - JavaScript
 * ========================================
 * Path: apollo-events-manager/assets/js/event-single-enhanced.js
 *
 * Handles all interactive features:
 * - Promo gallery slider
 * - Venue images carousel with infinite loop
 * - Route calculation with Google Maps
 * - RSVP favorite toggle with rocket animation
 * - Promo code copy
 * - Share functionality
 * - Smooth scroll
 * - Word cycling animation
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Initialize all event single page features
     */
    function initEventSingle() {
        initPromoGallery();
        initVenueSlider();
        initRouteButton();
        initFavoriteTrigger();
        initShareButton();
        initSmoothScroll();
        initWordCycling();
    }

    /**
     * Promo Gallery Slider
     */
    function initPromoGallery() {
        const promoTrack = document.getElementById('promoTrack');
        const prevBtn = document.querySelector('.promo-prev');
        const nextBtn = document.querySelector('.promo-next');

        if (!promoTrack) return;

        const promoSlides = promoTrack.children.length || 0;
        let currentPromo = 0;

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentPromo = (currentPromo - 1 + promoSlides) % promoSlides;
                promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentPromo = (currentPromo + 1) % promoSlides;
                promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
            });
        }
    }

    /**
     * Venue Images Slider (5 images with infinite loop)
     */
    function initVenueSlider() {
        const localTrack = document.getElementById('localTrack');
        const localDots = document.getElementById('localDots');

        if (!localTrack || !localTrack.children.length) return;

        const slides = localTrack.children;
        const slideCount = slides.length;
        let currentSlide = 0;

        // Create dots
        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('div');
            dot.classList.add('slider-dot');
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i));
            localDots.appendChild(dot);
        }

        function goToSlide(index) {
            currentSlide = index;
            localTrack.style.transition = 'transform 0.5s ease';
            localTrack.style.transform = `translateX(-${index * 100}%)`;
            updateDots();
        }

        function updateDots() {
            document.querySelectorAll('.slider-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === currentSlide);
            });
        }

        // Auto-advance with true infinite loop
        setInterval(() => {
            currentSlide++;
            if (currentSlide >= slideCount) {
                localTrack.style.transition = 'none';
                currentSlide = 0;
                localTrack.style.transform = 'translateX(0)';
                localTrack.offsetHeight; // Force reflow
                setTimeout(() => {
                    currentSlide = 1;
                    localTrack.style.transition = 'transform 0.5s ease';
                    localTrack.style.transform = 'translateX(-100%)';
                    updateDots();
                }, 50);
            } else {
                goToSlide(currentSlide);
            }
        }, 4000);
    }

    /**
     * Route Button - Open Google Maps with origin and destination
     */
    function initRouteButton() {
        const routeBtn = document.getElementById('route-btn');
        const originInput = document.getElementById('origin-input');

        if (!routeBtn || !originInput) return;

        routeBtn.addEventListener('click', () => {
            const origin = originInput.value;
            const destination = originInput.dataset.destination || '';

            if (origin && destination) {
                const url = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`;
                window.open(url, '_blank');
            } else if (!origin) {
                originInput.placeholder = 'Por favor, insira um endereço!';
                setTimeout(() => {
                    originInput.placeholder = 'Seu endereço de partida';
                }, 2000);
            }
        });
    }

    /**
     * Favorite Trigger - Rocket Animation + AJAX
     */
    function initFavoriteTrigger() {
        const favoriteTrigger = document.getElementById('favoriteTrigger');

        if (!favoriteTrigger) return;

        favoriteTrigger.addEventListener('click', function(event) {
            event.preventDefault();

            const iconContainer = this.querySelector('.quick-action-icon');
            const icon = iconContainer.querySelector('i');
            const avatarsContainer = document.querySelector('.avatars-explosion');
            const countEl = avatarsContainer ? avatarsContainer.querySelector('.avatar-count') : null;
            const resultEl = document.getElementById('result');
            const maxVisible = 10;

            function updateResult() {
                if (!avatarsContainer || !countEl || !resultEl) return;
                const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
                const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
                resultEl.textContent = visibleCount + hiddenCount;
            }

            if (icon.classList.contains('ri-rocket-line')) {
                // Favorite action
                iconContainer.classList.add('fly-away');

                setTimeout(() => {
                    iconContainer.classList.remove('fly-away');
                    icon.className = 'ri-ai-agent-fill fade-in';
                    iconContainer.style.borderColor = 'rgba(0,0,0,0.2)';
                }, 1500);

                if (avatarsContainer && countEl) {
                    let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
                    let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;

                    if (visibleCount < maxVisible) {
                        const newAvatar = document.createElement('div');
                        newAvatar.classList.add('avatar');
                        const gender = Math.random() < 0.5 ? 'men' : 'women';
                        const id = Math.floor(Math.random() * 99) + 1;
                        newAvatar.style.backgroundImage = `url('https://randomuser.me/api/portraits/${gender}/${id}.jpg')`;
                        avatarsContainer.insertBefore(newAvatar, countEl);
                    } else {
                        hiddenCount += 1;
                        countEl.textContent = `+${hiddenCount}`;
                    }
                }

                updateResult();

                // AJAX Call to save favorite
                if (window.apolloEventData && window.apolloEventData.ajaxUrl) {
                    $.ajax({
                        url: window.apolloEventData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'apollo_toggle_favorite',
                            event_id: window.apolloEventData.eventId,
                            nonce: window.apolloEventData.nonce,
                            favorite: true
                        }
                    });
                }
            } else {
                // Unfavorite action
                if (avatarsContainer && countEl) {
                    let hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
                    let visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
                    let total = visibleCount + hiddenCount;

                    if (total > 0) {
                        if (hiddenCount > 0) {
                            hiddenCount -= 1;
                            countEl.textContent = hiddenCount > 0 ? `+${hiddenCount}` : '';
                        } else if (visibleCount > 0) {
                            const lastAvatar = avatarsContainer.querySelector('.avatar:last-of-type');
                            if (lastAvatar) lastAvatar.remove();
                        }
                        updateResult();
                    }
                }

                icon.className = 'ri-rocket-line fade-in';

                // AJAX Call to remove favorite
                if (window.apolloEventData && window.apolloEventData.ajaxUrl) {
                    $.ajax({
                        url: window.apolloEventData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'apollo_toggle_favorite',
                            event_id: window.apolloEventData.eventId,
                            nonce: window.apolloEventData.nonce,
                            favorite: false
                        }
                    });
                }
            }
        });

        // Initial update
        const avatarsContainer = document.querySelector('.avatars-explosion');
        const countEl = avatarsContainer ? avatarsContainer.querySelector('.avatar-count') : null;
        const resultEl = document.getElementById('result');

        if (avatarsContainer && countEl && resultEl) {
            const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
            const hiddenCount = parseInt(countEl.textContent.replace('+', '')) || 0;
            resultEl.textContent = visibleCount + hiddenCount;
        }
    }

    /**
     * Copy Promo Code
     */
    window.copyPromoCode = function() {
        const code = 'APOLLO';
        navigator.clipboard.writeText(code).then(() => {
            const btn = event.target.closest('.copy-code-mini');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="ri-check-line"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 2000);
        });
    };

    /**
     * Share Function
     */
    function initShareButton() {
        const shareBtn = document.getElementById('bottomShareBtn');

        if (!shareBtn) return;

        shareBtn.addEventListener('click', () => {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Confere esse evento no Apollo::rio!',
                    url: window.location.href
                }).catch(err => console.log('Share cancelled'));
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Link copiado!');
            }
        });
    }

    /**
     * Smooth Scroll
     */
    function initSmoothScroll() {
        const bottomTicketBtn = document.getElementById('bottomTicketBtn');

        if (!bottomTicketBtn) return;

        bottomTicketBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const ticketsSection = document.getElementById('route_TICKETS');
            if (ticketsSection) {
                ticketsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }

    /**
     * Word Cycling Animation for Tickets Button
     */
    function initWordCycling() {
        const words = [
            'Entradas',
            'Ingressos',
            'Billets',
            'Ticket',
            'Acessos',
            'Biglietti'
        ];
        let i = 0;
        const elem = document.getElementById('changingword');

        if (!elem) return;

        // Set initial word
        elem.textContent = words[i];

        function fadeOut(el, duration, callback) {
            el.style.opacity = 1;
            let start = null;

            function step(timestamp) {
                if (!start) start = timestamp;
                const progress = timestamp - start;
                const fraction = progress / duration;

                if (fraction < 1) {
                    el.style.opacity = 1 - fraction;
                    window.requestAnimationFrame(step);
                } else {
                    el.style.opacity = 0;
                    if (callback) callback();
                }
            }

            window.requestAnimationFrame(step);
        }

        function fadeIn(el, duration, callback) {
            el.style.opacity = 0;
            el.style.display = '';
            let start = null;

            function step(timestamp) {
                if (!start) start = timestamp;
                const progress = timestamp - start;
                const fraction = progress / duration;

                if (fraction < 1) {
                    el.style.opacity = fraction;
                    window.requestAnimationFrame(step);
                } else {
                    el.style.opacity = 1;
                    if (callback) callback();
                }
            }

            window.requestAnimationFrame(step);
        }

        setInterval(() => {
            if (!elem) return;

            fadeOut(elem, 400, () => {
                i = (i + 1) % words.length;
                elem.textContent = words[i];
                fadeIn(elem, 400);
            });
        }, 4000);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventSingle);
    } else {
        initEventSingle();
    }

})(jQuery);
