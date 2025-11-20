# 📚 API DOCUMENTATION - Apollo Events Manager

## ✅ TODO 133: API Pública Documentada

**Versão:** 0.1.0  
**Data:** 15/01/2025  
**Projeto:** Apollo Events Manager

---

## 🎯 HOOKS DISPONÍVEIS

### Actions (do_action)

#### Plugin Lifecycle
```php
// Após plugin ser carregado
do_action('apollo_events_loaded');

// Após CPTs serem registrados
do_action('apollo_events_cpts_registered');

// Após templates serem carregados
do_action('apollo_events_templates_loaded');
```

#### Event Actions
```php
// Antes de exibir event card
do_action('apollo_before_event_card', $event_id);

// Depois de exibir event card
do_action('apollo_after_event_card', $event_id);

// Antes de exibir single event
do_action('apollo_before_single_event', $event_id);

// Depois de exibir single event
do_action('apollo_after_single_event', $event_id);
```

#### Statistics Actions
```php
// Quando view é trackado
do_action('apollo_event_view_tracked', $event_id, $type); // $type = 'page' ou 'popup'

// Quando favorito é adicionado
do_action('apollo_favorite_added', $event_id, $user_id);

// Quando favorito é removido
do_action('apollo_favorite_removed', $event_id, $user_id);
```

---

## 🔧 FILTERS DISPONÍVEIS

### Event Display Filters
```php
// Modificar título do evento
apply_filters('apollo_event_title', $title, $event_id);

// Modificar descrição do evento
apply_filters('apollo_event_description', $description, $event_id);

// Modificar URL do banner
apply_filters('apollo_event_banner_url', $banner_url, $event_id);

// Modificar args da query de eventos
apply_filters('apollo_events_query_args', $args);

// Modificar template path
apply_filters('apollo_event_template_path', $path, $template_name);
```

### Shortcode Filters
```php
// Modificar output do shortcode [events]
apply_filters('apollo_events_shortcode_output', $output, $atts);

// Modificar atributos do shortcode
apply_filters('apollo_events_shortcode_atts', $atts);
```

### Statistics Filters
```php
// Modificar estatísticas antes de retornar
apply_filters('apollo_event_stats', $stats, $event_id);

// Modificar contagem de favoritos
apply_filters('apollo_favorites_count', $count, $event_id);

// Modificar snapshot de favoritos
apply_filters('apollo_favorites_snapshot', $snapshot, $event_id);
```

### Asset Loading Filters
```php
// Modificar se assets devem ser carregados
apply_filters('apollo_should_enqueue_assets', $should_load, $post);

// Modificar whitelist de assets no canvas mode
apply_filters('apollo_canvas_keep_styles', $keep_styles);
apply_filters('apollo_canvas_keep_scripts', $keep_scripts);

// Modificar body classes
apply_filters('apollo_body_classes', $classes);
```

---

## 📝 FUNÇÕES PÚBLICAS

### Event Data Functions
```php
// Get event meta (safe wrapper)
apollo_get_post_meta($post_id, $meta_key, $single = false);

// Update event meta (safe wrapper)
apollo_update_post_meta($post_id, $meta_key, $meta_value);

// Get event lineup
apollo_get_event_lineup($event_id); // Returns array of DJs

// Get primary local ID
apollo_get_primary_local_id($event_id); // Returns local post ID

// Get event favorites snapshot
apollo_get_event_favorites_snapshot($event_id); // Returns array with count, avatars, etc
```

### Template Functions
```php
// Load template part
apollo_get_template_part($slug, $name = '', $args = array());

// Locate template
apollo_locate_template($template_name);

// Include template
apollo_include_template($template_name, $args = array());
```

### Utility Functions
```php
// Get plugin config
apollo_cfg($key = null); // Returns config array or specific key

// Sanitize timetable
apollo_sanitize_timetable($raw); // Returns sanitized timetable array

// Parse IDs
apollo_aem_parse_ids($raw); // Returns array of int IDs
```

---

## 🎨 SHORTCODES PÚBLICOS

### Event Shortcodes
```php
[events] // Main events listing
[event id="123"] // Single event
[past_events] // Past events only
[upcoming_events] // Future events only
[related_events] // Related to current event
```

### DJ Shortcodes
```php
[event_djs] // DJs listing
[event_dj id="456"] // Single DJ
```

### Local Shortcodes
```php
[event_locals] // Locals listing
[event_local id="789"] // Single local
```

### Form Shortcodes
```php
[submit_event_form] // Event submission form
[event_dashboard] // User event dashboard
```

---

## 🔐 CAPABILITIES

### Custom Capabilities
```php
// View statistics
'view_apollo_event_stats' // Admin, Editor

// Edit events
'edit_event_listings' // Admin, Editor, Author

// Publish events
'publish_event_listings' // Admin, Editor

// Delete events
'delete_event_listings' // Admin, Editor
```

---

## 📊 AJAX ENDPOINTS

### Favorites
```php
// Action: apollo_toggle_favorite
// Nonce: apollo_events_nonce
// Params: event_id
// Returns: { success: bool, favorited: bool, count: int }
```

### Statistics
```php
// Action: apollo_track_event_view
// Nonce: apollo_events_nonce
// Params: event_id, type ('page' ou 'popup')
// Returns: { success: bool }

// Action: apollo_get_event_stats
// Nonce: apollo_events_nonce
// Params: event_id
// Returns: { success: bool, stats: {...} }
```

