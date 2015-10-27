<?php
/*
MarketPress 2Checkout Gateway Plugin
Author: S H Mohanjith (Incsub), Marko Miljus (Incsub)
*/

class MP_Gateway_2Checkout extends MP_Gateway_API {
	//the current build version
	var $build = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = '2checkout';
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
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form = true;
	//credit card vars
	var $API_Username, $API_Password, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale;
	//if the gateway uses the order confirmation step during checkout (e.g. PayPal)
	var $use_confirmation_step = false;
	
	/**
	 * Refers to the gateways currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array(
		"AED" => 'AED - United Arab Emirates Dirham',
		"ARS" => 'ARS - Argentina Peso',
		"AUD" => 'AUD - Australian Dollar',
		"BRL" => 'BRL - Brazilian Real',
		"CAD" => 'CAD - Canadian Dollar',
		"CHF" => 'CHF - Swiss Franc',
		"DKK" => 'DKK - Danish Krone',
		"EUR" => 'EUR - Euro',
		"GBP" => 'GBP - British Pound',
		"HKD" => 'HKD - Hong Kong Dollar',
		"INR" => 'INR - Indian Rupee',
		"ILS" => 'ILS - Israeli New Sheqel',
		"JPY" => 'JPY - Japanese Yen',
		"LTL" => 'LTL - Lithuanian Litas',
		"MYR" => 'MYR - Malaysian Ringgit',
		"MXN" => 'MXN - Mexican Peso',
		"NOK" => 'NOK - Norwegian Krone',
		"NZD" => 'NZD - New Zealand Dollar',
		"PHP" => 'Philippine Peso',
		"RON" => 'Romanian New Leu',
		"RUB" => 'Russian Ruble',
		"SGD" => 'Singapore Dollar',
		"SEK" => 'SEK - Swedish Krona',
		"TRY" => 'TRY - Turkish Lira',
		"USD" => 'USD - U.S. Dollar',
		"ZAR" => 'ZAR - South African Rand'
	);

	/*		 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('2Checkout', 'mp');
		$this->public_name = __('2Checkout', 'mp');
		$this->method_img_url = mp_plugin_url('images/2co_logo.png');
		$this->method_button_img_url = mp_plugin_url('images/2co.png');
		$this->currencyCode = $this->get_setting('currency');
		$this->API_Username = $this->get_setting('api_credentials->sid');
		$this->API_Password = $this->get_setting('api_credentials->secret_word');
		$this->SandboxFlag = $this->get_setting('mode');
		
		add_filter( 'mp_checkout/address_fields_array', array( &$this, 'address_fields_array' ), 10, 2 );
	}
	
	/**
	 * Filter the address fields array
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_checkout/address_fields_array
	 */
	public function address_fields_array( $fields, $type ) {
		 foreach ( $fields as $k => &$field ) {
			if ( $name = mp_arr_get_value( 'name', $field ) ) {
				if ( $type . '[phone]' == $name ) {
					// make phone field required
					$field['validation'] = array(
						'required' => true,
					);
				}
			}
		 }
		 
		 return $fields;
	}
	
	/**
	 * Updates the gateway settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	function update( $settings) {
		 if ( ($seller_id = $this->get_setting('sid')) && ($secret_word = $this->get_setting('secret_word')) ) {
			mp_push_to_array($settings, 'gateways->2checkout->api_credentials->sid', $seller_id);
			mp_push_to_array($settings, 'gateways->2checkout->api_credentials->secret_word', $secret_word);
			unset($settings['gateways']['2checkout']['sid'], $settings['gateways']['2checkout']['secret_word']);
		 }
		 
		 return $settings;
	}

	/**
	 * Return fields you need to add to the top of the payment screen, like your credit card info fields
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		/* if (isset($_GET['2checkout_cancel'])) {
				echo '<div class="mp_checkout_error">' . __('Your 2Checkout transaction has been canceled.', 'mp') . '</div>';
		} */
		return __( 'You will be redirected to the 2Checkout site to finalize your payment.', 'mp' ); 
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
		$params = array();
		$order = new MP_Order();
		$return_url = mp_store_page_url( 'checkout-confirm', false );
		
		if ( 'sandbox' == $this->SandboxFlag ) {
			$url = "https://sandbox.2checkout.com/checkout/purchase";
		} else {
			$url = "https://www.2checkout.com/checkout/purchase";
		}

