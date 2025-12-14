<?php
declare(strict_types=1);

/**
 * Apollo Core - Form Renderer
 *
 * Renders forms based on schema definitions
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/includes/forms/render.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render form based on schema
 *
 * @param string $form_type Form type to render.
 * @param array  $args      Additional arguments (action, method, css_class, values).
 * @return string HTML form output.
 */
function apollo_render_form( $form_type, $args = [] ) {
	$defaults = [
		'action'    => '',
		'method'    => 'post',
		'css_class' => 'apollo-form',
		'values'    => [],
		'id'        => 'apollo-form-' . $form_type,
	];

	$args = wp_parse_args( $args, $defaults );

	$schema = apollo_get_form_schema( $form_type );

	if ( empty( $schema ) ) {
		return '<p>' . esc_html__( 'No form schema found.', 'apollo-core' ) . '</p>';
	}

	// Sort by order.
	usort(
		$schema,
		function ( $a, $b ) {
			return $a['order'] - $b['order'];
		}
	);

	ob_start();
	?>
	<form
		id="<?php echo esc_attr( $args['id'] ); ?>"
		class="<?php echo esc_attr( $args['css_class'] ); ?>"
		method="<?php echo esc_attr( $args['method'] ); ?>"
		action="<?php echo esc_url( $args['action'] ); ?>"
		data-form-type="<?php echo esc_attr( $form_type ); ?>"
	>
		<?php wp_nonce_field( 'apollo_form_' . $form_type, 'apollo_form_nonce' ); ?>
		<input type="hidden" name="form_type" value="<?php echo esc_attr( $form_type ); ?>">

		<?php foreach ( $schema as $field ) : ?>
			<?php if ( $field['visible'] ) : ?>
				<?php echo apollo_render_field( $field, $args['values'] ); ?>
			<?php endif; ?>
		<?php endforeach; ?>

		<div class="apollo-form-submit">
			<button type="submit" class="apollo-button apollo-button-primary">
				<?php esc_html_e( 'Submit', 'apollo-core' ); ?>
			</button>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Render single form field
 *
 * @param array $field  Field schema.
 * @param array $values Current values.
 * @return string HTML field output.
 */
function apollo_render_field( $field, $values = [] ) {
	$value = isset( $values[ $field['key'] ] ) ? $values[ $field['key'] ] : $field['default'];

	$required_attr = $field['required'] ? 'required' : '';
	$required_mark = $field['required'] ? '<span class="apollo-required">*</span>' : '';

	ob_start();
	?>
	<div class="apollo-form-field apollo-field-<?php echo esc_attr( $field['type'] ); ?>" data-field-key="<?php echo esc_attr( $field['key'] ); ?>">
		<label for="apollo-field-<?php echo esc_attr( $field['key'] ); ?>" class="apollo-field-label">
			<?php echo esc_html( $field['label'] ); ?>
			<?php echo wp_kses_post( $required_mark ); ?>
		</label>

		<?php
		switch ( $field['type'] ) {
			case 'textarea':
				?>
				<textarea
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-textarea"
					<?php echo esc_attr( $required_attr ); ?>
					rows="5"
				><?php echo esc_textarea( $value ); ?></textarea>
				<?php
				break;

			case 'select':
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
				?>
			<select
				id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
				name="<?php echo esc_attr( $field['key'] ); ?>"
				class="apollo-input apollo-select"
				<?php echo esc_attr( $required_attr ); ?>
			>
				<option value=""><?php esc_html_e( 'Select...', 'apollo-core' ); ?></option>
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option
						value="<?php echo esc_attr( $option_value ); ?>"
						<?php selected( $value, $option_value ); ?>
					>
						<?php echo esc_html( $option_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
				<?php
				break;

			case 'checkbox':
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];

				if ( empty( $options ) ) {
					// Single checkbox
					?>
				<label class="apollo-checkbox-label">
					<input
						type="checkbox"
						id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
						name="<?php echo esc_attr( $field['key'] ); ?>"
						class="apollo-checkbox"
						value="1"
						<?php checked( $value, 1 ); ?>
						<?php echo esc_attr( $required_attr ); ?>
					>
					<span><?php echo esc_html( $field['label'] ); ?></span>
				</label>
					<?php
				} else {
					// Multiple checkboxes
					$selected_values = is_array( $value ) ? $value : ( ! empty( $value ) ? explode( ',', $value ) : [] );
					?>
				<div class="apollo-checkbox-group">
					<?php foreach ( $options as $option_value => $option_label ) : ?>
						<label class="apollo-checkbox-label">
							<input
								type="checkbox"
								name="<?php echo esc_attr( $field['key'] ); ?>[]"
								class="apollo-checkbox"
								value="<?php echo esc_attr( $option_value ); ?>"
								<?php checked( in_array( $option_value, $selected_values, true ) ); ?>
							>
							<span><?php echo esc_html( $option_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
					<?php
				}//end if

				break;

			case 'radio':
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
				?>
			<div class="apollo-radio-group">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<label class="apollo-radio-label">
						<input
							type="radio"
							name="<?php echo esc_attr( $field['key'] ); ?>"
							class="apollo-radio"
							value="<?php echo esc_attr( $option_value ); ?>"
							<?php checked( $value, $option_value ); ?>
							<?php echo esc_attr( $required_attr ); ?>
						>
						<span><?php echo esc_html( $option_label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
				<?php
				break;

			case 'instagram':
				?>
				<div class="apollo-instagram-field">
					<span class="apollo-instagram-prefix">@</span>
					<input
						type="text"
						id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
						name="<?php echo esc_attr( $field['key'] ); ?>"
						class="apollo-input apollo-instagram-input"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php esc_attr_e( 'username', 'apollo-core' ); ?>"
						pattern="[A-Za-z0-9_]{1,30}"
						maxlength="30"
						<?php echo esc_attr( $required_attr ); ?>
					>
				</div>
				<p class="apollo-field-help">
					<?php esc_html_e( 'Your Instagram username (letters, numbers, and underscores only)', 'apollo-core' ); ?>
				</p>
				<?php
				break;

			case 'password':
				?>
				<input
					type="password"
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-password"
					value="<?php echo esc_attr( $value ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
				>
				<?php
				break;

			case 'number':
				?>
				<input
					type="number"
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-number"
					value="<?php echo esc_attr( $value ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
				>
				<?php
				break;

			case 'date':
				?>
				<input
					type="date"
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-date"
					value="<?php echo esc_attr( $value ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
				>
				<?php
				break;

			case 'email':
				?>
				<input
					type="email"
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-email"
					value="<?php echo esc_attr( $value ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
				>
				<?php
				break;

			default:
				// text
				?>
				<input
					type="text"
					id="apollo-field-<?php echo esc_attr( $field['key'] ); ?>"
					name="<?php echo esc_attr( $field['key'] ); ?>"
					class="apollo-input apollo-text"
					value="<?php echo esc_attr( $value ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
				>
				<?php
				break;
		}//end switch
		?>

		<div class="apollo-field-error" style="display:none;"></div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Hook into user registration to save Instagram ID
 *
 * @param int $user_id User ID.
 */
function apollo_save_user_instagram_on_register( $user_id ) {
	if ( isset( $_POST['instagram_user_id'] ) && ! empty( $_POST['instagram_user_id'] ) ) {
		$instagram_id = sanitize_text_field( wp_unslash( $_POST['instagram_user_id'] ) );

		// Validate format.
		if ( preg_match( '/^[A-Za-z0-9_]{1,30}$/', $instagram_id ) ) {
			// Check uniqueness.
			if ( apollo_is_instagram_id_unique( $instagram_id, $user_id ) ) {
				update_user_meta( $user_id, '_apollo_instagram_id', $instagram_id );

				// Log change.
				if ( function_exists( 'apollo_mod_log_action' ) ) {
					apollo_mod_log_action(
						$user_id,
						'instagram_added',
						'user',
						$user_id,
						[ 'instagram_id' => $instagram_id ]
					);
				}
			}
		}
	}//end if
}
add_action( 'user_register', 'apollo_save_user_instagram_on_register' );

/**
 * Hook into profile update to save Instagram ID
 *
 * @param int $user_id User ID being updated.
 */
function apollo_save_user_instagram_on_profile_update( $user_id ) {
	if ( ! isset( $_POST['instagram_user_id'] ) ) {
		return;
	}

	$instagram_id = sanitize_text_field( wp_unslash( $_POST['instagram_user_id'] ) );

	// If empty, delete meta.
	if ( empty( $instagram_id ) ) {
		delete_user_meta( $user_id, '_apollo_instagram_id' );

		return;
	}

	// Validate format.
	if ( ! preg_match( '/^[A-Za-z0-9_]{1,30}$/', $instagram_id ) ) {
		return;
	}

	// Check uniqueness.
	if ( ! apollo_is_instagram_id_unique( $instagram_id, $user_id ) ) {
		return;
	}

	$old_instagram = get_user_meta( $user_id, '_apollo_instagram_id', true );
	update_user_meta( $user_id, '_apollo_instagram_id', $instagram_id );

	// Log change.
	if ( function_exists( 'apollo_mod_log_action' ) && $old_instagram !== $instagram_id ) {
		apollo_mod_log_action(
			$user_id,
			'instagram_updated',
			'user',
			$user_id,
			[
				'old' => $old_instagram,
				'new' => $instagram_id,
			]
		);
	}
}
add_action( 'profile_update', 'apollo_save_user_instagram_on_profile_update' );

/**
 * Add Instagram field to user profile edit screen
 *
 * @param WP_User $user User object.
 */
function apollo_add_instagram_field_to_profile( $user ) {
	$instagram_id = get_user_meta( $user->ID, '_apollo_instagram_id', true );
	?>
	<h3><?php esc_html_e( 'Apollo Social', 'apollo-core' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="instagram_user_id"><?php esc_html_e( 'Instagram ID', 'apollo-core' ); ?></label></th>
			<td>
				<div style="display: flex; align-items: center;">
					<span style="margin-right: 5px;">@</span>
					<input
						type="text"
						name="instagram_user_id"
						id="instagram_user_id"
						value="<?php echo esc_attr( $instagram_id ); ?>"
						class="regular-text"
						pattern="[A-Za-z0-9_]{1,30}"
						maxlength="30"
					>
				</div>
				<p class="description"><?php esc_html_e( 'Your Instagram username (letters, numbers, and underscores only, max 30 characters)', 'apollo-core' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'apollo_add_instagram_field_to_profile' );
add_action( 'edit_user_profile', 'apollo_add_instagram_field_to_profile' );

/**
 * Display Instagram ID on user public page
 *
 * @param int $user_id User ID.
 * @return string Instagram display HTML.
 */
function apollo_display_user_instagram( $user_id ) {
	$instagram_id = get_user_meta( $user_id, '_apollo_instagram_id', true );

	if ( empty( $instagram_id ) ) {
		return '';
	}

	$instagram_url = 'https://instagram.com/' . urlencode( $instagram_id );

	return sprintf(
		'<a href="%s" target="_blank" rel="noopener" class="apollo-instagram-link">@%s</a>',
		esc_url( $instagram_url ),
		esc_html( $instagram_id )
	);
}
