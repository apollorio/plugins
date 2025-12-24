<?php
/**
 * Apollo Widget: Profile Card
 *
 * Displays user profile information: avatar, name, location, join date.
 * This widget is always present and cannot be deleted.
 *
 * Pattern: Habbo profile card, shows "Clubber carioca" label
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Widget_Profile_Card
 */
class Apollo_Widget_Profile_Card extends Apollo_Widget_Base {

	/**
	 * Get widget name
	 * Tooltip: Unique identifier in layout JSON
	 */
	public function get_name() {
		return 'profile-card';
	}

	/**
	 * Get widget title
	 * Tooltip: Display name in sidebar
	 */
	public function get_title() {
		return __( 'Profile Card', 'apollo-social' );
	}

	/**
	 * Get icon
	 * Tooltip: Dashicon for profile/user
	 */
	public function get_icon() {
		return 'dashicons-admin-users';
	}

	/**
	 * Get description
	 */
	public function get_description() {
		return __( 'Your profile info: avatar, name, location, and member since.', 'apollo-social' );
	}

	/**
	 * Get tooltip
	 */
	public function get_tooltip() {
		return __( 'Profile Card always shows your info. It cannot be deleted, only moved/resized.', 'apollo-social' );
	}

	/**
	 * Cannot be deleted (Habbo style: always present)
	 */
	public function can_delete() {
		return false;
	}

	/**
	 * Only one profile card per home
	 */
	public function get_max_instances() {
		return 1;
	}

	/**
	 * Default size
	 */
	public function get_default_width() {
		return 280;
	}

	public function get_default_height() {
		return 200;
	}

	/**
	 * Settings
	 * Tooltip: Configurable options for the widget
	 */
	public function get_settings() {
		return array(
			'show_location' => $this->field( 'switch', __( 'Show Location', 'apollo-social' ), true ),
			'show_date'     => $this->field( 'switch', __( 'Show Member Since', 'apollo-social' ), true ),
			'show_pronouns' => $this->field( 'switch', __( 'Show Pronouns', 'apollo-social' ), true ),
			'label'         => $this->field( 'text', __( 'Label', 'apollo-social' ), 'Clubber carioca' ),
		);
	}

	/**
	 * Render widget
	 *
	 * Data source: wp_users, wp_usermeta
	 *
	 * @param array $data Widget data
	 * @return string HTML
	 */
	public function render( $data ) {
		$settings = $data['settings'] ?? array();
		$post_id  = $data['post_id'] ?? 0;

		$user = $this->get_post_author( $post_id );

		if ( ! $user ) {
			return '<div class="apollo-widget-profile-card apollo-widget-error">'
				. __( 'User not found', 'apollo-social' )
				. '</div>';
		}

		// Get user meta
		$pronouns = get_user_meta( $user->ID, 'pronouns', true );
		$location = get_user_meta( $user->ID, 'location', true ) ?: get_user_meta( $user->ID, 'apollo_location', true );
		$label    = $settings['label'] ?? 'Clubber carioca';

		$show_location = ( $settings['show_location'] ?? true );
		$show_date     = ( $settings['show_date'] ?? true );
		$show_pronouns = ( $settings['show_pronouns'] ?? true );

		// Build HTML
		ob_start();
		?>
		<div class="apollo-widget-profile-card">
			<div class="profile-avatar-wrapper">
				<?php echo get_avatar( $user->ID, 80, '', '', array( 'class' => 'profile-avatar' ) ); ?>
			</div>
			
			<div class="profile-info">
				<h3 class="profile-name"><?php echo $this->esc( $user->display_name ); ?></h3>
				
				<?php if ( $show_pronouns && $pronouns ) : ?>
					<span class="profile-pronouns">(<?php echo $this->esc( $pronouns ); ?>)</span>
				<?php endif; ?>
				
				<p class="profile-label"><?php echo $this->esc( $label ); ?></p>
				
				<?php if ( $show_location && $location ) : ?>
					<p class="profile-location">
						<span class="dashicons dashicons-location"></span>
						<?php echo $this->esc( $location ); ?>
					</p>
				<?php endif; ?>
				
				<?php if ( $show_date ) : ?>
					<p class="profile-date">
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php
						printf(
							/* translators: %s: registration date */
							__( 'Member since %s', 'apollo-social' ),
							date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) )
						);
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Editor template (JS preview)
	 * Pattern: WOW getTemplate()
	 */
	public function get_editor_template() {
		return '
        <div class="apollo-widget-profile-card">
            <div class="profile-avatar-wrapper">
                <img src="{{apolloBuilderConfig.userAvatar}}" alt="" class="profile-avatar">
            </div>
            <div class="profile-info">
                <h3 class="profile-name">{{apolloBuilderConfig.userName}}</h3>
                <p class="profile-label">{{data.label || "Clubber carioca"}}</p>
            </div>
        </div>
        ';
	}
}
