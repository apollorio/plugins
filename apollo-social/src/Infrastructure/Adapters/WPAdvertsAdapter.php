<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * Adapter for WPAdverts plugin integration
 * Provides seamless integration with WPAdverts for classified listings
 */
class WPAdvertsAdapter {
    
    private $config;
    private $category_mapping;
    
    public function __construct() {
        $this->config = config('integrations.wpadverts');
        $this->category_mapping = $this->config['category_mapping'] ?? [];
        
        if ($this->config['enabled'] ?? false) {
            $this->init_hooks();
        }
    }
    
    /**
     * Initialize WordPress hooks for WPAdverts integration
     */
    private function init_hooks() {
        // Advert lifecycle hooks
        add_action('save_post', [$this, 'on_advert_saved'], 10, 2);
        add_action('before_delete_post', [$this, 'on_advert_deleted'], 10, 1);
        add_action('transition_post_status', [$this, 'on_advert_status_changed'], 10, 3);
        
        // Form submission hooks
        add_filter('adverts_form_load', [$this, 'modify_advert_form'], 10, 2);
        add_action('adverts_save_post', [$this, 'save_apollo_advert_data'], 10, 2);
        add_filter('adverts_form_bind', [$this, 'bind_apollo_data'], 10, 2);
        
        // Display hooks
        add_action('adverts_tpl_single_top', [$this, 'add_apollo_meta_display'], 10, 1);
        add_filter('adverts_list_query', [$this, 'modify_adverts_query'], 10, 2);
        
        // Search and filtering
        add_filter('adverts_search_query_meta', [$this, 'add_apollo_search_filters'], 10, 2);
        add_action('adverts_search_form', [$this, 'add_apollo_search_fields'], 10, 1);
        
        // Category mapping
        add_filter('adverts_category_list', [$this, 'map_apollo_categories'], 10, 1);
        
        // Price range and location filtering
        if ($this->config['price_range_filter'] ?? false) {
            add_action('adverts_search_form', [$this, 'add_price_range_filter'], 20, 1);
        }
        
        if ($this->config['location_filter'] ?? false) {
            add_action('adverts_search_form', [$this, 'add_location_filter'], 30, 1);
        }
    }
    
    /**
     * Check if WPAdverts is active and available
     */
    public function is_available(): bool {
        return class_exists('Adverts') || function_exists('adverts_config');
    }
    
    /**
     * Handle advert save
     */
    public function on_advert_saved($post_id, $post) {
        if (!$this->is_advert($post_id)) return;
        
        $apollo_meta = $this->get_apollo_advert_meta($post_id);
        if (empty($apollo_meta)) return;
        
        // Sync with Apollo system
        do_action('apollo_advert_saved', $post_id, $apollo_meta, $post);
    }
    
    /**
     * Handle advert deletion
     */
    public function on_advert_deleted($post_id) {
        if (!$this->is_advert($post_id)) return;
        
        $apollo_meta = $this->get_apollo_advert_meta($post_id);
        if (empty($apollo_meta)) return;
        
        do_action('apollo_advert_deleted', $post_id, $apollo_meta);
    }
    
    /**
     * Handle advert status changes
     */
    public function on_advert_status_changed($new_status, $old_status, $post) {
        if (!$this->is_advert($post->ID)) return;
        
        $apollo_meta = $this->get_apollo_advert_meta($post->ID);
        if (empty($apollo_meta)) return;
        
        // Handle approval/rejection
        if ($old_status === 'pending' && $new_status === 'publish') {
            do_action('apollo_advert_approved', $post->ID, $apollo_meta);
            
            // Award points for approved classified
            do_action('apollo_award_points', $post->post_author, 'classified_approved', [
                'advert_id' => $post->ID,
                'apollo_meta' => $apollo_meta
            ]);
            
        } elseif ($new_status === 'draft' || $new_status === 'trash') {
            do_action('apollo_advert_rejected', $post->ID, $apollo_meta);
        }
        
        do_action('apollo_advert_status_changed', $post->ID, $new_status, $old_status, $apollo_meta);
    }
    
