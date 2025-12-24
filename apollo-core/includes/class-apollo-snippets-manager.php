<?php
/**
 * Apollo Snippets Manager
 *
 * ============================================================================
 * ADMIN-MANAGED CSS/JS SNIPPETS FOR APOLLO PAGES
 * ============================================================================
 *
 * Allows administrators to add custom CSS and JavaScript snippets that are
 * applied ONLY to Apollo pages (never site-wide). Snippets can be enabled/disabled
 * individually and include comments/notes.
 *
 * SECURITY:
 * - Only users with 'manage_options' capability can manage snippets
 * - NO PHP execution from database (CSS/JS only)
 * - Size limits enforced (max 200KB per snippet)
 * - Proper nonce verification on all saves
 *
 * STORAGE:
 * - Uses WordPress options table (array of snippets)
 * - Each snippet: id, title, type, code, notes, enabled, priority, updated
 *
 * OUTPUT:
 * - CSS: Injected via wp_add_inline_style attached to apollo-core-uni
 * - JS: Injected via wp_add_inline_script attached to apollo-core-base
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Snippets_Manager {

	/**
	 * Option name for storing snippets
	 */
	const OPTION_NAME = 'apollo_snippets';

	/**
	 * Max code size per snippet (200KB)
	 */
	const MAX_CODE_SIZE = 204800;

	/**
	 * Admin page slug
	 */
	const PAGE_SLUG = 'apollo-snippets';

	/**
	 * Initialize the snippets manager
	 *
	 * @return void
	 */
	public static function init(): void {
		// Admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 20 );

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_save_snippet', array( __CLASS__, 'ajax_save_snippet' ) );
		add_action( 'wp_ajax_apollo_delete_snippet', array( __CLASS__, 'ajax_delete_snippet' ) );
		add_action( 'wp_ajax_apollo_toggle_snippet', array( __CLASS__, 'ajax_toggle_snippet' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu page
	 *
	 * @return void
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'apollo',
			__( 'Apollo Snippets', 'apollo-core' ),
			__( 'Snippets (CSS/JS)', 'apollo-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		// CodeMirror for code editing.
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

		// Enqueue Apollo admin styles.
		if ( class_exists( 'Apollo_Core\Assets' ) ) {
			Assets::enqueue_admin();
		}

		// Add inline styles for snippets page.
		wp_add_inline_style( 'wp-admin', self::get_admin_css() );
	}

	/**
	 * Get enabled snippets sorted by priority
	 *
	 * @return array
	 */
	public static function get_enabled_snippets(): array {
		$snippets = self::get_all_snippets();

		$enabled = array_filter(
			$snippets,
			function ( $snippet ) {
				return ! empty( $snippet['enabled'] );
			}
		);

		// Sort by priority (lower = earlier).
		usort(
			$enabled,
			function ( $a, $b ) {
				$pa = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
				$pb = isset( $b['priority'] ) ? (int) $b['priority'] : 10;
				return $pa - $pb;
			}
		);

		return $enabled;
	}

	/**
	 * Get all snippets
	 *
	 * @return array
	 */
	public static function get_all_snippets(): array {
		$snippets = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $snippets ) ) {
			return array();
		}

		return $snippets;
	}

	/**
	 * Get a single snippet by ID
	 *
	 * @param string $id Snippet ID
	 * @return array|null
	 */
	public static function get_snippet( string $id ): ?array {
		$snippets = self::get_all_snippets();

		foreach ( $snippets as $snippet ) {
			if ( isset( $snippet['id'] ) && $snippet['id'] === $id ) {
				return $snippet;
			}
		}

		return null;
	}

	/**
	 * Save a snippet
	 *
	 * @param array $data Snippet data
	 * @return array|WP_Error Saved snippet or error
	 */
	public static function save_snippet( array $data ): array|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to manage snippets.', 'apollo-core' ) );
		}

		// Validate required fields.
		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Snippet title is required.', 'apollo-core' ) );
		}

		if ( ! in_array( $data['type'] ?? '', array( 'css', 'js' ), true ) ) {
			return new WP_Error( 'invalid_type', __( 'Snippet type must be "css" or "js".', 'apollo-core' ) );
		}

		// Check code size.
		$code = $data['code'] ?? '';
		if ( strlen( $code ) > self::MAX_CODE_SIZE ) {
			return new WP_Error( 'code_too_large', sprintf( __( 'Snippet code exceeds maximum size of %s.', 'apollo-core' ), size_format( self::MAX_CODE_SIZE ) ) );
		}

		// Generate or validate ID.
		$id = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : 'snippet_' . wp_generate_uuid4();

		// Build snippet data.
		$snippet = array(
			'id'       => $id,
			'title'    => sanitize_text_field( $data['title'] ),
			'type'     => sanitize_key( $data['type'] ),
			'code'     => $code, // Store as-is (will be escaped on output).
			'notes'    => sanitize_textarea_field( $data['notes'] ?? '' ),
			'enabled'  => ! empty( $data['enabled'] ),
			'priority' => absint( $data['priority'] ?? 10 ),
			'updated'  => current_time( 'mysql' ),
			'author'   => get_current_user_id(),
		);

		// Get existing snippets.
		$snippets = self::get_all_snippets();

		// Update or add snippet.
		$found = false;
		foreach ( $snippets as $key => $existing ) {
			if ( isset( $existing['id'] ) && $existing['id'] === $id ) {
				$snippets[ $key ] = $snippet;
				$found            = true;
				break;
			}
		}

		if ( ! $found ) {
			$snippets[] = $snippet;
		}

		// Save.
		update_option( self::OPTION_NAME, $snippets, false );

		return $snippet;
	}

	/**
	 * Delete a snippet
	 *
	 * @param string $id Snippet ID
	 * @return bool
	 */
	public static function delete_snippet( string $id ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$snippets = self::get_all_snippets();
		$updated  = array_filter(
			$snippets,
			function ( $snippet ) use ( $id ) {
				return ! isset( $snippet['id'] ) || $snippet['id'] !== $id;
			}
		);

		if ( count( $updated ) === count( $snippets ) ) {
			return false; // Not found.
		}

		update_option( self::OPTION_NAME, array_values( $updated ), false );
		return true;
	}

	/**
	 * Toggle snippet enabled state
	 *
	 * @param string $id Snippet ID
	 * @return bool|WP_Error New enabled state or error
	 */
	public static function toggle_snippet( string $id ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to manage snippets.', 'apollo-core' ) );
		}

		$snippets = self::get_all_snippets();

		foreach ( $snippets as $key => $snippet ) {
			if ( isset( $snippet['id'] ) && $snippet['id'] === $id ) {
				$snippets[ $key ]['enabled'] = ! $snippet['enabled'];
				$snippets[ $key ]['updated'] = current_time( 'mysql' );
				update_option( self::OPTION_NAME, $snippets, false );
				return $snippets[ $key ]['enabled'];
			}
		}

		return new WP_Error( 'not_found', __( 'Snippet not found.', 'apollo-core' ) );
	}

	/**
	 * AJAX handler: Save snippet
	 *
	 * @return void
	 */
	public static function ajax_save_snippet(): void {
		check_ajax_referer( 'apollo_snippets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-core' ) ), 403 );
		}

		$data = array(
			'id'       => sanitize_key( $_POST['id'] ?? '' ),
			'title'    => sanitize_text_field( $_POST['title'] ?? '' ),
			'type'     => sanitize_key( $_POST['type'] ?? 'css' ),
			'code'     => wp_unslash( $_POST['code'] ?? '' ),
			'notes'    => sanitize_textarea_field( $_POST['notes'] ?? '' ),
			'enabled'  => ! empty( $_POST['enabled'] ),
			'priority' => absint( $_POST['priority'] ?? 10 ),
		);

		$result = self::save_snippet( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Snippet saved successfully.', 'apollo-core' ),
				'snippet' => $result,
			)
		);
	}

	/**
	 * AJAX handler: Delete snippet
	 *
	 * @return void
	 */
	public static function ajax_delete_snippet(): void {
		check_ajax_referer( 'apollo_snippets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-core' ) ), 403 );
		}

		$id = sanitize_key( $_POST['id'] ?? '' );

		if ( empty( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Snippet ID is required.', 'apollo-core' ) ), 400 );
		}

		if ( self::delete_snippet( $id ) ) {
			wp_send_json_success( array( 'message' => __( 'Snippet deleted.', 'apollo-core' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Snippet not found.', 'apollo-core' ) ), 404 );
		}
	}

	/**
	 * AJAX handler: Toggle snippet
	 *
	 * @return void
	 */
	public static function ajax_toggle_snippet(): void {
		check_ajax_referer( 'apollo_snippets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-core' ) ), 403 );
		}

		$id = sanitize_key( $_POST['id'] ?? '' );

		if ( empty( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Snippet ID is required.', 'apollo-core' ) ), 400 );
		}

		$result = self::toggle_snippet( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Snippet toggled.', 'apollo-core' ),
				'enabled' => $result,
			)
		);
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
		}

		$snippets = self::get_all_snippets();
		$editing  = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : '';
		$snippet  = $editing ? self::get_snippet( $editing ) : null;

		?>
		<div class="wrap apollo-snippets-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Apollo Snippets', 'apollo-core' ); ?>
			</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&edit=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Snippet', 'apollo-core' ); ?>
			</a>

			<p class="description" style="margin: 1rem 0;">
				<?php esc_html_e( 'Custom CSS and JavaScript snippets applied only to Apollo pages. Snippets are NOT applied site-wide.', 'apollo-core' ); ?>
			</p>

			<hr class="wp-header-end">

			<?php if ( $editing ) : ?>
				<?php self::render_editor( $snippet ); ?>
			<?php else : ?>
				<?php self::render_list( $snippets ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render snippets list
	 *
	 * @param array $snippets All snippets
	 * @return void
	 */
	private static function render_list( array $snippets ): void {
		$nonce = wp_create_nonce( 'apollo_snippets_nonce' );
		?>
		<table class="wp-list-table widefat fixed striped apollo-snippets-table">
			<thead>
				<tr>
					<th class="column-status" style="width: 50px;"><?php esc_html_e( 'Status', 'apollo-core' ); ?></th>
					<th class="column-title"><?php esc_html_e( 'Title', 'apollo-core' ); ?></th>
					<th class="column-type" style="width: 80px;"><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
					<th class="column-priority" style="width: 80px;"><?php esc_html_e( 'Priority', 'apollo-core' ); ?></th>
					<th class="column-updated" style="width: 160px;"><?php esc_html_e( 'Updated', 'apollo-core' ); ?></th>
					<th class="column-actions" style="width: 120px;"><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $snippets ) ) : ?>
					<tr>
						<td colspan="6" style="text-align: center; padding: 2rem;">
							<?php esc_html_e( 'No snippets yet. Click "Add New Snippet" to create one.', 'apollo-core' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $snippets as $snippet ) : ?>
						<tr data-id="<?php echo esc_attr( $snippet['id'] ); ?>">
							<td class="column-status">
								<button type="button"
									class="apollo-toggle-snippet button-link"
									data-id="<?php echo esc_attr( $snippet['id'] ); ?>"
									data-nonce="<?php echo esc_attr( $nonce ); ?>"
									title="<?php echo $snippet['enabled'] ? esc_attr__( 'Disable', 'apollo-core' ) : esc_attr__( 'Enable', 'apollo-core' ); ?>">
									<span class="dashicons <?php echo $snippet['enabled'] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>"
										style="color: <?php echo $snippet['enabled'] ? '#00a32a' : '#999'; ?>;"></span>
								</button>
							</td>
							<td class="column-title">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&edit=' . $snippet['id'] ) ); ?>">
										<?php echo esc_html( $snippet['title'] ); ?>
									</a>
								</strong>
								<?php if ( ! empty( $snippet['notes'] ) ) : ?>
									<p class="description"><?php echo esc_html( wp_trim_words( $snippet['notes'], 10 ) ); ?></p>
								<?php endif; ?>
							</td>
							<td class="column-type">
								<span class="apollo-snippet-type apollo-snippet-type--<?php echo esc_attr( $snippet['type'] ); ?>">
									<?php echo esc_html( strtoupper( $snippet['type'] ) ); ?>
								</span>
							</td>
							<td class="column-priority">
								<?php echo esc_html( $snippet['priority'] ?? 10 ); ?>
							</td>
							<td class="column-updated">
								<?php echo esc_html( $snippet['updated'] ?? '—' ); ?>
							</td>
							<td class="column-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&edit=' . $snippet['id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'apollo-core' ); ?>
								</a>
								<button type="button"
									class="apollo-delete-snippet button button-small button-link-delete"
									data-id="<?php echo esc_attr( $snippet['id'] ); ?>"
									data-nonce="<?php echo esc_attr( $nonce ); ?>"
									data-confirm="<?php esc_attr_e( 'Are you sure you want to delete this snippet?', 'apollo-core' ); ?>">
									<?php esc_html_e( 'Delete', 'apollo-core' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<script>
		jQuery(function($) {
			// Toggle snippet.
			$('.apollo-toggle-snippet').on('click', function() {
				var $btn = $(this);
				var id = $btn.data('id');
				var nonce = $btn.data('nonce');

				$.post(ajaxurl, {
					action: 'apollo_toggle_snippet',
					id: id,
					nonce: nonce
				}, function(response) {
					if (response.success) {
						var $icon = $btn.find('.dashicons');
						if (response.data.enabled) {
							$icon.removeClass('dashicons-marker').addClass('dashicons-yes-alt').css('color', '#00a32a');
						} else {
							$icon.removeClass('dashicons-yes-alt').addClass('dashicons-marker').css('color', '#999');
						}
					} else {
						alert(response.data.message || 'Error toggling snippet');
					}
				});
			});

			// Delete snippet.
			$('.apollo-delete-snippet').on('click', function() {
				var $btn = $(this);
				if (!confirm($btn.data('confirm'))) return;

				var id = $btn.data('id');
				var nonce = $btn.data('nonce');

				$.post(ajaxurl, {
					action: 'apollo_delete_snippet',
					id: id,
					nonce: nonce
				}, function(response) {
					if (response.success) {
						$btn.closest('tr').fadeOut(function() { $(this).remove(); });
					} else {
						alert(response.data.message || 'Error deleting snippet');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render snippet editor
	 *
	 * @param array|null $snippet Existing snippet or null for new
	 * @return void
	 */
	private static function render_editor( ?array $snippet ): void {
		$is_new = empty( $snippet );
		$nonce  = wp_create_nonce( 'apollo_snippets_nonce' );

		$defaults = array(
			'id'       => '',
			'title'    => '',
			'type'     => 'css',
			'code'     => '',
			'notes'    => '',
			'enabled'  => true,
			'priority' => 10,
		);

		$snippet = wp_parse_args( $snippet ?? array(), $defaults );

		?>
		<form id="apollo-snippet-form" class="apollo-snippet-editor">
			<input type="hidden" name="id" value="<?php echo esc_attr( $snippet['id'] ); ?>">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

			<div class="apollo-snippet-fields">
				<div class="apollo-field-row">
					<label for="snippet-title"><?php esc_html_e( 'Title', 'apollo-core' ); ?> <span class="required">*</span></label>
					<input type="text" id="snippet-title" name="title" value="<?php echo esc_attr( $snippet['title'] ); ?>" required class="regular-text">
				</div>

				<div class="apollo-field-row apollo-field-row--inline">
					<div>
						<label for="snippet-type"><?php esc_html_e( 'Type', 'apollo-core' ); ?></label>
						<select id="snippet-type" name="type">
							<option value="css" <?php selected( $snippet['type'], 'css' ); ?>>CSS</option>
							<option value="js" <?php selected( $snippet['type'], 'js' ); ?>>JavaScript</option>
						</select>
					</div>
					<div>
						<label for="snippet-priority"><?php esc_html_e( 'Priority', 'apollo-core' ); ?></label>
						<input type="number" id="snippet-priority" name="priority" value="<?php echo esc_attr( $snippet['priority'] ); ?>" min="1" max="100" class="small-text">
						<span class="description"><?php esc_html_e( '(lower = loads earlier)', 'apollo-core' ); ?></span>
					</div>
					<div>
						<label for="snippet-enabled">
							<input type="checkbox" id="snippet-enabled" name="enabled" value="1" <?php checked( $snippet['enabled'] ); ?>>
							<?php esc_html_e( 'Enabled', 'apollo-core' ); ?>
						</label>
					</div>
				</div>

				<div class="apollo-field-row">
					<label for="snippet-notes"><?php esc_html_e( 'Notes / Description', 'apollo-core' ); ?></label>
					<textarea id="snippet-notes" name="notes" rows="2" class="large-text"><?php echo esc_textarea( $snippet['notes'] ); ?></textarea>
				</div>

				<div class="apollo-field-row apollo-field-code">
					<label for="snippet-code"><?php esc_html_e( 'Code', 'apollo-core' ); ?></label>
					<textarea id="snippet-code" name="code" rows="20" class="large-text code"><?php echo esc_textarea( $snippet['code'] ); ?></textarea>
					<p class="description">
						<?php printf( esc_html__( 'Maximum size: %s. Snippets run ONLY on Apollo pages.', 'apollo-core' ), size_format( self::MAX_CODE_SIZE ) ); ?>
					</p>
				</div>
			</div>

			<div class="apollo-snippet-actions">
				<button type="submit" class="button button-primary button-large">
					<?php echo $is_new ? esc_html__( 'Create Snippet', 'apollo-core' ) : esc_html__( 'Save Changes', 'apollo-core' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>" class="button button-large">
					<?php esc_html_e( 'Cancel', 'apollo-core' ); ?>
				</a>
				<span class="spinner"></span>
				<span class="apollo-save-status"></span>
			</div>
		</form>

		<script>
		jQuery(function($) {
			var $form = $('#apollo-snippet-form');
			var $codeArea = $('#snippet-code');
			var $typeSelect = $('#snippet-type');
			var editor = null;

			// Initialize CodeMirror.
			function initEditor() {
				var type = $typeSelect.val();
				var mode = type === 'js' ? 'javascript' : 'css';

				if (typeof wp !== 'undefined' && wp.codeEditor) {
					var settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
					settings.codemirror = _.extend({}, settings.codemirror, {
						mode: mode,
						lineNumbers: true,
						lineWrapping: true,
						indentUnit: 2,
						tabSize: 2
					});
					editor = wp.codeEditor.initialize($codeArea, settings);
				}
			}

			initEditor();

			// Update CodeMirror mode when type changes.
			$typeSelect.on('change', function() {
				if (editor && editor.codemirror) {
					var mode = $(this).val() === 'js' ? 'javascript' : 'css';
					editor.codemirror.setOption('mode', mode);
				}
			});

			// Form submit.
			$form.on('submit', function(e) {
				e.preventDefault();

				var $btn = $form.find('button[type="submit"]');
				var $spinner = $form.find('.spinner');
				var $status = $form.find('.apollo-save-status');

				// Sync CodeMirror to textarea.
				if (editor && editor.codemirror) {
					editor.codemirror.save();
				}

				$btn.prop('disabled', true);
				$spinner.addClass('is-active');
				$status.text('');

				$.post(ajaxurl, {
					action: 'apollo_save_snippet',
					nonce: $form.find('[name="nonce"]').val(),
					id: $form.find('[name="id"]').val(),
					title: $form.find('[name="title"]').val(),
					type: $form.find('[name="type"]').val(),
					code: $form.find('[name="code"]').val(),
					notes: $form.find('[name="notes"]').val(),
					enabled: $form.find('[name="enabled"]').is(':checked') ? 1 : 0,
					priority: $form.find('[name="priority"]').val()
				}, function(response) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');

					if (response.success) {
						$status.text('✓ Saved').css('color', '#00a32a');
						// Update ID for new snippets.
						if (response.data.snippet && response.data.snippet.id) {
							$form.find('[name="id"]').val(response.data.snippet.id);
						}
						setTimeout(function() { $status.fadeOut(); }, 2000);
					} else {
						$status.text('✗ ' + (response.data.message || 'Error')).css('color', '#d63638');
					}
					$status.fadeIn();
				}).fail(function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$status.text('✗ Request failed').css('color', '#d63638').fadeIn();
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Get admin CSS
	 *
	 * @return string
	 */
	private static function get_admin_css(): string {
		return '
.apollo-snippets-wrap { max-width: 1200px; }
.apollo-snippets-wrap h1 .dashicons { vertical-align: middle; margin-right: 0.5rem; }
.apollo-snippets-table .column-status { text-align: center; }
.apollo-snippet-type {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}
.apollo-snippet-type--css { background: #e7f5ff; color: #1971c2; }
.apollo-snippet-type--js { background: #fff3bf; color: #e67700; }

.apollo-snippet-editor { background: #fff; padding: 20px; border: 1px solid #c3c4c7; margin-top: 20px; }
.apollo-field-row { margin-bottom: 15px; }
.apollo-field-row label { display: block; margin-bottom: 5px; font-weight: 600; }
.apollo-field-row .required { color: #d63638; }
.apollo-field-row--inline { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
.apollo-field-row--inline > div { margin-bottom: 0; }
.apollo-field-code { margin-top: 20px; }
.apollo-field-code textarea { font-family: Consolas, Monaco, monospace; }
.apollo-snippet-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; display: flex; align-items: center; gap: 10px; }
.apollo-snippet-actions .spinner { float: none; margin: 0; }
.apollo-save-status { font-weight: 600; display: none; }
';
	}
}
