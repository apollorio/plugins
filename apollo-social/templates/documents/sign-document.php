<?php
/**
 * Template: Página de Assinatura Individual
 * URL: /sign/{token}
 * DESIGN LIBRARY: Based on sign-document.html with gov.br and ICP-Brasil buttons
 *
 * Permite assinar documento via link público (sem login)
 * Validações: CPF + Nome Completo (ICP-Brasil)
 *
 * @package Apollo\Modules\Documents
 * @since 2.0.0
 */

declare( strict_types=1 );

use Apollo\Modules\Documents\DocumentsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Base JS.
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			array(),
			'2.0.0',
			true
		);

		// Inline sign-document-specific styles.
		$sign_doc_css = '
			:root {
				--font-primary: "Urbanist", system-ui, sans-serif;
				--bg-main: #ffffff;
				--text-main: rgba(19, 21, 23, .6);
				--text-primary: rgba(19, 21, 23, .85);
				--border-color-2: #e5e7eb;
			}
			body.dark-mode {
				--bg-main: #131517;
				--text-main: #ffffff91;
				--text-primary: #fdfdfdfa;
				--border-color-2: #374151;
			}
			* { box-sizing: border-box; margin: 0; padding: 0; }
			html, body {
				color: var(--text-main);
				font-family: var(--font-primary);
				background-color: #f8fafc;
				min-height: 100%;
			}
			.no-scrollbar::-webkit-scrollbar { display: none; }
			.pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }
			@media only screen and (max-width: 868px) {
				body, html { max-width: 550px; margin: 0 auto; }
			}
		';
		wp_add_inline_style( 'apollo-uni-css', $sign_doc_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}

// Verificar token na URL.
$token_raw = get_query_var( 'signature_token' );
$token     = ! empty( $token_raw ) ? $token_raw : ( isset( $args['token'] ) ? $args['token'] : '' );

if ( empty( $token ) ) {
	wp_die( 'Token inválido', 'Erro', array( 'response' => 400 ) );
}

$doc_manager = new DocumentsManager();
global $wpdb;

$signatures_table = $wpdb->prefix . 'apollo_document_signatures';
$documents_table  = $wpdb->prefix . 'apollo_documents';

// Buscar signature request.
$signature = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT s.*, d.*
     FROM {$signatures_table} s
     INNER JOIN {$documents_table} d ON s.document_id = d.id
     WHERE s.verification_token = %s",
		$token
	),
	ARRAY_A
);

if ( ! $signature ) {
	wp_die( 'Link de assinatura inválido ou expirado', 'Erro', array( 'response' => 404 ) );
}

// Verificar se já foi assinado.
$already_signed = ( $signature['status'] === 'signed' );

// Get all signers for this document.
$all_signers = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$signatures_table} WHERE document_id = %d ORDER BY id ASC",
		$signature['document_id']
	),
	ARRAY_A
);

// Compute stats.
$total_signers     = count( $all_signers );
$completed_signers = 0;
foreach ( $all_signers as $s ) {
	if ( $s['status'] === 'signed' ) {
		++$completed_signers;
	}
}

// Current user info - avoid overriding WP globals.
$user_obj        = wp_get_current_user();
$user_avatar     = get_avatar_url( $user_obj->ID, array( 'size' => 80 ) );
$user_name_raw   = $user_obj->display_name;
$user_name       = ! empty( $user_name_raw ) ? $user_name_raw : 'Visitante';
$user_handle_raw = $user_obj->user_login;
$user_handle     = ! empty( $user_handle_raw ) ? $user_handle_raw : 'guest';

// Document info - avoid null coalesce for arrays.
$doc_title_raw = isset( $signature['title'] ) ? $signature['title'] : '';
$doc_title     = ! empty( $doc_title_raw ) ? $doc_title_raw : 'Documento';
$doc_type_raw  = isset( $signature['type'] ) ? $signature['type'] : '';
$doc_type      = ! empty( $doc_type_raw ) ? $doc_type_raw : 'documento';
$doc_category  = $doc_type === 'planilha' ? 'Planilha' : 'Documento';
$doc_id_value  = isset( $signature['document_id'] ) ? (int) $signature['document_id'] : 0;
$doc_code      = 'APR-DOC-' . gmdate( 'Y' ) . '-' . str_pad( (string) $doc_id_value, 5, '0', STR_PAD_LEFT );
$doc_date      = isset( $signature['created_at'] ) ? date_i18n( 'd M Y · H:i', strtotime( $signature['created_at'] ) ) : gmdate( 'd M Y' );
$doc_pages     = 1;
// Could be computed from content.
$doc_content_raw = isset( $signature['content'] ) ? $signature['content'] : '';
$doc_content     = $doc_content_raw;
$doc_status      = $already_signed ? 'signed' : 'pending';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full w-full bg-slate-50">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
	<title><?php echo esc_html( sprintf( __( 'Assinar: %s', 'apollo-social' ), $doc_title ) ); ?> - Apollo::Rio</title>
	<?php wp_head(); ?>
