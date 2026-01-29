<?php
/**
 * Apollo User Profile Block - Server-Side Render
 *
 * Renders a user profile card with avatar, info, stats, and actions.
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
$user_id             = (int) ( $attributes['userId'] ?? 0 );
$layout              = $attributes['layout'] ?? 'card';
$show_avatar         = $attributes['showAvatar'] ?? true;
$avatar_size         = $attributes['avatarSize'] ?? 'large';
$show_name           = $attributes['showName'] ?? true;
$show_bio            = $attributes['showBio'] ?? true;
$show_location       = $attributes['showLocation'] ?? true;
$show_website        = $attributes['showWebsite'] ?? true;
$show_social_links   = $attributes['showSocialLinks'] ?? true;
$show_stats          = $attributes['showStats'] ?? true;
$show_follow_button  = $attributes['showFollowButton'] ?? true;
$show_message_button = $attributes['showMessageButton'] ?? false;
$show_recent_posts   = $attributes['showRecentPosts'] ?? false;
$recent_posts_count  = (int) ( $attributes['recentPostsCount'] ?? 3 );
$show_cover          = $attributes['showCover'] ?? true;
$cover_height        = (int) ( $attributes['coverHeight'] ?? 200 );
$show_badges         = $attributes['showBadges'] ?? true;
$show_join_date      = $attributes['showJoinDate'] ?? true;
$use_current_user    = $attributes['useCurrentUser'] ?? false;
$class_name          = $attributes['className'] ?? '';

// Determine which user to display.
if ( $use_current_user && is_user_logged_in() ) {
	$user_id = get_current_user_id();
}

// Get user data.
$user = $user_id > 0 ? get_userdata( $user_id ) : null;

if ( ! $user ) {
	// In editor context, show placeholder.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		echo '<div class="apollo-user-profile-placeholder">';
		echo '<p>' . esc_html__( 'Selecione um usuário para exibir o perfil.', 'apollo-social' ) . '</p>';
		echo '</div>';
	}
	return;
}

// Avatar sizes in pixels.
$avatar_sizes = array(
	'small'  => 48,
	'medium' => 80,
	'large'  => 128,
	'xlarge' => 180,
);
$avatar_px    = $avatar_sizes[ $avatar_size ] ?? 128;

// Get user data.
$display_name = $user->display_name;
$user_bio     = get_user_meta( $user_id, 'description', true );
$user_email   = $user->user_email;
$user_url     = $user->user_url;
$user_login   = $user->user_login;
$registered   = $user->user_registered;

// Location (custom meta field).
$user_location = get_user_meta( $user_id, 'apollo_location', true );
if ( empty( $user_location ) ) {
	$user_location = get_user_meta( $user_id, 'location', true );
}

// Avatar.
$avatar_url = get_avatar_url( $user_id, array( 'size' => $avatar_px * 2 ) );

// Cover image (custom meta field).
$cover_url = get_user_meta( $user_id, 'apollo_cover_image', true );
if ( empty( $cover_url ) ) {
	$cover_url = get_user_meta( $user_id, 'cover_image', true );
}

// Social links.
$social_links  = array();
$social_fields = array(
	'instagram'  => array(
		'icon'  => 'ri-instagram-line',
		'color' => '#e4405f',
	),
	'twitter'    => array(
		'icon'  => 'ri-twitter-x-line',
		'color' => '#1da1f2',
	),
	'facebook'   => array(
		'icon'  => 'ri-facebook-fill',
		'color' => '#1877f2',
	),
	'linkedin'   => array(
		'icon'  => 'ri-linkedin-fill',
		'color' => '#0a66c2',
	),
	'youtube'    => array(
		'icon'  => 'ri-youtube-fill',
		'color' => '#ff0000',
	),
	'soundcloud' => array(
		'icon'  => 'ri-soundcloud-fill',
		'color' => '#ff5500',
	),
	'spotify'    => array(
		'icon'  => 'ri-spotify-fill',
		'color' => '#1db954',
	),
	'mixcloud'   => array(
		'icon'  => 'ri-music-2-fill',
		'color' => '#5000ff',
	),
	'beatport'   => array(
		'icon'  => 'ri-disc-fill',
		'color' => '#94d500',
	),
);

foreach ( $social_fields as $network => $config ) {
	$link = get_user_meta( $user_id, $network, true );
	if ( ! empty( $link ) ) {
		$social_links[ $network ] = array(
			'url'   => $link,
			'icon'  => $config['icon'],
			'color' => $config['color'],
		);
	}
}

// Stats (these would come from your social system).
$stats = array(
	'posts'     => count_user_posts( $user_id ),
	'followers' => (int) get_user_meta( $user_id, 'apollo_followers_count', true ),
	'following' => (int) get_user_meta( $user_id, 'apollo_following_count', true ),
);

// Apply filters for custom stats.
$stats = apply_filters( 'apollo_user_profile_stats', $stats, $user_id );

// User badges.
$badges = array();
if ( $show_badges ) {
	// Check for verified status.
	$is_verified = get_user_meta( $user_id, 'apollo_verified', true );
	if ( $is_verified ) {
		$badges[] = array(
			'label' => __( 'Verificado', 'apollo-social' ),
			'icon'  => 'ri-verified-badge-fill',
			'color' => '#3b82f6',
		);
	}

	// Check for DJ/Artist role.
	if ( in_array( 'apollo_dj', (array) $user->roles, true ) ) {
		$badges[] = array(
			'label' => __( 'DJ', 'apollo-social' ),
			'icon'  => 'ri-disc-fill',
			'color' => '#8b5cf6',
		);
	}

	if ( in_array( 'apollo_promoter', (array) $user->roles, true ) ) {
		$badges[] = array(
			'label' => __( 'Promoter', 'apollo-social' ),
			'icon'  => 'ri-megaphone-fill',
			'color' => '#f59e0b',
		);
	}

	// Allow custom badges.
	$badges = apply_filters( 'apollo_user_profile_badges', $badges, $user_id );
}

// Generate unique ID.
$profile_id = 'apollo-profile-' . wp_unique_id();

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-user-profile-block',
	'apollo-user-profile',
	"apollo-user-profile--{$layout}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'        => implode( ' ', $wrapper_classes ),
		'id'           => $profile_id,
		'data-user-id' => $user_id,
	)
);

// Check if current user is following this user.
$is_following = false;
if ( is_user_logged_in() && function_exists( 'apollo_is_following' ) ) {
	$is_following = apollo_is_following( get_current_user_id(), $user_id );
}

// Follow/unfollow nonce.
$follow_nonce = wp_create_nonce( 'apollo_follow_' . $user_id );
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( ( 'hero' === $layout || 'card' === $layout ) && $show_cover ) : ?>
		<div
			class="apollo-user-profile__cover"
			style="height: <?php echo esc_attr( 'hero' === $layout ? $cover_height : $cover_height * 0.6 ); ?>px;
					<?php if ( ! empty( $cover_url ) ) : ?>
					background-image: url('<?php echo esc_url( $cover_url ); ?>');
					background-size: cover;
					background-position: center;
					<?php else : ?>
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					<?php endif; ?>"
		></div>
	<?php endif; ?>

	<div class="apollo-user-profile__content"
		style="<?php echo ( $show_cover && in_array( $layout, array( 'hero', 'card' ), true ) ) ? 'margin-top: -' . ( $avatar_px / 2 ) . 'px;' : ''; ?>">

		<?php if ( $show_avatar ) : ?>
			<div class="apollo-user-profile__avatar" style="width: <?php echo esc_attr( $avatar_px ); ?>px; height: <?php echo esc_attr( $avatar_px ); ?>px;">
				<img
					src="<?php echo esc_url( $avatar_url ); ?>"
					alt="<?php echo esc_attr( $display_name ); ?>"
					class="apollo-user-profile__avatar-img"
					width="<?php echo esc_attr( $avatar_px ); ?>"
					height="<?php echo esc_attr( $avatar_px ); ?>"
					loading="lazy"
				>
				<?php if ( ! empty( $badges ) && isset( $badges[0] ) && $badges[0]['label'] === __( 'Verificado', 'apollo-social' ) ) : ?>
					<span class="apollo-user-profile__verified" title="<?php esc_attr_e( 'Verificado', 'apollo-social' ); ?>">
						<i class="ri-verified-badge-fill"></i>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="apollo-user-profile__info">
			<?php if ( $show_name ) : ?>
				<h3 class="apollo-user-profile__name">
					<a href="<?php echo esc_url( get_author_posts_url( $user_id ) ); ?>">
						<?php echo esc_html( $display_name ); ?>
					</a>
				</h3>
			<?php endif; ?>

			<?php if ( $show_badges && ! empty( $badges ) ) : ?>
				<div class="apollo-user-profile__badges">
					<?php foreach ( $badges as $badge ) : ?>
						<span
							class="apollo-user-profile__badge"
							style="background-color: <?php echo esc_attr( $badge['color'] ); ?>20; color: <?php echo esc_attr( $badge['color'] ); ?>;"
						>
							<?php if ( ! empty( $badge['icon'] ) ) : ?>
								<i class="<?php echo esc_attr( $badge['icon'] ); ?>"></i>
							<?php endif; ?>
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_location && ! empty( $user_location ) ) : ?>
				<p class="apollo-user-profile__location">
					<i class="ri-map-pin-line"></i>
					<?php echo esc_html( $user_location ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $show_bio && ! empty( $user_bio ) ) : ?>
				<p class="apollo-user-profile__bio">
					<?php
					$bio_text = 'compact' === $layout ? wp_trim_words( $user_bio, 15 ) : $user_bio;
					echo esc_html( $bio_text );
					?>
				</p>
			<?php endif; ?>

			<?php if ( $show_website && ! empty( $user_url ) ) : ?>
				<a href="<?php echo esc_url( $user_url ); ?>" class="apollo-user-profile__website" target="_blank" rel="noopener noreferrer">
					<i class="ri-link"></i>
					<?php echo esc_html( wp_parse_url( $user_url, PHP_URL_HOST ) ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $show_stats ) : ?>
				<div class="apollo-user-profile__stats">
					<div class="apollo-user-profile__stat">
						<span class="apollo-user-profile__stat-value"><?php echo esc_html( number_format_i18n( $stats['posts'] ) ); ?></span>
						<span class="apollo-user-profile__stat-label"><?php esc_html_e( 'Posts', 'apollo-social' ); ?></span>
					</div>
					<div class="apollo-user-profile__stat">
						<span class="apollo-user-profile__stat-value"><?php echo esc_html( number_format_i18n( $stats['followers'] ) ); ?></span>
						<span class="apollo-user-profile__stat-label"><?php esc_html_e( 'Seguidores', 'apollo-social' ); ?></span>
					</div>
					<div class="apollo-user-profile__stat">
						<span class="apollo-user-profile__stat-value"><?php echo esc_html( number_format_i18n( $stats['following'] ) ); ?></span>
						<span class="apollo-user-profile__stat-label"><?php esc_html_e( 'Seguindo', 'apollo-social' ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_join_date ) : ?>
				<p class="apollo-user-profile__join-date">
					<i class="ri-calendar-line"></i>
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'Membro desde %s', 'apollo-social' ),
						date_i18n( 'M Y', strtotime( $registered ) )
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( $show_social_links && ! empty( $social_links ) ) : ?>
				<div class="apollo-user-profile__social-links">
					<?php foreach ( $social_links as $network => $link ) : ?>
						<a
							href="<?php echo esc_url( $link['url'] ); ?>"
							class="apollo-user-profile__social-link"
							style="color: <?php echo esc_attr( $link['color'] ); ?>;"
							target="_blank"
							rel="noopener noreferrer"
							title="<?php echo esc_attr( ucfirst( $network ) ); ?>"
						>
							<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_follow_button || $show_message_button ) : ?>
				<div class="apollo-user-profile__actions">
					<?php if ( $show_follow_button && is_user_logged_in() && get_current_user_id() !== $user_id ) : ?>
						<button
							class="apollo-btn apollo-btn--<?php echo $is_following ? 'outline' : 'primary'; ?> apollo-follow-btn"
							data-user-id="<?php echo esc_attr( $user_id ); ?>"
							data-nonce="<?php echo esc_attr( $follow_nonce ); ?>"
							data-following="<?php echo $is_following ? 'true' : 'false'; ?>"
						>
							<i class="<?php echo $is_following ? 'ri-user-unfollow-line' : 'ri-user-add-line'; ?>"></i>
							<span class="apollo-follow-btn__label">
								<?php echo $is_following ? esc_html__( 'Seguindo', 'apollo-social' ) : esc_html__( 'Seguir', 'apollo-social' ); ?>
							</span>
						</button>
					<?php endif; ?>

					<?php if ( $show_message_button && is_user_logged_in() && get_current_user_id() !== $user_id ) : ?>
						<a
							href="<?php echo esc_url( add_query_arg( 'user', $user_id, home_url( '/mensagens/' ) ) ); ?>"
							class="apollo-btn apollo-btn--outline apollo-message-btn"
						>
							<i class="ri-message-3-line"></i>
							<?php if ( 'hero' === $layout ) : ?>
								<span><?php esc_html_e( 'Mensagem', 'apollo-social' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $show_recent_posts && $stats['posts'] > 0 ) : ?>
		<div class="apollo-user-profile__recent-posts">
			<h4 class="apollo-user-profile__section-title">
				<?php esc_html_e( 'Posts Recentes', 'apollo-social' ); ?>
			</h4>
			<?php
			$recent_posts = get_posts(
				array(
					'author'         => $user_id,
					'posts_per_page' => $recent_posts_count,
					'post_status'    => 'publish',
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			if ( ! empty( $recent_posts ) ) :
				?>
				<ul class="apollo-user-profile__posts-list">
					<?php foreach ( $recent_posts as $post ) : ?>
						<li class="apollo-user-profile__post-item">
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<?php echo esc_html( get_the_title( $post ) ); ?>
							</a>
							<span class="apollo-user-profile__post-date">
								<?php echo esc_html( human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<style>
#<?php echo esc_attr( $profile_id ); ?> {
	--profile-avatar-size: <?php echo esc_attr( $avatar_px ); ?>px;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--card {
	background: #fff;
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--card .apollo-user-profile__content {
	padding: 1.5rem;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--hero .apollo-user-profile__content {
	padding: 0 2rem 2rem;
	text-align: center;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--compact {
	display: flex;
	align-items: center;
	gap: 1rem;
	padding: 1rem;
	background: #fff;
	border-radius: 8px;
	box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--compact .apollo-user-profile__content {
	margin-top: 0 !important;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--minimal {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__avatar {
	position: relative;
	flex-shrink: 0;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__avatar-img {
	width: 100%;
	height: 100%;
	border-radius: 50%;
	object-fit: cover;
	border: 3px solid #fff;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__verified {
	position: absolute;
	bottom: 0;
	right: 0;
	background: #fff;
	border-radius: 50%;
	width: 24px;
	height: 24px;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #3b82f6;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__name {
	margin: 0;
	font-size: 1.25rem;
	font-weight: 600;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__name a {
	color: inherit;
	text-decoration: none;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__name a:hover {
	color: #6366f1;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__badges {
	display: flex;
	flex-wrap: wrap;
	gap: 0.375rem;
	margin-top: 0.5rem;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__badge {
	display: inline-flex;
	align-items: center;
	gap: 0.25rem;
	padding: 0.125rem 0.5rem;
	border-radius: 9999px;
	font-size: 0.75rem;
	font-weight: 500;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__location,
#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__join-date {
	display: flex;
	align-items: center;
	gap: 0.25rem;
	color: #64748b;
	font-size: 0.875rem;
	margin: 0.5rem 0;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__bio {
	font-size: 0.875rem;
	color: #475569;
	margin: 0.75rem 0;
	line-height: 1.5;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__website {
	display: inline-flex;
	align-items: center;
	gap: 0.25rem;
	color: #6366f1;
	font-size: 0.875rem;
	text-decoration: none;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__website:hover {
	text-decoration: underline;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__stats {
	display: flex;
	gap: 1.5rem;
	margin: 1rem 0;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--hero .apollo-user-profile__stats {
	justify-content: center;
	gap: 2rem;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__stat {
	text-align: center;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__stat-value {
	display: block;
	font-size: 1.25rem;
	font-weight: 700;
	color: #1e293b;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__stat-label {
	display: block;
	font-size: 0.75rem;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__social-links {
	display: flex;
	gap: 0.75rem;
	margin: 1rem 0;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--hero .apollo-user-profile__social-links {
	justify-content: center;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__social-link {
	font-size: 1.25rem;
	transition: transform 0.2s, opacity 0.2s;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__social-link:hover {
	transform: scale(1.1);
	opacity: 0.8;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__actions {
	display: flex;
	gap: 0.5rem;
	margin-top: 1rem;
}

#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--hero .apollo-user-profile__actions {
	justify-content: center;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.375rem;
	padding: 0.5rem 1rem;
	border-radius: 8px;
	font-size: 0.875rem;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.2s;
	border: none;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-btn--primary {
	background: #6366f1;
	color: #fff;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-btn--primary:hover {
	background: #4f46e5;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-btn--outline {
	background: transparent;
	border: 1px solid #e2e8f0;
	color: #475569;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-btn--outline:hover {
	border-color: #6366f1;
	color: #6366f1;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__recent-posts {
	padding: 1rem 1.5rem 1.5rem;
	border-top: 1px solid #e2e8f0;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__section-title {
	font-size: 0.875rem;
	font-weight: 600;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.05em;
	margin: 0 0 0.75rem;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__posts-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 0.5rem 0;
	border-bottom: 1px solid #f1f5f9;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item:last-child {
	border-bottom: none;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item a {
	color: #1e293b;
	text-decoration: none;
	font-size: 0.875rem;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item a:hover {
	color: #6366f1;
}

#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-date {
	font-size: 0.75rem;
	color: #94a3b8;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
	#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--card,
	#<?php echo esc_attr( $profile_id ); ?>.apollo-user-profile--compact {
		background: #1e293b;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__name a {
		color: #f1f5f9;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__bio {
		color: #94a3b8;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__stat-value {
		color: #f1f5f9;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item a {
		color: #f1f5f9;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__recent-posts {
		border-top-color: #334155;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-user-profile__post-item {
		border-bottom-color: #334155;
	}

	#<?php echo esc_attr( $profile_id ); ?> .apollo-btn--outline {
		border-color: #475569;
		color: #94a3b8;
	}
}
</style>

<script>
(function() {
	const profile = document.getElementById('<?php echo esc_js( $profile_id ); ?>');
	if (!profile) return;

	// Handle follow button.
	const followBtn = profile.querySelector('.apollo-follow-btn');
	if (followBtn) {
		followBtn.addEventListener('click', function() {
			const userId = this.dataset.userId;
			const nonce = this.dataset.nonce;
			const isFollowing = this.dataset.following === 'true';
			const label = this.querySelector('.apollo-follow-btn__label');
			const icon = this.querySelector('i');

			// Optimistic update.
			this.disabled = true;

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: isFollowing ? 'apollo_unfollow_user' : 'apollo_follow_user',
					user_id: userId,
					nonce: nonce,
				}),
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					const newFollowing = !isFollowing;
					this.dataset.following = newFollowing.toString();
					label.textContent = newFollowing ? '<?php echo esc_js( __( 'Seguindo', 'apollo-social' ) ); ?>' : '<?php echo esc_js( __( 'Seguir', 'apollo-social' ) ); ?>';
					icon.className = newFollowing ? 'ri-user-unfollow-line' : 'ri-user-add-line';
					this.classList.toggle('apollo-btn--primary', !newFollowing);
					this.classList.toggle('apollo-btn--outline', newFollowing);

					// Update followers count if visible.
					const followersCount = profile.querySelector('.apollo-user-profile__stat:nth-child(2) .apollo-user-profile__stat-value');
					if (followersCount && data.data?.followers_count !== undefined) {
						followersCount.textContent = new Intl.NumberFormat().format(data.data.followers_count);
					}
				}
			})
			.finally(() => {
				this.disabled = false;
			});
		});
	}
})();
</script>
