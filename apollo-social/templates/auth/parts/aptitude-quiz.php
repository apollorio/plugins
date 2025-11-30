<?php
/**
 * ================================================================================
 * APOLLO AUTH - Aptitude Quiz Template Part
 * ================================================================================
 * Displays the aptitude quiz overlay for new user registration.
 * Contains 4 tests: Pattern Recognition, Simon Game, Ethics Quiz, Reaction Test
 * 
 * @package Apollo_Social
 * @since 1.0.0
 * 
 * QUIZ STAGES:
 * 1. Pattern Recognition - Musical pattern sequence
 * 2. Simon Game - Color memory sequence (4 levels)
 * 3. Ethics Quiz - Community values understanding
 * 4. Reaction Test - Capture targets before they disappear
 * 
 * PLACEHOLDERS:
 * - {{test_progress}} - Current test number (e.g., "TESTE 1 DE 4")
 * - {{test_content}} - Dynamically loaded test content
 * ================================================================================
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="aptitude-overlay" id="aptitude-overlay" data-tooltip="<?php esc_attr_e('Teste de Aptidão', 'apollo-social'); ?>">
    
    <!-- Quiz Header -->
    <header class="apollo-header" style="flex-shrink: 0;" data-tooltip="<?php esc_attr_e('Cabeçalho do quiz', 'apollo-social'); ?>">
        <div class="logo-mark">
            <div class="logo-icon"></div>
            <div class="logo-text">
                <span class="brand"><?php esc_html_e('APTIDÃO', 'apollo-social'); ?></span>
                <span class="sub"><?php esc_html_e('Teste de Admissão', 'apollo-social'); ?></span>
            </div>
        </div>
        <div class="coordinates">
            <span id="test-progress" data-tooltip="<?php esc_attr_e('Progresso do teste', 'apollo-social'); ?>">
                <?php esc_html_e('TESTE 1 DE 4', 'apollo-social'); ?>
            </span>
        </div>
    </header>

    <!-- Quiz Content Area -->
    <div class="scroll-area" style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
        
        <!-- Dynamic Test Content (populated by JavaScript) -->
        <div id="test-content" style="text-align: center; padding: 20px;" data-tooltip="<?php esc_attr_e('Conteúdo do teste atual', 'apollo-social'); ?>">
            
            <!-- Initial loading state -->
            <div class="test-loading">
                <i class="ri-loader-4-line" style="font-size: 32px; color: var(--color-accent); animation: spin 1s linear infinite;"></i>
                <p style="margin-top: 12px; color: rgba(148,163,184,0.9);">
                    <?php esc_html_e('Carregando teste...', 'apollo-social'); ?>
                </p>
            </div>
            
        </div>

        <!-- Test Navigation Button -->
        <div style="padding: 0 22px 20px; flex-shrink: 0;">
            <button type="button" id="test-btn" class="btn-primary" disabled data-tooltip="<?php esc_attr_e('Botão de confirmação', 'apollo-social'); ?>">
                <span id="test-btn-text"><?php esc_html_e('CONFIRMAR', 'apollo-social'); ?></span>
                <i class="ri-arrow-right-line"></i>
            </button>
        </div>

    </div>

    <!-- Quiz Footer -->
    <footer style="flex-shrink: 0;" data-tooltip="<?php esc_attr_e('Rodapé do quiz', 'apollo-social'); ?>">
        <p>
            <?php esc_html_e('Este teste avalia sua compatibilidade com a comunidade Apollo.', 'apollo-social'); ?>
        </p>
        <p>
            <?php esc_html_e('Responda com atenção. Erros reiniciam a pergunta atual.', 'apollo-social'); ?>
        </p>
    </footer>

</div>

<style>
/* Spin animation for loader */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
