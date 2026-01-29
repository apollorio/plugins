<?php
/**
 * Apollo Supplier CPT Migration
 *
 * Migrates legacy 'supplier' CPT to canonical 'apollo_supplier' CPT.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Resolves: supplier vs apollo_supplier CPT conflict
 * - apollo-core/templates/apollo-suppliers-functions.php:83 (supplier)
 * - apollo-social/src/Modules/Suppliers/SuppliersModule.php:171 (apollo_supplier)
 *
 * Canonical: apollo_supplier (owned by apollo-social)
 */

declare(strict_types=1);

namespace Apollo_Core\Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Supplier_Migration
 *
 * Handles one-time migration from legacy 'supplier' CPT to 'apollo_supplier'.
 */
class Supplier_Migration {

	/**
	 * Legacy post type
	 */
	private const LEGACY_POST_TYPE = 'supplier';

	/**
	 * Canonical post type
	 */
	private const CANONICAL_POST_TYPE = 'apollo_supplier';

	/**
	 * Legacy taxonomies
	 */
	private const LEGACY_TAXONOMIES = array(
		'supplier_category' => 'apollo_supplier_category',
		'supplier_tag'      => 'apollo_supplier_tag',
	);

	/**
	 * Migration option key
	 */
	private const MIGRATION_OPTION = 'apollo_supplier_migration_completed';

	/**
	 * Singleton instance
	 *
	 * @var Supplier_Migration|null
	 */
	private static ?Supplier_Migration $instance = null;

	/**
	 * Migration log
	 *
	 * @var array
	 */
	private array $log = array();

	/**
	 * Get singleton instance
	 *
	 * @return Supplier_Migration
	 */
	public static function get_instance(): Supplier_Migration {
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
		// Admin notice for pending migration.
		\add_action( 'admin_notices', array( $this, 'migration_notice' ) );

		// AJAX handler for migration.
		\add_action( 'wp_ajax_apollo_migrate_suppliers', array( $this, 'ajax_migrate' ) );

		// Admin menu for migration tool.
		\add_action( 'admin_menu', array( $this, 'add_migration_menu' ) );
	}

