<?php

	class WS_Form_Config_Public extends WS_Form_Config {

		// Configuration - Settings - Public
		public static function get_settings_form_public($field_types_filter = array()) {

			// Additional language strings for the public
			$settings_form_public = array(

				'language' => array(

					/* translators: %s: Minimum character count */
					'error_min_length'						=>	__('Minimum character count: %s', 'ws-form'),
					/* translators: %s: Maximum character count */
					'error_max_length'						=>	__('Maximum character count: %s', 'ws-form'),
					/* translators: %s: Minimum word count */
					'error_min_length_words'				=>	__('Minimum word count: %s', 'ws-form'),
					/* translators: %s: Maximum word count */
					'error_max_length_words'				=>	__('Maximum word count: %s', 'ws-form'),

					// Data grids
					'error_data_grid_source_type'			=>	__('Data grid source type not specified', 'ws-form'),
					'error_data_grid_source_id'				=>	__('Data grid source ID not specified', 'ws-form'),
					'error_data_source_data'				=>	__('Data source data not found', 'ws-form'),
					'error_data_source_columns'				=>	__('Data source columns not found', 'ws-form'),
					'error_data_source_groups'				=>	__('Data source groups not found', 'ws-form'),
					'error_data_source_group_label'			=>	__('Data source group label not found', 'ws-form'),
					'error_data_group_rows'					=>	__('Data source group rows not found', 'ws-form'),
					'error_data_group_label'				=>	__('Data source group label not found', 'ws-form'),

					// Help
					'error_mask_help'						=>	__('No help mask defined', 'ws-form'),

					// Geocoding
					/* translators: %s: Field type */
					'error_timeout_google_maps_api_js'		=>	__('Timeout waiting for Google Maps API JS to load (%s)', 'ws-form'),
					'error_geocoder_google_address_no_results'	=>	__('No results found for Google Geocoder', 'ws-form'),
					/* translators: %s: Google geocoder error message */
					'error_geocoder_google_address_error'	=>	__('Google Geocoder error: %s', 'ws-form'),
					/* translators: %s: Error message */
					'error_tracking_geo_location'			=>	__('Tracking - Geo location error: %s', 'ws-form'),
					/* translators: %s: Error message */
					'error_geo'								=>	__('Geo - IP lookup failed: %s', 'ws-form'),

					// Form
					'error_form_draft'						=>	__('Form is in draft', 'ws-form'),
					'error_form_future'						=>	__('Form is scheduled', 'ws-form'),
					'error_form_trash'						=>	__('Form is trashed', 'ws-form'),

					// Tabs
					'error_framework_tabs_activate_js'		=>	__('Framework tab activate JS not defined', 'ws-form'),

					// Framework
					/* translators: %s: Error message */
					'error_framework_plugin'				=>	__('Framework plugin error: %s', 'ws-form'),

					// Actions
					/* translators: %s: Message */
					'error_action'							=>	__('Actions - %s', 'ws-form'),
					'error_action_no_message'				=>	__('Actions - Error', 'ws-form'),
					/* translators: %s: Error message */
					'error_js'								=>	__('Syntax error in JavaScript: %s', 'ws-form'),

					// Submit
					'error_submit_hash'						=>	__('Invalid submission hash', 'ws-form'),
					'error_api_call_hash'					=>	__('Hash not returned in API call', 'ws-form'),
					'error_api_call_hash_invalid'			=>	__('Invalid hash returned in API call', 'ws-form'),
					'error_api_call_framework_invalid'		=>	__('Framework config not found', 'ws-form'),

					// Invalid feedback
					/* translators: %s: Error message */
					'error_invalid_feedback'				=>	__('Invalid feedback set on field ID: %s', 'ws-form'),
					'error_mask_invalid_feedback'			=>	__('No invalid feedback mask defined', 'ws-form'),

				)
			);

			// Email
			if(
				empty($field_types_filter) ||
				in_array('email', $field_types_filter)
			) {
				$settings_form_public['language']['error_email_allow_deny_message']	= __('The email address entered is not allowed.', 'ws-form');
				$settings_form_public['language']['error_not_supported_video']		= __('Sorry, your browser doesn\'t support embedded videos.', 'ws-form');
				$settings_form_public['language']['error_not_supported_audio']		= __('Sorry, your browser doesn\'t support embedded audio.', 'ws-form');
			}

			// Tel
			if(
				empty($field_types_filter) ||
				in_array('tel', $field_types_filter)
			) {
				$settings_form_public['language']['iti_number']			= __('Invalid number', 'ws-form');
				$settings_form_public['language']['iti_country_code']	= __('Invalid country code', 'ws-form');
				$settings_form_public['language']['iti_short']			= __('Too short', 'ws-form');
				$settings_form_public['language']['iti_long']			= __('Too long', 'ws-form');
			}


			// Styler
			if(WS_Form_Common::styler_visible_public()) {

				// Additional language strings for the public styler feature
				$language_extra = array(

					'styler_logo'						=>	WS_FORM_NAME_PRESENTABLE,
					'styler_search_placeholder'			=>	__('Setting search...', 'ws-form'),
					'styler_undo'						=>	__('Undo', 'ws-form'),
					'styler_undo_confirm'				=>	__('Are you sure you want to undo the changes made to this style?', 'ws-form'),
					'styler_pick_color'					=>	__('Pick color', 'ws-form'),
					'styler_save'						=>	__('Save', 'ws-form'),
					'styler_import'						=>	__('Import', 'ws-form'),
					'styler_export'						=>	__('Export', 'ws-form'),
					'styler_loading'					=>	__('Loading...', 'ws-form'),
					'styler_id'							=>	__('ID', 'ws-form'),
					'styler_scheme'						=>	__('Scheme', 'ws-form'),
					'styler_scheme_base'				=>	__('Base', 'ws-form'),
					'styler_scheme_alt'					=>	__('Alt', 'ws-form'),
					'styler_scheme_both'				=>	__('Both', 'ws-form'),
					'styler_settings'					=>	__('Settings', 'ws-form'),
					'styler_support'					=>	__('Support', 'ws-form'),
					'styler_label'						=>	__('Name', 'ws-form'),
					'styler_label_placeholder'			=>	__('Style name', 'ws-form'),
					'styler_close'						=>	__('Close', 'ws-form'),
					'styler_alt'						=>	__('Alt', 'ws-form'),
					'styler_alt_auto'					=>	__('Auto', 'ws-form'),
					'styler_alt_title'					=>	__('Create alternative color', 'ws-form'),
					'styler_copy'						=>	__('Copy', 'ws-form'),
				);

				// Add to language array
				foreach($language_extra as $key => $value) {

					$settings_form_public['language'][$key] = $value;
				}
			}
			// Full name components
			$settings_form_public['name'] = array(

				'prefixes' => WS_Form_Common::get_name_prefixes(),
				'suffixes' => WS_Form_Common::get_name_suffixes()
			);

			// Apply filter
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$settings_form_public = apply_filters('wsf_config_settings_form_public', $settings_form_public);

			return $settings_form_public;
		}

		// Configuration - Get field types public
		public static function get_field_types_public($field_types_filter = array()) {

			$field_types = self::get_field_types_flat(true);

			// Filter by fields found in forms
			if(count($field_types_filter) > 0) {

				$field_types_old = $field_types;
				$field_types = array();

				foreach($field_types_filter as $field_type) {

					if(isset($field_types_old[$field_type])) { $field_types[$field_type] = $field_types_old[$field_type]; }
				}
			}

			// Strip attributes
			$public_attributes_strip = array('label' => false, 'label_default' => false, 'submit_edit' => false, 'conditional' => array('logics_enabled', 'actions_enabled'), 'compatibility_id' => false, 'kb_url' => false, 'fieldsets' => false, 'pro_required' => false);

			foreach($field_types as $key => $field) {

				foreach($public_attributes_strip as $attribute_strip => $attributes_strip_sub) {

					if(isset($field_types[$key][$attribute_strip])) {

						if(is_array($attributes_strip_sub)) {

							foreach($attributes_strip_sub as $attribute_strip_sub) {

								if(isset($field_types[$key][$attribute_strip][$attribute_strip_sub])) {

									unset($field_types[$key][$attribute_strip][$attribute_strip_sub]);
								}
							}

						} else {

							unset($field_types[$key][$attribute_strip]);
						}
					}
				}
			}

			return $field_types;
		}
	}