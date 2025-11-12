<?php
/**
 * Template loader para view e editor
 */
class Apollo_User_Page_Template_Loader {
    public static function intercept($template) {
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
