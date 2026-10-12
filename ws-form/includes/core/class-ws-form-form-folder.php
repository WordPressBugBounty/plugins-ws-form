<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Form_Folder extends WS_Form_Core {

		public $table_name;

		const ERROR_DUPLICATE = 'duplicate';
		const ERROR_CHILDREN = 'children';
		const ERROR_NOT_FOUND = 'not_found';
		const ERROR_LABEL = 'label';
		const ERROR_DEPTH = 'depth';

		public function __construct() {

			global $wpdb;

			$this->table_name = sprintf('%s%sform_folder', $wpdb->prefix, WS_FORM_DB_TABLE_PREFIX);
		}

		// Human message for an error code
		public function message($code) {

			switch($code) {

				case self::ERROR_DUPLICATE :

					return __('A folder with that name already exists here.', 'ws-form');

				case self::ERROR_CHILDREN :

					return __('This folder could not be deleted.', 'ws-form');

				case self::ERROR_NOT_FOUND :

					return __('Folder not found.', 'ws-form');

				case self::ERROR_LABEL :

					return __('Enter a folder name.', 'ws-form');

				case self::ERROR_DEPTH :

					return __('Folders cannot be nested any deeper.', 'ws-form');

				default :

					return $code;
			}
		}

		// Read all folders
		public function db_read_all() {

			WS_Form_Common::user_must('read_form');

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$rows = $wpdb->get_results("SELECT id, label, parent_id, sort_index FROM " . esc_sql($this->table_name) . " ORDER BY sort_index ASC, label ASC", ARRAY_A);

			return is_array($rows) ? $rows : array();
		}

		// Read one folder
		public function db_get($id) {

			WS_Form_Common::user_must('read_form');

			$id = absint($id);
			if($id === 0) { return false; }

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$row = $wpdb->get_row($wpdb->prepare(

				"SELECT id, label, parent_id, sort_index FROM " . esc_sql($this->table_name) . " WHERE id = %d LIMIT 1",
				$id

			), ARRAY_A);

			return is_array($row) ? $row : false;
		}

		// Flat tree for the sidebar. depth 0 is a root folder.
		public function tree_rows() {

			$rows = self::db_read_all();
			$by_parent = array();

			foreach($rows as $row) {

				$parent_id = absint($row['parent_id']);
				if(!isset($by_parent[$parent_id])) { $by_parent[$parent_id] = array(); }
				$by_parent[$parent_id][] = $row;
			}

			$counts = self::form_counts();
			$flat = array();

			$walk = function($parent_id, $depth, $path) use (&$walk, &$flat, $by_parent, $counts) {

				if(empty($by_parent[$parent_id])) { return; }

				foreach($by_parent[$parent_id] as $row) {

					$id = absint($row['id']);
					$label = $row['label'];
					$row_path = ($path === '') ? $label : $path . ' / ' . $label;

					$flat[] = array(

						'id' => $id,
						'label' => $label,
						'parent_id' => absint($row['parent_id']),
						'depth' => $depth,
						'path' => $row_path,
						'form_count' => isset($counts[$id]) ? $counts[$id] : 0,
						'has_children' => !empty($by_parent[$id])
					);

					$walk($id, $depth + 1, $row_path);
				}
			};

			$walk(0, 0, '');

			return $flat;
		}

		// id => path
		public function paths() {

			$paths = array();

			foreach(self::tree_rows() as $row) {

				$paths[$row['id']] = $row['path'];
			}

			return $paths;
		}

		// Depth of a folder. 0 is a top-level folder.
		public function depth($id) {

			$depth = 0;
			$seen = array();
			$id = absint($id);

			while($id > 0) {

				if(isset($seen[$id])) { break; }
				$seen[$id] = true;

				$row = self::db_get($id);
				if(!$row) { break; }

				$id = absint($row['parent_id']);
				if($id > 0) { $depth++; }
			}

			return $depth;
		}

		// Direct form counts, excluding trash
		public function form_counts() {

			WS_Form_Common::user_must('read_form');

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$rows = $wpdb->get_results("SELECT folder_id, COUNT(id) AS form_count FROM {$wpdb->prefix}wsf_form WHERE NOT (status = 'trash') GROUP BY folder_id", ARRAY_A);

			$counts = array();

			if(is_array($rows)) {

				foreach($rows as $row) {

					$counts[absint($row['folder_id'])] = absint($row['form_count']);
				}
			}

			return $counts;
		}

		// Create
		public function db_create($label, $parent_id = 0) {

			WS_Form_Common::user_must('edit_form');

			$label = self::sanitize_folder_label($label);
			$parent_id = absint($parent_id);

			if($parent_id > 0) {

				if(!self::db_get($parent_id)) {

					parent::db_throw_error(self::ERROR_NOT_FOUND);
				}

				if((self::depth($parent_id) + 1) > WS_FORM_FORM_FOLDERS_DEPTH_MAX) {

					parent::db_throw_error(self::ERROR_DEPTH);
				}
			}

			self::require_unique_label($label, $parent_id, 0);

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$sort_index = absint($wpdb->get_var($wpdb->prepare(

				"SELECT MAX(sort_index) FROM " . esc_sql($this->table_name) . " WHERE parent_id = %d",
				$parent_id

			))) + 1;

			$now = WS_Form_Common::get_mysql_date();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom database table
			$insert_result = $wpdb->insert(

				$this->table_name,

				array(

					'label' => $label,
					'parent_id' => $parent_id,
					'sort_index' => $sort_index,
					'date_added' => $now,
					'date_updated' => $now
				),

				array('%s', '%d', '%d', '%s', '%s')
			);

			if($insert_result === false) {

				parent::db_wpdb_handle_error(__('Error adding folder', 'ws-form'));
			}

			return absint($wpdb->insert_id);
		}

		// Rename
		public function db_rename($id, $label) {

			WS_Form_Common::user_must('edit_form');

			$id = absint($id);
			$row = self::db_get($id);

			if(!$row) {

				parent::db_throw_error(self::ERROR_NOT_FOUND);
			}

			$label = self::sanitize_folder_label($label);
			$parent_id = absint($row['parent_id']);

			self::require_unique_label($label, $parent_id, $id);

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$update_result = $wpdb->update(

				$this->table_name,

				array(

					'label' => $label,
					'date_updated' => WS_Form_Common::get_mysql_date()
				),

				array('id' => $id),
				array('%s', '%s'),
				array('%d')
			);

			if($update_result === false) {

				parent::db_wpdb_handle_error(__('Error renaming folder', 'ws-form'));
			}

			return $id;
		}

		// Delete this folder and its subfolders. Forms in them are unassigned.
		public function db_delete($id) {

			WS_Form_Common::user_must('edit_form');

			$id = absint($id);
			$row = self::db_get($id);

			if(!$row) {

				parent::db_throw_error(self::ERROR_NOT_FOUND);
			}

			global $wpdb;

			$parent_id = absint($row['parent_id']);
			$ids = array_map('absint', self::folder_ids_with_descendants($id));

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$update_result = $wpdb->query($wpdb->prepare(

				"UPDATE {$wpdb->prefix}wsf_form SET folder_id = %d WHERE folder_id IN (" . esc_sql(implode(',', $ids)) . ")",
				0

			));

			if($update_result === false) {

				parent::db_wpdb_handle_error(__('Error deleting folder', 'ws-form'));
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$delete_result = $wpdb->query("DELETE FROM " . esc_sql($this->table_name) . " WHERE id IN (" . esc_sql(implode(',', $ids)) . ")");

			if($delete_result === false) {

				parent::db_wpdb_handle_error(__('Error deleting folder', 'ws-form'));
			}

			return array(

				'id' => $id,
				'parent_id' => $parent_id,
				'deleted_ids' => $ids
			);
		}

		// This folder and every folder under it
		private function folder_ids_with_descendants($id) {

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$rows = $wpdb->get_results("SELECT id, parent_id FROM " . esc_sql($this->table_name), ARRAY_A);
			$by_parent = array();

			if(is_array($rows)) {

				foreach($rows as $row) {

					$parent_id = absint($row['parent_id']);
					if(!isset($by_parent[$parent_id])) { $by_parent[$parent_id] = array(); }
					$by_parent[$parent_id][] = absint($row['id']);
				}
			}

			$ids = array();

			$walk = function($folder_id) use (&$walk, &$ids, $by_parent) {

				$folder_id = absint($folder_id);
				if(($folder_id < 1) || isset($ids[$folder_id])) { return; }

				$ids[$folder_id] = $folder_id;

				if(empty($by_parent[$folder_id])) { return; }

				foreach($by_parent[$folder_id] as $child_id) {

					$walk($child_id);
				}
			};

			$walk($id);

			return array_values($ids);
		}

		// Move forms. folder_id 0 removes them from a folder. Replaces any previous folder.
		public function db_assign($form_ids, $folder_id) {

			WS_Form_Common::user_must('edit_form');

			$folder_id = absint($folder_id);

			if($folder_id > 0 && !self::db_get($folder_id)) {

				parent::db_throw_error(self::ERROR_NOT_FOUND);
			}

			$ids = array();

			if(!is_array($form_ids)) { $form_ids = array($form_ids); }

			foreach($form_ids as $form_id) {

				$form_id = absint($form_id);
				if($form_id > 0) { $ids[$form_id] = $form_id; }
			}

			if(empty($ids)) {

				parent::db_throw_error(self::ERROR_NOT_FOUND);
			}

			global $wpdb;

			$ids = array_map('absint', $ids);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$wpdb->query($wpdb->prepare(

				"UPDATE {$wpdb->prefix}wsf_form SET folder_id = %d WHERE id IN (" . esc_sql(implode(',', $ids)) . ")",
				$folder_id

			));

			return array(

				'folder_id' => $folder_id,
				'form_ids' => array_values($ids)
			);
		}

		// Label
		private function sanitize_folder_label($label) {

			$label = sanitize_text_field($label);
			$label = trim($label);

			if($label === '') {

				parent::db_throw_error(self::ERROR_LABEL);
			}

			if(function_exists('mb_substr')) {

				$label = mb_substr($label, 0, 191);

			} else {

				$label = substr($label, 0, 191);
			}

			return $label;
		}

		// Sibling names must be unique, case-insensitive
		private function require_unique_label($label, $parent_id, $exclude_id) {

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom database table
			$existing_id = absint($wpdb->get_var($wpdb->prepare(

				"SELECT id FROM " . esc_sql($this->table_name) . " WHERE parent_id = %d AND LOWER(label) = LOWER(%s) AND id != %d LIMIT 1",
				$parent_id,
				$label,
				absint($exclude_id)

			)));

			if($existing_id > 0) {

				parent::db_throw_error(self::ERROR_DUPLICATE);
			}
		}
	}
