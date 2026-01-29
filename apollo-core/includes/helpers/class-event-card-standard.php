<?php
/**
 * Apollo Event Card Standard - Universal Event Card Template
 * ===========================================================
 * Path: apollo-core/includes/helpers/class-event-card-standard.php
 *
 * UNIVERSAL event card renderer with CDN CSS injection.
 * All event cards in the Apollo ecosystem MUST use this structure.
 *
 * CDN: https://cdn.apollo.rio.br/
 *
 * @package Apollo\Core
 * @version 1.0.0
 * @since 2026-01-06
 */

declare(strict_types=1);

namespace Apollo\Core\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Event_Card_Standard
 *
 * Centralized event card renderer following Apollo Design System.
 */
final class Event_Card_Standard {

	/**
	 * CDN URL for Apollo assets
	 */
	public const CDN_URL = 'https://cdn.apollo.rio.br/';

	/**
	 * Assets already enqueued flag
	 */
	private static bool $assets_enqueued = false;

	/**
	 * Enqueue CDN assets for event cards
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'inject_cdn_styles' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'inject_cdn_scripts' ), 99 );

		self::$assets_enqueued = true;
	}

	/**
	 * Inject CDN CSS in head
	 *
	 * @return void
	 */
	public static function inject_cdn_styles(): void {
		?>
<link rel="preconnect" href="<?php echo esc_url( self::CDN_URL ); ?>" crossorigin>
<link rel="stylesheet" href="<?php echo esc_url( self::CDN_URL ); ?>css/event-card.css" media="all">
<link rel="stylesheet" href="<?php echo esc_url( self::CDN_URL ); ?>css/reveal-animations.css" media="all">
<?php
	}

	/**
	 * Inject CDN JS in footer
	 *
	 * @return void
	 */
	public static function inject_cdn_scripts(): void {
		?>
<script src="<?php echo esc_url( self::CDN_URL ); ?>js/event-card.js" defer></script>
<script src="<?php echo esc_url( self::CDN_URL ); ?>js/reveal.js" defer></script>
<?php
	}

	/**
	 * Build context from event post ID
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Optional arguments.
	 * @return array Context array for template.
	 */
	public static function build_context( int $event_id, array $args = array() ): array {
		$defaults = array(
			'delay_index'   => 100,
			'show_sounds'   => true,
			'show_djs'      => true,
			'show_location' => true,
			'popup_display' => true,
		);
		$args = wp_parse_args( $args, $defaults );

		// Get event meta using canonical keys from cpt.md
		$event_title    = get_post_meta( $event_id, '_event_title', true ) ?: get_the_title( $event_id );
		$event_banner   = get_post_meta( $event_id, '_event_banner', true );
		$event_date     = get_post_meta( $event_id, '_event_start_date', true );
		$event_location = get_post_meta( $event_id, '_event_location', true );
		$tickets_ext    = get_post_meta( $event_id, '_tickets_ext', true );

		// Fallback to thumbnail if no banner
		if ( empty( $event_banner ) ) {
			$event_banner = get_the_post_thumbnail_url( $event_id, 'large' );
		}

		// Get location from related local if not set directly
		if ( empty( $event_location ) ) {
			$local_ids = get_post_meta( $event_id, '_event_local_ids', true );
			if ( $local_ids ) {
				$local_id = is_array( $local_ids ) ? reset( $local_ids ) : $local_ids;
				if ( $local_id ) {
					$event_location = get_post_meta( $local_id, '_local_name', true );
					if ( empty( $event_location ) ) {
						$event_location = get_the_title( $local_id );
					}
				}
			}
		}

		// Get DJs from timetable slots (canonical: _event_dj_slots)
		$event_djs = array();
		$dj_slots  = get_post_meta( $event_id, '_event_dj_slots', true );

		if ( ! empty( $dj_slots ) && is_array( $dj_slots ) ) {
			foreach ( $dj_slots as $slot ) {
				if ( isset( $slot['dj_id'] ) && $slot['dj_id'] ) {
					$dj_name = get_post_meta( $slot['dj_id'], '_dj_name', true );
					if ( empty( $dj_name ) ) {
						$dj_name = get_the_title( $slot['dj_id'] );
					}
					if ( $dj_name && ! in_array( $dj_name, $event_djs, true ) ) {
						$event_djs[] = $dj_name;
					}
				}
			}
		}

		// Fallback to legacy DJ IDs
		if ( empty( $event_djs ) ) {
			$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
			if ( $dj_ids ) {
				$dj_ids = maybe_unserialize( $dj_ids );
				if ( is_array( $dj_ids ) ) {
					foreach ( $dj_ids as $dj_id ) {
						$dj_name = get_post_meta( $dj_id, '_dj_name', true );
						if ( empty( $dj_name ) ) {
							$dj_name = get_the_title( $dj_id );
						}
						if ( $dj_name ) {
							$event_djs[] = $dj_name;
						}
					}
				}
			}
		}

		// Get sounds from taxonomy (event_sounds)
		$event_sounds = array();
		$sounds_terms = wp_get_post_terms( $event_id, 'event_sounds', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $sounds_terms ) && ! empty( $sounds_terms ) ) {
			$event_sounds = $sounds_terms;
		}

