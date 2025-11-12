<?php
/**
 * Regras de rewrite para /id/{userID}
 */
class Apollo_User_Page_Rewrite {
    public static function add_rewrite() {
        add_rewrite_rule('^id/(\d+)/(?:$|\?.*)', 'index.php?apollo_user_id=$matches[1]', 'top');
        add_rewrite_tag('%apollo_user_id%', '([0-9]+)');
    }
}
add_action('init', ['Apollo_User_Page_Rewrite', 'add_rewrite']);
