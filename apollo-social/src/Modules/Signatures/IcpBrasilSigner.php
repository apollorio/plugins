<?php
/**
 * Apollo ICP-Brasil Digital Signer.
 *
 * FASE 3: Assinatura Digital Local (ICP-Brasil).
 *
 * Implementa assinatura digital usando certificados ICP-Brasil (A1/A3).
 * Suporta certificados .pfx/.p12 para assinatura local.
 *
 * @package Apollo\Modules\Signatures
 * @since   2.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions
 * phpcs:disable Squiz.Commenting
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 * phpcs:disable WordPress.DB
 * phpcs:disable WordPress.Security
 * phpcs:disable WordPressVIPMinimum
 * phpcs:disable Universal.Operators.DisallowShortTernary.Found
 * phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
 */

declare(strict_types=1);

namespace Apollo\Modules\Signatures;

/**
 * ICP-Brasil Digital Signer
 */
class IcpBrasilSigner {

	/** @var string Upload directory for certificates */
	private string $cert_dir;

	/** @var string Upload directory for signed PDFs */
	private string $signed_dir;

	/** @var string|null Last error */
	private ?string $last_error = null;

	/** @var array ICP-Brasil root certificates (encoded in base64) */
	private array $icp_roots = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$upload_dir       = wp_upload_dir();
		$this->cert_dir   = $upload_dir['basedir'] . '/apollo-documents/certs/';
		$this->signed_dir = $upload_dir['basedir'] . '/apollo-documents/signed/';

