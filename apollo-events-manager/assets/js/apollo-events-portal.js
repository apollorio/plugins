/**
 * Apollo Events Portal - Main JavaScript
 * Handles modal, filters, search, and layout toggle
 *
 * @package ApolloEventsManager
 * @version 1.0.0
 */

(function ($) {
	'use strict';

	// Debug mode (fallback if not localized)
	const DEBUG = (typeof apolloPortalDebug !== 'undefined' && apolloPortalDebug.enabled === true) ||
		(typeof window.apolloPortalDebug !== 'undefined' && window.apolloPortalDebug.enabled === true) ||
		(window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

	/**
	 * Portal Manager Class
	 */
	const ApolloEventsPortal = {

		/**
		 * Initialize portal functionality
		 */
		init: function () {
			this.initModal();
			this.initFilters();
			this.initSearch();
			this.initLayoutToggle();
			this.initDatePicker();

			if (DEBUG) {
				console.log( 'Apollo Events Portal initialized' );
			}
		},

		/**
		 * Initialize modal functionality
		 */
		initModal: function () {
			const self = this;

			// Click handler for event cards (use event delegation for dynamically added cards)
			$( document ).on(
				'click',
				'.event_listing',
				function (e) {
					e.preventDefault();
					e.stopPropagation();

					const $card   = $( this );
					const eventId = $card.data( 'event-id' ) || $card.attr( 'data-event-id' );

					if ( ! eventId) {
						if (DEBUG) {
							console.error(
								'No event-id found on card',
								{
									element: this,
									hasDataAttr: $card.attr( 'data-event-id' ),
									allDataAttrs: $card.data()
								}
							);
						}
						return;
					}

					if (DEBUG) {
						console.log( 'Event card clicked:', eventId );
					}
					self.openModal( eventId );
				}
			);

			// Close modal handlers
			$( document ).on(
				'click',
				'[data-apollo-close]',
				function (e) {
					e.preventDefault();
					self.closeModal();
				}
			);

			// Close on ESC key
			$( document ).on(
				'keydown',
				function (e) {
					if (e.key === 'Escape' || e.keyCode === 27) {
						self.closeModal();
					}
				}
			);

			// Close on overlay click
			$( document ).on(
				'click',
				'.apollo-event-modal-overlay',
				function (e) {
					if (e.target === this) {
						self.closeModal();
					}
				}
			);
		},

		/**
		 * Open modal with event details
		 */
		openModal: function (eventId) {
			const self   = this;
			const $modal = $( '#apollo-event-modal' );

			// Store the element that triggered the modal (for focus restoration)
			this.lastFocusedElement = document.activeElement;

			if ( ! $modal.length) {
				if (DEBUG) {
					console.error( 'Modal container not found' );
				}
				// Create modal container if it doesn't exist
				$( 'body' ).append( '<div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>' );
				const $newModal = $( '#apollo-event-modal' );
				if ($newModal.length) {
					return this.openModal( eventId );
				}
				return;
			}

			// Check if apolloPortalAjax is defined
			if (typeof apolloPortalAjax === 'undefined') {
				if (DEBUG) {
					console.error( 'apolloPortalAjax not defined' );
				}
				alert( 'Erro: Scripts não carregados corretamente. Recarregue a página.' );
				return;
			}

			// Show loading state
			$modal.html( '<div class="apollo-modal-loading"><i class="ri-loader-4-line"></i> Carregando...</div>' );
			$modal.attr( 'aria-hidden', 'false' ).addClass( 'open' );
			$( 'body' ).addClass( 'apollo-modal-open' );

			// AJAX request for event details
			const ajaxData = {
				action: 'apollo_get_event_modal',
				event_id: eventId,
				nonce: apolloPortalAjax.nonce || ''
			};

			if (DEBUG) {
				console.log(
					'AJAX Request:',
					{
						url: apolloPortalAjax.ajaxurl || ajaxurl || '/wp-admin/admin-ajax.php',
						data: ajaxData,
						noncePresent: ! ! apolloPortalAjax.nonce
					}
				);
			}

			$.ajax(
				{
					url: apolloPortalAjax.ajaxurl || ajaxurl || '/wp-admin/admin-ajax.php',
					type: 'POST',
					data: ajaxData,
					timeout: 30000, // 30 second timeout
					success: function (response) {
						if (DEBUG) {
							console.log( 'AJAX Response:', response );
						}

						if (response.success && response.data && response.data.html) {
							$modal.html( response.data.html );
							self.scrollToTop();

							// Focus trap for accessibility
							self.trapFocus( $modal );

							// Trigger custom event
							$( document ).trigger( 'apollo:modal:opened', [eventId] );

							if (DEBUG) {
								console.log( 'Modal opened successfully for event:', eventId );
							}
						} else {
							const errorMsg = response.data && response.data.message
							? response.data.message
							: 'Erro ao carregar detalhes do evento.';
							$modal.html( '<div class="apollo-modal-error">' + errorMsg + '</div>' );
							if (DEBUG) {
								console.error( 'Modal AJAX error:', response );
							}
						}
					},
					error: function (xhr, status, error) {
						let errorMsg = 'Erro ao carregar evento. Tente novamente.';

						if (xhr.status === 0) {
							errorMsg = 'Erro de conexão. Verifique sua internet.';
						} else if (xhr.status === 403) {
							errorMsg = 'Acesso negado. Verifique se está logado.';
						} else if (xhr.status === 404) {
							errorMsg = 'Evento não encontrado.';
						}

						$modal.html( '<div class="apollo-modal-error">' + errorMsg + '</div>' );

						if (DEBUG) {
							console.error(
								'Modal AJAX request failed:',
								{
									status: xhr.status,
									statusText: xhr.statusText,
									error: error,
									responseText: xhr.responseText
								}
							);
						}
					}
				}
			);
		},

		/**
		 * Close modal
		 */
		closeModal: function () {
			const $modal = $( '#apollo-event-modal' );

			// Restore focus to the element that opened the modal
			if (this.lastFocusedElement) {
				this.lastFocusedElement.focus();
				this.lastFocusedElement = null;
			}

			$modal.attr( 'aria-hidden', 'true' ).removeClass( 'open' );
			$( 'body' ).removeClass( 'apollo-modal-open' );
			$modal.html( '' );

			$( document ).trigger( 'apollo:modal:closed' );

			if (DEBUG) {
				console.log( 'Modal closed' );
			}
		},

		/**
		 * Trap focus inside modal for accessibility
		 */
		trapFocus: function ($modal) {
			const focusableElements = $modal.find(
				'a[href], button:not([disabled]), textarea:not([disabled]), ' +
				'input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
			).filter( ':visible' );

			if (focusableElements.length === 0) {
				return;
			}

			const firstElement = focusableElements.first();
			const lastElement  = focusableElements.last();

			// Focus first element
			firstElement.focus();

			// Trap focus on Tab key
			$modal.on(
				'keydown',
				function (e) {
					if (e.key !== 'Tab' && e.keyCode !== 9) {
						return;
					}

					if (e.shiftKey) {
						// Shift + Tab
						if (document.activeElement === firstElement[0]) {
							e.preventDefault();
							lastElement.focus();
						}
					} else {
						// Tab
						if (document.activeElement === lastElement[0]) {
							e.preventDefault();
							firstElement.focus();
						}
					}
				}
			);
		},

		/**
		 * Scroll to top of modal
		 */
		scrollToTop: function () {
			const $modalContent = $( '.apollo-event-modal-content' );
			if ($modalContent.length) {
				$modalContent.scrollTop( 0 );
			}
		},

		/**
		 * Initialize category filters
		 */
		initFilters: function () {
			const self = this;

			// Category filter buttons
			$( document ).on(
				'click',
				'.event-category',
				function (e) {
					e.preventDefault();

					const $button = $( this );
					const slug    = $button.data( 'slug' ) || 'all';

					// Update active state and aria-pressed
					$( '.event-category' ).removeClass( 'active' ).attr( 'aria-pressed', 'false' );
					$button.addClass( 'active' ).attr( 'aria-pressed', 'true' );

					// Filter events
					self.filterEvents( 'category', slug );

					if (DEBUG) {
						console.log( 'Filter by category:', slug );
					}
				}
			);

			// Local filter buttons
			$( document ).on(
				'click',
				'.event-local-filter',
				function (e) {
					e.preventDefault();

					const $button = $( this );
					const slug    = $button.data( 'slug' ) || '';

					// Update active state and aria-pressed
					$( '.event-local-filter' ).removeClass( 'active' ).attr( 'aria-pressed', 'false' );
					$button.addClass( 'active' ).attr( 'aria-pressed', 'true' );

					// Filter events
					self.filterEvents( 'local', slug );

					if (DEBUG) {
						console.log( 'Filter by local:', slug );
					}
				}
			);
		},

		/**
		 * Filter events by type and value
		 * Now supports combining multiple filters
		 */
		filterEvents: function (type, value) {
			// Store current filter state
			if ( ! this.filterState) {
				this.filterState = {
					category: 'all',
					local: '',
					month: null,
					year: null,
					search: ''
				};
			}

			// Update filter state
			if (type === 'category') {
				this.filterState.category = value;
			} else if (type === 'local') {
				this.filterState.local = value;
			}

			// Apply all filters together
			this.applyAllFilters();
		},

		/**
		 * Apply all active filters together
		 */
		applyAllFilters: function () {
			const state = this.filterState || {
				category: 'all',
				local: '',
				month: null,
				year: null,
				search: ''
			};

			const $events    = $( '.event_listing' );
			let visibleCount = 0;

			$events.each(
				function () {
					const $event = $( this );
					let show     = true;

					// Category filter
					if (state.category !== 'all') {
						const eventCategory = $event.data( 'category' ) || 'general';
						if (eventCategory !== state.category) {
							show = false;
						}
					}

					// Local filter
					if (show && state.local) {
						const eventLocal           = $event.data( 'local-slug' ) || '';
						const normalizedEventLocal = eventLocal.toLowerCase().replace( /-/g, '' );
						const normalizedValue      = state.local.toLowerCase().replace( /-/g, '' );
						if (normalizedEventLocal !== normalizedValue) {
							show = false;
						}
					}

					// Month filter
					if (show && state.month !== null && state.year !== null) {
						const eventDateStr  = $event.data( 'event-start-date' ) || '';
						const eventMonthStr = $event.data( 'month-str' ) || '';

						if (eventDateStr) {
							const eventDate = new Date( eventDateStr );
							if (eventDate.getMonth() !== state.month || eventDate.getFullYear() !== state.year) {
								show = false;
							}
						} else if (eventMonthStr) {
							const monthNames      = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
							const targetMonthName = monthNames[state.month];
							if (eventMonthStr.toLowerCase() !== targetMonthName) {
								show = false;
							}
						}
					}

					// Search filter
					if (show && state.search) {
						const query        = state.search.toLowerCase();
						const title        = ($event.find( '.event-li-title, .event-title, h3, h2' ).first().text() || '').toLowerCase();
						const djText       = ($event.find( '.of-dj span, .dj-names' ).text() || '').toLowerCase();
						const locationText = ($event.find( '.of-location span, .location-name' ).text() || '').toLowerCase();

						const matches = title.indexOf( query ) !== -1 ||
						djText.indexOf( query ) !== -1 ||
						locationText.indexOf( query ) !== -1;

						if ( ! matches) {
							show = false;
						}
					}

					if (show) {
						$event.show();
						visibleCount++;
					} else {
						$event.hide();
					}
				}
			);

			// Show/hide "no events" message
			this.updateNoEventsMessage( visibleCount );

			if (DEBUG) {
				console.log( 'Filters applied:', state, '- Visible events:', visibleCount );
			}
		},

		/**
		 * Update "no events" message
		 */
		updateNoEventsMessage: function (visibleCount) {
			const $noEvents = $( '.no-events-found' );
			if (visibleCount === 0) {
				const message = 'Nenhum evento encontrado para este filtro. Tente outro estilo ou data.';
				if ( ! $noEvents.length) {
					$( '.event_listings' ).after( '<p class="no-events-found" role="alert">' + message + '</p>' );
				} else {
					$noEvents.text( message );
				}
			} else {
				$noEvents.remove();
			}
		},

		/**
		 * Initialize search functionality
		 */
		initSearch: function () {
			const self = this;
			let searchTimeout;

			$( '#eventSearchInput' ).on(
				'input keyup',
				function () {
					const $input = $( this );
					const query  = $input.val().toLowerCase().trim();

					// Debounce search
					clearTimeout( searchTimeout );
					searchTimeout = setTimeout(
						function () {
							self.searchEvents( query );
						},
						300
					);
				}
			);
		},

		/**
		 * Search events by query
		 * Now integrates with other filters
		 */
		searchEvents: function (query) {
			// Initialize filter state if needed
			if ( ! this.filterState) {
				this.filterState = {
					category: 'all',
					local: '',
					month: null,
					year: null,
					search: ''
				};
			}

			// Update search state
			this.filterState.search = query || '';

			// Apply all filters together
			this.applyAllFilters();

			if (DEBUG) {
				console.log( 'Search query:', query );
			}
		},

		/**
		 * Initialize layout toggle
		 */
		initLayoutToggle: function () {
			const self      = this;
			const $toggle   = $( '#aprio-event-toggle-layout' );
			const $listings = $( '.event_listings' );

			if ( ! $toggle.length) {
				return;
			}

			// Check initial state
			const initialLayout = $toggle.attr( 'data-layout' ) || 'list';
			if (initialLayout === 'card') {
				self.setCardLayout();
			} else {
				self.setListLayout();
			}

			// Toggle handler
			$toggle.on(
				'click',
				function (e) {
					e.preventDefault();

					const currentLayout = $toggle.attr( 'data-layout' ) || 'list';
					const newLayout     = currentLayout === 'list' ? 'card' : 'list';

					if (newLayout === 'card') {
						self.setCardLayout();
					} else {
						self.setListLayout();
					}

					if (DEBUG) {
						console.log( 'Layout toggled to:', newLayout );
					}
				}
			);
		},

		/**
		 * Set card layout
		 */
		setCardLayout: function () {
			const $toggle   = $( '#aprio-event-toggle-layout' );
			const $listings = $( '.event_listings' );

			$toggle.attr( 'data-layout', 'card' ).attr( 'aria-pressed', 'false' );
			$listings.removeClass( 'list-view' ).addClass( 'card-view' );

			// Update icon if needed
			const $icon = $toggle.find( 'i' );
			if ($icon.length) {
				$icon.removeClass( 'ri-list-check-2' ).addClass( 'ri-building-3-fill' );
			}
		},

		/**
		 * Set list layout
		 */
		setListLayout: function () {
			const $toggle   = $( '#aprio-event-toggle-layout' );
			const $listings = $( '.event_listings' );

			$toggle.attr( 'data-layout', 'list' ).attr( 'aria-pressed', 'true' );
			$listings.removeClass( 'card-view' ).addClass( 'list-view' );

			// Update icon if needed
			const $icon = $toggle.find( 'i' );
			if ($icon.length) {
				$icon.removeClass( 'ri-building-3-fill' ).addClass( 'ri-list-check-2' );
			}
		},

		/**
		 * Initialize date picker
		 */
		initDatePicker: function () {
			const self     = this;
			const $prev    = $( '#datePrev' );
			const $next    = $( '#dateNext' );
			const $display = $( '#dateDisplay' );

			if ( ! $prev.length || ! $next.length || ! $display.length) {
				return;
			}

			let currentMonth = new Date().getMonth();
			let currentYear  = new Date().getFullYear();

			// Update display
			function updateDisplay() {
				const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
				$display.text( monthNames[currentMonth] );
				self.filterEventsByMonth( currentMonth, currentYear );
			}

			// Previous month
			$prev.on(
				'click',
				function (e) {
					e.preventDefault();
					currentMonth--;
					if (currentMonth < 0) {
						currentMonth = 11;
						currentYear--;
					}
					updateDisplay();
				}
			);

			// Next month
			$next.on(
				'click',
				function (e) {
					e.preventDefault();
					currentMonth++;
					if (currentMonth > 11) {
						currentMonth = 0;
						currentYear++;
					}
					updateDisplay();
				}
			);

			// Initial display
			updateDisplay();
		},

		/**
		 * Filter events by month
		 */
		filterEventsByMonth: function (month, year) {
			// Initialize filter state if needed
			if ( ! this.filterState) {
				this.filterState = {
					category: 'all',
					local: '',
					month: null,
					year: null,
					search: ''
				};
			}

			// Update month/year state
			this.filterState.month = month;
			this.filterState.year  = year;

			// Apply all filters together
			this.applyAllFilters();

			if (DEBUG) {
				console.log( 'Filter by month:', month, year );
			}
		}
	};

	// Initialize when DOM is ready
	$( document ).ready(
		function () {
			ApolloEventsPortal.init();
		}
	);

})( jQuery );
