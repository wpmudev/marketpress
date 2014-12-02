<?php
/*
MarketPress Payment Gateway Plugin Base Class
*/
if( ! class_exists('MP_Gateway_API') ) {

  class MP_Gateway_API {
		//build of the gateway plugin
		var $build = null;
		
    //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
    var $plugin_name = '';
    
    //name of your gateway, for the admin side.
    var $admin_name = '';
    
    //public name of your gateway, for lists and such.
    var $public_name = '';
    
    //url for an image for your checkout method. Displayed on method form
    var $method_img_url = '';

    //url for an submit button image for your checkout method. Displayed on checkout form if set
    var $method_button_img_url = '';
    
    //whether or not ssl is needed for checkout page
    var $force_ssl = false;
    
    //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
    var $ipn_url;
    
    //whether if this is the only enabled gateway it can skip the payment_form step
    var $skip_form = false;
    
    //only required for global capable gateways. The maximum stores that can checkout at once
    var $max_stores = 1;
    
    /**
     * Refers to the registered gateways set by add_gateway()
     *
     * @since 3.0
     * @access private
     */
    private static $_gateways = array();
    
    /**
     * Refers to the active gateways
     *
     * @since 3.0
     * @access private
     */
    private static $_active_gateways = array();
    
    /**
     * Refers to the gateways that are loaded for the admin only (we need to load all the gateways for their settings)
     *
     * @since 3.0
     * @access private
     */
    private static $_active_gateways_admin = array();
    
    /**
     * Registers a gateway
     *
     * @since 3.0
     * @access public
     */
    public static function register_gateway( $plugin_name, $args ) {
	    self::$_gateways[$plugin_name] = $args;
    }
    
    /**
     * Gets all of the registered gateways
     *
     * @since 3.0
     * @access public
     * @param bool $network_enabled If multisite installation, only get gateways that are enabled in the store network settings.
     * @return array
     */
    public static function get_gateways( $network_enabled = false ) {
    	if ( is_multisite() && ! mp_is_main_site() && $network_enabled ) {
	    	$gateways = array();
	    	foreach ( self::$_gateways as $code => $gateway ) {
	    		$level = str_replace('psts_level_', '', mp_get_network_setting('allowed_gateways->' . $code, ''));
		    	if ( $level == 'full' || mp_is_pro_site(false, $level) ) {
			    	$gateways[$code] = $gateway;
		    	}
	    	}
	    	return $gateways;
    	}
    	
	    return self::$_gateways;
    }
    
    /**
     * Loads the active gateways
     *
     * @since 3.0
     * @access public
     * @return array
     */
    public static function load_active_gateways() {
    	if ( ! empty(self::$_active_gateways) ) {
    		// We already loaded the active gateways. No need to continue.
	    	return;
    	}
    	
			$gateways = mp_get_setting( 'gateways' );
			$network_enabled = ( is_multisite() && ! mp_is_main_site() && ! is_super_admin() ) ? true : false;
			
			foreach ( self::get_gateways($network_enabled) as $code => $plugin ) {
				$class = $plugin[0];
				
				// If global cart is enabled force it
				if ( mp_cart()->is_global ) {
					if ( $code == mp_get_network_setting('global_gateway') && class_exists($class) ) {
						self::$_active_gateways[$code] = new $class;
						break;
					}
				} elseif ( ! is_network_admin() ) {
					if ( is_admin() && ! mp_doing_ajax( 'mp_update_checkout_data' ) && class_exists($class) && ! array_key_exists($code, self::$_active_gateways) ) {
						// Load all gateways for admin
						self::$_active_gateways_admin[$code] = new $class;
					} elseif ( mp_arr_get_value("allowed->{$code}", $gateways) && class_exists($class) && ! $plugin[3] ) {
						self::$_active_gateways[$code] = new $class;
					}
				} elseif ( is_network_admin() ) {
					if ( ! $plugin[2] ) {
						continue;
					}
					
					self::$_active_gateways[$code] = new $class;
				}
			}
    }
    
    /**
     * Gets the active gateways
     *
     * @since 3.0
     * @access public
     * @return array
     */
    public static function get_active_gateways() {
	    return self::$_active_gateways;
    }
    
    /**
     * Generats the appropriate metabox ID for the gateway
     *
     * @since 3.0
     * @access private
     */
    public final function generate_metabox_id() {
    	if ( is_network_admin() ) {
	    	return 'mp-network-settings-gateway-' . strtolower($this->plugin_name);
    	} else {
	    	return 'mp-settings-gateway-' . strtolower($this->plugin_name);
    	}
    }
    
    /****** Below are the public methods you may overwrite via a plugin ******/
    
		function network_settings_save( $settings ) {
			return $settings;
		}
		
    /**
     * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
     */
    function on_creation() {
    }

    /**
     * Return fields you need to add to the payment screen, like your credit card info fields.
     *  If you don't need to add form fields set $skip_form to true so this page can be skipped
     *  at checkout.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function payment_form($cart, $shipping_info) {
    }
    
    /**
     * Use this to process any fields you added. Use the $_POST global,
     *  and be sure to save it to both the $_SESSION and usermeta if logged in.
     *  DO NOT save credit card details to usermeta as it's not PCI compliant.
     *  Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
     *  it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
		function process_payment_form($cart, $shipping_info) {
      wp_die( __("You must override the process_payment_form() method in your {$this->admin_name} payment gateway plugin!", 'mp') );
    }
    
    /**
     * Return the chosen payment details here for final confirmation. You probably don't need
     *  to post anything in the form as it should be in your $_SESSION var already.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
		function confirm_payment_form($cart, $shipping_info) {
      wp_die( __("You must override the confirm_payment_form() method in your {$this->admin_name} payment gateway plugin!", 'mp') );
    }

    /**
     * Use this to do the final payment. Create the order then process the payment. If
     *  you know the payment is successful right away go ahead and change the order status
     *  as well.
     *  Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
     *  it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
     * @param array $billing_info. Contains billing info and email in case you need it
     */
		function process_payment ( $cart, $billing_info ) {
      wp_die( __("You must override the process_payment() method in your {$this->admin_name} payment gateway plugin!", 'mp') );
    }

    /**
     * Runs before page load incase you need to run any scripts before loading the success message page
     */
		function order_confirmation($order) {
      wp_die( __("You must override the order_confirmation() method in your {$this->admin_name} payment gateway plugin!", 'mp') );
    }

		/**
     * Filters the order confirmation email message body. You may want to append something to
     *  the message. Optional
     *
     * Don't forget to return!
     */
		function order_confirmation_email($msg, $order) {
      return $msg;
    }
    
    /**
	   * Return any html you want to show on the confirmation screen after checkout. This
	   *  should be a payment details box and message.
	   *
	   * Don't forget to return!
	   */
		function order_confirmation_msg($content, $order) {
      wp_die( __("You must override the order_confirmation_msg() method in your {$this->admin_name} payment gateway plugin!", 'mp') );
    }
		
		/**
     * Use to handle any payment returns to the ipn_url. Do not display anything here. If you encounter errors
     *  return the proper headers. Exits after.
     */
		function process_ipn_return() {

    }
		
		/****** Do not override any of these private methods please! ******/
		
		//populates ipn_url var
		function _generate_ipn_url() {
      $this->ipn_url = home_url(mp_get_setting('slugs->store') . '/payment-return/' . $this->plugin_name);
    }
    
		//populates ipn_url var
		function _payment_form_skip($var) {
			return $this->skip_form;
    }
    
		//creates the payment method selections
		function _payment_form_wrapper($content, $cart = null, $billing_info = null) {
			if ( is_null($cart) ) {
				$cart = mp_cart()->get_items();
			}
			
			if ( is_null($billing_info) ) {
				$billing_info = mp_get_user_address('billing');
			}
			
      $hidden = (count(self::$_active_gateways) > 1 && mp_get_session_value('mp_payment_method') != $this->plugin_name) ? ' style="display:none;"' : '';
        
      $content .= '<div class="mp_gateway_form" id="mp-gateway-form-' . $this->plugin_name . '"' . $hidden . '>';
      $content .= $this->payment_form($cart, $billing_info);
      $content .= '</div>';
      
      return $content;
    }
    
    //calls the order_confirmation() method on the correct page
    function _checkout_confirmation_hook() {
      global $wp_query;

      if ($wp_query->query_vars['pagename'] == 'cart') {
        if (isset($wp_query->query_vars['checkoutstep']) && $wp_query->query_vars['checkoutstep'] == 'confirmation')
          do_action( 'mp_checkout_payment_pre_confirmation_' . $_SESSION['mp_payment_method'], mp()->get_order($_SESSION['mp_order']) );
      }
    }
    
    /**
     * Initialize the settings metabox
     *
     * @since 3.0
     * @access public
     */
    public function init_settings_metabox() {
	    // Override in child gateway
    }

    /**
     * Initialize the network settings metabox
     *
     * @since 3.0
     * @access public
     */
    public function init_network_settings_metabox() {
	    // Override in child gateway
    }
    
    /**
     * Generates an appropriate field name
     *
     * @since 3.0
     * @access public
     * @param string $name The name of the field (e.g. name->subname1->subname2).
     * @return string
     */
    public function get_field_name( $name ) {
    	$name_parts = explode('->', $name);
    	
    	foreach ( $name_parts as &$part ) {
	    	$part = '[' . $part . ']';
    	}
    	
	    return "gateways[{$this->plugin_name}]" . implode($name_parts);
    }
    
    /**
     * Gets a setting specific to the gateway
     *
     * @since 3.0
     * @access public
     * @param string $setting
     * @param mixed $default
     * @return mixed
     */
    public function get_setting( $setting, $default = false ) {
	    return mp_get_setting("gateways->" . $this->plugin_name . "->{$setting}", $default);
    }
    
    /**
     * Gets a network setting specific to the gateway
     *
     * @since 3.0
     * @access public
     * @param string $setting
     * @param mixed $default
     * @return mixed
     */
    public function get_network_setting( $setting, $default = false ) {
	    return mp_get_network_setting("gateways->" . $this->plugin_name . "->{$setting}", $default);
    }
    
    /**
     * Determines if the gateway settings needs to be updated
     *
     * @since 3.0
     * @access public
     */
    public final function maybe_update() {
	    if ( ! is_null($this->build) && $this->build != $this->get_setting('build') ) {
	    	$old_settings = get_option('mp_settings');
		    $settings = $this->update($old_settings);
		    $settings['gateways'][$this->plugin_name]['build'] = $this->build;
		    update_option('mp_settings', $settings);
	    }
    }
    
    /**
     * Updates the gateway settings
     *
     * @since 3.0
     * @access public
     * @param array $settings
     * @return array
     */
    public function update( $settings ) {
	    return $settings;
    }
    
    //DO NOT override the construct! instead use the on_creation() method.
    function __construct() {
    	$this->maybe_update();
    
      $this->_generate_ipn_url();
      
      //run plugin construct
      $this->on_creation();
      
      //check required vars
      if ( empty( $this->plugin_name ) || empty( $this->admin_name ) || empty( $this->public_name ) )
        wp_die( __("You must override all required vars in your {$this->admin_name} payment gateway plugin!", 'mp') );

      /*add_filter('mp_checkout_payment_form', array(&$this, '_payment_form_wrapper'), 10, 3);
      add_action('template_redirect', array(&$this, '_checkout_confirmation_hook'));
      add_filter('mp_payment_form_skip_' . $this->plugin_name, array(&$this, '_payment_form_skip'));
      add_action('mp_payment_submit_' . $this->plugin_name, array(&$this, 'process_payment_form'), 10, 2);
      add_filter('mp_checkout_confirm_payment_' . $this->plugin_name, array(&$this, 'confirm_payment_form'), 10, 2);
      add_action('mp_payment_confirm_' . $this->plugin_name, array(&$this, 'process_payment'), 10, 2);
      add_filter('mp_order_notification_' . $this->plugin_name, array(&$this, 'order_confirmation_email'), 10, 2);
      add_action('mp_checkout_payment_pre_confirmation_' . $this->plugin_name, array(&$this, 'order_confirmation'));
      add_filter('mp_checkout_payment_confirmation_' . $this->plugin_name, array(&$this, 'order_confirmation_msg'), 10, 2);
      add_action('mp_handle_payment_return_' . $this->plugin_name, array(&$this, 'process_ipn_return') );*/
      
      add_filter( 'mp_checkout_payment_form', array( &$this, '_payment_form_wrapper' ), 10, 3 );
      add_action( 'mp_process_payment_' . $this->plugin_name, array( &$this, 'process_payment' ), 10, 3 );

			if ( is_admin() ) {
      	$this->init_settings_metabox();
      }
      
      if ( is_network_admin() ) {
	      $this->init_network_settings_metabox();
      }
  	}
  }
  
}

if ( ! function_exists('mp_register_gateway_plugin') ) :
	/**
	 * Use this function to register your gateway plugin class
	 *
	 * @param string $class_name - the case sensitive name of your plugin class
	 * @param string $plugin_name - the sanitized private name for your plugin
	 * @param string $admin_name - pretty name of your gateway, for the admin side.
	 * @param bool $global optional - whether the gateway supports global checkouts
	 */
	function mp_register_gateway_plugin( $class_name, $plugin_name, $admin_name, $global = false, $demo = false ) {	
		if ( class_exists($class_name) ) {
			MP_Gateway_API::register_gateway($plugin_name, array($class_name, $admin_name, $global, $demo));
		} else {
			return false;
		}
	}
endif;