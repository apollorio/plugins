<?php

// phpcs:ignoreFile
defined('ABSPATH') || exit;

if (! class_exists('WP_List_Table')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * API Keys table list class for wp rest api.
 */
class APRIO_API_Keys_Table_List extends WP_List_Table
{
    /**
     * Initialize the API key table list.
     */
    public function __construct()
    {
        parent::__construct(
            [ 'ajax' => false ]
        );
    }

    /**
     * No items found text.
     */
    public function no_items()
    {
        esc_html_e('No keys found.', 'aprio-rest-api');
    }

    /**
     * Column cb.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_cb($key)
    {
        return sprintf('<input type="checkbox" name="key[]" value="%1$s" />', $key['key_id']);
    }

    public function column_default($item, $column_name)
    {
        return $item[ $column_name ];
    }

    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * Get list columns.
     *
     * @return array
     */
    public function get_columns()
    {
        return [
            'cb'            => '<input type="checkbox" />',
            'app_key'       => __('App Key', 'aprio-rest-api'),
            'title'         => __('Description', 'aprio-rest-api'),
            'truncated_key' => __('Consumer key ending in', 'aprio-rest-api'),
            'user_id'       => __('User', 'aprio-rest-api'),
            'event_id'      => __('Event', 'aprio-rest-api'),
            'permissions'   => __('Permissions', 'aprio-rest-api'),
            'last_access'   => __('Last access', 'aprio-rest-api'),
        ];
    }

    /**
     * Return title column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_title($key)
    {
        $url     = admin_url('edit.php?post_type=event_listing&page=aprio-rest-api-settings&tab=api-access&edit-key=' . $key['key_id']);
        $user_id = intval($key['user_id']);

        // Check if current user can edit other users or if it's the same user.
        $can_edit = current_user_can('edit_user', $user_id) || get_current_user_id() === $user_id;

        $output = '<strong>';
        if ($can_edit) {
            $output .= '<a href="' . esc_url($url) . '" class="row-title">';
        }
        if (empty($key['description'])) {
            $output .= esc_html__('API key', 'aprio-rest-api');
        } else {
            $output .= esc_html($key['description']);
        }
        if ($can_edit) {
            $output .= '</a>';
        }
        $output .= '</strong>';

        // Get actions.
        $actions = [
            /* translators: %s: API key ID. */
            'id' => sprintf(__('ID: %d', 'aprio-rest-api'), $key['key_id']),
        ];

        if ($can_edit) {
            $actions['edit']  = '<a href="' . esc_url($url) . '">' . __('View/Edit', 'aprio-rest-api') . '</a>';
            $actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__('Revoke API key', 'aprio-rest-api') . '" href="' . esc_url(
                wp_nonce_url(
                    add_query_arg(
                        [
                            'revoke-key' => $key['key_id'],
                        ],
                        admin_url('edit.php?post_type=event_listing&page=aprio-rest-api-settings&tab=api-access')
                    ),
                    'revoke'
                )
            ) . '">' . esc_html__('Revoke', 'aprio-rest-api') . '</a>';
        }

        $row_actions = [];
        foreach ($actions as $action => $link) {
            $row_actions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }

        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    /**
     * Return truncated consumer key column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_truncated_key($key)
    {
        return '<code>&hellip;' . esc_html($key['truncated_key']) . '</code>';
    }

    /**
     * Return user column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_user_id($key)
    {
        $user = get_user_by('id', $key['user_id']);
        if (! $user) {
            return '';
        }

        if (current_user_can('edit_user', $user->ID)) {
            return '<a href="' . esc_url(add_query_arg([ 'user_id' => $user->ID ], admin_url('user-edit.php'))) . '">' . esc_html($user->display_name) . '</a>';
        }

        return esc_html($user->display_name);
    }

    /**
     * Return event column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_event_id($key)
    {
        if (! empty($key['event_id'])) {
            return '<a href="' . esc_url(admin_url('post.php?post=' . $key['event_id']) . '&action=edit') . '" />' . get_the_title($key['event_id']) . '</a>';
        }

        return;
    }
    /**
     * Return permissions column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_permissions($key)
    {
        $permission_key = $key['permissions'];
        $permissions    = [
            'read'       => __('Read', 'aprio-rest-api'),
            'write'      => __('Write', 'aprio-rest-api'),
            'read_write' => __('Read/Write', 'aprio-rest-api'),
        ];

        if (isset($permissions[ $permission_key ])) {
            return esc_html($permissions[ $permission_key ]);
        } else {
            return '';
        }
    }

    /**
     * Return last access column.
     *
     * @param  array $key Key data.
     * @return string
     */
    public function column_last_access($key)
    {
        if (! empty($key['last_access'])) {
            /* translators: 1: last access date 2: last access time */
            $date = sprintf(__('%1$s at %2$s', 'aprio-rest-api'), date_i18n('Y-m-d', strtotime($key['last_access'])), date_i18n('H:s:i', strtotime($key['last_access'])));

            return apply_filters('aprio_api_key_last_access_datetime', $date, $key['last_access']);
        }

        return __('Unknown', 'aprio-rest-api');
    }

    /**
     * Get bulk actions.
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        if (! current_user_can('remove_users')) {
            return [];
        }

        return [
            'revoke' => __('Revoke', 'aprio-rest-api'),
        ];
    }

    /**
     * Prepare table list items.
     */
    public function prepare_items()
    {
        global $wpdb;

        $per_page     = $this->get_items_per_page('10');
        $current_page = $this->get_pagenum();

        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        if (1 < $current_page) {
            $offset = $per_page * ($current_page - 1);
        } else {
            $offset = 0;
        }

        $search = '';

        if (! empty($_REQUEST['s'])) {
            // WPCS: input var okay, CSRF ok.
            $term = wp_unslash($_REQUEST['s']);
            // WPCS: input var okay, CSRF ok.
            $like = '%' . $wpdb->esc_like($term) . '%';

            // Find users matching by display name, username, or email.
            $user_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s",
                    $like,
                    $like,
                    $like
                )
            );

            // Only search by user (username/display name/email). If no users match, return no results.
            if (! empty($user_ids)) {
                $search = 'AND user_id IN (' . implode(',', array_map('absint', $user_ids)) . ') ';
            } else {
                $search = 'AND 1 = 0 ';
            }
        }//end if

        // Get the API keys.
        // SECURITY: $search is already safe (constructed with absint() on user IDs)
        // But we validate it to be extra safe
        $safe_search = '';
        if ( ! empty( $search ) ) {
            // Validate that search only contains safe SQL (AND clause with IN() of integers)
            // Pattern: "AND user_id IN (1,2,3) " or "AND 1 = 0 "
            if ( preg_match( '/^AND\s+(user_id\s+IN\s*\([0-9,\s]+\)|1\s*=\s*0)\s*$/', $search ) ) {
                $safe_search = $search;
            }
        }
        $table_name = $wpdb->prefix . 'aprio_rest_api_keys';
        $safe_table = $wpdb->_escape( $table_name ); // phpcs:ignore WordPress.DB.RestrictedFunctions.restricted_db_escape
        
        // Build query with safe search clause (cannot use %s for SQL clause in prepare)
        $query = "SELECT key_id, app_key, user_id, event_id, description, permissions, truncated_key, last_access FROM `{$safe_table}` WHERE 1 = 1 {$safe_search} ORDER BY key_id DESC LIMIT %d OFFSET %d";
        $keys = $wpdb->get_results(
            $wpdb->prepare( $query, $per_page, $offset ),
            ARRAY_A
        );

        $count_query = "SELECT COUNT(key_id) FROM `{$safe_table}` WHERE 1 = 1 {$safe_search}";
        $count = $wpdb->get_var( $count_query );
        $this->_column_headers = [ $columns, $hidden, $sortable ];
        $this->items           = $keys;

        // Set the pagination.
        $this->set_pagination_args(
            [
                'total_items' => $count,
                'per_page'    => $per_page,
                'total_pages' => ceil($count / $per_page),
            ]
        );
    }

    /**
     * Search box.
     *
     * @param string $text     Button text.
     * @param string $input_id Input ID.
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && ! $this->has_items()) {
            // WPCS: input var okay, CSRF ok.
            return;
        }

        $input_id     = $input_id . '-search-input';
        $search_query = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        // WPCS: input var okay, CSRF ok.

        echo '<div class="aprio-admin-body">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="' . esc_attr($input_id) . '">' . esc_html($text) . ':</label>';
        echo '<input type="search" id="' . esc_attr($input_id) . '" name="s" value="' . esc_attr($search_query) . '" />';
        submit_button(
            $text,
            '',
            '',
            false,
            [
                'id' => 'search-submit',
            ]
        );
        echo '</p>';
    }
}
