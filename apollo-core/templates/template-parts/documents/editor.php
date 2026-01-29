<?php

declare(strict_types=1);
/**
 * Apollo Document Editor Template
 *
 * Rich text document editor with save/export functionality
 * Based on: editor-text-doc.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Get document data if editing
$doc_id = isset($_GET['doc_id']) ? absint($_GET['doc_id']) : 0;
$document = $doc_id ? get_post($doc_id) : null;

// Document meta
$doc_title     = $document ? $document->post_title : '';
$doc_content   = $document ? $document->post_content : '';
$doc_status    = $document ? $document->post_status : 'draft';
$doc_type      = $document ? get_post_meta($doc_id, '_document_type', true) : 'general';
$is_template   = $document ? get_post_meta($doc_id, '_is_template', true) : false;
$last_modified = $document ? get_post_modified_time('d/m/Y H:i', false, $document) : '';

// Current user
$current_user = wp_get_current_user();

// Document types
$doc_types = array(
	'general'   => 'Documento Geral',
	'contract'  => 'Contrato',
	'rider'     => 'Rider Técnico',
	'invoice'   => 'Nota/Fatura',
	'proposal'  => 'Proposta',
	'checklist' => 'Checklist',
	'terms'     => 'Termos e Condições',
);

// Get templates
$templates = get_posts(array(
	'post_type'      => 'apollo_document',
	'posts_per_page' => 20,
	'meta_key'       => '_is_template',
	'meta_value'     => '1',
	'post_status'    => 'publish',
));

?>
<div class="apollo-doc-editor" data-doc-id="<?php echo esc_attr($doc_id); ?>">

	<!-- Toolbar -->
	<header class="editor-toolbar">
		<div class="toolbar-left">
			<a href="<?php echo esc_url(home_url('/minha-conta/documentos')); ?>" class="back-btn">
				<i class="i-arrow-left-v" aria-hidden="true"></i>
			</a>
			<div class="doc-title-wrap">
				<input
					type="text"
					class="doc-title-input"
					value="<?php echo esc_attr($doc_title); ?>"
					placeholder="Título do documento..."
					id="doc-title">
				<span class="save-status" id="save-status">
					<?php if ($last_modified) : ?>
						<i class="i-check-v" aria-hidden="true"></i>
						Salvo às <?php echo $last_modified; ?>
					<?php else : ?>
						Novo documento
					<?php endif; ?>
				</span>
			</div>
		</div>

		<div class="toolbar-right">
			<div class="doc-type-select">
				<select id="doc-type">
					<?php foreach ($doc_types as $key => $label) : ?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($doc_type, $key); ?>>
							<?php echo esc_html($label); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="toolbar-divider"></div>

			<button type="button" class="toolbar-btn" id="btn-preview" title="Visualizar">
				<i class="i-eye-v" aria-hidden="true"></i>
			</button>

			<button type="button" class="toolbar-btn" id="btn-export" title="Exportar">
				<i class="i-download-v" aria-hidden="true"></i>
			</button>

			<button type="button" class="toolbar-btn" id="btn-share" title="Compartilhar">
				<i class="i-share-v" aria-hidden="true"></i>
			</button>

			<div class="toolbar-divider"></div>

			<button type="button" class="save-btn" id="btn-save">
				<i class="i-save-v" aria-hidden="true"></i>
				Salvar
			</button>
		</div>
	</header>

	<!-- Formatting Toolbar -->
	<div class="format-toolbar">
		<div class="format-group">
			<select class="format-select" id="heading-select">
				<option value="p">Parágrafo</option>
				<option value="h1">Título 1</option>
				<option value="h2">Título 2</option>
				<option value="h3">Título 3</option>
				<option value="h4">Título 4</option>
			</select>
		</div>

		<div class="format-divider"></div>

		<div class="format-group">
			<button type="button" class="format-btn" data-command="bold" title="Negrito (Ctrl+B)">
				<i class="i-bold-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="italic" title="Itálico (Ctrl+I)">
				<i class="i-italic-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="underline" title="Sublinhado (Ctrl+U)">
				<i class="i-underline-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="strikeThrough" title="Riscado">
				<i class="i-strikethrough-v" aria-hidden="true"></i>
			</button>
		</div>

		<div class="format-divider"></div>

		<div class="format-group">
			<button type="button" class="format-btn" data-command="justifyLeft" title="Alinhar à esquerda">
				<i class="i-align-left-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="justifyCenter" title="Centralizar">
				<i class="i-align-center-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="justifyRight" title="Alinhar à direita">
				<i class="i-align-right-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="justifyFull" title="Justificar">
				<i class="i-align-justify-v" aria-hidden="true"></i>
			</button>
		</div>

		<div class="format-divider"></div>

		<div class="format-group">
			<button type="button" class="format-btn" data-command="insertUnorderedList" title="Lista com marcadores">
				<i class="i-list-unordered-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="insertOrderedList" title="Lista numerada">
				<i class="i-list-ordered-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="indent" title="Aumentar recuo">
				<i class="i-indent-increase-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="outdent" title="Diminuir recuo">
				<i class="i-indent-decrease-v" aria-hidden="true"></i>
			</button>
		</div>

		<div class="format-divider"></div>

		<div class="format-group">
			<button type="button" class="format-btn" id="btn-link" title="Inserir link">
				<i class="i-link-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" id="btn-image" title="Inserir imagem">
				<i class="i-image-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" id="btn-table" title="Inserir tabela">
				<i class="i-table-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="insertHorizontalRule" title="Linha horizontal">
				<i class="i-separator-v" aria-hidden="true"></i>
			</button>
		</div>

		<div class="format-divider"></div>

		<div class="format-group">
			<button type="button" class="format-btn" data-command="undo" title="Desfazer (Ctrl+Z)">
				<i class="i-arrow-go-back-v" aria-hidden="true"></i>
			</button>
			<button type="button" class="format-btn" data-command="redo" title="Refazer (Ctrl+Y)">
				<i class="i-arrow-go-forward-v" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<!-- Editor Container -->
	<div class="editor-container">
		<!-- Sidebar -->
		<aside class="editor-sidebar" id="editor-sidebar">

			<!-- Templates -->
			<div class="sidebar-section">
				<h3>
					<i class="i-file-copy-v" aria-hidden="true"></i>
					Templates
				</h3>
				<ul class="template-list">
					<?php if (! empty($templates)) : ?>
						<?php foreach ($templates as $tmpl) : ?>
							<li>
								<button type="button" class="template-btn" data-template-id="<?php echo $tmpl->ID; ?>">
									<i class="i-file-text-v" aria-hidden="true"></i>
									<?php echo esc_html($tmpl->post_title); ?>
								</button>
							</li>
						<?php endforeach; ?>
					<?php else : ?>
						<li class="empty">Nenhum template disponível</li>
					<?php endif; ?>
				</ul>
			</div>

			<!-- Variables -->
			<div class="sidebar-section">
				<h3>
					<i class="i-code-v" aria-hidden="true"></i>
					Variáveis
				</h3>
				<p class="sidebar-hint">Clique para inserir</p>
				<ul class="variable-list">
					<li>
						<button type="button" class="var-btn" data-var="{{nome_usuario}}">
							Nome do Usuário
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{data_atual}}">
							Data Atual
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{nome_evento}}">
							Nome do Evento
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{data_evento}}">
							Data do Evento
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{local_evento}}">
							Local do Evento
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{valor}}">
							Valor
						</button>
					</li>
					<li>
						<button type="button" class="var-btn" data-var="{{assinatura}}">
							Assinatura
						</button>
					</li>
				</ul>
			</div>

			<!-- Document Info -->
			<div class="sidebar-section">
				<h3>
					<i class="i-information-v" aria-hidden="true"></i>
					Informações
				</h3>
				<ul class="info-list">
					<li>
						<span class="label">Autor:</span>
						<span class="value"><?php echo esc_html($current_user->display_name); ?></span>
					</li>
					<li>
						<span class="label">Status:</span>
						<span class="value status-<?php echo esc_attr($doc_status); ?>">
							<?php echo $doc_status === 'publish' ? 'Publicado' : 'Rascunho'; ?>
						</span>
					</li>
					<li>
						<span class="label">Palavras:</span>
						<span class="value" id="word-count">0</span>
					</li>
					<li>
						<span class="label">Caracteres:</span>
						<span class="value" id="char-count">0</span>
					</li>
				</ul>
			</div>

			<!-- Save as Template -->
			<div class="sidebar-section">
				<label class="template-checkbox">
					<input type="checkbox" id="save-as-template" <?php checked($is_template); ?>>
					<span>Salvar como template</span>
				</label>
			</div>

		</aside>

		<!-- Main Editor -->
		<main class="editor-main">
			<div
				class="editor-content"
				id="editor-content"
				contenteditable="true"
				data-placeholder="Comece a escrever seu documento..."><?php echo wp_kses_post($doc_content); ?></div>
		</main>
	</div>

	<!-- Toggle Sidebar Button (Mobile) -->
	<button type="button" class="toggle-sidebar" id="toggle-sidebar">
		<i class="i-menu-v" aria-hidden="true"></i>
	</button>

</div>

<!-- Link Modal -->
<div id="link-modal" class="editor-modal" aria-hidden="true">
	<div class="modal-overlay"></div>
	<div class="modal-content">
		<h3>Inserir Link</h3>
		<div class="form-group">
			<label for="link-url">URL</label>
			<input type="url" id="link-url" placeholder="https://...">
		</div>
		<div class="form-group">
			<label for="link-text">Texto do Link</label>
			<input type="text" id="link-text" placeholder="Clique aqui">
		</div>
		<div class="modal-actions">
			<button type="button" class="modal-cancel">Cancelar</button>
			<button type="button" class="modal-confirm" id="insert-link">Inserir</button>
		</div>
	</div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="editor-modal" aria-hidden="true">
	<div class="modal-overlay"></div>
	<div class="modal-content">
		<h3>Exportar Documento</h3>
		<div class="export-options">
			<button type="button" class="export-option" data-format="pdf">
				<i class="i-file-pdf-v" aria-hidden="true"></i>
				<span>PDF</span>
			</button>
			<button type="button" class="export-option" data-format="docx">
				<i class="i-file-word-v" aria-hidden="true"></i>
				<span>Word</span>
			</button>
			<button type="button" class="export-option" data-format="html">
				<i class="i-file-code-v" aria-hidden="true"></i>
				<span>HTML</span>
			</button>
			<button type="button" class="export-option" data-format="txt">
				<i class="i-file-text-v" aria-hidden="true"></i>
				<span>Texto</span>
			</button>
		</div>
		<div class="modal-actions">
			<button type="button" class="modal-cancel">Cancelar</button>
		</div>
	</div>
</div>

<style>
	/* Apollo Document Editor Styles */
	.apollo-doc-editor {
		width: 100%;
		min-height: 100vh;
		display: flex;
		flex-direction: column;
		background: var(--ap-bg-page);
	}

	/* Toolbar */
	.editor-toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0.75rem 1rem;
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		position: sticky;
		top: 0;
		z-index: 100;
	}

	.toolbar-left {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		flex: 1;
		min-width: 0;
	}

	.back-btn {
		width: 40px;
		height: 40px;
		border-radius: 0.5rem;
		display: flex;
		align-items: center;
		justify-content: center;
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
		transition: background 0.2s;
		flex-shrink: 0;
	}

	.back-btn:hover {
		background: var(--ap-bg-page);
	}

	.doc-title-wrap {
		flex: 1;
		min-width: 0;
	}

	.doc-title-input {
		width: 100%;
		border: none;
		outline: none;
		font-size: 1.1rem;
		font-weight: 700;
		background: transparent;
		padding: 0.25rem 0;
	}

	.doc-title-input:focus {
		border-bottom: 2px solid #f97316;
	}

	.save-status {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.save-status i {
		color: #10b981;
	}

	.toolbar-right {
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}

	.doc-type-select select {
		padding: 0.5rem 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.8rem;
		background: #fff;
		cursor: pointer;
	}

	.toolbar-divider {
		width: 1px;
		height: 24px;
		background: var(--ap-border-default);
		margin: 0 0.25rem;
	}

	.toolbar-btn {
		width: 36px;
		height: 36px;
		border-radius: 0.5rem;
		display: flex;
		align-items: center;
		justify-content: center;
		background: transparent;
		border: none;
		cursor: pointer;
		color: var(--ap-text-muted);
		transition: all 0.2s;
	}

	.toolbar-btn:hover {
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
	}

	.save-btn {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.save-btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	/* Format Toolbar */
	.format-toolbar {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		padding: 0.5rem 1rem;
		background: #fff;
		border-bottom: 1px solid var(--ap-border-default);
		overflow-x: auto;
		flex-wrap: nowrap;
	}

	.format-group {
		display: flex;
		align-items: center;
		gap: 0.15rem;
	}

	.format-divider {
		width: 1px;
		height: 20px;
		background: var(--ap-border-default);
		margin: 0 0.35rem;
		flex-shrink: 0;
	}

	.format-select {
		padding: 0.35rem 0.5rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.35rem;
		font-size: 0.8rem;
		background: #fff;
		cursor: pointer;
	}

	.format-btn {
		width: 32px;
		height: 32px;
		border-radius: 0.35rem;
		display: flex;
		align-items: center;
		justify-content: center;
		background: transparent;
		border: none;
		cursor: pointer;
		color: var(--ap-text-muted);
		transition: all 0.15s;
		flex-shrink: 0;
	}

	.format-btn:hover {
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
	}

	.format-btn.active {
		background: #1e293b;
		color: #fff;
	}

	/* Editor Container */
	.editor-container {
		display: flex;
		flex: 1;
	}

	/* Sidebar */
	.editor-sidebar {
		width: 280px;
		background: #fff;
		border-right: 1px solid var(--ap-border-default);
		padding: 1rem;
		overflow-y: auto;
		flex-shrink: 0;
		display: none;
	}

	@media (min-width: 992px) {
		.editor-sidebar {
			display: block;
		}
	}

	.editor-sidebar.open {
		display: block;
		position: fixed;
		top: 0;
		left: 0;
		bottom: 0;
		z-index: 200;
		box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
	}

	.sidebar-section {
		margin-bottom: 1.5rem;
	}

	.sidebar-section h3 {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.85rem;
		font-weight: 700;
		margin: 0 0 0.75rem;
		color: var(--ap-text-default);
	}

	.sidebar-section h3 i {
		color: #f97316;
	}

	.sidebar-hint {
		font-size: 0.7rem;
		color: var(--ap-text-muted);
		margin: 0 0 0.5rem;
	}

	.template-list,
	.variable-list,
	.info-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.template-list li,
	.variable-list li {
		margin-bottom: 0.35rem;
	}

	.template-list .empty {
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		font-style: italic;
	}

	.template-btn,
	.var-btn {
		width: 100%;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 0.75rem;
		background: var(--ap-bg-surface);
		border: none;
		border-radius: 0.5rem;
		font-size: 0.8rem;
		cursor: pointer;
		text-align: left;
		transition: all 0.15s;
	}

	.template-btn:hover,
	.var-btn:hover {
		background: var(--ap-bg-page);
	}

	.info-list li {
		display: flex;
		justify-content: space-between;
		padding: 0.35rem 0;
		font-size: 0.8rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.info-list li:last-child {
		border: none;
	}

	.info-list .label {
		color: var(--ap-text-muted);
	}

	.info-list .value {
		font-weight: 600;
	}

	.info-list .status-draft {
		color: #d97706;
	}

	.info-list .status-publish {
		color: #10b981;
	}

	.template-checkbox {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.8rem;
		cursor: pointer;
	}

	.template-checkbox input {
		width: 16px;
		height: 16px;
		accent-color: #f97316;
	}

	/* Main Editor */
	.editor-main {
		flex: 1;
		padding: 2rem;
		display: flex;
		justify-content: center;
		min-width: 0;
	}

	.editor-content {
		width: 100%;
		max-width: 800px;
		min-height: 600px;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		padding: 2rem;
		font-size: 1rem;
		line-height: 1.7;
		outline: none;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
	}

	.editor-content:focus {
		border-color: #f97316;
		box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
	}

	.editor-content:empty::before {
		content: attr(data-placeholder);
		color: var(--ap-text-muted);
		pointer-events: none;
	}

	.editor-content h1 {
		font-size: 2rem;
		margin: 0 0 1rem;
	}

	.editor-content h2 {
		font-size: 1.5rem;
		margin: 1.5rem 0 0.75rem;
	}

	.editor-content h3 {
		font-size: 1.25rem;
		margin: 1.25rem 0 0.5rem;
	}

	.editor-content h4 {
		font-size: 1.1rem;
		margin: 1rem 0 0.5rem;
	}

	.editor-content p {
		margin: 0 0 1rem;
	}

	.editor-content ul,
	.editor-content ol {
		margin: 0 0 1rem;
		padding-left: 1.5rem;
	}

	.editor-content a {
		color: #f97316;
		text-decoration: underline;
	}

	.editor-content img {
		max-width: 100%;
		height: auto;
		border-radius: 0.5rem;
		margin: 1rem 0;
	}

	.editor-content hr {
		border: none;
		height: 1px;
		background: var(--ap-border-default);
		margin: 1.5rem 0;
	}

	.editor-content table {
		width: 100%;
		border-collapse: collapse;
		margin: 1rem 0;
	}

	.editor-content th,
	.editor-content td {
		border: 1px solid var(--ap-border-default);
		padding: 0.5rem 0.75rem;
		text-align: left;
	}

	.editor-content th {
		background: var(--ap-bg-surface);
		font-weight: 600;
	}

	/* Toggle Sidebar Button */
	.toggle-sidebar {
		position: fixed;
		bottom: 1.5rem;
		left: 1rem;
		width: 48px;
		height: 48px;
		border-radius: 50%;
		background: #1e293b;
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		z-index: 150;
	}

	@media (min-width: 992px) {
		.toggle-sidebar {
			display: none;
		}
	}

	/* Modals */
	.editor-modal {
		position: fixed;
		inset: 0;
		z-index: 300;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 1rem;
	}

	.editor-modal[aria-hidden="false"] {
		display: flex;
	}

	.editor-modal .modal-overlay {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
	}

	.editor-modal .modal-content {
		position: relative;
		background: #fff;
		border-radius: 1rem;
		padding: 1.5rem;
		max-width: 400px;
		width: 100%;
		z-index: 1;
	}

	.editor-modal h3 {
		font-size: 1.1rem;
		font-weight: 700;
		margin: 0 0 1.25rem;
	}

	.form-group {
		margin-bottom: 1rem;
	}

	.form-group label {
		display: block;
		font-size: 0.8rem;
		font-weight: 600;
		margin-bottom: 0.35rem;
	}

	.form-group input {
		width: 100%;
		padding: 0.65rem 0.75rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.5rem;
		font-size: 0.9rem;
	}

	.form-group input:focus {
		outline: none;
		border-color: #f97316;
	}

	.modal-actions {
		display: flex;
		justify-content: flex-end;
		gap: 0.75rem;
		margin-top: 1.25rem;
	}

	.modal-cancel {
		padding: 0.5rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 999px;
		background: #fff;
		font-size: 0.85rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.modal-cancel:hover {
		background: var(--ap-bg-surface);
	}

	.modal-confirm {
		padding: 0.5rem 1rem;
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
		border-radius: 999px;
		font-size: 0.85rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.modal-confirm:hover {
		transform: translateY(-2px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
	}

	/* Export Options */
	.export-options {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 0.75rem;
	}

	.export-option {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
		padding: 1.25rem 1rem;
		background: var(--ap-bg-surface);
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.export-option:hover {
		border-color: #f97316;
		background: #fff;
	}

	.export-option i {
		font-size: 1.5rem;
		color: var(--ap-text-muted);
	}

	.export-option span {
		font-size: 0.8rem;
		font-weight: 600;
	}

	/* Dark Mode */
	body.dark-mode .editor-toolbar,
	body.dark-mode .format-toolbar,
	body.dark-mode .editor-sidebar,
	body.dark-mode .editor-content,
	body.dark-mode .editor-modal .modal-content {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .doc-title-input,
	body.dark-mode .format-select,
	body.dark-mode .doc-type-select select,
	body.dark-mode .form-group input {
		background: var(--ap-bg-surface);
		color: var(--ap-text-default);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .toggle-sidebar {
		background: var(--ap-bg-surface);
	}
</style>

<script>
	(function() {
		const editor = document.querySelector('.apollo-doc-editor');
		if (!editor) return;

		const content = document.getElementById('editor-content');
		const title = document.getElementById('doc-title');
		const saveStatus = document.getElementById('save-status');
		const docId = editor.dataset.docId;

		// Formatting commands
		document.querySelectorAll('[data-command]').forEach(btn => {
			btn.addEventListener('click', () => {
				document.execCommand(btn.dataset.command, false, null);
				content.focus();
			});
		});

		// Heading select
		document.getElementById('heading-select')?.addEventListener('change', function() {
			document.execCommand('formatBlock', false, this.value);
			content.focus();
		});

		// Word/character count
		function updateCounts() {
			const text = content.textContent || '';
			const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
			const chars = text.length;
			document.getElementById('word-count').textContent = words;
			document.getElementById('char-count').textContent = chars;
		}

		content.addEventListener('input', () => {
			updateCounts();
			saveStatus.innerHTML = '<i class="i-loader-v" style="animation: spin 1s linear infinite;"></i> Salvando...';

			// Auto-save debounce
			clearTimeout(content.saveTimer);
			content.saveTimer = setTimeout(autoSave, 2000);
		});

		updateCounts();

		// Auto-save function
		function autoSave() {
			if (typeof apolloAjax === 'undefined') {
				saveStatus.innerHTML = 'Salvo localmente';
				return;
			}

			fetch(apolloAjax.ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams({
						action: 'apollo_save_document',
						doc_id: docId,
						title: title.value,
						content: content.innerHTML,
						doc_type: document.getElementById('doc-type').value,
						is_template: document.getElementById('save-as-template').checked ? '1' : '0',
						nonce: apolloAjax.nonce
					})
				})
				.then(r => r.json())
				.then(data => {
					const now = new Date().toLocaleTimeString('pt-BR', {
						hour: '2-digit',
						minute: '2-digit'
					});
					saveStatus.innerHTML = `<i class="i-check-v"></i> Salvo às ${now}`;
				})
				.catch(() => {
					saveStatus.innerHTML = '<i class="i-error-warning-v" style="color:#dc2626;"></i> Erro ao salvar';
				});
		}

		// Manual save
		document.getElementById('btn-save')?.addEventListener('click', autoSave);

		// Toggle sidebar
		const sidebar = document.getElementById('editor-sidebar');
		document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
			sidebar.classList.toggle('open');
		});

		// Close sidebar when clicking outside (mobile)
		document.addEventListener('click', (e) => {
			if (sidebar.classList.contains('open') &&
				!sidebar.contains(e.target) &&
				!e.target.closest('#toggle-sidebar')) {
				sidebar.classList.remove('open');
			}
		});

		// Variables insertion
		document.querySelectorAll('.var-btn').forEach(btn => {
			btn.addEventListener('click', () => {
				const varText = btn.dataset.var;
				document.execCommand('insertText', false, varText);
				content.focus();
			});
		});

		// Template loading
		document.querySelectorAll('.template-btn').forEach(btn => {
			btn.addEventListener('click', () => {
				const templateId = btn.dataset.templateId;
				// Would load template content via AJAX
				alert('Carregar template ID: ' + templateId);
			});
		});

		// Link modal
		const linkModal = document.getElementById('link-modal');
		document.getElementById('btn-link')?.addEventListener('click', () => {
			const selection = window.getSelection();
			document.getElementById('link-text').value = selection.toString();
			linkModal.setAttribute('aria-hidden', 'false');
		});

		document.getElementById('insert-link')?.addEventListener('click', () => {
			const url = document.getElementById('link-url').value;
			const text = document.getElementById('link-text').value || url;
			if (url) {
				document.execCommand('insertHTML', false, `<a href="${url}" target="_blank">${text}</a>`);
			}
			linkModal.setAttribute('aria-hidden', 'true');
			content.focus();
		});

		// Export modal
		const exportModal = document.getElementById('export-modal');
		document.getElementById('btn-export')?.addEventListener('click', () => {
			exportModal.setAttribute('aria-hidden', 'false');
		});

		document.querySelectorAll('.export-option').forEach(btn => {
			btn.addEventListener('click', () => {
				const format = btn.dataset.format;
				// Would trigger export via AJAX
				alert('Exportar como: ' + format.toUpperCase());
				exportModal.setAttribute('aria-hidden', 'true');
			});
		});

		// Close modals
		document.querySelectorAll('.modal-cancel, .modal-overlay').forEach(el => {
			el.addEventListener('click', () => {
				el.closest('.editor-modal').setAttribute('aria-hidden', 'true');
			});
		});

		// Keyboard shortcuts
		document.addEventListener('keydown', (e) => {
			if (e.ctrlKey || e.metaKey) {
				switch (e.key.toLowerCase()) {
					case 's':
						e.preventDefault();
						autoSave();
						break;
				}
			}
		});
	})();
</script>
