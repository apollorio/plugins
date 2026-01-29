<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

final class AchievementsRepository {
	private const TABLE      = 'apollo_achievements';
	private const USER_TABLE = 'apollo_user_achievements';

	public static function create( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'name'               => sanitize_text_field( $d['name'] ),
				'slug'               => sanitize_title( $d['slug'] ?? $d['name'] ),
				'description'        => sanitize_textarea_field( $d['description'] ?? '' ),
				'type'               => in_array( $d['type'] ?? '', array( 'badge', 'milestone', 'special', 'competition' ) ) ? $d['type'] : 'badge',
				'trigger_name'       => sanitize_key( $d['trigger_name'] ),
				'trigger_count'      => (int) ( $d['trigger_count'] ?? 1 ),
				'trigger_conditions' => isset( $d['trigger_conditions'] ) ? wp_json_encode( $d['trigger_conditions'] ) : null,
				'points_reward'      => (int) ( $d['points_reward'] ?? 0 ),
				'icon'               => sanitize_text_field( $d['icon'] ?? '' ),
				'badge_image_id'     => isset( $d['badge_image_id'] ) ? (int) $d['badge_image_id'] : null,
				'is_secret'          => (int) ( $d['is_secret'] ?? 0 ),
				'is_active'          => 1,
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['trigger_conditions'] = json_decode( $r['trigger_conditions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getBySlug( string $slug ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE slug=%s', $slug ), ARRAY_A );
		if ( $r ) {
			$r['trigger_conditions'] = json_decode( $r['trigger_conditions'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getAll( bool $activeOnly = true, bool $includeSecret = false ): array {
		global $wpdb;
		$w = array();
		if ( $activeOnly ) {
			$w[] = 'is_active=1';
		}if ( ! $includeSecret ) {
			$w[] = 'is_secret=0';
		}
		$where = $w ? 'WHERE ' . \implode( ' AND ', $w ) : '';
		$rows  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE . " {$where} ORDER BY type,name", ARRAY_A ) ?: array();
		foreach ( $rows as &$r ) {
			$r['trigger_conditions'] = json_decode( $r['trigger_conditions'] ?? '', true );
		}
		return $rows;
	}

	public static function getByTrigger( string $trigger ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::TABLE . ' WHERE trigger_name=%s AND is_active=1',
				$trigger
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['trigger_conditions'] = json_decode( $r['trigger_conditions'] ?? '', true );
		}
		return $rows;
	}

	public static function getUserAchievements( int $userId, bool $completedOnly = false ): array {
		global $wpdb;
		$w = $completedOnly ? 'AND ua.is_completed=1' : '';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*,ua.progress,ua.is_completed,ua.completed_at FROM {$wpdb->prefix}" . self::TABLE . " a
			 INNER JOIN {$wpdb->prefix}" . self::USER_TABLE . " ua ON a.id=ua.achievement_id
			 WHERE ua.user_id=%d {$w} ORDER BY ua.completed_at DESC,a.name",
				$userId
			),
			ARRAY_A
		) ?: array();
	}

	public static function getUserProgress( int $userId, int $achievementId ): ?array {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::USER_TABLE . ' WHERE user_id=%d AND achievement_id=%d',
				$userId,
				$achievementId
			),
			ARRAY_A
		) ?: null;
	}

	public static function incrementProgress( int $userId, int $achievementId, int $amount = 1 ): array {
		global $wpdb;
		$ach = self::get( $achievementId );
		if ( ! $ach ) {
			return array(
				'success' => false,
				'error'   => 'not_found',
			);
		}
		$current     = self::getUserProgress( $userId, $achievementId );
		$newProgress = ( $current['progress'] ?? 0 ) + $amount;
		$isComplete  = $newProgress >= (int) $ach['trigger_count'];
		$wasComplete = $current['is_completed'] ?? false;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}" . self::USER_TABLE . ' (user_id,achievement_id,progress,is_completed,completed_at)
			 VALUES (%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE progress=%d,is_completed=%d,completed_at=IF(is_completed=0 AND %d=1,%s,completed_at)',
				$userId,
				$achievementId,
				$newProgress,
				$isComplete ? 1 : 0,
				$isComplete ? current_time( 'mysql' ) : null,
				$newProgress,
				$isComplete ? 1 : 0,
				$isComplete ? 1 : 0,
				current_time( 'mysql' )
			)
		);
		if ( $isComplete && ! $wasComplete && $ach['points_reward'] > 0 ) {
			PointsRepository::add( $userId, (int) $ach['points_reward'], 'achievement_unlocked', 'default', $achievementId, 'achievement', $ach['name'] );
		}
		return array(
			'success'        => true,
			'progress'       => $newProgress,
			'completed'      => $isComplete,
			'just_completed' => $isComplete && ! $wasComplete,
			'achievement'    => $ach,
		);
	}

	public static function awardAchievement( int $userId, int $achievementId ): array {
		$ach = self::get( $achievementId );
		if ( ! $ach ) {
			return array(
				'success' => false,
				'error'   => 'not_found',
			);
		}
		return self::incrementProgress( $userId, $achievementId, (int) $ach['trigger_count'] );
	}

	public static function revokeAchievement( int $userId, int $achievementId ): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . self::USER_TABLE,
			array(
				'user_id'        => $userId,
				'achievement_id' => $achievementId,
			)
		) !== false;
	}

	public static function update( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'description', 'type', 'trigger_name', 'trigger_count', 'points_reward', 'icon', 'badge_image_id', 'is_secret', 'is_active' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['trigger_conditions'] ) ) {
			$u['trigger_conditions'] = wp_json_encode( $d['trigger_conditions'] );
		}
		return $wpdb->update( $wpdb->prefix . self::TABLE, $u, array( 'id' => $id ) ) !== false;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::USER_TABLE, array( 'achievement_id' => $id ) );
		return $wpdb->delete( $wpdb->prefix . self::TABLE, array( 'id' => $id ) ) !== false;
	}

	public static function processTrigger( int $userId, string $trigger, int $count = 1, ?array $context = null ): array {
		$achievements = self::getByTrigger( $trigger );
		$results      = array();
		foreach ( $achievements as $a ) {
			if ( ! empty( $a['trigger_conditions'] ) && ! self::checkConditions( $a['trigger_conditions'], $context ) ) {
				continue;
			}
			$results[] = self::incrementProgress( $userId, (int) $a['id'], $count );
		}
		return $results;
	}

	private static function checkConditions( array $conditions, ?array $context ): bool {
		if ( ! $context ) {
			return true;
		}
		foreach ( $conditions as $key => $val ) {
			if ( ! isset( $context[ $key ] ) || $context[ $key ] !== $val ) {
				return false;
			}
		}
		return true;
	}
}
