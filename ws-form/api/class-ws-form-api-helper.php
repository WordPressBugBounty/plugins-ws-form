<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

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
			WS_Form_Common::echo_esc_css($ws_form_css->get_layout(null, false));

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
			if(!WS_Form_Common::check_submit_hash($hash)) { wp_die(esc_html__('Hash not specified', 'ws-form')); }

			// Get field ID
			$field_id = absint(WS_Form_Common::get_query_var_nonce('field_id', '', $parameters));
			if($field_id == 0) { wp_die(esc_html__('Field ID not specified', 'ws-form')); }

			// Get section repeatable index
			$section_repeatable_index = absint(WS_Form_Common::get_query_var_nonce('section_repeatable_index', '', $parameters));

			// Get file index
			$file_index = absint(WS_Form_Common::get_query_var_nonce('file_index', '', $parameters));
			if($file_index < 0) { wp_die(esc_html__('File index invalid', 'ws-form')); }

			// Get submit record
			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->hash = $hash;

			try {

				$submit = $ws_form_submit->db_read_by_hash(true, false, false);

			} catch (Exception $e) {

				wp_die(esc_html($e->getMessage()));
			}

			// Get field
			$meta_key_suffix = (($section_repeatable_index > 0) ? ('_' . $section_repeatable_index) : '');
			if(!isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix])) { self::api_throw_error(esc_html__('Field ID not found', 'ws-form')); }
			$field = $submit->meta[WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix];

			// Get files
			if(!isset($field['value'])) { wp_die(esc_html__('Field data not found', 'ws-form')); }
			$files = $field['value'];

			// Get file
			if(!isset($files[$file_index])) { wp_die(esc_html__('Field data not found', 'ws-form')); }
			$file = $files[$file_index];

			// Get file name
			if(!isset($file['name'])) { wp_die(esc_html__('File name not found', 'ws-form')); }
			$file_name = $file['name'];

			// Get file type
			if(!isset($file['type'])) { wp_die(esc_html__('File type not found', 'ws-form')); }
			$file_type = $file['type'];

			// Get file path
			if(!isset($file['path'])) { wp_die(esc_html__('File path not found', 'ws-form')); }
			$file_path = $file['path'];

			// Get base upload_dir
			$upload_dir = wp_upload_dir()['basedir'];

			// Build file path
			$file_path_full = $upload_dir . '/' . $file_path;

			// Prevent path traversal: ensure the resolved file path stays within the uploads directory.
			// realpath() resolves '..' and symlinks and returns false if the file does not exist.
			$upload_dir_real = realpath($upload_dir);
			$file_path_real = realpath($file_path_full);
			if(
				($upload_dir_real === false) ||
				($file_path_real === false) ||
				(strpos($file_path_real, $upload_dir_real . DIRECTORY_SEPARATOR) !== 0)
			) {
				wp_die(esc_html__('File not found', 'ws-form'));
			}

			// Set HTTP headers (strip CR/LF to prevent header injection via stored file metadata)
			header('Content-Type: ' . str_replace(array("\r", "\n"), '', $file_type));

			// Make browser download file instead of viewing it
			if(WS_Form_Common::get_query_var_nonce('download', '', $parameters) !== '') {

				header("Content-Transfer-Encoding: Binary"); 
				header('Content-disposition: attachment; filename="' . str_replace(array("\r", "\n", '"'), '', $file_name) . '"');
			}

			// Clear output buffer
			if(ob_get_length()) { ob_clean(); }

			// Push file to browser
			WS_Form_File::readfile($file_path_real);

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

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter hook
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

			// Prevent browsers/CDNs caching this GET (stale 0 after mark-as-read)
			self::api_no_cache();

			$ws_form_form = new WS_Form_Form();

			try {

				$count_submit_unread_total = $ws_form_form->db_get_count_submit_unread_total();

			} catch (Exception $e) {

				parent::api_throw_error($e->getMessage());
			}

			return array('count_submit_unread_total' => $count_submit_unread_total);
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