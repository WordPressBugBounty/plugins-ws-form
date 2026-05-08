<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Weglot integration: global dynamic selector registration for WS Form output.
	 *
	 * Weglot does not use per-string registration (unlike WPML / Polylang) and is applied globally
	 * to WS Form markup via DOM selectors.
	 *
	 * @see https://developers.weglot.com/wordpress/filters/translations-filters#dynamic-selectors
	 *
	 * Loaded only when the `WEGLOT_VERSION` constant is defined and is >= 4.0.0 (third-party bootstrap in {@see WS_Form::__construct()}).
	 *
	 * Translation tab form meta keys: translate_weglot_button_1.
	 */
	class WS_Form_Weglot {

		public function __construct() {

			// Weglot API: dynamic translation, URL allowlist, and selectors (third-party hooks).
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party (Weglot)
			add_filter( 'weglot_translate_dynamics', array( $this, 'filter_weglot_translate_dynamics' ), 10, 1 );

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party (Weglot)
			add_filter( 'weglot_allowed_urls', array( $this, 'filter_weglot_allowed_urls' ), 10, 1 );

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party (Weglot)
			add_filter( 'weglot_dynamics_selectors', array( $this, 'filter_weglot_selectors' ), 10, 1 );

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third party (Weglot)
			add_filter( 'weglot_whitelist_selectors', array( $this, 'filter_weglot_selectors' ), 10, 1 );

			// Register filters
			add_filter('wsf_translate_plugins', array($this, 'plugins'), 10, 1);
			add_filter('wsf_translate', array($this, 'translate'), 10, 4);
			add_filter('wsf_translate_fieldsets', array($this, 'translation_tab_fieldsets'), 10, 1);

			// Admin: Translation tab meta.
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);
		}

		/**
		 * Enable Weglot dynamic translation for WS Form selectors.
		 *
		 * @param bool $enabled Previous value from other filters.
		 * @return bool
		 */
		public function filter_weglot_translate_dynamics($enabled) {
			return true;
		}

		/**
		 * Allow dynamic translation script on all URLs for WS Form selectors.
		 *
		 * @param array<int|string, string>|string $urls URLs or 'all'.
		 * @return array<int|string, string>|string
		 */
		public function filter_weglot_allowed_urls($urls) {
			return 'all';
		}

		/**
		 * Append global WS Form selectors for Weglot dynamic translation.
		 *
		 * @param array<int, array<string, string>> $selectors Default selectors.
		 * @return array<int, array<string, string>>
		 */
		public function filter_weglot_selectors($selectors) {

			if(!is_array($selectors)) {

				$selectors = array();
			}

			$ws_form_selectors = array(
				array('value' => '.wsf-form'),
				array('value' => '[data-wsf-message]'),
				array('value' => '[data-wsf-validate]'),
			);

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$ws_form_selectors = apply_filters( 'wsf_weglot_selectors', $ws_form_selectors );

			if(!is_array($ws_form_selectors)) {

				return $selectors;
			}

			foreach($ws_form_selectors as $item) {

				if(!is_array($item) || !isset($item['value']) || $item['value'] === '') {

					continue;
				}

				$selectors[] = $item;
			}

			return $selectors;
		}

		public function plugins($plugins) {

			if(!is_array($plugins)) {

				$plugins = array();
			}

			$plugins[] = array(

				'id' => 'weglot',
				'label' => __('Weglot', 'ws-form'),
			);

			return $plugins;
		}

		/**
		 * Passthrough: Weglot translates page HTML; there is no per-string PHP API like {@see pll__()}.
		 * Dynamic regions are handled via {@see self::filter_weglot_selectors()}.
		 *
		 * @param string          $string_value Original string.
		 * @param string          $string_name  Stable WS Form string identifier.
		 * @param int|string      $form_id      Form ID.
		 * @param string          $form_label   Form label.
		 * @return string
		 */
		public function translate($string_value, $string_name, $form_id, $form_label) {
			return $string_value;
		}

		/**
		 * Form Settings meta: Weglot on the Translation tab.
		 *
		 * @param array<string, mixed> $meta_keys Meta keys.
		 * @param int                  $form_id   Form ID.
		 * @return array<string, mixed>
		 */
		public function config_meta_keys($meta_keys, $form_id = 0) {

			if(!is_array($meta_keys)) {

				$meta_keys = array();
			}

			$meta_keys['translate_weglot_button_1'] = array(

				'label'			=> __('Weglot Settings', 'ws-form'),
				'type'			=> 'button_url',
				'url'			=> admin_url('admin.php?page=weglot-settings'),
				'target'		=> '_blank',
				'class_field'	=> array('wsf-button-primary'),
			);

			return $meta_keys;
		}

		/**
		 * Translation tab: Weglot fieldset.
		 *
		 * @param array<int, array{label: string, meta_keys: array<int, string>}> $fieldsets Fieldsets.
		 * @return array<int, array{label: string, meta_keys: array<int, string>}>
		 */
		public function translation_tab_fieldsets($fieldsets) {

			if(!is_array($fieldsets)) {

				$fieldsets = array();
			}

			$fieldsets[] = array(

				'label'			=> __('Weglot', 'ws-form'),
				'meta_keys'		=> array(
					'translate_weglot_button_1',
				),
			);

			return $fieldsets;
		}
	}

	new WS_Form_Weglot();
