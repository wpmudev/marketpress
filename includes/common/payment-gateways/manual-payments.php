<?php
/*
MarketPress Manual Payments Gateway Plugin
Author: Aaron Edwards (Incsub)
*/

class MP_Gateway_ManualPayments extends MP_Gateway_API {

  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'manual-payments';
  
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
  
  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('Manual Payments', 'mp');
		$this->public_name = $this->get_setting('name', __('Manual Payment', 'mp'));
    $this->method_img_url = mp()->plugin_url . 'images/manual-payment.png';
	}

  /**
   * Return fields you need to add to the payment screen, like your credit card info fields
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function payment_form($cart, $shipping_info) {
    return do_shortcode($this->get_setting('instructions'));
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
  }
  
  /**
   * Return the chosen payment details here for final confirmation. You probably don't need
   *  to post anything in the form as it should be in your $_SESSION var already.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
	function confirm_payment_form($cart, $shipping_info) {
  	;
  }

  /**
   * Use this to do the final payment. Create the order then process the payment. If
   *  you know the payment is successful right away go ahead and change the order status
   *  as well.
   *  Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
	function process_payment($cart, $shipping_info) {
	  $timestamp = time();
	  
    $totals = array();
    $coupon_code = mp()->get_coupon_code();
    
    foreach ($cart as $product_id => $variations) {
			foreach ($variations as $data) {
				$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);			
      	$totals[] = $price;
      }
    }
    $total = array_sum($totals);

		//shipping line
    $shipping_tax = 0;
    if ( ($shipping_price = mp()->shipping_price(false)) !== false ) {
			$total += $shipping_price;
			$shipping_tax = (mp()->shipping_tax_price($shipping_price) - $shipping_price);
    }

    //tax line if tax inclusive pricing is off. It it's on it would screw up the totals
    if ( ! mp_get_setting('tax->tax_inclusive') ) {
    	$tax_price = (mp()->tax_price(false) + $shipping_tax);
			$total += $tax_price;
    }
    
		$order_id = mp()->generate_order_id();

    $payment_info['gateway_public_name'] = $this->public_name;
    $payment_info['gateway_private_name'] = $this->admin_name;
    $payment_info['status'][$timestamp] = __('Invoiced', 'mp');
    $payment_info['total'] = $total;
    $payment_info['currency'] = mp_get_setting('currency');
	  $payment_info['method'] = __('Manual/Invoice', 'mp');
	  
    //create our order now
    $result = mp()->create_order($order_id, $cart, $shipping_info, $payment_info, false);
  }

  /**
   * Runs before page load incase you need to run any scripts before loading the success message page
   */
	function order_confirmation($order) {
    
  }

	/**
   * Filters the order confirmation email message body. You may want to append something to
   *  the message. Optional
   *
   * Don't forget to return!
   */
	function order_confirmation_email($msg, $order) {
	  if ( $email_text = $this->get_setting('email') ) {
			$msg = mp()->filter_email($order, $email_text);
		}
		  
    return $msg;
  }
  
  /**
   * Return any html you want to show on the confirmation screen after checkout. This
   *  should be a payment details box and message.
   *
   * Don't forget to return!
   */
	function order_confirmation_msg($content, $order) {
    return $content . str_replace('TOTAL', mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']), $this->get_setting('confirmation'));
  }

  /**
   * Initialize the settings metabox
   *
   * @since 3.0
   * @access public
   */
  public function init_settings_metabox() {
  	$metabox = new WPMUDEV_Metabox(array(
			'id' => $this->generate_metabox_id(),
			'screen_ids' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => sprintf(__('%s Settings', 'mp'), $this->admin_name),
			'option_name' => 'mp_settings',
			'desc' => __('Record payments manually, such as by Cash, Check, or EFT.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('name'),
			'default_value' => $this->public_name,
			'label' => array('text' => __('Method Name', 'mp')),
			'desc' => __('Enter a public name for this payment method that is displayed to users - No HTML', 'mp'),
			'save_callback' => array('strip_tags'),
		));
		$metabox->add_field('wysiwyg', array(
			'name' => $this->get_field_name('instruction'),
			'label' => array('text' => __('User Instructions', 'mp')),
			'desc' => __('These are the manual payment instructions to display on the payments screen.', 'mp'),
		));
		$metabox->add_field('wysiwyg', array(
			'name' => $this->get_field_name('confirmation'),
			'label' => array('text' => __('Confirmation User Instructions', 'mp')),
			'desc' => __('These are the manual payment instructions to display on the order confirmation screen. TOTAL will be replaced with the order total.', 'mp'),
		));
		$metabox->add_field('textarea', array(
			'name' => $this->get_field_name('email'),
			'label' => array('text' => __('Order Confirmation Email', 'mp')),
			'desc' => __('This is the email text to send to those who have made manual payment checkouts. You should include your manual payment instructions here. It overrides the default order checkout email. These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. No HTML allowed.', 'mp'),
			'custom' => array('rows' => 10),
			'save_callback' => array('strip_tags'),
		));
	}
	  
	/**
   * Use to handle any payment returns to the ipn_url. Do not display anything here. If you encounter errors
   *  return the proper headers. Exits after.
   */
	function process_ipn_return() {

  }
}

mp_register_gateway_plugin( 'MP_Gateway_ManualPayments', 'manual-payments', __('Manual Payments', 'mp') );