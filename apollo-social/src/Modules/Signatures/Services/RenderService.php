<?php
/**
 * Render Service.
 *
 * @package Apollo\Modules\Signatures\Services
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 * phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
 */

namespace Apollo\Modules\Signatures\Services;

use Apollo\Modules\Signatures\Models\DocumentTemplate;

/**
 * Document Render Service
 *
 * Generates PDF documents from templates with data payload
 *
 * @since 1.0.0
 */
class RenderService {

	/** @var string */
	private $temp_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->temp_dir = wp_upload_dir()['basedir'] . '/apollo-signatures/temp/';

		// Ensure temp directory exists
		if ( ! file_exists( $this->temp_dir ) ) {
			wp_mkdir_p( $this->temp_dir );
		}
	}

	/**
	 * Render template to PDF
	 *
	 * @param DocumentTemplate $template
	 * @param array            $data
	 * @param array            $options
	 * @return string|false PDF file path or false on error
	 */
	public function renderToPdf( DocumentTemplate $template, array $data, array $options = [] ): string|false {
		try {
			// Validate template data
			$validation_errors = $template->validateData( $data );
			if ( ! empty( $validation_errors ) ) {
				throw new \Exception( 'Dados inválidos: ' . implode( ', ', $validation_errors ) );
			}

			// Render HTML content
			$html_content = $this->renderToHtml( $template, $data, $options );

			// Generate PDF
			$pdf_path = $this->generatePdf( $html_content, $options );

			return $pdf_path;

		} catch ( \Exception $e ) {
			error_log( 'Apollo Signatures RenderService Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Render template to HTML
	 *
	 * @param DocumentTemplate $template
	 * @param array            $data
	 * @param array            $options
	 * @return string
	 */
	public function renderToHtml( DocumentTemplate $template, array $data, array $options = [] ): string {
		// Render template content
		$content = $template->render( $data );

		// Apply formatting and styles
		$html = $this->buildHtmlDocument( $content, $options );

		return $html;
	}

	/**
	 * Build complete HTML document
	 *
	 * @param string $content
	 * @param array  $options
	 * @return string
	 */
	private function buildHtmlDocument( string $content, array $options = [] ): string {
		$title  = $options['title'] ?? 'Documento Apollo';
		$styles = $this->getDefaultStyles( $options );

		$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>{$styles}</style>
</head>
<body>
    <div class="document-container">
        <header class="document-header">
            {$this->renderHeader($options)}
        </header>

        <main class="document-content">
            {$content}
        </main>

        <footer class="document-footer">
            {$this->renderFooter($options)}
        </footer>
    </div>
</body>
</html>
HTML;

		return $html;
	}

	/**
	 * Get default CSS styles
	 *
	 * @param array $options
	 * @return string
	 */
	private function getDefaultStyles( array $options = [] ): string {
		$font_family = $options['font_family'] ?? 'Arial, sans-serif';
		$font_size   = $options['font_size'] ?? '12px';
		$line_height = $options['line_height'] ?? '1.6';

		return <<<CSS
body {
    margin: 0;
    padding: 20px;
    font-family: {$font_family};
    font-size: {$font_size};
    line-height: {$line_height};
    color: #333;
}

.document-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
}

.document-header {
    border-bottom: 2px solid #0073aa;
    margin-bottom: 30px;
    padding-bottom: 20px;
}

.document-content {
    margin-bottom: 40px;
}

.document-footer {
    border-top: 1px solid #ddd;
    padding-top: 20px;
    font-size: 10px;
    color: #666;
    text-align: center;
}

h1, h2, h3, h4, h5, h6 {
    color: #0073aa;
    margin-top: 30px;
    margin-bottom: 15px;
}

h1 { font-size: 24px; }
h2 { font-size: 20px; }
h3 { font-size: 16px; }

p {
    margin-bottom: 15px;
    text-align: justify;
}

.signature-block {
    margin-top: 60px;
    padding: 20px;
    border: 1px solid #ddd;
    background: #f9f9f9;
}

.signature-line {
    border-bottom: 1px solid #333;
    margin: 40px 0 10px 0;
    height: 1px;
}

.signature-label {
    text-align: center;
    font-size: 10px;
    color: #666;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.text-bold { font-weight: bold; }

@media print {
    body { print-color-adjust: exact; }
    .document-container { box-shadow: none; }
}
CSS;
	}

	/**
	 * Render document header
	 *
	 * @param array $options
	 * @return string
	 */
	private function renderHeader( array $options = [] ): string {
		if ( empty( $options['header'] ) ) {
			$date = date( 'd/m/Y' );
			return <<<HTML
<div class="header-content">
    <h1>Apollo Social</h1>
    <p>Documento gerado em {$date}</p>
</div>
HTML;
		}

		return $options['header'];
	}

	/**
	 * Render document footer
	 *
	 * @param array $options
	 * @return string
	 */
	private function renderFooter( array $options = [] ): string {
		if ( empty( $options['footer'] ) ) {
			$timestamp = date( 'd/m/Y H:i:s' );
			return <<<HTML
<div class="footer-content">
    <p>Documento gerado pelo sistema Apollo Social em {$timestamp}</p>
    <p>Este documento possui validade jurídica conforme Lei 14.063/2020</p>
</div>
HTML;
		}

		return $options['footer'];
	}

	/**
	 * Generate PDF from HTML using available libraries
	 *
	 * @param string $html
	 * @param array  $options
	 * @return string PDF file path
	 * @throws \Exception
	 */
	private function generatePdf( string $html, array $options = [] ): string {
		$filename    = $options['filename'] ?? 'document_' . uniqid() . '.pdf';
		$output_path = $this->temp_dir . $filename;

		// Try different PDF generation methods
		if ( $this->tryTcpdf( $html, $output_path, $options ) ) {
			return $output_path;
		}

		if ( $this->tryMpdf( $html, $output_path, $options ) ) {
			return $output_path;
		}

		if ( $this->tryWkhtmltopdf( $html, $output_path, $options ) ) {
			return $output_path;
		}

		// Fallback: save as HTML with PDF extension
		file_put_contents( $output_path . '.html', $html );
		throw new \Exception( 'Nenhuma biblioteca PDF disponível. HTML salvo como fallback.' );
	}

	/**
	 * Try generating PDF with TCPDF
	 *
	 * @param string $html
	 * @param string $output_path
	 * @param array  $options
	 * @return bool
	 */
	private function tryTcpdf( string $html, string $output_path, array $options = [] ): bool {
		if ( ! class_exists( 'TCPDF' ) ) {
			return false;
		}

		try {
			$pdf = new \TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );

			// Set document information
			$pdf->SetCreator( 'Apollo Social' );
			$pdf->SetTitle( $options['title'] ?? 'Documento' );

			// Set margins
			$pdf->SetMargins( 15, 20, 15 );
			$pdf->SetAutoPageBreak( true, 20 );

			// Add page
			$pdf->AddPage();

			// Write HTML
			$pdf->writeHTML( $html, true, false, true, false, '' );

			// Output file
			$pdf->Output( $output_path, 'F' );

			return file_exists( $output_path );

		} catch ( \Exception $e ) {
			return false;
		}//end try
	}

	/**
	 * Try generating PDF with mPDF
	 *
	 * @param string $html
	 * @param string $output_path
	 * @param array  $options
	 * @return bool
	 */
	private function tryMpdf( string $html, string $output_path, array $options = [] ): bool {
		if ( ! class_exists( 'Mpdf\Mpdf' ) ) {
			return false;
		}

		try {
			$mpdf = new \Mpdf\Mpdf(
				[
					'mode'          => 'utf-8',
					'format'        => 'A4',
					'margin_left'   => 15,
					'margin_right'  => 15,
					'margin_top'    => 20,
					'margin_bottom' => 20,
				]
			);

			$mpdf->WriteHTML( $html );
			$mpdf->Output( $output_path, 'F' );

			return file_exists( $output_path );

		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Try generating PDF with wkhtmltopdf
	 *
	 * @param string $html
	 * @param string $output_path
	 * @param array  $options
	 * @return bool
	 */
	private function tryWkhtmltopdf( string $html, string $output_path, array $options = [] ): bool {
		// Check if wkhtmltopdf is available
		$wkhtmltopdf_path = $this->findWkhtmltopdf();
		if ( ! $wkhtmltopdf_path ) {
			return false;
		}

		try {
			// Save HTML to temp file
			$html_file = $this->temp_dir . 'temp_' . uniqid() . '.html';
			file_put_contents( $html_file, $html );

			// Build command
			$command = sprintf(
				'%s --page-size A4 --margin-top 20mm --margin-bottom 20mm --margin-left 15mm --margin-right 15mm %s %s',
				escapeshellarg( $wkhtmltopdf_path ),
				escapeshellarg( $html_file ),
				escapeshellarg( $output_path )
			);

			// Execute command
			exec( $command, $output, $return_code );

			// Clean up temp HTML
			unlink( $html_file );

			return $return_code === 0 && file_exists( $output_path );

		} catch ( \Exception $e ) {
			return false;
		}//end try
	}

	/**
	 * Find wkhtmltopdf executable
	 *
	 * @return string|null
	 */
	private function findWkhtmltopdf(): ?string {
		$paths = [
			'/usr/bin/wkhtmltopdf',
			'/usr/loc/bin/wkhtmltopdf',
			'wkhtmltopdf',
		// System PATH
		];

		foreach ( $paths as $path ) {
			if ( is_executable( $path ) || exec( "which $path" ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Get document hash for integrity verification
	 *
	 * @param string $file_path
	 * @return string
	 */
	public function getDocumentHash( string $file_path ): string {
		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		return hash_file( 'sha256', $file_path );
	}

	/**
	 * Clean up temporary files
	 *
	 * @param int $max_age_hours
	 */
	public function cleanupTempFiles( int $max_age_hours = 24 ): void {
		if ( ! is_dir( $this->temp_dir ) ) {
			return;
		}

		$files   = glob( $this->temp_dir . '*' );
		$max_age = time() - ( $max_age_hours * 3600 );

		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $max_age ) {
				unlink( $file );
			}
		}
	}
}
