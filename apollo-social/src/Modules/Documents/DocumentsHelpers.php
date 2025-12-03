<?php
/**
 * Apollo Documents Helpers.
 *
 * Provides helper functions for document status, type labels, and tooltips.
 * All functions are prefixed with apollo_ for global scope.
 *
 * @package Apollo\Modules\Documents
 * @since   1.0.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Documents Helpers Class.
 *
 * Static helper methods for document-related operations.
 */
class DocumentsHelpers {

	/**
	 * Document status definitions
	 *
	 * @var array<string, array{label: string, icon: string, color: string, tooltip: string}>
	 */
	private static array $statuses = array(
		'draft'     => array(
			'label'   => 'Rascunho',
			'icon'    => 'ri-draft-line',
			'color'   => 'slate',
			'tooltip' => 'Documento em edição. Não está visível para outros usuários e pode ser modificado livremente.',
		),
		'ready'     => array(
			'label'   => 'Pronto',
			'icon'    => 'ri-checkbox-circle-line',
			'color'   => 'blue',
			'tooltip' => 'Documento finalizado e pronto para assinatura ou publicação.',
		),
		'signing'   => array(
			'label'   => 'Em Assinatura',
			'icon'    => 'ri-quill-pen-line',
			'color'   => 'amber',
			'tooltip' => 'Documento aguardando assinaturas das partes envolvidas.',
		),
		'completed' => array(
			'label'   => 'Concluído',
			'icon'    => 'ri-verified-badge-line',
			'color'   => 'emerald',
			'tooltip' => 'Documento completamente assinado e finalizado. Não pode ser alterado.',
		),
		'archived'  => array(
			'label'   => 'Arquivado',
			'icon'    => 'ri-archive-line',
			'color'   => 'gray',
			'tooltip' => 'Documento arquivado. Mantido para histórico mas não está ativo.',
		),
		'cancelled' => array(
			'label'   => 'Cancelado',
			'icon'    => 'ri-close-circle-line',
			'color'   => 'red',
			'tooltip' => 'Documento cancelado. O processo de assinatura foi interrompido.',
		),
	);

	/**
	 * Document type definitions
	 *
	 * @var array<string, array{label: string, icon: string, tooltip: string}>
	 */
	private static array $types = array(
		'documento'   => array(
			'label'   => 'Documento',
			'icon'    => 'ri-file-text-line',
			'tooltip' => 'Documento de texto com formatação rica. Ideal para contratos, termos e declarações.',
		),
		'planilha'    => array(
			'label'   => 'Planilha',
			'icon'    => 'ri-table-line',
			'tooltip' => 'Planilha com células e fórmulas. Ideal para orçamentos, tabelas e cálculos.',
		),
		'text'        => array(
			'label'   => 'Documento',
			'icon'    => 'ri-file-text-line',
			'tooltip' => 'Documento de texto com formatação rica. Ideal para contratos, termos e declarações.',
		),
		'spreadsheet' => array(
			'label'   => 'Planilha',
			'icon'    => 'ri-table-line',
			'tooltip' => 'Planilha com células e fórmulas. Ideal para orçamentos, tabelas e cálculos.',
		),
	);

