<?php
/*
MarketPress eWay Rapid 3.1 Payments Gateway Plugin
Author: Mariusz Maniu (Incsub)
*/

class MP_Gateway_eWay31 extends MP_Gateway_API {
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
	var $plugin_name = 'eway31';

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
	 * Whether or not ssl is needed for checkout page
	 *
	 * @since 3.0
	 * @access public
	 * @var bool
	 */
	var $force_ssl = true;

	/**
	 * Always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class.
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	var $ipn_url;

	/**
	 * whether if this is the only enabled gateway it can skip the payment_form step
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	var $skip_form = false;
	
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
	 * The gateway's error code mappings
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $error_map = array(
		'F7000' => 'Undefined Fraud',
		'V5000' => 'Undefined System',
		'A0000' => 'Undefined Approved',
		'A2000' => 'Transaction Approved',
		'A2008' => 'Honour With Identification',
		'A2010' => 'Approved For Partial Amount',
		'A2011' => 'Approved VIP',
		'A2016' => 'Approved Update Track 3',
		'V6000' => 'Undefined Validation',
		'V6001' => 'Invalid Request CustomerIP',
		'V6002' => 'Invalid Request DeviceID',
		'V6011' => 'Invalid Payment Amount',
		'V6012' => 'Invalid Payment InvoiceDescription',
		'V6013' => 'Invalid Payment InvoiceNumber',
		'V6014' => 'Invalid Payment InvoiceReference',
		'V6015' => 'Invalid Payment CurrencyCode',
		'V6016' => 'Payment Required',
		'V6017' => 'Payment CurrencyCode Required',
		'V6018' => 'Unknown Payment CurrencyCode',
		'V6021' => 'Cardholder Name Required',
		'V6022' => 'Card Number Required',
		'V6023' => 'CVN Required',
		'V6031' => 'Invalid Card Number',
		'V6032' => 'Invalid CVN',
		'V6033' => 'Invalid Expiry Date',
		'V6034' => 'Invalid Issue Number',
		'V6035' => 'Invalid Start Date',
		'V6036' => 'Invalid Month',
		'V6037' => 'Invalid Year',
		'V6040' => 'Invaild Token Customer Id',
		'V6041' => 'Customer Required',
		'V6042' => 'Customer First Name Required',
		'V6043' => 'Customer Last Name Required',
		'V6044' => 'Customer Country Code Required',
		'V6045' => 'Customer Title Required',
		'V6046' => 'Token Customer ID Required',
		'V6047' => 'RedirectURL Required',
		'V6051' => 'Invalid Customer First Name',
		'V6052' => 'Invalid Customer Last Name',
		'V6053' => 'Invalid Customer Country Code',
		'V6054' => 'Invalid Customer Email',
		'V6055' => 'Invalid Customer Phone',
		'V6056' => 'Invalid Customer Mobile',
		'V6057' => 'Invalid Customer Fax',
		'V6058' => 'Invalid Customer Title',
		'V6059' => 'Redirect URL Invalid',
		'V6060' => 'Redirect URL Invalid',
		'V6061' => 'Invaild Customer Reference',
		'V6062' => 'Invaild Customer CompanyName',
		'V6063' => 'Invaild Customer JobDescription',
		'V6064' => 'Invaild Customer Street1',
		'V6065' => 'Invaild Customer Street2',
		'V6066' => 'Invaild Customer City',
		'V6067' => 'Invaild Customer State',
		'V6068' => 'Invaild Customer Postalcode',
		'V6069' => 'Invaild Customer Email',
		'V6070' => 'Invaild Customer Phone',
		'V6071' => 'Invaild Customer Mobile',
		'V6072' => 'Invaild Customer Comments',
		'V6073' => 'Invaild Customer Fax',
		'V6074' => 'Invaild Customer Url',
		'V6075' => 'Invaild ShippingAddress FirstName',
		'V6076' => 'Invaild ShippingAddress LastName',
		'V6077' => 'Invaild ShippingAddress Street1',
		'V6078' => 'Invaild ShippingAddress Street2',
		'V6079' => 'Invaild ShippingAddress City',
		'V6080' => 'Invaild ShippingAddress State',
		'V6081' => 'Invaild ShippingAddress PostalCode',
		'V6082' => 'Invaild ShippingAddress Email',
		'V6083' => 'Invaild ShippingAddress Phone',
		'V6084' => 'Invaild ShippingAddress Country',
		'V6091' => 'Unknown Country Code',
		'V6100' => 'Invalid ProcessRequest name',
		'V6101' => 'Invalid ProcessRequest ExpiryMonth',
		'V6102' => 'Invalid ProcessRequest ExpiryYear',
		'V6103' => 'Invalid ProcessRequest StartMonth',
		'V6104' => 'Invalid ProcessRequest StartYear',
		'V6105' => 'Invalid ProcessRequest IssueNumber',
		'V6106' => 'Invalid ProcessRequest CVN',
		'V6107' => 'Invalid ProcessRequest AccessCode',
		'V6108' => 'Invalid ProcessRequest CustomerHostAddress',
		'V6109' => 'Invalid ProcessRequest UserAgent',
		'V6110' => 'Invalid ProcessRequest Number',
		'D4401' => 'Refer to Issuer',
		'D4402' => 'Refer to Issuer, special',
		'D4403' => 'No Merchant',
		'D4404' => 'Pick Up Card',
		'D4405' => 'Do Not Honour',
		'D4406' => 'Error',
		'D4407' => 'Pick Up Card, Special',
		'D4409' => 'Request In Progress',
		'D4412' => 'Invalid Transaction',
		'D4413' => 'Invalid Amount',
		'D4414' => 'Invalid Card Number',
		'D4415' => 'No Issuer',
		'D4419' => 'Re-enter Last Transaction',
		'D4421' => 'No Method Taken',
		'D4422' => 'Suspected Malfunction',
		'D4423' => 'Unacceptable Transaction Fee',
		'D4425' => 'Unable to Locate Record On File',
		'D4430' => 'Format Error',
		'D4431' => 'Bank Not Supported By Switch',
		'D4433' => 'Expired Card, Capture',
		'D4434' => 'Suspected Fraud, Retain Card',
		'D4435' => 'Card Acceptor, Contact Acquirer, Retain Card',
		'D4436' => 'Restricted Card, Retain Card',
		'D4437' => 'Contact Acquirer Security Department, Retain Card',
		'D4438' => 'PIN Tries Exceeded, Capture',
		'D4439' => 'No Credit Account',
		'D4440' => 'Function Not Supported',
		'D4441' => 'Lost Card',
		'D4442' => 'No Universal Account',
		'D4443' => 'Stolen Card',
		'D4444' => 'No Investment Account',
		'D4451' => 'Insufficient Funds',
		'D4452' => 'No Cheque Account',
		'D4453' => 'No Savings Account',
		'D4454' => 'Expired Card',
		'D4455' => 'Incorrect PIN',
		'D4456' => 'No Card Record',
		'D4457' => 'Function Not Permitted to Cardholder',
		'D4458' => 'Function Not Permitted to Terminal',
		'D4460' => 'Acceptor Contact Acquirer',
		'D4461' => 'Exceeds Withdrawal Limit',
		'D4462' => 'Restricted Card',
		'D4463' => 'Security Violation',
		'D4464' => 'Original Amount Incorrect',
		'D4466' => 'Acceptor Contact Acquirer, Security',
		'D4467' => 'Capture Card',
		'D4475' => 'PIN Tries Exceeded',
		'D4482' => 'CVV Validation Error',
		'D4490' => 'Cutoff In Progress',
		'D4491' => 'Card Issuer Unavailable',
		'D4492' => 'Unable To Route Transaction',
		'D4493' => 'Cannot Complete, Violation Of The Law',
		'D4494' => 'Duplicate Transaction',
		'D4496' => 'System Error',
	);		


	/****** Below are the public methods you may overwrite via a plugin ******/

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 *
	 * @since 3.0
	 * @access public
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('eWay Rapid 3.1 Payments (beta)', 'mp');
		$this->public_name = __('Credit Card', 'mp');
		
