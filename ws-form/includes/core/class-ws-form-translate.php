<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Translate {

		public $form_object = false;

		/**
		 * Full meta key config for one form operation (condition evaluation on control keys). Reset per register/translate run.
		 *
		 * @var array<string, array>|null
		 */
		private $meta_keys_config_for_conditions = null;

		/**
		 * True only while {@see register_field_data_grid_strings()} is registering label cells (for per-plugin data grid settings).
		 *
		 * @var bool
		 */
		private static $translate_register_is_data_grid = false;

		/**
		 * During {@see form_register()} only: the form object being registered (saved / editor state), not necessarily the published snapshot.
		 * Used by integrations when deciding Form Settings meta (e.g. Translate Data Grids) while registering strings.
		 *
		 * @var object|null
		 */
		private static $registering_form_object = null;

		public function __construct() {

			// After any form save: {@see 'wsf_form_update'} passes form ID; full form is loaded from DB here.
			add_action('wsf_form_update', array($this, 'form_update'), 10, 1);
			add_action('wsf_form_delete', array($this, 'form_delete'), 10, 1);

			// Translation tab in Form Settings (only when a plugin registers via wsf_translate_plugins)
			add_filter('wsf_config_settings_form_admin', array($this, 'config_settings_form_admin'), 10, 1);

			// Translate form when form parsed
			add_filter('wsf_form_translate', array($this, 'form_translate'), 10, 1);

			// Action meta (Show Message, email, …): only via {@see WS_Form_Action::actions_post()} filters, not during {@see 'wsf_form_translate'}.
			add_filter('wsf_actions_post_submit', array($this, 'translate_actions_post_configs'), 10, 3);
			add_filter('wsf_actions_post_save', array($this, 'translate_actions_post_configs'), 10, 3);
			add_filter('wsf_actions_post_action', array($this, 'translate_actions_post_configs'), 10, 3);
		}

		/**
		 * Translation integrations registered via {@see 'wsf_translate_plugins'}.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		private static function get_translation_plugins_list() {

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$plugins = apply_filters('wsf_translate_plugins', array());

			return is_array($plugins) ? $plugins : array();
		}

		/**
		 * @return array<int, array<string, mixed>>
		 */
		private function get_translation_plugins() {

			return self::get_translation_plugins_list();
		}

		/**
		 * Add Form Settings sidebar tab for translation when at least one translation integration is registered.
		 *
		 * Fieldsets use {@see 'wsf_translate_fieldsets'}. Translation plugins register
		 * meta via {@see 'wsf_config_meta_keys'}.
		 *
		 * @param array $settings_form_admin Admin settings config.
		 * @return array
		 */
		public function config_settings_form_admin($settings_form_admin) {

			$plugins = $this->get_translation_plugins();

			if($plugins === array()) {

				return $settings_form_admin;
			}

			if(
				!isset($settings_form_admin['sidebars']['form']['meta']['fieldsets']) ||
				!is_array($settings_form_admin['sidebars']['form']['meta']['fieldsets'])
			) {

				return $settings_form_admin;
			}

			$translation_tab = array(

				'label'		=> __('Translation', 'ws-form'),

				'meta_keys'		=> array(),

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				'fieldsets'		=> apply_filters('wsf_translate_fieldsets', array()),
			);

			$fieldsets = $settings_form_admin['sidebars']['form']['meta']['fieldsets'];
			$new_fieldsets = array();
			$translation_tab_inserted = false;

			foreach($fieldsets as $tab_key => $tab_config) {

				$new_fieldsets[$tab_key] = $tab_config;

				// After Data tab (key: action); otherwise appended at end below
				if($tab_key === 'action') {

					$new_fieldsets['translation'] = $translation_tab;
					$translation_tab_inserted = true;
				}
			}

			if(!$translation_tab_inserted) {

				$new_fieldsets['translation'] = $translation_tab;
			}

			$settings_form_admin['sidebars']['form']['meta']['fieldsets'] = $new_fieldsets;

			return $settings_form_admin;
		}

		/**
		 * Form meta as object (API / DB may supply array).
		 *
		 * @param object $form_object Form object.
		 * @return object|null
		 */
		private static function get_form_meta_as_object($form_object) {

			if(!is_object($form_object) || !isset($form_object->meta)) {

				return null;
			}

			$meta = $form_object->meta;

			if(is_array($meta)) {

				return (object) $meta;
			}

			if(is_object($meta)) {

				return $meta;
			}

			return null;
		}

		/**
		 * Whether a named integration (wsf_translate_plugins `id`) is enabled for this form.
		 *
		 * @param object $form_object Form object.
		 * @param string $plugin_id   Integration id (e.g. wpml, polylang, weglot), matching {@see 'wsf_translate_plugins'} `id`.
		 * @return bool
		 */
		public static function is_plugin_translation_enabled($form_object, $plugin_id) {

			$meta = self::get_form_meta_as_object($form_object);
			if($meta === null) {

				return false;
			}

			$key = 'translate_' . $plugin_id;

			if(property_exists($meta, $key)) {

				return WS_Form_Common::is_true($meta->{$key});
			}

			return false;
		}

		/**
		 * Whether "Translate Data Grid" is on for a plugin (per-form).
		 *
		 * Meta key: `translate_{plugin}_data_grid` (e.g. translate_wpml_data_grid).
		 *
		 * @param object $form_object Form object.
		 * @param string $plugin_id   Integration id (e.g. wpml, polylang), matching {@see 'wsf_translate_plugins'} `id`.
		 * @return bool
		 */
		public static function is_plugin_translate_data_grid_enabled($form_object, $plugin_id) {

			$meta = self::get_form_meta_as_object($form_object);
			if($meta === null) {

				return true;
			}

			$key = 'translate_' . $plugin_id . '_data_grid';

			if(property_exists($meta, $key)) {

				return WS_Form_Common::is_true($meta->{$key});
			}

			return true;
		}

		/**
		 * @param object $form_object Form object.
		 * @return bool
		 */
		public static function is_any_plugin_translation_enabled($form_object) {

			foreach(self::get_translation_plugins_list() as $plugin) {

				$id = isset($plugin['id']) ? (string) $plugin['id'] : '';

				if(
					($id !== '') &&
					self::is_plugin_translation_enabled($form_object, $id)
				) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @return bool
		 */
		public static function translate_register_is_data_grid_context() {

			return self::$translate_register_is_data_grid;
		}

		/**
		 * Form object for the current {@see form_register()} run, or null.
		 *
		 * @return object|null
		 */
		public static function get_registering_form_object() {

			return self::$registering_form_object;
		}

		/**
		 * Form object for WPML / Polylang checks and string registration when published JSON may not exist yet.
		 *
		 * Order: current {@see form_register()} object (saved state) → published snapshot → full saved form from DB.
		 *
		 * @param int $form_id Form ID.
		 * @return object|false
		 */
		public static function get_form_object_for_string_registration($form_id) {

			$form_id = absint($form_id);
			if($form_id < 1) {

				return false;
			}

			$registering = self::get_registering_form_object();
			if(
				is_object($registering) &&
				isset($registering->id) &&
				(absint($registering->id) === $form_id)
			) {

				return $registering;
			}

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $form_id;

			$published = $ws_form_form->db_read_published(false);
			if(is_object($published)) {

				return $published;
			}

			return $ws_form_form->db_read(true, true);
		}

		/**
		 * Whether to skip registering/translating this meta value as scalar text.
		 *
		 * Do not use PHP {@see empty()} — it treats string `0` and integer 0 as empty, which are valid content.
		 *
		 * @param mixed $meta_value Raw meta value.
		 * @return bool True when there is nothing meaningful to pass to string translation.
		 */
		private function is_scalar_meta_value_blank($meta_value) {

			if($meta_value === null || $meta_value === '' || $meta_value === false) {

				return true;
			}

			if(is_array($meta_value) && $meta_value === array()) {

				return true;
			}

			return false;
		}

		/**
		 * Whether the field uses the editor data grid only (no external data source).
		 * Non-empty data_source_id means options are loaded dynamically — labels should not be string-registered.
		 *
		 * @param object $field Field object.
		 * @return bool
		 */
		private function is_field_data_grid_manual_source($field) {

			if(!is_object($field) || !property_exists($field, 'meta')) {

				return true;
			}

			$data_source_id = WS_Form_Common::get_object_meta_value($field, 'data_source_id', '');

			return ($data_source_id === '');
		}

		/**
		 * On form save: register or call {@see form_unregister()} for installed translation plugins.
		 *
		 * @param int|string $form_id Form ID from {@see 'wsf_form_update'}.
		 * @return void
		 */
		public function form_update($form_id) {

			$form_id = absint($form_id);
			if($form_id < 1) { return; }

			// Source of truth after save: full form from database
			$this->form_load($form_id);
			if(
				!$this->form_object ||
				!is_object($this->form_object) ||
				empty($this->form_object->id)
			) {
				return;
			}

			$any_plugin_on = self::is_any_plugin_translation_enabled($this->form_object);

			foreach($this->get_translation_plugins() as $plugin) {

				$pid = isset($plugin['id']) ? (string) $plugin['id'] : '';

				if(
					($pid === '') ||
					self::is_plugin_translation_enabled($this->form_object, $pid)
				) {
					continue;
				}

				// Per-integration cleanup when turning one plugin off while another stays on (avoid duplicating full unregister below).
				if($any_plugin_on) {

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dynamic hook per integration
					do_action('wsf_translate_plugin_disabled_' . $pid, $form_id);
				}
			}

			if($any_plugin_on) {

				$this->form_register($this->form_object);
			} else {

				$this->form_unregister($form_id);
			}
		}

		public function form_load($form_id, $bypass_user_capability_check = false) {

			// Read form
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $form_id;

			// Get form object
			$this->form_object = $ws_form_form->db_read(true, true, false, false, $bypass_user_capability_check);
		}

		public function form_delete($form_id) {

			$this->form_unregister($form_id, 'delete');
		}

		public function form_translate($form_object) {

			if(!self::is_any_plugin_translation_enabled($form_object)) {

				return $form_object;
			}

			$this->meta_keys_config_for_conditions = null;

			// Get translatable meta keys
			$meta_keys = $this->get_meta_keys_translatable();

			// Set form object
			$this->form_object = $form_object;

			// Form
			$this->form_object = $this->form_translate_object($this->form_object, 'form', $meta_keys);

			// Groups
			if(property_exists($this->form_object, 'groups')) {

				$this->form_object->groups = $this->form_translate_groups($this->form_object->groups, $meta_keys);
			}

			return $this->form_object;
		}

		public function form_translate_groups($groups, $meta_keys) {

			foreach($groups as $group_index => $group) {

				$groups[$group_index] = $this->form_translate_object($group, 'group', $meta_keys);

				if(property_exists($groups[$group_index], 'sections')) {

					$groups[$group_index]->sections = $this->form_translate_sections($groups[$group_index]->sections, $meta_keys);
				}
			}

			return $groups;
		}

		public function form_translate_sections($sections, $meta_keys) {

			foreach($sections as $section_index => $section) {

				$sections[$section_index] = $this->form_translate_object($section, 'section', $meta_keys);

				if(property_exists($sections[$section_index], 'fields')) {

					$sections[$section_index]->fields = $this->form_translate_fields($sections[$section_index]->fields, $meta_keys);
				}
			}

			return $sections;
		}

		public function form_translate_fields($fields, $meta_keys) {

			foreach($fields as $field_index => $field) {

				$fields[$field_index] = $this->form_translate_object($field, 'field', $meta_keys);
			}

			return $fields;
		}

		public function form_translate_object($object, $object_type, $meta_keys) {

			// Get object ID
			$object_id = $this->get_object_id($object, $object_type);

			$object->label = $this->translate(

				$object->label,
				$this->get_string_name($object_type, 'label', $object_id)
			);

			if(property_exists($object, 'meta')) {

				// Translate meta data
				foreach($object->meta as $meta_key => $meta_value) {

					// Skip unknown meta keys or meta keys we should not translate
					if(!isset($meta_keys[$meta_key])) { continue; }

					$meta_key_config = $meta_keys[$meta_key];

					// Match admin sidebar: only translate meta rows that would be visible there
					if(!$this->meta_key_conditions_met($meta_key_config, $object, $object_type)) {

						continue;
					}

					// Field data grids: never pass the grid stdClass through generic translate (type LINE/TEXT).
					if(
						$object_type === 'field' &&
						isset($meta_key_config['type']) &&
						($meta_key_config['type'] === 'data_grid')
					) {

						if(
							$this->is_meta_key_field_data_grid_translatable($meta_key_config) &&
							$this->is_translate_data_grids_enabled() &&
							$this->is_field_data_grid_manual_source($object)
						) {

							$dg = $this->normalize_data_grid_structure($meta_value);
							if(
								($dg !== null) &&
								isset($dg->groups) &&
								is_array($dg->groups) &&
								($dg->groups !== array())
							) {

								$object->meta->{$meta_key} = $this->translate_field_data_grid_meta($object, $meta_key_config, $dg);
							}
						}

						continue;
					}

					// If value is empty, check if we should use a key from the config to populate the value
					if(
						($meta_value === '') &&
						isset($meta_key_config['translate_empty_key']) &&
						isset($meta_key_config[$meta_key_config['translate_empty_key']])
					) {
						$meta_value = $meta_key_config[$meta_key_config['translate_empty_key']];
					}

					// Skip empty meta values
					if($this->is_scalar_meta_value_blank($meta_value)) { continue; }

					if(is_object($meta_value) || is_array($meta_value)) {

						continue;
					}

					// Translate meta key
					$object->meta->{$meta_key} = $this->translate(

						$object->meta->{$meta_key},
						$this->get_string_name($object_type, $this->meta_key_to_id($meta_key), $object_id)
					);
				}
			}

			return $object;
		}

		/**
		 * Walk the form and fire {@see 'wsf_translate_register'} for each translatable string.
		 *
		 * @param object $form_object Form object.
		 * @return void
		 */
		public function form_register($form_object) {

			$this->form_object = $form_object;

			$this->meta_keys_config_for_conditions = null;

			self::$registering_form_object = $form_object;

			try {

				// Start
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				do_action('wsf_translate_start', $form_object->id, $form_object->label);

				// Get translatable meta keys
				$meta_keys = $this->get_meta_keys_translatable();

				// Process form
				$this->form_register_object($form_object, 'form', $meta_keys);

				// Form actions: register strings stored inside the action data grid JSON.
				$this->form_register_form_action_rows($form_object, $meta_keys);

				// Process groups
				if(property_exists($form_object, 'groups')) {

					$this->form_register_groups($form_object->groups, $meta_keys);
				}

				// Finish
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				do_action('wsf_translate_finish', $form_object->id, $form_object->label);

			} finally {
				self::$registering_form_object = null;
			}
		}

		public function form_register_groups($groups, $meta_keys) {

			foreach($groups as $group) {

				// Process group
				$this->form_register_object($group, 'group', $meta_keys);

				// Process sections
				if(property_exists($group, 'sections')) {

					$this->form_register_sections($group->sections, $meta_keys);
				}
			}
		}

		public function form_register_sections($sections, $meta_keys) {

			foreach($sections as $section) {

				// Process section
				$this->form_register_object($section, 'section', $meta_keys);

				// Process fields
				if(property_exists($section, 'fields')) {

					$this->form_register_fields($section->fields, $meta_keys);
				}
			}
		}

		public function form_register_fields($fields, $meta_keys) {

			foreach($fields as $field) {

				// Process field
				$this->form_register_object($field, 'field', $meta_keys);
			}
		}

		public function form_register_object($object, $object_type, $meta_keys) {

			// Get object ID
			$object_id = $this->get_object_id($object, $object_type);

			// Get object label
			$object_label = $object->label;

			// Register object label
			$this->register(

				$this->get_string_name($object_type, 'label', $object_id),
				$this->get_string_label($object_type, $object_id, $object_label, __('Label', 'ws-form')),
				'text',
				$object_label
			);

			if(property_exists($object, 'meta')) {

				// Register meta data translations
				foreach($object->meta as $meta_key => $meta_value) {

					// Skip unknown meta keys or meta keys we should not translate
					if(!isset($meta_keys[$meta_key])) { continue; }

					// Get meta config
					$meta_config = $meta_keys[$meta_key];

					// Match admin sidebar: only register strings for meta that applies to current setup (same as condition rows)
					if(!$this->meta_key_conditions_met($meta_config, $object, $object_type)) {

						continue;
					}

					// Field data grids: structured meta — never register the grid stdClass as one string (WPML expects a scalar).
					// Per-cell registration only when {@see is_meta_key_field_data_grid_translatable()} and data grids enabled.
					if(
						$object_type === 'field' &&
						isset($meta_config['type']) &&
						($meta_config['type'] === 'data_grid')
					) {

						if(
							$this->is_meta_key_field_data_grid_translatable($meta_config) &&
							$this->is_translate_data_grids_enabled() &&
							$this->is_field_data_grid_manual_source($object)
						) {

							$dg = $this->normalize_data_grid_structure($meta_value);
							if(
								($dg !== null) &&
								isset($dg->groups) &&
								is_array($dg->groups) &&
								($dg->groups !== array())
							) {

								$this->register_field_data_grid_strings($object, $object_id, $meta_config, $dg);
							}
						}

						continue;
					}

					// If value is empty, use translate_empty_key fallback (same rules as {@see form_translate_object()})
					if(
						($meta_value === '') &&
						isset($meta_config['translate_empty_key']) &&
						isset($meta_config[$meta_config['translate_empty_key']])
					) {

						$meta_value = $meta_config[$meta_config['translate_empty_key']];
					}

					// Skip empty meta values
					if($this->is_scalar_meta_value_blank($meta_value)) { continue; }

					// String packages cannot register objects/arrays as one string (defense in depth).
					if(is_object($meta_value) || is_array($meta_value)) {

						continue;
					}

					// Get meta label
					$meta_label = isset($meta_config['label']) ? $meta_config['label'] : __('Unknown', 'ws-form');

					// Register meta key
					$this->register(

						$this->get_string_name($object_type, $this->meta_key_to_id($meta_key), $object_id),
						$this->get_string_label($object_type, $object_id, $object_label, $meta_label),		// String label
						$meta_config['type'],																// String type
						$meta_value,																		// String value
					);
				}
			}
		}

		/**
		 * Register translatable meta stored inside the form Actions data grid (each row’s JSON `meta` object).
		 *
		 * @param object $form_object Form object.
		 * @param array  $meta_keys   Keys from {@see get_meta_keys_translatable()}.
		 * @return void
		 */
		private function form_register_form_action_rows($form_object, $meta_keys) {

			if(!is_array($meta_keys) || ($meta_keys === array())) {

				return;
			}

			$rows = $this->get_form_action_rows($form_object);
			if($rows === null) {

				return;
			}

			$form_label = property_exists($form_object, 'label') ? $form_object->label : '';

			foreach($rows as $row) {

				if(!is_object($row) || !isset($row->id)) {

					continue;
				}

				$data = $this->decode_form_action_row_json($row);
				if(
					($data === null) ||
					!isset($data->meta) ||
					!is_object($data->meta)
				) {

					continue;
				}

				$action_context = new stdClass();
				$action_context->meta = $data->meta;

				$row_label = (
					isset($row->data[0]) &&
					is_string($row->data[0]) &&
					($row->data[0] !== '')
				) ? $row->data[0] : __('Action', 'ws-form');

				foreach($meta_keys as $meta_key => $meta_config) {

					if(
						!is_string($meta_key) ||
						(strpos($meta_key, 'action_') !== 0)
					) {

						continue;
					}

					if(!$this->meta_key_conditions_met($meta_config, $action_context, 'form')) {

						continue;
					}

					if(!property_exists($data->meta, $meta_key)) {

						continue;
					}

					$meta_value = $data->meta->{$meta_key};

					if(
						($meta_value === '') &&
						isset($meta_config['translate_empty_key']) &&
						isset($meta_config[$meta_config['translate_empty_key']])
					) {

						$meta_value = $meta_config[$meta_config['translate_empty_key']];
					}

					if($this->is_scalar_meta_value_blank($meta_value)) {

						continue;
					}

					if(!isset($meta_config['type'])) {

						continue;
					}

					$meta_label = isset($meta_config['label']) ? $meta_config['label'] : __('Unknown', 'ws-form');

					$form_setting_label = sprintf(

						/* translators: 1: Action row title (e.g. Show Message), 2: Setting label (e.g. Content) */
						__('Action: %1$s - %2$s', 'ws-form'),
						$row_label,
						$meta_label
					);

					$sname = $this->get_string_name('form', $this->get_form_action_row_string_name_suffix($row->id, $meta_key), false);

					$this->register(

						$sname,
						$this->get_string_label('form', false, $form_label, $form_setting_label),
						$meta_config['type'],
						is_scalar($meta_value) ? (string) $meta_value : ''
					);
				}
			}
		}

		/**
		 * Translate action `meta` strings when {@see WS_Form_Action::actions_post()} runs (submit/save/action modes).
		 *
		 * @param array<int, array<string, mixed>> $actions Action configs ({@see WS_Form_Action::get_form_actions()} includes `row_id`).
		 * @param object                           $form    Form object.
		 * @param object                           $_submit Unused (third filter argument).
		 * @return array<int, array<string, mixed>>
		 */
		public function translate_actions_post_configs($actions, $form, $_submit) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter arity.

			if(
				!is_array($actions) ||
				!is_object($form) ||
				!isset($form->id) ||
				!self::is_any_plugin_translation_enabled($form)
			) {

				return $actions;
			}
			$this->meta_keys_config_for_conditions = null;

			$saved_form_object = $this->form_object;
			$this->form_object = $form;

			$meta_keys = $this->get_meta_keys_translatable();

			try {

				foreach($actions as $index => $config) {

					if(
						!is_array($config) ||
						!isset($config['meta']) ||
						!is_array($config['meta'])
					) {

						continue;
					}

					$row_id = isset($config['row_id']) ? absint($config['row_id']) : 0;
					if($row_id < 1) {

						continue;
					}

					$this->translate_form_action_meta_values($actions[$index]['meta'], $row_id, $meta_keys);
				}
			} finally {

				$this->form_object = $saved_form_object;
				$this->meta_keys_config_for_conditions = null;
			}

			return $actions;
		}

		/**
		 * Translate registered action `meta` keys in place (same rules as {@see form_register_form_action_rows()}).
		 *
		 * @param array<string, mixed> $meta      Action meta (by reference).
		 * @param int                  $row_id    Action data grid row id ({@see WS_Form_Action::get_form_actions()}).
		 * @param array<string, array> $meta_keys {@see get_meta_keys_translatable()}.
		 * @return void
		 */
		private function translate_form_action_meta_values(array &$meta, $row_id, array $meta_keys) {

			$row_id = absint($row_id);
			if(
				($row_id < 1) ||
				($meta_keys === array())
			) {

				return;
			}

			$action_context = new stdClass();
			$action_context->meta = json_decode(wp_json_encode($meta), false);
			if(!is_object($action_context->meta)) {

				return;
			}

			foreach($meta_keys as $meta_key => $meta_config) {

				if(
					!is_string($meta_key) ||
					(strpos($meta_key, 'action_') !== 0)
				) {

					continue;
				}

				if(!$this->meta_key_conditions_met($meta_config, $action_context, 'form')) {

					continue;
				}

				if(!array_key_exists($meta_key, $meta)) {

					continue;
				}

				$meta_value = $meta[$meta_key];

				if(
					($meta_value === '') &&
					isset($meta_config['translate_empty_key']) &&
					isset($meta_config[$meta_config['translate_empty_key']])
				) {

					$meta_value = $meta_config[$meta_config['translate_empty_key']];
				}

				if($this->is_scalar_meta_value_blank($meta_value)) {

					continue;
				}

				if(!isset($meta_config['type'])) {

					continue;
				}

				$string_name = $this->get_string_name('form', $this->get_form_action_row_string_name_suffix($row_id, $meta_key), false);

				$meta[$meta_key] = $this->translate((string) $meta_value, $string_name);
			}
		}

		/**
		 * Form Actions data grid rows (each row is normalized to an object for stable reads/writes).
		 *
		 * After {@see WS_Form_Form::db_read()}, `meta.action` may be arrays or objects depending on
		 * serialize/JSON round-trips; previously we required strict objects and skipped every row.
		 *
		 * @param object $form_object Form object (mutated: canonical object tree written to meta.action).
		 * @return array<int, object>|null
		 */
		private function get_form_action_rows($form_object) {

			if(
				!is_object($form_object) ||
				!property_exists($form_object, 'meta') ||
				!is_object($form_object->meta) ||
				!property_exists($form_object->meta, 'action') ||
				!isset($form_object->meta->action)
			) {

				return null;
			}

			// Single canonical tree: avoids array-vs-object mismatches from DB unserialize + json_encode decode.
			$encoded = wp_json_encode($form_object->meta->action);
			if(
				($encoded === false) ||
				($encoded === '') ||
				($encoded === 'null')
			) {

				return null;
			}

			$normalized = json_decode($encoded);
			if(!is_object($normalized)) {

				return null;
			}

			$form_object->meta->action = $normalized;

			if(!isset($normalized->groups)) {

				return null;
			}

			$groups = $normalized->groups;
			$g0 = null;

			if(is_array($groups) && isset($groups[0])) {

				$g0 = $groups[0];

			} elseif(is_object($groups)) {

				if(isset($groups->{'0'})) {

					$g0 = $groups->{'0'};
				}
			}

			if($g0 === null) {

				return null;
			}

			if(is_array($g0)) {

				$g0 = json_decode(wp_json_encode($g0), false);
				if(is_array($normalized->groups)) {

					$normalized->groups[0] = $g0;

				} elseif(is_object($normalized->groups)) {

					$normalized->groups->{'0'} = $g0;
				}
			}

			if(!is_object($g0) || !isset($g0->rows) || !is_array($g0->rows)) {

				return null;
			}

			foreach($g0->rows as $i => $row) {

				if(is_array($row)) {

					$g0->rows[$i] = json_decode(wp_json_encode($row), false);
				}
			}

			return $g0->rows;
		}

		/**
		 * @param object|array<int|string, mixed> $row Action data grid row.
		 * @return object|null
		 */
		private function decode_form_action_row_json($row) {

			$data_column = null;
			$row_id = null;

			if(is_object($row)) {

				if(!isset($row->data) || !is_array($row->data)) {

					return null;
				}

				$data_column = $row->data;
				$row_id = isset($row->id) ? $row->id : null;

			} elseif(is_array($row)) {

				if(!isset($row['data']) || !is_array($row['data'])) {

					return null;
				}

				$data_column = $row['data'];
				$row_id = isset($row['id']) ? $row['id'] : null;

			} else {

				return null;
			}

			if(
				($row_id === null) ||
				!isset($data_column[1]) ||
				!is_string($data_column[1]) ||
				($data_column[1] === '')
			) {

				return null;
			}

			$data = json_decode($data_column[1]);
			if(($data === null) || !is_object($data)) {

				return null;
			}

			if(isset($data->meta) && is_array($data->meta)) {

				$data->meta = (object) $data->meta;
			}

			return $data;
		}

		/**
		 * Stable suffix for action JSON meta keys (used inside {@see get_string_name()} with object_type form).
		 *
		 * Example: meta key `action_message_message`, row ID `2` → `action_2_message_message`, full name
		 * `form_{form_id}_action_2_message_message` for WPML / Polylang string IDs.
		 *
		 * @param int|string $row_id    Action data grid row ID.
		 * @param string     $meta_key  Full meta key (e.g. action_message_message).
		 * @return string
		 */
		private function get_form_action_row_string_name_suffix($row_id, $meta_key) {

			$row_id = absint($row_id);
			$mk = (string) $meta_key;

			if(
				($row_id > 0) &&
				preg_match('/^action_([a-z0-9]+)_(.+)$/i', $mk, $m)
			) {

				$action_type = strtolower($m[1]);
				$field_part = $m[2];

				return sprintf(

					'action_%u_%s_%s',
					$row_id,
					$action_type,
					$this->meta_key_to_id($field_part)
				);
			}

			return sprintf(

				'action_%u_%s',
				$row_id,
				$this->meta_key_to_id($mk)
			);
		}

		/**
		 * Whether {@see 'wsf_translate_data_grids'} allows field data grid handling for this form.
		 *
		 * @return bool
		 */
		private function is_translate_data_grids_enabled() {

			if(!is_object($this->form_object)) {

				return false;
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WS Form prefixed filter
			return (bool) apply_filters('wsf_translate_data_grids', false, $this->form_object);
		}

		/**
		 * Whether this meta key is a field options data grid translated per row (label column only).
		 *
		 * Config shape only — {@see is_translate_data_grids_enabled()} gates integration opt-in.
		 *
		 * @param array<string, mixed> $meta_key_config Meta key config from WS_Form_Config.
		 * @return bool
		 */
		private function is_meta_key_field_data_grid_translatable($meta_key_config) {

			return (
				isset($meta_key_config['translate']) &&
				$meta_key_config['translate'] &&
				isset($meta_key_config['type']) &&
				($meta_key_config['type'] === 'data_grid') &&
				isset($meta_key_config['meta_key_label']) &&
				($meta_key_config['meta_key_label'] !== '')
			);
		}

		/**
		 * Normalize stored meta (object or array) to a tree of stdClass for consistent traversal.
		 *
		 * @param mixed $value Raw meta value.
		 * @return object|null
		 */
		private function normalize_data_grid_structure($value) {

			if(($value === null) || ($value === false)) {

				return null;
			}

			$encoded = wp_json_encode($value);

			if(($encoded === false) || ($encoded === '')) {

				return null;
			}

			$decoded = json_decode($encoded);

			return is_object($decoded) ? $decoded : null;
		}

		/**
		 * Column index for the mapped label column ID (legacy columns without id use their index as id).
		 *
		 * @param object $data_grid Normalized data grid object.
		 * @param mixed  $column_id   Column ID from field meta (e.g. select_field_label).
		 * @return int
		 */
		private function data_grid_get_column_index_by_column_id($data_grid, $column_id) {

			if(!isset($data_grid->columns) || !is_array($data_grid->columns)) {

				return 0;
			}

			foreach($data_grid->columns as $column_index => $column) {

				$column_obj = is_object($column) ? $column : (object) $column;
				$id = property_exists($column_obj, 'id') ? $column_obj->id : $column_index;

				if(
					((string) $id === (string) $column_id) ||
					(absint($id) === absint($column_id))
				) {

					return (int) $column_index;
				}
			}

			return 0;
		}

		/**
		 * Admin-facing field type name for translation string titles.
		 *
		 * @param object $field Field object.
		 * @return string
		 */
		private function get_field_type_label_for_translate($field) {

			$type = (property_exists($field, 'type') && is_string($field->type)) ? $field->type : '';

			if($type === '') {

				return __('Field', 'ws-form');
			}

			$field_types = WS_Form_Config::get_field_types_flat();

			if(isset($field_types[$type]['label']) && is_string($field_types[$type]['label'])) {

				return $field_types[$type]['label'];
			}

			return $type;
		}

		/**
		 * Register one translatable string per label cell in the data grid.
		 *
		 * @param object $field         Field object.
		 * @param int    $object_id     Field ID.
		 * @param array  $meta_config   Meta key config (data_grid type).
		 * @param object $data_grid     Normalized data grid object.
		 * @return void
		 */
		private function register_field_data_grid_strings($field, $object_id, $meta_config, $data_grid) {

			$prev_grid = self::$translate_register_is_data_grid;
			self::$translate_register_is_data_grid = true;

			try {

			$label_meta_key = $meta_config['meta_key_label'];
			$column_id = WS_Form_Common::get_object_meta_value($field, $label_meta_key, 0);
			$label_column_index = $this->data_grid_get_column_index_by_column_id($data_grid, $column_id);

			$field_type_label = $this->get_field_type_label_for_translate($field);

			foreach($data_grid->groups as $group_index => $group) {

				$group_obj = is_object($group) ? $group : (object) $group;
				$group_id = property_exists($group_obj, 'id') ? absint($group_obj->id) : (int) $group_index;

				if(!property_exists($group_obj, 'rows') || !is_array($group_obj->rows)) {

					continue;
				}

				foreach($group_obj->rows as $row_index => $row) {

					$row_obj = is_object($row) ? $row : (object) $row;
					$row_id = property_exists($row_obj, 'id') ? absint($row_obj->id) : (int) $row_index;

					if(!property_exists($row_obj, 'data') || !is_array($row_obj->data)) {

						continue;
					}

					if(!array_key_exists($label_column_index, $row_obj->data)) {

						continue;
					}

					$label_cell = $row_obj->data[$label_column_index];

					if(!is_scalar($label_cell)) {

						continue;
					}

					$label_text = (string) $label_cell;

					$suffix = sprintf('data_grid_group_%u_row_%u', $group_id, $row_id);

					$string_name = $this->get_string_name('field', $suffix, $object_id);

					$preview = ($label_text !== '') ? $label_text : __('(Empty)', 'ws-form');

					$string_title = sprintf(

						/* translators: 1: Field type name (e.g. Select), 2: Field ID, 3: Option label text */
						__('Field: %1$s (ID: %2$s) - Option: %3$s', 'ws-form'),
						$field_type_label,
						(string) absint($object_id),
						$preview
					);

					$this->register(

						$string_name,
						$string_title,
						'text',
						$label_text
					);
				}
			}

			} finally {

				self::$translate_register_is_data_grid = $prev_grid;
			}
		}

		/**
		 * Apply translations to label cells and return the updated data grid object for meta.
		 *
		 * @param object $field         Field object.
		 * @param array  $meta_config   Meta key config.
		 * @param object $data_grid     Normalized data grid (same structure as stored meta).
		 * @return object
		 */
		private function translate_field_data_grid_meta($field, $meta_config, $data_grid) {

			$label_meta_key = $meta_config['meta_key_label'];
			$column_id = WS_Form_Common::get_object_meta_value($field, $label_meta_key, 0);
			$label_column_index = $this->data_grid_get_column_index_by_column_id($data_grid, $column_id);

			$object_id = $this->get_object_id($field, 'field');

			foreach($data_grid->groups as $group_index => $group) {

				$group_obj = is_object($group) ? $group : (object) $group;
				$group_id = property_exists($group_obj, 'id') ? absint($group_obj->id) : (int) $group_index;

				if(!property_exists($group_obj, 'rows') || !is_array($group_obj->rows)) {

					continue;
				}

				foreach($group_obj->rows as $row_index => $row) {

					$row_obj = is_object($row) ? $row : (object) $row;
					$row_id = property_exists($row_obj, 'id') ? absint($row_obj->id) : (int) $row_index;

					if(!property_exists($row_obj, 'data') || !is_array($row_obj->data)) {

						continue;
					}

					if(!array_key_exists($label_column_index, $row_obj->data)) {

						continue;
					}

					$label_cell = $row_obj->data[$label_column_index];

					if(!is_scalar($label_cell)) {

						continue;
					}

					$suffix = sprintf('data_grid_group_%u_row_%u', $group_id, $row_id);

					$string_name = $this->get_string_name('field', $suffix, $object_id);

					$data_grid->groups[$group_index]->rows[$row_index]->data[$label_column_index] = $this->translate(

						(string) $label_cell,
						$string_name
					);
				}
			}

			return $data_grid;
		}

		/**
		 * Full WS_Form_Config meta keys (for control-key types in conditions). Cached per register/translate pass.
		 *
		 * @return array<string, array>
		 */
		private function get_meta_keys_config_for_conditions() {

			if($this->meta_keys_config_for_conditions !== null) {

				return $this->meta_keys_config_for_conditions;
			}

			$form_id = (
				is_object($this->form_object) &&
				isset($this->form_object->id)
			) ? absint($this->form_object->id) : 0;

			$this->meta_keys_config_for_conditions = WS_Form_Config::get_meta_keys($form_id, false, false);

			return $this->meta_keys_config_for_conditions;
		}

		/**
		 * Whether a meta key's `condition` array matches the current object (same rules as admin sidebar meta conditions).
		 *
		 * @param array  $meta_key_config Config for the translatable meta key (includes optional `condition`).
		 * @param object $object          Form, group, section, or field object.
		 * @param string $object_type     form|group|section|field.
		 * @return bool
		 */
		private function meta_key_conditions_met($meta_key_config, $object, $object_type) {

			if(
				!isset($meta_key_config['condition']) ||
				!is_array($meta_key_config['condition']) ||
				$meta_key_config['condition'] === array()
			) {

				return true;
			}

			$meta_keys_full = $this->get_meta_keys_config_for_conditions();

			$combined = null;

			foreach($meta_key_config['condition'] as $condition) {

				if(!is_array($condition)) {

					continue;
				}

				$row = $this->evaluate_meta_condition_row($condition, $object, $meta_keys_full);

				if($combined === null) {

					$combined = $row;
				} else {

					$logic_previous = isset($condition['logic_previous']) ? $condition['logic_previous'] : '&&';

					if($logic_previous === '||') {

						$combined = $combined || $row;
					} else {

						$combined = $combined && $row;
					}
				}
			}

			return ($combined === null) ? true : (bool) $combined;
		}

		/**
		 * Evaluate one condition row (logic, meta_key, meta_value, optional type / logic_previous).
		 *
		 * @param array<string, mixed> $condition    Single condition from meta key config.
		 * @param object               $object       Current sidebar object.
		 * @param array<string, array> $meta_keys_full Full meta key definitions.
		 * @return bool
		 */
		private function evaluate_meta_condition_row($condition, $object, $meta_keys_full) {

			if(!isset($condition['meta_key']) || $condition['meta_key'] === '') {

				return true;
			}

			$control_key = $condition['meta_key'];

			$condition_type = isset($condition['type']) ? $condition['type'] : 'sidebar_meta_key';

			// Form-level meta (e.g. section option gated by form "conversational")
			if($condition_type === 'object_meta_value_form') {

				$source = $this->form_object;
			} else {

				$source = $object;
			}

			if(!is_object($source) || !property_exists($source, 'meta')) {

				$actual = '';
			} else {

				$actual = WS_Form_Common::get_object_meta_value($source, $control_key, '');
			}

			$control_config = isset($meta_keys_full[$control_key]) ? $meta_keys_full[$control_key] : array();
			$control_field_type = isset($control_config['type']) ? $control_config['type'] : 'text';

			// Select "default" option means read fallback meta (matches admin JS options_default)
			if(
				$control_field_type !== 'checkbox' &&
				(string) $actual === 'default' &&
				isset($control_config['options_default'])
			) {

				$fallback_key = $control_config['options_default'];

				if(
					is_object($this->form_object) &&
					property_exists($this->form_object, 'meta')
				) {

					$actual = WS_Form_Common::get_object_meta_value($this->form_object, $fallback_key, '');
				}
			}

			$logic = isset($condition['logic']) ? $condition['logic'] : '==';
			$expected = isset($condition['meta_value']) ? $condition['meta_value'] : '';

			// Checkbox: admin uses checked state; == means "if checked", != means "if unchecked"
			if($control_field_type === 'checkbox') {

				$checked = (
					$actual === 'on' ||
					$actual === true ||
					$actual === '1' ||
					$actual === 1
				);

				switch($logic) {

					case '!=':

						return ! $checked;

					case '==':
					default:

						return $checked;
				}
			}

			$actual_string = is_scalar($actual) ? (string) $actual : '';

			switch($logic) {

				case '!=':

					return $actual_string != (string) $expected;

				case 'contains':

					return strpos($actual_string, (string) $expected) !== false;

				case 'contains_not':

					return strpos($actual_string, (string) $expected) === false;

				case '==':
				default:

					return $actual_string == (string) $expected;
			}
		}

		public function get_object_id($object, $object_type) {

			switch($object_type) {

				case 'form' :

					return false;

				case 'group' :
				case 'section' :
				case 'field' :

					return $object->id;
			}
		}

		/**
		 * Normalized meta key segment for {@see get_string_name()} (lowercase, underscores only).
		 *
		 * @param string $meta_key Meta key from form object.
		 * @return string
		 */
		public function meta_key_to_id($meta_key) {

			$s = strtolower((string) $meta_key);
			$s = preg_replace('/[^a-z0-9_]+/', '_', $s);
			$s = trim(preg_replace('/_+/', '_', $s), '_');

			return $s !== '' ? $s : 'meta';
		}

		/**
		 * Stable translation string name for {@see 'wsf_translate'} / {@see 'wsf_translate_register'}.
		 * Pattern: `{type}_{id}_{suffix}` (e.g. field_123_help, form_45_label).
		 * Form action JSON settings use suffix from {@see get_form_action_row_string_name_suffix()} (e.g. form_12_action_2_message_message).
		 *
		 * @param string       $object_type form|group|section|field.
		 * @param string       $suffix      label or meta suffix ({@see meta_key_to_id()}).
		 * @param int|false    $object_id   Object id; false for form root (uses current form id).
		 * @return string
		 */
		public function get_string_name($object_type, $suffix, $object_id) {

			$suffix = is_string($suffix) ? $suffix : '';
			$suffix = strtolower($suffix);
			$suffix = preg_replace('/[^a-z0-9_]+/', '_', $suffix);
			$suffix = trim(preg_replace('/_+/', '_', $suffix), '_');

			if($suffix === '') {

				$suffix = 'x';
			}

			if($object_type === 'form') {

				$form_id = (
					is_object($this->form_object) &&
					isset($this->form_object->id)
				) ? absint($this->form_object->id) : 0;

				if($form_id > 0) {

					return sprintf('form_%u_%s', $form_id, $suffix);
				}
			}

			if($object_id !== false && $object_id !== null) {

				return sprintf('%s_%u_%s', $object_type, absint($object_id), $suffix);
			}

			return sprintf('%s_%s', $object_type, $suffix);
		}

		public function get_string_label($object_type, $object_id, $object_label, $meta_label) {

			$object_label_display = ($object_label !== '') ? $object_label : __('(Untitled)', 'ws-form');

			switch($object_type) {

				case 'form' :

					$form_id = (
						is_object($this->form_object) &&
						isset($this->form_object->id)
					) ? absint($this->form_object->id) : 0;

					/* translators: 1: Form name, 2: Form ID, 3: Setting label, or Action: row title - setting (form actions) */
					return sprintf(__('Form: %1$s (ID: %2$s) %3$s', 'ws-form'), $object_label_display, (string) $form_id, $meta_label);

				case 'group' :

					/* translators: 1: Tab label, 2: Tab ID, 3: Setting label */
					return sprintf(__('Tab: %1$s (ID: %2$s) - %3$s', 'ws-form'), $object_label_display, (string) $object_id, $meta_label);

				case 'section' :

					/* translators: 1: Section label, 2: Section ID, 3: Setting label */
					return sprintf(__('Section: %1$s (ID: %2$s) - %3$s', 'ws-form'), $object_label_display, (string) $object_id, $meta_label);

				case 'field' :

					/* translators: 1: Field label, 2: Field ID, 3: Setting label */
					return sprintf(__('Field: %1$s (ID: %2$s) - %3$s', 'ws-form'), $object_label_display, (string) $object_id, $meta_label);
			}
		}

		public function get_meta_keys_translatable() {

			// Get meta keys
			$meta_keys = array();

			foreach(WS_Form_Config::get_meta_keys(0, false, true) as $meta_key => $meta_key_config) {

				// Add translatable meta keys
				if(
					isset($meta_key_config['translate']) &&
					$meta_key_config['translate']
				) {
					$meta_keys[$meta_key] = $meta_key_config;
				}
			}

			return $meta_keys;
		}

		public function translate($string_value, $string_name) {

			return apply_filters(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				'wsf_translate',
				$string_value,
				$string_name,
				$this->form_object->id,
				$this->form_object->label
			);
		}

		/**
		 * Notify integrations to register one translatable string (Polylang, WPML, …).
		 *
		 * Action: {@see 'wsf_translate_register'}. Args: ( $string_value, $string_name, $string_label, $type, $form_id, $form_label ).
		 *
		 * @param string $string_name  Stable key.
		 * @param string $string_label Admin label.
		 * @param string $type         Meta type (e.g. text, data_grid).
		 * @param mixed  $string_value Default-language string.
		 * @return void
		 */
		public function register($string_name, $string_label, $type, $string_value) {

			do_action(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				'wsf_translate_register',
				$string_value,
				$string_name,
				$string_label,
				$type,
				$this->form_object->id,
				$this->form_object->label
			);
		}

		/**
		 * Remove this form's string package from translation plugins (e.g. when translation is off or the form is deleted).
		 *
		 * @param int    $form_id Form ID.
		 * @param string $context Optional. `disable` — respect per-form “Delete Package on Disable” (and filters) where applicable.
		 *                      `delete` — form is being permanently removed; integrations must drop registered strings/packages regardless of that setting.
		 * @return void
		 */
		public function form_unregister($form_id, $context = 'disable') {

			$form_id = absint($form_id);
			$context = is_string($context) ? $context : 'disable';
			if($context !== 'delete') {

				$context = 'disable';
			}

			do_action(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				'wsf_translate_unregister',
				$form_id,
				$context
			);
		}
	}

	new WS_Form_Translate();
