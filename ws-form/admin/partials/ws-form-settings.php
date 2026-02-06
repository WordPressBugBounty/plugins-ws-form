<?php

	// Get options
	$ws_form_options = WS_Form_Config::get_options();

	// Get current tab
	$ws_form_tab_current = WS_Form_Common::get_query_var('tab', false);
	if($ws_form_tab_current === false) {
		$ws_form_tab_current = WS_Form_Common::get_query_var_nonce('tab', 'basic', false, false, true, 'POST');	
	}
	if($ws_form_tab_current == 'setup') { $ws_form_tab_current = 'basic'; }				// Backward compatibility
	if($ws_form_tab_current == 'appearance') { $ws_form_tab_current = 'basic'; }		// Backward compatibility

	// Check tab is valid
	if(!isset($ws_form_options[$ws_form_tab_current])) {
?>
<script>

	location.href = '<?php WS_Form_Common::echo_esc_html(WS_Form_Common::get_admin_url('ws-form-settings')); ?>';

</script>
<?php

		exit;
	}

	// File upload checks
	$ws_form_upload_checks = WS_Form_Common::uploads_check();
	$ws_form_max_upload_size = $ws_form_upload_checks['max_upload_size'];
	$ws_form_max_uploads = $ws_form_upload_checks['max_uploads'];

	// Loader icon
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-header">
<h1><?php esc_html_e('Settings', 'ws-form'); ?></h1>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
	
	// SSL Warning
	if(($ws_form_tab_current == 'data') && !is_ssl()) {

		WS_Form_Common::admin_message_render(__('Your website is not configured to use a secure certificate. We recommend enabling SSL to ensure your submission data is securely transmitted.', 'ws-form'), 'notice-warning', false, false);
	}
?>
<h2 class="nav-tab-wrapper"> 
<?php

	// Render tabs
	foreach($ws_form_options as $tab => $ws_form_fields) {
?>
<a href="<?php WS_Form_Common::echo_esc_url(admin_url('admin.php?page=ws-form-settings&tab=' . $tab)); ?>" class="nav-tab<?php if($ws_form_tab_current == $tab) { ?> nav-tab-active<?php } ?>"><?php WS_Form_Common::echo_esc_html($ws_form_fields['label']); ?></a>
<?php

	}
?>
</h2>

<form method="post" action="<?php WS_Form_Common::echo_esc_url( admin_url( 'admin.php?page=ws-form-settings' . ( $ws_form_tab_current != '' ? '&tab=' . urlencode( $ws_form_tab_current ) : '' ) ) ); ?>" novalidate="novalidate" id="wsf-settings" enctype="multipart/form-data">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="tab" value="<?php WS_Form_Common::echo_esc_attr($ws_form_tab_current); ?>" />
<input type="hidden" name="action" value="wsf-settings-update" />
<input type="hidden" name="action_mode" id="wsf_action_mode" value="" />
<input type="hidden" name="action_license_action_id" id="wsf_action_license_action_id" value="" />
<input type="hidden" name="page" value="ws-form-settings" />
<?php

	$ws_form_js_on_change = '';
	$ws_form_save_button = false;

	if(isset($ws_form_options[$ws_form_tab_current]['fields'])) {

		$ws_form_fields = $ws_form_options[$ws_form_tab_current]['fields'];
		$ws_form_save_button = $ws_form_save_button || ws_form_render_fields($this, $ws_form_fields, $ws_form_max_uploads, $ws_form_max_upload_size, $ws_form_js_on_change);
	}

	if(isset($ws_form_options[$ws_form_tab_current]['groups'])) {

		$ws_form_groups = $ws_form_options[$ws_form_tab_current]['groups'];

		foreach($ws_form_groups as $ws_form_group) {

			// Condition
			if(isset($ws_form_group['condition'])) {

				$ws_form_condition_result = true;
				foreach($ws_form_group['condition'] as $ws_form_condition_field => $ws_form_condition_value) {

					$ws_form_condition_value_check = WS_Form_Common::option_get($ws_form_condition_field);
					if($ws_form_condition_value_check != $ws_form_condition_value) {

						$ws_form_condition_result = false;
						break;
					}
				}
				if(!$ws_form_condition_result) { continue; }
			}

			$ws_form_heading = isset($ws_form_group['heading']) ? $ws_form_group['heading'] : false;
			$ws_form_description = isset($ws_form_group['description']) ? $ws_form_group['description'] : false;
			$ws_form_fields = $ws_form_group['fields'];
			$ws_form_html_message = isset($ws_form_group['message']) ? $ws_form_group['message'] : false;

			$ws_form_save_button_return = ws_form_render_fields($this, $ws_form_fields, $ws_form_max_uploads, $ws_form_max_upload_size, $ws_form_js_on_change, $ws_form_heading, $ws_form_description, $ws_form_html_message);
			$ws_form_save_button = $ws_form_save_button || $ws_form_save_button_return;
		}
	}

	if($ws_form_save_button) {
?>
<p><input type="submit" name="wsf_submit" id="wsf_submit" class="wsf-button wsf-button-primary" value="Save Changes"></p>
<?php
	}