	/**
	 * Check if migration is needed
	 *
	 * @return bool
	 */
	public function needs_migration(): bool {
		// Already migrated.
		if ( \get_option( self::MIGRATION_OPTION ) ) {
			return false;
		}

		// Check if legacy posts exist.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				self::LEGACY_POST_TYPE
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Get count of legacy posts
	 *
	 * @return int
	 */
	public function get_legacy_count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				self::LEGACY_POST_TYPE
			)
		);
	}

	/**
	 * Display migration notice
	 *
	 * @return void
	 */
	public function migration_notice(): void {
		if ( ! $this->needs_migration() ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$count       = $this->get_legacy_count();
		$migrate_url = \admin_url( 'tools.php?page=apollo-supplier-migration' );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Apollo: Migração de Fornecedores Necessária', 'apollo-core' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %d: number of suppliers to migrate */
					esc_html__( 'Foram encontrados %d fornecedores no formato antigo que precisam ser migrados.', 'apollo-core' ),
					$count
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
			__( 'Apollo Supplier Migration', 'apollo-core' ),
			__( 'Migração de Fornecedores', 'apollo-core' ),
			'manage_options',
			'apollo-supplier-migration',
			array( $this, 'migration_page' )
		);
	}

	/**
	 * Render migration page
	 *
	 * @return void
	 */
	public function migration_page(): void {
		$count = $this->get_legacy_count();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo: Migração de Fornecedores', 'apollo-core' ); ?></h1>

			<div class="card" style="max-width: 600px; padding: 20px;">
				<h2><?php esc_html_e( 'Informações da Migração', 'apollo-core' ); ?></h2>

				<table class="widefat" style="margin: 20px 0;">
					<tr>
						<th><?php esc_html_e( 'Post Type Legado', 'apollo-core' ); ?></th>
						<td><code>supplier</code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Post Type Canônico', 'apollo-core' ); ?></th>
						<td><code>apollo_supplier</code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Posts para Migrar', 'apollo-core' ); ?></th>
						<td><strong><?php echo esc_html( $count ); ?></strong></td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'O que será migrado:', 'apollo-core' ); ?></h3>
				<ul>
					<li>✅ <?php esc_html_e( 'Post type será atualizado para apollo_supplier', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Taxonomias serão remapeadas', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Meta fields serão preservados', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Attachments serão mantidos', 'apollo-core' ); ?></li>
				</ul>

				<p class="description">
					<?php esc_html_e( 'Recomendamos fazer backup do banco de dados antes de prosseguir.', 'apollo-core' ); ?>
				</p>

				<div id="apollo-migration-progress" style="display: none; margin: 20px 0;">
					<div class="progress-bar" style="background: #f0f0f0; height: 20px; border-radius: 3px;">
						<div class="progress-fill" style="background: #0073aa; height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s;"></div>
					</div>
					<p class="progress-text" style="margin-top: 10px;"></p>
				</div>

				<div id="apollo-migration-result" style="display: none; margin: 20px 0;"></div>

				<p>
					<button type="button" id="apollo-start-migration" class="button button-primary button-hero">
						<?php esc_html_e( 'Iniciar Migração', 'apollo-core' ); ?>
					</button>
				</p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#apollo-start-migration').on('click', function() {
				var $btn = $(this);
				var $progress = $('#apollo-migration-progress');
				var $result = $('#apollo-migration-result');

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Migrando...', 'apollo-core' ); ?>');
				$progress.show();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_migrate_suppliers',
						_wpnonce: '<?php echo esc_js( \wp_create_nonce( 'apollo_migrate_suppliers' ) ); ?>'
					},
					success: function(response) {
						$progress.hide();

						if (response.success) {
							$result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
							$btn.text('<?php esc_html_e( 'Migração Concluída', 'apollo-core' ); ?>');
						} else {
							$result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
							$btn.prop('disabled', false).text('<?php esc_html_e( 'Tentar Novamente', 'apollo-core' ); ?>');
						}
					},
					error: function() {
						$progress.hide();
						$result.html('<div class="notice notice-error"><p><?php esc_html_e( 'Erro de conexão.', 'apollo-core' ); ?></p></div>').show();
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Tentar Novamente', 'apollo-core' ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler for migration
	 *
	 * @return void
	 */
	public function ajax_migrate(): void {
		\check_ajax_referer( 'apollo_migrate_suppliers' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-core' ) ) );
		}

		$result = $this->run_migration();

		if ( $result['success'] ) {
			\wp_send_json_success( $result );
		} else {
			\wp_send_json_error( $result );
		}
	}

	/**
	 * Run the migration
	 *
	 * @return array
	 */
	public function run_migration(): array {
		global $wpdb;

		$this->log = array();
		$migrated  = 0;
		$errors    = 0;

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1. Update post types.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$wpdb->posts,
				array( 'post_type' => self::CANONICAL_POST_TYPE ),
				array( 'post_type' => self::LEGACY_POST_TYPE ),
				array( '%s' ),
				array( '%s' )
			);

			if ( false === $result ) {
				throw new \Exception( __( 'Erro ao atualizar post types.', 'apollo-core' ) );
			}

			$migrated    = $result;
			$this->log[] = \sprintf(
				/* translators: %d: number of posts migrated */
				__( '%d posts migrados para apollo_supplier.', 'apollo-core' ),
				$migrated
			);

			// 2. Update taxonomy relationships.
			foreach ( self::LEGACY_TAXONOMIES as $legacy_tax => $new_tax ) {
				// Check if new taxonomy exists.
				if ( ! \taxonomy_exists( $new_tax ) && \taxonomy_exists( $legacy_tax ) ) {
					// Rename taxonomy.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$wpdb->term_taxonomy,
						array( 'taxonomy' => $new_tax ),
						array( 'taxonomy' => $legacy_tax ),
						array( '%s' ),
						array( '%s' )
					);

					$this->log[] = \sprintf(
						/* translators: %s: taxonomy name */
						__( 'Taxonomia %s renomeada.', 'apollo-core' ),
						$legacy_tax
					);
				}
			}

			// 3. Mark migration as complete.
			\update_option(
				self::MIGRATION_OPTION,
				array(
					'completed_at' => \current_time( 'mysql' ),
					'migrated'     => $migrated,
					'log'          => $this->log,
				)
			);

			// Commit transaction.
			$wpdb->query( 'COMMIT' );

			// Flush rewrite rules.
			\flush_rewrite_rules();

			return array(
				'success'  => true,
				'migrated' => $migrated,
				'message'  => \sprintf(
					/* translators: %d: number of suppliers migrated */
					__( 'Migração concluída! %d fornecedores foram migrados para o novo formato.', 'apollo-core' ),
					$migrated
				),
				'log'      => $this->log,
			);

		} catch ( \Exception $e ) {
			// Rollback on error.
			$wpdb->query( 'ROLLBACK' );

			return array(
				'success' => false,
				'message' => $e->getMessage(),
				'log'     => $this->log,
			);
		}
	}

	/**
	 * Check if migration was completed
	 *
	 * @return bool
	 */
	public function is_migrated(): bool {
		return (bool) \get_option( self::MIGRATION_OPTION );
	}

	/**
	 * Get migration data
	 *
	 * @return array|null
	 */
	public function get_migration_data(): ?array {
		$data = \get_option( self::MIGRATION_OPTION );
		return $data ? $data : null;
	}
}

// Initialize migration.
Supplier_Migration::get_instance();
