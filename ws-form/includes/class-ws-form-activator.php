<?php

	// Fired during plugin activation
	class WS_Form_Activator {

		public static function activate() {

			// These are set here to avoid problems if someone has both plugins installed and migrates from basic to PRO without de-activating the basic edition first. This ensures the PRO options are set up.
			$ws_form_edition = 'basic';
			$ws_form_version = '1.10.50';

			$run_version_check = true;

			// Check for edition change
			$edition = WS_Form_Common::option_get('edition');

			// If upgrading from basic to pro, force activation scripts to run
			if($edition != $ws_form_edition) {

				// Set edition
				WS_Form_Common::option_set('edition', $ws_form_edition);

				if($edition == 'basic') { $run_version_check = false; }
			}

			// Get current plug-in version
			$version = $version_old = WS_Form_Common::option_get('version');

			// Is this a fresh install?
			$fresh_install = empty($version_old);

			// Set initial install timestamp if one does not exist
			WS_Form_Common::option_get('install_timestamp', time(), true);

			// Debug - Uncomment this to force activation scripts to run
//			$run_version_check = false;

			// Check version numbers
			if($run_version_check && ($version !== false) && ($version !== '')) {

				// Installed value is current, so do not run install script
				if(WS_Form_Common::version_compare($version, $ws_form_version) == 0) { return true; }
			}

			// Set version
			WS_Form_Common::option_set('version', $ws_form_version);

			// Force CSS rebuild
			WS_Form_Common::option_set('css_rebuild', true);

			// Flush cache
			wp_cache_flush();

			// Initialize database
			self::database_init();

			// Upgrade
			self::upgrade_init($version_old);

			// Initialize options
			self::options_init();

			// Initialize roles and capabilities
			self::capabilities_init();

			// Initialize styler
			self::styler_init($fresh_install);

			// Run action
			do_action('wsf_activate');
		}

		private static function database_init() {

			global $wpdb;

			// Include WordPress upgrade script (it isn't loaded by default)
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			// Charset
			$charset_collate = $wpdb->get_charset_collate();

			// Table prefix
			$table_prefix = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX;

			// Table: Form
			$table_name = $table_prefix . 'form';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				label varchar(1024) NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_publish datetime,
				status varchar(16) DEFAULT 'draft' NOT NULL,
				count_stat_view bigint(20) unsigned DEFAULT 0 NOT NULL,
				count_stat_save bigint(20) unsigned DEFAULT 0 NOT NULL,
				count_stat_submit bigint(20) unsigned DEFAULT 0 NOT NULL,
				count_submit bigint(20) unsigned DEFAULT 0 NOT NULL,
				count_submit_unread bigint(20) unsigned DEFAULT 0 NOT NULL,
				published longtext NOT NULL,
				published_checksum varchar(32) DEFAULT '' NOT NULL,
				checksum varchar(32) DEFAULT '' NOT NULL,
				version varchar(32) DEFAULT '' NOT NULL,
				PRIMARY KEY (id),
				KEY label (label(191)),
				KEY date_added (date_added),
				KEY status (status),
				KEY count_stat_view (count_stat_view),
				KEY count_stat_save (count_stat_save),
				KEY count_stat_submit (count_stat_submit),
				KEY count_submit (count_submit),
				KEY count_submit_unread (count_submit_unread)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Form Meta
			$table_name = $table_prefix . 'form_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Stats
			$table_name = $table_prefix . 'form_stat';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				form_id bigint(20) unsigned NOT NULL,
				count_view bigint(20) unsigned NOT NULL,
				count_save bigint(20) unsigned NOT NULL,
				count_submit bigint(20) unsigned NOT NULL,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY date_added (date_added)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Group
			$table_name = $table_prefix . 'group';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				form_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				label varchar(1024) NOT NULL,
				sort_index bigint(20) unsigned NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY label (label(191)),
				KEY sort_index (sort_index)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Group Meta
			$table_name = $table_prefix . 'group_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Section
			$table_name = $table_prefix . 'section';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_section_id bigint(20) unsigned DEFAULT 0 NOT NULL,
				group_id bigint(20) unsigned DEFAULT 0 NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				label varchar(1024) NOT NULL,
				sort_index bigint(20) unsigned NOT NULL,
				child_count bigint(20) unsigned DEFAULT 0 NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id),
				KEY parent_section_id (parent_section_id),
				KEY label (label(191)),
				KEY group_id (group_id),
				KEY sort_index (sort_index)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Section Meta
			$table_name = $table_prefix . 'section_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Field
			$table_name = $table_prefix . 'field';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				section_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				label varchar(1024) NOT NULL,
				sort_index bigint(20) unsigned NOT NULL,
				type varchar(32) NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id),
				KEY label (label(191)),
				KEY type (type),
				KEY section_id (section_id),
				KEY section_id_sort_index (section_id, sort_index)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Field Meta
			$table_name = $table_prefix . 'field_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Submit
			$table_name = $table_prefix . 'submit';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				form_id bigint(20) unsigned NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_expire datetime,
				user_id bigint(20) unsigned NOT NULL,
				hash char(32) NOT NULL,
				actions longtext NOT NULL,
				section_repeatable longtext NOT NULL,
				count_submit bigint(20) unsigned NOT NULL,
				duration bigint(20) unsigned NOT NULL,
				status char(20) NOT NULL,
				preview tinyint(1) NOT NULL,
				spam_level tinyint(1),
				starred tinyint(1) DEFAULT 0 NOT NULL,
				viewed tinyint(1) DEFAULT 0 NOT NULL,
				encrypted tinyint(1) DEFAULT 0 NOT NULL,
				token char(32) NOT NULL,
				token_validated tinyint(1) DEFAULT 0 NOT NULL,
				PRIMARY KEY (id),
				KEY form_id (form_id),
				KEY date_added (date_added),
				KEY date_expire (date_expire),
				KEY user_id (user_id),
				KEY viewed (viewed),
				KEY hash (hash),
				KEY status (status),
				KEY token (token),
				KEY form_id_status (form_id, status),
				KEY form_id_viewed_status (form_id, viewed, status)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Submit Meta
			$table_name = $table_prefix . 'submit_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				section_id bigint(20) unsigned,
				field_id bigint(20) unsigned,
				repeatable_index bigint(20) unsigned,
				meta_key varchar(191),
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key),
				KEY section_id (section_id),
				KEY field_id (field_id)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Style
			$table_name = $table_prefix . 'style';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				label varchar(1024) NOT NULL,
				date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				date_publish datetime,
				status varchar(16) DEFAULT 'publish' NOT NULL,
				`default` tinyint(1) DEFAULT 0 NOT NULL,
				default_conv tinyint(1) DEFAULT 0 NOT NULL,
				published longtext NOT NULL,
				published_checksum varchar(32) DEFAULT '' NOT NULL,
				checksum varchar(32) DEFAULT '' NOT NULL,
				version varchar(32) DEFAULT '' NOT NULL,
				PRIMARY KEY (id),
				KEY label (label(191)),
				KEY date_added (date_added),
				KEY status (status),
				KEY `default` (`default`)
			) $charset_collate;";
			dbDelta($table_sql);

			// Table: Style Meta
			$table_name = $table_prefix . 'style_meta';
			$table_sql = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY (id),
				KEY parent_id (parent_id),
				KEY meta_key (meta_key)
			) $charset_collate;";
			dbDelta($table_sql);
		}

		public static function options_init() {

			// Get mode
			$mode = WS_Form_Common::option_get('mode', 'basic', true);

			// Default options
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

		private static function options_set($mode, $fields) {

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

		private static function capabilities_init() {

			// Create administrator capabilities
			$role = get_role('administrator');

			if(!is_null($role)) {

				// Form capabilities
				$role->add_cap('create_form');
				$role->add_cap('delete_form');
				$role->add_cap('edit_form');
				$role->add_cap('export_form');
				$role->add_cap('import_form');
				$role->add_cap('publish_form');
				$role->add_cap('read_form');

				// Submission capabilities
				$role->add_cap('delete_submission');
				$role->add_cap('edit_submission');
				$role->add_cap('export_submission');
				$role->add_cap('read_submission');

				// Form style capabilities
				$role->add_cap('create_form_style');
				$role->add_cap('delete_form_style');
				$role->add_cap('edit_form_style');
				$role->add_cap('export_form_style');
				$role->add_cap('import_form_style');
				$role->add_cap('publish_form_style');
				$role->add_cap('read_form_style');

				// Manage options capabilities
				$role->add_cap('manage_options_wsform');
			}

			// Get role capabilities (ensures new capabilities are available in current session)
			if(WS_Form_Common::logged_in()) {

				wp_get_current_user()->get_role_caps();
			}
		}

		private static function styler_init($fresh_install) {

			// Check style system has initialized
			$ws_form_style = new WS_Form_Style();
			$ws_form_style->check_initialized(true, !$fresh_install);

			// Ensure all forms are configured with default style ID
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_style_resolve(true);
		}

		private static function upgrade_init($version_old) {

			global $wpdb;

			// Version 1.7.112 - Autocomplete
			if(WS_Form_Common::version_compare($version_old, '1.7.112') < 0) {

				// Table prefix
				$table_prefix = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX;

				$wpdb->update(

					$table_prefix . 'field_meta',
					array('meta_key' => 'autocomplete', 'meta_value' => 'off'),
					array('meta_key' => 'autocomplete_off', 'meta_value' => 'on'),
					array('%s', '%s'),
					array('%s', '%s')
				);

				$wpdb->update(

					$table_prefix . 'field_meta',
					array('meta_key' => 'autocomplete', 'meta_value' => 'off'),
					array('meta_key' => 'autocomplete_off_on', 'meta_value' => 'on'),
					array('%s', '%s'),
					array('%s', '%s')
				);

				$wpdb->update(

					$table_prefix . 'field_meta',
					array('meta_key' => 'autocomplete', 'meta_value' => 'new-password'),
					array('meta_key' => 'autocomplete_new_password', 'meta_value' => 'on'),
					array('%s', '%s'),
					array('%s', '%s')
				);
			}
		}
	}

