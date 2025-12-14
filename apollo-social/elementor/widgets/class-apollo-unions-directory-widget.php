<?php

/**
 * Apollo Unions Directory Widget for Elementor (stub)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Unions_Directory_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'apollo_unions_directory';
	}

	public function get_title() {
		return 'Apollo Unions Directory';
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'apollo-social' ];
	}

	protected function register_controls() {
		// TODO: add unions display controls
	}

	protected function render() {
		// TODO: implement unions directory rendering
	}
}
