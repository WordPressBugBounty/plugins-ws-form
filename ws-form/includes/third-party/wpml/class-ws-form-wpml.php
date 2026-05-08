<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// WPML string packages — https://wpml.org/documentation/support/string-package-translation/
	// Declaring kinds, registering strings, translating on frontend, updating/removing strings, deleting packages.

	/**
	 * Translation tab form meta keys share the prefix `translate_wpml_` (except the main toggle):
	 *
	 * - translate_wpml — Enable WPML string package registration.
	 * - translate_wpml_data_grid — Register choice labels (data grids) for WPML.
	 * - translate_wpml_button_1, translate_wpml_button_2 — Admin shortcut buttons.
	 * - translate_wpml_delete_package_on_disable — Optional: remove WPML package when translation is turned off.
	 * - translate_wpml_purge_warning — Virtual note field (warns when Delete Package on Disable is enabled).
	 */
	class WS_Form_WPML {

		public function __construct() {

			// Declare active string package kind (WPML 4.7+) — must match kind / kind_slug on packages
			// https://wpml.org/wpml-hook/wpml_active_string_package_kinds/
			add_filter('wpml_active_string_package_kinds', array($this, 'active_string_package_kinds'), 10, 1);

			// Register filters
			add_filter('wsf_translate_plugins', array($this, 'plugins'), 10, 1);
			add_filter('wsf_translate', array($this, 'translate'), 10, 4);
			add_filter('wsf_translate_fieldsets', array($this, 'translation_tab_fieldsets'), 10, 1);

			// Register action hooks
			add_action('wsf_translate_start', array($this, 'start'), 10, 2);
			add_action('wsf_translate_register', array($this, 'register'), 10, 6);
			add_action('wsf_translate_finish', array($this, 'finish'), 10, 2);
			add_action('wsf_translate_unregister', array($this, 'unregister'), 10, 2);

			// Admin: WPML Translation tab meta.
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			add_action('wsf_translate_plugin_disabled_wpml', array($this, 'unregister'), 10, 1);

			add_filter('wsf_translate_data_grids', array($this, 'filter_translate_data_grids'), 10, 2);
		}

		/**
		 * Field data grids: opt in when WPML translation and Translate Data Grid are enabled for this form.
		 *
		 * @param bool   $translate_data_grids Previous value from other callbacks.
		 * @param object $form_object          Current form object (may lack meta).
		 * @return bool
		 */
		public function filter_translate_data_grids($translate_data_grids, $form_object) {

			if(
				is_object($form_object) &&
				isset($form_object->meta) &&
				WS_Form_Translate::is_plugin_translation_enabled($form_object, 'wpml') &&
				WS_Form_Translate::is_plugin_translate_data_grid_enabled($form_object, 'wpml')
			) {

				return true;
			}

			return $translate_data_grids;
		}

		public function plugins($plugins) {

			$plugins[] = array(

				'id' => 'wpml',
				'label' => __('WPML', 'ws-form'),
			);

			return $plugins;
		}

		/**
		 * Form Settings meta: WPML on the Translation tab (enable, data grid, admin links, Delete Package on Disable, optional purge warning).
		 *
		 * @param array<string, mixed> $meta_keys Meta keys.
		 * @param int                  $form_id   Form ID.
		 * @return array<string, mixed>
		 */
		public function config_meta_keys($meta_keys, $form_id = 0) {

			if(!is_array($meta_keys)) {

				$meta_keys = array();
			}

			$meta_keys['translate_wpml'] = array(

				'label'			=> __('Enable', 'ws-form'),
				'type'			=> 'checkbox',
				'default'		=> '',
				'help'			=>	sprintf(

					'%s <a href="%s" target="_blank">%s</a>',
					__('When enabled for WPML, translatable text is registered with WPML String Translation on save.', 'ws-form'),
					WS_Form_Common::get_plugin_website_url('/knowledgebase/translate-forms-with-wpml/'),
					__('Learn more', 'ws-form')
				),
				'data_change'				=>	array('event' => 'change', 'action' => 'save'),
			);

			$meta_keys['translate_wpml_data_grid'] = array(

				'label'			=> __('Translate Data Grids', 'ws-form'),
				'type'			=> 'checkbox',
				'default'		=> 'on',
				'help'			=>	__('When enabled, choice labels in select, checkbox, radio, and price variant fields are registered for WPML. Fields with Data Source enabled are excluded.', 'ws-form'),
				'condition'		=> array(

					array(

						'logic'			=> '==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'		=> 'translate_wpml',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'	=> 'on',
					),
				),
			);

			$condition = array(

				array(

					'logic'			=> '==',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'		=> 'translate_wpml',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value'	=> 'on',
				),
			);

			$meta_keys['translate_wpml_button_1'] = array(

				'label'			=> __('Translation Dashboard', 'ws-form'),
				'type'			=> 'button_url',
				'url'			=> WS_Form_Common::get_admin_url('tm/menu/main.php'),
				'target'		=> '_blank',
				'class_field'	=> array('wsf-button-primary'),
				'condition'		=> $condition,
			);

			$meta_keys['translate_wpml_button_2'] = array(

				'label'			=> __('String Translation', 'ws-form'),
				'type'			=> 'button_url',
				'url'			=> WS_Form_Common::get_admin_url('wpml-string-translation/menu/string-translation.php', false, 'context=' . $this->get_package_kind_slug() . '-form-#form_id'),
				'target'		=> '_blank',
				'condition'		=> $condition,
			);

			$meta_keys['translate_wpml_delete_package_on_disable'] = array(

				'label'			=> __('Delete Package on Disable', 'ws-form'),
				'type'			=> 'checkbox',
				'default'		=> '',
				'help'			=> __('If checked, disabling WPML for this form removes the form\'s string package in WPML and existing translations for those strings will be deleted. Leave off to keep the package and translations if you disable WPML later.', 'ws-form'),
				'condition'		=> $condition,
				'data_change'	=> array('event' => 'change', 'action' => 'save'),
			);

			$meta_keys['translate_wpml_purge_warning'] = array(

				'type'			=> 'note',
				'note_type'		=> 'warning',
				'html'			=> __('You have enabled deleting the WPML package when translation is turned off. If you disable WPML for this form, WPML will remove the string package and you will lose existing translations for those strings.', 'ws-form'),
				'condition'		=> array(

					array(

						'logic'			=> '==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'		=> 'translate_wpml',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'	=> 'on',
					),
					array(

						'logic'				=> '==',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_key'			=> 'translate_wpml_delete_package_on_disable',
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'meta_value'		=> 'on',
						'logic_previous'	=> '&&',
					),
				),
			);

			return $meta_keys;
		}

		/**
		 * Translation tab: WPML fieldset (buttons, Delete Package on Disable, optional purge warning).
		 *
		 * @param array<int, array{label: string, meta_keys: array<int, string>}> $fieldsets Fieldsets.
		 * @return array<int, array{label: string, meta_keys: array<int, string>}>
		 */
		public function translation_tab_fieldsets($fieldsets) {

			if(!is_array($fieldsets)) {

				$fieldsets = array();
			}

			$meta_keys = array(
				'translate_wpml',
				'translate_wpml_data_grid',
				'translate_wpml_button_1',
				'translate_wpml_button_2',
				'translate_wpml_delete_package_on_disable',
				'translate_wpml_purge_warning',
			);

			$fieldsets[] = array(

				'label'			=> __('WPML', 'ws-form'),
				'meta_keys'		=> $meta_keys,
			);

			return $fieldsets;
		}

		/**
		 * Register WS Form as an active string package kind with WPML (4.7+).
		 * Structure matches WPML docs: keyed by slug; each entry has title, plural, slug.
		 *
		 * @link https://wpml.org/documentation/support/string-package-translation/ Section 2
		 * @link https://wpml.org/wpml-hook/wpml_active_string_package_kinds/
		 *
		 * `title` must match package `kind`; `slug` must match package `kind_slug` — same values as {@see get_package_object()}.
		 *
		 * @param array $kinds Existing kinds keyed by slug (slug matches inner `slug` key).
		 * @return array
		 */
		public function active_string_package_kinds($kinds) {

			if(!is_array($kinds)) {

				$kinds = array();
			}

			$slug = $this->get_package_kind_slug();

			$kinds[ $slug ] = array(

				'title'		=> $this->get_package_kind(),
				// Product name: do not pluralize "WS Form" in UI strings.
				'plural'	=> $this->get_package_kind(),
				'slug'		=> $slug,
			);

			return $kinds;
		}

		public function translate($string_value, $string_name, $form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'wpml')
			) {

				return $string_value;
			}

			// https://wpml.org/wpml-hook/wpml_translate_string/
			return apply_filters(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party
				'wpml_translate_string',
				$string_value,
				$string_name,
				$this->get_package_object($form_id, $form_label)
			);
		}

		public function start($form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'wpml')
			) {

				return;
			}

			// Start string package registration
			// https://wpml.org/wpml-hook/wpml_start_string_package_registration/
			do_action(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party
				'wpml_start_string_package_registration',
				$this->get_package_object($form_id, $form_label)		// Package
			);
		}

		public function register($string_value, $string_name, $string_title, $type, $form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'wpml')
			) {

				return;
			}

			// Translate Data Grids: prefer explicit registering object; otherwise form resolved above (saved or published).
			$form_object_settings = WS_Form_Translate::get_registering_form_object();
			if(!is_object($form_object_settings)) {

				$form_object_settings = $form_object;
			}

			if(
				WS_Form_Translate::translate_register_is_data_grid_context() &&
				!WS_Form_Translate::is_plugin_translate_data_grid_enabled($form_object_settings, 'wpml')
			) {

				return;
			}

			// Register string (value, name, package, title, type — see wpml_register_string).
			// https://wpml.org/wpml-hook/wpml_register_string/
			do_action(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party
				'wpml_register_string',
				$string_value,
				$string_name,
				$this->get_package_object($form_id, $form_label),
				$string_title,
				$this->ws_form_type_convert($type)
			);
		}

		public function finish($form_id, $form_label) {

			$form_object = WS_Form_Translate::get_form_object_for_string_registration($form_id);

			if(
				!is_object($form_object) ||
				!WS_Form_Translate::is_plugin_translation_enabled($form_object, 'wpml')
			) {

				return;
			}

			// Delete unused package strings
			// https://wpml.org/wpml-hook/wpml_delete_unused_package_strings/
			do_action(

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party
				'wpml_delete_unused_package_strings',
				$this->get_package_object($form_id, $form_label)		// Package
			);
		}

		/**
		 * Whether disabling WS Form translation should delete this form’s WPML string package.
		 *
		 * Defaults to the per-form “Delete Package on Disable” setting (off unless enabled). The
		 * {@see 'wsf_translate_wpml_purge_on_disable'} filter receives that boolean so advanced sites can override.
		 *
		 * @param int $form_id Form ID.
		 * @return bool
		 */
		private function purge_wpml_package_on_translate_disable($form_id = 0) {

			$form_id = absint($form_id);
			$purge = false;

			if($form_id > 0) {

				// Saved form meta only: published snapshot (see {@see WS_Form_Translate::get_form_object_for_string_registration()})
				// does not include Translation tab settings such as translate_wpml_delete_package_on_disable.
				// Use WS_Form_Meta (not db_read): trashed forms are excluded from db_read, and wsf_form_delete runs while the row may still be trash.
				$ws_form_meta = new WS_Form_Meta();
				$ws_form_meta->object = 'form';
				$ws_form_meta->parent_id = $form_id;
				$meta_val = $ws_form_meta->db_read('translate_wpml_delete_package_on_disable');
				if($meta_val !== false) {

					$purge = WS_Form_Common::is_true($meta_val);
				}
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WS Form prefixed filter
			return (bool) apply_filters('wsf_translate_wpml_purge_on_disable', $purge, $form_id);
		}

		/**
		 * Remove this form's WPML string package when translation is turned off or the form is removed.
		 *
		 * When the form is permanently deleted (`$context === 'delete'`), the package is always removed so WPML strings
		 * do not outlive the form. “Delete Package on Disable” and {@see 'wsf_translate_wpml_purge_on_disable'} apply only
		 * when translation is turned off while the form still exists (`$context === 'disable'`).
		 *
		 * @param int         $form_id Form ID.
		 * @param string      $context Optional. `delete` or `disable` (default).
		 * @return void
		 */
		public function unregister($form_id, $context = 'disable') {

			$form_id = absint($form_id);

			if($form_id < 1) {

				return;
			}

			$context = (is_string($context) && $context === 'delete') ? 'delete' : 'disable';

			$purge = ($context === 'delete') || $this->purge_wpml_package_on_translate_disable($form_id);

			if($purge) {

				// https://wpml.org/documentation/support/string-package-translation/ — Deleting String Packages
				// https://wpml.org/wpml-hook/wpml_delete_package/
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party
				do_action('wpml_delete_package', $this->get_package_name($form_id), $this->get_package_kind());
			}
		}

		/**
		 * Map WS Form meta `type` to WPML string types (LINE, AREA, VISUAL).
		 *
		 * @link https://wpml.org/wpml-hook/wpml_register_string/
		 * @param string $type WS Form config type (e.g. text, textarea, text_editor).
		 * @return string
		 */
		public function ws_form_type_convert($type) {

			switch($type) {

				case 'textarea':	return 'AREA';
				case 'text_editor':	return 'VISUAL';
				default:			return 'LINE';
			}
		}

		/**
		 * Package payload for WPML string package hooks (one package per form).
		 *
		 * Must be an associative array: newer WPML listeners (e.g. page builders) read the package with array syntax;
		 * passing an object caused fatals in the `wpml_delete_unused_package_strings` flow.
		 *
		 * @param int|string $form_id    Form ID.
		 * @param string     $form_label Form label (used in package title; see {@see get_package_title()}).
		 * @return array{kind: string, kind_slug: string, name: string, title: string, edit_link: string, view_link: string}
		 */
		public function get_package_object($form_id, $form_label = '') {

			return array(

				'kind'      => $this->get_package_kind(),
				'kind_slug' => $this->get_package_kind_slug(),
				'name'      => $this->get_package_name($form_id),
				'title'     => $this->get_package_title($form_label, $form_id),
				// Use esc_url_raw — esc_url() HTML-encodes & (&#038;) and WPML can persist/display a broken link (#038;).
				'edit_link' => esc_url_raw(WS_Form_Common::get_admin_url('ws-form-edit', $form_id)),
				'view_link' => esc_url_raw(WS_Form_Common::get_preview_url($form_id)),
			);
		}

		/**
		 * WPML package `kind` (namespace label). Must stay identical in every locale so it matches
		 * {@see active_string_package_kinds()} `title` and stored packages — see WPML string package docs.
		 *
		 * @return string
		 */
		public function get_package_kind() {

			return WS_FORM_NAME_GENERIC;
		}

		/**
		 * Must match WPML's inference from {@see get_package_kind()} so {@see 'wpml_delete_package'} can resolve packages.
		 *
		 * @link https://wpml.org/documentation/support/string-package-translation/ Section 2 (slug / kind_slug)
		 * @return string
		 */
		public function get_package_kind_slug() {

			return sanitize_title_with_dashes($this->get_package_kind());
		}

		/**
		 * WPML package `name` (unique per form). Distinct from {@see get_package_title()} and from gettext text domain.
		 *
		 * Uses `form-{id}` so String Translation domains do not collide with add-on slugs (e.g. `ws-form-hubspot`).
		 *
		 * @param int|string $form_id Form ID.
		 * @return string
		 */
		public function get_package_name($form_id) {

			return sprintf('form-%u', absint($form_id));
		}

		/**
		 * Human-readable package title stored in WPML (`icl_string_packages.title`).
		 *
		 * String Translation lists domains as `kind - title` (see WPML package translation). Use the form name and ID here;
		 * do not prefix with “WS Form” — kind is shown separately by WPML.
		 *
		 * @param string          $form_label Form label.
		 * @param int|string|false $form_id    Form ID.
		 * @return string
		 */
		public function get_package_title($form_label = '', $form_id = 0) {

			$form_id = absint($form_id);
			$label_trimmed = is_string($form_label) ? trim($form_label) : '';

			if($label_trimmed === '') {

				$label_trimmed = __('(Untitled)', 'ws-form');
			}

			if($form_id > 0) {

				return sprintf(

					/* translators: 1: Form name, 2: Numeric form ID */
					__('%1$s (ID: %2$s)', 'ws-form'),
					$label_trimmed,
					$form_id
				);
			}

			return $label_trimmed;
		}
	}

	new WS_Form_WPML();
