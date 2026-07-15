<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define( 'WS_FORM_WPBAKERY_DIR', WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/wpbakery/' );
	define( 'WS_FORM_WPBAKERY_URL', WS_FORM_PLUGIN_DIR_URL . 'includes/third-party/wpbakery/' );

	// Generic shortcode identifiers (LITE/PRO compatible)
	define( 'WS_FORM_WPBAKERY_SHORTCODE', 'ws_form_wpb' );
	define( 'WS_FORM_WPBAKERY_ATTR_FORM_ID', 'form_id' );
	define( 'WS_FORM_WPBAKERY_ATTR_ELEMENT_ID', 'element_id' );
	define( 'WS_FORM_WPBAKERY_ATTR_CLASS', 'class' );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Third party class
	class WS_Form_WPBakery {

		// Is WP Bakery editor active?
		public static function is_editor() {

			if(function_exists('vc_is_inline') && vc_is_inline()) {

				return true;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if(isset($_GET['vc_editable']) && $_GET['vc_editable'] === 'true') {

				return true;
			}

			if(is_admin() && function_exists('vc_action') && vc_action() === 'vc_inline') {

				return true;
			}

			return false;
		}

		// Enqueue editor preview assets
		public static function enqueue_editor_assets() {

			wp_register_script('wsf-wpbakery', WS_FORM_WPBAKERY_URL . 'wpbakery-editor-preview.js', array('jquery'), WS_FORM_VERSION, true);
			wp_register_style('wsf-wpbakery-css', WS_FORM_WPBAKERY_URL . 'wpbakery-editor-preview.css', array(), WS_FORM_VERSION, 'all');

			wp_localize_script('wsf-wpbakery', 'wsf_wpbakery_vars', array(

				'shortcode' => WS_FORM_WPBAKERY_SHORTCODE,
			));

			wp_enqueue_script('wsf-wpbakery');
			wp_enqueue_style('wsf-wpbakery-css');
		}

		// Init
		public static function init() {

			// Visual builder enqueues
			if(self::is_editor()) {

				// Disable debug
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				add_filter('wsf_debug_enabled', function($debug_render) { return false; }, 10, 1);

				add_action('wp_enqueue_scripts', function() {

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
					do_action('wsf_enqueue_visual_builder');

					self::enqueue_editor_assets();
				});
			}

			// Backend and frontend editor assets
			add_action('vc_backend_editor_enqueue_js_css', array(__CLASS__, 'enqueue_editor_assets'));
			add_action('vc_frontend_editor_enqueue_js_css', array(__CLASS__, 'enqueue_editor_assets'));

			// Register element
			add_action('vc_before_init', function() {

				require_once WS_FORM_WPBAKERY_DIR . 'class-wpbakery-shortcode-ws-form.php';
				WS_Form_WPBakery_Shortcode::register();
			});
		}
	}

	WS_Form_WPBakery::init();
