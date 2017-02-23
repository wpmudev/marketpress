<?php

class MP_Gateway_AuthorizeNet_AIM extends MP_Gateway_API {
	//build of the gateway plugin
	var $build = null;
	//private gateway slug. Lowercase alpha (a-z) and underscores (_) only please!
	var $plugin_name = 'authorizenet_aim';
	//name of your gateway, for the admin side.
	var $admin_name = '';
	//public name of your gateway, for lists and such.
	var $public_name = '';
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
	var $API_Username, $API_Password, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale;
	
	/**
	 * Refers to the gateways currencies
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
		'USD' => 'USD - U.S. Dollar',
		'NZD' => 'NZD - New Zealand Dollar',
	);

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 *
	 * @since 3.0
	 * @access public
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('Authorize.net Checkout', 'mp');
		$this->public_name = __('Credit Card', 'mp');

		$this->method_img_url = mp_plugin_url('images/credit_card.png');
		$this->method_button_img_url = mp_plugin_url('images/cc-button.png');

		$this->version = "63.0"; //api version
		
		//set credit card vars
		$this->API_Username = $this->get_setting('api_credentials->api_user');
		$this->currencyCode = $this->get_setting('currency', 'USD');

		//set api urls
		if ( $custom_api = $this->get_setting('custom_api') ) {
			$this->API_Endpoint = esc_url_raw($custom_api);
		} elseif ( $this->get_setting('mode') == 'sandbox' ) {
			$this->API_Endpoint = "https://test.authorize.net/gateway/transact.dll";
			$this->force_ssl = false;
		} else {
			$this->API_Endpoint = "https://secure2.authorize.net/gateway/transact.dll";
		}
	}

	/**
	 * Updates the gateway settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array $settings
	 */
	public function update( $settings ) {
		if ( ($api_user = $this->get_setting('api_user')) && ($api_key = $this->get_setting('api_key')) ) {
			mp_push_to_array($settings, 'gateways->authorizenet-aim->api_credentials->api_user', $api_user);
			mp_push_to_array($settings, 'gateways->authorizenet-aim->api_credentials->api_key', $api_key);
			unset($settings['gateways']['authorizenet-aim']['api_user'], $settings['gateways']['authorizenet-aim']['api_key']);
		}
		
		return $settings;
	}
	
