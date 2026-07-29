<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Form_AI extends WS_Form_Core {

		public $id;
		public $label;

		const CREATE_FROM_JSON_MAX_FIELDS = 100;
		const CREATE_FROM_JSON_MAX_ACTIONS = 10;
		const CREATE_FROM_JSON_MAX_CONDITIONALS = 25;

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


			// Update checksum
			$ws_form_form->db_checksum();

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

					$section_ai_id = isset($section->id) ? absint($section->id) : ($section_index + 1);
					if(!$section_ai_id) { $section_ai_id = $section_index + 1; }

					$section_meta = array(

						'label' => sanitize_text_field($section->label),
						'ai_id' => $section_ai_id
					);

					// Section label visibility (label_render)
					$label_render = null;

					if(isset($section->label_render)) {

						$label_render = $section->label_render;

					} else if(
						isset($section->meta) &&
						(is_object($section->meta) || is_array($section->meta))
					) {

						$section_meta_ai = (array) $section->meta;

						if(isset($section_meta_ai['label_render'])) {

							$label_render = $section_meta_ai['label_render'];
						}
					}

					if($label_render !== null) {

						$section_meta['label_render'] = WS_Form_Common::is_true($label_render) ? 'on' : '';
					}

					$list_fields_meta_data['section_meta_data']['group_' . $group_index]['section_' . $section_index] = $section_meta;

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
								'data'		=> array(__('I consent to #blog_name storing my submitted information so they can respond to my inquiry', 'ws-form')),
								'required'	=> 'on'
							)
						))
					)
				),

				'submit' => array(

					'type'			=>	'submit'
				)
			);

			// Field type lookup for conditional expansion (AI field id => type)
			$field_type_lookup = array();
			foreach($list_fields as $list_field) {

				$field_type_lookup[$list_field['id']] = $list_field['type'];
			}

			// Section id lookup for conditional expansion (AI section id => true)
			$section_id_lookup = array();
			foreach($list_fields_meta_data['section_meta_data'] as $group_sections) {

				if(!is_array($group_sections)) { continue; }

				foreach($group_sections as $section_meta) {

					if(
						is_array($section_meta) &&
						isset($section_meta['ai_id'])
					) {
						$section_id_lookup[absint($section_meta['ai_id'])] = true;
					}
				}
			}

			// Create form actions (defaults; PRO may replace from AI JSON)
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

			// Build sanitized field meta
			$field_meta_sanitized = self::field_meta_sanitize($field_meta);

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

			// Update checksum
			$ws_form_field->db_checksum();

			// Re-read for return
			$ws_form_field->db_read(true);

			return $ws_form_field;
		}

		// Field update (label and/or editable meta only — type cannot change)
		public function field_update($field_id, $field_label, $field_meta) {

			$field_id = absint($field_id);

			if($field_id === 0) {

				throw new Exception(esc_html__('Invalid field ID.', 'ws-form'));
			}

			$has_label = is_string($field_label) && ($field_label !== '');
			$field_meta_sanitized = self::field_meta_sanitize($field_meta, true);
			$has_meta = (count($field_meta_sanitized) > 0);

			if(!$has_label && !$has_meta) {

				throw new Exception(esc_html__('Provide a label and/or meta to update.', 'ws-form'));
			}

			// Confirm the field belongs to this form
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $this->id;
			$form_object = $ws_form_form->db_read(true, true);

			$fields = WS_Form_Common::get_fields_from_form($form_object, true);

			if(!isset($fields[$field_id])) {

				throw new Exception(esc_html__('Field not found in form.', 'ws-form'));
			}

			// Read field
			$ws_form_field = new WS_Form_Field();
			$ws_form_field->form_id = $this->id;
			$ws_form_field->id = $field_id;
			$field_object = $ws_form_field->db_read(true);

			if($field_object === false) {

				throw new Exception(esc_html__('Invalid field ID.', 'ws-form'));
			}

			// Update label
			if($has_label) {

				$field_object->label = sanitize_text_field($field_label);
			}

			// Update editable meta (partial merge)
			if($has_meta) {

				if(!isset($field_object->meta) || !is_object($field_object->meta)) {

					$field_object->meta = new stdClass();
				}

				foreach($field_meta_sanitized as $meta_key => $meta_value) {

					$field_object->meta->{$meta_key} = $meta_value;
				}

				// Check for options
				if(isset($field_object->meta->options)) {

					$ws_form_data_grid = new WS_Form_Data_Grid($field_object);
					$ws_form_data_grid->update_data_grid_from_options($field_object->meta->options);
					unset($field_object->meta->options);
				}
			}

			// Save field
			$ws_form_field->db_update_from_object($field_object, false, false);

			// Update checksum
			$ws_form_field->db_checksum();

			// Re-read for return
			$ws_form_field->db_read(true);

			return $ws_form_field;
		}

		// Sanitize AI field meta (optionally only editable keys)
		public function field_meta_sanitize($field_meta, $editable_only = false) {

			$meta_keys_enabled = $editable_only ? self::get_field_meta_keys_editable() : self::get_field_meta_keys();

			if(is_object($field_meta)) {

				$field_meta = (array) $field_meta;
			}

			if(!is_array($field_meta)) {

				return array();
			}

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

			return $field_meta_sanitized;
		}

		// Read form object for AI structure tools
		public function form_read_object() {

			if(empty($this->id)) {

				throw new Exception(esc_html__('Form ID not set.', 'ws-form'));
			}

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $this->id;

			return $ws_form_form->db_read(true, true);
		}

		// Normalize optional ability object inputs (missing meta becomes {scalar:''} via to_object)
		public function ability_meta_to_array($meta) {

			if(is_object($meta)) {

				$meta = (array) $meta;
			}

			if(!is_array($meta)) {

				return array();
			}

			if(
				array_key_exists('scalar', $meta) &&
				(count($meta) === 1) &&
				(($meta['scalar'] === '') || is_null($meta['scalar']))
			) {
				return array();
			}

			return $meta;
		}

		// Format field for ability / MCP response
		public function field_format_for_ability($field) {

			$return = array(

				'id' => isset($field->id) ? (int) $field->id : 0,
				'label' => isset($field->label) ? (string) $field->label : '',
				'type' => isset($field->type) ? (string) $field->type : '',
				'section_id' => isset($field->section_id) ? (int) $field->section_id : 0,
				'meta' => array()
			);

			if(!isset($field->meta)) {

				return $return;
			}

			$meta_obj = is_object($field->meta) ? $field->meta : (object) $field->meta;

			foreach(array_keys(self::get_field_meta_keys_editable()) as $field_meta_key) {

				if(isset($meta_obj->{$field_meta_key})) {

					$return['meta'][$field_meta_key] = $meta_obj->{$field_meta_key};
				}
			}

			if(in_array($return['type'], self::get_field_types_data_grid(), true)) {

				$ws_form_data_grid = new WS_Form_Data_Grid($field);
				$options = $ws_form_data_grid->get_data_grid_options($field);

				if(is_array($options)) {

					$return['meta']['options'] = $options;
				}
			}

			return $return;
		}

		// Format section for ability / MCP response
		public function section_format_for_ability($section) {

			$meta = array();
			$section_meta = isset($section->meta) ? $section->meta : null;

			if(is_object($section_meta) && isset($section_meta->label_render)) {

				$meta['label_render'] = $section_meta->label_render;

			} elseif(is_array($section_meta) && isset($section_meta['label_render'])) {

				$meta['label_render'] = $section_meta['label_render'];
			}

			return array(

				'id' => isset($section->id) ? (int) $section->id : 0,
				'label' => isset($section->label) ? (string) $section->label : '',
				'group_id' => isset($section->group_id) ? (int) $section->group_id : 0,
				'meta' => $meta
			);
		}

		// Format tab for ability / MCP response
		public function tab_format_for_ability($group) {

			return array(

				'id' => isset($group->id) ? (int) $group->id : 0,
				'label' => isset($group->label) ? (string) $group->label : ''
			);
		}

		// List tabs (groups)
		public function tabs_list() {

			$form_object = self::form_read_object();
			$tabs = array();
			$index = 1;

			if(isset($form_object->groups) && is_array($form_object->groups)) {

				foreach($form_object->groups as $group) {

					if(!isset($group->id)) { continue; }

					$tabs[] = array(

						'id' => (int) $group->id,
						'label' => isset($group->label) ? (string) $group->label : '',
						'index' => $index
					);

					$index++;
				}
			}

			return $tabs;
		}

		// List sections
		public function sections_list() {

			$form_object = self::form_read_object();
			$sections = array();

			if(isset($form_object->groups) && is_array($form_object->groups)) {

				foreach($form_object->groups as $group) {

					if(!isset($group->id) || !isset($group->sections) || !is_array($group->sections)) { continue; }

					$group_id = (int) $group->id;
					$index = 1;

					foreach($group->sections as $section) {

						if(!isset($section->id)) { continue; }

						$sections[] = array(

							'id' => (int) $section->id,
							'label' => isset($section->label) ? (string) $section->label : '',
							'group_id' => $group_id,
							'index' => $index
						);

						$index++;
					}
				}
			}

			return $sections;
		}

		// List fields
		public function fields_list() {

			$form_object = self::form_read_object();
			$fields = array();

			if(isset($form_object->groups) && is_array($form_object->groups)) {

				foreach($form_object->groups as $group) {

					if(!isset($group->id) || !isset($group->sections) || !is_array($group->sections)) { continue; }

					$group_id = (int) $group->id;

					foreach($group->sections as $section) {

						if(!isset($section->id) || !isset($section->fields) || !is_array($section->fields)) { continue; }

						$section_id = (int) $section->id;

						foreach($section->fields as $field) {

							if(!isset($field->id)) { continue; }

							$fields[] = array(

								'id' => (int) $field->id,
								'label' => isset($field->label) ? (string) $field->label : '',
								'type' => isset($field->type) ? (string) $field->type : '',
								'section_id' => $section_id,
								'group_id' => $group_id
							);
						}
					}
				}
			}

			return $fields;
		}

		// Tab (group) add
		public function tab_add($label, $tab_id_before = 0) {

			$label = is_string($label) ? sanitize_text_field($label) : '';
			if($label === '') {

				$label = __('Tab', 'ws-form');
			}

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->label = $label;
			$ws_form_group->db_create(absint($tab_id_before), true);
			$ws_form_group->db_checksum();

			return $ws_form_group;
		}

		// Tab (group) update
		public function tab_update($tab_id, $label) {

			$tab_id = absint($tab_id);
			if($tab_id === 0) {

				throw new Exception(esc_html__('Invalid tab ID.', 'ws-form'));
			}

			$label = is_string($label) ? sanitize_text_field($label) : '';
			if($label === '') {

				throw new Exception(esc_html__('Provide a label to update.', 'ws-form'));
			}

			self::assert_tab_on_form($tab_id);

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->id = $tab_id;
			$group_object = $ws_form_group->db_read(true, false);
			$group_object->label = $label;
			$ws_form_group->db_update_from_object($group_object, false, false, false);
			$ws_form_group->db_checksum();
			$ws_form_group->db_read(true, false);

			return $ws_form_group;
		}

		// Tab (group) delete
		public function tab_delete($tab_id) {

			$tab_id = absint($tab_id);
			if($tab_id === 0) {

				throw new Exception(esc_html__('Invalid tab ID.', 'ws-form'));
			}

			$tabs = self::tabs_list();
			if(count($tabs) <= 1) {

				throw new Exception(esc_html__('Cannot delete the last tab on a form.', 'ws-form'));
			}

			self::assert_tab_on_form($tab_id);

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->id = $tab_id;
			$ws_form_group->db_delete(true);
			$ws_form_group->db_checksum();

			return true;
		}

		// Section add
		public function section_add($group_id, $label, $section_id_before = 0, $meta = array()) {

			$group_id = absint($group_id);
			if($group_id === 0) {

				throw new Exception(esc_html__('Invalid tab ID.', 'ws-form'));
			}

			self::assert_tab_on_form($group_id);

			$label = is_string($label) ? sanitize_text_field($label) : '';
			if($label === '') {

				$label = __('Section', 'ws-form');
			}

			$meta = self::ability_meta_to_array($meta);
			$meta_sanitized = array();
			if(isset($meta['label_render'])) {

				$meta_sanitized['label_render'] = WS_Form_Common::is_true($meta['label_render']) ? 'on' : '';
			}

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->form_id = $this->id;
			$ws_form_section->group_id = $group_id;
			$ws_form_section->label = $label;
			$ws_form_section->meta = $meta_sanitized;
			$ws_form_section->db_set_breakpoint_size_meta();
			$ws_form_section->db_create(absint($section_id_before));
			$ws_form_section->db_checksum();

			return $ws_form_section;
		}

		// Section update
		public function section_update($section_id, $label, $meta = array()) {

			$section_id = absint($section_id);
			if($section_id === 0) {

				throw new Exception(esc_html__('Invalid section ID.', 'ws-form'));
			}

			$has_label = is_string($label) && ($label !== '');
			$meta_array = self::ability_meta_to_array($meta);
			$has_meta = isset($meta_array['label_render']);

			if(!$has_label && !$has_meta) {

				throw new Exception(esc_html__('Provide a label and/or meta to update.', 'ws-form'));
			}

			$group_id = self::assert_section_on_form($section_id);

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->form_id = $this->id;
			$ws_form_section->group_id = $group_id;
			$ws_form_section->id = $section_id;
			$section_object = $ws_form_section->db_read(true, false);

			if($has_label) {

				$section_object->label = sanitize_text_field($label);
			}

			if($has_meta) {

				if(!isset($section_object->meta) || !is_object($section_object->meta)) {

					$section_object->meta = new stdClass();
				}

				$section_object->meta->label_render = WS_Form_Common::is_true($meta_array['label_render']) ? 'on' : '';
			}

			$ws_form_section->db_update_from_object($section_object, false, false, false);
			$ws_form_section->db_checksum();
			$ws_form_section->db_read(true, false);

			return $ws_form_section;
		}

		// Section delete
		public function section_delete($section_id) {

			$section_id = absint($section_id);
			if($section_id === 0) {

				throw new Exception(esc_html__('Invalid section ID.', 'ws-form'));
			}

			$group_id = self::assert_section_on_form($section_id);

			$section_count = 0;
			foreach(self::sections_list() as $section) {

				if((int) $section['group_id'] === (int) $group_id) {

					$section_count++;
				}
			}

			if($section_count <= 1) {

				throw new Exception(esc_html__('Cannot delete the last section on a tab.', 'ws-form'));
			}

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->form_id = $this->id;
			$ws_form_section->group_id = $group_id;
			$ws_form_section->id = $section_id;
			$ws_form_section->db_delete(true);
			$ws_form_section->db_checksum();

			return true;
		}

		// Assert tab belongs to form
		public function assert_tab_on_form($tab_id) {

			foreach(self::tabs_list() as $tab) {

				if((int) $tab['id'] === (int) $tab_id) {

					return true;
				}
			}

			throw new Exception(esc_html__('Tab not found in form.', 'ws-form'));
		}

		// Assert section belongs to form; returns group_id
		public function assert_section_on_form($section_id) {

			foreach(self::sections_list() as $section) {

				if((int) $section['id'] === (int) $section_id) {

					return (int) $section['group_id'];
				}
			}

			throw new Exception(esc_html__('Section not found in form.', 'ws-form'));
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
					'description' => 'Whether or not the field is required. Set to \'on\' if required, blank string if not required. Only set to required if appropriate.',
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
form->groups[0]->sections[]->fields

All forms specified should have:

- 1 group (Tab)
- 1 or more sections
- 1 or more fields in each section

Each section must have a unique positive integer id (1, 2, 3, ...).
Use separate sections to group related fields.
When several fields should be shown or hidden together, put them in one section and control that section with conditionals instead of controlling each field individually.
Strict rule: A field used in a conditional IF to show or hide a section must live in a different section that is always visible. Never put that controlling field inside the section it shows or hides.

Section properties:
- id (number)
- label (string)
- label_render (string) = \"on\" to show the section label on the form, or \"\" to hide it. Default is \"\". Set to \"on\" when the section label helps the person completing the form.
- fields (array)

When label_render is \"on\", put the section context only in the section label. Field labels must stay short and generic (e.g. First Name, Address Line 1). Do not repeat the section name in field labels (do not use prefixes or suffixes like Billing, Shipping, or similar in parentheses).

Order sections and fields in the sequence a person would complete the form.
Fields that control whether later sections are shown must appear before those sections, in their own earlier section when needed. Do not place a controlling field after or inside the sections it affects.

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
9. A field that controls showing or hiding a section must not be inside that section.
10. When a section has label_render set to \"on\", do not repeat that section's name in its field labels.
11. Keep sections and fields in logical completion order. Controlling fields come before the sections they show or hide.

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

		// Get AI prompt for the field update meta property
		public function get_field_update_meta_prompt() {

			return "Optional. Only include meta keys you want to change. Omitted keys are left unchanged.

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

			$example = array(

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
								'label_render' => 'on',

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
										'type' => 'tel',
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

												array('value' => 'email', 'label' => 'Email'),
												array('value' => 'phone', 'label' => 'Phone'),
											)
										)
									)
								)
							)
						)
					)
				)
			);


			return $example;
		}

	}
