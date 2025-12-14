<?php

/**
 * Demoiselle ICP-Brasil Signature Backend
 *
 * Backend implementation for Demoiselle Signer (ICP-Brasil).
 * Uses CLI/microservice to perform PAdES signatures.
 *
 * @package Apollo\Modules\Signatures\Backends
 * @since   2.0.0
 * @see     https://demoiselle.sourceforge.io/
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

namespace Apollo\Modules\Signatures\Backends;

use Apollo\Modules\Signatures\Contracts\SignatureBackendInterface;
use Apollo\Modules\Documents\DocumentsManager;
use WP_Error;

/**
 * Class DemoiselleBackend
 *
 * ICP-Brasil signature backend using Demoiselle Signer.
 *
 * Configuration options (wp-config.php or options):
 * - APOLLO_DEMOISELLE_JAR_PATH: Path to demoiselle-signer.jar
 * - APOLLO_DEMOISELLE_JAVA_PATH: Path to java executable
 * - APOLLO_DEMOISELLE_TSA_URL: Timestamp Authority URL
 * - APOLLO_DEMOISELLE_CRL_CHECK: Enable CRL verification
 */
class DemoiselleBackend implements SignatureBackendInterface
{
    /**
     * Path to Demoiselle JAR.
     *
     * @var string
     */
    private string $jar_path;

    /**
     * Path to Java executable.
     *
     * @var string
     */
    private string $java_path;

    /**
     * TSA URL for timestamp.
     *
     * @var string
     */
    private string $tsa_url;

    /**
     * Enable CRL checking.
     *
     * @var bool
     */
    private bool $crl_check;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->jar_path = defined('APOLLO_DEMOISELLE_JAR_PATH')
            ? APOLLO_DEMOISELLE_JAR_PATH
            : get_option('apollo_demoiselle_jar_path', '');

        $this->java_path = defined('APOLLO_DEMOISELLE_JAVA_PATH')
            ? APOLLO_DEMOISELLE_JAVA_PATH
            : get_option('apollo_demoiselle_java_path', 'java');

        $this->tsa_url = defined('APOLLO_DEMOISELLE_TSA_URL')
            ? APOLLO_DEMOISELLE_TSA_URL
            : get_option('apollo_demoiselle_tsa_url', 'http://timestamp.digicert.com');

