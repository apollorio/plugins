<?php

/**
 * Digital Signature Model
 *
 * @package Apollo\Modules\Signatures\Models
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 */

namespace Apollo\Modules\Signatures\Models;

/**
 * Digital Signature Model
 *
 * Representa uma assinatura digital com diferentes níveis de confiança
 * conforme Lei 14.063/2020
 *
 * @since 1.0.0
 */
class DigitalSignature
{
    // Signature levels according to Lei 14.063/2020
    public const LEVEL_SIMPLE = 'simple';
    // Assinatura eletrônica simples
    public const LEVEL_ADVANCED = 'advanced';
    // Assinatura eletrônica avançada
    public const LEVEL_QUALIFIED = 'qualified';
    // Assinatura eletrônica qualificada (ICP-Brasil)

    // Signature providers
    public const PROVIDER_GOVBR = 'govbr';
    public const PROVIDER_ICP   = 'icp_provider';

    // Status
    public const STATUS_PENDING  = 'pending';
    public const STATUS_SIGNED   = 'signed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED  = 'expired';
    public const STATUS_ERROR    = 'error';

    /** @var int */
    public $id;

    /** @var int */
    public $template_id;

    /** @var string */
    public $document_hash;

    /** @var string */
    public $signer_name;

    /** @var string */
    public $signer_email;

    /** @var string */
    public $signer_document;
    // CPF/CNPJ

    /** @var string */
    public $signature_level;

    /** @var string */
    public $provider;

    /** @var string */
    public $provider_envelope_id;

    /** @var string */
    public $signing_url;

    /** @var string */
    public $status;

    /** @var array Signature metadata */
    public $metadata;

    /** @var string */
    public $signed_at;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    /** @var int */
    public $created_by;

    /**
     * Constructor
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Ensure metadata is array
        if (is_string($this->metadata)) {
            $this->metadata = json_decode($this->metadata, true) ?: [];
        }
    }

    /**
     * Get signature level description
     *
     * @return string
     */
    public function getLevelDescription(): string
    {
        switch ($this->signature_level) {
            case self::LEVEL_SIMPLE:
                return 'Assinatura Eletrônica Simples';
            case self::LEVEL_ADVANCED:
                return 'Assinatura Eletrônica Avançada';
            case self::LEVEL_QUALIFIED:
                return 'Assinatura Eletrônica Qualificada (ICP-Brasil)';
            default:
                return 'Nível desconhecido';
        }
    }

    /**
     * Get status description
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Aguardando assinatura';
            case self::STATUS_SIGNED:
                return 'Assinado';
            case self::STATUS_DECLINED:
                return 'Recusado';
            case self::STATUS_EXPIRED:
                return 'Expirado';
            case self::STATUS_ERROR:
                return 'Erro';
            default:
                return 'Status desconhecido';
        }
    }

    /**
     * Check if signature is complete
     *
     * @return bool
     */
    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    /**
     * Check if signature is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get signature certificate info
     *
     * @return array|null
     */
    public function getCertificateInfo(): ?array
    {
        if (! isset($this->metadata['certificate'])) {
            return null;
        }

        return $this->metadata['certificate'];
    }

    /**
     * Get signature timestamp
     *
     * @return string|null
     */
    public function getSignatureTimestamp(): ?string
    {
        return $this->metadata['timestamp'] ?? $this->signed_at;
    }

    /**
     * Update signature status
     *
     * @param string $status
     * @param array  $metadata
     */
    public function updateStatus(string $status, array $metadata = []): void
    {
        $this->status     = $status;
        $this->metadata   = array_merge($this->metadata ?: [], $metadata);
        $this->updated_at = current_time('mysql');

        if ($status === self::STATUS_SIGNED && ! $this->signed_at) {
            $this->signed_at = current_time('mysql');
        }
    }

    /**
     * Get legal validity based on signature level
     *
     * @return array
     */
    public function getLegalValidity(): array
    {
        switch ($this->signature_level) {
            case self::LEVEL_SIMPLE:
                return [
                    'validity'     => 'Válida para documentos privados',
                    'legal_basis'  => 'Art. 10, § 1º da Lei 14.063/2020',
                    'requirements' => 'Aceita pelas partes e identificação do signatário',
                    'use_cases'    => [ 'Contratos privados', 'Termos de uso', 'Acordos comerciais' ],
                ];

            case self::LEVEL_ADVANCED:
                return [
                    'validity'     => 'Presunção de autenticidade e integridade',
                    'legal_basis'  => 'Art. 10, § 2º da Lei 14.063/2020',
                    'requirements' => 'Dados de criação vinculados ao signatário',
                    'use_cases'    => [ 'Documentos públicos', 'Contratos relevantes', 'Licitações' ],
                ];

            case self::LEVEL_QUALIFIED:
                return [
                    'validity'     => 'Equivale à assinatura manuscrita',
                    'legal_basis'  => 'Art. 10, § 2º da Lei 14.063/2020 e MP 2.200-2/2001',
                    'requirements' => 'Certificado digital ICP-Brasil',
                    'use_cases'    => [ 'Documentos oficiais', 'Cartórios', 'Órgãos públicos' ],
                ];

            default:
                return [ 'validity' => 'Indefinida' ];
        }//end switch
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'template_id'          => $this->template_id,
            'document_hash'        => $this->document_hash,
            'signer_name'          => $this->signer_name,
            'signer_email'         => $this->signer_email,
            'signer_document'      => $this->signer_document,
            'signature_level'      => $this->signature_level,
            'provider'             => $this->provider,
            'provider_envelope_id' => $this->provider_envelope_id,
            'signing_url'          => $this->signing_url,
            'status'               => $this->status,
            'metadata'             => $this->metadata,
            'signed_at'            => $this->signed_at,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
            'created_by'           => $this->created_by,
        ];
    }
}
