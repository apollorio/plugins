<?php

/**
 * Apollo PDF Generator.
 *
 * Generates PDF documents from HTML content using available libraries.
 * Supports: mPDF, TCPDF, Dompdf (in order of preference).
 *
 * @package Apollo\Modules\Documents
 * @since   1.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions
 * phpcs:disable WordPress.DB
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * PDF Generator Class
 *
 * Usage:
 * ```php
 * $pdf = new PdfGenerator();
 * $path = $pdf->generateFromHtml($html, 'my-document');
 * ```
 */
class PdfGenerator {

	/** @var string Output directory for PDFs */
	private string $output_dir;

	/** @var string Temp directory for processing */
	private string $temp_dir;

	/** @var array Default PDF options */
	private array $default_options = array(
		'format'                  => 'A4',
		'orientation'             => 'P',
		// Portrait
					'margin_left' => 15,
		'margin_right'            => 15,
		'margin_top'              => 20,
		'margin_bottom'           => 20,
		'font_family'             => 'DejaVu Sans',
		'font_size'               => 12,
		'title'                   => 'Documento Apollo',
		'author'                  => 'Apollo Social',
		'creator'                 => 'Apollo PDF Generator',
	);

	/** @var string|null Last error message */
	private ?string $last_error = null;

	/** @var string|null Library used for generation */
	private ?string $library_used = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$upload_dir       = wp_upload_dir();
		$this->output_dir = $upload_dir['basedir'] . '/apollo-documents/pdf/';
		$this->temp_dir   = $upload_dir['basedir'] . '/apollo-documents/temp/';