		$params['sid'] = $this->API_Username;
		$params['cart_order_id'] = $params['merchant_order_id'] = $order->get_id();
		$params['x_receipt_link_url'] = $return_url;
		$params['purchase_step'] = 'payment-method';
		$params['currency_code'] = $this->currencyCode;
		$params['mode'] = '2CO';
		
		// set shipping address
		foreach ( $shipping_info as $k => $v ) {
			switch ( $k ) {
				case 'address1' :
					$params['ship_street_address'] = $v;
				break;
				
				case 'address2' :
					$params['ship_street_address2'] = $v;
				break;
				
				case 'first_name' :
					$params['ship_name'] = $v;
				break;
				
				case 'last_name' :
					$params['ship_name'] .= ' ' . $v;
				break;
				
				default :
					$params[ 'ship_' . $k ] = $v;
				break;
			}
		}
		
		// set billing info
		foreach ( $billing_info as $k => $v ) {
			switch ( $k ) {
				case 'address1' :
					$params['street_address'] = $v;
				break;
				
				case 'address2' :
					$params['street_address2'] = $v;
				break;
				
				case 'first_name' :
					$params['card_holder_name'] = $v;
				break;
				
				case 'last_name' :
					$params['card_holder_name'] .= ' ' . $v;
				break;
				
				default :
					$params[ $k ] = $v;
				break;
			}
		}

		$total = 0;
		$counter = 1;
		$items = $cart->get_items_as_objects();
		
		foreach ( $items as $item ) {
			$price = $item->get_price( 'lowest' );
			
			$total += ($price * $item->qty);
			
			$prefix = 'li_' . $counter;
			$params["{$prefix}_product_id"] = $item->get_meta( 'sku', $item->ID );
			$params["{$prefix}_name"] = $item->title( false );
			$params["{$prefix}_quantity"] = $item->qty;
			$params["{$prefix}_description"] = $item->url( false );
			$params["{$prefix}_price"] = $price;
			$params["{$prefix}_type"] = 'product';
			
			if ( $item->get_meta( 'download', $item->ID ) ) {
				$params["{$prefix}_tangible"] = 'N';
			} else {
				$params["{$prefix}_tangible"] = 'Y';
			}
			
			$counter ++;
		}

		$shipping_tax = 0;
		if ( ($shipping_price = $cart->shipping_total( false )) !== false ) {
			$prefix = 'li_' . $counter;
			$params["{$prefix}_product_id"] = 'shipping';
			$params["{$prefix}_name"] = 'Shipping';
			$params["{$prefix}_type"] = 'shipping';
			$params["{$prefix}_price"] = $shipping_price;
			
			$counter += 1;
			$total += $shipping_price;
		}

		//tax line
		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
			$tax_price = $cart->tax_total( false );
			$prefix = 'li_' . $counter;
			$params["{$prefix}_product_id"] = 'taxes';
			$params["{$prefix}_name"] = 'Taxes';
			$params["{$prefix}_type"] = 'tax';
			$params["{$prefix}_price"] = $tax_price;
			
