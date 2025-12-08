<?php
/**
 * Document Signing Access Helper
 * Centralized check for document signing eligibility
 *
 * Rule: Only users with VALID CPF can sign documents.
 * Passport users CANNOT sign (Brazilian law requirement).
 *
 * @package Apollo\Helpers
 * @version 2.0.0
 */

namespace Apollo\Helpers;

class DocumentSigningAccess {

	/**
	 * Check if user can sign documents
	 *
	 * @param int|null $user_id User ID (defaults to current user)
	 * @return array Result with 'can_sign' boolean and 'reason' string
	 */
	public static function check( ?int $user_id = null ): array {
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return [
				'can_sign' => false,
				'reason'   => __( 'Você precisa estar logado para assinar documentos.', 'apollo-social' ),
				'code'     => 'not_logged_in',
			];
		}

		// Get user document data
		$cpf = get_user_meta( $user_id, 'apollo_cpf', true );
		if ( ! $cpf ) {
			// Fallback to old meta key
			$cpf = get_user_meta( $user_id, '_user_cpf', true );
		}

		$passport      = get_user_meta( $user_id, 'apollo_passport', true );
		$doc_type      = get_user_meta( $user_id, 'apollo_doc_type', true );
		$can_sign_meta = get_user_meta( $user_id, 'apollo_can_sign_documents', true );

		// Validate CPF
		$cpf_clean     = preg_replace( '/[^0-9]/', '', $cpf ?? '' );
		$has_valid_cpf = strlen( $cpf_clean ) === 11 && self::validateCpf( $cpf_clean );

		// Check passport only
		$has_passport_only = ! $has_valid_cpf && ! empty( $passport );

		// Determine result
		if ( $has_valid_cpf ) {
			return [
				'can_sign'   => true,
				'reason'     => '',
				'code'       => 'cpf_valid',
				'cpf_masked' => self::maskCpf( $cpf_clean ),
			];
		}

		if ( $has_passport_only ) {
			return [
				'can_sign' => false,
				'reason'   => __( 'Usuários com passaporte não podem assinar documentos digitais. A assinatura digital requer CPF válido conforme legislação brasileira (Lei 14.063/2020).', 'apollo-social' ),
				'code'     => 'passport_only',
			];
		}

		return [
			'can_sign' => false,
			'reason'   => __( 'Você precisa cadastrar um CPF válido no seu perfil para assinar documentos digitais.', 'apollo-social' ),
			'code'     => 'no_cpf',
		];
	}

	/**
	 * Quick check if user can sign
	 *
	 * @param int|null $user_id User ID
	 * @return bool
	 */
	public static function canSign( ?int $user_id = null ): bool {
		$result = self::check( $user_id );
		return $result['can_sign'];
	}

	/**
	 * Get block reason for display
	 *
	 * @param int|null $user_id User ID
	 * @return string Empty if can sign, otherwise reason string
	 */
	public static function getBlockReason( ?int $user_id = null ): string {
		$result = self::check( $user_id );
		return $result['reason'] ?? '';
	}

	/**
	 * Validate CPF checksum
	 * Same algorithm as CPFValidator::validate()
	 *
	 * @param string $cpf CPF numbers only (11 digits)
	 * @return bool
	 */
	private static function validateCpf( string $cpf ): bool {
		// Check for invalid sequences (all same digits)
		if ( preg_match( '/(\d)\1{10}/', $cpf ) ) {
			return false;
		}

		// First digit
		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += intval( $cpf[ $i ] ) * ( 10 - $i );
		}
		$digit1 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );
		if ( intval( $cpf[9] ) !== $digit1 ) {
			return false;
		}

		// Second digit
		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += intval( $cpf[ $i ] ) * ( 11 - $i );
		}
		$digit2 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );
		if ( intval( $cpf[10] ) !== $digit2 ) {
			return false;
		}

		return true;
	}

	/**
	 * Mask CPF for display
	 *
	 * @param string $cpf CPF numbers only
	 * @return string Masked CPF (***.$2.***-**)
	 */
	private static function maskCpf( string $cpf ): string {
		if ( strlen( $cpf ) !== 11 ) {
			return '***.***.***-**';
		}
		return '***.' . substr( $cpf, 3, 3 ) . '.***-**';
	}
}

/**
 * Global helper function
 */
if ( ! function_exists( 'apollo_user_can_sign_documents' ) ) {
	/**
	 * Check if user can sign documents
	 *
	 * @param int|null $user_id User ID (defaults to current user)
	 * @return bool
	 */
	function apollo_user_can_sign_documents( ?int $user_id = null ): bool {
		return \Apollo\Helpers\DocumentSigningAccess::canSign( $user_id );
	}
}

if ( ! function_exists( 'apollo_get_sign_block_reason' ) ) {
	/**
	 * Get reason why user cannot sign
	 *
	 * @param int|null $user_id User ID (defaults to current user)
	 * @return string Empty if can sign
	 */
	function apollo_get_sign_block_reason( ?int $user_id = null ): string {
		return \Apollo\Helpers\DocumentSigningAccess::getBlockReason( $user_id );
	}
}
