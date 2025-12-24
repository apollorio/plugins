<?php
/**
 * Plugin Name: Apollo Secure Upload
 * Plugin URI: https://apollorio.com/plugins/secure-upload
 * Description: Secure image upload handler for Apollo ecosystem, based on blueimp UploadHandler
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Apollo Team
 * Author URI: https://apollorio.com
 * License: MIT
 * Text Domain: apollo-secure-upload
 *
 * @package Apollo_Secure_Upload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Apollo Core is active
if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>Apollo Secure Upload:</strong> Requires Apollo Core plugin to be active.</p></div>';
		}
	);
	return;
}

// Load audit logging if available
if ( file_exists( plugin_dir_path( __FILE__ ) . '../apollo-core/includes/class-apollo-audit-log.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '../apollo-core/includes/class-apollo-audit-log.php';
}

/**
 * Apollo Secure Upload Handler
 * Adapted from blueimp/jQuery-File-Upload UploadHandler
 */
class Apollo_Secure_Upload_Handler {

	protected $options;
	protected $error_messages = array(
		1                     => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2                     => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3                     => 'The uploaded file was only partially uploaded',
		4                     => 'No file was uploaded',
		6                     => 'Missing a temporary folder',
		7                     => 'Failed to write file to disk',
		8                     => 'A PHP extension stopped the file upload',
		'post_max_size'       => 'The uploaded file exceeds the post_max_size directive in php.ini',
		'max_file_size'       => 'File is too big',
		'min_file_size'       => 'File is too small',
		'accept_file_types'   => 'Filetype not allowed',
		'max_number_of_files' => 'Maximum number of files exceeded',
		'invalid_file_type'   => 'Invalid file type',
		'max_width'           => 'Image exceeds maximum width',
		'min_width'           => 'Image requires a minimum width',
		'max_height'          => 'Image exceeds maximum height',
		'min_height'          => 'Image requires a minimum height',
		'abort'               => 'File upload aborted',
		'image_resize'        => 'Failed to resize image',
		'nonce_invalid'       => 'Security check failed',
		'no_permission'       => 'No permission to upload',
	);

	protected $response = array();

	public function __construct( $options = null ) {
		$upload_dir    = wp_upload_dir();
		$this->options = array(
			'upload_dir'        => $upload_dir['basedir'] . '/apollo-uploads/',
			'upload_url'        => $upload_dir['baseurl'] . '/apollo-uploads/',
			'param_name'        => 'files',
			'max_file_size'     => 10 * 1024 * 1024, // 10MB
			'min_file_size'     => 1,
			'accept_file_types' => '/\.(jpe?g|png|webp)$/i', // Only images, no SVG for security
			'image_versions'    => array(),
		);
		if ( $options ) {
			$this->options = array_merge( $this->options, $options );
		}
	}

