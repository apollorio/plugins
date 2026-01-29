<?php

declare(strict_types=1);
/**
 * Apollo Navbar Apps Manager
 *
 * Admin panel for managing custom apps displayed in the navbar menu-app modal.
 * Allows administrators to add/edit/delete apps with:
 * - Background image (file upload or URL)
 * - Background linear gradient
 * - Icon (Apollo CDN icons)
 * - URL with target options (blank/same page)
 *
 * @package Apollo_Core
 * @since 1.9.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class Apollo_Navbar_Apps
{

	/**
	 * Option key for storing apps
	 */
	const OPTION_KEY = 'apollo_navbar_apps';

	/**
	 * Default fallback apps (always available)
	 */
	private static $default_apps = [
		[
			'id' => 'eventos',
			'label' => 'Eventos',
			'icon' => 'ri-calendar-event-fill',
			'icon_text' => 'EV',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #FF6925, #E55A1E)',
			'background_image' => '',
			'url' => '/eventos/',
			'target' => '_self',
			'is_default' => true,
			'order' => 1
		],
		[
			'id' => 'agenda',
			'label' => 'Agenda',
			'icon' => 'ri-calendar-line',
			'icon_text' => 'AG',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
			'background_image' => '',
			'url' => '/agenda/',
			'target' => '_self',
			'is_default' => true,
			'order' => 2
		],
		[
			'id' => 'djs',
			'label' => 'DJs',
			'icon' => 'ri-disc-fill',
			'icon_text' => 'DJ',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #a855f7, #9333ea)',
			'background_image' => '',
			'url' => '/djs/',
			'target' => '_self',
			'is_default' => true,
			'order' => 3
		],
		[
			'id' => 'locais',
			'label' => 'Locais',
			'icon' => 'ri-map-pin-2-fill',
			'icon_text' => 'LC',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #22c55e, #16a34a)',
			'background_image' => '',
			'url' => '/locais/',
			'target' => '_self',
			'is_default' => true,
			'order' => 4
		],
		[
			'id' => 'classificados',
			'label' => 'Classif.',
			'icon' => 'ri-price-tag-3-fill',
			'icon_text' => 'CL',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #f97316, #ea580c)',
			'background_image' => '',
			'url' => '/classificados/',
			'target' => '_self',
			'is_default' => true,
			'order' => 5
		],
		[
			'id' => 'feed',
			'label' => 'Feed',
			'icon' => 'ri-broadcast-fill',
			'icon_text' => 'FD',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)',
			'background_image' => '',
			'url' => '/feed/',
			'target' => '_self',
			'is_default' => true,
			'order' => 6
		],
		[
			'id' => 'mapa',
			'label' => 'Mapa',
			'icon' => 'ri-earth-fill',
			'icon_text' => 'MP',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #64748b, #475569)',
			'background_image' => '',
			'url' => '/mapa/',
			'target' => '_self',
			'is_default' => true,
			'order' => 7
		],
		[
			'id' => 'mais',
			'label' => 'Mais',
			'icon' => 'ri-add-line',
			'icon_text' => '+',
			'background_type' => 'gradient',
			'background_gradient' => 'linear-gradient(135deg, #94a3b8, #64748b)',
			'background_image' => '',
			'url' => '/apps/',
			'target' => '_self',
			'is_default' => true,
			'order' => 8
		]
	];

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		// Admin hooks
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

		// AJAX handlers - Admin
		add_action('wp_ajax_apollo_save_navbar_apps', [$this, 'ajax_save_apps']);
		add_action('wp_ajax_apollo_get_navbar_apps', [$this, 'ajax_get_apps']);
		add_action('wp_ajax_apollo_delete_navbar_app', [$this, 'ajax_delete_app']);
		add_action('wp_ajax_apollo_reorder_navbar_apps', [$this, 'ajax_reorder_apps']);
		add_action('wp_ajax_apollo_upload_app_image', [$this, 'ajax_upload_image']);

		// AJAX handlers - Login
		add_action('wp_ajax_nopriv_apollo_ajax_login', [$this, 'ajax_login']);

		// Frontend hooks
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
		add_action('wp_footer', [$this, 'render_navbar'], 5);
		add_action('admin_bar_menu', [$this, 'maybe_hide_admin_bar'], 999);

		// REST API
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu()
	{
		add_submenu_page(
			'apollo-core-hub',
			__('Navbar Apps', 'apollo-core'),
			__('Navbar Apps', 'apollo-core'),
			'manage_options',
			'apollo-navbar-apps',
			[$this, 'render_admin_page']
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets($hook)
	{
		if (strpos($hook, 'apollo-navbar-apps') === false) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'apollo-navbar-apps-admin',
			APOLLO_CORE_PLUGIN_URL . 'assets/css/admin-navbar-apps.css',
			[],
			APOLLO_CORE_VERSION
		);

		wp_enqueue_script(
			'apollo-navbar-apps-admin',
			APOLLO_CORE_PLUGIN_URL . 'assets/js/admin-navbar-apps.js',
			['jquery', 'jquery-ui-sortable', 'wp-util'],
			APOLLO_CORE_VERSION,
			true
		);

		wp_localize_script('apollo-navbar-apps-admin', 'apolloNavbarApps', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('apollo_navbar_apps_nonce'),
			'defaultApps' => self::$default_apps,
			'strings' => [
				'confirmDelete' => __('Tem certeza que deseja excluir este app?', 'apollo-core'),
				'saved' => __('Alterações salvas com sucesso!', 'apollo-core'),
				'error' => __('Erro ao salvar. Tente novamente.', 'apollo-core'),
				'uploading' => __('Enviando...', 'apollo-core'),
				'selectImage' => __('Selecionar Imagem', 'apollo-core'),
				'useImage' => __('Usar esta imagem', 'apollo-core')
			],
			'iconOptions' => $this->get_icon_options()
		]);
	}

	/**
	 * Get available icon options
	 */
	private function get_icon_options()
	{
		return [
			'ri-calendar-event-fill' => 'Calendário Evento',
			'ri-calendar-line' => 'Calendário',
			'ri-disc-fill' => 'Disco/DJ',
			'ri-map-pin-2-fill' => 'Pin Mapa',
			'ri-price-tag-3-fill' => 'Tag Preço',
			'ri-broadcast-fill' => 'Broadcast',
			'ri-earth-fill' => 'Terra/Globo',
			'ri-add-line' => 'Adicionar',
			'ri-home-4-fill' => 'Casa',
			'ri-user-3-fill' => 'Usuário',
			'ri-heart-fill' => 'Coração',
			'ri-star-fill' => 'Estrela',
			'ri-fire-fill' => 'Fogo',
			'ri-music-2-fill' => 'Música',
			'ri-headphone-fill' => 'Fone',
			'ri-mic-fill' => 'Microfone',
			'ri-ticket-2-fill' => 'Ingresso',
			'ri-vip-crown-fill' => 'VIP',
			'ri-group-fill' => 'Grupo',
			'ri-chat-3-fill' => 'Chat',
			'ri-notification-3-fill' => 'Notificação',
			'ri-settings-3-fill' => 'Configurações',
			'ri-search-line' => 'Buscar',
			'ri-menu-line' => 'Menu',
			'ri-apps-2-fill' => 'Apps',
			'ri-gallery-fill' => 'Galeria',
			'ri-video-fill' => 'Vídeo',
			'ri-live-fill' => 'Ao Vivo',
			'ri-spotify-fill' => 'Spotify',
			'ri-soundcloud-fill' => 'SoundCloud',
			'ri-instagram-fill' => 'Instagram',
			'ri-facebook-fill' => 'Facebook',
			'ri-twitter-x-fill' => 'Twitter/X',
			'ri-whatsapp-fill' => 'WhatsApp',
			'ri-telegram-fill' => 'Telegram'
		];
	}

	/**
	 * Get gradient presets
	 */
	public static function get_gradient_presets()
	{
		return [
			'orange' => 'linear-gradient(135deg, #FF6925, #E55A1E)',
			'blue' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
			'green' => 'linear-gradient(135deg, #22c55e, #16a34a)',
			'purple' => 'linear-gradient(135deg, #a855f7, #9333ea)',
			'red' => 'linear-gradient(135deg, #ef4444, #dc2626)',
			'gray' => 'linear-gradient(135deg, #64748b, #475569)',
			'pink' => 'linear-gradient(135deg, #ec4899, #db2777)',
			'yellow' => 'linear-gradient(135deg, #eab308, #ca8a04)',
			'cyan' => 'linear-gradient(135deg, #06b6d4, #0891b2)',
			'indigo' => 'linear-gradient(135deg, #6366f1, #4f46e5)',
			'dark' => 'linear-gradient(135deg, #1e293b, #0f172a)',
			'sunset' => 'linear-gradient(135deg, #f97316, #ef4444)',
			'ocean' => 'linear-gradient(135deg, #06b6d4, #3b82f6)',
			'forest' => 'linear-gradient(135deg, #22c55e, #14b8a6)',
			'berry' => 'linear-gradient(135deg, #a855f7, #ec4899)'
		];
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-core'));
		}

		$apps = $this->get_apps();
		$gradients = self::get_gradient_presets();
