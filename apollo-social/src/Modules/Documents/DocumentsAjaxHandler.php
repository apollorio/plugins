<?php
/**
 * Apollo Documents AJAX Handlers
 *
 * Handles AJAX requests for document operations:
 * - Save document (auto-save and manual).
 * - Export to PDF.
 * - Prepare for signing.
 *
 * @package Apollo\Modules\Documents
 * @since   1.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 */

declare( strict_types=1 );

namespace Apollo\Modules\Documents;

/**
 * Documents AJAX Handler Class.
 */
class DocumentsAjaxHandler {

	/**
	 * Document libraries instance.
	 *
	 * @var DocumentLibraries
	 */
	private DocumentLibraries $libraries;

	/**
	 * PDF generator instance.
	 *
	 * @var PdfGenerator
	 */
	private PdfGenerator $pdf_generator;

	/**
	 * HTML render service instance.
	 *
	 * @var HtmlRenderService
	 */
	private HtmlRenderService $html_renderer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->libraries     = new DocumentLibraries();
		$this->pdf_generator = new PdfGenerator();
		$this->html_renderer = new HtmlRenderService();
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Save document.
		add_action( 'wp_ajax_apollo_save_document', array( $this, 'handle_save_document' ) );

		// Export PDF.
		add_action( 'wp_ajax_apollo_export_document_pdf', array( $this, 'handle_export_pdf' ) );

