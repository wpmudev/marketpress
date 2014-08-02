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
	 * Update a user's preference
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param int $user_id The user ID (optional) defaults to current user ID
	 * @param string $key The preference to get
	 * @param mixed $value The value to set
	 */
	public function update_user_preference( $user_id = null, $key, $value ) {
		if ( is_null($user_id) ) {
			$user_id = get_current_user_id();
		}
		
		$prefs = (array) get_user_meta($user_id, 'mp_user_preferences', true);
		$prefs[$_POST['key']] = $_POST['value'];
		update_user_meta($user_id, 'mp_user_preferences', $prefs);
		
		exit;
	}
	
	/**
	 * Get a users preference
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param int $user_id The user ID (optional) defaults to current user ID
	 * @param string $key The preference to get
	 * @param mixed $default The default value to return if a preference isn't set
	 * @return mixed
	 */
	public function get_user_preference( $user_id = null, $key, $default = false ) {
		if ( is_null($user_id) ) {
			$user_id = get_current_user_id();
		}
		
		$prefs = get_user_meta($user_id, 'mp_user_preferences', true);
		return mp_arr_get_value($key, $prefs, $default);
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
		register_taxonomy('product_category', 'product', apply_filters('mp_register_product_category', array(
			'hierarchical' => true,
			'label' => __('Product Categories', 'mp'),
			'singular_label' => __('Product Category', 'mp'),
			'capabilities' => array(
				'manage_terms' => 'manage_product_categories',
				'edit_terms' => 'manage_product_categories',
				'delete_terms' => 'manage_product_categories',
				'assign_terms' => 'edit_products'
			),
			'show_ui' => false,
			'rewrite' => array(
				'with_front' => false,
				'slug' => mp_get_setting('slugs->store') . '/' . mp_get_setting('slugs->products') . '/' . mp_get_setting('slugs->category')
			),
		)));
		
		//! Register product_tag taxonomy
		register_taxonomy('product_tag', 'product', apply_filters('mp_register_product_tag', array(
			'hierarchical' => false,
			'label' => __('Product Tags', 'mp'),
			'singular_label' => __('Product Tag', 'mp'),
			'capabilities' => array(
				'manage_terms' => 'manage_product_tags',
				'edit_terms' => 'manage_product_tags',
				'delete_terms' => 'manage_product_tags',
				'assign_terms' => 'edit_products'
			),
			'show_ui' => false,
			'rewrite' => array(
				'with_front' => false,
				'slug' => mp_get_setting('slugs->store') . '/' . mp_get_setting('slugs->products') . '/' . mp_get_setting('slugs->tag')
			),
		)));

		//! Register product_coupon post type
		register_post_type('product_coupon', array(
			'labels' => array(
				'name' => __('Coupons', 'mp'),
				'singular_name' => __('Coupon', 'mp'),
				'menu_name' => __('Manage Coupons', 'mp'),
				'all_items' => __('Coupons', 'mp'),
				'add_new' => __('Create New', 'mp'),
				'add_new_item' => __('Create New Coupon', 'mp'),
				'edit_item' => __('Edit Coupon', 'mp'),
				'edit' => __('Edit', 'mp'),
				'new_item' => __('New Coupon', 'mp'),
				'view_item' => __('View Coupon', 'mp'),
				'search_items' => __('Search Coupons', 'mp'),
				'not_found' => __('No Coupons Found', 'mp'),
				'not_found_in_trash' => __('No Coupons found in Trash', 'mp'),
				'view' => __('View Coupon', 'mp')
			),
			'capabilities' => array(
				'edit_post' => 'edit_product_coupon',
        'edit_posts' => 'edit_product_coupons',
        'edit_others_posts' => 'edit_other_product_coupons',
        'publish_posts' => 'publish_product_coupons',
        'read_post' => 'read_product_coupon',
        'read_private_posts' => 'read_private_product_coupons',
        'delete_post' => 'delete_product_coupon'
      ),
			'map_meta_cap' => true,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,	// we're going to use admin_menu hook so we can require manage_options cap to edit
			'supports' => array(''),
		));

		//! Register product post type
		register_post_type('product' , apply_filters('mp_register_post_type', array(
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
			'capabilities' => array(
				'edit_post' => 'edit_product',
        'edit_posts' => 'edit_products',
        'edit_others_posts' => 'edit_other_products',
        'publish_posts' => 'publish_products',
        'read_post' => 'read_product',
        'read_private_posts' => 'read_private_products',
        'delete_post' => 'delete_product'
      ),
      'menu_icon' => ( version_compare($wp_version, '3.8', '>=') ) ? 'dashicons-cart' : mp_plugin_url('ui/images/marketpress-icon.png'),
			'map_meta_cap' => true,
			'hierarchical' => false,
			'rewrite' => array(
				'slug' => mp_get_setting('slugs->store') . '/' . mp_get_setting('slugs->products'),
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
		register_post_type('mp_order', apply_filters('mp_register_post_type_mp_order', array(
			'labels' => array(
				'name' => __('Orders', 'mp'),
				'singular_name' => __('Order', 'mp'),
				'edit' => __('Edit', 'mp'),
				'view_item' => __('View Order', 'mp'),
				'search_items' => __('Search Orders', 'mp'),
				'not_found' => __('No Orders Found', 'mp')
			),
			'description' => __('Orders from your e-commerce store.', 'mp'),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'capabilities' => array(
				'edit_post' => 'edit_order',
        'edit_posts' => 'edit_orders',
        'edit_others_posts' => 'edit_other_orders',
        'publish_posts' => 'publish_orders',
        'read_post' => 'read_order',
        'read_private_posts' => 'read_private_orders',
        'delete_post' => 'delete_order'
      ),
			'map_meta_cap' => true,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array(),
		)));
		
		//! Register product_variation post type
		register_post_type('product_variation', array(
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
		register_post_status('trash', array(
			'label'			 	=> _x('Trash', 'post' ),
			'label_count' => _n_noop('Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', 'mp'),
			'show_in_admin_status_list' => true,
			'post_type'	 	=> 'mp_order',
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
		if ( is_network_admin() || ! mp_get_setting('disable_cart') ) {
			require_once $this->plugin_dir('includes/common/class-mp-gateway-api.php');
			mp_include_dir($this->plugin_dir('includes/common/payment-gateways'));
			
			/**
			 * Fires after internal gateway plugins are loaded
			 *
			 * @since 3.0
			 */
			do_action('mp_load_gateway_plugins');
			
			MP_Gateway_API::load_active_gateways();
		}		
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
		
		// Load plugins
		add_action('init', array(&$this, 'load_plugins'));
		
		// Setup custom types
		add_action('init', array(&$this, 'register_custom_types'), 0);
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

$mp = Marketpress::get_instance();

// Include helper functions
require_once $mp->plugin_dir('includes/common/helpers.php');
// Include installer class
require_once $mp->plugin_dir('includes/common/class-mp-installer.php');
// Include product attributes class
require_once $mp->plugin_dir('includes/common/class-mp-product-attributes.php');
// Include MP_Cart class
require_once $mp->plugin_dir('includes/common/class-mp-cart.php');
// Include template functions
require_once $mp->plugin_dir('includes/common/template-functions.php');


if ( is_admin() ) {
	require_once $mp->plugin_dir('includes/admin/class-mp-admin.php');
	
	if ( mp_doing_ajax() ) {
		require_once $mp->plugin_dir('includes/admin/class-mp-ajax.php');
	}
} else {
	require_once $mp->plugin_dir('includes/public/class-mp-public.php');
}
