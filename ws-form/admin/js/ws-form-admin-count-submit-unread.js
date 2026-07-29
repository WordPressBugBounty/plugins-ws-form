(function($) {

	'use strict';

	window.wsf_admin_wp_count_submit_unread_ajax = function() {

		// cache: false — same URL was returning a stale 0 after mark-as-read
		$.ajax({

			url: ws_form_admin_count_submit_read_settings.count_submit_unread_ajax_url,
			dataType: 'json',
			cache: false,
			success: function(data) {

				var count_submit_unread_total = (data && (typeof data.count_submit_unread_total !== 'undefined')) ? data.count_submit_unread_total : 0;

				wsf_admin_wp_count_submit_unread_render(count_submit_unread_total);
			}
		});
	}

	window.wsf_admin_wp_count_submit_unread_render = function(count_submit_unread_total) {

		if(typeof count_submit_unread_total === 'undefined') { var count_submit_unread_total = 0; } else { count_submit_unread_total = parseInt(count_submit_unread_total, 10); }

		if(typeof ws_form_admin_count_submit_read_settings !== 'undefined') {

			ws_form_admin_count_submit_read_settings.count_submit_unread_total = count_submit_unread_total;
		}

		var count_submit_unread_total_html = (count_submit_unread_total > 0) ? ' <span class="count-' + count_submit_unread_total + '" title="' + count_submit_unread_total + ' new submission' + ((count_submit_unread_total != 1) ? 's' : '') + '"><span class="update-count">' + count_submit_unread_total + '</span></span>' : '';

		$('.wsf-submit-unread-total').html(count_submit_unread_total_html);
	}

	// Adjust badge without an API round-trip (e.g. opening a submission marks it read)
	window.wsf_admin_wp_count_submit_unread_adjust = function(delta) {

		if(typeof ws_form_admin_count_submit_read_settings === 'undefined') { return; }

		var count_submit_unread_total = parseInt(ws_form_admin_count_submit_read_settings.count_submit_unread_total, 10);
		if(isNaN(count_submit_unread_total)) { count_submit_unread_total = 0; }

		count_submit_unread_total += parseInt(delta, 10) || 0;
		if(count_submit_unread_total < 0) { count_submit_unread_total = 0; }

		wsf_admin_wp_count_submit_unread_render(count_submit_unread_total);
	}

	// Initial wsf_admin_wp_count_submit_unread_render
	$(function() {

		window.wsf_admin_wp_count_submit_unread_render(ws_form_admin_count_submit_read_settings.count_submit_unread_total);
	});

})(jQuery);
