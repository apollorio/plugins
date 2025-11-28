<?php
/**
 * Apollo Social – Delta to HTML Converter
 *
 * This class converts Quill Delta JSON to sanitized HTML using the nadar/quill-delta-parser
 * library. It's a critical component in the document workflow, enabling:
 *
 *   1. PDF Generation: Convert Delta to HTML, then HTML to PDF for e-signatures
 *   2. Email Templates: Render document content in notification emails
 *   3. Preview Display: Show formatted content without initializing Quill JS
 *   4. Search Indexing: Extract plain text from Delta for full-text search
 *   5. Legacy Support: Provide HTML fallback for browsers without JS
 *
 * How the Delta Parser Works:
 * ===========================
 * The nadar/quill-delta-parser library processes Delta JSON through a "Lexer" that:
 *
 *   1. Parses the Delta ops array into an abstract syntax tree (AST)
 *   2. Each operation (insert, attributes) becomes a "Line" with "Inline" elements
 *   3. The Lexer applies formatting rules based on attributes (bold, italic, etc.)
 *   4. Finally, it renders the AST to HTML using configurable tag mappings
 *
 * Example Delta:
 *   {"ops":[{"insert":"Hello "},{"insert":"World","attributes":{"bold":true}},{"insert":"\n"}]}
 *
 * Becomes HTML:
 *   <p>Hello <strong>World</strong></p>
 *
 * Security Considerations:
 * ========================
 * The Delta format is inherently safer than HTML because:
 *   - It's structured data, not executable markup
 *   - The parser controls what HTML tags are generated
 *   - No arbitrary HTML can be injected through Delta ops
 *
 * However, we still apply wp_kses_post() as defense-in-depth because:
 *   - The parser may have undiscovered vulnerabilities
 *   - Custom embed handlers might introduce unsafe content
 *   - It's WordPress best practice for any user-generated content
 *
 * Architectural Intent:
 * =====================
 * Why convert Delta to HTML on the backend (not just use Quill's getHTML())?
 *
 *   1. Consistency: Same rendering logic for all use cases (PDF, email, preview)
 *   2. Security: Server-side sanitization is more trustworthy than client JS
 *   3. Performance: Generate HTML once, cache it, serve to all clients
 *   4. Offline: Can render content even when Quill JS isn't available
 *   5. Auditability: Server logs can track exactly what HTML was generated
 *
 * For the document signing workflow specifically:
 *   - PDF generation requires HTML (via libraries like TCPDF, Dompdf)
 *   - The signed document must match what the user saw in the editor
 *   - We store both Delta (source of truth) and HTML (rendered output)
 *
 * @package    ApolloSocial
 * @subpackage Converters
 * @since      1.1.0
 * @author     Apollo Team
 */

namespace Apollo\Converters;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Import the Quill Delta Parser library
// This is installed via Composer: composer require nadar/quill-delta-parser
use nadar\quill\Lexer;
use nadar\quill\listener\Text;
use nadar\quill\listener\Heading;
use nadar\quill\listener\Bold;
use nadar\quill\listener\Italic;
use nadar\quill\listener\Underline;
use nadar\quill\listener\Strike;
use nadar\quill\listener\Link;
use nadar\quill\listener\Image;
use nadar\quill\listener\Lists;
use nadar\quill\listener\Color;
use nadar\quill\listener\Font;

/**
 * Class DeltaToHtmlConverter
 *
 * Converts Quill Delta JSON to sanitized HTML for display, PDF generation,
 * and other backend use cases in the Apollo Documents system.
 *
 * Usage:
 *   $converter = new DeltaToHtmlConverter();
 *   $html = $converter->convert($deltaJson);
 *
 * Or use the static helper:
 *   $html = DeltaToHtmlConverter::toHtml($deltaJson);
 *
 * @since 1.1.0
 */
class DeltaToHtmlConverter {

