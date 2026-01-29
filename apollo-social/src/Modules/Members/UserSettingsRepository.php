<?php
declare(strict_types=1);
namespace Apollo\Modules\Members;

final class UserSettingsRepository {
	private const TABLE = 'apollo_user_settings';

	public static function set( int $userId, string $key, mixed $value ): bool {
		global $wpdb;
		return $wpdb->replace(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'       => $userId,
				'setting_key'   => sanitize_key( $key ),
				'setting_value' => maybe_serialize( $value ),
			)
		) !== false;
	}

	public static function get( int $userId, string $key, mixed $default = null ): mixed {
		global $wpdb;
		$val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM {$wpdb->prefix}" . self::TABLE . ' WHERE user_id=%d AND setting_key=%s',
				$userId,
				$key
			)
		);
		return $val !== null ? maybe_unserialize( $val ) : $default;
	}

	public static function getAll( int $userId ): array {
		global $wpdb;
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT setting_key,setting_value FROM {$wpdb->prefix}" . self::TABLE . ' WHERE user_id=%d',
				$userId
			),
			ARRAY_A
		) ?: array();
		$result = array();
		foreach ( $rows as $r ) {
			$result[ $r['setting_key'] ] = maybe_unserialize( $r['setting_value'] );}
		return $result;
	}

	public static function delete( int $userId, string $key ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array(
				'user_id'     => $userId,
				'setting_key' => $key,
			)
		) !== false;
	}

	public static function deleteAll( int $userId ): bool {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'user_id' => $userId ) ) !== false;
	}

	public static function setMultiple( int $userId, array $settings ): bool {
		foreach ( $settings as $key => $value ) {
			if ( ! self::set( $userId, $key, $value ) ) {
				return false;
			}
		}
		return true;
	}

	public static function getDefaults(): array {
		return array(
			'email_on_friend_request' => true,
			'email_on_friend_accept'  => true,
			'email_on_mention'        => true,
			'email_on_message'        => true,
			'email_on_group_invite'   => true,
			'email_on_group_update'   => false,
			'email_on_achievement'    => true,
			'email_on_rank_up'        => true,
			'email_digest'            => 'daily',
			'profile_visibility'      => 'public',
			'activity_visibility'     => 'public',
			'friends_visibility'      => 'public',
			'groups_visibility'       => 'public',
			'show_online_status'      => true,
			'allow_friend_requests'   => true,
			'allow_messages'          => true,
		);
	}

	public static function getWithDefaults( int $userId ): array {
		$defaults     = self::getDefaults();
		$userSettings = self::getAll( $userId );
		return array_merge( $defaults, $userSettings );
	}
}
