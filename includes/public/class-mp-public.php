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
		add_filter('get_post_metadata', array(&$this, 'remove_product_post_thumbnail'), 999, 4);
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles_scripts'));
		add_filter('comments_open', array(&$this, 'disable_comments_on_store_pages'), 10, 2);
	}
	
	/**
	 * Disable comments on store pages
	 *
	 * @since 3.0
	 * @access public
	 */
	public function disable_comments_on_store_pages( $open, $post_id ) {
		if ( get_post_type($post_id) == MP_Product::get_post_type() || get_post_meta($post_id, '_mp_store_page', true) !== '' ) {
			$open = false;
		}
		
		return $open;
	}

	/**
	 * Hide the single product title
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_title
	 * @return string
	 */
	public function hide_single_product_title( $title ) {
		if ( in_the_loop() && is_main_query() ) {
			$title = '';
		}
		
		return $title;
	}
	
	/**
	 * Check if the current page is a store page
	 *
	 * @since 3.0
	 * @param string $page The specific page to check - e.g. "cart".
	 * @return bool
	 */
	function is_store_page( $page = null ) {
		if ( is_null($page) ) {
			return ( get_post_meta(get_the_ID(), '_mp_store_page', true) !== '' || is_singular(MP_Product::get_post_type()) || is_tax(array('product_category', 'product_tag')) );
		} else {
			$page = (array) $page;
			return ( in_array(get_post_meta(get_the_ID(), '_mp_store_page', true), $page) );
		}
	}
	
	/**
	 * Include files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function includes() {
		require_once mp_plugin_dir('includes/public/class-mp-checkout.php');
		require_once mp_plugin_dir('includes/public/class-mp-cart.php');
		require_once mp_plugin_dir('includes/public/class-mp-short-codes.php');
	}
	
	/**
	 * Enqueue frontend styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function frontend_styles_scripts() {
		if ( ! $this->is_store_page() ) {
			return;
		}
		
		// CSS
		wp_register_style( 'jquery-ui', mp_plugin_url( 'ui/css/jquery-ui.min.css' ), false, MP_VERSION );
		wp_enqueue_style( 'mp-frontend', mp_plugin_url( 'ui/css/frontend.css' ), array( 'jquery-ui' ), MP_VERSION );
		wp_enqueue_style( 'mp-theme', mp_plugin_url( 'ui/themes/' . mp_get_setting( 'store_theme' ) . '.css' ), array( 'mp-frontend' ), MP_VERSION );
		wp_enqueue_style( 'select2', mp_plugin_url( 'ui/select2/select2.css'), false, MP_VERSION );
		
		// JS
		wp_register_script( 'hover-intent', mp_plugin_url( 'ui/js/hoverintent.min.js'), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'select2', mp_plugin_url( 'ui/select2/select2.min.js'), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-frontend', mp_plugin_url( 'ui/js/frontend.js'), array( 'jquery-ui-tooltip', 'colorbox', 'hover-intent', 'select2' ), MP_VERSION, true );
		
		// Localize js
		wp_localize_script( 'mp-frontend', 'mp_i18n', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'loadingImage' => mp_plugin_url( 'ui/images/loading.gif' ),
		) );
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
			$this->start_session();
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
	 * @uses $post, $wp_query
	 */
	public function load_single_product_template( $template ) {
		global $post, $wp_query;
		
		if ( get_post_type() == MP_Product::get_post_type() ) {
			$template = locate_template(array(
				"mp_product-{$post->post_name}.php",
				"mp_product-{$post->ID}.php",
				"mp_product.php",
			));
			
			if ( $template === '' ) {
				$ok = true;
				
				if ( $variation_id = get_query_var('mp_variation_id') ) {
					$variation = new MP_Product($variation_id);
					
					// Make sure variation actually exists, otherwise trigger a 404 error
					if ( ! $variation->exists() ) {
						$ok = false;
						$wp_query->set_404();
						$template = locate_template(array(
							'404.php',
							'index.php',
						));
					}
				}
				
				if ( $ok ) {
					add_filter('the_title', array(&$this, 'hide_single_product_title'));
					add_filter('the_content', array(&$this, 'single_product_content'));
				}
			}
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
	 * Hide the post thumbnail on single product template
	 *
	 * @since 3.0
	 * @access public
	 * @filter get_post_metadata
	 */
	public function remove_product_post_thumbnail( $content, $post_id, $meta_key, $single ) {
		if ( is_singular(MP_Product::get_post_type()) && is_main_query() && $meta_key == '_thumbnail_id' ) {
			return false;
		}
		
		return $content;
	}
	
	/**
	 * Filter the content for a single product
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_content
	 * @return string
	 */
	public function single_product_content( $content ) {
		if ( is_main_query() && in_the_loop() ) {
			remove_filter('get_post_metadata', array(&$this, 'remove_product_post_thumbnail'), 999, 4);
			remove_filter('the_content', array(&$this, 'single_product_content'));
			
			$show_img = ( mp_get_setting('show_img') ) ? 'single' : false;
			return mp_product(false, null, true, 'full', $show_img);
		}
		
		return $content;
	}
	
	/**
	 * Safely start session
	 *
	 * @since 3.0
	 * @access public
	 */
	public function start_session() {
		$sess_id = session_id();
		if ( empty($sess_id) ) {
			session_start();
		}
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