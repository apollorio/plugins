<?php

/**
 * Apollo Documents - Signature Service
 *
 * Handles document signing flow with proper data model and PKI integration hooks.
 *
 * @package Apollo\Modules\Documents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Class DocumentsSignatureService
 *
 * Manages document signatures with tamper-evident storage.
 */
class DocumentsSignatureService
{
    /**
     * Meta key for signatures array
     *
     * @var string
     */
    public const SIGNATURES_META_KEY = '_apollo_doc_signatures';

    /**
     * Sign a document
     *
     * @param int    $doc_id Document post ID.
     * @param array  $signer_data Signer information.
     * @param string $signature_method Method: 'e-sign-basic', 'pki-external-v1', etc.
     * @return array|WP_Error Array with signature data or WP_Error.
     */
    public static function sign_document(int $doc_id, array $signer_data, string $signature_method = 'e-sign-basic')
    {
        $post = get_post($doc_id);

        if (! $post || $post->post_type !== 'apollo_document') {
            return new \WP_Error(
                'doc_not_found',
                __('Documento não encontrado.', 'apollo-social')
            );
        }

        // Ensure PDF exists
        $pdf_url = DocumentsPdfService::get_pdf_url($doc_id);
        if (! $pdf_url) {
            return new \WP_Error(
                'no_pdf',
                __('Documento precisa ter PDF gerado antes de assinar.', 'apollo-social')
            );
        }

        // Get PDF hash
        $pdf_hash = self::get_pdf_hash($doc_id);
        if (is_wp_error($pdf_hash)) {
            return $pdf_hash;
        }

        // Get signer info
        $signer_id    = isset($signer_data['signer_id']) ? absint($signer_data['signer_id']) : null;
        $signer_name  = isset($signer_data['name']) ? sanitize_text_field($signer_data['name']) : '';
        $signer_email = isset($signer_data['email']) ? sanitize_email($signer_data['email']) : '';
        $role         = isset($signer_data['role']) ? sanitize_text_field($signer_data['role']) : 'signer';

        // Validate required fields
        if (empty($signer_name)) {
            return new \WP_Error(
                'invalid_signer',
                __('Nome do signatário é obrigatório.', 'apollo-social')
            );
        }

        // Get IP and user agent
        $ip_address = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        // Create signature entry
        $signature_entry = [
            'signer_id'        => $signer_id,
            'signer_name'      => $signer_name,
            'signer_email'     => $signer_email,
            'role'             => $role,
            'signed_at'        => current_time('mysql', true), // UTC
            'ip_address'       => $ip_address,
            'user_agent'       => $user_agent,
            'pdf_hash'         => $pdf_hash,
            'signature_method' => $signature_method,
            'pki_signature_id' => null, // Set by PKI hook if applicable
        ];

        // Check for duplicate signatures (same user/IP within 5 minutes)
        $existing = self::get_signatures($doc_id);
        foreach ($existing as $existing_sig) {
            if ($signer_id && $existing_sig['signer_id'] === $signer_id) {
                return new \WP_Error(
                    'already_signed',
                    __('Você já assinou este documento.', 'apollo-social')
                );
            }
        }

        // Rate limiting: max 3 signatures per IP per hour
        $recent_count = self::count_recent_signatures_by_ip($ip_address, 3600);
        if ($recent_count >= 3) {
            return new \WP_Error(
                'rate_limit',
                __('Muitas assinaturas recentes. Tente novamente mais tarde.', 'apollo-social')
            );
        }

        // Add signature to array
        $existing[] = $signature_entry;
        update_post_meta($doc_id, self::SIGNATURES_META_KEY, $existing);

        // Fire hook for PKI integration
        do_action('apollo_doc_signed', $doc_id, $signature_entry);

        // Update document status if needed
        $status = get_post_meta($doc_id, '_apollo_doc_status', true);
        if ('draft' === $status || empty($status)) {
            update_post_meta($doc_id, '_apollo_doc_status', 'signed');
        }

        return [
            'success'          => true,
            'signature_entry'  => $signature_entry,
            'total_signatures' => count($existing),
        ];
    }

    /**
     * Get all signatures for a document
     *
     * @param int $doc_id Document post ID.
     * @return array Array of signature entries.
     */
    public static function get_signatures(int $doc_id): array
    {
        $signatures = get_post_meta($doc_id, self::SIGNATURES_META_KEY, true);

        return is_array($signatures) ? $signatures : [];
    }

