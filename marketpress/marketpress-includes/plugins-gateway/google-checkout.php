<?php
/*
MarketPress Google Checkout Gateway Plugin
Author: Paul K Abwonji
*/

  
class MP_Gateway_GoogleCheckout extends MP_Gateway_API {
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'google-checkout';
	
	//name of your gateway, for the admin side.
	var $admin_name = '';
  
	//public name of your gateway, for lists and such.
	var $public_name = '';
	
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url = '';
  
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url = '';
	
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	var $ipn_url;
	
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form = true;
	
	//Google cart
	var $googleCart;
	
	//API response
	var $response;
	
	//Response array
	var $results  = array();
	var $approved;
	var $declined;
	var $error;
	var $method;

	//api vars
	var $server_type, $API_Merchant_id, $API_Merchant_key, $version, $currencyCode, $API_URL;
	
	
	/**
	* Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	* Sets up the google cart
	*/
	function on_creation() {
		global $mp;
		$settings = get_option('mp_settings');
		
		//set names here to be able to translate
		$this->admin_name = __('Google Checkout (beta)', 'mp');
		$this->public_name = __('Google Checkout', 'mp');
		$this->method_img_url = $mp->plugin_url . 'images/google_checkout.gif';
		$this->method_button_img_url = $mp->plugin_url . 'images/google_checkout-button.gif';
		
		/*
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googlecart.php');
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googleitem.php');
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googleresponse.php');
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googlemerchantcalculations.php');
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googleresult.php');
		require_once($mp->plugin_dir .'plugins-gateway/google-checkout-library/googlerequest.php');
		*/
		
		if (isset($settings['gateways']['google-checkout'] ) ) {
			$this->API_Merchant_id = $settings['gateways']['google-checkout']['merchant_id'];
			$this->API_Merchant_key = $settings['gateways']['google-checkout']['merchant_key'];
			$this->server_type = $settings['gateways']['google-checkout']['server_type'];
			$this->currencyCode = $settings['gateways']['google-checkout']['currency'];
			
			if(strtolower($this->server_type) == "sandbox") {
				$this->API_URL = "https://sandbox.google.com/checkout/";
			} else {
				$this->API_URL=  "https://checkout.google.com/";  
			}
		}
		
	}
	
	/**
	* Use this to process any fields you added. Use the $_POST global,
	* and be sure to save it to both the $_SESSION and usermeta if logged in.
	* DO NOT save credit card details to usermeta as it's not PCI compliant.
	* Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
	* it will redirect to the next step.
	*
	* @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
	* @param array $shipping_info. Contains shipping info and email in case you need it
	*/
	function process_payment_form($cart, $shipping_info) {
		global $mp;
	}
	
	/**
    * Echo fields you need to add to the payment screen, like your credit card info fields
    *
    * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
    * @param array $shipping_info. Contains shipping info and email in case you need it
    */
	function payment_form($cart, $shipping_info) {
		global $mp;
		if (isset($_GET['googlecheckout_cancel'])) {
		  echo '<div class="mp_checkout_error">' . __('Your Moneybookers transaction has been canceled.', 'mp') . '</div>';
		}
	}
	