?>
</form>

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			var wsf_obj = new $.WS_Form();

			// Partial initialization
			wsf_obj.init_partial();

			var file_frame;

			$('#wsf-settings').on('submit', function() {

				// mod_security fix
				$('input[type="text"]').each(function() {

					var input_string = $(this).val();
					var output_string = wsf_obj.mod_security_fix(input_string);
					$(this).val(output_string);
				});
			});

			// Set mode and submit
			$('[data-action="wsf-mode-submit"]').on('click', function() {

				$('#wsf_action_mode').val($(this).attr('data-mode'));
				$('#wsf-settings').trigger('submit');
			});

			// Framework detect
			$('[data-action="wsf-framework-detect"]').on('click', function() {

				var for_id = '#' + $(this).attr('data-for');

				wsf_obj.framework_detect(function(framework) {

					if(
						(typeof(framework.type) !== 'undefined') &&
						(framework.type !== false)
					) {

						// Set framework to that detected
						$(for_id).val(framework.type);

					} else {

						// Fallback to WS Form
						$(for_id).val('ws-form');
					}

					// Switch loader off
					wsf_obj.loader_off();

				}, function() {

					// Set framework to default
					$(for_id).val('<?php WS_Form_Common::echo_esc_html(WS_FORM_DEFAULT_FRAMEWORK); ?>');

					// Show info message
					wsf_obj.message('<?php WS_Form_Common::echo_esc_html(sprintf(

						/* translators: %s: WS Form */
						__('Your current theme does not contain a recognized framework. Using %s as the form framework.', 'ws-form'),
						WS_FORM_NAME_GENERIC

					)); ?>', true, 'notice-info');

					// Switch loader off
					wsf_obj.loader_off();
				});
			});

			// Max upload size
			$('[data-action="wsf-max-upload-size"]').on('click', function() {

				var for_id = $(this).attr('data-for');
				$('#' + for_id).val(<?php WS_Form_Common::echo_esc_html($ws_form_max_upload_size); ?>);
			});

			// Max uploads
			$('[data-action="wsf-max-uploads"]').on('click', function() {

				var for_id = $(this).attr('data-for');
				$('#' + for_id).val(<?php WS_Form_Common::echo_esc_html($ws_form_max_uploads); ?>);
			});

			// Image selector
			$('[data-action="wsf-image"]').on('click', function(e) {

				var for_id = $(this).attr('data-for');

				// If the media frame already exists, reopen it.
				if(file_frame) {

					// Open frame
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({

					title: 'Select image',
					library: {
						type: 'image'
					},
					button: {
						text: 'Use this image',
					},
					multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on('select', function() {

					// We set multiple to false so only get one image from the uploader
					var attachment = file_frame.state().get('selection').first().toJSON();

					// Sets the image ID
					var image_id = attachment.id;
					var image_id_obj = $('#' + for_id);
					image_id_obj.val(image_id);

					// Get thumbnail size
					if(typeof(attachment.sizes.<?php WS_Form_Common::echo_esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>) !== 'undefined') {
						var image_size = attachment.sizes.<?php WS_Form_Common::echo_esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>;
					} else {
						var image_size = attachment.sizes.thumbnail;	
					}
					var image_width = image_size.width;
					var image_height = image_size.height;
					var image_url = image_size.url;

					// Set the preview
					var image_obj = $('#' + for_id + '_preview_image');
					if(image_obj.length == 0) {

						$('<div id="' + for_id + '_preview" class="wsf-settings-image-preview"><img id="' + for_id + '_preview_image" src="' + image_url + '" width="' + image_width + '" height="' + image_height + '" class="attachment-<?php WS_Form_Common::echo_esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?> size-<?php WS_Form_Common::echo_esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>" /><div data-action="wsf-image-reset" data-for="' + for_id + '"><?php WS_Form_Common::render_icon_16_svg('delete'); ?></div></div>').insertAfter(image_id_obj);

							// Image selector reset
							$('[data-action="wsf-image-reset"]').on('click', function(e) {

								var for_id = $(this).attr('data-for');
								var image_id_obj = $('#' + for_id);
								image_id_obj.val('');
								$('#' + for_id + '_preview').remove();
							});

					} else {

						image_obj.attr('src', image_url);
						image_obj.removeAttr('srcset');
						image_obj.attr('width', image_width);
						image_obj.attr('height', image_height);
					}
				});

				// Finally, open the modal
				file_frame.open();
			});

			// Image selector reset
			$('[data-action="wsf-image-reset"]').on('click', function(e) {

				var for_id = $(this).attr('data-for');
				var image_id_obj = $('#' + for_id);
				image_id_obj.val('');
				$('#' + for_id + '_preview').remove();
			});


			$('[wsf-file]').on('change', function() {

				var id = $(this).attr('id');
				var files = $(this)[0].files;
				var label_obj = $('label[for="' + id + '"][data-wsf-file-label]');

				if(files.length == 0) {

					// Set back to field label
					var label = '';

				} else {

					// Build label of filenames
					var filenames = [];
					for(var file_index = 0; file_index < files.length; file_index++) {

						filenames.push(files[file_index].name);
					}
					var label = filenames.join(', ');
				}

				label_obj.html(label);
			});
<?php

	if($ws_form_js_on_change != '') {

		WS_Form_Common::echo_json($ws_form_js_on_change);
	}
?>
		});

	})(jQuery);

