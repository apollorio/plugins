<?php

namespace Apollo\Modules\Builder\Widgets;

use WP_Widget;

/**
 * Apollo Module Widget Base
 *
 * NOTE: This is DIFFERENT from apollo-social/src/Builder/widgets/class-widget-base.php
 * This extends WP_Widget for WordPress widget system integration.
 * The other Apollo_Widget_Base is for the Apollo Builder canvas system.
 */
abstract class Apollo_Module_Widget_Base extends WP_Widget {

	public function __construct( string $idBase, string $name, array $widgetOptions = array(), array $controlOptions = array() ) {
		parent::__construct(
			$idBase,
			$name,
			array_merge(
				array(
					'classname'   => $idBase,
					'description' => __( 'Widget personalizado Apollo', 'apollo-social' ),
				),
				$widgetOptions
			),
			$controlOptions
		);
	}

	/**
	 * Render widget on front-end.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance data.
	 * @return void
	 */
	public function widget( $args, $instance ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render( $args, $instance );
	}

	/**
	 * Render widget form in admin.
	 *
	 * @param array $instance Widget instance data.
	 * @return void
	 */
	public function form( $instance ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->renderForm( $instance );
	}

	/**
	 * Handle saving of instance.
	 */
	public function update( $newInstance, $oldInstance ) {
		return $this->sanitize( $newInstance, $oldInstance );
	}

	/**
	 * Subclasses should implement HTML output.
	 */
	abstract protected function render( array $args, array $instance ): string;

	/**
	 * Subclasses should implement admin form HTML.
	 */
	abstract protected function renderForm( array $instance ): string;

	/**
	 * Sanitize data before saving.
	 *
	 * @param array $newInstance New instance data.
	 * @param array $oldInstance Old instance data.
	 * @return array Sanitized instance data.
	 */
	protected function sanitize( array $newInstance, array $oldInstance ): array {
		return array_map( 'sanitize_text_field', $newInstance );
	}
}
