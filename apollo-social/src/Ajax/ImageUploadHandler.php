<?php
/**
 * Apollo Social – AJAX Image Upload Handler for Quill Editor
 *
 * This file provides a secure AJAX endpoint for uploading images from the
 * Quill rich text editor. It integrates with WordPress Media Library for
 * consistent file management and uses WordPress's built-in security features.
 *
 * Security measures implemented:
 *   1. Nonce verification (CSRF protection)
 *   2. User capability check (upload_files capability)
 *   3. File type validation (whitelist of allowed MIME types)
 *   4. File size validation (configurable limit)
 *   5. WordPress handles file sanitization and storage
 *
 * Flow:
 *   1. Validate nonce and user permissions
 *   2. Validate uploaded file (type, size, errors)
 *   3. Use wp_handle_upload() for secure file handling
 *   4. Create Media Library attachment
 *   5. Return attachment URL to the editor
 *
 * @package    ApolloSocial
 * @subpackage Ajax
 * @since      1.1.0
 * @author     Apollo Team
 */

namespace Apollo\Ajax;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ImageUploadHandler
 *
 * Handles AJAX image uploads from the Quill editor.
 * Registered on both wp_ajax_ hooks for logged-in users.
 *
 * Usage:
 *   The JavaScript client sends a POST request to admin-ajax.php with:
 *     - action: 'apollo_upload_editor_image'
 *     - nonce: Security nonce from apolloQuillConfig
 *     - image: The file (multipart/form-data)
 *
 * Response (JSON):
 *   Success: { success: true, data: { url: 'https://...', id: 123 } }
 *   Failure: { success: false, data: { message: 'Error description' } }
 *
 * @since 1.1.0
 */
class ImageUploadHandler {

    /**
     * AJAX action name.
     * Must match the 'uploadAction' in apolloQuillConfig.
     *
     * @var string
     */
    const ACTION = 'apollo_upload_editor_image';

    /**
     * Nonce action name for verification.
     *
     * @var string
     */
    const NONCE_ACTION = 'apollo_editor_image_upload';

    /**
     * Maximum file size in bytes.
     * Default: 5 MB. Can be filtered via 'apollo_editor_max_upload_size'.
     *
     * @var int
     */
    private $max_file_size;

    /**
     * Allowed MIME types for upload.
     * Can be filtered via 'apollo_editor_allowed_mime_types'.
     *
     * @var array
     */
    private $allowed_mime_types;

    /**
     * Constructor.
     * Sets up configuration and registers AJAX hooks.
     */
    public function __construct() {
        // Configuration with filterable defaults
        $this->max_file_size = apply_filters(
            'apollo_editor_max_upload_size',
            5 * 1024 * 1024 // 5 MB default
        );

        $this->allowed_mime_types = apply_filters(
            'apollo_editor_allowed_mime_types',
            array(
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            )
        );

        // Register AJAX handlers
        // wp_ajax_{action} - for logged-in users
        // wp_ajax_nopriv_{action} - for non-logged-in users (we don't register this for security)
        add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_upload' ) );

        // Note: We intentionally don't register nopriv handler.
        // Only logged-in users with upload_files capability can upload images.
    }

