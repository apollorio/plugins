<?php
/**
 * Partial: Community Hero Card
 * Componente reutilizável para hero/capa de comunidade ou núcleo
 * 
 * @var string $title Nome do grupo
 * @var string $description Descrição curta
 * @var string $cover_url URL da imagem de capa
 * @var string $avatar_url URL do avatar/logo
 * @var int $members_count Número de membros
 * @var array $tags Array de hashtags
 * @var bool $is_active Se está ativo (última atividade < 24h)
 * @var bool $is_verified Se é verificado (apenas núcleos)
 * @var string $type 'comunidade' | 'nucleo'
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? '';
$description = $description ?? '';
$cover_url = $cover_url ?? '';
$avatar_url = $avatar_url ?? '';
$members_count = (int) ($members_count ?? 0);
$tags = is_array($tags ?? null) ? $tags : [];
$is_active = (bool) ($is_active ?? false);
$is_verified = (bool) ($is_verified ?? false);
$type = $type ?? 'comunidade';

// Default gradient based on type
$default_gradient = $type === 'nucleo' 
    ? 'from-orange-500 via-red-500 to-pink-500'
    : 'from-fuchsia-500 via-sky-400 to-emerald-400';

// Badge icon
$badge_icon = $type === 'nucleo' ? 'ri-fire-fill' : 'ri-vip-crown-2-line';
$badge_color = $type === 'nucleo' ? 'text-orange-400' : 'text-amber-300';
?>
<div class="overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 relative">
  <div class="h-32 w-full">
    <?php if ($cover_url): ?>
    <img src="<?php echo esc_url($cover_url); ?>" alt="" class="w-full h-full object-cover opacity-90" loading="lazy" />
    <?php else: ?>
    <div class="w-full h-full bg-gradient-to-r <?php echo esc_attr($default_gradient); ?> opacity-80"></div>
    <?php endif; ?>
  </div>
  <div class="absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent">
    <div class="inline-flex items-center gap-2 mb-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/40 border border-white/20 overflow-hidden">
        <?php if ($avatar_url): ?>
        <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="w-full h-full object-cover <?php echo $type === 'comunidade' ? 'rounded-full' : ''; ?>" />
        <?php else: ?>
        <i class="<?php echo esc_attr($badge_icon); ?> text-lg <?php echo esc_attr($badge_color); ?>"></i>
        <?php endif; ?>
      </span>
      <?php if ($is_verified): ?>
      <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-500 border border-white/15 px-2.5 py-1">
        <i class="ri-verified-badge-fill text-[11px] text-white"></i>
        <span class="text-[11px] text-white font-medium uppercase tracking-wide">Verificado</span>
      </span>
      <?php elseif ($is_active): ?>
      <span class="inline-flex items-center gap-1.5 rounded-full bg-black/40 border border-white/15 px-2.5 py-1">
        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-[11px] text-slate-100 font-medium uppercase tracking-wide"><?php echo $type === 'nucleo' ? 'Ativo' : 'Comunidade ativa'; ?></span>
      </span>
      <?php endif; ?>
    </div>
    <h2 class="text-base md:text-lg font-semibold text-white leading-snug">
      <?php echo esc_html($title); ?>
    </h2>
    <?php if ($description): ?>
    <p class="text-[11px] text-slate-200 mt-1">
      <?php echo esc_html(wp_trim_words($description, 20)); ?>
    </p>
    <?php endif; ?>
    <div class="mt-3 flex items-center gap-3 text-[11px] text-slate-300">
      <span class="inline-flex items-center gap-1">
        <i class="ri-user-3-line text-xs"></i> <?php echo esc_html(number_format_i18n($members_count)); ?> membros
      </span>
      <?php if (!empty($tags)): ?>
      <span class="inline-flex items-center gap-1">
        <i class="ri-hashtag text-xs"></i> #<?php echo esc_html(implode(' #', array_slice($tags, 0, 3))); ?>
      </span>
      <?php endif; ?>
    </div>
  </div>
</div>

