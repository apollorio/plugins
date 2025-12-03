<?php
/**
 * CPF Validator
 * Same validator used in SIGN DOC functionality
 * Validates Brazilian CPF (Cadastro de Pessoa Física)
 */

namespace Apollo\Helpers;

class CPFValidator {

	/**
	 * Validate CPF format and checksum
	 * ✅ SAME VALIDATOR USED IN SIGN DOC FUNCTIONALITY
	 *
	 * @param string $cpf CPF to validate (with or without formatting)
	 * @return bool True if valid, false otherwise
	 */
	public static function validate( string $cpf ): bool {
		// Remove formatting
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		// Check length
		if ( strlen( $cpf ) !== 11 ) {
			return false;
		}

		// Check for invalid sequences (all same digits)
		if ( preg_match( '/(\d)\1{10}/', $cpf ) ) {
			return false;
		}

		// ✅ SAME ALGORITHM AS DocumentsManager::validateCPF()
		// Validate first checksum digit
		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += intval( $cpf[ $i ] ) * ( 10 - $i );
		}
		$digit1 = ( $sum % 11 < 2 ) ? 0 : 11 - ( $sum % 11 );
		if ( intval( $cpf[9] ) !== $digit1 ) {
			return false;
		}

		// Validate second checksum digit
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
	 * Format CPF (XXX.XXX.XXX-XX)
	 *
	 * @param string $cpf CPF to format
	 * @return string Formatted CPF
	 */
	public static function format( string $cpf ): string {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );
		return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf );
	}

	/**
	 * Sanitize CPF (remove formatting)
	 *
	 * @param string $cpf CPF to sanitize
	 * @return string Sanitized CPF (numbers only)
	 */
	public static function sanitize( string $cpf ): string {
		return preg_replace( '/[^0-9]/', '', $cpf );
	}
}
