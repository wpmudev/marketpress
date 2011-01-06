<?php
/*
MarketPress Shipping Plugin Base Class
*/
if(!class_exists('MP_Shipping_API')) {

  class MP_Shipping_API {

    //private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
    var $plugin_name = '';
    
    //public name of your method, for lists and such.
    var $public_name = '';
    
    //set to true if you need to use the shipping_metabox() method to add per-product shipping options
    var $use_metabox = false;

    /****** Below are the public methods you may overwrite via a plugin ******/

    /**
     * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
     */
    function on_creation() {

		}

    /**
     * Echo anything you want to add to the top of the shipping screen
     */
		function before_shipping_form() {

    }
    
    /**
     * Echo anything you want to add to the bottom of the shipping screen
     */
		function after_shipping_form() {

    }
    
    /**
     * Echo a table row with any extra shipping fields you need to add to the form
     */
		function extra_shipping_field() {

    }
    
    /**
     * Use this to process any additional field you may add. Use the $_POST global,
     *  and be sure to save it to both the cookie and usermeta if logged in.
     */
		function process_shipping_form() {

    }
		
		/**
     * Echo a settings meta box with whatever settings you need for you shipping module.
     *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
     *  You can access saved settings via $settings array.
     */
		function shipping_settings_box($settings) {

    }
    
    /**
     * Filters posted data from your form. Do anything you need to the $settings['shipping']['plugin_name']
     *  array. Don't forget to return!
     */
		function process_shipping_settings($settings) {

      return $settings;
    }
    
    /**
     * Echo any per-product shipping fields you need to add to the product edit screen shipping metabox
     *
     * @param array $shipping_meta, the contents of the post meta. Use to retrieve any previously saved product meta
     * @param array $settings, access saved settings via $settings array.
     */
		function shipping_metabox($shipping_meta, $settings) {
      //it is required to override this method if $use_metabox is set to true
      if ($this->use_metabox)
        wp_die( __("You must override the shipping_metabox() method in your {$this->public_name} shipping plugin if \$use_metabox is set to true!", 'mp') );
    }
    
    /**
     * Save any per-product shipping fields from the shipping metabox using update_post_meta
     *
     * @param array|string $shipping_meta, save anything from the $_POST global
     * return array|string $shipping_meta
     */
		function save_shipping_metabox($shipping_meta) {
		
      return $shipping_meta;
    }
    
    /**
     * Use this function to return your calculated price as an integer or float
     *
     * @param int $price, always 0. Modify this and return
     * @param float $total, cart total after any coupons and before tax
     * @param array $cart, the contents of the shopping cart for advanced calculations
     * @param string $address1
     * @param string $address2
     * @param string $city
     * @param string $state, state/province/region
     * @param string $zip, postal code
     * @param string $country, ISO 3166-1 alpha-2 country code
     *
     * return float $price
     */
		function calculate_shipping($price, $total, $cart, $address1, $address2, $city, $state, $zip, $country) {
      //it is required to override this method
      wp_die( __("You must override the calculate_shipping() method in your {$this->public_name} shipping plugin!", 'mp') );
    }
    
		
		
		/****** Do not override any of these private methods please! ******/
		function _filter_method_lbl($lbl) {
      return $this->public_name;
    }
		
    //DO NOT override the construct! instead use the on_creation() method.
  	function MP_Shipping_API() {
  		$this->__construct();
  	}

    function __construct() {
      $this->on_creation();

      add_action( 'mp_checkout_before_shipping', array(&$this, 'before_shipping_form') );
      add_action( 'mp_checkout_after_shipping', array(&$this, 'after_shipping_form') );
      add_action( 'mp_checkout_shipping_field', array(&$this, 'extra_shipping_field') );
      add_action( 'mp_shipping_process', array(&$this, 'process_shipping_form') );
      //add_filter( 'mp_shipping_method_lbl', array(&$this, '_filter_method_lbl') );
      add_action( 'mp_shipping_settings', array(&$this, 'shipping_settings_box') );
      add_action( 'mp_shipping_settings_filter', array(&$this, 'process_shipping_settings') );
      add_action( 'mp_calculate_shipping', array(&$this, 'calculate_shipping'), 10, 9 );
      
      if ($this->use_metabox) {
        add_action( 'mp_shipping_metabox', array(&$this, 'shipping_metabox'), 10, 2 );
        add_filter( 'mp_save_shipping_meta', array(&$this, 'save_shipping_metabox') );
      }

  	}
  }
  
}

/**
 * Use this function to register your shipping plugin class
 *
 * @param string $plugin_name - the sanitized private name for your plugin
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $public_name - the public name of the plugin for lists and such
 */
function mp_register_shipping_plugin($class_name, $plugin_name, $public_name) {
  global $mp_shipping_plugins;
  
  if(!is_array($mp_shipping_plugins)) {
		$mp_shipping_plugins = array();
	}
	
	if(class_exists($class_name)) {
		$mp_shipping_plugins[$plugin_name] = array($class_name, $public_name);
	} else {
		return false;
	}
}
?>