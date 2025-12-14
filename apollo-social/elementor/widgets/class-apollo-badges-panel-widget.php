<?php

/**
 * Apollo Badges Panel Widget for Elementor (stub)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Badges_Panel_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'apollo_badges_panel';
	}

	public function get_title() {
		return 'Apollo Badges Panel';
	}

	public function get_icon() {
		return 'eicon-trophy';
	}

	public function get_categories() {
		return [ 'apollo-social' ];
	}

	protected function register_controls() {
		// TODO: add badges display controls
	}

	protected function render() {
		// TODO: implement badges panel rendering
	}
}
