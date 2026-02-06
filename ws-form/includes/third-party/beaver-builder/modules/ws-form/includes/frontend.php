<?php

/**
 * You have access to two variables in this file: 
 * 
 * $module An instance of your module class.
 * $settings The module's settings.
 */

?>
<div class="fl-ws-form">
<?php

	$ws_form_fl_form_id = isset($settings->form_id) ? absint($settings->form_id) : 0;

	if($ws_form_fl_form_id > 0) {

		if(isset($_GET) && isset($_GET['fl_builder'])) {	// phpcs:ignore WordPress.Security.NonceVerification

			// Render form (Beaver Builder)
			echo do_shortcode(sprintf('[%s id="%u" visual_builder="true"]', WS_FORM_SHORTCODE, $ws_form_fl_form_id));

		} else {

			// Render form (Frontend)
			echo do_shortcode(sprintf('[%s id="%u"]', WS_FORM_SHORTCODE, $ws_form_fl_form_id));
		}
	}
?>
</div>
