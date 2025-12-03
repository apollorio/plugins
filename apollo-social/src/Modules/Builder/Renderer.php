<?php
/**
 * Renderer for Apollo Builder.
 *
 * Renders user layouts including backgrounds, widgets, and stickers.
 *
 * @package Apollo\Modules\Builder
 * @since   1.0.0
 */

namespace Apollo\Modules\Builder;

use Apollo\Modules\Builder\Assets\BackgroundRegistry;
use Apollo\Modules\Builder\Assets\StickerRegistry;
use SiteOrigin_Panels_Renderer;

/**
 * Responsible for rendering layouts stored in user meta.
 */
class Renderer {

	/**
	 * Layout repository instance.
	 *
	 * @var LayoutRepository
	 */
	private LayoutRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param LayoutRepository $repository Layout repository instance.
	 */
	public function __construct( LayoutRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Render layout for a specific user.
	 *
	 * @param int   $user_id User ID to render layout for.
	 * @param array $args    Optional render arguments.
	 * @return string Rendered HTML.
	 */
	public function renderForUser( int $user_id, array $args = array() ): string {
		$layout = $this->repository->getLayout( $user_id );

		// Enqueue background scripts if needed.
		$this->enqueueBackgroundAssets( $layout );

		// Build the canvas with layers.
		$output = $this->renderCanvas( $user_id, $layout, $args );

		return $output;
	}

	/**
	 * Render the complete canvas with all layers.
	 *
	 * @param int   $user_id User ID.
	 * @param array $layout  Layout data.
	 * @param array $args    Render arguments.
	 * @return string Rendered HTML.
	 */
	private function renderCanvas( int $user_id, array $layout, array $args ): string {
		$background_style = $this->getBackgroundStyle( $layout );
		$background_class = $this->getBackgroundClass( $layout );

		ob_start();
		?>
		<div class="apollo-canvas <?php echo esc_attr( $background_class ); ?>"
			style="<?php echo esc_attr( $background_style ); ?>"
			data-user-id="<?php echo esc_attr( (string) $user_id ); ?>">

			<?php
			// Background layer (for animated/complex backgrounds).
			?>
			<div class="apollo-canvas__background-layer"></div>

			<?php
			// Widgets layer.
			?>
			<div class="apollo-canvas__widgets-layer">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render methods
				echo $this->renderWidgetsLayer( $user_id, $layout, $args );
				?>
			</div>

			<?php
			// Stickers layer (on top).
			?>
			<div class="apollo-canvas__stickers-layer">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in renderStickersLayer
				echo $this->renderStickersLayer( $layout );
				?>
			</div>

		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Get inline background style for the canvas.
	 *
	 * @param array $layout Layout data.
	 * @return string CSS style string.
	 */
	private function getBackgroundStyle( array $layout ): string {
		$background_id = $layout['background']['id'] ?? LayoutRepository::DEFAULT_BACKGROUND;
		$background    = BackgroundRegistry::get_by_id( $background_id );

		if ( null === $background ) {
			return 'background-color: #ffffff;';
		}

		$style = '';

		// Apply CSS value (color, gradient, or pattern).
		$css_value = $background['css_value'] ?? '';
		if ( ! empty( $css_value ) ) {
			// Determine if it's a gradient, image, or solid color.
			if ( str_starts_with( $css_value, 'linear-gradient' ) || str_starts_with( $css_value, 'radial-gradient' ) ) {
				$style .= 'background: ' . $css_value . ';';
			} elseif ( str_starts_with( $css_value, 'url(' ) ) {
				$style .= 'background-image: ' . $css_value . '; background-size: cover; background-position: center;';
			} elseif ( str_starts_with( $css_value, '#' ) || str_starts_with( $css_value, 'rgb' ) ) {
				$style .= 'background-color: ' . $css_value . ';';
			} else {
				// Complex pattern (multiple backgrounds).
				$style .= 'background: ' . $css_value . ';';
			}
		}

		// Apply background size if specified (for patterns).
		if ( ! empty( $background['css_size'] ) ) {
			$style .= ' background-size: ' . $background['css_size'] . ';';
		}

		return $style;
	}

	/**
	 * Get background CSS class for the canvas.
	 *
	 * @param array $layout Layout data.
	 * @return string CSS class string.
	 */
	private function getBackgroundClass( array $layout ): string {
		$background_id = $layout['background']['id'] ?? LayoutRepository::DEFAULT_BACKGROUND;

		return 'apollo-canvas--bg-' . sanitize_html_class( $background_id );
	}

	/**
	 * Enqueue background-specific scripts and styles.
	 *
	 * @param array $layout Layout data.
	 * @return void
	 */
	private function enqueueBackgroundAssets( array $layout ): void {
		$background_id = $layout['background']['id'] ?? '';
		$background    = BackgroundRegistry::get_by_id( $background_id );

		if ( null === $background ) {
			return;
		}

		// Check for script hook (custom JS/CSS for animated backgrounds).
		$script_hook = $background['script_hook'] ?? '';
		if ( ! empty( $script_hook ) ) {
			/**
			 * Action to enqueue custom background assets.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $background    Background data.
			 * @param string $background_id Background ID.
			 */
			do_action( 'apollo_builder_enqueue_background', $background, $background_id );
			do_action( "apollo_builder_enqueue_background_{$script_hook}", $background );
		}
	}

	/**
	 * Render the widgets layer.
	 *
	 * @param int   $user_id User ID.
	 * @param array $layout  Layout data.
	 * @param array $args    Render arguments.
	 * @return string Rendered HTML.
	 */
	private function renderWidgetsLayer( int $user_id, array $layout, array $args ): string {
		// Check if layout has widgets.
		if ( empty( $layout['widgets'] ) ) {
			return $this->emptyState();
		}

		// If using SiteOrigin grids, use their renderer.
		if ( ! empty( $layout['grids'] ) && ! empty( $layout['grid_cells'] ) ) {
			if ( class_exists( SiteOrigin_Panels_Renderer::class ) ) {
				$cache_key = sprintf( 'apollo-user-%d', $user_id );
				$renderer  = SiteOrigin_Panels_Renderer::single();
				return $renderer->render( $cache_key, $layout, $args );
			}
			return $this->dependencyMissingNotice();
		}

		// Otherwise, render absolute positioned widgets.
		return $this->renderAbsoluteLayout( $layout );
	}

	/**
	 * Render the stickers layer.
	 *
	 * @param array $layout Layout data.
	 * @return string Rendered HTML.
	 */
	private function renderStickersLayer( array $layout ): string {
		$stickers = $layout['stickers'] ?? array();

		if ( empty( $stickers ) ) {
			return '';
		}

		$output = '';

		foreach ( $stickers as $sticker ) {
			$asset_id = $sticker['asset'] ?? '';
			$asset    = StickerRegistry::get_by_id( $asset_id );

			// Skip if asset no longer exists (legacy behavior: keep in data, don't render).
			if ( null === $asset ) {
				continue;
			}

			$x        = (int) ( $sticker['x'] ?? 0 );
			$y        = (int) ( $sticker['y'] ?? 0 );
			$scale    = (float) ( $sticker['scale'] ?? 1.0 );
			$rotation = (int) ( $sticker['rotation'] ?? 0 );
			$z_index  = (int) ( $sticker['z_index'] ?? $asset['z_index_hint'] ?? 50 );

			$transform = "translate({$x}px, {$y}px)";
			if ( 1.0 !== $scale ) {
				$transform .= " scale({$scale})";
			}
			if ( 0 !== $rotation ) {
				$transform .= " rotate({$rotation}deg)";
			}

			$style = sprintf(
				'transform: %s; z-index: %d;',
				$transform,
				$z_index
			);

			$output .= sprintf(
				'<div class="apollo-sticker" data-sticker-id="%s" data-instance-id="%s" style="%s">' .
				'<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" />' .
				'</div>',
				esc_attr( $asset_id ),
				esc_attr( $sticker['id'] ?? '' ),
				esc_attr( $style ),
				esc_url( $asset['image_url'] ?? '' ),
				esc_attr( $asset['label'] ?? '' ),
				(int) ( $asset['width'] ?? 64 ),
				(int) ( $asset['height'] ?? 64 )
			);
		}//end foreach

		return $output;
	}

	/**
	 * Render empty state message.
	 *
	 * @return string Rendered HTML.
	 */
	public function emptyState(): string {
		ob_start();
		?>
		<div class="apollo-builder-empty">
			<div class="apollo-card">
				<h3 class="apollo-card__title">
					<?php esc_html_e( 'Nenhum bloco configurado', 'apollo-social' ); ?>
				</h3>
				<p class="apollo-card__description">
					<?php esc_html_e( 'Comece adicionando um widget no construtor visual e arraste-o para a cena.', 'apollo-social' ); ?>
				</p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render dependency missing notice.
	 *
	 * @return string Rendered HTML.
	 */
	private function dependencyMissingNotice(): string {
		return '<div class="apollo-alert apollo-alert--warning">' .
			esc_html__( 'SiteOrigin Page Builder não está ativo. Ative o plugin para renderizar perfis.', 'apollo-social' ) .
			'</div>';
	}

	/**
	 * Render absolute positioned layout (legacy support).
	 *
	 * @param array $layout Layout data.
	 * @return string Rendered HTML.
	 */
	private function renderAbsoluteLayout( array $layout ): string {
		$output = '<div class="apollo-profile-canvas" style="position:relative;min-height:420px;">';

		foreach ( $layout['widgets'] as $widget ) {
			if ( empty( $widget['id_base'] ) ) {
				continue;
			}

			$position = $widget['position'] ?? array();
			$style    = sprintf(
				'left:%spx;top:%spx;width:%spx;height:%spx;position:absolute;z-index:%s;',
				isset( $position['x'] ) ? (float) $position['x'] : 0,
				isset( $position['y'] ) ? (float) $position['y'] : 0,
				isset( $position['width'] ) ? (float) $position['width'] : 260,
				isset( $position['height'] ) ? (float) $position['height'] : 180,
				isset( $position['z'] ) ? (int) $position['z'] : 10
			);

			$output .= '<div class="apollo-widget-instance" style="' . esc_attr( $style ) . '">';
			$output .= $this->renderWidgetManually( $widget['id_base'], $widget['settings'] ?? array() );
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a widget manually (fallback when SiteOrigin not available).
	 *
	 * @param string $id_base  Widget ID base.
	 * @param array  $settings Widget settings.
	 * @return string Rendered HTML.
	 */
	private function renderWidgetManually( string $id_base, array $settings ): string {
		switch ( $id_base ) {
			case 'apollo_sticky_note':
				$title   = $settings['title'] ?? __( 'Nota', 'apollo-social' );
				$content = $settings['content'] ?? '';
				$color   = $settings['color'] ?? '#fef3c7';

				return sprintf(
					'<div class="apollo-sticky-note" style="background:%s;">' .
					'<header class="apollo-sticky-note__header">' .
					'<span class="apollo-sticky-note__pin"></span>' .
					'<h3 class="apollo-sticky-note__title">%s</h3>' .
					'</header>' .
					'<div class="apollo-sticky-note__content">%s</div>' .
					'</div>',
					esc_attr( $color ),
					esc_html( $title ),
					wpautop( wp_kses_post( $content ) )
				);

			default:
				ob_start();
				the_widget(
					$id_base,
					$settings,
					array(
						'before_widget' => '',
						'after_widget'  => '',
					)
				);
				return (string) ob_get_clean();
		}//end switch
	}
}

