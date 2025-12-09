<?php
declare(strict_types=1);

/**
 * CENA-RIO Moderation System
 *
 * Handles moderation queue and approve/reject actions for CENA events.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CENA-RIO Moderation class
 */
class Apollo_Cena_Rio_Moderation {

	/**
	 * Initialize
	 */
	public static function init(): void {
		// Register shortcode for moderation queue
		add_shortcode( 'apollo_cena_moderation_queue', array( __CLASS__, 'render_moderation_queue' ) );

		// Handle POST actions
		add_action( 'admin_post_apollo_cena_approve', array( __CLASS__, 'handle_approve' ) );
		add_action( 'admin_post_apollo_cena_reject', array( __CLASS__, 'handle_reject' ) );

		// AJAX handlers for front-end moderation
		add_action( 'wp_ajax_apollo_cena_approve', array( __CLASS__, 'ajax_approve' ) );
		add_action( 'wp_ajax_apollo_cena_reject', array( __CLASS__, 'ajax_reject' ) );

		// REST API endpoints
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		// Filter: Exclude non-approved CENA-RIO events from public calendar queries
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_cena_rio_events' ), 20 );
	}

	/**
	 * Filter out non-approved CENA-RIO events from public event listings.
	 *
	 * CENA-RIO events should only appear in public calendar when _apollo_cena_status = 'approved'.
	 * Events with status 'expected' or 'confirmed' are still in moderation workflow.
	 *
	 * @param WP_Query $query The query object.
	 */
	public static function filter_cena_rio_events( $query ): void {
		// Only filter on front-end, main queries, and event_listing post type
		if ( is_admin() ) {
			return;
		}

		// Check if querying event_listing
		$post_type = $query->get( 'post_type' );
		if ( 'event_listing' !== $post_type ) {
			return;
		}

		// Get existing meta_query or create new
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		// Add filter: Exclude CENA-RIO events unless approved
		// Logic: Show event IF (not from cena-rio) OR (cena-rio AND approved)
		$meta_query[] = array(
			'relation' => 'OR',
			// Regular events (no _apollo_source meta)
			array(
				'key'     => '_apollo_source',
				'compare' => 'NOT EXISTS',
			),
			// Non-cena-rio sources
			array(
				'key'     => '_apollo_source',
				'value'   => 'cena-rio',
				'compare' => '!=',
			),
			// Approved CENA-RIO events only
			array(
				'relation' => 'AND',
				array(
					'key'   => '_apollo_source',
					'value' => 'cena-rio',
				),
				array(
					'key'   => '_apollo_cena_status',
					'value' => 'approved',
				),
			),
		);

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Register REST API routes
	 */
	public static function register_rest_routes(): void {
		// Get moderation queue
		register_rest_route(
			'apollo/v1',
			'/cena-rio/queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_queue' ),
				'permission_callback' => array( __CLASS__, 'check_moderation_permission' ),
			)
		);

		// Approve event
		register_rest_route(
			'apollo/v1',
			'/cena-rio/approve/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_approve_event' ),
				'permission_callback' => array( __CLASS__, 'check_moderation_permission' ),
				'args'                => array(
					'id'   => array(
						'required' => true,
						'type'     => 'integer',
					),
					'note' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Reject event
		register_rest_route(
			'apollo/v1',
			'/cena-rio/reject/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_reject_event' ),
				'permission_callback' => array( __CLASS__, 'check_moderation_permission' ),
				'args'                => array(
					'id'     => array(
						'required' => true,
						'type'     => 'integer',
					),
					'reason' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);
	}

	/**
	 * Check if user can moderate
	 *
	 * @return bool True if user has permission.
	 */
	public static function check_moderation_permission(): bool {
		return Apollo_Cena_Rio_Roles::user_can_moderate();
	}

