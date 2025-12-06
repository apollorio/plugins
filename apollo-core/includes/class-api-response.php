<?php
declare(strict_types=1);

namespace Apollo\API;

use WP_REST_Response;
use WP_Error;

/**
 * Standardized API Response Handler
 *
 * Provides consistent REST API responses across all Apollo plugins.
 * All responses follow the same structure for predictable frontend handling.
 *
 * @package Apollo_Core
 * @since 1.3.0
 */
class Response {

	/**
	 * Standard error codes
	 */
	public const ERROR_UNAUTHORIZED = 'unauthorized';
	public const ERROR_FORBIDDEN    = 'forbidden';
	public const ERROR_NOT_FOUND    = 'not_found';
	public const ERROR_VALIDATION   = 'validation_error';
	public const ERROR_RATE_LIMIT   = 'rate_limit_exceeded';
	public const ERROR_SERVER       = 'server_error';
	public const ERROR_BAD_REQUEST  = 'bad_request';
	public const ERROR_CONFLICT     = 'conflict';
	public const ERROR_GONE         = 'gone';

	/**
	 * HTTP status code mapping
	 */
	private static array $statusCodes = array(
		self::ERROR_UNAUTHORIZED => 401,
		self::ERROR_FORBIDDEN    => 403,
		self::ERROR_NOT_FOUND    => 404,
		self::ERROR_VALIDATION   => 422,
		self::ERROR_RATE_LIMIT   => 429,
		self::ERROR_SERVER       => 500,
		self::ERROR_BAD_REQUEST  => 400,
		self::ERROR_CONFLICT     => 409,
		self::ERROR_GONE         => 410,
	);

	/**
	 * Create standardized error response
	 *
	 * @param string $code    Error code (use class constants)
	 * @param string $message Human-readable error message
	 * @param array  $data    Additional error data (optional)
	 * @param int    $status  HTTP status code (auto-detected from code if not provided)
	 *
	 * @return WP_REST_Response
	 *
	 * @example
	 * return Response::error(Response::ERROR_NOT_FOUND, 'Evento não encontrado');
	 * return Response::error(Response::ERROR_VALIDATION, 'Email inválido', ['field' => 'email']);
	 */
	public static function error(
		string $code,
		string $message,
		array $data = array(),
		?int $status = null
	): WP_REST_Response {
		$httpStatus = $status ?? ( self::$statusCodes[ $code ] ?? 400 );

		$response = array(
			'success' => false,
			'error'   => array(
				'code'      => $code,
				'message'   => $message,
				'timestamp' => current_time( 'c' ),
			),
		);

		if ( ! empty( $data ) ) {
			$response['error']['data'] = $data;
		}

		// Log server errors
		if ( $httpStatus >= 500 ) {
			self::logError( $code, $message, $data );
		}

		return new WP_REST_Response( $response, $httpStatus );
	}

