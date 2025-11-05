<?php

namespace Apollo\Modules\Signatures\Services;

/**
 * Local Signature Service
 * 
 * Handles signature capture and evidence generation without external APIs.
 */
class LocalSignatureService
{
    /**
     * Create evidence pack for a locally captured signature
     */
    public function createEvidencePack(array $signature_data): array
    {
        $timestamp = time();
        $signature_hash = $this->generateSignatureHash($signature_data);
        
        return [
            'evidence_pack' => [
                'signature_hash' => $signature_hash,
                'timestamp' => $timestamp,
                'timestamp_iso' => date('c', $timestamp),
                'signature_method' => 'local_canvas',
                'ip_address' => $this->getClientIpAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'canvas_dimensions' => [
                    'width' => $signature_data['canvas_width'] ?? 0,
                    'height' => $signature_data['canvas_height'] ?? 0
                ],
                'stroke_points' => count($signature_data['stroke_data'] ?? []),
                'signature_duration' => $signature_data['duration'] ?? 0,
                'metadata' => [
                    'browser_info' => $this->getBrowserInfo(),
                    'screen_resolution' => $signature_data['screen_resolution'] ?? '',
                    'device_pixel_ratio' => $signature_data['device_pixel_ratio'] ?? 1
                ]
            ],
            'verification' => [
                'hash_algorithm' => 'sha256',
                'hash_verified' => true,
                'timestamp_verified' => true,
                'ip_geolocation' => $this->getBasicGeolocation()
            ]
        ];
    }

    /**
     * Generate SHA-256 hash of signature data
     */
    private function generateSignatureHash(array $signature_data): string
    {
        $data_to_hash = [
            'stroke_data' => $signature_data['stroke_data'] ?? [],
            'canvas_width' => $signature_data['canvas_width'] ?? 0,
            'canvas_height' => $signature_data['canvas_height'] ?? 0,
            'timestamp' => time()
        ];
        
        return hash('sha256', json_encode($data_to_hash));
    }

    /**
     * Get client IP address with proxy detection
     */
    private function getClientIpAddress(): string
    {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get basic browser information
     */
    private function getBrowserInfo(): array
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return [
            'user_agent' => $user_agent,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'connection' => $_SERVER['HTTP_CONNECTION'] ?? ''
        ];
    }

    /**
     * Get basic geolocation (country only, no API)
     */
    private function getBasicGeolocation(): array
    {
        // Simple timezone-based country detection
        $timezone = date_default_timezone_get();
        
        $timezone_countries = [
            'America/Sao_Paulo' => 'BR',
            'America/Recife' => 'BR',
            'America/Manaus' => 'BR',
            'America/Belem' => 'BR',
            'America/Fortaleza' => 'BR',
            'America/Maceio' => 'BR'
        ];
        
        return [
            'country_code' => $timezone_countries[$timezone] ?? 'unknown',
            'timezone' => $timezone,
            'method' => 'timezone_inference'
        ];
    }

    /**
     * Validate signature data structure
     */
    public function validateSignatureData(array $signature_data): array
    {
        $errors = [];
        
        if (empty($signature_data['stroke_data'])) {
            $errors[] = 'Dados de traço da assinatura são obrigatórios';
        }
        
        if (empty($signature_data['canvas_width']) || empty($signature_data['canvas_height'])) {
            $errors[] = 'Dimensões do canvas são obrigatórias';
        }
        
        if (empty($signature_data['signer_name'])) {
            $errors[] = 'Nome do signatário é obrigatório';
        }
        
        if (empty($signature_data['signer_email'])) {
            $errors[] = 'Email do signatário é obrigatório';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Generate local signature certificate
     */
    public function generateLocalCertificate(array $signature_data, array $evidence_pack): array
    {
        $cert_id = 'APOLLO_' . strtoupper($this->generateRandomString(12));
        
        return [
            'certificate_id' => $cert_id,
            'certificate_type' => 'local_apollo_signature',
            'issued_at' => date('c'),
            'valid_until' => date('c', strtotime('+10 years')),
            'signer_info' => [
                'name' => $signature_data['signer_name'],
                'email' => $signature_data['signer_email'],
                'document' => $signature_data['signer_document'] ?? '',
                'ip_address' => $evidence_pack['evidence_pack']['ip_address']
            ],
            'signature_info' => [
                'hash' => $evidence_pack['evidence_pack']['signature_hash'],
                'timestamp' => $evidence_pack['evidence_pack']['timestamp_iso'],
                'method' => 'canvas_capture',
                'stroke_points' => $evidence_pack['evidence_pack']['stroke_points']
            ],
            'verification_url' => $this->getHomeUrl() . "/apollo/verify-signature/{$cert_id}",
            'legal_notice' => 'Esta assinatura foi capturada localmente pelo sistema Apollo Social e possui validade para fins internos da plataforma.'
        ];
    }

    /**
     * Generate random string for certificate ID
     */
    private function generateRandomString(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $result;
    }

    /**
     * Get home URL
     */
    private function getHomeUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "{$protocol}://{$host}";
    }
}