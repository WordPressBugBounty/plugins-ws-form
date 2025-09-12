<?php

	class WS_Form_Ability {

		// Register abilities
		public function register() {

			// Get abilities
			$abilities = WS_Form_Config::get_abilities();

			// Register abilities
			foreach($abilities as $id => $ability) {

				wp_register_ability(

					// Ability
					sprintf(

						'%s/%s',
						WS_FORM_NAME,
						$id
					),

					// Args
					array(

						'label'               => $ability['label'],
						'description'         => $ability['description'],
						'input_schema'        => $ability['input_schema'],
						'output_schema'       => $ability['output_schema'],
						'execute_callback'    => $ability['execute_callback'],
						'permission_callback' => $ability['permission_callback']
					)
				);
			}
		}

		// Form - Create - Blank
		public function form_create_blank( $input ) {

			try {

				// Create instance
				$ws_form_form = new WS_Form_Form();

				// Create a new form
				$ws_form_form->db_create();

				// Get the form ID
				$form_id = $ws_form_form->id;

				// Return data
				return [

					'form_id' => $form_id,
					'url' => esc_url(WS_Form_Common::get_admin_url('ws-form-edit', $form_id))
				];

			} catch(Exception $e) {

				return new \WP_Error(

					'ws_form_error',
					sprintf(
						/* translators: %s = Error message */
						__('Error creating form: %s', 'ws-form'),
						$e->getMessage()
					),
					['status' => 400]
				);
			}
		}

		// Form - Shortcode
		public function form_shortcode( $input ) {

			try {

				// Parse input
				$input = self::input_parse($input);

				// Get form ID
				$form_id = absint($input['form_id'] ?? '');

				// Check form ID
				if($form_id == 0) {

					throw new Exception(esc_html__('Invalid form ID.', 'ws-form'));
				}

				// Return data
				return [

					'shortcode' => WS_Form_Common::shortcode($form_id)
				];

			} catch(Exception $e) {

				return new WP_Error(

					'ws_form_error',
					sprintf(
						/* translators: %s = Error message */
						__('Error generating shortcode: %s', 'ws-form'),
						$e->getMessage()
					),
					['status' => 400]
				);
			}
		}

		// Input parse
		public function input_parse($input) {

			// Check if input is WP_REST_Request
			if(is_a($input, 'WP_REST_Request')) {

				$input = $input->get_json_params();
			}

			return $input;
		}
	}
