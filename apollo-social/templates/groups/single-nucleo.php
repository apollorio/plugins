<?php
/**
 * Single Núcleo Template
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Núcleo = Promoter/Organizer collective (more professional than comunidade)
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

// Get núcleo data
$nucleo_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$description = get_post_meta($nucleo_id, '_group_description', true);

// Meta data
$cover_url = get_post_meta($nucleo_id, '_group_cover', true);
$logo_url = get_post_meta($nucleo_id, '_group_avatar', true);
$members_count = (int) get_post_meta($nucleo_id, '_group_members_count', true);
$events_count = (int) get_post_meta($nucleo_id, '_group_events_count', true);
$founded_year = get_post_meta($nucleo_id, '_nucleo_founded_year', true);
$genres = get_post_meta($nucleo_id, '_nucleo_genres', true);
$instagram = get_post_meta($nucleo_id, '_nucleo_instagram', true);
$website = get_post_meta($nucleo_id, '_nucleo_website', true);
$is_verified = (bool) get_post_meta($nucleo_id, '_nucleo_verified', true);

// Founders/Admins
$founders = get_post_meta($nucleo_id, '_nucleo_founders', true);
if (!is_array($founders)) {
    $founders = [];
}

// Default cover
if (!$cover_url) {
    $cover_url = 'https://assets.apollo.rio.br/covers/default-nucleo.jpg';
}

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');

get_header();
?>

<!-- STRICT MODE: Single Núcleo View -->
<div id="apollo-nucleo-root" class="mobile-container min-h-screen bg-slate-50">

  <!-- Cover with Logo Overlay -->
  <div class="relative h-56 md:h-72 overflow-hidden">
    <img 
      src="<?php echo esc_url($cover_url); ?>" 
      alt="<?php echo esc_attr($title); ?>"
      class="w-full h-full object-cover"
      data-tooltip="Capa do núcleo"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
    
    <!-- Back Button -->
    <a href="<?php echo esc_url(home_url('/nucleos/')); ?>" class="absolute top-4 left-4 h-9 w-9 flex items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 backdrop-blur-sm" data-tooltip="Voltar para núcleos">
      <i class="ri-arrow-left-line text-lg"></i>
    </a>
    
    <!-- Verified Badge -->
    <?php if ($is_verified): ?>
    <span class="absolute top-4 right-4 px-2 py-1 bg-blue-500 text-white text-xs font-bold rounded-full flex items-center gap-1" data-tooltip="Núcleo verificado oficialmente">
      <i class="ri-verified-badge-fill"></i>
      Verificado
    </span>
    <?php endif; ?>
    
    <!-- Logo + Title Overlay -->
    <div class="absolute bottom-0 left-0 right-0 p-4 flex items-end gap-4">
      <div class="shrink-0">
        <?php if ($logo_url): ?>
        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>" class="h-16 w-16 rounded-xl border-2 border-white shadow-lg object-cover" data-tooltip="Logo do núcleo" />
        <?php else: ?>
        <div class="h-16 w-16 rounded-xl border-2 border-white shadow-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center" data-tooltip="Logo padrão">
          <i class="ri-fire-fill text-2xl text-white"></i>
        </div>
        <?php endif; ?>
      </div>
      <div class="flex-1 min-w-0 pb-1">
        <h1 class="text-xl md:text-2xl font-bold text-white truncate drop-shadow-lg"><?php echo esc_html($title); ?></h1>
        <p class="text-sm text-white/80">
          Núcleo · <?php echo $founded_year ? 'Desde ' . esc_html($founded_year) : 'Rio de Janeiro'; ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="px-4 py-6 space-y-4">

    <!-- Quick Stats -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4">
      <div class="flex items-center justify-around text-center">
        <div data-tooltip="Eventos organizados">
          <span class="block text-xl font-bold text-slate-900"><?php echo esc_html($events_count); ?></span>
          <span class="text-xs text-slate-500">Eventos</span>
        </div>
        <div class="h-8 w-px bg-slate-200"></div>
        <div data-tooltip="Membros do núcleo">
          <span class="block text-xl font-bold text-slate-900"><?php echo esc_html($members_count); ?></span>
          <span class="text-xs text-slate-500">Membros</span>
        </div>
        <div class="h-8 w-px bg-slate-200"></div>
        <div data-tooltip="Seguidores">
          <span class="block text-xl font-bold text-slate-900">--</span>
          <span class="text-xs text-slate-500">Seguidores</span>
        </div>
      </div>
    </div>

    <!-- About -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Sobre este núcleo">Sobre</h2>
      <?php if ($description || $content): ?>
      <div class="text-sm text-slate-700 leading-relaxed prose prose-sm max-w-none">
        <?php echo wp_kses_post($description ?: $content); ?>
      </div>
      <?php else: ?>
      <p class="text-sm text-slate-400 italic" data-tooltip="Sem descrição disponível">Nenhuma descrição adicionada.</p>
      <?php endif; ?>
      
      <!-- Genres -->
      <?php if ($genres): ?>
      <div class="flex flex-wrap gap-2 mt-4">
        <?php 
        $genre_list = is_array($genres) ? $genres : explode(',', $genres);
        foreach ($genre_list as $genre): 
        ?>
        <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full" data-tooltip="Gênero musical">
          <?php echo esc_html(trim($genre)); ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Social Links -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Redes sociais do núcleo">Links</h2>
      <div class="space-y-2">
        <?php if ($instagram): ?>
        <a href="https://instagram.com/<?php echo esc_attr(ltrim($instagram, '@')); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors" data-tooltip="Perfil no Instagram">
          <div class="h-9 w-9 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 flex items-center justify-center text-white">
            <i class="ri-instagram-line"></i>
          </div>
          <span class="text-sm text-slate-700">@<?php echo esc_html(ltrim($instagram, '@')); ?></span>
        </a>
        <?php endif; ?>
        
        <?php if ($website): ?>
        <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg transition-colors" data-tooltip="Website oficial">
          <div class="h-9 w-9 rounded-full bg-slate-900 flex items-center justify-center text-white">
            <i class="ri-global-line"></i>
          </div>
          <span class="text-sm text-slate-700"><?php echo esc_html(parse_url($website, PHP_URL_HOST)); ?></span>
        </a>
        <?php endif; ?>
        
        <?php if (!$instagram && !$website): ?>
        <p class="text-sm text-slate-400 text-center py-2" data-tooltip="Nenhum link social cadastrado">Nenhum link social cadastrado.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Founders/Team -->
    <?php if (!empty($founders)): ?>
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Fundadores e equipe">Equipe</h2>
      <div class="space-y-3">
        <?php foreach ($founders as $founder_id): 
          $founder = get_userdata($founder_id);
          if (!$founder) continue;
        ?>
        <div class="flex items-center gap-3">
          <?php echo get_avatar($founder_id, 40, '', $founder->display_name, ['class' => 'h-10 w-10 rounded-full']); ?>
          <div>
            <a href="<?php echo esc_url(home_url('/id/' . $founder->user_login)); ?>" class="font-medium text-sm text-slate-900 hover:text-orange-600" data-tooltip="Ver perfil">
              <?php echo esc_html($founder->display_name); ?>
            </a>
            <p class="text-xs text-slate-500">Fundador</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Follow Button -->
    <div class="flex gap-2">
      <button class="flex-1 py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 transition-colors" data-tooltip="Seguir este núcleo">
        <i class="ri-add-line mr-1"></i>
        Seguir Núcleo
      </button>
      <button class="py-3 px-4 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors" data-tooltip="Compartilhar">
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