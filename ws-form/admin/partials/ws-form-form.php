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
<a class="button button-primary" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>" title="<?php esc_attr_e('Add New', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add New', 'ws-form'); ?></a>
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
		<a class="button button-primary wsf-submissions-empty-action" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php WS_Form_Common::render_icon_16_svg('plus'); ?> <?php esc_html_e('Add New', 'ws-form'); ?></a>
<?php
		}
?>
	</div>
</div>
<!-- /Forms Empty State -->
<?php
	} else {
?>
<!-- Form Table -->
<form id="wsf-form-list-table" method="post">
<?php

		// Search
		$this->ws_form_wp_list_table_form_obj->search_box('Search', 'search');

		// Views
		$this->ws_form_wp_list_table_form_obj->views();
?>
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form">
<?php

		// Display
		$this->ws_form_wp_list_table_form_obj->display();
?>
</form>
<!-- /Form Table -->
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
