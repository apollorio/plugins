/**
 * Apollo Classifieds JavaScript
 *
 * Handles:
 * - Directory filtering and search
 * - Create form validation and submission
 * - Safety modal interactions
 * - Chat integration
 *
 * @package Apollo_Social
 * @version 2.2.0
 */

(function ($) {
	'use strict';

	// Config from wp_localize_script
	const config = window.apolloClassifieds || {};
	const restUrl = config.restUrl || '/wp-json/apollo/v1';
	const nonce = config.nonce || '';
	const i18n = config.i18n || {};

	/**
	 * Directory Controller
	 */
	const Directory = {
		container: null,
		grid: null,
		filters: {},
		page: 1,
		loading: false,

		init() {
			this.container = $('.apollo-classifieds-directory');
			if (!this.container.length) return;

			this.grid = this.container.find('.classifieds-grid');
			this.bindEvents();
			this.loadFromUrl();
		},

		bindEvents() {
			// Filter change
			this.container.on('change', '[data-filter]', (e) => {
				const $el = $(e.currentTarget);
				this.filters[$el.data('filter')] = $el.val();
				this.page = 1;

				// Show/hide date filters based on domain
				if ($el.data('filter') === 'domain') {
					this.toggleDateFilters($el.val());
				}

				this.load();
			});

			// Search input with debounce
			let searchTimeout;
			this.container.on('input', '[data-filter="search"]', (e) => {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(() => {
					this.filters.search = $(e.currentTarget).val();
					this.page = 1;
					this.load();
				}, 300);
			});

			// Filter button
			this.container.on('click', '.btn-filter', (e) => {
				e.preventDefault();
				this.page = 1;
				this.load();
			});

			// Reset filters
			this.container.on('click', '.btn-filter-reset', (e) => {
				e.preventDefault();
				this.resetFilters();
			});

			// Pagination
			this.container.on('click', '.pagination-btn[data-page]', (e) => {
				e.preventDefault();
				const page = parseInt($(e.currentTarget).data('page'), 10);
				if (page && page !== this.page) {
					this.page = page;
					this.load();
					this.scrollToTop();
				}
			});
		},

		toggleDateFilters(domain) {
			const $dateFilters = this.container.find('.date-filters');
			const $ticketDate = $dateFilters.find('.filter-ticket-date');
			const $accomDates = $dateFilters.find('.filter-accom-dates');

			if (domain === 'ingressos') {
				$dateFilters.addClass('active');
				$ticketDate.show();
				$accomDates.hide();
			} else if (domain === 'acomodacao') {
				$dateFilters.addClass('active');
				$ticketDate.hide();
				$accomDates.show();
			} else {
				$dateFilters.removeClass('active');
			}
		},

		loadFromUrl() {
			const params = new URLSearchParams(window.location.search);

			['domain', 'intent', 'search', 'location', 'date_from', 'date_to'].forEach(key => {
				if (params.has(key)) {
					this.filters[key] = params.get(key);
					const $input = this.container.find(`[data-filter="${key}"]`);
					if ($input.length) {
						$input.val(this.filters[key]);
					}
				}
			});

			if (params.has('page')) {
				this.page = parseInt(params.get('page'), 10) || 1;
			}

			if (this.filters.domain) {
				this.toggleDateFilters(this.filters.domain);
			}

			this.load();
		},

		load() {
			if (this.loading) return;
			this.loading = true;

			this.grid.html(`
				<div class="classifieds-loading">
					<div class="loading-spinner"></div>
				</div>
			`);

			const params = new URLSearchParams();
			params.set('page', this.page);
			params.set('per_page', 12);

			Object.keys(this.filters).forEach(key => {
				if (this.filters[key]) {
					params.set(key, this.filters[key]);
				}
			});

			// Update URL
			const newUrl = `${window.location.pathname}?${params.toString()}`;
			window.history.replaceState({}, '', newUrl);

			$.ajax({
				url: `${restUrl}/classificados`,
				method: 'GET',
				data: params.toString(),
				headers: { 'X-WP-Nonce': nonce },
				success: (response) => {
					this.loading = false;

					if (response.success && response.data) {
						this.render(response.data);
					} else {
						this.renderEmpty();
					}
				},
				error: () => {
					this.loading = false;
					this.renderError();
				}
			});
		},

		render(data) {
			const { classifieds, total, page, pages } = data;

			if (!classifieds || classifieds.length === 0) {
				this.renderEmpty();
				return;
			}

			let html = '';

			classifieds.forEach(item => {
				html += this.renderCard(item);
			});

			this.grid.html(html);

			// Render pagination
			this.renderPagination(page, pages, total);
		},

		renderCard(item) {
			const thumbnail = item.thumbnail
				? `<img src="${item.thumbnail}" alt="${this.escapeHtml(item.title)}" />`
				: `<div class="card-image-placeholder">
					<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
						<circle cx="8.5" cy="8.5" r="1.5"/>
						<polyline points="21 15 16 10 5 21"/>
					</svg>
				</div>`;

			const dateLabel = item.domain === 'ingressos' && item.event_date
				? `<span class="card-meta-item">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
					${item.event_date}
				</span>`
				: item.period
				? `<span class="card-meta-item">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
					${item.period}
				</span>`
				: '';

			return `
				<article class="classified-card">
					<a href="${item.permalink}">
						<div class="card-image">
							${thumbnail}
							<div class="card-badges">
								${item.domain_label ? `<span class="card-badge badge-${item.domain}">${item.domain_label}</span>` : ''}
								${item.intent_label ? `<span class="card-badge badge-${item.intent}">${item.intent_label}</span>` : ''}
							</div>
						</div>
						<div class="card-content">
							<h3 class="card-title">${this.escapeHtml(item.title)}</h3>
							${item.price_formatted ? `<div class="card-price">${item.price_formatted}</div>` : ''}
							<div class="card-meta">
								${item.location ? `
									<span class="card-meta-item">
										<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
											<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
											<circle cx="12" cy="10" r="3"/>
										</svg>
										${this.escapeHtml(item.location)}
									</span>
								` : ''}
								${dateLabel}
							</div>
						</div>
						<div class="card-footer">
							<img src="${item.author.avatar_url}" alt="${this.escapeHtml(item.author.display_name)}" />
							<span>${this.escapeHtml(item.author.display_name)}</span>
						</div>
					</a>
				</article>
			`;
		},

		renderEmpty() {
			this.grid.html(`
				<div class="classifieds-empty">
					<svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="11" cy="11" r="8"/>
						<line x1="21" y1="21" x2="16.65" y2="16.65"/>
					</svg>
					<h3>${i18n.noResults || 'Nenhum anúncio encontrado'}</h3>
					<p>Tente ajustar os filtros ou fazer uma busca diferente.</p>
				</div>
			`);

			this.container.find('.classifieds-pagination').empty();
		},

		renderError() {
			this.grid.html(`
				<div class="classifieds-empty">
					<svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="12" cy="12" r="10"/>
						<line x1="12" y1="8" x2="12" y2="12"/>
						<line x1="12" y1="16" x2="12.01" y2="16"/>
					</svg>
					<h3>${i18n.error || 'Erro ao carregar'}</h3>
					<p>Tente novamente em alguns instantes.</p>
				</div>
			`);
		},

		renderPagination(currentPage, totalPages, totalItems) {
			let $pagination = this.container.find('.classifieds-pagination');

			if (!$pagination.length) {
				$pagination = $('<div class="classifieds-pagination"></div>');
				this.grid.after($pagination);
			}

			if (totalPages <= 1) {
				$pagination.empty();
				return;
			}

			let html = '';

			// Previous
			html += `<button class="pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="15 18 9 12 15 6"/>
				</svg>
			</button>`;

			// Page numbers
			const maxVisible = 5;
			let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
			let end = Math.min(totalPages, start + maxVisible - 1);

			if (end - start + 1 < maxVisible) {
				start = Math.max(1, end - maxVisible + 1);
			}

			if (start > 1) {
				html += `<button class="pagination-btn" data-page="1">1</button>`;
				if (start > 2) {
					html += `<span class="pagination-ellipsis">...</span>`;
				}
			}

			for (let i = start; i <= end; i++) {
				html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
			}

			if (end < totalPages) {
				if (end < totalPages - 1) {
					html += `<span class="pagination-ellipsis">...</span>`;
				}
				html += `<button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>`;
			}

			// Next
			html += `<button class="pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="9 18 15 12 9 6"/>
				</svg>
			</button>`;

			$pagination.html(html);
		},

		resetFilters() {
			this.filters = {};
			this.page = 1;

			this.container.find('[data-filter]').each((_, el) => {
				const $el = $(el);
				if ($el.is('select')) {
					$el.val('');
				} else {
					$el.val('');
				}
			});

			this.container.find('.date-filters').removeClass('active');
			this.load();
		},

		scrollToTop() {
			$('html, body').animate({
				scrollTop: this.container.offset().top - 100
			}, 300);
		},

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	/**
	 * Create Form Controller
	 */
	const CreateForm = {
		form: null,
		submitting: false,

		init() {
			this.form = $('.classified-form');
			if (!this.form.length) return;

			this.bindEvents();
		},

		bindEvents() {
			// Domain selection - show/hide conditional fields
			this.form.on('change', 'input[name="domain"]', (e) => {
				const domain = $(e.currentTarget).val();
				this.form.find('.conditional-fields').removeClass('active');
				this.form.find(`.conditional-fields[data-domain="${domain}"]`).addClass('active');
			});

			// Image upload
			const $uploadZone = this.form.find('.image-upload-zone');
			const $fileInput = this.form.find('input[name="images"]');

			$uploadZone.on('click', () => $fileInput.trigger('click'));

			$uploadZone.on('dragover', (e) => {
				e.preventDefault();
				$uploadZone.addClass('dragover');
			});

			$uploadZone.on('dragleave', () => {
				$uploadZone.removeClass('dragover');
			});

			$uploadZone.on('drop', (e) => {
				e.preventDefault();
				$uploadZone.removeClass('dragover');
				const files = e.originalEvent.dataTransfer.files;
				this.handleFiles(files);
			});

			$fileInput.on('change', (e) => {
				this.handleFiles(e.target.files);
			});

			// Remove image preview
			this.form.on('click', '.image-preview-remove', (e) => {
				$(e.currentTarget).closest('.image-preview').remove();
			});

			// Form submit
			this.form.on('submit', (e) => this.handleSubmit(e));
		},

		handleFiles(files) {
			const $previews = this.form.find('.image-previews');
			const maxImages = 5;
			const currentCount = $previews.find('.image-preview').length;

			Array.from(files).slice(0, maxImages - currentCount).forEach(file => {
				if (!file.type.startsWith('image/')) return;

				const reader = new FileReader();
				reader.onload = (e) => {
					const $preview = $(`
						<div class="image-preview">
							<img src="${e.target.result}" alt="Preview" />
							<button type="button" class="image-preview-remove">&times;</button>
						</div>
					`);
					$preview.data('file', file);
					$previews.append($preview);
				};
				reader.readAsDataURL(file);
			});
		},

		handleSubmit(e) {
			e.preventDefault();

			if (this.submitting) return;

			// Validation
			const domain = this.form.find('input[name="domain"]:checked').val();
			const intent = this.form.find('input[name="intent"]:checked').val();
			const title = this.form.find('input[name="title"]').val().trim();

			if (!domain) {
				this.showError('Selecione uma categoria (Ingressos ou Acomodação).');
				return;
			}

			if (!intent) {
				this.showError('Selecione o tipo de anúncio (Ofereço ou Procuro).');
				return;
			}

			if (!title) {
				this.showError('Digite um título para seu anúncio.');
				return;
			}

			// Domain-specific validation
			if (domain === 'ingressos') {
				const eventDate = this.form.find('input[name="event_date"]').val();
				if (!eventDate) {
					this.showError('Informe a data do evento.');
					return;
				}
			}

			if (domain === 'acomodacao') {
				const startDate = this.form.find('input[name="start_date"]').val();
				const endDate = this.form.find('input[name="end_date"]').val();
				if (!startDate || !endDate) {
					this.showError('Informe as datas de check-in e check-out.');
					return;
				}
			}

			// Build data
			const data = {
				title,
				domain,
				intent,
				description: this.form.find('textarea[name="description"]').val() || '',
				price: this.form.find('input[name="price"]').val() || '',
				location: this.form.find('input[name="location"]').val() || ''
			};

			// Domain-specific data
			if (domain === 'ingressos') {
				data.event_date = this.form.find('input[name="event_date"]').val();
				data.event_title = this.form.find('input[name="event_title"]').val() || '';
			}

			if (domain === 'acomodacao') {
				data.start_date = this.form.find('input[name="start_date"]').val();
				data.end_date = this.form.find('input[name="end_date"]').val();
				data.capacity = this.form.find('input[name="capacity"]').val() || '';
			}

			this.submit(data);
		},

		submit(data) {
			this.submitting = true;
			const $btn = this.form.find('.btn-submit');
			const originalText = $btn.text();
			$btn.text(i18n.loading || 'Publicando...').prop('disabled', true);

			$.ajax({
				url: `${restUrl}/classificados`,
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce
				},
				data: JSON.stringify(data),
				success: (response) => {
					this.submitting = false;
					$btn.text(originalText).prop('disabled', false);

					if (response.success && response.data) {
						// Redirect to the new ad
						window.location.href = response.data.permalink;
					} else {
						this.showError(response.message || 'Erro ao publicar anúncio.');
					}
				},
				error: (xhr) => {
					this.submitting = false;
					$btn.text(originalText).prop('disabled', false);

					const message = xhr.responseJSON?.message || 'Erro de conexão. Tente novamente.';
					this.showError(message);
				}
			});
		},

		showError(message) {
			// Remove existing error
			this.form.find('.form-error').remove();

			const $error = $(`
				<div class="form-error" role="alert">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"/>
						<line x1="12" y1="8" x2="12" y2="12"/>
						<line x1="12" y1="16" x2="12.01" y2="16"/>
					</svg>
					<span>${message}</span>
				</div>
			`);

			this.form.prepend($error);

			$('html, body').animate({
				scrollTop: this.form.offset().top - 100
			}, 300);
		}
	};

	/**
	 * Safety Modal Controller
	 */
	const SafetyModal = {
		modal: null,
		callback: null,

		init() {
			this.modal = $('#safety-modal');
			if (!this.modal.length) return;

			this.bindEvents();
		},

		bindEvents() {
			// Confirm button
			this.modal.on('click', '#safety-modal-confirm', () => {
				this.acknowledge();
				this.close();

				if (typeof this.callback === 'function') {
					this.callback();
				}
			});

			// Cancel button
			this.modal.on('click', '#safety-modal-cancel', () => {
				this.close();
			});

			// Backdrop click
			this.modal.on('click', '.modal-backdrop', () => {
				this.close();
			});

			// Escape key
			$(document).on('keydown', (e) => {
				if (e.key === 'Escape' && this.modal.hasClass('active')) {
					this.close();
				}
			});
		},

		open(callback) {
			this.callback = callback;
			this.modal.addClass('active');
			$('body').css('overflow', 'hidden');
		},

		close() {
			this.modal.removeClass('active');
			$('body').css('overflow', '');
			this.callback = null;
		},

		acknowledge() {
			const postId = this.modal.data('post-id');
			if (!postId) return;

			try {
				const ackList = JSON.parse(localStorage.getItem('apollo_safety_ack') || '[]');
				if (!ackList.includes(postId)) {
					ackList.push(postId);
					localStorage.setItem('apollo_safety_ack', JSON.stringify(ackList));
				}
			} catch (e) {
				// Ignore localStorage errors
			}

			// Track server-side
			$.ajax({
				url: `${restUrl}/classificados/${postId}/safety-ack`,
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce }
			});
		},

		hasAcknowledged(postId) {
			try {
				const ackList = JSON.parse(localStorage.getItem('apollo_safety_ack') || '[]');
				return ackList.includes(postId);
			} catch (e) {
				return false;
			}
		}
	};

	/**
	 * Contact Button Handler
	 */
	const ContactHandler = {
		init() {
			$(document).on('click', '.btn-contact, #btn-contact-seller', (e) => {
				e.preventDefault();

				const $btn = $(e.currentTarget);
				const authorId = $btn.data('author-id');
				const postId = $btn.data('post-id') || $btn.closest('[data-post-id]').data('post-id');

				if (!authorId) return;

				// Check if already acknowledged
				if (SafetyModal.hasAcknowledged(postId)) {
					this.openChat(authorId, postId);
				} else {
					SafetyModal.modal.data('post-id', postId);
					SafetyModal.open(() => {
						this.openChat(authorId, postId);
					});
				}
			});
		},

		openChat(authorId, postId) {
			// Try Apollo Chat integration
			if (window.ApolloChat && typeof window.ApolloChat.openConversation === 'function') {
				window.ApolloChat.openConversation({
					recipientId: authorId,
					contextType: 'classified',
					contextId: postId
				});
			} else {
				// Fallback: redirect to messages page
				window.location.href = `/mensagens/?user=${authorId}&context=classified&ref=${postId}`;
			}
		}
	};

	// Initialize on DOM ready
	$(function () {
		Directory.init();
		CreateForm.init();
		SafetyModal.init();
		ContactHandler.init();
	});

})(jQuery);
