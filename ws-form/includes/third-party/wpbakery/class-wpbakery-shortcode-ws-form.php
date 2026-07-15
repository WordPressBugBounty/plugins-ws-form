<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Third party class
	class WS_Form_WPBakery_Shortcode {

		// Get forms for WP Bakery dropdown (label => id)
		public static function get_forms_dropdown() {

			$forms = WS_Form_Common::get_forms_array(false);
			$dropdown = array(

				__('Select form...', 'ws-form') => '',
			);

			foreach($forms as $form_id => $form_label) {

				$dropdown[$form_label] = (string) $form_id;
			}

			return $dropdown;
		}

		// Register element with WP Bakery
		public static function register() {

			$map_function = function_exists('wpb_map') ? 'wpb_map' : 'vc_map';

			if(!function_exists($map_function)) {

				return;
			}

			$map_function(array(

				'name'        => WS_FORM_NAME_GENERIC,
				'base'        => WS_FORM_WPBAKERY_SHORTCODE,
				'description' => __('Add a form.', 'ws-form'),
				'category'    => WS_FORM_NAME_GENERIC,
				'icon'        => WS_FORM_WPBAKERY_URL . 'icon.svg',
				'params'      => array(

					array(

						'type'        => 'dropdown',
						'heading'     => __('Form', 'ws-form'),
						'param_name'  => WS_FORM_WPBAKERY_ATTR_FORM_ID,
						'value'       => self::get_forms_dropdown(),
						'std'         => '',
						'save_always' => true,
						'admin_label' => true,
						'description' => __('Choose the form you want to show in this element.', 'ws-form'),
					),

					array(

						'type'        => 'textfield',
						'heading'     => __('Form Element ID (Optional)', 'ws-form'),
						'param_name'  => WS_FORM_WPBAKERY_ATTR_ELEMENT_ID,
						'description' => __('Optional custom element ID for the form.', 'ws-form'),
					),

					array(

						'type'        => 'textfield',
						'heading'     => __('CSS Class (Optional)', 'ws-form'),
						'param_name'  => WS_FORM_WPBAKERY_ATTR_CLASS,
						'description' => __('Additional CSS classes for the form.', 'ws-form'),
					),
				),
			));
		}
	}

	if(class_exists('WPBakeryShortCode')) {

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Third party class
		class WPBakeryShortCode_Ws_Form_Wpb extends WPBakeryShortCode {

			// Build WS Form shortcode
			private function get_shortcode($form_id, $atts, $is_editor) {

				$shortcode = sprintf('[%s id="%u"', WS_FORM_SHORTCODE, $form_id);

				$element_id = isset($atts[WS_FORM_WPBAKERY_ATTR_ELEMENT_ID]) ? trim($atts[WS_FORM_WPBAKERY_ATTR_ELEMENT_ID]) : '';

				if($element_id !== '') {

					$shortcode .= sprintf(' element_id="%s"', esc_attr($element_id));
				}

				$class = isset($atts[WS_FORM_WPBAKERY_ATTR_CLASS]) ? trim($atts[WS_FORM_WPBAKERY_ATTR_CLASS]) : '';

				if($class !== '') {

					$shortcode .= sprintf(' class="%s"', esc_attr($class));
				}

				if($is_editor) {

					$shortcode .= ' visual_builder="true"';
				}

				$shortcode .= ']';

				return $shortcode;
			}

			// Render element output
			public function content($atts, $content = '') {

				if(function_exists('vc_map_get_attributes')) {

					$atts = vc_map_get_attributes(WS_FORM_WPBAKERY_SHORTCODE, $atts);

				} else {

					$atts = shortcode_atts(array(

						WS_FORM_WPBAKERY_ATTR_FORM_ID     => '',
						WS_FORM_WPBAKERY_ATTR_ELEMENT_ID => '',
						WS_FORM_WPBAKERY_ATTR_CLASS      => '',
					), $atts, WS_FORM_WPBAKERY_SHORTCODE);
				}

				$form_id = absint($atts[WS_FORM_WPBAKERY_ATTR_FORM_ID]);
				$is_editor = WS_Form_WPBakery::is_editor();
				$shortcode = $this->get_shortcode($form_id, $atts, $is_editor);

				$output = '<div class="wsf-wpbakery-form">';

				if($form_id > 0) {

					if($is_editor) {

						$output .= sprintf('<div style="min-height:42px">%s</div>', do_shortcode($shortcode));	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					} else {

						$output .= do_shortcode($shortcode);	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					if($is_editor) {

						$output .= '<script>if(typeof wsf_form_init === "function") { wsf_form_init(true); }</script>';	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

				} elseif($is_editor) {

					$output .= '<div class="wsf-wpbakery-form-selector">';

					ob_start();
					WS_Form_Common::echo_logo();
					$output .= ob_get_clean();	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					$output .= '<p>' . esc_html__('Please select a form from the element settings.', 'ws-form') . '</p>';
					$output .= '</div>';
				}

				$output .= '</div>';

				return $output;
			}
		}
	}
