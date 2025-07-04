<?php

	class WS_Form_Admin {

		// The ID of this plugin.
		private $plugin_name;

		// The version of this plugin.
		private $version;

		// HTML editor settings
		private $html_editor_settings = '';

		// Submit fields
		private $submit_fields = false;

		// Form ID
		private $form_id;

		// User meta for hidden columns
		private $user_meta_hidden_columns;

		// Show intro
		private $intro;

		// Remember 
		private $ws_form_hook = false;

		// Deregister scripts
		private $deregister_scripts = array();

		// Hooks
		private $hook_suffix_form = false;
		private $hook_suffix_form_add = false;
		private $hook_suffix_form_sub = false;
		private $hook_suffix_form_edit = false;
		private $hook_suffix_form_submit = false;
		private $hook_suffix_form_settings = false;
		private $hook_suffix_form_welcome = false;
		private $hook_suffix_form_migrate = false;
		private $hook_suffix_form_upgrade = false;
		private $hook_suffix_form_add_ons = false;
		private $hook_suffix_form_style = false;
		private $hook_suffix_form_style_add = false;
		private $hook_suffix_customize = false;

		// Table views
		private $ws_form_wp_list_table_form_obj;
		private $ws_form_wp_list_table_submit_obj;
		private $ws_form_wp_list_table_style_obj;

		// Initialize the class and set its properties.
		public function __construct() {

			$this->plugin_name = WS_FORM_NAME;
			$this->version = WS_FORM_VERSION;
			$this->user_meta_hidden_columns = 'managews-form_page_ws-form-submitcolumnshidden';	// AJAX function is in helper API
			$this->intro = WS_Form_Common::option_get('intro', false);

			// Activator to check for edition and version changes
			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-activator.php';
			WS_Form_Activator::activate();
		}

		// Register the stylesheets for the admin area.
		public function enqueue_styles($hook) {

			// Minified scripts?
			$min = SCRIPT_DEBUG ? '' : '.min';

			// Is WS Form page?
			$is_ws_form_page = false;

			switch($hook) {

				// Form - List
				case $this->hook_suffix_form_sub :

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-layout', WS_Form_Common::get_api_path('helper/ws-form-css-admin', sprintf('_wpnonce=%s', wp_create_nonce('wp_rest'))), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
//					wp_enqueue_style($this->plugin_name . '-template', sprintf('%sadmin/css/ws-form-admin-template%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;

				// Form - Add
				case $this->hook_suffix_form_add : 		

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-layout', WS_Form_Common::get_api_path('helper/ws-form-css-admin', sprintf('_wpnonce=%s', wp_create_nonce('wp_rest'))), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-template', sprintf('%sadmin/css/ws-form-admin-template%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;

				// Form - Edit
				case $this->hook_suffix_form_edit :

					// CSS - Intro
					if($this->intro) {

						wp_enqueue_style($this->plugin_name . '-intro', sprintf('%sadmin/css/external/introjs%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');
					}

					// CSS - Select2 (Check made because WooCommerce enqueues this)
					if(!wp_style_is('select2', 'enqueued')) {

						wp_enqueue_style('select2', sprintf('%sshared/css/external/select2%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), '4.0.13', 'all');
					}

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-layout', WS_Form_Common::get_api_path('helper/ws-form-css-admin', sprintf('_wpnonce=%s', wp_create_nonce('wp_rest'))), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-template', sprintf('%sadmin/css/ws-form-admin-template%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;

				// Submissions
				case $this->hook_suffix_form_submit :	

					// CSS - jQuery UI
					wp_enqueue_style($this->plugin_name . '-jquery-ui', sprintf('%sadmin/jquery/jquery-ui%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;

				// Settings
				case $this->hook_suffix_form_settings :

					$is_ws_form_page = true;

					break;

				// Style - List
				case $this->hook_suffix_form_style :

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-layout', WS_Form_Common::get_api_path('helper/ws-form-css-admin', sprintf('_wpnonce=%s', wp_create_nonce('wp_rest'))), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					$is_ws_form_page = true;

					break;

				// Style - Add
				case $this->hook_suffix_form_style_add : 		

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-layout', WS_Form_Common::get_api_path('helper/ws-form-css-admin', sprintf('_wpnonce=%s', wp_create_nonce('wp_rest'))), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-template', sprintf('%sadmin/css/ws-form-admin-template%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;
				// WordPress Posts
				case 'post.php' : 
				case 'post-new.php' :
				case 'widgets.php' :

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-template', sprintf('%sadmin/css/ws-form-admin-template%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

					$is_ws_form_page = true;

					break;
			}

			// CSS - WordPress (Used throughout WordPress to style admin icon and other integral functions like the 'Add Form' feature)
			wp_enqueue_style($this->plugin_name . '-wp', sprintf('%sadmin/css/ws-form-admin-wp%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

			if(strpos($hook, WS_FORM_NAME) !== false) {

				// CSS - Admin
				wp_enqueue_style($this->plugin_name . '-admin', sprintf('%sadmin/css/ws-form-admin%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');

				if(is_rtl()) {

					// CSS - RTL
					wp_enqueue_style($this->plugin_name . '-admin-rtl', sprintf('%sadmin/css/ws-form-admin-rtl%s.css', WS_FORM_PLUGIN_DIR_URL, $min), array(), $this->version, 'all');
				}
			}

			// Dequeue styles added by other plugins (they should only be enqueuing on their admin pages)
			if($is_ws_form_page) {

				// Addify
				wp_dequeue_script('addify_ps-select2-css');
				wp_dequeue_script('addify_ps-select2-bscss');

				// Simple Podcast Press
				wp_dequeue_script('spp_wp_admin_js_bootstrap_min');
				wp_dequeue_script('spp-admin-script');
				wp_dequeue_style('spp_wp_admin_css_bootstrap');
				wp_dequeue_style('spp_wp_admin_css_bootstrap_responsive');
				wp_dequeue_style('spp_wp_admin_css_common');
				wp_dequeue_style('spp_wp_admin_css_fontawesome');
				wp_dequeue_style('spp_wp_admin_css_project');
				wp_dequeue_style('spp_wp_admin_css');
			}
		}

		// Register the JavaScript for the admin area.
		public function enqueue_scripts($hook) {

			global $wp_version;

			// Minified scripts?
			$min = SCRIPT_DEBUG ? '' : '.min';

			// Get form ID
			$this->form_id = absint(WS_Form_Common::get_query_var('id', 0));

			// Sidebar reset ID
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$sidebar_id = array_keys($settings_form_admin['sidebars']);
			$sidebar_reset_id = WS_Form_Common::get_query_var('sidebar', 'toolbox');
			if(!in_array($sidebar_reset_id, $sidebar_id)) { $sidebar_reset_id = 'toolbox'; }

			// Sidebar tab key
			$sidebar_tab_key = WS_Form_Common::get_query_var('tab', false);

			// WP NONCE
			$x_wp_nonce = wp_create_nonce('wp_rest');

			// Check WordPress version
			$wp_new = WS_Form_Common::wp_version_at_least('5.3');

			// Enqueued scripts settings
			$ws_form_settings = array(

				// Nonce
				'nonce'							=> $x_wp_nonce,		// Backward compatibility for older add-ons (Will be removed eventually)
				'x_wp_nonce'					=> $x_wp_nonce,
				'wsf_nonce_field_name'			=> WS_FORM_POST_NONCE_FIELD_NAME,
				'wsf_nonce'						=> wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME),

				// URL
				'url_ajax'						=> WS_Form_Common::get_api_path(),
				'url_home'						=> get_home_url(null, '/'),

				// Permalink
				'permalink_custom'				=> (get_option('permalink_structure') != ''),

				// Default label - Group
				'label_default_group'			=> __('Tab', 'ws-form'),

				// Default label - Section
				'label_default_section'			=> __('Section', 'ws-form'),

				// Default label - Field
				'label_default_field'			=> __('Field', 'ws-form'),

				// HTML Editor settings
				'html_editor_settings'			=> $this->html_editor_settings,

				// Field prefix
				'field_prefix'					=> WS_FORM_FIELD_PREFIX,

				// Locale
				'locale'						=> get_locale(),
				'locale_user'					=> get_user_locale(),

				// Edition
				'edition'						=> WS_FORM_EDITION,

				// Version
				'version'						=> WS_FORM_VERSION,

				// Date / time format
				'date_format'					=> get_option('date_format'),
				'time_format'					=> get_option('time_format'),

				// Date / time
				'date'							=> $wp_new ? wp_date(get_option('date_format')) : gmdate(get_option('date_format'), current_time('timestamp')),
				'time'							=> $wp_new ? wp_date(get_option('time_format')) : gmdate(get_option('time_format'), current_time('timestamp')),

				// Sidebar
				'sidebar_reset_id'				=> $sidebar_reset_id,
				'sidebar_tab_key'				=> $sidebar_tab_key,

				// Preview update
				'helper_live_preview'			=> WS_Form_Common::option_get('helper_live_preview', true),

				// RTL
				'rtl'							=> is_rtl(),

				// Shortcode
				'shortcode'						=> WS_FORM_SHORTCODE,

				// Intro
				'intro'							=> $this->intro,

			);

			// Add default style ID
			if(WS_Form_Common::styler_enabled()) {

				$ws_form_style = new WS_Form_Style();
				$ws_form_settings['style_id_default'] = $ws_form_style->get_style_id_default();
			}

			// Form class
			wp_register_script($this->plugin_name . '-form-common', sprintf('%sshared/js/ws-form%s.js', WS_FORM_PLUGIN_DIR_URL, $min), array('jquery'), $this->version, true);

			// Form class - Admin
			wp_register_script($this->plugin_name, sprintf('%sadmin/js/ws-form-admin%s.js', WS_FORM_PLUGIN_DIR_URL, $min), array('jquery', $this->plugin_name . '-form-common'), $this->version, true);

			// Scripts by hook
			switch($hook) {

				// WS Form - Welcome
				case $this->hook_suffix_form_welcome :

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					break;

				// WS Form - Forms
				case $this->hook_suffix_form_sub :

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Add Form
				case $this->hook_suffix_form_add :

					// jQuery UI
					wp_enqueue_script('jquery-ui-tabs');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Edit Form
				case $this->hook_suffix_form_edit :

					// jQuery UI
					wp_enqueue_script('jquery-ui-core');
					wp_enqueue_script('jquery-ui-draggable');
					wp_enqueue_script('jquery-ui-droppable');
					wp_enqueue_script('jquery-ui-tabs');
					wp_enqueue_script('jquery-ui-slider');
					wp_enqueue_script('jquery-ui-sortable');

					// jQuery touch punch
					wp_enqueue_script('jquery-touch-punch');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					// Enqueue WP editors

					// Media selector
					wp_enqueue_media();

					// TinyMCE
					if(WS_Form_Common::version_compare($wp_version, '4.8') >= 0) {

						// Enable rich editing for this view (Overrides 'Disable the visual editor when writing' option for current user)
						add_filter('user_can_richedit', function($user_can_richedit) { return true; });

						wp_enqueue_editor();
					}

					// CodeMirror
					if(WS_Form_Common::version_compare($wp_version, '4.9') >= 0) {

						wp_enqueue_code_editor(array('type' => 'text/html'));
					}

					// Intro - Version 3.3.1
					if($this->intro) {

						wp_enqueue_script($this->plugin_name . '-intro', sprintf('%sadmin/js/external/intro%s.js', WS_FORM_PLUGIN_DIR_URL, $min), array('jquery'), '3.3.1', true);
					}

					// Select2 (Check made because WooCommerce enqueues this) - Version 4.0.5
					wp_enqueue_script($this->plugin_name . '-select2', sprintf('%sshared/js/external/select2.full%s.js', WS_FORM_PLUGIN_DIR_URL, $min), array('jquery'), '4.0.5', false);

					$this->deregister_scripts[] = 'select2.min.js';
					$this->deregister_scripts[] = 'select2.js';

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Form Submissions
				case $this->hook_suffix_form_submit :

					// jQuery UI
					wp_enqueue_script('jquery-ui-datepicker');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Form Styles
				case $this->hook_suffix_form_style :

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Add Form Style
				case $this->hook_suffix_form_style_add :

					// jQuery UI
					wp_enqueue_script('jquery-ui-tabs');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					// Output config as script in admin footer
					self::admin_footer_config_script();

					break;

				// WS Form - Migrate
				case $this->hook_suffix_form_migrate :

					// jQuery UI
					wp_enqueue_script('jquery-ui-tabs');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					break;

				// WS Form - Settings
				case $this->hook_suffix_form_settings :

					// WordPress Media
					wp_enqueue_media();

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					$this->ws_form_hook = $hook;

					break;

				// WordPress Posts
				case 'post.php' : 
				case 'post-new.php' : 
				case 'widgets.php' : 

					$post_type = WS_Form_Common::get_query_var('post_type', 'post');
					$render_media_button = apply_filters('wsf_render_media_button', true, $post_type);
					if($render_media_button) {

						add_action('media_buttons', array($this, 'media_button'));
						add_action('admin_footer', array($this, 'media_buttons_html'));
					}

					if(WS_Form_Common::is_block_editor()) {

						// Create public instance
						$ws_form_public = new WS_Form_Public();

						// Visual builder enqueues
						do_action('wsf_enqueue_visual_builder');

						// Add public footer to speed up loading of config
						$ws_form_public->wsf_form_json[0] = true;
						add_action('admin_footer', array($ws_form_public, 'wp_footer'));

					} else {

						// WS Form
						wp_enqueue_script($this->plugin_name . '-form-common');
						wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
						wp_enqueue_script($this->plugin_name);
					}

					break;

				// Dashboard
				case 'index.php' :

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// Plugins
				case 'plugins.php' :

					// Feedback
					add_action('admin_footer', array($this, 'feedback'));
					break;
			}

			// Enqueue admin count submit unread script
			$disable_count_submit_unread = WS_Form_Common::option_get('disable_count_submit_unread', false);
			if(!$disable_count_submit_unread) {

				wp_register_script($this->plugin_name . '-admin-count-submit-unread', sprintf('%sadmin/js/ws-form-admin-count-submit-unread%s.js', WS_FORM_PLUGIN_DIR_URL, $min), array('jquery'), $this->version, false);

				$ws_form_form = new WS_Form_Form();
				$count_submit_unread_total = $ws_form_form->db_get_count_submit_unread_total();

				$ws_form_admin_count_submit_read_settings = array(

					'count_submit_unread_total' => $count_submit_unread_total,
					'count_submit_unread_ajax_url' => sprintf('%s?_wpnonce=%s', WS_Form_Common::get_api_path('helper/count-submit-unread/'), wp_create_nonce('wp_rest'))
				);
				wp_localize_script($this->plugin_name . '-admin-count-submit-unread', 'ws_form_admin_count_submit_read_settings', $ws_form_admin_count_submit_read_settings);
				wp_enqueue_script($this->plugin_name . '-admin-count-submit-unread');
			}
		}

		// Output config as script in admin footer
		public function admin_footer_config_script() {

			add_action('admin_footer', function() {

				// Get config
				$json_config = WS_Form_Config::get_config(false, array(), true);
?>
<script>
	
	// Embed config
	var wsf_form_json_config = {};
<?php
				// Split up config (Fixes HTTP2 error on certain hosting providers that can't handle the full JSON string)
				foreach($json_config as $key => $config) {

?>	wsf_form_json_config.<?php WS_Form_Common::echo_esc_html($key); ?> = <?php WS_Form_Common::echo_wp_json_encode($config); ?>;
<?php
				}
?>
</script>
<?php
			});
		}

		// WP print scripts
		public function wp_print_scripts() {

			// Get registered scripts
			global $wp_scripts;
			if(!isset($wp_scripts->registered)) { return; }

			// Do not run if there are no deregister scripts
			if(count($this->deregister_scripts) === 0) { return; }

			// Only run this if on a WS Form admin page
			if($this->ws_form_hook === false) { return; }

			foreach($wp_scripts->registered as $handle => $script) {

				if(!isset($script->src)) { continue; }

				// jQuery UI Sortable fix
				// Disable enqueue of bundled jQuery UI sortable due to WordPress 5.9 using 1.13.0 that has a bug. Enqueue 1.13.1 instead.
				// https://github.com/jquery/jquery-ui/issues/2001
				if(
					(strpos($script->src, 'wp-includes/js/jquery/ui/sortable') !== false) &&
					isset($script->ver) &&
					($script->ver == '1.13.0')
				) {

					// Minified scripts?
					$min = SCRIPT_DEBUG ? '' : '.min';

					// Change path to sortable
					$wp_scripts->registered[$handle]->src = sprintf('%sadmin/js/external/jquery/ui/sortable%s.js', WS_FORM_PLUGIN_DIR_URL, $min);
				}

				foreach($this->deregister_scripts as $deregister_script) {

					if(strpos($script->src, $deregister_script) !== false) {

						unset($wp_scripts->registered[$handle]);
					}
				}
			}
		}

		// Feedback
		public function feedback() {
?>
<script>

	(function($) {

		'use strict';

		var wsf_feedback_deactivate_url = false;

		// Close modal
		function wsf_feedback_modal_close() {

			$('#wsf-feedback-modal').hide();
			$('#wsf-feedback-modal-backdrop').hide();

			if(wsf_feedback_deactivate_url !== false) {

				location.href = wsf_feedback_deactivate_url;
			}
		}

		// On load
		$(function() {

			// Escape key
			$(document).on('keydown', function(e) {

				if(e.keyCode == 27) { 

					// Close modal
					wsf_feedback_modal_close();
				}
			});

			// Modal open
			$('[data-slug="ws-form"] .deactivate a, [data-slug="ws-form-lite"] .deactivate a, [data-slug="ws-form-pro"] .deactivate a').on('click', function(e) {

				e.preventDefault();

				wsf_feedback_deactivate_url = $(this).attr('href');

				// Show modal
				$('#wsf-feedback-modal-backdrop').show();
				$('#wsf-feedback-modal').show();
				$('[data-action="wsf-feedback-submit"]').attr('disabled', false);
			});

			// Click modal backdrop
			$(document).on('click', '#wsf-feedback-modal-backdrop', function(e) {

				// Close modal
				wsf_feedback_modal_close();
			});

			// Click close button
			$('[data-action="wsf-close"]').on('click', function() {

				// Close modal
				wsf_feedback_modal_close();
			});

			// Toggle fields
			$('[name="wsf_feedback_reason"]').on('change', function() {

				var feedback_reason_other = $('#wsf-feedback-reason-other').is(':checked');

				if(feedback_reason_other) {

					$('#wsf-feedback-reason-other-text').show().trigger('focus');

				} else {

					$('#wsf-feedback-reason-other-text').hide();
				}

				var feedback_reason_found_better_plugin = $('#wsf-feedback-reason-found-better-plugin').is(':checked');

				if(feedback_reason_found_better_plugin) {

					$('#wsf-feedback-reason-found-better-plugin-select').show().trigger('focus');

				} else {

					$('#wsf-feedback-reason-found-better-plugin-select').hide();
				}

				var feedback_reason_error = $('#wsf-feedback-reason-error').is(':checked');

				if(feedback_reason_error) {

					$('#wsf-feedback-reason-error-wrapper').show();

				} else {

					$('#wsf-feedback-reason-error-wrapper').hide();
				}
			});

			// Submit
			$('[data-action="wsf-feedback-submit"]').on('click', function() {

				$(this).prop('disabled', true);

				$.ajax({

					url: '<?php WS_Form_Common::echo_esc_html(WS_Form_Common::get_api_path('helper/deactivate-feedback-submit/')); ?>',
					data: {

						'wsf_nonce_field_name' : '<?php WS_Form_Common::echo_esc_attr(WS_FORM_POST_NONCE_FIELD_NAME); ?>',
						'wsf_nonce': '<?php WS_Form_Common::echo_esc_attr(wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)); ?>',
						'feedback_reason': $('[name="wsf_feedback_reason"]:checked').val(),
						'feedback_reason_error': $('[name="wsf_feedback_reason_error"]').val(),
						'feedback_reason_found_better_plugin': $('[name="wsf_feedback_reason_found_better_plugin"]').val(),
						'feedback_reason_other': $('[name="wsf_feedback_reason_other"]').val(),
					},
					type: 'POST',
					beforeSend: function(xhr) {

						xhr.setRequestHeader('X-WP-Nonce', '<?php WS_Form_Common::echo_esc_html(wp_create_nonce('wp_rest')); ?>');
					},
					complete: function(data){

						wsf_feedback_modal_close();
					}
				});
			});

			// Defaults
			$('#wsf-feedback-reason-other-text').hide();
			$('#wsf-feedback-reason-found-better-plugin-select').hide();
			$('#wsf-feedback-reason-error-wrapper').hide();
		});

	})(jQuery);

</script>

<!-- WS Form - Modal - Feedback -->
<div id="wsf-feedback-modal-backdrop" class="wsf-modal-backdrop" style="display: none;"></div>

<div id="wsf-feedback-modal" class="wsf-modal" style="display: none; margin-left: -200px; margin-top: -180px; width: 400px;">

<div id="wsf-feedback">

<!-- WS Form - Modal - Feedback - Header -->
<div class="wsf-modal-title"><?php

	WS_Form_Common::echo_get_admin_icon('#002e5f', false);	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

?><h2><?php esc_html_e('Feedback', 'ws-form'); ?></h2></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - Feedback - Header -->

<!-- WS Form - Modal - Feedback - Content -->
<div class="wsf-modal-content">

<form id="wsf-feedback-form">

<fieldset>

<p><?php
	
	WS_Form_Common::echo_esc_html(sprintf(

		/* translators: %s = Presentable plugin name, e.g. WS Form PRO */
		__('We would greatly appreciate your feedback about why you are deactivating %s. Thank you for your help!', 'ws-form'),
		WS_FORM_NAME_PRESENTABLE
	));

?></p>

<label><input type="radio" name="wsf_feedback_reason" value="Upgraded" /> <?php
	
	echo sprintf(	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		/* translators: %s = WS Form PRO */
		esc_html__("I'm upgrading to %s", 'ws-form'),

		sprintf(

			'<a href="%s" target="_blank">WS Form PRO</a>',
			esc_url(WS_Form_Common::get_plugin_website_url('', 'plugins_deactivate'))
		)
	);

?></label>
<label><input type="radio" name="wsf_feedback_reason" value="Temporary" /> <?php esc_html_e("I'm temporarily deactivating", 'ws-form'); ?></label>

<label><input type="radio" id="wsf-feedback-reason-error" name="wsf_feedback_reason" value="Error" /> <?php esc_html_e('The plugin did not work', 'ws-form'); ?></label>

<div id="wsf-feedback-reason-error-wrapper">
<textarea id="wsf-feedback-reason-error-text" name="wsf_feedback_reason_error" placeholder="<?php esc_attr_e('Please describe the error...', 'ws-form'); ?>" rows="3"></textarea>
<p><em><?php esc_html_e("We'd love to help!", 'ws-form'); ?><?php

	echo sprintf(	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		' <a href="%s" target="_blank">%s</a>',
		esc_url(WS_Form_Common::get_plugin_website_url('/support/', 'plugins_deactivate')),
		esc_html__('Get Support', 'ws-form')
	);

?></em></p>
</div>

<label><input type="radio" name="wsf_feedback_reason" value="No Longer Need" /> <?php esc_html_e('I no longer need the plugin', 'ws-form'); ?></label>

<label><input type="radio" id="wsf-feedback-reason-found-better-plugin" name="wsf_feedback_reason" value="Found Better Plugin" /> <?php esc_html_e('I found a better plugin', 'ws-form'); ?></label>

<select id="wsf-feedback-reason-found-better-plugin-select" name="wsf_feedback_reason_found_better_plugin">
<option value=""><?php esc_html_e('Select...', 'ws-form'); ?></option>
<option value="Caldera Forms">Caldera Forms</option>
<option value="Contact Form 7">Contact Form 7</option>
<option value="Formidable Forms">Formidable Forms</option>
<option value="Fluent Forms">Fluent Forms</option>
<option value="Gravity Forms">Gravity Forms</option>
<option value="Ninja Forms">Ninja Forms</option>
<option value="Visual Form Builder">Visual Form Builder</option>
<option value="weForms">weForms</option>
<option value="WPForms">WPForms</option>
<option value="Other"><?php esc_html_e('Other', 'ws-form'); ?></option>
</select>

<label><input type="radio" id="wsf-feedback-reason-other" name="wsf_feedback_reason" value="Other" /> <?php esc_html_e('Other', 'ws-form'); ?></label>

<textarea id="wsf-feedback-reason-other-text" name="wsf_feedback_reason_other" placeholder="<?php esc_attr_e('Please specify...', 'ws-form'); ?>" rows="3"></textarea>

</fieldset>

</form>

</div>
<!-- /WS Form - Modal - Feedback - Content -->

<!-- WS Form - Modal - Feedback - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Skip &amp; Deactivate', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-feedback-submit">
<button class="button button-primary" data-action="wsf-feedback-submit"><?php esc_html_e('Submit &amp; Deactivate', 'ws-form'); ?></button>
</div>

</div>
<!-- /WS Form - Modal - Feedback - Buttons -->

</div>

</div>
<!-- /WS Form - Modal - Feedback -->
<?php
		}

		// Customize register
		public function customize_register($wp_customize) {

			if(WS_Form_Common::customizer_visible()) {

				// The class responsible for customizing
				require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-customize.php';

				new WS_Form_Customize($wp_customize);
			}
		}

		// Media button
		public function media_button() {

			// Build add form button
?><a href="#" class="button wsf-button-add-form"><span class="wsf-button-add-form-icon"><?php

	WS_Form_Common::echo_get_admin_icon('#888888', false);	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

?></span><?php WS_Form_Common::echo_esc_html(sprintf(

	/* translators: %s = WS Form */
	__('Add %s', 'ws-form'),

	WS_FORM_NAME_GENERIC

)); ?></a><?php

		}

		// Media buttons - HTML
		public function media_buttons_html() {
?>
<script>

	(function($) {

		'use strict';

		function wsf_add_form_modal_close() {

			$('#wsf-add-form-modal').hide();
			$('#wsf-add-form-modal-backdrop').hide();
		}

		// On load
		$(function() {

			// Escape key
			$(document).on('keydown', function(e) {

				if(e.keyCode == 27) { 

					// Close modal
					wsf_add_form_modal_close();
				}
			});

			// Modal - Actions
			$('[data-action]', $('#wsf-add-form-modal')).on('click', function() {

				var action = $(this).attr('data-action');

				switch(action) {

					case 'wsf-close' :

						// Close modal
						wsf_add_form_modal_close();

						break;

					case 'wsf-inject' :

						// Get form ID
						var id = $('#wsf-post-add-form-id').val();

						// Build shortcode
						var shortcode = '[<?php WS_Form_Common::echo_esc_html(WS_FORM_SHORTCODE); ?> id="' + id + '"]';

						// Insert into editor
						wp.media.editor.insert(shortcode);

						// Close modal
						wsf_add_form_modal_close();

						break;

					case 'wsf-add' :

						location.href = '<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>';

						// Close modal
						wsf_add_form_modal_close();

						break;
				}
			});

			// Open modal
			$(document).on('click', '.wsf-button-add-form', function(e) {

				e.preventDefault();

				// Show modal
				$('#wsf-add-form-modal-backdrop').show();
				$('#wsf-add-form-modal').show();
			});

			// Click modal backdrop
			$(document).on('click', '#wsf-add-form-modal-backdrop', function(e) {

				// Close modal
				wsf_add_form_modal_close();
			});
		});

	})(jQuery);

</script>

<!-- WS Form - Modal - Add Form -->
<div id="wsf-add-form-modal-backdrop" class="wsf-modal-backdrop" style="display: none;"></div>

<div id="wsf-add-form-modal" class="wsf-modal" style="display: none; margin-left: -200px; margin-top: -100px; width: 400px;">

<div id="wsf-add-form">

<!-- WS Form - Modal - Add Form - Header -->
<div class="wsf-modal-title"><?php

	WS_Form_Common::echo_get_admin_icon('#002e5f', false);	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

?><h2><?php

	echo sprintf(	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		/* translators: %s = WS Form */
		esc_html__('Add %s', 'ws-form'),

		WS_FORM_NAME_GENERIC
	);

?></h2></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - Add Form - Header -->

<!-- WS Form - Modal - Add Form - Content -->
<div class="wsf-modal-content">

<form>
<?php

	// Get forms from API
	$ws_form_form = new WS_Form_Form();
	$forms = $ws_form_form->db_read_all('', 'NOT status="trash"', 'label', '', '', false);

	if($forms) {
?>
<label for="wsf-post-add-form-id"><?php esc_html_e('Select the form you want to add...', 'ws-form'); ?></label>
<select id="wsf-post-add-form-id">
<?php
		foreach($forms as $form) {

?><option value="<?php WS_Form_Common::echo_esc_attr($form['id']); ?>"><?php WS_Form_Common::echo_esc_html($form['label']); ?> (<?php esc_html_e('ID', 'ws-form'); ?>: <?php WS_Form_Common::echo_esc_html($form['id']); ?>)</option>
<?php
		}
?>
</select>
<?php
	} else {
?>
<p><?php esc_html_e("You haven't created any forms yet.", 'ws-form'); ?></p>
<p><a href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php esc_html_e('Click here to create a form', 'ws-form'); ?></a></p>
<?php
	}
?>
</form>

</div>
<!-- /WS Form - Modal - Add Form - Content -->

<!-- WS Form - Modal - Add Form - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Cancel', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-add-form">
<?php

	if($forms) {
?>
<button class="button button-primary" data-action="wsf-inject"><?php esc_html_e('Insert Form', 'ws-form'); ?></button>
<?php
	} else {
?>
<button class="button button-primary" data-action="wsf-add"><?php esc_html_e('Add Form', 'ws-form'); ?></button>
<?php
	}
?>
</div>

</div>
<!-- /WS Form - Modal - Add Form - Buttons -->

</div>

</div>
<!-- /WS Form - Modal - Add Form -->
<?php
		}

		// Add admin menu pages (visible and hidden)
		public function admin_menu() {

			// Unread submission span
			$disable_count_submit_unread = WS_Form_Common::option_get('disable_count_submit_unread', false);
			$count_submit_unread_total_html = $disable_count_submit_unread ? '' : '<span class="wsf-submit-unread-total wsf-submit-unread"></span>';

			// Forms - List
			$this->hook_suffix_form = add_menu_page(

				WS_FORM_NAME_GENERIC,
				WS_FORM_NAME_GENERIC . $count_submit_unread_total_html,
				'read_form',
				$this->plugin_name,
				false,
				WS_Form_Common::get_admin_icon(),
				35
			);
			add_action('load-' . $this->hook_suffix_form, array($this, 'ws_form_wp_list_table_form_options'));

			// Welcome (Hidden)
			$this->hook_suffix_form_welcome = add_submenu_page(

				'options.php',
				__('Welcome', 'ws-form'),

				sprintf(

					/* translators: %s = Presentable name (e.g. WS Form PRO) */
					__('Welcome to %s', 'ws-form'), 

					WS_FORM_NAME_GENERIC
				),

				'manage_options_wsform',
				$this->plugin_name . '-welcome',
				array($this, 'admin_page_welcome')
			);

			// Forms - List (Sub Menu)
			$this->hook_suffix_form_sub = add_submenu_page(

				$this->plugin_name,
				__('Forms', 'ws-form'),
				__('Forms', 'ws-form'),
				'read_form',
				$this->plugin_name,
				array($this, 'admin_page_form')
			);

			// Form - Add
			$this->hook_suffix_form_add = add_submenu_page(

				$this->plugin_name,
				__('Add Form', 'ws-form'),
				__('Add Form', 'ws-form'),
				'create_form',
				$this->plugin_name . '-add',
				array($this, 'admin_page_form_add')
			);

			// Form - Submissions
			$this->hook_suffix_form_submit = add_submenu_page(

				$this->plugin_name,
				__('Submissions', 'ws-form'),
				__('Submissions', 'ws-form') . $count_submit_unread_total_html,
				'read_submission',
				$this->plugin_name . '-submit',
				array($this, 'admin_page_form_submit')
			);
			add_action('load-' . $this->hook_suffix_form_submit, array($this, 'ws_form_wp_list_table_submit_options'));

			// Forms - Edit (Hidden)
			$this->hook_suffix_form_edit = add_submenu_page(

				'options.php',
				__('Edit Form', 'ws-form'),
				WS_FORM_NAME_GENERIC,
				'edit_form',
				$this->plugin_name . '-edit',
				array($this, 'admin_page_form_edit')
			);

			// Styler
			if(WS_Form_Common::styler_visible_admin()) {

				// Styler
				$this->hook_suffix_form_style = add_submenu_page(

					$this->plugin_name,
					__('Styles', 'ws-form'),
					__('Styles', 'ws-form'),
					'read_form_style',
					$this->plugin_name . '-style',
					array($this, 'admin_page_form_style')
				);
				add_action('load-' . $this->hook_suffix_form_style, array($this, 'ws_form_wp_list_table_style_options'));

				// Styler - Add
				$this->hook_suffix_form_style_add = add_submenu_page(

					$this->plugin_name,
					__('Add Style', 'ws-form'),
					__('Add Style', 'ws-form'),
					'create_form',
					$this->plugin_name . '-style-add',
					array($this, 'admin_page_form_style_add')
				);
			}

			// Customizer - Legacy
			if(WS_Form_Common::customizer_visible()) {

				$page = WS_Form_Common::get_query_var('page');
				$id = absint(WS_Form_Common::get_query_var('id'));
				if(($page === 'ws-form-edit') && ($id > 0)) {

					$customize_url = WS_Form_Common::get_customize_url('ws_form', $id);

				} else {

					$customize_url = WS_Form_Common::get_customize_url('ws_form');
				}

				$this->hook_suffix_customize = add_submenu_page(

					$this->plugin_name,
					__('Customize', 'ws-form'),
					__('Customize', 'ws-form'),
					'customize',
					$customize_url
				);
			}

			// Settings
			$this->hook_suffix_form_settings = add_submenu_page(

				$this->plugin_name,
				__('Settings', 'ws-form'),
				__('Settings', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-settings',
				array($this, 'admin_page_settings')
			);

			// Upgrade to PRO
			$this->hook_suffix_form_upgrade = add_submenu_page(

				$this->plugin_name,
				__('Upgrade to PRO', 'ws-form'),
				__('Upgrade to PRO', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-upgrade',
				array($this, 'admin_page_upgrade')
			);
			// Add-Ons
			$this->hook_suffix_form_add_ons = add_submenu_page(

				$this->plugin_name,
				__('Add-Ons', 'ws-form'),
				__('Add-Ons', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-add-ons',
				array($this, 'admin_page_add_ons')
			);

			add_filter('default_hidden_columns', array($this, 'ws_form_default_hidden_columns'), 10, 2); 
			add_filter('screen_settings', array($this, 'screen_settings_submit'), 10, 2);
		}

		public function screen_settings_submit($current, $screen) {

			if(!in_array($screen->id, array($this->hook_suffix_form, $this->hook_suffix_form_submit))) { return $current; }

			// Submissions - Exclude hidden fields
			if($screen->id === $this->hook_suffix_form_submit) {

				$clear_hidden_fields = (get_user_meta(get_current_user_id(), 'ws_form_submissions_clear_hidden_fields', true) === 'on');
				$current .= sprintf('<fieldset class="screen-options"><legend>%s</legend>', __('Submissions', 'ws-form'));
				$current .= sprintf('<label for="ws_form_submissions_clear_hidden_fields"><input type="checkbox" name="ws_form_submissions_clear_hidden_fields" id="ws_form_submissions_clear_hidden_fields"%s />%s</label>', ($clear_hidden_fields ? ' checked' : ''), __('Clear hidden fields', 'ws-form')); 
				$current .= '</fieldset>';
			}

			// Add hidden field for form ID
			$current .= sprintf('<input type="hidden" name="id" value="%u" />', absint($this->form_id));
			$current .= sprintf('<input type="hidden" name="page" value="%s" />', esc_attr(WS_Form_Common::get_query_var('page')));
			$current .= sprintf('<input type="hidden" name="%s" value="%s" />', esc_attr(WS_FORM_POST_NONCE_FIELD_NAME), esc_attr(wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)));

			return $current;
		}

		// Default hidden submit columns
		public function ws_form_default_hidden_columns($hidden, $screen) {

			if(
				!$screen ||
				!isset($screen->id)
			) {
				return $hidden;
			}

			// Process hidden columns by screen ID
			switch($screen->id) {

				case 'ws-form_page_ws-form-submit' :

					$form_id = $this->ws_form_wp_list_table_submit_obj->form_id;

					if($form_id > 0) {

						$ws_form_submit = new WS_Form_Submit;
						$ws_form_submit->form_id = $form_id;
						$submit_fields = $ws_form_submit->db_get_submit_fields();

						foreach($submit_fields as $id => $field) {

							$field_hidden = $field['hidden'];
							if($field_hidden) { $hidden[] = WS_FORM_FIELD_PREFIX . $id; }
						}
					}

					break;
			}

			return $hidden;
		}

		// Form screen options
		public function ws_form_wp_list_table_form_options() {

			add_screen_option('per_page', array(

				'label' => __('Forms per page:', 'ws-form'),
				'default' => 20,
				'option' => 'ws_form_forms_per_page'
			));

			// Create forms object (List of forms)
			$this->ws_form_wp_list_table_form_obj = new WS_Form_WP_List_Table_Form();
		}

		// Submission screen options
		public function ws_form_wp_list_table_submit_options() {

			add_screen_option('per_page', array(

				'label' => __('Submissions per page:', 'ws-form'),
				'default' => 20,
				'option' => 'ws_form_submissions_per_page'
			));

			// Create submissions object (List of submissions)
			$this->ws_form_wp_list_table_submit_obj = new WS_Form_WP_List_Table_Submit();
		}

		// Style screen options
		public function ws_form_wp_list_table_style_options() {

			add_screen_option('per_page', array(

				'label' => __('Styles per page:', 'ws-form'),
				'default' => 20,
				'option' => 'ws_form_styles_per_page'
			));

			// Create styles object (List of styles)
			$this->ws_form_wp_list_table_style_obj = new WS_Form_WP_List_Table_Style();
		}

		// Set screen option
		public function ws_form_set_screen_option($status, $option, $value) {

			switch($option) {

				case 'ws_form_forms_per_page' :

					return $value;

				case 'ws_form_submissions_per_page' :

					// Exclude hidden fields
					update_user_meta(get_current_user_id(), 'ws_form_submissions_clear_hidden_fields', WS_Form_Common::get_query_var_nonce('ws_form_submissions_clear_hidden_fields'));

					return $value;
			}

			return $status;
		}

		// Block editor
		public function enqueue_block_assets() {

			if(is_admin()) {

				// Visual builder enqueues
				do_action('wsf_enqueue_visual_builder');
			}
		}

		public function enqueue_block_editor_assets_v1() {

			// Get forms from API
			$ws_form_form = new WS_Form_Form();
			$forms = $ws_form_form->db_read_all('', 'NOT status="trash"', 'label ASC, id ASC', '', '', false);

			// Enqueue block JavaScript in footer
			wp_enqueue_script(

				'wsf-block',
				plugins_url('admin/js/ws-form-block.js', WS_FORM_PLUGIN_ROOT_FILE),
				array('wp-blocks', 'wp-element', 'wp-components'),
				$this->version,
				true
			);

			// Build preview HtML
			$preview = sprintf(

				// Inline style because it is loaded in an iframe
				'<img src="%s" alt="%s" style="width:100%%" />',
				esc_attr(sprintf('%sadmin/images/block-preview.gif', WS_FORM_PLUGIN_DIR_URL)),
				esc_attr(__('WS Form Block Preview', 'ws-form'))
			);

			// Localize block JavaScript
			wp_localize_script('wsf-block', 'wsf_settings_block', array(

				// Add Form
				'form_add' => array(

					'name'						=> 'wsf-block/form-add',
					'label'						=> WS_FORM_NAME_PRESENTABLE,
					/* translators: %s = Presentable name (e.g. WS Form PRO) */
					'description'				=> sprintf(__('Add a form to your web page using %s.', 'ws-form'), WS_FORM_NAME_PRESENTABLE),
					'category'					=> WS_FORM_NAME,
					'keywords'					=> array(WS_FORM_NAME_PRESENTABLE, __('form', 'ws-form')),
					'preview'					=> $preview,
					'no_forms'					=> __("You haven't created any forms yet.", 'ws-form'),
					'form_not_selected'			=> __('From the block settings sidebar, choose the form you would like to add.', 'ws-form'),
					'form_id_options_label'		=> __('Form', 'ws-form'),
					'form_id_options_select'	=> __('Select...', 'ws-form'),
					'form_element_id_label'		=> __('ID (Optional)', 'ws-form'),
					'id'						=> __('ID', 'ws-form'),
					'add'						=> __('Add New', 'ws-form'),
					'url_add'					=> esc_url(WS_Form_Common::get_admin_url('ws-form-add')),
					'form_action'				=> esc_url(WS_Form_Common::get_api_path() . 'submit')
				),

				'forms'						=> $forms
			));
		}

		// Gutenbery Editor Block - Register category
		public function block_categories($categories, $post) {

			return array_merge(

				$categories,

				array(

					array(

						'slug'  => WS_FORM_NAME,
						'title' => WS_FORM_NAME_PRESENTABLE
					)
				)
			);
		}

		// Gutenberg Editor Blocks - Register
		public function register_blocks() {

			if(function_exists('register_block_type')) {

				$block_config = array(

					'editor_script'		=> 'wsf-block',

					'render_callback'	=> array($this, 'block_render')
				);

				register_block_type('wsf-block/form-add', $block_config);
			}
		}

		// Block rendering
		public function block_render($attributes, $content) {

			// Do not render if form ID is not set
			if(!isset($attributes['form_id'])) { return ''; }

			// Get form ID
			$form_id = absint($attributes['form_id']);

			// Do not render if form ID = 0
			if($form_id == 0) { return ''; }

			// Get form element ID
			$form_element_id = isset($attributes['form_element_id']) ? $attributes['form_element_id'] : '';
			if($form_element_id != '') { $form_element_id = sprintf(' element_id="%s"', esc_attr($form_element_id)); }

			// Get className
			$form_class_name = isset($attributes['className']) ? $attributes['className'] : '';
			if($form_class_name != '') { $form_class_name = sprintf(' class="%s"', esc_attr($form_class_name)); }

			$return_html = do_shortcode(sprintf('[%s id="%u"%s%s]', WS_FORM_SHORTCODE, $form_id, $form_element_id, $form_class_name));

			return $return_html;
		}

		// Pattern categories
		public function pattern_categories() {

			if(function_exists('register_block_pattern_category')) {

				register_block_pattern_category(

					WS_FORM_NAME,
					array('label' => WS_FORM_NAME_PRESENTABLE)
				);
			}
		}

		// Patterns
		public function patterns() {

			if(function_exists('register_block_pattern')) {

				// Get patterns from config
				$patterns = WS_Form_Config::get_patterns();

				// Process each pattern
				foreach($patterns as $id => $config) {

					register_block_pattern(

						sprintf('%s/%s', WS_FORM_NAME, $id),
						$config
					);
				}
			}
		}

		// WP loaded
		public function current_screen() {

			if(WS_Form_Common::is_block_editor()) {

				// Force framework to be ws-form
				add_filter('wsf_option_get', array('WS_Form_Common', 'option_get_framework_ws_form'), 10, 2);
			}
		}

		// Form processing
		public function admin_init() {

			// Get current page
 			$page = WS_Form_Common::get_query_var('page');
			if($page === '') { return true; }

			// Do on specific WS Form pages
			switch($page) {

				// Forms
				case 'ws-form' :

					if(!WS_Form_Common::can_user('read_form')) { break; }

					// Read form ID and action
					$this->form_id = absint(WS_Form_Common::get_query_var_nonce('id', '', false, false, true, 'POST'));
					$action = WS_Form_Common::get_query_var_nonce('action', '', false, false, true, 'POST');
					if($action == '-1') { $action = WS_Form_Common::get_query_var_nonce('action2'); }

					// Process action
					switch($action) {

						case 'wsf-add-blank' : 		self::form_add_blank(); break;
						case 'wsf-add-template' : 	self::form_add_template(WS_Form_Common::get_query_var_nonce('id')); break;
						case 'wsf-add-action' : 	self::form_add_action(WS_Form_Common::get_query_var_nonce('action_id'), WS_Form_Common::get_query_var_nonce('list_id'), WS_Form_Common::get_query_var_nonce('list_sub_id', false)); break;
						case 'wsf-add-hook' : 		self::form_add_hook(WS_Form_Common::get_query_var_nonce('id')); break;
						case 'wsf-clone' : 			self::form_clone($this->form_id); break;
						case 'wsf-delete' : 		self::form_delete($this->form_id); self::redirect('ws-form', false, self::get_filter_query()); break;
						case 'wsf-export' : 		self::form_export($this->form_id); break;
						case 'wsf-restore' : 		self::form_restore($this->form_id); self::redirect('ws-form', false); break;
						case 'wsf-bulk-delete' : 	self::form_bulk('delete'); break;
						case 'wsf-bulk-restore' : 	self::form_bulk('restore'); break;
						case '-1':

							// Check for delete_all
							if(WS_Form_Common::get_query_var_nonce('delete_all') != '') {

								// Empty trash
								if(WS_Form_Common::get_query_var_nonce('delete_all')) { self::form_trash_delete(); }
							}
							break;
					}

					break;

				// Submissions
				case 'ws-form-submit' :

					if(!WS_Form_Common::can_user('read_submission')) { break; }

					// Read form ID, submit ID and action
					$this->form_id = absint(WS_Form_Common::get_query_var('id', 0));
					if(!$this->form_id) { break; }
					$submit_id = absint(WS_Form_Common::get_query_var_nonce('submit_id', ''));
					$action = WS_Form_Common::get_query_var_nonce('action', '');
					if($action == '-1') { $action = WS_Form_Common::get_query_var_nonce('action2'); }

					// Process action
					switch($action) {

						case 'wsf-delete' :

							self::submit_delete($submit_id, true);
							self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
							break;

						case 'wsf-restore' : 			

							self::submit_restore($submit_id, true);
							self::redirect('ws-form-submit', $this->form_id);
							break;

						case 'wsf-bulk-delete' : 		self::submit_bulk('delete'); break;
						case 'wsf-bulk-restore' : 		self::submit_bulk('restore'); break;
						case 'wsf-bulk-export' : 		self::submit_bulk('export'); break;
						case 'wsf-bulk-spam' : 			self::submit_bulk('spam'); break;
						case 'wsf-bulk-not-spam' : 		self::submit_bulk('not-spam'); break;
						case 'wsf-bulk-read' : 			self::submit_bulk('read'); break;
						case 'wsf-bulk-not-read' : 		self::submit_bulk('not-read'); break;
						case 'wsf-bulk-starred' : 		self::submit_bulk('starred'); break;
						case 'wsf-bulk-not-starred' : 	self::submit_bulk('not-starred'); break;
						case '-1':

							// Check for delete_all
							if(WS_Form_Common::get_query_var_nonce('delete_all') != '') {

								// Empty trash
								if(WS_Form_Common::get_query_var_nonce($this->form_id, 'delete_all')) { self::submit_trash_delete(); }
							}
							break;
					}

					// Action
					do_action('wsf_table_submit_action', $action, $submit_id);

					// Process hidden columns
					if($this->form_id == 0) { break; }

					// Read hidden columns for current form
					$form_hidden_columns = get_user_option($this->user_meta_hidden_columns . '-' . $this->form_id);

					if($form_hidden_columns === '') {

						// Create fresh hidden columns array
						$form_hidden_columns = [];

						$ws_form_submit = new WS_Form_Submit;
						$ws_form_submit->form_id = $this->form_id;
						$submit_fields = $ws_form_submit->db_get_submit_fields();

						foreach($submit_fields as $id => $field) {

							$field_hidden = $field['hidden'];
							if($field_hidden) { $form_hidden_columns[] = WS_FORM_FIELD_PREFIX . $id; }
						}

						// Other fields to hide
						$form_hidden_columns[] = 'date_updated';
					}

					// Write hidden columns back to user meta for current form
					update_user_option(get_current_user_id(), $this->user_meta_hidden_columns, $form_hidden_columns, !is_multisite());

					break;
	
				// Styles
				case 'ws-form-style' :

					if(!WS_Form_Common::can_user('read_form_style')) { break; }

					// Read style ID and action
					$style_id = absint(WS_Form_Common::get_query_var_nonce('id', '', false, false, true, 'POST'));
					$action = WS_Form_Common::get_query_var_nonce('action', '', false, false, true, 'POST');
					if($action == '-1') { $action = WS_Form_Common::get_query_var_nonce('action2'); }

					// Process action
					switch($action) {

						case 'wsf-add-template' : 			self::form_style_add_template(WS_Form_Common::get_query_var_nonce('id')); break;
						case 'wsf-clone' :                  self::form_style_clone($style_id); break;
						case 'wsf-delete' :                 self::form_style_delete($style_id); self::redirect('ws-form-style', false, self::get_filter_query()); break;
						case 'wsf-export' :                 self::form_style_export($style_id); break;
						case 'wsf-restore' :                self::form_style_restore($style_id); self::redirect('ws-form-style', false); break;
						case 'wsf-default' :                self::form_style_default($style_id); self::redirect('ws-form-style', false); break;
						case 'wsf-default-conv' :           self::form_style_default_conv($style_id); self::redirect('ws-form-style', false); break;
						case 'wsf-reset' :                  self::form_style_reset($style_id); self::redirect('ws-form-style', false); break;
						case 'wsf-bulk-delete' :            self::form_style_bulk('delete'); break;
						case 'wsf-bulk-restore' :           self::form_style_bulk('restore'); break;
						case '-1':

							// Check for delete_all
							if(WS_Form_Common::get_query_var_nonce('delete_all') != '') {

								// Empty trash
								if(WS_Form_Common::get_query_var_nonce('delete_all')) { self::form_style_trash_delete(); }
							}
							break;
					}

					break;

				// Settings
				case 'ws-form-settings' :

					// Read form ID and action
					$action = WS_Form_Common::get_query_var_nonce('action', '', false, false, true, 'POST');

					switch($action) {

						case 'wsf-settings-update' :

							// Get options
							$options = WS_Form_Config::get_options(false);

							// Get current tab
							$tabCurrent = WS_Form_Common::get_query_var_nonce('tab', 'appearance');
							if($tabCurrent == 'setup') { $tabCurrent = 'appearance'; }				// Backward compatibility

							// File upload checks
							$upload_checks = WS_Form_Common::uploads_check();
							$max_upload_size = $upload_checks['max_upload_size'];
							$max_uploads = $upload_checks['max_uploads'];

							$fields = [];

							// Save current mode
							$mode_old = WS_Form_Common::option_get('mode');

							// Build field list
							if(isset($options[$tabCurrent]['fields'])) {

								$fields = $fields + $options[$tabCurrent]['fields'];
							}
							if(isset($options[$tabCurrent]['groups'])) {

								$groups = $options[$tabCurrent]['groups'];

								foreach($groups as $group) {

									$fields = $fields + $group['fields'];
								}
							}

							// Update fields
							self::settings_update_fields($fields, $max_uploads, $max_upload_size);

							// Update fields if mode has changed
							$mode = WS_Form_Common::option_get('mode');

							if($mode_old != $mode) {

								foreach($options as $tab => $attributes) {

									if(isset($attributes['fields'])) {

										$fields = $attributes['fields'];
										self::setting_mode_change_fields($fields, $mode);
									}

									if(isset($attributes['groups'])) {

										$groups = $attributes['groups'];

										foreach($groups as $group) {

											$fields = $group['fields'];

											self::setting_mode_change_fields($fields, $mode);
										}
									}
								}
							}

							do_action('wsf_settings_update');

							break;
					}

					do_action('wsf_settings');

					break;

				// Add New
				case 'ws-form-add' :

					// Check for form add error
					$form_add_error = WS_Form_Common::option_get('form_add_error');

					if(!empty($form_add_error)) {

						WS_Form_Common::admin_message_push($form_add_error, 'notice-error', false, true);

						WS_Form_Common::option_remove('form_add_error');
					}

					break;

				// Welcome page
				case 'ws-form-welcome' :

					// Disable nag notices
					if(!defined('DISABLE_NAG_NOTICES')) {

						define('DISABLE_NAG_NOTICES', true);
					}

					break;
			}

			// Do on every WS Form page
			if(strpos($page, $this->plugin_name) !== false) {

				// Except welcome and settings
				if(
					(strpos($page, $this->plugin_name . '-welcome') === false) &&
					(strpos($page, $this->plugin_name . '-settings') === false) &&
					(strpos($page, $this->plugin_name . '-upgrade') === false) &&
					(strpos($page, $this->plugin_name . '-add-ons') === false)
				) {

					// Check if set-up needs to be run
					$setup = WS_Form_Common::option_get('setup');
					if(
						empty($setup) &&
						(WS_Form_Common::get_query_var('skip_welcome') == '')
					) {
						wp_redirect(WS_Form_Common::get_admin_url('ws-form-welcome'));
					}
				}
			}

			// Run nags
			do_action('wsf_nag');
		}

		// Get filter query
		public function get_filter_query() {

			$submit_filter_query_array = array();
			$submit_filter_query_lookups = array('date_from', 'date_to', 'paged', 'ws-form-status');

			foreach($submit_filter_query_lookups as $submit_filter_query_lookup) {

				if(WS_Form_Common::get_query_var($submit_filter_query_lookup) != '') { $submit_filter_query_array[] = $submit_filter_query_lookup . '=' . WS_Form_Common::get_query_var($submit_filter_query_lookup); }
			}

			$submit_filter_query_array[] = WS_FORM_POST_NONCE_FIELD_NAME . '=' . wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME);

			return implode('&', $submit_filter_query_array);
		}

		// Form - Create
		public function form_add_blank() {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_create();

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Create from template
		public function form_add_template($id) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_create_from_template($id);

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Create from action
		public function form_add_action($action_id, $list_id, $list_sub_id = false) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_create_from_action($action_id, $list_id, $list_sub_id);

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Create from hook
		public function form_add_hook($template_id) {

			// Get templates
			$ws_form_template = new WS_Form_Template;
			$ws_form_template->id = $template_id;
			$hook = $ws_form_template->get_hook();

			// Check hook
			if($hook === false) {

				// Error
				self::redirect('ws-form-add');
			}

			// Create form from hook
			$ws_form_form = new WS_Form_Form();
			if(
				$ws_form_form->db_create_from_hook($hook) &&
				($ws_form_form->id > 0)
			) {

				// Success
				self::redirect('ws-form-edit', $ws_form_form->id);

			} else {

				// Error
				self::redirect('ws-form-add');
			}
		}

		// Form - Clone
		public function form_clone($id) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_clone();

			if($ws_form_form->id > 0) { self::redirect('ws-form', false, self::get_filter_query()); }
		}

		// Form - Delete
		public function form_delete($id) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_delete();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Export
		public function form_export($id) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_download_json();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Restore
		public function form_restore($id) {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_restore();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Bulk
		public function form_bulk($method = '') {

			$ids = WS_Form_Common::get_query_var_nonce('bulk-ids');

			if(!$ids || (count($ids) == 0)) { return false; }

			switch($method) {

				case 'delete' :

					foreach ($ids as $id) { self::form_delete($id); }
					self::redirect('ws-form', false, self::get_filter_query());
					break;

				case 'restore' :

					foreach ($ids as $id) { self::form_restore($id); }
					self::redirect('ws-form', false);
					break;
			}
		}

		// Form - Empty trash
		public function form_trash_delete() {

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_trash_delete();

			// Redirect
			self::redirect('ws-form', false, self::get_filter_query());
		}

		// Submit - Delete
		public function submit_delete($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_delete(false, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Restore
		public function submit_restore($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_restore($update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Spam
		public function submit_spam($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_status('spam', $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Not Spam
		public function submit_not_spam($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_status('not_spam', $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Read
		public function submit_read($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_viewed(true, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Unread
		public function submit_not_read($id, $update_count_submit_unread) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_viewed(false, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Starred
		public function submit_starred($id) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->db_set_starred(true);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Not Starred
		public function submit_not_starred($id) {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->db_set_starred(false);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Bulk
		public function submit_bulk($method = '') {

			$ids = WS_Form_Common::get_query_var_nonce('bulk-ids');

			if(!$ids || (count($ids) == 0)) { return false; }

			switch($method) {

				case 'delete' :

					foreach ($ids as $id) { self::submit_delete($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'restore' :

					foreach ($ids as $id) { self::submit_restore($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id);

					break;

				case 'spam' :

					foreach ($ids as $id) { self::submit_spam($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'not-spam' :

					foreach ($ids as $id) { self::submit_not_spam($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'read' :

					foreach ($ids as $id) { self::submit_read($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'not-read' :

					foreach ($ids as $id) { self::submit_not_read($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'starred' :

					foreach ($ids as $id) { self::submit_starred($id); }
					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
					break;

				case 'not-starred' :

					foreach ($ids as $id) { self::submit_not_starred($id); }
					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
					break;
			}
		}

		// Submit - Update statistics
		public function update_count_submit_unread() {

			// Update form submit unread count statistic
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $this->form_id;
			$ws_form_form->db_update_count_submit_unread();
		}

		// Submit - Empty trash
		public function submit_trash_delete() {

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_trash_delete();

			// Redirect
			self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
		}

		// Style - Create from template
		public function form_style_add_template($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->db_create_from_template($id);

			if($ws_form_style->id > 0) {

				// Redirect
				self::redirect('ws-form-style', $ws_form_form->id);
			}
		}

		// Style - Clone
		public function form_style_clone($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_clone();

			if($ws_form_style->id > 0) { self::redirect('ws-form-style', false, self::get_filter_query()); }
		}

		// Style - Delete
		public function form_style_delete($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_delete();

			// No redirect here in case it is called by bulk loop
		}

		// Style - Export
		public function form_style_export($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_download_json();

			// No redirect here in case it is called by bulk loop
		}

		// Style - Restore
		public function form_style_restore($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_restore();

			// No redirect here in case it is called by bulk loop
		}

		// Style - Default
		public function form_style_default($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_default();

			if($ws_form_style->id > 0) { self::redirect('ws-form-style', false, self::get_filter_query()); }
		}

		// Style - Default - Conversational
		public function form_style_default_conv($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_default_conv();

			if($ws_form_style->id > 0) { self::redirect('ws-form-style', false, self::get_filter_query()); }
		}

		// Style - Reset
		public function form_style_reset($id) {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->id = $id;
			$ws_form_style->db_reset();

			// No redirect here in case it is called by bulk loop
		}

		// Style - Bulk
		public function form_style_bulk($method = '') {

			$ids = WS_Form_Common::get_query_var_nonce('bulk-ids');

			if(!$ids || (count($ids) == 0)) { return false; }

			switch($method) {

				case 'delete' :

					foreach ($ids as $id) { self::form_style_delete($id); }
					self::redirect('ws-form-style', false, self::get_filter_query());
					break;

				case 'restore' :

					foreach ($ids as $id) { self::form_style_estore($id); }
					self::redirect('ws-form-style', false);
					break;
			}
		}

		// Style - Empty trash
		public function form_style_trash_delete() {

			$ws_form_style = new WS_Form_Style();
			$ws_form_style->db_trash_delete();

			// Redirect
			self::redirect('ws-form-style', false, self::get_filter_query());
		}

		public function settings_update_fields($fields, $max_uploads, $max_upload_size) {

			// Update
			foreach(array_reverse($fields) as $field => $attributes) {

				// Check for false (Hidden setting)
				if($attributes === false) { continue; }

				// Hidden values
				if($attributes['type'] === 'hidden') { continue; }

				// Fields not to save
				if(isset($attributes['save']) && !$attributes['save']) { continue; }

				// Condition
				if(isset($attributes['condition'])) {

					$condition_result = true;
					foreach($attributes['condition'] as $condition_field => $condition_value) {

						$condition_value_check = WS_Form_Common::option_get($condition_field);
						if($condition_value_check != $condition_value) {

							$condition_result = false;
							break;
						}
					}
					if(!$condition_result) { continue; }
				}

				$value = WS_Form_Common::get_query_var_nonce($field);

				// Process fields
				switch($field) {


					default :

						do_action('wsf_settings_update_fields', $field, $value);
				}

				// Write by type
				switch($attributes['type']) {

					case 'hidden' : break;				

					case 'static' : break;				

					case 'number' : 

						// Round numbers
						$value = floatval($value);
						if(isset($attributes['absint'])) { $value = absint($value); }

						// Minimum
						if(isset($attributes['minimum'])) {

							if($value < $attributes['minimum']) { $value = $attributes['minimum']; }
						}

						// Maximum
						if(isset($attributes['maximum'])) {

							$maximum = $attributes['maximum'];

							switch($maximum) {

								case '#max_upload_size' : $maximum = $max_upload_size; break;
								case '#max_uploads' : $maximum = $max_uploads; break;
							}

							if($value > $maximum) { $value = $maximum; }
						}

						WS_Form_Common::option_set($field, $value);

						break;

					case 'checkbox' :

						$value = ($value === '1');

						WS_Form_Common::option_set($field, $value);

						break;

					default :

						// Check for license related field
						if(strpos($field, 'license_key') !== false) {

							// Build license constant (e.g. WSF_LICENSE_KEY)
							$license_constant = sprintf('WSF_%s', strtoupper($field));

							// If defined, skip setting this option
							if(defined($license_constant)) {

								break;
							}
						}

						WS_Form_Common::option_set($field, $value);
				}
			}

			// Add admin message
			if(WS_Form_Common::get_admin_message_count() == 0) {

				WS_Form_Common::admin_message_push('Successfully saved settings!');
			}
		}

		public function setting_mode_change_fields($fields, $mode) {

			// Update
			foreach($fields as $field => $attributes) {

				// Set according to mode
				if(
					(isset($attributes['type']) && ($attributes['type'] != 'static')) &&
					(isset($attributes['mode']) && isset($attributes['mode'][$mode]))
				) {

					$value = $attributes['mode'][$mode];
					WS_Form_Common::option_set($field, $value);
				}
			}
		}

		// Redirect
		public function redirect($page_slug = 'ws-form', $item_id = false, $path_extra = '') {

			wp_redirect(WS_Form_Common::get_admin_url($page_slug, $item_id, $path_extra));
			exit;
		}

		// Settings links
		public function plugin_action_links($links) {

			// Upgrade to PRO
			array_unshift($links, sprintf('<a href="%s">%s</a>', esc_url(WS_Form_Common::get_admin_url('ws-form-upgrade')), __('Upgrade to PRO', 'ws-form')));
			// Settings
			array_unshift($links, sprintf('<a href="%s">%s</a>', esc_url(WS_Form_Common::get_admin_url('ws-form-settings')), __('Settings', 'ws-form')));

			return $links;
		}

		// Dashboard glance items
		public function dashboard_glance_items( $items = array() ) {

			if(!WS_Form_Common::can_user('read_form')) { return $items; }

			// Get form count
			$ws_form_form = new WS_Form_Form;
			$form_count = $ws_form_form->db_get_count_by_status();

			// Build text
			$text = sprintf(

				/* translators: %u = Number of forms */
				_n('%u Form', '%u Forms', $form_count, 'ws-form'),
				$form_count
			);

			// Add item
			if(WS_Form_Common::can_user('read_form')) {

				$url = esc_url(WS_Form_Common::get_admin_url('ws-form'));
				$items[] = sprintf('<a class="wsf-dashboard-glance-count" href="%s">%s</a>', esc_url($url), esc_html($text)) . "\n";

			} else {

				$items[] = sprintf('<span class="wsf-dashboard-glance-count">%s/span>', esc_html($text)) . "\n";
			}

			return $items;
		}

		// Theme switch, so reset preview template
		public function switch_theme() {

			WS_Form_Common::option_set('preview_template', '');			
		}


		public function admin_bar_menu($wp_admin_bar) {

			// + New item
			if(WS_Form_Common::can_user('create_form')) {

				$wp_admin_bar->add_node(

					array(

						'id'     => WS_FORM_NAME . '-new-form',
						'parent' => 'new-content',
						'title'  => WS_FORM_NAME_GENERIC,
						'href'   => esc_url(WS_Form_Common::get_admin_url('ws-form-add'))
					)
				);
			}

			// Check if toolbar should be rendered
			if(
				!WS_Form_Common::toolbar_enabled() ||
				!(
					WS_Form_Common::can_user('create_form') ||
					WS_Form_Common::can_user('read_form') ||
					WS_Form_Common::can_user('manage_options_wsform')
				)
			) {
				return;
			}

			// Build menu
			$wp_admin_bar->add_node(

				array(
	
					'id'    => WS_FORM_NAME . '-node',
					'title' => sprintf('<span class="ab-icon">%s</span><span class="ab-label">WS Form</span>', WS_Form_Common::get_admin_icon('#a0a5aa', false)),
					'href'  => esc_url(WS_Form_Common::get_admin_url('ws-form'))
				)
			);

			// Get recent forms
			if(WS_Form_Common::can_user('read_form')) {

				$ws_form_form = new WS_Form_Form();
				$forms = $ws_form_form->db_read_recent();
				if(empty($forms)) { $forms = array(); }

				foreach($forms as $form) {

					$form_id = $form['id'];

					// Add form to menu
					$wp_admin_bar->add_node(

						array(

							'id'     => WS_FORM_NAME . '-node-' . $form_id,
							'parent' => WS_FORM_NAME . '-node',
							'title'  => esc_attr($form['label']),
							'href'   => WS_Form_Common::can_user('edit_form') ? esc_url(WS_Form_Common::get_admin_url('ws-form-edit', $form_id)) : ''
						)
					);

					// Edit
					if(WS_Form_Common::can_user('edit_form')) {

						$wp_admin_bar->add_node(

							array(

								'id'     => WS_FORM_NAME . '-node-' . $form_id . '-edit',
								'parent' => WS_FORM_NAME . '-node-' . $form_id,
								'title'  => esc_attr__('Edit', 'ws-form'),
								'href'   => esc_url(WS_Form_Common::get_admin_url('ws-form-edit', $form_id))
							)
						);
					}

					// Submissions
					if(WS_Form_Common::can_user('read_submission')) {

						$wp_admin_bar->add_node(

							array(

								'id'     => WS_FORM_NAME . '-node-' . $form_id . '-submit',
								'parent' => WS_FORM_NAME . '-node-' . $form_id,
								'title'  => esc_attr__('Submissions', 'ws-form'),
								'href'   => esc_url(WS_Form_Common::get_admin_url('ws-form-submit', $form_id))
							)
						);
					}

					// Preview
					if(WS_Form_Common::can_user('edit_form')) {

						$wp_admin_bar->add_node(

							array(

								'id'     => WS_FORM_NAME . '-node-' . $form_id . '-preview',
								'parent' => WS_FORM_NAME . '-node-' . $form_id,
								'title'  => esc_attr__('Preview', 'ws-form'),
								'href'   => esc_url(WS_Form_Common::get_preview_url($form_id)),
								'meta'   => array(

									'target' => '_blank'
								)
							)
						);
					}
				}

				// All forms
				if(count($forms) > 0) {

					$wp_admin_bar->add_node(

						array(

							'id'     => WS_FORM_NAME . '-forms',
							'parent' => WS_FORM_NAME . '-node',
							'title'  => esc_attr__('Forms', 'ws-form'),
							'href'   => esc_url(WS_Form_Common::get_admin_url('ws-form'))
						)
					);
				}
			}

			// Add form
			if(WS_Form_Common::can_user('create_form')) {

				$wp_admin_bar->add_node(

					array(

						'id'     => WS_FORM_NAME . '-add-form',
						'parent' => WS_FORM_NAME . '-node',
						'title'  => esc_attr__('Add Form', 'ws-form'),
						'href'   => esc_url(WS_Form_Common::get_admin_url('ws-form-add'))
					)
				);
			}

		}

		// Admin page - Welcome
		public function admin_page_welcome() {

			include_once 'partials/ws-form-welcome.php';
		}

		// Admin page - Form
		public function admin_page_form() {

			include_once 'partials/ws-form-form.php';
		}

		// Admin page - Form - Add
		public function admin_page_form_add() {

			include_once 'partials/ws-form-form-add.php';
		}

		// Admin page - Form - Edit
		public function admin_page_form_edit() {

			include_once 'partials/ws-form-form-edit.php';
		}

		// Admin page - Form - Submissions
		public function admin_page_form_submit() {

			include_once 'partials/ws-form-form-submit.php';
		}


		// Admin page - Form - Delete
		public function admin_page_form_delete() {

			include_once 'partials/ws-form-form-delete.php';
		}

		// Admin page - Style
		public function admin_page_form_style() {

			include_once 'partials/ws-form-form-style.php';
		}

		// Admin page - Style - Add
		public function admin_page_form_style_add() {

			include_once 'partials/ws-form-form-style-add.php';
		}

		// Admin page - Settings
		public function admin_page_settings() {

			include_once 'partials/ws-form-settings.php';
		}

		// Admin page - Upgrade to PRO
		public function admin_page_upgrade() {

			include_once 'partials/ws-form-upgrade.php';
		}

		// Admin page - Add-Ons
		public function admin_page_add_ons() {

			include_once 'partials/ws-form-add-ons.php';
		}
	}
