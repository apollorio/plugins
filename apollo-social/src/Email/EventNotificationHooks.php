<?php
/**
 * Event Notification Hooks
 *
 * Hooks into event changes and triggers email notifications
 * for users who have expressed interest in events.
 *
 * @package Apollo_Social
 * @since   2.0.0
 */

namespace Apollo\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Notification Hooks
 *
 * Listens for event changes and sends notifications:
 * - Event edited
 * - Event cancelled
 * - Event DJs updated
 * - Event category changed
 * - Guest responses
 */
class EventNotificationHooks {

	/**
	 * Tracked meta keys for change detection.
	 */
	const TRACKED_META = array(
		'event_date',
		'event_time',
		'event_venue',
		'event_address',
		'event_djs',
		'event_status',
		'event_price',
	);

	/**
	 * Event post types to monitor.
	 */
	const EVENT_POST_TYPES = array( 'event', 'event_listing', 'apollo_event' );

	/**
	 * Initialize hooks.
	 */
	public static function init(): void {
		// Event post updates
		add_action( 'post_updated', array( self::class, 'on_event_updated' ), 10, 3 );
		add_action( 'save_post', array( self::class, 'on_event_saved' ), 20, 3 );

		// Meta changes
		add_action( 'updated_post_meta', array( self::class, 'on_meta_updated' ), 10, 4 );

		// Taxonomy changes
		add_action( 'set_object_terms', array( self::class, 'on_terms_changed' ), 10, 6 );

		// Event status changes (trashed = cancelled)
		add_action( 'transition_post_status', array( self::class, 'on_status_transition' ), 10, 3 );

		// Guest responses (custom hook from events manager)
		add_action( 'apollo_event_rsvp', array( self::class, 'on_guest_response' ), 10, 4 );
		add_action( 'apollo_event_attendance_changed', array( self::class, 'on_guest_response' ), 10, 4 );

		// Interest added (from apollo-events-manager)
		add_action( 'apollo_interest_added', array( self::class, 'on_interest_added' ), 10, 2 );
	}

	/**
	 * Handle event post updates.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  Post after update.
	 * @param WP_Post $post_before Post before update.
	 */
	public static function on_event_updated( int $post_id, $post_after, $post_before ): void {
		if ( ! self::is_event_post_type( $post_after->post_type ) ) {
			return;
		}

		// Check if content/title changed
		$title_changed   = $post_before->post_title !== $post_after->post_title;
		$content_changed = $post_before->post_content !== $post_after->post_content;

		if ( ! $title_changed && ! $content_changed ) {
			return;
		}

		// Debounce: don't send if we just sent for this event
		$last_notif = get_transient( "apollo_event_notif_{$post_id}" );
		if ( $last_notif ) {
			return;
		}
		set_transient( "apollo_event_notif_{$post_id}", time(), 300 ); // 5 min debounce

		self::send_event_changed_notification( $post_id, 'content_updated', array(
			'title_changed'   => $title_changed,
			'content_changed' => $content_changed,
		) );
	}

	/**
	 * Handle event saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public static function on_event_saved( int $post_id, $post, bool $update ): void {
		if ( ! $update ) {
			return; // Only handle updates, not new posts
		}

		if ( ! self::is_event_post_type( $post->post_type ) ) {
			return;
		}

		// This is handled by on_event_updated, skip
	}

	/**
	 * Handle meta updates.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New meta value.
	 */
	public static function on_meta_updated( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		$post = get_post( $post_id );

		if ( ! $post || ! self::is_event_post_type( $post->post_type ) ) {
			return;
		}

		// Check if this is a tracked meta key
		if ( ! in_array( $meta_key, self::TRACKED_META, true ) ) {
			return;
		}

		// Debounce
		$debounce_key = "apollo_meta_notif_{$post_id}_{$meta_key}";
		if ( get_transient( $debounce_key ) ) {
			return;
		}
		set_transient( $debounce_key, time(), 300 );

		// Special handling for event_djs
		if ( $meta_key === 'event_djs' ) {
			self::send_djs_update_notification( $post_id );
			return;
		}

		// Special handling for event_status (cancelled)
		if ( $meta_key === 'event_status' && in_array( $meta_value, array( 'cancelled', 'canceled' ), true ) ) {
			self::send_cancelled_notification( $post_id );
			return;
		}

		// General event change
		self::send_event_changed_notification( $post_id, 'meta_updated', array(
			'changed_field' => $meta_key,
			'new_value'     => $meta_value,
		) );
	}

