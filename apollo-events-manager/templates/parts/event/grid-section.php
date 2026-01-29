<?php
/**
 * Template: Events Grid Section - Official Apollo Design
 * ======================================================
 * Path: apollo-events-manager/templates/parts/event/grid-section.php
 *
 * Full events section with header, filters, and grid.
 * Matches the home page design from the Apollo CDN reference.
 *
 * CONTEXT CONTRACT (Optional Variables):
 * --------------------------------------
 * @var string $section_title    Section heading (default: "Eventos")
 * @var string $section_subtitle Section subheading
 * @var array  $events           Pre-loaded events array (or will query)
 * @var int    $count            Number of events to show (default: 8)
 * @var bool   $show_filters     Show filter dropdowns (default: true)
 * @var bool   $show_search      Show search input (default: true)
 * @var string $season           Filter by season slug
 * @var string $sounds           Filter by sounds/genres (comma-separated)
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// DEFAULT VALUES
// =============================================================================

$section_title    = $section_title ?? __( 'Eventos', 'apollo-events-manager' );
$section_subtitle = $section_subtitle ?? __( 'Acompanhe a pulsação da cidade.', 'apollo-events-manager' );
$count            = $count ?? 8;
$show_filters     = $show_filters ?? true;
$show_search      = $show_search ?? true;
$season           = $season ?? '';
$sounds           = $sounds ?? '';

// =============================================================================
// QUERY EVENTS IF NOT PROVIDED
// =============================================================================

if ( ! isset( $events ) || empty( $events ) ) {
	$query_args = array(
		'posts_per_page' => $count,
	);

	if ( $season ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'event_season',
			'field'    => 'slug',
			'terms'    => explode( ',', $season ),
		);
	}

	if ( $sounds ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'event_sounds',
			'field'    => 'slug',
			'terms'    => explode( ',', $sounds ),
		);
	}

	$events_query = apollo_get_events_for_grid( $query_args );
	$events       = $events_query->posts;
}

// =============================================================================
// GET FILTER OPTIONS
// =============================================================================

$seasons_terms = get_terms( array(
	'taxonomy'   => 'event_season',
	'hide_empty' => true,
) );

$sounds_terms = get_terms( array(
	'taxonomy'   => 'event_sounds',
	'hide_empty' => true,
) );

?>
<section id="events" class="events container" style="padding-left: 24px; padding-right: 24px;">

	<!-- Header with Filters -->
	<div class="events-header reveal-up">
		<div class="events-title">
			<h2><?php echo esc_html( $section_title ); ?></h2>
			<?php if ( $section_subtitle ) : ?>
			<p class="events-subtitle"><?php echo esc_html( $section_subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $show_filters || $show_search ) : ?>
		<div class="events-filters">

			<?php if ( $show_search ) : ?>
			<div class="filter-search">
				<input type="text"
				       placeholder="<?php esc_attr_e( 'Buscar eventos...', 'apollo-events-manager' ); ?>"
				       id="apollo-events-search"
				       data-target=".apollo-events-grid">
			</div>
			<?php endif; ?>

			<?php if ( $show_filters && ! empty( $seasons_terms ) && ! is_wp_error( $seasons_terms ) ) : ?>
			<div class="apollo-custom-select" data-type="single" data-filter="season">
				<div class="apollo-select-trigger"><?php esc_html_e( 'Temporada', 'apollo-events-manager' ); ?></div>
				<div class="apollo-select-dropdown">
					<div class="apollo-select-option" data-value=""><?php esc_html_e( 'Todas', 'apollo-events-manager' ); ?></div>
					<?php foreach ( $seasons_terms as $term ) : ?>
					<div class="apollo-select-option" data-value="<?php echo esc_attr( $term->slug ); ?>">
						<?php echo esc_html( $term->name ); ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $show_filters && ! empty( $sounds_terms ) && ! is_wp_error( $sounds_terms ) ) : ?>
			<div class="apollo-custom-select" data-type="multi" data-filter="sounds">
				<div class="apollo-select-trigger"><?php esc_html_e( 'Gêneros', 'apollo-events-manager' ); ?></div>
				<div class="apollo-select-dropdown">
					<?php foreach ( $sounds_terms as $term ) : ?>
					<div class="apollo-select-option" data-value="<?php echo esc_attr( $term->slug ); ?>">
						<input type="checkbox" value="<?php echo esc_attr( $term->slug ); ?>">
						<?php echo esc_html( $term->name ); ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

		</div>
		<?php endif; ?>
	</div>

	<!-- Events Grid -->
	<div class="apollo-events-grid" id="apollo-events-grid" data-endpoint="<?php echo esc_url( rest_url( 'apollo/v1/events' ) ); ?>">
		<?php
		if ( ! empty( $events ) ) :
			$delay_sequence = array( 100, 200, 300, 100, 200, 300, 100, 200 );
			$index          = 0;

			foreach ( $events as $event ) :
				$delay = $delay_sequence[ $index % count( $delay_sequence ) ];

				apollo_render_event_card( $event, array(
					'delay_index' => $delay,
					'show_sounds' => true,
					'show_djs'    => true,
				) );

				$index++;
			endforeach;
		else :
			?>
			<p class="apollo-no-events" style="grid-column: 1 / -1; text-align: center; color: var(--apollo-gray);">
				<?php esc_html_e( 'Nenhum evento encontrado.', 'apollo-events-manager' ); ?>
			</p>
			<?php
		endif;
		?>
	</div>

	<!-- Load More Button (optional) -->
	<?php if ( count( $events ) >= $count ) : ?>
	<div class="events-load-more" style="text-align: center; margin-top: 48px;">
		<button type="button"
		        class="hub-link hub-link-secondary"
		        id="apollo-load-more-events"
		        data-page="1"
		        data-per-page="<?php echo absint( $count ); ?>"
		        style="display: inline-flex; padding: 14px 32px;">
			<span><?php esc_html_e( 'Ver mais eventos', 'apollo-events-manager' ); ?></span>
			<i class="ri-arrow-right-line"></i>
		</button>
	</div>
	<?php endif; ?>

</section>

<script>
(function() {
	'use strict';

	// Custom Select Dropdowns
	document.querySelectorAll('.apollo-custom-select').forEach(function(select) {
		var trigger = select.querySelector('.apollo-select-trigger');
		var dropdown = select.querySelector('.apollo-select-dropdown');
		var options = select.querySelectorAll('.apollo-select-option');
		var isMulti = select.dataset.type === 'multi';

		trigger.addEventListener('click', function(e) {
			e.stopPropagation();
			document.querySelectorAll('.apollo-custom-select.open').forEach(function(s) {
				if (s !== select) s.classList.remove('open');
			});
			select.classList.toggle('open');
		});

		options.forEach(function(option) {
			option.addEventListener('click', function(e) {
				if (isMulti) {
					e.stopPropagation();
					var checkbox = option.querySelector('input[type="checkbox"]');
					if (checkbox) {
						checkbox.checked = !checkbox.checked;
						option.classList.toggle('selected', checkbox.checked);
					}
					updateMultiTrigger(select);
				} else {
					options.forEach(function(o) { o.classList.remove('selected'); });
					option.classList.add('selected');
					trigger.textContent = option.textContent.trim();
					select.classList.remove('open');
					filterEvents();
				}
			});
		});
	});

	function updateMultiTrigger(select) {
		var trigger = select.querySelector('.apollo-select-trigger');
		var selected = [];
		select.querySelectorAll('.apollo-select-option.selected').forEach(function(o) {
			selected.push(o.textContent.trim());
		});
		trigger.textContent = selected.length ? selected.join(', ') : '<?php esc_html_e( 'Gêneros', 'apollo-events-manager' ); ?>';
		filterEvents();
	}

	// Close dropdowns on outside click
	document.addEventListener('click', function() {
		document.querySelectorAll('.apollo-custom-select.open').forEach(function(s) {
			s.classList.remove('open');
		});
	});

	// Search functionality
	var searchInput = document.getElementById('apollo-events-search');
	if (searchInput) {
		var debounceTimer;
		searchInput.addEventListener('input', function() {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(filterEvents, 300);
		});
	}

	// Filter events via AJAX
	function filterEvents() {
		var grid = document.getElementById('apollo-events-grid');
		if (!grid) return;

		var endpoint = grid.dataset.endpoint;
		var params = new URLSearchParams();

		// Get search term
		var search = document.getElementById('apollo-events-search');
		if (search && search.value) {
			params.append('search', search.value);
		}

		// Get season filter
		var seasonSelect = document.querySelector('[data-filter="season"]');
		if (seasonSelect) {
			var selected = seasonSelect.querySelector('.apollo-select-option.selected');
			if (selected && selected.dataset.value) {
				params.append('season', selected.dataset.value);
			}
		}

		// Get sounds filter
		var soundsSelect = document.querySelector('[data-filter="sounds"]');
		if (soundsSelect) {
			var sounds = [];
			soundsSelect.querySelectorAll('.apollo-select-option.selected').forEach(function(o) {
				if (o.dataset.value) sounds.push(o.dataset.value);
			});
			if (sounds.length) {
				params.append('sounds', sounds.join(','));
			}
		}

		// Fetch filtered events
		var url = endpoint + (params.toString() ? '?' + params.toString() : '');

		grid.style.opacity = '0.5';

		fetch(url)
			.then(function(r) { return r.json(); })
			.then(function(data) {
				if (data && data.html) {
					grid.innerHTML = data.html;
				}
				grid.style.opacity = '1';

				// Re-trigger reveal animations
				grid.querySelectorAll('.reveal-up').forEach(function(el) {
					el.classList.add('in-view');
				});
			})
			.catch(function() {
				grid.style.opacity = '1';
			});
	}
})();
</script>
