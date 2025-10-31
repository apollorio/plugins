<?php
/**
 * Event Listings Start Wrapper
 * IDs únicos, filtros com normalização de acentos
 */

// Defensive config
if (!function_exists('apollo_cfg')) {
    function apollo_cfg() { return []; }
}

$cfg = apollo_cfg();
$tax = isset($cfg['tax']) ? $cfg['tax'] : [
    'sounds'   => 'event_sounds',
    'category' => 'event_listing_category',
];
?>

<section class="apollo-events-grid" data-layout="grid" id="apollo-events-grid">
    <div class="apollo-filters">
        <input id="apollo-q" type="search" placeholder="Buscar eventos…" autocomplete="off">
        
        <!-- Sounds Filter -->
        <select name="apollo-sounds" id="apollo-sounds">
            <option value="">Todos os sons</option>
            <?php
            $sounds_terms = get_terms([
                'taxonomy' => $tax['sounds'],
                'hide_empty' => false,
            ]);
            if (!is_wp_error($sounds_terms) && !empty($sounds_terms)) {
                foreach ($sounds_terms as $term) {
                    echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                }
            }
            ?>
        </select>
        
        <!-- Category Filter -->
        <select name="apollo-cats" id="apollo-cats">
            <option value="">Todas categorias</option>
            <?php
            $cat_terms = get_terms([
                'taxonomy' => $tax['category'],
                'hide_empty' => false,
            ]);
            if (!is_wp_error($cat_terms) && !empty($cat_terms)) {
                foreach ($cat_terms as $term) {
                    echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                }
            }
            ?>
        </select>
        
        <button type="button" id="apollo-toggle" class="btn toggle">Grid/List</button>
    </div>
    
    <div class="apollo-events-container">