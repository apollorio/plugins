<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * Adapter for BadgeOS plugin integration  
 * Provides seamless integration with BadgeOS for badges and gamification
 */
class BadgeOSAdapter {
    
    private $config;
    
    public function __construct() {
        $this->config = config('integrations.badgeos');
        
        if ($this->config['enabled'] ?? false) {
            $this->init_hooks();
        }
    }
    
    /**
     * Initialize WordPress hooks for BadgeOS integration
     */
    private function init_hooks() {
        // Achievement earning hooks
        add_action('badgeos_award_achievement', [$this, 'on_achievement_awarded'], 10, 5);
        add_action('badgeos_revoke_achievement', [$this, 'on_achievement_revoked'], 10, 3);
        
        // Point awarding hooks
        add_action('badgeos_update_users_points', [$this, 'on_points_updated'], 10, 6);
        add_action('badgeos_award_points', [$this, 'on_points_awarded'], 10, 4);
        add_action('badgeos_deduct_points', [$this, 'on_points_deducted'], 10, 4);
        
        // Apollo event listening
        add_action('apollo_award_points', [$this, 'award_apollo_points'], 10, 3);
        add_action('apollo_award_badge', [$this, 'award_apollo_badge'], 10, 3);
        
        // Activity triggers for Apollo events
        add_action('apollo_post_created', [$this, 'trigger_post_created'], 10, 2);
        add_action('apollo_event_approved', [$this, 'trigger_event_approved'], 10, 2);
        add_action('apollo_advert_approved', [$this, 'trigger_advert_approved'], 10, 2);
        add_action('apollo_document_signed', [$this, 'trigger_document_signed'], 10, 2);
        add_action('apollo_group_joined', [$this, 'trigger_group_joined'], 10, 2);
        add_action('apollo_invite_sent', [$this, 'trigger_invite_sent'], 10, 2);
        
        // Leaderboard integration
        if ($this->config['leaderboard_integration'] ?? false) {
            add_filter('badgeos_get_leaderboard', [$this, 'enhance_leaderboard'], 10, 3);
        }
        
        // Auto award if enabled
        if ($this->config['auto_award'] ?? false) {
            add_action('init', [$this, 'register_apollo_triggers'], 20);
        }
    }
    
    /**
     * Check if BadgeOS is active and available
     */
    public function is_available(): bool {
        return class_exists('BadgeOS') || function_exists('badgeos_award_achievement_to_user');
    }
    
    /**
     * Handle achievement awarded
     */
    public function on_achievement_awarded($user_id, $achievement_id, $earning_id, $trigger, $site_id) {
        $apollo_meta = get_post_meta($achievement_id, '_apollo_achievement_data', true);
        
        if ($apollo_meta) {
            do_action('apollo_achievement_earned', $user_id, $achievement_id, $apollo_meta, $earning_id);
        }
    }
    
    /**
     * Handle achievement revoked
     */
    public function on_achievement_revoked($user_id, $achievement_id, $earning_id) {
        $apollo_meta = get_post_meta($achievement_id, '_apollo_achievement_data', true);
        
        if ($apollo_meta) {
            do_action('apollo_achievement_revoked', $user_id, $achievement_id, $apollo_meta);
        }
    }
    
    /**
     * Handle points updated
     */
    public function on_points_updated($user_id, $new_points, $total_points, $admin_id, $achievement_id, $this_trigger) {
        do_action('apollo_points_updated', $user_id, $new_points, $total_points, [
            'admin_id' => $admin_id,
            'achievement_id' => $achievement_id,
            'trigger' => $this_trigger
        ]);
    }
    
    /**
     * Handle points awarded
     */
    public function on_points_awarded($user_id, $points, $admin_id, $achievement_id) {
        do_action('apollo_points_awarded', $user_id, $points, [
            'admin_id' => $admin_id,
            'achievement_id' => $achievement_id,
            'action' => 'award'
        ]);
    }
    
