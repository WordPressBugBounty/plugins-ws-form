<?php
/**
 * WsFormModule::module_styles()
 *
 * @package WS_Form\Modules\WsFormModule
 * @since 1.0.0
 */

namespace WS_Form\Modules\WsFormModule\WsFormModuleTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use WS_Form\Modules\WsFormModule\WsFormModule;

trait ModuleStylesTrait {

	use CustomCssTrait;

	/**
	 * WS Form module style components.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *   @type string         $id                Module ID.
	 *   @type string         $name              Module name.
	 *   @type array          $attrs             Module attributes.
	 *   @type array          $parentAttrs       Parent attrs.
	 *   @type string         $orderClass        Selector class name.
	 *   @type string         $parentOrderClass  Parent selector class name.
	 *   @type string         $wrapperOrderClass Wrapper selector class name.
	 *   @type array          $settings          Custom settings.
	 *   @type string         $state             Attributes state.
	 *   @type string         $mode              Style mode.
	 *   @type object         $elements          ModuleElements instance.
	 * }
	 */
	public static function module_styles( $args ) {
		$attrs    = $args['attrs'] ?? [];
		$elements = $args['elements'];
		$settings = $args['settings'] ?? [];

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					// Module-level decoration styles (background, spacing, border, etc.).
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'disabledOn' => [
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								],
							],
						]
					),

					// Custom CSS fields.
					CssStyle::style(
						[
							'selector'  => $args['orderClass'],
							'attr'      => $attrs['css'] ?? [],
							'cssFields' => WsFormModule::custom_css(),
						]
					),
				],
			]
		);
	}
}