	/**
	 * Field tooltips for editor UI
	 *
	 * @var array<string, string>
	 */
	private static array $field_tooltips = array(
		'title'        => 'Título do documento. Será exibido na listagem e no cabeçalho do PDF.',
		'type'         => 'Tipo define o formato de edição: texto formatado ou planilha.',
		'status'       => 'Status indica a etapa atual do documento no fluxo de trabalho.',
		'version'      => 'Versão é incrementada automaticamente a cada salvamento.',
		'created_at'   => 'Data e hora de criação do documento.',
		'updated_at'   => 'Data e hora da última modificação.',
		'created_by'   => 'Usuário que criou o documento.',
		'file_id'      => 'Identificador único do arquivo para URLs e referências.',
		'signatures'   => 'Lista de assinaturas coletadas ou pendentes neste documento.',
		'pdf_path'     => 'Caminho do arquivo PDF gerado para download ou assinatura.',
		'content'      => 'Conteúdo principal do documento em formato editável.',
		'font_family'  => 'Fonte tipográfica usada no texto selecionado.',
		'font_size'    => 'Tamanho da fonte em pixels ou pontos.',
		'font_weight'  => 'Peso da fonte: Light, Regular, Medium, Bold, etc.',
		'text_color'   => 'Cor do texto selecionado.',
		'text_align'   => 'Alinhamento do parágrafo: esquerda, centro, direita ou justificado.',
		'save_status'  => 'Indica se o documento está salvo, salvando ou com erro.',
		'export_pdf'   => 'Gera um arquivo PDF a partir do conteúdo atual.',
		'prepare_sign' => 'Prepara o documento para coleta de assinaturas digitais.',
	);

	/**
	 * Get status label.
	 *
	 * @param string $status Status key.
	 * @return string Translated label.
	 */
	public static function get_status_label( string $status ): string {
		return self::$statuses[ $status ]['label'] ?? ucfirst( $status );
	}

	/**
	 * Get status icon class.
	 *
	 * @param string $status Status key.
	 * @return string Icon class (Remix Icon).
	 */
	public static function get_status_icon( string $status ): string {
		return self::$statuses[ $status ]['icon'] ?? 'ri-file-line';
	}

	/**
	 * Get status color.
	 *
	 * @param string $status Status key.
	 * @return string Color name (Tailwind-compatible).
	 */
	public static function get_status_color( string $status ): string {
		return self::$statuses[ $status ]['color'] ?? 'gray';
	}

	/**
	 * Get status tooltip text.
	 *
	 * @param string $status Status key.
	 * @return string Tooltip text.
	 */
	public static function get_status_tooltip( string $status ): string {
		return self::$statuses[ $status ]['tooltip'] ?? '';
	}

	/**
	 * Get complete status info.
	 *
	 * @param string $status Status key.
	 * @return array{label: string, icon: string, color: string, tooltip: string} Status data.
	 */
	public static function get_status_info( string $status ): array {
		return self::$statuses[ $status ] ?? array(
			'label'   => ucfirst( $status ),
			'icon'    => 'ri-file-line',
			'color'   => 'gray',
			'tooltip' => '',
		);
	}

	/**
	 * Get all statuses.
	 *
	 * @return array<string, array{label: string, icon: string, color: string, tooltip: string}> All statuses.
	 */
	public static function get_all_statuses(): array {
		return self::$statuses;
	}

	/**
	 * Get type label.
	 *
	 * @param string $type Type key.
	 * @return string Translated label.
	 */
	public static function get_type_label( string $type ): string {
		return self::$types[ $type ]['label'] ?? ucfirst( $type );
	}

	/**
	 * Get type icon class.
	 *
	 * @param string $type Type key.
	 * @return string Icon class (Remix Icon).
	 */
	public static function get_type_icon( string $type ): string {
		return self::$types[ $type ]['icon'] ?? 'ri-file-line';
	}

	/**
	 * Get type tooltip text.
	 *
	 * @param string $type Type key.
	 * @return string Tooltip text.
	 */
	public static function get_type_tooltip( string $type ): string {
		return self::$types[ $type ]['tooltip'] ?? '';
	}

	/**
	 * Get complete type info.
	 *
	 * @param string $type Type key.
	 * @return array{label: string, icon: string, tooltip: string} Type data.
	 */
	public static function get_type_info( string $type ): array {
		return self::$types[ $type ] ?? array(
			'label'   => ucfirst( $type ),
			'icon'    => 'ri-file-line',
			'tooltip' => '',
		);
	}

	/**
	 * Get all types.
	 *
	 * @return array<string, array{label: string, icon: string, tooltip: string}> All types.
	 */
	public static function get_all_types(): array {
		return self::$types;
	}

