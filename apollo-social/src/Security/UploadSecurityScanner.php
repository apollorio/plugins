<?php
/**
 * Apollo Social – Upload Security Scanner
 *
 * Enhanced security layer for frontend file uploads.
 * Protects against:
 *   - PHP code injection in images
 *   - Malicious file signatures
 *   - Executable content disguised as images/PDFs
 *   - Path traversal attacks
 *
 * @package    ApolloSocial
 * @subpackage Security
 * @since      1.2.0
 */

namespace Apollo\Security;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UploadSecurityScanner
 *
 * Scans uploaded files for security threats before they are processed.
 * This is an additional security layer on top of WordPress's built-in checks.
 *
 * @since 1.2.0
 */
class UploadSecurityScanner {

	/**
	 * Dangerous strings that should never appear in uploaded files.
	 *
	 * @var array
	 */
	private static $dangerous_strings = array(
		'<?php',
		'<?=',
		'<script',
		'</script>',
		'javascript:',
		'vbscript:',
		'data:text/html',
		'eval(',
		'base64_decode(',
		'gzinflate(',
		'str_rot13(',
		'exec(',
		'system(',
		'passthru(',
		'shell_exec(',
		'popen(',
		'proc_open(',
		'`',
		'assert(',
		'create_function(',
		'call_user_func',
		'preg_replace.*e',
		'file_get_contents(',
		'file_put_contents(',
		'include(',
		'include_once(',
		'require(',
		'require_once(',
		'fopen(',
		'fwrite(',
		'fputs(',
		'curl_exec(',
		'\\x00',
		'GLOBALS[',
		'_GET[',
		'_POST[',
		'_REQUEST[',
		'_COOKIE[',
		'_SESSION[',
		'_SERVER[',
		'_FILES[',
		'chmod(',
		'chown(',
		'chgrp(',
		'symlink(',
		'link(',
	);

	/**
	 * Dangerous file signatures (magic bytes) that indicate executable content.
	 *
	 * @var array Key = hex signature, Value = description
	 */
	private static $dangerous_signatures = array(
		'4D5A'       => 'Windows executable (MZ)',
		'7F454C46'   => 'Linux ELF executable',
		'504B0304'   => 'ZIP archive (could contain malware)',
		'526172211A' => 'RAR archive',
		'1F8B08'     => 'GZIP archive',
		'25504446'   => 'PDF (will be scanned separately)',
		'CAFEBABE'   => 'Java class file',
		'213C617263' => 'Linux archive',
	);

	/**
	 * Allowed magic signatures for images.
	 *
	 * @var array Key = hex signature, Value = description
	 */
	private static $allowed_image_signatures = array(
		'FFD8FF'   => 'JPEG',
		'89504E47' => 'PNG',
		'47494638' => 'GIF (GIF87a or GIF89a)',
		'52494646' => 'RIFF (WebP container)',
	);

