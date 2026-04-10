<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use WordPress\AI_Client\AI_Client;
	use WordPress\AiClient\AiClient;
	use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
	use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

	class WS_Form_Action_WP_AI_Client extends WS_Form_Action {

		public $id = 'wp_ai_client';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $events;
		public $multiple = true;
		public $configured = true;
		public $priority = 150;
		public $can_repost = false;
		public $form_add = false;
		public $woocommerce_bypass = true;

		// Config
		public $type;

		public $input = '';
		public $input_type;
		public $input_field;
		public $input_mask;

		public $output = '';
		public $output_field;
		public $output_mask;
		public $output_mask_use;

		// Config - Text generation
		public $output_append;
		public $output_trim;
		public $output_wpautop;
		public $temperature;
		public $models_text;

		// Config - Image generation
		public $image_count;
		public $models_image;

		// Config - Speech generation
		public $models_speech;

		public $timeout;

		// Token usage
		public $prompt_tokens = '';
		public $completion_tokens = '';
		public $total_tokens = '';

		public function __construct() {

			// Events
			$this->events = array();

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);
			add_filter('wsf_config_parse_variables', array( $this, 'config_parse_variables' ), 10, 1);

			// Register init action
//			add_action('init', array($this, 'init'));
//		}

//		public function init() {

			// Set label
			/* translators: AI is the abbreviation for "Artificial Intelligence" */
			$this->label = __('AI', 'ws-form');

			// Set label for actions pull down
			/* translators: AI is the abbreviation for "Artificial Intelligence" */
			$this->label_action = __('Make AI Request', 'ws-form');

			// Register action
			parent::register($this);
		}

		// Parse variables
		public function config_parse_variables($parse_variables) {

			$parse_variables['ai'] = array(

				/* translators: AI is the abbreviation for "Artificial Intelligence" */
				'label' => __('AI', 'ws-form'),

				'variables'	=> array(

					'wp_ai_client_type' =>	array('label' => __('Type', 'ws-form'), 'value' => $this->type, 'description' => __('Returns the generation type.', 'ws-form')),
					'wp_ai_client_input' =>	array('label' => __('Input', 'ws-form'), 'value' => $this->input, 'description' => __('Returns the input.', 'ws-form')),
					'wp_ai_client_output' =>	array('label' => __('Output', 'ws-form'), 'value' => $this->output, 'description' => __('Returns the output.', 'ws-form')),
					'wp_ai_client_prompt_tokens' =>	array('label' => __('Prompt Tokens', 'ws-form'), 'value' => $this->prompt_tokens, 'description' => __('Returns the prompt token count.', 'ws-form')),
					'wp_ai_client_completion_tokens' =>	array('label' => __('Completion Tokens', 'ws-form'), 'value' => $this->completion_tokens, 'description' => __('Returns the completion token count.', 'ws-form')),
					'wp_ai_client_total_tokens' =>	array('label' => __('Total Tokens', 'ws-form'), 'value' => $this->total_tokens, 'description' => __('Returns the total token count.', 'ws-form'))
				)
			);

			return $parse_variables;
		}

		public function post($form, &$submit, $config) {

			// Check action is configured properly
			if(!self::check_configured()) { return false; }

			// Load config
			self::load_config($config);

			// Get input
			switch($this->input_type) {

				case 'mask' :

					$this->input = WS_Form_Common::parse_variables_process($this->input_mask, $form, $submit, 'text/plain');
					break;

				default :

					$this->input = trim(parent::get_submit_value($submit, WS_FORM_FIELD_PREFIX . $this->input_field, ''));
			}

			// Filter input
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$this->input = apply_filters('wsf_action_' . $this->id . '_input', $this->input, $this->type, $form, $submit, $config);

			// Timeout
			if($this->timeout > 0) {

				add_filter('wp_ai_client_default_request_timeout', array($this, 'wp_ai_client_default_request_timeout'), 10, 1);
			}

			// Set up prompt
			$prompt = AI_Client::prompt($this->input);

			// Process by type
			switch($this->type) {

				case 'text' :

					// Generate JSON
					try {

						// Set model preferences
						self::set_model_preferences($prompt, $this->models_text, 'text');

						// Set temperature
						$prompt->using_temperature($this->temperature);

						// Set candidate count
						$prompt->using_candidate_count(1);

						// Check prompt supports text generation
						if($prompt->is_supported_for_text_generation()) {

							// Generate result
							$result = $prompt->generate_text_result();

							// Get texts
							$texts = $result->toTexts();

							// Process texts
							foreach($texts as $text) {

								$this->output = wp_kses_post($text);
							}

							// Process result
							self::process_result($result);

						} else {

							self::error(__('To generate text with AI, you’ll need to connect an AI provider first. Add your credentials in the WordPress AI settings that supports text generation.', 'ws-form'));

							return true;
						}

					} catch(Exception $e) {

						self::error($e->getMessage());

						return true;
					}

					// Filter output
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
					$this->output = apply_filters('wsf_action_' . $this->id . '_output', $this->output, $this->type, $form, $submit, $config);

					// Post handler
					self::post_handler_text($form, $submit);

					// Success
					parent::success(__('Successfully processed AI generate text request.', 'ws-form'), array(

						array(

							'action' => 'field_value',
							'field_id' => absint($this->output_field),
							'value' => $this->output,
							'append' => $this->output_append
						)
					));

					return true;

				case 'image' :
				case 'speech' :

					try {

						switch($this->type) {

							case 'image' :

								// Set model preferences
								self::set_model_preferences($prompt, $this->models_image, 'image');

								// Set candidate count
								$prompt->using_candidate_count($this->image_count);

								// Check prompt supports image generation
								if($prompt->is_supported_for_image_generation()) {

									// Generate result
									$result = $prompt->generate_image_result();

								} else {

									self::error(__('To generate images with AI, you’ll need to connect an AI provider first. Add your credentials in the WordPress AI settings that supports text generation.', 'ws-form'));

									return true;
								}

								break;

							case 'speech' :

								// Set model preferences
								self::set_model_preferences($prompt, $this->models_speech, 'speech');

								// Set candidate count
								$prompt->using_candidate_count(1);

								// Check prompt supports speech generation
								if($prompt->is_supported_for_speech_generation() || true) {

									// Generate result
									$result = $prompt->generate_speech_result();

								} else {

									self::error(__('To generate speech with AI, you’ll need to connect an AI provider first. Add your credentials in the WordPress AI settings that supports speech generation.', 'ws-form'));

									return true;
								}

								break;
						}

					} catch(Exception $e) {

						self::error($e->getMessage());

						return true;
					}

					// API Field
					$ws_form_api_field = new WS_Form_API_Field();

					// Get form ID
					$form_id = $form->id;

					// Get field ID
					$field_id = absint($this->output_field);

					// Get field
					$field = $ws_form_api_field->api_field_get($form_id, $field_id);

					// Get field type
					$field_type = $field->type;

					// Process return by field type
					switch($field_type) {

						case 'file' :

							require_once(ABSPATH . 'wp-admin/includes/file.php');
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							require_once(ABSPATH . 'wp-admin/includes/media.php');

							// Size
							$file_min_size = floatval(WS_Form_Common::get_object_meta_value($field, 'file_min_size', 'wsform'));
							if($file_min_size > 0) { $file_min_size = ($file_min_size * 1048576); }
							$file_max_size = floatval(WS_Form_Common::get_object_meta_value($field, 'file_max_size', 'wsform'));
							if($file_max_size > 0) { $file_max_size = ($file_max_size * 1048576); }

							// Upload path
							$upload_path = WS_FORM_UPLOAD_DIR . '/' . $form_id . '/dropzonejs/' . $field_id;

							// Get files
							$files = $result->toFiles();

							// Process files
							$file_index = 0;
							foreach($files as $file) {

								// Get output
								$this->output = $file->getDataUri();

								// Filter output
								// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
								$this->output = apply_filters('wsf_action_' . $this->id . '_output', $this->output, $this->type, $form, $submit, $config);

								$file_data = '';
								$file_type = '';
								$file_size = 0;
								$file_name = '';

								// Build file id
								$file_id = sprintf('field_%u_%u', $field_id, $file_index);

								// Process data URI
								preg_match('/^data:([^;]+);base64,(.+)$/', $this->output, $matches);
								$file_type = trim($matches[1]);
								$base64_data = $matches[2];

								// Base64 decode (strict)
								$file_data = base64_decode($base64_data, true);

								if($file_data === false || $file_data === '') {

									self::error(__('Unable to decode data URI.', 'ws-form'));
									return true;
								}

								$file_size = strlen($file_data);

								// Pick extension from mime
								$ext = '';
								if(function_exists('wp_get_mime_types')) {

									$mime_types = wp_get_mime_types();

									foreach($mime_types as $exts => $mime) {
										if($mime === $file_type) {
											$ext_parts = explode('|', $exts);
											$ext = reset($ext_parts);
											break;
										}
									}
								}

								if($ext === '') {

									// Fallbacks
									if($file_type === 'image/png') { $ext = 'png'; }
									else if($file_type === 'image/jpeg') { $ext = 'jpg'; }
									else if($file_type === 'image/gif') { $ext = 'gif'; }
									else if($file_type === 'image/webp') { $ext = 'webp'; }
									else { $ext = 'bin'; }
								}

								$file_name = sprintf(

									'ai_%s_%u.%s',
									$this->type,
									($file_index + 1),
									$ext
								);

								// Enforce min/max sizes if configured
								if($file_min_size > 0 && $file_size < $file_min_size) {

									self::error(sprintf(

										/* translators: %s: File name */
										__('File is smaller than the minimum allowed size: %s', 'ws-form'),
										$file_name
									));

									return true;
								}

								if($file_max_size > 0 && $file_size > $file_max_size) {

									self::error(sprintf(

										/* translators: %s: File name */
										__('File is larger than the maximum allowed size: %s', 'ws-form'),
										$file_name
									));

									return true;
								}

								// Write file data to temporary file
								$tmp_name = wp_tempnam($file_name);
								if(!$tmp_name) {

									self::error(__('Unable to create temporary file.', 'ws-form'));
									return true;
								}

								// Initialize WP_Filesystem
								global $wp_filesystem;
								if(empty($wp_filesystem)) {
									require_once ABSPATH . 'wp-admin/includes/file.php';
									WP_Filesystem();
								}

								if(!$wp_filesystem->put_contents($tmp_name, $file_data, FS_CHMOD_FILE)) {
									self::error(__('Unable to write temporary file.', 'ws-form'));
									return true;
								}

								// Build fake file upload
								$_FILES[$file_id] = array(
									'name' => $file_name,
									'type' => $file_type,
									'tmp_name' => $tmp_name,
									'error' => 0,
									'size' => $file_size
								);

								try {

									WS_Form_File_Handler::dropzonejs_upload($file_id, $attachment_ids, $upload_path, $field, $file_name, $file_type, $file_size, $tmp_name);

								} catch (Exception $e) {

									self::error($e->getMessage());
									return true;
								}

								$file_index++;
							}

							// Build file objects
							$file_objects = array();

							foreach($attachment_ids as $attachment_id) {

								$file_objects[] = WS_Form_File_Handler::get_file_object_from_attachment_id($attachment_id);
								update_post_meta($attachment_id, '_wsf_attachment', true);
								update_post_meta($attachment_id, '_wsf_attachment_scratch', true);
							}

							// Process result
							self::process_result($result);

							// Success
							parent::success(

								sprintf(

									/* translators: %s: AI type */
									__('Successfully processed AI generate %s request.', 'ws-form'),
									$this->input_type

								),

								array(

									array(

										'action' => 'field_dropzonejs_file_objects',
										'field_id' => $field_id,
										'file_objects' => $file_objects
									)
								)
							);

							break;

						default :

							// Get files
							$files = $result->toFiles();

							// Process files
							$file_index = 0;
							foreach($files as $files) {

								// Get output
								$this->output = $file->getUrl();

								// Filter output
								// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
								$this->output = apply_filters('wsf_action_' . $this->id . '_output', $this->output, $this->type, $form, $submit, $config);
							}

							// Process text return
							self::post_handler_text($form, $submit);

							// Success
							parent::success(

								sprintf(

									/* translators: %s: AI type */
									__('Successfully processed AI generate %s request.', 'ws-form'),
									$this->input_type

								),

								array(

									array(

										'action' => 'field_value',
										'field_id' => absint($this->output_field),
										'value' => $this->output,
										'append' => $this->output_append
									)
								)
							);
					}

					return true;
			}
		}

		// Process result
		public function process_result($result) {

			// Get token usage
			$token_usage = $result->getTokenUsage();

			// Get prompt tokens
			$this->prompt_tokens = $token_usage->getPromptTokens();

			// Show prompt tokens in debug console
			parent::success(

				sprintf(

					/* translators: %u: Prompt tokens */
					__('Prompt tokens: %u', 'ws-form'),
					$this->prompt_tokens
				)
			);

			// Get completion tokens
			$this->completion_tokens = $token_usage->getCompletionTokens();

			// Show competion tokens in debug console
			parent::success(

				sprintf(

					/* translators: %u: Completion tokens */
					__('Completion tokens: %u', 'ws-form'),
					$this->completion_tokens
				)
			);

			// Get total tokens
			$this->total_tokens = $token_usage->getTotalTokens();

			// Show total tokens in debug console
			parent::success(

				sprintf(

					/* translators: %u: Total tokens */
					__('Total tokens: %u', 'ws-form'),
					$this->total_tokens
				)
			);
		}

		// Set model preferences
		public function set_model_preferences(&$prompt, $provider_models, $meta_key_suffix) {

			if(count($provider_models) == 0) { return; }

			$model_preferences = array();

			foreach($provider_models as $provider_model_row) {

				$meta_key = 'action_' . $this->id . '_model_' . $meta_key_suffix;

				if(!isset($provider_model_row[$meta_key])) { continue; }

				$provider_model = $provider_model_row[$meta_key];
				if(empty($provider_model)) { continue; }

				$provider_model_array = explode(',', $provider_model);
				if(count($provider_model_array) != 2) { continue; }

				$model_preferences[] = $provider_model_array;

				// Show model preference in debug console
				parent::success(

					sprintf(

						/* translators: %u: Provider and model */
						__('Set model preference: %s', 'ws-form'),
						esc_html(implode(': ', $provider_model_array))
					)
				);
			}

			if(count($model_preferences) > 0) {

				$prompt->usingModelPreference(...$model_preferences);
			}
		}

		// Timeout
		public function wp_ai_client_default_request_timeout($timeout) {

			// Show timeout in debug console
			parent::success(

				sprintf(

					/* translators: %u: Timeout in seconds */
					__('Set timeout: %u', 'ws-form'),
					$this->timeout
				)
			);

			return $this->timeout;
		}

		// Post handler - Text
		public function post_handler_text($form, $submit) {

			// Trim?
			if($this->output_trim) { $this->output = trim($this->output); }

			// wpautop
			if($this->output_wpautop) {

				$this->output = wpautop($this->output);
			}

			// Mask output
			if($this->output_mask_use) {

				$output_lookups = array(

					'output' => $this->output
				);

				if($this->output_mask == '') { $this->output_mask = '#output'; }

				$output_mask = WS_Form_Common::mask_parse($this->output_mask, $output_lookups);

				$this->output = WS_Form_Common::parse_variables_process($output_mask, $form, $submit, 'text/plain');
			}
		}

		public function load_config($config) {

			$this->type = parent::get_config($config, 'action_' . $this->id . '_type');
			if(!in_array($this->type, array('text', 'image', 'speech'))) { $this->type = 'text'; }

			$this->input_type = parent::get_config($config, 'action_' . $this->id . '_input_type', 'field');
			if(!in_array($this->input_type, array('field', 'mask'))) { $this->type = 'field'; }
			$this->input_field = absint(parent::get_config($config, 'action_' . $this->id . '_input_field', ''));
			$this->input_mask = parent::get_config($config, 'action_' . $this->id . '_input_mask', '');

			$this->output_field = absint(parent::get_config($config, 'action_' . $this->id . '_output_field', ''));
			$this->output_mask = parent::get_config($config, 'action_' . $this->id . '_output_mask', '#output');
			$this->output_mask_use = (parent::get_config($config, 'action_' . $this->id . '_output_mask_use', '') == 'on');

			// Text generation
			$this->output_append = (parent::get_config($config, 'action_' . $this->id . '_output_append', '') === 'on');
			$this->output_trim = (parent::get_config($config, 'action_' . $this->id . '_output_trim', '') === 'on');
			$this->output_wpautop = (parent::get_config($config, 'action_' . $this->id . '_output_wpautop', '') === 'on');
			$this->temperature = floatval(parent::get_config($config, 'action_' . $this->id . '_temperature', '1'));
			if($this->temperature < 0) { $this->temperature = 0; }
			if($this->temperature > 1) { $this->temperature = 1; }
			$this->models_text = parent::get_config($config, 'action_' . $this->id . '_models_text', array());
			if(empty($this->models_text) || !is_array($this->models_text)) { $this->models_text = array(); }

			// Image generation
			$this->image_count = absint(parent::get_config($config, 'action_' . $this->id . '_image_count', '1'));
			$this->models_image = parent::get_config($config, 'action_' . $this->id . '_models_image', array());
			if(empty($this->models_image) || !is_array($this->models_image)) { $this->models_image = array(); }

			// Speech generation
			$this->models_speech = parent::get_config($config, 'action_' . $this->id . '_models_speech', array());
			if(empty($this->models_speech) || !is_array($this->models_speech)) { $this->models_speech = array(); }

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_ai_client_default_request_timeout third party filter
			$this->timeout = absint(parent::get_config($config, 'action_' . $this->id . '_timeout', absint(apply_filters('wp_ai_client_default_request_timeout', 30))));
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_type',

					'action_' . $this->id . '_input_type',
					'action_' . $this->id . '_input_field',
					'action_' . $this->id . '_input_mask',

					'action_' . $this->id . '_output_field',
					'action_' . $this->id . '_output_mask_use',
					'action_' . $this->id . '_output_mask',

					// Text generation
					'action_' . $this->id . '_output_append',
					'action_' . $this->id . '_output_trim',
					'action_' . $this->id . '_output_wpautop',
					'action_' . $this->id . '_temperature',
					'action_' . $this->id . '_models_text',

					// Image generation
					'action_' . $this->id . '_image_count',
					'action_' . $this->id . '_models_image',

					// Speech generation
					'action_' . $this->id . '_models_speech',

					'action_' . $this->id . '_timeout',
				)
			);

			// Wrap settings so they will work with sidebar_html function in admin.js
			$settings = parent::get_settings_wrapper($settings);

			// Add labels
			$settings->label = $this->label;
			$settings->label_action = $this->label_action;

			// Add multiple
			$settings->multiple = $this->multiple;

			// Add events
			$settings->events = $this->events;

			// Add can_repost
			$settings->can_repost = $this->can_repost;

			// Apply filter
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$settings = apply_filters('wsf_action_wp_ai_client_settings', $settings);

			return $settings;
		}

		// Check action is configured properly
		public function check_configured() {

			return WS_Form_Common::abilities_api_enabled() &&
			WS_Form_Common::wp_ai_client_enabled();
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys = array(

				// Type
				'action_' . $this->id . '_type'	=> array(

					'label'				=>	__('Type', 'ws-form'),
					'type'				=>	'select',
					'options'			=>	array(
						array('value' => 'text', 'text' => __('Text', 'ws-form')),
						array('value' => 'image', 'text' => __('Image', 'ws-form')),
//						array('value' => 'speech', 'text' => __('Speech', 'ws-form'))
					),
					'help'				=>	__('Choose which type of content to generate.', 'ws-form'),
					'default'			=>	'text'
				),

				// Input Type
				'action_' . $this->id . '_input_type'	=> array(

					'label'							=>	__('Prompt Type', 'ws-form'),
					'type'							=>	'select',
					'help'							=>	__('Choose how to provide an input.', 'ws-form'),
					'options'						=>	array(

						array('value' => 'field', 'text' => __('Field', 'ws-form')),
						array('value' => 'mask', 'text' => __('Mask', 'ws-form'))
					),
					'default'						=>	'field'
				),

				// Field - Input
				'action_' . $this->id . '_input_field'	=> array(

					'label'						=>	__('Prompt Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_attribute'	=>	array('submit_save'),
					'help'						=>	__('Choose which field to use as the input.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_input_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'field'
						)
					)
				),

				// Mask input
				'action_' . $this->id . '_input_mask'	=> array(

					'label'						=>	__('Prompt Mask', 'ws-form'),
					'type'						=>	'html_editor',
					'placeholder'				=>	__('e.g. #field(123)', 'ws-form'),
					'help'						=>	__('Use the input mask to create prompts that include WS Form variables.', 'ws-form'),
					'select_list'				=>	true,
					'rows'						=>	5,
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_input_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'mask'
						)
					)
				),

				// Output - Field
				'action_' . $this->id . '_output_field'	=> array(

					'label'						=>	__('Output Field', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	'fields',
					'options_blank'				=>	__('Select...', 'ws-form'),
					'fields_filter_mappable'	=>	false,
					'fields_filter_attribute'	=>	array('html_in', 'submit_save')
				),

				// Output - Use mask
				'action_' . $this->id . '_output_mask_use'	=> array(

					'label'						=>	__('Use Output Mask', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'logic_previous'	=>	'||',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'text'
						)
					)
				),

				// Output mask
				'action_' . $this->id . '_output_mask'	=> array(

					'label'						=>	__('Output Mask', 'ws-form'),
					'type'						=>	'html_editor',
					'placeholder'				=>	__('#output', 'ws-form'),
					'help'						=>	__('Create an output mask containing WS Form variables. Use #output to insert the AI output. Does not apply if the output field is a file field.', 'ws-form'),
					'select_list'				=>	true,
					'default'					=>	'#output',
					'rows'						=>	5,
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'text'
						),

						array(

							'logic'				=>	'==',
							'logic_previous'	=>	'&&',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_output_mask_use',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'on'
						)
					)
				),

				// Completion append
				'action_' . $this->id . '_output_append'	=> array(

					'label'						=>	__('Append Output', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('If checked, the AI output will be appended to the existing content in the output field.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'text'
						)
					)
				),

				// Output - Trim
				'action_' . $this->id . '_output_trim'	=> array(

					'label'						=>	__('Trim Output', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'on',
					'help'						=>	__('If checked, the output will be trimmed.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'text'
						)
					)
				),

				// Output - wpautop
				'action_' . $this->id . '_output_wpautop'	=> array(

					'label'						=>	__('Apply WPAutoP to Output', 'ws-form'),
					'type'						=>	'checkbox',
					'default'					=>	'',
					'help'						=>	__('If checked, wpautop will be applied to the output.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'logic_previous'	=>	'||',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'text'
						)
					)
				),

				// Image - Count
				'action_' . $this->id . '_image_count'	=> array(

					'label'						=>	__('Image count', 'ws-form'),
					'type'						=>	'range',
					'min'						=>	1,
					'max'						=>	10,
					'default'					=>	1,
					'help'						=>	__('Specify how many images you would like to create.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'image'
						)
					)
				),

				// Models - Text
				'action_' . $this->id . '_models_text'	=> array(

					'label'				=>	__('Model Preference', 'ws-form'),
					'type'				=>	'repeater',
					'meta_keys'			=>	array(

						'action_' . $this->id . '_model_text'
					),
					'help'				=>	__('Choose your preferred provider and models for this request. Leave blank for automatic.', 'ws-form'),
					'condition'			=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'text'
						)
					)
				),

				// Model - Text
				'action_' . $this->id . '_model_text'	=> array(

					'label'				=>	__('Model', 'ws-form'),
					'type'				=>	'select',
					'options'			=>	self::options_model_text()
				),

				// Models - Image
				'action_' . $this->id . '_models_image'	=> array(

					'label'				=>	__('Model Preference', 'ws-form'),
					'type'				=>	'repeater',
					'meta_keys'			=>	array(

						'action_' . $this->id . '_model_image'
					),
					'help'				=>	__('Choose your preferred provider and models for this request. Leave blank for automatic.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'image'
						)
					)
				),

				// Model - Image
				'action_' . $this->id . '_model_image'	=> array(

					'label'				=>	__('Model', 'ws-form'),
					'type'				=>	'select',
					'options'			=>	self::options_model_image()
				),

				// Models - Speech
				'action_' . $this->id . '_models_speech'	=> array(

					'label'				=>	__('Model Preference', 'ws-form'),
					'type'				=>	'repeater',
					'meta_keys'			=>	array(

						'action_' . $this->id . '_model_speech'
					),
					'help'				=>	__('Choose your preferred provider and models for this request. Leave blank for automatic.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'		=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'	=>	'speech'
						)
					)
				),

				// Model - Speech
				'action_' . $this->id . '_model_speech'	=> array(

					'label'				=>	__('Model', 'ws-form'),
					'type'				=>	'select',
					'options'			=>	self::options_model_speech()
				),

				// Temperature
				'action_' . $this->id . '_temperature'	=> array(

					'label'						=>	__('Temperature', 'ws-form'),
					'type'						=>	'range',
					'min'						=>	0,
					'max'						=>	1,
					'step'						=>	0.01,
					'default'					=>	1,
					'help'						=>	__('Controls randomness: Lowering results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_key'			=>	'action_' . $this->id . '_type',
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_value'		=>	'text'
						)
					)
				),

				// Timeout
				'action_' . $this->id . '_timeout'	=> array(

					'label'						=>	__('API Request Timeout (Seconds)', 'ws-form'),
					'type'						=>	'number',
					'default'					=>	'',

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_ai_client_default_request_timeout third party filter
					'placeholder'				=>	absint(apply_filters('wp_ai_client_default_request_timeout', 30))
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		public function get_provider_models($capability = array()) {

			// Get registry
			$registry = AiClient::defaultRegistry();

			// Define requirements
			$requirements = new ModelRequirements(
				$capability,
				array()
			);

			// Get providers with models
			$providers_with_models = $registry->findModelsMetadataForSupport($requirements);
			
			// Build return array
			$return_array = array();
			
			foreach ($providers_with_models as $provider_models) {

				$provider_id = $provider_models->getProvider()->getId();
				
				$model_ids = array();

				foreach ($provider_models->getModels() as $model) {

					$model_id = $model->getId();

					// Skip models with dates (e.g., gpt-4o-2024-08-06, claude-3-5-sonnet-20241022)
					if (preg_match('/\d{4}-?\d{2}-?\d{2}/', $model_id)) {
						continue;
					}

					// Skip models with short date suffixes (e.g., gpt-3.5-turbo-0125, gpt-3.5-turbo-1106)
					if (preg_match('/-\d{4}$/', $model_id)) {
						continue;
					}

					// Skip models with version timestamps (e.g., gemini-1.5-pro-001)
					if (preg_match('/-\d{3}$/', $model_id)) {
						continue;
					}

					// Skip preview/experimental models
					if (preg_match('/-(preview|experimental|beta|alpha)/i', $model_id)) {
						continue;
					}

					$model_ids[] = sanitize_text_field($model_id);
				}
				
				$return_array[sanitize_text_field($provider_id)] = $model_ids;
			}

			return $return_array;
		}

		public function options_model_text() {

			return self::options_model(

				self::get_provider_models(array(

					CapabilityEnum::textGeneration()
				))
			);
		}

		public function options_model_image() {

			return self::options_model(

				self::get_provider_models(array(

					CapabilityEnum::imageGeneration()
				))
			);
		}

		public function options_model_speech() {

			return self::options_model(

				self::get_provider_models(array(

					CapabilityEnum::speechGeneration()
				))
			);
		}

		public function options_model($provider_models) {

			$options = array();

			foreach($provider_models as $provider => $models) {

				switch($provider) {
						
					case 'openai' :

						$provider_nice = 'OpenAI';
						break;

					default :

						$provider_nice = ucfirst($provider);
				}

				foreach($models as $model) {

					$options[] = array(

						'value' => sprintf(

							'%s,%s',
							$provider,
							$model
						),

						'text' => sprintf(

							'%s: %s',
							$provider_nice,
							$model
						)
					);
				}
			}

			return $options;
		}
	}

	new WS_Form_Action_WP_AI_Client();
