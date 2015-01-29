<?php

/*
  MarketPress Stripe Gateway Plugin
  Author - Aaron Edwards, Marko Miljus
 */

class MP_Gateway_Stripe extends MP_Gateway_API {

	//build
	var $build					 = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'stripe';
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
	var $publishable_key, $secret_key, $currency;

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
		$this->admin_name	 = __( 'Stripe', 'mp' );
		$this->public_name	 = __( 'Credit Card', 'mp' );

		$this->method_img_url		 = mp_plugin_url( 'images/credit_card.png' );
		$this->method_button_img_url = mp_plugin_url( 'images/cc-button.png' );

		$this->publishable_key	 = $this->get_setting( 'api_credentials->publishable_key' );
		$this->secret_key		 = $this->get_setting( 'api_credentials->secret_key' );
		$this->force_ssl		 = (bool) $this->get_setting( 'is_ssl' );
		$this->currency			 = $this->get_setting( 'currency', 'USD' );

		$this->currencies = array(
			"AED"	 => __( 'AED - United Arab Emirates Dirham', 'mp' ),
			"AFN"	 => __( 'AFN - Afghan Afghani*', 'mp' ),
			"ALL"	 => __( 'ALL - Albanian Lek', 'mp' ),
			"AMD"	 => __( 'AMD - Armenian Dram', 'mp' ),
			"ANG"	 => __( 'ANG - Netherlands Antillean Gulden', 'mp' ),
			"AOA"	 => __( 'AOA - Angolan Kwanza*', 'mp' ),
			"ARS"	 => __( 'ARS - Argentine Peso*', 'mp' ),
			"AUD"	 => __( 'AUD - Australian Dollar*', 'mp' ),
			"AWG"	 => __( 'AWG - Aruban Florin', 'mp' ),
			"AZN"	 => __( 'AZN - Azerbaijani Manat', 'mp' ),
			"BAM"	 => __( 'BAM - Bosnia & Herzegovina Convertible Mark', 'mp' ),
			"BBD"	 => __( 'BBD - Barbadian Dollar', 'mp' ),
			"BDT"	 => __( 'BDT - Bangladeshi Taka', 'mp' ),
			"BGN"	 => __( 'BGN - Bulgarian Lev', 'mp' ),
			"BIF"	 => __( 'BIF - Burundian Franc', 'mp' ),
			"BMD"	 => __( 'BMD - Bermudian Dollar', 'mp' ),
			"BND"	 => __( 'BND - Brunei Dollar', 'mp' ),
			"BOB"	 => __( 'BOB - Bolivian Boliviano*', 'mp' ),
			"BRL"	 => __( 'BRL - Brazilian Real*', 'mp' ),
			"BSD"	 => __( 'BSD - Bahamian Dollar', 'mp' ),
			"BWP"	 => __( 'BWP - Botswana Pula', 'mp' ),
			"BZD"	 => __( 'BZD - Belize Dollar', 'mp' ),
			"CAD"	 => __( 'CAD - Canadian Dollar*', 'mp' ),
			"CDF"	 => __( 'CDF - Congolese Franc', 'mp' ),
			"CHF"	 => __( 'CHF - Swiss Franc', 'mp' ),
			"CLP"	 => __( 'CLP - Chilean Peso*', 'mp' ),
			"CNY"	 => __( 'CNY - Chinese Renminbi Yuan', 'mp' ),
			"COP"	 => __( 'COP - Colombian Peso*', 'mp' ),
			"CRC"	 => __( 'CRC - Costa Rican Colón*', 'mp' ),
			"CVE"	 => __( 'CVE - Cape Verdean Escudo*', 'mp' ),
			"CZK"	 => __( 'CZK - Czech Koruna*', 'mp' ),
			"DJF"	 => __( 'DJF - Djiboutian Franc*', 'mp' ),
			"DKK"	 => __( 'DKK - Danish Krone', 'mp' ),
			"DOP"	 => __( 'DOP - Dominican Peso', 'mp' ),
			"DZD"	 => __( 'DZD - Algerian Dinar', 'mp' ),
			"EEK"	 => __( 'EEK - Estonian Kroon*', 'mp' ),
			"EGP"	 => __( 'EGP - Egyptian Pound', 'mp' ),
			"ETB"	 => __( 'ETB - Ethiopian Birr', 'mp' ),
			"EUR"	 => __( 'EUR - Euro', 'mp' ),
			"FJD"	 => __( 'FJD - Fijian Dollar', 'mp' ),
			"FKP"	 => __( 'FKP - Falkland Islands Pound*', 'mp' ),
			"GBP"	 => __( 'GBP - British Pound', 'mp' ),
			"GEL"	 => __( 'GEL - Georgian Lari', 'mp' ),
			"GIP"	 => __( 'GIP - Gibraltar Pound', 'mp' ),
			"GMD"	 => __( 'GMD - Gambian Dalasi', 'mp' ),
			"GNF"	 => __( 'GNF - Guinean Franc*', 'mp' ),
			"GTQ"	 => __( 'GTQ - Guatemalan Quetzal*', 'mp' ),
			"GYD"	 => __( 'GYD - Guyanese Dollar', 'mp' ),
			"HKD"	 => __( 'HKD - Hong Kong Dollar', 'mp' ),
			"HNL"	 => __( 'HNL - Honduran Lempira*', 'mp' ),
			"HRK"	 => __( 'HRK - Croatian Kuna', 'mp' ),
			"HTG"	 => __( 'HTG - Haitian Gourde', 'mp' ),
			"HUF"	 => __( 'HUF - Hungarian Forint', 'mp' ),
			"IDR"	 => __( 'IDR - Indonesian Rupiah', 'mp' ),
			"ILS"	 => __( 'ILS - Israeli New Sheqel', 'mp' ),
			"INR"	 => __( 'INR - Indian Rupee*', 'mp' ),
			"ISK"	 => __( 'ISK - Icelandic Króna', 'mp' ),
			"JMD"	 => __( 'JMD - Jamaican Dollar', 'mp' ),
			"JPY"	 => __( 'JPY - Japanese Yen', 'mp' ),
			"KES"	 => __( 'KES - Kenyan Shilling', 'mp' ),
			"KGS"	 => __( 'KGS - Kyrgyzstani Som', 'mp' ),
			"KHR"	 => __( 'KHR - Cambodian Riel', 'mp' ),
			"KMF"	 => __( 'KMF - Comorian Franc', 'mp' ),
			"KRW"	 => __( 'KRW - South Korean Won', 'mp' ),
			"KYD"	 => __( 'KYD - Cayman Islands Dollar', 'mp' ),
			"KZT"	 => __( 'KZT - Kazakhstani Tenge', 'mp' ),
			"LAK"	 => __( 'LAK - Lao Kip*', 'mp' ),
			"LBP"	 => __( 'LBP - Lebanese Pound', 'mp' ),
			"LKR"	 => __( 'LKR - Sri Lankan Rupee', 'mp' ),
			"LRD"	 => __( 'LRD - Liberian Dollar', 'mp' ),
			"LSL"	 => __( 'LSL - Lesotho Loti', 'mp' ),
			"LTL"	 => __( 'LTL - Lithuanian Litas', 'mp' ),
			"LVL"	 => __( 'LVL - Latvian Lats', 'mp' ),
			"MAD"	 => __( 'MAD - Moroccan Dirham', 'mp' ),
			"MDL"	 => __( 'MDL - Moldovan Leu', 'mp' ),
			"MGA"	 => __( 'MGA - Malagasy Ariary', 'mp' ),
			"MKD"	 => __( 'MKD - Macedonian Denar', 'mp' ),
			"MNT"	 => __( 'MNT - Mongolian Tögrög', 'mp' ),
			"MOP"	 => __( 'MOP - Macanese Pataca', 'mp' ),
			"MRO"	 => __( 'MRO - Mauritanian Ouguiya', 'mp' ),
			"MUR"	 => __( 'MUR - Mauritian Rupee*', 'mp' ),
			"MVR"	 => __( 'MVR - Maldivian Rufiyaa', 'mp' ),
			"MWK"	 => __( 'MWK - Malawian Kwacha', 'mp' ),
			"MXN"	 => __( 'MXN - Mexican Peso*', 'mp' ),
			"MYR"	 => __( 'MYR - Malaysian Ringgit', 'mp' ),
			"MZN"	 => __( 'MZN - Mozambican Metical', 'mp' ),
			"NAD"	 => __( 'NAD - Namibian Dollar', 'mp' ),
			"NGN"	 => __( 'NGN - Nigerian Naira', 'mp' ),
			"NIO"	 => __( 'NIO - Nicaraguan Córdoba*', 'mp' ),
			"NOK"	 => __( 'NOK - Norwegian Krone', 'mp' ),
			"NPR"	 => __( 'NPR - Nepalese Rupee', 'mp' ),
			"NZD"	 => __( 'NZD - New Zealand Dollar', 'mp' ),
			"PAB"	 => __( 'PAB - Panamanian Balboa*', 'mp' ),
			"PEN"	 => __( 'PEN - Peruvian Nuevo Sol*', 'mp' ),
			"PGK"	 => __( 'PGK - Papua New Guinean Kina', 'mp' ),
			"PHP"	 => __( 'PHP - Philippine Peso', 'mp' ),
			"PKR"	 => __( 'PKR - Pakistani Rupee', 'mp' ),
			"PLN"	 => __( 'PLN - Polish Złoty', 'mp' ),
			"PYG"	 => __( 'PYG - Paraguayan Guaraní*', 'mp' ),
			"QAR"	 => __( 'QAR - Qatari Riyal', 'mp' ),
			"RON"	 => __( 'RON - Romanian Leu', 'mp' ),
			"RSD"	 => __( 'RSD - Serbian Dinar', 'mp' ),
			"RUB"	 => __( 'RUB - Russian Ruble', 'mp' ),
			"RWF"	 => __( 'RWF - Rwandan Franc', 'mp' ),
			"SAR"	 => __( 'SAR - Saudi Riyal', 'mp' ),
			"SBD"	 => __( 'SBD - Solomon Islands Dollar', 'mp' ),
			"SCR"	 => __( 'SCR - Seychellois Rupee', 'mp' ),
			"SEK"	 => __( 'SEK - Swedish Krona', 'mp' ),
			"SGD"	 => __( 'SGD - Singapore Dollar', 'mp' ),
			"SHP"	 => __( 'SHP - Saint Helenian Pound*', 'mp' ),
			"SLL"	 => __( 'SLL - Sierra Leonean Leone', 'mp' ),
			"SOS"	 => __( 'SOS - Somali Shilling', 'mp' ),
			"SRD"	 => __( 'SRD - Surinamese Dollar*', 'mp' ),
			"STD"	 => __( 'STD - São Tomé and Príncipe Dobra', 'mp' ),
			"SVC"	 => __( 'SVC - Salvadoran Colón*', 'mp' ),
			"SZL"	 => __( 'SZL - Swazi Lilangeni', 'mp' ),
			"THB"	 => __( 'THB - Thai Baht', 'mp' ),
			"TJS"	 => __( 'TJS - Tajikistani Somoni', 'mp' ),
			"TOP"	 => __( 'TOP - Tongan Paʻanga', 'mp' ),
			"TRY"	 => __( 'TRY - Turkish Lira', 'mp' ),
			"TTD"	 => __( 'TTD - Trinidad and Tobago Dollar', 'mp' ),
			"TWD"	 => __( 'TWD - New Taiwan Dollar', 'mp' ),
			"TZS"	 => __( 'TZS - Tanzanian Shilling', 'mp' ),
			"UAH"	 => __( 'UAH - Ukrainian Hryvnia', 'mp' ),
			"UGX"	 => __( 'UGX - Ugandan Shilling', 'mp' ),
			"USD"	 => __( 'USD - United States Dollar', 'mp' ),
			"UYI"	 => __( 'UYI - Uruguayan Peso*', 'mp' ),
			"UZS"	 => __( 'UZS - Uzbekistani Som', 'mp' ),
			"VEF"	 => __( 'VEF - Venezuelan Bolívar*', 'mp' ),
			"VND"	 => __( 'VND - Vietnamese Đồng', 'mp' ),
			"VUV"	 => __( 'VUV - Vanuatu Vatu', 'mp' ),
			"WST"	 => __( 'WST - Samoan Tala', 'mp' ),
			"XAF"	 => __( 'XAF - Central African Cfa Franc', 'mp' ),
			"XCD"	 => __( 'XCD - East Caribbean Dollar', 'mp' ),
			"XOF"	 => __( 'XOF - West African Cfa Franc*', 'mp' ),
			"XPF"	 => __( 'XPF - Cfp Franc*', 'mp' ),
			"YER"	 => __( 'YER - Yemeni Rial', 'mp' ),
			"ZAR"	 => __( 'ZAR - South African Rand', 'mp' ),
			"ZMW"	 => __( 'ZMW - Zambian Kwacha', 'mp' ),
		);

		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {
		if ( !mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_enqueue_script( 'js-stripe', 'https://js.stripe.com/v2/', array( 'jquery' ), null );
		wp_enqueue_script( 'stripe-token', mp_plugin_url( 'includes/common/payment-gateways/stripe-files/stripe_token.js' ), array( 'js-stripe', 'jquery-ui-core' ), MP_VERSION );
		wp_localize_script( 'stripe-token', 'stripe', array(
			'publisher_key' => $this->publishable_key
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

		$content = '
			<input id="mp-stripe-name" type="hidden" value="' . esc_attr( $name ) . '" />
			<div class="mp-checkout-form-row">
				<label>' . __( 'Card Number', 'mp' ) . '<span class="mp-field-required">*</span></label>
				<input
					id="mp-stripe-cc-num"
					type="text"
					pattern="\d*"
					autocomplete="cc-number"
					class="mp-input-cc-num"
					data-rule-required="true"
					data-rule-cc-num="true"
					style="width:200px" />
			</div>
			<div class="mp-checkout-form-row">
				<div class="mp-checkout-input-complex clearfix">
					<div class="mp-checkout-column">
						<label>' . __( 'Expiration', 'mp' ) . '<span class="mp-field-required">*</span> <span class="mp-tooltip-help">' . __( 'Enter in <strong>MM/YYYY</strong> or <strong>MM/YY</strong> format', 'mp' ) . '</span></label>
						<input
							type="text"
							autocomplete="cc-exp"
							id="mp-stripe-cc-exp"
							class="mp-input-cc-exp"
							data-rule-required="true"
							data-rule-cc-exp="true"
							style="width:100px" />
					</div>
					<div class="mp-checkout-column">
						<label>' . __( 'Security Code ', 'mp' ) . '<span class="mp-field-required">*</span> <span class="mp-tooltip-help"><img src="' . mp_plugin_url( 'ui/images/cvv_2.jpg' ) . '" alt="CVV2" /></span></label>
						<input
							id="mp-stripe-cc-cvc"
							class="mp-input-cc-cvc"
							type="text"
							autocomplete="off"
							data-rule-required="true"
							data-rule-cc-cvc="true"
							style="width:75px;" />
					</div>
				</div>
			</div>';

		return $content;
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
			'desc'			 => __( 'Stripe makes it easy to start accepting credit cards directly on your site with full PCI compliance. Accept Visa, MasterCard, American Express, Discover, JCB, and Diners Club cards directly on your site. You don\'t need a merchant account or gateway. Stripe handles everything, including storing cards, subscriptions, and direct payouts to your bank account. Credit cards go directly to Stripe\'s secure environment, and never hit your servers so you can avoid most PCI requirements.', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'	 => $this->get_field_name( 'is_ssl' ),
			'label'	 => array( 'text' => __( 'Force SSL?', 'mp' ) ),
			'desc'	 => __( 'When in live mode Stripe recommends you have an SSL certificate setup for the site where the checkout form will be displayed.', 'mp' ),
		) );
		$creds	 = $metabox->add_field( 'complex', array(
			'name'	 => $this->get_field_name( 'api_credentials' ),
			'label'	 => array( 'text' => __( 'API Credentials', 'mp' ) ),
			'desc'	 => __( 'You must login to Stripe to <a target="_blank" href="https://manage.stripe.com/#account/apikeys">get your API credentials</a>. You can enter your test credentials, then live ones when ready.', 'mp' ),
		) );

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field( 'text', array(
				'name'		 => 'secret_key',
				'label'		 => array( 'text' => __( 'Secret Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$creds->add_field( 'text', array(
				'name'		 => 'publishable_key',
				'label'		 => array( 'text' => __( 'Publishable Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$metabox->add_field( 'advanced_select', array(
			'name'			 => $this->get_field_name( 'currency' ),
			'label'			 => array( 'text' => __( 'Currency', 'mp' ) ),
			'multiple'		 => false,
			'width'			 => 'element',
			'options'		 => $this->currencies,
			'default_value'	 => mp_get_setting( 'currency' ),
			'desc'			 => __( 'Selecting a currency other than that used for your store may cause problems at checkout.', 'mp' ),
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
		//make sure token is set at this point
		$token = mp_get_post_value( 'stripe_token' );

		if ( false === $token ) {
			mp_checkout()->add_error( __( 'The Stripe Token was not generated correctly. Please go back and try again.', 'mp' ), 'order-review-payment' );
			return false;
		}

		//setup the Stripe API
		if ( !class_exists( 'Stripe' ) ) {
			require_once mp_plugin_dir( 'includes/common/payment-gateways/stripe-files/lib/Stripe.php' );
		}

		Stripe::setApiKey( $this->secret_key );

		$totals = array(
			'product_total'	 => $cart->product_total( false ),
			'shipping_total' => $cart->shipping_total( false ),
			'tax_price'		 => 0,
		);

		// Get tax price, if applicable
		if ( !mp_get_setting( 'tax->tax_inclusive' ) ) {
			$totals[ 'tax_price' ] = $cart->tax_total( false );
		}

		// Create a new order object
		$order		 = new MP_Order();
		$order_id	 = $order->get_id();

		// Calc total
		$total = array_sum( $totals );

		try {
			// create the charge on Stripe's servers - this will charge the user's card
			$charge = Stripe_Charge::create( array(
				'amount'		 => round( $total * 100 ), // amount in cents
				'currency'		 => strtolower( $this->currency ),
				'card'			 => $token,
				'description'	 => sprintf( __( '%s Store Purchase - Order ID - %s, Email - %s', 'mp' ), get_bloginfo( 'name' ), $order_id, mp_get_user_address_part( 'email', 'billing' ) ),
			) );

			if ( $charge->paid == 'true' ) {
				//setup our payment details
				$timestamp		 = time();
				$payment_info	 = array(
					'gateway_public_name'	 => $this->public_name,
					'gateway_private_name'	 => $this->admin_name,
					'method'				 => sprintf( __( '%1$s Card ending in %2$s - Expires %3$s', 'mp' ), $charge->card->type, $charge->card->last4, $charge->card->exp_month . '/' . $charge->card->exp_year ),
					'transaction_id'		 => $charge->id,
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
			mp_checkout()->add_error( sprintf( __( 'There was an error processing your card - "%s". Please try again.', 'mp' ), $e->getMessage() ), 'payment' );
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
mp_register_gateway_plugin( 'MP_Gateway_Stripe', 'stripe', __( 'Stripe', 'mp' ) );
