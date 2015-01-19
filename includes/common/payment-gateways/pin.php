<?php
/*
MarketPress PIN Gateway (www.pin.net.au) Plugin
Author: Marko Miljus (Incsub)
*/

class MP_Gateway_PIN extends MP_Gateway_API {
	//build
	var $build = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'pin';
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
	var $publishable_key, $private_key, $currency;
	
	/**
	 * Gateway currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array(
		"AUD" => 'AUD',
		"NZD" => 'NZD',
		"USD" => 'USD',
		'SGD' => 'SGD',
		'GBP' => 'GBP',
		'EUR' => 'EUR'
	);

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('PIN (beta)', 'mp');
		$this->public_name = __('Credit Card', 'mp');
		$this->method_img_url = mp_plugin_url('images/credit_card.png');
		$this->method_button_img_url = mp_plugin_url('images/cc-button.png');
		$this->public_key = $this->get_setting('api_credentials->public_key');
		$this->private_key = $this->get_setting('api_credentials->private_key');
		$this->force_ssl = $this->get_setting('is_ssl');
		$this->currency = $this->get_setting('currency', 'AUD');
		
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {
		if ( ! mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_enqueue_script( 'js-pin', 'https://cdn.pin.net.au/pin.v2.js', array( 'jquery' ), null );

		wp_enqueue_script( 'pin-handler', mp_plugin_url( 'includes/common/payment-gateways/pin-files/pin-handler.js' ), array( 'js-pin' ), MP_VERSION );
		wp_localize_script( 'pin-handler', 'pin_vars', array(
			'publishable_api_key' => $this->public_key,
			'mode' => ( $this->force_ssl ) ? 'live' : 'test',
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
	 * Use this to process any fields you added. Use the $_POST global,
	 * and be sure to save it to both the $_SESSION and usermeta if logged in.
	 * DO NOT save credit card details to usermeta as it's not PCI compliant.
	 * Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
	 * it will redirect to the next step.
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function process_payment_form($cart, $shipping_info) {
			if (!isset($_POST['card_token']))
					mp()->cart_checkout_error(__('The PIN Token was not generated correctly. Please try again.', 'mp'));

			//save to session
			if (!mp()->checkout_error) {
					$_SESSION['card_token'] = $_POST['card_token'];
					$_SESSION['ip_address'] = $_POST['ip_address'];
			}
	}

	/**
	 * Filters the order confirmation email message body. You may want to append something to
	 *	the message. Optional
	 *
	 * Don't forget to return!
	 */
	function order_confirmation_email($msg, $order) {
			return $msg;
	}

