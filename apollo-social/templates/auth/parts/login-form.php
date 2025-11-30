<?php
/**
 * ================================================================================
 * APOLLO AUTH - Login Form Template Part
 * ================================================================================
 * Displays the login form with username/email and password fields.
 * 
 * @package Apollo_Social
 * @since 1.0.0
 * 
 * PLACEHOLDERS:
 * - {{username_label}} - Label for username field
 * - {{password_label}} - Label for password field
 * - {{remember_label}} - Label for remember me toggle
 * - {{login_button}} - Submit button text
 * - {{forgot_password_text}} - Forgot password link text
 * - {{register_text}} - Register link text
 * 
 * STATUS MESSAGES (Updated as per user request):
 * - Line 1: "Versão BETA LAB, caso de bug, relate <a>aqui</a>."
 * - Line 2: "'Sua Plataforma Digital da cultura carioca.'"
 * ================================================================================
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get URLs from config
$bug_report_url = isset($auth_config['bug_report_url']) ? $auth_config['bug_report_url'] : 'https://apollo.rio.br/bug/';
?>

<!-- Status Messages -->
<div class="flavor-text" data-tooltip="<?php esc_attr_e('Mensagens de status do sistema', 'apollo-social'); ?>">
    <span data-tooltip="<?php esc_attr_e('Versão atual', 'apollo-social'); ?>">
        > <?php esc_html_e('Versão BETA LAB, caso de bug, relate', 'apollo-social'); ?> 
        <a href="<?php echo esc_url($bug_report_url); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-accent);" data-tooltip="<?php esc_attr_e('Reportar bug', 'apollo-social'); ?>">
            <?php esc_html_e('aqui', 'apollo-social'); ?>
        </a>.
    </span>
</div>
<div class="flavor-text" style="margin-bottom: 16px;" data-tooltip="<?php esc_attr_e('Slogan', 'apollo-social'); ?>">
    <span>> '<?php esc_html_e('Sua Plataforma Digital da cultura carioca.', 'apollo-social'); ?>'</span>
</div>

<!-- Login Form -->
<form id="login-form" method="post" data-tooltip="<?php esc_attr_e('Formulário de login', 'apollo-social'); ?>">
    
    <?php wp_nonce_field('apollo_login_nonce', 'apollo_login_nonce'); ?>
    
    <!-- Username/Email Field -->
    <div class="form-group" data-tooltip="<?php esc_attr_e('Campo de identificação', 'apollo-social'); ?>">
        <label for="log"><?php esc_html_e('Identificação', 'apollo-social'); ?></label>
        <div class="input-wrapper">
            <span class="input-prefix" data-tooltip="<?php esc_attr_e('Prefixo do campo', 'apollo-social'); ?>">></span>
            <input 
                type="text" 
                id="log" 
                name="log" 
                placeholder="<?php esc_attr_e('e-mail ou usuário', 'apollo-social'); ?>"
                autocomplete="username"
                required
                data-tooltip="<?php esc_attr_e('Digite seu e-mail ou nome de usuário', 'apollo-social'); ?>"
            >
        </div>
    </div>

    <!-- Password Field -->
    <div class="form-group" data-tooltip="<?php esc_attr_e('Campo de senha', 'apollo-social'); ?>">
        <label for="pwd"><?php esc_html_e('Chave de Acesso', 'apollo-social'); ?></label>
        <div class="input-wrapper">
            <span class="input-prefix" data-tooltip="<?php esc_attr_e('Prefixo do campo', 'apollo-social'); ?>">></span>
            <input 
                type="password" 
                id="pwd" 
                name="pwd" 
                placeholder="••••••••"
                autocomplete="current-password"
                required
                data-tooltip="<?php esc_attr_e('Digite sua senha', 'apollo-social'); ?>"
            >
        </div>
    </div>

    <!-- Remember Session Toggle -->
    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;" data-tooltip="<?php esc_attr_e('Opções adicionais', 'apollo-social'); ?>">
        <div class="custom-toggle" data-tooltip="<?php esc_attr_e('Manter sessão ativa', 'apollo-social'); ?>">
            <div class="toggle-track">
                <div class="toggle-thumb"></div>
            </div>
            <span><?php esc_html_e('Manter sessão', 'apollo-social'); ?></span>
            <input type="hidden" name="rememberme" value="0">
        </div>
        <button type="button" class="btn-text" id="forgot-password" data-tooltip="<?php esc_attr_e('Recuperar acesso', 'apollo-social'); ?>">
            <?php esc_html_e('Esqueci a chave', 'apollo-social'); ?>
        </button>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-primary" data-tooltip="<?php esc_attr_e('Acessar o sistema', 'apollo-social'); ?>">
        <span><?php esc_html_e('ACESSAR TERMINAL', 'apollo-social'); ?></span>
        <i class="ri-arrow-right-line"></i>
    </button>

</form>

<!-- Register Link -->
<div style="text-align: center; margin-top: 20px;" data-tooltip="<?php esc_attr_e('Link para registro', 'apollo-social'); ?>">
    <p style="font-size: 12px; color: rgba(148,163,184,0.9);">
        <?php esc_html_e('Não possui acesso?', 'apollo-social'); ?>
        <button type="button" class="btn-text" id="switch-to-register" data-tooltip="<?php esc_attr_e('Criar nova conta', 'apollo-social'); ?>">
            <?php esc_html_e('Solicitar registro', 'apollo-social'); ?>
        </button>
    </p>
</div>
