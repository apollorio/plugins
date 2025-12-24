<?php
/**
 * Signature Capture.
 *
 * @package Apollo\Modules\Signatures\Local
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 * phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
 */

namespace Apollo\Modules\Signatures\Local;

/**
 * Signature Capture (Canvas + Web)
 *
 * Handles local signature capture without external APIs.
 */
class SignatureCapture {

	/**
	 * Render signature capture page
	 */
	public function renderCapturePage( string $token ): void {
		// Validate token
		$signature_request = $this->validateToken( $token );

		if ( ! $signature_request ) {
			$this->renderError( 'Token inválido ou expirado' );

			return;
		}

		$this->renderCaptureInterface( $signature_request );
	}

	/**
	 * Render signature capture interface
	 */
	private function renderCaptureInterface( array $request ): void {
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Assinar Documento - Apollo Social</title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 20px;
				}

				.signature-container {
					background: white;
					border-radius: 20px;
					box-shadow: 0 20px 60px rgba(0,0,0,0.15);
					max-width: 800px;
					width: 100%;
					overflow: hidden;
				}

				.signature-header {
					background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
					color: white;
					padding: 30px;
					text-align: center;
				}

				.signature-header h1 {
					font-size: 28px;
					margin-bottom: 10px;
					font-weight: 700;
				}

				.signature-body {
					padding: 40px;
				}

				.document-info {
					background: #f0f9ff;
					border: 1px solid #0ea5e9;
					border-radius: 12px;
					padding: 20px;
					margin-bottom: 30px;
				}

				.document-info h3 {
					color: #0ea5e9;
					margin-bottom: 10px;
				}

				.form-section {
					margin-bottom: 30px;
				}

				.form-section h3 {
					color: #374151;
					margin-bottom: 15px;
					font-size: 18px;
					font-weight: 600;
				}

				.form-group {
					margin-bottom: 20px;
				}

				.form-group label {
					display: block;
					margin-bottom: 8px;
					color: #374151;
					font-weight: 500;
				}

				.form-group input {
					width: 100%;
					padding: 12px 16px;
					border: 2px solid #e5e7eb;
					border-radius: 8px;
					font-size: 16px;
					transition: border-color 0.3s;
				}

				.form-group input:focus {
					outline: none;
					border-color: #4f46e5;
					box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
				}

				.signature-methods {
					display: flex;
					gap: 15px;
					margin-bottom: 20px;
				}

				.method-btn {
					flex: 1;
					padding: 15px;
					border: 2px solid #e5e7eb;
					border-radius: 12px;
					background: white;
					cursor: pointer;
					text-align: center;
					transition: all 0.3s;
				}

				.method-btn:hover {
					border-color: #4f46e5;
					background: #f0f0ff;
				}

				.method-btn.active {
					border-color: #4f46e5;
					background: #4f46e5;
					color: white;
				}

				.signature-area {
					border: 3px dashed #d1d5db;
					border-radius: 12px;
					padding: 20px;
					text-align: center;
					background: #f9fafb;
					margin: 20px 0;
					min-height: 250px;
					position: relative;
				}

				.signature-area.active {
					border-color: #4f46e5;
					background: #f0f0ff;
				}

