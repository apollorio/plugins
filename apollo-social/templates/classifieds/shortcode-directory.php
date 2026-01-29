<?php
/**
 * Classifieds Directory Shortcode Template
 *
 * Renders the filterable directory of classifieds.
 *
 * @package Apollo\Templates\Classifieds
 * @since 2.2.0
 */

defined( 'ABSPATH' ) || exit;

use Apollo\Modules\Classifieds\ClassifiedsModule;

// Shortcode attributes
$domain_filter = isset( $atts['domain'] ) ? sanitize_text_field( $atts['domain'] ) : '';
$intent_filter = isset( $atts['intent'] ) ? sanitize_text_field( $atts['intent'] ) : '';
$per_page      = isset( $atts['per_page'] ) ? absint( $atts['per_page'] ) : 12;

// Archive link
$archive_link = get_post_type_archive_link( ClassifiedsModule::POST_TYPE );
$create_link  = home_url( '/criar-anuncio/' );
?>

<div class="apollo-classifieds-directory" data-per-page="<?php echo esc_attr( $per_page ); ?>">
	<div class="container">
		<!-- Header -->
		<header class="classifieds-header">
			<h1><?php esc_html_e( 'Classificados', 'apollo-social' ); ?></h1>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( $create_link ); ?>" class="btn-create-ad">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="12" y1="5" x2="12" y2="19"/>
						<line x1="5" y1="12" x2="19" y2="12"/>
					</svg>
					<?php esc_html_e( 'Criar Anúncio', 'apollo-social' ); ?>
				</a>
			<?php endif; ?>
		</header>

		<!-- Filters -->
		<div class="classifieds-filters">
			<div class="filters-row">
				<!-- Search -->
				<div class="filter-group filter-group-search">
					<label for="filter-search"><?php esc_html_e( 'Buscar', 'apollo-social' ); ?></label>
					<div class="search-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<line x1="21" y1="21" x2="16.65" y2="16.65"/>
						</svg>
					</div>
					<input type="text" id="filter-search" data-filter="search" placeholder="<?php esc_attr_e( 'O que você procura?', 'apollo-social' ); ?>" />
				</div>

				<!-- Domain -->
				<?php if ( ! $domain_filter ) : ?>
					<div class="filter-group">
						<label for="filter-domain"><?php esc_html_e( 'Categoria', 'apollo-social' ); ?></label>
						<select id="filter-domain" data-filter="domain">
							<option value=""><?php esc_html_e( 'Todas', 'apollo-social' ); ?></option>
							<?php foreach ( ClassifiedsModule::DOMAINS as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<input type="hidden" data-filter="domain" value="<?php echo esc_attr( $domain_filter ); ?>" />
				<?php endif; ?>

				<!-- Intent -->
				<?php if ( ! $intent_filter ) : ?>
					<div class="filter-group">
						<label for="filter-intent"><?php esc_html_e( 'Tipo', 'apollo-social' ); ?></label>
						<select id="filter-intent" data-filter="intent">
							<option value=""><?php esc_html_e( 'Todos', 'apollo-social' ); ?></option>
							<?php foreach ( ClassifiedsModule::INTENTS as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php else : ?>
					<input type="hidden" data-filter="intent" value="<?php echo esc_attr( $intent_filter ); ?>" />
				<?php endif; ?>

				<!-- Location -->
				<div class="filter-group">
					<label for="filter-location"><?php esc_html_e( 'Local', 'apollo-social' ); ?></label>
					<input type="text" id="filter-location" data-filter="location" placeholder="<?php esc_attr_e( 'Cidade, Estado', 'apollo-social' ); ?>" />
				</div>

				<!-- Actions -->
				<div class="filter-actions">
					<button type="button" class="btn-filter-reset">
						<?php esc_html_e( 'Limpar', 'apollo-social' ); ?>
					</button>
				</div>
			</div>

			<!-- Date Filters (conditional) -->
			<div class="date-filters">
				<!-- For Ingressos: single date -->
				<div class="filter-ticket-date" style="display: none;">
					<div class="filter-group">
						<label for="filter-date-from"><?php esc_html_e( 'Data do Evento', 'apollo-social' ); ?></label>
						<input type="date" id="filter-date-from" data-filter="date_from" />
					</div>
					<div class="filter-group">
						<label for="filter-date-to"><?php esc_html_e( 'Até', 'apollo-social' ); ?></label>
						<input type="date" id="filter-date-to" data-filter="date_to" />
					</div>
				</div>

				<!-- For Acomodação: date range -->
				<div class="filter-accom-dates" style="display: none;">
					<div class="filter-group">
						<label for="filter-checkin"><?php esc_html_e( 'Check-in', 'apollo-social' ); ?></label>
						<input type="date" id="filter-checkin" data-filter="date_from" />
					</div>
					<div class="filter-group">
						<label for="filter-checkout"><?php esc_html_e( 'Check-out', 'apollo-social' ); ?></label>
						<input type="date" id="filter-checkout" data-filter="date_to" />
					</div>
				</div>
			</div>
		</div>

		<!-- Grid -->
		<div class="classifieds-grid">
			<!-- Content loaded via JavaScript -->
			<div class="classifieds-loading">
				<div class="loading-spinner"></div>
			</div>
		</div>

		<!-- Pagination -->
		<div class="classifieds-pagination"></div>
	</div>
</div>
