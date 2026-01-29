<?php
/**
 * Post User Partial - Apollo Social Feed
 *
 * Matches approved design: social - feed main.html
 * Renders individual user posts with proper CSS classes and structure.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post      = $post_data ?? array();
$post_type = $post['type'] ?? 'user_post';
$data      = $post['data'] ?? array();

// Bolha highlight: verifica se autor está na bolha do usuário atual.
$is_bolha = $post['is_bolha'] ?? false;
?>

<article class="ap-social-post feed-item" data-type="all">
	<div class="ap-social-avatar"
	style="background-image: url('<?php echo esc_url( $data['author']['avatar'] ?? '' ); ?>');">
	</div>
	<div class="ap-social-card">
	<div style="margin-bottom: 15px;">
		<p>
		<span class="ap-social-username">
			<?php echo esc_html( $data['author']['name'] ?? '' ); ?>
		</span>
		<?php
		// Render badges
		$author_id = isset( $data['author']['id'] ) ? (int) $data['author']['id'] : 0;
		if ( $author_id && function_exists( 'apollo_social_get_user_badges' ) ) {
			$badges = apollo_social_get_user_badges( $author_id );
			if ( ! empty( $badges ) ) {
				foreach ( $badges as $badge ) {
					?>
				<span class="ap-social-badge <?php echo esc_attr( $badge['class'] ); ?>">
					<?php echo esc_html( $badge['label'] ); ?>
				</span>
					<?php
				}
			}
		}
		?>
		</p>
		<p class="ap-social-second" style="margin-top: 4px;">
		<?php if ( $is_bolha ) : ?>
			<span>Núcleo Bolha</span>
			<span style="margin: 0 4px;">•</span>
		<?php endif; ?>
		<span>@<?php echo esc_html( $data['author']['name'] ?? '' ); ?></span>
		<span style="margin: 0 4px;">•</span>
		<span><?php echo esc_html( human_time_diff( strtotime( $data['date'] ?? 'now' ), current_time( 'timestamp' ) ) . ' atrás' ); ?></span>
		</p>
	</div>

	<p class="ap-social-content">
		<?php echo wp_kses_post( $data['content'] ?? $data['excerpt'] ?? '' ); ?>
	</p>

	<?php if ( ! empty( $data['thumbnail'] ) ) : ?>
		<div class="ap-media-wrapper" style="margin-top: 15px;">
		<img src="<?php echo esc_url( $data['thumbnail'] ); ?>"
			alt="<?php echo esc_attr( $data['title'] ?? '' ); ?>"
			style="width: 100%; height: auto; display: block;" />
		</div>
	<?php endif; ?>

	<?php
	// Media embeds
	if ( ! empty( $data['media_embeds'] ) ) :
		if ( ! empty( $data['media_embeds']['spotify'] ) ) :
			foreach ( $data['media_embeds']['spotify'] as $spotify ) :
				?>
			<div class="ap-media-wrapper ap-spotify-player">
				<?php
				if ( class_exists( '\Apollo\Helpers\MediaEmbedHelper' ) ) {
					echo \Apollo\Helpers\MediaEmbedHelper::renderSpotifyEmbed(
						$spotify['id'],
						esc_html( $spotify['type'] ),
						array(
							'width'  => '100%',
							'height' => esc_html( $spotify['type'] ) === 'track' ? '152' : '352',
						)
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
			<div class="ap-media-wrapper ap-soundcloud-player">
				<?php
				if ( class_exists( '\Apollo\Helpers\MediaEmbedHelper' ) ) {
					echo \Apollo\Helpers\MediaEmbedHelper::renderSoundCloudEmbed(
						$soundcloud['url'],
						array(
							'width'  => '100%',
							'height' => '166',
						)
					);
				}
				?>
			</div>
				<?php
			endforeach;
		endif;
	endif;
	?>

	<div class="ap-post-actions">
		<button class="ap-action-btn like-btn"
		data-content-type="apollo_social_post"
		data-content-id="<?php echo esc_attr( $data['id'] ?? 0 ); ?>"
		data-liked="<?php echo $data['user_liked'] ? 'true' : 'false'; ?>">
		<i class="ri-heart-3-line"></i>
		<span><?php echo esc_html( $data['like_count'] ?? 0 ); ?></span>
		</button>
		<button class="ap-action-btn">
		<i class="ri-chat-3-line"></i>
		<?php echo esc_html( $data['comment_count'] ?? 0 ); ?>
		</button>
		<button class="ap-action-btn">
		<i class="ri-bookmark-line"></i>
		</button>
	</div>
	</div>
</article>
