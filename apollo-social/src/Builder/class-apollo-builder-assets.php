<?php
/**
 * Apollo Builder Assets Admin
 *
 * Admin page for managing stickers and background textures.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Apollo_Builder_Assets_Admin
 */
class Apollo_Builder_Assets_Admin {

	/**
	 * Initialize hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_apollo_builder_save_assets', array( __CLASS__, 'ajax_save_assets' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_menu() {
		add_submenu_page(
			'apollo-social-hub',
			__( 'Builder Assets', 'apollo-social' ),
			__( '🖼️ Builder Assets', 'apollo-social' ),
			'manage_options',
			'apollo-builder-assets',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public static function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'apollo-builder-assets' ) === false ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'apollo-builder-assets-admin',
			plugins_url( 'assets/js/admin-builder-assets.js', dirname( __DIR__ ) ),
			array( 'jquery', 'wp-media-utils' ),
			APOLLO_BUILDER_VERSION,
			true
		);

		wp_localize_script(
			'apollo-builder-assets-admin',
			'apolloBuilderAssetsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_builder_assets_nonce' ),
				'i18n'    => array(
					'selectImage'   => __( 'Select Image', 'apollo-social' ),
					'useImage'      => __( 'Use Image', 'apollo-social' ),
					'removeImage'   => __( 'Remove Image', 'apollo-social' ),
					'confirmRemove' => __( 'Remove this item?', 'apollo-social' ),
					'saving'        => __( 'Saving...', 'apollo-social' ),
					'saved'         => __( 'Saved!', 'apollo-social' ),
					'error'         => __( 'Error saving', 'apollo-social' ),
				),
			)
		);

		// Inline styles
		wp_add_inline_style( 'wp-admin', self::get_admin_styles() );
	}

	/**
	 * Get admin styles
	 */
	private static function get_admin_styles() {
		return '
.apollo-assets-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.apollo-assets-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.apollo-asset-repeater {
    margin: 15px 0;
}
.apollo-asset-row {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    margin-bottom: 10px;
}
.apollo-asset-row .asset-preview {
    width: 60px;
    height: 60px;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.apollo-asset-row .asset-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
.apollo-asset-row .asset-preview .dashicons {
    font-size: 30px;
    color: #999;
}
.apollo-asset-row .asset-fields {
    flex-grow: 1;
}
.apollo-asset-row .asset-fields input {
    width: 100%;
    max-width: 300px;
}
.apollo-asset-row .asset-actions {
    display: flex;
    gap: 5px;
}
.apollo-add-asset {
    margin-top: 10px;
}
.apollo-assets-submit {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
        ';
	}

	/**
	 * Render admin page
	 */
	public static function render_page() {
		$stickers = get_option( 'apollo_builder_stickers', array() );
		$textures = get_option( 'apollo_builder_textures', array() );

		?>
		<div class="wrap">
			<h1>🖼️ <?php _e( 'Apollo Builder Assets', 'apollo-social' ); ?></h1>
			<p><?php _e( 'Manage stickers and background textures available in the Apollo Builder.', 'apollo-social' ); ?></p>
			
			<form id="apollo-builder-assets-form" method="post">
				<?php wp_nonce_field( 'apollo_builder_assets_nonce', 'assets_nonce' ); ?>
				
				<!-- Stickers Section -->
				<div class="apollo-assets-section">
					<h2>
						<span class="dashicons dashicons-smiley"></span>
						<?php _e( 'Stickers', 'apollo-social' ); ?>
					</h2>
					<p class="description">
						<?php _e( 'Stickers are decorative images users can place on their homes. Users can only choose from this library.', 'apollo-social' ); ?>
					</p>
					
					<div class="apollo-asset-repeater" id="stickers-repeater" data-type="sticker">
						<?php if ( ! empty( $stickers ) ) : ?>
							<?php foreach ( $stickers as $index => $sticker ) : ?>
								<?php self::render_asset_row( 'sticker', $index, $sticker ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<button type="button" class="button apollo-add-asset" data-type="sticker">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php _e( 'Add Sticker', 'apollo-social' ); ?>
					</button>
				</div>
				
				<!-- Textures Section -->
				<div class="apollo-assets-section">
					<h2>
						<span class="dashicons dashicons-format-image"></span>
						<?php _e( 'Background Textures', 'apollo-social' ); ?>
					</h2>
					<p class="description">
						<?php _e( 'Background textures users can choose for their home canvas.', 'apollo-social' ); ?>
					</p>
					
					<div class="apollo-asset-repeater" id="textures-repeater" data-type="texture">
						<?php if ( ! empty( $textures ) ) : ?>
							<?php foreach ( $textures as $index => $texture ) : ?>
								<?php self::render_asset_row( 'texture', $index, $texture ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<button type="button" class="button apollo-add-asset" data-type="texture">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php _e( 'Add Texture', 'apollo-social' ); ?>
					</button>
				</div>
				
				<div class="apollo-assets-submit">
					<button type="submit" class="button button-primary button-hero" id="save-assets">
						<span class="dashicons dashicons-saved"></span>
						<?php _e( 'Save All Assets', 'apollo-social' ); ?>
					</button>
					<span class="spinner"></span>
					<span class="save-status"></span>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render single asset row
	 */
	private static function render_asset_row( $type, $index, $data = array() ) {
		$id        = esc_attr( $data['id'] ?? uniqid( $type . '_' ) );
		$label     = esc_attr( $data['label'] ?? '' );
		$image_id  = absint( $data['image_id'] ?? 0 );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

		?>
		<div class="apollo-asset-row" data-index="<?php echo $index; ?>">
			<div class="asset-preview">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="">
				<?php else : ?>
					<span class="dashicons dashicons-format-image"></span>
				<?php endif; ?>
			</div>
			
			<div class="asset-fields">
				<input type="hidden" name="<?php echo $type; ?>s[<?php echo $index; ?>][id]" value="<?php echo $id; ?>" class="asset-id">
				<input type="hidden" name="<?php echo $type; ?>s[<?php echo $index; ?>][image_id]" value="<?php echo $image_id; ?>" class="asset-image-id">
				
				<input type="text" 
						name="<?php echo $type; ?>s[<?php echo $index; ?>][label]" 
						value="<?php echo $label; ?>" 
						placeholder="<?php esc_attr_e( 'Label', 'apollo-social' ); ?>"
						class="regular-text asset-label">
			</div>
			
			<div class="asset-actions">
				<button type="button" class="button select-image">
					<span class="dashicons dashicons-format-image"></span>
					<?php _e( 'Select', 'apollo-social' ); ?>
				</button>
				<button type="button" class="button button-link-delete remove-row">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Save assets
	 */
	public static function ajax_save_assets() {
		check_ajax_referer( 'apollo_builder_assets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-social' ) ), 403 );
		}

		// Process stickers
		$stickers = array();
		if ( isset( $_POST['stickers'] ) && is_array( $_POST['stickers'] ) ) {
			foreach ( $_POST['stickers'] as $s ) {
				if ( empty( $s['image_id'] ) ) {
					continue;
				}

				$stickers[] = array(
					'id'       => sanitize_key( $s['id'] ?? uniqid( 'sticker_' ) ),
					'label'    => sanitize_text_field( $s['label'] ?? '' ),
					'image_id' => absint( $s['image_id'] ),
				);
			}
		}

		// Process textures
		$textures = array();
		if ( isset( $_POST['textures'] ) && is_array( $_POST['textures'] ) ) {
			foreach ( $_POST['textures'] as $t ) {
				if ( empty( $t['image_id'] ) ) {
					continue;
				}

				$textures[] = array(
					'id'       => sanitize_key( $t['id'] ?? uniqid( 'texture_' ) ),
					'label'    => sanitize_text_field( $t['label'] ?? '' ),
					'image_id' => absint( $t['image_id'] ),
				);
			}
		}

		update_option( 'apollo_builder_stickers', $stickers );
		update_option( 'apollo_builder_textures', $textures );

		wp_send_json_success(
			array(
				'message'  => __( 'Assets saved!', 'apollo-social' ),
				'stickers' => count( $stickers ),
				'textures' => count( $textures ),
			)
		);
	}

	/**
	 * Static: Get stickers
	 */
	public static function get_stickers() {
		return get_option( 'apollo_builder_stickers', array() );
	}

	/**
	 * Static: Get textures
	 */
	public static function get_textures() {
		return get_option( 'apollo_builder_textures', array() );
	}
}
