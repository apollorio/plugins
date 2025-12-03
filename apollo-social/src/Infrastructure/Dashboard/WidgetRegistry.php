<?php
namespace Apollo\Infrastructure\Dashboard;

/**
 * Widget Registry
 * Manages available widgets for dashboard pages
 */
class WidgetRegistry {

	private $widgets = array();

	public function __construct() {
		$this->registerDefaultWidgets();
	}

	/**
	 * Register default widgets
	 */
	private function registerDefaultWidgets() {
		$this->registerWidget(
			'profile-header',
			array(
				'name'         => 'Cabeçalho do Perfil',
				'icon'         => 'user',
				'category'     => 'profile',
				'default_size' => array(
					'w' => 12,
					'h' => 3,
				),
				'min_size'     => array(
					'w' => 6,
					'h' => 2,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 5,
				),
			)
		);

		$this->registerWidget(
			'depoimentos',
			array(
				'name'          => 'Depoimentos',
				'icon'          => 'message-circle',
				'category'      => 'social',
				'default_size'  => array(
					'w' => 12,
					'h' => 6,
				),
				'min_size'      => array(
					'w' => 6,
					'h' => 4,
				),
				'max_size'      => array(
					'w' => 12,
					'h' => 12,
				),
				'configurable'  => true,
				'config_fields' => array(
					'title'          => array(
						'type'  => 'text',
						'label' => 'Título',
					),
					'allow_comments' => array(
						'type'  => 'checkbox',
						'label' => 'Permitir Comentários',
					),
					'max_comments'   => array(
						'type'  => 'number',
						'label' => 'Máximo de Comentários',
					),
				),
			)
		);

		$this->registerWidget(
			'bio',
			array(
				'name'         => 'Biografia',
				'icon'         => 'file-text',
				'category'     => 'content',
				'default_size' => array(
					'w' => 6,
					'h' => 4,
				),
				'min_size'     => array(
					'w' => 4,
					'h' => 3,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 8,
				),
			)
		);

		$this->registerWidget(
			'stats',
			array(
				'name'         => 'Estatísticas',
				'icon'         => 'bar-chart',
				'category'     => 'analytics',
				'default_size' => array(
					'w' => 6,
					'h' => 4,
				),
				'min_size'     => array(
					'w' => 4,
					'h' => 3,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 6,
				),
			)
		);

		$this->registerWidget(
			'chat',
			array(
				'name'         => 'Chat',
				'icon'         => 'message-square',
				'category'     => 'communication',
				'default_size' => array(
					'w' => 6,
					'h' => 8,
				),
				'min_size'     => array(
					'w' => 4,
					'h' => 6,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 12,
				),
			)
		);

		$this->registerWidget(
			'documents',
			array(
				'name'         => 'Documentos',
				'icon'         => 'file',
				'category'     => 'content',
				'default_size' => array(
					'w' => 6,
					'h' => 6,
				),
				'min_size'     => array(
					'w' => 4,
					'h' => 4,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 10,
				),
			)
		);

		$this->registerWidget(
			'sign-document',
			array(
				'name'         => 'Assinar Documento',
				'icon'         => 'pen-tool',
				'category'     => 'actions',
				'default_size' => array(
					'w' => 6,
					'h' => 6,
				),
				'min_size'     => array(
					'w' => 4,
					'h' => 4,
				),
				'max_size'     => array(
					'w' => 12,
					'h' => 8,
				),
			)
		);

		// P0-8: Register playlist widget
		$this->registerWidget(
			'playlist',
			array(
				'name'          => 'Playlist',
				'icon'          => 'music-2-line',
				'category'      => 'media',
				'default_size'  => array(
					'w' => 6,
					'h' => 6,
				),
				'min_size'      => array(
					'w' => 4,
					'h' => 4,
				),
				'max_size'      => array(
					'w' => 12,
					'h' => 10,
				),
				'configurable'  => true,
				'config_fields' => array(
					'title'          => array(
						'type'    => 'text',
						'label'   => 'Título',
						'default' => 'Playlist',
					),
					'spotify_url'    => array(
						'type'        => 'url',
						'label'       => 'URL do Spotify',
						'placeholder' => 'https://open.spotify.com/playlist/...',
					),
					'soundcloud_url' => array(
						'type'        => 'url',
						'label'       => 'URL do SoundCloud',
						'placeholder' => 'https://soundcloud.com/...',
					),
				),
			)
		);
	}

	/**
	 * Register a widget
	 */
	public function registerWidget( $type, $config ) {
		$this->widgets[ $type ] = wp_parse_args(
			$config,
			array(
				'name'          => ucfirst( $type ),
				'icon'          => 'square',
				'category'      => 'general',
				'default_size'  => array(
					'w' => 6,
					'h' => 4,
				),
				'min_size'      => array(
					'w' => 2,
					'h' => 2,
				),
				'max_size'      => array(
					'w' => 12,
					'h' => 12,
				),
				'configurable'  => false,
				'config_fields' => array(),
			)
		);
	}

	/**
	 * Get available widgets
	 */
	public function getAvailableWidgets() {
		return $this->widgets;
	}

	/**
	 * Get widget config
	 */
	public function getWidget( $type ) {
		return isset( $this->widgets[ $type ] ) ? $this->widgets[ $type ] : null;
	}
}