	/**
	 * Return any html you want to show on the confirmation screen after checkout. This
	 *	should be a payment details box and message.
	 *
	 * Don't forget to return!
	 */
	function order_confirmation_msg($content, $order) {
			if ($order->post_status == 'order_paid')
					$content .= '<p>' . sprintf(__('Your payment for this order totaling %s is complete.', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total'])) . '</p>';
			return $content;
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
		if ( $val = $this->get_setting('private_key') ) {
			mp_push_to_array($settings, 'gateways->pin->api_credentials->private_key', $val);
			unset($settings['gateways']['api']['private_key']);	
		}
		
		if ( $val = $this->get_setting('public_key') ) {
			mp_push_to_array($settings, 'gateways->pin->api_credentials->public_key', $val);
			unset($settings['gateways']['api']['public_key']);	
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
			'desc' => __('PIN makes it easy to start accepting credit card payments with Australia’s first all-in-one online payment system. Accept all major credit cards directly on your site. Your sales proceeds are deposited to any Australian bank account, no merchant account required.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('is_ssl'),
			'label' => array('text' => __('Force SSL?', 'mp')),
			'desc' => __('When in live mode PIN recommends you have an SSL certificate setup for the site where the checkout form will be displayed.', 'mp'),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials'),
			'label' => array('text' => __('API Credentials?', 'mp')),
			'desc' => __('You must login to PIN to <a target="_blank" href="https://dashboard.pin.net.au/account">get your API credentials</a>. You can enter your test keys, then live ones when ready.', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'private_key',
				'label' => array('text' => __('Secret Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'public_key',
				'label' => array('text' => __('Publishable Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('currency'),
			'label' => array('text' => __('Currency', 'mp')),
			'desc' => __('Selecting a currency other than currency supported by PIN may cause problems at checkout.', 'mp'),
			'multiple' => false,
			'width' => 'element',
			'options' => array('' => __('Select One', 'mp')) + $this->currencies,
			'default_value' => mp_get_setting('currency'),
			'validation' => array(
				'required' => true,
			),
		));
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
		die( 'here' );
		//make sure token is set at this point
		if ( ! isset($_SESSION['card_token']) ) {
				mp()->cart_checkout_error(__('The PIN Token was not generated correctly. Please go back and try again.', 'mp'));
				return false;
		}

		if ( $this->force_ssl ) {
			$this->api_url = 'https://api.pin.net.au/1/charges';
		} else {
			$this->api_url = 'https://test-api.pin.net.au/1/charges';
		}
		
		$token = $_SESSION['card_token'];

		if ($token) {

				$totals = array();
				$coupon_code = mp()->get_coupon_code();
				
				foreach ($cart as $product_id => $variations) {
						foreach ($variations as $variation => $data) {
							$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);
							$totals[] = $price;
						}
				}

				$total = array_sum($totals);

				//shipping line
				$shipping_tax = 0;
				if ( ($shipping_price = mp()->shipping_price(false)) !== false ) {
					$total += $shipping_price;
					$shipping_tax = (mp()->shipping_tax_price($shipping_price) - $shipping_price);
				}
		
				//tax line if tax inclusive pricing is off. It it's on it would screw up the totals
				if ( ! mp_get_setting('tax->tax_inclusive') ) {
					$tax_price = (mp()->tax_price(false) + $shipping_tax);
					$total += $tax_price;
				}
				
				$order_id = mp()->generate_order_id();

				try {
					$args = array(
						'httpversion' => '1.1',
						'timeout' => mp_get_api_timeout( $this->plugin_name ),
						'blocking' => true,
						'compress' => true,
						'headers' => array(
							'Authorization' => 'Basic ' . base64_encode( $this->private_key . ':' . '' ),
						),
						'body' => array(
							'amount' => (int) $total * 100,
							'currency' => strtolower( $this->currency ),
							'description' => sprintf( __( '%s Store Purchase - Order ID: %s, Email: %s', 'mp'), get_bloginfo( 'name' ), $order_id, $_SESSION['mp_shipping_info']['email']),
							'email' => mp_arr_get_value( 'email', $billing_info, '' ),
							'ip_address' => $_SESSION['ip_address'],
							'card_token' => $_SESSION['card_token']
						),
					);

					$charge = wp_remote_post( $this->api_url, $args );
					
					$charge = json_decode( $charge['body'], true );

					$charge = $charge['response'];

					if ( $charge['success'] == true ) {
							//setup our payment details
							$payment_info = array();
							$payment_info['gateway_public_name'] = $this->public_name;
							$payment_info['gateway_private_name'] = $this->admin_name;
							$payment_info['method'] = sprintf(__('%1$s Card %2$s', 'mp'), ucfirst($charge['card']['scheme']), $charge['card']['display_number']);
							$payment_info['transaction_id'] = $charge['token'];
							$timestamp = time();
							$payment_info['status'][$timestamp] = __('Paid', 'mp');
							$payment_info['total'] = $total;
							$payment_info['currency'] = $this->currency;

							$order = mp()->create_order($order_id, $cart, $_SESSION['mp_shipping_info'], $payment_info, true);

							unset($_SESSION['card_token']);
							mp()->set_cart_cookie(Array());
					} else {
							unset($_SESSION['card_token']);
							mp()->cart_checkout_error(sprintf(__('There was an error processing your card. Please <a href="%s">go back and try again</a>.', 'mp'), mp_checkout_step_url('checkout')));
							return false;
					}
			} catch (Exception $e) {
					unset($_SESSION['card_token']);
					mp()->cart_checkout_error(sprintf(__('There was an error processing your card: "%s". Please <a href="%s">go back and try again</a>.', 'mp'), $e->getMessage(), mp_checkout_step_url('checkout')));
					return false;
			}
		}
	}
}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_PIN', 'pin', __('PIN (beta)', 'mp'));