    /**
     * Modify advert submission form to include Apollo fields
     */
    public function modify_advert_form($form, $post_id = null) {
        if ($form['name'] !== 'advert') return $form;
        
        // Add Apollo fieldset
        $apollo_fieldset = [
            'name' => 'apollo',
            'title' => 'Configurações Apollo',
            'fields' => [
                [
                    'name' => 'apollo_group_type',
                    'type' => 'adverts_field_select',
                    'title' => 'Tipo de Grupo',
                    'options' => [
                        '' => 'Selecione...',
                        'comunidade' => 'Comunidade',
                        'nucleo' => 'Núcleo',
                        'season' => 'Season'
                    ],
                    'validators' => []
                ],
                [
                    'name' => 'apollo_visibility',
                    'type' => 'adverts_field_select',
                    'title' => 'Visibilidade',
                    'options' => [
                        'public' => 'Público',
                        'members' => 'Apenas Membros',
                        'private' => 'Privado'
                    ],
                    'value' => 'public',
                    'validators' => [
                        ['name' => 'is_required']
                    ]
                ],
                [
                    'name' => 'apollo_location_type',
                    'type' => 'adverts_field_select',
                    'title' => 'Tipo de Localização',
                    'options' => [
                        'local' => 'Local',
                        'regional' => 'Regional',
                        'national' => 'Nacional'
                    ],
                    'value' => 'local',
                    'validators' => []
                ],
                [
                    'name' => 'apollo_urgency',
                    'type' => 'adverts_field_select',
                    'title' => 'Urgência',
                    'options' => [
                        'low' => 'Baixa',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente'
                    ],
                    'value' => 'normal',
                    'validators' => []
                ],
                [
                    'name' => 'apollo_negotiable',
                    'type' => 'adverts_field_checkbox',
                    'title' => 'Preço Negociável',
                    'value' => '0',
                    'validators' => []
                ],
                [
                    'name' => 'apollo_tags',
                    'type' => 'adverts_field_text',
                    'title' => 'Tags Apollo',
                    'placeholder' => 'Separado por vírgulas',
                    'validators' => []
                ]
            ]
        ];
        
        // Insert Apollo fieldset after main fieldset
        $fieldsets = $form['fieldset'];
        $position = 1;
        
        array_splice($fieldsets, $position, 0, [$apollo_fieldset]);
        $form['fieldset'] = $fieldsets;
        
        return $form;
    }
    
