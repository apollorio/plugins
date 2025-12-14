<?php
/**
 * Local Signature Controller.
 *
 * Handles AJAX requests for local signature processing.
 * This file uses PSR-4 autoloading conventions.
 *
 * @package Apollo\Modules\Signatures\Controllers
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Apollo\Modules\Signatures\Controllers;

use Apollo\Modules\Signatures\Adapters\LocalSignatureAdapter;

/**
 * Local Signature AJAX Controller
 *
 * Handles AJAX requests for local signature processing.
 */
class LocalSignatureController
{
    /**
     * Local signature adapter instance.
     *
     * @var LocalSignatureAdapter
     */
    private LocalSignatureAdapter $adapter;

    /**
     * Constructor - register AJAX handlers.
     */
    public function __construct()
    {
        $this->adapter = new LocalSignatureAdapter();

        // Register AJAX handlers.
        add_action('wp_ajax_apollo_process_local_signature', [ $this, 'processSignature' ]);
        add_action('wp_ajax_nopriv_apollo_process_local_signature', [ $this, 'processSignature' ]);

        add_action('wp_ajax_apollo_verify_signature', [ $this, 'verifySignature' ]);
        add_action('wp_ajax_nopriv_apollo_verify_signature', [ $this, 'verifySignature' ]);
    }

    /**
     * Process local signature via AJAX.
     *
     * @return void
     */
    public function processSignature()
    {
        // Verify nonce.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'apollo_local_signature')) {
            wp_die(esc_html__('Security check failed', 'apollo-social'));
        }

