<?php

/*
  MarketPress Mollie Gateway Plugin
  Author: Marko Miljus (Incsub)
 */

class MP_Gateway_Mollie extends MP_Gateway_API {

	//the current build version
	var $build					 = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'mollie';
	//name of your gateway, for the admin side.
	var $admin_name				 = '';
	//public name of your gateway, for lists and such.
	var $public_name				 = '';
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url			 = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url	 = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl				 = false;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form				 = true;
	//credit card vars
	var $API_Username, $API_Password, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale;
	//if the gateway uses the order confirmation step during checkout (e.g. PayPal)
	var $use_confirmation_step	 = true;

	/**
	 * Refers to the gateways currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array();

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->currencies = array(
			'EUR' => __( 'EUR - Euro', 'mp' )
		);

		$this->admin_name			 = __( 'Mollie', 'mp' );
		$this->public_name			 = __( 'Mollie', 'mp' );
		$this->method_img_url		 = '';
		$this->method_button_img_url = '';
		$this->currencyCode			 = $this->get_setting( 'currency' );
		$this->API_Key				 = $this->get_setting( 'api_key' );
	}

	/**
	 * Init settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => $this->generate_metabox_id(),
			'page_slugs'	 => array( 'store-settings-payments', 'store-settings_page_store-settings-payments' ),
			'title'			 => sprintf( __( '%s Settings', 'mp' ), $this->admin_name ),
			'option_name'	 => 'mp_settings',
			'desc'			 => sprintf( __( '%sMollie%s provides a fully PCI Compliant and secure way to collect payments via iDeal, Credit Card, Bancontact / Mister Cash, SOFORT Banking, Overbooking, Bitcoin, PayPal, paysafecard and AcceptEmail.', 'mp' ), '<a href="https://www.mollie.com/">', '</a>' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'text', array(
			'name'		 => 'gateways[' . $this->plugin_name . '][api_key]',
			'label'		 => array( 'text' => __( 'API Key', 'mp' ) ),
			'validation' => array(
				'required' => true,
			),
		) );

		$metabox->add_field( 'advanced_select', array(
			'name'		 => 'gateways[' . $this->plugin_name . '][currency]',
			'label'		 => array( 'text' => __( 'Currency', 'mp' ) ),
			'multiple'	 => false,
			'options'	 => array_merge( array( '' => __( 'Select One', 'mp' ) ), $this->currencies ),
			'width'		 => 'element',
			'validation' => array(
				'required' => true,
			),
		) );
	}

	function init_mollie() {

		require_once 'mollie-files/Mollie/API/Autoloader.php';

		$this->mollie = new Mollie_API_Client;
		$this->mollie->setApiKey( $this->get_setting( 'api_key' ) );
	}

	/**
	 * Return fields you need to add to the top of the payment screen, like your credit card info fields
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		return __( 'You will be redirected to the Mollie site to finalize your payment.', 'mp' );
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
		$this->init_mollie();
		$timestamp	 = time();
		$params		 = array();
		$order		 = new MP_Order();

		$total = $cart->total( false );

		try {

			$payment = $this->mollie->payments->create( array(
				"amount"		 => $total,
				"description"	 => __( 'Order: #', 'mp' ) . $order->get_id(),
				"redirectUrl"	 => $this->return_url . '?order_id=' . $order->get_id(),
				"metadata"		 => array(
					"order_id" => $order->get_id(),
				),
			) );

			mp_update_session_value( 'transaction_id', $payment->id );
			mp_update_session_value( 'billing_info', $billing_info );
			mp_update_session_value( 'shipping_info', $shipping_info );
			mp_update_session_value( 'cart', $cart );

			wp_redirect( $payment->getPaymentUrl() );
			exit;
		} catch ( Mollie_API_Exception $e ) {
			print_r($e->getMessage());
			exit;
			mp_checkout()->add_error( sprintf( __( 'API error: - "%s"', 'mp' ), htmlspecialchars( $e->getMessage() ), 'payment' ) );
		}
	}

	/**
	 * Runs before page load incase you need to run any scripts before loading the success message page
	 */
	function process_confirm_order() {

		$order_id = mp_get_get_value( 'order_id' );

		if ( !isset( $order_id ) || empty( $order_id ) ) {
			return;
		}

		$order			 = new MP_Order( $order_id );
		$transaction_id	 = mp_get_session_value( 'transaction_id' );

		if ( isset( $transaction_id ) ) {

			$this->init_mollie();

			$payment = $this->mollie->payments->get( $transaction_id );

                        /**
			 * Mollie gateway use some payment methods that can take delays to confirm payment.
			 * We can assume the order as received if payment is paid or is still open
			 */
			if ( $payment->isPaid() == TRUE || $payment->isOpen() == TRUE ) {

				$billing_info	 = mp_get_session_value( 'billing_info' );
				$shipping_info	 = mp_get_session_value( 'shipping_info' );
				$cart	 		 = mp_get_session_value( 'cart' );
                                $timestamp	 = time();

				$payment_info = array(
				'gateway_public_name'	 => $this->public_name,
					'gateway_private_name'	 => $this->admin_name,
					'method'				 => __( 'Mollie', 'mp' ),
					'transaction_id'		 => $payment->id,
					'status'				 => array(
						$timestamp => __( 'The payment request is under process', 'mp' ),
					),
					'total'					 => $cart->total(),
					'currency'				 => $this->currencyCode,
				);

				$order->save( array(
					'cart'			 => $cart,
					'payment_info'	 => $payment_info,
					'billing_info'	 => $billing_info,
					'shipping_info'	 => $shipping_info,
					'paid'			 => false
				) );

				$status = __( 'The order has been received', 'mp' );
				$order->log_ipn_status( $payment->status . ': ' . $status );

				if ( $payment->isPaid() ){
					$order->change_status( 'paid', true );
				}

			} elseif ( $payment->isOpen() == FALSE ) {
				/**
				* The payment isn't paid and isn't open anymore.
				* We can assume it was aborted.
				* We delete the order and redirct the user again to checkout page.
				*/

				wp_redirect( mp_store_page_url( 'checkout', false ) );
			    die;
			}
		}

		wp_redirect( $order->tracking_url( false ) );
		die;
	}

	/**
	 * INS and payment return
	 */
	function process_ipn_return() {

	}

}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_Mollie', 'mollie', __( 'Mollie', 'mp' ) );
