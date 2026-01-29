<?php
/**
 * Post Event Partial - Apollo Social Feed
 *
 * Matches approved design: social - feed main.html
 * Renders event posts with event banner and proper CSS classes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post = $post_data ?? array();
$data = $post['data'] ?? array();

// Bolha highlight: verifica se autor está na bolha do usuário atual.
$is_bolha = $post['is_bolha'] ?? false;
?>

<article class="ap-social-post feed-item" data-type="events">
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
		<?php echo esc_html( $data['excerpt'] ?? '' ); ?>
	</p>

	<div class="ap-event-banner"
		style="background-image: url('<?php echo esc_url( $data['thumbnail'] ?? '' ); ?>');">
		<div>
		<div class="ap-event-title"><?php echo esc_html( $data['title'] ?? '' ); ?></div>
		<div class="ap-event-date">
			<?php if ( ! empty( $data['start_date'] ) ) : ?>
				<?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data['start_date'] ) ) ); ?>
				<?php if ( ! empty( $data['start_time'] ) ) : ?>
				às <?php echo esc_html( $data['start_time'] ); ?>
			<?php endif; ?>
			<?php endif; ?>
			<?php if ( ! empty( $data['local'] ) ) : ?>
			• <?php echo esc_html( $data['local'] ); ?>
			<?php endif; ?>
		</div>
		</div>
	</div>

	<div class="ap-post-actions">
		<button class="ap-action-btn like-btn"
		data-content-type="event_listing"
		data-content-id="<?php echo esc_attr( $data['id'] ?? 0 ); ?>"
		data-liked="<?php echo $data['user_liked'] ? 'true' : 'false'; ?>">
		<i class="ri-heart-3-line"></i>
		<span><?php echo esc_html( $data['like_count'] ?? 0 ); ?></span>
		</button>
		<button class="ap-action-btn">
		<i class="ri-chat-3-line"></i>
		<?php echo esc_html( $data['comment_count'] ?? 0 ); ?>
		</button>
		<button class="ap-action-btn" style="color: #f97316; font-weight: 600;">
		<i class="ri-calendar-event-line"></i>
		Ver Evento
		</button>
	</div>
	</div>
</article>
