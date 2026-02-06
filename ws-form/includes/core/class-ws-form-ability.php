<?php

	class WS_Form_Ability {

		public $input = false; 

		// Register ability categories
		public function register_categories() {

			wp_register_ability_category(

				'ws-form',

				array(

					'label' => __('WS Form', 'ws-form'),
					'description' => __('Abilities that relate to the plugin WS Form.', 'ws-form')
				)
			);
		}

		// Register abilities
		public function register() {

			// Get abilities
			$abilities = WS_Form_Config::get_abilities();

			// Register abilities
			foreach($abilities as $ability_name => $ability) {

				$registered_ability = wp_register_ability(

					// Ability
					$ability_name,

					// Args
					[
						'label'               => $ability['label'],
						'description'         => $ability['description'],
						'category'            => $ability['category'],
						'input_schema'        => $ability['input_schema'],
						'output_schema'       => $ability['output_schema'],
						'execute_callback'    => $ability['execute_callback'],
						'permission_callback' => $ability['permission_callback'],
						'meta'                => $ability['meta']
					]
				);
			}
		}

		// Forms
		public function forms($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'forms');

				// Initiate instance of Form class
				$ws_form_form = new WS_Form_Form();

				// Get published (sanitized as boolean, returns only true or false)
				$published = self::input_get('published');

				// Get order by (sanitized by enum, returns only label or id)
				$order_by = self::input_get('order_by');

				// Get order (sanitized by enum, returns only ASC or DESC)
				$order = self::input_get('order');

				// Return data
				return [

					'forms' => $ws_form_form->get_all($published, $order_by, $order)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving forms: %s', 'ws-form'));
			}
		}

		// Form - Create - JSON
		public function form_create_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-create-json');

				// Get JSON
				$form_json = self::input_get('json');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Create a new form
				$form_id = $ws_form_form_ai->form_create_json($form_json);

				// Return data
				return [

					'id' => $form_id,
					'json' => $ws_form_form_ai->form_get_json(),
					'block' => WS_Form_Common::block_markup($form_id),
					'shortcode' => WS_Form_Common::shortcode($form_id),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error creating form from JSON: %s', 'ws-form'));
			}
		}

		// Form - Get JSON
		public function form_get_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-get-json');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Read the form
				$form_json = $ws_form_form_ai->form_get_json();

				// Return data
				return [

					'id' => $form_id,
					'json' => $form_json
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving form as JSON: %s', 'ws-form'));
			}
		}

		// Form - Update from JSON
		public function form_update_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-update-json');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get form JSON
				$form_json = self::input_get('json');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Update the form
				$form_json = $ws_form_form_ai->form_update_json($form_json);

				// Return data
				return [

					'id' => $form_id,
					'json' => $form_json,
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating form from JSON: %s', 'ws-form'));
			}
		}

		// Form - Publish
		public function form_publish($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-publish');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Publish the form
				$ws_form_form->db_publish();

				// Return data
				return [

					'status' => 'publish',
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error publishing form: %s', 'ws-form'));
			}
		}

		// Form - Clone
		public function form_clone($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-clone');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Clone the form
				$form_id = $ws_form_form->db_clone();

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Return data
				return [

					'id' => $form_id,
					'json' => $ws_form_form_ai->form_get_json(),
					'block' => WS_Form_Common::block_markup($form_id),
					'shortcode' => WS_Form_Common::shortcode($form_id),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error cloning form: %s', 'ws-form'));
			}
		}

		// Form - Shortcode
		public function form_shortcode($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-shortcode');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Return data
				return [

					'shortcode' => WS_Form_Common::shortcode($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error generating shortcode: %s', 'ws-form'));
			}
		}

		// Form - Block
		public function form_block($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-block');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Return data
				return [

					'markup' => WS_Form_Common::block_markup($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error generating block: %s', 'ws-form'));
			}
		}

		// Form - Statistics
		public function form_stats($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-stats');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get date / time from
				$date_from = self::date_validate(self::input_get('date_from'), false);
				if($date_from === false) {

					throw new Exception(esc_html__('Invalid from date / time.', 'ws-form'));
				}
				$time_from_utc = empty($date_from) ? false : strtotime(get_gmt_from_date($date_from));

				// Get date / time to
				$date_to = self::date_validate(self::input_get('date_to'), true);
				if($date_to === false) {

					throw new Exception(esc_html__('Invalid to date / time.', 'ws-form'));
				}
				$time_to_utc = empty($date_to) ? false : strtotime(get_gmt_from_date($date_to));

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Read the form
				$form_object = $ws_form_form->db_read();

				// Update statistics for form
				$form_object = $ws_form_form->db_count_update($form_object);

				$return_data = [

					'id' => $form_id,
					'label' => esc_html($form_object->label),
					'status' => esc_html($form_object->status),
					'count_submit' => $form_object->count_submit,
					'count_submit_unread' => $form_object->count_submit_unread,
				];

				if(!$time_from_utc && !$time_to_utc) {

					// No date range specific so return totals
					$return_data['count_stat_view'] = $form_object->count_stat_view;
					$return_data['count_stat_save'] = $form_object->count_stat_save;
					$return_data['count_stat_submit'] = $form_object->count_stat_submit;

				} else {

					// Date range specified so get stats between dates
					$ws_form_form_stat = new WS_Form_Form_Stat();
					$ws_form_form_stat->form_id = $form_id;

					$form_stats = $ws_form_form_stat->report_form_statistics_get_data_process(

						$time_from_utc,
						$time_to_utc
					);

					$return_data['count_stat_view'] = $form_stats['count_view_total'];
					$return_data['count_stat_save'] = $form_stats['count_save_total'];
					$return_data['count_stat_submit'] = $form_stats['count_submit_total'];
				}

				// Return data
				return $return_data;

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving form statistics: %s', 'ws-form'));
			}
		}

		// Form - Delete
		public function form_delete($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-delete');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get permanent (wsf_ability_form_delete_permanent filter hook must be true)
				$permanent = self::input_get('permanent');

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				if($permanent && !apply_filters('wsf_ability_form_delete_permanent', false)) {

					throw new Exception(esc_html__('Permanent form deletion is not enabled.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Trash or permanently delete the form
				$ws_form_form->db_delete($permanent);

				// Return data
				return [

					'id' => $form_id,
					'permanent' => $permanent,
					'message' => esc_html($permanent ? __('Form permanently deleted', 'ws-form') : __('Form trashed', 'ws-form')),
					'url' => $permanent ? WS_Form_Common::get_admin_url('ws-form') : WS_Form_Common::get_admin_url('ws-form', false, '&ws-form-status=trash')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting form: %s', 'ws-form'));
			}
		}

		// Form - Restore
		public function form_restore($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-restore');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Restore the form
				$ws_form_form->db_restore();

				// Return data
				return [

					'id' => $form_id,
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error restoring form: %s', 'ws-form'));
			}
		}

		// Field - Add
		public function field_add($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'field-add');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get section ID
				$section_id = self::input_get('section_id');

				// Get field label
				$field_label = self::input_get('label');

				// Get type
				$field_type = self::input_get('type');

				// Get meta
				$field_meta = self::input_get('meta');

				// Get next sibling ID
				$next_sibling_id = self::input_get('field_id_before');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Add field
				$ws_form_field = $ws_form_form_ai->field_add(

					$section_id,
					$field_label,
					$field_type,
					$field_meta,
					$next_sibling_id
				);

				// Return data
				return [

					'id' => $form_id,
					'field_id' => $ws_form_field->id,
					'field_label' => $ws_form_field->label,
					'json' => $ws_form_form_ai->form_get_json(),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error adding field: %s', 'ws-form'));
			}
		}

		// Field - Delete
		public function field_delete($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'field-delete');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get field ID
				$field_id = self::input_get('field_id');

				// Check field ID
				if($field_id === 0) {

					throw new Exception(esc_html__('Invalid field ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Field
				$ws_form_field = new WS_Form_Field();

				// Set form ID
				$ws_form_field->form_id = $form_id;

				// Set field ID
				$ws_form_field->id = $field_id;

				// Delete field
				$ws_form_field->db_delete();

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Return data
				return [

					'id' => $form_id,
					'field_id' => $field_id,
					'message' => __('Field permanently deleted', 'ws-form'),
					'json' => $ws_form_form_ai->form_get_json(),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting field: %s', 'ws-form'));
			}
		}

		// Init
		public function init($input, $ability_name) {

			// Parse input
			self::input_parse($input, $ability_name);
		}

		// Permission callback
		public function permission_callback($caps) {

			if ( empty( $caps ) ) {
				return false;
			}

			$user = wp_get_current_user();

			if ( ! $user || 0 === $user->ID ) {

				return false;
			}

			if( is_string($caps) ) {

				return $user->has_cap( $caps );
			}

			if( is_array($caps) ) {

				foreach ( $caps as $cap ) {

					if ( ! $user->has_cap( $cap ) ) {

						return false;
					}
				}

				return true;
			}

			return false;
		}

		// Input parse
		public function input_parse($input, $ability_name) {

			// Reset input
			$this->input = array();

			// Check if input is WP_REST_Request
			if(is_a($input, 'WP_REST_Request')) {

				$input = $input->get_json_params();
			}

			// Check input are an array
			if(!is_array($input)) { $input = array(); }

			// Ensure all input match input schema

			// Get ability input schema
			$input_schema = WS_Form_Config_Ability::get_ability_input_schema($ability_name);

			// Check input schema is valid
			if(!is_array($input_schema)) {

				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('Input schema for ability %s not found.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Check if input schema is empty
			if(!is_array($input_schema) || empty($input_schema)) {

				return array();
			}

			// Check type is object
			if(
				!isset($input_schema['type']) ||
				($input_schema['type'] != 'object')
			) {
				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('Invalid input schema type for ability %s. Expects object.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Check properties exist
			if(
				!isset($input_schema['properties']) ||
				!is_array($input_schema['properties'])
			) {
				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('No input schema properties for ability %s.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Process properties
			foreach($input_schema['properties'] as $property_name => $property) {

				// Check property name (Required)
				if(empty($property_name)) {

					throw new Exception(

						sprintf(

							/* translators: %s: Ability name */
							esc_html__('Invalid input schema property name for ability %s.', 'ws-form'),
							esc_html($ability_name)
						)
					);
				}

				// Get property type
				$type = isset($property['type']) ? strtolower($property['type']) : 'string';

				// Check property type (Required)
				if(
					!in_array($type, array(

						// MCP
						'number',
						'boolean',
						'string',
						'array',
						'object',
						'null',

						// WordPress abilities API
						'integer'
					))
				) {
					throw new Exception(

						sprintf(

							/* translators: %1$s: Ability name, %2$s: Property name */
							esc_html__('Invalid input schema property type for ability %1$s (Property: %2$s).', 'ws-form'),
							esc_html($ability_name),
							esc_html($property_name)
						)
					);
				}

				// Get input value
				$input_value = isset($input[$property_name]) ? $input[$property_name] : '';

				// Get required
				$required = isset($property['required']) ? WS_Form_Common::is_true($property['required']) : null;

				// Check required
				if(
					empty($input_value) &&
					$required
				) {
					throw new Exception(

						sprintf(

							/* translators: %1$s: Ability ID, %2$s: Property name */
							esc_html__('Required input schema property for ability %1$s missing (Property: %2$s).', 'ws-form'),
							esc_html($ability_name),
							esc_html($property_name)
						)
					);
				}

				// Process according to type
				// Attempt to convert to correct type in case AI provides a different type
				switch($type) {

					case 'number' :
					case 'integer' :

						$input_value = intval($input_value);
						break;

					case 'boolean' :

						$input_value = WS_Form_Common::is_true($input_value);
						break;

					case 'string' :

						$input_value = sanitize_text_field($input_value);
						break;

					case 'array' :

						$input_value = WS_Form_Common::to_array($input_value);
						break;

					case 'object' :

						$input_value = WS_Form_Common::to_object($input_value);
						break;

					case 'null' :

						$input_value = null;
						break;
				}

				// If no value passed and string then set to default value
				$default_value = isset($property['default']) ? $property['default'] : null;
				if(
					($input_value === '') &&
					!is_null($default_value)
				) {
					$input_value = $default_value;
				}

				// Enumeration
				if(isset($property['enum'])) {

					if(!is_array($property['enum'])) {

						throw new Exception(

							sprintf(

								/* translators: %1$s: Property name, %2$s: Ability ID */
								esc_html__('Input schema property %1$s has invalid enum for ability %2$s. Expected array.', 'ws-form'),
								esc_html($property_name),
								esc_html($ability_name)
							)
						);
					}

					if(!in_array($input_value, $property['enum'], true)) {

						throw new Exception(

							sprintf(

								/* translators: %1$s: Ability name, %2$s: Property name */
								esc_html__('Invalid enumerated input value for ability %1$s (Property: %2$s).', 'ws-form'),
								esc_html($ability_name),
								esc_html($property_name)
							)
						);
					}
				}

				// Sanitize and set input
				$this->input[$property_name] = $input_value;
			}

			return $this->input;
		}

		// Get input
		public function input_get($property_name) {

			// Check input have been parsed
			if($this->input === false) {

				throw new Exception(esc_html__('Inputs not parsed.', 'ws-form'));				
			}

			if(!isset($this->input[$property_name])) {

				throw new Exception(

					sprintf(

						/* translators: %s: Property name */
						esc_html__('Property %s does not exist in input schema.', 'ws-form'),
						esc_html($property_name)
					)
				);				
			}

			return $this->input[$property_name];
		}

		// Validate date
		public function date_validate($date, $to = true) {

			// Allow blank value
			if ($date === '' || $date === false) {
				return '';
			}

			// Try to parse the date
			$timestamp = strtotime($date);

			if ($timestamp === false) {

				return false;
			}

			// Normalize date
			$date_normalized = gmdate('Y-m-d', $timestamp);

			// Ensure the original string matches the normalized format exactly
			if ($date_normalized !== $date) {
				
				return false;
			}

			// Change to YYYY-MM-DD HH:MM:SS format
			$formatted = sprintf(

				'%s %s',
				$date_normalized,
				($to ? '11:59:59' : '00:00:00')
			);

			return $formatted;
		}

		// Error handling
		public function error($e, $message) {

			return [

				'error' => array(

					'code' => 'ws_form_error',
					'message' => sprintf(

						esc_html($message),
						esc_html($e->getMessage())
					),
					'data' => $this->input
				)
			];
		}
	}
