<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">
<?php

	WS_Form_Common::admin_header(__('Upgrade to PRO', 'ws-form'));

	// Ajax load
	WS_Form_Common::ajax_load('https://wsform.com/plugin-support/upgrade_to_pro.php?l=#locale&v=#version&a=#action_license_item_ids');
?>
</div>
