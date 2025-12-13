<?php
/**
 * Document Sign Template
 * DESIGN LIBRARY: Matches approved HTML from 'social doc single sign.md'
 * Secure digital signature flow with gov.br and ICP-Brasil support
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get document.
$document_id = get_the_ID();
$document    = get_post( $document_id );

if ( ! $document || $document->post_type !== 'apollo_document' ) {
	wp_die( __( 'Documento não encontrado.', 'apollo-social' ) );
}

// Get document meta.
$document_title   = $document->post_title;
$document_content = $document->post_content;

$status_raw      = get_post_meta( $document_id, '_document_status', true );
$document_status = ! empty( $status_raw ) ? $status_raw : 'draft';

$category_raw      = get_post_meta( $document_id, '_document_category', true );
$document_category = ! empty( $category_raw ) ? $category_raw : __( 'Geral', 'apollo-social' );

$code_raw      = get_post_meta( $document_id, '_document_code', true );
$document_code = ! empty( $code_raw ) ? $code_raw : sprintf( 'APR-DOC-%s-%05d', date( 'Y' ), $document_id );

$document_created = get_the_date( 'd \d\e M. \d\e Y', $document_id );

$pages_raw      = get_post_meta( $document_id, '_document_pages', true );
$document_pages = ! empty( $pages_raw ) ? $pages_raw : 1;

// Get signers.
$signers = get_post_meta( $document_id, '_document_signers', true );
if ( ! is_array( $signers ) ) {
	$signers = [];
}

// Current user.
$user_obj              = wp_get_current_user();
$current_user_id       = $user_obj->ID;
$current_user_avatar   = get_avatar_url( $current_user_id, [ 'size' => 64 ] );
$current_user_initials = mb_strtoupper( mb_substr( $user_obj->display_name, 0, 2 ) );

// Check if current user has already signed.
$current_user_signed = false;
$signed_count        = 0;
foreach ( $signers as $signer ) {
	if ( ! empty( $signer['signed'] ) ) {
		++$signed_count;
	}
	if ( isset( $signer['user_id'] ) && (int) $signer['user_id'] === $current_user_id && ! empty( $signer['signed'] ) ) {
		$current_user_signed = true;
	}
}

$total_signers = max( count( $signers ), 1 );
$all_signed    = $signed_count === $total_signers;

// ===== CPF/PASSPORT CHECK - CRITICAL FOR DOCUMENT SIGNING =====
// User must have CPF to sign documents. Passport users CANNOT sign.
$user_cpf = get_user_meta( $current_user_id, 'apollo_cpf', true );
if ( ! $user_cpf ) {
	// Fallback to old meta key.
	$user_cpf = get_user_meta( $current_user_id, '_user_cpf', true );
}
$user_passport      = get_user_meta( $current_user_id, 'apollo_passport', true );
$user_doc_type_raw  = get_user_meta( $current_user_id, 'apollo_doc_type', true );
$user_doc_type      = ! empty( $user_doc_type_raw ) ? $user_doc_type_raw : ( $user_cpf ? 'cpf' : ( $user_passport ? 'passport' : '' ) );
$can_sign_documents = get_user_meta( $current_user_id, 'apollo_can_sign_documents', true );

// Determine if user can sign.
$user_has_valid_cpf     = ! empty( $user_cpf ) && strlen( preg_replace( '/[^0-9]/', '', $user_cpf ) ) === 11;
$user_has_passport_only = ! $user_has_valid_cpf && ! empty( $user_passport );
$user_can_sign          = $user_has_valid_cpf && $can_sign_documents !== false;

// Block reason.
$sign_blocked_reason = '';
if ( ! $user_has_valid_cpf && $user_has_passport_only ) {
	$sign_blocked_reason = __( 'Usuários com passaporte não podem assinar documentos digitais. A assinatura digital requer CPF válido conforme legislação brasileira.', 'apollo-social' );
} elseif ( ! $user_has_valid_cpf ) {
	$sign_blocked_reason = __( 'Você precisa cadastrar um CPF válido no seu perfil para assinar documentos digitais.', 'apollo-social' );
}

// CPF mask for display.
$cpf_display = __( 'CPF não cadastrado', 'apollo-social' );
if ( $user_has_valid_cpf ) {
	$cpf_display = 'CPF ' . preg_replace( '/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '***.$2.***-**', preg_replace( '/[^0-9]/', '', $user_cpf ) );
} elseif ( $user_has_passport_only ) {
	$cpf_display = __( 'Passaporte (não aceito para assinatura)', 'apollo-social' );
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			[],
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			[],
			'4.7.0'
		);
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full w-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
	<title><?php esc_html_e( 'Assinar Documento', 'apollo-social' ); ?> - Apollo::Rio</title>

	<?php wp_head(); ?>
</head>
<body class="apollo-canvas min-h-screen">
<div class="min-h-screen flex">

	<!-- SIDEBAR DESKTOP -->
	<aside class="hidden md:flex md:flex-col w-64 border-r border-slate-200 bg-white/95 backdrop-blur-xl" data-tooltip="<?php esc_attr_e( 'Menu de navegação', 'apollo-social' ); ?>">
		<!-- Logo -->
		<div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
			<div class="h-9 w-9 rounded-[8px] bg-slate-900 flex items-center justify-center text-white" data-tooltip="<?php esc_attr_e( 'Apollo::Rio', 'apollo-social' ); ?>">
				<i class="ri-command-fill text-lg"></i>
			</div>
			<div class="flex flex-col leading-tight">
				<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]"><?php esc_html_e( 'plataforma', 'apollo-social' ); ?></span>
				<span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
			</div>
		</div>

		<!-- Navigation -->
		<nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
			<div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider"><?php esc_html_e( 'Navegação', 'apollo-social' ); ?></div>

			<a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Ver feed', 'apollo-social' ); ?>">
				<i class="ri-building-3-line"></i>
				<span><?php esc_html_e( 'Feed', 'apollo-social' ); ?></span>
			</a>

			<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Ver eventos', 'apollo-social' ); ?>">
				<i class="ri-calendar-event-line"></i>
				<span><?php esc_html_e( 'Eventos', 'apollo-social' ); ?></span>
			</a>

			<a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Ver comunidades', 'apollo-social' ); ?>">
				<i class="ri-user-community-fill"></i>
				<span><?php esc_html_e( 'Comunidades', 'apollo-social' ); ?></span>
			</a>

			<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" aria-current="page" data-tooltip="<?php esc_attr_e( 'Gerenciar documentos', 'apollo-social' ); ?>">
				<i class="ri-file-text-line"></i>
				<span><?php esc_html_e( 'Docs & Contratos', 'apollo-social' ); ?></span>
			</a>

			<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Ver perfil', 'apollo-social' ); ?>">
				<i class="ri-user-smile-fill"></i>
				<span><?php esc_html_e( 'Perfil', 'apollo-social' ); ?></span>
			</a>
		</nav>

		<!-- User Footer -->
		<div class="border-t border-slate-100 px-4 py-3">
			<div class="flex items-center gap-3">
				<div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100" data-tooltip="<?php echo esc_attr( $user_obj->display_name ); ?>">
					<img src="<?php echo esc_url( $current_user_avatar ); ?>" class="h-full w-full object-cover" alt="<?php echo esc_attr( $user_obj->display_name ); ?>">
				</div>
				<div class="flex flex-col leading-tight">
					<span class="text-[12px] font-semibold text-slate-900"><?php echo esc_html( $user_obj->display_name ); ?></span>
					<span class="text-[10px] text-slate-500">@<?php echo esc_html( $current_user->user_login ); ?></span>
				</div>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="ml-auto text-slate-400 hover:text-slate-700" data-tooltip="<?php esc_attr_e( 'Sair', 'apollo-social' ); ?>">
					<i class="ri-logout-circle-r-line text-base"></i>
				</a>
			</div>
		</div>
	</aside>

	<!-- MAIN COLUMN -->
	<div class="flex-1 flex flex-col h-full relative overflow-hidden bg-slate-50/50">

		<!-- HEADER -->
		<header class="flex-none bg-white/80 backdrop-blur-xl border-b border-slate-200/60 z-30 relative" data-tooltip="<?php esc_attr_e( 'Cabeçalho da página', 'apollo-social' ); ?>">
			<div class="px-4 h-16 flex items-center justify-between max-w-7xl mx-auto w-full">
				<div class="flex items-center gap-3">
					<button type="button" onclick="history.back()" class="h-8 w-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" data-tooltip="<?php esc_attr_e( 'Voltar', 'apollo-social' ); ?>">
						<i class="ri-arrow-left-line text-lg"></i>
					</button>

					<div class="flex flex-col leading-tight md:hidden">
						<span class="text-[10px] uppercase tracking-wider text-slate-400"><?php esc_html_e( 'Documento', 'apollo-social' ); ?></span>
						<span class="text-[13px] font-semibold text-slate-900"><?php esc_html_e( 'Assinar Digitalmente', 'apollo-social' ); ?></span>
					</div>

					<div class="hidden md:flex flex-col leading-tight ml-2">
						<h1 class="text-xl font-bold text-slate-900" data-tooltip="<?php esc_attr_e( 'Página de assinatura digital', 'apollo-social' ); ?>"><?php esc_html_e( 'Assinatura de Documento', 'apollo-social' ); ?></h1>
						<p class="text-[12px] text-slate-500"><?php esc_html_e( 'Fluxo seguro, auditável e disponível para toda a rede Apollo', 'apollo-social' ); ?></p>
					</div>
				</div>

				<div class="flex items-center gap-3">
					<?php if ( $all_signed ) : ?>
					<span id="doc-status-pill-header" class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700" data-tooltip="<?php esc_attr_e( 'Documento assinado por todas as partes', 'apollo-social' ); ?>">
						<span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
						<?php esc_html_e( 'Assinado', 'apollo-social' ); ?>
					</span>
					<?php elseif ( $current_user_signed ) : ?>
					<span id="doc-status-pill-header" class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700" data-tooltip="<?php esc_attr_e( 'Aguardando outras assinaturas', 'apollo-social' ); ?>">
						<span class="inline-flex h-2 w-2 rounded-full bg-blue-400"></span>
						<?php esc_html_e( 'Você assinou', 'apollo-social' ); ?>
					</span>
					<?php else : ?>
					<span id="doc-status-pill-header" class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700" data-tooltip="<?php esc_attr_e( 'Aguardando sua assinatura', 'apollo-social' ); ?>">
						<span class="inline-flex h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
						<?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<!-- MAIN CONTENT -->
		<main class="flex-1 overflow-y-auto scroll-smooth relative p-4 md:p-6" id="mainContainer">
			<!-- Breadcrumb (Desktop) -->
			<div class="hidden md:flex items-center gap-2 text-[11px] text-slate-500 mb-4 max-w-7xl mx-auto px-1">
				<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="hover:text-slate-900 cursor-pointer" data-tooltip="<?php esc_attr_e( 'Ver todos os documentos', 'apollo-social' ); ?>"><?php esc_html_e( 'Documentos', 'apollo-social' ); ?></a>
				<span class="text-slate-300">/</span>
				<span class="text-slate-900 font-medium truncate" id="bc-doc-title" data-tooltip="<?php echo esc_attr( $document_title ); ?>"><?php echo esc_html( $document_title ); ?></span>
			</div>

			<div class="max-w-7xl mx-auto w-full h-full flex flex-col lg:flex-row gap-6 pb-20 md:pb-0">

				<!-- LEFT: DOCUMENT PREVIEW -->
				<div class="flex-1 flex flex-col gap-4 min-w-0">
					<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 md:p-6 flex-1 flex flex-col min-h-[500px]" data-tooltip="<?php esc_attr_e( 'Visualização do documento', 'apollo-social' ); ?>">
						<!-- Doc Header Info -->
						<div class="flex flex-col gap-4 mb-6 border-b border-slate-100 pb-6">
							<div class="flex items-start justify-between gap-4">
								<div>
									<div class="flex items-center gap-2 mb-1">
										<span id="doc-category" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase tracking-wide" data-tooltip="<?php esc_attr_e( 'Categoria do documento', 'apollo-social' ); ?>">
											<?php echo esc_html( $document_category ); ?>
										</span>
										<span id="doc-id" class="text-[10px] text-slate-400 font-mono" data-tooltip="<?php esc_attr_e( 'Código do documento', 'apollo-social' ); ?>"><?php echo esc_html( $document_code ); ?></span>
									</div>
									<h2 id="doc-title" class="text-lg md:text-xl font-bold text-slate-900 leading-snug" data-tooltip="<?php esc_attr_e( 'Título do documento', 'apollo-social' ); ?>">
										<?php echo esc_html( $document_title ); ?>
									</h2>
								</div>
							</div>

							<div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
								<span id="doc-meta-main" class="flex items-center gap-1.5" data-tooltip="<?php esc_attr_e( 'Data de criação', 'apollo-social' ); ?>">
									< i class="ri-time-line"></i> <?php echo esc_html( sprintf( __( 'Criado em %s', 'apollo-social' ), $document_created ) ); ?>
								</span>
								<span id="doc-meta-second" class="flex items-center gap-1.5" data-tooltip="<?php esc_attr_e( 'Número de páginas', 'apollo-social' ); ?>">
										<i class="ri-file-list-2-line"></i> <?php echo esc_html( sprintf( _n( '%d página', '%d páginas', $document_pages, 'apollo-social' ), $document_pages ) ); ?>
								</span>
							</div>
						</div>

						<!-- PDF Preview Container -->
						<div class="flex-1 bg-slate-50 rounded-xl border border-slate-200 relative overflow-hidden flex flex-col" data-tooltip="<?php esc_attr_e( 'Conteúdo do documento', 'apollo-social' ); ?>">
							<!-- Toolbar -->
							<div class="flex items-center justify-between px-4 py-2 bg-white border-b border-slate-200">
								<div class="flex items-center gap-2">
									<span class="h-2.5 w-2.5 rounded-full bg-red-400/80"></span>
									<span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
									<span class="h-2.5 w-2.5 rounded-full bg-green-400/80"></span>
								</div>
								<span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider"><?php esc_html_e( 'Preview Visual', 'apollo-social' ); ?></span>
							</div>

							<!-- Page Content -->
							<div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
								<div class="max-w-[600px] mx-auto bg-white shadow-lg border border-slate-100 min-h-[600px] p-8 md:p-12 text-slate-800 text-sm leading-relaxed space-y-4">
									<div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
										<div>
											<p class="text-[10px] uppercase tracking-widest text-slate-400" data-tooltip="<?php esc_attr_e( 'Identificação do documento', 'apollo-social' ); ?>">Apollo::Rio · <?php esc_html_e( 'Documento Oficial', 'apollo-social' ); ?></p>
											<h3 class="font-bold text-base" data-tooltip="<?php echo esc_attr( $document_title ); ?>"><?php echo esc_html( $document_title ); ?></h3>
										</div>
										<div class="h-8 w-8 bg-slate-900 rounded-full flex items-center justify-center text-white" data-tooltip="<?php esc_attr_e( 'Logo Apollo', 'apollo-social' ); ?>">
											<i class="ri-command-fill"></i>
										</div>
									</div>

									<div class="document-content" data-tooltip="<?php esc_attr_e( 'Conteúdo do documento', 'apollo-social' ); ?>">
										<?php echo wp_kses_post( $document_content ); ?>
									</div>

									<div class="mt-8 pt-4 border-t border-dashed border-slate-200">
										<p class="text-[10px] text-slate-400 text-center"><?php esc_html_e( 'Fim do documento (Preview)', 'apollo-social' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>

				<!-- RIGHT: ACTION PANEL -->
				<div class="w-full lg:w-[380px] shrink-0 flex flex-col gap-4">
					<!-- Signers Status Card -->
					<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5" data-tooltip="<?php esc_attr_e( 'Status das assinaturas', 'apollo-social' ); ?>">
						<div class="flex items-center justify-between mb-4">
							<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider" data-tooltip="<?php esc_attr_e( 'Lista de assinantes', 'apollo-social' ); ?>">
								<?php esc_html_e( 'Fluxo de Assinaturas', 'apollo-social' ); ?>
							</h3>
							<span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md" data-tooltip="<?php esc_attr_e( 'Progresso das assinaturas', 'apollo-social' ); ?>">
								<span id="sign-count-label"><?php echo esc_html( $signed_count . '/' . $total_signers ); ?></span>
							</span>
						</div>

						<div class="space-y-3">
							<!-- Current User -->
							<div id="signer-you-card" class="flex items-center justify-between p-3 rounded-xl <?php echo $current_user_signed ? 'bg-emerald-50 border border-emerald-100' : 'bg-amber-50 border border-amber-100'; ?>" data-tooltip="<?php esc_attr_e( 'Seu status de assinatura', 'apollo-social' ); ?>">
								<div class="flex items-center gap-3">
									<div class="h-8 w-8 rounded-full bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-xs font-bold text-white shadow-sm" data-tooltip="<?php echo esc_attr( $user_obj->display_name ); ?>">
										<?php echo esc_html( $current_user_initials ); ?>
									</div>
									<div>
										<p class="text-xs font-bold text-slate-900" data-tooltip="<?php esc_attr_e( 'Você é o assinante atual', 'apollo-social' ); ?>"><?php esc_html_e( 'Você', 'apollo-social' ); ?></p>
										<p class="text-[10px] text-slate-500" id="sign-user-id-label" data-tooltip="<?php esc_attr_e( 'Seu CPF (parcialmente oculto)', 'apollo-social' ); ?>"><?php echo esc_html( $cpf_display ); ?></p>
									</div>
								</div>
								<?php if ( $current_user_signed ) : ?>
								<span id="signer-you-status" class="flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-white px-2 py-1 rounded-full shadow-sm" data-tooltip="<?php esc_attr_e( 'Você já assinou este documento', 'apollo-social' ); ?>">
									<i class="ri-check-line"></i> <?php esc_html_e( 'Assinado', 'apollo-social' ); ?>
								</span>
								<?php else : ?>
								<span id="signer-you-status" class="flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-white px-2 py-1 rounded-full shadow-sm" data-tooltip="<?php esc_attr_e( 'Aguardando sua assinatura', 'apollo-social' ); ?>">
									<span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> <?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
								</span>
								<?php endif; ?>
							</div>

							<!-- Other Signers -->
							<?php
							foreach ( $signers as $signer ) :
								if ( isset( $signer['user_id'] ) && (int) $signer['user_id'] === $current_user_id ) {
									continue;
								}
								$signer_name     = $signer['name'] ?? __( 'Parceiro', 'apollo-social' );
								$signer_signed   = ! empty( $signer['signed'] );
								$signer_initials = mb_strtoupper( mb_substr( $signer_name, 0, 2 ) );
								?>
							<div class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-100" data-tooltip="<?php echo esc_attr( $signer_name ); ?>">
								<div class="flex items-center gap-3">
									<div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500">
										<?php echo esc_html( $signer_initials ); ?>
									</div>
									<div>
										<p class="text-xs font-bold text-slate-700"><?php echo esc_html( $signer_name ); ?></p>
										<p class="text-[10px] text-slate-400"><?php echo esc_html( $signer['role'] ?? __( 'Parte', 'apollo-social' ) ); ?></p>
									</div>
								</div>
								<?php if ( $signer_signed ) : ?>
								<span class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full" data-tooltip="<?php esc_attr_e( 'Assinatura confirmada', 'apollo-social' ); ?>">
									<i class="ri-check-line"></i> <?php esc_html_e( 'Assinado', 'apollo-social' ); ?>
								</span>
								<?php else : ?>
								<span class="flex items-center gap-1 text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-full" data-tooltip="<?php esc_attr_e( 'Aguardando assinatura', 'apollo-social' ); ?>">
									<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> <?php esc_html_e( 'Pendente', 'apollo-social' ); ?>
								</span>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						</div>
					</section>

					<!-- Signature Action Card -->
					<?php if ( ! $current_user_signed ) : ?>

						<?php if ( ! $user_can_sign ) : ?>
					<!-- ===== BLOCKED: User cannot sign (no CPF or passport only) ===== -->
					<section class="bg-white rounded-3xl shadow-sm border border-red-200 p-5 flex-1 flex flex-col" style="opacity: 0.5; pointer-events: none;" data-tooltip="<?php esc_attr_e( 'Assinatura bloqueada', 'apollo-social' ); ?>">
						<div class="flex items-center gap-2 mb-4">
							<i class="ri-lock-line text-red-500 text-lg"></i>
							<h3 class="text-sm font-bold text-red-700 uppercase tracking-wider">
								<?php esc_html_e( 'Assinatura Bloqueada', 'apollo-social' ); ?>
							</h3>
						</div>

						<!-- Blocked Message -->
						<div class="p-4 bg-red-50 border border-red-100 rounded-xl mb-4">
							<p class="text-sm text-red-700 font-medium mb-2">
								<i class="ri-error-warning-fill"></i>
								<?php echo esc_html( $sign_blocked_reason ); ?>
							</p>
							<?php if ( $user_has_passport_only ) : ?>
							<p class="text-xs text-red-600 mt-2">
								<?php esc_html_e( 'A Lei 14.063/2020 exige CPF para assinaturas digitais com validade jurídica no Brasil.', 'apollo-social' ); ?>
							</p>
							<?php endif; ?>
						</div>

						<!-- Disabled Form Preview -->
						<div class="space-y-4 flex-1">
							<div class="space-y-3">
								<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 cursor-not-allowed">
									<input type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300" disabled>
									<span class="text-xs text-slate-400">
										<?php esc_html_e( 'Li e concordo com o conteúdo do documento.', 'apollo-social' ); ?>
									</span>
								</label>
								<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 cursor-not-allowed">
									<input type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300" disabled>
									<span class="text-xs text-slate-400">
										<?php esc_html_e( 'Autorizo o uso da minha assinatura digital.', 'apollo-social' ); ?>
									</span>
								</label>
							</div>

							<div class="space-y-3 pt-2">
								<button disabled class="w-full flex items-center justify-center gap-2 bg-slate-300 text-slate-500 font-bold py-3 rounded-xl cursor-not-allowed">
									<i class="ri-lock-line text-lg"></i>
									<?php esc_html_e( 'Assinar com gov.br', 'apollo-social' ); ?>
								</button>
								<button disabled class="w-full flex items-center justify-center gap-2 bg-slate-100 border border-slate-200 text-slate-400 font-bold py-3 rounded-xl cursor-not-allowed">
									<i class="ri-lock-line text-lg"></i>
									<?php esc_html_e( 'Certificado ICP-Brasil', 'apollo-social' ); ?>
								</button>
							</div>
						</div>

							<?php if ( ! $user_has_passport_only ) : ?>
						<div class="mt-4 pt-4 border-t border-slate-100">
							<a href="<?php echo esc_url( home_url( '/perfil/editar/' ) ); ?>" class="w-full flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-all" style="opacity: 1; pointer-events: auto;" data-tooltip="<?php esc_attr_e( 'Atualizar seu CPF no perfil', 'apollo-social' ); ?>">
								<i class="ri-user-settings-line text-lg"></i>
								<?php esc_html_e( 'Cadastrar CPF no Perfil', 'apollo-social' ); ?>
							</a>
						</div>
						<?php endif; ?>
					</section>

					<?php else : ?>
					<!-- ===== ENABLED: User can sign (has valid CPF) ===== -->
					<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 flex-1 flex flex-col" data-tooltip="<?php esc_attr_e( 'Realizar assinatura digital', 'apollo-social' ); ?>">
						<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">
							<?php esc_html_e( 'Realizar Assinatura', 'apollo-social' ); ?>
						</h3>

						<!-- Stepper -->
						<div class="flex items-center gap-2 mb-6" data-tooltip="<?php esc_attr_e( 'Progresso da assinatura', 'apollo-social' ); ?>">
							<div data-step="1" class="flex-1 h-1 rounded-full bg-slate-900 transition-colors"></div>
							<div data-step="2" class="flex-1 h-1 rounded-full bg-slate-200 transition-colors"></div>
							<div data-step="3" class="flex-1 h-1 rounded-full bg-slate-200 transition-colors"></div>
						</div>

						<div class="space-y-4 flex-1">
							<!-- Terms Checkboxes -->
							<div class="space-y-3">
								<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer group" data-tooltip="<?php esc_attr_e( 'Confirme que leu o documento', 'apollo-social' ); ?>">
									<input id="chk-terms" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
									<span class="text-xs text-slate-600 group-hover:text-slate-900">
										<?php esc_html_e( 'Li e concordo com o conteúdo do documento.', 'apollo-social' ); ?>
									</span>
								</label>
								<label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer group" data-tooltip="<?php esc_attr_e( 'Autorize o uso da assinatura digital', 'apollo-social' ); ?>">
									<input id="chk-rep" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
									<span class="text-xs text-slate-600 group-hover:text-slate-900">
										<?php esc_html_e( 'Autorizo o uso da minha assinatura digital.', 'apollo-social' ); ?>
									</span>
								</label>
								<p id="sign-error" class="hidden text-[11px] text-red-500 font-medium px-1 flex items-center gap-1">
									<i class="ri-error-warning-line"></i> <?php esc_html_e( 'Marque as opções acima para continuar.', 'apollo-social' ); ?>
								</p>
							</div>

							<!-- Action Buttons -->
							<div class="space-y-3 pt-2">
								<button
									id="btn-sign-govbr"
									data-provider="govbr"
									class="w-full flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-lg shadow-slate-900/10 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
									data-tooltip="<?php esc_attr_e( 'Assinar usando conta gov.br', 'apollo-social' ); ?>"
								>
									<i class="ri-shield-check-line text-lg"></i>
									<?php esc_html_e( 'Assinar com gov.br', 'apollo-social' ); ?>
								</button>

								<button
									id="btn-sign-icp"
									data-provider="icp"
									class="w-full flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold py-3 rounded-xl active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
									data-tooltip="<?php esc_attr_e( 'Assinar usando certificado digital ICP-Brasil', 'apollo-social' ); ?>"
								>
									<i class="ri-key-2-line text-lg"></i>
									<?php esc_html_e( 'Certificado ICP-Brasil', 'apollo-social' ); ?>
								</button>
							</div>
						</div>

						<!-- Result Block (Hidden initially) -->
						<div id="sign-result" class="hidden mt-4 p-4 bg-emerald-50 border border-emerald-100 rounded-xl" data-tooltip="<?php esc_attr_e( 'Detalhes da assinatura', 'apollo-social' ); ?>">
							<div class="flex items-center gap-2 mb-2 text-emerald-800 font-bold text-sm">
								<i class="ri-checkbox-circle-fill text-lg"></i>
								<?php esc_html_e( 'Assinado com Sucesso', 'apollo-social' ); ?>
							</div>
							<div class="text-[10px] text-emerald-700 space-y-1 font-mono">
								<p id="signed-at" data-tooltip="<?php esc_attr_e( 'Data e hora da assinatura', 'apollo-social' ); ?>">Data: --</p>
								<p id="signed-code" data-tooltip="<?php esc_attr_e( 'Código da assinatura', 'apollo-social' ); ?>">Cod: --</p>
								<p id="signed-hash" class="truncate" data-tooltip="<?php esc_attr_e( 'Hash de verificação', 'apollo-social' ); ?>">Hash: --</p>
							</div>
						</div>

						<div class="mt-4 text-[10px] text-slate-400 text-center">
							<?php esc_html_e( 'Ambiente seguro Apollo::rio · disponível para toda a comunidade', 'apollo-social' ); ?>
						</div>
					</section>
					<?php endif; ?>
					<?php else : ?>
					<!-- Already Signed Card -->
					<section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5" data-tooltip="<?php esc_attr_e( 'Você já assinou este documento', 'apollo-social' ); ?>">
						<div class="text-center py-6">
							<div class="h-16 w-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-4">
								<i class="ri-check-double-line text-3xl text-emerald-600"></i>
							</div>
							<h3 class="text-lg font-bold text-slate-900 mb-2"><?php esc_html_e( 'Documento Assinado', 'apollo-social' ); ?></h3>
							<p class="text-sm text-slate-500 mb-4"><?php esc_html_e( 'Você já assinou este documento. Aguarde as demais assinaturas para finalização.', 'apollo-social' ); ?></p>
							<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-900 hover:text-slate-700" data-tooltip="<?php esc_attr_e( 'Voltar para lista de documentos', 'apollo-social' ); ?>">
								<i class="ri-arrow-left-line"></i>
								<?php esc_html_e( 'Voltar para Documentos', 'apollo-social' ); ?>
							</a>
						</div>
					</section>
					<?php endif; ?>
				</div>
			</div>
		</main>

		<!-- BOTTOM NAVIGATION (MOBILE) -->
		<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-slate-200/50 pb-safe">
			<div class="max-w-2xl mx-auto w-full px-4 py-2 flex items-end justify-between h-[60px]">
				<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e( 'Ver agenda', 'apollo-social' ); ?>">
					<i class="ri-calendar-line"></i>
					<span><?php esc_html_e( 'Agenda', 'apollo-social' ); ?></span>
				</a>
				<a href="<?php echo esc_url( home_url( '/explorar/' ) ); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e( 'Explorar eventos', 'apollo-social' ); ?>">
					<i class="ri-compass-3-line"></i>
					<span><?php esc_html_e( 'Explorar', 'apollo-social' ); ?></span>
				</a>
				<div class="relative -top-5">
					<button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(15,23,42,0.6)] opacity-50 cursor-default" disabled data-tooltip="<?php esc_attr_e( 'Criar novo', 'apollo-social' ); ?>">
						<i class="ri-add-line text-3xl"></i>
					</button>
				</div>
				<a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="nav-btn active w-14 pb-1" data-tooltip="<?php esc_attr_e( 'Ver documentos', 'apollo-social' ); ?>">
					<i class="ri-file-text-line"></i>
					<span><?php esc_html_e( 'Docs', 'apollo-social' ); ?></span>
				</a>
				<a href="<?php echo esc_url( home_url( '/perfil/' ) ); ?>" class="nav-btn w-14 pb-1" data-tooltip="<?php esc_attr_e( 'Ver perfil', 'apollo-social' ); ?>">
					<i class="ri-user-3-line"></i>
					<span><?php esc_html_e( 'Perfil', 'apollo-social' ); ?></span>
				</a>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const chkTerms = document.getElementById('chk-terms');
	const chkRep = document.getElementById('chk-rep');
	const errorEl = document.getElementById('sign-error');
	const btnGov = document.getElementById('btn-sign-govbr');
	const btnIcp = document.getElementById('btn-sign-icp');
	const signResult = document.getElementById('sign-result');
	const signedAt = document.getElementById('signed-at');
	const signedCode = document.getElementById('signed-code');
	const signedHash = document.getElementById('signed-hash');
	const headerPill = document.getElementById('doc-status-pill-header');
	const signerYouStatus = document.getElementById('signer-you-status');
	const signerYouCard = document.getElementById('signer-you-card');
	const signCountLabel = document.getElementById('sign-count-label');

	function generateHash() {
		const chars = 'abcdef0123456789';
		let h = '';
		for (let i = 0; i < 40; i++) {
			h += chars[Math.floor(Math.random() * chars.length)];
		}
		return h;
	}

	async function handleSign(provider) {
		if (!chkTerms || !chkRep) return;

		if (!chkTerms.checked || !chkRep.checked) {
			if (errorEl) errorEl.classList.remove('hidden');
			return;
		}
		if (errorEl) errorEl.classList.add('hidden');

		if (btnGov) btnGov.disabled = true;
		if (btnIcp) btnIcp.disabled = true;

		try {
			// Get signer info
			const signerName = '<?php echo esc_js( $user_obj->display_name ); ?>';
			const signerEmail = '<?php echo esc_js( $user_obj->user_email ); ?>';
			const signerId = <?php echo $current_user_id > 0 ? (int) $current_user_id : 'null'; ?>;

			const response = await fetch('<?php echo esc_url( rest_url( 'apollo/v1/doc/' . $document_id . '/assinar' ) ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
				},
				body: JSON.stringify({
					name: signerName,
					email: signerEmail,
					role: 'signer',
					consent: true,
					signature_method: provider === 'icp' ? 'pki-external-v1' : 'e-sign-basic'
				})
			});

			const data = await response.json();

			if (data.success) {
				const signature = data.signature || {};
				const signedDate = signature.signed_at || new Date().toISOString();
				const formatted = new Date(signedDate).toLocaleString('pt-BR');

				if (signedAt) signedAt.textContent = '<?php echo esc_js( __( 'Data:', 'apollo-social' ) ); ?> ' + formatted;
				if (signedCode) signedCode.textContent = '<?php echo esc_js( __( 'Método:', 'apollo-social' ) ); ?> ' + (signature.signature_method || 'e-sign-basic');
				if (signedHash) {
					const hash = signature.pdf_hash || '';
					const hashShort = hash ? hash.substring(0, 16) + '...' : '--';
					signedHash.textContent = 'Hash: ' + hashShort;
				}

				if (signResult) signResult.classList.remove('hidden');

				if (headerPill) {
					headerPill.className = 'hidden md:inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700';
					headerPill.innerHTML = '<span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span> <?php echo esc_js( __( 'Assinado', 'apollo-social' ) ); ?>';
				}

				if (signerYouCard && signerYouStatus) {
					signerYouCard.className = 'flex items-center justify-between p-3 rounded-xl bg-emerald-50 border border-emerald-100';
					signerYouStatus.className = 'flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-white px-2 py-1 rounded-full shadow-sm';
					signerYouStatus.innerHTML = '<i class="ri-check-line"></i> <?php echo esc_js( __( 'Assinado', 'apollo-social' ) ); ?>';
				}

				if (signCountLabel && data.total_signatures) {
					// Update count (assuming we know total signers from page load)
					const currentText = signCountLabel.textContent;
					const parts = currentText.split('/');
					if (parts.length === 2) {
						const total = parseInt(parts[1]) || 1;
						signCountLabel.textContent = data.total_signatures + '/' + total;
					} else {
						signCountLabel.textContent = data.total_signatures + '/1';
					}
				}
			} else {
				alert(data.message || '<?php echo esc_js( __( 'Erro ao assinar documento', 'apollo-social' ) ); ?>');
				if (btnGov) btnGov.disabled = false;
				if (btnIcp) btnIcp.disabled = false;
			}
		} catch (err) {
			console.error(err);
			alert('<?php echo esc_js( __( 'Erro de conexão', 'apollo-social' ) ); ?>');
			if (btnGov) btnGov.disabled = false;
			if (btnIcp) btnIcp.disabled = false;
		}
	}

	if (btnGov) {
		btnGov.addEventListener('click', () => handleSign('govbr'));
	}
	if (btnIcp) {
		btnIcp.addEventListener('click', () => handleSign('icp'));
	}
});
</script>

<?php wp_footer(); ?>
</body>
</html>

