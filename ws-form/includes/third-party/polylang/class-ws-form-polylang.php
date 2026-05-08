<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

		/**
		 * Polylang string translation integration.
		 *
		 * Registers strings so they appear under Languages → String translations and resolves them on output with
		 * {@see pll__()}. Per Polylang, {@see pll_register_string()} must run on admin requests (not the frontend).
		 * On each full admin load we replay a cached list of strings (built when the form is saved) so we do not reload every form from the database.
		 *
		 * Translation tab form meta keys share the prefix `translate_polylang_` (except the main toggle):
		 * translate_polylang, translate_polylang_data_grid, translate_polylang_button_1.
		 *
		 * @link https://polylang.pro/documentation/support/developers/function-reference/#pll_register_string
		 * @link https://polylang.pro/documentation/support/developers/function-reference/#pll__
		 */

	class WS_Form_Polylang {

		/** Option key (via {@see WS_Form_Common::option_get()}). @var string */
		private static $manifest_option_key = 'translate_manifests';

		/** Manifest bucket key for this integration. @var string */
		private static $manifest_plugin_id = 'polylang';

		/**
		 * While translation registration runs for a Polylang-enabled form, collect arguments for {@see pll_register_string()}.
		 *
		 * @var bool
		 */
		private static $manifest_collecting = false;

		/** @var int */
		private static $manifest_form_id = 0;

		/**
		 * @var list<array{name: string, string: string, group: string, multiline: bool}>
		 */
		private static $manifest_buffer = array();

		public function __construct() {

			// Polylang: replay cached pll_register_string() args on admin (non-AJAX); see admin_register_all_polylang_strings().
			add_action('admin_init', array($this, 'admin_register_all_polylang_strings'), 20);

			// Register filters
			add_filter('wsf_translate_plugins', array($this, 'plugins'), 10, 1);
			add_filter('wsf_translate', array($this, 'translate'), 10, 4);
			add_filter('wsf_translate_fieldsets', array($this, 'translation_tab_fieldsets'), 10, 1);

			// Register action hooks
			add_action('wsf_translate_start', array($this, 'start'), 10, 2);
			add_action('wsf_translate_register', array($this, 'register'), 10, 6);
			add_action('wsf_translate_finish', array($this, 'finish'), 10, 2);
			add_action('wsf_translate_unregister', array($this, 'translate_unregister'), 10, 2);

			// Form save/delete: maintain Polylang string manifest
			add_action('wsf_form_update', array($this, 'form_update'), 10, 1);
			add_action('wsf_form_delete', array($this, 'form_delete'), 10, 1);

			// Admin: Translation tab meta.
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			add_filter('wsf_translate_data_grids', array($this, 'filter_translate_data_grids'), 10, 2);
		}

		/**
		 * Field data grids: opt in when Polylang translation and Translate Data Grid are enabled for this form.
		 *
		 * @param bool   $translate_data_grids Previous value from other callbacks.
		 * @param object $form_object          Current form object (may lack meta).
		 * @return bool
		 */
		public function filter_translate_data_grids($translate_data_grids, $form_object) {

			if(
				is_object($form_object) &&
				isset($form_object->meta) &&
				WS_Form_Translate::is_plugin_translation_enabled($form_object, 'polylang') &&
				WS_Form_Translate::is_plugin_translate_data_grid_enabled($form_object, 'polylang')
			) {

				return true;
			}

			return $translate_data_grids;
		}

		public function plugins($plugins) {

			if(!is_array($plugins)) {

				$plugins = array();
			}

			$plugins[] = array(

				'id' => 'polylang',
				'label' => __('Polylang', 'ws-form'),
			);

			return $plugins;
		}

		/**
		 * Form Settings meta: Polylang on the Translation tab (enable, data grid, admin link).
		 *
		 * @param array<string, mixed> $meta_keys Meta keys.
		 * @param int                  $form_id   Form ID.
		 * @return array<string, mixed>
		 */
		public function config_meta_keys($meta_keys, $form_id = 0) {

			if(!is_array($meta_keys)) {

				$meta_keys = array();
			}

			$meta_keys['translate_polylang'] = array(

				'label'			=> __('Enable', 'ws-form'),
				'type'			=> 'checkbox',
				'default'		=> '',
				'help'			=>	sprintf(

					'%s <a href="%s" target="_blank">%s</a>',
					__('When enabled for Polylang, translatable text is registered for string translation on each admin load. The list of strings is refreshed when you save the form.', 'ws-form'),
					WS_Form_Common::get_plugin_website_url('/knowledgebase/translate-forms-with-polylang/'),
					__('Learn more', 'ws-form')
				),
				'data_change'				=>	array('event' => 'change', 'action' => 'save'),
			);

			$meta_keys['translate_polylang_data_grid'] = array(

				'label'			=> __('Translate Data Grids', 'ws-form'),
				'type'			=> 'checkbox',
				'default'		=> 'on',
				'help'			=>	__('When enabled, choice labels in select, checkbox, radio, and price variant fields are registered for Polylang. Fields with Data Source enabled are excluded.', 'ws-form'),
				'condition'		=> array(

					array(

						'logic'			=> '==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'		=> 'translate_polylang',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'	=> 'on',
					),
				),
			);

			$meta_keys['translate_polylang_button_1'] = array(

				'label'			=> __('Manage in Polylang', 'ws-form'),
				'type'			=> 'button_url',
				'url'			=> admin_url('admin.php?page=mlang_strings'),
				'target'		=> '_blank',
				'class_field'	=> array('wsf-button-primary'),
				'condition'		=> array(

					array(

						'logic'			=> '==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'		=> 'translate_polylang',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'	=> 'on',
					),
				),
			);

			return $meta_keys;
		}

		/**
		 * Translation tab: Polylang fieldset.
		 *
		 * @param array<int, array{label: string, meta_keys: array<int, string>}> $fieldsets Fieldsets.
		 * @return array<int, array{label: string, meta_keys: array<int, string>}>
		 */
		public function translation_tab_fieldsets($fieldsets) {

			if(!is_array($fieldsets)) {

				$fieldsets = array();
			}

			$fieldsets[] = array(

				'label'			=> __('Polylang', 'ws-form'),
				'meta_keys'		=> array(
					'translate_polylang',
					'translate_polylang_data_grid',
					'translate_polylang_button_1',
				),
			);

			return $fieldsets;
		}

		/**
		 * Resolve string for the request language.
		 *
		 * {@see pll__()} depends on Polylang’s current language. On REST submit (and some non-frontend requests),
		 * {@see pll_current_language()} is often unset, so we use {@see pll_translate_string()} with an explicit slug
		 * (current language, {@code lang} POST, {@code pll_language} cookie, or {@see 'wsf_polylang_request_language'}).
		 *
		 * @param string          $string_value Original string (same value passed to registration).
		 * @param string          $string_name  Stable WS Form string identifier.
		 * @param int|string      $form_id      Form ID.
		 * @param string          $form_label   Form label.
		 * @return string
		 */
		public function translate($string_value, $string_name, $form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'polylang')
			) {

				return $string_value;
			}

			if(!function_exists('pll__')) {

				return $string_value;
			}

			$lang = '';
			if(function_exists('pll_current_language')) {

				$cur = pll_current_language('slug');
				if(is_string($cur) && $cur !== '') {

					$lang = $cur;
				}
			}

			if(
				($lang === '') &&
				function_exists('pll_translate_string')
			) {

				$lang = $this->get_polylang_language_slug_for_request(absint($form_id));
			}

			if(
				($lang !== '') &&
				function_exists('pll_translate_string')
			) {

				return pll_translate_string($string_value, $lang);
			}

			return pll__($string_value);
		}

		/**
		 * Language slug for {@see pll_translate_string()} when {@see pll_current_language()} is empty (typical on REST).
		 *
		 * @param int $form_id Form ID (for {@see 'wsf_polylang_request_language'}).
		 * @return string
		 */
		private function get_polylang_language_slug_for_request($form_id = 0) {

			$lang_raw = WS_Form_Common::get_query_var('lang', '');
			if(is_string($lang_raw) && $lang_raw !== '') {

				$candidate = sanitize_key($lang_raw);
				if($this->is_polylang_registered_language_slug($candidate)) {

					return $candidate;
				}
			}

			$pll_cookie = WS_Form_Common::cookie_get_raw('pll_language', '');
			if($pll_cookie !== '') {

				$candidate = sanitize_key($pll_cookie);
				if($this->is_polylang_registered_language_slug($candidate)) {

					return $candidate;
				}
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WS Form prefixed filter
			$extra = apply_filters('wsf_polylang_request_language', '', $form_id);
			if(is_string($extra) && $extra !== '') {

				$candidate = sanitize_key($extra);
				if($this->is_polylang_registered_language_slug($candidate)) {

					return $candidate;
				}
			}

			return '';
		}

		/**
		 * @param string $slug Language slug.
		 * @return bool
		 */
		private function is_polylang_registered_language_slug($slug) {

			if(
				!is_string($slug) ||
				($slug === '') ||
				!function_exists('PLL')
			) {

				return false;
			}

			$pll = PLL();
			if(
				!is_object($pll) ||
				!isset($pll->model) ||
				!is_object($pll->model)
			) {

				return false;
			}

			$lang = $pll->model->get_language($slug);

			return !empty($lang);
		}

		/**
		 * Register one string with Polylang (admin only; {@see pll_register_string()} is a no-op when Polylang is not in admin context).
		 *
		 * @param string $string_value String content (default language).
		 * @param string $string_name  Unique name / key.
		 * @param string $string_title Human title (unused here).
		 * @param string $type          WS Form meta type (e.g. text, textarea).
		 * @param int    $form_id       Form ID.
		 * @param string $form_label    Form label.
		 * @return void
		 */
		public function register($string_value, $string_name, $string_title, $type, $form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'polylang')
			) {

				return;
			}

			$form_object_settings = WS_Form_Translate::get_registering_form_object();
			if(!is_object($form_object_settings)) {

				$form_object_settings = $form_object;
			}

			if(
				WS_Form_Translate::translate_register_is_data_grid_context() &&
				!WS_Form_Translate::is_plugin_translate_data_grid_enabled($form_object_settings, 'polylang')
			) {

				return;
			}

			$group = $this->get_strings_group($form_label, $form_id);
			$multiline = $this->is_multiline_string_type($type);

			if(
				self::$manifest_collecting &&
				(absint($form_id) === self::$manifest_form_id)
			) {

				self::$manifest_buffer[] = array(

					'name'			=> $string_name,
					'string'		=> $string_value,
					'group'			=> $group,
					'multiline'		=> $multiline,
				);
			}

			pll_register_string($string_name, $string_value, $group, $multiline);
		}

		/**
		 * Begin manifest collection for one form registration cycle.
		 *
		 * @param int|string $form_id    Form ID.
		 * @param string     $form_label Form label.
		 * @return void
		 */
		public function start($form_id, $form_label) {

			self::$manifest_collecting = false;
			self::$manifest_form_id = 0;
			self::$manifest_buffer = array();

			$form_id = absint($form_id);
			if($form_id < 1) {

				return;
			}

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);
			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'polylang')
			) {

				return;
			}

			self::$manifest_collecting = true;
			self::$manifest_form_id = $form_id;
			self::$manifest_buffer = array();
		}

		/**
		 * Persist or clear the Polylang string manifest for this form.
		 *
		 * @param int|string $form_id    Form ID.
		 * @param string     $form_label Form label.
		 * @return void
		 */
		public function finish($form_id, $form_label) {

			$form_id = absint($form_id);
			if(
				self::$manifest_collecting &&
				($form_id === self::$manifest_form_id) &&
				($form_id > 0)
			) {

				$all = WS_Form_Common::option_get(self::$manifest_option_key, array());
				if(!is_array($all)) {

					$all = array();
				}

				if(
					!isset($all[self::$manifest_plugin_id]) ||
					!is_array($all[self::$manifest_plugin_id])
				) {

					$all[self::$manifest_plugin_id] = array();
				}

				$all[self::$manifest_plugin_id][ (string) $form_id ] = self::$manifest_buffer;
				WS_Form_Common::option_set(self::$manifest_option_key, $all);
			}

			self::$manifest_collecting = false;
			self::$manifest_form_id = 0;
			self::$manifest_buffer = array();
		}

		/**
		 * Keep manifest in sync when form settings change and Polylang gets disabled.
		 *
		 * @param int|string $form_id Form ID.
		 * @return void
		 */
		public function form_update($form_id) {

			$form_id = absint($form_id);
			if($form_id < 1) {

				return;
			}

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);
			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'polylang')
			) {

				$this->remove_manifest_for_form($form_id);
			}
		}

		/**
		 * @param int|string $form_id Form ID.
		 * @return void
		 */
		public function form_delete($form_id) {

			$this->remove_manifest_for_form($form_id);
		}

		/**
		 * @param int    $form_id Form ID.
		 * @param string $context Optional. `delete` or `disable` — same cleanup either way.
		 * @return void
		 */
		public function translate_unregister($form_id, $context = 'disable') {

			$this->remove_manifest_for_form($form_id);
		}

		/**
		 * @param int|string $form_id Form ID.
		 * @return void
		 */
		private function remove_manifest_for_form($form_id) {

			$form_id = absint($form_id);
			if($form_id < 1) {

				return;
			}

			$all = WS_Form_Common::option_get(self::$manifest_option_key, array());
			if(!is_array($all)) {

				return;
			}

			if(
				!isset($all[self::$manifest_plugin_id]) ||
				!is_array($all[self::$manifest_plugin_id])
			) {

				return;
			}

			$key = (string) $form_id;
			if(!isset($all[self::$manifest_plugin_id][$key])) {

				return;
			}

			unset($all[self::$manifest_plugin_id][$key]);

			if($all[self::$manifest_plugin_id] === array()) {

				unset($all[self::$manifest_plugin_id]);
			}

			WS_Form_Common::option_set(self::$manifest_option_key, $all);
		}

		/**
		 * Polylang expects {@see pll_register_string()} on admin-side requests.
		 * Replays the cached manifest on every admin load (non-AJAX) so Polylang (including Pro)
		 * always sees registered strings, not only on Languages → String translations.
		 *
		 * @return void
		 */
		public function admin_register_all_polylang_strings() {

			if(
				!function_exists('pll_register_string') ||
				!is_admin() ||
				wp_doing_ajax()
			) {

				return;
			}
			if(function_exists('PLL')) {

				$pll = PLL();

				if(
					!is_object($pll) ||
					!is_a($pll, 'PLL_Admin_Base', true)
				) {

					return;
				}
			}

			$manifests = WS_Form_Common::option_get(self::$manifest_option_key, array());
			if(!is_array($manifests)) {

				$manifests = array();
			}

			if(
				!isset($manifests[self::$manifest_plugin_id]) ||
				!is_array($manifests[self::$manifest_plugin_id]) ||
				($manifests[self::$manifest_plugin_id] === array())
			) {

				return;
			}

			foreach($manifests[self::$manifest_plugin_id] as $rows) {

				if(!is_array($rows) || ($rows === array())) {

					continue;
				}

				foreach($rows as $row) {

					if(
						!is_array($row) ||
						!isset($row['name'], $row['string'], $row['group'], $row['multiline'])
					) {

						continue;
					}

					pll_register_string($row['name'], $row['string'], $row['group'], (bool) $row['multiline']);
				}
			}
		}

		/**
		 * Group label in Polylang string list (one group per form).
		 *
		 * @param string          $form_label Form label.
		 * @param int|string|false $form_id    Form ID.
		 * @return string
		 */
		private function get_strings_group($form_label = '', $form_id = 0) {

			$form_id = absint($form_id);
			$label_trimmed = is_string($form_label) ? trim($form_label) : '';

			if($label_trimmed === '') {

				$label_trimmed = __('(Untitled)', 'ws-form');
			}

			if($form_id > 0) {

				return sprintf(

					'%s — %s (ID: %d)',
					WS_FORM_NAME_GENERIC,
					$label_trimmed,
					$form_id
				);
			}

			return WS_FORM_NAME_GENERIC;
		}

		/**
		 * Map WS Form meta types to Polylang multiline string editor.
		 *
		 * @param string $type WS Form meta type.
		 * @return bool
		 */
		private function is_multiline_string_type($type) {

			switch($type) {

				case 'textarea':
				case 'text_editor':
				case 'html_editor':
					return true;

				default:
					return false;
			}
		}
	}

	new WS_Form_Polylang();
