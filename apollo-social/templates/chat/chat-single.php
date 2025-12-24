<?php
/**
 * Chat Single Conversation Template
 * Direct link to a specific user conversation: /chat/{userID}
 *
 * STRICT MODE: Full HTML structure for raw_html canvas routes.
 * Security: Validates user, enforces authentication, sanitizes all output.
 *
 * @package Apollo_Social
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// SECURITY: Strict User ID Validation
// ─────────────────────────────────────────────────────────────────────────────
$chat_user_id = absint( get_query_var( 'user_id', 0 ) );

if ( empty( $chat_user_id ) || 0 === $chat_user_id ) {
	wp_safe_redirect( home_url( '/chat/' ) );
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// SECURITY: Authentication Check
// ─────────────────────────────────────────────────────────────────────────────
if ( ! is_user_logged_in() ) {
	auth_redirect();
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// DATA: Fetch & Validate Target User
// ─────────────────────────────────────────────────────────────────────────────
$chat_user = get_user_by( 'ID', $chat_user_id );

if ( false === $chat_user ) {
	wp_safe_redirect( home_url( '/chat/' ) );
	exit;
}

$logged_user     = wp_get_current_user();
$current_user_id = (int) $logged_user->ID;

// Prevent chatting with self.
if ( $current_user_id === $chat_user_id ) {
	wp_safe_redirect( home_url( '/chat/' ) );
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// DATA: Fetch Conversation Messages
// ─────────────────────────────────────────────────────────────────────────────
$messages = array();

// Integrate with Apollo Chat Renderer if available.
if ( class_exists( 'Apollo_Chat_Renderer' ) ) {
	$renderer = new Apollo_Chat_Renderer();
	$messages = $renderer->get_conversation( $current_user_id, $chat_user_id );
}

// Fallback placeholder messages for development.
if ( empty( $messages ) ) {
	$messages = array(
		array(
			'id'        => 1,
			'sender_id' => $chat_user_id,
			'content'   => 'Olá! Como posso ajudar você?',
			'time'      => '10:30',
			'read'      => true,
		),
		array(
			'id'        => 2,
			'sender_id' => $current_user_id,
			'content'   => 'Estou procurando informações sobre eventos.',
			'time'      => '10:32',
			'read'      => true,
		),
		array(
			'id'        => 3,
			'sender_id' => $chat_user_id,
			'content'   => 'Claro! Temos vários eventos acontecendo esta semana.',
			'time'      => '10:33',
			'read'      => false,
		),
	);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<title><?php echo esc_html( 'Chat com ' . $chat_user->display_name . ' - Apollo::rio' ); ?></title>
	<?php wp_head(); ?>
	<style>
		/* ═══════════════════════════════════════════════════════════════════════
		CHAT SINGLE: Mobile-First ShadCN/Tailwind Layout
		═══════════════════════════════════════════════════════════════════════ */
		:root {
			--chat-bg: #fafafa;
			--chat-card: #ffffff;
			--chat-border: #e5e7eb;
			--chat-primary: #18181b;
			--chat-primary-fg: #fafafa;
			--chat-muted: #f4f4f5;
			--chat-muted-fg: #71717a;
			--chat-radius: 0.75rem;
			--apollo-viewport-height: 100vh;
		}

		.dark {
			--chat-bg: #09090b;
			--chat-card: #18181b;
			--chat-border: #27272a;
			--chat-primary: #fafafa;
			--chat-primary-fg: #18181b;
			--chat-muted: #27272a;
			--chat-muted-fg: #a1a1aa;
		}

		@supports (height: 100dvh) {
			:root {
				--apollo-viewport-height: 100dvh;
			}
		}

		*, *::before, *::after {
			box-sizing: border-box;
		}

		html {
			height: 100%;
			min-height: 100%;
		}

		body {
			margin: 0;
			padding: 0;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: var(--chat-bg);
			color: var(--chat-primary);
			-webkit-font-smoothing: antialiased;
			min-height: var(--apollo-viewport-height);
		}

		.chat-container {
			display: flex;
			flex-direction: column;
			height: var(--apollo-viewport-height);
			min-height: var(--apollo-viewport-height);
			max-width: 100%;
			overflow: hidden;
		}

		/* ─── Header ─── */
		.chat-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0.75rem 1rem;
			background: var(--chat-card);
			border-bottom: 1px solid var(--chat-border);
			flex-shrink: 0;
		}

		.chat-header-left {
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}

		.chat-back-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2rem;
			height: 2rem;
			border: none;
			background: transparent;
			border-radius: 0.5rem;
			cursor: pointer;
			color: var(--chat-muted-fg);
			transition: background 0.15s, color 0.15s;
			text-decoration: none;
		}

		.chat-back-btn:hover {
			background: var(--chat-muted);
			color: var(--chat-primary);
		}

		.chat-avatar {
			width: 2.5rem;
			height: 2.5rem;
			border-radius: 50%;
			object-fit: cover;
			flex-shrink: 0;
		}

		.chat-user-info h2 {
			margin: 0;
			font-size: 0.875rem;
			font-weight: 600;
			line-height: 1.25;
			color: var(--chat-primary);
		}

		.chat-user-info p {
			margin: 0;
			font-size: 0.75rem;
			color: var(--chat-muted-fg);
		}

		.chat-header-actions {
			display: flex;
			align-items: center;
			gap: 0.25rem;
		}

		.chat-action-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2rem;
			height: 2rem;
			border: none;
			background: transparent;
			border-radius: 0.5rem;
			cursor: pointer;
			color: var(--chat-muted-fg);
			transition: background 0.15s, color 0.15s;
		}

		.chat-action-btn:hover {
			background: var(--chat-muted);
			color: var(--chat-primary);
		}

		/* ─── Messages Area ─── */
		.chat-messages {
			flex: 1;
			overflow-y: auto;
			padding: 1rem;
			display: flex;
			flex-direction: column;
			gap: 1rem;
			scroll-behavior: smooth;
		}

		.chat-messages::-webkit-scrollbar {
			width: 6px;
		}

		.chat-messages::-webkit-scrollbar-thumb {
			background: var(--chat-border);
			border-radius: 3px;
		}

		.chat-messages::-webkit-scrollbar-track {
			background: transparent;
		}

		.chat-message {
			display: flex;
			gap: 0.5rem;
			max-width: 85%;
		}

		.chat-message--mine {
			flex-direction: row-reverse;
			align-self: flex-end;
		}

		.chat-message--other {
			align-self: flex-start;
		}

		.chat-message__avatar {
			width: 2rem;
			height: 2rem;
			border-radius: 50%;
			object-fit: cover;
			flex-shrink: 0;
		}

		.chat-message__content {
			display: flex;
			flex-direction: column;
			gap: 0.25rem;
		}

		.chat-message--mine .chat-message__content {
			align-items: flex-end;
		}

		.chat-message__meta {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			font-size: 0.625rem;
		}

		.chat-message--mine .chat-message__meta {
			flex-direction: row-reverse;
		}

		.chat-message__sender {
			font-weight: 500;
			color: var(--chat-primary);
		}

		.chat-message__time {
			color: var(--chat-muted-fg);
		}

		.chat-message__bubble {
			padding: 0.625rem 0.875rem;
			border-radius: var(--chat-radius);
			font-size: 0.875rem;
			line-height: 1.4;
			word-break: break-word;
		}

		.chat-message--mine .chat-message__bubble {
			background: var(--chat-primary);
			color: var(--chat-primary-fg);
			border-bottom-right-radius: 0.25rem;
		}

		.chat-message--other .chat-message__bubble {
			background: var(--chat-muted);
			color: var(--chat-primary);
			border-bottom-left-radius: 0.25rem;
		}

		/* ─── Input Area ─── */
		.chat-input-area {
			padding: 0.75rem 1rem;
			padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));
			background: var(--chat-card);
			border-top: 1px solid var(--chat-border);
			flex-shrink: 0;
		}

		.chat-input-form {
			display: flex;
			align-items: flex-end;
			gap: 0.5rem;
		}

		.chat-input-wrapper {
			flex: 1;
			position: relative;
		}

		.chat-textarea {
			width: 100%;
			min-height: 2.5rem;
			max-height: 7.5rem;
			padding: 0.5rem 0.75rem;
			border: 1px solid var(--chat-border);
			border-radius: var(--chat-radius);
			background: var(--chat-bg);
			color: var(--chat-primary);
			font-family: inherit;
			font-size: 0.875rem;
			line-height: 1.5;
			resize: none;
			outline: none;
			transition: border-color 0.15s;
		}

		.chat-textarea:focus {
			border-color: var(--chat-primary);
		}

		.chat-textarea::placeholder {
			color: var(--chat-muted-fg);
		}

		.chat-send-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2.5rem;
			height: 2.5rem;
			border: none;
			background: var(--chat-primary);
			color: var(--chat-primary-fg);
			border-radius: var(--chat-radius);
			cursor: pointer;
			transition: opacity 0.15s;
			flex-shrink: 0;
		}

		.chat-send-btn:hover {
			opacity: 0.9;
		}

		.chat-send-btn:disabled {
			opacity: 0.5;
			cursor: not-allowed;
		}

		/* ─── Responsive ─── */
		@media (min-width: 768px) {
			.chat-container {
				max-width: 48rem;
				margin: 0 auto;
				border-left: 1px solid var(--chat-border);
				border-right: 1px solid var(--chat-border);
			}

			.chat-message {
				max-width: 70%;
			}
		}
	</style>
