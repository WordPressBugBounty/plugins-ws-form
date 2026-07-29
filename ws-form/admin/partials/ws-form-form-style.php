<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Style - Admin Page

	// Loader
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<?php

	ob_start();

	if(WS_Form_Common::can_user('create_form_style')) {
?>
<a class="button button-primary" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-style-add')); ?>" title="<?php esc_attr_e('Add New', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add New', 'ws-form'); ?></a>
<?php
	}

	if(WS_Form_Common::can_user('import_form_style')) {
?>
<button type="button" class="button" data-action-button="wsf-style-upload" title="<?php esc_attr_e('Import', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('upload'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Import', 'ws-form'); ?></span></button>
<?php
	}

	WS_Form_Common::admin_header(__('Styles', 'ws-form'), ob_get_clean());

	// Review nag
	WS_Form_Common::review();

	// Import
	$this->render_object_upload_dropzone('import_form_style');
?>
<!-- Style Table -->
<form id="wsf-style-list-table" method="post">
<?php

	// Prepare
	$this->ws_form_wp_list_table_style_obj->prepare_items();

	// Search
	$this->ws_form_wp_list_table_style_obj->search_box('Search', 'search');

	// Views
	$this->ws_form_wp_list_table_style_obj->views();
?>
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-style">
<?php

	// Display
	$this->ws_form_wp_list_table_style_obj->display();
?>
</form>
<!-- /Form Table -->

<!-- Form Actions -->
<form action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" id="wsf-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-style">
<input type="hidden" id="wsf-action" name="action" value="">
<input type="hidden" id="wsf-id" name="id" value="">
<input type="hidden" name="paged" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('paged', '', false, false, true, 'POST')); ?>">
<input type="hidden" name="ws-style-status" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('ws-style-status', '', false, false, true, 'POST')); ?>">
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

			// Initialize style table
			wsf_obj.wp_list_table_style();
		});

	})(jQuery);

</script>

</div>
