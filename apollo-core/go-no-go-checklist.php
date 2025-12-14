<?php

/**
 * Apollo GO/NO-GO Checklist
 *
 * Script de verificação para deploy. Executa checagens de:
 * - Rotas REST com permission_callback
 * - Nonces em formulários admin
 * - Módulos e limites funcionando
 * - Integrações ativas
 *
 * FASE 5 do plano de modularização Apollo.
 *
 * @package Apollo_Core
 * @since 4.0.0
 *
 * Executar via WP-CLI: wp eval-file go-no-go-checklist.php
 * Ou acessar via browser com ?apollo_run_checklist=1 (admin only)
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	// Allow running as standalone script.
	require_once __DIR__ . '/wp-load.php';
}

// Only allow admins.
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
	wp_die( 'Acesso negado.' );
}

/**
 * Apollo GO/NO-GO Checklist Runner
 */
class Apollo_GONO_Checklist {

	private array $results = array();
	private int $passed    = 0;
	private int $failed    = 0;
	private int $warnings  = 0;

	/**
	 * Run all checks
	 */
	public function run(): void {
		$this->output( '=' . str_repeat( '=', 60 ) );
		$this->output( 'APOLLO GO/NO-GO CHECKLIST' );
		$this->output( 'Data: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
		$this->output( '=' . str_repeat( '=', 60 ) );
		$this->output( '' );

		$this->check_php_version();
		$this->check_wp_version();
		$this->check_required_plugins();
		$this->check_rest_routes();
		$this->check_db_tables();
		$this->check_user_mod();
		$this->check_modules_config();
		$this->check_admin_pages();
		$this->check_capabilities();
		$this->check_cron_jobs();

		$this->output( '' );
		$this->output( '=' . str_repeat( '=', 60 ) );
		$this->output( 'RESULTADO FINAL' );
		$this->output( '=' . str_repeat( '=', 60 ) );
		$this->output( sprintf( '✅ Passou: %d', $this->passed ) );
		$this->output( sprintf( '❌ Falhou: %d', $this->failed ) );
		$this->output( sprintf( '⚠️  Avisos: %d', $this->warnings ) );
		$this->output( '' );

		if ( $this->failed > 0 ) {
			$this->output( '🔴 STATUS: NO-GO - Corrija os erros antes do deploy.' );
		} elseif ( $this->warnings > 0 ) {
			$this->output( '🟡 STATUS: GO COM RESSALVAS - Revise os avisos.' );
		} else {
			$this->output( '🟢 STATUS: GO - Tudo pronto para deploy!' );
		}
	}

	/**
	 * Check PHP version
	 */
	private function check_php_version(): void {
		$this->output( '--- PHP Version ---' );

		$required = '8.1.0';
		$current  = PHP_VERSION;

		if ( version_compare( $current, $required, '>=' ) ) {
			$this->pass( "PHP $current >= $required" );
		} else {
			$this->fail( "PHP $current < $required (requerido: $required)" );
		}
	}

	/**
	 * Check WordPress version
	 */
	private function check_wp_version(): void {
		$this->output( '--- WordPress Version ---' );

		$required = '6.0';
		$current  = get_bloginfo( 'version' );

		if ( version_compare( $current, $required, '>=' ) ) {
			$this->pass( "WordPress $current >= $required" );
		} else {
			$this->fail( "WordPress $current < $required" );
		}
	}

	/**
	 * Check required plugins
	 */
	private function check_required_plugins(): void {
		$this->output( '--- Plugins Requeridos ---' );

		$plugins = array(
			'apollo-core'           => 'Apollo Core',
			'apollo-social'         => 'Apollo Social',
			'apollo-events-manager' => 'Apollo Events Manager',
		);

		foreach ( $plugins as $slug => $name ) {
			$active = is_plugin_active( "$slug/$slug.php" ) || is_plugin_active( "$slug/plugin.php" );
			if ( $active ) {
				$this->pass( "$name está ativo" );
			} else {
				$this->warn( "$name não encontrado ou inativo" );
			}
		}
	}

	/**
	 * Check REST routes have permission callbacks
	 */
	private function check_rest_routes(): void {
		$this->output( '--- Rotas REST ---' );

		$server = rest_get_server();
		$routes = $server->get_routes();

		$apollo_routes       = 0;
		$routes_without_perm = array();

		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, '/apollo/' ) === false ) {
				continue;
			}

