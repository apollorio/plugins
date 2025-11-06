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
            'contacts' => $this->getSampleContacts()
        ];

        $args = wp_parse_args($args, $defaults);

        // Include the template
        include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/dj-contacts-table.php';
    }

    /**
     * Get sample contacts data (replace with real data source)
     */
    private function getSampleContacts(): array
    {
        return [
            [
                'name' => 'Robert Fox',
                'role' => 'DJ/Producer',
                'email' => 'robert.fox@example.com',
                'phone' => '202-555-0152',
                'score' => 7,
                'platform' => 'SoundCloud',
                'avatar' => 'https://images.unsplash.com/photo-1502823403499-6ccfcf4fb453?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80',
                'profile_url' => '#',
                'message_url' => '#',
                'platform_url' => '#'
            ],
            [
                'name' => 'Darlene Robertson',
                'role' => 'Event Promoter',
                'email' => 'darlene@example.com',
                'phone' => '224-567-2662',
                'score' => 5,
                'platform' => 'Instagram',
                'avatar' => 'https://images.unsplash.com/photo-1610271340738-726e199f0258?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80',
                'profile_url' => '#',
                'message_url' => '#',
                'platform_url' => '#'
            ],
            [
                'name' => 'Theresa Webb',
                'role' => 'Club Manager',
                'email' => 'theresa@example.com',
                'phone' => '401-505-6800',
                'score' => 2,
                'platform' => 'Facebook',
                'avatar' => 'https://images.unsplash.com/photo-1610878722345-79c5eaf6a48c?ixlib=rb-4.0.3&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80',
                'profile_url' => '#',
                'message_url' => '#',
                'platform_url' => '#'
            ]
        ];
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