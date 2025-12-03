<?php
/**
 * Template: Apollo Word Editor (Documentos/Planilhas)
 *
 * Editor WYSIWYG estilo Word com formatação rica e exportação PDF.
 * Baseado no template aprovado: design-library/docs-editor.html
 *
 * URLs:
 *   /doc/new     → Criar novo documento
 *   /doc/{id}    → Editar documento existente
 *   /pla/new     → Criar nova planilha
 *   /pla/{id}    → Editar planilha existente
 *
 * @package Apollo\Modules\Documents
 * @since   1.1.0
 * @see     apollo-core/templates/design-library/docs-editor.html
 */

declare( strict_types=1 );

use Apollo\Modules\Documents\DocumentsHelpers;

// Get document data
$type_label   = $type === 'documento' ? 'Documento' : 'Planilha';
$is_new       = $mode === 'new';
$document_id  = $is_new ? 0 : ( $document['id'] ?? 0 );
$file_id      = $is_new ? '' : ( $document['file_id'] ?? '' );
$doc_title    = $is_new ? 'Novo ' . $type_label : ( $document['title'] ?? '' );
$doc_content  = $is_new ? '<h1>Novo ' . $type_label . '</h1><p>Comece a escrever seu documento aqui...</p>' : ( $document['content'] ?? '' );
$doc_status   = $document['status'] ?? 'draft';
$doc_version  = (int) ( $document['version'] ?? 1 );

// Get status and type info with tooltips
$status_info = DocumentsHelpers::get_status_info( $doc_status );
$type_info   = DocumentsHelpers::get_type_info( $type );

// Field tooltips
$tooltips = array(
	'title'        => DocumentsHelpers::get_field_tooltip( 'title' ),
	'status'       => $status_info['tooltip'],
	'type'         => $type_info['tooltip'],
	'version'      => DocumentsHelpers::get_version_tooltip( $doc_version ),
	'save_status'  => DocumentsHelpers::get_field_tooltip( 'save_status' ),
	'export_pdf'   => DocumentsHelpers::get_field_tooltip( 'export_pdf' ),
	'prepare_sign' => DocumentsHelpers::get_field_tooltip( 'prepare_sign' ),
	'font_family'  => DocumentsHelpers::get_field_tooltip( 'font_family' ),
	'font_size'    => DocumentsHelpers::get_field_tooltip( 'font_size' ),
	'font_weight'  => DocumentsHelpers::get_field_tooltip( 'font_weight' ),
	'text_color'   => DocumentsHelpers::get_field_tooltip( 'text_color' ),
	'text_align'   => DocumentsHelpers::get_field_tooltip( 'text_align' ),
);

// Can edit/sign based on status
$can_edit = DocumentsHelpers::can_edit( $doc_status );
$can_sign = DocumentsHelpers::can_sign( $doc_status ) || $doc_status === 'draft';

// Generate nonce for secure AJAX operations
$ajax_nonce = wp_create_nonce( 'apollo_document_editor' );
$ajax_url   = admin_url( 'admin-ajax.php' );

