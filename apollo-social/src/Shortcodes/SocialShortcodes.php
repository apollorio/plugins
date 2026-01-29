<?php
/**
 * Apollo Social - Unified Shortcodes
 *
 * PSR-4 compliant shortcodes for social features.
 * Provides user profiles, social feeds, classifieds, and share functionality.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Shortcodes;

use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SocialShortcodes
 *
 * Unified social shortcodes with template loader integration.
 */
class SocialShortcodes {

	/**
	 * Singleton instance
	 *
	 * @var SocialShortcodes|null
	 */
	private static ?SocialShortcodes $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return SocialShortcodes
	 */
	public static function get_instance(): SocialShortcodes {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes
	 *
	 * @return void
	 */
	private function register_shortcodes(): void {
		// Social Feed.
		add_shortcode( 'apollo_social_feed', array( $this, 'render_social_feed' ) );

		// Social Share Buttons.
		add_shortcode( 'apollo_social_share', array( $this, 'render_social_share' ) );

		// User Profile.
		add_shortcode( 'apollo_user_profile', array( $this, 'render_user_profile' ) );

		// Profile Card (compact).
		add_shortcode( 'apollo_profile_card', array( $this, 'render_profile_card' ) );

		// Classifieds.
		add_shortcode( 'apollo_classifieds', array( $this, 'render_classifieds' ) );

		// Classified Form.
		add_shortcode( 'apollo_classified_form', array( $this, 'render_classified_form' ) );

		// User Dashboard.
		add_shortcode( 'apollo_user_dashboard', array( $this, 'render_user_dashboard' ) );

		// Follow Button.
		add_shortcode( 'apollo_follow_button', array( $this, 'render_follow_button' ) );

		// User Activity Feed.
		add_shortcode( 'apollo_user_activity', array( $this, 'render_user_activity' ) );
	}

	// =========================================================================
	// [apollo_social_feed] - Social Feed Display
	// =========================================================================

	/**
	 * Render social feed shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_social_feed( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'user_id'   => 0,
				'limit'     => 10,
				'type'      => 'all',
				'layout'    => 'timeline',
				'show_form' => 'true',
			),
			$atts,
			'apollo_social_feed'
		);

		$this->enqueue_assets();

		$user_id = absint( $atts['user_id'] ) ?: get_current_user_id();
		$limit   = absint( $atts['limit'] );

		// Query activity posts.
		$args = array(
			'post_type'      => 'apollo_activity',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Filter by user if specified.
		if ( $user_id && 'all' !== $atts['type'] ) {
			$args['author'] = $user_id;
		}

		// Filter by activity type.
		if ( 'all' !== $atts['type'] ) {
			$args['meta_query'][] = array(
				'key'   => '_activity_type',
				'value' => sanitize_key( $atts['type'] ),
			);
		}

		$query = new WP_Query( $args );

		ob_start();
		?>
		<div class="apollo-social-feed apollo-social-feed--<?php echo esc_attr( $atts['layout'] ); ?>">
			<?php if ( 'true' === $atts['show_form'] && is_user_logged_in() ) : ?>
				<?php echo $this->render_activity_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<div class="apollo-feed__list" id="apollo-feed-list">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : ?>
						<?php $query->the_post(); ?>
						<?php echo $this->render_activity_item( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="apollo-empty-state apollo-empty-state--feed">
						<i class="ri-chat-3-line"></i>
						<p><?php esc_html_e( 'Nenhuma atividade ainda.', 'apollo-social' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $query->max_num_pages > 1 ) : ?>
				<div class="apollo-feed__load-more">
					<button type="button" class="apollo-btn apollo-btn--outline apollo-btn--full" data-action="load-more">
						<i class="ri-loader-4-line"></i>
						<?php esc_html_e( 'Carregar mais', 'apollo-social' ); ?>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render activity form
	 *
	 * @return string
	 */
	private function render_activity_form(): string {
		$current_user = wp_get_current_user();

		ob_start();
		?>
		<div class="apollo-activity-form apollo-card">
			<form id="apollo-activity-form" data-ajax="true">
				<?php wp_nonce_field( 'apollo_activity_nonce', 'apollo_nonce' ); ?>
				<div class="apollo-activity-form__header">
					<div class="apollo-avatar apollo-avatar--sm">
						<?php echo get_avatar( $current_user->ID, 40, '', '', array( 'class' => 'apollo-avatar__img' ) ); ?>
					</div>
					<textarea
						name="activity_content"
						class="apollo-input apollo-input--textarea"
						placeholder="<?php esc_attr_e( 'O que você está pensando?', 'apollo-social' ); ?>"
						rows="2"
						required
					></textarea>
				</div>
				<div class="apollo-activity-form__footer">
					<div class="apollo-activity-form__actions">
						<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--icon" data-action="add-image">
							<i class="ri-image-line"></i>
						</button>
						<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--icon" data-action="add-link">
							<i class="ri-link"></i>
						</button>
						<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--icon" data-action="add-emoji">
							<i class="ri-emotion-line"></i>
						</button>
					</div>
					<button type="submit" class="apollo-btn apollo-btn--primary apollo-btn--sm">
						<?php esc_html_e( 'Publicar', 'apollo-social' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single activity item
	 *
	 * @param int $activity_id Activity post ID.
	 * @return string
	 */
	private function render_activity_item( int $activity_id ): string {
		$author_id = (int) get_post_field( 'post_author', $activity_id );
		$author    = get_userdata( $author_id );
		$content   = get_the_content( null, false, $activity_id );
		$timestamp = get_the_date( '', $activity_id ) . ' ' . get_the_time( '', $activity_id );

		ob_start();
		?>
		<article class="apollo-activity-item apollo-card" data-id="<?php echo esc_attr( (string) $activity_id ); ?>">
			<div class="apollo-activity-item__header">
				<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-avatar">
					<?php echo get_avatar( $author_id, 48, '', '', array( 'class' => 'apollo-avatar__img' ) ); ?>
				</a>
				<div class="apollo-activity-item__meta">
					<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="apollo-activity-item__author">
						<?php echo esc_html( $author ? $author->display_name : __( 'Usuário', 'apollo-social' ) ); ?>
					</a>
					<time class="apollo-activity-item__time" datetime="<?php echo esc_attr( get_the_date( 'c', $activity_id ) ); ?>">
						<?php echo esc_html( human_time_diff( get_the_time( 'U', $activity_id ), current_time( 'timestamp' ) ) ); ?> atrás
					</time>
				</div>
				<div class="apollo-activity-item__options">
					<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--icon" data-dropdown-toggle>
						<i class="ri-more-2-line"></i>
					</button>
				</div>
			</div>
			<div class="apollo-activity-item__content">
				<?php echo wp_kses_post( $content ); ?>
			</div>
			<div class="apollo-activity-item__footer">
				<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--sm" data-action="like" data-id="<?php echo esc_attr( (string) $activity_id ); ?>">
					<i class="ri-heart-line"></i>
					<span class="count"><?php echo esc_html( (string) absint( get_post_meta( $activity_id, '_like_count', true ) ) ); ?></span>
				</button>
				<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--sm" data-action="comment" data-id="<?php echo esc_attr( (string) $activity_id ); ?>">
					<i class="ri-chat-3-line"></i>
					<span class="count"><?php echo esc_html( (string) get_comments_number( $activity_id ) ); ?></span>
				</button>
				<button type="button" class="apollo-btn apollo-btn--ghost apollo-btn--sm" data-action="share" data-id="<?php echo esc_attr( (string) $activity_id ); ?>">
					<i class="ri-share-line"></i>
				</button>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_social_share] - Social Share Buttons
	// =========================================================================

	/**
	 * Render social share shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_social_share( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'url'      => '',
				'title'    => '',
				'networks' => 'facebook,twitter,whatsapp,linkedin,telegram',
				'style'    => 'icons',
				'size'     => 'md',
			),
			$atts,
			'apollo_social_share'
		);

		$url   = $atts['url'] ?: get_permalink();
		$title = $atts['title'] ?: get_the_title();

		$networks = \array_map( 'trim', \explode( ',', $atts['networks'] ) );

		$share_links = $this->get_share_links( $url, $title );

		$this->enqueue_assets();

		ob_start();
		?>
		<div class="apollo-social-share apollo-social-share--<?php echo esc_attr( $atts['style'] ); ?> apollo-social-share--<?php echo esc_attr( $atts['size'] ); ?>">
			<?php foreach ( $networks as $network ) : ?>
				<?php if ( isset( $share_links[ $network ] ) ) : ?>
					<a
						href="<?php echo esc_url( $share_links[ $network ]['url'] ); ?>"
						class="apollo-social-share__btn apollo-social-share__btn--<?php echo esc_attr( $network ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( sprintf( __( 'Compartilhar no %s', 'apollo-social' ), $share_links[ $network ]['name'] ) ); ?>"
					>
						<i class="<?php echo esc_attr( $share_links[ $network ]['icon'] ); ?>"></i>
						<?php if ( 'buttons' === $atts['style'] ) : ?>
							<span><?php echo esc_html( $share_links[ $network ]['name'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get share links for all networks
	 *
	 * @param string $url   URL to share.
	 * @param string $title Title to share.
	 * @return array
	 */
	private function get_share_links( string $url, string $title ): array {
		$encoded_url   = rawurlencode( $url );
		$encoded_title = rawurlencode( $title );

		return array(
			'facebook'  => array(
				'name' => 'Facebook',
				'icon' => 'ri-facebook-fill',
				'url'  => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
			),
			'twitter'   => array(
				'name' => 'Twitter',
				'icon' => 'ri-twitter-x-fill',
				'url'  => "https://twitter.com/intent/tweet?url={$encoded_url}&text={$encoded_title}",
			),
			'whatsapp'  => array(
				'name' => 'WhatsApp',
				'icon' => 'ri-whatsapp-fill',
				'url'  => "https://api.whatsapp.com/send?text={$encoded_title}%20{$encoded_url}",
			),
			'linkedin'  => array(
				'name' => 'LinkedIn',
				'icon' => 'ri-linkedin-fill',
				'url'  => "https://www.linkedin.com/shareArticle?mini=true&url={$encoded_url}&title={$encoded_title}",
			),
			'telegram'  => array(
				'name' => 'Telegram',
				'icon' => 'ri-telegram-fill',
				'url'  => "https://t.me/share/url?url={$encoded_url}&text={$encoded_title}",
			),
			'pinterest' => array(
				'name' => 'Pinterest',
				'icon' => 'ri-pinterest-fill',
				'url'  => "https://pinterest.com/pin/create/button/?url={$encoded_url}&description={$encoded_title}",
			),
			'email'     => array(
				'name' => 'Email',
				'icon' => 'ri-mail-fill',
				'url'  => "mailto:?subject={$encoded_title}&body={$encoded_url}",
			),
			'copy'      => array(
				'name' => 'Copiar',
				'icon' => 'ri-file-copy-line',
				'url'  => '#copy',
			),
		);
	}

	// =========================================================================
	// [apollo_user_profile] - Full User Profile
	// =========================================================================

	/**
	 * Render user profile shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_user_profile( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'user_id'     => 0,
				'show_cover'  => 'true',
				'show_bio'    => 'true',
				'show_stats'  => 'true',
				'show_social' => 'true',
			),
			$atts,
			'apollo_user_profile'
		);

		$user_id = absint( $atts['user_id'] );

		if ( ! $user_id ) {
			// Try to get from query var or current user.
			$user_id = get_query_var( 'author' ) ?: get_current_user_id();
		}

		if ( ! $user_id ) {
			return $this->render_empty_state( __( 'Usuário não encontrado.', 'apollo-social' ) );
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return $this->render_empty_state( __( 'Usuário não encontrado.', 'apollo-social' ) );
		}

		$this->enqueue_assets();

		$profile_data = $this->get_user_profile_data( $user );

		ob_start();
		?>
		<div class="apollo-user-profile">
			<?php if ( 'true' === $atts['show_cover'] ) : ?>
				<div class="apollo-user-profile__cover" style="<?php echo $profile_data['cover'] ? 'background-image: url(' . esc_url( $profile_data['cover'] ) . ')' : ''; ?>">
					<div class="apollo-user-profile__avatar-container">
						<div class="apollo-avatar apollo-avatar--xl apollo-avatar--ring">
							<?php echo get_avatar( $user_id, 150, '', '', array( 'class' => 'apollo-avatar__img' ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="apollo-user-profile__body">
				<header class="apollo-user-profile__header">
					<h1 class="apollo-user-profile__name"><?php echo esc_html( $user->display_name ); ?></h1>
					<?php if ( $profile_data['username'] ) : ?>
						<span class="apollo-user-profile__username">@<?php echo esc_html( $profile_data['username'] ); ?></span>
					<?php endif; ?>

					<?php if ( $profile_data['role_label'] ) : ?>
						<span class="apollo-badge apollo-badge--<?php echo esc_attr( $profile_data['role_class'] ); ?>">
							<?php echo esc_html( $profile_data['role_label'] ); ?>
						</span>
					<?php endif; ?>
				</header>

				<?php if ( 'true' === $atts['show_bio'] && $profile_data['bio'] ) : ?>
					<div class="apollo-user-profile__bio">
						<?php echo wp_kses_post( wpautop( $profile_data['bio'] ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'true' === $atts['show_stats'] ) : ?>
					<div class="apollo-user-profile__stats">
						<div class="apollo-stat">
							<span class="apollo-stat__value"><?php echo esc_html( number_format_i18n( $profile_data['events_count'] ) ); ?></span>
							<span class="apollo-stat__label"><?php esc_html_e( 'Eventos', 'apollo-social' ); ?></span>
						</div>
						<div class="apollo-stat">
							<span class="apollo-stat__value"><?php echo esc_html( number_format_i18n( $profile_data['followers_count'] ) ); ?></span>
							<span class="apollo-stat__label"><?php esc_html_e( 'Seguidores', 'apollo-social' ); ?></span>
						</div>
						<div class="apollo-stat">
							<span class="apollo-stat__value"><?php echo esc_html( number_format_i18n( $profile_data['following_count'] ) ); ?></span>
							<span class="apollo-stat__label"><?php esc_html_e( 'Seguindo', 'apollo-social' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( 'true' === $atts['show_social'] && ! empty( $profile_data['social_links'] ) ) : ?>
					<div class="apollo-user-profile__social">
						<?php foreach ( $profile_data['social_links'] as $network => $link ) : ?>
							<a href="<?php echo esc_url( $link ); ?>" class="apollo-btn apollo-btn--ghost apollo-btn--icon" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $network ); ?>">
								<i class="ri-<?php echo esc_attr( $network ); ?>-fill"></i>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( get_current_user_id() !== $user_id ) : ?>
					<div class="apollo-user-profile__actions">
						<?php echo $this->render_follow_button( array( 'user_id' => $user_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a href="<?php echo esc_url( home_url( '/mensagens/?to=' . $user_id ) ); ?>" class="apollo-btn apollo-btn--outline">
							<i class="ri-message-3-line"></i>
							<?php esc_html_e( 'Mensagem', 'apollo-social' ); ?>
						</a>
					</div>
				<?php elseif ( is_user_logged_in() ) : ?>
					<div class="apollo-user-profile__actions">
						<a href="<?php echo esc_url( home_url( '/editar-perfil/' ) ); ?>" class="apollo-btn apollo-btn--outline">
							<i class="ri-edit-line"></i>
							<?php esc_html_e( 'Editar Perfil', 'apollo-social' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get user profile data
	 *
	 * @param WP_User $user User object.
	 * @return array
	 */
	private function get_user_profile_data( WP_User $user ): array {
		$user_id = $user->ID;

		// Get role label.
		$role_label = '';
		$role_class = 'default';

		$roles = $user->roles;

		if ( in_array( 'dj', $roles, true ) || in_array( 'artist', $roles, true ) ) {
			$role_label = __( 'DJ/Artista', 'apollo-social' );
			$role_class = 'primary';
		} elseif ( in_array( 'promoter', $roles, true ) || in_array( 'event_organizer', $roles, true ) ) {
			$role_label = __( 'Promoter', 'apollo-social' );
			$role_class = 'warning';
		} elseif ( in_array( 'venue', $roles, true ) || in_array( 'local_owner', $roles, true ) ) {
			$role_label = __( 'Local', 'apollo-social' );
			$role_class = 'info';
		}

		// Get social links.
		$social_links = array();
		$social_meta  = array(
			'instagram'  => get_user_meta( $user_id, 'instagram', true ),
			'facebook'   => get_user_meta( $user_id, 'facebook', true ),
			'twitter'    => get_user_meta( $user_id, 'twitter', true ),
			'soundcloud' => get_user_meta( $user_id, 'soundcloud', true ),
			'spotify'    => get_user_meta( $user_id, 'spotify', true ),
			'youtube'    => get_user_meta( $user_id, 'youtube', true ),
		);

		foreach ( $social_meta as $network => $url ) {
			if ( ! empty( $url ) ) {
				$social_links[ $network ] = $url;
			}
		}

		// Get counts.
		$events_count    = count_user_posts( $user_id, 'event_listing' );
		$followers_count = absint( get_user_meta( $user_id, '_followers_count', true ) );
		$following_count = absint( get_user_meta( $user_id, '_following_count', true ) );

		return array(
			'id'              => $user_id,
			'username'        => $user->user_login,
			'bio'             => get_user_meta( $user_id, 'description', true ),
			'cover'           => get_user_meta( $user_id, '_cover_image', true ),
			'role_label'      => $role_label,
			'role_class'      => $role_class,
			'events_count'    => $events_count,
			'followers_count' => $followers_count,
			'following_count' => $following_count,
			'social_links'    => $social_links,
			'location'        => get_user_meta( $user_id, '_city', true ),
			'website'         => $user->user_url,
		);
	}

	// =========================================================================
	// [apollo_profile_card] - Compact Profile Card
	// =========================================================================

	/**
	 * Render profile card shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_profile_card( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'user_id' => 0,
				'size'    => 'md',
				'style'   => 'card',
			),
			$atts,
			'apollo_profile_card'
		);

		$user_id = absint( $atts['user_id'] );

		if ( ! $user_id ) {
			return '';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return '';
		}

		$this->enqueue_assets();

		$profile_data = $this->get_user_profile_data( $user );

		ob_start();
		?>
		<div class="apollo-profile-card apollo-card apollo-profile-card--<?php echo esc_attr( $atts['size'] ); ?> apollo-profile-card--<?php echo esc_attr( $atts['style'] ); ?>">
			<a href="<?php echo esc_url( get_author_posts_url( $user_id ) ); ?>" class="apollo-profile-card__link">
				<div class="apollo-avatar apollo-avatar--<?php echo esc_attr( $atts['size'] ); ?>">
					<?php echo get_avatar( $user_id, 80, '', '', array( 'class' => 'apollo-avatar__img' ) ); ?>
				</div>
				<div class="apollo-profile-card__info">
					<span class="apollo-profile-card__name"><?php echo esc_html( $user->display_name ); ?></span>
					<?php if ( $profile_data['role_label'] ) : ?>
						<span class="apollo-profile-card__role"><?php echo esc_html( $profile_data['role_label'] ); ?></span>
					<?php endif; ?>
				</div>
			</a>
			<?php if ( get_current_user_id() !== $user_id ) : ?>
				<button type="button" class="apollo-btn apollo-btn--primary apollo-btn--sm" data-action="follow" data-user-id="<?php echo esc_attr( (string) $user_id ); ?>">
					<?php esc_html_e( 'Seguir', 'apollo-social' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_classifieds] - Classifieds Listing
	// =========================================================================

	/**
	 * Render classifieds shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_classifieds( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit'    => 12,
				'category' => '',
				'type'     => '',
				'user_id'  => 0,
				'layout'   => 'grid',
				'columns'  => 3,
			),
			$atts,
			'apollo_classifieds'
		);

		$this->enqueue_assets();

		$args = array(
			'post_type'      => 'apollo_classified',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'classified_domain',
				'field'    => 'slug',
				'terms'    => \array_map( 'trim', \explode( ',', $atts['category'] ) ),
			);
		}

		if ( ! empty( $atts['type'] ) ) {
			$args['meta_query'][] = array(
				'key'   => '_classified_type',
				'value' => sanitize_key( $atts['type'] ),
			);
		}

		if ( absint( $atts['user_id'] ) ) {
			$args['author'] = absint( $atts['user_id'] );
		}

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $this->render_empty_state( __( 'Nenhum anúncio encontrado.', 'apollo-social' ) );
		}

		$columns = min( max( absint( $atts['columns'] ), 1 ), 4 );

		ob_start();
		?>
		<div class="apollo-classifieds apollo-classifieds--<?php echo esc_attr( $atts['layout'] ); ?>">
			<div class="apollo-grid apollo-grid--<?php echo esc_attr( (string) $columns ); ?>">
				<?php while ( $query->have_posts() ) : ?>
					<?php $query->the_post(); ?>
					<?php echo $this->render_classified_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endwhile; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render classified card
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function render_classified_card( int $post_id ): string {
		$price     = get_post_meta( $post_id, '_classified_price', true );
		$type      = get_post_meta( $post_id, '_classified_type', true );
		$location  = get_post_meta( $post_id, '_classified_location', true );
		$thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' );
		$author_id = (int) get_post_field( 'post_author', $post_id );

		ob_start();
		?>
		<article class="apollo-card apollo-card--classified">
			<?php if ( $thumbnail ) : ?>
				<div class="apollo-card__media">
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
						<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy">
					</a>
					<?php if ( $type ) : ?>
						<span class="apollo-card__badge apollo-badge apollo-badge--<?php echo 'venda' === $type ? 'success' : 'info'; ?>">
							<?php echo esc_html( 'venda' === $type ? __( 'Venda', 'apollo-social' ) : __( 'Troca', 'apollo-social' ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="apollo-card__body">
				<h3 class="apollo-card__title">
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
						<?php echo esc_html( get_the_title( $post_id ) ); ?>
					</a>
				</h3>
				<?php if ( $price ) : ?>
					<p class="apollo-card__price">
						<?php echo esc_html( 'R$ ' . number_format( (float) $price, 2, ',', '.' ) ); ?>
					</p>
				<?php endif; ?>
				<?php if ( $location ) : ?>
					<p class="apollo-card__location">
						<i class="ri-map-pin-line"></i>
						<?php echo esc_html( $location ); ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="apollo-card__footer">
				<div class="apollo-card__author">
					<?php echo get_avatar( $author_id, 24, '', '', array( 'class' => 'apollo-avatar__img apollo-avatar--xs' ) ); ?>
					<span><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></span>
				</div>
				<time class="apollo-card__time"><?php echo esc_html( human_time_diff( get_the_time( 'U', $post_id ), current_time( 'timestamp' ) ) ); ?></time>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_classified_form] - Classified Submission Form
	// =========================================================================

	/**
	 * Render classified form shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_classified_form( $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return $this->render_login_required( __( 'Faça login para publicar anúncios.', 'apollo-social' ) );
		}

		$this->enqueue_assets();

		ob_start();
		?>
		<div class="apollo-classified-form apollo-card">
			<div class="apollo-card__header">
				<h3><?php esc_html_e( 'Novo Anúncio', 'apollo-social' ); ?></h3>
			</div>
			<form id="apollo-classified-form" class="apollo-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'apollo_classified_submit', 'apollo_nonce' ); ?>

				<div class="apollo-form__group">
					<label for="classified_title" class="apollo-form__label"><?php esc_html_e( 'Título', 'apollo-social' ); ?> <span class="required">*</span></label>
					<input type="text" id="classified_title" name="classified_title" class="apollo-input" required>
				</div>

				<div class="apollo-form__row">
					<div class="apollo-form__group">
						<label for="classified_type" class="apollo-form__label"><?php esc_html_e( 'Tipo', 'apollo-social' ); ?></label>
						<select id="classified_type" name="classified_type" class="apollo-select">
							<option value="venda"><?php esc_html_e( 'Venda', 'apollo-social' ); ?></option>
							<option value="troca"><?php esc_html_e( 'Troca', 'apollo-social' ); ?></option>
							<option value="doacao"><?php esc_html_e( 'Doação', 'apollo-social' ); ?></option>
						</select>
					</div>
					<div class="apollo-form__group">
						<label for="classified_price" class="apollo-form__label"><?php esc_html_e( 'Preço (R$)', 'apollo-social' ); ?></label>
						<input type="number" id="classified_price" name="classified_price" class="apollo-input" step="0.01" min="0">
					</div>
				</div>

				<div class="apollo-form__group">
					<label for="classified_description" class="apollo-form__label"><?php esc_html_e( 'Descrição', 'apollo-social' ); ?> <span class="required">*</span></label>
					<textarea id="classified_description" name="classified_description" class="apollo-input apollo-input--textarea" rows="5" required></textarea>
				</div>

				<div class="apollo-form__group">
					<label for="classified_location" class="apollo-form__label"><?php esc_html_e( 'Localização', 'apollo-social' ); ?></label>
					<input type="text" id="classified_location" name="classified_location" class="apollo-input" placeholder="<?php esc_attr_e( 'Ex: Rio de Janeiro, RJ', 'apollo-social' ); ?>">
				</div>

				<div class="apollo-form__group">
					<label class="apollo-form__label"><?php esc_html_e( 'Imagens', 'apollo-social' ); ?></label>
					<div class="apollo-dropzone" data-max-files="5" data-accepted-files="image/*">
						<i class="ri-image-add-line"></i>
						<p><?php esc_html_e( 'Arraste imagens ou clique para selecionar', 'apollo-social' ); ?></p>
						<input type="file" name="classified_images[]" multiple accept="image/*" class="apollo-dropzone__input">
					</div>
				</div>

				<div class="apollo-form__actions">
					<button type="submit" class="apollo-btn apollo-btn--primary">
						<?php esc_html_e( 'Publicar Anúncio', 'apollo-social' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_user_dashboard] - User Dashboard
	// =========================================================================

	/**
	 * Render user dashboard shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_user_dashboard( $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return $this->render_login_required();
		}

		$atts = shortcode_atts(
			array(
				'show_events'      => 'true',
				'show_classifieds' => 'true',
				'show_activity'    => 'true',
			),
			$atts,
			'apollo_user_dashboard'
		);

		$this->enqueue_assets();

		$current_user = wp_get_current_user();

		ob_start();
		?>
		<div class="apollo-dashboard">
			<div class="apollo-dashboard__header">
				<h2><?php esc_html_e( 'Meu Painel', 'apollo-social' ); ?></h2>
				<p><?php echo esc_html( sprintf( __( 'Bem-vindo, %s!', 'apollo-social' ), $current_user->display_name ) ); ?></p>
			</div>

			<div class="apollo-dashboard__stats apollo-grid apollo-grid--4">
				<div class="apollo-stat-card apollo-card">
					<div class="apollo-stat-card__icon"><i class="ri-calendar-event-line"></i></div>
					<div class="apollo-stat-card__content">
						<span class="apollo-stat-card__value"><?php echo esc_html( (string) count_user_posts( $current_user->ID, 'event_listing' ) ); ?></span>
						<span class="apollo-stat-card__label"><?php esc_html_e( 'Meus Eventos', 'apollo-social' ); ?></span>
					</div>
				</div>
				<div class="apollo-stat-card apollo-card">
					<div class="apollo-stat-card__icon"><i class="ri-price-tag-3-line"></i></div>
					<div class="apollo-stat-card__content">
						<span class="apollo-stat-card__value"><?php echo esc_html( (string) count_user_posts( $current_user->ID, 'apollo_classified' ) ); ?></span>
						<span class="apollo-stat-card__label"><?php esc_html_e( 'Meus Anúncios', 'apollo-social' ); ?></span>
					</div>
				</div>
				<div class="apollo-stat-card apollo-card">
					<div class="apollo-stat-card__icon"><i class="ri-user-heart-line"></i></div>
					<div class="apollo-stat-card__content">
						<span class="apollo-stat-card__value"><?php echo esc_html( (string) absint( get_user_meta( $current_user->ID, '_followers_count', true ) ) ); ?></span>
						<span class="apollo-stat-card__label"><?php esc_html_e( 'Seguidores', 'apollo-social' ); ?></span>
					</div>
				</div>
				<div class="apollo-stat-card apollo-card">
					<div class="apollo-stat-card__icon"><i class="ri-notification-3-line"></i></div>
					<div class="apollo-stat-card__content">
						<span class="apollo-stat-card__value"><?php echo esc_html( (string) absint( get_user_meta( $current_user->ID, '_unread_notifications', true ) ) ); ?></span>
						<span class="apollo-stat-card__label"><?php esc_html_e( 'Notificações', 'apollo-social' ); ?></span>
					</div>
				</div>
			</div>

			<div class="apollo-dashboard__tabs apollo-tabs">
				<div class="apollo-tabs__list" role="tablist">
					<?php if ( 'true' === $atts['show_events'] ) : ?>
						<button class="apollo-tabs__tab apollo-tabs__tab--active" role="tab" data-tab="events">
							<i class="ri-calendar-line"></i>
							<?php esc_html_e( 'Eventos', 'apollo-social' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( 'true' === $atts['show_classifieds'] ) : ?>
						<button class="apollo-tabs__tab" role="tab" data-tab="classifieds">
							<i class="ri-price-tag-3-line"></i>
							<?php esc_html_e( 'Anúncios', 'apollo-social' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( 'true' === $atts['show_activity'] ) : ?>
						<button class="apollo-tabs__tab" role="tab" data-tab="activity">
							<i class="ri-history-line"></i>
							<?php esc_html_e( 'Atividade', 'apollo-social' ); ?>
						</button>
					<?php endif; ?>
				</div>
				<div class="apollo-tabs__content">
					<!-- Tab content loaded via AJAX -->
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_follow_button] - Follow Button
	// =========================================================================

	/**
	 * Render follow button shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_follow_button( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'user_id' => 0,
				'size'    => 'md',
			),
			$atts,
			'apollo_follow_button'
		);

		$target_user_id  = absint( $atts['user_id'] );
		$current_user_id = get_current_user_id();

		if ( ! $target_user_id || $target_user_id === $current_user_id ) {
			return '';
		}

		$is_following = false;

		if ( $current_user_id ) {
			$following    = get_user_meta( $current_user_id, '_following', true ) ?: array();
			$is_following = in_array( $target_user_id, (array) $following, true );
		}

		$button_text  = $is_following ? __( 'Seguindo', 'apollo-social' ) : __( 'Seguir', 'apollo-social' );
		$button_class = $is_following ? 'apollo-btn--outline' : 'apollo-btn--primary';
		$button_icon  = $is_following ? 'ri-user-unfollow-line' : 'ri-user-add-line';

		ob_start();
		?>
		<button
			type="button"
			class="apollo-btn <?php echo esc_attr( $button_class ); ?> apollo-btn--<?php echo esc_attr( $atts['size'] ); ?> apollo-follow-btn <?php echo $is_following ? 'is-following' : ''; ?>"
			data-action="follow"
			data-user-id="<?php echo esc_attr( (string) $target_user_id ); ?>"
			<?php echo ! is_user_logged_in() ? 'data-requires-login="true"' : ''; ?>
		>
			<i class="<?php echo esc_attr( $button_icon ); ?>"></i>
			<span><?php echo esc_html( $button_text ); ?></span>
		</button>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [apollo_user_activity] - User Activity Feed
	// =========================================================================

	/**
	 * Render user activity shortcode
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_user_activity( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'user_id' => 0,
				'limit'   => 10,
			),
			$atts,
			'apollo_user_activity'
		);

		$user_id = absint( $atts['user_id'] ) ?: get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		$atts['user_id']   = $user_id;
		$atts['show_form'] = 'false';

		return $this->render_social_feed( $atts );
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Render empty state
	 *
	 * @param string $message Message to display.
	 * @return string
	 */
	private function render_empty_state( string $message ): string {
		return sprintf(
			'<div class="apollo-empty-state"><i class="ri-emotion-sad-line"></i><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Render login required state
	 *
	 * @param string $message Optional message.
	 * @return string
	 */
	private function render_login_required( string $message = '' ): string {
		$message   = $message ?: __( 'Faça login para acessar este conteúdo.', 'apollo-social' );
		$login_url = wp_login_url( get_permalink() );

		ob_start();
		?>
		<div class="apollo-login-required apollo-card">
			<div class="apollo-login-required__icon">
				<i class="ri-lock-line"></i>
			</div>
			<p><?php echo esc_html( $message ); ?></p>
			<a href="<?php echo esc_url( $login_url ); ?>" class="apollo-btn apollo-btn--primary">
				<i class="ri-login-box-line"></i>
				<?php esc_html_e( 'Fazer Login', 'apollo-social' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue required assets
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		// Enqueue global Apollo assets via CDN loader.
		if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
			apollo_enqueue_global_assets();
		} else {
			// Fallback: CDN loader script auto-loads all needed styles (index.css, icons, etc.)
			wp_enqueue_script( 'apollo-cdn-loader', 'https://assets.apollo.rio.br/index.min.js', array(), '3.1.0', true );
		}

		// RemixIcon is included in CDN styles/index.css - no need to load separately
	}
}

// Initialize on init.
add_action(
	'init',
	function () {
		SocialShortcodes::get_instance();
	},
	20
);
