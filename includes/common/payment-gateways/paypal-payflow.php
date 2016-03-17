<?php

class MP_Gateway_PayPal_Payflow extends MP_Gateway_API {
	//build of the gateway plugin
	var $build = 2;

  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'payflow';

  //name of your gateway, for the admin side.
  var $admin_name = '';

  //public name of your gateway, for lists and such.
  var $public_name = 'Credit Card';

  //url for an image for your checkout method. Displayed on checkout form if set
  var $method_img_url = '';

  //url for an submit button image for your checkout method. Displayed on checkout form if set
  var $method_button_img_url = '';

  //whether or not ssl is needed for checkout page
  var $force_ssl = true;

  //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
  var $ipn_url;

	//whether if this is the only enabled gateway it can skip the payment_form step
  var $skip_form = false;

  //credit card vars
  var $API_Username, $API_Vendor, $API_Partner, $API_Password, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale;

  /**
   * Gateway currencies
   *
   * @since 3.0
   * @access public
   * @var array
   */
  var $currencies = array(
    'AUD' => 'AUD - Australian Dollar',
    'CAD' => 'CAD - Canadian Dollar',
    'EUR' => 'EUR - Euro',
    'GBP' => 'GBP - Pound Sterling',
    'JPY' => 'JPY - Japanese Yen',
    'USD' => 'USD - U.S. Dollar'
  );

  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
	  require_once mp_plugin_dir( 'includes/common/payment-gateways/payflow/class-mp-gateway-worker-payflow.php' );

    //set names here to be able to translate
    $this->admin_name = __('PayPal Payflow Pro', 'mp');
    $this->public_name = __('Credit Card', 'mp');

    $this->version = "63.0"; //api version

    //set credit card vars
    $this->API_Username = $this->get_setting('api_credentials->user');
    $this->API_Vendor = $this->get_setting('api_credentials->vendor');
    $this->API_Partner = $this->get_setting('api_credentials->partner');
    $this->API_Password = $this->get_setting('api_credentials->password');
    $this->currencyCode = $this->get_setting('currency');
    $this->locale = $this->get_setting('locale');

    //set api urls
    if ( $this->get_setting('mode') == 'sandbox')	{
      $this->API_Endpoint = "https://pilot-payflowpro.paypal.com";
    } else {
      $this->API_Endpoint = "https://payflowpro.paypal.com";
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
  	if ( $val = $this->get_setting('api_user') ) {
	  	mp_push_to_array($settings, 'gateways->payflow->api_credentials->user', $val);
	  	unset($settings['gateways']['payflow']['api_user']);
  	}

  	if ( $val = $this->get_setting('api_vendor') ) {
	  	mp_push_to_array($settings, 'gateways->payflow->api_credentials->vendor', $val);
	  	unset($settings['gateways']['payflow']['api_vendor']);
  	}

  	if ( $val = $this->get_setting('api_partner') ) {
	  	mp_push_to_array($settings, 'gateways->payflow->api_credentials->partner', $val);
	  	unset($settings['gateways']['payflow']['api_partner']);
  	}

  	if ( $val = $this->get_setting('api_pwd') ) {
	  	mp_push_to_array($settings, 'gateways->payflow->api_credentials->password', $val);
	  	unset($settings['gateways']['payflow']['api_pwd']);
  	}

  	return $settings;
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
	  return $this->_cc_default_form();
  }

  function _print_year_dropdown($sel='', $pfp = false) {
    $localDate=getdate();
    $minYear = $localDate["year"];
    $maxYear = $minYear + 15;

    $output = "<option value=''>--</option>";
    for($i=$minYear; $i<$maxYear; $i++) {
            if ($pfp) {
                    $output .= "<option value='". substr($i, 0, 4) ."'".($sel==(substr($i, 0, 4))?' selected':'').
                    ">". $i ."</option>";
            } else {
                    $output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
            ">". $i ."</option>";
            }
    }
    return($output);
  }

