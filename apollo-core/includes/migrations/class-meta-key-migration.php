<?php
/**
 * Apollo Meta Key Migration
 *
 * Migrates deprecated meta keys to canonical versions.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Migrations:
 * - _event_lat → _event_latitude
 * - _event_lng → _event_longitude
 * - _event_timetable → _event_dj_slots
 * - _event_djs → _event_dj_ids (with transformation)
 * - _event_local → _event_local_ids (with transformation)
 * - _local_lat → _local_latitude
 * - _local_lng → _local_longitude
 */

declare(strict_types=1);

namespace Apollo_Core\Migration;

use Apollo_Core\Meta\Apollo_Meta_Keys;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Meta_Key_Migration
 *
 * Handles migration of deprecated meta keys.
 */
class Meta_Key_Migration {

	/**
	 * Migration option key
	 *
	 * @var string
	 */
	private const MIGRATION_OPTION = 'apollo_meta_key_migration';

	/**
	 * Current migration version
	 *
	 * @var string
	 */
	private const MIGRATION_VERSION = '2.0.0';

	/**
	 * Batch size for migration
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Singleton instance
	 *
	 * @var Meta_Key_Migration|null
	 */
	private static ?Meta_Key_Migration $instance = null;

	/**
	 * Migration log
	 *
	 * @var array
	 */
	private array $log = array();

