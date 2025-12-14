<?php

/**
 * Apollo Social – Luckysheet Helper Functions
 *
 * Global helper functions for working with Luckysheet spreadsheet data.
 * These provide convenient access to the LuckysheetConverter class
 * without needing to instantiate it or use the full namespace.
 *
 * Usage:
 *   $json = apollo_spreadsheet_to_luckysheet( $rows );
 *   $rows = apollo_luckysheet_to_array( $json );
 *   echo apollo_render_spreadsheet( $post_id );
 *
 * @package    ApolloSocial
 * @subpackage Helpers
 * @since      1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define class name as string to avoid Intelephense static analysis on optional dependency
define( 'APOLLO_LUCKYSHEET_CONVERTER_CLASS', 'ApolloSocial\\Converters\\LuckysheetConverter' );

/**
 * Convert a 2D array to Luckysheet JSON format.
 *
 * This is the primary helper for converting spreadsheet data.
 *
 * Example:
 *   $rows = [
 *     ['Nome', 'Email', 'Vendas'],
 *     ['João', 'joao@example.com', 1500],
 *     ['Maria', 'maria@example.com', 2300],
 *   ];
 *   $json = apollo_spreadsheet_to_luckysheet( $rows );
 *
 * @since 1.2.0
 *
 * @param array $rows    2D array of data.
 * @param array $options Optional. Conversion options.
 * @return string Luckysheet JSON.
 */
function apollo_spreadsheet_to_luckysheet( array $rows, array $options = array() ) {
	if ( ! class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Converters/LuckysheetConverter.php';
	}

	$converter = new ( APOLLO_LUCKYSHEET_CONVERTER_CLASS )( $options );

	return $converter->fromArray( $rows, $options );
}

/**
 * Convert Luckysheet JSON back to a 2D array.
 *
 * Useful for exporting or processing spreadsheet data.
 *
 * @since 1.2.0
 *
 * @param string $json Luckysheet JSON.
 * @return array 2D array of values.
 */
function apollo_luckysheet_to_array( $json ) {
	if ( ! class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Converters/LuckysheetConverter.php';
	}

	$class     = APOLLO_LUCKYSHEET_CONVERTER_CLASS;
	$converter = new $class();

	return $converter->toArray( $json );
}

/**
 * Load spreadsheet from a WordPress post.
 *
 * @since 1.2.0
 *
 * @param int   $post_id Post ID.
 * @param array $options Optional. Conversion options.
 * @return string|false Luckysheet JSON or false.
 */
function apollo_get_spreadsheet( $post_id, array $options = array() ) {
	if ( ! class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Converters/LuckysheetConverter.php';
	}

	return call_user_func_array( array( APOLLO_LUCKYSHEET_CONVERTER_CLASS, 'fromPost' ), array( $post_id, $options ) );
}

/**
 * Save Luckysheet JSON to a post.
 *
 * @since 1.2.0
 *
 * @param int    $post_id Post ID.
 * @param string $json    Luckysheet JSON.
 * @return bool True on success.
 */
function apollo_save_spreadsheet( $post_id, $json ) {
	// Validate JSON
	$decoded = json_decode( $json, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return false;
	}

	// Store both the full JSON and a simplified array version
	update_post_meta( $post_id, '_apollo_spreadsheet_json', $json );

	// Also store as array for easier querying/searching
	if ( class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		$class      = APOLLO_LUCKYSHEET_CONVERTER_CLASS;
		$converter  = new $class();
		$array_data = $converter->toArray( $json );
		update_post_meta( $post_id, '_apollo_spreadsheet_data', $array_data );
	}

	return true;
}

/**
 * Create an empty spreadsheet JSON.
 *
 * @since 1.2.0
 *
 * @param array $options Configuration options.
 * @return string Empty Luckysheet JSON.
 */
function apollo_create_empty_spreadsheet( array $options = array() ) {
	if ( ! class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Converters/LuckysheetConverter.php';
	}

	return call_user_func( array( APOLLO_LUCKYSHEET_CONVERTER_CLASS, 'createEmptySpreadsheet' ), $options );
}

/**
 * Export spreadsheet to CSV.
 *
 * @since 1.2.0
 *
 * @param int|string $source Post ID or Luckysheet JSON.
 * @return string CSV content.
 */
function apollo_spreadsheet_to_csv( $source ) {
	if ( ! class_exists( APOLLO_LUCKYSHEET_CONVERTER_CLASS ) ) {
		require_once APOLLO_SOCIAL_PLUGIN_DIR . 'src/Converters/LuckysheetConverter.php';
	}

	// Get JSON if post ID provided
	if ( is_numeric( $source ) ) {
		$json = apollo_get_spreadsheet( (int) $source );
	} else {
		$json = $source;
	}

	if ( empty( $json ) ) {
		return '';
	}

	$class     = APOLLO_LUCKYSHEET_CONVERTER_CLASS;
	$converter = new $class();

	return $converter->toCsv( $json );
}

