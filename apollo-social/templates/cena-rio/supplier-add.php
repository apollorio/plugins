<?php
/**
 * Add Supplier Template - Canvas Mode
 *
 * Form for creating a new supplier in the Cena-Rio catalog.
 * Standalone HTML template (no get_header/get_footer).
 *
 * @package Apollo\Templates\CenaRio
 * @since   1.0.0
 */

declare( strict_types = 1 );

use Apollo\Modules\Suppliers\SuppliersModule;
use Apollo\Domain\Suppliers\SupplierService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get service.
$service = SuppliersModule::get_service();

// Check if user can manage (add) suppliers.
if ( ! $service->user_can_manage() ) {
	wp_safe_redirect( home_url( '/fornece/' ) );
	exit;
}

// Get filter options for dropdowns.
$categories     = SupplierService::get_category_labels();
$regions        = SupplierService::get_region_labels();
$neighborhoods  = SupplierService::get_neighborhood_labels();
$event_types    = SupplierService::get_event_type_labels();
$supplier_types = SupplierService::get_supplier_type_labels();
$modes          = SupplierService::get_mode_labels();
$badges         = SupplierService::get_badge_labels();

// Current user info.
$apollo_user        = wp_get_current_user();
$apollo_user_avatar = get_avatar_url( $apollo_user->ID, array( 'size' => 64 ) );
$apollo_user_name   = $apollo_user->display_name ? $apollo_user->display_name : 'Usuário';

// Nonce for form submission.
$nonce = wp_create_nonce( 'apollo_add_supplier' );

// Form success/error messages.
$form_message = '';
$form_error   = false;

// Handle form submission.
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['apollo_supplier_nonce'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_supplier_nonce'] ) ), 'apollo_add_supplier' ) ) {
		$form_message = 'Erro de segurança. Tente novamente.';
		$form_error   = true;
	} else {
		// Collect and sanitize data.
		$supplier_data = array(
			'name'              => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description'       => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'category'          => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '',
			'region'            => isset( $_POST['region'] ) ? sanitize_key( wp_unslash( $_POST['region'] ) ) : '',
			'neighborhood'      => isset( $_POST['neighborhood'] ) ? sanitize_key( wp_unslash( $_POST['neighborhood'] ) ) : '',
			'event_types'       => isset( $_POST['event_types'] ) && is_array( $_POST['event_types'] )
				? array_map( 'sanitize_key', wp_unslash( $_POST['event_types'] ) )
				: array(),
			'type'              => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '',
			'mode'              => isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '',
			'contact_whatsapp'  => isset( $_POST['contact_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_whatsapp'] ) ) : '',
			'contact_instagram' => isset( $_POST['contact_instagram'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_instagram'] ) ) : '',
			'contact_email'     => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			'contact_phone'     => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
			'contact_website'   => isset( $_POST['contact_website'] ) ? esc_url_raw( wp_unslash( $_POST['contact_website'] ) ) : '',
			'tags'              => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
		);

		// Validate required fields.
		if ( empty( $supplier_data['name'] ) ) {
			$form_message = 'O nome do fornecedor é obrigatório.';
			$form_error   = true;
		} elseif ( empty( $supplier_data['category'] ) ) {
			$form_message = 'Selecione uma categoria.';
			$form_error   = true;
		} else {
			// Create supplier.
			$result = $service->create_supplier( $supplier_data );

			if ( is_wp_error( $result ) ) {
				$form_message = $result->get_error_message();
				$form_error   = true;
			} else {
				// Redirect to the new supplier.
				wp_safe_redirect( home_url( '/fornece/' . absint( $result ) . '/' ) );
				exit;
			}
		}
	}
}

// Enqueue assets.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', array(), '2.0.0' );
		wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );
		wp_enqueue_script( 'apollo-base-js', 'https://assets.apollo.rio.br/base.js', array(), '2.0.0', true );
	},
	5
);