	/**
	 * Create standardized success response
	 *
	 * @param mixed  $data    Response data
	 * @param string $message Success message (optional)
	 * @param int    $status  HTTP status code (default 200)
	 * @param array  $meta    Additional metadata (pagination, etc.)
	 *
	 * @return WP_REST_Response
	 *
	 * @example
	 * return Response::success(['user' => $user], 'Usuário criado');
	 * return Response::success($events, 'Eventos carregados', 200, ['total' => 100, 'page' => 1]);
	 */
	public static function success(
		$data = null,
		string $message = '',
		int $status = 200,
		array $meta = array()
	): WP_REST_Response {
		$response = array(
			'success' => true,
			'data'    => $data,
		);

		if ( $message ) {
			$response['message'] = $message;
		}

		if ( ! empty( $meta ) ) {
			$response['meta'] = $meta;
		}

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Create paginated response
	 *
	 * @param array  $items      Items for current page
	 * @param int    $total      Total items count
	 * @param int    $page       Current page
	 * @param int    $per_page   Items per page
	 * @param string $message   Success message (optional)
	 *
	 * @return WP_REST_Response
	 */
	public static function paginated(
		array $items,
		int $total,
		int $page = 1,
		int $per_page = 10,
		string $message = ''
	): WP_REST_Response {
		$total_pages = (int) ceil( $total / $per_page );

		return self::success(
			$items,
			$message,
			200,
			array(
				'pagination' => array(
					'total'        => $total,
					'per_page'     => $per_page,
					'current_page' => $page,
					'total_pages'  => $total_pages,
					'has_more'     => $page < $total_pages,
				),
			)
		);
	}

	/**
	 * Create created response (201)
	 *
	 * @param mixed  $data    Created resource data
	 * @param string $message Success message
	 *
	 * @return WP_REST_Response
	 */
	public static function created( $data, string $message = 'Recurso criado com sucesso' ): WP_REST_Response {
		return self::success( $data, $message, 201 );
	}

	/**
	 * Create no content response (204)
	 *
	 * @return WP_REST_Response
	 */
	public static function noContent(): WP_REST_Response {
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Create accepted response (202) - for async operations
	 *
	 * @param string $message Status message
	 * @param array  $data    Additional data (job ID, etc.)
	 *
	 * @return WP_REST_Response
	 */
	public static function accepted( string $message = 'Processando', array $data = array() ): WP_REST_Response {
		return self::success( $data, $message, 202 );
	}

	/**
	 * Convert WP_Error to standardized response
	 *
	 * @param WP_Error $error WordPress error object
	 *
	 * @return WP_REST_Response
	 */
	public static function fromWpError( WP_Error $error ): WP_REST_Response {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$data    = $error->get_error_data();

		return self::error(
			is_string( $code ) ? $code : self::ERROR_SERVER,
			$message,
			is_array( $data ) ? $data : array()
		);
	}

	/**
	 * Validation error helper
	 *
	 * @param array $errors Field => error message mapping
	 *
	 * @return WP_REST_Response
	 *
	 * @example
	 * return Response::validationError([
	 *     'email' => 'Email inválido',
	 *     'name' => 'Nome é obrigatório'
	 * ]);
	 */
	public static function validationError( array $errors ): WP_REST_Response {
		return self::error(
			self::ERROR_VALIDATION,
			'Erro de validação',
			array( 'fields' => $errors )
		);
	}

	/**
	 * Unauthorized helper
	 *
	 * @param string $message Custom message
	 *
	 * @return WP_REST_Response
	 */
	public static function unauthorized( string $message = 'Autenticação necessária' ): WP_REST_Response {
		return self::error( self::ERROR_UNAUTHORIZED, $message );
	}

	/**
	 * Forbidden helper
	 *
	 * @param string $message Custom message
	 *
	 * @return WP_REST_Response
	 */
	public static function forbidden( string $message = 'Permissão negada' ): WP_REST_Response {
		return self::error( self::ERROR_FORBIDDEN, $message );
	}

	/**
	 * Not found helper
	 *
	 * @param string $resource Resource name (e.g., 'Evento', 'Usuário')
	 *
	 * @return WP_REST_Response
	 */
	public static function notFound( string $resource = 'Recurso' ): WP_REST_Response {
		return self::error( self::ERROR_NOT_FOUND, "{$resource} não encontrado" );
	}

	/**
	 * Rate limit exceeded helper
	 *
	 * @param int $retry_after Seconds until retry allowed
	 *
	 * @return WP_REST_Response
	 */
	public static function rateLimited( int $retry_after = 60 ): WP_REST_Response {
		$response = self::error(
			self::ERROR_RATE_LIMIT,
			'Limite de requisições excedido',
			array( 'retry_after' => $retry_after )
		);

		$response->header( 'Retry-After', (string) $retry_after );
		return $response;
	}

	/**
	 * Log server errors
	 */
	private static function logError( string $code, string $message, array $data ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Apollo API Error] Code: %s | Message: %s | Data: %s',
					$code,
					$message,
					wp_json_encode( $data )
				)
			);
		}
	}
}

// Make class available globally
if ( ! function_exists( 'apollo_api_response' ) ) {
	/**
	 * Get Response class instance for static method access
	 *
	 * @return string Response class name
	 */
	function apollo_api_response(): string {
		return Response::class;
	}
}