	 /**
	   * Use this to do the final payment. Create the order then process the payment. If
	   *  you know the payment is successful right away go ahead and change the order status
	   *  as well.
	   *  Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
	   *  it will redirect to the next step.
	   *
	   * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
	   * @param array $shipping_info. Contains shipping info and email in case you need it
	   */
	function process_payment($cart, $shipping_info) {
		global $mp, $current_user;
		$timestamp = time();
		$settings = get_option('mp_settings');
		
		$url = $this->API_URL . "api/checkout/v2/merchantCheckoutForm/Merchant/" . $this->API_Merchant_id;
		$order_id = $mp->generate_order_id();
		
		$params = array();
		$params['_type'] = 'checkout-shopping-cart';
		$params['order_id'] = $order_id;
		$params['checkout-flow-support.merchant-checkout-flow-support.edit-cart-url'] = mp_cart_link(false, true);
		$params["checkout-flow-support.merchant-checkout-flow-support.continue-shopping-url"] = mp_checkout_step_url('confirmation');

    $params["checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.shipping-taxed"] = ($settings['tax']['tax_shipping']) ? 'true' : 'false';
    $params["checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.tax-areas.world-area-1"] = '';

		$totals = array();
		$item_params = array();
		$i = 1;
		$items = 0;
		foreach ($cart as $product_id => $variations) {
			foreach ($variations as $data) {
				$totals[] = $data['price'] * $data['quantity'];
		    $item_params["shopping-cart.items.item-{$i}.item-name"] = $data['name'];
				$item_params["shopping-cart.items.item-{$i}.item-description"] = $data['url'];
				$item_params["shopping-cart.items.item-{$i}.unit-price"] = $data['price'];
				$item_params["shopping-cart.items.item-{$i}.unit-price.currency"] = $this->currencyCode;
				$item_params["shopping-cart.items.item-{$i}.quantity"] = $data['quantity'];
				$item_params["shopping-cart.items.item-{$i}.merchant-item-id"] = $data['SKU'];
				$i++;
				$items++;
			}
		}
		
		$total = array_sum($totals);
		
		if ( $coupon = $mp->coupon_value($mp->get_coupon_code(), $total) ) {
		  $total = $coupon['new_total'];
		  $params["shopping-cart.items.item-1.item-name"] = __('Order ID: ', 'mp') . $order_id;
			$params["shopping-cart.items.item-1.item-description"] = sprintf( __('Cart Subtotal for %d Items', 'mp'), $items);
			$params["shopping-cart.items.item-1.unit-price"] = $total;
			$params["shopping-cart.items.item-1.unit-price.currency"] = $this->currencyCode;
			$params["shopping-cart.items.item-1.quantity"] = 1;
			$params["shopping-cart.items.item-1.merchant-item-id"] = $order_id;
		} else {
      $params = array_merge($params, $item_params);
		}

		//shipping line
		if ( ($shipping_price = $mp->shipping_price()) !== false ) {
			$total = $total + $shipping_price;
			$params["checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.price"] = $shipping_price;
			$params["checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.price.currency"] = $this->currencyCode;
			$params["checkout-flow-support.merchant-checkout-flow-support.shipping-methods.flat-rate-shipping-1.name"] = __('Standard Shipping', 'mp');
		}
		
		//tax line
		if ( ($tax_price = $mp->tax_price()) !== false ) {
			$total = $total + $tax_price;
			$params["checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.rate"] = $settings['tax']['rate'];
		} else {
      $params["checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.rate"] = '0.00';
		}

		$param_list = array();
		foreach ($params as $k => $v) {
			$param_list[] = "{$k}=".rawurlencode($v);
		}

		$param_str = implode('&', $param_list);
    
		//setup transients for ipn in case checkout doesn't redirect (ipn should come within 12 hrs!)
		set_transient('mp_order_'. $order_id . '_cart', $cart, 60*60*12);
		set_transient('mp_order_'. $order_id . '_userid', $current_user->ID, 60*60*12);
		
		$this->processGoogleCart($param_str, $url);

    parse_str($this->response, $response);

		if ($response['_type'] == 'checkout-redirect') {
      wp_redirect($response['redirect-url']);
			exit;
		} else {
			$mp->cart_checkout_error( sprintf(__('There was a problem setting up your purchase with Google Checkout. Please try again or <a href="%s">select a different payment method</a>.<br/>%s', 'mp'), mp_checkout_step_url('checkout'), @$response['error-message']) );
		}
	}
	
	function processGoogleCart($param_str, $url){
		global $mp;
		$args['user-agent'] = "MarketPress/{$mp->version}: http://premium.wpmudev.org/project/e-commerce | Google Checkout Payment Plugin/{$mp->version}";
		$args['body'] = $param_str;
		$args['sslverify'] = false;
    $args['headers']['Authorization'] = 'Basic ' . base64_encode($this->API_Merchant_id.':'.$this->API_Merchant_key);
    $args['headers']['Content-Type'] = 'application/xml;charset=UTF-8';
    $args['headers']['Accept'] = 'application/xml;charset=UTF-8';
		    
    //use built in WP http class to work with most server setups
    $response = wp_remote_post($url, $args);
		if (is_array($response) && isset($response['body'])) {
			$this->response = $response['body'];
    } else {
			$this->response = "";
			$this->error = true;
			return;
    }
	}
	
	
	
	/**
   * Return the chosen payment details here for final confirmation. You probably don't need
   *  to post anything in the form as it should be in your $_SESSION var already.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
	function confirm_payment_form($cart, $shipping_info) {
		global $mp;
		//print payment details
		return $this->googleCart->CustomCheckoutButtonCode("SMALL");
	}
	
	
	
	/**
   * Filters the order confirmation email message body. You may want to append something to
   *  the message. Optional
   *
   * Don't forget to return!
   */
  function order_confirmation_email($msg, $order) {
		return $msg;
  }
	
