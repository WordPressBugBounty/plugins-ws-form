<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_API_Submit_Note extends WS_Form_API {

		// API - GET (list)
		public function api_get($parameters) {

			$ws_form_submit_note = new WS_Form_Submit_Note();
			$ws_form_submit_note->submit_id = self::api_get_submit_id($parameters);

			try {

				$notes = $ws_form_submit_note->db_read_by_submit();

				self::api_json_response($notes);

			} catch(Exception $e) {

				self::api_throw_error($e->getMessage());
			}
		}

		// API - POST (create)
		public function api_post($parameters) {

			$ws_form_submit_note = new WS_Form_Submit_Note();
			$ws_form_submit_note->submit_id = self::api_get_submit_id($parameters);
			$ws_form_submit_note->user_id = get_current_user_id();
			$ws_form_submit_note->content = self::api_get_content($parameters);
			$ws_form_submit_note->meta = self::api_get_meta($parameters);

			try {

				$ws_form_submit_note->db_create();
				$note = $ws_form_submit_note->db_read();

				self::api_json_response($note);

			} catch(Exception $e) {

				self::api_throw_error($e->getMessage());
			}
		}

		// API - PUT (update)
		public function api_put($parameters) {

			$ws_form_submit_note = new WS_Form_Submit_Note();
			$ws_form_submit_note->id = self::api_get_note_id($parameters);
			$ws_form_submit_note->submit_id = self::api_get_submit_id($parameters);

			try {

				// Ensure note belongs to submit
				$existing = $ws_form_submit_note->db_read();
				if(absint($existing->submit_id) !== absint($ws_form_submit_note->submit_id)) {

					self::api_throw_error(__('Submit note not found', 'ws-form'));
				}

				$ws_form_submit_note->content = self::api_get_content($parameters);
				$ws_form_submit_note->meta = self::api_get_meta($parameters, $existing->meta);
				$ws_form_submit_note->db_update();

				$note = $ws_form_submit_note->db_read();

				self::api_json_response($note);

			} catch(Exception $e) {

				self::api_throw_error($e->getMessage());
			}
		}

		// API - DELETE
		public function api_delete($parameters) {

			$ws_form_submit_note = new WS_Form_Submit_Note();
			$ws_form_submit_note->id = self::api_get_note_id($parameters);
			$ws_form_submit_note->submit_id = self::api_get_submit_id($parameters);

			try {

				// Ensure note belongs to submit
				$existing = $ws_form_submit_note->db_read();
				if(absint($existing->submit_id) !== absint($ws_form_submit_note->submit_id)) {

					self::api_throw_error(__('Submit note not found', 'ws-form'));
				}

				$ws_form_submit_note->db_delete();

				self::api_json_response(array('id' => absint($ws_form_submit_note->id)));

			} catch(Exception $e) {

				self::api_throw_error($e->getMessage());
			}
		}

		// Get submit ID
		public function api_get_submit_id($parameters) {

			return absint(WS_Form_Common::get_query_var_nonce('submit_id', 0, $parameters));
		}

		// Get note ID
		public function api_get_note_id($parameters) {

			return absint(WS_Form_Common::get_query_var_nonce('note_id', 0, $parameters));
		}

		// Get content
		public function api_get_content($parameters) {

			$content = WS_Form_Common::get_query_var_nonce('content', '', $parameters);

			return is_string($content) ? $content : '';
		}

		// Get note meta { values, buttons }
		public function api_get_meta($parameters, $default = array()) {

			$meta = WS_Form_Common::get_query_var_nonce('meta', false, $parameters);

			if($meta === false) {

				return WS_Form_Submit_Note::meta_normalize(is_array($default) ? $default : array());
			}

			return WS_Form_Submit_Note::meta_normalize(self::api_parse_array_param($meta));
		}

		// Parse array/object/JSON string param
		public function api_parse_array_param($value) {

			if(is_string($value)) {

				$decoded = json_decode($value, true);
				return is_array($decoded) ? $decoded : array();
			}

			if(is_object($value)) {

				$value = (array) $value;
			}

			return is_array($value) ? $value : array();
		}
	}
