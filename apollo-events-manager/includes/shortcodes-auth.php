<?php
// phpcs:ignoreFile
/**
 * Authentication Shortcodes
 * [apollo_register] - Registration form
 * [apollo_login] - Login form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registration Form Shortcode
 * Shortcode: [apollo_register]
 */
function apollo_register_shortcode( $atts ) {
	// If already logged in, show message
	if ( is_user_logged_in() ) {
		$current_user  = wp_get_current_user();
		$dashboard_url = home_url( '/my-apollo/' );
		return '<div class="apollo-auth-already-logged glass p-6 rounded-lg text-center">
            <h3 class="text-xl font-semibold mb-2">Você já está logado!</h3>
            <p class="mb-4">Olá, <strong>' . esc_html( $current_user->display_name ) . '</strong></p>
            <div class="flex gap-4 justify-center">
                <a href="' . esc_url( $dashboard_url ) . '" class="btn btn-primary">Meu Apollo</a>
                <a href="' . esc_url( wp_logout_url( get_permalink() ) ) . '" class="btn btn-secondary">Sair</a>
            </div>
        </div>';
	}

	// Handle registration submission
	$registration_success = false;
	$registration_errors  = array();

	if ( isset( $_POST['apollo_register_submit'] ) && isset( $_POST['apollo_register_nonce'] ) && wp_verify_nonce( $_POST['apollo_register_nonce'], 'apollo_register' ) ) {
		$username         = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '';
		$email            = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$password         = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';

		// Validation
		if ( empty( $username ) ) {
			$registration_errors[] = 'Nome de usuário é obrigatório.';
		} elseif ( username_exists( $username ) ) {
			$registration_errors[] = 'Este nome de usuário já está em uso.';
		} elseif ( ! validate_username( $username ) ) {
			$registration_errors[] = 'Nome de usuário inválido. Use apenas letras, números e underscore.';
		}

		if ( empty( $email ) ) {
			$registration_errors[] = 'Email é obrigatório.';
		} elseif ( ! is_email( $email ) ) {
			$registration_errors[] = 'Email inválido.';
		} elseif ( email_exists( $email ) ) {
			$registration_errors[] = 'Este email já está cadastrado.';
		}

		if ( empty( $password ) ) {
			$registration_errors[] = 'Senha é obrigatória.';
		} elseif ( strlen( $password ) < 6 ) {
			$registration_errors[] = 'Senha deve ter pelo menos 6 caracteres.';
		}

		if ( $password !== $password_confirm ) {
			$registration_errors[] = 'As senhas não coincidem.';
		}

		// Create user if no errors
		if ( empty( $registration_errors ) ) {
			$user_id = wp_create_user( $username, $password, $email );

			if ( ! is_wp_error( $user_id ) ) {
				// Set default role to 'clubber'
				$user = new WP_User( $user_id );
				$user->set_role( 'clubber' );

				// Add membership meta using canonical key from apollo-core/memberships.php
				update_user_meta( $user_id, '_apollo_membership', 'nao-verificado' );

				// Auto-login user
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );

				$registration_success = true;

				// Show success message
				$dashboard_url = home_url( '/my-apollo/' );
				return '<div class="apollo-register-success glass p-6 rounded-lg text-center bg-green-50 border border-green-200">
                    <h3 class="text-xl font-semibold text-green-800 mb-2">✓ Conta Criada com Sucesso!</h3>
                    <p class="text-green-700 mb-4">Bem-vindo ao Apollo, <strong>' . esc_html( $username ) . '</strong>!</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 text-left">
                        <p class="text-sm text-blue-800 mb-2"><strong>Próximo passo:</strong></p>
                        <p class="text-sm text-blue-700">Envie uma mensagem inbox para <strong>@apollo.rio.br</strong> com:</p>
                        <p class="text-sm text-blue-700 font-mono bg-blue-100 p-2 rounded mt-2">Meu user é @' . esc_html( $username ) . '</p>
                    </div>
                    <a href="' . esc_url( $dashboard_url ) . '" class="btn btn-primary">Ir para Meu Apollo</a>
                </div>';
			} else {
				$registration_errors[] = $user_id->get_error_message();
			}//end if
		}//end if
	}//end if

	ob_start();
	?>
	<div class="apollo-register-form-wrapper glass p-6 rounded-lg max-w-md mx-auto">
		<h2 class="text-2xl font-bold mb-6 text-center">Criar Conta</h2>

		<?php if ( ! empty( $registration_errors ) ) : ?>
			<div class="apollo-register-errors bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
				<h4 class="font-semibold text-red-800 mb-2">Erros encontrados:</h4>
				<ul class="list-disc list-inside text-red-700 text-sm">
					<?php foreach ( $registration_errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="apollo-register-form space-y-4" method="post">
			<?php wp_nonce_field( 'apollo_register', 'apollo_register_nonce' ); ?>

			<!-- Username -->
			<div>
				<label for="apollo_register_username" class="block text-sm font-medium mb-2">
					Nome de Usuário <span class="text-red-500">*</span>
				</label>
				<input
					type="text"
					id="apollo_register_username"
					name="username"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
					required
					autocomplete="username"
					value="<?php echo isset( $_POST['username'] ) ? esc_attr( $_POST['username'] ) : ''; ?>"
					placeholder="Escolha um nome de usuário"
				>
			</div>

			<!-- Email -->
			<div>
				<label for="apollo_register_email" class="block text-sm font-medium mb-2">
					Email <span class="text-red-500">*</span>
				</label>
				<input
					type="email"
					id="apollo_register_email"
					name="email"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
					required
					autocomplete="email"
					value="<?php echo isset( $_POST['email'] ) ? esc_attr( $_POST['email'] ) : ''; ?>"
					placeholder="seu@email.com"
				>
			</div>

			<!-- Password -->
			<div>
				<label for="apollo_register_password" class="block text-sm font-medium mb-2">
					Senha <span class="text-red-500">*</span>
				</label>
				<input
					type="password"
					id="apollo_register_password"
					name="password"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
					required
					autocomplete="new-password"
					minlength="6"
					placeholder="Mínimo 6 caracteres"
				>
			</div>

			<!-- Password Confirm -->
			<div>
				<label for="apollo_register_password_confirm" class="block text-sm font-medium mb-2">
					Confirmar Senha <span class="text-red-500">*</span>
				</label>
				<input
					type="password"
					id="apollo_register_password_confirm"
					name="password_confirm"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
					required
					autocomplete="new-password"
					minlength="6"
					placeholder="Digite a senha novamente"
				>
			</div>

			<!-- Submit Button -->
			<div>
				<button
					type="submit"
					name="apollo_register_submit"
					value="1"
					class="w-full btn btn-primary px-6 py-3"
				>
					<i class="ri-user-add-line"></i> Criar Conta
				</button>
			</div>
		</form>

		<div class="mt-6 text-center text-sm">
			<p>
				Já tem uma conta?
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="text-primary hover:underline">
					Fazer Login
				</a>
			</p>
		</div>
	</div>

	<script>
	// Password match validation
	document.addEventListener('DOMContentLoaded', function() {
		const form = document.querySelector('.apollo-register-form');
		const password = document.getElementById('apollo_register_password');
		const passwordConfirm = document.getElementById('apollo_register_password_confirm');

		if (form && password && passwordConfirm) {
			function validatePasswords() {
				if (passwordConfirm.value && password.value !== passwordConfirm.value) {
					passwordConfirm.setCustomValidity('As senhas não coincidem');
				} else {
					passwordConfirm.setCustomValidity('');
				}
			}

			password.addEventListener('input', validatePasswords);
			passwordConfirm.addEventListener('input', validatePasswords);
		}
	});
	</script>
	<?php
	return ob_get_clean();
}

