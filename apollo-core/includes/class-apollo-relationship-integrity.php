<?php
/**
 * Apollo Relationship Integrity Checker
 *
 * Validates and repairs relationship data integrity.
 * Detects orphaned references, broken links, and bidirectional sync issues.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Relationship_Integrity
 *
 * Check and repair relationship integrity.
 */
class Apollo_Relationship_Integrity {

	/**
	 * Issue severity levels.
	 */
	const SEVERITY_ERROR   = 'error';
	const SEVERITY_WARNING = 'warning';
	const SEVERITY_INFO    = 'info';

	/**
	 * Issue types.
	 */
	const ISSUE_ORPHANED_REFERENCE  = 'orphaned_reference';
	const ISSUE_MISSING_INVERSE     = 'missing_inverse';
	const ISSUE_INVALID_POST_TYPE   = 'invalid_post_type';
	const ISSUE_DUPLICATE_REFERENCE = 'duplicate_reference';
	const ISSUE_SELF_REFERENCE      = 'self_reference';
	const ISSUE_INVALID_STORAGE     = 'invalid_storage';

	/**
	 * Check all relationships for integrity issues.
	 *
	 * @param array $options Check options.
	 * @return array Report with issues found.
	 */
	public static function check_all( array $options = array() ): array {
		$schema = Apollo_Relationships::get_schema();
		$report = array(
			'checked_at'    => \current_time( 'mysql' ),
			'relationships' => array(),
			'summary'       => array(
				'total_issues' => 0,
				'errors'       => 0,
				'warnings'     => 0,
				'info'         => 0,
			),
		);

		foreach ( $schema as $name => $definition ) {
			// Skip reverse relationships.
			if ( ! empty( $definition['is_reverse'] ) ) {
				continue;
			}

			$result                           = self::check_relationship( $name, $definition, $options );
			$report['relationships'][ $name ] = $result;

			// Update summary.
			$report['summary']['total_issues'] += \count( $result['issues'] );
			foreach ( $result['issues'] as $issue ) {
				switch ( $issue['severity'] ) {
					case self::SEVERITY_ERROR:
						++$report['summary']['errors'];
						break;
					case self::SEVERITY_WARNING:
						++$report['summary']['warnings'];
						break;
					case self::SEVERITY_INFO:
						++$report['summary']['info'];
						break;
				}
			}
		}

		return $report;
	}

	/**
	 * Check a specific relationship.
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @param array  $options    Check options.
	 * @return array Result with issues.
	 */
	public static function check_relationship( string $name, array $definition, array $options = array() ): array {
		$limit     = $options['limit'] ?? 1000;
		$from_type = $definition['from'] ?? '';
		$to_type   = $definition['to'] ?? '';
		$issues    = array();

		// Skip user relationships for now (different query).
		if ( $from_type === 'user' ) {
			$source_ids = self::get_user_ids( $limit );
		} else {
			$source_ids = self::get_post_ids( $from_type, $limit );
		}

		foreach ( $source_ids as $source_id ) {
			$source_issues = self::check_source( $source_id, $name, $definition );
			$issues        = \array_merge( $issues, $source_issues );
		}

		// Check bidirectional integrity.
		if ( ! empty( $definition['bidirectional'] ) && ! empty( $definition['inverse'] ) ) {
			$bidirectional_issues = self::check_bidirectional( $name, $definition, $source_ids );
			$issues               = \array_merge( $issues, $bidirectional_issues );
		}

		return array(
			'relationship'  => $name,
			'sources_count' => \count( $source_ids ),
			'issues'        => $issues,
			'issues_count'  => \count( $issues ),
		);
	}

