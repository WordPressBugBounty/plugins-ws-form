<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_AbuseIPDB {

		public $id = 'abuseipdbv1';
		public $label;

		const API_ENDPOINT = 'https://api.abuseipdb.com/api/v2/';
		const API_KEY_CONSTANT = 'WSF_ABUSEIPDB_API_KEY';
		const REPORT_CATEGORY_WEB_SPAM = '11';

		public function __construct() {

			// Add to spam tab in form settings sidebar
			add_filter('wsf_config_settings_form_admin', array($this, 'config_settings_form_admin'), 10, 1);

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			// Spam check during submit setup
			add_filter('wsf_submit_spam_check', array($this, 'submit_spam_check'), 10, 3);

			// Two-way reporting when submission is marked as spam
			add_action('wsf_submit_status', array($this, 'submit_status'), 10, 2);

			// Register init action
			add_action('init', array($this, 'init'));
		}

		public function init() {

			// Set label
			$this->label = __('AbuseIPDB', 'ws-form');
		}

		public function should_run($form) {

			if(!self::has_api_key()) { return false; }

			if(self::all_forms_enabled()) { return true; }

			return self::enabled($form);
		}

		public function enabled($form) {

			$enabled = WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_enabled');

			return ($enabled === 'on');
		}

		public function all_forms_enabled() {

			return (bool) WS_Form_Common::option_get('abuseipdb_all_forms', false);
		}

		public function admin_no_run_global() {

			return (bool) WS_Form_Common::option_get('abuseipdb_admin_no_run', true);
		}

		public function has_api_key() {

			$api_key = self::get_api_key();

			return ($api_key !== '');
		}

		public function get_api_key() {

			if(defined(self::API_KEY_CONSTANT)) {

				$api_key = trim((string) constant(self::API_KEY_CONSTANT));

				return ($api_key !== '') ? $api_key : '';
			}

			return trim((string) WS_Form_Common::option_get('abuseipdb_api_key', ''));
		}

		public function submit_spam_check($spam_score, $form, $submit) {

			// Checks
			if(!self::should_run($form)) { return $spam_score; }

			$api_key = self::get_api_key();
			if($api_key === '') { return $spam_score; }

			// Bypass If Administrator (global first; form setting when not all-forms)
			$admin_no_run = self::admin_no_run_global();
			if(!$admin_no_run && !self::all_forms_enabled()) {

				$admin_no_run = WS_Form_Common::get_object_meta_value($form, 'action_' . $this->id . '_admin_no_run', 'on');
			}
			if($admin_no_run && WS_Form_Common::can_user('manage_options_wsform')) { return $spam_score; }

			// Get IP address
			$ip = self::get_request_ip();
			if($ip === '') { return $spam_score; }

			// Settings — blank threshold = use each form’s Spam Threshold via spam_level
			$threshold_option = WS_Form_Common::option_get('abuseipdb_threshold', '');
			$threshold_global = (($threshold_option !== '') && ($threshold_option !== null) && is_numeric($threshold_option));
			$threshold = $threshold_global ? absint($threshold_option) : false;
			if(($threshold !== false) && ($threshold > 100)) { $threshold = 100; }

			$max_age_days = absint(WS_Form_Common::option_get('abuseipdb_max_age_days', 30));
			if($max_age_days < 1) { $max_age_days = 1; }
			if($max_age_days > 365) { $max_age_days = 365; }

			$countries = array_values(array_unique(array_map('strtoupper', self::parse_list(WS_Form_Common::option_get('abuseipdb_countries', '')))));
			$domains = array_values(array_unique(array_map('strtolower', self::parse_list(WS_Form_Common::option_get('abuseipdb_domains', '')))));

			// Call AbuseIPDB check endpoint
			$api_response = $this->api_call(

				self::API_ENDPOINT,
				'check',
				'GET',
				array(
					'ipAddress' => $ip,
					'maxAgeInDays' => $max_age_days
				),
				array('Key' => $api_key),
				'application/json',
				false
			);

			// Fail open on transport / HTTP errors
			if(
				!empty($api_response['error']) ||
				(absint($api_response['http_code']) !== 200)
			) {

				return $spam_score;
			}

			$response_object = json_decode($api_response['response']);
			if(
				!is_object($response_object) ||
				!isset($response_object->data) ||
				!is_object($response_object->data)
			) {

				return $spam_score;
			}

			$data = $response_object->data;

			$score = isset($data->abuseConfidenceScore) ? absint($data->abuseConfidenceScore) : 0;
			$country = isset($data->countryCode) ? strtoupper(sanitize_text_field($data->countryCode)) : '';
			$domain = isset($data->domain) ? strtolower(sanitize_text_field($data->domain)) : '';
			$total_reports = isset($data->totalReports) ? absint($data->totalReports) : 0;

			$reason = '';
			$actions_stopped = false;

			// Persist AbuseIPDB confidence score (real score is also stored on the system note)
			$spam_level = min($score, WS_FORM_SPAM_LEVEL_MAX);

			// Country blocklist
			if(($country !== '') && in_array($country, $countries, true)) {

				$reason = 'country';
				$actions_stopped = true;
				$spam_level = WS_FORM_SPAM_LEVEL_MAX;
			}

			// Domain blocklist
			if(($reason === '') && ($domain !== '') && self::domain_blocked($domain, $domains)) {

				$reason = 'domain';
				$actions_stopped = true;
				$spam_level = WS_FORM_SPAM_LEVEL_MAX;
			}

			// Confidence score (0–100, same scale as form Spam Threshold)
			if($reason === '') {

				if($threshold !== false) {

					// Global threshold set — stop actions on every form, keep real score for display
					if($score >= $threshold) {

						$reason = 'score';
						$actions_stopped = true;
					}

				}
				// Blank global threshold — form Spam Threshold decides using the real score
			}

			// Stop non-database actions (e.g. global threshold or blocklist)
			if($actions_stopped) {

				$submit->actions_stopped = true;
			}

			if($reason !== '') {

				$content = sprintf(

					/* translators: %1$s: IP address, %2$u: Abuse confidence score, %3$s: Reason */
					__('Flagged IP %1$s (score: %2$u, reason: %3$s).', 'ws-form'),
					$ip,
					$score,
					$reason
				);

			} else {

				$content = sprintf(

					/* translators: %1$s: IP address, %2$u: Abuse confidence score */
					__('Checked IP %1$s (score: %2$u).', 'ws-form'),
					$ip,
					$score
				);
			}

			// Persist check as a locked system note
			WS_Form_Submit_Note::add_to_submit($submit, $content, self::build_note_meta($ip, $score, $country, $domain, $total_reports, $reason), 0, true, 'AbuseIPDB');

			// Return highest spam score
			if(is_null($spam_score) || ($spam_score < $spam_level)) {

				return $spam_level;
			}

			return $spam_score;
		}

		// Two-way reporting when an admin marks a submission as spam
		public function submit_status($submit_id, $status) {

			if($status !== 'spam') { return; }

			if(!(bool) WS_Form_Common::option_get('abuseipdb_report', false)) { return; }

			$api_key = self::get_api_key();
			if($api_key === '') { return; }

			$submit_id = absint($submit_id);
			if($submit_id === 0) { return; }

			try {

				$ws_form_submit = new WS_Form_Submit();
				$ws_form_submit->id = $submit_id;
				$submit_object = $ws_form_submit->db_read(true, true);

			} catch(Exception $e) {

				return;
			}

			$notes = (isset($submit_object->notes) && is_array($submit_object->notes)) ? $submit_object->notes : array();

			// Already reported
			if(self::notes_have_reported($notes)) { return; }

			// Resolve IP from notes, then tracking meta
			$ip = self::notes_get_ip($notes);
			if($ip === '') {

				$ip = WS_Form_Action::get_submit_value($submit_object, 'tracking_remote_ip', '');
			}
			$ip = self::sanitize_single_ip($ip);
			if($ip === '') { return; }

			$form_id = isset($submit_object->form_id) ? absint($submit_object->form_id) : 0;

			$comment = sprintf(

				/* translators: %1$u: Form ID, %2$u: Submission ID */
				__('WS Form spam report (form %1$u, submission %2$u)', 'ws-form'),
				$form_id,
				$submit_id
			);

			$body = http_build_query(array(

				'ip' => $ip,
				'categories' => self::REPORT_CATEGORY_WEB_SPAM,
				'comment' => $comment
			));

			$api_response = $this->api_call(

				self::API_ENDPOINT,
				'report',
				'POST',
				$body,
				array('Key' => $api_key),
				'application/json',
				'application/x-www-form-urlencoded'
			);

			if(
				!empty($api_response['error']) ||
				(absint($api_response['http_code']) !== 200)
			) {

				return;
			}

			$content = sprintf(

				/* translators: %s: IP address */
				__('Reported IP %s to AbuseIPDB as web spam.', 'ws-form'),
				$ip
			);

			$meta = array(

				'values' => array(

					__('IP Address', 'ws-form') => $ip,
					__('Reported to AbuseIPDB', 'ws-form') => __('Yes', 'ws-form')
				)
			);

			try {

				WS_Form_Submit_Note::add($submit_id, $content, $meta, 0, true, true, 'AbuseIPDB');

			} catch(Exception $e) {

				return;
			}
		}

		// Build note meta for an AbuseIPDB check
		public static function build_note_meta($ip, $score, $country, $domain, $total_reports, $reason, $reported = false) {

			$values = array();

			if($ip !== '') { $values[__('IP Address', 'ws-form')] = (string) $ip; }
			if($score !== '' && $score !== null) { $values[__('Abuse Confidence Score', 'ws-form')] = (string) $score; }
			if($country !== '') { $values[__('Country', 'ws-form')] = (string) $country; }
			if($domain !== '') { $values[__('Domain', 'ws-form')] = (string) $domain; }
			if($total_reports !== '' && $total_reports !== null) { $values[__('Total Reports', 'ws-form')] = (string) $total_reports; }

			if($reason !== '') {

				switch($reason) {

					case 'score' :

						$reason_label = __('Confidence score', 'ws-form');
						break;

					case 'country' :

						$reason_label = __('Country blocklist', 'ws-form');
						break;

					case 'domain' :

						$reason_label = __('Domain blocklist', 'ws-form');
						break;

					default :

						$reason_label = $reason;
				}

				$values[__('Flagged Reason', 'ws-form')] = $reason_label;
			}

			if($reported) {

				$values[__('Reported to AbuseIPDB', 'ws-form')] = __('Yes', 'ws-form');
			}

			return array(

				'values' => $values
			);
		}

		// Check notes for an AbuseIPDB reported flag
		public static function notes_have_reported($notes) {

			$reported_label = __('Reported to AbuseIPDB', 'ws-form');
			$yes_label = __('Yes', 'ws-form');

			foreach((array) $notes as $note) {

				$note = (object) $note;
				if(!isset($note->meta) || !is_array($note->meta)) { continue; }

				$values = (isset($note->meta['values']) && is_array($note->meta['values'])) ? $note->meta['values'] : array();

				if(
					isset($values[$reported_label]) &&
					((string) $values[$reported_label] === $yes_label)
				) {
					return true;
				}
			}

			return false;
		}

		// Get IP address from AbuseIPDB notes
		public static function notes_get_ip($notes) {

			$ip_label = __('IP Address', 'ws-form');

			foreach((array) $notes as $note) {

				$note = (object) $note;
				if(!isset($note->meta) || !is_array($note->meta)) { continue; }

				$values = (isset($note->meta['values']) && is_array($note->meta['values'])) ? $note->meta['values'] : array();

				if(isset($values[$ip_label]) && ($values[$ip_label] !== '')) {

					return (string) $values[$ip_label];
				}
			}

			return '';
		}

		// Add meta keys to spam tab in form settings
		public function config_settings_form_admin($config_settings_form_admin) {

			if(self::has_api_key()) {

				$fieldset = array(

					'label'		=>	$this->label,
					'kb_url'	=>	'/knowledgebase/abuseipdb/',
					'meta_keys'	=> array(

						'action_' . $this->id . '_enabled',
						'action_' . $this->id . '_admin_no_run'
					)
				);

			} else {

				$fieldset = array(

					'label'		=>	$this->label,
					'kb_url'	=>	'/knowledgebase/abuseipdb/',
					'meta_keys'	=> array(

						'action_' . $this->id . '_not_enabled'
					)
				);
			}

			// Inject after Akismet (index 5 once Akismet has injected at 4)
			$config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'] = WS_Form_Common::array_inject_element($config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'], $fieldset, 5);

			return $config_settings_form_admin;
		}

		// Meta keys for this integration
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			$settings_url = WS_Form_Common::get_admin_url('ws-form-settings', false, 'tab=spam_protection&section=abuseipdb');

			$instructions = sprintf(

				'<p>%s</p><ol><li>%s</li><li>%s</li></ol>',
				esc_html__('To enable AbuseIPDB on this form:', 'ws-form'),
				sprintf(

					/* translators: %s: Settings URL */
					__('Enter your AbuseIPDB API key in <a href="%s">settings</a>.', 'ws-form'),
					esc_url($settings_url)
				),
				esc_html__('Enable protection for this form or all forms.', 'ws-form')
			);

			// Enabled
			$enabled = array(

				'label'						=>	__('Enable', 'ws-form'),
				'type'						=>	'checkbox',
				'default'					=>	'',
				'help'						=>	__('Check submissions against the AbuseIPDB IP reputation database.', 'ws-form')
			);

			if(self::all_forms_enabled()) {

				$enabled['disabled'] = true;
				$enabled['checked'] = true;
				$enabled['help'] = sprintf(

					/* translators: %s: Settings URL */
					__('Enabled on all forms in <a href="%s">Settings</a>.', 'ws-form'),
					esc_url($settings_url)
				);
			}

			// Administrator
			$admin_no_run = array(

				'label'						=>	__('Bypass If Administrator', 'ws-form'),
				'type'						=>	'checkbox',
				'help'						=>	__('If checked, this check will not run if you are signed in as an administrator.', 'ws-form'),
				'default'					=>	'on'
			);

			if(self::admin_no_run_global()) {

				$admin_no_run['disabled'] = true;
				$admin_no_run['checked'] = true;
				$admin_no_run['help'] = sprintf(

					/* translators: %s: Settings URL */
					__('Enabled on all forms in <a href="%s">Settings</a>.', 'ws-form'),
					esc_url($settings_url)
				);

			} else {

				$admin_no_run['condition'] = array(

					array(

						'logic'				=>	'==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'			=>	'action_' . $this->id . '_enabled',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'		=>	'on'
					)
				);
			}

			$config_meta_keys = array(

				// Not enabled HTML block
				'action_' . $this->id . '_not_enabled' => array(

					'type'						=>	'html',
					'html'						=>	$instructions
				),

				// Enabled
				'action_' . $this->id . '_enabled' => $enabled,

				// Administrator
				'action_' . $this->id . '_admin_no_run' => $admin_no_run
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// Get primary request IP
		private function get_request_ip() {

			return self::sanitize_single_ip(WS_Form_Common::get_ip());
		}

		// Sanitize a single IP (supports comma-separated proxy chains)
		private function sanitize_single_ip($ip) {

			$ip = trim((string) $ip);
			if($ip === '') { return ''; }

			$ip_array = explode(',', $ip);
			$ip = trim($ip_array[0]);

			$ip = WS_Form_Common::sanitize_ip_address($ip);

			return ($ip === false) ? '' : $ip;
		}

		// Parse comma / newline separated list
		private function parse_list($value) {

			$value = str_replace(array("\r\n", "\r", "\n", ';'), ',', (string) $value);
			$parts = array_filter(array_map('trim', explode(',', $value)));

			return array_values(array_unique($parts));
		}

		// Domain blocklist match (suffix-aware)
		private function domain_blocked($domain, $blocked_domains) {

			$domain = strtolower($domain);

			foreach($blocked_domains as $blocked_domain) {

				$blocked_domain = strtolower($blocked_domain);
				if($blocked_domain === '') { continue; }

				if($domain === $blocked_domain) { return true; }

				$suffix = '.' . $blocked_domain;
				$suffix_length = strlen($suffix);

				if(
					(strlen($domain) > $suffix_length) &&
					(substr($domain, -$suffix_length) === $suffix)
				) {

					return true;
				}
			}

			return false;
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
	}

	new WS_Form_AbuseIPDB();
