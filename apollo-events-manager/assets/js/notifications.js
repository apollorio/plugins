/**
 * Notifications JavaScript
 * TODO 118-121: Notifications animations, popup, list
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    // TODO 119: Entry animations
    function initEntryAnimations() {
        const notifications = document.querySelectorAll('[data-motion-notification="true"]');
        
        notifications.forEach((notification, index) => {
            const delay = index * 0.1;
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                notification.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                notification.style.transitionDelay = delay + 's';
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 50);
        });
    }

    // TODO 120: Desktop popup notifications
    function initDesktopPopup() {
        const popup = document.querySelector('[data-popup-notifications="true"]');
        if (!popup) return;
        
        const notifications = popup.querySelectorAll('.notification-item');
        
        notifications.forEach((notification, index) => {
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                dismissNotification(notification);
            }, 5000 + (index * 1000));
        });
    }

    // TODO 121: Mobile list with pull to refresh
    function initMobileList() {
        const list = document.querySelector('[data-list-notifications="true"]');
        if (!list) return;
        
        let startY = 0;
        let isPulling = false;
        
        list.addEventListener('touchstart', function(e) {
            if (list.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });
        
        list.addEventListener('touchmove', function(e) {
            if (!isPulling) return;
            
            const currentY = e.touches[0].clientY;
            const pullDistance = currentY - startY;
            
            if (pullDistance > 50) {
                // Show refresh indicator
                list.classList.add('refreshing');
            }
        });
        
        list.addEventListener('touchend', function() {
            if (list.classList.contains('refreshing')) {
                // Refresh notifications
                refreshNotifications();
            }
            list.classList.remove('refreshing');
            isPulling = false;
        });
    }

    function dismissNotification(notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    function refreshNotifications() {
        // TODO: AJAX refresh
        console.log('Refreshing notifications...');
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initEntryAnimations();
            initDesktopPopup();
            initMobileList();
        });
    } else {
        initEntryAnimations();
        initDesktopPopup();
        initMobileList();
    }
})();