		// Get categories for tags (event_listing_category)
		$event_categories = array();
		$cat_terms = wp_get_post_terms( $event_id, 'event_listing_category', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) {
			$event_categories = $cat_terms;
		}

		// Determine URL (external tickets or permalink)
		$event_url   = get_permalink( $event_id );
		$is_external = false;

		if ( ! empty( $tickets_ext ) ) {
			$event_url   = $tickets_ext;
			$is_external = true;
		}

		return array(
			'event_id'         => $event_id,
			'event_title'      => $event_title,
			'event_url'        => $event_url,
			'event_image'      => $event_banner,
			'event_date'       => $event_date,
			'event_location'   => $args['show_location'] ? $event_location : '',
			'event_djs'        => $args['show_djs'] ? $event_djs : array(),
			'event_sounds'     => $args['show_sounds'] ? $event_sounds : array(),
			'event_categories' => $event_categories,
			'is_external'      => $is_external,
			'popup_display'    => $args['popup_display'],
			'delay_index'      => $args['delay_index'],
		);
	}

	/**
	 * Render event card HTML
	 *
	 * UNIVERSAL STRUCTURE - All event cards MUST follow this format.
	 *
	 * @param array $context Context from build_context().
	 * @return string HTML output.
	 */
	public static function render( array $context ): string {
		// Ensure assets are enqueued
		self::enqueue_assets();

		// Extract context
		$event_id         = $context['event_id'] ?? 0;
		$event_title      = $context['event_title'] ?? '';
		$event_url        = $context['event_url'] ?? '#';
		$event_image      = $context['event_image'] ?? '';
		$event_date       = $context['event_date'] ?? '';
		$event_location   = $context['event_location'] ?? '';
		$event_djs        = $context['event_djs'] ?? array();
		$event_sounds     = $context['event_sounds'] ?? array();
		$event_categories = $context['event_categories'] ?? array();
		$is_external      = $context['is_external'] ?? false;
		$popup_display    = $context['popup_display'] ?? true;
		$delay_index      = $context['delay_index'] ?? 100;

		// Parse date
		$date_day   = '';
		$date_month = '';
		if ( $event_date ) {
			$timestamp  = is_numeric( $event_date ) ? (int) $event_date : strtotime( $event_date );
			if ( $timestamp ) {
				$date_day   = date_i18n( 'd', $timestamp );
				$date_month = date_i18n( 'M', $timestamp );
			}
		}

		// Build target attribute
		$target_attr = '';
		if ( $is_external ) {
			$target_attr = ' target="_blank" rel="noopener noreferrer"';
		} elseif ( $popup_display ) {
			$target_attr = ' target="apollo-event-popup"';
		}

		// Random delay for animation (100-500)
		if ( 'random' === $delay_index ) {
			$delay_index = wp_rand( 100, 500 );
		}

		// Build HTML following UNIVERSAL structure
		ob_start();
		?>
<a href="<?php echo esc_url( $event_url ); ?>"
	<?php echo $target_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	class="a-eve-card reveal-up delay-<?php echo absint( $delay_index ); ?> in-view"
	data-idx="<?php echo absint( $event_id ); ?>">

	<?php if ( $date_day && $date_month ) : ?>
	<div class="a-eve-date">
		<span class="a-eve-date-day"><?php echo esc_html( $date_day ); ?></span>
		<span class="a-eve-date-month"><?php echo esc_html( $date_month ); ?></span>
	</div>
	<?php endif; ?>

	<div class="a-eve-media">
		<?php if ( $event_image ) : ?>
		<img src="<?php echo esc_url( $event_image ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" loading="lazy"
			decoding="async">
		<?php endif; ?>

		<?php if ( ! empty( $event_categories ) ) : ?>
		<div class="a-eve-tags">
			<?php foreach ( array_slice( $event_categories, 0, 3 ) as $category ) : ?>
			<span class="a-eve-tag"><?php echo esc_html( $category ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="a-eve-content">
		<h2 class="a-eve-title"><?php echo esc_html( $event_title ); ?></h2>

		<?php if ( ! empty( $event_djs ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-sound-module-fill"></i>
			<span><?php echo esc_html( implode( ', ', array_slice( $event_djs, 0, 3 ) ) ); ?></span>
		</p>
		<?php endif; ?>

		<?php if ( ! empty( $event_location ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-map-pin-2-line"></i>
			<span><?php echo esc_html( $event_location ); ?></span>
		</p>
		<?php endif; ?>

		<?php if ( ! empty( $event_sounds ) ) : ?>
		<p class="a-eve-meta">
			<i class="ri-music-2-line"></i>
			<span><?php echo esc_html( implode( ', ', array_slice( $event_sounds, 0, 3 ) ) ); ?></span>
		</p>
		<?php endif; ?>
	</div>
</a>
<?php
		return ob_get_clean();
	}

	/**
	 * Render event card from event ID (convenience method)
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Optional arguments.
	 * @return string HTML output.
	 */
	public static function render_from_id( int $event_id, array $args = array() ): string {
		$context = self::build_context( $event_id, $args );
		return self::render( $context );
	}

	/**
	 * Echo event card from event ID
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $args     Optional arguments.
	 * @return void
	 */
	public static function display( int $event_id, array $args = array() ): void {
		echo self::render_from_id( $event_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render events grid
	 *
	 * @param array|WP_Query $events Events array or WP_Query object.
	 * @param array          $args   Grid arguments.
	 * @return string HTML output.
	 */
	public static function render_grid( $events, array $args = array() ): string {
		$defaults = array(
			'columns'    => 4,
			'max_events' => 8,
			'animate'    => true,
			'grid_class' => 'apollo-events-grid',
		);
		$args = wp_parse_args( $args, $defaults );

		// Convert WP_Query to array
		if ( $events instanceof \WP_Query ) {
			$events = $events->posts;
		}

		// Limit events
		$events = array_slice( $events, 0, $args['max_events'] );

		if ( empty( $events ) ) {
			return '<p class="apollo-no-events">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-core' ) . '</p>';
		}

		// Ensure assets are enqueued
		self::enqueue_assets();

		ob_start();
		?>
<div class="<?php echo esc_attr( $args['grid_class'] ); ?>">
	<?php
			$delay_sequence = array( 100, 200, 300, 100, 200, 300, 100, 200 );
			$index          = 0;

			foreach ( $events as $event ) :
				$event_id = is_object( $event ) ? $event->ID : (int) $event;
				$delay    = $args['animate'] ? $delay_sequence[ $index % count( $delay_sequence ) ] : 0;

				echo self::render_from_id( $event_id, array( 'delay_index' => $delay ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				++$index;
			endforeach;
			?>
</div>
<?php
		return ob_get_clean();
	}
}

// =============================================================================
// GLOBAL HELPER FUNCTIONS
// =============================================================================

/**
 * Render Apollo standard event card
 *
 * @param int   $event_id Event post ID.
 * @param array $args     Optional arguments.
 * @return void
 */
function apollo_event_card( int $event_id, array $args = array() ): void {
	\Apollo\Core\Helpers\Event_Card_Standard::display( $event_id, $args );
}

/**
 * Get Apollo standard event card HTML
 *
 * @param int   $event_id Event post ID.
 * @param array $args     Optional arguments.
 * @return string HTML output.
 */
function apollo_get_event_card( int $event_id, array $args = array() ): string {
	return \Apollo\Core\Helpers\Event_Card_Standard::render_from_id( $event_id, $args );
}

/**
 * Render Apollo events grid
 *
 * @param array|WP_Query $events Events array or WP_Query object.
 * @param array          $args   Grid arguments.
 * @return void
 */
function apollo_events_grid( $events, array $args = array() ): void {
	echo \Apollo\Core\Helpers\Event_Card_Standard::render_grid( $events, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