	/**
	 * Get field tooltip.
	 *
	 * @param string $field Field key.
	 * @return string Tooltip text.
	 */
	public static function get_field_tooltip( string $field ): string {
		return self::$field_tooltips[ $field ] ?? '';
	}

	/**
	 * Get all field tooltips.
	 *
	 * @return array<string, string> All field tooltips.
	 */
	public static function get_all_field_tooltips(): array {
		return self::$field_tooltips;
	}

	/**
	 * Render tooltip HTML attribute.
	 *
	 * @param string $text Tooltip text.
	 * @return string HTML attribute string.
	 */
	public static function tooltip_attr( string $text ): string {
		if ( empty( $text ) ) {
			return '';
		}
		return 'data-ap-tooltip="' . esc_attr( $text ) . '"';
	}

	/**
	 * Render tooltip icon with text.
	 *
	 * @param string $text    Tooltip text.
	 * @param string $icon    Icon class (default: ri-question-line).
	 * @param string $classes Additional CSS classes.
	 * @return string HTML output.
	 */
	public static function tooltip_icon( string $text, string $icon = 'ri-question-line', string $classes = '' ): string {
		if ( empty( $text ) ) {
			return '';
		}

		$classes = trim( 'ap-tooltip-icon ' . $classes );

		return sprintf(
			'<span class="%s" data-ap-tooltip="%s"><i class="%s"></i></span>',
			esc_attr( $classes ),
			esc_attr( $text ),
			esc_attr( $icon )
		);
	}

	/**
	 * Render status badge HTML.
	 *
	 * @param string $status  Status key.
	 * @param bool   $tooltip Include tooltip.
	 * @return string HTML output.
	 */
	public static function status_badge( string $status, bool $tooltip = true ): string {
		$info         = self::get_status_info( $status );
		$tooltip_attr = $tooltip ? self::tooltip_attr( $info['tooltip'] ) : '';

		return sprintf(
			'<span class="ap-badge ap-badge-%s" %s><i class="%s"></i> %s</span>',
			esc_attr( $info['color'] ),
			$tooltip_attr,
			esc_attr( $info['icon'] ),
			esc_html( $info['label'] )
		);
	}

	/**
	 * Render type badge HTML.
	 *
	 * @param string $type    Type key.
	 * @param bool   $tooltip Include tooltip.
	 * @return string HTML output.
	 */
	public static function type_badge( string $type, bool $tooltip = true ): string {
		$info         = self::get_type_info( $type );
		$tooltip_attr = $tooltip ? self::tooltip_attr( $info['tooltip'] ) : '';

		return sprintf(
			'<span class="ap-badge ap-badge-outline" %s><i class="%s"></i> %s</span>',
			$tooltip_attr,
			esc_attr( $info['icon'] ),
			esc_html( $info['label'] )
		);
	}

	/**
	 * Format version number.
	 *
	 * @param int $version Version number.
	 * @return string Formatted version (v1, v2, etc.).
	 */
	public static function format_version( int $version ): string {
		return 'v' . $version;
	}

	/**
	 * Get version tooltip with version number.
	 *
	 * @param int $version Version number.
	 * @return string Tooltip text.
	 */
	public static function get_version_tooltip( int $version ): string {
		$base_tooltip = self::get_field_tooltip( 'version' );
		return sprintf( '%s Versão atual: %d', $base_tooltip, $version );
	}

	/**
	 * Check if document can be edited.
	 *
	 * @param string $status Document status.
	 * @return bool True if editable.
	 */
	public static function can_edit( string $status ): bool {
		return in_array( $status, array( 'draft', 'ready' ), true );
	}

	/**
	 * Check if document can be signed.
	 *
	 * @param string $status Document status.
	 * @return bool True if can be signed.
	 */
	public static function can_sign( string $status ): bool {
		return in_array( $status, array( 'ready', 'signing' ), true );
	}

