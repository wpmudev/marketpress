<?php
/*
MarketPress WePay Gateway Plugin
Author: Marko Miljus (Incsub)
*/

class MP_Gateway_Wepay extends MP_Gateway_API {
	//build
	var $build = 2;
	
	var $version = '1.0b';
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'wepay';
	//name of your gateway, for the admin side.
	var $admin_name = '';
	//public name of your gateway, for lists and such.
	var $public_name = '';
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form = false;
	//api vars
	var $publishable_key, $private_key, $currency, $mode, $checkout_type;

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __( 'WePay', 'mp' );
		$this->public_name = __( 'Credit Card', 'mp' );

		$this->client_id =	$this->get_setting( 'api_credentials->client_id' );
		$this->client_secret =$this->get_setting( 'api_credentials->client_secret' );
		$this->access_token = $this->get_setting( 'api_credentials->access_token' );
		$this->account_id = $this->get_setting( 'api_credentials->account_id' );
		$this->mode = $this->get_setting( 'mode' );
		$this->checkout_type =$this->get_setting( 'checkout_type' );

		$this->force_ssl = (bool) $this->get_setting( 'is_ssl' );
		$this->currency = 'USD';//just USD for now

		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {
		if ( ! mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_enqueue_script( 'wepay-tokenization', 'https://static.wepay.com/min/js/tokenization.3.latest.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'wepay-script', mp_plugin_url( 'includes/common/payment-gateways/wepay-files/wepay.js' ), array( 'wepay-tokenization' ), MP_VERSION, true );
		wp_localize_script( 'wepay-script', 'wepay_script', array(
			'mode' => $this->mode,
			'client_id' => $this->client_id,
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
	 * Updates the gateway settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	public function update( $settings ) {
		if ( $val = $this->get_setting('client_id') ) {
		 	mp_push_to_array($settings, 'gateways->wepay->api_credentials->client_id', $val);
		 	unset($settings['gateways']['wepay']['client_id']);	
		}
		
		if ( $val = $this->get_setting('client_secret') ) {
		 	mp_push_to_array($settings, 'gateways->wepay->api_credentials->client_secret', $val);
		 	unset($settings['gateways']['wepay']['client_secret']);	
		}
		
		if ( $val = $this->get_setting('access_token') ) {
		 	mp_push_to_array($settings, 'gateways->wepay->api_credentials->access_token', $val);
		 	unset($settings['gateways']['wepay']['access_token']);	
		}
		
		if ( $val = $this->get_setting('account_id') ) {
		 	mp_push_to_array($settings, 'gateways->wepay->api_credentials->account_id', $val);
		 	unset($settings['gateways']['wepay']['account_id']);	
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
			'desc' => __('Wepay makes it easy to start accepting credit cards directly on your site with full PCI compliance. Accept cards directly on your site. You don\'t need a merchant account or gateway. WePay handles everything including storing cards. Credit cards go directly to WePay\'s secure environment, and never hit your servers so you can avoid most PCI requirements.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('mode'),
			'label' => array('text' => __('Mode', 'mp')),
			'desc' => __('Choose STAGING if you have registered the app on stage.wepay.com, or PRODUCTION if you registered on www.wepay.com', 'mp'),
			'default_value' => 'staging',
			'options' => array(
				'staging' => __('Staging', 'mp'),
				'production' => __('Production', 'mp'),
			),
		));
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('checkout_type'),
			'label' => array('text' => __('Checkout Type', 'mp')),
			'desc' => __('Choose type of payments', 'mp'),
			'options' => array(
				'' => __('Select One', 'mp'),
				'GOODS' => __('Goods', 'mp'),
				'SERVICE' => __('Service', 'mp'),
				'PERSONAL' => __('Personal', 'mp'),
				'EVENT' => __('Event', 'mp'),
				'DONATION' => __('Donation', 'mp'),
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('is_ssl'),
			'label' => array('text' => __('Force SSL?', 'mp')),
			'desc' => __('When in live mode it is recommended to use SSL certificate setup for the site where the checkout form will be displayed.', 'mp'),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials'),
			'label' => array('text' => __('API Credentials', 'mp')),
			'desc' => __('You must login to WePay to <a target="_blank" href="https://www.wepay.com/">get your API credentials</a>. Make sure to check "Tokenize credit cards" option under "API Keys" section of your WePay app.', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'client_id',
				'label' => array('text' => __('Client ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'client_secret',
				'label' => array('text' => __('Client Secret', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'access_token',
				'label' => array('text' => __('Access Token', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'account_id',
				'label' => array('text' => __('Account ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
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
		$card_token = mp_get_post_value( 'wepay_token' );

		if ( ! $card_token ) {
			mp_checkout()->add_error( __( 'The WePay Card Token was not generated correctly. Please go back and try again.', 'mp' ), 'order-review-payment' );
			return false;
		}

		$total = $cart->total( false );
		$order = new MP_Order();
		$order_id = $order->get_id();

		//Get the WePay SDK
		if ( ! class_exists( 'WePay' ) ) {
			require_once mp_plugin_dir( 'includes/common/payment-gateways/wepay-files/wepay-sdk.php' );
		}

		try {
			// Application settings
			$account_id = $this->account_id;
			$client_id = $this->client_id;
			$client_secret = $this->client_secret;
			$access_token = $this->access_token;

			// Credit card id to charge
			//$credit_card_id = $_SESSION['payment_method_id'];
			
			if ( 'staging' == $this->mode ) {
				WePay::useStaging( $this->client_id, $this->client_secret );
			} else {
				WePay::useProduction( $this->client_id, $this->client_secret );
			}

			$wepay = new WePay( $access_token );

			// charge the credit card
			$response = $wepay->request( 'checkout/create', array(
				'account_id' => $account_id,
				'amount' => $total,
				'currency' => 'USD',
				'short_description' => $order_id,
				'type' => strtolower($this->checkout_type),
				//'payment_method_id' => $card_token,
				//'payment_method_type' => 'credit_card',
				'payment_method'      => array(
					'type'            => 'credit_card',
					'credit_card'     => array(
						'id'          => $card_token
					)
				)
			) );


			if ( isset( $response->state ) && $response->state == 'authorized' ) {
				$credit_card_response = $wepay->request( '/credit_card', array(
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'credit_card_id' => $card_token,
				) );

				//setup our payment details
				$payment_info = array();
				$payment_info['gateway_public_name'] = $this->public_name;
				$payment_info['gateway_private_name'] = $this->admin_name;
				$payment_info['method'] = sprintf( __( '%1$s', 'mp' ), $credit_card_response->credit_card_name );
				$payment_info['transaction_id'] = $order_id;
				$payment_info['status'][ time() ] = __('Paid', 'mp');
				$payment_info['total'] = $total;
				$payment_info['currency'] = $this->currency;

				$order->save( array(
					'payment_info' => $payment_info,
					'cart' => $cart,
					'paid' => true,
				) );
				
				wp_redirect( $order->tracking_url( false ) );
				exit;
			}
		} catch ( Exception $e ) {
			mp_checkout()->add_error( sprintf( __( 'There was an error processing your card: "%s". Please check your credit card info and try again.', 'mp' ), $e->getMessage() ), 'order-review-payment' );
		}
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
}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_Wepay', 'wepay', __('WePay', 'mp'));
