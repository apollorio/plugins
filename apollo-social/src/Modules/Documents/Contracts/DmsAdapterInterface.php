<?php
/**
 * DMS Adapter Interface
 *
 * Defines the contract for Document Management System adapters.
 * Enables pluggable document storage backends (local, S3, etc.).
 *
 * @package Apollo\Modules\Documents\Contracts
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 */

declare( strict_types=1 );

namespace Apollo\Modules\Documents\Contracts;

use WP_Error;

/**
 * Interface DmsAdapterInterface
 *
 * Contract for DMS storage adapters implementing document
 * storage, retrieval, and management operations.
 */
interface DmsAdapterInterface {

	/**
	 * Get the unique identifier for this adapter.
	 *
	 * @return string Adapter identifier.
	 */
	public function get_identifier(): string;

	/**
	 * Get the human-readable name for this adapter.
	 *
	 * @return string Adapter display name.
	 */
	public function get_name(): string;

	/**
	 * Check if the adapter is available and properly configured.
	 *
	 * @return bool True if adapter is ready to use.
	 */
	public function is_available(): bool;

	/**
	 * Get adapter capabilities.
	 *
	 * @return array List of supported features.
	 */
	public function get_capabilities(): array;

	/**
	 * Store a document in the DMS.
	 *
	 * @param string $content      Document content.
	 * @param array  $metadata     Document metadata.
	 * @param string $content_type MIME type of the content.
	 *
	 * @return array|WP_Error Document info or error.
	 */
	public function store( string $content, array $metadata, string $content_type = 'text/html' );

	/**
	 * Retrieve a document from the DMS.
	 *
	 * @param string $document_id Document identifier.
	 *
	 * @return array|WP_Error Document data or error.
	 */
	public function retrieve( string $document_id );

	/**
	 * Update an existing document.
	 *
	 * @param string $document_id Document identifier.
	 * @param string $content     Updated content.
	 * @param array  $metadata    Updated metadata.
	 *
	 * @return array|WP_Error Updated document info or error.
	 */
	public function update( string $document_id, string $content, array $metadata );

	/**
	 * Delete a document from the DMS.
	 *
	 * @param string $document_id Document identifier.
	 * @param bool   $permanent   Whether to permanently delete.
	 *
	 * @return bool|WP_Error True on success or error.
	 */
	public function delete( string $document_id, bool $permanent = false );

	/**
	 * List documents with optional filtering.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array|WP_Error List of documents or error.
	 */
	public function list_documents( array $args = [] );

	/**
	 * Get a signed/temporary URL for document download.
	 *
	 * @param string $document_id Document identifier.
	 * @param int    $expiry      URL expiry time in seconds.
	 *
	 * @return string|WP_Error Signed URL or error.
	 */
	public function get_download_url( string $document_id, int $expiry = 3600 );

	/**
	 * Generate a PDF version of the document.
	 *
	 * @param string $document_id Document identifier.
	 * @param array  $options     PDF generation options.
	 *
	 * @return string|WP_Error Path to PDF or error.
	 */
	public function generate_pdf( string $document_id, array $options = [] );

	/**
	 * Get document version history.
	 *
	 * @param string $document_id Document identifier.
	 *
	 * @return array|WP_Error List of versions or error.
	 */
	public function get_versions( string $document_id );

	/**
	 * Create a new version of a document.
	 *
	 * @param string $document_id Document identifier.
	 * @param string $content     New version content.
	 * @param string $comment     Version comment.
	 *
	 * @return array|WP_Error Version info or error.
	 */
	public function create_version( string $document_id, string $content, string $comment = '' );

	/**
	 * Restore a previous version.
	 *
	 * @param string $document_id Document identifier.
	 * @param int    $version_id  Version to restore.
	 *
	 * @return bool|WP_Error True on success or error.
	 */
	public function restore_version( string $document_id, int $version_id );
}
