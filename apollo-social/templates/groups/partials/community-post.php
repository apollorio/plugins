<?php
/**
 * Partial: Community Post Card
 * STRICT MODE: UNI.CSS compliance
 * Reusable post card component for community/núcleo
 *
 * @var array $post_data Post data array
 * @var int $group_id Group/Community ID
 * @var int $creator_id Creator user ID
 * @var array $moderators Array of moderator user IDs
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract post data
$post_id             = (int) ( $post_data['id'] ?? 0 );
$post_author_id      = (int) ( $post_data['author_id'] ?? 0 );
$post_content        = $post_data['content'] ?? '';
$post_date           = $post_data['date'] ?? '';
$post_location       = $post_data['location'] ?? '';
$post_tags           = $post_data['tags'] ?? [];
$post_image          = $post_data['image'] ?? '';
$post_likes          = (int) ( $post_data['likes'] ?? 0 );
$post_comments_count = (int) ( $post_data['comments_count'] ?? 0 );
$is_notice           = (bool) ( $post_data['is_notice'] ?? false );
$featured_comment    = $post_data['featured_comment'] ?? null;

// Get author data
$post_author = get_userdata( $post_author_id );

// Check if author is owner or moderator
$is_owner = ( $post_author_id === ( $creator_id ?? 0 ) );
$is_mod   = in_array( $post_author_id, $moderators ?? [], true );

// Format date
$time_ago = '';
if ( $post_date ) {
	$time_ago = human_time_diff( strtotime( $post_date ), current_time( 'timestamp' ) );
}

// Tags array
if ( ! is_array( $post_tags ) ) {
	$post_tags = $post_tags ? array_map( 'trim', explode( ',', $post_tags ) ) : [];
}
?>
<article class="ap-card" data-post-id="<?php echo esc_attr( $post_id ); ?>">
	<div class="ap-card-body">
		<header class="ap-flex ap-items-start ap-gap-3">
			<div class="ap-avatar ap-avatar-md">
				<?php if ( $post_author ) : ?>
					<?php echo get_avatar( $post_author_id, 36, '', $post_author->display_name, [ 'class' => 'ap-avatar-img' ] ); ?>
				<?php else : ?>
				<div class="ap-avatar-fallback">
					<i class="ri-user-line"></i>
				</div>
				<?php endif; ?>
			</div>
			
			<div class="ap-flex-1">
				<div class="ap-flex ap-items-center ap-justify-between ap-gap-2">
					<div>
						<div class="ap-flex ap-items-center ap-gap-1">
							<span class="ap-font-semibold"><?php echo esc_html( $post_author->display_name ?? __( 'Usuário', 'apollo-social' ) ); ?></span>
							<?php if ( $is_owner || $is_mod ) : ?>
							<span class="ap-text-xs ap-text-muted">
								@<?php echo esc_html( $post_author->user_login ?? '' ); ?> · 
								<?php echo $is_owner ? esc_html__( 'Responsável', 'apollo-social' ) : esc_html__( 'Moderação', 'apollo-social' ); ?>
							</span>
							<?php else : ?>
							<span class="ap-text-xs ap-text-muted">@<?php echo esc_html( $post_author->user_login ?? '' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="ap-flex ap-items-center ap-gap-2 ap-text-xs ap-text-muted">
							<?php if ( $time_ago ) : ?>
							<span><?php echo esc_html( sprintf( __( 'há %s', 'apollo-social' ), $time_ago ) ); ?></span>
							<?php endif; ?>
							<?php if ( $post_location ) : ?>
							<span class="ap-dot"></span>
							<span class="ap-flex ap-items-center ap-gap-1">
								<i class="ri-map-pin-2-line"></i> <?php echo esc_html( $post_location ); ?>
							</span>
							<?php endif; ?>
						</div>
					</div>
					<?php if ( $is_notice ) : ?>
					<span class="ap-badge ap-badge-warning ap-badge-sm">
						<i class="ri-notification-3-line"></i> <?php esc_html_e( 'aviso', 'apollo-social' ); ?>
					</span>
					<?php else : ?>
					<button class="ap-btn ap-btn-icon ap-btn-ghost ap-btn-sm" 
							data-action="post-menu" 
							data-post-id="<?php echo esc_attr( $post_id ); ?>"
							data-ap-tooltip="<?php esc_attr_e( 'Mais opções', 'apollo-social' ); ?>">
						<i class="ri-more-2-fill"></i>
					</button>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<div class="ap-post-content ap-mt-3 ap-text-sm ap-leading-relaxed">
			<?php echo wp_kses_post( $post_content ); ?>
		</div>

		<?php if ( $post_image ) : ?>
		<div class="ap-post-media ap-mt-3 ap-rounded-xl ap-overflow-hidden ap-border">
			<img src="<?php echo esc_url( $post_image ); ?>" 
				alt="" 
				class="ap-w-full ap-h-auto" 
				loading="lazy" />
		</div>
		<?php endif; ?>

		<div class="ap-flex ap-flex-wrap ap-items-center ap-justify-between ap-gap-3 ap-mt-3">
			<?php if ( ! empty( $post_tags ) ) : ?>
			<div class="ap-flex ap-flex-wrap ap-gap-1">
				<?php foreach ( array_slice( $post_tags, 0, 3 ) as $tag ) : ?>
				<span class="ap-chip ap-chip-sm">
					<i class="ri-hashtag"></i> <?php echo esc_html( $tag ); ?>
				</span>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div></div>
			<?php endif; ?>
			
			<div class="ap-flex ap-items-center ap-gap-4 ap-text-xs ap-text-muted">
				<button class="ap-btn ap-btn-ghost ap-btn-sm ap-gap-1" 
						data-action="like-post" 
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-ap-tooltip="<?php esc_attr_e( 'Curtir', 'apollo-social' ); ?>">
					<i class="ri-heart-3-line"></i> <?php echo esc_html( $post_likes ); ?>
				</button>
				<button class="ap-btn ap-btn-ghost ap-btn-sm ap-gap-1" 
						data-action="view-comments" 
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-ap-tooltip="<?php esc_attr_e( 'Comentários', 'apollo-social' ); ?>">
					<i class="ri-message-2-line"></i> <?php echo esc_html( $post_comments_count ); ?>
				</button>
				<button class="ap-btn ap-btn-ghost ap-btn-sm" 
						data-action="share-post" 
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-ap-tooltip="<?php esc_attr_e( 'Compartilhar', 'apollo-social' ); ?>">
					<i class="ri-share-forward-line"></i>
				</button>
			</div>
		</div>

		<!-- Featured comment -->
		<?php
		if ( $featured_comment ) :
			$comment_author_id = (int) ( $featured_comment['author_id'] ?? 0 );
			$comment_author    = $comment_author_id ? get_userdata( $comment_author_id ) : null;
			$comment_content   = $featured_comment['content'] ?? '';
			$comment_date      = $featured_comment['date'] ?? '';
			?>
		<div class="ap-border-t ap-border-dashed ap-mt-3 ap-pt-3">
			<div class="ap-flex ap-items-start ap-gap-2">
				<div class="ap-avatar ap-avatar-sm">
					<?php if ( $comment_author ) : ?>
						<?php echo get_avatar( $comment_author_id, 28, '', $comment_author->display_name, [ 'class' => 'ap-avatar-img' ] ); ?>
					<?php else : ?>
					<div class="ap-avatar-fallback">
						<i class="ri-user-line"></i>
					</div>
					<?php endif; ?>
				</div>
				<div class="ap-flex-1">
					<div class="ap-flex ap-items-center ap-gap-1">
						<span class="ap-text-xs ap-font-semibold">
							<?php echo esc_html( $comment_author ? $comment_author->display_name : ( $featured_comment['author_name'] ?? __( 'Usuário', 'apollo-social' ) ) ); ?>
						</span>
						<?php if ( $comment_date ) : ?>
						<span class="ap-text-[10px] ap-text-muted">
							<?php echo esc_html( sprintf( __( 'há %s', 'apollo-social' ), human_time_diff( strtotime( $comment_date ), current_time( 'timestamp' ) ) ) ); ?>
						</span>
						<?php endif; ?>
					</div>
					<p class="ap-text-xs ap-text-secondary ap-leading-relaxed">
						<?php echo esc_html( wp_trim_words( $comment_content, 30 ) ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
</article>
