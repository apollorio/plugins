<?php
/**
 * Apollo Widget: Note
 *
 * Small text widgets like Habbo sticky notes.
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined('ABSPATH') || exit;

class Apollo_Widget_Note extends Apollo_Widget_Base
{
    public function get_name()
    {
        return 'note';
    }

    public function get_title()
    {
        return __('Sticky Note', 'apollo-social');
    }

    public function get_icon()
    {
        return 'dashicons-edit';
    }

    public function get_description()
    {
        return __('Add short text notes to your home.', 'apollo-social');
    }

    public function get_tooltip()
    {
        return __('Sticky notes are small text boxes. Max 500 characters. Great for quotes, messages, etc.', 'apollo-social');
    }

    public function get_default_width()
    {
        return 150;
    }

    public function get_default_height()
    {
        return 150;
    }

    /**
     * Max 10 notes per home
     */
    public function get_max_instances()
    {
        return 10;
    }

    /**
     * Settings
     */
    public function get_settings()
    {
        return [
            'text' => $this->field(
                'textarea',
                __('Text', 'apollo-social'),
                __('My note...', 'apollo-social'),
                [
                    'rows'      => 4,
                    'maxlength' => 500,
                ]
            ),
            'color'      => $this->field('color', __('Note Color', 'apollo-social'), '#ffff88'),
            'text_color' => $this->field('color', __('Text Color', 'apollo-social'), '#333333'),
            'font_size'  => $this->field(
                'slider',
                __('Font Size', 'apollo-social'),
                14,
                [
                    'min'  => 10,
                    'max'  => 24,
                    'unit' => 'px',
                ]
            ),
            'rotation' => $this->field(
                'slider',
                __('Rotation', 'apollo-social'),
                0,
                [
                    'min'  => -15,
                    'max'  => 15,
                    'unit' => '°',
                ]
            ),
        ];
    }

    /**
     * Render widget
     */
    public function render($data)
    {
        $settings = $data['settings'] ?? [];

        $text       = sanitize_textarea_field(substr($settings['text'] ?? __('My note...', 'apollo-social'), 0, 500));
        $color      = sanitize_hex_color($settings['color'] ?? '#ffff88') ?: '#ffff88';
        $text_color = sanitize_hex_color($settings['text_color'] ?? '#333333') ?: '#333333';
        $font_size  = max(10, min(24, intval($settings['font_size'] ?? 14)));
        $rotation   = max(-15, min(15, intval($settings['rotation'] ?? 0)));

        // Build styles
        $styles = [
            'background-color: ' . $color,
            'color: ' . $text_color,
            'font-size: ' . $font_size . 'px',
        ];

        if ($rotation) {
            $styles[] = 'transform: rotate(' . $rotation . 'deg)';
        }

        ob_start();
        ?>
		<div class="apollo-widget-note" style="<?php echo esc_attr(implode(';', $styles)); ?>">
			<div class="note-pin"></div>
			<div class="note-content"><?php echo nl2br($this->esc($text)); ?></div>
		</div>
		<?php
        return ob_get_clean();
    }

    /**
     * Editor template
     */
    public function get_editor_template()
    {
        return '
        <div class="apollo-widget-note" style="background-color:{{data.color || "#ffff88"}};color:{{data.text_color || "#333"}};font-size:{{data.font_size || 14}}px;transform:rotate({{data.rotation || 0}}deg)">
            <div class="note-pin"></div>
            <div class="note-content">{{data.text || "' . __('My note...', 'apollo-social') . '"}}</div>
        </div>
        ';
    }
}
