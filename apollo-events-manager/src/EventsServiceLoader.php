<?php
/**
 * Apollo Events Manager Service Loader
 *
 * Bootstraps all decomposed services and integrates with DI container.
 * This replaces the monolithic god class pattern.
 *
 * @package Apollo\Events
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo\Events;

use Apollo\Events\Controllers\EventsAjaxController;
use Apollo\Events\Services\EventsAdmin;
use Apollo\Events\Services\EventsAnalytics;
use Apollo\Events\Services\EventsAssetLoader;
use Apollo\Events\Services\EventsCronJobs;
use Apollo\Events\Services\EventsShortcodes;

/**
 * Service container and loader for Apollo Events.
 */
final class EventsServiceLoader {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered services.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Boot all services.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->registerServices();
		$this->initializeServices();
	}

	/**
	 * Register all services in container.
	 *
	 * @return void
	 */
	private function registerServices(): void {
		// Asset Loader
		$this->services['asset_loader'] = new EventsAssetLoader(
			APOLLO_APRIO_URL,
			APOLLO_APRIO_VERSION
		);

		// AJAX Controller
		$this->services['ajax_controller'] = new EventsAjaxController();

		// Shortcodes
		$this->services['shortcodes'] = new EventsShortcodes();

		// Analytics
		$this->services['analytics'] = new EventsAnalytics();

		// Admin
		$this->services['admin'] = new EventsAdmin();

		// Cron Jobs
		$this->services['cron_jobs'] = new EventsCronJobs();

		// Allow plugins to add more services
		$this->services = apply_filters( 'apollo_events_services', $this->services );
	}

	/**
	 * Initialize all registered services.
	 *
	 * @return void
	 */
	private function initializeServices(): void {
		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Get a service by key.
	 *
	 * @param string $key Service key.
	 * @return object|null
	 */
	public function get( string $key ): ?object {
		return $this->services[ $key ] ?? null;
	}

	/**
	 * Check if a service exists.
	 *
	 * @param string $key Service key.
	 * @return bool
	 */
	public function has( string $key ): bool {
		return isset( $this->services[ $key ] );
	}

	/**
	 * Get all registered services.
	 *
	 * @return array<string, object>
	 */
	public function all(): array {
		return $this->services;
	}

	/**
	 * Get the asset loader service.
	 *
	 * @return EventsAssetLoader
	 */
	public function assets(): EventsAssetLoader {
		return $this->services['asset_loader'];
	}

	/**
	 * Get the AJAX controller service.
	 *
	 * @return EventsAjaxController
	 */
	public function ajax(): EventsAjaxController {
		return $this->services['ajax_controller'];
	}

	/**
	 * Get the shortcodes service.
	 *
	 * @return EventsShortcodes
	 */
	public function shortcodes(): EventsShortcodes {
		return $this->services['shortcodes'];
	}

	/**
	 * Get the analytics service.
	 *
	 * @return EventsAnalytics
	 */
	public function analytics(): EventsAnalytics {
		return $this->services['analytics'];
	}

	/**
	 * Get the admin service.
	 *
	 * @return EventsAdmin
	 */
	public function admin(): EventsAdmin {
		return $this->services['admin'];
	}

	/**
	 * Get the cron jobs service.
	 *
	 * @return EventsCronJobs
	 */
	public function cron(): EventsCronJobs {
		return $this->services['cron_jobs'];
	}
}

/**
 * Helper function to get the Events Service Loader.
 *
 * @return EventsServiceLoader
 */
function apollo_events(): EventsServiceLoader {
	return EventsServiceLoader::getInstance();
}