	/**
	 * Check if document is finalized.
	 *
	 * @param string $status Document status.
	 * @return bool True if finalized.
	 */
	public static function is_finalized( string $status ): bool {
		return in_array( $status, array( 'completed', 'archived', 'cancelled' ), true );
	}

	/**
	 * Get next possible statuses from current status.
	 *
	 * @param string $current_status Current status.
	 * @return array<string> Possible next statuses.
	 */
	public static function get_next_statuses( string $current_status ): array {
		$transitions = array(
			'draft'     => array( 'ready', 'archived' ),
			'ready'     => array( 'signing', 'draft', 'archived' ),
			'signing'   => array( 'completed', 'cancelled' ),
			'completed' => array( 'archived' ),
			'archived'  => array(),
			'cancelled' => array( 'archived' ),
		);

		return $transitions[ $current_status ] ?? array();
	}

	/**
	 * Export all tooltips as JSON for JavaScript.
	 *
	 * @return string JSON string.
	 */
	public static function export_tooltips_json(): string {
		return wp_json_encode(
			array(
				'statuses' => self::$statuses,
				'types'    => self::$types,
				'fields'   => self::$field_tooltips,
			),
			JSON_UNESCAPED_UNICODE
		);
	}
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed

/**
 * Get document status label.
 *
 * @param string $status Status key.
 * @return string Translated label.
 */
function apollo_get_document_status_label( string $status ): string {
	return DocumentsHelpers::get_status_label( $status );
}

/**
 * Get document status info (label, icon, color, tooltip).
 *
 * @param string $status Status key.
 * @return array Status info array.
 */
function apollo_get_document_status_info( string $status ): array {
	return DocumentsHelpers::get_status_info( $status );
}

/**
 * Get document type label.
 *
 * @param string $type Type key.
 * @return string Translated label.
 */
function apollo_get_document_type_label( string $type ): string {
	return DocumentsHelpers::get_type_label( $type );
}

/**
 * Get document type info (label, icon, tooltip).
 *
 * @param string $type Type key.
 * @return array Type info array.
 */
function apollo_get_document_type_info( string $type ): array {
	return DocumentsHelpers::get_type_info( $type );
}

/**
 * Get field tooltip text.
 *
 * @param string $field Field key.
 * @return string Tooltip text.
 */
function apollo_get_field_tooltip( string $field ): string {
	return DocumentsHelpers::get_field_tooltip( $field );
}

/**
 * Render tooltip HTML attribute.
 *
 * @param string $text Tooltip text.
 * @return string HTML attribute.
 */
function apollo_tooltip_attr( string $text ): string {
	return DocumentsHelpers::tooltip_attr( $text );
}

/**
 * Render tooltip icon.
 *
 * @param string $text Tooltip text.
 * @param string $icon Icon class.
 * @return string HTML output.
 */
function apollo_tooltip_icon( string $text, string $icon = 'ri-question-line' ): string {
	return DocumentsHelpers::tooltip_icon( $text, $icon );
}

/**
 * Render document status badge.
 *
 * @param string $status Status key.
 * @return string HTML output.
 */
function apollo_document_status_badge( string $status ): string {
	return DocumentsHelpers::status_badge( $status );
}

/**
 * Render document type badge.
 *
 * @param string $type Type key.
 * @return string HTML output.
 */
function apollo_document_type_badge( string $type ): string {
	return DocumentsHelpers::type_badge( $type );
}

/**
 * Check if document can be edited based on status.
 *
 * @param string $status Status key.
 * @return bool True if editable.
 */
function apollo_document_can_edit( string $status ): bool {
	return DocumentsHelpers::can_edit( $status );
}

/**
 * Check if document can be signed based on status.
 *
 * @param string $status Status key.
 * @return bool True if can be signed.
 */
function apollo_document_can_sign( string $status ): bool {
	return DocumentsHelpers::can_sign( $status );
}
