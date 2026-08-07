<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class WS_Form_Config_Option extends WS_Form_Config {

		// Configuration - Options
		public static function get_options($process_options = true) {

			// File upload checks
			$upload_checks = WS_Form_Common::uploads_check();
			$max_upload_size = $upload_checks['max_upload_size'];
			$max_uploads = $upload_checks['max_uploads'];

			$options = array(

				// Basic
				'basic'		=> array(

					'label'		=>	__('Basic', 'ws-form'),
					'groups'	=>	array(

						'preview'	=>	array(

							'heading'		=>	__('Preview', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/previewing-forms/',
							'fields'	=>	array(

								'helper_live_preview'	=>	array(

									'label'		=>	__('Enable Live Preview', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Update the form preview window automatically.', 'ws-form'),
									'admin'		=>	true,
									'default'	=>	true,
								),

								'preview_template'	=> array(

									'label'				=>	__('Template', 'ws-form'),
									'type'				=>	'select',
									'help'				=>	__('Page template used for previewing forms.', 'ws-form'),
									'options'			=>	array(),	// Populated below
									'default'			=>	''
								)
							)
						),


						'layout_editor'	=>	array(

							'heading'	=>	__('Layout Editor', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/the-layout-editor/',
							'fields'	=>	array(

								'helper_columns'	=>	array(

									'label'		=>	__('Column Guidelines', 'ws-form'),
									'type'		=>	'select',
									'help'		=>	__('Show column guidelines when editing forms?', 'ws-form'),
									'options'	=>	array(

										'off'		=>	array('text' => __('Off', 'ws-form')),
										'resize'	=>	array('text' => __('On resize', 'ws-form')),
										'on'		=>	array('text' => __('Always on', 'ws-form')),
									),
									'default'	=>	'resize',
									'admin'		=>	true
								),

								'publish_auto'	=>	array(

									'label'		=>	__('Auto Publish', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, changes made to your form will be automatically published.', 'ws-form'),
									'default'	=>	false
								),

								'helper_breakpoint_width'	=>	array(

									'label'		=>	__('Breakpoint Widths', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Resize the width of the form to the selected breakpoint.', 'ws-form'),
									'default'	=>	true,
									'admin'		=>	true
								),

								'helper_compatibility' => array(

									'label'		=>	__('HTML Compatibility Helpers', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Show HTML compatibility helper links (Data from', 'ws-form') . ' <a href="' . WS_FORM_COMPATIBILITY_URL . '" target="_blank">' . WS_FORM_COMPATIBILITY_NAME . '</a>).',
									'default'	=>	true,
									'admin'		=>	true,
								),

								'helper_icon_tooltip' => array(

									'label'		=>	__('Icon Tooltips', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Show icon tooltips.', 'ws-form'),
									'default'	=>	true,
									'admin'		=>	true
								),

								'helper_field_help' => array(

									'label'		=>	__('Sidebar Help Text', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Show help text in sidebar.', 'ws-form'),
									'default'	=>	true,
									'admin'		=>	true
								),

								'helper_select2_on_mousedown'	=> array(

									'label'		=>	__('Searchable Sidebar Dropdowns', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If enabled, dropdown settings in the sidebar with 20 or more options will become searchable.', 'ws-form'),
									'default'	=>	false,
									'admin'		=>	true
								)
							)
						),

						'admin'	=>	array(

							'heading'	=>	__('Administration', 'ws-form'),
							'fields'	=>	array(

								'disable_count_submit_unread'	=>	array(

									'label'		=>	__('Disable Unread Submission Bubbles', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false
								),

								'disable_toolbar_menu'			=>	array(

									'label'		=>	__('Disable Toolbar Menu', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false,
									'help'		=>	sprintf(

										/* translators: %s: WS Form */
										__('If checked, the %s toolbar menu will not be shown.', 'ws-form'),

										WS_FORM_NAME_GENERIC
									)
								),

								'disable_translation'			=>	array(

									'label'		=>	__('Disable Translation', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false
								)
							)
						)
					)
				),

				// Advanced
				'advanced'	=> array(

					'label'		=>	__('Advanced', 'ws-form'),
					'groups'	=>	array(

						'performance'	=>	array(

							'heading'		=>	__('Performance', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/performance/',
							'fields'	=>	array(

								'enqueue_dynamic'	=>	array(

									'label'		=>	__('Dynamic Enqueuing', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should WS Form dynamically enqueue CSS and JavaScript components? (Recommended)', 'ws-form'),
									'default'	=>	true
								),
							),
						),

						'javascript'	=>	array(

							'heading'	=>	__('JavaScript', 'ws-form'),
							'fields'	=>	array(

								'js_defer'	=>	array(

									'label'		=>	__('Defer', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, scripts will be executed after the document has been parsed.', 'ws-form'),
									'default'	=>	''
								),

								'jquery_footer'	=>	array(

									'label'		=>	__('Enqueue in Footer', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, scripts will be enqueued in the footer.', 'ws-form'),
									'default'	=>	''
								),
							)
						),
						'cookie'	=>	array(

							'heading'	=>	__('Cookies', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/cookies/',
							'fields'	=>	array(

								'cookie_timeout'	=>	array(

									'label'		=>	__('Cookie Timeout (Seconds)', 'ws-form'),
									'type'		=>	'number',
									'help'		=>	__('Duration in seconds cookies are valid for.', 'ws-form'),
									'default'	=>	60 * 60 * 24 * 28,	// 28 day
									'public'	=>	true
								),

								'cookie_prefix'	=>	array(

									'label'		=>	__('Cookie Prefix', 'ws-form'),
									'type'		=>	'text',
									'help'		=>	__('We recommend leaving this value as it is.', 'ws-form'),
									'default'	=>	WS_FORM_IDENTIFIER,
									'public'	=>	true
								),

								'cookie_hash'	=>	array(

									'label'		=>	__('Enable Save Cookie', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked a cookie will be set when a form save button is clicked to later recall the form content.', 'ws-form'),
									'default'	=>	true,
									'public'	=>	true
								)
							)
						),

						'google'	=>	array(

							'heading'	=>	__('Google', 'ws-form'),
							'learn_more'	=>	'/knowledgebase_category/field-types-mapping/',
							'fields'	=>	array(

								'api_key_google_map'	=>	array(

									'label'		=>	__('API Key', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'',
									'help'		=>	sprintf('%s <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">%s</a>', __('Need an API key?', 'ws-form'), __('Learn more', 'ws-form')),
									'admin'		=>	true,
									'public'	=>	true
								),

								'google_maps_js_api_version'	=>	array(

									'label'		=>	__('API Version', 'ws-form'),
									'type'		=>	'select',
									'options'	=>	array(

										'' => array('text' => __('Places API (Legacy)', 'ws-form')),
										'2' => array('text' => __('Places API (New)', 'ws-form')),
									),
									'help'		=>	__('For Google accounts registered after March 1st, 2025, choose Places API (New).', 'ws-form'),
									'default'	=>	'',
									'public'	=>	true
								)
							)
						),

						'geo'	=>	array(

							'heading'	=>	__('Geolocation Lookup by IP', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/geolocation-lookup-by-ip/',
							'fields'	=>	array(

								'ip_lookup_method' => array(

									'label'		=>	__('Service', 'ws-form'),
									'type'		=>	'select',
									'options'	=>	array(

										'' => array('text' => __('geoplugin.com', 'ws-form')),
										'ipapi' => array('text' => __('ip-api.com', 'ws-form')),
										'ipapico' => array('text' => __('ipapi.co (Recommended)', 'ws-form')),
										'ipinfo' => array('text' => __('ipinfo.io', 'ws-form'))
									),
									'default'	=>	'ipapico'
								),

								'ip_lookup_geoplugin_key' => array(

									'label'		=>	__('geoplugin.com API Key', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'',
									'help'		=>	sprintf(

										'%s <a href="https://www.geoplugin.com" target="_blank">%s</a>',

										__('If you are using the commercial version of geoplugin.com, please enter your API key. Used for server-side tracking only.', 'ws-form'),
										__('Learn more', 'ws-form')
									)
								),

								'ip_lookup_ipapi_key' => array(

									'label'		=>	__('ip-api.com API Key', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'',
									'help'		=>	sprintf(

										'%s <a href="https://ip-api.com" target="_blank">%s</a>',

										__('If you are using the commercial version of ip-api.com, please enter your API key. Used for server-side tracking only.', 'ws-form'),
										__('Learn more', 'ws-form')
									)
								),

								'ip_lookup_ipapico_key' => array(

									'label'		=>	__('ipapi.co API Key', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'',
									'help'		=>	sprintf(

										'%s <a href="https://ipapi.co" target="_blank">%s</a>',

										__('If you are using the commercial version of ipapi.co, please enter your API key. Used for server-side tracking only.', 'ws-form'),
										__('Learn more', 'ws-form')
									)
								),

								'ip_lookup_ipinfo_key' => array(

									'label'		=>	__('ipinfo.io API Key', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'',
									'help'		=>	sprintf(

										'%s <a href="https://ipinfo.io" target="_blank">%s</a>',

										__('If you are using the commercial version of ipinfo.io, please enter your API key. Used for server-side tracking only.', 'ws-form'),
										__('Learn more', 'ws-form')
									)
								)
							)
						),

						'tracking'	=>	array(

							'heading'	=>	__('Tracking Links', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/tracking/',
							'fields'	=>	array(


								'ip_lookup_url_mask' => array(

									'label'		=>	__('URL Mask - IP Lookup', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'https://whatismyipaddress.com/ip/#value',
									'admin'		=>	true,
									'help'		=>	__('#value will be replaced with the tracking IP address.', 'ws-form')
								),

								'latlon_lookup_url_mask' => array(

									'label'		=>	__('URL Mask - Lat/Lon Lookup', 'ws-form'),
									'type'		=>	'text',
									'default'	=>	'https://www.google.com/maps/search/?api=1&query=#value',
									'admin'		=>	true,
									'help'		=>	__('#value will be replaced with latitude,longitude.', 'ws-form')
								)
							)
						),

					)
				),

				// Styling
				'styling'	=> array(

					'label'		=>	__('Styling', 'ws-form'),
					'groups'	=>	array(

						'markup'	=>	array(

							'heading'		=>	__('Markup', 'ws-form'),
							'fields'	=>	array(

								'framework'	=> array(

									'label'			=>	__('Framework', 'ws-form'),
									'type'			=>	'select',
									'help'			=>	__('Framework used for rendering the front-end HTML.', 'ws-form'),
									'options'		=>	array(),	// Populated below
									'default'		=>	WS_FORM_DEFAULT_FRAMEWORK,
									'button'		=>	'wsf-framework-detect',
									'admin'			=>	true,
									'public'		=>	true,
									'data_change'	=>	'reload'
								),

								'css_layout'	=>	array(

									'label'		=>	__('Layout CSS', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should the layout CSS be rendered?', 'ws-form'),
									'default'	=>	true,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								(WS_Form_Common::styler_enabled() ? 'css_style' : 'css_skin')	=>	array(

									'label'		=>	(WS_Form_Common::styler_enabled() ? __('Style CSS', 'ws-form') : __('Skin CSS', 'ws-form')),
									'type'		=>	'checkbox',
									'help'		=>	sprintf(

										'%s <a href="%s">%s</a>',
										__('Should the style CSS be rendered?', 'ws-form'),
										WS_Form_Common::styler_enabled() ? WS_Form_Common::get_admin_url('ws-form-style') : admin_url('customize.php?return=%2Fwp-admin%2Fadmin.php%3Fpage%3Dws-form-settings%26tab%3Dappearance'),
										WS_Form_Common::styler_enabled() ? __('View styles', 'ws-form') : __('Customize', 'ws-form')
									),
									'default'	=>	true,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'framework_column_count'	=> array(

									'label'		=>	__('Column Count', 'ws-form'),
									'type'		=>	'select_number',
									'default'	=>	12,
									'minimum'	=>	1,
									'maximum'	=>	24,
									'admin'		=>	true,
									'public'	=>	true,
									'absint'	=>	true,
									'help'		=>	__('We recommend leaving this setting at 12.', 'ws-form')
								),
							),
						),

						'performance'	=>	array(

							'heading'		=>	__('Performance', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/performance/',
							'fields'	=>	array(

								'css_compile'	=>	array(

									'label'		=>	__('Compile CSS', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should CSS be precompiled? (Recommended)', 'ws-form'),
									'default'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'css_inline'	=>	array(

									'label'		=>	__('Inline CSS', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('Should CSS be rendered inline? (Recommended)', 'ws-form'),
									'default'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),

								'css_cache_duration'	=>	array(

									'label'		=>	__('CSS Cache Duration', 'ws-form'),
									'type'		=>	'number',
									'help'		=>	__('Expires header duration in seconds for CSS.', 'ws-form'),
									'default'	=>	WS_FORM_CSS_CACHE_DURATION_DEFAULT,
									'public'	=>	true,
									'condition'	=>	array('framework' => 'ws-form')
								),
							)
						),
					),
				),

			);

			// AI
			if(
				WS_Form_Common::wp_ai_client_enabled() ||
				WS_Form_Common::abilities_api_enabled() ||
				WS_Form_Common::mcp_adapter_enabled(false) ||
				WS_Form_Common::angie_enabled(false)
			) {
				$options['ai'] = array(

					/* translators: AI is the abbreviation for "Artificial Intelligence" */
					'label'		=>	__('AI', 'ws-form'),

					'groups'	=> array()
				);
			}

			if(WS_Form_Common::wp_ai_client_enabled()) {

				$options['ai']['groups']['ai_connectors'] = array(

					'heading'	=>	__('AI Connectors', 'ws-form'),
					'learn_more'	=>	'/knowledgebase_category/ai/',

					'message'	=>	sprintf(

						/* translators: 1: Create from AI template link, 2: Make AI Request action link */
						__('AI connectors link WordPress to providers such as Anthropic, Google Gemini, or OpenAI. They are used by the %1$s template and %2$s action.', 'ws-form'),
						'<a href="' . esc_url(WS_Form_Common::get_plugin_website_url('/knowledgebase/create-from-ai-template/')) . '" target="_blank">' . esc_html__('Create from AI', 'ws-form') . '</a>',
						'<a href="' . esc_url(WS_Form_Common::get_plugin_website_url('/knowledgebase/make-ai-request-action/')) . '" target="_blank">' . esc_html__('Make AI Request', 'ws-form') . '</a>'
					),

					'fields'	=>	array(

						'ai_connectors_status'	=>	array(

							'label'		=>	__('Status', 'ws-form'),
							'type'		=>	'static',
							'button'	=>	'wsf-ai-connectors'
						)
					)
				);
			}

			if(
				WS_Form_Common::abilities_api_enabled() ||
				WS_Form_Common::angie_enabled(false)
			) {

				$abilities_api_fields = array(

					'mcp_adapter_public'	=>	array(

						'label'		=>	__('Include in MCP Discovery', 'ws-form'),
						'type'		=>	'checkbox',
						'default'	=>	true,
						'help'		=>	__('If enabled, MCP clients can discover WS Form abilities. Authentication and permissions still apply.', 'ws-form'),
						'admin'		=>	true,
					),

					'abilities_api_edit_form'	=>	array(

						'label'		=>	__('Allow Updates', 'ws-form'),
						'type'		=>	'checkbox',
						'default'	=>	false,
						'help'		=>	sprintf(

							'%s <strong>%s</strong>',

							WS_Form_Common::styler_enabled()
								? __('If enabled, form and style updates can be made by AI clients.', 'ws-form')
								: __('If enabled, form updates can be made by AI clients.', 'ws-form'),

							__('Use at your own risk!', 'ws-form')
						),
						'admin'		=>	true,
					),

					'abilities_api_delete_form'	=>	array(

						'label'		=>	__('Allow Deletes', 'ws-form'),
						'type'		=>	'checkbox',
						'default'	=>	false,
						'help'		=>	sprintf(

							'%s <strong>%s</strong>',

							WS_Form_Common::styler_enabled()
								? __('If enabled, form and style deletions can be made by AI clients.', 'ws-form')
								: __('If enabled, form deletions can be made by AI clients.', 'ws-form'),

							__('Use at your own risk!', 'ws-form')
						),
						'admin'		=>	true,
					)
				);

				$options['ai']['groups']['abilities_api'] = array(

					'heading'	=>	__('Abilities API', 'ws-form'),
					'learn_more'	=>	'/knowledgebase/abilities/',

					'message'	=>	esc_html__('Registers WS Form abilities with the WordPress Abilities API.', 'ws-form'),

					'fields'	=>	$abilities_api_fields
				);
			}

			if(WS_Form_Common::mcp_adapter_enabled(false)) {

				$options['ai']['groups']['mcp_adapter'] = array(

					'heading'	=>	__('MCP Adapter', 'ws-form'),
					'learn_more'	=>	'/knowledgebase/mcp-server/',

					'message'	=>	esc_html(sprintf(

						/* translators: %s: Presentable name (e.g. WS Form PRO) */
						__('Register a dedicated %s MCP server so authenticated AI clients can connect using the server URL below.', 'ws-form'),
						WS_FORM_NAME_PRESENTABLE
					)),

					'fields'	=>	array(

						'mcp_adapter'	=>	array(

							'label'		=>	__('Enable', 'ws-form'),
							'type'		=>	'checkbox',
							'admin'		=>	true,
						),

						'mcp_adapter_url'	=>	array(

							'label'		=>	__('Server URL', 'ws-form'),
							'type'		=>	'static'
						)
					)
				);
			}

			if(
				WS_Form_Common::angie_enabled(false)
			) {

				$options['ai']['groups']['angie'] = array(

					'heading'	=>	__('Angie', 'ws-form'),
					'learn_more'	=>	'/knowledgebase/angie/',

					'fields'	=>	array(

						'angie'	=>	array(

							'label'		=>	__('Enable', 'ws-form'),
							'type'		=>	'checkbox',
							'default'	=>	true,
							'help'		=>	sprintf(

								'%s<br><em>%s</em>',

								__('If enabled, WS Form abilities will be registered with Angie Agentic AI.', 'ws-form'),

								__('Experimental', 'ws-form')
							),
							'admin'		=>	true,
						)
					)
				);
			}

			$options = array_merge($options, array(

				// Spam Protection
				'spam_protection'	=> array(

					'label'		=>	__('Spam Protection', 'ws-form'),
					'groups'	=>	array(

						'recaptcha'	=>	array(

							'heading'	=> 'reCAPTCHA',
							'learn_more'	=>	'/knowledgebase/recaptcha/',
							'fields'	=>	array(

								'recaptcha_site_key' => array(

									'label'		=>	__('Site Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s site key.', 'ws-form'),
										'reCAPTCHA'
									)),
									'default'		=>	'',
									'admin'			=>	true,
									'public'		=>	true
								),

								'recaptcha_secret_key' => array(

									'label'		=>	__('Secret Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s secret key.', 'ws-form'),
										'reCAPTCHA'
									)),
									'default'		=>	'',
									'admin'			=>	true
								),

								// reCAPTCHA - Default type
								'recaptcha_recaptcha_type' => array(

									'label'						=>	__('Default reCAPTCHA Type', 'ws-form'),
									'type'						=>	'select',
									'help'						=>	__('Select the default type used for new reCAPTCHA fields.', 'ws-form'),
									'options'					=>	array(

										'v2_default' => array('text' => __('Version 2 - Default', 'ws-form')),
										'v2_invisible' => array('text' => __('Version 2 - Invisible', 'ws-form')),
										'v3_default' => array('text' => __('Version 3', 'ws-form')),
									),
									'default'					=>	'v2_default'
								)
							)
						),

						'hcaptcha'	=>	array(

							'heading'	=>	'hCaptcha',
							'learn_more'	=>	'/knowledgebase/hcaptcha/',
							'fields'	=>	array(

								'hcaptcha_site_key' => array(

									'label'		=>	__('Site Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s site key.', 'ws-form'),
										'hCaptcha'
									)),
									'default'		=>	'',
									'admin'			=>	true,
									'public'		=>	true
								),

								'hcaptcha_secret_key' => array(

									'label'		=>	__('Secret Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s secret key.', 'ws-form'),
										'hCaptcha'
									)),
									'default'		=>	'',
									'admin'			=>	true
								)
							)
						),

						'turnstile'	=>	array(

							'heading'	=>	'Turnstile',
							'learn_more'	=>	'/knowledgebase/turnstile/',
							'fields'	=>	array(

								'turnstile_site_key' => array(

									'label'		=>	__('Site Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s site key.', 'ws-form'),
										'Turnstile'
									)),
									'default'		=>	'',
									'admin'			=>	true,
									'public'		=>	true
								),

								'turnstile_secret_key' => array(

									'label'		=>	__('Secret Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s secret key.', 'ws-form'),
										'Turnstile'
									)),
									'default'		=>	'',
									'admin'			=>	true
								)
							)
						),

						'captchafox'	=>	array(

							'heading'	=>	'CaptchaFox',
							'learn_more'	=>	'/knowledgebase/captchafox/',
							'fields'	=>	array(

								'captchafox_site_key' => array(

									'label'		=>	__('Site Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s site key.', 'ws-form'),
										'CaptchaFox'
									)),
									'default'		=>	'',
									'admin'			=>	true,
									'public'		=>	true
								),

								'captchafox_secret_key' => array(

									'label'		=>	__('Secret Key', 'ws-form'),
									'type'		=>	'key',
									'help'		=>	esc_html(sprintf(

										/* translators: %s: Brand name */
										__('%s secret key.', 'ws-form'),
										'CaptchaFox'
									)),
									'default'		=>	'',
									'admin'			=>	true
								)
							)
						),

						'abuseipdb'	=>	array(

							'heading'	=>	'AbuseIPDB',
							'learn_more'	=>	'/knowledgebase/abuseipdb/',
							'fields'	=>	array(

								'abuseipdb_api_key' => array(

									'label'		=>	__('API Key', 'ws-form'),
									'type'		=>	'key',
									'constant'	=>	'WSF_ABUSEIPDB_API_KEY',
									'help'		=>	sprintf(
										'%s <a href="%s" target="_blank">%s</a>',
										esc_html__('AbuseIPDB API key.', 'ws-form'),
										esc_url('https://www.abuseipdb.com/register'),
										esc_html__('Get an API key', 'ws-form')
									),
									'default'	=>	'',
									'admin'		=>	true
								),

								'abuseipdb_all_forms' => array(

									'label'		=>	__('Enable On All Forms', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, AbuseIPDB checks run on every form. Otherwise enable it per form under Settings → Spam Protection.', 'ws-form'),
									'default'	=>	''
								),

								'abuseipdb_admin_no_run' => array(

									'label'		=>	__('Bypass If Administrator', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, this check will not run if you are signed in as an administrator.', 'ws-form'),
									'default'	=>	'on'
								),

								'abuseipdb_threshold' => array(

									'label'		=>	__('Abuse Confidence Score', 'ws-form'),
									'type'		=>	'number',
									'minimum'	=>	0,
									'maximum'	=>	100,
									'default'	=>	'',
									'allow_blank'	=>	true,
									'help'		=>	__('Optional. If set (0–100), submissions at or above this AbuseIPDB score are treated as spam on every form. If left blank, each form’s Spam Threshold setting is used instead.', 'ws-form')
								),

								'abuseipdb_max_age_days' => array(

									'label'		=>	__('Report Window (Days)', 'ws-form'),
									'type'		=>	'number',
									'minimum'	=>	1,
									'maximum'	=>	365,
									'default'	=>	30,
									'help'		=>	__('Only consider AbuseIPDB reports within this many days (1–365).', 'ws-form')
								),

								'abuseipdb_countries' => array(

									'label'		=>	__('Country Blocklist', 'ws-form'),
									'type'		=>	'select',
									'multiple'	=>	true,
									'select2'	=>	true,
									'options'	=>	array(),
									'help'		=>	__('Block submissions from these countries regardless of score.', 'ws-form'),
									'default'	=>	''
								),

								'abuseipdb_domains' => array(

									'label'		=>	__('Domain Blocklist', 'ws-form'),
									'type'		=>	'textarea',
									'help'		=>	__('Block submissions from IPs tied to these domains regardless of score. Separate with commas or new lines (e.g. example.com).', 'ws-form'),
									'default'	=>	''
								),

								'abuseipdb_report' => array(

									'label'		=>	__('Report Spam To AbuseIPDB', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	__('If checked, when a submission is marked as spam in WS Form the IP address is reported to AbuseIPDB as web spam.', 'ws-form'),
									'default'	=>	''
								)
							)
						),

						'nonce'	=>	array(

							'heading'	=>	__('NONCE', 'ws-form'),
							'learn_more'	=>	'/knowledgebase/using-nonces-to-protect-against-spam/',
							'fields'	=>	array(

								'security_nonce'	=>	array(

									'label'		=>	__('Enable NONCE', 'ws-form'),
									'type'		=>	'checkbox',
									'help'		=>	sprintf(

										'%s<br />%s',

										__('Add a NONCE to all form submissions.', 'ws-form'),
										__('If enabled we recommend keeping overall page caching to less than 10 hours.<br />NONCEs are always used on forms if a user is logged in.', 'ws-form')
									),
									'default'	=>	''
								)
							)
						)
					)
				),

				// System
				'system'	=> array(

					'label'		=>	__('System', 'ws-form'),
					'fields'	=>	array(

						'system' => array(

							'label'		=>	false,
							'type'		=>	'static',
							'class_row'	=>	'wsf-settings-system-report'
						),

						'setup'	=> array(

							'type'		=>	'hidden',
							'default'	=>	false
						)
					)
				),
				// Data
				'data'	=> array(

					'label'		=>	__('Data', 'ws-form'),
					'groups'	=>	array(

						'uninstall'	=>	array(

							'heading'	=>	__('Uninstall', 'ws-form'),
							'fields'	=>	array(

								'uninstall_options' => array(

									'label'		=>	__('Delete Plugin Settings on Uninstall', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false,
									'help'		=>	sprintf(

										'<p><strong style="color: #bb0000;">%s:</strong> %s</p>',
										esc_html(__('Caution', 'ws-form')),
										esc_html(__('If you enable this setting and uninstall the plugin this data cannot be recovered.', 'ws-form'))
									)
								),

								'uninstall_database' => array(

									'label'		=>	__('Delete Database Tables on Uninstall', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false,
									'help'		=>	sprintf(

										'<p><strong style="color: #bb0000;">%s:</strong> %s</p>',
										esc_html(__('Caution', 'ws-form')),
										esc_html(__('If you enable this setting and uninstall the plugin this data cannot be recovered.', 'ws-form'))
									)
								)
							)
						)
					)
				),
				'variable' => array(

					'label'		=>	__('Variables', 'ws-form'),

					'groups'	=>	array(

						'variable_email_logo'	=>	array(

							'heading'		=>	'#email_logo',

							'fields'	=>	array(

								'action_email_logo'	=>	array(

									'label'		=>	__('Image', 'ws-form'),
									'type'		=>	'image',
									'button'	=>	'wsf-image',
									'help'		=>	__('Use <code>#email_logo</code> in your template to add this logo.', 'ws-form')
								),

								'action_email_logo_size'	=>	array(

									'label'		=>	__('Size', 'ws-form'),
									'type'		=>	'image_size',
									'default'	=>	'full',
									'help'		=>	__('Recommended max dimensions: 400 x 200 pixels.', 'ws-form')
								)
							)
						),

						'variable_email_submission'	=>	array(

							'heading'		=>	'#email_submission',

							'fields'	=>	array(

								'action_email_group_labels'	=> array(

									'label'		=>	__('Tab Labels', 'ws-form'),
									'type'		=>	'select',
									'default'	=>	'auto',
									'options'	=>	array(

										'auto'				=>	array('text' => __('Auto', 'ws-form')),
										'true'				=>	array('text' => __('Yes', 'ws-form')),
										'false'				=>	array('text' => __('No', 'ws-form'))
									),
									'help'		=>	__("Auto - Only shown if any fields are not empty and the 'Show Label' setting is enabled.<br />Yes - Only shown if the 'Show Label' setting is enabled for that tab.<br />No - Never shown.", 'ws-form')
								),

								'action_email_section_labels'	=> array(

									'label'		=>	__('Section Labels', 'ws-form'),
									'type'		=>	'select',
									'default'	=>	'auto',
									'options'	=>	array(

										'auto'				=>	array('text' => __('Auto', 'ws-form')),
										'true'				=>	array('text' => __('Yes', 'ws-form')),
										'false'				=>	array('text' => __('No', 'ws-form'))
									),
									'help'		=>	__("Auto - Only shown if any fields are not empty and the 'Show Label' setting is enabled.<br />Yes - Only shown if the 'Show Label' setting is enabled.<br />No - Never shown.", 'ws-form')
								),

								'action_email_field_labels'	=> array(

									'label'		=>	__('Field Labels', 'ws-form'),
									'type'		=>	'select',
									'default'	=>	'auto',
									'options'	=>	array(

										'auto'				=>	array('text' => __("Auto", 'ws-form')),
										'true'				=>	array('text' => __('Yes', 'ws-form')),
										'false'				=>	array('text' => __('No', 'ws-form'))
									),
									'help'		=>	__("Auto - Only shown if the 'Show Label' setting is enabled.<br />Yes - Always shown.<br />No - Never shown.", 'ws-form')
								),

								'action_email_static_fields'	=>	array(

									'label'		=>	__('Static Fields', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	true,
									'help'		=>	__('Show static fields such as text and HTML, if not excluded at a field level.', 'ws-form')
								),

								'action_email_exclude_empty'	=>	array(

									'label'		=>	__('Exclude Empty Fields', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	true,
									'help'		=>	__('Exclude empty fields.', 'ws-form')
								)
							)
						),

						'variable_field'	=>	array(

							'heading'		=>	'#field',

							'fields'	=>	array(

								'action_email_embed_images'	=>	array(

									'label'		=>	__('Show File Preview', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	true,
									'help'		=>	__('If checked, file and signature previews will be shown. Compatible with the WS Form (Private), WS Form (Public) and Media Library file handlers.', 'ws-form')
								),

								'action_email_embed_image_description'	=>	array(

									'label'		=>	__('Show File Name and Size', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	true,
									'help'		=>	__('If checked, file and signature file names and sizes will be shown. Compatible with the WS Form (Private), WS Form (Public) and Media Library file handlers.', 'ws-form')
								),

								'action_email_embed_image_link'	=>	array(

									'label'		=>	__('Link to Files', 'ws-form'),
									'type'		=>	'checkbox',
									'default'	=>	false,
									'help'		=>	__('If checked, file and signature files will have links added to them. The Send Email action has a separate setting for this. Compatible with the WS Form (Private), WS Form (Public) and Media Library file handlers.', 'ws-form')
								)
							)
						)
					)
				)
			));

			// Don't run the rest of this function to improve client side performance
			if(!$process_options) {

				// Apply filter
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
				$options = apply_filters('wsf_config_options', $options);

				return self::options_normalize($options);
			}

			// Frameworks
			$frameworks = self::get_frameworks(false);
			foreach($frameworks['types'] as $key => $framework) {

				$name = $framework['name'];
				$options['styling']['groups']['markup']['fields']['framework']['options'][$key] = array('text' => $name);
			}

			// Templates
			$options['basic']['groups']['preview']['fields']['preview_template']['options'][''] = array('text' => __('Automatic', 'ws-form'));

			// Custom page templates
			$page_templates = array();
			$templates_path = get_template_directory();
			$templates = wp_get_theme()->get_page_templates();
			$templates['page.php'] = 'Page';
			$templates['singular.php'] = 'Singular';
			$templates['index.php'] = 'Index';
			$templates['front-page.php'] = 'Front Page';
			$templates['single-post.php'] = 'Single Post';
			$templates['single.php'] = 'Single';
			$templates['home.php'] = 'Home';

			foreach($templates as $template_file => $template_title) {

				// Build template path
				$template_file_full = $templates_path . '/' . $template_file;

				// Skip files that don't exist
				if(!WS_Form_File::file_exists($template_file_full)) { continue; }

				$page_templates[$template_file] = $template_title . ' (' . $template_file . ')';
			}

			asort($page_templates);

			foreach($page_templates as $template_file => $template_title) {

				$options['basic']['groups']['preview']['fields']['preview_template']['options'][$template_file] = array('text' => $template_title);
			}

			// Fallback
			$options['basic']['groups']['preview']['fields']['preview_template']['options']['fallback'] = array('text' => __('Blank Page', 'ws-form'));

			// AbuseIPDB country blocklist
			$countries_alpha_2 = self::get_countries_alpha_2();
			foreach($countries_alpha_2 as $code => $name) {

				$options['spam_protection']['groups']['abuseipdb']['fields']['abuseipdb_countries']['options'][$code] = array(

					'text' => sprintf('%s (%s)', $name, $code)
				);
			}

			// Apply filter
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
			$options = apply_filters('wsf_config_options', $options);

			return self::options_normalize($options);
		}

		// Core settings tab keys (built-in; everything else folds under Integrations)
		public static function get_options_core_tabs() {

			return array(
				'basic',
				'advanced',
				'styling',
				'ai',
				'spam_protection',
				'ecommerce',
				'system',
				'license',
				'data',
				'report',
				'variable',
				'integrations',
			);
		}

		// Fold non-core tabs under a single Integrations tab as sections
		public static function options_normalize($options) {

			if(!is_array($options)) { return $options; }

			$core_tabs = self::get_options_core_tabs();
			$integration_tabs = array();

			foreach($options as $tab_key => $tab) {

				if(in_array($tab_key, $core_tabs, true)) { continue; }

				$integration_tabs[$tab_key] = $tab;
			}

			// Keep as top-level tabs when at or below threshold
			if(
				empty($integration_tabs) ||
				(count($integration_tabs) <= WS_FORM_INTEGRATIONS_COLLAPSE_THRESHOLD)
			) {

				return $options;
			}

			foreach($integration_tabs as $tab_key => $tab) {

				unset($options[$tab_key]);
			}

			$integration_groups = array();

			foreach($integration_tabs as $tab_key => $tab) {

				$group = array(

					'heading' => isset($tab['label']) ? $tab['label'] : $tab_key,
					'fields'  => isset($tab['fields']) ? $tab['fields'] : array(),
				);

				if(isset($tab['groups']) && is_array($tab['groups'])) {

					$group['groups'] = $tab['groups'];
				}

				$integration_groups[$tab_key] = $group;
			}

			$options['integrations'] = array(

				'label'  => __('Integrations', 'ws-form'),
				'groups' => $integration_groups,
			);

			return $options;
		}

		// Resolve settings tab/section with backward compatibility for legacy URLs
		// e.g. tab=action_brevov3 → tab=integrations, section=action_brevov3
		public static function settings_tab_section_resolve($tab, $section = '', $options = false) {

			if($options === false) {

				$options = WS_Form_Config::get_options(false);
			}

			if(!is_array($options)) {

				return array(

					'tab'     => $tab,
					'section' => $section,
				);
			}

			// Legacy core tab aliases
			if(($tab === 'setup') || ($tab === 'appearance')) {

				$tab = 'basic';
			}

			// Legacy top-level integration tabs (pre Integrations bundling)
			if(
				($tab !== '') &&
				($tab !== false) &&
				!isset($options[$tab]) &&
				isset($options['integrations']['groups'][$tab])
			) {

				$section = $tab;
				$tab = 'integrations';
			}

			return array(

				'tab'     => $tab,
				'section' => $section,
			);
		}
	}