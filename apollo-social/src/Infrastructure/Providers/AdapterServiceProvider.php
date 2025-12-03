<?php

namespace Apollo\Infrastructure\Providers;

use Exception;
use Apollo\Infrastructure\Adapters\GroupsAdapter;
use Apollo\Infrastructure\Adapters\EventManagerAdapter;
use Apollo\Infrastructure\Adapters\WPAdvertsAdapter;
use Apollo\Infrastructure\Adapters\BadgeOSAdapter;

/**
 * Service Provider for External Plugin Adapters
 * Registers and initializes all external plugin integrations
 */
class AdapterServiceProvider {


	private $adapters = array();

	public function __construct() {
		$this->register_adapters();
		$this->init_adapters();
	}

	/**
	 * Register all available adapters
	 */
	private function register_adapters() {
		$this->adapters = array(
			'groups'        => GroupsAdapter::class,
			'event_manager' => EventManagerAdapter::class,
			'wpadverts'     => WPAdvertsAdapter::class,
			'badgeos'       => BadgeOSAdapter::class,
		);
	}

	/**
	 * Initialize enabled adapters
	 */
	private function init_adapters() {
		foreach ( $this->adapters as $adapter_key => $adapter_class ) {
			if ( $this->is_adapter_enabled( $adapter_key ) ) {
				try {
					$instance                       = new $adapter_class();
					$this->adapters[ $adapter_key ] = $instance;

					// Log successful initialization
					do_action( 'apollo_adapter_initialized', $adapter_key, $adapter_class );
				} catch ( Exception $e ) {
					// Log initialization error
					do_action( 'apollo_adapter_initialization_failed', $adapter_key, $adapter_class, $e->getMessage() );

					// Keep class name for potential retry
					$this->adapters[ $adapter_key ] = $adapter_class;
				}
			}
		}
	}

	/**
	 * Check if adapter is enabled in configuration
	 */
	private function is_adapter_enabled( $adapter_key ): bool {
		$integrations_config = config( 'integrations' );

		$config_key = match ( $adapter_key ) {
			'groups' => 'itthinx_groups',
			'event_manager' => 'wp_event_manager',
			'wpadverts' => 'wpadverts',
			'badgeos' => 'badgeos',
			default => $adapter_key
		};

		return ( $integrations_config[ $config_key ]['enabled'] ?? false ) === true;
	}

	/**
	 * Get specific adapter instance
	 */
	public function get_adapter( $adapter_key ) {
		if ( ! isset( $this->adapters[ $adapter_key ] ) ) {
			return null;
		}

		$adapter = $this->adapters[ $adapter_key ];

		// If it's still a class name, try to initialize it
		if ( is_string( $adapter ) ) {
			try {
				$instance                       = new $adapter();
				$this->adapters[ $adapter_key ] = $instance;
				return $instance;
			} catch ( Exception $e ) {
				do_action( 'apollo_adapter_late_initialization_failed', $adapter_key, $adapter, $e->getMessage() );
				return null;
			}
		}

		return $adapter;
	}

	/**
	 * Get all initialized adapters
	 */
	public function get_all_adapters(): array {
		$initialized = array();

		foreach ( $this->adapters as $key => $adapter ) {
			if ( is_object( $adapter ) ) {
				$initialized[ $key ] = $adapter;
			}
		}

		return $initialized;
	}

	/**
	 * Check if specific external plugin is available and adapter is working
	 */
	public function is_plugin_available( $adapter_key ): bool {
		$adapter = $this->get_adapter( $adapter_key );

		if ( ! $adapter || ! is_object( $adapter ) ) {
			return false;
		}

		return method_exists( $adapter, 'is_available' ) ? $adapter->is_available() : true;
	}

