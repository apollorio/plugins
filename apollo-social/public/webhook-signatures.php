<?php
/**
 * DocuSeal Webhook Endpoint
 * 
 * Processes webhook callbacks from DocuSeal signature service
 * 
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if called directly
    $wp_load_path = dirname(__FILE__) . '/../../../../../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        http_response_code(404);
        exit('WordPress not found');
    }
}

/**
 * Process DocuSeal webhook
 */
function apollo_process_docuseal_webhook() {
    try {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        // Get raw POST data
        $raw_payload = file_get_contents('php://input');
        if (empty($raw_payload)) {
            http_response_code(400);
            exit('Empty payload');
        }

        // Parse JSON payload
        $payload = json_decode($raw_payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit('Invalid JSON');
        }

        // Log webhook for debugging
        error_log('DocuSeal Webhook Received: ' . json_encode($payload, JSON_PRETTY_PRINT));

        // Load DocuSeal API adapter
        if (!class_exists('Apollo\Modules\Signatures\Adapters\DocuSealApi')) {
            require_once dirname(__FILE__) . '/../wp-content/plugins/apollo-social/src/Modules/Signatures/Adapters/DocuSealApi.php';
        }

        $docuseal_api = new \Apollo\Modules\Signatures\Adapters\DocuSealApi();
        
        // Process webhook
        $result = $docuseal_api->processWebhook($payload);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to process webhook']);
        }

    } catch (Exception $e) {
        error_log('DocuSeal Webhook Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    }

    exit;
}

/**
 * Handle webhook routing
 */
function apollo_handle_webhook_request() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Check if this is a DocuSeal webhook request
    if (strpos($request_uri, '/apollo-signatures/webhook/docuseal') !== false) {
        apollo_process_docuseal_webhook();
    }
    
    // Check if this is a GOV.BR webhook request
    if (strpos($request_uri, '/apollo-signatures/webhook/govbr') !== false) {
        apollo_process_govbr_webhook();
    }
}

/**
 * Process GOV.BR webhook (stub)
 */
function apollo_process_govbr_webhook() {
    try {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        // Get raw POST data
        $raw_payload = file_get_contents('php://input');
        if (empty($raw_payload)) {
            http_response_code(400);
            exit('Empty payload');
        }

        // Parse JSON payload
        $payload = json_decode($raw_payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit('Invalid JSON');
        }

        // Log webhook for debugging
        error_log('GOV.BR Webhook Received: ' . json_encode($payload, JSON_PRETTY_PRINT));

        // Load GOV.BR API adapter
        if (!class_exists('Apollo\Modules\Signatures\Adapters\GovbrApi')) {
            require_once dirname(__FILE__) . '/../wp-content/plugins/apollo-social/src/Modules/Signatures/Adapters/GovbrApi.php';
        }

        $govbr_api = new \Apollo\Modules\Signatures\Adapters\GovbrApi();
        
        // Process webhook
        $result = $govbr_api->processWebhook($payload);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to process webhook']);
        }

    } catch (Exception $e) {
        error_log('GOV.BR Webhook Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    }

    exit;
}

// Handle the request
apollo_handle_webhook_request();

// If we reach here, it's not a webhook request
// Return 404 for invalid webhook URLs
http_response_code(404);
exit('Not Found');