</head>
<body class="apollo-canvas-mode">

<div class="chat-container">
	<!-- Chat Header -->
	<header class="chat-header">
		<div class="chat-header-left">
			<a href="<?php echo esc_url( home_url( '/chat/' ) ); ?>" class="chat-back-btn" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-social' ); ?>">
				<i class="ri-arrow-left-line"></i>
			</a>
			<img
				src="<?php echo esc_url( get_avatar_url( $chat_user_id, array( 'size' => 80 ) ) ); ?>"
				alt="<?php echo esc_attr( $chat_user->display_name ); ?>"
				class="chat-avatar"
			>
			<div class="chat-user-info">
				<h2><?php echo esc_html( $chat_user->display_name ); ?></h2>
				<p><?php echo esc_html( '@' . $chat_user->user_login ); ?></p>
			</div>
		</div>
		<div class="chat-header-actions">
			<button type="button" class="chat-action-btn" aria-label="<?php esc_attr_e( 'Chamada de voz', 'apollo-social' ); ?>">
				<i class="ri-phone-line"></i>
			</button>
			<button type="button" class="chat-action-btn" aria-label="<?php esc_attr_e( 'Chamada de vídeo', 'apollo-social' ); ?>">
				<i class="ri-vidicon-line"></i>
			</button>
			<button type="button" class="chat-action-btn" aria-label="<?php esc_attr_e( 'Mais opções', 'apollo-social' ); ?>">
				<i class="ri-more-2-fill"></i>
			</button>
		</div>
	</header>

	<!-- Messages Area -->
	<div class="chat-messages" id="chatMessages">
		<?php foreach ( $messages as $msg ) : ?>
			<?php
			$is_mine = (int) $msg['sender_id'] === $current_user_id;
			$sender  = $is_mine ? $logged_user : $chat_user;
			?>
			<div class="chat-message <?php echo $is_mine ? 'chat-message--mine' : 'chat-message--other'; ?>">
				<img
					src="<?php echo esc_url( get_avatar_url( $sender->ID, array( 'size' => 64 ) ) ); ?>"
					alt="<?php echo esc_attr( $sender->display_name ); ?>"
					class="chat-message__avatar"
				>
				<div class="chat-message__content">
					<div class="chat-message__meta">
						<span class="chat-message__sender"><?php echo esc_html( $sender->display_name ); ?></span>
						<span class="chat-message__time"><?php echo esc_html( $msg['time'] ); ?></span>
					</div>
					<div class="chat-message__bubble">
						<?php echo esc_html( $msg['content'] ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- Input Area -->
	<div class="chat-input-area">
		<form id="chatForm" class="chat-input-form" method="post">
			<?php wp_nonce_field( 'apollo_chat_send', 'apollo_chat_nonce' ); ?>
			<input type="hidden" name="recipient_id" value="<?php echo esc_attr( (string) $chat_user_id ); ?>">
			<div class="chat-input-wrapper">
				<textarea
					name="message"
					id="chatInput"
					class="chat-textarea"
					placeholder="<?php esc_attr_e( 'Digite sua mensagem...', 'apollo-social' ); ?>"
					rows="1"
					aria-label="<?php esc_attr_e( 'Mensagem', 'apollo-social' ); ?>"
				></textarea>
			</div>
			<button type="submit" class="chat-send-btn" id="chatSendBtn" aria-label="<?php esc_attr_e( 'Enviar', 'apollo-social' ); ?>">
				<i class="ri-send-plane-fill"></i>
			</button>
		</form>
	</div>
</div>

<script>
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	var messagesEl = document.getElementById( 'chatMessages' );
	var formEl     = document.getElementById( 'chatForm' );
	var inputEl    = document.getElementById( 'chatInput' );
	var sendBtn    = document.getElementById( 'chatSendBtn' );

	// Scroll to bottom on load
	if ( messagesEl ) {
		messagesEl.scrollTop = messagesEl.scrollHeight;
	}

	// Auto-resize textarea
	if ( inputEl ) {
		inputEl.addEventListener( 'input', function() {
			this.style.height = 'auto';
			this.style.height = Math.min( this.scrollHeight, 120 ) + 'px';
		} );

		// Enter to send (Shift+Enter for newline)
		inputEl.addEventListener( 'keydown', function( e ) {
			if ( 'Enter' === e.key && ! e.shiftKey ) {
				e.preventDefault();
				if ( formEl ) {
					formEl.dispatchEvent( new Event( 'submit', { cancelable: true } ) );
				}
			}
		} );
	}

	// Form submission
	if ( formEl ) {
		formEl.addEventListener( 'submit', function( e ) {
			e.preventDefault();

			if ( ! inputEl ) {
				return;
			}

			var message = inputEl.value.trim();

			if ( '' === message ) {
				return;
			}

			// Disable button while sending
			if ( sendBtn ) {
				sendBtn.disabled = true;
			}

			// TODO: Implement AJAX send via wp_ajax
			console.log( 'Apollo Chat: Sending message', message );

			// Clear input
			inputEl.value = '';
			inputEl.style.height = 'auto';

			// Re-enable button
			if ( sendBtn ) {
				sendBtn.disabled = false;
			}

			// Focus input
			inputEl.focus();
		} );
	}
} );
</script>

<?php wp_footer(); ?>
</body>
</html>