	/**
   * Return any html you want to show on the confirmation screen after checkout. This
   *  should be a payment details box and message.
   *
   * Don't forget to return!
   */
	function order_confirmation_msg($content, $order) {
		global $mp;
		$content = '';
		if ($order->post_status == 'order_received') {
		  $content .= '<p>' . sprintf(__('Your payment via Google Checkout for this order totaling %s is in progress. Here is the latest status:', 'mp'), $mp->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total'])) . '</p>';
		  $statuses = $order->mp_payment_info['status'];
		  krsort($statuses); //sort with latest status at the top
		  $status = reset($statuses);
		  $timestamp = key($statuses);
		  $content .= '<p><strong>' . date(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong>' . htmlentities($status) . '</p>';
		} else {
		  $content .= '<p>' . sprintf(__('Your payment via Google Checkout for this order totaling %s is complete. The transaction number is <strong>%s</strong>.', 'mp'), $mp->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']), $order->mp_payment_info['transaction_id']) . '</p>';
		}
		return $content;
	}
  
	/**
   * Runs before page load incase you need to run any scripts before loading the success message page
   */
	function order_confirmation($order) {
		global $mp;
		
		//check if not created already by IPN, and create it
		if (!$order) {
		  //get totals
			$cart = $mp->get_cart_contents();
			foreach ($cart as $product_id => $variations) {
					foreach ($variations as $data) {
						$totals[] = $data['price'] * $data['quantity'];
					}
			}
			$total = array_sum($totals);

			if ( $coupon = $mp->coupon_value($mp->get_coupon_code(), $total) ) {
			  $total = $coupon['new_total'];
			}

			//shipping line
			if ( ($shipping_price = $mp->shipping_price()) !== false ) {
			  $total = $total + $shipping_price;
			}

			//tax line
			if ( ($tax_price = $mp->tax_price()) !== false ) {
			  $total = $total + $tax_price;
			}

			$status = __('Received - The order has been received, awaiting payment confirmation.', 'mp');
			//setup our payment details
		  $payment_info['gateway_public_name'] = $this->public_name;
			$payment_info['gateway_private_name'] = $this->admin_name;
		  $payment_info['method'] = __('Google Checkout Payment', 'mp');
		  $payment_info['transaction_id'] = $_SESSION['mp_order'];
		  $timestamp = time();
		  $payment_info['status'][$timestamp] = $status;
		  $payment_info['total'] = $total;
		  $payment_info['currency'] = $this->currencyCode;

			$order = $mp->create_order($_SESSION['mp_order'], $cart, $_SESSION['mp_shipping_info'], $payment_info, false);
			//if successful delete transients
		  if ($order) {
				delete_transient('mp_order_' . $order_id . '_cart');
				delete_transient('mp_order_' . $order_id . '_shipping');
		  }
		} else {
		  $mp->set_cart_cookie(Array());
		}
	}
	
	
	/**
   * Echo a settings meta box with whatever settings you need for you gateway.
   *  Form field names should be prefixed with mp[gateways][plugin_name], like "mp[gateways][plugin_name][mysetting]".
   *  You can access saved settings via $settings array.
   */
  function gateway_settings_box($settings) {
    global $mp;
        
    ?>
    <div id="mp_google_checkout" class="postbox">
      <h3 class='handle'><span><?php _e('Google Checkout Settings', 'mp'); ?></span></h3>
      <div class="inside">
        <span class="description"><?php _e('Resell your inventory via Google Checkout', 'mp') ?></span>
        <table class="form-table">
				  <tr>
				    <th scope="row"><?php _e('Mode', 'mp') ?></th>
				    <td>
			        <p>
			          <select name="mp[gateways][google-checkout][server_type]">
								<?php
								$server_types = array(
									"sandbox" => 'Sandbox',
									"live" => 'Live'
								);
								foreach ($server_types as $k => $v) {
								  echo '<option value="' . $k . '"' . ($k == $settings['gateways']['google-checkout']['server_type'] ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
								}
								?>
			          </select>
			        </p>
				    </td>
				  </tr>
				  <tr>
				    <th scope="row"><?php _e('Google Checkout Credentials', 'mp') ?></th>
				    <td>
			        <span class="description"><?php print sprintf(__('You must login to Google Checkout to obtain your merchant ID and merchant key. <a target="_blank" href="%s">Instructions &raquo;</a>', 'mp'), "http://code.google.com/apis/checkout/developer/Google_Checkout_Basic_HTML_Signing_Up.html"); ?></span>
				      <p>
							<label><?php _e('Merchant ID', 'mp') ?><br />
							  <input value="<?php echo esc_attr($settings['gateways']['google-checkout']['merchant_id']); ?>" size="30" name="mp[gateways][google-checkout][merchant_id]" type="text" />
							</label>
				      </p>
				      <p>
							<label><?php _e('Merchant Key', 'mp') ?><br />
							  <input value="<?php echo esc_attr($settings['gateways']['google-checkout']['merchant_key']); ?>" size="30" name="mp[gateways][google-checkout][merchant_key]" type="text" />
							</label>
				      </p>
				    </td>
				  </tr>
	          <tr valign="top">
	        <th scope="row"><?php _e('Google Checkout Currency', 'mp') ?></th>
	        <td>
	          <select name="mp[gateways][google-checkout][currency]">
	          <?php
	          $sel_currency = ($settings['gateways']['google-checkout']['currency']) ? $settings['gateways']['google-checkout']['currency'] : $settings['currency'];
	          $currencies = array(
							"USD" => 'USD - U.S. Dollar',
							"GBP" => 'GBP - British Pound'
	          );

	          foreach ($currencies as $k => $v) {
	              echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
	          }
	          ?>
	          </select>
	        </td>
	        </tr>
        </table>
      </div>
    </div>
    <?php
  }
  
  /**
   * Filters posted data from your settings form. Do anything you need to the $settings['gateways']['plugin_name']
   *  array. Don't forget to return!
   */
  function process_gateway_settings($settings) {
    return $settings;
  }
}

//register payment gateway plugin
mp_register_gateway_plugin( 'MP_Gateway_GoogleCheckout', 'google-checkout', __('Google Checkout (beta)', 'mp') );
?>