	/**
	 * Handle taxonomy term changes.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Array of term taxonomy IDs.
	 * @param array  $tt_ids     Array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms.
	 * @param array  $old_tt_ids Old term taxonomy IDs.
	 */
	public static function on_terms_changed( int $object_id, array $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
		$post = get_post( $object_id );

		if ( ! $post || ! self::is_event_post_type( $post->post_type ) ) {
			return;
		}

		// Only track event_listing taxonomy
		if ( ! in_array( $taxonomy, array( 'event_category', 'event_listing_category', 'event_type' ), true ) ) {
			return;
		}

		// Check if terms actually changed
		if ( $tt_ids === $old_tt_ids ) {
			return;
		}

		// Debounce
		$debounce_key = "apollo_tax_notif_{$object_id}_{$taxonomy}";
		if ( get_transient( $debounce_key ) ) {
			return;
		}
		set_transient( $debounce_key, time(), 300 );

		self::send_category_update_notification( $object_id, $taxonomy, $terms, $old_tt_ids );
	}

	/**
	 * Handle post status transitions.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post object.
	 */
	public static function on_status_transition( string $new_status, string $old_status, $post ): void {
		if ( ! self::is_event_post_type( $post->post_type ) ) {
			return;
		}

		// Event cancelled (trashed or draft from publish)
		if ( $new_status === 'trash' && $old_status === 'publish' ) {
			self::send_cancelled_notification( $post->ID );
		}
	}

	/**
	 * Handle guest response to event.
	 *
	 * @param int    $event_id    Event ID.
	 * @param int    $user_id     User who responded.
	 * @param string $response    Response (going, maybe, not_going).
	 * @param array  $extra_data  Extra data.
	 */
	public static function on_guest_response( int $event_id, int $user_id, string $response, array $extra_data = array() ): void {
		$event = get_post( $event_id );

		if ( ! $event ) {
			return;
		}

		// Get event organizer/author
		$organizer_id = $event->post_author;

		if ( ! $organizer_id || $organizer_id === $user_id ) {
			return; // Don't notify self
		}

		$guest = get_userdata( $user_id );
		if ( ! $guest ) {
			return;
		}

		$response_labels = array(
			'going'     => __( 'confirmou presença', 'apollo-social' ),
			'maybe'     => __( 'marcou "talvez"', 'apollo-social' ),
			'not_going' => __( 'não poderá ir', 'apollo-social' ),
		);

		$response_label = $response_labels[ $response ] ?? $response;

		UnifiedEmailService::send( array(
			'to'       => $organizer_id,
			'type'     => 'event_response',
			'subject'  => sprintf(
				/* translators: %1$s: guest name, %2$s: event name */
				__( '%1$s %2$s no evento %3$s', 'apollo-social' ),
				$guest->display_name,
				$response_label,
				$event->post_title
			),
			'body'     => self::get_response_email_body( $event, $guest, $response, $response_label ),
			'template' => 'event_response',
			'data'     => array(
				'event_id'       => $event_id,
				'event_name'     => $event->post_title,
				'event_url'      => get_permalink( $event_id ),
				'guest_name'     => $guest->display_name,
				'guest_id'       => $user_id,
				'response'       => $response,
				'response_label' => $response_label,
			),
		) );
	}

	/**
	 * Handle interest added to event.
	 *
	 * @param int $event_id Event ID.
	 * @param int $user_id  User ID.
	 */
	public static function on_interest_added( int $event_id, int $user_id ): void {
		// Optionally notify organizer or log
		do_action( 'apollo_event_interest_logged', $event_id, $user_id );
	}

	/**
	 * Send event changed notification.
	 *
	 * @param int    $event_id    Event ID.
	 * @param string $change_type Type of change.
	 * @param array  $details     Change details.
	 */
	private static function send_event_changed_notification( int $event_id, string $change_type, array $details ): void {
		$event = get_post( $event_id );

		if ( ! $event ) {
			return;
		}

		UnifiedEmailService::notify_event_interested( $event_id, 'event_changed', array(
			'subject'  => sprintf(
				/* translators: %s: event name */
				__( '📢 Atualização no evento: %s', 'apollo-social' ),
				$event->post_title
			),
			'body'     => self::get_event_changed_email_body( $event, $change_type, $details ),
			'template' => 'event_update',
			'data'     => array(
				'change_type' => $change_type,
				'details'     => $details,
			),
		) );
	}

