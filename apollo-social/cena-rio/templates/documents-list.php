<?php
/**
 * Documents List - Cena::Rio
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @subpackage CenaRio
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id        = get_current_user_id();
$user_documents = array();
$max_documents  = 5;

if ( class_exists( 'Apollo\CenaRio\CenaRioModule' ) ) {
	if ( method_exists( 'Apollo\CenaRio\CenaRioModule', 'getUserDocuments' ) ) {
		$user_documents = Apollo\CenaRio\CenaRioModule::getUserDocuments( $user_id );
	}
	if ( defined( 'Apollo\CenaRio\CenaRioModule::MAX_DOCUMENTS_PER_USER' ) ) {
		$max_documents = Apollo\CenaRio\CenaRioModule::MAX_DOCUMENTS_PER_USER;
	}
}

$docs_count = count( $user_documents );
$can_create = $docs_count < $max_documents;
?>

<div class="ap-section">
	<!-- Header -->
	<div class="ap-section-header">
		<div class="ap-section-title-group">
			<h2 class="ap-heading-2">Meus Documentos</h2>
			<p class="ap-text-muted">
				<?php echo esc_html( $docs_count ); ?> de <?php echo esc_html( $max_documents ); ?> documentos criados
			</p>
		</div>
		<div class="ap-section-actions">
			<?php if ( $can_create ) : ?>
			<a href="<?php echo esc_url( home_url( '/doc/new' ) ); ?>"
				class="ap-btn ap-btn-primary"
				data-ap-tooltip="Criar um novo documento">
				<i class="ri-add-line"></i>
				Novo Documento
			</a>
			<?php else : ?>
			<span class="ap-badge ap-badge-warning" data-ap-tooltip="Você atingiu o limite de documentos">
				<i class="ri-error-warning-line"></i>
				Limite atingido
			</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Documents Grid -->
	<?php if ( empty( $user_documents ) ) : ?>
	<div class="ap-card ap-empty-state-card">
		<div class="ap-empty-state">
			<i class="ri-file-text-line"></i>
			<h3>Nenhum documento criado</h3>
			<p>Crie seu primeiro documento para começar</p>
			<a href="<?php echo esc_url( home_url( '/doc/new' ) ); ?>"
				class="ap-btn ap-btn-primary"
				data-ap-tooltip="Criar seu primeiro documento">
				<i class="ri-add-line"></i>
				Criar Documento
			</a>
		</div>
	</div>
	<?php else : ?>
	<div class="ap-grid ap-grid-3">
		<?php
		foreach ( $user_documents as $doc ) :
			$doc_status    = $doc->post_status;
			$status_config = array(
				'publish' => array(
					'label'   => 'Publicado',
					'class'   => 'ap-badge-success',
					'tooltip' => __( 'Documento publicado e disponível', 'apollo-social' ),
				),
				'draft'   => array(
					'label'   => 'Rascunho',
					'class'   => 'ap-badge-warning',
					'tooltip' => __( 'Documento em rascunho, não publicado', 'apollo-social' ),
				),
				'pending' => array(
					'label'   => 'Pendente',
					'class'   => 'ap-badge-info',
					'tooltip' => __( 'Aguardando revisão/aprovação', 'apollo-social' ),
				),
			);
			$status        = $status_config[ $doc_status ] ?? array(
				'label'   => ucfirst( $doc_status ),
				'class'   => 'ap-badge-secondary',
				'tooltip' => sprintf( __( 'Status: %s', 'apollo-social' ), ucfirst( $doc_status ) ),
			);
			?>
		<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>"
			class="ap-card ap-card-hover"
			data-ap-tooltip="<?php esc_attr_e( 'Clique para ver detalhes', 'apollo-social' ); ?>">
			<div class="ap-card-header">
				<div class="ap-card-icon" data-ap-tooltip="<?php esc_attr_e( 'Tipo: Documento', 'apollo-social' ); ?>">
					<i class="ri-file-text-line"></i>
				</div>
				<span class="ap-badge <?php echo esc_attr( $status['class'] ); ?>" data-ap-tooltip="<?php echo esc_attr( $status['tooltip'] ); ?>">
					<?php echo esc_html( $status['label'] ); ?>
				</span>
			</div>
			<div class="ap-card-body">
				<h3 class="ap-card-title"><?php echo esc_html( $doc->post_title ); ?></h3>
				<?php if ( ! empty( $doc->post_content ) ) : ?>
				<p class="ap-card-text"><?php echo esc_html( wp_trim_words( $doc->post_content, 15 ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ap-card-footer">
				<div class="ap-card-meta">
					<i class="ri-calendar-line"></i>
					<span><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $doc->post_date ) ) ); ?></span>
				</div>
				<div class="ap-card-action">
					<i class="ri-arrow-right-line"></i>
				</div>
			</div>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
