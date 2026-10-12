<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Get ID of form (0 = New)
	$ws_form_form_id = absint(WS_Form_Common::get_query_var('id', 0));
	$ws_form_form_locked = ($ws_form_form_id && WS_Form_Form::db_is_locked_id($ws_form_form_id));

	// Loader icon
	WS_Form_Common::loader();

	if($ws_form_form_locked) {

		ob_start();
?>
<a class="button" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_preview_url($ws_form_form_id)); ?>" target="wsf-preview-<?php WS_Form_Common::echo_esc_attr($ws_form_form_id); ?>" title="<?php esc_attr_e('Preview', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Preview', 'ws-form'); ?></span></a>
<?php
		if(WS_Form_Common::can_user('read_submission')) {
?>
<a class="button" href="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php?page=ws-form-submit&id=' . $ws_form_form_id)); ?>" title="<?php esc_attr_e('Submissions', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('table'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Submissions', 'ws-form'); ?></span></a>
<?php
		}

		WS_Form_Common::admin_header(__('Edit Form', 'ws-form'), ob_get_clean());
		WS_Form_Common::review();

		$ws_form_lock_icon = str_replace(

			'<svg height="16" width="16"',
			'<svg class="wsf-submissions-empty-icon-doc" height="64" width="64"',
			WS_Form_Config::get_icon_16_svg('lock')
		);
?>
<div id="wsf-form-locked" class="wsf-submissions-empty">
	<div class="wsf-submissions-empty-inner">
		<div class="wsf-submissions-empty-icon" aria-hidden="true"><?php

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from the icon config
			echo $ws_form_lock_icon;

		?></div>
		<h2 class="wsf-submissions-empty-title"><?php esc_html_e('This Form is Locked', 'ws-form'); ?></h2>
		<p class="wsf-submissions-empty-copy"><?php

			if(WS_Form_Common::can_user('unlock_form')) {

				esc_html_e('Unlock it to make changes.', 'ws-form');

			} else {

				esc_html_e('You do not have permission to unlock this form.', 'ws-form');
			}

		?> <a href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_plugin_website_url('/knowledgebase/form-locking/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn more', 'ws-form'); ?></a></p>
<?php
		if(WS_Form_Common::can_user('unlock_form')) {
?>
		<form id="wsf-form-unlock" method="post" action="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-edit', $ws_form_form_id)); ?>">
<?php
			wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME);
?>
			<input type="hidden" name="page" value="ws-form-edit" />
			<input type="hidden" name="id" value="<?php WS_Form_Common::echo_esc_attr($ws_form_form_id); ?>" />
			<input type="hidden" name="action" value="wsf-form-unlock" />
			<button type="submit" class="button button-primary wsf-submissions-empty-action"><?php esc_html_e('Unlock', 'ws-form'); ?></button>
		</form>
<script>
(function($) {

	'use strict';

	$(function() {

		var form = document.getElementById('wsf-form-unlock');
		if(!form) { return; }

		var wsf_obj = new $.WS_Form();

		wsf_obj.get_configuration(function() {

			$(form).on('submit', function(e) {

				e.preventDefault();

				wsf_obj.confirm_modal(

					wsf_obj.language('form_unlock_confirm', false, false),
					function() { form.submit(); },
					{
						title: wsf_obj.language('form_unlock'),
						confirm_label: wsf_obj.language('form_unlock')
					}
				);
			});

		}, false, true);
	});

})(jQuery);
</script>
<?php
		}
?>
	</div>
</div>
<?php
		return;
	}
?>
<!-- Layout Editor -->
<div id="wsf-layout-editor">

<!-- Header -->
<div class="wsf-loading-hidden">
<?php

	ob_start();

	// Preview