  function _print_month_dropdown($sel='') {
    $output =  "<option value=''>--</option>";
    $output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
    $output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
    $output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
    $output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
    $output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
    $output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
    $output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
    $output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
    $output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
    $output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
    $output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
    $output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Dec</option>";

    return($output);
  }

  /**
   * Use this to process any fields you added. Use the $_POST global,
   *  and be sure to save it to both the $_SESSION and usermeta if logged in.
   *  DO NOT save credit card details to usermeta as it's not PCI compliant.
   *  Call mp_checkout()->add_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function process_payment_form($cart, $shipping_info) {
    if ( ! is_email(mp_get_post_value('email', '')) )
      mp_checkout()->add_error('Please enter a valid Email Address.', 'email');

    if ( ! mp_get_post_value('name') )
      mp_checkout()->add_error('Please enter your Full Name.', 'name');

    if ( mp_get_post_value('address1') )
      mp_checkout()->add_error('Please enter your Street Address.', 'address1');

    if ( ! mp_get_post_value('city') )
      mp_checkout()->add_error('Please enter your City.', 'city');

    if ((mp_get_post_value('country') == 'US' || mp_get_post_value('country') == 'CA') && empty($_POST['state']))
      mp_checkout()->add_error('Please enter your State/Province/Region.', 'state');

    if ( ! mp()->is_valid_zip(mp_get_post_value('zip'), mp_get_post_value('country')) )
      mp_checkout()->add_error('Please enter a valid Zip/Postal Code.', 'zip');

    if ( empty($_POST['country']) || strlen(mp_get_post_value('country', '')) != 2 )
      mp_checkout()->add_error('Please enter your Country.', 'country');

    //for checkout plugins
    do_action('mp_billing_process');

    //save to session
    global $current_user;
    $meta = get_user_meta($current_user->ID, 'mp_billing_info', true);
    $_SESSION['mp_billing_info']['email'] = ($_POST['email']) ? trim(stripslashes($_POST['email'])) : $current_user->user_email;
    $_SESSION['mp_billing_info']['name'] = ($_POST['name']) ? trim(stripslashes($_POST['name'])) : $current_user->user_firstname . ' ' . $current_user->user_lastname;
    $_SESSION['mp_billing_info']['address1'] = ($_POST['address1']) ? trim(stripslashes($_POST['address1'])) : $meta['address1'];
    $_SESSION['mp_billing_info']['address2'] = ($_POST['address2']) ? trim(stripslashes($_POST['address2'])) : $meta['address2'];
    $_SESSION['mp_billing_info']['city'] = ($_POST['city']) ? trim(stripslashes($_POST['city'])) : $meta['city'];
    $_SESSION['mp_billing_info']['state'] = ($_POST['state']) ? trim(stripslashes($_POST['state'])) : $meta['state'];
    $_SESSION['mp_billing_info']['zip'] = ($_POST['zip']) ? trim(stripslashes($_POST['zip'])) : $meta['zip'];
    $_SESSION['mp_billing_info']['country'] = ($_POST['country']) ? trim($_POST['country']) : $meta['country'];
    $_SESSION['mp_billing_info']['phone'] = ($_POST['phone']) ? preg_replace('/[^0-9-\(\) ]/', '', trim($_POST['phone'])) : $meta['phone'];

    //save to user meta
    if ($current_user->ID)
      update_user_meta($current_user->ID, 'mp_billing_info', $_SESSION['mp_billing_info']);

    if (!isset($_POST['exp_month']) || !isset($_POST['exp_year']) || empty($_POST['exp_month']) || empty($_POST['exp_year'])) {
      mp_checkout()->add_error( __('Please select your credit card expiration date.', 'mp'), 'exp');
    }

    if (!isset($_POST['card_code']) || empty($_POST['card_code'])) {
      mp_checkout()->add_error( __('Please enter your credit card security code', 'mp'), 'card_code');
    }

    if (!isset($_POST['card_num']) || empty($_POST['card_num'])) {
      mp_checkout()->add_error( __('Please enter your credit card number', 'mp'), 'card_num');
    } else {
      if ($this->_get_card_type($_POST['card_num']) == "") {
        mp_checkout()->add_error( __('Please enter a valid credit card number', 'mp'), 'card_num');
      }
    }

    if (!mp()->checkout_error) {
      if (
        ($this->_get_card_type($_POST['card_num']) == "American Express" && strlen($_POST['card_code']) != 4) ||
        ($this->_get_card_type($_POST['card_num']) != "American Express" && strlen($_POST['card_code']) != 3)
        ) {
        mp_checkout()->add_error(__('Please enter a valid credit card security code', 'mp'), 'card_code');
      }
    }

    if (!mp()->checkout_error) {
      $_SESSION['card_num'] = $_POST['card_num'];
      $_SESSION['card_code'] = $_POST['card_code'];
      $_SESSION['exp_month'] = $_POST['exp_month'];
      $_SESSION['exp_year'] = $_POST['exp_year'];

      mp()->generate_order_id();
    }
  }

  function _get_card_type($number) {
    $num_length = strlen($number);

    if ($num_length > 10 && preg_match('/[0-9]+/', $number) >= 1) {
      if((substr($number, 0, 1) == '4') && (($num_length == 13)||($num_length == 16))) {
        return "Visa";
      } else if((substr($number, 0, 1) == '5' && ((substr($number, 1, 1) >= '1') && (substr($number, 1, 1) <= '5'))) && ($num_length == 16)) {
        return "Mastercard";
      } else if(substr($number, 0, 4) == "6011" && ($num_length == 16)) {
        return "Discover Card";
      } else if((substr($number, 0, 1) == '3' && ((substr($number, 1, 1) == '4') || (substr($number, 1, 1) == '7'))) && ($num_length == 15)) {
        return "American Express";
      }
    }
    return "";
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
		$exp = array_map( 'trim', explode( '/', mp_get_post_value( 'mp_cc_exp', '' ) ) );
		$total = $cart->total( false );
		$order = new MP_Order();

    $payment = new MP_Gateway_Worker_Payflow( $this->API_Endpoint );

    $payment->transaction( mp_get_post_value( 'mp_cc_num' ) );
    $payment->setParameter( 'EXPDATE', $exp[0] . substr( $exp[1], -2 ) );
    $payment->setParameter( 'CVV2', mp_get_post_value( 'mp_cc_cvc' ) );
    $payment->setParameter( 'USER', $this->API_Username );
    $payment->setParameter( 'VENDOR', $this->API_Vendor );
    $payment->setParameter( 'PWD', $this->API_Password );
    $payment->setParameter( 'PARTNER', $this->API_Partner );

    // Billing Info
    $payment->setParameter( 'TENDER', 'C');
    $payment->setParameter( 'TRXTYPE', 'S');
	  $payment->setParameter( 'BUTTONSOURCE', 'incsub_SP');
    $payment->setParameter( 'AMT', $total );

    $payment->setParameter( 'CURRENCY', $this->currencyCode );

    // Order Info
    $payment->setParameter( 'COMMENT1', 'Order ID: ' . $order->get_id() );
    $payment->setParameter( 'INVNUM', $order->get_id() );

    $address = mp_arr_get_value( 'address1', $billing_info, '' );
    if ( $address2 = mp_arr_get_value( 'address2', $billing_info ) ) {
      $address .= "\n" . $address2;
    }

    // Billing Info
    $payment->setParameter( 'FIRSTNAME', mp_arr_get_value( 'first_name', $billing_info, '' ) );
    $payment->setParameter( 'LASTNAME', mp_arr_get_value( 'last_name', $billing_info, '' ) );
    $payment->setParameter( 'STREET', $address );
    $payment->setParameter( 'CITY', mp_arr_get_value( 'city', $billing_info, '' ) );
		$payment->setParameter( 'STATE', mp_arr_get_value( 'state', $billing_info, '' ) );
    $payment->setParameter( 'COUNTRY', mp_arr_get_value( 'country', $billing_info, '' ) );
    $payment->setParameter( 'ZIP', mp_arr_get_value( 'zip', $billing_info, '' ) );
    $payment->setParameter( 'EMAIL', mp_arr_get_value( 'email', $billing_info, '' ) );

    // Shipping Info
    $address = mp_arr_get_value( 'address1', $shipping_info, '' );
    if ( $address2 = mp_arr_get_value( 'address2', $shipping_info ) ) {
      $address .= "\n" . $address2;
    }

		$payment->setParameter( 'SHIPTOFIRSTNAME', mp_arr_get_value( 'first_name', $shipping_info, '' ) );
		$payment->setParameter( 'SHIPTOLASTNAME', mp_arr_get_value( 'last_name', $shipping_info, '' ) );
		$payment->setParameter( 'SHIPTOSTREET', $address );
		$payment->setParameter( 'SHIPTOCITY', mp_arr_get_value( 'city', $shipping_info, '' ) );
		$payment->setParameter( 'SHIPTOSTATE', mp_arr_get_value( 'state', $shipping_info, '' ) );
		$payment->setParameter( 'SHIPTOCOUNTRY', mp_arr_get_value( 'country', $shipping_info, '' ) );
		$payment->setParameter( 'SHIPTOZIP', mp_arr_get_value( 'zip', $shipping_info, '' ) );

		// IP Address
    $payment->setParameter( 'CLIENTIP', $_SERVER['REMOTE_ADDR'] );

    $payment->process();

    if ( is_wp_error( $payment ) ) {
	    mp_checkout()->add_error( $payment->get_error_message(), 'order-review-payment' );
	    return;
    }

    if ( $payment->isApproved() ) {
			$timestamp = time();

			$payment_info = array();
      $payment_info['gateway_public_name'] = $this->public_name;
      $payment_info['gateway_private_name'] = $this->admin_name;
      $payment_info['method'] = $payment->getMethod();
      $payment_info['status'][ $timestamp ] = ( $payment->isHeldForReview() ) ? $payment->status : 'paid';
      $payment_info['total'] = $total;
      $payment_info['currency'] = $this->currencyCode;
      $payment_info['transaction_id'] = $payment->getTransactionID();

      //succesful payment, create our order now
      $order->save( array(
	    	'cart' => $cart,
	    	'payment_info' => $payment_info,
	    	'paid' => ( $payment->isHeldForReview() ) ? false : true,
      ) );

      wp_redirect( $order->tracking_url( false ) );
      exit;
    } else {
      $error = $payment->getResponseText();
      mp_checkout()->add_error( $error, 'order-review-payment' );
    }
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
			'desc' => __('Use Payflow payment gateway to accept online payments using your Internet merchant account and processing network. PayPal Payflow Pro is a customizable payment processing solution that gives the merchant control over all the steps in processing a transaction. An SSL certificate is required to use this gateway.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('mode'),
			'label' => array('text' => __('Mode', 'mp')),
			'default_value' => 'sandbox',
			'options' => array(
				'sandbox' => __('Sandbox', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials'),
			'label' => array('text' => __('Gateway Credentials', 'mp')),
		));

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'user',
				'label' => array('text' => __('User', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'vendor',
				'label' => array('text' => __('Vendor', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'partner',
				'label' => array('text' => __('Partner', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'password',
				'label' => array('text' => __('Password', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('currency'),
			'label' => array('text' => __('Currency', 'mp')),
			'width' => 'element',
			'multiple' => false,
			'default_value' => mp_get_setting('currency'),
			'options' => $this->currencies,
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('email_customer'),
			'label' => array('text' => __('Email Customer (on success)', 'mp')),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('header_email_receipt'),
			'label' => array('text' => __('Email Header', 'mp')),
			'desc' => __('This text will appear as the header of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => $this->get_field_name('email_customer'),
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('footer_email_receipt'),
			'label' => array('text' => __('Email Footer', 'mp')),
			'desc' => __('This text will appear as the footer of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => $this->get_field_name('email_customer'),
				'value' => 1,
				'action' => 'show',
			),
		));
  }
}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_PayPal_Payflow', 'payflow', __('PayPal Payflow Pro', 'mp') );