	/**
	 * Get singleton instance
	 *
	 * @return Meta_Key_Migration
	 */
	public static function get_instance(): Meta_Key_Migration {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		\add_action( 'admin_notices', array( $this, 'migration_notice' ) );
		\add_action( 'wp_ajax_apollo_migrate_meta_keys', array( $this, 'ajax_migrate' ) );
		\add_action( 'admin_menu', array( $this, 'add_migration_menu' ) );

		// CLI command.
		if ( \defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'apollo migrate-meta', array( $this, 'cli_migrate' ) );
		}
	}

	/**
	 * Check if migration is needed
	 *
	 * @return bool
	 */
	public function needs_migration(): bool {
		$completed = \get_option( self::MIGRATION_OPTION );

		if ( $completed && isset( $completed['version'] ) && $completed['version'] === self::MIGRATION_VERSION ) {
			return false;
		}

		// Check for deprecated keys.
		return $this->has_deprecated_meta();
	}

	/**
	 * Check if any deprecated meta exists
	 *
	 * @return bool
	 */
	private function has_deprecated_meta(): bool {
		global $wpdb;

		$deprecated_keys = \array_keys( Apollo_Meta_Keys::get_deprecated_keys() );

		if ( empty( $deprecated_keys ) ) {
			return false;
		}

		$placeholders = \implode( ', ', \array_fill( 0, \count( $deprecated_keys ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders)",
				...$deprecated_keys
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Get migration statistics
	 *
	 * @return array
	 */
	public function get_migration_stats(): array {
		global $wpdb;

		$stats = array(
			'keys'         => array(),
			'total'        => 0,
			'by_post_type' => array(),
		);

		$deprecated = Apollo_Meta_Keys::get_deprecated_keys();

		foreach ( $deprecated as $old_key => $new_key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$old_key
				)
			);

			if ( (int) $count > 0 ) {
				$stats['keys'][ $old_key ] = array(
					'count'   => (int) $count,
					'new_key' => $new_key,
				);
				$stats['total']           += (int) $count;
			}
		}

		return $stats;
	}

	/**
	 * Display migration notice
	 *
	 * @return void
	 */
	public function migration_notice(): void {
		if ( ! $this->needs_migration() || ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats       = $this->get_migration_stats();
		$migrate_url = \admin_url( 'tools.php?page=apollo-meta-key-migration' );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Apollo: Migração de Meta Keys', 'apollo-core' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %d: number of meta entries */
					esc_html__( 'Encontradas %d entradas de meta com chaves obsoletas que precisam ser migradas.', 'apollo-core' ),
					$stats['total']
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $migrate_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Iniciar Migração', 'apollo-core' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Add migration menu
	 *
	 * @return void
	 */
	public function add_migration_menu(): void {
		if ( ! $this->needs_migration() ) {
			return;
		}

		\add_management_page(
			__( 'Migração de Meta Keys Apollo', 'apollo-core' ),
			__( 'Meta Keys Apollo', 'apollo-core' ),
			'manage_options',
			'apollo-meta-key-migration',
			array( $this, 'migration_page' )
		);
	}

	/**
	 * Render migration page
	 *
	 * @return void
	 */
	public function migration_page(): void {
		$stats = $this->get_migration_stats();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo: Migração de Meta Keys', 'apollo-core' ); ?></h1>

			<div class="card" style="max-width: 800px; padding: 20px;">
				<h2><?php esc_html_e( 'Chaves Obsoletas Encontradas', 'apollo-core' ); ?></h2>

				<table class="widefat striped" style="margin: 20px 0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Chave Obsoleta', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Nova Chave', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Registros', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['keys'] as $old_key => $info ) : ?>
							<tr>
								<td><code><?php echo esc_html( $old_key ); ?></code></td>
								<td><code><?php echo esc_html( $info['new_key'] ); ?></code></td>
								<td><?php echo esc_html( $info['count'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="2"><strong><?php esc_html_e( 'Total', 'apollo-core' ); ?></strong></th>
							<th><strong><?php echo esc_html( $stats['total'] ); ?></strong></th>
						</tr>
					</tfoot>
				</table>

				<h3><?php esc_html_e( 'O que será migrado:', 'apollo-core' ); ?></h3>
				<ul>
					<li><code>_event_lat</code> → <code>_event_latitude</code> (coordenada)</li>
					<li><code>_event_lng</code> → <code>_event_longitude</code> (coordenada)</li>
					<li><code>_event_timetable</code> → <code>_event_dj_slots</code> (timetable)</li>
					<li><code>_event_djs</code> → <code>_event_dj_ids</code> (relacionamento)</li>
					<li><code>_event_local</code> → <code>_event_local_ids</code> (relacionamento)</li>
					<li><code>_local_lat</code> → <code>_local_latitude</code> (coordenada)</li>
					<li><code>_local_lng</code> → <code>_local_longitude</code> (coordenada)</li>
				</ul>

				<div class="notice notice-info inline" style="margin: 20px 0;">
					<p>
						<strong><?php esc_html_e( 'Nota:', 'apollo-core' ); ?></strong>
						<?php esc_html_e( 'As chaves obsoletas serão removidas após a migração. Faça backup do banco antes de continuar.', 'apollo-core' ); ?>
					</p>
				</div>

				<div id="apollo-meta-migration-progress" style="display: none; margin: 20px 0;">
					<div class="progress-bar" style="background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
						<div class="progress-fill" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
					</div>
					<p class="progress-text" style="margin-top: 10px;"></p>
				</div>

				<div id="apollo-meta-migration-result" style="display: none; margin: 20px 0;"></div>

				<p>
					<label>
						<input type="checkbox" id="apollo-meta-delete-old" checked>
						<?php esc_html_e( 'Remover chaves obsoletas após migração', 'apollo-core' ); ?>
					</label>
				</p>

				<p>
					<button type="button" id="apollo-start-meta-migration" class="button button-primary button-hero">
						<?php esc_html_e( 'Iniciar Migração', 'apollo-core' ); ?>
					</button>
				</p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var totalBatches = Math.ceil(<?php echo esc_js( $stats['total'] ); ?> / <?php echo esc_js( self::BATCH_SIZE ); ?>);
			var currentBatch = 0;

			function runBatch() {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_migrate_meta_keys',
						_wpnonce: '<?php echo esc_js( \wp_create_nonce( 'apollo_migrate_meta_keys' ) ); ?>',
						batch: currentBatch,
						delete_old: $('#apollo-meta-delete-old').is(':checked') ? 1 : 0
					},
					success: function(response) {
						if (response.success) {
							currentBatch++;
							var progress = Math.min(100, (currentBatch / Math.max(1, totalBatches)) * 100);
							$('.progress-fill').css('width', progress + '%');
							$('.progress-text').text(response.data.message);

							if (response.data.complete) {
								$('#apollo-meta-migration-result').html(
									'<div class="notice notice-success"><p>' +
									'<?php esc_html_e( 'Migração concluída com sucesso!', 'apollo-core' ); ?>' +
									'</p><pre style="max-height:200px;overflow:auto;">' +
									response.data.log.join('\n') +
									'</pre></div>'
								).show();
								$('#apollo-start-meta-migration').text('<?php esc_html_e( 'Concluído', 'apollo-core' ); ?>');
							} else {
								runBatch();
							}
						} else {
							$('#apollo-meta-migration-result').html(
								'<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
							).show();
							$('#apollo-start-meta-migration').prop('disabled', false).text('<?php esc_html_e( 'Tentar Novamente', 'apollo-core' ); ?>');
						}
					},
					error: function() {
						$('#apollo-meta-migration-result').html(
							'<div class="notice notice-error"><p><?php esc_html_e( 'Erro de conexão.', 'apollo-core' ); ?></p></div>'
						).show();
						$('#apollo-start-meta-migration').prop('disabled', false).text('<?php esc_html_e( 'Tentar Novamente', 'apollo-core' ); ?>');
					}
				});
			}

			$('#apollo-start-meta-migration').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Migrando...', 'apollo-core' ); ?>');
				$('#apollo-meta-migration-progress').show();
				$('#apollo-meta-migration-result').hide();
				currentBatch = 0;
				runBatch();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler
	 *
	 * @return void
	 */
	public function ajax_migrate(): void {
		\check_ajax_referer( 'apollo_migrate_meta_keys' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-core' ) ) );
		}

		$batch      = isset( $_POST['batch'] ) ? \absint( $_POST['batch'] ) : 0;
		$delete_old = isset( $_POST['delete_old'] ) && $_POST['delete_old'] === '1';

		$result = $this->run_batch( $batch, $delete_old );

		\wp_send_json_success( $result );
	}

	/**
	 * Run a migration batch
	 *
	 * @param int  $batch      Batch number.
	 * @param bool $delete_old Whether to delete old keys.
	 * @return array Result.
	 */
	public function run_batch( int $batch, bool $delete_old = true ): array {
		global $wpdb;

		$this->log  = array();
		$offset     = $batch * self::BATCH_SIZE;
		$migrated   = 0;
		$deprecated = Apollo_Meta_Keys::get_deprecated_keys();

		foreach ( $deprecated as $old_key => $new_key ) {
			// Get posts with this old key.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				ORDER BY meta_id
				LIMIT %d OFFSET %d",
					$old_key,
					self::BATCH_SIZE,
					$offset
				)
			);

			foreach ( $rows as $row ) {
				$post_id = (int) $row->post_id;
				$value   = $row->meta_value;

				// Transform value if needed.
				$value = $this->transform_value( $old_key, $value );

				// Check if new key already has value.
				$existing = \get_post_meta( $post_id, $new_key, true );

				if ( empty( $existing ) ) {
					\update_post_meta( $post_id, $new_key, $value );
					$this->log[] = \sprintf( '✅ Post %d: %s → %s', $post_id, $old_key, $new_key );
				} else {
					$this->log[] = \sprintf( '⏭️ Post %d: %s já existe, pulando', $post_id, $new_key );
				}

				// Delete old key.
				if ( $delete_old ) {
					\delete_post_meta( $post_id, $old_key );
				}

				++$migrated;
			}
		}

		// Check if complete.
		$complete = ! $this->has_deprecated_meta();

		if ( $complete ) {
			\update_option(
				self::MIGRATION_OPTION,
				array(
					'version'      => self::MIGRATION_VERSION,
					'completed_at' => \current_time( 'mysql' ),
					'migrated'     => $migrated,
				)
			);
		}

		return array(
			'batch'    => $batch,
			'migrated' => $migrated,
			'complete' => $complete,
			'message'  => $complete
				? __( 'Migração concluída!', 'apollo-core' )
				: \sprintf(
					/* translators: %d: batch number */
					__( 'Batch %d processado...', 'apollo-core' ),
					$batch + 1
				),
			'log'      => $this->log,
		);
	}

	/**
	 * Transform value during migration
	 *
	 * @param string $old_key Original key.
	 * @param mixed  $value   Original value.
	 * @return mixed Transformed value.
	 */
	private function transform_value( string $old_key, mixed $value ): mixed {
		switch ( $old_key ) {
			case '_event_lat':
			case '_event_lng':
			case '_local_lat':
			case '_local_lng':
				// Ensure float.
				return (float) $value;

			case '_event_djs':
				// Convert comma-separated IDs or single ID to array.
				if ( \is_string( $value ) ) {
					if ( \strpos( $value, ',' ) !== false ) {
						return \array_map( 'intval', \explode( ',', $value ) );
					}
					return array( (int) $value );
				}
				if ( \is_numeric( $value ) ) {
					return array( (int) $value );
				}
				return \maybe_unserialize( $value );

			case '_event_local':
				// Convert single local ID to array.
				if ( \is_numeric( $value ) ) {
					return array( (int) $value );
				}
				return \maybe_unserialize( $value );

			case '_event_timetable':
				// Ensure array format.
				$unserialized = \maybe_unserialize( $value );
				return \is_array( $unserialized ) ? $unserialized : array();

			default:
				return \maybe_unserialize( $value );
		}
	}

	/**
	 * Run full migration (for CLI)
	 *
	 * @return array Result.
	 */
	public function run_full_migration(): array {
		$batch    = 0;
		$total    = 0;
		$all_logs = array();

		while ( true ) {
			$result   = $this->run_batch( $batch, true );
			$total   += $result['migrated'];
			$all_logs = \array_merge( $all_logs, $result['log'] );

			if ( $result['complete'] ) {
				break;
			}

			++$batch;
		}

		return array(
			'batches' => $batch + 1,
			'total'   => $total,
			'log'     => $all_logs,
		);
	}

	/**
	 * CLI command handler
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named args.
	 * @return void
	 */
	public function cli_migrate( array $args, array $assoc_args ): void {
		if ( ! $this->needs_migration() ) {
			\WP_CLI::success( 'Nenhuma migração necessária.' );
			return;
		}

		$stats = $this->get_migration_stats();
		\WP_CLI::log( \sprintf( 'Encontradas %d entradas para migrar.', $stats['total'] ) );

		$result = $this->run_full_migration();

		foreach ( $result['log'] as $line ) {
			\WP_CLI::log( $line );
		}

		\WP_CLI::success(
			\sprintf(
				'Migração concluída! %d entradas em %d batches.',
				$result['total'],
				$result['batches']
			)
		);
	}

	/**
	 * Migrate a single post's meta keys
	 *
	 * @param int  $post_id    Post ID.
	 * @param bool $delete_old Whether to delete old keys.
	 * @return array Migrated keys.
	 */
	public function migrate_single_post( int $post_id, bool $delete_old = true ): array {
		$deprecated = Apollo_Meta_Keys::get_deprecated_keys();
		$migrated   = array();

		foreach ( $deprecated as $old_key => $new_key ) {
			$value = \get_post_meta( $post_id, $old_key, true );

			if ( $value !== '' && $value !== null ) {
				$value    = $this->transform_value( $old_key, $value );
				$existing = \get_post_meta( $post_id, $new_key, true );

				if ( empty( $existing ) ) {
					\update_post_meta( $post_id, $new_key, $value );
					$migrated[] = $old_key;
				}

				if ( $delete_old ) {
					\delete_post_meta( $post_id, $old_key );
				}
			}
		}

		return $migrated;
	}
}

// Initialize migration.
Meta_Key_Migration::get_instance();
