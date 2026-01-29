<?php
/**
 * Safety Modal Partial
 *
 * Trust-forward modal that gates contact actions.
 * Displays security tips before allowing chat initiation.
 *
 * @package Apollo\Templates\Classifieds
 * @since 2.2.0
 *
 * @param int $post_id The classified post ID.
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $post_id ) ? (int) $post_id : 0;
?>

<div id="safety-modal" class="apollo-modal" role="dialog" aria-modal="true" aria-labelledby="safety-modal-title" data-post-id="<?php echo esc_attr( $post_id ); ?>">
	<div class="apollo-modal-backdrop"></div>
	<div class="apollo-modal-content">
		<div class="apollo-modal-header">
			<div class="apollo-modal-icon">
				<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#e67e22" stroke-width="2">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
					<line x1="12" y1="8" x2="12" y2="12"/>
					<line x1="12" y1="16" x2="12.01" y2="16"/>
				</svg>
			</div>
			<h2 id="safety-modal-title"><?php esc_html_e( 'Dicas de Segurança', 'apollo-social' ); ?></h2>
		</div>

		<div class="apollo-modal-body">
			<p class="apollo-modal-intro">
				<?php esc_html_e( 'Antes de prosseguir, leia estas dicas importantes:', 'apollo-social' ); ?>
			</p>

			<ul class="safety-tips">
				<li>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#27ae60" stroke-width="2">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
					<span>
						<strong><?php esc_html_e( 'Nunca pague antecipado', 'apollo-social' ); ?></strong>
						<?php esc_html_e( 'sem verificar a autenticidade do vendedor e do produto.', 'apollo-social' ); ?>
					</span>
				</li>
				<li>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#27ae60" stroke-width="2">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
					<span>
						<strong><?php esc_html_e( 'Prefira encontros presenciais', 'apollo-social' ); ?></strong>
						<?php esc_html_e( 'em locais públicos e movimentados.', 'apollo-social' ); ?>
					</span>
				</li>
				<li>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#27ae60" stroke-width="2">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
					<span>
						<strong><?php esc_html_e( 'Verifique os ingressos', 'apollo-social' ); ?></strong>
						<?php esc_html_e( 'através dos canais oficiais do evento antes de comprar.', 'apollo-social' ); ?>
					</span>
				</li>
				<li>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#27ae60" stroke-width="2">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
					<span>
						<strong><?php esc_html_e( 'Desconfie de preços', 'apollo-social' ); ?></strong>
						<?php esc_html_e( 'muito abaixo ou muito acima do mercado.', 'apollo-social' ); ?>
					</span>
				</li>
				<li>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#27ae60" stroke-width="2">
						<polyline points="20 6 9 17 4 12"/>
					</svg>
					<span>
						<strong><?php esc_html_e( 'Use o chat interno', 'apollo-social' ); ?></strong>
						<?php esc_html_e( 'para manter registro das conversas.', 'apollo-social' ); ?>
					</span>
				</li>
			</ul>

			<div class="safety-disclaimer">
				<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#e74c3c" stroke-width="2">
					<circle cx="12" cy="12" r="10"/>
					<line x1="12" y1="8" x2="12" y2="12"/>
					<line x1="12" y1="16" x2="12.01" y2="16"/>
				</svg>
				<p>
					<strong><?php esc_html_e( 'A Apollo Social não processa pagamentos.', 'apollo-social' ); ?></strong><br>
					<?php esc_html_e( 'Qualquer transação financeira ocorre externamente à plataforma e é de responsabilidade das partes envolvidas.', 'apollo-social' ); ?>
				</p>
			</div>
		</div>

		<div class="apollo-modal-footer">
			<button type="button" class="btn-secondary" id="safety-modal-cancel">
				<?php esc_html_e( 'Cancelar', 'apollo-social' ); ?>
			</button>
			<button type="button" class="btn-primary" id="safety-modal-confirm">
				<?php esc_html_e( 'Entendi e continuar', 'apollo-social' ); ?>
			</button>
		</div>
	</div>
</div>

<style>
	/* Apollo Modal Base Styles */
	.apollo-modal {
		display: none;
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 99999;
		align-items: center;
		justify-content: center;
	}

	.apollo-modal.active {
		display: flex;
	}

	.apollo-modal-backdrop {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
		backdrop-filter: blur(2px);
	}

	.apollo-modal-content {
		position: relative;
		background: #fff;
		border-radius: 16px;
		max-width: 520px;
		width: 90%;
		max-height: 90vh;
		overflow-y: auto;
		animation: apolloModalSlideIn 0.3s ease;
		box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
	}

	@keyframes apolloModalSlideIn {
		from {
			opacity: 0;
			transform: translateY(-20px) scale(0.95);
		}
		to {
			opacity: 1;
			transform: translateY(0) scale(1);
		}
	}

	.apollo-modal-header {
		padding: 2rem 2rem 1rem;
		text-align: center;
	}

	.apollo-modal-icon {
		margin-bottom: 1rem;
	}

	.apollo-modal-header h2 {
		font-size: 1.5rem;
		font-weight: 700;
		margin: 0;
		color: #1a1a1a;
	}

	.apollo-modal-body {
		padding: 0 2rem 1.5rem;
	}

	.apollo-modal-intro {
		text-align: center;
		color: #666;
		margin-bottom: 1.5rem;
	}

	/* Safety Tips */
	.safety-tips {
		list-style: none;
		padding: 0;
		margin: 0 0 1.5rem;
	}

	.safety-tips li {
		display: flex;
		gap: 0.75rem;
		padding: 0.875rem 0;
		border-bottom: 1px solid #f0f0f0;
		line-height: 1.5;
	}

	.safety-tips li:last-child {
		border-bottom: none;
	}

	.safety-tips svg {
		flex-shrink: 0;
		margin-top: 0.125rem;
	}

	.safety-tips strong {
		color: #1a1a1a;
	}

	/* Disclaimer */
	.safety-disclaimer {
		display: flex;
		gap: 1rem;
		padding: 1rem;
		background: #fef3f2;
		border: 1px solid #fecaca;
		border-radius: 8px;
		align-items: flex-start;
	}

	.safety-disclaimer svg {
		flex-shrink: 0;
	}

	.safety-disclaimer p {
		margin: 0;
		font-size: 0.875rem;
		color: #b91c1c;
		line-height: 1.5;
	}

	/* Footer */
	.apollo-modal-footer {
		padding: 1rem 2rem 2rem;
		display: flex;
		gap: 1rem;
		justify-content: flex-end;
	}

	.apollo-modal-footer .btn-secondary {
		padding: 0.75rem 1.5rem;
		background: #f1f1f1;
		color: #333;
		border: none;
		border-radius: 8px;
		font-size: 0.9375rem;
		font-weight: 500;
		cursor: pointer;
		transition: background 0.2s;
	}

	.apollo-modal-footer .btn-secondary:hover {
		background: #e5e5e5;
	}

	.apollo-modal-footer .btn-primary {
		padding: 0.75rem 1.5rem;
		background: #3498db;
		color: #fff;
		border: none;
		border-radius: 8px;
		font-size: 0.9375rem;
		font-weight: 600;
		cursor: pointer;
		transition: background 0.2s;
	}

	.apollo-modal-footer .btn-primary:hover {
		background: #2980b9;
	}

	/* Responsive */
	@media (max-width: 600px) {
		.apollo-modal-content {
			margin: 1rem;
			width: calc(100% - 2rem);
		}

		.apollo-modal-header,
		.apollo-modal-body,
		.apollo-modal-footer {
			padding-left: 1.25rem;
			padding-right: 1.25rem;
		}

		.apollo-modal-footer {
			flex-direction: column-reverse;
		}

		.apollo-modal-footer button {
			width: 100%;
		}
	}
</style>
