<?php

	class WS_Form_API_Helper extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - Detect framework
		public function api_framework_detect($parameters) {

			// Get file path if provided
			$path = WS_Form_Common::get_query_var_nonce('path', '', $parameters);

			// Get framework auto detect configuration
			$frameworks = WS_Form_Config::get_frameworks(false);
			if(!isset($frameworks['auto_detect'])) { self::api_framework_detect_error(); }

			$auto_detect = $frameworks['auto_detect'];

			// Get framework type lookups
			$types = (isset($auto_detect['types'])) ? $auto_detect['types'] : false;
			if($types === false) { self::api_framework_detect_error(); }

			// Get framework filename exclusions
			$exclude_filenames = (isset($auto_detect['exclude_filenames'])) ? $auto_detect['exclude_filenames'] : false;

			// Get framework filename inclusions
			$include_filenames = (isset($auto_detect['include_filenames'])) ? $auto_detect['include_filenames'] : false;

			// Pass cookies
			$cookies = array();
			foreach($_COOKIE as $name => $value) {

				$cookies[] = new WP_Http_Cookie(array('name' => $name, 'value' => $value));
			}

			// Build URL
			$url = site_url($path);
			if(!$url) { return false; }

			// Build args
			$args = array(

				'headers' => array(

					'X-WP-Nonce' => wp_create_nonce('wp_rest'),
				),

				'user-agent'	=> WS_Form_Common::get_request_user_agent(),
				'timeout'		=> WS_Form_Common::get_request_timeout(),
				'sslverify'		=> WS_Form_Common::get_request_sslverify(),
				'cookies'		=> $cookies
			);

			// Make HTTP request to get URL
			$wp_remote_get_response = wp_remote_get($url, $args);

			if(is_wp_error($wp_remote_get_response)) { self::api_framework_detect_error(); }

			// Read body response
			$http_body = wp_remote_retrieve_body($wp_remote_get_response); // use the content
			if($http_body == '') { self::api_framework_detect_error(); }
			if((strpos($http_body, 'css') === false) && (strpos($http_body, 'CSS') === false)) { self::api_framework_detect_error(); }

			// Start DOM document
			$dom_doc = new DOMDocument();

			// Load HTML into DOM document (diseregard parse errors)
			libxml_use_internal_errors(true);
			if(!$dom_doc->loadHTML($http_body)) { self::api_framework_detect_error(); }
			libxml_use_internal_errors(false);

			// Look for link tags
			$links = $dom_doc->getElementsByTagName('link');
			foreach($links as $link) {

				// Look for rel attributes
				if(strtolower($link->getAttribute('rel')) != "stylesheet") { continue; }

				// Get href attribute
				$url = $link->getAttribute('href');

				// Do we recognize the file name?
				$exclude = false;
				if($exclude_filenames !== false) {

					foreach($exclude_filenames as $exclude_filename) {

						if(strpos($url, $exclude_filename) !== false) { $exclude = true; break; }
					}
				}
				if($include_filenames !== false) {

					foreach($include_filenames as $include_filename) {

						if(strpos($url, $include_filename) !== false) { $exclude = false; break; }
					}
				}
				if($exclude) { continue; }

				// Request CSS document
				$wp_remote_get_response = wp_remote_get($url, $args);

				// Check for error
				if(is_wp_error($wp_remote_get_response)) { continue; }

				// Load response body into string
				$css_body = wp_remote_retrieve_body($wp_remote_get_response);

				// Run through each framework type
				foreach($types as $type => $type_strings) {

					$lookup_strings_found = true;

					// Run through each string to find in the framework
					foreach($type_strings as $type_string) {

						// Look for element in CSS body (Case sensitive)
						if(strpos($css_body, $type_string) === false) {

							$lookup_strings_found = false;
							break;
						}
					}

					// If all strings are found, return that framework
					if($lookup_strings_found) {

						// Return framework data
						$return_array = array();
						$return_array['type'] = $type;
						$return_array['framework'] = $frameworks['types'][$type];
						self::api_json_response($return_array, 0, false);
					}
				}
			}

			// Unable to find a framework
			self::api_framework_detect_error();
		}

		// API - Detect framework - Error
		public function api_framework_detect_error() {

			// Return framework data
			$return_array = array();
			$return_array['type'] = false;
			$return_array['framework'] = false;
			self::api_json_response($return_array, 0, false);
		}


		// API - Push setup
		public function api_setup_push($parameters) {

			// Get framework
			$framework = WS_Form_Common::get_query_var_nonce('framework', '', $parameters);
			if($framework == '') { self::api_throw_error(__('Framework not specified', 'ws-form')); }

			// Check framework
			$frameworks = WS_Form_Config::get_frameworks(false);
			if(!isset($frameworks['types'][$framework])) { self::api_throw_error(__('Invalid framework specified', 'ws-form')); }

			// Get mode
			$mode = WS_Form_Common::get_query_var_nonce('mode', '', $parameters);
			if($mode == '') { $mode = WS_FORM_DEFAULT_MODE; }

			// Check mode
			$modes = explode(',', WS_FORM_MODES);
			if(!in_array($mode, $modes)) { self::api_throw_error(__('Invalid mode specified', 'ws-form')); }

			// Set framework
			WS_Form_Common::option_set('framework', $framework);

			// Set mode
			WS_Form_Common::option_set('mode', $mode);

			// Configure settings according to mode selected
			$options = WS_Form_Config::get_options(false);
			foreach($options as $tab => $data) {

				if(isset($data['fields'])) {

					$fields = $data['fields'];
				}

				if(isset($data['groups'])) {

					$groups = $data['groups'];

					foreach($groups as $group) {

						$fields = $group['fields'];

						self::api_set_push_options($mode, $fields);
					}
				}
			}

			// Set setup (true = complete)
			WS_Form_Common::option_set('setup', true);

			// Success
			self::api_json_response([], 0, false);
		}

		// API - Push setup - Set options
		public function api_set_push_options($mode, $fields) {

			foreach($fields as $key => $attributes) {

				if(
					isset($attributes['type']) && 
					($attributes['type'] != 'static') && 
					isset($attributes['mode']) &&
					isset($attributes['mode'][$mode])
				) {

					$value = $attributes['mode'][$mode];

					WS_Form_Common::option_set($key, $value);
				}
			}
		}

		// API - Support contact submit
		public function api_support_contact_submit() {

			// Read support inquiry fields
			$data = array(

				'contact_first_name'	=> WS_Form_Common::get_query_var_nonce('contact_first_name'),
				'contact_last_name'		=> WS_Form_Common::get_query_var_nonce('contact_last_name'),
				'contact_email'			=> WS_Form_Common::get_query_var_nonce('contact_email'),
				'contact_inquiry'		=> WS_Form_Common::get_query_var_nonce('contact_inquiry')
			);

			// Push form
			$contact_push_form = WS_Form_Common::get_query_var_nonce('contact_push_form');
			$form_id = absint(WS_Form_Common::get_query_var_nonce('id'));
			if($contact_push_form && ($form_id > 0)) {

				// Create form file attachment
				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $form_id;

				try {

					// Get form
					$form_object = $ws_form_form->db_read(true, true);

				} catch (Exception $e) {

					parent::api_throw_error($e->getMessage());
				}

				// Clean form
				unset($form_object->checksum);
				unset($form_object->published_checksum);

				// Stamp form data
				$form_object->identifier = WS_FORM_IDENTIFIER;
				$form_object->version = WS_FORM_VERSION;
				$form_object->time = time();

				// Add checksum
				$form_object->checksum = md5(wp_json_encode($form_object));

				$form_json = wp_json_encode($form_object);

				// Add to data
				$data['contact_form'] = $form_json;
			}

			// Push system
			$contact_push_system = WS_Form_Common::get_query_var_nonce('contact_push_system');
			if($contact_push_system) {

				// Add to data
				$data['contact_system'] = wp_json_encode(WS_Form_Config::get_system());
			}

			// Build URL
			$url = 'https://wsform.com/plugin-support/contact.php';

			// Build args
			$args = array(

				'body'			=> http_build_query($data),
				'user-agent'	=> WS_Form_Common::get_request_user_agent(),
				'timeout'		=> WS_Form_Common::get_request_timeout(),
				'sslverify'		=> WS_Form_Common::get_request_sslverify(),
			);

			// Call using Wordpress wp_remote_post
			$wp_remote_post_response = wp_remote_post($url, $args);

			// Check for error
			if($api_response_error = is_wp_error($wp_remote_post_response)) {

				// Handle error
				$api_response_error_message = $wp_remote_post_response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				// Handle response
				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($wp_remote_post_response);
				$api_response_body = wp_remote_retrieve_body($wp_remote_post_response);
				$api_response_http_code = wp_remote_retrieve_response_code($wp_remote_post_response);
			}

			// Return response
			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code);
		}

		// API - Deactivate feedback submit
		public function api_deactivate_feedback_submit() {

			// Read support inquiry fields
			$data = array(

				'feedback_reason'						=> WS_Form_Common::get_query_var_nonce('feedback_reason'),
				'feedback_reason_error'					=> WS_Form_Common::get_query_var_nonce('feedback_reason_error'),
				'feedback_reason_found_better_plugin'	=> WS_Form_Common::get_query_var_nonce('feedback_reason_found_better_plugin'),
				'feedback_reason_other'					=> WS_Form_Common::get_query_var_nonce('feedback_reason_other')
			);

			// Build URL
			$url = 'https://wsform.com/plugin-support/deactivate_feedback.php';

			// Build args
			$args = array(

				'body'			=> http_build_query($data),
				'user-agent'	=> WS_Form_Common::get_request_user_agent(),
				'timeout'		=> WS_Form_Common::get_request_timeout(),
				'sslverify'		=> WS_Form_Common::get_request_sslverify(),
			);

			// Call using Wordpress wp_remote_post
			$wp_remote_post_response = wp_remote_post($url, $args);

			// Check for error
			if($api_response_error = is_wp_error($wp_remote_post_response)) {

				// Handle error
				$api_response_error_message = $wp_remote_post_response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				// Handle response
				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($wp_remote_post_response);
				$api_response_body = wp_remote_retrieve_body($wp_remote_post_response);
				$api_response_http_code = wp_remote_retrieve_response_code($wp_remote_post_response);
			}

			// Return response
			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code);
		}

		// API - WS Form Admin CSS
		public function api_ws_form_css_admin() {

			// Output HTTP header
			parent::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			WS_Form_Common::echo_esc_css($ws_form_css->get_admin());
			exit;
		}

		// API - WS Form Layout CSS
		public function api_ws_form_css() {

			// Output HTTP header
			parent::api_css_header();

			// Check for block editor
			if(WS_Form_Common::is_block_editor()) {

				// Force framework to be ws-form
				add_filter('wsf_option_get', array('WS_Form_Common', 'option_get_framework_ws_form'), 10, 2);
			}

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			WS_Form_Common::echo_esc_css($ws_form_css->get_layout(null, false, is_rtl()));

			exit;
		}

		// API - WS Form Skin CSS
		public function api_ws_form_css_skin() {

			// Output HTTP header
			parent::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			WS_Form_Common::echo_esc_css($ws_form_css->get_skin(null, false, is_rtl()));

			exit;
		}

		// API - WS Form Conversational CSS
		public function api_ws_form_css_conversational() {

			// Output HTTP header
			parent::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			WS_Form_Common::echo_esc_css($ws_form_css->get_conversational(null, false, is_rtl()));

			exit;
		}

		// API - Email CSS
		public function api_css_email() {

			// Output HTTP header
			parent::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			WS_Form_Common::echo_esc_css($ws_form_css->get_email());
			
			exit;
		}

		// API - File download
		public function api_file_download($parameters) {

			// Get submit hash
			$hash = WS_Form_Common::get_query_var_nonce('hash', '', $parameters);
			if(!WS_Form_Common::check_submit_hash($hash)) { wp_die(__('Hash not specified', 'ws-form')); }

			// Get field ID
			$field_id = absint(WS_Form_Common::get_query_var_nonce('field_id', '', $parameters));
			if($field_id == 0) { wp_die(__('Field ID not specified', 'ws-form')); }

			// Get section repeatable index
			$section_repeatable_index = absint(WS_Form_Common::get_query_var_nonce('section_repeatable_index', '', $parameters));

			// Get file index
			$file_index = absint(WS_Form_Common::get_query_var_nonce('file_index', '', $parameters));
			if($file_index < 0) { wp_die(__('File index invalid', 'ws-form')); }

			// Get submit record
			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->hash = $hash;

			try {

				$submit = $ws_form_submit->db_read_by_hash(true, false, false);

			} catch (Exception $e) {

				wp_die($e->getMessage());
			}

			// Get field
			$meta_key_suffix = (($section_repeatable_index > 0) ? ('_' . $section_repeatable_index) : '');
			if(!isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix])) { self::api_throw_error(__('Field ID not found', 'ws-form')); }
			$field = $submit->meta[WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix];

			// Get files
			if(!isset($field['value'])) { wp_die(__('Field data not found', 'ws-form')); }
			$files = $field['value'];

			// Get file
			if(!isset($files[$file_index])) { wp_die(__('Field data not found', 'ws-form')); }
			$file = $files[$file_index];

			// Get file name
			if(!isset($file['name'])) { wp_die(__('File name not found', 'ws-form')); }
			$file_name = $file['name'];

			// Get file type
			if(!isset($file['type'])) { wp_die(__('File type not found', 'ws-form')); }
			$file_type = $file['type'];

			// Get file path
			if(!isset($file['path'])) { wp_die(__('File path not found', 'ws-form')); }
			$file_path = $file['path'];

			// Get base upload_dir
			$upload_dir = wp_upload_dir()['basedir'];

			// Build file path
			$file_path_full = $upload_dir . '/' . $file_path;

			// Check file exists
			if(!file_exists($file_path_full)) {

				wp_die(__('File not found'));
			}

			// Set HTTP headers
			header('Content-Type: ' . $file_type);

			// Make browser download file instead of viewing it
			if(WS_Form_Common::get_query_var_nonce('download', '', $parameters) !== '') {

				header("Content-Transfer-Encoding: Binary"); 
				header("Content-disposition: attachment; filename=\"" . $file_name . "\""); 
			}

			// Clear output buffer
			if(ob_get_length()) { ob_clean(); }

			// Push file to browser
			readfile($file_path_full);

			exit;
		}

		// Hidden columns changed via AJAX request
		public function api_user_meta_hidden_columns($parameters) {

			// Get form ID
			$form_id = absint(WS_Form_Common::get_query_var_nonce('id', '', $parameters));
			if($form_id == 0) { exit; }

			// Get hidden columns
			$form_hidden_columns_string = WS_Form_Common::get_query_var_nonce('hidden', '', $parameters);
			$form_hidden_columns = explode(',', $form_hidden_columns_string);

			// Write hidden columns back to user meta for current form
			update_user_option(get_current_user_id(), 'managews-form_page_ws-form-submitcolumnshidden-' . $form_id, $form_hidden_columns, !is_multisite());

			self::api_json_response();
		}

		// API - Review nag dismiss
		public function api_review_nag_dismiss($parameters) {

			WS_Form_Common::option_set('review_nag', true);

			return array('error' => false);
		}

		// API - Test API is working
		public function api_test($parameters) {

			// REST API test
			wp_set_current_user(0);
			setup_userdata(0);
			$access = apply_filters('rest_authentication_errors', true);

			if(is_wp_error($access)) {

				return array('error' => true, 'error_message' => $access->get_error_message());

			} else {

				return array('error' => false, 'version' => WS_FORM_VERSION, 'edition' => WS_FORM_EDITION, 'license' => WS_Form_Common::get_license_key_obscured());
			}
		}

		// API - System
		public function api_system($parameters) {

			return WS_Form_Config::get_system();
		}

		// Get count submit unread total
		public function api_count_submit_unread($parameters) {

			$ws_form_form = new WS_Form_Form();

			try {

				$count_submit_unread_total = $ws_form_form->db_get_count_submit_unread_total();

			} catch (Exception $e) {

				parent::api_throw_error($e->getMessage());
			}

			return array('count_submit_unread_total' => $count_submit_unread_total);
		}

		// Intro
		public function api_intro($paramters) {

			$hints = [

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Publish', 'ws-form'), __('Once you have finished editing your form, click this button to publish it. Any changes made before publishing can only be seen by you.', 'ws-form')),
					'element' 		=> '[data-action="wsf-publish"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/publishing-forms/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Preview', 'ws-form'), __('Click this to preview your form in your website theme. You can change the template used for previewing in settings.', 'ws-form')),
					'element' 		=> '[data-action="wsf-preview"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/previewing-forms/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Style', 'ws-form'), __('Click this to style your form in your website theme. You can change the style used in the form settings.', 'ws-form')),
					'element' 		=> '[data-action="wsf-style"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/styler/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Submissions', 'ws-form'), __('To view your form submissions, click here. You can edit, export and print submissions.', 'ws-form')),
					'element' 		=> '[data-action="wsf-submission"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/submissions/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Import', 'ws-form'), __('Click this to import a form that you have previously exported. This is useful if you want to transfer a form to another website.', 'ws-form')),
					'element' 		=> '[data-action="wsf-form-upload"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/import-export/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Export', 'ws-form'), __('Click this to export your form. You can use the exported JSON file to move your form to another website.', 'ws-form')),
					'element' 		=> '[data-action="wsf-form-download"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/import-export/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Toolbox', 'ws-form'), __('Drag-and-drop or click a field type to add it to your form. The \'Undo\' tab contains a history of your form edits. You can go back to any step if you make a mistake.', 'ws-form')),
					'element' 		=> '[data-action-sidebar="toolbox"]',
					'sidebar_open' 	=> 'toolbox',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/the-layout-editor/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Conditional Logic', 'ws-form'), __('Upgrade to PRO to use conditional logic and make your form interactive! For example, you could show or hide sections of a form to make it easier to complete.', 'ws-form')),
					'element' 		=> '[data-action-sidebar="conditional"]',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/conditional-logic/')
				],
				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Actions', 'ws-form'), __('Actions run whenever a form is submitted or saved. You can send emails, show messages, redirect to a page, integrate with a CRM and more.', 'ws-form')),
					'element' 		=> '[data-action-sidebar="action"]',
					'sidebar_open' 	=> 'action',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/introduction-actions/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Support', 'ws-form'), __('Need help? Click here to browse and search the knowledge base.', 'ws-form')),
					'element' 		=> '[data-action-sidebar="support"]',
					'sidebar_open' 	=> 'support',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Form Settings', 'ws-form'), __('Form settings include spam settings and duplicate protection. You can also add custom CSS classes and edit the behavior of the form.', 'ws-form')),
					'element' 		=> '[data-action-sidebar="form"]',
					'sidebar_open' 	=> 'form',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/form-settings/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Add Tab', 'ws-form'), __('Click this to add tabs to your form. Use tabs to create multi-step forms. If you only have one tab, your form will be shown without tabs on your website.', 'ws-form')),
					'element' 		=> '.wsf-group-add button',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/tabs/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Add Section', 'ws-form'), __('Click this to add sections to your form. Use sections to break up your form into logic sections.', 'ws-form')),
					'element' 		=> '.wsf-section-add button',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/sections/')
				],

				[
					'hint' 			=> sprintf('<strong>%s</strong><br />%s', __('Breakpoint Selector', 'ws-form'), __('You can create unique form layouts for each screen width. To choose a different screen width, drag the breakpoint selector left or right. If you change a section or field width it will apply to that one breakpoint and all those above it.', 'ws-form')),
					'element' 		=> '#wsf-breakpoints span',
					'button_url'	=> WS_Form_Common::get_plugin_website_url('/knowledgebase/responsive-forms/')
				],
			];

			WS_Form_Common::option_set('intro', false);

			return $hints;
		}

		// API - Styler
		public function api_styler($parameters) {

			// Check supplied debug styler state
			if(
				!isset($parameters['helper_styler']) ||
				!in_array($parameters['helper_styler'], array('off', 'administrator', 'on'))
			) {
				return array('error' => true, 'error_message' => __('Invalid styler state', 'ws-form'));
			}

			// Set styler console state
			WS_Form_Common::option_set('helper_styler', $parameters['helper_styler']);

			return array('error' => false);
		}
	}