</script>

</div>
<?php

	function ws_form_render_fields($wsform, $ws_form_fields, $ws_form_max_uploads, $ws_form_max_upload_size, &$ws_form_js_on_change, $ws_form_heading = false, $ws_form_description = false, $ws_form_html_message = false) {

		// Groups with no fields
		if(!is_array($ws_form_fields) || (count($ws_form_fields) == 0)) { return false; }

		// Heading
		if($ws_form_heading !== false) {
?>
<h2 class="title"><?php WS_Form_Common::echo_esc_html($ws_form_heading); ?></h2>
<?php
		}

		// HTML Message
		if($ws_form_html_message !== false) {

			WS_Form_Common::echo_html(sprintf(

				'<p><em>%s</em></p>',
				$ws_form_html_message
			));
		}

		// Description
		if($ws_form_description !== false) {
?>
<p><?php WS_Form_Common::echo_esc_html($ws_form_description); ?></p>
<?php
		}
?>
<table class="form-table"><tbody>
<?php
		$ws_form_save_button = false;

		foreach($ws_form_fields as $ws_form_field => $ws_form_config) {

			// Check config
			if(
				!is_array($ws_form_config) ||
				!isset($ws_form_config['type'])
			) {
				continue;
			}

			// Hidden values
			if($ws_form_config['type'] == 'hidden') { continue; }

			// Condition
			$ws_form_read_only = false;
			if(isset($ws_form_config['condition'])) {

				$ws_form_condition_result = true;
				foreach($ws_form_config['condition'] as $ws_form_condition_field => $ws_form_condition_value) {

					$ws_form_condition_value_check = WS_Form_Common::option_get($ws_form_condition_field);
					if($ws_form_condition_value_check != $ws_form_condition_value) {

						$ws_form_condition_result = false;
						break;
					}
				}
				if(!$ws_form_condition_result) { $ws_form_read_only = true; }
			}

			// Minimum
			$minimum = (isset($ws_form_config['minimum'])) ? absint($ws_form_config['minimum']) : false;

			// Maximum
			if(isset($ws_form_config['maximum'])) {

				$ws_form_maximum = $ws_form_config['maximum'];

				switch($ws_form_maximum) {

					case '#max_upload_size' : $ws_form_maximum = $ws_form_max_upload_size; break;
					case '#max_uploads' : $ws_form_maximum = $ws_form_max_uploads; break;
				}

			} else {

				$ws_form_maximum = false;
			}

			// Classes
			$ws_form_class_row_array = array();
			if($ws_form_read_only) { $ws_form_class_row_array[] = 'wsf-read-only'; }
			if(!empty($ws_form_config['class_row'])) { $ws_form_class_row_array[] = $ws_form_config['class_row']; }
			$ws_form_class_row = implode(' ', $ws_form_class_row_array);
?>
<tr<?php if(!empty($ws_form_class_row)) { ?> class="<?php WS_Form_Common::echo_esc_attr($ws_form_class_row); ?>"<?php } ?>>
<?php
			if($ws_form_config['label'] !== false) {
?>
<th scope="row"><label class="wsf-label" for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>"><?php WS_Form_Common::echo_esc_html($ws_form_config['label']); ?></label></th>
<?php
			}
?>
<td<?php if($ws_form_config['label'] === false) { ?> colspan="2"<?php } ?>><?php

			$ws_form_default = isset($ws_form_config['default']) ? $ws_form_config['default'] : false;
			$ws_form_value = WS_Form_Common::option_get($ws_form_field, $ws_form_default, true);
			if(!is_array($ws_form_value)) { $ws_form_value = esc_html($ws_form_value); }

			// Attributes
			$attributes = array();

			// Check for license related field
			$ws_form_is_license_key = (strpos($ws_form_field, 'license_key') !== false);

			if($ws_form_is_license_key) {

				// Build license constant (e.g. WSF_LICENSE_KEY)
				$ws_form_license_constant = sprintf('WSF_%s', strtoupper($ws_form_field));

				// If license constant is defined
				if(defined($ws_form_license_constant)) {

					// Set value as obscured string so that it cannot be seen if source code viewed
					$ws_form_value = WS_Form_Common::get_license_key_obscured(trim(constant($ws_form_license_constant)));

					// Set as static
					$ws_form_config['type'] = 'static';
					$ws_form_config['obscure'] = false;

					// Get prefix
					$prefix = isset($ws_form_config['action']) ? sprintf('action_%s_', $ws_form_config['action']) : '';

					// Build option key
					$option_key = sprintf('%slicense_activated', $prefix);

					// Hide deactivate button
					if(
						WS_Form_Common::option_get($option_key, false) &&
						isset($ws_form_config['button'])
					) {
						unset($ws_form_config['button']);
					}
				}
			}

			// Name
			$multiple = isset($ws_form_config['multiple']) ? $ws_form_config['multiple'] : false;
			$attributes['name'] = sprintf('%s%s', esc_attr($ws_form_field), $multiple ? '[]' : '');

			// ID
			$attributes['id'] = sprintf('wsf_%s', esc_attr($ws_form_field));

			// Obscure licenses and keys
			$obscure = isset($ws_form_config['obscure']) ? $ws_form_config['obscure'] : true;
			if(
				$obscure &&
				(
					($ws_form_config['type'] === 'license') ||
					($ws_form_config['type'] === 'key') ||
					(strpos($ws_form_field, '_key') !== false) ||
					(strpos($ws_form_field, '_client_id') !== false)
				)
			) {
				$ws_form_config['type'] = empty($ws_form_value) ? 'text' : 'password';
			}

			// Type
			$attributes['type'] = $ws_form_config['type'];

			// Size
			$size = isset($ws_form_config['size']) ? absint($ws_form_config['size']) : false;
			if($size !== false) { $attributes['size'] = $size; }

			// Minimum
			if($minimum !== false) { $attributes['min'] = $minimum; }

			// Maximum
			if($ws_form_maximum !== false) { $attributes['max'] = $ws_form_maximum; }

			// Placeholder
			if(isset($ws_form_config['placeholder'])) {

				$attributes['placeholder'] = $ws_form_config['placeholder'];
			}

			// Disabled
			$disabled = isset($ws_form_config['disabled']) ? $ws_form_config['disabled'] : false;
			if($disabled) {

				$attributes['disabled'] = '';
			}

			// Output by type
			switch($ws_form_config['type']) {

				// Static value
				case 'static' :

					switch($ws_form_field) {

						// Version
						case 'version' :

							WS_Form_Common::echo_esc_html(WS_FORM_VERSION);
							break;


						// System
						case 'system' :

							WS_Form_Common::echo_html(WS_Form_Common::get_system_report_html());
							break;

						// MCP adapter URL
						case 'mcp_adapter_url' :

							WS_Form_Common::echo_html(sprintf(

								'<code>%s</code>',
								esc_url(WS_Form_Common::get_api_path('mcp'))
							));
							break;

						default :

							// Other
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
							$ws_form_value = apply_filters('wsf_settings_static', $ws_form_value, $ws_form_field);
							WS_Form_Common::echo_html($ws_form_value);
					}
					break;

				// Text field
				case 'text' :
?>
<input class="wsf-field" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// Email field
				case 'email' :
?>
<input class="wsf-field" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// Url field
				case 'url' :
?>
<input class="wsf-field" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// File field
				case 'file' :
?>
<input class="wsf-field" wsf-file<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<label for="<?php WS_Form_Common::echo_esc_attr(sprintf('wsf_%s', $ws_form_field)); ?>" class="wsf-label" data-wsf-file-label>&nbsp;</label>

<?php
					$ws_form_save_button = true;
					break;

				// Password field
				case 'password' :
?>
<input class="wsf-field" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>" autocomplete="new-password"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// Number field
				case 'number' :
?>
<input class="wsf-field" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// Color field
				case 'color' :
?>
<input value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					$ws_form_save_button = true;
					break;

				// Checkbox field
				case 'checkbox' :
?>
<input class="wsf-field wsf-switch" value="1"<?php if($ws_form_value) { ?> checked<?php } ?><?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<label for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" class="wsf-label">&nbsp;</label>
<?php
					$ws_form_save_button = true;
					break;

				// Selectbox field
				case 'select' :

?>
<select class="wsf-field" name="<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?><?php if($multiple) { ?>[]<?php } ?>" id="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>"<?php if(($size !== false) && ($size > 1)) { ?> size="<?php WS_Form_Common::echo_esc_attr($size); ?>"<?php } ?><?php if($multiple) { ?> multiple="multiple"<?php } ?><?php WS_Form_Common::echo_esc_attributes($attributes); ?>>
<?php
					// Render options
					$ws_form_options = $ws_form_config['options'];
					$ws_form_option_selected = is_array($ws_form_value) ? $ws_form_value : array($ws_form_value);
					foreach($ws_form_options as $option_value => $option_array) {

						$option_text = $option_array['text'];
						$option_disabled = isset($option_array['disabled']) ? $option_array['disabled'] : false;

?><option value="<?php WS_Form_Common::echo_esc_attr($option_value); ?>"<?php if(in_array($option_value, $ws_form_option_selected)) { ?> selected<?php } ?><?php if($option_disabled) { ?> disabled<?php } ?>><?php WS_Form_Common::echo_esc_html($option_text); ?></option>
<?php
					}
?>
</select>
<?php
					$ws_form_save_button = true;
					break;

				// Selectbox field (Number)
				case 'select_number' :
?>
<select class="wsf-field" name="<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" id="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?>>
<?php
					// Render options
					$minimum = isset($ws_form_config['minimum']) ? $ws_form_config['minimum'] : 1;
					$ws_form_maximum = isset($ws_form_config['maximum']) ? $ws_form_config['maximum'] : 100;
					for($option_value = $minimum; $option_value <= $ws_form_maximum; $option_value++) {

?><option value="<?php WS_Form_Common::echo_esc_attr($option_value); ?>"<?php if($option_value == $ws_form_value) { ?> selected<?php } ?>><?php WS_Form_Common::echo_esc_html($option_value); ?></option>
<?php
					}
?>
</select>
<?php
					$ws_form_save_button = true;
					break;

				// Image
				case 'image' :
?>
<input name="<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" type="hidden" id="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php WS_Form_Common::echo_esc_attr($ws_form_value); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
					// Get the image ID
					$image_id = absint($ws_form_value);
					if($image_id == 0) { break; }

					// Show preview image
					$image = wp_get_attachment_image($image_id, WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE, false, array('id' => 'wsf_' . $ws_form_field . '_preview_image'));
					if($image) {
?>
<div id="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>_preview" class="wsf-settings-image-preview"><?php

	WS_Form_Common::echo_html($image);

?><div data-action="wsf-image-reset" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>"><?php WS_Form_Common::render_icon_16_svg('delete'); ?></div></div>
<?php
					}

					$ws_form_save_button = true;
					break;

				// Image size
				case 'image_size' :
?>
<select class="wsf-field" name="<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" id="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?>>
<?php
					// Render image sizes
					$ws_form_image_sizes = get_intermediate_image_sizes();
					$ws_form_image_sizes[] = 'full';
					foreach($ws_form_image_sizes as $ws_form_image_size) {

?><option value="<?php WS_Form_Common::echo_esc_attr($ws_form_image_size); ?>"<?php if($ws_form_image_size == $ws_form_value) { ?> selected<?php } ?>><?php WS_Form_Common::echo_esc_html($ws_form_image_size); ?></option>
<?php
					}
?>
</select>
<?php
					$ws_form_save_button = true;
					break;
			}

			// Buttons
			if(isset($ws_form_config['button'])) {

				$ws_form_button = $ws_form_config['button'];

				switch($ws_form_button) {

					case 'wsf-license' :

						if(WS_Form_Common::option_get('license_activated', false)) {
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-mode-submit" data-mode="deactivate" value="<?php esc_attr_e('Deactivate', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						} else {
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-mode-submit" data-mode="activate" value="<?php esc_attr_e('Activate', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						}

						break;

					case 'wsf-framework-detect' :
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-framework-detect" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Detect', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					case 'wsf-key-generate' :
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-key-generate" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Generate', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					case 'wsf-max-upload-size' :
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-max-upload-size" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Use php.ini value', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					case 'wsf-max-uploads' :
?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-max-uploads" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Use php.ini value', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					case 'wsf-image' :

?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-image" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Select image...', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					case 'wsf-form-stat-reset' :

?>
<input type="button" class="wsf-button wsf-button-inline" data-action="wsf-form-stat-reset" data-for="wsf_<?php WS_Form_Common::echo_esc_attr($ws_form_field); ?>" value="<?php esc_attr_e('Reset', 'ws-form'); ?>"<?php WS_Form_Common::echo_esc_attributes($attributes); ?> />
<?php
						break;

					default :

						// Additional allowed attributes for the input[type="button"]
						$ws_form_allowed_html = wp_kses_allowed_html( 'post' );
						$ws_form_allowed_html['input']['id'] = true;
						$ws_form_allowed_html['input']['name'] = true;
						$ws_form_allowed_html['input']['type'] = true;
						$ws_form_allowed_html['input']['class'] = true;
						$ws_form_allowed_html['input']['value'] = true;
						$ws_form_allowed_html['input']['data-action'] = true;
						$ws_form_allowed_html['input']['data-for'] = true;
						$ws_form_allowed_html['input']['data-mode'] = true;

						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- All hooks prefixed with wsf_
						WS_Form_Common::echo_html(apply_filters('wsf_settings_button', '', $ws_form_field, $ws_form_button), $ws_form_allowed_html);
				}
			}

			if(isset($ws_form_config['help'])) {

				if(
					!$ws_form_is_license_key ||
					!defined($ws_form_license_constant)
				) {
					WS_Form_Common::echo_html(sprintf(

						'<p class="wsf-helper" id="' . esc_attr($ws_form_field) . '_description">%s</p>',
						$ws_form_config['help']
					));
				}

				// Check for license related field
				if($ws_form_is_license_key && !defined($ws_form_license_constant)) {

					WS_Form_Common::echo_html(sprintf(

						'<p class="wsf-helper">%s <a href="%s" target="_blank">%s</a></p>',

						sprintf(

							/* translators: %s: License key named constant, e.g. WSF_LICENSE_KEY */
							__('The license key can also be set in <code>wp-config.php</code> using the <code>%s</code> named constant.', 'ws-form'),
							esc_html($ws_form_license_constant)
						),

						esc_attr(WS_Form_Common::get_plugin_website_url('/knowledgebase/setting-license-keys-with-php-constants/')),

						esc_html__('Learn more', 'ws-form')
					));
				}
			}

			if(isset($ws_form_config['data_change']) && $ws_form_config['data_change'] == 'reload') {

				$ws_form_js_on_change .= "\n			$('#wsf_$ws_form_field').on('change', function() { $('#wsf-settings').trigger('submit'); });";
			}
?></td>
</tr>
<?php
		}
?>
</tbody></table>
<?php

		return $ws_form_save_button;
	}
