<?php
/**
 * Apollo Classified Card Standard - Universal Classified/Advert Card Template
 * =============================================================================
 * Path: apollo-core/includes/helpers/class-classified-card-standard.php
 *
 * UNIVERSAL classified/advert card renderer using placeholder-template approach.
 * All classified cards (accommodations, tickets, etc.) in Apollo ecosystem MUST use this.
 *
 * @package Apollo\Core
 * @version 1.0.0
 * @since 2026-01-06
 */

declare(strict_types=1);

namespace Apollo\Core\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Classified_Card_Standard
 *
 * Centralized classified/advert card renderer with placeholder replacement.
 */
final class Classified_Card_Standard {

	/**
	 * Default placeholder image URL
	 */
	public const DEFAULT_IMAGE = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23ddd" width="400" height="300"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" fill="%23999" font-size="18"%3ESem Imagem%3C/text%3E%3C/svg%3E';

	/**
	 * Render classified card HTML
	 *
	 * UNIVERSAL STRUCTURE - All classified cards MUST use this.
	 * Uses placeholder-template + replacement approach (NO unreplaced placeholders in output).
	 *
	 * @param int   $ad_id Classified post ID.
	 * @param array $args  Optional arguments.
	 * @return void Echoes HTML output.
	 */
	public static function render( int $ad_id, array $args = array() ): void {
		// Build context data
		$context = self::build_context( $ad_id, $args );

		// Get HTML template with placeholders
		$template_html = self::get_template_html( $context );

		// Build replacement map
		$replacements = self::build_replacement_map( $context );

		// Replace all placeholders in one pass using strtr
		$final_html = strtr( $template_html, $replacements );

		// Echo final HTML (already escaped in replacement map)
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $final_html;
	}

	/**
	 * Build context data from classified post ID
	 *
	 * @param int   $ad_id Classified post ID.
	 * @param array $args  Optional arguments.
	 * @return array Context array.
	 */
	private static function build_context( int $ad_id, array $args = array() ): array {
		$defaults = array(
			'context'      => 'grid', // 'grid' or 'scroll'
			'class'        => '',
			'show_contact' => true,
			'show_views'   => false,
			'show_author'  => false,
			'show_created' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		// Get post object
		$post = get_post( $ad_id );
		if ( ! $post || 'apollo_classified' !== $post->post_type ) {
			return array();
		}

		// Get taxonomies
		$categories      = wp_get_post_terms( $ad_id, 'classified_domain', array( 'fields' => 'all' ) );
		$category_names  = array();
		$category_slugs  = array();
		$is_ticket       = false;

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				$category_names[] = $cat->name;
				$category_slugs[] = $cat->slug;

				// Check if this is a tickets category
				if ( stripos( $cat->name, 'ticket' ) !== false || stripos( $cat->slug, 'ticket' ) !== false ) {
					$is_ticket = true;
				}
			}
		}

		// Get image
		$image_url = self::get_classified_image( $ad_id );

		// Get title
		$title = get_the_title( $ad_id );

		// Get location (unified meta key)
		$location = get_post_meta( $ad_id, '_classified_location_text', true );
		if ( empty( $location ) ) {
			$location = 'N/A';
		}

		// Get price (unified meta key)
		$price = get_post_meta( $ad_id, '_classified_price', true );

		// Get contact info
		$phone = get_post_meta( $ad_id, '_classified_contact_phone', true );
		$email = get_post_meta( $ad_id, '_classified_contact_email', true );

		// Get views
		$views = (int) get_post_meta( $ad_id, 'views', true );

		// Get author
		$author_data = get_userdata( $post->post_author );
		$author_name = $author_data ? $author_data->display_name : '';

		// Get created date
		$created_date = $post->post_date;

		// Tickets-specific data
		$ticket_event_title    = '';
		$ticket_event_date     = '';
		$ticket_original_price = '';
		$ticket_discount_badge = '';

		if ( $is_ticket ) {
			// Event title
			$ticket_event_title = get_post_meta( $ad_id, '_classified_event_title', true );
			if ( empty( $ticket_event_title ) ) {
				$ticket_event_title = $title;
			}

			// Event date
			$event_date_raw = get_post_meta( $ad_id, '_classified_event_date', true );
			if ( $event_date_raw ) {
				$timestamp = is_numeric( $event_date_raw ) ? (int) $event_date_raw : strtotime( $event_date_raw );
				if ( $timestamp && $timestamp > 0 ) {
					$ticket_event_date = date_i18n( 'd M', $timestamp );
				} else {
					$ticket_event_date = 'N/A';
				}
			} else {
				$ticket_event_date = 'N/A';
			}

			// Original price and discount
			$original_price_raw = get_post_meta( $ad_id, '_classified_original_price', true );
			if ( $original_price_raw && is_numeric( $original_price_raw ) && is_numeric( $price ) ) {
				$original_val = (float) $original_price_raw;
				$current_val  = (float) $price;

				if ( $original_val > $current_val && $original_val > 0 ) {
					$discount_percent      = round( ( ( $original_val - $current_val ) / $original_val ) * 100 );
					$ticket_original_price = number_format_i18n( $original_val, 2 );
					$ticket_discount_badge = '-' . $discount_percent . '%';
				}
			}
		}