        try {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data sanitized after decode.
            $raw_data       = isset($_POST['signature_data']) ? wp_unslash($_POST['signature_data']) : '{}';
            $signature_data = json_decode($raw_data, true);

            if (empty($signature_data)) {
                wp_send_json_error([ 'errors' => [ 'Dados de assinatura inválidos' ] ]);
            }

            // Process signature via adapter.
            $result = $this->adapter->processSignature($signature_data);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } catch (\Exception $e) {
            wp_send_json_error([ 'errors' => [ 'Erro interno: ' . $e->getMessage() ] ]);
        }
    }

    /**
     * Verify signature via AJAX.
     *
     * @return void
     */
    public function verifySignature()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Public verification endpoint for certificate validation.
        $nonce          = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $certificate_id = isset($_POST['certificate_id']) ? sanitize_text_field(wp_unslash($_POST['certificate_id'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // Allow public verification without nonce (read-only operation).
        if (empty($certificate_id)) {
            wp_send_json_error([ 'error' => 'ID do certificado é obrigatório' ]);
        }

        try {
            $result = $this->adapter->verifySignature($certificate_id);
            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error([ 'error' => 'Erro interno: ' . $e->getMessage() ]);
        }
    }

    /**
     * Render signature canvas page.
     *
     * @return void
     */
    public function renderSignatureCanvas()
    {
        // Security headers.
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public signature canvas page.
        $template_id  = isset($_GET['template_id']) ? sanitize_text_field(wp_unslash($_GET['template_id'])) : '';
        $signer_email = isset($_GET['signer_email']) ? sanitize_email(wp_unslash($_GET['signer_email'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Load template.
        $template_path = defined('APOLLO_SOCIAL_PLUGIN_DIR') ? APOLLO_SOCIAL_PLUGIN_DIR . 'templates/signatures/local-signature-canvas.php' : '';

        if ($template_path && file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die(esc_html__('Template não encontrado', 'apollo-social'));
        }

        exit;
    }

    /**
     * Render signature verification page.
     *
     * @param string $certificate_id The certificate ID to verify.
     * @return void
     */
    public function renderVerificationPage($certificate_id)
    {
        $result = $this->adapter->verifySignature($certificate_id);

        if (! $result['valid']) {
            wp_die('Certificado inválido ou não encontrado');
        }

        $signature_info       = $result['signature_info'];
        $verification_details = $result['verification_details'];

        ?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Verificação de Assinatura - Apollo Social</title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: #f8fafc;
					margin: 0;
					padding: 20px;
				}

				.verification-container {
					max-width: 800px;
					margin: 0 auto;
					background: white;
					border-radius: 12px;
					box-shadow: 0 4px 12px rgba(0,0,0,0.1);
					overflow: hidden;
				}

				.verification-header {
					background: linear-gradient(135deg, #10b981 0%, #059669 100%);
					color: white;
					padding: 30px;
					text-align: center;
				}

				.verification-body {
					padding: 30px;
				}

				.info-section {
					margin-bottom: 25px;
					padding: 20px;
					background: #f8fafc;
					border-radius: 8px;
					border-left: 4px solid #10b981;
				}

				.info-section h3 {
					margin: 0 0 15px 0;
					color: #374151;
				}

				.info-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
					gap: 15px;
				}

				.info-item {
					display: flex;
					justify-content: space-between;
					padding: 10px 0;
					border-bottom: 1px solid #e5e7eb;
				}

				.info-label {
					font-weight: 600;
					color: #6b7280;
				}

				.info-value {
					color: #374151;
					text-align: right;
					word-break: break-all;
				}

				.status-badge {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					padding: 8px 16px;
					background: #dcfce7;
					color: #166534;
					border-radius: 20px;
					font-weight: 600;
					font-size: 14px;
				}
			</style>
		</head>
		<body>
			<div class="verification-container">
				<div class="verification-header">
					<h1>✅ Assinatura Verificada</h1>
					<p>Certificado Apollo Social</p>
					<div class="status-badge">
						🔒 Válida e Íntegra
					</div>
				</div>

				<div class="verification-body">
					<div class="info-section">
						<h3>👤 Informações do Signatário</h3>
						<div class="info-grid">
							<div class="info-item">
								<span class="info-label">Nome:</span>
								<span class="info-value"><?php echo esc_html($signature_info['signer_name']); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">Email:</span>
								<span class="info-value"><?php echo esc_html($signature_info['signer_email']); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">Data/Hora:</span>
								<span class="info-value"><?php echo esc_html(wp_date('d/m/Y H:i:s', strtotime($signature_info['signed_at']))); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">IP Address:</span>
								<span class="info-value"><?php echo esc_html($signature_info['ip_address']); ?></span>
							</div>
						</div>
					</div>

					<div class="info-section">
						<h3>🔐 Detalhes Técnicos</h3>
						<div class="info-grid">
							<div class="info-item">
								<span class="info-label">Certificado ID:</span>
								<span class="info-value"><?php echo esc_html($signature_info['certificate_id']); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">Algoritmo Hash:</span>
								<span class="info-value"><?php echo esc_html($verification_details['hash_algorithm']); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">Hash Value:</span>
								<span class="info-value" style="font-family: monospace; font-size: 12px;">
									<?php echo esc_html(substr($verification_details['hash_value'], 0, 32)) . '...'; ?>
								</span>
							</div>
							<div class="info-item">
								<span class="info-label">Pontos de Traço:</span>
								<span class="info-value"><?php echo esc_html($verification_details['stroke_points']); ?></span>
							</div>
							<div class="info-item">
								<span class="info-label">Dimensões Canvas:</span>
								<span class="info-value">
									<?php echo esc_html($verification_details['canvas_dimensions']['width']); ?>x<?php echo esc_html($verification_details['canvas_dimensions']['height']); ?>
								</span>
							</div>
						</div>
					</div>

					<div class="info-section">
						<h3>⚖️ Informações Legais</h3>
						<p style="color: #6b7280; line-height: 1.6;">
							Esta assinatura foi capturada localmente pelo sistema Apollo Social e possui validade para fins internos da plataforma.
							O certificado digital foi gerado com timestamp confiável e hash SHA-256 para garantir a integridade dos dados.
						</p>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
        exit;
    }
}