	/**
	 * Check a single source item.
	 *
	 * @param int    $source_id  Source ID.
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @return array Issues found.
	 */
	private static function check_source( int $source_id, string $name, array $definition ): array {
		$issues  = array();
		$to_type = $definition['to'] ?? '';
		$storage = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;

		// Get related IDs.
		$related_ids = Apollo_Relationship_Query::get_related( $source_id, $name, array( 'return' => 'ids' ) );

		if ( empty( $related_ids ) ) {
			return array();
		}

		// Check for duplicates.
		$unique_ids = \array_unique( $related_ids );
		if ( \count( $unique_ids ) !== \count( $related_ids ) ) {
			$duplicates = \array_diff_assoc( $related_ids, $unique_ids );
			$issues[]   = array(
				'type'       => self::ISSUE_DUPLICATE_REFERENCE,
				'severity'   => self::SEVERITY_WARNING,
				'source_id'  => $source_id,
				'message'    => \sprintf(
					'Duplicate references found: %s',
					\implode( ', ', $duplicates )
				),
				'duplicates' => $duplicates,
			);
		}

		// Check each related ID.
		foreach ( $unique_ids as $related_id ) {
			// Check self-reference (if not allowed).
			if ( ! ( $definition['self_referential'] ?? false ) && $source_id === $related_id ) {
				$issues[] = array(
					'type'       => self::ISSUE_SELF_REFERENCE,
					'severity'   => self::SEVERITY_ERROR,
					'source_id'  => $source_id,
					'related_id' => $related_id,
					'message'    => 'Self-reference detected in non-self-referential relationship.',
				);
				continue;
			}

			// Check if target exists.
			if ( $to_type === 'user' ) {
				$exists = \get_userdata( $related_id ) !== false;
			} elseif ( \is_array( $to_type ) ) {
				// Polymorphic - check if any type exists.
				$post   = \get_post( $related_id );
				$exists = $post && \in_array( $post->post_type, $to_type, true );
			} else {
				$post   = \get_post( $related_id );
				$exists = $post && $post->post_type === $to_type;

				// Invalid post type.
				if ( $post && $post->post_type !== $to_type ) {
					$issues[] = array(
						'type'          => self::ISSUE_INVALID_POST_TYPE,
						'severity'      => self::SEVERITY_ERROR,
						'source_id'     => $source_id,
						'related_id'    => $related_id,
						'expected_type' => $to_type,
						'actual_type'   => $post->post_type,
						'message'       => \sprintf(
							'Related post %d has type "%s" but expected "%s".',
							$related_id,
							$post->post_type,
							$to_type
						),
					);
					continue;
				}
			}

			if ( ! $exists ) {
				$issues[] = array(
					'type'       => self::ISSUE_ORPHANED_REFERENCE,
					'severity'   => self::SEVERITY_ERROR,
					'source_id'  => $source_id,
					'related_id' => $related_id,
					'message'    => \sprintf(
						'Referenced %s ID %d does not exist.',
						$to_type,
						$related_id
					),
				);
			}
		}

		return $issues;
	}

