/**
 * Motion.dev Animations for Event Cards
 * 
 * Adds Framer Motion animations to event cards with fade + slide entrance,
 * hover effects, and click animations
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let motionAvailable = false;
    let initAttempts = 0;
    const MAX_ATTEMPTS = 50; // 5 seconds max wait

    /**
     * Wait for Framer Motion to be available
     */
    function waitForMotion(callback) {
        if (typeof window.Motion !== 'undefined' || typeof window.motion !== 'undefined') {
            motionAvailable = true;
            callback();
            return;
        }

        initAttempts++;
        if (initAttempts >= MAX_ATTEMPTS) {
            console.warn('Apollo: Framer Motion not available after waiting, using CSS fallback');
            callback(); // Continue with CSS fallback
            return;
        }

        setTimeout(() => waitForMotion(callback), 100);
    }

    /**
     * Initialize Motion animations for event cards
     * TODO 91-92: Enhanced with layoutId for smooth grid/list transitions
     */
    function initMotionCards() {
        const cards = document.querySelectorAll('[data-motion-card="true"]:not([data-motion-initialized])');
        
        if (cards.length === 0) {
            return;
        }

        cards.forEach((card, index) => {
            card.setAttribute('data-motion-initialized', 'true');
            
            // TODO 91: Add layoutId for shared layout transitions
            const eventId = card.getAttribute('data-event-id') || card.querySelector('[data-event-id]')?.getAttribute('data-event-id');
            if (eventId) {
                card.setAttribute('data-layout-id', 'event-card-' + eventId);
            }
            
            // TODO 92: Stagger delay based on index
            const delay = index * 0.05; // 50ms between each card
            
            // Initial state: fade + slide up
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            
            // Animate in with stagger
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                card.style.transitionDelay = delay + 's';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                
                // Remove transition delay after animation completes
                setTimeout(() => {
                    card.style.transitionDelay = '';
                }, 300 + (delay * 1000));
            }, 50);
        });
    }

    /**
     * Add hover and click effects
     */
    function addInteractiveEffects() {
        const cards = document.querySelectorAll('[data-motion-card="true"]');
        
        cards.forEach(card => {
            // Hover effect (already handled by Tailwind classes, but enhance with JS)
            card.addEventListener('mouseenter', function() {
                if (!this.hasAttribute('data-motion-hovering')) {
                    this.setAttribute('data-motion-hovering', 'true');
                }
            });
            
            card.addEventListener('mouseleave', function() {
                this.removeAttribute('data-motion-hovering');
            });
            
            // Click animation (whileTap equivalent)
            card.addEventListener('mousedown', function(e) {
                // Only if clicking on the card itself, not child elements
                if (e.target === this || this.contains(e.target)) {
                    this.style.transform = 'scale(0.98)';
                    this.style.transition = 'transform 0.1s ease-out';
                }
            });
            
            card.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        waitForMotion(() => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    initMotionCards();
                    addInteractiveEffects();
                });
            } else {
                initMotionCards();
                addInteractiveEffects();
            }

            // Re-initialize for dynamically loaded cards (AJAX)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        // Small delay to ensure DOM is ready
                        setTimeout(() => {
                            initMotionCards();
                            addInteractiveEffects();
                        }, 100);
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    // Start initialization
    init();
})();

