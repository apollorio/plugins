<?php
// phpcs:ignoreFile
/**
 * Simple Test - Verifica apenas se WordPress e Plugin carregam
 */

// Enable error reporting
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<title>Simple Test - Apollo Events Manager</title>
	<style>
		body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
		.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
		.success { color: #46b450; }
		.error { color: #dc3232; }
		code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Simple Test - Apollo Events Manager</h1>
		
		<h2>1. Carregando WordPress...</h2>
		<?php
		$wp_loaded     = false;
		$wp_load_paths = array(
			__DIR__ . '/../../../../wp-load.php',
			__DIR__ . '/../../../wp-load.php',
		);

		foreach ( $wp_load_paths as $path ) {
			if ( file_exists( $path ) ) {
				try {
					require_once $path;
					if ( defined( 'ABSPATH' ) ) {
						$wp_loaded = true;
						echo '<p class="success">✅ WordPress carregado de: <code>' . htmlspecialchars( $path ) . '</code></p>';
						break;
					}
				} catch ( Exception $e ) {
					echo '<p class="error">❌ Erro ao carregar: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
				} catch ( Error $e ) {
					echo '<p class="error">❌ Erro fatal: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
					echo '<p>Arquivo: ' . htmlspecialchars( $e->getFile() ) . '</p>';
					echo '<p>Linha: ' . $e->getLine() . '</p>';
				}
			}
		}

		if ( ! $wp_loaded ) {
			echo '<p class="error">❌ WordPress não carregado</p>';
			die( '</div></body></html>' );
		}
		?>
		
		<h2>2. Carregando Plugin...</h2>
		<?php
		$plugin_file = dirname( __DIR__ ) . '/apollo-events-manager.php';
		echo '<p>Arquivo do plugin: <code>' . htmlspecialchars( $plugin_file ) . '</code></p>';
		echo '<p>Existe: ' . ( file_exists( $plugin_file ) ? '✅ Sim' : '❌ Não' ) . '</p>';

		if ( file_exists( $plugin_file ) ) {
			try {
				require_once $plugin_file;
				echo '<p class="success">✅ Arquivo do plugin carregado</p>';
			} catch ( Exception $e ) {
				echo '<p class="error">❌ Erro ao carregar plugin: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
				die( '</div></body></html>' );
			} catch ( Error $e ) {
				echo '<p class="error">❌ Erro fatal ao carregar plugin: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
				echo '<p>Arquivo: ' . htmlspecialchars( $e->getFile() ) . '</p>';
				echo '<p>Linha: ' . $e->getLine() . '</p>';
				die( '</div></body></html>' );
			}
		} else {
			echo '<p class="error">❌ Arquivo do plugin não encontrado</p>';
			die( '</div></body></html>' );
		}
		?>
		
		<h2>3. Verificando Classe do Plugin...</h2>
		<?php
		if ( class_exists( 'Apollo_Events_Manager_Plugin' ) ) {
			echo '<p class="success">✅ Classe Apollo_Events_Manager_Plugin existe</p>';
		} else {
			echo '<p class="error">❌ Classe Apollo_Events_Manager_Plugin NÃO existe</p>';
			die( '</div></body></html>' );
		}
		?>
		
		<h2>4. Instanciando Plugin...</h2>
		<?php
		global $apollo_events_manager;
		try {
			if ( ! isset( $apollo_events_manager ) || ! ( $apollo_events_manager instanceof Apollo_Events_Manager_Plugin ) ) {
				$apollo_events_manager = new Apollo_Events_Manager_Plugin();
			}
			echo '<p class="success">✅ Plugin instanciado</p>';
		} catch ( Exception $e ) {
			echo '<p class="error">❌ Erro ao instanciar: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
			die( '</div></body></html>' );
		} catch ( Error $e ) {
			echo '<p class="error">❌ Erro fatal ao instanciar: ' . htmlspecialchars( $e->getMessage() ) . '</p>';
			echo '<p>Arquivo: ' . htmlspecialchars( $e->getFile() ) . '</p>';
			echo '<p>Linha: ' . $e->getLine() . '</p>';
			die( '</div></body></html>' );
		}
		?>
		
		<h2>5. Verificando Hooks...</h2>
		<?php
		// Check hook status (don't manually trigger - WordPress handles this)
		echo '<p>plugins_loaded: ' . ( did_action( 'plugins_loaded' ) ? '✅ Sim' : '❌ Não' ) . '</p>';
		echo '<p>init: ' . ( did_action( 'init' ) ? '✅ Sim' : '❌ Não' ) . '</p>';

		// Check WordPress core objects
		global $wp_rewrite, $wp;
		echo '<p>wp_rewrite: ' . ( isset( $wp_rewrite ) ? '✅ Existe' : '❌ Não existe' ) . '</p>';
		echo '<p>wp: ' . ( isset( $wp ) ? '✅ Existe' : '❌ Não existe' ) . '</p>';

		// Note: We don't manually trigger hooks to avoid errors
		// WordPress should have already executed them via wp-load.php
		?>
		
		<h2>6. Verificando CPTs...</h2>
		<?php
		$cpts = array( 'event_listing', 'event_dj', 'event_local' );
		foreach ( $cpts as $cpt ) {
			if ( post_type_exists( $cpt ) ) {
				echo '<p class="success">✅ CPT "' . $cpt . '" registrado</p>';
			} else {
				echo '<p class="error">❌ CPT "' . $cpt . '" NÃO registrado</p>';
			}
		}
		?>
		
		<h2>7. Verificando Shortcodes...</h2>
		<?php
		$shortcodes = array( 'apollo_eventos', 'apollo_dj_profile', 'submit_event_form', 'apollo_register', 'apollo_login', 'my_apollo_dashboard' );
		foreach ( $shortcodes as $shortcode ) {
			if ( shortcode_exists( $shortcode ) ) {
				echo '<p class="success">✅ Shortcode "' . $shortcode . '" registrado</p>';
			} else {
				echo '<p class="error">❌ Shortcode "' . $shortcode . '" NÃO registrado</p>';
			}
		}
		?>
		
		<h2>✅ Teste Completo!</h2>
		<p>Se você chegou até aqui, o plugin está carregando corretamente.</p>
	</div>
</body>
</html>

