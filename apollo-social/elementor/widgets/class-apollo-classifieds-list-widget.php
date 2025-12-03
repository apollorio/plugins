<?php
/**
 * Apollo Classifieds List Widget for Elementor (stub)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Classifieds_List_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'apollo_classifieds_list';
	}

	public function get_title() {
		return 'Apollo Classifieds List';
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return array( 'apollo-social' );
	}

	protected function register_controls() {
		// TODO: add classifieds display controls
	}

	protected function render() {
		// TODO: implement classifieds list rendering
	}
}
