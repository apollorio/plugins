<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Role Badges System
 *
 * Badge colors and styling for different user roles
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get badge HTML for user role
 *
 * @param int|WP_User $user User ID or WP_User object
 * @param string      $role Optional role override
 * @return string Badge HTML
 */
function apollo_get_role_badge( $user = null, $role = null ) {
	if ( ! $user && ! $role ) {
		$user = wp_get_current_user();
	}

	if ( $user && ! $role ) {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if ( $user instanceof WP_User ) {
			// Check for Apollo-specific roles first
			if ( in_array( 'apollo_admin', $user->roles ) || in_array( 'apollo_mod', $user->roles ) ) {
				$role = 'apollo_admin';
			} elseif ( in_array( 'cena_rio', $user->roles ) ) {
				$role = 'cena_rio';
			} elseif ( in_array( 'visual_artist', $user->roles ) ) {
				$role = 'visual_artist';
			} elseif ( in_array( 'political_business', $user->roles ) ) {
				$role = 'political_business';
			} elseif ( in_array( 'administrator', $user->roles ) ) {
				$role = 'apollo_admin'; 
				// Admins get Apollo Admin badge
			} else {
				$role = 'default';
			}
		}
	}//end if

	$badge_config = apollo_get_role_badge_config( $role ?: 'default' );

	return sprintf(
		'<span class="apollo-role-badge apollo-role-badge-%s" title="%s">%s</span>',
		esc_attr( $badge_config['class'] ),
		esc_attr( $badge_config['title'] ),
		esc_html( $badge_config['label'] )
	);
}

/**
 * Get badge configuration for role
 *
 * @param string $role Role identifier
 * @return array Badge configuration
 */
function apollo_get_role_badge_config( $role ) {
	$badges = array(
		'apollo_admin'       => array(
			'class'                          => 'apollo-admin',
			'label'                          => __( 'Apollo ADM / Mod', 'apollo-events-manager' ),
			'title'                          => __( 'Apollo Administrator or Moderator', 'apollo-events-manager' ),
			'bg_color'                       => '#ff6b35', 
			// Apollo orange
								'text_color' => '#ffffff',
		),
		'cena_rio'           => array(
			'class'                          => 'cena-rio',
			'label'                          => __( 'CENA::RIO', 'apollo-events-manager' ),
			'title'                          => __( 'CENA::RIO Member', 'apollo-events-manager' ),
			'bg_color'                       => '#a855f7', 
			// Purple 500
								'text_color' => '#ffffff',
		),
		'visual_artist'      => array(
			'class'                          => 'visual-artist',
			'label'                          => __( 'Visual Artist', 'apollo-events-manager' ),
			'title'                          => __( 'Visual Artist', 'apollo-events-manager' ),
			'bg_color'                       => '#22c55e', 
			// Green 500
								'text_color' => '#ffffff',
		),
		'political_business' => array(
			'class'                          => 'political-business',
			'label'                          => __( 'Political / Business', 'apollo-events-manager' ),
			'title'                          => __( 'Political or Business', 'apollo-events-manager' ),
			'bg_color'                       => '#9ca3af', 
			// Gray 400
								'text_color' => '#ffffff',
		),
		'default'            => array(
			'class'                          => 'default',
			'label'                          => __( 'User', 'apollo-events-manager' ),
			'title'                          => __( 'Standard User', 'apollo-events-manager' ),
			'bg_color'                       => '#6b7280', 
			// Gray 500
								'text_color' => '#ffffff',
		),
	);

	return isset( $badges[ $role ] ) ? $badges[ $role ] : $badges['default'];
}

/**
 * Enqueue badge styles
 */
function apollo_enqueue_role_badge_styles() {
	?>
	<style>
	.apollo-role-badge {
		display: inline-flex;
		align-items: center;
		padding: 4px 12px;
		border-radius: 12px;
		font-size: 0.75rem;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		white-space: nowrap;
	}
	
	.apollo-role-badge-apollo-admin {
		background-color: #ff6b35;
		color: #ffffff;
	}
	
	.apollo-role-badge-cena-rio {
		background-color: #a855f7;
		color: #ffffff;
	}
	
	.apollo-role-badge-visual-artist {
		background-color: #22c55e;
		color: #ffffff;
	}
	
	.apollo-role-badge-political-business {
		background-color: #9ca3af;
		color: #ffffff;
	}
	
	.apollo-role-badge-default {
		background-color: #6b7280;
		color: #ffffff;
	}
	</style>
	<?php
}
add_action( 'wp_head', 'apollo_enqueue_role_badge_styles' );
add_action( 'admin_head', 'apollo_enqueue_role_badge_styles' );

