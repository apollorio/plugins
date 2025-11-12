<?php
/**
 * Apollo Events Manager - Admin Meta Boxes
 * Enhanced event editing with correct CPT structure
 * 
 * CPTs: event_listing, event_dj, event_local
 * No organizer, no venue - only DJs and Local
 */

defined('ABSPATH') || exit;

class Apollo_Events_Admin_Metaboxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'register_metaboxes'));
        add_action('save_post_event_listing', array($this, 'save_metabox_data'), 20, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_apollo_add_new_dj', array($this, 'ajax_add_new_dj'));
        add_action('wp_ajax_apollo_add_new_local', array($this, 'ajax_add_new_local'));
    }
    
    /**
     * Register meta boxes
     */
    public function register_metaboxes() {
        add_meta_box(
            'apollo_event_details',
            __('Apollo Event Details', 'apollo-events-manager'),
            array($this, 'render_event_details_metabox'),
            'event_listing',
            'normal',
            'high'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type !== 'event_listing') {
            return;
        }
        
        wp_enqueue_style('apollo-admin-metabox', APOLLO_WPEM_URL . 'assets/admin-metabox.css', array(), APOLLO_WPEM_VERSION);
        
        wp_enqueue_script(
            'apollo-admin-metabox',
            APOLLO_WPEM_URL . 'assets/admin-metabox.js',
            array('jquery', 'jquery-ui-dialog'),
            APOLLO_WPEM_VERSION,
            true
        );
        
        wp_localize_script('apollo-admin-metabox', 'apolloAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apollo_admin_nonce'),
            'i18n' => array(
                'dj_exists' => __('DJ %s já está registrado com slug %s', 'apollo-events-manager'),
                'local_exists' => __('Local %s já está registrado com slug %s', 'apollo-events-manager'),
                'enter_name' => __('Por favor, digite um nome', 'apollo-events-manager'),
            )
        ));
        
        wp_enqueue_style('wp-jquery-ui-dialog');
    }
    
    /**
     * Render event details metabox
     */
    public function render_event_details_metabox($post) {
        wp_nonce_field('apollo_event_meta_save', 'apollo_event_meta_nonce');
        
        // Get current values
        $current_djs = get_post_meta($post->ID, '_event_dj_ids', true);
        $current_djs = maybe_unserialize($current_djs);
        $current_djs = is_array($current_djs) ? array_map('intval', $current_djs) : array();

        $current_local = get_post_meta($post->ID, '_event_local_ids', true);
        if (is_array($current_local)) {
            $current_local = array_values(array_filter(array_map('intval', $current_local)));
        } elseif (!empty($current_local)) {
            $current_local = array((int) $current_local);
        } else {
            $current_local = array();
        }

    $current_timetable_raw = get_post_meta($post->ID, '_event_timetable', true);
    $current_timetable     = apollo_sanitize_timetable($current_timetable_raw);
    $timetable_json        = !empty($current_timetable) ? wp_json_encode($current_timetable) : '';
        
        $event_video_url = get_post_meta($post->ID, '_event_video_url', true);
        
        ?>
        <div class="apollo-metabox-container">
            
            <!-- ===== DJS SECTION ===== -->
            <div class="apollo-field-group">
                <h3><?php _e('DJs e Line-up', 'apollo-events-manager'); ?></h3>
                
                <div class="apollo-field">
                    <label for="apollo_event_djs"><?php _e('DJs:', 'apollo-events-manager'); ?></label>
                    <div class="apollo-field-controls">
                        <select multiple="multiple" name="apollo_event_djs[]" id="apollo_event_djs" class="widefat" size="8">
                            <?php
                            $all_djs = get_posts(
                                array(
                                    'post_type'      => 'event_dj',
                                    'posts_per_page' => -1,
                                    'orderby'        => 'title',
                                    'order'          => 'ASC',
                                    'post_status'    => 'publish',
                                )
                            );
                            
                            foreach ($all_djs as $dj) {
                                $dj_name   = get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
                                $is_active = in_array($dj->ID, $current_djs, true)
                                    ? ' selected="selected"'
                                    : '';

                                printf(
                                    '<option value="%d"%s>%s</option>',
                                    $dj->ID,
                                    $is_active,
                                    esc_html($dj_name)
                                );
                            }
                            ?>
                        </select>
                        <button type="button" class="button button-secondary" id="apollo_add_new_dj">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Adicionar novo DJ', 'apollo-events-manager'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Segure Ctrl/Cmd para selecionar múltiplos DJs. Use o botão para adicionar um DJ novo ao banco de dados.', 'apollo-events-manager'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- TIMETABLE DYNAMIC ROWS -->
                <div class="apollo-field">
                    <label><?php _e('Timetable (Horários):', 'apollo-events-manager'); ?></label>
                    <div id="apollo_timetable_container">
                        <table class="widefat striped" id="apollo_timetable_table" style="display:none;">
                            <thead>
                                <tr>
                                    <th width="40%"><?php _e('DJ', 'apollo-events-manager'); ?></th>
                                    <th width="30%"><?php _e('Começa às', 'apollo-events-manager'); ?></th>
                                    <th width="30%"><?php _e('Termina às', 'apollo-events-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="apollo_timetable_rows">
                                <!-- Dynamic rows inserted by JS -->
                            </tbody>
                        </table>
                        <p id="apollo_timetable_empty" style="color:#999;padding:20px;background:#f9f9f9;border-radius:4px;">
                            <?php _e('Selecione DJs acima primeiro. Os horários serão ordenados automaticamente ao salvar.', 'apollo-events-manager'); ?>
                        </p>
                        <button type="button" class="button" id="apollo_refresh_timetable" style="margin-top:10px;">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Atualizar Timetable', 'apollo-events-manager'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Store timetable data as JSON -->
                <input type="hidden"
                    name="apollo_event_timetable"
                    id="apollo_event_timetable"
                    value="<?php echo esc_attr($timetable_json); ?>">
            </div>
            
            <!-- ===== LOCAL SECTION ===== -->
            <div class="apollo-field-group">
                <h3><?php _e('Local do Evento', 'apollo-events-manager'); ?></h3>
                
                <div class="apollo-field">
                    <label for="apollo_event_local"><?php _e('Local:', 'apollo-events-manager'); ?></label>
                    <div class="apollo-field-controls">
                        <select name="apollo_event_local" id="apollo_event_local" class="widefat">
                            <option value=""><?php _e('Selecione um local', 'apollo-events-manager'); ?></option>
                            <?php
                            $all_locals = get_posts(
                                array(
                                    'post_type'      => 'event_local',
                                    'posts_per_page' => -1,
                                    'orderby'        => 'title',
                                    'order'          => 'ASC',
                                    'post_status'    => 'publish',
                                )
                            );
                            
                            foreach ($all_locals as $local) {
                                $local_name = get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
                                $is_active  = in_array($local->ID, $current_local, true)
                                    ? ' selected="selected"'
                                    : '';

                                printf(
                                    '<option value="%d"%s>%s</option>',
                                    $local->ID,
                                    $is_active,
                                    esc_html($local_name)
                                );
                            }
                            ?>
                        </select>
                        <button type="button" class="button button-secondary" id="apollo_add_new_local">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Adicionar novo Local', 'apollo-events-manager'); ?>
                        </button>
                        <p class="description">
                            <?php _e('O local será geocodificado automaticamente ao salvar.', 'apollo-events-manager'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- ===== MEDIA SECTION ===== -->
            <div class="apollo-field-group">
                <h3><?php _e('Mídia', 'apollo-events-manager'); ?></h3>
                
                <div class="apollo-field">
                    <label for="apollo_event_video_url"><?php _e('Event Video URL:', 'apollo-events-manager'); ?></label>
                    <input 
                        type="url" 
                        name="apollo_event_video_url" 
                        id="apollo_event_video_url" 
                        class="widefat" 
                        placeholder="https://www.youtube.com/watch?v=..."
                        value="<?php echo esc_attr($event_video_url); ?>"
                    >
                    <p class="description">
                        <?php _e('YouTube, Vimeo ou outro vídeo promocional (será exibido no hero da página do evento)', 'apollo-events-manager'); ?>
                    </p>
                </div>
            </div>
            
        </div>
        
        <!-- ===== ADD NEW DJ DIALOG ===== -->
        <div id="apollo_add_dj_dialog" style="display:none;" title="<?php esc_attr_e('Adicionar novo DJ', 'apollo-events-manager'); ?>">
            <form id="apollo_add_dj_form">
                <p>
                    <label for="new_dj_name"><?php _e('Nome do DJ:', 'apollo-events-manager'); ?></label>
                    <input type="text" name="new_dj_name" id="new_dj_name" class="widefat" placeholder="<?php esc_attr_e('Ex: Marta Supernova', 'apollo-events-manager'); ?>">
                </p>
                <p class="description">
                    <?php _e('O sistema verificará automaticamente se o DJ já existe (ignorando maiúsculas/minúsculas)', 'apollo-events-manager'); ?>
                </p>
                <div id="apollo_dj_form_message" style="display:none;margin-top:10px;"></div>
            </form>
        </div>
        
        <!-- ===== ADD NEW LOCAL DIALOG ===== -->
        <div id="apollo_add_local_dialog" style="display:none;" title="<?php esc_attr_e('Adicionar novo Local', 'apollo-events-manager'); ?>">
            <form id="apollo_add_local_form">
                <p>
                    <label for="new_local_name"><?php _e('Nome do Local:', 'apollo-events-manager'); ?></label>
                    <input type="text" name="new_local_name" id="new_local_name" class="widefat" placeholder="<?php esc_attr_e('Ex: D-Edge', 'apollo-events-manager'); ?>">
                </p>
                <p>
                    <label for="new_local_address"><?php _e('Endereço:', 'apollo-events-manager'); ?></label>
                    <input type="text" name="new_local_address" id="new_local_address" class="widefat" placeholder="<?php esc_attr_e('Rua, número', 'apollo-events-manager'); ?>">
                </p>
                <p>
                    <label for="new_local_city"><?php _e('Cidade:', 'apollo-events-manager'); ?></label>
                    <input type="text" name="new_local_city" id="new_local_city" class="widefat" placeholder="<?php esc_attr_e('Ex: Rio de Janeiro', 'apollo-events-manager'); ?>">
                </p>
                <p class="description">
                    <?php _e('O sistema verificará duplicados e fará geocoding automático com OpenStreetMap', 'apollo-events-manager'); ?>
                </p>
                <div id="apollo_local_form_message" style="display:none;margin-top:10px;"></div>
            </form>
        </div>
        
        <?php
    }
    
    /**
     * AJAX: Add new DJ (with duplicate check)
     */
    public function ajax_add_new_dj() {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        if (empty($name)) {
            wp_send_json_error(__('Por favor, digite um nome', 'apollo-events-manager'));
        }
        
        // Normalize for comparison (case-insensitive)
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        
        // Check duplicates
        $existing = get_posts(array(
            'post_type' => 'event_dj',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($existing as $dj) {
            $existing_title = mb_strtolower(trim($dj->post_title), 'UTF-8');
            $existing_meta = mb_strtolower(trim(get_post_meta($dj->ID, '_dj_name', true)), 'UTF-8');
            
            if ($existing_title === $normalized || $existing_meta === $normalized) {
                wp_send_json_error(sprintf(
                    __('DJ %s já está registrado com slug %s', 'apollo-events-manager'),
                    $dj->post_title,
                    $dj->post_name
                ));
            }
        }
        
        // Create new DJ
        $new_dj_id = wp_insert_post(array(
            'post_type' => 'event_dj',
            'post_title' => $name,
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($new_dj_id)) {
            wp_send_json_error(__('Erro ao criar DJ', 'apollo-events-manager'));
        }
        
        // Save DJ name meta
        update_post_meta($new_dj_id, '_dj_name', $name);
        
        wp_send_json_success(array(
            'id' => $new_dj_id,
            'name' => $name,
            'slug' => get_post($new_dj_id)->post_name
        ));
    }
    
    /**
     * AJAX: Add new Local (with duplicate check)
     */
    public function ajax_add_new_local() {
        check_ajax_referer('apollo_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $address = sanitize_text_field($_POST['address'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        
        if (empty($name)) {
            wp_send_json_error(__('Por favor, digite um nome', 'apollo-events-manager'));
        }
        
        // Normalize for comparison
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        
        // Check duplicates
        $existing = get_posts(array(
            'post_type' => 'event_local',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($existing as $local) {
            $existing_title = mb_strtolower(trim($local->post_title), 'UTF-8');
            $existing_meta = mb_strtolower(trim(get_post_meta($local->ID, '_local_name', true)), 'UTF-8');
            
            if ($existing_title === $normalized || $existing_meta === $normalized) {
                wp_send_json_error(sprintf(
                    __('Local %s já está registrado com slug %s', 'apollo-events-manager'),
                    $local->post_title,
                    $local->post_name
                ));
            }
        }
        
        // Create new Local
        $new_local_id = wp_insert_post(array(
            'post_type' => 'event_local',
            'post_title' => $name,
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($new_local_id)) {
            wp_send_json_error(__('Erro ao criar Local', 'apollo-events-manager'));
        }
        
        // Save Local meta
        update_post_meta($new_local_id, '_local_name', $name);
        if ($address) update_post_meta($new_local_id, '_local_address', $address);
        if ($city) update_post_meta($new_local_id, '_local_city', $city);
        
        // Auto-geocode will trigger on save_post hook
        
        wp_send_json_success(array(
            'id' => $new_local_id,
            'name' => $name,
            'slug' => get_post($new_local_id)->post_name
        ));
    }
    
    /**
     * Save metabox data
     */
    public function save_metabox_data($post_id, $post) {
        // Security checks
        if (!isset($_POST['apollo_event_meta_nonce']) || !wp_verify_nonce($_POST['apollo_event_meta_nonce'], 'apollo_event_meta_save')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // ✅ Save DJs (WordPress handles serialization automatically)
        if (isset($_POST['apollo_event_djs']) && is_array($_POST['apollo_event_djs'])) {
            $dj_ids = array_values(
                array_filter(
                    array_map('intval', wp_unslash($_POST['apollo_event_djs']))
                )
            );

            if (!empty($dj_ids)) {
                update_post_meta($post_id, '_event_dj_ids', $dj_ids);
            } else {
                delete_post_meta($post_id, '_event_dj_ids');
            }
        } else {
            delete_post_meta($post_id, '_event_dj_ids');
        }

        // ✅ Save Local (as numeric IDs array)
        if (isset($_POST['apollo_event_local'])) {
            $local_ids = array_values(
                array_filter(
                    array_map('intval', (array) wp_unslash($_POST['apollo_event_local']))
                )
            );

            if (!empty($local_ids)) {
                update_post_meta($post_id, '_event_local_ids', $local_ids);
            } else {
                delete_post_meta($post_id, '_event_local_ids');
            }
        } else {
            delete_post_meta($post_id, '_event_local_ids');
        }

        // ✅ Save Timetable (from JSON string to sorted array)
        if (isset($_POST['apollo_event_timetable'])) {
            $clean_timetable = apollo_sanitize_timetable($_POST['apollo_event_timetable']);

            if (!empty($clean_timetable)) {
                update_post_meta($post_id, '_event_timetable', $clean_timetable);
            } else {
                delete_post_meta($post_id, '_event_timetable');
            }
        } else {
            delete_post_meta($post_id, '_event_timetable');
        }
        
        // ✅ Save Video URL
        if (!empty($_POST['apollo_event_video_url'])) {
            update_post_meta($post_id, '_event_video_url', esc_url_raw($_POST['apollo_event_video_url']));
        } else {
            delete_post_meta($post_id, '_event_video_url');
        }
    }
}

// Initialize only in admin
if (is_admin()) {
    new Apollo_Events_Admin_Metaboxes();
}




