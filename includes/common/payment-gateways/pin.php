<?php

/*
  MarketPress PIN Gateway (www.pin.net.au) Plugin
  Author: Marko Miljus (Incsub)
 */

class MP_Gateway_PIN extends MP_Gateway_API {

	//build
	var $build					 = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'pin';
	//name of your gateway, for the admin side.
	var $admin_name				 = '';
	//public name of your gateway, for lists and such.
	var $public_name				 = '';
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url			 = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url	 = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form				 = false;
	//api vars
	var $publishable_key, $private_key, $currency;

	/**
	 * Gateway currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array();

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name	 = __( 'PIN', 'mp' );
		$this->public_name	 = __( 'Credit Card', 'mp' );

		$this->method_img_url		 = mp_plugin_url( 'images/credit_card.png' );
		$this->method_button_img_url = mp_plugin_url( 'images/cc-button.png' );

		$this->public_key	 = $this->get_setting( 'api_credentials->public_key' );
		$this->private_key	 = $this->get_setting( 'api_credentials->private_key' );
		$this->force_ssl	 = $this->get_setting( 'is_ssl' );
		$this->currency		 = $this->get_setting( 'currency', 'AUD' );
		$this->currencies	 = array(
			"AUD"	 => __( 'AUD - Australian Dollar', 'mp' ),
			"USD"	 => __( 'USD - United States Dollar', 'mp' ),
			"NZD"	 => __( 'NZD - New Zealand Dollar', 'mp' ),
			'SGD'	 => __( 'SGD - Singapore Dollar', 'mp' ),
			'EUR'	 => __( 'EUR - Euro', 'mp' ),
			'GBP'	 => __( 'GBP - British Pound', 'mp' ),
			'CAD'	 => __( 'CAD - Canadian Dollar', 'mp' ),
			'HKD'	 => __( 'HKD - Hong Kong Dollar', 'mp' ),
			'JPY'	 => __( 'JPY - Japanese Yen', 'mp' ),
		);

		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue checkout scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_enqueue_scripts
	 */
	function enqueue_scripts() {
		if ( !mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_enqueue_script( 'js-pin', 'https://cdn.pin.net.au/pin.v2.js', array( 'jquery' ), null );

		wp_enqueue_script( 'pin-handler', mp_plugin_url( 'includes/common/payment-gateways/pin-files/pin-handler.js' ), array( 'js-pin' ), MP_VERSION );
		wp_localize_script( 'pin-handler', 'pin_vars', array(
			'publishable_api_key'	 => $this->public_key,
			'mode'					 => ( $this->force_ssl ) ? 'live' : 'test',
		) );
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
		return $this->_cc_default_form( false );
	}

	/**
	 * Print checkout scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	function print_checkout_scripts() {
		// Intentionally left blank
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
		if ( $val = $this->get_setting( 'private_key' ) ) {
			mp_push_to_array( $settings, 'gateways->pin->api_credentials->private_key', $val );
			unset( $settings[ 'gateways' ][ 'api' ][ 'private_key' ] );
		}

		if ( $val = $this->get_setting( 'public_key' ) ) {
			mp_push_to_array( $settings, 'gateways->pin->api_credentials->public_key', $val );
			unset( $settings[ 'gateways' ][ 'api' ][ 'public_key' ] );
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
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => $this->generate_metabox_id(),
			'page_slugs'	 => array( 'store-settings-payments', 'store-settings_page_store-settings-payments' ),
			'title'			 => sprintf( __( '%s Settings', 'mp' ), $this->admin_name ),
			'option_name'	 => 'mp_settings',
			'desc'			 => __( 'PIN makes it easy to start accepting credit card payments with Australia’s first all-in-one online payment system. Accept all major credit cards directly on your site. Your sales proceeds are deposited to any Australian bank account, no merchant account required.', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'	 => $this->get_field_name( 'is_ssl' ),
			'label'	 => array( 'text' => __( 'Force SSL?', 'mp' ) ),
			'desc'	 => __( 'When in live mode PIN recommends you have an SSL certificate setup for the site where the checkout form will be displayed.', 'mp' ),
		) );

		$creds = $metabox->add_field( 'complex', array(
			'name'	 => $this->get_field_name( 'api_credentials' ),
			'label'	 => array( 'text' => __( 'API Credentials?', 'mp' ) ),
			'desc'	 => __( 'You must login to PIN to <a target="_blank" href="https://dashboard.pin.net.au/account">get your API credentials</a>. You can enter your test keys, then live ones when ready.', 'mp' ),
		) );

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field( 'text', array(
				'name'		 => 'private_key',
				'label'		 => array( 'text' => __( 'Secret Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$creds->add_field( 'text', array(
				'name'		 => 'public_key',
				'label'		 => array( 'text' => __( 'Publishable Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$metabox->add_field( 'advanced_select', array(
			'name'			 => $this->get_field_name( 'currency' ),
			'label'			 => array( 'text' => __( 'Currency', 'mp' ) ),
			'desc'			 => __( 'Selecting a currency other than currency supported by PIN may cause problems at checkout.', 'mp' ),
			'multiple'		 => false,
			'width'			 => 'element',
			'options'		 => array( '' => __( 'Select One', 'mp' ) ) + $this->currencies,
			'default_value'	 => mp_get_setting( 'currency' ),
			'validation'	 => array(
				'required' => true,
			),
		) );
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
		$card_token = mp_get_post_value( 'card_token' );

		if ( empty( $card_token ) ) {
			mp_checkout()->add_error( __( 'The PIN Token was not generated correctly. Please go back and try again.', 'mp' ) );
			return false;
		}

		if ( $this->force_ssl ) {
			$this->api_url = 'https://api.pin.net.au/1/charges';
		} else {
			$this->api_url = 'https://test-api.pin.net.au/1/charges';
		}

		$total = $cart->total( false );

		$order		 = new MP_Order();
		$order_id	 = $order->get_id();

		try {
			$args = array(
				'httpversion'	 => '1.1',
				'timeout'		 => mp_get_api_timeout( $this->plugin_name ),
				'blocking'		 => true,
				'compress'		 => true,
				'headers'		 => array(
					'Authorization' => 'Basic ' . base64_encode( $this->private_key . ':' . '' ),
				),
				'body'			 => array(
					'amount'		 => ($total * 100),
					'currency'		 => strtolower( $this->currency ),
					'description'	 => sprintf( __( '%s Store Purchase - Order ID: %s, Email: %s', 'mp' ), get_bloginfo( 'name' ), $order_id, mp_arr_get_value( 'email', $billing_info, '' ) ),
					'email'			 => mp_arr_get_value( 'email', $billing_info, '' ),
					'ip_address'	 => $_SERVER[ 'REMOTE_ADDR' ],
					'card_token'	 => $card_token,
				),
			);

			$charge	 = wp_remote_post( $this->api_url, $args );
			$charge	 = json_decode( $charge[ 'body' ], true );
			$charge	 = $charge[ 'response' ];

			if ( $charge[ 'success' ] == true ) {

				$timestamp		 = time();
				$payment_info	 = array(
					'gateway_public_name'	 => $this->public_name,
					'gateway_private_name'	 => $this->admin_name,
					'method'				 => sprintf( __( '%1$s Card %2$s', 'mp' ), ucfirst( $charge[ 'card' ][ 'scheme' ] ), $charge[ 'card' ][ 'display_number' ] ),
					'transaction_id'		 => $charge[ 'token' ],
					'status'				 => array(
						$timestamp => __( 'Paid', 'mp' ),
					),
					'total'					 => $total,
					'currency'				 => $this->currency,
				);

				$order->save( array(
					'cart'			 => $cart,
					'payment_info'	 => $payment_info,
					'billing_info'	 => $billing_info,
					'shipping_info'	 => $shipping_info,
					'paid'			 => true
				) );

				wp_redirect( $order->tracking_url( false ) );
			} else {
				mp_checkout()->add_error( __( '<li>There was an error processing your card. Please verify your credit card information and try again.</li>', 'mp' ), 'order-review-payment' );
				return false;
			}
		} catch ( Exception $e ) {
			mp_checkout()->add_error( sprintf( __( '<li>There was an error processing your card: "%s". Please verify your credit card information and try again.</li>', 'mp' ), $e->getMessage() ), 'order-review-payment' );
			return false;
		}
	}

}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_PIN', 'pin', __( 'PIN', 'mp' ) );
