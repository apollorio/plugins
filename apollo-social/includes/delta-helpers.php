<?php

/**
 * Apollo Social – Delta Helper Functions
 *
 * Global helper functions for working with Quill Delta content.
 * These provide convenient access to the DeltaToHtmlConverter class
 * without needing to instantiate it or use the full namespace.
 *
 * Usage:
 *   $html = apollo_delta_to_html( $delta_json );
 *   $text = apollo_delta_to_text( $delta_json );
 *   $words = apollo_delta_word_count( $delta_json );
 *
 * @package    ApolloSocial
 * @subpackage Helpers
 * @since      1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define class name as string to avoid Intelephense static analysis on optional dependency
define( 'APOLLO_DELTA_CONVERTER_CLASS', 'ApolloSocial\\Converters\\DeltaToHtmlConverter' );

/**
 * Convert Quill Delta JSON to sanitized HTML.
 *
 * This is the primary helper for converting Delta content to HTML.
 * The output is sanitized and ready for display on the frontend.
 *
 * Example:
 *   $delta = '{"ops":[{"insert":"Hello "},{"insert":"World","attributes":{"bold":true}}]}';
 *   echo apollo_delta_to_html( $delta );
 *   // Output: <div class="apollo-document-content"><p>Hello <strong>World</strong></p></div>
 *
 * @since 1.1.0
 *
 * @param string|array $delta   The Delta content as JSON string or decoded array.
 * @param array        $options Optional. Configuration options for the converter.
 *                              - 'container_class' (string): CSS class for wrapper div. Default 'apollo-document-content'.
 *                              - 'add_timestamp' (bool): Add data-converted attribute. Default true.
 *                              - 'apply_content_filters' (bool): Run the_content filters. Default false.
 *                              - 'format_output' (bool): Pretty-print HTML. Default false.
 *                              - 'strict_mode' (bool): Throw on invalid Delta. Default false.
 * @return string Sanitized HTML output.
 */
function apollo_delta_to_html( $delta, array $options = array() ) {
	// Check if the converter class is available (Composer autoload)
	if ( ! class_exists( APOLLO_DELTA_CONVERTER_CLASS ) ) {
		// Log warning and return fallback
		error_log( '[Apollo] DeltaToHtmlConverter not available. Run: composer require nadar/quill-delta-parser' );

		// Try to decode and extract plain text as fallback
		if ( is_string( $delta ) ) {
			$decoded = json_decode( $delta, true );
			if ( $decoded && isset( $decoded['ops'] ) ) {
				$text = '';
				foreach ( $decoded['ops'] as $op ) {
					if ( isset( $op['insert'] ) && is_string( $op['insert'] ) ) {
						$text .= $op['insert'];
					}
				}
				return '<div class="apollo-document-content"><p>' . nl2br( esc_html( $text ) ) . '</p></div>';
			}
		}
		return '';
	}

	return call_user_func_array( array( APOLLO_DELTA_CONVERTER_CLASS, 'toHtml' ), array( $delta, $options ) );
}

/**
 * Convert Quill Delta JSON to plain text.
 *
 * Extracts all text content from the Delta, stripping formatting.
 * Useful for:
 *   - Search indexing
 *   - Generating excerpts
 *   - Text-only email previews
 *   - Character/word counting
 *
 * Example:
 *   $delta = '{"ops":[{"insert":"Hello "},{"insert":"World","attributes":{"bold":true}}]}';
 *   echo apollo_delta_to_text( $delta );
 *   // Output: Hello World
 *
 * @since 1.1.0
 *
 * @param string|array $delta The Delta content as JSON string or decoded array.
 * @return string Plain text content (embeds replaced with [type] placeholders).
 */
function apollo_delta_to_text( $delta ) {
	if ( ! class_exists( APOLLO_DELTA_CONVERTER_CLASS ) ) {
		// Fallback: extract text manually
		if ( is_string( $delta ) ) {
			$decoded = json_decode( $delta, true );
			if ( $decoded && isset( $decoded['ops'] ) ) {
				$text = '';
				foreach ( $decoded['ops'] as $op ) {
					if ( isset( $op['insert'] ) && is_string( $op['insert'] ) ) {
						$text .= $op['insert'];
					}
				}
				return trim( $text );
			}
		}
		return '';
	}

	return call_user_func( array( APOLLO_DELTA_CONVERTER_CLASS, 'toText' ), $delta );
}

