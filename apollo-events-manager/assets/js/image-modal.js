/**
 * Fullscreen Image Modal with Motion.dev
 * 
 * Click em imagem → modal fullscreen com zoom e pan
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let currentImageIndex = 0;
    let imageElements = [];
    let modal = null;
    let scale = 1;
    let translateX = 0;
    let translateY = 0;

    /**
     * Create modal HTML
     */
    function createModal() {
        modal = document.createElement('div');
        modal.id = 'apollo-image-modal';
        modal.className = 'apollo-image-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 999999;
            display: none;
            justify-content: center;
            align-items: center;
        `;
        
        modal.innerHTML = `
            <div class="modal-backdrop" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; backdrop-filter: blur(8px);"></div>
            <div class="modal-content" style="position: relative; max-width: 90vw; max-height: 90vh; z-index: 1;">
                <img class="modal-image" style="max-width: 100%; max-height: 90vh; object-fit: contain; cursor: grab; user-select: none; transition: transform 0.3s ease;">
                
                <button class="modal-close" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: transform 0.2s ease;">
                    <i class="ri-close-line" style="font-size: 1.5rem;"></i>
                </button>
                
                <button class="modal-prev" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    <i class="ri-arrow-left-s-line" style="font-size: 2rem;"></i>
                </button>
                
                <button class="modal-next" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    <i class="ri-arrow-right-s-line" style="font-size: 2rem;"></i>
                </button>
                
                <div class="modal-counter" style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); padding: 0.5rem 1rem; background: rgba(0,0,0,0.7); color: #fff; border-radius: 20px;">
                    <span class="current">1</span> / <span class="total">1</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listeners
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.modal-backdrop').addEventListener('click', closeModal);
        modal.querySelector('.modal-prev').addEventListener('click', showPrevImage);
        modal.querySelector('.modal-next').addEventListener('click', showNextImage);
        
        // Zoom and pan
        const img = modal.querySelector('.modal-image');
        addZoomPanHandlers(img);
        
        // Keyboard navigation
        document.addEventListener('keydown', handleKeyboard);
    }

    /**
     * Open modal with image
     */
    function openModal(imgSrc, images, index) {
        if (!modal) {
            createModal();
        }
        
        imageElements = images;
        currentImageIndex = index;
        
        const img = modal.querySelector('.modal-image');
        const counter = modal.querySelector('.modal-counter');
        
        img.src = imgSrc;
        counter.querySelector('.current').textContent = index + 1;
        counter.querySelector('.total').textContent = images.length;
        
        // Show/hide nav buttons
        const prevBtn = modal.querySelector('.modal-prev');
        const nextBtn = modal.querySelector('.modal-next');
        prevBtn.style.display = index > 0 ? 'block' : 'none';
        nextBtn.style.display = index < images.length - 1 ? 'block' : 'none';
        
        // Show modal with animation
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.opacity = '1';
        }, 10);
        
        // Lock body scroll
        document.body.style.overflow = 'hidden';
        
        // Reset zoom
        scale = 1;
        translateX = 0;
        translateY = 0;
        updateImageTransform(img);
    }

    /**
     * Close modal
     */
    function closeModal() {
        if (!modal) return;
        
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    /**
     * Show previous image
     */
    function showPrevImage() {
        if (currentImageIndex > 0) {
            currentImageIndex--;
            updateModalImage();
        }
    }

    /**
     * Show next image
     */
    function showNextImage() {
        if (currentImageIndex < imageElements.length - 1) {
            currentImageIndex++;
            updateModalImage();
        }
    }

    /**
     * Update modal image
     */
    function updateModalImage() {
        const img = modal.querySelector('.modal-image');
        const newSrc = imageElements[currentImageIndex].src || imageElements[currentImageIndex];
        
        img.style.opacity = '0';
        setTimeout(() => {
            img.src = newSrc;
            img.style.transition = 'opacity 0.3s ease';
            img.style.opacity = '1';
            
            // Update counter
            modal.querySelector('.modal-counter .current').textContent = currentImageIndex + 1;
            
            // Update nav buttons
            const prevBtn = modal.querySelector('.modal-prev');
            const nextBtn = modal.querySelector('.modal-next');
            prevBtn.style.display = currentImageIndex > 0 ? 'block' : 'none';
            nextBtn.style.display = currentImageIndex < imageElements.length - 1 ? 'block' : 'none';
            
            // Reset zoom
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform(img);
        }, 150);
    }

    /**
     * Add zoom and pan handlers
     */
    function addZoomPanHandlers(img) {
        // Wheel zoom
        img.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            scale = Math.max(1, Math.min(3, scale + delta));
            updateImageTransform(img);
        });

        // Double-click to zoom
        img.addEventListener('dblclick', function() {
            scale = scale > 1 ? 1 : 2;
            translateX = 0;
            translateY = 0;
            updateImageTransform(img);
        });

        // Drag to pan (when zoomed)
        let isDragging = false;
        let startX = 0;
        let startY = 0;

        img.addEventListener('mousedown', function(e) {
            if (scale > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                img.style.cursor = 'grabbing';
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (isDragging) {
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateImageTransform(img);
            }
        });

        document.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                img.style.cursor = scale > 1 ? 'grab' : 'grab';
            }
        });
    }

    /**
     * Update image transform
     */
    function updateImageTransform(img) {
        img.style.transform = `scale(${scale}) translate(${translateX/scale}px, ${translateY/scale}px)`;
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeyboard(e) {
        if (modal && modal.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'ArrowLeft') {
                showPrevImage();
            } else if (e.key === 'ArrowRight') {
                showNextImage();
            }
        }
    }

    /**
     * Initialize clickable images
     */
    function initClickableImages() {
        const images = document.querySelectorAll('.event-body img, .promo-slide img, .gallery-image img, .local-image img');
        
        images.forEach(function(img, index) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get all images in the same container
                const container = img.closest('.event-body, .promo-gallery-slider, .promo-gallery-card-stack, .local-gallery');
                const containerImages = container ? 
                    Array.from(container.querySelectorAll('img')).map(i => i.src) : 
                    [img.src];
                
                const imgIndex = containerImages.indexOf(img.src);
                openModal(img.src, containerImages, imgIndex >= 0 ? imgIndex : 0);
            });
        });
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initClickableImages);
        } else {
            initClickableImages();
        }
    }

    init();
})();

