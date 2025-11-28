<?php
/**
 * Plugin Name: Apollo Social Core
 * Plugin URI:  https://apollo.rio.br/plugins/apollo-social-core
 * Description: Apollo Social Core - Sistema social completo com perfis, feed, grupos e comunidades. Canvas Mode, rotas dinâmicas e integrações.
 * Version:     1.0.0
 * Author:      Apollo::Rio Team
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-social
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 8.1
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Composer Autoloader - DocuSeal e-signature integration & Delta Parser
 * 
 * Loads:
 * - docuseal-php library for DocuSeal e-signature API integration
 * - nadar/quill-delta-parser for Delta to HTML conversion
 * 
 * - vendor/autoload.php is optional in local/dev environments
 * - Required in production if e-signature or document features are needed
 * - Install via: composer require docusealco/docuseal-php nadar/quill-delta-parser
 * 
 * @see https://github.com/docusealco/docuseal-php
 * @see https://github.com/nadar/quill-delta-parser
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Define plugin constants
define('APOLLO_SOCIAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APOLLO_SOCIAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APOLLO_SOCIAL_VERSION', '0.0.1');

/**
 * Check if Apollo Core dependency is met
 * 
 * @return bool True if Apollo Core is active and available
 */
function apollo_social_dependency_ok() {
    // Check if function exists (WordPress loaded)
    if (function_exists('is_plugin_active')) {
        // Check if apollo-core is active
        if (!is_plugin_active('apollo-core/apollo-core.php')) {
            return false;
        }
    }
    
    // Check if Apollo Core is bootstrapped
    if (!class_exists('Apollo_Core') && !defined('APOLLO_CORE_BOOTSTRAPPED')) {
        return false;
    }
    
    return true;
}

/**
 * Display admin notice when Apollo Core is missing
 */
function apollo_social_missing_core_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <strong><?php esc_html_e('Apollo Social Core', 'apollo-social'); ?></strong>: 
            <?php esc_html_e('O plugin "Apollo Core" não está ativo. Por favor, ative o plugin "apollo-core" para usar o Apollo Social Core.', 'apollo-social'); ?>
        </p>
    </div>
    <?php
}

// Early dependency check - prevent fatal errors if core is missing
if (!apollo_social_dependency_ok()) {
    add_action('admin_notices', 'apollo_social_missing_core_notice');
    // Don't load the rest of the plugin
    return;
}

// Autoload classes (PSR-4)
spl_autoload_register(function ($class) {
    $prefix = 'Apollo\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Constructor automatically calls bootstrap()
    $plugin = new \Apollo\Plugin();
    
    // Load ShadCN/Tailwind loader (centralizado para todos os plugins Apollo)
    $shadcn_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
    if (file_exists($shadcn_loader)) {
        require_once $shadcn_loader;
    }
    
    // Load user-pages module
    $user_pages_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/user-pages-loader.php';
    if (file_exists($user_pages_loader)) {
        require_once $user_pages_loader;
    }
    
    // Load Help Menu Admin
    if (is_admin()) {
        $help_menu = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Admin/HelpMenuAdmin.php';
        if (file_exists($help_menu)) {
            require_once $help_menu;
        }
        
        // Load E-signature Settings Admin
        $esign_settings = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Admin/EsignSettingsAdmin.php';
        if (file_exists($esign_settings)) {
            require_once $esign_settings;
        }
    }
    
    // Load AJAX Image Upload Handler for Quill Editor
    $image_upload = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Ajax/ImageUploadHandler.php';
    if (file_exists($image_upload)) {
        require_once $image_upload;
    }
    
    // Load AJAX Document Save Handler for Quill Editor (Delta autosave)
    $doc_save = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Ajax/DocumentSaveHandler.php';
    if (file_exists($doc_save)) {
        require_once $doc_save;
    }
    
    // Load AJAX PDF Export Handler for document-to-PDF conversion
    $pdf_export = APOLLO_SOCIAL_PLUGIN_DIR . 'src/Ajax/PdfExportHandler.php';
    if (file_exists($pdf_export)) {
        require_once $pdf_export;
    }
    
    // Load Delta Helper Functions (apollo_delta_to_html, etc.)
    $delta_helpers = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/delta-helpers.php';
    if (file_exists($delta_helpers)) {
        require_once $delta_helpers;
    }
    
    // Load Luckysheet Helper Functions (apollo_spreadsheet_to_luckysheet, etc.)
    $luckysheet_helpers = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/luckysheet-helpers.php';
    if (file_exists($luckysheet_helpers)) {
        require_once $luckysheet_helpers;
    }
    
    // Initialize Documents Module (Libraries, Signatures, Audit)
    if (class_exists('\Apollo\Modules\Documents\DocumentsModule')) {
        \Apollo\Modules\Documents\DocumentsModule::init();
    }
}, 5);

