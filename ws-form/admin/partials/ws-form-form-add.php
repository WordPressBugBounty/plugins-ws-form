<?php

	global $wpdb;

	// Get core template data
	$ws_form_template = new WS_Form_Template;
	$ws_form_template_categories = $ws_form_template->read_config();

	// Add action categories
	$ws_form_actions = $ws_form_template->db_get_actions();
	if($ws_form_actions !== false) {

		foreach($ws_form_actions as $action) {

			$action->action_id = $action->id;
			$action->action_template_add_modal_label = isset($action->list_sub_modal_label) ? $action->list_sub_modal_label : false;
			$ws_form_template_categories[] = $action;
		}
	}

	// Order template categories by priority, then label
	uasort($ws_form_template_categories, function($a, $b) {

		$pa = isset($a->priority) ? $a->priority : 0;
		$pb = isset($b->priority) ? $b->priority : 0;

		if($pa === $pb) {

			return ($a->label == $b->label) ? 0 : (($a->label > $b->label) ? 1 : -1);

		} else {

			return ($pa < $pb) ? 1 : -1;
		}
	});

	// Loader icon
	WS_Form_Common::loader();
?>
<script>

	// Localize
	var ws_form_settings_language_form_add_create = '<?php esc_html_e('Use Template', 'ws-form'); ?>';

</script>

<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-header">
<h1><?php esc_html_e('Add Form', 'ws-form'); ?></h1>
<?php
	if(WS_Form_Common::can_user('import_form')) {
?>
<button class="wsf-button wsf-button-small" data-action-button="wsf-form-upload"><?php WS_Form_Common::render_icon_16_svg('upload'); ?> <?php esc_html_e('Import', 'ws-form'); ?></button>
<?php
	}
?></div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();

	// Import
	if(WS_Form_Common::can_user('import_form')) {
?>
<input type="file" id="wsf-object-upload-file" class="wsf-file-upload" accept=".json" aria-hidden aria-label="<?php esc_attr_e('File upload', 'ws-form'); ?>" />
<?php
	}
?>
<p><?php esc_html_e('Choose a form template or start with a blank template to create your own.', 'ws-form'); ?></p>

<!-- Template -->
<div id="wsf-template-add">

<!-- Tabs - Categories -->
<ul id="wsf-template-add-tabs">
<?php

	// Loop through templates
	foreach ($ws_form_template_categories as $ws_form_template_category)  {

		if(isset($ws_form_template_category->templates) && (count($ws_form_template_category->templates) == 0)) { continue; }

		$ws_form_action_id = isset($ws_form_template_category->action_id) ? $ws_form_template_category->action_id : false;

?><li><a href="<?php WS_Form_Common::echo_esc_url(sprintf('#wsf_template_category_%s', $ws_form_template_category->id)); ?>"><?php WS_Form_Common::echo_esc_html($ws_form_template_category->label); ?><?php

		if(($ws_form_action_id !== false) && ($ws_form_template_category->reload)) {

?><span data-action="wsf-api-reload" data-action-id="<?php WS_Form_Common::echo_esc_attr($ws_form_action_id); ?>" data-method="lists_fetch"<?php

	WS_Form_Common::echo_esc_attr_tooltip(__('Update', 'ws-form'), 'top-center');

?>><?php WS_Form_Common::render_icon_16_svg('reload'); ?></span><?php

		}

?></a></li>
<?php

	}
?>
</ul>
<!-- Tabs - Categories -->
<?php

	// Loop through templates
	foreach ($ws_form_template_categories as $ws_form_template_category)  {

		if(isset($ws_form_template_category->templates) && (count($ws_form_template_category->templates) == 0)) { continue; }
?>
<!-- Tab Content: <?php WS_Form_Common::echo_esc_html($ws_form_template_category->label); ?> -->
<div id="<?php WS_Form_Common::echo_esc_attr(sprintf('wsf_template_category_%s', $ws_form_template_category->id)); ?>"<?php if(isset($ws_form_template_category->action_id)) { ?> data-action-id="<?php WS_Form_Common::echo_esc_attr($ws_form_template_category->action_id); ?>"<?php } ?><?php if(isset($ws_form_template_category->action_template_add_modal_label)) { ?> data-action-template-add-modal-label="<?php WS_Form_Common::echo_esc_attr($ws_form_template_category->action_template_add_modal_label); ?>"<?php } ?> style="display: none;">
<ul class="wsf-templates">
<?php
		$ws_form_template->template_category_render($ws_form_template_category);
?>
</ul>

</div>
<!-- /Tab Content: <?php WS_Form_Common::echo_esc_html($ws_form_template_category->label); ?> -->
<?php

	}
?>

</div>
<!-- /Template -->

<!-- Loading -->
<div id="wsf-template-add-loading" class="wsf-popup-progress">
	<div class="wsf-popup-progress-backdrop"></div>
	<div class="wsf-popup-progress-inner"><img src="<?php WS_Form_Common::echo_esc_attr(sprintf('%sadmin/images/loader.gif', WS_FORM_PLUGIN_DIR_URL)); ?>" class="wsf-responsive" width="256" height="256" alt="<?php esc_attr_e('Your form is being created...', 'ws-form'); ?>" /><p><?php esc_html_e('Your form is being created...', 'ws-form'); ?></p></div>
</div>
<!-- /Loading -->

<!-- WS Form - Modal -->
<div id="wsf-template-add-modal-backdrop" class="wsf-modal-backdrop" style="display: none;"></div>

<div id="wsf-template-add-modal" class="wsf-modal" style="display: none; margin-left: -200px; margin-top: -100px; width: 400px;">

<div id="wsf-template-add">

<!-- WS Form - Modal - Header -->
<div class="wsf-modal-title"><?php

	WS_Form_Common::echo_get_admin_icon('#002e5f', false);	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

?><h2></h2></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - Header -->

<!-- WS Form - Modal - Content -->
<div class="wsf-modal-content">

<form id="wsf-template-add-modal-form" action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" method="post"></form>

</div>
<!-- /WS Form - Modal - Content -->

<!-- WS Form - Modal - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Cancel', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-create">
<button class="button button-primary" data-action="wsf-template-add-modal-submit"><?php esc_html_e('Create', 'ws-form'); ?></button>
</div>

</div>
<!-- /WS Form - Modal - Buttons -->

</div>

</div>
<!-- /WS Form - Modal -->

<!-- Form Actions -->
<form action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" id="ws-form-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form">
<input type="hidden" id="ws-form-action" name="action" value="">
<input type="hidden" id="ws-form-id" name="id" value="">
<input type="hidden" id="ws-form-action-id" name="action_id" value="">
<input type="hidden" id="ws-form-list-id" name="list_id" value="">
<?php

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
	do_action('wsf_form_add_hidden');
?>
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

			// Initialize form add
			wsf_obj.template_form();

			$('#wsf-form-add h1').html('<?php esc_html_e('Drop file to import', 'ws-form'); ?>');
		});

	})(jQuery);

</script>

</div>
