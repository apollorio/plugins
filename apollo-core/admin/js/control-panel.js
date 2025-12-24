/**
 * Apollo Unified Control Panel JavaScript
 */
(function ($) {
	'use strict';

	$(document).ready(
		function () {
			// Tab switching
			$('.nav-tab').on(
				'click',
				function (e) {
					e.preventDefault();
					var tab = $(this).attr('href').split('tab=')[1];

					// Update active tab
					$('.nav-tab').removeClass('nav-tab-active');
					$(this).addClass('nav-tab-active');

					// Show/hide content (if needed for client-side switching)
					$('.apollo-tab-content').hide();
					$('.apollo-tab-content[data-tab="' + tab + '"]').show();
				}
			);

			// Toggle all checkboxes in section
			$('.apollo-toggle-all').on(
				'change',
				function () {
					var section = $(this).data('section');
					var checked = $(this).is(':checked');
					$('input[type="checkbox"][name*="[' + section + ']"]').prop('checked', checked);
				}
			);

			// Suspend user
			$('.apollo-suspend-user').on(
				'click',
				function () {
					var userId = $(this).data('user-id');
					var userName = $(this).data('user-name');
					var isSuspended = $(this).text().trim() === apolloControl.i18n.unsuspend || $(this).text().trim() === 'Unsuspend';
					var action = isSuspended ? 'unsuspend' : 'suspend';
					var confirmMsg = isSuspended ? apolloControl.i18n.confirm_unsuspend : apolloControl.i18n.confirm_suspend;

					if (!confirm(confirmMsg)) {
						return;
					}

					$.ajax(
						{
							url: apolloControl.ajax_url,
							type: 'POST',
							data: {
								action: 'apollo_suspend_user',
								nonce: apolloControl.nonce,
								user_id: userId,
								days: 7,
								unsuspend: isSuspended
							},
							success: function (response) {
								if (response.success) {
									location.reload();
								} else {
									alert(apolloControl.i18n.error + ': ' + (response.data ? .message || 'Unknown error'));
								}
							}
						}
					);
				}
			);

			// Send notification
			$('.apollo-send-notification').on(
				'click',
				function () {
					var recipient = $('#notification_recipient').val();
					var userId = $('#notification_user_id').val();
					var title = $('#notification_title').val();
					var message = $('#notification_message').val();

					if (!title || !message) {
						alert('Title and message are required');
						return;
					}

					$.ajax(
						{
							url: apolloControl.ajax_url,
							type: 'POST',
							data: {
								action: 'apollo_send_notification',
								nonce: apolloControl.nonce,
								recipient: recipient,
								user_id: userId,
								title: title,
								message: message
							},
							success: function (response) {
								if (response.success) {
									alert(apolloControl.i18n.success + ': ' + response.data.message);
									$('#notification_title, #notification_message').val('');
								} else {
									alert(apolloControl.i18n.error + ': ' + (response.data ? .message || 'Unknown error'));
								}
							}
						}
					);
				}
			);

			// Test email
			$('.apollo-test-email, .apollo-send-test-email').on(
				'click',
				function () {
					var emailType = $(this).data('email-type') || $('#test_email_type').val();
					var to = $('#test_email_to').val() || $('input[data-email-type="' + emailType + '"]').closest('tr').find('input[type="email"]').val();

					if (!to || !isValidEmail(to)) {
						alert('Please enter a valid email address');
						return;
					}

					$.ajax(
						{
							url: apolloControl.ajax_url,
							type: 'POST',
							data: {
								action: 'apollo_test_email',
								nonce: apolloControl.nonce,
								to: to,
								type: emailType
							},
							success: function (response) {
								if (response.success) {
									alert(apolloControl.i18n.success + ': ' + response.data.message);
								} else {
									alert(apolloControl.i18n.error + ': ' + (response.data ? .message || 'Unknown error'));
								}
							}
						}
					);
				}
			);

			// Toggle feature
			$('.apollo-feature-toggle').on(
				'change',
				function () {
					var feature = $(this).data('feature');
					var enabled = $(this).is(':checked');

					$.ajax(
						{
							url: apolloControl.ajax_url,
							type: 'POST',
							data: {
								action: 'apollo_toggle_feature',
								nonce: apolloControl.nonce,
								feature: feature,
								enabled: enabled
							},
							success: function (response) {
								if (!response.success) {
									alert(apolloControl.i18n.error + ': ' + (response.data ? .message || 'Unknown error'));
								}
							}
						}
					);
				}
			);

			// Show/hide user selector based on recipient type
			$('#notification_recipient').on(
				'change',
				function () {
					if ($(this).val() === 'specific') {
						$('#notification_user_row').show();
					} else {
						$('#notification_user_row').hide();
					}
				}
			);

			// Change user role/membership inline
			$('.apollo-change-role').on(
				'change',
				function () {
					var $select = $(this);
					var $wrapper = $select.closest('.apollo-role-inline-editor');
					var userId = $select.data('user-id');
					var currentRole = $select.data('current-role');
					var newRole = $select.val();

					if (newRole === currentRole) {
						return;
					}

					// Show saving indicator
					$wrapper.find('.apollo-role-saving').show();
					$wrapper.find('.apollo-role-saved').hide();
					$select.prop('disabled', true);

					$.ajax(
						{
							url: apolloControl.ajax_url,
							type: 'POST',
							data: {
								action: 'apollo_change_user_role',
								nonce: apolloControl.nonce,
								user_id: userId,
								new_role: newRole
							},
							success: function (response) {
								$wrapper.find('.apollo-role-saving').hide();
								$select.prop('disabled', false);

								if (response.success) {
									$wrapper.find('.apollo-role-saved').fadeIn().delay(2000).fadeOut();
									$select.data('current-role', newRole);
								} else {
									alert(apolloControl.i18n.error + ': ' + (response.data?.message || 'Unknown error'));
									$select.val(currentRole);
								}
							},
							error: function () {
								$wrapper.find('.apollo-role-saving').hide();
								$select.prop('disabled', false);
								alert(apolloControl.i18n.error + ': Network error');
								$select.val(currentRole);
							}
						}
					);
				}
			);

			function isValidEmail(email) {
				var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return re.test(email);
			}
		}
	);
})(jQuery);
