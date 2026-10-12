<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Form - Admin Page

	// Loader
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<?php

	ob_start();

	if(WS_Form_Common::can_user('create_form')) {
?>
<a class="button button-primary" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>" title="<?php esc_attr_e('Add Form', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add Form', 'ws-form'); ?></a>
<?php
	}

	if(WS_Form_Common::can_user('edit_form')) {
?>
<button type="button" class="button" data-action="wsf-folder-add" data-parent-id="0"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add Folder', 'ws-form'); ?></button>
<?php
	}

	if(WS_Form_Common::can_user('import_form')) {
?>
<button type="button" class="button" data-action-button="wsf-form-upload"><?php WS_Form_Common::render_icon_16_svg('upload'); ?> <?php esc_html_e('Import', 'ws-form'); ?></button>
<?php
	}

	WS_Form_Common::admin_header(__('Forms', 'ws-form'), ob_get_clean());

	// Review nag
	WS_Form_Common::review();

	// Import
	$this->render_object_upload_dropzone('import_form');

	// Prepare
	$this->ws_form_wp_list_table_form_obj->prepare_items();

	// Empty state — zero forms in the database (including trash)
	$ws_form_form = new WS_Form_Form();
	$ws_form_has_forms = (
		($ws_form_form->db_get_count_by_status() > 0) ||
		($ws_form_form->db_get_count_by_status('trash') > 0)
	);

	// Empty state — no forms in the database (including trash)
	if(!$ws_form_has_forms) {

		$ws_form_empty_icon = '<svg class="wsf-submissions-empty-icon-doc" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true" focusable="false"><rect x="14" y="8" width="36" height="48" rx="4" fill="none" stroke="currentColor" stroke-width="2"/><line x1="22" y1="22" x2="42" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="30" x2="38" y2="30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="38" x2="42" y2="38" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="46" x2="34" y2="46" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
?>
<!-- Forms Empty State -->
<div id="wsf-forms-empty" class="wsf-submissions-empty">
	<div class="wsf-submissions-empty-inner">
		<div class="wsf-submissions-empty-icon wsf-submissions-empty-icon-add" aria-hidden="true">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup
				echo $ws_form_empty_icon;
			?>
			<span class="wsf-submissions-empty-icon-badge">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="10" height="10" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8 3v10M3 8h10"/></svg>
			</span>
		</div>
		<h2 class="wsf-submissions-empty-title"><?php esc_html_e('No Forms Yet', 'ws-form'); ?></h2>
		<p class="wsf-submissions-empty-copy"><?php esc_html_e('Create your first form to get started.', 'ws-form'); ?></p>
<?php
		if(WS_Form_Common::can_user('create_form')) {
?>
		<a class="button button-primary wsf-submissions-empty-action" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add Form', 'ws-form'); ?></a>
<?php
		}
?>
	</div>
</div>
<!-- /Forms Empty State -->
<?php
	} else {

		$ws_form_form_folder = new WS_Form_Form_Folder();
		$ws_form_can_edit_folder = WS_Form_Common::can_user('edit_form');
		$ws_form_folder_tree = $ws_form_form_folder->tree_rows();
		$ws_form_folders_visible = count($ws_form_folder_tree);
		$ws_form_folder_current = WS_Form_WP_List_Table_Form::current_folder_id();
		$ws_form_folder_paths = array();
		foreach($ws_form_folder_tree as $ws_form_folder_row) {

			$ws_form_folder_paths[$ws_form_folder_row['id']] = $ws_form_folder_row['path'];
		}
		$ws_form_folder_base = remove_query_arg(array('ws-form-folder', 'paged'));
		$ws_form_folder_width = absint(WS_Form_Common::option_get('sidebar_width_form_list', WS_FORM_FORM_FOLDERS_WIDTH_DEFAULT));
		if($ws_form_folder_width < WS_FORM_FORM_FOLDERS_WIDTH_MIN) { $ws_form_folder_width = WS_FORM_FORM_FOLDERS_WIDTH_MIN; }
		if($ws_form_folder_width > WS_FORM_FORM_FOLDERS_WIDTH_MAX) { $ws_form_folder_width = WS_FORM_FORM_FOLDERS_WIDTH_MAX; }
		$ws_form_folder_error = WS_Form_Common::get_query_var('wsf_folder_error');
		$ws_form_folder_all_count = $ws_form_form->db_get_count_by_status();
		$ws_form_folder_unfiled_count = $ws_form_form->db_get_count_by_status('', 0);
		$ws_form_folder_current_label = __('All Forms', 'ws-form');
		$ws_form_folder_empty_label = '';
		$ws_form_folder_empty = false;
		if($ws_form_folder_current === 0) {

			$ws_form_folder_current_label = __('Unfiled', 'ws-form');
			$ws_form_folder_empty_label = $ws_form_folder_current_label;
			$ws_form_folder_empty = ($ws_form_folder_unfiled_count === 0);

		} else if($ws_form_folder_current !== null) {

			$ws_form_folder_current_label = isset($ws_form_folder_paths[$ws_form_folder_current]) ? $ws_form_folder_paths[$ws_form_folder_current] : __('All Forms', 'ws-form');
			$ws_form_folder_empty_label = $ws_form_folder_current_label;
			$ws_form_folder_empty = true;
			foreach($ws_form_folder_tree as $ws_form_folder_row) {

				if($ws_form_folder_row['id'] === $ws_form_folder_current) {

					$ws_form_folder_empty_label = $ws_form_folder_row['label'];
					$ws_form_folder_empty = ($ws_form_folder_row['form_count'] === 0);
					break;
				}
			}
		}

		// Trash in an otherwise empty folder still uses the table
		if($ws_form_folder_empty && $this->ws_form_wp_list_table_form_obj->has_items()) {

			$ws_form_folder_empty = false;
		}
?>
<div id="wsf-form-list" class="wsf-form-list<?php echo $ws_form_folders_visible ? '' : ' wsf-form-folders-hidden'; ?>"<?php echo $ws_form_folders_visible ? ' style="--wsf-form-folders-width: ' . absint($ws_form_folder_width) . 'px;"' : ''; ?>>
<?php
		if($ws_form_folders_visible) {
?>
<div id="wsf-form-folders" data-width-min="<?php echo absint(WS_FORM_FORM_FOLDERS_WIDTH_MIN); ?>" data-width-max="<?php echo absint(WS_FORM_FORM_FOLDERS_WIDTH_MAX); ?>"<?php echo $ws_form_can_edit_folder ? ' data-can-edit="1"' : ''; ?>>
<div class="wsf-sidebar-resize"></div>
<div class="wsf-form-folders-header">
<span class="wsf-form-folders-heading"><?php esc_html_e('Folders', 'ws-form'); ?></span>
</div>
<nav class="wsf-form-folders-tree" aria-label="<?php esc_attr_e('Folders', 'ws-form'); ?>">
<a class="wsf-form-folder<?php echo ($ws_form_folder_current === null) ? ' wsf-form-folder-current' : ''; ?>" href="<?php echo esc_url(add_query_arg('ws-form-folder', 'all', $ws_form_folder_base)); ?>" style="padding-inline-start: 14px;">
<span class="wsf-form-folder-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg></span>
<span class="wsf-form-folder-label"><?php esc_html_e('All Forms', 'ws-form'); ?></span>
<span class="wsf-form-folder-count"><?php echo absint($ws_form_folder_all_count); ?></span>
</a>
<a class="wsf-form-folder<?php echo ($ws_form_folder_current === 0) ? ' wsf-form-folder-current' : ''; ?>" href="<?php echo esc_url(add_query_arg('ws-form-folder', 'unfiled', $ws_form_folder_base)); ?>" data-folder-id="0" style="padding-inline-start: 14px;">
<span class="wsf-form-folder-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg></span>
<span class="wsf-form-folder-label"><?php esc_html_e('Unfiled', 'ws-form'); ?></span>
<span class="wsf-form-folder-count"><?php echo absint($ws_form_folder_unfiled_count); ?></span>
</a>
<div class="wsf-form-folders-rule" role="separator"></div>
<?php
			foreach($ws_form_folder_tree as $ws_form_folder_row) {

				$ws_form_folder_pad = 14 + ($ws_form_folder_row['depth'] * 16);
				$ws_form_folder_is_current = ($ws_form_folder_current === $ws_form_folder_row['id']);
?>
<div class="wsf-form-folder-row<?php echo $ws_form_folder_is_current ? ' wsf-form-folder-current' : ''; ?>" style="padding-inline-start: <?php echo absint($ws_form_folder_pad); ?>px;" data-folder-id="<?php echo absint($ws_form_folder_row['id']); ?>">
<a class="wsf-form-folder" href="<?php echo esc_url(add_query_arg('ws-form-folder', $ws_form_folder_row['id'], $ws_form_folder_base)); ?>" data-folder-id="<?php echo absint($ws_form_folder_row['id']); ?>">
<span class="wsf-form-folder-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg></span>
<span class="wsf-form-folder-label"><?php echo esc_html($ws_form_folder_row['label']); ?></span>
<span class="wsf-form-folder-count"><?php echo absint($ws_form_folder_row['form_count']); ?></span>
</a>
<?php
				if($ws_form_can_edit_folder) {

					$ws_form_folder_parent_label = '';
					if($ws_form_folder_row['parent_id'] > 0 && isset($ws_form_folder_paths[$ws_form_folder_row['parent_id']])) {

						$ws_form_folder_parent_label = $ws_form_folder_paths[$ws_form_folder_row['parent_id']];
					}
?>
<span class="wsf-form-folder-actions">
<button type="button" data-action="wsf-folder-rename" data-id="<?php echo absint($ws_form_folder_row['id']); ?>" data-label="<?php echo esc_attr($ws_form_folder_row['label']); ?>"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Rename', 'ws-form'), 'top-right'); ?>><?php WS_Form_Common::render_icon_16_svg('edit'); ?></button>
<?php
					if($ws_form_folder_row['depth'] < WS_FORM_FORM_FOLDERS_DEPTH_MAX) {
?>
<button type="button" data-action="wsf-folder-add" data-parent-id="<?php echo absint($ws_form_folder_row['id']); ?>"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Add subfolder', 'ws-form'), 'top-right'); ?>><?php WS_Form_Common::render_icon_16_svg('plus'); ?></button>
<?php
					}
?>
<button type="button" data-action="wsf-folder-delete" data-id="<?php echo absint($ws_form_folder_row['id']); ?>" data-parent-id="<?php echo absint($ws_form_folder_row['parent_id']); ?>" data-parent-label="<?php echo esc_attr($ws_form_folder_parent_label); ?>" data-has-children="<?php echo $ws_form_folder_row['has_children'] ? '1' : '0'; ?>"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Delete', 'ws-form'), 'top-right'); ?>><?php WS_Form_Common::render_icon_16_svg('delete'); ?></button>
</span>
<?php
				}
?>
</div>
<?php
			}
?>
</nav>
</div>
<?php
		}
?>
<div class="wsf-form-list-main">
<?php
		if($ws_form_folder_error !== '') {
?>
<div class="notice notice-error"><p><?php echo esc_html($ws_form_form_folder->message($ws_form_folder_error)); ?></p></div>
<?php
		}

		if($ws_form_folder_empty) {

			$ws_form_empty_icon = '<svg class="wsf-submissions-empty-icon-doc" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
?>
<div class="wsf-form-folder-empty wsf-submissions-empty">
	<div class="wsf-submissions-empty-inner">
		<div class="wsf-submissions-empty-icon" aria-hidden="true">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup
				echo $ws_form_empty_icon;
			?>
		</div>
		<h2 class="wsf-submissions-empty-title"><?php echo esc_html($ws_form_folder_empty_label); ?></h2>
		<p class="wsf-submissions-empty-copy"><?php esc_html_e('This folder is empty.', 'ws-form'); ?></p>
	</div>
</div>
<?php
		} else {

		if($ws_form_can_edit_folder && count($ws_form_folder_tree)) {
?>
<select id="wsf-bulk-folder-source" hidden>
<option value="0"><?php esc_html_e('Unfiled', 'ws-form'); ?></option>
<?php
			foreach($ws_form_folder_tree as $ws_form_folder_row) {

				$ws_form_folder_option = str_repeat('- ', $ws_form_folder_row['depth']) . $ws_form_folder_row['label'];
?>
<option value="<?php echo absint($ws_form_folder_row['id']); ?>"><?php echo esc_html($ws_form_folder_option); ?></option>
<?php
			}
?>
</select>
<?php
		}
?>
<!-- Form Table -->
<form id="wsf-form-list-table" method="post">
<?php

		// Search
		$this->ws_form_wp_list_table_form_obj->search_box('Search', 'search');

		if($ws_form_folders_visible) {
?>
<div class="wsf-form-list-filters">
<span class="wsf-form-folder-breadcrumb"><strong><?php echo esc_html($ws_form_folder_current_label); ?></strong></span>
<?php
			$this->ws_form_wp_list_table_form_obj->views();
?>
</div>
<?php
		} else {

			$this->ws_form_wp_list_table_form_obj->views();
		}
?>
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form">
<input type="hidden" name="ws-form-folder" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var('ws-form-folder')); ?>">
<?php

		// Display
		$this->ws_form_wp_list_table_form_obj->display();
?>
</form>
<!-- /Form Table -->
<?php
		}
?>
</div>
</div>
<?php
	}
?>

<!-- Form Actions -->
<form action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" id="wsf-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form">
<input type="hidden" id="wsf-action" name="action" value="">
<input type="hidden" id="wsf-id" name="id" value="">
<input type="hidden" name="paged" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('paged', '', false, false, true, 'POST')); ?>">
<input type="hidden" name="ws-form-status" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('ws-form-status', '', false, false, true, 'POST')); ?>">
<input type="hidden" name="ws-form-folder" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var('ws-form-folder')); ?>">
<input type="hidden" id="wsf-folder-label" name="folder_label" value="">
<input type="hidden" id="wsf-folder-parent-id" name="folder_parent_id" value="">
</form>
<!-- /Form Actions -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();

			// Partial initialization
			wsf_obj.init_partial();

			// Initialize tooltips
			wsf_obj.tooltips();

			// Initialize form table
			wsf_obj.wp_list_table_form();
		});

	})(jQuery);

</script>
</div>
