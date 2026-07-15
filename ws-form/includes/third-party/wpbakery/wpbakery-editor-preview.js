(function($) {

	'use strict';

	var wsf_wpbakery_shortcode = (typeof wsf_wpbakery_vars !== 'undefined') ? wsf_wpbakery_vars.shortcode : 'ws_form_wpb';

	function wsf_wpbakery_form_init($context) {

		if(typeof wsf_form_init !== 'function') {

			return;
		}

		wsf_form_init(true, true, $context);
	}

	function wsf_wpbakery_init_iframe() {

		var $iframe = $('#vc_inline-frame');

		if(!$iframe.length) {

			return;
		}

		var iframe_window = $iframe[0].contentWindow;

		if(!iframe_window || !iframe_window.jQuery) {

			return;
		}

		wsf_wpbakery_form_init(iframe_window.jQuery('.wsf-wpbakery-form'));
	}

	$(window).on('vc_iframe_loaded', wsf_wpbakery_init_iframe);

	if(window.vc && window.vc.events) {

		window.vc.events.on('shortcode:' + wsf_wpbakery_shortcode + ':update', function() {

			wsf_wpbakery_form_init();
		});

		window.vc.events.on('shortcode:' + wsf_wpbakery_shortcode + ':frontend_render', function($element) {

			wsf_wpbakery_form_init($element);
		});
	}

})(jQuery);
