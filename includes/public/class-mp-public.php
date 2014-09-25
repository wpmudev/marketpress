<?php

class MP_Public {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Public();
		}
		return self::$_instance;
	}

	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_filter('taxonomy_template', array(&$this, 'load_taxonomy_templates'));
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles'));
	}
	
	/**
	 * Enqueue public stylesheets
	 *
	 * @since 3.0
	 * @access public
	 */
	public function frontend_styles() {
		wp_enqueue_style('mp-frontend', mp_plugin_url('ui/css/frontend.css'), array(), MP_VERSION);
	}
	
	/**
	 * Load page template for product_category and product_tag
	 *
	 * We don't want to use the default taxonomy template as this doesn't provide
	 * enough flexibility to correctly display product layouts, etc
	 *
	 * @since 3.0
	 * @access public
	 * @filter taxonomy_template
	 * @uses $wp_query
	 */
	public function load_taxonomy_templates( $template ) {
		global $wp_query;
		
		if ( ! empty($template) ) {
			// Don't continue as there is a taxonomy template in the theme
			return $template;
		}
		
		switch ( get_query_var('taxonomy') ) {
			case 'product_category' :
			case 'product_tag' :
				add_filter('edit_post_link', create_function('', 'return "";'));
				add_filter('the_title', array(&$this, 'taxonomy_title'));
				add_filter('the_content', array(&$this, 'taxonomy_content'));
				
				$wp_query->post_count = 1; // Only show the first post
				$template = locate_template(array('page.php', 'index.php'));
				break;
		}
		
		return $template;
	}
	
	/**
	 * Change the title output for product_category and product_tag archives
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_title
	 */
	public function taxonomy_title( $title ) {
		if ( ! in_the_loop() ) {
			return $title;
		}
		
		$tax = get_taxonomy(get_query_var('taxonomy'));
		$term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
		return $tax->singular_label . ': ' . $term->name;
	}
	
	/**
	 * Change the content for product_category and product_tag archives
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_content
	 */
	public function taxonomy_content( $content ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		
		return mp_list_products();
	}
}

MP_Public::get_instance();