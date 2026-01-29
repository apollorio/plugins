<?php
/**
 * Classified Form Shortcode Template
 *
 * Renders the form for creating new classifieds.
 * Supports both domains: Ingressos (tickets) and Acomodação (accommodation).
 *
 * @package Apollo\Templates\Classifieds
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || exit;

use Apollo\Modules\Classifieds\ClassifiedsModule;
?>

<div class="classified-form-wrapper">
	<form class="classified-form" id="classified-create-form">
		<h2><?php esc_html_e( 'Criar Novo Anúncio', 'apollo-social' ); ?></h2>

		<!-- Step 1: Domain Selection -->
		<div class="form-section">
			<div class="form-section-title"><?php esc_html_e( 'Categoria', 'apollo-social' ); ?></div>

			<div class="domain-selector">
				<div class="domain-option">
					<input type="radio" name="domain" id="domain-ingressos" value="ingressos" />
					<label for="domain-ingressos">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
							<path d="M13 5v2"/>
							<path d="M13 17v2"/>
							<path d="M13 11v2"/>
						</svg>
						<span><?php esc_html_e( 'Ingressos', 'apollo-social' ); ?></span>
					</label>
				</div>

				<div class="domain-option">
					<input type="radio" name="domain" id="domain-acomodacao" value="acomodacao" />
					<label for="domain-acomodacao">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M3 21h18"/>
							<path d="M3 7v14"/>
							<path d="M21 7v14"/>
							<path d="M3 7l9-4 9 4"/>
							<rect x="7" y="11" width="4" height="6"/>
							<rect x="13" y="11" width="4" height="3"/>
						</svg>
						<span><?php esc_html_e( 'Acomodação', 'apollo-social' ); ?></span>
					</label>
				</div>
			</div>
		</div>

		<!-- Step 2: Intent Selection -->
		<div class="form-section">
			<div class="form-section-title"><?php esc_html_e( 'Tipo de Anúncio', 'apollo-social' ); ?></div>

			<div class="intent-selector">
				<div class="intent-option">
					<input type="radio" name="intent" id="intent-ofereco" value="ofereco" />
					<label for="intent-ofereco">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M12 2v4"/>
							<path d="M12 18v4"/>
							<path d="M4.93 4.93l2.83 2.83"/>
							<path d="M16.24 16.24l2.83 2.83"/>
							<path d="M2 12h4"/>
							<path d="M18 12h4"/>
							<path d="M4.93 19.07l2.83-2.83"/>
							<path d="M16.24 7.76l2.83-2.83"/>
						</svg>
						<span><?php esc_html_e( 'Ofereço', 'apollo-social' ); ?></span>
					</label>
				</div>

				<div class="intent-option">
					<input type="radio" name="intent" id="intent-procuro" value="procuro" />
					<label for="intent-procuro">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<line x1="21" y1="21" x2="16.65" y2="16.65"/>
						</svg>
						<span><?php esc_html_e( 'Procuro', 'apollo-social' ); ?></span>
					</label>
				</div>
			</div>
		</div>

		<!-- Step 3: Basic Info -->
		<div class="form-section">
			<div class="form-section-title"><?php esc_html_e( 'Informações Básicas', 'apollo-social' ); ?></div>

			<div class="form-group">
				<label for="title">
					<?php esc_html_e( 'Título do Anúncio', 'apollo-social' ); ?>
					<span class="required">*</span>
				</label>
				<input type="text" name="title" id="title" required maxlength="100" placeholder="<?php esc_attr_e( 'Ex: 2 ingressos para Lollapalooza - Pista', 'apollo-social' ); ?>" />
				<span class="help-text"><?php esc_html_e( 'Seja claro e específico no título.', 'apollo-social' ); ?></span>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="price"><?php esc_html_e( 'Preço (R$)', 'apollo-social' ); ?></label>
					<input type="number" name="price" id="price" min="0" step="0.01" placeholder="<?php esc_attr_e( '0,00', 'apollo-social' ); ?>" />
					<span class="help-text"><?php esc_html_e( 'Deixe em branco para "a combinar".', 'apollo-social' ); ?></span>
				</div>

				<div class="form-group">
					<label for="location"><?php esc_html_e( 'Local', 'apollo-social' ); ?></label>
					<input type="text" name="location" id="location" placeholder="<?php esc_attr_e( 'Ex: São Paulo, SP', 'apollo-social' ); ?>" />
				</div>
			</div>

			<div class="form-group">
				<label for="description"><?php esc_html_e( 'Descrição', 'apollo-social' ); ?></label>
				<textarea name="description" id="description" rows="5" placeholder="<?php esc_attr_e( 'Descreva os detalhes do seu anúncio...', 'apollo-social' ); ?>"></textarea>
			</div>
		</div>

		<!-- Conditional: Ingressos -->
		<div class="conditional-fields" data-domain="ingressos">
			<div class="form-section">
				<div class="form-section-title"><?php esc_html_e( 'Detalhes do Ingresso', 'apollo-social' ); ?></div>

				<div class="form-row">
					<div class="form-group">
						<label for="event_date">
							<?php esc_html_e( 'Data do Evento', 'apollo-social' ); ?>
							<span class="required">*</span>
						</label>
						<input type="date" name="event_date" id="event_date" />
					</div>

					<div class="form-group">
						<label for="event_title"><?php esc_html_e( 'Nome do Evento', 'apollo-social' ); ?></label>
						<input type="text" name="event_title" id="event_title" placeholder="<?php esc_attr_e( 'Ex: Rock in Rio 2025', 'apollo-social' ); ?>" />
					</div>
				</div>
			</div>
		</div>

		<!-- Conditional: Acomodação -->
		<div class="conditional-fields" data-domain="acomodacao">
			<div class="form-section">
				<div class="form-section-title"><?php esc_html_e( 'Detalhes da Acomodação', 'apollo-social' ); ?></div>

				<div class="form-row">
					<div class="form-group">
						<label for="start_date">
							<?php esc_html_e( 'Check-in', 'apollo-social' ); ?>
							<span class="required">*</span>
						</label>
						<input type="date" name="start_date" id="start_date" />
					</div>

					<div class="form-group">
						<label for="end_date">
							<?php esc_html_e( 'Check-out', 'apollo-social' ); ?>
							<span class="required">*</span>
						</label>
						<input type="date" name="end_date" id="end_date" />
					</div>
				</div>

				<div class="form-group">
					<label for="capacity"><?php esc_html_e( 'Capacidade (pessoas)', 'apollo-social' ); ?></label>
					<input type="number" name="capacity" id="capacity" min="1" max="50" placeholder="<?php esc_attr_e( 'Ex: 4', 'apollo-social' ); ?>" />
				</div>
			</div>
		</div>

		<!-- Step 4: Images -->
		<div class="form-section">
			<div class="form-section-title"><?php esc_html_e( 'Imagens', 'apollo-social' ); ?></div>

			<div class="form-group">
				<div class="image-upload-zone">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
						<circle cx="8.5" cy="8.5" r="1.5"/>
						<polyline points="21 15 16 10 5 21"/>
					</svg>
					<p><?php esc_html_e( 'Clique ou arraste para adicionar imagens', 'apollo-social' ); ?></p>
					<input type="file" name="images" accept="image/*" multiple style="display: none;" />
				</div>
				<div class="image-previews"></div>
				<span class="help-text"><?php esc_html_e( 'Até 5 imagens. JPEG, PNG ou WebP.', 'apollo-social' ); ?></span>
			</div>
		</div>

		<!-- Submit -->
		<div class="form-submit">
			<button type="submit" class="btn-submit">
				<?php esc_html_e( 'Publicar Anúncio', 'apollo-social' ); ?>
			</button>
		</div>

		<!-- Terms notice -->
		<p class="form-terms-notice">
			<?php
			printf(
				/* translators: %s: link to terms */
				esc_html__( 'Ao publicar, você concorda com nossos %s.', 'apollo-social' ),
				'<a href="/termos" target="_blank">' . esc_html__( 'Termos de Uso', 'apollo-social' ) . '</a>'
			);
			?>
		</p>
	</form>
</div>

<style>
	.form-terms-notice {
		text-align: center;
		margin-top: 1rem;
		font-size: 0.8125rem;
		color: #888;
	}

	.form-terms-notice a {
		color: #3498db;
		text-decoration: none;
	}

	.form-error {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 1rem;
		background: #fef3f2;
		border: 1px solid #fecaca;
		border-radius: 8px;
		color: #dc2626;
		margin-bottom: 1.5rem;
	}

	.form-error svg {
		flex-shrink: 0;
	}
</style>
