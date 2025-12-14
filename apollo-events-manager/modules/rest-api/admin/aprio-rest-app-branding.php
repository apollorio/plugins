<?php

// phpcs:ignoreFile
defined('ABSPATH') || exit;

/**
 * APRIO_Rest_APP_Branding class would be used for set app branding parameters like colors of app.
 */
class APRIO_Rest_APP_Branding
{
    /**
     * Initialize the API Keys admin actions.
     */
    public function __construct()
    {
        // Ajax
        add_action('wp_ajax_change_brighness_color', [ $this, 'change_brighness_color' ]);
        add_action('wp_ajax_save_app_branding', [ $this, 'save_app_branding' ]);
    }

    /**
     * Page output.
     */
    public static function page_output()
    {
        // Hide the save button.
        $GLOBALS['hide_save_button'] = true;
        wp_enqueue_script('aprio-rest-api-admin-js');

        $primary_color = ! empty(get_option('aprio_primary_color')) ? get_option('aprio_primary_color') : '#3366FF';
        $success_color = ! empty(get_option('aprio_success_color')) ? get_option('aprio_success_color') : '#77DD37';
        $info_color    = ! empty(get_option('aprio_info_color')) ? get_option('aprio_info_color') : '#42BCFF';
        $warning_color = ! empty(get_option('aprio_warning_color')) ? get_option('aprio_warning_color') : '#FCD837';
        $danger_color  = ! empty(get_option('aprio_danger_color')) ? get_option('aprio_danger_color') : '#FC4C20';

        $primary_dark_color = ! empty(get_option('aprio_primary_dark_color')) ? get_option('aprio_primary_dark_color') : '#3366FF';
        $success_dark_color = ! empty(get_option('aprio_success_dark_color')) ? get_option('aprio_success_dark_color') : '#77DD37';
        $info_dark_color    = ! empty(get_option('aprio_info_dark_color')) ? get_option('aprio_info_dark_color') : '#42BCFF';
        $warning_dark_color = ! empty(get_option('aprio_warning_dark_color')) ? get_option('aprio_warning_dark_color') : '#FCD837';
        $danger_dark_color  = ! empty(get_option('aprio_danger_dark_color')) ? get_option('aprio_danger_dark_color') : '#FC4C20';

        include __DIR__ . '/templates/html-app-branding.php';
    }

    /**
     * change brighness color
     *
     * @param  int $key_id API Key ID.
     * @return bool
     */
    public function change_brighness_color()
    {
        $output = '';
        if (isset($_REQUEST['color']) && ! empty($_REQUEST['color'])) {
            $color_code = sanitize_hex_color($_REQUEST['color']);

            for ($i = 1; $i < 10; $i++) {

                $brightness        = (1000 - $i * 100);
                $adjust_percentage = $i / 10;

                if ($brightness == 500) {
                    $adjust_percentage = 0;
                }

                $code = aprio_rest_api_color_brightness($color_code, $adjust_percentage);
                if ($i < 5) {
                    $output .= '<div class="aprio-color-pallet-wrapper"><div>' . $brightness . '</div> <div class="aprio-color-pallet" style="background-color:' . $code . '" data-color-code="' . $code . '"><span style="color:#fff">' . $code . '</span></div></div>';
                } else {
                    $output .= '<div class="aprio-color-pallet-wrapper"><div>' . $brightness . '</div> <div class="aprio-color-pallet" style="background-color:' . $code . '" data-color-code="' . $code . '"><span style="color:' . $code . '">' . $code . '</span></div></div>';
                }
            }
        }
        echo esc_attr($output);
        wp_die();
    }

