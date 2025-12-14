<?php

/**
 * Signature Backend Interface
 *
 * Contract for all signature backend implementations.
 * Allows pluggable backends: Demoiselle, SetaPDF, GoSigner, etc.
 *
 * @package Apollo\Modules\Signatures\Contracts
 * @since   2.0.0
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

declare(strict_types=1);

namespace Apollo\Modules\Signatures\Contracts;

use WP_Error;

/**
 * Interface SignatureBackendInterface
 *
 * All signature backends must implement this interface.
 */
interface SignatureBackendInterface
{
    /**
     * Get backend identifier.
     *
     * @return string Unique backend identifier (e.g., 'local_stub', 'demoiselle', 'setapdf').
     */
    public function get_identifier(): string;

    /**
     * Get backend display name.
     *
     * @return string Human-readable backend name.
     */
    public function get_name(): string;

    /**
     * Check if backend is available/configured.
     *
     * @return bool True if backend is ready to use.
     */
    public function is_available(): bool;

    /**
     * Get backend capabilities.
     *
     * @return array Array of supported features.
     *   - 'pades'           => bool (PAdES PDF signatures)
     *   - 'cades'           => bool (CAdES signatures)
     *   - 'xades'           => bool (XAdES signatures)
     *   - 'icp_brasil'      => bool (ICP-Brasil qualified)
     *   - 'timestamp'       => bool (TSA timestamp)
     *   - 'batch_sign'      => bool (batch signing)
     *   - 'certificate_a1'  => bool (A1 software cert)
     *   - 'certificate_a3'  => bool (A3 hardware token)
     */
    public function get_capabilities(): array;

    /**
     * Sign a document.
     *
     * @param int   $document_id Apollo document ID.
     * @param int   $user_id     User performing the signature.
     * @param array $options     Signature options array.
     *
     * @return array|WP_Error Result array on success, WP_Error on failure.
     */
    public function sign(int $document_id, int $user_id, array $options = []): array|WP_Error;

    /**
     * Verify a signature.
     *
     * @param string $pdf_path Path to signed PDF.
     * @param array  $options  Verification options.
     *
     * @return array|WP_Error Verification result.
     */
    public function verify(string $pdf_path, array $options = []): array|WP_Error;

    /**
     * Get signer information from certificate.
     *
     * @param string $certificate_path Path to certificate file.
     * @param string $password         Certificate password (for A1).
     *
     * @return array|WP_Error Signer information array.
     */
    public function get_certificate_info(string $certificate_path, string $password = ''): array|WP_Error;
}