/**
 * Get word count from Quill Delta.
 *
 * Counts words in the Delta content, useful for document statistics.
 *
 * @since 1.1.0
 *
 * @param string|array $delta The Delta content as JSON string or decoded array.
 * @return int Word count.
 */
function apollo_delta_word_count( $delta ) {
	$text = apollo_delta_to_text( $delta );
	return str_word_count( $text );
}

/**
 * Check if a Delta is empty (no meaningful content).
 *
 * A Delta is considered empty if:
 *   - It's null/empty string
 *   - It has no ops
 *   - Its only content is whitespace
 *
 * @since 1.1.0
 *
 * @param string|array $delta The Delta content as JSON string or decoded array.
 * @return bool True if the Delta is empty.
 */
function apollo_delta_is_empty( $delta ) {
	if ( empty( $delta ) ) {
		return true;
	}

	// Parse if string
	if ( is_string( $delta ) ) {
		$delta = json_decode( $delta, true );
	}

	// Check for valid structure
	if ( ! is_array( $delta ) || ! isset( $delta['ops'] ) || empty( $delta['ops'] ) ) {
		return true;
	}

	// Check if only whitespace
	$text = '';
	foreach ( $delta['ops'] as $op ) {
		if ( isset( $op['insert'] ) && is_string( $op['insert'] ) ) {
			$text .= $op['insert'];
		} elseif ( isset( $op['insert'] ) && is_array( $op['insert'] ) ) {
			// Has an embed (image, etc.) - not empty
			return false;
		}
	}

	return trim( $text ) === '';
}

/**
 * Get an excerpt from Quill Delta.
 *
 * Extracts the first N words from the Delta content.
 *
 * @since 1.1.0
 *
 * @param string|array $delta  The Delta content.
 * @param int          $length Number of words in excerpt. Default 55.
 * @param string       $more   What to append if content is truncated. Default '...'.
 * @return string The excerpt text.
 */
function apollo_delta_excerpt( $delta, $length = 55, $more = '...' ) {
	$text  = apollo_delta_to_text( $delta );
	$words = explode( ' ', $text );

	if ( count( $words ) <= $length ) {
		return $text;
	}

	return implode( ' ', array_slice( $words, 0, $length ) ) . $more;
}

/**
 * Get document content (Delta or HTML) by ID.
 *
 * Retrieves the stored Delta and optionally converts to HTML.
 * This is the main function for displaying document content.
 *
 * @since 1.1.0
 *
 * @param int  $document_id The document post ID.
 * @param bool $as_html     Return as HTML instead of Delta. Default true.
 * @return string|null The content (HTML or Delta JSON), or null if not found.
 */
function apollo_get_document_content( $document_id, $as_html = true ) {
	$delta = get_post_meta( $document_id, '_apollo_document_delta', true );

	if ( empty( $delta ) ) {
		// Fallback: return post_content (HTML)
		$post = get_post( $document_id );
		return $post ? $post->post_content : null;
	}

	if ( $as_html ) {
		return apollo_delta_to_html( $delta );
	}

	return $delta;
}

/**
 * Render document content for display.
 *
 * Echoes the document content with proper styling container.
 * Use this in templates where you want to display document content.
 *
 * @since 1.1.0
 *
 * @param int   $document_id The document post ID.
 * @param array $options     Options for rendering.
 *                           - 'echo' (bool): Echo instead of return. Default true.
 *                           - 'class' (string): Additional CSS classes.
 * @return string|void HTML output if echo is false.
 */
function apollo_render_document( $document_id, array $options = array() ) {
	$defaults = array(
		'echo'  => true,
		'class' => '',
	);
	$options  = array_merge( $defaults, $options );

	$html = apollo_get_document_content( $document_id, true );

	if ( empty( $html ) ) {
		$output = '<div class="apollo-document-empty">' .
			esc_html__( 'Este documento está vazio.', 'apollo-social' ) .
			'</div>';
	} else {
		$class = 'apollo-document-render';
		if ( ! empty( $options['class'] ) ) {
			$class .= ' ' . esc_attr( $options['class'] );
		}
		$output = '<div class="' . $class . '">' . $html . '</div>';
	}

	if ( $options['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}
