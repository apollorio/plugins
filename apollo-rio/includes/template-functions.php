<?php
/**
 * Apollo Template Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if request is from PWA
 */
function apollo_is_pwa() {
    $is_pwa = false;
    
    // 1. Check cookie (set by JavaScript)
    $display_mode = isset($_COOKIE['apollo_display_mode']) ? sanitize_text_field(wp_unslash($_COOKIE['apollo_display_mode'])) : '';
    if ($display_mode === 'standalone') {
        $is_pwa = true;
    }
    
    // 2. Check custom header (for programmatic detection)
    $header_value = isset($_SERVER['HTTP_X_APOLLO_PWA']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_APOLLO_PWA'])) : '';
    if ($header_value === 'true') {
        $is_pwa = true;
    }
    
    // 3. Check User-Agent for iOS standalone indicators
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // iOS PWA often has 'Mobile/' without 'Safari/' in UA
        $is_ios = stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false;
        $has_mobile = stripos($user_agent, 'Mobile/') !== false;
        $no_safari = stripos($user_agent, 'Safari/') === false;
        
        // If iOS + Mobile + no Safari = likely PWA
        if ($is_ios && $has_mobile && $no_safari) {
            $is_pwa = true;
        }
    }
    
    return apply_filters('apollo_is_pwa', $is_pwa);
}

/**
 * Check if mobile device
 */
function apollo_is_mobile() {
    return wp_is_mobile();
}

/**
 * Determine if page should show content or PWA redirect
 * FIXED: Now properly handles all scenarios including iOS PWA
 */
function apollo_should_show_content($template_type) {
    $is_mobile = apollo_is_mobile();
    $is_pwa = apollo_is_pwa();
    
    // pagx_site: ALWAYS show content (public pages)
    if ($template_type === 'pagx_site') {
        return true;
    }
    
    // pagx_app and pagx_appclean: Conditional logic
    if (in_array($template_type, ['pagx_app', 'pagx_appclean'])) {
        // Desktop: ALWAYS show content
        if (!$is_mobile) {
            return true;
        }
        
        // Mobile + PWA: SHOW content
        if ($is_mobile && $is_pwa) {
            return true;
        }
        
        // Mobile + Browser: SHOW install page
        return false;
    }
    
    // Default fallback: show content
    return true;
}

/**
 * Get header for template type
 */
function apollo_get_header_for_template($template_type) {
    $header_file = 'header.php';
    
    if ($template_type === 'pagx_appclean') {
        $header_file = 'header-minimal.php';
    }
    
    apollo_get_header($header_file);
}

/**
 * Get footer for template type
 */
function apollo_get_footer_for_template($template_type) {
    $footer_file = 'footer.php';
    
    if ($template_type === 'pagx_appclean') {
        $footer_file = 'footer-minimal.php';
    }
    
    apollo_get_footer($footer_file);
}

/**
 * Load custom header
 */
function apollo_get_header($name = null) {
    do_action('apollo_before_header', $name);
    
    $template = 'partials/header';
    if ($name) {
        $name = str_replace('.php', '', $name);
        $template = 'partials/' . $name;
    }
    $template .= '.php';
    
    $file = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template;
    
    if (file_exists($file)) {
        load_template($file, false);
    }
    
    do_action('apollo_after_header', $name);
}

/**
 * Load custom footer
 */
function apollo_get_footer($name = null) {
    do_action('apollo_before_footer', $name);
    
    $template = 'partials/footer';
    if ($name) {
        $name = str_replace('.php', '', $name);
        $template = 'partials/' . $name;
    }
    $template .= '.php';
    
    $file = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template;
    
    if (file_exists($file)) {
        load_template($file, false);
    }
    
    do_action('apollo_after_footer', $name);
}

/**
 * Render PWA install page
 * IMPROVED: Better iOS instructions and auto-detection
 */
