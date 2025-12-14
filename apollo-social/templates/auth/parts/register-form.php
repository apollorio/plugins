<?php
/**
 * ================================================================================
 * APOLLO AUTH - Registration Form Template Part
 * ================================================================================
 * Displays the registration form with all required fields.
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * FIELDS (Updated as per user request):
 * - Nome social & Sobrenome (full name)
 * - Instagram (@username)
 * - CPF / Passport (with type selector)
 * - E-mail (changed from "E-mail principal")
 * - Crie sua chave de acesso (password)
 * - Gêneros Musicais (SOUNDS - mandatory, at least 1)
 * - Terms & Privacy Policy (links to apollo.rio.br/politica)
 *
 * PLACEHOLDERS:
 * - {{name_label}} - Label for name field
 * - {{instagram_label}} - Label for Instagram field
 * - {{doc_type_label}} - Label for document type
 * - {{cpf_label}} - Label for CPF field
 * - {{passport_label}} - Label for Passport field
 * - {{email_label}} - Label for email field
 * - {{password_label}} - Label for password field
 * - {{sounds_label}} - Label for sounds selection
 * - {{terms_label}} - Label for terms toggle
 * ================================================================================
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Get configuration values
$terms_url        = isset($auth_config['terms_url']) ? $auth_config['terms_url'] : 'https://apollo.rio.br/politica';
$show_instagram   = isset($auth_config['show_instagram']) ? $auth_config['show_instagram'] : true;
$available_sounds = isset($available_sounds) ? $available_sounds : [];
?>

<!-- Status Messages -->
<div class="flavor-text" data-tooltip="<?php esc_attr_e('Instruções de registro', 'apollo-social'); ?>">
	<span>> <?php esc_html_e('Registro de novo operador', 'apollo-social'); ?></span>
</div>
<div class="flavor-text" style="margin-bottom: 16px;">
	<span>> <?php esc_html_e('Preencha todos os campos obrigatórios', 'apollo-social'); ?></span>
</div>

<!-- Registration Form -->
<form id="register-form" method="post" data-tooltip="<?php esc_attr_e('Formulário de registro', 'apollo-social'); ?>">
	
	<?php wp_nonce_field('apollo_register_nonce', 'apollo_register_nonce'); ?>
	
	<!-- Name Field -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Campo de nome', 'apollo-social'); ?>">
		<label for="nome"><?php esc_html_e('Nome social & Sobrenome', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div class="input-wrapper">
			<span class="input-prefix">></span>
			<input 
				type="text" 
				id="nome" 
				name="nome" 
				placeholder="<?php esc_attr_e('Como você é conhecid@', 'apollo-social'); ?>"
				autocomplete="name"
				required
				data-tooltip="<?php esc_attr_e('Digite seu nome social e sobrenome', 'apollo-social'); ?>"
			>
		</div>
	</div>

	<?php if ($show_instagram) : ?>
	<!-- Instagram Field -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Campo de Instagram', 'apollo-social'); ?>">
		<label for="instagram"><?php esc_html_e('Instagram', 'apollo-social'); ?></label>
		<div class="input-wrapper">
			<span class="input-prefix" style="color: var(--color-accent); font-weight: bold;" data-tooltip="<?php esc_attr_e('Prefixo fixo @', 'apollo-social'); ?>">@</span>
			<input 
				type="text" 
				id="instagram" 
				name="instagram" 
				placeholder="<?php esc_attr_e('seu_usuario', 'apollo-social'); ?>"
				autocomplete="off"
				style="padding-left: 26px;"
				data-tooltip="<?php esc_attr_e('Digite apenas seu usuário sem @', 'apollo-social'); ?>"
			>
		</div>
		<p class="small-note"><?php esc_html_e('Será seu ID Apollo. Digite apenas o usuário.', 'apollo-social'); ?></p>
	</div>
	<?php endif; ?>

	<!-- Document Type Selector -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Tipo de documento', 'apollo-social'); ?>">
		<label for="doc_type"><?php esc_html_e('Tipo de Documento', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div style="display: flex; gap: 12px; margin-bottom: 12px;">
			<label class="custom-toggle doc-type-toggle" data-type="cpf" data-tooltip="<?php esc_attr_e('Selecionar CPF', 'apollo-social'); ?>">
				<div class="toggle-track" style="width: auto; padding: 6px 12px;">
					<span style="font-size: 11px;"><?php esc_html_e('CPF', 'apollo-social'); ?></span>
				</div>
			</label>
			<label class="custom-toggle doc-type-toggle" data-type="passport" data-tooltip="<?php esc_attr_e('Selecionar Passaporte', 'apollo-social'); ?>">
				<div class="toggle-track" style="width: auto; padding: 6px 12px;">
					<span style="font-size: 11px;"><?php esc_html_e('Passaporte', 'apollo-social'); ?></span>
				</div>
			</label>
		</div>
		<input type="hidden" name="doc_type" id="doc_type" value="cpf">
	</div>

	<!-- CPF Field (shown by default) -->
	<div class="form-group" id="cpf-field" data-tooltip="<?php esc_attr_e('Campo de CPF', 'apollo-social'); ?>">
		<label for="cpf"><?php esc_html_e('CPF', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div class="input-wrapper">
			<span class="input-prefix">></span>
			<input 
				type="text" 
				id="cpf" 
				name="cpf" 
				placeholder="000.000.000-00"
				maxlength="14"
				autocomplete="off"
				data-tooltip="<?php esc_attr_e('Digite seu CPF (necessário para assinatura digital)', 'apollo-social'); ?>"
			>
		</div>
		<p class="small-note"><?php esc_html_e('Necessário para assinatura digital de documentos.', 'apollo-social'); ?></p>
	</div>

	<!-- Passport Fields (hidden by default) -->
	<div class="form-group hidden" id="passport-field" data-tooltip="<?php esc_attr_e('Campo de Passaporte', 'apollo-social'); ?>">
		<label for="passport"><?php esc_html_e('Número do Passaporte', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div class="input-wrapper">
			<span class="input-prefix">></span>
			<input 
				type="text" 
				id="passport" 
				name="passport" 
				placeholder="ABC123456"
				maxlength="20"
				style="text-transform: uppercase;"
				autocomplete="off"
				data-tooltip="<?php esc_attr_e('Digite o número do seu passaporte', 'apollo-social'); ?>"
			>
		</div>
		<div class="input-wrapper" style="margin-top: 10px;">
			<span class="input-prefix">></span>
			<input 
				type="text" 
				id="passport_country" 
				name="passport_country" 
				placeholder="<?php esc_attr_e('País de emissão', 'apollo-social'); ?>"
				autocomplete="off"
				data-tooltip="<?php esc_attr_e('País que emitiu seu passaporte', 'apollo-social'); ?>"
			>
		</div>
		<!-- Passport Warning -->
		<div style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); border-radius: 8px; padding: 10px; margin-top: 10px;" data-tooltip="<?php esc_attr_e('Aviso importante para usuários com passaporte', 'apollo-social'); ?>">
			<p style="font-size: 11px; color: #fca5a5; margin: 0;">
				<i class="ri-alert-fill" style="margin-right: 4px;"></i>
				<?php esc_html_e('Usuários com passaporte NÃO poderão assinar documentos digitais. Assinatura digital requer CPF válido (Lei 14.063/2020).', 'apollo-social'); ?>
			</p>
		</div>
	</div>

	<!-- Email Field -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Campo de e-mail', 'apollo-social'); ?>">
		<label for="email"><?php esc_html_e('E-mail', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div class="input-wrapper">
			<span class="input-prefix">></span>
			<input 
				type="email" 
				id="email" 
				name="email" 
				placeholder="<?php esc_attr_e('seu@email.com', 'apollo-social'); ?>"
				autocomplete="email"
				required
				data-tooltip="<?php esc_attr_e('Digite seu e-mail principal', 'apollo-social'); ?>"
			>
		</div>
	</div>

	<!-- Password Field -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Campo de senha', 'apollo-social'); ?>">
		<label for="senha"><?php esc_html_e('Crie sua chave de acesso', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<div class="input-wrapper">
			<span class="input-prefix">></span>
			<input 
				type="password" 
				id="senha" 
				name="senha" 
				placeholder="<?php esc_attr_e('mínimo 8 caracteres', 'apollo-social'); ?>"
				minlength="8"
				autocomplete="new-password"
				required
				data-tooltip="<?php esc_attr_e('Crie uma senha forte com pelo menos 8 caracteres', 'apollo-social'); ?>"
			>
		</div>
	</div>

	<!-- Musical Genres (SOUNDS) - MANDATORY -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Seleção de gêneros musicais', 'apollo-social'); ?>">
		<label><?php esc_html_e('Gêneros Musicais', 'apollo-social'); ?> <span style="color: var(--color-accent);">*</span></label>
		<p class="small-note" style="margin-bottom: 10px;"><?php esc_html_e('Selecione pelo menos 1 gênero que você curte.', 'apollo-social'); ?></p>
		<div class="sounds-chips" style="display: flex; flex-wrap: wrap; gap: 8px;" data-tooltip="<?php esc_attr_e('Clique para selecionar', 'apollo-social'); ?>">
			<?php foreach ($available_sounds as $slug => $name) : ?>
				<label class="quiz-chip sound-chip" data-value="<?php echo esc_attr($slug); ?>" data-tooltip="<?php echo esc_attr($name); ?>">
					<input type="checkbox" name="sounds[]" value="<?php echo esc_attr($slug); ?>" style="display: none;">
					<?php echo esc_html($name); ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Terms & Privacy Policy -->
	<div class="form-group" data-tooltip="<?php esc_attr_e('Aceite dos termos', 'apollo-social'); ?>">
		<div class="custom-toggle terms-toggle" data-tooltip="<?php esc_attr_e('Clique para aceitar', 'apollo-social'); ?>">
			<div class="toggle-track">
				<div class="toggle-thumb"></div>
			</div>
			<span style="font-size: 11px; line-height: 1.4;">
				<?php esc_html_e('Li e aceito o', 'apollo-social'); ?>
				<a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-accent); text-decoration: underline;" data-tooltip="<?php esc_attr_e('Abrir em nova aba', 'apollo-social'); ?>">
					<?php esc_html_e('Protocolo de Convivência, Termos e Política de Privacidade de Uso Coletivo no Apollo', 'apollo-social'); ?>
				</a>
			</span>
			<input type="hidden" name="terms_accepted" value="0">
		</div>
	</div>

	<!-- Submit Button -->
	<button type="submit" class="btn-primary" data-tooltip="<?php esc_attr_e('Prosseguir para o teste de aptidão', 'apollo-social'); ?>">
		<span><?php esc_html_e('PROSSEGUIR', 'apollo-social'); ?></span>
		<i class="ri-arrow-right-line"></i>
	</button>

</form>

<!-- Login Link -->
<div style="text-align: center; margin-top: 20px;" data-tooltip="<?php esc_attr_e('Link para login', 'apollo-social'); ?>">
	<p style="font-size: 12px; color: rgba(148,163,184,0.9);">
		<?php esc_html_e('Já possui acesso?', 'apollo-social'); ?>
		<button type="button" class="btn-text" id="switch-to-login" data-tooltip="<?php esc_attr_e('Voltar para login', 'apollo-social'); ?>">
			<?php esc_html_e('Acessar terminal', 'apollo-social'); ?>
		</button>
	</p>
</div>

<!-- Document Type Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	const docTypeToggles = document.querySelectorAll('.doc-type-toggle');
	const cpfField = document.getElementById('cpf-field');
	const passportField = document.getElementById('passport-field');
	const docTypeInput = document.getElementById('doc_type');
	const cpfInput = document.getElementById('cpf');
	const passportInput = document.getElementById('passport');

	// Initialize - CPF selected by default
	docTypeToggles[0].classList.add('active');

	docTypeToggles.forEach(toggle => {
		toggle.addEventListener('click', function() {
			const type = this.getAttribute('data-type');
			
			// Update active state
			docTypeToggles.forEach(t => t.classList.remove('active'));
			this.classList.add('active');
			
			// Update hidden input
			docTypeInput.value = type;
			
			// Toggle fields
			if (type === 'cpf') {
				cpfField.classList.remove('hidden');
				passportField.classList.add('hidden');
				cpfInput.required = true;
				passportInput.required = false;
			} else {
				cpfField.classList.add('hidden');
				passportField.classList.remove('hidden');
				cpfInput.required = false;
				passportInput.required = true;
			}
		});
	});

	// CPF Mask
	const cpf = document.getElementById('cpf');
	if (cpf) {
		cpf.addEventListener('input', function(e) {
			let value = e.target.value.replace(/\D/g, '');
			if (value.length <= 11) {
				value = value.replace(/(\d{3})(\d)/, '$1.$2');
				value = value.replace(/(\d{3})(\d)/, '$1.$2');
				value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
				e.target.value = value;
			}
		});
	}

	// Sound chips selection
	const soundChips = document.querySelectorAll('.sound-chip');
	soundChips.forEach(chip => {
		chip.addEventListener('click', function() {
			this.classList.toggle('selected');
			const checkbox = this.querySelector('input[type="checkbox"]');
			if (checkbox) {
				checkbox.checked = this.classList.contains('selected');
			}
		});
	});
});
</script>
