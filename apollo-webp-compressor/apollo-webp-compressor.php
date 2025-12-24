<?php
/**
 * Plugin Name: Apollo WebP Compressor
 * Plugin URI: https://apollorio.com/plugins/webp-compressor
 * Description: Automatic WebP compression for Apollo images, based on webp-express
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Apollo Team
 * Author URI: https://apollorio.com
 * License: GPL2
 * Text Domain: apollo-webp-compressor
 *
 * @package Apollo_WebP_Compressor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Apollo Core is active
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p><strong>Apollo WebP Compressor:</strong> Requires Apollo Core plugin to be active.</p></div>';
	} );
	return;
}

// Load audit logging if available
if ( file_exists( plugin_dir_path( __FILE__ ) . '../apollo-core/includes/class-apollo-audit-log.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '../apollo-core/includes/class-apollo-audit-log.php';
}

define( 'APOLLO_WEBP_PLUGIN', __FILE__ );
define( 'APOLLO_WEBP_PLUGIN_DIR', __DIR__ );

// Include the conversion helper.
require_once APOLLO_WEBP_PLUGIN_DIR . '/includes/ConvertHelper.php';

// Activation hook.
register_activation_hook( __FILE__, 'apollo_webp_activate' );

/**
 * Activation callback.
 */
function apollo_webp_activate() {
	// Create necessary directories if needed.
	$upload_dir = wp_upload_dir();
	$webp_dir   = $upload_dir['basedir'] . '/apollo-webp';
	if ( ! file_exists( $webp_dir ) ) {
		wp_mkdir_p( $webp_dir );
	}
}

// Hook into image upload to convert.
add_filter( 'wp_generate_attachment_metadata', 'apollo_webp_convert_on_upload', 10, 2 );

/**
 * Convert image to WebP on upload.
 *
 * @param array $metadata Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array Metadata.
 */
function apollo_webp_convert_on_upload( $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( apollo_webp_is_image( $file ) ) {
		apollo_webp_convert_image( $file );
	}

	return $metadata;
}

/**
 * Check if file is a supported image type.
 *
 * @param string $file File path.
 * @return bool True if image.
 */
function apollo_webp_is_image( $file ) {
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

	return in_array( $ext, [ 'jpg', 'jpeg', 'png' ], true );
}

/**
 * Convert image to WebP format.
 *
 * @param string $source Source image path.
 * @return bool True on success.
 */
function apollo_webp_convert_image( $source ) {
	$upload_dir  = wp_upload_dir();
	$rel_path    = str_replace( $upload_dir['basedir'] . '/', '', $source );
	$destination = $upload_dir['basedir'] . '/apollo-webp/' . $rel_path . '.webp';

	// Ensure directory exists.
	$dest_dir = dirname( $destination );
	if ( ! file_exists( $dest_dir ) ) {
		wp_mkdir_p( $dest_dir );
	}

	// Convert using helper.
	$result = ApolloWebPConvertHelper::convert( $source, $destination );

	// Log conversion.
	if ( $result && function_exists( 'Apollo_Audit_Log' ) ) {
		$original_size = filesize( $source );
		$webp_size     = file_exists( $destination ) ? filesize( $destination ) : 0;
		$savings       = $original_size > 0 ? round( ( ( $original_size - $webp_size ) / $original_size ) * 100, 1 ) : 0;

		Apollo_Audit_Log::log_event(
			'image_compression',
			[
				'message'     => 'Image converted to WebP format',
				'target_type' => 'file',
				'target_id'   => basename( $source ),
				'context'     => [
					'source'          => $source,
					'destination'     => $destination,
					'original_size'   => $original_size,
					'webp_size'       => $webp_size,
					'savings_percent' => $savings,
				],
				'severity'    => 'info',
			]
		);
	}

	return $result;
}

// Admin settings.
/**
 * Register admin page.
 */
function apollo_webp_admin_page() {
	// Changed to always use Apollo Cabin as parent menu to organize better
	add_submenu_page(
		'apollo-cabin',
		__( 'Apollo WebP Compressor', 'apollo-webp-compressor' ),
		__( '🖼️ WebP Compressor', 'apollo-webp-compressor' ),
		'manage_apollo_compression',
		'apollo-webp-compressor',
		'apollo_webp_settings_page'
	);
}
add_action( 'admin_menu', 'apollo_webp_admin_page' );

/**
 * Render settings page.
 */
function apollo_webp_settings_page() {
	// Check capability.
	if ( ! current_user_can( 'manage_apollo_compression' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'apollo-webp-compressor' ) );
	}

	// Handle form submission.
	if ( isset( $_POST['submit'] ) && check_admin_referer( 'apollo_webp_settings', 'apollo_webp_nonce' ) ) {
		$quality = isset( $_POST['quality'] ) ? absint( $_POST['quality'] ) : 75;
		// Validate quality range.
		if ( $quality >= 50 && $quality <= 95 ) {
			update_option( 'apollo_webp_quality', $quality );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'apollo-webp-compressor' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid quality value. Must be between 50 and 95.', 'apollo-webp-compressor' ) . '</p></div>';
		}
	}

	$quality = get_option( 'apollo_webp_quality', 75 );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo WebP Compressor Settings', 'apollo-webp-compressor' ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'apollo_webp_settings', 'apollo_webp_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Quality', 'apollo-webp-compressor' ); ?></th>
					<td>
						<input type="number" name="quality" value="<?php echo esc_attr( $quality ); ?>" min="50" max="95" />
						<p class="description"><?php esc_html_e( 'WebP quality (50-95)', 'apollo-webp-compressor' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
