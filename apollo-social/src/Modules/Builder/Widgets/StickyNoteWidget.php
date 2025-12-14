<?php

namespace Apollo\Modules\Builder\Widgets;

class StickyNoteWidget extends Apollo_Widget_Base
{
    public function __construct()
    {
        parent::__construct(
            'apollo_sticky_note',
            __('Apollo Sticky Note', 'apollo-social')
        );
    }

    protected function render(array $args, array $instance): string
    {
        $title   = $instance['title']   ?? __('Lembrete', 'apollo-social');
        $content = $instance['content'] ?? __('Clique para editar esta nota.', 'apollo-social');
        $color   = $instance['color']   ?? '#fef3c7';

        ob_start();
        ?>
		<div class="apollo-sticky-note" style="background: <?php echo esc_attr($color); ?>">
			<header class="apollo-sticky-note__header">
				<span class="apollo-sticky-note__pin"></span>
				<h3 class="apollo-sticky-note__title"><?php echo esc_html($title); ?></h3>
			</header>
			<div class="apollo-sticky-note__content">
				<?php echo wpautop(wp_kses_post($content)); ?>
			</div>
		</div>
		<?php
        return (string) ob_get_clean();
    }

    protected function renderForm(array $instance): string
    {
        $title   = esc_attr($instance['title'] ?? '');
        $content = esc_textarea($instance['content'] ?? '');
        $color   = esc_attr($instance['color'] ?? '#fef3c7');

        ob_start();
        ?>
		<div class="apollo-widget-field">
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
				<?php esc_html_e('Título', 'apollo-social'); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr($this->get_field_id('title')); ?>"
				name="<?php echo esc_attr($this->get_field_name('title')); ?>"
				type="text"
				value="<?php echo $title; ?>"
			>
		</div>

		<div class="apollo-widget-field">
			<label for="<?php echo esc_attr($this->get_field_id('content')); ?>">
				<?php esc_html_e('Conteúdo', 'apollo-social'); ?>
			</label>
			<textarea
				class="widefat"
				rows="5"
				id="<?php echo esc_attr($this->get_field_id('content')); ?>"
				name="<?php echo esc_attr($this->get_field_name('content')); ?>"
			><?php echo $content; ?></textarea>
		</div>

		<div class="apollo-widget-field">
			<label for="<?php echo esc_attr($this->get_field_id('color')); ?>">
				<?php esc_html_e('Cor de fundo', 'apollo-social'); ?>
			</label>
			<input
				class="widefat"
				type="color"
				id="<?php echo esc_attr($this->get_field_id('color')); ?>"
				name="<?php echo esc_attr($this->get_field_name('color')); ?>"
				value="<?php echo $color; ?>"
			>
		</div>
		<?php
        return (string) ob_get_clean();
    }
}
