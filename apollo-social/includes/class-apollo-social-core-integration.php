<?php
/**
 * Apollo Social - Core Integration
 *
 * Hooks into Apollo Core's template system and integration bridge.
 * Provides social sharing buttons via filter hooks.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Social\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Social_Core_Integration
 *
 * Integrates Apollo Social with Apollo Core's template system.
 */
class Apollo_Social_Core_Integration {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Social_Core_Integration|null
	 */
	private static ?Apollo_Social_Core_Integration $instance = null;

	/**
	 * Integration bridge instance
	 *
	 * @var object|null
	 */
	private ?object $bridge = null;

	/**
	 * Social button defaults
	 *
	 * @var array<string, mixed>
	 */
	private array $button_defaults = array(
		'show_share'    => true,
		'show_like'     => true,
		'show_favorite' => true,
		'show_comment'  => false,
		'size'          => 'medium',
		'style'         => 'default',
	);

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Social_Core_Integration
	 */
	public static function get_instance(): Apollo_Social_Core_Integration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Register with Core Integration Bridge when ready.
		add_action( 'apollo_integration_bridge_ready', array( $this, 'register_with_bridge' ) );

		// Provide social buttons via filter.
		add_filter( 'apollo_core_social_buttons', array( $this, 'render_social_buttons' ), 10, 4 );

		// Add user data transformations.
		add_filter( 'apollo_core_user_data_transform', array( $this, 'transform_user_data' ), 10, 2 );

		// Hook into event display for social features.
		add_action( 'apollo_core_after_event_display', array( $this, 'add_social_features' ), 20, 2 );

