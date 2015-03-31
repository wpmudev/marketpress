<?php

/*
  MarketPress Mijireh Gateway Plugin
  Author: Marko Miljus (Incsub)
 */

class MP_Gateway_Mijireh extends MP_Gateway_API {

	//the current build version
	var $build					 = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'mijireh';
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
	var $currencies				 = array();

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {

		//set names here to be able to translate
		$this->admin_name	 = __( 'Mijireh', 'mp' );
		$this->public_name	 = __( 'Mijireh', 'mp' );

		$this->access_key = $this->get_setting( 'api_credentials->access_key', '' );

		add_filter( 'mp_checkout/address_fields_array', array( &$this, 'address_fields_array' ), 10, 2 );
	}

	public function init_mijireh() {
		if ( !class_exists( 'Mijireh' ) ) {
			require_once 'mijireh/Mijireh.php';
			Mijireh::$access_key = $this->access_key;
		}
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
					$field[ 'validation' ] = array(
						'required' => true,
					);
				}
			}
		}

		return $fields;
	}

	/**
	 * Return fields you need to add to the top of the payment screen, like your credit card info fields
	 *
	 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info. Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		if ( isset( $_GET[ 'cancel' ] ) ) {
			echo '<div class="mp_checkout_error">' . __( 'Your Mijireh transaction has been canceled.', 'mp' ) . '</div>';
		}
		return __( 'You will be redirected to the Mijireh.com site to finalize your payment.', 'mp' );
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

		$timestamp	 = time();
		$order		 = new MP_Order();

		$this->init_mijireh();
		//$address	 = new Mijireh_Address();
		$mj_order = new Mijireh_Order();

		// set shipping address
		foreach ( $shipping_info as $k => $v ) {
			switch ( $k ) {
				case 'first_name' :
					$address->first_name = $v;
					break;

				case 'last_name' :
					$address->last_name = $v;
					break;

				case 'address1' :
					$address->street = $v;
					break;

				case 'address2' :
					$address->street = $v;
					break;

				case 'city' :
					$address->city = $v;
					break;

				case 'state' :
					$address->state_province = $v;
					break;

				case 'zip' :
					$address->zip_code = $v;
					break;

				case 'country' :
					$address->country = $v;
					break;

				case 'phone' :
					$address->phone = $v;
					break;

				case 'company' :
					$address->company = $v;
					break;

				case 'email' :
					$address->email = $v;
					break;
			}
		}

		// set billing info
		foreach ( $billing_info as $k => $v ) {
			switch ( $k ) {
				case 'first_name' :
					$mj_order->first_name = $v;
					break;

				case 'last_name' :
					$mj_order->last_name = $v;
					break;

				case 'email' :
					$mj_order->email = $v;
					break;
			}
		}

		$total	 = 0;
		$items	 = $cart->get_items_as_objects();

		foreach ( $items as $item ) {
			$price = $item->get_price( 'lowest' );
			$total += ($price * $item->qty);
			$mj_order->add_item( $item->title( false ), $price, $item->qty, $item->get_meta( 'sku', $item->ID ) );
		}

		$shipping_price = 0;

		//ship line
		if ( ($shipping_price = $cart->shipping_total( false )) !== false ) {
			if ( is_numeric( $shipping_price ) ) {
				$mj_order->shipping = $shipping_price;
			} else {
				$mj_order->shipping = 0;
			}
		}

		//tax line
		if ( !mp_get_setting( 'tax->tax_inclusive' ) ) {
			$mj_order->show_tax = true;

			$tax_price = $cart->tax_total( false );
			if ( is_numeric( $tax_price ) ) {
				$mj_order->tax = ($mj_order->tax + $tax_price);
			}
		} else {
			$mj_order->show_tax = false;
		}

		$mj_meta_data = array( 'order_id' => $order->get_id() );

		$mj_order->total		 = $total + $mj_order->tax + $mj_order->shipping;
		$mj_order->return_url	 = $this->return_url;
		$mj_order->partner_id	 = '';
		$mj_order->meta_data	 = $mj_meta_data;

		try {
			$mj_order->create();
			wp_redirect( $mj_order->checkout_url );
			exit;
		} catch ( Mijireh_Exception $e ) {
			echo $e->getMessage();
			exit;
		}
	}

	/**
	 * Runs before page load incase you need to run any scripts before loading the success message page
	 */
	function process_confirm_order() {
		if ( isset( $_GET[ 'order_number' ] ) ) {

			$order_num = $_GET[ 'order_number' ];

			$this->init_mijireh();

			try {
				$mj_order = new Mijireh_Order( esc_attr( $_GET[ 'order_number' ] ) );

				$payment_status = $mj_order->status;

				if ( $payment_status == 'paid' ) {

					$status = __( 'The order has been received', 'mp' );

					$payment_info = array(
						'gateway_public_name'	 => $this->public_name,
						'gateway_private_name'	 => $this->admin_name,
						'status'				 => array(
							$timestamp => __( 'Paid', 'mp' ),
						),
						'total'					 => $mj_order->total,
						'currency'				 => mp_get_setting( 'currency', 'USD' ),
						'transaction_id'		 => $order_num,
						'method'				 => $this->admin_name
					);

					$order = new MP_Order( $mp_order_num );
					$order->save( array(
						'cart'			 => mp_cart(),
						'payment_info'	 => $payment_info,
						'paid'			 => true,
					) );

					wp_redirect( $order->tracking_url( false ) );
				} else {
					//not paid, waiting for the payment status
				}
				exit;
			} catch ( Mijireh_Exception $e ) {
				mp_checkout()->add_error( '<li>' . __( 'Mijireh Error: ', 'mp' ) . $e->getMessage() . '</li>', 'order-review-payment' );
				return false;
			}

			die;
		}
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
			'desc'			 => __( 'Mijireh Checkout provides a fully PCI Compliant, secure way to collect and transmit credit card data to your payment gateway while keeping you in control of the design of your site.', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'	 => 1,
				'action' => 'show',
			),
		) );

		$creds = $metabox->add_field( 'complex', array(
			'name'	 => 'gateways[' . $this->plugin_name . '][api_credentials]',
			'label'	 => array( 'text' => __( 'Credentials', 'mp' ) ),
			'desc'	 => __( 'You must login to the Mijireh.com dashboard to obtain the Access Key.', 'mp' ),
		) );

		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field( 'text', array(
				'name'		 => 'access_key',
				'label'		 => array( 'text' => __( 'Access Key', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}
	}

	/**
	 * INS and payment return
	 */
	function process_ipn_return() {
		
	}

}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_Mijireh', 'mijireh', __( 'Mijireh', 'mp' ) );
