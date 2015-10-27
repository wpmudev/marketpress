<?php
/*
MarketPress iDeal Gateway Plugin
Author: Remi Schouten
*/

class MP_Gateway_IDeal extends MP_Gateway_API {

  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'ideal';
  
  //name of your gateway, for the admin side.
  var $admin_name = '';
  
  //public name of your gateway, for lists and such.
  var $public_name = '';
  
  //url for an image for your checkout method. Displayed on method form
  var $method_img_url = '';

  //url for an submit button image for your checkout method. Displayed on checkout form if set
  var $method_button_img_url = '';
  
  //whether or not ssl is needed for checkout page
  var $force_ssl = false;
  
  //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
  var $ipn_url;
  
	//whether if this is the only enabled gateway it can skip the payment_form step
  var $skip_form = true;

  //if the gateway uses the order confirmation step during checkout (e.g. PayPal)
  var $use_confirmation_step = true;
  
  /****** Below are the public methods you may overwrite via a plugin ******/

	/**
	 * Generate a SHA1 hash based upon the based parameters
	 *
	 * @since 3.0
	 * @access protected
	 * @param string $key
	 * @param string $subID
	 * @param float $total
	 * @param string $purchaseID
	 * @param string $paymentType
	 * @param string $validUntil
	 * @param array $items An array of items.
	 * @return string
	 */
	protected function _get_hash( $key, $subID, $total, $purchaseID, $paymentType, $validUntil, $items ) {
		$shastring = $key . $merchantID . $subID . $total . $purchaseID . $paymentType . $validUntil;
		foreach ( $items as $i => $item ) {
			$idx = ($i + 1);
			$shastring .= $item[ 'itemNumber' . $idx ] . $item[ 'itemDescription' . $idx ] . $item[ 'itemQuantity' . $idx ] . $item[ 'itemPrice' . $idx ];	
		}
		
		// Remove HTML Entities
		$shastring = html_entity_decode( $shastring );

		// Remove space characters: "\t", "\n", "\r" and " "
		$shastring = str_replace( array( "\t", "\n", "\r", ' '), '', $shastring );

		// Generate hash
		$shasign = sha1( $shastring );
		
		return $shasign;	
	}
	 
  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('iDEAL (beta)', 'mp');
		$this->public_name = __('iDEAL', 'mp');

    $this->method_img_url = mp_plugin_url('images/ideal.png');
		$this->method_button_img_url = mp_plugin_url('images/ideal.png');
		$this->merchant_id = $this->get_setting('merchant_id');
		$this->ideal_hash = $this->get_setting('ideal_hash');
		$this->returnURL = mp_checkout_step_url('confirm-checkout');
  	$this->cancelURL = mp_checkout_step_url('checkout') . "?cancel=1";
		$this->errorURL = mp_checkout_step_url('checkout') . "?err=1";
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
	  return __( 'Your payment will be processed by the iDEAL network', 'mp' );
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
		$key = $this->ideal_hash; // Your hashkey or Secret key
		$merchantID = $this->merchant_id; //Your merchant ID
		$order = new MP_Order();
		$subID = '0'; //Almost always 0
		$purchaseID = $order->get_id();
		$paymentType = 'ideal'; //Always ideal
		$validUntil = date( 'Y-m-d\TG:i:s\Z', strtotime( '+1 hour' ) );
		$cart_items = $cart->get_items_as_objects();
		$total = ($cart->total( false ) * 100);
		
		$i = 1;
		foreach ( $cart_items as $item ) {
			$items[] = array(
				'itemNumber' . $i => ( '' == $item->get_meta( 'sku', '' ) ) ? $item->ID : substr( $item->get_meta( 'sku', '' ), 0, 12 ), // Article number
				'itemDescription' . $i => substr( $item->title( false ), 0, 32 ), // Description
				'itemQuantity' . $i => $item->qty, // Quantity
				'itemPrice' . $i => ($item->get_price( 'lowest' ) * 100) // Artikel price in cents
			);
			
			$i++;
		}

		//shipping line
    if ( ($shipping_price = $cart->shipping_total( false )) !== false ) {
			//Add shipping as separate product
			$items[] = array(
				'itemNumber' . $i => '99999998', // Product number
				'itemDescription' . $i => __('Shipping', 'mp'), // Description
				'itemQuantity' . $i => 1, // Quantity
				'itemPrice' . $i => ($shipping_price * 100) // Product price in cents
			);
			$i++;
    }

