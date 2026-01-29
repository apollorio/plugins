/*
Front-end beacon for event pages.
- Mirrors Plausible calls (if present) and posts compact payload to WP REST endpoint.
- Include this script on single event pages (enqueue already set).
*/

(function () {
	'use strict';

	// Get event ID from page
	function getEventId() {
		// Try to get from data attribute
		var eventElement = document.querySelector( '[data-event-id]' );
		if (eventElement) {
			return parseInt( eventElement.getAttribute( 'data-event-id' ), 10 );
		}

		// Try to get from URL
		var urlMatch = window.location.pathname.match( /\/evento\/(\d+)/ );
		if (urlMatch) {
			return parseInt( urlMatch[1], 10 );
		}

		// Try to get from post ID in body class
		var bodyClass = document.body.className.match( /postid-(\d+)/ );
		if (bodyClass) {
			return parseInt( bodyClass[1], 10 );
		}

		return 0;
	}

	// Send beacon to WordPress REST API
	function sendBeacon(eventType, eventName, properties) {
		var eventId = getEventId();
		if ( ! eventId) {
			return; // No event ID found
		}

		var payload = {
			event_id: eventId,
			event_type: eventType || 'pageview',
			event_name: eventName || null,
			properties: properties || null
		};

		// Use sendBeacon for reliability
		var blob    = new Blob( [JSON.stringify( payload )], { type: 'application/json' } );
		var restUrl = '/wp-json/apollo/v1/analytics';

		if (navigator.sendBeacon) {
			navigator.sendBeacon( restUrl, blob );
		} else {
			// Fallback to fetch
			fetch(
				restUrl,
				{
					method: 'POST',
					body: JSON.stringify( payload ),
					headers: {
						'Content-Type': 'application/json'
					},
					keepalive: true
				}
			).catch(
				function (err) {
					console.warn( 'Apollo beacon failed:', err );
				}
			);
		}
	}

	// Track pageview
	function trackPageview() {
		sendBeacon(
			'pageview',
			'Page View',
			{
				path: window.location.pathname,
				referrer: document.referrer
			}
		);
	}

	// Track like
	function trackLike(eventId) {
		fetch(
			'/wp-json/apollo/v1/likes',
			{
				method: 'POST',
				body: JSON.stringify( { event_id: eventId } ),
				headers: {
					'Content-Type': 'application/json'
				}
			}
		).then(
			function (response) {
				return response.json();
			}
		).then(
			function (data) {
				if (data.success) {
					// Update UI
					var likeButton = document.querySelector( '[data-like-button]' );
					if (likeButton) {
						likeButton.classList.toggle( 'liked', data.liked );
					}
				}
			}
		).catch(
			function (err) {
				console.warn( 'Apollo like failed:', err );
			}
		);
	}

	// Track share
	function trackShare(platform) {
		var eventId = getEventId();
		if ( ! eventId) {
			return;
		}

		sendBeacon(
			'event',
			'Share',
			{
				platform: platform,
				url: window.location.href
			}
		);
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener(
			'DOMContentLoaded',
			function () {
				trackPageview();
			}
		);
	} else {
		trackPageview();
	}

	// Expose functions globally
	window.apolloBeacon = {
		trackPageview: trackPageview,
		trackLike: trackLike,
		trackShare: trackShare,
		sendBeacon: sendBeacon
	};

	// Mirror Plausible events if present
	if (window.plausible) {
		var originalPlausible = window.plausible;
		window.plausible      = function (eventName, options) {
			// Call original
			originalPlausible( eventName, options );

			// Mirror to Apollo
			sendBeacon( 'event', eventName, options && options.props ? options.props : null );
		};
	}

	// Track like button clicks
	document.addEventListener(
		'click',
		function (e) {
			var likeButton = e.target.closest( '[data-like-button]' );
			if (likeButton) {
				e.preventDefault();
				var eventId = parseInt( likeButton.getAttribute( 'data-event-id' ), 10 ) || getEventId();
				trackLike( eventId );
			}

			// Track share buttons
			var shareButton = e.target.closest( '[data-share]' );
			if (shareButton) {
				var platform = shareButton.getAttribute( 'data-share' );
				trackShare( platform );
			}
		}
	);
})();