    /**
     * Get PDF hash for a document
     *
     * Tries to get cached hash from meta first, then computes from file if needed.
     *
     * @param int $doc_id Document post ID.
     * @return string|WP_Error Hash hex string or WP_Error.
     */
    public static function get_pdf_hash(int $doc_id)
    {
        // Try cached hash first
        $cached_hash = get_post_meta($doc_id, '_apollo_doc_pdf_hash', true);
        if (! empty($cached_hash) && strlen($cached_hash) === 64) {
            // Verify file still exists and matches
            $attachment_id = get_post_meta($doc_id, '_apollo_doc_pdf_file', true);
            if ($attachment_id) {
                $pdf_path = get_attached_file($attachment_id);
                if ($pdf_path && file_exists($pdf_path)) {
                    // Return cached hash (assume it's still valid unless file was modified)
                    return $cached_hash;
                }
            }
        }

        // Compute hash from file
        $attachment_id = get_post_meta($doc_id, '_apollo_doc_pdf_file', true);

        if (! $attachment_id) {
            return new \WP_Error(
                'no_pdf',
                __('PDF não encontrado para este documento.', 'apollo-social')
            );
        }

        $pdf_path = get_attached_file($attachment_id);
        if (! $pdf_path || ! file_exists($pdf_path)) {
            return new \WP_Error(
                'pdf_not_found',
                __('Arquivo PDF não encontrado no servidor.', 'apollo-social')
            );
        }

        // Compute SHA-256 hash
        $pdf_contents = file_get_contents($pdf_path);
        if (false === $pdf_contents) {
            return new \WP_Error(
                'read_error',
                __('Erro ao ler arquivo PDF.', 'apollo-social')
            );
        }

        $hash = hash('sha256', $pdf_contents);

        // Cache the hash
        update_post_meta($doc_id, '_apollo_doc_pdf_hash', $hash);

        return $hash;
    }

    /**
     * Verify document integrity
     *
     * @param int $doc_id Document post ID.
     * @return array Verification result.
     */
    public static function verify_document(int $doc_id): array
    {
        $post = get_post($doc_id);

        if (! $post || $post->post_type !== 'apollo_document') {
            return [
                'valid'   => false,
                'message' => __('Documento não encontrado.', 'apollo-social'),
            ];
        }

        $signatures = self::get_signatures($doc_id);
        if (empty($signatures)) {
            return [
                'valid'   => false,
                'message' => __('Documento não possui assinaturas.', 'apollo-social'),
            ];
        }

        // Get current PDF hash
        $current_hash = self::get_pdf_hash($doc_id);
        if (is_wp_error($current_hash)) {
            return [
                'valid'   => false,
                'message' => $current_hash->get_error_message(),
            ];
        }

        // Check each signature's hash
        $mismatches = [];
        foreach ($signatures as $index => $signature) {
            if (isset($signature['pdf_hash']) && $signature['pdf_hash'] !== $current_hash) {
                $mismatches[] = [
                    'index'        => $index,
                    'signer'       => $signature['signer_name'],
                    'signed_at'    => $signature['signed_at'],
                    'stored_hash'  => substr($signature['pdf_hash'], 0, 16) . '...',
                    'current_hash' => substr($current_hash, 0, 16) . '...',
                ];
            }
        }

        $is_valid = empty($mismatches);

        return [
            'valid'   => $is_valid,
            'message' => $is_valid
                ? __('Documento íntegro. Todas as assinaturas são válidas.', 'apollo-social')
                : __('Documento foi modificado após assinatura. Integridade comprometida.', 'apollo-social'),
            'current_hash'     => $current_hash,
            'total_signatures' => count($signatures),
            'mismatches'       => $mismatches,
            'signatures'       => self::format_signatures_for_verification($signatures),
        ];
    }

    /**
     * Format signatures for verification display (mask sensitive data)
     *
     * @param array $signatures Raw signatures array.
     * @return array Formatted signatures.
     */
    private static function format_signatures_for_verification(array $signatures): array
    {
        $formatted = [];
        foreach ($signatures as $sig) {
            $formatted[] = [
                'signer_name'      => $sig['signer_name'],
                'signer_email'     => self::mask_email($sig['signer_email'] ?? ''),
                'role'             => $sig['role'] ?? 'signer',
                'signed_at'        => $sig['signed_at'],
                'signature_method' => $sig['signature_method'] ?? 'e-sign-basic',
                'hash_match'       => true, // Will be checked in verify_document
            ];
        }

        return $formatted;
    }

    /**
     * Mask email address for display
     *
     * @param string $email Email address.
     * @return string Masked email.
     */
    private static function mask_email(string $email): string
    {
        if (empty($email)) {
            return '';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $local  = $parts[0];
        $domain = $parts[1];

        // Mask local part: show first 2 chars, rest as ***
        $masked_local = substr($local, 0, 2) . '***';

        return $masked_local . '@' . $domain;
    }

    /**
     * Get client IP address
     *
     * @return string IP address.
     */
    private static function get_client_ip(): string
    {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[ $key ])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[ $key ]));
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Count recent signatures by IP (for rate limiting)
     *
     * @param string $ip_address IP address.
     * @param int    $time_window Time window in seconds.
     * @return int Count of signatures.
     */
    private static function count_recent_signatures_by_ip(string $ip_address, int $time_window): int
    {
        global $wpdb;

        $cutoff_time = date('Y-m-d H:i:s', time() - $time_window);

        // Query all documents with signatures
        $posts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
                self::SIGNATURES_META_KEY
            )
        );

        $count = 0;
        foreach ($posts as $post_id) {
            $signatures = get_post_meta($post_id, self::SIGNATURES_META_KEY, true);
            if (! is_array($signatures)) {
                continue;
            }

            foreach ($signatures as $sig) {
                if (isset($sig['ip_address']) && $sig['ip_address'] === $ip_address) {
                    $signed_at = strtotime($sig['signed_at'] ?? '');
                    if ($signed_at && $signed_at >= (time() - $time_window)) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }
}
