<?php

/*
  MarketPress Paymill Gateway Plugin
  Author: Marko Miljus
 */

class MP_Gateway_Paymill extends MP_Gateway_API {

//build
	var $build					 = 2;
//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'paymill';
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
	 * The gateway's currencies
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
		$this->admin_name	 = __( 'Paymill', 'mp' );
		$this->public_name	 = __( 'Credit Card', 'mp' );

		$this->method_img_url		 = mp_plugin_url( 'images/credit_card.png' );
		$this->method_button_img_url = mp_plugin_url( 'images/cc-button.png' );

		$this->public_key	 = $this->get_setting( 'api_credentials->public_key' );
		$this->private_key	 = $this->get_setting( 'api_credentials->private_key' );
		$this->force_ssl	 = (bool) $this->get_setting( 'is_ssl' );
		$this->currency		 = $this->get_setting( 'currency', 'EUR' );
		$this->currencies	 = array(
			"EUR"	 => __( 'EUR - Euro', 'mp' ),
			"BGN"	 => __( 'BGN - Bulgarian Leva', 'mp' ),
			"CZK"	 => __( 'CZK - Czech Koruna', 'mp' ),
			"HRK"	 => __( 'HRK - Croatian Kuna', 'mp' ),
			"DKK"	 => __( 'DKK - Danish Krone', 'mp' ),
			"HUF"	 => __( 'HUF - Hungarian Forint', 'mp' ),
			"ISK"	 => __( 'ISK - Iceland Krona', 'mp' ),
			"ILS"	 => __( 'ILS - Israeli Shekel', 'mp' ),
			"LVL"	 => __( 'LVL - Latvian Lat', 'mp' ),
			"CHF"	 => __( 'CHF - Swiss Franc', 'mp' ),
			"LTL"	 => __( 'LTL - Lithuanian Litas', 'mp' ),
			"NOK"	 => __( 'NOK - Norwegian Krone', 'mp' ),
			"PLN"	 => __( 'PLN - Polish Zloty', 'mp' ),
			"RON"	 => __( 'RON - Romanian Leu New', 'mp' ),
			"SEK"	 => __( 'SEK - Swedish Krona', 'mp' ),
			"TRY"	 => __( 'TRY - Turkish Lira', 'mp' ),
			"GBP"	 => __( 'GBP - British Pound', 'mp' )
		);

		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {
		if ( !mp_is_shop_page( 'checkout' ) ) {
			return;
		}
		wp_enqueue_script( 'js-paymill', 'https://bridge.paymill.com/', array( 'jquery' ) );
		wp_enqueue_script( 'paymill-token', mp_plugin_url( 'includes/common/payment-gateways/paymill-files/paymill_token.js' ), array( 'js-paymill', 'jquery' ), MP_VERSION );
		wp_localize_script( 'paymill-token', 'paymill_token', array(
			'public_key'		 => $this->public_key,
			'invalid_cc_number'	 => __( 'Please enter a valid Credit Card Number.', 'mp' ),
			'invalid_expiration' => __( 'Please choose a valid Expiration Date.', 'mp' ),
			'invalid_cvc'		 => __( 'Please enter a valid Card CVC', 'mp' ),
			'expired_card'		 => __( 'Card is no longer valid or has expired', 'mp' ),
			'invalid_cardholder' => __( 'Invalid cardholder', 'mp' ),
		) );
	}

