<?php
/**
 * Apollo Social – Luckysheet JSON Converter
 *
 * Transforms stored spreadsheet data from WordPress CPT or custom database tables
 * into the JSON structure required by Luckysheet, enabling a seamless Excel-like
 * editor experience within the WordPress admin and frontend.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * LUCKYSHEET DATA FORMAT EXPLAINED
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * Luckysheet uses a specific JSON structure to represent spreadsheet data.
 * The main components are:
 *
 * 1. WORKBOOK (root level):
 *    - Array of sheets (like Excel workbook with multiple tabs)
 *    - Global settings (title, lang, gridlines, etc.)
 *
 * 2. SHEET (each tab):
 *    - name: Sheet tab name (e.g., "Sheet1", "Vendas 2025")
 *    - index: Unique identifier for the sheet
 *    - status: 1 = active/visible, 0 = hidden
 *    - order: Display order in tab bar
 *    - celldata: Array of cell objects (the actual data)
 *    - config: Column widths, row heights, merged cells, etc.
 *
 * 3. CELLDATA (each cell):
 *    {
 *      "r": 0,           // Row index (0-based)
 *      "c": 0,           // Column index (0-based)
 *      "v": {            // Cell value object
 *        "v": "Hello",   // Raw value (string, number, boolean)
 *        "m": "Hello",   // Display value (formatted)
 *        "ct": {         // Cell type
 *          "fa": "General",  // Format string
 *          "t": "s"          // Type: s=string, n=number, b=boolean, d=date
 *        },
 *        "fc": "#000000",    // Font color
 *        "bg": "#ffffff",    // Background color
 *        "ff": 0,            // Font family index
 *        "fs": 11,           // Font size
 *        "bl": 0,            // Bold: 0=no, 1=yes
 *        "it": 0,            // Italic: 0=no, 1=yes
 *        "ht": 1,            // Horizontal alignment: 0=left, 1=center, 2=right
 *        "vt": 1             // Vertical alignment: 0=top, 1=middle, 2=bottom
 *      }
 *    }
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHITECTURAL INTENT
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * This converter enables storing spreadsheets as structured JSON in WordPress,
 * providing:
 *
 * 1. NO EXTERNAL DEPENDENCIES
 *    - No Google Sheets API, no Microsoft Office integration
 *    - Data stays within the WordPress database
 *    - Full offline capability
 *
 * 2. SEAMLESS EXCEL-LIKE EDITING
 *    - Luckysheet provides formulas, formatting, charts
 *    - Users familiar with Excel can work immediately
 *    - No learning curve for basic operations
 *
 * 3. WordPress INTEGRATION
 *    - Spreadsheets stored as Custom Post Type or post meta
 *    - Full revision history via WordPress revisions
 *    - Permission control via capabilities
 *    - REST API ready for headless usage
 *
 * 4. DATA PORTABILITY
 *    - JSON format is universal and portable
 *    - Easy export/import between sites
 *    - Can be converted to CSV, Excel, etc.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * SECURITY CONSIDERATIONS
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * Spreadsheet cells can contain malicious content. This converter implements:
 *
 * 1. HTML STRIPPING
 *    - All cell values are stripped of HTML tags
 *    - Prevents XSS via cell content display
 *
 * 2. SCRIPT DETECTION
 *    - Formula injection patterns are detected and blocked
 *    - Dangerous formulas (=EXEC, =SYSTEM) are sanitized
 *
 * 3. ENCODING
 *    - Special characters are properly JSON-encoded
 *    - No raw user input in output JSON
 *
 * 4. SIZE LIMITS
 *    - Maximum rows/columns enforced to prevent memory issues
 *    - Large datasets are paginated or truncated with warning
 *
 * @package    ApolloSocial
 * @subpackage Converters
 * @since      1.2.0
 * @author     Apollo Team
 */

namespace Apollo\Converters;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LuckysheetConverter
 *
 * Converts WordPress spreadsheet data to Luckysheet JSON format and vice versa.
 *
 * Usage Examples:
 *
 *   // From raw array data
 *   $converter = new LuckysheetConverter();
 *   $json = $converter->fromArray( $rows );
 *
 *   // From CPT post
 *   $json = LuckysheetConverter::fromPost( $post_id );
 *
 *   // From custom DB table
 *   $json = LuckysheetConverter::fromTable( 'apollo_spreadsheets', $sheet_id );
 *
 *   // Convert Luckysheet JSON back to array
 *   $rows = $converter->toArray( $luckysheet_json );
 *
 * @since 1.2.0
 */
