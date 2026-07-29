<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_API_Sidebar extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - POST - Sidebar width
		public function api_sidebar_width($parameters) {

			// Get width
			$sidebar_width = absint(WS_Form_Common::get_query_var_nonce('sidebar_width', '', $parameters));

			// Context: layout editor (edit) vs submissions (submit)
			$sidebar_context = sanitize_key(WS_Form_Common::get_query_var_nonce('sidebar_context', 'edit', $parameters));
			if($sidebar_context !== 'submit') { $sidebar_context = 'edit'; }

			if($sidebar_context === 'submit') {

				$sidebar_width_min = WS_FORM_SIDEBAR_WIDTH_MIN_SUBMIT;
				$sidebar_width_max = WS_FORM_SIDEBAR_WIDTH_MAX_SUBMIT;
				$sidebar_option = 'sidebar_width_submit';

			} else {

				$sidebar_width_min = WS_Form_Common::get_sidebar_width_min();
				$sidebar_width_max = WS_FORM_SIDEBAR_WIDTH_MAX;
				$sidebar_option = 'sidebar_width';
			}

			// Check width
			if($sidebar_width < $sidebar_width_min) {

				$sidebar_width = $sidebar_width_min;
			}
			if($sidebar_width > $sidebar_width_max) {

				$sidebar_width = $sidebar_width_max;
			}

			// Set option
			WS_Form_Common::option_set($sidebar_option, $sidebar_width);

			return array(

				'error' => false,
				'sidebar_width' => $sidebar_width
			);
		}
	}
