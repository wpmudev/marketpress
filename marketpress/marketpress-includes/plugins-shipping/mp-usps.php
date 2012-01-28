<?php
/*
MarketPress USPS Calculated Shipping Plugin
Author: Aaron Edwards (Incsub)
*/
class MP_Shipping_USPS extends MP_Shipping_API {

  //private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'usps';
  
  //public name of your method, for lists and such.
  var $public_name = '';
  
  //set to true if you need to use the shipping_metabox() method to add per-product shipping options
  var $use_metabox = false;
	
	//set to true if you want to add per-product extra shipping cost field
	var $use_extra = false;
	
	//set to true if you want to add per-product weight shipping field
	var $use_weight = true;
	
	
  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //set name here to be able to translate
    $this->public_name = __('USPS', 'mp');
	}

  /**
   * Echo anything you want to add to the top of the shipping screen
   */
	function before_shipping_form($content) {
		return $content;
  }
  
  /**
   * Echo anything you want to add to the bottom of the shipping screen
   */
	function after_shipping_form($content) {
		return $content;
  }
  
  /**
   * Echo a table row with any extra shipping fields you need to add to the shipping checkout form
   */
	function extra_shipping_field($content) {
		return $content;
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
    global $mp;
    ?>
    <div id="mp_flat_rate" class="postbox">
      <h3 class='hndle'><span><?php _e('USPS Settings', 'mp'); ?></span></h3>
      <div class="inside">
        <span class="description"><?php _e('Here is how to get your api credentials...', 'mp') ?></span>
        <table class="form-table">

        </table>
      </div>
    </div>
    <?php
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

  }

  /**
   * Save any per-product shipping fields from the shipping metabox using update_post_meta
   *
   * @param array $shipping_meta, save anything from the $_POST global
   * return array $shipping_meta
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
    global $mp;
    $settings = get_option('mp_settings');

    //merge
    $price = 7.99;
    
    return $price;
  }
	
	/**
	* For calculated shipping modules, use this method to return an associative array of the sub-options. The key will be what's saved as selected
	*  in the session. Note the shipping parameters won't always be set. If they are, add the prices to the labels for each option.
	*
	* @param string $address1
	* @param string $address2
	* @param string $city
	* @param string $state, state/province/region
	* @param string $zip, postal code
	* @param string $country, ISO 3166-1 alpha-2 country code
	*
	* return array $shipping_options 
	*/
	function shipping_options($address1, $address2, $city, $state, $zip, $country) {
	 
		$shipping_options = array( 'priority' => __('Priority Mail', 'mp'),
															'parcel' => __('Parcel Post', 'mp'),
															'express' => __('Express Mail', 'mp'));
	 
	 return $shipping_options;
	}
	
}

//register plugin as calculated
mp_register_shipping_plugin( 'MP_Shipping_USPS', 'usps', __('USPS', 'mp'), true );
?>