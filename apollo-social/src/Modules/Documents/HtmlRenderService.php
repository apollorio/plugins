<?php
/**
 * Apollo HTML Render Service.
 *
 * Converts Quill Delta format and Luckysheet JSON to clean HTML
 * for PDF generation and document display.
 *
 * @package Apollo\Modules\Documents
 * @since   1.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * HTML Render Service Class
 *
 * Usage:
 * ```php
 * $renderer = new HtmlRenderService();
 * $html = $renderer->deltaToHtml($deltaJson);
 * $html = $renderer->sheetToHtml($luckysheetJson);
 * ```
 */
class HtmlRenderService {

	/** @var array Quill format handlers */
	private array $format_handlers;

	/** @var string Base CSS for PDF output */
	private string $base_css;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initFormatHandlers();
		$this->initBaseCss();
	}

	/**
	 * Initialize Quill format handlers
	 */
	private function initFormatHandlers(): void {
		$this->format_handlers = [
			// Inline formats
			'bold'       => fn( string $text ): string => "<strong>{$text}</strong>",
			'italic'     => fn( string $text ): string => "<em>{$text}</em>",
			'underline'  => fn( string $text ): string => "<u>{$text}</u>",
			'strike'     => fn( string $text ): string => "<s>{$text}</s>",
			'code'       => fn( string $text ): string => "<code>{$text}</code>",
			'script'     => function ( string $text, $value ): string {
				return $value === 'super' ? "<sup>{$text}</sup>" : "<sub>{$text}</sub>";
			},
			'link'       => function ( string $text, $value ): string {
				$href = is_array( $value ) ? ( $value['href'] ?? '' ) : $value;
				$href = esc_url( $href );
				return "<a href=\"{$href}\" target=\"_blank\">{$text}</a>";
			},
			'color'      => function ( string $text, $value ): string {
				$color = esc_attr( $value );
				return "<span style=\"color: {$color}\">{$text}</span>";
			},
			'background' => function ( string $text, $value ): string {
				$bg = esc_attr( $value );
				return "<span style=\"background-color: {$bg}\">{$text}</span>";
			},
			'size'       => function ( string $text, $value ): string {
				$sizes = [
					'small'  => '0.75em',
					'normal' => '1em',
					'large'  => '1.5em',
					'huge'   => '2.5em',
				];
				$size  = $sizes[ $value ] ?? '1em';
				return "<span style=\"font-size: {$size}\">{$text}</span>";
			},
			'font'       => function ( string $text, $value ): string {
				$font = esc_attr( $value );
				return "<span style=\"font-family: {$font}\">{$text}</span>";
			},
		];
	}

	/**
	 * Initialize base CSS for PDF
	 */
	private function initBaseCss(): void {
		$this->base_css = <<<'CSS'
/* Apollo Document Styles */
.apollo-document {
    font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
    font-size: 12pt;
    line-height: 1.6;
    color: #1a1a1a;
    max-width: 100%;
}

.apollo-document h1 {
    font-size: 24pt;
    font-weight: 700;
    margin: 0 0 16pt 0;
    color: #0f172a;
    border-bottom: 2px solid #0f172a;
    padding-bottom: 8pt;
}

.apollo-document h2 {
    font-size: 18pt;
    font-weight: 600;
    margin: 20pt 0 12pt 0;
    color: #1e293b;
}

.apollo-document h3 {
    font-size: 14pt;
    font-weight: 600;
    margin: 16pt 0 8pt 0;
    color: #334155;
}

.apollo-document p {
    margin: 0 0 12pt 0;
}

.apollo-document ul,
.apollo-document ol {
    margin: 0 0 12pt 0;
    padding-left: 24pt;
}

.apollo-document li {
    margin-bottom: 4pt;
}

.apollo-document blockquote {
    margin: 12pt 0;
    padding: 8pt 16pt;
    border-left: 4pt solid #3b82f6;
    background: #f1f5f9;
    font-style: italic;
}

.apollo-document pre {
    margin: 12pt 0;
    padding: 12pt;
    background: #1e293b;
    color: #e2e8f0;
    border-radius: 4pt;
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 10pt;
    overflow-x: auto;
}

.apollo-document code {
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    font-size: 10pt;
    background: #f1f5f9;
    padding: 2pt 4pt;
    border-radius: 2pt;
}

.apollo-document img {
    max-width: 100%;
    height: auto;
    margin: 12pt 0;
}

.apollo-document table {
    width: 100%;
    border-collapse: collapse;
    margin: 12pt 0;
}

.apollo-document th,
.apollo-document td {
    border: 1px solid #cbd5e1;
    padding: 8pt 12pt;
    text-align: left;
    vertical-align: top;
}

.apollo-document th {
    background: #f1f5f9;
    font-weight: 600;
}

.apollo-document tr:nth-child(even) td {
    background: #fafafa;
}

.apollo-document .text-center { text-align: center; }
.apollo-document .text-right { text-align: right; }
.apollo-document .text-justify { text-align: justify; }

.apollo-document .indent-1 { padding-left: 24pt; }
.apollo-document .indent-2 { padding-left: 48pt; }
.apollo-document .indent-3 { padding-left: 72pt; }
.apollo-document .indent-4 { padding-left: 96pt; }
.apollo-document .indent-5 { padding-left: 120pt; }

/* Spreadsheet specific styles */
.apollo-spreadsheet {
    width: 100%;
    border-collapse: collapse;
    font-family: 'DejaVu Sans', 'Arial', sans-serif;
    font-size: 10pt;
}

.apollo-spreadsheet th,
.apollo-spreadsheet td {
    border: 1px solid #d1d5db;
    padding: 4pt 8pt;
    text-align: left;
    min-width: 60pt;
}

.apollo-spreadsheet th {
    background: #e5e7eb;
    font-weight: 600;
    text-align: center;
}

.apollo-spreadsheet .cell-number {
    text-align: right;
    font-family: 'DejaVu Sans Mono', monospace;
}

.apollo-spreadsheet .cell-formula {
    color: #3b82f6;
}

/* Signature block styles */
.signature-block {
    margin-top: 40pt;
    padding: 20pt;
    border: 1px dashed #9ca3af;
    background: #fafafa;
    page-break-inside: avoid;
}

.signature-block .signature-title {
    font-size: 10pt;
    color: #6b7280;
    margin-bottom: 8pt;
    text-transform: uppercase;
    letter-spacing: 1pt;
}

.signature-block .signature-line {
    border-bottom: 1px solid #1a1a1a;
    height: 48pt;
    margin: 16pt 0 8pt 0;
}

.signature-block .signature-name {
    font-size: 12pt;
    font-weight: 600;
}

.signature-block .signature-cpf {
    font-size: 9pt;
    color: #6b7280;
}

.signature-block .signature-date {
    font-size: 9pt;
    color: #6b7280;
    margin-top: 4pt;
}

/* Page break utility */
.page-break {
    page-break-after: always;
}

.no-break {
    page-break-inside: avoid;
}
CSS;
	}

	/**
	 * Convert Quill Delta JSON to HTML
	 *
	 * @param string|array $delta Delta JSON string or array
	 * @param array        $options Rendering options
	 * @return string HTML output
	 */
	public function deltaToHtml( $delta, array $options = [] ): string {
		// Parse JSON if string
		if ( is_string( $delta ) ) {
			$delta = json_decode( $delta, true );
		}

		if ( ! is_array( $delta ) || empty( $delta['ops'] ) ) {
			return '';
		}

		$html = '';
		$ops  = $delta['ops'];

		// Group ops into blocks
		$blocks = $this->groupOpsIntoBlocks( $ops );

		// Render each block
		foreach ( $blocks as $block ) {
			$html .= $this->renderBlock( $block );
		}

		// Wrap in container
		$class = 'apollo-document';
		if ( ! empty( $options['class'] ) ) {
			$class .= ' ' . esc_attr( $options['class'] );
		}

		return "<div class=\"{$class}\">{$html}</div>";
	}

	/**
	 * Group Delta ops into blocks (paragraphs, headings, lists, etc.)
	 *
	 * @param array $ops Delta operations
	 * @return array Grouped blocks
	 */
	private function groupOpsIntoBlocks( array $ops ): array {
		$blocks       = [];
		$currentBlock = [
			'ops'        => [],
			'attributes' => [],
		];

		foreach ( $ops as $op ) {
			$insert = $op['insert'] ?? '';

			// Check if this is a newline with block attributes
			if ( $insert === "\n" && ! empty( $op['attributes'] ) ) {
				// End current block with these attributes
				$currentBlock['attributes'] = $op['attributes'];
				$blocks[]                   = $currentBlock;
				$currentBlock               = [
					'ops'        => [],
					'attributes' => [],
				];
				continue;
			}

			// Handle multiple newlines
			if ( is_string( $insert ) && strpos( $insert, "\n" ) !== false ) {
				$lines = explode( "\n", $insert );

				foreach ( $lines as $i => $line ) {
					if ( $line !== '' ) {
						$lineOp = [ 'insert' => $line ];
						if ( ! empty( $op['attributes'] ) ) {
							$lineOp['attributes'] = $op['attributes'];
						}
						$currentBlock['ops'][] = $lineOp;
					}

					// Each newline (except the last empty split) creates a new block
					if ( $i < count( $lines ) - 1 ) {
						$blocks[]     = $currentBlock;
						$currentBlock = [
							'ops'        => [],
							'attributes' => [],
						];
					}
				}
			} else {
				// Regular op, add to current block
				$currentBlock['ops'][] = $op;
			}//end if
		}//end foreach

		// Don't forget the last block
		if ( ! empty( $currentBlock['ops'] ) ) {
			$blocks[] = $currentBlock;
		}

		return $blocks;
	}

	/**
	 * Render a single block
	 *
	 * @param array $block Block with ops and attributes
	 * @return string HTML
	 */
	private function renderBlock( array $block ): string {
		$content    = '';
		$attributes = $block['attributes'] ?? [];

		// Render inline content
		foreach ( $block['ops'] as $op ) {
			$insert = $op['insert'] ?? '';

			// Handle embeds (images, videos, etc.)
			if ( is_array( $insert ) ) {
				$content .= $this->renderEmbed( $insert );
				continue;
			}

			// Escape and format text
			$text = esc_html( $insert );

			// Apply inline formats
			$opAttrs = $op['attributes'] ?? [];
			foreach ( $opAttrs as $format => $value ) {
				if ( isset( $this->format_handlers[ $format ] ) && $value ) {
					$handler = $this->format_handlers[ $format ];
					$text    = is_callable( $handler ) ? $handler( $text, $value ) : $text;
				}
			}

			$content .= $text;
		}//end foreach

		// Empty block = empty paragraph
		if ( trim( $content ) === '' ) {
			return '<p>&nbsp;</p>';
		}

		// Determine block type from attributes
		return $this->wrapBlockContent( $content, $attributes );
	}

	/**
	 * Wrap block content in appropriate HTML element
	 *
	 * @param string $content Block content
	 * @param array  $attributes Block attributes
	 * @return string HTML
	 */
	private function wrapBlockContent( string $content, array $attributes ): string {
		$tag   = 'p';
		$class = [];
		$style = [];

		// Headings
		if ( ! empty( $attributes['header'] ) ) {
			$level = min( 6, max( 1, (int) $attributes['header'] ) );
			$tag   = "h{$level}";
		}

		// Lists
		if ( ! empty( $attributes['list'] ) ) {
			$listType = $attributes['list'];

			if ( $listType === 'ordered' ) {
				return "<li>{$content}</li>";
				// Will be wrapped in <ol> during post-processing
			} elseif ( $listType === 'bullet' ) {
				return "<li>{$content}</li>";
				// Will be wrapped in <ul> during post-processing
			} elseif ( $listType === 'checked' || $listType === 'unchecked' ) {
				$checked = $listType === 'checked' ? '☑' : '☐';
				return "<p>{$checked} {$content}</p>";
			}
		}

		// Blockquote
		if ( ! empty( $attributes['blockquote'] ) ) {
			$tag = 'blockquote';
		}

		// Code block
		if ( ! empty( $attributes['code-block'] ) ) {
			return '<pre><code>' . $content . '</code></pre>';
		}

		// Text alignment
		if ( ! empty( $attributes['align'] ) ) {
			$class[] = 'text-' . esc_attr( $attributes['align'] );
		}

		// Indent
		if ( ! empty( $attributes['indent'] ) ) {
			$indent  = min( 5, max( 1, (int) $attributes['indent'] ) );
			$class[] = "indent-{$indent}";
		}

		// Direction (RTL)
		if ( ! empty( $attributes['direction'] ) && $attributes['direction'] === 'rtl' ) {
			$style[] = 'direction: rtl';
		}

		// Build class and style strings
		$classStr = ! empty( $class ) ? ' class="' . implode( ' ', $class ) . '"' : '';
		$styleStr = ! empty( $style ) ? ' style="' . implode( '; ', $style ) . '"' : '';

		return "<{$tag}{$classStr}{$styleStr}>{$content}</{$tag}>";
	}

	/**
	 * Render embed (image, video, formula)
	 *
	 * @param array $embed Embed data
	 * @return string HTML
	 */
	private function renderEmbed( array $embed ): string {
		// Image
		if ( isset( $embed['image'] ) ) {
			$src = esc_url( $embed['image'] );
			$alt = esc_attr( $embed['alt'] ?? 'Imagem' );
			return "<img src=\"{$src}\" alt=\"{$alt}\" loading=\"lazy\">";
		}

		// Video (convert to link for PDF)
		if ( isset( $embed['video'] ) ) {
			$src = esc_url( $embed['video'] );
			return "<p><a href=\"{$src}\">[Vídeo: {$src}]</a></p>";
		}

		// Formula (LaTeX - display as is for now)
		if ( isset( $embed['formula'] ) ) {
			$formula = esc_html( $embed['formula'] );
			return "<code class=\"formula\">{$formula}</code>";
		}

		// Divider
		if ( isset( $embed['divider'] ) ) {
			return '<hr>';
		}

		return '';
	}

	/**
	 * Convert Luckysheet JSON to HTML table
	 *
	 * @param string|array $sheetData Luckysheet JSON string or array
	 * @param array        $options Rendering options
	 * @return string HTML table
	 */
	public function sheetToHtml( $sheetData, array $options = [] ): string {
		// Parse JSON if string
		if ( is_string( $sheetData ) ) {
			$sheetData = json_decode( $sheetData, true );
		}

		if ( ! is_array( $sheetData ) ) {
			return '<p class="error">Dados da planilha inválidos.</p>';
		}

		// Handle Luckysheet format (array of sheets)
		if ( isset( $sheetData[0]['celldata'] ) || isset( $sheetData[0]['data'] ) ) {
			$sheet = $sheetData[0];
			// Use first sheet
		} elseif ( isset( $sheetData['celldata'] ) || isset( $sheetData['data'] ) ) {
			$sheet = $sheetData;
		} else {
			return '<p class="error">Formato de planilha não reconhecido.</p>';
		}

		// Get cells data
		$cells = [];

		// Luckysheet stores data in 'data' (2D array) or 'celldata' (sparse array)
		if ( ! empty( $sheet['data'] ) && is_array( $sheet['data'] ) ) {
			$cells = $this->convertDataArrayToCells( $sheet['data'] );
		} elseif ( ! empty( $sheet['celldata'] ) && is_array( $sheet['celldata'] ) ) {
			$cells = $this->convertCelldataToCells( $sheet['celldata'] );
		}

		if ( empty( $cells ) ) {
			return '<p class="empty">Planilha vazia.</p>';
		}

		// Find dimensions
		$maxRow = 0;
		$maxCol = 0;
		foreach ( $cells as $key => $cell ) {
			list( $row, $col ) = explode( ':', $key );
			$maxRow            = max( $maxRow, (int) $row );
			$maxCol            = max( $maxCol, (int) $col );
		}

		// Limit output size
		$maxRow = min( $maxRow, $options['maxRows'] ?? 100 );
		$maxCol = min( $maxCol, $options['maxCols'] ?? 26 );

		// Build HTML table
		$html = '<table class="apollo-spreadsheet">';

		// Header row (A, B, C, ...)
		$html .= '<thead><tr><th></th>';
		for ( $col = 0; $col <= $maxCol; $col++ ) {
			$html .= '<th>' . $this->columnToLetter( $col ) . '</th>';
		}
		$html .= '</tr></thead>';

		// Data rows
		$html .= '<tbody>';
		for ( $row = 0; $row <= $maxRow; $row++ ) {
			$html .= '<tr>';
			$html .= '<th>' . ( $row + 1 ) . '</th>';
			// Row number

			for ( $col = 0; $col <= $maxCol; $col++ ) {
				$key  = "{$row}:{$col}";
				$cell = $cells[ $key ] ?? null;

				$value    = '';
				$class    = [];
				$styleArr = [];

				if ( $cell ) {
					$value = $this->getCellDisplayValue( $cell );

					// Check if number
					if ( is_numeric( $cell['v'] ?? '' ) ) {
						$class[] = 'cell-number';
					}

					// Check if formula
					if ( ! empty( $cell['f'] ) ) {
						$class[] = 'cell-formula';
					}

					// Cell styling
					if ( ! empty( $cell['fc'] ) ) {
						$styleArr[] = 'color: ' . esc_attr( $cell['fc'] );
					}
					if ( ! empty( $cell['bg'] ) ) {
						$styleArr[] = 'background-color: ' . esc_attr( $cell['bg'] );
					}
					if ( ! empty( $cell['bl'] ) && $cell['bl'] == 1 ) {
						$styleArr[] = 'font-weight: bold';
					}
					if ( ! empty( $cell['it'] ) && $cell['it'] == 1 ) {
						$styleArr[] = 'font-style: italic';
					}
				}//end if

				$classStr = ! empty( $class ) ? ' class="' . implode( ' ', $class ) . '"' : '';
				$styleStr = ! empty( $styleArr ) ? ' style="' . implode( '; ', $styleArr ) . '"' : '';

				$html .= "<td{$classStr}{$styleStr}>" . esc_html( $value ) . '</td>';
			}//end for

			$html .= '</tr>';
		}//end for
		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * Convert Luckysheet 'data' 2D array to cell map
	 *
	 * @param array $data 2D array of cell data
	 * @return array Cell map (row:col => cell)
	 */
	private function convertDataArrayToCells( array $data ): array {
		$cells = [];

		foreach ( $data as $row => $rowData ) {
			if ( ! is_array( $rowData ) ) {
				continue;
			}
			foreach ( $rowData as $col => $cell ) {
				if ( $cell !== null && $cell !== '' ) {
					$cells[ "{$row}:{$col}" ] = is_array( $cell ) ? $cell : [ 'v' => $cell ];
				}
			}
		}

		return $cells;
	}

	/**
	 * Convert Luckysheet 'celldata' sparse array to cell map
	 *
	 * @param array $celldata Sparse cell data
	 * @return array Cell map (row:col => cell)
	 */
	private function convertCelldataToCells( array $celldata ): array {
		$cells = [];

		foreach ( $celldata as $item ) {
			if ( isset( $item['r'], $item['c'] ) ) {
				$row = (int) $item['r'];
				$col = (int) $item['c'];
				$v   = $item['v'] ?? null;

				if ( $v !== null ) {
					$cells[ "{$row}:{$col}" ] = is_array( $v ) ? $v : [ 'v' => $v ];
				}
			}
		}

		return $cells;
	}

	/**
	 * Get display value for a cell
	 *
	 * @param array $cell Cell data
	 * @return string Display value
	 */
	private function getCellDisplayValue( array $cell ): string {
		// 'm' is the display value (formatted)
		if ( isset( $cell['m'] ) && $cell['m'] !== '' ) {
			return (string) $cell['m'];
		}

		// 'v' is the raw value
		if ( isset( $cell['v'] ) ) {
			return (string) $cell['v'];
		}

		return '';
	}

	/**
	 * Convert column index to letter (0 = A, 1 = B, etc.)
	 *
	 * @param int $col Column index
	 * @return string Column letter(s)
	 */
	private function columnToLetter( int $col ): string {
		$letter = '';

		while ( $col >= 0 ) {
			$letter = chr( ( $col % 26 ) + 65 ) . $letter;
			$col    = intdiv( $col, 26 ) - 1;
		}

		return $letter;
	}

	/**
	 * Get base CSS for documents
	 *
	 * @return string CSS content
	 */
	public function getBaseCss(): string {
		return $this->base_css;
	}

	/**
	 * Render complete HTML document with styles
	 *
	 * @param string $bodyHtml Body HTML content
	 * @param array  $options Document options
	 * @return string Complete HTML document
	 */
	public function renderFullDocument( string $bodyHtml, array $options = [] ): string {
		$title  = esc_html( $options['title'] ?? 'Documento Apollo' );
		$author = esc_html( $options['author'] ?? 'Apollo Social' );
		$date   = $options['date'] ?? wp_date( 'd/m/Y H:i' );

		$css = $this->base_css;

		// Add custom CSS if provided
		if ( ! empty( $options['customCss'] ) ) {
			$css .= "\n/* Custom Styles */\n" . $options['customCss'];
		}

		$header = '';
		if ( ! empty( $options['showHeader'] ) ) {
			$header = <<<HTML
<header class="document-header" style="border-bottom: 2px solid #0f172a; padding-bottom: 12pt; margin-bottom: 24pt;">
    <h1 style="margin: 0; font-size: 24pt;">{$title}</h1>
    <p style="margin: 8pt 0 0 0; color: #6b7280; font-size: 10pt;">
        {$author} &bull; {$date}
    </p>
</header>
HTML;
		}

		$footer = '';
		if ( ! empty( $options['showFooter'] ) ) {
			$footer = <<<HTML
<footer class="document-footer" style="border-top: 1px solid #e5e7eb; padding-top: 12pt; margin-top: 24pt; font-size: 9pt; color: #9ca3af; text-align: center;">
    Documento gerado por Apollo Social &bull; {$date}
</footer>
HTML;
		}

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="author" content="{$author}">
    <meta name="generator" content="Apollo Social PDF Generator">
    <style>
{$css}
    </style>
</head>
<body>
{$header}
{$bodyHtml}
{$footer}
</body>
</html>
HTML;
	}

	/**
	 * Add signature blocks to document
	 *
	 * @param string $html Document HTML
	 * @param array  $signers Array of signers ['name' => 'Name', 'cpf' => '000.000.000-00', 'party' => 'A']
	 * @return string HTML with signature blocks
	 */
	public function addSignatureBlocks( string $html, array $signers = [] ): string {
		if ( empty( $signers ) ) {
			// Default two-party signature
			$signers = [
				[
					'name'  => '',
					'cpf'   => '',
					'party' => 'A',
				],
				[
					'name'  => '',
					'cpf'   => '',
					'party' => 'B',
				],
			];
		}

		$blocks  = '<div class="signatures-container" style="margin-top: 48pt; page-break-inside: avoid;">';
		$blocks .= '<h3 style="margin-bottom: 24pt;">Assinaturas</h3>';
		$blocks .= '<div style="display: flex; justify-content: space-between; gap: 24pt;">';

		foreach ( $signers as $signer ) {
			$name  = esc_html( $signer['name'] ?? '' );
			$cpf   = esc_html( $signer['cpf'] ?? '' );
			$party = esc_html( $signer['party'] ?? '' );
			$date  = ! empty( $signer['signed_at'] ) ? esc_html( $signer['signed_at'] ) : '___/___/_____';

			$blocks .= <<<HTML
<div class="signature-block" style="flex: 1;">
    <div class="signature-title">Parte {$party}</div>
    <div class="signature-line"></div>
    <div class="signature-name">{$name}</div>
    <div class="signature-cpf">CPF: {$cpf}</div>
    <div class="signature-date">Data: {$date}</div>
</div>
HTML;
		}

		$blocks .= '</div></div>';

		return $html . $blocks;
	}
}
