<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Angie_WS_Form {

	public function __construct() {

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function register_rest_routes() {

		// Get abilities
		$abilities = WS_Form_Config::get_abilities();

		// Register rest routes
		foreach($abilities as $id => $ability) {

			register_rest_route( 

				WS_FORM_RESTFUL_NAMESPACE,
				sprintf('/angie/%s/', $id),
				array(
					'methods' => 'POST',
					'callback' => $ability['execute_callback'],
					'permission_callback' => $ability['permission_callback'],
				)
			);
		}

		// Register endpoint for retrieving abilities
		register_rest_route(

			WS_FORM_RESTFUL_NAMESPACE,
			'/angie/abilities/',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'api_abilities'),
				'permission_callback' => 'is_user_logged_in'
			)
		);
	}

	public function api_abilities() {

		// Get abilities
		$abilities = WS_Form_Config::get_abilities();

		$abilities_return = array();

		foreach($abilities as $id => $ability) {

			$abilities_return[] = array(

				'id' => $id,
				'description' => $ability['description'],
				'input_schema' => $ability['input_schema'],
				'output_schema' => $ability['output_schema'],
			);
		}

		return $abilities_return;
	}

	public function enqueue_scripts() {

		wp_enqueue_script_module(
			'angie-ws-form-mcp-server',
			plugin_dir_url( __FILE__ ) . 'angie-ws-form-mcp-server.mjs',
			[],
			WS_FORM_VERSION,
			true
		);
	}
}

new Angie_WS_Form();
