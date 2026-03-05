<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if(FLBuilderModel::is_builder_active()) {
?>
	(function($) {

		wsf_form_init();

	})(jQuery);
<?php
	}
