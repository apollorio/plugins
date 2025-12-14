<?php

/**
 * Local WordPress DMS Adapter
 *
 * DMS adapter using WordPress posts and attachments for document storage.
 * Stores documents as custom post types with versions as post revisions.
 *
 * @package Apollo\Modules\Documents\Adapters
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable Generic.Files.LowercasedFilename.NotFound
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents\Adapters;

use Apollo\Modules\Documents\Contracts\DmsAdapterInterface;
use WP_Error;
use WP_Post;
use WP_Query;

/**
 * Class LocalWordPressDmsAdapter
 *
 * Implements DMS storage using WordPress native features:
 * - Documents as 'apollo_document' custom post type
 * - Versions as post revisions
 * - PDFs as media attachments
 * - Metadata as post meta
 */
class LocalWordPressDmsAdapter implements DmsAdapterInterface
{
    /**
     * Custom post type for documents.
     *
     * @var string
     */
    public const POST_TYPE = 'apollo_document';

    /**
     * Meta key prefix.
     *
     * @var string
     */
    public const META_PREFIX = '_apollo_dms_';

    /**
     * Get the unique identifier for this adapter.
     *
     * @return string
     */
    public function get_identifier(): string
    {
        return 'local-wordpress';
    }

    /**
     * Get the human-readable name for this adapter.
     *
     * @return string
     */
    public function get_name(): string
    {
        return __('WordPress Local Storage', 'apollo-social');
    }

    /**
     * Check if the adapter is available.
     *
     * @return bool
     */
    public function is_available(): bool
    {
        return post_type_exists(self::POST_TYPE);
    }

    /**
     * Get adapter capabilities.
     *
     * @return array
     */
    public function get_capabilities(): array
    {
        return [
            'versioning'     => true,
            'pdf_generation' => class_exists('Dompdf\Dompdf') || class_exists('TCPDF'),
            'signed_urls'    => false,
            'soft_delete'    => true,
            'metadata'       => true,
            'search'         => true,
            'attachments'    => true,
        ];
    }

    /**
     * Store a new document.
     *
     * @param string $content      Document content.
     * @param array  $metadata     Document metadata.
     * @param string $content_type MIME type.
     *
     * @return array|WP_Error
     */
    public function store(string $content, array $metadata, string $content_type = 'text/html')
    {
        $title       = $metadata['title']     ?? __('Untitled Document', 'apollo-social');
        $author_id   = $metadata['author_id'] ?? get_current_user_id();
        $doc_type    = $metadata['type']      ?? 'documento';
        $status      = $metadata['status']    ?? 'draft';
        $post_status = $this->map_doc_status_to_post_status($status);

        // Generate unique file ID.
        $file_id = $this->generate_file_id();

        // Create the document post.
        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => $post_status,
            'post_type'    => self::POST_TYPE,
            'post_author'  => $author_id,
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Store metadata.
        update_post_meta($post_id, self::META_PREFIX . 'file_id', $file_id);
        update_post_meta($post_id, self::META_PREFIX . 'type', $doc_type);
        update_post_meta($post_id, self::META_PREFIX . 'status', $status);
        update_post_meta($post_id, self::META_PREFIX . 'version', 1);
        update_post_meta($post_id, self::META_PREFIX . 'content_type', $content_type);
        update_post_meta($post_id, self::META_PREFIX . 'created_at', current_time('mysql'));

        // Store additional metadata.
        if (isset($metadata['category'])) {
            update_post_meta($post_id, self::META_PREFIX . 'category', sanitize_text_field($metadata['category']));
        }

        if (isset($metadata['tags']) && is_array($metadata['tags'])) {
            update_post_meta($post_id, self::META_PREFIX . 'tags', array_map('sanitize_text_field', $metadata['tags']));
        }

        // Fire action for extensions.
        do_action('apollo_dms_document_stored', $post_id, $file_id, $metadata);

        return [
            'id'           => $post_id,
            'file_id'      => $file_id,
            'title'        => $title,
            'type'         => $doc_type,
            'status'       => $status,
            'version'      => 1,
            'content_type' => $content_type,
            'created_at'   => current_time('mysql'),
            'author_id'    => $author_id,
        ];
    }

