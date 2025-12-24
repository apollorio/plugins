/**
 * Apollo Admin Cabin JavaScript
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

(function ($) {
	'use strict';

	const ApolloCabin = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function () {
			// Toggle details in logs
			$( document ).on( 'click', '.toggle-details', this.toggleLogDetails );

			// Unblock IP button
			$( document ).on( 'click', '.unblock-ip-btn', this.handleUnblockIP );

			// Module toggle visual update
			$( document ).on( 'change', '.apollo-toggle input', this.updateToggleLabel );

			// Confirm dangerous actions
			$( document ).on( 'submit', '.block-ip-form', this.confirmBlockIP );

			// Notification recipient change
			$( document ).on( 'change', '#notification_recipient', this.handleNotificationRecipientChange );

			// Send notification
			$( document ).on( 'click', '.apollo-send-notification', this.handleSendNotification );

			// Test email (individual)
			$( document ).on( 'click', '.apollo-test-email', this.handleTestEmail );

			// Send test email (general)
			$( document ).on( 'click', '.apollo-send-test-email', this.handleSendTestEmail );
		},

		/**
		 * Toggle log details visibility
		 */
		toggleLogDetails: function (e) {
			e.preventDefault();
			const $btn     = $( this );
			const $details = $btn.siblings( '.log-details' );

			$details.toggleClass( 'hidden' );
			$btn.text( $details.hasClass( 'hidden' ) ? apolloCabin.i18n.ver || 'Ver' : 'Ocultar' );
		},

		/**
		 * Handle unblock IP
		 */
		handleUnblockIP: function (e) {
			e.preventDefault();

			if ( ! confirm( apolloCabin.i18n.confirmBlockIP || 'Tem certeza?' )) {
				return;
			}

			const $btn = $( this );
			const hash = $btn.data( 'hash' );
			const $row = $btn.closest( 'tr' );

			$btn.prop( 'disabled', true ).text( '...' );

			$.ajax(
				{
					url: apolloCabin.restUrl + '/mod/unblock-ip',
					method: 'POST',
					beforeSend: function (xhr) {
						xhr.setRequestHeader( 'X-WP-Nonce', apolloCabin.nonce );
					},
					data: {
						ip_hash: hash
					},
					success: function (response) {
						if (response.success) {
							$row.fadeOut(
								300,
								function () {
									$( this ).remove();
								}
							);
						} else {
							alert( response.message || apolloCabin.i18n.error );
							$btn.prop( 'disabled', false ).text( 'Desbloquear' );
						}
					},
					error: function () {
						alert( apolloCabin.i18n.error );
						$btn.prop( 'disabled', false ).text( 'Desbloquear' );
					}
				}
			);
		},

		/**
		 * Update toggle label when changed
		 */
		updateToggleLabel: function () {
			const $input = $( this );
			const $card  = $input.closest( '.apollo-cabin-card' );
			const $label = $input.closest( '.apollo-toggle' ).find( '.toggle-label' );

			if ($input.is( ':checked' )) {
				$card.removeClass( 'is-disabled' ).addClass( 'is-enabled' );
				$label.text( 'Ativo' );
			} else {
				$card.removeClass( 'is-enabled' ).addClass( 'is-disabled' );
				$label.text( 'Inativo' );
			}
		},

		/**
		 * Confirm block IP action
		 */
		confirmBlockIP: function (e) {
			const ip = $( this ).find( 'input[name="block_ip"]' ).val();

			if ( ! ip) {
				alert( 'Por favor, insira um endereço IP.' );
				e.preventDefault();
				return false;
			}

			if ( ! confirm( apolloCabin.i18n.confirmBlockIP || 'Tem certeza que deseja bloquear este IP?' )) {
				e.preventDefault();
				return false;
			}
		},

		/**
		 * Handle notification recipient change
		 */
		handleNotificationRecipientChange: function () {
			const recipient = $( this ).val();
			const $userRow  = $( '#notification_user_row' );

			if (recipient === 'specific') {
				$userRow.show();
			} else {
				$userRow.hide();
			}
		},

		/**
		 * Handle send notification
		 */
		handleSendNotification: function (e) {
			e.preventDefault();

			const $btn      = $( this );
			const recipient = $( '#notification_recipient' ).val();
			const userId    = $( '#notification_user_id' ).val();
			const title     = $( '#notification_title' ).val().trim();
			const message   = $( '#notification_message' ).val().trim();

			if ( ! title || ! message) {
				alert( 'Por favor, preencha título e mensagem.' );
				return;
			}

			$btn.prop( 'disabled', true ).text( 'Enviando...' );

			$.ajax(
				{
					url: apolloCabin.ajaxUrl,
					method: 'POST',
					data: {
						action: 'apollo_send_notification',
						nonce: apolloCabin.nonce,
						recipient: recipient,
						user_id: userId,
						title: title,
						message: message
					},
					success: function (response) {
						if (response.success) {
							alert( response.data.message || 'Notificação enviada com sucesso!' );
							// Clear form
							$( '#notification_title' ).val( '' );
							$( '#notification_message' ).val( '' );
						} else {
							alert( response.data.message || 'Erro ao enviar notificação.' );
						}
					},
					error: function () {
						alert( 'Erro ao enviar notificação.' );
					},
					complete: function () {
						$btn.prop( 'disabled', false ).text( 'Enviar Notificação' );
					}
				}
			);
		},

		/**
		 * Handle test email (individual button)
		 */
		handleTestEmail: function (e) {
			e.preventDefault();

			const $btn      = $( this );
			const emailType = $btn.data( 'email-type' );
			const sender    = $( '#email_sender_' + emailType ).val();

			if ( ! sender) {
				alert( 'Por favor, configure o email do remetente primeiro.' );
				return;
			}

			$btn.prop( 'disabled', true ).text( 'Enviando...' );

			$.ajax(
				{
					url: apolloCabin.ajaxUrl,
					method: 'POST',
					data: {
						action: 'apollo_test_email',
						nonce: apolloCabin.nonce,
						to: sender, // Send to the sender email itself
						type: emailType
					},
					success: function (response) {
						if (response.success) {
							alert( response.data.message || 'Email de teste enviado!' );
						} else {
							alert( response.data.message || 'Erro ao enviar email de teste.' );
						}
					},
					error: function () {
						alert( 'Erro ao enviar email de teste.' );
					},
					complete: function () {
						$btn.prop( 'disabled', false ).text( 'Test Email' );
					}
				}
			);
		},

		/**
		 * Handle send test email (general form)
		 */
		handleSendTestEmail: function (e) {
			e.preventDefault();

			const $btn = $( this );
			const to   = $( '#test_email_to' ).val().trim();
			const type = $( '#test_email_type' ).val();

			if ( ! to) {
				alert( 'Por favor, insira um endereço de email.' );
				return;
			}

			if ( ! type) {
				alert( 'Por favor, selecione um tipo de email.' );
				return;
			}

			$btn.prop( 'disabled', true ).text( 'Enviando...' );

			$.ajax(
				{
					url: apolloCabin.ajaxUrl,
					method: 'POST',
					data: {
						action: 'apollo_test_email',
						nonce: apolloCabin.nonce,
						to: to,
						type: type
					},
					success: function (response) {
						if (response.success) {
							alert( response.data.message || 'Email de teste enviado!' );
							$( '#test_email_to' ).val( '' );
						} else {
							alert( response.data.message || 'Erro ao enviar email de teste.' );
						}
					},
					error: function () {
						alert( 'Erro ao enviar email de teste.' );
					},
					complete: function () {
						$btn.prop( 'disabled', false ).text( 'Send Test Email' );
					}
				}
			);
		}
	};

	// Initialize on document ready
	$( document ).ready(
		function () {
			ApolloCabin.init();
		}
	);

})( jQuery );
