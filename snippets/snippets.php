<?php
/*
Plugin Name: Snippets
Plugin URI: http://github.com/edwardcasbon/wp-snippets/
Description: Use snippets of content across your Wordpress website.
Author: Edward Casbon
Version: 1.0
Author URI: http://www.edwardcasbon.co.uk
*/
class Snippets {
	
	/**
	 * Kick off.
	 *
	 */
	public static function init () {
		add_action('init', 'Snippets::snippetsPostType'); 			// Set up the new 'Snippets' post type.
		add_filter ('parse_query', 'Snippets::orderSnippets'); 		// Order the snippets alphabetically.
		add_action('add_meta_boxes', 'Snippets::snippetsMetaBox'); 	// Add the snippets menu to the posts page.
		add_action('admin_footer', 'Snippets::snippetsJS');			// Load the snippets javascript.
		add_shortcode ('snippet', 'Snippets::snippetShortcode');	// Enable the snippets shortcode.
	}
	
	/**
	 * Set up the new 'Snippets' post type.
	 *
	 */
	public static function snippetsPostType () {
		register_post_type('snippet', array(
			'labels' 	=> array( 
				'name' => 'Snippets', 
				'singular_name'	=> 'Snippet',
				'add_new_item'	=> 'Add New Snippet',
				'edit_item'		=> 'Edit Snippet',
				'new_item'		=> 'New Snippet',
				'view_item'		=> 'View Snippet'
			),
			'public'	=> false,
			'show_ui'	=> true,
		));
	}
	
	/**
	 * Order the snippets alphabetically.
	 *
	 */
	public function orderSnippets ($query) {
		global $pagenow;
		if ( is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'snippet' && !isset($_GET['orderby'])) {
			$query->query_vars['orderby'] = 'title';
			$query->query_vars['order'] = 'ASC';
		}
	}
	
	/**
	 * Add the snippets menu to the posts page.
	 *
	 */
	public static function snippetsMetaBox () {
		add_meta_box('snippets', 'Snippets', 'Snippets::snippetsCallback', 'page', 'side');
	}
	
	/**
	 * Callback for the snippets meta box
	 *
	 */	 
	public static function snippetsCallback ($post) {
		$snippetsQuery = new WP_Query(array('post_type' => 'snippet', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
		if(count($snippetsQuery->posts)>0) {
			echo "<p>Click on a snippet below to add it into the page.</p>";
			echo '<ul id="snippets" style="list-style-type: disc; margin-left: 1.5em;">';
			foreach($snippetsQuery->posts as $snippet) {
				echo '<li><a href="#" data-snippet-id="' . $snippet->ID . '">' . $snippet->post_title . '</a></li>';
			}
			echo '</ul>';
		} else { 
			echo 'You\'ve not added any snippets yet. Why not <a href="' . admin_url('post-new.php?post_type=snippet') . '">add a snippet</a> now?';
		}
	}
	
	/** 
	 * Load the snippets javascript.
	 * 
	 */
	public static function snippetsJS () {
		wp_enqueue_script('snippets', plugins_url()."/snippets/snippets.js", array(), false, true);
	}
	
	/**
	 * Enable the snippets shortcode.
	 *
	 */
	public static function snippetShortcode ($atts) {
		$snippetId = $atts['id'];
		if(!$snippetId) return;
	
		$query = new WP_Query(array('post_type' => 'snippet', 'posts_per_page'	=> '1', 'p' => $snippetId, 'post_status' => 'publish'));
		if($query->have_posts()) {
			$query->the_post();
			$snippet = get_the_content();
			$snippet = apply_filters('the_content', $snippet);
			$snippet = str_replace(']]>', ']]&gt;', $snippet);
		}
	
		wp_reset_query();
		return $snippet;
	}
}

// Initialise the plugin.
Snippets::init();