/**
 * Render Luckysheet editor container with initialization script.
 *
 * This outputs the HTML and JavaScript needed to display a Luckysheet
 * spreadsheet editor on the page.
 *
 * @since 1.2.0
 *
 * @param int   $post_id    Post ID (or 0 for new).
 * @param array $options    Render options.
 *                          - 'container_id' (string): Container element ID.
 *                          - 'height' (string): Container height (default '600px').
 *                          - 'readonly' (bool): Read-only mode.
 *                          - 'autosave' (bool): Enable autosave.
 * @return string HTML output.
 */
function apollo_render_spreadsheet_editor( $post_id = 0, array $options = array() ) {
	$defaults = array(
		'container_id' => 'apollo-luckysheet',
		'height'       => '600px',
		'readonly'     => false,
		'autosave'     => true,
		'toolbar'      => true,
	);
	$options  = wp_parse_args( $options, $defaults );

	// Get spreadsheet data
	if ( $post_id > 0 ) {
		$json = apollo_get_spreadsheet( $post_id );
	} else {
		$json = apollo_create_empty_spreadsheet();
	}

	// Escape JSON for inline script
	$json_escaped = esc_attr( $json );

	ob_start();
	?>
	<!-- Luckysheet Container -->
	<div id="<?php echo esc_attr( $options['container_id'] ); ?>"
		class="apollo-spreadsheet-container"
		style="height: <?php echo esc_attr( $options['height'] ); ?>; width: 100%;">
	</div>

	<!-- Luckysheet Initialization -->
	<script type="text/javascript">
		(function() {
			'use strict';

			document.addEventListener('DOMContentLoaded', function() {
				// Check if Luckysheet is loaded
				if (typeof luckysheet === 'undefined') {
					console.error('[Apollo] Luckysheet library not loaded.');
					return;
				}

				// Parse spreadsheet data
				var sheetData = <?php echo $json; ?>;

				// Initialize Luckysheet
				var options = {
					container: '<?php echo esc_js( $options['container_id'] ); ?>',
					title: '<?php echo esc_js( get_the_title( $post_id ) ?: 'Nova Planilha' ); ?>',
					lang: 'pt',
					data: sheetData,
					allowEdit: <?php echo $options['readonly'] ? 'false' : 'true'; ?>,
					showtoolbar: <?php echo $options['toolbar'] ? 'true' : 'false'; ?>,
					showinfobar: false,
					showsheetbar: true,
					showstatisticBar: true,
					enableAddRow: true,
					enableAddBackTop: true,
					userInfo: false,
					myFolderUrl: '',
					devicePixelRatio: window.devicePixelRatio,

					// Hooks for autosave
					hook: {
						updated: function(operate) {
							<?php if ( $options['autosave'] && $post_id > 0 ) : ?>
								// Debounced autosave
								clearTimeout(window.apolloSpreadsheetSaveTimer);
								window.apolloSpreadsheetSaveTimer = setTimeout(function() {
									apolloSaveSpreadsheet(<?php echo (int) $post_id; ?>);
								}, 3000);
							<?php endif; ?>
						}
					}
				};

				luckysheet.create(options);
				console.log('[Apollo] Luckysheet initialized');
			});

			// Save function
			window.apolloSaveSpreadsheet = function(postId) {
				var data = luckysheet.getAllSheets();
				var json = JSON.stringify(data);

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: 'apollo_save_spreadsheet',
							nonce: '<?php echo wp_create_nonce( 'apollo_spreadsheet_save' ); ?>',
							post_id: postId,
							data: json
						})
					})
					.then(function(response) {
						return response.json();
					})
					.then(function(result) {
						if (result.success) {
							console.log('[Apollo] Spreadsheet saved');
						} else {
							console.error('[Apollo] Save failed:', result.data);
						}
					})
					.catch(function(error) {
						console.error('[Apollo] Save error:', error);
					});
			};
		})();
	</script>
	<?php

	return ob_get_clean();
}

/**
 * AJAX handler for saving spreadsheet data.
 *
 * Registered on wp_ajax_apollo_save_spreadsheet.
 */
add_action( 'wp_ajax_apollo_save_spreadsheet', 'apollo_ajax_save_spreadsheet' );
function apollo_ajax_save_spreadsheet() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'apollo_spreadsheet_save' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce inválido.' ), 403 );

		return;
	}

	// Check capability
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Sem permissão.' ), 403 );

		return;
	}

	// Get and validate data
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$data    = wp_unslash( $_POST['data'] ?? '' );

	if ( $post_id < 1 ) {
		wp_send_json_error( array( 'message' => 'ID inválido.' ), 400 );

		return;
	}

	// Validate JSON
	$decoded = json_decode( $data, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		wp_send_json_error( array( 'message' => 'JSON inválido.' ), 400 );

		return;
	}

	// Save
	if ( apollo_save_spreadsheet( $post_id, $data ) ) {
		wp_send_json_success(
			array(
				'message' => 'Salvo com sucesso.',
				'post_id' => $post_id,
			)
		);
	} else {
		wp_send_json_error( array( 'message' => 'Erro ao salvar.' ), 500 );
	}
}
