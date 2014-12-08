<?php

/*
Plugin Name: MarketPress
Version: 3.0a.1
Plugin URI: https://premium.wpmudev.org/project/e-commerce/
Description: The complete WordPress ecommerce plugin - works perfectly with BuddyPress and Multisite too to create a social marketplace, where you can take a percentage! Activate the plugin, adjust your settings then add some products to your store.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
Text Domain: mp
WDP ID: 144

Copyright 2009-2014 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
*/

define('MP_VERSION', '3.0a.1');

class Marketpress {	
	/**
	 * 
	 *
	 * @since 3.0
	 * @access public
	 * @var bool
	 */
	var $weight_printed = false;
	
	/**
	 * Refers to the single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Refers to the absolute path to the plugin's main file
	 *
	 * @since 3.0
	 * @access private
	 * @var string
	 */
	private $_plugin_file = null;

	/**
	 * Refers to the absolute url to the plugin's directory
	 *
	 * @since 3.0
	 * @access private
	 * @var string
	 */
	private $_plugin_url = null;
	
	/**
	 * Refers to the absolute path to the plugin's directory
	 *
	 * @since 3.0
	 * @access private
	 */
	private $_plugin_dir = null;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new Marketpress();
		}
		return self::$_instance;
	}
		
	/**
	 * Gets an absolute path to the plugin's base directory
	 *
	 * @since 3.0
	 * @access private
	 * @param string $path (optional) Will be appended onto the base directory
	 * @return string
	 */
	public function plugin_dir( $path = '' ) {
		return $this->_plugin_dir . ltrim($path, '/');
	}
	
	/**
	 * Gets an absolute url to the plugin's base directory
	 *
	 * @since 3.0
	 * @access private
	 * @param string $path (optional) Will be appended onto the base directory
	 * @return string
	 */	
	public function plugin_url( $path = '' ) {
		return $this->_plugin_url . ltrim($path, '/');
	}

	/**
	 * Register custom post types, taxonomies and stati
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wp_version
	 */
	public function register_custom_types() {
		global $wp_version;
		
		//! Register product_category taxonomy
		register_taxonomy('product_category', MP_Product::get_post_type(), apply_filters('mp_register_product_category', array(
			'hierarchical' => true,
			'labels' => array(
				'name' => _x('Product Categories', 'product_category', 'mp'),
				'singular_name' => _x('Product Category', 'product_category', 'mp'),
				'all_items' => __('All Product Categories', 'mp'),
				'edit_item' => __('Edit Product Category', 'mp'),
				'view_item' => __('View Product Category', 'mp'),
				'update_item' => __('Update Product Category', 'mp'),
				'add_new_item' => __('Add New Product Category', 'mp'),
				'new_item_name' => __('New Product Category Name', 'mp'),
				'parent_item' => __('Parent Product Category', 'mp'),
				'parent_item_colon' => __('Parent Product Category:', 'mp'),
				'search_items' => __('Search Product Categories', 'mp'),
				'separate_items_with_commas' => __('Separate product categories with commas', 'mp'),
				'add_or_remove_items' => __('Add or remove product categories', 'mp'),
				'choose_from_most_used' => __('Choose from the most used product categories', 'mp'),
				'not_found' => __('No product categories found', 'mp'),
			),
			'capabilities' => array(
				'manage_terms' => 'manage_product_categories',
				'edit_terms' => 'manage_product_categories',
				'delete_terms' => 'manage_product_categories',
				'assign_terms' => 'edit_products'
			),
			'show_ui' => true,
			'show_admin_column' => true,
			'rewrite' => array(
				'with_front' => false,
				'slug' => mp_get_setting('pages->products'),
			),
		)));
		
		//! Register product_tag taxonomy
		register_taxonomy('product_tag', MP_Product::get_post_type(), apply_filters('mp_register_product_tag', array(
			'hierarchical' => false,
			'labels' => array(
				'name' => _x('Product Tags', 'product_tag', 'mp'),
				'singular_name' => _x('Product Tag', 'product_tag', 'mp'),
				'all_items' => __('All Product Tags', 'mp'),
				'edit_item' => __('Edit Product Tag', 'mp'),
				'view_item' => __('View Product Tag', 'mp'),
				'update_item' => __('Update Product Tag', 'mp'),
				'add_new_item' => __('Add New Product Tag', 'mp'),
				'new_item_name' => __('New Product Tag Name', 'mp'),
				'parent_item' => __('Parent Product Tag', 'mp'),
				'parent_item_colon' => __('Parent Product Tag:', 'mp'),
				'search_items' => __('Search Product Tags', 'mp'),
				'separate_items_with_commas' => __('Separate product tags with commas', 'mp'),
				'add_or_remove_items' => __('Add or remove product tags', 'mp'),
				'choose_from_most_used' => __('Choose from the most used product tags', 'mp'),
				'not_found' => __('No product tags found', 'mp'),
			),
			'capabilities' => array(
				'manage_terms' => 'manage_product_tags',
				'edit_terms' => 'manage_product_tags',
				'delete_terms' => 'manage_product_tags',
				'assign_terms' => 'edit_products'
			),
			'show_admin_column' => true,
			'show_ui' => true,
			'rewrite' => array(
				'with_front' => false,
				'slug' => mp_get_setting('pages->products'),
			),
		)));

		//! Register product post type
		register_post_type(MP_Product::get_post_type(), apply_filters('mp_register_post_type', array(
			'labels' => array(
				'name' => __('Products', 'mp'),
				'singular_name' => __('Product', 'mp'),
				'menu_name' => __('Store', 'mp'),
				'all_items' => __('Products', 'mp'),
				'add_new' => __('Create New Product', 'mp'),
				'add_new_item' => __('Create New Product', 'mp'),
				'edit_item' => __('Edit Product', 'mp'),
				'edit' => __('Edit', 'mp'),
				'new_item' => __('New Product', 'mp'),
				'view_item' => __('View Product', 'mp'),
				'search_items' => __('Search Products', 'mp'),
				'not_found' => __('No Products Found', 'mp'),
				'not_found_in_trash' => __('No Products found in Trash', 'mp'),
				'view' => __('View Product', 'mp')
			),
			'description' => __('Products for your e-commerce store.', 'mp'),
			'public' => true,
			'show_ui' => true,
			'publicly_queryable' => true,
			'capability_type' => array('product', 'products'),
      'menu_icon' => ( version_compare($wp_version, '3.8', '>=') ) ? 'dashicons-cart' : mp_plugin_url('ui/images/marketpress-icon.png'),
			'hierarchical' => false,
			'map_meta_cap' => true,
			'rewrite' => array(
				'slug' => mp_store_page_uri('products', false),
				'with_front' => false
			),
			'query_var' => true,
			'supports' => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'revisions',
				'thumbnail',
			),
			'taxonomies' => array(
				'product_category',
				'product_tag',
			),
		)));

		//! Register mp_order post type
		register_post_type( 'mp_order', apply_filters( 'mp_register_post_type_mp_order', array( 
			'labels' => array( 
				'name' => __( 'Orders', 'mp' ),
				'singular_name' => __( 'Order', 'mp' ),
				'add_new' => _x( 'Add New', 'mp_order', 'mp' ),
				'add_new_item' => __( 'Add New Order', 'mp' ),
				'edit_item' => __( 'Edit Order', 'mp' ),
				'new_item' => __( 'New Order', 'mp' ),
				'view_item' => __( 'View Order', 'mp' ),
				'search_items' => __( 'Search Orders', 'mp' ),
				'not_found' => __( 'No orders found', 'mp' ),
				'not_found_in_trash' => __( 'No orders found in trash', 'mp' ),
				'parent_item_colon' => __( 'Parent Order', 'mp' ),
			 ),
			'description' => __( 'Orders from your e-commerce store.', 'mp' ),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'capability_type' => array( 'store_order', 'store_orders' ),
			'map_meta_cap' => true,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array( '' ),
		 ) ) );
		
		//! Register product_variation post type
		register_post_type('mp_product_variation', array(
			'public' => false,
			'show_ui' => false,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array(),
		));

		//! Register custom post statuses for our orders
		register_post_status('order_received', array(
			'label'				=> __('Received', 'mp'),
			'label_count' => _n_noop('Received <span class="count">(%s)</span>', 'Received <span class="count">(%s)</span>', 'mp'),
			'post_type'		=> 'mp_order',
			'public'			=> false
		) );
		register_post_status('order_paid', array(
			'label'				=> __('Paid', 'mp'),
			'label_count' => _n_noop('Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'mp'),
			'post_type'		=> 'mp_order',
			'public'			=> false
		) );
		register_post_status('order_shipped', array(
			'label'				=> __('Shipped', 'mp'),
			'label_count' => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'mp'),
			'post_type'		=> 'mp_order',
			'public'			=> false
		) );
		register_post_status('order_closed', array(
			'label'				=> __('Closed', 'mp'),
			'label_count' => _n_noop('Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'mp'),
			'post_type'		=> 'mp_order',
			'public'			=> false
		) );
		
		// register product attributes
		MP_Product_Attributes::get_instance()->register();
	}
	
	/**
	 * Load payment and shipping gateways
	 *
	 * @since 3.0
	 * @access public
	 */
	public function load_plugins() {
		if ( mp_get_setting('disable_cart') ) {
			return;
		}
		
		require_once $this->plugin_dir('includes/common/class-mp-gateway-api.php');
		mp_include_dir($this->plugin_dir('includes/common/payment-gateways'));
		
		/**
		 * Fires after internal gateway plugins are loaded
		 *
		 * @since 3.0
		 */
		do_action('mp_load_gateway_plugins');
		
		MP_Gateway_API::load_active_gateways();
		
		require_once $this->plugin_dir('includes/common/class-mp-shipping-api.php');
		mp_include_dir($this->plugin_dir('includes/common/shipping-modules'));
		
		/**
		 * Fires after internal shipping plugins are loaded
		 *
		 * @since 3.0
		 */
		do_action('mp_load_shipping_plugins');
		
		MP_Shipping_API::load_active_plugins();
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		// Init variables
		$this->_init_vars();
		
		// Include constants
		require_once $this->plugin_dir('includes/common/constants.php');
		
		// Includes
		add_action('init', array(&$this, 'includes'), 0);
		// Load gateway/shipping plugins
		add_action('init', array(&$this, 'load_plugins'), 0);
		// Register system addons
		add_action('init', array(&$this, 'register_addons'), 0);
		// Setup custom types
		add_action('init', array(&$this, 'register_custom_types'), 1);
		// Maybe flush rewrites
		add_action('init', array(&$this, 'maybe_flush_rewrites'), 99);
		// Fix insecure images
		add_filter('wp_get_attachment_url', array(&$this, 'fix_insecure_images'), 10, 2);
		// Setup rewrite rules
		add_filter('rewrite_rules_array', array(&$this, 'add_rewrite_rules'));
		// Add custom query vars
		add_filter('query_vars', array(&$this, 'add_query_vars'));
		// Filter billing info user meta
		add_filter('get_user_metadata', array(&$this, 'get_user_billing_info'), 10, 4);
	}
	
	/**
	 * Register add ons
	 *
	 * @since 3.0
	 * @access public
	 */
	public function register_addons() {
		mp_register_addon(array(
			'label' => __('Coupons', 'mp'),
			'desc' => __('Offer and accept coupon codes', 'mp'),
			'class' => 'MP_Coupons',
			'path' => mp_plugin_dir('includes/addons/mp-coupons/class-mp-coupons.php')
		));		
	}
	
	/**
	 * Add rewrite rules
	 *
	 * @since 3.0
	 * @access public
	 * @filter rewrite_rules_array
	 */
	public function add_rewrite_rules( $rewrites ) {
		$new_rules = array();
		
		// Product variations
		if ( $post_id = mp_get_setting( 'pages->products' ) ) {
			$uri = get_page_uri( $post_id );
			$new_rules[ $uri . '/([^/]+)/variation/([^/]+)/?' ] = 'index.php?' . MP_Product::get_post_type() . '=$matches[1]&post_type=' . MP_Product::get_post_type() . '&name=$matches[1]&mp_variation_id=$matches[2]';
		}
		
		// Order status
		if ( $post_id = mp_get_setting( 'pages->order_status' ) ) {
			$uri = get_page_uri( $post_id );
			$new_rules[ $uri . '/([^/]+)/?' ] = 'index.php?pagename=' . $uri . '&mp_order_id=$matches[1]';
		}
		
		return $rewrites + $new_rules;
	}
	
	/**
	 * Add custom query vars
	 *
	 * @since 3.0
	 * @access public
	 * @filter query_vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'mp_variation_id';
		$vars[] = 'mp_order_id';
		return $vars;
	}
	
	/**
	 * Make sure images use https protocol when displaying content over ssl
	 *
	 * @since 3.0
	 * @access public
	 * @filter wp_get_attachment_url
	 * @return string
	 */
	public function fix_insecure_images( $url, $post_id ) {
		//Skip file attachments
    if ( ! wp_attachment_is_image($post_id) ) {
    	return $url;
    }
    
    if ( is_ssl() ) {
	    $url = str_replace('http://', 'https://', $url);
    }
    
    return $url;
	}
	
	/**
	 * Maybe flush rewrite rules
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function maybe_flush_rewrites() {
		$flush_rewrites = get_option('mp_flush_rewrites');
		
		if ( $flush_rewrites === false ) {
			add_option('mp_flush_rewrites', 0);
		}
		
		if ( $flush_rewrites ) {
			flush_rewrite_rules();
			update_option('mp_flush_rewrites', 1);
		}
	}
	
	/**
	 * Get user billing info
	 *
	 * Before 3.0 only shipping info was captured. This function will return the
	 * shipping info if billing info doesn't exist for the given user.
	 *
	 * @since 3.0
	 * @access public
	 * @filter get_user_metadata
	 */
	public function get_user_billing_info( $value, $user_id, $meta_key, $single ) {
		if ( $meta_key != 'mp_billing_info' ) {
			return $value;
		}
		
		remove_filter('get_user_metadata', array(&$this, 'get_user_billing_info'));
		
		if ( metadata_exists('user', $user_id, 'mp_billing_info') ) {
			return $value;
		}
		
		add_filter('get_user_metadata', array(&$this, 'get_user_billing_info'), 10, 4);
		
		return get_user_meta($user_id, 'mp_shipping_info', false);
	}
	
	/**
	 * Include necessary files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function includes() {
		require_once $this->plugin_dir('includes/wpmudev-metaboxes/wpmudev-metabox.php');
		require_once $this->plugin_dir('includes/common/helpers.php');
		require_once $this->plugin_dir('includes/common/class-mp-addons.php');
		require_once $this->plugin_dir('includes/common/class-mp-order.php');
		require_once $this->plugin_dir('includes/common/class-mp-product.php');
		require_once $this->plugin_dir('includes/common/class-mp-installer.php');
		require_once $this->plugin_dir('includes/common/class-mp-shipping-api.php');
		require_once $this->plugin_dir('includes/common/class-mp-gateway-api.php');
		require_once $this->plugin_dir('includes/common/class-mp-product-attributes.php');
		require_once $this->plugin_dir('includes/common/template-functions.php');
		
		if ( is_admin() ) {
			require_once $this->plugin_dir('includes/admin/class-mp-admin.php');
			require_once $this->plugin_dir('includes/admin/class-mp-pages-admin.php');
			
			if ( is_multisite() ) {
				require_once $this->plugin_dir('includes/admin/class-mp-admin-multisite.php');
			}
			
			if ( mp_doing_ajax() ) {
				require_once $this->plugin_dir('includes/admin/class-mp-ajax.php');
				require_once $this->plugin_dir('includes/public/class-mp-public.php');
			}
		} else {
			require_once $this->plugin_dir('includes/public/class-mp-public.php');
		}
	}
	
	/**
	 * Catch deprecated functions
	 *
	 * @since 3.0
	 * @access public
	 */
	public function __call( $method, $args ) {
		switch ( $method ) {
			case 'get_download_url' :
				_deprecated_function( $method, '3.0', 'MP_Product::download_url' );
				$product = new MP_Product( $args[0] );
				return $product->download_url( $args[1], false );
			case 'mail' :
				_deprecated_function( $method, '3.0', 'mp_send_email' );
				return call_user_func_array( 'mp_send_email', $args );
			break;
			
			case 'order_notification' :
				_deprecated_function( $method, '3.0', 'MP_Order::send_notifications' );
				$order = new MP_Order( $args[0] );
				$order->send_notifications();
			break;
			
			case 'get_order' :
				_deprecated_function( $method, '3.0', 'MP_Order' );
			break;
			
			case 'low_stock_notification' :
				_deprecated_function( $method, '3.0', 'MP_Product::low_stock_notification' );
			break;
			
			case 'create_order' :
				_deprecated_function( $method, '3.0', 'MP_Order::save' );
			break;
			
			case 'generate_order_id' :
				_deprecated_function( $method, '3.0', 'MP_Order' );
				$order = new MP_Order();
				return $order->get_id();
			break;
			
			case 'cart_checkout_error' :
				_deprecated_function($method, '3.0', 'MP_Checkout::add_error OR MP_Checkout::get_error');
			break;
			
			case 'is_valid_zip' :
				_deprecated_function($method, '3.0', 'mp_is_valid_zip');
				return call_user_func_array('mp_is_valid_zip', $args);
			break;
			
			case 'coupon_applicable' :
				_deprecated_function($method, '3.0', 'MP_Coupon::is_applicable');
				$is_applicable = false;
				
				if ( class_exists('MP_Coupon') ) {
					$coupon = new MP_Coupon($args[0]);
					$is_applicable = $coupon->is_applicable($args[1]);
				}
				
				return $is_applicable;
			break;
			
			case 'download_only_cart' :
				_deprecated_function($method, '3.0', 'MP_Cart::download_only');
				$cart = MP_Cart::get_instance();
				$cart->set_id($args[0]);
				$is_download_only = $cart->is_download_only();
				$cart->reset_id();
				return $is_download_only;
			break;
			
			case 'get_setting' :
				_deprecated_function($method, '3.0', 'mp_get_setting');
				return call_user_func_array('mp_get_setting', $args);
			break;
			
			case 'format_currency' :
				_deprecated_function($method, '3.0', 'mp_format_currency');
				return call_user_func_array('mp_format_currency', $args);
			break;
			
			case 'format_date' :
				_deprecated_function($method, '3.0', 'mp_format_date');
				return call_user_func_array('mp_format_date', $args);
			break;
			
			case 'product_excerpt' :
				_deprecated_function($method, '3.0', 'mp_product_excerpt');
				return call_user_func_array('mp_product_excerpt', $args);
			break;
			
			case 'product_price' :
				_deprecated_function($method, '3.0', 'mp_product_price');
				return call_user_func_array('mp_product_price', $args);
			break;
			
			case 'shipping_price' :
				_deprecated_function($method, '3.0', 'MP_Cart::shipping_total');
				$mp_cart = mp_cart();
				return call_user_func_array(array($mp_cart, 'shipping_total'), $args);
			break;
			
			case 'tax_price' :
				_deprecated_function($method, '3.0', 'MP_Cart::tax_total');
				$mp_cart = mp_cart();
				return call_user_func_array(array($mp_cart, 'tax_total'), $args);
			break;
			
			default :
				trigger_error('Error! MarketPress doesn\'t have a ' . $method . ' method.', E_USER_ERROR);
			break;
		}
	}

	/**
	 * Initializes the class variables
	 *
	 * @since 3.0
	 * @access private
	 */	 
	private function _init_vars() {
		//setup proper directories
		$this->_plugin_file = __FILE__;
		$this->_plugin_dir = plugin_dir_path(__FILE__);
		$this->_plugin_url = plugin_dir_url(__FILE__);

		//load data structures
		require_once $this->plugin_dir('includes/common/data.php');
	}
}

$GLOBALS['mp'] = Marketpress::get_instance();