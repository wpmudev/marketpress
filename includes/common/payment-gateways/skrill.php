<?php

/*
  MarketPress Skrill / Moneybookers Gateway Plugin
  Author: Marko Miljus, Aaron Edwards
 */

class MP_Gateway_Skrill extends MP_Gateway_API {

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'skrill';
	//name of your gateway, for the admin side.
	var $admin_name				 = '';
	//public name of your gateway, for lists and such.
	var $public_name				 = '';
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url			 = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url	 = '';
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form				 = true;
	//api vars
	var $API_Email, $API_Language, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale, $confirmationNote;
	var $currencies				 = array();
	var $languages				 = array();

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {

		$this->languages = array(
			"CN" => __( 'Chinese', 'mp' ),
			"CZ" => __( 'Czech', 'mp' ),
			"DA" => __( 'Danish', 'mp' ),
			"NL" => __( 'Dutch', 'mp' ),
			"EN" => __( 'English', 'mp' ),
			"FI" => __( 'Finnish', 'mp' ),
			"FR" => __( 'French', 'mp' ),
			"DE" => __( 'German', 'mp' ),
			"GR" => __( 'Greek', 'mp' ),
			"IT" => __( 'Italian', 'mp' ),
			"PL" => __( 'Polish', 'mp' ),
			"RO" => __( 'Romainian', 'mp' ),
			"RU" => __( 'Russian', 'mp' ),
			"ES" => __( 'Spanish', 'mp' ),
			"SV" => __( 'Swedish', 'mp' ),
			"TR" => __( 'Turkish', 'mp' ),
		);

		$this->currencies = array(
			"AED"	 => __( 'AED - Utd. Arab Emir. Dirham', 'mp' ),
			"AUD"	 => __( 'AUD - Australian Dollar', 'mp' ),
			"BGN"	 => __( 'BGN - Bulgarian Leva', 'mp' ),
			"CAD"	 => __( 'CAD - Canadian Dollar', 'mp' ),
			"CHF"	 => __( 'CHF - Swiss Franc', 'mp' ),
			"CZK"	 => __( 'CZK - Czech Koruna', 'mp' ),
			"DKK"	 => __( 'DKK - Danish Krone', 'mp' ),
			"EEK"	 => __( 'EEK - Estonian Kroon', 'mp' ),
			"EUR"	 => __( 'EUR - Euro', 'mp' ),
			"GBP"	 => __( 'GBP - British Pound', 'mp' ),
			"HKD"	 => __( 'HKD - Hong Kong Dollar', 'mp' ),
			"HRK"	 => __( 'HRK - Croatian Kuna', 'mp' ),
			"HUF"	 => __( 'HUF - Hungarian Forint', 'mp' ),
			"ILS"	 => __( 'ILS - Israeli Shekel', 'mp' ),
			"INR"	 => __( 'INR - Indian Rupee', 'mp' ),
			"ISK"	 => __( 'ISK - Iceland Krona', 'mp' ),
			"JOD"	 => __( 'JOD - Jordanian Dinar', 'mp' ),
			"JPY"	 => __( 'JPY - Japanese Yen', 'mp' ),
			"KRW"	 => __( 'KRW - South-Korean Won', 'mp' ),
			"LTL"	 => __( 'LTL - Lithuanian Litas', 'mp' ),
			"LVL"	 => __( 'LVL - Latvian Lat', 'mp' ),
			"MAD"	 => __( 'MAD - Moroccan Dirham', 'mp' ),
			"MYR"	 => __( 'MYR - Malaysian Ringgit', 'mp' ),
			"NZD"	 => __( 'NZD - New Zealand Dollar', 'mp' ),
			"NOK"	 => __( 'NOK - Norwegian Krone ', 'mp' ),
			"OMR"	 => __( 'OMR - Omani Rial', 'mp' ),
			"PLN"	 => __( 'PLN - Polish Zloty', 'mp' ),
			"QAR"	 => __( 'QAR - Qatari Rial', 'mp' ),
			"RON"	 => __( 'RON - Romanian Leu New', 'mp' ),
			"RSD"	 => __( 'RSD - Serbian dinar', 'mp' ),
			"SAR"	 => __( 'SAR - Saudi Riyal', 'mp' ),
			"SEK"	 => __( 'SEK - Swedish Krona', 'mp' ),
			"SGD"	 => __( 'SGD - Singapore Dollar', 'mp' ),
			"SKK"	 => __( 'SKK - Slovakian Koruna', 'mp' ),
			"THB"	 => __( 'THB - Thailand Baht', 'mp' ),
			"TND"	 => __( 'TND - Tunisian Dinar', 'mp' ),
			"TRY"	 => __( 'TRY - New Turkish Lira', 'mp' ),
			"TWD"	 => __( 'TWD - Taiwan Dollar', 'mp' ),
			"USD"	 => __( 'USD - U.S. Dollar', 'mp' ),
			"ZAR"	 => __( 'ZAR - South-African Rand', 'mp' ),
		);

		//set names here to be able to translate
		$this->admin_name	 = __( 'Skrill (Moneybookers)', 'mp' );
		$this->public_name	 = __( 'Skrill (Moneybookers)', 'mp' );

		$this->method_img_url		 = '';
		$this->method_button_img_url = '';

		$this->currencyCode		 = $this->get_setting( 'currency' );
		$this->API_Email		 = $this->get_setting( 'email' );
		$this->confirmationNote	 = $this->get_setting( 'confirmationNote' );
		$this->API_Language		 = $this->get_setting( 'language' );
	}