// P0-1: Improved activation hook with idempotency checks
register_activation_hook(__FILE__, function() {
    // Check Apollo Core dependency first
    if (!function_exists('apollo_social_dependency_ok') || !apollo_social_dependency_ok()) {
        // Deactivate this plugin
        if (function_exists('deactivate_plugins')) {
            deactivate_plugins(plugin_basename(__FILE__));
        }
        
        // Show error message
        wp_die(
            '<h1>' . esc_html__('Plugin Activation Failed', 'apollo-social') . '</h1>' .
            '<p>' . esc_html__('Apollo Social Core requires Apollo Core to be active.', 'apollo-social') . '</p>' .
            '<p>' . esc_html__('Please activate the "Apollo Core" plugin first, then activate Apollo Social Core.', 'apollo-social') . '</p>',
            esc_html__('Dependency Error', 'apollo-social'),
            array('back_link' => true)
        );
        return;
    }
    
    // Check if already activated recently (prevent double runs)
    $activation_key = 'apollo_social_activation_' . APOLLO_SOCIAL_VERSION;
    $last_activation = get_option($activation_key, false);
    
    // If activated in last 5 minutes, skip (might be double-click or refresh)
    if ($last_activation && (time() - $last_activation) < 300) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Social: Activation skipped (already activated recently)');
        }
        return;
    }
    
    // Mark activation start
    update_option($activation_key, time());
    
    try {
        // Create database tables (idempotent - uses dbDelta)
        $schema = new \Apollo\Infrastructure\Database\Schema();
        $schema->install();
        $schema->updateGroupsTable();
        
        // Create default groups (idempotent - checks existence)
        $default_groups = new \Apollo\Domain\Groups\DefaultGroups();
        $default_groups->createDefaults();
        
        // Register routes
        $routes = new \Apollo\Infrastructure\Http\Routes();
        $routes->register();
        
        // Load user-pages CPT and rewrite
        $user_pages_cpt = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-cpt.php';
        if (file_exists($user_pages_cpt)) {
            require_once $user_pages_cpt;
            if (class_exists('Apollo_User_Page_CPT')) {
                Apollo_User_Page_CPT::register();
            }
        }
        
        $user_pages_rewrite = APOLLO_SOCIAL_PLUGIN_DIR . 'user-pages/class-user-page-rewrite.php';
        if (file_exists($user_pages_rewrite)) {
            require_once $user_pages_rewrite;
            if (class_exists('Apollo_User_Page_Rewrite')) {
                Apollo_User_Page_Rewrite::add_rewrite();
            }
        }
        
        // Flush rewrite rules (only once per version)
        flush_rewrite_rules(false); // false = soft flush (faster)
        
        // Mark activation complete
        update_option('apollo_social_activated_version', APOLLO_SOCIAL_VERSION);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Social: Activation completed successfully (v' . APOLLO_SOCIAL_VERSION . ')');
        }
    } catch (\Exception $e) {
        // Log error but don't break activation
        error_log('❌ Apollo Social: Activation error - ' . $e->getMessage());
        // Still mark as activated to prevent retry loops
        update_option($activation_key, time());
    }
});

// Clean up on deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

/**
 * AJAX Handler: Submit Depoimento (Testimonial)
 * STRICT MODE: Required for user-page-view.php testimonials form
 */
add_action('wp_ajax_apollo_submit_depoimento', 'apollo_social_handle_depoimento_submit');
function apollo_social_handle_depoimento_submit() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'apollo_depoimento_nonce')) {
        wp_send_json_error(['message' => 'Nonce inválido.'], 403);
        return;
    }
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado.'], 401);
        return;
    }
    
    // Validate input
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'ID do post inválido.'], 400);
        return;
    }
    
    if (empty($content) || strlen($content) < 5) {
        wp_send_json_error(['message' => 'O depoimento deve ter pelo menos 5 caracteres.'], 400);
        return;
    }
    
    if (strlen($content) > 1000) {
        wp_send_json_error(['message' => 'O depoimento não pode exceder 1000 caracteres.'], 400);
        return;
    }
    
    // Check post exists and is a user_page
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'user_page') {
        wp_send_json_error(['message' => 'Página de usuário não encontrada.'], 404);
        return;
    }
    
    // Check user is not commenting on own page
    $page_owner_id = $post->post_author;
    $current_user_id = get_current_user_id();
    
    if ($page_owner_id === $current_user_id) {
        wp_send_json_error(['message' => 'Você não pode deixar depoimento na sua própria página.'], 400);
        return;
    }
    
    // Rate limiting: max 3 depoimentos per user per day
    $today_key = 'apollo_depoimentos_' . date('Y-m-d') . '_' . $current_user_id;
    $today_count = (int) get_transient($today_key);
    
    if ($today_count >= 3) {
        wp_send_json_error(['message' => 'Limite diário de depoimentos atingido (3 por dia).'], 429);
        return;
    }
    
    // Get current user data
    $current_user = wp_get_current_user();
    
    // Insert comment
    $comment_data = [
        'comment_post_ID' => $post_id,
        'comment_author' => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_author_url' => $current_user->user_url ?: '',
        'comment_content' => $content,
        'comment_type' => 'comment',
        'comment_parent' => 0,
        'user_id' => $current_user_id,
        'comment_date' => current_time('mysql'),
        'comment_date_gmt' => current_time('mysql', true),
        'comment_approved' => 1, // Auto-approve for logged-in users
    ];
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(['message' => 'Erro ao salvar depoimento.'], 500);
        return;
    }
    
    // Increment daily counter
    set_transient($today_key, $today_count + 1, DAY_IN_SECONDS);
    
    // Log for audit
    update_user_meta($current_user_id, '_last_depoimento_time', time());
    
    wp_send_json_success([
        'message' => 'Depoimento publicado com sucesso!',
        'comment_id' => $comment_id
    ]);
}