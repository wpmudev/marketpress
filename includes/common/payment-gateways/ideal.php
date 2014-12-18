<?php
/*
MarketPress iDeal Gateway Plugin
Author: Remi Schouten
*/

class MP_Gateway_IDeal extends MP_Gateway_API {

  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'ideal';
  
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
  var $skip_form = true;
  
  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('iDEAL (beta)', 'mp');
		$this->public_name = __('iDEAL', 'mp');

    $this->method_img_url = mp_plugin_url('images/ideal.png');
		$this->method_button_img_url = mp_plugin_url('images/ideal.png');
		$this->merchant_id = $this->get_setting('merchant_id');
		$this->ideal_hash = $this->get_setting('ideal_hash');
		$this->returnURL = mp_checkout_step_url('confirm-checkout');
  	$this->cancelURL = mp_checkout_step_url('checkout') . "?cancel=1";
		$this->errorURL = mp_checkout_step_url('checkout') . "?err=1";
	}

  /**
   * Return fields you need to add to the payment screen, like your credit card info fields
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function payment_form( $cart, $shipping_info ) {
		if ( mp_get_get_value('cancel') ) {
			echo '<div class="mp_checkout_error">' . __('Your iDEAL transaction has been canceled.', 'mp') . '</div>';
		}
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
		$key = $this->ideal_hash; // Your hashkey or Secret key
		$merchantID = $this->merchant_id; //Your merchant ID
		$subID = '0'; //Almost always 0
		$purchaseID = mp()->generate_order_id(); //Order ID
		$paymentType = 'ideal'; //Always ideal
		$validUntil = date('Y-m-d\TG:i:s\Z', strtotime('+1 hour'));
		$coupon_code = mp()->get_coupon_code();
		
		$i = 1;
		foreach ($cart as $product_id => $variations) {
			foreach ($variations as $data) {
				//we're sending tax included prices here isf tax included is on, to avoid rounding errors
				$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);
				$totals[] = $price;
				$items[] = array(
					'itemNumber'.$i => empty($data['SKU']) ? $product_id : substr($data['SKU'], 0, 12), // Article number
					'itemDescription'.$i => substr($data['name'], 0, 32), // Description
					'itemQuantity'.$i => $data['quantity'], // Quantity
					'itemPrice'.$i =>  round($price*100) // Artikel price in cents
				);
				$i++;
			}
		}
		$total = array_sum($totals);

		//shipping line
    $shipping_tax = 0;
    if ( ($shipping_price = mp()->shipping_price(false)) !== false ) {
			$total += $shipping_price;
			$shipping_tax = (mp()->shipping_tax_price($shipping_price) - $shipping_price);
			
			//Add shipping as separate product
			$items[] = array(
				'itemNumber'.$i => '99999998', // Product number
				'itemDescription'.$i => __('Shipping', 'mp'), // Description
				'itemQuantity'.$i => 1, // Quantity
				'itemPrice'.$i => round($shipping_price*100) // Product price in cents
			);
			$i++;
    }

    //tax line if tax inclusive pricing is off. It it's on it would screw up the totals
    if ( ! mp_get_setting('tax->tax_inclusive') ) {
    	$tax_price = (mp()->tax_price(false) + $shipping_tax);
			$total += $tax_price;
			
			//Add tax as separate product
			$items[] = array(
				'itemNumber'.$i => '99999999', // Product number
				'itemDescription'.$i => __('Tax', 'mp'), // Description
				'itemQuantity'.$i => 1, // Quantity
				'itemPrice'.$i => round($tax_price*100)  // Product price in cents
			);
    }
	
		$total = round($total * 100);
		$shastring = "$key$merchantID$subID$total$purchaseID$paymentType$validUntil";
		
		$i = 1;
		foreach ($items as $item){
			$shastring .= $item['itemNumber'.$i].$item['itemDescription'.$i].$item['itemQuantity'.$i].$item['itemPrice'.$i];	
			$i++;
		}
		
		// Remove HTML Entities
		$shastring = html_entity_decode($shastring);

		// Remove space characters: "\t", "\n", "\r" and " "
		$shastring = str_replace(array("\t", "\n", "\r", " "), '', $shastring);

		// Generate hash
		$shasign = sha1($shastring);

		// Other variables not part of the hash
		$language = 'nl'; # preferred '' for consistent texts, also quicker
		$currency = 'EUR';
		$description = substr( sprintf(__('%s Store Purchase - Order ID: %s', 'mp'), get_bloginfo('name'), $order_id), 0, 32 );
		$urlSuccess = $this->returnURL;
		$urlCancel = $this->cancelURL;
		$urlError = $this->errorURL;
		
		//setup bank specific urls
		$test = ( $this->get_setting('mode', 'test') == 'test');
		$redirectURL = 'https://www.ideal-simulator.nl/lite/?';
		
		switch ( $this->get_setting('bank') ) {
			case 'ing' :
				$redirectURL = 'https://ideal' . ($test ? 'test' : '') . '.secure-ing.com/ideal/mpiPayInitIng.do?';
				break;
			
			case 'rabo' :
				$redirectURL = 'https://ideal' . ($test ? 'test' : '') . '.rabobank.nl/ideal/mpiPayInitRabo.do?';
				break;
			
			case 'fries' :
				$redirectURL = 'https://' . ($test ? 'test' : '') . 'idealkassa.frieslandbank.nl/ideal/mpiPayInitFriesland.do?';
				break;
				
			case 'abn' :
				$redirectURL = 'https://abnamro' . ($test ? '-test' : '') . '.ideal-payment.de/ideal/mpiPayInitFortis.do?';
				break;
		}
			
		$redirectURL .= 'merchantID='.$merchantID;
		$redirectURL .='&subID='.$subID;
		$redirectURL .='&amount='.$total;
		$redirectURL .='&purchaseID='.$purchaseID;
		$redirectURL .='&language='.$language;
		$redirectURL .='&currency='.$currency;
		$redirectURL .='&description='.urlencode($description);
		$redirectURL .='&hash='.$shasign;
		$redirectURL .='&paymentType='.$paymentType;
		$redirectURL .='&validUntil='.$validUntil;
		
		$i = 1;
		foreach ($items as $item) {
			$redirectURL .= '&itemNumber'.$i.'='.$item['itemNumber'.$i].'&itemDescription'.$i.'='.$item['itemDescription'.$i].'&itemQuantity'.$i.'='.$item['itemQuantity'.$i].'&itemPrice'.$i.'='.$item['itemPrice'.$i];	
			$i++;
		}
	
		$redirectURL .='&urlSuccess='.urlencode($urlSuccess);
		$redirectURL .='&urlCancel='.urlencode($urlCancel);
		$redirectURL .='&urlError='.urlencode($urlError);
		
		//echo $redirectURL;
		wp_redirect($redirectURL);
		
		/*
		//create form
		$form = '
			<form method="post" action="https://ideal.secure-ing.com/ideal/mpiPayInitIng.do" name="form1">
			<!-- Vergeet na het uitvoeren van de testen niet de url in de ACTION te veranderen naar de productie-omgeving -->
			<input type="hidden" name="merchantID" value="'.$merchantID.'">
			<input type="hidden" name="subID" value="'.$subID.'">
			<input type="hidden" name="amount" value="'.$total.'">
			<input type="hidden" name="purchaseID" value="'.$purchaseID.'">
			<input type="hidden" name="language" value="'.$language.'">
			<input type="hidden" name="currency" value="'.$currency.'">
			<input type="hidden" name="description" value="'.$description.'">
			<input type="hidden" name="hash" value="'.$shasign.'">
			<input type="hidden" name="paymentType" value="'.$paymentType.'">
			<input type="hidden" name="validUntil" value="'.$validUntil.'">';
		
		//Add all products to the form
		$i = 1;
		foreach ($items as $item){
			$form .= '<input type="hidden" name="itemNumber'.$i.'" value="'.$item['itemNumber'.$i].'" />
			<input type="hidden" name="itemDescription'.$i.'" value="'.$item['itemDescription'.$i].'" />
			<input type="hidden" name="itemQuantity'.$i.'" value="'.$item['itemQuantity'.$i].'" />
			<input type="hidden" name="itemPrice'.$i.'" value="'.$item['itemPrice'.$i].'" />';	
			$i++;
		}		
		$form .= '
			<input type="hidden" name="urlSuccess" value="'.$urlSuccess.'" />
			<input type="hidden" name="urlCancel" value="'.$urlCancel.'" />
			<input type="hidden" name="urlError" value="'.$urlError.'" />
			<input type="submit" name="submit2" value="Verstuur" />
			</form>';
		echo $form;*/
		exit;
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
		$timestamp = time();
	  
		$totals = array();
		foreach ($cart as $product_id => $variations) {
			foreach ($variations as $data) {
				$totals[] = mp()->before_tax_price($data['price'], $product_id) * $data['quantity'];
			}
		}
		$total = array_sum($totals);
	
		if ( $coupon = mp()->coupon_value(mp()->get_coupon_code(), $total) ) {
			$total = $coupon['new_total'];
		}
		
		//shipping line
		if ( ($shipping_price = mp()->shipping_price()) !== false ) {
			$total = $total + $shipping_price;
		}
		
		//tax line
		if ( ($tax_price = mp()->tax_price()) !== false ) {
			$total = $total + $tax_price;
		}
	
		$payment_info['gateway_public_name'] = $this->public_name;
		$payment_info['gateway_private_name'] = $this->admin_name;
		$payment_info['status'][$timestamp] = __('Invoiced', 'mp');
		$payment_info['total'] = $total;
		$payment_info['currency'] = mp_get_setting('currency');
		$payment_info['method'] = __('iDEAL', 'mp');
		$payment_info['transaction_id'] = $order_id;
	
		//create our order now
		$result = mp()->create_order($order_id, $cart, $shipping_info, $payment_info, false);
		
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
		;
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
    return $msg;
  }
  
  /**
   * Return any html you want to show on the confirmation screen after checkout. This
   *  should be a payment details box and message.
   *
   * Don't forget to return!
   */
	function order_confirmation_msg($content, $order) {
    return $content;
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
			'page_slugs' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => sprintf(__('%s Settings', 'mp'), $this->admin_name),
			'option_name' => 'mp_settings',
			'desc' => __('To make it easier to pay for online products and services, the Dutch banking community has developed the iDEAL online payment method. iDEAL allows online payments to be made using online banking in EUR only.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('bank'),
			'label' => array('text' => __('Bank', 'mp')),
			'width' => 'element',
			'multiple' => false,
			'options' => array(
				'' => __('Select Your Bank', 'mp'),
				'ing' => __('ING Bank', 'mp'),
				'rabo' => __('Rabobank', 'mp'),
				'fries' => __('Friesland Bank', 'mp'),
				'abn' => __('ABN Amro Bank', 'mp'),
				'sim' => __('iDEAL Simulator (for testing)', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('merchant_id'),
			'label' => array('text' => __('Merchant ID', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('ideal_hash'),
			'label' => array('text' => __('Secret Key', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('mode'),
			'label' => array('text' => __('Mode', 'mp')),
			'default_value' => 'test',
			'options' => array(
				'test' => __('Test', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
  }
  
	/**
   * Use to handle any payment returns to the ipn_url. Do not display anything here. If you encounter errors
   *  return the proper headers. Exits after.
   */
	function process_ipn_return() {

  }
}

mp_register_gateway_plugin( 'MP_Gateway_IDeal', 'ideal', __('iDEAL (beta)', 'mp') );