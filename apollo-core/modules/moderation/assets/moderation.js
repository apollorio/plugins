/**
 * Apollo Moderation Admin Scripts
 *
 * @package Apollo_Core
 */

(function($) {
	'use strict';

	const apolloModAdmin = {
		/**
		 * Initialize
		 */
		init: function() {
			this.loadQueue();
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Queue actions.
			$(document).on('click', '.apollo-approve-post', this.approvePost);
			$(document).on('click', '.apollo-reject-post', this.rejectPost);

			// User actions.
			$(document).on('click', '.apollo-notify-user', this.notifyUser);
			$(document).on('click', '.apollo-suspend-user', this.suspendUser);
			$(document).on('click', '.apollo-block-user', this.blockUser);
			$(document).on('click', '.apollo-unblock-user', this.unblockUser);

			// Filters.
			$('.apollo-queue-filters input[type="checkbox"]').on('change', this.loadQueue);

			// Select all.
			$('#select-all').on('change', function() {
				$('#apollo-queue-tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
			});
		},

		/**
		 * Load mod queue
		 */
		loadQueue: function() {
			const $tbody = $('#apollo-queue-tbody');
			$tbody.html('<tr><td colspan="7" style="text-align: center;"><span class="spinner is-active" style="float: none;"></span> Loading...</td></tr>');

			$.ajax({
				url: apolloModeration.restUrl + '/mod/fila',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
				},
				success: function(response) {
					if (response.success && response.data.length > 0) {
						apolloModAdmin.renderQueue(response.data);
					} else {
						$tbody.html('<tr><td colspan="7" style="text-align: center;">No items in queue.</td></tr>');
					}
				},
				error: function() {
					$tbody.html('<tr><td colspan="7" style="text-align: center; color: red;">Failed to load queue.</td></tr>');
				}
			});
		},

		/**
		 * Render queue items
		 *
		 * @param {Array} items Queue items.
		 */
		renderQueue: function(items) {
			const $tbody = $('#apollo-queue-tbody');
			$tbody.empty();

			items.forEach(function(item) {
				const thumbnail = item.thumbnail ? '<img src="' + item.thumbnail + '" style="width: 50px; height: auto;">' : '—';
				const date = new Date(item.date).toLocaleString();

				const row = $('<tr>').attr('data-post-id', item.id).html(
					'<td><input type="checkbox" value="' + item.id + '"></td>' +
					'<td>' + thumbnail + '</td>' +
					'<td><a href="' + item.edit_link + '" target="_blank">' + item.title + '</a></td>' +
					'<td>' + item.type + '</td>' +
					'<td>' + item.author.name + '</td>' +
					'<td>' + date + '</td>' +
					'<td>' +
						'<button class="button apollo-approve-post" data-post-id="' + item.id + '">Approve</button> ' +
						'<button class="button apollo-reject-post" data-post-id="' + item.id + '">Reject</button>' +
					'</td>'
				);

				$tbody.append(row);
			});
		},

		/**
		 * Approve post
		 */
		approvePost: function(e) {
			e.preventDefault();

			const postId = $(this).data('post-id');
			const note = prompt('Add a note (optional):');

			if (note === null) {
				return; // User cancelled.
			}

			const $button = $(this);
			$button.prop('disabled', true).text('Approving...');

			$.ajax({
				url: apolloModeration.restUrl + '/modaprovar',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
				},
				data: JSON.stringify({
					post_id: postId,
					note: note
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						alert('Post approved successfully!');
						$('tr[data-post-id="' + postId + '"]').fadeOut();
					} else {
						alert('Failed to approve post.');
						$button.prop('disabled', false).text('Approve');
					}
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to approve post.';
					alert(message);
					$button.prop('disabled', false).text('Approve');
				}
			});
		},

		/**
		 * Reject post
		 */
		rejectPost: function(e) {
			e.preventDefault();

			const postId = $(this).data('post-id');
			const note = prompt('Reason for rejection:');

			if (!note) {
				alert('Please provide a reason for rejection.');
				return;
			}

			const $button = $(this);
			$button.prop('disabled', true).text('Rejecting...');

			$.ajax({
				url: apolloModeration.restUrl + '/mod/reject',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
				},
				data: JSON.stringify({
					post_id: postId,
					note: note
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						alert('Post rejected successfully!');
						$('tr[data-post-id="' + postId + '"]').fadeOut();
					} else {
						alert('Failed to reject post.');
						$button.prop('disabled', false).text('Reject');
					}
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to reject post.';
					alert(message);
					$button.prop('disabled', false).text('Reject');
				}
			});
		},

		/**
		 * Notify user
		 */
		notifyUser: function(e) {
			e.preventDefault();

			const userId = $(this).data('user-id');
			const message = prompt('Enter notification message:');

			if (!message) {
				return;
			}

			const $button = $(this);
			$button.prop('disabled', true);

			$.ajax({
				url: apolloModeration.restUrl + '/mod/notificar-user',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
				},
				data: JSON.stringify({
					user_id: userId,
					message: message
				}),
				contentType: 'application/json',
				success: function(response) {
					alert(response.message);
					$button.prop('disabled', false);
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to send notification.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Suspend user
		 */
		suspendUser: function(e) {
			e.preventDefault();

			const userId = $(this).data('user-id');
			const days = prompt('Suspend for how many days?');

			if (!days || isNaN(days) || days <= 0) {
				alert('Please enter a valid number of days.');
				return;
			}

			const reason = prompt('Reason for suspension:');

			const $button = $(this);
			$button.prop('disabled', true);

			$.ajax({
				url: apolloModeration.restUrl + '/mod/suspender/-user',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
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
					const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to suspend user.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Block user
		 */
		blockUser: function(e) {
			e.preventDefault();

			if (!confirm('Are you sure you want to block this user? This is a permanent action.')) {
				return;
			}

			const userId = $(this).data('user-id');
			const reason = prompt('Reason for blocking:');

			const $button = $(this);
			$button.prop('disabled', true);

			$.ajax({
				url: apolloModeration.restUrl + '/mod/bloquear/-user',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModeration.nonce);
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
					const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to block user.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Unblock user
		 */
		unblockUser: function(e) {
			e.preventDefault();

			if (!confirm('Are you sure you want to unblock this user?')) {
				return;
			}

			const userId = $(this).data('user-id');
			const $button = $(this);
			$button.prop('disabled', true);

			// Note: Need to add unblock endpoint to REST API.
			alert('Unblock functionality coming soon. Please use database directly for now.');
			$button.prop('disabled', false);
		}
	};

	// Initialize on document ready.
	$(document).ready(function() {
		if ($('.apollo-mod-wrap').length) {
			apolloModAdmin.init();
		}
	});

})(jQuery);

