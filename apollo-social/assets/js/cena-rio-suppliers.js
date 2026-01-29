/**
 * Cena-Rio Suppliers JavaScript
 *
 * Client-side functionality for the Cena-Rio suppliers catalog.
 *
 * @package Apollo\Modules\Suppliers
 * @since   1.0.0
 */

(function() {
	'use strict';

	// Guard to prevent multiple initializations
	if (window.apolloForneceInitialized) {
		return;
	}
	window.apolloForneceInitialized = true;

	// Configuration
	const config = window.apolloFornece || {};
	const baseUrl = config.baseUrl || '/fornece/';
	const ajaxUrl = config.ajaxUrl || '';
	const nonce = config.nonce || '';

	// Cache for DOM elements and state
	const cache = {
		container: null,
		modal: null,
		cards: new WeakSet(),
		currentSupplierId: null,
		filters: {},
	};

	// Utility functions
	const utils = {
		// Debounce function for search/filter inputs
		debounce: function(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		},

		// Check if element is in viewport
		isInViewport: function(element) {
			const rect = element.getBoundingClientRect();
			return (
				rect.top >= 0 &&
				rect.left >= 0 &&
				rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
				rect.right <= (window.innerWidth || document.documentElement.clientWidth)
			);
		},

		// Sanitize HTML
		sanitizeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},
	};

	// Modal management
	const modal = {
		show: function(supplierId) {
			if (!supplierId || cache.currentSupplierId === supplierId) {
				return;
			}

			cache.currentSupplierId = supplierId;
			this.loadSupplierData(supplierId);
		},

		hide: function() {
			if (cache.modal) {
				cache.modal.style.display = 'none';
				document.body.style.overflow = '';
			}
			cache.currentSupplierId = null;

			// Update URL
			if (window.history.replaceState) {
				window.history.replaceState(null, null, baseUrl);
			}
		},

		loadSupplierData: function(supplierId) {
			// Show loading state
			this.showLoadingModal();

			// Fetch supplier data via AJAX
			fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'apollo_get_supplier',
					supplier_id: supplierId,
					nonce: nonce,
				}),
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					this.renderModal(data.data);
					this.showModal();
				} else {
					console.error('Failed to load supplier:', data.message);
					this.showErrorModal();
				}
			})
			.catch(error => {
				console.error('Error loading supplier:', error);
				this.showErrorModal();
			});
		},

		showLoadingModal: function() {
			this.ensureModalExists();
			const content = cache.modal.querySelector('.supplier-modal__content');
			content.innerHTML = `
				<div class="supplier-modal__body" style="text-align: center; padding: 3rem;">
					<div style="font-size: 1.5rem; margin-bottom: 1rem;">Carregando...</div>
					<div style="border: 4px solid #f3f4f6; border-top: 4px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
				</div>
			`;
			this.showModal();
		},

		showErrorModal: function() {
			this.ensureModalExists();
			const content = cache.modal.querySelector('.supplier-modal__content');
			content.innerHTML = `
				<div class="supplier-modal__body" style="text-align: center; padding: 3rem;">
					<div style="font-size: 1.5rem; color: #dc2626; margin-bottom: 1rem;">Erro ao carregar fornecedor</div>
					<p style="color: #6b7280;">Tente novamente mais tarde.</p>
				</div>
			`;
			this.showModal();
		},

		renderModal: function(supplier) {
			this.ensureModalExists();
			const content = cache.modal.querySelector('.supplier-modal__content');

			const badges = [];
			if (supplier.category) badges.push(`<span class="supplier-card__badge supplier-card__badge--category">${utils.sanitizeHtml(supplier.category)}</span>`);
			if (supplier.region) badges.push(`<span class="supplier-card__badge supplier-card__badge--region">${utils.sanitizeHtml(supplier.region)}</span>`);
			if (supplier.type) badges.push(`<span class="supplier-card__badge supplier-card__badge--type">${utils.sanitizeHtml(supplier.type)}</span>`);

			const details = [];
			if (supplier.price_tier) details.push(`<div class="supplier-modal__detail"><div class="supplier-modal__detail-label">Faixa de Preço</div><div class="supplier-modal__detail-value">${utils.sanitizeHtml(supplier.price_tier)}</div></div>`);
			if (supplier.capacity) details.push(`<div class="supplier-modal__detail"><div class="supplier-modal__detail-label">Capacidade</div><div class="supplier-modal__detail-value">${supplier.capacity}</div></div>`);
			if (supplier.location) details.push(`<div class="supplier-modal__detail"><div class="supplier-modal__detail-label">Localização</div><div class="supplier-modal__detail-value">${utils.sanitizeHtml(supplier.location)}</div></div>`);

			const contactButtons = [];
			if (supplier.whatsapp) {
				contactButtons.push(`<a href="${utils.sanitizeHtml(supplier.whatsapp)}" class="supplier-modal__btn supplier-modal__btn--primary" target="_blank" rel="noopener">WhatsApp</a>`);
			}
			if (supplier.email) {
				contactButtons.push(`<a href="mailto:${utils.sanitizeHtml(supplier.email)}" class="supplier-modal__btn supplier-modal__btn--secondary">Email</a>`);
			}
			if (supplier.website) {
				contactButtons.push(`<a href="${utils.sanitizeHtml(supplier.website)}" class="supplier-modal__btn supplier-modal__btn--secondary" target="_blank" rel="noopener">Website</a>`);
			}

			content.innerHTML = `
				<button class="supplier-modal__close" aria-label="Fechar">&times;</button>
				<div class="supplier-modal__header">
					<h2 class="supplier-modal__title">${utils.sanitizeHtml(supplier.title)}</h2>
					<div class="supplier-modal__badges">${badges.join('')}</div>
				</div>
				<div class="supplier-modal__body">
					${supplier.image ? `<div style="text-align: center; margin-bottom: 2rem;"><img src="${utils.sanitizeHtml(supplier.image)}" alt="${utils.sanitizeHtml(supplier.title)}" style="max-width: 100%; height: auto; border-radius: 8px;"></div>` : ''}
					<div class="supplier-modal__description">${supplier.description || 'Sem descrição disponível.'}</div>
					<div class="supplier-modal__details">${details.join('')}</div>
				</div>
				<div class="supplier-modal__actions">
					<div class="supplier-modal__contact">${contactButtons.join('')}</div>
				</div>
			`;

			// Bind close events
			content.querySelector('.supplier-modal__close').addEventListener('click', () => this.hide());
		},

		showModal: function() {
			if (cache.modal) {
				cache.modal.style.display = 'flex';
				document.body.style.overflow = 'hidden';

				// Update URL
				if (window.history.replaceState && cache.currentSupplierId) {
					window.history.replaceState(null, null, `${baseUrl}${cache.currentSupplierId}/`);
				}
			}
		},

		ensureModalExists: function() {
			if (!cache.modal) {
				cache.modal = document.createElement('div');
				cache.modal.className = 'supplier-modal';
				cache.modal.innerHTML = '<div class="supplier-modal__content"></div>';
				cache.modal.addEventListener('click', (e) => {
					if (e.target === cache.modal) {
						this.hide();
					}
				});
				document.body.appendChild(cache.modal);
			}
		},
	};

	// Filter management
	const filters = {
		init: function() {
			const filterForm = document.querySelector('.suppliers-filters');
			if (!filterForm) return;

			// Bind filter changes
			filterForm.addEventListener('submit', (e) => {
				e.preventDefault();
				this.applyFilters();
			});

			filterForm.addEventListener('input', utils.debounce(() => {
				this.applyFilters();
			}, 300));

			filterForm.addEventListener('change', () => {
				this.applyFilters();
			});
		},

		applyFilters: function() {
			const formData = new FormData(document.querySelector('.suppliers-filters'));
			const filterParams = {};

			for (let [key, value] of formData.entries()) {
				if (value.trim()) {
					filterParams[key] = value.trim();
				}
			}

			// Update URL without reload
			const url = new URL(window.location);
			Object.keys(filterParams).forEach(key => {
				url.searchParams.set(key, filterParams[key]);
			});

			// Remove empty params
			for (let [key, value] of url.searchParams.entries()) {
				if (!value) {
					url.searchParams.delete(key);
				}
			}

			if (window.history.replaceState) {
				window.history.replaceState(null, null, url.toString());
			}

			// Apply filters to cards
			this.filterCards(filterParams);
		},

		filterCards: function(params) {
			const cards = document.querySelectorAll('.supplier-card');
			let visibleCount = 0;

			cards.forEach(card => {
				let show = true;

				// Category filter
				if (params.category && card.dataset.category !== params.category) {
					show = false;
				}

				// Region filter
				if (params.region && card.dataset.region !== params.region) {
					show = false;
				}

				// Type filter
				if (params.type && card.dataset.type !== params.type) {
					show = false;
				}

				// Search filter
				if (params.search) {
					const title = card.querySelector('.supplier-card__title').textContent.toLowerCase();
					const description = card.dataset.description ? card.dataset.description.toLowerCase() : '';
					const searchTerm = params.search.toLowerCase();

					if (!title.includes(searchTerm) && !description.includes(searchTerm)) {
						show = false;
					}
				}

				// Price tier filter
				if (params.price_tier && card.dataset.priceTier !== params.price_tier) {
					show = false;
				}

				// Capacity filter
				if (params.capacity && parseInt(card.dataset.capacity || 0) < parseInt(params.capacity)) {
					show = false;
				}

				if (show) {
					card.style.display = '';
					visibleCount++;
				} else {
					card.style.display = 'none';
				}
			});

			// Update empty state
			this.updateEmptyState(visibleCount === 0);
		},

		updateEmptyState: function(isEmpty) {
			let emptyState = document.querySelector('.suppliers-empty');
			if (isEmpty && !emptyState) {
				emptyState = document.createElement('div');
				emptyState.className = 'suppliers-empty';
				emptyState.innerHTML = `
					<h3>Nenhum fornecedor encontrado</h3>
					<p>Tente ajustar os filtros de busca.</p>
				`;
				document.querySelector('.suppliers-container').appendChild(emptyState);
			} else if (!isEmpty && emptyState) {
				emptyState.remove();
			}
		},
	};

	// Card interactions
	const cards = {
		init: function() {
			this.bindCardClicks();
			this.observeNewCards();
		},

		bindCardClicks: function() {
			const cardElements = document.querySelectorAll('.supplier-card');
			cardElements.forEach(card => {
				if (!cache.cards.has(card)) {
					cache.cards.add(card);
					card.addEventListener('click', (e) => {
						e.preventDefault();
						const supplierId = card.dataset.supplierId;
						if (supplierId) {
							modal.show(supplierId);
						}
					});
				}
			});
		},

		observeNewCards: function() {
			const observer = new MutationObserver((mutations) => {
				let shouldBind = false;
				mutations.forEach(mutation => {
					mutation.addedNodes.forEach(node => {
						if (node.nodeType === Node.ELEMENT_NODE) {
							if (node.classList.contains('supplier-card') || node.querySelector('.supplier-card')) {
								shouldBind = true;
							}
						}
					});
				});

				if (shouldBind) {
					// Use requestAnimationFrame to ensure DOM is ready
					requestAnimationFrame(() => {
						this.bindCardClicks();
					});
				}
			});

			const container = document.querySelector('.suppliers-grid') || document.body;
			observer.observe(container, {
				childList: true,
				subtree: true,
			});
		},
	};

	// History API support
	const history = {
		init: function() {
			window.addEventListener('popstate', (e) => {
				const path = window.location.pathname;
				const match = path.match(/\/fornece\/(\d+)\//);
				if (match) {
					modal.show(match[1]);
				} else {
					modal.hide();
				}
			});

			// Check initial URL
			const initialMatch = window.location.pathname.match(/\/fornece\/(\d+)\//);
			if (initialMatch) {
				modal.show(initialMatch[1]);
			}
		},
	};

	// Keyboard navigation
	const keyboard = {
		init: function() {
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && cache.modal && cache.modal.style.display === 'flex') {
					modal.hide();
				}
			});
		},
	};

	// Initialize everything
	function init() {
		// Initialize components
		filters.init();
		cards.init();
		history.init();
		keyboard.init();

		// Add CSS animation
		const style = document.createElement('style');
		style.textContent = `
			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}
		`;
		document.head.appendChild(style);
	}

	// Start when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
