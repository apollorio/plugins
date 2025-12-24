<?php

namespace Apollo\Modules\Builder;

use Apollo\Modules\Builder\Admin\BuilderAdminPage;
use Apollo\Modules\Builder\Http\BuilderRestController;
use Apollo\Modules\Builder\Widgets\StickyNoteWidget;

class BuilderServiceProvider {

	private LayoutRepository $repository;
	private Renderer $renderer;
	private ?BuilderAdminPage $adminPage           = null;
	private ?BuilderRestController $restController = null;

	public function __construct() {
		$this->repository = new LayoutRepository();
		$this->renderer   = new Renderer( $this->repository );
	}

	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 25 );
	}

	public function bootstrap(): void {
		$this->maybeRegisterAdminPage();
		$this->maybeRegisterRest();
		$this->registerShortcodes();
		$this->registerWidgets();
		$this->registerFrontendAssets();
	}

	private function dependenciesSatisfied(): bool {
		return class_exists( 'SiteOrigin_Panels_Renderer' );
	}

	private function maybeRegisterAdminPage(): void {
		$this->adminPage = new BuilderAdminPage( $this->repository );
		$this->adminPage->register();

		// Legacy dependency check removed as we are moving to Apollo Builder (ShadCN)
		// if (!$this->dependenciesSatisfied()) {
		// add_action('admin_notices', [$this, 'dependencyNotice']);
		// }
	}

	private function maybeRegisterRest(): void {
		$this->restController = new BuilderRestController( $this->repository );
		$this->restController->register();
	}

	private function registerShortcodes(): void {
		add_shortcode(
			'apollo_profile',
			function ( $atts = array() ) {
				$atts = shortcode_atts(
					array(
						'user_id' => get_current_user_id(),
					),
					$atts
				);

				$userId = absint( $atts['user_id'] );

				wp_enqueue_style( 'apollo-uni-css' );
				wp_enqueue_style( 'apollo-social-builder' );

				return $this->renderer->renderForUser( $userId );
			}
		);
	}

	private function registerFrontendAssets(): void {
		add_action(
			'init',
			function () {
				$pluginFile = APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php';

				if ( ! wp_style_is( 'apollo-uni-css', 'registered' ) ) {
					wp_register_style(
						'apollo-uni-css',
						'https://assets.apollo.rio.br/uni.css',
						array(),
						'2025-11'
					);
				}

				wp_register_style(
					'apollo-social-builder',
					plugins_url( 'assets/css/builder.css', $pluginFile ),
					array( 'apollo-uni-css' ),
					APOLLO_SOCIAL_VERSION
				);

				// Register interactjs - load in head for availability.
				wp_register_script(
					'interactjs',
					'https://cdn.jsdelivr.net/npm/interactjs@1.10.17/dist/interact.min.js',
					array(),
					'1.10.17',
					false // Head, not footer.
				);

				// Apollo Builder main script (for frontend profile display).
				wp_register_script(
					'apollo-social-builder-runtime',
					plugins_url( 'assets/js/builder.js', $pluginFile ),
					array( 'jquery' ),
					APOLLO_SOCIAL_VERSION,
					true
				);

				// Apollo Builder Assets module (backgrounds & stickers).
				// Depends on main builder script AND interactjs.
				wp_register_script(
					'apollo-builder-assets',
					plugins_url( 'assets/js/apollo-builder-assets.js', $pluginFile ),
					array( 'jquery', 'interactjs' ),
					APOLLO_SOCIAL_VERSION,
					true
				);

				// Localize REST nonce for the assets module.
				wp_localize_script(
					'apollo-builder-assets',
					'apolloBuilderAssetsConfig',
					array(
						'restNonce' => wp_create_nonce( 'wp_rest' ),
						'restUrl'   => rest_url( 'apollo-social/v1/builder' ),
					)
				);
			}
		);
	}

	private function registerWidgets(): void {
		add_action(
			'widgets_init',
			function () {
				register_widget( StickyNoteWidget::class );
			}
		);

		add_filter(
			'siteorigin_panels_widgets',
			function ( $widgets ) {
				$widgets['apollo_sticky_note'] = array(
					'title'        => __( 'Apollo Sticky Note', 'apollo-social' ),
					'description'  => __( 'Nota adesiva estilo Habbo com Tailwind/Shadcn.', 'apollo-social' ),
					'class'        => StickyNoteWidget::class,
					'is_so_widget' => false,
				);

				return $widgets;
			}
		);
	}

	public function dependencyNotice(): void {
		if ( class_exists( 'SiteOrigin_Panels_Renderer' ) ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'Apollo Social Builder requer o plugin SiteOrigin Page Builder ativo.', 'apollo-social' ); ?>
			</p>
		</div>
		<?php
	}
}
