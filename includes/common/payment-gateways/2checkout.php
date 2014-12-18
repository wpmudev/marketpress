<?php
/*
MarketPress 2Checkout Gateway Plugin
Author: S H Mohanjith (Incsub), Marko Miljus (Incsub)
*/

class MP_Gateway_2Checkout extends MP_Gateway_API {
	//the current build version
	var $build = 2;
  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = '2checkout';
  //name of your gateway, for the admin side.
  var $admin_name = '';
  //public name of your gateway, for lists and such.
  var $public_name = '';
  //url for an image for your checkout method. Displayed on checkout form if set
  var $method_img_url = '';
  //url for an submit button image for your checkout method. Displayed on checkout form if set
  var $method_button_img_url = '';
  //whether or not ssl is needed for checkout page
  var $force_ssl = false;
  //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
  var $ipn_url;
  //whether if this is the only enabled gateway it can skip the payment_form step
  var $skip_form = true;
  //credit card vars
  var $API_Username, $API_Password, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $version, $currencyCode, $locale;
  
  /**
   * Refers to the gateways currencies
   *
   * @since 3.0
   * @access public
   * @var array
   */
  var $currencies = array(
    "AED" => 'AED - United Arab Emirates Dirham',
    "ARS" => 'ARS - Argentina Peso',
    "AUD" => 'AUD - Australian Dollar',
    "BRL" => 'BRL - Brazilian Real',
    "CAD" => 'CAD - Canadian Dollar',
    "CHF" => 'CHF - Swiss Franc',
    "DKK" => 'DKK - Danish Krone',
    "EUR" => 'EUR - Euro',
    "GBP" => 'GBP - British Pound',
    "HKD" => 'HKD - Hong Kong Dollar',
    "INR" => 'INR - Indian Rupee',
    "ILS" => 'ILS - Israeli New Sheqel',
    "JPY" => 'JPY - Japanese Yen',
    "LTL" => 'LTL - Lithuanian Litas',
    "MYR" => 'MYR - Malaysian Ringgit',
    "MXN" => 'MXN - Mexican Peso',
    "NOK" => 'NOK - Norwegian Krone',
    "NZD" => 'NZD - New Zealand Dollar',
    "PHP" => 'Philippine Peso',
    "RON" => 'Romanian New Leu',
    "RUB" => 'Russian Ruble',
    "SGD" => 'Singapore Dollar',
    "SEK" => 'SEK - Swedish Krona',
    "TRY" => 'TRY - Turkish Lira',
    "USD" => 'USD - U.S. Dollar',
    "ZAR" => 'ZAR - South African Rand'
  );

