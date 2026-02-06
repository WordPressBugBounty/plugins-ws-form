<?php

	if(!function_exists('ws_form_divi_extensions_init')) {

		function ws_form_divi_extensions_init() {

			require_once plugin_dir_path(__FILE__) . 'includes/ws-form.php';
		}
		add_action('divi_extensions_init', 'ws_form_divi_extensions_init');
	}
