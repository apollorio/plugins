/**
 * Apollo Push Notifications Client
 *
 * Handles browser push subscription using the native Web Push API.
 * No external libraries or services required.
 */

(function () {
    'use strict';

    // Check for push support
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Apollo Push: Browser does not support push notifications');
        return;
    }

    const config = window.apolloPush || {};

    if (!config.vapidPublicKey) {
        console.log('Apollo Push: VAPID key not configured');
        return;
    }

    /**
     * Convert base64 URL to Uint8Array for applicationServerKey
     */
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Subscribe to push notifications
     */
    async function subscribeToPush() {
        try {
            // Register service worker
            const registration = await navigator.serviceWorker.register(config.swUrl);
            console.log('Apollo Push: Service worker registered');

            // Wait for service worker to be ready
            await navigator.serviceWorker.ready;

            // Request notification permission
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.log('Apollo Push: Notification permission denied');
                return false;
            }

            // Check for existing subscription
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                // Subscribe
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey)
                });
                console.log('Apollo Push: New subscription created');
            }

            // Send subscription to server
            const response = await fetch(config.restUrl + 'subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: subscription.getKey ? btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))) : '',
                        auth: subscription.getKey ? btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))) : ''
                    }
                })
            });

            const data = await response.json();
            if (data.success) {
                console.log('Apollo Push: Subscription saved to server');
                return true;
            }
        } catch (error) {
            console.error('Apollo Push: Subscription failed', error);
        }
        return false;
    }

    /**
     * Unsubscribe from push notifications
     */
    async function unsubscribeFromPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();

                await fetch(config.restUrl + 'unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.nonce
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                });

                console.log('Apollo Push: Unsubscribed');
                return true;
            }
        } catch (error) {
            console.error('Apollo Push: Unsubscribe failed', error);
        }
        return false;
    }

    /**
     * Check subscription status
     */
    async function isSubscribed() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            return !!subscription;
        } catch (error) {
            return false;
        }
    }

    /**
     * Create notification toggle button
     */
    function createToggleButton() {
        // Check if already exists
        if (document.getElementById('apollo-push-toggle')) {
            return;
        }

        const container = document.createElement('div');
        container.id = 'apollo-push-toggle';
        container.innerHTML = `
			<button type="button" class="apollo-push-btn" title="Push Notifications">
				<span class="dashicons dashicons-bell"></span>
				<span class="apollo-push-label">Notifications</span>
			</button>
		`;

        // Style the button
        const style = document.createElement('style');
        style.textContent = `
			#apollo-push-toggle {
				position: fixed;
				bottom: 20px;
				right: 20px;
				z-index: 9999;
			}
			.apollo-push-btn {
				display: flex;
				align-items: center;
				gap: 8px;
				padding: 10px 16px;
				background: #0073aa;
				color: white;
				border: none;
				border-radius: 50px;
				cursor: pointer;
				box-shadow: 0 2px 10px rgba(0,0,0,0.2);
				transition: all 0.3s ease;
				font-size: 14px;
			}
			.apollo-push-btn:hover {
				background: #005177;
				transform: scale(1.05);
			}
			.apollo-push-btn.subscribed {
				background: #46b450;
			}
			.apollo-push-btn.subscribed:hover {
				background: #32a140;
			}
			.apollo-push-btn .dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
			}
			@media (max-width: 600px) {
				.apollo-push-label {
					display: none;
				}
				.apollo-push-btn {
					padding: 12px;
					border-radius: 50%;
				}
			}
		`;
        document.head.appendChild(style);

        document.body.appendChild(container);

        const button = container.querySelector('.apollo-push-btn');

        // Update button state
        async function updateButtonState() {
            const subscribed = await isSubscribed();
            if (subscribed) {
                button.classList.add('subscribed');
                button.querySelector('.apollo-push-label').textContent = 'Subscribed';
                button.title = 'Click to unsubscribe';
            } else {
                button.classList.remove('subscribed');
                button.querySelector('.apollo-push-label').textContent = 'Notifications';
                button.title = 'Click to subscribe';
            }
        }

        // Toggle subscription on click
        button.addEventListener('click', async function () {
            button.disabled = true;
            button.style.opacity = '0.5';

            const subscribed = await isSubscribed();
            if (subscribed) {
                await unsubscribeFromPush();
            } else {
                await subscribeToPush();
            }

            await updateButtonState();
            button.disabled = false;
            button.style.opacity = '1';
        });

        // Set initial state
        updateButtonState();
    }

    // Export functions for external use
    window.ApolloPush = {
        subscribe: subscribeToPush,
        unsubscribe: unsubscribeFromPush,
        isSubscribed: isSubscribed,
        // Expose for navbar integration
        createToggleButton: createToggleButton
    };

    // REMOVED: Auto-creation of floating button in bottom-right corner
    // The notification toggle is now handled through the navbar menu-app
    // To restore floating button, uncomment below:
    // if (document.readyState === 'loading') {
    //     document.addEventListener('DOMContentLoaded', createToggleButton);
    // } else {
    //     createToggleButton();
    // }
    
    // Initialize navbar notification button if exists
    function initNavbarNotification() {
        const navbarBtn = document.querySelector('[data-apollo-push-navbar]');
        if (!navbarBtn) return;
        
        // Update button state based on subscription
        async function updateNavbarState() {
            const subscribed = await isSubscribed();
            if (subscribed) {
                navbarBtn.classList.add('subscribed');
                navbarBtn.setAttribute('data-subscribed', 'true');
            } else {
                navbarBtn.classList.remove('subscribed');
                navbarBtn.setAttribute('data-subscribed', 'false');
            }
        }
        
        // Toggle subscription on click
        navbarBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            navbarBtn.disabled = true;
            navbarBtn.style.opacity = '0.5';
            
            const subscribed = await isSubscribed();
            if (subscribed) {
                await unsubscribeFromPush();
            } else {
                await subscribeToPush();
            }
            
            await updateNavbarState();
            navbarBtn.disabled = false;
            navbarBtn.style.opacity = '1';
        });
        
        updateNavbarState();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbarNotification);
    } else {
        initNavbarNotification();
    }
})();
