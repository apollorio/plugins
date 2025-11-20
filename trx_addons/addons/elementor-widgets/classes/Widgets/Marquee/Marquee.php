<?php
/**
 * Marquee Module
 *
 * @package ThemeREX Addons
 * @since v2.35.3
 */

namespace TrxAddons\ElementorWidgets\Widgets\Marquee;

use TrxAddons\ElementorWidgets\BaseWidgetModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Marquee module
 */
class Marquee extends BaseWidgetModule {

	/**
	 * Constructor.
	 *
	 * Initializing the module base class.
	 */
	public function __construct() {
		parent::__construct();
		$this->assets = array(
			'css' => true,
			'js'  => true,
		);
	}

	/**
	 * Get the name of the module
	 *
	 * @return string  The name of the module.
	 */
	public function get_name() {
		return 'marquee';
	}

	/**
	 * Get the selector for the animation type 'Item by item'
	 * 
	 * @return string  The selector of the single item.
	 */
	public function get_separate_animation_selector() {
		return '.trx-addons-marquee-item';
	}

	/**
	 * Get the class name ot the block for the text animation types 'Line by Line', 'Word by Word', 'Char by Char', etc.
	 * and names of animation types suitable for this widget.
	 * 
	 * @return string  The class name of the widget block with names of supported animation types.
	 */
	public function get_text_animation_class() {
		return array( 'trx-addons-marquee-item' => 'sequental,random,line,word,char' );
	}

}
