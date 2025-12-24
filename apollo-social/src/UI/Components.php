<?php
/**
 * Apollo UI Components Library
 * WordPress-compatible component system inspired by ShadCN
 * Uses uni.css for styling and provides similar API
 */

namespace Apollo\UI;

/**
 * Button Component
 */
class Button {

	public static function render( $text, $variant = 'primary', $size = 'md', $attrs = array() ) {
		$classes = array( 'apollo-btn', "apollo-btn-{$variant}", "apollo-btn-{$size}" );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs_str = self::buildAttributes( $attrs );

		return '<button class="' . implode( ' ', $classes ) . "\" {$attrs_str}>{$text}</button>";
	}

	public static function buildAttributes( $attrs ) {
		$attr_strings = array();
		foreach ( $attrs as $key => $value ) {
			if ( $value !== null && $value !== false ) {
				$attr_strings[] = $key . '="' . htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) . '"';
			}
		}

		return implode( ' ', $attr_strings );
	}
}

/**
 * Input Component
 */
class Input {

	public static function render( $name, $value = '', $type = 'text', $placeholder = '', $attrs = array() ) {
		$classes = array( 'apollo-input' );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs = array_merge(
			array(
				'type'        => $type,
				'name'        => $name,
				'value'       => $value,
				'placeholder' => $placeholder,
				'class'       => implode( ' ', $classes ),
			),
			$attrs
		);

		$attrs_str = Button::buildAttributes( $attrs );

		return "<input {$attrs_str}>";
	}
}

/**
 * Form Field Component
 */
class Field {

	public static function render( $label, $control, $helper = '', $orientation = 'vertical', $attrs = array() ) {
		$classes = array( 'apollo-field', "apollo-field-{$orientation}" );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs_str = Button::buildAttributes( $attrs );

		$helper_html = $helper ? "<div class=\"apollo-field-helper\">{$helper}</div>" : '';

		return '
            <div class="' . implode( ' ', $classes ) . "\" {$attrs_str}>
                <label class=\"apollo-field-label\">{$label}</label>
                <div class=\"apollo-field-control\">{$control}</div>
                {$helper_html}
            </div>
        ";
	}
}

/**
 * Dialog/Modal Component
 */
class Dialog {

	private static $instance_count = 0;

	public static function render( $trigger, $content, $title = '', $attrs = array() ) {
		++self::$instance_count;
		$id = 'apollo-dialog-' . self::$instance_count;

		$classes = array( 'apollo-dialog' );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs_str = Button::buildAttributes( $attrs );

		return "
            <div class=\"apollo-dialog-wrapper\" {$attrs_str}>
                <div class=\"apollo-dialog-trigger\" onclick=\"apolloDialogToggle('{$id}')\">{$trigger}</div>
                <div id=\"{$id}\" class=\"apollo-dialog-overlay\" style=\"display:none;\">
                    <div class=\"apollo-dialog-content\">
                        <div class=\"apollo-dialog-header\">
                            <h3 class=\"apollo-dialog-title\">{$title}</h3>
                            <button class=\"apollo-dialog-close\" onclick=\"apolloDialogToggle('{$id}')\">×</button>
                        </div>
                        <div class=\"apollo-dialog-body\">{$content}</div>
                    </div>
                </div>
            </div>
        ";
	}
}

/**
 * Table Component
 */
class Table {

	public static function render( $headers, $rows, $attrs = array() ) {
		$classes = array( 'apollo-table' );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs_str = Button::buildAttributes( $attrs );

		$header_html = '<thead><tr>';
		foreach ( $headers as $header ) {
			$header_html .= "<th>{$header}</th>";
		}
		$header_html .= '</tr></thead>';

		$body_html = '<tbody>';
		foreach ( $rows as $row ) {
			$body_html .= '<tr>';
			foreach ( $row as $cell ) {
				$body_html .= "<td>{$cell}</td>";
			}
			$body_html .= '</tr>';
		}
		$body_html .= '</tbody>';

		return '<table class="' . implode( ' ', $classes ) . "\" {$attrs_str}>{$header_html}{$body_html}</table>";
	}
}

/**
 * Badge Component
 */
class Badge {

	public static function render( $text, $variant = 'default', $attrs = array() ) {
		$classes = array( 'apollo-badge', "apollo-badge-{$variant}" );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs_str = Button::buildAttributes( $attrs );

		return '<span class="' . implode( ' ', $classes ) . "\" {$attrs_str}>{$text}</span>";
	}
}

/**
 * Avatar Component
 */
class Avatar {

	public static function render( $src, $alt = '', $size = 'md', $attrs = array() ) {
		$classes = array( 'apollo-avatar', "apollo-avatar-{$size}" );

		if ( isset( $attrs['class'] ) ) {
			$classes[] = $attrs['class'];
			unset( $attrs['class'] );
		}

		$attrs = array_merge(
			array(
				'src'   => $src,
				'alt'   => $alt,
				'class' => implode( ' ', $classes ),
			),
			$attrs
		);

		$attrs_str = Button::buildAttributes( $attrs );

		return "<img {$attrs_str}>";
	}
}

/**
 * Initialize JavaScript for interactive components
 */
function apollo_ui_init_scripts() {
	?>
	<script>
	function apolloDialogToggle(id) {
		const dialog = document.getElementById(id);
		if (dialog) {
			dialog.style.display = dialog.style.display === 'none' ? 'flex' : 'none';
		}
	}

	// Close dialog when clicking overlay
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('apollo-dialog-overlay')) {
			e.target.style.display = 'none';
		}
	});
	</script>
	<?php
}
