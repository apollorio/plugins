<?php
/**
 * Endpoints AJAX para salvar/carregar layout, upload de mídia, depoimentos
 */
class Apollo_User_Page_Editor_AJAX {
    public static function save_layout() {
        check_ajax_referer('apollo_userpage_save', 'nonce');
        $user_id = intval($_POST['user_id']);
        $post_id = get_user_meta($user_id, 'apollo_user_page_id', true);
        if (get_current_user_id() != $user_id && !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Acesso negado');
        }
        $layout = json_decode(stripslashes($_POST['layout']), true);
        if (!$layout || !is_array($layout) || strlen($_POST['layout']) > 300000) {
            wp_send_json_error('Layout inválido ou muito grande');
        }
        update_post_meta($post_id, 'apollo_userpage_layout_v1', $layout);
        wp_send_json_success(['ok' => true]);
    }
    public static function load_layout() {
        check_ajax_referer('apollo_userpage_load', 'nonce');
        $user_id = intval($_POST['user_id']);
        $post_id = get_user_meta($user_id, 'apollo_user_page_id', true);
        if (!$post_id) wp_send_json_error('Página não encontrada');
        $layout = get_post_meta($post_id, 'apollo_userpage_layout_v1', true);
        wp_send_json_success(['layout' => $layout]);
    }
}
add_action('wp_ajax_apollo_userpage_save', ['Apollo_User_Page_Editor_AJAX', 'save_layout']);
add_action('wp_ajax_apollo_userpage_load', ['Apollo_User_Page_Editor_AJAX', 'load_layout']);
