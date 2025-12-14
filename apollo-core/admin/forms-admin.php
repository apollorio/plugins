<?php
declare(strict_types=1);

/**
 * Apollo Core - Forms Admin UI
 *
 * Admin interface for managing form schemas
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/admin/forms-admin.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register forms admin menu
 */
function apollo_register_forms_admin_menu() {
	add_submenu_page(
		'apollo-mod',
		// Parent slug (assuming mod is parent).
		__( 'Forms', 'apollo-core' ),
		__( 'Formulários', 'apollo-core' ),
		'manage_options',
		'apollo-forms',
		'apollo_render_forms_admin_page'
	);
}
add_action( 'admin_menu', 'apollo_register_forms_admin_menu' );

/**
 * Enqueue forms admin assets
 *
 * @param string $hook Current admin page hook.
 */
function apollo_enqueue_forms_admin_assets( $hook ) {
	if ( 'mod_page_apollo-forms' !== $hook && 'toplevel_page_apollo-forms' !== $hook ) {
		return;
	}

	// Enqueue sortable (jQuery UI).
	wp_enqueue_script( 'jquery-ui-sortable' );

	// Enqueue custom CSS.
	wp_enqueue_style(
		'apollo-forms-admin',
		APOLLO_CORE_PLUGIN_URL . 'admin/css/forms-admin.css',
		array(),
		APOLLO_CORE_VERSION
	);

	// Enqueue custom JS.
	wp_enqueue_script(
		'apollo-forms-admin',
		APOLLO_CORE_PLUGIN_URL . 'admin/js/forms-admin.js',
		array( 'jquery', 'jquery-ui-sortable', 'wp-util' ),
		APOLLO_CORE_VERSION,
		true
	);

	// Localize script.
	wp_localize_script(
		'apollo-forms-admin',
		'apolloFormsAdmin',
		array(
			'nonce'      => wp_create_nonce( 'apollo_forms_admin' ),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'validTypes' => array( 'text', 'textarea', 'number', 'email', 'select', 'checkbox', 'date', 'instagram', 'password' ),
			'strings'    => array(
				'confirmDelete' => __( 'Are you sure you want to delete this field?', 'apollo-core' ),
				'addField'      => __( 'Add Field', 'apollo-core' ),
				'editField'     => __( 'Edit Field', 'apollo-core' ),
				'save'          => __( 'Save', 'apollo-core' ),
				'cancel'        => __( 'Cancel', 'apollo-core' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'apollo_enqueue_forms_admin_assets' );

/**
 * Render forms admin page
 */
function apollo_render_forms_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-core' ) );
	}

	// Get current form type.
	$current_form_type = isset( $_GET['form_type'] ) ? sanitize_text_field( wp_unslash( $_GET['form_type'] ) ) : 'new_user';

	// Get schema.
	$schema = apollo_get_form_schema( $current_form_type );

	// Sort by order.
	usort(
		$schema,
		function ( $a, $b ) {
			return $a['order'] - $b['order'];
		}
	);

	?>
	<div class="wrap apollo-forms-admin-wrap">
		<h1><?php esc_html_e( 'Form Builder', 'apollo-core' ); ?></h1>

		<!-- Form Type Selector -->
		<div class="apollo-form-type-selector">
			<label for="apollo-form-type-select"><?php esc_html_e( 'Select Form Type:', 'apollo-core' ); ?></label>
			<select id="apollo-form-type-select" onchange="location.href='?page=apollo-forms&form_type=' + this.value">
				<option value="new_user" <?php selected( $current_form_type, 'new_user' ); ?>><?php esc_html_e( 'New User Registration', 'apollo-core' ); ?></option>
				<option value="cpt_event" <?php selected( $current_form_type, 'cpt_event' ); ?>><?php esc_html_e( 'Create Event', 'apollo-core' ); ?></option>
				<option value="cpt_local" <?php selected( $current_form_type, 'cpt_local' ); ?>><?php esc_html_e( 'Create Venue', 'apollo-core' ); ?></option>
				<option value="cpt_dj" <?php selected( $current_form_type, 'cpt_dj' ); ?>><?php esc_html_e( 'Create DJ', 'apollo-core' ); ?></option>
			</select>
		</div>

		<div class="apollo-forms-container">
			<!-- Fields Table -->
			<div class="apollo-forms-fields-section">
				<div class="apollo-forms-actions">
					<button type="button" class="button button-primary" id="apollo-add-field-btn">
						<?php esc_html_e( 'Add Field', 'apollo-core' ); ?>
					</button>
					<button type="button" class="button" id="apollo-save-schema-btn">
						<?php esc_html_e( 'Save Changes', 'apollo-core' ); ?>
					</button>
					<button type="button" class="button" id="apollo-revert-schema-btn">
						<?php esc_html_e( 'Revert', 'apollo-core' ); ?>
					</button>
					<button type="button" class="button" id="apollo-export-schema-btn">
						<?php esc_html_e( 'Export JSON', 'apollo-core' ); ?>
					</button>
				</div>

				<table class="wp-list-table widefat fixed striped" id="apollo-fields-table">
					<thead>
						<tr>
							<th class="apollo-drag-handle-th" style="width: 30px;"></th>
							<th><?php esc_html_e( 'Field Key', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Label', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Type', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Required', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Visible', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Validation', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody id="apollo-fields-tbody">
						<?php foreach ( $schema as $index => $field ) : ?>
							<?php echo apollo_render_field_row( $field, $index ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Preview Pane -->
			<div class="apollo-forms-preview-section">
				<h3><?php esc_html_e( 'Form Preview', 'apollo-core' ); ?></h3>
				<div id="apollo-form-preview">
					<?php
					echo apollo_render_form(
						$current_form_type,
						array(
							'action'    => '#',
							'css_class' => 'apollo-form apollo-form-preview',
						)
					);
					?>
				</div>
			</div>
		</div>

		<!-- Hidden field for form type -->
		<input type="hidden" id="apollo-current-form-type" value="<?php echo esc_attr( $current_form_type ); ?>">
	</div>

	<!-- Add/Edit Field Modal -->
	<div id="apollo-field-modal" class="apollo-modal" style="display: none;">
		<div class="apollo-modal-content">
		<span class="apollo-modal-close">&times;</span>
		<h2 id="apollo-modal-title"><?php esc_html_e( 'Add Field', 'apollo-core' ); ?></h2>
		<form id="apollo-field-form">
			<?php wp_nonce_field( 'apollo_forms_admin', 'apollo_forms_nonce' ); ?>
			<table class="form-table">
					<tr>
						<th><label for="field-key"><?php esc_html_e( 'Field Key', 'apollo-core' ); ?></label></th>
						<td>
							<input type="text" id="field-key" name="key" class="regular-text" required pattern="[a-z0-9_-]+">
							<p class="description"><?php esc_html_e( 'Lowercase letters, numbers, underscores, and hyphens only', 'apollo-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="field-label"><?php esc_html_e( 'Label', 'apollo-core' ); ?></label></th>
						<td><input type="text" id="field-label" name="label" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="field-type"><?php esc_html_e( 'Type', 'apollo-core' ); ?></label></th>
						<td>
							<select id="field-type" name="type" class="regular-text" required>
								<option value="text"><?php esc_html_e( 'Text', 'apollo-core' ); ?></option>
								<option value="textarea"><?php esc_html_e( 'Textarea', 'apollo-core' ); ?></option>
								<option value="number"><?php esc_html_e( 'Number', 'apollo-core' ); ?></option>
								<option value="email"><?php esc_html_e( 'Email', 'apollo-core' ); ?></option>
								<option value="select"><?php esc_html_e( 'Select', 'apollo-core' ); ?></option>
								<option value="checkbox"><?php esc_html_e( 'Checkbox', 'apollo-core' ); ?></option>
								<option value="date"><?php esc_html_e( 'Date', 'apollo-core' ); ?></option>
								<option value="instagram"><?php esc_html_e( 'Instagram', 'apollo-core' ); ?></option>
								<option value="password"><?php esc_html_e( 'Password', 'apollo-core' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="field-required"><?php esc_html_e( 'Required', 'apollo-core' ); ?></label></th>
						<td><input type="checkbox" id="field-required" name="required" value="1"></td>
					</tr>
					<tr>
						<th><label for="field-visible"><?php esc_html_e( 'Visible', 'apollo-core' ); ?></label></th>
						<td><input type="checkbox" id="field-visible" name="visible" value="1" checked></td>
					</tr>
					<tr>
						<th><label for="field-default"><?php esc_html_e( 'Default Value', 'apollo-core' ); ?></label></th>
						<td><input type="text" id="field-default" name="default" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="field-validation"><?php esc_html_e( 'Validation Regex', 'apollo-core' ); ?></label></th>
						<td>
							<input type="text" id="field-validation" name="validation" class="regular-text">
							<p class="description"><?php esc_html_e( 'Enter regex pattern (e.g., /^[0-9]+$/)', 'apollo-core' ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Field', 'apollo-core' ); ?></button>
					<button type="button" class="button apollo-modal-close"><?php esc_html_e( 'Cancel', 'apollo-core' ); ?></button>
				</p>
				<input type="hidden" id="field-index" name="index" value="">
			</form>
		</div>
	</div>
	<?php
}

/**
 * Render single field row in table
 *
 * @param array $field Field data.
 * @param int   $index Field index.
 * @return string HTML row.
 */
function apollo_render_field_row( $field, $index ) {
	ob_start();
	?>
	<tr class="apollo-field-row" data-index="<?php echo esc_attr( $index ); ?>" data-field='<?php echo esc_attr( wp_json_encode( $field ) ); ?>'>
		<td class="apollo-drag-handle"><span class="dashicons dashicons-menu"></span></td>
		<td class="apollo-field-key"><code><?php echo esc_html( $field['key'] ); ?></code></td>
		<td class="apollo-field-label"><?php echo esc_html( $field['label'] ); ?></td>
		<td class="apollo-field-type"><span class="apollo-type-badge"><?php echo esc_html( $field['type'] ); ?></span></td>
		<td class="apollo-field-required">
			<?php echo $field['required'] ? '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>' : '<span class="dashicons dashicons-no-alt" style="color: #ccc;"></span>'; ?>
		</td>
		<td class="apollo-field-visible">
			<?php echo $field['visible'] ? '<span class="dashicons dashicons-visibility" style="color: blue;"></span>' : '<span class="dashicons dashicons-hidden" style="color: #ccc;"></span>'; ?>
		</td>
		<td class="apollo-field-validation"><code><?php echo esc_html( $field['validation'] ); ?></code></td>
		<td class="apollo-field-actions">
			<button type="button" class="button button-small apollo-edit-field-btn" data-index="<?php echo esc_attr( $index ); ?>">
				<?php esc_html_e( 'Edit', 'apollo-core' ); ?>
			</button>
			<button type="button" class="button button-small apollo-duplicate-field-btn" data-index="<?php echo esc_attr( $index ); ?>">
				<?php esc_html_e( 'Duplicate', 'apollo-core' ); ?>
			</button>
			<button type="button" class="button button-small button-link-delete apollo-delete-field-btn" data-index="<?php echo esc_attr( $index ); ?>">
				<?php esc_html_e( 'Delete', 'apollo-core' ); ?>
			</button>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

/**
 * AJAX handler: Save form schema
 */
function apollo_ajax_save_form_schema() {
	// Check nonce.
	check_ajax_referer( 'apollo_forms_admin', 'nonce' );

	// Check capability.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-core' ) ) );
	}

	// Get data.
	$form_type = isset( $_POST['form_type'] ) ? sanitize_text_field( wp_unslash( $_POST['form_type'] ) ) : '';
	$schema    = isset( $_POST['schema'] ) ? json_decode( wp_unslash( $_POST['schema'] ), true ) : array();

	if ( empty( $form_type ) || empty( $schema ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid data.', 'apollo-core' ) ) );
	}

	// Save schema.
	$result = apollo_save_form_schema( $form_type, $schema );

	if ( $result ) {
		wp_send_json_success( array( 'message' => __( 'Schema saved successfully.', 'apollo-core' ) ) );
	} else {
		wp_send_json_error( array( 'message' => __( 'Failed to save schema.', 'apollo-core' ) ) );
	}
}
add_action( 'wp_ajax_apollo_save_form_schema', 'apollo_ajax_save_form_schema' );
