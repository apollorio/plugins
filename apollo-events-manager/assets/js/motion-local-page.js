/**
 * Motion.dev Local Page Animations
 * 
 * Cursor trail effect and reveal animations
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let trail = [];
    const MAX_TRAIL_LENGTH = 20;

    /**
     * Create cursor trail effect
     */
    function initCursorTrail() {
        const trailTarget = document.querySelector('[data-cursor-trail]');
        if (!trailTarget) {
            return;
        }

        const trailContainer = document.createElement('div');
        trailContainer.className = 'cursor-trail-container';
        trailContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        `;
        document.body.appendChild(trailContainer);

        trailTarget.addEventListener('mousemove', function(e) {
            const rect = trailTarget.getBoundingClientRect();
            const x = e.clientX;
            const y = e.clientY;

            // Check if mouse is over the element
            if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                createTrailDot(x, y, trailContainer);
            }
        });
    }

    /**
     * Create trail dot
     */
    function createTrailDot(x, y, container) {
        const dot = document.createElement('div');
        dot.className = 'cursor-trail-dot';
        dot.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 8px;
            height: 8px;
            background: radial-gradient(circle, rgba(0, 124, 186, 0.8) 0%, rgba(0, 124, 186, 0) 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: trailFade 0.6s ease-out forwards;
        `;

        container.appendChild(dot);
        trail.push(dot);

        // Remove old dots
        if (trail.length > MAX_TRAIL_LENGTH) {
            const oldDot = trail.shift();
            if (oldDot && oldDot.parentNode) {
                oldDot.remove();
            }
        }

        // Auto remove after animation
        setTimeout(() => {
            if (dot && dot.parentNode) {
                dot.remove();
            }
        }, 600);
    }

    /**
     * Initialize reveal animations
     */
    function initRevealAnimations() {
        const elements = document.querySelectorAll('[data-reveal]');
        if (elements.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const delay = element.getAttribute('data-reveal-delay') || 0;

                    setTimeout(() => {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }, delay);

                    observer.unobserve(element);
                }
            });
        }, {
            threshold: 0.1
        });

        elements.forEach(function(element) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            observer.observe(element);
        });
    }

    /**
     * Add animation styles
     */
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes trailFade {
                0% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                100% {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.5);
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                addStyles();
                initCursorTrail();
                initRevealAnimations();
            });
        } else {
            addStyles();
            initCursorTrail();
            initRevealAnimations();
        }
    }

    init();
})();

