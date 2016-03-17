<?php

/**
 * MarketPress Bitpay Gateway
 *
 * @since 3.0
 * @package MarketPress
 * @subpackage Bitway Gateway
 */

class MP_Gateway_Bitpay extends MP_Gateway_API {
	//the current build version
	var $build = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'bitpay';
	//name of your gateway, for the admin side.
	var $admin_name = 'Bitpay';
	//public name of your gateway, for lists and such.
	var $public_name = 'Bitpay';
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form = true;
	//api vars
	var $private_key = '';
	//api url
	var $api_url = '';

  var $currency = '';
	//Transaction Speed for bitpay
	var $transactionSpeed = '';
	//Notification option for bitpay
	var $fullNotifications = '';
  //if the gateway uses the order confirmation step during checkout (e.g. PayPal)
  var $use_confirmation_step = true;

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		$this->private_key = $this->get_setting('private_key');
		$this->transactionSpeed = $this->get_setting('transactionSpeed');
		$this->fullNotifications = $this->get_setting('fullNotifications');
		$this->api_url = $this->get_setting('testMode') ? 'https://test.bitpay.com' : 'https://www.bitpay.com';
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
		if ( ! $this->get_setting('transactionSpeed') ) {
			mp_push_to_array($settings, 'gateways->bitpay->transactionSpeed', 'high');
		}

		if ( ! $this->get_setting('redirectMessage') ) {
			mp_push_to_array($settings, 'gateways->bitpay->redirectMessage', __('You will be redirected to <a href="http://bitpay.com" title="">bitpay.com</a>, for bitcoin payment. It is completely safe.', 'mp'));
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
		$total = $cart->total( false );
		$currency = mp_get_setting( 'currency' );
		$redirect = $this->return_url;
		$order = new MP_Order();
		$order_id = $order->get_id();
		$notificationURL = $this->ipn_url;
		$fullNotifications = ( 'yes' == $this->fullNotifications );

		//Create Invoice and redirect to bitpay
		$options = array(
			'apiKey' => $this->private_key,
			'transactionSpeed' => $this->transactionSpeed,
			'currency' => $currency,
			'redirectURL' => $redirect,
			'notificationURL' => $notificationURL,
			'posData' => $order_id,
			'fullNotifications' => $fullNotifications,
			'buyerName' => substr( $shipping_info['name'], 0, 100 ),
			'buyerAddress1' => substr( $shipping_info['address1'], 0, 100 ),
			'buyerAddress2' => substr( $shipping_info['address2'], 0, 100 ),
			'buyerCity' => substr( $shipping_info['city'], 0, 100 ),
			'buyerState' => substr( $shipping_info['state'], 0, 100 ),
			'buyerZip' => substr( $shipping_info['zip'], 0, 100 ),
			'buyerCountry' => substr( $shipping_info['country'], 0, 100 ),
			'buyerPhone' => substr( $shipping_info['phone'], 0, 100 ),
			'buyerEmail' => substr( $shipping_info['email'], 0, 100 ),
		);

		$invoice = $this->bitpay_create_invoice( $order_id, $total, $order_id, $options );

		//Invoice response from bitpay
		$bitpay_invoice_error = $bitpay_error_messages = '';

		if ( isset( $invoice->error ) ) {
			$bitpay_invoice_error = isset( $invoice->error->message ) ? $invoice->error->message : '';
			if ( ! empty( $invoice->error->messages ) ) {
				foreach ( $invoice->error->messages as $error_field => $error_message ) {
					$bitpay_error_messages .= '<li>' . $error_field . ' => ' . $error_message . '</li>';
				}
			}

			mp_checkout()->add_error( $bitpay_error_messages, 'order-review-payment' );
		} else {
			//Invoice obtained
			mp_update_session_value( 'mp_order', $order->get_id() );
			mp_update_session_value( 'bitpay_invoice_id', $invoice->id );
			wp_redirect( $invoice->url );
			exit;
		}
	}

