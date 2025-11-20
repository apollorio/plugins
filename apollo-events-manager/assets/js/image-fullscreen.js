/**
 * Fullscreen Image with Shared Layout Animation
 * TODO 122: Shared layout animation for thumbnail → fullscreen
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    // TODO 122: Shared layout animation
    function initFullscreenImages() {
        const thumbnails = document.querySelectorAll('[data-image-fullscreen="true"]');
        
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const imageUrl = this.getAttribute('data-image-url') || this.src;
                const imageId = this.getAttribute('data-image-id') || 'image-' + Date.now();
                
                // Create fullscreen overlay
                const overlay = document.createElement('div');
                overlay.className = 'image-fullscreen-overlay';
                overlay.setAttribute('data-layout-id', 'image-' + imageId);
                
                const fullscreenImg = document.createElement('img');
                fullscreenImg.src = imageUrl;
                fullscreenImg.setAttribute('data-layout-id', 'image-' + imageId);
                fullscreenImg.className = 'fullscreen-image';
                
                overlay.appendChild(fullscreenImg);
                document.body.appendChild(overlay);
                
                // Animate in
                setTimeout(() => {
                    overlay.classList.add('active');
                }, 10);
                
                // Close on click
                overlay.addEventListener('click', function() {
                    overlay.classList.remove('active');
                    setTimeout(() => {
                        overlay.remove();
                    }, 300);
                });
                
                // Close on ESC
                document.addEventListener('keydown', function escHandler(e) {
                    if (e.key === 'Escape') {
                        overlay.classList.remove('active');
                        setTimeout(() => {
                            overlay.remove();
                        }, 300);
                        document.removeEventListener('keydown', escHandler);
                    }
                });
            });
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFullscreenImages);
    } else {
        initFullscreenImages();
    }
})();

