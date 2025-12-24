/**
 * Apollo Events - Modal/Lightbox Handler
 * Micromodal-based event modal system
 */

(function () {
	'use strict';

	const MODAL_ID       = 'apollo-event-modal';
	const LOADING_MARKUP =
		'<div class="flex items-center justify-center p-8">' +
		'<i class="ri-loader-4-line animate-spin text-4xl"></i>' +
		'</div>';

	function setBodyScrollLocked(locked) {
		if (locked) {
			document.documentElement.classList.add( 'apollo-modal-open' );
			document.body.classList.add( 'apollo-modal-open' );
			document.body.style.overflow = 'hidden';
			document.body.style.position = 'fixed';
			document.body.style.width    = '100%';
			document.body.style.height   = '100vh';
		} else {
			document.documentElement.classList.remove( 'apollo-modal-open' );
			document.body.classList.remove( 'apollo-modal-open' );
			document.body.style.overflow = '';
			document.body.style.position = '';
			document.body.style.width    = '';
			document.body.style.height   = '';
		}
	}

	if (typeof MicroModal !== 'undefined') {
		MicroModal.init(
			{
				disableScroll: true,
				disableFocus: false,
				awaitOpenAnimation: true,
				awaitCloseAnimation: true,
				debugMode: false
			}
		);
	}

	document.addEventListener(
		'modal:open',
		function (event) {
			if (event && event.detail && event.detail.modalId === MODAL_ID) {
				setBodyScrollLocked( true );
			}
		}
	);

	document.addEventListener(
		'modal:close',
		function (event) {
			if (event && event.detail && event.detail.modalId === MODAL_ID) {
				setBodyScrollLocked( false );
			}
		}
	);

	document.addEventListener(
		'click',
		function (event) {
			if (
			event.target &&
			event.target.hasAttribute( 'data-micromodal-close' )
			) {
				const modal = document.getElementById( MODAL_ID );
				if (modal) {
					modal.classList.remove( 'is-open' );
					modal.setAttribute( 'aria-hidden', 'true' );
				}
				setBodyScrollLocked( false );
			}
		}
	);

	document.addEventListener(
		'keydown',
		function (event) {
			if (event.key === 'Escape') {
				const modal = document.getElementById( MODAL_ID );
				if (modal && modal.classList.contains( 'is-open' )) {
					setBodyScrollLocked( false );
				}
			}
		}
	);

	document.addEventListener(
		'click',
		function (event) {
			const trigger = event.target.closest( '[data-event-modal]' );
			if ( ! trigger) {
				return;
			}

			event.preventDefault();
			const eventId = trigger.dataset.eventModal || trigger.dataset.eventId;

			if ( ! eventId) {
				console.error( 'Evento sem ID' );
				return;
			}

			loadEventModal( eventId );
		}
	);

	function resolveAjaxUrl() {
		if (
			typeof apollo_events_ajax !== 'undefined' &&
			(apollo_events_ajax.url || apollo_events_ajax.ajax_url)
		) {
			return apollo_events_ajax.url || apollo_events_ajax.ajax_url;
		}

		if (typeof ajaxurl !== 'undefined') {
			return ajaxurl;
		}

		return '';
	}

	function resolveNonce() {
		if (typeof apollo_events_ajax !== 'undefined') {
			return apollo_events_ajax.nonce || '';
		}

		if (typeof apolloEvents !== 'undefined') {
			return apolloEvents.nonce || '';
		}

		return '';
	}

	function loadEventModal(eventId) {
		let modal = document.getElementById( MODAL_ID );
		if ( ! modal) {
			createModalContainer();
			modal = document.getElementById( MODAL_ID );
		}

		const modalContent = document.getElementById(
			'apollo-event-modal-content'
		);
		if ( ! modalContent) {
			return;
		}

		modalContent.innerHTML = LOADING_MARKUP;

		const ajaxUrl = resolveAjaxUrl();
		if ( ! ajaxUrl) {
			console.error( 'AJAX URL não definida' );
			modalContent.innerHTML =
				'<div class="p-8 text-center text-destructive">' +
				'Configuração AJAX ausente' +
				'</div>';
			return;
		}

		const nonceValue = resolveNonce();

		if (typeof MicroModal !== 'undefined') {
			MicroModal.show( MODAL_ID );
		} else {
			modal.classList.add( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'false' );
			setBodyScrollLocked( true );
		}

		const params = new URLSearchParams(
			{
				action: 'apollo_get_event_modal',
				event_id: eventId,
				_ajax_nonce: nonceValue
			}
		);

		fetch(
			ajaxUrl,
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: params
			}
		)
			.then(
				function (response) {
					return response.text();
				}
			)
			.then(
				function (text) {
					const trimmed = text.trim();
					if (trimmed === '-1') {
						const nonceError = new Error( 'nonce_invalid' );
						nonceError.raw   = trimmed;
						throw nonceError;
					}

					try {
						return JSON.parse( text );
					} catch (error) {
						const parseError = new Error( 'invalid_json' );
						parseError.raw   = text;
						throw parseError;
					}
				}
			)
			.then(
				function (resp) {
					if (resp.success && resp.data && resp.data.html) {
						modalContent.innerHTML = resp.data.html;

						// ✅ FORCE: Trigger event to initialize map after modal content loads
						var event = new CustomEvent(
							'apollo:modal:content:loaded',
							{
								detail: { eventId: eventId }
							}
						);
						document.dispatchEvent( event );

						// ✅ FORCE: Initialize map after a short delay to ensure DOM is ready
						setTimeout(
							function () {
								// Trigger map initialization
								var mapInitEvent = new CustomEvent(
									'apollo:map:init',
									{
										detail: { eventId: eventId }
									}
								);
								document.dispatchEvent( mapInitEvent );

								// Also try direct initialization
								if (typeof L !== 'undefined') {
									var mapEl = document.getElementById( 'eventMap' );
									if (mapEl && mapEl.dataset.lat && mapEl.dataset.lng) {
										try {
											var lat = parseFloat( mapEl.dataset.lat );
											var lng = parseFloat( mapEl.dataset.lng );
											if (lat && lng && lat !== 0 && lng !== 0) {
												var osmSettings = window.apolloOSM || {};
												var defaultZoom = parseInt( osmSettings.defaultZoom, 10 );
												defaultZoom     = defaultZoom && defaultZoom >= 1 ? defaultZoom : 15;
												var tileStyle   = osmSettings.tileStyle || 'default';

												var tileConfig = (function (style) {
													switch (style) {
														case 'light':
															return {
																url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
																attribution: '© OpenStreetMap © CARTO',
																maxZoom: 22,
														};
														case 'dark':
															return {
																url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
																attribution: '© OpenStreetMap © CARTO',
																maxZoom: 22,
														};
														default:
															return {
																url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
																attribution: '© OpenStreetMap',
																maxZoom: 19,
														};
													}
												})( tileStyle );

												// Check if map already initialized
												if ( ! mapEl._leaflet_id) {
														var map = L.map(
															'eventMap',
															{
																zoomControl: false,
																scrollWheelZoom: false,
																dragging: false,
																touchZoom: false,
																doubleClickZoom: false,
																boxZoom: false,
																keyboard: false,
																attributionControl: false
															}
														).setView( [lat, lng], defaultZoom );

														L.tileLayer(
															tileConfig.url,
															{
																attribution: tileConfig.attribution,
																maxZoom: tileConfig.maxZoom
															}
														).addTo( map );

														var markerText = mapEl.dataset.marker || 'Local do Evento';
														L.marker( [lat, lng] ).addTo( map ).bindPopup( markerText );

														setTimeout(
															function () {
																map.invalidateSize();
															},
															100
														);

														console.log( '✅ Map initialized in modal' );
												}
											}
										} catch (e) {
											console.error( '❌ Map init error in modal:', e );
										}
									}
								}
							},
							300
						);

						return;
					}

					modalContent.innerHTML =
					'<div class="p-8 text-center text-destructive">' +
					'Erro ao carregar evento' +
					'</div>';
					console.error( 'Erro ao carregar modal:', resp );
				}
			)
			.catch(
				function (error) {
					let message = 'Erro de conexão';

					if (error && error.message === 'nonce_invalid') {
						message = 'Sessão expirada. Recarregue a página.';
					}

					modalContent.innerHTML =
					'<div class="p-8 text-center text-destructive">' +
					message +
					'</div>';
					console.error( 'Erro ao carregar modal:', error );
				}
			);
	}

	function createModalContainer() {
		const modal     = document.createElement( 'div' );
		modal.id        = MODAL_ID;
		modal.className = 'modal micromodal-slide';
		modal.setAttribute( 'aria-hidden', 'true' );

		modal.innerHTML =
			'<div class="modal__overlay" tabindex="-1" data-micromodal-close></div>' +
			'<div class="modal__container" role="dialog" aria-modal="true">' +
			'<button class="modal__close" aria-label="Fechar" data-micromodal-close>' +
			'<i class="ri-close-line"></i>' +
			'</button>' +
			'<div id="apollo-event-modal-content" class="modal__content">' +
			'<!-- Conteúdo dinâmico aqui -->' +
			'</div>' +
			'</div>';

		modal.addEventListener(
			'click',
			function (event) {
				if (event.target.classList.contains( 'modal__overlay' )) {
					modal.classList.remove( 'is-open' );
					modal.setAttribute( 'aria-hidden', 'true' );
					setBodyScrollLocked( false );
				}
				if (event.target.hasAttribute( 'data-micromodal-close' )) {
					modal.classList.remove( 'is-open' );
					modal.setAttribute( 'aria-hidden', 'true' );
					setBodyScrollLocked( false );
				}
			}
		);

		document.body.appendChild( modal );
	}

	window.openEventModal = loadEventModal;

})();
