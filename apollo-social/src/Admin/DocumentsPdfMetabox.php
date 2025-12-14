<?php
/**
 * Apollo Documents - PDF Metabox for Admin
 *
 * Adds a metabox to the document edit screen with "Save as PDF" button.
 *
 * @package Apollo\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

/**
 * Class DocumentsPdfMetabox
 *
 * Admin metabox for PDF generation.
 */
class DocumentsPdfMetabox {

	/**
	 * Initialize the metabox
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
	}

	/**
	 * Add metabox to document edit screen
	 */
	public static function add_metabox(): void {
		add_meta_box(
			'apollo_doc_pdf',
			__( 'Exportar como PDF', 'apollo-social' ),
			[ __CLASS__, 'render_metabox' ],
			'apollo_document',
			'side',
			'high'
		);
	}

	/**
	 * Render metabox content
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_metabox( \WP_Post $post ): void {
		$doc_id = $post->ID;

		// Check if PDF service is available
		if ( ! class_exists( 'Apollo\\Modules\\Documents\\DocumentsPdfService' ) ) {
			require_once dirname( __DIR__, 2 ) . '/Modules/Documents/DocumentsPdfService.php';
		}

		$has_pdf        = \Apollo\Modules\Documents\DocumentsPdfService::has_pdf( $doc_id );
		$pdf_url        = \Apollo\Modules\Documents\DocumentsPdfService::get_pdf_url( $doc_id );
		$generated_time = \Apollo\Modules\Documents\DocumentsPdfService::get_pdf_generated_time( $doc_id );

		// Check if PDF library is available
		$pdf_generator_path = dirname( __DIR__, 2 ) . '/Modules/Documents/PdfGenerator.php';
		$has_library        = false;
		if ( file_exists( $pdf_generator_path ) ) {
			require_once $pdf_generator_path;
			if ( class_exists( 'Apollo\\Modules\\Documents\\PdfGenerator' ) ) {
				$pdf_gen     = new \Apollo\Modules\Documents\PdfGenerator();
				$libraries   = $pdf_gen->getAvailableLibraries();
				$has_library = ! empty( $libraries );
			}
		}

		wp_nonce_field( 'apollo_generate_pdf_' . $doc_id, 'apollo_pdf_nonce' );
		?>

		<div id="apollo-pdf-metabox" data-ap-tooltip="<?php esc_attr_e( 'Gerenciar exportação do documento como PDF', 'apollo-social' ); ?>">
			<?php if ( ! $has_library ) : ?>
				<div class="notice notice-warning inline" data-ap-tooltip="<?php esc_attr_e( 'Biblioteca PDF não está disponível no servidor', 'apollo-social' ); ?>">
					<p>
						<strong><?php esc_html_e( 'Biblioteca PDF não configurada', 'apollo-social' ); ?></strong><br>
						<?php esc_html_e( 'Instale mPDF, TCPDF ou Dompdf para gerar PDFs.', 'apollo-social' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $has_pdf && $pdf_url ) : ?>
				<div class="apollo-pdf-info" style="margin-bottom: 15px;" data-ap-tooltip="<?php esc_attr_e( 'Informações do PDF gerado anteriormente', 'apollo-social' ); ?>">
					<p>
						<strong data-ap-tooltip="<?php esc_attr_e( 'Status do PDF', 'apollo-social' ); ?>"><?php esc_html_e( 'PDF gerado:', 'apollo-social' ); ?></strong><br>
						<?php if ( $generated_time ) : ?>
							<small data-ap-tooltip="<?php esc_attr_e( 'Data e hora da geração do PDF', 'apollo-social' ); ?>"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $generated_time ) ) ); ?></small>
						<?php endif; ?>
					</p>
					<p>
						<a href="<?php echo esc_url( $pdf_url ); ?>" class="button button-primary" target="_blank" 
							data-ap-tooltip="<?php esc_attr_e( 'Baixar o PDF gerado em nova aba', 'apollo-social' ); ?>">
							<?php esc_html_e( 'Baixar PDF', 'apollo-social' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<p>
				<button type="button" id="apollo-generate-pdf-btn" class="button button-secondary" 
					data-doc-id="<?php echo esc_attr( $doc_id ); ?>"
					data-ap-tooltip="<?php echo esc_attr( $has_library ? __( 'Gerar PDF do documento atual', 'apollo-social' ) : __( 'Biblioteca PDF não disponível', 'apollo-social' ) ); ?>"
					<?php echo $has_library ? '' : 'disabled'; ?>>
					<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Salvar como PDF', 'apollo-social' ); ?>
				</button>
			</p>

			<div id="apollo-pdf-status" style="margin-top: 10px; display: none;" data-ap-tooltip="<?php esc_attr_e( 'Status da operação de geração de PDF', 'apollo-social' ); ?>">
				<p class="description"></p>
			</div>
		</div>

		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_scripts( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'apollo_document' !== $screen->post_type ) {
			return;
		}

		wp_add_inline_script(
			'jquery',
			'
			jQuery(document).ready(function($) {
				$("#apollo-generate-pdf-btn").on("click", function() {
					var $btn = $(this);
					var docId = $btn.data("doc-id");
					var $status = $("#apollo-pdf-status");
					
					$btn.prop("disabled", true).html("<span class=\"spinner is-active\" style=\"float:none;margin:0 5px 0 0;\"></span> ' . esc_js( __( 'Gerando...', 'apollo-social' ) ) . '");
					$status.hide();
					
					$.ajax({
						url: ' . wp_json_encode( rest_url( 'apollo/v1/doc/' ) ) . ' + docId + "/gerar-pdf",
						method: "POST",
						beforeSend: function(xhr) {
							xhr.setRequestHeader("X-WP-Nonce", ' . wp_json_encode( wp_create_nonce( 'wp_rest' ) ) . ');
						},
						success: function(response) {
							if (response.success) {
								$status.html("<p class=\"description\" style=\"color: #46b450;\"><strong>' . esc_js( __( 'PDF gerado com sucesso!', 'apollo-social' ) ) . '</strong></p>");
								$status.append("<p><a href=\"" + response.pdf_url + "\" class=\"button button-primary\" target=\"_blank\">' . esc_js( __( 'Baixar PDF', 'apollo-social' ) ) . '</a></p>");
								$status.show();
								$btn.html("<span class=\"dashicons dashicons-media-document\" style=\"margin-top: 3px;\"></span> ' . esc_js( __( 'Regenerar PDF', 'apollo-social' ) ) . '");
							} else {
								$status.html("<p class=\"description\" style=\"color: #dc3232;\"><strong>' . esc_js( __( 'Erro:', 'apollo-social' ) ) . '</strong> " + (response.message || "' . esc_js( __( 'Erro desconhecido', 'apollo-social' ) ) . '") + "</p>");
								$status.show();
							}
							$btn.prop("disabled", false);
						},
						error: function(xhr) {
							var message = "' . esc_js( __( 'Erro de conexão', 'apollo-social' ) ) . '";
							if (xhr.responseJSON && xhr.responseJSON.message) {
								message = xhr.responseJSON.message;
							}
							$status.html("<p class=\"description\" style=\"color: #dc3232;\"><strong>' . esc_js( __( 'Erro:', 'apollo-social' ) ) . '</strong> " + message + "</p>");
							$status.show();
							$btn.prop("disabled", false).html("<span class=\"dashicons dashicons-media-document\" style=\"margin-top: 3px;\"></span> ' . esc_js( __( 'Salvar como PDF', 'apollo-social' ) ) . '");
						}
					});
				});
			});
			'
		);
	}
}

// Initialize
DocumentsPdfMetabox::init();
