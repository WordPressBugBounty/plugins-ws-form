<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Form - Submissions - Admin Page
	$ws_form_form_id = absint($this->ws_form_wp_list_table_submit_obj->form_id);

	// Loader
	WS_Form_Common::loader();
?>
<div id="poststuff">
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?> wsf-sidebar-closed">

<?php

	ob_start();

	if($ws_form_form_id > 0) {

		// User capability check
		if(WS_Form_Common::can_user('edit_form')) {
?>
<a class="button" href="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php?page=ws-form-edit&id=' . $ws_form_form_id)); ?>" title="<?php esc_attr_e('Edit Form', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('edit'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Edit Form', 'ws-form'); ?></span></a>
<?php
		}
?>
<a class="button" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_preview_url($ws_form_form_id)); ?>" target="_blank" title="<?php esc_attr_e('Preview Form', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Preview Form', 'ws-form'); ?></span></a>
<?php

		if($this->ws_form_wp_list_table_submit_obj->record_count() > 0) {

			// User capability check
			if(WS_Form_Common::can_user('export_submission')) {
?>
<button type="button" data-action="wsf-export-all" class="button" title="<?php esc_attr_e('Export CSV', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('download'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Export CSV', 'ws-form'); ?></span></button>
<?php
			}
		}
	}

	// Hook for additional buttons
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
	do_action('wsf_form_submit_nav_left', $ws_form_form_id);

	WS_Form_Common::admin_header(__('Submissions', 'ws-form'), ob_get_clean());

	// Review nag
	WS_Form_Common::review();

	// Prepare
	$this->ws_form_wp_list_table_submit_obj->prepare_items();

	// Ordering
	$ws_form_order_query_var = WS_Form_Common::get_query_var('order', '');
	$ws_form_order_by_query_var = WS_Form_Common::get_query_var('orderby', '');

	// Empty-state panels vs normal table
	// FORMS = draft/publish forms exist; FORM ID = selected form; SUBMISSIONS = selected form has submissions
	$ws_form_form_count = $this->ws_form_wp_list_table_submit_obj->form_count_by_status();
	$ws_form_has_forms = ($ws_form_form_count > 0);
	$ws_form_has_form_id = ($ws_form_form_id > 0);
	$ws_form_has_submissions = $this->ws_form_wp_list_table_submit_obj->form_has_submissions($ws_form_form_id);

	$ws_form_submit_empty_panel = false;

	// There are no forms
	if(!$ws_form_has_forms) {

		$ws_form_submit_empty_panel = 'a';

	// There is no form selected
	} else if(!$ws_form_has_form_id) {

		$ws_form_submit_empty_panel = 'b';

	// There are no submissions for the selected form
	} else if($ws_form_has_form_id && !$ws_form_has_submissions) {

		$ws_form_submit_empty_panel = 'c';
	}

	// Empty state panels
	if($ws_form_submit_empty_panel !== false) {

		// Forms list for Panel B / Panel C option B
		$ws_form_empty_forms = false;
		if(
			($ws_form_submit_empty_panel === 'b') ||
			(($ws_form_submit_empty_panel === 'c') && ($ws_form_form_count > 1))
		) {

			$ws_form_empty_forms = $this->ws_form_wp_list_table_submit_obj->get_forms_for_empty_state();
		}
?>
<!-- Submissions Empty State -->
<div id="wsf-submissions-empty" class="wsf-submissions-empty wsf-submissions-empty-panel-<?php WS_Form_Common::echo_esc_attr($ws_form_submit_empty_panel); ?>">
	<div class="wsf-submissions-empty-inner">
<?php
		// Shared document icon (Panel A adds plus badge via CSS/SVG wrapper)
		$ws_form_empty_icon = '<svg class="wsf-submissions-empty-icon-doc" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true" focusable="false"><rect x="14" y="8" width="36" height="48" rx="4" fill="none" stroke="currentColor" stroke-width="2"/><line x1="22" y1="22" x2="42" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="30" x2="38" y2="30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="38" x2="42" y2="38" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="46" x2="34" y2="46" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

		switch($ws_form_submit_empty_panel) {

			case 'a' :
?>
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
		<p class="wsf-submissions-empty-copy"><?php esc_html_e('Create your first form to start collecting submissions.', 'ws-form'); ?></p>
<?php
				if(WS_Form_Common::can_user('create_form')) {
?>
		<a class="button button-primary wsf-submissions-empty-action" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php esc_html_e('+ Add Form', 'ws-form'); ?></a>
<?php
				}
				break;

			case 'b' :
?>
		<div class="wsf-submissions-empty-icon" aria-hidden="true">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup
				echo $ws_form_empty_icon;
			?>
		</div>
		<h2 class="wsf-submissions-empty-title"><?php esc_html_e('Select a Form', 'ws-form'); ?></h2>
		<p class="wsf-submissions-empty-copy"><?php esc_html_e('Choose a form below to view its submissions.', 'ws-form'); ?></p>
		<form method="get" class="wsf-submissions-empty-form" action="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php')); ?>">
			<input type="hidden" name="page" value="ws-form-submit">
			<select id="wsf-submissions-empty-form-id" class="wsf-submissions-empty-select" name="id" aria-label="<?php esc_attr_e('Select a form...', 'ws-form'); ?>">
				<option value=""><?php esc_html_e('Select a form...', 'ws-form'); ?></option>
<?php
				if(!empty($ws_form_empty_forms)) {

					foreach($ws_form_empty_forms as $ws_form_empty_form) {

						$ws_form_empty_form_count_submit = absint($ws_form_empty_form['count_submit']);
?>
				<option value="<?php WS_Form_Common::echo_esc_attr(absint($ws_form_empty_form['id'])); ?>"><?php

						WS_Form_Common::echo_esc_html(sprintf(

							'%s (%s: %u)',
							$ws_form_empty_form['label'],
							__('ID', 'ws-form'),
							$ws_form_empty_form['id']
						));

						WS_Form_Common::echo_esc_html(' - ' . sprintf(

							/* translators: %u: Number of records */
							_n('%u record', '%u records', $ws_form_empty_form_count_submit, 'ws-form'),
							$ws_form_empty_form_count_submit
						));
?></option>
<?php
					}
				}
?>
			</select>
		</form>
<?php
				break;

			case 'c' :

				$ws_form_empty_form_label = '';
				try {

					$ws_form_empty_form_obj = new WS_Form_Form();
					$ws_form_empty_form_obj->id = $ws_form_form_id;
					$ws_form_empty_form_label = $ws_form_empty_form_obj->db_get_label();

				} catch (Exception $e) {

					$ws_form_empty_form_label = '';
				}
?>
		<div class="wsf-submissions-empty-icon" aria-hidden="true">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup
				echo $ws_form_empty_icon;
			?>
		</div>
		<h2 class="wsf-submissions-empty-title"><?php esc_html_e('This Form Has No Submissions Yet', 'ws-form'); ?></h2>
<?php
				if($ws_form_empty_form_label !== '') {
?>
		<p class="wsf-submissions-empty-form-name"><?php WS_Form_Common::echo_esc_html($ws_form_empty_form_label); ?></p>
<?php
				}
?>
		<div class="wsf-submissions-empty-choices">
			<div class="wsf-submissions-empty-choice">
				<p class="wsf-submissions-empty-choice-copy"><?php esc_html_e('Preview this form and submit a test entry.', 'ws-form'); ?></p>
				<a class="button button-primary wsf-submissions-empty-action" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_preview_url($ws_form_form_id)); ?>" target="_blank"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <?php esc_html_e('Preview Form', 'ws-form'); ?></a>
			</div>
<?php
				if($ws_form_empty_forms !== false) {
?>
			<div class="wsf-submissions-empty-choice-divider" aria-hidden="true"><span><?php esc_html_e('or', 'ws-form'); ?></span></div>
			<div class="wsf-submissions-empty-choice">
				<p class="wsf-submissions-empty-choice-copy"><?php esc_html_e('Choose a different form.', 'ws-form'); ?></p>
				<form method="get" class="wsf-submissions-empty-form" action="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php')); ?>">
					<input type="hidden" name="page" value="ws-form-submit">
					<select id="wsf-submissions-empty-form-id" class="wsf-submissions-empty-select" name="id" aria-label="<?php esc_attr_e('Select a form...', 'ws-form'); ?>">
						<option value=""><?php esc_html_e('Select a form...', 'ws-form'); ?></option>
<?php
					foreach($ws_form_empty_forms as $ws_form_empty_form) {

						$ws_form_empty_form_option_id = absint($ws_form_empty_form['id']);
						$ws_form_empty_form_count_submit = absint($ws_form_empty_form['count_submit']);
?>
						<option value="<?php WS_Form_Common::echo_esc_attr($ws_form_empty_form_option_id); ?>"<?php selected($ws_form_empty_form_option_id, $ws_form_form_id); ?>><?php

							WS_Form_Common::echo_esc_html(sprintf(

								'%s (%s: %u)',
								$ws_form_empty_form['label'],
								__('ID', 'ws-form'),
								$ws_form_empty_form_option_id
							));

							WS_Form_Common::echo_esc_html(' - ' . sprintf(

								/* translators: %u: Number of records */
								_n('%u record', '%u records', $ws_form_empty_form_count_submit, 'ws-form'),
								$ws_form_empty_form_count_submit
							));
?></option>
<?php
					}
?>
					</select>
				</form>
			</div>
<?php
				}
?>
		</div>
<?php
				break;
		}

		if(
			($ws_form_submit_empty_panel === 'b') ||
			(($ws_form_submit_empty_panel === 'c') && ($ws_form_empty_forms !== false))
		) {
?>
		<script>

			(function() {

				var select = document.getElementById('wsf-submissions-empty-form-id');
				if(!select) { return; }

				select.addEventListener('change', function() {

					if(this.value === '') { return; }
					this.form.submit();
				});
			})();

		</script>
<?php
		}
