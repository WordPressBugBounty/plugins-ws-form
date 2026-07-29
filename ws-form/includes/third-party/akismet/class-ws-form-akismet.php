<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Akismet {

		public $id = 'akismetv1';
		public $label;

		public function __construct() {

			// Add to spam tab in form settings sidebar
			add_filter('wsf_config_settings_form_admin', array($this, 'config_settings_form_admin'), 10, 1);

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			// Spam check during submit setup
			add_filter('wsf_submit_spam_check', array($this, 'submit_spam_check'), 10, 3);

			// Register init action
			add_action('init', array($this, 'init'));
		}

		public function init() {

			// Set label
			$this->label = __('Akismet', 'ws-form');
		}

		public function enabled($form) {

			$enabled = WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_enabled');

			return ($enabled === 'on');
		}

		public function plugin_installed() {

			return class_exists('Akismet');
		}

		public function form_enabled() {

			$key = self::get_key();

			return ($key !== false) && (strlen($key) === 12);
		}

		public function get_key() {

			return is_callable(array('Akismet', 'get_api_key')) ? Akismet::get_api_key() : ((function_exists('akismet_get_key')) ? akismet_get_key() : false);
		}

		public function submit_spam_check($spam_score, $form, $submit) {

			// Get configuration
			$api_key = 				self::get_key();
			$field_email =			WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_field_email');
			$field_mapping =		WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_field_mapping');
			$spam_level_reject =	WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_spam_level_reject', '');
			$admin_no_run =			WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_admin_no_run', 'on');
			$test =					WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_test');

			// Checks
			if(!self::enabled($form)) { return $spam_score; }
			if(($api_key === false) || (strlen($api_key) !== 12)) { return $spam_score; }
			if($admin_no_run && WS_Form_Common::can_user('manage_options_wsform')) { return $spam_score; }

			// Build API endpoint URL
			$api_endpoint = 'https://' . $api_key . '.rest.akismet.com/1.1/';

			// Reset spam level
			$spam_level = 0;

			// Build post request
			$data = array(

				'blog'			=>	get_option('home'),
				'blog_lang'		=>	get_locale(),
				'blog_charset'	=>	get_bloginfo('charset'),
				'user_ip'		=>	WS_Form_Common::get_ip(),
				'user_agent'	=>	WS_Form_Common::get_user_agent(),
				'referrer'		=>	WS_Form_Common::get_referrer(),
				'comment_type'	=>	'contact-form',
			);

			// Build comment_email
			if(
				($field_email != '') &&
				isset($submit->meta) &&
				isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_email]) &&
				isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_email]['value'])
			) {

				$email_address = $submit->meta[WS_FORM_FIELD_PREFIX . $field_email]['value'];
				if(filter_var($email_address, FILTER_VALIDATE_EMAIL)) { $data['comment_author_email'] = $email_address; }
			}

			// Build comment_content
			$comment_content_array = array();
			if(is_array($field_mapping)) {

				foreach($field_mapping as $field_map) {

					$field_id = $field_map->ws_form_field;
					$submit_value = WS_Form_Action::get_submit_value($submit, WS_FORM_FIELD_PREFIX . $field_id, false);
					if($submit_value !== false) {

						$comment_content_array[] = $submit_value;
					}
				}
			}
			if(count($comment_content_array) > 0) {

				$data['commment_content'] = implode("\n", $comment_content_array);
			}

			// Test
			if($test) { $data['is_test'] = true; }

			// Add permalink if available
			if($permalink = get_permalink()) { $data['permalink'] = $permalink; }

			// Build query string
			$query_string = http_build_query($data);

			// POST
			$api_response = $this->api_call($api_endpoint, 'comment-check', 'POST', $query_string, array(), 'text/plain', 'application/x-www-form-urlencoded');

			$result = '';
			$pro_tip = false;

			// Check for X-akismet-pro-tip header
			if(($pro_tip = $this->api_get_header($api_response, 'X-akismet-pro-tip')) !== false) {

				switch($pro_tip) {

					case 'discard' :

						$spam_level = WS_FORM_SPAM_LEVEL_MAX;
						$result = 'spam';
						break;
				}
			}

			// Process response
			if($spam_level == 0) {

				switch($api_response['http_code']) {

					case 200 :

						// Get response string
						$response = trim($api_response['response']);
 						switch($response) {

							// Not spam
							case 'false' :

								$result = 'ham';
								break;

							// Spam
							case 'true' :

								$spam_level = (WS_FORM_SPAM_LEVEL_MAX * 0.75);		// 0.75 Shows up as orange in submit table
								$result = 'spam';
								break;
						}
						break;
				}
			}

			// Reject submission if spam level meets criteria
			$spam_level_reject = absint($spam_level_reject);
			if(($spam_level_reject > 0) && ($spam_level >= $spam_level_reject)) {

				$submit->error_validation_actions[] = array(

					'action'	=>	'message',
					'message'	=>	__('Spam detected', 'ws-form')
				);
			}

			// Persist check as a locked system note
			if($result !== '') {

				if($result === 'spam') {

					if(($pro_tip !== false) && ($pro_tip !== '')) {

						$content = sprintf(

							/* translators: %1$u: Spam score, %2$s: Akismet pro tip */
							__('Flagged submission as spam (score: %1$u, pro tip: %2$s).', 'ws-form'),
							$spam_level,
							$pro_tip
						);

					} else {

						$content = sprintf(

							/* translators: %u: Spam score */
							__('Flagged submission as spam (score: %u).', 'ws-form'),
							$spam_level
						);
					}

				} else {

					$content = sprintf(

						/* translators: %u: Spam score */
						__('Checked submission (score: %u).', 'ws-form'),
						$spam_level
					);
				}

				$values = array(

					__('Result', 'ws-form') => ($result === 'spam') ? __('Spam', 'ws-form') : __('Not spam', 'ws-form'),
					__('Spam Score', 'ws-form') => (string) $spam_level
				);

				if(($pro_tip !== false) && ($pro_tip !== '')) {

					$values[__('Pro Tip', 'ws-form')] = $pro_tip;
				}

				if($test) {

					$values[__('Test Mode', 'ws-form')] = __('Yes', 'ws-form');
				}

				WS_Form_Submit_Note::add_to_submit($submit, $content, array('values' => $values), 0, true, 'Akismet');
			}

			// Return highest spam score
			if(is_null($spam_score) || ($spam_score < $spam_level)) {

				return $spam_level;
			}

			return $spam_score;
		}

		// Add meta keys to spam tab in form settings
		public function config_settings_form_admin($config_settings_form_admin) {

			if(self::plugin_installed() && self::form_enabled()) {

				$fieldset = array(

					'label'		=>	$this->label,
					'kb_url'	=>	'/knowledgebase/spam-check-with-akismet/',
					'meta_keys'	=> array(

						'action_' . $this->id . '_enabled',
						'action_' . $this->id . '_field_email',
						'action_' . $this->id . '_field_mapping',
						'action_' . $this->id . '_spam_level_reject',
						'action_' . $this->id . '_test',
						'action_' . $this->id . '_admin_no_run'
					)
				);

			} else {

				$fieldset = array(

					'label'		=>	$this->label,
					'kb_url'	=>	'/knowledgebase/spam-check-with-akismet/',
					'meta_keys'	=> array(

						'action_' . $this->id . '_not_enabled'
					)
				);
			}

			// Inject
			$config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'] = WS_Form_Common::array_inject_element($config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'], $fieldset, 4);

			return $config_settings_form_admin;
		}

		// Meta keys for this integration
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build instructions
			$instructions_array = array();

			if(!self::plugin_installed()) {

				$instructions_array[] = '<li>' . sprintf(

					/* translators: %s: Akismet plugin installation link */
					__('Install and activate the %s plugin.', 'ws-form'),
					'<a href="https://akismet.com/?utm_source=ws_form" target="_blank">Akismet</a>',
				) . '</li>';

			} else {

				$instructions_array[] = sprintf('<li class="wsf-disabled">%s</li>',  __('Install and activate the Akismet plugin.', 'ws-form'));
			}

			if(!self::form_enabled()) {

				if(!self::plugin_installed()) {

					$instructions_array[] = sprintf('<li>%s</li>', __('Enter your Akismet key.', 'ws-form'));

				} else {

					$instructions_array[] = sprintf('<li><a href="%s">%s</a></li>', get_admin_url(null, 'options-general.php?page=akismet-key-config'), __('Enter your Akismet key.', 'ws-form'));
				}

			} else {

				$instructions_array[] = sprintf('<li class="wsf-disabled">%s</li>', __('Enable protection on this form.', 'ws-form'));
			}

			$instructions = sprintf('<p>%s</p><ol>%s</ol>', __('To enable Akismet on this form:', 'ws-form'), implode('', $instructions_array));

			// Build config_meta_keys
			$config_meta_keys = array(
				
				// Not enable HTML block
				'action_' . $this->id . '_not_enabled' => array(

					'type'						=>	'html',
					'html'						=>	$instructions
				),

				// Enabled
				'action_' . $this->id . '_enabled'	=> array(

					'label'						=>	__('Enabled', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	''
				),

				// Email field
				'action_' . $this->id . '_field_email'	=> array(

					'label'							=>	__('Email Field', 'ws-form'),
					'type'							=>	'select',
					'options'						=>	'fields',
					'options_blank'					=>	__('Select...', 'ws-form'),
					'fields_filter_type'			=>	array('email'),
					'help'							=>	__('Select which field contains the email address of the person submitting the form.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_enabled',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				),

				// Field mapping
				'action_' . $this->id . '_field_mapping'	=> array(

					'label'						=>	__('Fields To Check For Spam', 'ws-form'),
					'type'						=>	'repeater',
					'help'						=>	sprintf(

						/* translators: %s: WS Form */
						__('Select which %s fields Akismet should check for spam.', 'ws-form'),

						WS_FORM_NAME_GENERIC
					),
					'meta_keys'					=>	array(

						'ws_form_field_edit'
					),
					'meta_keys_unique'			=>	array(

						'ws_form_field_edit'
					),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_enabled',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				),

				// List ID
				'action_' . $this->id . '_spam_level_reject'	=> array(

					'label'						=>	__('Settings', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Reject submission if spam level meets this criteria.', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 'text' => __('Use Spam Threshold', 'ws-form')),
						array('value' => '75', 'text' => __('Reject Suspected Spam', 'ws-form')),
						array('value' => '100', 'text' => __('Reject Blatant Spam', 'ws-form')),
					),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_enabled',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				),

				// Administrator
				'action_' . $this->id . '_admin_no_run'	=> array(

					'label'						=>	__('Bypass If Administrator', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('If checked, this check will not run if you are signed in as an administrator.', 'ws-form'),
					'default'					=>	'on',
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_enabled',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				),

				// Test
				'action_' . $this->id . '_test'	=> array(

					'label'						=>	__('Test Mode', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('If checked, Akismet will run in test mode.', 'ws-form'),
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_enabled',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// API call
		private function api_call($endpoint, $path = '', $method = 'GET', $body = null, $headers = array(), $accept = 'application/json', $content_type = 'application/json') {

			if(!is_array($headers)) { $headers = array(); }
			if($accept !== false) { $headers['Accept'] = $accept; }
			if($content_type !== false) { $headers['Content-Type'] = $content_type; }

			$args = array(

				'method'		=> $method,
				'headers'		=> $headers,
				'user-agent'	=> WS_Form_Common::get_request_user_agent(),
				'timeout'		=> WS_Form_Common::get_request_timeout(WS_FORM_API_CALL_TIMEOUT),
				'sslverify'		=> WS_Form_Common::get_request_sslverify(WS_FORM_API_CALL_SSL_VERIFY)
			);

			$url = $endpoint . $path;

			if(
				($body !== null) &&
				($body !== false)
			) {

				switch($method) {

					case 'GET' :

						if(is_object($body)) { $body = (array) $body; }
						if(is_array($body)) {

							$url = WS_Form_Common::wsf_add_query_args($body, $url);
						}
						break;

					default :

						$args['body'] = $body;
				}
			}

			$wp_remote_request_response = wp_remote_request($url, $args);

			if($api_response_error = is_wp_error($wp_remote_request_response)) {

				$api_response_error_message = $wp_remote_request_response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($wp_remote_request_response);
				$api_response_body = wp_remote_retrieve_body($wp_remote_request_response);
				$api_response_http_code = wp_remote_retrieve_response_code($wp_remote_request_response);
			}

			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code, 'headers' => $api_response_headers);
		}

		// Get API call header
		private function api_get_header($response, $header) {

			if(
				!isset($response['headers']) ||
				!isset($response['headers'][$header])
			) { return false; }

			return $response['headers'][$header];
		}
	}

	new WS_Form_Akismet();
