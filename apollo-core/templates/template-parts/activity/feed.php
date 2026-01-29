<?php

declare(strict_types=1);
/**
 * Activity Stream - Main Feed
 * File: template-parts/activity/feed.php
 * REST: GET /activity, POST /activity
 */

$user_id   = get_current_user_id();
$feed_type = isset( $_GET['feed'] ) ? sanitize_text_field( $_GET['feed'] ) : 'all';
?>

<div class="apollo-activity-feed" data-feed-type="<?php echo esc_attr( $feed_type ); ?>">

	<?php if ( $user_id ) : ?>
	<div class="activity-composer">
		<div class="composer-avatar">
			<img src="<?php echo apollo_get_user_avatar( $user_id, 48 ); ?>" alt="<?php esc_attr_e( 'Seu avatar', 'apollo' ); ?>">
		</div>
		<form class="composer-form" id="activity-composer">
			<label for="activity-content" class="sr-only">O que está acontecendo?</label>
			<textarea id="activity-content" name="content" placeholder="<?php esc_attr_e( 'O que está acontecendo?', 'apollo' ); ?>" rows="2" title="<?php esc_attr_e( 'Escreva uma publicação', 'apollo' ); ?>"></textarea>
			<div class="composer-actions">
				<div class="composer-attachments">
					<button type="button" class="btn-attach" data-type="image" title="<?php esc_attr_e( 'Adicionar imagem', 'apollo' ); ?>" aria-label="<?php esc_attr_e( 'Adicionar imagem', 'apollo' ); ?>"><i class="ri-image-line"></i></button>
					<button type="button" class="btn-attach" data-type="link" title="<?php esc_attr_e( 'Adicionar link', 'apollo' ); ?>" aria-label="<?php esc_attr_e( 'Adicionar link', 'apollo' ); ?>"><i class="ri-link"></i></button>
					<button type="button" class="btn-attach" data-type="poll" title="<?php esc_attr_e( 'Criar enquete', 'apollo' ); ?>" aria-label="<?php esc_attr_e( 'Criar enquete', 'apollo' ); ?>"><i class="ri-bar-chart-horizontal-line"></i></button>
				</div>
				<button type="submit" class="btn btn-primary btn-sm" title="<?php esc_attr_e( 'Publicar', 'apollo' ); ?>">Publicar</button>
			</div>
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
	</div>
	<?php endif; ?>

	<div class="feed-filters">
		<a href="?feed=all" class="filter-tab <?php echo $feed_type === 'all' ? 'active' : ''; ?>">Tudo</a>
		<?php if ( $user_id ) : ?>
		<a href="?feed=friends" class="filter-tab <?php echo $feed_type === 'friends' ? 'active' : ''; ?>">Amigos</a>
		<a href="?feed=mentions" class="filter-tab <?php echo $feed_type === 'mentions' ? 'active' : ''; ?>">Menções</a>
		<?php endif; ?>
	</div>

	<div class="activity-list" id="activity-list">
		<?php
		switch ( $feed_type ) {
			case 'friends':
				$activities = apollo_get_friends_activity( $user_id );
				break;
			case 'mentions':
				$activities = apollo_get_my_activity( $user_id );
				break;
			default:
				$activities = apollo_get_activity_feed( array( 'per_page' => 20 ) );
		}

		if ( ! empty( $activities ) ) :
			foreach ( $activities as $activity ) :
				$author = get_userdata( $activity->user_id );
				if ( ! $author ) {
					continue;
				}
				?>
		<article class="activity-item" data-activity-id="<?php echo $activity->id; ?>">
			<div class="activity-avatar">
				<a href="<?php echo home_url( '/membro/' . $author->user_nicename ); ?>">
					<img src="<?php echo apollo_get_user_avatar( $author->ID, 48 ); ?>" alt="<?php echo esc_attr( 'Avatar de ' . $author->display_name ); ?>">
				</a>
			</div>
			<div class="activity-content">
				<div class="activity-header">
					<a href="<?php echo home_url( '/membro/' . $author->user_nicename ); ?>" class="activity-author">
						<?php echo esc_html( $author->display_name ); ?>
					</a>
					<?php if ( get_user_meta( $author->ID, 'verified', true ) ) : ?>
					<i class="ri-verified-badge-fill verified-icon"></i>
					<?php endif; ?>
					<span class="activity-meta">
						<span class="activity-action"><?php echo esc_html( $activity->action_type ?? '' ); ?></span>
						<span class="activity-time"><?php echo apollo_format_activity_time( $activity->created_at ); ?></span>
					</span>
				</div>
				<div class="activity-body">
					<?php echo wp_kses_post( $activity->content ); ?>
				</div>
				<?php if ( ! empty( $activity->attachment ) ) : ?>
				<div class="activity-attachment">
					<?php echo apollo_render_activity_attachment( $activity->attachment ); ?>
				</div>
				<?php endif; ?>
				<div class="activity-actions">
					<button class="action-btn btn-like <?php echo apollo_user_liked( $activity->id, $user_id ) ? 'liked' : ''; ?>"
							data-id="<?php echo $activity->id; ?>">
						<i class="ri-heart-<?php echo apollo_user_liked( $activity->id, $user_id ) ? 'fill' : 'line'; ?>"></i>
						<span class="count"><?php echo $activity->like_count ?? 0; ?></span>
					</button>
					<button class="action-btn btn-comment" data-id="<?php echo $activity->id; ?>">
						<i class="ri-chat-1-line"></i>
						<span class="count"><?php echo $activity->comment_count ?? 0; ?></span>
					</button>
					<button class="action-btn btn-share" data-id="<?php echo $activity->id; ?>">
						<i class="ri-share-line"></i>
					</button>
					<?php if ( $activity->user_id == $user_id ) : ?>
					<button class="action-btn btn-more" data-id="<?php echo $activity->id; ?>">
						<i class="ri-more-line"></i>
					</button>
					<?php endif; ?>
				</div>
			</div>
		</article>
				<?php
			endforeach;
		else :
			?>
		<div class="empty-state">
			<i class="ri-chat-smile-2-line"></i>
			<p>Nenhuma atividade ainda.</p>
		</div>
		<?php endif; ?>
	</div>

	<div class="feed-loader" id="feed-loader" style="display:none;">
		<i class="ri-loader-4-line spinning"></i> Carregando...
	</div>

</div>

<?php
/**
 * Helper: Render activity attachment
 */
function apollo_render_activity_attachment( $attachment ) {
	$data = is_string( $attachment ) ? json_decode( $attachment, true ) : $attachment;
	if ( ! $data ) {
		return '';
	}

	$type = $data['type'] ?? 'link';

	switch ( $type ) {
		case 'image':
			return '<img src="' . esc_url( $data['url'] ) . '" alt="" class="attachment-image">';
		case 'link':
			return '<a href="' . esc_url( $data['url'] ) . '" class="attachment-link" target="_blank">
                <span class="link-domain">' . parse_url( $data['url'], PHP_URL_HOST ) . '</span>
                <span class="link-title">' . esc_html( $data['title'] ?? $data['url'] ) . '</span>
            </a>';
		default:
			return '';
	}
}

/**
 * Helper: Check if user liked activity
 */
function apollo_user_liked( $activity_id, $user_id ) {
	if ( ! $user_id ) {
		return false;
	}
	global $wpdb;
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}apollo_likes
         WHERE item_type = 'activity' AND item_id = %d AND user_id = %d",
			$activity_id,
			$user_id
		)
	);
}
?>
<style>
	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}
</style>
<script src="https://cdn.apollo.rio.br/"></script>
