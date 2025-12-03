<?php
/**
 * Form Helpers
 * Helper functions for Apollo forms with Hold-to-Confirm security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render hold-to-confirm submit button
 *
 * @param array $args Button arguments
 * @return string HTML button
 */
function apollo_hold_to_confirm_button( $args = array() ) {
	$defaults = array(
		'text'           => __( 'Segure para Confirmar', 'apollo-social' ),
		'confirm_text'   => __( '✓ Confirmado', 'apollo-social' ),
		'hold_duration'  => 2000,
		'progress_color' => '#3b82f6',
		'success_color'  => '#10b981',
		'type'           => 'submit',
		'class'          => 'apollo-button apollo-button-primary',
		'id'             => '',
		'on_complete'    => '',
		'on_cancel'      => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$attributes = array(
		'type'                 => esc_attr( $args['type'] ),
		'class'                => esc_attr( $args['class'] ),
		'data-hold-to-confirm' => '',
		'data-hold-duration'   => absint( $args['hold_duration'] ),
		'data-progress-color'  => esc_attr( $args['progress_color'] ),
		'data-success-color'   => esc_attr( $args['success_color'] ),
		'data-confirm-text'    => esc_attr( $args['confirm_text'] ),
	);

	if ( ! empty( $args['id'] ) ) {
		$attributes['id'] = esc_attr( $args['id'] );
	}

	if ( ! empty( $args['on_complete'] ) ) {
		$attributes['data-on-complete'] = esc_attr( $args['on_complete'] );
	}

	if ( ! empty( $args['on_cancel'] ) ) {
		$attributes['data-on-cancel'] = esc_attr( $args['on_cancel'] );
	}

	$attr_string = '';
	foreach ( $attributes as $key => $value ) {
		if ( $value === '' ) {
			$attr_string .= ' ' . esc_attr( $key );
		} else {
			$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
	}

	return sprintf(
		'<button%s>%s</button>',
		$attr_string,
		esc_html( $args['text'] )
	);
}

/**
 * Apply hold-to-confirm to all submit buttons in a form
 *
 * @param string $form_selector CSS selector for the form
 * @return void
 */
function apollo_apply_hold_to_confirm_to_form( $form_selector ) {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const form = document.querySelector('<?php echo esc_js( $form_selector ); ?>');
		if (!form) return;

		const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
		
		submitButtons.forEach(function(button) {
			// Convert input to button if needed
			if (button.tagName === 'INPUT') {
				const newButton = document.createElement('button');
				newButton.type = 'submit';
				newButton.className = button.className;
				newButton.textContent = button.value;
				button.replaceWith(newButton);
				button = newButton;
			}

			// Add hold-to-confirm attributes
			button.setAttribute('data-hold-to-confirm', '');
			button.setAttribute('data-hold-duration', '2000');
			button.setAttribute('data-confirm-text', '<?php esc_js_e( '✓ Enviando...', 'apollo-social' ); ?>');
			
			// Add class if not present
			if (!button.classList.contains('apollo-button')) {
				button.classList.add('apollo-button', 'apollo-button-primary');
			}
		});
	});
	</script>
	<?php
}

/**
 * Get default hold-to-confirm button for registration
 */
function apollo_registration_button() {
	return apollo_hold_to_confirm_button(
		array(
			'text'          => __( 'Segure para Registrar', 'apollo-social' ),
			'confirm_text'  => __( '✓ Registrando...', 'apollo-social' ),
			'hold_duration' => 2000,
			'id'            => 'apollo-register-button',
		)
	);
}

/**
 * Get default hold-to-confirm button for post submission
 */
function apollo_post_submit_button() {
	return apollo_hold_to_confirm_button(
		array(
			'text'          => __( 'Segure para Publicar', 'apollo-social' ),
			'confirm_text'  => __( '✓ Publicando...', 'apollo-social' ),
			'hold_duration' => 1500,
		)
	);
}

/**
 * Get default hold-to-confirm button for comment submission
 */
function apollo_comment_submit_button() {
	return apollo_hold_to_confirm_button(
		array(
			'text'          => __( 'Segure para Comentar', 'apollo-social' ),
			'confirm_text'  => __( '✓ Enviando...', 'apollo-social' ),
			'hold_duration' => 1000,
		)
	);
}

