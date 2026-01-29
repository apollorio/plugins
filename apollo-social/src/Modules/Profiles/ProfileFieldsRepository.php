<?php
declare(strict_types=1);
namespace Apollo\Modules\Profiles;

final class ProfileFieldsRepository {
	private const FIELDS = 'apollo_profile_fields';
	private const GROUPS = 'apollo_profile_field_groups';
	private const VALUES = 'apollo_profile_field_values';

	public static function createGroup( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::GROUPS,
			array(
				'name'          => sanitize_text_field( $d['name'] ),
				'slug'          => sanitize_title( $d['slug'] ?? $d['name'] ),
				'description'   => sanitize_textarea_field( $d['description'] ?? '' ),
				'context'       => in_array( $d['context'] ?? '', array( 'user', 'comuna', 'nucleo', 'all' ) ) ? $d['context'] : 'user',
				'is_repeatable' => (int) ( $d['is_repeatable'] ?? 0 ),
				'sort_order'    => (int) ( $d['sort_order'] ?? 0 ),
				'is_active'     => 1,
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function getGroup( int $id ): ?array {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::GROUPS . ' WHERE id=%d', $id ), ARRAY_A ) ?: null;
	}

	public static function getGroups( string $context = 'all' ): array {
		global $wpdb;
		$w = $context === 'all' ? '' : "WHERE context IN ('all','{$context}')";
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::GROUPS . " {$w} AND is_active=1 ORDER BY sort_order", ARRAY_A ) ?: array();
	}

	public static function updateGroup( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'name', 'slug', 'description', 'context', 'is_repeatable', 'sort_order', 'is_active' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		return $wpdb->update( $wpdb->prefix . self::GROUPS, $u, array( 'id' => $id ) ) !== false;
	}

	public static function deleteGroup( int $id ): bool {
		global $wpdb;
		$fields = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}" . self::FIELDS . ' WHERE group_id=%d', $id ) );
		if ( $fields ) {
			$in = \implode( ',', $fields );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}" . self::VALUES . " WHERE field_id IN ({$in})" );
			$wpdb->delete( $wpdb->prefix . self::FIELDS, array( 'group_id' => $id ) );
		}
		return $wpdb->delete( $wpdb->prefix . self::GROUPS, array( 'id' => $id ) ) !== false;
	}

	public static function reorderGroups( array $order ): bool {
		global $wpdb;
		foreach ( $order as $pos => $id ) {
			$wpdb->update( $wpdb->prefix . self::GROUPS, array( 'sort_order' => $pos ), array( 'id' => (int) $id ) );
		}
		return true;
	}

	public static function createField( array $d ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::FIELDS,
			array(
				'group_id'          => (int) $d['group_id'],
				'name'              => sanitize_text_field( $d['name'] ),
				'slug'              => sanitize_title( $d['slug'] ?? $d['name'] ),
				'type'              => $d['type'] ?? 'text',
				'options'           => isset( $d['options'] ) ? wp_json_encode( $d['options'] ) : null,
				'placeholder'       => sanitize_text_field( $d['placeholder'] ?? '' ),
				'default_value'     => $d['default_value'] ?? '',
				'is_required'       => (int) ( $d['is_required'] ?? 0 ),
				'is_public'         => (int) ( $d['is_public'] ?? 1 ),
				'is_searchable'     => (int) ( $d['is_searchable'] ?? 0 ),
				'is_editable'       => (int) ( $d['is_editable'] ?? 1 ),
				'visibility'        => $d['visibility'] ?? 'everyone',
				'validation_rules'  => isset( $d['validation_rules'] ) ? wp_json_encode( $d['validation_rules'] ) : null,
				'conditional_logic' => isset( $d['conditional_logic'] ) ? wp_json_encode( $d['conditional_logic'] ) : null,
				'sort_order'        => (int) ( $d['sort_order'] ?? 0 ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function getField( int $id ): ?array {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::FIELDS . ' WHERE id=%d', $id ), ARRAY_A );
		if ( $r ) {
			$r['options']           = json_decode( $r['options'] ?? '', true );
			$r['validation_rules']  = json_decode( $r['validation_rules'] ?? '', true );
			$r['conditional_logic'] = json_decode( $r['conditional_logic'] ?? '', true );
		}
		return $r ?: null;
	}

	public static function getFieldsByGroup( int $groupId ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::FIELDS . ' WHERE group_id=%d ORDER BY sort_order',
				$groupId
			),
			ARRAY_A
		) ?: array();
		foreach ( $rows as &$r ) {
			$r['options']           = json_decode( $r['options'] ?? '', true );
			$r['validation_rules']  = json_decode( $r['validation_rules'] ?? '', true );
			$r['conditional_logic'] = json_decode( $r['conditional_logic'] ?? '', true );
		}
		return $rows;
	}

	public static function updateField( int $id, array $d ): bool {
		global $wpdb;
		$u = array();
		foreach ( array( 'group_id', 'name', 'slug', 'type', 'placeholder', 'default_value', 'is_required', 'is_public', 'is_searchable', 'is_editable', 'visibility', 'sort_order' ) as $k ) {
			if ( isset( $d[ $k ] ) ) {
				$u[ $k ] = $d[ $k ];
			}
		}
		if ( isset( $d['options'] ) ) {
			$u['options'] = wp_json_encode( $d['options'] );
		}
		if ( isset( $d['validation_rules'] ) ) {
			$u['validation_rules'] = wp_json_encode( $d['validation_rules'] );
		}
		if ( isset( $d['conditional_logic'] ) ) {
			$u['conditional_logic'] = wp_json_encode( $d['conditional_logic'] );
		}
		return $wpdb->update( $wpdb->prefix . self::FIELDS, $u, array( 'id' => $id ) ) !== false;
	}

	public static function deleteField( int $id ): bool {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::VALUES, array( 'field_id' => $id ) );
		return $wpdb->delete( $wpdb->prefix . self::FIELDS, array( 'id' => $id ) ) !== false;
	}

	public static function reorderFields( int $groupId, array $order ): bool {
		global $wpdb;
		foreach ( $order as $pos => $id ) {
			$wpdb->update(
				$wpdb->prefix . self::FIELDS,
				array( 'sort_order' => $pos ),
				array(
					'id'       => (int) $id,
					'group_id' => $groupId,
				)
			);
		}
		return true;
	}

	public static function saveValue( int $userId, int $fieldId, $value ): bool {
		global $wpdb;
		$v = is_array( $value ) ? wp_json_encode( $value ) : $value;
		return $wpdb->replace(
			$wpdb->prefix . self::VALUES,
			array(
				'user_id'  => $userId,
				'field_id' => $fieldId,
				'value'    => $v,
			)
		) !== false;
	}

	public static function getValue( int $userId, int $fieldId ) {
		global $wpdb;
		$v = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$wpdb->prefix}" . self::VALUES . ' WHERE user_id=%d AND field_id=%d', $userId, $fieldId ) );
		if ( $v === null ) {
			return null;
		}
		$decoded = json_decode( $v, true );
		return json_last_error() === JSON_ERROR_NONE ? $decoded : $v;
	}

	public static function getUserProfileData( int $userId, string $context = 'user' ): array {
		global $wpdb;
		$groups = self::getGroups( $context );
		$data   = array();
		foreach ( $groups as $g ) {
			$g['fields'] = array();
			$fields      = self::getFieldsByGroup( (int) $g['id'] );
			foreach ( $fields as $f ) {
				$f['value']    = self::getValue( $userId, (int) $f['id'] );
				$g['fields'][] = $f;
			}
			$data[] = $g;
		}
		return $data;
	}

	public static function calculateCompleteness( int $userId, string $context = 'user' ): array {
		$profile        = self::getUserProfileData( $userId, $context );
		$total          = 0;
		$filled         = 0;
		$required       = 0;
		$requiredFilled = 0;
		foreach ( $profile as $g ) {
			foreach ( $g['fields'] as $f ) {
				++$total;
				$hasValue = ! empty( $f['value'] ) || $f['value'] === '0';
				if ( $hasValue ) {
					++$filled;
				}
				if ( $f['is_required'] ) {
					++$required;
					if ( $hasValue ) {
						++$requiredFilled;
					}
				}
			}
		}
		return array(
			'total_fields'      => $total,
			'filled_fields'     => $filled,
			'required_fields'   => $required,
			'required_filled'   => $requiredFilled,
			'percentage'        => $total > 0 ? round( ( $filled / $total ) * 100 ) : 100,
			'required_complete' => $required === $requiredFilled,
		);
	}
}
