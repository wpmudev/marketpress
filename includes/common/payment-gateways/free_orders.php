<?php

/*
  MarketPress FREE Orders Gateway Plugin
  Author: Marko Miljus (Incsub)
 */

class MP_Gateway_FREE_Orders extends MP_Gateway_API {

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name				 = 'free_orders';
	//name of your gateway, for the admin side.
	var $admin_name				 = '';
	//public name of your gateway, for lists and such.
	var $public_name				 = 'Free Order';
	//url for an image for your checkout method. Displayed on method form
	var $method_img_url			 = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url	 = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl				 = false;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form				 = false;

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name	 = __( 'Free Orders', 'mp' );
		$public_name		 = $this->get_setting( 'name', __( 'Free Order', 'mp' ) );
		$this->public_name	 = empty( $public_name ) ? __( 'Free Order', 'mp' ) : $public_name;

		add_filter( 'WPMUDEV_Field_Checkbox_Group_Arguments_free_orders', array( &$this, 'WPMUDEV_Field_Checkbox_Group_Arguments_free_orders' ), 10, 1 );
		add_filter( 'WPMUDEV_Field_Checkbox_checked', array( &$this, 'WPMUDEV_Field_Checkbox_Checked_free_orders' ), 10, 2 );

		add_filter( 'mp_order/notification_body/free_orders', array( &$this, 'order_confirmation_email' ), 10, 2 );
		add_filter( 'mp_order/confirmation_text/' . $this->plugin_name, array( &$this, 'order_confirmation_text' ), 10, 2 );
	}

	public function WPMUDEV_Field_Checkbox_Group_Arguments_free_orders( $arguments ) {
		$arguments[ 'disabled' ] = 'disabled';
		return $arguments;
	}

	public function WPMUDEV_Field_Checkbox_Checked_free_orders( $checked, $name ) {
		if ( is_admin() && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'store-settings-payments' && $name == 'gateways[allowed][' . $this->plugin_name . ']' ) {
			$checked = 'checked';
		}
		return $checked;
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
		return do_shortcode( $this->get_setting( 'instruction' ) );
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
		$payment_info = array();

		$order = new MP_Order();

		$timestamp = time();

		$payment_info = array(
			'gateway_public_name'	 => $this->public_name,
			'gateway_private_name'	 => $this->admin_name,
			'gateway_plugin_name'	 => $this->plugin_name,
			'method'				 => __( 'Free Order', 'mp' ),
			'status'				 => array(
				$timestamp => __( 'Paid', 'mp' ),
			),
			'total'					 => $cart->total(),
			'currency'				 => mp_get_setting( 'currency' ),
		);

		$order->save( array(
			'cart'			 => $cart,
			'payment_info'	 => $payment_info,
			'billing_info'	 => $billing_info,
			'shipping_info'	 => $shipping_info,
			'paid'			 => true
		) );


		wp_redirect( $order->tracking_url( false ) );
		exit;
	}

	/**
	 * Filter the order confirmation email text
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_order/notification_body/free_orders
	 */
	function order_confirmation_email( $msg, $order ) {
		if ( $email_text = $this->get_setting( 'email' ) ) {
			$msg = mp_filter_email( $order, $email_text );
		}

		return $msg;
	}

	/**
	 * Filter the order confirmation text that shows up on the order status page
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_order/confirmation_text/free_orders
	 */
	function order_confirmation_text( $content, $order ) {
		return $content . wpautop( str_replace( 'TOTAL', mp_format_currency( $order->get_meta( 'mp_payment_info->currency' ), $order->get_meta( 'mp_payment_info->total' ) ), $this->get_setting( 'confirmation' ) ) );
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
			'desc'			 => __( "This gateway is automatically activated if order totalling 0 and it can't be disabled", 'mp' ),
		/* 'conditional'	 => array(
		  'name'	 => 'gateways[allowed][' . $this->plugin_name . ']',
		  'value'	 => 1,
		  'action' => 'show',
		  ), */
		) );

		$metabox->add_field( 'checkbox', array(
			'name'			 => $this->get_field_name( 'automatic_payment_status_paid' ),
			'label'			 => array( 'text' => __( 'Payment Status', 'mp' ) ),
			'message'		 => __( 'Yes', 'mp' ),
			'value'			 => 'yes',
			'default_value'	 => 'yes',
			'desc'			 => __( 'If enabled, all free orders will be automatically marked as paid.', 'mp' ),
		) );

		$metabox->add_field( 'text', array(
			'name'			 => $this->get_field_name( 'name' ),
			'default_value'	 => $this->public_name,
			'label'			 => array( 'text' => __( 'Method Name', 'mp' ) ),
			'desc'			 => __( 'Enter a public name for this payment method that is displayed to users - No HTML', 'mp' ),
			'save_callback'	 => array( 'strip_tags' ),
		) );

		$metabox->add_field( 'wysiwyg', array(
			'name'	 => $this->get_field_name( 'instruction' ),
			'label'	 => array( 'text' => __( 'Payment Information', 'mp' ) ),
			'desc'	 => __( 'This is the information to display on the payments screen.', 'mp' ),
		) );

		$metabox->add_field( 'wysiwyg', array(
			'name'	 => $this->get_field_name( 'confirmation' ),
			'label'	 => array( 'text' => __( 'Confirmation User Information', 'mp' ) ),
			'desc'	 => __( 'This is the free order information to display on the order confirmation screen. TOTAL will be replaced with the order total.', 'mp' ),
		) );

		$metabox->add_field( 'textarea', array(
			'name'			 => $this->get_field_name( 'email' ),
			'label'			 => array( 'text' => __( 'Order Confirmation Email', 'mp' ) ),
			'desc'			 => __( 'This is the email text to send to those who have made free order checkouts. You should include your free order instructions / information here. It overrides the default order checkout email. These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. No HTML allowed.', 'mp' ),
			'custom'		 => array( 'rows' => 10 ),
			'save_callback'	 => array( 'strip_tags' ),
		) );
	}

}

mp_register_gateway_plugin( 'MP_Gateway_FREE_Orders', 'free_orders', __( 'Free Orders', 'mp' ) );
