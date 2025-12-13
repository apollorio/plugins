<?php
/**
 * Apollo Documents - PDF Signature Block Generator
 *
 * Adds a visible signature block page to PDFs after signing.
 * This is optional and only works if PDF library supports modification.
 *
 * @package Apollo\Modules\Documents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Class DocumentsPdfSignatureBlock
 *
 * Generates signature block HTML to append to PDF.
 */
class DocumentsPdfSignatureBlock {

	/**
	 * Generate signature block HTML
	 *
	 * @param int $doc_id Document post ID.
	 * @return string HTML for signature block.
	 */
	public static function generate( int $doc_id ): string {
		$signatures = DocumentsSignatureService::get_signatures( $doc_id );

		if ( empty( $signatures ) ) {
			return '';
		}

		$html = '<div class="signature-section" style="page-break-before: always; margin-top: 40pt; padding-top: 20pt; border-top: 2px solid #0f172a;">';
		$html .= '<h2 style="font-size: 18pt; font-weight: 600; margin-bottom: 20pt; color: #0f172a;">Assinaturas Digitais</h2>';

		foreach ( $signatures as $index => $sig ) {
			// Sanitize all signature data
			$signed_date = isset( $sig['signed_at'] ) ? sanitize_text_field( $sig['signed_at'] ) : '';
			$formatted_date = $signed_date ? date_i18n( 'd/m/Y H:i', strtotime( $signed_date ) ) : '';
			$signer_name = isset( $sig['signer_name'] ) ? sanitize_text_field( $sig['signer_name'] ) : __( 'N/A', 'apollo-social' );
			$role = isset( $sig['role'] ) ? sanitize_text_field( $sig['role'] ) : __( 'Signatário', 'apollo-social' );
			$signature_method = isset( $sig['signature_method'] ) ? sanitize_key( $sig['signature_method'] ) : 'e-sign-basic';
			$pdf_hash = isset( $sig['pdf_hash'] ) ? sanitize_text_field( $sig['pdf_hash'] ) : '';

			$html .= '<div class="signature-block" style="margin-bottom: 30pt; padding-bottom: 20pt; border-bottom: 1px dashed #d1d5db;">';
			$html .= '<div style="margin-bottom: 15pt;">';
			$html .= '<p style="font-size: 12pt; font-weight: 600; margin: 0 0 5pt 0; color: #1a1a1a;">' . esc_html( $signer_name ) . '</p>';
			$html .= '<p style="font-size: 10pt; color: #64748b; margin: 0;">' . esc_html( $role ) . '</p>';
			$html .= '</div>';

			$html .= '<div style="margin-bottom: 15pt;">';
			$html .= '<div style="border-bottom: 1px solid #333; height: 50pt; margin-bottom: 8pt;"></div>';
			$html .= '<p style="font-size: 9pt; color: #666; margin: 0;">' . esc_html__( 'Assinatura', 'apollo-social' ) . '</p>';
			$html .= '</div>';

			$html .= '<div style="font-size: 9pt; color: #64748b;">';
			$html .= '<p style="margin: 0 0 3pt 0;">' . esc_html__( 'Data:', 'apollo-social' ) . ' ' . esc_html( $formatted_date ) . '</p>';
			$html .= '<p style="margin: 0 0 3pt 0;">' . esc_html__( 'Método:', 'apollo-social' ) . ' ' . esc_html( $signature_method ) . '</p>';
			if ( ! empty( $pdf_hash ) ) {
				$hash_short = substr( $pdf_hash, 0, 16 ) . '...';
				$html .= '<p style="margin: 0; font-family: monospace; font-size: 8pt;">' . esc_html__( 'Hash:', 'apollo-social' ) . ' ' . esc_html( $hash_short ) . '</p>';
			}
			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '<div style="margin-top: 30pt; padding-top: 15pt; border-top: 1px solid #e5e7eb; font-size: 9pt; color: #9ca3af; text-align: center;">';
		$html .= '<p style="margin: 0;">Documento assinado digitalmente via Apollo Social</p>';
		$html .= '<p style="margin: 5pt 0 0 0;">Validade conforme Lei 14.063/2020</p>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Append signature block to print view HTML
	 *
	 * @param int    $doc_id Document post ID.
	 * @param string $print_html Original print HTML.
	 * @return string HTML with signature block appended.
	 */
	public static function append_to_print_view( int $doc_id, string $print_html ): string {
		$signature_block = self::generate( $doc_id );

		if ( empty( $signature_block ) ) {
			return $print_html;
		}

		// Insert before closing </body> tag
		$print_html = str_replace( '</body>', $signature_block . '</body>', $print_html );

		return $print_html;
	}
}