    /**
     * Handle points deducted
     */
    public function on_points_deducted($user_id, $points, $admin_id, $achievement_id) {
        do_action('apollo_points_deducted', $user_id, $points, [
            'admin_id' => $admin_id,
            'achievement_id' => $achievement_id,
            'action' => 'deduct'
        ]);
    }
    
    /**
     * Award points for Apollo events
     */
    public function award_apollo_points($user_id, $event_type, $data = []) {
        if (!$this->is_available()) return;
        
        $badges_config = config('badges');
        $event_config = $badges_config['events'][$event_type] ?? null;
        
        if (!$event_config || empty($event_config['points'])) return;
        
        $points = $event_config['points'];
        $reason = $event_config['description'] ?? "Apollo event: {$event_type}";
        
        // Award points using BadgeOS
        badgeos_award_points_to_user($user_id, $points, null, null, $reason);
        
        // Check for level advancement
        $this->check_level_advancement($user_id);
        
        do_action('apollo_badgeos_points_awarded', $user_id, $points, $event_type, $data);
    }
    
    /**
     * Award badge for Apollo events
     */
    public function award_apollo_badge($user_id, $event_type, $data = []) {
        if (!$this->is_available()) return;
        
        $badges_config = config('badges');
        $event_config = $badges_config['events'][$event_type] ?? null;
        
        if (!$event_config || empty($event_config['badge'])) return;
        
        $badge_slug = $event_config['badge'];
        $achievement_id = $this->get_achievement_by_slug($badge_slug);
        
        if ($achievement_id) {
            $this->award_achievement_to_user($user_id, $achievement_id, 'apollo_trigger', $data);
        }
    }
    
    /**
     * Trigger: Post created
     */
    public function trigger_post_created($post_id, $user_id) {
        if (!$this->is_available()) return;
        
        // Award points
        $this->award_apollo_points($user_id, 'post_created', ['post_id' => $post_id]);
        
        // Award badge
        $this->award_apollo_badge($user_id, 'post_created', ['post_id' => $post_id]);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_post_created', $user_id, $post_id);
    }
    
    /**
     * Trigger: Event approved
     */
    public function trigger_event_approved($post_id, $apollo_meta) {
        if (!$this->is_available()) return;
        
        $event = get_post($post_id);
        if (!$event) return;
        
        $user_id = $event->post_author;
        
        // Award points
        $this->award_apollo_points($user_id, 'event_approved', ['post_id' => $post_id, 'apollo_meta' => $apollo_meta]);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_event_approved', $user_id, $post_id, $apollo_meta);
    }
    
    /**
     * Trigger: Classified approved  
     */
    public function trigger_advert_approved($post_id, $apollo_meta) {
        if (!$this->is_available()) return;
        
        $advert = get_post($post_id);
        if (!$advert) return;
        
        $user_id = $advert->post_author;
        
        // Award points
        $this->award_apollo_points($user_id, 'classified_approved', ['post_id' => $post_id, 'apollo_meta' => $apollo_meta]);
        
        // Award badge
        $this->award_apollo_badge($user_id, 'classified_approved', ['post_id' => $post_id, 'apollo_meta' => $apollo_meta]);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_advert_approved', $user_id, $post_id, $apollo_meta);
    }
    
    /**
     * Trigger: Document signed
     */
    public function trigger_document_signed($document_id, $user_id) {
        if (!$this->is_available()) return;
        
        // Award points
        $this->award_apollo_points($user_id, 'document_signed', ['document_id' => $document_id]);
        
        // Award badge
        $this->award_apollo_badge($user_id, 'document_signed', ['document_id' => $document_id]);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_document_signed', $user_id, $document_id);
    }
    
    /**
     * Trigger: Group joined
     */
    public function trigger_group_joined($user_id, $group_data) {
        if (!$this->is_available()) return;
        
        // Award points
        $this->award_apollo_points($user_id, 'group_joined', $group_data);
        
        // Award badge
        $this->award_apollo_badge($user_id, 'group_joined', $group_data);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_group_joined', $user_id, $group_data);
    }
    
