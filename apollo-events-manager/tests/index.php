<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Test Suite Index
 *
 * Página índice para acessar todos os testes
 */

// Prevent directory listing
if ( ! defined( 'ABSPATH' ) ) {
	// Try to load WordPress
	$wp_load_paths = array(
		__DIR__ . '/../../../../wp-load.php',
		__DIR__ . '/../../../wp-load.php',
		__DIR__ . '/../../wp-load.php',
		dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php',
	);

	$wp_loaded = false;
	foreach ( $wp_load_paths as $path ) {
		$real_path = realpath( $path );
		if ( $real_path && file_exists( $real_path ) ) {
			require_once $real_path;
			$wp_loaded = true;
			break;
		}
	}

	if ( ! $wp_loaded ) {
		$current_dir = __DIR__;
		for ( $i = 0; $i < 5; $i++ ) {
			$wp_load = $current_dir . '/wp-load.php';
			if ( file_exists( $wp_load ) ) {
				require_once $wp_load;
				$wp_loaded = true;
				break;
			}
			$current_dir = dirname( $current_dir );
		}
	}
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Apollo Events Manager - Test Suite</title>
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			padding: 40px 20px;
			min-height: 100vh;
		}
		.container {
			max-width: 1000px;
			margin: 0 auto;
			background: white;
			padding: 40px;
			border-radius: 12px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
		}
		h1 {
			color: #1a1a1a;
			margin-bottom: 10px;
			font-size: 32px;
		}
		.subtitle {
			color: #666;
			margin-bottom: 30px;
			font-size: 16px;
		}
		.test-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 30px;
		}
		.test-card {
			background: #f8f9fa;
			border: 2px solid #e0e0e0;
			border-radius: 8px;
			padding: 25px;
			transition: all 0.3s ease;
			text-decoration: none;
			color: inherit;
			display: block;
		}
		.test-card:hover {
			border-color: #0073aa;
			transform: translateY(-5px);
			box-shadow: 0 5px 15px rgba(0,115,170,0.2);
		}
		.test-card h2 {
			color: #0073aa;
			margin-bottom: 10px;
			font-size: 20px;
		}
		.test-card p {
			color: #666;
			margin-bottom: 15px;
			line-height: 1.6;
		}
		.test-card .url {
			font-family: 'Courier New', monospace;
			background: white;
			padding: 8px 12px;
			border-radius: 4px;
			font-size: 12px;
			color: #0073aa;
			word-break: break-all;
		}
		.status-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 20px;
			font-size: 12px;
			font-weight: bold;
			margin-top: 10px;
		}
		.status-badge.available {
			background: #46b450;
			color: white;
		}
		.status-badge.unavailable {
			background: #dc3232;
			color: white;
		}
		.info-box {
			background: #e8f4f8;
			border-left: 4px solid #0073aa;
			padding: 15px;
			margin-top: 30px;
			border-radius: 4px;
		}
		.info-box h3 {
			color: #0073aa;
			margin-bottom: 10px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🧪 Apollo Events Manager - Test Suite</h1>
		<p class="subtitle">Escolha um teste para executar:</p>
		
		<div class="info-box" style="background: #fff3cd; border-left-color: #ffb900;">
			<h3>⚠️ Aviso de Manutenção</h3>
			<p>Alguns testes foram temporariamente desabilitados para permitir o deployment em produção. Apenas o Database Test está disponível no momento.</p>
		</div>
		
		<div class="test-grid">
			<a href="db-test.php" class="test-card">
				<h2>Database Test</h2>
				<p>Teste de conexão e estrutura do banco de dados MySQL. Verifica tabelas, CPTs, meta keys canônicas e legadas.</p>
				<div class="url">db-test.php</div>
				<span class="status-badge available">✅ Disponível</span>
			</a>
		</div>
		
		<div class="info-box">
			<h3>ℹ️ Informações</h3>
			<p><strong>Site URL:</strong> <?php echo defined( 'home_url' ) ? home_url() : 'http://localhost:10004'; ?></p>
			<p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
			<p><strong>WordPress:</strong> <?php echo defined( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'N/A'; ?></p>
			<p><strong>Xdebug:</strong> <?php echo function_exists( 'xdebug_info' ) ? '✅ Ativo' : '❌ Não encontrado'; ?></p>
		</div>
	</div>
</body>
</html>



