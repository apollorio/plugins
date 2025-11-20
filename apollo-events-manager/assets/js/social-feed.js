/**
 * Social Feed JavaScript
 * TODO 106-109: App Store style cards, swipe actions, stagger animations
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    // TODO 108: Stagger animations
    function initStaggerAnimations() {
        const cards = document.querySelectorAll('[data-motion-card="true"]');
        cards.forEach((card, index) => {
            const delay = index * 0.05;
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                card.style.transitionDelay = delay + 's';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        });
    }

    // TODO 107: Swipe actions
    function initSwipeActions() {
        const swipeCards = document.querySelectorAll('[data-swipe-action="true"]');
        
        swipeCards.forEach(card => {
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            card.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            card.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentX = e.touches[0].clientX - startX;
                
                if (Math.abs(currentX) > 50) {
                    card.style.transform = `translateX(${currentX}px)`;
                }
            });
            
            card.addEventListener('touchend', function() {
                if (Math.abs(currentX) > 100) {
                    if (currentX > 0) {
                        // Swipe right - like
                        triggerAction(card, 'like');
                    } else {
                        // Swipe left - share
                        triggerAction(card, 'share');
                    }
                }
                
                card.style.transform = '';
                isDragging = false;
                currentX = 0;
            });
        });
    }

    function triggerAction(card, action) {
        const actionBtn = card.querySelector(`[data-action="${action}"]`);
        if (actionBtn) {
            actionBtn.click();
        }
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initStaggerAnimations();
            initSwipeActions();
        });
    } else {
        initStaggerAnimations();
        initSwipeActions();
    }
})();