		// Add activity stream integration.
		add_action( 'apollo_core_template_data_prepared', array( $this, 'inject_activity_data' ), 10, 2 );
	}

	/**
	 * Register with Apollo Core Integration Bridge
	 *
	 * @param object $bridge Integration Bridge instance.
	 * @return void
	 */
	public function register_with_bridge( object $bridge ): void {
		$this->bridge = $bridge;

		if ( method_exists( $bridge, 'register_plugin' ) ) {
			$bridge->register_plugin(
				'social',
				array(
					'version'      => defined( 'APOLLO_SOCIAL_VERSION' ) ? APOLLO_SOCIAL_VERSION : '1.0.0',
					'file'         => 'apollo-social/apollo-social.php',
					'path'         => defined( 'APOLLO_SOCIAL_PLUGIN_DIR' ) ? APOLLO_SOCIAL_PLUGIN_DIR : '',
					'url'          => defined( 'APOLLO_SOCIAL_PLUGIN_URL' ) ? APOLLO_SOCIAL_PLUGIN_URL : '',
					'capabilities' => array( 'social_interact', 'moderate_social' ),
					'supports'     => array(
						'social_buttons',
						'activity_stream',
						'likes',
						'favorites',
						'shares',
						'comments',
						'groups',
						'members',
						'gamification',
					),
					'hooks'        => array(
						'apollo_core_social_buttons',
						'apollo_core_user_data_transform',
					),
				)
			);
		}
	}

	/**
	 * Render social buttons
	 *
	 * @param string $html    Existing HTML.
	 * @param int    $post_id Post ID.
	 * @param string $context Context (event, dj, local, post).
	 * @param array  $args    Display arguments.
	 * @return string
	 */
	public function render_social_buttons( string $html, int $post_id, string $context, array $args ): string {
		$args = wp_parse_args( $args, $this->button_defaults );

		// Get current user data.
		$user_id      = get_current_user_id();
		$is_liked     = $this->is_liked( $post_id, $user_id );
		$is_favorited = $this->is_favorited( $post_id, $user_id );
		$like_count   = $this->get_like_count( $post_id );
		$fav_count    = $this->get_favorite_count( $post_id );

		// Size classes.
		$size_classes = array(
			'small'  => 'w-6 h-6 text-sm',
			'medium' => 'w-8 h-8 text-base',
			'large'  => 'w-10 h-10 text-lg',
		);

		$size_class = $size_classes[ $args['size'] ] ?? $size_classes['medium'];

		// Build HTML.
		ob_start();
		?>
		<div class="apollo-social-buttons flex items-center gap-2" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>" data-context="<?php echo esc_attr( $context ); ?>">
			<?php if ( $args['show_like'] ) : ?>
				<button
					type="button"
					class="apollo-like-btn flex items-center gap-1 px-3 py-1.5 rounded-full transition-colors <?php echo $is_liked ? 'text-red-500 bg-red-50' : 'text-gray-500 hover:text-red-500 hover:bg-red-50'; ?>"
					data-action="like"
					data-liked="<?php echo $is_liked ? 'true' : 'false'; ?>"
					aria-label="<?php echo $is_liked ? esc_attr__( 'Descurtir', 'apollo-social' ) : esc_attr__( 'Curtir', 'apollo-social' ); ?>"
				>
					<i class="ri-heart-<?php echo $is_liked ? 'fill' : 'line'; ?> <?php echo esc_attr( $size_class ); ?>"></i>
					<span class="like-count"><?php echo esc_html( (string) $like_count ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( $args['show_favorite'] ) : ?>
				<button
					type="button"
					class="apollo-favorite-btn flex items-center gap-1 px-3 py-1.5 rounded-full transition-colors <?php echo $is_favorited ? 'text-yellow-500 bg-yellow-50' : 'text-gray-500 hover:text-yellow-500 hover:bg-yellow-50'; ?>"
					data-action="favorite"
					data-favorited="<?php echo $is_favorited ? 'true' : 'false'; ?>"
					aria-label="<?php echo $is_favorited ? esc_attr__( 'Remover dos favoritos', 'apollo-social' ) : esc_attr__( 'Adicionar aos favoritos', 'apollo-social' ); ?>"
				>
					<i class="ri-star-<?php echo $is_favorited ? 'fill' : 'line'; ?> <?php echo esc_attr( $size_class ); ?>"></i>
					<span class="favorite-count"><?php echo esc_html( (string) $fav_count ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( $args['show_share'] ) : ?>
				<button
					type="button"
					class="apollo-share-btn flex items-center gap-1 px-3 py-1.5 rounded-full text-gray-500 hover:text-blue-500 hover:bg-blue-50 transition-colors"
					data-action="share"
					aria-label="<?php esc_attr_e( 'Compartilhar', 'apollo-social' ); ?>"
				>
					<i class="ri-share-line <?php echo esc_attr( $size_class ); ?>"></i>
					<span class="sr-only"><?php esc_html_e( 'Compartilhar', 'apollo-social' ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( $args['show_comment'] ) : ?>
				<button
					type="button"
					class="apollo-comment-btn flex items-center gap-1 px-3 py-1.5 rounded-full text-gray-500 hover:text-green-500 hover:bg-green-50 transition-colors"
					data-action="comment"
					aria-label="<?php esc_attr_e( 'Comentar', 'apollo-social' ); ?>"
				>
					<i class="ri-chat-3-line <?php echo esc_attr( $size_class ); ?>"></i>
					<span class="comment-count"><?php echo esc_html( (string) get_comments_number( $post_id ) ); ?></span>
				</button>
			<?php endif; ?>
		</div>

		<!-- Share Modal (hidden by default) -->
		<div class="apollo-share-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
			<div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
				<div class="flex justify-between items-center mb-4">
					<h3 class="text-lg font-semibold"><?php esc_html_e( 'Compartilhar', 'apollo-social' ); ?></h3>
					<button type="button" class="apollo-share-close text-gray-400 hover:text-gray-600">
						<i class="ri-close-line text-xl"></i>
					</button>
				</div>
				<div class="grid grid-cols-4 gap-4">
					<a href="<?php echo esc_url( $this->get_share_url( 'facebook', $post_id ) ); ?>" target="_blank" rel="noopener" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
						<i class="ri-facebook-fill text-2xl text-blue-600"></i>
						<span class="text-xs mt-1">Facebook</span>
					</a>
					<a href="<?php echo esc_url( $this->get_share_url( 'twitter', $post_id ) ); ?>" target="_blank" rel="noopener" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
						<i class="ri-twitter-x-fill text-2xl text-black"></i>
						<span class="text-xs mt-1">X</span>
					</a>
					<a href="<?php echo esc_url( $this->get_share_url( 'whatsapp', $post_id ) ); ?>" target="_blank" rel="noopener" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
						<i class="ri-whatsapp-fill text-2xl text-green-500"></i>
						<span class="text-xs mt-1">WhatsApp</span>
					</a>
					<button type="button" class="apollo-copy-link flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors" data-url="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
						<i class="ri-link text-2xl text-gray-600"></i>
						<span class="text-xs mt-1"><?php esc_html_e( 'Copiar', 'apollo-social' ); ?></span>
					</button>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if post is liked by user
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_liked( int $post_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$likes = get_user_meta( $user_id, 'apollo_liked_posts', true );
		return is_array( $likes ) && in_array( $post_id, $likes, true );
	}

	/**
	 * Check if post is favorited by user
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_favorited( int $post_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$favorites = get_user_meta( $user_id, 'apollo_favorite_posts', true );
		return is_array( $favorites ) && in_array( $post_id, $favorites, true );
	}

	/**
	 * Get like count
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function get_like_count( int $post_id ): int {
		return absint( get_post_meta( $post_id, '_apollo_likes_count', true ) );
	}

	/**
	 * Get favorite count
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function get_favorite_count( int $post_id ): int {
		return absint( get_post_meta( $post_id, '_favorites_count', true ) );
	}

	/**
	 * Get share URL for social network
	 *
	 * @param string $network Social network.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	private function get_share_url( string $network, int $post_id ): string {
		$url   = get_permalink( $post_id );
		$title = rawurlencode( get_the_title( $post_id ) );

		return match ( $network ) {
			'facebook'  => "https://www.facebook.com/sharer/sharer.php?u={$url}",
			'twitter'   => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
			'whatsapp'  => "https://wa.me/?text={$title}%20{$url}",
			'telegram'  => "https://t.me/share/url?url={$url}&text={$title}",
			'linkedin'  => "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}",
			default     => $url,
		};
	}

	/**
	 * Transform user data
	 *
	 * @param array $user_data User data.
	 * @param int   $user_id   User ID.
	 * @return array
	 */
	public function transform_user_data( array $user_data, int $user_id ): array {
		// Add social stats.
		$user_data['social_stats'] = array(
			'followers' => $this->get_follower_count( $user_id ),
			'following' => $this->get_following_count( $user_id ),
			'posts'     => $this->get_user_posts_count( $user_id ),
			'points'    => $this->get_user_points( $user_id ),
			'level'     => $this->get_user_level( $user_id ),
		);

		// Add verification status.
		$user_data['is_verified'] = $this->is_user_verified( $user_id );

		// Add badges.
		$user_data['badges'] = $this->get_user_badges( $user_id );

		// Add bubble info.
		$user_data['bubble'] = array(
			'close_friends' => $this->get_close_friends_count( $user_id ),
			'in_bubble'     => $this->is_in_user_bubble( $user_id, get_current_user_id() ),
		);

		return $user_data;
	}

	/**
	 * Get follower count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_follower_count( int $user_id ): int {
		$followers = get_user_meta( $user_id, 'apollo_followers', true );
		return is_array( $followers ) ? count( $followers ) : 0;
	}

	/**
	 * Get following count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_following_count( int $user_id ): int {
		$following = get_user_meta( $user_id, 'apollo_following', true );
		return is_array( $following ) ? count( $following ) : 0;
	}

	/**
	 * Get user posts count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_user_posts_count( int $user_id ): int {
		return count_user_posts( $user_id, 'apollo_social_post' );
	}

	/**
	 * Get user points
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_user_points( int $user_id ): int {
		return absint( get_user_meta( $user_id, 'apollo_points', true ) );
	}

	/**
	 * Get user level
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_user_level( int $user_id ): int {
		$points = $this->get_user_points( $user_id );
		// Level formula: 1 level per 100 points.
		return max( 1, (int) floor( $points / 100 ) + 1 );
	}

	/**
	 * Check if user is verified
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_user_verified( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, 'apollo_verified', true );
	}

	/**
	 * Get user badges
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_user_badges( int $user_id ): array {
		$badges = get_user_meta( $user_id, 'apollo_badges', true );
		return is_array( $badges ) ? $badges : array();
	}

	/**
	 * Get close friends count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_close_friends_count( int $user_id ): int {
		$close_friends = get_user_meta( $user_id, 'apollo_close_friends', true );
		return is_array( $close_friends ) ? count( $close_friends ) : 0;
	}

	/**
	 * Check if user is in another user's bubble
	 *
	 * @param int $target_user_id Target user ID.
	 * @param int $current_user_id Current user ID.
	 * @return bool
	 */
	private function is_in_user_bubble( int $target_user_id, int $current_user_id ): bool {
		if ( ! $current_user_id ) {
			return false;
		}

		$bubble = get_user_meta( $target_user_id, 'apollo_bubble', true );
		return is_array( $bubble ) && in_array( $current_user_id, $bubble, true );
	}

	/**
	 * Add social features after event display
	 *
	 * @param int   $event_id Event ID.
	 * @param array $args     Display args.
	 * @return void
	 */
	public function add_social_features( int $event_id, array $args = array() ): void {
		// Add interested users section.
		$this->render_interested_users( $event_id );
	}

	/**
	 * Render interested users section
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function render_interested_users( int $event_id ): void {
		$interested_users = get_post_meta( $event_id, '_event_interested_users', true );

		if ( empty( $interested_users ) || ! is_array( $interested_users ) ) {
			return;
		}

		$count         = count( $interested_users );
		$display_users = array_slice( $interested_users, 0, 5 );
		?>
		<div class="apollo-interested-users mt-4 pt-4 border-t border-gray-200">
			<div class="flex items-center gap-2">
				<div class="flex -space-x-2">
					<?php foreach ( $display_users as $user_id ) : ?>
						<?php $avatar_url = get_avatar_url( $user_id, array( 'size' => 32 ) ); ?>
						<img
							src="<?php echo esc_url( $avatar_url ); ?>"
							alt=""
							class="w-8 h-8 rounded-full border-2 border-white"
						>
					<?php endforeach; ?>
				</div>
				<span class="text-sm text-gray-600">
					<?php
					printf(
						esc_html( _n( '%d pessoa interessada', '%d pessoas interessadas', $count, 'apollo-social' ) ),
						$count
					);
					?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Inject activity data into templates
	 *
	 * @param string $template Template name.
	 * @param array  $data     Template data.
	 * @return void
	 */
	public function inject_activity_data( string $template, array $data ): void {
		// Only inject for relevant templates.
		$activity_templates = array( 'activity-stream', 'member-profile', 'group-single' );

		if ( ! in_array( $template, $activity_templates, true ) ) {
			return;
		}

		// Add recent activity to global.
		$GLOBALS['apollo_activity_data'] = $this->get_recent_activity();
	}

	/**
	 * Get recent activity
	 *
	 * @param int $limit Number of items.
	 * @return array
	 */
	private function get_recent_activity( int $limit = 10 ): array {
		// This would query the activity stream - simplified for example.
		return array();
	}
}

// Initialize integration.
add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'APOLLO_CORE_BOOTSTRAPPED' ) || class_exists( 'Apollo_Core' ) ) {
			Apollo_Social_Core_Integration::get_instance();
		}
	},
	15
);
