<?php
/**
 * Module: WS Form Module class.
 *
 * @package WS_Form\Modules\WsFormModule
 * @since 1.0.0
 */

namespace WS_Form\Modules\WsFormModule;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * `WsFormModule` registers the WS Form Divi 5 module and its render callback.
 *
 * @since 1.0.0
 */
class WsFormModule implements DependencyInterface {
	use WsFormModuleTrait\RenderCallbackTrait;
	use WsFormModuleTrait\ModuleClassnamesTrait;
	use WsFormModuleTrait\ModuleStylesTrait;
	use WsFormModuleTrait\ModuleScriptDataTrait;

	/**
	 * Loads WsFormModule and registers the front-end render callback.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load() {
		$module_json_folder_path = WS_FORM_DIVI5_JSON_PATH . 'ws-form-module/';

		add_action(
			'init',
			function() use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					[
						'render_callback' => [ WsFormModule::class, 'render_callback' ],
					]
				);
			}
		);
	}
}
