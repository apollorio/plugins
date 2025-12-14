<?php

declare(strict_types=1);

/**
 * CENA-RIO Canvas Mode Handler
 *
 * Handles routing and template loading for CENA-RIO pages
 * in Canvas Mode (no theme CSS).
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CENA-RIO Canvas class
 */
class Apollo_Cena_Rio_Canvas {

	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ), 10 );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_filter( 'template_include', array( __CLASS__, 'handle_canvas_template' ), 999 );
		add_action( 'template_redirect', array( __CLASS__, 'check_access' ), 5 );
	}

	/**
	 * Add query variables
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public static function add_query_vars( array $vars ): array {
		$vars[] = 'apollo_cena';

		return $vars;
	}

	/**
	 * Add rewrite rules for CENA-RIO pages
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule( '^cena-rio/?$', 'index.php?apollo_cena=calendar', 'top' );
		add_rewrite_rule( '^cena-rio/mod/?$', 'index.php?apollo_cena=mod', 'top' );
	}

	/**
	 * Check access permissions
	 */
	public static function check_access(): void {
		$page = get_query_var( 'apollo_cena', '' );

		if ( empty( $page ) || ! is_string( $page ) ) {
			return;
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		// Check if Apollo_Cena_Rio_Roles class exists
		if ( ! class_exists( 'Apollo_Cena_Rio_Roles' ) ) {
			wp_die( esc_html__( 'Required class not loaded.', 'apollo-core' ) );
		}

		// Check permissions based on page
		if ( 'calendar' === $page ) {
			if ( ! Apollo_Cena_Rio_Roles::user_can_submit() ) {
				wp_die(
					esc_html__( 'Você não tem permissão para acessar esta página.', 'apollo-core' ),
					esc_html__( 'Acesso Negado', 'apollo-core' ),
					array( 'response' => 403 )
				);
			}
		}

		if ( 'mod' === $page ) {
			if ( ! Apollo_Cena_Rio_Roles::user_can_moderate() ) {
				wp_die(
					esc_html__( 'Você não tem permissão para acessar a moderação.', 'apollo-core' ),
					esc_html__( 'Acesso Negado', 'apollo-core' ),
					array( 'response' => 403 )
				);
			}
		}
	}

	/**
	 * Handle canvas template loading
	 *
	 * @param string $template Current template.
	 * @return string Template path.
	 */
	public static function handle_canvas_template( string $template ): string {
		$page = get_query_var( 'apollo_cena', '' );

		if ( empty( $page ) || ! is_string( $page ) ) {
			return $template;
		}

		// Define template paths
		$template_map = array(
			'calendar' => APOLLO_CORE_PLUGIN_DIR . 'templates/cena-rio-calendar.php',
			'mod'      => APOLLO_CORE_PLUGIN_DIR . 'templates/cena-rio-mod.php',
		);

		// Return template if exists
		if ( isset( $template_map[ $page ] ) && file_exists( $template_map[ $page ] ) ) {
			return $template_map[ $page ];
		}

		return $template;
	}

	/**
	 * Flush rewrite rules (call on plugin activation)
	 */
	public static function flush_rewrite_rules(): void {
		self::add_rewrite_rules();
		flush_rewrite_rules();
	}
}

// Initialize
Apollo_Cena_Rio_Canvas::init();
