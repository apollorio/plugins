<?php
/**
 * Template: DJ Card
 * Component-based card for DJ listings
 * Inspired by ShadCN UI components
 * 
 * Variables available:
 * - $dj_id
 * - $dj_name
 * - $dj_bio
 * - $dj_photo
 * - $dj_link
 * - $instagram
 * - $soundcloud
 * 
 * @package Apollo_Events_Manager
 */

defined('ABSPATH') || exit;

// Get additional data if not set
if (!isset($dj_id)) {
    $dj_id = get_the_ID();
}
if (!isset($dj_name)) {
    $dj_name = get_the_title($dj_id);
}
if (!isset($dj_photo)) {
    $dj_photo = get_the_post_thumbnail_url($dj_id, 'medium');
}
if (!isset($dj_link)) {
    $dj_link = get_permalink($dj_id);
}
if (!isset($instagram)) {
    $instagram = apollo_get_post_meta($dj_id, '_dj_instagram', true);
}
if (!isset($soundcloud)) {
    $soundcloud = apollo_get_post_meta($dj_id, '_dj_soundcloud', true);
}
if (!isset($dj_bio)) {
    $dj_bio = get_the_excerpt($dj_id);
}

// Get genres
$genres = wp_get_post_terms($dj_id, 'event_sounds', array('fields' => 'names'));
$genres_str = !empty($genres) && !is_wp_error($genres) ? implode(', ', array_slice($genres, 0, 3)) : '';

// Get upcoming events count
$upcoming_events = new WP_Query(array(
    'post_type' => 'event_listing',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => array(
        array(
            'key' => '_event_dj_ids',
            'value' => '"' . $dj_id . '"',
            'compare' => 'LIKE'
        ),
        array(
            'key' => '_event_start_date',
            'value' => current_time('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    )
));

// ✅ Error handling para WP_Query
$events_count = 0;
if (is_wp_error($upcoming_events)) {
    error_log('Apollo: WP_Query error em dj-card: ' . $upcoming_events->get_error_message());
    // Continuar com count = 0 se houver erro
} else {
    $events_count = $upcoming_events->found_posts;
    wp_reset_postdata();
}

?>

<div class="apollo-dj-card shadcn-card" data-dj-id="<?php echo absint($dj_id); ?>">
    <a href="<?php echo esc_url($dj_link); ?>" class="card-link">
        <div class="card-header">
            <?php if ($dj_photo): ?>
                <div class="dj-avatar">
                    <img src="<?php echo esc_url($dj_photo); ?>" 
                         alt="<?php echo esc_attr($dj_name); ?>"
                         loading="lazy">
                </div>
            <?php else: ?>
                <div class="dj-avatar placeholder">
                    <i class="ri-user-music-line"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($events_count > 0): ?>
                <div class="badge badge-primary">
                    <?php printf(_n('%d evento', '%d eventos', $events_count, 'apollo-events-manager'), $events_count); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-content">
            <h3 class="dj-name"><?php echo esc_html($dj_name); ?></h3>
            
            <?php if ($genres_str): ?>
                <p class="dj-genres">
                    <i class="ri-music-2-line"></i>
                    <?php echo esc_html($genres_str); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($dj_bio): ?>
                <p class="dj-bio"><?php echo esc_html(wp_trim_words($dj_bio, 15)); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card-footer">
            <?php if ($instagram || $soundcloud): ?>
                <div class="dj-social">
                    <?php if ($instagram): ?>
                        <a href="<?php echo esc_url($instagram); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="social-btn instagram"
                           onclick="event.stopPropagation();"
                           title="Instagram">
                            <i class="ri-instagram-line"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($soundcloud): ?>
                        <a href="<?php echo esc_url($soundcloud); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="social-btn soundcloud"
                           onclick="event.stopPropagation();"
                           title="SoundCloud">
                            <i class="ri-soundcloud-line"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <span class="view-profile">
                <?php esc_html_e('Ver Perfil', 'apollo-events-manager'); ?>
                <i class="ri-arrow-right-line"></i>
            </span>
        </div>
    </a>
</div>

<style>
/* ShadCN-inspired DJ Card Styles */
.apollo-dj-card.shadcn-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--apollo-card-bg, #ffffff);
    border: 1px solid var(--apollo-border, #e5e7eb);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
}

.apollo-dj-card.shadcn-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border-color: var(--apollo-primary, #3b82f6);
}

.apollo-dj-card .card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.apollo-dj-card .card-header {
    position: relative;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.apollo-dj-card .dj-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.apollo-dj-card .dj-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.apollo-dj-card .dj-avatar.placeholder {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.8);
}

.apollo-dj-card .badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    color: var(--apollo-primary, #3b82f6);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.apollo-dj-card .card-content {
    padding: 1.5rem;
    flex-grow: 1;
}

.apollo-dj-card .dj-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: var(--apollo-text, #1f2937);
    line-height: 1.4;
}

.apollo-dj-card .dj-genres {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--apollo-text-muted, #6b7280);
    margin: 0 0 0.75rem 0;
}

.apollo-dj-card .dj-bio {
    font-size: 0.875rem;
    color: var(--apollo-text-secondary, #4b5563);
    line-height: 1.6;
    margin: 0;
}

.apollo-dj-card .card-footer {
    padding: 1rem 1.5rem;
    background: var(--apollo-card-footer, #f9fafb);
    border-top: 1px solid var(--apollo-border, #e5e7eb);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.apollo-dj-card .dj-social {
    display: flex;
    gap: 0.5rem;
}

.apollo-dj-card .social-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    background: var(--apollo-bg, #ffffff);
    border: 1px solid var(--apollo-border, #e5e7eb);
    color: var(--apollo-text-muted, #6b7280);
    transition: all 0.2s;
    font-size: 1rem;
}

.apollo-dj-card .social-btn:hover {
    background: var(--apollo-primary, #3b82f6);
    border-color: var(--apollo-primary, #3b82f6);
    color: white;
    transform: scale(1.1);
}

.apollo-dj-card .view-profile {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--apollo-primary, #3b82f6);
    transition: gap 0.2s;
}

.apollo-dj-card:hover .view-profile {
    gap: 0.5rem;
}

/* Grid Layout */
.apollo-djs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

/* List Layout */
.apollo-djs-list .apollo-dj-card {
    display: flex;
    flex-direction: row;
}

.apollo-djs-list .apollo-dj-card .card-header {
    width: 200px;
    flex-shrink: 0;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .apollo-dj-card.shadcn-card {
        --apollo-card-bg: #1f2937;
        --apollo-border: #374151;
        --apollo-text: #f9fafb;
        --apollo-text-muted: #9ca3af;
        --apollo-text-secondary: #d1d5db;
        --apollo-card-footer: #111827;
        --apollo-bg: #1f2937;
    }
}

/* Responsive */
@media (max-width: 640px) {
    .apollo-djs-grid {
        grid-template-columns: 1fr;
    }
    
    .apollo-djs-list .apollo-dj-card {
        flex-direction: column;
    }
    
    .apollo-djs-list .apollo-dj-card .card-header {
        width: 100%;
    }
}
</style>
