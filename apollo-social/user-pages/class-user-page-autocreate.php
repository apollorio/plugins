<?php
/**
 * Autocriação da página do usuário
 */
class Apollo_User_Page_Autocreate {
    public static function on_register($user_id) {
        if (!get_user_meta($user_id, 'apollo_user_page_id', true)) {
            $post_id = wp_insert_post([
                'post_type' => 'user_page',
                'post_title' => 'Página de ' . get_userdata($user_id)->display_name,
                'post_status' => 'publish',
                'post_author' => $user_id,
                'meta_input' => [ 'user_id' => $user_id ]
            ]);
            if ($post_id) {
                update_user_meta($user_id, 'apollo_user_page_id', $post_id);
            }
        }
    }
    public static function on_demand() {
        $user_id = get_query_var('apollo_user_id');
        if ($user_id && !get_user_meta($user_id, 'apollo_user_page_id', true)) {
            self::on_register($user_id);
        }
    }
}
add_action('user_register', ['Apollo_User_Page_Autocreate', 'on_register']);
add_action('template_redirect', ['Apollo_User_Page_Autocreate', 'on_demand']);