---

## 🎯 CONSTANTES

### Plugin Constants
```php
APOLLO_WPEM_VERSION // Plugin version (0.1.0)
APOLLO_WPEM_PATH // Plugin directory path
APOLLO_WPEM_URL // Plugin URL
APOLLO_DEBUG // Debug mode flag
```

---

## 📦 CLASSES PÚBLICAS

### Main Plugin Class
```php
Apollo_Events_Manager_Plugin
  - enqueue_assets() // Load CSS/JS
  - events_shortcode($atts) // [events] handler
  - remove_theme_assets_if_shortcode() // Canvas mode
  - force_uni_css_last() // uni.css priority
```

### Statistics Class
```php
Apollo_Event_Statistics
  - track_view($event_id, $type) // Track view
  - get_stats($event_id) // Get statistics
  - get_all_stats() // Get all events stats
```

### Context Menu Class
```php
Apollo_Context_Menu
  - get_menu_items($context) // Get menu for context
  - render_menu() // Render menu HTML
```

### Shortcodes Class
```php
Apollo_Events_Shortcodes
  - output_events($atts) // [events] shortcode
  - output_event_djs($atts) // [event_djs] shortcode
  - output_event_locals($atts) // [event_locals] shortcode
```

---

## 🎨 TAXONOMIES

### Event Taxonomies
```php
'event_listing_category' // Event categories
'event_sounds' // Music genres/sounds
'event_listing_tag' // Event tags (if enabled)
```

### DJ Taxonomies
```php
'event_dj_category' // DJ categories (if enabled)
```

### Local Taxonomies
```php
'event_local_category' // Local categories (if enabled)
```

---

## 📋 POST TYPES

### Custom Post Types
```php
'event_listing' // Events
'event_dj' // DJs
'event_local' // Locals/Venues
```

---

## 🔧 META KEYS

### Event Meta Keys
```php
'_event_title' // Event title
'_event_banner' // Banner URL or attachment ID
'_event_video_url' // YouTube URL
'_event_start_date' // Y-m-d format
'_event_end_date' // Y-m-d format
'_event_start_time' // H:i:s format
'_event_end_time' // H:i:s format
'_event_description' // Event description
'_event_location' // Location string
'_event_address' // Address string
'_event_local_ids' // Array of local post IDs
'_event_dj_ids' // Array of DJ post IDs
'_event_timetable' // JSON timetable
'_tickets_ext' // External ticket URL
'_cupom_ario' // Coupon code
'_3_imagens_promo' // Array of promo image IDs/URLs
'_imagem_final' // Final image ID/URL
'_event_type' // Event type
'_favorites_count' // Number of favorites
```

### Local Meta Keys
```php
'_local_name' // Local name
'_local_address' // Full address
'_local_city' // City
'_local_state' // State
'_local_latitude' // Latitude (float)
'_local_longitude' // Longitude (float)
'_local_image_1' to '_local_image_5' // Local images
```

### DJ Meta Keys
```php
'_dj_name' // DJ name
'_dj_photo' // DJ photo URL/ID
'_dj_bio' // DJ biography
```

---

## 🌐 EXTERNAL ASSETS (CDN)

### Required CDN Assets
```php
// uni.css (UNIVERSAL & MAIN CSS)
'https://assets.apollo.rio.br/uni.css'

// RemixIcon
'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css'

// Framer Motion
'https://cdn.jsdelivr.net/npm/framer-motion@11.0.0/dist/framer-motion.js'

// Leaflet (maps)
'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'

// Apollo Scripts
'https://assets.apollo.rio.br/base.js' // Event portal pages
'https://assets.apollo.rio.br/event-page.js' // Single event pages
```

---

## 📖 EXEMPLOS DE USO

### Hook Example: Modificar Query de Eventos
```php
add_filter('apollo_events_query_args', function($args) {
    // Mostrar apenas eventos featured
    $args['meta_query'][] = array(
        'key' => '_featured',
        'value' => '1'
    );
    return $args;
});
```

### Hook Example: Adicionar Custom Meta ao Card
```php
add_action('apollo_after_event_card', function($event_id) {
    $custom_field = get_post_meta($event_id, '_custom_field', true);
    if ($custom_field) {
        echo '<div class="custom-meta">' . esc_html($custom_field) . '</div>';
    }
});
```

### Hook Example: Custom Statistics Tracking
```php
add_action('apollo_event_view_tracked', function($event_id, $type) {
    // Log para analytics externo
    error_log("Event {$event_id} viewed as {$type}");
    
    // Integrar com Google Analytics
    // ga_track_event('event_view', $event_id, $type);
});
```

### Filter Example: Modificar Canvas Mode Whitelist
```php
add_filter('apollo_canvas_keep_scripts', function($keep_scripts) {
    // Manter script custom no canvas mode
    $keep_scripts[] = 'my-custom-script';
    return $keep_scripts;
});
```

---

## ✅ STATUS

**TODO 133:** ✅ CONCLUÍDO  
**Documentação:** Completa  
**Hooks:** Documentados  
**Filters:** Documentados  
**Functions:** Documentadas  
**Examples:** Incluídos  

---

**Arquivo:** `API-DOCUMENTATION.md`  
**Data:** 15/01/2025  
**Status:** API FULLY DOCUMENTED ✅

