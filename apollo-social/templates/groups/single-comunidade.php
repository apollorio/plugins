<?php
/**
 * Single Comunidade Template
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Forced tooltips on ALL placeholders
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get group data
$group_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$description = get_post_meta($group_id, '_group_description', true) ?: wp_trim_words($content, 30);

// Meta data
$cover_url = get_post_meta($group_id, '_group_cover', true);
$avatar_url = get_post_meta($group_id, '_group_avatar', true);
$members_count = (int) get_post_meta($group_id, '_group_members_count', true);
$events_count = (int) get_post_meta($group_id, '_group_events_count', true);
$is_private = (bool) get_post_meta($group_id, '_group_is_private', true);
$category = get_post_meta($group_id, '_group_category', true);
$location = get_post_meta($group_id, '_group_location', true);
$created_date = get_the_date('M Y');

// Admin/Creator
$creator_id = get_post_field('post_author', $group_id);
$creator = get_userdata($creator_id);

// Current user membership
$current_user_id = get_current_user_id();
$is_member = false;
$is_admin = false;
if ($current_user_id) {
    $membership = get_user_meta($current_user_id, '_group_memberships', true);
    if (is_array($membership) && in_array($group_id, $membership)) {
        $is_member = true;
    }
    $admin_groups = get_user_meta($current_user_id, '_group_admin_of', true);
    if (is_array($admin_groups) && in_array($group_id, $admin_groups)) {
        $is_admin = true;
    }
}

// Default cover
if (!$cover_url) {
    $cover_url = 'https://assets.apollo.rio.br/covers/default-community.jpg';
}

// Category colors
$category_colors = [
    'tech_house' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
    'minimal' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
    'trance' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
    'bass' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
    'default' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600']
];
$cat_color = $category_colors[$category] ?? $category_colors['default'];

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');

get_header();
?>

<!-- STRICT MODE: Single Comunidade View -->
<div id="apollo-comunidade-root" class="mobile-container min-h-screen bg-slate-50">

  <!-- Cover Image -->
  <div class="relative h-48 md:h-64 overflow-hidden">
    <img 
      src="<?php echo esc_url($cover_url); ?>" 
      alt="<?php echo esc_attr($title); ?>"
      class="w-full h-full object-cover"
      data-tooltip="Capa da comunidade"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
    
    <!-- Back Button -->
    <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="absolute top-4 left-4 h-9 w-9 flex items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50 backdrop-blur-sm" data-tooltip="Voltar para comunidades">
      <i class="ri-arrow-left-line text-lg"></i>
    </a>
    
    <!-- Privacy Badge -->
    <?php if ($is_private): ?>
    <span class="absolute top-4 right-4 px-2 py-1 bg-black/30 text-white text-xs font-medium rounded-full backdrop-blur-sm flex items-center gap-1" data-tooltip="Comunidade privada - apenas membros veem o conteúdo">
      <i class="ri-lock-line"></i>
      Privada
    </span>
    <?php endif; ?>
  </div>

  <!-- Profile Section -->
  <div class="relative -mt-12 px-4">
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      
      <!-- Avatar + Name -->
      <div class="flex items-start gap-4 -mt-16 mb-4">
        <div class="shrink-0">
          <?php if ($avatar_url): ?>
          <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($title); ?>" class="h-20 w-20 rounded-2xl border-4 border-white shadow-lg object-cover" data-tooltip="Avatar da comunidade" />
          <?php else: ?>
          <div class="h-20 w-20 rounded-2xl border-4 border-white shadow-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center" data-tooltip="Avatar padrão">
            <i class="ri-group-line text-2xl text-white"></i>
          </div>
          <?php endif; ?>
        </div>
        <div class="flex-1 mt-12 min-w-0">
          <h1 class="text-xl font-bold text-slate-900 truncate"><?php echo esc_html($title); ?></h1>
          <p class="text-sm text-slate-500">Comunidade · Criada em <?php echo esc_html($created_date); ?></p>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="flex items-center justify-around py-3 border-y border-slate-100 my-4">
        <div class="text-center" data-tooltip="Total de membros">
          <span class="block text-lg font-bold text-slate-900"><?php echo esc_html($members_count); ?></span>
          <span class="text-xs text-slate-500">Membros</span>
        </div>
        <div class="text-center" data-tooltip="Eventos organizados">
          <span class="block text-lg font-bold text-slate-900"><?php echo esc_html($events_count); ?></span>
          <span class="text-xs text-slate-500">Eventos</span>
        </div>
        <div class="text-center" data-tooltip="Categoria musical">
          <span class="block px-2 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($cat_color['bg'] . ' ' . $cat_color['text']); ?>">
            <?php echo esc_html(ucfirst(str_replace('_', ' ', $category ?: 'Geral'))); ?>
          </span>
          <span class="text-xs text-slate-500 mt-0.5 block">Gênero</span>
        </div>
      </div>

      <!-- Description -->
      <?php if ($description): ?>
      <p class="text-sm text-slate-700 mb-4" data-tooltip="Sobre esta comunidade"><?php echo esc_html($description); ?></p>
      <?php else: ?>
      <p class="text-sm text-slate-400 italic mb-4" data-tooltip="Sem descrição disponível">Nenhuma descrição adicionada.</p>
      <?php endif; ?>

      <!-- Location -->
      <?php if ($location): ?>
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-4" data-tooltip="Localização base">
        <i class="ri-map-pin-line"></i>
        <?php echo esc_html($location); ?>
      </div>
      <?php endif; ?>

      <!-- Action Buttons -->
      <div class="flex gap-2">
        <?php if (!$is_member && is_user_logged_in()): ?>
        <button class="flex-1 py-2.5 px-4 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors" data-tooltip="Solicitar entrada na comunidade" data-action="join-group" data-group-id="<?php echo esc_attr($group_id); ?>">
          <i class="ri-user-add-line mr-1"></i>
          Participar
        </button>
        <?php elseif ($is_member): ?>
        <button class="flex-1 py-2.5 px-4 bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium" data-tooltip="Você já é membro" disabled>
          <i class="ri-check-line mr-1"></i>
          Membro
        </button>
        <?php else: ?>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flex-1 py-2.5 px-4 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors text-center" data-tooltip="Faça login para participar">
          <i class="ri-login-box-line mr-1"></i>
          Entrar para Participar
        </a>
        <?php endif; ?>
        
        <button class="py-2.5 px-4 border border-slate-200 rounded-full text-slate-600 hover:bg-slate-50 transition-colors" data-tooltip="Compartilhar comunidade" data-action="share">
          <i class="ri-share-line"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Content Tabs -->
  <div class="px-4 py-6 space-y-4">
    
    <!-- Upcoming Events -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2" data-tooltip="Eventos organizados por esta comunidade">
        <i class="ri-calendar-event-line text-purple-500"></i>
        Próximos Eventos
      </h2>
      
      <?php
      $events = get_posts([
          'post_type' => 'event_listing',
          'posts_per_page' => 3,
          'meta_query' => [
              ['key' => '_event_community_id', 'value' => $group_id]
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
        ?>
        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="flex gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors group">
          <div class="h-12 w-12 rounded-lg bg-slate-100 flex flex-col items-center justify-center text-slate-700">
            <?php if ($date_obj): ?>
            <span class="text-[10px] uppercase font-bold"><?php echo esc_html(strtoupper(substr(['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'][$date_obj->format('w')], 0, 3))); ?></span>
            <span class="text-sm font-bold"><?php echo esc_html($date_obj->format('d')); ?></span>
            <?php else: ?>
            <i class="ri-calendar-line"></i>
            <?php endif; ?>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-sm text-slate-900 group-hover:text-purple-600 truncate"><?php echo esc_html($event->post_title); ?></h3>
            <p class="text-xs text-slate-500">Evento da comunidade</p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="text-center py-6" data-tooltip="Nenhum evento programado ainda">
        <i class="ri-calendar-line text-3xl text-slate-300 mb-2"></i>
        <p class="text-sm text-slate-400">Nenhum evento próximo.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Members Preview -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2" data-tooltip="Membros desta comunidade">
        <i class="ri-team-line text-blue-500"></i>
        Membros
      </h2>
      
      <div class="flex items-center -space-x-2 mb-3">
        <?php 
        // Get some members (mock for now)
        for ($i = 0; $i < min(6, $members_count); $i++):
        ?>
        <div class="h-10 w-10 rounded-full border-2 border-white bg-slate-200 flex items-center justify-center text-slate-400" data-tooltip="Membro da comunidade">
          <i class="ri-user-line text-sm"></i>
        </div>
        <?php endfor; ?>
        
        <?php if ($members_count > 6): ?>
        <div class="h-10 w-10 rounded-full border-2 border-white bg-slate-900 text-white text-xs font-bold flex items-center justify-center" data-tooltip="E mais <?php echo esc_attr($members_count - 6); ?> membros">
          +<?php echo esc_html($members_count - 6); ?>
        </div>
        <?php endif; ?>
      </div>
      
      <a href="#members" class="text-sm text-purple-600 hover:underline" data-tooltip="Ver todos os membros">Ver todos os membros →</a>
    </div>

    <!-- Admin Info -->
    <?php if ($creator): ?>
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Administrador da comunidade">Administrador</h2>
      <div class="flex items-center gap-3">
        <?php echo get_avatar($creator_id, 48, '', $creator->display_name, ['class' => 'h-12 w-12 rounded-full']); ?>
        <div>
          <a href="<?php echo esc_url(home_url('/id/' . $creator->user_login)); ?>" class="font-medium text-slate-900 hover:text-purple-600" data-tooltip="Ver perfil do administrador">
            <?php echo esc_html($creator->display_name); ?>
          </a>
          <p class="text-xs text-slate-500">Criador da comunidade</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

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