class LuckysheetConverter {

	/**
	 * Maximum allowed rows in a spreadsheet.
	 * Prevents memory exhaustion with very large datasets.
	 *
	 * @var int
	 */
	const MAX_ROWS = 10000;

	/**
	 * Maximum allowed columns in a spreadsheet.
	 * Excel limit is 16,384 (XFD), but we use a reasonable limit.
	 *
	 * @var int
	 */
	const MAX_COLS = 256;

	/**
	 * Maximum cell content length.
	 * Prevents excessively long strings that could cause issues.
	 *
	 * @var int
	 */
	const MAX_CELL_LENGTH = 32767;
	// Excel's limit

	/**
	 * Default column width in pixels.
	 *
	 * @var int
	 */
	const DEFAULT_COL_WIDTH = 100;

	/**
	 * Default row height in pixels.
	 *
	 * @var int
	 */
	const DEFAULT_ROW_HEIGHT = 25;

	/**
	 * Dangerous formula patterns that should be blocked.
	 * These patterns could be used for formula injection attacks.
	 *
	 * @var array
	 */
	private static $dangerous_patterns = [
		'/^=\s*cmd/i',
		// Command execution
					'/^=\s*exec/i',
		// Execute
					'/^=\s*system/i',
		// System calls
					'/^=\s*shell/i',
		// Shell commands
					'/^=\s*powershell/i',
		// PowerShell
					'/^=\s*wscript/i',
		// Windows Script Host
					'/^=\s*mshta/i',
		// MSHTA
					'/^\+\s*cmd/i',
		// Plus prefix injection
					'/^-\s*cmd/i',
		// Minus prefix injection
					'/^@\s*cmd/i',
		// At prefix injection
					'/\|\s*cmd/i',
	// Pipe to command
	];

	/**
	 * Sheet configuration for the current conversion.
	 *
	 * @var array
	 */
	private $sheet_config = [];

	/**
	 * Warnings collected during conversion.
	 *
	 * @var array
	 */
	private $warnings = [];

	/**
	 * Constructor.
	 *
	 * @param array $config Optional configuration overrides.
	 */
	public function __construct( array $config = [] ) {
		$this->sheet_config = wp_parse_args(
			$config,
			[
				'sheet_name'         => 'Planilha 1',
				'default_col_width'  => self::DEFAULT_COL_WIDTH,
				'default_row_height' => self::DEFAULT_ROW_HEIGHT,
				'freeze_row'         => 0,
				// Freeze first N rows (header)
												'freeze_col' => 0,
				// Freeze first N columns
												'show_grid' => true,
				'show_row_header'    => true,
				'show_col_header'    => true,
			]
		);
	}

