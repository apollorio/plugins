/**
 * Apollo Core - Moderation Admin JavaScript
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

(function($) {
	'use strict';

	const ApolloModeration = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('click', '.apollo-approve-btn', this.approvePost);
			$(document).on('click', '.apollo-suspend-btn', this.suspendUser);
			$(document).on('click', '.apollo-block-btn', this.blockUser);
		},

		approvePost: function(e) {
			e.preventDefault();

			const $button = $(this);
			const postId = $button.data('post-id');
			const note = prompt('Add a note (optional):');

			if (note === null) {
				return; // User cancelled
			}

			$button.prop('disabled', true).text('Approving...');

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/approve',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				data: JSON.stringify({
					post_id: postId,
					note: note
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						alert('Content approved successfully!');
						$button.closest('tr').fadeOut();
					} else {
						alert('Failed to approve content.');
						$button.prop('disabled', false).text('Approve');
					}
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message 
						? xhr.responseJSON.message 
						: 'Failed to approve content.';
					alert(message);
					$button.prop('disabled', false).text('Approve');
				}
			});
		},

		suspendUser: function(e) {
			e.preventDefault();

			const $button = $(this);
			const userId = $button.data('user-id');
			const days = prompt('Suspend for how many days?');

			if (!days || isNaN(days) || days <= 0) {
				alert('Please enter a valid number of days.');
				return;
			}

			const reason = prompt('Reason for suspension:');

			$button.prop('disabled', true);

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'users/suspend',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				data: JSON.stringify({
					user_id: userId,
					days: parseInt(days),
					reason: reason
				}),
				contentType: 'application/json',
				success: function(response) {
					alert(response.message);
					location.reload();
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message 
						? xhr.responseJSON.message 
						: 'Failed to suspend user.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		},

		blockUser: function(e) {
			e.preventDefault();

			if (!confirm('Are you sure you want to block this user? This action is permanent.')) {
				return;
			}

			const $button = $(this);
			const userId = $button.data('user-id');
			const reason = prompt('Reason for blocking:');

			$button.prop('disabled', true);

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'users/block',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				data: JSON.stringify({
					user_id: userId,
					reason: reason
				}),
				contentType: 'application/json',
				success: function(response) {
					alert(response.message);
					location.reload();
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message 
						? xhr.responseJSON.message 
						: 'Failed to block user.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.apollo-moderation-wrap').length) {
			ApolloModeration.init();
		}
	});

})(jQuery);

