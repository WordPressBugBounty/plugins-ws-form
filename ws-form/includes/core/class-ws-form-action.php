<?php

	abstract class WS_Form_Action {

		// Variables global to this abstract class
		public static $actions = array();
		public static $action_post_count = array();
		private static $return_array = array();
		private static $form_tab_populate_added = false;
		public static $spam_level = null;

		// Run actions
		public function actions_post($form, $submit, $complete_action = false, $row_id_filter = false, $database_only = false, $process_file_fields = false) {

			// Full return array
			$return_array_full = array();

			// Check if request is to only create database record
			if($database_only) {

				$actions = array();
				$action_database_found = false;
				$action_database_required = true;

			} else {

				// Get form actions to run
				$actions = self::get_form_actions($form, $submit->post_mode, $row_id_filter);

				// Actions filter (Allows you to add additional actions to run)
				$actions = apply_filters('wsf_actions_post_' . $submit->post_mode, $actions, $form, $submit);

				$action_logs = array();
				$action_errors = array();
				$action_js = array();

				// Run through form actions and check to see if database action is mandatory
				$action_database_found = false;
				$action_database_required = false;
				foreach($actions as $action_index => $config) {

					// Get action
					if(!isset($config['id'])) { continue; }
					$id = $config['id'];

					// Skip uninstalled actions
					if(!isset(self::$actions[$id])) { continue; }
					$action = self::$actions[$id];

					$action_database_required |= (isset($action->database_required) ? $action->database_required : false);

					if($id == 'database') { $action_database_found = true; }
				}
			}

			// Set spam level
			if($submit->spam_level !== null) { self::$spam_level = $submit->spam_level; }

			// If saving, database is required
			if($submit->post_mode == 'save') {

				$action_database_required = true;
			}

			// Manually add database action if required
			if(!$action_database_found && $action_database_required) {

				$priorities = array(0, 200);

				foreach($priorities as $priority) {

					$actions[] = array(

						'id' => 'database',
						'meta' => array(
							'action_database_field_filter' => '',
							'action_database_field_filter_mapping' => '',
							'action_database_expire' => '',
							'action_database_expire_duration' => 90
						),
						'events' => array(
							'0' => 'save',
							'1' => 'submit'
						),
						'label' => 'Database',
						'priority' => $priority,
						'row_index' => 0
					);
				}

				$action_database_found = true;
			}

			// Sort actions by priority
			usort($actions, function ($action1, $action2) {

				if($action1['priority'] == $action2['priority']) {

					return ($action1['row_index'] == $action2['row_index']) ? 0 : ($action1['row_index'] < $action2['row_index'] ? -1 : 1);
				}

				return ($action1['priority'] < $action2['priority']) ? -1 : 1;
			});

			// Get spam threshold
			$spam_threshold = absint(WS_Form_Common::get_object_meta_value($form, 'spam_threshold', 50));

			// Run through form actions
			$action_run_index = 0;
			foreach($actions as $action_index => $config) {

				// Should this action run?
				$action_post_do = apply_filters('wsf_action_post_do', true, $form, $submit, $row_id_filter, $database_only, $config);
				if(!$action_post_do) { continue; }

				// Get action ID
				if(!isset($config['id'])) { continue; }
				$action_id = $config['id'];

				// If spam threshold exceeded, don't run actions unless its the database action
				if(
					(self::$spam_level >= $spam_threshold) &&
					($action_id != 'database')
				) {
					// Do not log this action
					unset($actions[$action_index]);

					// Do not run this action (unless database)
					continue;
				}

				if(
					$process_file_fields && (

						(($action_run_index == 0) && ($action_id != 'database')) ||
						($action_run_index > 0)
					)
				) {

					// Process file fields
					$submit->process_file_fields($form);

					// Check for field validation errors
					if(count($submit->error_validation_actions) > 0) { break; }

					// Only process once
					$process_file_fields = false;
				}

				// Reset return array
				self::return_array_reset();

				try {

					// Add actions to submit (Needs to run on each loop ready for 'Database' action)
					$submit->actions = serialize(array_values($actions));

					// Call action post method
					$return_value = self::action_post($form, $submit, $config, false);

				} catch(Exception $e) {

					throw new Exception($e->getMessage());
				}

				// If spam threshold exceeded, throw error
				if(
					(self::$spam_level >= $spam_threshold) &&
					($action_id != 'database')
				) {
					self::error(__('Spam detected', 'ws-form'));
				}

				// Should this action be logged?
				$action_log = isset($config['action_log']) ? $config['action_log'] : true;
				if(!$action_log) {

					unset($actions[$action_index]);

				} else {

					// Add logs, errors and js to submit
					if(!isset($action_logs)) { $action_logs = array(); }
					if(!isset($action_errors)) { $action_errors = array(); }
					if(!isset($action_js)) { $action_js = array(); }

					$action_logs = array_merge($action_logs, self::$return_array['logs']);
					$action_errors = array_merge($action_errors, self::$return_array['errors']);
					$action_js = array_merge($action_js, self::$return_array['js']);

					// Set up submit with action data
					$actions[$action_index]['logs'] = self::$return_array['logs'];
					$actions[$action_index]['errors'] = self::$return_array['errors'];
					$actions[$action_index]['js'] = self::$return_array['js'];
				}

				// Halt action processing? (e.g. if blatant spam detected)
				if($return_value === 'halt') { break; }

				$action_run_index++;
			}

			// Build full return array
			$return_array_full = array(

				'logs'		=> $action_logs,
				'errors'	=> $action_errors,
				'js'		=> $action_js
			);

			// Do complete action
			if($complete_action !== false) { do_action($complete_action, $return_array_full); }

			return true;
		}

		// Run actions
		public function action_repost($form, $submit, $config, $complete_action = false) {

			// Reset return array
			self::return_array_reset();

			try {

				// Call action post method
				$return_value = self::action_post($form, $submit, $config, false);

			} catch(Exception $e) {

				throw new Exception($e->getMessage());
			}

			// Do complete action
			if($complete_action !== false) { do_action($complete_action, self::$return_array); }

			return true;
		}

		// Run action
		public function action_post($form, $submit, $config, $complete_action = false) {

			// Reset return array
			if($complete_action !== false) { self::return_array_reset(); }

			// Get action
			if(!isset($config['id'])) { return false; }
			$id = $config['id'];

			// Skip uninstalled actions
			if(!isset(self::$actions[$id])) { return false; }

			// Get action object
			$action_obj = self::$actions[$id];

			// If user is using conditional logic to run an action, and this action is 'Save Submission' (database), then enable returning of hash
			if(
				!$submit->return_hash &&
				($id === 'database')
			) {

				$submit->return_hash = true;
			}

			// Filter
			$action_obj = apply_filters('wsf_action_pre_post', $action_obj, $form, $submit, $config);
			$action_obj = apply_filters('wsf_action_pre_post_' . $id, $action_obj, $form, $submit, $config);

			// Action post count
			if(!isset(self::$action_post_count[$id])) { self::$action_post_count[$id] = 0; }
			self::$action_post_count[$id]++;

			// Run the action
			$return_value = $action_obj->post($form, $submit, $config, self::$action_post_count[$id]);

			// Do complete action
			if($complete_action !== false) { do_action($complete_action, self::$return_array, true); }

			return $return_value;
		}

		// Reset return array
		public function return_array_reset() {

			self::$return_array = array(

				'logs' => array(),
				'errors' => array(),
				'js' => array()
			);
		}

		// Get configuration
		public function get_config($config, $meta_key, $default_value = false, $throw_error = false) {

			if(!isset($config['meta']) || !isset($config['meta'][$meta_key])) {

				return $throw_error ? self::get_config_error($config, $meta_key, $default_value) : $default_value;
			}

			return $config['meta'][$meta_key];
		}

		// Get configuration error
		public function get_config_error($config, $meta_key, $default_value = false) {

			if($throw_error) { self::error('Cannot find configuration meta_key: ' + $meta_key, false, false); }

			return $default_value;
		}

		// Get action settings
		public static function get_settings() {

			$return_settings = array();

			// Build action settings
			foreach(self::$actions as $id => $action) {

				if(method_exists($action, 'get_action_settings')) {

					$return_settings[$id] = $action->get_action_settings();

					// Settings filter
					$return_settings[$id] = apply_filters('wsf_action_settings', $return_settings[$id], $action);
				}
			}

			// Sort actions alphabetically
			uasort($return_settings, function ($action1, $action2) {

			    return ($action1->label == $action2->label) ? 0 : (($action1->label < $action2->label) ? -1 : 1);
			});

			return $return_settings;
		}

		public function get_settings_wrapper($settings) {

			$settings_wrapper = new stdClass();

			$settings_wrapper->fieldsets = array(

				$this->id	=> $settings
			);

			return $settings_wrapper;
		}

		// Get action count
		public static function get_form_action_count($form) {

			$data_grid = WS_Form_Common::get_object_meta_value($form, 'action', array());

			// Check data grid rows exists
			if(!isset($data_grid->groups)) { return 0; }
			if(!isset($data_grid->groups[0])) { return 0; }
			if(!isset($data_grid->groups[0]->rows)) { return 0; }

			return count($data_grid->groups[0]->rows);
		}

		// Get actions configured for a form
		// $row_id_filter = Filters by row ID (e.g. 3)
		// $action_id_filter - Filters by action ID (e.g. search)
		public function get_form_actions($form, $post_mode = false, $row_id_filter = 0, $action_id_filter = false) {

			$actions = array();

			// Read meta
			$data_grid = WS_Form_Common::get_object_meta_value($form, 'action', array());

			// Check data grid rows exists
			if(!isset($data_grid->groups)) { return $actions; }
			if(!isset($data_grid->groups[0])) { return $actions; }
			if(!isset($data_grid->groups[0]->rows)) { return $actions; }

			// Check for 'actions_run' (Conditional wants to run an action)
			$actions_run = WS_Form_Common::get_query_var_nonce('wsf_actions_run');
			if(!is_array($actions_run) || (count($actions_run) == 0)) {

				$actions_run = false;
			}

			// Read rows
			$rows = $data_grid->groups[0]->rows;

			foreach($rows as $row_index => $row) {

				// Ignore rows with no or invalid data
				if(!isset($row->data) && (count($row->data) != 2)) { continue; }

				// Ignore disabled rows
				if(isset($row->disabled) && ($row->disabled != '')) { continue; }

				// Read row ID
				$row_id = $row->id;

				// Single row ID requests
				if(
					($row_id_filter > 0) &&
					($row_id_filter !== $row_id)
				) {
					continue;
				}

				// Read JSON data
				$row_data_json = $row->data[1];

				// Decode JSON data
				$config = json_decode($row_data_json, true);

				// Ignore JSON data that cannot be decoded
				if(is_null($config)) { continue; }

				// Get action ID
				if(!isset($config['id'])) { continue; }
				$action_id = $config['id'];

				// Single action ID requests
				if(
					($action_id_filter !== false) &&
					($action_id_filter !== $action_id)
				) {
					continue;
				}

				// Ignore uninstalled actions
				if(!isset(self::$actions[$action_id])) { continue; }

				// Check for post_mode
				if(
					($post_mode !== false) &&
					($post_mode !== 'action')
				) {

					if($actions_run !== false) {

						// If conditional logic is specifying which actions to run...
						if(!in_array($row_id, $actions_run)) { continue; }

					} else {

						if(!in_array($post_mode, $config['events'])) { continue; }
					}
				}

				// Add label
				$config['label'] = $row->data[0];

				// Add row index
				$config['row_index'] = $row_index;

				// Check for custom priority
				if(method_exists(self::$actions[$action_id], 'get_priority')) {

					$config['priority'] = self::$actions[$action_id]->get_priority($config);

				} else {

					$config['priority'] = self::$actions[$action_id]->priority;
				}

				// Add to actions array
				if(is_array($config['priority'])) {

					$priority_count = count($config['priority']) - 1;

					foreach($config['priority'] as $priority_index => $priority) {

						$action = $config;
						$action['priority'] = $priority;
						if($priority_index < $priority_count) { $action['action_log'] = false; }
						$actions[] = $action;
					}

				} else {

					$actions[] = $config;
				}
			}

			// Sort by priority
			usort($actions, function ($action1, $action2) {

				if ($action1['priority'] == $action2['priority']) {

					return ($action1['row_index'] == $action2['row_index']) ? 0 : ($action1['row_index'] < $action2['row_index'] ? -1 : 1);
				}

				return ($action1['priority'] < $action2['priority']) ? -1 : 1;
			});

			return $actions;
		}

		// Error
		public function error($errors, $action_js = false, $label_prefix = true) {

			if(!is_array($errors)) { $errors = array($errors); }
			if(!isset(self::$return_array['errors'])) { self::$return_array['errors'] = array(); }

			// Sanitize errors
			foreach($errors as $error_index => $error) {

				$errors[$error_index] = sanitize_text_field($error);
			}

			// Add message to queue
			self::$return_array['errors'] = array_merge(self::$return_array['errors'], $errors);

			// Add action_js to queue
			if(is_array($action_js)) { self::$return_array['js'] = array_merge(self::$return_array['js'], $action_js); }

			return false;
		}

		// Error count
		public function error_count() {

			return count(self::$return_array['errors']);
		}

		// Action API call response
		public function api_response($data) {

			// API response
			$ws_form_api = new WS_Form_API();

			// Check for errors
			if(isset(self::$return_array['errors'])) {

				$ws_form_api->api_throw_error(self::$return_array['errors'][0]);
			}

			// Normal response
			$ws_form_api->api_json_response($data);
		}

		// Action API trigger
		public function api_trigger($event, $params) {

			// API response
			$ws_form_api = new WS_Form_API();

			$data = array(

				'js' => array(

					array(

						'action' => 'trigger',
						'event' => $event,
						'params' => $params
					)
				)
			);

			// Normal response
			$ws_form_api->api_json_response($data);
		}

		// Action ran successfully, log message and set JSON return variables
		public function success($logs, $action_js = false, $label_prefix = true) {

			if(!is_array($logs)) { $logs = array($logs); }
			if(!isset(self::$return_array['logs'])) { self::$return_array['logs'] = array(); }

			// Prefix logs with action label
			if($label_prefix) {

				foreach($logs as $log_index => $log) {

					$logs[$log_index] = $this->label . ' - ' . $log;
				}
			}

			// Add message to queue
			self::$return_array['logs'] = array_merge(self::$return_array['logs'], $logs);

			// Add action_js to queue
			if(is_array($action_js)) { self::$return_array['js'] = array_merge(self::$return_array['js'], $action_js); }
		}

		// Success count
		public function success_count() {

			return count(self::$return_array['logs']);
		}

		// Register action
		public function register($object) {

			// Initialize WordPress
			if(count(self::$actions) == 0) { self::wp_init(); }

			// Check if pro required for action
			if(!WS_Form_Common::is_edition($this->pro_required ? 'pro' : 'basic')) { return false; }

			// Get action ID
			$action_id = $this->id;

			// Add action to actions array
			self::$actions[$action_id] = $object;

			// Check if action can get data for form population
			if(!self::$form_tab_populate_added && self::check_capabilities(self::$actions[$action_id], array('get'))) {

				// Add actions tab to form sidebar
				add_filter('wsf_config_settings_form_admin', array($this, 'config_settings_form_admin_action'), 5);

				// Add actions meta keys
				add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys_action'), 5);

				// Form tab populated (set to true so it only populates once)
				self::$form_tab_populate_added = true;
			}
		}

		public function form_create_meta_keys($meta_keys) {

			// Inject default action rows
			if(!isset($meta_keys['action'])) { return $meta_keys; }

			// Build rows
			if(count(self::$actions) == 0) { return $meta_keys; }

			$rows = array();
			$row_index = 1;

			foreach(self::$actions as $action) {

				// Skip any actions that should not be added to a new form
				if($action->form_add === false) { continue; }

				// Get action ID
				$action_id = $action->id;

				// Get settings
				$action_settings = $action->get_action_settings();

				// Get meta keys
				$meta = array();
				if(
					isset($action_settings->fieldsets) && 
					isset($action_settings->fieldsets[$action_id]) && 
					isset($action_settings->fieldsets[$action_id]['meta_keys'])
				) {

					$action_meta_keys = $action_settings->fieldsets[$action_id]['meta_keys'];

					foreach($action_meta_keys as $action_meta_key) {

						if(isset($meta_keys[$action_meta_key])) {

							$default_value = isset($meta_keys[$action_meta_key]['default']) ? $meta_keys[$action_meta_key]['default'] : '';

						} else {

							$default_value = '';
						}

						$meta[$action_meta_key] = $default_value;
					}
				}

				// Build action data
				$action_data = array(

					'id' => $action->id,
					'meta' => $meta,
					'events' => $action->events
				);

				$action_json = wp_json_encode($action_data);

				// Build new row
				$row = array(

					'id'		=> $row_index,
					'data'		=> array($action->label_action, $action_json)
				);

				$rows[] = $row;

				$row_index++;
			}

			// Add rows to the meta_key
			$meta_keys['action']['default']['groups'][0]['rows'] = $rows;

			return $meta_keys;
		}

		public function config_settings_form_admin_action($config_settings_form_admin) {

			$config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['action'] = array(

				'label'		=>	__('Data', 'ws-form'),

				'fieldsets'		=>	array(

					array(

						'label'			=>	__('Populate', 'ws-form'),
						'meta_keys'		=> array('form_populate_enabled', 'form_populate_action_id', 'form_populate_list_id', 'form_populate_field_mapping', 'form_populate_tag_mapping')
					)
				)
			);

			return $config_settings_form_admin;
		}

		public function config_meta_keys_action($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys_action = array(

				// Form populate enable
				'form_populate_enabled'		=> array(

					'label'						=>	__('Populate Using Action', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	sprintf(

						/* translators: %s = WS Form */
						__('If checked, %s will populate the form with data from an action.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'default'					=>	''
				),

				// Action ID
				'form_populate_action_id'	=> array(

					'label'							=>	__('Action to Populate From', 'ws-form'),
					'type'							=>	'select',
					'help'							=>	sprintf(

						/* translators: %s = WS Form */
						__('Select which action to populate this form with.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'options'						=>	array(),
					'options_action_api_repopulate'	=>	true,
					'condition'						=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'form_populate_enabled',
							'meta_value'	=>	'on'
						)
					)
				),

				// List ID
				'form_populate_list_id'	=> array(

					'label'							=>	__('List to Populate From', 'ws-form'),
					'type'							=>	'select',
					'help'							=>	__('Select which list to populate this form with.', 'ws-form'),
					'options'						=>	'action_api_populate',
					'options_blank'					=>	__('Select...', 'ws-form'),
					'options_action_id_meta_key'	=>	'form_populate_action_id',
					'options_action_api_populate'	=>	'lists',
					'reload'						=>	array(

						'action_id_meta_key'		=>	'form_populate_action_id',
						'method'					=>	'lists_fetch'
					),
					'condition'						=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'form_populate_enabled',
							'meta_value'	=>	'on',
							'logic_previous'	=>	'&&'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'form_populate_action_id',
							'meta_value'		=>	'',
							'logic_previous'	=>	'&&'
						)
					)
				),

				// Field mapping
				'form_populate_field_mapping'	=> array(

					'label'						=>	__('Field Mapping', 'ws-form'),
					'type'						=>	'repeater',
					'help'						=>	sprintf(

						/* translators: %s = WS Form */
						__('Map list fields to %s fields.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'meta_keys'					=>	array(

						'form_populate_list_fields',
						'ws_form_field_edit'
					),
					'meta_keys_unique'			=>	array(

						'ws_form_field_edit'
					),
					'reload'					=>	array(

						'action_id_meta_key'	=>	'form_populate_action_id',
						'method'				=>	'list_fields_fetch',
						'list_id_meta_key'		=>	'form_populate_list_id'
					),
					'auto_map'					=>	true,
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'form_populate_enabled',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'form_populate_action_id',
							'meta_value'		=>	'',
							'logic_previous'	=>	'&&'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'form_populate_list_id',
							'meta_value'		=>	'',
							'logic_previous'	=>	'&&'
						)
					)
				),

				// Term mapping
				'form_populate_tag_mapping'	=> array(

					'label'						=>	__('Term Mapping', 'ws-form'),
					'type'						=>	'repeater',
					'help'						=>	sprintf(

						/* translators: %s = WS Form */
						__('Map fields containing terms to %s fields.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'meta_keys'					=>	array(

						'ws_form_field_choice'
					),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'form_populate_enabled',
							'meta_value'		=>	'on'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'form_populate_action_id',
							'meta_value'		=>	'',
							'logic_previous'	=>	'&&'
						),

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'form_populate_list_id',
							'meta_value'		=>	'',
							'logic_previous'	=>	'&&'
						)
					)
				),

				// List fields
				'form_populate_list_fields'	=> array(

					'label'							=>	__('Action Field', 'ws-form'),
					'type'							=>	'select',
					'options'						=>	'action_api_populate',
					'options_blank'					=>	__('Select...', 'ws-form'),
					'options_action_id_meta_key'	=>	'form_populate_action_id',
					'options_list_id_meta_key'		=>	'form_populate_list_id',
					'options_action_api_populate'	=>	'list_fields'
				)
			);

			// Add action ID options
			$config_meta_keys_action['form_populate_action_id']['options'][] = array('value' => '', 'text' => __('Select...', 'ws-form'));
			foreach(self::get_actions_with_capabilities(array('get')) as $action_id => $action) {

				// Add action option
				$config_meta_keys_action['form_populate_action_id']['options'][] = array('value' => $action_id, 'text' => $action->label);

				// Only show tags if action supports tags
				if(!method_exists($action, 'get_tags')) {

					$config_meta_keys_action['form_populate_tag_mapping']['condition'][] = array(

						'logic'				=>	'!=',
						'meta_key'			=>	'form_populate_action_id',
						'meta_value'		=>	$action_id,
						'logic_previous'	=>	'&&'
					);
				}
			}

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys_action);

			return $meta_keys;
		}

		public function wp_init() {

			// Register parent WordPress actions
			add_action('wsf_actions_post', array($this, 'actions_post'), 10, 6);
			add_action('wsf_action_post', array($this, 'action_post'), 10, 5);
			add_action('wsf_action_repost', array($this, 'action_repost'), 10, 4);

			// Add build_meta_data filter
			add_filter('wsf_form_create_meta_keys', array($this, 'form_create_meta_keys'), 5, 1);
		}

		public function api_call($endpoint, $path = '', $method = 'GET', $body = null, $headers = array(), $authentication = 'basic', $username = false, $password = false, $accept = 'application/json', $content_type = 'application/json', $timeout = WS_FORM_API_CALL_TIMEOUT, $ssl_verify = WS_FORM_API_CALL_SSL_VERIFY, $cookies = array(), $blocking = true) {

			// Headers
			if(!is_array($headers)) { $headers = array(); }
			if($accept !== false) { $headers['Accept'] = $accept; }
			if($content_type !== false) { $headers['Content-Type'] = $content_type; }
			if($username !== false) {

				switch($authentication) {

					case 'basic' :

						$headers['Authorization']  = 'Basic ' . base64_encode($username . ':' . $password);
						break;
				}
			}

			// Build args
			$args = array(

				'method'		=> $method,
				'headers'		=> $headers,
				'user-agent'	=> WS_Form_Common::get_request_user_agent(),
				'timeout'		=> WS_Form_Common::get_request_timeout($timeout),
				'sslverify'		=> WS_Form_Common::get_request_sslverify($ssl_verify),
				'cookies'		=> $cookies,
				'blocking'		=> $blocking
			);

			// URL
			$url = $endpoint . $path;

			// Body
			if(
				($body !== null) &&
				($body !== false)
			) {

				switch($method) {

					case 'GET' :

						// Convert object to array
						if(is_object($body)) { $body = (array) $body; }

						// Build query string
						if(is_array($body)) {

							// Add query parameter to URL
							$url = WS_Form_Common::wsf_add_query_args($body, $url);
						}

						break;

					default :

						$args['body'] = $body;
				}
			}

			// Call using Wordpress wp_remote_request
			$wp_remote_request_response = wp_remote_request($url, $args);

			// Check for error
			if($api_response_error = is_wp_error($wp_remote_request_response)) {

				// Handle error
				$api_response_error_message = $wp_remote_request_response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				// Handle response
				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($wp_remote_request_response);
				$api_response_body = wp_remote_retrieve_body($wp_remote_request_response);
				$api_response_http_code = wp_remote_retrieve_response_code($wp_remote_request_response);
			}

			// Return response
			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code, 'headers' => $api_response_headers);
		}

		// Get API call header
		public function api_get_header($response, $header) {

			if(
				!isset($response['headers']) ||
				!isset($response['headers'][$header])

			) { return false; }

			return $response['headers'][$header];
		}

		// Get value of an object, otherwise return false if not set
		public function get_object_value($field, $key, $default_value = false) {

			return isset($field->{$key}) ? $field->{$key} : $default_value;
		}

		// Get all actions that have the capabilities provided (string or array for capabilities)
		public static function get_actions_with_capabilities($capabilities) {

			$return_actions = array();

			foreach(self::$actions as $id => $action) {

				if(self::check_capabilities($action, $capabilities) && $action->configured) {

					$return_actions[$id] = $action;
				}
			}

			// Sort alphabetically
			uasort($return_actions, function($a, $b) {

				$a_label = strtolower($a->label);
				$b_label = strtolower($b->label);

				return ($a_label == $b_label) ? 0 : (($a_label < $b_label) ? -1 : 1);
			});

			return $return_actions;
		}

		// Check if action has the capabilities provided (string or array for capabilities)
		public static function check_capabilities($action, $capabilities) {

			$return_value = true;
			foreach($capabilities as $capability) {

				if(!method_exists($action, $capability)) { $return_value = false; break; }
			}

			return $return_value; 
		}

		// Get form data for a particular action and list ID
		public static function update_form($form_id, $action_id, $list_id, $list_sub_id = false, $list = false, $list_fields = false, $list_fields_meta_data = false, $form_fields = false, $form_actions = false, $form_conditionals = false, $form_meta = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			$ws_form_form = new WS_Form_Form;
			$ws_form_form->id = $form_id;
			$form_object = $ws_form_form->db_read(true, true);

			// Field mapping
			$field_mapping_action = array();
			$field_mapping_populate = array();

			// Tag mapping
			$tag_mapping_action = array();
			$tag_mapping_populate = array();

			// Get framework info and calculate breakpoint meta key and value for widths
			$framework_id = WS_Form_Common::option_get('framework');
			$framework_column_count = WS_Form_Common::option_get('framework_column_count');
			$frameworks = WS_Form_Config::get_frameworks();
			$framework_breakpoints = $frameworks['types'][$framework_id]['breakpoints'];
			reset($framework_breakpoints);
			$breakpoint_first = key($framework_breakpoints);
			$breakpoint_meta_key = 'breakpoint_size_' . $breakpoint_first;

			// If action is not installed and active
			if(!isset(self::$actions[$action_id])) { return false; }

			// Get action
			$action = self::$actions[$action_id];

			// Set list ID
			$action->list_id = $list_id;

			// Set list sub ID
			if($list_sub_id !== false) { $action->list_sub_id = $list_sub_id; }

			// Get list (And force API request)
			if($list === false) {

				$list = $action->get_list(true);
			}

			// API was unable to retrieve the list
			if($list === false) {

				$list = array(

					'label' => __('Unknown', 'ws-form')
				);
			}

			// Set label
			$form_object->label = $action->label . ': ' . $list['label'];

			// Action specific meta data
			if(isset($list['meta']) && ($list['meta'] !== false)) {

				foreach($list['meta'] as $meta_key => $meta_value) {

					// Set meta data
					$form_object->meta->{$meta_key} = $meta_value;
				}
			}

			// Update form
			$ws_form_form->db_update_from_object($form_object, true, false);

			$form_field_id_lookup = array();
			$form_field_id_lookup_all = array();
			$form_field_type_lookup = array();

			// Get list fields (And force API request)
			if($list_fields === false) {

				$list_fields = $action->get_list_fields(true);
			}

			// Get list fields meta data
			$group_meta_data = array();
			$section_meta_data = array();

			if(
				($list_fields_meta_data === false) &&
				method_exists($action, 'get_list_fields_meta_data')
			) {
				$list_fields_meta_data = $action->get_list_fields_meta_data();
			}

			if(is_array($list_fields_meta_data)) {

				$group_meta_data = $list_fields_meta_data['group_meta_data'];
				$section_meta_data = $list_fields_meta_data['section_meta_data'];
			}

			// Separate list fields = sections
			$form_structure = array();
			foreach($list_fields as $list_field) {

				// Group index
				$form_structure_group_index = isset($list_field['group_index']) ? $list_field['group_index'] : 0;
				if(!isset($form_structure[$form_structure_group_index])) {

					$form_structure[$form_structure_group_index] = array();
				}

				// Section index
				$form_structure_section_index = isset($list_field['section_index']) ? $list_field['section_index'] : 0;
				if(!isset($form_structure[$form_structure_group_index][$form_structure_section_index])) {

					$form_structure[$form_structure_group_index][$form_structure_section_index] = array();
				}

				// Add field
				$form_structure[$form_structure_group_index][$form_structure_section_index][] = $list_field;
			}

			// Check for empty structure
			if(!isset($form_structure[0])) { $form_structure[0] = array(); }
			if(!isset($form_structure[0][0])) { $form_structure[0][0] = array(); }

			// Run through each group and section
			$section_count = 0;

			foreach($form_structure as $form_structure_group_index => $form_structure_sections) {

				// Create new group
				$ws_form_group = new WS_Form_Group();
				$ws_form_group->form_id = $form_id;

				// Build group meta
				$group_meta_data_array = isset($group_meta_data['group_' . $form_structure_group_index]) ? $group_meta_data['group_' . $form_structure_group_index] : false;
				if(is_array($group_meta_data_array)) {

					foreach($group_meta_data_array as $group_meta_data_key => $group_meta_data_value) {

						switch($group_meta_data_key) {

							case 'label' :

								$ws_form_group->label = $group_meta_data_value;
								break;

							default :

								$ws_form_group->meta[$group_meta_data_key] = $group_meta_data_value;
						}
					}
				}

				// Create group
				$group_id = $ws_form_group->db_create(0, false);

				foreach($form_structure_sections as $form_structure_section_index => $list_fields) {

					// Create new section
					$ws_form_section = new WS_Form_Section();
					$ws_form_section->form_id = $form_id;
					$ws_form_section->group_id = $group_id;

					// Build section meta
					$section_meta_data_array = (

						isset($section_meta_data['group_' . $form_structure_group_index]) &&
						isset($section_meta_data['group_' . $form_structure_group_index]['section_' . $form_structure_section_index])

					) ? $section_meta_data['group_' . $form_structure_group_index]['section_' . $form_structure_section_index] : false;
					if(is_array($section_meta_data_array)) {

						foreach($section_meta_data_array as $section_meta_data_key => $section_meta_data_value) {

							switch($section_meta_data_key) {

								case 'label' :

									$ws_form_section->label = $section_meta_data_value;
									break;

								case 'width_factor' :

									$width_factor = floatval($section_meta_data_value);

									if(
										($width_factor > 0) &&
										($width_factor <= 1)
									) {

										$breakpoint_meta_value = round($framework_column_count * $width_factor);

										// Set column width
										$ws_form_section->meta[$breakpoint_meta_key] = $breakpoint_meta_value;
									}
									break;

								default :

									$ws_form_section->meta[$section_meta_data_key] = $section_meta_data_value;
							}
						}
					}

					// Create section
					$section_id = $ws_form_section->db_create();
					$section_count++;

					// Ensure sort indexes are tidy
					$sort_index = 0;
					usort($list_fields, function ($a, $b) {

						return ($a['sort_index'] == $b['sort_index']) ? 0 : (($a['sort_index'] < $b['sort_index']) ? -1 : 1);
					});
					$sort_index = 0;
					foreach($list_fields as $key => $list_field) {

						$list_fields[$key]['sort_index'] = $sort_index++;
					}

					// Process each list field
					foreach($list_fields as $list_field) {

						// Add to form?
						$no_add = isset($list_field['no_add']) && $list_field['no_add'];
						if($no_add) { continue; }

						// Check if a label exists
						if($list_field['label_field'] == '') { continue; }

						// Create field
						$ws_form_field = new WS_Form_Field();
						$ws_form_field->form_id = $form_id;
						$ws_form_field->section_id = $section_id;
						$ws_form_field->type = $list_field['type'];
						$ws_form_field->db_create();

						// Skip errors
						if($ws_form_field->id == 0) { continue; }

						// Remember field type
						$form_field_type_lookup[$ws_form_field->id] = $list_field['type'];

						// Get field ID
						$list_field_id = isset($list_field['id']) ? $list_field['id'] : false;

						// Store for field repair
						if($list_field_id !== false) {

							$ws_form_form->new_lookup['field'][$list_field_id] = $ws_form_field->id;
						}

						// Read field
						$field = $ws_form_field->db_read();

						// Add to field mapping arrays
						$no_map = isset($list_field['no_map']) && $list_field['no_map'];
						if(!$no_map) {

							$field_mapping_action[] = array('ws_form_field' => $ws_form_field->id, 'action_' . $action_id . '_list_fields' => $list_field['id']);
							$field_mapping_populate[] = array('ws_form_field' => $ws_form_field->id, 'form_populate_list_fields' => $list_field['id']);
						}

						// Set label (Use sub if defined)
						$field->label = $list_field['label_field'];

						// Set sort_index
						$field->sort_index = $list_field['sort_index'];

						// Set meta - Required
						if(isset($list_field['required'])) {

							$field->meta->required = ($list_field['required'] ? 'on' : '');
						}

						// Set meta - Default value
						if(isset($list_field['default_value'])) {

							$field->meta->default_value = $list_field['default_value'];
						}

						// Set meta - Input Mask
						if(isset($list_field['input_mask']) && ($list_field['input_mask'] !== false)) {

							$field->meta->input_mask = $list_field['input_mask'];
						}

						// Set meta - Placeholder
						if(isset($list_field['placeholder']) && ($list_field['placeholder'] !== false)) {

							$field->meta->placeholder = $list_field['placeholder'];
						}

						// Set meta - Pattern
						if(isset($list_field['pattern']) && ($list_field['pattern'] !== false)) {

							$field->meta->pattern = $list_field['pattern'];
						}

						// Set meta - Help
						if(isset($list_field['help'])) {
			
							$field->meta->help = $list_field['help'];
						}

						// Set width
						if(isset($list_field['width_factor'])) {

							$width_factor = floatval($list_field['width_factor']);

							if(
								($width_factor > 0) &&
								($width_factor <= 1)
							) {

								$breakpoint_meta_value = round($framework_column_count * $width_factor);

								// Set column width
								$field->meta->{$breakpoint_meta_key} = $breakpoint_meta_value;
							}
						}

						// Action specific meta data
						if(isset($list_field['meta']) && ($list_field['meta'] !== false)) {

							foreach($list_field['meta'] as $meta_key => $meta_value) {

								// Set meta data
								$field->meta->{$meta_key} = $meta_value;
							}
						}

						// Update
						$ws_form_field->db_update_from_object($field, false);

						// Save to lookup
						$no_map = isset($list_field['no_map']) && $list_field['no_map'];
						if(!$no_map) { $form_field_id_lookup[$list_field['id']] = $field->id; }
						$form_field_id_lookup_all[$list_field['id']] = $field->id;
					}

					// Add repeatable icons?
					$section_repeatable = isset($ws_form_section->meta['section_repeatable']) ? $ws_form_section->meta['section_repeatable'] : false;
					if(!empty($section_repeatable)) {

						// Create field
						$ws_form_field = new WS_Form_Field();
						$ws_form_field->form_id = $form_id;
						$ws_form_field->section_id = $section_id;
						$ws_form_field->type = 'section_icons';
						$ws_form_field->meta['section_repeatable_section_id'] = $section_id;
						$ws_form_field->db_create();
					}
				}
			}

			// If we created more than one field in the previous step, add another for the final part of the form
			if(
				($section_count == 0) ||
				($section_count > 1)
			) {

				// Create new section
				$ws_form_section = new WS_Form_Section();
				$ws_form_section->form_id = $form_id;
				$ws_form_section->group_id = $group_id;
				$section_id = $ws_form_section->db_create();
			}

			// Create tag categories
			if(method_exists($action, 'get_tag_categories') && method_exists($action, 'get_tags')) {

				// Build columns
				$data_grid_columns = array(

					array('id' => 0, 'label' => __('Value', 'ws-form')),
					array('id' => 1, 'label' => __('Label', 'ws-form'))
				);

				$tag_categories = $action->get_tag_categories(true);

				foreach($tag_categories as $tag_category) {

					// Get tag category ID
					$tag_category_id = $tag_category['id'];

					// Get tag category label
					$tag_category_label = $tag_category['label'];
					if(empty($tag_category_label)) { $tag_category_label = $tag_category_id; }

					// Get tag category type
					$tag_category_type = $tag_category['type'];

					$tags = $action->get_tags($tag_category_id, true);
					if(count($tags) == 0) { continue; }

					// Build data grid data
					$data_grid_rows = array();
					$tag_index = 1;
					foreach($tags as $tag) {

						// Get tag ID
						$tag_id = $tag['id'];

						// Get tag label
						$tag_label = $tag['label'];
						if(empty($tag_label)) { $tag_label = $tag_id; }

						$data_grid_rows[] = array(

							'id'		=> $tag_index,
							'data'		=> array($tag_id, $tag_label)
						);

						$tag_index++;
					}

					// Build category label full
					$tag_category_label_full = (isset($action->tag_category_label_prefix) ? $action->tag_category_label_prefix : '') . $tag_category_label;

					// Create tag category field
					$update_form_field_return = self::update_form_field($form_id, $section_id, $tag_category_type, $tag_category_label_full);

					$ws_form_field = $update_form_field_return['ws_form_field'];
					$field = $update_form_field_return['field'];

					// Update checkbox columns
					$field->meta->{'data_grid_' . $tag_category_type}->columns = $data_grid_columns;

					// Update [type]_field_label meta_key
					$field->meta->{$tag_category_type . '_field_label'} = 1;	// Column index 1 = $tag['label']

					// Update [type]_field_parse_variable meta_key
					$field->meta->{$tag_category_type . '_field_parse_variable'} = 1;	// Column index 1 = $tag['label']

					// Update label render
					$field->meta->label_render = 'on';

					// Update checkbox rows
					$field->meta->{'data_grid_' . $tag_category_type}->groups[0]->rows = $data_grid_rows;

					// Data source
					$data_source = isset($tag_category['data_source']) ? $tag_category['data_source'] : false;
					if(
						($data_source !== false) &&
						(isset($data_source['id']))
					) {

						// Get data source ID
						$data_source_id = $data_source['id'];

						// Set field data source ID
						$field->meta->{'data_source_id'} = $data_source_id;

						// Get data source base meta
						$meta = WS_Form_Data_Source::get_data_source_meta($data_source_id);
						foreach($meta as $meta_key => $meta_value) {

							$field->meta->{$meta_key} = $meta_value;
						}

						// Get data source action meta
						$data_source_meta = isset($data_source['meta']) ? $data_source['meta'] : false;
						if($data_source_meta !== false) {

							foreach($data_source_meta as $meta_key => $meta_value) {

								$field->meta->{$meta_key} = $meta_value;
							}
						}
					}

					$ws_form_field->db_update_from_object($field, false);

					// Remember for tag mapping
					$tag_mapping_action[] = array('ws_form_field' => $ws_form_field->id, 'action_' . $action->id . '_tag_category_id' => $tag_category['id']);
					$tag_mapping_populate[] = array('ws_form_field' => $ws_form_field->id);	// , 'action_' . $action->id . '_tag_category_id' => $tag_category['id']
				}
			}

			// Get add form fields
			if(
				($form_fields === false) &&
				method_exists($action, 'get_fields')
			) {

				$form_fields = $action->get_fields();
			}

			if(is_array($form_fields)) {

				foreach($form_fields as $form_field_id => $form_field_config) {

					// Read field data
					$form_field_type = $form_field_config['type'];
					$form_field_label = isset($form_field_config['label']) ? $form_field_config['label'] : false;
					$form_field_width_factor = isset($form_field_config['width_factor']) ? $form_field_config['width_factor'] : false;
					$form_field_meta = isset($form_field_config['meta']) ? $form_field_config['meta'] : false;

					// Add field
					$update_form_field_return = self::update_form_field($form_id, $section_id, $form_field_type, $form_field_label, $form_field_width_factor, $form_field_meta);

					// Save to lookup
					$form_field_id_lookup[$form_field_id] = $update_form_field_return['id'];
					$form_field_id_lookup_all[$form_field_id] = $update_form_field_return['id'];
					$form_field_type_lookup[$form_field_id] = $form_field_config['type'];
				}
			}

			// Get meta keys
			$meta_keys = WS_Form_Config::get_meta_keys();

			// Get add form actions
			$meta_action = $meta_keys['action']['default'];

			if(
				($form_actions === false) &&
				method_exists($action, 'get_actions')
			) {

				$form_actions = $action->get_actions($form_field_id_lookup_all, $form_field_type_lookup);
			}

			if(is_array($form_actions)) {

				$form_action_index = 1;

				foreach($form_actions as $form_action_id => $form_action_config) {

					if(is_numeric($form_action_id)) {

						$form_action_id = $form_action_config;
						$form_action_config = array();
					}

					// Add meta
					$form_action_meta = isset($form_action_config['meta']) ? $form_action_config['meta'] : array();

					// Parse meta
					$form_action_meta = self::get_action_parse($form_action_meta, $field_mapping_action, $tag_mapping_action, $form_field_id_lookup, $form_field_id_lookup_all);

					// Add action
					$meta_action['groups'][0]['rows'][] = self::update_form_action($form_action_index++, $form_action_id, $form_action_meta, $form_action_config);
				}
			}

			// Form meta
			$meta = array(

				// Actions
				'action' => $meta_action,
				// Auto populate
				'form_populate_action_id' => $action_id,
			);

			// Set populate list ID
			$action_get_require_list_id = isset($action->get_require_list_id) ? $action->get_require_list_id : true;
			if($action_get_require_list_id) {

				$meta['form_populate_list_id'] = $list_id;
			}

			// Set populate field mapping
			$action_get_require_field_mapping = isset($action->get_require_field_mapping) ? $action->get_require_field_mapping : true;
			if($action_get_require_field_mapping) {

				$meta['form_populate_field_mapping'] = $field_mapping_populate;
			}

			// Get form meta
			if(
				($form_meta === false) &&
				method_exists($action, 'get_meta')
			) {
				$form_meta = $action->get_meta($form_field_id_lookup_all, $form_field_type_lookup);
			}

			if(is_array($form_meta)) {

				$meta = array_merge($meta, $form_meta);
			}

			// Form meta - Tagging
			if(method_exists($action, 'get_tag_categories') && method_exists($action, 'get_tags')) {

				$meta['form_populate_tag_mapping'] = $tag_mapping_populate;
			}

			// Update form meta
			$ws_form_meta = new WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $form_id;
			$ws_form_meta->db_update_from_array($meta);

			// Fix data - Meta
			$ws_form_form->db_meta_repair();

			// Set checksum
			$ws_form_form->db_checksum();

			return true;
		}

		public static function get_action_parse($form_action_meta, $field_mapping_action, $tag_mapping_action, $form_field_id_lookup, $form_field_id_lookup_all) {

			foreach($form_action_meta as $form_action_meta_key => $form_action_meta_value) {

				// Check for nested arrays
				if(is_array($form_action_meta_value)) {

					$form_action_meta[$form_action_meta_key] = self::get_action_parse($form_action_meta_value, $field_mapping_action, $tag_mapping_action, $form_field_id_lookup, $form_field_id_lookup_all);
					continue;
				}

				if(!is_string($form_action_meta_value)) { continue; }

				switch($form_action_meta_value) {

					case 'field_mapping' :

						$form_action_meta[$form_action_meta_key] = $field_mapping_action;
						break;

					case 'tag_mapping' :

						$form_action_meta[$form_action_meta_key] = $tag_mapping_action;
						break;

					default :

						// Direct replacements
						if(isset($form_field_id_lookup[$form_action_meta_value])) {

							$form_action_meta[$form_action_meta_key] = $form_field_id_lookup[$form_action_meta_value];
						}

						// #action_field_id replacements e.g. for #field(#action_field_id)
						foreach($form_field_id_lookup_all as $meta_key => $meta_value) {

							// Avoid conflicts with WS Form variables (e.g. #submit_admin_url)
							if(strpos($form_action_meta_value, '#' . $meta_key . '_') !== false) {

								break;
							}

							// Replace
							if(strpos($form_action_meta_value, '#' . $meta_key) !== false) {

								$form_action_meta_value = $form_action_meta[$form_action_meta_key] = str_replace('#' . $meta_key, $form_field_id_lookup_all[$meta_key], $form_action_meta_value);
							}
						}
				}
			}

			return $form_action_meta;
		}

		public static function update_form_field($form_id, $section_id, $field_type, $label = false, $width_factor = false, $meta = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Create reset button
			$ws_form_field = new WS_Form_Field();
			$ws_form_field->form_id = $form_id;
			$ws_form_field->section_id = $section_id;
			$ws_form_field->type = $field_type;
			$ws_form_field->db_create();

			// Read field
			$field_object = $ws_form_field->db_read();

			// Label
			if(!empty($label)) { $field_object->label = $label; }

			// Width
			if($width_factor !== false) {

				// Get framework info and calculate breakpoint meta key and value for 50% width
				$framework_id = WS_Form_Common::option_get('framework');
				$framework_column_count = WS_Form_Common::option_get('framework_column_count');
				$frameworks = WS_Form_Config::get_frameworks();
				$framework_breakpoints = $frameworks['types'][$framework_id]['breakpoints'];
				reset($framework_breakpoints);
				$breakpoint_first = key($framework_breakpoints);
				$breakpoint_meta_key = 'breakpoint_size_' . $breakpoint_first;
				$breakpoint_meta_value = round($framework_column_count * $width_factor);

				// Set column width
				$field_object->meta->{$breakpoint_meta_key} = $breakpoint_meta_value;
			}

			// Meta data
			if($meta !== false) {

				foreach($meta as $meta_key => $meta_value) {

					$field_object->meta->{$meta_key} = $meta_value;
				}
			}

			// Update
			$ws_form_field->db_update_from_object($field_object, false);

			return(array('id' => $ws_form_field->id, 'ws_form_field' => $ws_form_field, 'field' => $field_object));
		}

		public static function update_form_action($row_id, $action_id, $action_meta_lookups = array(), $form_action_config = array()) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check action is installed
			if(!isset(self::$actions[$action_id])) { return false; }

			// Get action
			$action = self::$actions[$action_id];

			// Build action meta
			$action_meta = array();
			if(method_exists($action, 'config_meta_keys')) {

				$config_meta_keys = $action->config_meta_keys();

				foreach($config_meta_keys as $meta_key => $config_meta_key) {

					if(isset($action_meta_lookups[$meta_key])) {

						$meta_value = $action_meta_lookups[$meta_key];

					} else {

						$meta_value = isset($config_meta_key['default']) ? $config_meta_key['default'] : '';
					}

					$action_meta[$meta_key] = $meta_value;
				}
			}

			// Build action row
			$action_row = array(

				'id'		=> $row_id,
				'disabled'	=> (isset($form_action_config['disabled']) ? $form_action_config['disabled'] : false) ? 'on' : '',
				'data'		=> array(

					$action->label_action,
					wp_json_encode(

						array(

							'id'		=>	$action_id,
							'meta'		=>	$action_meta,
							'events'	=>	$action->events
						)
					)
				)
			);

			return $action_row;
		}

		public static function update_form_conditional($row_id, $conditional, $form_field_id_lookup = array()) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Pre-process conditional
			foreach($conditional['conditional'] as $key => $parts) {

				// If
				foreach($parts as $index => $part) {

					if(isset($part['conditions'])) {

						foreach($part['conditions'] as $condition_index => $condition) {

							// Object ID lookup
							if(isset($condition['object_id'])) {

								if(isset($form_field_id_lookup[$condition['object_id']])) {

									$conditional['conditional'][$key][$index]['conditions'][$condition_index]['object_id'] = $form_field_id_lookup[$condition['object_id']];
								}
							}

							// Value lookup
							if(isset($condition['value'])) {

								if(isset($form_field_id_lookup[$condition['value']])) {

									$conditional['conditional'][$key][$index]['conditions'][$condition_index]['value'] = $form_field_id_lookup[$condition['value']];
								}
							}
						}

					} else {

						// Object ID lookup
						if(isset($part['object_id'])) {

							if(isset($form_field_id_lookup[$part['object_id']])) {

								$conditional['conditional'][$key][$index]['object_id'] = $form_field_id_lookup[$part['object_id']];
							}
						}

						// Value lookup
						if(isset($part['value'])) {

							if(isset($form_field_id_lookup[$part['value']])) {

								$conditional['conditional'][$key][$index]['value'] = $form_field_id_lookup[$part['value']];
							}
						}
					}
				}
			}

			// Build conditional row
			$conditional_row = array(

				'id'		=> $row_id,
				'disabled'	=> '',
				'data'		=> array(

					$conditional['label'],
					wp_json_encode($conditional['conditional'])
				)
			);

			return $conditional_row;
		}

		public static function get_svg($action_id, $list_id, $label, $field_count, $record_count, $field_label = false, $record_label = false) {

			// SVG defaults
			$svg_width = 140;
			$svg_height = 180;

			if($field_label === false) { $field_label = __('Fields', 'ws-form'); }
			if($record_label === false) { $record_label = __('Records', 'ws-form'); }

			if(WS_Form_Common::styler_enabled()) {

				// Colors
				$color_form_background = WS_Form_Color::get_color_base_contrast();
				$color_default = WS_Form_Color::get_color_base();
				$color_default_inverted = WS_Form_Color::get_color_base_contrast();
				$color_information = WS_Form_Color::get_color_info();

			} else {

				// Colors
				$color_form_background = WS_Form_Common::option_get('skin_color_form_background');
				if($color_form_background == '') { $color_form_background = '#ffffff'; }

				$color_default = WS_Form_Common::option_get('skin_color_default');
				$color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted');
				$color_information = WS_Form_Common::option_get('skin_color_information');
			}

			$svg = sprintf('<svg class="wsf-responsive" viewBox="0 0 %u %u">', esc_attr($svg_width), esc_attr($svg_height));
			$svg .= sprintf('<rect height="100%%" width="100%%" fill="%s"/>', esc_attr($color_form_background));
			$svg .= sprintf('<text fill="%s" class="wsf-template-title"><tspan x="%u" y="16">%s</tspan></text>', $color_default, (is_rtl() ? esc_attr($svg_width - 5) : 5), esc_html($label));

			$svg .= self::$actions[$action_id]->get_svg_logo_color($list_id);

			$svg .= '<text id="stats" class="wsf-template-stats">';

			$ypos = 161;

			// Field count
			if($field_count !== false) {

				$svg .= sprintf('<tspan x="%u" y="%u" fill="%s">%s: <tspan class="wsf-template-stat-number" fill="%s">%u</tspan></tspan>', (is_rtl() ? ($svg_width - 5) : 5), esc_attr($ypos), esc_attr($color_default), esc_attr($field_label), esc_attr($color_information), number_format($field_count));
			}

			$ypos = $ypos + 12;

			// Record count
			if($record_count !== false) {

				$svg .= sprintf('<tspan x="%u" y="%u" fill="%s">%s: <tspan class="wsf-template-stat-number" fill="%s">%u</tspan></tspan>', (is_rtl() ? ($svg_width - 5) : 5), esc_attr($ypos), esc_attr($color_default), esc_attr($record_label), esc_attr($color_information), number_format($record_count));
			}

			$svg .= '</text>';

			$svg .= '</svg>';

			return $svg;
		}

		public static function get_submit_value($submit, $submit_field, $default_value = '', $protected = false) {

			if(!isset($submit->meta)) { return $default_value; }
			if(!isset($submit->meta[$submit_field]) && !isset($submit->meta_protected[$submit_field])) { return $default_value; }

			if(isset($submit->meta[$submit_field])) {

				if(is_array($submit->meta[$submit_field])) {

					return (isset($submit->meta[$submit_field]['value'])) ? $submit->meta[$submit_field]['value'] : $default_value;

				} else {

					return $submit->meta[$submit_field];
				}

			} else if($protected && isset($submit->meta_protected[$submit_field])) {

				if(is_array($submit->meta_protected[$submit_field])) {

					return (isset($submit->meta_protected[$submit_field]['value'])) ? $submit->meta_protected[$submit_field]['value'] : $default_value;

				} else {

					return $submit->meta_protected[$submit_field];
				}

			} else {

				return $default_value;
			}
		}

		public static function get_submit_value_repeatable($submit, $submit_field, $default_value = '', $protected = false) {

			// Default return array
			$return_array_repeatable = false;
			$return_array_repeatable_index = array();
			$return_array_value = $default_value;
			$return_array_value_set = false;
			$return_array = array(

				'repeatable' => $return_array_repeatable,
				'repeatable_index' => $return_array_repeatable_index,
				'value' => array($return_array_value)
			);

			// Check meta data exists
			if(
				!isset($submit->meta) ||
				(!isset($submit->meta[$submit_field]) && !isset($submit->meta_protected[$submit_field]))

			) { return $return_array; }

			// Get section_id on meta data (This is only set if a field is in a repeatable section)
			if(
				(isset($submit->meta[$submit_field]['section_id']) && (absint($submit->meta[$submit_field]['section_id']) > 0)) ||
				(isset($submit->meta_protected[$submit_field]['section_id']) && (absint($submit->meta_protected[$submit_field]['section_id']) > 0))
			) {

				// Get repeatable section ID
				$section_id = isset($submit->meta[$submit_field]['section_id']) ? absint($submit->meta[$submit_field]['section_id']) : absint($submit->meta_protected[$submit_field]['section_id']);

				// Check for section repeatable array
				$section_repeatable = false;
				if(isset($submit->section_repeatable)) {

					if(
						!is_array($submit->section_repeatable) &&
						($submit->section_repeatable != '')
					) {
						$section_repeatable = is_serialized($submit->section_repeatable) ? unserialize($submit->section_repeatable) : false;
					} else {

						$section_repeatable = $submit->section_repeatable;
					}
				}

				// If repeatable data found, read values in as an array
				if(
					isset($section_repeatable['section_' . $section_id]) &&
					isset($section_repeatable['section_' . $section_id]['index']) &&
					is_array($section_repeatable['section_' . $section_id]['index'])
				) {

					$section_repeatable_index = $section_repeatable['section_' . $section_id]['index'];

					// Set return array as repeatable
					$return_array_repeatable = true;
					$return_array_repeatable_index = $section_repeatable_index;
					$return_array_value = array();

					foreach($section_repeatable_index as $index) {

						$submit_field_indexed = $submit_field . '_' . $index;

						$return_array_value[] = self::get_submit_value($submit, $submit_field_indexed, $default_value, $protected);

						$return_array_value_set = true;
					}
				}
			}

			// If this is not a repeatable field, process as normal
			if(!$return_array_value_set) {

				$return_array_value = array(self::get_submit_value($submit, $submit_field, $default_value, $protected));
			}

			$return_array = array(

				'repeatable' => $return_array_repeatable,
				'repeatable_index' => $return_array_repeatable_index,
				'value' => $return_array_value
			);

			return $return_array;
		}


		public static function get_submit_type($submit, $submit_field, $default_type, $protected = false) {

			if(!isset($submit->meta)) return $default_type;
			if(!isset($submit->meta[$submit_field]) && !isset($submit->meta_protected[$submit_field])) return $default_type;

			if(isset($submit->meta[$submit_field])) {

				if(is_array($submit->meta[$submit_field])) {

					return (isset($submit->meta[$submit_field]['type'])) ? $submit->meta[$submit_field]['type'] : $default_type;

				} else {

					return $submit->meta[$submit_field];
				}

			} else if($protected && isset($submit->meta_protected[$submit_field])) {

				if(is_array($submit->meta_protected[$submit_field])) {

					return (isset($submit->meta_protected[$submit_field]['type'])) ? $submit->meta_protected[$submit_field]['type'] : $default_type;

				} else {

					return $submit->meta_protected[$submit_field];
				}

			} else {

				return $default_type;
			}
		}

		// Get form ID
		public static function api_get_form_id() {

			return absint(WS_Form_Common::get_query_var_nonce('form_id', 0));
		}

		// Get submit ID
		public static function api_get_submit_id() {

			return absint(WS_Form_Common::get_query_var_nonce('submit_id', 0));
		}

		// Get submit action index
		public static function api_get_submit_action_index() {

			return absint(WS_Form_Common::get_query_var_nonce('submit_action_index', 0));
		}

		// Get config
		public function get_action_config() {

			// Build config
			$config = array(

				'id'		=>	$this->id,
				'meta'		=>	array(),
				'events'	=>	$this->events,
				'label'		=>	$this->label,
				'priority'	=>	$this->priority,
				'row_index'	=>	0
			);

			// Build action meta
			$action_meta = array();
			if(method_exists($this, 'config_meta_keys')) {

				$config_meta_keys = $this->config_meta_keys();

				foreach($config_meta_keys as $meta_key => $config_meta_key) {

					$meta_value = isset($config_meta_key['default']) ? $config_meta_key['default'] : '';

					$config['meta'][$meta_key] = $meta_value;
				}
			}

			return $config;
		}
	}
