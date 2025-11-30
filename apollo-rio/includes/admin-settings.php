<?php
declare(strict_types=1);
/**
 * Admin Settings for Apollo PWA
 * 
 * @package Apollo_Rio
 * @since 1.0.0
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
    // Register settings with sanitization callbacks
    register_setting('apollo_settings', 'apollo_android_app_url', 'apollo_sanitize_android_app_url');
    
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
    esc_html_e('Configure PWA and app download settings', 'apollo-rio');
}

function apollo_android_app_url_render() {
    $value = get_option('apollo_android_app_url', '');
    ?>
    <input type="url" name="apollo_android_app_url" value="<?php echo esc_attr($value); ?>" style="width: 400px;">
    <p class="description"><?php esc_html_e('Google Play Store URL for Android app', 'apollo-rio'); ?></p>
    <?php
}

function apollo_sanitize_android_app_url($value) {
    $value = esc_url_raw($value);
    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        add_settings_error('apollo_android_app_url', 'invalid_url', __('Invalid URL format. Please enter a valid URL.', 'apollo-rio'));
        return get_option('apollo_android_app_url', '');
    }
    return $value;
}

function apollo_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Apollo::Rio Settings', 'apollo-rio'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('apollo_settings');
            do_settings_sections('apollo_settings');
            submit_button();
            ?>
        </form>
        
        <hr>
        
        <h2><?php esc_html_e('Como Associar Templates PWA às Páginas', 'apollo-rio'); ?></h2>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <ol style="line-height: 1.8;">
                <li>
                    <strong><?php esc_html_e('Edite a página desejada:', 'apollo-rio'); ?></strong>
                    <?php esc_html_e('Vá para Páginas → Todas as Páginas e clique em "Editar" na página que deseja configurar.', 'apollo-rio'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Selecione o template:', 'apollo-rio'); ?></strong>
                    <?php esc_html_e('Na coluna lateral direita, encontre o metabox "Atributos da Página" → "Template".', 'apollo-rio'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Escolha um dos templates disponíveis:', 'apollo-rio'); ?></strong>
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li><strong>Site::rio</strong> - <?php esc_html_e('Página pública, sempre mostra conteúdo', 'apollo-rio'); ?></li>
                        <li><strong>App::rio</strong> - <?php esc_html_e('Página do app com header completo', 'apollo-rio'); ?></li>
                        <li><strong>App::rio clean</strong> - <?php esc_html_e('Página do app sem navegação (minimal)', 'apollo-rio'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php esc_html_e('Salve a página:', 'apollo-rio'); ?></strong>
                    <?php esc_html_e('Clique em "Atualizar" para salvar as alterações.', 'apollo-rio'); ?>
                </li>
            </ol>
        </div>
        
        <h2><?php esc_html_e('Guia de Templates PWA', 'apollo-rio'); ?></h2>
        <table class="widefat" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="padding: 12px;"><?php esc_html_e('Template', 'apollo-rio'); ?></th>
                    <th style="padding: 12px;"><?php esc_html_e('Header/Footer', 'apollo-rio'); ?></th>
                    <th style="padding: 12px;"><?php esc_html_e('Desktop', 'apollo-rio'); ?></th>
                    <th style="padding: 12px;"><?php esc_html_e('Mobile Browser', 'apollo-rio'); ?></th>
                    <th style="padding: 12px;"><?php esc_html_e('Mobile PWA', 'apollo-rio'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Site::rio</strong></td>
                    <td><?php esc_html_e('Full (with nav)', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                </tr>
                <tr>
                    <td><strong>App::rio</strong></td>
                    <td><?php esc_html_e('Full (with nav)', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                    <td>⚠️ <?php esc_html_e('Install Page', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                </tr>
                <tr>
                    <td><strong>App::rio clean</strong></td>
                    <td><?php esc_html_e('Minimal (no nav)', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                    <td>⚠️ <?php esc_html_e('Install Page', 'apollo-rio'); ?></td>
                    <td>✅ <?php esc_html_e('Content', 'apollo-rio'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong><?php esc_html_e('Nota:', 'apollo-rio'); ?></strong>
                <?php esc_html_e('Os templates "App::rio" e "App::rio clean" mostram uma página de instalação do PWA quando acessados via navegador mobile. Quando instalado como PWA, o conteúdo é exibido normalmente.', 'apollo-rio'); ?>
            </p>
        </div>
    </div>
    <?php
}