	/**
	 * Send DJs update notification.
	 *
	 * @param int $event_id Event ID.
	 */
	private static function send_djs_update_notification( int $event_id ): void {
		$event = get_post( $event_id );

		if ( ! $event ) {
			return;
		}

		$djs = get_post_meta( $event_id, 'event_djs', true );

		UnifiedEmailService::notify_event_interested( $event_id, 'event_djs_update', array(
			'subject'  => sprintf(
				/* translators: %s: event name */
				__( '🎧 Novidade na programação da %s!', 'apollo-social' ),
				$event->post_title
			),
			'body'     => self::get_djs_update_email_body( $event, $djs ),
			'template' => 'event_djs_update',
			'data'     => array(
				'event_djs' => $djs,
			),
		) );
	}

	/**
	 * Send cancelled notification.
	 *
	 * @param int $event_id Event ID.
	 */
	private static function send_cancelled_notification( int $event_id ): void {
		$event = get_post( $event_id );

		if ( ! $event ) {
			return;
		}

		UnifiedEmailService::notify_event_interested( $event_id, 'event_cancelled', array(
			'subject'  => sprintf(
				/* translators: %s: event name */
				__( '🚫 Evento cancelado: %s', 'apollo-social' ),
				$event->post_title
			),
			'body'     => self::get_cancelled_email_body( $event ),
			'template' => 'event_cancelled',
		) );
	}

	/**
	 * Send category update notification.
	 *
	 * @param int    $event_id     Event ID.
	 * @param string $taxonomy     Taxonomy.
	 * @param array  $new_terms    New term IDs.
	 * @param array  $old_term_ids Old term taxonomy IDs.
	 */
	private static function send_category_update_notification( int $event_id, string $taxonomy, array $new_terms, array $old_term_ids ): void {
		$event = get_post( $event_id );

		if ( ! $event ) {
			return;
		}

		// Get term names
		$new_term_names = array();
		foreach ( $new_terms as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$new_term_names[] = $term->name;
			}
		}

