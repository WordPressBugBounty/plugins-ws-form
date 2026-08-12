<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Submit_Export extends WS_Form_Core {

		public $ws_form_submit;
		public $form_id;

		const USER_OPTION_COLUMNS_HIDDEN = 'ws_form_submit_export_columns_hidden';
		const USER_OPTION_COLUMNS_ORDER = 'ws_form_submit_export_columns_order';

		public function __construct($form_id = false) {

			// Check form ID
			if(empty($form_id)) {

				throw new Exception(esc_html__('Form ID empty', 'ws-form'));
			}

			// Initial WS_Form_Submit class
			$this->ws_form_submit = new WS_Form_Submit();
			$this->ws_form_submit->form_id = $form_id;

			// Set form ID
			$this->form_id = $form_id;
		}

		// User option key for hidden export columns
		public static function get_columns_hidden_option_name($form_id) {

			return self::USER_OPTION_COLUMNS_HIDDEN . '-' . absint($form_id);
		}

		// User option key for export column order
		public static function get_columns_order_option_name($form_id) {

			return self::USER_OPTION_COLUMNS_ORDER . '-' . absint($form_id);
		}

		// Get export header after wsf_submit_export_csv_header
		public function get_export_header($bypass_user_capability_check = false) {

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$header = apply_filters('wsf_submit_export_csv_header', $this->ws_form_submit->get_keys_all($bypass_user_capability_check), $this->form_id);

			return is_array($header) ? $header : array();
		}

		// Get saved hidden column keys
		public function get_columns_hidden() {

			$columns_hidden = get_user_option(self::get_columns_hidden_option_name($this->form_id));

			return is_array($columns_hidden) ? $columns_hidden : array();
		}

		// Save hidden column keys (denylist)
		public function set_columns_hidden($columns_hidden) {

			$header = self::get_export_header();
			$header_keys = array_map('strval', array_keys($header));

			if(!is_array($columns_hidden)) {

				$columns_hidden = array();
			}

			$columns_hidden = array_values(array_intersect($header_keys, array_map('strval', $columns_hidden)));

			update_user_option(get_current_user_id(), self::get_columns_hidden_option_name($this->form_id), $columns_hidden, !is_multisite());

			return $columns_hidden;
		}

		// Get saved column order
		public function get_columns_order() {

			$columns_order = get_user_option(self::get_columns_order_option_name($this->form_id));

			return is_array($columns_order) ? array_map('strval', $columns_order) : array();
		}

		// Save column order (selected keys in export order)
		public function set_columns_order($columns_order) {

			$header = self::get_export_header();
			$header_keys = array_map('strval', array_keys($header));

			if(!is_array($columns_order)) {

				$columns_order = array();
			}

			$columns_order = array_values(array_unique(array_intersect(array_map('strval', $columns_order), $header_keys)));

			update_user_option(get_current_user_id(), self::get_columns_order_option_name($this->form_id), $columns_order, !is_multisite());

			return $columns_order;
		}

		// Resolved selected column order (saved order, then new columns)
		public function get_columns_order_resolved($bypass_user_capability_check = false) {

			$header = self::get_export_header($bypass_user_capability_check);
			$header_keys = array_map('strval', array_keys($header));
			$columns_hidden = self::get_columns_hidden();
			$checked = array_values(array_diff($header_keys, array_map('strval', $columns_hidden)));
			$ordered = array_values(array_intersect(self::get_columns_order(), $checked));
			$remaining = array_values(array_diff($checked, $ordered));

			return array_merge($ordered, $remaining);
		}

		// Grouped columns for picker UI
		public function get_export_columns_grouped($bypass_user_capability_check = false) {

			$header = self::get_export_header($bypass_user_capability_check);
			$columns_hidden = self::get_columns_hidden();

			$keys_fixed = array_keys($this->ws_form_submit->get_keys_fixed($bypass_user_capability_check));
			$keys_fields = array_keys($this->ws_form_submit->get_keys_fields($bypass_user_capability_check));

			$groups = array(

				'submission' => array(

					'label' => __('Submission', 'ws-form'),
					'columns' => array()
				),
				'fields' => array(

					'label' => __('Form Fields', 'ws-form'),
					'columns' => array()
				),
				'custom' => array(

					'label' => __('Custom', 'ws-form'),
					'columns' => array()
				)
			);

			foreach($header as $key => $label) {

				$key = strval($key);
				$column = array(

					'key' => $key,
					'label' => is_string($label) ? $label : strval($label),
					'checked' => !in_array($key, $columns_hidden, true)
				);

				if(in_array($key, $keys_fixed, true)) {

					$groups['submission']['columns'][] = $column;

				} else if(in_array($key, $keys_fields, true)) {

					// Show field ID in picker (matches migrate / mapping UI)
					if(strpos($key, WS_FORM_FIELD_PREFIX) === 0) {

						$field_id = absint(substr($key, strlen(WS_FORM_FIELD_PREFIX)));
						if($field_id > 0) {

							$column['label'] = sprintf(
								/* translators: %1$s: field label, %2$u: field ID */
								__('%1$s (ID: %2$u)', 'ws-form'),
								$column['label'],
								$field_id
							);
						}
					}

					$groups['fields']['columns'][] = $column;

				} else {

					$groups['custom']['columns'][] = $column;
				}
			}

			// Drop empty groups
			foreach($groups as $group_key => $group) {

				if(empty($group['columns'])) {

					unset($groups[$group_key]);
				}
			}

			return $groups;
		}

		// Resolve requested columns against post-header-filter keys (request order)
		public function resolve_columns($columns, $header = false) {

			if($columns === false) { return false; }

			if($header === false) {

				$header = self::get_export_header();
			}

			if(!is_array($columns)) {

				return array();
			}

			$columns = array_map('strval', $columns);
			$header_keys = array_map('strval', array_keys($header));

			// Preserve request/selection order
			return array_values(array_intersect($columns, $header_keys));
		}

		// Project associative row/header onto selected keys
		public function project_columns($row, $columns) {

			$projected = array();

			foreach($columns as $key) {

				$projected[$key] = isset($row[$key]) ? $row[$key] : '';
			}

			return $projected;
		}

		// Get rows
		public function get_row_by_id($id, $bypass_user_capability_check = false, $clear_hidden_fields = false, $sanitize_rows = true) {

			// User capability check
			WS_Form_Common::user_must('read_submission', $bypass_user_capability_check);

			// Get record from core
			$submit = $this->ws_form_submit->db_read(

				false,										// Get meta
				false,										// Get expanded
				false,										// Bypass user capability check
				$clear_hidden_fields 						// Clear hidden fields
			);

			return self::process_rows(array($submit), $bypass_user_capability_check, $clear_hidden_fields, $sanitize_rows);
		}

		// Get header
		public function get_header($bypass_user_capability_check = false) {

			return $this->ws_form_submit->get_keys_all($bypass_user_capability_check);
		}

		// Get rows
		public function get_rows($limit = false, $offset = 0, $keyword = '', $filters = false, $order_by = 'id', $order = 'DESC', $bypass_user_capability_check = false, $clear_hidden_fields = false, $sanitize_rows = true) {

			// User capability check
			WS_Form_Common::user_must('read_submission', $bypass_user_capability_check);

			// Get records from core
			$submits = $this->ws_form_submit->db_read_all(

				$this->ws_form_submit->get_join($keyword, $order_by, $bypass_user_capability_check),
				$this->ws_form_submit->get_where($filters, $bypass_user_capability_check),
				$this->ws_form_submit->get_group_by(),
				$this->ws_form_submit->get_order_by($order_by, $order, $bypass_user_capability_check),
				self::get_limit($limit),					// Limit
				self::get_offset($offset),					// Offset
				false,										// Get meta
				false,										// Get expanded
				$bypass_user_capability_check,				// Bypass user capability check
				$clear_hidden_fields 						// Clear hidden fields
			);

			// Return processed rows
			return (empty($submits) || !is_array($submits)) ? array() : self::process_rows($submits, $bypass_user_capability_check, $clear_hidden_fields, $sanitize_rows);
		}

		// Process submit rows
		public function process_rows($submits, $bypass_user_capability_check = false, $clear_hidden_fields = false, $sanitize_rows = true) {

			$rows = array();

			// Get keys
			$keys_fixed = $this->ws_form_submit->get_keys_fixed($bypass_user_capability_check);

			// Get field data
			$this->ws_form_submit->db_get_submit_fields($bypass_user_capability_check);

			// Process meta data
			foreach($submits as $key => $submit_object) {

				// Read expanded
				$this->ws_form_submit->db_read_expanded($submit_object, true, true, true, true, true, true, true, $bypass_user_capability_check);

				// Get meta data
				$submit_object->meta = $this->ws_form_submit->db_get_submit_meta($submit_object, false, $bypass_user_capability_check);

				// Clear hidden fields
				if($clear_hidden_fields) {

					$submit_object = $this->ws_form_submit->clear_hidden_meta_values($submit_object);
				}

				// Build CSV row
				$row = array();

				// Fixed fields
				foreach($keys_fixed as $key => $value) {

					switch($key) {

						case 'date_added' :

							$row[$key] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($submit_object->date_added)));
							break;

						case 'date_updated' :

							$row[$key] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($submit_object->date_updated)));
							break;

						case 'user_first_name' :

							$row[$key] = (isset($submit_object->user) && !$bypass_user_capability_check) ? $submit_object->user->first_name : '';
							break;

						case 'user_last_name' :

							$row[$key] = (isset($submit_object->user) && !$bypass_user_capability_check) ? $submit_object->user->last_name : '';
							break;

						case 'user_id' :

							$row[$key] = (isset($submit_object->user_id) && !$bypass_user_capability_check) ? $submit_object->{$key} : 0;
							break;

						case 'id' :
						case 'status' :
						case 'status_full' :
						case 'duration' :

							$row[$key] = isset($submit_object->{$key}) ? $submit_object->{$key} : '';
							break;

						default :

							$row[$key] = isset($submit_object->meta[$key]) ? $submit_object->meta[$key] : '';
					}
				}

				// Form fields
				foreach($this->ws_form_submit->submit_fields as $id => $field) {

					$field_name = WS_FORM_FIELD_PREFIX . $id;

					// Get type
					$type = isset($submit_object->meta[$field_name]) ? (isset($submit_object->meta[$field_name]['type']) ? $submit_object->meta[$field_name]['type'] : '') : '';

					// Get value
					$value = isset($submit_object->meta[$field_name]) ? (isset($submit_object->meta[$field_name]['value']) ? $submit_object->meta[$field_name]['value'] : '') : '';

					// Apply filter
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
					$value = apply_filters('wsf_submit_field_type_csv', $value, $id, $type);

					// Process by type
					switch($type) {

					}

					// Process array values (e.g. Select, Checkbox, Radio field types)
					if(is_array($value)) { $value = implode(',', $value); }

					// Add column
					$row['field_' . $id] = $value;
				}

				// Sanitize row
				if($sanitize_rows) {

					$row = self::sanitize_row($row);
				}

				// Add to rows
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				$rows[] = apply_filters('wsf_submit_export_csv_row', $row, $this->form_id, $submit_object);
			}

			return $rows;
		}

		// Sanitize row
		public function sanitize_row($row) {

			return array_map(function($column) {

				return esc_html($column);

			}, $row);
		}

		// Get record ids
		public function get_ids($limit = false, $offset = 0, $keyword = '', $filters = false, $order_by = 'id', $order = 'DESC', $bypass_user_capability_check = false) {

			// User capability check
			WS_Form_Common::user_must('read_submission', $bypass_user_capability_check);

			// Get records from core
			$ids = $this->ws_form_submit->db_read_ids(

				$this->ws_form_submit->get_join($keyword, $order_by, $bypass_user_capability_check),
				$this->ws_form_submit->get_where($filters, $bypass_user_capability_check),
				$this->ws_form_submit->get_group_by(),
				$this->ws_form_submit->get_order_by($order_by, $order, $bypass_user_capability_check),
				self::get_limit($limit),					// Limit
				self::get_offset($offset),					// Offset
				false,										// Get meta
				false,										// Get expanded
				$bypass_user_capability_check				// Bypass user capability check
			);

			return is_null($ids) ? array() : $ids;
		}

		// Get record count
		public function get_row_count($keyword = '', $filters = false, $bypass_user_capability_check = false) {

			return $this->ws_form_submit->db_read_count(

				$this->ws_form_submit->get_join($keyword, 'id', $bypass_user_capability_check),
				$this->ws_form_submit->get_where($filters, $bypass_user_capability_check),
				$bypass_user_capability_check
			);
		}

		// Check limit
		public function get_limit($limit = false) {

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			return empty($limit) ? absint(apply_filters('wsf_submit_export_page_size', WS_FORM_SUBMIT_EXPORT_PAGE_SIZE)) : $limit;
		}

		// Check offset
		public function get_offset($offset = false) {

			return absint($offset);
		}

		// Get CSV page
		public function get_csv_page(&$file, $page = 0, $keyword = '', $filters = false, $order_by = 'id', $order = 'DESC', $bypass_user_capability_check = false, $clear_hidden_fields = false, $sanitize_rows = true, $columns = false) {

			// User capability check
			WS_Form_Common::user_must('export_submission');

			// Clear hidden fields?
			$clear_hidden_fields = (get_user_meta(get_current_user_id(), 'ws_form_submissions_clear_hidden_fields', true) === 'on');

			// Limit
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$limit = absint(apply_filters('wsf_submit_export_page_size', WS_FORM_SUBMIT_EXPORT_PAGE_SIZE));

			// Offset
			$offset = ($page * $limit);

			// Full header (hook), then optional column selection
			$header = self::get_export_header($bypass_user_capability_check);
			$columns = self::resolve_columns($columns, $header);

			if($columns !== false) {

				if(empty($columns)) {

					throw new Exception(esc_html__('Select at least one column', 'ws-form'));
				}

				$header = self::project_columns($header, $columns);
			}

			// Output header
			if($page === 0) {

				// Sanitize header
				if($sanitize_rows) {

					$header = self::sanitize_row($header);
				}

				$header_keys = array_keys($header);
				$header_values = array_values($header);

				// SYLK: first column key id → write "ID", then remaining labels
				if(isset($header_keys[0]) && ($header_keys[0] === 'id')) {

					fwrite($file, '"ID",'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- File stream
					WS_Form_File::esc_fputcsv($file, array_slice($header_values, 1));

				} else {

					WS_Form_File::esc_fputcsv($file, $header_values);
				}
			}

			// Get records (full rows after wsf_submit_export_csv_row)
			$rows = self::get_rows($limit, $offset, $keyword, $filters, $order_by, $order, $bypass_user_capability_check, $clear_hidden_fields, $sanitize_rows);

			// Process records
			foreach($rows as $row) {

				if($columns !== false) {

					$row = self::project_columns($row, $columns);
				}

				// Write escaped fputcsv
				WS_Form_File::esc_fputcsv($file, $row);
			}

			// Return data
			return array(

				'records_processed' => $offset + (is_null($rows) ? 0 : count($rows)),
				'records_total' => (($page === 0) ? self::get_row_count($keyword, $filters, $bypass_user_capability_check) : false)
			);
		}
	}