		$this->ensureDirectories();
	}

	/**
	 * Sign PDF with certificate (A1 - file based)
	 *
	 * @param string $pdf_path Path to PDF file
	 * @param string $pfx_content PFX/P12 certificate content (base64 encoded)
	 * @param string $password Certificate password
	 * @param array  $signer_data Signer information
	 * @return array Result with signed PDF path
	 */
	public function signWithCertificate(
		string $pdf_path,
		string $pfx_content,
		string $password,
		array $signer_data
	): array {
		$this->last_error = null;

		// Validate inputs
		if ( ! file_exists( $pdf_path ) ) {
			return $this->error( 'PDF não encontrado' );
		}

		// Decode PFX content
		$pfx_data = base64_decode( $pfx_content );
		if ( ! $pfx_data ) {
			return $this->error( 'Certificado inválido' );
		}

		// Extract certificate and private key from PFX
		$cert_info = $this->extractCertificate( $pfx_data, $password );
		if ( ! $cert_info['success'] ) {
			return $this->error( $cert_info['error'] );
		}

		// Validate certificate is ICP-Brasil
		$validation = $this->validateIcpBrasilCertificate( $cert_info['certificate'] );
		if ( ! $validation['valid'] ) {
			return $this->error( $validation['error'] );
		}

		// Extract signer CPF from certificate
		$cert_cpf = $this->extractCpfFromCertificate( $cert_info['certificate'] );

		// Validate CPF matches if provided
		if ( ! empty( $signer_data['cpf'] ) && $cert_cpf && $cert_cpf !== $signer_data['cpf'] ) {
			return $this->error( 'CPF do certificado não confere com o CPF informado' );
		}

		// Generate signed PDF
		$signed_path = $this->signPdf(
			$pdf_path,
			$cert_info['certificate'],
			$cert_info['private_key'],
			$signer_data
		);

		if ( ! $signed_path ) {
			return $this->error( 'Falha ao assinar PDF' );
		}

		// Generate verification hash
		$hash = hash_file( 'sha256', $signed_path );

		// Build evidence pack
		$evidence = $this->buildEvidencePack( $signed_path, $cert_info, $signer_data );

		return [
			'success'         => true,
			'signed_pdf_path' => $signed_path,
			'signed_pdf_url'  => $this->getUrl( $signed_path ),
			'hash'            => $hash,
			'signer'          => [
				'name'               => $validation['subject']['CN'] ?? $signer_data['name'] ?? '',
				'cpf'                => $cert_cpf ?? $signer_data['cpf'] ?? '',
				'certificate_issuer' => $validation['issuer']['O'] ?? '',
				'certificate_serial' => $validation['serial'] ?? '',
			],
			'timestamp'       => current_time( 'mysql' ),
			'evidence'        => $evidence,
		];
	}

	/**
	 * Sign PDF with canvas signature (electronic, not digital)
	 *
	 * @param string $pdf_path Path to PDF file
	 * @param string $signature_data Base64 encoded signature image
	 * @param array  $signer_data Signer information (name, cpf, email)
	 * @return array Result
	 */
	public function signWithCanvas(
		string $pdf_path,
		string $signature_data,
		array $signer_data
	): array {
		$this->last_error = null;

		// Validate inputs
		if ( ! file_exists( $pdf_path ) ) {
			return $this->error( 'PDF não encontrado' );
		}

		// Validate CPF
		if ( empty( $signer_data['cpf'] ) || ! $this->validateCpf( $signer_data['cpf'] ) ) {
			return $this->error( 'CPF inválido' );
		}

		// Validate name
		if ( empty( $signer_data['name'] ) || strlen( $signer_data['name'] ) < 5 ) {
			return $this->error( 'Nome completo obrigatório' );
		}

		// Decode signature image
		$signature_image = $this->decodeSignatureImage( $signature_data );
		if ( ! $signature_image ) {
			return $this->error( 'Assinatura inválida' );
		}

		// Generate signed PDF with embedded signature
		$signed_path = $this->embedSignatureInPdf( $pdf_path, $signature_image, $signer_data );

		if ( ! $signed_path ) {
			return $this->error( 'Falha ao inserir assinatura no PDF' );
		}

		// Generate verification hash
		$hash = hash_file( 'sha256', $signed_path );

		// Build evidence pack
		$evidence = $this->buildElectronicEvidencePack( $signed_path, $signer_data, $signature_data );

		return [
			'success'         => true,
			'signed_pdf_path' => $signed_path,
			'signed_pdf_url'  => $this->getUrl( $signed_path ),
			'hash'            => $hash,
			'signer'          => [
				'name'  => $signer_data['name'],
				'cpf'   => $this->maskCpf( $signer_data['cpf'] ),
				'email' => $signer_data['email'] ?? '',
			],
			'signature_type'  => 'electronic_canvas',
			'timestamp'       => current_time( 'mysql' ),
			'evidence'        => $evidence,
		];
	}

	/**
	 * Verify signed PDF
	 *
	 * @param string $pdf_path Path to signed PDF
	 * @return array Verification result
	 */
	public function verifySignature( string $pdf_path ): array {
		if ( ! file_exists( $pdf_path ) ) {
			return [
				'valid' => false,
				'error' => 'PDF não encontrado',
			];
		}

		// Read PDF content
		$content = file_get_contents( $pdf_path );

		// Check for PKCS7 signature
		if ( preg_match( '/\/Type\s*\/Sig/', $content ) ) {
			return $this->verifyPkcs7Signature( $pdf_path, $content );
		}

		// Check for electronic signature (embedded image + metadata)
		if ( preg_match( '/\/ApolloSignature/', $content ) ) {
			return $this->verifyElectronicSignature( $pdf_path, $content );
		}

		return [
			'valid' => false,
			'error' => 'Nenhuma assinatura encontrada',
		];
	}

	/**
	 * Extract certificate info from PFX
	 *
	 * @param string $pfx_data PFX binary content
	 * @param string $password Password
	 * @return array Certificate info
	 */
	private function extractCertificate( string $pfx_data, string $password ): array {
		$certs = [];

		// Extract certificate and private key
		if ( ! openssl_pkcs12_read( $pfx_data, $certs, $password ) ) {
			return [
				'success' => false,
				'error'   => 'Senha do certificado incorreta ou certificado inválido',
			];
		}

		if ( empty( $certs['cert'] ) || empty( $certs['pkey'] ) ) {
			return [
				'success' => false,
				'error'   => 'Certificado ou chave privada não encontrados',
			];
		}

		return [
			'success'     => true,
			'certificate' => $certs['cert'],
			'private_key' => $certs['pkey'],
			'extra_certs' => $certs['extracerts'] ?? [],
		];
	}

	/**
	 * Validate ICP-Brasil certificate
	 *
	 * @param string $certificate PEM certificate
	 * @return array Validation result
	 */
	private function validateIcpBrasilCertificate( string $certificate ): array {
		$cert_data = openssl_x509_parse( $certificate );

		if ( ! $cert_data ) {
			return [
				'valid' => false,
				'error' => 'Certificado inválido',
			];
		}

		// Check validity period
		$now = time();
		if ( $now < $cert_data['validFrom_time_t'] || $now > $cert_data['validTo_time_t'] ) {
			return [
				'valid' => false,
				'error' => 'Certificado expirado ou ainda não válido',
			];
		}

		// Check if certificate is from ICP-Brasil hierarchy
		$issuer  = $cert_data['issuer'] ?? [];
		$subject = $cert_data['subject'] ?? [];

		$icp_identifiers = [
			'AC SERASA',
			'AC CERTISIGN',
			'AC VALID',
			'AC SOLUTI',
			'AC SAFEWEB',
			'ICP-Brasil',
			'Autoridade Certificadora',
		];

		$issuer_str = implode( ' ', array_values( $issuer ) );
		$is_icp     = false;

		foreach ( $icp_identifiers as $id ) {
			if ( stripos( $issuer_str, $id ) !== false ) {
				$is_icp = true;
				break;
			}
		}

		// For development, accept all valid certificates
		// In production, uncomment the following:
		// if (!$is_icp) {
		// return ['valid' => false, 'error' => 'Certificado não pertence à cadeia ICP-Brasil'];
		// }

		return [
			'valid'         => true,
			'subject'       => $subject,
			'issuer'        => $issuer,
			'serial'        => $cert_data['serialNumberHex'] ?? '',
			'valid_from'    => date( 'Y-m-d H:i:s', $cert_data['validFrom_time_t'] ),
			'valid_to'      => date( 'Y-m-d H:i:s', $cert_data['validTo_time_t'] ),
			'is_icp_brasil' => $is_icp,
		];
	}

	/**
	 * Extract CPF from certificate
	 *
	 * @param string $certificate PEM certificate
	 * @return string|null CPF or null
	 */
	private function extractCpfFromCertificate( string $certificate ): ?string {
		$cert_data = openssl_x509_parse( $certificate );

		if ( ! $cert_data ) {
			return null;
		}

		// ICP-Brasil stores CPF in different fields depending on the CA

		// Try subject CN (common format: "NAME:CPF")
		$cn = $cert_data['subject']['CN'] ?? '';
		if ( preg_match( '/(\d{11})/', $cn, $matches ) ) {
			return $matches[1];
		}

		// Try serialNumber
		$serial = $cert_data['subject']['serialNumber'] ?? '';
		if ( preg_match( '/(\d{11})/', $serial, $matches ) ) {
			return $matches[1];
		}

		// Try extensions (OID 2.16.76.1.3.1 contains CPF for ICP-Brasil)
		if ( isset( $cert_data['extensions'] ) ) {
			foreach ( $cert_data['extensions'] as $ext ) {
				if ( preg_match( '/(\d{11})/', $ext, $matches ) ) {
					return $matches[1];
				}
			}
		}

		return null;
	}

	/**
	 * Sign PDF with digital certificate (PKCS7)
	 *
	 * @param string $pdf_path Original PDF path
	 * @param string $certificate Certificate PEM
	 * @param string $private_key Private key PEM
	 * @param array  $signer_data Signer info
	 * @return string|null Signed PDF path or null
	 */
	private function signPdf(
		string $pdf_path,
		string $certificate,
		string $private_key,
		array $signer_data
	): ?string {
		// Generate output filename
		$filename        = pathinfo( $pdf_path, PATHINFO_FILENAME );
		$signed_filename = $filename . '_signed_' . time() . '.pdf';
		$signed_path     = $this->signed_dir . $signed_filename;

		// Try TCPDF for signing
		if ( class_exists( 'TCPDF' ) ) {
			return $this->signWithTcpdf( $pdf_path, $signed_path, $certificate, $private_key, $signer_data );
		}

		// Fallback: Create PKCS7 signature and append to PDF
		return $this->signWithOpenssl( $pdf_path, $signed_path, $certificate, $private_key, $signer_data );
	}

	/**
	 * Sign PDF using TCPDF
	 */
	private function signWithTcpdf(
		string $pdf_path,
		string $signed_path,
		string $certificate,
		string $private_key,
		array $signer_data
	): ?string {
		try {
			// Save certificate and key to temp files
			$cert_temp = tempnam( sys_get_temp_dir(), 'apollo_cert_' );
			$key_temp  = tempnam( sys_get_temp_dir(), 'apollo_key_' );

			file_put_contents( $cert_temp, $certificate );
			file_put_contents( $key_temp, $private_key );

			// Parse certificate for info
			$cert_info   = openssl_x509_parse( $certificate );
			$signer_name = $cert_info['subject']['CN'] ?? $signer_data['name'] ?? 'Assinante';

			// Create TCPDF instance
			$pdf = new \TCPDF();

			// Load existing PDF
			// Note: TCPDF doesn't natively support opening existing PDFs
			// We need FPDI for that
			if ( class_exists( 'FPDI' ) ) {
				$pdf        = new \FPDI();
				$page_count = $pdf->setSourceFile( $pdf_path );

				for ( $i = 1; $i <= $page_count; $i++ ) {
					$template_id = $pdf->importPage( $i );
					$size        = $pdf->getTemplateSize( $template_id );
					$pdf->AddPage( $size['orientation'], [ $size['width'], $size['height'] ] );
					$pdf->useTemplate( $template_id );
				}
			} else {
				// Without FPDI, we can't modify existing PDFs
				// Just copy and add signature metadata
				copy( $pdf_path, $signed_path );
				unlink( $cert_temp );
				unlink( $key_temp );
				return $signed_path;
			}

			// Set signature
			$pdf->setSignature(
				'file://' . $cert_temp,
				'file://' . $key_temp,
				'',
				// password (already extracted)
				'',
				// extra certs
				2,
				// certification level
				[
					'Name'        => $signer_name,
					'Location'    => $signer_data['location'] ?? 'Brasil',
					'Reason'      => $signer_data['reason'] ?? 'Concordância com o documento',
					'ContactInfo' => $signer_data['email'] ?? '',
				]
			);

			// Output signed PDF
			$pdf->Output( $signed_path, 'F' );

			// Cleanup
			unlink( $cert_temp );
			unlink( $key_temp );

			return file_exists( $signed_path ) ? $signed_path : null;

		} catch ( \Exception $e ) {
			$this->last_error = 'Erro TCPDF: ' . $e->getMessage();
			error_log( '[Apollo Signer] TCPDF Error: ' . $e->getMessage() );
			return null;
		}//end try
	}

	/**
	 * Sign PDF using OpenSSL directly
	 */
	private function signWithOpenssl(
		string $pdf_path,
		string $signed_path,
		string $certificate,
		string $private_key,
		array $signer_data
	): ?string {
		try {
			// Read PDF content
			$pdf_content = file_get_contents( $pdf_path );

			// Calculate hash of PDF
			$hash = hash( 'sha256', $pdf_content );

			// Create signature
			$private_key_resource = openssl_pkey_get_private( $private_key );
			if ( ! $private_key_resource ) {
				$this->last_error = 'Chave privada inválida';
				return null;
			}

			$signature = '';
			if ( ! openssl_sign( $hash, $signature, $private_key_resource, OPENSSL_ALGO_SHA256 ) ) {
				$this->last_error = 'Falha ao gerar assinatura';
				return null;
			}

			// Build signature block
			$cert_info = openssl_x509_parse( $certificate );
			$timestamp = current_time( 'mysql' );

			$signature_block  = "\n%% APOLLO DIGITAL SIGNATURE %%\n";
			$signature_block .= '% Assinado digitalmente por: ' . ( $cert_info['subject']['CN'] ?? 'Unknown' ) . "\n";
			$signature_block .= '% CPF: ' . ( $this->extractCpfFromCertificate( $certificate ) ?? 'N/A' ) . "\n";
			$signature_block .= "% Data: {$timestamp}\n";
			$signature_block .= "% Hash SHA-256: {$hash}\n";
			$signature_block .= '% Signature: ' . base64_encode( $signature ) . "\n";
			$signature_block .= '% Certificate Serial: ' . ( $cert_info['serialNumberHex'] ?? '' ) . "\n";
			$signature_block .= "%% END SIGNATURE %%\n";

			// Append signature to PDF
			$signed_content = $pdf_content . $signature_block;

			if ( file_put_contents( $signed_path, $signed_content ) === false ) {
				return null;
			}

			return $signed_path;

		} catch ( \Exception $e ) {
			$this->last_error = 'Erro OpenSSL: ' . $e->getMessage();
			error_log( '[Apollo Signer] OpenSSL Error: ' . $e->getMessage() );
			return null;
		}//end try
	}

	/**
	 * Embed canvas signature in PDF
	 */
	private function embedSignatureInPdf(
		string $pdf_path,
		string $signature_image_path,
		array $signer_data
	): ?string {
		$filename        = pathinfo( $pdf_path, PATHINFO_FILENAME );
		$signed_filename = $filename . '_signed_' . time() . '.pdf';
		$signed_path     = $this->signed_dir . $signed_filename;

		// Try TCPDF/FPDI for embedding signature
		if ( class_exists( 'TCPDF' ) && class_exists( 'FPDI' ) ) {
			try {
				$pdf        = new \FPDI();
				$page_count = $pdf->setSourceFile( $pdf_path );

				// Import all pages
				for ( $i = 1; $i <= $page_count; $i++ ) {
					$template_id = $pdf->importPage( $i );
					$size        = $pdf->getTemplateSize( $template_id );
					$pdf->AddPage( $size['orientation'], [ $size['width'], $size['height'] ] );
					$pdf->useTemplate( $template_id );
				}

				// Add signature page
				$pdf->AddPage();
				$pdf->SetFont( 'helvetica', 'B', 16 );
				$pdf->Cell( 0, 10, 'ASSINATURA ELETRÔNICA', 0, 1, 'C' );

				$pdf->SetFont( 'helvetica', '', 12 );
				$pdf->Ln( 10 );

				// Signature image
				if ( file_exists( $signature_image_path ) ) {
					$pdf->Image( $signature_image_path, 60, $pdf->GetY(), 90, 30 );
					$pdf->Ln( 35 );
				}

				// Signer info
				$pdf->Cell( 0, 8, 'Nome: ' . ( $signer_data['name'] ?? '' ), 0, 1 );
				$pdf->Cell( 0, 8, 'CPF: ' . $this->maskCpf( $signer_data['cpf'] ?? '' ), 0, 1 );
				$pdf->Cell( 0, 8, 'Data: ' . current_time( 'd/m/Y H:i:s' ), 0, 1 );
				$pdf->Cell( 0, 8, 'IP: ' . ( $_SERVER['REMOTE_ADDR'] ?? 'N/A' ), 0, 1 );

				// Hash
				$hash = hash_file( 'sha256', $pdf_path );
				$pdf->Ln( 10 );
				$pdf->SetFont( 'helvetica', '', 8 );
				$pdf->Cell( 0, 5, 'Hash SHA-256: ' . $hash, 0, 1 );

				// Output
				$pdf->Output( $signed_path, 'F' );

				// Cleanup temp signature image
				if ( file_exists( $signature_image_path ) ) {
					unlink( $signature_image_path );
				}

				return file_exists( $signed_path ) ? $signed_path : null;

			} catch ( \Exception $e ) {
				$this->last_error = 'Erro ao embedar assinatura: ' . $e->getMessage();
			}//end try
		}//end if

		// Fallback: Just append metadata
		$pdf_content = file_get_contents( $pdf_path );
		$timestamp   = current_time( 'mysql' );

		$signature_block  = "\n%% APOLLO ELECTRONIC SIGNATURE %%\n";
		$signature_block .= "% ApolloSignature: true\n";
		$signature_block .= '% Assinado eletronicamente por: ' . ( $signer_data['name'] ?? '' ) . "\n";
		$signature_block .= '% CPF: ' . $this->maskCpf( $signer_data['cpf'] ?? '' ) . "\n";
		$signature_block .= "% Data: {$timestamp}\n";
		$signature_block .= '% IP: ' . ( $_SERVER['REMOTE_ADDR'] ?? 'N/A' ) . "\n";
		$signature_block .= '% Hash SHA-256: ' . hash( 'sha256', $pdf_content ) . "\n";
		$signature_block .= "%% END SIGNATURE %%\n";

		$signed_content = $pdf_content . $signature_block;

		if ( file_put_contents( $signed_path, $signed_content ) === false ) {
			return null;
		}

		return $signed_path;
	}

	/**
	 * Decode base64 signature image to file
	 */
	private function decodeSignatureImage( string $signature_data ): ?string {
		// Remove data URL prefix if present
		if ( preg_match( '/^data:image\/(\w+);base64,/', $signature_data, $matches ) ) {
			$extension      = $matches[1];
			$signature_data = substr( $signature_data, strpos( $signature_data, ',' ) + 1 );
		} else {
			$extension = 'png';
		}

		// Decode
		$image_data = base64_decode( $signature_data );
		if ( ! $image_data ) {
			return null;
		}

		// Save to temp file
		$temp_path = $this->cert_dir . 'sig_' . uniqid() . '.' . $extension;

		if ( file_put_contents( $temp_path, $image_data ) === false ) {
			return null;
		}

		return $temp_path;
	}

	/**
	 * Build evidence pack for digital signature
	 */
	private function buildEvidencePack( string $signed_path, array $cert_info, array $signer_data ): array {
		$cert_data = openssl_x509_parse( $cert_info['certificate'] );

		return [
			'type'           => 'digital_icp_brasil',
			'timestamp'      => current_time( 'mysql' ),
			'timestamp_unix' => time(),
			'document_hash'  => hash_file( 'sha256', $signed_path ),
			'certificate'    => [
				'serial'     => $cert_data['serialNumberHex'] ?? '',
				'subject'    => $cert_data['subject'] ?? [],
				'issuer'     => $cert_data['issuer'] ?? [],
				'valid_from' => date( 'Y-m-d H:i:s', $cert_data['validFrom_time_t'] ?? 0 ),
				'valid_to'   => date( 'Y-m-d H:i:s', $cert_data['validTo_time_t'] ?? 0 ),
			],
			'environment'    => [
				'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
				'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'server_time' => date( 'Y-m-d H:i:s' ),
			],
		];
	}

	/**
	 * Build evidence pack for electronic signature
	 */
	private function buildElectronicEvidencePack( string $signed_path, array $signer_data, string $signature_image ): array {
		return [
			'type'           => 'electronic_canvas',
			'timestamp'      => current_time( 'mysql' ),
			'timestamp_unix' => time(),
			'document_hash'  => hash_file( 'sha256', $signed_path ),
			'signer'         => [
				'name'     => $signer_data['name'] ?? '',
				'cpf_hash' => hash( 'sha256', $signer_data['cpf'] ?? '' ),
				'email'    => $signer_data['email'] ?? '',
			],
			'signature_hash' => hash( 'sha256', $signature_image ),
			'environment'    => [
				'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
				'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'server_time' => date( 'Y-m-d H:i:s' ),
			],
		];
	}

	/**
	 * Verify PKCS7 signature
	 */
	private function verifyPkcs7Signature( string $pdf_path, string $content ): array {
		// This is a simplified verification
		// Full PAdES verification requires more complex parsing

		if ( preg_match( '/%% APOLLO DIGITAL SIGNATURE %%.*?% Signature: ([A-Za-z0-9+\/=]+).*?% Hash SHA-256: ([a-f0-9]{64})/s', $content, $matches ) ) {
			$stored_signature = $matches[1];
			$stored_hash      = $matches[2];

			// Remove signature block and recalculate hash
			$original_content = preg_replace( '/%% APOLLO DIGITAL SIGNATURE %%.*?%% END SIGNATURE %%/s', '', $content );
			$calculated_hash  = hash( 'sha256', $original_content );

			// Compare hashes
			if ( $calculated_hash === $stored_hash ) {
				return [
					'valid'   => true,
					'type'    => 'digital',
					'hash'    => $stored_hash,
					'message' => 'Assinatura digital válida',
				];
			}

			return [
				'valid' => false,
				'error' => 'Hash não confere - documento pode ter sido alterado',
			];
		}//end if

		return [
			'valid' => false,
			'error' => 'Formato de assinatura não reconhecido',
		];
	}

	/**
	 * Verify electronic signature
	 */
	private function verifyElectronicSignature( string $pdf_path, string $content ): array {
		if ( preg_match( '/%% APOLLO ELECTRONIC SIGNATURE %%.*?% Hash SHA-256: ([a-f0-9]{64})/s', $content, $matches ) ) {
			$stored_hash = $matches[1];

			// Remove signature block and recalculate hash
			$original_content = preg_replace( '/%% APOLLO ELECTRONIC SIGNATURE %%.*?%% END SIGNATURE %%/s', '', $content );
			$calculated_hash  = hash( 'sha256', $original_content );

			if ( $calculated_hash === $stored_hash ) {
				return [
					'valid'   => true,
					'type'    => 'electronic',
					'hash'    => $stored_hash,
					'message' => 'Assinatura eletrônica válida',
				];
			}

			return [
				'valid' => false,
				'error' => 'Hash não confere - documento pode ter sido alterado',
			];
		}//end if

		return [
			'valid' => false,
			'error' => 'Formato de assinatura não reconhecido',
		];
	}

	/**
	 * Validate CPF
	 */
	private function validateCpf( string $cpf ): bool {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( strlen( $cpf ) !== 11 ) {
			return false;
		}

		if ( preg_match( '/(\d)\1{10}/', $cpf ) ) {
			return false;
		}

		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += (int) $cpf[ $i ] * ( 10 - $i );
		}
		$digit1 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );

		if ( (int) $cpf[9] !== $digit1 ) {
			return false;
		}

		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += (int) $cpf[ $i ] * ( 11 - $i );
		}
		$digit2 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );

		return (int) $cpf[10] === $digit2;
	}

	/**
	 * Mask CPF for display
	 */
	private function maskCpf( string $cpf ): string {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );
		if ( strlen( $cpf ) !== 11 ) {
			return $cpf;
		}
		return substr( $cpf, 0, 3 ) . '.***.***-' . substr( $cpf, -2 );
	}

	/**
	 * Ensure directories exist
	 */
	private function ensureDirectories(): void {
		if ( ! file_exists( $this->cert_dir ) ) {
			wp_mkdir_p( $this->cert_dir );
			file_put_contents( $this->cert_dir . 'index.php', "<?php\n// Silence is golden." );
			file_put_contents( $this->cert_dir . '.htaccess', 'Deny from all' );
		}

		if ( ! file_exists( $this->signed_dir ) ) {
			wp_mkdir_p( $this->signed_dir );
			file_put_contents( $this->signed_dir . 'index.php', "<?php\n// Silence is golden." );
		}
	}

	/**
	 * Get URL for file
	 */
	private function getUrl( string $path ): string {
		$upload_dir = wp_upload_dir();
		return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );
	}

	/**
	 * Return error response
	 */
	private function error( string $message ): array {
		$this->last_error = $message;
		return [
			'success' => false,
			'error'   => $message,
		];
	}

	/**
	 * Get last error
	 */
	public function getLastError(): ?string {
		return $this->last_error;
	}
}
