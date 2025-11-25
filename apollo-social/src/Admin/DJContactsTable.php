<?php

namespace Apollo\Admin;

use Apollo\Application\Users\UserProfileRepository;

/**
 * DJ Contacts Table Handler
 * Manages the glassmorphism DJ contacts table for admin interface
 */
class DJContactsTable
{
    private UserProfileRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserProfileRepository();
        $this->init();
    }

    /**
     * Initialize the table handler
     */
    public function init(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register shortcode
        add_shortcode('apollo_dj_contacts', [$this, 'renderShortcode']);
    }

    /**
     * Enqueue table assets
     */
    public function enqueueAssets($hook = ''): void
    {
        // Only load on relevant pages
        if (!is_admin() && !in_array($hook, ['toplevel_page_apollo-dj-contacts', 'apollo_page_apollo-dj-contacts'])) {
            return;
        }

        $plugin_url = plugin_dir_url(__FILE__) . '../../../assets/';

        // Enqueue uni.css if not already loaded
        if (!wp_style_is('uni-css', 'enqueued')) {
            wp_enqueue_style(
                'uni-css',
                'https://assets.apollo.rio.br/uni.css',
                [],
                '1.0.0'
            );
        }

        // Enqueue Remixicon
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
            [],
            '4.7.0'
        );

        // Enqueue table styles
        wp_enqueue_style(
            'apollo-dj-contacts-table',
            $plugin_url . 'css/dj-contacts-table.css',
            ['uni-css'],
            '1.0.0'
        );
    }

    /**
     * Render the DJ contacts table
     */
    public function renderTable(array $args = []): void
    {
        $defaults = [
            'title' => __('DJ Contacts', 'apollo-social'),
            'contacts' => $this->getDJContacts()
        ];

        $args = wp_parse_args($args, $defaults);

        // Include the template
        include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/dj-contacts-table.php';
    }

    /**
     * Get DJ contacts from database (CPT event_dj)
     * 
     * @return array Array of DJ contact data
     */
    private function getDJContacts(): array
    {
        $contacts = [];
        
        // Query DJs from custom post type
        $djs = get_posts([
            'post_type'      => 'event_dj',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        
        foreach ($djs as $dj) {
            // Get DJ meta
            $email      = get_post_meta($dj->ID, '_dj_email', true);
            $phone      = get_post_meta($dj->ID, '_dj_phone', true);
            $soundcloud = get_post_meta($dj->ID, '_dj_soundcloud', true);
            $instagram  = get_post_meta($dj->ID, '_dj_instagram', true);
            $facebook   = get_post_meta($dj->ID, '_dj_facebook', true);
            
            // Determine platform
            $platform     = 'Website';
            $platform_url = get_permalink($dj->ID);
            
            if ($soundcloud) {
                $platform     = 'SoundCloud';
                $platform_url = $soundcloud;
            } elseif ($instagram) {
                $platform     = 'Instagram';
                $platform_url = 'https://instagram.com/' . ltrim($instagram, '@');
            } elseif ($facebook) {
                $platform     = 'Facebook';
                $platform_url = $facebook;
            }
            
            // Get avatar (featured image or default)
            $avatar = get_the_post_thumbnail_url($dj->ID, 'thumbnail');
            if (!$avatar) {
                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($dj->post_title) . '&size=256&background=6366f1&color=fff';
            }
            
            // Calculate engagement score (based on events count)
            $events_count = $this->getDJEventsCount($dj->ID);
            $score        = min(10, ceil($events_count / 5)); // 1 point per 5 events, max 10
            
            $contacts[] = [
                'name'         => $dj->post_title,
                'role'         => __('DJ/Producer', 'apollo-social'),
                'email'        => $email ?: __('No email', 'apollo-social'),
                'phone'        => $phone ?: __('No phone', 'apollo-social'),
                'score'        => $score,
                'platform'     => $platform,
                'avatar'       => $avatar,
                'profile_url'  => get_permalink($dj->ID),
                'message_url'  => $email ? 'mailto:' . $email : '#',
                'platform_url' => $platform_url
            ];
        }
        
        return $contacts;
    }
    
    /**
     * Get count of events a DJ has participated in
     * 
     * @param int $dj_id DJ post ID
     * @return int Number of events
     */
    private function getDJEventsCount(int $dj_id): int
    {
        $events = get_posts([
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_event_dj_ids',
                    'value'   => sprintf(':"%d";', $dj_id),
                    'compare' => 'LIKE'
                ]
            ],
            'fields'         => 'ids'
        ]);
        
        return count($events);
    }

    /**
     * Render shortcode
     */
    public function renderShortcode($atts): string
    {
        ob_start();
        $this->renderTable($atts);
        return ob_get_clean();
    }

    /**
     * Get score badge class
     */
    private function getScoreClass(int $score): string
    {
        if ($score >= 7) return 'success';
        if ($score >= 4) return 'warning';
        return 'danger';
    }
}