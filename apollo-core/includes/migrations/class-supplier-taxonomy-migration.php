<?php
/**
 * Apollo Supplier Taxonomy Migration
 *
 * Migrates legacy supplier taxonomies to canonical apollo_supplier_* taxonomies.
 *
 * @package Apollo_Core
 * @since 2.0.0
 *
 * Resolves:
 * - supplier_category (apollo-core) → apollo_supplier_category (apollo-social)
 * - supplier_tag (apollo-core) → apollo_supplier_tag (apollo-social)
 */

declare(strict_types=1);

namespace Apollo_Core\Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Supplier_Taxonomy_Migration
 *
 * Handles migration of taxonomy terms and relationships.
 */
class Supplier_Taxonomy_Migration {

	/**
	 * Migration mappings
	 *
	 * @var array<string, string>
	 */
	private const TAXONOMY_MAPPINGS = array(
		'supplier_category' => 'apollo_supplier_category',
		'supplier_tag'      => 'apollo_supplier_tag',
	);

	/**
	 * Post type mappings
	 *
	 * @var array<string, string>
	 */
	private const POST_TYPE_MAPPINGS = array(
		'supplier' => 'apollo_supplier',
	);

	/**
	 * Migration option key
	 *
	 * @var string
	 */
	private const MIGRATION_OPTION = 'apollo_supplier_taxonomy_migration_completed';

	/**
	 * Singleton instance
	 *
	 * @var Supplier_Taxonomy_Migration|null
	 */
	private static ?Supplier_Taxonomy_Migration $instance = null;

	/**
	 * Migration log
	 *
	 * @var array
	 */
	private array $log = array();

