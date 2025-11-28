<?php
/**
 * Partial: Member Chip
 * Componente reutilizável para chip de membro
 * 
 * @var int $member_id User ID
 * @var string $variant 'default' | 'small' | 'large'
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$member = get_userdata($member_id ?? 0);
if (!$member) {
    return;
}

$variant = $variant ?? 'default';
$profile_url = home_url('/id/' . $member->user_login);

// Size classes based on variant
$sizes = [
    'small' => ['chip' => 'px-2 py-0.5 text-[10px]', 'avatar' => 'h-4 w-4'],
    'default' => ['chip' => 'px-2.5 py-1 text-[11px]', 'avatar' => 'h-5 w-5'],
    'large' => ['chip' => 'px-3 py-1.5 text-[12px]', 'avatar' => 'h-6 w-6']
];

$size = $sizes[$variant] ?? $sizes['default'];
?>
<a href="<?php echo esc_url($profile_url); ?>" class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 <?php echo esc_attr($size['chip']); ?> hover:bg-slate-200 transition-colors">
  <span class="<?php echo esc_attr($size['avatar']); ?> rounded-full bg-slate-300 overflow-hidden">
    <?php echo get_avatar($member_id, 24, '', $member->display_name, ['class' => 'w-full h-full']); ?>
  </span>
  <span>@<?php echo esc_html($member->user_login); ?></span>
</a>

