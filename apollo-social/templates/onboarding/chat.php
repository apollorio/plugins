<?php
/**
 * Onboarding Chat Template
 * STRICT MODE: 100% UNI.CSS conformance
 * Chat-style conversational onboarding flow
 *
 * @package Apollo_Social
 * @subpackage Onboarding
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
}

$config       = $config ?? [];
$progress     = $progress ?? [];
$current_step = $current_step ?? 'welcome';
$user_id      = $user_id ?? get_current_user_id();
$nonce        = $nonce ?? wp_create_nonce( 'apollo_onboarding' );
?>

<div class="ap-onboarding ap-onboarding-chat">
	<!-- Chat Container -->
	<div class="ap-chat-container">
		<!-- Chat Header -->
		<header class="ap-chat-header">
			<div class="ap-chat-header-avatar">
				<div class="ap-avatar ap-avatar-md" style="background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));">
					<i class="ri-robot-2-line"></i>
				</div>
				<div class="ap-chat-status-dot"></div>
			</div>
			<div class="ap-chat-header-info">
				<h3 class="ap-chat-header-title">Apollo Social</h3>
				<span class="ap-chat-header-status">
					<span class="ap-online-indicator"></span>
					online
				</span>
			</div>
			<button class="ap-btn-icon-sm ap-hide-mobile" data-ap-tooltip="Fechar onboarding">
				<i class="ri-close-line"></i>
			</button>
		</header>

		<!-- Messages Area -->
		<div class="ap-chat-messages" id="chatMessages">
			<!-- Welcome message -->
			<div class="ap-chat-message ap-chat-message-bot" data-step="welcome">
				<div class="ap-chat-bubble">
					<p><?php echo esc_html( $config['messages']['welcome'] ?? 'Olá! Vou te ajudar a configurar seu perfil no Apollo 🚀' ); ?></p>
				</div>
				<span class="ap-chat-timestamp"><?php echo esc_html( date_i18n( 'H:i' ) ); ?></span>
			</div>
			<!-- Dynamic messages will be added here by JavaScript -->
		</div>

		<!-- Typing Indicator -->
		<div class="ap-chat-typing" id="typingIndicator" style="display: none;">
			<div class="ap-chat-typing-bubble">
				<span></span>
				<span></span>
				<span></span>
			</div>
		</div>

		<!-- Chat Input Area -->
		<div class="ap-chat-input" id="chatInputArea">
			<!-- Input will be dynamically generated based on current step -->
		</div>

		<!-- Progress Bar -->
		<div class="ap-chat-progress">
			<div class="ap-progress">
				<div class="ap-progress-bar" id="progressFill" style="width: 0%;"></div>
			</div>
			<span class="ap-chat-progress-text" id="progressText">Passo 1 de 7</span>
		</div>
	</div>
</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="onboardingData">
{
	"config": <?php echo wp_json_encode( $config ); ?>,
	"progress": <?php echo wp_json_encode( $progress ); ?>,
	"currentStep": "<?php echo esc_js( $current_step ); ?>",
	"userId": <?php echo intval( $user_id ); ?>,
	"nonce": "<?php echo esc_js( $nonce ); ?>"
}
</script>

<style>
/* ==========================================================================
	ONBOARDING CHAT - UNI.CSS Extension
	========================================================================== */

.ap-onboarding-chat {
	max-width: 480px;
	margin: 0 auto;
	height: 100vh;
	display: flex;
	flex-direction: column;
	background: var(--ap-bg-surface);
	font-family: var(--ap-font-primary);
}

/* Chat Container */
.ap-chat-container {
	flex: 1;
	display: flex;
	flex-direction: column;
	background: var(--ap-bg-card);
	overflow: hidden;
}

/* Chat Header */
.ap-chat-header {
	display: flex;
	align-items: center;
	padding: 16px 20px;
	background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
	color: white;
	position: sticky;
	top: 0;
	z-index: 10;
	gap: 12px;
}

