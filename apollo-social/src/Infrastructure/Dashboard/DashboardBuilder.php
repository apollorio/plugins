<?php

namespace Apollo\Infrastructure\Dashboard;

use Apollo\Infrastructure\Rendering\CanvasRenderer;

/**
 * Dashboard Builder - ShadCN + Motion.dev Based
 *
 * Builds customizable dashboard pages with draggable widgets
 * Auto-creates user pages for new registrations
 */
class DashboardBuilder extends CanvasRenderer {

	private $widget_registry;
	private $page_layout;
	private $user_id;

	public function __construct() {
		parent::__construct();
		$this->widget_registry = new WidgetRegistry();
	}

	/**
	 * Build dashboard page
	 */
	public function buildDashboard( $route_config, $user_id = null ) {
		$this->user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $this->user_id ) {
			wp_die( 'Você precisa estar logado para acessar esta página.', 'Acesso Negado', array( 'response' => 403 ) );
		}

		// Ensure user page exists
		$this->ensureUserPageExists( $this->user_id );

		// Load user page layout
		$this->page_layout = $this->loadUserPageLayout( $this->user_id );

		// Build with parent CanvasRenderer
		parent::render( $route_config );
	}

	/**
	 * Ensure user page exists (auto-create if needed)
	 */
	private function ensureUserPageExists( $user_id ) {
		$user_page = apollo_get_user_page( $user_id );

		if ( ! $user_page ) {
			// Auto-create user page
			$user_page = apollo_get_or_create_user_page( $user_id );

			// Initialize with default widgets
			$this->initializeDefaultWidgets( $user_id, $user_page->ID );
		}
	}

	/**
	 * Initialize default widgets for new user page
	 */
	private function initializeDefaultWidgets( $user_id, $page_id ) {
		$default_widgets = array(
			array(
				'type'     => 'profile-header',
				'position' => array(
					'x' => 0,
					'y' => 0,
				),
				'size'     => array(
					'w' => 12,
					'h' => 3,
				),
				'config'   => array(),
			),
			array(
				'type'     => 'depoimentos',
				'position' => array(
					'x' => 0,
					'y' => 3,
				),
				'size'     => array(
					'w' => 12,
					'h' => 6,
				),
				'config'   => array(
					'title'          => 'Depoimentos',
					'allow_comments' => true,
					'max_comments'   => 50,
				),
			),
			array(
				'type'     => 'bio',
				'position' => array(
					'x' => 0,
					'y' => 9,
				),
				'size'     => array(
					'w' => 6,
					'h' => 4,
				),
				'config'   => array(),
			),
			array(
				'type'     => 'stats',
				'position' => array(
					'x' => 6,
					'y' => 9,
				),
				'size'     => array(
					'w' => 6,
					'h' => 4,
				),
				'config'   => array(),
			),
		);

		update_post_meta( $page_id, '_apollo_widgets', $default_widgets );
		update_post_meta( $page_id, '_apollo_layout_version', '1.0' );
	}

	/**
	 * Load user page layout
	 */
	private function loadUserPageLayout( $user_id ) {
		$user_page = apollo_get_user_page( $user_id );

		if ( ! $user_page ) {
			return array();
		}

		$widgets = get_post_meta( $user_page->ID, '_apollo_widgets', true );

		return is_array( $widgets ) ? $widgets : array();
	}

	/**
	 * Get widgets data for JavaScript
	 */
	public function getWidgetsData() {
		return array(
			'widgets'           => $this->page_layout,
			'available_widgets' => $this->widget_registry->getAvailableWidgets(),
			'user_id'           => $this->user_id,
			'can_edit'          => current_user_can( 'edit_post', apollo_get_user_page( $this->user_id )->ID ?? 0 ),
		);
	}
}