				#signatureCanvas {
					border: 2px solid #e5e7eb;
					border-radius: 8px;
					cursor: crosshair;
					background: white;
					display: block;
					margin: 0 auto;
				}

				#typedSignature {
					width: 100%;
					padding: 20px;
					border: 2px solid #e5e7eb;
					border-radius: 12px;
					font-family: 'Brush Script MT', cursive;
					font-size: 32px;
					text-align: center;
					background: white;
					color: #374151;
				}

				.signature-controls {
					margin-top: 15px;
					display: flex;
					justify-content: center;
					gap: 15px;
					flex-wrap: wrap;
				}

				.btn {
					padding: 12px 24px;
					border: none;
					border-radius: 8px;
					font-size: 14px;
					font-weight: 600;
					cursor: pointer;
					transition: all 0.3s;
					text-decoration: none;
					display: inline-flex;
					align-items: center;
					gap: 8px;
				}

				.btn-primary {
					background: #4f46e5;
					color: white;
				}

				.btn-primary:hover {
					background: #4338ca;
					transform: translateY(-2px);
				}

				.btn-secondary {
					background: #6b7280;
					color: white;
				}

				.btn-secondary:hover {
					background: #4b5563;
				}

				.btn-outline {
					background: transparent;
					color: #4f46e5;
					border: 2px solid #4f46e5;
				}

				.btn-outline:hover {
					background: #4f46e5;
					color: white;
				}

				.legal-notice {
					background: #fffbeb;
					border: 1px solid #f59e0b;
					border-radius: 8px;
					padding: 20px;
					margin: 20px 0;
					font-size: 14px;
					line-height: 1.6;
					color: #92400e;
				}

				.legal-notice h4 {
					color: #d97706;
					margin-bottom: 10px;
				}

				.evidence-info {
					background: #f0fdf4;
					border: 1px solid #16a34a;
					border-radius: 8px;
					padding: 15px;
					margin: 20px 0;
					font-size: 13px;
				}

				.evidence-info h5 {
					color: #16a34a;
					margin-bottom: 8px;
				}

				.evidence-info ul {
					color: #166534;
					padding-left: 20px;
				}

				.loading {
					text-align: center;
					padding: 20px;
					display: none;
				}

				.loading .spinner {
					width: 40px;
					height: 40px;
					border: 4px solid #e5e7eb;
					border-top: 4px solid #4f46e5;
					border-radius: 50%;
					animation: spin 1s linear infinite;
					margin: 0 auto 15px;
				}

				@keyframes spin {
					0% { transform: rotate(0deg); }
					100% { transform: rotate(360deg); }
				}

				@media (max-width: 768px) {
					.signature-body {
						padding: 20px;
					}

					.signature-methods {
						flex-direction: column;
					}

					#signatureCanvas {
						width: 100%;
						height: 200px;
					}

					#typedSignature {
						font-size: 24px;
					}
				}
			</style>
		</head>
		<body>
			<div class="signature-container">
				<div class="signature-header">
					<h1>✍️ Assinatura de Documento</h1>
					<p>Sistema local Apollo Social</p>
				</div>

				<div class="signature-body">
					<!-- Document Info -->
					<div class="document-info">
						<h3>📄 Documento para Assinatura</h3>
						<p><strong>Título:</strong> <?php echo htmlspecialchars( $request['document_title'] ?? 'Documento Apollo' ); ?></p>
						<p><strong>Criado por:</strong> <?php echo htmlspecialchars( $request['creator_name'] ?? 'Apollo Social' ); ?></p>
						<p><strong>Válido até:</strong> <?php echo date( 'd/m/Y H:i', strtotime( $request['expires_at'] ) ); ?></p>
					</div>

					<!-- Signer Information -->
					<div class="form-section">
						<h3>👤 Suas Informações</h3>

						<div class="form-group">
							<label for="signerName">Nome Completo *</label>
							<input type="text" id="signerName" required placeholder="Digite seu nome completo">
						</div>

						<div class="form-group">
							<label for="signerEmail">Email *</label>
							<input type="email" id="signerEmail" required placeholder="Digite seu email">
						</div>

						<div class="form-group">
							<label for="signerWhatsapp">WhatsApp * (obrigatório)</label>
							<input type="tel" id="signerWhatsapp" required placeholder="(11) 99999-9999"
									pattern="\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}">
						</div>

						<div class="form-group">
							<label for="signerInstagram">Instagram * (obrigatório)</label>
							<input type="text" id="signerInstagram" required placeholder="@seuusuario"
									pattern="@[a-zA-Z0-9_.]+">
						</div>
					</div>

					<!-- Signature Method -->
					<div class="form-section">
						<h3>✍️ Método de Assinatura</h3>

						<div class="signature-methods">
							<div class="method-btn active" onclick="selectMethod('canvas')">
								<div style="font-size: 24px; margin-bottom: 8px;">✏️</div>
								<div><strong>Desenhar</strong></div>
								<div style="font-size: 12px; color: #6b7280;">Com caneta/dedo</div>
							</div>
							<div class="method-btn" onclick="selectMethod('typed')">
								<div style="font-size: 24px; margin-bottom: 8px;">⌨️</div>
								<div><strong>Digitar</strong></div>
								<div style="font-size: 12px; color: #6b7280;">Nome em fonte cursiva</div>
							</div>
						</div>

						<!-- Canvas Signature -->
						<div class="signature-area" id="canvasArea">
							<p style="margin-bottom: 15px;">👆 Desenhe sua assinatura no quadro abaixo</p>
							<canvas id="signatureCanvas" width="600" height="200"></canvas>

							<div class="signature-controls">
								<button type="button" class="btn btn-outline" onclick="clearCanvas()">
									🗑️ Limpar
								</button>
								<button type="button" class="btn btn-secondary" onclick="undoStroke()">
									↶ Desfazer
								</button>
							</div>
						</div>

						<!-- Typed Signature -->
						<div class="signature-area" id="typedArea" style="display: none;">
							<p style="margin-bottom: 15px;">⌨️ Digite seu nome para gerar assinatura cursiva</p>
							<input type="text" id="typedSignature" placeholder="Digite seu nome aqui"
									oninput="updateTypedPreview(this.value)">
						</div>
					</div>

					<!-- Legal Notice -->
					<div class="legal-notice">
						<h4>⚖️ Aviso Legal</h4>
						<p>
							Esta é uma <strong>assinatura eletrônica simples/avançada light</strong> conforme MP 2.200-2/2001.
							Não possui validade de certificação digital ICP-Brasil, mas é adequada para contratos privados
							onde as partes concordam com o meio eletrônico. O sistema gera evidências técnicas (hash, timestamp, IP)
							para comprovar a integridade e autenticidade.
						</p>
					</div>

					<!-- Evidence Info -->
					<div class="evidence-info">
						<h5>🔐 Evidências Coletadas</h5>
						<ul>
							<li>Hash SHA-256 da assinatura</li>
							<li>Timestamp e data/hora</li>
							<li>Endereço IP (parcialmente anonimizado)</li>
							<li>Informações do navegador</li>
							<li>Dados de contato (WhatsApp + Instagram)</li>
						</ul>
					</div>

					<!-- Action Buttons -->
					<div class="form-section">
						<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
							<button type="button" class="btn btn-primary" onclick="processSignature()">
								✅ Confirmar Assinatura
							</button>
							<a href="javascript:history.back()" class="btn btn-outline">
								← Cancelar
							</a>
						</div>
					</div>

					<!-- Loading State -->
					<div id="loadingContainer" class="loading">
						<div class="spinner"></div>
						<p>Processando assinatura...</p>
					</div>
				</div>
			</div>

			<script>
				// Signature capture logic
				let currentMethod = 'canvas';
				let canvas, ctx, isDrawing = false, strokes = [], currentStroke = [];
				let startTime = Date.now();

				// Initialize
				document.addEventListener('DOMContentLoaded', function() {
					initializeCanvas();
					setupInputMasks();
				});

				function initializeCanvas() {
					canvas = document.getElementById('signatureCanvas');
					ctx = canvas.getContext('2d');

					ctx.strokeStyle = '#000000';
					ctx.lineWidth = 2;
					ctx.lineCap = 'round';
					ctx.lineJoin = 'round';

					// Mouse events
					canvas.addEventListener('mousedown', startDrawing);
					canvas.addEventListener('mousemove', draw);
					canvas.addEventListener('mouseup', stopDrawing);
					canvas.addEventListener('mouseout', stopDrawing);

					// Touch events
					canvas.addEventListener('touchstart', handleTouch);
					canvas.addEventListener('touchmove', handleTouch);
					canvas.addEventListener('touchend', handleTouch);
				}

				function setupInputMasks() {
					// WhatsApp mask
					const whatsappInput = document.getElementById('signerWhatsapp');
					whatsappInput.addEventListener('input', function(e) {
						let value = e.target.value.replace(/\D/g, '');
						if (value.length >= 11) {
							value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
						} else if (value.length >= 7) {
							value = value.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
						} else if (value.length >= 3) {
							value = value.replace(/(\d{2})(\d+)/, '($1) $2');
						}
						e.target.value = value;
					});

					// Instagram mask
					const instagramInput = document.getElementById('signerInstagram');
					instagramInput.addEventListener('input', function(e) {
						let value = e.target.value;
						if (value && !value.startsWith('@')) {
							e.target.value = '@' + value;
						}
					});
				}

				function selectMethod(method) {
					currentMethod = method;

					// Update buttons
					document.querySelectorAll('.method-btn').forEach(btn => {
						btn.classList.remove('active');
					});
					event.target.closest('.method-btn').classList.add('active');

					// Show/hide areas
					if (method === 'canvas') {
						document.getElementById('canvasArea').style.display = 'block';
						document.getElementById('typedArea').style.display = 'none';
					} else {
						document.getElementById('canvasArea').style.display = 'none';
						document.getElementById('typedArea').style.display = 'block';
					}
				}

				function startDrawing(e) {
					isDrawing = true;
					currentStroke = [];

					const rect = canvas.getBoundingClientRect();
					const x = e.clientX - rect.left;
					const y = e.clientY - rect.top;

					ctx.beginPath();
					ctx.moveTo(x, y);
					currentStroke.push({x, y, timestamp: Date.now()});
				}

				function draw(e) {
					if (!isDrawing) return;

					const rect = canvas.getBoundingClientRect();
					const x = e.clientX - rect.left;
					const y = e.clientY - rect.top;

					ctx.lineTo(x, y);
					ctx.stroke();
					currentStroke.push({x, y, timestamp: Date.now()});
				}

				function stopDrawing() {
					if (!isDrawing) return;

					isDrawing = false;
					strokes.push([...currentStroke]);
					currentStroke = [];
				}

				function handleTouch(e) {
					e.preventDefault();

					const touch = e.touches[0] || e.changedTouches[0];
					const mouseEvent = new MouseEvent(
						e.type === 'touchstart' ? 'mousedown' :
						e.type === 'touchmove' ? 'mousemove' : 'mouseup',
						{
							clientX: touch.clientX,
							clientY: touch.clientY
						}
					);

					canvas.dispatchEvent(mouseEvent);
				}

				function clearCanvas() {
					ctx.clearRect(0, 0, canvas.width, canvas.height);
					strokes = [];
					currentStroke = [];
				}

				function undoStroke() {
					if (strokes.length === 0) return;

					strokes.pop();
					redrawCanvas();
				}

				function redrawCanvas() {
					ctx.clearRect(0, 0, canvas.width, canvas.height);

					strokes.forEach(stroke => {
						if (stroke.length === 0) return;

						ctx.beginPath();
						ctx.moveTo(stroke[0].x, stroke[0].y);

						stroke.forEach(point => {
							ctx.lineTo(point.x, point.y);
						});

						ctx.stroke();
					});
				}

				function updateTypedPreview(value) {
					const preview = document.getElementById('typedSignature');
					preview.style.fontFamily = value ? "'Brush Script MT', cursive" : "inherit";
				}

				function validateForm() {
					const name = document.getElementById('signerName').value.trim();
					const email = document.getElementById('signerEmail').value.trim();
					const whatsapp = document.getElementById('signerWhatsapp').value.trim();
					const instagram = document.getElementById('signerInstagram').value.trim();

					if (!name || !email || !whatsapp || !instagram) {
						alert('Todos os campos são obrigatórios');
						return false;
					}

					if (currentMethod === 'canvas' && strokes.length === 0) {
						alert('Por favor, desenhe sua assinatura');
						return false;
					}

					if (currentMethod === 'typed' && !document.getElementById('typedSignature').value.trim()) {
						alert('Por favor, digite seu nome para a assinatura');
						return false;
					}

					return true;
				}

				async function processSignature() {
					if (!validateForm()) return;

					document.getElementById('loadingContainer').style.display = 'block';

					const signatureData = {
						token: '<?php echo htmlspecialchars( $request['token'] ); ?>',
						signer_name: document.getElementById('signerName').value.trim(),
						signer_email: document.getElementById('signerEmail').value.trim(),
						signer_whatsapp: document.getElementById('signerWhatsapp').value.trim(),
						signer_instagram: document.getElementById('signerInstagram').value.trim(),
						signature_method: currentMethod,
						signature_data: currentMethod === 'canvas' ? {
							strokes: strokes,
							canvas_width: canvas.width,
							canvas_height: canvas.height
						} : {
							typed_name: document.getElementById('typedSignature').value.trim()
						},
						duration: Date.now() - startTime,
						screen_resolution: `${screen.width}x${screen.height}`,
						device_pixel_ratio: window.devicePixelRatio || 1
					};

					try {
						const response = await fetch('/wp-admin/admin-ajax.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: new URLSearchParams({
								action: 'apollo_process_local_signature',
								signature_data: JSON.stringify(signatureData),
								nonce: '<?php echo \wp_create_nonce( 'apollo_local_signature' ); ?>'
							})
						});

						const result = await response.json();

						if (result.success) {
							window.location.href = '/apollo/signature/success/' + result.data.signature_id;
						} else {
							alert('Erro: ' + (result.data.error || 'Erro desconhecido'));
						}

					} catch (error) {
						alert('Erro de conexão: ' + error.message);
					} finally {
						document.getElementById('loadingContainer').style.display = 'none';
					}
				}
			</script>
		</body>
		</html>
		<?php
	}

	/**
	 * Validate signature token
	 */
	private function validateToken( string $token ): ?array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'apollo_signature_requests';

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE token = %s AND expires_at > NOW() AND status = 'pending'",
				$token
			),
			ARRAY_A
		);

		return $request ?: null;
	}

	/**
	 * Render error page
	 */
	private function renderError( string $message ): void {
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Erro - Apollo Social</title>
		</head>
		<body style="font-family: system-ui; text-align: center; padding: 50px; color: #dc2626;">
			<h1>❌ Erro</h1>
			<p><?php echo htmlspecialchars( $message ); ?></p>
			<p><a href="/" style="color: #4f46e5;">← Voltar ao início</a></p>
		</body>
		</html>
		<?php
	}
}
