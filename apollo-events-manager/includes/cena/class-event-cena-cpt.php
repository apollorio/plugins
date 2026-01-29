<?php
/**
 * Apollo CENA CPT Registration
 *
 * Custom Post Type: event_cena
 * STRICT MODE: Separate from event_listing to avoid data crossing.
 *
 * This CPT is dedicated to the Rio music/art industry calendar.
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

namespace Apollo\Events\Cena;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Event_Cena_CPT
 *
 * Registers and manages the event_cena custom post type.
 */
class Event_Cena_CPT {

	/**
	 * Post type name.
	 */
	const POST_TYPE = 'event_cena';

	/**
	 * Meta keys.
	 */
	const META_DATE      = '_event_cena_date';
	const META_TIME      = '_event_cena_time';
	const META_LOCATION  = '_event_cena_location';
	const META_TYPE      = '_event_cena_type';
	const META_AUTHOR    = '_event_cena_author';
	const META_COAUTHOR  = '_event_cena_coauthor';
	const META_TAGS      = '_event_cena_tags';
	const META_LAT       = '_event_cena_lat';
	const META_LNG       = '_event_cena_lng';
	const META_STATUS    = 'event_cena_status';

	/**
	 * Initialize the CPT.
	 */
	public static function init() {
		add_action( 'init', [ self::class, 'register_post_type' ], 5 );
		add_action( 'init', [ self::class, 'register_meta_fields' ], 10 );
		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ self::class, 'save_meta_box' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ self::class, 'add_admin_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ self::class, 'render_admin_columns' ], 10, 2 );
	}