function apollo_render_pwa_install_page() {
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $is_ios = wp_is_mobile() && (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false);
    $is_android = wp_is_mobile() && stripos($user_agent, 'Android') !== false;
    ?>
    <div class="apollo-pwa-install-page">
        <div class="apollo-pwa-container">
            <div class="apollo-pwa-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
            </div>
            
            <h1 class="apollo-pwa-title">
                <?php _e('Abra nosso app para acessar este conteúdo', 'apollo-rio'); ?>
            </h1>
            
            <p class="apollo-pwa-subtitle">
                <?php 
                if ($is_ios) {
                    _e('Adicione este site à sua tela inicial para acessar como app', 'apollo-rio');
                } elseif ($is_android) {
                    _e('Baixe nosso app ou adicione à tela inicial', 'apollo-rio');
                } else {
                    _e('Se você ainda não tem, baixe', 'apollo-rio');
                }
                ?> 
                <?php if (!$is_ios): ?>
                <a href="#" id="apollo-pwa-get-app" class="apollo-pwa-link">
                    <?php _e('aqui', 'apollo-rio'); ?>
                </a>
                <?php endif; ?>
            </p>
            
            <?php if ($is_ios): ?>
                <!-- Auto-open iOS instructions -->
                <div id="apollo-pwa-accordion" class="apollo-pwa-accordion" style="display: block;">
                    <div class="apollo-pwa-accordion-item">
                        <button class="apollo-pwa-accordion-button active" data-target="ios-content">
                            <span class="apollo-pwa-platform-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                </svg>
                            </span>
                            Como instalar no iPhone/iPad
                            <span class="apollo-pwa-accordion-icon">▲</span>
                        </button>
                        <div id="ios-content" class="apollo-pwa-accordion-content" style="display: block;">
                            <div class="apollo-pwa-ios-instructions">
                                <ol>
                                    <li>
                                        <strong>1. Toque no botão Compartilhar</strong>
                                        <div class="apollo-pwa-icon-demo">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                                                <polyline points="16 6 12 2 8 6"></polyline>
                                                <line x1="12" y1="2" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Na parte inferior do Safari</p>
                                    </li>
                                    <li>
                                        <strong>2. Role e toque em "Adicionar à Tela de Início"</strong>
                                        <div class="apollo-pwa-icon-demo">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                                <line x1="8" y1="12" x2="16" y2="12"></line>
                                            </svg>
                                        </div>
                                    </li>
                                    <li>
                                        <strong>3. Toque em "Adicionar"</strong>
                                        <p>No canto superior direito</p>
                                    </li>
                                    <li>
                                        <strong>4. Abra o ícone na tela inicial</strong>
                                        <p>Pronto! Agora você tem acesso ao conteúdo</p>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Android/Desktop options -->
                <div id="apollo-pwa-accordion" class="apollo-pwa-accordion" style="display: none;">
                    
                    <!-- Android Option -->
                    <div class="apollo-pwa-accordion-item">
                        <button class="apollo-pwa-accordion-button" data-target="android-content">
                            <span class="apollo-pwa-platform-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85-.29-.15-.65-.06-.83.22l-1.88 3.24a11.5 11.5 0 0 0-8.94 0L5.65 5.67c-.19-.28-.54-.37-.83-.22-.3.16-.42.54-.26.85l1.84 3.18C4.05 11.15 2.5 13.64 2.5 16.5h19c0-2.86-1.55-5.35-3.9-7.02zM7 14.75c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25zm10 0c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25z"/>
                                </svg>
                            </span>
                            Android
                            <span class="apollo-pwa-accordion-icon">▼</span>
                        </button>
                        <div id="android-content" class="apollo-pwa-accordion-content">
                            <a href="<?php echo esc_url(get_option('apollo_android_app_url', '#')); ?>" 
                               class="apollo-pwa-download-btn android-btn" 
                               target="_blank" 
                               rel="noopener">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85-.29-.15-.65-.06-.83.22l-1.88 3.24a11.5 11.5 0 0 0-8.94 0L5.65 5.67c-.19-.28-.54-.37-.83-.22-.3.16-.42.54-.26.85l1.84 3.18C4.05 11.15 2.5 13.64 2.5 16.5h19c0-2.86-1.55-5.35-3.9-7.02zM7 14.75c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25zm10 0c-.69 0-1.25-.56-1.25-1.25s.56-1.25 1.25-1.25 1.25.56 1.25 1.25-.56 1.25-1.25 1.25z"/>
                                </svg>
                                <?php _e('Baixar para Android', 'apollo-rio'); ?>
                            </a>
                        </div>
                    </div>
                    
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var getAppLink = document.getElementById('apollo-pwa-get-app');
        if (getAppLink) {
            getAppLink.addEventListener('click', function(e) {
                e.preventDefault();
                var accordion = document.getElementById('apollo-pwa-accordion');
                accordion.style.display = accordion.style.display === 'none' ? 'block' : 'none';
            });
        }
        
        document.querySelectorAll('.apollo-pwa-accordion-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var target = this.getAttribute('data-target');
                var content = document.getElementById(target);
                var icon = this.querySelector('.apollo-pwa-accordion-icon');
                
                document.querySelectorAll('.apollo-pwa-accordion-content').forEach(function(c) {
                    if (c.id !== target) {
                        c.style.display = 'none';
                    }
                });
                
                document.querySelectorAll('.apollo-pwa-accordion-icon').forEach(function(i) {
                    if (i !== icon) {
                        i.textContent = '▼';
                    }
                });
                
                if (content.style.display === 'none' || !content.style.display) {
                    content.style.display = 'block';
                    icon.textContent = '▲';
                    this.classList.add('active');
                } else {
                    content.style.display = 'none';
                    icon.textContent = '▼';
                    this.classList.remove('active');
                }
            });
        });
    });
    </script>
    <?php
}