	/**
	 * Return fields you need to add to the top of the payment screen, like your credit card info fields
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
		if ( ! class_exists( 'MP_Gateway_Worker_AuthorizeNet_AIM' ) ) {
			require_once mp_plugin_dir( 'includes/common/payment-gateways/authorizenet-aim/class-mp-gateway-worker-authorizenet-aim.php' );
		}
		
		$timestamp = time();
		$payment = new MP_Gateway_Worker_AuthorizeNet_AIM( $this->API_Endpoint, $this->get_setting('delim_data'),	$this->get_setting('delim_char'), $this->get_setting('encap_char'), $this->get_setting('api_credentials->api_user'), $this->get_setting('api_credentials->api_key'), ($this->get_setting('mode') == 'sandbox') );
		$items = $cart->get_items_as_objects();
		$order = new MP_Order();
		
		$payment->transaction( mp_get_post_value( 'mp_cc_num' ) );
		
		foreach ( $items as $item ) {
			$item_id = ( '' == $item->get_meta( 'sku', '' ) ) ? $item->ID : $item->get_meta( 'sku' );
			$price = $item->get_price( 'lowest' );
			$payment->addLineItem( $item_id, substr( $item->title( false ), 0, 31), substr( $item->title( false ) . ' - ' . $item->url( false ), 0, 254), $item->qty, $price, 1 );
		}

		// Billing Info
		$payment->setParameter("x_card_code", mp_get_post_value( 'mp_cc_cvc' ) );
		$payment->setParameter("x_exp_date ", str_replace( array( ' ', '/' ), '', mp_get_post_value( 'mp_cc_exp' ) ) );
		$payment->setParameter("x_amount", $cart->total( false ) );
		$payment->setParameter("x_currency_code", $this->currencyCode);

		// Order Info
		$payment->setParameter("x_description", "Order ID: " . $order->get_id());
		$payment->setParameter("x_invoice_num", $order->get_id());
		$payment->setParameter("x_test_request", false);	// this should NEVER be true, even in sandbox mode
		$payment->setParameter("x_duplicate_window", 30);
		
		// E-mail
		$payment->setParameter("x_header_email_receipt",	$this->get_setting('header_email_receipt'));
		$payment->setParameter("x_footer_email_receipt", $this->get_setting('footer_email_receipt'));
		$payment->setParameter("x_email_customer", strtoupper($this->get_setting('email_customer')));

		$address = mp_arr_get_value( 'address1', $billing_info, '' );
		if ( $address2 = mp_arr_get_value( 'address2', $billing_info ) ) {
			$address .= "\n" . $address2;
		}

		//Billing Info
		$payment->setParameter("x_first_name", mp_arr_get_value( 'first_name', $billing_info ));
		$payment->setParameter("x_last_name", mp_arr_get_value( 'last_name', $billing_info ));
		$payment->setParameter("x_address", $address);
		$payment->setParameter("x_city", mp_arr_get_value( 'city', $billing_info ));
		$payment->setParameter("x_state", mp_arr_get_value( 'state', $billing_info ));
		$payment->setParameter("x_country", mp_arr_get_value( 'country', $billing_info ));
		$payment->setParameter("x_zip", mp_arr_get_value( 'zip', $billing_info ));
		$payment->setParameter("x_phone", mp_arr_get_value( 'phone', $billing_info ));
		$payment->setParameter("x_email", mp_arr_get_value( 'email', $billing_info ));

		//Shipping Info - if applicable
		if ( ! $cart->is_download_only() && mp_get_post_value( 'enable_shipping_address' ) ) {
			$address = mp_arr_get_value( 'address1', $shipping_info, '' );
			if ( $address2 = mp_arr_get_value( 'address2', $shipping_info ) ) {
				$address .= "\n" . $address2;
			}

			$payment->setParameter("x_ship_to_first_name", mp_arr_get_value( 'first_name', $shipping_info ));
			$payment->setParameter("x_ship_to_last_name", mp_arr_get_value( 'last_name', $shipping_info ));
			$payment->setParameter("x_ship_to_address", $address);
			$payment->setParameter("x_ship_to_city", mp_arr_get_value( 'city', $shipping_info ));
			$payment->setParameter("x_ship_to_state", mp_arr_get_value( 'state', $shipping_info ));
			$payment->setParameter("x_ship_to_country", mp_arr_get_value( 'country', $shipping_info ));
			$payment->setParameter("x_ship_to_zip", mp_arr_get_value( 'zip', $shipping_info ));
		}
		
		// Customer IP
		$payment->setParameter("x_customer_ip", $_SERVER['REMOTE_ADDR']);

		$payment->process();

		if ( $payment->isApproved() ) {
			//succesful payment, save order
			$order->save( array(
				'cart' => $cart,
				'paid' => ( $payment->isHeldForReview() ) ? false : true,
				'payment_info' => array(
				 'gateway_public_name' => $this->public_name,
				 'gateway_private_name' => $this->admin_name,
				 'method' => $payment->getMethod(),
				 'status' => array(
				 	$timestamp => ( $payment->isHeldForReview() ) ? __( 'Held For Review', 'mp' ) : __( 'Paid', 'mp' ),
				 ),
				 'total' => $cart->total( false ),
				 'currency' => $this->currencyCode,
				 'transaction_id' => $payment->getTransactionID(),
				 ),
			) );
			
			wp_redirect( $order->tracking_url( false ) );
			exit;
		} else {
			$error = $payment->getResponseText();
			mp_checkout()->add_error( sprintf( __( 'There was a problem finalizing your purchase. %s Please <a href="%s">go back and try again</a>.', 'mp'), $error, mp_store_page_url( 'checkout', false ) ), 'order-review-payment' , false );
		}
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
			'desc' => __('Authorize.net AIM is a customizable payment processing solution that gives the merchant control over all the steps in processing a transaction. An SSL certificate is required to use this gateway. USD is the only currency supported by this gateway.', 'mp'),
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
			'desc' => __('You must login to Authorize.net merchant dashboard to obtain the API login ID and API transaction key. <a target="_blank" href="https://support.authorize.net/authkb/index?page=content&id=A576">Instructions &raquo;</a>', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'api_user',
				'label' => array('text' => __('Login ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'api_key',
				'label' => array('text' => __('Transaction Key', 'mp')),
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
		$metabox->add_field('section', array(
			'name' => 'section_advanced_settings',
			'title' => __('Advanced Settings', 'mp'),
			'subtitle' => __('Optional settings to control advanced options', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][delim_char]',
			'label' => array('text' => __('Delimeter Character', 'mp')),
			'desc' => __('Authorize.net default is ",". Otherwise, get this from your credit card processor. If the transactions are not going through, this character is most likely wrong.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][encap_char]',
			'label' => array('text' => __('Encapsulation Character', 'mp')),
			'desc' => __('Authorize.net default is blank. Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong.', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
			'label' => array('text' => __('Email Customer (on success)', 'mp')),
			'default_value' => 'yes',
			'options' => array(
				'yes' => __('Yes', 'mp'),
				'no' => __('No', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][header_email_receipt]',
			'label' => array('text' => __('Customer Receipt Email Header', 'mp')),
			'desc' => __('This text will appear as the header of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
				'value' => 'yes',
				'action' => 'show'
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][footer_email_receipt]',
			'label' => array('text' => __('Customer Receipt Email Footer', 'mp')),
			'desc' => __('This text will appear as the footer of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
				'value' => 'yes',
				'action' => 'show'
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][md5_hash]',
			'label' => array('text' => __('Security: MD5 Hash', 'mp')),
			'desc' => __('The payment gateway generated MD5 hash value that can be used to authenticate the transaction response. Not needed because responses are returned using an SSL connection.', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][delim_data]',
			'label' => array('text' => __('Delim Data', 'mp')),
			'desc' => __('Request a delimited response from the payment gateway.', 'mp'),
			'default_value' => 'yes',
			'options' => array(
				'yes' => __('Yes', 'mp'),
				'no' => __('No', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][custom_api]',
			'label' => array('text' => __('Custom API URL', 'mp')),
			'desc' => __('Many other gateways have Authorize.net API emulators. To use one of these gateways input their API post url here.', 'mp'),
			'validation' => array(
				'url' => true,
			),
		));
	}
}

if ( ! class_exists('MP_Gateway_Worker_AuthorizeNet_AIM' ) ) :
endif;

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_AuthorizeNet_AIM', 'authorizenet_aim', __('Authorize.net AIM Checkout', 'mp'));