.ap-chat-header-avatar {
	position: relative;
}

.ap-chat-header-avatar .ap-avatar {
	background: rgba(255, 255, 255, 0.2) !important;
}

.ap-chat-status-dot {
	position: absolute;
	bottom: 0;
	right: 0;
	width: 10px;
	height: 10px;
	background: var(--ap-color-success);
	border-radius: 50%;
	border: 2px solid white;
}

.ap-chat-header-info {
	flex: 1;
}

.ap-chat-header-title {
	font-size: var(--ap-text-md);
	font-weight: 600;
	margin: 0;
	color: white;
}

.ap-chat-header-status {
	font-size: var(--ap-text-sm);
	opacity: 0.9;
	display: flex;
	align-items: center;
	gap: 6px;
}

.ap-online-indicator {
	width: 8px;
	height: 8px;
	background: var(--ap-color-success);
	border-radius: 50%;
	animation: ap-pulse 2s infinite;
}

/* Chat Messages */
.ap-chat-messages {
	flex: 1;
	padding: 20px;
	overflow-y: auto;
	scroll-behavior: smooth;
}

.ap-chat-message {
	margin-bottom: 16px;
	animation: ap-slide-up 0.3s ease;
}

.ap-chat-bubble {
	padding: 12px 16px;
	max-width: 85%;
	border-radius: var(--ap-radius-xl);
}

.ap-chat-message-bot .ap-chat-bubble {
	background: var(--ap-bg-muted);
	color: var(--ap-text-primary);
	border-radius: var(--ap-radius-xl) var(--ap-radius-xl) var(--ap-radius-xl) 4px;
	box-shadow: var(--ap-shadow-sm);
}

.ap-chat-message-user .ap-chat-bubble {
	background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
	color: white;
	border-radius: var(--ap-radius-xl) var(--ap-radius-xl) 4px var(--ap-radius-xl);
	margin-left: auto;
	box-shadow: var(--ap-shadow-sm);
}

.ap-chat-timestamp {
	font-size: var(--ap-text-xs);
	color: var(--ap-text-muted);
	margin-top: 4px;
	display: block;
}

.ap-chat-message-user .ap-chat-timestamp {
	text-align: right;
}

/* Typing Indicator */
.ap-chat-typing {
	padding: 0 20px 10px;
}

.ap-chat-typing-bubble {
	background: var(--ap-bg-muted);
	border-radius: var(--ap-radius-xl) var(--ap-radius-xl) var(--ap-radius-xl) 4px;
	padding: 12px 16px;
	max-width: 60px;
	display: flex;
	gap: 4px;
}

.ap-chat-typing-bubble span {
	width: 6px;
	height: 6px;
	background: var(--ap-text-muted);
	border-radius: 50%;
	animation: ap-typing-dots 1.4s infinite;
}

.ap-chat-typing-bubble span:nth-child(2) { animation-delay: 0.2s; }
.ap-chat-typing-bubble span:nth-child(3) { animation-delay: 0.4s; }

/* Chat Input */
.ap-chat-input {
	padding: 16px 20px;
	background: var(--ap-bg-card);
	border-top: 1px solid var(--ap-border-light);
}

.ap-chat-input-group {
	display: flex;
	align-items: center;
	gap: 8px;
}

.ap-chat-text-input {
	flex: 1;
	border: 1px solid var(--ap-border-default);
	border-radius: var(--ap-radius-full);
	padding: 10px 16px;
	font-size: var(--ap-text-base);
	outline: none;
	transition: var(--ap-transition-fast);
	font-family: var(--ap-font-primary);
}

.ap-chat-text-input:focus {
	border-color: var(--ap-orange-500);
	box-shadow: 0 0 0 3px var(--ap-orange-100);
}

.ap-chat-send-btn {
	width: 40px;
	height: 40px;
	border-radius: 50%;
	border: none;
	background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
	color: white;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: var(--ap-transition-fast);
}

.ap-chat-send-btn:hover {
	transform: scale(1.05);
}

