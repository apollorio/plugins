<?php
/**
 * Single Season Template
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Season = Event series (e.g., "Carnaval 2025", "Verão 2025")
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get season data
$season_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$description = get_post_meta($season_id, '_season_description', true);

// Meta data
$cover_url = get_post_meta($season_id, '_season_cover', true);
$start_date = get_post_meta($season_id, '_season_start_date', true);
$end_date = get_post_meta($season_id, '_season_end_date', true);
$events_count = (int) get_post_meta($season_id, '_season_events_count', true);
$is_active = (bool) get_post_meta($season_id, '_season_is_active', true);
$theme_color = get_post_meta($season_id, '_season_theme_color', true) ?: 'orange';

// Date formatting
$start_obj = $start_date ? DateTime::createFromFormat('Y-m-d', $start_date) : null;
$end_obj = $end_date ? DateTime::createFromFormat('Y-m-d', $end_date) : null;
$date_range = '';
if ($start_obj && $end_obj) {
    $date_range = $start_obj->format('d M') . ' - ' . $end_obj->format('d M Y');
} elseif ($start_obj) {
    $date_range = 'A partir de ' . $start_obj->format('d M Y');
}

// Default cover
if (!$cover_url) {
    $cover_url = 'https://assets.apollo.rio.br/covers/default-season.jpg';
}

// Theme colors
$theme_colors = [
    'orange' => ['from' => 'from-orange-500', 'to' => 'to-red-500', 'text' => 'text-orange-600', 'bg' => 'bg-orange-100'],
    'purple' => ['from' => 'from-purple-500', 'to' => 'to-pink-500', 'text' => 'text-purple-600', 'bg' => 'bg-purple-100'],
    'blue' => ['from' => 'from-blue-500', 'to' => 'to-cyan-500', 'text' => 'text-blue-600', 'bg' => 'bg-blue-100'],
    'green' => ['from' => 'from-emerald-500', 'to' => 'to-teal-500', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
];
$colors = $theme_colors[$theme_color] ?? $theme_colors['orange'];

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');

get_header();
?>

<!-- STRICT MODE: Single Season View -->
<div id="apollo-season-root" class="mobile-container min-h-screen bg-slate-50">

  <!-- Hero Cover -->
  <div class="relative h-64 md:h-80 overflow-hidden">
    <img 
      src="<?php echo esc_url($cover_url); ?>" 
      alt="<?php echo esc_attr($title); ?>"
      class="w-full h-full object-cover"
      data-tooltip="Capa da temporada"
    />
    <div class="absolute inset-0 bg-gradient-to-t <?php echo esc_attr($colors['from']); ?>/60 via-transparent to-transparent"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
    
    <!-- Back Button -->
    <a href="<?php echo esc_url(home_url('/temporadas/')); ?>" class="absolute top-4 left-4 h-9 w-9 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 backdrop-blur-sm" data-tooltip="Voltar">
      <i class="ri-arrow-left-line text-lg"></i>
    </a>
    
    <!-- Status Badge -->
    <?php if ($is_active): ?>
    <span class="absolute top-4 right-4 px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full flex items-center gap-1 animate-pulse" data-tooltip="Temporada ativa agora!">
      <i class="ri-live-line"></i>
      ATIVA
    </span>
    <?php endif; ?>
    
    <!-- Title Overlay -->
    <div class="absolute bottom-0 left-0 right-0 p-5">
      <h1 class="text-2xl md:text-3xl font-bold text-white drop-shadow-lg"><?php echo esc_html($title); ?></h1>
      <?php if ($date_range): ?>
      <p class="text-white/80 mt-1 flex items-center gap-2" data-tooltip="Período da temporada">
        <i class="ri-calendar-2-line"></i>
        <?php echo esc_html($date_range); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content -->
  <div class="px-4 py-6 space-y-4">

    <!-- About Card -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <?php if ($description || $content): ?>
      <div class="text-sm text-slate-700 leading-relaxed prose prose-sm max-w-none">
        <?php echo wp_kses_post($description ?: $content); ?>
      </div>
      <?php else: ?>
      <p class="text-sm text-slate-400 italic text-center py-4" data-tooltip="Descrição da temporada">
        Uma seleção especial de eventos para você aproveitar.
      </p>
      <?php endif; ?>
    </div>

    <!-- Events Counter -->
    <div class="aprioEXP-card-shell bg-gradient-to-r <?php echo esc_attr($colors['from'] . ' ' . $colors['to']); ?> rounded-2xl p-5 text-white">
      <div class="flex items-center justify-between">
        <div>
          <span class="text-3xl font-bold"><?php echo esc_html($events_count); ?></span>
          <span class="text-white/80 ml-2">eventos nesta temporada</span>
        </div>
        <i class="ri-calendar-event-fill text-4xl opacity-30"></i>
      </div>
    </div>

    <!-- Events List -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2" data-tooltip="Eventos desta temporada">
        <i class="ri-calendar-event-line <?php echo esc_attr($colors['text']); ?>"></i>
        Programação
      </h2>
      
      <?php
      $events = get_posts([
          'post_type' => 'event_listing',
          'posts_per_page' => 10,
          'meta_query' => [
              ['key' => '_event_season_id', 'value' => $season_id]
          ],
          'orderby' => 'meta_value',
          'meta_key' => '_event_start_date',
          'order' => 'ASC'
      ]);
      
      if (!empty($events)):
      ?>
      <div class="space-y-3">
        <?php foreach ($events as $event): 
          $event_date = get_post_meta($event->ID, '_event_start_date', true);
          $date_obj = $event_date ? DateTime::createFromFormat('Y-m-d', $event_date) : null;
          $venue = get_post_meta($event->ID, '_event_venue_name', true);
          $thumb = get_the_post_thumbnail_url($event->ID, 'thumbnail');
        ?>
        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="flex gap-3 p-3 hover:bg-slate-50 rounded-xl transition-colors group border border-slate-100">
          <div class="shrink-0">
            <?php if ($thumb): ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="" class="h-16 w-16 rounded-lg object-cover" />
            <?php else: ?>
            <div class="h-16 w-16 rounded-lg <?php echo esc_attr($colors['bg']); ?> flex items-center justify-center">
              <i class="ri-music-2-line text-xl <?php echo esc_attr($colors['text']); ?>"></i>
            </div>
            <?php endif; ?>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-slate-900 group-hover:<?php echo esc_attr($colors['text']); ?> truncate"><?php echo esc_html($event->post_title); ?></h3>
            <?php if ($date_obj): ?>
            <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
              <i class="ri-calendar-line"></i>
              <?php echo esc_html($date_obj->format('D, d M')); ?>
            </p>
            <?php endif; ?>
            <?php if ($venue): ?>
            <p class="text-xs text-slate-400 flex items-center gap-1 mt-0.5 truncate">
              <i class="ri-map-pin-line"></i>
              <?php echo esc_html($venue); ?>
            </p>
            <?php endif; ?>
          </div>
          <div class="shrink-0 self-center">
            <i class="ri-arrow-right-s-line text-slate-400 group-hover:<?php echo esc_attr($colors['text']); ?>"></i>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="text-center py-8" data-tooltip="Programação em breve">
        <i class="ri-calendar-todo-line text-4xl text-slate-300 mb-2"></i>
        <p class="text-slate-400">Programação em breve.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Share Section -->
    <div class="flex gap-2">
      <button class="flex-1 py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 transition-colors" data-tooltip="Receber notificações desta temporada">
        <i class="ri-notification-3-line mr-1"></i>
        Ativar Alertas
      </button>
      <button class="py-3 px-4 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors" data-tooltip="Compartilhar temporada">
        <i class="ri-share-line"></i>
      </button>
    </div>

  </div>

</div>

<!-- Tooltip CSS -->
<style>
[data-tooltip] { position: relative; }
[data-tooltip]:hover::before {
  content: attr(data-tooltip);
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  padding: 6px 10px;
  background: rgba(15, 23, 42, 0.95);
  color: #fff;
  font-size: 11px;
  font-weight: 500;
  border-radius: 6px;
  white-space: nowrap;
  z-index: 9999;
  pointer-events: none;
}
</style>

<?php get_footer(); ?>