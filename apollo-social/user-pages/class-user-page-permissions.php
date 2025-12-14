<?php

/**
 * Permissões e segurança para edição
 */
class Apollo_User_Page_Permissions
{
    public static function can_edit($user_id, $post_id)
    {
        return get_current_user_id() == $user_id || current_user_can('edit_post', $post_id);
    }
    public static function sanitize_props($props)
    {
        // Sanitiza props dos widgets (exemplo básico)
        foreach ($props as $k => $v) {
            if (is_string($v)) {
                $props[ $k ] = sanitize_text_field($v);
            }
        }

        return $props;
    }
}
