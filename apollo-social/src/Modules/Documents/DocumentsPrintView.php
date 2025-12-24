<?php

/**
 * Apollo Documents - Print View Renderer
 *
 * Renders a deterministic HTML "print view" of a document for PDF generation.
 * This view is optimized for PDF engines (minimal JS, inline CSS, no animations).
 *
 * @package Apollo\Modules\Documents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Class DocumentsPrintView
 *
 * Generates print-ready HTML from document data.
 */
class DocumentsPrintView {

	/**
	 * Render print view for a document
	 *
	 * @param int $doc_id Document post ID.
	 * @return string Full HTML string for print/PDF.
	 */
	public static function render( int $doc_id ): string {
		$post = get_post( $doc_id );

		if ( ! $post || $post->post_type !== 'apollo_document' ) {
			return '';
		}

		// Get document content (prefer meta, fallback to post_content)
		$body_html = get_post_meta( $doc_id, '_apollo_doc_body_html', true );
		if ( empty( $body_html ) ) {
			$body_html = $post->post_content;
		}

		// Sanitize HTML
		$body_html = wp_kses_post( $body_html );

		// Get document title
		$title = get_post_meta( $doc_id, '_apollo_doc_title', true );
		if ( empty( $title ) ) {
			$title = $post->post_title;
		}
		$title = esc_html( $title );

		// Get author
		$author      = get_userdata( $post->post_author );
		$author_name = $author ? $author->display_name : __( 'Usuário', 'apollo-social' );

		// Get document date
		$date = get_post_meta( $doc_id, '_apollo_doc_pdf_generated', true );
		if ( empty( $date ) ) {
			$date = $post->post_modified;
		}
		$formatted_date = date_i18n( 'd/m/Y H:i', strtotime( $date ) );

		// Get version
		$version      = get_post_meta( $doc_id, '_apollo_doc_version', true );
		$version_text = $version ? sprintf( __( 'Versão %d', 'apollo-social' ), absint( $version ) ) : '';

		// Build print view HTML
		$html = self::build_print_html( $title, $body_html, $author_name, $formatted_date, $version_text );

		// Append signature block if document has signatures
		if ( class_exists( 'Apollo\\Modules\\Documents\\DocumentsPdfSignatureBlock' ) ) {
			$html = DocumentsPdfSignatureBlock::append_to_print_view( $doc_id, $html );
		}

		return $html;
	}

	/**
	 * Build complete print HTML document
	 *
	 * @param string $title Document title.
	 * @param string $body_html Document body HTML.
	 * @param string $author_name Author name.
	 * @param string $date Formatted date.
	 * @param string $version Version text.
	 * @return string Complete HTML document.
	 */
	private static function build_print_html( string $title, string $body_html, string $author_name, string $date, string $version ): string {
		$logo_url = self::get_logo_url();

		$html = <<<HTML
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
            color: #1a1a1a;
            background: #fff;
        }

        .print-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15pt;
            margin-bottom: 25pt;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .print-logo {
            max-height: 40pt;
            max-width: 150pt;
        }

        .print-title-section {
            flex: 1;
            margin-left: 20pt;
        }

        .print-title {
            font-size: 22pt;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8pt 0;
        }

        .print-meta {
            font-size: 10pt;
            color: #64748b;
        }

        .print-content {
            min-height: 200mm;
        }

        .print-content h1 {
            font-size: 24pt;
            font-weight: 700;
            margin: 0 0 16pt 0;
            color: #1a1a1a;
        }

        .print-content h2 {
            font-size: 18pt;
            font-weight: 600;
            margin: 20pt 0 12pt 0;
            color: #333;
        }

        .print-content h3 {
            font-size: 14pt;
            font-weight: 600;
            margin: 16pt 0 8pt 0;
            color: #444;
        }

        .print-content p {
            margin: 0 0 12pt 0;
            text-align: justify;
        }

        .print-content ul,
        .print-content ol {
            margin: 0 0 12pt 20pt;
            padding: 0;
        }

        .print-content li {
            margin-bottom: 6pt;
        }

        .print-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16pt 0;
        }

        .print-content th,
        .print-content td {
            border: 1px solid #d1d5db;
            padding: 8pt 10pt;
            text-align: left;
        }

        .print-content th {
            background: #f3f4f6;
            font-weight: 600;
        }

        .print-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 12pt auto;
        }

        .print-content strong {
            font-weight: 600;
        }

        .print-content em {
            font-style: italic;
        }

        .print-content u {
            text-decoration: underline;
        }

        .print-footer {
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
    <div class="print-header">
        {$logo_url}
        <div class="print-title-section">
            <h1 class="print-title">{$title}</h1>
            <div class="print-meta">
                {$date} · {$author_name}
                {$version}
            </div>
        </div>
    </div>

    <div class="print-content">
        {$body_html}
    </div>

    <div class="print-footer">
        Documento gerado pelo sistema Apollo Social<br>
        Este documento possui validade conforme Lei 14.063/2020
    </div>
</body>
</html>
HTML;

		return $html;
	}

	/**
	 * Get Apollo logo URL for print view
	 *
	 * @return string HTML img tag or empty string.
	 */
	private static function get_logo_url(): string {
		// Try to get logo from theme or plugin
		$logo_paths = array(
			get_template_directory() . '/assets/images/apollo-logo.png',
			get_template_directory() . '/assets/apollo-logo.png',
			APOLLO_SOCIAL_PLUGIN_DIR . 'assets/images/apollo-logo.png',
		);

		foreach ( $logo_paths as $path ) {
			if ( file_exists( $path ) ) {
				$upload_dir = wp_upload_dir();
				$url        = str_replace( ABSPATH, home_url( '/' ), $path );

				return '<img src="' . esc_url( $url ) . '" alt="Apollo" class="print-logo">';
			}
		}

		// Fallback: text logo
		return '<div class="print-logo" style="font-size: 18pt; font-weight: 700; color: #0f172a;">APOLLO</div>';
	}
}