	/**
	 * Scan a file for security threats.
	 *
	 * @param string $file_path Path to the uploaded file.
	 * @param string $file_type Expected file type ('image' or 'pdf').
	 * @return array {
	 *     @type bool   $safe     Whether the file is safe.
	 *     @type string $message  Description of any threat found.
	 *     @type string $code     Error code if not safe.
	 * }
	 */
	public static function scan( string $file_path, string $file_type = 'image' ): array {
		// File must exist
		if ( ! file_exists( $file_path ) ) {
			return array(
				'safe'    => false,
				'message' => __( 'Arquivo não encontrado.', 'apollo-social' ),
				'code'    => 'file_not_found',
			);
		}

		// Check file size (protect against decompression bombs)
		$file_size = filesize( $file_path );
		$max_size  = apply_filters( 'apollo_upload_max_scan_size', 10 * 1024 * 1024 ); 
		// 10 MB
		if ( $file_size > $max_size ) {
			return array(
				'safe'    => false,
				'message' => __( 'Arquivo muito grande para verificação de segurança.', 'apollo-social' ),
				'code'    => 'file_too_large',
			);
		}

		// Read file contents for analysis
		$content = file_get_contents( $file_path );
		if ( $content === false ) {
			return array(
				'safe'    => false,
				'message' => __( 'Não foi possível ler o arquivo.', 'apollo-social' ),
				'code'    => 'read_error',
			);
		}

		// Check 1: Scan for dangerous strings (case-insensitive)
		$dangerous_found = self::scanForDangerousStrings( $content );
		if ( $dangerous_found ) {
			// Log the attempt
			self::logSecurityThreat( $file_path, 'dangerous_string', $dangerous_found );

			return array(
				'safe'    => false,
				'message' => __( 'O arquivo contém conteúdo potencialmente malicioso.', 'apollo-social' ),
				'code'    => 'malicious_content',
			);
		}

		// Check 2: Verify magic bytes for images
		if ( $file_type === 'image' ) {
			$valid_image = self::verifyImageSignature( $content );
			if ( ! $valid_image ) {
				self::logSecurityThreat( $file_path, 'invalid_signature', 'Not a valid image' );

				return array(
					'safe'    => false,
					'message' => __( 'O arquivo não é uma imagem válida.', 'apollo-social' ),
					'code'    => 'invalid_image',
				);
			}
		}

		// Check 3: Check for double extensions (e.g., image.php.jpg)
		$file_name = basename( $file_path );
		if ( self::hasDoubleExtension( $file_name ) ) {
			self::logSecurityThreat( $file_path, 'double_extension', $file_name );

			return array(
				'safe'    => false,
				'message' => __( 'Nome de arquivo suspeito detectado.', 'apollo-social' ),
				'code'    => 'suspicious_filename',
			);
		}

		// Check 4: Look for hidden executable content
		if ( self::hasHiddenExecutable( $content ) ) {
			self::logSecurityThreat( $file_path, 'hidden_executable', 'Executable content detected' );

			return array(
				'safe'    => false,
				'message' => __( 'Conteúdo executável oculto detectado.', 'apollo-social' ),
				'code'    => 'hidden_executable',
			);
		}

		// Check 5: For PDFs, scan for JavaScript
		if ( $file_type === 'pdf' ) {
			$pdf_safe = self::scanPdfForThreats( $content );
			if ( ! $pdf_safe['safe'] ) {
				self::logSecurityThreat( $file_path, 'pdf_threat', $pdf_safe['message'] );
				return $pdf_safe;
			}
		}

		// All checks passed
		return array(
			'safe'    => true,
			'message' => __( 'Arquivo verificado e seguro.', 'apollo-social' ),
			'code'    => 'ok',
		);
	}

	/**
	 * Scan content for dangerous strings.
	 *
	 * @param string $content File content.
	 * @return string|null The dangerous string found, or null if clean.
	 */
	private static function scanForDangerousStrings( string $content ): ?string {
		$content_lower = strtolower( $content );

		foreach ( self::$dangerous_strings as $dangerous ) {
			// Handle regex patterns
			if ( strpos( $dangerous, '.*' ) !== false ) {
				if ( preg_match( '/' . $dangerous . '/i', $content_lower ) ) {
					return $dangerous;
				}
			} elseif ( strpos( $content_lower, strtolower( $dangerous ) ) !== false ) {
				return $dangerous;
			}
		}

		return null;
	}

