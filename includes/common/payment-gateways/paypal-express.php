<?php

class MP_Gateway_Paypal_Express extends MP_Gateway_API {
	//build
	var $build = 2;

	//private gateway slug. Lowercase alpha (a-z) and underscores (_) only please!
	var $plugin_name = 'paypal_express';

	//name of your gateway, for the admin side.
	var $admin_name = '';

	//public name of your gateway, for lists and such.
	var $public_name = '';

	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url = '';

	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url = '';

	//whether or not ssl is needed for checkout page
	var $force_ssl = false;

	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;

	//only required for global capable gateways. The maximum stores that can checkout at once
	var $max_stores = 10;

	// Payment action
	var $payment_action = 'Sale';

	//paypal vars
	var $API_Username, $API_Password, $API_Signature, $SandboxFlag, $cancelURL, $API_Endpoint, $paypalURL, $version, $currencyCode, $locale;

	//use confirmation step
	var $use_confirmation_step = true;

	/**
	 * List of supported currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array(
		'AUD' => 'AUD - Australian Dollar',
		'BRL' => 'BRL - Brazilian Real',
		'CAD' => 'CAD - Canadian Dollar',
		'CHF' => 'CHF - Swiss Franc',
		'CZK' => 'CZK - Czech Koruna',
		'DKK' => 'DKK - Danish Krone',
		'EUR' => 'EUR - Euro',
		'GBP' => 'GBP - Pound Sterling',
		'ILS' => 'ILS - Israeli Shekel',
		'HKD' => 'HKD - Hong Kong Dollar',
		'HUF' => 'HUF - Hungarian Forint',
		'JPY' => 'JPY - Japanese Yen',
		'MYR' => 'MYR - Malaysian Ringgits',
		'MXN' => 'MXN - Mexican Peso',
		'NOK' => 'NOK - Norwegian Krone',
		'NZD' => 'NZD - New Zealand Dollar',
		'PHP' => 'PHP - Philippine Pesos',
		'PLN' => 'PLN - Polish Zloty',
		'RUB' => 'RUB - Russian Rubles',
		'SEK' => 'SEK - Swedish Krona',
		'SGD' => 'SGD - Singapore Dollar',
		'TWD' => 'TWD - Taiwan New Dollars',
		'THB' => 'THB - Thai Baht',
		'TRY' => 'TRY - Turkish lira',
		'USD' => 'USD - U.S. Dollar'
	);

	/**
	 * List of support locales
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $locales = array(
		'AR' => 'Argentina',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'BE' => 'Belgium',
		'BR' => 'Brazil',
		'CA' => 'Canada',
		'CN' => 'China',
		'FI' => 'Finland',
		'FR' => 'France',
		'DE' => 'Germany',
		'HK' => 'Hong Kong',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JP' => 'Japan',
		'MX' => 'Mexico',
		'NL' => 'Netherlands',
		'NZ' => 'New Zealand',
		'PL' => 'Poland',
		'RU' => 'Russia',
		'SG' => 'Singapore',
		'ES' => 'Spain',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'TR' => 'Turkey',
		'GB' => 'United Kingdom',
		'US' => 'United States'
	);

	/**
	 * Process an API call
	 *
	 * @since 3.0
	 *
	 * @param string $method
	 * @param array $request
	 *
	 * @access protected
	 * @return array
	 */
	protected function _api_call( $method, $request ) {
		$request['METHOD']    = $method;
		$request['VERSION']   = $this->version;
		$request['PWD']       = $this->API_Password;
		$request['USER']      = $this->API_Username;
		$request['SIGNATURE'] = $this->API_Signature;

		//allow easy debugging
		if ( defined( "MP_DEBUG_API_$method" ) ) {
			var_dump( $request );
			die;
		}

		//make API call
		$response = wp_remote_post( $this->API_Endpoint, array(
			'user-agent'  => 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | PayPal Express Plugin/' . MP_VERSION,
			'body'        => http_build_query( $request ),
			'sslverify'   => false,
			'timeout'     => mp_get_api_timeout( $this->plugin_name ),
			'httpversion' => '1.1',    //api call will fail without this!
		) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( '', __( 'There was a problem connecting to PayPal. Please try again.', 'mp' ) );
		} else {
			return $this->_deformat_nvp( $response['body'] );
		}
	}

	/**
	 * Take NVPString and convert it to an associative array and it will decode the response
	 *
	 * @since 3.0
	 * @access protected
	 * @return array
	 */
	protected function _deformat_nvp( $nvpstr ) {
		parse_str( $nvpstr, $nvparray );

		return $nvparray;
	}

	/**
	 * Prepare the parameters for the DoExpressCheckoutPayment API Call
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param string $token
	 * @param string $payer_id
	 */
	protected function _do_express_checkout_payment( $token, $payer_id ) {
		$saved_request = (array) mp_get_session_value( 'paypal_request', array() );
		$request       = array_merge( $saved_request, array(
			'TOKEN'   => $token,
			'PAYERID' => $payer_id,
			//'BUTTONSOURCE' => 'incsub_SP',
		) );

		return $this->_api_call( 'DoExpressCheckoutPayment', $request );
	}

