<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Config_Ability_Structure {

		// Structure / action / conditional abilities
		public static function get_abilities($ws_form_ability, $ws_form_form_ai) {

			$edit_permission = function() use ( $ws_form_ability ) {

				return (

					WS_Form_Common::option_get( 'abilities_api_edit_form', false ) &&
					$ws_form_ability->permission_callback( 'edit_form' )
				);
			};

			$read_permission = function() use ( $ws_form_ability ) {

				return $ws_form_ability->permission_callback( 'read_form' );
			};

			$mcp_tool = function($priority = 2.0, $destructive = false, $read_only = false) {

				return array(

					'annotations' => array(

						'priority' => $priority,
						'readOnlyHint' => $read_only,
						'destructiveHint' => $destructive,
						'idempotentHint' => false,
						'openWorldHint' => false
					),
					'mcp' => array(

						'public' => false,
						'type' => 'tool',
					)
				);
			};

			$form_id_prop = array(

				'type' => 'number',
				'description' => 'The form ID.'
			);

			$url_props = array(

				'url_edit' => array(

					'type' => 'string',
					'format' => 'uri',
					'description' => 'The URL to edit the form.'
				),
				'url_preview' => array(

					'type' => 'string',
					'format' => 'uri',
					'description' => 'The URL to preview the form.'
				),
				'json' => array(

					'type' => 'string',
					'description' => 'The form JSON.'
				)
			);

			$row_target_props = array(

				'row_id' => array(

					'type' => 'number',
					'description' => 'Preferred. Stable data-grid row ID from the actions or conditionals list tool. Survives reorder.',
					'default' => 0
				),
				'index' => array(

					'type' => 'number',
					'description' => 'Optional 1-based position from the list tool. Use if row_id is unknown.',
					'default' => 0
				),
				'label_match' => array(

					'type' => 'string',
					'description' => 'Optional. Unique label to match. Fails if multiple rows share the label.',
					'default' => ''
				)
			);

			$abilities = array(

				WS_FORM_ABILITY_API_NAMESPACE . 'tabs' => array(

					'label' => __('List tabs', 'ws-form'),
					'description' => __('Returns all tabs (groups) for a form with id, label, and 1-based index. Use tab IDs with tab-update, tab-delete, and section-add (group_id).', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $read_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array('id' => $form_id_prop)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tabs' => array(

								'type' => 'array',
								'description' => 'The tabs.',
								'items' => array(

									'type' => 'object',
									'properties' => array(

										'id' => array('type' => 'number', 'description' => 'Tab (group) ID.'),
										'label' => array('type' => 'string', 'description' => 'Tab label.'),
										'index' => array('type' => 'number', 'description' => '1-based index.')
									)
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->tabs( $input );
					},
					'meta' => $mcp_tool(1.0, false, true)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'sections' => array(

					'label' => __('List sections', 'ws-form'),
					'description' => __('Returns all sections for a form with id, label, parent group_id (tab id), and index. Use with section-update, section-delete, and field-add.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $read_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array('id' => $form_id_prop)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'sections' => array(

								'type' => 'array',
								'description' => 'The sections.',
								'items' => array(

									'type' => 'object',
									'properties' => array(

										'id' => array('type' => 'number', 'description' => 'Section ID.'),
										'label' => array('type' => 'string', 'description' => 'Section label.'),
										'group_id' => array('type' => 'number', 'description' => 'Parent tab (group) ID.'),
										'index' => array('type' => 'number', 'description' => '1-based index within the tab.')
									)
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->sections( $input );
					},
					'meta' => $mcp_tool(1.0, false, true)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'fields' => array(

					'label' => __('List fields', 'ws-form'),
					'description' => __('Returns all fields for a form with id, label, type, section_id, and group_id. Prefer this over form-get-json when you only need to find field IDs for field-update, field-delete, or field-add.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $read_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array('id' => $form_id_prop)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'fields' => array(

								'type' => 'array',
								'description' => 'The fields.',
								'items' => array(

									'type' => 'object',
									'properties' => array(

										'id' => array('type' => 'number', 'description' => 'Field ID.'),
										'label' => array('type' => 'string', 'description' => 'Field label.'),
										'type' => array('type' => 'string', 'description' => 'Field type.'),
										'section_id' => array('type' => 'number', 'description' => 'Parent section ID.'),
										'group_id' => array('type' => 'number', 'description' => 'Parent tab (group) ID.')
									)
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->fields( $input );
					},
					'meta' => $mcp_tool(1.0, false, true)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'tab-add' => array(

					'label' => __('Add tab', 'ws-form'),
					'description' => __('Add a tab (group) to a form. First use the tabs tool if you need existing tab IDs. When a form has multiple tabs, also add tab_next / tab_previous fields with field-add as needed.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'label' => array(

								'type' => 'string',
								'description' => 'The tab label.',
								'default' => ''
							),
							'tab_id_before' => array(

								'type' => 'number',
								'description' => 'Optional. Insert before this tab ID. Set to 0 to add at the end.',
								'default' => 0
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tab' => array(

								'type' => 'object',
								'description' => 'The added tab.',
								'properties' => array(

									'id' => array('type' => 'number', 'description' => 'The tab ID.'),
									'label' => array('type' => 'string', 'description' => 'The tab label.')
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->tab_add( $input );
					},
					'meta' => $mcp_tool(2.5)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'tab-update' => array(

					'label' => __('Update tab', 'ws-form'),
					'description' => __('Update a tab label by tab ID. Use the tabs tool to get tab IDs.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tab_id' => array(

								'type' => 'number',
								'description' => 'The tab (group) ID.'
							),
							'label' => array(

								'type' => 'string',
								'description' => 'The new tab label.'
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tab' => array(

								'type' => 'object',
								'description' => 'The updated tab.',
								'properties' => array(

									'id' => array('type' => 'number', 'description' => 'The tab ID.'),
									'label' => array('type' => 'string', 'description' => 'The tab label.')
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->tab_update( $input );
					},
					'meta' => $mcp_tool(2.5)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'tab-delete' => array(

					'label' => __('Delete tab', 'ws-form'),
					'description' => __('Permanently delete a tab and its sections/fields. Cannot delete the last tab on a form. Use the tabs tool to get tab IDs.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tab_id' => array(

								'type' => 'number',
								'description' => 'The tab (group) ID to delete.'
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'tab_id' => array('type' => 'number', 'description' => 'The deleted tab ID.'),
							'message' => array('type' => 'string', 'description' => 'Result message.')
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->tab_delete( $input );
					},
					'meta' => $mcp_tool(3.0, true)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'section-add' => array(

					'label' => __('Add section', 'ws-form'),
					'description' => __('Add a section to a tab. Use tabs to get group_id (tab id), and sections if inserting before another section.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'group_id' => array(

								'type' => 'number',
								'description' => 'The tab (group) ID that will contain the section.'
							),
							'label' => array(

								'type' => 'string',
								'description' => 'The section label.',
								'default' => ''
							),
							'section_id_before' => array(

								'type' => 'number',
								'description' => 'Optional. Insert before this section ID. Set to 0 to add at the end.',
								'default' => 0
							),
							'meta' => array(

								'type' => 'object',
								'description' => 'Optional section meta. Supported: label_render (boolean or on/off) to show or hide the section label.'
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'section' => array(

								'type' => 'object',
								'description' => 'The added section.',
								'properties' => array(

									'id' => array('type' => 'number', 'description' => 'The section ID.'),
									'label' => array('type' => 'string', 'description' => 'The section label.'),
									'group_id' => array('type' => 'number', 'description' => 'Parent tab ID.'),
									'meta' => array('type' => 'object', 'description' => 'Section meta.')
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->section_add( $input );
					},
					'meta' => $mcp_tool(2.5)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'section-update' => array(

					'label' => __('Update section', 'ws-form'),
					'description' => __('Update a section label and/or meta (label_render). Use the sections tool to get section IDs.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'section_id' => array(

								'type' => 'number',
								'description' => 'The section ID.'
							),
							'label' => array(

								'type' => 'string',
								'description' => 'Optional. New section label.',
								'default' => ''
							),
							'meta' => array(

								'type' => 'object',
								'description' => 'Optional. Supported: label_render (boolean or on/off).'
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'section' => array(

								'type' => 'object',
								'description' => 'The updated section.',
								'properties' => array(

									'id' => array('type' => 'number', 'description' => 'The section ID.'),
									'label' => array('type' => 'string', 'description' => 'The section label.'),
									'group_id' => array('type' => 'number', 'description' => 'Parent tab ID.'),
									'meta' => array('type' => 'object', 'description' => 'Section meta.')
								)
							)
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->section_update( $input );
					},
					'meta' => $mcp_tool(2.5)
				),

				WS_FORM_ABILITY_API_NAMESPACE . 'section-delete' => array(

					'label' => __('Delete section', 'ws-form'),
					'description' => __('Permanently delete a section and its fields. Cannot delete the last section on a tab. Use the sections tool to get section IDs.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => $edit_permission,
					'input_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'section_id' => array(

								'type' => 'number',
								'description' => 'The section ID to delete.'
							)
						)
					),
					'output_schema' => array(

						'type' => 'object',
						'properties' => array(

							'id' => $form_id_prop,
							'section_id' => array('type' => 'number', 'description' => 'The deleted section ID.'),
							'message' => array('type' => 'string', 'description' => 'Result message.')
						)
					),
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->section_delete( $input );
					},
					'meta' => $mcp_tool(3.0, true)
				),
			);


			return $abilities;
		}
	}