    /**
     * Handle the AJAX upload request.
     *
     * This is the main entry point for image uploads from the Quill editor.
     * It performs security checks, validates the file, and saves it to the
     * Media Library.
     *
     * @return void Outputs JSON response and exits.
     */
    public function handle_upload() {
        // Step 1: Verify nonce for CSRF protection
        // The nonce was generated in the enqueue script and passed to JavaScript.
        // If the nonce is invalid or expired, we reject the request.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], self::NONCE_ACTION ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Sessão expirada. Recarregue a página e tente novamente.', 'apollo-social' ),
                    'code'    => 'invalid_nonce',
                ),
                403
            );
            return;
        }

        // Step 2: Check user capability
        // Only users with 'upload_files' capability can upload images.
        // This is typically Contributor role and above in WordPress.
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você não tem permissão para enviar arquivos.', 'apollo-social' ),
                    'code'    => 'permission_denied',
                ),
                403
            );
            return;
        }

        // Step 3: Check if file was uploaded
        if ( empty( $_FILES['image'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Nenhum arquivo foi enviado.', 'apollo-social' ),
                    'code'    => 'no_file',
                ),
                400
            );
            return;
        }

        $file = $_FILES['image'];

        // Step 4: Check for upload errors
        // PHP sets error codes for common upload problems.
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            $error_message = $this->get_upload_error_message( $file['error'] );
            wp_send_json_error(
                array(
                    'message' => $error_message,
                    'code'    => 'upload_error',
                ),
                400
            );
            return;
        }

        // Step 5: Validate file size
        // Even though we validate on the client, malicious users could bypass it.
        if ( $file['size'] > $this->max_file_size ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        /* translators: %s: maximum file size */
                        __( 'Arquivo muito grande. O tamanho máximo permitido é %s.', 'apollo-social' ),
                        size_format( $this->max_file_size )
                    ),
                    'code'    => 'file_too_large',
                ),
                400
            );
            return;
        }

        // Step 6: Validate MIME type
        // We use wp_check_filetype_and_ext() for robust validation.
        // This checks both the file extension and actual file contents.
        $file_info = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
        $mime_type = $file_info['type'];

        // If WordPress couldn't determine the type, try finfo as fallback
        if ( empty( $mime_type ) && function_exists( 'finfo_open' ) ) {
            $finfo     = finfo_open( FILEINFO_MIME_TYPE );
            $mime_type = finfo_file( $finfo, $file['tmp_name'] );
            finfo_close( $finfo );
        }

        if ( empty( $mime_type ) || ! in_array( $mime_type, $this->allowed_mime_types, true ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.', 'apollo-social' ),
                    'code'    => 'invalid_mime_type',
                ),
                400
            );
            return;
        }

        // Step 7: Handle the upload using WordPress functions
        // wp_handle_upload() moves the file to the uploads directory and
        // performs additional security checks (like checking for PHP code).
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload_overrides = array(
            'test_form' => false, // We're not using a form, just AJAX
            'mimes'     => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png',
                'gif'          => 'image/gif',
                'webp'         => 'image/webp',
            ),
        );

        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        if ( isset( $uploaded_file['error'] ) ) {
            wp_send_json_error(
                array(
                    'message' => $uploaded_file['error'],
                    'code'    => 'wp_upload_error',
                ),
                500
            );
            return;
        }

        // Step 8: Create Media Library attachment
        // This adds the image to WordPress Media Library so it can be managed,
        // and generates thumbnails/sizes automatically.
        $attachment_data = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_file_name( $file['name'] ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment_data, $uploaded_file['file'] );

        if ( is_wp_error( $attachment_id ) ) {
            // Clean up the uploaded file since attachment creation failed
            wp_delete_file( $uploaded_file['file'] );

            wp_send_json_error(
                array(
                    'message' => __( 'Erro ao criar anexo na biblioteca de mídia.', 'apollo-social' ),
                    'code'    => 'attachment_error',
                ),
                500
            );
            return;
        }

        // Step 9: Generate attachment metadata (thumbnails, sizes, etc.)
        $attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
        wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

        // Step 10: Return success response with image URL
        // We return both the URL and attachment ID in case client needs it.
        wp_send_json_success(
            array(
                'url'        => $uploaded_file['url'],
                'id'         => $attachment_id,
                'filename'   => basename( $uploaded_file['file'] ),
                'filesize'   => size_format( $file['size'] ),
                'dimensions' => $this->get_image_dimensions( $uploaded_file['file'] ),
            )
        );
    }

    /**
     * Get a human-readable error message for PHP upload errors.
     *
     * @param int $error_code PHP upload error code from $_FILES['file']['error'].
     * @return string Localized error message.
     */
    private function get_upload_error_message( $error_code ) {
        $messages = array(
            UPLOAD_ERR_INI_SIZE   => __( 'O arquivo excede o tamanho máximo permitido pelo servidor.', 'apollo-social' ),
            UPLOAD_ERR_FORM_SIZE  => __( 'O arquivo excede o tamanho máximo especificado no formulário.', 'apollo-social' ),
            UPLOAD_ERR_PARTIAL    => __( 'O arquivo foi enviado apenas parcialmente.', 'apollo-social' ),
            UPLOAD_ERR_NO_FILE    => __( 'Nenhum arquivo foi enviado.', 'apollo-social' ),
            UPLOAD_ERR_NO_TMP_DIR => __( 'Erro no servidor: pasta temporária não encontrada.', 'apollo-social' ),
            UPLOAD_ERR_CANT_WRITE => __( 'Erro no servidor: falha ao gravar arquivo.', 'apollo-social' ),
            UPLOAD_ERR_EXTENSION  => __( 'O envio foi interrompido por uma extensão do servidor.', 'apollo-social' ),
        );

        return isset( $messages[ $error_code ] )
            ? $messages[ $error_code ]
            : __( 'Erro desconhecido ao enviar o arquivo.', 'apollo-social' );
    }

    /**
     * Get image dimensions.
     *
     * @param string $file_path Path to the image file.
     * @return array|null Array with 'width' and 'height', or null if unavailable.
     */
    private function get_image_dimensions( $file_path ) {
        $size = getimagesize( $file_path );
        if ( $size ) {
            return array(
                'width'  => $size[0],
                'height' => $size[1],
            );
        }
        return null;
    }
}

// Initialize the handler
new ImageUploadHandler();