	/**
	 * Check bidirectional relationship integrity.
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @param array  $source_ids Source IDs to check.
	 * @return array Issues found.
	 */
	private static function check_bidirectional( string $name, array $definition, array $source_ids ): array {
		$issues  = array();
		$inverse = $definition['inverse'] ?? '';

		if ( empty( $inverse ) ) {
			return array();
		}

		foreach ( $source_ids as $source_id ) {
			$related_ids = Apollo_Relationship_Query::get_related( $source_id, $name, array( 'return' => 'ids' ) );

			foreach ( $related_ids as $related_id ) {
				// Check if inverse relationship exists.
				$inverse_ids = Apollo_Relationship_Query::get_related( $related_id, $inverse, array( 'return' => 'ids' ) );

				if ( ! \in_array( $source_id, $inverse_ids, true ) ) {
					$issues[] = array(
						'type'         => self::ISSUE_MISSING_INVERSE,
						'severity'     => self::SEVERITY_WARNING,
						'source_id'    => $source_id,
						'related_id'   => $related_id,
						'relationship' => $name,
						'inverse'      => $inverse,
						'message'      => \sprintf(
							'%s → %d has no inverse link %s → %d.',
							$name,
							$related_id,
							$inverse,
							$source_id
						),
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Repair all issues for a relationship.
	 *
	 * @param string $name    Relationship name.
	 * @param array  $options Repair options.
	 * @return array Repair report.
	 */
	public static function repair_relationship( string $name, array $options = array() ): array {
		$definition = Apollo_Relationships::get( $name );

		if ( ! $definition ) {
			return array(
				'success' => false,
				'error'   => 'Relationship not found.',
			);
		}

		$check_result = self::check_relationship( $name, $definition, $options );
		$repaired     = array();
		$failed       = array();
		$dry_run      = $options['dry_run'] ?? false;

		foreach ( $check_result['issues'] as $issue ) {
			$repair_result = self::repair_issue( $issue, $definition, $dry_run );

			if ( $repair_result['success'] ) {
				$repaired[] = $repair_result;
			} else {
				$failed[] = $repair_result;
			}
		}

		return array(
			'relationship'   => $name,
			'issues_found'   => \count( $check_result['issues'] ),
			'repaired'       => $repaired,
			'repaired_count' => \count( $repaired ),
			'failed'         => $failed,
			'failed_count'   => \count( $failed ),
			'dry_run'        => $dry_run,
		);
	}

	/**
	 * Repair a specific issue.
	 *
	 * @param array $issue      Issue data.
	 * @param array $definition Relationship definition.
	 * @param bool  $dry_run    If true, don't actually repair.
	 * @return array Repair result.
	 */
	private static function repair_issue( array $issue, array $definition, bool $dry_run = false ): array {
		$source_id  = $issue['source_id'] ?? 0;
		$related_id = $issue['related_id'] ?? 0;
		$type       = $issue['type'] ?? '';

		switch ( $type ) {
			case self::ISSUE_ORPHANED_REFERENCE:
				return self::repair_orphaned( $source_id, $related_id, $definition, $dry_run );

			case self::ISSUE_DUPLICATE_REFERENCE:
				return self::repair_duplicates( $source_id, $definition, $dry_run );

			case self::ISSUE_MISSING_INVERSE:
				return self::repair_missing_inverse( $issue, $definition, $dry_run );

			case self::ISSUE_INVALID_POST_TYPE:
				return self::repair_orphaned( $source_id, $related_id, $definition, $dry_run );

			case self::ISSUE_SELF_REFERENCE:
				return self::repair_orphaned( $source_id, $related_id, $definition, $dry_run );

			default:
				return array(
					'success' => false,
					'issue'   => $issue,
					'action'  => 'unknown',
					'message' => 'Unknown issue type.',
				);
		}
	}

	/**
	 * Repair orphaned reference by removing it.
	 *
	 * @param int   $source_id  Source ID.
	 * @param int   $related_id Related ID to remove.
	 * @param array $definition Relationship definition.
	 * @param bool  $dry_run    If true, don't actually repair.
	 * @return array Result.
	 */
	private static function repair_orphaned( int $source_id, int $related_id, array $definition, bool $dry_run = false ): array {
		if ( $dry_run ) {
			return array(
				'success'    => true,
				'source_id'  => $source_id,
				'related_id' => $related_id,
				'action'     => 'remove_orphaned',
				'message'    => 'Would remove orphaned reference.',
				'dry_run'    => true,
			);
		}

		$meta_key  = $definition['meta_key'] ?? '';
		$from_type = $definition['from'] ?? '';

		if ( empty( $meta_key ) ) {
			return array(
				'success' => false,
				'action'  => 'remove_orphaned',
				'message' => 'No meta key defined.',
			);
		}

		// Get current IDs.
		if ( $from_type === 'user' ) {
			$user_meta_key = $definition['user_meta'] ?? $meta_key;
			$current       = \get_user_meta( $source_id, $user_meta_key, true );
		} else {
			$current = \get_post_meta( $source_id, $meta_key, true );
		}

		$storage = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;
		$ids     = self::parse_stored_ids( $current, $storage );

		// Remove orphaned ID.
		$key = \array_search( $related_id, $ids, true );
		if ( false !== $key ) {
			unset( $ids[ $key ] );
			$ids = \array_values( $ids );

			$value = self::format_for_storage( $ids, $storage );

			if ( $from_type === 'user' ) {
				\update_user_meta( $source_id, $user_meta_key, $value );
			} else {
				\update_post_meta( $source_id, $meta_key, $value );
			}

			return array(
				'success'    => true,
				'source_id'  => $source_id,
				'related_id' => $related_id,
				'action'     => 'remove_orphaned',
				'message'    => 'Removed orphaned reference.',
			);
		}

		return array(
			'success' => false,
			'action'  => 'remove_orphaned',
			'message' => 'Reference not found in storage.',
		);
	}

	/**
	 * Repair duplicate references.
	 *
	 * @param int   $source_id  Source ID.
	 * @param array $definition Relationship definition.
	 * @param bool  $dry_run    If true, don't actually repair.
	 * @return array Result.
	 */
	private static function repair_duplicates( int $source_id, array $definition, bool $dry_run = false ): array {
		$meta_key  = $definition['meta_key'] ?? '';
		$from_type = $definition['from'] ?? '';

		if ( empty( $meta_key ) ) {
			return array(
				'success' => false,
				'action'  => 'remove_duplicates',
				'message' => 'No meta key defined.',
			);
		}

		if ( $dry_run ) {
			return array(
				'success'   => true,
				'source_id' => $source_id,
				'action'    => 'remove_duplicates',
				'message'   => 'Would remove duplicate references.',
				'dry_run'   => true,
			);
		}

		// Get current IDs.
		if ( $from_type === 'user' ) {
			$user_meta_key = $definition['user_meta'] ?? $meta_key;
			$current       = \get_user_meta( $source_id, $user_meta_key, true );
		} else {
			$current = \get_post_meta( $source_id, $meta_key, true );
		}

		$storage    = $definition['storage'] ?? Apollo_Relationships::STORAGE_SERIALIZED_ARRAY;
		$ids        = self::parse_stored_ids( $current, $storage );
		$unique_ids = \array_values( \array_unique( $ids ) );

		if ( \count( $unique_ids ) === \count( $ids ) ) {
			return array(
				'success'   => true,
				'source_id' => $source_id,
				'action'    => 'remove_duplicates',
				'message'   => 'No duplicates found.',
			);
		}

		$value = self::format_for_storage( $unique_ids, $storage );

		if ( $from_type === 'user' ) {
			\update_user_meta( $source_id, $user_meta_key, $value );
		} else {
			\update_post_meta( $source_id, $meta_key, $value );
		}

		return array(
			'success'       => true,
			'source_id'     => $source_id,
			'action'        => 'remove_duplicates',
			'removed_count' => \count( $ids ) - \count( $unique_ids ),
			'message'       => 'Removed duplicate references.',
		);
	}

	/**
	 * Repair missing inverse relationship.
	 *
	 * @param array $issue      Issue data.
	 * @param array $definition Relationship definition.
	 * @param bool  $dry_run    If true, don't actually repair.
	 * @return array Result.
	 */
	private static function repair_missing_inverse( array $issue, array $definition, bool $dry_run = false ): array {
		$source_id    = $issue['source_id'] ?? 0;
		$related_id   = $issue['related_id'] ?? 0;
		$inverse_name = $issue['inverse'] ?? '';

		if ( empty( $inverse_name ) ) {
			return array(
				'success' => false,
				'action'  => 'add_inverse',
				'message' => 'No inverse relationship defined.',
			);
		}

		if ( $dry_run ) {
			return array(
				'success'    => true,
				'source_id'  => $source_id,
				'related_id' => $related_id,
				'action'     => 'add_inverse',
				'message'    => 'Would add inverse relationship.',
				'dry_run'    => true,
			);
		}

		// Add the inverse connection.
		$result = Apollo_Relationship_Query::connect( $related_id, $source_id, $inverse_name );

		return array(
			'success'    => $result,
			'source_id'  => $source_id,
			'related_id' => $related_id,
			'action'     => 'add_inverse',
			'message'    => $result ? 'Added inverse relationship.' : 'Failed to add inverse relationship.',
		);
	}

	/**
	 * Get post IDs for a post type.
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit     Maximum number of posts.
	 * @return array<int> Post IDs.
	 */
	private static function get_post_ids( string $post_type, int $limit = 1000 ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
			)
		);

		return $query->posts;
	}

	/**
	 * Get user IDs.
	 *
	 * @param int $limit Maximum number of users.
	 * @return array<int> User IDs.
	 */
	private static function get_user_ids( int $limit = 1000 ): array {
		$users = \get_users(
			array(
				'number' => $limit,
				'fields' => 'ID',
			)
		);

		return \array_map( 'intval', $users );
	}

	/**
	 * Parse stored IDs.
	 *
	 * @param mixed  $value   Stored value.
	 * @param string $storage Storage type.
	 * @return array<int> IDs.
	 */
	private static function parse_stored_ids( $value, string $storage ): array {
		if ( empty( $value ) ) {
			return array();
		}

		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SINGLE_ID:
				return array( (int) $value );

			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				if ( \is_string( $value ) ) {
					$value = \maybe_unserialize( $value );
				}
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();

			case Apollo_Relationships::STORAGE_JSON_ARRAY:
				if ( \is_string( $value ) ) {
					$value = \json_decode( $value, true );
				}
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();

			case Apollo_Relationships::STORAGE_CSV:
				$parts = \explode( ',', (string) $value );
				return \array_map( 'intval', \array_filter( $parts ) );

			default:
				return \is_array( $value ) ? \array_map( 'intval', $value ) : array();
		}
	}

	/**
	 * Format IDs for storage.
	 *
	 * @param array  $ids     IDs to store.
	 * @param string $storage Storage type.
	 * @return mixed Formatted value.
	 */
	private static function format_for_storage( array $ids, string $storage ) {
		switch ( $storage ) {
			case Apollo_Relationships::STORAGE_SINGLE_ID:
				return ! empty( $ids ) ? $ids[0] : 0;

			case Apollo_Relationships::STORAGE_SERIALIZED_ARRAY:
				return $ids;

			case Apollo_Relationships::STORAGE_JSON_ARRAY:
				return \wp_json_encode( $ids );

			case Apollo_Relationships::STORAGE_CSV:
				return \implode( ',', $ids );

			default:
				return $ids;
		}
	}

	/**
	 * Generate integrity report as HTML.
	 *
	 * @param array $report Report data.
	 * @return string HTML output.
	 */
	public static function generate_html_report( array $report ): string {
		$html  = '<div class="apollo-integrity-report">';
		$html .= '<h2>Relationship Integrity Report</h2>';
		$html .= '<p><strong>Checked at:</strong> ' . \esc_html( $report['checked_at'] ) . '</p>';

		// Summary.
		$html .= '<div class="summary">';
		$html .= '<h3>Summary</h3>';
		$html .= '<ul>';
		$html .= '<li>Total Issues: ' . (int) $report['summary']['total_issues'] . '</li>';
		$html .= '<li class="error">Errors: ' . (int) $report['summary']['errors'] . '</li>';
		$html .= '<li class="warning">Warnings: ' . (int) $report['summary']['warnings'] . '</li>';
		$html .= '<li class="info">Info: ' . (int) $report['summary']['info'] . '</li>';
		$html .= '</ul>';
		$html .= '</div>';

		// Relationships.
		$html .= '<div class="relationships">';
		foreach ( $report['relationships'] as $name => $result ) {
			$html .= '<div class="relationship">';
			$html .= '<h4>' . \esc_html( $name ) . '</h4>';
			$html .= '<p>Sources checked: ' . (int) $result['sources_count'] . '</p>';
			$html .= '<p>Issues found: ' . (int) $result['issues_count'] . '</p>';

			if ( ! empty( $result['issues'] ) ) {
				$html .= '<table class="issues">';
				$html .= '<tr><th>Type</th><th>Severity</th><th>Message</th></tr>';
				foreach ( \array_slice( $result['issues'], 0, 50 ) as $issue ) {
					$html .= '<tr class="' . \esc_attr( $issue['severity'] ) . '">';
					$html .= '<td>' . \esc_html( $issue['type'] ) . '</td>';
					$html .= '<td>' . \esc_html( $issue['severity'] ) . '</td>';
					$html .= '<td>' . \esc_html( $issue['message'] ) . '</td>';
					$html .= '</tr>';
				}
				if ( \count( $result['issues'] ) > 50 ) {
					$html .= '<tr><td colspan="3">... and ' . ( \count( $result['issues'] ) - 50 ) . ' more issues</td></tr>';
				}
				$html .= '</table>';
			}

			$html .= '</div>';
		}
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Schedule regular integrity checks.
	 *
	 * @return void
	 */
	public static function schedule_checks(): void {
		if ( ! \wp_next_scheduled( 'apollo_relationship_integrity_check' ) ) {
			\wp_schedule_event( \time(), 'weekly', 'apollo_relationship_integrity_check' );
		}

		\add_action( 'apollo_relationship_integrity_check', array( self::class, 'run_scheduled_check' ) );
	}

	/**
	 * Run scheduled integrity check.
	 *
	 * @return void
	 */
	public static function run_scheduled_check(): void {
		$report = self::check_all( array( 'limit' => 500 ) );

		// Log if issues found.
		if ( $report['summary']['total_issues'] > 0 ) {
			\error_log(
				\sprintf(
					'Apollo Relationship Integrity: %d issues found (%d errors, %d warnings).',
					$report['summary']['total_issues'],
					$report['summary']['errors'],
					$report['summary']['warnings']
				)
			);

			// Store report for admin review.
			\update_option( 'apollo_last_integrity_report', $report );

			// Emit event.
			if ( \class_exists( Apollo_Event_Bus::class ) ) {
				Apollo_Event_Bus::emit(
					'apollo.integrity.issues_found',
					array(
						'report' => $report,
					)
				);
			}
		}
	}
}