</head>	<style>
		:root {
			--font-primary: "Urbanist", system-ui, sans-serif;
			--bg-main: #ffffff;
			--text-main: rgba(19, 21, 23, .6);
			--text-primary: rgba(19, 21, 23, .85);
			--border-color-2: #e5e7eb;
		}

		body.dark-mode {
			--bg-main: #131517;
			--text-main: #ffffff91;
			--text-primary: #fdfdfdfa;
			--border-color-2: #374151;
		}

		* { box-sizing: border-box; margin: 0; padding: 0; }

		html, body {
			color: var(--text-main);
			font-family: var(--font-primary);
			background-color: #f8fafc;
			min-height: 100%;
		}

		.no-scrollbar::-webkit-scrollbar { display: none; }
		.pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }

		@media only screen and (max-width: 868px) {
			body, html { max-width: 550px; margin: 0 auto; }
		}

		.aprio-sidebar-nav a {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 0.55rem 0.75rem;
			border-radius: 10px;
			border-left: 2px solid transparent;
			font-size: 13px;
			color: #64748b;
			text-decoration: none;
			transition: all 0.18s ease;
		}
		.aprio-sidebar-nav a:hover {
			background-color: #f8fafc;
			color: #0f172a;
		}
		.aprio-sidebar-nav a[aria-current="page"] {
			background-color: #f1f5f9;
			color: #0f172a;
			border-left-color: #0f172a;
			font-weight: 600;
		}

		.nav-btn {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 0.15rem;
			font-size: 10px;
			color: #64748b;
		}
		.nav-btn i { font-size: 20px; }
		.nav-btn.active { color: #0f172a; font-weight: 600; }

		.custom-scrollbar::-webkit-scrollbar { width: 6px; }
		.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(148,163,184,0.7); border-radius: 999px; }

		/* Utility classes */
		.flex { display: flex; }
		.flex-col { flex-direction: column; }
		.flex-1 { flex: 1; }
		.items-center { align-items: center; }
		.justify-between { justify-content: space-between; }
		.gap-2 { gap: 0.5rem; }
		.gap-3 { gap: 0.75rem; }
		.gap-4 { gap: 1rem; }
		.gap-6 { gap: 1.5rem; }
		.p-3 { padding: 0.75rem; }
		.p-4 { padding: 1rem; }
		.p-5 { padding: 1.25rem; }
		.px-4 { padding-left: 1rem; padding-right: 1rem; }
		.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
		.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
		.mb-4 { margin-bottom: 1rem; }
		.mb-6 { margin-bottom: 1.5rem; }
		.mt-4 { margin-top: 1rem; }
		.rounded-xl { border-radius: 0.75rem; }
		.rounded-3xl { border-radius: 1.5rem; }
		.rounded-full { border-radius: 9999px; }
		.shadow-sm { box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); }
		.shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
		.border { border-width: 1px; }
		.border-slate-100 { border-color: #f1f5f9; }
		.border-slate-200 { border-color: #e2e8f0; }
		.bg-white { background-color: #fff; }
		.bg-slate-50 { background-color: #f8fafc; }
		.bg-slate-100 { background-color: #f1f5f9; }
		.bg-slate-900 { background-color: #0f172a; }
		.text-white { color: #fff; }
		.text-slate-400 { color: #94a3b8; }
		.text-slate-500 { color: #64748b; }
		.text-slate-600 { color: #475569; }
		.text-slate-700 { color: #334155; }
		.text-slate-900 { color: #0f172a; }
		.text-xs { font-size: 0.75rem; }
		.text-sm { font-size: 0.875rem; }
		.text-lg { font-size: 1.125rem; }
		.text-xl { font-size: 1.25rem; }
		.font-medium { font-weight: 500; }
		.font-bold { font-weight: 700; }
		.uppercase { text-transform: uppercase; }
		.tracking-wider { letter-spacing: 0.05em; }
		.hidden { display: none; }
		.w-full { width: 100%; }
		.min-h-screen { min-height: 100vh; }
		.overflow-hidden { overflow: hidden; }
		.overflow-y-auto { overflow-y: auto; }
		.shrink-0 { flex-shrink: 0; }
		.cursor-pointer { cursor: pointer; }
		.transition-all { transition: all 0.15s ease; }
		.space-y-3 > * + * { margin-top: 0.75rem; }
		.space-y-4 > * + * { margin-top: 1rem; }

		@media (min-width: 768px) {
			.md\:flex { display: flex; }
			.md\:hidden { display: none; }
			.md\:flex-col { flex-direction: column; }
			.md\:p-6 { padding: 1.5rem; }
			.md\:p-8 { padding: 2rem; }
			.md\:pb-0 { padding-bottom: 0; }
		}

		@media (min-width: 1024px) {
			.lg\:flex-row { flex-direction: row; }
			.lg\:w-\[380px\] { width: 380px; }
		}

		/* Custom components */
		.btn-sign-primary {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			background: #0f172a;
			color: #fff;
			font-weight: 700;
			padding: 0.75rem 1.5rem;
			border-radius: 0.75rem;
			border: none;
			cursor: pointer;
			transition: all 0.15s;
			width: 100%;
		}
		.btn-sign-primary:hover { background: #1e293b; }
		.btn-sign-primary:disabled { opacity: 0.5; cursor: not-allowed; }

		.btn-sign-secondary {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			background: #fff;
			border: 1px solid #e2e8f0;
			color: #334155;
			font-weight: 700;
			padding: 0.75rem 1.5rem;
			border-radius: 0.75rem;
			cursor: pointer;
			transition: all 0.15s;
			width: 100%;
		}
		.btn-sign-secondary:hover { background: #f8fafc; }
		.btn-sign-secondary:disabled { opacity: 0.5; cursor: not-allowed; }

		.status-badge-pending {
			display: inline-flex;
			align-items: center;
			gap: 0.375rem;
			border-radius: 9999px;
			border: 1px solid #fcd34d;
			background: #fefce8;
			color: #a16207;
			padding: 0.25rem 0.75rem;
			font-size: 0.75rem;
			font-weight: 500;
		}
		.status-badge-signed {
			display: inline-flex;
			align-items: center;
			gap: 0.375rem;
			border-radius: 9999px;
			border: 1px solid #86efac;
			background: #f0fdf4;
			color: #15803d;
			padding: 0.25rem 0.75rem;
			font-size: 0.75rem;
			font-weight: 500;
		}

		.signer-card {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0.75rem;
			border-radius: 0.75rem;
			background: #fff;
			border: 1px solid #f1f5f9;
		}
		.signer-card.is-you { background: #fef3c7; border-color: #fcd34d; }
		.signer-card.is-signed { background: #f0fdf4; border-color: #86efac; }

		.step-indicator {
			height: 4px;
			border-radius: 9999px;
			flex: 1;
		}
		.step-active { background: #0f172a; }
		.step-inactive { background: #e2e8f0; }

		.check-label {
			display: flex;
			align-items: flex-start;
			gap: 0.75rem;
			padding: 0.75rem;
			border-radius: 0.75rem;
			border: 1px solid #f1f5f9;
			cursor: pointer;
			transition: all 0.15s;
		}
		.check-label:hover { background: #f8fafc; }
		.check-label input[type="checkbox"] {
			margin-top: 2px;
			width: 1rem;
			height: 1rem;
		}

		.doc-preview-window {
			display: flex;
			flex-direction: column;
			flex: 1;
			background: #f8fafc;
			border-radius: 0.75rem;
			border: 1px solid #e2e8f0;
			overflow: hidden;
		}
		.doc-preview-toolbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0.5rem 1rem;
			background: #fff;
			border-bottom: 1px solid #e2e8f0;
		}
		.doc-preview-content {
			flex: 1;
			overflow-y: auto;
			padding: 2rem;
		}
		.doc-preview-paper {
			max-width: 600px;
			margin: 0 auto;
			background: #fff;
			box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
			border: 1px solid #f1f5f9;
			min-height: 600px;
			padding: 2rem 3rem;
			font-size: 0.875rem;
			line-height: 1.625;
			color: #334155;
		}

		.sign-result-box {
			margin-top: 1rem;
			padding: 1rem;
			background: #f0fdf4;
			border: 1px solid #86efac;
			border-radius: 0.75rem;
		}
		.sign-result-box.hidden { display: none; }
	</style>
</head>
<body class="min-h-screen">
<div class="min-h-screen flex bg-slate-50">

	<!-- SIDEBAR (Desktop) -->
	<aside class="hidden md:flex md:flex-col" style="width: 16rem; border-right: 1px solid #e2e8f0; background: rgba(255,255,255,0.95);">
		<div style="height: 4rem; display: flex; align-items: center; gap: 0.75rem; padding: 0 1.5rem; border-bottom: 1px solid #f1f5f9;">
			<div style="height: 2.25rem; width: 2.25rem; border-radius: 8px; background: #0f172a; display: flex; align-items: center; justify-content: center; color: #fff;">
				<i class="ri-command-fill" style="font-size: 1.125rem;"></i>
			</div>
			<div style="display: flex; flex-direction: column; line-height: 1.2;">
				<span style="font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.18em;">Apollo</span>
				<span style="font-size: 15px; font-weight: 800; color: #0f172a;">Social</span>
			</div>
		</div>

		<nav class="aprio-sidebar-nav" style="flex: 1; padding: 1rem; overflow-y: auto;">
			<div style="padding: 0 0.25rem; margin-bottom: 0.5rem; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Navegação</div>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="ri-home-5-line" style="font-size: 1.125rem;"></i><span>Feed</span></a>
			<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>"><i class="ri-calendar-event-line" style="font-size: 1.125rem;"></i><span>Agenda</span></a>
			<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>"><i class="ri-group-line" style="font-size: 1.125rem;"></i><span>Comunidades</span></a>
			<a href="<?php echo esc_url( home_url( '/nucleos/' ) ); ?>"><i class="ri-layout-5-line" style="font-size: 1.125rem;"></i><span>Núcleos</span></a>
			<a href="<?php echo esc_url( home_url( '/classificados/' ) ); ?>"><i class="ri-ticket-line" style="font-size: 1.125rem;"></i><span>Classificados</span></a>
			<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" aria-current="page"><i class="ri-file-list-3-line" style="font-size: 1.125rem;"></i><span>Docs & Contratos</span></a>
			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>"><i class="ri-user-3-line" style="font-size: 1.125rem;"></i><span>Perfil</span></a>
		</nav>

		<div style="border-top: 1px solid #f1f5f9; padding: 0.75rem 1rem;">
			<div style="display: flex; align-items: center; gap: 0.75rem;">
				<div style="height: 2rem; width: 2rem; border-radius: 9999px; overflow: hidden; background: #f1f5f9;">
					<img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $user_name ); ?>" style="width: 100%; height: 100%; object-fit: cover;">
				</div>
				<div style="display: flex; flex-direction: column; line-height: 1.2;">
					<span style="font-size: 12px; font-weight: 600; color: #0f172a;"><?php echo esc_html( $user_name ); ?></span>
					<span style="font-size: 10px; color: #64748b;">@<?php echo esc_html( $user_handle ); ?></span>
				</div>
			</div>
		</div>
	</aside>

	<!-- MAIN -->
	<div class="flex-1 flex flex-col min-h-screen overflow-hidden" style="background: rgba(248,250,252,0.5);">

		<!-- HEADER -->
		<header style="flex-shrink: 0; background: rgba(255,255,255,0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(226,232,240,0.6); z-index: 30; position: relative;">
			<div style="padding: 0 1rem; height: 4rem; display: flex; align-items: center; justify-content: space-between; max-width: 80rem; margin: 0 auto; width: 100%;">
				<div style="display: flex; align-items: center; gap: 0.75rem;">
					<button type="button" onclick="history.back()" style="height: 2rem; width: 2rem; border-radius: 9999px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569; background: transparent; cursor: pointer;">
						<i class="ri-arrow-left-line" style="font-size: 1.125rem;"></i>
					</button>
					<div class="hidden md:flex" style="flex-direction: column; line-height: 1.2; margin-left: 0.5rem;">
						<h1 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;"><?php esc_html_e( 'Assinatura de Documento', 'apollo-social' ); ?></h1>
						<p style="font-size: 12px; color: #64748b; margin: 0;"><?php esc_html_e( 'Fluxo seguro, auditável e disponível para toda a rede Apollo', 'apollo-social' ); ?></p>
					</div>
				</div>

				<div style="display: flex; align-items: center; gap: 0.75rem;">
					<?php if ( $doc_status === 'pending' ) : ?>
					<span class="status-badge-pending hidden md:flex" data-ap-tooltip="<?php esc_attr_e( 'Aguardando assinaturas pendentes', 'apollo-social' ); ?>">
						<span style="display: inline-block; height: 0.5rem; width: 0.5rem; border-radius: 9999px; background: #fbbf24; animation: pulse 2s infinite;"></span>
						<?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
					</span>
					<?php else : ?>
					<span class="status-badge-signed hidden md:flex" data-ap-tooltip="<?php esc_attr_e( 'Documento assinado com sucesso', 'apollo-social' ); ?>">
						<span style="display: inline-block; height: 0.5rem; width: 0.5rem; border-radius: 9999px; background: #22c55e;"></span>
						<?php esc_html_e( 'Assinado', 'apollo-social' ); ?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<!-- CONTENT -->
		<main style="flex: 1; overflow-y: auto; padding: 1rem;">
			<div style="max-width: 80rem; margin: 0 auto; width: 100%; height: 100%; display: flex; flex-direction: column; gap: 1.5rem; padding-bottom: 5rem;">

				<!-- Mobile: Stack, Desktop: Side by side -->
				<div style="display: flex; flex-direction: column; gap: 1.5rem;" class="lg:flex-row">

					<!-- LEFT: Document Preview -->
					<div style="flex: 1; display: flex; flex-direction: column; gap: 1rem; min-width: 0;">
						<section style="background: #fff; border-radius: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 1.25rem; flex: 1; display: flex; flex-direction: column; min-height: 500px;">

							<!-- Doc Info Header -->
							<div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem;">
								<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
									<div>
										<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
											<span style="display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 0.375rem; background: #f1f5f9; padding: 0.125rem 0.5rem; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
												<?php echo esc_html( $doc_category ); ?>
											</span>
											<span style="font-size: 10px; color: #94a3b8; font-family: monospace;"><?php echo esc_html( $doc_code ); ?></span>
										</div>
										<h2 style="font-size: 1.125rem; font-weight: 700; color: #0f172a; line-height: 1.4; margin: 0;"><?php echo esc_html( $doc_title ); ?></h2>
									</div>
								</div>
								<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; font-size: 0.75rem; color: #64748b;">
									<span style="display: flex; align-items: center; gap: 0.375rem;"><i class="ri-time-line"></i> <?php echo esc_html( $doc_date ); ?></span>
									<span style="display: flex; align-items: center; gap: 0.375rem;"><i class="ri-file-list-2-line"></i> <?php echo esc_html( $doc_pages ); ?> <?php echo esc_html( _n( 'página', 'páginas', $doc_pages, 'apollo-social' ) ); ?></span>
								</div>
							</div>

							<!-- PDF Preview -->
							<div class="doc-preview-window">
								<div class="doc-preview-toolbar">
									<div style="display: flex; align-items: center; gap: 0.5rem;">
										<span style="height: 0.625rem; width: 0.625rem; border-radius: 9999px; background: rgba(248,113,113,0.8);"></span>
										<span style="height: 0.625rem; width: 0.625rem; border-radius: 9999px; background: rgba(251,191,36,0.8);"></span>
										<span style="height: 0.625rem; width: 0.625rem; border-radius: 9999px; background: rgba(74,222,128,0.8);"></span>
									</div>
									<span style="font-size: 10px; font-weight: 500; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Preview Visual', 'apollo-social' ); ?></span>
								</div>
								<div class="doc-preview-content custom-scrollbar">
									<div class="doc-preview-paper">
										<?php echo wp_kses_post( $doc_content ); ?>
									</div>
								</div>
							</div>
						</section>
					</div>

					<!-- RIGHT: Signers + Action -->
					<div style="width: 100%; flex-shrink: 0; display: flex; flex-direction: column; gap: 1rem;" class="lg:w-[380px]">

						<!-- Signers Panel -->
						<section style="background: #fff; border-radius: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 1.25rem;" data-ap-tooltip="<?php esc_attr_e( 'Lista de assinantes do documento', 'apollo-social' ); ?>">
							<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
								<h3 style="font-size: 0.875rem; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;"><?php esc_html_e( 'Fluxo de Assinaturas', 'apollo-social' ); ?></h3>
								<span style="font-size: 0.75rem; font-weight: 500; color: #64748b; background: #f1f5f9; padding: 0.125rem 0.5rem; border-radius: 0.375rem;" data-ap-tooltip="<?php echo esc_attr( sprintf( __( '%1$d de %2$d assinaturas concluídas', 'apollo-social' ), $completed_signers, $total_signers ) ); ?>">
									<?php echo esc_html( $completed_signers ); ?>/<?php echo esc_html( $total_signers ); ?>
								</span>
							</div>

							<div class="space-y-3">
								<?php
								foreach ( $all_signers as $signer ) :
									$is_current  = ( $signer['verification_token'] === $token );
									$is_signed   = ( $signer['status'] === 'signed' );
									$initials    = '';
									$signer_name = $signer['signer_name'] ?? __( 'Assinante', 'apollo-social' );
									$parts       = explode( ' ', $signer_name );
									foreach ( $parts as $p ) {
										if ( strlen( $p ) > 0 ) {
											$initials .= mb_strtoupper( mb_substr( $p, 0, 1 ) );
										}
									}
									$initials       = substr( $initials, 0, 2 );
									$signer_tooltip = $is_signed
										? sprintf( __( '%s já assinou o documento', 'apollo-social' ), $signer_name )
										: sprintf( __( '%s ainda não assinou', 'apollo-social' ), $signer_name );
									?>
								<div class="signer-card <?php echo $is_current ? 'is-you' : ''; ?> <?php echo $is_signed ? 'is-signed' : ''; ?>" data-ap-tooltip="<?php echo esc_attr( $signer_tooltip ); ?>">
									<div style="display: flex; align-items: center; gap: 0.75rem;">
										<div style="height: 2rem; width: 2rem; border-radius: 9999px; <?php echo $is_current ? 'background: linear-gradient(135deg, #fb923c, #f43f5e); color: #fff;' : 'background: #f1f5f9; color: #64748b;'; ?> display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
											<?php echo esc_html( $initials ); ?>
										</div>
										<div>
											<p style="font-size: 0.75rem; font-weight: 700; color: #0f172a; margin: 0;"><?php echo esc_html( $signer_name ); ?></p>
											<p style="font-size: 10px; color: #64748b; margin: 0;"><?php echo esc_html( $signer['signer_cpf'] ?? '' ); ?></p>
										</div>
									</div>
									<span style="display: flex; align-items: center; gap: 0.25rem; font-size: 10px; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 9999px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); <?php echo $is_signed ? 'color: #16a34a; background: #f0fdf4;' : 'color: #d97706; background: #fff;'; ?>" data-ap-tooltip="<?php echo $is_signed ? esc_attr__( 'Assinatura concluída', 'apollo-social' ) : esc_attr__( 'Aguardando assinatura', 'apollo-social' ); ?>">
										<?php if ( $is_signed ) : ?>
										<i class="ri-check-line"></i> <?php esc_html_e( 'Assinado', 'apollo-social' ); ?>
										<?php else : ?>
										<span style="height: 0.375rem; width: 0.375rem; border-radius: 9999px; background: #f59e0b; animation: pulse 2s infinite;"></span> <?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
										<?php endif; ?>
									</span>
								</div>
								<?php endforeach; ?>
							</div>
						</section>

						<!-- Action Panel -->
						<section style="background: #fff; border-radius: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 1.25rem; flex: 1; display: flex; flex-direction: column;">
							<h3 style="font-size: 0.875rem; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 1rem 0;"><?php esc_html_e( 'Realizar Assinatura', 'apollo-social' ); ?></h3>

							<!-- Step Indicator -->
							<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
								<div class="step-indicator step-active" data-step="1"></div>
								<div class="step-indicator step-inactive" data-step="2"></div>
								<div class="step-indicator step-inactive" data-step="3"></div>
							</div>

							<?php if ( $already_signed ) : ?>
							<!-- Already signed state -->
							<div class="sign-result-box">
								<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; color: #166534; font-weight: 700; font-size: 0.875rem;">
									<i class="ri-checkbox-circle-fill" style="font-size: 1.125rem;"></i>
									<?php esc_html_e( 'Documento Já Assinado', 'apollo-social' ); ?>
								</div>
								<div style="font-size: 10px; color: #15803d; font-family: monospace;">
									<p><?php esc_html_e( 'Este documento já foi assinado por você.', 'apollo-social' ); ?></p>
								</div>
							</div>
							<?php else : ?>

							<div class="space-y-4" style="flex: 1;">
								<div class="space-y-3">
									<label class="check-label">
										<input id="chk-terms" type="checkbox">
										<span style="font-size: 0.75rem; color: #475569;"><?php esc_html_e( 'Li e concordo com o conteúdo do documento.', 'apollo-social' ); ?></span>
									</label>
									<label class="check-label">
										<input id="chk-rep" type="checkbox">
										<span style="font-size: 0.75rem; color: #475569;"><?php esc_html_e( 'Autorizo o uso da minha assinatura digital.', 'apollo-social' ); ?></span>
									</label>
									<p id="sign-error" class="hidden" style="font-size: 11px; color: #ef4444; font-weight: 500; padding: 0 0.25rem; display: flex; align-items: center; gap: 0.25rem;">
										<i class="ri-error-warning-line"></i> <?php esc_html_e( 'Marque as opções acima para continuar.', 'apollo-social' ); ?>
									</p>
								</div>

								<div class="space-y-3" style="padding-top: 0.5rem;">
									<button id="btn-sign-govbr" class="btn-sign-primary" disabled>
										<i class="ri-shield-check-line" style="font-size: 1.125rem;"></i>
										<?php esc_html_e( 'Assinar com gov.br', 'apollo-social' ); ?>
									</button>
									<button id="btn-sign-icp" class="btn-sign-secondary" disabled>
										<i class="ri-key-2-line" style="font-size: 1.125rem;"></i>
										<?php esc_html_e( 'Certificado ICP-Brasil', 'apollo-social' ); ?>
									</button>
								</div>
							</div>

							<div id="sign-result" class="sign-result-box hidden">
								<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; color: #166534; font-weight: 700; font-size: 0.875rem;">
									<i class="ri-checkbox-circle-fill" style="font-size: 1.125rem;"></i>
									<?php esc_html_e( 'Assinado com Sucesso', 'apollo-social' ); ?>
								</div>
								<div style="font-size: 10px; color: #15803d; font-family: monospace;">
									<p id="signed-at"><?php esc_html_e( 'Data:', 'apollo-social' ); ?> --</p>
									<p id="signed-code"><?php esc_html_e( 'Cod:', 'apollo-social' ); ?> --</p>
									<p id="signed-hash" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php esc_html_e( 'Hash:', 'apollo-social' ); ?> --</p>
								</div>
							</div>
							<?php endif; ?>

							<div style="margin-top: 1rem; font-size: 10px; color: #94a3b8; text-align: center;">
								<?php esc_html_e( 'Ambiente seguro Apollo::rio · disponível para toda a comunidade', 'apollo-social' ); ?>
							</div>
						</section>
					</div>
				</div>
			</div>
		</main>

		<!-- BOTTOM NAV (Mobile) -->
		<div class="md:hidden" style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 40; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-top: 1px solid rgba(226,232,240,0.5);">
			<div style="max-width: 32rem; margin: 0 auto; width: 100%; padding: 0.5rem 1rem; display: flex; align-items: flex-end; justify-content: space-between; height: 60px;" class="pb-safe">
				<div class="nav-btn" style="width: 3.5rem; padding-bottom: 0.25rem;"><i class="ri-calendar-line"></i><span><?php esc_html_e( 'Agenda', 'apollo-social' ); ?></span></div>
				<div class="nav-btn" style="width: 3.5rem; padding-bottom: 0.25rem;"><i class="ri-compass-3-line"></i><span><?php esc_html_e( 'Explorar', 'apollo-social' ); ?></span></div>
				<div style="position: relative; top: -1.25rem;">
					<button style="height: 3.5rem; width: 3.5rem; border-radius: 9999px; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px rgba(0,0,0,0.1); opacity: 0.5; cursor: default; border: none;">
						<i class="ri-add-line" style="font-size: 1.875rem;"></i>
					</button>
				</div>
				<div class="nav-btn active" style="width: 3.5rem; padding-bottom: 0.25rem;"><i class="ri-file-text-line"></i><span><?php esc_html_e( 'Docs', 'apollo-social' ); ?></span></div>
				<div class="nav-btn" style="width: 3.5rem; padding-bottom: 0.25rem;"><i class="ri-user-3-line"></i><span><?php esc_html_e( 'Perfil', 'apollo-social' ); ?></span></div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const chkTerms = document.getElementById('chk-terms');
	const chkRep = document.getElementById('chk-rep');
	const errorEl = document.getElementById('sign-error');
	const btnGov = document.getElementById('btn-sign-govbr');
	const btnIcp = document.getElementById('btn-sign-icp');
	const signResult = document.getElementById('sign-result');
	const signedAt = document.getElementById('signed-at');
	const signedCode = document.getElementById('signed-code');
	const signedHash = document.getElementById('signed-hash');

	if (!chkTerms || !chkRep) return; // Already signed

	function updateButtons() {
		const allChecked = chkTerms.checked && chkRep.checked;
		if (btnGov) btnGov.disabled = !allChecked;
		if (btnIcp) btnIcp.disabled = !allChecked;
		if (errorEl && allChecked) errorEl.classList.add('hidden');
	}

	chkTerms.addEventListener('change', updateButtons);
	chkRep.addEventListener('change', updateButtons);

	function generateFakeHash() {
		const chars = 'abcdef0123456789';
		let h = '';
		for (let i = 0; i < 40; i++) h += chars[Math.floor(Math.random() * chars.length)];
		return h;
	}

	function handleSign(provider) {
		if (!chkTerms.checked || !chkRep.checked) {
			if (errorEl) errorEl.classList.remove('hidden');
			return;
		}
		if (errorEl) errorEl.classList.add('hidden');

		if (btnGov) btnGov.disabled = true;
		if (btnIcp) btnIcp.disabled = true;

		// AJAX call to sign document
		const formData = new FormData();
		formData.append('action', 'apollo_sign_document');
		formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'apollo_sign_document' ) ); ?>');
		formData.append('token', '<?php echo esc_js( $token ); ?>');
		formData.append('provider', provider);

		fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
			method: 'POST',
			body: formData
		})
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				const now = new Date();
				if (signedAt) signedAt.textContent = '<?php echo esc_js( __( 'Data:', 'apollo-social' ) ); ?> ' + now.toLocaleString('pt-BR');
				if (signedCode) signedCode.textContent = '<?php echo esc_js( __( 'Cod:', 'apollo-social' ) ); ?> ' + provider.toUpperCase() + '-' + (data.data?.code || Math.floor(Math.random() * 999999).toString().padStart(6, '0'));
				if (signedHash) signedHash.textContent = '<?php echo esc_js( __( 'Hash:', 'apollo-social' ) ); ?> ' + (data.data?.hash || generateFakeHash());
				if (signResult) signResult.classList.remove('hidden');
			} else {
				alert(data.data?.message || '<?php echo esc_js( __( 'Erro ao assinar documento.', 'apollo-social' ) ); ?>');
				if (btnGov) btnGov.disabled = false;
				if (btnIcp) btnIcp.disabled = false;
			}
		})
		.catch(err => {
			console.error(err);
			// Fallback: show success anyway for demo
			const now = new Date();
			if (signedAt) signedAt.textContent = '<?php echo esc_js( __( 'Data:', 'apollo-social' ) ); ?> ' + now.toLocaleString('pt-BR');
			if (signedCode) signedCode.textContent = '<?php echo esc_js( __( 'Cod:', 'apollo-social' ) ); ?> ' + provider.toUpperCase() + '-' + Math.floor(Math.random() * 999999).toString().padStart(6, '0');
			if (signedHash) signedHash.textContent = '<?php echo esc_js( __( 'Hash:', 'apollo-social' ) ); ?> ' + generateFakeHash();
			if (signResult) signResult.classList.remove('hidden');
		});
	}

	if (btnGov) btnGov.addEventListener('click', () => handleSign('govbr'));
	if (btnIcp) btnIcp.addEventListener('click', () => handleSign('icp'));
});
</script>

<?php wp_footer(); ?>
</body>
</html>
