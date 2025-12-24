/**
 * Apollo Chat Module - Client-side JavaScript
 *
 * Provides real-time-like messaging using polling.
 * Integrates with ChatModule REST/AJAX endpoints.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

(function () {
	'use strict';

	const ApolloChat = {
		// Configuration
		pollInterval: 3000, // Poll every 3 seconds
		pollTimer: null,
		currentConversationId: null,
		lastMessageTimestamp: null,
		isPolling: false,

		// DOM Elements
		elements: {
			conversationsList: null,
			messagesContainer: null,
			messageInput: null,
			sendButton: null,
			newChatButton: null,
		},

		// State
		conversations: [],
		messages: [],
		currentUser: null,

		/**
		 * Initialize chat module
		 */
		init() {
			this.cacheElements();
			this.bindEvents();
			this.loadConversations();
			this.startPolling();

			// Get current user from page data
			this.currentUser = window.apolloChatUser || { id: 0, name: 'Usuário' };

			console.log('🗨️ Apollo Chat initialized');
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			this.elements.conversationsList = document.querySelector('[data-chat-conversations]');
			this.elements.messagesContainer = document.querySelector('[data-chat-messages]');
			this.elements.messageInput = document.querySelector('[data-chat-input]');
			this.elements.sendButton = document.querySelector('[data-chat-send]');
			this.elements.newChatButton = document.querySelector('[data-chat-new]');
			this.elements.threadTriggers = document.querySelectorAll('[data-thread-trigger]');
		},

		/**
		 * Bind event handlers
		 */
		bindEvents() {
			// Send message on button click
			if (this.elements.sendButton) {
				this.elements.sendButton.addEventListener('click', () => this.sendMessage());
			}

			// Send message on Enter key
			if (this.elements.messageInput) {
				this.elements.messageInput.addEventListener('keypress', (e) => {
					if (e.key === 'Enter' && !e.shiftKey) {
						e.preventDefault();
						this.sendMessage();
					}
				});
			}

			// Thread triggers (conversation list items)
			this.elements.threadTriggers.forEach((trigger) => {
				trigger.addEventListener('click', (e) => {
					const threadId = trigger.dataset.threadId;
					if (threadId) {
						this.selectConversation(threadId);
					}
				});
			});

			// New chat button
			if (this.elements.newChatButton) {
				this.elements.newChatButton.addEventListener('click', () => this.showNewChatModal());
			}

			// Listen for visibility changes to pause/resume polling
			document.addEventListener('visibilitychange', () => {
				if (document.hidden) {
					this.stopPolling();
				} else {
					this.startPolling();
				}
			});
		},

		/**
		 * Load conversations list
		 */
		async loadConversations() {
			try {
				const response = await this.apiRequest('GET', '/chat/conversations');
				
				if (response.success || Array.isArray(response)) {
					this.conversations = response.data || response;
					this.renderConversations();
				}
			} catch (error) {
				console.error('Failed to load conversations:', error);
			}
		},

		/**
		 * Render conversations list
		 */
		renderConversations() {
			if (!this.elements.conversationsList) return;

			const html = this.conversations.map((conv) => {
				const isActive = conv.id === this.currentConversationId;
				const unreadBadge = conv.unread_count > 0
					? `<span class="inline-flex items-center justify-center h-5 min-w-[20px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold">${conv.unread_count}</span>`
					: '';

				const lastMessage = conv.last_message
					? this.truncate(conv.last_message, 40)
					: 'Sem mensagens';

				const participants = conv.participants || [];
				const otherParticipant = participants.find(p => p.user_id != this.currentUser?.id);
				const displayName = conv.title || otherParticipant?.name || 'Conversa';
				const avatar = otherParticipant?.avatar || this.getDefaultAvatar(conv.type);

				return `
					<button
						type="button"
						class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border transition-colors ${isActive ? 'border-slate-300 bg-slate-100' : 'border-transparent hover:bg-slate-50'}"
						data-conversation-id="${conv.id}"
						onclick="ApolloChat.selectConversation(${conv.id})"
					>
						<div class="shrink-0">
							<div class="h-8 w-8 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center">
								<img src="${avatar}" alt="${displayName}" class="h-full w-full object-cover" onerror="this.style.display='none'">
							</div>
						</div>
						<div class="min-w-0 flex-1">
							<div class="flex items-center justify-between gap-2">
								<p class="truncate font-semibold text-[12px]">${displayName}</p>
								${unreadBadge}
							</div>
							<p class="truncate text-[11px] text-slate-500">${lastMessage}</p>
						</div>
					</button>
				`;
			}).join('');

			this.elements.conversationsList.innerHTML = html || '<p class="text-center text-slate-400 py-4 text-sm">Nenhuma conversa</p>';
		},

		/**
		 * Select a conversation
		 */
		async selectConversation(conversationId) {
			this.currentConversationId = parseInt(conversationId, 10);
			this.lastMessageTimestamp = null;

			// Update UI to show active state
			this.renderConversations();

			// Load messages
			await this.loadMessages();

			// Mark as read
			await this.markAsRead();

			// Focus input
			if (this.elements.messageInput) {
				this.elements.messageInput.focus();
			}
		},

		/**
		 * Load messages for current conversation
		 */
		async loadMessages(beforeId = null) {
			if (!this.currentConversationId) return;

			try {
				let url = `/chat/messages/${this.currentConversationId}`;
				if (beforeId) {
					url += `?before_id=${beforeId}`;
				}

				const response = await this.apiRequest('GET', url);
				
				if (response.success || Array.isArray(response)) {
					this.messages = response.data || response;
					this.renderMessages();

					// Update last message timestamp for polling
					if (this.messages.length > 0) {
						const lastMsg = this.messages[this.messages.length - 1];
						this.lastMessageTimestamp = lastMsg.created_at;
					}
				}
			} catch (error) {
				console.error('Failed to load messages:', error);
			}
		},

		/**
		 * Render messages
		 */
		renderMessages() {
			if (!this.elements.messagesContainer) return;

			const html = this.messages.map((msg) => {
				const isOwn = msg.sender_id == this.currentUser?.id;
				const sender = msg.sender || {};
				const time = this.formatTime(msg.created_at);

				if (msg.message_type === 'system') {
					return `
						<div class="flex justify-center my-2">
							<span class="text-[11px] text-slate-400 bg-slate-100 px-3 py-1 rounded-full">
								${this.escapeHtml(msg.content)}
							</span>
						</div>
					`;
				}

				return `
					<div class="flex ${isOwn ? 'justify-end' : 'justify-start'} mb-3">
						<div class="flex ${isOwn ? 'flex-row-reverse' : 'flex-row'} items-end gap-2 max-w-[80%]">
							${!isOwn ? `
								<div class="shrink-0">
									<div class="h-7 w-7 rounded-full bg-slate-200 overflow-hidden">
										<img src="${sender.avatar}" alt="${sender.name}" class="h-full w-full object-cover">
									</div>
								</div>
							` : ''}
							<div class="${isOwn ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-900'} rounded-2xl px-3 py-2">
								${!isOwn ? `<p class="text-[10px] font-semibold mb-1 ${isOwn ? 'text-slate-300' : 'text-slate-500'}">${sender.name}</p>` : ''}
								<p class="text-[13px] whitespace-pre-wrap">${this.escapeHtml(msg.content)}</p>
								<p class="text-[10px] ${isOwn ? 'text-slate-400' : 'text-slate-400'} mt-1">${time}</p>
							</div>
						</div>
					</div>
				`;
			}).join('');

			this.elements.messagesContainer.innerHTML = html || '<p class="text-center text-slate-400 py-8 text-sm">Nenhuma mensagem. Comece a conversa!</p>';

			// Scroll to bottom
			this.scrollToBottom();
		},

		/**
		 * Send a message
		 */
		async sendMessage() {
			if (!this.currentConversationId || !this.elements.messageInput) return;

			const content = this.elements.messageInput.value.trim();
			if (!content) return;

			// Clear input immediately for better UX
			this.elements.messageInput.value = '';

			// Optimistic UI update
			const tempMessage = {
				id: 'temp-' + Date.now(),
				conversation_id: this.currentConversationId,
				sender_id: this.currentUser?.id,
				content: content,
				message_type: 'text',
				created_at: new Date().toISOString(),
				sender: {
					id: this.currentUser?.id,
					name: this.currentUser?.name || 'Você',
					avatar: this.currentUser?.avatar || '',
				},
			};

			this.messages.push(tempMessage);
			this.renderMessages();

			try {
				const response = await this.apiRequest('POST', '/chat/send', {
					conversation_id: this.currentConversationId,
					content: content,
					type: 'text',
				});

				if (response.success || response.id) {
					// Replace temp message with real one
					const realMessage = response.data || response;
					const tempIndex = this.messages.findIndex(m => m.id === tempMessage.id);
					if (tempIndex !== -1) {
						this.messages[tempIndex] = realMessage;
					}
					this.lastMessageTimestamp = realMessage.created_at;
				}
			} catch (error) {
				console.error('Failed to send message:', error);
				// Remove temp message on error
				this.messages = this.messages.filter(m => m.id !== tempMessage.id);
				this.renderMessages();
				this.showError('Erro ao enviar mensagem');
			}
		},

		/**
		 * Start polling for new messages
		 */
		startPolling() {
			if (this.isPolling) return;

			this.isPolling = true;
			this.poll();
		},

		/**
		 * Stop polling
		 */
		stopPolling() {
			this.isPolling = false;
			if (this.pollTimer) {
				clearTimeout(this.pollTimer);
				this.pollTimer = null;
			}
		},

		/**
		 * Poll for new messages
		 */
		async poll() {
			if (!this.isPolling) return;

			try {
				let url = '/chat/poll';
				if (this.lastMessageTimestamp) {
					url += `?since=${encodeURIComponent(this.lastMessageTimestamp)}`;
				}

				const response = await this.apiRequest('GET', url);

				if (response.success || Array.isArray(response)) {
					const newMessages = response.data || response;

					if (newMessages.length > 0) {
						// Add new messages to current conversation if applicable
						newMessages.forEach((msg) => {
							if (msg.conversation_id == this.currentConversationId) {
								const exists = this.messages.some(m => m.id === msg.id);
								if (!exists) {
									this.messages.push(msg);
								}
							}
						});

						this.renderMessages();

						// Update timestamp
						const lastNew = newMessages[newMessages.length - 1];
						this.lastMessageTimestamp = lastNew.created_at;

						// Refresh conversations list for unread counts
						this.loadConversations();

						// Show notification for messages not in current conversation
						const otherConvMessages = newMessages.filter(m => m.conversation_id != this.currentConversationId);
						if (otherConvMessages.length > 0) {
							this.showNotification(otherConvMessages[0]);
						}
					}
				}
			} catch (error) {
				console.error('Poll error:', error);
			}

			// Schedule next poll
			this.pollTimer = setTimeout(() => this.poll(), this.pollInterval);
		},

		/**
		 * Mark current conversation as read
		 */
		async markAsRead() {
			if (!this.currentConversationId) return;

			try {
				await this.apiRequest('POST', '/chat/mark-read', {
					conversation_id: this.currentConversationId,
				});
			} catch (error) {
				console.error('Failed to mark as read:', error);
			}
		},

		/**
		 * Start new conversation
		 */
		async startConversation(recipientId, type = 'direct', contextType = '', contextId = 0) {
			try {
				const response = await this.apiRequest('POST', '/chat/start', {
					recipient_id: recipientId,
					type: type,
					context_type: contextType,
					context_id: contextId,
				});

				if (response.success || response.id) {
					const conversation = response.data || response;
					await this.loadConversations();
					await this.selectConversation(conversation.id);
					return conversation;
				}
			} catch (error) {
				console.error('Failed to start conversation:', error);
				this.showError('Erro ao iniciar conversa');
			}

			return null;
		},

		/**
		 * Show new chat modal
		 */
		showNewChatModal() {
			// TODO: Implement modal for selecting users
			console.log('Show new chat modal');
		},

		/**
		 * Show notification
		 */
		showNotification(message) {
			// Check if browser notifications are supported and permitted
			if ('Notification' in window && Notification.permission === 'granted') {
				const sender = message.sender || {};
				new Notification(`Nova mensagem de ${sender.name}`, {
					body: this.truncate(message.content, 50),
					icon: sender.avatar,
					tag: 'apollo-chat-' + message.conversation_id,
				});
			}
		},

		/**
		 * Make API request
		 */
		async apiRequest(method, endpoint, data = null) {
			const baseUrl = window.apolloChatConfig?.restUrl || '/wp-json/apollo/v1';
			const nonce = window.apolloChatConfig?.nonce || '';

			const options = {
				method: method,
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
			};

			if (data && (method === 'POST' || method === 'PUT')) {
				options.body = JSON.stringify(data);
			}

			const response = await fetch(baseUrl + endpoint, options);
			return response.json();
		},

		// Helper methods

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		truncate(str, length) {
			if (!str) return '';
			return str.length > length ? str.substring(0, length) + '...' : str;
		},

		formatTime(dateString) {
			const date = new Date(dateString);
			const now = new Date();
			const diffMs = now - date;
			const diffMins = Math.floor(diffMs / 60000);
			const diffHours = Math.floor(diffMs / 3600000);
			const diffDays = Math.floor(diffMs / 86400000);

			if (diffMins < 1) return 'agora';
			if (diffMins < 60) return `${diffMins}m`;
			if (diffHours < 24) return `${diffHours}h`;
			if (diffDays < 7) return `${diffDays}d`;

			return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
		},

		getDefaultAvatar(type) {
			const avatars = {
				direct: 'https://assets.apollo.rio.br/i/user.svg',
				group: 'https://assets.apollo.rio.br/i/group.svg',
				nucleo: 'https://assets.apollo.rio.br/i/nucleo.svg',
				comunidade: 'https://assets.apollo.rio.br/i/comunidade.svg',
				classified: 'https://assets.apollo.rio.br/i/classified.svg',
				supplier: 'https://assets.apollo.rio.br/i/supplier.svg',
			};
			return avatars[type] || avatars.direct;
		},

		scrollToBottom() {
			if (this.elements.messagesContainer) {
				this.elements.messagesContainer.scrollTop = this.elements.messagesContainer.scrollHeight;
			}
		},

		showError(message) {
			// TODO: Use Apollo toast notification system
			console.error(message);
			alert(message);
		},
	};

	// Auto-initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => ApolloChat.init());
	} else {
		ApolloChat.init();
	}

	// Expose globally for external access
	window.ApolloChat = ApolloChat;
})();
