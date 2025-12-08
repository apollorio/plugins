<?php
namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\User;

/**
 * Base Controller for REST API
 */
abstract class BaseController {

	protected $nonce_action = 'apollo_api';

	/**
	 * Validate nonce for POST requests
	 */
	protected function validateNonce(): bool {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return true;
			// GET requests don't need nonce
		}

		$nonce = $_REQUEST['_wpnonce'] ?? $_REQUEST['nonce'] ?? '';

		if ( empty( $nonce ) ) {
			return false;
		}

		// TODO: In real WordPress context, use wp_verify_nonce
		// return wp_verify_nonce($nonce, $this->nonce_action);

		// Mock validation for development
		return ! empty( $nonce );
	}

	/**
	 * Get current user
	 */
	protected function getCurrentUser(): ?User {
		// TODO: In real WordPress context, use wp_get_current_user
		// $wp_user = wp_get_current_user();
		// return new User([...]);

		// Mock user for development
		return new User(
			[
				'id'           => 1,
				'login'        => 'testuser',
				'email'        => 'test@example.com',
				'display_name' => 'Test User',
				'roles'        => [ 'subscriber' ],
				'capabilities' => [],
			]
		);
	}

	/**
	 * Sanitize input parameters
	 */
	protected function sanitizeParams( array $params ): array {
		$sanitized = [];

		foreach ( $params as $key => $value ) {
			if ( is_string( $value ) ) {
				// TODO: Use WordPress sanitization functions
				// $sanitized[$key] = sanitize_text_field($value);
				$sanitized[ $key ] = trim( strip_tags( $value ) );
			} elseif ( is_int( $value ) ) {
				$sanitized[ $key ] = intval( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitizeParams( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Send JSON response
	 */
	protected function jsonResponse( array $data, int $status = 200 ): void {
		http_response_code( $status );
		header( 'Content-Type: application/json' );
		echo json_encode( $data );
		exit;
	}

	/**
	 * Send success response
	 */
	protected function success( $data = null, string $message = 'Success' ): void {
		$response = [
			'ok'      => true,
			'message' => $message,
		];

		if ( $data !== null ) {
			$response['data'] = $data;
		}

		$this->jsonResponse( $response, 200 );
	}

	/**
	 * Send error response
	 */
	protected function error( string $message, int $code = 400, $details = null ): void {
		$response = [
			'ok'    => false,
			'error' => $message,
			'code'  => $code,
		];

		if ( $details !== null ) {
			$response['details'] = $details;
		}

		$this->jsonResponse( $response, $code );
	}

	/**
	 * Send validation error
	 */
	protected function validationError( string $message, $details = null ): void {
		$this->error( $message, 422, $details );
	}

	/**
	 * Send permission error
	 */
	protected function permissionError( string $message = 'Permission denied' ): void {
		$this->error( $message, 403 );
	}

	/**
	 * Send authentication error
	 */
	protected function authError( string $message = 'Authentication required' ): void {
		$this->error( $message, 401 );
	}
}
