<?php
/*
Plugin Name: Snippets
Plugin URI: http://github.com/edwardcasbon/wp-snippets/
Description: Use snippets of content across your Wordpress website.
Author: Edward Casbon
Version: 1.1
Author URI: http://www.edwardcasbon.co.uk
*/
class Snippets {
	
	/**
	 * Plugin settings.
	 * 
	 */	
	protected $_settings;
	
	/**
	 * Kick off.
	 *
	 */
	public function __construct () {
		$this->_getSettings();
	
		add_action ( 'init', array($this, 'snippetsPostType'));
		add_filter ( 'parse_query', array($this, 'orderSnippets'));
		add_action ( 'add_meta_boxes', array($this, 'snippetsMetaBox'));
		add_action ( 'admin_footer', array($this, 'snippetsJS'));
		add_action ( 'admin_menu', array($this, 'addSettingsPage'));
		add_action ( 'wp_ajax_getSnippet', array($this, 'getAjaxSnippet'));
		add_action ( 'wp_ajax_getSnippetsSettings', array($this, 'getAjaxSettings'));
		add_shortcode ( 'snippet', array($this, 'snippetShortcode'));
	}
	
	/**
	 * Set up the new 'Snippets' post type.
	 *
	 */
	public function snippetsPostType () {
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
	public function snippetsMetaBox () {
		add_meta_box('snippets', 'Snippets', array($this, 'snippetsCallback'), 'page', 'side');
	}
	
	/**
	 * Callback for the snippets meta box.
	 *
	 */	 
	public function snippetsCallback ($post) {
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
	public function snippetsJS () {
		wp_enqueue_script('snippets', plugins_url()."/snippets/snippets.js", array(), false, true);
	}
	
	/**
	 * Get and return the snippet content.
	 *
	 */
	public function getSnippetContent ($snippetId) {
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
	
	/**
	 * Enable the snippets shortcode.
	 *
	 */
	public function snippetShortcode ($atts) {
		$snippetId = $atts['id'];
		if(!$snippetId) return;
	
		return $this->getSnippetContent($snippetId);
	}
	
	/**
	 * Get the snippet content for an ajax request.
	 *
	 */
	public function getAjaxSnippet () {
		$snippetId = $_GET['snippetId'];
		if(!$snippetId) return;
		
		die($this->getSnippetContent($snippetId));
	}
	
	/**
	 * Add a settings page for the plugin.
	 *
	 */
	public function addSettingsPage () {
		add_submenu_page('options-general.php', 'Snippets Settings', 'Snippets', 'manage_options', 'snippets', array($this, 'createSettingsPage'));
	}
	
	/**
	 * Create the plugin settings page.
	 *
	 */	
	public function createSettingsPage () {
		$posted = false;
		$message = false;
		
		if(isset($_POST['submit'])) {
			$settings = array('type' => $_POST['snippet-type']);
			update_option('snippets_options', $settings);
			$posted = true;
			$message = '<div id="message" class="updated"><p>Settings saved</p></div>'; 
			$this->_getSettings();
		}
		
		$tokenChecked = ($this->_settings['type'] == 'token') ? 'checked="checked"' : false;
		$fullChecked = ($this->_settings['type'] == 'full') ? 'checked="checked"' : false;
		echo '<style>.indent{padding-left: 2em; margin-top:1em;}</style>' . 
			'<div class="wrap">' . 
			screen_icon() . 
			'<h2>Snippets Settings</h2>' . 
			$message .
			'<form action="" method="post" id="snippets">' .
				'<p><strong>Snippet type:</strong> How is the snippet to be inserted into the content?</p>' .
				'<ul class="indent">' .
					'<li><label for="token"><input type="radio" name="snippet-type" id="token" value="token" ' . $tokenChecked . ' /> <strong>Token</strong> (Adds a token into the content which is parsed on the page load)</label></li>' .
					'<li><label for="full"><input type="radio" name="snippet-type" id="full" value="full" ' . $fullChecked . ' /> <strong>In full</strong> (Adds the full content of the snippet into the editor)</label></li>' .
				'</ul>' .
				'<input class="button-primary" type="submit" value="Save changes" name="submit"/>' .
			'</form>' .
		'</div>';
	}
	
	/**
	 * Utility function for getting plugin settings.
	 *
	 */
	protected function _getSettings () {
		if(!$this->_settings = get_option('snippets_options')) {
			$settings = array('type' => 'token');
			add_option('snippets_options', $settings);
			$this->_settings = $settings;
		}
	}
	
	/**
	 * Get the settings via ajax.
	 * 
	 */
	public function getAjaxSettings () {
		die(json_encode($this->_settings));
	}	
}

// Initialise the plugin.
new Snippets();