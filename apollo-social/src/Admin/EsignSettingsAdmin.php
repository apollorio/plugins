<?php
/**
 * E-signature Settings Admin
 * 
 * Admin settings page for DocuSeal e-signature integration.
 * Allows configuration of API credentials and base URL.
 * 
 * @package ApolloSocial\Admin
 * @since 1.0.0
 */

namespace ApolloSocial\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class EsignSettingsAdmin
{
    /**
     * Option names
     */
    const OPTION_ENABLE = 'apollo_esign_enable';
    const OPTION_API_KEY = 'apollo_esign_api_key';
    const OPTION_API_BASE_URL = 'apollo_esign_api_base_url';

    /**
     * Settings group name
     */
    const SETTINGS_GROUP = 'apollo_esign_settings';

    /**
     * Page slug
     */
    const PAGE_SLUG = 'apollo-social-esign-settings';

    /**
     * Default API base URL
     */
    const DEFAULT_API_BASE_URL = 'https://api.docuseal.com';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerAdminMenu'], 100);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this, 'displayValidationNotices']);
    }

    /**
     * Register admin submenu page
     *
     * @return void
     */
    public function registerAdminMenu(): void
    {
        // Add as submenu under Apollo Help (or as standalone if no parent exists)
        add_submenu_page(
            'apollo-help',                              // Parent slug
            __('Apollo – E-signature', 'apollo-social'), // Page title
            __('E-signature', 'apollo-social'),          // Menu title
            'manage_options',                            // Capability
            self::PAGE_SLUG,                             // Menu slug
            [$this, 'renderSettingsPage']                // Callback
        );
    }

    /**
     * Register settings using WordPress Settings API
     *
     * @return void
     */
    public function registerSettings(): void
    {
        // Register settings
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_ENABLE,
            [
                'type' => 'boolean',
                'sanitize_callback' => [$this, 'sanitizeCheckbox'],
                'default' => false,
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_API_KEY,
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_API_BASE_URL,
            [
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => self::DEFAULT_API_BASE_URL,
            ]
        );

        // Add settings section
        add_settings_section(
            'apollo_esign_main_section',
            __('DocuSeal Integration Settings', 'apollo-social'),
            [$this, 'renderSectionDescription'],
            self::PAGE_SLUG
        );

        // Add settings fields
        add_settings_field(
            'apollo_esign_enable_field',
            __('Enable Integration', 'apollo-social'),
            [$this, 'renderEnableField'],
            self::PAGE_SLUG,
            'apollo_esign_main_section'
        );

        add_settings_field(
            'apollo_esign_api_key_field',
            __('API Key', 'apollo-social'),
            [$this, 'renderApiKeyField'],
            self::PAGE_SLUG,
            'apollo_esign_main_section'
        );

        add_settings_field(
            'apollo_esign_api_base_url_field',
            __('API Base URL', 'apollo-social'),
            [$this, 'renderApiBaseUrlField'],
            self::PAGE_SLUG,
            'apollo_esign_main_section'
        );
    }

    /**
     * Sanitize checkbox value
     *
     * @param mixed $value The value to sanitize.
     * @return int 1 if checked, 0 if not.
     */
    public function sanitizeCheckbox($value): int
    {
        return $value ? 1 : 0;
    }

    /**
     * Render section description
     *
     * @return void
     */
    public function renderSectionDescription(): void
    {
        echo '<p>' . esc_html__(
            'Configure your DocuSeal API credentials to enable e-signature functionality. '
            . 'Get your API key from your DocuSeal dashboard.',
            'apollo-social'
        ) . '</p>';
        echo '<p><a href="https://www.docuseal.com/docs/api" target="_blank" rel="noopener">'
            . esc_html__('View DocuSeal API Documentation', 'apollo-social')
            . ' <span class="dashicons dashicons-external"></span></a></p>';
    }

    /**
     * Render enable checkbox field
     *
     * @return void
     */
    public function renderEnableField(): void
    {
        $enabled = get_option(self::OPTION_ENABLE, false);
        ?>
        <label for="<?php echo esc_attr(self::OPTION_ENABLE); ?>">
            <input
                type="checkbox"
                id="<?php echo esc_attr(self::OPTION_ENABLE); ?>"
                name="<?php echo esc_attr(self::OPTION_ENABLE); ?>"
                value="1"
                <?php checked(1, $enabled); ?>
            />
            <?php esc_html_e('Enable DocuSeal e-signature integration', 'apollo-social'); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
                'When enabled, users will be able to send documents for electronic signature.',
                'apollo-social'
            ); ?>
        </p>
        <?php
    }

    /**
     * Render API key field
     *
     * @return void
     */
    public function renderApiKeyField(): void
    {
        $apiKey = get_option(self::OPTION_API_KEY, '');
        ?>
        <input
            type="password"
            id="<?php echo esc_attr(self::OPTION_API_KEY); ?>"
            name="<?php echo esc_attr(self::OPTION_API_KEY); ?>"
            value="<?php echo esc_attr($apiKey); ?>"
            class="regular-text"
            autocomplete="off"
        />
        <p class="description">
            <?php esc_html_e(
                'Enter your DocuSeal API key. This is kept secure and never displayed.',
                'apollo-social'
            ); ?>
        </p>
        <?php
    }

    /**
     * Render API base URL field
     *
     * @return void
     */
    public function renderApiBaseUrlField(): void
    {
        $baseUrl = get_option(self::OPTION_API_BASE_URL, self::DEFAULT_API_BASE_URL);
        ?>
        <input
            type="url"
            id="<?php echo esc_attr(self::OPTION_API_BASE_URL); ?>"
            name="<?php echo esc_attr(self::OPTION_API_BASE_URL); ?>"
            value="<?php echo esc_attr($baseUrl); ?>"
            class="regular-text"
            placeholder="<?php echo esc_attr(self::DEFAULT_API_BASE_URL); ?>"
        />
        <p class="description">
            <?php esc_html_e(
                'DocuSeal API endpoint. Use the default unless you have a self-hosted instance.',
                'apollo-social'
            ); ?>
        </p>
        <?php
    }

    /**
     * Display validation notices if settings are incomplete
     *
     * @return void
     */
    public function displayValidationNotices(): void
    {
        // Only show on our settings page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, self::PAGE_SLUG) === false) {
            return;
        }

        $enabled = get_option(self::OPTION_ENABLE, false);
        $apiKey = get_option(self::OPTION_API_KEY, '');
        $baseUrl = get_option(self::OPTION_API_BASE_URL, '');

        if ($enabled && (empty($apiKey) || empty($baseUrl))) {
            $message = __('DocuSeal integration is enabled but configuration is incomplete. '
                . 'Please fill in both the API Key and API Base URL to use e-signature features.',
                'apollo-social'
            );
            
            printf(
                '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
                esc_html__('E-signature Configuration:', 'apollo-social'),
                esc_html($message)
            );
        }
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'apollo-social'));
        }
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-edit-page" style="font-size: 30px; margin-right: 10px;"></span>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Save Settings', 'apollo-social'));
                ?>
            </form>

            <hr />

            <div class="apollo-esign-info">
                <h2><?php esc_html_e('About DocuSeal Integration', 'apollo-social'); ?></h2>
                <p>
                    <?php esc_html_e(
                        'This integration allows you to send documents for electronic signature '
                        . 'directly from Apollo Social. Users can sign documents using DocuSeal\'s '
                        . 'secure e-signature platform.',
                        'apollo-social'
                    ); ?>
                </p>
                <h3><?php esc_html_e('Features:', 'apollo-social'); ?></h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php esc_html_e('Upload documents for signature', 'apollo-social'); ?></li>
                    <li><?php esc_html_e('Multiple signers support', 'apollo-social'); ?></li>
                    <li><?php esc_html_e('Audit trail and certificates', 'apollo-social'); ?></li>
                    <li><?php esc_html_e('Webhook notifications', 'apollo-social'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Check if e-signature integration is properly configured
     *
     * @return bool True if enabled and configured, false otherwise.
     */
    public static function isConfigured(): bool
    {
        $enabled = get_option(self::OPTION_ENABLE, false);
        $apiKey = get_option(self::OPTION_API_KEY, '');
        $baseUrl = get_option(self::OPTION_API_BASE_URL, '');

        return $enabled && !empty($apiKey) && !empty($baseUrl);
    }

    /**
     * Get API configuration
     *
     * @return array{enabled: bool, api_key: string, base_url: string}
     */
    public static function getConfig(): array
    {
        return [
            'enabled' => (bool) get_option(self::OPTION_ENABLE, false),
            'api_key' => get_option(self::OPTION_API_KEY, ''),
            'base_url' => get_option(self::OPTION_API_BASE_URL, self::DEFAULT_API_BASE_URL),
        ];
    }
}