    /**
     * Trigger: Invite sent
     */
    public function trigger_invite_sent($user_id, $invite_data) {
        if (!$this->is_available()) return;
        
        // Award points
        $this->award_apollo_points($user_id, 'invite_sent', $invite_data);
        
        // Award badge
        $this->award_apollo_badge($user_id, 'invite_sent', $invite_data);
        
        // Custom BadgeOS trigger
        do_action('badgeos_apollo_invite_sent', $user_id, $invite_data);
    }
    
    /**
     * Register Apollo triggers with BadgeOS
     */
    public function register_apollo_triggers() {
        if (!$this->is_available()) return;
        
        // Register triggers for BadgeOS
        $apollo_triggers = [
            'apollo_post_created' => 'Post Criado no Apollo',
            'apollo_event_approved' => 'Evento Aprovado no Apollo',
            'apollo_advert_approved' => 'Anúncio Aprovado no Apollo',
            'apollo_document_signed' => 'Documento Assinado no Apollo',
            'apollo_group_joined' => 'Entrou em Grupo Apollo',
            'apollo_invite_sent' => 'Convite Enviado no Apollo'
        ];
        
        foreach ($apollo_triggers as $trigger => $label) {
            add_filter('badgeos_activity_triggers', function($triggers) use ($trigger, $label) {
                $triggers[$trigger] = $label;
                return $triggers;
            });
        }
    }
    
    /**
     * Enhance leaderboard with Apollo data
     */
    public function enhance_leaderboard($leaderboard, $type, $limit) {
        foreach ($leaderboard as &$entry) {
            $user_id = $entry['user_id'];
            
            // Add Apollo group info
            $groups_adapter = new GroupsAdapter();
            $apollo_groups = $groups_adapter->get_user_apollo_groups($user_id);
            $entry['apollo_groups'] = $apollo_groups;
            
            // Add Apollo level
            $entry['apollo_level'] = $this->get_user_apollo_level($user_id);
            
            // Add Apollo badges
            $entry['apollo_badges'] = $this->get_user_apollo_badges($user_id);
        }
        
        return $leaderboard;
    }
    
    /**
     * Create Apollo achievement/badge
     */
    public function create_apollo_achievement($achievement_data): ?int {
        if (!$this->is_available()) return null;
        
        $defaults = [
            'post_type' => badgeos_get_achievement_types_slugs()[0] ?? 'badge',
            'post_status' => 'publish',
            'meta_input' => [
                '_badgeos_earned_by' => 'triggers',
                '_apollo_achievement' => true
            ]
        ];
        
        $achievement_data = array_merge($defaults, $achievement_data);
        
        // Extract Apollo meta
        $apollo_meta = [];
        if (isset($achievement_data['apollo_data'])) {
            $apollo_meta = $achievement_data['apollo_data'];
            unset($achievement_data['apollo_data']);
        }
        
        // Create achievement
        $achievement_id = wp_insert_post($achievement_data);
        
        if ($achievement_id && !is_wp_error($achievement_id)) {
            // Save Apollo meta
            update_post_meta($achievement_id, '_apollo_achievement_data', $apollo_meta);
            
            do_action('apollo_achievement_created', $achievement_id, $apollo_meta);
            
            return $achievement_id;
        }
        
        return null;
    }
    
