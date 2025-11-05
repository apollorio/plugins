<?php

namespace Apollo\Modules\Signatures\Adapters;

use Apollo\Modules\Signatures\Models\DigitalSignature;

/**
 * DocuSeal API Adapter
 * 
 * Integration with DocuSeal for advanced electronic signatures
 * Provides Track A (fast signature) functionality
 * 
 * @since 1.0.0
 */
class DocuSealApi
{
    /** @var string */
    private $api_key;
    
    /** @var string */
    private $api_url;
    
    /** @var string */
    private $webhook_secret;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->api_key = get_option('apollo_docuseal_api_key', '');
        $this->api_url = get_option('apollo_docuseal_api_url', 'https://api.docuseal.co');
        $this->webhook_secret = get_option('apollo_docuseal_webhook_secret', '');
    }

    /**
     * Create envelope for signature
     * 
     * @param DigitalSignature $signature
     * @param string $pdf_path
     * @param array $options
     * @return array|false
     */
    public function createEnvelope(DigitalSignature $signature, string $pdf_path, array $options = []): array|false
    {
        try {
            // Upload document
            $document = $this->uploadDocument($pdf_path, $options);
            if (!$document) {
                throw new \Exception('Falha ao fazer upload do documento');
            }

            // Create template
            $template = $this->createTemplate($document, $signature, $options);
            if (!$template) {
                throw new \Exception('Falha ao criar template');
            }

            // Create submission (envelope)
            $submission = $this->createSubmission($template['id'], $signature, $options);
            if (!$submission) {
                throw new \Exception('Falha ao criar envelope');
            }

            return [
                'envelope_id' => $submission['slug'],
                'signing_url' => $submission['url'],
                'expires_at' => $submission['expires_at'] ?? null,
                'template_id' => $template['id'],
                'document_id' => $document['id']
            ];

        } catch (\Exception $e) {
            error_log('DocuSeal API Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload document to DocuSeal
     * 
     * @param string $pdf_path
     * @param array $options
     * @return array|false
     */
    private function uploadDocument(string $pdf_path, array $options = []): array|false
    {
        if (!file_exists($pdf_path)) {
            return false;
        }

        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/documents',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: multipart/form-data'
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($pdf_path, 'application/pdf', basename($pdf_path)),
                'name' => $options['document_name'] ?? basename($pdf_path)
            ]
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code !== 200) {
            error_log('DocuSeal Upload Error: HTTP ' . $http_code . ' - ' . $response);
            return false;
        }

        $data = json_decode($response, true);
        return $data ?: false;
    }

    /**
     * Create template in DocuSeal
     * 
     * @param array $document
     * @param DigitalSignature $signature
     * @param array $options
     * @return array|false
     */
    private function createTemplate(array $document, DigitalSignature $signature, array $options = []): array|false
    {
        $template_data = [
            'name' => $options['template_name'] ?? 'Documento Apollo - ' . date('Y-m-d H:i:s'),
            'folder_name' => $options['folder_name'] ?? 'Apollo Signatures',
            'documents' => [
                [
                    'id' => $document['id'],
                    'name' => $document['name']
                ]
            ],
            'fields' => [
                [
                    'name' => 'signature',
                    'type' => 'signature',
                    'role' => 'signer',
                    'required' => true,
                    'page' => 0,
                    'x' => 400,
                    'y' => 600,
                    'w' => 150,
                    'h' => 50
                ]
            ],
            'roles' => [
                [
                    'name' => 'signer',
                    'signing_order' => 1
                ]
            ]
        ];

        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/templates',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($template_data)
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code !== 200) {
            error_log('DocuSeal Template Error: HTTP ' . $http_code . ' - ' . $response);
            return false;
        }

        $data = json_decode($response, true);
        return $data ?: false;
    }

    /**
     * Create submission (envelope) in DocuSeal
     * 
     * @param int $template_id
     * @param DigitalSignature $signature
     * @param array $options
     * @return array|false
     */
    private function createSubmission(int $template_id, DigitalSignature $signature, array $options = []): array|false
    {
        $webhook_url = site_url('/apollo-signatures/webhook/docuseal');
        $expires_at = date('Y-m-d', strtotime('+30 days')); // 30 days default
        
        if (!empty($options['expires_at'])) {
            $expires_at = date('Y-m-d', strtotime($options['expires_at']));
        }

        $submission_data = [
            'template_id' => $template_id,
            'send_email' => !empty($options['send_email']),
            'reply_to' => $options['reply_to'] ?? get_option('admin_email'),
            'expires_at' => $expires_at,
            'webhook_url' => $webhook_url,
            'submitters' => [
                [
                    'role' => 'signer',
                    'email' => $signature->signer_email,
                    'name' => $signature->signer_name,
                    'phone' => $options['signer_phone'] ?? '',
                    'message' => $options['custom_message'] ?? $this->getDefaultMessage($signature)
                ]
            ]
        ];

        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/submissions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($submission_data)
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code !== 200) {
            error_log('DocuSeal Submission Error: HTTP ' . $http_code . ' - ' . $response);
            return false;
        }

        $data = json_decode($response, true);
        return $data ?: false;
    }

    /**
     * Process webhook from DocuSeal
     * 
     * @param array $payload
     * @return bool
     */
    public function processWebhook(array $payload): bool
    {
        try {
            // Verify webhook signature if secret is configured
            if (!empty($this->webhook_secret)) {
                $signature = $_SERVER['HTTP_X_DOCUSEAL_SIGNATURE'] ?? '';
                if (!$this->verifyWebhookSignature($signature, json_encode($payload))) {
                    throw new \Exception('Assinatura do webhook inválida');
                }
            }

            $event_type = $payload['event_type'] ?? '';
            $submission = $payload['data'] ?? [];

            if (empty($submission['slug'])) {
                throw new \Exception('Slug do envelope não encontrado');
            }

            switch ($event_type) {
                case 'form.completed':
                    return $this->handleSignatureCompleted($submission);
                    
                case 'form.declined':
                    return $this->handleSignatureDeclined($submission);
                    
                case 'form.expired':
                    return $this->handleSignatureExpired($submission);
                    
                default:
                    // Log unknown events for debugging
                    error_log('DocuSeal Unknown Event: ' . $event_type);
                    return true;
            }

        } catch (\Exception $e) {
            error_log('DocuSeal Webhook Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle signature completed event
     * 
     * @param array $submission
     * @return bool
     */
    private function handleSignatureCompleted(array $submission): bool
    {
        $envelope_id = $submission['slug'];
        
        // Get signature metadata
        $metadata = [
            'provider' => 'docuseal',
            'completed_at' => $submission['completed_at'] ?? date('Y-m-d H:i:s'),
            'submission_id' => $submission['id'] ?? '',
            'template_id' => $submission['template_id'] ?? '',
            'audit_trail' => $submission['audit_trail'] ?? [],
            'certificate' => [
                'issuer' => 'DocuSeal',
                'issued_at' => $submission['completed_at'] ?? date('Y-m-d H:i:s'),
                'signature_level' => DigitalSignature::LEVEL_ADVANCED,
                'compliance' => 'Lei 14.063/2020 Art. 10 § 2º'
            ]
        ];

        // Update signature through service
        $signatures_service = new \Apollo\Modules\Signatures\Services\SignaturesService();
        return $signatures_service->updateSignatureStatus($envelope_id, DigitalSignature::STATUS_SIGNED, $metadata);
    }

    /**
     * Handle signature declined event
     * 
     * @param array $submission
     * @return bool
     */
    private function handleSignatureDeclined(array $submission): bool
    {
        $envelope_id = $submission['slug'];
        
        $metadata = [
            'provider' => 'docuseal',
            'declined_at' => date('Y-m-d H:i:s'),
            'reason' => $submission['decline_reason'] ?? 'Não especificado'
        ];

        $signatures_service = new \Apollo\Modules\Signatures\Services\SignaturesService();
        return $signatures_service->updateSignatureStatus($envelope_id, DigitalSignature::STATUS_DECLINED, $metadata);
    }

    /**
     * Handle signature expired event
     * 
     * @param array $submission
     * @return bool
     */
    private function handleSignatureExpired(array $submission): bool
    {
        $envelope_id = $submission['slug'];
        
        $metadata = [
            'provider' => 'docuseal',
            'expired_at' => date('Y-m-d H:i:s'),
            'expires_at' => $submission['expires_at'] ?? ''
        ];

        $signatures_service = new \Apollo\Modules\Signatures\Services\SignaturesService();
        return $signatures_service->updateSignatureStatus($envelope_id, DigitalSignature::STATUS_EXPIRED, $metadata);
    }

    /**
     * Verify webhook signature
     * 
     * @param string $signature
     * @param string $payload
     * @return bool
     */
    private function verifyWebhookSignature(string $signature, string $payload): bool
    {
        if (empty($this->webhook_secret) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Get default message for signer
     * 
     * @param DigitalSignature $signature
     * @return string
     */
    private function getDefaultMessage(DigitalSignature $signature): string
    {
        return sprintf(
            'Olá %s,

Você tem um documento para assinar no sistema Apollo.

Este documento utiliza assinatura eletrônica avançada conforme a Lei 14.063/2020, garantindo sua autenticidade e integridade.

Clique no link abaixo para assinar:',
            $signature->signer_name
        );
    }

    /**
     * Test API connection
     * 
     * @return array
     */
    public function testConnection(): array
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'API Key não configurada'
            ];
        }

        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/account',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key
            ]
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code === 200) {
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Falha na conexão: HTTP ' . $http_code
            ];
        }
    }

    /**
     * Get envelope status
     * 
     * @param string $envelope_id
     * @return array|false
     */
    public function getEnvelopeStatus(string $envelope_id): array|false
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/submissions/' . $envelope_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_key
            ]
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code === 200) {
            return json_decode($response, true) ?: false;
        }

        return false;
    }
}