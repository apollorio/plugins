<?php

/**
 * Loader: só ativa apollo-social se o plugin base (WordPress) estiver ativo.
 *
 * Verifica se WordPress está carregado antes de inicializar o plugin.
 *
 * @package Apollo_Social
 * @version 2.0.0 - Design Library Conformance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}

// Verificar se WordPress está carregado.
if ( ! function_exists( 'add_action' ) ) {
	if ( function_exists( 'wp_die' ) ) {
		wp_die( 'WordPress precisa estar ativo para usar Apollo Social.' );
	} else {
		die( 'WordPress não está carregado. Apollo Social requer WordPress.' );
	}
}

// Carregar módulos do apollo-social com validação defensiva.
$loader_dir = __DIR__;
$main_file  = $loader_dir . '/apollo-social.php';

if ( file_exists( $main_file ) ) {
	require_once $main_file;
} else {
	// Log error only when debugging is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging.
		error_log( 'Apollo Social: Arquivo principal não encontrado: ' . $main_file );
	}

	// Tentar carregar outros arquivos PHP como fallback (compatibilidade).
	$php_files = glob( $loader_dir . '/*.php' );
	if ( ! empty( $php_files ) ) {
		foreach ( $php_files as $file ) {
			if ( basename( $file ) !== 'apollo-social-loader.php' && file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}