// Don't use standard WP header - this is a full-page app
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Apollo :: Editor - <?php echo esc_html( $doc_title ); ?></title>

	<!-- UNI.CSS Global Design System (MANDATORY) -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
	<script src="https://assets.apollo.rio.br/base.js" defer></script>

	<!-- Material Icons -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />

	<!-- Remix Icons (for consistency with other Apollo templates) -->
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet" />

	<!-- Sign Document Module -->
	<script src="<?php echo esc_url( plugins_url( 'assets/js/sign-document.js', dirname( __DIR__ ) ) ); ?>" defer></script>

	<!-- Default Font (matches uni.css Urbanist) -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Open+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

	<style>
		:root {
			--apollo-primary: #3498db;
			--apollo-bg: #f0f2f5;
			--paper-width: 210mm;
			--paper-height: 297mm;
			--toolbar-height: 70px;
		}

		body {
			font-family: var(--ap-font-primary, 'Urbanist', system-ui, sans-serif);
			background-color: var(--apollo-bg);
			color: var(--ap-text-primary, #1a1a1a);
			margin: 0;
			height: 100vh;
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}

		/* --- Top Navigation Bar --- */
		.apollo-navbar {
			height: 50px;
			background: var(--ap-bg-card, #fff);
			border-bottom: 1px solid var(--ap-border-light, #e0e0e0);
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0 20px;
			flex-shrink: 0;
		}

		.brand {
			display: flex;
			align-items: center;
			font-weight: 600;
			color: var(--ap-text-primary, #2c3e50);
			font-size: 16px;
			gap: 10px;
		}

		.ripple-dot {
			width: 10px;
			height: 10px;
			background-color: var(--ap-color-primary, var(--apollo-primary));
			border-radius: 50%;
			position: relative;
			animation: rippleShadow 2s infinite ease-out;
		}

		@keyframes rippleShadow {
			0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.6); }
			100% { box-shadow: 0 0 0 12px rgba(52, 152, 219, 0); }
		}

		.file-name {
			color: var(--ap-text-primary, #333);
			font-size: 14px;
			font-weight: 600;
			border: 1px solid transparent;
			padding: 4px 10px;
			border-radius: var(--ap-radius-sm, 6px);
			outline: none;
			min-width: 200px;
			background: transparent;
		}

		.file-name:hover,
		.file-name:focus {
			border-color: var(--ap-border-default, #e0e0e0);
			background: var(--ap-bg-muted, #f9f9f9);
		}

		.navbar-actions {
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.save-status {
			font-size: 12px;
			color: var(--ap-text-muted, #888);
			display: flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border-radius: var(--ap-radius-sm, 6px);
		}

		.save-status.saving {
			background: rgba(234, 179, 8, 0.1);
			color: #92400e;
		}

		.save-status.saved {
			background: rgba(16, 185, 129, 0.1);
			color: #065f46;
		}

		.save-status.error {
			background: rgba(239, 68, 68, 0.1);
			color: #dc2626;
		}

		/* Use .ap-btn classes from uni.css */
		.btn-navbar {
			padding: 8px 16px;
			border: 1px solid var(--ap-border-default, #ddd);
			background: var(--ap-bg-card, #fff);
			border-radius: var(--ap-radius-md, 8px);
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 6px;
			transition: var(--ap-transition-fast, all 0.2s);
			color: var(--ap-text-secondary, #555);
		}

		.btn-navbar:hover {
			background: var(--ap-bg-muted, #f5f5f5);
			border-color: var(--ap-border-dark, #ccc);
		}

		.btn-navbar.primary {
			background: var(--ap-text-primary, #0f172a);
			color: var(--ap-bg-main, #fff);
			border-color: var(--ap-text-primary, #0f172a);
		}

		.btn-navbar.primary:hover {
			opacity: 0.9;
		}

		.btn-navbar.success {
			background: var(--ap-color-success, #10b981);
			color: #fff;
			border-color: var(--ap-color-success, #10b981);
		}

		/* --- Editor Toolbar --- */
		.editor-toolbar {
			background: var(--ap-bg-card, #fff);
			padding: 10px 20px;
			border-bottom: 1px solid var(--ap-border-light, #e0e0e0);
			display: flex;
			gap: 12px;
			align-items: center;
			flex-wrap: nowrap;
			overflow-x: auto;
			box-shadow: var(--ap-shadow-sm, 0 2px 6px rgba(0,0,0,0.02));
			flex-shrink: 0;
			z-index: 10;
		}

		.tool-group {
			display: flex;
			align-items: center;
			gap: 6px;
			padding-right: 12px;
			border-right: 1px solid var(--ap-border-light, #eee);
		}

		.tool-group:last-child {
			border-right: none;
		}

		.form-select-sm {
			border-radius: var(--ap-radius-sm, 4px);
			border: 1px solid var(--ap-border-default, #ddd);
			font-size: 13px;
			cursor: pointer;
			padding: 6px 10px;
			background: var(--ap-bg-card, #fff);
			color: var(--ap-text-primary, #333);
		}

		#fontSelector { width: 180px; }
		#fontWeight { width: 130px; }
		#fontSize { width: 70px; }

		.btn-tool {
			width: 32px;
			height: 32px;
			padding: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			border: 1px solid transparent;
			background: transparent;
			border-radius: var(--ap-radius-sm, 4px);
			color: var(--ap-text-secondary, #555);
			transition: var(--ap-transition-fast, all 0.2s);
			cursor: pointer;
		}

		.btn-tool:hover {
			background: var(--ap-bg-muted, #f5f5f5);
			border-color: var(--ap-border-default, #ddd);
		}

		.btn-tool.active {
			background: rgba(59, 130, 246, 0.1);
			color: var(--ap-color-info, #3b82f6);
			border-color: rgba(59, 130, 246, 0.3);
		}

		.btn-tool .material-symbols-rounded {
			font-size: 20px;
		}

		#textColor {
			width: 32px;
			height: 32px;
			padding: 2px;
			border: none;
			background: none;
			cursor: pointer;
		}

		/* --- Main Workspace --- */
		.workspace {
			flex: 1;
			overflow-y: auto;
			padding: 40px;
			display: flex;
			justify-content: center;
			position: relative;
			background: var(--apollo-bg);
		}

		.document-page {
			background: var(--ap-bg-card, white);
			width: var(--paper-width);
			min-height: var(--paper-height);
			padding: 25mm;
			box-shadow: var(--ap-shadow-lg, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
			margin-bottom: 40px;
			outline: none;
			line-height: 1.6;
		}

		.document-page:focus {
			outline: none;
		}

		.document-page h1 {
			font-size: 28px;
			font-weight: 600;
			margin-bottom: 16px;
			color: var(--ap-text-primary, #1a1a1a);
		}

		.document-page h2 {
			font-size: 22px;
			font-weight: 600;
			margin-bottom: 12px;
			color: var(--ap-text-primary, #333);
		}

		.document-page h3 {
			font-size: 18px;
			font-weight: 600;
			margin-bottom: 10px;
			color: var(--ap-text-secondary, #444);
		}

		.document-page p {
			font-size: 14px;
			margin-bottom: 12px;
			color: var(--ap-text-secondary, #444);
		}

		.document-page table {
			width: 100%;
			border-collapse: collapse;
			margin: 16px 0;
		}

		.document-page th,
		.document-page td {
			border: 1px solid var(--ap-border-default, #ddd);
			padding: 8px 12px;
			text-align: left;
		}

		.document-page th {
			background: var(--ap-bg-muted, #f5f5f5);
			font-weight: 600;
		}

		/* Scrollbar */
		::-webkit-scrollbar { width: 8px; height: 8px; }
		::-webkit-scrollbar-track { background: var(--ap-bg-muted, #f1f1f1); }
		::-webkit-scrollbar-thumb { background: var(--ap-border-default, #ccc); border-radius: 4px; }
		::-webkit-scrollbar-thumb:hover { background: var(--ap-border-dark, #bbb); }

		/* Print / PDF Styles */
		@media print {
			.apollo-navbar, .editor-toolbar { display: none !important; }
			.workspace { padding: 0; overflow: visible; }
			.document-page {
				box-shadow: none;
				margin: 0;
				padding: 20mm;
			}
		}

		/* Responsive */
		@media (max-width: 900px) {
			.document-page {
				width: 100%;
				padding: 20px;
			}
			.navbar-actions .btn-navbar span {
				display: none;
			}
			.editor-toolbar {
				padding: 8px 12px;
				gap: 8px;
			}
			.tool-group {
				padding-right: 8px;
			}
		}

		/* Spreadsheet Mode */
		.spreadsheet-container {
			width: 100%;
			overflow-x: auto;
		}

		.spreadsheet-grid {
			width: 100%;
			border-collapse: collapse;
			font-family: var(--ap-font-primary);
		}

		.spreadsheet-grid th {
			background: var(--ap-bg-muted, #f7fafc);
			border: 1px solid var(--ap-border-light, #e2e8f0);
			padding: 8px;
			font-weight: 600;
			font-size: 12px;
			color: var(--ap-text-muted, #718096);
			text-align: center;
			position: sticky;
			top: 0;
		}

		.spreadsheet-grid td {
			border: 1px solid var(--ap-border-light, #e2e8f0);
			padding: 0;
		}

		.spreadsheet-cell {
			border: none;
			width: 100%;
			padding: 8px;
			font-size: 14px;
			font-family: 'Fira Code', 'Monaco', monospace;
			background: transparent;
		}

		.spreadsheet-cell:focus {
			outline: 2px solid var(--ap-color-info, #3b82f6);
			background: rgba(59, 130, 246, 0.05);
		}

		/* Dark mode support */
		body.dark-mode {
			--apollo-bg: #1e293b;
		}

		body.dark-mode .document-page {
			background: #0f172a;
			color: #f1f5f9;
		}

		body.dark-mode .document-page h1,
		body.dark-mode .document-page h2,
		body.dark-mode .document-page h3,
		body.dark-mode .document-page p {
			color: #e2e8f0;
		}
	</style>
</head>

<body>
	<!-- @section:navbar -->
	<div class="apollo-navbar">
		<div class="brand">
			<div class="ripple-dot"></div>
			<span>Apollo::Rio</span>
			<span style="color: var(--ap-text-disabled, #ccc);">/</span>
			<span class="file-name <?php echo $can_edit ? '' : 'readonly'; ?>"
					contenteditable="<?php echo $can_edit ? 'true' : 'false'; ?>"
					id="document-title"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['title'] ); ?>"><?php echo esc_html( $doc_title ); ?></span>
		</div>

		<div class="navbar-actions">
			<!-- Status Badge with Tooltip -->
			<span class="ap-badge <?php echo esc_attr( $status_info['class'] ); ?>"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['status'] ); ?>">
				<span class="material-symbols-rounded" style="font-size: 14px;"><?php echo esc_html( $status_info['icon'] ); ?></span>
				<?php echo esc_html( $status_info['label'] ); ?>
			</span>

			<!-- Type Badge -->
			<span class="ap-badge ap-badge--muted"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['type'] ); ?>">
				<i class="<?php echo esc_attr( $type_info['icon'] ); ?>"></i>
				<?php echo esc_html( $type_info['label'] ); ?>
			</span>

			<!-- Version Badge -->
			<span class="ap-badge ap-badge--outline"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['version'] ); ?>">
				v<?php echo esc_html( $doc_version ); ?>
			</span>

			<span class="save-status saved" id="save-status"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['save_status'] ); ?>">
				<span class="material-symbols-rounded" style="font-size: 16px;">cloud_done</span>
				<span id="save-text"><?php echo $is_new ? 'Novo' : 'Salvo'; ?></span>
			</span>

			<button class="btn-navbar" onclick="window.location.href='/sign'"
					data-ap-tooltip="Voltar à lista de documentos">
				<span class="material-symbols-rounded">arrow_back</span>
				<span>Voltar</span>
			</button>

			<?php if ( $can_edit ) : ?>
			<button class="btn-navbar" id="btn-save"
					data-ap-tooltip="Salvar documento (Ctrl+S)">
				<span class="material-symbols-rounded">save</span>
				<span>Salvar</span>
			</button>
			<?php endif; ?>

			<button class="btn-navbar primary" id="btn-export-pdf"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['export_pdf'] ); ?>">
				<span class="material-symbols-rounded">picture_as_pdf</span>
				<span>Exportar PDF</span>
			</button>

			<?php if ( ! $is_new && $can_sign ) : ?>
			<button class="btn-navbar success" id="btn-prepare-sign"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['prepare_sign'] ); ?>">
				<i class="ri-quill-pen-line"></i>
				<span>Assinar</span>
			</button>
			<?php endif; ?>
		</div>
	</div>

	<!-- @section:toolbar -->
	<?php if ( $type === 'documento' && $can_edit ) : ?>
	<div class="editor-toolbar">
		<!-- Font Family -->
		<div class="tool-group">
			<select id="fontSelector" class="form-select-sm"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['font_family'] ); ?>">
				<option value="Poppins">Poppins</option>
				<option value="Roboto">Roboto</option>
				<option value="Open Sans">Open Sans</option>
				<option value="Montserrat">Montserrat</option>
				<option value="Lato">Lato</option>
				<option value="Inter">Inter</option>
				<option value="Urbanist" selected>Urbanist</option>
				<option value="Oswald">Oswald</option>
				<option value="Raleway">Raleway</option>
				<option value="Nunito">Nunito</option>
				<option value="Ubuntu">Ubuntu</option>
				<option value="Playfair Display">Playfair Display</option>
				<option value="Merriweather">Merriweather</option>
				<option value="PT Sans">PT Sans</option>
				<option value="Work Sans">Work Sans</option>
				<option value="DM Sans">DM Sans</option>
				<option value="Fira Sans">Fira Sans</option>
				<option value="Quicksand">Quicksand</option>
				<option value="Barlow">Barlow</option>
				<option value="Manrope">Manrope</option>
			</select>
		</div>

		<!-- Weight & Size -->
		<div class="tool-group">
			<select id="fontWeight" class="form-select-sm"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['font_weight'] ); ?>">
				<option value="300">Light (300)</option>
				<option value="400" selected>Regular (400)</option>
				<option value="500">Medium (500)</option>
				<option value="600">Semi Bold (600)</option>
				<option value="700">Bold (700)</option>
				<option value="800">Extra Bold (800)</option>
			</select>
			<select id="fontSize" class="form-select-sm"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['font_size'] ); ?>">
				<option value="10px">10</option>
				<option value="12px">12</option>
				<option value="14px" selected>14</option>
				<option value="16px">16</option>
				<option value="18px">18</option>
				<option value="20px">20</option>
				<option value="24px">24</option>
				<option value="28px">28</option>
				<option value="32px">32</option>
				<option value="36px">36</option>
				<option value="48px">48</option>
			</select>
		</div>

		<!-- Formatting -->
		<div class="tool-group">
			<button id="styleBold" class="btn-tool" data-ap-tooltip="Negrito (Ctrl+B)">
				<span class="material-symbols-rounded">format_bold</span>
			</button>
			<button id="styleItalic" class="btn-tool" data-ap-tooltip="Itálico (Ctrl+I)">
				<span class="material-symbols-rounded">format_italic</span>
			</button>
			<button id="styleUnderline" class="btn-tool" data-ap-tooltip="Sublinhado (Ctrl+U)">
				<span class="material-symbols-rounded">format_underlined</span>
			</button>
			<input type="color" id="textColor" value="#000000"
					data-ap-tooltip="<?php echo esc_attr( $tooltips['text_color'] ); ?>">
		</div>

		<!-- Alignment -->
		<div class="tool-group">
			<button id="alignLeft" class="btn-tool alignment-btn active" data-ap-tooltip="Alinhar à esquerda">
				<span class="material-symbols-rounded">format_align_left</span>
			</button>
			<button id="alignCenter" class="btn-tool alignment-btn" data-ap-tooltip="Centralizar">
				<span class="material-symbols-rounded">format_align_center</span>
			</button>
			<button id="alignRight" class="btn-tool alignment-btn" data-ap-tooltip="Alinhar à direita">
				<span class="material-symbols-rounded">format_align_right</span>
			</button>
			<button id="alignJustify" class="btn-tool alignment-btn" data-ap-tooltip="Justificar">
				<span class="material-symbols-rounded">format_align_justify</span>
			</button>
		</div>

		<!-- Insert -->
		<div class="tool-group">
			<button id="insertHeading" class="btn-tool" data-ap-tooltip="Inserir título H2">
				<span class="material-symbols-rounded">title</span>
			</button>
			<button id="insertList" class="btn-tool" data-ap-tooltip="Inserir lista com marcadores">
				<span class="material-symbols-rounded">format_list_bulleted</span>
			</button>
			<button id="insertTable" class="btn-tool" data-ap-tooltip="Inserir tabela 3x3">
				<span class="material-symbols-rounded">table_chart</span>
			</button>
			<button id="insertImage" class="btn-tool" data-ap-tooltip="Inserir imagem (URL)">
				<span class="material-symbols-rounded">image</span>
			</button>
		</div>
	</div>
	<?php elseif ( $type === 'planilha' && $can_edit ) : ?>
	<!-- Spreadsheet Toolbar -->
	<div class="editor-toolbar">
		<div class="tool-group">
			<button class="btn-tool" id="addRow" data-ap-tooltip="Adicionar nova linha">
				<i class="ri-add-line"></i>
			</button>
			<button class="btn-tool" id="addCol" data-ap-tooltip="Adicionar nova coluna">
				<i class="ri-add-circle-line"></i>
			</button>
			<button class="btn-tool" id="deleteRow" data-ap-tooltip="Remover linha selecionada">
				<i class="ri-subtract-line"></i>
			</button>
		</div>
		<div class="tool-group">
			<button class="btn-tool" id="insertSum" data-ap-tooltip="Inserir fórmula SOMA">
				Σ
			</button>
			<button class="btn-tool" id="insertAvg" title="Inserir MÉDIA">
				μ
			</button>
			<button class="btn-tool" id="insertCount" title="Inserir CONTAR">
				#
			</button>
		</div>
	</div>
	<?php endif; ?>

	<!-- @section:workspace -->
	<div id="main" class="workspace">
		<?php if ( $type === 'documento' ) : ?>
		<div class="document-page" contenteditable="true" spellcheck="true" id="editor-content">
			<?php echo wp_kses_post( $doc_content ); ?>
		</div>
		<?php else : ?>
		<div class="document-page">
			<div class="spreadsheet-container">
				<table class="spreadsheet-grid" id="spreadsheet-grid">
					<thead>
						<tr>
							<th style="width: 40px;">#</th>
							<?php for ( $col = 0; $col < 10; $col++ ) : ?>
								<th><?php echo chr( 65 + $col ); ?></th>
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody id="spreadsheet-body">
						<?php for ( $row = 1; $row <= 20; $row++ ) : ?>
							<tr>
								<th><?php echo $row; ?></th>
								<?php for ( $col = 0; $col < 10; $col++ ) : ?>
									<td>
										<input type="text"
											class="spreadsheet-cell"
											data-row="<?php echo $row; ?>"
											data-col="<?php echo $col; ?>">
									</td>
								<?php endfor; ?>
							</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Hidden form data -->
	<input type="hidden" id="document-id" value="<?php echo esc_attr( $document_id ); ?>">
	<input type="hidden" id="document-file-id" value="<?php echo esc_attr( $file_id ); ?>">
	<input type="hidden" id="document-type" value="<?php echo esc_attr( $type ); ?>">
	<input type="hidden" id="ajax-nonce" value="<?php echo esc_attr( $ajax_nonce ); ?>">
	<input type="hidden" id="ajax-url" value="<?php echo esc_url( $ajax_url ); ?>">

	<?php
	// Include signature modal if document can be signed
	if ( ! $is_new && $can_sign ) :
		$document_title = $doc_title;
		include __DIR__ . '/partials/signature-modal.php';
	endif;
	?>

	<script>
	(function() {
		'use strict';

		// Configuration
		const config = {
			documentId: document.getElementById('document-id').value || null,
			fileId: document.getElementById('document-file-id').value || null,
			documentType: document.getElementById('document-type').value || 'documento',
			nonce: document.getElementById('ajax-nonce').value,
			ajaxUrl: document.getElementById('ajax-url').value || '/wp-admin/admin-ajax.php',
			autosaveInterval: 2000
		};

		// State
		let activeElement = null;
		let saveTimeout = null;
		let isDirty = false;
		let loadedFonts = new Set(['Urbanist', 'Poppins', 'Open Sans']);

		// Google Fonts loader
		function loadGoogleFont(font) {
			if (loadedFonts.has(font)) return;

			const fontQuery = font.replace(/ /g, '+');
			const url = `https://fonts.googleapis.com/css2?family=${fontQuery}:wght@300;400;500;600;700;800&display=swap`;

			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = url;
			link.className = 'dynamic-font';
			document.head.appendChild(link);
			loadedFonts.add(font);
		}

		// Save status UI
		function updateSaveStatus(status, text) {
			const statusEl = document.getElementById('save-status');
			const textEl = document.getElementById('save-text');
			const iconEl = statusEl.querySelector('.material-symbols-rounded');

			statusEl.classList.remove('saving', 'saved', 'error');

			switch(status) {
				case 'saving':
					statusEl.classList.add('saving');
					iconEl.textContent = 'sync';
					textEl.textContent = text || 'Salvando...';
					break;
				case 'saved':
					statusEl.classList.add('saved');
					iconEl.textContent = 'cloud_done';
					textEl.textContent = text || 'Salvo';
					break;
				case 'error':
					statusEl.classList.add('error');
					iconEl.textContent = 'error';
					textEl.textContent = text || 'Erro ao salvar';
					break;
			}
		}

		// Mark as dirty (needs save)
		function markDirty() {
			isDirty = true;
			clearTimeout(saveTimeout);
			saveTimeout = setTimeout(autoSave, config.autosaveInterval);
		}

		// Auto-save function
		function autoSave() {
			if (!isDirty) return;

			updateSaveStatus('saving');

			const formData = new FormData();
			formData.append('action', 'apollo_save_document');
			formData.append('nonce', config.nonce);
			formData.append('document_id', config.documentId || '');
			formData.append('document_type', config.documentType);
			formData.append('title', document.getElementById('document-title').textContent.trim());

			if (config.documentType === 'documento') {
				formData.append('content', document.getElementById('editor-content').innerHTML);
			} else {
				formData.append('content', getSpreadsheetData());
			}

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					isDirty = false;
					config.documentId = data.data.document_id;
					config.fileId = data.data.file_id;
					updateSaveStatus('saved');

					// Update URL if new document
					if (data.data.is_new && data.data.file_id) {
						const prefix = config.documentType === 'documento' ? 'doc' : 'pla';
						window.history.replaceState({}, '', '/' + prefix + '/' + data.data.file_id);
					}
				} else {
					updateSaveStatus('error', data.data?.message || 'Erro');
				}
			})
			.catch(() => {
				updateSaveStatus('error', 'Falha na conexão');
			});
		}

		// Export PDF
		function exportPDF() {
			updateSaveStatus('saving', 'Gerando PDF...');

			const formData = new FormData();
			formData.append('action', 'apollo_export_document_pdf');
			formData.append('nonce', config.nonce);
			formData.append('document_id', config.documentId || '');
			formData.append('title', document.getElementById('document-title').textContent.trim());

			if (config.documentType === 'documento') {
				formData.append('content', document.getElementById('editor-content').innerHTML);
			} else {
				formData.append('content', getSpreadsheetData());
			}

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data.pdf_url) {
					updateSaveStatus('saved', 'PDF gerado!');
					window.open(data.data.pdf_url, '_blank');
				} else {
					updateSaveStatus('error', data.data?.message || 'Erro ao gerar PDF');
				}
			})
			.catch(() => {
				updateSaveStatus('error', 'Falha ao gerar PDF');
			});
		}

		// Prepare for signing - Opens the signature modal
		function prepareForSigning() {
			if (!config.documentId) {
				alert('Salve o documento antes de assinar.');
				return;
			}

			// The modal is handled by sign-document.js
			// This function is now a fallback if the modal script isn't loaded
			if (window.ApolloSignatureModal) {
				window.ApolloSignatureModal.open({
					documentId: config.documentId,
					title: document.getElementById('document-title').textContent.trim(),
					restUrl: '/wp-json/apollo-social/v1/documents/' + config.documentId + '/sign'
				});
			} else {
				// Fallback to old behavior if modal not available
				console.warn('Signature modal not available, using legacy flow');
				updateSaveStatus('saving', 'Preparando...');

				const formData = new FormData();
				formData.append('action', 'apollo_prepare_document_signing');
				formData.append('nonce', config.nonce);
				formData.append('document_id', config.documentId);

				fetch(config.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})
				.then(response => response.json())
				.then(data => {
					if (data.success && data.data.sign_url) {
						updateSaveStatus('saved', 'Pronto!');
						window.location.href = data.data.sign_url;
					} else {
						updateSaveStatus('error', data.data?.message || 'Erro');
					}
				})
				.catch(() => {
					updateSaveStatus('error', 'Falha na conexão');
				});
			}
		}

		// Apply style to selection/element
		function applyStyle(property, value) {
			document.execCommand('styleWithCSS', false, true);

			const selection = window.getSelection();
			if (selection.rangeCount > 0 && !selection.isCollapsed) {
				const range = selection.getRangeAt(0);
				const span = document.createElement('span');
				span.style[property] = value;
				range.surroundContents(span);
			} else if (activeElement) {
				activeElement.style[property] = value;
			}

			markDirty();
		}

		// RGB to Hex
		function rgbToHex(rgb) {
			if (!rgb || rgb === 'rgba(0, 0, 0, 0)') return '#000000';
			const result = rgb.match(/\d+/g);
			if (!result) return '#000000';
			return '#' + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1).toUpperCase();
		}

		// Spreadsheet: Get data as JSON
		function getSpreadsheetData() {
			const cells = document.querySelectorAll('.spreadsheet-cell');
			const data = [];

			cells.forEach(cell => {
				if (cell.value) {
					data.push({
						row: cell.dataset.row,
						col: cell.dataset.col,
						value: cell.value
					});
				}
			});

			return JSON.stringify(data);
		}

		// Initialize
		document.addEventListener('DOMContentLoaded', function() {
			const editorContent = document.getElementById('editor-content');
			const titleEl = document.getElementById('document-title');

			// Document type specific setup
			if (config.documentType === 'documento' && editorContent) {
				// Select element on click
				editorContent.addEventListener('click', function(e) {
					const target = e.target.closest('h1, h2, h3, p, li, td, th, span, div');
					if (target && editorContent.contains(target)) {
						document.querySelectorAll('.document-page *').forEach(el => el.classList.remove('active-element'));
						target.classList.add('active-element');
						activeElement = target;

						// Update toolbar
						const fontFamily = getComputedStyle(target).fontFamily.split(',')[0].replace(/['"]/g, '');
						const fontSize = getComputedStyle(target).fontSize;
						const fontWeight = getComputedStyle(target).fontWeight;
						const color = rgbToHex(getComputedStyle(target).color);
						const textAlign = getComputedStyle(target).textAlign;

						const fontSel = document.getElementById('fontSelector');
						const sizeSel = document.getElementById('fontSize');
						const weightSel = document.getElementById('fontWeight');
						const colorPicker = document.getElementById('textColor');

						if (fontSel) fontSel.value = fontFamily;
						if (sizeSel) sizeSel.value = fontSize;
						if (weightSel) weightSel.value = fontWeight;
						if (colorPicker) colorPicker.value = color;

						document.querySelectorAll('.alignment-btn').forEach(btn => btn.classList.remove('active'));
						const alignBtn = document.getElementById('align' + textAlign.charAt(0).toUpperCase() + textAlign.slice(1));
						if (alignBtn) alignBtn.classList.add('active');
					}
				});

				// Input events
				editorContent.addEventListener('input', markDirty);

				// Font selector
				const fontSelector = document.getElementById('fontSelector');
				if (fontSelector) {
					fontSelector.addEventListener('change', function() {
						const font = this.value;
						loadGoogleFont(font);
						applyStyle('fontFamily', '"' + font + '", sans-serif');
					});
				}

				// Font weight
				const fontWeight = document.getElementById('fontWeight');
				if (fontWeight) {
					fontWeight.addEventListener('change', function() {
						applyStyle('fontWeight', this.value);
					});
				}

				// Font size
				const fontSize = document.getElementById('fontSize');
				if (fontSize) {
					fontSize.addEventListener('change', function() {
						applyStyle('fontSize', this.value);
					});
				}

				// Text color
				const textColor = document.getElementById('textColor');
				if (textColor) {
					textColor.addEventListener('input', function() {
						applyStyle('color', this.value);
					});
				}

				// Bold
				const styleBold = document.getElementById('styleBold');
				if (styleBold) {
					styleBold.addEventListener('click', function() {
						document.execCommand('bold', false, null);
						this.classList.toggle('active');
						markDirty();
					});
				}

				// Italic
				const styleItalic = document.getElementById('styleItalic');
				if (styleItalic) {
					styleItalic.addEventListener('click', function() {
						document.execCommand('italic', false, null);
						this.classList.toggle('active');
						markDirty();
					});
				}

				// Underline
				const styleUnderline = document.getElementById('styleUnderline');
				if (styleUnderline) {
					styleUnderline.addEventListener('click', function() {
						document.execCommand('underline', false, null);
						this.classList.toggle('active');
						markDirty();
					});
				}

				// Alignment
				['alignLeft', 'alignCenter', 'alignRight', 'alignJustify'].forEach(function(id) {
					const btn = document.getElementById(id);
					if (btn) {
						btn.addEventListener('click', function() {
							const align = id.replace('align', '').toLowerCase();
							document.execCommand('justify' + align.charAt(0).toUpperCase() + align.slice(1), false, null);
							document.querySelectorAll('.alignment-btn').forEach(b => b.classList.remove('active'));
							this.classList.add('active');
							markDirty();
						});
					}
				});

				// Insert heading
				const insertHeading = document.getElementById('insertHeading');
				if (insertHeading) {
					insertHeading.addEventListener('click', function() {
						document.execCommand('formatBlock', false, 'h2');
						markDirty();
					});
				}

				// Insert list
				const insertList = document.getElementById('insertList');
				if (insertList) {
					insertList.addEventListener('click', function() {
						document.execCommand('insertUnorderedList', false, null);
						markDirty();
					});
				}

				// Insert table
				const insertTable = document.getElementById('insertTable');
				if (insertTable) {
					insertTable.addEventListener('click', function() {
						const table = '<table style="width: 100%; border-collapse: collapse; margin: 16px 0;">' +
							'<tr>' +
							'<th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">Coluna 1</th>' +
							'<th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">Coluna 2</th>' +
							'<th style="border: 1px solid #ddd; padding: 8px; background: #f5f5f5;">Coluna 3</th>' +
							'</tr>' +
							'<tr>' +
							'<td style="border: 1px solid #ddd; padding: 8px;">Valor 1</td>' +
							'<td style="border: 1px solid #ddd; padding: 8px;">Valor 2</td>' +
							'<td style="border: 1px solid #ddd; padding: 8px;">Valor 3</td>' +
							'</tr>' +
							'</table>';
						document.execCommand('insertHTML', false, table);
						markDirty();
					});
				}

				// Insert image
				const insertImage = document.getElementById('insertImage');
				if (insertImage) {
					insertImage.addEventListener('click', function() {
						const url = prompt('URL da imagem:');
						if (url) {
							document.execCommand('insertImage', false, url);
							markDirty();
						}
					});
				}

			} else {
				// Spreadsheet mode
				const cells = document.querySelectorAll('.spreadsheet-cell');
				cells.forEach(function(cell) {
					cell.addEventListener('input', markDirty);
				});

				// Add row
				const addRowBtn = document.getElementById('addRow');
				if (addRowBtn) {
					addRowBtn.addEventListener('click', function() {
						const tbody = document.getElementById('spreadsheet-body');
						const rowCount = tbody.rows.length + 1;
						const newRow = tbody.insertRow();

						const th = document.createElement('th');
						th.textContent = rowCount;
						newRow.appendChild(th);

						for (let col = 0; col < 10; col++) {
							const td = document.createElement('td');
							const input = document.createElement('input');
							input.type = 'text';
							input.className = 'spreadsheet-cell';
							input.dataset.row = rowCount;
							input.dataset.col = col;
							input.addEventListener('input', markDirty);
							td.appendChild(input);
							newRow.appendChild(td);
						}

						markDirty();
					});
				}

				// Delete row
				const deleteRowBtn = document.getElementById('deleteRow');
				if (deleteRowBtn) {
					deleteRowBtn.addEventListener('click', function() {
						const tbody = document.getElementById('spreadsheet-body');
						if (tbody && tbody.rows.length > 1) {
							tbody.deleteRow(-1);
							markDirty();
						}
					});
				}
			}

			// Title input
			if (titleEl) {
				titleEl.addEventListener('input', markDirty);
			}

			// Save button
			const saveBtn = document.getElementById('btn-save');
			if (saveBtn) {
				saveBtn.addEventListener('click', function() {
					isDirty = true;
					autoSave();
				});
			}

			// Export PDF button
			const exportBtn = document.getElementById('btn-export-pdf');
			if (exportBtn) {
				exportBtn.addEventListener('click', exportPDF);
			}

			// Prepare for signing button
			const prepareBtn = document.getElementById('btn-prepare-sign');
			if (prepareBtn) {
				prepareBtn.addEventListener('click', prepareForSigning);
			}

			// Keyboard shortcuts
			document.addEventListener('keydown', function(e) {
				if (e.ctrlKey || e.metaKey) {
					switch(e.key.toLowerCase()) {
						case 's':
							e.preventDefault();
							isDirty = true;
							autoSave();
							break;
						case 'b':
							e.preventDefault();
							const boldBtn = document.getElementById('styleBold');
							if (boldBtn) boldBtn.click();
							break;
						case 'i':
							e.preventDefault();
							const italicBtn = document.getElementById('styleItalic');
							if (italicBtn) italicBtn.click();
							break;
						case 'u':
							e.preventDefault();
							const underlineBtn = document.getElementById('styleUnderline');
							if (underlineBtn) underlineBtn.click();
							break;
					}
				}
			});

			// Warn before leaving with unsaved changes
			window.addEventListener('beforeunload', function(e) {
				if (isDirty) {
					e.preventDefault();
					e.returnValue = '';
					return 'Você tem alterações não salvas. Deseja sair?';
				}
			});
		});
	})();
	</script>
</body>
</html>