		$this->method_img_url = mp_plugin_url('images/credit_card.png');
		$this->method_button_img_url = mp_plugin_url('images/cc-button.png');
		
		$this->returnURL = mp_checkout_step_url('confirmation');
		
		//sets eway api settings
		if ( $this->get_setting('mode') == 'rapid30live' ) {
			$this->UserAPIKey =  $this->get_setting('api_credentials->live->api_key');
			$this->UserPassword =  $this->get_setting('api_credentials->live->api_pass');
		} else if ( $this->get_setting('mode') == 'rapid30sandbox' ) {
			$this->UserAPIKey = $this->get_setting('api_credentials->sandbox->api_key');
			$this->UserPassword = $this->get_setting('api_credentials->sandbox->api_pass');
		} else {
			$this->UserAPIKey = '';
			$this->UserPassword = '';
		}
	}

	/**
	 * Return fields you need to add to the payment screen, like your credit card info fields
	 *
	 * @since 3.0
	 * @access public
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		return $this->_cc_default_form();
	}
	
	/**
	 * Use this to do the final payment. Create the order then process the payment. If
	 * you know the payment is successful right away go ahead and change the order status
	 * as well.
	 *
	 * @param MP_Cart $cart. Contains the MP_Cart object.
	 * @param array $billing_info. Contains billing info and email in case you need it.
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function process_payment( $cart, $billing_info, $shipping_info ) {
		$timestamp = time();
		
		$order = new MP_Order();
		$order_id = $order->get_id();
		$total = $cart->total( false );
		$product_count = 0;
		$amount = number_format( round( $total, 2 ), 2, '.', '');
			
		require_once mp_plugin_dir( 'includes/common/payment-gateways/eway/RapidAPI.php' );

		$eway_service = new RapidAPI( $this->UserAPIKey, $this->UserPassword, array( 'sandbox' => ( $this->get_setting( 'mode' ) == 'rapid30sandbox' ) ) );
	
		// Create AccessCode Request Object
		$request = new CreateDirectPaymentRequest();
		$request->Customer->Reference = 'MarketPress';
		
		// Card Info
		$exp = explode( '/', mp_get_post_value( 'mp_cc_exp', '' ) );
    $request->Customer->CardDetails->Name = mp_arr_get_value( 'first_name', $billing_info ) . ' ' . mp_arr_get_value( 'last_name', $billing_info );
    $request->Customer->CardDetails->Number = mp_get_post_value( 'mp_cc_num' );
    $request->Customer->CardDetails->ExpiryMonth = trim( $exp[0] );
    $request->Customer->CardDetails->ExpiryYear = trim( $exp[1] );
    $request->Customer->CardDetails->CVN = mp_get_post_value( 'mp_cc_cvc' );

		// Billing Info
		$request->Customer->FirstName = mp_arr_get_value( 'first_name', $billing_info );
		$request->Customer->LastName = mp_arr_get_value( 'last_name', $billing_info );
		$request->Customer->Street1 = mp_arr_get_value( 'address1', $billing_info );
		
		if ( $address2 = mp_arr_get_value( 'address2', $billing_info ) ) {
			$request->Customer->Street2 = mp_arr_get_value('address2', $billing_info);
		}
		
		$request->Customer->Phone = mp_arr_get_value( 'phone', $billing_info );	
		$request->Customer->City = mp_arr_get_value( 'city', $billing_info );
		$request->Customer->State = mp_arr_get_value( 'state', $billing_info );
		$request->Customer->PostalCode = mp_arr_get_value( 'zip', $billing_info );
		$request->Customer->Country = mp_arr_get_value( 'country', $billing_info );		
		$request->Customer->Email = mp_arr_get_value( 'email', $shipping_info );
		$request->Customer->Mobile = '';

		if ( ! $cart->is_download_only() && mp_get_setting( 'shipping->method' ) != 'none' && mp_get_post_value( 'enable_shipping_address' ) ) {	
			$request->ShippingAddress->FirstName = mp_arr_get_value( 'first_name', $billing_info );
			$request->ShippingAddress->LastName = mp_arr_get_value( 'last_name', $billing_info );
			$request->ShippingAddress->Street1 = mp_arr_get_value( 'address1', $billing_info );
			
			if ( $address2 = mp_arr_get_value( 'address2', $billing_info ) ) {
				$request->ShippingAddress->Street2 = mp_arr_get_value('address2', $billing_info);
			}
			
			$request->ShippingAddress->Phone = mp_arr_get_value( 'phone', $billing_info );	
			$request->ShippingAddress->City = mp_arr_get_value( 'city', $billing_info );
			$request->ShippingAddress->State = mp_arr_get_value( 'state', $billing_info );
			$request->ShippingAddress->PostalCode = mp_arr_get_value( 'zip', $billing_info );
			$request->ShippingAddress->Country = mp_arr_get_value( 'country', $billing_info );		
			$request->ShippingAddress->Email = mp_arr_get_value( 'email', $shipping_info );
			$request->ShippingAddress->ShippingMethod = 'Unknown';
		}
		
	
		$request->Payment->TotalAmount = ($total * 100);
		$request->Payment->InvoiceNumber = $order_id;
		$request->Payment->InvoiceDescription = '';
		$request->Payment->InvoiceReference = '';
		$request->Payment->CurrencyCode = $this->get_setting( 'Currency' );
	
		$request->Method = 'ProcessPayment';
	
		//Call RapidAPI
		$result = $eway_service->DirectPayment( $request );
	
		if ( isset( $result->Errors ) ) {
			//Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
			$ErrorArray = explode(',', trim( $result->Errors ) );
			$lblError = '';
			foreach ( $ErrorArray as $error ) {
				if ( isset( $this->error_map[ $error ] ) ) {
					$lblError .= '<li>' . $error . ' ' . $this->error_map[ $error ] . '</li>';
				} else {
					$lblError .= '<li>' . $error . '</li>';
				}
			}
		}
	
		if ( $isError ) {
			mp_checkout()->add_error( $lblError, 'order-review-payment' );
		} else {
			$payment_info = array();
			$payment_info['gateway_public_name'] = $this->public_name;
			$payment_info['gateway_private_name'] = $this->admin_name;
			$payment_info['method'] = "eWay payment";
			$payment_info['status'][ $timestamp ] = "paid";
			$payment_info['total'] = $amount;
			$payment_info['currency'] = $this->get_setting( 'Currency' );
			$payment_info['transaction_id'] = $result->TransactionID;
	  
			$order->save( array(
				'cart' => $cart,
				'payment_info' => $payment_info,
				'paid' => true,
			) );
			
			wp_redirect( $order->tracking_url( false ) );
			exit;
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
  	$settings = get_option('mp_settings');
  	
  	if ( $api_key = $this->get_setting('UserAPIKeyLive') ) {
	  	mp_push_to_array($settings, 'api_credentials->live->api_key', $api_key);
	  	unset($settings['gateways']['eway30']['UserAPIKeyLive']);
  	}
  	
  	if ( $api_pass = $this->get_setting('UserPasswordLive') ) {
	  	mp_push_to_array($settings, 'api_credentials->live->api_pass', $api_key);
	  	unset($settings['gateways']['eway30']['UserPasswordLive']);
  	}
  	
  	if ( $api_key = $this->get_setting('UserAPIKeySandbox') ) {
	  	mp_push_to_array($settings, 'api_credentials->sandbox->api_key', $api_key);
	  	unset($settings['gateways']['eway30']['UserAPIKeySandbox']);
  	}
  	
  	if ( $api_pass = $this->get_setting('UserPasswordSandbox') ) {
	  	mp_push_to_array($settings, 'api_credentials->sandbox->api_pass', $api_key);
	  	unset($settings['gateways']['eway30']['UserPasswordSandbox']);
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
			'page_slugs' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => sprintf(__('%s Settings', 'mp'), $this->admin_name),
			'option_name' => 'mp_settings',
			'desc' => __('eWay Rapid 3.0 Payments lets merchants recieve credit card payments through eWay without need for users to leave the shop. Note this gateway requires a valid SSL certificate configured for this site.', 'mp'),
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
			'default_value' => 'rapid30sandbox',
			'options' => array(
				'rapid30sandbox' => 'Sandbox',
				'rapid30live' => 'Live',
			),
		));
		$api_creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials->live'),
			'label' => array('text' => __('Live API Credentials', 'mp')),
			'conditional' => array(
				'name' => $this->get_field_name('mode'),
				'value' => 'rapid30live',
				'action' => 'show',
			),
		));
		
		if ( $api_creds instanceof WPMUDEV_Field ) {
			$api_creds->add_field('text', array(
				'name' => 'api_key',
				'label' => array('text' => __('API Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$api_creds->add_field('text', array(
				'name' => 'api_password',
				'label' => array('text' => __('API Password', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$api_creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials->sandbox'),
			'label' => array('text' => __('Sandbox API Credentials', 'mp')),
			'conditional' => array(
				'name' => $this->get_field_name('mode'),
				'value' => 'rapid30sandbox',
				'action' => 'show',
			),
		));
		
		if ( $api_creds instanceof WPMUDEV_Field ) {
			$api_creds->add_field('text', array(
				'name' => 'api_key',
				'label' => array('text' => __('API Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$api_creds->add_field('text', array(
				'name' => 'api_pass',
				'label' => array('text' => __('API Password', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
	}
}

mp_register_gateway_plugin( 'MP_Gateway_eWay31', 'eway31', __('eWay Rapid 3.1 Payments (beta)', 'mp') );