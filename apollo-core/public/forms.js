/**
 * Apollo Core - Public Forms JavaScript
 *
 * Client-side validation and AJAX submission for Apollo forms
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/public/forms.js
 */

(function($) {
	'use strict';

	const ApolloForms = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Form submission
			$(document).on('submit', '.apollo-form', (e) => {
				e.preventDefault();
				this.handleSubmit(e.currentTarget);
			});

			// Real-time validation
			$(document).on('blur', '.apollo-form .apollo-input, .apollo-form .apollo-textarea', (e) => {
				this.validateField(e.currentTarget);
			});

			// Instagram field formatting
			$(document).on('input', '.apollo-instagram-input', (e) => {
				this.formatInstagram(e.currentTarget);
			});
		},

		handleSubmit: function(form) {
			const $form = $(form);
			const formType = $form.data('form-type');

			// Clear previous errors
			$form.find('.apollo-field-error').hide().text('');
			$form.find('.apollo-form-field').removeClass('apollo-field-has-error');

			// Validate all fields
			let hasErrors = false;
			$form.find('.apollo-input, .apollo-textarea, .apollo-select').each((index, input) => {
				if (!this.validateField(input)) {
					hasErrors = true;
				}
			});

			if (hasErrors) {
				return;
			}

			// Collect form data
			const formData = {};
			$form.find(':input[name]').each((index, input) => {
				const $input = $(input);
				const name = $input.attr('name');

				if (name === 'form_type' || name === 'apollo_form_nonce' || name === '_wp_http_referer') {
					return; // Skip
				}

				if ($input.is(':checkbox')) {
					formData[name] = $input.is(':checked') ? 1 : 0;
				} else {
					formData[name] = $input.val();
				}
			});

			// Show loading
			const $submitBtn = $form.find('[type="submit"]');
			const originalText = $submitBtn.text();
			$submitBtn.prop('disabled', true).text('Submitting...');

			// AJAX submit
			$.ajax({
				url: apolloForms.restUrl + 'forms/submit',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloForms.nonce);
				},
				data: JSON.stringify({
					form_type: formType,
					data: formData
				}),
				contentType: 'application/json',
				success: (response) => {
					if (response.success) {
						this.handleSuccess($form, response);
					} else {
						this.handleErrors($form, response.errors || {});
					}
				},
				error: (xhr) => {
					const response = xhr.responseJSON;
					if (response && response.errors) {
						this.handleErrors($form, response.errors);
					} else {
						alert('An error occurred. Please try again.');
					}
				},
				complete: () => {
					$submitBtn.prop('disabled', false).text(originalText);
				}
			});
		},

		validateField: function(input) {
			const $input = $(input);
			const $field = $input.closest('.apollo-form-field');
			const $error = $field.find('.apollo-field-error');
			const value = $input.val().trim();

			// Check required
			if ($input.prop('required') && !value) {
				this.showFieldError($field, $error, 'This field is required.');
				return false;
			}

			// Type-specific validation
			const type = $input.attr('type') || 'text';

			if (type === 'email' && value) {
				if (!this.isValidEmail(value)) {
					this.showFieldError($field, $error, 'Please enter a valid email address.');
					return false;
				}
			}

			if ($input.hasClass('apollo-instagram-input') && value) {
				if (!this.isValidInstagram(value)) {
					this.showFieldError($field, $error, 'Only letters, numbers, and underscores allowed (max 30 characters).');
					return false;
				}
			}

			// Pattern validation
			const pattern = $input.attr('pattern');
			if (pattern && value) {
				const regex = new RegExp(pattern);
				if (!regex.test(value)) {
					this.showFieldError($field, $error, 'Invalid format.');
					return false;
				}
			}

			// Clear error
			$field.removeClass('apollo-field-has-error');
			$error.hide().text('');
			return true;
		},

		showFieldError: function($field, $error, message) {
			$field.addClass('apollo-field-has-error');
			$error.text(message).show();
		},

		handleErrors: function($form, errors) {
			Object.keys(errors).forEach((fieldKey) => {
				const $field = $form.find('[name="' + fieldKey + '"]').closest('.apollo-form-field');
				const $error = $field.find('.apollo-field-error');
				this.showFieldError($field, $error, errors[fieldKey]);
			});

			// Scroll to first error
			const $firstError = $form.find('.apollo-field-has-error').first();
			if ($firstError.length) {
				$('html, body').animate({
					scrollTop: $firstError.offset().top - 100
				}, 300);
			}
		},

		handleSuccess: function($form, response) {
			// Show success message
			const $success = $('<div class="apollo-form-success" style="padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">')
				.text(response.message);

			$form.before($success);

			// Reset form
			$form[0].reset();

			// Scroll to success message
			$('html, body').animate({
				scrollTop: $success.offset().top - 100
			}, 300);

			// Optional: Redirect or trigger custom event
			if (response.data && response.data.redirect_url) {
				setTimeout(() => {
					window.location.href = response.data.redirect_url;
				}, 2000);
			}

			// Trigger custom event
			$(document).trigger('apolloFormSuccess', [response]);
		},

		formatInstagram: function(input) {
			const $input = $(input);
			let value = $input.val();

			// Remove @ prefix if user types it
			value = value.replace(/^@+/, '');

			// Remove invalid characters
			value = value.replace(/[^A-Za-z0-9_]/g, '');

			// Limit length
			if (value.length > 30) {
				value = value.substring(0, 30);
			}

			$input.val(value);
		},

		isValidEmail: function(email) {
			const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return regex.test(email);
		},

		isValidInstagram: function(username) {
			const regex = /^[A-Za-z0-9_]{1,30}$/;
			return regex.test(username);
		}
	};

	// Initialize on document ready
	$(document).ready(() => {
		// Check if apolloForms object exists (localized from PHP)
		if (typeof apolloForms !== 'undefined') {
			ApolloForms.init();
		}
	});

	// Expose to global scope for external access
	window.ApolloForms = ApolloForms;

})(jQuery);

