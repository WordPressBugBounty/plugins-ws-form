<?php

	class WS_Form_Data_Grid extends WS_Form_Core {

		public $data_grid = false;
		public $field_object = false;

		public function __construct($field_object = false) {

			if($field_object !== false) {

				self::set_field_object($field_object);
			}
		}

		// Set form object
		public function set_field_object($field_object) {

			// Check field object
			$ws_form_field = new WS_Form_Field();

			if(!$ws_form_field->is_valid($field_object)) {

				throw new Exception(esc_html__('Invalid field object', 'ws-form'));
			}

			// Set field object
			$this->field_object = $field_object;

			// Get data grid meta key
			$meta_key = self::get_meta_key($this->field_object);

			$this->data_grid = WS_Form_Common::get_object_meta_value($this->field_object, $meta_key, false);
		}

		// Set data grid in field object
		public function set_data_grid($data_grid, $field_object = false) {

			// Check field object
			if($field_object !== false) {

				$ws_form_field = new WS_Form_Field();

				if($ws_form_field->is_valid($field_object)) {

					// Set field object
					$this->field_object = $field_object;
				}
			}

			// Get data grid meta key
			$meta_key = self::get_meta_key();

			// Check data grid
			if(!self::is_valid($data_grid)) {

				throw new Exception(esc_html__('Invalid data grid', 'ws-form'));
			}

			// Set data grid
			$this->field_object->meta->{$meta_key} = $data_grid;

			return $this->field_object;
		}

		// Get meta key for data grid
		public function get_meta_key() {

			switch(self::get_field_type()) {

				case 'select' : return 'data_grid_select';
				case 'price_select' : return 'data_grid_select_price';
				case 'checkbox' : return 'data_grid_checkbox';
				case 'price_checkbox' : return 'data_grid_checkbox_price';
				case 'radio' : return 'data_grid_radio';
				case 'price_radio' : return 'data_grid_radio_price';
				default : return 'data_grid';
			}
		}

		// Get meta key for column mapping - Label
		public function get_column_mapping_meta_key($column = 'label') {

			switch(self::get_field_type()) {

				case 'select' : return sprintf('select_field_%s', $column);
				case 'price_select' : return sprintf('select_price_field_%s', $column);
				case 'checkbox' : return sprintf('checkbox_field_%s', $column);
				case 'price_checkbox' : return sprintf('checkbox_price_field_%s', $column);
				case 'radio' : return sprintf('radio_field_%s', $column);
				case 'price_radio' : return sprintf('radio_price_field_%s', $column);
				default : return sprintf('datalist_field_%s', $column);
			}
		}

		// Get column index - Value
		public function get_data_grid_column_index_value() {

			// Get column mapping meta key
			$meta_key = self::get_column_mapping_meta_key('value');

			// Get column ID
			$column_id = WS_Form_Common::get_object_meta_value($this->field_object, $meta_key, 0);

			// Get column index
			return self::get_data_grid_column_index_by_id($column_id);
		}

		// Get column index - Label
		public function get_data_grid_column_index_label() {

			// Get column mapping meta key
			$meta_key = self::get_column_mapping_meta_key('label');

			// Get column ID
			$column_id = WS_Form_Common::get_object_meta_value($this->field_object, $meta_key, 0);

			// Get column index
			return self::get_data_grid_column_index_by_id($column_id);
		}

		// Get column index by ID
		public function get_data_grid_column_index_by_id($id) {

			// Process columns
			foreach($this->data_grid->columns as $column_index => $column) {

				// Check column
				if(!isset($column->id)) {

					throw new Exception('Invalid column');
				}

				if($column->id == $id) {

					return $column_index;
				}
			}

			return 0;
		}

		// Get data grid rows as array of objects {'value': $value, 'label' : $label}
		public function get_data_grid_options() {

			// Build options
			$options = array();

			// Check data grid
			if(!self::is_valid($this->data_grid)) { return false; }

			// Get column count
			$column_count = count($this->data_grid->columns);
			if($column_count > 2) { return false; }

			// Get column indexes 
			$column_index_value = self::get_data_grid_column_index_value();
			$column_index_label = self::get_data_grid_column_index_label();

			// Get rows
			$rows = $this->data_grid->groups[0]->rows;

			// Process rows
			foreach($rows as $row) {

				if(
					!is_object($row) &&
					!isset($row->data)
				) {
					continue;
				}

				// Get data
				$data = $row->data;

				// Get value
				$value = isset($data[$column_index_value]) ? $data[$column_index_value] : '';

				// Get label
				$label = isset($data[$column_index_label]) ? $data[$column_index_label] : '';

				// Add option
				$options[] = array('value' => $value, 'label' => $label);
			}

			return $options;
		}

		// Update data grid rows from array of objects {'value': $value, 'label' : $label}
		public function update_data_grid_from_options($options) {

			// Check options
			if(!self::is_valid_options($options)) {

				throw new Exception(esc_html__('Invalid options', 'ws-form'));
			}

			// Get field ID
			$field_id = $this->field_object->id;

			// Get current data grid
			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = $field_id;
			$field_object_old = $ws_form_field->db_read();

			// Create instance of WS_Form_Data_Grid contining the old field object
			$ws_form_data_grid_old = new WS_Form_Data_Grid($field_object_old);

			// Get data grid meta key
			$meta_key = $ws_form_data_grid_old->get_meta_key();

			// Get column indexes 
			$column_index_value = $ws_form_data_grid_old->get_data_grid_column_index_value();
			$column_index_label = $ws_form_data_grid_old->get_data_grid_column_index_label();

			// Get column count
			if(!is_array($ws_form_data_grid_old->data_grid->columns)) {

				return $this->field_object;
			}
			$column_count = count($ws_form_data_grid_old->data_grid->columns);

			// Check column counts and column mapping
			if(
				($column_count == 0)

				||

				($column_count > 2)

				||

				(
					($column_count == 1) &&
					(
						($column_index_value > 0) ||
						($column_index_label > 0)
					)
				)

				||

				(
					($column_count == 2) &&
					(
						($column_index_value > 1) ||
						($column_index_label > 1)
					)
				)

				||

				(
					($column_count == 2) &&
					($column_index_value == $column_index_label)
				)
			) {
				throw new Exception(esc_html__('Data grid has a structure that cannot be updated.', 'ws-form'));
			}

			// Get data source
			$data_source = WS_Form_Common::get_object_meta_value($field_object_old, 'data_source_id', '');
			if(!empty($data_source)) {

				throw new Exception(esc_html__('Data grid has a data source and cannot be updated.', 'ws-form'));
			}

			// Build new rows
			$rows = array();
			$id = 1;
			foreach($options as $option) {

				// Check option
				if(!self::is_valid_option($option)) { continue; }

				// Build data
				$data = array_fill(0, $column_count, '');

				// Add value
				if($column_index_value < $column_count) {

					$data[$column_index_value] = sanitize_text_field(WS_Form_Common::get_object_property($option, 'value'));
				}

				// Add label
				if($column_index_label < $column_count) {

					$data[$column_index_label] = sanitize_text_field(WS_Form_Common::get_object_property($option, 'label'));
				}

				// Add row
				$rows[] = array(

					'id'		=> $id,
					'data'		=> $data
				);

				$id++;
			}

			// Set rows
			$ws_form_data_grid_old->data_grid->groups[0]->rows = $rows;
			$this->field_object->meta->{$meta_key} = $ws_form_data_grid_old->data_grid;
			$this->data_grid = $ws_form_data_grid_old->data_grid;

			return $this->data_grid;
		}

		// Set data grid rows from array of objects {'value': $value, 'label' : $label}
		public function set_data_grid_from_options($options) {

			// Check options
			if(!self::is_valid_options($options)) {

				throw new Exception(esc_html__('Invalid options', 'ws-form'));
			}

			// Get default data grid
			$data_grid = self::get_default_data_grid();

			// Build new rows
			$rows = array();
			$id = 1;
			foreach($options as $option) {

				if(is_object($option)) {

					$option = (array) $option;
				}

				if(
					!is_array($option) ||
					!isset($option['value']) ||
					!isset($option['label'])
				) {
					continue;
				}

				$rows[] = array(

					'id'		=> $id,
					'data'		=> array(

						sanitize_text_field($option['value']),
						sanitize_text_field($option['label'])
					)
				);

				$id++;
			}

			// Set rows
			$data_grid['groups'][0]['rows'] = $rows;

			// Set columns
			$data_grid['columns'] = array(

				array('id' => 0, 'label' => __('Value', 'ws-form')),
				array('id' => 1, 'label' => __('Label', 'ws-form'))
			);

			// Get data grid meta key
			$meta_key_data_grid = self::get_meta_key();

			// Get column mapping meta keys
			$meta_key_column_mapping_label = self::get_column_mapping_meta_key('label');
			$meta_key_column_mapping_parse_variables = self::get_column_mapping_meta_key('parse_variable');

			// Set meta
			$this->field_object->meta->{$meta_key_data_grid} = $data_grid;
			$this->field_object->meta->{$meta_key_column_mapping_label} = 1;
			$this->field_object->meta->{$meta_key_column_mapping_parse_variables} = 1;
			$this->data_grid = $data_grid;

			return $data_grid;
		}

		// Get base meta
		public function get_default_data_grid() {

			// Get data grid meta key
			$meta_key = self::get_meta_key();

			// Get base meta
			$meta_keys = WS_Form_Config::get_meta_keys();
			if(
				!isset($meta_keys[$meta_key]) ||
				!isset($meta_keys[$meta_key]['default'])
			) {
				throw new Exception(esc_html__('Invalid field type', 'ws-form'));
			}

			return $meta_keys[$meta_key]['default'];
		}

		// Get field type
		public function get_field_type() {

			return $this->field_object->type;
		}

		// Get groups
		public function get_groups($data_grid = false, $group_id = false) {

			if(self::is_valid($data_grid)) {

				$this->data_grid = $data_grid;
			}

			if(!self::is_valid($this->data_grid)) {

				throw new Exception(esc_html__('Invalid data grid', 'ws-form'));
			}

			// Get the groups
			$groups = $this->data_grid->groups;

			// Check the group ID
			if($group_id !== false) {

				if(!isset($groups[$group_id])) {

					throw new Exception(esc_html__('Group ID not found', 'ws-form'));
				}

				return array($groups[$group_id]);
			}

			return $groups;
		}

		// Get group next row ID
		public function get_group_row_id_next($group_object) {

			if(!self::is_valid_group($group_object)) {

				throw new Exception(esc_html__('Invalid group', 'ws-form'));
			}

			$rows = $group_object->rows;

			$id_max = 0;
			foreach($rows as $row) {

				if(!isset($row->id)) {

					throw new Exception(esc_html__('Row ID not found', 'ws-form'));
				}

				if($row->id > $id_max) { $id_max = $row->id; }
			}

			return ++$id_max;
		}

		// Clear rows
		public function rows_clear($group_id) {

			// Get groups from data grid
			$groups = self::get_groups($this->data_grid, $group_id);
			
			// Process each row
			foreach($groups as $group) {

				$group->rows = array();
			}

			return $this->field_object;
		}

		// Row add
		public function row_add($row) {

			// Check data grid row
			if(!self::is_valid_row($row)) {

				throw new Exception(esc_html__('Invalid data grid row', 'ws-form'));
			}

			// Get first group from data grid
			$group = $this->data_grid->groups[0];

			// Set defaults if not already set
			if(!isset($row->default)) { $row->default = false; }
			if(!isset($row->required)) { $row->required = false; }
			if(!isset($row->disabled)) { $row->disabled = false; }
			if(!isset($row->hidden)) { $row->hidden = false; }

			// Set row ID
			$row->id = self::get_group_row_id_next($group);

			// Add to rows
			$group->rows[] = $row;

			return $this->field_object;
		}

		// Is valid
		public function is_valid($data_grid) {

			return (

				is_object($data_grid) &&
				isset($data_grid->groups) &&
				is_array($data_grid->groups) &&
				isset($data_grid->groups[0]->rows) &&
				is_array($data_grid->groups[0]->rows) &&
				isset($data_grid->columns) &&
				is_array($data_grid->columns)
			);	
		}

		// Is valid - Group
		public function is_valid_group($data_grid_group_object) {

			return (
				is_object($data_grid_group_object) &&
				isset($data_grid_group_object->rows)
			);
		}

		// Is valid - Row
		public function is_valid_row($data_grid_row_object) {

			return (
				is_object($data_grid_row_object) &&
				isset($data_grid_row_object->data) &&
				is_array($data_grid_row_object->data)
			);
		}

		// Is valid - Options
		public function is_valid_options($options) {

			return (
				is_array($options)
			);
		}

		// Is valid - Option
		public function is_valid_option($option) {

			return (
				is_object($option) &&
				isset($option->value) &&
				isset($option->label)
			);
		}
	}
