/**
 * Apollo Cookie Consent Banner JavaScript
 *
 * @package Apollo_Core
 */

(function ($) {
	'use strict';

	var ApolloConsent = {
		/**
		 * Initialize consent handlers.
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind click events to consent buttons.
		 */
		bindEvents: function () {
			$( document ).on(
				'click',
				'[data-consent]',
				function (e) {
					e.preventDefault();
					var consent = $( this ).data( 'consent' );
					ApolloConsent.saveConsent( consent );
				}
			);
		},

		/**
		 * Save consent via AJAX and hide banner.
		 *
		 * @param {string} consent - 'accept' or 'decline'
		 */
		saveConsent: function (consent) {
			var $banner = $( '#apollo-cookie-consent-banner' );

			$.ajax(
				{
					url: apolloCookieConsent.ajaxUrl,
					type: 'POST',
					data: {
						action: 'apollo_save_consent',
						nonce: apolloCookieConsent.nonce,
						consent: consent
					},
					success: function (response) {
						if (response.success) {
							// Fade out banner.
							$banner.fadeOut(
								300,
								function () {
									$( this ).remove();
								}
							);

							// If accepted, trigger event for analytics to load.
							if (consent === 'accept') {
								$( document ).trigger( 'apollo_consent_accepted' );
								ApolloConsent.loadAnalytics();
							}
						}
					},
					error: function () {
						// On error, still hide the banner but set a session cookie.
						ApolloConsent.setSessionCookie( consent );
						$banner.fadeOut( 300 );
					}
				}
			);
		},

		/**
		 * Set a session cookie as fallback.
		 *
		 * @param {string} consent - 'accept' or 'decline'
		 */
		setSessionCookie: function (consent) {
			var value       = (consent === 'accept') ? 'accepted' : 'declined';
			document.cookie = 'apollo_cookie_consent=' + value + '; path=/';
		},

		/**
		 * Load analytics scripts after consent.
		 * This triggers any waiting analytics loaders.
		 */
		loadAnalytics: function () {
			// Trigger custom event for any deferred analytics.
			if (typeof window.apolloLoadDeferredAnalytics === 'function') {
				window.apolloLoadDeferredAnalytics();
			}

			// Dispatch native event for other listeners.
			window.dispatchEvent( new CustomEvent( 'apollo:analytics-consent-granted' ) );
		}
	};

	// Initialize when document is ready.
	$( document ).ready(
		function () {
			ApolloConsent.init();
		}
	);

})( jQuery );
