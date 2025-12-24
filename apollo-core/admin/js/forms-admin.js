/**
 * Apollo Core - Forms Admin JavaScript
 *
 * Handles drag-and-drop, modal, and AJAX operations for form builder
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/admin/js/forms-admin.js
 */

(function($) {
	'use strict';

	const ApolloFormsAdmin = {
		schema: [],
		currentEditIndex: null,

		init: function() {
			this.loadSchemaFromDOM();
			this.bindEvents();
			this.initSortable();
		},

		bindEvents: function() {
			// Add field button
			$('#apollo-add-field-btn').on('click', () => this.openAddFieldModal());

			// Edit field buttons
			$(document).on('click', '.apollo-edit-field-btn', (e) => {
				const index = $(e.currentTarget).data('index');
				this.openEditFieldModal(index);
			});

			// Duplicate field buttons
			$(document).on('click', '.apollo-duplicate-field-btn', (e) => {
				const index = $(e.currentTarget).data('index');
				this.duplicateField(index);
			});

			// Delete field buttons
			$(document).on('click', '.apollo-delete-field-btn', (e) => {
				const index = $(e.currentTarget).data('index');
				this.deleteField(index);
			});

			// Save schema button
			$('#apollo-save-schema-btn').on('click', () => this.saveSchema());

			// Revert button
			$('#apollo-revert-schema-btn').on('click', () => location.reload());

			// Export button
			$('#apollo-export-schema-btn').on('click', () => this.exportSchema());

			// Modal close
			$('.apollo-modal-close').on('click', () => this.closeModal());

			// Modal form submit
			$('#apollo-field-form').on('submit', (e) => {
				e.preventDefault();
				this.saveField();
			});

			// Click outside modal to close
			$('#apollo-field-modal').on('click', (e) => {
				if ($(e.target).is('#apollo-field-modal')) {
					this.closeModal();
				}
			});
		},

		initSortable: function() {
			$('#apollo-fields-tbody').sortable({
				handle: '.apollo-drag-handle',
				placeholder: 'apollo-sortable-placeholder',
				update: () => {
					this.updateOrderFromDOM();
					this.updatePreview();
				}
			});
		},

		loadSchemaFromDOM: function() {
			this.schema = [];
			$('#apollo-fields-tbody .apollo-field-row').each((index, row) => {
				const fieldData = $(row).data('field');
				if (fieldData) {
					fieldData.order = index + 1;
					this.schema.push(fieldData);
				}
			});
		},

		openAddFieldModal: function() {
			this.currentEditIndex = null;
			$('#apollo-modal-title').text(apolloFormsAdmin.strings.addField);
			$('#apollo-field-form')[0].reset();
			$('#field-visible').prop('checked', true);
			$('#field-index').val('');
			$('#apollo-field-modal').fadeIn();
		},

		openEditFieldModal: function(index) {
			this.currentEditIndex = index;
			const field = this.schema[index];

			$('#apollo-modal-title').text(apolloFormsAdmin.strings.editField);
			$('#field-key').val(field.key);
			$('#field-label').val(field.label);
			$('#field-type').val(field.type);
			$('#field-required').prop('checked', field.required);
			$('#field-visible').prop('checked', field.visible);
			$('#field-default').val(field.default);
			$('#field-validation').val(field.validation);
			$('#field-index').val(index);

			$('#apollo-field-modal').fadeIn();
		},

		closeModal: function() {
			$('#apollo-field-modal').fadeOut();
			this.currentEditIndex = null;
		},

		saveField: function() {
			const formData = {
				key: $('#field-key').val().trim(),
				label: $('#field-label').val().trim(),
				type: $('#field-type').val(),
				required: $('#field-required').is(':checked'),
				visible: $('#field-visible').is(':checked'),
				default: $('#field-default').val(),
				validation: $('#field-validation').val().trim(),
				order: 0 // Will be set based on position
			};

			// Validate
			if (!formData.key || !formData.label || !formData.type) {
				alert('Please fill in all required fields.');
				return;
			}

			// Check for duplicate keys (except when editing same field)
			const isDuplicate = this.schema.some((field, index) => {
				return field.key === formData.key && index !== this.currentEditIndex;
			});

			if (isDuplicate) {
				alert('A field with this key already exists. Please use a unique key.');
				return;
			}

			if (this.currentEditIndex !== null) {
				// Edit existing field
				this.schema[this.currentEditIndex] = formData;
			} else {
				// Add new field
				formData.order = this.schema.length + 1;
				this.schema.push(formData);
			}

			this.updateOrderFromDOM();
			this.renderTable();
			this.updatePreview();
			this.closeModal();
		},

		duplicateField: function(index) {
			const field = { ...this.schema[index] };
			field.key = field.key + '_copy';
			field.order = this.schema.length + 1;
			this.schema.push(field);
			this.renderTable();
			this.updatePreview();
		},

		deleteField: function(index) {
			if (!confirm(apolloFormsAdmin.strings.confirmDelete)) {
				return;
			}

			this.schema.splice(index, 1);
			this.renderTable();
			this.updatePreview();
		},

		updateOrderFromDOM: function() {
			$('#apollo-fields-tbody .apollo-field-row').each((index, row) => {
				const fieldIndex = $(row).data('index');
				if (this.schema[fieldIndex]) {
					this.schema[fieldIndex].order = index + 1;
				}
			});

			// Re-sort schema by order
			this.schema.sort((a, b) => a.order - b.order);
		},

		renderTable: function() {
			const tbody = $('#apollo-fields-tbody');
			tbody.empty();

			this.schema.forEach((field, index) => {
				const row = this.createFieldRow(field, index);
				tbody.append(row);
			});

			// Reinitialize sortable
			tbody.sortable('refresh');
		},

		createFieldRow: function(field, index) {
			const requiredIcon = field.required 
				? '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>' 
				: '<span class="dashicons dashicons-no-alt" style="color: #ccc;"></span>';

			const visibleIcon = field.visible 
				? '<span class="dashicons dashicons-visibility" style="color: blue;"></span>' 
				: '<span class="dashicons dashicons-hidden" style="color: #ccc;"></span>';

			return `
				<tr class="apollo-field-row" data-index="${index}" data-field='${JSON.stringify(field)}'>
					<td class="apollo-drag-handle"><span class="dashicons dashicons-menu"></span></td>
					<td class="apollo-field-key"><code>${this.escapeHtml(field.key)}</code></td>
					<td class="apollo-field-label">${this.escapeHtml(field.label)}</td>
					<td class="apollo-field-type"><span class="apollo-type-badge">${this.escapeHtml(field.type)}</span></td>
					<td class="apollo-field-required">${requiredIcon}</td>
					<td class="apollo-field-visible">${visibleIcon}</td>
					<td class="apollo-field-validation"><code>${this.escapeHtml(field.validation)}</code></td>
					<td class="apollo-field-actions">
						<button type="button" class="button button-small apollo-edit-field-btn" data-index="${index}">Edit</button>
						<button type="button" class="button button-small apollo-duplicate-field-btn" data-index="${index}">Duplicate</button>
						<button type="button" class="button button-small button-link-delete apollo-delete-field-btn" data-index="${index}">Delete</button>
					</td>
				</tr>
			`;
		},

		saveSchema: function() {
			const formType = $('#apollo-current-form-type').val();
			const $button = $('#apollo-save-schema-btn');

			$button.prop('disabled', true).text('Saving...');

			$.ajax({
				url: apolloFormsAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apollo_save_form_schema',
					nonce: apolloFormsAdmin.nonce,
					form_type: formType,
					schema: JSON.stringify(this.schema)
				},
				success: (response) => {
					if (response.success) {
						alert('Schema saved successfully!');
						location.reload();
					} else {
						alert('Error: ' + (response.data.message || 'Unknown error'));
					}
				},
				error: (xhr) => {
					alert('AJAX error: ' + xhr.statusText);
				},
				complete: () => {
					$button.prop('disabled', false).text('Save Changes');
				}
			});
		},

		exportSchema: function() {
			const formType = $('#apollo-current-form-type').val();
			const dataStr = JSON.stringify(this.schema, null, 2);
			const dataBlob = new Blob([dataStr], { type: 'application/json' });
			const url = URL.createObjectURL(dataBlob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `apollo-form-schema-${formType}.json`;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(url);
		},

		updatePreview: function() {
			// TODO: Implement live preview update
			// For now, just reload preview
			console.log('Preview update - schema:', this.schema);
		},

		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	// Initialize on document ready
	$(document).ready(() => {
		if ($('.apollo-forms-admin-wrap').length) {
			ApolloFormsAdmin.init();
		}
	});

})(jQuery);