if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full w-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
	<title>Adicionar Fornecedor - Cena::Rio - Apollo::Rio</title>
	<?php wp_head(); ?>
	<style>
		:root {
			--ap-font-primary: "Urbanist", system-ui, -apple-system, sans-serif;
			--ap-bg-main: #ffffff;
			--ap-bg-surface: #f8fafc;
			--nav-height: 70px;
		}

		*, *::before, *::after {
			box-sizing: border-box;
			-webkit-tap-highlight-color: transparent;
		}

		body {
			font-family: var(--ap-font-primary);
			background-color: var(--ap-bg-surface);
			color: #0f172a;
			min-height: 100vh;
			margin: 0;
			padding: 0;
		}

		::-webkit-scrollbar { width: 6px; }
		::-webkit-scrollbar-track { background: transparent; }
		::-webkit-scrollbar-thumb { background-color: rgba(148, 163, 184, 0.4); border-radius: 999px; }
		.pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }

		/* Navbar */
		.navbar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 1000;
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0 1rem;
			height: var(--nav-height);
			background: rgba(255, 255, 255, 0.85);
			backdrop-filter: blur(16px);
			-webkit-backdrop-filter: blur(16px);
		}

		.nav-btn {
			width: 42px;
			height: 42px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #64748b;
			transition: all 0.2s ease;
			background: transparent;
			border: none;
			cursor: pointer;
			text-decoration: none;
		}

		.nav-btn:hover {
			background: rgba(0, 0, 0, 0.05);
			color: #FF6925;
		}

		/* Form Styles */
		.form-card {
			background: white;
			border: 1px solid #e2e8f0;
			border-radius: 24px;
			padding: 24px;
		}

		.form-section {
			margin-bottom: 24px;
			padding-bottom: 24px;
			border-bottom: 1px solid #f1f5f9;
		}

		.form-section:last-child {
			margin-bottom: 0;
			padding-bottom: 0;
			border-bottom: none;
		}

		.form-label {
			display: block;
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0f172a;
			margin-bottom: 8px;
		}

		.form-label .required {
			color: #ef4444;
			margin-left: 2px;
		}

		.form-input,
		.form-textarea,
		.form-select {
			width: 100%;
			padding: 12px 16px;
			font-size: 14px;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			background: white;
			color: #0f172a;
			transition: all 0.2s;
		}

		.form-input:focus,
		.form-textarea:focus,
		.form-select:focus {
			outline: none;
			border-color: #FF6925;
			box-shadow: 0 0 0 3px rgba(255, 105, 37, 0.1);
		}

		.form-textarea {
			resize: vertical;
			min-height: 120px;
		}

		.form-hint {
			font-size: 11px;
			color: #94a3b8;
			margin-top: 6px;
		}

		.form-group {
			margin-bottom: 16px;
		}

		.form-grid {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 16px;
		}

		@media (max-width: 640px) {
			.form-grid {
				grid-template-columns: 1fr;
			}
		}

		/* Checkbox Group */
		.checkbox-group {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}

		.checkbox-item {
			display: flex;
			align-items: center;
			gap: 6px;
			padding: 8px 12px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			cursor: pointer;
			transition: all 0.2s;
		}

		.checkbox-item:hover {
			border-color: #cbd5e1;
		}

		.checkbox-item input {
			display: none;
		}

		.checkbox-item.checked {
			background: #0f172a;
			border-color: #0f172a;
			color: white;
		}

		.checkbox-item span {
			font-size: 12px;
			font-weight: 500;
		}

		/* Upload Area */
		.upload-area {
			border: 2px dashed #e2e8f0;
			border-radius: 16px;
			padding: 24px;
			text-align: center;
			cursor: pointer;
			transition: all 0.2s;
		}

		.upload-area:hover {
			border-color: #FF6925;
			background: #fffbf7;
		}

		.upload-area input {
			display: none;
		}

		/* Buttons */
		.btn-primary {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			padding: 14px 28px;
			background: #0f172a;
			color: white;
			font-size: 14px;
			font-weight: 700;
			border: none;
			border-radius: 12px;
			cursor: pointer;
			transition: all 0.2s;
		}

		.btn-primary:hover {
			background: #1e293b;
		}

		.btn-primary:active {
			transform: scale(0.98);
		}

		.btn-secondary {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			padding: 14px 28px;
			background: white;
			color: #64748b;
			font-size: 14px;
			font-weight: 600;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			cursor: pointer;
			transition: all 0.2s;
			text-decoration: none;
		}

		.btn-secondary:hover {
			background: #f8fafc;
			color: #0f172a;
		}

		/* Alert */
		.alert {
			padding: 14px 18px;
			border-radius: 12px;
			font-size: 14px;
			font-weight: 500;
			margin-bottom: 24px;
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.alert-error {
			background: #fef2f2;
			color: #dc2626;
			border: 1px solid #fecaca;
		}

		.alert-success {
			background: #f0fdf4;
			color: #16a34a;
			border: 1px solid #bbf7d0;
		}
	</style>
</head>
<body class="apollo-canvas">

	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="flex items-center gap-3">
			<a href="<?php echo esc_url( home_url( '/fornece/' ) ); ?>" class="nav-btn" aria-label="Voltar">
				<i class="ri-arrow-left-line text-xl"></i>
			</a>
			<span class="text-sm font-bold text-slate-900">Adicionar Fornecedor</span>
		</div>

		<div class="flex items-center gap-2">
			<div class="clock-pill hidden sm:block px-4 py-2 text-xs font-semibold text-slate-500" id="digital-clock"></div>
		</div>
	</nav>

	<!-- MAIN CONTENT -->
	<main class="pt-[90px] px-4 pb-8 max-w-2xl mx-auto">

		<?php if ( $form_message ) : ?>
		<div class="alert <?php echo $form_error ? 'alert-error' : 'alert-success'; ?>">
			<i class="<?php echo $form_error ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'; ?> text-lg"></i>
			<?php echo esc_html( $form_message ); ?>
		</div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data" class="form-card">
			<?php wp_nonce_field( 'apollo_add_supplier', 'apollo_supplier_nonce' ); ?>

			<!-- Basic Info -->
			<div class="form-section">
				<h2 class="text-lg font-bold text-slate-900 mb-4">Informações Básicas</h2>

				<div class="form-group">
					<label class="form-label" for="name">
						Nome do Fornecedor <span class="required">*</span>
					</label>
					<input
						type="text"
						id="name"
						name="name"
						class="form-input"
						placeholder="Ex: Studio Foto Premium"
						required
						value="<?php echo isset( $_POST['name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : ''; ?>"
					>
				</div>

				<div class="form-group">
					<label class="form-label" for="description">Descrição</label>
					<textarea
						id="description"
						name="description"
						class="form-textarea"
						placeholder="Descreva os serviços oferecidos..."
					><?php echo isset( $_POST['description'] ) ? esc_textarea( wp_unslash( $_POST['description'] ) ) : ''; ?></textarea>
					<p class="form-hint">Até 500 caracteres. Seja claro e objetivo.</p>
				</div>

				<div class="form-grid">
					<div class="form-group">
						<label class="form-label" for="category">
							Categoria <span class="required">*</span>
						</label>
						<select id="category" name="category" class="form-select" required>
							<option value="">Selecione...</option>
							<?php foreach ( $categories as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_POST['category'] ) && sanitize_key( wp_unslash( $_POST['category'] ) ) === $slug ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label class="form-label" for="region">Região</label>
						<select id="region" name="region" class="form-select">
							<option value="">Selecione...</option>
							<?php foreach ( $regions as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_POST['region'] ) && sanitize_key( wp_unslash( $_POST['region'] ) ) === $slug ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label" for="neighborhood">Bairro</label>
					<select id="neighborhood" name="neighborhood" class="form-select">
						<option value="">Selecione...</option>
						<?php foreach ( $neighborhoods as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_POST['neighborhood'] ) && sanitize_key( wp_unslash( $_POST['neighborhood'] ) ) === $slug ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Classification -->
			<div class="form-section">
				<h2 class="text-lg font-bold text-slate-900 mb-4">Classificação</h2>

				<div class="form-grid">
					<div class="form-group">
						<label class="form-label" for="type">Tipo de Fornecedor</label>
						<select id="type" name="type" class="form-select">
							<option value="">Selecione...</option>
							<?php foreach ( $supplier_types as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_POST['type'] ) && sanitize_key( wp_unslash( $_POST['type'] ) ) === $slug ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label class="form-label" for="mode">Modalidade</label>
						<select id="mode" name="mode" class="form-select">
							<option value="">Selecione...</option>
							<?php foreach ( $modes as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_POST['mode'] ) && sanitize_key( wp_unslash( $_POST['mode'] ) ) === $slug ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Tipos de Evento</label>
					<div class="checkbox-group" id="eventTypesGroup">
						<?php foreach ( $event_types as $slug => $label ) : ?>
						<label class="checkbox-item">
							<input type="checkbox" name="event_types[]" value="<?php echo esc_attr( $slug ); ?>">
							<span><?php echo esc_html( $label ); ?></span>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Contact -->
			<div class="form-section">
				<h2 class="text-lg font-bold text-slate-900 mb-4">Contato</h2>

				<div class="form-grid">
					<div class="form-group">
						<label class="form-label" for="contact_whatsapp">
							<i class="ri-whatsapp-line text-green-500"></i> WhatsApp
						</label>
						<input
							type="text"
							id="contact_whatsapp"
							name="contact_whatsapp"
							class="form-input"
							placeholder="(21) 99999-9999"
							value="<?php echo isset( $_POST['contact_whatsapp'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['contact_whatsapp'] ) ) ) : ''; ?>"
						>
					</div>

					<div class="form-group">
						<label class="form-label" for="contact_instagram">
							<i class="ri-instagram-line text-pink-500"></i> Instagram
						</label>
						<input
							type="text"
							id="contact_instagram"
							name="contact_instagram"
							class="form-input"
							placeholder="@usuario"
							value="<?php echo isset( $_POST['contact_instagram'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['contact_instagram'] ) ) ) : ''; ?>"
						>
					</div>
				</div>

				<div class="form-grid">
					<div class="form-group">
						<label class="form-label" for="contact_email">
							<i class="ri-mail-line text-blue-500"></i> E-mail
						</label>
						<input
							type="email"
							id="contact_email"
							name="contact_email"
							class="form-input"
							placeholder="contato@empresa.com"
							value="<?php echo isset( $_POST['contact_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['contact_email'] ) ) ) : ''; ?>"
						>
					</div>

					<div class="form-group">
						<label class="form-label" for="contact_phone">
							<i class="ri-phone-line text-slate-500"></i> Telefone
						</label>
						<input
							type="text"
							id="contact_phone"
							name="contact_phone"
							class="form-input"
							placeholder="(21) 3333-4444"
							value="<?php echo isset( $_POST['contact_phone'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) ) : ''; ?>"
						>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label" for="contact_website">
						<i class="ri-global-line text-slate-500"></i> Website
					</label>
					<input
						type="url"
						id="contact_website"
						name="contact_website"
						class="form-input"
						placeholder="https://www.empresa.com.br"
						value="<?php echo isset( $_POST['contact_website'] ) ? esc_attr( esc_url_raw( wp_unslash( $_POST['contact_website'] ) ) ) : ''; ?>"
					>
				</div>
			</div>

			<!-- Media -->
			<div class="form-section">
				<h2 class="text-lg font-bold text-slate-900 mb-4">Mídia</h2>

				<div class="form-group">
					<label class="form-label">Logo</label>
					<div class="upload-area" id="logoUpload">
						<input type="file" name="logo" accept="image/*" id="logoInput">
						<i class="ri-image-add-line text-3xl text-slate-300 mb-2"></i>
						<p class="text-sm text-slate-500">Clique para enviar o logo</p>
						<p class="text-xs text-slate-400 mt-1">PNG, JPG ou SVG. Máx 2MB.</p>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Banner</label>
					<div class="upload-area" id="bannerUpload">
						<input type="file" name="banner" accept="image/*" id="bannerInput">
						<i class="ri-landscape-line text-3xl text-slate-300 mb-2"></i>
						<p class="text-sm text-slate-500">Clique para enviar o banner</p>
						<p class="text-xs text-slate-400 mt-1">1200x400px recomendado. Máx 5MB.</p>
					</div>
				</div>
			</div>

			<!-- Tags -->
			<div class="form-section">
				<h2 class="text-lg font-bold text-slate-900 mb-4">Tags</h2>

				<div class="form-group">
					<label class="form-label" for="tags">Palavras-chave</label>
					<input
						type="text"
						id="tags"
						name="tags"
						class="form-input"
						placeholder="Ex: casamento, festa, corporativo"
						value="<?php echo isset( $_POST['tags'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['tags'] ) ) ) : ''; ?>"
					>
					<p class="form-hint">Separe as tags com vírgula.</p>
				</div>
			</div>

			<!-- Actions -->
			<div class="flex flex-col sm:flex-row gap-3 pt-4">
				<button type="submit" class="btn-primary flex-1">
					<i class="ri-check-line"></i>
					Cadastrar Fornecedor
				</button>
				<a href="<?php echo esc_url( home_url( '/fornece/' ) ); ?>" class="btn-secondary">
					Cancelar
				</a>
			</div>

		</form>

	</main>

	<?php wp_footer(); ?>

	<script>
	(function() {
		'use strict';

		// Clock.
		const clockEl = document.getElementById('digital-clock');
		if (clockEl) {
			function updateClock() {
				clockEl.textContent = new Date().toLocaleTimeString('pt-BR', { hour12: false });
			}
			updateClock();
			setInterval(updateClock, 1000);
		}

		// Checkbox toggle.
		document.querySelectorAll('.checkbox-item').forEach(item => {
			const input = item.querySelector('input');
			item.addEventListener('click', (e) => {
				if (e.target !== input) {
					input.checked = !input.checked;
				}
				item.classList.toggle('checked', input.checked);
			});

			// Init state.
			if (input.checked) {
				item.classList.add('checked');
			}
		});

		// Upload areas.
		['logo', 'banner'].forEach(type => {
			const upload = document.getElementById(type + 'Upload');
			const input = document.getElementById(type + 'Input');

			if (upload && input) {
				upload.addEventListener('click', () => input.click());

				input.addEventListener('change', () => {
					if (input.files && input.files[0]) {
						const file = input.files[0];
						upload.innerHTML = `
							<i class="ri-checkbox-circle-fill text-3xl text-green-500 mb-2"></i>
							<p class="text-sm text-slate-700 font-medium">${file.name}</p>
							<p class="text-xs text-slate-400 mt-1">Clique para trocar</p>
						`;
					}
				});
			}
		});

	})();
	</script>
</body>
</html>
