<?php
/**
 * Apollo Document Libraries.
 *
 * FASE 1: Arquitetura de Bibliotecas e Permissões.
 *
 * Organiza documentos em bibliotecas categorizadas:
 * - Apollo: Documentos institucionais (admin only).
 * - Cena-rio: Documentos comunitários/templates (todos autenticados).
 * - Private: Documentos privados por usuário.
 *
 * @package Apollo\\Modules\\Documents
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Document Libraries Manager
 */
class DocumentLibraries {

	/** @var string Tabela de documentos */
	private string $documents_table;

	/** @var string Tabela de permissões */
	private string $permissions_table;

	/** @var array Tipos de biblioteca */
	public const LIBRARY_TYPES = [
		'apollo'  => [
			'name'        => 'Apollo',
			'description' => 'Documentos institucionais e templates oficiais',
			'icon'        => 'ri-command-fill',
			'color'       => '#0f172a',
			'roles'       => [ 'administrator', 'apollo_admin' ],
		],
		'cenario' => [
			'name'        => 'Cena-rio',
			'description' => 'Documentos comunitários e modelos compartilhados',
			'icon'        => 'ri-community-line',
			'color'       => '#f97316',
			'roles'       => [ '*' ],
	// Todos autenticados
		],
		'private' => [
			'name'        => 'Minha Biblioteca',
			'description' => 'Documentos pessoais e privados',
			'icon'        => 'ri-folder-user-line',
			'color'       => '#3b82f6',
			'roles'       => [ 'owner' ],
	// Apenas o dono
		],
	];

	/** @var array Status de documento */
	public const DOCUMENT_STATUS = [
		'draft'     => [
			'name'  => 'Rascunho',
			'color' => '#f59e0b',
		],
		'ready'     => [
			'name'  => 'Pronto',
			'color' => '#3b82f6',
		],
		'signing'   => [
			'name'  => 'Em Assinatura',
			'color' => '#8b5cf6',
		],
		'completed' => [
			'name'  => 'Concluído',
			'color' => '#10b981',
		],
		'archived'  => [
			'name'  => 'Arquivado',
			'color' => '#64748b',
		],
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->documents_table   = $wpdb->prefix . 'apollo_documents';
		$this->permissions_table = $wpdb->prefix . 'apollo_document_permissions';
	}