	/**
	 * Convert a 2D array of data to Luckysheet JSON format.
	 *
	 * This is the main conversion method. It takes a simple 2D array
	 * (like what you'd get from a CSV or database query) and transforms
	 * it into the full Luckysheet JSON structure.
	 *
	 * @since 1.2.0
	 *
	 * @param array $rows       2D array of data. Each row is an array of cell values.
	 * @param array $options    Optional. Additional conversion options.
	 *                          - 'first_row_header' (bool): Treat first row as header (bold).
	 *                          - 'column_types' (array): Type hints for columns ('number', 'date', 'text').
	 *                          - 'column_widths' (array): Custom column widths.
	 *                          - 'merged_cells' (array): Array of merge definitions.
	 * @return string JSON string ready for Luckysheet initialization.
	 */
	public function fromArray( array $rows, array $options = [] ) {
		$this->warnings = [];

		// Parse options
		$options = wp_parse_args(
			$options,
			[
				'first_row_header' => true,
				'column_types'     => [],
				'column_widths'    => [],
				'merged_cells'     => [],
			]
		);

		// Validate and limit data size
		$rows = $this->limitDataSize( $rows );

		// Build celldata array
		// This is where we iterate over every cell and create the Luckysheet format
		$celldata = [];
		$max_cols = 0;

		foreach ( $rows as $row_index => $row ) {
			// Skip if row is not an array (malformed data)
			if ( ! is_array( $row ) ) {
				$this->warnings[] = sprintf( 'Linha %d ignorada: não é um array.', $row_index );
				continue;
			}

			$col_index = 0;
			foreach ( $row as $cell_value ) {
				// Create the cell data object
				// Each cell in Luckysheet needs specific structure
				$cell = $this->createCellData(
					$row_index,
					$col_index,
					$cell_value,
					[
						'is_header'   => ( $options['first_row_header'] && $row_index === 0 ),
						'column_type' => isset( $options['column_types'][ $col_index ] )
							? $options['column_types'][ $col_index ]
							: 'auto',
					]
				);

				if ( $cell !== null ) {
					$celldata[] = $cell;
				}

				++$col_index;
			}//end foreach

			// Track maximum columns for config
			$max_cols = max( $max_cols, $col_index );
		}//end foreach

		// Build sheet configuration
		// This includes column widths, row heights, frozen panes, etc.
		$config = $this->buildSheetConfig( count( $rows ), $max_cols, $options );

		// Build the complete sheet object
		$sheet = [
			'name'                          => $this->sheet_config['sheet_name'],
			'index'                         => 'sheet_' . uniqid(),
			// Unique ID for this sheet
							'status'        => 1,
			// 1 = active/visible
							'order'         => 0,
			// First sheet
							'hide'          => 0,
			// Not hidden
							'row'           => count( $rows ),
			// Total rows
							'column'        => $max_cols,
			// Total columns
							'celldata'      => $celldata,
			// All cell data
							'config'        => $config,
			// Sheet configuration
							'scrollLeft'    => 0,
			// Scroll position
							'scrollTop'     => 0,
			'luckysheet_select_save'        => [],
			// Selection state
							'zoomRatio'     => 1,
			// Zoom level
							'showGridLines' => $this->sheet_config['show_grid'] ? 1 : 0,
		];

		// Add frozen panes if configured
		if ( $this->sheet_config['freeze_row'] > 0 || $this->sheet_config['freeze_col'] > 0 ) {
			$sheet['frozen'] = [
				'type'  => 'rangeBoth',
				'range' => [
					'row_focus'    => $this->sheet_config['freeze_row'] - 1,
					'column_focus' => $this->sheet_config['freeze_col'] - 1,
				],
			];
		}

		// Add merged cells if provided
		if ( ! empty( $options['merged_cells'] ) ) {
			$sheet['config']['merge'] = $this->formatMergedCells( $options['merged_cells'] );
		}

		// Build the workbook (array of sheets)
		// Luckysheet expects an array even for single-sheet workbooks
		$workbook = [ $sheet ];

		// Return as JSON string
		// We use JSON_UNESCAPED_UNICODE for proper UTF-8 support
		return wp_json_encode( $workbook, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Create a single cell data object for Luckysheet.
	 *
	 * This method takes a raw cell value and converts it to the Luckysheet
	 * cell format with proper type detection, formatting, and sanitization.
	 *
	 * @since 1.2.0
	 *
	 * @param int   $row       Row index (0-based).
	 * @param int   $col       Column index (0-based).
	 * @param mixed $value     The cell value (string, number, bool, null).
	 * @param array $options   Cell-specific options (is_header, column_type).
	 * @return array|null Cell data object, or null if cell is empty.
	 */
	private function createCellData( $row, $col, $value, array $options = [] ) {
		// Skip completely empty cells to reduce JSON size
		// Luckysheet handles missing cells as empty
		if ( $value === null || $value === '' ) {
			return null;
		}

		// Sanitize the value for security
		// This removes HTML, scripts, and dangerous content
		$sanitized = $this->sanitizeCellValue( $value );

		// Detect the value type and format
		$type_info = $this->detectCellType( $sanitized, $options['column_type'] ?? 'auto' );

		// Build the cell value object (v property)
		$cell_value = [
			'v'                 => $type_info['value'],
			// Raw value
							'm' => $type_info['display'],
			// Display/formatted value
				'ct'            => [
					'fa'                        => $type_info['format'],
					// Format string (e.g., "General", "#,##0.00")
											't' => $type_info['type'],
			// Type: s=string, n=number, b=boolean, d=date
				],
		];

		// Apply header styling if this is a header row
		if ( ! empty( $options['is_header'] ) ) {
			$cell_value['bl'] = 1;
			// Bold
			$cell_value['bg'] = '#f3f4f6';
			// Light gray background
			$cell_value['ht'] = 1;
			// Center align
			$cell_value['fc'] = '#1f2937';
			// Dark text color
		}

		// Build the complete cell object
		// The structure requires r (row), c (column), and v (value object)
		return [
			'r' => (int) $row,
			'c' => (int) $col,
			'v' => $cell_value,
		];
	}

	/**
	 * Sanitize a cell value for security.
	 *
	 * This method ensures that cell values don't contain:
	 * - HTML tags (XSS prevention)
	 * - Script injection patterns
	 * - Formula injection attacks
	 * - Excessively long content
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $value Raw cell value.
	 * @return mixed Sanitized value.
	 */
	private function sanitizeCellValue( $value ) {
		// Handle non-string values
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_numeric( $value ) ) {
			return $value;
			// Numbers don't need sanitization
		}
		if ( ! is_string( $value ) ) {
			return (string) $value;
		}

		// Step 1: Strip HTML tags
		// This prevents XSS if cell content is displayed in HTML context
		$value = wp_strip_all_tags( $value );

		// Step 2: Check for formula injection patterns
		// Formulas starting with =, +, -, @ can be exploited
		foreach ( self::$dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				// Log the attempt and neutralize
				error_log(
					sprintf(
						'[Apollo Luckysheet] Dangerous formula blocked: %s',
						substr( $value, 0, 50 )
					)
				);
				// Prefix with single quote to make it a string literal
				$value = "'" . $value;
				break;
			}
		}

		// Step 3: Trim to maximum length
		if ( strlen( $value ) > self::MAX_CELL_LENGTH ) {
			$value            = substr( $value, 0, self::MAX_CELL_LENGTH );
			$this->warnings[] = sprintf(
				'Célula truncada para %d caracteres.',
				self::MAX_CELL_LENGTH
			);
		}

		// Step 4: Normalize line breaks
		$value = str_replace( [ "\r\n", "\r" ], "\n", $value );

		return $value;
	}

