<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_WP_List_Table_Form extends WP_List_Table {

		// Construct
	    public function __construct() {

			parent::__construct(array(

				'singular'		=> __('Form', 'ws-form'), //Singular label
				'plural'		=> __('Forms', 'ws-form'), //plural label, also this well be one of the table css class
				'ajax'			=> false //We won't support Ajax for this table
			));

			// Set primary column
			add_filter('list_table_primary_column',[$this, 'list_table_primary_column'], 10, 2);
	    }

	    // Get columns
		public function get_columns() {

  		  	$columns = [

				'cb'			=> '<input type="checkbox" />',
			];

			$current = WS_Form_Common::get_query_var('ws-form-status', 'all');

			if($current == 'all') {

				$columns['media'] = __('Status', 'ws-form');
			}

			$columns = array_merge($columns, array(

				'title'				=> __('Name', 'ws-form'),
				'id'				=> __('ID', 'ws-form'),
				'count_submit'		=> __('Submissions', 'ws-form'),
				'shortcode'			=> __('Shortcode', 'ws-form')
			));

			if($current !== 'trash') {

				$columns['locked'] = __('Lock', 'ws-form');
			}

			return $columns;
		}

		// Get sortable columns
		public function get_sortable_columns() {

			return array(

				'media'				=> array('status', true),		// Used 'media' as opposed to 'status' because WordPress considers that a special keyword and excludes it from the screen options column checkboxes
				'title'				=> array('label', true),		// Used 'title' as opposed to 'label' because WordPress considers that a special keyword and excludes it from the screen options column checkboxes
				'id'				=> array('id', true),
				'count_submit'		=> array('count_submit', true),
				'shortcode'			=> array('id', true),
				'locked'			=> array('locked', true)
			);
		}

		// Column - Default
		public function column_default($item, $column_name) {

			switch ($column_name) {

				case 'name':
				case 'id':

					return $item[$column_name];

				default:

					return '';
			}
		}

		// Column - Checkbox
		function column_cb($item) {

			return sprintf('<input type="checkbox" name="bulk-ids[]" value="%u" />', $item['id']);
		}

		// Column - Title
		function column_title($item) {

			// URLs
			$id = absint($item['id']);
			$url_edit = WS_Form_Common::get_admin_url('ws-form-edit', $id);

			// Title
			$title = '<strong>';

			if(WS_Form_Common::can_user('edit_form')) {

				$title .= sprintf('<span class="wsf-form-drag"><a href="%s" draggable="false">%s</a></span>', esc_url($url_edit), esc_html($item['label']));

			} else {

				$title .= esc_html($item['label']);
			}

			// Publish pending
			if(self::is_publish_pending($item)) {

				$title .= sprintf(

					'<span class="post-state-publish-pending"> — <span class="post-state">%s</span></span>',
					__('Publish Pending', 'ws-form')
				);
			}

			$title .= '</strong>';

			// Actions
			$status = WS_Form_Common::get_query_var('ws-form-status');
			$actions = array();
			switch($status) {

				case 'trash' :

					if(WS_Form_Common::can_user('delete_form')) {

						$actions['restore'] = 	sprintf('<a href="#" data-action="wsf-restore" data-id="%u">%s</a>', $id, __('Restore', 'ws-form'));
						$actions['delete'] = 	sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Delete Permanently', 'ws-form'));
					}

					break;

				default :

					if(WS_Form_Common::can_user('edit_form')) {

						$actions['edit'] = 	sprintf('<a href="%s">%s</a>', esc_url($url_edit), __('Edit', 'ws-form'));
					}

					if(WS_Form_Common::can_user('create_form')) {

						$actions['copy'] = 	sprintf('<a href="#" data-action="wsf-clone" data-id="%u">%s</a>', $id, __('Clone', 'ws-form'));
					}

					if(WS_Form_Common::can_user('delete_form')) {

						$actions[empty($item['locked']) ? 'trash' : 'trash hidden'] = sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Trash', 'ws-form'));
					}

					$actions['preview'] = sprintf('<a href="%s" target="_blank">%s</a>', esc_url(WS_Form_Common::get_preview_url($id)), __('Preview', 'ws-form'));

					if(WS_Form_Common::styler_enabled() && WS_Form_Common::can_user('edit_form_style')) {

						$actions['style'] = sprintf('<a href="%s" target="_blank">%s</a>', esc_url(WS_Form_Common::get_preview_url($id, false, false, true)), __('Style', 'ws-form'));
					}

					if(WS_Form_Common::can_user('export_form')) {

						$actions['export'] = sprintf('<a href="#" data-action="wsf-export" data-id="%u">%s</a>', $id, __('Export', 'ws-form'));
					}

					if(WS_Form_Common::can_user('read_form')) {

						$actions['locate'] = sprintf('<a href="#" data-action-ajax="wsf-form-locate" data-id="%u">%s</a>', $id, __('Locate', 'ws-form'));
					}
			}

			return $title . self::column_title_folder_path($item) . $this->row_actions($actions);
		}

		// Column - Status
		function _column_media($item) {

			// Title
			$ws_form_form = new WS_Form_Form();
			$status_name = $ws_form_form->db_get_status_name(

				$item['status'],
				self::is_publish_pending($item)
			);

			$toggle_enabled = true;

			switch($item['status']) {

				case 'publish' :

					$toggle_checked = true;

					break;

				case 'trash' :

					$toggle_enabled = false;
					break;

				default :

					$toggle_checked = false;
			}

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { $toggle_enabled = false; }

			$locked = !empty($item['locked']);

			if($toggle_enabled) {

				$toggle_id = 'wsf-status-' . $item['id'];
				$toggle_title = ($locked ? __('Locked. Unlock to change status', 'ws-form') : $status_name);
				$status_html = '<span class="wsf-form-status-switch"><input type="checkbox" id="' . $toggle_id . '" class="wsf-field wsf-switch' . (self::is_publish_pending($item) ? ' wsf-switch-warning' : '') . '" data-id="' . $item['id'] . '" data-action-ajax="wsf-form-status" data-title-locked="' . esc_attr__('Locked. Unlock to change status', 'ws-form') . '"' . ($toggle_checked ? ' checked': '') . ($locked ? ' disabled' : '') . ' /><label id="' . $toggle_id . '-label" for="' . $toggle_id . '" class="wsf-label" title="' . esc_attr($toggle_title) . '">&nbsp;</label></span>';
			} else {

				$status_html = $status_name;
			}

			return '<th scope="row" class="manage-column column-is_active"><span class="wsf-form-status">' . $status_html . '</span></th>';
		}

		// Column - Lock
		function column_locked($item) {

			if($item['status'] === 'trash') { return ''; }

			return WS_Form_Common::get_lock_control_html('form', $item['id'], !empty($item['locked']));
		}

		// Column - Submit Count
		function column_count_submit($item) {

			// URLs
			$id = absint($item['id']);
			$url_submissions = WS_Form_Common::get_admin_url('ws-form-submit&id=' . $id);

			// Get counts
			$count_submit = $item['count_submit'];

			// Build title
			$title = absint($count_submit);

			$disable_count_submit_unread = WS_Form_Common::option_get('disable_count_submit_unread', false);

			if(!$disable_count_submit_unread) {

				$count_submit_unread = $item['count_submit_unread'];
				if($count_submit_unread > 0) {

					$count_submit_unread_html = ($count_submit_unread > 0) ? sprintf(' <span class="wsf-submit-unread"><span title="%1$u new submission%2$s"><span class="update-count">%1$u</span></span></span>', $count_submit_unread, (($count_submit_unread != 1) ? 's' : '')) : '';
					$title .= $count_submit_unread_html;
				}
			}

			if(WS_Form_Common::can_user('read_submission')) {

				$title = sprintf('<a href="%s">%s</a>', esc_url($url_submissions), $title);
			}

			// Actions
			$actions = array();
			if($count_submit > 0) {

				if(WS_Form_Common::can_user('read_submission')) {

					$actions['view'] = sprintf('<a href="%s">%s</a>', esc_url($url_submissions), __('View', 'ws-form'));
				}

				if(WS_Form_Common::can_user('export_submission')) {

					$actions['export'] = sprintf('<a href="%s">%s</a>', esc_url($url_submissions), __('Export', 'ws-form'));
				}
			}

			return $title . $this->row_actions($actions);
		}

		// Column - Shortcode
		function column_shortcode($item) {

			$id = absint($item['id']);

			// Title
			$title = sprintf('<div class="wsf-shortcode"><code data-action="wsf-clipboard"%s>%s</code></div>',WS_Form_Common::esc_attr_tooltip(__('Click to Copy', 'ws-form'), 'left'), esc_html(WS_Form_Common::shortcode($id)));

			return $title;
		}

		// Publish pending
		function is_publish_pending($item) {

			return (

				($item['status'] == 'publish') &&
				($item['checksum'] != $item['published_checksum']) &&
				WS_Form_Common::can_user('publish_form')
			);
		}

		// Views
		function get_views(){

			// Get data from API
			$ws_form_form = new WS_Form_Form();
			$folder_id = self::current_folder_id();

			$views = array();
			$current = WS_Form_Common::get_query_var('ws-form-status', 'all');
			$all_url = remove_query_arg(array('ws-form-status', 'paged'));

			// All link
			$count_all = $ws_form_form->db_get_count_by_status('', $folder_id);
			if($count_all) {

				$views['all'] = sprintf(

					'<a href="%s"%s>%s <span class="count">%u</span></a>',
					esc_url(add_query_arg('ws-form-status', 'all', $all_url)),
					($current === 'all' ? ' class="current"' :''),
					__('All', 'ws-form'),
					$count_all
				);
			}

			// Draft link
			$count_draft = $ws_form_form->db_get_count_by_status('draft', $folder_id);
			if($count_draft) {

				$views['draft'] = sprintf(

					'<a href="%s"%s>%s <span class="count">%u</span></a>',
					esc_url(add_query_arg('ws-form-status', 'draft', $all_url)),
					($current === 'draft' ? ' class="current"' :''),
					__('Draft', 'ws-form'),
					$count_draft
				);
			}

			// Published link
			$count_publish = $ws_form_form->db_get_count_by_status('publish', $folder_id);
			if($count_publish) {

				$views['publish'] = sprintf(

					'<a href="%s"%s>%s <span class="count">%u</span></a>',
					esc_url(add_query_arg('ws-form-status', 'publish', $all_url)),
					($current === 'publish' ? ' class="current"' :''),
					__('Published', 'ws-form'),
					$count_publish
				);
			}

			// Trashed link
			$count_trash = $ws_form_form->db_get_count_by_status('trash', $folder_id);
			if($count_trash) {

				$views['trash'] = sprintf(

					'<a href="%s"%s>%s <span class="count">%u</span></a>',
					esc_url(add_query_arg('ws-form-status', 'trash', $all_url)),
					($current === 'trash' ? ' class="current"' :''),
					__('Trash', 'ws-form'),
					$count_trash
				);
			}

			return $views;
		}

		// Get data
		function get_data($per_page = 20, $page_number = 1) {

			// Build JOIN
			$join = '';

			// Build WHERE
			$where_array = array();
			$status = WS_Form_Common::get_query_var('ws-form-status');
			if($status == '') { $status == 'all'; }
			if(WS_Form_Common::check_form_status($status) == '') { $status = 'all'; }
			if($status != 'all') {
	
				// Filter by status
				$where_array[] = sprintf('status = "%s"', esc_sql($status));

			} else {

				// Show everything but trash (All)
				$where_array[] = "NOT(status = 'trash')";
			}

			$folder_id = self::current_folder_id();
			if($folder_id !== null) {

				$where_array[] = sprintf('folder_id = %u', $folder_id);
			}

			// Check for search
			$s = WS_Form_Common::get_query_var_nonce('s');
			if(!empty($s)) {

				$where_array[] = sprintf("label LIKE '%%%s%%'", esc_sql($s));
			}

			$where = implode(' AND ', $where_array);

			// Build ORDER BY
			$order_by = '';

			$user = wp_get_current_user();

			// Order by
			$meta_key_orderby = $user->user_nicename . '_list_table_form_orderby';
			$order_by_query_var = WS_Form_Common::check_form_order_by(WS_Form_Common::get_query_var('orderby'));
			if(!empty($order_by_query_var)) { WS_Form_Common::option_set($meta_key_orderby, $order_by_query_var); }
			$order_by_option = WS_Form_Common::check_form_order_by(WS_Form_Common::option_get($meta_key_orderby));
			if(isset($_GET) && !empty($order_by_option)) { $_GET['orderby'] = $order_by_option; }	// phpcs:ignore WordPress.Security.NonceVerification

			// Order
			$meta_key_order = $user->user_nicename . '_list_table_form_order';
			$order_query_var = WS_Form_Common::check_form_order(WS_Form_Common::get_query_var('order'));
			if(!empty($order_query_var)) { WS_Form_Common::option_set($meta_key_order, $order_query_var); }
			$order_option = WS_Form_Common::check_form_order(WS_Form_Common::option_get($meta_key_order));
			if(isset($_GET) && !empty($order_option)) { $_GET['order'] = $order_option; }	// phpcs:ignore WordPress.Security.NonceVerification

			if(!empty($order_by_option)) {

				$order_by = esc_sql($order_by_option);

				$order = !empty($order_option) ? $order_option : 'ASC';
				if(!in_array(strtoupper($order), array('ASC', 'DESC'), true)) { $order = 'ASC'; }
				$order_by .= ' ' . esc_sql(strtoupper($order));

			} else {

				$order_by = 'label ASC';
			}

			// Build LIMIT
			$limit = $per_page;

			// Build OFFSET
			$offset = ($page_number - 1) * $per_page;

			// Get data from API
			$ws_form_form = new WS_Form_Form();
			$result = $ws_form_form->db_read_all($join, $where, $order_by, $limit, $offset);

			return $result;
		}

		// Prepare items
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			$per_page     = $this->get_items_per_page('ws_form_forms_per_page', 20);
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args(array(

				'total_items' => $total_items,
				'per_page'    => $per_page
			));

			$this->items = self::get_data($per_page, $current_page);
		}

		// Bulk actions - Prepare
		public function get_bulk_actions() {

			$actions = array();
			$status = WS_Form_Common::get_query_var('ws-form-status');

			switch($status) {

				case 'trash' :

					if(WS_Form_Common::can_user('delete_form')) {

						$actions['wsf-bulk-restore'] = __('Restore', 'ws-form');
						$actions['wsf-bulk-delete'] = __('Delete Permanently', 'ws-form');
					}
					break;

				default:

					if(WS_Form_Common::can_user('lock_form')) {

						$actions['wsf-bulk-lock'] = __('Lock', 'ws-form');
					}

					if(WS_Form_Common::can_user('unlock_form')) {

						$actions['wsf-bulk-unlock'] = __('Unlock', 'ws-form');
					}

					if(WS_Form_Common::can_user('delete_form')) {

						$actions['wsf-bulk-delete'] = __('Move to Trash', 'ws-form');
					}
			}

			self::bulk_actions_folders($actions);

			return $actions;
		}

		// Extra table nav
		function extra_tablenav( $which ) {

			$status = WS_Form_Common::get_query_var('ws-form-status');

			switch($status) {

				case 'trash' :

					if(WS_Form_Common::can_user('delete_form')) {
?>
		<div class="alignleft actions">
<?php 
			submit_button(__('Empty Trash', 'ws-form'), 'apply', 'delete_all', false );
?>
		</div>
<?php
					}

					break;
			}
		}

		// Set primary column
		public function list_table_primary_column($default, $screen) {

		    if($screen === 'toplevel_page_ws-form') { $default = 'title'; }

		    return $default;
		}

		// Get record count
		public function record_count() {

			if(empty(WS_Form_Common::get_query_var_nonce('s'))) {

				$ws_form_form = new WS_Form_Form();

				$current = WS_Form_Common::get_query_var('ws-form-status', 'all');
				if($current === 'all') { $current = ''; }

				return $ws_form_form->db_get_count_by_status($current, self::current_folder_id());

			} else {

				return count(self::get_data(0, 0));
			}
		}

		// No records
		public function no_items() {

			esc_html_e('No forms available.', 'ws-form');
		}

		// Remember the open folder. Forms menu (no folder in the URL) returns to it.
		public static function remember_folder() {

			if(WS_Form_Common::get_request_method() !== 'GET') { return; }

			$user_id = get_current_user_id();
			if($user_id < 1) { return; }

			$meta_key = 'ws_form_last_form_folder';
			$folder = WS_Form_Common::get_query_var('ws-form-folder', false);

			if($folder !== false) {

				if(($folder === '') || ($folder === 'all')) {

					update_user_meta($user_id, $meta_key, 'all');
					return;
				}

				if($folder === 'unfiled') {

					update_user_meta($user_id, $meta_key, 'unfiled');
					return;
				}

				$folder_id = absint($folder);
				$ws_form_form_folder = new WS_Form_Form_Folder();

				if(($folder_id > 0) && $ws_form_form_folder->db_get($folder_id)) {

					update_user_meta($user_id, $meta_key, (string) $folder_id);

				} else {

					delete_user_meta($user_id, $meta_key);
				}

				return;
			}

			$saved = get_user_meta($user_id, $meta_key, true);
			$saved_folder = '';

			if($saved === 'unfiled') {

				$saved_folder = 'unfiled';

			} else {

				$saved_id = absint($saved);
				if($saved_id < 1) { return; }

				$ws_form_form_folder = new WS_Form_Form_Folder();
				if(!$ws_form_form_folder->db_get($saved_id)) {

					delete_user_meta($user_id, $meta_key);
					return;
				}

				$saved_folder = (string) $saved_id;
			}

			$query = array('ws-form-folder' => $saved_folder);

			foreach(array('ws-form-status', 's', 'paged', 'orderby', 'order') as $key) {

				$value = WS_Form_Common::get_query_var($key, false);
				if(($value === false) || ($value === '')) { continue; }
				$query[$key] = $value;
			}

			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Admin URL built above
			wp_safe_redirect(WS_Form_Common::get_admin_url('ws-form', false, http_build_query($query)));
			exit;
		}

		// Selected folder. null is All Forms. 0 is Unfiled. Unknown ids fall back to All Forms.
		public static function current_folder_id() {

			$folder = WS_Form_Common::get_query_var('ws-form-folder', 'all');
			if(($folder === '') || ($folder === 'all')) { return null; }
			if($folder === 'unfiled') { return 0; }

			$folder_id = absint($folder);
			if($folder_id < 1) { return null; }

			$ws_form_form_folder = new WS_Form_Form_Folder();
			if(!$ws_form_form_folder->db_get($folder_id)) { return null; }

			return $folder_id;
		}

		// Path under the name on All Forms
		public static function column_title_folder_path($item) {

			if(self::current_folder_id() !== null) { return ''; }

			$folder_id = isset($item['folder_id']) ? absint($item['folder_id']) : 0;
			if($folder_id < 1) { return ''; }

			$paths = self::folder_paths();
			if(!isset($paths[$folder_id])) { return ''; }

			return '<div class="wsf-form-folder-path">' . esc_html($paths[$folder_id]) . '</div>';
		}

		public static function folder_paths() {

			static $paths = null;

			if($paths === null) {

				$ws_form_form_folder = new WS_Form_Form_Folder();
				$paths = $ws_form_form_folder->paths();
			}

			return $paths;
		}

		// One bulk action. The folder is chosen in a modal.
		public static function bulk_actions_folders(&$actions) {

			if(!WS_Form_Common::can_user('edit_form')) { return; }

			$ws_form_form_folder = new WS_Form_Form_Folder();
			if(!count($ws_form_form_folder->tree_rows())) { return; }

			$actions['wsf-bulk-folder'] = __('Move to Folder', 'ws-form');
		}
	}
