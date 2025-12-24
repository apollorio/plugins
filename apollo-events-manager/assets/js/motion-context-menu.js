/**
 * Motion.dev Context Menu
 * 
 * Implements context menu with Motion.dev animations and smart positioning
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let contextMenu = null;
    let currentTarget = null;

    /**
     * Initialize context menu
     */
    function initContextMenu() {
        contextMenu = document.getElementById('apollo-context-menu');
        if (!contextMenu) {
            return;
        }

        // Add right-click handlers to event cards
        document.addEventListener('contextmenu', handleContextMenu);

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (contextMenu && !contextMenu.contains(e.target)) {
                closeContextMenu();
            }
        });

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && contextMenu.classList.contains('is-open')) {
                closeContextMenu();
            }
        });

        // Add click handlers to menu items
        const menuItems = contextMenu.querySelectorAll('.context-menu-item');
        menuItems.forEach(function(item) {
            item.addEventListener('click', handleMenuAction);
        });
    }

    /**
     * Handle right-click on event cards
     */
    function handleContextMenu(e) {
        const eventCard = e.target.closest('.event_listing, .event-list-item, [data-event-id]');
        
        if (!eventCard) {
            return;
        }

        e.preventDefault();
        currentTarget = eventCard;

        const eventId = eventCard.getAttribute('data-event-id');
        if (!eventId) {
            return;
        }

        // Position menu
        positionMenu(e.clientX, e.clientY);

        // Open menu with animation
        openContextMenu();
    }

    /**
     * Position menu with smart positioning to avoid going off-screen
     */
    function positionMenu(x, y) {
        if (!contextMenu) {
            return;
        }

        const menuWidth = contextMenu.offsetWidth || 200;
        const menuHeight = contextMenu.offsetHeight || 300;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        let left = x;
        let top = y;

        // Adjust horizontal position if menu would go off-screen
        if (left + menuWidth > windowWidth) {
            left = windowWidth - menuWidth - 10;
        }

        // Adjust vertical position if menu would go off-screen
        if (top + menuHeight > windowHeight) {
            top = windowHeight - menuHeight - 10;
        }

        // Ensure menu doesn't go off left or top edge
        left = Math.max(10, left);
        top = Math.max(10, top);

        contextMenu.style.left = left + 'px';
        contextMenu.style.top = top + 'px';
    }

    /**
     * Open context menu with animation
     * TODO 101: Enhanced with spring animations
     */
    function openContextMenu() {
        if (!contextMenu) {
            return;
        }

        // TODO 101: Spring animation entrance
        contextMenu.style.display = 'block';
        contextMenu.style.opacity = '0';
        contextMenu.style.transform = 'scale(0.9) translateY(-10px)';
        contextMenu.style.transition = 'opacity 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        setTimeout(() => {
            contextMenu.classList.add('is-open');
            contextMenu.style.opacity = '1';
            contextMenu.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }

    /**
     * Close context menu with animation
     * TODO 101: Enhanced with spring animations
     */
    function closeContextMenu() {
        if (!contextMenu) {
            return;
        }

        // TODO 101: Spring animation exit
        contextMenu.classList.remove('is-open');
        contextMenu.style.opacity = '0';
        contextMenu.style.transform = 'scale(0.9) translateY(-10px)';
        
        setTimeout(() => {
            contextMenu.style.display = 'none';
        }, 200);
    }

    /**
     * Handle menu action
     */
    function handleMenuAction(e) {
        const action = this.getAttribute('data-action');
        const eventId = currentTarget ? currentTarget.getAttribute('data-event-id') : null;

        if (!eventId) {
            return;
        }

        switch (action) {
            case 'copy-url':
                copyEventUrl(currentTarget);
                break;
            
            case 'copy-title':
                copyEventTitle(currentTarget);
                break;
            
            case 'edit':
                editEvent(eventId);
                break;
            
            case 'duplicate':
                duplicateEvent(eventId);
                break;
            
            case 'delete':
                deleteEvent(eventId);
                break;
            
            case 'share':
                shareEvent(currentTarget);
                break;
        }

        closeContextMenu();
    }

    /**
     * Copy event URL
     */
    function copyEventUrl(card) {
        const url = card.href || card.querySelector('a')?.href;
        if (url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('URL copiada!');
            });
        }
    }

    /**
     * Copy event title
     */
    function copyEventTitle(card) {
        const title = card.querySelector('.event-title, .box-info-event h2')?.textContent || 
                     card.getAttribute('title');
        if (title) {
            navigator.clipboard.writeText(title.trim()).then(() => {
                showToast('Título copiado!');
            });
        }
    }

    /**
     * Edit event (admin only)
     */
    function editEvent(eventId) {
        window.location.href = '/wp-admin/post.php?post=' + eventId + '&action=edit';
    }

    /**
     * Duplicate event (admin only)
     */
    function duplicateEvent(eventId) {
        if (confirm('Deseja duplicar este evento?')) {
            // Implement duplication logic via AJAX
            console.log('Duplicating event:', eventId);
            showToast('Funcionalidade em desenvolvimento');
        }
    }

    /**
     * Delete event (admin only)
     */
    function deleteEvent(eventId) {
        if (confirm('Tem certeza que deseja excluir este evento?')) {
            // Implement deletion logic via AJAX
            console.log('Deleting event:', eventId);
            showToast('Funcionalidade em desenvolvimento');
        }
    }

    /**
     * Share event
     */
    function shareEvent(card) {
        const url = card.href || card.querySelector('a')?.href;
        const title = card.querySelector('.event-title, .box-info-event h2')?.textContent;

        if (navigator.share && url) {
            navigator.share({
                title: title || 'Evento Apollo',
                url: url
            }).catch(() => {
                // Fallback: copy URL
                copyEventUrl(card);
            });
        } else {
            copyEventUrl(card);
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'apollo-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #007cba;
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            z-index: 100000;
            animation: toastSlideIn 0.3s ease-out;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    /**
     * Add toast animations
     */
    function addToastStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes toastSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes toastSlideOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(20px);
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
                addToastStyles();
                initContextMenu();
            });
        } else {
            addToastStyles();
            initContextMenu();
        }
    }

    init();
})();

