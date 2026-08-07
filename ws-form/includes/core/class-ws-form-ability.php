<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Ability {

		public $input = false; 

		// Register ability categories
		public function register_categories() {

			// Use of wp_register_ability_category() (requires WordPress 6.9+) is already gated: this method is only hooked when abilities_api_enabled() confirms the Abilities API (WP_Ability) is present.
			// We must call it via call_user_func() because Plugin Check provides no way to ignore its WordPress version compatibility errors inline.
			call_user_func(

				'wp_register_ability_category',

				'ws-form',

				array(

					'label' => __('WS Form', 'ws-form'),
					'description' => __('Abilities that relate to the plugin WS Form.', 'ws-form')
				)
			);
		}

		// Register abilities
		public function register() {

			// Get abilities
			$abilities = WS_Form_Config::get_abilities();

			// Register abilities
			foreach($abilities as $ability_name => $ability) {

				// Use of wp_register_ability() (requires WordPress 6.9+) is already gated: this method is only hooked when abilities_api_enabled() confirms the Abilities API (WP_Ability) is present.
				// We must call it via call_user_func() because Plugin Check provides no way to ignore its WordPress version compatibility errors inline.
				$registered_ability = call_user_func(

					'wp_register_ability',

					// Ability
					$ability_name,

					// Args
					[
						'label'               => $ability['label'],
						'description'         => $ability['description'],
						'category'            => $ability['category'],
						'input_schema'        => $ability['input_schema'],
						'output_schema'       => $ability['output_schema'],
						'execute_callback'    => $ability['execute_callback'],
						'permission_callback' => $ability['permission_callback'],
						'meta'                => $ability['meta']
					]
				);
			}
		}

		// Forms
		public function forms($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'forms');

				// Initiate instance of Form class
				$ws_form_form = new WS_Form_Form();

				// Get published (sanitized as boolean, returns only true or false)
				$published = self::input_get('published');

				// Get order by (sanitized by enum, returns only label or id)
				$order_by = self::input_get('order_by');

				// Get order (sanitized by enum, returns only ASC or DESC)
				$order = self::input_get('order');

				// Get forms
				$forms = $ws_form_form->get_all($published, $order_by, $order);
				if(!is_array($forms)) { $forms = array(); }

				// Cast IDs to integers ($wpdb returns numeric columns as strings)
				foreach($forms as $key => $form) {

					if(isset($form['id'])) {

						$forms[$key]['id'] = (int) $form['id'];
					}
				}

				// Return data
				return [

					'forms' => $forms
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving forms: %s', 'ws-form'));
			}
		}

		// Form - Create - JSON
		public function form_create_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-create-json');

				// Get JSON
				$form_json = self::input_get('json');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Create a new form
				$form_id = $ws_form_form_ai->form_create_json($form_json);

				// Return data
				return [

					'id' => $form_id,
					'json' => $ws_form_form_ai->form_get_json(),
					'block' => WS_Form_Common::block_markup($form_id),
					'shortcode' => WS_Form_Common::shortcode($form_id),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error creating form from JSON: %s', 'ws-form'));
			}
		}

		// Form - Get JSON
		public function form_get_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-get-json');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Read the form
				$form_json = $ws_form_form_ai->form_get_json();

				// Return data
				return [

					'id' => $form_id,
					'json' => $form_json
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving form as JSON: %s', 'ws-form'));
			}
		}

		// Form - Update from JSON
		public function form_update_json($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-update-json');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get form JSON
				$form_json = self::input_get('json');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Update the form
				$form_json = $ws_form_form_ai->form_update_json($form_json);

				// Return data
				return [

					'id' => $form_id,
					'json' => $form_json,
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating form from JSON: %s', 'ws-form'));
			}
		}

		// Form - Publish
		public function form_publish($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-publish');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Publish the form
				$ws_form_form->db_publish();

				// Return data
				return [

					'status' => 'publish',
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error publishing form: %s', 'ws-form'));
			}
		}

		// Form - Draft
		public function form_draft($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-draft');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Draft the form
				$ws_form_form->db_draft();

				// Return data
				return [

					'status' => 'draft',
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error drafting form: %s', 'ws-form'));
			}
		}

		// Form - Clone
		public function form_clone($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-clone');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Clone the form
				$form_id = $ws_form_form->db_clone();

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Return data
				return [

					'id' => $form_id,
					'json' => $ws_form_form_ai->form_get_json(),
					'block' => WS_Form_Common::block_markup($form_id),
					'shortcode' => WS_Form_Common::shortcode($form_id),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error cloning form: %s', 'ws-form'));
			}
		}

		// Form - Shortcode
		public function form_shortcode($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-shortcode');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Return data
				return [

					'shortcode' => WS_Form_Common::shortcode($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error generating shortcode: %s', 'ws-form'));
			}
		}

		// Form - Block
		public function form_block($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-block');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Return data
				return [

					'markup' => WS_Form_Common::block_markup($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error generating block: %s', 'ws-form'));
			}
		}

		// Form - Statistics
		public function form_stats($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-stats');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get date / time from
				$date_from = self::date_validate(self::input_get('date_from'), false);
				if($date_from === false) {

					throw new Exception(esc_html__('Invalid from date / time.', 'ws-form'));
				}
				$time_from_utc = empty($date_from) ? false : strtotime(get_gmt_from_date($date_from));

				// Get date / time to
				$date_to = self::date_validate(self::input_get('date_to'), true);
				if($date_to === false) {

					throw new Exception(esc_html__('Invalid to date / time.', 'ws-form'));
				}
				$time_to_utc = empty($date_to) ? false : strtotime(get_gmt_from_date($date_to));

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Read the form
				$form_object = $ws_form_form->db_read();

				// Update statistics for form
				$form_object = $ws_form_form->db_count_update($form_object);

				$return_data = [

					'id' => $form_id,
					'label' => esc_html($form_object->label),
					'status' => esc_html($form_object->status),
					'count_submit' => (int) $form_object->count_submit,
					'count_submit_unread' => (int) $form_object->count_submit_unread,
				];

				// Create instance of WS_Form_Form_Stat
				$ws_form_form_stat = new WS_Form_Form_Stat();
				$ws_form_form_stat->form_id = $form_id;

				if(!$time_from_utc && !$time_to_utc) {

					// No date range specific so return totals
					$return_data['count_stat_view'] = (int) $form_object->count_stat_view;
					$return_data['count_stat_save'] = (int) $form_object->count_stat_save;
					$return_data['count_stat_submit'] = (int) $form_object->count_stat_submit;

				} else {

					// Date range specified so get stats between dates
					$form_stats = $ws_form_form_stat->report_form_statistics_get_data_process(

						$time_from_utc,
						$time_to_utc
					);

					$return_data['count_stat_view'] = (int) $form_stats['count_view_total'];
					$return_data['count_stat_save'] = (int) $form_stats['count_save_total'];
					$return_data['count_stat_submit'] = (int) $form_stats['count_submit_total'];
				}

				// Daily statistics (empty array if no stats / range available)
				$return_data['daily'] = $ws_form_form_stat->db_get_daily($time_from_utc, $time_to_utc);

				// Return data
				return $return_data;

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving form statistics: %s', 'ws-form'));
			}
		}

		// Submissions - List
		public function submissions($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submissions');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Build filters
				$filters = array();

				// Date from
				$date_from = self::input_get('date_from');
				if($date_from !== '') {

					if(self::date_validate($date_from, false) === false) {

						throw new Exception(esc_html__('Invalid from date / time.', 'ws-form'));
					}

					$filters[] = array(

						'field' => 'date_from',
						'operator' => '==',
						'value' => $date_from
					);
				}

				// Date to
				$date_to = self::input_get('date_to');
				if($date_to !== '') {

					if(self::date_validate($date_to, true) === false) {

						throw new Exception(esc_html__('Invalid to date / time.', 'ws-form'));
					}

					$filters[] = array(

						'field' => 'date_to',
						'operator' => '==',
						'value' => $date_to
					);
				}

				// Status (sanitized by enum)
				$status = self::input_get('status');
				$filters[] = array(

					'field' => 'status',
					'operator' => '==',
					'value' => $status
				);

				// Keyword
				$keyword = self::input_get('keyword');

				// Limit / offset
				$limit = self::input_get('limit');
				if($limit <= 0) { $limit = 50; }

				$offset = self::input_get('offset');
				if($offset < 0) { $offset = 0; }

				// Order
				$order_by = self::input_get('order_by');
				$order = self::input_get('order');

				// Create instance of WS_Form_Submit_Export
				$ws_form_submit_export = new WS_Form_Submit_Export($form_id);

				// Get rows
				$rows = $ws_form_submit_export->get_rows(

					$limit,
					$offset,
					$keyword,
					$filters,
					$order_by,
					$order,
					false,
					false,
					false
				);

				// Get total
				$total = $ws_form_submit_export->get_row_count($keyword, $filters);

				// Return data
				return [

					'id' => $form_id,
					'total' => (int) $total,
					'submissions' => self::submissions_format_rows($ws_form_submit_export, $rows)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving submissions: %s', 'ws-form'));
			}
		}

		// Submission - Get
		public function submission_get($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submission-get');

				// Get submission ID
				$submission_id = self::input_get('id');

				// Check submission ID
				if($submission_id === 0) {

					throw new Exception(esc_html__('Invalid submission ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Submit
				$ws_form_submit = new WS_Form_Submit();
				$ws_form_submit->id = $submission_id;

				// Read submission
				$submit_object = $ws_form_submit->db_read(true, true);

				// Create instance of WS_Form_Submit_Export
				$ws_form_submit_export = new WS_Form_Submit_Export($submit_object->form_id);

				// Format as a single submission row
				$rows = $ws_form_submit_export->process_rows(array($submit_object), false, false, false);
				$submissions = self::submissions_format_rows($ws_form_submit_export, $rows);

				if(empty($submissions)) {

					throw new Exception(esc_html__('Unable to read submission.', 'ws-form'));
				}

				$submission = $submissions[0];
				$submission['form_id'] = (int) $submit_object->form_id;
				$submission['notes'] = self::submission_notes_format(isset($submit_object->notes) ? $submit_object->notes : array());

				// Return data
				return $submission;

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving submission: %s', 'ws-form'));
			}
		}

		// Submission - Notes
		public function submission_notes($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submission-notes');

				// Get submission ID
				$submission_id = self::input_get('id');

				// Check submission ID
				if($submission_id === 0) {

					throw new Exception(esc_html__('Invalid submission ID.', 'ws-form'));
				}

				$ws_form_submit_note = new WS_Form_Submit_Note();
				$ws_form_submit_note->submit_id = $submission_id;

				return [

					'notes' => self::submission_notes_format($ws_form_submit_note->db_read_by_submit())
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving submission notes: %s', 'ws-form'));
			}
		}

		// Submission - Note - Create
		public function submission_note_create($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submission-note-create');

				// Get submission ID
				$submission_id = self::input_get('id');

				// Check submission ID
				if($submission_id === 0) {

					throw new Exception(esc_html__('Invalid submission ID.', 'ws-form'));
				}

				$content = self::input_get('content');
				if(!is_string($content)) { $content = ''; }

				// Object inputs arrive as stdClass from input_parse
				$meta = self::input_object_to_array(self::input_get('meta'));

				$user_name = self::input_get('user_name');
				if(!is_string($user_name)) { $user_name = ''; }
				$user_name = sanitize_text_field($user_name);

				$locked = self::input_get('locked');
				if(!is_bool($locked)) { $locked = !empty($locked); }

				// user_name set = system/third-party note; otherwise current WordPress user
				$user_id = ($user_name !== '') ? 0 : get_current_user_id();

				$ws_form_submit_note = new WS_Form_Submit_Note();
				$ws_form_submit_note->submit_id = $submission_id;
				$ws_form_submit_note->user_id = $user_id;
				$ws_form_submit_note->user_name = $user_name;
				$ws_form_submit_note->content = $content;
				$ws_form_submit_note->meta = WS_Form_Submit_Note::meta_normalize($meta);
				$ws_form_submit_note->locked = $locked;
				$ws_form_submit_note->db_create();

				$notes = self::submission_notes_format(array($ws_form_submit_note->db_read()));

				return [

					'note' => isset($notes[0]) ? $notes[0] : array(),
					'message' => __('Submission note created.', 'ws-form')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error creating submission note: %s', 'ws-form'));
			}
		}

		// Submission - Note - Update
		public function submission_note_update($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submission-note-update');

				// Get note ID
				$note_id = self::input_get('id');

				// Check note ID
				if($note_id === 0) {

					throw new Exception(esc_html__('Invalid note ID.', 'ws-form'));
				}

				$ws_form_submit_note = new WS_Form_Submit_Note();
				$ws_form_submit_note->id = $note_id;
				$existing = $ws_form_submit_note->db_read();

				$content = self::input_get('content');
				if(!is_string($content)) { $content = ''; }

				// Object inputs arrive as stdClass; omitted meta becomes empty via to_object
				$meta = self::input_object_to_array(self::input_get('meta'));
				if(count($meta) === 0) {

					$meta = (isset($existing->meta) && is_array($existing->meta)) ? $existing->meta : array();
				}

				$ws_form_submit_note->submit_id = absint($existing->submit_id);
				$ws_form_submit_note->content = $content;
				$ws_form_submit_note->meta = WS_Form_Submit_Note::meta_normalize($meta);
				$ws_form_submit_note->db_update();

				$notes = self::submission_notes_format(array($ws_form_submit_note->db_read()));

				return [

					'note' => isset($notes[0]) ? $notes[0] : array(),
					'message' => __('Submission note updated.', 'ws-form')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating submission note: %s', 'ws-form'));
			}
		}

		// Submission - Note - Delete
		public function submission_note_delete($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'submission-note-delete');

				// Get note ID
				$note_id = self::input_get('id');

				// Check note ID
				if($note_id === 0) {

					throw new Exception(esc_html__('Invalid note ID.', 'ws-form'));
				}

				$ws_form_submit_note = new WS_Form_Submit_Note();
				$ws_form_submit_note->id = $note_id;
				$ws_form_submit_note->db_read();
				$ws_form_submit_note->db_delete();

				return [

					'id' => (int) $note_id,
					'message' => __('Submission note deleted.', 'ws-form')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting submission note: %s', 'ws-form'));
			}
		}

		// Format notes for ability output
		public function submission_notes_format($notes) {

			if(!is_array($notes)) { return array(); }

			$return_array = array();

			foreach($notes as $note) {

				$note = (object) $note;

				$return_array[] = array(

					'id' => isset($note->id) ? (int) $note->id : 0,
					'submit_id' => isset($note->submit_id) ? (int) $note->submit_id : 0,
					'user_id' => isset($note->user_id) ? (int) $note->user_id : 0,
					'user_name' => isset($note->user_name) ? (string) $note->user_name : '',
					'user_display_name' => isset($note->user_display_name) ? (string) $note->user_display_name : '',
					'date_added' => isset($note->date_added_wp) ? (string) $note->date_added_wp : (isset($note->date_added) ? (string) $note->date_added : ''),
					'date_updated' => isset($note->date_updated_wp) ? (string) $note->date_updated_wp : (isset($note->date_updated) ? (string) $note->date_updated : ''),
					'content' => isset($note->content) ? (string) $note->content : '',
					'meta' => WS_Form_Submit_Note::meta_normalize((isset($note->meta) && is_array($note->meta)) ? $note->meta : array()),
					'locked' => !empty($note->locked)
				);
			}

			return $return_array;
		}

		// Format submission export rows for ability output
		public function submissions_format_rows($ws_form_submit_export, $rows) {

			if(!is_array($rows) || empty($rows)) { return array(); }

			// Get field labels (field_N => label)
			$field_labels = $ws_form_submit_export->ws_form_submit->get_keys_fields();

			// Get field data (includes type)
			$submit_fields = $ws_form_submit_export->ws_form_submit->db_get_submit_fields();

			$submissions = array();

			foreach($rows as $row) {

				$fields = array();

				foreach($row as $key => $value) {

					if(strpos($key, 'field_') !== 0) { continue; }

					$field_id = (int) substr($key, 6);

					$fields[] = array(

						'id' => $field_id,
						'label' => isset($field_labels[$key]) ? $field_labels[$key] : $key,
						'type' => isset($submit_fields[$field_id]['type']) ? (string) $submit_fields[$field_id]['type'] : '',
						'value' => is_scalar($value) ? (string) $value : wp_json_encode($value)
					);
				}

				$submissions[] = array(

					'id' => isset($row['id']) ? (int) $row['id'] : 0,
					'status' => isset($row['status']) ? (string) $row['status'] : '',
					'status_full' => isset($row['status_full']) ? (string) $row['status_full'] : '',
					'date_added' => isset($row['date_added']) ? (string) $row['date_added'] : '',
					'date_updated' => isset($row['date_updated']) ? (string) $row['date_updated'] : '',
					'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : 0,
					'duration' => isset($row['duration']) ? (int) $row['duration'] : 0,
					'fields' => $fields
				);
			}

			return $submissions;
		}

		// Form - Delete
		public function form_delete($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-delete');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get permanent (wsf_ability_form_delete_permanent filter hook must be true)
				$permanent = self::input_get('permanent');

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				if($permanent && !apply_filters('wsf_ability_form_delete_permanent', false)) {

					throw new Exception(esc_html__('Permanent form deletion is not enabled.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Trash or permanently delete the form
				$ws_form_form->db_delete($permanent);

				// Return data
				return [

					'id' => $form_id,
					'permanent' => $permanent,
					'message' => esc_html($permanent ? __('Form permanently deleted', 'ws-form') : __('Form trashed', 'ws-form')),
					'url' => $permanent ? WS_Form_Common::get_admin_url('ws-form') : WS_Form_Common::get_admin_url('ws-form', false, '&ws-form-status=trash')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting form: %s', 'ws-form'));
			}
		}

		// Form - Restore
		public function form_restore($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'form-restore');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Form
				$ws_form_form = new WS_Form_Form();

				// Set form ID
				$ws_form_form->id = $form_id;

				// Restore the form
				$ws_form_form->db_restore();

				// Return data
				return [

					'id' => $form_id,
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-edit', $form_id),
					'url_preview' => WS_Form_Common::get_preview_url($form_id)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error restoring form: %s', 'ws-form'));
			}
		}

		// Styles - List
		public function styles($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'styles');

				$published = self::input_get('published');
				$order_by = self::input_get('order_by');
				$order = self::input_get('order');

				$where = $published ? "status = 'publish'" : "NOT (status = 'trash')";
				$order_sql = sprintf('%s %s', $order_by === 'id' ? 'id' : 'label', $order === 'DESC' ? 'DESC' : 'ASC');

				$ws_form_style = new WS_Form_Style();
				$styles = $ws_form_style->db_read_all('', $where, $order_sql);
				if(!is_array($styles)) { $styles = array(); }

				$return_styles = array();

				foreach($styles as $style) {

					$return_styles[] = array(

						'id' => (int) $style['id'],
						'label' => $style['label'],
						'status' => $style['status'],
						'default' => !empty($style['default']),
						'default_conv' => !empty($style['default_conv'])
					);
				}

				return array('styles' => $return_styles);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving styles: %s', 'ws-form'));
			}
		}

		// Style - Get
		public function style_get($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'style-get');

				$style_id = self::input_get('id');
				if($style_id === 0) {

					throw new Exception(esc_html__('Invalid style ID.', 'ws-form'));
				}

				$ws_form_style = new WS_Form_Style();
				$ws_form_style->id = $style_id;
				$style_object = $ws_form_style->db_read(true);

				return array(

					'id' => (int) $style_object->id,
					'label' => $style_object->label,
					'status' => $style_object->status,
					'default' => !empty($style_object->default),
					'default_conv' => !empty($style_object->default_conv),
					'meta' => self::style_root_meta_from_object($style_object),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-style', $style_id)
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error retrieving style: %s', 'ws-form'));
			}
		}

		// Style - Create
		public function style_create($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'style-create');

				$label = self::input_get('label');
				$template_id = self::input_get('template_id');
				$meta = self::style_root_meta_sanitize(self::input_get('meta'));
				$form_id = self::input_get('form_id');
				$conversational = self::input_get('conversational');
				$default = self::input_get('default');
				$default_conv = self::input_get('default_conv');

				self::style_require_edit_option($meta, $form_id, $default, $default_conv);

				$ws_form_style = new WS_Form_Style();

				if($template_id !== '') {

					self::style_template_validate($template_id);
					$ws_form_style->db_create_from_template($template_id);

				} else {

					if($label !== '') {

						$ws_form_style->label = $label;
					}

					$ws_form_style->db_create();
				}

				$style_id = (int) $ws_form_style->id;
				if($style_id === 0) {

					throw new Exception(esc_html__('Error creating style.', 'ws-form'));
				}

				if(($template_id !== '') && ($label !== '')) {

					$ws_form_style->db_label($label);
				}

				if(!empty($meta)) {

					self::style_root_meta_apply($ws_form_style, $meta);
				}

				if($default) {

					$ws_form_style->db_default();
				}

				if($default_conv) {

					$ws_form_style->db_default_conv();
				}

				if($form_id > 0) {

					self::style_assign_to_form($style_id, $form_id, $conversational);
				}

				self::style_finish($ws_form_style);

				$ws_form_style->id = $style_id;
				$style_object = $ws_form_style->db_read(true);

				$return = array(

					'id' => $style_id,
					'label' => $style_object->label,
					'status' => $style_object->status,
					'default' => !empty($style_object->default),
					'default_conv' => !empty($style_object->default_conv),
					'meta' => self::style_root_meta_from_object($style_object),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-style', $style_id)
				);

				if($form_id > 0) {

					$return['form_id'] = $form_id;
					$return['conversational'] = $conversational;
					$return['url_form_edit'] = WS_Form_Common::get_admin_url('ws-form-edit', $form_id);
					$return['url_form_preview'] = WS_Form_Common::get_preview_url($form_id);
				}

				return $return;

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error creating style: %s', 'ws-form'));
			}
		}

		// Style - Update
		public function style_update($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'style-update');

				$style_id = self::input_get('id');
				if($style_id === 0) {

					throw new Exception(esc_html__('Invalid style ID.', 'ws-form'));
				}

				$label = self::input_get('label');
				$meta = self::style_root_meta_sanitize(self::input_get('meta'));
				$form_id = self::input_get('form_id');
				$conversational = self::input_get('conversational');
				$default = self::input_get('default');
				$default_conv = self::input_get('default_conv');

				if(
					($label === '') &&
					empty($meta) &&
					!$default &&
					!$default_conv &&
					($form_id === 0)
				) {

					throw new Exception(esc_html__('Provide a label, meta, default flag, and/or form ID to update.', 'ws-form'));
				}

				$ws_form_style = new WS_Form_Style();
				$ws_form_style->id = $style_id;
				$ws_form_style->db_read(false);

				if($label !== '') {

					$ws_form_style->db_label($label);
				}

				if(!empty($meta)) {

					self::style_root_meta_apply($ws_form_style, $meta);
				}

				if($default) {

					$ws_form_style->db_default();
				}

				if($default_conv) {

					$ws_form_style->db_default_conv();
				}

				if($form_id > 0) {

					self::style_assign_to_form($style_id, $form_id, $conversational);
				}

				self::style_finish($ws_form_style);

				$ws_form_style->id = $style_id;
				$style_object = $ws_form_style->db_read(true);

				$return = array(

					'id' => $style_id,
					'label' => $style_object->label,
					'status' => $style_object->status,
					'default' => !empty($style_object->default),
					'default_conv' => !empty($style_object->default_conv),
					'meta' => self::style_root_meta_from_object($style_object),
					'url_edit' => WS_Form_Common::get_admin_url('ws-form-style', $style_id)
				);

				if($form_id > 0) {

					$return['form_id'] = $form_id;
					$return['conversational'] = $conversational;
					$return['url_form_edit'] = WS_Form_Common::get_admin_url('ws-form-edit', $form_id);
					$return['url_form_preview'] = WS_Form_Common::get_preview_url($form_id);
				}

				return $return;

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating style: %s', 'ws-form'));
			}
		}

		// Style - Delete
		public function style_delete($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'style-delete');

				$style_id = self::input_get('id');
				if($style_id === 0) {

					throw new Exception(esc_html__('Invalid style ID.', 'ws-form'));
				}

				$permanent = self::input_get('permanent');

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				if($permanent && !apply_filters('wsf_ability_style_delete_permanent', false)) {

					throw new Exception(esc_html__('Permanent style deletion is not enabled.', 'ws-form'));
				}

				$ws_form_style = new WS_Form_Style();
				$ws_form_style->id = $style_id;

				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
				$status = $wpdb->get_var($wpdb->prepare(

					"SELECT status FROM {$wpdb->prefix}wsf_style WHERE id = %d;",
					$style_id
				));

				if(is_null($status)) {

					throw new Exception(esc_html__('Invalid style ID.', 'ws-form'));
				}

				if($permanent && ($status !== 'trash')) {

					$trash_result = $ws_form_style->db_delete();
					if($trash_result === false) {

						throw new Exception(esc_html__('Unable to delete style. Default styles cannot be deleted.', 'ws-form'));
					}
				}

				$delete_result = $ws_form_style->db_delete();
				if($delete_result === false) {

					throw new Exception(esc_html__('Unable to delete style. Default styles cannot be deleted.', 'ws-form'));
				}

				return array(

					'id' => $style_id,
					'permanent' => $permanent || ($status === 'trash'),
					'message' => esc_html(
						($permanent || ($status === 'trash'))
							? __('Style permanently deleted', 'ws-form')
							: __('Style trashed', 'ws-form')
					),
					'url' => ($permanent || ($status === 'trash'))
						? WS_Form_Common::get_admin_url('ws-form-style')
						: WS_Form_Common::get_admin_url('ws-form-style', false, 'ws-form-status=trash')
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting style: %s', 'ws-form'));
			}
		}

		// Field - Add
		public function field_add($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'field-add');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get section ID
				$section_id = self::input_get('section_id');

				// Get field label
				$field_label = self::input_get('label');

				// Get type
				$field_type = self::input_get('type');

				// Get meta
				$field_meta = self::input_get('meta');

				// Get next sibling ID
				$next_sibling_id = self::input_get('field_id_before');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Add field
				$ws_form_field = $ws_form_form_ai->field_add(

					$section_id,
					$field_label,
					$field_type,
					$field_meta,
					$next_sibling_id
				);

				// Return data
				return [

					'id' => $form_id,
					'field' => $ws_form_form_ai->field_format_for_ability($ws_form_field)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error adding field: %s', 'ws-form'));
			}
		}

		// Field - Update
		public function field_update($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'field-update');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get field ID
				$field_id = self::input_get('field_id');

				// Check field ID
				if($field_id === 0) {

					throw new Exception(esc_html__('Invalid field ID.', 'ws-form'));
				}

				// Get label (optional)
				$field_label = self::input_get('label');

				// Get meta (optional)
				$field_meta = self::input_get('meta');

				// Create instance of WS_Form_Form_AI
				$ws_form_form_ai = new WS_Form_Form_AI();

				// Set form ID
				$ws_form_form_ai->id = $form_id;

				// Update field
				$ws_form_field = $ws_form_form_ai->field_update(

					$field_id,
					$field_label,
					$field_meta
				);

				// Return data
				return [

					'id' => $form_id,
					'field' => $ws_form_form_ai->field_format_for_ability($ws_form_field)
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating field: %s', 'ws-form'));
			}
		}

		// Field - Delete
		public function field_delete($input) {

			try {

				// Init
				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'field-delete');

				// Get form ID
				$form_id = self::input_get('id');

				// Check form ID
				if($form_id === 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Get field ID
				$field_id = self::input_get('field_id');

				// Check field ID
				if($field_id === 0) {

					throw new Exception(esc_html__('Invalid field ID.', 'ws-form'));
				}

				// Create instance of WS_Form_Field
				$ws_form_field = new WS_Form_Field();

				// Set form ID
				$ws_form_field->form_id = $form_id;

				// Set field ID
				$ws_form_field->id = $field_id;

				// Delete field
				$ws_form_field->db_delete();

				// Return data
				return [

					'id' => $form_id,
					'field_id' => $field_id,
					'message' => __('Field permanently deleted', 'ws-form')
				];

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting field: %s', 'ws-form'));
			}
		}

		// Tabs - List
		public function tabs($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'tabs');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;

				return array('id' => $form_id, 'tabs' => $ws_form_form_ai->tabs_list());

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error listing tabs: %s', 'ws-form'));
			}
		}

		// Sections - List
		public function sections($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'sections');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;

				return array('id' => $form_id, 'sections' => $ws_form_form_ai->sections_list());

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error listing sections: %s', 'ws-form'));
			}
		}

		// Fields - List
		public function fields($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'fields');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;

				return array('id' => $form_id, 'fields' => $ws_form_form_ai->fields_list());

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error listing fields: %s', 'ws-form'));
			}
		}

		// Tab - Add
		public function tab_add($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'tab-add');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_group = $ws_form_form_ai->tab_add(self::input_get('label'), self::input_get('tab_id_before'));

				return array(

					'id' => $form_id,
					'tab' => $ws_form_form_ai->tab_format_for_ability($ws_form_group)
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error adding tab: %s', 'ws-form'));
			}
		}

		// Tab - Update
		public function tab_update($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'tab-update');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_group = $ws_form_form_ai->tab_update(self::input_get('tab_id'), self::input_get('label'));

				return array(

					'id' => $form_id,
					'tab' => $ws_form_form_ai->tab_format_for_ability($ws_form_group)
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating tab: %s', 'ws-form'));
			}
		}

		// Tab - Delete
		public function tab_delete($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'tab-delete');
				$form_id = self::input_get('id');
				$tab_id = self::input_get('tab_id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_form_ai->tab_delete($tab_id);

				return array(

					'id' => $form_id,
					'tab_id' => $tab_id,
					'message' => __('Tab permanently deleted', 'ws-form')
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting tab: %s', 'ws-form'));
			}
		}

		// Section - Add
		public function section_add($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'section-add');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_section = $ws_form_form_ai->section_add(

					self::input_get('group_id'),
					self::input_get('label'),
					self::input_get('section_id_before'),
					self::input_get('meta')
				);

				return array(

					'id' => $form_id,
					'section' => $ws_form_form_ai->section_format_for_ability($ws_form_section)
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error adding section: %s', 'ws-form'));
			}
		}

		// Section - Update
		public function section_update($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'section-update');
				$form_id = self::input_get('id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_section = $ws_form_form_ai->section_update(

					self::input_get('section_id'),
					self::input_get('label'),
					self::input_get('meta')
				);

				return array(

					'id' => $form_id,
					'section' => $ws_form_form_ai->section_format_for_ability($ws_form_section)
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error updating section: %s', 'ws-form'));
			}
		}

		// Section - Delete
		public function section_delete($input) {

			try {

				self::init($input, WS_FORM_ABILITY_API_NAMESPACE . 'section-delete');
				$form_id = self::input_get('id');
				$section_id = self::input_get('section_id');
				if($form_id === 0) { throw new Exception(esc_html__('Invalid form ID.', 'ws-form')); }

				$ws_form_form_ai = new WS_Form_Form_AI();
				$ws_form_form_ai->id = $form_id;
				$ws_form_form_ai->section_delete($section_id);

				return array(

					'id' => $form_id,
					'section_id' => $section_id,
					'message' => __('Section permanently deleted', 'ws-form')
				);

			} catch(Exception $e) {

				/* translators: %s: Error message */
				return self::error($e, __('Error deleting section: %s', 'ws-form'));
			}
		}


		// Style root palette meta keys (cascade drivers)
		public function style_root_meta_keys() {

			return array(

				'form_color_background',
				'form_color_base',
				'form_color_base_contrast',
				'form_color_accent',
				'form_color_neutral',
				'form_color_primary',
				'form_color_secondary',
				'form_color_success',
				'form_color_info',
				'form_color_warning',
				'form_color_danger',
				'form_font_family',
				'form_font_size',
				'form_font_weight',
				'form_border_radius',
				'form_grid_gap'
			);
		}

		// Style root keys including alt variants
		public function style_root_meta_keys_allowed() {

			$keys = self::style_root_meta_keys();

			foreach(self::style_root_meta_keys() as $key) {

				$keys[] = $key . '_alt';
			}

			return $keys;
		}

		// Sanitize sparse root palette meta; reject unknown keys
		public function style_root_meta_sanitize($meta) {

			$meta = self::input_object_to_array($meta);
			if(empty($meta)) { return array(); }

			$allowed = array_flip(self::style_root_meta_keys_allowed());
			$sanitized = array();
			$unknown = array();

			foreach($meta as $key => $value) {

				if(!isset($allowed[$key])) {

					$unknown[] = $key;
					continue;
				}

				if(!is_scalar($value) && !is_null($value)) {

					throw new Exception(

						sprintf(

							/* translators: %s: Meta key */
							esc_html__('Invalid style meta value for %s.', 'ws-form'),
							esc_html($key)
						)
					);
				}

				$sanitized[$key] = WS_Form_Common::sanitize_css_value((string) $value);
			}

			if(!empty($unknown)) {

				throw new Exception(

					sprintf(

						/* translators: %s: Comma-separated meta keys */
						esc_html__('Unsupported style meta key(s): %s', 'ws-form'),
						esc_html(implode(', ', $unknown))
					)
				);
			}

			return $sanitized;
		}

		// Extract root palette meta from a style object
		public function style_root_meta_from_object($style_object) {

			$meta = array();

			if(!isset($style_object->meta) || !is_object($style_object->meta)) {

				return $meta;
			}

			foreach(self::style_root_meta_keys_allowed() as $key) {

				if(property_exists($style_object->meta, $key)) {

					$meta[$key] = $style_object->meta->{$key};
				}
			}

			return $meta;
		}

		// Apply root palette meta to a style (partial merge)
		public function style_root_meta_apply($ws_form_style, $meta) {

			if(empty($meta)) { return; }

			$style_object = $ws_form_style->db_read(true);

			if(!isset($style_object->meta) || !is_object($style_object->meta)) {

				$style_object->meta = new stdClass();
			}

			foreach($meta as $key => $value) {

				$style_object->meta->{$key} = $value;
			}

			$ws_form_style->db_update_from_object($style_object, false, false);
		}

		// Assign style to a form
		public function style_assign_to_form($style_id, $form_id, $conversational = false) {

			if(!WS_Form_Common::can_user('edit_form')) {

				throw new Exception(esc_html__('You do not have permission to assign a style to a form.', 'ws-form'));
			}

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $form_id;

			if(!$ws_form_form->db_check_id_exists()) {

				throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
			}

			$ws_form_meta = new WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $form_id;
			$ws_form_meta->db_update_from_array(array(

				($conversational ? 'style_id_conv' : 'style_id') => $style_id
			));
		}

		// Validate style template ID
		public function style_template_validate($template_id) {

			$ws_form_template = new WS_Form_Template();
			$ws_form_template->type = 'style';
			$ws_form_template->id = $template_id;
			$ws_form_template->read();
		}

		// Require Allow Updates when mutating palette, default, or form assignment on create
		public function style_require_edit_option($meta, $form_id, $default, $default_conv) {

			if(
				empty($meta) &&
				($form_id === 0) &&
				!$default &&
				!$default_conv
			) {
				return;
			}

			if(!WS_Form_Common::option_get('abilities_api_edit_form', false)) {

				throw new Exception(esc_html__('Style updates are not enabled.', 'ws-form'));
			}
		}

		// Publish or checksum style after changes
		public function style_finish($ws_form_style) {

			if($ws_form_style->publish_auto) {

				$ws_form_style->db_publish();

			} else {

				$ws_form_style->db_checksum();
			}
		}

		// Init
		public function init($input, $ability_name) {

			// Parse input
			self::input_parse($input, $ability_name);
		}

		// Permission callback
		public function permission_callback($caps) {

			if ( empty( $caps ) ) {
				return false;
			}

			$user = wp_get_current_user();

			if ( ! $user || 0 === $user->ID ) {

				return false;
			}

			if( is_string($caps) ) {

				return $user->has_cap( $caps );
			}

			if( is_array($caps) ) {

				foreach ( $caps as $cap ) {

					if ( ! $user->has_cap( $cap ) ) {

						return false;
					}
				}

				return true;
			}

			return false;
		}

		// Input parse
		public function input_parse($input, $ability_name) {

			// Reset input
			$this->input = array();

			// Check if input is WP_REST_Request
			if(is_a($input, 'WP_REST_Request')) {

				$input = $input->get_json_params();
			}

			// Check input are an array
			if(!is_array($input)) { $input = array(); }

			// Ensure all input match input schema

			// Get ability input schema
			$input_schema = WS_Form_Config_Ability::get_ability_input_schema($ability_name);

			// Check input schema is valid
			if(!is_array($input_schema)) {

				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('Input schema for ability %s not found.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Check if input schema is empty
			if(!is_array($input_schema) || empty($input_schema)) {

				return array();
			}

			// Check type is object
			if(
				!isset($input_schema['type']) ||
				($input_schema['type'] != 'object')
			) {
				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('Invalid input schema type for ability %s. Expects object.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Check properties exist
			if(
				!isset($input_schema['properties']) ||
				!is_array($input_schema['properties'])
			) {
				throw new Exception(

					sprintf(

						/* translators: %s: Ability name */
						esc_html__('No input schema properties for ability %s.', 'ws-form'),
						esc_html($ability_name)
					)
				);
			}

			// Process properties
			foreach($input_schema['properties'] as $property_name => $property) {

				// Check property name (Required)
				if(empty($property_name)) {

					throw new Exception(

						sprintf(

							/* translators: %s: Ability name */
							esc_html__('Invalid input schema property name for ability %s.', 'ws-form'),
							esc_html($ability_name)
						)
					);
				}

				// Get property type
				$type = isset($property['type']) ? strtolower($property['type']) : 'string';

				// Check property type (Required)
				if(
					!in_array($type, array(

						// MCP
						'number',
						'boolean',
						'string',
						'array',
						'object',
						'null',

						// WordPress abilities API
						'integer'
					))
				) {
					throw new Exception(

						sprintf(

							/* translators: %1$s: Ability name, %2$s: Property name */
							esc_html__('Invalid input schema property type for ability %1$s (Property: %2$s).', 'ws-form'),
							esc_html($ability_name),
							esc_html($property_name)
						)
					);
				}

				// Get input value
				$input_value = isset($input[$property_name]) ? $input[$property_name] : '';

				// Get required
				$required = isset($property['required']) ? WS_Form_Common::is_true($property['required']) : null;

				// Check required
				if($required) {

					if(
						!isset($input[$property_name]) ||
						(
							is_null($input[$property_name]) &&
							($type !== 'null')
						) ||
						(
							($type === 'string') &&
							($input[$property_name] === '')
						)
					) {
						throw new Exception(

							sprintf(

								/* translators: %1$s: Ability ID, %2$s: Property name */
								esc_html__('Required input schema property for ability %1$s missing (Property: %2$s).', 'ws-form'),
								esc_html($ability_name),
								esc_html($property_name)
							)
						);
					}
				}

				// Process according to type
				// Attempt to convert to correct type in case AI provides a different type
				switch($type) {

					case 'number' :
					case 'integer' :

						$input_value = intval($input_value);
						break;

					case 'boolean' :

						$input_value = WS_Form_Common::is_true($input_value);
						break;

					case 'string' :

						$input_value = sanitize_text_field($input_value);
						break;

					case 'array' :

						$input_value = WS_Form_Common::to_array($input_value);
						break;

					case 'object' :

						$input_value = WS_Form_Common::to_object($input_value);
						break;

					case 'null' :

						$input_value = null;
						break;
				}

				// If no value passed and string then set to default value
				$default_value = isset($property['default']) ? $property['default'] : null;
				if(
					($input_value === '') &&
					!is_null($default_value)
				) {
					$input_value = $default_value;
				}

				// Enumeration
				if(isset($property['enum'])) {

					if(!is_array($property['enum'])) {

						throw new Exception(

							sprintf(

								/* translators: %1$s: Property name, %2$s: Ability ID */
								esc_html__('Input schema property %1$s has invalid enum for ability %2$s. Expected array.', 'ws-form'),
								esc_html($property_name),
								esc_html($ability_name)
							)
						);
					}

					if(!in_array($input_value, $property['enum'], true)) {

						throw new Exception(

							sprintf(

								/* translators: %1$s: Ability name, %2$s: Property name */
								esc_html__('Invalid enumerated input value for ability %1$s (Property: %2$s).', 'ws-form'),
								esc_html($ability_name),
								esc_html($property_name)
							)
						);
					}
				}

				// Sanitize and set input
				$this->input[$property_name] = $input_value;
			}

			return $this->input;
		}

		// Get input
		public function input_get($property_name) {

			// Check input have been parsed
			if($this->input === false) {

				throw new Exception(esc_html__('Inputs not parsed.', 'ws-form'));
			}

			if(!isset($this->input[$property_name])) {

				throw new Exception(

					sprintf(

						/* translators: %s: Property name */
						esc_html__('Property %s does not exist in input schema.', 'ws-form'),
						esc_html($property_name)
					)
				);
			}

			return $this->input[$property_name];
		}

		// Normalize object ability inputs to arrays (missing values become {scalar:''} via to_object)
		public function input_object_to_array($value) {

			if(is_object($value)) {

				$value = (array) $value;
			}

			if(!is_array($value)) {

				return array();
			}

			if(
				array_key_exists('scalar', $value) &&
				(count($value) === 1) &&
				(($value['scalar'] === '') || is_null($value['scalar']))
			) {
				return array();
			}

			return $value;
		}

		// Validate date
		public function date_validate($date, $to = true) {

			// Allow blank value
			if ($date === '' || $date === false) {
				return '';
			}

			// Try to parse the date
			$timestamp = strtotime($date);

			if ($timestamp === false) {

				return false;
			}

			// Normalize date
			$date_normalized = gmdate('Y-m-d', $timestamp);

			// Ensure the original string matches the normalized format exactly
			if ($date_normalized !== $date) {
				
				return false;
			}

			// Change to YYYY-MM-DD HH:MM:SS format
			$formatted = sprintf(

				'%s %s',
				$date_normalized,
				($to ? '11:59:59' : '00:00:00')
			);

			return $formatted;
		}

		// Error handling
		public function error($e, $message) {

			return [

				'error' => array(

					'code' => 'ws_form_error',
					'message' => sprintf(

						esc_html($message),
						esc_html($e->getMessage())
					),
					'data' => $this->input
				)
			];
		}
	}
