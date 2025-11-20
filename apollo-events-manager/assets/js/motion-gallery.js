/**
 * Motion.dev Gallery with Card Stack and Swipe Navigation
 * 
 * Implements card-stack gallery with drag gestures for image navigation
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let currentIndex = 0;
    let images = [];

    /**
     * Initialize card stack gallery
     */
    function initCardStackGallery() {
        const gallery = document.querySelector('[data-motion-gallery="card-stack"]');
        if (!gallery) {
            return;
        }

        const imageElements = gallery.querySelectorAll('.gallery-image');
        if (imageElements.length === 0) {
            return;
        }

        images = Array.from(imageElements);
        currentIndex = 0;

        // Initialize positions
        updateGalleryStack();

        // Add swipe/drag handlers
        images.forEach((img, index) => {
            if (index === 0) {
                addDragHandlers(img);
            }
        });

        // Add navigation buttons
        const prevBtn = gallery.querySelector('.gallery-prev');
        const nextBtn = gallery.querySelector('.gallery-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', navigatePrev);
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', navigateNext);
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (!gallery.classList.contains('active')) {
                return;
            }

            if (e.key === 'ArrowLeft') {
                navigatePrev();
            } else if (e.key === 'ArrowRight') {
                navigateNext();
            }
        });
    }

    /**
     * Update gallery stack positions
     */
    function updateGalleryStack() {
        images.forEach((img, index) => {
            const offset = index - currentIndex;
            
            if (offset < 0) {
                // Already swiped away
                img.style.transform = 'translateX(-150%) rotate(-10deg)';
                img.style.opacity = '0';
                img.style.zIndex = '0';
            } else if (offset === 0) {
                // Current image
                img.style.transform = 'translateX(0) scale(1) rotate(0deg)';
                img.style.opacity = '1';
                img.style.zIndex = '10';
                img.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            } else if (offset === 1) {
                // Next image
                img.style.transform = 'translateX(0) scale(0.95) rotate(0deg)';
                img.style.opacity = '0.8';
                img.style.zIndex = '9';
                img.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            } else if (offset === 2) {
                // Second next
                img.style.transform = 'translateX(0) scale(0.9) rotate(0deg)';
                img.style.opacity = '0.6';
                img.style.zIndex = '8';
                img.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            } else {
                // Rest of stack
                img.style.transform = 'translateX(0) scale(0.85) rotate(0deg)';
                img.style.opacity = '0';
                img.style.zIndex = String(7 - offset);
                img.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            }
        });
    }

    /**
     * Add drag/swipe handlers to image
     */
    function addDragHandlers(img) {
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        img.addEventListener('mousedown', startDrag);
        img.addEventListener('touchstart', startDrag, { passive: true });

        function startDrag(e) {
            isDragging = true;
            startX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
            currentX = startX;
            img.style.transition = 'none';

            document.addEventListener('mousemove', onDrag);
            document.addEventListener('touchmove', onDrag, { passive: true });
            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchend', endDrag);
        }

        function onDrag(e) {
            if (!isDragging) return;

            currentX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
            const deltaX = currentX - startX;
            const rotation = deltaX / 20; // Subtle rotation

            img.style.transform = `translateX(${deltaX}px) rotate(${rotation}deg)`;
        }

        function endDrag() {
            if (!isDragging) return;
            isDragging = false;

            const deltaX = currentX - startX;
            const threshold = window.innerWidth / 4; // 25% of screen width

            document.removeEventListener('mousemove', onDrag);
            document.removeEventListener('touchmove', onDrag);
            document.removeEventListener('mouseup', endDrag);
            document.removeEventListener('touchend', endDrag);

            img.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';

            if (Math.abs(deltaX) > threshold) {
                // Swipe threshold met
                if (deltaX > 0) {
                    navigatePrev();
                } else {
                    navigateNext();
                }
            } else {
                // Reset position
                img.style.transform = 'translateX(0) scale(1) rotate(0deg)';
            }
        }
    }

    /**
     * Navigate to previous image
     */
    function navigatePrev() {
        if (currentIndex > 0) {
            currentIndex--;
            updateGalleryStack();
        }
    }

    /**
     * Navigate to next image
     */
    function navigateNext() {
        if (currentIndex < images.length - 1) {
            currentIndex++;
            updateGalleryStack();
        }
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCardStackGallery);
        } else {
            initCardStackGallery();
        }
    }

    init();
})();

