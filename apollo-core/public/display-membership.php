<?php
declare(strict_types=1);

/**
 * Apollo Core - Membership Display (Frontend)
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display membership badge for a user
 *
 * @param int   $user_id User ID.
 * @param array $args    Display arguments.
 * @return string HTML markup for membership badge.
 */
function apollo_display_membership_badge( $user_id, $args = array() ) {
	$defaults = array(
		'show_instagram' => true,
		'badge_class'    => '',
		'show_label'     => true,
	);
	$args     = wp_parse_args( $args, $defaults );

	// Get user membership.
	$membership_slug = apollo_get_user_membership( $user_id );
	$membership_data = apollo_get_membership_data( $membership_slug );

	if ( ! $membership_data ) {
		return '';
	}

	// Get Instagram ID if available.
	$instagram_id = '';
	if ( $args['show_instagram'] ) {
		$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );
	}

	// Build badge HTML.
	$badge_classes = array(
		'apollo-membership',
		'apollo-membership--' . esc_attr( $membership_slug ),
	);

	if ( ! empty( $args['badge_class'] ) ) {
		$badge_classes[] = esc_attr( $args['badge_class'] );
	}

	$bg_color   = esc_attr( $membership_data['color'] );
	$text_color = esc_attr( $membership_data['text_color'] );

	$styles = sprintf(
		'background-color: %s; color: %s; padding: 4px 12px; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500;',
		$bg_color,
		$text_color
	);

	$html = sprintf(
		'<span class="%s" style="%s">',
		esc_attr( implode( ' ', $badge_classes ) ),
		$styles
	);

	if ( $args['show_label'] ) {
		$html .= '<span class="apollo-membership__label">' . esc_html( $membership_data['frontend_label'] ) . '</span>';
	}

	if ( ! empty( $instagram_id ) ) {
		$instagram_url = 'https://instagram.com/' . esc_attr( $instagram_id );
		$html         .= sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" style="color: %s; text-decoration: none;" class="apollo-membership__instagram">@%s</a>',
			esc_url( $instagram_url ),
			$text_color,
			esc_html( $instagram_id )
		);
	}

	$html .= '</span>';

	return $html;
}

/**
 * Echo membership badge
 *
 * @param int   $user_id User ID.
 * @param array $args    Display arguments.
 */
function apollo_the_membership_badge( $user_id, $args = array() ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apollo_display_membership_badge( $user_id, $args );
}

/**
 * Hook membership badge into author box
 *
 * @param string $author_box Original author box HTML.
 * @param int    $user_id    User ID.
 * @return string Modified author box HTML.
 */
function apollo_add_membership_to_author_box( $author_box, $user_id ) {
	$badge = apollo_display_membership_badge( $user_id );

	if ( empty( $badge ) ) {
		return $author_box;
	}

	// Inject badge after author name.
	$author_box = preg_replace(
		'/(<h[2-4][^>]*>.*?<\/h[2-4]>)/i',
		'$1 ' . $badge,
		$author_box,
		1
	);

	return $author_box;
}
add_filter( 'apollo_author_box_html', 'apollo_add_membership_to_author_box', 10, 2 );

/**
 * Display membership badge on user profile page
 *
 * @param WP_User $user User object.
 */
