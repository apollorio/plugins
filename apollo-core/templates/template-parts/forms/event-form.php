<?php

declare(strict_types=1);
/**
 * Apollo Event Form Template
 *
 * Form for creating/editing events
 * Based on: form-new-event.html design
 *
 * @package Apollo_Core
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int|WP_Post $event  Event post for editing (optional)
 *     @type string      $mode   'create' or 'edit'
 * }
 */

defined('ABSPATH') || exit;

// Check if user can create events.
if (! is_user_logged_in()) {
	echo '<div class="ap-auth-required">Faça login para criar eventos.</div>';
	return;
}

$current_user_id = get_current_user_id();
$mode            = $args['mode'] ?? 'create';
$event           = $args['event'] ?? null;

// If editing, get event data.
$event_data = array(
	'title'       => '',
	'description' => '',
	'date'        => '',
	'time_start'  => '',
	'time_end'    => '',
	'venue'       => '',
	'address'     => '',
	'price'       => '',
	'price_type'  => 'free',
	'link'        => '',
	'genres'      => array(),
	'djs'         => array(),
	'community'   => '',
	'privacy'     => 'public',
	'cover_image' => '',
);

if ($event && 'edit' === $mode) {
	if (is_numeric($event)) {
		$event = get_post($event);
	}

	if ($event) {
		$event_data = array(
			'title'       => $event->post_title,
			'description' => $event->post_content,
			'date'        => get_post_meta($event->ID, '_event_date', true),
			'time_start'  => get_post_meta($event->ID, '_event_time', true),
			'time_end'    => get_post_meta($event->ID, '_event_time_end', true),
			'venue'       => get_post_meta($event->ID, '_event_venue', true),
			'address'     => get_post_meta($event->ID, '_event_address', true),
			'price'       => get_post_meta($event->ID, '_event_price', true),
			'price_type'  => get_post_meta($event->ID, '_event_price_type', true) ?: 'free',
			'link'        => get_post_meta($event->ID, '_event_link', true),
			'genres'      => wp_get_post_terms($event->ID, 'event_genre', array('fields' => 'ids')),
			'djs'         => (array) get_post_meta($event->ID, '_event_djs', true),
			'community'   => get_post_meta($event->ID, '_event_community', true),
			'privacy'     => get_post_meta($event->ID, '_event_privacy', true) ?: 'public',
			'cover_image' => get_the_post_thumbnail_url($event->ID, 'large'),
		);
	}
}

// Get available genres.
$genres = get_terms(array(
	'taxonomy'   => 'event_genre',
	'hide_empty' => false,
));

// Get DJs for autocomplete.
$djs = get_posts(array(
	'post_type'      => 'event_dj',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
));

// Get user's communities.
$communities = get_posts(array(
	'post_type'      => 'apollo_community',
	'posts_per_page' => -1,
	'meta_query'     => array(
		'relation' => 'OR',
		array(
			'key'     => '_community_members',
			'value'   => $current_user_id,
			'compare' => 'LIKE',
		),
		array(
			'key'   => '_community_owner',
			'value' => $current_user_id,
		),
	),
));

// Form nonce.
$nonce = wp_create_nonce('apollo_event_form');

