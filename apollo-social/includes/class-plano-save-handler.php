<?php
/**
 * Plano Canvas Save Handler
 *
 * Handles saving canvas images as WordPress attachments
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Plano_Save_Handler
 */
class Apollo_Plano_Save_Handler {

	/**
	 * Initialize save handler
	 */
	public static function init() {
		add_action( 'wp_ajax_apollo_save_canvas', array( __CLASS__, 'handle_save_canvas' ) );
		add_action( 'wp_ajax_nopriv_apollo_save_canvas', array( __CLASS__, 'handle_save_canvas' ) );
	}

	/**
	 * Handle canvas save via AJAX
	 */
	public static function handle_save_canvas() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wp_rest' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Nonce inválido', 'apollo-social' ) ) );
			return;
		}

		// Check user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Você precisa estar logado', 'apollo-social' ) ) );
			return;
		}

		// Get data URL
		if ( ! isset( $_POST['data_url'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Data URL não fornecida', 'apollo-social' ) ) );
			return;
		}

		$data_url = sanitize_text_field( wp_unslash( $_POST['data_url'] ) );

		// Validate data URL format
		if ( ! preg_match( '/^data:image\/(png|jpeg|jpg);base64,/', $data_url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Formato de imagem inválido', 'apollo-social' ) ) );
			return;
		}

		// Extract image data
		preg_match( '/data:image\/(png|jpeg|jpg);base64,(.+)/', $data_url, $matches );
		if ( empty( $matches[2] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Erro ao processar imagem', 'apollo-social' ) ) );
			return;
		}

		$image_data = base64_decode( $matches[2] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$image_type = $matches[1];

		// Validate image data
		if ( $image_data === false ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Erro ao decodificar imagem', 'apollo-social' ) ) );
			return;
		}

		// Create filename
		$filename = 'apollo-canvas-' . time() . '.' . $image_type;

		// Upload file
		$upload = wp_upload_bits( $filename, null, $image_data );

		if ( $upload['error'] ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Erro ao fazer upload: ', 'apollo-social' ) . $upload['error'] ) );
			return;
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => 'image/' . $image_type,
			'post_title'     => sanitize_file_name( 'Apollo Canvas ' . current_time( 'mysql' ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );

		if ( is_wp_error( $attach_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Erro ao criar attachment', 'apollo-social' ) ) );
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Get attachment URL
		$attachment_url = wp_get_attachment_url( $attach_id );

		// Return success with attachment data
		wp_send_json_success(
			array(
				'attachment_id'  => $attach_id,
				'attachment_url' => $attachment_url,
				'message'        => esc_html__( 'Canvas salvo com sucesso!', 'apollo-social' ),
			)
		);
	}
}

// Initialize
Apollo_Plano_Save_Handler::init();
