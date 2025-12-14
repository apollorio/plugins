<?php
/**
 * FASE 2: Partial template para evento no feed
 * Inclui destaque visual para eventos de usuários na bolha.
 */
if (! defined('ABSPATH')) {
    exit;
}

$post = $post_data    ?? [];
$data = $post['data'] ?? [];

// Bolha highlight: verifica se autor está na bolha do usuário atual.
$is_bolha        = $post['is_bolha']        ?? false;
$bolha_highlight = $post['bolha_highlight'] ?? '';
$bolha_classes   = $is_bolha ? 'apollo-bolha-featured ring-2 ring-orange-300 bg-gradient-to-br from-orange-50/50 to-white' : '';
?>
<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden hover:shadow-md transition-all apollo-feed-card apollo-event-card <?php echo esc_attr($bolha_classes); ?>"
		data-feed-card
		data-content-type="event_listing"
		data-content-id="<?php echo esc_attr($data['id'] ?? 0); ?>"
		<?php echo $is_bolha ? 'data-bolha="true"' : ''; ?>>
	<?php if ($is_bolha) : ?>
	<!-- Bolha Badge: sutil, apenas indicador visual -->
	<div class="px-5 pt-3 pb-0 flex items-center gap-1.5 text-orange-600">
		<i class="ri-bubble-chart-line text-xs"></i>
		<span class="text-[11px] font-medium uppercase tracking-wider">Da sua Bolha</span>
	</div>
	<?php endif; ?>
	<div class="p-5 <?php echo $is_bolha ? 'pt-2' : ''; ?>">
	<div class="flex gap-3">
		<div class="h-11 w-11 rounded-full overflow-hidden shrink-0 ring-2 ring-orange-100">
		<img src="<?php echo esc_url($data['author']['avatar'] ?? ''); ?>"
			alt="<?php echo esc_attr($data['author']['name'] ?? ''); ?>"
			class="h-full w-full object-cover" />
		</div>
		<div class="flex-1 min-w-0">
		<div class="flex items-center justify-between">
			<div>
			<section class="ap-social-user-data">
				<p>
					<span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-medium">Evento</span>
					<span class="ap-social-username">
						<?php echo esc_html($data['author']['name'] ?? ''); ?>
					</span>
					<?php
                    // Render badges
                    $author_id = isset($data['author']['id']) ? (int) $data['author']['id'] : 0;
if ($author_id && function_exists('apollo_social_get_user_badges')) {
    $badges = apollo_social_get_user_badges($author_id);
    if (! empty($badges)) {
        foreach ($badges as $badge) {
            ?>
								<span class="ap-social-badge <?php echo esc_attr($badge['class']); ?>">
									<?php echo esc_html($badge['label']); ?>
								</span>
								<?php
        }
    }
}
?>
				</p>
				<p class="text-[13px] text-slate-500 ap-social-second">
					@<?php echo esc_html($data['author']['name'] ?? ''); ?> ·
					<span class="ap-social-second"><?php echo esc_html(human_time_diff(strtotime($data['date'] ?? 'now'), current_time('timestamp')) . ' atrás'); ?></span>
				</p>
			</section>
			</div>
			<button class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-slate-100">
			<i class="ri-more-2-line text-slate-400"></i>
			</button>
		</div>

		<h4 class="mt-3 text-lg font-bold text-slate-900">
			<?php echo esc_html($data['title'] ?? ''); ?>
		</h4>

		<?php if (! empty($data['start_date']) || ! empty($data['local'])) : ?>
			<div class="mt-2 flex flex-wrap gap-3 text-sm text-slate-600">
			<?php if (! empty($data['start_date'])) : ?>
				<span class="flex items-center gap-1">
				<i class="ri-calendar-line"></i>
				<?php echo esc_html(date_i18n('d/m/Y', strtotime($data['start_date']))); ?>
				<?php if (! empty($data['start_time'])) : ?>
					às <?php echo esc_html($data['start_time']); ?>
				<?php endif; ?>
				</span>
			<?php endif; ?>
			<?php if (! empty($data['local'])) : ?>
				<span class="flex items-center gap-1">
				<i class="ri-map-pin-line"></i>
				<?php echo esc_html($data['local']); ?>
				</span>
			<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if (! empty($data['excerpt'])) : ?>
			<p class="mt-3 text-[15px] text-slate-700 leading-relaxed">
			<?php echo esc_html(wp_trim_words($data['excerpt'], 30)); ?>
			</p>
		<?php endif; ?>

		<?php if (! empty($data['thumbnail'])) : ?>
			<div class="mt-4 rounded-xl overflow-hidden">
			<img src="<?php echo esc_url($data['thumbnail']); ?>"
				alt="<?php echo esc_attr($data['title'] ?? ''); ?>"
				class="w-full h-auto" />
			</div>
		<?php endif; ?>
		</div>
	</div>
	</div>

	<!-- Actions -->
	<div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
	<button class="flex items-center gap-2 text-slate-600 hover:text-orange-600 transition-colors group apollo-feed-like-btn"
			data-content-type="event_listing"
			data-content-id="<?php echo esc_attr($data['id'] ?? 0); ?>"
			data-liked="<?php echo $data['user_liked'] ? 'true' : 'false'; ?>">
		<i class="ri-heart-3-<?php echo $data['user_liked'] ? 'fill' : 'line'; ?> text-xl group-hover:scale-110 transition-transform"></i>
		<span class="text-[14px] font-medium apollo-like-count"><?php echo esc_html($data['like_count'] ?? 0); ?></span>
	</button>
	<button class="flex items-center gap-2 text-slate-600 hover:text-green-600 transition-colors group apollo-feed-share-btn"
			data-permalink="<?php echo esc_url($data['permalink'] ?? ''); ?>"
			data-title="<?php echo esc_attr($data['title'] ?? ''); ?>">
		<i class="ri-share-forward-line text-xl group-hover:scale-110 transition-transform"></i>
		<span class="text-[14px] font-medium">Compartilhar</span>
	</button>
	<button class="flex items-center gap-2 text-slate-600 hover:text-yellow-600 transition-colors apollo-feed-favorite-btn"
			data-event-id="<?php echo esc_attr($data['id'] ?? 0); ?>"
			data-content-type="event_listing"
			data-favorited="<?php echo $data['user_favorited'] ? 'true' : 'false'; ?>">
		<i class="ri-star-<?php echo $data['user_favorited'] ? 'fill' : 'line'; ?> text-xl"></i>
		<span class="text-[14px] font-medium">Salvar</span>
	</button>
	<a href="<?php echo esc_url($data['permalink'] ?? '#'); ?>"
		class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors">
		Ver Evento
	</a>
	</div>
</article>

