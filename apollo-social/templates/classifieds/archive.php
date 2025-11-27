<?php
/**
 * Classifieds Archive Template - /anuncios/
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Forced tooltips on ALL placeholders
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Query classifieds
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$category_filter = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize_text_field($_GET['condition']) : '';
$search_query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

$args = [
    'post_type' => 'apollo_classified',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
];

// Apply filters
$meta_query = [];

if ($category_filter) {
    $meta_query[] = [
        'key' => '_classified_category',
        'value' => $category_filter,
        'compare' => '='
    ];
}

if ($condition_filter) {
    $meta_query[] = [
        'key' => '_classified_condition',
        'value' => $condition_filter,
        'compare' => '='
    ];
}

if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}

if ($search_query) {
    $args['s'] = $search_query;
}

$classifieds = new WP_Query($args);

// Categories for filter
$categories = [
    'tickets' => 'Ingressos',
    'equipment' => 'Equipamentos',
    'services' => 'Serviços',
    'other' => 'Outros'
];

// Conditions for filter
$conditions = [
    'new' => 'Novo',
    'like_new' => 'Seminovo',
    'used' => 'Usado'
];

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', [], null, true);

get_header();
?>

<!-- STRICT MODE: Classifieds Archive -->
<div id="apollo-classifieds-root" class="mobile-container min-h-screen bg-slate-50">

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200/50 px-4 py-3">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a href="<?php echo esc_url(home_url('/feed/')); ?>" class="h-9 w-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-600" data-tooltip="Voltar ao feed">
            <i class="ri-arrow-left-line text-xl"></i>
          </a>
          <h1 class="text-lg font-bold text-slate-900">Classificados</h1>
        </div>
        <a href="<?php echo esc_url(home_url('/anunciar/')); ?>" class="flex items-center gap-1 px-3 py-1.5 bg-slate-900 text-white text-sm font-medium rounded-full hover:bg-slate-800" data-tooltip="Publicar novo anúncio">
          <i class="ri-add-line"></i>
          Anunciar
        </a>
      </div>
      
      <!-- Search Bar -->
      <form method="get" class="mt-3">
        <div class="relative">
          <input 
            type="text" 
            name="q" 
            value="<?php echo esc_attr($search_query); ?>"
            placeholder="Buscar anúncios..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border-0 rounded-xl text-sm focus:ring-2 focus:ring-orange-500/20 focus:bg-white"
            data-tooltip="Digite para buscar anúncios"
          />
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        </div>
      </form>
    </div>
  </header>

  <!-- Filters -->
  <div class="sticky top-[105px] z-40 bg-white border-b border-slate-100 px-4 py-2 overflow-x-auto">
    <div class="flex gap-2 max-w-6xl mx-auto">
      <a href="<?php echo esc_url(remove_query_arg(['cat', 'condition'])); ?>" 
         class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-full <?php echo (!$category_filter && !$condition_filter) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"
         data-tooltip="Mostrar todos os anúncios">
        Todos
      </a>
      <?php foreach ($categories as $key => $label): ?>
      <a href="<?php echo esc_url(add_query_arg('cat', $key)); ?>" 
         class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-full <?php echo ($category_filter === $key) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"
         data-tooltip="Filtrar por <?php echo esc_attr($label); ?>">
        <?php echo esc_html($label); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Classifieds Grid -->
  <main class="px-4 py-6">
    <div class="max-w-6xl mx-auto">
      
      <?php if ($classifieds->have_posts()): ?>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        
        <?php while ($classifieds->have_posts()): $classifieds->the_post(); 
          $price = get_post_meta(get_the_ID(), '_classified_price', true);
          $condition = get_post_meta(get_the_ID(), '_classified_condition', true);
          $location = get_post_meta(get_the_ID(), '_classified_location', true);
          $price_display = $price ? 'R$ ' . number_format((float)$price, 2, ',', '.') : 'Consulte';
          $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
        ?>
        <a href="<?php the_permalink(); ?>" class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden hover:shadow-md transition-shadow group">
          <!-- Image -->
          <div class="aspect-square relative overflow-hidden bg-slate-100">
            <?php if ($thumb): ?>
            <img 
              src="<?php echo esc_url($thumb); ?>" 
              alt="<?php echo esc_attr(get_the_title()); ?>"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              data-tooltip="<?php echo esc_attr(get_the_title()); ?>"
            />
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center" data-tooltip="Sem imagem">
              <i class="ri-image-line text-3xl text-slate-300"></i>
            </div>
            <?php endif; ?>
            
            <?php if ($condition === 'new'): ?>
            <span class="absolute top-2 left-2 px-2 py-0.5 bg-emerald-500 text-white text-[10px] font-bold rounded-full" data-tooltip="Item novo">NOVO</span>
            <?php endif; ?>
          </div>
          
          <!-- Info -->
          <div class="p-3">
            <span class="text-sm font-bold text-emerald-600" data-tooltip="Preço"><?php echo esc_html($price_display); ?></span>
            <h3 class="text-xs font-medium text-slate-900 mt-0.5 line-clamp-2"><?php the_title(); ?></h3>
            <?php if ($location): ?>
            <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1" data-tooltip="Localização">
              <i class="ri-map-pin-line"></i>
              <?php echo esc_html($location); ?>
            </p>
            <?php endif; ?>
          </div>
        </a>
        <?php endwhile; ?>
        
      </div>
      
      <!-- Pagination -->
      <?php if ($classifieds->max_num_pages > 1): ?>
      <div class="flex justify-center gap-2 mt-8">
        <?php
        echo paginate_links([
            'total' => $classifieds->max_num_pages,
            'current' => $paged,
            'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
            'next_text' => '<i class="ri-arrow-right-s-line"></i>',
            'before_page_number' => '<span class="px-3 py-1.5 rounded-lg hover:bg-slate-100">',
            'after_page_number' => '</span>'
        ]);
        ?>
      </div>
      <?php endif; ?>
      
      <?php wp_reset_postdata(); ?>
      
      <?php else: ?>
      
      <!-- Empty State -->
      <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-8 text-center">
        <i class="ri-megaphone-line text-5xl text-slate-300 mb-3"></i>
        <h2 class="text-lg font-bold text-slate-900 mb-1" data-tooltip="Nenhum resultado encontrado">Nenhum anúncio encontrado</h2>
        <p class="text-sm text-slate-500 mb-4">
          <?php if ($search_query): ?>
          Não encontramos anúncios para "<?php echo esc_html($search_query); ?>".
          <?php else: ?>
          Seja o primeiro a anunciar!
          <?php endif; ?>
        </p>
        <a href="<?php echo esc_url(home_url('/anunciar/')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-full hover:bg-slate-800" data-tooltip="Criar um novo anúncio">
          <i class="ri-add-line"></i>
          Publicar Anúncio
        </a>
      </div>
      
      <?php endif; ?>
      
    </div>
  </main>

</div>

<!-- Tooltip CSS -->
<style>
[data-tooltip] {
  position: relative;
}
[data-tooltip]:hover::before,
[data-tooltip]:focus::before {
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
  animation: tooltipFade 0.2s ease;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
}
@keyframes tooltipFade {
  from { opacity: 0; transform: translateX(-50%) translateY(4px); }
  to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

<?php get_footer(); ?>