		$this->ensureDirectories();
	}

	/**
	 * Generate PDF from HTML content
	 *
	 * @param string $html HTML content to convert
	 * @param string $filename Output filename (without .pdf extension)
	 * @param array  $options PDF generation options
	 * @return string|false Path to generated PDF or false on failure
	 */
	public function generateFromHtml( string $html, string $filename = '', array $options = array() ): string|false {
		$this->last_error   = null;
		$this->library_used = null;

		// Merge options with defaults
		$options = array_merge( $this->default_options, $options );

		// Generate filename if not provided
		if ( empty( $filename ) ) {
			$filename = 'document_' . uniqid();
		}

		// Sanitize filename
		$filename    = sanitize_file_name( $filename );
		$output_path = $this->output_dir . $filename . '.pdf';

		// Wrap HTML in full document if needed
		$html = $this->prepareHtml( $html, $options );

		// Try each library in order of preference
		if ( $this->tryMpdf( $html, $output_path, $options ) ) {
			$this->library_used = 'mPDF';

			return $output_path;
		}

		if ( $this->tryTcpdf( $html, $output_path, $options ) ) {
			$this->library_used = 'TCPDF';

			return $output_path;
		}

		if ( $this->tryDompdf( $html, $output_path, $options ) ) {
			$this->library_used = 'Dompdf';

			return $output_path;
		}

		// Fallback: save as HTML for manual conversion
		$html_fallback = $this->output_dir . $filename . '.html';
		if ( file_put_contents( $html_fallback, $html ) !== false ) {
			$this->last_error = 'Nenhuma biblioteca PDF disponível. HTML salvo como fallback em: ' . $html_fallback;

			return false;
		}

		$this->last_error = 'Não foi possível gerar o PDF. Instale mPDF, TCPDF, ou Dompdf.';

		return false;
	}

	/**
	 * Generate PDF from document ID
	 *
	 * @param int $document_id Document ID from wp_apollo_documents
	 * @return string|false Path to generated PDF or false on failure
	 */
	public function generateFromDocument( int $document_id ): string|false {
		global $wpdb;

		$documents_table = $wpdb->prefix . 'apollo_documents';

		$document = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$documents_table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $document ) {
			$this->last_error = 'Documento não encontrado.';

			return false;
		}

		$options = array(
			'title' => $document['title'] ?? 'Documento',
		);

		$filename = $document['file_id'] ?? 'doc_' . $document_id;

		$pdf_path = $this->generateFromHtml( $document['content'] ?? '', $filename, $options );

		// Update document with PDF path
		if ( $pdf_path ) {
			$relative_path = str_replace( ABSPATH, '/', $pdf_path );
			$wpdb->update(
				$documents_table,
				array( 'pdf_path' => $relative_path ),
				array( 'id' => $document_id )
			);
		}

		return $pdf_path;
	}

	/**
	 * Prepare HTML for PDF conversion
	 *
	 * @param string $html Raw HTML content
	 * @param array  $options PDF options
	 * @return string Complete HTML document
	 */
	private function prepareHtml( string $html, array $options ): string {
		// Check if already complete HTML document
		if ( stripos( $html, '<!DOCTYPE' ) !== false || stripos( $html, '<html' ) !== false ) {
			return $html;
		}

		// Build complete HTML document
		$title       = esc_html( $options['title'] ?? 'Documento' );
		$font_family = esc_html( $options['font_family'] ?? 'DejaVu Sans' );
		$font_size   = intval( $options['font_size'] ?? 12 );

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        @page {
            margin: {$options['margin_top']}mm {$options['margin_right']}mm {$options['margin_bottom']}mm {$options['margin_left']}mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: '{$font_family}', 'DejaVu Sans', sans-serif;
            font-size: {$font_size}pt;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        h1 {
            font-size: 24pt;
            font-weight: 700;
            margin: 0 0 16pt 0;
            color: #1a1a1a;
        }

        h2 {
            font-size: 18pt;
            font-weight: 600;
            margin: 16pt 0 12pt 0;
            color: #333;
        }

        h3 {
            font-size: 14pt;
            font-weight: 600;
            margin: 12pt 0 8pt 0;
            color: #444;
        }

        p {
            margin: 0 0 12pt 0;
        }

        ul, ol {
            margin: 0 0 12pt 20pt;
            padding: 0;
        }

        li {
            margin-bottom: 4pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12pt 0;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8pt;
            text-align: left;
        }

        th {
            background: #f5f5f5;
            font-weight: 600;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10pt;
            margin-bottom: 20pt;
        }

        .footer {
            border-top: 1px solid #e0e0e0;
            padding-top: 10pt;
            margin-top: 20pt;
            font-size: 10pt;
            color: #666;
        }

        .signature-block {
            margin-top: 40pt;
            padding-top: 20pt;
            border-top: 1px dashed #ccc;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 40pt;
            margin: 20pt 0 5pt 0;
        }

        .signature-label {
            font-size: 10pt;
            color: #666;
        }
    </style>
</head>
<body>
{$html}
</body>
</html>
HTML;
	}

	/**
	 * Try generating PDF with mPDF
	 *
	 * @param string $html HTML content
	 * @param string $output_path Output file path
	 * @param array  $options PDF options
	 * @return bool Success
	 */
	private function tryMpdf( string $html, string $output_path, array $options ): bool {
		// Check if mPDF is available
		if ( ! class_exists( 'Mpdf\\Mpdf' ) ) {
			// Try autoloading from common locations
			// Use global constant with backslash prefix to access from namespace
			if ( defined( '\APOLLO_SOCIAL_PLUGIN_FILE' ) ) {
				$plugin_file = \APOLLO_SOCIAL_PLUGIN_FILE;
			} else {
				// Fallback: calculate path to main plugin file
				$plugin_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/apollo-social.php';
			}
			$autoload_paths = array(
				ABSPATH . 'vendor/autoload.php',
				WP_CONTENT_DIR . '/vendor/autoload.php',
				dirname( $plugin_file ) . '/vendor/autoload.php',
			);

			foreach ( $autoload_paths as $path ) {
				if ( file_exists( $path ) ) {
					require_once $path;

					break;
				}
			}

			if ( ! class_exists( 'Mpdf\\Mpdf' ) ) {
				return false;
			}
		}//end if

		try {
			$mpdf = new \Mpdf\Mpdf(
				array(
					'tempDir'       => $this->temp_dir,
					'mode'          => 'utf-8',
					'format'        => $options['format'] ?? 'A4',
					'orientation'   => $options['orientation'] ?? 'P',
					'margin_left'   => $options['margin_left'] ?? 15,
					'margin_right'  => $options['margin_right'] ?? 15,
					'margin_top'    => $options['margin_top'] ?? 20,
					'margin_bottom' => $options['margin_bottom'] ?? 20,
					'default_font'  => 'dejavusans',
				)
			);

			// Set document info
			$mpdf->SetTitle( $options['title'] ?? 'Documento' );
			$mpdf->SetAuthor( $options['author'] ?? 'Apollo Social' );
			$mpdf->SetCreator( $options['creator'] ?? 'Apollo PDF Generator' );

			// Write HTML
			$mpdf->WriteHTML( $html );

			// Output to file
			$mpdf->Output( $output_path, 'F' );

			return file_exists( $output_path );

		} catch ( \Exception $e ) {
			$this->last_error = 'mPDF Error: ' . $e->getMessage();
			error_log( '[Apollo PDF] mPDF Error: ' . $e->getMessage() );

			return false;
		}//end try
	}

	/**
	 * Try generating PDF with TCPDF
	 *
	 * @param string $html HTML content
	 * @param string $output_path Output file path
	 * @param array  $options PDF options
	 * @return bool Success
	 */
	private function tryTcpdf( string $html, string $output_path, array $options ): bool {
		if ( ! class_exists( 'TCPDF' ) ) {
			return false;
		}

		try {
			$orientation = ( $options['orientation'] ?? 'P' ) === 'P' ? 'P' : 'L';

			$pdf = new \TCPDF( $orientation, 'mm', $options['format'] ?? 'A4', true, 'UTF-8', false );

			// Set document info
			$pdf->SetCreator( $options['creator'] ?? 'Apollo PDF Generator' );
			$pdf->SetAuthor( $options['author'] ?? 'Apollo Social' );
			$pdf->SetTitle( $options['title'] ?? 'Documento' );

			// Set margins
			$pdf->SetMargins(
				$options['margin_left'] ?? 15,
				$options['margin_top'] ?? 20,
				$options['margin_right'] ?? 15
			);
			$pdf->SetAutoPageBreak( true, $options['margin_bottom'] ?? 20 );

			// Set font
			$pdf->SetFont( 'dejavusans', '', $options['font_size'] ?? 12 );

			// Add page
			$pdf->AddPage();

			// Write HTML
			$pdf->writeHTML( $html, true, false, true, false, '' );

			// Output file
			$pdf->Output( $output_path, 'F' );

			return file_exists( $output_path );

		} catch ( \Exception $e ) {
			$this->last_error = 'TCPDF Error: ' . $e->getMessage();
			error_log( '[Apollo PDF] TCPDF Error: ' . $e->getMessage() );

			return false;
		}//end try
	}

	/**
	 * Try generating PDF with Dompdf
	 *
	 * @param string $html HTML content
	 * @param string $output_path Output file path
	 * @param array  $options PDF options
	 * @return bool Success
	 */
	private function tryDompdf( string $html, string $output_path, array $options ): bool {
		if ( ! class_exists( 'Dompdf\\Dompdf' ) ) {
			return false;
		}

		try {
			$dompdf = new \Dompdf\Dompdf(
				array(
					'tempDir'                 => $this->temp_dir,
					'chroot'                  => ABSPATH,
					'isRemoteEnabled'         => true,
					'isHtml5ParserEnabled'    => true,
					'isFontSubsettingEnabled' => true,
				)
			);

			// Load HTML
			$dompdf->loadHtml( $html );

			// Set paper size
			$format      = $options['format'] ?? 'A4';
			$orientation = ( $options['orientation'] ?? 'P' ) === 'P' ? 'portrait' : 'landscape';
			$dompdf->setPaper( $format, $orientation );

			// Render
			$dompdf->render();

			// Save to file
			$output = $dompdf->output();
			$result = file_put_contents( $output_path, $output );

			return $result !== false && file_exists( $output_path );

		} catch ( \Exception $e ) {
			$this->last_error = 'Dompdf Error: ' . $e->getMessage();
			error_log( '[Apollo PDF] Dompdf Error: ' . $e->getMessage() );

			return false;
		}//end try
	}

	/**
	 * Ensure output directories exist
	 */
	private function ensureDirectories(): void {
		if ( ! file_exists( $this->output_dir ) ) {
			wp_mkdir_p( $this->output_dir );
		}

		if ( ! file_exists( $this->temp_dir ) ) {
			wp_mkdir_p( $this->temp_dir );
		}

		// Add index.php for security
		$index_content = "<?php\n// Silence is golden.";

		$output_index = $this->output_dir . 'index.php';
		if ( ! file_exists( $output_index ) ) {
			file_put_contents( $output_index, $index_content );
		}

		$temp_index = $this->temp_dir . 'index.php';
		if ( ! file_exists( $temp_index ) ) {
			file_put_contents( $temp_index, $index_content );
		}
	}

	/**
	 * Get last error message
	 *
	 * @return string|null
	 */
	public function getLastError(): ?string {
		return $this->last_error;
	}

	/**
	 * Get library used for last generation
	 *
	 * @return string|null
	 */
	public function getLibraryUsed(): ?string {
		return $this->library_used;
	}

	/**
	 * Check which PDF libraries are available
	 *
	 * @return array Available libraries
	 */
	public function getAvailableLibraries(): array {
		$available = array();

		if ( class_exists( 'Mpdf\\Mpdf' ) ) {
			$available[] = 'mPDF';
		}

		if ( class_exists( 'TCPDF' ) ) {
			$available[] = 'TCPDF';
		}

		if ( class_exists( 'Dompdf\\Dompdf' ) ) {
			$available[] = 'Dompdf';
		}

		return $available;
	}

	/**
	 * Get URL for a generated PDF
	 *
	 * @param string $path Absolute path to PDF
	 * @return string URL to PDF
	 */
	public function getUrl( string $path ): string {
		$upload_dir = wp_upload_dir();

		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );
	}

	/**
	 * Clean up old temp files
	 *
	 * @param int $max_age_hours Max age in hours
	 * @return int Number of files deleted
	 */
	public function cleanupTempFiles( int $max_age_hours = 24 ): int {
		$deleted = 0;
		$max_age = $max_age_hours * 3600;
		$now     = time();

		$files = glob( $this->temp_dir . '*' );

		foreach ( $files as $file ) {
			if ( is_file( $file ) && basename( $file ) !== 'index.php' ) {
				if ( ( $now - filemtime( $file ) ) > $max_age ) {
					if ( unlink( $file ) ) {
						++$deleted;
					}
				}
			}
		}

		return $deleted;
	}
}
