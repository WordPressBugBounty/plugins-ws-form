<?php

	class WS_Form_Config_Ability {

		public static $abilities = false;

		// Configuration - Abilities
		public static function get_abilities() {

			// Check cache
			if(self::$abilities !== false) { return self::$abilities; }

			$ws_form_form_ai = new WS_Form_Form_AI();
			$ws_form_ability = new WS_Form_Ability();

			// Abilities
			$abilities = [

				// Forms - List
				WS_FORM_ABILITY_API_NAMESPACE . 'forms' => [

					'label' => __('List forms', 'ws-form'),
					'description' => __('Returns a list of all of the forms in WS Form.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'published' => [

								'type' => 'boolean',
								'description' => 'Optionally whether to retrieve only published forms. Published forms are those that can be added to pages and posts. Unpublished forms are in a draft state and are still being developed. Defaults to true.',
								'default' => true
							],

							'order_by' => [

								'type' => 'string',
								'description' => 'Optionally which column to order the forms by. Valid values are id or label. Defaults to label.',
								'enum' => ['label', 'id'],
								'default' => 'label'
							],

							'order' => [

								'type' => 'string',
								'description' => 'Optionally how to order the forms. Valid values are ASC or DESC. Defaults to label.',
								'enum' => ['ASC', 'DESC'],
								'default' => 'ASC'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'forms' => [

								'type' => 'array',

								'description' => 'The forms.',

								'items' => [

									'type' => 'object',

									'description' => 'A form.',

									'properties' => [

										'id' => [

											'type' => 'number',
											'description' => 'The form ID.'
										],

										'label' => [

											'type' => 'string',
											'description' => 'The form label.'
										]
									]
								]
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->forms( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 1.0,
							'readOnlyHint' => true,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Create - JSON
				WS_FORM_ABILITY_API_NAMESPACE . 'form-create-json' => [

					'label' => __('Create form from a description', 'ws-form'),
					'description' => __('Use this tool to create a new form from a description provided by the user. The tool generates the form using a JSON definition that must follow the required structure described in the json input property.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( [ 'create_form', 'edit_form' ] );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'json' => [

								'type' => 'string',
								'description' => $ws_form_form_ai->get_form_create_json_prompt()
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'block' => [

								'type' => 'string',
								'description' => 'The form block markup.'
							],

							'shortcode' => [

								'type' => 'string',
								'description' => 'The form shortcode.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to edit the form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to preview the new form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_create_json( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.5,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Get JSON
				WS_FORM_ABILITY_API_NAMESPACE . 'form-get-json' => [

					'label' => __('Get form in JSON format', 'ws-form'),
					'description' => __('Returns the form in JSON format by ID.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_get_json( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 1.0,
							'readOnlyHint' => true,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Update from JSON
				WS_FORM_ABILITY_API_NAMESPACE . 'form-update-json' => [

					'label' => __('Update a form from a JSON string', 'ws-form'),
					'description' => __('Use this tool to modify an existing form using JSON data obtained from the form-get-json tool. To add a new field, use the field-add tool instead. The JSON provided must keep the same structure as returned by form-get-json, with no additions or removals of groups, sections, or fields.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return (

							WS_Form_Common::option_get( 'abilities_api_edit_form', false ) &&
							$ws_form_ability->permission_callback( array( 'read_form', 'edit_form' ) )
						);
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'json' => [

								'type' => 'string',
								'description' => $ws_form_form_ai->get_form_update_json_prompt()
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to edit the form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to preview the form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_update_json( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.0,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Publish
				WS_FORM_ABILITY_API_NAMESPACE . 'form-publish' => [

					'label' => __('Publish form', 'ws-form'),
					'description' => __('When a form is created in WS Form it initially has a status of draft. Use this to publish a form by ID so that it can be used on a live web page.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'edit_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'status' => [

								'type' => 'string',
								'description' => 'The new status of the form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_publish( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.0,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Clone
				WS_FORM_ABILITY_API_NAMESPACE . 'form-clone' => [

					'label' => __('Clone form', 'ws-form'),
					'description' => __('Use this tool to clone/copy/duplicate a form. The cloned form will be in a draft state.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( array( 'read_form', 'create_form' ) );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'block' => [

								'type' => 'string',
								'description' => 'The form block markup.'
							],

							'shortcode' => [

								'type' => 'string',
								'description' => 'The form shortcode.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to edit the form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to preview the form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_clone( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.5,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Shortcode
				WS_FORM_ABILITY_API_NAMESPACE . 'form-shortcode' => [

					'label' => __('Get form shortcode', 'ws-form'),
					'description' => __('Gets a shortcode for a form in the WS Form form plugin for WordPress by form ID.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'shortcode' => [

								'type' => 'string',
								'description' => 'The shortcode.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_shortcode( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 1.0,
							'readOnlyHint' => true,
							'destructiveHint' => false,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Block
				WS_FORM_ABILITY_API_NAMESPACE . 'form-block' => [

					'label' => __('Get form block markup', 'ws-form'),
					'description' => __('Gets the block markup for a form in WS Form by form ID. This should only be used for pages edited by the block editor (Gutenberg).', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'markup' => [

								'type' => 'string',
								'description' => 'The block markup.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_block( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 1.0,
							'readOnlyHint' => true,
							'destructiveHint' => false,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form
				WS_FORM_ABILITY_API_NAMESPACE . 'form-stats' => [

					'label' => __('Get statistical data about a form by ID', 'ws-form'),
					'description' => __('Returns statistical data about a form by ID including total views, saves, submissions as well as the total number of submission records and how many of those are unread.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'date_from' => [

								'type' => 'string',
								'format' => 'date',
								'description' => 'The from date in YYYY-MM-DD format. Date / time must be provided in WordPress website timezone. Leave blank for none.'
							],

							'date_to' => [

								'type' => 'string',
								'format' => 'date',
								'description' => 'Optional. The to date in YYYY-MM-DD format. Date / time must be provided in WordPress website timezone. Leave blank for none.'
							]
						],
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'label' => [

								'type' => 'string',
								'description' => 'The form label.'
							],

							'status' => [

								'type' => 'string',
								'description' => 'The form status. "draft" means the form is still being developed. "publish" means a live and completed version of the form exists.'
							],

							'count_stat_view' => [

								'type' => 'number',
								'description' => 'The total number of times the form has been viewed publicly within the date range provided. Views by administrators are only included if WS Form > Settings > Basic > Statistics > Include Admin Traffic has been enabled.'
							],

							'count_stat_save' => [

								'type' => 'number',
								'description' => 'The total number of times someone has viewed the form and clicked "Save" to save their progress within the date range provided.'
							],

							'count_stat_submit' => [

								'type' => 'number',
								'description' => 'The total number of times someone has submitted the form within the date range provided.'
							],

							'count_submit' => [

								'type' => 'number',
								'description' => 'The total number of submission records that exist for the form. This can differ from count_stat_submit because submission records can be trashed. This is always for all time.'
							],

							'count_submit_unread' => [

								'type' => 'number',
								'description' => 'The total number of submission records that have not been read yet. This is always for all time.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_stats( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 1.0,
							'readOnlyHint' => true,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Delete
				WS_FORM_ABILITY_API_NAMESPACE . 'form-delete' => [

					'label' => __('Delete form', 'ws-form'),
					'description' => __('Trash or permanently delete a form by ID.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return (
							WS_Form_Common::option_get( 'abilities_api_delete_form', false ) &&
							$ws_form_ability->permission_callback( 'delete_form' )
						);
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'permanent' => [

								'type' => 'boolean',
								'description' => 'If set to true, the form will be permanently deleted. If set to false, the form will be moved to trash and can later be restored.',
								'default' => false
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'permanent' => [

								'type' => 'boolean',
								'description' => 'Whether the form was permanently deleted.'
							],

							'message' => [

								'type' => 'string',
								'description' => 'A message describing whether the form was permanently deleted.'
							],

							'url' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'A suggested URL to redirect to after the form is deleted.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_delete( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 3.0,
							'readOnlyHint' => false,
							'destructiveHint' => true,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Form - Restore
				WS_FORM_ABILITY_API_NAMESPACE . 'form-restore' => [

					'label' => __('Restore form', 'ws-form'),
					'description' => __('Restore a form from the trash by ID.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'delete_form' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The admin URL of the restored form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The preview URL of the restored form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->form_restore( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.0,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Field - Add
				WS_FORM_ABILITY_API_NAMESPACE . 'field-add' => [

					'label' => __('Add field', 'ws-form'),
					'description' => __('Use this tool to add or insert a new field into a form. First, use the form-get-json tool to obtain the form JSON so you can specify the section_id where the field should be added, and optionally the field_id_before if you want it inserted before another field.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return (

							WS_Form_Common::option_get( 'abilities_api_edit_form', false ) &&
							$ws_form_ability->permission_callback( 'edit_form' )
						);
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'section_id' => [

								'type' => 'number',
								'description' => 'The section ID that must exist in the specified form ID.'
							],

							'label' => [

								'type' => 'string',
								'description' => 'The field label.'
							],

							'type' => [

								'type' => 'string',
								'enum' => $ws_form_form_ai->get_field_type_ids(),
								'default' => 'text',
								'description' => $ws_form_form_ai->get_field_add_type_prompt()
							],

							'meta' => [

								'type' => 'object',
								'description' => $ws_form_form_ai->get_field_add_meta_prompt()
							],

							'field_id_before' => [

								'type' => 'number',
								'description' => 'The field ID the new field will be inserted before. The field ID must be in the specified section_id. Set to 0 to add the field to the end of the section.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'field_id' => [

								'type' => 'number',
								'description' => 'The ID of the added field.'
							],

							'field_label' => [

								'type' => 'string',
								'description' => 'The label of the added field.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to edit the form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to preview the form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->field_add( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 2.5,
							'readOnlyHint' => false,
							'destructiveHint' => false,
							'idempotentHint' => false,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],

				// Field - Delete
				WS_FORM_ABILITY_API_NAMESPACE . 'field-delete' => [

					'label' => __('Delete field', 'ws-form'),
					'description' => __('Use this tool to delete a field from a form.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return (

							WS_Form_Common::option_get( 'abilities_api_edit_form', false ) &&
							$ws_form_ability->permission_callback( 'edit_form' )
						);
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'field_id' => [

								'type' => 'number',
								'description' => 'The field ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The form ID.'
							],

							'field_id' => [

								'type' => 'number',
								'description' => 'The ID of the deleted field.'
							],

							'message' => [

								'type' => 'string',
								'description' => 'A message describing whether the field was permanently deleted.'
							],

							'json' => [

								'type' => 'string',
								'description' => 'The form JSON.'
							],

							'url_edit' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to edit the form.'
							],

							'url_preview' => [

								'type' => 'string',
								'format' => 'uri',
								'description' => 'The URL to preview the form.'
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->field_delete( $input );
					},
					'meta' => [
						'annotations' => [
							'priority' => 3.0,
							'readOnlyHint' => false,
							'destructiveHint' => true,
							'idempotentHint' => true,
							'openWorldHint' => false
						],
						'mcp' => [
							'public' => false,
							'type'   => 'tool',
						]
					]
				],
			];

			// Apply filter
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$abilities = apply_filters('wsf_config_abilities', $abilities);
			if (!is_array($abilities)) {
				$abilities = [];
			}

			// Store in cache
			self::$abilities = $abilities;

			return $abilities;
		}

		// Get an ability by ID
		public static function get_ability($id) {

			// Check cache
			if(self::$abilities === false) {

				// Build cache
				self::$abilities = self::get_abilities();
			}

			return isset(self::$abilities[$id]) ? self::$abilities[$id] : false;
		}

		// Get ability input schema
		public static function get_ability_input_schema($id) {

			$ability = self::get_ability($id);

			if($ability === false) { return false; }

			return isset($ability['input_schema']) ? $ability['input_schema'] : false;
		}

		// Get ability output schema
		public static function get_ability_output_schema($id) {

			$ability = self::get_ability($id);

			if($ability === false) { return false; }

			return isset($ability['output_schema']) ? $ability['output_schema'] : false;
		}
	}