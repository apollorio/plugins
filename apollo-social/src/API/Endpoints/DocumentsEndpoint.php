<?php
/**
 * P0-9: Documents Endpoint
 * 
 * Handles document saving, retrieval, and export (PDF/XLSX/CSV).
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) exit;

class DocumentsEndpoint
{
    /**
     * Register REST routes
     */
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register routes
     */
    public function registerRoutes(): void
    {
        // Save document
        register_rest_route('apollo/v1', '/documents', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'saveDocument'],
            'permission_callback' => [$this, 'permissionCheck'],
            'args' => [
                'file_id' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => __('File ID (for updates).', 'apollo-social'),
                ],
                'title' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => __('Document title.', 'apollo-social'),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'type' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => function($param) {
                        return in_array($param, ['document', 'spreadsheet']);
                    },
                ],
                'content' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => __('Document content (HTML for documents, JSON for spreadsheets).', 'apollo-social'),
                ],
            ],
        ]);

        // Get document
        register_rest_route('apollo/v1', '/documents/(?P<file_id>[a-zA-Z0-9]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'getDocument'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        // Export to PDF
        register_rest_route('apollo/v1', '/documents/(?P<file_id>[a-zA-Z0-9]+)/export/pdf', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'exportToPdf'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        // Export to XLSX
        register_rest_route('apollo/v1', '/documents/(?P<file_id>[a-zA-Z0-9]+)/export/xlsx', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'exportToXlsx'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        // Export to CSV
        register_rest_route('apollo/v1', '/documents/(?P<file_id>[a-zA-Z0-9]+)/export/csv', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'exportToCsv'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        // List user documents
        register_rest_route('apollo/v1', '/documents', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'listDocuments'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);
    }

    /**
     * Permission check
     */
    public function permissionCheck(WP_REST_Request $request): bool|WP_Error
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in.', 'apollo-social'),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * P0-9: Save document
     */
    public function saveDocument(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $file_id = $request->get_param('file_id');
        $title = $request->get_param('title');
        $type = $request->get_param('type');
        $content = $request->get_param('content');

        if (empty($title) || empty($type) || empty($content)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Title, type, and content are required.', 'apollo-social'),
            ], 400);
        }

        $table_name = $wpdb->prefix . 'apollo_documents';

        // Generate file_id if new document
        if (empty($file_id)) {
            $file_id = $this->generateFileId();
        } else {
            // Verify ownership
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM $table_name WHERE file_id = %s",
                $file_id
            ));

            if ($existing && (int) $existing->user_id !== $user_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('You do not have permission to edit this document.', 'apollo-social'),
                ], 403);
            }
        }

        // Save or update
        $data = [
            'file_id' => $file_id,
            'user_id' => $user_id,
            'title' => $title,
            'type' => $type,
            'content' => $content,
            'updated_at' => current_time('mysql'),
        ];

        if ($existing) {
            // Update
            $wpdb->update(
                $table_name,
                $data,
                ['file_id' => $file_id],
                ['%s', '%d', '%s', '%s', '%s', '%s'],
                ['%s']
            );
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table_name,
                $data,
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'file_id' => $file_id,
                'title' => $title,
                'type' => $type,
            ],
            'message' => __('Document saved successfully.', 'apollo-social'),
        ], 200);
    }

    /**
     * P0-9: Get document
     */
    public function getDocument(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $file_id = $request->get_param('file_id');
        $user_id = get_current_user_id();

        $table_name = $wpdb->prefix . 'apollo_documents';

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE file_id = %s",
            $file_id
        ));

        if (!$document) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Document not found.', 'apollo-social'),
            ], 404);
        }

        // Check ownership
        if ((int) $document->user_id !== $user_id && !current_user_can('edit_others_posts')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('You do not have permission to view this document.', 'apollo-social'),
            ], 403);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'file_id' => $document->file_id,
                'title' => $document->title,
                'type' => $document->type,
                'content' => $document->content,
                'status' => $document->status,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ],
        ], 200);
    }

    /**
     * P0-9: List user documents
     */
    public function listDocuments(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $type = $request->get_param('type'); // Optional filter

        $table_name = $wpdb->prefix . 'apollo_documents';

        $where = $wpdb->prepare("user_id = %d", $user_id);
        if ($type && in_array($type, ['document', 'spreadsheet'])) {
            $where .= $wpdb->prepare(" AND type = %s", $type);
        }

        $documents = $wpdb->get_results(
            "SELECT file_id, title, type, status, created_at, updated_at FROM $table_name WHERE $where ORDER BY updated_at DESC"
        );

        return new WP_REST_Response([
            'success' => true,
            'data' => $documents,
        ], 200);
    }

    /**
     * P0-9: Export to PDF
     */
    public function exportToPdf(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $file_id = $request->get_param('file_id');
        $user_id = get_current_user_id();

        $table_name = $wpdb->prefix . 'apollo_documents';

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE file_id = %s",
            $file_id
        ));

        if (!$document || (int) $document->user_id !== $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Document not found.', 'apollo-social'),
            ], 404);
        }

        // P0-9: Scaffold for PDF generation
        // Note: Requires Dompdf or TCPDF library
        // For now, return HTML that can be converted client-side or via server library
        
        $html_content = $document->content;
        if ($document->type === 'spreadsheet') {
            // Convert spreadsheet JSON to HTML table
            $data = json_decode($document->content, true);
            $html_content = $this->spreadsheetToHtml($data);
        }

        // Return HTML for now (client-side PDF generation or server-side library needed)
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'html' => $html_content,
                'title' => $document->title,
            ],
            'message' => __('PDF export ready. Use a PDF library (Dompdf/TCPDF) for server-side generation.', 'apollo-social'),
        ], 200);
    }

    /**
     * P0-9: Export to XLSX
     */
    public function exportToXlsx(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $file_id = $request->get_param('file_id');
        $user_id = get_current_user_id();

        $table_name = $wpdb->prefix . 'apollo_documents';

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE file_id = %s",
            $file_id
        ));

        if (!$document || (int) $document->user_id !== $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Document not found.', 'apollo-social'),
            ], 404);
        }

        // P0-9: Scaffold for XLSX export
        // Note: Requires PhpSpreadsheet library
        // For now, return JSON data that can be converted
        
        if ($document->type === 'spreadsheet') {
            $data = json_decode($document->content, true);
            return new WP_REST_Response([
                'success' => true,
                'data' => $data,
                'message' => __('XLSX export ready. Use PhpSpreadsheet library for server-side generation.', 'apollo-social'),
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('XLSX export only available for spreadsheets.', 'apollo-social'),
            ], 400);
        }
    }

    /**
     * P0-9: Export to CSV
     */
    public function exportToCsv(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $file_id = $request->get_param('file_id');
        $user_id = get_current_user_id();

        $table_name = $wpdb->prefix . 'apollo_documents';

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE file_id = %s",
            $file_id
        ));

        if (!$document || (int) $document->user_id !== $user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Document not found.', 'apollo-social'),
            ], 404);
        }

        // P0-9: Simple CSV export (no library needed)
        if ($document->type === 'spreadsheet') {
            $data = json_decode($document->content, true);
            $csv = $this->arrayToCsv($data);
            
            // Return CSV content
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'csv' => $csv,
                    'filename' => sanitize_file_name($document->title) . '.csv',
                ],
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('CSV export only available for spreadsheets.', 'apollo-social'),
            ], 400);
        }
    }

    /**
     * Generate unique file ID
     */
    private function generateFileId(): string
    {
        return wp_generate_password(32, false);
    }

    /**
     * Convert spreadsheet data to HTML table
     */
    private function spreadsheetToHtml(array $data): string
    {
        if (empty($data) || !isset($data['cells'])) {
            return '<p>No data available.</p>';
        }

        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        
        foreach ($data['cells'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data) || !isset($data['cells'])) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        foreach ($data['cells'] as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}