	/**
	 * REST API: Get moderation queue
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public static function rest_get_queue( $request ): WP_REST_Response {
		$events = self::get_pending_events();

		$formatted = array();
		foreach ( $events as $event ) {
			$formatted[] = self::format_event_for_api( $event );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'total'   => count( $formatted ),
				'events'  => $formatted,
			),
			200
		);
	}

	/**
	 * REST API: Approve event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public static function rest_approve_event( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$note    = $request->get_param( 'note' ) ?? '';

		$result = self::approve_event( $post_id, $note );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Evento aprovado e publicado com sucesso!', 'apollo-core' ),
				'event'   => self::format_event_for_api( get_post( $post_id ) ),
			),
			200
		);
	}

	/**
	 * REST API: Reject event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public static function rest_reject_event( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$reason  = $request->get_param( 'reason' ) ?? '';

		$result = self::reject_event( $post_id, $reason );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Evento rejeitado.', 'apollo-core' ),
			),
			200
		);
	}

	/**
	 * Render moderation queue shortcode
	 *
	 * @return string HTML output.
	 */
	public static function render_moderation_queue(): string {
		// Check permission
		if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
			return '<div class="apollo-cena-notice" style="padding:20px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:8px;color:#991b1b">
				<strong>Acesso Negado</strong><br>
				Você não tem permissão para acessar a fila de moderação.
			</div>';
		}

		// Get pending events
		$events = self::get_pending_events();

		ob_start();
		?>
		<div class="apollo-cena-moderation-queue" style="max-width:1200px;margin:0 auto">
			<div style="margin-bottom:24px">
				<h2 style="font-size:24px;font-weight:800;color:#0f172a;margin:0 0 8px 0">
					Fila de Moderação Cena::Rio
				</h2>
				<p style="color:#64748b;margin:0">
					<?php echo esc_html( sprintf( __( '%d evento(s) aguardando aprovação', 'apollo-core' ), count( $events ) ) ); ?>
				</p>
			</div>