  /**
   * Process order confirmation before page loads (e.g. verify callback data, etc)
   *
   * @since 3.0
   * @access public
   * @action mp_checkout/confirm_order/{plugin_name}
   */
  public function process_confirm_order() {
		$private_key = $this->get_setting( 'private_key' );
		$bitpayInvoiceId = mp_get_session_value( 'bitpay_invoice_id' );

		//if no invoice id, display error
		if ( ! $bitpayInvoiceId ) {
			mp_checkout()->add_error( __( 'We could not verify order invoice details, please try again or contact site administrator for help', 'mp' ), 'order-review-payment' );
			return;
		}

		//get Invoice status
		$invoice = $this->bitpay_get_invoice( $bitpayInvoiceId, $private_key );

		//Check order Id for obtained Invoice
		if ( ! isset( $invoice->posData ) || mp_get_session_value ('mp_order' ) != $invoice->posData ) {
			mp_checkout()->add_error( __( 'Incorrect order invoice, please contact site administrator', 'mp' ), 'order-review-payment' );
			return;
		}

		//Check invoice status
		$status = array(
			'paid',
			'confirmed',
			'complete'
		);

		if ( in_array( $invoice->status, $status ) ) {
			$timestamp = time();

      //setup our payment details
      $payment_info = array();
			$payment_info['gateway_public_name'] = $this->public_name;
			$payment_info['gateway_private_name'] = $this->admin_name;
			$payment_info['method'] = $this->get_setting('admin_name');
			$payment_info['transaction_id'] = $invoice->id;
			$payment_info['total'] = $invoice->price;
			$payment_info['total_btc'] = $invoice->btcPrice;
			$payment_info['currency'] = $invoice->currency;

			if ( $invoice->status == 'complete' ) {
				$payment_info['status'][ $timestamp ] = sprintf( __( '%s - The payment request has been processed - %s', 'mp' ), $invoice->status, $invoice->status );
			} else {
				$payment_info['status'][ $timestamp ] = sprintf( __( '%s - The payment request is under process. Bitpay invoice status - %s', 'mp' ), 'pending', $invoice->status );
			}

			$order = new MP_Order( mp_get_session_value ('mp_order' ) );
			$order->save( array(
				'paid' => true,
				'payment_info' => $payment_info,
				'cart' => mp_cart(),
			) );

			wp_redirect( $order->tracking_url( false ) );
			exit;
		} else {

			switch ( $invoice->status ) {
				case 'new' :
					$message = 'Payment not recieved at BitPay.';
					break;
				case 'invalid':
					$message = 'Payment not processed, a refund has been initiated.';
					break;
				case 'expired' :
					$message = 'Invoice expired, please reorder.';
					break;
				default :
					$message = 'There was an error processing payment at ' . $bitpay->public_name . ', please reorder.';
					break;
			}

			mp_checkout()->add_error( $message, 'order-review-payment' );
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
			'desc' => __('You can now accept a payment from any country on Earth, with no risk of fraud. To use Bitpay, you need to signup on <a href="https://bitpay.com/start" title="Bitpay signup">Bitpay</a>. \n After completing the signup process, you can get api keys at <a href="https://bitpay.com/api-keys" title="API keys">Bitpay API key</a>. You can read more about Bitpay at <a href="https://bitpay.com/downloads/bitpayApi.pdf" title="Bitpay documentation">Bitpay API</a>. \n <strong>Bitpay requires SSL(https) for payment notifications to work.</strong>', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('private_key'),
			'label' => array('text' => __('API Key', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('transactionSpeed'),
			'label' => array('text' => __('Transaction Speed', 'mp')),
			'default_value' => 'high',
			'desc' => __('Speed at which the bitcoin transaction registers as "confirmed" to the store. This overrides your merchant settings on the Bitpay website.', 'mp'),
			'options' => array(
				'high' => __('High', 'mp'),
				'medium' => __('Medium', 'mp'),
				'low' => __('Low', 'mp'),
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('fullNotifications'),
			'label' => array('text' => __('Full Notifications', 'mp')),
			'message' => __('Yes', 'mp'),
			'value' => 'yes',
			'desc' => __('If enabled, you will recieve an email for each status update on payment.', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('debugging'),
			'label' => array('text' => __('Enable Debug Log', 'mp')),
			'message' => __('Yes', 'mp'),
			'value' => 'yes',
			'desc' => __('If checked, response fron bitpay will be stored in log file, keep it disabled unless required.', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('testMode'),
			'label' => array('text' => __('Use Test Mode?', 'mp')),
			'message' => __('Yes', 'mp'),
			'value' => 'yes',
			'desc' => __('If enabled, all API calls will be made to <a href="https://test.bitpay.com">https://test.bitpay.com</a>.', 'mp'),
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
	function _process_payment( $cart, $billing_info, $shipping_info ) {
		$private_key = $this->get_setting( 'private_key' );

		//Bitpay Invoice id
		$bitpayInvoiceId = isset( $_SESSION['bitpayInvoiceId'] ) ? $_SESSION['bitpayInvoiceId'] : '';
		if ( !$bitpayInvoiceId ) {
			return;
		}

		//get Invoice status
		$invoice = $this->bitpay_get_invoice( $bitpayInvoiceId, $private_key );

		//Check order Id for obtained Invoice
		if ( $_SESSION['mp_order'] != $invoice->posData ) {
			mp_checkout()->add_error( __( 'Incorrect order invoice, please contact site administrator', 'mp' ) );
			wp_redirect( mp_checkout_step_url( 'confirm-checkout' ) );
			exit;
		}

		//If order status new, redirect user to bitpay
		if ( $invoice->status == 'new' ) {
			wp_redirect( $invoice->url );
			exit;
		}
	}

	/**
   * Use to handle any payment returns to the ipn_url. Do not display anything here. If you encounter errors
   * return the proper headers. Exits after.
   */
	function process_ipn_return() {
		if ( ! mp_get_setting( 'gateways->bitpay' ) ) {
			//Just to keep a note
			$this->bitpay_log( 'Untracked Order, due to gateway inactivation' );
			exit;
		}

		$private_key = $this->get_setting( 'private_key' );
		$response = $this->bitpay_verify_notification( $private_key );

		if ( isset( $response['error'] ) ) {
			$this->bitpay_log( $response );
		} else {
			$orderId = $response['posData'];
			$this->update_bitpay_payment_status( $orderId, $response['status'] );
		}
	}

	/**
	 * Send POST request to bitpay.com api
	 * @global type $mp
	 * @param type $url
	 * @param type $apiKey
	 * @param type $post
	 * @return type Invoice
	 */
	function bitpay_request_url( $url, $apiKey, $post = false ) {
		$post = $post ? json_encode( $post ) : '';
		$params = array(
			'body' => $post,
			'sslverify' => false,
			'timeout' => mp_get_api_timeout( $this->plugin_name ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $apiKey )
			)
		);
		$response = wp_remote_post( $url, $params );

		//If Debug Log enabled
		if ( $this->get_setting( 'debugging' ) ) {
			$this->bitpay_log( $response );
		}

		return $response;
	}

	/**
	 * Create Invoice using Order details at bitpay.com
	 *
	 * @param type  $orderId , Unique order id generated for each order by marketpress.
	 * @param type  $price   , Cart Total, including shipping cost (if any), sent in default currency set by site administrator.
	 * @param type  $posData Contains order id to match invoice against order id while confirming the order or
	 *                       updating invoice status for order
	 * @param type  $options , ('itemDesc', 'itemCode', 'notificationEmail', 'notificationURL', 'redirectURL', 'apiKey'
	 *                       'currency', 'physical', 'fullNotifications', 'transactionSpeed', 'buyerName',
	 *                       'buyerAddress1', 'buyerAddress2', 'buyerCity', 'buyerState', 'buyerZip', 'buyerEmail', 'buyerPhone')
	 *                       If a given option is not provided here, the value of that option will default to what is found in bp_options.php
	 *
	 * @return $response, invoice body recieved
	 */
	function bitpay_create_invoice( $orderId, $price, $posData, $options = array() ) {
		$pos = array( 'posData' => $posData );
		$pos['hash'] = crypt( serialize( $posData ), $options['apiKey'] );

		$options['posData'] = json_encode( $pos );
		$options['orderID'] = $orderId;
		$options['price']   = $price;

		$postOptions = array(
			'orderID',
			'itemDesc',
			'itemCode',
			'notificationEmail',
			'notificationURL',
			'redirectURL',
			'posData',
			'price',
			'currency',
			'physical',
			'fullNotifications',
			'transactionSpeed',
			'buyerName',
			'buyerAddress1',
			'buyerAddress2',
			'buyerCity',
			'buyerState',
			'buyerZip',
			'buyerEmail',
			'buyerPhone',
		);

		foreach ( $postOptions as $o ) {
			if ( array_key_exists( $o, $options ) ) {
				$post[ $o ] = $options[ $o ];
			}
		}

		$response = $this->bitpay_request_url( $this->api_url . '/api/invoice/', $options['apiKey'], $post );
		$response = json_decode( $response['body'] );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => array(
					'message' => 'Connection error'
			) );
		}

		return $response;
	}

	/**
	 * Verify the recieved invoice against the hash recieved in posData
	 * @param type $apiKey
	 * @return $json, Invoice body
	 */
	function bitpay_verify_notification( $apiKey = false ) {
		if ( ! $apiKey ) {
			return;
		}

		$post = file_get_contents( "php://input" );
		if ( ! $post ) {
			return array( 'error' => 'No post data' );
		}

		$json = json_decode( $post, true );
		if ( is_string( $json ) ) {
			return array( 'error' => $json );
		} // error

		if ( ! array_key_exists( 'posData', $json ) ) {
			return array( 'error' => 'no posData' );
		}

		// decode posData
		$posData = json_decode( $json['posData'], true );
		if ( $posData[ 'hash' ] != crypt( serialize( $posData[ 'posData' ] ), $apiKey ) ) {
			return array( 'error' => 'authentication failed (bad hash)' );
		}
		$json['posData'] = $posData['posData'];

		return $json;
	}

	/*
	 * Get bitpay invoice using GET method
	 * @param $invoiceid, obtained from bitpay_create_invoice
	 * @param bitpay api key, default false
	 *
	 */
	function bitpay_get_invoice( $invoiceId, $apiKey = false ) {
		if ( ! $apiKey ) {
			return false;
		}

		$params = array(
			'body'       => '',
			'method'     => 'GET',
			'sslverify'  => false,
			'timeout'    => mp_get_api_timeout( $this->plugin_name ),
			'headers'    => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $apiKey )
			)
		);

		$invoice = wp_remote_get( $this->api_url . '/api/invoice/' . $invoiceId, $params );
		$body = '';
		if ( $invoice['response']['code'] != 400 ){
			//decode posData
			$body = json_decode( $invoice['body'] );
			$body->posData = json_decode( $body->posData, true );
			$body->posData = $body->posData['posData'];
		}
		return $body;
	}

	/**
	 * Debug Log for Bitpay Invoices
	 *
	 * @param type $contents
	 */
	function bitpay_log( $contents ) {
		$file = plugin_dir_path( __FILE__ ) . 'bplog.txt';
		@file_put_contents( $file, date( 'm-d H:i:s' ) . ": ", FILE_APPEND );
		if ( is_array( $contents ) ) {
			@file_put_contents( $file, var_export( $contents, true ) . "\n", FILE_APPEND );
		} else {
			if ( is_object( $contents ) ) {
				@file_put_contents( $file, json_encode( $contents ) . "\n", FILE_APPEND );
			} else {
				@file_put_contents( $file, $contents . "\n", FILE_APPEND );
			}
		}
	}

	/**
	* Updates Payment Status as per Invoice status
	* @global type $mp
	* @param type $orderId
	* @param type $invoice_status
	*/
	function update_bitpay_payment_status( $orderId, $invoice_status ) {
		$order = new MP_Order( $orderId );

		switch ( $invoice_status ) {
			case 'paid':
				$status = sprintf( __( '%s - The payment request is under process. Bitpay invoice status - %s', 'mp' ), 'pending', $invoice_status );
				$order->log_ipn_status( $status );
				break;

			case 'confirmed':
				$status = sprintf( __( '%s - The payment request is under process. Bitpay invoice status - %s', 'mp' ), 'pending', $invoice_status );
				$order->log_ipn_status( $status );
				break;

			case 'complete':
				$status = sprintf( __( '%s - The payment request has been processed - %s', 'mp' ), $invoice_status, $invoice_status );
				$order->log_ipn_status( $status );

				if ( $order->post_status != 'order_paid' ) {
					$order->change_status( 'paid' );
				}
			break;

			case 'invalid':
				$status = sprintf( __( '%s - The payment not credited in merchants bitpay account, action required. Bitpay invoice status  - %s', 'mp' ), 'error', $invoice_status );
				$order->log_ipn_status( $status );
			break;

			case 'expired':
				$status = sprintf( __( '%s - The payment request expired, - %s', 'mp' ), 'cancelled', $invoice_status );
				$order->log_ipn_status( $status );
				$order->change_status( 'closed' );
			break;
		}
	}
}

//register payment gateway plugin
//mp_register_gateway_plugin( 'MP_Gateway_Bitpay', 'bitpay', __( 'Bitpay', 'mp' ) );
