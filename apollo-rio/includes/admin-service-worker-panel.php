<?php
declare(strict_types=1);
/**
 * Admin Panel for Service Worker Management
 * 
 * @package Apollo_Rio
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

function apollo_service_worker_admin_menu() {
    add_submenu_page(
        'options-general.php',
        __('Apollo Service Worker', 'apollo-rio'),
        __('Service Worker', 'apollo-rio'),
        'manage_options',
        'apollo-service-worker',
        'apollo_service_worker_admin_page'
    );
}
add_action('admin_menu', 'apollo_service_worker_admin_menu');

function apollo_service_worker_admin_page() {
    $cache_size = apollo_get_cache_size();
    $sw_status = apollo_is_service_worker_active() ? __('Ativo', 'apollo-rio') : __('Inativo', 'apollo-rio');
    ?>
    <div class="wrap">
        <h1><?php _e('Apollo Service Worker', 'apollo-rio'); ?></h1>
        <table class="form-table">
            <tr>
                <th><?php _e('Status do Service Worker', 'apollo-rio'); ?></th>
                <td><?php echo esc_html($sw_status); ?></td>
            </tr>
            <tr>
                <th><?php _e('Tamanho do Cache', 'apollo-rio'); ?></th>
                <td><?php echo esc_html(size_format($cache_size)); ?></td>
            </tr>
        </table>
        <form method="post">
            <?php wp_nonce_field('apollo_clear_cache'); ?>
            <input type="submit" name="apollo_clear_cache" class="button button-primary" value="<?php _e('Limpar Cache', 'apollo-rio'); ?>">
        </form>
    </div>
    <?php
}

function apollo_get_cache_size() {
    // Placeholder for cache size calculation logic
    return 0;
}

function apollo_is_service_worker_active() {
    // Placeholder for checking service worker status
    return true;
}

if (isset($_POST['apollo_clear_cache']) && check_admin_referer('apollo_clear_cache')) {
    // Placeholder for cache clearing logic
    wp_redirect(admin_url('options-general.php?page=apollo-service-worker'));
    exit;
}

function apollo_pwa_settings_menu() {
    add_options_page(
        __('Apollo Rio PWA', 'apollo-rio'),
        __('Apollo Rio PWA', 'apollo-rio'),
        'manage_options',
        'apollo-rio-pwa',
        'apollo_pwa_settings_page'
    );
}
add_action('admin_menu', 'apollo_pwa_settings_menu');

function apollo_pwa_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('apollo_pwa_settings')) {
        update_option('apollo_cache_posts', isset($_POST['apollo_cache_posts']));
        update_option('apollo_cache_media', isset($_POST['apollo_cache_media']));
        update_option('apollo_offline_page', isset($_POST['apollo_offline_page']));
        echo '<div class="updated"><p>' . __('Configurações salvas.', 'apollo-rio') . '</p></div>';
    }

    $cache_posts = get_option('apollo_cache_posts', false);
    $cache_media = get_option('apollo_cache_media', false);
    $offline_page = get_option('apollo_offline_page', false);
    ?>
    <div class="wrap">
        <h1><?php _e('Configurações do Apollo Rio PWA', 'apollo-rio'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('apollo_pwa_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Cachear Posts', 'apollo-rio'); ?></th>
                    <td><input type="checkbox" name="apollo_cache_posts" <?php checked($cache_posts); ?>></td>
                </tr>
                <tr>
                    <th><?php _e('Cachear Mídia', 'apollo-rio'); ?></th>
                    <td><input type="checkbox" name="apollo_cache_media" <?php checked($cache_media); ?>></td>
                </tr>
                <tr>
                    <th><?php _e('Página Offline Customizada', 'apollo-rio'); ?></th>
                    <td><input type="checkbox" name="apollo_offline_page" <?php checked($offline_page); ?>></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" value="<?php _e('Salvar Configurações', 'apollo-rio'); ?>"></p>
        </form>
    </div>
    <?php
}