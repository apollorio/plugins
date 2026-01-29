<?php
/**
 * Apollo Builder Custom Styles Admin
 *
 * Admin interface for creating and managing custom widget/card styles.
 * Allows administrators to define reusable style templates with HTML, CSS, and JS.
 *
 * @package Apollo_Social
 * @since 1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Builder_Custom_Styles
 *
 * Tooltip: Admin page for managing custom card/widget style templates.
 */
class Apollo_Builder_Custom_Styles {

	/**
	 * Option key for storing custom styles
	 */
	public const OPTION_KEY = 'apollo_builder_custom_styles';

	/**
	 * Initialize hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_ajax_apollo_builder_save_custom_style', array( __CLASS__, 'ajax_save_style' ) );
		add_action( 'wp_ajax_apollo_builder_delete_custom_style', array( __CLASS__, 'ajax_delete_style' ) );
		add_action( 'wp_head', array( __CLASS__, 'inject_custom_styles_css' ), 100 );
		add_action( 'wp_footer', array( __CLASS__, 'inject_custom_styles_js' ), 100 );
	}

	/**
	 * Add menu page
	 */
	public static function add_menu_page() {
		add_submenu_page(
			'apollo-social-hub',
			__( 'Style Editor', 'apollo-social' ),
			__( '🎨 Style Editor', 'apollo-social' ),
			'manage_options',
			'apollo-style-editor',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting(
			'apollo_builder_styles',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_styles' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Get all custom styles
	 *
	 * @return array
	 */
	public static function get_styles() {
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * Get a single style by ID
	 *
	 * @param string $style_id Style ID.
	 * @return array|null
	 */
	public static function get_style( $style_id ) {
		$styles = self::get_styles();
		return $styles[ $style_id ] ?? null;
	}

	/**
	 * Save a style
	 *
	 * @param array $style_data Style data.
	 * @return bool Success.
	 */
	public static function save_style( $style_data ) {
		$styles   = self::get_styles();
		$style_id = sanitize_key( $style_data['id'] ?? '' );

		if ( empty( $style_id ) ) {
			$style_id = 'style_' . wp_generate_uuid4();
		}

		$styles[ $style_id ]               = self::sanitize_single_style( $style_data );
		$styles[ $style_id ]['id']         = $style_id;
		$styles[ $style_id ]['updated_at'] = current_time( 'mysql' );

		return update_option( self::OPTION_KEY, $styles );
	}

	/**
	 * Delete a style
	 *
	 * @param string $style_id Style ID.
	 * @return bool Success.
	 */
	public static function delete_style( $style_id ) {
		$styles = self::get_styles();

		if ( ! isset( $styles[ $style_id ] ) ) {
			return false;
		}

		unset( $styles[ $style_id ] );
		return update_option( self::OPTION_KEY, $styles );
	}

	/**
	 * Sanitize all styles
	 *
	 * @param array $styles Styles array.
	 * @return array Sanitized styles.
	 */
	public static function sanitize_styles( $styles ) {
		if ( ! is_array( $styles ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $styles as $id => $style ) {
			$sanitized[ sanitize_key( $id ) ] = self::sanitize_single_style( $style );
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single style
	 *
	 * @param array $style Style data.
	 * @return array Sanitized style.
	 */
	private static function sanitize_single_style( $style ) {
		return array(
			'id'          => sanitize_key( $style['id'] ?? '' ),
			'name'        => sanitize_text_field( $style['name'] ?? __( 'Untitled Style', 'apollo-social' ) ),
			'description' => sanitize_textarea_field( $style['description'] ?? '' ),
			'category'    => sanitize_key( $style['category'] ?? 'card' ),
			'html'        => self::sanitize_html_template( $style['html'] ?? '' ),
			'css'         => self::sanitize_css( $style['css'] ?? '' ),
			'js'          => self::sanitize_js( $style['js'] ?? '' ),
			'active'      => ! empty( $style['active'] ),
			'created_at'  => sanitize_text_field( $style['created_at'] ?? current_time( 'mysql' ) ),
			'updated_at'  => sanitize_text_field( $style['updated_at'] ?? current_time( 'mysql' ) ),
		);
	}

	/**
	 * Sanitize HTML template
	 *
	 * @param string $html Raw HTML.
	 * @return string Sanitized HTML.
	 */
	private static function sanitize_html_template( $html ) {
		// Allow safe HTML tags for templates.
		$allowed_tags = array(
			'div'    => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
				'style'  => true,
			),
			'span'   => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
				'style'  => true,
			),
			'p'      => array(
				'class' => true,
				'style' => true,
			),
			'h1'     => array(
				'class' => true,
				'style' => true,
			),
			'h2'     => array(
				'class' => true,
				'style' => true,
			),
			'h3'     => array(
				'class' => true,
				'style' => true,
			),
			'h4'     => array(
				'class' => true,
				'style' => true,
			),
			'a'      => array(
				'href'   => true,
				'class'  => true,
				'target' => true,
				'rel'    => true,
			),
			'img'    => array(
				'src'   => true,
				'alt'   => true,
				'class' => true,
				'style' => true,
			),
			'ul'     => array( 'class' => true ),
			'ol'     => array( 'class' => true ),
			'li'     => array( 'class' => true ),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
			'hr'     => array( 'class' => true ),
			'button' => array(
				'class'  => true,
				'type'   => true,
				'data-*' => true,
			),
			'input'  => array(
				'type'        => true,
				'class'       => true,
				'placeholder' => true,
				'name'        => true,
			),
			'label'  => array(
				'for'   => true,
				'class' => true,
			),
			'form'   => array(
				'class'  => true,
				'data-*' => true,
			),
			'svg'    => array(
				'class'   => true,
				'width'   => true,
				'height'  => true,
				'viewBox' => true,
				'fill'    => true,
			),
			'path'   => array(
				'd'      => true,
				'fill'   => true,
				'stroke' => true,
			),
		);

		// Limit length.
		$html = substr( $html, 0, 10000 );

		return wp_kses( $html, $allowed_tags );
	}

	/**
	 * Sanitize CSS
	 *
	 * @param string $css Raw CSS.
	 * @return string Sanitized CSS.
	 */
	private static function sanitize_css( $css ) {
		// Remove potentially dangerous content.
		$css = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/url\s*\(\s*["\']?\s*data:/i', 'url(', $css );
		$css = preg_replace( '/@import/i', '', $css );

		// Strip HTML tags.
		$css = wp_strip_all_tags( $css );

		// Limit length.
		return substr( $css, 0, 50000 );
	}

	/**
	 * Sanitize JS
	 *
	 * @param string $js Raw JS.
	 * @return string Sanitized JS.
	 */
	private static function sanitize_js( $js ) {
		// Remove dangerous patterns.
		$js = preg_replace( '/eval\s*\(/i', '', $js );
		$js = preg_replace( '/Function\s*\(/i', '', $js );
		$js = preg_replace( '/document\.write/i', '', $js );
		$js = preg_replace( '/innerHTML\s*=/i', 'textContent =', $js );

		// Strip HTML tags.
		$js = wp_strip_all_tags( $js );

		// Limit length.
		return substr( $js, 0, 20000 );
	}

	/**
	 * Inject custom styles CSS on frontend
	 */
	public static function inject_custom_styles_css() {
		if ( ! is_singular( 'apollo_home' ) ) {
			return;
		}

		$styles = self::get_styles();
		$css    = '';

		foreach ( $styles as $style ) {
			if ( ! empty( $style['active'] ) && ! empty( $style['css'] ) ) {
				$css .= "/* Custom Style: {$style['name']} */\n";
				$css .= $style['css'] . "\n";
			}
		}

		if ( $css ) {
			echo '<style id="apollo-custom-styles">' . "\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitized in sanitize_css.
			echo $css;
			echo '</style>' . "\n";
		}
	}

	/**
	 * Inject custom styles JS on frontend
	 */
	public static function inject_custom_styles_js() {
		if ( ! is_singular( 'apollo_home' ) ) {
			return;
		}

		$styles = self::get_styles();
		$js     = '';

		foreach ( $styles as $style ) {
			if ( ! empty( $style['active'] ) && ! empty( $style['js'] ) ) {
				$js .= "/* Custom Style JS: {$style['name']} */\n";
				$js .= "(function() {\n";
				$js .= $style['js'] . "\n";
				$js .= "})();\n";
			}
		}

		if ( $js ) {
			echo '<script id="apollo-custom-styles-js">' . "\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS sanitized in sanitize_js.
			echo $js;
			echo '</script>' . "\n";
		}
	}

	/**
	 * AJAX: Save custom style
	 */
	public static function ajax_save_style() {
		check_ajax_referer( 'apollo-style-editor-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-social' ) ), 403 );
		}

		$style_data = array(
			'id'          => sanitize_key( $_POST['style_id'] ?? '' ),
			'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
			'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
			'category'    => sanitize_key( $_POST['category'] ?? 'card' ),
			'html'        => wp_unslash( $_POST['html'] ?? '' ),
			'css'         => wp_unslash( $_POST['css'] ?? '' ),
			'js'          => wp_unslash( $_POST['js'] ?? '' ),
			'active'      => ! empty( $_POST['active'] ),
		);

		$success = self::save_style( $style_data );

		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => __( 'Style saved!', 'apollo-social' ),
					'styles'  => self::get_styles(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save style.', 'apollo-social' ) ), 500 );
		}
	}

	/**
	 * AJAX: Delete custom style
	 */
	public static function ajax_delete_style() {
		check_ajax_referer( 'apollo-style-editor-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apollo-social' ) ), 403 );
		}

		$style_id = sanitize_key( $_POST['style_id'] ?? '' );

		if ( empty( $style_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid style ID.', 'apollo-social' ) ), 400 );
		}

		$success = self::delete_style( $style_id );

		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => __( 'Style deleted!', 'apollo-social' ),
					'styles'  => self::get_styles(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete style.', 'apollo-social' ) ), 500 );
		}
	}

	/**
	 * Render admin page
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'apollo-social' ) );
		}

		$styles = self::get_styles();
		$nonce  = wp_create_nonce( 'apollo-style-editor-nonce' );
		?>
		<div class="wrap apollo-style-editor">
			<h1>🎨 <?php esc_html_e( 'Apollo Style Editor', 'apollo-social' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Create and manage custom card/widget styles for Apollo Builder. Define reusable HTML templates, CSS styles, and JavaScript behaviors.', 'apollo-social' ); ?>
			</p>

			<div class="style-editor-layout">
				<!-- Styles List -->
				<div class="styles-sidebar">
					<h2><?php esc_html_e( 'Custom Styles', 'apollo-social' ); ?></h2>
					<button type="button" class="button button-primary" id="add-new-style">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Add New Style', 'apollo-social' ); ?>
					</button>

					<ul class="styles-list" id="styles-list">
						<?php if ( empty( $styles ) ) : ?>
							<li class="no-styles"><?php esc_html_e( 'No custom styles yet.', 'apollo-social' ); ?></li>
						<?php else : ?>
							<?php foreach ( $styles as $style ) : ?>
								<li class="style-item" data-style-id="<?php echo esc_attr( $style['id'] ); ?>">
									<span class="style-status <?php echo $style['active'] ? 'active' : 'inactive'; ?>"></span>
									<span class="style-name"><?php echo esc_html( $style['name'] ); ?></span>
									<span class="style-category"><?php echo esc_html( $style['category'] ); ?></span>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>

				<!-- Editor Panel -->
				<div class="style-editor-panel">
					<form id="style-editor-form">
						<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<input type="hidden" name="style_id" id="style_id" value="">

						<div class="editor-header">
							<h2 id="editor-title"><?php esc_html_e( 'Select or create a style', 'apollo-social' ); ?></h2>
							<div class="editor-actions">
								<button type="button" class="button" id="preview-style">
									<span class="dashicons dashicons-visibility"></span>
									<?php esc_html_e( 'Preview', 'apollo-social' ); ?>
								</button>
								<button type="submit" class="button button-primary" id="save-style" disabled>
									<span class="dashicons dashicons-saved"></span>
									<?php esc_html_e( 'Save Style', 'apollo-social' ); ?>
								</button>
								<button type="button" class="button button-link-delete" id="delete-style" disabled>
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete', 'apollo-social' ); ?>
								</button>
							</div>
						</div>

						<div class="editor-fields">
							<div class="field-row">
								<div class="field-group">
									<label for="style_name"><?php esc_html_e( 'Style Name', 'apollo-social' ); ?></label>
									<input type="text" id="style_name" name="name" placeholder="<?php esc_attr_e( 'My Custom Card', 'apollo-social' ); ?>" class="regular-text">
								</div>
								<div class="field-group">
									<label for="style_category"><?php esc_html_e( 'Category', 'apollo-social' ); ?></label>
									<select id="style_category" name="category">
										<option value="card"><?php esc_html_e( 'Card', 'apollo-social' ); ?></option>
										<option value="widget"><?php esc_html_e( 'Widget', 'apollo-social' ); ?></option>
										<option value="header"><?php esc_html_e( 'Header', 'apollo-social' ); ?></option>
										<option value="footer"><?php esc_html_e( 'Footer', 'apollo-social' ); ?></option>
										<option value="button"><?php esc_html_e( 'Button', 'apollo-social' ); ?></option>
										<option value="other"><?php esc_html_e( 'Other', 'apollo-social' ); ?></option>
									</select>
								</div>
								<div class="field-group">
									<label for="style_active">
										<input type="checkbox" id="style_active" name="active" value="1">
										<?php esc_html_e( 'Active', 'apollo-social' ); ?>
									</label>
								</div>
							</div>

							<div class="field-group">
								<label for="style_description"><?php esc_html_e( 'Description', 'apollo-social' ); ?></label>
								<textarea id="style_description" name="description" rows="2" placeholder="<?php esc_attr_e( 'Brief description of this style...', 'apollo-social' ); ?>"></textarea>
							</div>

							<!-- Code Editors -->
							<div class="code-editors">
								<div class="code-tabs">
									<button type="button" class="tab-button active" data-tab="html">
										<span class="dashicons dashicons-editor-code"></span> HTML
									</button>
									<button type="button" class="tab-button" data-tab="css">
										<span class="dashicons dashicons-admin-appearance"></span> CSS
									</button>
									<button type="button" class="tab-button" data-tab="js">
										<span class="dashicons dashicons-media-code"></span> JavaScript
									</button>
								</div>

								<div class="tab-content" id="tab-html">
									<p class="tab-hint">
										<?php esc_html_e( 'Define the HTML structure for your card/widget. Use placeholders like {{title}}, {{content}}, {{image}}.', 'apollo-social' ); ?>
									</p>
									<textarea id="style_html" name="html" rows="15" class="code-editor" placeholder="<div class='my-custom-card'>
	<div class='card-header'>{{title}}</div>
	<div class='card-body'>{{content}}</div>
</div>"></textarea>
								</div>

								<div class="tab-content" id="tab-css" style="display: none;">
									<p class="tab-hint">
										<?php esc_html_e( 'Write CSS styles for your card. Prefix selectors with .apollo-home-container to scope styles.', 'apollo-social' ); ?>
									</p>
									<textarea id="style_css" name="css" rows="15" class="code-editor" placeholder=".apollo-home-container .my-custom-card {
	background: #fff;
	border-radius: 8px;
	box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	padding: 16px;
}
.my-custom-card .card-header {
	font-weight: bold;
	margin-bottom: 10px;
}"></textarea>
								</div>

								<div class="tab-content" id="tab-js" style="display: none;">
									<p class="tab-hint">
										<?php esc_html_e( 'Add JavaScript for interactive behaviors. Code runs in an IIFE scope. Use jQuery or vanilla JS.', 'apollo-social' ); ?>
									</p>
									<textarea id="style_js" name="js" rows="15" class="code-editor" placeholder="// Example: Add hover effect
document.querySelectorAll('.my-custom-card').forEach(function(card) {
	card.addEventListener('mouseenter', function() {
		this.style.transform = 'scale(1.02)';
	});
	card.addEventListener('mouseleave', function() {
		this.style.transform = 'scale(1)';
	});
});"></textarea>
								</div>
							</div>
						</div>
					</form>

					<!-- Live Preview -->
					<div class="preview-panel" id="preview-panel" style="display: none;">
						<h3><?php esc_html_e( 'Live Preview', 'apollo-social' ); ?></h3>
						<div class="preview-frame" id="preview-frame"></div>
					</div>
				</div>
			</div>

			<!-- Styles Data for JS -->
			<script id="styles-data" type="application/json"><?php echo wp_json_encode( $styles ); ?></script>
		</div>

		<style>
			.apollo-style-editor {
				max-width: 1600px;
			}
			.style-editor-layout {
				display: flex;
				gap: 20px;
				margin-top: 20px;
			}
			.styles-sidebar {
				width: 280px;
				flex-shrink: 0;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 15px;
			}
			.styles-sidebar h2 {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 1px solid #ddd;
			}
			.styles-sidebar #add-new-style {
				width: 100%;
				margin-bottom: 15px;
			}
			.styles-list {
				list-style: none;
				margin: 0;
				padding: 0;
				max-height: 500px;
				overflow-y: auto;
			}
			.styles-list .style-item {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 10px;
				cursor: pointer;
				border-radius: 4px;
				border: 1px solid transparent;
			}
			.styles-list .style-item:hover,
			.styles-list .style-item.selected {
				background: #f0f6fc;
				border-color: #2271b1;
			}
			.style-status {
				width: 10px;
				height: 10px;
				border-radius: 50%;
				flex-shrink: 0;
			}
			.style-status.active {
				background: #00a32a;
			}
			.style-status.inactive {
				background: #ddd;
			}
			.style-name {
				flex: 1;
				font-weight: 500;
			}
			.style-category {
				font-size: 11px;
				color: #666;
				background: #f0f0f1;
				padding: 2px 6px;
				border-radius: 3px;
			}
			.style-editor-panel {
				flex: 1;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 20px;
			}
			.editor-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 20px;
				padding-bottom: 15px;
				border-bottom: 1px solid #ddd;
			}
			.editor-header h2 {
				margin: 0;
			}
			.editor-actions {
				display: flex;
				gap: 10px;
			}
			.field-row {
				display: flex;
				gap: 15px;
				margin-bottom: 15px;
			}
			.field-group {
				flex: 1;
			}
			.field-group label {
				display: block;
				font-weight: 600;
				margin-bottom: 5px;
			}
			.field-group input[type="text"],
			.field-group select,
			.field-group textarea {
				width: 100%;
			}
			.code-tabs {
				display: flex;
				gap: 5px;
				margin-bottom: 10px;
				border-bottom: 2px solid #ddd;
				padding-bottom: 10px;
			}
			.tab-button {
				padding: 8px 16px;
				background: #f0f0f1;
				border: 1px solid #c3c4c7;
				border-radius: 4px 4px 0 0;
				cursor: pointer;
				display: flex;
				align-items: center;
				gap: 5px;
			}
			.tab-button.active {
				background: #2271b1;
				color: #fff;
				border-color: #2271b1;
			}
			.tab-content {
				margin-top: 10px;
			}
			.tab-hint {
				font-size: 12px;
				color: #666;
				margin-bottom: 10px;
				padding: 8px 12px;
				background: #f9f9f9;
				border-left: 3px solid #2271b1;
			}
			.code-editor {
				font-family: Consolas, Monaco, 'Courier New', monospace;
				font-size: 13px;
				line-height: 1.5;
				padding: 10px;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				background: #f9f9f9;
				resize: vertical;
			}
			.preview-panel {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #ddd;
			}
			.preview-frame {
				background: #f0f0f0;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 20px;
				min-height: 200px;
			}
			.no-styles {
				color: #666;
				font-style: italic;
				padding: 15px 10px;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var stylesData = JSON.parse($('#styles-data').text() || '{}');
			var currentStyleId = null;

			// Tab switching
			$('.tab-button').on('click', function() {
				$('.tab-button').removeClass('active');
				$(this).addClass('active');
				$('.tab-content').hide();
				$('#tab-' + $(this).data('tab')).show();
			});

			// Add new style
			$('#add-new-style').on('click', function() {
				currentStyleId = null;
				$('#style_id').val('');
				$('#style_name').val('');
				$('#style_description').val('');
				$('#style_category').val('card');
				$('#style_active').prop('checked', true);
				$('#style_html').val('');
				$('#style_css').val('');
				$('#style_js').val('');
				$('#editor-title').text('<?php echo esc_js( __( 'New Style', 'apollo-social' ) ); ?>');
				$('#save-style, #delete-style').prop('disabled', false);
				$('.style-item').removeClass('selected');
			});

			// Select style
			$(document).on('click', '.style-item', function() {
				var styleId = $(this).data('style-id');
				var style = stylesData[styleId];

				if (!style) return;

				currentStyleId = styleId;
				$('.style-item').removeClass('selected');
				$(this).addClass('selected');

				$('#style_id').val(styleId);
				$('#style_name').val(style.name || '');
				$('#style_description').val(style.description || '');
				$('#style_category').val(style.category || 'card');
				$('#style_active').prop('checked', !!style.active);
				$('#style_html').val(style.html || '');
				$('#style_css').val(style.css || '');
				$('#style_js').val(style.js || '');
				$('#editor-title').text(style.name || 'Edit Style');
				$('#save-style, #delete-style').prop('disabled', false);
			});

			// Save style
			$('#style-editor-form').on('submit', function(e) {
				e.preventDefault();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_builder_save_custom_style',
						nonce: $('[name="nonce"]').val(),
						style_id: $('#style_id').val(),
						name: $('#style_name').val(),
						description: $('#style_description').val(),
						category: $('#style_category').val(),
						active: $('#style_active').is(':checked') ? 1 : 0,
						html: $('#style_html').val(),
						css: $('#style_css').val(),
						js: $('#style_js').val()
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							stylesData = response.data.styles;
							refreshStylesList();
						} else {
							alert(response.data.message || 'Error');
						}
					}
				});
			});

			// Delete style
			$('#delete-style').on('click', function() {
				if (!currentStyleId) return;
				if (!confirm('<?php echo esc_js( __( 'Delete this style?', 'apollo-social' ) ); ?>')) return;

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_builder_delete_custom_style',
						nonce: $('[name="nonce"]').val(),
						style_id: currentStyleId
					},
					success: function(response) {
						if (response.success) {
							stylesData = response.data.styles;
							refreshStylesList();
							$('#add-new-style').click();
						} else {
							alert(response.data.message || 'Error');
						}
					}
				});
			});

			// Preview
			$('#preview-style').on('click', function() {
				var html = $('#style_html').val();
				var css = $('#style_css').val();

				$('#preview-panel').toggle();
				$('#preview-frame').html(
					'<style>' + css + '</style>' +
					'<div class="apollo-home-container">' + html + '</div>'
				);
			});

			function refreshStylesList() {
				var $list = $('#styles-list');
				$list.empty();

				if (Object.keys(stylesData).length === 0) {
					$list.append('<li class="no-styles"><?php echo esc_js( __( 'No custom styles yet.', 'apollo-social' ) ); ?></li>');
					return;
				}

				for (var id in stylesData) {
					var s = stylesData[id];
					$list.append(
						'<li class="style-item" data-style-id="' + id + '">' +
						'<span class="style-status ' + (s.active ? 'active' : 'inactive') + '"></span>' +
						'<span class="style-name">' + (s.name || 'Untitled') + '</span>' +
						'<span class="style-category">' + (s.category || 'card') + '</span>' +
						'</li>'
					);
				}
			}
		});
		</script>
		<?php
	}
}

// Initialize.
add_action( 'init', array( 'Apollo_Builder_Custom_Styles', 'init' ) );
