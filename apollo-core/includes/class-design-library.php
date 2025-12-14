<?php

/**
 * Apollo Design Library
 *
 * Helper class para carregar templates HTML de referência.
 * NÃO é um sistema automático de templates - serve apenas como
 * referência para o assistente AI criar código PHP manualmente.
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Design_Library
 *
 * Gerencia a biblioteca de templates de design.
 */
class Apollo_Design_Library {

	/**
	 * Caminho base da biblioteca
	 *
	 * @var string
	 */
	private static string $library_path = '';

	/**
	 * Cache do índice de templates
	 *
	 * @var array|null
	 */
	private static ?array $index_cache = null;

	/**
	 * Inicializa a biblioteca
	 */
	public static function init(): void {
		$plugin_dir         = defined( 'APOLLO_CORE_PLUGIN_DIR' ) ? APOLLO_CORE_PLUGIN_DIR : plugin_dir_path( __FILE__ ) . '../';
		self::$library_path = $plugin_dir . 'templates/design-library/';
	}

	/**
	 * Retorna o caminho da biblioteca
	 *
	 * @return string
	 */
	public static function get_library_path(): string {
		if ( empty( self::$library_path ) ) {
			self::init();
		}

		return self::$library_path;
	}

	/**
	 * Carrega o índice de templates
	 *
	 * @return array
	 */
	public static function get_index(): array {
		if ( null !== self::$index_cache ) {
			return self::$index_cache;
		}

		$index_file = self::get_library_path() . '_index.json';

		if ( ! file_exists( $index_file ) ) {
			return array();
		}

		$content = file_get_contents( $index_file );
		if ( false === $content ) {
			return array();
		}

		$data = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array();
		}

		self::$index_cache = $data;

		return $data;
	}

	/**
	 * Lista todos os templates disponíveis
	 *
	 * @return array Array de templates com metadados.
	 */
	public static function list_templates(): array {
		$index = self::get_index();

		return $index['templates'] ?? array();
	}

	/**
	 * Lista todos os componentes disponíveis
	 *
	 * @return array Array de componentes.
	 */
	public static function list_components(): array {
		$index = self::get_index();

		return $index['components'] ?? array();
	}

	/**
	 * Retorna os design tokens
	 *
	 * @return array Array de tokens de design.
	 */
	public static function get_design_tokens(): array {
		$index = self::get_index();

		return $index['design_tokens'] ?? array();
	}

	/**
	 * Retorna os assets externos
	 *
	 * @return array Array de URLs de assets.
	 */
	public static function get_external_assets(): array {
		$index = self::get_index();

		return $index['external_assets'] ?? array();
	}

	/**
	 * Carrega um template HTML como string
	 *
	 * @param string $template_name Nome do template (ex: 'feed-social').
	 * @return string|false Conteúdo HTML ou false se não encontrado.
	 */
	public static function get_template( string $template_name ) {
		$templates = self::list_templates();

		if ( ! isset( $templates[ $template_name ] ) ) {
			return false;
		}

		$filename = $templates[ $template_name ]['file'] ?? "{$template_name}.html";
		$filepath = self::get_library_path() . $filename;

		if ( ! file_exists( $filepath ) ) {
			return false;
		}

		return file_get_contents( $filepath );
	}

	/**
	 * Retorna metadados de um template
	 *
	 * @param string $template_name Nome do template.
	 * @return array|false Metadados ou false se não encontrado.
	 */
	public static function get_template_meta( string $template_name ) {
		$templates = self::list_templates();

		return $templates[ $template_name ] ?? false;
	}

	/**
	 * Extrai placeholders de um template HTML
	 *
	 * @param string $html Conteúdo HTML.
	 * @return array Lista de placeholders encontrados.
	 */
	public static function extract_placeholders( string $html ): array {
		$placeholders = array();

		// Match {{variable}} patterns
		preg_match_all( '/\{\{([^}]+)\}\}/', $html, $matches );

		if ( ! empty( $matches[1] ) ) {
			$placeholders = array_unique( $matches[1] );
		}

		return $placeholders;
	}

	/**
	 * Extrai seções de um template HTML
	 *
	 * @param string $html Conteúdo HTML.
	 * @return array Lista de seções encontradas.
	 */
	public static function extract_sections( string $html ): array {
		$sections = array();

		// Match <!-- @section:name --> patterns
		preg_match_all( '/<!--\s*@section:([a-z0-9_-]+)\s*-->/', $html, $matches );

		if ( ! empty( $matches[1] ) ) {
			$sections = array_unique( $matches[1] );
		}

		return $sections;
	}

	/**
	 * Extrai componentes referenciados em um template HTML
	 *
	 * @param string $html Conteúdo HTML.
	 * @return array Lista de componentes referenciados.
	 */
	public static function extract_components( string $html ): array {
		$components = array();

		// Match <!-- @component:name --> patterns
		preg_match_all( '/<!--\s*@component:([a-z0-9_-]+)\s*-->/', $html, $matches );

		if ( ! empty( $matches[1] ) ) {
			$components = array_unique( $matches[1] );
		}

		return $components;
	}

	/**
	 * Enfileira os assets CSS externos
	 *
	 * @param string $handle Handle para o enqueue.
	 */
	public static function enqueue_css( string $handle = 'apollo-design' ): void {
		$assets = self::get_external_assets();
		$css    = $assets['css'] ?? array();

		foreach ( $css as $index => $url ) {
			wp_enqueue_style( "{$handle}-{$index}", $url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}
	}

	/**
	 * Enfileira os assets JS externos
	 *
	 * @param string $handle Handle para o enqueue.
	 */
	public static function enqueue_js( string $handle = 'apollo-design' ): void {
		$assets = self::get_external_assets();
		$js     = $assets['js'] ?? array();

		foreach ( $js as $index => $url ) {
			wp_enqueue_script( "{$handle}-{$index}", $url, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}
	}

	/**
	 * Gera CSS inline com os design tokens
	 *
	 * @return string CSS inline com variáveis.
	 */
	public static function get_css_tokens(): string {
		$tokens = self::get_design_tokens();
		$colors = $tokens['colors'] ?? array();
		$radius = $tokens['radius'] ?? array();

		$css = ':root {' . PHP_EOL;

		foreach ( $colors as $name => $value ) {
			$css .= "  --apollo-{$name}: {$value};" . PHP_EOL;
		}

		foreach ( $radius as $name => $value ) {
			$css .= "  --apollo-radius-{$name}: {$value};" . PHP_EOL;
		}

		$css .= '}' . PHP_EOL;

		return $css;
	}

	/**
	 * Verifica se um template existe
	 *
	 * @param string $template_name Nome do template.
	 * @return bool
	 */
	public static function template_exists( string $template_name ): bool {
		$templates = self::list_templates();

		return isset( $templates[ $template_name ] );
	}

	/**
	 * Retorna as notas/dicas da biblioteca
	 *
	 * @return array Lista de notas.
	 */
	public static function get_notes(): array {
		$index = self::get_index();

		return $index['notes'] ?? array();
	}
}

// Inicializa a biblioteca
add_action( 'plugins_loaded', array( 'Apollo_Design_Library', 'init' ), 5 );
