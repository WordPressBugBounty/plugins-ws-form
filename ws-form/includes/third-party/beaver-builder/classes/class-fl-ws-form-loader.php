<?php
	
/**
 * A class that handles loading custom modules and custom
 * fields if the builder is installed and activated.
 */
class WS_Form_Beaver_Builder_Loader {
	
	/**
	 * Initializes the class once all plugins have loaded.
	 */
	static public function init() {

		self::setup_hooks();
	}
	
	/**
	 * Setup hooks if the builder is installed and activated.
	 */
	static public function setup_hooks() {

		if ( ! class_exists( 'FLBuilder' ) ) {
			return;	
		}

		if(
			isset($_GET) && isset($_GET['fl_builder'])	// phpcs:ignore WordPress.Security.NonceVerification
		) {

			// Disable debug
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			add_filter('wsf_debug_enabled', function($debug_render) { return false; }, 10, 1);

			// Visual builder enqueues
			add_action('wp_enqueue_scripts', function() {

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				do_action('wsf_enqueue_core');
			});
		}
		
		// Load custom modules.
		add_action( 'init', __CLASS__ . '::load_modules' );
	}
	
	/**
	 * Loads our custom modules.
	 */
	static public function load_modules() {

		require_once WS_FORM_BEAVER_BUILDER_DIR . 'modules/ws-form/ws-form.php';
	}

	static public function get_forms() {

		// Build form list
		$ws_form_form = new WS_Form_Form();
		$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label ASC', '', '', false, true);
		$form_array = array('0' => __('Select form...', 'ws-form'));

		if($forms) {

			foreach($forms as $form) {

				$form_array[$form['id']] = $form['label'] . ' (ID: ' . $form['id'] . ')';
			}
		}

		return $form_array;
	}
}

WS_Form_Beaver_Builder_Loader::init();