		// Prepare for signing.
		add_action( 'wp_ajax_apollo_prepare_document_signing', array( $this, 'handle_prepare_for_signing' ) );
	}

	/**
	 * Handle document save (create or update).
	 *
	 * @return void
	 */
	public function handle_save_document(): void {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'apollo_document_editor' ) ) {
			wp_send_json_error( array( 'message' => 'Sessão expirada. Recarregue a página.' ) );
		}

		// Check user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Você precisa estar logado.' ) );
		}

		$document_id   = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;
		$document_type = isset( $_POST['document_type'] ) ? sanitize_text_field( wp_unslash( $_POST['document_type'] ) ) : 'documento';
		$title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'Sem título';
		$content       = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		$user_id = get_current_user_id();
		$is_new  = ( 0 === $document_id );

		if ( $is_new ) {
			// Create new document.
			$result = $this->libraries->createDocument(
				array(
					'library_type' => 'private',
					'type'         => $document_type,
					'title'        => $title,
					'content'      => $content,
					'status'       => 'draft',
				),
				$user_id
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success(
				array(
					'document_id' => $result['id'],
					'file_id'     => $result['file_id'],
					'is_new'      => true,
					'message'     => 'Documento criado com sucesso.',
				)
			);
		} else {
			// Update existing document.
			$document = $this->libraries->getDocument( $document_id );

			if ( ! $document ) {
				wp_send_json_error( array( 'message' => 'Documento não encontrado.' ) );
			}

			// Check ownership.
			if ( (int) $document['created_by'] !== $user_id && ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Você não tem permissão para editar este documento.' ) );
			}

			$result = $this->libraries->updateDocument(
				$document_id,
				array(
					'title'   => $title,
					'content' => $content,
				),
				$user_id
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success(
				array(
					'document_id' => $document_id,
					'file_id'     => $document['file_id'],
					'is_new'      => false,
					'message'     => 'Documento salvo.',
				)
			);
		}//end if
	}

	/**
	 * Handle PDF export.
	 *
	 * @return void
	 */
	public function handle_export_pdf(): void {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'apollo_document_editor' ) ) {
			wp_send_json_error( array( 'message' => 'Sessão expirada. Recarregue a página.' ) );
		}

		// Check user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Você precisa estar logado.' ) );
		}

		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'Documento';
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		// If document exists, get from database.
		if ( $document_id > 0 ) {
			$document = $this->libraries->getDocument( $document_id );

			if ( ! $document ) {
				wp_send_json_error( array( 'message' => 'Documento não encontrado.' ) );
			}

			$title   = isset( $document['title'] ) ? $document['title'] : $title;
			$content = isset( $document['content'] ) ? $document['content'] : $content;
		}

		// Render HTML with proper styling.
		$body_html = '<div class="apollo-document">' . $content . '</div>';

		$current_user = wp_get_current_user();
		$author_name  = $current_user->display_name ? $current_user->display_name : 'Apollo Social';

		$full_html = $this->html_renderer->renderFullDocument(
			$body_html,
			array(
				'title'      => $title,
				'author'     => $author_name,
				'showHeader' => true,
				'showFooter' => true,
			)
		);

		// Generate filename.
		$filename = sanitize_file_name( $title );
		$filename = $filename ? $filename : 'documento';
		$filename = substr( $filename, 0, 50 );

		// Generate PDF.
		$pdf_path = $this->pdf_generator->generateFromHtml(
			$full_html,
			$filename,
			array(
				'title'  => $title,
				'author' => $author_name,
			)
		);

		if ( ! $pdf_path ) {
			$error = $this->pdf_generator->getLastError();
			$error = $error ? $error : 'Erro desconhecido ao gerar PDF.';
			wp_send_json_error( array( 'message' => $error ) );
		}

		// Get URL for download.
		$pdf_url = $this->pdf_generator->getUrl( $pdf_path );

		// Update document with PDF path if it exists.
		if ( $document_id > 0 ) {
			$this->libraries->updateDocument(
				$document_id,
				array( 'pdf_path' => str_replace( ABSPATH, '/', $pdf_path ) ),
				get_current_user_id()
			);
		}

		wp_send_json_success(
			array(
				'pdf_url'  => $pdf_url,
				'pdf_path' => $pdf_path,
				'library'  => $this->pdf_generator->getLibraryUsed(),
				'message'  => 'PDF gerado com sucesso.',
			)
		);
	}

	/**
	 * Handle prepare for signing.
	 *
	 * @return void
	 */
	public function handle_prepare_for_signing(): void {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'apollo_document_editor' ) ) {
			wp_send_json_error( array( 'message' => 'Sessão expirada. Recarregue a página.' ) );
		}

		// Check user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Você precisa estar logado.' ) );
		}

		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;

		if ( 0 === $document_id ) {
			wp_send_json_error( array( 'message' => 'ID do documento inválido.' ) );
		}

		$document = $this->libraries->getDocument( $document_id );

		if ( ! $document ) {
			wp_send_json_error( array( 'message' => 'Documento não encontrado.' ) );
		}

		$user_id = get_current_user_id();

		// Check ownership.
		if ( (int) $document['created_by'] !== $user_id && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para preparar este documento.' ) );
		}

		// Generate PDF first.
		$title     = isset( $document['title'] ) ? $document['title'] : 'Documento';
		$content   = isset( $document['content'] ) ? $document['content'] : '';
		$body_html = '<div class="apollo-document">' . $content . '</div>';

		$current_user = wp_get_current_user();
		$author_name  = $current_user->display_name ? $current_user->display_name : 'Apollo Social';

		$full_html = $this->html_renderer->renderFullDocument(
			$body_html,
			array(
				'title'      => $title,
				'author'     => $author_name,
				'showHeader' => true,
				'showFooter' => true,
			)
		);

		// Add signature blocks.
		$full_html = $this->html_renderer->addSignatureBlocks( $full_html );

		$filename = sanitize_file_name( $title );
		$filename = $filename ? $filename : 'documento';
		$pdf_path = $this->pdf_generator->generateFromHtml(
			$full_html,
			$filename . '_signing',
			array(
				'title'  => $title,
				'author' => $author_name,
			)
		);

		if ( ! $pdf_path ) {
			$error = $this->pdf_generator->getLastError();
			$error = $error ? $error : 'Erro ao gerar PDF.';
			wp_send_json_error( array( 'message' => $error ) );
		}

		// Finalize document for signing.
		$result = $this->libraries->finalizeDocument( $document_id, $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update with PDF path.
		$this->libraries->updateDocument(
			$document_id,
			array(
				'pdf_path' => str_replace( ABSPATH, '/', $pdf_path ),
				'status'   => 'ready',
			),
			$user_id
		);

		wp_send_json_success(
			array(
				'sign_url' => site_url( '/sign/' . $document['file_id'] ),
				'pdf_url'  => $this->pdf_generator->getUrl( $pdf_path ),
				'message'  => 'Documento preparado para assinatura.',
			)
		);
	}
}
