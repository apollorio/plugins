<?php
/**
 * FASE 2: Partial template para post de usuário
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post      = $post_data ?? [];
$post_type = $post['type'] ?? 'user_post';
$data      = $post['data'] ?? [];
?>
<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 overflow-hidden hover:shadow-md transition-all apollo-feed-card" 
		data-feed-card 
		data-content-type="apollo_social_post" 
		data-content-id="<?php echo esc_attr( $data['id'] ?? 0 ); ?>">
	<div class="p-5">
	<div class="flex gap-3">
		<div class="h-11 w-11 rounded-full overflow-hidden shrink-0 ring-2 ring-orange-100">
		<img src="<?php echo esc_url( $data['author']['avatar'] ?? '' ); ?>" 
			alt="<?php echo esc_attr( $data['author']['name'] ?? '' ); ?>" 
			class="h-full w-full object-cover" />
		</div>
		<div class="flex-1 min-w-0">
		<div class="flex items-center justify-between">
			<div>
			<h3 class="font-semibold text-[15px] text-slate-900">
				<?php echo esc_html( $data['author']['name'] ?? '' ); ?>
			</h3>
			<p class="text-[13px] text-slate-500">
				@<?php echo esc_html( $data['author']['name'] ?? '' ); ?> · 
				<?php echo esc_html( human_time_diff( strtotime( $data['date'] ?? 'now' ), current_time( 'timestamp' ) ) . ' atrás' ); ?>
			</p>
			</div>
			<button class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-slate-100">
			<i class="ri-more-2-line text-slate-400"></i>
			</button>
		</div>
		
		<p class="mt-3 text-[15px] text-slate-800 leading-relaxed">
			<?php echo wp_kses_post( $data['content'] ?? $data['excerpt'] ?? '' ); ?>
		</p>

		<?php if ( ! empty( $data['thumbnail'] ) ) : ?>
			<div class="mt-4 rounded-xl overflow-hidden">
			<img src="<?php echo esc_url( $data['thumbnail'] ); ?>" 
				alt="<?php echo esc_attr( $data['title'] ?? '' ); ?>" 
				class="w-full h-auto" />
			</div>
		<?php endif; ?>

		<?php
		// P0-5: Render Spotify/SoundCloud embeds
		if ( ! empty( $data['media_embeds'] ) ) :
			if ( ! empty( $data['media_embeds']['spotify'] ) ) :
				foreach ( $data['media_embeds']['spotify'] as $spotify ) :
					?>
					<div class="mt-4 rounded-xl overflow-hidden">
						<?php
						if ( class_exists( '\Apollo\Helpers\MediaEmbedHelper' ) ) {
							echo \Apollo\Helpers\MediaEmbedHelper::renderSpotifyEmbed(
								$spotify['id'],
								$spotify['type'],
								[
									'width'  => '100%',
									'height' => $spotify['type'] === 'track' ? '152' : '352',
								]
							);
						}
						?>
					</div>
					<?php
				endforeach;
			endif;
			if ( ! empty( $data['media_embeds']['soundcloud'] ) ) :
				foreach ( $data['media_embeds']['soundcloud'] as $soundcloud ) :
					?>
					<div class="mt-4 rounded-xl overflow-hidden">
						<?php
						if ( class_exists( '\Apollo\Helpers\MediaEmbedHelper' ) ) {
							echo \Apollo\Helpers\MediaEmbedHelper::renderSoundCloudEmbed(
								$soundcloud['url'],
								[
									'width'  => '100%',
									'height' => '166',
								]
							);
						}
						?>
					</div>
					<?php
				endforeach;
			endif;
		endif;
		?>
		</div>
	</div>
	</div>

	<!-- Actions -->
	<div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
	<button class="flex items-center gap-2 text-slate-600 hover:text-orange-600 transition-colors group apollo-feed-like-btn" 
			data-content-type="apollo_social_post" 
			data-content-id="<?php echo esc_attr( $data['id'] ?? 0 ); ?>"
			data-liked="<?php echo $data['user_liked'] ? 'true' : 'false'; ?>">
		<i class="ri-heart-3-<?php echo $data['user_liked'] ? 'fill' : 'line'; ?> text-xl group-hover:scale-110 transition-transform"></i>
		<span class="text-[14px] font-medium apollo-like-count"><?php echo esc_html( $data['like_count'] ?? 0 ); ?></span>
	</button>
	<button class="flex items-center gap-2 text-slate-600 hover:text-blue-600 transition-colors group apollo-feed-comment-btn" 
			data-post-id="<?php echo esc_attr( $data['id'] ?? 0 ); ?>">
		<i class="ri-chat-3-line text-xl group-hover:scale-110 transition-transform"></i>
		<span class="text-[14px] font-medium apollo-comment-count"><?php echo esc_html( $data['comment_count'] ?? 0 ); ?></span>
	</button>
	<button class="flex items-center gap-2 text-slate-600 hover:text-green-600 transition-colors group apollo-feed-share-btn" 
			data-permalink="<?php echo esc_url( $data['permalink'] ?? '' ); ?>"
			data-title="<?php echo esc_attr( $data['title'] ?? '' ); ?>">
		<i class="ri-share-forward-line text-xl group-hover:scale-110 transition-transform"></i>
		<span class="text-[14px] font-medium">Compartilhar</span>
	</button>
	<button class="flex items-center gap-2 text-slate-600 hover:text-slate-900 transition-colors">
		<i class="ri-bookmark-line text-xl"></i>
	</button>
	</div>

	<!-- Comments Section (hidden by default) -->
	<div class="apollo-comments-section hidden px-5 pb-3 border-t border-slate-100">
	<div class="apollo-comments-list mt-3 space-y-3">
		<!-- Comentários serão carregados aqui via AJAX -->
	</div>
	<form class="apollo-comment-form mt-3 flex gap-2">
		<?php wp_nonce_field( 'apollo_comment_nonce', 'apollo_comment_nonce' ); ?>
		<input type="hidden" name="post_id" value="<?php echo esc_attr( $data['id'] ?? 0 ); ?>">
		<input type="text" 
			name="comment" 
			placeholder="Escreva um comentário..." 
			class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
		<button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors">
		Enviar
		</button>
	</form>
	</div>
</article>

