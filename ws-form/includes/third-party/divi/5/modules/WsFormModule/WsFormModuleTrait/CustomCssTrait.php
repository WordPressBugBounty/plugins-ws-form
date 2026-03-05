<?php
/**
 * WsFormModule::custom_css()
 *
 * @package WS_Form\Modules\WsFormModule
 * @since 1.0.0
 */

namespace WS_Form\Modules\WsFormModule\WsFormModuleTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait CustomCssTrait {

	/**
	 * Custom CSS fields.
	 *
	 * Equivalent of JS const cssFields in src/components/ws-form-module/custom-css.ts.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function custom_css() {
		return \WP_Block_Type_Registry::get_instance()->get_registered( 'wsform/ws-form-module' )->customCssFields;
	}
}
