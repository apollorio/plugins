<?php
/**
 * Groups Business Rules - Type-specific logic (Comuna vs Nucleo)
 *
 * @package Apollo\Modules\Groups
 * @since   2.2.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Groups;

use WP_Error;

/**
 * GroupsBusinessRules - Enforces type-specific behavior
 *
 * - Comuna: public, direct join, open posting
 * - Nucleo: private, approval-based join, restricted posting
 */
final class GroupsBusinessRules {

	const TYPE_COMUNA = 'comuna';
	const TYPE_NUCLEO = 'nucleo';
	const TYPE_SEASON = 'season';

	const VALID_TYPES = array( self::TYPE_COMUNA, self::TYPE_NUCLEO, self::TYPE_SEASON );

	/**
	 * Validate group type
	 *
	 * @param string $type Group type
	 * @return true|WP_Error
	 */
	public static function validateType( string $type ) {
		if ( ! in_array( $type, self::VALID_TYPES, true ) ) {
			return new WP_Error(
				'invalid_group_type',
				sprintf( 'Invalid group type: %s. Must be one of: %s', $type, implode( ', ', self::VALID_TYPES ) )
			);
		}
		return true;
	}

	/**
	 * Get default visibility for type
	 *
	 * @param string $type Group type
	 * @return string 'public' | 'private'
	 */
	public static function defaultVisibility( string $type ): string {
		return self::TYPE_NUCLEO === $type ? 'private' : 'public';
	}

	/**
	 * Get default join policy for type
	 *
	 * @param string $type Group type
	 * @return string 'direct' | 'approval'
	 */
	public static function defaultJoinPolicy( string $type ): string {
		return self::TYPE_NUCLEO === $type ? 'approval' : 'direct';
	}

	/**
	 * Check if join requires approval
	 *
	 * @param array $group Group data
	 * @return bool
	 */
	public static function joinRequiresApproval( array $group ): bool {
		$type = $group['group_type'] ?? self::TYPE_COMUNA;
		return self::TYPE_NUCLEO === $type || 'approval' === ( $group['join_policy'] ?? 'direct' );
	}

	/**
	 * Check if posting requires specific capability
	 *
	 * @param array  $group Group data
	 * @param int    $user_id User ID
	 * @param int    $group_id Group ID
	 * @return true|WP_Error
	 */
	public static function canPost( array $group, int $user_id, int $group_id ) {
		// For nucleo, only members with post_cap can post
		if ( self::TYPE_NUCLEO === $group['group_type'] ) {
			global $wpdb;
			$member = $wpdb->get_row( $wpdb->prepare(
				"SELECT role FROM {$wpdb->prefix}apollo_group_members
				WHERE group_id = %d AND user_id = %d AND is_banned = 0",
				$group_id,
				$user_id
			) );

			if ( ! $member ) {
				return new WP_Error( 'not_group_member', 'You must be a group member to post.' );
			}

			// Disallow 'member' role if posting restricted to mod+
			if ( 'member' === $member->role && ( $group['post_cap'] ?? 'member' ) === 'moderator' ) {
				return new WP_Error( 'insufficient_role', 'Only moderators/admins can post in this group.' );
			}
		}

		return true;
	}

	/**
	 * Check if creation is allowed for user/type
	 *
	 * @param string $type Group type
	 * @param int    $user_id User ID
	 * @return true|WP_Error
	 */
	public static function canCreate( string $type, int $user_id ) {
		// Validate type
		$validation = self::validateType( $type );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Nucleo creation requires specific capability
		if ( self::TYPE_NUCLEO === $type ) {
			if ( ! user_can( $user_id, 'apollo_create_nucleo' ) ) {
				return new WP_Error(
					'insufficient_cap',
					'You do not have permission to create a nucleo.'
				);
			}
		}

		// Comuna can be created by subscribers+ (configurable)
		if ( self::TYPE_COMUNA === $type ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user || empty( $user->roles ) ) {
				return new WP_Error( 'no_role', 'User has no role assigned.' );
			}
		}

		return true;
	}

	/**
	 * Check if invite is allowed for user/group
	 *
	 * @param int $group_id Group ID
	 * @param int $user_id Inviting user ID
	 * @return true|WP_Error
	 */
	public static function canInvite( int $group_id, int $user_id ) {
		global $wpdb;

		$member = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_group_members
			WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		) );

		if ( ! $member ) {
			return new WP_Error( 'not_group_member', 'You are not a group member.' );
		}

		// Only owners/admins/mods can invite
		if ( ! in_array( $member->role, array( 'owner', 'admin', 'mod' ), true ) ) {
			return new WP_Error( 'insufficient_role', 'Only admins/mods can invite members.' );
		}

		if ( $member->is_banned ) {
			return new WP_Error( 'user_banned', 'You are banned from this group.' );
		}

		return true;
	}

	/**
	 * Sanitize group data for creation/update
	 *
	 * @param array $data Raw input
	 * @return array Sanitized data
	 */
	public static function sanitizeGroupData( array $data ): array {
		$type = sanitize_key( $data['group_type'] ?? self::TYPE_COMUNA );

		return array(
			'name'        => sanitize_text_field( $data['name'] ?? '' ),
			'description' => wp_kses_post( $data['description'] ?? '' ),
			'group_type'  => in_array( $type, self::VALID_TYPES, true ) ? $type : self::TYPE_COMUNA,
			'visibility'  => in_array( $data['visibility'] ?? '', array( 'public', 'private', 'hidden' ), true ) ? $data['visibility'] : self::defaultVisibility( $type ),
			'join_policy' => in_array( $data['join_policy'] ?? '', array( 'direct', 'approval' ), true ) ? $data['join_policy'] : self::defaultJoinPolicy( $type ),
			'post_cap'    => in_array( $data['post_cap'] ?? '', array( 'member', 'moderator' ), true ) ? $data['post_cap'] : 'member',
		);
	}
}
