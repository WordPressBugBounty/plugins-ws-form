<?php

	#[AllowDynamicProperties]
	class WS_Form_Data_Source_Toolset extends WS_Form_Data_Source {

		public $id = 'toolset';
		public $pro_required = false;
		public $label;
		public $label_retrieving;
		public $records_per_page = 0;

		public function __construct() {

			// Version check
			if(!WS_Form_Toolset::toolset_version_check()) { return; }

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			// Register API endpoint
			add_action('rest_api_init', array($this, 'rest_api_init'), 10, 0);

			// Records per page
			$this->records_per_page = apply_filters('wsf_data_source_' . $this->id . '_records_per_age', $this->records_per_page);

			// Register init actin
			add_action('init', array($this, 'init'));
		}

		public function init() {

			// Set label
			$this->label = __('Toolset Field Options', 'ws-form');

			// Set label retrieving
			$this->label_retrieving = __('Retrieving Toolset field options...', 'ws-form');

			// Register data source
			parent::register($this);
		}

		// Get
		public function get($form_object, $field_id, $page, $meta_key, $meta_value, $no_paging = false, $api_request = false) {

			// Check meta key
			if(empty($meta_key)) { return self::error(__('No meta key specified', 'ws-form'), $field_id, $this, $api_request); }

			// Get meta key config
			$meta_keys = WS_Form_Config::get_meta_keys();
			if(!isset($meta_keys[$meta_key])) { return self::error(__('Unknown meta key', 'ws-form'), $field_id, $this, $api_request); }
			$meta_key_config = $meta_keys[$meta_key];

			// Check meta value
			if(
				!is_object($meta_value) ||
				!isset($meta_value->columns) ||
				!isset($meta_value->groups) ||
				!isset($meta_value->groups[0])
			) {

				if(!isset($meta_key_config['default'])) { return self::error(__('No default value', 'ws-form'), $field_id, $this, $api_request); }

				// If meta_value is invalid, create one from default
				$meta_value = json_decode(wp_json_encode($meta_key_config['default']));
			}

			// Columns
			$meta_value->columns = array(

				(object) array('id' => 0, 'label' => __('Value', 'ws-form')),
				(object) array('id' => 1, 'label' => __('Label', 'ws-form'))
			);

			// Base meta
			$group = clone($meta_value->groups[0]);

			// Get Toolset field slug
			$toolset_field_slug = $this->{'data_source_' . $this->id . '_field_slug'};

			// Get Toolset field object
			$toolset_factory = Toolset_Field_Definition_Factory::get_factory_by_domain(Toolset_Element_Domain::POSTS);
			$toolset_field_definition = $toolset_factory->load_field_definition($toolset_field_slug);
			if(is_null($toolset_field_definition)) {

				$toolset_factory = Toolset_Field_Definition_Factory::get_factory_by_domain(Toolset_Element_Domain::USERS);
				$toolset_field_definition = $toolset_factory->load_field_definition($toolset_field_slug);
			}
			if(is_null($toolset_field_definition)) {

				return self::error(__('Toolset field slug not found', 'ws-form'), $field_id, $this, $api_request);
			}
			$toolset_field_definition_array = $toolset_field_definition->get_definition_array();

			// Filter by post?
			$filter_by_options = false;
			$filter_by_post = $this->{'data_source_' . $this->id . '_filter_by_post'};
			if($filter_by_post) {

				// Get post ID
				$filter_by_post_id = $this->{'data_source_' . $this->id . '_filter_by_post_id'};
				if($filter_by_post_id == '') { $filter_by_post_id = '#post_id'; }
				$filter_by_post_id = absint(WS_Form_Common::parse_variables_process($filter_by_post_id, $form_object, false, 'text/plain'));

				if($filter_by_post_id > 0) {

					// Get options
					$filter_by_options = get_post_meta($filter_by_post_id, sprintf('wpcf-%s', $toolset_field_slug), true);
				}
			}

			// Get Toolset field label
			$label = $toolset_field_definition->get_name();

			// Get Toolset field choices
			$options_definition = toolset_ensarr(toolset_getnest($toolset_field_definition_array, array('data', 'options')));

			// The 'default' key can be present, we have to remove it so it's not handled as another option.
			$has_default = array_key_exists('default', $options_definition);
			$default_option_id = toolset_getarr($options_definition, 'default', 'no-default');
			if($has_default) {
				unset($options_definition['default']);
			}

			// Run through choices
			$rows = array();
			$row_index = 1;
			foreach($options_definition as $option_id => $option_config) {

				switch($toolset_field_definition->get_type()->get_slug()) {

					case Toolset_Field_Type_Definition_Factory::RADIO:
						$option = new Toolset_Field_Option_Radio($option_id, $option_config, $default_option_id, $toolset_field_definition);
						break;

					case Toolset_Field_Type_Definition_Factory::SELECT:
						$option = new Toolset_Field_Option_Select($option_id, $option_config, $default_option_id, $toolset_field_definition);
						break;

					case Toolset_Field_Type_Definition_Factory::CHECKBOXES:
						$option = new Toolset_Field_Option_Checkboxes($option_id, $option_config, $default_option_id);
						break;
				}

				// Get value
				$value = $option->get_value_to_store();

				// Get label
				$label = $option->get_label();

				// Get checked
				$default = ($default_option_id === $option_id) || (isset($option_config['checked']) ? $option_config['checked'] : false);

				// Filter by post?
				if($filter_by_options !== false) {

					if(!in_array($value, $filter_by_options)) { continue; }
				}

				$rows[] = (object) array(

					'id'		=> $row_index++,
					'default'	=> ($default ? 'on' : ''),
					'data'		=> array(

						$value,
						$label
					)
				);
			}

			// Build new group if one does not exist
			if(!isset($meta_value->groups[0])) {

				$meta_value->groups[0] = $group;
			}

			$meta_value->groups[0]->label = $label;

			// Rows
			$meta_value->groups[0]->rows = $rows;

			// Delete any old groups
			$group_index = 1;
			while(isset($meta_value->groups[$group_index])) {

				unset($meta_value->groups[$group_index++]);
			}

			// Column mapping
			$meta_keys = parent::get_column_mapping(array(), $meta_value, $meta_key_config);

			// Return data
			return array('error' => false, 'error_message' => '', 'meta_value' => $meta_value, 'max_num_pages' => 0, 'meta_keys' => $meta_keys);
		}

		// Get meta keys
		public function get_data_source_meta_keys() {

			return array(

				'data_source_' . $this->id . '_field_slug',
				'data_source_' . $this->id . '_filter_by_post',
				'data_source_' . $this->id . '_filter_by_post_id'
			);
		}

		// Get settings
		public function get_data_source_settings() {

			// Build settings
			$settings = array(

				'meta_keys' => self::get_data_source_meta_keys()
			);

			// Add retrieve button
			$settings['meta_keys'][] = 'data_source_' . $this->id . '_get';

			// Wrap settings so they will work with sidebar_html function in admin.js
			$settings = parent::get_settings_wrapper($settings);

			// Add label
			$settings->label = $this->label;

			// Add label retrieving
			$settings->label_retrieving = $this->label_retrieving;

			// Add API GET endpoint
			$settings->endpoint_get = 'data-source/' . $this->id . '/';

			// Apply filter
			$settings = apply_filters('wsf_data_source_' . $this->id . '_settings', $settings);

			return $settings;
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			$field_options = is_admin() ? array_merge(

				WS_Form_Toolset::toolset_get_fields_all(array('domain' => Toolset_Element_Domain::POSTS), true, false, true, false),
				WS_Form_Toolset::toolset_get_fields_all(array('domain' => Toolset_Element_Domain::USERS), true, false, true, false)

			) : array();

			// Build config_meta_keys
			$config_meta_keys = array(

				// Toolset Field
				'data_source_' . $this->id . '_field_slug' => array(

					'label'						=>	__('Toolset Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	$field_options,
					'options_blank'				=>	__('Select...', 'ws-form')
				),

				// Toolset Filter by Post
				'data_source_' . $this->id . '_filter_by_post' => array(

					'label'						=>	__('Filter by Post', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('Filter the options by those selected in a post.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'data_source_' . $this->id . '_field_slug',
							'meta_value'		=>	''
						)
					)
				),

				// Toolset Filter by Post ID
				'data_source_' . $this->id . '_filter_by_post_id' => array(

					'label'						=>	__('Post ID', 'ws-form'),
					'type'						=>	'text',
					'default'					=>	'',
					'placeholder'				=>	'#post_id',
					'help'						=>	sprintf(

						/* translators: %s = WS Form */
						__('Choose the post ID to filter by. This can be a number or %s variable. If blank, the ID of the post the form is shown on will be used.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'data_source_' . $this->id . '_field_slug',
							'meta_value'		=>	''
						),

						array(

							'logic_previous'	=>	'&&',
							'logic'				=>	'==',
							'meta_key'			=>	'data_source_' . $this->id . '_filter_by_post'
						)
					)
				),

				// Get Data
				'data_source_' . $this->id . '_get' => array(

					'label'						=>	__('Get Data', 'ws-form'),
					'type'						=>	'button',
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'data_source_' . $this->id . '_field_slug',
							'meta_value'		=>	''
						)
					),
					'key'						=>	'data_source_get'
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// Build REST API endpoints
		public function rest_api_init() {

			// Get data source
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/data-source/' . $this->id . '/', array('methods' => 'POST', 'callback' => array($this, 'api_post'), 'permission_callback' => function () { return WS_Form_Common::can_user('edit_form'); }));
		}

		// api_post
		public function api_post() {

			// Get meta keys
			$meta_keys = self::get_data_source_meta_keys();

			// Read settings
			foreach($meta_keys as $meta_key) {

				$this->{$meta_key} = WS_Form_Common::get_query_var($meta_key, false);
				if(
					is_object($this->{$meta_key}) ||
					is_array($this->{$meta_key})
				) {

					$this->{$meta_key} = json_decode(wp_json_encode($this->{$meta_key}));
				}
			}

			// Get field ID
			$field_id = WS_Form_Common::get_query_var('field_id', 0);

			// Get page
			$page = absint(WS_Form_Common::get_query_var('page', 1));

			// Get meta key
			$meta_key = WS_Form_Common::get_query_var('meta_key', 0);

			// Get meta value
			$meta_value = WS_Form_Common::get_query_var('meta_value', 0);

			// Get return data
			$get_return = self::get(false, $field_id, $page, $meta_key, $meta_value, false, true);

			// Error checking
			if($get_return['error']) {

				// Error
				return self::api_error($get_return);

			} else {

				// Success
				return $get_return;
			}
		}
	}

	new WS_Form_Data_Source_Toolset();
