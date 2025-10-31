<?php
/**
 * Template for event listing content - Card enxuto com filtros defensivos
 * Normaliza acentos para filtros funcionarem bem
 */

// Defensive: se apollo_cfg() não existir, cria stub
if (!function_exists('apollo_cfg')) {
    function apollo_cfg() { return []; }
}

$cfg = apollo_cfg();
$tax = isset($cfg['tax']) ? $cfg['tax'] : [
    'category' => 'event_listing_category',
    'sounds'   => 'event_sounds',
];

$event_id = get_the_ID();
$cats     = wp_get_post_terms($event_id, $tax['category'], ['fields' => 'names']);
$sounds   = wp_get_post_terms($event_id, $tax['sounds'], ['fields' => 'names']);

// Normalize accents for filtering
$normalize = function($str) {
    $s = wp_strip_all_tags((string)$str);
    $s = remove_accents($s);
    return strtolower(trim($s));
};

$data_cats   = implode(',', array_map($normalize, (array)$cats));
$data_sounds = implode(',', array_map($normalize, (array)$sounds));

$banner  = get_post_meta($event_id, '_event_banner', true);
$tickets = get_post_meta($event_id, '_tickets_ext', true);
$start_date = get_post_meta($event_id, '_event_start_date', true);
$start_time = get_post_meta($event_id, '_event_start_time', true);
?>

<article class="apollo-event-card" data-cats="<?php echo esc_attr($data_cats); ?>" data-sounds="<?php echo esc_attr($data_sounds); ?>">
    <?php do_action('single_event_overview_before'); ?>
    
    <a class="card-link" href="<?php the_permalink(); ?>">
        <div class="card-media">
            <?php 
            if ($banner) {
                echo wp_get_attachment_image($banner, 'large', false, ['loading' => 'lazy']);
            } elseif (has_post_thumbnail()) {
                the_post_thumbnail('large', ['loading' => 'lazy']);
            }
            ?>
        </div>
        
        <h3 class="card-title"><?php the_title(); ?></h3>
        
        <div class="card-meta">
            <span class="when-date"><?php echo esc_html($start_date); ?></span>
            <?php if ($start_time): ?>
                <span class="when-time"><?php echo esc_html($start_time); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="card-badges">
            <?php 
            foreach ((array)$cats as $c) {
                echo '<span class="badge">' . esc_html($c) . '</span>';
            }
            foreach ((array)$sounds as $s) {
                echo '<span class="badge">' . esc_html($s) . '</span>';
            }
            ?>
        </div>
    </a>
    
    <?php if ($tickets): ?>
        <a class="btn ticket" href="<?php echo esc_url($tickets); ?>" target="_blank" rel="noopener">Ingressos</a>
    <?php endif; ?>
    
    <?php do_action('single_event_overview_after'); ?>
</article>
