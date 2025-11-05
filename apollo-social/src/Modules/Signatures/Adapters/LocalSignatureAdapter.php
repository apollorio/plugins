<?php

namespace Apollo\Modules\Signatures\Adapters;

use Apollo\Modules\Signatures\Services\LocalSignatureService;

/**
 * Local Signature Adapter
 * 
 * Adapter for local signature processing without external APIs.
 */
class LocalSignatureAdapter
{
    private LocalSignatureService $localService;

    public function __construct()
    {
        $this->localService = new LocalSignatureService();
    }

    /**
     * Process signature request locally
     */
    public function processSignature(array $signature_data): array
    {
        // Validate signature data
        $validation = $this->localService->validateSignatureData($signature_data);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        try {
            // Create evidence pack
            $evidence_pack = $this->localService->createEvidencePack($signature_data);
            
            // Generate certificate
            $certificate = $this->localService->generateLocalCertificate($signature_data, $evidence_pack);
            
            // Store signature data
            $signature_record = $this->storeSignatureRecord($signature_data, $evidence_pack, $certificate);
            
            return [
                'success' => true,
                'signature_id' => $signature_record['id'],
                'certificate' => $certificate,
                'evidence_pack' => $evidence_pack,
                'verification_url' => $certificate['verification_url']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Erro interno: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Store signature record in database
     */
    private function storeSignatureRecord(array $signature_data, array $evidence_pack, array $certificate): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_signatures';
        
        $record = [
            'certificate_id' => $certificate['certificate_id'],
            'signer_name' => $signature_data['signer_name'],
            'signer_email' => $signature_data['signer_email'],
            'document_template_id' => $signature_data['template_id'] ?? null,
            'signature_type' => 'local',
            'signature_hash' => $evidence_pack['evidence_pack']['signature_hash'],
            'evidence_pack' => json_encode($evidence_pack),
            'certificate_data' => json_encode($certificate),
            'status' => 'completed',
            'created_at' => current_time('mysql'),
            'ip_address' => $evidence_pack['evidence_pack']['ip_address']
        ];
        
        $wpdb->insert($table_name, $record);
        
        return array_merge($record, ['id' => $wpdb->insert_id]);
    }

    /**
     * Verify signature by certificate ID
     */
    public function verifySignature(string $certificate_id): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apollo_signatures';
        
        $signature = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE certificate_id = %s", $certificate_id),
            ARRAY_A
        );
        
        if (!$signature) {
            return [
                'valid' => false,
                'error' => 'Certificado não encontrado'
            ];
        }
        
        $evidence_pack = json_decode($signature['evidence_pack'], true);
        $certificate = json_decode($signature['certificate_data'], true);
        
        return [
            'valid' => true,
            'signature_info' => [
                'certificate_id' => $certificate_id,
                'signer_name' => $signature['signer_name'],
                'signer_email' => $signature['signer_email'],
                'signed_at' => $signature['created_at'],
                'signature_type' => 'Apollo Local Signature',
                'ip_address' => $signature['ip_address']
            ],
            'verification_details' => [
                'hash_algorithm' => 'SHA-256',
                'hash_value' => $signature['signature_hash'],
                'timestamp' => $evidence_pack['evidence_pack']['timestamp_iso'],
                'stroke_points' => $evidence_pack['evidence_pack']['stroke_points'],
                'canvas_dimensions' => $evidence_pack['evidence_pack']['canvas_dimensions']
            ],
            'certificate' => $certificate
        ];
    }

    /**
     * Generate signature URL for embedding
     */
    public function generateSignatureUrl(array $template_data): string
    {
        $params = http_build_query([
            'template_id' => $template_data['id'] ?? '',
            'signer_email' => $template_data['signer_email'] ?? '',
            'track' => 'local'
        ]);
        
        return home_url("/apollo/canvas/signature?{$params}");
    }

    /**
     * Check adapter availability
     */
    public function isAvailable(): bool
    {
        // Local adapter is always available
        return true;
    }

    /**
     * Get adapter configuration
     */
    public function getConfig(): array
    {
        return [
            'name' => 'Local Signature',
            'type' => 'local',
            'description' => 'Assinatura local com canvas HTML5',
            'features' => [
                'canvas_signature' => true,
                'evidence_pack' => true,
                'local_storage' => true,
                'offline_capable' => true
            ],
            'legal_notice' => 'Assinaturas capturadas localmente para uso interno da plataforma Apollo Social.'
        ];
    }
}