<?php
/**
 * Single Classified Template - /anuncio/{slug}
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

// Get classified data
$classified_id = get_the_ID();
$title = get_the_title();
$content = get_the_content();
$author_id = get_post_field('post_author', $classified_id);
$author = get_userdata($author_id);
$author_name = $author ? $author->display_name : 'Anunciante';
$author_login = $author ? $author->user_login : '';

// Meta data
$price = get_post_meta($classified_id, '_classified_price', true);
$condition = get_post_meta($classified_id, '_classified_condition', true);
$category = get_post_meta($classified_id, '_classified_category', true);
$location = get_post_meta($classified_id, '_classified_location', true);
$contact_phone = get_post_meta($classified_id, '_classified_phone', true);
$contact_whatsapp = get_post_meta($classified_id, '_classified_whatsapp', true);
$views_count = (int) get_post_meta($classified_id, '_classified_views', true);

// Increment view count
update_post_meta($classified_id, '_classified_views', $views_count + 1);

// Images
$featured_image = get_the_post_thumbnail_url($classified_id, 'large');
$gallery = get_post_meta($classified_id, '_classified_gallery', true);
if (!is_array($gallery)) {
    $gallery = [];
}

// Format price
$price_display = $price ? 'R$ ' . number_format((float)$price, 2, ',', '.') : 'Consulte';

// Condition labels
$condition_labels = [
    'new' => 'Novo',
    'like_new' => 'Seminovo',
    'used' => 'Usado',
    'for_parts' => 'Para peças'
];
$condition_display = $condition_labels[$condition] ?? 'Não especificado';

// Enqueue assets
wp_enqueue_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
wp_enqueue_style('apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0');
wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');

get_header();
?>

<!-- STRICT MODE: Single Classified View -->
<div id="apollo-classified-root" class="mobile-container min-h-screen bg-slate-50">

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200/50 px-4 py-3">
    <div class="flex items-center justify-between max-w-4xl mx-auto">
      <a href="<?php echo esc_url(home_url('/anuncios/')); ?>" class="flex items-center gap-2 text-slate-600 hover:text-slate-900" data-tooltip="Voltar para anúncios">
        <i class="ri-arrow-left-line text-xl"></i>
        <span class="text-sm font-medium">Anúncios</span>
      </a>
      <div class="flex items-center gap-2">
        <button class="h-9 w-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-600" data-tooltip="Compartilhar anúncio" data-action="share">
          <i class="ri-share-line text-lg"></i>
        </button>
        <button class="h-9 w-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-600" data-tooltip="Salvar nos favoritos" data-action="favorite">
          <i class="ri-heart-3-line text-lg"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Image Gallery -->
  <div class="relative bg-black">
    <?php if ($featured_image): ?>
    <div class="aspect-[4/3] max-h-80">
      <img 
        src="<?php echo esc_url($featured_image); ?>" 
        alt="<?php echo esc_attr($title); ?>"
        class="w-full h-full object-contain"
        data-tooltip="Imagem principal do anúncio"
      />
    </div>
    <?php else: ?>
    <div class="aspect-[4/3] max-h-80 flex items-center justify-center bg-slate-200" data-tooltip="Sem imagem disponível">
      <i class="ri-image-line text-5xl text-slate-400"></i>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($gallery) && count($gallery) > 0): ?>
    <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full" data-tooltip="Total de fotos">
      <i class="ri-image-line mr-1"></i>
      <?php echo count($gallery) + 1; ?> fotos
    </div>
    <?php endif; ?>
  </div>

  <!-- Main Content -->
  <div class="px-4 py-6 space-y-4 max-w-4xl mx-auto">

    <!-- Price & Title Card -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
          <span class="text-2xl font-bold text-emerald-600" data-tooltip="Preço do item"><?php echo esc_html($price_display); ?></span>
          <h1 class="text-lg font-bold text-slate-900 mt-1"><?php echo esc_html($title); ?></h1>
        </div>
        <span class="shrink-0 px-3 py-1 bg-slate-100 text-slate-600 text-xs font-medium rounded-full" data-tooltip="Condição do item">
          <?php echo esc_html($condition_display); ?>
        </span>
      </div>
      
      <div class="flex flex-wrap gap-3 mt-4 text-sm text-slate-500">
        <?php if ($location): ?>
        <span class="flex items-center gap-1" data-tooltip="Localização">
          <i class="ri-map-pin-line"></i>
          <?php echo esc_html($location); ?>
        </span>
        <?php endif; ?>
        <span class="flex items-center gap-1" data-tooltip="Data de publicação">
          <i class="ri-time-line"></i>
          <?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> atrás
        </span>
        <span class="flex items-center gap-1" data-tooltip="Visualizações">
          <i class="ri-eye-line"></i>
          <?php echo esc_html($views_count + 1); ?> views
        </span>
      </div>
    </div>

    <!-- Description Card -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Detalhes do produto">Descrição</h2>
      <?php if ($content): ?>
      <div class="text-slate-700 text-sm leading-relaxed prose prose-sm max-w-none">
        <?php echo wp_kses_post($content); ?>
      </div>
      <?php else: ?>
      <p class="text-slate-400 italic text-sm" data-tooltip="O anunciante não adicionou uma descrição">Nenhuma descrição fornecida.</p>
      <?php endif; ?>
    </div>

    <!-- Seller Card -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3" data-tooltip="Informações do vendedor">Anunciante</h2>
      <div class="flex items-center gap-4">
        <?php echo get_avatar($author_id, 56, '', $author_name, ['class' => 'h-14 w-14 rounded-full']); ?>
        <div class="flex-1 min-w-0">
          <a href="<?php echo esc_url(home_url('/id/' . $author_login)); ?>" class="font-bold text-slate-900 hover:text-orange-600" data-tooltip="Ver perfil do anunciante">
            <?php echo esc_html($author_name); ?>
          </a>
          <p class="text-sm text-slate-500">Membro desde <?php echo esc_html(date('M Y', strtotime($author->user_registered))); ?></p>
        </div>
      </div>
    </div>

    <!-- Contact Actions -->
    <div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5 space-y-3">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-1" data-tooltip="Formas de contato">Contato</h2>
      
      <?php if ($contact_whatsapp): ?>
      <a href="https://wa.me/55<?php echo esc_attr(preg_replace('/\D/', '', $contact_whatsapp)); ?>?text=<?php echo urlencode('Olá! Vi seu anúncio "' . $title . '" no Apollo e gostaria de mais informações.'); ?>" 
         target="_blank" rel="noopener"
         class="flex items-center justify-center gap-2 w-full py-3 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors"
         data-tooltip="Enviar mensagem via WhatsApp">
        <i class="ri-whatsapp-line text-xl"></i>
        Chamar no WhatsApp
      </a>
      <?php endif; ?>
      
      <?php if ($contact_phone): ?>
      <a href="tel:+55<?php echo esc_attr(preg_replace('/\D/', '', $contact_phone)); ?>" 
         class="flex items-center justify-center gap-2 w-full py-3 border border-slate-200 text-slate-700 rounded-xl font-medium hover:bg-slate-50 transition-colors"
         data-tooltip="Ligar para o anunciante">
        <i class="ri-phone-line text-xl"></i>
        <?php echo esc_html($contact_phone); ?>
      </a>
      <?php endif; ?>
      
      <?php if (!$contact_whatsapp && !$contact_phone): ?>
      <button class="flex items-center justify-center gap-2 w-full py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 transition-colors" 
              data-tooltip="Enviar mensagem pelo chat interno"
              data-action="internal-message" 
              data-user-id="<?php echo esc_attr($author_id); ?>">
        <i class="ri-mail-send-line text-xl"></i>
        Enviar Mensagem
      </button>
      <?php endif; ?>
    </div>

    <!-- Safety Tips -->
    <div class="aprioEXP-card-shell bg-amber-50 rounded-2xl border border-amber-200/50 p-4">
      <div class="flex gap-3">
        <i class="ri-shield-check-line text-xl text-amber-600 shrink-0"></i>
        <div>
          <h3 class="font-medium text-amber-800 text-sm" data-tooltip="Dicas de segurança para sua compra">Dicas de Segurança</h3>
          <ul class="text-xs text-amber-700 mt-1 space-y-0.5">
            <li>• Prefira encontros em locais públicos</li>
            <li>• Verifique o produto antes de pagar</li>
            <li>• Desconfie de preços muito baixos</li>
          </ul>
        </div>
      </div>
    </div>

  </div>

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
}
[data-tooltip]:hover::after,
[data-tooltip]:focus::after {
  content: '';
  position: absolute;
  bottom: calc(100% + 4px);
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: rgba(15, 23, 42, 0.95);
  z-index: 9999;
  pointer-events: none;
}
@keyframes tooltipFade {
  from { opacity: 0; transform: translateX(-50%) translateY(4px); }
  to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>

<?php get_footer(); ?>