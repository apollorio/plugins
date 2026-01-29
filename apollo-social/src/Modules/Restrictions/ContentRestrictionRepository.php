<?php
declare(strict_types=1);
namespace Apollo\Modules\Restrictions;

final class ContentRestrictionRepository {
	private const TABLE = 'apollo_content_restrictions';

	public static function setRestriction( string $objectType, int $objectId, array $rules ): bool {
		global $wpdb;
		return $wpdb->replace(
			$wpdb->prefix . self::TABLE,
			array(
				'object_type'  => sanitize_key( $objectType ),
				'object_id'    => $objectId,
				'roles'        => isset( $rules['roles'] ) ? wp_json_encode( $rules['roles'] ) : null,
				'memberships'  => isset( $rules['memberships'] ) ? wp_json_encode( $rules['memberships'] ) : null,
				'groups'       => isset( $rules['groups'] ) ? wp_json_encode( $rules['groups'] ) : null,
				'users'        => isset( $rules['users'] ) ? wp_json_encode( $rules['users'] ) : null,
				'conditions'   => isset( $rules['conditions'] ) ? wp_json_encode( $rules['conditions'] ) : null,
				'redirect_url' => isset( $rules['redirect_url'] ) ? esc_url_raw( $rules['redirect_url'] ) : null,
				'message'      => isset( $rules['message'] ) ? sanitize_textarea_field( $rules['message'] ) : null,
			)
		) !== false;
	}

	public static function getRestriction( string $objectType, int $objectId ): ?array {
		global $wpdb;
		$r = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE object_type=%s AND object_id=%d',
				$objectType,
				$objectId
			),
			ARRAY_A
		);
		if ( $r ) {
			$r['roles']       = json_decode( $r['roles'] ?? '', true );
			$r['memberships'] = json_decode( $r['memberships'] ?? '', true );
			$r['groups']      = json_decode( $r['groups'] ?? '', true );
			$r['users']       = json_decode( $r['users'] ?? '', true );
			$r['conditions']  = json_decode( $r['conditions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function canAccess( string $objectType, int $objectId, int $userId ): bool {
		$restriction = self::getRestriction( $objectType, $objectId );
		if ( ! $restriction ) {
			return true;
		}
		if ( $userId <= 0 ) {
			return false;
		}
		$user = get_userdata( $userId );
		if ( ! $user ) {
			return false;
		}
		if ( in_array( 'administrator', $user->roles ) ) {
			return true;
		}
		if ( ! empty( $restriction['users'] ) && in_array( $userId, $restriction['users'] ) ) {
			return true;
		}
		if ( ! empty( $restriction['roles'] ) ) {
			if ( empty( array_intersect( $user->roles, $restriction['roles'] ) ) ) {
				return false;
			}
		}
		if ( ! empty( $restriction['groups'] ) ) {
			$userGroups   = \Apollo\Modules\Groups\GroupsRepository::getUserGroups( $userId );
			$userGroupIds = array_column( $userGroups, 'id' );
			if ( empty( array_intersect( $userGroupIds, $restriction['groups'] ) ) ) {
				return false;
			}
		}
		if ( ! empty( $restriction['memberships'] ) ) {
			$hasMembership = false;
			foreach ( $restriction['memberships'] as $membershipId ) {
				if ( \function_exists( 'swpm_is_member_of' ) && \swpm_is_member_of( $userId, $membershipId ) ) {
					$hasMembership = true;
					break;
				}
			}
			if ( ! $hasMembership ) {
				return false;
			}
		}
		return true;
	}

	public static function getRedirectUrl( string $objectType, int $objectId ): ?string {
		$restriction = self::getRestriction( $objectType, $objectId );
		return $restriction['redirect_url'] ?? null;
	}

	public static function getMessage( string $objectType, int $objectId ): ?string {
		$restriction = self::getRestriction( $objectType, $objectId );
		return $restriction['message'] ?? null;
	}

	public static function removeRestriction( string $objectType, int $objectId ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array(
				'object_type' => $objectType,
				'object_id'   => $objectId,
			)
		) !== false;
	}

	public static function getRestrictedObjects( string $objectType ): array {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM {$wpdb->prefix}" . self::TABLE . ' WHERE object_type=%s', $objectType ) ) ?: array();
	}
}