	/**
	 * Get singleton instance
	 *
	 * @return Supplier_Taxonomy_Migration
	 */
	public static function get_instance(): Supplier_Taxonomy_Migration {
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

		// AJAX handler.
		\add_action( 'wp_ajax_apollo_migrate_supplier_taxonomies', array( $this, 'ajax_migrate' ) );

		// Admin menu.
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

		// Check if legacy taxonomies have terms.
		foreach ( self::TAXONOMY_MAPPINGS as $legacy_tax => $new_tax ) {
			if ( \taxonomy_exists( $legacy_tax ) ) {
				$terms = \get_terms(
					array(
						'taxonomy'   => $legacy_tax,
						'hide_empty' => false,
						'number'     => 1,
					)
				);

				if ( ! \is_wp_error( $terms ) && ! empty( $terms ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get migration statistics
	 *
	 * @return array
	 */
	public function get_migration_stats(): array {
		$stats = array(
			'taxonomies'          => array(),
			'total_terms'         => 0,
			'total_relationships' => 0,
		);

		foreach ( self::TAXONOMY_MAPPINGS as $legacy_tax => $new_tax ) {
			if ( ! \taxonomy_exists( $legacy_tax ) ) {
				continue;
			}

			$terms = \get_terms(
				array(
					'taxonomy'   => $legacy_tax,
					'hide_empty' => false,
				)
			);

			if ( \is_wp_error( $terms ) ) {
				continue;
			}

			$term_count         = \count( $terms );
			$relationship_count = 0;

			foreach ( $terms as $term ) {
				$posts = \get_posts(
					array(
						'post_type'      => 'supplier',
						'posts_per_page' => -1,
						'tax_query'      => array(
							array(
								'taxonomy' => $legacy_tax,
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
						'fields'         => 'ids',
					)
				);

				$relationship_count += \count( $posts );
			}

			$stats['taxonomies'][ $legacy_tax ] = array(
				'new_taxonomy'  => $new_tax,
				'terms'         => $term_count,
				'relationships' => $relationship_count,
			);

			$stats['total_terms']         += $term_count;
			$stats['total_relationships'] += $relationship_count;
		}

		return $stats;
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

		$stats       = $this->get_migration_stats();
		$migrate_url = \admin_url( 'tools.php?page=apollo-supplier-taxonomy-migration' );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Apollo: Migração de Taxonomias de Fornecedores', 'apollo-core' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %1$d: terms, %2$d: relationships */
					esc_html__( 'Encontrados %1$d termos e %2$d relacionamentos para migrar.', 'apollo-core' ),
					$stats['total_terms'],
					$stats['total_relationships']
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
			__( 'Migração de Taxonomias Apollo', 'apollo-core' ),
			__( 'Migração Taxonomias', 'apollo-core' ),
			'manage_options',
			'apollo-supplier-taxonomy-migration',
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
			<h1><?php esc_html_e( 'Apollo: Migração de Taxonomias de Fornecedores', 'apollo-core' ); ?></h1>

			<div class="card" style="max-width: 700px; padding: 20px;">
				<h2><?php esc_html_e( 'Detalhes da Migração', 'apollo-core' ); ?></h2>

				<table class="widefat striped" style="margin: 20px 0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Taxonomia Legada', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Nova Taxonomia', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Termos', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Relacionamentos', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['taxonomies'] as $legacy_tax => $info ) : ?>
							<tr>
								<td><code><?php echo esc_html( $legacy_tax ); ?></code></td>
								<td><code><?php echo esc_html( $info['new_taxonomy'] ); ?></code></td>
								<td><?php echo esc_html( $info['terms'] ); ?></td>
								<td><?php echo esc_html( $info['relationships'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="2"><strong><?php esc_html_e( 'Total', 'apollo-core' ); ?></strong></th>
							<th><strong><?php echo esc_html( $stats['total_terms'] ); ?></strong></th>
							<th><strong><?php echo esc_html( $stats['total_relationships'] ); ?></strong></th>
						</tr>
					</tfoot>
				</table>

				<h3><?php esc_html_e( 'O que será migrado:', 'apollo-core' ); ?></h3>
				<ul>
					<li>✅ <?php esc_html_e( 'Termos serão copiados para as novas taxonomias', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Relacionamentos de posts serão atualizados', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Metadados de termos serão preservados', 'apollo-core' ); ?></li>
					<li>✅ <?php esc_html_e( 'Hierarquia será mantida (para categorias)', 'apollo-core' ); ?></li>
				</ul>

				<div class="notice notice-info inline" style="margin: 20px 0;">
					<p>
						<strong><?php esc_html_e( 'Nota:', 'apollo-core' ); ?></strong>
						<?php esc_html_e( 'As taxonomias legadas não serão excluídas automaticamente. Você poderá removê-las manualmente após verificar a migração.', 'apollo-core' ); ?>
					</p>
				</div>

				<div id="apollo-tax-migration-progress" style="display: none; margin: 20px 0;">
					<div class="progress-bar" style="background: #f0f0f0; height: 20px; border-radius: 3px;">
						<div class="progress-fill" style="background: #0073aa; height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s;"></div>
					</div>
					<p class="progress-text" style="margin-top: 10px;"></p>
				</div>

				<div id="apollo-tax-migration-result" style="display: none; margin: 20px 0;"></div>

				<p>
					<button type="button" id="apollo-start-tax-migration" class="button button-primary button-hero">
						<?php esc_html_e( 'Iniciar Migração', 'apollo-core' ); ?>
					</button>
				</p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#apollo-start-tax-migration').on('click', function() {
				var $btn = $(this);
				var $progress = $('#apollo-tax-migration-progress');
				var $result = $('#apollo-tax-migration-result');

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Migrando...', 'apollo-core' ); ?>');
				$progress.show();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_migrate_supplier_taxonomies',
						_wpnonce: '<?php echo esc_js( \wp_create_nonce( 'apollo_migrate_supplier_taxonomies' ) ); ?>'
					},
					success: function(response) {
						$progress.hide();

						if (response.success) {
							$result.html('<div class="notice notice-success"><p>' + response.data.message + '</p><pre style="max-height:200px;overflow:auto;">' + response.data.log.join('\n') + '</pre></div>').show();
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
	 * AJAX handler
	 *
	 * @return void
	 */
	public function ajax_migrate(): void {
		\check_ajax_referer( 'apollo_migrate_supplier_taxonomies' );

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
		$this->log              = array();
		$migrated_terms         = 0;
		$migrated_relationships = 0;
		$errors                 = 0;

		// Ensure new taxonomies exist.
		if ( ! \taxonomy_exists( 'apollo_supplier_category' ) ) {
			$this->log[] = __( '⚠️ apollo_supplier_category não existe. Apollo Social pode estar inativo.', 'apollo-core' );
		}

		foreach ( self::TAXONOMY_MAPPINGS as $legacy_tax => $new_tax ) {
			if ( ! \taxonomy_exists( $legacy_tax ) ) {
				$this->log[] = \sprintf(
					/* translators: %s: taxonomy name */
					__( 'Taxonomia legada "%s" não existe, pulando.', 'apollo-core' ),
					$legacy_tax
				);
				continue;
			}

			// Get all terms from legacy taxonomy.
			$terms = \get_terms(
				array(
					'taxonomy'   => $legacy_tax,
					'hide_empty' => false,
					'orderby'    => 'parent',
					'order'      => 'ASC',
				)
			);

			if ( \is_wp_error( $terms ) ) {
				$this->log[] = \sprintf(
					/* translators: %s: error message */
					__( 'Erro ao buscar termos: %s', 'apollo-core' ),
					$terms->get_error_message()
				);
				++$errors;
				continue;
			}

			$this->log[] = \sprintf(
				/* translators: %1$d: count, %2$s: taxonomy */
				__( 'Encontrados %1$d termos em "%2$s".', 'apollo-core' ),
				\count( $terms ),
				$legacy_tax
			);

			// Map old term IDs to new term IDs (for parent relationships).
			$term_id_map = array();

			foreach ( $terms as $term ) {
				$result = $this->migrate_term( $term, $legacy_tax, $new_tax, $term_id_map );

				if ( $result['success'] ) {
					$term_id_map[ $term->term_id ] = $result['new_term_id'];
					++$migrated_terms;
					$migrated_relationships += $result['relationships'];
				} else {
					++$errors;
				}
			}
		}

		// Mark as complete if successful.
		if ( $errors === 0 ) {
			\update_option(
				self::MIGRATION_OPTION,
				array(
					'completed_at'   => \current_time( 'mysql' ),
					'terms_migrated' => $migrated_terms,
					'relationships'  => $migrated_relationships,
				)
			);

			// Flush rewrite rules.
			\flush_rewrite_rules();
		}

		return array(
			'success'       => $errors === 0,
			'terms'         => $migrated_terms,
			'relationships' => $migrated_relationships,
			'errors'        => $errors,
			'message'       => $errors === 0
				? \sprintf(
					/* translators: %1$d: terms, %2$d: relationships */
					__( 'Migração concluída! %1$d termos e %2$d relacionamentos migrados.', 'apollo-core' ),
					$migrated_terms,
					$migrated_relationships
				)
				: __( 'Migração concluída com erros. Verifique o log.', 'apollo-core' ),
			'log'           => $this->log,
		);
	}

	/**
	 * Migrate a single term
	 *
	 * @param \WP_Term $term        Term to migrate.
	 * @param string   $legacy_tax  Legacy taxonomy.
	 * @param string   $new_tax     New taxonomy.
	 * @param array    $term_id_map Mapping of old to new term IDs.
	 * @return array
	 */
	private function migrate_term( \WP_Term $term, string $legacy_tax, string $new_tax, array $term_id_map ): array {
		// Check if term already exists in new taxonomy.
		$existing = \term_exists( $term->slug, $new_tax );

		if ( $existing ) {
			$new_term_id = \is_array( $existing ) ? $existing['term_id'] : $existing;
			$this->log[] = \sprintf(
				/* translators: %1$s: term name, %2$s: taxonomy */
				__( 'Termo "%1$s" já existe em "%2$s".', 'apollo-core' ),
				$term->name,
				$new_tax
			);
		} else {
			// Determine parent in new taxonomy.
			$new_parent = 0;
			if ( $term->parent > 0 && isset( $term_id_map[ $term->parent ] ) ) {
				$new_parent = $term_id_map[ $term->parent ];
			}

			// Insert term in new taxonomy.
			$result = \wp_insert_term(
				$term->name,
				$new_tax,
				array(
					'slug'        => $term->slug,
					'description' => $term->description,
					'parent'      => $new_parent,
				)
			);

			if ( \is_wp_error( $result ) ) {
				$this->log[] = \sprintf(
					/* translators: %1$s: term, %2$s: error */
					__( '❌ Erro ao migrar termo "%1$s": %2$s', 'apollo-core' ),
					$term->name,
					$result->get_error_message()
				);
				return array(
					'success'       => false,
					'new_term_id'   => 0,
					'relationships' => 0,
				);
			}

			$new_term_id = $result['term_id'];

			// Copy term meta.
			$term_meta = \get_term_meta( $term->term_id );
			foreach ( $term_meta as $meta_key => $meta_values ) {
				foreach ( $meta_values as $meta_value ) {
					\add_term_meta( $new_term_id, $meta_key, \maybe_unserialize( $meta_value ) );
				}
			}

			$this->log[] = \sprintf(
				/* translators: %1$s: term, %2$s: taxonomy */
				__( '✅ Termo "%1$s" migrado para "%2$s".', 'apollo-core' ),
				$term->name,
				$new_tax
			);
		}

		// Migrate relationships.
		$relationships = $this->migrate_term_relationships(
			$term->term_id,
			(int) $new_term_id,
			$legacy_tax,
			$new_tax
		);

		return array(
			'success'       => true,
			'new_term_id'   => (int) $new_term_id,
			'relationships' => $relationships,
		);
	}

	/**
	 * Migrate term relationships
	 *
	 * @param int    $old_term_id Old term ID.
	 * @param int    $new_term_id New term ID.
	 * @param string $legacy_tax  Legacy taxonomy.
	 * @param string $new_tax     New taxonomy.
	 * @return int Number of relationships migrated.
	 */
	private function migrate_term_relationships( int $old_term_id, int $new_term_id, string $legacy_tax, string $new_tax ): int {
		global $wpdb;

		$count = 0;

		// Get posts with legacy term.
		$posts = \get_posts(
			array(
				'post_type'      => 'supplier',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => $legacy_tax,
						'field'    => 'term_id',
						'terms'    => $old_term_id,
					),
				),
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			// Get new post ID if post was also migrated.
			$new_post_id = $post_id;

			// Check if there's a migrated apollo_supplier post.
			$migrated_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_migrated_from_supplier_id'
				AND meta_value = %d",
					$post_id
				)
			);

			if ( $migrated_id ) {
				$new_post_id = (int) $migrated_id;
			}

			// Check if post exists with new post type.
			if ( \post_type_exists( 'apollo_supplier' ) ) {
				$apollo_post = \get_post( $new_post_id );
				if ( $apollo_post && $apollo_post->post_type === 'apollo_supplier' ) {
					// Assign new term to new post.
					\wp_set_object_terms( $new_post_id, $new_term_id, $new_tax, true );
					++$count;
				}
			}
		}

		if ( $count > 0 ) {
			$this->log[] = \sprintf(
				/* translators: %1$d: count, %2$s: term ID */
				__( '  → %1$d relacionamentos atualizados para termo ID %2$d.', 'apollo-core' ),
				$count,
				$new_term_id
			);
		}

		return $count;
	}

	/**
	 * Check if migration was completed
	 *
	 * @return bool
	 */
	public function is_migrated(): bool {
		return (bool) \get_option( self::MIGRATION_OPTION );
	}
}

// Initialize migration.
Supplier_Taxonomy_Migration::get_instance();