	/**
	 * Return fields you need to add to the top of the payment screen, like your credit card info fields
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		$name = mp_get_user_address_part( 'first_name', 'billing' ) . ' ' . mp_get_user_address_part( 'last_name', 'billing' );

		$totals = array(
			'product_total'	 => $cart->product_total( false ),
			'shipping_total' => $cart->shipping_total( false ),
			'tax_price'		 => 0,
		);

		// Get tax price, if applicable
		if ( !mp_get_setting( 'tax->tax_inclusive' ) ) {
			$totals[ 'tax_price' ] = $cart->tax_total( false );
		}

		// Calc total
		$total = array_sum( $totals );

		$content = '
			<input id="mp-paymill-name" type="hidden" value="' . esc_attr( $name ) . '">
			<input id="mp-paymill-currency" type="hidden" value="' . esc_attr( $this->currency ) . '">
			<input id="mp-paymill-amount" type="hidden" value="' . esc_attr( round( $total * 100 ) ) . '">
			<div class="mp_checkout_field">
				<label class="mp_form_label">' . __( 'Card Number', 'mp' ) . ' <span class="mp_field_required">*</span></label>
				<input id="mp-paymill-cc-num" type="text" pattern="\d*" autocomplete="cc-number" class="mp_form_input mp_form_input-cc-num mp-input-cc-num" data-rule-required="true" data-rule-cc-num="true">
			</div>
			<div class="mp_checkout_fields">
				<div class="mp_checkout_column mp_checkout_field">
					<label class="mp_form_label">' . __( 'Expiration', 'mp' ) . ' <span class="mp_field_required">*</span> <span class="mp_tooltip-help">' . __( 'Enter in <strong>MM/YYYY</strong> or <strong>MM/YY</strong> format', 'mp' ) . '</span></label>
					<input type="text" autocomplete="cc-exp" id="mp-paymill-cc-exp" class="mp_form_input mp_form_input-cc-exp mp-input-cc-exp" data-rule-required="true" data-rule-cc-exp="true">
				</div>
				<div class="mp_checkout_column mp_checkout_field">
					<label class="mp_form_label">' . __( 'Security Code ', 'mp' ) . ' <span class="mp_field_required">*</span> <span class="mp_tooltip-help"><img src="' . mp_plugin_url( 'ui/images/cvv_2.jpg' ) . '" alt="' . __( 'CVV2', 'mp' ) . '"></span></label>
					<input id="mp-paymill-cc-cvc" class="mp_form_input mp_form_input-cc-cvc mp-input-cc-cvc" type="text" autocomplete="off" data-rule-required="true" data-rule-cc-cvc="true">
				</div>
			</div>';

		return $content;
	}

	/* Initialize the settings metabox
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
			'desc'			 => __( "Accept Visa, Mastercard, Maestro UK, Discover and Solo cards directly on your site. You don't need a merchant account or gateway. Credit cards go directly to Paymill's secure environment, and never hit your servers so you can avoid most PCI requirements.", 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'		 => $this->get_field_name( 'is_ssl' ),
			'label'		 => array( 'text' => __( 'Force SSL?', 'mp' ) ),
			'message'	 => __( 'Yes', 'mp' ),
			'desc'		 => __( 'When running on a live site, Paymill recommends you have an SSL certificate setup for the site where the checkout form will be displayed.', 'mp' ),
		) );

		$creds = $metabox->add_field( 'complex', array(
			'name'	 => $this->get_field_name( 'api_credentials' ),
			'label'	 => array( 'text' => __( 'API Credentials', 'mp' ) ),
			'desc'	 => __( 'You must login to Paymill to <a target="_blank" href="https://app.paymill.com/en-gb/auth/login">get your API credentials</a>. You can enter your test keys, then live ones when ready.', 'mp' ),
		) );

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field( 'text', array(
				'name'		 => 'private_key',
				'label'		 => array( 'text' => __( 'Private Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );

			$creds->add_field( 'text', array(
				'name'		 => 'public_key',
				'label'		 => array( 'text' => __( 'Public Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$metabox->add_field( 'advanced_select', array(
			'name'			 => $this->get_field_name( 'currency' ),
			'default_value'	 => mp_get_setting( 'currency' ),
			'label'			 => array( 'text' => __( 'Currency', 'mp' ) ),
			'multiple'		 => false,
			'width'			 => 'element',
			'options'		 => array( '' => __( 'Select One', 'mp' ) ) + $this->currencies,
			'validation'	 => array(
				'required' => true,
			),
		) );
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

		if ( ($private_key = $this->get_setting( 'private_key' )) && ($public_key	 = $this->get_setting( 'public_key' )) ) {
			mp_push_to_array( $settings, 'gateways->paymill->api_credentials->private_key', $private_key );
			mp_push_to_array( $settings, 'gateways->authorizenet-aim->api_credentials->api_key', $public_key );
			unset( $settings[ 'gateways' ][ 'paymill' ][ 'private_key' ], $settings[ 'gateways' ][ 'paymill' ][ 'public_key' ] );
		}
		return $settings;
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
		//make sure token is set at this point
		$token = mp_get_post_value( 'paymill_token' );

		if ( false === $token ) {
			mp_checkout()->add_error( __( 'The Paymill Token was not generated correctly. Please go back and try again.', 'mp' ), 'order-review-payment' );
			return false;
		}


		define( 'PAYMILL_API_HOST', 'https://api.paymill.com/v2/' );
		define( 'PAYMILL_API_KEY', $this->private_key );

		if ( $token ) {
			require mp_plugin_dir( 'includes/common/payment-gateways/paymill-files/lib/Services/Paymill/Transactions.php' );
			$transactionsObject = new Services_Paymill_Transactions( PAYMILL_API_KEY, PAYMILL_API_HOST );

			// Create a new order object
			$order		 = new MP_Order();
			$order_id	 = $order->get_id();

			// Calc total
			$total = $cart->total( false );

			try {
				$params = array(
					'amount'		 => round( $total * 100 ), //// I.e. 49 * 100 = 4900 Cents = 49 EUR
					'currency'		 => strtolower( $this->currency ), // ISO 4217
					'token'			 => $token,
					'description'	 => sprintf( __( '%s Store Purchase - Order ID: %s, Email: %s', 'mp' ), get_bloginfo( 'name' ), $order_id, mp_get_user_address_part( 'email', 'billing' ) )
				);

				$charge = $transactionsObject->create( $params );

				if ( $charge[ 'status' ] == 'closed' ) {

					$timestamp		 = time();
					$payment_info	 = array(
						'gateway_public_name'	 => $this->public_name,
						'gateway_private_name'	 => $this->admin_name,
						'method'				 => sprintf( __( '%1$s Card ending in %2$s - Expires %3$s', 'mp' ), ucfirst( $charge[ 'payment' ][ 'card_type' ] ), $charge[ 'payment' ][ 'last4' ], $charge[ 'payment' ][ 'expire_month' ] . '/' . $charge[ 'payment' ][ 'expire_year' ] ),
						'transaction_id'		 => $charge[ 'id' ],
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
				}
			} catch ( Exception $e ) {
				mp_checkout()->add_error( sprintf( __( 'There was an error processing your card: "%s". Please try again.', 'mp' ), $e->getMessage() ), 'payment' );
				return false;
			}
		}
	}

	/**
	 * INS and payment return
	 */
	function process_ipn_return() {
		
	}

	function print_checkout_scripts() {
		// Intentionally left blank
	}

}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_Paymill', 'paymill', __( 'Paymill', 'mp' ) );