	/**
	 * Get error string from result array
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $result
	 *
	 * @return string
	 */
	protected function _get_error( $result ) {
		$error = '';
		for ( $i = 0; $i <= 5; $i ++ ) {
			if ( isset( $result["L_ERRORCODE{$i}"] ) ) {
				$error .= '<li>' . $result["L_ERRORCODE{$i}"] . ' - ' . $result["L_LONGMESSAGE{$i}"] . '</li>';
			}
		}

		return $error;
	}

	/**
	 * Prepare the parameters for the GetExpressCheckoutDetails API Call
	 *
	 * @since 3.0
	 * @access protected
	 * @return array
	 */
	protected function _get_express_checkout_details( $token ) {
		$request = array(
			'TOKEN' => $token,
		);

		return $this->_api_call( 'GetExpressCheckoutDetails', $request );
	}

	/**
	 * Redirect to PayPal site
	 *
	 * @since 3.0
	 *
	 * @param string $token A token as supplied by SetExpressCheckout API call
	 *
	 * @access protected
	 */
	function _redirect_to_paypal( $token ) {
		wp_redirect( $this->paypalURL . $token );
		exit;
	}


	/**
	 * Prepare the parameters for the SetExpressCheckout API Call
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _set_express_checkout_deprecated( $cart, $billing_info, $shipping_info, $order_id ) {
		$blog_id = $current_blog_id = get_current_blog_id();

		$items    = $cart->get_items_as_objects();
		$subtotal = 0;

		$request                 = array();
		$request['RETURNURL']    = $this->return_url;
		$request['CANCELURL']    = $this->cancel_url;
		$request['ADDROVERRIDE'] = 1;
		$request['NOSHIPPING']   = 0; // don't display shipping fields by default
		$request['ALLOWNOTE']    = 0; // dont allow buyers to add note
		$request['LANDINGPAGE']  = 'Billing';
		$request['SOLUTIONTYPE'] = 'Sole';
		$request['LOCALECODE']   = $this->locale;
		$request['CHANNELTYPE']  = 'Merchant';
		$request['TOTALTYPE']    = 'Total';
		$request['EMAIL']        = mp_arr_get_value( 'email', $billing_info, '' );

		//formatting
		$request['HDRIMG']         = $this->get_setting( 'header_img', '' );
		$request['HDRBORDERCOLOR'] = $this->get_setting( 'header_border', '' );
		$request['HDRBACKCOLOR']   = $this->get_setting( 'header_back', '' );
		$request['PAYFLOWCOLOR']   = $this->get_setting( 'page_back', '' );

		//setup payment request
		$request['PAYMENTREQUEST_0_PAYMENTACTION'] = $this->payment_action;
		$request['PAYMENTREQUEST_0_CURRENCYCODE']  = $this->currencyCode;
		$request['PAYMENTREQUEST_0_NOTIFYURL']     = $this->ipn_url;
		$request['PAYMENTREQUEST_0_CUSTOM']        = $this->_crc( $cart->get_items() );

		if ( 'none' != mp_get_setting( 'shipping->method' ) ) {
			$name                                          = mp_arr_get_value( 'first_name', $shipping_info, '' ) . ' ' . mp_arr_get_value( 'last_name', $shipping_info, '' );
			$request['PAYMENTREQUEST_0_SHIPTONAME']        = $this->trim_name( $name, 128 );
			$request['PAYMENTREQUEST_0_SHIPTOSTREET']      = $this->trim_name( mp_arr_get_value( 'address1', $shipping_info, '' ), 100 );
			$request['PAYMENTREQUEST_0_SHIPTOSTREET2']     = $this->trim_name( mp_arr_get_value( 'address2', $shipping_info, '' ), 100 );
			$request['PAYMENTREQUEST_0_SHIPTOCITY']        = $this->trim_name( mp_arr_get_value( 'city', $shipping_info, '' ), 40 );
			$request['PAYMENTREQUEST_0_SHIPTOSTATE']       = $this->trim_name( mp_arr_get_value( 'state', $shipping_info, '' ), 40 );
			$request['PAYMENTREQUEST_0_SHIPTOZIP']         = $this->trim_name( mp_arr_get_value( 'zip', $shipping_info, '' ), 20 );
			$request['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $this->trim_name( mp_arr_get_value( 'country', $shipping_info, '' ), 2 );
			$request['PAYMENTREQUEST_0_SHIPTOPHONENUM']    = $this->trim_name( mp_arr_get_value( 'phone', $shipping_info, '' ), 20 );
			$request['NOSHIPPING']                         = 2; //enable shipping fields
		}

		$i = 0;
		foreach ( $items as $item ) {
			$price = $item->get_price( 'lowest' );

			if ( $price <= 0 ) {
				//skip free products to avoid paypal error
				continue;
			}

			$subtotal += $price;
			$request["PAYMENTREQUEST_0_NAME{$i}"]         = $this->trim_name( $item->title( false ) );
			$request["PAYMENTREQUEST_0_AMT{$i}"]          = $price;
			$request["PAYMENTREQUEST_0_NUMBER{$i}"]       = $item->get_meta( 'sku', '' );
			$request["PAYMENTREQUEST_0_QTY{$i}"]          = $item->qty;
			$request["PAYMENTREQUEST_0_ITEMURL{$i}"]      = $item->url( false );
			$request["PAYMENTREQUEST_0_ITEMCATEGORY{$i}"] = 'Physical';
			$i ++;
		}

		$request["PAYMENTREQUEST_0_ITEMAMT"] = $subtotal; //items subtotal

		//shipping total
		if ( ( $shipping_price = $cart->shipping_total( false ) ) !== false ) {
			$request["PAYMENTREQUEST_0_SHIPPINGAMT"] = $shipping_price;
		}

		//tax total - only if tax inclusive pricing is off. It it's on it would screw up the totals.
		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
			$tax_total                          = $cart->tax_total( false );
			$request["PAYMENTREQUEST_0_TAXAMT"] = $tax_total;
		}

		//order details
		$request["PAYMENTREQUEST_0_DESC"]             = $this->trim_name( sprintf( __( '%s Store Purchase - Order ID: %s', 'mp' ), get_bloginfo( 'name' ), $order_id ) );
		$request["PAYMENTREQUEST_0_AMT"]              = $cart->total( false );
		$request["PAYMENTREQUEST_0_INVNUM"]           = $order_id;
		$request["PAYMENTREQUEST_0_PAYMENTREQUESTID"] = $blog_id . ':' . $order_id;

		if ( $this->payment_action == 'Sale' ) {
			//$request["PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD"] = 'InstantPaymentOnly';
		}
		// save this request to session for later use
		mp_update_session_value( 'paypal_request', $request );

		return $this->_api_call( 'SetExpressCheckout', $request );
	}

	/**
	 * Prepare params for global cart
	 *
	 * @param MP_Cart $cart
	 * @param $billing_info
	 * @param $shipping_info
	 * @param $order_id
	 *
	 * @return array
	 * @since 3.0
	 */
	protected function _set_express_checkout( MP_Cart $cart, $billing_info, $shipping_info, $order_id ) {
		$blog_id                 = $current_blog_id = get_current_blog_id();
		$request                 = array();
		$request['RETURNURL']    = $this->return_url;
		$request['CANCELURL']    = $this->cancel_url;
		$request['ADDROVERRIDE'] = 1;
		$request['NOSHIPPING']   = 0; // don't display shipping fields by default
		$request['ALLOWNOTE']    = 0; // dont allow buyers to add note
		$request['LANDINGPAGE']  = 'Billing';
		$request['SOLUTIONTYPE'] = 'Sole';
		$request['LOCALECODE']   = $this->locale;
		$request['CHANNELTYPE']  = 'Merchant';
		$request['TOTALTYPE']    = 'Total';
		$request['EMAIL']        = mp_arr_get_value( 'email', $billing_info, '' );

		//formatting
		$request['HDRIMG']         = $this->get_setting( 'header_img', '' );
		$request['HDRBORDERCOLOR'] = $this->get_setting( 'header_border', '' );
		$request['HDRBACKCOLOR']   = $this->get_setting( 'header_back', '' );
		$request['PAYFLOWCOLOR']   = $this->get_setting( 'page_back', '' );

		//build the item details
		$index = 0;

		//we loop through sites
		foreach ( $cart->get_all_items() as $bid => $items ) {
			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}

			if ( $cart->is_global && $bid != $blog_id ) {
				switch_to_blog( $bid );
			}

			if ( $cart->is_global ) {
				//check if the current merchant don't have email setting, we bypass
				$merchant_email = mp_get_setting( 'gateways->paypal_express->merchant_email' );
				$merchant_email = trim( $merchant_email );
				if ( empty( $merchant_email ) || strlen( $merchant_email ) == 0 ) {
					continue;
				}
			}

			//setup payment request
			$request[ 'PAYMENTREQUEST_' . $index . '_PAYMENTACTION' ] = $this->payment_action;
			$request[ 'PAYMENTREQUEST_' . $index . '_CURRENCYCODE' ]  = $this->currencyCode;
			$request[ 'PAYMENTREQUEST_' . $index . '_NOTIFYURL' ]     = $this->ipn_url;
			$request[ 'PAYMENTREQUEST_' . $index . '_CUSTOM' ]        = $this->_crc( $cart->get_items() );
			if ( isset( $merchant_email ) ) {
				///merchant email provided, case global cart
				$request[ 'PAYMENTREQUEST_' . $index . '_SELLERPAYPALACCOUNTID' ] = $merchant_email;
				$request[ 'PAYMENTREQUEST_' . $index . '_SELLERID' ]              = $bid;
			}
			//set shipping
			if ( 'none' != mp_get_setting( 'shipping->method' ) ) {
				$name                                                         = mp_arr_get_value( 'first_name', $shipping_info, '' ) . ' ' . mp_arr_get_value( 'last_name', $shipping_info, '' );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTONAME' ]        = $this->trim_name( $name, 128 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOSTREET' ]      = $this->trim_name( mp_arr_get_value( 'address1', $shipping_info, '' ), 100 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOSTREET2' ]     = $this->trim_name( mp_arr_get_value( 'address2', $shipping_info, '' ), 100 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOCITY' ]        = $this->trim_name( mp_arr_get_value( 'city', $shipping_info, '' ), 40 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOSTATE' ]       = $this->trim_name( mp_arr_get_value( 'state', $shipping_info, '' ), 40 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOZIP' ]         = $this->trim_name( mp_arr_get_value( 'zip', $shipping_info, '' ), 20 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOCOUNTRYCODE' ] = $this->trim_name( mp_arr_get_value( 'country', $shipping_info, '' ), 2 );
				$request[ 'PAYMENTREQUEST_' . $index . '_SHIPTOPHONENUM' ]    = $this->trim_name( mp_arr_get_value( 'phone', $shipping_info, '' ), 20 );
				$request['NOSHIPPING']                                        = 2; //enable shipping fields
			}

			$i = 0;
			//we need to make a virtual cart for calculating,don't write to cookie
			$vcart    = new MP_Cart( false );
			foreach ( $items as $product_id => $quantity ) {
				$item  = new MP_Product( $product_id );
				$price = $item->get_price( 'lowest' );
				if ( $price <= 0 ) {
					//skip free products to avoid paypal error
					continue;
				}

				$vcart->add_item( $product_id, $quantity );

				$request["L_PAYMENTREQUEST_{$index}_NAME{$i}"]         = $this->trim_name( $item->title( false ) );
				$request["L_PAYMENTREQUEST_{$index}_AMT{$i}"]          = $price;
				$request["L_PAYMENTREQUEST_{$index}_NUMBER{$i}"]       = $item->get_meta( 'sku', '' );
				$request["L_PAYMENTREQUEST_{$index}_QTY{$i}"]          = $quantity;
				$request["L_PAYMENTREQUEST_{$index}_ITEMURL{$i}"]      = $item->url( false );
				$request["L_PAYMENTREQUEST_{$index}_ITEMCATEGORY{$i}"] = 'Physical';

				$i ++;
			}

			$request["PAYMENTREQUEST_{$index}_ITEMAMT"] = (float) $vcart->product_total(false); //items subtotal

			//shipping total
			if ( ( $shipping_price = $vcart->shipping_total( false ) ) !== false ) {
				$request["PAYMENTREQUEST_{$index}_SHIPPINGAMT"] = (float) $shipping_price;
			}

			//tax total - only if tax inclusive pricing is off. It it's on it would screw up the totals.
			if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
				$tax_total                                 = $vcart->tax_total( false );
				$request["PAYMENTREQUEST_{$index}_TAXAMT"] = $tax_total;
			} else {
				//incase of inclusive, we will have to submit the shipping tax
				$tax_total = $vcart->shipping_tax_total(false);
				$request["PAYMENTREQUEST_{$index}_TAXAMT"] = round( $tax_total, 2 );
			}
			//order details
			$request["PAYMENTREQUEST_{$index}_DESC"]             = $this->trim_name( sprintf( __( '%s Store Purchase - Order ID: %s', 'mp' ), get_bloginfo( 'name' ), $order_id ) );
			$request["PAYMENTREQUEST_{$index}_AMT"]              = $vcart->total( false );
			$request["PAYMENTREQUEST_{$index}_INVNUM"]           = $order_id;
			$request["PAYMENTREQUEST_{$index}_PAYMENTREQUESTID"] = $bid . ':' . $order_id;

