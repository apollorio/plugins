<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Shortcodes
 *
 * Organized shortcode system with analytics integration
 * Migrated from wp-event-manager with Apollo updates
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apollo Events Shortcodes Class
 */
class Apollo_Events_Shortcodes {

	private $event_dashboard_message = '';
	private $dj_dashboard_message    = '';
	private $local_dashboard_message = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'shortcode_action_handler' ) );

		// Event shortcodes
		// NOTE: 'events' is registered in apollo-events-manager.php (main shortcode)
		// NOTE: 'submit_event_form' may be registered elsewhere - check before registering
		if ( ! shortcode_exists( 'submit_event_form' ) ) {
			add_shortcode( 'submit_event_form', array( $this, 'submit_event_form' ) );
		}
		add_shortcode( 'event_dashboard', array( $this, 'event_dashboard' ) );
		// 'events' shortcode is registered in main plugin file - do not register here
		// if (!shortcode_exists('events')) {
		// add_shortcode('events', array($this, 'output_events'));
		// }
		add_shortcode( 'event', array( $this, 'output_event' ) );
		add_shortcode( 'event_summary', array( $this, 'output_event_summary' ) );
		add_shortcode( 'past_events', array( $this, 'output_past_events' ) );
		add_shortcode( 'upcoming_events', array( $this, 'output_upcoming_events' ) );
		add_shortcode( 'related_events', array( $this, 'output_related_events' ) );
		add_shortcode( 'event_register', array( $this, 'output_event_register' ) );

		// DJ shortcodes
		add_shortcode( 'submit_dj_form', array( $this, 'submit_dj_form' ) );
		add_shortcode( 'dj_dashboard', array( $this, 'dj_dashboard' ) );
		add_shortcode( 'event_djs', array( $this, 'output_event_djs' ) );
		add_shortcode( 'event_dj', array( $this, 'output_event_dj' ) );
		add_shortcode( 'single_event_dj', array( $this, 'output_single_event_dj' ) );

		// Local shortcodes
		add_shortcode( 'submit_local_form', array( $this, 'submit_local_form' ) );
		add_shortcode( 'local_dashboard', array( $this, 'local_dashboard' ) );
		add_shortcode( 'event_locals', array( $this, 'output_event_locals' ) );
		add_shortcode( 'event_local', array( $this, 'output_event_local' ) );
		add_shortcode( 'single_event_local', array( $this, 'output_single_event_local' ) );
	}

	/**
	 * Handle actions which need to be run before the shortcode
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'event_dashboard' ) ) {
			$this->event_dashboard_handler();
			$this->dj_dashboard_handler();
			$this->local_dashboard_handler();
		} elseif ( has_shortcode( $post->post_content, 'dj_dashboard' ) ) {
			$this->dj_dashboard_handler();
		} elseif ( has_shortcode( $post->post_content, 'local_dashboard' ) ) {
			$this->local_dashboard_handler();
		}
	}

	/**
	 * Show the event submission form
	 */
	public function submit_event_form( $atts = array() ) {
		if ( ! function_exists( 'apollo_render_public_event_form' ) ) {
			return '<div class="apollo-alert apollo-alert-danger">' .
				esc_html__( 'Event submission form is temporarily unavailable.', 'apollo-events-manager' ) .
				'</div>';
		}

		$atts = shortcode_atts(
			array(
				'show_title' => true,
				'title'      => __( 'Submit Your Event', 'apollo-events-manager' ),
			),
			$atts,
			'submit_event_form'
		);

		ob_start();

		if ( ! is_user_logged_in() ) {
			echo '<div class="apollo-alert apollo-alert-info">';
			echo '<i class="ri-information-line"></i>';
			echo '<p>' . esc_html__(
				'Envie seu evento para aparecer no portal. Nossa equipe revisa tudo antes de publicar.',
				'apollo-events-manager'
			) . '</p>';
			echo '</div>';
		}

		echo apollo_render_public_event_form( $atts );

		return ob_get_clean();
	}

	/**
	 * Show the DJ submission form
	 */
	public function submit_dj_form( $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-alert apollo-alert-info">' .
					esc_html__( 'You must be logged in to submit a DJ profile.', 'apollo-events-manager' ) .
					' <a href="' . esc_url( wp_login_url() ) . '">' .
					esc_html__( 'Login', 'apollo-events-manager' ) . '</a></div>';
		}

		// TODO: Integrate with Apollo forms system
		return '<div class="apollo-submit-dj-form">' .
				esc_html__( 'DJ submission form coming soon.', 'apollo-events-manager' ) .
				'</div>';
	}

	/**
	 * Show the local submission form
	 */
	public function submit_local_form( $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-alert apollo-alert-info">' .
					esc_html__( 'You must be logged in to submit a venue.', 'apollo-events-manager' ) .
					' <a href="' . esc_url( wp_login_url() ) . '">' .
					esc_html__( 'Login', 'apollo-events-manager' ) . '</a></div>';
		}

		// TODO: Integrate with Apollo forms system
		return '<div class="apollo-submit-local-form">' .
				esc_html__( 'Venue submission form coming soon.', 'apollo-events-manager' ) .
				'</div>';
	}

	/**
	 * Handles actions on event dashboard
	 */
	public function event_dashboard_handler() {
		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'apollo_event_dashboard_actions' ) ) {

			$action   = sanitize_title( $_REQUEST['action'] );
			$event_id = isset( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;

			if ( ! $event_id ) {
				$this->event_dashboard_message = '<div class="apollo-alert apollo-alert-danger">' .
					esc_html__( 'Invalid event ID.', 'apollo-events-manager' ) . '</div>';
				return;
			}

			try {
				$event = get_post( $event_id );

				if ( ! $event || $event->post_type !== 'event_listing' ) {
					throw new Exception( __( 'Invalid event.', 'apollo-events-manager' ) );
				}

				// Check ownership
				if ( $event->post_author != get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
					throw new Exception( __( 'You do not have permission to perform this action.', 'apollo-events-manager' ) );
				}

				switch ( $action ) {
					case 'delete':
						wp_trash_post( $event_id );
						$this->event_dashboard_message = '<div class="apollo-alert apollo-alert-success">' .
							sprintf( __( '%s has been deleted.', 'apollo-events-manager' ), esc_html( $event->post_title ) ) .
							'</div>';
						break;

					case 'duplicate':
						// Duplicate the event: copy post data and all _event_* meta to a new draft.
						$new_event_id = wp_insert_post(
							array(
								'post_type'    => 'event_listing',
								'post_title'   => sprintf( __( '%s (Copy)', 'apollo-events-manager' ), $event->post_title ),
								'post_content' => $event->post_content,
								'post_status'  => 'draft',
								'post_author'  => get_current_user_id(),
							)
						);

						if ( is_wp_error( $new_event_id ) ) {
							$this->event_dashboard_message = '<div class="apollo-alert apollo-alert-danger">' .
								esc_html__( 'Failed to duplicate event.', 'apollo-events-manager' ) .
								'</div>';
						} else {
							// Copy all _event_* meta keys.
							$meta = get_post_meta( $event_id );
							foreach ( $meta as $key => $values ) {
								if ( 0 === strpos( $key, '_event_' ) ) {
									foreach ( $values as $value ) {
										add_post_meta( $new_event_id, $key, maybe_unserialize( $value ) );
									}
								}
							}
							$edit_link = admin_url( 'post.php?post=' . $new_event_id . '&action=edit' );
							$this->event_dashboard_message = '<div class="apollo-alert apollo-alert-success">' .
								sprintf(
									/* translators: %1$s: original title, %2$s: link to edit */
									__( '%1$s duplicated successfully. <a href="%2$s">Edit the copy</a>.', 'apollo-events-manager' ),
									esc_html( $event->post_title ),
									esc_url( $edit_link )
								) .
								'</div>';
						}
						break;

					default:
						do_action( 'apollo_event_dashboard_do_action_' . $action, $event_id );
						break;
				}
			} catch ( Exception $e ) {
				$this->event_dashboard_message = '<div class="apollo-alert apollo-alert-danger">' .
					esc_html( $e->getMessage() ) . '</div>';
			}//end try
		}//end if
	}

	/**
	 * Shortcode which lists the logged in user's events
	 */
	public function event_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="apollo-event-dashboard">
				<p class="apollo-alert apollo-alert-info">
					<?php esc_html_e( 'You need to be signed in to manage your events.', 'apollo-events-manager' ); ?> 
					<a href="<?php echo esc_url( wp_login_url() ); ?>">
						<?php esc_html_e( 'Sign in', 'apollo-events-manager' ); ?>
					</a>
				</p>
			</div>
			<?php
			return ob_get_clean();
		}

		$atts = shortcode_atts(
			array(
				'posts_per_page' => 10,
			),
			$atts
		);

		ob_start();

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => absint( $atts['posts_per_page'] ),
			'paged'          => max( 1, get_query_var( 'paged' ) ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'author'         => get_current_user_id(),
		);

		$events = new WP_Query( $args );

		echo wp_kses_post( $this->event_dashboard_message );

		// Get analytics data
		$stats = array();
		if ( function_exists( 'apollo_get_user_event_stats' ) ) {
			$stats = apollo_get_user_event_stats( get_current_user_id() );
		}

		?>
		<div class="apollo-event-dashboard">
			<h2><?php esc_html_e( 'My Events', 'apollo-events-manager' ); ?></h2>
			
			<?php if ( ! empty( $stats ) ) : ?>
			<div class="apollo-dashboard-stats">
				<div class="stat-item">
					<i class="ri-calendar-event-line"></i>
					<span class="stat-value"><?php echo esc_html( $stats['total_events'] ?? 0 ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Events', 'apollo-events-manager' ); ?></span>
				</div>
				<div class="stat-item">
					<i class="ri-eye-line"></i>
					<span class="stat-value"><?php echo esc_html( $stats['total_views'] ?? 0 ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Views', 'apollo-events-manager' ); ?></span>
				</div>
				<div class="stat-item">
					<i class="ri-heart-line"></i>
					<span class="stat-value"><?php echo esc_html( $stats['total_favorites'] ?? 0 ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Favorites', 'apollo-events-manager' ); ?></span>
				</div>
			</div>
			<?php endif; ?>
			
			<?php if ( $events->have_posts() ) : ?>
			<table class="apollo-dashboard-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'apollo-events-manager' ); ?></th>
						<th><?php esc_html_e( 'Location', 'apollo-events-manager' ); ?></th>
						<th><?php esc_html_e( 'Start Date', 'apollo-events-manager' ); ?></th>
						<th><?php esc_html_e( 'Views', 'apollo-events-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-events-manager' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'apollo-events-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $events->have_posts() ) :
						$events->the_post();
						$event_id   = get_the_ID();
						$views      = function_exists( 'apollo_get_event_views' ) ? apollo_get_event_views( $event_id ) : 0;
						$location   = get_post_meta( $event_id, '_event_location', true );
						$start_date = get_post_meta( $event_id, '_event_start_date', true );
						?>
					<tr>
						<td><strong><?php the_title(); ?></strong></td>
						<td><?php echo esc_html( $location ?: '-' ); ?></td>
						<td><?php echo esc_html( $start_date ? date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) : '-' ); ?></td>
						<td><i class="ri-eye-line"></i> <?php echo esc_html( $views ); ?></td>
						<td><span class="status-<?php echo esc_attr( get_post_status() ); ?>"><?php echo esc_html( get_post_status() ); ?></span></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $event_id ) ); ?>" class="apollo-btn apollo-btn-sm">
								<i class="ri-edit-line"></i> <?php esc_html_e( 'Edit', 'apollo-events-manager' ); ?>
							</a>
							<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="apollo-btn apollo-btn-sm">
								<i class="ri-eye-line"></i> <?php esc_html_e( 'View', 'apollo-events-manager' ); ?>
							</a>
						</td>
					</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			
				<?php
				// Pagination
				if ( $events->max_num_pages > 1 ) {
					echo '<div class="apollo-pagination">';
					echo paginate_links(
						array(
							'total'   => $events->max_num_pages,
							'current' => max( 1, get_query_var( 'paged' ) ),
						)
					);
					echo '</div>';
				}
				?>
			
			<?php else : ?>
			<p class="apollo-alert apollo-alert-info">
				<?php esc_html_e( 'You have not created any events yet.', 'apollo-events-manager' ); ?>
			</p>
			<?php endif; ?>
		</div>
		<?php

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Output of events
	 */
	public function output_events( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page'        => 10,
				'orderby'         => 'meta_value',
				'order'           => 'ASC',
				'meta_key'        => '_event_start_date',
				'show_pagination' => false,
				'categories'      => '',
				'event_types'     => '',
				'featured'        => null,
				'cancelled'       => null,
			),
			$atts
		);

		ob_start();

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'paged'          => max( 1, get_query_var( 'paged' ) ),
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
		);

		if ( $atts['orderby'] === 'meta_value' && ! empty( $atts['meta_key'] ) ) {
			$args['meta_key']  = $atts['meta_key'];
			$args['meta_type'] = 'DATETIME';
		}

		// Featured filter
		if ( ! is_null( $atts['featured'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_featured',
				'value'   => '1',
				'compare' => $atts['featured'] ? '=' : '!=',
			);
		}

		// Cancelled filter
		if ( ! is_null( $atts['cancelled'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_cancelled',
				'value'   => '1',
				'compare' => $atts['cancelled'] ? '=' : '!=',
			);
		}

		// Categories filter
		if ( ! empty( $atts['categories'] ) ) {
			$categories          = array_map( 'trim', explode( ',', $atts['categories'] ) );
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_category',
				'field'    => 'slug',
				'terms'    => $categories,
			);
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) :
			echo '<div class="apollo-events-list">';
			while ( $events->have_posts() ) :
				$events->the_post();
				// Track view
				if ( function_exists( 'apollo_record_event_view' ) ) {
					apollo_record_event_view( get_the_ID() );
				}

				// Use event card template (with placeholder/tooltip support)
				$card_template = APOLLO_APRIO_PATH . 'templates/event-card.php';
				if ( file_exists( $card_template ) ) {
					include $card_template;
				} else {
					echo '<div class="apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de card não encontrado', 'apollo-events-manager' ) . '">';
					echo '<span class="apollo-placeholder">' . esc_html__( 'Card de evento não disponível', 'apollo-events-manager' ) . '</span>';
					echo '</div>';
				}
			endwhile;
			echo '</div>';

			// Pagination
			if ( $atts['show_pagination'] && $events->max_num_pages > 1 ) :
				echo '<div class="apollo-pagination">';
				echo paginate_links(
					array(
						'total'   => $events->max_num_pages,
						'current' => max( 1, get_query_var( 'paged' ) ),
					)
				);
				echo '</div>';
			endif;
		else :
			echo '<div class="apollo-alert apollo-alert-info" data-tooltip="' . esc_attr__( 'Nenhum evento encontrado com os filtros aplicados', 'apollo-events-manager' ) . '">';
			echo '<span class="apollo-placeholder">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-events-manager' ) . '</span>';
			echo '</div>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Output single event
	 */
	public function output_event( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$event_id = absint( $atts['id'] );
		if ( ! $event_id ) {
			return '<p class="apollo-alert apollo-alert-danger">' .
					esc_html__( 'Event ID is required.', 'apollo-events-manager' ) .
					'</p>';
		}

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return '<p class="apollo-alert apollo-alert-danger">' .
					esc_html__( 'Event not found.', 'apollo-events-manager' ) .
					'</p>';
		}

		// Track view
		if ( function_exists( 'apollo_record_event_view' ) ) {
			apollo_record_event_view( $event_id );
		}

		ob_start();
		global $post;
		$post = $event;
		setup_postdata( $post );

		// Use single event template (with placeholder/tooltip support)
		$single_template = APOLLO_APRIO_PATH . 'templates/single-event-standalone.php';
		if ( file_exists( $single_template ) ) {
			include $single_template;
		} else {
			echo '<div class="apollo-alert apollo-alert-danger" data-tooltip="' . esc_attr__( 'Template de evento não encontrado', 'apollo-events-manager' ) . '">';
			echo '<span class="apollo-placeholder">' . esc_html__( 'Template de evento não disponível', 'apollo-events-manager' ) . '</span>';
			echo '</div>';
		}

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Event Summary shortcode
	 */
	public function output_event_summary( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'width'    => '320px',
				'align'    => 'left',
				'featured' => null,
				'limit'    => 1,
			),
			$atts
		);

		$limit = intval( $atts['limit'] );
		if ( $limit === 0 ) {
			$limit = 1;
		}

		$selected_ids = array();

		if ( ! empty( $atts['id'] ) ) {
			$selected_ids[] = absint( $atts['id'] );
		} elseif ( is_singular( 'event_listing' ) ) {
				$selected_ids[] = get_queried_object_id();
		} else {
			global $post;
			if ( $post instanceof \WP_Post && $post->post_type === 'event_listing' ) {
				$selected_ids[] = $post->ID;
			}
		}

		$selected_ids = array_values( array_unique( array_filter( array_map( 'absint', $selected_ids ) ) ) );

		if ( $limit > 0 && ! empty( $selected_ids ) ) {
			$selected_ids = array_slice( $selected_ids, 0, $limit );
		}

		$events = array();

		if ( ! empty( $selected_ids ) ) {
			$events = get_posts(
				array(
					'post_type'      => 'event_listing',
					'post_status'    => 'publish',
					'post__in'       => $selected_ids,
					'orderby'        => 'post__in',
					'posts_per_page' => -1,
				)
			);
		} else {
			$query_args = array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => $limit > 0 ? $limit : -1,
			);

			if ( ! is_null( $atts['featured'] ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_featured',
					'value'   => '1',
					'compare' => $atts['featured'] ? '=' : '!=',
				);
			}

			$events = get_posts( $query_args );
		}//end if

		if ( empty( $events ) ) {
			return '<p class="apollo-alert apollo-alert-info">' .
				esc_html__( 'No events found.', 'apollo-events-manager' ) .
				'</p>';
		}

		$width = trim( $atts['width'] );
		$align = strtolower( trim( $atts['align'] ) );

		$style_rules = array();
		if ( $width !== '' ) {
			$style_rules[] = 'max-width:' . esc_attr( $width );
		}

		if ( $align === 'center' ) {
			$style_rules[] = 'margin:0 auto';
			$style_rules[] = 'text-align:center';
		} elseif ( $align === 'right' ) {
			$style_rules[] = 'margin-left:auto';
			$style_rules[] = 'text-align:right';
		} else {
			$style_rules[] = 'text-align:left';
		}

		$card_style = ! empty( $style_rules ) ? implode( ';', $style_rules ) : '';

		ob_start();
		echo '<div class="apollo-event-summary-wrapper">';

		foreach ( $events as $event_post ) {
			$event_id    = $event_post->ID;
			$title       = get_the_title( $event_id );
			$permalink   = get_permalink( $event_id );
			$excerpt_src = get_post_field( 'post_excerpt', $event_id );

			if ( empty( $excerpt_src ) ) {
				$excerpt_src = get_post_field( 'post_content', $event_id );
			}

			$excerpt = $excerpt_src ? wp_trim_words( wp_strip_all_tags( $excerpt_src ), 26, '…' ) : '';

			$start_date_raw = get_post_meta( $event_id, '_event_start_date', true );
			$start_label    = '';

			if ( ! empty( $start_date_raw ) ) {
				$timestamp = strtotime( $start_date_raw );
				if ( $timestamp ) {
					$start_label = date_i18n( get_option( 'date_format' ), $timestamp );
				}
			}

			$location_display = '';
			$location_meta    = get_post_meta( $event_id, '_event_location', true );

			if ( ! empty( $location_meta ) ) {
				if ( strpos( $location_meta, '|' ) !== false ) {
					list($loc_name, $loc_area) = array_map( 'trim', explode( '|', $location_meta, 2 ) );
					$location_display          = trim( $loc_name . ' ' . ( $loc_area ? '(' . $loc_area . ')' : '' ) );
				} else {
					$location_display = trim( $location_meta );
				}
			}

			echo '<div class="apollo-card apollo-event-summary-card" style="' . esc_attr( $card_style ) . '">';
			echo '  <div class="apollo-card-header">';
			echo '      <div class="apollo-card-title"><i class="ri-calendar-event-line"></i> ' . esc_html( $title ) . '</div>';
			if ( $start_label ) {
				echo '      <p class="apollo-card-description"><i class="ri-time-line"></i> ' . esc_html( $start_label ) . '</p>';
			}
			if ( $location_display ) {
				echo '      <p class="apollo-card-description"><i class="ri-map-pin-2-line"></i> ' . esc_html( $location_display ) . '</p>';
			}
			echo '  </div>';
			echo '  <div class="apollo-card-body">';
			if ( $excerpt ) {
				echo '      <p class="apollo-card-text">' . esc_html( $excerpt ) . '</p>';
			} else {
				echo '      <p class="apollo-card-text">' . esc_html__( 'Event details coming soon.', 'apollo-events-manager' ) . '</p>';
			}
			echo '  </div>';
			echo '  <div class="apollo-card-footer">';
			echo '      <a class="apollo-btn apollo-btn-primary apollo-btn-sm" href="' . esc_url( $permalink ) . '">';
			echo '          <i class="ri-arrow-right-line"></i> ' . esc_html__( 'View Event', 'apollo-events-manager' );
			echo '      </a>';
			echo '  </div>';
			echo '</div>';
		}//end foreach

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Past Events shortcode
	 */
	public function output_past_events( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 10,
				'order'    => 'DESC',
				'orderby'  => 'event_start_date',
			),
			$atts
		);

		ob_start();

		$today = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => array( 'publish', 'expired' ),
			'posts_per_page' => absint( $atts['per_page'] ),
			'paged'          => max( 1, get_query_var( 'paged' ) ),
			'order'          => $atts['order'],
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
		);

		if ( $atts['orderby'] === 'event_start_date' ) {
			$args['orderby']   = 'meta_value';
			$args['meta_key']  = '_event_start_date';
			$args['meta_type'] = 'DATETIME';
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) :
			echo '<div class="apollo-past-events">';
			while ( $events->have_posts() ) :
				$events->the_post();
				// Use event card template (with placeholder/tooltip support)
				$card_template = APOLLO_APRIO_PATH . 'templates/event-card.php';
				if ( file_exists( $card_template ) ) {
					include $card_template;
				} else {
					echo '<div class="apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de card não encontrado', 'apollo-events-manager' ) . '">';
					echo '<span class="apollo-placeholder">' . esc_html__( 'Card de evento não disponível', 'apollo-events-manager' ) . '</span>';
					echo '</div>';
				}
			endwhile;
			echo '</div>';
		else :
			echo '<p class="apollo-alert apollo-alert-info">' .
				esc_html__( 'No past events found.', 'apollo-events-manager' ) .
				'</p>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Upcoming Events shortcode
	 */
	public function output_upcoming_events( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 10,
				'order'    => 'ASC',
				'orderby'  => 'event_start_date',
			),
			$atts
		);

		ob_start();

		$today = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'paged'          => max( 1, get_query_var( 'paged' ) ),
			'order'          => $atts['order'],
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);

		if ( $atts['orderby'] === 'event_start_date' ) {
			$args['orderby']   = 'meta_value';
			$args['meta_key']  = '_event_start_date';
			$args['meta_type'] = 'DATETIME';
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) :
			echo '<div class="apollo-upcoming-events">';
			while ( $events->have_posts() ) :
				$events->the_post();
				// Track view
				if ( function_exists( 'apollo_record_event_view' ) ) {
					apollo_record_event_view( get_the_ID() );
				}

				// Use event card template (with placeholder/tooltip support)
				$card_template = APOLLO_APRIO_PATH . 'templates/event-card.php';
				if ( file_exists( $card_template ) ) {
					include $card_template;
				} else {
					echo '<div class="apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de card não encontrado', 'apollo-events-manager' ) . '">';
					echo '<span class="apollo-placeholder">' . esc_html__( 'Card de evento não disponível', 'apollo-events-manager' ) . '</span>';
					echo '</div>';
				}
			endwhile;
			echo '</div>';
		else :
			echo '<p class="apollo-alert apollo-alert-info">' .
				esc_html__( 'No upcoming events found.', 'apollo-events-manager' ) .
				'</p>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Related Events shortcode
	 */
	public function output_related_events( $atts ) {
		global $post;

		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'per_page' => 5,
			),
			$atts
		);

		$event_id = $atts['id'] ? absint( $atts['id'] ) : ( is_singular( 'event_listing' ) ? get_the_ID() : 0 );

		if ( ! $event_id ) {
			return '';
		}

		ob_start();

		// Get event categories and sounds
		$categories = wp_get_post_terms( $event_id, 'event_listing_category', array( 'fields' => 'ids' ) );
		$sounds     = wp_get_post_terms( $event_id, 'event_sounds', array( 'fields' => 'ids' ) );

		$args = array(
			'post_type'      => 'event_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'post__not_in'   => array( $event_id ),
			'orderby'        => 'rand',
		);

		if ( ! empty( $categories ) || ! empty( $sounds ) ) {
			$args['tax_query'] = array( 'relation' => 'OR' );

			if ( ! empty( $categories ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'event_listing_category',
					'field'    => 'term_id',
					'terms'    => $categories,
				);
			}

			if ( ! empty( $sounds ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'event_sounds',
					'field'    => 'term_id',
					'terms'    => $sounds,
				);
			}
		}

		$events = new WP_Query( $args );

		if ( $events->have_posts() ) :
			echo '<div class="apollo-related-events">';
			echo '<h3>' . esc_html__( 'Related Events', 'apollo-events-manager' ) . '</h3>';
			while ( $events->have_posts() ) :
				$events->the_post();
				// Use event card template (with placeholder/tooltip support)
				$card_template = APOLLO_APRIO_PATH . 'templates/event-card.php';
				if ( file_exists( $card_template ) ) {
					include $card_template;
				} else {
					echo '<div class="apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de card não encontrado', 'apollo-events-manager' ) . '">';
					echo '<span class="apollo-placeholder">' . esc_html__( 'Card de evento não disponível', 'apollo-events-manager' ) . '</span>';
					echo '</div>';
				}
			endwhile;
			echo '</div>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Event Register shortcode
	 */
	public function output_event_register( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$event_id = $atts['id'] ? absint( $atts['id'] ) : ( is_singular( 'event_listing' ) ? get_the_ID() : 0 );

		if ( ! $event_id ) {
			return '<p class="apollo-alert apollo-alert-danger">' .
					esc_html__( 'Event ID is required.', 'apollo-events-manager' ) .
					'</p>';
		}

		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return '<p class="apollo-alert apollo-alert-danger">' .
					esc_html__( 'Event not found.', 'apollo-events-manager' ) .
					'</p>';
		}

		ob_start();
		?>
		<div class="apollo-event-register">
			<h3><?php esc_html_e( 'Register for this Event', 'apollo-events-manager' ); ?></h3>
			<p><?php esc_html_e( 'Registration form coming soon.', 'apollo-events-manager' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	// DJ Dashboard methods (similar structure to event_dashboard)
	public function dj_dashboard_handler() {
		// Similar to event_dashboard_handler
	}

	public function dj_dashboard( $atts ) {
		// Similar to event_dashboard but for DJs
		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-alert apollo-alert-info">' .
					esc_html__( 'You need to be signed in to manage your DJ profiles.', 'apollo-events-manager' ) .
					'</div>';
		}

		// TODO: Implement DJ dashboard
		return '<div class="apollo-dj-dashboard">DJ Dashboard coming soon.</div>';
	}

	/**
	 * Output Event DJs - Lista de DJs
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_event_djs( $atts ) {
		$atts = shortcode_atts(
			array(
				'event_id' => 0,
				'per_page' => 12,
				'orderby'  => 'title',
				'order'    => 'ASC',
				'show_bio' => false,
				'layout'   => 'grid', 
			// grid, list, slider
			),
			$atts
		);

		ob_start();

		$args = array(
			'post_type'      => 'event_dj',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
		);

		// Se especificou event_id, buscar DJs daquele evento
		if ( $atts['event_id'] ) {
			$event_id = absint( $atts['event_id'] );
			$dj_ids   = get_post_meta( $event_id, '_event_dj_ids', true );

			if ( ! empty( $dj_ids ) && is_array( $dj_ids ) ) {
				$args['post__in'] = array_map( 'absint', $dj_ids );
				$args['orderby']  = 'post__in'; 
				// Manter ordem do evento
			} else {
				// Nenhum DJ associado
				echo '<p class="apollo-alert apollo-alert-info">' .
					esc_html__( 'No DJs found for this event.', 'apollo-events-manager' ) .
					'</p>';
				return ob_get_clean();
			}
		}

		$djs = new WP_Query( $args );

		if ( $djs->have_posts() ) :
			$layout_class = 'apollo-djs-' . esc_attr( $atts['layout'] );
			echo '<div class="apollo-event-djs ' . $layout_class . '">';

			while ( $djs->have_posts() ) :
				$djs->the_post();
				$dj_id      = get_the_ID();
				$dj_name    = get_the_title();
				$dj_bio     = $atts['show_bio'] ? get_the_excerpt() : '';
				$dj_photo   = get_the_post_thumbnail_url( $dj_id, 'medium' );
				$dj_link    = get_permalink( $dj_id );
				$instagram  = get_post_meta( $dj_id, '_dj_instagram', true );
				$soundcloud = get_post_meta( $dj_id, '_dj_soundcloud', true );

				// Include DJ card template (with placeholder/tooltip support)
				$dj_card_template = APOLLO_APRIO_PATH . 'templates/dj-card.php';
				if ( file_exists( $dj_card_template ) ) {
					include $dj_card_template;
				} else {
					// Fallback basic card with tooltip
					?>
					<div class="apollo-dj-card">
						<?php if ( $dj_photo ) : ?>
						<div class="dj-photo">
							<img src="<?php echo esc_url( $dj_photo ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>">
						</div>
						<?php endif; ?>
						<div class="dj-info">
							<h3><a href="<?php echo esc_url( $dj_link ); ?>"><?php echo esc_html( $dj_name ); ?></a></h3>
							<?php if ( $dj_bio ) : ?>
							<p class="dj-bio"><?php echo esc_html( $dj_bio ); ?></p>
							<?php endif; ?>
							<?php if ( $instagram || $soundcloud ) : ?>
							<div class="dj-social">
								<?php if ( $instagram ) : ?>
								<a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener" class="social-link instagram">
									<i class="ri-instagram-line"></i>
								</a>
								<?php endif; ?>
								<?php if ( $soundcloud ) : ?>
								<a href="<?php echo esc_url( $soundcloud ); ?>" target="_blank" rel="noopener" class="social-link soundcloud">
									<i class="ri-soundcloud-line"></i>
								</a>
								<?php endif; ?>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}//end if
			endwhile;

			echo '</div>';
		else :
			echo '<p class="apollo-alert apollo-alert-info">' .
				esc_html__( 'No DJs found.', 'apollo-events-manager' ) .
				'</p>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Output Single Event DJ
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_event_dj( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'          => 0,
				'show_events' => true,
				'show_bio'    => true,
				'show_social' => true,
			),
			$atts
		);

		$dj_id = $atts['id'] ? absint( $atts['id'] ) : ( is_singular( 'event_dj' ) ? get_the_ID() : 0 );

		if ( ! $dj_id ) {
			return '<div class="apollo-alert apollo-alert-danger" data-tooltip="' . esc_attr__( 'ID do DJ é obrigatório', 'apollo-events-manager' ) . '">' .
					'<span class="apollo-placeholder">' . esc_html__( 'ID do DJ é obrigatório.', 'apollo-events-manager' ) . '</span>' .
					'</div>';
		}

		$dj = get_post( $dj_id );
		if ( ! $dj || $dj->post_type !== 'event_dj' ) {
			return '<div class="apollo-alert apollo-alert-danger" data-tooltip="' . esc_attr__( 'DJ não encontrado ou tipo inválido', 'apollo-events-manager' ) . '">' .
					'<span class="apollo-placeholder">' . esc_html__( 'DJ não encontrado.', 'apollo-events-manager' ) . '</span>' .
					'</div>';
		}

		ob_start();

		// Include single DJ template (with placeholder/tooltip support)
		$dj_template = APOLLO_APRIO_PATH . 'templates/single-event_dj.php';
		if ( file_exists( $dj_template ) ) {
			include $dj_template;
		} else {
			echo '<div class="apollo-single-dj apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de DJ não encontrado', 'apollo-events-manager' ) . '">';
			echo '<h2>' . esc_html( $dj->post_title ) . '</h2>';
			if ( $atts['show_bio'] ) {
				echo '<div class="dj-bio">' . wp_kses_post( $dj->post_content ) . '</div>';
			} else {
				echo '<span class="apollo-placeholder">' . esc_html__( 'Biografia não disponível', 'apollo-events-manager' ) . '</span>';
			}
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Output Single Event DJ Page
	 * Same as output_event_dj but for full page context
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_single_event_dj( $atts ) {
		// Use the same implementation as output_event_dj
		return $this->output_event_dj( $atts );
	}

	// Local Dashboard methods
	public function local_dashboard_handler() {
		// Similar to event_dashboard_handler
	}

	public function local_dashboard( $atts ) {
		// Similar to event_dashboard but for Locals
		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-alert apollo-alert-info">' .
					esc_html__( 'You need to be signed in to manage your venues.', 'apollo-events-manager' ) .
					'</div>';
		}

		// TODO: Implement Local dashboard
		return '<div class="apollo-local-dashboard">Venue Dashboard coming soon.</div>';
	}

	/**
	 * Output Event Locals/Venues - Lista de Locais
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_event_locals( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page'         => 12,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'show_next_events' => true,
				'region'           => '',
				'layout'           => 'grid', 
			// grid, list, map
			),
			$atts
		);

		ob_start();

		$args = array(
			'post_type'      => 'event_local',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['per_page'] ),
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
		);

		// Filter by region if specified
		if ( ! empty( $atts['region'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_local_region',
				'value'   => sanitize_text_field( $atts['region'] ),
				'compare' => 'LIKE',
			);
		}

		$locals = new WP_Query( $args );

		if ( $locals->have_posts() ) :
			$layout_class = 'apollo-locals-' . esc_attr( $atts['layout'] );
			echo '<div class="apollo-event-locals ' . $layout_class . '">';

			while ( $locals->have_posts() ) :
				$locals->the_post();
				$local_id       = get_the_ID();
				$local_name     = get_the_title();
				$local_region   = get_post_meta( $local_id, '_local_region', true );
				$local_address  = get_post_meta( $local_id, '_local_address', true );
				$local_photo    = get_the_post_thumbnail_url( $local_id, 'medium' );
				$local_link     = get_permalink( $local_id );
				$local_capacity = get_post_meta( $local_id, '_local_capacity', true );

				// Get next events for this local if requested
				$next_events = array();
				if ( $atts['show_next_events'] ) {
					$today        = current_time( 'Y-m-d' );
					$events_query = new WP_Query(
						array(
							'post_type'      => 'event_listing',
							'post_status'    => 'publish',
							'posts_per_page' => 3,
							'orderby'        => 'meta_value',
							'order'          => 'ASC',
							'meta_key'       => '_event_start_date',
							'meta_type'      => 'DATETIME',
							'meta_query'     => array(
								array(
									'key'     => '_event_local_ids',
									'value'   => '"' . $local_id . '"',
									'compare' => 'LIKE',
								),
								array(
									'key'     => '_event_start_date',
									'value'   => $today,
									'compare' => '>=',
									'type'    => 'DATE',
								),
							),
						)
					);

					if ( $events_query->have_posts() ) {
						while ( $events_query->have_posts() ) {
							$events_query->the_post();
							$next_events[] = array(
								'id'    => get_the_ID(),
								'title' => get_the_title(),
								'date'  => get_post_meta( get_the_ID(), '_event_start_date', true ),
								'link'  => get_permalink(),
							);
						}
						wp_reset_postdata();
					}
				}//end if

				// Include local card template (with placeholder/tooltip support)
				$local_card_template = APOLLO_APRIO_PATH . 'templates/local-card.php';
				if ( file_exists( $local_card_template ) ) {
					include $local_card_template;
				} else {
					// Fallback basic card with tooltip
					?>
					<div class="apollo-local-card">
						<?php if ( $local_photo ) : ?>
						<div class="local-photo">
							<img src="<?php echo esc_url( $local_photo ); ?>" alt="<?php echo esc_attr( $local_name ); ?>">
						</div>
						<?php endif; ?>
						<div class="local-info">
							<h3><a href="<?php echo esc_url( $local_link ); ?>"><?php echo esc_html( $local_name ); ?></a></h3>
							<?php if ( $local_region ) : ?>
							<p class="local-region"><i class="ri-map-pin-line"></i> <?php echo esc_html( $local_region ); ?></p>
							<?php endif; ?>
							<?php if ( $local_capacity ) : ?>
							<p class="local-capacity"><i class="ri-group-line"></i> <?php echo esc_html( $local_capacity ); ?> pessoas</p>
							<?php endif; ?>
							
							<?php if ( ! empty( $next_events ) ) : ?>
							<div class="local-next-events">
								<h4><?php esc_html_e( 'Próximos Eventos', 'apollo-events-manager' ); ?></h4>
								<ul>
								<?php foreach ( $next_events as $event ) : ?>
									<li>
										<a href="<?php echo esc_url( $event['link'] ); ?>">
											<?php echo esc_html( $event['title'] ); ?>
											<span class="event-date"><?php echo esc_html( date( 'd/m', strtotime( $event['date'] ) ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
								</ul>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}//end if
			endwhile;

			echo '</div>';
		else :
			echo '<p class="apollo-alert apollo-alert-info">' .
				esc_html__( 'No venues found.', 'apollo-events-manager' ) .
				'</p>';
		endif;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Output Single Event Local
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_event_local( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'               => 0,
				'show_events'      => true,
				'show_description' => true,
				'show_map'         => true,
			),
			$atts
		);

		$local_id = $atts['id'] ? absint( $atts['id'] ) : ( is_singular( 'event_local' ) ? get_the_ID() : 0 );

		if ( ! $local_id ) {
			return '<div class="apollo-alert apollo-alert-danger" data-tooltip="' . esc_attr__( 'ID do local é obrigatório', 'apollo-events-manager' ) . '">' .
					'<span class="apollo-placeholder">' . esc_html__( 'ID do local é obrigatório.', 'apollo-events-manager' ) . '</span>' .
					'</div>';
		}

		$local = get_post( $local_id );
		if ( ! $local || $local->post_type !== 'event_local' ) {
			return '<div class="apollo-alert apollo-alert-danger" data-tooltip="' . esc_attr__( 'Local não encontrado ou tipo inválido', 'apollo-events-manager' ) . '">' .
					'<span class="apollo-placeholder">' . esc_html__( 'Local não encontrado.', 'apollo-events-manager' ) . '</span>' .
					'</div>';
		}

		ob_start();

		// Include single local template (with placeholder/tooltip support)
		$local_template = APOLLO_APRIO_PATH . 'templates/single-event_local.php';
		if ( file_exists( $local_template ) ) {
			include $local_template;
		} else {
			echo '<div class="apollo-single-local apollo-alert apollo-alert-warning" data-tooltip="' . esc_attr__( 'Template de local não encontrado', 'apollo-events-manager' ) . '">';
			echo '<h2>' . esc_html( $local->post_title ) . '</h2>';
			if ( $atts['show_description'] ) {
				echo '<div class="local-description">' . wp_kses_post( $local->post_content ) . '</div>';
			} else {
				echo '<span class="apollo-placeholder">' . esc_html__( 'Descrição não disponível', 'apollo-events-manager' ) . '</span>';
			}
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Output Single Event Local Page
	 * Same as output_event_local but for full page context
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function output_single_event_local( $atts ) {
		// Use the same implementation as output_event_local
		return $this->output_event_local( $atts );
	}
}

// Initialize
new Apollo_Events_Shortcodes();