  /*     * **** Below are the public methods you may overwrite via a plugin ***** */

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //set names here to be able to translate
    $this->admin_name = __('2Checkout', 'mp');
    $this->public_name = __('2Checkout', 'mp');
    $this->method_img_url = mp_plugin_url('images/2co_logo.png');
    $this->method_button_img_url = mp_plugin_url('images/2co.png');
    $this->currencyCode = $this->get_setting('currency');
    $this->API_Username = $this->get_setting('sid');
    $this->API_Password = $this->get_setting('secret_word');
    $this->SandboxFlag = $this->get_setting('mode');
  }
  
  /**
   * Updates the gateway settings
   *
   * @since 3.0
   * @access public
   * @param array $settings
   * @return array
   */
  function update( $settings) {
	  if ( ($seller_id = $this->get_setting('sid')) && ($secret_word = $this->get_setting('secret_word')) ) {
		  mp_push_to_array($settings, 'gateways->2checkout->api_credentials->sid', $seller_id);
		  mp_push_to_array($settings, 'gateways->2checkout->api_credentials->secret_word', $secret_word);
		  unset($settings['gateways']['2checkout']['sid'], $settings['gateways']['2checkout']['secret_word']);
	  }
	  
	  return $settings;
  }

  /**
   * Return fields you need to add to the top of the payment screen, like your credit card info fields
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function payment_form($cart, $shipping_info) {
      if (isset($_GET['2checkout_cancel'])) {
          echo '<div class="mp_checkout_error">' . __('Your 2Checkout transaction has been canceled.', 'mp') . '</div>';
      }
  }

  /**
   * Use this to process any fields you added. Use the $_REQUEST global,
   *  and be sure to save it to both the $_SESSION and usermeta if logged in.
   *  DO NOT save credit card details to usermeta as it's not PCI compliant.
   *  Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function process_payment_form($cart, $shipping_info) {
      mp()->generate_order_id();
  }

  /**
   * Return the chosen payment details here for final confirmation. You probably don't need
   *  to post anything in the form as it should be in your $_SESSION var already.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function confirm_payment_form($cart, $shipping_info) {
  }

  /**
   * Use this to do the final payment. Create the order then process the payment. If
   *  you know the payment is successful right away go ahead and change the order status
   *  as well.
   *  Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
   * @param array $shipping_info. Contains shipping info and email in case you need it
   */
  function process_payment($cart, $shipping_info) {
      $timestamp = time();
      $settings = get_option('mp_settings');

      $url = "https://www.2checkout.com/checkout/purchase";

      $params = array();

      $params['sid'] = $this->API_Username;
      $params['cart_order_id'] = $_SESSION['mp_order'];
      $params['x_receipt_link_url'] = mp_checkout_step_url('confirmation');
      $params['skip_landing'] = '1';
      $params['fixed'] = 'Y';
      $params['currency_code'] = $this->currencyCode;

      if ($this->SandboxFlag == 'sandbox') {
          $params['demo'] = 'Y';
      }

      $totals = array();
      $counter = 1;

      $params["id_type"] = 1;
      $coupon_code = mp()->get_coupon_code();

      foreach ($cart as $product_id => $variations) {
          foreach ($variations as $variation => $data) {
							$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);
              $totals[] = $price;

              $suffix = "_{$counter}";

              $sku = empty($data['SKU']) ? $product_id : $data['SKU'];
              $params["c_prod{$suffix}"] = "{$sku},{$data['quantity']}";
              $params["c_name{$suffix}"] = $data['name'];
              $params["c_description{$suffix}"] = $data['url'];
              $params["c_price{$suffix}"] = $data['price'];
              if ($data['download'])
                  $params["c_tangible{$suffix}"] = 'N';
              else
                  $params["c_tangible{$suffix}"] = 'Y';
              $counter++;
          }
      }

      $total = array_sum($totals);

      //tax line
      if ( ! mp_get_setting('tax->tax_inclusive') ) {
      	$total += round(($total + mp()->tax_price()), 2);
      }

      $shipping_tax = 0;
      if ( ($shipping_price = mp()->shipping_price(false)) !== false ) {
				$total += $shipping_price;
				$params['sh_cost'] = $shipping_price;
				$shipping_tax = (mp()->shipping_tax_price($shipping_price) - $shipping_price);
      }

      //tax line if tax inclusive pricing is off. It it's on it would screw up the totals
      if ( ! mp_get_setting('tax->tax_inclusive') ) {
      	$tax_price = (mp()->tax_price(false) + $shipping_tax);
				$total += $tax_price;
      }
			
      $params['total'] = $total;

      $param_list = array();

      foreach ($params as $k => $v) {
          $param_list[] = "{$k}=" . rawurlencode($v);
      }

      $param_str = implode('&', $param_list);

      wp_redirect("{$url}?{$param_str}");

      exit(0);
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
      if ($order->post_status == 'order_received') {
          $content .= '<p>' . sprintf(__('Your payment via 2Checkout for this order totaling %s is not yet complete. Here is the latest status:', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total'])) . '</p>';
          $statuses = $order->mp_payment_info['status'];
          krsort($statuses); //sort with latest status at the top
          $status = reset($statuses);
          $timestamp = key($statuses);
          $content .= '<p><strong>' . mp()->format_date($timestamp) . ':</strong> ' . esc_html($status) . '</p>';
      } else {
          $content .= '<p>' . sprintf(__('Your payment via 2Checkout for this order totaling %s is complete. The transaction number is <strong>%s</strong>.', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']), $order->mp_payment_info['transaction_id']) . '</p>';
      }
      return $content;
  }

  /**
   * Runs before page load incase you need to run any scripts before loading the success message page
   */
  function order_confirmation($order) {
      ;

      $timestamp = time();
      $total = round($_REQUEST['total'], 2);

      if ($this->SandboxFlag == 'sandbox') {
          $hash = strtoupper(md5($this->API_Password . $this->API_Username . 1 . $total));
      } else {
          $hash = strtoupper(md5($this->API_Password . $this->API_Username . $_REQUEST['order_number'] . $total));
      }

      if ($_REQUEST['key'] == $hash) {
          $status = __('The order has been received', 'mp');
          $paid = apply_filters('mp_twocheckout_post_order_paid_status', true);

          $payment_info['gateway_public_name'] = $this->public_name;
          $payment_info['gateway_private_name'] = $this->admin_name;
          $payment_info['status'][$timestamp] = __("Paid", 'mp');
          $payment_info['total'] = $_REQUEST['total'];
          $payment_info['currency'] = $this->currencyCode;
          $payment_info['transaction_id'] = $_REQUEST['order_number'];
          $payment_info['method'] = "Credit Card";

          $order = mp()->create_order($_SESSION['mp_order'], mp()->get_cart_contents(), $_SESSION['mp_shipping_info'], $payment_info, $paid);
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
			'desc' => __('Resell your inventory via 2Checkout.com. This gateway requires that the setting in 2Checkout for "Return Method" inside Account -> Site Management be set to "Header Redirect".', 'mp'),
			'conditional' => array(
				'name' => 'gateways[allowed][' . $this->plugin_name . ']',
				'value' => 1,
				'action' => 'show',
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][mode]',
			'label' => array('text' => __('Mode', 'mp')),
			'default_value' => 'sandbox',
			'options' => array(
				'sandbox' => __('Sandbox', 'mp'),
				'live' => __('Live', 'mp'),
			),
		));
		$creds = $metabox->add_field('complex', array(
			'name' => 'gateways[' . $this->plugin_name . '][api_credentials]',
			'label' => array('text' => __('API Credentials', 'mp')),
			'desc' => __('You must login to PayPal and create an API signature to get your credentials. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">Instructions &raquo;</a>', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'sid',
				'label' => array('text' => __('Seller ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'secret_word',
				'label' => array('text' => __('Secret Word', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => 'gateways[' . $this->plugin_name . '][currency]',
			'label' => array('text' => __('Currency', 'mp')),
			'multiple' => false,
			'options' => array_merge(array('' => __('Select One', 'mp')), $this->currencies),
			'width' => 'element',
			'validation' => array(
				'required' => true,
			),
		));
  }

  /**
   * INS and payment return
   */
  function process_ipn_return() {
      $settings = get_option('mp_settings');

      if (isset($_REQUEST['message_type']) && $_REQUEST['message_type'] == 'INVOICE_STATUS_CHANGED') {
          $sale_id = $_REQUEST['sale_id'];
          $tco_invoice_id = $_REQUEST['invoice_id'];
          $tco_vendor_order_id = $_REQUEST['vendor_order_id'];
          $tco_invoice_status = $_REQUEST['invoice_status'];
          $tco_hash = $_REQUEST['md5_hash'];
          $total = $_REQUEST['invoice_list_amount'];
          $payment_method = ucfirst($_REQUEST['payment_type']);

          $order = mp()->get_order($tco_vendor_order_id);

          if (!$order) {
              header('HTTP/1.0 404 Not Found');
              header('Content-type: text/plain; charset=UTF-8');
              print 'Invoice not found';
              exit(0);
          }

          $calc_key = md5($sale_id . $settings['gateways']['2checkout']['sid'] . $_REQUEST['invoice_id'] . $settings['gateways']['2checkout']['secret_word']);

          if (strtolower($tco_hash) != strtolower($calc_key)) {
              header('HTTP/1.0 403 Forbidden');
              header('Content-type: text/plain; charset=UTF-8');
              print 'We were unable to authenticate the request';
              exit(0);
          }

          if (strtolower($_REQUEST['invoice_status']) != "deposited") {
              header('HTTP/1.0 200 OK');
              header('Content-type: text/plain; charset=UTF-8');
              print 'Thank you very much for letting us know. REF: Not success';
              exit(0);
          }

          if ($this->SandboxFlag != 'sandbox') {
              if (intval($total) >= $order->mp_order_total) {
                  $payment_info = $order->mp_payment_info;
                  $payment_info['transaction_id'] = $tco_invoice_id;
                  $payment_info['method'] = $payment_method;

                  update_post_meta($order->ID, 'mp_payment_info', $payment_info);

                  mp()->update_order_payment_status($tco_vendor_order_id, "paid", true);

                  header('HTTP/1.0 200 OK');
                  header('Content-type: text/plain; charset=UTF-8');
                  print 'Thank you very much for letting us know';
                  exit(0);
              }
          }
      }
  }

}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_2Checkout', '2checkout', __('2Checkout', 'mp'));