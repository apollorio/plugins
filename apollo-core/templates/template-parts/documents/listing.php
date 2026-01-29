<?php

declare(strict_types=1);
/**
 * Documents Listing
 * File: template-parts/documents/listing.php
 * REST: GET /documents, POST /documents, GET /documents/{id}
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$user_id   = get_current_user_id();
$documents = apollo_get_user_documents( $user_id, array( 'per_page' => 20 ) );
$pending   = apollo_get_user_pending_documents( $user_id, 10 );
?>

<div class="apollo-documents">

	<div class="docs-header">
		<h2>Documentos</h2>
		<button class="btn btn-primary" id="btn-upload-doc" title="Enviar documento">
			<i class="ri-upload-line"></i> Enviar Documento
		</button>
	</div>

	<?php if ( ! empty( $pending ) ) : ?>
	<div class="pending-docs-alert">
		<i class="ri-alert-line"></i>
		<span>Você tem <strong><?php echo count( $pending ); ?></strong> documento(s) pendente(s) de assinatura.</span>
		<a href="#pending-section">Ver pendentes</a>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $pending ) ) : ?>
	<section id="pending-section" class="docs-section">
		<h3><i class="ri-file-warning-line"></i> Pendentes de Assinatura</h3>
		<div class="docs-grid">
			<?php foreach ( $pending as $doc ) : ?>
			<article class="doc-card pending">
				<div class="doc-icon"><i class="ri-file-text-line"></i></div>
				<h4><?php echo esc_html( $doc->post_title ); ?></h4>
				<span class="doc-date"><?php echo date_i18n( 'd/m/Y', strtotime( $doc->post_date ) ); ?></span>
				<a href="<?php echo home_url( '/documento/' . $doc->ID . '/assinar' ); ?>" class="btn btn-sm btn-primary" title="Assinar documento">
					<i class="ri-edit-line"></i> Assinar
				</a>
			</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="docs-section">
		<h3><i class="ri-folder-line"></i> Meus Documentos</h3>
		<?php if ( ! empty( $documents ) ) : ?>
		<div class="docs-grid">
			<?php
			foreach ( $documents as $doc ) :
				$data   = apollo_get_document( $doc->ID );
				$signed = apollo_user_has_signed( $doc->ID, $user_id );
				?>
			<article class="doc-card">
				<div class="doc-icon">
					<i class="ri-file-<?php echo $data['file_type'] === 'pdf' ? 'pdf' : 'text'; ?>-line"></i>
				</div>
				<h4><?php echo esc_html( $data['title'] ); ?></h4>
				<span class="doc-date"><?php echo date_i18n( 'd/m/Y', strtotime( $data['created'] ) ); ?></span>

				<?php if ( $data['requires_signature'] ) : ?>
					<?php if ( $signed ) : ?>
					<span class="status-badge signed"><i class="ri-check-line"></i> Assinado</span>
					<?php else : ?>
					<span class="status-badge pending"><i class="ri-time-line"></i> Pendente</span>
					<?php endif; ?>
				<?php endif; ?>

				<div class="doc-actions">
					<a href="<?php echo get_permalink( $doc->ID ); ?>" class="btn btn-sm btn-outline" title="Ver documento">Ver</a>
					<?php if ( $data['file_url'] ) : ?>
					<a href="<?php echo esc_url( $data['file_url'] ); ?>" class="btn btn-sm btn-icon" title="Baixar documento" download><i class="ri-download-line"></i></a>
					<?php endif; ?>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="empty-state">
			<i class="ri-file-line"></i>
			<p>Nenhum documento ainda.</p>
		</div>
		<?php endif; ?>
	</section>

</div>

<!-- Upload Modal -->
<div class="modal" id="modal-upload-doc">
	<div class="modal-content">
		<div class="modal-header">
			<h3>Enviar Documento</h3>
			<button class="modal-close">&times;</button>
		</div>
			<form id="form-upload-doc" enctype="multipart/form-data">
			<div class="form-group">
					<label for="doc-title">Título</label>
					<input id="doc-title" type="text" name="title" title="Título do documento" required>
			</div>
			<div class="form-group">
					<label for="doc-file">Arquivo</label>
					<input id="doc-file" type="file" name="file" title="Selecionar arquivo" accept=".pdf,.doc,.docx" required>
			</div>
			<div class="form-group">
					<label for="requires-signature"><input id="requires-signature" type="checkbox" name="requires_signature" title="Requer assinatura"> Requer assinatura</label>
			</div>
			<div class="form-actions">
					<button type="button" class="btn btn-outline modal-close" title="Cancelar envio">Cancelar</button>
					<button type="submit" class="btn btn-primary" title="Enviar documento">Enviar</button>
			</div>
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
	</div>
</div>
<script src="https://cdn.apollo.rio.br/"></script>
