<?php

	class WS_Form_Config_Ability {

		// Configuration - Abilities
		public static function get_abilities() {

			// Abilities
			$abilities = array(

				// Form - Create - Blank
				'form-create-blank' => array(

					'label' => __('Creates a blank form', 'ws-form'),
					'description' => __('Creates a new blank / empty form in the WS Form form plugin for WordPress.', 'ws-form'),
					'thinking_message' => __('Creating a blank form!', 'ws-form'),
					'success_message' => __('Blank form created!', 'ws-form'),
					'permission_callback' => function() {

						return (

							current_user_can( 'create_form' ) &&
							current_user_can( 'edit_form' )
						);
					},
					'input_schema'  => [],
					'output_schema' => [

						'type' => 'object',

						'properties' => array(

							'form_id' => array(

								'type' => 'number',
								'description' => __('The newly created form ID', 'ws-form')
							),
						),
					],
					'execute_callback' => function( $input ) {

						$ws_form_ability = new WS_Form_Ability();
						return $ws_form_ability->form_create_blank($input);
					}
				),

				// Form - Shortcode
				'form-shortcode' => array(

					'label' => __('Gets a shortcode for a form by ID', 'ws-form'),
					'description' => __('Gets a shortcode for a form in the WS Form form plugin for WordPress by form ID.', 'ws-form'),
					'thinking_message' => __('Creating shortcode!', 'ws-form'),
					'success_message' => __('Shortcode created!', 'ws-form'),
					'permission_callback' => function() {

						return (

							current_user_can( 'read_form' )
						);
					},
					'input_schema'  => [

						'type' => 'object',

						'properties' => array(

							'form_id' => array(

								'type' => 'number',
								'description' => __('The form ID to create a shortcode for', 'ws-form')
							),
						),
					],
					'output_schema' => [

						'type' => 'object',

						'properties' => array(

							'shortcode' => array(

								'type' => 'string',
								'description' => __('The shortcode for the specified form ID', 'ws-form')
							),
						),
					],
					'execute_callback' => function( $input ) {

						$ws_form_ability = new WS_Form_Ability();
						return $ws_form_ability->form_shortcode($input);
					}
				)
			);

			return $abilities;
		}
	}
