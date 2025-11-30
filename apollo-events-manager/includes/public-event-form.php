<?php
/**
 * Apollo Events Manager - Public Event Submission Form
 * 
 * Landing hero section form for public event submissions
 * 
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render public event submission form
 * 
 * @param array $atts Shortcode attributes
 * @return string Form HTML
 */
function apollo_render_public_event_form($atts = array()) {
    $atts = shortcode_atts(array(
        'show_title' => true,
        'title' => __('Submit Your Event', 'apollo-events-manager'),
    ), $atts);
    
    // Handle form submission
    $submitted = false;
    $error_message = '';
    
    $submitted_nonce = isset($_POST['apollo_event_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['apollo_event_nonce']))
        : '';

    if (isset($_POST['apollo_submit_event']) && wp_verify_nonce($submitted_nonce, 'apollo_public_event')) {
        $submitted = apollo_process_public_event_submission();
        if (is_wp_error($submitted)) {
            $error_message = $submitted->get_error_message();
            $submitted = false;
        }
    }
    
    ob_start();
    ?>
    <div class="apollo-public-event-form-wrapper">
        <?php if ($atts['show_title']): ?>
            <h2 class="apollo-form-title"><?php echo esc_html($atts['title']); ?></h2>
        <?php endif; ?>
        
        <?php if ($submitted): ?>
            <div class="apollo-alert apollo-alert-success">
                <i class="ri-checkbox-circle-line"></i>
                <p>
                    <?php esc_html_e('O evento foi registrado internamente! Algum de nossos moderadores vão verificar a veracidade dos dados e já lançaremos aqui, vamos juntos?', 'apollo-events-manager'); ?>
                </p>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="apollo-alert apollo-alert-danger">
                    <i class="ri-error-warning-line"></i>
                    <p><?php echo esc_html($error_message); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" class="apollo-public-event-form" id="apolloPublicEventForm">
                <?php wp_nonce_field('apollo_public_event', 'apollo_event_nonce'); ?>
                
                <p class="apollo-form-helper">
                    <?php esc_html_e('Todos os envios ficam pendentes até revisão da equipe Apollo.', 'apollo-events-manager'); ?>
                </p>

                <div class="apollo-form-field">
                    <label for="event_date_start" class="apollo-field-label">
                        <i class="ri-calendar-event-line"></i>
                        <?php esc_html_e('Data do Evento', 'apollo-events-manager'); ?>
                    </label>
                    <input 
                        type="date" 
                        id="event_date_start" 
                        name="day_start" 
                        class="apollo-input" 
                        required 
                        min="<?php echo esc_attr(date('Y-m-d')); ?>"
                    />
                </div>
                
                <div class="apollo-form-field">
                    <label for="event_name" class="apollo-field-label">
                        <i class="ri-calendar-todo-line"></i>
                        <?php esc_html_e('Nome do Evento', 'apollo-events-manager'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="event_name" 
                        name="event_name" 
                        class="apollo-input" 
                        placeholder="<?php esc_attr_e('Nome do evento', 'apollo-events-manager'); ?>" 
                        required 
                    />
                </div>
                
                <div class="apollo-form-field">
                    <label for="local_write" class="apollo-field-label">
                        <i class="ri-map-pin-2-line"></i>
                        <?php esc_html_e('Local', 'apollo-events-manager'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="local_write" 
                        name="local_write" 
                        class="apollo-input" 
                        placeholder="<?php esc_attr_e('Local', 'apollo-events-manager'); ?>" 
                        required 
                    />
                </div>
                
                <div class="apollo-form-field">
                    <label for="url_tickets" class="apollo-field-label">
                        <i class="ri-ticket-line"></i>
                        <?php esc_html_e('URL de Ingressos', 'apollo-events-manager'); ?>
                    </label>
                    <input 
                        type="url" 
                        id="url_tickets" 
                        name="url_tickets" 
                        class="apollo-input" 
                        placeholder="https://..." 
                    />
                </div>
                
                <div class="apollo-form-field">
                    <label for="coupon_apollo" class="apollo-field-label">
                        <i class="ri-coupon-line"></i>
                        <?php esc_html_e('Cupom Apollo', 'apollo-events-manager'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="coupon_apollo" 
                        name="coupon_apollo" 
                        class="apollo-input" 
                        placeholder="<?php esc_attr_e('Ex: apollo25', 'apollo-events-manager'); ?>" 
                    />
                </div>
                
                <button type="submit" name="apollo_submit_event" class="apollo-btn apollo-btn-primary apollo-btn-block">
                    <i class="ri-add-circle-line"></i>
                    <?php esc_html_e('Incluir Evento', 'apollo-events-manager'); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <style>
    .apollo-public-event-form-wrapper {
        max-width: 600px;
        margin: 0 auto;
        padding: 30px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .apollo-form-title {
        margin: 0 0 24px 0;
        font-size: 1.75rem;
        font-weight: 700;
        text-align: center;
    }
    
    .apollo-public-event-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .apollo-form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .apollo-form-helper {
        margin: 0;
        font-size: 0.95rem;
        color: var(--text-secondary, #4a5568);
    }
    
    .apollo-field-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        color: var(--text-primary, #1a1a1a);
    }
    
    .apollo-field-label i {
        font-size: 1.1em;
        color: var(--text-secondary, #666);
    }
    
    .apollo-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }
    
    .apollo-input:focus {
        outline: none;
        border-color: var(--primary-color, #0078d4);
        box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.1);
    }
    
    .apollo-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    
    .apollo-btn-primary {
        background: var(--primary-color, #0078d4);
        color: #fff;
    }
    
    .apollo-btn-primary:hover {
        background: var(--primary-color-dark, #0063a3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 120, 212, 0.3);
    }
    
    .apollo-btn-block {
        width: 100%;
    }
    
    .apollo-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .apollo-alert i {
        font-size: 1.5em;
        flex-shrink: 0;
    }
    
    .apollo-alert-success {
        background: #f0fdf4;
        border: 2px solid #86efac;
        color: #166534;
    }
    
    .apollo-alert-success i {
        color: #22c55e;
    }
    
    .apollo-alert-danger {
        background: #fef2f2;
        border: 2px solid #fecaca;
        color: #991b1b;
    }
    
    .apollo-alert-danger i {
        color: #ef4444;
    }
    </style>
    
    <?php
    return ob_get_clean();
}

/**
 * Process public event submission
 * 
 * NOTE: This function may also be defined in admin-shortcodes-page.php
 * Using function_exists() to prevent redeclaration errors
 * 
 * @return bool|WP_Error True on success, WP_Error on failure
 */
if (!function_exists('apollo_process_public_event_submission')) {
function apollo_process_public_event_submission()
{
    if (empty($_POST['day_start']) || empty($_POST['event_name']) || empty($_POST['local_write'])) {
        return new WP_Error(
            'missing_fields',
            __('Please fill in all required fields.', 'apollo-events-manager')
        );
    }

    $date_start  = sanitize_text_field(wp_unslash($_POST['day_start']));
    $event_name  = sanitize_text_field(wp_unslash($_POST['event_name']));
    $local_write = sanitize_text_field(wp_unslash($_POST['local_write']));
    $url_tickets = !empty($_POST['url_tickets'])
        ? esc_url_raw(wp_unslash($_POST['url_tickets']))
        : '';
    $coupon_apollo = !empty($_POST['coupon_apollo'])
        ? sanitize_text_field(wp_unslash($_POST['coupon_apollo']))
        : '';

    $date_timestamp = strtotime($date_start);
    if (!$date_timestamp || $date_timestamp < strtotime('today')) {
        return new WP_Error(
            'invalid_date',
            __('Please select a valid future date.', 'apollo-events-manager')
        );
    }

    $current_user_id = get_current_user_id();
    $allow_guest = apply_filters('apollo_public_event_allow_guest_submission', true);

    if (!$current_user_id && !$allow_guest) {
        return new WP_Error(
            'login_required',
            __('Please log in to submit an event.', 'apollo-events-manager')
        );
    }

    $restricted_caps = $current_user_id
        && !current_user_can('edit_event_listings')
        && !current_user_can('publish_posts');

    if ($restricted_caps) {
        /**
         * Fired when a user submits an event without elevated capabilities.
         * Allows auditing or custom throttling while still accepting the entry.
         */
        do_action('apollo_public_event_limited_caps_submission', $current_user_id);
    }

    $default_author = (int) apply_filters(
        'apollo_public_event_default_author',
        (int) get_option('apollo_events_default_author', 1)
    );

    $post_author = $current_user_id ?: $default_author;

    $event_data = array(
        'post_title'   => $event_name,
        'post_content' => '',
        'post_status'  => 'pending',
        'post_type'    => 'event_listing',
        'post_author'  => $post_author,
    );
    
    $grant_caps = function ($allcaps, $caps, $args, $user) {
        if (empty($caps)) {
            return $allcaps;
        }

        $targets = array(
            'edit_event_listing',
            'edit_event_listings',
            'publish_event_listings',
            'edit_posts',
            'edit_post',
        );

        if (array_intersect($targets, $caps)) {
            foreach ($targets as $cap) {
                $allcaps[$cap] = true;
            }
        }

        return $allcaps;
    };

    add_filter('user_has_cap', $grant_caps, 10, 4);
    $event_id = wp_insert_post($event_data, true);
    remove_filter('user_has_cap', $grant_caps, 10);
    
    if (is_wp_error($event_id)) {
        return $event_id;
    }
    
    // Save meta fields
    update_post_meta($event_id, '_event_start_date', $date_start);
    update_post_meta($event_id, '_event_location', $local_write);

    // TODO(step-3): collect organizer contact details for reviewers.
    
    if ($url_tickets) {
        update_post_meta($event_id, '_tickets_ext', $url_tickets);
    }
    
    if ($coupon_apollo) {
        update_post_meta($event_id, '_cupom_ario', $coupon_apollo);
    }
    
    // Mark as public submission
    update_post_meta($event_id, '_apollo_public_submission', '1');
    update_post_meta($event_id, '_apollo_submission_date', current_time('mysql'));

    error_log(
        sprintf(
            '✅ Apollo: Public event submission #%d: "%s" on %s at %s',
            $event_id,
            $event_name,
            $date_start,
            $local_write
        )
    );
    
    // Send notification email to admin (optional)
    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail(
            $admin_email,
            sprintf(
                __('New Event Submission: %s', 'apollo-events-manager'),
                $event_name
            ),
            sprintf(
                __(
                    'A new event has been submitted for review:' .
                    "\n\nEvent: %s\nDate: %s\nLocation: %s\n\nView: %s",
                    'apollo-events-manager'
                ),
                $event_name,
                $date_start,
                $local_write,
                admin_url('post.php?post=' . $event_id . '&action=edit')
            )
        );
    }
    
    return true;
}
} // End if (!function_exists('apollo_process_public_event_submission'))

// Register shortcode
add_shortcode('apollo_public_event_form', 'apollo_render_public_event_form');

