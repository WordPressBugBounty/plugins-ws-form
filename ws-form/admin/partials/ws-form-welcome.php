<?php

	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Render loader
	WS_Form_Common::loader();

	// Set intro option to true
	WS_Form_Common::option_set('intro', true);

	// Mark set-up as complete so the welcome screen is only shown once
	WS_Form_Common::option_set('setup', true);

	// Assume the REST API check fails until the background test confirms otherwise.
	// If the REST API is unreachable the test cannot write back, so this is cleared on success instead.
	WS_Form_Common::option_set('api_check_warning', true);

	// Flush WP rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
?>
<!-- Welcome Banner -->
<div id="wsf-welcome">

<div class="wsf-welcome-logo"><svg xmlns="http://www.w3.org/2000/svg" x="0" y="0" viewBox="0 0 503.2 150" xml:space="preserve"><title><?php WS_Form_Common::echo_esc_html(sprintf(

	/* translators: %s: Presentatable name (e.g. WS Form PRO) */
	__('%s - Smart. Fast. Forms.', 'ws-form'),

	WS_FORM_NAME_PRESENTABLE

), 'ws-form'); ?></title><path d="M75.3 148L59.8 78.6l-.4-1.6-3.3-18.1H56l-1.4 8-2.4 11.7-16 69.4H24.4L0 45.5h9.8l14 59.7 6.5 31.2h.6a330 330 0 016-31.4l14-59.5h10.3l14.1 59.7c1.1 4.5 3.1 14.9 5.9 31.2h.6c.2-2.1 1.2-7.3 3.1-15.7 1.8-8.4 7.7-33.4 17.5-75.2h9.7L86.9 148H75.3zM173.2 122.3c0 8.6-2.5 15.4-7.4 20.3-5 4.9-12.1 7.3-21.5 7.3-5.1 0-9.6-.6-13.4-1.8a32 32 0 01-9-4l4.3-7.5c2.9 1.8 2.1 1.3 5.8 2.6a38 38 0 0012.9 2.1 18 18 0 0013.7-5.2c3.3-3.5 5-8.1 5-13.9 0-4.5-1.2-8.4-3.6-11.5a48.2 48.2 0 00-13.6-10.6 86.5 86.5 0 01-15.7-10.6 26.6 26.6 0 01-8.8-20.6c0-7.4 2.7-13.5 8.2-18.3a30.3 30.3 0 0120.8-7.2c9 0 15.8 2.3 21.9 6.2l-4.3 7.5a33.8 33.8 0 00-18-5.2c-5.8 0-10.4 1.6-13.9 4.7a16 16 0 00-5.2 12.3c0 4.5 1.2 8.3 3.5 11.4 2.3 3.1 7.3 6.8 14.9 11.1 7.4 4.5 12.5 8 15.3 10.7 2.8 2.7 4.8 5.6 6.2 8.9 1.3 3.4 1.9 7.1 1.9 11.3zM225.3 53.5h-17.6V148H198V53.5h-14.3l.1-7.8h14.1l.1-8.9c0-13 1.2-21.3 4.7-27 3.6-6 9.6-9.7 18-9.7h10.1v8.3l-9.6.1c-4.9.1-7 1.6-8.8 3.4-1.7 1.8-2.6 4-3.5 8.1-.8 4.1-1.2 9.8-1.2 17v8.6h17.6v7.9zM300 96.5c0 17.3-3 30.5-8.9 39.6a28.7 28.7 0 01-25.6 13.7c-11 0-19.4-4.6-25.2-13.7-5.8-9.2-8.7-22.4-8.7-39.6 0-35.3 11.4-53 34.3-53 10.8 0 19.1 4.6 25.1 13.9s9 22.3 9 39.1zm-58.3 0c0 14.8 1.9 26 5.8 33.5a19 19 0 0018.1 11.3c16.1 0 24.1-15 24.1-44.9 0-29.7-8-44.5-24.1-44.5-8.4 0-14.5 3.7-18.3 11.1a78.8 78.8 0 00-5.6 33.5zM315.6 68.8c0-12.4 15-25.1 31-25.2 10.8-.1 14.7 3 18.6 4.8l-4.9 7.5a28 28 0 00-13.9-3.5c-4.7-.1-8.4.5-12.6 3.2-3.4 2.3-7.1 4.5-8.3 14.3-.8 6.6-.3 16.1-.3 23.7V148h-9.8M366.6 67c1.9-16.9 17.4-23.5 28.2-23.5a33 33 0 0117.6 5.1c3.3 2.5 5.1 4.7 7.1 11.3 2.7-6.3 4.9-8.2 8.8-11a26 26 0 0115.3-5.2c8.5 0 16.9 2.6 22 10 4.1 5.9 5.9 14.3 5.9 27.4v67h-9.7V78.2c.2-19.9-5-26.3-18.2-26.3-6.5 0-11.3 1.6-14.8 7.7-3.4 6-5 16.8-5 28.5v60h-9.7V78.2c0-8.7-1.3-15.2-4-19.4s-9.3-6.5-15-6.5c-7.4 0-12.5 3.6-15.8 9.8-3.4 6.2-3 15.8-3 29.6V148h-9.8"/><circle cx="494.4" cy="52.3" r="8.8"/><circle cx="494.4" cy="95.5" r="8.8"/><circle cx="494.4" cy="138.2" r="8.8"/></svg></div>
<?php

	// Partner
	$ws_form_partner_logo_text = getenv('wsf_partner_logo_text');
	$ws_form_partner_logo_url = getenv('wsf_partner_logo_url');
	$ws_form_partner_logo_width = getenv('wsf_partner_logo_width');
	$ws_form_partner_logo_height = getenv('wsf_partner_logo_height');
	$ws_form_partner_logo_alt = getenv('wsf_partner_logo_alt');

	if(
		($ws_form_partner_logo_text !== false) ||
		($ws_form_partner_logo_url !== false) 
	) {
?>
<div class="wsf-welcome-partner">
<?php
		if($ws_form_partner_logo_text !== false) {
?>
<p><?php WS_Form_Common::echo_esc_html($ws_form_partner_logo_text); ?></p>
<?php
		}

		if($ws_form_partner_logo_url !== false) {
?>
<img src="<?php WS_Form_Common::echo_esc_attr($ws_form_partner_logo_url); ?>"<?php if($ws_form_partner_logo_width !== false) { ?> width="<?php WS_Form_Common::echo_esc_attr($ws_form_partner_logo_width); ?>" <?php } ?><?php if($ws_form_partner_logo_height !== false) { ?> height="<?php WS_Form_Common::echo_esc_attr($ws_form_partner_logo_height); ?>" <?php } ?><?php if($ws_form_partner_logo_alt !== false) { ?> alt="<?php WS_Form_Common::echo_esc_attr($ws_form_partner_logo_alt); ?>" title="<?php WS_Form_Common::echo_esc_attr($ws_form_partner_logo_alt); ?>" <?php } ?> />
<?php
		}
?>
</div>
<?php
		
	}