	/**
	 * Detect the type and format of a cell value.
	 *
	 * Luckysheet needs to know the type of each cell for proper handling:
	 * - Numbers: Right-aligned, can be used in calculations
	 * - Dates: Special formatting, date picker in editor
	 * - Strings: Left-aligned, no calculations
	 * - Formulas: Evaluated and displayed
	 *
	 * @since 1.2.0
	 *
	 * @param mixed  $value       The sanitized cell value.
	 * @param string $type_hint   Hint from column configuration ('auto', 'number', 'date', 'text').
	 * @return array Array with 'type', 'value', 'display', 'format' keys.
	 */
	private function detectCellType( $value, $type_hint = 'auto' ) {
		// Default result for strings
		$result = [
			'type'                    => 's',
			// String
							'value'   => $value,
			// Raw value
							'display' => $value,
			// Display value
							'format'  => 'General',
		// Format string
		];

		// Handle booleans
		if ( is_bool( $value ) ) {
			return [
				'type'    => 'b',
				'value'   => $value,
				'display' => $value ? 'TRUE' : 'FALSE',
				'format'  => 'General',
			];
		}

		// Handle numbers (integers and floats)
		if ( is_numeric( $value ) ) {
			$num_value = floatval( $value );
			$is_int    = ( floor( $num_value ) == $num_value );

			return [
				'type'    => 'n',
				'value'   => $num_value,
				'display' => $is_int ? number_format( $num_value, 0, ',', '.' ) : number_format( $num_value, 2, ',', '.' ),
				'format'  => $is_int ? '#,##0' : '#,##0.00',
			];
		}

		// Handle formulas (start with =)
		if ( is_string( $value ) && strpos( $value, '=' ) === 0 ) {
			// Don't execute formulas, just store them
			// Luckysheet will evaluate them client-side
			return [
				'type'                      => 'f',
				// Formula (custom type, Luckysheet uses 's' but stores formula)
									'value' => $value,
				'display'                   => $value,
				'format'                    => 'General',
			];
		}

		// Handle dates (if type hint is 'date' or auto-detected)
		if ( $type_hint === 'date' || $this->looksLikeDate( $value ) ) {
			$timestamp = strtotime( $value );
			if ( $timestamp !== false ) {
				return [
					'type'    => 'd',
					'value'   => $value,
					'display' => date_i18n( 'd/m/Y', $timestamp ),
					'format'  => 'dd/mm/yyyy',
				];
			}
		}

		// Handle currency (BRL format)
		if ( preg_match( '/^R\$\s*[\d.,]+$/', $value ) ) {
			$num = preg_replace( '/[^\d,.-]/', '', $value );
			$num = str_replace( [ '.', ',' ], [ '', '.' ], $num );
			if ( is_numeric( $num ) ) {
				return [
					'type'    => 'n',
					'value'   => floatval( $num ),
					'display' => $value,
					'format'  => 'R$ #,##0.00',
				];
			}
		}

		// Handle percentages
		if ( preg_match( '/^[\d.,]+\s*%$/', $value ) ) {
			$num = floatval( str_replace( [ '%', ',', ' ' ], [ '', '.', '' ], $value ) );
			return [
				'type'    => 'n',
				'value'   => $num / 100,
				'display' => $value,
				'format'  => '0.00%',
			];
		}

		return $result;
	}