/**
 * Login Form Shortcode
 * Shortcode: [apollo_login]
 */
function apollo_login_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'redirect' => '',
		),
		$atts
	);

	// If already logged in, show message
	if ( is_user_logged_in() ) {
		$current_user  = wp_get_current_user();
		$dashboard_url = ! empty( $atts['redirect'] ) ? $atts['redirect'] : home_url( '/my-apollo/' );
		return '<div class="apollo-auth-already-logged glass p-6 rounded-lg text-center">
            <h3 class="text-xl font-semibold mb-2">Você já está logado!</h3>
            <p class="mb-4">Olá, <strong>' . esc_html( $current_user->display_name ) . '</strong></p>
            <div class="flex gap-4 justify-center">
                <a href="' . esc_url( $dashboard_url ) . '" class="btn btn-primary">Meu Apollo</a>
                <a href="' . esc_url( wp_logout_url( get_permalink() ) ) . '" class="btn btn-secondary">Sair</a>
            </div>
        </div>';
	}

	// Determine redirect URL
	$redirect_to = ! empty( $atts['redirect'] ) ? $atts['redirect'] : ( isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : get_permalink() );

	ob_start();
	?>
	<div class="apollo-login-form-wrapper glass p-6 rounded-lg max-w-md mx-auto">
		<h2 class="text-2xl font-bold mb-6 text-center">Entrar</h2>

		<?php
		// Show login errors if any
		if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
			echo '<div class="apollo-login-error bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-red-700 text-sm">Usuário ou senha incorretos. Tente novamente.</p>
            </div>';
		}
		?>

		<?php
		// Use WordPress login form
		$login_args = array(
			'redirect'       => $redirect_to,
			'form_id'        => 'apollo_login_form',
			'label_username' => 'Nome de Usuário ou Email',
			'label_password' => 'Senha',
			'label_remember' => 'Lembrar-me',
			'label_log_in'   => 'Entrar',
			'id_username'    => 'apollo_login_username',
			'id_password'    => 'apollo_login_password',
			'id_remember'    => 'apollo_login_remember',
			'id_submit'      => 'apollo_login_submit',
			'remember'       => true,
			'value_username' => isset( $_POST['log'] ) ? esc_attr( $_POST['log'] ) : '',
		);

		wp_login_form( $login_args );
		?>

		<div class="mt-6 space-y-3 text-center text-sm">
			<p>
				<a href="<?php echo esc_url( wp_lostpassword_url( get_permalink() ) ); ?>" class="text-primary hover:underline">
					Esqueceu sua senha?
				</a>
			</p>
			<p>
				Não tem uma conta?
				<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="text-primary hover:underline">
					Criar Conta
				</a>
			</p>
		</div>
	</div>

	<style>
	.apollo-login-form-wrapper form {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}
	.apollo-login-form-wrapper label {
		display: block;
		font-weight: 500;
		margin-bottom: 0.5rem;
	}
	.apollo-login-form-wrapper input[type="text"],
	.apollo-login-form-wrapper input[type="password"] {
		width: 100%;
		padding: 0.5rem 1rem;
		border: 1px solid #e2e8f0;
		border-radius: 0.5rem;
	}
	.apollo-login-form-wrapper input[type="checkbox"] {
		margin-right: 0.5rem;
	}
	.apollo-login-form-wrapper #apollo_login_submit {
		width: 100%;
		padding: 0.75rem;
		background: var(--primary-color, #3b82f6);
		color: white;
		border: none;
		border-radius: 0.5rem;
		cursor: pointer;
		font-weight: 500;
	}
	.apollo-login-form-wrapper #apollo_login_submit:hover {
		opacity: 0.9;
	}
	</style>
	<?php
	return ob_get_clean();
}

// Register shortcodes
add_shortcode( 'apollo_register', 'apollo_register_shortcode' );
add_shortcode( 'apollo_login', 'apollo_login_shortcode' );