	/**
	 * Initialize the settings metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => $this->generate_metabox_id(),
			'page_slugs'	 => array( 'store-settings-payments', 'store-settings_page_store-settings-payments' ),
			'title'			 => sprintf( __( '%s Settings', 'mp' ), $this->admin_name ),
			'option_name'	 => 'mp_settings',
			'desc'			 => __( 'Resell your inventory via Skrill.com (Moneybookers)', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'text', array(
			'name'		 => $this->get_field_name( 'email' ),
			'label'		 => array( 'text' => __( 'Skrill Email', 'mp' ) ),
			'desc'		 => sprintf( __( 'You must use your valid Skrill merchant email. <a target="_blank" href="%s">Instructions &raquo;</a>', 'mp' ), 'http://www.moneybookers.com/app/help.pl?s=m_paymentoptions' ),
			'validation' => array(
				'email'		 => true,
				'required'	 => true,
			),
		) );

		$metabox->add_field( 'text', array(
			'name'		 => $this->get_field_name( 'secret-word' ),
			'label'		 => array( 'text' => __( 'Secret Word', 'mp' ) ),
			'desc'		 => sprintf( __( 'The secret word must match the word submitted in the "Merchant Tools" section of your <a target="_blank" href="%s">Skrill account</a>.', 'mp' ), 'https://www.moneybookers.com/app/' ),
			'validation' => array(
				'required' => true,
			),
		) );

		$metabox->add_field( 'advanced_select', array(
			'name'			 => $this->get_field_name( 'currency' ),
			'label'			 => array( 'text' => __( 'Currency', 'mp' ) ),
			'default_value'	 => mp_get_setting( 'currency', array() ),
			'multiple'		 => false,
			'width'			 => 'element',
			'desc'			 => __( 'Selecting a currency other than that used for your store may cause problems at checkout.', 'mp' ),
			'options'		 => array_merge( $this->currencies, array( '' => __( 'Select One', 'mp' ) ) ),
			'validation'	 => array(
				'required' => true,
			),
		) );

		$metabox->add_field( 'advanced_select', array(
			'name'		 => $this->get_field_name( 'language' ),
			'label'		 => array( 'text' => __( 'Language', 'mp' ) ),
			'multiple'	 => false,
			'width'		 => 'element',
			'options'	 => array_merge( $this->languages, array( '' => __( 'Select One', 'mp' ) ) ),
			'validation' => array(
				'required' => true,
			),
		) );

		$metabox->add_field( 'text', array(
			'name'	 => $this->get_field_name( 'business-name' ),
			'label'	 => array( 'text' => __( 'Merchant Name (optional)', 'mp' ) ),
			'desc'	 => __( 'The name of this store, which will be shown on the gateway. If no value is submitted, the account email will be shown as the recipient of the payment.', 'mp' ),
		) );

		$metabox->add_field( 'file', array(
			'name'	 => $this->get_field_name( 'logourl' ),
			'label'	 => array( 'text' => __( 'Logo Image (optional)', 'mp' ) ),
			'desc'	 => __( 'The URL of the logo which you would like to appear at the top of the payment form. The logo must be accessible via HTTPS otherwise it will not be shown. For best integration results we recommend that you use a logo with dimensions up to 200px in width and 50px in height.', 'mp' ),
		) );

		$metabox->add_field( 'textarea', array(
			'name'	 => $this->get_field_name( 'confirmationNote' ),
			'label'	 => array( 'text' => __( 'Confirmation Note (optional)', 'mp' ) ),
			'desc'	 => __( 'Shown to the customer on the confirmation screen - the end step of the process - a note, confirmation number, or any other message. Line breaks &lt;br&gt; may be used for longer messages.', 'mp' ),
			'custom' => array(
				'rows' => 10,
			),
		) );
	}

	/**
	 * Return fields you need to add to the payment screen, like your credit card info fields
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		if ( mp_get_get_value( 'moneybookers_cancel' ) ) {
			echo '<div class="mp_checkout_error">' . __( 'Your Moneybookers transaction has been canceled.', 'mp' ) . '</div>';
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
		global $current_user;

		$timestamp = time();

		$url = 'https://www.moneybookers.com/app/payment.pl';

		$order = new MP_Order();

		$order_id = $order->get_id();

		$params							 = array();
		$params[ 'transaction_id' ]		 = $order_id;
		$params[ 'pay_to_email' ]		 = $this->API_Email;
		$params[ 'currency' ]			 = $this->currencyCode;
		$params[ 'language' ]			 = $this->API_Language;
		$params[ 'return_url' ]			 = $this->return_url;
		$params[ 'return_url_text' ]	 = __( 'Complete Checkout', 'mp' );
		$params[ 'cancel_url' ]			 = $this->cancel_url . '?moneybookers_cancel';
		$params[ 'status_url' ]			 = $this->ipn_url; //add_query_arg( 'order_id', $order_id, $this->ipn_url );
		$params[ 'confirmation_note' ]	 = $this->confirmationNote;

		if ( $logourl = $this->get_setting( 'logourl' ) ) {
			$params[ 'logo_url' ] = $logourl;
		}

		if ( $biz_name = $this->get_setting( 'business-name' ) ) {
			$params[ 'recipient_description' ] = $biz_name;
		}

		$params[ 'pay_from_email' ] = mp_arr_get_value( 'email', $shipping_info );

		if ( !mp()->download_only_cart( $cart ) && mp_get_setting( 'shipping->method' ) != 'none' && isset( $shipping_info[ 'name' ] ) ) {
			$names						 = explode( ' ', $shipping_info[ 'name' ] );
			$params[ 'firstname' ]		 = $names[ 0 ];
			$params[ 'lastname' ]		 = $names[ count( $names ) - 1 ]; //grab last name
			$params[ 'address' ]		 = $shipping_info[ 'address1' ];
			$params[ 'phone_number' ]	 = $shipping_info[ 'phone' ];
			$params[ 'postal_code' ]	 = $shipping_info[ 'zip' ];
			$params[ 'city' ]			 = $shipping_info[ 'city' ];
			$params[ 'state' ]			 = $shipping_info[ 'state' ];
		}

		$params[ "detail1_text" ]		 = $order_id;
		$params[ "detail1_description" ] = __( 'Order ID:', 'mp' );

		$total				 = $cart->total( false );
		$params[ 'amount' ]	 = $total;

		$param_list = array();

		foreach ( $params as $k => $v ) {
			$param_list[] = "{$k}=" . rawurlencode( $v );
		}

		$param_str = implode( '&', $param_list );

		$payment_info = array(
			'gateway_public_name'	 => $this->public_name,
			'gateway_private_name'	 => $this->admin_name,
			'status'				 => array(
				$timestamp => __( 'Pending payment', 'mp' ),
			),
			'total'					 => mp_cart()->total( false ),
			'currency'				 => $this->currencyCode,
			'transaction_id'		 => $order_id,
			'method'				 => __( 'Skrill', 'mp' ),
		);

		$order->save( array(
			'cart'			 => $cart,
			'payment_info'	 => $payment_info,
			'billing_info'	 => $billing_info,
			'shipping_info'	 => $shipping_info,
			'paid'			 => false,
		) );

		wp_redirect( "{$url}?{$param_str}" );
		exit( 0 );
	}

	/**
	 * Runs before page load incase you need to run any scripts before loading the success message page
	 */
	function process_checkout_return() {

		$order_id = mp_get_get_value( 'transaction_id' );

		if ( !isset( $order_id ) || empty( $order_id ) ) {
			return;
		}

		$order = new MP_Order( $order_id );

		wp_redirect( $order->tracking_url( false ) );
		die;
	}

