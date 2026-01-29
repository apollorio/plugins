<?php
/**
 * Apollo REST Response Format
 *
 * Unified response format for all Apollo REST endpoints.
 * Provides consistent structure, pagination, and error handling.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\REST_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_REST_Response
 *
 * Unified response helper for REST API.
 */
class Apollo_REST_Response {

	/**
	 * Response codes
	 */
	public const CODE_SUCCESS      = 'success';
	public const CODE_CREATED      = 'created';
	public const CODE_UPDATED      = 'updated';
	public const CODE_DELETED      = 'deleted';
	public const CODE_ERROR        = 'error';
	public const CODE_NOT_FOUND    = 'not_found';
	public const CODE_UNAUTHORIZED = 'unauthorized';
	public const CODE_FORBIDDEN    = 'forbidden';
	public const CODE_INVALID      = 'invalid_request';
	public const CODE_CONFLICT     = 'conflict';
	public const CODE_RATE_LIMITED = 'rate_limited';
	public const CODE_SERVER_ERROR = 'server_error';

	/**
	 * Create a success response
	 *
	 * @param mixed  $data    Response data.
	 * @param string $message Optional message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	public static function success(
		mixed $data = null,
		string $message = '',
		int $status = 200
	): \WP_REST_Response {
		$response_data = array(
			'success' => true,
			'code'    => self::CODE_SUCCESS,
			'data'    => $data,
		);

		if ( ! empty( $message ) ) {
			$response_data['message'] = $message;
		}

		$response_data['meta'] = self::get_meta();

		return new \WP_REST_Response( $response_data, $status );
	}

	/**
	 * Create a created response
	 *
	 * @param mixed  $data    Created resource data.
	 * @param string $message Optional message.
	 * @return \WP_REST_Response
	 */
	public static function created(
		mixed $data = null,
		string $message = ''
	): \WP_REST_Response {
		$response_data = array(
			'success' => true,
			'code'    => self::CODE_CREATED,
			'data'    => $data,
			'message' => $message ?: __( 'Resource created successfully.', 'apollo-core' ),
		);

		$response_data['meta'] = self::get_meta();

		return new \WP_REST_Response( $response_data, 201 );
	}