			$counter += 1;
			$total += $tax_price;
		}

		$params['total'] = $total;
		
		$url .= '?' . http_build_query( $params );
		
		wp_redirect( $url );

		die;
	}

	/**
	 * Runs before page load incase you need to run any scripts before loading the success message page
	 */
	function process_checkout_return() {
		$timestamp = time();
		$total = number_format( round( mp_get_request_value( 'total' ), 2 ), 2, '.', '');
		$order_num = mp_get_request_value( 'order_number' );
		$mp_order_num = mp_get_request_value( 'merchant_order_id' );
		$hash = strtoupper( md5( $this->API_Password . $this->API_Username . $order_num . $total ) );

		if ( mp_get_request_value( 'key' ) == $hash && $mp_order_num ) {
			$status = __('The order has been received', 'mp');
	
			$payment_info = array(
				'gateway_public_name' => $this->public_name,
				'gateway_private_name' => $this->admin_name,
				'status' => array(
					$timestamp => __( 'Paid', 'mp' ),
				),
				'total' => $total,
				'currency' => $this->currencyCode,
				'transaction_id' => $order_num,
				'method' => __( 'Credit Card', 'mp' ),
			);
			 
			$order = new MP_Order( $mp_order_num );
			$order->save( array(
				'cart' => mp_cart(),
				'payment_info' => $payment_info,
				'paid' => true,
			) );
			 
			wp_redirect( $order->tracking_url( false ) );
		}
		
		die;
	}

	/**
	 * Init settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	function init_settings_metabox() {
		 $metabox = new WPMUDEV_Metabox(array(
			'id' => $this->generate_metabox_id(),
			'page_slugs' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => sprintf(__('%s Settings', 'mp'), $this->admin_name),
			'option_name' => 'mp_settings',
			'desc' => sprintf( __( '<ol><li>Set the "Return Method" within <a target="_blank" href="https://sandbox.2checkout.com/sandbox/acct/detail_company_info">Site Management</a> to <strong>Header Redirect</strong> and set the "Return URL" to <strong>%s</strong></li><li>Set your <a target="https://www.2checkout.com/sandbox/notifications/">notifications url</a> to <strong>%s</strong></li></ol>', 'mp' ), $this->return_url, $this->ipn_url ),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][mode]',
			'label' => array('text' => __('Mode', 'mp')),
			'default_value' => 'sandbox',
			'options' => array(
				'sandbox' => __('Sandbox', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => 'gateways[' . $this->plugin_name . '][api_credentials]',
			'label' => array('text' => __('API Credentials', 'mp')),
			'desc' => __('You must login to the 2Checkout vendor dashboard to obtain the seller ID and secret word. <a target="_blank" href="http://help.2checkout.com/articles/FAQ/Where-do-I-set-up-the-Secret-Word/">Instructions &raquo;</a>', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'sid',
				'label' => array('text' => __('Seller ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'secret_word',
				'label' => array('text' => __('Secret Word', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => 'gateways[' . $this->plugin_name . '][currency]',
			'label' => array('text' => __('Currency', 'mp')),
			'multiple' => false,
			'options' => array_merge(array('' => __('Select One', 'mp')), $this->currencies),
			'width' => 'element',
			'validation' => array(
				'required' => true,
			),
		));
	}
	
	/**
	 * INS and payment return
	 */
	function process_ipn_return() {
		if ( 'INVOICE_STATUS_CHANGED' != mp_get_request_value( 'message_type' ) ) {
			 // bail
			 return;
		 }
		 
		$sale_id = mp_get_request_value( 'sale_id' );
		$tco_invoice_id = mp_get_request_value( 'invoice_id' );
		$tco_vendor_order_id = mp_get_request_value( 'vendor_order_id' );
		$tco_invoice_status = mp_get_request_value( 'invoice_status' );
		$tco_hash = mp_get_request_value( 'md5_hash' );
		$total = (float) mp_get_request_value( 'invoice_list_amount' );
		$payment_method = ucfirst( mp_get_request_value( 'payment_type' ) );

		$order = new MP_Order( $tco_vendor_order_id );

		if ( ! $order->exists() ) {
			header( 'HTTP/1.0 404 Not Found' );
			header( 'Content-type: text/plain; charset=UTF-8' );
			die( 'Invoice not found' );
		}

		$calc_key = md5( $sale_id . $this->get_setting( 'api_credentials->sid' ) . $tco_invoice_id . $this->get_setting( 'api_credentials->secret_word' ) );

		if ( strtolower( $tco_hash ) != strtolower( $calc_key ) ) {
			header( 'HTTP/1.0 403 Forbidden' );
			header( 'Content-type: text/plain; charset=UTF-8' );
			die( 'We were unable to authenticate the request' );
		}

		if ( strtolower( $tco_invoice_status ) != 'deposited' ) {
			header( 'HTTP/1.0 200 OK' );
			header( 'Content-type: text/plain; charset=UTF-8' );
			die( 'Thank you very much for letting us know. REF: Not success' );
		}

		if ( $total >= $order->get_meta( 'mp_order_total' ) ) {
			$payment_info = $order->get_meta( 'mp_payment_info' );
			$payment_info['transaction_id'] = $tco_invoice_id;
			$payment_info['method'] = $payment_method;

			$order->update_meta( 'mp_payment_info', $payment_info );
			$order->change_status( 'paid', true );

			header( 'HTTP/1.0 200 OK' );
			header( 'Content-type: text/plain; charset=UTF-8' );
			die( 'Thank you very much for letting us know' );
		}
		
		exit;
	}
}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_2Checkout', '2checkout', __('2Checkout', 'mp'));