			if ( $this->payment_action == 'Sale' ) {
				//$request["PAYMENTREQUEST_{$index}_ALLOWEDPAYMENTMETHOD"] = 'InstantPaymentOnly';
			}
			$index ++;

			if ( $cart->is_global ) {
				switch_to_blog( $blog_id );
			}
		}
		// save this request to session for later use
		mp_update_session_value( 'paypal_request', $request );

		return $this->_api_call( 'SetExpressCheckout', $request );
	}

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name  = __( 'PayPal Express Checkout', 'mp' );
		$this->public_name = __( 'PayPal', 'mp' );

		//dynamic button img, see: https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECButtonIntegration
		$this->method_img_url        = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&buttontype=ecmark&locale=' . get_locale();
		$this->method_button_img_url = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&locale=' . get_locale();

		$mode = '';
		if ( is_multisite() && mp_get_network_setting( 'global_cart' ) == 1 ) {
			//global cart init
			$this->API_Username  = $this->get_network_setting( 'api_credentials->username' );
			$this->API_Password  = $this->get_network_setting( 'api_credentials->password' );
			$this->API_Signature = $this->get_network_setting( 'api_credentials->signature' );
			$this->currencyCode  = $this->get_network_setting( 'currency', mp_get_network_setting( 'currency' ) );
			$this->locale        = $this->get_network_setting( 'locale' );

			//determine mode
			$mode = $this->get_network_setting( 'mode' );
		} else {
			$this->API_Username  = $this->get_setting( 'api_credentials->username' );
			$this->API_Password  = $this->get_setting( 'api_credentials->password' );
			$this->API_Signature = $this->get_setting( 'api_credentials->signature' );
			$this->currencyCode  = $this->get_setting( 'currency', mp_get_setting( 'currency' ) );
			$this->locale        = $this->get_setting( 'locale' );

			//determine mode
			$mode = $this->get_setting( 'mode' );
		}
		$this->cancelURL = add_query_arg( 'cancel', '1', mp_store_page_url( 'checkout', false ) );
		$this->version   = '69.0'; //api version

		// Set api urls

		if ( $mode == 'sandbox' ) {
			$this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
			$this->paypalURL    = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
		} else {
			$this->API_Endpoint = "https://api-3t.paypal.com/nvp";
			$this->paypalURL    = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
		}
	}

	/**
	 * Updates the gateway settings
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function update( $settings ) {
		if ( ( $api_user = $this->get_setting( 'api_user' ) ) && ( $api_pass = $this->get_setting( 'api_pass' ) ) && ( $api_sig = $this->get_setting( 'api_sig' ) ) ) {
			// Update api user
			mp_push_to_array( $settings, 'gateways->paypal_express->api_credentials->username', $api_user );

			// Update api pass
			mp_push_to_array( $settings, 'gateways->paypal_express->api_credentials->password', $api_pass );

			// Update api signature
			mp_push_to_array( $settings, 'gateways->paypal_express->api_credentials->signature', $api_sig );

			// Unset old keys
			unset( $settings['gateways']['paypal_express']['api_user'], $settings['gateways']['paypal_express']['api_pass'], $settings['gateways']['paypal_express']['api_sig'] );
		}

		return $settings;
	}

	/**
	 * Init network settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	function init_network_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => $this->generate_metabox_id(),
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Paypal Express Network Settings', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'desc'             => __( 'Express Checkout is PayPal\'s premier checkout solution, which streamlines the checkout process for buyers and keeps them on your site after making a purchase. Unlike PayPal Pro, there are no additional fees to use Express Checkout, though you may need to do a free upgrade to a business account. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">More Info &raquo;</a>', 'mp' ),
			'order'            => 16,
			'conditional'      => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'global_cart',
					'value' => 1,
				),
				array(
					'name'  => 'global_gateway',
					'value' => $this->plugin_name,
				),
			),
		) );
		$this->common_metabox_fields( $metabox );
	}

	/**
	 * Init settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => $this->generate_metabox_id(),
			'page_slugs'  => array( 'store-settings-payments' ),
			'title'       => __( 'Paypal Express Checkout Settings', 'mp' ),
			'option_name' => 'mp_settings',
			'desc'        => __( 'Express Checkout is PayPal\'s premier checkout solution, which streamlines the checkout process for buyers and keeps them on your site after making a purchase. Unlike PayPal Pro, there are no additional fees to use Express Checkout, though you may need to do a free upgrade to a business account. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">More Info &raquo;</a>', 'mp' ),
			'conditional' => array(
				'name'   => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		if ( mp_get_network_setting( 'global_cart' ) ) {
			$metabox->add_field( 'text', array(
				'name'       => $this->get_field_name( 'merchant_email' ),
				'label'      => array( 'text' => __( 'Merchant Email', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'email'    => true,
				),
			) );
		} else {
			$this->common_metabox_fields( $metabox );
		}
	}

	/**
	 * Both network settings and blog setting use these same fields
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param WPMUDEV_Metabox $metabox
	 */
	function common_metabox_fields( $metabox ) {
		$metabox->add_field( 'advanced_select', array(
			'name'     => $this->get_field_name( 'locale' ),
			'label'    => array( 'text' => __( 'Locale', 'mp' ) ),
			'multiple' => false,
			'options'  => $this->locales,
			'width'    => 'element',
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'     => $this->get_field_name( 'currency' ),
			'label'    => array( 'text' => __( 'Currency', 'mp' ) ),
			'multiple' => false,
			'options'  => $this->currencies,
			'width'    => 'element',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => $this->get_field_name( 'mode' ),
			'label'         => array( 'text' => __( 'Mode', 'mp' ) ),
			'default_value' => 'sandbox',
			'options'       => array(
				'sandbox' => __( 'Sandbox', 'mp' ),
				'live'    => __( 'Live', 'mp' ),
			),
		) );
		$creds = $metabox->add_field( 'complex', array(
			'name'  => $this->get_field_name( 'api_credentials' ),
			'label' => array( 'text' => __( 'API Credentials', 'mp' ) ),
			'desc'  => __( 'You must login to PayPal and create an API signature to get your credentials. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">Instructions &raquo;</a>', 'mp' ),
		) );

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field( 'text', array(
				'name'       => 'username',
				'label'      => array( 'text' => __( 'Username', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$creds->add_field( 'text', array(
				'name'       => 'password',
				'label'      => array( 'text' => __( 'Password', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$creds->add_field( 'text', array(
				'name'       => 'signature',
				'label'      => array( 'text' => __( 'Signature', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$metabox->add_field( 'file', array(
			'name'  => $this->get_field_name( 'header_img' ),
			'label' => array( 'text' => __( 'Header Image', 'mp' ) ),
			'desc'  => __( 'URL for an image you want to appear at the top left of the payment page. The image has a maximum size of 750 pixels wide by 90 pixels high. PayPal recommends that you provide an image that is stored on a secure (https) server. If you do not specify an image, the business name is displayed.', 'mp' ),
		) );
		$metabox->add_field( 'colorpicker', array(
			'name'  => $this->get_field_name( 'header_border' ),
			'label' => array( 'text' => __( 'Header Border Color', 'mp' ) ),
			'desc'  => __( 'Sets the border color around the header of the payment page. The border is a 2-pixel perimeter around the header space, which is 750 pixels wide by 90 pixels high. By default, the color is black.', 'mp' ),
		) );
		$metabox->add_field( 'colorpicker', array(
			'name'  => $this->get_field_name( 'header_back' ),
			'label' => array( 'text' => __( 'Header Background Color', 'mp' ) ),
			'desc'  => __( 'Sets the background color for the header of the payment page. By default, the color is white.', 'mp' ),
		) );
		$metabox->add_field( 'colorpicker', array(
			'name'  => $this->get_field_name( 'page_back' ),
			'label' => array( 'text' => __( 'Page Background Color', 'mp' ) ),
			'desc'  => __( 'Sets the background color for the payment page. By default, the color is white.', 'mp' ),
		) );
	}

	/**
	 * Get the confirm order html
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_checkout/confirm_order_html/{plugin_name}
	 */
	public function confirm_order_html( $html ) {
		$html .= '
			<input type="hidden" name="mp_paypal_token" value="' . mp_get_get_value( 'token' ) . '" />
			<input type="hidden" name="mp_paypal_payer_id" value="' . mp_get_get_value( 'PayerID' ) . '" />
			<div style="display:none"><input type="radio" name="payment_method" value="' . $this->plugin_name . '" checked /></div>';
		$html .= parent::confirm_order_html( $html );

		return $html;
	}

	/**
	 * Return fields you need to add to the payment screen, like your credit card info fields.
	 *    If you don't need to add form fields set $skip_form to true so this page can be skipped
	 *    at checkout.
	 *
	 * @param array $cart . Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info . Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		return __( 'You will be redirected to the PayPal site to finalize your payment.', 'mp' );
	}

	/**
	 * Use this to authorize ordered transactions.
	 *
	 * @param array $order Contains the list of order ids
	 */
	function process_payment_authorize( $orders ) {
		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$transaction_id = $order['transaction_id'];
				$amount         = $order['amount'];

				$authorization = $this->DoAuthorization( $transaction_id, $amount );

				switch ( $result["PAYMENTSTATUS"] ) {
					case 'Canceled-Reversal':
						$status     = __( 'A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.', 'mp' );
						$authorized = true;
						break;
					case 'Expired':
						$status     = __( 'The authorization period for this payment has been reached.', 'mp' );
						$authorized = false;
						break;
					case 'Voided':
						$status     = __( 'An authorization for this transaction has been voided.', 'mp' );
						$authorized = false;
						break;
					case 'Failed':
						$status     = __( 'The payment has failed. This happens only if the payment was made from your customer\'s bank account.', 'mp' );
						$authorized = false;
						break;
					case 'Partially-Refunded':
						$status     = __( 'The payment has been partially refunded.', 'mp' );
						$authorized = true;
						break;
					case 'In-Progress':
						$status     = __( 'The transaction has not terminated, e.g. an authorization may be awaiting completion.', 'mp' );
						$authorized = false;
						break;
					case 'Completed':
						$status     = __( 'The payment has been completed, and the funds have been added successfully to your account balance.', 'mp' );
						$authorized = true;
						break;
					case 'Processed':
						$status     = __( 'A payment has been accepted.', 'mp' );
						$authorized = true;
						break;
					case 'Reversed':
						$status          = __( 'A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer', 'mp' );
						$reverse_reasons = array(
							'none'            => '',
							'chargeback'      => __( 'A reversal has occurred on this transaction due to a chargeback by your customer.', 'mp' ),
							'guarantee'       => __( 'A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', 'mp' ),
							'buyer-complaint' => __( 'A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', 'mp' ),
							'refund'          => __( 'A reversal has occurred on this transaction because you have given the customer a refund.', 'mp' ),
							'other'           => __( 'A reversal has occurred on this transaction due to an unknown reason.', 'mp' )
						);
						$status .= ': ' . $reverse_reasons[ $result["REASONCODE"] ];
						$authorized = false;
						break;
					case 'Refunded':
						$status     = __( 'You refunded the payment.', 'mp' );
						$authorized = false;
						break;
					case 'Denied':
						$status     = __( 'You denied the payment when it was marked as pending.', 'mp' );
						$authorized = false;
						break;
					case 'Pending':
						$pending_str = array(
							'address'        => __( 'The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences	 section of your Profile.', 'mp' ),
							'authorization'  => __( 'The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'mp' ),
							'echeck'         => __( 'The payment is pending because it was made by an eCheck that has not yet cleared.', 'mp' ),
							'intl'           => __( 'The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'mp' ),
							'multi-currency' => __( 'You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'mp' ),
							'order'          => __( 'The payment is pending because it is part of an order that has been authorized but not settled.', 'mp' ),
							'paymentreview'  => __( 'The payment is pending while it is being reviewed by PayPal for risk.', 'mp' ),
							'unilateral'     => __( 'The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'mp' ),
							'upgrade'        => __( 'The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. It can also mean that you have reached the monthly limit for transactions on your account.', 'mp' ),
							'verify'         => __( 'The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'mp' ),
							'other'          => __( 'The payment is pending for an unknown reason. For more information, contact PayPal customer service.', 'mp' ),
							'*'              => ''
						);
						$status      = __( 'The payment is pending', 'mp' );
						if ( isset( $pending_str[ $result["PENDINGREASON"] ] ) ) {
							$status .= ': ' . $pending_str[ $result["PENDINGREASON"] ];
						}
						$authorized = false;
						break;
					default:
						// case: various error cases
						$authorized = false;
				}

				if ( $authorized ) {
					update_post_meta( $order['order_id'], 'mp_deal', 'authorized' );
					update_post_meta( $order['order_id'], 'mp_deal_authorization_id', $authorization['TRANSACTIONID'] );
				}
			}
		}
	}

	/**
	 * Use this to capture authorized transactions.
	 *
	 * @param array $cart . Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $authorizations Contains the list of authorization ids
	 */
	function process_payment_capture( $authorizations ) {
		if ( is_array( $authorizations ) ) {
			foreach ( $authorizations as $authorization ) {
				$transaction_id = $authorization['transaction_id'];
				$amount         = $authorization['amount'];

				$capture = $this->DoCapture( $transaction_id, $amount );

				update_post_meta( $authorization['deal_id'], 'mp_deal', 'captured' );
			}
		}
	}

	function process_payment( $cart, $billing_info, $shipping_info ) {
		if ( ( $token = mp_get_post_value( 'mp_paypal_token' ) ) && ( $payer_id = mp_get_post_value( 'mp_paypal_payer_id' ) ) ) {
			// Buyer has already setup payment with PayPal - process order
			$error    = false;
			$response = $this->_do_express_checkout_payment( $token, $payer_id );
			if ( ! is_wp_error( $response ) ) {
				if ( 'Success' == mp_arr_get_value( 'ACK', $response ) || 'SuccessWithWarning' == mp_arr_get_value( 'ACK', $response ) ) {
					$blog_id      = get_current_blog_id();
					$timestamp    = time();
					$tracking_url = '';
					$order_id     = '';
					if ( $cart->is_global && is_multisite() ) {
						$index    = 0;
						$blog_ids = $cart->get_blog_ids();
						foreach ( $blog_ids as $bid ) {
							switch_to_blog( $bid );
							$order_id = mp_get_session_value( 'paypal_request->PAYMENTREQUEST_' . $index . '_INVNUM' );
							if ( $order_id ) {
								//we need to create a vritual cart
								$vcart = mp_get_single_site_cart( $bid );
								if ( count( $vcart->get_items() ) == 0 ) {
									continue;
								}
								$order = new MP_Order( $order_id );
								$order->save( array(
									'payment_info'       => array(
										'gateway_public_name'  => $this->public_name,
										'gateway_private_name' => $this->admin_name,
										'status'               => array(
											$timestamp => __( 'Paid', 'mp' ),
										),
										'total'                => $vcart->total(),
										'currency'             => $this->currencyCode,
										'transaction_id'       => mp_arr_get_value( 'PAYMENTINFO_' . $index . '_TRANSACTIONID', $response ),
										'method'               => __( 'PayPal', 'mp' ),
									),
									'cart'               => $vcart,
									'paid'               => true,
									'shipping_total'     => $vcart->shipping_total( false ),
									'shipping_tax_total' => $vcart->shipping_tax_total( false ),
									'tax_total'          => $vcart->tax_total( false ),
								) );
								$tracking_url = $order->tracking_url( false );
							}
							$index ++;
						}
						if ( ! empty( $order_id ) ) {
							//we will need to store the order for later referrer
							$global_order_index              = get_site_option( 'mp_global_order_index', array() );
							$global_order_index[ $order_id ] = $cart;
							update_site_option( 'mp_global_order_index', $global_order_index );
						}
						$cart->empty_cart();
						switch_to_blog( $blog_id );
					} else {
						if ( $order_id = mp_get_session_value( 'paypal_request->PAYMENTREQUEST_0_INVNUM' ) ) {
							$order = new MP_Order( $order_id );
							$order->save( array(
								'payment_info' => array(
									'gateway_public_name'  => $this->public_name,
									'gateway_private_name' => $this->admin_name,
									'status'               => array(
										$timestamp => __( 'Paid', 'mp' ),
									),
									'total'                => $cart->total(),
									'currency'             => $this->currencyCode,
									'transaction_id'       => mp_arr_get_value( 'PAYMENTINFO_0_TRANSACTIONID', $response ),
									'method'               => __( 'Credit Card', 'mp' ),
								),
								'cart'         => mp_cart(),
								'paid'         => true,
							) );
							$tracking_url = $order->tracking_url( false );
						}
					}
					unset( $_SESSION['paypal_request'] );
					wp_redirect( $tracking_url );
					exit;
				} else {
					$error = $this->_get_error( $response );
				}
			} else {
				$error = $response->get_error_message();
			}

			if ( false !== $error ) {
				mp_checkout()->add_error( $error, 'order-review-payment' );
			}

			return;
		}

		//create order id for paypal invoice
		$order    = new MP_Order();
		$order_id = $order->get_id();

		$result = $this->_set_express_checkout( $cart, $billing_info, $shipping_info, $order_id );

		//check response
		if ( ! is_wp_error( $result ) ) {
			if ( 'Success' == mp_arr_get_value( 'ACK', $result ) || 'SuccessWithWarning' == mp_arr_get_value( 'ACK', $result ) ) {
				$token = urldecode( mp_arr_get_value( 'TOKEN', $result ) );
				$this->_redirect_to_paypal( $token );
			} else {
				$error = $this->_get_error( $result );
				mp_checkout()->add_error( $error, 'order-review-payment' );
			}
		} else {
			mp_checkout()->add_error( $result->get_error_message(), 'order-review-payment' );
		}
	}

	/**
	 * Use to handle any payment returns from your gateway to the ipn_url. Do not echo anything here. If you encounter errors
	 *    return the proper headers to your ipn sender. Exits after.
	 */
	function process_ipn_return() {
		$payment_status = mp_get_post_value( 'payment_status' );
		$txn_type       = mp_get_post_value( 'txn_type' );
		$invoice        = mp_get_post_value( 'invoice' );

		if ( empty( $payment_status ) || empty( $txn_type ) || empty( $invoice ) ) {
			header( 'Status: 404 Not Found' );
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}

		$domain = 'https://www.paypal.com/cgi-bin/webscr';
		if ( 'sandbox' == $this->get_setting( 'mode' ) ) {
			$domain = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		if ( get_magic_quotes_gpc() ) {
			$_POST = array_map( 'stripslashes', $_POST );
		}

		$req = array_merge( array( 'cmd' => '_notify-validate' ), $_POST );

		$response = wp_remote_post( $domain, array(
			'user-agent' => 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | PayPal Express Plugin/' . MP_VERSION,
			'body'       => http_build_query( $req ),
			'sslverify'  => false,
			'timeout'    => mp_get_api_timeout( $this->plugin_name ),
		) );

		//check results
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 || $response['body'] != 'VERIFIED' ) {
			header( "HTTP/1.1 503 Service Unavailable" );
			_e( 'There was a problem verifying the IPN string with PayPal. Please try again.', 'mp' );
			exit;
		}

		$order = new MP_Order( $invoice );

		// process PayPal response
		switch ( $payment_status ) {
			case 'Canceled-Reversal':
				$status = __( 'A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.', 'mp' );
				$paid   = true;
				break;

			case 'Expired':
				$status = __( 'The authorization period for this payment has been reached.', 'mp' );
				$paid   = false;
				break;

			case 'Voided':
				$status = __( 'An authorization for this transaction has been voided.', 'mp' );
				$paid   = false;
				break;

			case 'Failed':
				$status = __( "The payment has failed. This happens only if the payment was made from your customer's bank account.", 'mp' );
				$paid   = false;
				break;

			case 'Partially-Refunded':
				$status = __( 'The payment has been partially refunded.', 'mp' );
				$paid   = true;
				break;

			case 'In-Progress':
				$status = __( 'The transaction has not terminated, e.g. an authorization may be awaiting completion.', 'mp' );
				$paid   = false;
				break;

			case 'Completed':
				$status = __( 'The payment has been completed, and the funds have been added successfully to your account balance.', 'mp' );
				$paid   = true;
				break;

			case 'Processed':
				$status = __( 'A payment has been accepted.', 'mp' );
				break;

			case 'Reversed':
				$status          = __( 'A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer:', 'mp' );
				$reverse_reasons = array(
					'none'            => '',
					'chargeback'      => __( 'A reversal has occurred on this transaction due to a chargeback by your customer.', 'mp' ),
					'guarantee'       => __( 'A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', 'mp' ),
					'buyer-complaint' => __( 'A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', 'mp' ),
					'refund'          => __( 'A reversal has occurred on this transaction because you have given the customer a refund.', 'mp' ),
					'other'           => __( 'A reversal has occurred on this transaction due to an unknown reason.', 'mp' )
				);
				$status .= '<br />' . mp_arr_get_value( mp_get_post_value( 'reason_code' ), $reverse_reasons, '' );
				$paid = false;
				break;

			case 'Refunded':
				$status = __( 'You refunded the payment.', 'mp' );
				$paid   = false;
				break;

			case 'Denied':
				$status = __( 'You denied the payment when it was marked as pending.', 'mp' );
				$paid   = false;
				break;

			case 'Pending':
				$pending_str = array(
					'address'        => __( 'The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences	 section of your Profile.', 'mp' ),
					'authorization'  => __( 'The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'mp' ),
					'echeck'         => __( 'The payment is pending because it was made by an eCheck that has not yet cleared.', 'mp' ),
					'intl'           => __( 'The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'mp' ),
					'multi-currency' => __( 'You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'mp' ),
					'order'          => __( 'The payment is pending because it is part of an order that has been authorized but not settled.', 'mp' ),
					'paymentreview'  => __( 'The payment is pending while it is being reviewed by PayPal for risk.', 'mp' ),
					'unilateral'     => __( 'The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'mp' ),
					'upgrade'        => __( 'The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. It can also mean that you have reached the monthly limit for transactions on your account.', 'mp' ),
					'verify'         => __( 'The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'mp' ),
					'other'          => __( 'The payment is pending for an unknown reason. For more information, contact PayPal customer service.', 'mp' ),
					'*'              => ''
				);
				$status      = __( 'The payment is pending.', 'mp' );
				$status .= '<br />' . mp_arr_get_value( mp_get_post_value( 'pending_reason' ), $pending_str, '' );
				$paid = false;
				break;
		}

		if ( $order->post_status == 'order_paid' || $order->post_status == 'order_received' ) {
			$order->change_status( ( $paid ) ? 'paid' : 'received' );
		}

		$order->log_ipn_status( $payment_status . ': ' . $status );
	}

	function trim_name( $name, $length = 127 ) {
		while ( strlen( urlencode( $name ) ) > $length ) {
			$name = substr( $name, 0, - 1 );
		}

		return urldecode( $name );
	}

}

//register shipping plugin
mp_register_gateway_plugin( 'MP_Gateway_Paypal_Express', 'paypal_express', __( 'PayPal Express Checkout', 'mp' ), true );