			/**
			 * Handle the upload request.
			 *
			 * @return array Response array.
			 */
	public function handle_request() {
		// Check nonce.
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'apollo_secure_upload' ) ) {
			return $this->generate_response( array( 'error' => $this->error_messages['nonce_invalid'] ) );
		}

		// Check permission.
		if ( ! current_user_can( 'upload_files' ) && ! current_user_can( 'apollo_upload_media' ) ) {
			return $this->generate_response( array( 'error' => $this->error_messages['no_permission'] ) );
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		switch ( $method ) {
			case 'POST':
				return $this->post();
			default:
				return $this->generate_response( array( 'error' => 'Method not allowed' ) );
		}
	}

			/**
			 * Validate the uploaded file.
			 *
			 * @param array $file File array.
			 * @return string|true Error key or true if valid.
			 */
	protected function validate_file( $file ) {
		// Check upload error first
		if ( isset( $file['error'] ) && $file['error'] !== 0 ) {
			return isset( $this->error_messages[ $file['error'] ] ) ? $file['error'] : 'abort';
		}

		// Use wp_check_filetype_and_ext
		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( $checked['type'] === false || ! preg_match( $this->options['accept_file_types'], $file['name'] ) ) {
			return 'accept_file_types';
		}

		// Check size
		if ( $file['size'] > $this->options['max_file_size'] ) {
			return 'max_file_size';
		}

		// Check if image
		if ( getimagesize( $file['tmp_name'] ) === false ) {
			return 'invalid_file_type';
		}

		return true;
	}

	/**
	 * Handle file upload.
	 *
	 * @param array $file File array.
	 * @return array Response array.
	 */
	protected function handle_file_upload( $file ) {
		$validation = $this->validate_file( $file );
		if ( $validation !== true ) {
			return array( 'error' => $this->error_messages[ $validation ] );
		}

		// Generate unique name
		$name = wp_unique_filename( $this->options['upload_dir'], sanitize_file_name( $file['name'] ) );

		$file_path = $this->options['upload_dir'] . $name;
		$file_url  = $this->options['upload_url'] . $name;

		// Ensure dir exists
		wp_mkdir_p( $this->options['upload_dir'] );

		if ( move_uploaded_file( $file['tmp_name'], $file_path ) ) {
			// Generate hash
			$hash = hash_file( 'sha256', $file_path );

			// Store metadata
			$metadata = array(
				'name'        => $name,
				'hash'        => $hash,
				'size'        => $file['size'],
				'user_id'     => get_current_user_id(),
				'source'      => 'apollo_secure_upload',
				'uploaded_at' => current_time( 'mysql' ),
			);
			update_option( 'apollo_upload_' . $hash, $metadata );

			// Log successful upload
			if ( function_exists( 'Apollo_Audit_Log' ) ) {
				Apollo_Audit_Log::log_event(
					'file_upload',
					array(
						'message'     => 'File uploaded via Apollo Secure Upload',
						'target_type' => 'file',
						'target_id'   => $hash,
						'context'     => array(
							'filename' => $name,
							'size'     => $file['size'],
							'hash'     => $hash,
							'url'      => $file_url,
						),
						'severity'    => 'info',
					)
				);
			}

			return array(
				'name' => $name,
				'size' => $file['size'],
				'url'  => $file_url,
				'hash' => $hash,
			);
		} else {
			return array( 'error' => 'Failed to save file' );
		}
	}

			/**
			 * Handle POST upload.
			 *
			 * @return array Response array.
			 */
	protected function post() {
		$files = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_request().
		if ( ! empty( $_FILES[ $this->options['param_name'] ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES is validated and sanitized in handle_file_upload().
			$uploads = $_FILES[ $this->options['param_name'] ];
			if ( is_array( $uploads['tmp_name'] ) ) {
				foreach ( $uploads['tmp_name'] as $index => $tmp_name ) {
					$file    = array(
						'tmp_name' => $tmp_name, // tmp_name is a system path, should not be sanitized.
						'name'     => isset( $uploads['name'][ $index ] ) ? sanitize_file_name( $uploads['name'][ $index ] ) : '',
						'size'     => isset( $uploads['size'][ $index ] ) ? intval( $uploads['size'][ $index ] ) : 0,
						'error'    => isset( $uploads['error'][ $index ] ) ? intval( $uploads['error'][ $index ] ) : 0,
					);
					$files[] = $this->handle_file_upload( $file );
				}
			} else {
				$file    = array(
					'tmp_name' => $uploads['tmp_name'],
					'name'     => sanitize_file_name( $uploads['name'] ),
					'size'     => intval( $uploads['size'] ),
					'error'    => intval( $uploads['error'] ),
				);
				$files[] = $this->handle_file_upload( $file );
			}
		}

		return $this->generate_response( array( $this->options['param_name'] => $files ) );
	}

	/**
	 * Generate and send JSON response.
	 *
	 * @param array $content Response content.
	 * @return array Response array.
	 */
	protected function generate_response( $content ) {
		$this->response = $content;
		if ( function_exists( 'wp_send_json' ) ) {
			wp_send_json( $content );
		} else {
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			echo json_encode( $content );
			exit;
		}
		return $content;
	}
}

/**
 * Function for Apollo forms.
 *
 * @return array Response array.
 */
function apollo_secure_upload_handle_request() {
	$handler = new Apollo_Secure_Upload_Handler();
	return $handler->handle_request();
}

// Hook into WordPress - only for authenticated users
add_action( 'wp_ajax_apollo_secure_upload', 'apollo_secure_upload_handle_request' );

/**
 * Register admin page for upload logs.
 */
function apollo_secure_upload_admin_page() {
	// Changed to always use Apollo Cabin as parent menu to organize better
	add_submenu_page(
		'apollo-cabin',
		__( 'Apollo Secure Upload', 'apollo-secure-upload' ),
		__( '📤 Secure Upload', 'apollo-secure-upload' ),
		'manage_apollo_uploads',
		'apollo-secure-upload-logs',
		'apollo_secure_upload_logs_page'
	);
}
add_action( 'admin_menu', 'apollo_secure_upload_admin_page' );

/**
 * Render upload logs page.
 */
function apollo_secure_upload_logs_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo Secure Upload Logs', 'apollo-secure-upload' ); ?></h1>
		<p><?php esc_html_e( 'Recent uploads:', 'apollo-secure-upload' ); ?></p>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'File', 'apollo-secure-upload' ); ?></th>
					<th><?php esc_html_e( 'User', 'apollo-secure-upload' ); ?></th>
					<th><?php esc_html_e( 'Date', 'apollo-secure-upload' ); ?></th>
					<th><?php esc_html_e( 'Hash', 'apollo-secure-upload' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- For admin log display only.
				$logs = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT 50",
						'apollo_upload_%'
					)
				);
				if ( is_array( $logs ) ) {
					foreach ( $logs as $log ) {
						if ( ! is_object( $log ) || ! isset( $log->option_value ) ) {
							continue;
						}
						$data = maybe_unserialize( $log->option_value );
						if ( is_array( $data ) ) {
							$user = get_user_by( 'id', $data['user_id'] ?? 0 );
							echo '<tr>';
							echo '<td>' . esc_html( $data['name'] ?? 'N/A' ) . '</td>';
							echo '<td>' . esc_html( $user ? $user->display_name : 'N/A' ) . '</td>';
							echo '<td>' . esc_html( $data['uploaded_at'] ?? 'N/A' ) . '</td>';
							echo '<td>' . esc_html( $data['hash'] ?? 'N/A' ) . '</td>';
							echo '</tr>';
						}
					}
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
}
