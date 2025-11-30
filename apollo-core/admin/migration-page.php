<?php
/**
 * Apollo Core - Migration Page
 * 
 * Handles migration from old Apollo plugins to Apollo Core
 * 
 * @package Apollo_Core
 * @version 1.0.0
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Register migration page
 */
function apollo_core_register_migration_page(): void {
    // Try to add as submenu of Apollo Core Hub, fallback to standalone page
    if (function_exists('apollo_core_register_hub_page')) {
        add_submenu_page(
            'apollo-core-hub',
            __('Migration', 'apollo-core'),
            __('Migration', 'apollo-core'),
            'manage_options',
            'apollo-core-migration',
            'apollo_core_render_migration_page'
        );
    } else {
        // Fallback: standalone page if hub doesn't exist
        add_menu_page(
            __('Apollo Core Migration', 'apollo-core'),
            __('Apollo Migration', 'apollo-core'),
            'manage_options',
            'apollo-core-migration',
            'apollo_core_render_migration_page',
            'dashicons-update',
            26
        );
    }
}
add_action('admin_menu', 'apollo_core_register_migration_page', 15);

/**
 * Handle migration action
 */
function apollo_core_handle_migration_action(): void {
    if (!isset($_GET['page']) || $_GET['page'] !== 'apollo-core-migration') {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action.', 'apollo-core'));
    }
    
    if (isset($_POST['apollo_run_migration']) && check_admin_referer('apollo_migration_action')) {
        require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-migration.php';
        $result = Apollo_Core_Migration::run();
        
        if ($result['success']) {
            add_settings_error(
                'apollo_migration',
                'migration_success',
                sprintf(
                    __('Migration completed successfully! %d options and %d meta keys migrated.', 'apollo-core'),
                    $result['results']['options_migrated'],
                    $result['results']['meta_migrated']
                ),
                'success'
            );
        } else {
            add_settings_error(
                'apollo_migration',
                'migration_error',
                $result['message'] ?? __('Migration failed.', 'apollo-core'),
                'error'
            );
        }
    }
    
    if (isset($_POST['apollo_rollback_migration']) && check_admin_referer('apollo_migration_action')) {
        require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-migration.php';
        $result = Apollo_Core_Migration::rollback();
        
        if ($result) {
            add_settings_error(
                'apollo_migration',
                'rollback_success',
                __('Migration rolled back successfully.', 'apollo-core'),
                'success'
            );
        } else {
            add_settings_error(
                'apollo_migration',
                'rollback_error',
                __('Rollback failed. No backup found.', 'apollo-core'),
                'error'
            );
        }
    }
}
add_action('admin_init', 'apollo_core_handle_migration_action');

/**
 * Render migration page
 */
