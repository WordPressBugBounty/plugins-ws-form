<?php
/**
 * Register WS Form Divi 5 module with the dependency tree.
 *
 * @package WS_Form\Modules
 * @since 1.0.0
 */

namespace WS_Form\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use WS_Form\Modules\WsFormModule\WsFormModule;

add_action(
	'divi_module_library_modules_dependency_tree',
	function ( $dependency_tree ) {
		$dependency_tree->add_dependency( new WsFormModule() );
	}
);
