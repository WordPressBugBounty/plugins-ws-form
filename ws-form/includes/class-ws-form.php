<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */
final class WS_Form {

	// Loader
	protected $loader;

	// Plugin name
	protected $plugin_name;

	// Version
	protected $version;


	// Plugin Public
	public $plugin_public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {

		$this->plugin_name = WS_FORM_NAME;
		$this->version = WS_FORM_VERSION;
		$plugin_path = plugin_dir_path(dirname(__FILE__));

		// The class responsible for all common functions
		require_once $plugin_path . 'includes/class-ws-form-common.php';


		$this->load_dependencies();

		$this->plugin_public = new WS_Form_Public();

		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_public_shortcodes();
		$this->define_api_hooks();

	}

	// Load the required dependencies for this plugin.
	private function load_dependencies() {

		$wp_version = get_bloginfo('version');

		// Configuration (Options, field types, field variables)
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-config.php';

		// The class responsible for orchestrating the actions and filters of the core plugin
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-loader.php';

		// The class responsible for defining internationalization functionality of the plugin
		// The classes responsible for populating WP List Tables
		if(is_admin()) {

			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-form.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-submit.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-style.php';
		}

		// The class responsible for defining all actions that occur in the admin area
		require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-public.php';

		// The class responsible for managing form previews
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-preview.php';

		// The class responsible for the widget
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-widget.php';

		// Core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-core.php';

		// Cron
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-cron.php';

		// Color
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-color.php';


		// Core classes
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-group.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-section.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-field.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-file.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-data-grid.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit-export.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-template.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-css.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-style.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form-stat.php';

		// Actions
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-action.php';

		// Actions - Spam protection
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-akismet.php';

		// Actions - GDPR
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-erasure-request.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-export-request.php';

		// Actions - Basic
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-database.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-message.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-redirect.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-email.php';

		// Data Sources
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-data-source.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-data-source-cron.php';

		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-preset.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-post.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-post-status.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-term.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-user.php';

		// API core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api.php';

		// Functions
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/functions.php';

		// Blocks
 		if(WS_Form_Common::version_compare($wp_version, '5.9') >= 0) {

 			// API version 3
			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/blocks/form-add/form-add.php';
		}

		// Ability class
		if(
			WS_Form_Common::abilities_api_enabled() ||
			WS_Form_Common::angie_enabled()
		) {
			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-ability.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form-ai.php';
		}

		// ability-api
		if(WS_Form_Common::abilities_api_enabled()) {

			$ws_form_ability = new WS_Form_Ability();

			// Register ability categories
			add_action('wp_abilities_api_categories_init', array($ws_form_ability, 'register_categories'));
			add_action('abilities_api_categories_init', array($ws_form_ability, 'register_categories'));	// Legacy (This will eventually be removed)

			// Register abilities - New hook
			add_action('wp_abilities_api_init', array($ws_form_ability, 'register'));
			add_action('abilities_api_init', array($ws_form_ability, 'register'));	// Legacy (This will eventually be removed)
		}

		// Third party
		add_action('plugins_loaded', function() {

			// ACF
			if(class_exists('ACF')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/acf/class-ws-form-acf.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-acf.php';
			}

			// ACPT
			if(
				defined('ACPT_PLUGIN_VERSION') &&
				(WS_Form_Common::version_compare(ACPT_PLUGIN_VERSION, '2.0.0') >= 0)
			) {
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/acpt/class-ws-form-acpt-v2.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-acpt.php';
			}

			// Angie
			if(WS_Form_Common::angie_enabled()) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/angie/angie.php';
			}

			// Beaver Builder
			if(class_exists('FLBuilder')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/beaver-builder/fl-ws-form.php';
			}

			// Breakdance
			if(defined('__BREAKDANCE_VERSION')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/breakdance/breakdance.php';
			}

			// Bricks Theme (Don't remove init action)
			add_action('init', function() {

				if(class_exists('\Bricks\Elements')) {

					require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/bricks/bricks.php';
				}

			}, 11);

			// Divi
			if(
				wp_get_theme()->get('Name') === 'Divi' ||
				wp_get_theme()->get('Template') === 'Divi' ||
				WS_Form_File::file_exists( WP_PLUGIN_DIR . '/divi-builder/divi-builder.php' ) ||
				defined('ET_CORE_VERSION') ||
				class_exists('ET_Builder_Module')
			) {
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/divi/ws-form/ws-form.php';
			}

			// Elementor
			if(defined('ELEMENTOR_VERSION')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/elementor/elementor.php';
			}

			// Hostinger Reach
			if(defined('HOSTINGER_INTEGRATIONS_SUPPORTED')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/hostinger-reach/class-ws-form-hostinger-reach.php';
			}

			// JetEngine
			if(class_exists('Jet_Engine')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/jetengine/class-ws-form-jetengine.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-jetengine.php';
			}

			// Litespeed
			if(class_exists('LiteSpeed\Core')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/litespeed/litespeed.php';
			}

			// Meta Box
			if(
				defined('RWMB_VER') ||
				defined('META_BOX_LITE_DIR') ||
				defined('META_BOX_AIO_DIR')
			) {
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/meta-box/class-ws-form-meta-box.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-meta-box.php';
			}

			// Oxygen
			if(class_exists('OxyEl')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/oxygen/oxygen.php';
			}

			// Pods
			if(defined('PODS_VERSION')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/pods/class-ws-form-pods.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-pods.php';
			}

			// Toolset
			if(defined('TYPES_VERSION')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/toolset/class-ws-form-toolset.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-toolset.php';
			}

			// WooCommerce
			if(defined('WC_VERSION')) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/woocommerce/class-ws-form-woocommerce.php';
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-woocommerce.php';
			}

/*			// Translation

			// WPML
			$translation_enabled = false;
			if(
				defined('ICL_SITEPRESS_VERSION') &&
				defined('WPML_ST_VERSION')
			) {
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/wpml/class-ws-form-wpml.php';

				$translation_enabled = true;
			}

			if($translation_enabled) {

				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-translate.php';
			}
*/

			// Run wsf_loaded action
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			do_action('wsf_loaded');
		});

		// Options and styles are initialized on init because of translated strings
		// The activation script sets two options to initialize these because init is not called during activation
		add_action('init', function() {

			// Check if options need to be initialized
			if(WS_Form_Common::option_get('options_init')) {

				// Initialize options
				self::options_init();

				// Remove option
				WS_Form_Common::option_remove('options_init');
			}

			// Check if styler needs to be initialized
			if(WS_Form_Common::option_get('styler_init')) {

				// Initialize styles
				self::styler_init(WS_Form_Common::option_get('styler_init') == 'fresh_intall');

				// Remove option
				WS_Form_Common::option_remove('styler_init');
			}
		});

		$this->loader = new WS_Form_Loader();
	}

	private function options_init() {

		// Get mode
		$mode = WS_Form_Common::option_get('mode', 'basic', true);

		// Get  options
		$options = WS_Form_Config::get_options(false);

		// Set up options with default values
		foreach($options as $tab => $attributes) {

			if(isset($attributes['fields'])) {

				$fields = $attributes['fields'];
				self::options_set($mode, $fields);
			}

			if(isset($attributes['groups'])) {

				$groups = $attributes['groups'];

				foreach($groups as $group) {

					$fields = $group['fields'];
					self::options_set($mode, $fields);
				}
			}
		}

		// Set skin option defaults
		$ws_form_css = new WS_Form_CSS();
		$ws_form_css->option_set_defaults();

		// Clear compiled CSS
		WS_Form_Common::option_set('css_public_layout', '');
	}

	private function options_set($mode, $fields) {

		// File upload checks
		$upload_checks = WS_Form_Common::uploads_check();
		$max_upload_size = $upload_checks['max_upload_size'];
		$max_uploads = $upload_checks['max_uploads'];

		foreach($fields as $key => $attributes) {

			if(
				isset($attributes['type']) && 
				($attributes['type'] != 'static')
			) { 

				if(
					isset($attributes['mode']) &&
					isset($attributes['mode'][$mode])
				) {

					// Use mode specific values
					$value = $attributes['mode'][$mode];

					WS_Form_Common::option_set($key, $value, false);

				} else if(isset($attributes['default'])) {

					// Use default value
					$value = $attributes['default'];

					// Value parsing
					if($value === '#max_upload_size') { $value = $max_upload_size; }
					if($value === '#max_uploads') { $value = $max_uploads; }

					WS_Form_Common::option_set($key, $value, false);
				}
			}
		}
	}

	private function styler_init($fresh_install = false) {

		// Check style system has initialized
		$ws_form_style = new WS_Form_Style();
		$ws_form_style->check_initialized(true, !$fresh_install);

		// Ensure all forms are configured with default style ID
		$ws_form_form = new WS_Form_Form();
		$ws_form_form->db_style_resolve(true);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {

		$wp_version = get_bloginfo('version');

		$plugin_admin = new WS_Form_Admin();

		// General
		$this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
		$this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');

		// Screen options
		$this->loader->add_action('wp_ajax_ws_form_hidden_columns', $plugin_admin, 'ws_form_hidden_columns', 1);
		$this->loader->add_action('set-screen-option', $plugin_admin, 'ws_form_set_screen_option', 10, 3);

		// Enqueuing
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 9999);	// Make sure we're overriding other styles
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		if(is_admin()) {

			$this->loader->add_action('wp_print_scripts', $plugin_admin, 'wp_print_scripts', 1);
		}

		// Admin notifications
		$this->loader->add_action('admin_notices', 'WS_Form_Common', 'admin_messages_render');

		// Customize
		$this->loader->add_action('customize_register', $plugin_admin, 'customize_register');

		// Theme switching
		$this->loader->add_action('switch_theme', $plugin_admin, 'switch_theme');

		// Plugins
		$this->loader->add_filter('plugin_action_links_' . WS_FORM_PLUGIN_BASENAME, $plugin_admin, 'plugin_action_links');

		// Blocks
 		$this->loader->add_action('enqueue_block_assets', $plugin_admin, 'enqueue_block_assets');

 		if(!(WS_Form_Common::version_compare($wp_version, '5.9') >= 0)) {

 			// API version 1
			$this->loader->add_action('init', $plugin_admin, 'register_blocks');
			$this->loader->add_action('enqueue_block_assets', $plugin_admin, 'enqueue_block_assets');
			$this->loader->add_action('enqueue_block_editor_assets', $plugin_admin, 'enqueue_block_editor_assets_v1');
 		}

 		if(WS_Form_Common::version_compare($wp_version, '5.8') >= 0) {

			$this->loader->add_filter('block_categories_all', $plugin_admin, 'block_categories', 10, 2);

		} else {

			$this->loader->add_filter('block_categories', $plugin_admin, 'block_categories', 10, 2);
		}

		// Patterns
		$this->loader->add_action('init', $plugin_admin, 'pattern_categories');
		$this->loader->add_action('init', $plugin_admin, 'patterns');

		// Dashboard glance items
		$this->loader->add_filter('dashboard_glance_items', $plugin_admin, 'dashboard_glance_items');

		// Toolbar
		$this->loader->add_action('admin_bar_menu', $plugin_admin, 'admin_bar_menu', 99, 1);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {

		// General
		$this->loader->add_action('init', $this->plugin_public, 'init');
		$this->loader->add_action('wp', $this->plugin_public, 'wp');

		// Enqueuing
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue');

		if(!is_admin()) {

			$this->loader->add_action('wp_print_scripts', $this->plugin_public, 'wp_print_scripts', 1);
		}

		// Footer scripts
		$this->loader->add_action('wp_footer', $this->plugin_public, 'wp_footer', 9999);

		// NONCE management
		$this->loader->add_filter('nonce_user_logged_out', $this->plugin_public, 'nonce_user_logged_out', 9999, 2);

		// Divi
		$this->loader->add_action('wp_ajax_ws_form_divi_form', $this->plugin_public, 'ws_form_divi_form');
	}

	/**
	 * Register all of the shortcodes related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_shortcodes() {

		$this->loader->add_shortcode('ws_form', $this->plugin_public, 'shortcode_ws_form');
	}

	/**
	 * Register all of the hooks related to the API
	 */
	private function define_api_hooks() {

		$plugin_api = new WS_Form_API();

		// Initialize API
		$this->loader->add_action('rest_api_init', $plugin_api, 'api_rest_api_init');

		// mcp-adapter
		if(WS_Form_Common::mcp_adapter_enabled()) {

			$this->loader->add_action('mcp_adapter_init', $plugin_api, 'mcp_adapter_init', 10, 1);
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