	/**
	 * Register the custom post type.
	 */
	public static function register_post_type() {
		$labels = [
			'name'                  => 'Eventos CENA',
			'singular_name'         => 'Evento CENA',
			'menu_name'             => 'CENA::rio',
			'name_admin_bar'        => 'Evento CENA',
			'add_new'               => 'Adicionar Novo',
			'add_new_item'          => 'Adicionar Novo Evento CENA',
			'new_item'              => 'Novo Evento CENA',
			'edit_item'             => 'Editar Evento CENA',
			'view_item'             => 'Ver Evento CENA',
			'all_items'             => 'Todos os Eventos',
			'search_items'          => 'Buscar Eventos CENA',
			'parent_item_colon'     => 'Evento CENA Pai:',
			'not_found'             => 'Nenhum evento CENA encontrado.',
			'not_found_in_trash'    => 'Nenhum evento CENA na lixeira.',
			'featured_image'        => 'Imagem do Evento',
			'set_featured_image'    => 'Definir imagem do evento',
			'remove_featured_image' => 'Remover imagem do evento',
			'use_featured_image'    => 'Usar como imagem do evento',
			'archives'              => 'Arquivo de Eventos CENA',
			'insert_into_item'      => 'Inserir no evento',
			'uploaded_to_this_item' => 'Enviado para este evento',
			'filter_items_list'     => 'Filtrar eventos CENA',
			'items_list_navigation' => 'Navegação da lista de eventos',
			'items_list'            => 'Lista de eventos CENA',
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'cena-evento', 'with_front' => false ],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 26,
			'menu_icon'          => 'dashicons-tickets-alt',
			'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'custom-fields' ],
			'show_in_rest'       => true,
			'rest_base'          => 'event-cena',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register meta fields for REST API.
	 */
	public static function register_meta_fields() {
		$meta_fields = [
			self::META_DATE     => [ 'type' => 'string', 'default' => '' ],
			self::META_TIME     => [ 'type' => 'string', 'default' => '' ],
			self::META_LOCATION => [ 'type' => 'string', 'default' => '' ],
			self::META_TYPE     => [ 'type' => 'string', 'default' => '' ],
			self::META_AUTHOR   => [ 'type' => 'string', 'default' => '' ],
			self::META_COAUTHOR => [ 'type' => 'string', 'default' => '' ],
			self::META_TAGS     => [ 'type' => 'string', 'default' => '' ],
			self::META_LAT      => [ 'type' => 'number', 'default' => -22.9068 ],
			self::META_LNG      => [ 'type' => 'number', 'default' => -43.1729 ],
			self::META_STATUS   => [ 'type' => 'string', 'default' => 'previsto' ],
		];

		foreach ( $meta_fields as $key => $config ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => $config['type'],
					'default'       => $config['default'],
					'auth_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes() {
		register_rest_route( 'apollo/v1', '/cena-events', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'rest_get_events' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'year'  => [ 'type' => 'integer', 'default' => (int) date( 'Y' ) ],
				'month' => [ 'type' => 'integer', 'default' => (int) date( 'm' ) ],
			],
		] );

		register_rest_route( 'apollo/v1', '/cena-events', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'rest_create_event' ],
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		] );

		register_rest_route( 'apollo/v1', '/cena-events/(?P<id>\d+)', [
			'methods'             => 'PUT',
			'callback'            => [ self::class, 'rest_update_event' ],
			'permission_callback' => [ self::class, 'check_edit_permission' ],
			'args'                => [
				'id' => [
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				],
			],
		] );

		register_rest_route( 'apollo/v1', '/cena-events/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ self::class, 'rest_delete_event' ],
			'permission_callback' => [ self::class, 'check_delete_permission' ],
			'args'                => [
				'id' => [
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				],
			],
		] );

		register_rest_route( 'apollo/v1', '/cena-geocode', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'rest_geocode' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'address' => [ 'type' => 'string', 'required' => true ],
			],
		] );
	}

	/**
	 * Check if user can edit event (admin OR author/coauthor).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function check_edit_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Admin can edit all
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return false;
		}

		// Check if user is author or coauthor
		$current_user = wp_get_current_user();
		$author       = get_post_meta( $post_id, self::META_AUTHOR, true );
		$coauthor     = get_post_meta( $post_id, self::META_COAUTHOR, true );

		return ( $author === $current_user->user_login || $coauthor === $current_user->user_login );
	}

	/**
	 * Check if user can delete event (admin OR author/coauthor).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function check_delete_permission( $request ) {
		// Use same logic as edit permission
		return self::check_edit_permission( $request );
	}

	/**
	 * REST: Get events for a month.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_get_events( $request ) {
		$year  = $request->get_param( 'year' );
		$month = str_pad( $request->get_param( 'month' ), 2, '0', STR_PAD_LEFT );

		$args = [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_query'     => [
				[
					'key'     => self::META_DATE,
					'value'   => "{$year}-{$month}",
					'compare' => 'LIKE',
				],
			],
			'orderby'        => 'meta_value',
			'meta_key'       => self::META_DATE,
			'order'          => 'ASC',
		];

		$query  = new \WP_Query( $args );
		$events = [];

		foreach ( $query->posts as $post ) {
			$date_key = get_post_meta( $post->ID, self::META_DATE, true );

			if ( ! isset( $events[ $date_key ] ) ) {
				$events[ $date_key ] = [];
			}

			$events[ $date_key ][] = self::format_event_for_rest( $post );
		}

		return rest_ensure_response( $events );
	}

	/**
	 * REST: Create event.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_create_event( $request ) {
		$params = $request->get_json_params();

		$post_data = [
			'post_title'   => sanitize_text_field( $params['title'] ?? 'Novo Evento' ),
			'post_content' => '',
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
		];

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_Error( 'create_failed', 'Falha ao criar evento.', [ 'status' => 500 ] );
		}

		self::save_event_meta( $post_id, $params );

		$post = get_post( $post_id );
		return rest_ensure_response( self::format_event_for_rest( $post ) );
	}

	/**
	 * REST: Update event.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_update_event( $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$params  = $request->get_json_params();

		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_Error( 'not_found', 'Evento não encontrado.', [ 'status' => 404 ] );
		}

		if ( isset( $params['title'] ) ) {
			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => sanitize_text_field( $params['title'] ),
			] );
		}

		self::save_event_meta( $post_id, $params );

		$post = get_post( $post_id );
		return rest_ensure_response( self::format_event_for_rest( $post ) );
	}

	/**
	 * REST: Delete event.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_delete_event( $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return new \WP_Error( 'not_found', 'Evento não encontrado.', [ 'status' => 404 ] );
		}

		wp_trash_post( $post_id );

		return rest_ensure_response( [ 'deleted' => true ] );
	}

	/**
	 * REST: Geocode address.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_geocode( $request ) {
		$address = $request->get_param( 'address' );

		// Append Rio de Janeiro to improve results.
		$full_address = $address . ', Rio de Janeiro, Brazil';
		$encoded      = urlencode( $full_address );

		$response = wp_remote_get(
			"https://nominatim.openstreetmap.org/search?format=json&limit=1&q={$encoded}",
			[
				'headers' => [
					'User-Agent' => 'Apollo-Calendar/2.0 (WordPress)',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			// Return default Rio center.
			return rest_ensure_response( [
				'lat' => -22.9068,
				'lng' => -43.1729,
			] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data[0] ) ) {
			return rest_ensure_response( [
				'lat' => (float) $data[0]['lat'],
				'lng' => (float) $data[0]['lon'],
			] );
		}

		// Default to Rio center.
		return rest_ensure_response( [
			'lat' => -22.9068,
			'lng' => -43.1729,
		] );
	}

	/**
	 * Save event meta from params.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Parameters.
	 */
	private static function save_event_meta( $post_id, $params ) {
		$meta_map = [
			'event_date' => self::META_DATE,
			'date'       => self::META_DATE,
			'time'       => self::META_TIME,
			'event_time' => self::META_TIME,
			'location'   => self::META_LOCATION,
			'type'       => self::META_TYPE,
			'author'     => self::META_AUTHOR,
			'coauthor'   => self::META_COAUTHOR,
			'tags'       => self::META_TAGS,
			'lat'        => self::META_LAT,
			'lng'        => self::META_LNG,
			'status'     => self::META_STATUS,
			'event_cena_status' => self::META_STATUS,
		];

		foreach ( $meta_map as $param_key => $meta_key ) {
			if ( isset( $params[ $param_key ] ) ) {
				$value = $params[ $param_key ];

				// Handle tags as JSON.
				if ( $meta_key === self::META_TAGS && is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		// Validate status.
		$valid_statuses = [ 'previsto', 'confirmado', 'adiado', 'cancelado' ];
		$current_status = get_post_meta( $post_id, self::META_STATUS, true );

		if ( ! in_array( $current_status, $valid_statuses, true ) ) {
			update_post_meta( $post_id, self::META_STATUS, 'previsto' );
		}
	}

	/**
	 * Format event for REST response.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private static function format_event_for_rest( $post ) {
		$date  = get_post_meta( $post->ID, self::META_DATE, true );
		$parts = explode( '-', $date );
		$formatted_date = count( $parts ) === 3 ? "{$parts[2]}/{$parts[1]}" : '';

		$tags_str = get_post_meta( $post->ID, self::META_TAGS, true );
		$tags     = $tags_str ? array_map( 'trim', explode( ',', $tags_str ) ) : [];

		return [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'date'     => $formatted_date,
			'dateKey'  => $date,
			'time'     => get_post_meta( $post->ID, self::META_TIME, true ),
			'location' => get_post_meta( $post->ID, self::META_LOCATION, true ),
			'type'     => get_post_meta( $post->ID, self::META_TYPE, true ),
			'status'   => get_post_meta( $post->ID, self::META_STATUS, true ) ?: 'previsto',
			'author'   => get_post_meta( $post->ID, self::META_AUTHOR, true ),
			'coauthor' => get_post_meta( $post->ID, self::META_COAUTHOR, true ),
			'tags'     => $tags,
			'lat'      => (float) ( get_post_meta( $post->ID, self::META_LAT, true ) ?: -22.9068 ),
			'lng'      => (float) ( get_post_meta( $post->ID, self::META_LNG, true ) ?: -43.1729 ),
		];
	}

	/**
	 * Add meta boxes for admin editing.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'event_cena_details',
			'Detalhes do Evento CENA',
			[ self::class, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'event_cena_save', 'event_cena_nonce' );

		$date     = get_post_meta( $post->ID, self::META_DATE, true );
		$time     = get_post_meta( $post->ID, self::META_TIME, true );
		$location = get_post_meta( $post->ID, self::META_LOCATION, true );
		$type     = get_post_meta( $post->ID, self::META_TYPE, true );
		$author   = get_post_meta( $post->ID, self::META_AUTHOR, true );
		$coauthor = get_post_meta( $post->ID, self::META_COAUTHOR, true );
		$tags     = get_post_meta( $post->ID, self::META_TAGS, true );
		$status   = get_post_meta( $post->ID, self::META_STATUS, true ) ?: 'previsto';
		$lat      = get_post_meta( $post->ID, self::META_LAT, true ) ?: -22.9068;
		$lng      = get_post_meta( $post->ID, self::META_LNG, true ) ?: -43.1729;

		$statuses = [
			'previsto'   => [ 'label' => 'Previsto', 'color' => '#f97316', 'icon' => 'ri-hourglass-line' ],
			'confirmado' => [ 'label' => 'Confirmado', 'color' => '#10b981', 'icon' => 'ri-checkbox-circle-line' ],
			'adiado'     => [ 'label' => 'Adiado', 'color' => '#a855f7', 'icon' => 'ri-time-line' ],
			'cancelado'  => [ 'label' => 'Cancelado', 'color' => '#ef4444', 'icon' => 'ri-close-circle-line' ],
		];
		?>
<style>
.event-cena-meta {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
}

.event-cena-meta .full-width {
	grid-column: 1 / -1;
}

.event-cena-field label {
	display: block;
	font-weight: 600;
	margin-bottom: 4px;
}

.event-cena-field input,
.event-cena-field select {
	width: 100%;
	padding: 8px;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
}

.status-selector {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
}

.status-option {
	padding: 8px 16px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.2s;
}

.status-option:hover {
	border-color: currentColor;
}

.status-option.selected {
	border-color: currentColor;
	font-weight: 600;
}

.status-option input {
	display: none;
}
</style>
<div class="event-cena-meta">
	<div class="event-cena-field">
		<label for="event_cena_date">Data</label>
		<input type="date" id="event_cena_date" name="event_cena_date" value="<?php echo esc_attr( $date ); ?>">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_time">Horário</label>
		<input type="time" id="event_cena_time" name="event_cena_time" value="<?php echo esc_attr( $time ); ?>">
	</div>
	<div class="event-cena-field full-width">
		<label for="event_cena_location">Local</label>
		<input type="text" id="event_cena_location" name="event_cena_location"
			value="<?php echo esc_attr( $location ); ?>" placeholder="Rua, Bairro, Rio de Janeiro">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_type">Tipo</label>
		<input type="text" id="event_cena_type" name="event_cena_type" value="<?php echo esc_attr( $type ); ?>"
			placeholder="Festival, Masterclass, etc.">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_tags">Tags (separadas por vírgula)</label>
		<input type="text" id="event_cena_tags" name="event_cena_tags" value="<?php echo esc_attr( $tags ); ?>"
			placeholder="Techno, House, Live">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_author">Autor (@username)</label>
		<input type="text" id="event_cena_author" name="event_cena_author" value="<?php echo esc_attr( $author ); ?>">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_coauthor">Coautor (@username)</label>
		<input type="text" id="event_cena_coauthor" name="event_cena_coauthor"
			value="<?php echo esc_attr( $coauthor ); ?>">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_lat">Latitude</label>
		<input type="number" step="any" id="event_cena_lat" name="event_cena_lat"
			value="<?php echo esc_attr( $lat ); ?>">
	</div>
	<div class="event-cena-field">
		<label for="event_cena_lng">Longitude</label>
		<input type="number" step="any" id="event_cena_lng" name="event_cena_lng"
			value="<?php echo esc_attr( $lng ); ?>">
	</div>
	<div class="event-cena-field full-width">
		<label>Status do Evento</label>
		<div class="status-selector">
			<?php foreach ( $statuses as $key => $s ) : ?>
			<label class="status-option <?php echo $status === $key ? 'selected' : ''; ?>"
				style="color: <?php echo esc_attr( $s['color'] ); ?>;">
				<input type="radio" name="event_cena_status" value="<?php echo esc_attr( $key ); ?>"
					<?php checked( $status, $key ); ?>>
				<?php echo esc_html( $s['label'] ); ?>
			</label>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<script>
document.querySelectorAll('.status-option input').forEach(input => {
	input.addEventListener('change', function() {
		document.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('selected'));
		this.closest('.status-option').classList.add('selected');
	});
});
</script>
<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['event_cena_nonce'] ) || ! wp_verify_nonce( $_POST['event_cena_nonce'], 'event_cena_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = [
			'event_cena_date'     => self::META_DATE,
			'event_cena_time'     => self::META_TIME,
			'event_cena_location' => self::META_LOCATION,
			'event_cena_type'     => self::META_TYPE,
			'event_cena_author'   => self::META_AUTHOR,
			'event_cena_coauthor' => self::META_COAUTHOR,
			'event_cena_tags'     => self::META_TAGS,
			'event_cena_lat'      => self::META_LAT,
			'event_cena_lng'      => self::META_LNG,
			'event_cena_status'   => self::META_STATUS,
		];

		foreach ( $fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
			}
		}
	}

	/**
	 * Add admin columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public static function add_admin_columns( $columns ) {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( $key === 'title' ) {
				$new_columns['event_date']   = 'Data';
				$new_columns['event_status'] = 'Status';
				$new_columns['event_location'] = 'Local';
			}
		}

		return $new_columns;
	}

	/**
	 * Render admin columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'event_date':
				$date = get_post_meta( $post_id, self::META_DATE, true );
				$time = get_post_meta( $post_id, self::META_TIME, true );
				if ( $date ) {
					$parts = explode( '-', $date );
					echo esc_html( "{$parts[2]}/{$parts[1]}/{$parts[0]}" );
					if ( $time ) {
						echo ' às ' . esc_html( $time );
					}
				}
				break;

			case 'event_status':
				$status   = get_post_meta( $post_id, self::META_STATUS, true ) ?: 'previsto';
				$statuses = [
					'previsto'   => [ 'label' => 'Previsto', 'color' => '#f97316' ],
					'confirmado' => [ 'label' => 'Confirmado', 'color' => '#10b981' ],
					'adiado'     => [ 'label' => 'Adiado', 'color' => '#a855f7' ],
					'cancelado'  => [ 'label' => 'Cancelado', 'color' => '#ef4444' ],
				];
				$s = $statuses[ $status ] ?? $statuses['previsto'];
				printf(
					'<span style="display:inline-block;padding:4px 12px;border-radius:4px;background:%s;color:white;font-size:11px;font-weight:600;">%s</span>',
					esc_attr( $s['color'] ),
					esc_html( $s['label'] )
				);
				break;

			case 'event_location':
				echo esc_html( get_post_meta( $post_id, self::META_LOCATION, true ) );
				break;
		}
	}
}

// Initialize.
Event_Cena_CPT::init();