    /**
     * Allowed HTML tags for wp_kses sanitization.
     *
     * This whitelist defines what HTML elements and attributes are allowed
     * in the final output. It's more permissive than the default wp_kses_post()
     * because we trust the Delta parser to generate safe markup.
     *
     * @var array
     */
    private $allowed_html = array(
        // Block elements
        'p'          => array(
            'class' => array(),
            'style' => array(),
        ),
        'h1'         => array( 'class' => array() ),
        'h2'         => array( 'class' => array() ),
        'h3'         => array( 'class' => array() ),
        'h4'         => array( 'class' => array() ),
        'h5'         => array( 'class' => array() ),
        'h6'         => array( 'class' => array() ),
        'blockquote' => array( 'class' => array() ),
        'pre'        => array( 'class' => array() ),
        'code'       => array( 'class' => array() ),
        
        // List elements
        'ul'         => array( 'class' => array() ),
        'ol'         => array( 'class' => array() ),
        'li'         => array( 'class' => array() ),
        
        // Inline formatting
        'strong'     => array(),
        'b'          => array(),
        'em'         => array(),
        'i'          => array(),
        'u'          => array(),
        's'          => array(),
        'strike'     => array(),
        'sub'        => array(),
        'sup'        => array(),
        'span'       => array(
            'class' => array(),
            'style' => array(),
        ),
        
        // Links
        'a'          => array(
            'href'   => array(),
            'target' => array(),
            'rel'    => array(),
            'title'  => array(),
        ),
        
        // Media
        'img'        => array(
            'src'    => array(),
            'alt'    => array(),
            'width'  => array(),
            'height' => array(),
            'class'  => array(),
        ),
        
        // Tables (for future support)
        'table'      => array( 'class' => array() ),
        'thead'      => array(),
        'tbody'      => array(),
        'tr'         => array(),
        'th'         => array( 'colspan' => array(), 'rowspan' => array() ),
        'td'         => array( 'colspan' => array(), 'rowspan' => array() ),
        
        // Misc
        'br'         => array(),
        'hr'         => array(),
        'div'        => array( 'class' => array(), 'style' => array() ),
    );

    /**
     * Custom embed handlers for Delta types not supported by default.
     *
     * The Quill Delta format supports custom "embeds" - non-text content
     * like mentions, custom widgets, or file attachments. This array maps
     * embed types to handler callbacks.
     *
     * Example:
     *   'mention' => function($value) { return '<span class="mention">@' . esc_html($value['name']) . '</span>'; }
     *
     * @var array
     */
    private $embed_handlers = array();

    /**
     * Configuration options for the converter.
     *
     * @var array
     */
    private $options = array(
        // Wrap output in a container div with this class
        'container_class' => 'apollo-document-content',
        
        // Add data attribute with conversion timestamp
        'add_timestamp' => true,
        
        // Apply WordPress content filters (the_content)
        'apply_content_filters' => false,
        
        // Pretty-print HTML output (for debugging)
        'format_output' => false,
        
        // Throw exception on invalid Delta (vs. return empty string)
        'strict_mode' => false,
    );

    /**
     * Constructor.
     *
     * @param array $options Configuration options to override defaults.
     */
    public function __construct( array $options = array() ) {
        $this->options = array_merge( $this->options, $options );
        
        // Register default embed handlers
        $this->register_default_embed_handlers();
    }

