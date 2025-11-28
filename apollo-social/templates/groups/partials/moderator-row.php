<?php
/**
 * Partial: Moderator Row
 * Componente reutilizável para linha de moderador/responsável
 * 
 * @var int $user_id User ID
 * @var string $role 'founder' | 'co-founder' | 'moderator' | 'curator'
 * @var bool $is_online Optional online status
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$user = get_userdata($user_id ?? 0);
if (!$user) {
    return;
}

$role = $role ?? 'moderator';
$is_online = $is_online ?? false;
$profile_url = home_url('/id/' . $user->user_login);

// Role labels and styles
$role_config = [
    'founder' => [
        'label' => 'fundador(a)',
        'subtitle' => 'Donx',
        'badge_class' => 'bg-slate-900 text-white',
        'icon' => 'ri-star-smile-line'
    ],
    'co-founder' => [
        'label' => 'co-fundador',
        'subtitle' => 'Co-responsável',
        'badge_class' => 'bg-slate-100 text-slate-700',
        'icon' => 'ri-user-star-line'
    ],
    'moderator' => [
        'label' => 'moderador',
        'subtitle' => 'Moderação',
        'badge_class' => 'bg-slate-100 text-slate-700',
        'icon' => 'ri-shield-user-line'
    ],
    'curator' => [
        'label' => 'curadoria',
        'subtitle' => 'Co-responsável',
        'badge_class' => 'bg-slate-100 text-slate-700',
        'icon' => 'ri-command-line'
    ]
];

$config = $role_config[$role] ?? $role_config['moderator'];
?>
<div class="flex items-center gap-3">
  <a href="<?php echo esc_url($profile_url); ?>" class="h-8 w-8 rounded-full overflow-hidden bg-slate-100 shrink-0">
    <?php echo get_avatar($user_id, 32, '', $user->display_name, ['class' => 'w-full h-full object-cover']); ?>
  </a>
  <div class="flex-1 min-w-0">
    <a href="<?php echo esc_url($profile_url); ?>" class="text-[13px] font-semibold text-slate-900 hover:text-slate-700 block truncate">
      <?php echo esc_html($user->display_name); ?>
    </a>
    <p class="text-[11px] text-slate-500 truncate">@<?php echo esc_html($user->user_login); ?> · <?php echo esc_html($config['subtitle']); ?></p>
  </div>
  <?php if ($is_online): ?>
  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] px-2 py-0.5 shrink-0">
    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> online
  </span>
  <?php else: ?>
  <span class="inline-flex items-center gap-1 rounded-full <?php echo esc_attr($config['badge_class']); ?> text-[10px] px-2 py-0.5 shrink-0">
    <i class="<?php echo esc_attr($config['icon']); ?> text-[11px]"></i> <?php echo esc_html($config['label']); ?>
  </span>
  <?php endif; ?>
</div>

