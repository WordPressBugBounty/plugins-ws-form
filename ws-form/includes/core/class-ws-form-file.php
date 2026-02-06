<?php

	/**
	 * File system functions used by this plugin
	 */
	class WS_Form_File {

		// Ensure WP_Filesystem is initialized
		private static function init() {

			global $wp_filesystem;

			if (empty($wp_filesystem)) {

				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			return $wp_filesystem;
		}

		// Remove a directory (matches PHP's rmdir signature)
		public static function rmdir($directory, $context = null) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($directory)) {
				return false;
			}

			// rmdir in PHP only removes empty directories
			// WP_Filesystem's rmdir has recursive option, but we'll keep it non-recursive to match PHP
			return $wp_filesystem->rmdir($directory, false);
		}

		// Read a file and output to browser (matches PHP's readfile signature)
		public static function readfile($filename, $use_include_path = false, $context = null) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($filename)) {
				return false;
			}

			$contents = $wp_filesystem->get_contents($filename);

			if ($contents === false) {
				return false;
			}

			echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Used for CSV and file upload output

			// readfile returns the number of bytes read on success
			return strlen($contents);
		}

		// Check if a file or directory exists (matches PHP's file_exists signature)
		public static function file_exists($filename) {

			$wp_filesystem = self::init();

			return $wp_filesystem->exists($filename);
		}

		// Write data to a file (matches PHP's file_put_contents signature)
		public static function file_put_contents($filename, $data, $flags = 0, $context = null) {

			$wp_filesystem = self::init();

			return $wp_filesystem->put_contents($filename, $data, FS_CHMOD_FILE);
		}

		// Read entire file into a string (matches PHP's file_get_contents signature)
		public static function file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $length = null) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($filename)) {

				return false;
			}

			return $wp_filesystem->get_contents($filename);
		}

		// Escape string to prevent CSV injection hack
		public static function esc_csv($value) {

			// Check input is a string
			if(!is_string($value) && !is_numeric($value)) { return ''; }

			// If character is safe, return the string from this point
			if(in_array(mb_substr($value, 0, 1), array('=', '-', '+', '@', ';', "\t", "\r"), true)) {

				return sprintf('\'%s', $value);

			} else {

				return $value;
			}
		}

		// Check whether a file or directory is writable (matches PHP's is_writable signature)
		public static function is_writeable($filename) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($filename)) {
				return false;
			}

			return $wp_filesystem->is_writable($filename);
		}

		// Escaped version of fputcsv
		public static function esc_fputcsv($stream, $fields) {

			// Check fields is an array
			if(!is_array($fields)) { return false; }

			// Process 
			$fields = array_map(function($value) {

				return self::esc_csv($value);

			}, $fields);

			// Run fputcsv
			return fputcsv($stream, $fields);
		}

		// Rename a file or directory (matches PHP's rename signature)
		public static function rename($oldname, $newname, $context = null) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($oldname)) {
				return false;
			}

			return $wp_filesystem->move($oldname, $newname, true);
		}

		// Get file size in bytes (matches PHP's filesize signature)
		public static function filesize($filename) {

			$wp_filesystem = self::init();

			if (!$wp_filesystem->exists($filename)) {
				return false;
			}

			return $wp_filesystem->size($filename);
		}
	}