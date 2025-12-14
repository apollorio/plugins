<?php

/**
 * SEO e metas para páginas de usuário
 */
class Apollo_User_Page_SEO
{
    public static function add_meta()
    {
        $user_id = get_query_var('apollo_user_id');
        if ($user_id) {
            if (isset($_GET['action']) && $_GET['action'] === 'edit') {
                echo '<meta name="robots" content="noindex, nofollow">';
                echo '<link rel="canonical" href="' . esc_url(home_url('/id/' . $user_id)) . '">';
            } else {
                $display_name = esc_html(get_userdata($user_id)->display_name);
                echo '<meta property="og:title" content="Perfil de ' . $display_name . '">';
                echo '<meta property="og:url" content="' . esc_url(home_url('/id/' . $user_id)) . '">';
            }
        }
    }
}
add_action('wp_head', [ 'Apollo_User_Page_SEO', 'add_meta' ]);