		UnifiedEmailService::notify_event_interested( $event_id, 'event_category_update', array(
			'subject'  => sprintf(
				/* translators: %s: event name */
				__( '📂 Categoria atualizada: %s', 'apollo-social' ),
				$event->post_title
			),
			'body'     => self::get_category_update_email_body( $event, $new_term_names ),
			'template' => 'event_category_update',
			'data'     => array(
				'new_categories' => implode( ', ', $new_term_names ),
			),
		) );
	}

	/**
	 * Check if post type is an event type.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	private static function is_event_post_type( string $post_type ): bool {
		$event_types = apply_filters( 'apollo_event_post_types', self::EVENT_POST_TYPES );
		return in_array( $post_type, $event_types, true );
	}

	/**
	 * Get event changed email body.
	 *
	 * @param WP_Post $event       Event post.
	 * @param string  $change_type Change type.
	 * @param array   $details     Details.
	 * @return string
	 */
	private static function get_event_changed_email_body( $event, string $change_type, array $details ): string {
		$event_url = get_permalink( $event->ID );

		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #333;">Atualização no evento</h2>

			<div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0;">
				<h3 style="margin: 0 0 10px; color: #333;"><?php echo esc_html( $event->post_title ); ?></h3>
				<p style="margin: 0; color: #666;">
					<?php esc_html_e( 'O evento que você marcou interesse foi atualizado.', 'apollo-social' ); ?>
				</p>
			</div>

			<p style="color: #666;">
				<?php esc_html_e( 'Confira as novidades e não perca nenhuma informação importante.', 'apollo-social' ); ?>
			</p>

			<a href="<?php echo esc_url( $event_url ); ?>"
				style="display: inline-block; background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin-top: 16px;">
				<?php esc_html_e( 'Ver evento', 'apollo-social' ); ?>
			</a>

			<p style="margin-top: 30px; font-size: 12px; color: #888;">
				<?php esc_html_e( 'Você recebeu este email porque marcou interesse neste evento.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get DJs update email body.
	 *
	 * @param WP_Post $event Event post.
	 * @param mixed   $djs   DJs value.
	 * @return string
	 */
	private static function get_djs_update_email_body( $event, $djs ): string {
		$event_url = get_permalink( $event->ID );
		$djs_list  = is_array( $djs ) ? implode( ', ', $djs ) : $djs;

		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #333;">🎧 Novidade na programação!</h2>

			<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; margin: 20px 0; color: #fff;">
				<h3 style="margin: 0 0 10px; color: #fff;"><?php echo esc_html( $event->post_title ); ?></h3>
				<?php if ( $djs_list ) : ?>
					<p style="margin: 0; opacity: 0.9;">
						<strong>Line-up:</strong> <?php echo esc_html( $djs_list ); ?>
					</p>
				<?php endif; ?>
			</div>

			<p style="color: #666;">
				<?php esc_html_e( 'A programação do evento que você tem interesse foi atualizada. Não perca!', 'apollo-social' ); ?>
			</p>

			<a href="<?php echo esc_url( $event_url ); ?>"
				style="display: inline-block; background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin-top: 16px;">
				<?php esc_html_e( 'Ver evento completo', 'apollo-social' ); ?>
			</a>

			<p style="margin-top: 30px; font-size: 12px; color: #888;">
				<?php esc_html_e( 'Você recebeu este email porque marcou interesse neste evento.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get cancelled email body.
	 *
	 * @param WP_Post $event Event post.
	 * @return string
	 */
	private static function get_cancelled_email_body( $event ): string {
		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #d32f2f;">🚫 Evento Cancelado</h2>

			<div style="background: #ffebee; border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #d32f2f;">
				<h3 style="margin: 0 0 10px; color: #333;"><?php echo esc_html( $event->post_title ); ?></h3>
				<p style="margin: 0; color: #666;">
					<?php esc_html_e( 'Infelizmente, este evento foi cancelado pelo organizador.', 'apollo-social' ); ?>
				</p>
			</div>

			<p style="color: #666;">
				<?php esc_html_e( 'Sentimos muito por essa notícia. Fique de olho em outros eventos que possam te interessar.', 'apollo-social' ); ?>
			</p>

			<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>"
				style="display: inline-block; background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin-top: 16px;">
				<?php esc_html_e( 'Explorar outros eventos', 'apollo-social' ); ?>
			</a>

			<p style="margin-top: 30px; font-size: 12px; color: #888;">
				<?php esc_html_e( 'Você recebeu este email porque marcou interesse neste evento.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get category update email body.
	 *
	 * @param WP_Post $event          Event post.
	 * @param array   $new_categories New category names.
	 * @return string
	 */
	private static function get_category_update_email_body( $event, array $new_categories ): string {
		$event_url = get_permalink( $event->ID );

		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #333;">📂 Categoria atualizada</h2>

			<div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0;">
				<h3 style="margin: 0 0 10px; color: #333;"><?php echo esc_html( $event->post_title ); ?></h3>
				<?php if ( ! empty( $new_categories ) ) : ?>
					<p style="margin: 0; color: #666;">
						<strong><?php esc_html_e( 'Nova categoria:', 'apollo-social' ); ?></strong>
						<?php echo esc_html( implode( ', ', $new_categories ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<a href="<?php echo esc_url( $event_url ); ?>"
				style="display: inline-block; background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin-top: 16px;">
				<?php esc_html_e( 'Ver evento', 'apollo-social' ); ?>
			</a>

			<p style="margin-top: 30px; font-size: 12px; color: #888;">
				<?php esc_html_e( 'Você recebeu este email porque marcou interesse neste evento.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get response email body.
	 *
	 * @param WP_Post $event          Event post.
	 * @param WP_User $guest          Guest user.
	 * @param string  $response       Response type.
	 * @param string  $response_label Response label.
	 * @return string
	 */
	private static function get_response_email_body( $event, $guest, string $response, string $response_label ): string {
		$event_url = get_permalink( $event->ID );

		$emoji = array(
			'going'     => '✅',
			'maybe'     => '🤔',
			'not_going' => '❌',
		);

		ob_start();
		?>
		<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #333;"><?php echo $emoji[ $response ] ?? '📬'; ?> Resposta de convidado</h2>

			<div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0;">
				<p style="margin: 0 0 10px; color: #333; font-size: 16px;">
					<strong><?php echo esc_html( $guest->display_name ); ?></strong>
					<?php echo esc_html( $response_label ); ?>
				</p>
				<p style="margin: 0; color: #666;">
					<?php esc_html_e( 'Evento:', 'apollo-social' ); ?>
					<strong><?php echo esc_html( $event->post_title ); ?></strong>
				</p>
			</div>

			<a href="<?php echo esc_url( $event_url ); ?>"
				style="display: inline-block; background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin-top: 16px;">
				<?php esc_html_e( 'Ver lista de convidados', 'apollo-social' ); ?>
			</a>

			<p style="margin-top: 30px; font-size: 12px; color: #888;">
				<?php esc_html_e( 'Você recebeu este email porque é organizador deste evento.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}
}

// Initialize hooks
EventNotificationHooks::init();
