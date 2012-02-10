<?php
/*
MarketPress Stripe Gateway Plugin
Author: Aaron Edwards
*/

class MP_Gateway_Stripe extends MP_Gateway_API {

	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'stripe';
	
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
	
	//api vars
	var $publisher_key;
	
	/**
	* Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	* Sets up the google cart
	*/
	function on_creation() {
		global $mp;
		$settings = get_option('mp_settings');
		
		//set names here to be able to translate
		$this->admin_name = __('Stripe (beta)', 'mp');
		$this->public_name = __('Stripe', 'mp');
		if (isset($settings['gateways']['stripe'] ) ) {
			$this->publisher_key = $settings['gateways']['stripe']['publisher_key'];
		}
	}
	
	/**
	* Return fields you need to add to the top of the payment screen, like your credit card info fields
	*
	* @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
	* @param array $shipping_info. Contains shipping info and email in case you need it
	*/
	function payment_form($cart, $shipping_info) {
		global $mp;
		$settings = get_option('mp_settings');
		global $mp;
		$content = '';
		
		if (isset($_GET['cancel'])) {
		  $content .= '<div class="mp_checkout_error">' . __('Your credit card transaction has been canceled.', 'mp') . '</div>';
		}
		$settings = get_option('mp_settings');
		$meta = get_user_meta($current_user->ID, 'mp_billing_info', true);
		
		$email = (!empty($_SESSION['mp_billing_info']['email'])) ? $_SESSION['mp_billing_info']['email'] : (!empty($meta['email'])?$meta['email']:$_SESSION['mp_shipping_info']['email']);
		$name = (!empty($_SESSION['mp_billing_info']['name'])) ? $_SESSION['mp_billing_info']['name'] : (!empty($meta['name'])?$meta['name']:$_SESSION['mp_shipping_info']['name']);
		$address1 = (!empty($_SESSION['mp_billing_info']['address1'])) ? $_SESSION['mp_billing_info']['address1'] : (!empty($meta['address1'])?$meta['address1']:$_SESSION['mp_shipping_info']['address1']);
		$address2 = (!empty($_SESSION['mp_billing_info']['address2'])) ? $_SESSION['mp_billing_info']['address2'] : (!empty($meta['address2'])?$meta['address2']:$_SESSION['mp_shipping_info']['address2']);
		$city = (!empty($_SESSION['mp_billing_info']['city'])) ? $_SESSION['mp_billing_info']['city'] : (!empty($meta['city'])?$meta['city']:$_SESSION['mp_shipping_info']['city']);
		$state = (!empty($_SESSION['mp_billing_info']['state'])) ? $_SESSION['mp_billing_info']['state'] : (!empty($meta['state'])?$meta['state']:$_SESSION['mp_shipping_info']['state']);
		$zip = (!empty($_SESSION['mp_billing_info']['zip'])) ? $_SESSION['mp_billing_info']['zip'] : (!empty($meta['zip'])?$meta['zip']:$_SESSION['mp_shipping_info']['zip']);
		$country = (!empty($_SESSION['mp_billing_info']['country'])) ? $_SESSION['mp_billing_info']['country'] : (!empty($meta['country'])?$meta['country']:$_SESSION['mp_shipping_info']['country']);
		if (!$country)
		  $country = $settings['base_country'];
		$phone = (!empty($_SESSION['mp_billing_info']['phone'])) ? $_SESSION['mp_billing_info']['phone'] : (!empty($meta['phone'])?$meta['phone']:$_SESSION['mp_shipping_info']['phone']);

		

		$content = '';
		$content .= '<table class="mp_cart_billing">
        <thead><tr>
          <th colspan="2">'.__('Enter Your Billing Information:', 'mp').'</th>
        </tr></thead>
        <tbody>
        <tr>
          <td align="right">'.__('Email:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_email', '' ).'
        <input size="35" name="email" type="text" value="'.esc_attr($email).'" /></td>
          </tr>
  
          <tr>
          <td align="right">'.__('Full Name:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_name', '' ).'
        <input size="35" name="name" type="text" value="'.esc_attr($name).'" /> </td>
          </tr>
  
          <tr>
          <td align="right">'.__('Address:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_address1', '' ).'
        <input size="45" name="address1" type="text" value="'.esc_attr($address1).'" /><br />
        <small><em>'.__('Street address, P.O. box, company name, c/o', 'mp').'</em></small>
        </td>
          </tr>
  
          <tr>
          <td align="right">'.__('Address 2:', 'mp').'&nbsp;</td><td>
        <input size="45" name="address2" type="text" value="'.esc_attr($address2).'" /><br />
        <small><em>'.__('Apartment, suite, unit, building, floor, etc.', 'mp').'</em></small>
        </td>
          </tr>
  
          <tr>
          <td align="right">'.__('City:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_city', '' ).'
        <input size="25" name="city" type="text" value="'.esc_attr($city).'" /></td>
          </tr>
  
          <tr>
          <td align="right">'.__('State/Province/Region:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_state', '' ).'
        <input size="15" name="state" type="text" value="'.esc_attr($state).'" /></td>
          </tr>
  
          <tr>
          <td align="right">'.__('Postal/Zip Code:', 'mp').'*</td><td>
        '.apply_filters( 'mp_checkout_error_zip', '' ).'
        <input size="10" id="mp_zip" name="zip" type="text" value="'.esc_attr($zip).'" /></td>
          </tr>
  
          <tr>
          <td align="right">'.__('Country:', 'mp').'*</td><td>
          '.apply_filters( 'mp_checkout_error_country', '' ).'
        <select id="mp_" name="country">';

          foreach ((array)$settings['shipping']['allowed_countries'] as $code) {
            $content .= '<option value="'.$code.'"'.selected($country, $code, false).'>'.esc_attr($mp->countries[$code]).'</option>';
          }

      $content .= '</select>
        </td>
          </tr>
  
          <tr>
          <td align="right">'.__('Phone Number:', 'mp').'</td><td>
        	<input size="20" name="phone" type="text" value="'.esc_attr($phone).'" /></td>
          </tr>';
		$content .= '<tr>';
		$content .= '<td>';
		$content .= __('Card Number', 'mp');
		$content .= '</td>';
		$content .= '<td>';
		$content .= '<input type="text" size="20" autocomplete="off" name="card_num"/>';
		$content .= '</td>';
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= '<td>';
		$content .= __('CVC', 'mp');
		$content .= '</td>';
		$content .= '<td>';
		$content .= '<input type="text" size="4" autocomplete="off"name="card_code" />';
		$content .= '</td>';
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= '<td>';
		$content .= __('Expiration (MM/YYYY)', 'mp');
		$content .= '</td>';
		$content .= '<td>';
		$content .= '<select name="exp_month">';
		$content .= $this->_print_month_dropdown();
		$content .= '</select>';
		$content .= '<span> / </span>';
		$content .= '<select name="exp_year">';
		$content .= $this->_print_year_dropdown('', true);
		$content .= '</select>';
		$content .= '</td>';
		$content .= '</tr>';
		$content .= '</table>';
		return $content;
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
    
    $settings = get_option('mp_settings');
    $meta = get_user_meta($current_user->ID, 'mp_billing_info', true);
    
    $email = (!empty($_SESSION['mp_billing_info']['email'])) ? $_SESSION['mp_billing_info']['email'] : (!empty($meta['email'])?$meta['email']:$_SESSION['mp_shipping_info']['email']);
    $name = (!empty($_SESSION['mp_billing_info']['name'])) ? $_SESSION['mp_billing_info']['name'] : (!empty($meta['name'])?$meta['name']:$_SESSION['mp_shipping_info']['name']);
    $address1 = (!empty($_SESSION['mp_billing_info']['address1'])) ? $_SESSION['mp_billing_info']['address1'] : (!empty($meta['address1'])?$meta['address1']:$_SESSION['mp_shipping_info']['address1']);
    $address2 = (!empty($_SESSION['mp_billing_info']['address2'])) ? $_SESSION['mp_billing_info']['address2'] : (!empty($meta['address2'])?$meta['address2']:$_SESSION['mp_shipping_info']['address2']);
    $city = (!empty($_SESSION['mp_billing_info']['city'])) ? $_SESSION['mp_billing_info']['city'] : (!empty($meta['city'])?$meta['city']:$_SESSION['mp_shipping_info']['city']);
    $state = (!empty($_SESSION['mp_billing_info']['state'])) ? $_SESSION['mp_billing_info']['state'] : (!empty($meta['state'])?$meta['state']:$_SESSION['mp_shipping_info']['state']);
    $zip = (!empty($_SESSION['mp_billing_info']['zip'])) ? $_SESSION['mp_billing_info']['zip'] : (!empty($meta['zip'])?$meta['zip']:$_SESSION['mp_shipping_info']['zip']);
    $country = (!empty($_SESSION['mp_billing_info']['country'])) ? $_SESSION['mp_billing_info']['country'] : (!empty($meta['country'])?$meta['country']:$_SESSION['mp_shipping_info']['country']);
    if (!$country)
      $country = $settings['base_country'];
    $phone = (!empty($_SESSION['mp_billing_info']['phone'])) ? $_SESSION['mp_billing_info']['phone'] : (!empty($meta['phone'])?$meta['phone']:$_SESSION['mp_shipping_info']['phone']);

    $content = '';
    
    $content .= '<table class="mp_cart_billing">';
    $content .= '<thead><tr>';
    $content .= '<th>'.__('Billing Information:', 'mp').'</th>';
    $content .= '<th align="right"><a href="'. mp_checkout_step_url('checkout').'">'.__('&laquo; Edit', 'mp').'</a></th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Email:', 'mp').'</td><td>';
    $content .= esc_attr($email).'</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Full Name:', 'mp').'</td><td>';
    $content .= esc_attr($name).'</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Address:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($address1).'</td>';
    $content .= '</tr>';
    
    if ($address2) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Address 2:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($address2).'</td>';
      $content .= '</tr>';
    }
  
    $content .= '<tr>';
    $content .= '<td align="right">'.__('City:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($city).'</td>';
    $content .= '</tr>';
    
    if ($state) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('State/Province/Region:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($state).'</td>';
      $content .= '</tr>';
    }
    
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Postal/Zip Code:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($zip).'</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Country:', 'mp').'</td>';
    $content .= '<td>'.$mp->countries[$country].'</td>';
    $content .= '</tr>';
    
    if ($phone) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Phone Number:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($phone).'</td>';
      $content .= '</tr>';
    }
    
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Payment method:', 'mp').'</td>';
    $content .= '<td>'.$this->_get_card_type($_SESSION['card_num']).' ending in '. substr($_SESSION['card_num'], strlen($_SESSION['card_num'])-4, 4).'</td>';
    $content .= '</tr>';
    $content .= '</tbody>';
    $content .= '</table>';
    
    return $content;
  }
	
		function _get_card_type($number) {
			$num_length = strlen($number);
			
			if ($num_length > 10 && preg_match('/[0-9]+/', $number) >= 1) {
			  if((substr($number, 0, 1) == '4') && (($num_length == 13)||($num_length == 16))) {
				return "Visa";
			  } else if((substr($number, 0, 1) == '5' && ((substr($number, 1, 1) >= '1') && (substr($number, 1, 1) <= '5'))) && ($num_length == 16)) {
				return "Mastercard";
			  } else if(substr($number, 0, 4) == "6011" && ($num_length == 16)) {
				return "Discover Card";
			  } else if((substr($number, 0, 1) == '3' && ((substr($number, 1, 1) == '4') || (substr($number, 1, 1) == '7'))) && ($num_length == 15)) {
				return "American Express";
			  }
			}
			return "";
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
		$localDate=getdate();
		$minYear = $localDate["year"];
		$maxYear = $minYear + 15;

		$output = "<option value=''>--</option>";
		for($i=$minYear; $i<$maxYear; $i++) {
				if ($pfp) {
						$output .= "<option value='". substr($i, 0, 4) ."'".($sel==(substr($i, 0, 4))?' selected':'').
						">". $i ."</option>";
				} else {
						$output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
				">". $i ."</option>";
				}
		}
		return($output);
	}
	  
	/**
	 * Print the months
	 */
	function _print_month_dropdown($sel='') {
		$output =  "<option value=''>--</option>";
		$output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
		$output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
		$output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
		$output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
		$output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
		$output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
		$output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
		$output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
		$output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
		$output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
		$output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
		$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Dec</option>";

		return($output);
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
		$settings = get_option('mp_settings');
		if (!is_email($_POST['email']))
      $mp->cart_checkout_error('Please enter a valid Email Address.', 'email');
    
    if (empty($_POST['name']))
      $mp->cart_checkout_error('Please enter your Full Name.', 'name');
    
    if (empty($_POST['address1']))
      $mp->cart_checkout_error('Please enter your Street Address.', 'address1');
    
    if (empty($_POST['city']))
      $mp->cart_checkout_error('Please enter your City.', 'city');
    
    if (($_POST['country'] == 'US' || $_POST['country'] == 'CA') && empty($_POST['state']))
      $mp->cart_checkout_error('Please enter your State/Province/Region.', 'state');
    
    if (empty($_POST['zip']))
      $mp->cart_checkout_error('Please enter your Zip/Postal Code.', 'zip');
    
    if (empty($_POST['country']) || strlen($_POST['country']) != 2)
      $mp->cart_checkout_error('Please enter your Country.', 'country');
    
    //for checkout plugins
    do_action( 'mp_billing_process' );
    
    //save to session
    global $current_user;
    $meta = get_user_meta($current_user->ID, 'mp_billing_info', true);
    $_SESSION['mp_billing_info']['email'] = ($_POST['email']) ? trim(stripslashes($_POST['email'])) : $current_user->user_email;
    $_SESSION['mp_billing_info']['name'] = ($_POST['name']) ? trim(stripslashes($_POST['name'])) : $current_user->user_firstname . ' ' . $current_user->user_lastname;
    $_SESSION['mp_billing_info']['address1'] = ($_POST['address1']) ? trim(stripslashes($_POST['address1'])) : $meta['address1'];
    $_SESSION['mp_billing_info']['address2'] = ($_POST['address2']) ? trim(stripslashes($_POST['address2'])) : $meta['address2'];
    $_SESSION['mp_billing_info']['city'] = ($_POST['city']) ? trim(stripslashes($_POST['city'])) : $meta['city'];
    $_SESSION['mp_billing_info']['state'] = ($_POST['state']) ? trim(stripslashes($_POST['state'])) : $meta['state'];
    $_SESSION['mp_billing_info']['zip'] = ($_POST['zip']) ? trim(stripslashes($_POST['zip'])) : $meta['zip'];
    $_SESSION['mp_billing_info']['country'] = ($_POST['country']) ? trim($_POST['country']) : $meta['country'];
    $_SESSION['mp_billing_info']['phone'] = ($_POST['phone']) ? preg_replace('/[^0-9-\(\) ]/', '', trim($_POST['phone'])) : $meta['phone'];

    //save to user meta
    if ($current_user->ID)
      update_user_meta($current_user->ID, 'mp_billing_info', $_SESSION['mp_billing_info']);
		if (!isset($_POST['exp_month']) || !isset($_POST['exp_year']) || empty($_POST['exp_month']) || empty($_POST['exp_year'])) {
			$mp->cart_checkout_error( __('Please select your credit card expiration date.', 'mp'), 'exp');
		}
		
		if (!isset($_POST['card_code']) || empty($_POST['card_code'])) {
		  $mp->cart_checkout_error( __('Please enter your credit card security code', 'mp'), 'card_code');
		}
		
		if (!isset($_POST['card_num']) || empty($_POST['card_num'])) {
		  $mp->cart_checkout_error( __('Please enter your credit card number', 'mp'), 'card_num');
		} else {
		  if ($this->_get_card_type($_POST['card_num']) == "") {
			$mp->cart_checkout_error( __('Please enter a valid credit card number', 'mp'), 'card_num');
		  }
		}
		
		if (!$mp->checkout_error) {
		  $_SESSION['card_num'] = $_POST['card_num'];
		  $_SESSION['card_code'] = $_POST['card_code'];
		  $_SESSION['exp_month'] = $_POST['exp_month'];
		  $_SESSION['exp_year'] = $_POST['exp_year'];
		  
		  $mp->generate_order_id();
		}
	}
	

	
	/**
    * Filters the order confirmation email message body. You may want to append something to
    *  the message. Optional
    *
    * Don't forget to return!
    */
    function order_confirmation_email($msg) {
		return $msg;
    }
	
	/**
	* Echo a settings meta box with whatever settings you need for you gateway.
	*  Form field names should be prefixed with mp[gateways][plugin_name], like "mp[gateways][plugin_name][mysetting]".
	*  You can access saved settings via $settings array.
	*/
	function gateway_settings_box($settings) {
		global $mp;
		?>
		<div id="mp_stripe" class="postbox">
			<h3 class='hndle'><span><?php _e('Stripe', 'mp'); ?></span></h3>
			<div class="inside">
			<table class="form-table">
				<tr>
				    <th scope="row"><?php _e('Publisher Key', 'mp') ?></th>
				    <td>
			        <p>
						<input value="<?php echo esc_attr($settings['gateways']['stripe']['publisher_key']); ?>" size="30" name="mp[gateways][stripe][publisher_key]" type="text" />
			        </p>
				    </td>
				 </tr>
			</table>
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
			global $mp;
		$settings = get_option('mp_settings');
		$totals = array();
		foreach ($cart as $product_id => $variations) {
		  foreach ($variations as $variation => $data) {
			$sku = empty($data['SKU']) ? "{$product_id}_{$variation}" : $data['SKU'];
			$totals[] = $mp->before_tax_price($data['price']) * $data['quantity'];
			$payment->addLineItem($sku, substr($data['name'], 0, 31),
			  substr($data['name'].' - '.$data['url'], 0, 254), $data['quantity'], $mp->before_tax_price($data['price']), 1);
			$i++;
		  }
		}
		$total = array_sum($totals);

		//coupon line
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
		?>
		<span class="payment-errors"></span>
		<form action="" method="POST" id="payment-form">
		<input type="hidden" value="<?php echo $_SESSION['card_num']; ?>"  class="card-number" />
		<input type="hidden" value="<?php echo $_SESSION['card_code']; ?>"  class="card-cvc" />
		<input type="hidden" value="<?php echo $_SESSION['exp_month']; ?>"  class="card-expiry-month" />
		<input type="hidden" value="<?php echo $_SESSION['exp_year']; ?>"  class="card-expiry-year" />
		</form>
	  	<script type="text/javascript" src="https://js.stripe.com/v1/"></script>
		<script type="text/javascript">
		Stripe.setPublishableKey("<?php echo $settings['gateways']['stripe']['publisher_key']; ?>");
		function stripeResponseHandler(status, response) {
			if (response.error) {
				// re-enable the submit button
				jQuery('.submit-button').removeAttr("disabled");
				// show the errors on the form
				jQuery(".payment-errors").html(response.error.message);
			} else {
				var form$ = jQuery("#payment-form");
				// token contains id, last4, and card type
				var token = response['id'];
				// insert the token into the form so it gets submitted to the server
				form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
				// and submit
				form$.get(0).submit();
			}
		}

		jQuery(document).ready(function() {
				var chargeAmount = '<?php echo $total; ?>'; //amount you want to charge, in cents. 1000 = $10.00, 2000 = $20.00 ...
				// createToken returns immediately - the supplied callback submits the form if there are no errors
				Stripe.createToken({
					number: jQuery('.card-number').val(),
					cvc: jQuery('.card-cvc').val(),
					exp_month: jQuery('.card-expiry-month').val(),
					exp_year: jQuery('.card-expiry-year').val()
				}, chargeAmount, stripeResponseHandler);
		});
		</script>
		<?php
	  }
	
	/**
	* INS and payment return
	*/
	function process_ipn_return() {
		global $mp;
		$settings = get_option('mp_settings');
	}
}
 
//register payment gateway plugin
//mp_register_gateway_plugin( 'MP_Gateway_Stripe', 'stripe', __('Stripe', 'mp') );
?>