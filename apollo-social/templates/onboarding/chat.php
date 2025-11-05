<?php
/**
 * Onboarding Chat Template
 * Based on CodePen design: https://codepen.io/Rafael-Valle-the-looper/pen/xbZpXyM
 * Features: chat bubbles, typing animation, minimal header, smooth transitions
 */
?>

<div class="apollo-canvas apollo-onboarding">
    <div class="chat-container">
        <!-- Minimal Header -->
        <div class="chat-header">
            <div class="header-avatar">
                <img src="<?php echo plugin_dir_url(__FILE__) . '../../assets/images/apollo-avatar.png'; ?>" 
                     alt="Apollo" class="avatar-img">
            </div>
            <div class="header-info">
                <h3 class="header-title">Apollo Social</h3>
                <span class="header-status">
                    <span class="status-dot"></span>
                    online
                </span>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chatMessages">
            <!-- Welcome message -->
            <div class="message-bubble bot-message" data-step="welcome">
                <div class="message-content">
                    <p><?php echo esc_html($config['messages']['welcome'] ?? 'Olá! Vou te ajudar a configurar seu perfil no Apollo 🚀'); ?></p>
                </div>
                <div class="message-timestamp">
                    <?php echo date('H:i'); ?>
                </div>
            </div>

            <!-- Dynamic messages will be added here by JavaScript -->
        </div>

        <!-- Chat Input Area -->
        <div class="chat-input-area" id="chatInputArea">
            <!-- Input will be dynamically generated based on current step -->
        </div>

        <!-- Typing Indicator -->
        <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <div class="typing-bubble">
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="onboarding-progress">
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
        </div>
        <div class="progress-text">
            <span id="progressText">Passo 1 de 7</span>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="onboardingData">
{
    "config": <?php echo json_encode($config); ?>,
    "progress": <?php echo json_encode($progress); ?>,
    "currentStep": "<?php echo esc_js($current_step); ?>",
    "userId": <?php echo intval($user_id ?? 0); ?>,
    "nonce": "<?php echo esc_js($nonce); ?>"
}
</script>

<style>
/* Import base styles for Apollo Canvas */
.apollo-canvas.apollo-onboarding {
    max-width: 480px;
    margin: 0 auto;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Chat Container */
.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 0;
    box-shadow: none;
    overflow: hidden;
}

/* Header */
.chat-header {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 12px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.header-info {
    flex: 1;
}

.header-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 2px 0;
}

.header-status {
    font-size: 13px;
    opacity: 0.9;
    display: flex;
    align-items: center;
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    margin-right: 6px;
    animation: pulse 2s infinite;
}

/* Messages Area */
.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    scroll-behavior: smooth;
}

/* Message Bubbles */
.message-bubble {
    margin-bottom: 16px;
    animation: slideUp 0.3s ease;
}

.bot-message .message-content {
    background: #f1f5f9;
    color: #1e293b;
    border-radius: 18px 18px 18px 4px;
    padding: 12px 16px;
    max-width: 85%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.user-message .message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 18px 18px 4px 18px;
    padding: 12px 16px;
    max-width: 85%;
    margin-left: auto;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message-timestamp {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
    text-align: right;
}

.user-message .message-timestamp {
    text-align: right;
    color: #64748b;
}

/* Typing Indicator */
.typing-indicator {
    padding: 0 20px 10px 20px;
}

.typing-bubble {
    background: #f1f5f9;
    border-radius: 18px 18px 18px 4px;
    padding: 12px 16px;
    max-width: 60px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.typing-dots {
    display: flex;
    gap: 4px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background: #64748b;
    border-radius: 50%;
    animation: typingDots 1.4s infinite;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

/* Chat Input Area */
.chat-input-area {
    padding: 16px 20px;
    background: white;
    border-top: 1px solid #e2e8f0;
}

/* Text Input */
.input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.text-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 10px 16px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease;
}

.text-input:focus {
    border-color: #667eea;
}

.send-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.1s ease;
}

.send-button:hover {
    transform: scale(1.05);
}

.send-button:active {
    transform: scale(0.95);
}

/* Choice Chips */
.choice-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.chip {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    background: white;
    color: #374151;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    user-select: none;
}

.chip:hover {
    border-color: #667eea;
    background: #f8fafc;
}

.chip.selected {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.chip.multi-select.selected {
    background: #667eea;
    color: white;
}

/* Contact Form */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.form-input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease;
}

.form-input:focus {
    border-color: #667eea;
}

/* Progress Bar */
.onboarding-progress {
    padding: 16px 20px;
    background: white;
    border-top: 1px solid #e2e8f0;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-size: 12px;
    color: #6b7280;
}

/* Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes typingDots {
    0%, 20% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
    80%, 100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* Responsive */
@media (max-width: 480px) {
    .apollo-canvas.apollo-onboarding {
        max-width: 100%;
        height: 100vh;
    }
    
    .choice-chips {
        flex-direction: column;
    }
    
    .chip {
        text-align: center;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .apollo-canvas.apollo-onboarding {
        background: #0f172a;
    }
    
    .chat-container {
        background: #1e293b;
    }
    
    .bot-message .message-content {
        background: #334155;
        color: #f1f5f9;
    }
    
    .typing-bubble {
        background: #334155;
    }
    
    .chat-input-area {
        background: #1e293b;
        border-color: #334155;
    }
    
    .onboarding-progress {
        background: #1e293b;
        border-color: #334155;
    }
}
</style>