?>
<a data-action="wsf-preview" class="button" href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_preview_url($ws_form_form_id)); ?>" target="wsf-preview-<?php WS_Form_Common::echo_esc_attr($ws_form_form_id); ?>" title="<?php esc_attr_e('Preview', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Preview', 'ws-form'); ?></span></a>
<?php

	// Style
	if(WS_Form_Common::styler_visible_admin()) {
?>
<a data-action="wsf-style" class="button" href="#" title="<?php esc_attr_e('Style', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('style'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Style', 'ws-form'); ?></span></a>
<?php
	}

	// Submissions
	if(WS_Form_Common::can_user('read_submission')) {
?>
<a data-action="wsf-submission" class="button" href="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php?page=ws-form-submit&id=' . $ws_form_form_id)); ?>" title="<?php esc_attr_e('Submissions', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('table'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Submissions', 'ws-form'); ?></span></a>
<?php
	}

	// Hook for additional buttons
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
	do_action('wsf_form_edit_nav_left', $ws_form_form_id);
?>
<ul class="wsf-settings wsf-settings-form">
<?php
	// Export (DOM first → visual last with row-reverse)
	// Tooltips open left so they stay in the header and are not covered by the sidebar
	if(WS_Form_Common::can_user('export_form')) {
?>
<li data-action="wsf-form-download"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Export Form', 'ws-form'), 'left'); ?>><?php WS_Form_Common::render_icon_16_svg('download'); ?></li>
<?php
	}

	// Import
	if(WS_Form_Common::can_user('import_form')) {
?>
<li data-action="wsf-form-upload"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Import Form', 'ws-form'), 'left'); ?>><?php WS_Form_Common::render_icon_16_svg('upload'); ?></li>
<?php
	}
?>
<li data-action="wsf-redo"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Redo', 'ws-form'), 'left'); ?> class="wsf-redo-inactive"><?php WS_Form_Common::render_icon_16_svg('redo'); ?></li>
<li data-action="wsf-undo"<?php WS_Form_Common::echo_esc_attr_tooltip(__('Undo', 'ws-form'), 'left'); ?> class="wsf-undo-inactive"><?php WS_Form_Common::render_icon_16_svg('undo'); ?></li>
</ul>
<?php

	// Build / sidebar open (mobile only — styled like other header action buttons)
?>
<button type="button" data-action-sidebar="toolbox" class="button wsf-admin-header-sidebar-open" title="<?php esc_attr_e('Build', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('tools'); ?> <span class="wsf-admin-header-action-label"><?php esc_html_e('Build', 'ws-form'); ?></span></button>
<?php

	// Publish (rightmost)
	if(WS_Form_Common::can_user('publish_form')) {
?>
<button type="button" data-action="wsf-publish" class="button button-primary" disabled><?php esc_html_e('Publish', 'ws-form'); ?></button>
<?php
	}

	WS_Form_Common::admin_header(__('Edit Form', 'ws-form'), ob_get_clean());
?>
</div>
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();

	// Import (form JSON — viewport-fixed drop zone)
	$this->render_object_upload_dropzone('import_form');
?>
<!-- Wrapper -->
<div id="poststuff" class="wsf-loading-hidden">

<!-- Label -->
<div id="titlediv">
<div id="titlewrap">

<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e('Form Label', 'ws-form'); ?></label>
<input type="text" id="title" class="wsf-field" placeholder="<?php esc_html_e('Form Label', 'ws-form'); ?>" data-action="wsf-form-label" name="form_label" size="30" value="" spellcheck="true" autocomplete="off" />
<span class="wsf-form-label-id"></span>
<button type="button" data-action="wsf-label-save" class="button button-primary"><?php esc_html_e('Save', 'ws-form'); ?></button>

</div>
</div>
<!-- /Label -->

<!-- Form -->
<div id="wsf-form" class="wsf-form wsf-form-canvas"></div>
<!-- /Form -->

</div>
<!-- /Wrapper -->

<!-- Breakpoints -->
<div id="wsf-breakpoints"></div>
<!-- /Breakpoints -->

</div>
<!-- /Layout Editor -->

<!-- Popover -->
<div id="wsf-popover" class="wsf-ui-cancel"></div>
<!-- /Popover -->

<!-- Field Draggable Container (Fixes Chrome bug) -->
<div class="wsf-field-selector"><div id="wsf-field-draggable"><ul></ul></div></div>
<!-- /Field Draggable Container (Fixes Chrome bug) -->

<!-- Section Draggable Container (Fixes Chrome bug) -->
<div class="wsf-section-selector"><div id="wsf-section-draggable"><ul></ul></div></div>
<!-- /Section Draggable Container (Fixes Chrome bug) -->

<!-- Sidebars -->
<div id="wsf-sidebars"></div>
<!-- /Sidebars -->

<!-- Variable Helper -->
<div id="wsf-variable-helper-modal" class="wsf-modal">

<div id="wsf-variable-helper">

<!-- Variable Helper - Header -->
<div id="wsf-variable-helper-header">

<div class="wsf-modal-title"><?php

	WS_Form_Common::echo_get_admin_icon('#002e5f', false);	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

?><h2><?php

	WS_Form_Common::echo_esc_html(__('Variables', 'ws-form'));	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

?></h2></div>

<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('delete'); ?></div>

</div>
<!-- /Variable Helper - Header -->

<!-- Variable Helper - Content -->
<div class="wsf-modal-content">

<!-- Variable Helper - Search -->
<div id="wsf-variable-helper-search">

<input id="wsf-variable-helper-search-input" class="wsf-field" type="search" placeholder="<?php

	WS_Form_Common::echo_esc_html(__('Variable search...', 'ws-form'));	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

?>" />

</div>
<!-- Variable Helper - /Search -->

<!-- Variable Helper - Search - No Results -->
<div id="wsf-variable-helper-search-no-results">
	
<p><?php

	WS_Form_Common::echo_esc_html(__('No results', 'ws-form'));	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

?>
</div>
<!-- /Variable Helper - /Search - No Results -->

<!-- Variable Helper - Tabs -->
<div id="wsf-variable-helper-variables"></div>
<!-- /Variable Helper - /Tabs -->

</div>
<!-- /Variable Helper - Content -->

</div>

</div>
<!-- /Variable Helper -->

<script>

<?php

	// Get form data
	try {

		$ws_form_form = new WS_Form_Form();
		$ws_form_form->id = $ws_form_form_id;
		$ws_form_form_object = $ws_form_form->db_read(true, true);
		$ws_form_json_form = wp_json_encode($ws_form_form_object);

	} catch(Exception $e) {

		$ws_form_json_form = false;
	}
?>

	// Embed form data
	var wsf_form_json = { <?php

	WS_Form_Common::echo_esc_html(absint($ws_form_form_id));

?>: <?php

	WS_Form_Common::echo_json($ws_form_json_form);
?> };

	var wsf_obj = null;

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();

			// Render form
			wsf_obj.render({

				'obj': '#wsf-form',
				'form_id': <?php WS_Form_Common::echo_esc_attr($ws_form_form_id); ?>
			});
		});

	})(jQuery);

</script>
