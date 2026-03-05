<?php
/**
 * WsFormModule::module_classnames()
 *
 * @package WS_Form\Modules\WsFormModule
 * @since 1.0.0
 */

namespace WS_Form\Modules\WsFormModule\WsFormModuleTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Text\TextClassnames;

trait ModuleClassnamesTrait {

	/**
	 * Module classnames function for WS Form module.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     @type object $classnamesInstance Instance of ET\Builder\Packages\Module\Layout\Components\Classnames.
	 *     @type array  $attrs              Block attributes data being rendered.
	 * }
	 */
	public static function module_classnames( $args ) {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'];

		$text_options_classnames = TextClassnames::text_options_classnames(
			$attrs['module']['advanced']['text'] ?? []
		);

		if ( $text_options_classnames ) {
			$classnames_instance->add( $text_options_classnames, true );
		}
	}
}