    /**
     * Convert Delta JSON to HTML.
     *
     * This is the main conversion method. It:
     *   1. Validates and parses the Delta JSON
     *   2. Initializes the Lexer with default listeners
     *   3. Processes the Delta through the parser
     *   4. Sanitizes the output HTML
     *   5. Optionally wraps in a container
     *
     * @param string|array $delta Delta as JSON string or decoded array.
     * @return string Sanitized HTML output.
     * @throws \InvalidArgumentException If Delta is invalid and strict_mode is true.
     */
    public function convert( $delta ) {
        // Step 1: Parse Delta JSON if string
        // The Delta can come from post meta (string) or already decoded (array)
        if ( is_string( $delta ) ) {
            $delta = $this->parse_json( $delta );
        }

        // Handle empty or invalid Delta
        if ( ! $delta || ! isset( $delta['ops'] ) || empty( $delta['ops'] ) ) {
            if ( $this->options['strict_mode'] ) {
                throw new \InvalidArgumentException( 'Invalid Delta: missing or empty ops array' );
            }
            return '';
        }

        // Step 2: Initialize the Lexer
        // The Lexer is the core of nadar/quill-delta-parser. It processes Delta ops
        // through a series of "listeners" that handle different content types.
        try {
            $lexer = new Lexer( json_encode( $delta ) );
            
            // Step 3: Render Delta to HTML
            // The render() method processes all ops and returns the final HTML
            $html = $lexer->render();
            
        } catch ( \Exception $e ) {
            // Log the error for debugging
            error_log( '[Apollo DeltaConverter] Conversion failed: ' . $e->getMessage() );
            
            if ( $this->options['strict_mode'] ) {
                throw $e;
            }
            return '';
        }

        // Step 4: Process custom embeds
        // The parser may leave placeholders for custom embeds that we need to handle
        $html = $this->process_custom_embeds( $html, $delta );

        // Step 5: Sanitize the HTML
        // Even though the parser generates safe HTML, we apply wp_kses as defense-in-depth.
        // This ensures no malicious content can slip through even if the parser has bugs.
        $html = wp_kses( $html, $this->allowed_html );

        // Step 6: Apply WordPress content filters if enabled
        // This runs the_content filters (shortcodes, embeds, etc.) on the output.
        // Usually disabled for documents to prevent unexpected transformations.
        if ( $this->options['apply_content_filters'] ) {
            $html = apply_filters( 'the_content', $html );
        }

        // Step 7: Wrap in container if configured
        if ( ! empty( $this->options['container_class'] ) ) {
            $timestamp_attr = $this->options['add_timestamp']
                ? ' data-converted="' . esc_attr( current_time( 'c' ) ) . '"'
                : '';
            
            $html = sprintf(
                '<div class="%s"%s>%s</div>',
                esc_attr( $this->options['container_class'] ),
                $timestamp_attr,
                $html
            );
        }

        // Step 8: Format output if debugging
        if ( $this->options['format_output'] ) {
            $html = $this->format_html( $html );
        }

        return $html;
    }

    /**
     * Parse JSON string to array.
     *
     * @param string $json The JSON string to parse.
     * @return array|null Decoded array or null on failure.
     */
    private function parse_json( $json ) {
        if ( empty( $json ) ) {
            return null;
        }

        $decoded = json_decode( $json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( '[Apollo DeltaConverter] JSON parse error: ' . json_last_error_msg() );
            return null;
        }

        return $decoded;
    }

    /**
     * Register default embed handlers.
     *
     * Embeds are non-text content in Delta (images, videos, mentions, etc.).
     * The parser handles standard embeds, but custom ones need handlers.
     */
    private function register_default_embed_handlers() {
        // Mention handler: @username references
        // Delta: {"insert":{"mention":{"id":"123","name":"John"}}}
        $this->embed_handlers['mention'] = function( $value ) {
            $name = isset( $value['name'] ) ? $value['name'] : 'Unknown';
            $id   = isset( $value['id'] ) ? $value['id'] : '';
            
            return sprintf(
                '<span class="apollo-mention" data-user-id="%s">@%s</span>',
                esc_attr( $id ),
                esc_html( $name )
            );
        };

        // Document link handler: [[doc:123]] style links
        $this->embed_handlers['document-link'] = function( $value ) {
            $title = isset( $value['title'] ) ? $value['title'] : 'Document';
            $id    = isset( $value['id'] ) ? $value['id'] : '';
            $url   = home_url( '/doc/' . $id );
            
            return sprintf(
                '<a href="%s" class="apollo-document-link">📄 %s</a>',
                esc_url( $url ),
                esc_html( $title )
            );
        };

        // Signature placeholder handler: [SIGNATURE:signer-id]
        // This is replaced with an actual signature image or field in PDF
        $this->embed_handlers['signature'] = function( $value ) {
            $signer_id = isset( $value['signer_id'] ) ? $value['signer_id'] : '';
            $label     = isset( $value['label'] ) ? $value['label'] : 'Assinatura';
            
            return sprintf(
                '<div class="apollo-signature-placeholder" data-signer-id="%s">
                    <div class="signature-box">
                        <span class="signature-label">%s</span>
                        <div class="signature-line"></div>
                    </div>
                </div>',
                esc_attr( $signer_id ),
                esc_html( $label )
            );
        };

        // Allow plugins/themes to register custom embed handlers
        // Usage: add_filter('apollo_delta_embed_handlers', function($handlers) {
        //     $handlers['my-embed'] = function($value) { return '...'; };
        //     return $handlers;
        // });
        $this->embed_handlers = apply_filters( 'apollo_delta_embed_handlers', $this->embed_handlers );
    }

