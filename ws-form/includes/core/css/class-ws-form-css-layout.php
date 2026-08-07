<?php

	class WS_Form_CSS_Layout extends WS_Form_CSS {

		// Render
		public function render_layout() {

			// Read frameworks
			$frameworks = WS_Form_Config::get_frameworks();

			// Get framework ID
			$framework_id = WS_Form_Common::option_get('framework', 'ws-form');

			// Get framework
			$framework = $frameworks['types'][$framework_id];

			// Get column class mask
			$column_class = $framework['columns']['column_css_selector'];

			// Get offset class mask
			$offset_class = $framework['columns']['offset_css_selector'];

			// Get form column count
			$columns = absint(WS_Form_Common::option_get('framework_column_count', 0));
			if($columns == 0) { $columns = 12; }

			// Container query mode (WS Form framework only)
			$container_query = ($framework_id === 'ws-form');

			// Invalid Feedback (Legacy)
			$css_return = ".wsf-invalid-feedback,\n";
			$css_return .= "[data-select-min-max], \n";
			$css_return .= "[data-checkbox-min-max] {\n";
			$css_return .= "\tdisplay: none;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-validated .wsf-field:invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated .wsf-field.wsf-invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated [role=\"radiogroup\"][data-wsf-invalid] ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated [data-select-min-max]:invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated [data-checkbox-min-max]:invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated .wsf-input-group:has(.iti .wsf-field:invalid) ~ .wsf-invalid-feedback {\n";
			$css_return .= "\tdisplay: block;\n";
			$css_return .= "}\n\n";

			// Grid
			$css_return .= ".wsf-grid {\n";

			$css_return .= "\tdisplay: flex;\n";
			$css_return .= "\tflex-wrap: wrap;\n";
			$css_return .= "}\n\n";

			// Tile - column/offset sizes via CSS variables (reset so nested tiles do not inherit)
			$css_return .= ".wsf-tile {\n";
			$css_return .= "\t--wsf-c: initial;\n";
			$css_return .= "\t--wsf-o: initial;\n";
			$css_return .= "\tposition: relative;\n";
			$css_return .= "\twidth: 100%;\n";
			$css_return .= "\tbox-sizing: border-box;\n";
			$css_return .= "\tflex: 0 0 calc(var(--wsf-c) * 100% / " . $columns . ") !important;\n";
			$css_return .= "\tmax-width: calc(var(--wsf-c) * 100% / " . $columns . ") !important;\n";
			$css_return .= "\tmargin-inline-start: calc(var(--wsf-o) * 100% / " . $columns . ") !important;\n";
			$css_return .= "}\n\n";

			// Container query containment
			if($container_query) {

				$css_return .= ".wsf-form.wsf-form-container-query {\n";
				$css_return .= "\tcontainer-type: inline-size;\n";
				$css_return .= "\tcontainer-name: wsf-form;\n";
				$css_return .= "}\n\n";
			}

			// Breakpoint CSS (columns + offsets share one media/container query)
			foreach($framework['breakpoints'] as $key => $breakpoint) {

				// Get outer breakpoint ID and name
				$breakpoint_id = esc_html($breakpoint['id']);
				$breakpoint_name = esc_html($breakpoint['name']);
				if(isset($breakpoint['min_width'])) {
					$breakpoint_min_width = floatval($breakpoint['min_width']);
				} else {
					$breakpoint_min_width = 0;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_name);

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint['column_css_selector'])) {

					$column_class_single = $breakpoint['column_css_selector'];

				} else {

					$column_class_single = $column_class;
				}

				// Check for breakpoint specific offset CSS selector
				if(isset($breakpoint['offset_css_selector'])) {

					$offset_class_single = $breakpoint['offset_css_selector'];

				} else {

					$offset_class_single = $offset_class;
				}

				// Build column + offset rules
				$rules = array();

				for($column_index = 1; $column_index <= $columns; $column_index++) {

					$mask_values = ['id' => $breakpoint_id, 'size' => $column_index];
					$class_single = WS_Form_Common::mask_parse($column_class_single, $mask_values);
					$rules[] = $class_single . " { --wsf-c: " . $column_index . "; }";
				}

				for($column_index = 0; $column_index <= $columns; $column_index++) {

					$mask_values = ['id' => $breakpoint_id, 'offset' => $column_index];
					$offset_single = WS_Form_Common::mask_parse($offset_class_single, $mask_values);
					$rules[] = $offset_single . " { --wsf-o: " . $column_index . "; }";
				}

				if($breakpoint_min_width > 0) {

					// Viewport media queries (default)
					$css_return .= "@media (min-width: " . $breakpoint_min_width . "px) {\n";

					foreach($rules as $rule) {

						if($container_query) {

							$css_return .= "\t.wsf-form:not(.wsf-form-container-query) " . $rule . "\n";

						} else {

							$css_return .= "\t" . $rule . "\n";
						}
					}

					$css_return .= "}\n";

					// Container queries (opt-in per form)
					if($container_query) {

						$css_return .= "@container wsf-form (min-width: " . $breakpoint_min_width . "px) {\n";

						foreach($rules as $rule) {

							$css_return .= "\t" . $rule . "\n";
						}

						$css_return .= "}\n";
					}

				} else {

					// Base breakpoint (no query)
					foreach($rules as $rule) {

						$css_return .= $rule . "\n";
					}
				}

				$css_return .= "\n";
			}

			$css_return .= ".wsf-bottom {\n";
			$css_return .= "\talign-self: flex-end !important;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-top {\n";
			$css_return .= "\talign-self: flex-start !important;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-middle {\n";
			$css_return .= "\talign-self: center !important;\n";
			$css_return .= "}\n\n";

			// $css_return is already escaped. Further escaping will break any base64 SVG elements.
			WS_Form_Common::echo_esc_css($css_return);	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
