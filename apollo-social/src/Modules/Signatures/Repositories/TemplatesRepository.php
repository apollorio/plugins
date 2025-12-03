<?php
/**
 * Templates Repository
 *
 * @package Apollo\Modules\Signatures\Repositories
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

namespace Apollo\Modules\Signatures\Repositories;

use Apollo\Modules\Signatures\Models\DocumentTemplate;

/**
 * Templates Repository
 *
 * CRUD operations for document templates with placeholders
 *
 * @since 1.0.0
 */
class TemplatesRepository {

	/** @var string */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'apollo_document_templates';
	}

	/**
	 * Create templates table
	 *
	 * @return bool
	 */
	public function createTable(): bool {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            content longtext NOT NULL,
            placeholders longtext,
            category varchar(100) DEFAULT 'general',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_category (category),
            KEY idx_active (is_active),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return true;
	}

	/**
	 * Create a new template
	 *
	 * @param array $data
	 * @return DocumentTemplate|false
	 */
	public function create( array $data ): DocumentTemplate|false {
		global $wpdb;

		// Extract and validate placeholders
		$template     = new DocumentTemplate( $data );
		$placeholders = $template->extractPlaceholders();

		$insert_data = array(
			'name'         => $data['name'],
			'description'  => $data['description'] ?? '',
			'content'      => $data['content'],
			'placeholders' => json_encode( $placeholders ),
			'category'     => $data['category'] ?? 'general',
			'is_active'    => $data['is_active'] ?? 1,
			'created_by'   => get_current_user_id(),
		);

		$result = $wpdb->insert( $this->table_name, $insert_data );

		if ( $result === false ) {
			return false;
		}

		return $this->findById( $wpdb->insert_id );
	}

	/**
	 * Find template by ID
	 *
	 * @param int $id
	 * @return DocumentTemplate|null
	 */
	public function findById( int $id ): ?DocumentTemplate {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $result ? new DocumentTemplate( $result ) : null;
	}

	/**
	 * Find all templates
	 *
	 * @param array $filters
	 * @param int   $limit
	 * @param int   $offset
	 * @return array
	 */
	public function findAll( array $filters = array(), int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$where_clauses = array( '1=1' );
		$values        = array();

		if ( ! empty( $filters['category'] ) ) {
			$where_clauses[] = 'category = %s';
			$values[]        = $filters['category'];
		}

		if ( isset( $filters['is_active'] ) ) {
			$where_clauses[] = 'is_active = %d';
			$values[]        = (int) $filters['is_active'];
		}

		if ( ! empty( $filters['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR description LIKE %s)';
			$search_term     = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$values[]        = $search_term;
			$values[]        = $search_term;
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$order_by  = $filters['order_by'] ?? 'created_at DESC';

		$sql = "SELECT * FROM {$this->table_name}
                WHERE {$where_sql}
                ORDER BY {$order_by}
                LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$values ),
			ARRAY_A
		);

		return array_map(
			function ( $row ) {
				return new DocumentTemplate( $row );
			},
			$results ?: array()
		);
	}

	/**
	 * Update template
	 *
	 * @param int   $id
	 * @param array $data
	 * @return DocumentTemplate|false
	 */
	public function update( int $id, array $data ): DocumentTemplate|false {
		global $wpdb;

		// Re-extract placeholders if content changed
		if ( isset( $data['content'] ) ) {
			$template             = new DocumentTemplate( $data );
			$data['placeholders'] = json_encode( $template->extractPlaceholders() );
		}

		// Remove non-updatable fields
		unset( $data['id'], $data['created_at'], $data['created_by'] );

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id )
		);

		if ( $result === false ) {
			return false;
		}

		return $this->findById( $id );
	}

	/**
	 * Delete template
	 *
	 * @param int $id
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete( $this->table_name, array( 'id' => $id ) );

		return $result !== false;
	}

	/**
	 * Get templates count
	 *
	 * @param array $filters
	 * @return int
	 */
	public function count( array $filters = array() ): int {
		global $wpdb;

		$where_clauses = array( '1=1' );
		$values        = array();

		if ( ! empty( $filters['category'] ) ) {
			$where_clauses[] = 'category = %s';
			$values[]        = $filters['category'];
		}

		if ( isset( $filters['is_active'] ) ) {
			$where_clauses[] = 'is_active = %d';
			$values[]        = (int) $filters['is_active'];
		}

		if ( ! empty( $filters['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR description LIKE %s)';
			$search_term     = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$values[]        = $search_term;
			$values[]        = $search_term;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";

		if ( empty( $values ) ) {
			return (int) $wpdb->get_var( $sql );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) );
	}

	/**
	 * Get templates by category
	 *
	 * @param string $category
	 * @return array
	 */
	public function findByCategory( string $category ): array {
		return $this->findAll(
			array(
				'category'  => $category,
				'is_active' => 1,
			)
		);
	}

	/**
	 * Duplicate template
	 *
	 * @param int    $id
	 * @param string $new_name
	 * @return DocumentTemplate|false
	 */
	public function duplicate( int $id, string $new_name ): DocumentTemplate|false {
		$template = $this->findById( $id );

		if ( ! $template ) {
			return false;
		}

		$data = $template->toArray();
		unset( $data['id'], $data['created_at'], $data['updated_at'] );
		$data['name'] = $new_name;

		return $this->create( $data );
	}

	/**
	 * Get available categories
	 *
	 * @return array
	 */
	public function getCategories(): array {
		global $wpdb;

		$results = $wpdb->get_col(
			"SELECT DISTINCT category FROM {$this->table_name} ORDER BY category"
		);

		return $results ?: array();
	}
}
