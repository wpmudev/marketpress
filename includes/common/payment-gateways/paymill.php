<?php
/*
	MarketPress Paymill Gateway Plugin
	Author: Marko Miljus
 */

class MP_Gateway_Paymill extends MP_Gateway_API {

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'paymill';
	
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
	 * The gateway's currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array(
		"EUR" => 'EUR',
		"BGN" => 'BGN',
		"CZK" => 'CZK',
		"HRK" => 'HRK',
		"DKK" => 'DKK',
		"GIP" => 'GIP',
		"HUF" => 'HUF',
		"ISK" => 'ISK',
		"ILS" => 'ILS',
		"LVL" => 'LVL',
		"CHF" => 'CHF',
		"LTL" => 'LTL',
		"NOK" => 'NOK',
		"PLN" => 'PLN',
		"RON" => 'RON',
		"SEK" => 'SEK',
		"TRY" => 'TRY',
		"GBP" => 'GBP'
	);

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set names here to be able to translate
		$this->admin_name = __('Paymill (beta)', 'mp');
		$this->public_name = __('Credit Card', 'mp');
		$this->method_img_url = mp_plugin_url('images/credit_card.png');
		$this->method_button_img_url = mp_plugin_url('images/cc-button.png');
		$this->public_key = $this->get_setting('api_credentials->public_key');
		$this->private_key = $this->get_setting('api_credentials->private_key');
		$this->force_ssl = (bool) $this->get_setting('is_ssl');
		$this->currency = $this->get_setting('currency', 'EUR');

		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}

		function enqueue_scripts() {
			if ( ! is_admin() && get_query_var('pagename') == 'cart' && get_query_var('checkoutstep') == 'checkout' ) {
				wp_enqueue_script('js-paymill', 'https://bridge.paymill.com/', array('jquery'));
				wp_enqueue_script('paymill-token', mp()->plugin_url . 'plugins-gateway/paymill-files/paymill_token.js', array('js-paymill', 'jquery'));
				wp_localize_script('paymill-token', 'paymill_token', array(
					'public_key' => $this->public_key,
					'invalid_cc_number' => __('Please enter a valid Credit Card Number.', 'mp'),
					'invalid_expiration' => __('Please choose a valid Expiration Date.', 'mp'),
					'invalid_cvc' => __('Please enter a valid Card CVC', 'mp'),
					'expired_card' => __('Card is no longer valid or has expired', 'mp'),
					'invalid_cardholder' => __('Invalid cardholder', 'mp'),
				));
			}
		}

		/**
		 * Return fields you need to add to the top of the payment screen, like your credit card info fields
		 *
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function payment_form($cart, $shipping_info) {
				$name = isset($_SESSION['mp_shipping_info']['name']) ? $_SESSION['mp_shipping_info']['name'] : '';

				$content = '';

				$content .= '<div id="paymill_checkout_errors"></div>';

				$content .= '<table class="mp_cart_billing">
				<thead><tr>
					<th colspan="2">' . __('Enter Your Credit Card Information:', 'mp') . '</th>
				</tr></thead>
				<tbody>
					<tr>
					<td align="right">' . __('Cardholder Name:', 'mp') . '</td>
					<td><input size="35" class="card-holdername" type="text" value="' . esc_attr($name) . '" /> </td>
					</tr>';


				$totals = array();
				foreach ($cart as $product_id => $variations) {
					foreach ($variations as $variation => $data) {
						$totals[] = mp()->before_tax_price($data['price'], $product_id) * $data['quantity'];
					}
				}

				$total = array_sum($totals);

				//coupon line
				if ($coupon = mp()->coupon_value(mp()->get_coupon_code(), $total)) {
						$total = $coupon['new_total'];
				}

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
						
				$content .= '<tr>';
				$content .= '<td>';
				$content .= __('Card Number', 'mp');
				$content .= '</td>';
				$content .= '<td>';
				$content .= '<input type="text" size="30" autocomplete="off" class="card-number"/>';
				$content .= '</td>';
				$content .= '</tr>';
				$content .= '<tr>';
				$content .= '<td>';
				$content .= __('Expiration:', 'mp');
				$content .= '</td>';
				$content .= '<td>';
				$content .= '<select class="card-expiry-month">';
				$content .= $this->_print_month_dropdown();
				$content .= '</select>';
				$content .= '<span> / </span>';
				$content .= '<select class="card-expiry-year">';
				$content .= $this->_print_year_dropdown('', true);
				$content .= '</select>';
				$content .= '</td>';
				$content .= '</tr>';
				$content .= '<tr>';
				$content .= '<td>';
				$content .= __('CVC:', 'mp');
				$content .= '</td>';
				$content .= '<td>';
				$content .= '<input type="text" size="4" autocomplete="off" class="card-cvc" />';
				$content .= '<input type="hidden" class="currency" value="' . $this->currency . '" />';
				$content .= '<input type="hidden" class="amount" value="' . $total * 100 . '" />';
				$content .= '</td>';
				$content .= '</tr>';
				$content .= '</table>';
				$content .= '<span id="paymill_processing" style="display: none;float: right;"><img src="' . mp()->plugin_url . 'images/loading.gif" /> ' . __('Processing...', 'psts') . '</span>';
				return $content;
		}

		/**
		 * Return the chosen payment details here for final confirmation. You probably don't need
		 *	to post anything in the form as it should be in your $_SESSION var already.
		 *
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function confirm_payment_form($cart, $shipping_info) {
				
		}

		/**
		 * Runs before page load incase you need to run any scripts before loading the success message page
		 */
		function order_confirmation($order) {
				
		}

		/**
		 * Print the years
		 */
		function _print_year_dropdown($sel='', $pfp = false) {
				$localDate = getdate();
				$minYear = $localDate["year"];
				$maxYear = $minYear + 15;

				$output = "<option value=''>--</option>";
				for ($i = $minYear; $i < $maxYear; $i++) {
						if ($pfp) {
								$output .= "<option value='" . substr($i, 0, 4) . "'" . ($sel == (substr($i, 0, 4)) ? ' selected' : '') .
												">" . $i . "</option>";
						} else {
								$output .= "<option value='" . substr($i, 2, 2) . "'" . ($sel == (substr($i, 2, 2)) ? ' selected' : '') .
												">" . $i . "</option>";
						}
				}
				return($output);
		}

		/**
		 * Print the months
		 */
		function _print_month_dropdown($sel='') {
				$output = "<option value=''>--</option>";
				$output .= "<option " . ($sel == 1 ? ' selected' : '') . " value='01'>01 - ".__('Jan', 'mp')."</option>";
				$output .= "<option " . ($sel == 2 ? ' selected' : '') . "	value='02'>02 - ".__('Feb', 'mp')."</option>";
				$output .= "<option " . ($sel == 3 ? ' selected' : '') . "	value='03'>03 - ".__('Mar', 'mp')."</option>";
				$output .= "<option " . ($sel == 4 ? ' selected' : '') . "	value='04'>04 - ".__('Apr', 'mp')."</option>";
				$output .= "<option " . ($sel == 5 ? ' selected' : '') . "	value='05'>05 - ".__('May', 'mp')."</option>";
				$output .= "<option " . ($sel == 6 ? ' selected' : '') . "	value='06'>06 - ".__('Jun', 'mp')."</option>";
				$output .= "<option " . ($sel == 7 ? ' selected' : '') . "	value='07'>07 - ".__('Jul', 'mp')."</option>";
				$output .= "<option " . ($sel == 8 ? ' selected' : '') . "	value='08'>08 - ".__('Aug', 'mp')."</option>";
				$output .= "<option " . ($sel == 9 ? ' selected' : '') . "	value='09'>09 - ".__('Sep', 'mp')."</option>";
				$output .= "<option " . ($sel == 10 ? ' selected' : '') . "	 value='10'>10 - ".__('Oct', 'mp')."</option>";
				$output .= "<option " . ($sel == 11 ? ' selected' : '') . "	 value='11'>11 - ".__('Nov', 'mp')."</option>";
				$output .= "<option " . ($sel == 12 ? ' selected' : '') . "	 value='12'>12 - ".__('Dec', 'mp')."</option>";

				return($output);
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
			if ( ! isset($_POST['paymillToken']) )
				mp()->cart_checkout_error(__('The Paymill Token was not generated correctly. Please try again.', 'mp'));

			//save to session
			if ( ! mp()->checkout_error ) {
				$_SESSION['paymillToken'] = $_POST['paymillToken'];
			}
		}

		/**
		 * Filters the order confirmation email message body. You may want to append something to
		 *	the message. Optional
		 *
		 * Don't forget to return!
		 */
		function order_confirmation_email($msg, $order = null) {
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
		
		
	  /* Initialize the settings metabox
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
				'desc' => __('Accept Visa, MasterCard, Maestro UK, Discover and Solo cards directly on your site. You don\'t need a merchant account or gateway. Credit cards go directly to Paymill\'s secure environment, and never hit your servers so you can avoid most PCI requirements.', 'mp'),
				'conditional' => array(
					'name' => 'gateways[allowed][' . $this->plugin_name . ']',
					'value' => 1,
					'action' => 'show',
				),
			));
			$metabox->add_field('checkbox', array(
				'name' => $this->get_field_name('is_ssl'),
				'label' => array('text' => __('Force SSL?', 'mp')),
				'message' => __('Yes', 'mp'),
				'desc' => __('When running on a live site, Paymill recommends you have an SSL certificate setup for the site where the checkout form will be displayed.', 'mp'),
			));
			$creds = $metabox->add_field('complex', array(
				'name' => $this->get_field_name('api_credentials'),
				'label' => array('text' => __('API Credentials', 'mp')),
				'desc' => __('You must login to Paymill to <a target="_blank" href="https://app.paymill.com/en-gb/auth/login">get your API credentials</a>. You can enter your test keys, then live ones when ready.', 'mp'),
			));
			
			if ( $creds instanceof WPMUDEV_Field ) {
				$creds->add_field('text', array(
					'name' => $this->get_field_name('private_key'),
					'label' => array('text' => __('Private Key', 'mp')),
				));
				$creds->add_field('text', array(
					'name' => $this->get_field_name('public_key'),
					'label' => array('text' => __('Public Key', 'mp')),
				));			
			}
			
			$metabox->add_field('advanced_select', array(
				'name' => $this->get_field_name('currency'),
				'default_value' => mp_get_setting('currency'),
				'label' => array('text' => __('Currency', 'mp')),
				'multiple' => false,
				'width' => 'element',
				'options' => array('' => __('Select One', 'mp')) + $this->currencies,
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
    		mp_push_to_array($settings, 'gateways->paymill->api_credentials->private_key', $val);
    		unset($settings['gateways']['paymill']['private_key']);
    	}
    	
    	if ( $val = $this->get_setting('public_key') ) {
    		mp_push_to_array($settings, 'gateways->paymill->api_credentials->public_key', $val);
    		unset($settings['gateways']['paymill']['public_key']);
    	}
    	
    	return $settings;
    }	 

		/**
		 * Use this to do the final payment. Create the order then process the payment. If
		 *	you know the payment is successful right away go ahead and change the order status
		 *	as well.
		 *	Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
		 *	it will redirect to the next step.
		 *
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function process_payment($cart, $shipping_info) {
				//make sure token is set at this point
				if (!isset($_SESSION['paymillToken'])) {
						mp()->cart_checkout_error(__('The Paymill Token was not generated correctly. Please go back and try again.', 'mp'));
						return false;
				}

				define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');
				define('PAYMILL_API_KEY', $this->private_key);

				$token = $_SESSION['paymillToken'];

				if ($token) {
						require "paymill-files/lib/Services/Paymill/Transactions.php";
						$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

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
						if ($shipping_price = mp()->shipping_price()) {
								$total += $shipping_price;
						}

						//tax line
						if ($tax_price = mp()->tax_price()) {
								$total += $tax_price;
						}

						$order_id = mp()->generate_order_id();

						try {
								$params = array(
										'amount' => $total * 100, //// I.e. 49 * 100 = 4900 Cents = 49 EUR
										'currency' => strtolower($this->currency), // ISO 4217
										'token' => $token,
										'description' => sprintf(__('%s Store Purchase - Order ID: %s, Email: %s', 'mp'), get_bloginfo('name'), $order_id, $_SESSION['mp_shipping_info']['email'])
								);
								$charge = $transactionsObject->create($params);

								if ($charge['status'] == 'closed') {
										//setup our payment details
										$payment_info = array();
										$payment_info['gateway_public_name'] = $this->public_name;
										$payment_info['gateway_private_name'] = $this->admin_name;
										$payment_info['method'] = sprintf(__('%1$s Card ending in %2$s - Expires %3$s', 'mp'), ucfirst($charge['payment']['card_type']), $charge['payment']['last4'], $charge['payment']['expire_month'] . '/' . $charge['payment']['expire_year']);
										$payment_info['transaction_id'] = $charge['id'];
										$timestamp = time();
										$payment_info['status'][$timestamp] = __('Paid', 'mp');
										$payment_info['total'] = $total;
										$payment_info['currency'] = $this->currency;

										$order = mp()->create_order($order_id, $cart, $_SESSION['mp_shipping_info'], $payment_info, true);
										unset($_SESSION['paymillToken']);
										mp()->set_cart_cookie(Array());
								}
						} catch (Exception $e) {
								unset($_SESSION['paymillToken']);
								mp()->cart_checkout_error(sprintf(__('There was an error processing your card: "%s". Please <a href="%s">go back and try again</a>.', 'mp'), $e->getMessage(), mp_checkout_step_url('checkout')));
								return false;
						}
				}
		}

		/**
		 * INS and payment return
		 */
		function process_ipn_return() {
		}
}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_Paymill', 'paymill', __('Paymill (beta)', 'mp'));