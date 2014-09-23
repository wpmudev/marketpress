<?php

/**
 * MarketPress eWay Gateway Plugin
 *
 * @since 3.0
 *
 * @package MarketPress
 */

class MP_Gateway_eWay_Shared extends MP_Gateway_API {
	/**
	 * Build of the gateway plugin
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $build = 2;
	
  /**
   * Private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $plugin_name = 'eway';

	/**
   * Name of your gateway, for the admin side.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $admin_name = '';

	/**
   * Public name of your gateway, for lists and such.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $public_name = '';

	/**
   * Url for an image for your checkout method. Displayed on checkout form if set.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $method_img_url = '';

	/**
   * Url for an submit button image for your checkout method. Displayed on checkout form if set.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $method_button_img_url = '';

	/**
   * Whether or not ssl is needed for checkout page.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $force_ssl = false;

	/**
   * Always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $ipn_url;

	/**
   * Whether if this is the only enabled gateway it can skip the payment_form step.
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $skip_form = true;
  
  /**
   * The gateway's currencies 
   *
   * @since 3.0
   * @access public
   * @var array
   */
  var $currencies = array(
    'NZD' => 'NZD - New Zealand Dollar',
    'AUD' => 'AUD - Australian Dollar',
    'CAD' => 'CAD - Canadian Dollar',
    'EUR' => 'EUR - Euro',
    'GBP' => 'GBP - Pound Sterling',
    'HKD' => 'HKD - Hong Kong Dollar',
    'JPY' => 'JPY - Japanese Yen',
    'SGD' => 'SGD - Singapore Dollar',
    'USD' => 'USD - U.S. Dollar'
  );
  
  /**
   * The gateway's locales
   *
   * @since 3.0
   * @access public
   * @var array
   */
  var $locales = array(
    'EN'	=> 'English',
    'ES'	=> 'Spanish',
    'FR'	=> 'French',
    'DE'	=> 'German',
    'NL'	=> 'Dutch'
  );


  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //set names here to be able to translate
    $this->admin_name = __('eWay Shared Payments', 'mp');
    $this->public_name = __('Credit Card', 'mp');

    $this->method_img_url = mp()->plugin_url . 'images/credit_card.png';
    $this->method_button_img_url = mp()->plugin_url . 'images/cc-button.png';
 
    $this->returnURL = mp_checkout_step_url('confirmation');
  	$this->cancelURL = mp_checkout_step_url('checkout') . "?eway-cancel=1";

    //set api urls
  	if ( $this->get_setting('mode') == 'sandbox')	{
  		$this->CustomerID = '87654321';
  		$this->UserName = 'TestAccount';
  	} else {
  		$this->CustomerID = $this->get_setting('CustomerID');
  		$this->UserName = $this->get_setting('UserName');
    }
  }

