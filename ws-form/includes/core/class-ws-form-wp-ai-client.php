<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use WordPress\AI_Client\AI_Client;

	class WS_Form_WP_AI_Client {

		public function __construct() {

			// Initialize
			AI_Client::init();

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

			$ws_form_form_ai = new WS_Form_Form_AI();

			// Get description
			$description = sanitize_text_field(WS_Form_Common::get_query_var_nonce('ai_prompt'));

			// Build prompt
			$prompt = sprintf(

				/* translators: %s: AI prompt describing the required form */
				__('Return a JSON string for a form for the following description: %s', 'ws-form'),
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
				$prompt = AI_Client::prompt($prompt);

				// Set model preferences
				$prompt->using_model_preference(

					array('anthropic', 'claude-sonnet-4-5'),
					array('google', 'gemini-2.5-flash'),
					array('openai', 'gpt-4.1')
				);

				// Set temperature
				$prompt->usingTemperature(1);

				// Check prompt supports text generation
				if($prompt->is_supported_for_text_generation()) {

					// Generate JSON
					$json = $prompt->generate_text();

				} else {

					throw new ErrorException(__('To create a form with AI, you’ll need to connect an AI provider first. Add your credentials in the WordPress AI settings that supports text generation.', 'ws-form'));
				}

			} catch(Exception $e) {

				throw new ErrorException(esc_html($e->getMessage()));
			}

			// Run the form-create-json ability
			$ability_name = 'ws-form/form-create-json';

			// Get ability
			$ability = wp_get_ability($ability_name);

			if($ability) {

				// Build ability input
			    $input = array(

			        'json' => $json
			    );

			    // Execute ability
			    $result = $ability->execute($input);

			    // Check for errors
			    if (is_wp_error($result)) {

					throw new ErrorException(esc_html($result->get_error_message()));

			    } else {

			        // Use $result
			        if(isset($result['id'])) {

			        	return array(

			        		'form_id' => absint($result['id'])
			        	);
			        }
			    }

			} else {

				throw new ErrorException(sprintf(

					/* translators: %s: Ability name */
					esc_html__('%s ability unavailable', 'ws-form'),
					esc_html($ability_name)
				));
			}
		}
	}

	new WS_Form_WP_AI_Client();