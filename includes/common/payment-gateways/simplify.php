<?php

class MP_Gateway_Simplify extends MP_Gateway_API {
	//build
	var $build = 2;
	
	var $plugin_name = 'simplify';
	var $admin_name = '';
	var $public_name = '';
	var $method_img_url = '';
	var $method_button_img_url = '';
	var $force_ssl;
	var $ipn_url;
	var $skip_form = false;
	var $public_key, $private_key, $currency;

	function on_creation() {
		$this->admin_name = __( 'Simplify', 'mp' );
		$this->public_name = __( 'Credit Card', 'mp' );
		$this->public_key = $this->get_setting( 'api_credentials->public_key' );
		$this->private_key = $this->get_setting( 'api_credentials->private_key' );
		$this->force_ssl = $this->get_setting( 'is_ssl' );
		$this->currency = $this->get_setting( 'currency', 'USD' );
		
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue necessary scripts for checkout
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_enqueue_scripts
	 */
	function enqueue_scripts() {
		if ( ! mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_enqueue_script( 'js-simplify', 'https://www.simplify.com/commerce/v1/simplify.js', array( 'jquery' ), null );
		wp_enqueue_script( 'simplify-token', mp_plugin_url( 'includes/common/payment-gateways/simplify-files/simplify_token.js' ), array( 'js-simplify' ), MP_VERSION );
		wp_localize_script( 'simplify-token', 'simplify', array(
			'publicKey' => $this->public_key,
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
			'desc' => __('Simplify helps merchants to accept online payments from Mastercard, Visa, American Express, Discover, JCB, and Diners Club cards. It\'s that simple. We offer a merchant account and payment gateway in a single, secure package so you can concentrate on what really matters to your business. Only supports USD currently.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => $this->get_field_name('api_credentials'),
			'label' => array('text' => __('API Credentials', 'mp')),
			'desc' => __('Login to Simplify to <a target="_blank" href="https://www.simplify.com/commerce/app#/account/apiKeys">get your API credentials</a>. Enter your test credentials, then live ones when ready.', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'public_key',
				'label' => array('text' => __('Public Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'private_key',
				'label' => array('text' => __('Private Key', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('is_ssl'),
			'label' => array('text' => __('Force SSL?', 'mp')),
			'desc' => __('When in live mode, although it is not required, Simplify recommends you have an SSL certificate.', 'mp'),
		));
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
		 	mp_push_to_array($settings, 'gateways->simplify->api_credentials->private_key', $val);
		 	unset($settings['gateways']['simplify']['private_key']);	
		}
		
		if ( $val = $this->get_setting('publishable_key') ) {
		 	mp_push_to_array($settings, 'gateways->simplify->api_credentials->public_key', $val);
		 	unset($settings['gateways']['simplify']['public_key']);	
		}
		
		return $settings;
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
	 * Use this to do the final payment. Create the order then process the payment. If
	 * you know the payment is successful right away go ahead and change the order status
	 * as well.
	 *
	 * @param MP_Cart $cart. Contains the MP_Cart object.
	 * @param array $billing_info. Contains billing info and email in case you need it.
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function process_payment( $cart, $billing_info, $shipping_info ) {
		if ( mp_get_post_value( 'card_token' ) ) {
			mp_checkout()->add_error( __( 'The Simplify Token was not generated correctly. Please go back and try again.', 'mp' ), 'order-review-payment' );
			return false;
		}

		if ( ! class_exists( 'Simplify' ) ) {
			require_once mp_plugin_dir( 'includes/common/payment-gateways/simplify-files/lib/Simplify.php' );
		}
		
		Simplify::$publicKey = $this->public_key;
		Simplify::$privateKey = $this->private_key;

		$total = $cart->total( false );
		$order = new MP_Order();
		$order_id = $order->get_id();

		try {
			$token = mp_get_post_value( 'simplify_token' );
			$charge = Simplify_Payment::createPayment( array(
				'amount' => ($total * 100),
				'token' => $token,
				'description' => sprintf( __( '%s Store Purchase - Order ID: %s, Email: %s', 'mp'), get_bloginfo( 'name' ), $order_id, mp_arr_get_value( 'email', $shipping_info ) ),
				'currency' => $this->currency
			));

			if ( $charge->paymentStatus == 'APPROVED' ) {
				$payment_info = array();
				$payment_info['gateway_public_name'] = $this->public_name;
				$payment_info['gateway_private_name'] = $this->admin_name;
				$payment_info['method'] = sprintf( __( '%1$s Card ending in %2$s - Expires %3$s', 'mp' ), $charge->card->type, $charge->card->last4, $charge->card->expMonth . '/' . $charge->card->expYear );
				$payment_info['transaction_id'] = $charge->id;
				$payment_info['status'][ time() ] = __( 'Paid', 'mp' );
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
			mp_checkout()->add_error( sprintf( __( 'There was an error processing your card: <strong>%s</strong>. Please re-enter your credit card info and try again</a>.', 'mp'), $e->getMessage() ), 'order-review-payment' );
			return false;
		}
	}
}

mp_register_gateway_plugin('MP_Gateway_Simplify', 'simplify', __('Simplify Commerce by MasterCard', 'mp'));
