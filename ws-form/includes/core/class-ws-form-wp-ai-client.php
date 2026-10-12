<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_WP_AI_Client {

		// Set true to dump JSON and ability results in Create from AI errors
		const DEBUG = false;

		public function __construct() {

			// Register AI config file for templates
			add_filter('wsf_template_form_config_files', array($this, 'wsf_template_form_config_files'), 10, 1);

			// Template handler
			add_filter('wsf_wp_ai_client_create', array($this, 'create'), 10, 1);
		}

		public function wsf_template_form_config_files($config_files) {

			$config_files[] = WS_FORM_PLUGIN_DIR_PATH . 'includes/templates/form/ai/config.json';

			return $config_files;
		}

		public function create($description = false) {

			$json = '';
			$debug_meta = array();
			$ws_form_form_ai = new WS_Form_Form_AI();

			// Get description
			$description = sanitize_text_field(WS_Form_Common::get_query_var_nonce('ai_prompt'));

			// Build prompt
			$prompt = sprintf(

				/* translators: %s: AI prompt describing the required form */
				__('Return only a minified JSON string for a form for the following description. Do not use markdown or code fences. %s', 'ws-form'),
				$description
			);

			// Add JSON format
			$prompt .= "\n\n" . $ws_form_form_ai->get_form_create_json_prompt();

			// Generate JSON
			try {

				// Change timeout
				add_filter('wp_ai_client_default_request_timeout', function($timeout) {

					return 120;

				}, 10, 1);

				// Initiate prompt
				// Use of wp_ai_client_prompt() (requires WordPress 7.0+) is already gated: this file is only loaded when wp_ai_client_enabled() confirms the function exists.
				// We must call it via call_user_func() because Plugin Check provides no way to ignore its WordPress version compatibility errors inline.
				$prompt = call_user_func('wp_ai_client_prompt', $prompt);

				// Set model preferences
				$prompt->using_model_preference(

					array('openai', 'gpt-4.1'),
					array('anthropic', 'claude-sonnet-4-5'),
					array('google', 'gemini-2.5-flash')
				);

				// Set temperature
				$prompt->using_temperature(1);

				$max_tokens = absint(WS_Form_Common::option_get('wp_ai_client_form_create_max_tokens', 32768, false, true, true));
				if($max_tokens < 1) { $max_tokens = 32768; }
				$prompt->using_max_tokens($max_tokens);

				// Check prompt supports text generation
				if($prompt->is_supported_for_text_generation()) {

					$result = $prompt->generate_text_result();

					if(is_wp_error($result)) {

						self::create_fail($result->get_error_message(), $json, $result, $debug_meta);
					}

					$candidates = (is_object($result) && method_exists($result, 'getCandidates')) ? $result->getCandidates() : array();

					if(
						!empty($candidates) &&
						is_object($candidates[0]) &&
						method_exists($candidates[0], 'getFinishReason')
					) {

						$finish_reason = $candidates[0]->getFinishReason();
						$debug_meta['finish_reason'] = self::finish_reason_label($finish_reason);

						if(
							is_object($finish_reason) &&
							method_exists($finish_reason, 'isLength') &&
							$finish_reason->isLength()
						) {

							self::create_fail(

								__('AI stopped because the response reached the maximum length. Try a simpler prompt, or use a different AI connector.', 'ws-form'),
								$json,
								$result,
								$debug_meta
							);
						}
					}

					if(
						is_object($result) &&
						method_exists($result, 'getTokenUsage')
					) {

						$debug_meta['token_usage'] = $result->getTokenUsage();
					}

					$json = (is_object($result) && method_exists($result, 'toText')) ? $result->toText() : '';

					if(is_array($json) || is_object($json)) {

						$json = wp_json_encode($json);
					}

					if(!is_string($json) || ($json === '')) {

						self::create_fail(

							__('Unable to create the form with AI. Please try again.', 'ws-form'),
							$json,
							$result,
							$debug_meta
						);
					}

				} else {

					throw new ErrorException(sprintf(

						'%s <a href="%s" target="_blank">%s</a>',
						__('To create a form with AI, you’ll need to connect an AI provider first.', 'ws-form'),
						WS_Form_Common::get_plugin_website_url('/knowledgebase/create-from-ai-template/', 'wp_ai_client'),
						__('Learn more', 'ws-form')
					));
				}

			} catch(Exception $e) {

				throw new ErrorException(wp_kses_post($e->getMessage()));
			}

			// Run the form-create-json ability
			$ability_name = 'ws-form/form-create-json';

			// Get ability
			// Use of wp_get_ability() (requires WordPress 6.9+) is already gated: this file is only loaded when abilities_api_enabled() confirms the Abilities API is present.
			// We must call it via call_user_func() because Plugin Check provides no way to ignore its WordPress version compatibility errors inline.
			$ability = call_user_func('wp_get_ability', $ability_name);

			if($ability) {

				// Build ability input
			    $input = array(

			        'json' => $json
			    );

			    // Execute ability
			    $result = $ability->execute($input);

			    // Check for errors
			    if (is_wp_error($result)) {

					self::create_fail($result->get_error_message(), $json, $result, $debug_meta);
			    }

		        if(isset($result['id']) && (absint($result['id']) > 0)) {

		        	return array(

		        		'form_id' => absint($result['id'])
		        	);
		        }

				$message = __('Unable to create the form with AI. Please try again.', 'ws-form');

				if(
					is_array($result) &&
					isset($result['error']['message']) &&
					is_string($result['error']['message']) &&
					($result['error']['message'] !== '')
				) {

					$message = $result['error']['message'];
				}

				self::create_fail($message, $json, $result, $debug_meta);

			} else {

				throw new ErrorException(sprintf(

					/* translators: %s: Ability name */
					esc_html__('%s ability unavailable', 'ws-form'),
					esc_html($ability_name)
				));
			}
		}

		public function finish_reason_label($finish_reason) {

			if(!is_object($finish_reason)) {

				return gettype($finish_reason);
			}

			if(method_exists($finish_reason, 'isLength') && $finish_reason->isLength()) { return 'length'; }
			if(method_exists($finish_reason, 'isStop') && $finish_reason->isStop()) { return 'stop'; }
			if(method_exists($finish_reason, 'isContentFilter') && $finish_reason->isContentFilter()) { return 'content_filter'; }
			if(method_exists($finish_reason, 'isError') && $finish_reason->isError()) { return 'error'; }
			if(method_exists($finish_reason, 'isToolCalls') && $finish_reason->isToolCalls()) { return 'tool_calls'; }

			return get_class($finish_reason);
		}

		public function create_fail($message, $json, $result = null, $debug_meta = array()) {

			if(!self::DEBUG) {

				throw new ErrorException(esc_html($message));
			}

			$debug = array();

			if(isset($debug_meta['finish_reason'])) {

				$debug[] = 'Finish reason: ' . $debug_meta['finish_reason'];
			}

			if(isset($debug_meta['token_usage'])) {

				$debug[] = 'Token usage: ' . substr((string) wp_json_encode($debug_meta['token_usage']), 0, 800);
			}

			$debug[] = 'JSON type: ' . gettype($json);

			if(is_string($json)) {

				$debug[] = 'JSON length: ' . strlen($json);

				if(strlen($json) <= 8000) {

					$debug[] = 'JSON: ' . $json;

				} else {

					$debug[] = 'JSON preview: ' . substr($json, 0, 800);
					$debug[] = 'JSON tail: ' . substr($json, -800);
				}

				json_decode($json);

				if(json_last_error() !== JSON_ERROR_NONE) {

					$debug[] = 'json_decode: ' . json_last_error_msg();
				}

			} elseif(is_bool($json)) {

				$debug[] = 'JSON value: ' . ($json ? 'true' : 'false');

			} elseif(is_null($json)) {

				$debug[] = 'JSON value: null';

			} else {

				$encoded = wp_json_encode($json);
				$debug[] = 'JSON encoded length: ' . (is_string($encoded) ? strlen($encoded) : 0);
				$debug[] = 'JSON preview: ' . substr((string) $encoded, 0, 800);
			}

			if(is_wp_error($result)) {

				$debug[] = 'WP_Error code: ' . $result->get_error_code();
				$debug[] = 'WP_Error message: ' . $result->get_error_message();

				$error_data = $result->get_error_data();

				if($error_data !== null) {

					$debug[] = 'WP_Error data: ' . substr((string) wp_json_encode($error_data), 0, 800);
				}

			} elseif($result !== null) {

				$debug[] = 'Result type: ' . gettype($result);
				$debug[] = 'Result keys: ' . (is_array($result) ? implode(', ', array_keys($result)) : '');
				$debug[] = 'Result: ' . substr((string) wp_json_encode($result), 0, 1200);
			}

			throw new ErrorException(

				esc_html($message) . "\n\n" . esc_html(implode("\n", $debug))
			);
		}
	}

	new WS_Form_WP_AI_Client();