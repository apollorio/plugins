/**
 * Apollo Classified Contact JavaScript
 *
 * Handles safety modal and chat initiation for classified ads.
 *
 * @package Apollo_Social
 * @version 2.2.0
 */

(function ($) {
	'use strict';

	// Config from wp_localize_script
	const config = window.apolloClassifiedContact || {};
	const restUrl = config.restUrl || '/wp-json/apollo-social/v1';
	const nonce = config.nonce || '';
	const loginUrl = config.loginUrl || '';
	const isLoggedIn = config.isLoggedIn || false;

	/**
	 * Safety Modal Controller
	 */
	const SafetyModal = {
		modal: null,
		confirmBtn: null,
		currentAdData: null,

		init() {
			this.modal = $('#apollo-safety-modal');
			this.confirmBtn = $('#apollo-safety-confirm');

			if (!this.modal.length) return;

			this.bindEvents();
		},

		bindEvents() {
			// Confirm button
			this.confirmBtn.on('click', () => {
				if (this.currentAdData) {
					this.startChat(this.currentAdData);
				}
				this.hide();
			});

			// ESC key
			$(document).on('keydown', (e) => {
				if (e.keyCode === 27 && this.modal.is(':visible')) {
					this.hide();
				}
			});
		},

		show(adData) {
			this.currentAdData = adData;
			this.modal.show();
			this.confirmBtn.focus();
		},

		hide() {
			this.modal.hide();
			this.currentAdData = null;
		},

		async startChat(adData) {
			const btn = $(`.ap-classified-contact-btn[data-ad-id="${adData.adId}"]`);
			btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Iniciando conversa...');

			try {
				const response = await fetch(`${restUrl}/chat/context-thread`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce
					},
					body: JSON.stringify({
						context: adData.context,
						entity_type: 'ad',
						entity_id: parseInt(adData.adId),
						seller_id: parseInt(adData.sellerId)
					})
				});

				const data = await response.json();

				if (data.success) {
					// Track analytics if available
					if (window.gtag) {
						window.gtag('event', 'ad_contact_chat', {
							context: 'classified',
							ad_id: adData.adId
						});
					}

					// Open chat UI
					if (window.ApolloChat && window.ApolloChat.openConversation) {
						window.ApolloChat.openConversation(data.conversation_id);
					} else if (data.open_url) {
						window.location.href = data.open_url;
					} else {
						alert('Conversa iniciada! Verifique suas mensagens.');
					}
				} else {
					throw new Error(data.error || 'Erro ao iniciar conversa');
				}
			} catch (error) {
				console.error('Chat error:', error);

				// Fallback to WhatsApp if available
				if (adData.whatsapp) {
					if (window.gtag) {
						window.gtag('event', 'ad_contact_whatsapp', {
							context: 'classified',
							ad_id: adData.adId
						});
					}
					window.open(adData.whatsapp, '_blank');
				} else {
					alert('Erro ao iniciar conversa. Tente novamente ou entre em contato diretamente.');
				}
			} finally {
				btn.prop('disabled', false).html('<i class="ri-chat-1-line"></i> Falar no Chat');
			}
		}
	};

	/**
	 * Contact Button Handler
	 */
	const ContactHandler = {
		init() {
			$(document).on('click', '.ap-classified-contact-btn', (e) => {
				e.preventDefault();

				if (!isLoggedIn) {
					window.location.href = loginUrl;
					return;
				}

				const btn = $(e.currentTarget);
				const adData = {
					adId: btn.data('ad-id'),
					sellerId: btn.data('seller-id'),
					context: btn.data('context'),
					whatsapp: btn.data('whatsapp')
				};

				SafetyModal.show(adData);
			});
		}
	};

	// Initialize on document ready
	$(document).ready(() => {
		SafetyModal.init();
		ContactHandler.init();
	});

})(jQuery);