	/**
	 * Create/update library tables
	 */
	public function createTables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Atualizar tabela de documentos com campo de biblioteca
		$sql1 = "CREATE TABLE {$this->documents_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            file_id varchar(32) NOT NULL UNIQUE,
            library_type enum('apollo','cenario','private') NOT NULL DEFAULT 'private',
            type enum('document','spreadsheet','template','contract') NOT NULL DEFAULT 'document',
            title varchar(255) NOT NULL,
            content longtext,
            html_content longtext,
            pdf_path varchar(500),
            pdf_hash varchar(64),
            status enum('draft','ready','signing','completed','archived') DEFAULT 'draft',
            is_template tinyint(1) DEFAULT 0,
            template_category varchar(100),
            requires_signatures tinyint(1) DEFAULT 0,
            total_signatures_needed int(2) DEFAULT 2,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            finalized_at datetime NULL,
            metadata longtext,
            PRIMARY KEY (id),
            UNIQUE KEY file_id_idx (file_id),
            KEY library_idx (library_type),
            KEY type_idx (type),
            KEY status_idx (status),
            KEY created_by_idx (created_by),
            KEY template_idx (is_template),
            KEY created_at_idx (created_at)
        ) $charset_collate;";

		// Tabela de permissões de documento
		$sql2 = "CREATE TABLE {$this->permissions_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NULL,
            role varchar(50) NULL,
            permission enum('view','edit','sign','admin') NOT NULL DEFAULT 'view',
            granted_by bigint(20) unsigned NOT NULL,
            granted_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY doc_user_perm_idx (document_id, user_id, permission),
            KEY document_idx (document_id),
            KEY user_idx (user_id),
            KEY role_idx (role),
            KEY permission_idx (permission)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	/**
	 * Get documents by library
	 *
	 * @param string   $library Library type
	 * @param int|null $user_id User ID (for private library)
	 * @param array    $args Query args
	 * @return array Documents
	 */
	public function getDocumentsByLibrary( string $library, ?int $user_id = null, array $args = [] ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		// Check access
		if ( ! $this->canAccessLibrary( $library, $user_id ) ) {
			return [];
		}

		// Build query
		$where  = [ 'library_type = %s' ];
		$params = [ $library ];

		// Private library: only own documents
		if ( $library === 'private' ) {
			$where[]  = 'created_by = %d';
			$params[] = $user_id;
		}

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		// Type filter
		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = $args['type'];
		}

		// Template filter
		if ( isset( $args['is_template'] ) ) {
			$where[]  = 'is_template = %d';
			$params[] = (int) $args['is_template'];
		}

		// Search
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(title LIKE %s OR content LIKE %s)';
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $search;
			$params[] = $search;
		}

		$where_sql = implode( ' AND ', $where );

		// Pagination
		$per_page = (int) ( $args['per_page'] ?? 20 );
		$page     = (int) ( $args['page'] ?? 1 );
		$offset   = ( $page - 1 ) * $per_page;

		// Order
		$order_by = $args['order_by'] ?? 'updated_at';
		$order    = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		// Count total
		$count_sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->documents_table} WHERE {$where_sql}",
			...$params
		);
		$total     = (int) $wpdb->get_var( $count_sql );

		// Get documents
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->documents_table}
             WHERE {$where_sql}
             ORDER BY {$order_by} {$order}
             LIMIT %d OFFSET %d",
			[ ...$params, $per_page, $offset ]
		);

		$documents = $wpdb->get_results( $sql, ARRAY_A );

		// Add author info
		foreach ( $documents as &$doc ) {
			$doc['author']      = $this->getAuthorInfo( (int) $doc['created_by'] );
			$doc['permissions'] = $this->getUserPermissions( (int) $doc['id'], $user_id );
		}

		return [
			'documents'    => $documents,
			'total'        => $total,
			'pages'        => ceil( $total / $per_page ),
			'current_page' => $page,
		];
	}

	/**
	 * Check if user can access library
	 *
	 * @param string $library Library type
	 * @param int    $user_id User ID
	 * @return bool
	 */
	public function canAccessLibrary( string $library, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$config = self::LIBRARY_TYPES[ $library ] ?? null;
		if ( ! $config ) {
			return false;
		}

		// Everyone authenticated can access cenario
		if ( in_array( '*', $config['roles'], true ) ) {
			return true;
		}

		// Owner can access private
		if ( in_array( 'owner', $config['roles'], true ) && $library === 'private' ) {
			return true;
		}

		// Check specific roles
		foreach ( $config['roles'] as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user can write to library
	 *
	 * @param string $library Library type
	 * @param int    $user_id User ID
	 * @return bool
	 */
	public function canWriteToLibrary( string $library, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		switch ( $library ) {
			case 'apollo':
				// Only admins can write to Apollo library
				return user_can( $user_id, 'manage_options' ) ||
						in_array( 'apollo_admin', $user->roles, true );

			case 'cenario':
				// All authenticated users can write to Cena-rio
				return true;

			case 'private':
				// Everyone can write to their private library
				return true;

			default:
				return false;
		}
	}

	/**
	 * Create document in library
	 *
	 * @param string   $library Library type
	 * @param array    $data Document data
	 * @param int|null $user_id User ID
	 * @return array Result
	 */
	public function createDocument( string $library, array $data, ?int $user_id = null ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		// Check write permission
		if ( ! $this->canWriteToLibrary( $library, $user_id ) ) {
			return [
				'success' => false,
				'error'   => 'Você não tem permissão para criar documentos nesta biblioteca',
			];
		}

		// Generate unique file ID
		$file_id = $this->generateFileId();

		$insert_data = [
			'file_id'           => $file_id,
			'library_type'      => $library,
			'type'              => $data['type'] ?? 'document',
			'title'             => sanitize_text_field( $data['title'] ?? 'Novo Documento' ),
			'content'           => wp_kses_post( $data['content'] ?? '' ),
			'html_content'      => $data['html_content'] ?? null,
			'status'            => 'draft',
			'is_template'       => (int) ( $data['is_template'] ?? 0 ),
			'template_category' => sanitize_text_field( $data['template_category'] ?? '' ),
			'created_by'        => $user_id,
			'metadata'          => json_encode( $data['metadata'] ?? [] ),
		];

		$result = $wpdb->insert( $this->documents_table, $insert_data );

		if ( $result ) {
			$document_id = $wpdb->insert_id;

			// Add owner permission
			$this->grantPermission( $document_id, $user_id, 'admin', $user_id );

			return [
				'success'     => true,
				'file_id'     => $file_id,
				'document_id' => $document_id,
				'url'         => $this->getDocumentUrl( $insert_data['type'], $file_id ),
			];
		}

		return [
			'success' => false,
			'error'   => 'Erro ao criar documento: ' . $wpdb->last_error,
		];
	}

	/**
	 * Get document by file ID
	 *
	 * @param string   $file_id File ID
	 * @param int|null $user_id User ID for permission check
	 * @return array|null Document or null
	 */
	public function getDocument( string $file_id, ?int $user_id = null ): ?array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		$document = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->documents_table} WHERE file_id = %s",
				$file_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			return null;
		}

		// Check access
		if ( ! $this->canAccessDocument( (int) $document['id'], $user_id ) ) {
			return null;
		}

		// Add metadata
		$document['author']       = $this->getAuthorInfo( (int) $document['created_by'] );
		$document['permissions']  = $this->getUserPermissions( (int) $document['id'], $user_id );
		$document['library_info'] = self::LIBRARY_TYPES[ $document['library_type'] ] ?? null;
		$document['status_info']  = self::DOCUMENT_STATUS[ $document['status'] ] ?? null;

		return $document;
	}

	/**
	 * Update document
	 *
	 * @param string   $file_id File ID
	 * @param array    $data Data to update
	 * @param int|null $user_id User ID
	 * @return array Result
	 */
	public function updateDocument( string $file_id, array $data, ?int $user_id = null ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		// Get document
		$document = $this->getDocument( $file_id, $user_id );
		if ( ! $document ) {
			return [
				'success' => false,
				'error'   => 'Documento não encontrado',
			];
		}

		// Check edit permission
		if ( ! $this->canEditDocument( (int) $document['id'], $user_id ) ) {
			return [
				'success' => false,
				'error'   => 'Você não tem permissão para editar este documento',
			];
		}

		// Check if document is finalized
		if ( $document['status'] !== 'draft' ) {
			return [
				'success' => false,
				'error'   => 'Documento finalizado não pode ser editado',
			];
		}

		// Build update data
		$update_data = [];

		if ( isset( $data['title'] ) ) {
			$update_data['title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['content'] ) ) {
			$update_data['content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['html_content'] ) ) {
			$update_data['html_content'] = $data['html_content'];
		}

		if ( isset( $data['metadata'] ) ) {
			$update_data['metadata'] = json_encode( $data['metadata'] );
		}

		if ( empty( $update_data ) ) {
			return [
				'success' => true,
				'message' => 'Nada para atualizar',
			];
		}

		$result = $wpdb->update(
			$this->documents_table,
			$update_data,
			[ 'file_id' => $file_id ]
		);

		if ( $result !== false ) {
			return [
				'success' => true,
				'message' => 'Documento atualizado',
			];
		}

		return [
			'success' => false,
			'error'   => 'Erro ao atualizar documento',
		];
	}

	/**
	 * Finalize document (lock for editing, prepare for signing)
	 *
	 * @param string   $file_id File ID
	 * @param int|null $user_id User ID
	 * @return array Result with PDF path
	 */
	public function finalizeDocument( string $file_id, ?int $user_id = null ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		$document = $this->getDocument( $file_id, $user_id );
		if ( ! $document ) {
			return [
				'success' => false,
				'error'   => 'Documento não encontrado',
			];
		}

		if ( ! $this->canEditDocument( (int) $document['id'], $user_id ) ) {
			return [
				'success' => false,
				'error'   => 'Sem permissão',
			];
		}

		if ( $document['status'] !== 'draft' ) {
			return [
				'success' => false,
				'error'   => 'Documento já foi finalizado',
			];
		}

		// Generate PDF
		$pdf_generator = new PdfGenerator();
		$pdf_path      = $pdf_generator->generateFromDocument( (int) $document['id'] );

		if ( ! $pdf_path ) {
			// Fallback: save HTML content
			$upload_dir = wp_upload_dir();
			$html_path  = $upload_dir['basedir'] . '/apollo-documents/html/' . $file_id . '.html';

			if ( ! file_exists( dirname( $html_path ) ) ) {
				wp_mkdir_p( dirname( $html_path ) );
			}

			file_put_contents( $html_path, $document['content'] ?? $document['html_content'] ?? '' );
		}

		// Calculate hash
		$content_hash = hash( 'sha256', $document['content'] . $document['title'] . time() );

		// Update document
		$wpdb->update(
			$this->documents_table,
			[
				'status'       => 'ready',
				'pdf_path'     => $pdf_path ? str_replace( ABSPATH, '/', $pdf_path ) : null,
				'pdf_hash'     => $content_hash,
				'finalized_at' => current_time( 'mysql' ),
			],
			[ 'file_id' => $file_id ]
		);

		return [
			'success'  => true,
			'message'  => 'Documento finalizado',
			'pdf_path' => $pdf_path,
			'pdf_url'  => $pdf_path ? $pdf_generator->getUrl( $pdf_path ) : null,
			'hash'     => $content_hash,
			'sign_url' => site_url( "/sign/{$file_id}" ),
		];
	}

	/**
	 * Move document to another library
	 *
	 * @param string   $file_id File ID
	 * @param string   $target_library Target library
	 * @param int|null $user_id User ID
	 * @return array Result
	 */
	public function moveDocument( string $file_id, string $target_library, ?int $user_id = null ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		$document = $this->getDocument( $file_id, $user_id );
		if ( ! $document ) {
			return [
				'success' => false,
				'error'   => 'Documento não encontrado',
			];
		}

		// Check permission to move
		if ( ! $this->canEditDocument( (int) $document['id'], $user_id ) ) {
			return [
				'success' => false,
				'error'   => 'Sem permissão para mover',
			];
		}

		// Check target library write permission
		if ( ! $this->canWriteToLibrary( $target_library, $user_id ) ) {
			return [
				'success' => false,
				'error'   => 'Sem permissão para a biblioteca destino',
			];
		}

		// Validate target library
		if ( ! isset( self::LIBRARY_TYPES[ $target_library ] ) ) {
			return [
				'success' => false,
				'error'   => 'Biblioteca inválida',
			];
		}

		$result = $wpdb->update(
			$this->documents_table,
			[ 'library_type' => $target_library ],
			[ 'file_id' => $file_id ]
		);

		if ( $result !== false ) {
			return [
				'success' => true,
				'message' => 'Documento movido',
			];
		}

		return [
			'success' => false,
			'error'   => 'Erro ao mover documento',
		];
	}

	/**
	 * Get templates from Cena-rio library
	 *
	 * @param string|null $category Category filter
	 * @return array Templates
	 */
	public function getTemplates( ?string $category = null ): array {
		return $this->getDocumentsByLibrary(
			'cenario',
			null,
			[
				'is_template'       => 1,
				'template_category' => $category,
			]
		);
	}

	/**
	 * Create document from template
	 *
	 * @param string   $template_file_id Template file ID
	 * @param string   $target_library Target library for new document
	 * @param int|null $user_id User ID
	 * @return array Result
	 */
	public function createFromTemplate( string $template_file_id, string $target_library = 'private', ?int $user_id = null ): array {
		$user_id = $user_id ?? get_current_user_id();

		// Get template
		$template = $this->getDocument( $template_file_id, $user_id );
		if ( ! $template || ! $template['is_template'] ) {
			return [
				'success' => false,
				'error'   => 'Template não encontrado',
			];
		}

		// Create new document based on template
		return $this->createDocument(
			$target_library,
			[
				'type'         => $template['type'],
				'title'        => $template['title'] . ' (Cópia)',
				'content'      => $template['content'],
				'html_content' => $template['html_content'],
				'metadata'     => [
					'source_template' => $template_file_id,
				],
			],
			$user_id
		);
	}

	/**
	 * Grant permission to user
	 *
	 * @param int    $document_id Document ID
	 * @param int    $user_id User ID
	 * @param string $permission Permission type
	 * @param int    $granted_by Grantor user ID
	 * @return bool Success
	 */
	public function grantPermission( int $document_id, int $user_id, string $permission, int $granted_by ): bool {
		global $wpdb;

		$result = $wpdb->replace(
			$this->permissions_table,
			[
				'document_id' => $document_id,
				'user_id'     => $user_id,
				'permission'  => $permission,
				'granted_by'  => $granted_by,
			]
		);

		return $result !== false;
	}

	/**
	 * Check if user can access document
	 *
	 * @param int $document_id Document ID
	 * @param int $user_id User ID
	 * @return bool
	 */
	public function canAccessDocument( int $document_id, int $user_id ): bool {
		global $wpdb;

		// Get document
		$document = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT library_type, created_by FROM {$this->documents_table} WHERE id = %d",
				$document_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			return false;
		}

		// Owner always has access
		if ( (int) $document['created_by'] === $user_id ) {
			return true;
		}

		// Check library access
		if ( ! $this->canAccessLibrary( $document['library_type'], $user_id ) ) {
			return false;
		}

		// For private library, only owner
		if ( $document['library_type'] === 'private' ) {
			return (int) $document['created_by'] === $user_id;
		}

		// Check specific permissions
		$has_permission = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->permissions_table}
                 WHERE document_id = %d AND user_id = %d",
				$document_id,
				$user_id
			)
		);

		return $has_permission > 0 || in_array( $document['library_type'], [ 'apollo', 'cenario' ], true );
	}

	/**
	 * Check if user can edit document
	 *
	 * @param int $document_id Document ID
	 * @param int $user_id User ID
	 * @return bool
	 */
	public function canEditDocument( int $document_id, int $user_id ): bool {
		global $wpdb;

		// Get document
		$document = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT created_by, library_type FROM {$this->documents_table} WHERE id = %d",
				$document_id
			),
			ARRAY_A
		);

		if ( ! $document ) {
			return false;
		}

		// Owner can always edit
		if ( (int) $document['created_by'] === $user_id ) {
			return true;
		}

		// Check admin/edit permission
		$permission = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT permission FROM {$this->permissions_table}
                 WHERE document_id = %d AND user_id = %d
                 AND permission IN ('edit', 'admin')",
				$document_id,
				$user_id
			)
		);

		return ! empty( $permission );
	}

	/**
	 * Get user permissions for document
	 *
	 * @param int $document_id Document ID
	 * @param int $user_id User ID
	 * @return array Permissions
	 */
	public function getUserPermissions( int $document_id, int $user_id ): array {
		global $wpdb;

		// Check if owner
		$is_owner = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT created_by FROM {$this->documents_table} WHERE id = %d",
				$document_id
			)
		) == $user_id;

		if ( $is_owner ) {
			return [ 'view', 'edit', 'sign', 'admin' ];
		}

		// Get explicit permissions
		$permissions = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT permission FROM {$this->permissions_table}
                 WHERE document_id = %d AND user_id = %d",
				$document_id,
				$user_id
			)
		);

		// Add view permission if can access library
		if ( empty( $permissions ) && $this->canAccessDocument( $document_id, $user_id ) ) {
			$permissions[] = 'view';
		}

		return $permissions;
	}

	/**
	 * Get library statistics
	 *
	 * @param string   $library Library type
	 * @param int|null $user_id User ID
	 * @return array Stats
	 */
	public function getLibraryStats( string $library, ?int $user_id = null ): array {
		global $wpdb;

		$user_id = $user_id ?? get_current_user_id();

		$where  = 'library_type = %s';
		$params = [ $library ];

		if ( $library === 'private' ) {
			$where   .= ' AND created_by = %d';
			$params[] = $user_id;
		}

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                    SUM(CASE WHEN status = 'signing' THEN 1 ELSE 0 END) as signing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN is_template = 1 THEN 1 ELSE 0 END) as templates
                 FROM {$this->documents_table}
                 WHERE {$where}",
				...$params
			),
			ARRAY_A
		);

		return $stats ?: [
			'total'     => 0,
			'drafts'    => 0,
			'ready'     => 0,
			'signing'   => 0,
			'completed' => 0,
			'templates' => 0,
		];
	}

	/**
	 * Get author info
	 *
	 * @param int $user_id User ID
	 * @return array Author info
	 */
	private function getAuthorInfo( int $user_id ): array {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return [
				'id'     => $user_id,
				'name'   => 'Usuário Removido',
				'avatar' => '',
			];
		}

		return [
			'id'       => $user_id,
			'name'     => $user->display_name,
			'username' => $user->user_login,
			'avatar'   => get_avatar_url( $user_id, [ 'size' => 48 ] ),
		];
	}

	/**
	 * Generate unique file ID
	 *
	 * @return string File ID
	 */
	private function generateFileId(): string {
		return wp_generate_password( 16, false, false );
	}

	/**
	 * Get document URL
	 *
	 * @param string $type Document type
	 * @param string $file_id File ID
	 * @return string URL
	 */
	private function getDocumentUrl( string $type, string $file_id ): string {
		$prefix = match ( $type ) {
			'document', 'contract' => 'doc',
			'spreadsheet' => 'pla',
			'template' => 'tpl',
			default => 'doc'
		};

		return site_url( "/{$prefix}/{$file_id}" );
	}
}