        $this->crl_check = defined('APOLLO_DEMOISELLE_CRL_CHECK')
            ? APOLLO_DEMOISELLE_CRL_CHECK
            : (bool) get_option('apollo_demoiselle_crl_check', false);
    }

    /**
     * {@inheritDoc}
     */
    public function get_identifier(): string
    {
        return 'demoiselle';
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string
    {
        return __('Demoiselle Signer (ICP-Brasil)', 'apollo-social');
    }

    /**
     * {@inheritDoc}
     */
    public function is_available(): bool
    {
        // Check if JAR file exists.
        if (empty($this->jar_path) || ! file_exists($this->jar_path)) {
            return false;
        }

        // Check if Java is available.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- Required for Java check.
        $java_check = shell_exec(escapeshellcmd($this->java_path) . ' -version 2>&1');
        if (empty($java_check) || stripos($java_check, 'java') === false) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get_capabilities(): array
    {
        return [
            // PAdES-B, PAdES-T formats.
            'pades' => true,
            // CAdES-B, CAdES-T formats.
            'cades' => true,
            // XAdES not supported yet.
            'xades' => false,
            // Full ICP-Brasil support.
            'icp_brasil' => true,
            // TSA timestamp.
            'timestamp' => true,
            // Batch signing.
            'batch_sign' => true,
            // A1 software certificates.
            'certificate_a1' => true,
            // A3 hardware tokens.
            'certificate_a3' => true,
        ];
    }

    /**
     * Sign a document.
     *
     * {@inheritDoc}
     *
     * @param int   $document_id The document ID.
     * @param int   $user_id     The user ID performing the signature.
     * @param array $options     Additional signing options.
     */
    public function sign(int $document_id, int $user_id, array $options = []): array|WP_Error
    {
        // Check availability.
        if (! $this->is_available()) {
            return new WP_Error(
                'apollo_sign_backend_unavailable',
                __('Backend Demoiselle não está disponível. Verifique a configuração.', 'apollo-social'),
                [ 'status' => 503 ]
            );
        }

        // Validate document.
        $manager  = new DocumentsManager();
        $document = $manager->getDocumentById($document_id);

        if (! $document) {
            return new WP_Error(
                'apollo_sign_document_not_found',
                __('Documento não encontrado.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        // Check PDF path.
        $pdf_path = $document['pdf_path'] ?? '';
        if (empty($pdf_path) || ! file_exists($pdf_path)) {
            return new WP_Error(
                'apollo_sign_pdf_not_found',
                __('PDF do documento não encontrado. Gere o PDF primeiro.', 'apollo-social'),
                [ 'status' => 400 ]
            );
        }

        // Validate certificate.
        $cert_type = $options['certificate_type'] ?? 'A1';
        $cert_path = $options['certificate_path'] ?? '';

        if ('A1' === $cert_type) {
            if (empty($cert_path) || ! file_exists($cert_path)) {
                return new WP_Error(
                    'apollo_sign_cert_not_found',
                    __('Certificado A1 não encontrado.', 'apollo-social'),
                    [ 'status' => 400 ]
                );
            }
        }

        // Prepare output path.
        $uploads_dir     = wp_upload_dir();
        $signed_dir      = $uploads_dir['basedir'] . '/apollo-documents/signed';
        $signed_filename = pathinfo(basename($pdf_path), PATHINFO_FILENAME) . '_signed_' . time() . '.pdf';
        $signed_pdf_path = $signed_dir . '/' . $signed_filename;

        if (! file_exists($signed_dir)) {
            wp_mkdir_p($signed_dir);
        }

        // Build Demoiselle command.
        $this->build_sign_command($pdf_path, $signed_pdf_path, $options);

        /*
         * NOTE: Demoiselle execution is disabled until configured.
         *
         * When ready, enable exec() call and process return_code.
         * See setup guide in error response below.
         */

        // For now, return error indicating setup needed.
        return new WP_Error(
            'apollo_sign_not_configured',
            __('Backend Demoiselle ainda não está configurado. Configure APOLLO_DEMOISELLE_JAR_PATH.', 'apollo-social'),
            [
                'status'      => 501,
                'setup_guide' => [
                    'step_1' => 'Download Demoiselle Signer: https://demoiselle.sourceforge.io/',
                    'step_2' => 'Set APOLLO_DEMOISELLE_JAR_PATH in wp-config.php',
                    'step_3' => 'Ensure Java 8+ is installed',
                    'step_4' => 'Configure TSA URL if needed',
                ],
            ]
        );
    }

    /**
     * Build Demoiselle sign command.
     *
     * @param string $input_path  Input PDF path.
     * @param string $output_path Output PDF path.
     * @param array  $options     Signature options.
     *
     * @return string Shell command.
     */
    private function build_sign_command(string $input_path, string $output_path, array $options): string
    {
        $args = [
            escapeshellcmd($this->java_path),
            '-jar',
            escapeshellarg($this->jar_path),
            'sign',
            // PAdES format.
            '-t',
            'pades',
            '-i',
            escapeshellarg($input_path),
            '-o',
            escapeshellarg($output_path),
        ];

        // Certificate.
        if (! empty($options['certificate_path'])) {
            $args[] = '-c';
            $args[] = escapeshellarg($options['certificate_path']);
        }

        // Password.
        if (! empty($options['certificate_pass'])) {
            $args[] = '-p';
            $args[] = escapeshellarg($options['certificate_pass']);
        }

        // Reason.
        if (! empty($options['reason'])) {
            $args[] = '-r';
            $args[] = escapeshellarg($options['reason']);
        }

        // Location.
        if (! empty($options['location'])) {
            $args[] = '-l';
            $args[] = escapeshellarg($options['location']);
        }

        // Timestamp.
        if (! empty($this->tsa_url)) {
            $args[] = '--tsa';
            $args[] = escapeshellarg($this->tsa_url);
        }

        // CRL check.
        if ($this->crl_check) {
            $args[] = '--crl-check';
        }

        return implode(' ', $args);
    }

    /**
     * Verify a signed PDF.
     *
     * {@inheritDoc}
     *
     * @param string $pdf_path Path to the PDF file.
     * @param array  $options  Additional verification options.
     */
    public function verify(string $pdf_path, array $options = []): array|WP_Error
    {
        if (! $this->is_available()) {
            return new WP_Error(
                'apollo_verify_backend_unavailable',
                __('Backend Demoiselle não está disponível.', 'apollo-social'),
                [ 'status' => 503 ]
            );
        }

        if (! file_exists($pdf_path)) {
            return new WP_Error(
                'apollo_verify_file_not_found',
                __('Arquivo PDF não encontrado.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        /*
         * NOTE: Verification command is prepared but not executed.
         *
         * When ready, enable shell_exec and parse JSON result.
         */

        return new WP_Error(
            'apollo_verify_not_configured',
            __('Verificação Demoiselle não configurada.', 'apollo-social'),
            [ 'status' => 501 ]
        );
    }

    /**
     * Get certificate information.
     *
     * {@inheritDoc}
     *
     * @param string $certificate_path Path to the certificate file.
     * @param string $password         Certificate password.
     */
    public function get_certificate_info(string $certificate_path, string $password = ''): array|WP_Error
    {
        if (! file_exists($certificate_path)) {
            return new WP_Error(
                'apollo_cert_not_found',
                __('Arquivo de certificado não encontrado.', 'apollo-social'),
                [ 'status' => 404 ]
            );
        }

        // Use OpenSSL to read certificate.
        if (! extension_loaded('openssl')) {
            return new WP_Error(
                'apollo_cert_openssl_missing',
                __('Extensão OpenSSL não disponível.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        // Read PKCS#12 certificate.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
        $pkcs12_content = file_get_contents($certificate_path);
        $certs          = [];

        if (! openssl_pkcs12_read($pkcs12_content, $certs, $password)) {
            return new WP_Error(
                'apollo_cert_invalid',
                __('Não foi possível ler o certificado. Verifique a senha.', 'apollo-social'),
                [ 'status' => 400 ]
            );
        }

        // Parse certificate.
        $cert_resource = openssl_x509_read($certs['cert']);
        $cert_data     = openssl_x509_parse($cert_resource);

        if (! $cert_data) {
            return new WP_Error(
                'apollo_cert_parse_failed',
                __('Falha ao analisar o certificado.', 'apollo-social'),
                [ 'status' => 500 ]
            );
        }

        // Extract CPF from ICP-Brasil OID (2.16.76.1.3.1).
        $cpf = $this->extract_cpf_from_certificate($cert_data);

        // PKCS#12 is always A1 type.
        return [
            'name'       => $cert_data['subject']['CN'] ?? '',
            'cpf'        => $cpf,
            'email'      => $cert_data['subject']['emailAddress'] ?? '',
            'issuer'     => $cert_data['issuer']['CN']            ?? '',
            'valid_from' => gmdate('Y-m-d', $cert_data['validFrom_time_t']),
            'valid_to'   => gmdate('Y-m-d', $cert_data['validTo_time_t']),
            'serial'     => $cert_data['serialNumberHex'] ?? $cert_data['serialNumber'] ?? '',
            'type'       => 'A1',
        ];
    }

    /**
     * Extract CPF from ICP-Brasil certificate.
     *
     * OID 2.16.76.1.3.1 contains Brazilian ID data.
     *
     * @param array $cert_data Parsed certificate data.
     *
     * @return string CPF or empty string.
     */
    private function extract_cpf_from_certificate(array $cert_data): string
    {
        // ICP-Brasil stores CPF in the subject alternative name or OID.
        // The format varies by issuer, but commonly in OU field or CN.

        // Try to find in OU (common for some CAs).
        $ou = $cert_data['subject']['OU'] ?? '';
        if (preg_match('/(\d{11})/', $ou, $matches)) {
            return $this->format_cpf($matches[1]);
        }

        // Try in CN.
        $cn = $cert_data['subject']['CN'] ?? '';
        if (preg_match('/:(\d{11})/', $cn, $matches)) {
            return $this->format_cpf($matches[1]);
        }

        // Extension parsing would require deeper ASN.1 handling.
        return '';
    }

    /**
     * Format CPF with mask.
     *
     * @param string $cpf Raw CPF (11 digits).
     *
     * @return string Formatted CPF (XXX.XXX.XXX-XX).
     */
    private function format_cpf(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2)
        );
    }
}