	/**
	 * Check if a string value looks like a date.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value appears to be a date.
	 */
	private function looksLikeDate( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		// Common date patterns
		$patterns = [
			'/^\d{2}\/\d{2}\/\d{4}$/',
			// 25/12/2025
							'/^\d{4}-\d{2}-\d{2}$/',
			// 2025-12-25
							'/^\d{2}-\d{2}-\d{4}$/',
			// 25-12-2025
							'/^\d{1,2}\s+\w+\s+\d{4}$/',
		// 25 December 2025
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build sheet configuration object.
	 *
	 * Configuration includes column widths, row heights, merged cells,
	 * frozen panes, and other sheet-level settings.
	 *
	 * @since 1.2.0
	 *
	 * @param int   $row_count   Total number of rows.
	 * @param int   $col_count   Total number of columns.
	 * @param array $options     Conversion options with column_widths, etc.
	 * @return array Sheet configuration object.
	 */
	private function buildSheetConfig( $row_count, $col_count, array $options ) {
		$config = [];

		// Column widths
		// Map: column_index => width_in_pixels
		$columnlen = [];
		for ( $i = 0; $i < $col_count; $i++ ) {
			if ( isset( $options['column_widths'][ $i ] ) ) {
				$columnlen[ $i ] = (int) $options['column_widths'][ $i ];
			} else {
				$columnlen[ $i ] = $this->sheet_config['default_col_width'];
			}
		}
		if ( ! empty( $columnlen ) ) {
			$config['columnlen'] = $columnlen;
		}

		// Row heights (usually default, but can be customized)
		// Only include if there are custom heights
		if ( isset( $options['row_heights'] ) && is_array( $options['row_heights'] ) ) {
			$config['rowlen'] = [];
			foreach ( $options['row_heights'] as $row_index => $height ) {
				$config['rowlen'][ (int) $row_index ] = (int) $height;
			}
		}

		// Default row/column settings
		$config['defaultrowlen'] = $this->sheet_config['default_row_height'];
		$config['defaultcollen'] = $this->sheet_config['default_col_width'];

		return $config;
	}

	/**
	 * Format merged cells configuration for Luckysheet.
	 *
	 * Merged cells in Luckysheet are defined as an object where each key
	 * is a unique identifier and the value contains the merge range.
	 *
	 * @since 1.2.0
	 *
	 * @param array $merged_cells Array of merge definitions.
	 *                            Each: array( 'r' => 0, 'c' => 0, 'rs' => 2, 'cs' => 3 )
	 *                            (row, col, row_span, col_span)
	 * @return array Luckysheet merge configuration.
	 */
	private function formatMergedCells( array $merged_cells ) {
		$merge = [];

		foreach ( $merged_cells as $index => $def ) {
			// Each merge needs a unique key
			$key = sprintf( '%d_%d', $def['r'], $def['c'] );

			$merge[ $key ] = [
				'r'                      => (int) $def['r'],
				// Start row
									'c'  => (int) $def['c'],
				// Start column
									'rs' => (int) $def['rs'],
				// Row span
									'cs' => (int) $def['cs'],
			// Column span
			];
		}

		return $merge;
	}

	/**
	 * Limit data size to prevent memory issues.
	 *
	 * @param array $rows Input rows.
	 * @return array Possibly truncated rows.
	 */
	private function limitDataSize( array $rows ) {
		$row_count = count( $rows );

		// Limit rows
		if ( $row_count > self::MAX_ROWS ) {
			$rows             = array_slice( $rows, 0, self::MAX_ROWS );
			$this->warnings[] = sprintf(
				'Dados truncados de %d para %d linhas.',
				$row_count,
				self::MAX_ROWS
			);
		}

		// Limit columns in each row
		foreach ( $rows as $index => &$row ) {
			if ( is_array( $row ) && count( $row ) > self::MAX_COLS ) {
				$row              = array_slice( $row, 0, self::MAX_COLS );
				$this->warnings[] = sprintf(
					'Linha %d truncada para %d colunas.',
					$index,
					self::MAX_COLS
				);
			}
		}

		return $rows;
	}

	/**
	 * Get warnings from the last conversion.
	 *
	 * @return array Array of warning messages.
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/*
	=========================================================================
	 * STATIC FACTORY METHODS
	 * =========================================================================
	 * These methods provide convenient ways to load data from various sources.
	 */

	/**
	 * Load spreadsheet data from a WordPress post (CPT).
	 *
	 * Expects the post to have spreadsheet data stored in post meta.
	 *
	 * @since 1.2.0
	 *
	 * @param int   $post_id  The post ID.
	 * @param array $options  Conversion options.
	 * @return string|false Luckysheet JSON or false on failure.
	 */
	public static function fromPost( $post_id, array $options = [] ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// Check for stored Luckysheet JSON first (already converted)
		$existing_json = get_post_meta( $post_id, '_apollo_spreadsheet_json', true );
		if ( ! empty( $existing_json ) ) {
			return $existing_json;
		}

		// Try to load raw data from meta
		// Supported formats:
		// 1. _apollo_spreadsheet_data: 2D array
		// 2. _apollo_spreadsheet_csv: CSV string
		$raw_data = get_post_meta( $post_id, '_apollo_spreadsheet_data', true );

		if ( empty( $raw_data ) ) {
			// Try CSV format
			$csv_data = get_post_meta( $post_id, '_apollo_spreadsheet_csv', true );
			if ( ! empty( $csv_data ) ) {
				$raw_data = self::parseCsv( $csv_data );
			}
		}

		if ( empty( $raw_data ) || ! is_array( $raw_data ) ) {
			// Return empty spreadsheet template
			return self::createEmptySpreadsheet( $options );
		}

		// Set sheet name from post title
		$config = wp_parse_args(
			$options,
			[
				'sheet_name' => $post->post_title ?: 'Planilha',
			]
		);

		$converter = new self( $config );
		return $converter->fromArray( $raw_data, $options );
	}

	/**
	 * Load spreadsheet data from a custom database table.
	 *
	 * @since 1.2.0
	 *
	 * @param string $table_name Table name (without prefix).
	 * @param int    $sheet_id   Row ID in the table.
	 * @param array  $options    Conversion options.
	 * @return string|false Luckysheet JSON or false on failure.
	 */
	public static function fromTable( $table_name, $sheet_id, array $options = [] ) {
		global $wpdb;

		$table = $wpdb->prefix . sanitize_key( $table_name );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `$table` WHERE id = %d",
				$sheet_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return false;
		}

		// Look for data column
		$data_columns = [ 'data', 'spreadsheet_data', 'json_data', 'content' ];
		$raw_data     = null;

		foreach ( $data_columns as $col ) {
			if ( isset( $row[ $col ] ) && ! empty( $row[ $col ] ) ) {
				$decoded = json_decode( $row[ $col ], true );
				if ( is_array( $decoded ) ) {
					$raw_data = $decoded;
					break;
				}
			}
		}

		if ( empty( $raw_data ) ) {
			return self::createEmptySpreadsheet( $options );
		}

		// Set sheet name from table row if available
		$sheet_name = $row['name'] ?? $row['title'] ?? $row['sheet_name'] ?? 'Planilha';
		$config     = wp_parse_args( $options, [ 'sheet_name' => $sheet_name ] );

		$converter = new self( $config );
		return $converter->fromArray( $raw_data, $options );
	}

	/**
	 * Create an empty spreadsheet JSON.
	 *
	 * @param array $options Configuration options.
	 * @return string Empty Luckysheet JSON.
	 */
	public static function createEmptySpreadsheet( array $options = [] ) {
		$sheet_name = $options['sheet_name'] ?? 'Planilha 1';
		$rows       = $options['rows'] ?? 50;
		$cols       = $options['cols'] ?? 20;

		$sheet = [
			'name'          => $sheet_name,
			'index'         => 'sheet_' . uniqid(),
			'status'        => 1,
			'order'         => 0,
			'hide'          => 0,
			'row'           => $rows,
			'column'        => $cols,
			'celldata'      => [],
			'config'        => [
				'defaultrowlen' => self::DEFAULT_ROW_HEIGHT,
				'defaultcollen' => self::DEFAULT_COL_WIDTH,
			],
			'scrollLeft'    => 0,
			'scrollTop'     => 0,
			'showGridLines' => 1,
		];

		return wp_json_encode( [ $sheet ], JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Parse CSV string into 2D array.
	 *
	 * @param string $csv_string CSV content.
	 * @return array 2D array of data.
	 */
	private static function parseCsv( $csv_string ) {
		$rows  = [];
		$lines = str_getcsv( $csv_string, "\n" );

		foreach ( $lines as $line ) {
			$rows[] = str_getcsv( $line );
		}

		return $rows;
	}

	/*
	=========================================================================
	 * REVERSE CONVERSION (Luckysheet JSON to Array)
	 * =========================================================================
	 */

	/**
	 * Convert Luckysheet JSON back to a simple 2D array.
	 *
	 * This is useful for:
	 * - Exporting to CSV
	 * - Storing simplified data
	 * - Processing with PHP
	 *
	 * @since 1.2.0
	 *
	 * @param string $json Luckysheet JSON string.
	 * @return array 2D array of values.
	 */
	public function toArray( $json ) {
		$workbook = json_decode( $json, true );

		if ( ! is_array( $workbook ) || empty( $workbook ) ) {
			return [];
		}

		// Get first sheet (usually the active one)
		$sheet = $workbook[0];

		if ( ! isset( $sheet['celldata'] ) || ! is_array( $sheet['celldata'] ) ) {
			return [];
		}

		// Initialize 2D array
		$rows    = [];
		$max_row = 0;
		$max_col = 0;

		// First pass: determine dimensions
		foreach ( $sheet['celldata'] as $cell ) {
			$max_row = max( $max_row, $cell['r'] );
			$max_col = max( $max_col, $cell['c'] );
		}

		// Initialize with empty values
		for ( $r = 0; $r <= $max_row; $r++ ) {
			$rows[ $r ] = array_fill( 0, $max_col + 1, '' );
		}

		// Fill in values
		foreach ( $sheet['celldata'] as $cell ) {
			$r = $cell['r'];
			$c = $cell['c'];
			$v = $cell['v'];

			// Extract the actual value
			if ( is_array( $v ) ) {
				$value = $v['v'] ?? $v['m'] ?? '';
			} else {
				$value = $v;
			}

			$rows[ $r ][ $c ] = $value;
		}

		return $rows;
	}

	/**
	 * Export Luckysheet data to CSV format.
	 *
	 * @since 1.2.0
	 *
	 * @param string $json Luckysheet JSON string.
	 * @return string CSV content.
	 */
	public function toCsv( $json ) {
		$rows = $this->toArray( $json );

		$output = fopen( 'php://temp', 'r+' );

		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}
}
