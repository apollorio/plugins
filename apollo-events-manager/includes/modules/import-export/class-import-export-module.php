<?php
/**
 * Import/Export Module
 *
 * Import and export events functionality.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ImportExport_Module
 *
 * CSV/JSON import and export for events.
 *
 * @since 2.0.0
 */
class ImportExport_Module extends Abstract_Module {

	/**
	 * Supported export formats.
	 *
	 * @var array
	 */
	private array $formats = array( 'csv', 'json', 'ical' );

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'import_export';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Import/Export', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Importação e exportação de eventos em CSV, JSON e iCal.', 'apollo-events' );
	}

	/**
	 * Get module version.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_version(): string {
		return '2.0.0';
	}

	/**
	 * Check if module is enabled by default.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		$this->register_hooks();
		$this->register_assets();
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_hooks(): void {
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_tools_page' ) );

		// Export handlers.
		add_action( 'admin_post_apollo_export_events', array( $this, 'handle_export' ) );

		// Import handlers.
		add_action( 'wp_ajax_apollo_import_events', array( $this, 'ajax_import' ) );
		add_action( 'wp_ajax_apollo_import_preview', array( $this, 'ajax_import_preview' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Feed endpoints.
		add_action( 'init', array( $this, 'add_feed_endpoints' ) );
		add_action( 'template_redirect', array( $this, 'handle_feed' ) );
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 2.0.0
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'event_listing_page_apollo-import-export' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'apollo-import-export',
			plugins_url( 'assets/css/import-export.css', dirname( dirname( __DIR__ ) ) ),
			array(),
			$this->get_version()
		);

		wp_enqueue_script(
			'apollo-import-export',
			plugins_url( 'assets/js/import-export.js', dirname( dirname( __DIR__ ) ) ),
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-import-export',
			'apolloImportExport',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_import_export_nonce' ),
				'i18n'    => array(
					'importing' => __( 'Importando...', 'apollo-events' ),
					'success'   => __( 'Importação concluída!', 'apollo-events' ),
					'error'     => __( 'Erro na importação', 'apollo-events' ),
					'confirm'   => __( 'Confirmar importação?', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Add tools page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_tools_page(): void {
		add_submenu_page(
			'edit.php?post_type=event_listing',
			__( 'Import/Export', 'apollo-events' ),
			__( 'Import/Export', 'apollo-events' ),
			'manage_options',
			'apollo-import-export',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Render tools page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_tools_page(): void {
		$events_count = wp_count_posts( 'event_listing' )->publish;
		?>
		<div class="wrap apollo-import-export">
			<h1><?php esc_html_e( 'Import/Export de Eventos', 'apollo-events' ); ?></h1>

			<div class="apollo-ie-grid">
				<!-- Export Section -->
				<div class="apollo-ie-card">
					<div class="apollo-ie-card__header">
						<i class="dashicons dashicons-download"></i>
						<h2><?php esc_html_e( 'Exportar Eventos', 'apollo-events' ); ?></h2>
					</div>

					<div class="apollo-ie-card__body">
						<p><?php printf( esc_html__( '%d eventos disponíveis para exportação.', 'apollo-events' ), $events_count ); ?></p>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'apollo_export_events', 'apollo_export_nonce' ); ?>
							<input type="hidden" name="action" value="apollo_export_events">

							<table class="form-table">
								<tr>
									<th><label for="export_format"><?php esc_html_e( 'Formato', 'apollo-events' ); ?></label></th>
									<td>
										<select name="format" id="export_format">
											<option value="csv">CSV</option>
											<option value="json">JSON</option>
											<option value="ical">iCal (.ics)</option>
										</select>
									</td>
								</tr>

								<tr>
									<th><label for="export_status"><?php esc_html_e( 'Status', 'apollo-events' ); ?></label></th>
									<td>
										<select name="status" id="export_status">
											<option value="publish"><?php esc_html_e( 'Publicados', 'apollo-events' ); ?></option>
											<option value="draft"><?php esc_html_e( 'Rascunhos', 'apollo-events' ); ?></option>
											<option value="all"><?php esc_html_e( 'Todos', 'apollo-events' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th><label for="export_date_from"><?php esc_html_e( 'Data Inicial', 'apollo-events' ); ?></label></th>
									<td>
										<input type="date" name="date_from" id="export_date_from">
									</td>
								</tr>

								<tr>
									<th><label for="export_date_to"><?php esc_html_e( 'Data Final', 'apollo-events' ); ?></label></th>
									<td>
										<input type="date" name="date_to" id="export_date_to">
									</td>
								</tr>
							</table>

							<p class="submit">
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-download"></span>
									<?php esc_html_e( 'Exportar', 'apollo-events' ); ?>
								</button>
							</p>
						</form>
					</div>
				</div>

				<!-- Import Section -->
				<div class="apollo-ie-card">
					<div class="apollo-ie-card__header">
						<i class="dashicons dashicons-upload"></i>
						<h2><?php esc_html_e( 'Importar Eventos', 'apollo-events' ); ?></h2>
					</div>

					<div class="apollo-ie-card__body">
						<p><?php esc_html_e( 'Importe eventos de um arquivo CSV ou JSON.', 'apollo-events' ); ?></p>

						<form id="apollo-import-form" enctype="multipart/form-data">
							<?php wp_nonce_field( 'apollo_import_events', 'apollo_import_nonce' ); ?>

							<div class="apollo-ie-dropzone" id="import-dropzone">
								<input type="file" name="import_file" id="import_file"
										accept=".csv,.json" style="display: none;">
								<div class="apollo-ie-dropzone__content">
									<i class="dashicons dashicons-upload"></i>
									<p><?php esc_html_e( 'Arraste um arquivo ou clique para selecionar', 'apollo-events' ); ?></p>
									<span><?php esc_html_e( 'CSV ou JSON', 'apollo-events' ); ?></span>
								</div>
							</div>

							<div class="apollo-ie-options" style="display: none;">
								<table class="form-table">
									<tr>
										<th><label for="import_status"><?php esc_html_e( 'Status dos Importados', 'apollo-events' ); ?></label></th>
										<td>
											<select name="import_status" id="import_status">
												<option value="draft"><?php esc_html_e( 'Rascunho', 'apollo-events' ); ?></option>
												<option value="publish"><?php esc_html_e( 'Publicado', 'apollo-events' ); ?></option>
											</select>
										</td>
									</tr>

									<tr>
										<th><label><?php esc_html_e( 'Opções', 'apollo-events' ); ?></label></th>
										<td>
											<label>
												<input type="checkbox" name="skip_existing" value="1" checked>
												<?php esc_html_e( 'Pular eventos duplicados', 'apollo-events' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>

							<div class="apollo-ie-preview" id="import-preview" style="display: none;"></div>

							<div class="apollo-ie-progress" id="import-progress" style="display: none;">
								<div class="apollo-ie-progress__bar">
									<div class="apollo-ie-progress__fill"></div>
								</div>
								<p class="apollo-ie-progress__text"></p>
							</div>

							<p class="submit" id="import-submit" style="display: none;">
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Importar', 'apollo-events' ); ?>
								</button>
							</p>
						</form>
					</div>
				</div>
			</div>

			<!-- Feeds Section -->
			<div class="apollo-ie-card apollo-ie-card--full">
				<div class="apollo-ie-card__header">
					<i class="dashicons dashicons-rss"></i>
					<h2><?php esc_html_e( 'Feeds de Eventos', 'apollo-events' ); ?></h2>
				</div>

				<div class="apollo-ie-card__body">
					<p><?php esc_html_e( 'Use estes links para sincronizar eventos com outros sistemas.', 'apollo-events' ); ?></p>

					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Formato', 'apollo-events' ); ?></th>
								<th><?php esc_html_e( 'URL', 'apollo-events' ); ?></th>
								<th><?php esc_html_e( 'Ações', 'apollo-events' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>JSON Feed</strong></td>
								<td>
									<code id="json-feed-url"><?php echo esc_url( home_url( '/apollo-events-feed/json/' ) ); ?></code>
								</td>
								<td>
									<button type="button" class="button apollo-copy-url" data-target="json-feed-url">
										<?php esc_html_e( 'Copiar', 'apollo-events' ); ?>
									</button>
								</td>
							</tr>
							<tr>
								<td><strong>iCal Feed</strong></td>
								<td>
									<code id="ical-feed-url"><?php echo esc_url( home_url( '/apollo-events-feed/ical/' ) ); ?></code>
								</td>
								<td>
									<button type="button" class="button apollo-copy-url" data-target="ical-feed-url">
										<?php esc_html_e( 'Copiar', 'apollo-events' ); ?>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Template Download -->
			<div class="apollo-ie-card apollo-ie-card--full">
				<div class="apollo-ie-card__header">
					<i class="dashicons dashicons-media-spreadsheet"></i>
					<h2><?php esc_html_e( 'Template de Importação', 'apollo-events' ); ?></h2>
				</div>

				<div class="apollo-ie-card__body">
					<p><?php esc_html_e( 'Baixe o template CSV para facilitar a importação em massa.', 'apollo-events' ); ?></p>

					<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=apollo_export_events&format=csv&template=1&' . wp_create_nonce( 'apollo_export_events' ) ) ); ?>"
						class="button">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Baixar Template CSV', 'apollo-events' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle export.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_export(): void {
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['apollo_export_nonce'] ?? '' ), 'apollo_export_events' ) ) {
			wp_die( esc_html__( 'Nonce inválido', 'apollo-events' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permissão negada', 'apollo-events' ) );
		}

		$format    = isset( $_GET['format'] ) ? sanitize_key( $_GET['format'] ) : 'csv';
		$status    = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'publish';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$template  = isset( $_GET['template'] ) && $_GET['template'];

		if ( $template ) {
			$this->export_template();
			exit;
		}

		$events = $this->get_events_for_export( $status, $date_from, $date_to );

		switch ( $format ) {
			case 'json':
				$this->export_json( $events );
				break;
			case 'ical':
				$this->export_ical( $events );
				break;
			case 'csv':
			default:
				$this->export_csv( $events );
				break;
		}

		exit;
	}

	/**
	 * Get events for export.
	 *
	 * @since 2.0.0
	 * @param string $status    Post status.
	 * @param string $date_from Start date.
	 * @param string $date_to   End date.
	 * @return array
	 */
	private function get_events_for_export( string $status, string $date_from, string $date_to ): array {
		$args = array(
			'post_type'      => 'event_listing',
			'posts_per_page' => -1,
			'post_status'    => 'all' === $status ? array( 'publish', 'draft', 'pending' ) : $status,
		);

		if ( $date_from || $date_to ) {
			$args['meta_query'] = array();

			if ( $date_from ) {
				$args['meta_query'][] = array(
					'key'     => '_event_start_date',
					'value'   => $date_from,
					'compare' => '>=',
					'type'    => 'DATE',
				);
			}

			if ( $date_to ) {
				$args['meta_query'][] = array(
					'key'     => '_event_start_date',
					'value'   => $date_to,
					'compare' => '<=',
					'type'    => 'DATE',
				);
			}
		}

		return get_posts( $args );
	}

	/**
	 * Export CSV.
	 *
	 * @since 2.0.0
	 * @param array $events Events to export.
	 * @return void
	 */
	private function export_csv( array $events ): void {
		$filename = 'eventos-' . wp_date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers.
		$headers = array(
			'ID',
			'Título',
			'Descrição',
			'Data Início',
			'Data Fim',
			'Local',
			'DJs',
			'Preço',
			'URL Ingresso',
			'Status',
			'URL',
		);

		fputcsv( $output, $headers, ';' );

		// Data rows.
		foreach ( $events as $event ) {
			$row = array(
				$event->ID,
				$event->post_title,
				wp_strip_all_tags( $event->post_content ),
				get_post_meta( $event->ID, '_event_start_date', true ),
				get_post_meta( $event->ID, '_event_end_date', true ),
				$this->get_local_names( $event->ID ),
				$this->get_dj_names( $event->ID ),
				get_post_meta( $event->ID, '_event_ticket_price', true ),
				get_post_meta( $event->ID, '_event_ticket_url', true ),
				$event->post_status,
				get_permalink( $event->ID ),
			);

			fputcsv( $output, $row, ';' );
		}

		fclose( $output );
	}

	/**
	 * Export JSON.
	 *
	 * @since 2.0.0
	 * @param array $events Events to export.
	 * @return void
	 */
	private function export_json( array $events ): void {
		$filename = 'eventos-' . wp_date( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$data = array();

		foreach ( $events as $event ) {
			$data[] = array(
				'id'           => $event->ID,
				'title'        => $event->post_title,
				'description'  => wp_strip_all_tags( $event->post_content ),
				'excerpt'      => $event->post_excerpt,
				'start_date'   => get_post_meta( $event->ID, '_event_start_date', true ),
				'end_date'     => get_post_meta( $event->ID, '_event_end_date', true ),
				'locals'       => $this->get_local_ids( $event->ID ),
				'djs'          => $this->get_dj_ids( $event->ID ),
				'ticket_price' => get_post_meta( $event->ID, '_event_ticket_price', true ),
				'ticket_url'   => get_post_meta( $event->ID, '_event_ticket_url', true ),
				'status'       => $event->post_status,
				'url'          => get_permalink( $event->ID ),
				'thumbnail'    => get_the_post_thumbnail_url( $event->ID, 'large' ),
			);
		}

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Export iCal.
	 *
	 * @since 2.0.0
	 * @param array $events Events to export.
	 * @return void
	 */
	private function export_ical( array $events ): void {
		$filename = 'eventos-' . wp_date( 'Y-m-d' ) . '.ics';

		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$ical  = "BEGIN:VCALENDAR\r\n";
		$ical .= "VERSION:2.0\r\n";
		$ical .= "PRODID:-//Apollo Events Manager//EN\r\n";
		$ical .= "CALSCALE:GREGORIAN\r\n";
		$ical .= "METHOD:PUBLISH\r\n";
		$ical .= 'X-WR-CALNAME:' . get_bloginfo( 'name' ) . " Eventos\r\n";

		foreach ( $events as $event ) {
			$start_date = get_post_meta( $event->ID, '_event_start_date', true );
			$end_date   = get_post_meta( $event->ID, '_event_end_date', true );

			if ( ! $start_date ) {
				continue;
			}

			$dtstart = wp_date( 'Ymd\THis', strtotime( $start_date ) );
			$dtend   = $end_date ? wp_date( 'Ymd\THis', strtotime( $end_date ) ) : $dtstart;

			$ical .= "BEGIN:VEVENT\r\n";
			$ical .= 'UID:' . $event->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
			$ical .= 'DTSTAMP:' . wp_date( 'Ymd\THis' ) . "\r\n";
			$ical .= "DTSTART:{$dtstart}\r\n";
			$ical .= "DTEND:{$dtend}\r\n";
			$ical .= 'SUMMARY:' . $this->escape_ical( $event->post_title ) . "\r\n";
			$ical .= 'DESCRIPTION:' . $this->escape_ical( wp_strip_all_tags( $event->post_content ) ) . "\r\n";
			$ical .= 'URL:' . get_permalink( $event->ID ) . "\r\n";

			$local = $this->get_local_names( $event->ID );
			if ( $local ) {
				$ical .= 'LOCATION:' . $this->escape_ical( $local ) . "\r\n";
			}

			$ical .= "END:VEVENT\r\n";
		}

		$ical .= "END:VCALENDAR\r\n";

		echo $ical;
	}

	/**
	 * Escape string for iCal.
	 *
	 * @since 2.0.0
	 * @param string $text Text to escape.
	 * @return string
	 */
	private function escape_ical( string $text ): string {
		$text = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\;', '\,' ), $text );
		$text = str_replace( array( "\r\n", "\n", "\r" ), '\n', $text );
		return $text;
	}

	/**
	 * Export template.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function export_template(): void {
		$filename = 'template-eventos.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers.
		$headers = array(
			'Título',
			'Descrição',
			'Data Início (YYYY-MM-DD HH:MM)',
			'Data Fim (YYYY-MM-DD HH:MM)',
			'Local',
			'DJs (separados por vírgula)',
			'Preço',
			'URL Ingresso',
		);

		fputcsv( $output, $headers, ';' );

		// Example row.
		$example = array(
			'Nome do Evento',
			'Descrição detalhada do evento',
			'2024-12-31 22:00',
			'2025-01-01 06:00',
			'Nome do Local',
			'DJ 1, DJ 2, DJ 3',
			'50.00',
			'https://exemplo.com/ingressos',
		);

		fputcsv( $output, $example, ';' );

		fclose( $output );
	}

	/**
	 * AJAX import handler.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_import(): void {
		check_ajax_referer( 'apollo_import_export_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada', 'apollo-events' ) ), 403 );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Arquivo não enviado', 'apollo-events' ) ), 400 );
		}

		$file   = $_FILES['import_file'];
		$status = isset( $_POST['import_status'] ) ? sanitize_key( wp_unslash( $_POST['import_status'] ) ) : 'draft';
		$skip   = isset( $_POST['skip_existing'] ) && $_POST['skip_existing'];

		$ext = pathinfo( $file['name'], PATHINFO_EXTENSION );

		if ( 'csv' === $ext ) {
			$result = $this->import_csv( $file['tmp_name'], $status, $skip );
		} elseif ( 'json' === $ext ) {
			$result = $this->import_json( $file['tmp_name'], $status, $skip );
		} else {
			wp_send_json_error( array( 'message' => __( 'Formato não suportado', 'apollo-events' ) ), 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Import CSV.
	 *
	 * @since 2.0.0
	 * @param string $file   File path.
	 * @param string $status Post status.
	 * @param bool   $skip   Skip existing.
	 * @return array
	 */
	private function import_csv( string $file, string $status, bool $skip ): array {
		$handle   = fopen( $file, 'r' );
		$headers  = fgetcsv( $handle, 0, ';' );
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
			$data = array_combine( $headers, $row );

			if ( $skip && $this->event_exists( $data['Título'] ?? '' ) ) {
				++$skipped;
				continue;
			}

			$post_id = $this->create_event_from_data( $data, $status );

			if ( $post_id ) {
				++$imported;
			} else {
				++$errors;
			}
		}

		fclose( $handle );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Import JSON.
	 *
	 * @since 2.0.0
	 * @param string $file   File path.
	 * @param string $status Post status.
	 * @param bool   $skip   Skip existing.
	 * @return array
	 */
	private function import_json( string $file, string $status, bool $skip ): array {
		$content  = file_get_contents( $file );
		$events   = json_decode( $content, true );
		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		if ( ! is_array( $events ) ) {
			return array(
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => 1,
			);
		}

		foreach ( $events as $event ) {
			if ( $skip && $this->event_exists( $event['title'] ?? '' ) ) {
				++$skipped;
				continue;
			}

			$data = array(
				'Título'       => $event['title'] ?? '',
				'Descrição'    => $event['description'] ?? '',
				'Data Início'  => $event['start_date'] ?? '',
				'Data Fim'     => $event['end_date'] ?? '',
				'Preço'        => $event['ticket_price'] ?? '',
				'URL Ingresso' => $event['ticket_url'] ?? '',
			);

			$post_id = $this->create_event_from_data( $data, $status );

			if ( $post_id ) {
				++$imported;
			} else {
				++$errors;
			}
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Create event from data.
	 *
	 * @since 2.0.0
	 * @param array  $data   Event data.
	 * @param string $status Post status.
	 * @return int|false
	 */
	private function create_event_from_data( array $data, string $status ) {
		$title = $data['Título'] ?? $data['title'] ?? '';

		if ( ! $title ) {
			return false;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'event_listing',
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => wp_kses_post( $data['Descrição'] ?? $data['description'] ?? '' ),
				'post_status'  => $status,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Meta fields.
		$start_date = $data['Data Início'] ?? $data['Data Início (YYYY-MM-DD HH:MM)'] ?? $data['start_date'] ?? '';
		if ( $start_date ) {
			update_post_meta( $post_id, '_event_start_date', sanitize_text_field( $start_date ) );
		}

		$end_date = $data['Data Fim'] ?? $data['Data Fim (YYYY-MM-DD HH:MM)'] ?? $data['end_date'] ?? '';
		if ( $end_date ) {
			update_post_meta( $post_id, '_event_end_date', sanitize_text_field( $end_date ) );
		}

		$price = $data['Preço'] ?? $data['ticket_price'] ?? '';
		if ( $price ) {
			update_post_meta( $post_id, '_event_ticket_price', sanitize_text_field( $price ) );
		}

		$ticket_url = $data['URL Ingresso'] ?? $data['ticket_url'] ?? '';
		if ( $ticket_url ) {
			update_post_meta( $post_id, '_event_ticket_url', esc_url_raw( $ticket_url ) );
		}

		return $post_id;
	}

	/**
	 * Check if event exists.
	 *
	 * @since 2.0.0
	 * @param string $title Event title.
	 * @return bool
	 */
	private function event_exists( string $title ): bool {
		if ( ! $title ) {
			return false;
		}

		$existing = get_posts(
			array(
				'post_type'   => 'event_listing',
				'title'       => $title,
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		return ! empty( $existing );
	}

	/**
	 * Get local names.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return string
	 */
	private function get_local_names( int $event_id ): string {
		$ids = get_post_meta( $event_id, '_event_local_ids', true );

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return '';
		}

		$names = array();
		foreach ( $ids as $id ) {
			$local = get_post( $id );
			if ( $local ) {
				$names[] = $local->post_title;
			}
		}

		return implode( ', ', $names );
	}

	/**
	 * Get DJ names.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return string
	 */
	private function get_dj_names( int $event_id ): string {
		$ids = get_post_meta( $event_id, '_event_dj_ids', true );

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return '';
		}

		$names = array();
		foreach ( $ids as $id ) {
			$dj = get_post( $id );
			if ( $dj ) {
				$names[] = $dj->post_title;
			}
		}

		return implode( ', ', $names );
	}

	/**
	 * Get local IDs.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	private function get_local_ids( int $event_id ): array {
		$ids = get_post_meta( $event_id, '_event_local_ids', true );
		return is_array( $ids ) ? $ids : array();
	}

	/**
	 * Get DJ IDs.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	private function get_dj_ids( int $event_id ): array {
		$ids = get_post_meta( $event_id, '_event_dj_ids', true );
		return is_array( $ids ) ? $ids : array();
	}

	/**
	 * Add feed endpoints.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_feed_endpoints(): void {
		add_rewrite_rule( '^apollo-events-feed/(json|ical)/?$', 'index.php?apollo_feed=$matches[1]', 'top' );
		add_rewrite_tag( '%apollo_feed%', '([^&]+)' );
	}

	/**
	 * Handle feed.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_feed(): void {
		$feed = get_query_var( 'apollo_feed' );

		if ( ! $feed ) {
			return;
		}

		$events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);

		if ( 'json' === $feed ) {
			$this->export_json( $events );
		} elseif ( 'ical' === $feed ) {
			$this->export_ical( $events );
		}

		exit;
	}

	/**
	 * Register REST routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'apollo-events/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_export' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST export callback.
	 *
	 * @since 2.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_export( $request ): \WP_REST_Response {
		$events = get_posts(
			array(
				'post_type'      => 'event_listing',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		$data = array();

		foreach ( $events as $event ) {
			$data[] = array(
				'id'         => $event->ID,
				'title'      => $event->post_title,
				'start_date' => get_post_meta( $event->ID, '_event_start_date', true ),
				'end_date'   => get_post_meta( $event->ID, '_event_end_date', true ),
				'url'        => get_permalink( $event->ID ),
			);
		}

		return new \WP_REST_Response( $data );
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		// No shortcodes for this module.
	}

	/**
	 * Enqueue module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		// Admin only module.
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'enable_feeds' => array(
				'type'    => 'boolean',
				'label'   => __( 'Habilitar feeds públicos', 'apollo-events' ),
				'default' => true,
			),
			'feed_limit'   => array(
				'type'    => 'number',
				'label'   => __( 'Limite de eventos no feed', 'apollo-events' ),
				'default' => 100,
			),
		);
	}
}
