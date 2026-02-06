<?php 

	add_action('init', function() {

		if(isset($_GET) && isset($_GET['breakdance_iframe'])) {	// phpcs:ignore WordPress.Security.NonceVerification

			// Visual builder enqueues
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			do_action('wsf_enqueue_visual_builder');
		}
	});

	try {

		// Register element
		include_once 'elements/ws-form-form/element.php';

	} catch (Exception $e) {}
