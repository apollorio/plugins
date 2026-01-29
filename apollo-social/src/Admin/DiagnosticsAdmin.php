<?php
/**
 * Apollo Diagnostics Admin Page
 *
 * Admin page showing system diagnostics: schema status, feature flags, routes.
 *
 * @package Apollo\Admin
 * @since   2.3.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

use Apollo\Schema;
use Apollo\Infrastructure\FeatureFlags;
use Apollo\Infrastructure\Http\Apollo_Router;

/**
 * Diagnostics Admin Page
 */
class DiagnosticsAdmin {

	/** @var string Admin page slug */
	private const PAGE_SLUG = 'apollo-diagnostics';

	/**
	 * Initialize admin page.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'addMenuPage' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public static function addMenuPage(): void {
		add_submenu_page(
			'apollo-social',
			__( 'Diagnósticos', 'apollo-social' ),
			__( 'Diagnósticos', 'apollo-social' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'renderPage' )
		);
	}

	/**
	 * Render admin page.
	 */
	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'apollo-social' ) );
		}

		// Handle form submissions.
		self::handleActions();

		$schema_status = self::getSchemaStatus();
		$feature_flags = self::getFeatureFlags();
		$router_status = self::getRouterStatus();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Social - Diagnósticos', 'apollo-social' ); ?></h1>

			<div id="apollo-diagnostics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">

				<!-- Schema Status -->
				<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2 style="margin-top: 0;">📦 Schema Status</h2>
					<table class="widefat" style="margin-top: 15px;">
						<tbody>
							<tr>
								<th>Versão Armazenada</th>
								<td><code><?php echo esc_html( $schema_status['version_stored'] ); ?></code></td>
							</tr>
							<tr>
								<th>Versão Atual</th>
								<td><code><?php echo esc_html( $schema_status['version_current'] ); ?></code></td>
							</tr>
							<tr>
								<th>Precisa Upgrade</th>
								<td>
									<?php if ( $schema_status['needs_upgrade'] ) : ?>
										<span style="color: #dc3545;">⚠️ SIM</span>
									<?php else : ?>
										<span style="color: #28a745;">✓ Não</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th>Tabelas</th>
								<td>
									<?php
									$total   = $schema_status['total_tables'];
									$missing = $schema_status['missing_tables'];
									$present = $total - $missing;
									?>
									<?php if ( $missing > 0 ) : ?>
										<span style="color: #dc3545;"><?php echo esc_html( $present ); ?>/<?php echo esc_html( $total ); ?></span>
									<?php else : ?>
										<span style="color: #28a745;"><?php echo esc_html( $present ); ?>/<?php echo esc_html( $total ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>

					<?php if ( $schema_status['needs_upgrade'] ) : ?>
						<form method="post" style="margin-top: 15px;">
							<?php wp_nonce_field( 'apollo_schema_upgrade', 'apollo_nonce' ); ?>
							<input type="hidden" name="apollo_action" value="upgrade_schema">
							<button type="submit" class="button button-primary">
								Executar Upgrade
							</button>
						</form>
					<?php endif; ?>
				</div>

				<!-- Feature Flags -->
				<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2 style="margin-top: 0;">🚩 Feature Flags</h2>

					<p>
						<strong>Inicializado:</strong>
						<?php if ( $feature_flags['initialized'] ) : ?>
							<span style="color: #28a745;">✓ Sim</span>
						<?php else : ?>
							<span style="color: #dc3545;">✗ NÃO (fail-closed ativo!)</span>
						<?php endif; ?>
					</p>

					<table class="widefat" style="margin-top: 15px;">
						<thead>
							<tr>
								<th>Feature</th>
								<th>Status</th>
								<th>Padrão</th>
								<th>Ação</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $feature_flags['features'] as $name => $data ) : ?>
								<tr>
									<td><code><?php echo esc_html( $name ); ?></code></td>
									<td>
										<?php if ( $data['enabled'] ) : ?>
											<span style="color: #28a745;">✅ Ativo</span>
										<?php else : ?>
											<span style="color: #6c757d;">⛔ Desativado</span>
										<?php endif; ?>
									</td>
									<td>
										<?php echo $data['default'] ? 'ON' : 'OFF'; ?>
									</td>
									<td>
										<form method="post" style="display: inline;">
											<?php wp_nonce_field( 'apollo_toggle_flag', 'apollo_nonce' ); ?>
											<input type="hidden" name="apollo_action" value="toggle_flag">
											<input type="hidden" name="feature" value="<?php echo esc_attr( $name ); ?>">
											<input type="hidden" name="enable" value="<?php echo $data['enabled'] ? '0' : '1'; ?>">
											<button type="submit" class="button button-small">
												<?php echo $data['enabled'] ? 'Desativar' : 'Ativar'; ?>
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p style="margin-top: 15px; color: #666; font-size: 12px;">
						<?php echo esc_html( sprintf( 'Resumo: %d ativos, %d desativados', $feature_flags['enabled_count'], $feature_flags['disabled_count'] ) ); ?>
					</p>
				</div>

				<!-- Router Status -->
				<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2 style="margin-top: 0;">🛣️ Router Status</h2>

					<table class="widefat" style="margin-top: 15px;">
						<tbody>
							<tr>
								<th>Versão das Regras</th>
								<td><code><?php echo esc_html( $router_status['version'] ); ?></code></td>
							</tr>
							<tr>
								<th>Prefixo</th>
								<td><code><?php echo esc_html( $router_status['prefix'] ); ?></code></td>
							</tr>
							<tr>
								<th>Total de Rotas</th>
								<td><?php echo esc_html( $router_status['total_routes'] ); ?></td>
							</tr>
						</tbody>
					</table>

					<?php if ( ! empty( $router_status['routes_by_module'] ) ) : ?>
						<h4 style="margin-top: 15px;">Rotas por Módulo:</h4>
						<ul style="margin-left: 20px;">
							<?php foreach ( $router_status['routes_by_module'] as $module => $routes ) : ?>
								<li><strong><?php echo esc_html( $module ); ?>:</strong> <?php echo count( $routes ); ?> rotas</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( ! empty( $router_status['blocked_routes'] ) ) : ?>
						<h4 style="margin-top: 15px; color: #dc3545;">⚠️ Rotas Bloqueadas:</h4>
						<ul style="margin-left: 20px; color: #dc3545;">
							<?php foreach ( $router_status['blocked_routes'] as $route ) : ?>
								<li><?php echo esc_html( $route ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<!-- Quick Actions -->
				<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2 style="margin-top: 0;">⚡ Ações Rápidas</h2>

					<form method="post" style="margin-bottom: 10px;">
						<?php wp_nonce_field( 'apollo_flush_rules', 'apollo_nonce' ); ?>
						<input type="hidden" name="apollo_action" value="flush_rules">
						<button type="submit" class="button">
							🔄 Flush Rewrite Rules
						</button>
						<span class="description">Recarrega as regras de URL.</span>
					</form>

					<form method="post" style="margin-bottom: 10px;">
						<?php wp_nonce_field( 'apollo_reset_flags', 'apollo_nonce' ); ?>
						<input type="hidden" name="apollo_action" value="reset_flags">
						<button type="submit" class="button">
							🔙 Reset Feature Flags
						</button>
						<span class="description">Volta aos valores padrão.</span>
					</form>

					<h4>WP-CLI Commands:</h4>
					<pre style="background: #23282d; color: #eee; padding: 10px; border-radius: 4px; overflow-x: auto;">
wp apollo diag status   # Diagnóstico completo
wp apollo diag flags    # Feature flags
wp apollo diag routes   # Rotas registradas
wp apollo schema status # Status do schema
					</pre>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Handle form actions.
	 */
	private static function handleActions(): void {
		if ( empty( $_POST['apollo_action'] ) || empty( $_POST['apollo_nonce'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['apollo_action'] ) );
		$nonce  = sanitize_text_field( wp_unslash( $_POST['apollo_nonce'] ) );

		switch ( $action ) {
			case 'upgrade_schema':
				if ( ! wp_verify_nonce( $nonce, 'apollo_schema_upgrade' ) ) {
					wp_die( 'Nonce inválido.' );
				}
				$schema = new Schema();
				$result = $schema->upgrade();
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'apollo', 'schema_error', $result->get_error_message(), 'error' );
				} else {
					add_settings_error( 'apollo', 'schema_success', 'Schema atualizado com sucesso!', 'success' );
				}
				break;

			case 'toggle_flag':
				if ( ! wp_verify_nonce( $nonce, 'apollo_toggle_flag' ) ) {
					wp_die( 'Nonce inválido.' );
				}
				$feature = sanitize_text_field( wp_unslash( $_POST['feature'] ?? '' ) );
				$enable  = (bool) ( $_POST['enable'] ?? false );
				if ( $enable ) {
					FeatureFlags::enable( $feature );
				} else {
					FeatureFlags::disable( $feature );
				}
				add_settings_error( 'apollo', 'flag_toggled', sprintf( 'Feature "%s" %s.', $feature, $enable ? 'ativada' : 'desativada' ), 'success' );
				break;

			case 'flush_rules':
				if ( ! wp_verify_nonce( $nonce, 'apollo_flush_rules' ) ) {
					wp_die( 'Nonce inválido.' );
				}
				flush_rewrite_rules();
				add_settings_error( 'apollo', 'rules_flushed', 'Rewrite rules atualizadas!', 'success' );
				break;

			case 'reset_flags':
				if ( ! wp_verify_nonce( $nonce, 'apollo_reset_flags' ) ) {
					wp_die( 'Nonce inválido.' );
				}
				FeatureFlags::resetToDefaults();
				add_settings_error( 'apollo', 'flags_reset', 'Feature flags resetadas para padrão.', 'success' );
				break;
		}

		settings_errors( 'apollo' );
	}

	/**
	 * Get schema status.
	 *
	 * @return array
	 */
	private static function getSchemaStatus(): array {
		if ( ! class_exists( Schema::class ) ) {
			return array(
				'version_stored'  => 'N/A',
				'version_current' => 'N/A',
				'needs_upgrade'   => false,
				'total_tables'    => 0,
				'missing_tables'  => 0,
			);
		}

		$schema = new Schema();
		$status = $schema->getStatus();

		$total   = 0;
		$missing = 0;

		foreach ( $status['modules'] as $tables ) {
			foreach ( $tables as $exists ) {
				++$total;
				if ( ! $exists ) {
					++$missing;
				}
			}
		}

		return array(
			'version_stored'  => $status['version_stored'],
			'version_current' => $status['version_current'],
			'needs_upgrade'   => $status['needs_upgrade'],
			'total_tables'    => $total,
			'missing_tables'  => $missing,
		);
	}

	/**
	 * Get feature flags status.
	 *
	 * @return array
	 */
	private static function getFeatureFlags(): array {
		if ( ! class_exists( FeatureFlags::class ) ) {
			return array(
				'initialized'    => false,
				'features'       => array(),
				'enabled_count'  => 0,
				'disabled_count' => 0,
			);
		}

		$features = FeatureFlags::getAllFeatures();
		$enabled  = 0;
		$disabled = 0;

		foreach ( $features as $data ) {
			if ( $data['enabled'] ) {
				++$enabled;
			} else {
				++$disabled;
			}
		}

		return array(
			'initialized'    => FeatureFlags::isInitialized(),
			'features'       => $features,
			'enabled_count'  => $enabled,
			'disabled_count' => $disabled,
		);
	}

	/**
	 * Get router status.
	 *
	 * @return array
	 */
	private static function getRouterStatus(): array {
		if ( ! class_exists( Apollo_Router::class ) ) {
			return array(
				'version'          => 'N/A',
				'prefix'           => 'N/A',
				'total_routes'     => 0,
				'routes_by_module' => array(),
				'blocked_routes'   => array(),
			);
		}

		return Apollo_Router::getInventory();
	}
}
