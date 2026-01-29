<?php
/**
 * Apollo Social Feed Block - Server-Side Render
 *
 * Renders a social activity feed with posts and interactions.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract attributes with defaults.
$layout         = $attributes['layout'] ?? 'list';
$limit          = $attributes['limit'] ?? 10;
$show_author    = $attributes['showAuthor'] ?? true;
$show_avatar    = $attributes['showAvatar'] ?? true;
$show_date      = $attributes['showDate'] ?? true;
$show_likes     = $attributes['showLikes'] ?? true;
$show_comments  = $attributes['showComments'] ?? true;
$show_share     = $attributes['showShare'] ?? true;
$show_load_more = $attributes['showLoadMore'] ?? true;
$category       = $attributes['category'] ?? '';
$user_id        = $attributes['userId'] ?? 0;
$class_name     = $attributes['className'] ?? '';

// Build query args.
$query_args = array(
	'post_type'      => 'apollo_social_post',
	'post_status'    => 'publish',
	'posts_per_page' => (int) $limit,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

// Filter by user.
if ( $user_id > 0 ) {
	$query_args['author'] = $user_id;
}

// Filter by category.
if ( ! empty( $category ) ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'apollo_post_category',
			'field'    => 'term_id',
			'terms'    => array_map( 'intval', explode( ',', $category ) ),
		),
	);
}

$posts_query = new WP_Query( $query_args );

// Generate unique ID.
$feed_id = 'apollo-social-feed-' . wp_unique_id();

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-social-feed-block',
	'apollo-social-feed',
	"apollo-social-feed--{$layout}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
		'id'    => $feed_id,
	)
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $posts_query->have_posts() ) : ?>

		<div class="apollo-social-feed__posts">
			<?php
			while ( $posts_query->have_posts() ) :
				$posts_query->the_post();
				$post_id     = get_the_ID();
				$author_id   = get_the_author_meta( 'ID' );
				$author_name = get_the_author();
				$avatar_url  = get_avatar_url( $author_id, array( 'size' => 96 ) );
				$post_date   = get_the_date();
				$time_ago    = human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) );

				// Get interaction counts (would come from meta in real implementation).
				$likes_count    = (int) get_post_meta( $post_id, '_apollo_likes_count', true ) ?: 0;
				$comments_count = (int) get_comments_number( $post_id );
				?>

				<article class="apollo-social-post" data-post-id="<?php echo esc_attr( $post_id ); ?>">

					<?php if ( $show_author || $show_avatar ) : ?>
					<header class="apollo-social-post__header">
						<?php if ( $show_avatar ) : ?>
							<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-social-post__avatar">
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" />
							</a>
						<?php endif; ?>

						<div class="apollo-social-post__author-info">
							<?php if ( $show_author ) : ?>
								<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-social-post__author">
									<?php echo esc_html( $author_name ); ?>
								</a>
							<?php endif; ?>

							<?php if ( $show_date ) : ?>
								<time class="apollo-social-post__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php
									printf(
										/* translators: %s: time ago */
										esc_html__( 'há %s', 'apollo-social' ),
										esc_html( $time_ago )
									);
									?>
								</time>
							<?php endif; ?>
						</div>

						<button class="apollo-social-post__menu-btn" aria-label="<?php esc_attr_e( 'Menu', 'apollo-social' ); ?>">
							<i class="ri-more-2-fill"></i>
						</button>
					</header>
					<?php endif; ?>

					<div class="apollo-social-post__content">
						<?php the_content(); ?>
					</div>

					<?php if ( has_post_thumbnail() ) : ?>
					<div class="apollo-social-post__media">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'large' ); ?>
						</a>
					</div>
					<?php endif; ?>

					<?php if ( $show_likes || $show_comments || $show_share ) : ?>
					<footer class="apollo-social-post__footer">
						<div class="apollo-social-post__stats">
							<?php if ( $show_likes && $likes_count > 0 ) : ?>
								<span class="apollo-social-post__stat">
									<?php
									printf(
										/* translators: %d: number of likes */
										esc_html( _n( '%d curtida', '%d curtidas', $likes_count, 'apollo-social' ) ),
										$likes_count
									);
									?>
								</span>
							<?php endif; ?>
							<?php if ( $show_comments && $comments_count > 0 ) : ?>
								<span class="apollo-social-post__stat">
									<?php
									printf(
										/* translators: %d: number of comments */
										esc_html( _n( '%d comentário', '%d comentários', $comments_count, 'apollo-social' ) ),
										$comments_count
									);
									?>
								</span>
							<?php endif; ?>
						</div>

						<div class="apollo-social-post__actions">
							<?php if ( $show_likes ) : ?>
								<button
									class="apollo-social-post__action apollo-social-post__action--like"
									data-post-id="<?php echo esc_attr( $post_id ); ?>"
									aria-label="<?php esc_attr_e( 'Curtir', 'apollo-social' ); ?>"
								>
									<i class="ri-heart-line"></i>
									<span><?php esc_html_e( 'Curtir', 'apollo-social' ); ?></span>
								</button>
							<?php endif; ?>

							<?php if ( $show_comments ) : ?>
								<button
									class="apollo-social-post__action apollo-social-post__action--comment"
									data-post-id="<?php echo esc_attr( $post_id ); ?>"
									aria-label="<?php esc_attr_e( 'Comentar', 'apollo-social' ); ?>"
								>
									<i class="ri-chat-1-line"></i>
									<span><?php esc_html_e( 'Comentar', 'apollo-social' ); ?></span>
								</button>
							<?php endif; ?>

							<?php if ( $show_share ) : ?>
								<button
									class="apollo-social-post__action apollo-social-post__action--share"
									data-url="<?php echo esc_url( get_permalink() ); ?>"
									data-title="<?php echo esc_attr( get_the_title() ); ?>"
									aria-label="<?php esc_attr_e( 'Compartilhar', 'apollo-social' ); ?>"
								>
									<i class="ri-share-line"></i>
									<span><?php esc_html_e( 'Compartilhar', 'apollo-social' ); ?></span>
								</button>
							<?php endif; ?>
						</div>
					</footer>
					<?php endif; ?>
				</article>

			<?php endwhile; ?>
		</div>

		<?php if ( $show_load_more && $posts_query->max_num_pages > 1 ) : ?>
		<div class="apollo-social-feed__load-more">
			<button
				class="apollo-btn apollo-btn--outline apollo-btn--block apollo-social-feed__load-more-btn"
				data-page="1"
				data-max-pages="<?php echo esc_attr( $posts_query->max_num_pages ); ?>"
			>
				<i class="ri-refresh-line"></i>
				<?php esc_html_e( 'Carregar mais', 'apollo-social' ); ?>
			</button>
		</div>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>
		<div class="apollo-social-feed__empty">
			<div class="apollo-empty-state">
				<i class="ri-chat-smile-3-line apollo-empty-state__icon"></i>
				<p class="apollo-empty-state__text">
					<?php esc_html_e( 'Nenhuma publicação encontrada.', 'apollo-social' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post {
	background: #fff;
	border-radius: 12px;
	padding: 1.25rem;
	margin-bottom: 1rem;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__header {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	margin-bottom: 1rem;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__avatar {
	flex-shrink: 0;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__avatar img {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	object-fit: cover;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__author-info {
	flex: 1;
	min-width: 0;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__author {
	display: block;
	font-weight: 600;
	color: #1e293b;
	text-decoration: none;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__author:hover {
	color: #4f46e5;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__date {
	font-size: 0.8125rem;
	color: #64748b;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__menu-btn {
	padding: 0.5rem;
	background: none;
	border: none;
	cursor: pointer;
	color: #64748b;
	border-radius: 50%;
	transition: background 0.2s;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__menu-btn:hover {
	background: #f1f5f9;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__content {
	margin-bottom: 1rem;
	line-height: 1.6;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__media {
	margin: 0 -1.25rem 1rem;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__media img {
	width: 100%;
	display: block;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__footer {
	border-top: 1px solid #e2e8f0;
	padding-top: 0.75rem;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__stats {
	display: flex;
	gap: 1rem;
	font-size: 0.8125rem;
	color: #64748b;
	margin-bottom: 0.75rem;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__actions {
	display: flex;
	justify-content: space-around;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__action {
	display: flex;
	align-items: center;
	gap: 0.375rem;
	padding: 0.5rem 1rem;
	background: none;
	border: none;
	cursor: pointer;
	color: #64748b;
	font-size: 0.875rem;
	border-radius: 8px;
	transition: all 0.2s;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__action:hover {
	background: #f1f5f9;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__action--like:hover,
#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__action--like.active {
	color: #ef4444;
}

#<?php echo esc_attr( $feed_id ); ?> .apollo-social-post__action--like.active i {
	font-weight: bold;
}

/* Grid layout */
#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--grid .apollo-social-feed__posts {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 1rem;
}

#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--grid .apollo-social-post {
	margin-bottom: 0;
}

/* Masonry layout */
#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--masonry .apollo-social-feed__posts {
	columns: 2;
	column-gap: 1rem;
}

#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--masonry .apollo-social-post {
	break-inside: avoid;
}

@media (max-width: 768px) {
	#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--grid .apollo-social-feed__posts,
	#<?php echo esc_attr( $feed_id ); ?>.apollo-social-feed--masonry .apollo-social-feed__posts {
		grid-template-columns: 1fr;
		columns: 1;
	}
}
</style>