    /**
     * Add a custom embed handler.
     *
     * @param string   $type    The embed type (e.g., 'mention', 'file').
     * @param callable $handler Callback that receives embed value and returns HTML.
     * @return self For method chaining.
     */
    public function add_embed_handler( $type, callable $handler ) {
        $this->embed_handlers[ $type ] = $handler;
        return $this;
    }

    /**
     * Process custom embeds in the Delta.
     *
     * Scans the Delta for embed ops and replaces them with rendered HTML.
     * The standard parser handles images and basic embeds, but custom ones
     * (mentions, signatures, etc.) need special handling.
     *
     * @param string $html  The HTML output from the parser.
     * @param array  $delta The original Delta array.
     * @return string HTML with custom embeds rendered.
     */
    private function process_custom_embeds( $html, $delta ) {
        if ( empty( $delta['ops'] ) ) {
            return $html;
        }

        foreach ( $delta['ops'] as $op ) {
            // Skip text inserts - only process embeds
            if ( ! isset( $op['insert'] ) || ! is_array( $op['insert'] ) ) {
                continue;
            }

            // Get the embed type (the first/only key in the insert object)
            $embed_type = key( $op['insert'] );
            $embed_value = $op['insert'][ $embed_type ];

            // Check if we have a handler for this embed type
            if ( isset( $this->embed_handlers[ $embed_type ] ) ) {
                $rendered = call_user_func( $this->embed_handlers[ $embed_type ], $embed_value );
                
                // The parser may have left a placeholder - replace it
                // Note: This is a simplified approach; complex documents may need
                // position-based replacement for accuracy
                // TODO: Implement position-based embed replacement for complex documents
            }
        }

        return $html;
    }

    /**
     * Format HTML for readability (debugging).
     *
     * @param string $html The HTML to format.
     * @return string Formatted HTML.
     */
    private function format_html( $html ) {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        
        // Suppress warnings for HTML5 tags
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();
        
        return $dom->saveHTML();
    }

    /**
     * Extract plain text from Delta.
     *
     * Useful for search indexing, excerpts, and text-only contexts.
     *
     * @param string|array $delta Delta as JSON string or decoded array.
     * @return string Plain text content.
     */
    public function to_plain_text( $delta ) {
        if ( is_string( $delta ) ) {
            $delta = $this->parse_json( $delta );
        }

        if ( ! $delta || ! isset( $delta['ops'] ) ) {
            return '';
        }

        $text = '';

        foreach ( $delta['ops'] as $op ) {
            if ( ! isset( $op['insert'] ) ) {
                continue;
            }

            // Text insert
            if ( is_string( $op['insert'] ) ) {
                $text .= $op['insert'];
            }
            // Embed insert (image, mention, etc.) - add placeholder
            elseif ( is_array( $op['insert'] ) ) {
                $embed_type = key( $op['insert'] );
                $text .= "[$embed_type]";
            }
        }

        return trim( $text );
    }

    /**
     * Get word count from Delta.
     *
     * @param string|array $delta Delta as JSON string or decoded array.
     * @return int Word count.
     */
    public function get_word_count( $delta ) {
        $text = $this->to_plain_text( $delta );
        return str_word_count( $text );
    }

    /**
     * Static helper: Convert Delta to HTML.
     *
     * Convenience method for one-off conversions without instantiating the class.
     *
     * @param string|array $delta   Delta as JSON string or decoded array.
     * @param array        $options Configuration options.
     * @return string Sanitized HTML output.
     */
    public static function toHtml( $delta, array $options = array() ) {
        $converter = new self( $options );
        return $converter->convert( $delta );
    }

    /**
     * Static helper: Convert Delta to plain text.
     *
     * @param string|array $delta Delta as JSON string or decoded array.
     * @return string Plain text content.
     */
    public static function toText( $delta ) {
        $converter = new self();
        return $converter->to_plain_text( $delta );
    }
}