.ap-chat-send-btn:active {
	transform: scale(0.95);
}

/* Choice Chips */
.ap-chat-chips {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-top: 12px;
}

.ap-chat-chip {
	padding: 8px 16px;
	border: 1px solid var(--ap-border-default);
	border-radius: var(--ap-radius-full);
	background: var(--ap-bg-card);
	color: var(--ap-text-secondary);
	cursor: pointer;
	font-size: var(--ap-text-sm);
	transition: var(--ap-transition-fast);
	user-select: none;
	font-family: var(--ap-font-primary);
}

.ap-chat-chip:hover {
	border-color: var(--ap-orange-500);
	background: var(--ap-orange-50);
}

.ap-chat-chip.selected {
	background: linear-gradient(135deg, var(--ap-orange-500), var(--ap-orange-600));
	color: white;
	border-color: var(--ap-orange-500);
}

/* Progress */
.ap-chat-progress {
	padding: 16px 20px;
	background: var(--ap-bg-card);
	border-top: 1px solid var(--ap-border-light);
}

.ap-chat-progress .ap-progress {
	margin-bottom: 8px;
}

.ap-chat-progress-text {
	text-align: center;
	font-size: var(--ap-text-xs);
	color: var(--ap-text-muted);
	display: block;
}

/* Animations */
@keyframes ap-slide-up {
	from { opacity: 0; transform: translateY(20px); }
	to { opacity: 1; transform: translateY(0); }
}

@keyframes ap-typing-dots {
	0%, 20% { transform: scale(1); opacity: 1; }
	50% { transform: scale(1.2); opacity: 0.7; }
	80%, 100% { transform: scale(1); opacity: 1; }
}

@keyframes ap-pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}

/* Responsive */
@media (max-width: 480px) {
	.ap-onboarding-chat {
		max-width: 100%;
		height: 100vh;
	}
	
	.ap-chat-chips {
		flex-direction: column;
	}
	
	.ap-chat-chip {
		text-align: center;
	}
}

/* Dark mode */
body.dark-mode .ap-chat-container {
	background: var(--ap-bg-card);
}

body.dark-mode .ap-chat-message-bot .ap-chat-bubble {
	background: var(--ap-bg-muted);
	color: var(--ap-text-primary);
}

body.dark-mode .ap-chat-typing-bubble {
	background: var(--ap-bg-muted);
}

body.dark-mode .ap-chat-input,
body.dark-mode .ap-chat-progress {
	background: var(--ap-bg-card);
	border-color: var(--ap-border-light);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const dataEl = document.getElementById('onboardingData');
	if (!dataEl) return;
	
	const data = JSON.parse(dataEl.textContent);
	
	// Initialize onboarding chat
	window.apolloOnboarding = {
		config: data.config,
		progress: data.progress,
		currentStep: data.currentStep,
		userId: data.userId,
		nonce: data.nonce,
		
		showTyping: function() {
			document.getElementById('typingIndicator').style.display = 'block';
		},
		
		hideTyping: function() {
			document.getElementById('typingIndicator').style.display = 'none';
		},
		
		addMessage: function(content, isUser = false) {
			const container = document.getElementById('chatMessages');
			const msg = document.createElement('div');
			msg.className = 'ap-chat-message ' + (isUser ? 'ap-chat-message-user' : 'ap-chat-message-bot');
			msg.innerHTML = `
				<div class="ap-chat-bubble">
					<p>${content}</p>
				</div>
				<span class="ap-chat-timestamp">${new Date().toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'})}</span>
			`;
			container.appendChild(msg);
			container.scrollTop = container.scrollHeight;
		},
		
		updateProgress: function(percent) {
			document.getElementById('progressFill').style.width = percent + '%';
		},
		
		setProgressText: function(text) {
			document.getElementById('progressText').textContent = text;
		}
	};
	
	// Start onboarding flow
	console.log('Apollo Onboarding initialized', data);
});
</script>
