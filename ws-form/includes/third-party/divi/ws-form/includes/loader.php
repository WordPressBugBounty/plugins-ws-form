<?php

	if (!class_exists('ET_Builder_Element')) { return; }

	$ws_form_module_files = glob(__DIR__ . '/modules/*/*.php');

	// Load custom Divi Builder modules
	foreach ((array) $ws_form_module_files as $ws_form_module_file) {
		if ($ws_form_module_file && preg_match( "/\/modules\/\b([^\/]+)\/\\1\.php$/", $ws_form_module_file)) {
			require_once $ws_form_module_file;
		}
	}