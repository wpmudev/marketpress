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
   * The API base url
   *
   * @since 3.0
   * @access public
   * @var string
   */
  var $api_url = '';
  
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

  //if the gateway uses the order confirmation step during checkout (e.g. PayPal)
  var $use_confirmation_step = true;


  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //set names here to be able to translate
    $this->admin_name = __('eWay Shared Payments', 'mp');
    $this->public_name = __('Credit Card', 'mp');
    $this->returnURL = mp_store_page_url( 'checkout-confirm', false );
  	$this->cancelURL = mp_store_page_url( 'checkout', false ) . "?eway-cancel=1";

    //set api urls
  	if ( 'sandbox' == $this->get_setting( 'mode' ) )	{
  		$this->CustomerID = '87654321';
  		$this->UserName = 'TestAccount';
  		$this->api_url = 'https://payment.ewaygateway.com';
  	} else {
  		$this->CustomerID = $this->get_setting( 'CustomerID' );
  		$this->UserName = $this->get_setting( 'UserName' );
  		$this->api_url = 'https://au.ewaygateway.com';
    }
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
    
    $params = array();
		$params['CustomerID'] = $this->CustomerID;
  	$params['UserName'] = $this->UserName;
		$params['MerchantInvoice'] = $order_id;
		$params['MerchantReference'] = $order_id;
		$params['Currency'] = $this->get_setting( 'Currency' );
		$params['Language'] = $this->get_setting( 'Language' );
		$params['ReturnURL'] = $this->return_url;
		$params['CancelURL'] = $this->cancelURL;
		$params['ModifiableCustomerDetails'] = 'false';
		$params['InvoiceDescription'] = sprintf( __( '%s Store Purchase - Order ID: %s', 'mp' ), get_bloginfo( 'name' ), $order_id ); //cart name
		
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
		if ( ! $cart->is_download_only() && mp_get_setting( 'shipping->method' ) != 'none' && mp_arr_get_value( 'first_name', $shipping_info ) ) {	
			$params['CustomerFirstName'] = mp_arr_get_value( 'first_name', $shipping_info );
			$params['CustomerLastName'] = mp_arr_get_value( 'last_name', $shipping_info );
			$params['CustomerAddress'] = mp_arr_get_value('address1', $shipping_info);
			
			if ( $address2 = mp_arr_get_value( 'address2', $shipping_info ) ) {
				$params['CustomerAddress'] = $params['CustomerAddress'] . ' ' . $address2;
			}
			
			$params['CustomerPhone'] = mp_arr_get_value( 'phone', $shipping_info );
			$params['CustomerPostCode'] = mp_arr_get_value( 'zip', $shipping_info );
			$params['CustomerCity'] = mp_arr_get_value( 'city', $shipping_info );
			$params['CustomerState'] = mp_arr_get_value( 'state', $shipping_info );
			$params['CustomerCountry'] = mp_arr_get_value( 'country', $shipping_info );
		}
    
    $total = $cart->total( false );
		$product_count = $cart->item_count( false );
		
    $params['Amount'] = number_format( $total, 2, '.', '' );
    
    $result = $this->api_call( $this->api_url . '/Request', $params );
		
		if ( false !== $result ) {
			libxml_use_internal_errors(true);
			$xml = new SimpleXMLElement( $result) ;
			if ( ! $xml ) {
				mp_checkout()->add_error( '<li>' . __( 'There was a problem parsing the response from eWay. Please try again.', 'mp' ) . '</li>', 'order-review-payment' );
				return false;
			}

			if ( $xml->Result == 'True' ) {
				wp_redirect( $xml->URI );
				exit;
			} else {
				mp_checkout()->add_error( '<li>' . sprintf(__( 'There was a problem setting up the transaction with eWay: %s', 'mp' ), $xml->Error ) . '</li>', 'order-review-payment' );
			}
		}
  }

	/**
	 * Display the payment form
	 *
	 * @since 3.0
	 * @access public
   * @param array $cart. Contains the cart contents for the current blog
   * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
  public function payment_form( $cart, $shipping_info ) {
    return __( 'You will be redirected to https://www.eway.com.au to finalize your payment.', 'mp' );
  }
    
  /**
   * Process order confirmation before page loads (e.g. verify callback data, etc)
   *
   * @since 3.0
   * @access public
   * @action mp_checkout/confirm_order/{plugin_name}
   */
  public function process_confirm_order() {
		if ( $payment_code = mp_get_post_value( 'AccessPaymentCode' ) ) {
			$params = array();
			$params['CustomerID'] = $this->CustomerID;
			$params['UserName'] = $this->UserName;
			$params['AccessPaymentCode'] = $payment_code;
			
			$result = $this->api_call( $this->api_url . '/Result', $params );
			if ( $result ) {
				libxml_use_internal_errors( true );
				$xml = new SimpleXMLElement( $result );
				if ( ! $xml ) {
					mp_checkout()->add_error( '<li>' . __( 'There was a problem parsing the response from eWay. Please try again.', 'mp' ) . '</li>', 'order-review-payment' );
					return false;
				}

				if ( strtolower( $xml->TrxnStatus ) == 'true' ) {
					$timestamp = time();
					$payment_info = array();
					$payment_info['gateway_public_name'] = $this->public_name;
					$payment_info['gateway_private_name'] = $this->admin_name;
					$payment_info['method'] = __( 'Credit Card', 'mp' );
					$payment_info['transaction_id'] = (string) $xml->TrxnNumber;
					$payment_info['status'][ $timestamp ] = sprintf( __( 'Paid - The card has been processed - %s', 'mp'), (string) $xml->TrxnResponseMessage );
					$payment_info['total'] = (string) $xml->ReturnAmount;
					$payment_info['currency'] = $this->get_setting( 'Currency' );

					$order = new MP_Order( $xml->MerchantInvoice );					
					$order->save( array(
						'cart' => mp_cart(),
						'payment_info' => $payment_info,
						'paid' => true,
					) );
					
					wp_redirect( $order->tracking_url( false ) );
					exit;
				} else {
					mp_checkout()->add_error( '<li>' . sprintf( __( 'There was a problem with your credit card information: %s', 'mp' ), $xml->TrxnResponseMessage ) . '</li>', 'order-review-payment' );
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
			'page_slugs' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
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
			'desc' => __('Note when testing in sandbox mode it will use the default eWay test API credentials. Also, please note that cart total needs to be a whole number (e.g. 10.00) in order to generate a successful transaction, otherwise the transaction will fail.', 'mp'),
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
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'CustomerID',
				'label' => array('text' => __('Customer ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
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
		
	function api_call( $url, $fields ) {
    $url .= '?' . http_build_query( $fields );
		
	  //build args
	  $args = array();
	  $args['user-agent'] = 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | eWay Shared Payments Gateway/' . MP_VERSION;
	  $args['sslverify'] = false;
	  $args['timeout'] = mp_get_api_timeout( $this->plugin_name );

	  $response = wp_remote_get( $url, $args );
	  if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response) != 200 ) {
	    mp_checkout()->add_error( '<li>' . __( 'There was a problem connecting to eWay. Please try again.', 'mp' ) . '</li>', 'order-review-payment' );
	    return false;
	  } else {
	    return $response['body'];
	  }
	}
}

//register gateway only if SimpleXML module installed
if ( class_exists( 'SimpleXMLElement' ) ) {
	mp_register_gateway_plugin( 'MP_Gateway_eWay_Shared', 'eway', __('eWay Shared Payments', 'mp') );
}