    if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
			//Add tax as separate product
			$items[] = array(
				'itemNumber' . $i => '99999999', // Product number
				'itemDescription' . $i => __('Tax', 'mp'), // Description
				'itemQuantity' . $i => 1, // Quantity
				'itemPrice' . $i => ($cart->tax_total( false ) * 100),  // Product price in cents
			);
    }

		$hash = $this->_get_hash( $items );

		// Other variables not part of the hash
		$language = ''; # preferred '' for consistent texts, also quicker
		$currency = 'EUR';
		$description = substr( sprintf( __( '%s Store Purchase - Order ID: %s', 'mp' ), get_bloginfo( 'name' ), $order_id ), 0, 32 );
		$test = ( $this->get_setting('mode', 'test') == 'test');
		
		// Setup bank specific urls
		switch ( $this->get_setting('bank') ) {
			case 'ing' :
				$redirectURL = 'https://ideal' . ($test ? 'test' : '') . '.secure-ing.com/ideal/mpiPayInitIng.do?';
			break;
			
			case 'rabo' :
				$redirectURL = 'https://ideal' . ($test ? 'test' : '') . '.rabobank.nl/ideal/mpiPayInitRabo.do?';
			break;
			
			case 'fries' :
				$redirectURL = 'https://' . ($test ? 'test' : '') . 'idealkassa.frieslandbank.nl/ideal/mpiPayInitFriesland.do?';
			break;
				
			case 'abn' :
				$redirectURL = 'https://abnamro' . ($test ? '-test' : '') . '.ideal-payment.de/ideal/mpiPayInitFortis.do?';
			break;
			
			default :
				$redirectURL = 'https://www.ideal-checkout.nl/simulator/?';
			break;
		}
		
		var_dump( $merchantID ); die;
		// Build the request array
		$request = array();
		$request['merchantID'] = $merchantID;
		$request['subID'] = $subID;
		$request['amount'] = $total;
		$request['purchaseID'] = $purchaseID;
		$request['language'] = $language;
		$request['currency'] = $currency;
		$request['description'] = $description;
		$request['hash'] = $shasign;
		$request['paymentType'] = $paymentType;
		$request['validUntil'] = $validUntil;
		
		foreach ( $items as $item ) {
			foreach ( $item as $k => $val ) {
				$request[ $k ] = $val;
			}
		}
	
		$request['urlSuccess'] = $this->return_url;
		$request['urlCancel'] = $this->cancel_url;
		$request['urlError'] = $this->return_url;
		
		wp_redirect( $redirectURL . http_build_query( $request ) );
		exit;
  }
  
  /**
   * Process order confirmation before page loads (e.g. verify callback data, etc)
   *
   * @since 3.0
   * @access public
   * @action mp_checkout/confirm_order/{plugin_name}
   */
  public function process_confirm_order() {
		$timestamp = time();
	  
		$totals = array();
		foreach ($cart as $product_id => $variations) {
			foreach ($variations as $data) {
				$totals[] = mp()->before_tax_price($data['price'], $product_id) * $data['quantity'];
			}
		}
		$total = array_sum($totals);
	
		if ( $coupon = mp()->coupon_value(mp()->get_coupon_code(), $total) ) {
			$total = $coupon['new_total'];
		}
		
		//shipping line
		if ( ($shipping_price = mp()->shipping_price()) !== false ) {
			$total = $total + $shipping_price;
		}
		
		//tax line
		if ( ($tax_price = mp()->tax_price()) !== false ) {
			$total = $total + $tax_price;
		}
	
		$payment_info['gateway_public_name'] = $this->public_name;
		$payment_info['gateway_private_name'] = $this->admin_name;
		$payment_info['status'][$timestamp] = __('Invoiced', 'mp');
		$payment_info['total'] = $total;
		$payment_info['currency'] = mp_get_setting('currency');
		$payment_info['method'] = __('iDEAL', 'mp');
		$payment_info['transaction_id'] = $order_id;
	
		//create our order now
		$result = mp()->create_order($order_id, $cart, $shipping_info, $payment_info, false);
		
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
			'desc' => __('To make it easier to pay for online products and services, the Dutch banking community has developed the iDEAL online payment method. iDEAL allows online payments to be made using online banking in EUR only.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('advanced_select', array(
			'name' => $this->get_field_name('bank'),
			'label' => array('text' => __('Bank', 'mp')),
			'width' => 'element',
			'multiple' => false,
			'options' => array(
				'' => __('Select Your Bank', 'mp'),
				'ing' => __('ING Bank', 'mp'),
				'rabo' => __('Rabobank', 'mp'),
				'fries' => __('Friesland Bank', 'mp'),
				'abn' => __('ABN Amro Bank', 'mp'),
				'sim' => __('iDEAL Simulator (for testing)', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('merchant_id'),
			'label' => array('text' => __('Merchant ID', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('ideal_hash'),
			'label' => array('text' => __('Secret Key', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('mode'),
			'label' => array('text' => __('Mode', 'mp')),
			'default_value' => 'test',
			'options' => array(
				'test' => __('Test', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
  }
}

//mp_register_gateway_plugin( 'MP_Gateway_IDeal', 'ideal', __('iDEAL (beta)', 'mp') );