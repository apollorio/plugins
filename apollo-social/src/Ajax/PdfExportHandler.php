<?php
declare(strict_types=1);
/**
 * Apollo PDF Export Handler
 *
 * Handles AJAX requests for PDF generation from the document editor.
 *
 * @package Apollo\Ajax
 * @since 1.0.0
 */

namespace Apollo\Ajax;

// Load PdfGenerator if not already loaded
if ( ! class_exists( 'Apollo\\Modules\\Documents\\PdfGenerator' ) ) {
	$pdf_generator_path = dirname( __DIR__ ) . '/Modules/Documents/PdfGenerator.php';
	if ( file_exists( $pdf_generator_path ) ) {
		require_once $pdf_generator_path;
	}
}

use Apollo\Modules\Documents\PdfGenerator;

/**
 * PDF Export AJAX Handler
 */
class PdfExportHandler {

	/** @var string AJAX action name */
	private const ACTION = 'apollo_export_pdf';

	/** @var string Nonce action */
	private const NONCE_ACTION = 'apollo_editor_image_upload';

	/**
	 * Constructor - registers AJAX handlers
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handleExport' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handleUnauthorized' ) );
	}

	/**
	 * Static initializer (alternative)
	 */
	public static function init(): void {
		new self();
	}

	/**
	 * Handle PDF export request
	 */
	public function handleExport(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
			self::NONCE_ACTION
		) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Token de segurança inválido.', 'apollo-social' ),
				),
				403
			);
		}

		// Check user capability
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Você não tem permissão para esta ação.', 'apollo-social' ),
				),
				403
			);
		}

		// Get parameters
		$document_id = isset( $_POST['document_id'] ) ? intval( $_POST['document_id'] ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'Documento';
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( empty( $content ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Conteúdo do documento está vazio.', 'apollo-social' ),
				),
				400
			);
		}

		// Initialize PDF Generator
		$pdf_generator = new PdfGenerator();

		// Check available libraries
		$libraries = $pdf_generator->getAvailableLibraries();

		if ( empty( $libraries ) ) {
			// Return HTML download link as fallback
			$html_url = self::saveAsHtml( $content, $title );

			wp_send_json_success(
				array(
					'pdf_url'  => null,
					'html_url' => $html_url,
					'message'  => __( 'Nenhuma biblioteca PDF disponível. Use a versão HTML.', 'apollo-social' ),
					'fallback' => true,
				)
			);
		}

		// Generate filename
		$filename  = sanitize_file_name( $title ) ?: 'documento';
		$filename .= '_' . date( 'Ymd_His' );

		// Prepare HTML with styles
		$styled_html = self::prepareStyledHtml( $content, $title );

		// Generate PDF
		$pdf_path = $pdf_generator->generateFromHtml(
			$styled_html,
			$filename,
			array(
				'title'  => $title,
				'author' => wp_get_current_user()->display_name,
			)
		);

		if ( ! $pdf_path ) {
			wp_send_json_error(
				array(
					'message' => $pdf_generator->getLastError() ?: __( 'Erro ao gerar PDF.', 'apollo-social' ),
				),
				500
			);
		}

		// Get URL
		$pdf_url = $pdf_generator->getUrl( $pdf_path );

		// Update document record if exists
		if ( $document_id > 0 ) {
			global $wpdb;
			$table = $wpdb->prefix . 'apollo_documents';

			$relative_path = str_replace( ABSPATH, '/', $pdf_path );

			$wpdb->update(
				$table,
				array(
					'pdf_path' => $relative_path,
					'status'   => 'ready',
				),
				array( 'id' => $document_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		wp_send_json_success(
			array(
				'pdf_url'  => $pdf_url,
				'pdf_path' => $pdf_path,
				'library'  => $pdf_generator->getLibraryUsed(),
				'message'  => __( 'PDF gerado com sucesso!', 'apollo-social' ),
			)
		);
	}

	/**
	 * Handle unauthorized requests
	 */
	public function handleUnauthorized(): void {
		wp_send_json_error(
			array(
				'message' => __( 'Você precisa estar logado para exportar PDFs.', 'apollo-social' ),
			),
			401
		);
	}

	/**
	 * Save content as HTML file (fallback)
	 *
	 * @param string $content HTML content
	 * @param string $title Document title
	 * @return string URL to HTML file
	 */
	private static function saveAsHtml( string $content, string $title ): string {
		$upload_dir = wp_upload_dir();
		$output_dir = $upload_dir['basedir'] . '/apollo-documents/html/';

		if ( ! file_exists( $output_dir ) ) {
			wp_mkdir_p( $output_dir );
		}

		$filename = sanitize_file_name( $title ) . '_' . date( 'Ymd_His' ) . '.html';
		$filepath = $output_dir . $filename;

		$full_html = self::prepareStyledHtml( $content, $title );

		file_put_contents( $filepath, $full_html );

		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $filepath );
	}

	/**
	 * Prepare HTML with embedded styles for PDF/HTML export
	 *
	 * @param string $content Raw HTML content
	 * @param string $title Document title
	 * @return string Complete styled HTML
	 */
	private static function prepareStyledHtml( string $content, string $title ): string {
		$date   = date_i18n( 'd/m/Y H:i' );
		$author = wp_get_current_user()->display_name;

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 0;
        }
        
        .document-wrapper {
            max-width: 180mm;
            margin: 0 auto;
        }
        
        .document-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15pt;
            margin-bottom: 25pt;
        }
        
        .document-header h1 {
            font-size: 22pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8pt 0;
        }
        
        .document-meta {
            font-size: 10pt;
            color: #64748b;
        }
        
        .document-content {
            min-height: 200mm;
        }
        
        .document-content h1 {
            font-size: 24pt;
            font-weight: 700;
            margin: 0 0 16pt 0;
            color: #1a1a1a;
        }
        
        .document-content h2 {
            font-size: 18pt;
            font-weight: 600;
            margin: 20pt 0 12pt 0;
            color: #333;
        }
        
        .document-content h3 {
            font-size: 14pt;
            font-weight: 600;
            margin: 16pt 0 8pt 0;
            color: #444;
        }
        
        .document-content p {
            margin: 0 0 12pt 0;
            text-align: justify;
        }
        
        .document-content ul, 
        .document-content ol {
            margin: 0 0 12pt 20pt;
            padding: 0;
        }
        
        .document-content li {
            margin-bottom: 6pt;
        }
        
        .document-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16pt 0;
        }
        
        .document-content th,
        .document-content td {
            border: 1px solid #d1d5db;
            padding: 8pt 10pt;
            text-align: left;
        }
        
        .document-content th {
            background: #f3f4f6;
            font-weight: 600;
        }
        
        .document-content img {
            max-width: 100%;
            height: auto;
        }
        
        .document-content strong {
            font-weight: 600;
        }
        
        .document-content em {
            font-style: italic;
        }
        
        .document-content u {
            text-decoration: underline;
        }
        
        .document-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 15pt;
            margin-top: 30pt;
            font-size: 9pt;
            color: #9ca3af;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 50pt;
            padding-top: 20pt;
            border-top: 1px dashed #d1d5db;
        }
        
        .signature-block {
            display: inline-block;
            width: 45%;
            margin: 30pt 2% 0 2%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 50pt;
            margin-bottom: 8pt;
        }
        
        .signature-label {
            font-size: 10pt;
            color: #666;
        }
        
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="document-wrapper">
        <div class="document-header">
            <h1>{$title}</h1>
            <div class="document-meta">
                Gerado em {$date} por {$author} · Apollo Social
            </div>
        </div>
        
        <div class="document-content">
            {$content}
        </div>
        
        <div class="document-footer">
            Documento gerado pelo sistema Apollo Social<br>
            Este documento possui validade conforme Lei 14.063/2020
        </div>
    </div>
</body>
</html>
HTML;
	}
}

// Initialize the handler when file is loaded
new PdfExportHandler();
