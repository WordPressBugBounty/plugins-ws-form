<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Submit_Note extends WS_Form_Core {

		public $id;
		public $submit_id;
		public $user_id;
		public $user_name;
		public $date_added;
		public $date_updated;
		public $content;
		public $meta;
		public $locked;

		public $table_name;

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->submit_id = 0;
			$this->user_id = 0;
			$this->user_name = '';
			$this->date_added = '';
			$this->date_updated = '';
			$this->content = '';
			$this->meta = array(

				'values' => array(),
				'buttons' => array()
			);
			$this->locked = false;

			$this->table_name = sprintf('%s%ssubmit_note', $wpdb->prefix, WS_FORM_DB_TABLE_PREFIX);
		}

		// Create note
		public function db_create($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('edit_submission', $bypass_user_capability_check);

			self::db_check_submit_id();

			global $wpdb;

			$date = WS_Form_Common::get_mysql_date();
			$this->meta = self::meta_normalize($this->meta);
			$meta_string = self::meta_encode($this->meta);

			// user_name only applies when there is no WordPress user
			$user_id = absint($this->user_id);
			$user_name = ($user_id > 0) ? '' : sanitize_text_field((string) $this->user_name);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom database table
			$insert_result = $wpdb->insert(
				$this->table_name,
				array(
					'submit_id' => $this->submit_id,
					'user_id' => $user_id,
					'user_name' => $user_name,
					'date_added' => $date,
					'date_updated' => $date,
					'content' => (string) $this->content,
					'meta' => $meta_string,
					'locked' => ($this->locked ? 1 : 0),
				),
				array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
			);

			if($insert_result === false) {

				parent::db_wpdb_handle_error(__('Error adding submit note', 'ws-form'));
			}

			$this->id = $wpdb->insert_id;
			$this->user_id = $user_id;
			$this->user_name = $user_name;
			$this->date_added = $date;
			$this->date_updated = $date;

			return $this->id;
		}

		// Helper for actions / system notes
		// $user_name is used when $user_id is 0 (third-party / system label); ignored when $user_id > 0
		// $meta is { values, buttons }
		public static function add($submit_id, $content = '', $meta = array(), $user_id = 0, $locked = false, $bypass_user_capability_check = true, $user_name = '') {

			$ws_form_submit_note = new self();
			$ws_form_submit_note->submit_id = absint($submit_id);
			$ws_form_submit_note->content = (string) $content;
			$ws_form_submit_note->meta = self::meta_normalize($meta);
			$ws_form_submit_note->user_id = absint($user_id);
			$ws_form_submit_note->user_name = (string) $user_name;
			$ws_form_submit_note->locked = (bool) $locked;

			return $ws_form_submit_note->db_create($bypass_user_capability_check);
		}

		// Queue a note on a submit object until it has an ID
		public static function add_to_submit($submit, $content = '', $meta = array(), $user_id = 0, $locked = false, $user_name = '') {

			$submit_id = (isset($submit->id) ? absint($submit->id) : 0);

			if($submit_id > 0) {

				return self::add($submit_id, $content, $meta, $user_id, $locked, true, $user_name);
			}

			if(!isset($submit->submit_notes_pending) || !is_array($submit->submit_notes_pending)) {

				$submit->submit_notes_pending = array();
			}

			$submit->submit_notes_pending[] = array(

				'content' => (string) $content,
				'meta' => is_array($meta) ? $meta : array(),
				'user_id' => absint($user_id),
				'user_name' => (string) $user_name,
				'locked' => (bool) $locked
			);

			return true;
		}

		// Flush pending notes after submit create
		public static function db_flush_pending($submit) {

			if(
				!isset($submit->id) ||
				(absint($submit->id) === 0) ||
				!isset($submit->submit_notes_pending) ||
				!is_array($submit->submit_notes_pending) ||
				(count($submit->submit_notes_pending) === 0)
			) {
				return;
			}

			foreach($submit->submit_notes_pending as $note) {

				self::add(
					$submit->id,
					isset($note['content']) ? $note['content'] : '',
					isset($note['meta']) ? $note['meta'] : array(),
					isset($note['user_id']) ? $note['user_id'] : 0,
					isset($note['locked']) ? $note['locked'] : false,
					true,
					isset($note['user_name']) ? $note['user_name'] : ''
				);
			}

			$submit->submit_notes_pending = array();
		}

		// Read single note
		public function db_read($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('read_submission', $bypass_user_capability_check);

			self::db_check_id();

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$row = $wpdb->get_row($wpdb->prepare(

				"SELECT id, submit_id, user_id, user_name, date_added, date_updated, content, meta, locked FROM {$wpdb->prefix}wsf_submit_note WHERE id = %d LIMIT 1;",
				$this->id
			), OBJECT);

			if(is_null($row)) {

				parent::db_throw_error(__('Unable to read submit note', 'ws-form'));
			}

			$this->id = absint($row->id);
			$this->submit_id = absint($row->submit_id);
			$this->user_id = absint($row->user_id);
			$this->user_name = isset($row->user_name) ? (string) $row->user_name : '';
			$this->date_added = $row->date_added;
			$this->date_updated = $row->date_updated;
			$this->content = $row->content;
			$this->meta = self::meta_decode($row->meta);
			$this->locked = !empty($row->locked);

			return self::format_note($this);
		}

		// Read all notes for a submit
		public function db_read_by_submit($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('read_submission', $bypass_user_capability_check);

			self::db_check_submit_id();

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$rows = $wpdb->get_results($wpdb->prepare(

				"SELECT id, submit_id, user_id, user_name, date_added, date_updated, content, meta, locked FROM {$wpdb->prefix}wsf_submit_note WHERE submit_id = %d ORDER BY date_added ASC, id ASC;",
				$this->submit_id
			), OBJECT);

			$notes = array();

			if(is_array($rows)) {

				foreach($rows as $row) {

					$note = new self();
					$note->id = absint($row->id);
					$note->submit_id = absint($row->submit_id);
					$note->user_id = absint($row->user_id);
					$note->user_name = isset($row->user_name) ? (string) $row->user_name : '';
					$note->date_added = $row->date_added;
					$note->date_updated = $row->date_updated;
					$note->content = $row->content;
					$note->meta = self::meta_decode($row->meta);
					$note->locked = !empty($row->locked);

					$notes[] = self::format_note($note);
				}
			}

			return $notes;
		}

		// Update note
		public function db_update($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('edit_submission', $bypass_user_capability_check);

			self::db_check_id();
			self::db_check_submit_id();
			self::db_check_not_locked();

			global $wpdb;

			$date = WS_Form_Common::get_mysql_date();
			$this->meta = self::meta_normalize($this->meta);
			$meta_string = self::meta_encode($this->meta);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$update_result = $wpdb->update(
				$this->table_name,
				array(
					'date_updated' => $date,
					'content' => (string) $this->content,
					'meta' => $meta_string,
				),
				array(
					'id' => $this->id,
					'submit_id' => $this->submit_id,
				),
				array('%s', '%s', '%s'),
				array('%d', '%d')
			);

			if($update_result === false) {

				parent::db_wpdb_handle_error(__('Error updating submit note', 'ws-form'));
			}

			$this->date_updated = $date;

			return true;
		}

		// Delete note
		public function db_delete($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('edit_submission', $bypass_user_capability_check);

			self::db_check_id();
			self::db_check_not_locked();

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$delete_result = $wpdb->delete(
				$this->table_name,
				array('id' => $this->id),
				array('%d')
			);

			if($delete_result === false) {

				parent::db_wpdb_handle_error(__('Error deleting submit note', 'ws-form'));
			}

			return true;
		}

		// Delete all notes for a submit
		public function db_delete_by_submit($bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('edit_submission', $bypass_user_capability_check);

			self::db_check_submit_id();

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$delete_result = $wpdb->delete(
				$this->table_name,
				array('submit_id' => $this->submit_id),
				array('%d')
			);

			if($delete_result === false) {

				parent::db_wpdb_handle_error(__('Error deleting submit notes', 'ws-form'));
			}

			return true;
		}

		// Migrate legacy AbuseIPDB submit meta into a system note
		public static function migrate_abuseipdb_meta($submit_object) {

			if(
				!is_object($submit_object) ||
				!isset($submit_object->id) ||
				(absint($submit_object->id) === 0) ||
				!isset($submit_object->meta) ||
				!is_array($submit_object->meta) ||
				!isset($submit_object->meta['abuseipdb_checked']) ||
				($submit_object->meta['abuseipdb_checked'] === '') ||
				($submit_object->meta['abuseipdb_checked'] === false)
			) {
				return false;
			}

			$submit_meta = $submit_object->meta;

			$ip = isset($submit_meta['abuseipdb_ip']) ? (string) $submit_meta['abuseipdb_ip'] : '';
			$score = isset($submit_meta['abuseipdb_score']) ? (string) $submit_meta['abuseipdb_score'] : '';
			$country = isset($submit_meta['abuseipdb_country']) ? (string) $submit_meta['abuseipdb_country'] : '';
			$domain = isset($submit_meta['abuseipdb_domain']) ? (string) $submit_meta['abuseipdb_domain'] : '';
			$total_reports = isset($submit_meta['abuseipdb_total_reports']) ? (string) $submit_meta['abuseipdb_total_reports'] : '';
			$reason = isset($submit_meta['abuseipdb_reason']) ? (string) $submit_meta['abuseipdb_reason'] : '';
			$reported = (isset($submit_meta['abuseipdb_reported']) && ((string) $submit_meta['abuseipdb_reported'] === '1'));

			$values = array();

			if($ip !== '') { $values[__('IP Address', 'ws-form')] = $ip; }
			if($score !== '') { $values[__('Abuse Confidence Score', 'ws-form')] = $score; }
			if($country !== '') { $values[__('Country', 'ws-form')] = $country; }
			if($domain !== '') { $values[__('Domain', 'ws-form')] = $domain; }
			if($total_reports !== '') { $values[__('Total Reports', 'ws-form')] = $total_reports; }

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

			if($reason !== '') {

				$content = sprintf(

					/* translators: %1$s: IP address, %2$s: Abuse confidence score, %3$s: Reason */
					__('Flagged IP %1$s (score: %2$s, reason: %3$s).', 'ws-form'),
					$ip,
					$score,
					$reason
				);

			} else {

				$content = sprintf(

					/* translators: %1$s: IP address, %2$s: Abuse confidence score */
					__('Checked IP %1$s (score: %2$s).', 'ws-form'),
					$ip,
					$score
				);
			}

			try {

				self::add($submit_object->id, $content, array('values' => $values), 0, true, true, 'AbuseIPDB');

			} catch(Exception $e) {

				return false;
			}

			// Remove legacy meta keys
			$meta_keys = array(
				'abuseipdb_checked',
				'abuseipdb_ip',
				'abuseipdb_score',
				'abuseipdb_country',
				'abuseipdb_domain',
				'abuseipdb_total_reports',
				'abuseipdb_reason',
				'abuseipdb_reported'
			);

			global $wpdb;

			foreach($meta_keys as $meta_key) {

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
				$wpdb->delete(
					"{$wpdb->prefix}wsf_submit_meta",
					array(
						'parent_id' => absint($submit_object->id),
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key' => $meta_key,
					),
					array('%d', '%s')
				);

				unset($submit_object->meta[$meta_key]);
			}

			return true;
		}

		// Format note for API / UI
		public static function format_note($note) {

			$user_id = absint($note->user_id);
			$user_name = isset($note->user_name) ? sanitize_text_field((string) $note->user_name) : '';
			$user_display_name = '';

			if($user_id > 0) {

				// WordPress user takes precedence; user_name is ignored
				$user = get_user_by('ID', $user_id);
				if($user !== false) {

					$user_display_name = $user->display_name;
				}

			} else {

				$user_display_name = $user_name;
			}

			$date_added_wp = '';
			$date_updated_wp = '';

			if(!empty($note->date_added)) {

				$date_added_wp = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($note->date_added)));
			}

			if(!empty($note->date_updated)) {

				$date_updated_wp = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($note->date_updated)));
			}

			return (object) array(

				'id' => absint($note->id),
				'submit_id' => absint($note->submit_id),
				'user_id' => $user_id,
				'user_name' => ($user_id > 0) ? '' : $user_name,
				'user_display_name' => $user_display_name,
				'date_added' => $note->date_added,
				'date_added_wp' => $date_added_wp,
				'date_updated' => $note->date_updated,
				'date_updated_wp' => $date_updated_wp,
				'content' => (string) $note->content,
				'meta' => self::meta_normalize(is_array($note->meta) ? $note->meta : array()),
				'locked' => !empty($note->locked)
			);
		}

		// Normalize meta shape { values, buttons }
		public static function meta_normalize($meta = array()) {

			if(is_object($meta)) {

				$meta = (array) $meta;
			}

			if(!is_array($meta)) {

				$meta = array();
			}

			$values = isset($meta['values']) ? $meta['values'] : array();
			if(is_object($values)) {

				$values = (array) $values;
			}

			$buttons = isset($meta['buttons']) ? $meta['buttons'] : array();
			if(is_object($buttons)) {

				$buttons = (array) $buttons;
			}

			return array(

				'values' => self::meta_values_sanitize(is_array($values) ? $values : array()),
				'buttons' => self::meta_buttons_sanitize(is_array($buttons) ? $buttons : array())
			);
		}

		// Sanitize note meta values (label => value)
		public static function meta_values_sanitize($values) {

			if(!is_array($values)) {

				return array();
			}

			$return_array = array();

			foreach($values as $label => $value) {

				if(is_int($label)) {

					continue;
				}

				$label = sanitize_text_field((string) $label);
				if($label === '') {

					continue;
				}

				if(is_array($value) || is_object($value)) {

					$encoded = wp_json_encode($value);
					$value = ($encoded === false) ? '' : $encoded;

				} else {

					$value = (string) $value;
				}

				$return_array[$label] = $value;
			}

			return $return_array;
		}

		// Sanitize note meta buttons
		public static function meta_buttons_sanitize($buttons) {

			if(!is_array($buttons)) {

				return array();
			}

			$return_array = array();

			foreach($buttons as $button) {

				if(is_object($button)) {

					$button = (array) $button;
				}

				if(!is_array($button)) {

					continue;
				}

				$url = isset($button['url']) ? esc_url_raw((string) $button['url']) : '';
				$label = isset($button['label']) ? sanitize_text_field((string) $button['label']) : '';

				if(($url === '') || ($label === '')) {

					continue;
				}

				$type = isset($button['type']) ? sanitize_text_field((string) $button['type']) : 'primary';
				if(($type !== 'primary') && ($type !== 'secondary')) {

					$type = 'primary';
				}

				$target = isset($button['target']) ? sanitize_text_field((string) $button['target']) : '';

				$button_sanitized = array(

					'url' => $url,
					'label' => $label,
					'type' => $type
				);

				if($target !== '') {

					$button_sanitized['target'] = $target;
				}

				$return_array[] = $button_sanitized;
			}

			return $return_array;
		}

		// Encode meta for DB column
		public static function meta_encode($meta) {

			$meta = self::meta_normalize($meta);

			if((count($meta['values']) === 0) && (count($meta['buttons']) === 0)) {

				return '';
			}

			$encoded = wp_json_encode($meta);

			return ($encoded === false) ? '' : $encoded;
		}

		// Decode meta from DB column
		public static function meta_decode($meta_string) {

			if(($meta_string === '') || ($meta_string === null)) {

				return array(

					'values' => array(),
					'buttons' => array()
				);
			}

			$decoded = json_decode($meta_string, true);

			return self::meta_normalize(is_array($decoded) ? $decoded : array());
		}

		// Checks
		public function db_check_id() {

			if(absint($this->id) === 0) {

				parent::db_throw_error(__('Submit note ID not set', 'ws-form'));
			}
		}

		public function db_check_submit_id() {

			if(absint($this->submit_id) === 0) {

				parent::db_throw_error(__('Submit ID not set', 'ws-form'));
			}
		}

		public function db_check_not_locked() {

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$locked = $wpdb->get_var($wpdb->prepare(

				"SELECT locked FROM {$wpdb->prefix}wsf_submit_note WHERE id = %d LIMIT 1;",
				$this->id
			));

			if(!empty($locked)) {

				parent::db_throw_error(__('This note is locked and cannot be edited or deleted.', 'ws-form'));
			}
		}
	}
