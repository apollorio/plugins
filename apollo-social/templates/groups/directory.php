<?php
/**
 * Groups Directory Template
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Displays groups listings (/comunidades/, /nucleos/, /temporadas/)
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine group type from URL
$current_url = $_SERVER['REQUEST_URI'];
$group_type = 'comunidade'; // default
$page_title = 'Comunidades';
$page_icon = 'ri-group-line';

if (strpos($current_url, 'nucleo') !== false) {
    $group_type = 'nucleo';
    $page_title = 'Núcleos';
    $page_icon = 'ri-fire-line';
} elseif (strpos($current_url, 'temporada') !== false || strpos($current_url, 'season') !== false) {
    $group_type = 'season';
    $page_title = 'Temporadas';
    $page_icon = 'ri-calendar-event-line';
}

// Query groups
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$search_query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

$args = [
    'post_type' => 'apollo_group',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'meta_value_num',
    'meta_key' => '_group_members_count',
    'order' => 'DESC',
    'meta_query' => [
        [
            'key' => '_group_type',
            'value' => $group_type,
            'compare' => '='
        ]
    ]
];

if ($search_query) {
    $args['s'] = $search_query;
}

$groups = new WP_Query($args);

// Color schemes per type
$type_colors = [
    'comunidade' => ['primary' => 'purple', 'gradient' => 'from-purple-500 to-pink-500'],
    'nucleo' => ['primary' => 'orange', 'gradient' => 'from-orange-500 to-red-500'],
    'season' => ['primary' => 'blue', 'gradient' => 'from-blue-500 to-cyan-500']
];
$colors = $type_colors[$group_type];

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', [], null, true);

get_header();
?>

<!-- STRICT MODE: Groups Directory -->
<div id="apollo-groups-directory" class="mobile-container min-h-screen bg-slate-50">

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200/50 px-4 py-3">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a href="<?php echo esc_url(home_url('/feed/')); ?>" class="h-9 w-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-600" data-tooltip="Voltar ao feed">
            <i class="ri-arrow-left-line text-xl"></i>
          </a>
          <div class="flex items-center gap-2">
            <div class="h-8 w-8 rounded-lg bg-gradient-to-br <?php echo esc_attr($colors['gradient']); ?> flex items-center justify-center text-white">
              <i class="<?php echo esc_attr($page_icon); ?>"></i>
            </div>
            <h1 class="text-lg font-bold text-slate-900"><?php echo esc_html($page_title); ?></h1>
          </div>
        </div>
        
        <?php if ($group_type !== 'season'): ?>
        <a href="<?php echo esc_url(home_url('/criar-' . $group_type . '/')); ?>" class="flex items-center gap-1 px-3 py-1.5 bg-slate-900 text-white text-sm font-medium rounded-full hover:bg-slate-800" data-tooltip="Criar novo">
          <i class="ri-add-line"></i>
          Criar
        </a>
        <?php endif; ?>
      </div>
      
      <!-- Search Bar -->
      <form method="get" class="mt-3">
        <div class="relative">
          <input 
            type="text" 
            name="q" 
            value="<?php echo esc_attr($search_query); ?>"
            placeholder="Buscar <?php echo esc_attr(strtolower($page_title)); ?>..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border-0 rounded-xl text-sm focus:ring-2 focus:ring-<?php echo esc_attr($colors['primary']); ?>-500/20 focus:bg-white"
            data-tooltip="Digite para buscar"
          />
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        </div>
      </form>
    </div>
  </header>

  <!-- Type Tabs -->
  <div class="sticky top-[105px] z-40 bg-white border-b border-slate-100 px-4 py-2 overflow-x-auto">
    <div class="flex gap-2 max-w-6xl mx-auto">
      <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" 
         class="shrink-0 px-4 py-2 text-sm font-medium rounded-full flex items-center gap-2 <?php echo $group_type === 'comunidade' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"
         data-tooltip="Comunidades de interesse">
        <i class="ri-group-line"></i>
        Comunidades
      </a>
      <a href="<?php echo esc_url(home_url('/nucleos/')); ?>" 
         class="shrink-0 px-4 py-2 text-sm font-medium rounded-full flex items-center gap-2 <?php echo $group_type === 'nucleo' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"
         data-tooltip="Coletivos e produtoras">
        <i class="ri-fire-line"></i>
        Núcleos
      </a>
      <a href="<?php echo esc_url(home_url('/temporadas/')); ?>" 
         class="shrink-0 px-4 py-2 text-sm font-medium rounded-full flex items-center gap-2 <?php echo $group_type === 'season' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"
         data-tooltip="Séries de eventos">
        <i class="ri-calendar-event-line"></i>
        Temporadas
      </a>
    </div>
  </div>

  <!-- Groups Grid -->
  <main class="px-4 py-6">
    <div class="max-w-6xl mx-auto">
      
      <?php if ($groups->have_posts()): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        
        <?php while ($groups->have_posts()): $groups->the_post();
          $group_id = get_the_ID();
          $members = (int) get_post_meta($group_id, '_group_members_count', true);
          $events = (int) get_post_meta($group_id, '_group_events_count', true);
          $avatar = get_post_meta($group_id, '_group_avatar', true);
          $cover = get_post_meta($group_id, '_group_cover', true);
          $is_verified = (bool) get_post_meta($group_id, '_group_verified', true);
        ?>
        <a href="<?php the_permalink(); ?>" class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden hover:shadow-md transition-all group">
          
          <!-- Cover -->
          <div class="h-24 relative overflow-hidden bg-slate-100">
            <?php if ($cover): ?>
            <img src="<?php echo esc_url($cover); ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
            <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br <?php echo esc_attr($colors['gradient']); ?> opacity-20"></div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
          </div>
          
          <!-- Info -->
          <div class="p-4 -mt-8 relative">
            <div class="flex items-start gap-3">
              <!-- Avatar -->
              <div class="shrink-0">
                <?php if ($avatar): ?>
                <img src="<?php echo esc_url($avatar); ?>" alt="" class="h-14 w-14 rounded-xl border-2 border-white shadow-md object-cover" />
                <?php else: ?>
                <div class="h-14 w-14 rounded-xl border-2 border-white shadow-md bg-gradient-to-br <?php echo esc_attr($colors['gradient']); ?> flex items-center justify-center">
                  <i class="<?php echo esc_attr($page_icon); ?> text-xl text-white"></i>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="flex-1 min-w-0 pt-6">
                <div class="flex items-center gap-1">
                  <h3 class="font-bold text-slate-900 truncate"><?php the_title(); ?></h3>
                  <?php if ($is_verified): ?>
                  <i class="ri-verified-badge-fill text-blue-500 shrink-0" data-tooltip="Verificado"></i>
                  <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                  <span data-tooltip="Membros"><?php echo esc_html($members); ?> membros</span>
                  <span data-tooltip="Eventos"><?php echo esc_html($events); ?> eventos</span>
                </div>
              </div>
            </div>
          </div>
        </a>
        <?php endwhile; ?>
        
      </div>
      
      <!-- Pagination -->
      <?php if ($groups->max_num_pages > 1): ?>
      <div class="flex justify-center gap-2 mt-8">
        <?php
        echo paginate_links([
            'total' => $groups->max_num_pages,
            'current' => $paged,
            'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
            'next_text' => '<i class="ri-arrow-right-s-line"></i>',
        ]);
        ?>
      </div>
      <?php endif; ?>
      
      <?php wp_reset_postdata(); ?>
      
      <?php else: ?>
      
      <!-- Empty State -->
      <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-8 text-center">
        <div class="h-16 w-16 mx-auto rounded-2xl bg-gradient-to-br <?php echo esc_attr($colors['gradient']); ?> flex items-center justify-center mb-4">
          <i class="<?php echo esc_attr($page_icon); ?> text-3xl text-white"></i>
        </div>
        <h2 class="text-lg font-bold text-slate-900 mb-1" data-tooltip="Nenhum resultado">Nenhum resultado encontrado</h2>
        <p class="text-sm text-slate-500 mb-4">
          <?php if ($search_query): ?>
          Não encontramos <?php echo esc_html(strtolower($page_title)); ?> para "<?php echo esc_html($search_query); ?>".
          <?php else: ?>
          Seja o primeiro a criar!
          <?php endif; ?>
        </p>
        <?php if ($group_type !== 'season'): ?>
        <a href="<?php echo esc_url(home_url('/criar-' . $group_type . '/')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-full hover:bg-slate-800" data-tooltip="Criar novo">
          <i class="ri-add-line"></i>
          Criar <?php echo esc_html(rtrim($page_title, 's')); ?>
        </a>
        <?php endif; ?>
      </div>
      
      <?php endif; ?>
      
    </div>
  </main>

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