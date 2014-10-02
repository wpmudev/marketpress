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
		$this->includes();
		add_filter('taxonomy_template', array(&$this, 'load_taxonomy_template'));
		add_filter('single_template', array(&$this, 'load_single_product_template'));
		add_filter('page_template', array(&$this, 'load_page_template'));
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles'));
	}
	
	/**
	 * Include files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function includes() {
		require_once mp_plugin_dir('includes/public/class-mp-short-codes.php');
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
	 * Load template for a store page
	 *
	 * @since 3.0
	 * @access public
	 * @filter page_template
	 * @uses $post
	 */
	public function load_page_template( $template ) {
		global $post;
		
		if ( mp_get_setting('pages->store') == $post->ID ) {
			$template = locate_template(array('mp_store.php'));		
		} elseif ( mp_get_setting('pages->product') == $post->ID ) {
			$template = locate_template(array('mp_productlist.php'));
		} elseif ( mp_get_setting('pages->cart') == $post->ID ) {
			$template = locate_template(array('mp_cart.php'));
		} elseif ( mp_get_setting('pages->checkout') == $post->ID ) {
			$template = locate_template(array('mp_checkout.php', 'mp_cart.php'));
		} elseif ( mp_get_setting('pages->order_status') == $post->ID ) {
			$template = locate_template(array('mp_orderstatus.php'));
		}
		
		return $template;
	}
	
	/**
	 * Load template for a single product
	 *
	 * @since 3.0
	 * @access public
	 * @filter single_template
	 * @uses $post
	 */
	public function load_single_product_template( $template ) {
		global $post;
		
		if ( get_post_type() == MP_Product::get_post_type() ) {
			$template = locate_template(array(
				"mp_product-{$post->post_name}.php",
				"mp_product-{$post->ID}.php",
				"mp_product.php",
			));
		}
		
		return $template;
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
	public function load_taxonomy_template( $template ) {
		global $wp_query;
		
		switch ( get_query_var('taxonomy') ) {
			case 'product_category' :
			case 'product_tag' :
				$term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			
			case 'product_category' :
				$template = locate_template(array(
					'mp_category-' . get_query_var('taxonomy') . '.php',
					'mp_category-' . $term->term_id . '.php',
					'mp_category.php',
					'mp_taxonomy.php',
					'taxonomy-product_category-' . get_query_var('term') . '.php',
					'taxonomy-product_category.php',
					'mp_productlist.php',
					'page.php',
				));
			break;
			
			case 'product_tag' :
				$template = locate_template(array(
					'mp_tag-' . get_query_var('taxonomy') . '.php',
					'mp_tag-' . $term->term_id . '.php',
					'mp_tag.php',
					'mp_taxonomy.php',
					'taxonomy-product_tag-' . get_query_var('term') . '.php',
					'taxonomy-product_tag.php',
					'mp_productlist.php',
					'page.php',
				));
			break;
		}
		
		if ( strpos($template, 'page.php') !== false ) {
			// Hide edit-post links
			add_filter('edit_post_link', create_function('', 'return "";'));
			// Filter output of the_title()
			add_filter('the_title', array(&$this, 'taxonomy_title'));
			// Filter output of the_content()
			add_filter('the_content', array(&$this, 'taxonomy_content'));
			// Only show the first post	
			$wp_query->post_count = 1;
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