    /**
     * Save Apollo-specific data when advert is saved
     */
    public function save_apollo_advert_data($post_id, $form) {
        if ($form['name'] !== 'advert') return;
        
        $apollo_fields = [
            'apollo_group_type',
            'apollo_visibility', 
            'apollo_location_type',
            'apollo_urgency',
            'apollo_negotiable',
            'apollo_tags'
        ];
        
        foreach ($apollo_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Set Apollo category mapping
        $this->sync_apollo_category($post_id);
        
        do_action('apollo_advert_data_saved', $post_id, $_POST);
    }
    
    /**
     * Bind Apollo data to form when editing
     */
    public function bind_apollo_data($form, $post_id) {
        if (!$post_id || $form['name'] !== 'advert') return $form;
        
        $apollo_fields = [
            'apollo_group_type',
            'apollo_visibility',
            'apollo_location_type', 
            'apollo_urgency',
            'apollo_negotiable',
            'apollo_tags'
        ];
        
        foreach ($apollo_fields as $field) {
            $value = get_post_meta($post_id, '_' . $field, true);
            if ($value) {
                adverts_form_bind_value($form, $field, $value);
            }
        }
        
        return $form;
    }
    
    /**
     * Add Apollo meta display to single advert view
     */
    public function add_apollo_meta_display($post_id) {
        $apollo_meta = $this->get_apollo_advert_meta($post_id);
        if (empty($apollo_meta)) return;
        
        echo '<div class="apollo-advert-meta">';
        
        // Group type
        if (!empty($apollo_meta['group_type'])) {
            echo '<div class="apollo-meta-item">';
            echo '<strong>Grupo:</strong> ' . esc_html(ucfirst($apollo_meta['group_type']));
            echo '</div>';
        }
        
        // Visibility
        if (!empty($apollo_meta['visibility'])) {
            $visibility_labels = [
                'public' => 'Público',
                'members' => 'Apenas Membros',
                'private' => 'Privado'
            ];
            echo '<div class="apollo-meta-item">';
            echo '<strong>Visibilidade:</strong> ' . esc_html($visibility_labels[$apollo_meta['visibility']] ?? $apollo_meta['visibility']);
            echo '</div>';
        }
        
        // Location type
        if (!empty($apollo_meta['location_type'])) {
            $location_labels = [
                'local' => 'Local',
                'regional' => 'Regional', 
                'national' => 'Nacional'
            ];
            echo '<div class="apollo-meta-item">';
            echo '<strong>Abrangência:</strong> ' . esc_html($location_labels[$apollo_meta['location_type']] ?? $apollo_meta['location_type']);
            echo '</div>';
        }
        
        // Urgency
        if (!empty($apollo_meta['urgency']) && $apollo_meta['urgency'] !== 'normal') {
            $urgency_labels = [
                'low' => 'Baixa',
                'normal' => 'Normal',
                'high' => 'Alta',
                'urgent' => 'Urgente'
            ];
            echo '<div class="apollo-meta-item apollo-urgency-' . esc_attr($apollo_meta['urgency']) . '">';
            echo '<strong>Urgência:</strong> ' . esc_html($urgency_labels[$apollo_meta['urgency']] ?? $apollo_meta['urgency']);
            echo '</div>';
        }
        
        // Negotiable
        if (!empty($apollo_meta['negotiable'])) {
            echo '<div class="apollo-meta-item">';
            echo '<span class="apollo-negotiable">💰 Preço Negociável</span>';
            echo '</div>';
        }
        
        // Tags
        if (!empty($apollo_meta['tags'])) {
            $tags = explode(',', $apollo_meta['tags']);
            echo '<div class="apollo-meta-item">';
            echo '<strong>Tags:</strong> ';
            foreach ($tags as $tag) {
                echo '<span class="apollo-tag">' . esc_html(trim($tag)) . '</span> ';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Modify adverts query for Apollo filtering
     */
    public function modify_adverts_query($query, $params) {
        // Add Apollo meta queries
        if (isset($params['apollo_group_type'])) {
            $query['meta_query'][] = [
                'key' => '_apollo_group_type',
                'value' => $params['apollo_group_type'],
                'compare' => '='
            ];
        }
        
        if (isset($params['apollo_visibility'])) {
            $query['meta_query'][] = [
                'key' => '_apollo_visibility',
                'value' => $params['apollo_visibility'],
                'compare' => '='
            ];
        }
        
        if (isset($params['apollo_urgency'])) {
            $query['meta_query'][] = [
                'key' => '_apollo_urgency',
                'value' => $params['apollo_urgency'],
                'compare' => '='
            ];
        }
        
        return $query;
    }
    
    /**
     * Add Apollo search filters
     */
    public function add_apollo_search_filters($meta_query, $params) {
        if (isset($_GET['apollo_tags']) && !empty($_GET['apollo_tags'])) {
            $tags = sanitize_text_field($_GET['apollo_tags']);
            $meta_query[] = [
                'key' => '_apollo_tags',
                'value' => $tags,
                'compare' => 'LIKE'
            ];
        }
        
        if (isset($_GET['apollo_negotiable']) && $_GET['apollo_negotiable'] === '1') {
            $meta_query[] = [
                'key' => '_apollo_negotiable',
                'value' => '1',
                'compare' => '='
            ];
        }
        
        return $meta_query;
    }
    
    /**
     * Add Apollo search fields to search form
     */
    public function add_apollo_search_fields($form) {
        echo '<div class="apollo-search-fields">';
        
        // Group type filter
        echo '<div class="apollo-search-field">';
        echo '<label for="apollo_group_type">Tipo de Grupo:</label>';
        echo '<select name="apollo_group_type" id="apollo_group_type">';
        echo '<option value="">Todos</option>';
        echo '<option value="comunidade"' . selected($_GET['apollo_group_type'] ?? '', 'comunidade', false) . '>Comunidade</option>';
        echo '<option value="nucleo"' . selected($_GET['apollo_group_type'] ?? '', 'nucleo', false) . '>Núcleo</option>';
        echo '<option value="season"' . selected($_GET['apollo_group_type'] ?? '', 'season', false) . '>Season</option>';
        echo '</select>';
        echo '</div>';
        
        // Urgency filter
        echo '<div class="apollo-search-field">';
        echo '<label for="apollo_urgency">Urgência:</label>';
        echo '<select name="apollo_urgency" id="apollo_urgency">';
        echo '<option value="">Todas</option>';
        echo '<option value="urgent"' . selected($_GET['apollo_urgency'] ?? '', 'urgent', false) . '>Urgente</option>';
        echo '<option value="high"' . selected($_GET['apollo_urgency'] ?? '', 'high', false) . '>Alta</option>';
        echo '<option value="normal"' . selected($_GET['apollo_urgency'] ?? '', 'normal', false) . '>Normal</option>';
        echo '<option value="low"' . selected($_GET['apollo_urgency'] ?? '', 'low', false) . '>Baixa</option>';
        echo '</select>';
        echo '</div>';
        
        // Tags search
        echo '<div class="apollo-search-field">';
        echo '<label for="apollo_tags">Tags:</label>';
        echo '<input type="text" name="apollo_tags" id="apollo_tags" value="' . esc_attr($_GET['apollo_tags'] ?? '') . '" placeholder="Buscar por tags...">';
        echo '</div>';
        
        // Negotiable checkbox
        echo '<div class="apollo-search-field">';
        echo '<label>';
        echo '<input type="checkbox" name="apollo_negotiable" value="1"' . checked($_GET['apollo_negotiable'] ?? '', '1', false) . '>';
        echo ' Apenas preços negociáveis';
        echo '</label>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Add price range filter
     */
    public function add_price_range_filter($form) {
        echo '<div class="apollo-price-range">';
        echo '<label>Faixa de Preço:</label>';
        echo '<div class="price-inputs">';
        echo '<input type="number" name="price_min" placeholder="Mín" value="' . esc_attr($_GET['price_min'] ?? '') . '">';
        echo '<span>até</span>';
        echo '<input type="number" name="price_max" placeholder="Máx" value="' . esc_attr($_GET['price_max'] ?? '') . '">';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Add location filter
     */
    public function add_location_filter($form) {
        echo '<div class="apollo-location-filter">';
        echo '<label for="apollo_location_type">Abrangência:</label>';
        echo '<select name="apollo_location_type" id="apollo_location_type">';
        echo '<option value="">Todas</option>';
        echo '<option value="local"' . selected($_GET['apollo_location_type'] ?? '', 'local', false) . '>Local</option>';
        echo '<option value="regional"' . selected($_GET['apollo_location_type'] ?? '', 'regional', false) . '>Regional</option>';
        echo '<option value="national"' . selected($_GET['apollo_location_type'] ?? '', 'national', false) . '>Nacional</option>';
        echo '</select>';
        echo '</div>';
    }
    
    /**
     * Map Apollo categories to WPAdverts categories
     */
    public function map_apollo_categories($categories) {
        foreach ($this->category_mapping as $apollo_cat => $wpadverts_cat) {
            // Find and enhance category
            foreach ($categories as &$category) {
                if ($category->slug === $wpadverts_cat) {
                    $category->apollo_type = $apollo_cat;
                    break;
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Create Apollo advert programmatically
     */
    public function create_apollo_advert($advert_data): ?int {
        if (!$this->is_available()) return null;
        
        $defaults = [
            'post_type' => 'advert',
            'post_status' => $this->config['auto_approve'] ? 'publish' : 'pending',
            'meta_input' => []
        ];
        
        $advert_data = array_merge($defaults, $advert_data);
        
        // Extract Apollo meta
        $apollo_meta = [];
        $apollo_fields = ['apollo_group_type', 'apollo_visibility', 'apollo_location_type', 'apollo_urgency', 'apollo_negotiable', 'apollo_tags'];
        
        foreach ($apollo_fields as $field) {
            if (isset($advert_data[$field])) {
                $apollo_meta[$field] = $advert_data[$field];
                unset($advert_data[$field]);
            }
        }
        
        // Create advert
        $post_id = wp_insert_post($advert_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Save Apollo meta
            foreach ($apollo_meta as $key => $value) {
                update_post_meta($post_id, '_' . $key, $value);
            }
            
            do_action('apollo_advert_created', $post_id, $apollo_meta);
            
            return $post_id;
        }
        
        return null;
    }
    
    /**
     * Get adverts by Apollo group
     */
    public function get_adverts_by_group($group_type, $args = []): array {
        $query_args = array_merge([
            'post_type' => 'advert',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_apollo_group_type',
                    'value' => $group_type,
                    'compare' => '='
                ]
            ]
        ], $args);
        
        return get_posts($query_args);
    }
    
    /**
     * Check if user can view advert based on Apollo rules
     */
    public function user_can_view_advert($user_id, $post_id): bool {
        $apollo_meta = $this->get_apollo_advert_meta($post_id);
        
        if (empty($apollo_meta['visibility']) || $apollo_meta['visibility'] === 'public') {
            return true;
        }
        
        if ($apollo_meta['visibility'] === 'members') {
            // Check if user is member of the advert's group
            $group_type = $apollo_meta['group_type'] ?? null;
            if ($group_type) {
                $groups_adapter = new GroupsAdapter();
                return $groups_adapter->user_has_group_access($user_id, $group_type);
            }
        }
        
        if ($apollo_meta['visibility'] === 'private') {
            // Check if user is advert author or admin
            $advert = get_post($post_id);
            return $user_id == $advert->post_author || user_can($user_id, 'manage_options');
        }
        
        return false;
    }
    
    /**
     * Helper methods
     */
    private function is_advert($post_id): bool {
        return get_post_type($post_id) === 'advert';
    }
    
    private function get_apollo_advert_meta($post_id): array {
        return [
            'group_type' => get_post_meta($post_id, '_apollo_group_type', true),
            'visibility' => get_post_meta($post_id, '_apollo_visibility', true),
            'location_type' => get_post_meta($post_id, '_apollo_location_type', true),
            'urgency' => get_post_meta($post_id, '_apollo_urgency', true),
            'negotiable' => get_post_meta($post_id, '_apollo_negotiable', true),
            'tags' => get_post_meta($post_id, '_apollo_tags', true)
        ];
    }
    
    private function sync_apollo_category($post_id) {
        $group_type = get_post_meta($post_id, '_apollo_group_type', true);
        if (!$group_type || !isset($this->category_mapping[$group_type])) return;
        
        $category_slug = $this->category_mapping[$group_type];
        $category = get_term_by('slug', $category_slug, 'advert_category');
        
        if ($category) {
            wp_set_post_terms($post_id, [$category->term_id], 'advert_category');
        }
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
        return update_option('apollo_wpadverts_config', $this->config);
    }
}