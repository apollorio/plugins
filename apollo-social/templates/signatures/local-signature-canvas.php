<?php
/**
 * Local Signature Canvas Template
 * STRICT MODE: 100% UNI.CSS compliance
 * Signature capture interface for internal Apollo Social documents
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue global assets
if ( function_exists( 'apollo_enqueue_global_assets' ) ) {
	apollo_enqueue_global_assets();
}
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', array(), '4.7.0' );

// Get signer email from URL if provided
$signer_email = isset( $_GET['signer_email'] ) ? sanitize_email( wp_unslash( $_GET['signer_email'] ) ) : '';
$template_id  = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

get_header();
?>

<!-- STRICT MODE: Local Signature Canvas - UNI.CSS v5.2.0 -->
<div class="ap-page ap-bg-gradient-primary ap-min-h-screen ap-flex ap-items-center ap-justify-center ap-py-6">
	<div class="ap-container ap-max-w-3xl">
		
		<div class="ap-card ap-shadow-lg">
			<!-- Header -->
			<div class="ap-card-header ap-bg-gradient-primary ap-text-white ap-text-center ap-py-8">
				<h1 class="ap-heading-xl ap-flex ap-items-center ap-justify-center ap-gap-2">
					<i class="ri-edit-2-line"></i>
					<?php esc_html_e( 'Assinatura Local', 'apollo-social' ); ?>
				</h1>
				<p class="ap-text-white-80 ap-mt-2">
					<?php esc_html_e( 'Sistema de assinatura interno Apollo Social', 'apollo-social' ); ?>
				</p>
			</div>

			<div class="ap-card-body ap-p-8">
				<!-- Error/Success Messages -->
				<div id="errorContainer"></div>
				<div id="successContainer"></div>

				<!-- Signer Information -->
				<section class="ap-mb-8">
					<h3 class="ap-heading-md ap-flex ap-items-center ap-gap-2 ap-mb-4">
						<i class="ri-user-line ap-text-primary"></i>
						<?php esc_html_e( 'Informações do Signatário', 'apollo-social' ); ?>
					</h3>
					
					<div class="ap-space-y-4">
						<div class="ap-form-group">
							<label for="signerName" class="ap-form-label">
								<?php esc_html_e( 'Nome Completo', 'apollo-social' ); ?> *
							</label>
							<input type="text" id="signerName" class="ap-form-input" required 
									placeholder="<?php esc_attr_e( 'Digite seu nome completo', 'apollo-social' ); ?>"
									data-ap-tooltip="<?php esc_attr_e( 'Nome como aparecerá no documento assinado', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="signerEmail" class="ap-form-label">
								<?php esc_html_e( 'Email', 'apollo-social' ); ?> *
							</label>
							<input type="email" id="signerEmail" class="ap-form-input" required 
									placeholder="<?php esc_attr_e( 'Digite seu email', 'apollo-social' ); ?>"
									value="<?php echo esc_attr( $signer_email ); ?>"
									data-ap-tooltip="<?php esc_attr_e( 'Email para confirmação da assinatura', 'apollo-social' ); ?>">
						</div>

						<div class="ap-form-group">
							<label for="signerDocument" class="ap-form-label">
								<?php esc_html_e( 'Documento (opcional)', 'apollo-social' ); ?>
							</label>
							<input type="text" id="signerDocument" class="ap-form-input" 
									placeholder="<?php esc_attr_e( 'CPF, RG ou outro documento', 'apollo-social' ); ?>"
									data-ap-tooltip="<?php esc_attr_e( 'Documento para verificação adicional', 'apollo-social' ); ?>">
						</div>
					</div>
				</section>

				<!-- Signature Area -->
				<section class="ap-mb-8">
					<h3 class="ap-heading-md ap-flex ap-items-center ap-gap-2 ap-mb-4">
						<i class="ri-pen-nib-line ap-text-primary"></i>
						<?php esc_html_e( 'Área de Assinatura', 'apollo-social' ); ?>
					</h3>
					
					<div class="ap-alert ap-alert-info ap-mb-4">
						<h4 class="ap-flex ap-items-center ap-gap-2 ap-font-semibold ap-mb-2">
							<i class="ri-shield-check-line"></i>
							<?php esc_html_e( 'Informações de Segurança', 'apollo-social' ); ?>
						</h4>
						<ul class="ap-list-disc ap-list-inside ap-text-sm ap-space-y-1">
							<li><?php esc_html_e( 'Sua assinatura será capturada como evidência digital', 'apollo-social' ); ?></li>
							<li><?php esc_html_e( 'Geramos hash SHA-256 para verificação de integridade', 'apollo-social' ); ?></li>
							<li><?php esc_html_e( 'Timestamp e IP são registrados para auditoria', 'apollo-social' ); ?></li>
							<li><?php esc_html_e( 'Certificado local com validade de 10 anos', 'apollo-social' ); ?></li>
						</ul>
					</div>

					<div class="ap-signature-canvas-wrapper ap-border-2 ap-border-dashed ap-rounded-xl ap-p-5 ap-bg-muted ap-text-center" id="canvasSection">
						<p class="ap-text-muted ap-mb-3 ap-flex ap-items-center ap-justify-center ap-gap-2">
							<i class="ri-hand-coin-line"></i>
							<?php esc_html_e( 'Desenhe sua assinatura no quadro abaixo', 'apollo-social' ); ?>
						</p>
						<canvas id="signatureCanvas" 
								class="ap-signature-canvas ap-border ap-rounded-lg ap-bg-white ap-cursor-crosshair"
								width="600" height="200"
								data-ap-tooltip="<?php esc_attr_e( 'Clique e arraste para desenhar', 'apollo-social' ); ?>"></canvas>
						
						<div class="ap-flex ap-justify-center ap-gap-4 ap-mt-4">
							<button type="button" class="ap-btn ap-btn-outline" onclick="clearSignature()"
									data-ap-tooltip="<?php esc_attr_e( 'Limpar assinatura', 'apollo-social' ); ?>">
								<i class="ri-delete-bin-line"></i>
								<?php esc_html_e( 'Limpar', 'apollo-social' ); ?>
							</button>
							<button type="button" class="ap-btn ap-btn-secondary" onclick="undoLastStroke()"
									data-ap-tooltip="<?php esc_attr_e( 'Desfazer último traço', 'apollo-social' ); ?>">
								<i class="ri-arrow-go-back-line"></i>
								<?php esc_html_e( 'Desfazer', 'apollo-social' ); ?>
							</button>
						</div>
					</div>
				</section>

				<!-- Action Buttons -->
				<div class="ap-flex ap-justify-center ap-gap-4">
					<button type="button" class="ap-btn ap-btn-primary ap-btn-lg" onclick="processSignature()"
							data-ap-tooltip="<?php esc_attr_e( 'Processar e salvar assinatura', 'apollo-social' ); ?>">
						<i class="ri-checkbox-circle-line"></i>
						<?php esc_html_e( 'Processar Assinatura', 'apollo-social' ); ?>
					</button>
					<a href="javascript:history.back()" class="ap-btn ap-btn-outline ap-btn-lg">
						<i class="ri-arrow-left-line"></i>
						<?php esc_html_e( 'Voltar', 'apollo-social' ); ?>
					</a>
				</div>

				<!-- Loading State -->
				<div id="loadingContainer" class="ap-text-center ap-py-8" style="display: none;">
					<div class="ap-spinner ap-spinner-lg ap-mx-auto ap-mb-4"></div>
					<p class="ap-text-muted"><?php esc_html_e( 'Processando assinatura...', 'apollo-social' ); ?></p>
				</div>
			</div>
		</div>
		
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Canvas setup
	const canvas = document.getElementById('signatureCanvas');
	const ctx = canvas.getContext('2d');
	let isDrawing = false;
	let strokes = [];
	let currentStroke = [];
	let startTime = Date.now();

	// Setup canvas
	ctx.strokeStyle = '#000000';
	ctx.lineWidth = 2;
	ctx.lineCap = 'round';
	ctx.lineJoin = 'round';

	// Mouse events
	canvas.addEventListener('mousedown', startDrawing);
	canvas.addEventListener('mousemove', draw);
	canvas.addEventListener('mouseup', stopDrawing);
	canvas.addEventListener('mouseleave', stopDrawing);

	// Touch events
	canvas.addEventListener('touchstart', handleTouch, { passive: false });
	canvas.addEventListener('touchmove', handleTouch, { passive: false });
	canvas.addEventListener('touchend', handleTouch, { passive: false });

	function startDrawing(e) {
		isDrawing = true;
		currentStroke = [];
		
		const rect = canvas.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		
		ctx.beginPath();
		ctx.moveTo(x, y);
		currentStroke.push({x, y, timestamp: Date.now()});
		
		document.getElementById('canvasSection').classList.add('ap-signature-canvas-active');
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
		if (currentStroke.length > 0) {
			strokes.push([...currentStroke]);
		}
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

	window.clearSignature = function() {
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		strokes = [];
		currentStroke = [];
		document.getElementById('canvasSection').classList.remove('ap-signature-canvas-active');
	};

	window.undoLastStroke = function() {
		if (strokes.length === 0) return;
		
		strokes.pop();
		redrawCanvas();
	};

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

	function showError(message) {
		document.getElementById('errorContainer').innerHTML = 
			`<div class="ap-alert ap-alert-error ap-mb-4">
				<i class="ri-error-warning-line"></i> ${message}
			</div>`;
	}

	function showSuccess(message) {
		document.getElementById('successContainer').innerHTML = 
			`<div class="ap-alert ap-alert-success ap-mb-4">
				<i class="ri-checkbox-circle-line"></i> ${message}
			</div>`;
	}

	function clearMessages() {
		document.getElementById('errorContainer').innerHTML = '';
		document.getElementById('successContainer').innerHTML = '';
	}

	window.processSignature = async function() {
		clearMessages();
		
		// Validate form
		const name = document.getElementById('signerName').value.trim();
		const email = document.getElementById('signerEmail').value.trim();
		
		if (!name) {
			showError('<?php echo esc_js( __( 'Nome é obrigatório', 'apollo-social' ) ); ?>');
			return;
		}
		
		if (!email) {
			showError('<?php echo esc_js( __( 'Email é obrigatório', 'apollo-social' ) ); ?>');
			return;
		}
		
		if (strokes.length === 0) {
			showError('<?php echo esc_js( __( 'Por favor, desenhe sua assinatura', 'apollo-social' ) ); ?>');
			return;
		}
		
		// Show loading
		document.getElementById('loadingContainer').style.display = 'block';
		
		const signatureData = {
			signer_name: name,
			signer_email: email,
			signer_document: document.getElementById('signerDocument').value.trim(),
			stroke_data: strokes,
			canvas_width: canvas.width,
			canvas_height: canvas.height,
			duration: Date.now() - startTime,
			screen_resolution: `${screen.width}x${screen.height}`,
			device_pixel_ratio: window.devicePixelRatio || 1,
			template_id: '<?php echo esc_js( $template_id ); ?>'
		};
		
		try {
			const response = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'apollo_process_local_signature',
					signature_data: JSON.stringify(signatureData),
					nonce: '<?php echo esc_js( wp_create_nonce( 'apollo_local_signature' ) ); ?>'
				})
			});
			
			const result = await response.json();
			
			if (result.success) {
				showSuccess('<?php echo esc_js( __( 'Assinatura processada com sucesso!', 'apollo-social' ) ); ?>');
				
				const certificate = result.data.certificate;
				setTimeout(() => {
					alert(`✅ <?php echo esc_js( __( 'Assinatura concluída!', 'apollo-social' ) ); ?>\n\n<?php echo esc_js( __( 'Certificado:', 'apollo-social' ) ); ?> ${certificate.certificate_id}\n<?php echo esc_js( __( 'Verificação:', 'apollo-social' ) ); ?> ${certificate.verification_url}`);
					
					if (window.opener) {
						window.close();
					} else {
						window.location.href = '<?php echo esc_url( home_url( '/docs/' ) ); ?>';
					}
				}, 1000);
				
			} else {
				showError(result.data.errors ? result.data.errors.join(', ') : '<?php echo esc_js( __( 'Erro desconhecido', 'apollo-social' ) ); ?>');
			}
			
		} catch (error) {
			showError('<?php echo esc_js( __( 'Erro ao processar assinatura:', 'apollo-social' ) ); ?> ' + error.message);
		} finally {
			document.getElementById('loadingContainer').style.display = 'none';
		}
	};
});
</script>

<?php get_footer(); ?>