		return array(
			'ad_id'                 => $ad_id,
			'title'                 => $title,
			'image_url'             => $image_url,
			'location'              => $location,
			'price'                 => $price,
			'category_names'        => $category_names,
			'category_slugs'        => $category_slugs,
			'is_ticket'             => $is_ticket,
			'phone'                 => $phone,
			'email'                 => $email,
			'views'                 => $views,
			'author_name'           => $author_name,
			'created_date'          => $created_date,
			'ticket_event_title'    => $ticket_event_title,
			'ticket_event_date'     => $ticket_event_date,
			'ticket_original_price' => $ticket_original_price,
			'ticket_discount_badge' => $ticket_discount_badge,
			'permalink'             => get_permalink( $ad_id ),
			'args'                  => $args,
		);
	}

	/**
	 * Get classified image URL
	 *
	 * @param int $ad_id Classified post ID.
	 * @return string Image URL.
	 */
	private static function get_classified_image( int $ad_id ): string {
		// First try _classified_gallery meta (array of attachment IDs)
		$gallery = get_post_meta( $ad_id, '_classified_gallery', true );
		if ( is_array( $gallery ) && ! empty( $gallery ) ) {
			$first_id = reset( $gallery );
			if ( $first_id ) {
				$url = wp_get_attachment_image_url( $first_id, 'large' );
				if ( ! $url ) {
					$url = wp_get_attachment_url( $first_id );
				}
				if ( $url ) {
					return $url;
				}
			}
		}

		// Fallback to post thumbnail
		$thumbnail = get_the_post_thumbnail_url( $ad_id, 'large' );
		if ( $thumbnail ) {
			return $thumbnail;
		}

		// Final fallback to default placeholder
		return self::DEFAULT_IMAGE;
	}

	/**
	 * Get HTML template with placeholders
	 *
	 * @param array $context Context data.
	 * @return string HTML template with placeholders.
	 */
	private static function get_template_html( array $context ): string {
		$args      = $context['args'] ?? array();
		$is_ticket = $context['is_ticket'] ?? false;

		// Build container class
		$container_class = 'a-classified-card';

		// Add context-specific classes
		if ( 'grid' === $args['context'] ) {
			$container_class .= ' accommodation-card';
		} elseif ( 'scroll' === $args['context'] ) {
			$container_class .= ' resell-ticket';
		}

		// Add custom class if provided
		if ( ! empty( $args['class'] ) ) {
			$container_class .= ' ' . $args['class'];
		}

		// Build template based on type
		if ( $is_ticket ) {
			return self::get_ticket_template( $container_class, $args );
		} else {
			return self::get_accommodation_template( $container_class, $args );
		}
	}

	/**
	 * Get accommodation template HTML
	 *
	 * @param string $container_class Container CSS class.
	 * @param array  $args            Arguments.
	 * @return string Template HTML.
	 */
	private static function get_accommodation_template( string $container_class, array $args ): string {
		ob_start();
		?>
<a href="{classified_url}" class="<?php echo esc_attr( $container_class ); ?>">
	<div class="accommodation-image a-classified-media">
		<img src="{classified_image}" alt="{classified_title}" loading="lazy" decoding="async">
		{classified_categories}
	</div>
	<div class="accommodation-info a-classified-content">
		<h3 class="accommodation-title a-classified-title">{classified_title}</h3>
		<div class="accommodation-hood a-classified-location">
			<i class="ri-map-pin-2-line"></i>
			<span>{classified_location}</span>
		</div>
		<div class="accommodation-price a-classified-price">{classified_price}</div>
		{classified_contact}
		{classified_meta_footer}
	</div>
</a>
<?php
		return ob_get_clean();
	}

	/**
	 * Get ticket template HTML
	 *
	 * @param string $container_class Container CSS class.
	 * @param array  $args            Arguments.
	 * @return string Template HTML.
	 */
	private static function get_ticket_template( string $container_class, array $args ): string {
		ob_start();
		?>
<div class="<?php echo esc_attr( $container_class ); ?>">
	<div class="ticket-event a-classified-event">{ticket_event_title}</div>
	<div class="ticket-info a-classified-info">
		{ticket_event_date}{ticket_location_separator}{classified_location}
	</div>
	<div class="ticket-price a-classified-price">
		{classified_price}
		{ticket_original_price}
	</div>
	{ticket_discount_badge}
	{classified_meta_footer}
</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Build replacement map for placeholders
	 *
	 * All values MUST be properly escaped here.
	 *
	 * @param array $context Context data.
	 * @return array Replacement map.
	 */
	private static function build_replacement_map( array $context ): array {
		$args = $context['args'] ?? array();

		// Format price
		$price_formatted = 'N/A';
		if ( ! empty( $context['price'] ) && is_numeric( $context['price'] ) ) {
			$price_formatted = 'R$ ' . number_format_i18n( (float) $context['price'], 2 );
		}

		// Build category tags HTML
		$categories_html = '';
		if ( ! empty( $context['category_names'] ) ) {
			$categories_html = '<div class="a-classified-tags">';
			foreach ( array_slice( $context['category_names'], 0, 3 ) as $cat_name ) {
				$categories_html .= '<span class="a-classified-tag">' . esc_html( $cat_name ) . '</span>';
			}
			$categories_html .= '</div>';
		}

		// Build contact block HTML
		$contact_html = '';
		if ( $args['show_contact'] && ( ! empty( $context['phone'] ) || ! empty( $context['email'] ) ) ) {
			$contact_html = '<div class="a-classified-contact">';
			if ( ! empty( $context['phone'] ) ) {
				$contact_html .= '<span class="a-classified-phone"><i class="ri-phone-line"></i> ' . esc_html( $context['phone'] ) . '</span>';
			}
			if ( ! empty( $context['email'] ) ) {
				$contact_html .= '<span class="a-classified-email"><i class="ri-mail-line"></i> ' . esc_html( $context['email'] ) . '</span>';
			}
			$contact_html .= '</div>';
		}

		// Build meta footer HTML
		$meta_footer_html = '';
		$meta_parts       = array();

		if ( $args['show_views'] && $context['views'] > 0 ) {
			$meta_parts[] = '<span class="a-classified-views"><i class="ri-eye-line"></i> ' . esc_html( number_format_i18n( $context['views'] ) ) . '</span>';
		}

		if ( $args['show_author'] && ! empty( $context['author_name'] ) ) {
			$meta_parts[] = '<span class="a-classified-author"><i class="ri-user-line"></i> ' . esc_html( $context['author_name'] ) . '</span>';
		}

		if ( $args['show_created'] && ! empty( $context['created_date'] ) ) {
			$created_formatted = date_i18n( get_option( 'date_format' ), strtotime( $context['created_date'] ) );
			$meta_parts[]      = '<span class="a-classified-created"><i class="ri-time-line"></i> ' . esc_html( $created_formatted ) . '</span>';
		}

		if ( ! empty( $meta_parts ) ) {
			$meta_footer_html = '<div class="a-classified-meta-footer">' . implode( '', $meta_parts ) . '</div>';
		}

		// Ticket-specific replacements
		$ticket_original_price_html = '';
		if ( ! empty( $context['ticket_original_price'] ) ) {
			$ticket_original_price_html = '<span class="ticket-original a-classified-original">R$ ' . esc_html( $context['ticket_original_price'] ) . '</span>';
		}

		$ticket_discount_badge_html = '';
		if ( ! empty( $context['ticket_discount_badge'] ) ) {
			$ticket_discount_badge_html = '<span class="ticket-badge a-classified-badge">' . esc_html( $context['ticket_discount_badge'] ) . '</span>';
		}

		$ticket_location_separator = '';
		if ( ! empty( $context['ticket_event_date'] ) && 'N/A' !== $context['ticket_event_date'] && ! empty( $context['location'] ) && 'N/A' !== $context['location'] ) {
			$ticket_location_separator = ' · ';
		}

		// Build final replacement map
		return array(
			'{classified_url}'              => esc_url( $context['permalink'] ),
			'{classified_image}'            => esc_url( $context['image_url'] ),
			'{classified_title}'            => esc_html( $context['title'] ),
			'{classified_location}'         => esc_html( $context['location'] ),
			'{classified_price}'            => $price_formatted, // Already formatted and safe
			'{classified_categories}'       => $categories_html, // Already escaped
			'{classified_contact}'          => $contact_html, // Already escaped
			'{classified_meta_footer}'      => $meta_footer_html, // Already escaped
			'{ticket_event_title}'          => esc_html( $context['ticket_event_title'] ),
			'{ticket_event_date}'           => esc_html( $context['ticket_event_date'] ),
			'{ticket_original_price}'       => $ticket_original_price_html, // Already escaped
			'{ticket_discount_badge}'       => $ticket_discount_badge_html, // Already escaped
			'{ticket_location_separator}'   => $ticket_location_separator, // Safe literal
		);
	}
}

// =============================================================================
// GLOBAL HELPER FUNCTIONS
// =============================================================================

/**
 * Render Apollo standard classified card
 *
 * @param int   $ad_id Classified post ID.
 * @param array $args  Optional arguments.
 * @return void
 */
function apollo_classified_card( int $ad_id, array $args = array() ): void {
	\Apollo\Core\Helpers\Classified_Card_Standard::render( $ad_id, $args );
}