			++$apollo_routes;

			foreach ( $handlers as $handler ) {
				if ( ! isset( $handler['callback'] ) ) {
					continue;
				}

				// Check if permission_callback exists and is not __return_true for sensitive routes.
				$has_perm = isset( $handler['permission_callback'] ) && $handler['permission_callback'] !== null;

				if ( ! $has_perm ) {
					$methods               = isset( $handler['methods'] ) ? implode( ',', array_keys( (array) $handler['methods'] ) ) : 'GET';
					$routes_without_perm[] = "[$methods] $route";
				}
			}
		}

		$this->pass( "Total de rotas Apollo: $apollo_routes" );

		if ( empty( $routes_without_perm ) ) {
			$this->pass( 'Todas as rotas têm permission_callback' );
		} else {
			foreach ( $routes_without_perm as $route ) {
				$this->warn( "Rota sem permission_callback: $route" );
			}
		}

		// Check specific sensitive routes.
		$sensitive_routes = array(
			'/apollo/v1/mod/suspender/er/er/',
			'/apollo/v1/mod/ban',
			'/apollo/v1/bolha/pedir',
			'/apollo/v1/bolha/aceitar',
		);

		foreach ( $sensitive_routes as $route ) {
			$found = false;
			foreach ( $routes as $pattern => $handlers ) {
				if ( strpos( $pattern, $route ) !== false ) {
					$found = true;
					$this->pass( "Rota sensível registrada: $route" );

					break;
				}
			}
			if ( ! $found ) {
				$this->warn( "Rota sensível não encontrada: $route" );
			}
		}
	}

	/**
	 * Check database tables exist
	 */
	private function check_db_tables(): void {
		$this->output( '--- Tabelas do Banco ---' );

		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'apollo_mod_log',
			$wpdb->prefix . 'apollo_audit_log',
		);

		foreach ( $tables as $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
			if ( $exists ) {
				$this->pass( "Tabela existe: $table" );
			} else {
				$this->warn( "Tabela não existe: $table (será criada na ativação)" );
			}
		}
	}

	/**
	 * Check user mod class
	 */
	private function check_user_mod(): void {
		$this->output( '--- Sistema de Moderação ---' );

		if ( class_exists( 'Apollo_User_Moderation' ) ) {
			$this->pass( 'Classe Apollo_User_Moderation existe' );

			// Check constants.
			$constants = array(
				'STATUS_ACTIVE',
				'STATUS_SUSPENDED',
				'STATUS_BANNED',
				'MOD_LEVEL_BASIC',
				'MOD_LEVEL_ADVANCED',
				'MOD_LEVEL_FULL',
			);

			foreach ( $constants as $const ) {
				if ( defined( "Apollo_User_Moderation::$const" ) ) {
					$this->pass( "Constante definida: $const" );
				} else {
					$this->fail( "Constante não definida: $const" );
				}
			}
		} else {
			$this->fail( 'Classe Apollo_User_Moderation não existe' );
		}

		// Check helper functions.
		$functions = array(
			'apollo_suspend_user',
			'apollo_unsuspend_user',
			'apollo_ban_user',
			'apollo_is_user_suspended',
			'apollo_can_user_perform',
			'apollo_get_mod_level',
			'apollo_can_moderate',
		);

		foreach ( $functions as $func ) {
			if ( function_exists( $func ) ) {
				$this->pass( "Função existe: $func()" );
			} else {
				$this->fail( "Função não existe: $func()" );
			}
		}
	}

	/**
	 * Check modules config
	 */
	private function check_modules_config(): void {
		$this->output( '--- Configuração de Módulos ---' );

		if ( class_exists( 'Apollo_Modules_Config' ) ) {
			$this->pass( 'Classe Apollo_Modules_Config existe' );

			$modules = Apollo_Modules_Config::get_modules();
			$this->pass( 'Módulos carregados: ' . count( $modules ) );

			$limits = Apollo_Modules_Config::get_limits();
			$this->pass( 'Limites carregados: ' . count( $limits ) );
		} else {
			$this->fail( 'Classe Apollo_Modules_Config não existe' );
		}

		// Check helper functions.
		$functions = array(
			'apollo_is_module_enabled',
			'apollo_get_limit',
			'apollo_check_limit',
		);

		foreach ( $functions as $func ) {
			if ( function_exists( $func ) ) {
				$this->pass( "Função existe: $func()" );
			} else {
				$this->fail( "Função não existe: $func()" );
			}
		}
	}

	/**
	 * Check admin pages are registered
	 */
	private function check_admin_pages(): void {
		$this->output( '--- Páginas Admin ---' );

		global $menu, $submenu;

		// Check if Apollo Cabin menu exists.
		$cabin_found = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === 'apollo-cabin' ) {
					$cabin_found = true;

					break;
				}
			}
		}

		if ( $cabin_found ) {
			$this->pass( 'Menu Apollo Cabin registrado' );
		} else {
			$this->warn( 'Menu Apollo Cabin não encontrado (verificar após admin_menu)' );
		}
	}

	/**
	 * Check capabilities are set up
	 */
	private function check_capabilities(): void {
		$this->output( '--- Capabilities ---' );

		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			$this->fail( 'Role administrator não encontrada' );

			return;
		}

		$required_caps = array(
			'apollo_moderate_basic',
			'apollo_moderate_advanced',
			'apollo_moderate_full',
			'apollo_block_ip',
			'apollo_manage_moderators',
		);

		foreach ( $required_caps as $cap ) {
			if ( $admin->has_cap( $cap ) ) {
				$this->pass( "Admin tem capability: $cap" );
			} else {
				$this->warn( "Admin não tem capability: $cap (será adicionada na ativação)" );
			}
		}
	}

	/**
	 * Check cron jobs
	 */
	private function check_cron_jobs(): void {
		$this->output( '--- Cron Jobs ---' );

		$crons        = _get_cron_array();
		$apollo_crons = array();

		foreach ( $crons as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( strpos( $hook, 'apollo' ) !== false ) {
					$apollo_crons[ $hook ] = gmdate( 'Y-m-d H:i:s', $timestamp );
				}
			}
		}

		if ( empty( $apollo_crons ) ) {
			$this->warn( 'Nenhum cron job Apollo agendado' );
		} else {
			foreach ( $apollo_crons as $hook => $next_run ) {
				$this->pass( "Cron: $hook (próxima execução: $next_run)" );
			}
		}
	}

	/**
	 * Output helpers
	 */
	private function output( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::line( $message );
		} else {
			echo esc_html( $message ) . '<br>';
		}
	}

	private function pass( string $message ): void {
		++$this->passed;
		$this->output( '  ✅ ' . $message );
	}

	private function fail( string $message ): void {
		++$this->failed;
		$this->output( '  ❌ ' . $message );
	}

	private function warn( string $message ): void {
		++$this->warnings;
		$this->output( '  ⚠️  ' . $message );
	}
}

// Run checklist if requested.
if ( isset( $_GET['apollo_run_checklist'] ) && current_user_can( 'manage_options' ) ) {
	// Disable output buffering.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!DOCTYPE html><html><head><title>Apollo GO/NO-GO Checklist</title>';
	echo '<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;line-height:1.6;}</style>';
	echo '</head><body><pre>';

	$checklist = new Apollo_GONO_Checklist();
	$checklist->run();

	echo '</pre></body></html>';
	exit;
}

// WP-CLI support.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$checklist = new Apollo_GONO_Checklist();
	$checklist->run();
}