?>
<div class="event-form-container">

	<header class="form-header">
		<a href="javascript:history.back()" class="back-link">
			<i class="i-arrow-left-v" aria-hidden="true"></i>
			Voltar
		</a>
		<h1 class="form-title">
			<?php echo 'edit' === $mode ? 'Editar Evento' : 'Criar Novo Evento'; ?>
		</h1>
		<p class="form-subtitle">
			Preencha as informações do seu evento. Campos com * são obrigatórios.
		</p>
	</header>

	<form id="apollo-event-form" class="event-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="apollo_save_event">
		<input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
		<input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
		<?php if ($event) : ?>
			<input type="hidden" name="event_id" value="<?php echo esc_attr($event->ID); ?>">
		<?php endif; ?>

		<!-- Cover Image Upload -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-image-v" aria-hidden="true"></i>
				Imagem de Capa
			</div>

			<div class="cover-upload-zone" id="coverUploadZone" title="Selecionar imagem de capa do evento">
				<?php if ($event_data['cover_image']) : ?>
					<img src="<?php echo esc_url($event_data['cover_image']); ?>" alt="Pré-visualização da capa do evento" class="cover-preview">
					<button type="button" class="cover-remove" title="Remover imagem de capa">
						<i class="i-close-v" aria-hidden="true"></i>
					</button>
				<?php else : ?>
					<div class="upload-placeholder">
						<i class="i-upload-cloud-v" aria-hidden="true"></i>
						<span>Arraste uma imagem ou clique para selecionar</span>
						<small>Recomendado: 1200x630px, JPG ou PNG</small>
					</div>
				<?php endif; ?>
				<label class="sr-only" for="coverImageInput">Imagem de capa do evento</label>
				<input type="file" name="cover_image" id="coverImageInput" accept="image/*" title="Selecionar imagem de capa" hidden>
			</div>
		</section>

		<!-- Basic Info -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-file-text-v" aria-hidden="true"></i>
				Informações Básicas
			</div>

			<div class="form-group">
				<label for="eventTitle">Título do Evento *</label>
				<input
					type="text"
					id="eventTitle"
					name="title"
					value="<?php echo esc_attr($event_data['title']); ?>"
					placeholder="Ex: Dismantle #4 - Techno Night"
					title="Título do evento"
					required
					maxlength="100">
				<span class="char-count"><span id="titleCount">0</span>/100</span>
			</div>

			<div class="form-group">
				<label for="eventDescription">Descrição *</label>
				<textarea
					id="eventDescription"
					name="description"
					rows="5"
					placeholder="Conte sobre o evento, o que esperar, dress code, etc..."
					title="Descrição do evento"
					required
					maxlength="2000"><?php echo esc_textarea($event_data['description']); ?></textarea>
				<span class="char-count"><span id="descCount">0</span>/2000</span>
			</div>
		</section>

		<!-- Date & Time -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-calendar-v" aria-hidden="true"></i>
				Data e Horário
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="eventDate">Data *</label>
					<input
						type="date"
						id="eventDate"
						name="date"
						value="<?php echo esc_attr($event_data['date']); ?>"
						title="Data do evento"
						required
						min="<?php echo date('Y-m-d'); ?>">
				</div>

				<div class="form-group">
					<label for="eventTimeStart">Hora Início *</label>
					<input
						type="time"
						id="eventTimeStart"
						name="time_start"
						value="<?php echo esc_attr($event_data['time_start']); ?>"
						title="Hora de início"
						required>
				</div>

				<div class="form-group">
					<label for="eventTimeEnd">Hora Fim</label>
					<input
						type="time"
						id="eventTimeEnd"
						name="time_end"
						value="<?php echo esc_attr($event_data['time_end']); ?>"
						title="Hora de término">
				</div>
			</div>
		</section>

		<!-- Location -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-map-pin-v" aria-hidden="true"></i>
				Localização
			</div>

			<div class="form-group">
				<label for="eventVenue">Nome do Local *</label>
				<input
					type="text"
					id="eventVenue"
					name="venue"
					value="<?php echo esc_attr($event_data['venue']); ?>"
					placeholder="Ex: Casa Estranha, Warehouse X, etc."
					title="Nome do local"
					required>
			</div>

			<div class="form-group">
				<label for="eventAddress">Endereço</label>
				<input
					type="text"
					id="eventAddress"
					name="address"
					value="<?php echo esc_attr($event_data['address']); ?>"
					placeholder="Rua, número, bairro - CEP"
					title="Endereço do evento">
				<small class="form-hint">Será exibido apenas para quem confirmar presença</small>
			</div>
		</section>

		<!-- Genres -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-music-2-v" aria-hidden="true"></i>
				Gêneros Musicais
			</div>

			<div class="genre-picker">
				<?php foreach ($genres as $genre) :
					$is_selected = in_array($genre->term_id, (array) $event_data['genres'], true);
					$genre_id    = 'eventGenre-' . $genre->term_id;
				?>
					<label class="genre-chip <?php echo $is_selected ? 'selected' : ''; ?>" for="<?php echo esc_attr($genre_id); ?>">
						<input
							type="checkbox"
							id="<?php echo esc_attr($genre_id); ?>"
							name="genres[]"
							value="<?php echo esc_attr($genre->term_id); ?>"
							title="<?php echo esc_attr($genre->name); ?>"
							<?php checked($is_selected); ?>>
						<?php echo esc_html($genre->name); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- DJs / Lineup -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-disc-v" aria-hidden="true"></i>
				Line-up (DJs)
			</div>

			<div class="dj-selector">
				<div class="dj-selected-list" id="djSelectedList">
					<?php foreach ((array) $event_data['djs'] as $dj_id) :
						$dj_post = get_post($dj_id);
						if (! $dj_post) continue;
					?>
						<div class="dj-chip" data-id="<?php echo esc_attr($dj_id); ?>">
							<?php echo esc_html($dj_post->post_title); ?>
							<button type="button" class="dj-remove" title="Remover DJ da lista">
								<i class="i-close-v" aria-hidden="true"></i>
							</button>
							<input type="hidden" name="djs[]" value="<?php echo esc_attr($dj_id); ?>">
						</div>
					<?php endforeach; ?>
				</div>

				<div class="dj-search-wrap">
					<label class="sr-only" for="djSearch">Buscar DJ pelo nome</label>
					<input
						type="text"
						id="djSearch"
						placeholder="Buscar DJ pelo nome..."
						title="Buscar DJ pelo nome"
						autocomplete="off">
					<div class="dj-dropdown" id="djDropdown">
						<?php foreach ($djs as $dj) : ?>
							<div class="dj-option" data-id="<?php echo esc_attr($dj->ID); ?>" data-name="<?php echo esc_attr($dj->post_title); ?>">
								<div class="dj-option-avatar">
									<?php echo get_the_post_thumbnail($dj->ID, 'thumbnail') ?: '<i class="i-disc-v" aria-hidden="true"></i>'; ?>
								</div>
								<?php echo esc_html($dj->post_title); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<small class="form-hint">Adicione os DJs que vão tocar no evento</small>
			</div>
		</section>

		<!-- Price -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-ticket-v" aria-hidden="true"></i>
				Entrada
			</div>

			<div class="price-type-selector">
				<label class="price-type-option <?php echo 'free' === $event_data['price_type'] ? 'selected' : ''; ?>" for="priceTypeFree">
					<input type="radio" id="priceTypeFree" name="price_type" value="free" title="Entrada gratuita" <?php checked($event_data['price_type'], 'free'); ?>>
					<i class="i-gift-v" aria-hidden="true"></i>
					<span>Gratuito</span>
				</label>
				<label class="price-type-option <?php echo 'paid' === $event_data['price_type'] ? 'selected' : ''; ?>" for="priceTypePaid">
					<input type="radio" id="priceTypePaid" name="price_type" value="paid" title="Entrada paga" <?php checked($event_data['price_type'], 'paid'); ?>>
					<i class="i-ticket-v" aria-hidden="true"></i>
					<span>Pago</span>
				</label>
				<label class="price-type-option <?php echo 'donation' === $event_data['price_type'] ? 'selected' : ''; ?>" for="priceTypeDonation">
					<input type="radio" id="priceTypeDonation" name="price_type" value="donation" title="Entrada por contribuição" <?php checked($event_data['price_type'], 'donation'); ?>>
					<i class="i-heart-v" aria-hidden="true"></i>
					<span>Contribuição</span>
				</label>
			</div>

			<div class="form-group price-input-group" id="priceInputGroup" style="<?php echo 'paid' !== $event_data['price_type'] ? 'display:none;' : ''; ?>">
				<label for="eventPrice">Valor da Entrada</label>
				<div class="price-input-wrap">
					<span class="price-currency">R$</span>
					<input
						type="number"
						id="eventPrice"
						name="price"
						value="<?php echo esc_attr($event_data['price']); ?>"
						placeholder="0,00"
						min="0"
						step="0.01"
						title="Valor da entrada">
				</div>
			</div>

			<div class="form-group">
				<label for="eventLink">Link para Ingressos/Informações</label>
				<input
					type="url"
					id="eventLink"
					name="link"
					value="<?php echo esc_url($event_data['link']); ?>"
					placeholder="https://sympla.com.br/seu-evento"
					title="Link de ingressos ou informações">
			</div>
		</section>

		<!-- Community -->
		<?php if (! empty($communities)) : ?>
			<section class="form-section">
				<div class="section-title">
					<i class="i-community-v" aria-hidden="true"></i>
					Comunidade
				</div>

				<div class="form-group">
					<label for="eventCommunity">Associar a uma Comunidade</label>
					<select id="eventCommunity" name="community" title="Selecionar comunidade">
						<option value="">-- Nenhuma --</option>
						<?php foreach ($communities as $comm) : ?>
							<option value="<?php echo esc_attr($comm->ID); ?>" <?php selected($event_data['community'], $comm->ID); ?>>
								<?php echo esc_html($comm->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</section>
		<?php endif; ?>

		<!-- Privacy -->
		<section class="form-section">
			<div class="section-title">
				<i class="i-eye-v" aria-hidden="true"></i>
				Visibilidade
			</div>

			<div class="privacy-selector">
				<label class="privacy-option <?php echo 'public' === $event_data['privacy'] ? 'selected' : ''; ?>" for="privacyPublic">
					<input type="radio" id="privacyPublic" name="privacy" value="public" title="Visibilidade pública" <?php checked($event_data['privacy'], 'public'); ?>>
					<div class="privacy-icon">
						<i class="i-global-v" aria-hidden="true"></i>
					</div>
					<div class="privacy-info">
						<strong>Público</strong>
						<span>Qualquer pessoa pode ver e interagir</span>
					</div>
				</label>

				<label class="privacy-option <?php echo 'private' === $event_data['privacy'] ? 'selected' : ''; ?>" for="privacyPrivate">
					<input type="radio" id="privacyPrivate" name="privacy" value="private" title="Visibilidade privada" <?php checked($event_data['privacy'], 'private'); ?>>
					<div class="privacy-icon">
						<i class="i-lock-v" aria-hidden="true"></i>
					</div>
					<div class="privacy-info">
						<strong>Privado</strong>
						<span>Apenas convidados podem ver</span>
					</div>
				</label>

				<label class="privacy-option <?php echo 'unlisted' === $event_data['privacy'] ? 'selected' : ''; ?>" for="privacyUnlisted">
					<input type="radio" id="privacyUnlisted" name="privacy" value="unlisted" title="Visibilidade não listada" <?php checked($event_data['privacy'], 'unlisted'); ?>>
					<div class="privacy-icon">
						<i class="i-link-v" aria-hidden="true"></i>
					</div>
					<div class="privacy-info">
						<strong>Não Listado</strong>
						<span>Acessível apenas via link direto</span>
					</div>
				</label>
			</div>
		</section>

		<!-- Form Actions -->
		<div class="form-actions">
			<button type="button" class="btn-secondary" id="saveDraft" title="Salvar rascunho do evento">
				<i class="i-save-v" aria-hidden="true"></i>
				Salvar Rascunho
			</button>
			<button type="submit" class="btn-primary" title="Publicar ou atualizar o evento">
				<i class="i-send-plane-v" aria-hidden="true"></i>
				<?php echo 'edit' === $mode ? 'Atualizar Evento' : 'Publicar Evento'; ?>
			</button>
		</div>

	</form>

</div>

<style>
	/* Event Form Styles */
	.event-form-container {
		max-width: 720px;
		margin: 0 auto;
		padding: 2rem 1.5rem 4rem;
	}

	.sr-only {
		position: absolute !important;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}

	/* Header */
	.form-header {
		margin-bottom: 2rem;
	}

	.back-link {
		display: inline-flex;
		align-items: center;
		gap: 0.35rem;
		font-size: 0.8rem;
		color: var(--ap-text-muted);
		margin-bottom: 1rem;
		transition: color 0.2s;
	}

	.back-link:hover {
		color: #f97316;
	}

	.form-title {
		font-size: 2rem;
		font-weight: 900;
		margin: 0;
		letter-spacing: -0.02em;
	}

	.form-subtitle {
		font-size: 0.95rem;
		color: var(--ap-text-muted);
		margin: 0.5rem 0 0;
	}

	/* Sections */
	.form-section {
		background: #fff;
		border-radius: 1.25rem;
		border: 1px solid var(--ap-border-default);
		padding: 1.5rem;
		margin-bottom: 1.25rem;
	}

	.section-title {
		font-size: 0.85rem;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		display: flex;
		align-items: center;
		gap: 0.5rem;
		margin-bottom: 1.25rem;
		padding-bottom: 0.75rem;
		border-bottom: 1px solid var(--ap-border-default);
	}

	.section-title i {
		color: #f97316;
	}

	/* Cover Upload */
	.cover-upload-zone {
		position: relative;
		border: 2px dashed var(--ap-border-default);
		border-radius: 1rem;
		overflow: hidden;
		cursor: pointer;
		transition: all 0.2s;
		aspect-ratio: 1200/630;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.cover-upload-zone:hover {
		border-color: #f97316;
		background: rgba(249, 115, 22, 0.02);
	}

	.cover-upload-zone.dragover {
		border-color: #f97316;
		background: rgba(249, 115, 22, 0.05);
	}

	.upload-placeholder {
		text-align: center;
		color: var(--ap-text-muted);
		padding: 2rem;
	}

	.upload-placeholder i {
		font-size: 2.5rem;
		margin-bottom: 0.75rem;
		display: block;
		opacity: 0.5;
	}

	.upload-placeholder span {
		display: block;
		font-size: 0.9rem;
		margin-bottom: 0.25rem;
	}

	.upload-placeholder small {
		font-size: 0.75rem;
		opacity: 0.75;
	}

	.cover-preview {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.cover-remove {
		position: absolute;
		top: 0.75rem;
		right: 0.75rem;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: rgba(0, 0, 0, 0.6);
		color: #fff;
		border: none;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: background 0.2s;
	}

	.cover-remove:hover {
		background: #ef4444;
	}

	/* Form Groups */
	.form-group {
		margin-bottom: 1rem;
		position: relative;
	}

	.form-group:last-child {
		margin-bottom: 0;
	}

	.form-group label {
		display: block;
		font-size: 0.8rem;
		font-weight: 600;
		margin-bottom: 0.5rem;
	}

	.form-group input,
	.form-group textarea,
	.form-group select {
		width: 100%;
		padding: 0.75rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		font-size: 0.95rem;
		transition: border-color 0.2s, box-shadow 0.2s;
		background: #fff;
	}

	.form-group input:focus,
	.form-group textarea:focus,
	.form-group select:focus {
		outline: none;
		border-color: #f97316;
		box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
	}

	.form-group textarea {
		resize: vertical;
		min-height: 100px;
	}

	.char-count {
		position: absolute;
		right: 0.75rem;
		bottom: 0.75rem;
		font-size: 0.7rem;
		color: var(--ap-text-muted);
	}

	.form-hint {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
		margin-top: 0.35rem;
	}

	/* Form Row */
	.form-row {
		display: grid;
		grid-template-columns: 1fr;
		gap: 1rem;
	}

	@media (min-width: 640px) {
		.form-row {
			grid-template-columns: 1.5fr 1fr 1fr;
		}
	}

	/* Genre Picker */
	.genre-picker {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
	}

	.genre-chip {
		display: inline-flex;
		align-items: center;
		padding: 0.4rem 0.85rem;
		border-radius: 999px;
		font-size: 0.8rem;
		border: 1px solid var(--ap-border-default);
		background: #fff;
		cursor: pointer;
		transition: all 0.2s;
	}

	.genre-chip input {
		display: none;
	}

	.genre-chip:hover {
		border-color: #f97316;
	}

	.genre-chip.selected {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	/* DJ Selector */
	.dj-selector {
		position: relative;
	}

	.dj-selected-list {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		margin-bottom: 0.75rem;
	}

	.dj-chip {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.35rem 0.5rem 0.35rem 0.75rem;
		background: #1e293b;
		color: #fff;
		border-radius: 999px;
		font-size: 0.8rem;
	}

	.dj-remove {
		width: 20px;
		height: 20px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.2);
		border: none;
		color: #fff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.6rem;
	}

	.dj-remove:hover {
		background: #ef4444;
	}

	.dj-search-wrap {
		position: relative;
	}

	.dj-search-wrap input {
		width: 100%;
		padding: 0.75rem 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		font-size: 0.9rem;
	}

	.dj-dropdown {
		position: absolute;
		top: 100%;
		left: 0;
		right: 0;
		background: #fff;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
		max-height: 240px;
		overflow-y: auto;
		z-index: 10;
		display: none;
	}

	.dj-dropdown.open {
		display: block;
	}

	.dj-option {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.65rem 1rem;
		cursor: pointer;
		transition: background 0.15s;
	}

	.dj-option:hover {
		background: var(--ap-bg-surface);
	}

	.dj-option-avatar {
		width: 36px;
		height: 36px;
		border-radius: 50%;
		overflow: hidden;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		color: var(--ap-text-muted);
	}

	.dj-option-avatar img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	/* Price Type */
	.price-type-selector {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 0.75rem;
		margin-bottom: 1rem;
	}

	.price-type-option {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.35rem;
		padding: 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		cursor: pointer;
		transition: all 0.2s;
		text-align: center;
	}

	.price-type-option input {
		display: none;
	}

	.price-type-option i {
		font-size: 1.5rem;
		color: var(--ap-text-muted);
	}

	.price-type-option span {
		font-size: 0.8rem;
		font-weight: 600;
	}

	.price-type-option:hover {
		border-color: #f97316;
	}

	.price-type-option.selected {
		background: #1e293b;
		color: #fff;
		border-color: #1e293b;
	}

	.price-type-option.selected i {
		color: #fb923c;
	}

	.price-input-wrap {
		display: flex;
		align-items: center;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		overflow: hidden;
	}

	.price-currency {
		padding: 0.75rem 1rem;
		background: var(--ap-bg-surface);
		font-weight: 600;
		border-right: 1px solid var(--ap-border-default);
	}

	.price-input-wrap input {
		border: none;
		border-radius: 0;
		flex: 1;
	}

	.price-input-wrap input:focus {
		box-shadow: none;
	}

	/* Privacy Selector */
	.privacy-selector {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.privacy-option {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		border: 1px solid var(--ap-border-default);
		border-radius: 0.75rem;
		cursor: pointer;
		transition: all 0.2s;
	}

	.privacy-option input {
		display: none;
	}

	.privacy-option:hover {
		border-color: #f97316;
	}

	.privacy-option.selected {
		border-color: #1e293b;
		background: rgba(30, 41, 59, 0.02);
	}

	.privacy-icon {
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: var(--ap-bg-surface);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.25rem;
		color: var(--ap-text-muted);
	}

	.privacy-option.selected .privacy-icon {
		background: #1e293b;
		color: #fff;
	}

	.privacy-info strong {
		display: block;
		font-size: 0.9rem;
	}

	.privacy-info span {
		display: block;
		font-size: 0.75rem;
		color: var(--ap-text-muted);
	}

	/* Form Actions */
	.form-actions {
		display: flex;
		gap: 1rem;
		justify-content: flex-end;
		padding-top: 1rem;
	}

	.btn-primary,
	.btn-secondary {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.85rem 1.5rem;
		border-radius: 0.75rem;
		font-size: 0.9rem;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}

	.btn-primary {
		background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
		color: #fff;
		border: none;
	}

	.btn-primary:hover {
		transform: translateY(-1px);
		box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
	}

	.btn-secondary {
		background: #fff;
		color: var(--ap-text-default);
		border: 1px solid var(--ap-border-default);
	}

	.btn-secondary:hover {
		background: var(--ap-bg-surface);
	}

	/* Dark Mode */
	body.dark-mode .form-section {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .form-group input,
	body.dark-mode .form-group textarea,
	body.dark-mode .form-group select,
	body.dark-mode .dj-search-wrap input {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
		color: var(--ap-text-default);
	}

	body.dark-mode .genre-chip,
	body.dark-mode .price-type-option,
	body.dark-mode .privacy-option {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .dj-dropdown {
		background: var(--ap-bg-card);
		border-color: var(--ap-border-default);
	}

	body.dark-mode .btn-secondary {
		background: var(--ap-bg-surface);
		border-color: var(--ap-border-default);
		color: var(--ap-text-default);
	}
</style>

<script>
	(function() {
		const form = document.getElementById('apollo-event-form');
		if (!form) return;

		// Cover image upload
		const coverZone = document.getElementById('coverUploadZone');
		const coverInput = document.getElementById('coverImageInput');

		if (coverZone && coverInput) {
			coverZone.addEventListener('click', () => coverInput.click());

			coverZone.addEventListener('dragover', (e) => {
				e.preventDefault();
				coverZone.classList.add('dragover');
			});

			coverZone.addEventListener('dragleave', () => {
				coverZone.classList.remove('dragover');
			});

			coverZone.addEventListener('drop', (e) => {
				e.preventDefault();
				coverZone.classList.remove('dragover');
				const file = e.dataTransfer.files[0];
				if (file && file.type.startsWith('image/')) {
					handleCoverFile(file);
				}
			});

			coverInput.addEventListener('change', (e) => {
				if (e.target.files[0]) {
					handleCoverFile(e.target.files[0]);
				}
			});

			function handleCoverFile(file) {
				const reader = new FileReader();
				reader.onload = (e) => {
					coverZone.innerHTML = `
                    <img src="${e.target.result}" alt="" class="cover-preview">
                    <button type="button" class="cover-remove">
                        <i class="i-close-v" aria-hidden="true"></i>
                    </button>
                `;
					coverZone.querySelector('.cover-remove').addEventListener('click', (ev) => {
						ev.stopPropagation();
						coverInput.value = '';
						coverZone.innerHTML = `
                        <div class="upload-placeholder">
                            <i class="i-upload-cloud-v" aria-hidden="true"></i>
                            <span>Arraste uma imagem ou clique para selecionar</span>
                            <small>Recomendado: 1200x630px, JPG ou PNG</small>
                        </div>
                    `;
					});
				};
				reader.readAsDataURL(file);
			}
		}

		// Character counters
		const titleInput = document.getElementById('eventTitle');
		const titleCount = document.getElementById('titleCount');
		const descInput = document.getElementById('eventDescription');
		const descCount = document.getElementById('descCount');

		if (titleInput && titleCount) {
			titleCount.textContent = titleInput.value.length;
			titleInput.addEventListener('input', () => {
				titleCount.textContent = titleInput.value.length;
			});
		}

		if (descInput && descCount) {
			descCount.textContent = descInput.value.length;
			descInput.addEventListener('input', () => {
				descCount.textContent = descInput.value.length;
			});
		}

		// Genre chips
		form.querySelectorAll('.genre-chip input').forEach(input => {
			input.addEventListener('change', function() {
				this.closest('.genre-chip').classList.toggle('selected', this.checked);
			});
		});

		// DJ selector
		const djSearch = document.getElementById('djSearch');
		const djDropdown = document.getElementById('djDropdown');
		const djSelectedList = document.getElementById('djSelectedList');

		if (djSearch && djDropdown && djSelectedList) {
			djSearch.addEventListener('focus', () => {
				djDropdown.classList.add('open');
			});

			djSearch.addEventListener('input', function() {
				const query = this.value.toLowerCase();
				djDropdown.querySelectorAll('.dj-option').forEach(opt => {
					const name = opt.dataset.name.toLowerCase();
					opt.style.display = name.includes(query) ? 'flex' : 'none';
				});
			});

			document.addEventListener('click', (e) => {
				if (!e.target.closest('.dj-selector')) {
					djDropdown.classList.remove('open');
				}
			});

			djDropdown.querySelectorAll('.dj-option').forEach(opt => {
				opt.addEventListener('click', function() {
					const id = this.dataset.id;
					const name = this.dataset.name;

					// Check if already selected
					if (djSelectedList.querySelector(`[data-id="${id}"]`)) return;

					// Add chip
					const chip = document.createElement('div');
					chip.className = 'dj-chip';
					chip.dataset.id = id;
					chip.innerHTML = `
                    ${name}
                    <button type="button" class="dj-remove">
                        <i class="i-close-v" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" name="djs[]" value="${id}">
                `;
					djSelectedList.appendChild(chip);

					chip.querySelector('.dj-remove').addEventListener('click', () => {
						chip.remove();
					});

					djSearch.value = '';
					djDropdown.classList.remove('open');
				});
			});

			// Remove existing chips
			djSelectedList.querySelectorAll('.dj-remove').forEach(btn => {
				btn.addEventListener('click', function() {
					this.closest('.dj-chip').remove();
				});
			});
		}

		// Price type
		const priceTypeOptions = form.querySelectorAll('.price-type-option');
		const priceInputGroup = document.getElementById('priceInputGroup');

		priceTypeOptions.forEach(opt => {
			opt.querySelector('input').addEventListener('change', function() {
				priceTypeOptions.forEach(o => o.classList.remove('selected'));
				this.closest('.price-type-option').classList.add('selected');
				priceInputGroup.style.display = this.value === 'paid' ? 'block' : 'none';
			});
		});

		// Privacy selector
		const privacyOptions = form.querySelectorAll('.privacy-option');
		privacyOptions.forEach(opt => {
			opt.querySelector('input').addEventListener('change', function() {
				privacyOptions.forEach(o => o.classList.remove('selected'));
				this.closest('.privacy-option').classList.add('selected');
			});
		});

		// Form submission
		form.addEventListener('submit', async function(e) {
			e.preventDefault();

			const formData = new FormData(form);
			const submitBtn = form.querySelector('button[type="submit"]');
			const originalText = submitBtn.innerHTML;

			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i class="i-loader-4-v" aria-hidden="true"></i> Salvando...';

			try {
				if (typeof apolloAjax !== 'undefined') {
					const response = await fetch(apolloAjax.ajaxurl, {
						method: 'POST',
						body: formData
					});
					const data = await response.json();

					if (data.success) {
						window.location.href = data.data.redirect || '/events/';
					} else {
						alert(data.data.message || 'Erro ao salvar evento');
						submitBtn.disabled = false;
						submitBtn.innerHTML = originalText;
					}
				}
			} catch (error) {
				console.error('Error:', error);
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalText;
			}
		});

		// Save draft
		const saveDraftBtn = document.getElementById('saveDraft');
		if (saveDraftBtn) {
			saveDraftBtn.addEventListener('click', async function() {
				const formData = new FormData(form);
				formData.append('status', 'draft');

				this.disabled = true;
				this.innerHTML = '<i class="i-loader-4-v" aria-hidden="true"></i> Salvando...';

				try {
					if (typeof apolloAjax !== 'undefined') {
						const response = await fetch(apolloAjax.ajaxurl, {
							method: 'POST',
							body: formData
						});
						const data = await response.json();

						if (data.success) {
							alert('Rascunho salvo!');
						}
					}
				} catch (error) {
					console.error('Error:', error);
				}

				this.disabled = false;
				this.innerHTML = '<i class="i-save-v" aria-hidden="true"></i> Salvar Rascunho';
			});
		}
	})();
</script>
