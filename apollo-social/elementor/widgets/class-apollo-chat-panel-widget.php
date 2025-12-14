<?php

/**
 * Apollo Chat Panel Widget for Elementor (stub)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Chat_Panel_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'apollo_chat_panel';
	}

	public function get_title() {
		return 'Apollo Chat Panel';
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_categories() {
		return [ 'apollo-social' ];
	}

	protected function register_controls() {
		// TODO: add chat configuration controls
	}

	protected function render() {
		// TODO: implement chat panel rendering
	}
}
