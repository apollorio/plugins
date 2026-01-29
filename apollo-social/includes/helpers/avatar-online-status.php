<?php
/**
 * Online Status Helper
 *
 * Automatically adds online status indicators to user avatars.
 * Shows green dot for online users (all users except administrators).
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Get avatar with online status indicator
 *
 * @param int    $user_id      User ID
 * @param int    $size         Avatar size (default: 40)
 * @param string $default      Default avatar URL
 * @param string $alt          Alt text
 * @param array  $extra_attr   Additional HTML attributes
 * @return string HTML avatar with online indicator
 */
function apollo_get_avatar_with_online_status( int $user_id, int $size = 40, string $default = '', string $alt = '', array $extra_attr = array() ): string {
	if ( ! $user_id || $user_id <= 0 ) {
		return '';
	}

	// Check if user is administrator
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}

	$is_admin = in_array( 'administrator', $user->roles, true );

	// Get avatar
	$avatar = get_avatar( $user_id, $size, $default, $alt, $extra_attr );

	// If administrator, return avatar without online indicator
	if ( $is_admin ) {
		return $avatar;
	}

	// Check if user is online (optional - can always show as online)
	$is_online = true; // Force all non-admin users to show as online
	// Uncomment below to use actual online status:
	// $is_online = class_exists( '\Apollo\Modules\Members\OnlineUsersRepository' )
	//     ? \Apollo\Modules\Members\OnlineUsersRepository::isOnline( $user_id )
	//     : false;

	// Wrap avatar with online status container
	$status_class = $is_online ? 'online' : 'offline';

	return sprintf(
		'<div class="ap-avatar-status %s">%s</div>',
		esc_attr( $status_class ),
		$avatar
	);
}

/**
 * Add online status CSS to head
 */
add_action( 'wp_head', 'apollo_online_status_css', 999 );
add_action( 'admin_head', 'apollo_online_status_css', 999 );
function apollo_online_status_css() {
	?>
	<style>
		/* Online Status Indicator */
		.ap-avatar-status {
			position: relative;
			display: inline-block;
		}

		.ap-avatar-status::after {
			content: "";
			position: absolute;
			bottom: 0;
			right: 0;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			border: 2px solid var(--ap-bg-main, #fff);
			background: var(--ap-color-success, #22c55e);
			z-index: 1;
		}

		/* Offline state (hidden by default for non-admins) */
		.ap-avatar-status.offline::after {
			background: var(--ap-text-disabled, #9ca3af);
			display: none; /* Hidden since all non-admins should show as online */
		}

		/* Different sizes */
		.ap-avatar-status.size-small::after {
			width: 8px;
			height: 8px;
			border-width: 1.5px;
		}

		.ap-avatar-status.size-large::after {
			width: 12px;
			height: 12px;
			border-width: 2.5px;
		}

		.ap-avatar-status.size-xlarge::after {
			width: 14px;
			height: 14px;
			border-width: 3px;
		}

		/* Pulsing animation for online status */
		.ap-avatar-status.online::after {
			animation: pulse-online 2s ease-in-out infinite;
		}

		@keyframes pulse-online {
			0%, 100% {
				opacity: 1;
			}
			50% {
				opacity: 0.7;
				transform: scale(1.1);
			}
		}
	</style>
	<?php
}

/**
 * Filter all avatars to add online status automatically
 * This ensures ALL avatars site-wide get the online indicator (except admins)
 */
add_filter( 'get_avatar', 'apollo_filter_avatar_add_online_status', 10, 6 );
function apollo_filter_avatar_add_online_status( $avatar, $id_or_email, $size, $default, $alt, $args ) {
	// Get user ID from various formats
	$user_id = 0;

	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		$user_id = $user ? $user->ID : 0;
	} elseif ( $id_or_email instanceof \WP_User ) {
		$user_id = $id_or_email->ID;
	} elseif ( $id_or_email instanceof \WP_Post ) {
		$user_id = (int) $id_or_email->post_author;
	} elseif ( $id_or_email instanceof \WP_Comment ) {
		$user_id = (int) $id_or_email->user_id;
	}

	if ( ! $user_id || $user_id <= 0 ) {
		return $avatar;
	}

	// Check if user is administrator
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return $avatar;
	}

	$is_admin = in_array( 'administrator', $user->roles, true );

	// Administrators don't get online indicator
	if ( $is_admin ) {
		return $avatar;
	}

	// All non-admin users show as online
	$is_online = true;

	// Optional: Use actual online status from database
	// Uncomment to enable real-time online detection:
	/*
	if ( class_exists( '\Apollo\Modules\Members\OnlineUsersRepository' ) ) {
		$is_online = \Apollo\Modules\Members\OnlineUsersRepository::isOnline( $user_id );
	}
	*/

	// Determine size class
	$size_class = 'size-medium';
	if ( $size <= 32 ) {
		$size_class = 'size-small';
	} elseif ( $size >= 56 ) {
		$size_class = 'size-large';
	} elseif ( $size >= 80 ) {
		$size_class = 'size-xlarge';
	}

	$status_class = $is_online ? 'online' : 'offline';

	// Wrap avatar with status indicator
	return sprintf(
		'<div class="ap-avatar-status %s %s">%s</div>',
		esc_attr( $status_class ),
		esc_attr( $size_class ),
		$avatar
	);
}