    /**
     * Retrieve a document.
     *
     * @param string $document_id Document ID or file_id.
     *
     * @return array|WP_Error
     */
    public function retrieve(string $document_id)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        return $this->format_document($post);
    }

    /**
     * Update an existing document.
     *
     * @param string $document_id Document ID.
     * @param string $content     Updated content.
     * @param array  $metadata    Updated metadata.
     *
     * @return array|WP_Error
     */
    public function update(string $document_id, string $content, array $metadata)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        // Update post.
        $post_data = [
            'ID'           => $post->ID,
            'post_content' => $content,
        ];

        if (isset($metadata['title'])) {
            $post_data['post_title'] = sanitize_text_field($metadata['title']);
        }

        if (isset($metadata['status'])) {
            $post_data['post_status'] = $this->map_doc_status_to_post_status($metadata['status']);
            update_post_meta($post->ID, self::META_PREFIX . 'status', $metadata['status']);
        }

        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Increment version.
        $current_version = (int) get_post_meta($post->ID, self::META_PREFIX . 'version', true);
        update_post_meta($post->ID, self::META_PREFIX . 'version', $current_version + 1);
        update_post_meta($post->ID, self::META_PREFIX . 'updated_at', current_time('mysql'));

        // Update other metadata.
        $meta_fields = [ 'category', 'type' ];
        foreach ($meta_fields as $field) {
            if (isset($metadata[ $field ])) {
                update_post_meta($post->ID, self::META_PREFIX . $field, sanitize_text_field($metadata[ $field ]));
            }
        }

        // Fire action.
        do_action('apollo_dms_document_updated', $post->ID, $metadata);

        return $this->format_document(get_post($post->ID));
    }

    /**
     * Delete a document.
     *
     * @param string $document_id Document ID.
     * @param bool   $permanent   Permanent delete flag.
     *
     * @return bool|WP_Error
     */
    public function delete(string $document_id, bool $permanent = false)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        if ($permanent) {
            $result = wp_delete_post($post->ID, true);
        } else {
            $result = wp_trash_post($post->ID);
        }

        if (! $result) {
            return new WP_Error(
                'delete_failed',
                __('Failed to delete document.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        do_action('apollo_dms_document_deleted', $post->ID, $permanent);

        return true;
    }

    /**
     * List documents.
     *
     * @param array $args Query arguments.
     *
     * @return array|WP_Error
     */
    public function list_documents(array $args = [])
    {
        $defaults = [
            'status'     => 'any',
            'type'       => '',
            'author'     => 0,
            'per_page'   => 20,
            'page'       => 1,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'search'     => '',
            'date_after' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $query_args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['page'],
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
        ];

        // Status filter.
        if ('any' !== $args['status']) {
            $query_args['post_status'] = $this->map_doc_status_to_post_status($args['status']);
        } else {
            $query_args['post_status'] = [ 'publish', 'draft', 'pending', 'private' ];
        }

        // Author filter.
        if ($args['author']) {
            $query_args['author'] = absint($args['author']);
        }

        // Type filter via meta query.
        if ($args['type']) {
            $query_args['meta_query'][] = [
                'key'   => self::META_PREFIX . 'type',
                'value' => $args['type'],
            ];
        }

        // Search.
        if ($args['search']) {
            $query_args['s'] = $args['search'];
        }

        // Date filter.
        if ($args['date_after']) {
            $query_args['date_query'][] = [
                'after' => $args['date_after'],
            ];
        }

        $query = new WP_Query($query_args);

        $documents = [];
        foreach ($query->posts as $post) {
            $documents[] = $this->format_document($post);
        }

        return [
            'documents'   => $documents,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $args['page'],
            'per_page'    => $args['per_page'],
        ];
    }

    /**
     * Get download URL.
     *
     * @param string $document_id Document ID.
     * @param int    $expiry      Expiry time (not used for local).
     *
     * @return string|WP_Error
     */
    public function get_download_url(string $document_id, int $expiry = 3600)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        // Check for PDF attachment.
        $pdf_id = get_post_meta($post->ID, self::META_PREFIX . 'pdf_attachment_id', true);

        if ($pdf_id) {
            return wp_get_attachment_url($pdf_id);
        }

        // Return permalink for HTML view.
        return get_permalink($post->ID);
    }

    /**
     * Generate PDF from document.
     *
     * @param string $document_id Document ID.
     * @param array  $options     PDF options.
     *
     * @return string|WP_Error
     */
    public function generate_pdf(string $document_id, array $options = [])
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        // Check for existing PDF.
        $existing_pdf = get_post_meta($post->ID, self::META_PREFIX . 'pdf_attachment_id', true);
        if ($existing_pdf && empty($options['regenerate'])) {
            $url = wp_get_attachment_url($existing_pdf);
            if ($url) {
                return $url;
            }
        }

        // Try to generate PDF.
        $pdf_result = $this->create_pdf_from_content($post, $options);

        if (is_wp_error($pdf_result)) {
            return $pdf_result;
        }

        // Store attachment ID.
        update_post_meta($post->ID, self::META_PREFIX . 'pdf_attachment_id', $pdf_result['attachment_id']);
        update_post_meta($post->ID, self::META_PREFIX . 'pdf_generated_at', current_time('mysql'));

        return $pdf_result['url'];
    }

    /**
     * Get document versions.
     *
     * @param string $document_id Document ID.
     *
     * @return array|WP_Error
     */
    public function get_versions(string $document_id)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        $revisions = wp_get_post_revisions($post->ID);
        $versions  = [];

        $version_num = count($revisions) + 1;
        foreach ($revisions as $revision) {
            $versions[] = [
                'id'         => $revision->ID,
                'version'    => $version_num,
                'author_id'  => $revision->post_author,
                'created_at' => $revision->post_date,
                'excerpt'    => wp_trim_words($revision->post_content, 30),
            ];
            --$version_num;
        }

        return $versions;
    }

    /**
     * Create a new version.
     *
     * @param string $document_id Document ID.
     * @param string $content     New content.
     * @param string $comment     Version comment.
     *
     * @return array|WP_Error
     */
    public function create_version(string $document_id, string $content, string $comment = '')
    {
        return $this->update($document_id, $content, [ 'version_comment' => $comment ]);
    }

    /**
     * Restore a version.
     *
     * @param string $document_id Document ID.
     * @param int    $version_id  Version (revision) ID.
     *
     * @return bool|WP_Error
     */
    public function restore_version(string $document_id, int $version_id)
    {
        $post = $this->get_post_by_id($document_id);

        if (! $post) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        $revision = get_post($version_id);

        if (! $revision || 'revision' !== $revision->post_type || $revision->post_parent !== $post->ID) {
            return new WP_Error(
                'version_not_found',
                __('Version not found.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        $result = wp_restore_post_revision($version_id);

        if (! $result) {
            return new WP_Error(
                'restore_failed',
                __('Failed to restore version.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        do_action('apollo_dms_version_restored', $post->ID, $version_id);

        return true;
    }

    /**
     * Get post by ID or file_id.
     *
     * @param string $document_id ID or file_id.
     *
     * @return WP_Post|null
     */
    private function get_post_by_id(string $document_id): ?WP_Post
    {
        // Try numeric ID first.
        if (is_numeric($document_id)) {
            $post = get_post((int) $document_id);
            if ($post && self::POST_TYPE === $post->post_type) {
                return $post;
            }
        }

        // Try file_id.
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Required for document lookup by file_id.
        $args = [
            'post_type'      => self::POST_TYPE,
            'meta_key'       => self::META_PREFIX . 'file_id',
            'meta_value'     => $document_id,
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ];
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $query = new WP_Query($args);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Generate unique file ID.
     *
     * @return string
     */
    private function generate_file_id(): string
    {
        return wp_generate_uuid4();
    }

    /**
     * Map document status to post status.
     *
     * @param string $status Document status.
     *
     * @return string
     */
    private function map_doc_status_to_post_status(string $status): string
    {
        $map = [
            'draft'     => 'draft',
            'pending'   => 'pending',
            'ready'     => 'publish',
            'signing'   => 'publish',
            'signed'    => 'publish',
            'completed' => 'publish',
            'archived'  => 'private',
        ];

        return $map[ $status ] ?? 'draft';
    }

    /**
     * Format document for response.
     *
     * @param WP_Post $post Post object.
     *
     * @return array
     */
    private function format_document(WP_Post $post): array
    {
        $file_id      = get_post_meta($post->ID, self::META_PREFIX . 'file_id', true);
        $doc_type     = get_post_meta($post->ID, self::META_PREFIX . 'type', true);
        $doc_status   = get_post_meta($post->ID, self::META_PREFIX . 'status', true);
        $doc_version  = get_post_meta($post->ID, self::META_PREFIX . 'version', true);
        $content_type = get_post_meta($post->ID, self::META_PREFIX . 'content_type', true);
        $category     = get_post_meta($post->ID, self::META_PREFIX . 'category', true);

        return [
            'id'           => $post->ID,
            'file_id'      => $file_id ? $file_id : '',
            'title'        => $post->post_title,
            'content'      => $post->post_content,
            'type'         => $doc_type ? $doc_type : 'documento',
            'status'       => $doc_status ? $doc_status : 'draft',
            'version'      => $doc_version ? (int) $doc_version : 1,
            'content_type' => $content_type ? $content_type : 'text/html',
            'category'     => $category ? $category : '',
            'author_id'    => (int) $post->post_author,
            'created_at'   => $post->post_date,
            'updated_at'   => $post->post_modified,
            'permalink'    => get_permalink($post->ID),
        ];
    }

    /**
     * Create PDF from document content.
     *
     * @param WP_Post $post    Post object.
     * @param array   $options PDF options.
     *
     * @return array|WP_Error
     */
    private function create_pdf_from_content(WP_Post $post, array $options = [])
    {
        // Check for Dompdf.
        if (class_exists('Dompdf\Dompdf')) {
            return $this->generate_pdf_dompdf($post, $options);
        }

        // Check for TCPDF.
        if (class_exists('TCPDF')) {
            return $this->generate_pdf_tcpdf($post, $options);
        }

        return new WP_Error(
            'pdf_library_missing',
            __('No PDF library available. Install Dompdf or TCPDF.', 'apollo-social'),
            [ 'status' => 500 ]
        );
    }

    /**
     * Generate PDF using Dompdf.
     *
     * @param WP_Post $post    Post object.
     * @param array   $options Options.
     *
     * @return array|WP_Error
     */
    private function generate_pdf_dompdf(WP_Post $post, array $options = [])
    {
        if (! class_exists('Dompdf\Dompdf')) {
            return new WP_Error(
                'dompdf_not_available',
                __('Dompdf library is not available.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        // Build HTML.
        $html = $this->build_pdf_html($post, $options);

        // Create Dompdf instance.
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);

        $paper       = isset($options['paper']) ? $options['paper'] : 'A4';
        $orientation = isset($options['orientation']) ? $options['orientation'] : 'portrait';
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        // Save to uploads using WP_Filesystem.
        global $wp_filesystem;
        if (! function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        $upload_dir = wp_upload_dir();
        $filename   = 'document-' . $post->ID . '-' . time() . '.pdf';
        $filepath   = $upload_dir['path'] . '/' . $filename;

        $result = $wp_filesystem->put_contents($filepath, $dompdf->output(), FS_CHMOD_FILE);

        if (false === $result) {
            return new WP_Error(
                'pdf_save_failed',
                __('Failed to save PDF file.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        // Create attachment.
        $attachment = [
            'post_mime_type' => 'application/pdf',
            'post_title'     => $post->post_title . ' (PDF)',
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $filepath, $post->ID);

        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return [
            'attachment_id' => $attach_id,
            'url'           => wp_get_attachment_url($attach_id),
            'path'          => $filepath,
        ];
    }

    /**
     * Generate PDF using TCPDF.
     *
     * @param WP_Post $post    Post object.
     * @param array   $options Options.
     *
     * @return array|WP_Error
     */
    private function generate_pdf_tcpdf(WP_Post $post, array $options = [])
    {
        // Simplified TCPDF implementation.
        return new WP_Error(
            'tcpdf_not_implemented',
            __('TCPDF support coming soon.', 'apollo-social'),
            [ 'status' => 501 ]
        );
    }

    /**
     * Build HTML for PDF.
     *
     * @param WP_Post $post    Post object.
     * @param array   $options Options.
     *
     * @return string
     */
    private function build_pdf_html(WP_Post $post, array $options = []): string
    {
        $title   = esc_html($post->post_title);
        $content = wp_kses_post($post->post_content);
        $date    = date_i18n('d/m/Y H:i', strtotime($post->post_date));
        $file_id = esc_html(get_post_meta($post->ID, self::META_PREFIX . 'file_id', true));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #0f172a;
        }
        .meta {
            font-size: 10px;
            color: #64748b;
        }
        .content {
            text-align: justify;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{$title}</h1>
        <div class="meta">
            Criado em: {$date} | ID: {$file_id}
        </div>
    </div>
    <div class="content">
        {$content}
    </div>
    <div class="footer">
        Documento gerado pelo Apollo::Rio Document Management System
    </div>
</body>
</html>
HTML;
    }
}