?>
	</div>
</div>
<!-- /Submissions Empty State -->
<?php
	} else {
?>
<!-- Submissions Table -->
<div id="wsf-submissions">
<form method="get">
<?php

		// Views
		$this->ws_form_wp_list_table_submit_obj->views();
?>
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<input type="hidden" name="orderby" value="<?php WS_Form_Common::echo_esc_attr($ws_form_order_by_query_var); ?>">
<input type="hidden" name="order" value="<?php WS_Form_Common::echo_esc_attr($ws_form_order_query_var); ?>">
<input type="hidden" name="page" value="ws-form-submit">
<?php

		// Nonce
		wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME);

		// Display
		$this->ws_form_wp_list_table_submit_obj->display();
?>
</form>
</div>
<!-- /Submissions Table -->
<?php
	}
?>
<!-- View / Edit Sidebar -->
<div id="wsf-sidebars">

<div id="wsf-sidebar-submit" class="wsf-sidebar wsf-sidebar-closed">

<!-- Header -->
<div class="wsf-sidebar-header">

<h2><?php

	WS_Form_Common::render_icon_16_svg('table');
	esc_html_e('Submission', 'ws-form');
?>

<!-- Submit ID -->
<code></code>

</h2>

</div>
<!-- /Header -->

</div>
	