	/**
	 * Get adapter availability status for all adapters
	 */
	public function get_adapter_status(): array {
		$status = array();

		foreach ( $this->adapters as $key => $adapter ) {
			$status[ $key ] = array(
				'enabled'     => $this->is_adapter_enabled( $key ),
				'initialized' => is_object( $adapter ),
				'available'   => $this->is_plugin_available( $key ),
				'class'       => is_object( $adapter ) ? get_class( $adapter ) : $adapter,
			);
		}

		return $status;
	}

	/**
	 * Reinitialize specific adapter
	 */
	public function reinitialize_adapter( $adapter_key ): bool {
		if ( ! isset( $this->adapters[ $adapter_key ] ) ) {
			return false;
		}

		$adapter_class = is_object( $this->adapters[ $adapter_key ] )
			? get_class( $this->adapters[ $adapter_key ] )
			: $this->adapters[ $adapter_key ];

		try {
			$instance                       = new $adapter_class();
			$this->adapters[ $adapter_key ] = $instance;

			do_action( 'apollo_adapter_reinitialized', $adapter_key, $adapter_class );

			return true;
		} catch ( Exception $e ) {
			do_action( 'apollo_adapter_reinitialization_failed', $adapter_key, $adapter_class, $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get Groups adapter (itthinx Groups integration)
	 */
	public function groups(): ?GroupsAdapter {
		return $this->get_adapter( 'groups' );
	}

	/**
	 * Get Event Manager adapter (WP Event Manager integration)
	 */
	public function event_manager(): ?EventManagerAdapter {
		return $this->get_adapter( 'event_manager' );
	}

	/**
	 * Get WPAdverts adapter (WPAdverts integration)
	 */
	public function wpadverts(): ?WPAdvertsAdapter {
		return $this->get_adapter( 'wpadverts' );
	}

	/**
	 * Get BadgeOS adapter (BadgeOS integration)
	 */
	public function badgeos(): ?BadgeOSAdapter {
		return $this->get_adapter( 'badgeos' );
	}



	/**
	 * Register hooks for adapter management
	 */
	public function register_hooks() {
		// Admin hooks for adapter management
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 25 );
		add_action( 'admin_post_apollo_toggle_adapter', array( $this, 'admin_toggle_adapter' ) );
		add_action( 'admin_post_apollo_test_adapter', array( $this, 'admin_test_adapter' ) );

		// AJAX hooks for adapter status checks
		add_action( 'wp_ajax_apollo_check_adapter_status', array( $this, 'ajax_check_adapter_status' ) );

		// Hook for displaying adapter status in admin
		add_action( 'apollo_admin_dashboard_widgets', array( $this, 'add_adapter_status_widget' ) );
	}

	/**
	 * Add admin menu for adapter management
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'apollo-social',
			'Integrações Apollo',
			'Integrações',
			'manage_options',
			'apollo-integrations',
			array( $this, 'admin_integrations_page' )
		);
	}

	/**
	 * Admin integrations page
	 */
	public function admin_integrations_page() {
		$adapter_status = $this->get_adapter_status();

		echo '<div class="wrap">';
		echo '<h1>Integrações Apollo</h1>';
		echo '<p>Gerencie as integrações com plugins externos.</p>';

		echo '<div class="apollo-adapters-grid">';

		foreach ( $adapter_status as $key => $status ) {
			$this->render_adapter_card( $key, $status );
		}

		echo '</div>';

		// Configuration forms
		$this->render_configuration_forms();

		echo '</div>';

		// Add CSS and JS
		$this->enqueue_admin_assets();
	}

	/**
	 * Render adapter status card
	 */
	private function render_adapter_card( $adapter_key, $status ) {
		$adapter_names = array(
			'groups'        => 'itthinx Groups',
			'event_manager' => 'WP Event Manager',
			'wpadverts'     => 'WPAdverts',
			'badgeos'       => 'BadgeOS',
		);

		$adapter_name = $adapter_names[ $adapter_key ] ?? ucfirst( $adapter_key );
		$status_class = $status['available'] ? 'available' : ( $status['enabled'] ? 'enabled-unavailable' : 'disabled' );

		echo '<div class="apollo-adapter-card ' . esc_attr( $status_class ) . '">';
		echo '<h3>' . esc_html( $adapter_name ) . '</h3>';

		// Status indicators
		echo '<div class="adapter-status">';
		echo '<span class="status-indicator enabled-' . ( $status['enabled'] ? 'yes' : 'no' ) . '">Habilitado: ' . ( $status['enabled'] ? 'Sim' : 'Não' ) . '</span>';
		echo '<span class="status-indicator initialized-' . ( $status['initialized'] ? 'yes' : 'no' ) . '">Inicializado: ' . ( $status['initialized'] ? 'Sim' : 'Não' ) . '</span>';
		echo '<span class="status-indicator available-' . ( $status['available'] ? 'yes' : 'no' ) . '">Disponível: ' . ( $status['available'] ? 'Sim' : 'Não' ) . '</span>';
		echo '</div>';

		// Actions
		echo '<div class="adapter-actions">';

		if ( $status['enabled'] ) {
			echo '<form method="post" action="' . admin_url( 'admin-post.php' ) . '" style="display: inline;">';
			echo '<input type="hidden" name="action" value="apollo_toggle_adapter">';
			echo '<input type="hidden" name="adapter" value="' . esc_attr( $adapter_key ) . '">';
			echo '<input type="hidden" name="enable" value="0">';
			wp_nonce_field( 'apollo_toggle_adapter_' . $adapter_key );
			echo '<button type="submit" class="button">Desabilitar</button>';
			echo '</form>';
		} else {
			echo '<form method="post" action="' . admin_url( 'admin-post.php' ) . '" style="display: inline;">';
			echo '<input type="hidden" name="action" value="apollo_toggle_adapter">';
			echo '<input type="hidden" name="adapter" value="' . esc_attr( $adapter_key ) . '">';
			echo '<input type="hidden" name="enable" value="1">';
			wp_nonce_field( 'apollo_toggle_adapter_' . $adapter_key );
			echo '<button type="submit" class="button button-primary">Habilitar</button>';
			echo '</form>';
		}

		if ( $status['initialized'] ) {
			echo '<form method="post" action="' . admin_url( 'admin-post.php' ) . '" style="display: inline;">';
			echo '<input type="hidden" name="action" value="apollo_test_adapter">';
			echo '<input type="hidden" name="adapter" value="' . esc_attr( $adapter_key ) . '">';
			wp_nonce_field( 'apollo_test_adapter_' . $adapter_key );
			echo '<button type="submit" class="button">Testar</button>';
			echo '</form>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render configuration forms
	 */
	private function render_configuration_forms() {
		echo '<div class="apollo-adapter-configs">';
		echo '<h2>Configurações de Integração</h2>';
		echo '<p>Configure os parâmetros específicos de cada integração.</p>';
		echo '</div>';
	}

	/**
	 * Enqueue admin assets
	 */
	private function enqueue_admin_assets() {
		echo '<style>
        .apollo-adapters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .apollo-adapter-card {
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            background: #fff;
        }
        .apollo-adapter-card.available {
            border-left: 4px solid #46b450;
        }
        .apollo-adapter-card.enabled-unavailable {
            border-left: 4px solid #ffb900;
        }
        .apollo-adapter-card.disabled {
            border-left: 4px solid #dc3232;
        }
        .adapter-status {
            margin: 10px 0;
        }
        .status-indicator {
            display: block;
            margin: 5px 0;
            font-size: 13px;
        }
        .status-indicator.enabled-yes,
        .status-indicator.initialized-yes,
        .status-indicator.available-yes {
            color: #46b450;
        }
        .status-indicator.enabled-no,
        .status-indicator.initialized-no,
        .status-indicator.available-no {
            color: #dc3232;
        }
        .adapter-actions {
            margin-top: 15px;
        }
        .adapter-actions .button {
            margin-right: 5px;
        }
        </style>';
	}

	/**
	 * Handle adapter toggle
	 */
	public function admin_toggle_adapter() {
		$adapter = sanitize_text_field( $_POST['adapter'] ?? '' );
		$enable  = intval( $_POST['enable'] ?? 0 );

		if ( ! $adapter || ! wp_verify_nonce( $_POST['_wpnonce'], 'apollo_toggle_adapter_' . $adapter ) ) {
			wp_die( 'Invalid request' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		// Update configuration
		$this->update_adapter_enabled_status( $adapter, $enable );

		wp_redirect( admin_url( 'admin.php?page=apollo-integrations&message=' . ( $enable ? 'enabled' : 'disabled' ) ) );
		exit;
	}

	/**
	 * Handle adapter testing
	 */
	public function admin_test_adapter() {
		$adapter = sanitize_text_field( $_POST['adapter'] ?? '' );

		if ( ! $adapter || ! wp_verify_nonce( $_POST['_wpnonce'], 'apollo_test_adapter_' . $adapter ) ) {
			wp_die( 'Invalid request' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$adapter_instance = $this->get_adapter( $adapter );
		$test_result      = $adapter_instance && $this->is_plugin_available( $adapter ) ? 'success' : 'failed';

		wp_redirect( admin_url( 'admin.php?page=apollo-integrations&test_result=' . $test_result . '&tested_adapter=' . $adapter ) );
		exit;
	}

	/**
	 * AJAX check adapter status
	 */
	public function ajax_check_adapter_status() {
		check_ajax_referer( 'apollo_adapter_status' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$adapter = sanitize_text_field( $_POST['adapter'] ?? '' );

		if ( $adapter ) {
			$status = $this->get_adapter_status()[ $adapter ] ?? null;
		} else {
			$status = $this->get_adapter_status();
		}

		wp_send_json_success( $status );
	}

	/**
	 * Add adapter status widget to admin dashboard
	 */
	public function add_adapter_status_widget() {
		$status          = $this->get_adapter_status();
		$available_count = count( array_filter( $status, fn( $s ) => $s['available'] ) );
		$total_count     = count( $status );

		echo '<div class="apollo-dashboard-widget">';
		echo '<h3>Status das Integrações</h3>';
		echo '<p><strong>' . $available_count . '/' . $total_count . '</strong> integrações disponíveis</p>';

		foreach ( $status as $key => $adapter_status ) {
			$icon          = $adapter_status['available'] ? '✅' : '❌';
			$adapter_names = array(
				'groups'        => 'Groups',
				'event_manager' => 'Events',
				'wpadverts'     => 'Adverts',
				'badgeos'       => 'Badges',
			);
			$name          = $adapter_names[ $key ] ?? ucfirst( $key );

			echo '<div>' . $icon . ' ' . esc_html( $name ) . '</div>';
		}

		echo '<p><a href="' . admin_url( 'admin.php?page=apollo-integrations' ) . '">Gerenciar Integrações</a></p>';
		echo '</div>';
	}

	/**
	 * Update adapter enabled status in configuration
	 */
	private function update_adapter_enabled_status( $adapter_key, $enabled ) {
		$config_key = match ( $adapter_key ) {
			'groups' => 'itthinx_groups',
			'event_manager' => 'wp_event_manager',
			'wpadverts' => 'wpadverts',
			'badgeos' => 'badgeos',
			default => $adapter_key
		};

		$integrations_config                           = get_option( 'apollo_integrations_config', array() );
		$integrations_config[ $config_key ]['enabled'] = $enabled;

		update_option( 'apollo_integrations_config', $integrations_config );

		// Reinitialize if enabled
		if ( $enabled ) {
			$this->reinitialize_adapter( $adapter_key );
		}
	}
}

// Global function to access adapter service
function apollo_adapters(): AdapterServiceProvider {
	static $instance = null;

	if ( $instance === null ) {
		$instance = new AdapterServiceProvider();
	}

	return $instance;
}