			<?php if ( empty( $events ) ) : ?>
				<div style="padding:40px;text-align:center;background:#f8fafc;border-radius:12px;border:2px dashed #cbd5e1">
					<i class="ri-checkbox-circle-line" style="font-size:48px;color:#94a3b8;margin-bottom:12px;display:block"></i>
					<div style="font-weight:700;color:#475569;margin-bottom:4px">Nenhum evento pendente</div>
					<div style="color:#94a3b8;font-size:14px">Todos os eventos foram moderados!</div>
				</div>
			<?php else : ?>
				<div class="moderation-grid" style="display:flex;flex-direction:column;gap:16px">
					<?php foreach ( $events as $event ) : ?>
						<?php
						$post_id      = $event->ID;
						$title        = get_the_title( $post_id );
						$author_id    = $event->post_author;
						$author       = get_userdata( $author_id );
						$date         = get_the_date( 'd/m/Y', $post_id );
						$start_date   = get_post_meta( $post_id, '_event_start_date', true );
						$venue        = get_post_meta( $post_id, '_event_venue_name', true );
						$submitted_at = get_post_meta( $post_id, '_apollo_cena_submitted_at', true );
						?>
						<div class="event-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.08);border-left:4px solid #f97316" data-event-id="<?php echo esc_attr( $post_id ); ?>">
							<div style="display:flex;gap:20px;align-items:start;justify-content:space-between;flex-wrap:wrap">
								<!-- Event Info -->
								<div style="flex:1;min-width:250px">
									<h3 style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 8px 0">
										<?php echo esc_html( $title ); ?>
									</h3>
									<div style="display:flex;flex-direction:column;gap:4px;font-size:14px;color:#64748b">
										<div style="display:flex;align-items:center;gap:8px">
											<i class="ri-calendar-line"></i>
											<span><strong>Data:</strong> <?php echo esc_html( $start_date ?: $date ); ?></span>
										</div>
										<?php if ( $venue ) : ?>
											<div style="display:flex;align-items:center;gap:8px">
												<i class="ri-map-pin-line"></i>
												<span><strong>Local:</strong> <?php echo esc_html( $venue ); ?></span>
											</div>
										<?php endif; ?>
										<div style="display:flex;align-items:center;gap:8px">
											<i class="ri-user-line"></i>
											<span><strong>Enviado por:</strong> <?php echo esc_html( $author ? $author->display_name : 'Desconhecido' ); ?></span>
										</div>
										<?php if ( $submitted_at ) : ?>
											<div style="display:flex;align-items:center;gap:8px">
												<i class="ri-time-line"></i>
												<span><strong>Em:</strong> <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $submitted_at ) ) ); ?></span>
											</div>
										<?php endif; ?>
									</div>
									<div style="margin-top:12px">
										<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" target="_blank" class="btn-view" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f1f5f9;color:#475569;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600">
											<i class="ri-external-link-line"></i>
											Ver no Admin
										</a>
									</div>
								</div>

								<!-- Actions -->
								<div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<?php wp_nonce_field( 'apollo_cena_approve_' . $post_id, 'cena_nonce' ); ?>
										<input type="hidden" name="action" value="apollo_cena_approve">
										<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
										<button type="submit" class="btn-approve" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#10b981;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">
											<i class="ri-check-line"></i>
											Aprovar
										</button>
									</form>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('Tem certeza que deseja rejeitar este evento?')">
										<?php wp_nonce_field( 'apollo_cena_reject_' . $post_id, 'cena_nonce' ); ?>
										<input type="hidden" name="action" value="apollo_cena_reject">
										<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
										<button type="submit" class="btn-reject" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#fff;color:#dc2626;border:1px solid #fecaca;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">
											<i class="ri-close-line"></i>
											Rejeitar
										</button>
									</form>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<style>
			.btn-approve:hover { background:#059669; }
			.btn-reject:hover { background:#fef2f2; border-color:#dc2626; }
			.btn-view:hover { background:#e2e8f0; color:#1e293b; }
			@media (max-width: 768px) {
				.event-card > div { flex-direction: column; }
				.event-card .btn-approve, .event-card .btn-reject { width: 100%; justify-content: center; }
			}
		</style>
		<?php
		$output = ob_get_clean();
		return $output !== false ? $output : '';
	}

	/**
	 * Get pending CENA events
	 *
	 * @return array Array of WP_Post objects.
	 */
	private static function get_pending_events(): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'pending',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_apollo_source',
						'value' => 'cena-rio',
					),
				),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return $query->posts;
	}

	/**
	 * Approve event (publish)
	 *
	 * @param int    $post_id Post ID.
	 * @param string $note    Optional approval note.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	private static function approve_event( int $post_id, string $note = '' ) {
		// Check permission
		if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
			return new WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para aprovar eventos.', 'apollo-core' )
			);
		}

		// Verify post exists and is pending
		$post = get_post( $post_id );
		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post',
				__( 'Evento não encontrado.', 'apollo-core' )
			);
		}

		if ( 'pending' !== $post->post_status ) {
			return new WP_Error(
				'invalid_status',
				__( 'Apenas eventos pendentes podem ser aprovados.', 'apollo-core' )
			);
		}

		// Publish event
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update meta
		update_post_meta( $post_id, '_apollo_cena_status', 'approved' );
		update_post_meta( $post_id, '_apollo_cena_approved_by', get_current_user_id() );
		update_post_meta( $post_id, '_apollo_cena_approved_at', current_time( 'mysql' ) );

		if ( ! empty( $note ) ) {
			update_post_meta( $post_id, '_apollo_cena_approval_note', $note );
		}

		// Log action
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				get_current_user_id(),
				'cena_event_approved',
				'event_listing',
				$post_id,
				array(
					'title' => $post->post_title,
					'note'  => $note,
				)
			);
		}

		return true;
	}

	/**
	 * Reject event (move to draft or trash)
	 *
	 * @param int    $post_id Post ID.
	 * @param string $reason  Optional rejection reason.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	private static function reject_event( int $post_id, string $reason = '' ) {
		// Check permission
		if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
			return new WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para rejeitar eventos.', 'apollo-core' )
			);
		}

		// Verify post exists
		$post = get_post( $post_id );
		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post',
				__( 'Evento não encontrado.', 'apollo-core' )
			);
		}

		// Move to draft (or trash if you prefer)
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			// or 'trash'
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update meta
		update_post_meta( $post_id, '_apollo_cena_status', 'rejected' );
		update_post_meta( $post_id, '_apollo_cena_rejected_by', get_current_user_id() );
		update_post_meta( $post_id, '_apollo_cena_rejected_at', current_time( 'mysql' ) );

		if ( ! empty( $reason ) ) {
			update_post_meta( $post_id, '_apollo_cena_rejection_reason', $reason );
		}

		// Log action
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				get_current_user_id(),
				'cena_event_rejected',
				'event_listing',
				$post_id,
				array(
					'title'  => $post->post_title,
					'reason' => $reason,
				)
			);
		}

		return true;
	}

	/**
	 * Handle approve action (traditional POST)
	 */
	public static function handle_approve(): void {
		// Verify nonce
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['cena_nonce'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'apollo-core' ) );
		}

		$post_id = absint( $_POST['post_id'] );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['cena_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'apollo_cena_approve_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'apollo-core' ) );
		}

		$result = self::approve_event( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect back with success message
		$referer      = wp_get_referer();
		$redirect_url = $referer ? add_query_arg( 'cena_approved', '1', $referer ) : admin_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle reject action (traditional POST)
	 */
	public static function handle_reject(): void {
		// Verify nonce
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['cena_nonce'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'apollo-core' ) );
		}

		$post_id = absint( $_POST['post_id'] );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['cena_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'apollo_cena_reject_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'apollo-core' ) );
		}

		$result = self::reject_event( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect back with success message
		$referer      = wp_get_referer();
		$redirect_url = $referer ? add_query_arg( 'cena_rejected', '1', $referer ) : admin_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * AJAX approve handler
	 */
	public static function ajax_approve(): void {
		check_ajax_referer( 'apollo_cena_ajax', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$note    = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		$result = self::approve_event( $post_id, $note );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Evento aprovado com sucesso!', 'apollo-core' ) );
	}

	/**
	 * AJAX reject handler
	 */
	public static function ajax_reject(): void {
		check_ajax_referer( 'apollo_cena_ajax', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$reason  = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		$result = self::reject_event( $post_id, $reason );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Evento rejeitado.', 'apollo-core' ) );
	}

	/**
	 * Format event for API response
	 *
	 * @param WP_Post|null $post Post object.
	 * @return array Formatted event data.
	 */
	private static function format_event_for_api( $post ): array {
		if ( ! $post ) {
			return array();
		}

		$author = get_userdata( $post->post_author );

		return array(
			'id'           => $post->ID,
			'title'        => get_the_title( $post->ID ),
			'description'  => $post->post_content,
			'status'       => $post->post_status,
			'start_date'   => get_post_meta( $post->ID, '_event_start_date', true ),
			'end_date'     => get_post_meta( $post->ID, '_event_end_date', true ),
			'venue'        => get_post_meta( $post->ID, '_event_venue_name', true ),
			'lat'          => get_post_meta( $post->ID, '_event_lat', true ),
			'lng'          => get_post_meta( $post->ID, '_event_lng', true ),
			'author'       => $author ? $author->display_name : '',
			'author_id'    => $post->post_author,
			'submitted_at' => get_post_meta( $post->ID, '_apollo_cena_submitted_at', true ),
			'cena_status'  => get_post_meta( $post->ID, '_apollo_cena_status', true ),
		);
	}
}

// Initialize
Apollo_Cena_Rio_Moderation::init();