function apollo_display_membership_on_profile( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$membership_slug = apollo_get_user_membership( $user->ID );
	$memberships     = apollo_get_memberships();
	?>
	<h3><?php esc_html_e( 'Apollo Membership', 'apollo-core' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="apollo_membership"><?php esc_html_e( 'Membership', 'apollo-core' ); ?></label></th>
			<td>
				<select name="apollo_membership" id="apollo_membership" <?php disabled( ! current_user_can( 'edit_apollo_users' ) ); ?>>
					<?php foreach ( $memberships as $slug => $data ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $membership_slug, $slug ); ?>>
							<?php echo esc_html( $data['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Current membership level for this user.', 'apollo-core' ); ?>
				</p>
				<div style="margin-top: 10px;">
					<?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo apollo_display_membership_badge( $user->ID );
					?>
				</div>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'apollo_display_membership_on_profile' );
add_action( 'edit_user_profile', 'apollo_display_membership_on_profile' );

/**
 * Save membership on user profile update
 *
 * @param int $user_id User ID.
 */
function apollo_save_membership_on_profile_update( $user_id ) {
	if ( ! current_user_can( 'edit_apollo_users' ) ) {
		return;
	}

	if ( ! isset( $_POST['apollo_membership'] ) ) {
		return;
	}

	$membership_slug = sanitize_key( $_POST['apollo_membership'] );

	apollo_set_user_membership( $user_id, $membership_slug, get_current_user_id() );
}
add_action( 'personal_options_update', 'apollo_save_membership_on_profile_update' );
add_action( 'edit_user_profile_update', 'apollo_save_membership_on_profile_update' );

/**
 * Add membership column to users list table
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function apollo_add_membership_column( $columns ) {
	$columns['apollo_membership'] = __( 'Membership', 'apollo-core' );

	return $columns;
}
add_filter( 'manage_users_columns', 'apollo_add_membership_column' );

/**
 * Display membership in users list table
 *
 * @param string $output      Custom column output.
 * @param string $column_name Column name.
 * @param int    $user_id     User ID.
 * @return string Column content.
 */
function apollo_display_membership_column( $output, $column_name, $user_id ) {
	if ( 'apollo_membership' === $column_name ) {
		$output = apollo_display_membership_badge( $user_id, array( 'show_instagram' => false ) );
	}

	return $output;
}
add_filter( 'manage_users_custom_column', 'apollo_display_membership_column', 10, 3 );

/**
 * Make membership column sortable
 *
 * @param array $columns Sortable columns.
 * @return array Modified columns.
 */
function apollo_make_membership_column_sortable( $columns ) {
	$columns['apollo_membership'] = 'apollo_membership';

	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'apollo_make_membership_column_sortable' );

/**
 * Sort users by membership
 *
 * @param WP_User_Query $query User query object.
 */
function apollo_sort_users_by_membership( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'apollo_membership' === $orderby ) {
		$query->set( 'meta_key', '_apollo_membership' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_users', 'apollo_sort_users_by_membership' );

/**
 * Add membership badge to comments
 *
 * @param string     $comment_text Comment text.
 * @param WP_Comment $comment    Comment object.
 * @return string Modified comment text.
 */
function apollo_add_membership_to_comment( $comment_text, $comment = null ) {
	if ( ! $comment || ! $comment->user_id ) {
		return $comment_text;
	}

	$badge = apollo_display_membership_badge( $comment->user_id, array( 'show_instagram' => false ) );

	if ( empty( $badge ) ) {
		return $comment_text;
	}

	return '<div style="margin-bottom: 8px;">' . $badge . '</div>' . $comment_text;
}
add_filter( 'comment_text', 'apollo_add_membership_to_comment', 10, 2 );

/**
 * Get membership badge CSS
 *
 * @return string CSS styles.
 */
function apollo_get_membership_badge_css() {
	ob_start();
	?>
	<style>
		.apollo-membership {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 4px 12px;
			border-radius: 4px;
			font-size: 14px;
			font-weight: 500;
			white-space: nowrap;
		}

		.apollo-membership__label {
			line-height: 1;
		}

		.apollo-membership__instagram {
			line-height: 1;
			opacity: 0.9;
			transition: opacity 0.2s ease;
		}

		.apollo-membership__instagram:hover {
			opacity: 1;
		}

		/* Admin table styles */
		.widefat .apollo-membership {
			font-size: 12px;
			padding: 2px 8px;
		}
	</style>
	<?php
	return ob_get_clean();
}

/**
 * Enqueue membership badge styles
 */
function apollo_enqueue_membership_styles() {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apollo_get_membership_badge_css();
}
add_action( 'wp_head', 'apollo_enqueue_membership_styles', 100 );
add_action( 'admin_head', 'apollo_enqueue_membership_styles', 100 );
