<?php

/**
 * Apollo Documents - PDF Service
 *
 * Service class for generating PDFs from documents.
 * Integrates PdfGenerator with the apollo_document CPT.
 *
 * @package Apollo\Modules\Documents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Class DocumentsPdfService
 *
 * Handles PDF generation for documents.
 */
class DocumentsPdfService {

	/**
	 * Generate PDF for a document
	 *
	 * @param int $doc_id Document post ID.
	 * @return array|WP_Error Array with 'success', 'pdf_path', 'pdf_url', 'attachment_id' or WP_Error.
	 */
	public static function generate_pdf( int $doc_id ) {
		$post = get_post( $doc_id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return new \WP_Error(
				'doc_not_found',
				__( 'Documento não encontrado.', 'apollo-social' )
			);
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $doc_id ) ) {
			return new \WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para gerar PDF deste documento.', 'apollo-social' )
			);
		}

		// Get print view HTML
		$print_html = DocumentsPrintView::render( $doc_id );

		if ( empty( $print_html ) ) {
			return new \WP_Error(
				'empty_content',
				__( 'Documento está vazio. Adicione conteúdo antes de gerar PDF.', 'apollo-social' )
			);
		}

		// Initialize PDF generator
		$pdf_generator = new PdfGenerator();

		// Check if PDF library is available
		$libraries = $pdf_generator->getAvailableLibraries();
		if ( empty( $libraries ) ) {
			return new \WP_Error(
				'no_pdf_library',
				__( 'Nenhuma biblioteca PDF disponível. Instale mPDF, TCPDF ou Dompdf.', 'apollo-social' )
			);
		}

		// Get document title for filename
		$title = get_post_meta( $doc_id, '_apollo_doc_title', true );
		if ( empty( $title ) ) {
			$title = $post->post_title;
		}
		$filename  = sanitize_file_name( $title ) ?: 'documento';
		$filename .= '_' . $doc_id . '_' . date( 'Ymd_His' );

		// Get author for PDF metadata
		$author      = get_userdata( $post->post_author );
		$author_name = $author ? $author->display_name : 'Apollo Social';

		// Generate PDF
		$pdf_path = $pdf_generator->generateFromHtml(
			$print_html,
			$filename,
			array(
				'title'  => $title,
				'author' => $author_name,
			)
		);

		if ( ! $pdf_path ) {
			$error = $pdf_generator->getLastError();

			return new \WP_Error(
				'pdf_generation_failed',
				$error ?: __( 'Erro ao gerar PDF.', 'apollo-social' )
			);
		}

		// Create WordPress attachment
		$attachment_id = self::create_pdf_attachment( $pdf_path, $doc_id, $title );

		// Compute and store PDF hash
		$pdf_contents = file_get_contents( $pdf_path );
		$pdf_hash     = $pdf_contents ? hash( 'sha256', $pdf_contents ) : '';

		// Update document meta
		update_post_meta( $doc_id, '_apollo_doc_pdf_file', $attachment_id );
		update_post_meta( $doc_id, '_apollo_doc_pdf_generated', current_time( 'mysql' ) );
		update_post_meta( $doc_id, '_apollo_doc_library', $pdf_generator->getLibraryUsed() );
		update_post_meta( $doc_id, '_apollo_doc_pdf_hash', $pdf_hash );

		// Get PDF URL
		$pdf_url = $pdf_generator->getUrl( $pdf_path );

		return array(
			'success'       => true,
			'pdf_path'      => $pdf_path,
			'pdf_url'       => $pdf_url,
			'attachment_id' => $attachment_id,
			'library'       => $pdf_generator->getLibraryUsed(),
		);
	}

	/**
	 * Create WordPress attachment for PDF
	 *
	 * @param string $pdf_path Absolute path to PDF file.
	 * @param int    $doc_id Document post ID (parent).
	 * @param string $title Document title.
	 * @return int|false Attachment ID or false on failure.
	 */
	private static function create_pdf_attachment( string $pdf_path, int $doc_id, string $title ) {
		if ( ! file_exists( $pdf_path ) ) {
			return false;
		}

		$upload_dir    = wp_upload_dir();
		$relative_path = str_replace( $upload_dir['basedir'] . '/', '', $pdf_path );

		// Check if attachment already exists
		$existing = get_post_meta( $doc_id, '_apollo_doc_pdf_file', true );
		if ( $existing ) {
			$attachment = get_post( $existing );
			if ( $attachment && $attachment->post_type === 'attachment' ) {
				// Update existing attachment
				wp_update_post(
					array(
						'ID'           => $existing,
						'post_title'   => $title . ' (PDF)',
						'post_content' => '',
					)
				);

				return $existing;
			}
		}

		// Create new attachment
		$attachment = array(
			'post_mime_type' => 'application/pdf',
			'post_title'     => $title . ' (PDF)',
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_parent'    => $doc_id,
		);

		$attachment_id = wp_insert_attachment( $attachment, $relative_path, $doc_id );

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $pdf_path );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}

	/**
	 * Get PDF URL for a document
	 *
	 * @param int $doc_id Document post ID.
	 * @return string|null PDF URL or null if not generated.
	 */
	public static function get_pdf_url( int $doc_id ): ?string {
		$attachment_id = get_post_meta( $doc_id, '_apollo_doc_pdf_file', true );

		if ( ! $attachment_id ) {
			return null;
		}

		$url = wp_get_attachment_url( $attachment_id );

		return $url ?: null;
	}

	/**
	 * Check if PDF is available for a document
	 *
	 * @param int $doc_id Document post ID.
	 * @return bool True if PDF exists.
	 */
	public static function has_pdf( int $doc_id ): bool {
		$attachment_id = get_post_meta( $doc_id, '_apollo_doc_pdf_file', true );

		if ( ! $attachment_id ) {
			return false;
		}

		$url = wp_get_attachment_url( $attachment_id );

		return ! empty( $url );
	}

	/**
	 * Get PDF generation timestamp
	 *
	 * @param int $doc_id Document post ID.
	 * @return string|null Timestamp or null.
	 */
	public static function get_pdf_generated_time( int $doc_id ): ?string {
		$timestamp = get_post_meta( $doc_id, '_apollo_doc_pdf_generated', true );

		return $timestamp ?: null;
	}
}
