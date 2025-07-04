<?php

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

			return $columns;
		}

		// Get sortable columns
		public function get_sortable_columns() {

			return array(

				'media'				=> array('status', true),		// Used 'media' as opposed to 'status' because WordPress considers that a special keyword and excludes it from the screen options column checkboxes
				'title'				=> array('label', true),		// Used 'title' as opposed to 'label' because WordPress considers that a special keyword and excludes it from the screen options column checkboxes
				'id'				=> array('id', true),
				'count_submit'		=> array('count_submit', true),
				'shortcode'			=> array('id', true)
			);
		}

		// Column - Default
		public function column_default($item, $column_name) {

			switch ($column_name) {

				case 'name':
				case 'id':

					return $item[$column_name];
					break;

				default:

					return print_r($item, true); //Show the whole array for troubleshooting purposes
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

				$title .= sprintf('<a href="%s">%s</a>', esc_url($url_edit), esc_html($item['label']));

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

						$actions['trash'] = sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Trash', 'ws-form'));
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

			return $title . $this->row_actions($actions);
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

			if($toggle_enabled) {

				$toggle_id = 'wsf-status-' . $item['id'];
				$status_html = '<input type="checkbox" id="' . $toggle_id . '" class="wsf-field wsf-switch' . (self::is_publish_pending($item) ? ' wsf-switch-warning' : '') . '" data-id="' . $item['id'] . '" data-action-ajax="wsf-form-status"' . ($toggle_checked ? ' checked': '') . ' /><label id="' . $toggle_id . '-label" for="' . $toggle_id . '" class="wsf-label" title="' . $status_name . '">&nbsp;</label>';
			} else {

				$status_html = $status_name;
			}

			return '<th scope="row" class="manage-column column-is_active">' . $status_html . '</th>';
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
			$title = sprintf('<div class="wsf-shortcode"><code data-action="wsf-clipboard"%s>%s</code></div>',WS_Form_Common::tooltip(__('Click to Copy', 'ws-form'), 'left'), esc_html(WS_Form_Common::shortcode($id)));

			return $title;
		}

		// Publish pending
		function is_publish_pending($item) {

			return (

				($item['status'] == 'publish') &&
				($item['checksum'] != $item['published_checksum']) &&
				WS_Form_Common::user_must('publish_form')
			);
		}

		// Views
		function get_views(){

			// Get data from API
			$ws_form_form = new WS_Form_Form();

			$views = array();
			$current = WS_Form_Common::get_query_var('ws-form-status', 'all');
			$all_url = remove_query_arg(array('ws-form-status', 'paged'));

			// All link
			$count_all = $ws_form_form->db_get_count_by_status();
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
			$count_draft = $ws_form_form->db_get_count_by_status('draft');
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
			$count_publish = $ws_form_form->db_get_count_by_status('publish');
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
			$count_trash = $ws_form_form->db_get_count_by_status('trash');
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

					if(WS_Form_Common::can_user('delete_form')) {

						$actions['wsf-bulk-delete'] = __('Move to Trash', 'ws-form');
					}
			}

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

				return $ws_form_form->db_get_count_by_status($current);

			} else {

				return count(self::get_data(0, 0));
			}
		}

		// No records
		public function no_items() {

			esc_html_e('No forms available.', 'ws-form');
		}
	}