</div>
<!-- /View / Edit Sidebar -->

<!-- Submissions Actions -->
<form action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" id="ws-form-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-submit">
<input type="hidden" id="ws-form-action" name="action" value="">
<input type="hidden" id="ws-form-id" name="id" value="<?php WS_Form_Common::echo_esc_attr($ws_form_form_id); ?>">
<input type="hidden" id="ws-form-submit-id" name="submit_id" value="">
<input type="hidden" id="ws-form-date-from" name="date_from" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('date_from', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-date-to" name="date_to" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('date_to', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-paged" name="paged" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('paged', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-status" name="ws-form-status" value="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_query_var_nonce('ws-form-status', '', false, false, true, 'POST')); ?>">
</form>
<!-- /Submissions Actions -->

<!-- Popover -->
<div id="wsf-popover" class="wsf-ui-cancel"></div>
<!-- /Popover -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();

			// Partial initialization
			wsf_obj.init_partial();

			// Sidebar resizing
			wsf_obj.sidebar_resize_init();

			// Initialize submission table
			wsf_obj.wp_list_table_submit(<?php WS_Form_Common::echo_esc_html($ws_form_form_id); ?>);
		});

	})(jQuery);

</script>

</div>
</div>

<!-- Submit export process -->
<div id="wsf-submit-export-popup" class="wsf-popup-progress">
	<div class="wsf-popup-progress-backdrop"></div>
	<div class="wsf-popup-progress-inner"><img src="<?php WS_Form_Common::echo_esc_attr(WS_FORM_PLUGIN_DIR_URL . 'admin/images/loader.gif'); ?>" class="wsf-responsive" width="256" height="256" alt="<?php esc_html_e('Your export is being created...', 'ws-form'); ?>" /><p><?php esc_html_e('Your export is being created...', 'ws-form'); ?></p>
		<div class="wsf-popup-progress-bar"><progress class="wsf-progress" max="100" value="0"></progress></div>
	</div>
</div>
<!-- /Submit export process -->
<?php

	// Only render JavaScript if a form has been selected
	if($ws_form_form_id > 0) {

		// Get draft and published form data (parsed) so submission view/edit
		// does not need a follow-up form/full API call
		$ws_form_json_form = false;
		$ws_form_json_form_draft = false;
		$ws_form_form_same = false;

		try {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $ws_form_form_id;

			// Draft (with form_parse for data sources)
			$ws_form_form_draft = $ws_form_form->db_read(true, true, false, true);
			$ws_form_json_form_draft = wp_json_encode($ws_form_form_draft);

			// Published (with form_parse); false if never published
			$ws_form_form_published = $ws_form_form->db_read_published(true);

			// If draft matches published (or never published), embed once
			$ws_form_form_same = (
				($ws_form_form_published === false) ||
				(
					is_object($ws_form_form_published) &&
					isset($ws_form_form_draft->checksum, $ws_form_form_draft->published_checksum) &&
					($ws_form_form_draft->checksum === $ws_form_form_draft->published_checksum)
				)
			);

			if(!$ws_form_form_same) {

				$ws_form_json_form = wp_json_encode($ws_form_form_published);
			}

		} catch(Exception $e) {

			$ws_form_json_form = false;
			$ws_form_json_form_draft = false;
			$ws_form_form_same = false;
		}

		if($ws_form_json_form_draft) {
?>
<script>

	// Embed draft form data (preview submissions)
	var wsf_form_json_draft = { <?php

		WS_Form_Common::echo_esc_html($ws_form_form_id);

?>: <?php

		WS_Form_Common::echo_json($ws_form_json_form_draft);
?> };

<?php
			if($ws_form_form_same) {
?>
	// Published matches draft (or never published) — reuse draft object
	var wsf_form_json = wsf_form_json_draft;
<?php
			} else {
?>
	// Embed published form data
	var wsf_form_json = { <?php

		WS_Form_Common::echo_esc_html($ws_form_form_id);

?>: <?php

		WS_Form_Common::echo_json($ws_form_json_form);
?> };
<?php
			}
?>

</script>
<?php
		}
	}
?>