?>

<div class="wsf-video-container" data-wsf-video-src="https://player.vimeo.com/video/289590605?autoplay=1&controls=1&preload=auto">
<img src="<?php WS_Form_Common::echo_esc_attr(WS_FORM_PLUGIN_DIR_URL . 'admin/images/welcome-video-placeholder.jpg'); ?>" srcset="<?php WS_Form_Common::echo_esc_attr(WS_FORM_PLUGIN_DIR_URL . 'admin/images/welcome-video-placeholder.jpg'); ?> 1x, <?php WS_Form_Common::echo_esc_attr(WS_FORM_PLUGIN_DIR_URL . 'admin/images/welcome-video-placeholder-2x.jpg'); ?> 2x" width="1024" height="576" alt="<?php esc_attr_e('Play welcome video', 'ws-form'); ?>" />
</div>

<div class="wsf-welcome-buttons">
<a href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_admin_url('ws-form-add')); ?>" class="wsf-welcome-button"><?php WS_Form_Common::echo_esc_svg(WS_Form_Config::get_icon_16_svg('plus-circle')); ?><span><?php esc_html_e('Create Your First Form', 'ws-form'); ?></span></a>
<a href="<?php WS_Form_Common::echo_esc_url(WS_Form_Common::get_plugin_website_url('/knowledgebase/', 'welcome')); ?>" target="_blank" class="wsf-welcome-button wsf-welcome-button-secondary"><?php WS_Form_Common::echo_esc_svg(WS_Form_Config::get_icon_16_svg('documentation')); ?><span><?php esc_html_e('Documentation', 'ws-form'); ?></span></a>
</div>

</div>
<!-- /Welcome Banner -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			var wsf_obj = new $.WS_Form();

			wsf_obj.init_partial();

			// Highlight menu
			$('#toplevel_page_ws-form').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu current').addClass('selected');
			$('[href="admin.php?page=ws-form-welcome"]', $('#toplevel_page_ws-form-welcome')).closest('li').addClass('wp-menu-open current');

			// Welcome video - swap the placeholder for the Vimeo player on click (autoplay allowed as it is user initiated)
			$('.wsf-video-container').on('click', function() {

				var video_src = $(this).data('wsf-video-src');
				if(!video_src) { return; }

				var iframe = document.createElement('iframe');
				iframe.setAttribute('src', video_src);
				iframe.setAttribute('frameborder', '0');
				iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
				iframe.setAttribute('allowfullscreen', '');

				$(this).removeAttr('data-wsf-video-src').empty().append(iframe);
			});

			// Scale the welcome content down on short viewports so the buttons are never cut off.
			// CSS transforms do not affect the layout box, so offsetHeight always reports the true unscaled height.
			function wsf_welcome_scale() {

				var welcome = document.getElementById('wsf-welcome');
				if(!welcome) { return; }

				var body = document.getElementById('wpbody');
				var available = body ? body.clientHeight : window.innerHeight;
				var content = welcome.offsetHeight;
				if(!available || !content) { return; }

				// Never enlarge, only shrink to fit
				var scale = Math.min(1, available / content);
				welcome.style.transform = 'translate(-50%, -50%) scale(' + scale + ')';
			}

			$(window).on('resize', wsf_welcome_scale);
			$('.wsf-video-container img').on('load', wsf_welcome_scale);
			wsf_welcome_scale();

			// Recalculate once everything (fonts, images) has finished loading
			$(window).on('load', wsf_welcome_scale);

			// Background REST API check (shows the loader while it runs)
			// On success the endpoint clears the warning flag server side. On failure the flag remains
			// set, so a dismissable warning is shown on subsequent admin screens.
			wsf_obj.api_test();
		});

	})(jQuery);

</script>
