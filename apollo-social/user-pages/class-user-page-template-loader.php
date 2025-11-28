<?php
/**
 * Template loader para user pages:
 * - /id/{user_id}/ - view (public) ou ?action=edit (owner)
 * - /meu-perfil/ - private profile dashboard (logged-in user)
 */
class Apollo_User_Page_Template_Loader {
    public static function intercept($template) {
        // Check for private profile route first
        $private_profile = get_query_var('apollo_private_profile');
        if ($private_profile) {
            $private_template = WP_PLUGIN_DIR . '/apollo-social/templates/private-profile.php';
            if (file_exists($private_template)) {
                return $private_template;
            }
        }
        
        // Check for public user page route
        $user_id = get_query_var('apollo_user_id');
        if ($user_id) {
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
            if ($action === 'edit') {
                $editor = WP_PLUGIN_DIR . '/apollo-social/templates/user-page-editor.php';
                if (file_exists($editor)) return $editor;
            } else {
                $view = WP_PLUGIN_DIR . '/apollo-social/templates/user-page-view.php';
                if (file_exists($view)) return $view;
            }
        }
        return $template;
    }
}
add_filter('template_include', ['Apollo_User_Page_Template_Loader', 'intercept']);