?>
		<div class="wrap apollo-navbar-apps-admin">
			<h1>
				<span class="dashicons dashicons-grid-view"></span>
				<?php _e('Apollo Navbar Apps', 'apollo-core'); ?>
			</h1>

			<p class="description">
				<?php _e('Gerencie os aplicativos exibidos no modal de apps da navbar. Arraste para reordenar.', 'apollo-core'); ?>
			</p>

			<div class="apollo-apps-container">
				<!-- Apps List -->
				<div class="apollo-apps-list-wrapper">
					<div class="apollo-apps-header">
						<h2><?php _e('Apps Ativos', 'apollo-core'); ?></h2>
						<button type="button" class="button button-primary" id="apollo-add-app">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php _e('Adicionar App', 'apollo-core'); ?>
						</button>
					</div>

					<div class="apollo-apps-list" id="apollo-apps-sortable">
						<?php foreach ($apps as $app): ?>
							<?php $this->render_app_item($app); ?>
						<?php endforeach; ?>
					</div>

					<div class="apollo-apps-actions">
						<button type="button" class="button button-primary button-hero" id="apollo-save-apps">
							<span class="dashicons dashicons-saved"></span>
							<?php _e('Salvar Alterações', 'apollo-core'); ?>
						</button>
						<button type="button" class="button" id="apollo-reset-defaults">
							<span class="dashicons dashicons-undo"></span>
							<?php _e('Restaurar Padrões', 'apollo-core'); ?>
						</button>
					</div>
				</div>

				<!-- Preview Panel -->
				<div class="apollo-preview-wrapper">
					<h2><?php _e('Preview', 'apollo-core'); ?></h2>
					<div class="apollo-preview-container">
						<div class="apollo-preview-navbar">
							<div class="preview-clock">00:00:00</div>
							<div class="preview-btn preview-notif">
								<span class="preview-badge"></span>
							</div>
							<div class="preview-btn preview-apps active">
								<svg viewBox="0 0 24 24" fill="currentColor">
									<path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z" />
								</svg>
							</div>
							<div class="preview-btn preview-avatar">M</div>
						</div>
						<div class="apollo-preview-menu" id="apollo-preview-menu">
							<div class="preview-section-title">Aplicativos</div>
							<div class="preview-apps-grid" id="apollo-preview-grid">
								<!-- Populated by JS -->
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Edit Modal -->
			<div class="apollo-modal" id="apollo-edit-modal" style="display:none;">
				<div class="apollo-modal-overlay"></div>
				<div class="apollo-modal-content">
					<div class="apollo-modal-header">
						<h3 id="apollo-modal-title"><?php _e('Editar App', 'apollo-core'); ?></h3>
						<button type="button" class="apollo-modal-close">&times;</button>
					</div>
					<div class="apollo-modal-body">
						<form id="apollo-app-form">
							<input type="hidden" name="app_id" id="app_id">

							<div class="apollo-form-row">
								<div class="apollo-form-group">
									<label for="app_label"><?php _e('Nome do App', 'apollo-core'); ?></label>
									<input type="text" name="app_label" id="app_label" class="regular-text" required maxlength="12">
									<p class="description"><?php _e('Máximo 12 caracteres', 'apollo-core'); ?></p>
								</div>
								<div class="apollo-form-group">
									<label for="app_icon_text"><?php _e('Texto do Ícone', 'apollo-core'); ?></label>
									<input type="text" name="app_icon_text" id="app_icon_text" class="small-text" maxlength="3">
									<p class="description"><?php _e('Fallback se ícone não carregar', 'apollo-core'); ?></p>
								</div>
							</div>

							<div class="apollo-form-group">
								<label for="app_icon"><?php _e('Ícone', 'apollo-core'); ?></label>
								<select name="app_icon" id="app_icon" class="regular-text">
									<?php foreach ($this->get_icon_options() as $icon => $label): ?>
										<option value="<?php echo esc_attr($icon); ?>"><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
								<div class="apollo-icon-preview">
									<i id="icon-preview-element"></i>
								</div>
							</div>

							<div class="apollo-form-group">
								<label><?php _e('Tipo de Fundo', 'apollo-core'); ?></label>
								<div class="apollo-radio-group">
									<label>
										<input type="radio" name="background_type" value="gradient" checked>
										<?php _e('Gradiente', 'apollo-core'); ?>
									</label>
									<label>
										<input type="radio" name="background_type" value="image">
										<?php _e('Imagem', 'apollo-core'); ?>
									</label>
								</div>
							</div>

							<!-- Gradient Options -->
							<div class="apollo-form-group apollo-bg-gradient" id="gradient-options">
								<label><?php _e('Gradiente Pré-definido', 'apollo-core'); ?></label>
								<div class="apollo-gradient-presets">
									<?php foreach ($gradients as $key => $gradient): ?>
										<button type="button" class="gradient-preset" data-gradient="<?php echo esc_attr($gradient); ?>" style="background: <?php echo esc_attr($gradient); ?>;" title="<?php echo esc_attr(ucfirst($key)); ?>"></button>
									<?php endforeach; ?>
								</div>
								<div class="apollo-form-row" style="margin-top: 1rem;">
									<div class="apollo-form-group">
										<label for="gradient_custom"><?php _e('Ou CSS personalizado', 'apollo-core'); ?></label>
										<input type="text" name="background_gradient" id="gradient_custom" class="regular-text" placeholder="linear-gradient(135deg, #FF6925, #E55A1E)">
									</div>
								</div>
							</div>

							<!-- Image Options -->
							<div class="apollo-form-group apollo-bg-image" id="image-options" style="display:none;">
								<label><?php _e('Imagem de Fundo', 'apollo-core'); ?></label>
								<div class="apollo-image-upload">
									<div class="apollo-image-preview" id="image-preview">
										<span class="placeholder"><?php _e('Nenhuma imagem', 'apollo-core'); ?></span>
									</div>
									<div class="apollo-image-actions">
										<button type="button" class="button" id="upload-image-btn">
											<span class="dashicons dashicons-upload"></span>
											<?php _e('Upload', 'apollo-core'); ?>
										</button>
										<button type="button" class="button" id="remove-image-btn" style="display:none;">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</div>
								<input type="hidden" name="background_image" id="background_image">
								<div class="apollo-form-group" style="margin-top: 1rem;">
									<label for="image_url"><?php _e('Ou URL da imagem', 'apollo-core'); ?></label>
									<input type="url" name="image_url" id="image_url" class="regular-text" placeholder="https://...">
								</div>
								<div class="apollo-form-group">
									<label for="overlay_gradient"><?php _e('Overlay (opcional)', 'apollo-core'); ?></label>
									<input type="text" name="overlay_gradient" id="overlay_gradient" class="regular-text" placeholder="linear-gradient(135deg, rgba(0,0,0,0.5), rgba(0,0,0,0.3))">
									<p class="description"><?php _e('Gradiente sobre a imagem para melhor legibilidade', 'apollo-core'); ?></p>
								</div>
							</div>

							<div class="apollo-form-row">
								<div class="apollo-form-group apollo-form-group-wide">
									<label for="app_url"><?php _e('URL', 'apollo-core'); ?></label>
									<input type="text" name="app_url" id="app_url" class="regular-text" required placeholder="/pagina/ ou https://...">
								</div>
								<div class="apollo-form-group">
									<label for="app_target"><?php _e('Abrir em', 'apollo-core'); ?></label>
									<select name="app_target" id="app_target">
										<option value="_self"><?php _e('Mesma página', 'apollo-core'); ?></option>
										<option value="_blank"><?php _e('Nova aba', 'apollo-core'); ?></option>
									</select>
								</div>
							</div>

							<div class="apollo-form-group">
								<label>
									<input type="checkbox" name="app_active" id="app_active" value="1" checked>
									<?php _e('App ativo', 'apollo-core'); ?>
								</label>
							</div>
						</form>
					</div>
					<div class="apollo-modal-footer">
						<button type="button" class="button" id="apollo-modal-cancel"><?php _e('Cancelar', 'apollo-core'); ?></button>
						<button type="button" class="button button-primary" id="apollo-modal-save"><?php _e('Salvar', 'apollo-core'); ?></button>
					</div>
				</div>
			</div>

			<!-- Toast Notification -->
			<div class="apollo-toast" id="apollo-toast"></div>
		</div>
	<?php
	}

	/**
	 * Render single app item in admin list
	 */
	private function render_app_item($app)
	{
		$is_default = !empty($app['is_default']);
		$bg_style = '';

		if (!empty($app['background_image'])) {
			$bg_style = 'background-image: url(' . esc_url($app['background_image']) . '); background-size: cover; background-position: center;';
			if (!empty($app['overlay_gradient'])) {
				$bg_style = 'background: ' . esc_attr($app['overlay_gradient']) . ', url(' . esc_url($app['background_image']) . '); background-size: cover; background-position: center;';
			}
		} elseif (!empty($app['background_gradient'])) {
			$bg_style = 'background: ' . esc_attr($app['background_gradient']) . ';';
		}
	?>
		<div class="apollo-app-item" data-app-id="<?php echo esc_attr($app['id']); ?>" data-app='<?php echo esc_attr(wp_json_encode($app)); ?>'>
			<div class="apollo-app-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>
			<div class="apollo-app-icon" style="<?php echo $bg_style; ?>">
				<?php if (!empty($app['icon'])): ?>
					<i class="<?php echo esc_attr($app['icon']); ?>"></i>
				<?php else: ?>
					<span><?php echo esc_html($app['icon_text'] ?? substr($app['label'], 0, 2)); ?></span>
				<?php endif; ?>
			</div>
			<div class="apollo-app-info">
				<strong><?php echo esc_html($app['label']); ?></strong>
				<span class="apollo-app-url"><?php echo esc_html($app['url']); ?></span>
				<?php if (!empty($app['target']) && $app['target'] === '_blank'): ?>
					<span class="apollo-app-target dashicons dashicons-external"></span>
				<?php endif; ?>
			</div>
			<div class="apollo-app-actions">
				<button type="button" class="button apollo-edit-app" title="<?php esc_attr_e('Editar', 'apollo-core'); ?>">
					<span class="dashicons dashicons-edit"></span>
				</button>
				<?php if (!$is_default): ?>
					<button type="button" class="button apollo-delete-app" title="<?php esc_attr_e('Excluir', 'apollo-core'); ?>">
						<span class="dashicons dashicons-trash"></span>
					</button>
				<?php endif; ?>
			</div>
			<?php if ($is_default): ?>
				<span class="apollo-app-badge"><?php _e('Padrão', 'apollo-core'); ?></span>
			<?php endif; ?>
		</div>
	<?php
	}

	/**
	 * Get all apps (merged defaults + custom)
	 */
	public function get_apps()
	{
		$saved_apps = get_option(self::OPTION_KEY, []);

		if (empty($saved_apps)) {
			return self::$default_apps;
		}

		// Merge with defaults to ensure they always exist
		$merged = [];
		$saved_ids = array_column($saved_apps, 'id');

		// Add saved apps first (they may include modified defaults)
		foreach ($saved_apps as $app) {
			$merged[] = $app;
		}

		// Add any missing default apps
		foreach (self::$default_apps as $default) {
			if (!in_array($default['id'], $saved_ids)) {
				$merged[] = $default;
			}
		}

		// Sort by order
		usort($merged, function ($a, $b) {
			return ($a['order'] ?? 999) - ($b['order'] ?? 999);
		});

		return $merged;
	}

	/**
	 * AJAX: Save apps
	 */
	public function ajax_save_apps()
	{
		check_ajax_referer('apollo_navbar_apps_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permissão negada.', 'apollo-core')]);
		}

		$apps = isset($_POST['apps']) ? $_POST['apps'] : [];
		$sanitized = [];

		foreach ($apps as $index => $app) {
			$sanitized[] = [
				'id' => sanitize_key($app['id'] ?? uniqid('app_')),
				'label' => sanitize_text_field($app['label'] ?? ''),
				'icon' => sanitize_text_field($app['icon'] ?? ''),
				'icon_text' => sanitize_text_field(substr($app['icon_text'] ?? '', 0, 3)),
				'background_type' => in_array($app['background_type'] ?? '', ['gradient', 'image']) ? $app['background_type'] : 'gradient',
				'background_gradient' => sanitize_text_field($app['background_gradient'] ?? ''),
				'background_image' => esc_url_raw($app['background_image'] ?? ''),
				'overlay_gradient' => sanitize_text_field($app['overlay_gradient'] ?? ''),
				'url' => esc_url_raw($app['url'] ?? ''),
				'target' => in_array($app['target'] ?? '', ['_self', '_blank']) ? $app['target'] : '_self',
				'is_default' => !empty($app['is_default']),
				'active' => isset($app['active']) ? (bool)$app['active'] : true,
				'order' => intval($index + 1)
			];
		}

		update_option(self::OPTION_KEY, $sanitized);

		wp_send_json_success([
			'message' => __('Apps salvos com sucesso!', 'apollo-core'),
			'apps' => $sanitized
		]);
	}

	/**
	 * AJAX: Get apps
	 */
	public function ajax_get_apps()
	{
		check_ajax_referer('apollo_navbar_apps_nonce', 'nonce');
		wp_send_json_success(['apps' => $this->get_apps()]);
	}

	/**
	 * AJAX: Delete app
	 */
	public function ajax_delete_app()
	{
		check_ajax_referer('apollo_navbar_apps_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permissão negada.', 'apollo-core')]);
		}

		$app_id = sanitize_key($_POST['app_id'] ?? '');
		$apps = get_option(self::OPTION_KEY, []);

		$apps = array_filter($apps, function ($app) use ($app_id) {
			return $app['id'] !== $app_id;
		});

		update_option(self::OPTION_KEY, array_values($apps));

		wp_send_json_success(['message' => __('App excluído.', 'apollo-core')]);
	}

	/**
	 * AJAX: Reorder apps
	 */
	public function ajax_reorder_apps()
	{
		check_ajax_referer('apollo_navbar_apps_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permissão negada.', 'apollo-core')]);
		}

		$order = isset($_POST['order']) ? array_map('sanitize_key', $_POST['order']) : [];
		$apps = get_option(self::OPTION_KEY, self::$default_apps);

		$reordered = [];
		foreach ($order as $index => $id) {
			foreach ($apps as $app) {
				if ($app['id'] === $id) {
					$app['order'] = $index + 1;
					$reordered[] = $app;
					break;
				}
			}
		}

		update_option(self::OPTION_KEY, $reordered);

		wp_send_json_success(['message' => __('Ordem atualizada.', 'apollo-core')]);
	}

	/**
	 * AJAX: Upload image
	 */
	public function ajax_upload_image()
	{
		check_ajax_referer('apollo_navbar_apps_nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Permissão negada.', 'apollo-core')]);
		}

		if (empty($_FILES['image'])) {
			wp_send_json_error(['message' => __('Nenhuma imagem enviada.', 'apollo-core')]);
		}

		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		$attachment_id = media_handle_upload('image', 0);

		if (is_wp_error($attachment_id)) {
			wp_send_json_error(['message' => $attachment_id->get_error_message()]);
		}

		$url = wp_get_attachment_url($attachment_id);

		wp_send_json_success([
			'url' => $url,
			'id' => $attachment_id
		]);
	}

	/**
	 * AJAX: Login handler
	 */
	public function ajax_login()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['apollo_nonce'] ?? '', 'apollo_login_nonce')) {
			wp_send_json_error([
				'message' => __('Sessão expirada. Recarregue a página.', 'apollo-core')
			]);
		}

		$info = [
			'user_login' => sanitize_user($_POST['log'] ?? ''),
			'user_password' => $_POST['pwd'] ?? '',
			'remember' => isset($_POST['rememberme'])
		];

		// Validate input
		if (empty($info['user_login']) || empty($info['user_password'])) {
			wp_send_json_error([
				'message' => __('Usuário e senha são obrigatórios.', 'apollo-core')
			]);
		}

		// Attempt login
		$user = wp_signon($info, is_ssl());

		if (is_wp_error($user)) {
			wp_send_json_error([
				'message' => $user->get_error_message()
			]);
		}

		// Success
		wp_send_json_success([
			'message' => __('Login realizado com sucesso!', 'apollo-core'),
			'redirect' => isset($_POST['redirect_to']) ? esc_url($_POST['redirect_to']) : home_url(),
			'user' => [
				'id' => $user->ID,
				'name' => $user->display_name,
				'initial' => strtoupper(substr($user->display_name, 0, 1))
			]
		]);
	}

	/**
	 * Register REST routes
	 */
	public function register_rest_routes()
	{
		register_rest_route('apollo/v1', '/navbar/apps', [
			'methods' => 'GET',
			'callback' => [$this, 'rest_get_apps'],
			'permission_callback' => '__return_true'
		]);
	}

	/**
	 * REST: Get apps (public endpoint)
	 */
	public function rest_get_apps()
	{
		$apps = $this->get_apps();

		// Filter to only active apps
		$apps = array_filter($apps, function ($app) {
			return !isset($app['active']) || $app['active'];
		});

		return rest_ensure_response(array_values($apps));
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets()
	{
		// CDN URLs
		$cdn_base = 'https://cdn.apollo.rio.br/';
		$use_cdn = defined('APOLLO_USE_CDN') && APOLLO_USE_CDN === true;

		// Navbar CSS - CDN or local
		$navbar_css_url = $use_cdn
			? $cdn_base . 'apollo-navbar.css'
			: APOLLO_CORE_PLUGIN_URL . 'assets/css/navbar.css';

		wp_enqueue_style(
			'apollo-navbar',
			$navbar_css_url,
			[],
			APOLLO_CORE_VERSION
		);

		// Navbar JS - CDN or local
		$navbar_js_url = $use_cdn
			? $cdn_base . 'apollo-navbar.js'
			: APOLLO_CORE_PLUGIN_URL . 'assets/js/navbar.js';

		wp_enqueue_script(
			'apollo-navbar',
			$navbar_js_url,
			[],
			APOLLO_CORE_VERSION,
			true
		);

		$current_user = wp_get_current_user();

		wp_localize_script('apollo-navbar', 'apolloNavbar', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'restUrl' => rest_url('apollo/v1/'),
			'nonce' => wp_create_nonce('wp_rest'),
			'loginNonce' => wp_create_nonce('apollo_login_nonce'),
			'isLoggedIn' => is_user_logged_in(),
			'userId' => get_current_user_id(),
			'userName' => $current_user->display_name ?? '',
			'userInitial' => $current_user->display_name ? strtoupper(substr($current_user->display_name, 0, 1)) : 'G',
			'logoutUrl' => wp_logout_url(home_url()),
			'lostPasswordUrl' => wp_lostpassword_url(),
			'registerUrl' => wp_registration_url(),
			'profileUrl' => is_user_logged_in() ? get_author_posts_url($current_user->ID) : '',
			'apps' => $this->get_apps(),
			'strings' => [
				'notifications' => __('Notificações', 'apollo-core'),
				'apps' => __('Aplicativos', 'apollo-core'),
				'account' => __('Conta', 'apollo-core'),
				'settings' => __('Configurações', 'apollo-core'),
				'darkMode' => __('Modo Noturno', 'apollo-core'),
				'language' => __('Idioma: Português', 'apollo-core'),
				'support' => __('Suporte', 'apollo-core'),
				'logout' => __('Sair', 'apollo-core'),
				'login' => __('Entrar', 'apollo-core'),
				'welcome' => __('Bem-vindo ao Apollo', 'apollo-core'),
				'enterAccess' => __('Entre para acessar todos os recursos', 'apollo-core'),
				'user' => __('Usuário', 'apollo-core'),
				'code' => __('Código', 'apollo-core'),
				'rememberMe' => __('Permanecer sempre conectado', 'apollo-core'),
				'forgotPassword' => __('Esqueci meu código de acesso', 'apollo-core'),
				'register' => __('Registrar nova conta', 'apollo-core'),
				'seeAll' => __('Ver todas', 'apollo-core'),
				'seeMore' => __('Ver mais', 'apollo-core'),
				'noNotifications' => __('Sem notificações', 'apollo-core'),
				'chat' => __('Chat', 'apollo-core'),
				'more' => __('+ Mais', 'apollo-core'),
				'access' => __('Acesso', 'apollo-core')
			]
		]);
	}

	/**
	 * Render navbar in footer
	 */
	public function render_navbar()
	{
		// Skip admin pages
		if (is_admin()) {
			return;
		}

		// Get navbar template
		$template_path = APOLLO_CORE_PATH . 'templates/partials/navbar.php';

		if (file_exists($template_path)) {
			include $template_path;
		} else {
			// Inline fallback
			$this->render_navbar_inline();
		}
	}

	/**
	 * Render navbar inline (fallback)
	 */
	private function render_navbar_inline()
	{
		$is_logged_in = is_user_logged_in();
		$auth_state = $is_logged_in ? 'logged' : 'guest';
		$current_user = $is_logged_in ? wp_get_current_user() : null;
		$apps = $this->get_apps();
	?>
		<nav class="apollo-navbar" id="apollo-navbar" data-auth="<?php echo esc_attr($auth_state); ?>">
			<div class="clock-pill" id="digital-clock">00:00:00</div>

			<?php if ($is_logged_in): ?>
				<!-- OFFICIAL NOTIFICATION ICON: Broadcast/Radar (NOT bell) - Badge: top:18px left:18px -->
				<button id="btn-notif" class="nav-btn" aria-label="<?php esc_attr_e('Notificações', 'apollo-core'); ?>" aria-expanded="false" aria-controls="menu-notif" data-apollo-notif-trigger title="Notificações - Ícone oficial: Broadcast/Radar">
					<div class="badge" id="notif-badge" data-notif="false"></div>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
						<path d="M6.11629 20.0868C3.62137 18.2684 2 15.3236 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 15.3236 20.3786 18.2684 17.8837 20.0868L16.8692 18.348C18.7729 16.8856 20 14.5861 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 14.5861 5.2271 16.8856 7.1308 18.348L6.11629 20.0868ZM8.14965 16.6018C6.83562 15.5012 6 13.8482 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 13.8482 17.1644 15.5012 15.8503 16.6018L14.8203 14.8365C15.549 14.112 16 13.1087 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 13.1087 8.45105 14.112 9.17965 14.8365L8.14965 16.6018ZM11 13H13L14 22H10L11 13Z" />
					</svg>
				</button>
			<?php endif; ?>

			<button id="btn-apps" class="nav-btn" aria-label="<?php esc_attr_e('Aplicativos', 'apollo-core'); ?>" aria-expanded="false" aria-controls="menu-app">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z" />
				</svg>
			</button>

			<?php if ($is_logged_in): ?>
				<button id="btn-profile" class="nav-btn avatar-btn" aria-label="<?php esc_attr_e('Perfil', 'apollo-core'); ?>" aria-expanded="false" aria-controls="menu-profile">
					<?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
				</button>
			<?php endif; ?>
		</nav>

		<!-- Dropdown containers are rendered by JS -->
		<div id="apollo-dropdowns-container"></div>
<?php
	}

	/**
	 * Maybe hide admin bar on frontend
	 */
	public function maybe_hide_admin_bar($wp_admin_bar)
	{
		// Keep admin bar for now, navbar is additional
	}
}

// Initialize
function apollo_navbar_apps()
{
	return Apollo_Navbar_Apps::get_instance();
}

// Start on plugins_loaded
add_action('plugins_loaded', 'apollo_navbar_apps');
