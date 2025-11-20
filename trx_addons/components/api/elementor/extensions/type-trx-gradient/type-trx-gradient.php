<?php
defined( 'ABSPATH' ) || exit();

/**
 * Adds a new parameter type for Elementor widgets
 */
class TrxAddonsElementorParameterTypeGradient extends TRX_Addons_Elementor_Control_Type {
	protected $type = 'trx_gradient';
}

new TrxAddonsElementorParameterTypeGradient();
