<?php

if (!defined('ABSPATH')) exit;

function aem_submit_event_shortcode() {
    if (!is_user_logged_in()) {
        $url = site_url('/membership/');
        return '<div class="aem-auth-required">
            <p>Log in to submit an event.</p>
            <a class="btn" href="' . esc_url($url) . '">Sign in / Register</a>
        </div>';
    }

    if (!empty($_POST['aem_submit']) && check_admin_referer('apollo_submit_event', 'aem_nonce')) {
        $title   = sanitize_text_field($_POST['post_title'] ?? '');
        $content = wp_kses_post($_POST['post_content'] ?? '');
        $dj_ids  = array_map('intval', $_POST['dj_ids'] ?? []);
        $locals  = array_map('intval', $_POST['local_ids'] ?? []);

        if ($title) {
            $post_id = wp_insert_post([
                'post_type'   => 'event_listing',
                'post_status' => 'pending',
                'post_title'  => $title,
                'post_content'=> $content,
                'post_author' => get_current_user_id(),
            ]);

            if (!is_wp_error($post_id) && $post_id) {
                update_post_meta($post_id, '_event_dj_ids', $dj_ids);
                update_post_meta($post_id, '_event_local_ids', $locals);

                if (!empty($_FILES['event_banner']['name'])) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $upload = wp_handle_upload($_FILES['event_banner'], ['test_form' => false]);

                    if (empty($upload['error'])) {
                        $att_id = wp_insert_attachment([
                            'post_mime_type' => $upload['type'],
                            'post_title'     => basename($upload['file']),
                            'post_status'    => 'inherit',
                            'post_parent'    => $post_id
                        ], $upload['file'], $post_id);

                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        $attach_data = wp_generate_attachment_metadata($att_id, $upload['file']);
                        wp_update_attachment_metadata($att_id, $attach_data);
                        set_post_thumbnail($post_id, $att_id);
                    }
                }

                if (function_exists('aem_events_transient_key')) {
                    delete_transient(aem_events_transient_key());
                }

                return '<div class="aem-success">Event submitted. Awaiting approval.</div>';
            }
        }

        return '<div class="aem-error">Submit failed. Check required fields.</div>';
    }

    ob_start(); ?>
    <form class="aem-submit-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('apollo_submit_event', 'aem_nonce'); ?>
        <label>Event Title</label>
        <input type="text" name="post_title" required>

        <label>Description</label>
        <textarea name="post_content" rows="6"></textarea>

        <label>Banner</label>
        <input type="file" name="event_banner" accept="image/*">

        <label>DJ IDs (comma separated)</label>
        <input type="text" name="dj_ids_raw" placeholder="12,34,56">
        <input type="hidden" name="dj_ids[]" value="">

        <label>Local ID</label>
        <input type="number" name="local_ids[]" min="1" step="1">

        <button type="submit" name="aem_submit" value="1">Submit event</button>
    </form>

    <script>
    document.querySelector('.aem-submit-form')?.addEventListener('submit', e => {
        const raw = e.target.querySelector('[name="dj_ids_raw"]')?.value || '';
        const existing = e.target.querySelectorAll('input[name="dj_ids[]"]');
        existing.forEach((n, i) => i > 0 && n.remove());
        raw.split(',').map(s => parseInt(s.trim(), 10)).filter(Boolean).forEach(v => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'dj_ids[]'; i.value = String(v);
            e.target.appendChild(i);
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

add_shortcode('submit_event_form', 'aem_submit_event_shortcode');