    /**
     * save app branding tab data
     *
     * @param  int $key_id API Key ID.
     * @return bool
     */
    public function save_app_branding()
    {

        check_ajax_referer('save-api-branding', 'security');

        // normal colors
        if (isset($_POST['aprio_primary_color'])) {
            update_option('aprio_primary_color', sanitize_hex_color($_POST['aprio_primary_color']));
        }

        if (isset($_POST['aprio_success_color'])) {
            update_option('aprio_success_color', sanitize_hex_color($_POST['aprio_success_color']));
        }

        if (isset($_POST['aprio_info_color'])) {
            update_option('aprio_info_color', sanitize_hex_color($_POST['aprio_info_color']));
        }

        if (isset($_POST['aprio_warning_color'])) {
            update_option('aprio_warning_color', sanitize_hex_color($_POST['aprio_warning_color']));
        }

        if (isset($_POST['aprio_danger_color'])) {
            update_option('aprio_danger_color', sanitize_hex_color($_POST['aprio_danger_color']));
        }

        $primary_color = ! empty(get_option('aprio_primary_color')) ? get_option('aprio_primary_color') : '#3366FF';
        $success_color = ! empty(get_option('aprio_success_color')) ? get_option('aprio_success_color') : '#77DD37';
        $info_color    = ! empty(get_option('aprio_info_color')) ? get_option('aprio_info_color') : '#42BCFF';
        $warning_color = ! empty(get_option('aprio_warning_color')) ? get_option('aprio_warning_color') : '#FCD837';
        $danger_color  = ! empty(get_option('aprio_danger_color')) ? get_option('aprio_danger_color') : '#FC4C20';

        // dark mode colors
        if (isset($_POST['aprio_primary_dark_color'])) {
            update_option('aprio_primary_dark_color', sanitize_hex_color($_POST['aprio_primary_dark_color']));
        }

        if (isset($_POST['aprio_success_dark_color'])) {
            update_option('aprio_success_dark_color', sanitize_hex_color($_POST['aprio_success_dark_color']));
        }

        if (isset($_POST['aprio_info_dark_color'])) {
            update_option('aprio_info_dark_color', sanitize_hex_color($_POST['aprio_info_dark_color']));
        }

        if (isset($_POST['aprio_warning_dark_color'])) {
            update_option('aprio_warning_dark_color', sanitize_hex_color($_POST['aprio_warning_dark_color']));
        }

        if (isset($_POST['aprio_danger_dark_color'])) {
            update_option('aprio_danger_dark_color', sanitize_hex_color($_POST['aprio_danger_dark_color']));
        }
        $primary_dark_color = ! empty(get_option('aprio_primary_dark_color')) ? get_option('aprio_primary_dark_color') : '#3366FF';
        $success_dark_color = ! empty(get_option('aprio_success_dark_color')) ? get_option('aprio_success_dark_color') : '#77DD37';
        $info_dark_color    = ! empty(get_option('aprio_info_dark_color')) ? get_option('aprio_info_dark_color') : '#42BCFF';
        $warning_dark_color = ! empty(get_option('aprio_warning_dark_color')) ? get_option('aprio_warning_dark_color') : '#FCD837';
        $danger_dark_color  = ! empty(get_option('aprio_danger_dark_color')) ? get_option('aprio_danger_dark_color') : '#FC4C20';

        $aprio_colors = $this->generate_scheme_formatted_colorcodes($primary_color, $success_color, $info_color, $warning_color, $danger_color);

        $aprio_dark_colors = $this->generate_scheme_formatted_colorcodes($primary_dark_color, $success_dark_color, $info_dark_color, $warning_dark_color, $danger_dark_color);

        if (! empty($aprio_colors)) {
            ksort($aprio_colors);
            update_option('aprio_app_branding_settings', $aprio_colors);
        }

        if (! empty($aprio_dark_colors)) {
            ksort($aprio_dark_colors);
            update_option('aprio_app_branding_dark_settings', $aprio_dark_colors);
        }

        if (isset($_POST['active_mode'])) {
            update_option('aprio_active_mode', sanitize_text_field($_POST['active_mode']));
        }

        $response            = [];
        $response['message'] = __('Your preferred color for your app branding has been successfully saved.', 'aprio-rest-api');
        $response['mode']    = sanitize_text_field($_POST['active_mode']);

        wp_send_json_success($response);

        wp_die();
    }

    /**
     * This function is used to generate schema for color of app
     */
    public function generate_scheme_formatted_colorcodes($primary_color = '#3366FF', $success_color = '#77DD37', $info_color = '#42BCFF', $warning_color = '#FCD837', $danger_color = '#FC4C20')
    {

        $rgb_primary_color = aprio_rest_api_hex_to_rgb($primary_color);
        $rgb_success_color = aprio_rest_api_hex_to_rgb($success_color);
        $rgb_info_color    = aprio_rest_api_hex_to_rgb($info_color);
        $rgb_warning_color = aprio_rest_api_hex_to_rgb($warning_color);
        $rgb_danger_color  = aprio_rest_api_hex_to_rgb($danger_color);

        $default_rgb = 0.08;

        $aprio_colors = [];
        $data_color   = [];
        for ($i = 1; $i < 10; $i++) {
            $brightness        = $i * 100;
            $adjust_percentage = $i / 10;

            if ($brightness == 500) {
                $adjust_percentage = 9 / 10;
            }

            $aprio_colors[ 'color-primary-' . $brightness ] = aprio_rest_api_color_brightness($primary_color, (1 - $adjust_percentage));
            $aprio_colors[ 'color-success-' . $brightness ] = aprio_rest_api_color_brightness($success_color, (1 - $adjust_percentage));
            $aprio_colors[ 'color-info-' . $brightness ]    = aprio_rest_api_color_brightness($info_color, (1 - $adjust_percentage));
            $aprio_colors[ 'color-warning-' . $brightness ] = aprio_rest_api_color_brightness($warning_color, (1 - $adjust_percentage));
            $aprio_colors[ 'color-danger-' . $brightness ]  = aprio_rest_api_color_brightness($danger_color, (1 - $adjust_percentage));

            if ($brightness <= 600) {
                $aprio_colors[ 'color-primary-transparent-' . $brightness ] = 'rgba( ' . $rgb_primary_color['red'] . ', ' . $rgb_primary_color['green'] . ', ' . $rgb_primary_color['blue'] . ', ' . $i * $default_rgb . ' )';
                $aprio_colors[ 'color-success-transparent-' . $brightness ] = 'rgba( ' . $rgb_success_color['red'] . ', ' . $rgb_success_color['green'] . ', ' . $rgb_success_color['blue'] . ', ' . $i * $default_rgb . ' )';
                $aprio_colors[ 'color-info-transparent-' . $brightness ]    = 'rgba( ' . $rgb_info_color['red'] . ', ' . $rgb_info_color['green'] . ', ' . $rgb_info_color['blue'] . ', ' . $i          * $default_rgb . ' )';
                $aprio_colors[ 'color-warning-transparent-' . $brightness ] = 'rgba( ' . $rgb_warning_color['red'] . ', ' . $rgb_warning_color['green'] . ', ' . $rgb_warning_color['blue'] . ', ' . $i * $default_rgb . ' )';
                $aprio_colors[ 'color-danger-transparent-' . $brightness ]  = 'rgba( ' . $rgb_danger_color['red'] . ', ' . $rgb_danger_color['green'] . ', ' . $rgb_danger_color['blue'] . ', ' . $i    * $default_rgb . ' )';
            }
        }//end for

        return $aprio_colors;
    }
}
new APRIO_Rest_APP_Branding();
