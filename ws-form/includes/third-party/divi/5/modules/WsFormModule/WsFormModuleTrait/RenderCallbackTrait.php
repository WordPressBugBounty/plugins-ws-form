<?php
/**
 * WsFormModule::render_callback()
 *
 * @package WS_Form\Modules\WsFormModule
 * @since 1.0.0
 */

namespace WS_Form\Modules\WsFormModule\WsFormModuleTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use WS_Form\Modules\WsFormModule\WsFormModule;

trait RenderCallbackTrait {

	/**
	 * WS Form module render callback — outputs server-side rendered HTML on the front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array          $attrs    Block attributes saved by the Visual Builder.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object being rendered.
	 * @param object         $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$form_id = isset( $attrs['formId']['desktop']['value'] )
			? absint( $attrs['formId']['desktop']['value'] )
			: 0;

		// Build inner content.
		if ( $form_id > 0 ) {
			$inner_content = do_shortcode(
				sprintf( '[%s id="%u"]', WS_FORM_SHORTCODE, $form_id )
			);
		} else {
			$inner_content = HTMLUtility::render(
				[
					'tag'               => 'div',
					'attributes'        => [
						'class' => 'ws_form_divi5_no_form_id',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => HTMLUtility::render(
						[
							'tag'      => 'h2',
							'children' => esc_html( WS_FORM_NAME_GENERIC ),
						]
					) . HTMLUtility::render(
						[
							'tag'      => 'p',
							'children' => esc_html__(
								'Select the form that you would like to use for this Divi module.',
								'ws-form'
							),
						]
					),
				]
			);
		}

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				// FE only.
				'orderIndex'          => $block->parsed_block['orderIndex'],
				'storeInstance'       => $block->parsed_block['storeInstance'],

				// VB equivalent.
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'],
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'classnamesFunction'  => [ WsFormModule::class, 'module_classnames' ],
				'stylesComponent'     => [ WsFormModule::class, 'module_styles' ],
				'scriptDataComponent' => [ WsFormModule::class, 'module_script_data' ],
				'parentAttrs'         => $parent_attrs,
				'parentId'            => $parent->id ?? '',
				'parentName'          => $parent->blockName ?? '',
				'children'            => [
					ElementComponents::component(
						[
							'attrs'         => $attrs['module']['decoration'] ?? [],
							'id'            => $block->parsed_block['id'],
							'orderIndex'    => $block->parsed_block['orderIndex'],
							'storeInstance' => $block->parsed_block['storeInstance'],
						]
					),
					HTMLUtility::render(
						[
							'tag'               => 'div',
							'attributes'        => [
								'class' => 'ws_form_divi5__inner',
							],
							'childrenSanitizer' => 'et_core_esc_previously',
							'children'          => $inner_content,
						]
					),
				],
			]
		);
	}
}