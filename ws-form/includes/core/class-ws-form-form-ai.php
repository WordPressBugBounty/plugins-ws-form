<?php

	class WS_Form_Form_AI extends WS_Form_Core {

		public $id;
		public $label;

		const CREATE_FROM_JSON_MAX_FIELDS = 100;

		public function __construct() {

			$this->id = 0;
			$this->label = __('New Form', 'ws-form');
		}

		// Get form as JSON
		public function form_get_json() {

			return self::form_get(true);
		}

		// Get form object
		public function form_get($as_json = false) {

			if(empty($this->id)) {

				throw new Exception(esc_html__('Form ID not set.', 'ws-form'));
			}

			// Read form
			$ws_form_form = new WS_Form_Form();

			// Set form ID
			$ws_form_form->id = $this->id;

			// Return for object
			$form_object = $ws_form_form->db_read(true, true);

			// Build form data as array for return
			$form = array(

				'id' => (int) $form_object->id,
				'label' => $form_object->label,
				'groups' => array()
			);

			// Process groups
			$form = self::form_get_groups($form, $form_object->groups);

			if($as_json) {

				// Return as JSON
				return wp_json_encode($form);

			} else {

				// Return as object
				return json_decode(wp_json_encode($form));
			}
		}

		// Get form object - Groups
		public function form_get_groups($form, $groups) {

			foreach($groups as $group_index => $group) {

				$form['groups'][$group_index] = array(

					'id' => (int) $group->id,
					'label' => $group->label,
					'sections' => array()
				);

				// Process sections
				$form['groups'][$group_index] = self::form_get_sections($form['groups'][$group_index], $group->sections);
			}

			return $form;
		}

		// Get from object - Sections
		public function form_get_sections($group, $sections) {

			foreach($sections as $section_index => $section) {

				$group['sections'][$section_index] = array(

					'id' => (int) $section->id,
					'label' => $section->label,
					'fields' => array()
				);

				// Process sections
				$group['sections'][$section_index] = self::form_get_fields($group['sections'][$section_index], $section->fields);
			}

			return $group;
		}

		// Get from object - Fields
		public function form_get_fields($section, $fields) {

			$field_meta_keys_editable = array_keys(self::get_field_meta_keys_editable());

			$field_types_data_grid = self::get_field_types_data_grid();

			foreach($fields as $field_index => $field_object) {

				$section['fields'][$field_index] = array(

					'id' => (int) $field_object->id,
					'label' => $field_object->label,
					'type' => $field_object->type
				);

				// Build meta
				if(isset($field_object->meta)) {

					foreach($field_meta_keys_editable as $field_meta_key) {

						if(isset($field_object->meta->{$field_meta_key})) {

							if(!isset($section['fields'][$field_index]['meta'])) {

								$section['fields'][$field_index]['meta'] = array();
							}

							$section['fields'][$field_index]['meta'][$field_meta_key] = $field_object->meta->{$field_meta_key};
						}
					}

					// Get options
					if(in_array($field_object->type, $field_types_data_grid)) {

						$ws_form_data_grid = new WS_Form_Data_Grid($field_object);

						$options = $ws_form_data_grid->get_data_grid_options($field_object);

						if(is_array($options)) {

							$section['fields'][$field_index]['meta']['options'] = $options;
						}
					}
				}
			}

			return $section;
		}

		// Update form from JSON
		public function form_update_json($json_modified) {

			if(empty($this->id)) {

				throw new Exception(esc_html__('Form ID not set.', 'ws-form'));
			}

			// Get original JSON
			$json_original = self::form_get_json();

			// Check for updates
			if($json_original === $json_modified) {

				throw new Exception(esc_html__('No changes found in JSON.', 'ws-form'));
			}

			// Check that no structural changes have been made
			if(!self::form_json_compare_structure($json_original, $json_modified)) {

				throw new Exception(esc_html__('Invalid form changes detected. To insert or add a field, use the field-add tool.', 'ws-form'));
			}

			// Get new form object
			$form_object_new = json_decode($json_modified);

			if(
				is_null($form_object_new) ||
				!is_object($form_object_new) ||
				!isset($form_object_new->id) ||
				($form_object_new->id !== $this->id) ||
				!isset($form_object_new->label)
			) {
				throw new Exception(esc_html__('Invalid form JSON.', 'ws-form'));
			}

			// Process new form object
			foreach($form_object_new->groups as $group_index => $group) {

				// Check group
				if(
					!isset($group->label) ||
					!is_string($group->label) ||
					!isset($group->sections) ||
					!is_array($group->sections) ||
					!isset($group->sections[0]) ||
					!is_object($group->sections[0])
				) {
					throw new Exception(esc_html__('Invalid group data. Please try again.', 'ws-form'));
				}

				foreach($group->sections as $section_index => $section) {

					// Check section
					if(
						!isset($section->label) ||
						!is_string($section->label) ||
						!isset($section->fields) ||
						!is_array($section->fields) ||
						!isset($section->fields[0]) ||
						!is_object($section->fields[0])
					) {
						throw new Exception(esc_html__('Invalid section data. Please try again.', 'ws-form'));
					}

					foreach($section->fields as $field_index => $field_object_new) {

						// Check field
						if(
							!isset($field_object_new->id) ||
							!is_numeric($field_object_new->id) ||
							!isset($field_object_new->label) ||
							!is_string($field_object_new->label) ||
							!isset($field_object_new->type) ||
							!is_string($field_object_new->type)
						) {
							throw new Exception(esc_html__('Invalid field data. Please try again.', 'ws-form'));
						}

						// Check if meta options are specified
						if(
							isset($field_object_new->meta) &&
							isset($field_object_new->meta->options) &&
							is_array($field_object_new->meta->options)
						) {
							// Get options
							$options = WS_Form_Common::get_object_meta_value($field_object_new, 'options');

							// Set data grid from options
							$ws_form_data_grid = new WS_Form_Data_Grid($field_object_new);
							$ws_form_data_grid->update_data_grid_from_options($options);

							// Remove options key
							if(isset($field_object_new->meta->options)) {

								unset($field_object_new->meta->options);
							}
						}
					}
				}
			}

			// Read form
			$ws_form_form = new WS_Form_Form();

			// Set form ID
			$ws_form_form->id = $this->id;

			// Put form as object
			$ws_form_form->db_update_from_object($form_object_new, true, false, false);

			return self::form_get_json();
		}

		// Create form from JSON
		public function form_create_json($json) {

			// Attempt to decode output
			$json_decoded = json_decode($json);

			// Check form
			if(
				!is_object($json_decoded) ||
				!isset($json_decoded->label) ||
				!isset($json_decoded->groups) ||
				!is_array($json_decoded->groups) ||
				!isset($json_decoded->groups[0]) ||
				!is_object($json_decoded->groups[0])
			) {
				throw new Exception(esc_html__('Invalid form data. Please try again.', 'ws-form'));
			}

			// Create list
			$list = array(

				'label' => sanitize_text_field($json_decoded->label)
			);

			// Check count of fields
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$field_count_max = apply_filters('wsf_create_from_json_max_fields', self::CREATE_FROM_JSON_MAX_FIELDS);

			// Create list fields
			$list_fields = array();
			$list_fields_meta_data = array(

				'group_meta_data' => array(),
				'section_meta_data' => array(),
			);
			$sort_index = 0;
			$field_count = 0;

			foreach($json_decoded->groups as $group_index => $group) {

				// Check group
				if(
					!isset($group->label) ||
					!is_string($group->label) ||
					!isset($group->sections) ||
					!is_array($group->sections) ||
					!isset($group->sections[0]) ||
					!is_object($group->sections[0])
				) {
					throw new Exception(esc_html__('Invalid group data. Please try again.', 'ws-form'));
				}

				$list_fields_meta_data['group_meta_data']['group_' . $group_index]['label'] = sanitize_text_field($group->label);

				foreach($group->sections as $section_index => $section) {

					// Check section
					if(
						!isset($section->label) ||
						!is_string($section->label) ||
						!isset($section->fields) ||
						!is_array($section->fields) ||
						!isset($section->fields[0]) ||
						!is_object($section->fields[0])
					) {
						throw new Exception(esc_html__('Invalid section data. Please try again.', 'ws-form'));
					}

					if(!isset($list_fields_meta_data['section_meta_data']['group_' . $group_index])) {

						$list_fields_meta_data['section_meta_data']['group_' . $group_index] = array();
					}

					$list_fields_meta_data['section_meta_data']['group_' . $group_index]['section_' . $section_index] = array(

						'label' => sanitize_text_field($section->label)
					);

					foreach($section->fields as $field_index => $field) {

						// Check field
						if(
							!isset($field->label) ||
							!is_string($field->label) ||
							!isset($field->type) ||
							!is_string($field->type)
						) {
							throw new Exception(esc_html__('Invalid field data. Please try again.', 'ws-form'));
						}

						// Label
						$field_label = sanitize_text_field(

							WS_Form_Common::get_object_property($field, 'label', '')
						);

						// Type
						$field_type = sanitize_text_field(

							WS_Form_Common::get_object_property($field, 'type', 'text')
						);

						// Check field type
						if(!in_array($field_type, self::get_field_type_ids())) {

							continue;
						}

						// ID
						$field_id = isset($field->id) ? absint($field->id) : false;
						if(!$field_id) { continue; }

						// Required
						$field_required = WS_Form_Common::is_true(

							WS_Form_Common::get_object_meta_value($field, 'required', '')
						);

						// Default value
						$field_default_value = sanitize_text_field(

							WS_Form_Common::get_object_meta_value($field, 'default_value', '')
						);

						// Placeholder
						$field_placeholder = sanitize_text_field(

							WS_Form_Common::get_object_meta_value($field, 'placeholder', '')
						);

						// Help
						$field_help = sanitize_text_field(

							WS_Form_Common::get_object_meta_value($field, 'help', '')
						);

						// Field width factor
						$field_width_factor = floatval(

							WS_Form_Common::get_object_meta_value($field, 'width_factor', 1)
						);

						if(
							($field_width_factor <= 0) ||
							($field_width_factor > 1)
						) {
							$field_width_factor = 1;
						}

						$list_fields[] = array(

							'id' => 			$field_id,
							'label' => 			$field_label, 
							'label_field' => 	$field_label, 
							'type' => 			$field_type, 
							'required' => 		$field_required, 
							'default_value' => 	$field_default_value, 
							'pattern' => 		'', 
							'placeholder' => 	$field_placeholder, 
							'input_mask' =>		false,
							'help' => 			$field_help, 
							'visible' =>		true,
							'meta' =>			self::get_meta($field),
							'width_factor' =>	$field_width_factor,
							'group_index' =>	$group_index,
							'section_index' =>	$section_index,
							'sort_index' => 	$field_index
						);

						$field_count++;

						if($field_count > $field_count_max) {

							throw new Exception(esc_html__('Too many fields returned. Please try again.', 'ws-form'));
						}
					}
				}
			}

			// Create form fields
			$form_fields = array(

				'opt_in_field' => array(

					'type'	=>	'checkbox',
					'label'	=>	__('GDPR', 'ws-form'),
					'meta'	=>	array(

						'data_grid_checkbox' => WS_Form_Common::build_data_grid_meta('data_grid_checkbox', false, false, array(

							array(

								'id'		=> 1,
								'data'		=> array(__('I consent to #blog_name storing my submitted information so they can respond to my inquiry', 'ws-form'))
							)
						))
					)
				),

				'submit' => array(

					'type'			=>	'submit'
				)
			);

			// Create form actions
			$form_actions = array(

				'email',

				'message',

				'database'
			);

			// Create form conditionals
			$form_conditionals = false;

			// Create form meta
			$form_meta = false;

			$ws_form_form = new WS_Form_Form();

			// Create new form
			if($this->id === 0) {

				$this->id = $ws_form_form->db_create(false);
			}

			// Check form created
			if(empty($this->id)) {

				throw new Exception(esc_html__('Error creating form.', 'ws-form'));
			}

			// Modify form so it matches action list
			WS_Form_Action::update_form($this->id, false, false, false, $list, $list_fields, $list_fields_meta_data, $form_fields, $form_actions, $form_conditionals, $form_meta);

			return $this->id;
		}

		// Field add
		public function field_add($section_id, $field_label, $field_type, $field_meta, $next_sibling_id) {

			// Check section ID
			if($section_id === 0) {

				throw new Exception(esc_html__('Invalid section ID.', 'ws-form'));
			}

			// Check field type
			if(!in_array($field_type, self::get_field_type_ids())) {

				throw new Exception(esc_html__('Invalid field type.', 'ws-form'));
			}

			// Get usable meta keys
			$meta_keys_enabled = self::get_field_meta_keys();

			// Build sanitized field meta
			$field_meta_sanitized = array();

			foreach($field_meta as $meta_key => $meta_value) {

				// Check meta key is enabled
				if(!isset($meta_keys_enabled[$meta_key])) { continue; }

				// Get meta key config
				$meta_key_config = $meta_keys_enabled[$meta_key];

				// Get meta key type
				$meta_key_type = isset($meta_key_config['type']) ? $meta_key_config['type'] : 'string';

				// Process by type
				switch($meta_key_type) {

					case 'boolean' :

						$meta_value = WS_Form_Common::is_true($meta_value) ? 'on' : '';
						break;

					case 'integer' :

						$meta_value = intval($meta_value);
						break;

					case 'float' :

						$meta_value = floatval($meta_value);
						break;

					case 'array' :

						$meta_value = WS_Form_Common::to_array($meta_value);
						break;

					case 'object' :

						$meta_value = WS_Form_Common::to_object($meta_value);
						break;

					default :

						$meta_value = sanitize_text_field($meta_value);
				}

				$field_meta_sanitized[$meta_key] = $meta_value;
			}

			// Initiate instance of Field class
			$ws_form_field = new WS_Form_Field();
			$ws_form_field->form_id = $this->id;
			$ws_form_field->section_id = $section_id;
			$ws_form_field->type = $field_type;
			$ws_form_field->label = $field_label;
			$ws_form_field->meta = (object) $field_meta_sanitized;

			// Check for options
			if(isset($ws_form_field->meta->options)) {

				$ws_form_data_grid = new WS_Form_Data_Grid($ws_form_field);
				$ws_form_data_grid->set_data_grid_from_options($ws_form_field->meta->options);
				unset($ws_form_field->meta->options);
			}

			// Create field
			$ws_form_field->db_create($next_sibling_id);

			return $ws_form_field;
		}

		// Convert action field to WS Form meta key
		public function get_meta($field) {

			$type = WS_Form_Common::get_object_property($field, 'type');

			// Get WS Form meta configurations for action field types
			switch($type) {

				// text_editor
				case 'note' :
				case 'texteditor' :

					$text_editor = sanitize_text_field(WS_Form_Common::get_object_meta_value($field, 'text_editor'));

					if(!empty($text_editor)) {

						return(array('text_editor' => $text_editor));

					} else {

						return false;
					}

				// Build data grids
				case 'select' :
				case 'checkbox' :
				case 'radio' :

					// Get options
					$options = WS_Form_Common::get_object_meta_value($field, 'options', false);
					if($options !== false) {

						// Build data grid
						$ws_form_data_grid = new WS_Form_Data_Grid($field);
						$ws_form_data_grid->set_data_grid_from_options($options);
						unset($field->meta->options);
						return $field->meta;

					} else {

						return false;
					}

					break;

				default :

					return false;
			}
		}

		// Field types that can be used to build a form from JSON
		public function get_field_types() {

			return array(

				'checkbox' => array(

					'description' => 'One or more HTML input checkbox fields.',
					'data_grid' => true
				),

				'email' => array(

					'description' => 'An HTML email input field.'
				),

				'note' => array(

					'description' => 'Used for adding notes to the form that are only seen in the WS Form layout editor by the administrator. The note string is stored in the text_editor meta property of the field.'
				),

				'number' => array(

					'description' => 'An HTML number input field.'
				),

				'radio' => array(

					'description' => 'One or more HTML input radio fields.',
					'data_grid' => true
				),

				'select' => array(

					'description' => 'An HTML select field with one or more options.',
					'data_grid' => true
				),

				'tel' => array(

					'description' => 'An HTML tel input field. Used for phone numbers.'
				),

				'text' => array(

					'description' => 'An HTML text input field.'
				),

				'textarea' => array(

					'description' => 'One or more HTML input checkbox fields.'
				),

				'texteditor' => array(

					'description' => 'Outputs text to the form. Use this for showing the user interacting with the form useful instructions. The texteditor string is stored in the text_editor meta property of the field.'
				),

				'url' => array(

					'description' => 'An HTML url input field. Used for web addresses.'
				),

			);
		}

		// Get field types that have data grid
		public function get_field_types_data_grid() {

			$field_types_data_grid = array();

			foreach(self::get_field_types() as $field_type => $field_type_config) {

				if(isset($field_type_config['data_grid']) && $field_type_config['data_grid']) {

					$field_types_data_grid[] = $field_type;
				}
			}

			return $field_types_data_grid;
		}


		// Get only field type ids (keys)
		public function get_field_type_ids() {

			return array_keys(self::get_field_types());
		}

		// Get field types in a prompt format
		public function get_field_types_prompt() {

			$field_types = self::get_field_types();

			$prompt_array = array();

			foreach($field_types as $id => $field_type) {

				$prompt_array[] = sprintf(

					'%s: %s',
					$id,
					$field_type['description']
				);
			}

			return implode("\n", $prompt_array);
		}

		// Get field properties
		public function get_field_properties() {

			return array(

				'id' => array(

					'type' => 'integer',
					'description' => 'A unique ID for the field, starting with 1 and increments by 1 for each field added.',
					'editable' => false
				),

				'label' => array(

					'type' => 'string',
					'description' => 'The label of the field. This key is mandatory.',
					'editable' => true
				),

				'type' => array(

					'type' => 'string',
					'description' => 'The type of the field. This key is mandatory. Available types are: text, textarea, email, hidden, note, number, price, tel, url, datetime, select, checkbox, radio, file, texteditor, rating, color',
					'editable' => false
				)
			);
		}

		// Get field properties in a prompt format
		public function get_field_properties_prompt() {

			$field_properties = self::get_field_properties();

			$prompt_array = array();

			foreach($field_properties as $id => $field_property) {

				$prompt_array[] = sprintf(

					'	%s (%s): %s',
					$id,
					$field_property['type'],
					$field_property['description']
				);
			}

			return implode("\n", $prompt_array);
		}

		// Get only editable meta properties
		public function get_field_meta_keys_editable() {

			// Get all field meta properties
			$meta_keys = $this->get_field_meta_keys();

			// Return only editable ones
			$meta_keys_editable = array();

			foreach ($meta_keys as $key => $def) {

				if (!empty($def['editable'])) {

					$meta_keys_editable[$key] = $def;
				}
			}

			return $meta_keys_editable;
		}

		// Get field meta keys
		public function get_field_meta_keys() {

			return array(

				'required' => array(

					'type' => 'string',
					'description' => 'Whether or not the field is required. Set to \'on\' if required, blank string if not required.',
					'editable' => true
				),

				'placeholder' => array(

					'type' => 'string',
					'description' => 'An optional placeholder for the field. Omit if there is no placeholder.',
					'editable' => true
				),

				'help' => array(

					'type' => 'string',
					'description' => 'Optional help text shown underneath each field. Omit if there is no help text.',
					'editable' => true
				),

				'text_editor' => array(

					'type' => 'string',
					'description' => 'Enter text to show for a texteditor or note field type.',
					'editable' => true
				),

				'default_value' => array(

					'type' => 'string',
					'description' => 'Only use this if it is appropriate to add a default value to a field.',
					'editable' => true
				),

				'step' => array(

					'type' => 'float',
					'description' => 'Used for number fields only and sets the step attribute. If blank it defaults to 1. Example value: 0.01 which allows numbers with 2 decimal places. Same as the HTML spec for number fields.',
					'editable' => true
				),

				'invalid_feedback' => array(

					'type' => 'string',
					'description' => 'Shown if the field is not valid when the form is validated. If blank then \'This field is required\' will be shown by default.',
					'editable' => true
				),

				'options' => array(

					'type' => 'array',
					'description' => 'Only used for Select, Checkbox and Radio field types to specify the options. Each element in the array represents either select option, or a single checkbox or radio. An example of this meta property is: [{\'value\':\'option_1\',\'label\':\'Option 1\'},{\'value\':\'option_2\',\'label\':\'Option 2\'}] where \'value\' is the value stored when the form is submitted and \'label\' is the label shown to the user completing the form. Omit if not a Select, Checkbox or Checkbox field.',
					'editable' => true
				),

				'width_factor' => array(

					'type' => 'float',
					'description' => 'How wide the field should be on the form. Valid values are 0.5 for 1/2 width. Omit if full width.',
					'editable' => false,
					'create_only' => true
				),
			);
		}

		// Get field meta keys in a prompt format
		public function get_field_meta_keys_prompt($include_create_only = false) {

			$field_meta_keys = self::get_field_meta_keys();

			$prompt_array = array();

			foreach($field_meta_keys as $id => $field_meta_key) {

				$create_only = isset($field_meta_key['create_only']) ? $field_meta_key['create_only'] : false;
				if(!$include_create_only && $create_only) { continue; }

				$prompt_array[] = sprintf(

					'	%s (%s): %s',
					$id,
					$field_meta_key['type'],
					$field_meta_key['description']
				);
			}

			return implode("\n", $prompt_array);
		}

		// Compare form JSON structures to ensure nothing has changed that is locked down
		public function form_json_compare_structure($json_original, $json_modified) {

			// Decode both JSON strings
			$form_original = json_decode($json_original, true);
			$form_modified = json_decode($json_modified, true);

			if (!is_array($form_original) || !is_array($form_modified)) {
				return false;
			}

			// Get field and meta key definitions
			$field_properties = $this->get_field_properties();
			$field_meta_keys  = $this->get_field_meta_keys();

			// Derive allowed keys
			$allowed_field_keys = array_keys($field_properties);
			$allowed_meta_keys  = array_keys($field_meta_keys);

			// Editable key lists (so we can ignore them in the diff)
			$editable_field_keys = array();
			foreach ($field_properties as $key => $def) {
				if (!empty($def['editable'])) {
					$editable_field_keys[] = $key;
				}
			}

			$editable_meta_keys = array();
			foreach ($field_meta_keys as $key => $def) {
				if (!empty($def['editable'])) {
					$editable_meta_keys[] = $key;
				}
			}

			// Helper function to normalize structure
			$get_structure = function($form) use ($allowed_field_keys, $allowed_meta_keys, $editable_field_keys, $editable_meta_keys) {

				if (empty($form['groups']) || !is_array($form['groups'])) {
					return array();
				}

				$structure = array();

				foreach ($form['groups'] as $group) {

					$group_id = $group['id'] ?? null;
					if (!$group_id) { continue; }

					$group_data = array(
						'id' => $group_id,
						'sections' => array()
					);

					if (!empty($group['sections']) && is_array($group['sections'])) {
						foreach ($group['sections'] as $section) {

							$section_id = $section['id'] ?? null;
							if (!$section_id) { continue; }

							$section_data = array(
								'id' => $section_id,
								'fields' => array()
							);

							if (!empty($section['fields']) && is_array($section['fields'])) {
								foreach ($section['fields'] as $field) {

									$field_id = $field['id'] ?? null;
									if (!$field_id) { continue; }

									$field_type = $field['type'] ?? '';

									// Filter and sanitize meta
									$meta_filtered = array();
									if (!empty($field['meta']) && is_array($field['meta'])) {
										foreach ($field['meta'] as $meta_key => $meta_value) {

											// Skip unknown meta keys entirely
											if (!in_array($meta_key, $allowed_meta_keys, true)) {
												continue;
											}

											// Only include non-editable meta keys
											if (!in_array($meta_key, $editable_meta_keys, true)) {
												$meta_filtered[$meta_key] = $meta_value;
											}
										}
									}

									// Build field signature excluding editable and unknown keys
									$field_signature = array(
										'id'   => $field_id,
										'type' => $field_type,
										'meta' => $meta_filtered
									);

									foreach ($field as $key => $value) {

										// Skip meta itself (handled above)
										if ($key === 'meta') {
											continue;
										}

										// Skip unknown or editable field properties
										if (
											!in_array($key, $allowed_field_keys, true) ||
											in_array($key, $editable_field_keys, true)
										) {
											continue;
										}

										$field_signature[$key] = $value;
									}

									$section_data['fields'][] = $field_signature;
								}
							}

							$group_data['sections'][] = $section_data;
						}
					}

					$structure[] = $group_data;
				}

				return $structure;
			};

			// Extract and normalize both forms
			$structure_original = $get_structure($form_original);
			$structure_modified = $get_structure($form_modified);

			// Compare structures strictly
			return $structure_original === $structure_modified;
		}


		// Get AI prompt for creating a new form by JSON
		public function get_form_create_json_prompt() {

			return "When creating a form, the following rules must be followed when providing the JSON.

= Example JSON =
An example format of the JSON object to create is:

" . self::get_form_example_prompt() . "

DO NOT use the same groups, sections and fields in this example.

= General format =
form->groups[0]->section[0]->fields

All forms specified should have:

- 1 group (Tab)
- 1 section
- 1 or more fields

= Allowed Field Types =
" . self::get_field_types_prompt() . "

= Field Properties =
" . self::get_field_properties_full_prompt(true) . "


= JSON rules =
The form JSON must adhere to these strict rules:

1. Ensure only the allowed field types are used.
2. Do not format the JSON with new lines, indentation or tabulation. Minify the JSON.
3. Do not include an opt-in or submit button in the field array.
4. Do not add 'Full Name' or 'Your Name' fields. Always have separate first and last name fields.
5. The only allowed width_factor value is 0.5.
6. If there are two fields that are related to one another (e.g. from and to) set the width_factor to 0.5. Only do this if you can place two fields side-by-side.
7. When specifying options for select, checkbox or radio field types, provide a comprehensive and full list of options rather than just a sample.
8. Do not wrap the JSON string in anything else, return only the JSON string.

Very strict rule: Only include the minified JSON object in the output.";
		}

		// Get AI prompt for building JSON suitable for updating a form by JSON
		public function get_form_update_json_prompt() {

			return "When updating a form, the following rules must be followed when providing updated JSON.

= Field Types =
Only these field types can be updated.
" . self::get_field_types_prompt() . "
submit: The form submit button. Submit fields only have the help meta property.

When editing a form, other field types might be present. You should ensure you include these in the updated JSON.

= Field Properties =
Only these field properties can be updated. Do not include any properties not listed below in the updated JSON.
" . self::get_field_properties_full_prompt(false) . "


= JSON rules =
The form JSON must adhere to these strict rules:

1. Do not change the structure of the groups, sections or fields. ALL ORIGINAL FIELDS RETRIEVED FROM form-get-json MUST BE INCLUDED.
2. Do not format the JSON with new lines, indentation or tabulation. Minify the JSON.
3. Do not wrap the JSON string in anything else, return only the JSON string.

Very strict rule: Only include the minified JSON object.
";
		}

		// Get AI prompt for the field add type property
		public function get_field_add_type_prompt() {

			return "The following field types can be chosen from:

" . self::get_field_types_prompt() . "

Use only the field type, e.g. text, in this input property.
";
		}

		// Get AI prompt for the field add meta property
		public function get_field_add_meta_prompt() {

			return "The following field meta can be specified:

" . self::get_field_meta_keys_prompt() . "
";
		}

		// Get AI prompt for the field properties
		public function get_field_properties_full_prompt($include_create_only = false) {

			return "
Each field has the following properties:

" . self::get_field_properties_prompt() . "
meta (object) = {
" . self::get_field_meta_keys_prompt($include_create_only) . "
}
";
		}

		// Get the AI prompt for variables
		public function get_variables_prompt() {

			return "
#field(id) can be used in the default_value meta property to return the value of a field, where id is the number ID of the field you want to reference.

If #field(id) is used within #text(), for example #text(#field(123)), it will dynamically update that value. So in this example, if field ID 123 is changed by the user, the field containing #text(#field(123)) would dynamically update with the value of field ID 123.

If #field(id) is used within #calc(), for example #calc(#field(123)), it will ALWAYS return a numeric value, NOT a string. Instead of checking against strings like 'wood' or 'aluminum', #field() should return 0, 1, 2, 3, etc., and the conditions should check against those numbers. The value returned by #field() could also just be the literal number required in the value column.
";
		}

		// Get the AI prompt for calculations
		public function get_calc_prompt() {

			return "
If the form calls for a calculation (e.g. for a Mortgage or Loan calculator form), use #calc() in the default_value field meta property (field->meta->default_value).

#calc can be used in the following field types:

- price
- number
- text
- hidden

Here are some examples that can be put in the default_value of a field:

Add the values of field ID 1 and 2 together:
	#calc(#field(1) + #field(2))

Subtract a values from another:
	#calc(#field(1) - #field(2))

Multiply two values:
	#calc(#field(1) * #field(2))

Divide two values:
	#calc(#field(1) / #field(2))

The #calc() variable gets assessed like a regular JavaScript mathematical expression. Ensure all parameters within #calc() are numeric.

There are other variables that can be used for mathematical functions. Here are some examples:

Absolute: #abs(input)
Ceiling: #ceil(input)
Cosine: #cos(input)
Euler's: #exp(input)
Exponentiation: #pow(base, exponent)
Floor: #floor(input)
Logarithmic: #log(input)
Minimum: #min(50,input)
Maximum: #max(50,input)
Negative: #negative(input)
Positive: #positive(input)
Round: #round(input)
Sine: #sin(input)
Square Root: #sqrt(input)
Tangent: #tan(input)

Here's are some examples of how #calc() might be used in the default_value:

#calc(#field(1) * ((#field(2) / 3.5) + #field(3)))
#calc(#field(1) * ((#field(2) / 100) / 12) / (1 - #pow(1 + (#field(2) / 100) / 12, -#field(3) * 12)))
";
		}

		// Get the AI prompt for calculation rules
		public function get_calc_rules_prompt() {

			return "
These strict rules must be adhered to if the form includes calculations:

1. #calc() can only be used in the field object property: field->meta->default_value.
2. #calc() can only be used in the field types: price, number, text, hidden
3. Open and closing brackets in #calc() must be correctly balanced. Do not miss closing brackets.
4. If an input or output relates to a price or currency amount, use field type 'price'.
5. If an input or output relates to a numeric value (not a price) with decimals, use field type: 'number' and set field->meta->step to 'any'.
6. #field() used in #calc() will always return a numeric value, never a string. Don't do (#field(194101) == 'triple' ? 40 : 0), instead set the value of the 194101 field to be 40 or 0.
7. There should always be one or more visible outputs, using a number, price or text field.
8. To avoid too many nested brackets in #calc(), break the calculation down using hidden fields.
9. Use hidden fields to break calculations into smaller manageable chunks to make #calc() easier to understand.
";
		}

		// Get form example - JSON
		public function get_form_example_prompt() {

			return wp_json_encode(self::get_form_example());
		}

		// Get form example
		public function get_form_example() {

			return array(

				'id' => 1,
				'label' => 'This is the name of the form',

				'groups' => array(

					array(

						'id' => 1,
						'label' => 'This is the name of a tab, e.g. Tab',

						'sections' => array(

							array(

								'id' => 1,
								'label' => 'This is the name of a section, e.g. Section',

								'fields' => array(

									array(

										'id' => 1,
										'label' => 'Instructions',
										'type' => 'texteditor',
										'meta' => array(
											'text_editor' => 'Example instructions for the form.'
										)
									),

									array(

										'id' => 2,
										'label' => 'First Name',
										'type' => 'text',
										'meta' => array(
											'required' => 'on',
											'width_factor' => 0.5
										)
									),

									array(

										'id' => 3,
										'label' => 'Last Name',
										'type' => 'text',
										'meta' => array(
											'required' => 'on',
											'width_factor' => 0.5
										)
									),

									array(

										'id' => 4,
										'label' => 'Email',
										'type' => 'email',
										'meta' => array(
											'required' => 'on'
										)
									),

									array(

										'id' => 5,
										'label' => 'Phone',
										'type' => 'phone',
										'meta' => array(
											'required' => ''
										)
									),

									array(

										'id' => 6,
										'label' => 'Inquiry',
										'type' => 'textarea',
										'meta' => array(
											'placeholder' => 'How can we help?',
											'help' => 'Example help text.'
										)
									),

									array(

										'id' => 7,
										'label' => 'Preferred contact method',
										'type' => 'radio',
										'meta' => array(
											'options' => array(

												array('value' => 'email', 'text' => 'Email'),
												array('value' => 'phone', 'text' => 'Phone'),
											)
										)
									)
								)
							)
						)
					)
				)
			);
		}
	}
