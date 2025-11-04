<?php
/**
 * Admin Settings for Apollo PWA
 */

if (!defined('ABSPATH')) exit;

function apollo_add_admin_menu() {
    add_options_page(
        'Apollo::Rio Settings',
        'Apollo::Rio',
        'manage_options',
        'apollo-settings',
        'apollo_settings_page'
    );
}
add_action('admin_menu', 'apollo_add_admin_menu');

function apollo_settings_init() {
    register_setting('apollo_settings', 'apollo_android_app_url');
    register_setting('apollo_settings', 'apollo_pwa_install_page_id');
    
    add_settings_section(
        'apollo_settings_section',
        __('PWA Configuration', 'apollo-rio'),
        'apollo_settings_section_callback',
        'apollo_settings'
    );
    
    add_settings_field(
        'apollo_android_app_url',
        __('Android App URL', 'apollo-rio'),
        'apollo_android_app_url_render',
        'apollo_settings',
        'apollo_settings_section'
    );
}
add_action('admin_init', 'apollo_settings_init');

function apollo_settings_section_callback() {
    echo __('Configure PWA and app download settings', 'apollo-rio');
}

function apollo_android_app_url_render() {
    $value = get_option('apollo_android_app_url', '');
    ?>
    <input type="url" name="apollo_android_app_url" value="<?php echo esc_attr($value); ?>" style="width: 400px;">
    <p class="description"><?php _e('Google Play Store URL for Android app', 'apollo-rio'); ?></p>
    <?php
}

function apollo_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Apollo::Rio Settings', 'apollo-rio'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('apollo_settings');
            do_settings_sections('apollo_settings');
            submit_button();
            ?>
        </form>
        
        <hr>
        
        <h2><?php _e('Page Builder Guide', 'apollo-rio'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Template', 'apollo-rio'); ?></th>
                    <th><?php _e('Header/Footer', 'apollo-rio'); ?></th>
                    <th><?php _e('Desktop', 'apollo-rio'); ?></th>
                    <th><?php _e('Mobile Browser', 'apollo-rio'); ?></th>
                    <th><?php _e('Mobile PWA', 'apollo-rio'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Site::rio</strong></td>
                    <td>Full (with nav)</td>
                    <td>✅ Content</td>
                    <td>✅ Content</td>
                    <td>✅ Content</td>
                </tr>
                <tr>
                    <td><strong>App::rio</strong></td>
                    <td>Full (with nav)</td>
                    <td>✅ Content</td>
                    <td>⚠️ Install Page</td>
                    <td>✅ Content</td>
                </tr>
                <tr>
                    <td><strong>App::rio clean</strong></td>
                    <td>Minimal (no nav)</td>
                    <td>✅ Content</td>
                    <td>⚠️ Install Page</td>
                    <td>✅ Content</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}