<?php

namespace Apollo\Modules\Builder\Widgets;

use WP_Widget;

abstract class Apollo_Widget_Base extends WP_Widget
{
    public function __construct(string $idBase, string $name, array $widgetOptions = [], array $controlOptions = [])
    {
        parent::__construct(
            $idBase,
            $name,
            array_merge(
                [
                    'classname'   => $idBase,
                    'description' => __('Widget personalizado Apollo', 'apollo-social'),
                ],
                $widgetOptions
            ),
            $controlOptions
        );
    }

    /**
     * Render widget on front-end.
     */
    public function widget($args, $instance)
    {
        echo $this->render($args, $instance);
    }

    /**
     * Render widget form in admin.
     */
    public function form($instance)
    {
        echo $this->renderForm($instance);
    }

    /**
     * Handle saving of instance.
     */
    public function update($newInstance, $oldInstance)
    {
        return $this->sanitize($newInstance, $oldInstance);
    }

    /**
     * Subclasses should implement HTML output.
     */
    abstract protected function render(array $args, array $instance): string;

    /**
     * Subclasses should implement admin form HTML.
     */
    abstract protected function renderForm(array $instance): string;

    /**
     * Sanitize data before saving.
     */
    protected function sanitize(array $newInstance, array $oldInstance): array
    {
        return array_map('sanitize_text_field', $newInstance);
    }
}