function apollo_core_render_migration_page(): void {
    require_once APOLLO_CORE_PLUGIN_DIR . 'includes/class-migration.php';
    
    $migration_completed = get_option('apollo_core_migration_completed', false);
    $migration_version = get_option('apollo_core_migration_version', '0');
    $migration_date = get_option('apollo_core_migration_date', '');
    
    // Check for old plugin options
    $has_old_options = false;
    $old_options = array(
        'apollo_events_version',
        'apollo_social_version',
        'apollo_rio_version',
        'apollo_events_settings',
        'apollo_social_settings',
        'apollo_canvas_pages_created',
    );
    
    foreach ($old_options as $option) {
        if (get_option($option) !== false) {
            $has_old_options = true;
            break;
        }
    }
    
    settings_errors('apollo_migration');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Apollo Core Migration', 'apollo-core'); ?></h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('What is Migration?', 'apollo-core'); ?></h2>
            <p>
                <?php esc_html_e('Migration transfers data from old Apollo plugins (apollo-events-manager, apollo-social, apollo-rio) to the unified Apollo Core plugin.', 'apollo-core'); ?>
            </p>
            <p>
                <?php esc_html_e('This process will:', 'apollo-core'); ?>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php esc_html_e('Migrate plugin options and settings', 'apollo-core'); ?></li>
                <li><?php esc_html_e('Migrate post meta keys (e.g., canvas page markers)', 'apollo-core'); ?></li>
                <li><?php esc_html_e('Create a backup before making changes', 'apollo-core'); ?></li>
                <li><?php esc_html_e('Mark migration as completed to prevent duplicate runs', 'apollo-core'); ?></li>
            </ul>
        </div>
        
        <?php if ($migration_completed) : ?>
            <div class="notice notice-success" style="margin-top: 20px;">
                <p>
                    <strong><?php esc_html_e('Migration Status:', 'apollo-core'); ?></strong>
                    <?php esc_html_e('Completed', 'apollo-core'); ?>
                    <?php if ($migration_date) : ?>
                        (<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($migration_date))); ?>)
                    <?php endif; ?>
                </p>
                <p>
                    <?php esc_html_e('Migration version:', 'apollo-core'); ?> 
                    <code><?php echo esc_html($migration_version); ?></code>
                </p>
            </div>
        <?php elseif ($has_old_options) : ?>
            <div class="notice notice-warning" style="margin-top: 20px;">
                <p>
                    <strong><?php esc_html_e('Migration Available', 'apollo-core'); ?></strong>
                </p>
                <p>
                    <?php esc_html_e('Old plugin options detected. Run migration to transfer data to Apollo Core.', 'apollo-core'); ?>
                </p>
            </div>
        <?php else : ?>
            <div class="notice notice-info" style="margin-top: 20px;">
                <p>
                    <?php esc_html_e('No old plugin data detected. Migration is not needed.', 'apollo-core'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('Migration Actions', 'apollo-core'); ?></h2>
            
            <?php if (!$migration_completed && $has_old_options) : ?>
                <form method="post" action="">
                    <?php wp_nonce_field('apollo_migration_action'); ?>
                    <p>
                        <?php esc_html_e('Click the button below to start the migration process.', 'apollo-core'); ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e('Important:', 'apollo-core'); ?></strong>
                        <?php esc_html_e('A backup will be created automatically before migration. You can rollback if needed.', 'apollo-core'); ?>
                    </p>
                    <p>
                        <button type="submit" name="apollo_run_migration" class="button button-primary button-large">
                            <?php esc_html_e('Run Migration', 'apollo-core'); ?>
                        </button>
                    </p>
                </form>
            <?php elseif ($migration_completed) : ?>
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('apollo_migration_action'); ?>
                    <p>
                        <?php esc_html_e('Migration has been completed. If you need to rollback, use the button below.', 'apollo-core'); ?>
                    </p>
                    <p>
                        <button type="submit" name="apollo_rollback_migration" class="button button-secondary" 
                                onclick="return confirm('<?php esc_attr_e('Are you sure you want to rollback the migration?', 'apollo-core'); ?>');">
                            <?php esc_html_e('Rollback Migration', 'apollo-core'); ?>
                        </button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php esc_html_e('What Gets Migrated?', 'apollo-core'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Old Option', 'apollo-core'); ?></th>
                        <th><?php esc_html_e('New Option', 'apollo-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>apollo_events_version</code></td>
                        <td><code>apollo_core_version</code></td>
                    </tr>
                    <tr>
                        <td><code>apollo_social_version</code></td>
                        <td><code>apollo_core_version</code></td>
                    </tr>
                    <tr>
                        <td><code>apollo_rio_version</code></td>
                        <td><code>apollo_core_version</code></td>
                    </tr>
                    <tr>
                        <td><code>apollo_events_settings</code></td>
                        <td><code>apollo_mod_settings</code></td>
                    </tr>
                    <tr>
                        <td><code>apollo_social_settings</code></td>
                        <td><code>apollo_mod_settings</code></td>
                    </tr>
                    <tr>
                        <td><code>apollo_canvas_pages_created</code></td>
                        <td><code>apollo_core_canvas_pages_created</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 20px;"><?php esc_html_e('Meta Keys', 'apollo-core'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Old Meta Key', 'apollo-core'); ?></th>
                        <th><?php esc_html_e('New Meta Key', 'apollo-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>_apollo_canvas_page</code></td>
                        <td><code>_apollo_canvas</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

