<?php
/**
 * Apollo REST Controller Base
 *
 * Base controller class for all Apollo REST API endpoints.
 * Provides standardized response format, error handling, and common utilities.
 *
 * @package Apollo_Core
 * @subpackage REST_API
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core\REST_API;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_REST_Controller
 *
 * Abstract base controller extending WP_REST_Controller.
 *
 * @since 2.0.0
 */
abstract class Apollo_REST_Controller extends \WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $namespace = 'apollo/v1';

	/**
	 * Default items per page.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	protected int $per_page_default = 10;

	/**
	 * Maximum items per page.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	protected int $per_page_max = 100;

	/**
	 * Create a standardized success response.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed  $data    Response data.
	 * @param string $message Optional success message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	protected function success( $data = null, string $message = '', int $status = 200 ): \WP_REST_Response {
		$response = array(
			'success' => true,
			'data'    => $data,
			'message' => $message,
		);

		return new \WP_REST_Response( $response, $status );
	}

	/**
	 * Create a standardized error response.
	 *
	 * @since 2.0.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @param array  $data    Additional error data.
	 * @return \WP_Error
	 */
	protected function error( string $code, string $message, int $status = 400, array $data = array() ): \WP_Error {
		return new \WP_Error(
			$code,
			$message,
			array_merge( array( 'status' => $status ), $data )
		);
	}

	/**
	 * Create a paginated response.
	 *
	 * @since 2.0.0
	 *
	 * @param array            $items       Array of items.
	 * @param int              $total       Total items count.
	 * @param int              $page        Current page.
	 * @param int              $per_page    Items per page.
	 * @param \WP_REST_Request $request     The request object.
	 * @return \WP_REST_Response
	 */
	protected function paginated_response(
		array $items,
		int $total,
		int $page,
		int $per_page,
		\WP_REST_Request $request
	): \WP_REST_Response {
		$total_pages = (int) ceil( $total / $per_page );

		$response = $this->success(
			array(
				'items'       => $items,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			)
		);

		// Add pagination headers.
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		// Add Link headers for pagination.
		$base  = rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) );
		$links = array();

		if ( $page > 1 ) {
			$links[] = sprintf( '<%s?page=%d&per_page=%d>; rel="prev"', $base, $page - 1, $per_page );
		}
		if ( $page < $total_pages ) {
			$links[] = sprintf( '<%s?page=%d&per_page=%d>; rel="next"', $base, $page + 1, $per_page );
		}

		if ( ! empty( $links ) ) {
			$response->header( 'Link', implode( ', ', $links ) );
		}

		return $response;
	}

	/**
	 * Get pagination parameters from request.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array{page: int, per_page: int, offset: int}
	 */
	protected function get_pagination_params( \WP_REST_Request $request ): array {
		$page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
		$per_page = (int) $request->get_param( 'per_page' ) ?: $this->per_page_default;
		$per_page = min( $per_page, $this->per_page_max );
		$offset   = ( $page - 1 ) * $per_page;

		return array(
			'page'     => $page,
			'per_page' => $per_page,
			'offset'   => $offset,
		);
	}

	/**
	 * Check if current user can read (public access).
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool
	 */
	public function check_read_permission( \WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Check if current user can create items.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_create_permission( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->error(
				'rest_not_logged_in',
				__( 'Você precisa estar logado para realizar esta ação.', 'apollo-core' ),
				401
			);
		}

		return true;
	}

	/**
	 * Check if current user can update items.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_update_permission( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->error(
				'rest_not_logged_in',
				__( 'Você precisa estar logado para realizar esta ação.', 'apollo-core' ),
				401
			);
		}

		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post ) {
			return $this->error(
				'rest_post_not_found',
				__( 'Item não encontrado.', 'apollo-core' ),
				404
			);
		}

		if ( ! current_user_can( 'edit_post', $id ) ) {
			return $this->error(
				'rest_forbidden',
				__( 'Você não tem permissão para editar este item.', 'apollo-core' ),
				403
			);
		}

		return true;
	}

	/**
	 * Check if current user can delete items.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function check_delete_permission( \WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return $this->error(
				'rest_not_logged_in',
				__( 'Você precisa estar logado para realizar esta ação.', 'apollo-core' ),
				401
			);
		}

		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post ) {
			return $this->error(
				'rest_post_not_found',
				__( 'Item não encontrado.', 'apollo-core' ),
				404
			);
		}

		if ( ! current_user_can( 'delete_post', $id ) ) {
			return $this->error(
				'rest_forbidden',
				__( 'Você não tem permissão para excluir este item.', 'apollo-core' ),
				403
			);
		}

		return true;
	}

	/**
	 * Verify nonce for REST request.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @param string           $action  Nonce action.
	 * @return bool|\WP_Error
	 */
	protected function verify_nonce( \WP_REST_Request $request, string $action = 'wp_rest' ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return $this->error(
				'rest_nonce_invalid',
				__( 'Nonce inválido. Por favor, recarregue a página.', 'apollo-core' ),
				403
			);
		}

		return true;
	}

	/**
	 * Sanitize text input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	protected function sanitize_text( $value ): string {
		return sanitize_text_field( wp_unslash( $value ?? '' ) );
	}

	/**
	 * Sanitize textarea input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	protected function sanitize_textarea( $value ): string {
		return sanitize_textarea_field( wp_unslash( $value ?? '' ) );
	}

	/**
	 * Sanitize HTML input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	protected function sanitize_html( $value ): string {
		return wp_kses_post( wp_unslash( $value ?? '' ) );
	}

	/**
	 * Sanitize integer input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	protected function sanitize_int( $value ): int {
		return (int) $value;
	}

	/**
	 * Sanitize float input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return float
	 */
	protected function sanitize_float( $value ): float {
		return (float) $value;
	}

	/**
	 * Sanitize boolean input.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return bool
	 */
	protected function sanitize_bool( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize array of integers.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	protected function sanitize_int_array( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = explode( ',', (string) $value );
		}

		return array_map( 'intval', array_filter( $value ) );
	}

	/**
	 * Sanitize date input (Y-m-d format).
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return string|null
	 */
	protected function sanitize_date( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$date = \DateTime::createFromFormat( 'Y-m-d', $value );
		if ( $date && $date->format( 'Y-m-d' ) === $value ) {
			return $value;
		}

		return null;
	}

	/**
	 * Sanitize datetime input (Y-m-d H:i:s format).
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Input value.
	 * @return string|null
	 */
	protected function sanitize_datetime( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$datetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
		if ( $datetime && $datetime->format( 'Y-m-d H:i:s' ) === $value ) {
			return $value;
		}

		// Try ISO 8601 format.
		$datetime = \DateTime::createFromFormat( \DateTime::ATOM, $value );
		if ( $datetime ) {
			return $datetime->format( 'Y-m-d H:i:s' );
		}

		return null;
	}

	/**
	 * Get common collection parameters.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Página atual.', 'apollo-core' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Itens por página.', 'apollo-core' ),
				'type'              => 'integer',
				'default'           => $this->per_page_default,
				'minimum'           => 1,
				'maximum'           => $this->per_page_max,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => __( 'Termo de busca.', 'apollo-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'  => array(
				'description' => __( 'Campo de ordenação.', 'apollo-core' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'title', 'id', 'modified', 'relevance' ),
			),
			'order'    => array(
				'description' => __( 'Direção da ordenação.', 'apollo-core' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			),
		);
	}

	/**
	 * Prepare a post for response.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function prepare_post_for_response( \WP_Post $post, \WP_REST_Request $request ): array {
		$data = array(
			'id'           => $post->ID,
			'title'        => get_the_title( $post ),
			'slug'         => $post->post_name,
			'status'       => $post->post_status,
			'date'         => mysql_to_rfc3339( $post->post_date ),
			'date_gmt'     => mysql_to_rfc3339( $post->post_date_gmt ),
			'modified'     => mysql_to_rfc3339( $post->post_modified ),
			'modified_gmt' => mysql_to_rfc3339( $post->post_modified_gmt ),
			'content'      => apply_filters( 'the_content', $post->post_content ),
			'excerpt'      => get_the_excerpt( $post ),
			'author'       => (int) $post->post_author,
			'link'         => get_permalink( $post ),
		);

		// Add featured image.
		$thumbnail_id = get_post_thumbnail_id( $post );
		if ( $thumbnail_id ) {
			$data['featured_image'] = array(
				'id'        => $thumbnail_id,
				'url'       => wp_get_attachment_url( $thumbnail_id ),
				'thumbnail' => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ),
				'medium'    => wp_get_attachment_image_url( $thumbnail_id, 'medium' ),
				'large'     => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
				'alt'       => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
			);
		} else {
			$data['featured_image'] = null;
		}

		return $data;
	}

	/**
	 * Prepare author data for response.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id The user ID.
	 * @return array|null
	 */
	protected function prepare_author_for_response( int $user_id ): ?array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		return array(
			'id'          => $user->ID,
			'name'        => $user->display_name,
			'slug'        => $user->user_nicename,
			'avatar'      => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
			'description' => get_user_meta( $user->ID, 'description', true ),
			'link'        => get_author_posts_url( $user->ID ),
		);
	}

	/**
	 * Prepare taxonomy terms for response.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $taxonomy The taxonomy slug.
	 * @return array
	 */
	protected function prepare_terms_for_response( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			function ( $term ) {
				return array(
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => $term->count,
					'link'  => get_term_link( $term ),
				);
			},
			$terms
		);
	}

	/**
	 * Log API request for debugging.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @param string           $context Additional context.
	 * @return void
	 */
	protected function log_request( \WP_REST_Request $request, string $context = '' ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_data = array(
			'route'   => $request->get_route(),
			'method'  => $request->get_method(),
			'params'  => $request->get_params(),
			'context' => $context,
			'user_id' => get_current_user_id(),
			'time'    => current_time( 'mysql' ),
		);

		if ( function_exists( 'apollo_log_once' ) ) {
			apollo_log_once( 'rest_request_' . md5( $request->get_route() ), wp_json_encode( $log_data ), 'debug' );
		}
	}
}
