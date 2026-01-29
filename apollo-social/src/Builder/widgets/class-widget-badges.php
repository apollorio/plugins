<?php
/**
 * Apollo Widget: Badges
 *
 * Displays user membership badges (PNG images).
 * Click on badge shows tooltip with name/description.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

class Apollo_Widget_Badges extends Apollo_Widget_Base {

	public function get_name() {
		return 'badges';
	}

	public function get_title() {
		return __( 'Membership Badges', 'apollo-social' );
	}

	public function get_icon() {
		return 'dashicons-awards';
	}

	public function get_description() {
		return __( 'Display your membership badges and achievements.', 'apollo-social' );
	}

	public function get_tooltip() {
		return __( 'Shows PNG badges from your memberships. Visitors can click to see details.', 'apollo-social' );
	}

	public function get_default_width() {
		return 250;
	}

	public function get_default_height() {
		return 80;
	}

	/**
	 * Settings
	 */
	public function get_settings() {
		return array(
			'layout'      => $this->field(
				'select',
				__( 'Layout', 'apollo-social' ),
				'row',
				array(
					'options' => array(
						'row'   => __( 'Row', 'apollo-social' ),
						'grid'  => __( 'Grid', 'apollo-social' ),
						'stack' => __( 'Stack', 'apollo-social' ),
					),
				)
			),
			'badge_size'  => $this->field(
				'slider',
				__( 'Badge Size', 'apollo-social' ),
				40,
				array(
					'min'  => 24,
					'max'  => 80,
					'unit' => 'px',
				)
			),
			'show_titles' => $this->field( 'switch', __( 'Show Titles on Hover', 'apollo-social' ), true ),
		);
	}

	/**
	 * Render widget
	 *
	 * Data source: apollo_social_user_badges filter
	 * Tooltip: Plugins/themes can add badges via filter
	 */
	public function render( $data ) {
		$settings = $data['settings'] ?? array();
		$post_id  = $data['post_id'] ?? 0;

		$user = $this->get_post_author( $post_id );
		if ( ! $user ) {
			return '<div class="apollo-widget-badges apollo-widget-error">'
				. __( 'User not found', 'apollo-social' )
				. '</div>';
		}

		/**
		 * Filter: apollo_social_user_badges
		 *
		 * Tooltip: Return array of badges for user
		 * Expected format: [['id' => 'badge-1', 'image_url' => 'url', 'label' => 'Name', 'description' => 'Desc']]
		 *
		 * @param array $badges Empty array
		 * @param int $user_id User ID
		 */
		$badges = apply_filters( 'apollo_social_user_badges', array(), $user->ID );

		// Also check user meta for Cultura::Rio identities
		$identities = get_user_meta( $user->ID, 'apollo_cultura_identities', true );
		if ( is_array( $identities ) && ! empty( $identities ) ) {
			foreach ( $identities as $identity ) {
				if ( $identity === 'clubber' ) {
					continue;
					// Skip default
				}

				$badges[] = array(
					'id'                           => 'cultura-' . $identity,
					'label'                        => ucfirst( str_replace( '-', ' ', $identity ) ),
					'description'                  => sprintf( __( 'Cultura::Rio %s', 'apollo-social' ), $identity ),
					'image_url'                    => '',
					// Will use default badge
											'type' => 'cultura',
				);
			}
		}

		$layout      = $settings['layout'] ?? 'row';
		$badge_size  = absint( $settings['badge_size'] ?? 40 );
		$show_titles = ! empty( $settings['show_titles'] );

		ob_start();
		?>
		<div class="apollo-widget-badges badges-layout-<?php echo $this->esc( $layout, 'attr' ); ?>">
			<?php if ( empty( $badges ) ) : ?>
				<p class="badges-empty"><?php _e( 'No badges yet', 'apollo-social' ); ?></p>
			<?php else : ?>
				<div class="badges-list">
					<?php
					foreach ( $badges as $badge ) :
						$badge_id = sanitize_key( $badge['id'] ?? uniqid( 'badge-' ) );
						$label    = $badge['label'] ?? '';
						$desc     = $badge['description'] ?? '';
						$img_url  = $badge['image_url'] ?? '';
						$type     = $badge['type'] ?? 'membership';

						// Default badge icon if no image
						if ( empty( $img_url ) ) {
							$img_url = 'data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ff6f00"><path d="M12 2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2z"/></svg>' );
						}
						?>
						<div class="badge-item badge-type-<?php echo $this->esc( $type, 'attr' ); ?>" 
							data-badge-id="<?php echo $this->esc( $badge_id, 'attr' ); ?>"
							<?php if ( $show_titles && ( $label || $desc ) ) : ?>
							title="<?php echo $this->esc( $label . ( $desc ? ': ' . $desc : '' ), 'attr' ); ?>"
							<?php endif; ?>>
							<img src="<?php echo $this->esc( $img_url, 'url' ); ?>" 
								alt="<?php echo $this->esc( $label, 'attr' ); ?>"
								width="<?php echo $badge_size; ?>"
								height="<?php echo $badge_size; ?>"
								loading="lazy"
								class="badge-image">
							<?php if ( ! $show_titles && $label ) : ?>
								<span class="badge-label"><?php echo $this->esc( $label ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
