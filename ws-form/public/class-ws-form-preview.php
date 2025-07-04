<?php

	/**
	 * Form preview
	 */
	class WS_Form_Preview {

		protected $form_id = 0;
		protected $form_label = '';
		protected $conversational = false;

		public function __construct() {

			// Get form_id
			$this->form_id = $this->form_id = ((isset($_GET) && isset($_GET['wsf_preview_form_id'])) ? absint($_GET['wsf_preview_form_id']) : 0);	// phpcs:ignore WordPress.Security.NonceVerification
			if($this->form_id === 0) { return false; }

			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Load form to get label
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $this->form_id;
			$form_object = $ws_form_form->db_read(false, false, false, false, false, true);
			$this->form_label = $form_object->label;
			// Clear filters (Prevents bugs in other plugins affecting our output)
			remove_all_filters('the_content');
			remove_all_filters('get_the_excerpt');

			// Set up fake post
			add_action('template_redirect', array($this, 'template_redirect'));

			// Determine which template to use (Use priority 1000 to override other template_include overrides, e.g. Oxygen visual builder)
			add_filter('template_include', array($this, 'template_include'), 1000);

			// Empty post thumbnail
			add_filter('post_thumbnail_html', function() { return ''; });

			// Oxygen support
			if(defined('CT_VERSION')) {

				global $ct_replace_render_template;
				$ct_replace_render_template = self::template_include();
			}
		}

		public function template_redirect() {

			global $wp, $wp_query;

			// Set post ID
			$post_id = -1;

			// Post constructor
			$post = new stdClass();
			$post->ID = $post_id;
			$post->post_author = 1;
			$post->post_date = current_time('mysql');
			$post->post_date_gmt = current_time('mysql', 1);

			/* translators: %s is the form label */
			$post->post_title = sprintf(__('%s Preview', 'ws-form'), $this->form_label);

			$post->post_content = do_shortcode(sprintf('[%s id="%u" published="false" preview="true"]', WS_FORM_SHORTCODE, esc_attr($this->form_id)));
			$post->post_status = 'publish';
			$post->comment_status = 'closed';
			$post->ping_status = 'closed';
			$post->post_name = 'wsf-post-preview';
			$post->post_type = 'page';
			$post->filter = 'raw';

			// Create fake post
			$wp_post = new WP_Post($post);

			// Add post to cache
			wp_cache_add($post_id, $wp_post, 'posts');

			// Update the main query
			$wp_query->post = $wp_post;
			$wp_query->posts = array( $wp_post );
			$wp_query->posts_per_page = 1;
			$wp_query->queried_object = $wp_post;
			$wp_query->queried_object_id = $post_id;
			$wp_query->found_posts = 1;
			$wp_query->post_count = 1;
			$wp_query->max_num_pages = 1; 
			$wp_query->is_page = true;
			$wp_query->is_singular = true; 
			$wp_query->is_single = false; 
			$wp_query->is_attachment = false;
			$wp_query->is_archive = false; 
			$wp_query->is_category = false;
			$wp_query->is_tag = false; 
			$wp_query->is_tax = false;
			$wp_query->is_author = false;
			$wp_query->is_date = false;
			$wp_query->is_year = false;
			$wp_query->is_month = false;
			$wp_query->is_day = false;
			$wp_query->is_time = false;
			$wp_query->is_search = false;
			$wp_query->is_feed = false;
			$wp_query->is_comment_feed = false;
			$wp_query->is_trackback = false;
			$wp_query->is_home = false;
			$wp_query->is_embed = false;
			$wp_query->is_404 = false; 
			$wp_query->is_paged = false;
			$wp_query->is_admin = false; 
			$wp_query->is_preview = false; 
			$wp_query->is_robots = false; 
			$wp_query->is_posts_page = false;
			$wp_query->is_post_type_archive = false;

			// Update globals
			$GLOBALS['wp_query'] = $wp_query;
			$wp->register_globals();
		}

		public function template_include() {

			$templates_path = get_template_directory();

			// Get preview template
			$preview_template = WS_Form_Common::option_get('preview_template', '');

			// Template selected in settings, so use that
			if($preview_template != '') {

				if($preview_template = 'fallback') {

					return WS_FORM_PLUGIN_DIR_PATH . 'public/preview-fallback.php';

				} else {

					return $templates_path . '/' . $preview_template;
				}
			}

			// Load templates until we find one that contains the_content()
			$templates = array('page.php', 'singular.php', 'index.php');

			// Return a template that contains the_content()
			foreach($templates as $template) {

				// Build template path
				$template_file = $templates_path . '/' . $template;

				// Skip files that don't exist
				if(!file_exists($template_file)) { continue; }

				// Load template into string
				$template_html = file_get_contents($template_file);

				// Look for reference to loading content
				if(
					(strpos($template_html, 'the_content(') !== false) ||
					(strpos($template_html, 'content\'', strpos($template_html, 'get_template_part(')) !== false) ||
					(strpos($template_html, 'content/', strpos($template_html, 'get_template_part(')) !== false)
				) {
					return $template_file;
				}
			}

			// Fallback
			return WS_FORM_PLUGIN_DIR_PATH . 'public/preview-fallback.php';
		}
	}
