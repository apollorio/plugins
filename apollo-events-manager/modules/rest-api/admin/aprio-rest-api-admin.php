<?php

// phpcs:ignoreFile
defined('ABSPATH') || exit;
/**
 * APRIO_Rest_API_Admin class used to create rest api tab in wp event manager plugin.
 */
class APRIO_Rest_API_Admin
{
    public $settings_page;

    /**
     * __construct function.
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function __construct()
    {

        include 'aprio-rest-api-settings.php';
        include 'aprio-rest-api-keys.php';
        include 'aprio-rest-app-branding.php';
        include 'aprio-rest-api-keys-table-list.php';

        $this->settings_page = new APRIO_Rest_API_Settings();

        // add actions
        add_action('admin_menu', [ $this, 'admin_menu' ], 10);
        add_action('admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ]);
        add_filter('event_manager_admin_screen_ids', [ $this, 'aprio_rest_api_add_admin_screen' ]);

        add_action('wp_ajax_save_rest_api_keys', [ $this, 'update_api_key' ]);
    }

    /**
     * admin_enqueue_scripts function.
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function admin_enqueue_scripts()
    {
        if (isset($_GET['page']) && $_GET['page'] == 'aprio-rest-api-settings') {

            wp_enqueue_media();

            wp_register_script('aprio-rest-api-admin-js', APRIO_REST_API_PLUGIN_URL . '/assets/js/admin.min.js', [ 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'wp-util', 'wp-color-picker' ], APRIO_REST_API_VERSION, true);
            wp_localize_script(
                'aprio-rest-api-admin-js',
                'aprio_rest_api_admin',
                [
                    'ajaxUrl'                 => admin_url('admin-ajax.php'),
                    'save_api_nonce'          => wp_create_nonce('save-api-key'),
                    'save_app_branding_nonce' => wp_create_nonce('save-api-branding'),
                ]
            );

            wp_enqueue_style('jquery-ui');
            wp_enqueue_style('jquery-ui-style', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-ui/jquery-ui.min.css', []);
        }
    }

    /**
     * admin_enqueue_scripts function.
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function aprio_rest_api_add_admin_screen($screen_ids)
    {
        $screen_ids[] = 'event_listing_page_aprio-rest-api-settings';

        return $screen_ids;
    }

    /**
     * admin_menu function.
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function admin_menu()
    {
        add_submenu_page('edit.php?post_type=event_listing', __('Rest API Settings', 'aprio-rest-api'), __('Rest API', 'aprio-rest-api'), 'manage_options', 'aprio-rest-api-settings', [ $this->settings_page, 'output' ]);
    }

    /**
     * Create/Update API key.
     *
     * @throws Exception On invalid or empty description, user, or permissions.
     */
    public function update_api_key()
    {
        ob_start();

        global $wpdb;

        check_ajax_referer('save-api-key', 'security');
        $response = [];

        try {
            if (empty($_POST['description'])) {
                throw new Exception(__('Description is missing.', 'aprio-rest-api'));
            }
            if (empty($_POST['user'])) {
                throw new Exception(__('User is missing.', 'aprio-rest-api'));
            }
            if (empty($_POST['permissions'])) {
                throw new Exception(__('Permissions is missing.', 'aprio-rest-api'));
            }

            $key_id            = isset($_POST['key_id']) ? absint($_POST['key_id']) : 0;
            $description       = sanitize_text_field(wp_unslash($_POST['description']));
            $permissions       = (in_array(wp_unslash($_POST['permissions']), [ 'read', 'write', 'read_write' ], true)) ? sanitize_text_field(wp_unslash($_POST['permissions'])) : 'read';
            $user_id           = absint($_POST['user']);
            $event_id          = ! empty($_POST['event_id']) ? absint($_POST['event_id']) : '';
            $date_expires      = ! empty($_POST['date_expires']) ? date('Y-m-d H:i:s', strtotime(str_replace('-', '/', $_POST['date_expires']))) : null;
            $restrict_check_in = isset($_POST['restrict_check_in']) ? sanitize_text_field($_POST['restrict_check_in']) : '';
            $event_show_by     = isset($_POST['event_show_by']) ? sanitize_text_field($_POST['event_show_by']) : 'loggedin';
            $select_events     = isset($_POST['select_events']) ? maybe_serialize(array_map('absint', $_POST['select_events'])) : maybe_serialize([]);
            $mobile_menu       = isset($_POST['mobile_menu']) ? array_map('sanitize_text_field', $_POST['mobile_menu']) : [];

            update_user_meta($user_id, '_mobile_menu', $mobile_menu);

            // Check if current user can edit other users.
            if ($user_id && ! current_user_can('edit_user', $user_id)) {
                if (get_current_user_id() !== $user_id) {
                    throw new Exception(__('You do not have permission to assign API Keys to the selected user.', 'aprio-rest-api'));
                }
            }

            if (0 < $key_id) {
                $data = [
                    'user_id'         => $user_id,
                    'description'     => $description,
                    'permissions'     => $permissions,
                    'event_id'        => $event_id,
                    'date_expires'    => $date_expires,
                    'event_show_by'   => $event_show_by,
                    'selected_events' => $select_events,
                ];

                $wpdb->update(
                    $wpdb->prefix . 'aprio_rest_api_keys',
                    $data,
                    [ 'key_id' => $key_id ],
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                    ],
                    [ '%d' ]
                );
                update_user_meta($user_id, '_restrict_check_in', $restrict_check_in);
                $response                    = $data;
                $response['consumer_key']    = '';
                $response['consumer_secret'] = '';
                $response['message']         = __('API Key updated successfully.', 'aprio-rest-api');
                $response['selected_events'] = maybe_unserialize($select_events);
            } else {
                $app_key         = wp_rand();
                $consumer_key    = 'ck_' . sha1(wp_rand());
                $consumer_secret = 'cs_' . sha1(wp_rand());

                $data = [
                    'user_id'         => $user_id,
                    'app_key'         => $app_key,
                    'description'     => $description,
                    'permissions'     => $permissions,
                    'event_id'        => $event_id,
                    'consumer_key'    => $consumer_key,
                    'consumer_secret' => $consumer_secret,
                    'truncated_key'   => substr($consumer_key, -7),
                    'date_created'    => current_time('mysql'),
                    'date_expires'    => $date_expires,
                    'event_show_by'   => $event_show_by,
                    'selected_events' => $select_events,
                ];
                $wpdb->insert(
                    $wpdb->prefix . 'aprio_rest_api_keys',
                    $data,
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ]
                );
                $key_id                      = $wpdb->insert_id;
                $response                    = $data;
                $response['consumer_key']    = $consumer_key;
                $response['consumer_secret'] = $consumer_secret;
                $response['app_key']         = $app_key;
                $response['message']         = __('API Key generated successfully. Make sure to copy your new keys now as the secret key will be hidden once you leave this page.', 'aprio-rest-api');
                $response['revoke_url']      = '<a class="aprio-backend-theme-button" href="' . esc_url(admin_url('edit.php?post_type=event_listing&page=aprio-rest-api-settings&tab=api-access')) . '">' . __('I have Copied the Keys', 'aprio-rest-api') . '</a> <br/><br/> <a class="aprio-backend-theme-button aprio-revoke-button" href="' . esc_url(wp_nonce_url(add_query_arg([ 'revoke-key' => $key_id ], admin_url('edit.php?post_type=event_listing&page=aprio-rest-api-settings&tab=api-access')), 'revoke')) . '">' . __('Revoke key', 'aprio-rest-api') . '</a>';
                $response['event_show_by']   = $event_show_by;
                $response['selected_events'] = maybe_unserialize($select_events);
            }//end if
        } catch (Exception $e) {
            wp_send_json_error([ 'message' => $e->getMessage() ]);
        }//end try
        // wp_send_json_success must be outside the try block not to break phpunit tests.
        wp_send_json_success($response);
    }
}
new APRIO_Rest_API_Admin();