    /**
     * Award achievement to user
     */
    public function award_achievement_to_user($user_id, $achievement_id, $trigger = 'apollo_trigger', $data = []): bool {
        if (!$this->is_available()) return false;
        
        // Check if user already has this achievement
        if (badgeos_get_user_achievements(array('user_id' => $user_id, 'achievement_id' => $achievement_id))) {
            return false; // Already earned
        }
        
        // Award the achievement
        $earning_id = badgeos_award_achievement_to_user($achievement_id, $user_id, 'apollo_admin', $trigger);
        
        if ($earning_id) {
            // Store Apollo data with the earning
            update_post_meta($earning_id, '_apollo_earning_data', $data);
            
            do_action('apollo_badgeos_achievement_awarded', $user_id, $achievement_id, $earning_id, $data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user's total points
     */
    public function get_user_points($user_id, $point_type = null): int {
        if (!$this->is_available()) return 0;
        
        $point_types = $this->config['point_types'] ?? ['points'];
        
        if ($point_type && in_array($point_type, $point_types)) {
            return badgeos_get_users_points($user_id, $point_type);
        }
        
        // Get points from first configured type
        return badgeos_get_users_points($user_id, $point_types[0]);
    }
    
    /**
     * Get user's achievements
     */
    public function get_user_achievements($user_id, $achievement_type = null): array {
        if (!$this->is_available()) return [];
        
        $args = ['user_id' => $user_id];
        
        if ($achievement_type) {
            $args['achievement_type'] = $achievement_type;
        }
        
        return badgeos_get_user_achievements($args);
    }
    
    /**
     * Get user's Apollo badges
     */
    public function get_user_apollo_badges($user_id): array {
        $achievements = $this->get_user_achievements($user_id);
        $apollo_badges = [];
        
        foreach ($achievements as $achievement) {
            $is_apollo = get_post_meta($achievement->ID, '_apollo_achievement', true);
            if ($is_apollo) {
                $apollo_data = get_post_meta($achievement->ID, '_apollo_achievement_data', true);
                $apollo_badges[] = [
                    'id' => $achievement->ID,
                    'title' => $achievement->post_title,
                    'image' => get_post_thumbnail_id($achievement->ID),
                    'apollo_data' => $apollo_data,
                    'earned_date' => $achievement->date_earned
                ];
            }
        }
        
        return $apollo_badges;
    }
    
    /**
     * Get user's Apollo level based on points
     */
    public function get_user_apollo_level($user_id): array {
        $points = $this->get_user_points($user_id);
        $badges_config = config('badges');
        $levels = $badges_config['levels'] ?? [];
        
        $current_level = ['name' => 'bronze', 'min_points' => 0, 'color' => '#CD7F32'];
        
        foreach ($levels as $level_name => $level_data) {
            if ($points >= $level_data['min_points']) {
                $current_level = array_merge(['name' => $level_name], $level_data);
            }
        }
        
        return $current_level;
    }
    
    /**
     * Check and handle level advancement
     */
    private function check_level_advancement($user_id) {
        $old_level = get_user_meta($user_id, '_apollo_level', true);
        $new_level = $this->get_user_apollo_level($user_id);
        
        if (!$old_level || $old_level['name'] !== $new_level['name']) {
            update_user_meta($user_id, '_apollo_level', $new_level);
            
            // Award level-up achievement if exists
            $level_achievement = $this->get_achievement_by_slug('level_' . $new_level['name']);
            if ($level_achievement) {
                $this->award_achievement_to_user($user_id, $level_achievement, 'apollo_level_up', $new_level);
            }
            
            do_action('apollo_level_advanced', $user_id, $old_level, $new_level);
        }
    }
    
    /**
     * Get achievement by slug
     */
    private function get_achievement_by_slug($slug): ?int {
        $posts = get_posts([
            'post_type' => badgeos_get_achievement_types_slugs(),
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        return $posts ? $posts[0]->ID : null;
    }
    
    /**
     * Get rank/level display for user
     */
    public function get_user_rank_display($user_id): string {
        $level = $this->get_user_apollo_level($user_id);
        $points = $this->get_user_points($user_id);
        
        return sprintf(
            '<span class="apollo-rank" style="color: %s">%s</span> <span class="apollo-points">(%d pts)</span>',
            esc_attr($level['color']),
            esc_html(ucfirst($level['name'])),
            $points
        );
    }
    
    /**
     * Get configuration
     */
    public function get_config(): array {
        return $this->config;
    }
    
    /**
     * Update configuration
     */
    public function update_config(array $config): bool {
        $this->config = array_merge($this->config, $config);
        return update_option('apollo_badgeos_config', $this->config);
    }
}