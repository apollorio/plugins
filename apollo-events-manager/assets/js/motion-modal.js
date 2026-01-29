/**
 * Motion.dev Modal Animations
 * 
 * Adds Motion.dev animations to event modals with AnimatePresence,
 * backdrop blur, and smooth transitions
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let motionAvailable = false;
    const MODAL_ID = 'apollo-event-modal';

    /**
     * Wait for Motion.dev to be available
     */
    function waitForMotion(callback) {
        if (typeof window.Motion !== 'undefined' || typeof window.motion !== 'undefined') {
            motionAvailable = true;
            callback();
            return;
        }
        setTimeout(() => waitForMotion(callback), 100);
    }

    /**
     * Add backdrop blur animation
     */
    function addBackdropBlur(modal) {
        const backdrop = modal.querySelector('.apollo-event-modal-overlay') || 
                        modal.querySelector('.modal-overlay');
        
        if (backdrop) {
            backdrop.style.backdropFilter = 'blur(8px)';
            backdrop.style.webkitBackdropFilter = 'blur(8px)';
            backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            backdrop.style.transition = 'all 0.3s ease-out';
        }
    }

    /**
     * Animate modal entrance with scale + fade
     * TODO 93-95: Enhanced with layoutId for shared layout transitions
     */
    function animateModalEntrance(modal) {
        const content = modal.querySelector('.mobile-container') || 
                       modal.querySelector('.apollo-event-modal-content');
        
        if (content) {
            // TODO 93-94: Add layoutId matching event card
            const eventId = modal.getAttribute('data-event-id') || 
                          content.querySelector('[data-event-id]')?.getAttribute('data-event-id');
            if (eventId) {
                content.setAttribute('data-layout-id', 'event-card-' + eventId);
            }
            
            // TODO 95: Initial state with blur
            content.style.opacity = '0';
            content.style.transform = 'scale(0.95)';
            content.style.filter = 'blur(4px)';
            content.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out, filter 0.3s ease-out';
            
            // Animate in
            setTimeout(() => {
                content.style.opacity = '1';
                content.style.transform = 'scale(1)';
                content.style.filter = 'blur(0)';
            }, 50);
        }
    }

    /**
     * Animate modal exit
     */
    function animateModalExit(modal, callback) {
        const content = modal.querySelector('.mobile-container') || 
                       modal.querySelector('.apollo-event-modal-content');
        
        const backdrop = modal.querySelector('.apollo-event-modal-overlay') || 
                        modal.querySelector('.modal-overlay');
        
        if (content) {
            content.style.opacity = '0';
            content.style.transform = 'scale(0.95)';
        }
        
        if (backdrop) {
            backdrop.style.opacity = '0';
        }
        
        setTimeout(() => {
            if (callback && typeof callback === 'function') {
                callback();
            }
        }, 300);
    }

    /**
     * Initialize modal animations
     */
    function initModalAnimations() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) {
            return;
        }

        // Listen for modal open events
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const classList = modal.classList;
                    if (classList.contains('is-open') || classList.contains('modal-open')) {
                        addBackdropBlur(modal);
                        animateModalEntrance(modal);
                    }
                }
            });
        });

        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Listen for close events
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('apollo-modal-close') || 
                e.target.closest('.apollo-modal-close')) {
                animateModalExit(modal, function() {
                    modal.classList.remove('is-open', 'modal-open');
                });
            }
        });

        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                animateModalExit(modal, function() {
                    modal.classList.remove('is-open', 'modal-open');
                });
            }
        });
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        waitForMotion(() => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initModalAnimations);
            } else {
                initModalAnimations();
            }
        });
    }

    init();
})();