	/**
	 * INS and payment return
	 */
	function process_ipn_return() {

		foreach ( $_POST as $key => $value ) {
			$message .= "Field " . htmlspecialchars( $key ) . " = " . htmlspecialchars( $value ) . "<br>";
		}

		
		$order_id	 = mp_get_get_value( 'order_id' );
		$order		 = new MP_Order( $order_id );
		$order->log_ipn_status( __( 'Skrill IPN message received.', 'mp' ) );

		if ( $_SERVER[ 'HTTP_USER_AGENT' ] != 'Moneybookers Merchant Payment Agent' ) {
			header( 'HTTP/1.0 403 Forbidden' );
			exit( 'Invalid request (Code: WA)' );
		}

		if ( mp_get_post_value( 'transaction_id' ) ) {
			$checksum = strtoupper( md5( mp_get_post_value( 'merchant_id', '' ) . mp_get_post_value( 'transaction_id', '' ) . strtoupper( md5( $this->get_setting( 'secret-word', '' ) ) ) . mp_get_post_value( 'mb_amount', '' ) . mp_get_post_value( 'mb_currency', '' ) . mp_get_post_value( 'status', '' ) ) );

			if ( mp_get_post_value( 'md5sig' ) != $checksum ) {
				header( 'HTTP/1.0 403 Forbidden' );
				exit( 'We were unable to authenticate the request' );
			}

			//setup our payment details
			$payment_info[ 'gateway_public_name' ]	 = $this->public_name;
			$payment_info[ 'gateway_private_name' ]	 = $this->admin_name;
			$payment_info[ 'method' ]				 = ( $payment_type							 = mp_get_post_value( 'payment_type' ) ) ? $payment_type : __( 'Skrill balance, Credit Card, or Instant Transfer', 'mp' );
			$payment_info[ 'transaction_id' ]		 = ( $trans_id								 = mp_get_post_value( 'mb_transaction_id' ) ) ? mp_get_post_value( 'mb_transaction_id' ) : mp_get_post_value( 'transaction_id' );

			$timestamp	 = time();
			$order_id	 = mp_get_post_value( 'transaction_id' );

			//setup status
			switch ( mp_get_post_value( 'status' ) ) {
				case '2':
					$status			 = __( 'Processed - The payment has been completed, and the funds have been added successfully to your Moneybookers account balance.', 'mp' );
					$create_order	 = true;
					$paid			 = true;
					break;

				case '0':
					$status			 = __( 'Pending - The payment is pending. It can take 2-3 days for bank transfers to complete.', 'mp' );
					$create_order	 = true;
					$paid			 = false;
					break;

				case '-1':
					$status			 = __( 'Cancelled - The payment was cancelled manually by the sender in their online account history or was auto-cancelled after 14 days pending.', 'mp' );
					$create_order	 = false;
					$paid			 = false;
					break;

				case '-2':
					$status			 = __( 'Failed - The Credit Card or Direct Debit transaction was declined.', 'mp' );
					$create_order	 = false;
					$paid			 = false;
					break;

				case '-3':
					$status			 = __( 'Chargeback - A payment was reversed due to a chargeback. The funds have been removed from your account balance and returned to the buyer.', 'mp' );
					$create_order	 = false;
					$paid			 = false;
					break;

				default:
					// case: various error cases
					$create_order	 = false;
					$paid			 = false;
			}


			//status's are stored as an array with unix timestamp as key
			$payment_info[ 'status' ][ $timestamp ]	 = $status;
			$payment_info[ 'total' ]				 = mp_get_post_value( 'amount' );
			$payment_info[ 'currency' ]				 = mp_get_post_value( 'currency' );

			$order = new MP_Order( $order_id );

			$order->log_ipn_status( $status );

			if ( $order ) {
				$order->change_status( 'paid', $paid );
			} else if ( $create_order ) {
				//succesful payment, create our order now
				$cart			 = get_transient( 'mp_order_' . $order_id . '_cart' );
				$shipping_info	 = get_transient( 'mp_order_' . $order_id . '_shipping' );
				$billing_info	 = get_transient( 'mp_order_' . $order_id . '_billing' );
				$user_id		 = get_transient( 'mp_order_' . $order_id . '_userid' );

				$order->save( array(
					'cart'			 => $cart,
					'payment_info'	 => $payment_info,
					'billing_info'	 => $billing_info,
					'shipping_info'	 => $shipping_info,
					'paid'			 => false
				) );

				delete_transient( 'mp_order_' . $order_id . '_cart' );
				delete_transient( 'mp_order_' . $order_id . '_shipping' );
				delete_transient( 'mp_order_' . $order_id . '_billing' );
				delete_transient( 'mp_order_' . $order_id . '_userid' );
			}

			//if we get this far return success so ipns don't get resent
			header( 'HTTP/1.0 200 OK' );
			exit( 'Successfully recieved!' );
		} else {
			header( 'HTTP/1.0 403 Forbidden' );
			exit( 'Invalid request (Code: TID)' );
		}
	}

}

//register payment gateway plugin - moved for 3.1 release
//mp_register_gateway_plugin( 'MP_Gateway_Skrill', 'skrill', __( 'Skrill (Moneybookers)', 'mp' ) );
