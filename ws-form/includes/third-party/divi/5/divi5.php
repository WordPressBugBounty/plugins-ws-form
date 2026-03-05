<?php
/**
 * WS Form — Divi 5 module integration.
 *
 * Loaded by WS Form Pro when Divi 5 is active.
 * Path: <plugin_root>/includes/third-party/divi5/divi5.php
 *
 * @package WS_Form
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Paths used throughout this integration.
define( 'WS_FORM_DIVI5_PATH', plugin_dir_path( __FILE__ ) );
define( 'WS_FORM_DIVI5_URL', plugin_dir_url( __FILE__ ) );

/**
 * module.json files are copied here by webpack CopyWebpackPlugin.
 * PHP ModuleRegistration reads from this directory.
 */
define( 'WS_FORM_DIVI5_JSON_PATH', WS_FORM_DIVI5_PATH . 'modules-json/' );

// Composer PSR-4 autoloader for WS_Form\Modules\ namespace.
require_once WS_FORM_DIVI5_PATH . 'vendor/autoload.php';

// Register the module with Divi 5's dependency tree.
require_once WS_FORM_DIVI5_PATH . 'modules/Modules.php';

/**
 * Get published WS Form forms for the Visual Builder form selector.
 *
 * Returns a plain id => label map so JSON encodes as an object, not an array.
 *
 * @return object
 */
function ws_form_divi5_get_forms() {
	$forms = wsf_form_get_all_key_value( false );

	if ( empty( $forms ) ) {
		return (object) [];
	}

	$options = [];
	foreach ( $forms as $id => $label ) {
		$options[ (string) $id ] = $label;
	}

	return (object) $options;
}

/**
 * Check a form ID exists and is published.
 *
 * @param int $form_id Form ID.
 * @return bool
 */
function ws_form_divi5_form_exists( $form_id ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQueryUse,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$result = $wpdb->get_var($wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}wsf_form WHERE id = %d LIMIT 1",
		$form_id
	));

	return ! empty( $result );
}

/**
 * Register the WS Form Divi 5 REST API endpoint.
 *
 * Returns rendered form HTML for the Visual Builder.
 * Restricted to users who can edit posts.
 */
function ws_form_divi5_register_rest_routes() {
	if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
		return;
	}

	register_rest_route(
		'wsform/v1',
		'/third-party/divi/5/form-shortcode/(?P<id>\d+)',
		[
			'methods'             => 'GET',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
			'callback'            => static function ( WP_REST_Request $request ) {
				$form_id = absint( $request->get_param( 'id' ) );

				if ( ! ws_form_divi5_form_exists( $form_id ) ) {
					return new WP_Error( 'invalid_form', 'Form not found.', [ 'status' => 404 ] );
				}

				$html = do_shortcode( sprintf( '[%s id="%u" visual_builder="true"]', WS_FORM_SHORTCODE, $form_id ) );

				return rest_ensure_response( [ 'html' => $html ] );
			},
		]
	);
}
add_action( 'rest_api_init', 'ws_form_divi5_register_rest_routes' );

/**
 * Enqueue Divi 5 Visual Builder assets (bundle.js + bundle.css).
 *
 * Fires before Divi enqueues its own VB scripts so our bundle is available.
 */
function ws_form_divi5_enqueue_vb_scripts() {
	if ( et_builder_d5_enabled() && et_core_is_fb_enabled() ) {
		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'ws-form-divi5-builder-bundle-script',
				'version' => WS_FORM_VERSION,
				'script'  => [
					'src'                => WS_FORM_DIVI5_URL . 'scripts/bundle.js',
					'deps'               => [
						'divi-module-library',
						'divi-vendor-wp-hooks',
					],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		// Pass form list to JS via a dedicated inline script handle.
		wp_register_script( 'ws-form-divi5-data', false, [], WS_FORM_VERSION, false );
		wp_enqueue_script( 'ws-form-divi5-data' );
		wp_add_inline_script(
			'ws-form-divi5-data',
			'window.wsFormDivi5 = ' . wp_json_encode( [ 'forms' => ws_form_divi5_get_forms() ] ) . ';'
		);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'ws-form-divi5-vb-bundle-style',
				'version' => WS_FORM_VERSION,
				'style'   => [
					'src'                => WS_FORM_DIVI5_URL . 'styles/bundle.css',
					'deps'               => [],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		// Enqueue WS Form Visual Builder scripts and config.
		$ws_form_public = new WS_Form_Public();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
		do_action( 'wsf_enqueue_visual_builder' );
		$ws_form_public->wsf_form_json[0] = true;
		add_action( 'admin_footer', [ $ws_form_public, 'wp_footer' ] );
	}
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'ws_form_divi5_enqueue_vb_scripts' );