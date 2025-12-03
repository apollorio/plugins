<?php
/**
 * Document Editor Template
 * DESIGN LIBRARY: Matches approved HTML from 'social doc editor.md'
 * Word-like editor with toolbar for font, size, color, and alignment
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get document if editing existing
$document_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$is_new      = ! $document_id;

$document         = null;
$document_title   = __( 'Untitled Document', 'apollo-social' );
$document_content = '';
$document_status  = 'draft';

if ( $document_id ) {
	$document = get_post( $document_id );
	if ( $document && $document->post_type === 'apollo_document' ) {
		$document_title   = $document->post_title;
		$document_content = $document->post_content;
		$document_status  = get_post_meta( $document_id, '_document_status', true ) ?: 'draft';

		// Check permissions
		if ( ! current_user_can( 'edit_post', $document_id ) ) {
			wp_die( __( 'Você não tem permissão para editar este documento.', 'apollo-social' ) );
		}
	} else {
		$document_id = 0;
		$is_new      = true;
	}
}

// Current user
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
	<title><?php echo esc_html( $document_title ); ?> - DOC::rio</title>
	
	<!-- Apollo Design System -->
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
	
	<!-- Remixicon -->
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
	
	<!-- Google Fonts - Initial -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	
	<style>
		:root {
			--apollo-primary: #3498db;
			--apollo-bg: #f0f2f5;
			--paper-width: 210mm;
			--paper-height: 297mm;
			--toolbar-height: 70px;
		}
		
		body.apollo-canvas {
			font-family: 'Inter', sans-serif;
			background-color: var(--apollo-bg);
			color: #1a1a1a;
			margin: 0;
			height: 100vh;
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}
		
		/* Top Navigation */
		.apollo-navbar {
			height: 50px;
			background: #fff;
			border-bottom: 1px solid #e0e0e0;
			display: flex;
			align-items: center;
			padding: 0 20px;
			flex-shrink: 0;
		}
		
		.brand {
			display: flex;
			align-items: center;
			font-weight: 600;
			color: #2c3e50;
			font-size: 16px;
		}
		
		.ripple-dot {
			width: 10px;
			height: 10px;
			background-color: var(--apollo-primary);
			border-radius: 50%;
			position: relative;
			margin-right: 15px;
			animation: rippleShadow 2s infinite ease-out;
		}
		
		@keyframes rippleShadow {
			0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.6); }
			100% { box-shadow: 0 0 0 12px rgba(52, 152, 219, 0); }
		}
		
		.file-name {
			margin-left: 10px;
			color: #666;
			font-size: 14px;
			border: 1px solid transparent;
			padding: 2px 8px;
			border-radius: 4px;
			min-width: 150px;
			outline: none;
		}
		.file-name:hover, .file-name:focus {
			border-color: #e0e0e0;
			background: #f9f9f9;
		}
		
		.navbar-actions {
			margin-left: auto;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		
		.save-indicator {
			font-size: 12px;
			color: #888;
			display: flex;
			align-items: center;
			gap: 4px;
		}
		.save-indicator.saving { color: var(--apollo-primary); }
		.save-indicator.saved { color: #27ae60; }
		
		/* Editor Toolbar */
		.editor-toolbar {
			background: #fff;
			padding: 10px 20px;
			border-bottom: 1px solid #e0e0e0;
			display: flex;
			gap: 12px;
			align-items: center;
			flex-wrap: nowrap;
			overflow-x: auto;
			box-shadow: 0 2px 6px rgba(0,0,0,0.02);
			flex-shrink: 0;
			z-index: 10;
		}
		
		.tool-group {
			display: flex;
			align-items: center;
			gap: 6px;
			padding-right: 12px;
			border-right: 1px solid #eee;
		}
		.tool-group:last-child { border-right: none; }
		
		.tool-select {
			border-radius: 4px;
			border: 1px solid #ddd;
			font-size: 13px;
			padding: 4px 8px;
			cursor: pointer;
			background: #fff;
		}
		
		#fontSelector { width: 160px; }
		#fontWeight { width: 100px; }
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
			border-radius: 4px;
			color: #555;
			transition: all 0.2s;
			cursor: pointer;
		}
		.btn-tool:hover {
			background: #f5f5f5;
			border-color: #ddd;
		}
		.btn-tool.active {
			background: #e3f2fd;
			color: var(--apollo-primary);
			border-color: #bbdefb;
		}
		.btn-tool i { font-size: 18px; }
		
		#textColor {
			width: 32px;
			height: 32px;
			padding: 2px;
			border: none;
			background: none;
			cursor: pointer;
		}
		
		/* Workspace */
		.workspace {
			flex: 1;
			overflow-y: auto;
			padding: 40px;
			display: flex;
			justify-content: center;
			position: relative;
		}
		
		.document-page {
			background: white;
			width: var(--paper-width);
			min-height: var(--paper-height);
			padding: 25mm;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
			margin-bottom: 40px;
			outline: none;
		}
		
		.document-page h1 {
			font-family: 'Poppins', sans-serif;
			font-size: 32px;
			font-weight: 600;
			color: #000;
			margin: 0 0 16px 0;
		}
		
		.document-page h2 {
			font-family: 'Poppins', sans-serif;
			font-size: 24px;
			font-weight: 600;
			color: #333;
			margin: 24px 0 12px 0;
		}
		
		.document-page p {
			font-family: 'Inter', sans-serif;
			font-size: 14px;
			color: #444;
			line-height: 1.7;
			margin: 0 0 12px 0;
		}
		
		.active-element {
			position: relative;
		}
		.active-element::before {
			content: '';
			position: absolute;
			left: -15px;
			top: 0;
			bottom: 0;
			width: 3px;
			background-color: var(--apollo-primary);
			opacity: 0.5;
		}
		
		/* Mobile Responsive */
		@media (max-width: 900px) {
			.workspace { padding: 16px; }
			.document-page {
				width: 100%;
				padding: 20px;
				min-height: auto;
			}
			.editor-toolbar { padding: 8px 12px; }
			.tool-group { padding-right: 8px; }
			#fontSelector { width: 120px; }
		}
		
		@media (max-width: 600px) {
			.navbar-actions .btn { font-size: 12px; padding: 4px 8px; }
			.file-name { font-size: 12px; min-width: 100px; }
		}
		
		/* Scrollbar */
		::-webkit-scrollbar { width: 8px; height: 8px; }
		::-webkit-scrollbar-track { background: #f1f1f1; }
		::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
		::-webkit-scrollbar-thumb:hover { background: #bbb; }
	</style>
	
	<?php wp_head(); ?>
</head>
<body class="apollo-canvas">

<!-- Top Navigation -->
<div class="apollo-navbar" data-tooltip="<?php esc_attr_e( 'Barra de navegação do editor', 'apollo-social' ); ?>">
	<div class="brand">
		<div class="ripple-dot" data-tooltip="<?php esc_attr_e( 'Status: conectado', 'apollo-social' ); ?>"></div>
		<span style="font-family:system-ui;font-weight:700;font-size:1.5rem;" data-tooltip="<?php esc_attr_e( 'Editor de documentos Apollo', 'apollo-social' ); ?>">DOC::rio</span>
		<span class="mx-3" style="font-family:system-ui;font-weight:200;font-size:1.5rem;">/</span>
		<span 
			class="file-name" 
			contenteditable="true" 
			id="documentTitle"
			data-tooltip="<?php esc_attr_e( 'Clique para editar o nome do documento', 'apollo-social' ); ?>"
		><?php echo esc_html( $document_title ); ?></span>
	</div>
	
	<div class="navbar-actions">
		<span class="save-indicator" id="saveIndicator" data-tooltip="<?php esc_attr_e( 'Status de salvamento', 'apollo-social' ); ?>">
			<i class="ri-cloud-line"></i>
			<span><?php esc_html_e( 'Salvo', 'apollo-social' ); ?></span>
		</span>
		
		<button class="btn btn-secondary" id="btnPreview" data-tooltip="<?php esc_attr_e( 'Pré-visualizar documento', 'apollo-social' ); ?>">
			<i class="ri-eye-line"></i>
			<span class="hidden md:inline"><?php esc_html_e( 'Preview', 'apollo-social' ); ?></span>
		</button>
		
		<button class="btn btn-primary" id="btnSave" data-tooltip="<?php esc_attr_e( 'Salvar documento', 'apollo-social' ); ?>">
			<i class="ri-save-line"></i>
			<span class="hidden md:inline"><?php esc_html_e( 'Salvar', 'apollo-social' ); ?></span>
		</button>
		
		<?php if ( $document_status === 'draft' ) : ?>
		<button class="btn btn-success" id="btnPublish" data-tooltip="<?php esc_attr_e( 'Publicar e enviar para assinatura', 'apollo-social' ); ?>">
			<i class="ri-send-plane-line"></i>
			<span class="hidden md:inline"><?php esc_html_e( 'Publicar', 'apollo-social' ); ?></span>
		</button>
		<?php endif; ?>
	</div>
</div>

<!-- Editor Toolbar -->
<div class="editor-toolbar" data-tooltip="<?php esc_attr_e( 'Barra de ferramentas de formatação', 'apollo-social' ); ?>">
	<!-- Font Family -->
	<div class="tool-group">
		<select id="fontSelector" class="tool-select" title="<?php esc_attr_e( 'Família da fonte', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Selecionar fonte', 'apollo-social' ); ?>">
			<option value="Poppins">Poppins</option>
			<option value="Inter">Inter</option>
			<option value="Roboto">Roboto</option>
			<option value="Open Sans">Open Sans</option>
			<option value="Montserrat">Montserrat</option>
			<option value="Lato">Lato</option>
			<option value="Playfair Display">Playfair Display</option>
			<option value="Merriweather">Merriweather</option>
			<option value="Ubuntu">Ubuntu</option>
			<option value="Nunito">Nunito</option>
			<option value="Work Sans">Work Sans</option>
			<option value="DM Sans">DM Sans</option>
			<option value="Quicksand">Quicksand</option>
			<option value="Manrope">Manrope</option>
		</select>
	</div>

	<!-- Weight & Size -->
	<div class="tool-group">
		<select id="fontWeight" class="tool-select" title="<?php esc_attr_e( 'Peso da fonte', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Selecionar peso', 'apollo-social' ); ?>">
			<option value="300"><?php esc_html_e( 'Light', 'apollo-social' ); ?></option>
			<option value="400"><?php esc_html_e( 'Regular', 'apollo-social' ); ?></option>
			<option value="500"><?php esc_html_e( 'Medium', 'apollo-social' ); ?></option>
			<option value="600"><?php esc_html_e( 'Semi Bold', 'apollo-social' ); ?></option>
			<option value="700"><?php esc_html_e( 'Bold', 'apollo-social' ); ?></option>
		</select>

		<select id="fontSize" class="tool-select" title="<?php esc_attr_e( 'Tamanho da fonte', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Selecionar tamanho', 'apollo-social' ); ?>">
			<option value="10px">10</option>
			<option value="12px">12</option>
			<option value="14px">14</option>
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
		<button id="styleBold" class="btn-tool" title="<?php esc_attr_e( 'Negrito', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Aplicar negrito (Ctrl+B)', 'apollo-social' ); ?>">
			<i class="ri-bold"></i>
		</button>
		<button id="styleItalic" class="btn-tool" title="<?php esc_attr_e( 'Itálico', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Aplicar itálico (Ctrl+I)', 'apollo-social' ); ?>">
			<i class="ri-italic"></i>
		</button>
		<button id="styleUnderline" class="btn-tool" title="<?php esc_attr_e( 'Sublinhado', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Aplicar sublinhado (Ctrl+U)', 'apollo-social' ); ?>">
			<i class="ri-underline"></i>
		</button>
		<input type="color" id="textColor" value="#000000" title="<?php esc_attr_e( 'Cor do texto', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Selecionar cor do texto', 'apollo-social' ); ?>">
	</div>

	<!-- Alignment -->
	<div class="tool-group">
		<button id="alignLeft" class="btn-tool alignment-btn" title="<?php esc_attr_e( 'Alinhar à esquerda', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Alinhar texto à esquerda', 'apollo-social' ); ?>">
			<i class="ri-align-left"></i>
		</button>
		<button id="alignCenter" class="btn-tool alignment-btn" title="<?php esc_attr_e( 'Centralizar', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Centralizar texto', 'apollo-social' ); ?>">
			<i class="ri-align-center"></i>
		</button>
		<button id="alignRight" class="btn-tool alignment-btn" title="<?php esc_attr_e( 'Alinhar à direita', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Alinhar texto à direita', 'apollo-social' ); ?>">
			<i class="ri-align-right"></i>
		</button>
		<button id="alignJustify" class="btn-tool alignment-btn" title="<?php esc_attr_e( 'Justificar', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Justificar texto', 'apollo-social' ); ?>">
			<i class="ri-align-justify"></i>
		</button>
	</div>

	<!-- Insert -->
	<div class="tool-group">
		<button id="insertHeading" class="btn-tool" title="<?php esc_attr_e( 'Inserir título', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Inserir título H2', 'apollo-social' ); ?>">
			<i class="ri-heading"></i>
		</button>
		<button id="insertList" class="btn-tool" title="<?php esc_attr_e( 'Inserir lista', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Inserir lista com marcadores', 'apollo-social' ); ?>">
			<i class="ri-list-unordered"></i>
		</button>
		<button id="insertImage" class="btn-tool" title="<?php esc_attr_e( 'Inserir imagem', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Inserir imagem no documento', 'apollo-social' ); ?>">
			<i class="ri-image-add-line"></i>
		</button>
	</div>
</div>

<!-- Main Workspace -->
<div class="workspace" data-tooltip="<?php esc_attr_e( 'Área de edição do documento', 'apollo-social' ); ?>">
	<div 
		class="document-page" 
		contenteditable="true" 
		spellcheck="true" 
		id="documentContent"
		data-tooltip="<?php esc_attr_e( 'Clique para editar o conteúdo', 'apollo-social' ); ?>"
	>
		<?php if ( $document_content ) : ?>
			<?php echo wp_kses_post( $document_content ); ?>
		<?php else : ?>
			<h1 data-tooltip="<?php esc_attr_e( 'Título principal do documento', 'apollo-social' ); ?>"><?php esc_html_e( 'Título do Documento', 'apollo-social' ); ?></h1>
			<p data-tooltip="<?php esc_attr_e( 'Parágrafo de introdução', 'apollo-social' ); ?>"><?php esc_html_e( 'Comece a escrever seu documento aqui. Use a barra de ferramentas acima para formatar o texto.', 'apollo-social' ); ?></p>
		<?php endif; ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const documentPage = document.getElementById('documentContent');
	const documentTitle = document.getElementById('documentTitle');
	const saveIndicator = document.getElementById('saveIndicator');
	const documentId = <?php echo $document_id ?: 'null'; ?>;
	
	let activeElement = null;
	let saveTimeout = null;
	let loadedFonts = new Set(['Poppins', 'Inter']);
	
	// Helper to update save indicator
	function setSaveStatus(status) {
		saveIndicator.className = 'save-indicator ' + status;
		const icon = saveIndicator.querySelector('i');
		const text = saveIndicator.querySelector('span');
		
		if (status === 'saving') {
			icon.className = 'ri-loader-4-line';
			text.textContent = '<?php echo esc_js( __( 'Salvando...', 'apollo-social' ) ); ?>';
		} else if (status === 'saved') {
			icon.className = 'ri-check-line';
			text.textContent = '<?php echo esc_js( __( 'Salvo', 'apollo-social' ) ); ?>';
		} else {
			icon.className = 'ri-cloud-line';
			text.textContent = '<?php echo esc_js( __( 'Salvar', 'apollo-social' ) ); ?>';
		}
	}
	
	// Auto-save functionality
	function autoSave() {
		if (saveTimeout) clearTimeout(saveTimeout);
		saveTimeout = setTimeout(saveDocument, 2000);
	}
	
	// Save document
	async function saveDocument(publish = false) {
		setSaveStatus('saving');
		
		const data = {
			title: documentTitle.textContent.trim(),
			content: documentPage.innerHTML,
			status: publish ? 'pending' : 'draft',
			document_id: documentId
		};
		
		try {
			const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/documents/save' ) ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
				},
				body: JSON.stringify(data)
			});
			
			const result = await response.json();
			
			if (result.success) {
				setSaveStatus('saved');
				if (!documentId && result.document_id) {
					// Update URL with new document ID
					const newUrl = new URL(window.location);
					newUrl.searchParams.set('id', result.document_id);
					history.replaceState(null, '', newUrl);
				}
				if (publish && result.redirect_url) {
					window.location.href = result.redirect_url;
				}
			} else {
				alert(result.message || '<?php echo esc_js( __( 'Erro ao salvar', 'apollo-social' ) ); ?>');
				setSaveStatus('');
			}
		} catch (err) {
			console.error(err);
			alert('<?php echo esc_js( __( 'Erro de conexão', 'apollo-social' ) ); ?>');
			setSaveStatus('');
		}
	}
	
	// Event listeners for content changes
	documentPage.addEventListener('input', autoSave);
	documentTitle.addEventListener('input', autoSave);
	
	// Button actions
	document.getElementById('btnSave')?.addEventListener('click', () => saveDocument(false));
	document.getElementById('btnPublish')?.addEventListener('click', () => {
		if (confirm('<?php echo esc_js( __( 'Publicar documento e enviar para assinatura?', 'apollo-social' ) ); ?>')) {
			saveDocument(true);
		}
	});
	
	// Selection tracking
	documentPage.addEventListener('click', function(e) {
		const target = e.target;
		if (target === documentPage) return;
		
		// Remove active from all
		documentPage.querySelectorAll('.active-element').forEach(el => el.classList.remove('active-element'));
		
		// Add active to clicked element
		target.classList.add('active-element');
		activeElement = target;
		
		// Update toolbar to reflect current element styles
		updateToolbarFromElement(target);
	});
	
	function updateToolbarFromElement(el) {
		const style = window.getComputedStyle(el);
		
		// Font family
		const fontFamily = style.fontFamily.split(',')[0].replace(/['"]/g, '').trim();
		document.getElementById('fontSelector').value = fontFamily;
		
		// Font size
		document.getElementById('fontSize').value = style.fontSize;
		
		// Font weight
		document.getElementById('fontWeight').value = style.fontWeight;
		
		// Text color
		const rgb = style.color;
		const hex = rgbToHex(rgb);
		document.getElementById('textColor').value = hex;
		
		// Italic
		document.getElementById('styleItalic').classList.toggle('active', style.fontStyle === 'italic');
		
		// Bold
		document.getElementById('styleBold').classList.toggle('active', parseInt(style.fontWeight) >= 600);
		
		// Underline
		document.getElementById('styleUnderline').classList.toggle('active', style.textDecoration.includes('underline'));
		
		// Alignment
		const align = style.textAlign;
		document.querySelectorAll('.alignment-btn').forEach(btn => btn.classList.remove('active'));
		const alignBtn = document.getElementById('align' + capitalize(align));
		if (alignBtn) alignBtn.classList.add('active');
	}
	
	function rgbToHex(rgb) {
		if (!rgb || rgb === 'rgba(0, 0, 0, 0)') return '#000000';
		const result = rgb.match(/\d+/g);
		if (!result) return '#000000';
		return '#' + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1);
	}
	
	function capitalize(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	}
	
	// Apply style to active element or selection
	function applyStyle(property, value) {
		if (activeElement) {
			activeElement.style[property] = value;
			autoSave();
		} else {
			// Apply to selection using execCommand as fallback
			document.execCommand('styleWithCSS', false, true);
		}
	}
	
	// Font selector
	document.getElementById('fontSelector').addEventListener('change', function() {
		const font = this.value;
		
		// Load font if not already loaded
		if (!loadedFonts.has(font)) {
			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = `https://fonts.googleapis.com/css2?family=${font.replace(/ /g, '+')}:wght@300;400;500;600;700&display=swap`;
			document.head.appendChild(link);
			loadedFonts.add(font);
		}
		
		applyStyle('fontFamily', `"${font}", sans-serif`);
	});
	
	// Font weight
	document.getElementById('fontWeight').addEventListener('change', function() {
		applyStyle('fontWeight', this.value);
	});
	
	// Font size
	document.getElementById('fontSize').addEventListener('change', function() {
		applyStyle('fontSize', this.value);
	});
	
	// Text color
	document.getElementById('textColor').addEventListener('input', function() {
		applyStyle('color', this.value);
	});
	
	// Bold
	document.getElementById('styleBold').addEventListener('click', function() {
		document.execCommand('bold', false, null);
		this.classList.toggle('active');
		autoSave();
	});
	
	// Italic
	document.getElementById('styleItalic').addEventListener('click', function() {
		document.execCommand('italic', false, null);
		this.classList.toggle('active');
		autoSave();
	});
	
	// Underline
	document.getElementById('styleUnderline').addEventListener('click', function() {
		document.execCommand('underline', false, null);
		this.classList.toggle('active');
		autoSave();
	});
	
	// Alignment buttons
	['Left', 'Center', 'Right', 'Justify'].forEach(align => {
		document.getElementById('align' + align)?.addEventListener('click', function() {
			document.execCommand('justify' + align, false, null);
			document.querySelectorAll('.alignment-btn').forEach(btn => btn.classList.remove('active'));
			this.classList.add('active');
			autoSave();
		});
	});
	
	// Insert heading
	document.getElementById('insertHeading')?.addEventListener('click', function() {
		document.execCommand('formatBlock', false, 'h2');
		autoSave();
	});
	
	// Insert list
	document.getElementById('insertList')?.addEventListener('click', function() {
		document.execCommand('insertUnorderedList', false, null);
		autoSave();
	});
	
	// Insert image (placeholder - needs media library integration)
	document.getElementById('insertImage')?.addEventListener('click', function() {
		const url = prompt('<?php echo esc_js( __( 'URL da imagem:', 'apollo-social' ) ); ?>');
		if (url) {
			document.execCommand('insertImage', false, url);
			autoSave();
		}
	});
	
	// Keyboard shortcuts
	documentPage.addEventListener('keydown', function(e) {
		if (e.ctrlKey || e.metaKey) {
			switch (e.key.toLowerCase()) {
				case 's':
					e.preventDefault();
					saveDocument(false);
					break;
				case 'b':
					e.preventDefault();
					document.getElementById('styleBold').click();
					break;
				case 'i':
					e.preventDefault();
					document.getElementById('styleItalic').click();
					break;
				case 'u':
					e.preventDefault();
					document.getElementById('styleUnderline').click();
					break;
			}
		}
	});
	
	// Preview button
	document.getElementById('btnPreview')?.addEventListener('click', function() {
		const previewWindow = window.open('', '_blank');
		previewWindow.document.write(`
			<!DOCTYPE html>
			<html>
			<head>
				<title>${documentTitle.textContent}</title>
				<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
				<style>body { font-family: Inter, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }</style>
			</head>
			<body>${documentPage.innerHTML}</body>
			</html>
		`);
		previewWindow.document.close();
	});
});
</script>

<?php wp_footer(); ?>
</body>
</html>

