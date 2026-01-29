<?php
/**
 * Registration Form Template
 * Uses Hold-to-Confirm security system
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user
$user_obj     = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// If already logged in, redirect
if ( $is_logged_in ) {
	wp_redirect( home_url() );
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Registro', 'apollo-social' ); ?> - <?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo esc_url( APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/registration.css' ); ?>">
</head>
<body class="apollo-canvas apollo-registration">
	<div class="apollo-registration-container">
		<div class="apollo-registration-card">
			<div class="apollo-registration-header">
				<h1><?php esc_html_e( 'Criar Conta', 'apollo-social' ); ?></h1>
				<p><?php esc_html_e( 'Junte-se à comunidade Apollo', 'apollo-social' ); ?></p>
			</div>

			<form id="apollo-registration-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<?php wp_nonce_field( 'apollo_register', 'apollo_register_nonce' ); ?>
				<input type="hidden" name="action" value="apollo_register">

				<div class="apollo-form-group">
					<label for="apollo_username">
						<?php esc_html_e( 'Nome de Usuário', 'apollo-social' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="apollo_username"
						name="username"
						required
						autocomplete="username"
						placeholder="<?php esc_attr_e( 'Escolha um nome de usuário', 'apollo-social' ); ?>"
						title="<?php esc_attr_e( 'Nome de Usuário', 'apollo-social' ); ?>"
					>
					<span class="apollo-field-error" id="username-error"></span>
				</div>

				<div class="apollo-form-group">
					<label for="apollo_email">
						<?php esc_html_e( 'Email', 'apollo-social' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="email"
						id="apollo_email"
						name="email"
						required
						autocomplete="email"
						placeholder="<?php esc_attr_e( 'seu@email.com', 'apollo-social' ); ?>"
						title="<?php esc_attr_e( 'Email', 'apollo-social' ); ?>"
					>
					<span class="apollo-field-error" id="email-error"></span>
				</div>

				<div class="apollo-form-group">
					<label for="apollo_password">
						<?php esc_html_e( 'Senha', 'apollo-social' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="password"
						id="apollo_password"
						name="password"
						required
						autocomplete="new-password"
						placeholder="<?php esc_attr_e( 'Mínimo 8 caracteres', 'apollo-social' ); ?>"
						minlength="8"
						title="<?php esc_attr_e( 'Senha', 'apollo-social' ); ?>"
					>
					<span class="apollo-field-error" id="password-error"></span>
				</div>

				<div class="apollo-form-group">
					<label for="apollo_password_confirm">
						<?php esc_html_e( 'Confirmar Senha', 'apollo-social' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="password"
						id="apollo_password_confirm"
						name="password_confirm"
						required
						autocomplete="new-password"
						placeholder="<?php esc_attr_e( 'Digite a senha novamente', 'apollo-social' ); ?>"
						title="<?php esc_attr_e( 'Confirmar Senha', 'apollo-social' ); ?>"
					>
					<span class="apollo-field-error" id="password-confirm-error"></span>
				</div>

				<div class="apollo-form-group apollo-checkbox-group">
					<label>
						<input
							type="checkbox"
							name="terms"
							required
							id="apollo_terms"
						>
						<span><?php esc_html_e( 'Eu aceito os', 'apollo-social' ); ?>
							<a href="<?php echo esc_url( home_url( '/termos' ) ); ?>" target="_blank">
								<?php esc_html_e( 'Termos de Uso', 'apollo-social' ); ?>
							</a>
						</span>
					</label>
					<span class="apollo-field-error" id="terms-error"></span>
				</div>

				<div class="apollo-form-actions">
					<?php
					// Use helper function for consistent button rendering
					if ( function_exists( 'apollo_registration_button' ) ) {
						echo apollo_registration_button();
					} else {
						// Fallback if helper not loaded
						?>
						<button
							type="submit"
							id="apollo-register-button"
							class="apollo-button apollo-button-primary"
							data-hold-to-confirm
							data-hold-duration="2000"
							data-progress-color="#3b82f6"
							data-success-color="#10b981"
							data-confirm-text="<?php esc_attr_e( '✓ Registrando...', 'apollo-social' ); ?>"
						>
							<?php esc_html_e( 'Segure para Registrar', 'apollo-social' ); ?>
						</button>
						<?php
					}
					?>
				</div>

				<div class="apollo-form-footer">
					<p>
						<?php esc_html_e( 'Já tem uma conta?', 'apollo-social' ); ?>
						<a href="<?php echo esc_url( wp_login_url() ); ?>">
							<?php esc_html_e( 'Fazer Login', 'apollo-social' ); ?>
						</a>
					</p>
				</div>
			</form>
		</div>
	</div>

	<script>
	// Form validation
	document.addEventListener('DOMContentLoaded', function() {
		const form = document.getElementById('apollo-registration-form');
		const password = document.getElementById('apollo_password');
		const passwordConfirm = document.getElementById('apollo_password_confirm');

		// Password match validation
		function validatePasswords() {
			if (password.value !== passwordConfirm.value) {
				passwordConfirm.setCustomValidity('<?php esc_js_e( 'As senhas não coincidem', 'apollo-social' ); ?>');
				return false;
			} else {
				passwordConfirm.setCustomValidity('');
				return true;
			}
		}

		password.addEventListener('input', validatePasswords);
		passwordConfirm.addEventListener('input', validatePasswords);

		// Form submission handler
		form.addEventListener('submit', function(e) {
			if (!validatePasswords()) {
				e.preventDefault();
				return false;
			}
		});
	});
	</script>

	<?php wp_footer(); ?>
</body>
</html>
