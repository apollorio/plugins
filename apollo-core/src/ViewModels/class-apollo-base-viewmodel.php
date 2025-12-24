<?php
/**
 * Base ViewModel Class - Apollo Design System
 *
 * Provides common functionality for all ViewModels that transform
 * WordPress data into approved DOM structures.
 *
 * @package ApolloCore\ViewModels
 */

abstract class Apollo_Base_ViewModel {
	/**
	 * Raw data from WordPress
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Transformed data for template consumption
	 *
	 * @var array
	 */
	protected $transformed_data = array();

	/**
	 * Validation errors
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructor
	 *
	 * @param mixed $data Raw WordPress data
	 */
	public function __construct( $data = null ) {
		if ( $data !== null ) {
			$this->set_data( $data );
		}
	}

	/**
	 * Set the raw data
	 *
	 * @param mixed $data
	 * @return $this
	 */
	public function set_data( $data ) {
		$this->data             = $data;
		$this->transformed_data = array();
		$this->errors           = array();
		return $this;
	}

	/**
	 * Get transformed data for template
	 *
	 * @return array
	 */
	public function get_template_data() {
		if ( empty( $this->transformed_data ) ) {
			$this->transform();
		}
		return $this->transformed_data;
	}

	/**
	 * Get calendar/agenda data.
	 *
	 * Override in child class to provide calendar-specific data.
	 *
	 * @return array
	 */
	public function get_calendar_agenda_data() {
		return $this->get_template_data();
	}

	/**
	 * Get dashboard access data.
	 *
	 * Override in child class to provide dashboard-specific data.
	 *
	 * @return array
	 */
	public function get_dashboard_access_data() {
		return $this->get_template_data();
	}

	/**
	 * Transform raw data into template-ready format
	 *
	 * @return void
	 */
	abstract protected function transform();

	/**
	 * Validate the data
	 *
	 * @return bool
	 */
	public function is_valid() {
		return empty( $this->errors );
	}

	/**
	 * Get validation errors
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Add a validation error
	 *
	 * @param string $field
	 * @param string $message
	 * @return void
	 */
	protected function add_error( $field, $message ) {
		$this->errors[ $field ][] = $message;
	}

	/**
	 * Sanitize and escape text
	 *
	 * @param string $text
	 * @return string
	 */
	protected function sanitize_text( $text ) {
		return esc_html( trim( $text ) );
	}

	/**
	 * Sanitize and escape URL
	 *
	 * @param string $url
	 * @return string
	 */
	protected function sanitize_url( $url ) {
		return esc_url( trim( $url ) );
	}

	/**
	 * Sanitize and escape attribute
	 *
	 * @param string $attr
	 * @return string
	 */
	protected function sanitize_attr( $attr ) {
		return esc_attr( trim( $attr ) );
	}

	/**
	 * Format date for display
	 *
	 * @param string|int $date
	 * @param string     $format
	 * @return string
	 */
	protected function format_date( $date, $format = 'M j' ) {
		if ( empty( $date ) ) {
			return '';
		}

		$timestamp = is_numeric( $date ) ? $date : strtotime( $date );
		return date_i18n( $format, $timestamp );
	}

	/**
	 * Get excerpt with fallback
	 *
	 * @param string $content
	 * @param int    $length
	 * @return string
	 */
	protected function get_excerpt( $content, $length = 150 ) {
		$excerpt = wp_trim_words( strip_tags( $content ), $length );
		return $this->sanitize_text( $excerpt );
	}

	/**
	 * Get featured image URL with fallback
	 *
	 * @param int    $post_id
	 * @param string $size
	 * @param string $fallback
	 * @return string
	 */
	protected function get_featured_image( $post_id, $size = 'large', $fallback = '' ) {
		if ( has_post_thumbnail( $post_id ) ) {
			return $this->sanitize_url( get_the_post_thumbnail_url( $post_id, $size ) );
		}
		return $this->sanitize_url( $fallback );
	}

	/**
	 * Get user avatar URL
	 *
	 * @param int $user_id
	 * @param int $size
	 * @return string
	 */
	protected function get_user_avatar( $user_id, $size = 96 ) {
		return $this->sanitize_url( get_avatar_url( $user_id, array( 'size' => $size ) ) );
	}

	/**
	 * Get term names for post
	 *
	 * @param int    $post_id
	 * @param string $taxonomy
	 * @return array
	 */
	protected function get_terms( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			function ( $term ) {
				return $this->sanitize_text( $term->name );
			},
			$terms
		);
	}
}
