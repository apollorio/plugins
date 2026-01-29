<?php
/**
 * Events Admin Service
 *
 * Handles admin menus, settings, and dashboard for Apollo Events Manager.
 * Extracted from the main plugin class to follow SRP.
 *
 * @package Apollo\Events\Services
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events\Services;

/**
 * Manages admin functionality for events.
 */
final class EventsAdmin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addAdminMenus' ) );
		add_action( 'admin_notices', array( $this, 'displayAdminNotices' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_filter( 'manage_event_listing_posts_columns', array( $this, 'addCustomColumns' ) );
		add_action( 'manage_event_listing_posts_custom_column', array( $this, 'renderCustomColumn' ), 10, 2 );
		add_filter( 'manage_edit-event_listing_sortable_columns', array( $this, 'sortableColumns' ) );
	}

	/**
	 * Add admin menus.
	 *
	 * @return void
	 */
	public function addAdminMenus(): void {
		add_menu_page(
			__( 'Eventos', 'apollo-events-manager' ),
			__( 'Eventos', 'apollo-events-manager' ),
			'edit_posts',
			'apollo-events',
			array( $this, 'renderDashboard' ),
			'dashicons-calendar-alt',
			25
		);

		add_submenu_page(
			'apollo-events',
			__( 'Dashboard', 'apollo-events-manager' ),
			__( 'Dashboard', 'apollo-events-manager' ),
			'edit_posts',
			'apollo-events',
			array( $this, 'renderDashboard' )
		);

		add_submenu_page(
			'apollo-events',
			__( 'Configurações', 'apollo-events-manager' ),
			__( 'Configurações', 'apollo-events-manager' ),
			'manage_options',
			'apollo-events-settings',
			array( $this, 'renderSettings' )
		);
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public function displayAdminNotices(): void {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'event' ) === false ) {
			return;
		}

		// Check for pending events
		$pending_count = wp_count_posts( 'event_listing' )->pending;

		if ( $pending_count > 0 && current_user_can( 'edit_others_posts' ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
				sprintf(
					/* translators: %d: number of pending events */
					esc_html__( 'Você tem %d evento(s) aguardando aprovação.', 'apollo-events-manager' ),
					absint( $pending_count )
				),
				esc_url( admin_url( 'edit.php?post_type=event_listing&post_status=pending' ) ),
				esc_html__( 'Ver eventos pendentes', 'apollo-events-manager' )
			);
		}
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function registerSettings(): void {
		register_setting(
			'apollo_events_settings',
			'apollo_events_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
				'default'           => $this->getDefaultSettings(),
			)
		);

		add_settings_section(
			'apollo_events_general',
			__( 'Configurações Gerais', 'apollo-events-manager' ),
			'__return_empty_string',
			'apollo-events-settings'
		);

		add_settings_field(
			'events_per_page',
			__( 'Eventos por página', 'apollo-events-manager' ),
			array( $this, 'renderNumberField' ),
			'apollo-events-settings',
			'apollo_events_general',
			array(
				'name'    => 'events_per_page',
				'min'     => 1,
				'max'     => 100,
				'default' => 12,
			)
		);

		add_settings_field(
			'enable_canvas_mode',
			__( 'Habilitar Canvas Mode', 'apollo-events-manager' ),
			array( $this, 'renderCheckboxField' ),
			'apollo-events-settings',
			'apollo_events_general',
			array(
				'name'    => 'enable_canvas_mode',
				'label'   => __( 'Permitir modo canvas em eventos individuais', 'apollo-events-manager' ),
			)
		);

		add_settings_field(
			'enable_map',
			__( 'Habilitar Mapa', 'apollo-events-manager' ),
			array( $this, 'renderCheckboxField' ),
			'apollo-events-settings',
			'apollo_events_general',
			array(
				'name'  => 'enable_map',
				'label' => __( 'Mostrar mapa na listagem de eventos', 'apollo-events-manager' ),
			)
		);

		add_settings_field(
			'google_maps_api_key',
			__( 'Google Maps API Key', 'apollo-events-manager' ),
			array( $this, 'renderTextField' ),
			'apollo-events-settings',
			'apollo_events_general',
			array(
				'name'        => 'google_maps_api_key',
				'placeholder' => __( 'Sua API Key do Google Maps', 'apollo-events-manager' ),
			)
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	private function getDefaultSettings(): array {
		return array(
			'events_per_page'     => 12,
			'enable_canvas_mode'  => true,
			'enable_map'          => true,
			'google_maps_api_key' => '',
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitizeSettings( array $input ): array {
		$sanitized = array();

		$sanitized['events_per_page']     = absint( $input['events_per_page'] ?? 12 );
		$sanitized['enable_canvas_mode']  = ! empty( $input['enable_canvas_mode'] );
		$sanitized['enable_map']          = ! empty( $input['enable_map'] );
		$sanitized['google_maps_api_key'] = sanitize_text_field( $input['google_maps_api_key'] ?? '' );

		return $sanitized;
	}

	/**
	 * Render number field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderNumberField( array $args ): void {
		$options = get_option( 'apollo_events_options', $this->getDefaultSettings() );
		$value   = $options[ $args['name'] ] ?? $args['default'];
		?>
		<input type="number"
			   name="apollo_events_options[<?php echo esc_attr( $args['name'] ); ?>]"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   min="<?php echo esc_attr( (string) $args['min'] ); ?>"
			   max="<?php echo esc_attr( (string) $args['max'] ); ?>"
			   class="small-text">
		<?php
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderCheckboxField( array $args ): void {
		$options = get_option( 'apollo_events_options', $this->getDefaultSettings() );
		$checked = ! empty( $options[ $args['name'] ] );
		?>
		<label>
			<input type="checkbox"
				   name="apollo_events_options[<?php echo esc_attr( $args['name'] ); ?>]"
				   value="1"
				   <?php checked( $checked ); ?>>
			<?php echo esc_html( $args['label'] ); ?>
		</label>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderTextField( array $args ): void {
		$options = get_option( 'apollo_events_options', $this->getDefaultSettings() );
		$value   = $options[ $args['name'] ] ?? '';
		?>
		<input type="text"
			   name="apollo_events_options[<?php echo esc_attr( $args['name'] ); ?>]"
			   value="<?php echo esc_attr( $value ); ?>"
			   placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
			   class="regular-text">
		<?php
	}

	/**
	 * Add custom columns to events list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function addCustomColumns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['event_date']     = __( 'Data', 'apollo-events-manager' );
				$new_columns['event_location'] = __( 'Local', 'apollo-events-manager' );
				$new_columns['event_views']    = __( 'Views', 'apollo-events-manager' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function renderCustomColumn( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'event_date':
				$start_date = get_post_meta( $post_id, '_event_start_date', true );
				if ( $start_date ) {
					echo esc_html( wp_date( 'd/m/Y', strtotime( $start_date ) ) );
				} else {
					echo '—';
				}
				break;

			case 'event_location':
				$location = get_post_meta( $post_id, '_event_location', true );
				echo $location ? esc_html( $location ) : '—';
				break;

			case 'event_views':
				$views = get_post_meta( $post_id, '_event_views', true );
				echo esc_html( number_format_i18n( (int) $views ) );
				break;
		}
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array<string, string> $columns Existing sortable columns.
	 * @return array<string, string>
	 */
	public function sortableColumns( array $columns ): array {
		$columns['event_date']  = '_event_start_date';
		$columns['event_views'] = '_event_views';
		return $columns;
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function renderDashboard(): void {
		$stats = $this->getDashboardStats();

		$template_path = APOLLO_APRIO_PATH . 'templates/admin/dashboard.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->renderDefaultDashboard( $stats );
		}
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function renderSettings(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configurações de Eventos', 'apollo-events-manager' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'apollo_events_settings' );
				do_settings_sections( 'apollo-events-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get dashboard statistics.
	 *
	 * @return array<string, int>
	 */
	private function getDashboardStats(): array {
		$counts = wp_count_posts( 'event_listing' );

		return array(
			'published' => (int) $counts->publish,
			'pending'   => (int) $counts->pending,
			'draft'     => (int) $counts->draft,
			'total'     => (int) $counts->publish + (int) $counts->pending + (int) $counts->draft,
		);
	}

	/**
	 * Render default dashboard.
	 *
	 * @param array<string, int> $stats Dashboard stats.
	 * @return void
	 */
	private function renderDefaultDashboard( array $stats ): void {
		?>
		<div class="wrap apollo-events-dashboard">
			<h1><?php esc_html_e( 'Apollo Events Manager', 'apollo-events-manager' ); ?></h1>

			<div class="apollo-dashboard-cards" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin: 2rem 0;">
				<div class="apollo-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
					<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; color: #2271b1;"></span>
					<h2 style="margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['published'] ) ); ?></h2>
					<p style="color: #666; margin: 0;"><?php esc_html_e( 'Publicados', 'apollo-events-manager' ); ?></p>
				</div>
				<div class="apollo-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
					<span class="dashicons dashicons-clock" style="font-size: 48px; color: #dba617;"></span>
					<h2 style="margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['pending'] ) ); ?></h2>
					<p style="color: #666; margin: 0;"><?php esc_html_e( 'Pendentes', 'apollo-events-manager' ); ?></p>
				</div>
				<div class="apollo-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
					<span class="dashicons dashicons-edit" style="font-size: 48px; color: #72777c;"></span>
					<h2 style="margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['draft'] ) ); ?></h2>
					<p style="color: #666; margin: 0;"><?php esc_html_e( 'Rascunhos', 'apollo-events-manager' ); ?></p>
				</div>
				<div class="apollo-card" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
					<span class="dashicons dashicons-list-view" style="font-size: 48px; color: #135e96;"></span>
					<h2 style="margin: 0.5rem 0;"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></h2>
					<p style="color: #666; margin: 0;"><?php esc_html_e( 'Total', 'apollo-events-manager' ); ?></p>
				</div>
			</div>

			<div class="apollo-quick-actions" style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2><?php esc_html_e( 'Ações Rápidas', 'apollo-events-manager' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event_listing' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Novo Evento', 'apollo-events-manager' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=event_listing' ) ); ?>" class="button">
						<?php esc_html_e( 'Ver Todos', 'apollo-events-manager' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=event_listing&post_status=pending' ) ); ?>" class="button">
						<?php esc_html_e( 'Pendentes', 'apollo-events-manager' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
