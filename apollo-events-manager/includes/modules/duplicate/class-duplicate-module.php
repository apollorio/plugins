<?php
/**
 * Duplicate Module
 *
 * Provides quick event duplication and cloning features.
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
 * Class Duplicate_Module
 *
 * Admin tools for quick event duplication and batch operations.
 *
 * @since 2.0.0
 */
class Duplicate_Module extends Abstract_Module {

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'duplicate';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Duplicar Evento', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Ferramentas para duplicar eventos rapidamente e criar séries recorrentes.', 'apollo-events' );
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
		$this->register_assets();
		$this->register_admin_hooks();
	}

	/**
	 * Register admin hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_admin_hooks(): void {
		// Add row action.
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_action' ), 10, 2 );

		// Add bulk action.
		add_filter( 'bulk_actions-edit-event_listing', array( $this, 'add_bulk_duplicate_action' ) );
		add_filter( 'handle_bulk_actions-edit-event_listing', array( $this, 'handle_bulk_duplicate' ), 10, 3 );

		// Process duplicate action.
		add_action( 'admin_action_apollo_duplicate_event', array( $this, 'handle_duplicate_action' ) );
		add_action( 'admin_action_apollo_quick_duplicate', array( $this, 'handle_quick_duplicate' ) );

		// Add metabox.
		add_action( 'add_meta_boxes', array( $this, 'add_duplicate_metabox' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_apollo_create_recurring', array( $this, 'ajax_create_recurring' ) );
		add_action( 'wp_ajax_apollo_quick_duplicate', array( $this, 'ajax_quick_duplicate' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'show_duplicate_notice' ) );

		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_tools_page' ) );
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		// This module doesn't have frontend shortcodes.
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'event_listing' !== $screen->post_type ) {
			return;
		}

		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_enqueue_style(
			'apollo-duplicate',
			$plugin_url . '/assets/css/duplicate.css',
			array(),
			$this->get_version()
		);

		wp_enqueue_script(
			'apollo-duplicate',
			$plugin_url . '/assets/js/duplicate.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-duplicate',
			'apolloDuplicate',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_duplicate_nonce' ),
				'i18n'    => array(
					'duplicating'   => __( 'Duplicando...', 'apollo-events' ),
					'success'       => __( 'Evento duplicado com sucesso!', 'apollo-events' ),
					'error'         => __( 'Erro ao duplicar evento.', 'apollo-events' ),
					'confirmDelete' => __( 'Tem certeza?', 'apollo-events' ),
					'creating'      => __( 'Criando eventos...', 'apollo-events' ),
					'created'       => __( 'eventos criados', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Add duplicate action to row actions.
	 *
	 * @since 2.0.0
	 * @param array    $actions Existing actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function add_duplicate_action( array $actions, $post ): array {
		if ( 'event_listing' !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$duplicate_url = wp_nonce_url(
			admin_url( 'admin.php?action=apollo_duplicate_event&post=' . $post->ID ),
			'apollo_duplicate_' . $post->ID
		);

		$quick_duplicate_url = wp_nonce_url(
			admin_url( 'admin.php?action=apollo_quick_duplicate&post=' . $post->ID ),
			'apollo_quick_duplicate_' . $post->ID
		);

		$actions['duplicate'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $duplicate_url ),
			esc_attr__( 'Duplicar este evento', 'apollo-events' ),
			__( 'Duplicar', 'apollo-events' )
		);

		$actions['quick_duplicate'] = sprintf(
			'<a href="%s" title="%s" class="apollo-quick-duplicate">%s</a>',
			esc_url( $quick_duplicate_url ),
			esc_attr__( 'Duplicar rapidamente para próxima data', 'apollo-events' ),
			__( 'Duplicar +1 semana', 'apollo-events' )
		);

		return $actions;
	}

	/**
	 * Add bulk duplicate action.
	 *
	 * @since 2.0.0
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	public function add_bulk_duplicate_action( array $actions ): array {
		$actions['apollo_bulk_duplicate'] = __( 'Duplicar selecionados', 'apollo-events' );
		return $actions;
	}

	/**
	 * Handle bulk duplicate action.
	 *
	 * @since 2.0.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Action name.
	 * @param array  $post_ids    Post IDs.
	 * @return string
	 */
	public function handle_bulk_duplicate( string $redirect_to, string $doaction, array $post_ids ): string {
		if ( 'apollo_bulk_duplicate' !== $doaction ) {
			return $redirect_to;
		}

		$duplicated = 0;

		foreach ( $post_ids as $post_id ) {
			$new_id = $this->duplicate_event( $post_id );
			if ( $new_id ) {
				++$duplicated;
			}
		}

		return add_query_arg( 'apollo_duplicated', $duplicated, $redirect_to );
	}

	/**
	 * Handle duplicate action.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_duplicate_action(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'ID do evento não fornecido.', 'apollo-events' ) );
		}

		check_admin_referer( 'apollo_duplicate_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Você não tem permissão para duplicar este evento.', 'apollo-events' ) );
		}

		$new_id = $this->duplicate_event( $post_id );

		if ( $new_id ) {
			wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id . '&apollo_duplicated=1' ) );
			exit;
		}

		wp_die( esc_html__( 'Erro ao duplicar evento.', 'apollo-events' ) );
	}

	/**
	 * Handle quick duplicate action.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_quick_duplicate(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'ID do evento não fornecido.', 'apollo-events' ) );
		}

		check_admin_referer( 'apollo_quick_duplicate_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Você não tem permissão para duplicar este evento.', 'apollo-events' ) );
		}

		// Duplicate with +1 week offset.
		$new_id = $this->duplicate_event(
			$post_id,
			array(
				'date_offset' => '+1 week',
				'status'      => 'draft',
			)
		);

		if ( $new_id ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=event_listing&apollo_duplicated=1' ) );
			exit;
		}

		wp_die( esc_html__( 'Erro ao duplicar evento.', 'apollo-events' ) );
	}

	/**
	 * Duplicate an event.
	 *
	 * @since 2.0.0
	 * @param int   $post_id Original post ID.
	 * @param array $args    Additional arguments.
	 * @return int|false New post ID or false on failure.
	 */
	public function duplicate_event( int $post_id, array $args = array() ): int|false {
		$post = get_post( $post_id );

		if ( ! $post || 'event_listing' !== $post->post_type ) {
			return false;
		}

		$defaults = array(
			'title_prefix' => __( 'Cópia de', 'apollo-events' ),
			'title_suffix' => '',
			'date_offset'  => null,
			'status'       => 'draft',
			'copy_meta'    => true,
			'copy_terms'   => true,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build new title.
		$new_title = $post->post_title;
		if ( $args['title_prefix'] ) {
			$new_title = $args['title_prefix'] . ' ' . $new_title;
		}
		if ( $args['title_suffix'] ) {
			$new_title .= ' ' . $args['title_suffix'];
		}

		// Create new post.
		$new_post_data = array(
			'post_title'   => $new_title,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_status'  => $args['status'],
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
		);

		$new_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_id ) ) {
			return false;
		}

		// Copy meta.
		if ( $args['copy_meta'] ) {
			$this->copy_post_meta( $post_id, $new_id, $args['date_offset'] );
		}

		// Copy terms.
		if ( $args['copy_terms'] ) {
			$this->copy_post_terms( $post_id, $new_id );
		}

		// Copy featured image.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_id, $thumbnail_id );
		}

		/**
		 * Fires after event is duplicated.
		 *
		 * @since 2.0.0
		 * @param int   $new_id  New post ID.
		 * @param int   $post_id Original post ID.
		 * @param array $args    Duplication arguments.
		 */
		do_action( 'apollo_event_duplicated', $new_id, $post_id, $args );

		return $new_id;
	}

	/**
	 * Copy post meta.
	 *
	 * @since 2.0.0
	 * @param int         $source_id   Source post ID.
	 * @param int         $target_id   Target post ID.
	 * @param string|null $date_offset Date offset for date fields.
	 * @return void
	 */
	private function copy_post_meta( int $source_id, int $target_id, ?string $date_offset = null ): void {
		$excluded_keys = array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_event_interested_users',
			'_event_rsvps',
			'_event_reviews',
			'_event_tracking',
			'_event_community_photos',
		);

		/**
		 * Filter excluded meta keys during duplication.
		 *
		 * @since 2.0.0
		 * @param array $excluded_keys Excluded keys.
		 * @param int   $source_id     Source post ID.
		 */
		$excluded_keys = apply_filters( 'apollo_duplicate_excluded_meta', $excluded_keys, $source_id );

		$date_keys = array(
			'_event_start_date',
			'_event_end_date',
			'_event_start_time',
			'_event_end_time',
		);

		$meta = get_post_meta( $source_id );

		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, $excluded_keys, true ) ) {
				continue;
			}

			foreach ( $values as $value ) {
				$value = maybe_unserialize( $value );

				// Apply date offset if applicable.
				if ( $date_offset && in_array( $key, $date_keys, true ) && strtotime( $value ) ) {
					$value = wp_date( 'Y-m-d H:i:s', strtotime( $value . ' ' . $date_offset ) );
				}

				update_post_meta( $target_id, $key, $value );
			}
		}
	}

	/**
	 * Copy post terms.
	 *
	 * @since 2.0.0
	 * @param int $source_id Source post ID.
	 * @param int $target_id Target post ID.
	 * @return void
	 */
	private function copy_post_terms( int $source_id, int $target_id ): void {
		$taxonomies = get_object_taxonomies( 'event_listing' );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				wp_set_object_terms( $target_id, $terms, $taxonomy );
			}
		}
	}

	/**
	 * Add duplicate metabox.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_duplicate_metabox(): void {
		add_meta_box(
			'apollo_duplicate_options',
			__( 'Duplicar Evento', 'apollo-events' ),
			array( $this, 'render_duplicate_metabox' ),
			'event_listing',
			'side',
			'low'
		);
	}

	/**
	 * Render duplicate metabox.
	 *
	 * @since 2.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_duplicate_metabox( $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Salve o evento primeiro para poder duplicá-lo.', 'apollo-events' ) . '</p>';
			return;
		}

		$duplicate_url = wp_nonce_url(
			admin_url( 'admin.php?action=apollo_duplicate_event&post=' . $post->ID ),
			'apollo_duplicate_' . $post->ID
		);
		?>
		<div class="apollo-duplicate-box">
			<a href="<?php echo esc_url( $duplicate_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-admin-page"></span>
				<?php esc_html_e( 'Duplicar Evento', 'apollo-events' ); ?>
			</a>

			<hr>

			<h4><?php esc_html_e( 'Criar Série Recorrente', 'apollo-events' ); ?></h4>

			<div class="apollo-recurring-form">
				<p>
					<label for="apollo_recurring_count"><?php esc_html_e( 'Quantidade:', 'apollo-events' ); ?></label>
					<input type="number" id="apollo_recurring_count" min="1" max="52" value="4" class="small-text">
				</p>

				<p>
					<label for="apollo_recurring_interval"><?php esc_html_e( 'Intervalo:', 'apollo-events' ); ?></label>
					<select id="apollo_recurring_interval">
						<option value="day"><?php esc_html_e( 'Diário', 'apollo-events' ); ?></option>
						<option value="week" selected><?php esc_html_e( 'Semanal', 'apollo-events' ); ?></option>
						<option value="2 weeks"><?php esc_html_e( 'Quinzenal', 'apollo-events' ); ?></option>
						<option value="month"><?php esc_html_e( 'Mensal', 'apollo-events' ); ?></option>
					</select>
				</p>

				<button type="button" class="button button-primary apollo-create-recurring" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
					<span class="dashicons dashicons-calendar-alt"></span>
					<?php esc_html_e( 'Criar Série', 'apollo-events' ); ?>
				</button>

				<div class="apollo-recurring-progress" style="display:none;">
					<span class="spinner is-active"></span>
					<span class="progress-text"></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for creating recurring events.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_create_recurring(): void {
		check_ajax_referer( 'apollo_duplicate_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão', 'apollo-events' ) ), 403 );
		}

		$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$count    = isset( $_POST['count'] ) ? absint( $_POST['count'] ) : 4;
		$interval = isset( $_POST['interval'] ) ? sanitize_text_field( wp_unslash( $_POST['interval'] ) ) : 'week';

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'ID inválido', 'apollo-events' ) ), 400 );
		}

		$count = min( $count, 52 ); // Max 52 events.

		$created     = array();
		$date_offset = '+1 ' . $interval;

		for ( $i = 0; $i < $count; $i++ ) {
			$offset = '+' . ( $i + 1 ) . ' ' . $interval;

			$new_id = $this->duplicate_event(
				$post_id,
				array(
					'title_prefix' => '',
					'date_offset'  => $offset,
					'status'       => 'draft',
				)
			);

			if ( $new_id ) {
				$created[] = array(
					'id'    => $new_id,
					'title' => get_the_title( $new_id ),
					'link'  => get_edit_post_link( $new_id, 'raw' ),
				);
			}
		}

		wp_send_json_success(
			array(
				'created' => $created,
				'message' => sprintf(
				/* translators: %d: number of events created */
					__( '%d eventos criados com sucesso!', 'apollo-events' ),
					count( $created )
				),
			)
		);
	}

	/**
	 * AJAX handler for quick duplicate.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_quick_duplicate(): void {
		check_ajax_referer( 'apollo_duplicate_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sem permissão', 'apollo-events' ) ), 403 );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'ID inválido', 'apollo-events' ) ), 400 );
		}

		$new_id = $this->duplicate_event( $post_id );

		if ( $new_id ) {
			wp_send_json_success(
				array(
					'new_id'   => $new_id,
					'edit_url' => get_edit_post_link( $new_id, 'raw' ),
					'message'  => __( 'Evento duplicado!', 'apollo-events' ),
				)
			);
		}

		wp_send_json_error( array( 'message' => __( 'Erro ao duplicar', 'apollo-events' ) ), 500 );
	}

	/**
	 * Show duplicate notice.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function show_duplicate_notice(): void {
		if ( ! isset( $_GET['apollo_duplicated'] ) ) {
			return;
		}

		$count = absint( $_GET['apollo_duplicated'] );

		if ( $count > 0 ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: number of events duplicated */
					esc_html( _n( '%d evento duplicado com sucesso.', '%d eventos duplicados com sucesso.', $count, 'apollo-events' ) ),
					$count
				)
			);
		}
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
			__( 'Ferramentas de Duplicação', 'apollo-events' ),
			__( 'Duplicar em Lote', 'apollo-events' ),
			'edit_posts',
			'apollo-duplicate-tools',
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
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ferramentas de Duplicação', 'apollo-events' ); ?></h1>

			<div class="apollo-tools-grid">
				<div class="apollo-tool-card">
					<h2><?php esc_html_e( 'Duplicar Evento Existente', 'apollo-events' ); ?></h2>
					<p><?php esc_html_e( 'Selecione um evento para duplicar:', 'apollo-events' ); ?></p>

					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
						<input type="hidden" name="action" value="apollo_duplicate_event">
						<?php wp_nonce_field( 'apollo_duplicate_select', '_wpnonce', false ); ?>

						<?php
						$events = get_posts(
							array(
								'post_type'      => 'event_listing',
								'posts_per_page' => -1,
								'orderby'        => 'title',
								'order'          => 'ASC',
							)
						);
						?>

						<select name="post" required style="width: 100%; margin-bottom: 10px;">
							<option value=""><?php esc_html_e( '— Selecione um evento —', 'apollo-events' ); ?></option>
							<?php foreach ( $events as $event ) : ?>
								<option value="<?php echo esc_attr( $event->ID ); ?>">
									<?php echo esc_html( $event->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>

						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Duplicar', 'apollo-events' ); ?>
						</button>
					</form>
				</div>

				<div class="apollo-tool-card">
					<h2><?php esc_html_e( 'Criar Template', 'apollo-events' ); ?></h2>
					<p><?php esc_html_e( 'Crie um template reutilizável a partir de um evento:', 'apollo-events' ); ?></p>

					<form method="post" action="">
						<?php wp_nonce_field( 'apollo_create_template' ); ?>

						<select name="source_event" required style="width: 100%; margin-bottom: 10px;">
							<option value=""><?php esc_html_e( '— Selecione um evento —', 'apollo-events' ); ?></option>
							<?php foreach ( $events as $event ) : ?>
								<option value="<?php echo esc_attr( $event->ID ); ?>">
									<?php echo esc_html( $event->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>

						<input type="text" name="template_name" placeholder="<?php esc_attr_e( 'Nome do template', 'apollo-events' ); ?>"
								style="width: 100%; margin-bottom: 10px;" required>

						<button type="submit" name="action" value="create_template" class="button button-secondary">
							<?php esc_html_e( 'Criar Template', 'apollo-events' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>

		<style>
			.apollo-tools-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}
			.apollo-tool-card {
				background: #fff;
				padding: 20px;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
			}
			.apollo-tool-card h2 {
				margin-top: 0;
			}
		</style>
		<?php
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'default_status' => array(
				'type'        => 'select',
				'label'       => __( 'Status padrão', 'apollo-events' ),
				'description' => __( 'Status dos eventos duplicados.', 'apollo-events' ),
				'options'     => array(
					'draft'   => __( 'Rascunho', 'apollo-events' ),
					'pending' => __( 'Pendente', 'apollo-events' ),
					'publish' => __( 'Publicado', 'apollo-events' ),
				),
				'default'     => 'draft',
			),
			'copy_author'    => array(
				'type'    => 'boolean',
				'label'   => __( 'Manter autor original', 'apollo-events' ),
				'default' => false,
			),
			'add_prefix'     => array(
				'type'    => 'boolean',
				'label'   => __( 'Adicionar "Cópia de" ao título', 'apollo-events' ),
				'default' => true,
			),
		);
	}
}
