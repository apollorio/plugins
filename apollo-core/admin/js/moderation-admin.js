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
			this.updatePendingCount();
		},

		bindEvents: function() {
			$(document).on('click', '.apollo-approve-btn', this.approvePost);
			$(document).on('click', '.apollo-reject-btn', this.rejectPost);
			$(document).on('click', '.apollo-suspend-btn', this.suspendUser);
			$(document).on('click', '.apollo-block-btn', this.blockUser);
		},

		/**
		 * Update pending count badge in real-time
		 */
		updatePendingCount: function() {
			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/pending-count',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				success: function(response) {
					if (response.success) {
						const $badge = $('#adminmenu .awaiting-mod .pending-count');
						if ($badge.length) {
							$badge.text(response.total);
							if (response.total === 0) {
								$badge.closest('.awaiting-mod').hide();
							}
						}
					}
				}
			});
		},

		/**
		 * Approve post
		 */
		approvePost: function(e) {
			e.preventDefault();

			const $button = $(this);
			const $row = $button.closest('tr');
			const postId = $button.data('post-id');

			// Disable button and show loading state
			$button.prop('disabled', true);
			$button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update spin');

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/approve',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				data: JSON.stringify({
					post_id: postId,
					note: ''
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						// Show success state
						$row.css('background', '#f0fdf4');
						$button.html('<span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span> Aprovado!');
						
						// Fade out row
						setTimeout(function() {
							$row.fadeOut(400, function() {
								$(this).remove();
								ApolloModeration.checkEmptyQueue();
								ApolloModeration.updatePendingCount();
							});
						}, 800);
					} else {
						ApolloModeration.showError($button, 'Falha ao aprovar');
					}
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message 
						? xhr.responseJSON.message 
						: 'Falha ao aprovar conteúdo.';
					ApolloModeration.showError($button, message);
				}
			});
		},

		/**
		 * Reject post
		 */
		rejectPost: function(e) {
			e.preventDefault();

			if (!confirm('Tem certeza que deseja rejeitar este conteúdo?')) {
				return;
			}

			const $button = $(this);
			const $row = $button.closest('tr');
			const postId = $button.data('post-id');
			const reason = prompt('Motivo da rejeição (opcional):');

			if (reason === null) {
				return; // User cancelled
			}

			// Disable button and show loading state
			$button.prop('disabled', true);
			$button.find('.dashicons').removeClass('dashicons-no').addClass('dashicons-update spin');

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/reject',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apolloModerationAdmin.nonce);
				},
				data: JSON.stringify({
					post_id: postId,
					note: reason
				}),
				contentType: 'application/json',
				success: function(response) {
					if (response.success) {
						// Show rejected state
						$row.css('background', '#fef2f2');
						
						// Fade out row
						setTimeout(function() {
							$row.fadeOut(400, function() {
								$(this).remove();
								ApolloModeration.checkEmptyQueue();
								ApolloModeration.updatePendingCount();
							});
						}, 500);
					} else {
						ApolloModeration.showError($button, 'Falha ao rejeitar');
					}
				},
				error: function(xhr) {
					const message = xhr.responseJSON && xhr.responseJSON.message 
						? xhr.responseJSON.message 
						: 'Falha ao rejeitar conteúdo.';
					ApolloModeration.showError($button, message);
				}
			});
		},

		/**
		 * Check if queue is empty and show message
		 */
		checkEmptyQueue: function() {
			const $tbody = $('#apollo-moderation-queue tbody');
			if ($tbody.find('tr:visible').length === 0) {
				$tbody.html(`
					<tr>
						<td colspan="7" style="text-align: center; padding: 40px;">
							<span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #10b981; display: block; margin-bottom: 12px;"></span>
							<strong>Nenhum item pendente!</strong>
							<p style="color: #6b7280; margin: 8px 0 0 0;">
								Todos os conteúdos foram moderados.
							</p>
						</td>
					</tr>
				`);
			}
		},

		/**
		 * Show error on button
		 */
		showError: function($button, message) {
			alert(message);
			$button.prop('disabled', false);
			$button.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-yes');
		},

		/**
		 * Suspend user
		 */
		suspendUser: function(e) {
			e.preventDefault();

			const $button = $(this);
			const userId = $button.data('user-id');
			const days = prompt('Suspender por quantos dias?');

			if (!days || isNaN(days) || days <= 0) {
				alert('Por favor, insira um número válido de dias.');
				return;
			}

			const reason = prompt('Motivo da suspensão:');

			$button.prop('disabled', true);

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/suspend-user',
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
						: 'Falha ao suspender usuário.';
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

			if (!confirm('Tem certeza que deseja bloquear este usuário? Esta ação é permanente.')) {
				return;
			}

			const $button = $(this);
			const userId = $button.data('user-id');
			const reason = prompt('Motivo do bloqueio:');

			$button.prop('disabled', true);

			$.ajax({
				url: apolloModerationAdmin.restUrl + 'moderation/block-user',
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
						: 'Falha ao bloquear usuário.';
					alert(message);
					$button.prop('disabled', false);
				}
			});
		}
	};

	// CSS for spinning animation
	$('<style>')
		.text('.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
		.appendTo('head');

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.apollo-moderation-wrap').length) {
			ApolloModeration.init();
		}
	});

})(jQuery);