	/**
	 * Verify image has valid magic bytes/signature.
	 *
	 * @param string $content File content.
	 * @return bool True if valid image signature.
	 */
	private static function verifyImageSignature( string $content ): bool {
		$hex = strtoupper( bin2hex( substr( $content, 0, 8 ) ) );

		foreach ( self::$allowed_image_signatures as $signature => $name ) {
			if ( strpos( $hex, $signature ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for double extensions.
	 *
	 * @param string $filename The filename to check.
	 * @return bool True if suspicious double extension found.
	 */
	private static function hasDoubleExtension( string $filename ): bool {
		$dangerous_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'vbs' );
		$parts                = explode( '.', strtolower( $filename ) );

		// Need at least 3 parts for double extension
		if ( count( $parts ) < 3 ) {
			return false;
		}

		// Check if any middle part is a dangerous extension
		for ( $i = 0; $i < count( $parts ) - 1; $i++ ) {
			if ( in_array( $parts[ $i ], $dangerous_extensions, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for hidden executable content within the file.
	 *
	 * @param string $content File content.
	 * @return bool True if hidden executable detected.
	 */
	private static function hasHiddenExecutable( string $content ): bool {
		// Look for executable signatures after image data
		// This catches images with appended malicious content
		$hex = strtoupper( bin2hex( $content ) );

		// Check for executable signatures anywhere in the file
		foreach ( self::$dangerous_signatures as $signature => $description ) {
			// Skip PDF check here (handled separately)
			if ( $signature === '25504446' ) {
				continue;
			}

			// Don't flag if it's at the very beginning (would be caught by type check)
			$pos = strpos( $hex, $signature );
			if ( $pos !== false && $pos > 16 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Scan PDF for potential threats.
	 *
	 * @param string $content PDF content.
	 * @return array Scan result.
	 */
	private static function scanPdfForThreats( string $content ): array {
		// Dangerous PDF elements
		$pdf_threats = array(
			'/JavaScript',
			'/JS ',
			'/Launch',
			'/OpenAction',
			'/AA ',
			'/AcroForm',
			'/EmbeddedFile',
			'/XFA',
		);

		foreach ( $pdf_threats as $threat ) {
			if ( stripos( $content, $threat ) !== false ) {
				return array(
					'safe'    => false,
					'message' => __( 'O PDF contém elementos potencialmente perigosos.', 'apollo-social' ),
					'code'    => 'pdf_threat',
				);
			}
		}

		return array(
			'safe'    => true,
			'message' => __( 'PDF verificado.', 'apollo-social' ),
			'code'    => 'ok',
		);
	}

	/**
	 * Log security threat for admin review.
	 *
	 * @param string $file_path  Path to the suspicious file.
	 * @param string $threat_type Type of threat detected.
	 * @param string $details    Additional details.
	 */
	private static function logSecurityThreat( string $file_path, string $threat_type, string $details ): void {
		$user_id = get_current_user_id();
		$ip      = self::getClientIp();

		$log_entry = sprintf(
			'[Apollo Security] Threat detected: Type=%s, File=%s, User=%d, IP=%s, Details=%s',
			$threat_type,
			basename( $file_path ),
			$user_id,
			$ip,
			$details
		);

		// Log to WordPress error log
		error_log( $log_entry );

		// Optional: Log to database for admin review
		$threats   = get_option( 'apollo_security_threats', array() );
		$threats[] = array(
			'timestamp' => current_time( 'mysql' ),
			'type'      => $threat_type,
			'file'      => basename( $file_path ),
			'user_id'   => $user_id,
			'ip'        => $ip,
			'details'   => $details,
		);

		// Keep only last 100 threats
		$threats = array_slice( $threats, -100 );
		update_option( 'apollo_security_threats', $threats );

		// Fire action for additional handling (email admin, etc.)
		do_action( 'apollo_security_threat_detected', $threat_type, $file_path, $details, $user_id, $ip );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP.
	 */
	private static function getClientIp(): string {
		$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated list (X-Forwarded-For)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Sanitize uploaded filename.
	 *
	 * @param string $filename Original filename.
	 * @return string Sanitized filename.
	 */
	public static function sanitizeFilename( string $filename ): string {
		// Remove path components
		$filename = basename( $filename );

		// Use WordPress sanitization
		$filename = sanitize_file_name( $filename );

		// Additional cleaning
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '', $filename );

		// Ensure it has a valid extension
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( empty( $extension ) ) {
			return '';
		}

		return $filename;
	}
}
