<?php

	global $wpdb;

	// Get core template data
	$ws_form_template = new WS_Form_Template;
	$ws_form_template->type = 'style';
	$ws_form_template_categories = $ws_form_template->read_config();

	// Loader icon
	WS_Form_Common::loader();
?>
<script>

	// Localize
	var ws_form_settings_language_style_add_create = '<?php esc_html_e('Use Template', 'ws-form'); ?>';

</script>

<svg xmlns="http://www.w3.org/2000/svg" style="width: 0; height: 0; position: absolute;"><defs><pattern id="wsf-styler-template-circle-check" width="10" height="10" patternUnits="userSpaceOnUse"><rect width="10" height="10" fill="#ddd"/><rect width="5" height="5" fill="#fff"/><rect x="5" y="5" width="5" height="5" fill="#fff"/></pattern></defs></svg>

<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-header">
<h1><?php esc_html_e('Add Style', 'ws-form'); ?></h1>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
?>
<p><?php esc_html_e('To create a new style, start by selecting a template.', 'ws-form'); ?></p>

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
<div id="<?php WS_Form_Common::echo_esc_attr(sprintf('wsf_template_category_%s', $ws_form_template_category->id)); ?>"<?php if(isset($ws_form_template_category->action_id)) { ?> data-action-id="<?php WS_Form_Common::echo_esc_attr($ws_form_template_category->action_id); ?>"<?php } ?> style="display: none;">
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
	<div class="wsf-popup-progress-inner"><img src="<?php WS_Form_Common::echo_esc_attr(sprintf('%sadmin/images/loader.gif', WS_FORM_PLUGIN_DIR_URL)); ?>" class="wsf-responsive" width="256" height="256" alt="<?php esc_attr_e('Your style is being created...', 'ws-form'); ?>" /><p><?php esc_html_e('Your style is being created...', 'ws-form'); ?></p></div>
</div>
<!-- /Loading -->

<!-- Form Actions -->
<form action="<?php WS_Form_Common::echo_esc_attr(WS_Form_Common::get_admin_url()); ?>" id="ws-style-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php WS_Form_Common::echo_esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-style">
<input type="hidden" id="ws-style-action" name="action" value="">
<input type="hidden" id="ws-style-id" name="id" value="">
<input type="hidden" id="ws-style-action-id" name="action_id" value="">
<input type="hidden" id="ws-style-list-id" name="list_id" value="">
</form>
<!-- /Form Actions -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Init template functionality
			var wsf_obj = new $.WS_Form();

			// Partial initialization
			wsf_obj.init_partial();

			// Initialize tooltips
			wsf_obj.tooltips();

			// Initialize style templatee
			wsf_obj.template_style();
		});

	})(jQuery);

</script>

</div>