/**
   * Return fields you need to add to the payment screen, like your credit card info fields
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function payment_form($cart, $shipping_info) {
    ;
    if (isset($_GET['eway-cancel'])) {
      echo '<div class="mp_checkout_error">' . __('Your eWay transaction has been canceled.', 'mp') . '</div>';
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
    ;
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
    //print payment details
    return '<img src="'.mp()->plugin_url . 'images/ewaylogo.png" border="0" alt="'.__('Checkout with eWay', 'mp').'">';
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
	function process_payment( $cart, $shipping_info ) {
    global $current_user;
    
    $timestamp = time();
    
    $order_id = mp()->generate_order_id();
    
    $params = array();
		$params['CustomerID'] = $this->CustomerID;
  	$params['UserName'] = $this->UserName;
		$params['MerchantInvoice'] = $order_id;
		$params['MerchantReference'] = $order_id;
		$params['Currency'] = $this->get_setting('Currency');
		$params['Language'] = $this->get_setting('Language');
		$params['ReturnURL'] = $this->returnURL;
		$params['CancelURL'] = $this->cancelURL;
		$params['ModifiableCustomerDetails'] = 'false';
		$params['InvoiceDescription'] = sprintf(__('%s Store Purchase - Order ID: %s', 'mp'), get_bloginfo('name'), $order_id); //cart name
		
		if ( $company_name = $this->get_setting('CompanyName') ) {
			$params['CompanyName'] = $company_name;
		}
		
		if ( $page_title = $this->get_setting('PageTitle') ) {
			$params['PageTitle'] = $page_title;
		}
		
		if ( $page_desc = $this->get_setting('PageDescription') ) {
			$params['PageDescription'] = $page_desc;
		}
		
		if ( $page_footer = $this->get_setting('PageFooter') ) {
			$params['PageFooter'] = $page_footer;
		}
		
		if ( $company_logo = $this->get_setting('CompanyLogo') ) {
			$params['CompanyLogo'] = $company_logo;
		}
		
		if ( $page_banner = $this->get_setting('PageBanner') ) {
			$params['PageBanner'] = $page_banner;
		}
			
		$params['CustomerEmail'] = mp_arr_get_value('email', $shipping_info);
		
		//add shipping info if set
		if ( ! mp()->download_only_cart($cart) && mp_get_setting('shipping->method') != 'none' && mp_arr_get_value('name', $shipping_info) ) {	
			$names = explode(' ', mp_arr_get_value('name', $shipping_info, ''));
			$params['CustomerFirstName'] = array_shift($names);
			$params['CustomerLastName'] = ( ! empty($names) ) ? array_shift($names) : ''; //grab last name
			$params['CustomerAddress'] = mp_arr_get_value('address1', $shipping_info);
			
			if ( $address2 = mp_arr_get_value('address2', $shipping_info) ) {
				$params['CustomerAddress'] = $params['CustomerAddress'] . ' ' . $address2;
			}
			
			$params['CustomerPhone'] = mp_arr_get_value('phone', $shipping_info);
			$params['CustomerPostCode'] = mp_arr_get_value('zip', $shipping_info);
			$params['CustomerCity'] = mp_arr_get_value('city', $shipping_info);
			$params['CustomerState'] = mp_arr_get_value('state', $shipping_info);
			$params['CustomerCountry'] = mp_arr_get_value('country', $shipping_info);
		}
    
    $totals = array();
		$product_count = 0;
		$coupon_code = mp()->get_coupon_code();
		
    foreach ( $cart as $product_id => $variations ) {
			foreach ( $variations as $data ) {
				$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);			
				$totals[] = $price;
				$product_count++;
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
				
    $params['Amount'] = number_format( round( $total, 2 ), 2, '.', '');
    
    $result = $this->api_call('https://au.ewaygateway.com/Request', $params);
		
		if ($result) {
			libxml_use_internal_errors(true);
			$xml = new SimpleXMLElement($result);
			if (!$xml) {
				mp()->cart_checkout_error( __('There was a problem parsing the response from eWay. Please try again.', 'mp') );
				return false;
			}

			if ($xml->Result == 'True') {
				wp_redirect($xml->URI);
				exit;
			} else {
				mp()->cart_checkout_error( sprintf(__('There was a problem setting up the transaction with eWay: %s', 'mp'), $xml->Error) );
				return false;
			}
		}
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
    $content = '';
		
		if (!$order)
			return '<p><a href="'.mp_checkout_step_url('confirm-checkout').'">' . __('Please go back and try again.', 'mp') . '</a></p>';
		
    if ($order->post_status == 'order_received') {
      $content .= '<p>' . sprintf(__('Your payment via eWay for this order totaling %s is in progress. Here is the latest status:', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total'])) . '</p>';
      $statuses = $order->mp_payment_info['status'];
      krsort($statuses); //sort with latest status at the top
      $status = reset($statuses);
      $timestamp = key($statuses);
      $content .= '<p><strong>' . mp()->format_date($timestamp) . ':</strong> ' . esc_html($status) . '</p>';
    } else {
      $content .= '<p>' . sprintf(__('Your payment for this order totaling %s is complete. The transaction number is <strong>%s</strong>.', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']), $order->mp_payment_info['transaction_id']) . '</p>';
    }
    return $content;
  }
  
  /**
   * Runs before page load incase you need to run any scripts before loading the success message page
   */
	function order_confirmation($order) {
		if ( $payment_code = mp_get_post_value('AccessPaymentCode') ) {
			$params = array();
			$params['CustomerID'] = $this->CustomerID;
			$params['UserName'] = $this->UserName;
			$params['AccessPaymentCode'] = $payment_code;
			
			$result = $this->api_call('https://au.ewaygateway.com/Result', $params);
			if ($result) {
				libxml_use_internal_errors(true);
				$xml = new SimpleXMLElement($result);
				if (!$xml) {
					mp()->cart_checkout_error( __('There was a problem parsing the response from eWay. Please try again.', 'mp') );
					return false;
				}

				if ($xml->TrxnStatus == 'True') {	
					$status = __('Received - The order has been received, awaiting payment confirmation.', 'mp');
					//setup our payment details
					$payment_info['gateway_public_name'] = $this->public_name;
					$payment_info['gateway_private_name'] = $this->admin_name;
					$payment_info['method'] = __('Credit Card', 'mp');
					$payment_info['transaction_id'] = (string)$xml->TrxnNumber;
					$timestamp = time();
					$payment_info['status'][$timestamp] = sprintf(__('Paid - The card has been processed - %s', 'mp'), (string)$xml->TrxnResponseMessage);
					$payment_info['total'] = (string)$xml->ReturnAmount;
					$payment_info['currency'] = $this->get_setting('Currency');
					
					$order = mp()->create_order(mp_get_session_value('mp_order'), mp()->get_cart_contents(), mp_get_session_value('mp_shipping_info'), $payment_info, true);
				} else {
					mp()->cart_checkout_error( sprintf(__('There was a problem with your credit card information: %s', 'mp'), $xml->TrxnResponseMessage) );
					wp_redirect($this->cancelURL);
					exit;
				}
			}
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
  	if ( $username = $this->get_setting('UserName') ) {
	  	mp_push_to_array($settings, 'gateways->eway->api_credentials->UserName', $username);
	  	unset($settings['gateways']['eway']['UserName']);
  	}
  	
  	if ( $cust_id = $this->get_setting('CustomerID') ) {
	  	mp_push_to_array($settings, 'gateways->eway->api_credentials->CustomerID', $cust_id);
	  	unset($settings['gateways']['eway']['CustomerID']);
  	}
  	
  	return $settings;
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
			'desc' => __('The Hosted Page is a webpage hosted on eWAY\'s side eliminating the need for merchants to capture, transmit or store credit card numbers. At the checkout time the merchant automatically redirects the customer to the Hosted Page where they would enter their details and have the transaction processed. Upon completion of the transaction the customer is redirected back to the MarketPress checkout confirmation page.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('Currency'),
			'label' => array('text' => __('Currency', 'mp')),
			'default_value' => mp_get_setting('currency'),
			'multiple' => false,
			'width' => 'element',
			'options' => $this->currencies,
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('mode'),
			'label' => array('text' => __('Gateway Mode', 'mp')),
			'default_value' => 'sandbox',
			'desc' => sprintf(__('Note when testing in sandbox mode it will use the default eWay test API credentials. You must also test in AUD currency as that is what the sandbox account is in. <a href="%s" target="_blank">It is important that you read and follow the testing instructions &raquo;</a>', 'mp'), 'http://www.eway.com.au/Developer/Testing/'),
			'options' => array(
				'sandbox' => __('Sandbox', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials'),
			'label' => array('text' => __('Live API Credentials', 'mp')),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'UserName',
				'label' => array('text' => __('Username', 'mp')),
			));
			$creds->add_field('text', array(
				'name' => 'CustomerID',
				'label' => array('text' => __('Customer ID', 'mp')),
			));
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('Language'),
			'label' => array('text' => __('Hosted Payment Page Language', 'mp')),
			'default_value' => 'EN',
			'multiple' => false,
			'width' => 'element',
			'options' => $this->locales,
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('CompanyName'),
			'label' => array('text' => __('Company Name', 'mp')),
			'desc' => __('This will be displayed as the company the customer is purchasing from, including this is highly recommended.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('PageTitle'),
			'label' => array('text' => __('Page Title (optional)', 'mp')),
			'desc' => __('This value is used to populate the browsers title bar at the top of the screen.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('PageDescription'),
			'label' => array('text' => __('Page Description (optional)', 'mp')),
			'desc' => __('This value is used to populate the browsers title bar at the top of the screen.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('PageFooter'),
			'label' => array('text' => __('Page Footer (optional)', 'mp')),
			'desc' => __('The page footer text can be customised and populated below the customer\'s order details. Useful for contact information.', 'mp'),
		));
		$metabox->add_field('file', array(
			'name' => $this->get_field_name('CompanyLogo'),
			'label' => array('text' => __('Company Logo (optional)', 'mp')),
			'desc' => __('The url of the image can be hosted on your website and pass the secure https:// path of the image to be displayed at the top of the website. This is the second image block on the webpage and is restricted to 960px X 65px. A default secure image is used if none is supplied.', 'mp'),
		));
		$metabox->add_field('file', array(
			'name' => $this->get_field_name('PageBanner'),
			'label' => array('text' => __('Company Logo (optional)', 'mp')),
			'desc' => __('The url of the image can be hosted on your website and pass the secure https:// path of the image to be displayed at the top of the website. This is the second image block on the webpage and is restricted to 960px X 65px. A default secure image is used if none is supplied.', 'mp'),
		));
	}
		
	/**
   * Use to handle any payment returns from your gateway to the ipn_url. Do not echo anything here. If you encounter errors
   *  return the proper headers to your ipn sender. Exits after.
   */
	function process_ipn_return() {
  }

	function api_call($url, $fields) {
	  $param_list = array();
    foreach ($fields as $k => $v) {
      $param_list[] = "{$k}=".rawurlencode($v);
    }

    $url .= '?' . implode('&', $param_list);
		
	  //build args
	  $args['user-agent'] = 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | eWay Shared Payments Gateway/' . MP_VERSION;
	  $args['sslverify'] = false;
	  $args['timeout'] = 60;

	  //use built in WP http class to work with most server setups
	  $response = wp_remote_get($url, $args);
	  if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
	    mp()->cart_checkout_error( __('There was a problem connecting to eWay. Please try again.', 'mp') );
	    return false;
	  } else {
	    return $response['body'];
	  }
	}


}

//register gateway only if SimpleXML module installed
if (class_exists("SimpleXMLElement"))
	mp_register_gateway_plugin( 'MP_Gateway_eWay_Shared', 'eway', __('eWay Shared Payments', 'mp') );