	/**
	 * Create an updated response
	 *
	 * @param mixed  $data    Updated resource data.
	 * @param string $message Optional message.
	 * @return \WP_REST_Response
	 */
	public static function updated(
		mixed $data = null,
		string $message = ''
	): \WP_REST_Response {
		$response_data = array(
			'success' => true,
			'code'    => self::CODE_UPDATED,
			'data'    => $data,
			'message' => $message ?: __( 'Resource updated successfully.', 'apollo-core' ),
		);

		$response_data['meta'] = self::get_meta();

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Create a deleted response
	 *
	 * @param mixed  $data    Deleted resource info.
	 * @param string $message Optional message.
	 * @return \WP_REST_Response
	 */
	public static function deleted(
		mixed $data = null,
		string $message = ''
	): \WP_REST_Response {
		$response_data = array(
			'success' => true,
			'code'    => self::CODE_DELETED,
			'data'    => $data,
			'message' => $message ?: __( 'Resource deleted successfully.', 'apollo-core' ),
		);

		$response_data['meta'] = self::get_meta();

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Create an error response
	 *
	 * @param string $message Error message.
	 * @param string $code    Error code.
	 * @param int    $status  HTTP status code.
	 * @param array  $details Additional error details.
	 * @return \WP_REST_Response
	 */
	public static function error(
		string $message,
		string $code = self::CODE_ERROR,
		int $status = 400,
		array $details = array()
	): \WP_REST_Response {
		$response_data = array(
			'success' => false,
			'code'    => $code,
			'message' => $message,
		);

		if ( ! empty( $details ) ) {
			$response_data['details'] = $details;
		}

		$response_data['meta'] = self::get_meta();

		return new \WP_REST_Response( $response_data, $status );
	}

	/**
	 * Create a WP_Error-based response
	 *
	 * @param \WP_Error $error  WP_Error object.
	 * @param int       $status HTTP status code.
	 * @return \WP_REST_Response
	 */
	public static function from_wp_error(
		\WP_Error $error,
		int $status = 400
	): \WP_REST_Response {
		$codes    = $error->get_error_codes();
		$messages = array();
		$details  = array();

		foreach ( $codes as $code ) {
			$messages[] = $error->get_error_message( $code );
			$error_data = $error->get_error_data( $code );
			if ( $error_data ) {
				$details[ $code ] = $error_data;
			}
		}

		return self::error(
			\implode( ' ', $messages ),
			$codes[0] ?? self::CODE_ERROR,
			$status,
			$details
		);
	}

	/**
	 * Create a not found response
	 *
	 * @param string $resource Resource type (e.g., 'Event', 'DJ').
	 * @param mixed  $id       Resource ID.
	 * @return \WP_REST_Response
	 */
	public static function not_found(
		string $resource = 'Resource',
		mixed $id = null
	): \WP_REST_Response {
		$message = $id
			? \sprintf( __( '%1$s with ID %2$s not found.', 'apollo-core' ), $resource, $id )
			: \sprintf( __( '%s not found.', 'apollo-core' ), $resource );

		return self::error( $message, self::CODE_NOT_FOUND, 404 );
	}

	/**
	 * Create an unauthorized response
	 *
	 * @param string $message Optional custom message.
	 * @return \WP_REST_Response
	 */
	public static function unauthorized( string $message = '' ): \WP_REST_Response {
		return self::error(
			$message ?: __( 'Authentication required.', 'apollo-core' ),
			self::CODE_UNAUTHORIZED,
			401
		);
	}

	/**
	 * Create a forbidden response
	 *
	 * @param string $message Optional custom message.
	 * @return \WP_REST_Response
	 */
	public static function forbidden( string $message = '' ): \WP_REST_Response {
		return self::error(
			$message ?: __( 'You do not have permission to perform this action.', 'apollo-core' ),
			self::CODE_FORBIDDEN,
			403
		);
	}

	/**
	 * Create a validation error response
	 *
	 * @param array $errors Validation errors (field => message).
	 * @return \WP_REST_Response
	 */
	public static function validation_error( array $errors ): \WP_REST_Response {
		return self::error(
			__( 'Validation failed.', 'apollo-core' ),
			self::CODE_INVALID,
			422,
			array( 'validation_errors' => $errors )
		);
	}

	/**
	 * Create a rate limited response
	 *
	 * @param int    $retry_after Seconds until retry is allowed.
	 * @param string $message     Optional custom message.
	 * @return \WP_REST_Response
	 */
	public static function rate_limited(
		int $retry_after = 60,
		string $message = ''
	): \WP_REST_Response {
		$response = self::error(
			$message ?: __( 'Too many requests. Please slow down.', 'apollo-core' ),
			self::CODE_RATE_LIMITED,
			429
		);

		$response->header( 'Retry-After', (string) $retry_after );
		$response->header( 'X-RateLimit-Reset', (string) ( \time() + $retry_after ) );

		return $response;
	}

	/**
	 * Create a paginated response
	 *
	 * @param array $items      Items to paginate.
	 * @param int   $total      Total items count.
	 * @param int   $page       Current page.
	 * @param int   $per_page   Items per page.
	 * @param array $extra_meta Additional meta data.
	 * @return \WP_REST_Response
	 */
	public static function paginated(
		array $items,
		int $total,
		int $page = 1,
		int $per_page = 10,
		array $extra_meta = array()
	): \WP_REST_Response {
		$total_pages = (int) \ceil( $total / $per_page );

		$pagination = array(
			'total'        => $total,
			'count'        => \count( $items ),
			'per_page'     => $per_page,
			'current_page' => $page,
			'total_pages'  => $total_pages,
			'has_previous' => $page > 1,
			'has_next'     => $page < $total_pages,
		);

		if ( $page > 1 ) {
			$pagination['previous_page'] = $page - 1;
		}

		if ( $page < $total_pages ) {
			$pagination['next_page'] = $page + 1;
		}

		$response_data = array(
			'success'    => true,
			'code'       => self::CODE_SUCCESS,
			'data'       => $items,
			'pagination' => $pagination,
			'meta'       => \array_merge( self::get_meta(), $extra_meta ),
		);

		$response = new \WP_REST_Response( $response_data, 200 );

		// Add Link headers for pagination.
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Create a collection response from WP_Query
	 *
	 * @param \WP_Query $query       WP_Query object.
	 * @param callable  $transformer Function to transform each post.
	 * @param array     $extra_meta  Additional meta data.
	 * @return \WP_REST_Response
	 */
	public static function from_query(
		\WP_Query $query,
		callable $transformer,
		array $extra_meta = array()
	): \WP_REST_Response {
		$items = \array_map( $transformer, $query->posts );

		return self::paginated(
			$items,
			$query->found_posts,
			$query->get( 'paged' ) ?: 1,
			$query->get( 'posts_per_page' ),
			$extra_meta
		);
	}

	/**
	 * Create a collection response with links
	 *
	 * @param array  $items       Items.
	 * @param int    $total       Total count.
	 * @param string $base_url    Base URL for pagination links.
	 * @param int    $page        Current page.
	 * @param int    $per_page    Items per page.
	 * @return \WP_REST_Response
	 */
	public static function collection(
		array $items,
		int $total,
		string $base_url,
		int $page = 1,
		int $per_page = 10
	): \WP_REST_Response {
		$total_pages = (int) \ceil( $total / $per_page );

		$links = array();

		// First page.
		$links['first'] = \add_query_arg(
			array(
				'page'     => 1,
				'per_page' => $per_page,
			),
			$base_url
		);

		// Last page.
		$links['last'] = \add_query_arg(
			array(
				'page'     => $total_pages,
				'per_page' => $per_page,
			),
			$base_url
		);

		// Previous page.
		if ( $page > 1 ) {
			$links['prev'] = \add_query_arg(
				array(
					'page'     => $page - 1,
					'per_page' => $per_page,
				),
				$base_url
			);
		}

		// Next page.
		if ( $page < $total_pages ) {
			$links['next'] = \add_query_arg(
				array(
					'page'     => $page + 1,
					'per_page' => $per_page,
				),
				$base_url
			);
		}

		$response = self::paginated( $items, $total, $page, $per_page );

		// Add links to response data.
		$data          = $response->get_data();
		$data['links'] = $links;
		$response->set_data( $data );

		// Add Link header.
		$link_header_parts = array();
		foreach ( $links as $rel => $url ) {
			$link_header_parts[] = \sprintf( '<%s>; rel="%s"', $url, $rel );
		}
		$response->header( 'Link', \implode( ', ', $link_header_parts ) );

		return $response;
	}

	/**
	 * Create a no content response
	 *
	 * @return \WP_REST_Response
	 */
	public static function no_content(): \WP_REST_Response {
		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Create an accepted response (for async operations)
	 *
	 * @param string $job_id     Job/task ID for tracking.
	 * @param string $status_url URL to check status.
	 * @return \WP_REST_Response
	 */
	public static function accepted(
		string $job_id,
		string $status_url = ''
	): \WP_REST_Response {
		$response_data = array(
			'success' => true,
			'code'    => 'accepted',
			'message' => __( 'Request accepted. Processing in background.', 'apollo-core' ),
			'data'    => array(
				'job_id'     => $job_id,
				'status_url' => $status_url,
			),
			'meta'    => self::get_meta(),
		);

		$response = new \WP_REST_Response( $response_data, 202 );

		if ( $status_url ) {
			$response->header( 'Location', $status_url );
		}

		return $response;
	}

	/**
	 * Get response meta
	 *
	 * @return array
	 */
	private static function get_meta(): array {
		return array(
			'api_version' => Apollo_REST_Namespace::get_version(),
			'timestamp'   => \gmdate( 'c' ),
		);
	}

	/**
	 * Add standard headers to response
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @return \WP_REST_Response
	 */
	public static function with_standard_headers(
		\WP_REST_Response $response
	): \WP_REST_Response {
		$response->header( 'X-Apollo-API-Version', Apollo_REST_Namespace::get_version() );
		$response->header( 'X-Apollo-Namespace', Apollo_REST_Namespace::V1 );
		$response->header( 'X-Content-Type-Options', 'nosniff' );
		$response->header( 'X-Frame-Options', 'DENY' );

		return $response;
	}

	/**
	 * Add cache headers
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param int               $max_age  Cache max age in seconds.
	 * @param bool              $private  Whether cache is private.
	 * @return \WP_REST_Response
	 */
	public static function with_cache_headers(
		\WP_REST_Response $response,
		int $max_age = 300,
		bool $private = false
	): \WP_REST_Response {
		$cache_control  = $private ? 'private' : 'public';
		$cache_control .= ", max-age={$max_age}";

		$response->header( 'Cache-Control', $cache_control );
		$response->header( 'Expires', \gmdate( 'D, d M Y H:i:s', \time() + $max_age ) . ' GMT' );

		return $response;
	}

	/**
	 * Add no-cache headers
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @return \WP_REST_Response
	 */
	public static function with_no_cache(
		\WP_REST_Response $response
	): \WP_REST_Response {
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );

		return $response;
	}

	/**
	 * Wrap callback with unified response handling
	 *
	 * @param callable $callback Endpoint callback.
	 * @return callable
	 */
	public static function wrap( callable $callback ): callable {
		return function ( \WP_REST_Request $request ) use ( $callback ) {
			try {
				$result = $callback( $request );

				// If already a response, add standard headers.
				if ( $result instanceof \WP_REST_Response ) {
					return self::with_standard_headers( $result );
				}

				// If WP_Error, convert to response.
				if ( \is_wp_error( $result ) ) {
					return self::with_standard_headers( self::from_wp_error( $result ) );
				}

				// Wrap raw data in success response.
				return self::with_standard_headers( self::success( $result ) );

			} catch ( \Exception $e ) {
				// Log the error.
				\error_log( 'Apollo REST Error: ' . $e->getMessage() );

				$status = 500;
				if ( \method_exists( $e, 'getCode' ) && $e->getCode() >= 400 && $e->getCode() < 600 ) {
					$status = $e->getCode();
				}

				return self::with_standard_headers(
					self::error(
						$e->getMessage(),
						self::CODE_SERVER_ERROR,
						$status
					)
				);
			}
		};
	}
}
