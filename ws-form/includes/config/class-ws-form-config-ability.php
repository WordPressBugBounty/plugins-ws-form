<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

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

						return $ws_form_ability->permission_callback( 'publish_form' );
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

				// Form - Draft
				WS_FORM_ABILITY_API_NAMESPACE . 'form-draft' => [

					'label' => __('Draft form', 'ws-form'),
					'description' => __('Set a published form back to draft by ID. Draft forms cannot be used on live web pages until they are published again.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'publish_form' );
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

						return $ws_form_ability->form_draft( $input );
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
					'description' => __('Returns statistical data about a form by ID including total views, saves, submissions, daily figures, as well as the total number of submission records and how many of those are unread.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_submission' );
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
							],

							'daily' => [

								'type' => 'array',
								'description' => 'Daily statistical figures for the form. When a date range is provided, one entry is returned for each day in that range (including days with zero activity). When no date range is provided, one entry is returned for each day from the first recorded statistic through today. Returns an empty array if no statistics are available.',
								'items' => [

									'type' => 'object',
									'description' => 'Statistics for a single day.',
									'properties' => [

										'date' => [

											'type' => 'string',
											'format' => 'date',
											'description' => 'The date in YYYY-MM-DD format in the WordPress website timezone.'
										],

										'count_view' => [

											'type' => 'number',
											'description' => 'The number of times the form was viewed publicly on this day.'
										],

										'count_save' => [

											'type' => 'number',
											'description' => 'The number of times someone saved their progress on this day.'
										],

										'count_submit' => [

											'type' => 'number',
											'description' => 'The number of times the form was submitted on this day.'
										]
									]
								]
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

				// Submissions - List
				WS_FORM_ABILITY_API_NAMESPACE . 'submissions' => [

					'label' => __('List submissions', 'ws-form'),
					'description' => __('Returns a list of submissions for a form by ID. Supports optional date range, status, keyword search, pagination, and ordering.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_submission' );
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
								'description' => 'Optional. The from date in YYYY-MM-DD format. Date / time must be provided in WordPress website timezone. Leave blank for none.'
							],

							'date_to' => [

								'type' => 'string',
								'format' => 'date',
								'description' => 'Optional. The to date in YYYY-MM-DD format. Date / time must be provided in WordPress website timezone. Leave blank for none.'
							],

							'status' => [

								'type' => 'string',
								'description' => 'Optional. Filter submissions by status. Valid values are all (excludes trash and spam), publish (submitted), draft (in progress), error, spam, or trash. Defaults to all.',
								'enum' => ['all', 'publish', 'draft', 'error', 'spam', 'trash'],
								'default' => 'all'
							],

							'keyword' => [

								'type' => 'string',
								'description' => 'Optional. Keyword to search submission field values for.',
								'default' => ''
							],

							'limit' => [

								'type' => 'number',
								'description' => 'Optional. Maximum number of submissions to return. Defaults to 50.',
								'default' => 50
							],

							'offset' => [

								'type' => 'number',
								'description' => 'Optional. Number of submissions to skip. Defaults to 0.',
								'default' => 0
							],

							'order_by' => [

								'type' => 'string',
								'description' => 'Optional. Column to order submissions by. Valid values are id, date_added, or date_updated. Defaults to id.',
								'enum' => ['id', 'date_added', 'date_updated'],
								'default' => 'id'
							],

							'order' => [

								'type' => 'string',
								'description' => 'Optional. How to order the submissions. Valid values are ASC or DESC. Defaults to DESC.',
								'enum' => ['ASC', 'DESC'],
								'default' => 'DESC'
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

							'total' => [

								'type' => 'number',
								'description' => 'The total number of submissions matching the filters.'
							],

							'submissions' => [

								'type' => 'array',
								'description' => 'The submissions.',
								'items' => [

									'type' => 'object',
									'description' => 'A submission.',
									'properties' => [

										'id' => [

											'type' => 'number',
											'description' => 'The submission ID.'
										],

										'status' => [

											'type' => 'string',
											'description' => 'The submission status code.'
										],

										'status_full' => [

											'type' => 'string',
											'description' => 'The human-readable submission status.'
										],

										'date_added' => [

											'type' => 'string',
											'description' => 'The date and time the submission was added, in the WordPress website timezone.'
										],

										'date_updated' => [

											'type' => 'string',
											'description' => 'The date and time the submission was last updated, in the WordPress website timezone.'
										],

										'user_id' => [

											'type' => 'number',
											'description' => 'The WordPress user ID associated with the submission, or 0 if none.'
										],

										'duration' => [

											'type' => 'number',
											'description' => 'How long the submitter took to complete the form, in seconds.'
										],

										'fields' => [

											'type' => 'array',
											'description' => 'The field values for the submission.',
											'items' => [

												'type' => 'object',
												'description' => 'A field value.',
												'properties' => [

													'id' => [

														'type' => 'number',
														'description' => 'The field ID.'
													],

													'label' => [

														'type' => 'string',
														'description' => 'The field label.'
													],

													'type' => [

														'type' => 'string',
														'description' => 'The field type.'
													],

													'value' => [

														'type' => 'string',
														'description' => 'The field value.'
													]
												]
											]
										]
									]
								]
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->submissions( $input );
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

				// Submission - Get
				WS_FORM_ABILITY_API_NAMESPACE . 'submission-get' => [

					'label' => __('Get submission', 'ws-form'),
					'description' => __('Returns a single submission by ID including its field values.', 'ws-form'),
					'category' => 'ws-form',
					'permission_callback' => function() use ( $ws_form_ability ) {

						return $ws_form_ability->permission_callback( 'read_submission' );
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The submission ID.'
							]
						]
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => [

							'id' => [

								'type' => 'number',
								'description' => 'The submission ID.'
							],

							'form_id' => [

								'type' => 'number',
								'description' => 'The form ID the submission belongs to.'
							],

							'status' => [

								'type' => 'string',
								'description' => 'The submission status code.'
							],

							'status_full' => [

								'type' => 'string',
								'description' => 'The human-readable submission status.'
							],

							'date_added' => [

								'type' => 'string',
								'description' => 'The date and time the submission was added, in the WordPress website timezone.'
							],

							'date_updated' => [

								'type' => 'string',
								'description' => 'The date and time the submission was last updated, in the WordPress website timezone.'
							],

							'user_id' => [

								'type' => 'number',
								'description' => 'The WordPress user ID associated with the submission, or 0 if none.'
							],

							'duration' => [

								'type' => 'number',
								'description' => 'How long the submitter took to complete the form, in seconds.'
							],

							'fields' => [

								'type' => 'array',
								'description' => 'The field values for the submission.',
								'items' => [

									'type' => 'object',
									'description' => 'A field value.',
									'properties' => [

										'id' => [

											'type' => 'number',
											'description' => 'The field ID.'
										],

										'label' => [

											'type' => 'string',
											'description' => 'The field label.'
										],

										'type' => [

											'type' => 'string',
											'description' => 'The field type.'
										],

										'value' => [

											'type' => 'string',
											'description' => 'The field value.'
										]
									]
								]
							]
						]
					],
					'execute_callback' => function( $input ) use ( $ws_form_ability ) {

						return $ws_form_ability->submission_get( $input );
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

			// Apply MCP public exposure setting (meta.mcp.public)
			$mcp_public = (bool) WS_Form_Common::option_get( 'mcp_adapter_public', true );

			foreach ( $abilities as $ability_name => $ability ) {

				if ( isset( $ability['meta']['mcp'] ) ) {

					$abilities[ $ability_name ]['meta']['mcp']['public'] = $mcp_public;
				}
			}

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