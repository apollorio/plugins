<?php
/**
 * Partial: Community Post Card
 * Componente reutilizável para posts de comunidade/núcleo
 * 
 * @var array $post_data Post data array
 * @var int $group_id Group/Community ID
 * @var int $creator_id Creator user ID
 * @var array $moderators Array of moderator user IDs
 * 
 * @package Apollo_Social
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Extract post data
$post_id = (int) ($post_data['id'] ?? 0);
$post_author_id = (int) ($post_data['author_id'] ?? 0);
$post_content = $post_data['content'] ?? '';
$post_date = $post_data['date'] ?? '';
$post_location = $post_data['location'] ?? '';
$post_tags = $post_data['tags'] ?? [];
$post_image = $post_data['image'] ?? '';
$post_likes = (int) ($post_data['likes'] ?? 0);
$post_comments_count = (int) ($post_data['comments_count'] ?? 0);
$is_notice = (bool) ($post_data['is_notice'] ?? false);
$featured_comment = $post_data['featured_comment'] ?? null;

// Get author data
$post_author = get_userdata($post_author_id);

// Check if author is owner or moderator
$is_owner = ($post_author_id === ($creator_id ?? 0));
$is_mod = in_array($post_author_id, $moderators ?? [], true);

// Format date
$time_ago = '';
if ($post_date) {
    $time_ago = human_time_diff(strtotime($post_date), current_time('timestamp'));
}

// Tags array
if (!is_array($post_tags)) {
    $post_tags = $post_tags ? array_map('trim', explode(',', $post_tags)) : [];
}
?>
<article class="bg-white/95 border border-slate-200 rounded-2xl px-4 py-3 md:px-5 md:py-4 shadow-sm" data-post-id="<?php echo esc_attr($post_id); ?>">
  <header class="flex items-start gap-3">
    <div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100">
      <?php if ($post_author): ?>
      <?php echo get_avatar($post_author_id, 36, '', $post_author->display_name, ['class' => 'h-full w-full object-cover']); ?>
      <?php else: ?>
      <div class="h-full w-full bg-slate-200 flex items-center justify-center">
        <i class="ri-user-line text-slate-400"></i>
      </div>
      <?php endif; ?>
    </div>
    <div class="flex-1">
      <div class="flex items-center justify-between gap-2">
        <div>
          <div class="flex items-center gap-1.5">
            <span class="text-[13px] font-semibold text-slate-900"><?php echo esc_html($post_author->display_name ?? 'Usuário'); ?></span>
            <?php if ($is_owner || $is_mod): ?>
            <span class="text-[11px] text-slate-400">@<?php echo esc_html($post_author->user_login ?? ''); ?> · <?php echo $is_owner ? 'Responsável' : 'Moderação'; ?></span>
            <?php else: ?>
            <span class="text-[11px] text-slate-400">@<?php echo esc_html($post_author->user_login ?? ''); ?></span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-2 text-[11px] text-slate-400">
            <?php if ($time_ago): ?>
            <span>há <?php echo esc_html($time_ago); ?></span>
            <?php endif; ?>
            <?php if ($post_location): ?>
            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
            <span class="inline-flex items-center gap-1">
              <i class="ri-map-pin-2-line text-[10px]"></i> <?php echo esc_html($post_location); ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($is_notice): ?>
        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5">
          <i class="ri-notification-3-line text-[11px]"></i> aviso
        </span>
        <?php else: ?>
        <button class="text-slate-400 hover:text-slate-700" data-action="post-menu" data-post-id="<?php echo esc_attr($post_id); ?>">
          <i class="ri-more-2-fill"></i>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="mt-3 text-[13px] text-slate-700 leading-relaxed">
    <?php echo wp_kses_post($post_content); ?>
  </div>

  <?php if ($post_image): ?>
  <div class="mt-3 rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
    <img src="<?php echo esc_url($post_image); ?>" alt="" class="w-full h-auto" loading="lazy" />
  </div>
  <?php endif; ?>

  <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
    <?php if (!empty($post_tags)): ?>
    <div class="inline-flex flex-wrap gap-1.5 text-[11px]">
      <?php foreach (array_slice($post_tags, 0, 3) as $tag): ?>
      <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2 py-0.5">
        <i class="ri-hashtag text-[10px]"></i> <?php echo esc_html($tag); ?>
      </span>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    <div class="flex items-center gap-4 text-[12px] text-slate-500">
      <button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="like-post" data-post-id="<?php echo esc_attr($post_id); ?>">
        <i class="ri-heart-3-line text-sm"></i> <?php echo esc_html($post_likes); ?>
      </button>
      <button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="view-comments" data-post-id="<?php echo esc_attr($post_id); ?>">
        <i class="ri-message-2-line text-sm"></i> <?php echo esc_html($post_comments_count); ?>
      </button>
      <button class="inline-flex items-center gap-1 hover:text-slate-900" data-action="share-post" data-post-id="<?php echo esc_attr($post_id); ?>">
        <i class="ri-share-forward-line text-sm"></i>
      </button>
    </div>
  </div>

  <!-- Featured comment -->
  <?php if ($featured_comment): 
    $comment_author_id = (int) ($featured_comment['author_id'] ?? 0);
    $comment_author = $comment_author_id ? get_userdata($comment_author_id) : null;
    $comment_content = $featured_comment['content'] ?? '';
    $comment_date = $featured_comment['date'] ?? '';
  ?>
  <div class="mt-3 border-t border-dashed border-slate-200 pt-3">
    <div class="flex items-start gap-2.5">
      <div class="h-7 w-7 rounded-full overflow-hidden bg-slate-100">
        <?php if ($comment_author): ?>
        <?php echo get_avatar($comment_author_id, 28, '', $comment_author->display_name, ['class' => 'h-full w-full object-cover']); ?>
        <?php else: ?>
        <div class="h-full w-full bg-slate-200 flex items-center justify-center">
          <i class="ri-user-line text-xs text-slate-400"></i>
        </div>
        <?php endif; ?>
      </div>
      <div class="flex-1">
        <div class="flex items-center gap-1.5">
          <span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html($comment_author ? $comment_author->display_name : ($featured_comment['author_name'] ?? 'Usuário')); ?></span>
          <?php if ($comment_date): ?>
          <span class="text-[10px] text-slate-400">há <?php echo esc_html(human_time_diff(strtotime($comment_date), current_time('timestamp'))); ?></span>
          <?php endif; ?>
        </div>
        <p class="text-[12px] text-slate-700 leading-relaxed">
          <?php echo esc_html(wp_trim_words($comment_content, 30)); ?>
        </p>
      </div>
    </div>
  </div>
  <?php endif; ?>
</article>

