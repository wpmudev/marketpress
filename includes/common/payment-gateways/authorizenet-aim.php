<?php

/**
 * MarketPress Authorize.net AIM Gateway Plugin
 *
 * @since 3.0
 * @package MarketPress
 */

class MP_Gateway_AuthorizeNet_AIM extends MP_Gateway_API {
		//build of the gateway plugin
		var $build = null;
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = 'authorizenet-aim';
		//name of your gateway, for the admin side.
		var $admin_name = '';
		//public name of your gateway, for lists and such.
		var $public_name = '';
		//url for an image for your checkout method. Displayed on checkout form if set
		var $method_img_url = '';
		//url for an submit button image for your checkout method. Displayed on checkout form if set
		var $method_button_img_url = '';
		//whether or not ssl is needed for checkout page
		var $force_ssl = true;
		//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
		var $ipn_url;
		//whether if this is the only enabled gateway it can skip the payment_form step
		var $skip_form = false;
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
			'AUD' => 'AUD - Australian Dollar',
			'CAD' => 'CAD - Canadian Dollar',
			'EUR' => 'EUR - Euro',
			'GBP' => 'GBP - Pound Sterling',
			'USD' => 'USD - U.S. Dollar',
			'NZD' => 'NZD - New Zealand Dollar',
		);

		/**
		 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
		 *
		 * @since 3.0
		 * @access public
		 */
		function on_creation() {
			//set names here to be able to translate
			$this->admin_name = __('Authorize.net Checkout', 'mp');
			$this->public_name = __('Credit Card', 'mp');

			$this->method_img_url = mp_plugin_url('images/credit_card.png');
			$this->method_button_img_url = mp_plugin_url('images/cc-button.png');

			$this->version = "63.0"; //api version
			
			//set credit card vars
			$this->API_Username = $this->get_setting('api_credentials->api_user');
			$this->currencyCode = $this->get_setting('currency', 'USD');

			//set api urls
			if ( $custom_api = $this->get_setting('custom_api') ) {
				$this->API_Endpoint = esc_url_raw($custom_api);
			} elseif ( $this->get_setting('mode') == 'sandbox' ) {
				$this->API_Endpoint = "https://test.authorize.net/gateway/transact.dll";
				$this->force_ssl = false;
			} else {
				$this->API_Endpoint = "https://secure.authorize.net/gateway/transact.dll";
			}
		}

	  /**
	   * Updates the gateway settings
	   *
	   * @since 3.0
	   * @access public
	   * @param array $settings
	   * @return array $settings
	   */
    public function update( $settings ) {
    	if ( ($api_user = $this->get_setting('api_user')) && ($api_key = $this->get_setting('api_key')) ) {
	    	mp_push_to_array($settings, 'gateways->authorizenet-aim->api_credentials->api_user', $api_user);
	    	mp_push_to_array($settings, 'gateways->authorizenet-aim->api_credentials->api_key', $api_key);
	    	unset($settings['gateways']['authorizenet-aim']['api_user'], $settings['gateways']['authorizenet-aim']['api_key']);
    	}
    	
    	return $settings;
		}
		
		/**
		 * Return fields you need to add to the top of the payment screen, like your credit card info fields
		 *
		 * @since 3.0
		 * @access public		 
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function payment_form( $cart, $shipping_info ) {
				global $current_user;
				$content = '';

				if (isset($_GET['cancel'])) {
					$content .= '<div class="mp_checkout_error">' . __('Your credit card transaction has been canceled.', 'mp') . '</div>';
				}

				$meta = get_user_meta($current_user->ID, 'mp_billing_info', true);

				$email = (!empty($_SESSION['mp_billing_info']['email'])) ? $_SESSION['mp_billing_info']['email'] : (!empty($meta['email']) ? $meta['email'] : $_SESSION['mp_shipping_info']['email']);
				$name = (!empty($_SESSION['mp_billing_info']['name'])) ? $_SESSION['mp_billing_info']['name'] : (!empty($meta['name']) ? $meta['name'] : $_SESSION['mp_shipping_info']['name']);
				$address1 = (!empty($_SESSION['mp_billing_info']['address1'])) ? $_SESSION['mp_billing_info']['address1'] : (!empty($meta['address1']) ? $meta['address1'] : $_SESSION['mp_shipping_info']['address1']);
				$address2 = (!empty($_SESSION['mp_billing_info']['address2'])) ? $_SESSION['mp_billing_info']['address2'] : (!empty($meta['address2']) ? $meta['address2'] : $_SESSION['mp_shipping_info']['address2']);
				$city = (!empty($_SESSION['mp_billing_info']['city'])) ? $_SESSION['mp_billing_info']['city'] : (!empty($meta['city']) ? $meta['city'] : $_SESSION['mp_shipping_info']['city']);
				$state = (!empty($_SESSION['mp_billing_info']['state'])) ? $_SESSION['mp_billing_info']['state'] : (!empty($meta['state']) ? $meta['state'] : $_SESSION['mp_shipping_info']['state']);
				$zip = (!empty($_SESSION['mp_billing_info']['zip'])) ? $_SESSION['mp_billing_info']['zip'] : (!empty($meta['zip']) ? $meta['zip'] : $_SESSION['mp_shipping_info']['zip']);
				$country = (!empty($_SESSION['mp_billing_info']['country'])) ? $_SESSION['mp_billing_info']['country'] : (!empty($meta['country']) ? $meta['country'] : $_SESSION['mp_shipping_info']['country']);
				if (!$country)
						$country = mp_get_setting('base_country');
				$phone = (!empty($_SESSION['mp_billing_info']['phone'])) ? $_SESSION['mp_billing_info']['phone'] : (!empty($meta['phone']) ? $meta['phone'] : $_SESSION['mp_shipping_info']['phone']);

				$content .= '<style type="text/css">
				.cardimage {
					height: 23px;
					width: 157px;
					display: inline-table;
				}
				
				.nocard {
					background-position: 0px 0px !important;
				}
				
				.visa_card {
					background-position: 0px -23px !important;
				}
				
				.mastercard {
					background-position: 0px -46px !important;
				}
				
				.discover_card {
					background-position: 0px -69px !important;
				}
				
				.amex {
					background-position: 0px -92px !important;
				}
			</style>
			<script type="text/javascript">
				function cc_card_pick(card_image, card_num){
					if (card_image == null) {
									card_image = "#cardimage";
					}
					if (card_num == null) {
									card_num = "#card_num";
					}
	
					numLength = jQuery(card_num).val().length;
					number = jQuery(card_num).val();
					if (numLength > 10)
					{
									if((number.charAt(0) == "4") && ((numLength == 13)||(numLength==16))) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage visa_card"); }
									else if((number.charAt(0) == "5" && ((number.charAt(1) >= "1") && (number.charAt(1) <= "5"))) && (numLength==16)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage mastercard"); }
									else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage amex"); }
									else if((number.charAt(0) == "3" && ((number.charAt(1) == "4") || (number.charAt(1) == "7"))) && (numLength==15)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage discover_card"); }
									else { jQuery(card_image).removeClass(); jQuery(card_image).addClass("cardimage nocard"); }
	
					}
				}
				jQuery(document).ready( function() {
					jQuery(".noautocomplete").attr("autocomplete", "off");
				});
			</script>';
				$content .= '<table class="mp_cart_billing">
				<thead><tr>
					<th colspan="2">' . __('Enter Your Billing Information:', 'mp') . '</th>
				</tr></thead>
				<tbody>
				<tr>
					<td align="right">' . __('Email:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_email', '') . '
				<input size="35" name="email" type="text" value="' . esc_attr($email) . '" /></td>
					</tr>
	
					<tr>
					<td align="right">' . __('Full Name:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_name', '') . '
				<input size="35" name="name" type="text" value="' . esc_attr($name) . '" /> </td>
					</tr>
	
					<tr>
					<td align="right">' . __('Address:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_address1', '') . '
				<input size="45" name="address1" type="text" value="' . esc_attr($address1) . '" /><br />
				<small><em>' . __('Street address, P.O. box, company name, c/o', 'mp') . '</em></small>
				</td>
					</tr>
	
					<tr>
					<td align="right">' . __('Address 2:', 'mp') . '&nbsp;</td><td>
				<input size="45" name="address2" type="text" value="' . esc_attr($address2) . '" /><br />
				<small><em>' . __('Apartment, suite, unit, building, floor, etc.', 'mp') . '</em></small>
				</td>
					</tr>
	
					<tr>
					<td align="right">' . __('City:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_city', '') . '
				<input size="25" name="city" type="text" value="' . esc_attr($city) . '" /></td>
					</tr>
	
					<tr>
					<td align="right">' . __('State/Province/Region:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_state', '') . '
				<input size="15" name="state" type="text" value="' . esc_attr($state) . '" /></td>
					</tr>
	
					<tr>
					<td align="right">' . __('Postal/Zip Code:', 'mp') . '*</td><td>
				' . apply_filters('mp_checkout_error_zip', '') . '
				<input size="10" id="mp_zip" name="zip" type="text" value="' . esc_attr($zip) . '" /></td>
					</tr>
	
					<tr>
					<td align="right">' . __('Country:', 'mp') . '*</td><td>
					' . apply_filters('mp_checkout_error_country', '') . '
				<select id="mp_" name="country">';

				foreach ( mp_get_setting('shipping->allowed_countries', array()) as $code) {
				 $content .= '<option value="' . $code . '"' . selected($country, $code, false) . '>' . esc_attr(mp()->countries[$code]) . '</option>';
				}

				$content .= '</select>
				</td>
					</tr>
	
					<tr>
					<td align="right">' . __('Phone Number:', 'mp') . '</td><td>
					<input size="20" name="phone" type="text" value="' . esc_attr($phone) . '" /></td>
					</tr>
					
					<tr>
						<td align="right">' . __('Credit Card Number:', 'mp') . '*</td>
						<td>
							' . apply_filters('mp_checkout_error_card_num', '') . '
							<input name="card_num" onkeyup="cc_card_pick(\'#cardimage\', \'#card_num\');"
							 id="card_num" class="credit_card_number input_field noautocomplete"
							 type="text" size="22" maxlength="22" />
						<div class="hide_after_success nocard cardimage"	id="cardimage" style="background: url(' . mp()->plugin_url . 'images/card_array.png) no-repeat;"></div></td>
					</tr>
					
					<tr>
						<td align="right">' . __('Expiration Date:', 'mp') . '*</td>
						<td>
						' . apply_filters('mp_checkout_error_exp', '') . '
						<label class="inputLabel" for="exp_month">' . __('Month', 'mp') . '</label>
						<select name="exp_month" id="exp_month">
							' . $this->_print_month_dropdown() . '
						</select>
						<label class="inputLabel" for="exp_year">' . __('Year', 'mp') . '</label>
						<select name="exp_year" id="exp_year">
							' . $this->_print_year_dropdown('', true) . '
						</select>
						</td>
					</tr>
					
					<tr>
						<td align="right">' . __('Security Code:', 'mp') . '</td>
						<td>' . apply_filters('mp_checkout_error_card_code', '') . '
						<input id="card_code" name="card_code" class="input_field noautocomplete"
							 style="width: 70px;" type="text" size="4" maxlength="4" /></td>
					</tr>
	
				</tbody>
			</table>';

				return $content;
		}

		function _print_year_dropdown($sel = '', $pfp = false) {
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

		function _print_month_dropdown($sel = '') {
				$output = "<option value=''>--</option>";
				$output .= "<option " . ($sel == 1 ? ' selected' : '') . " value='01'>01 - Jan</option>";
				$output .= "<option " . ($sel == 2 ? ' selected' : '') . "	value='02'>02 - Feb</option>";
				$output .= "<option " . ($sel == 3 ? ' selected' : '') . "	value='03'>03 - Mar</option>";
				$output .= "<option " . ($sel == 4 ? ' selected' : '') . "	value='04'>04 - Apr</option>";
				$output .= "<option " . ($sel == 5 ? ' selected' : '') . "	value='05'>05 - May</option>";
				$output .= "<option " . ($sel == 6 ? ' selected' : '') . "	value='06'>06 - Jun</option>";
				$output .= "<option " . ($sel == 7 ? ' selected' : '') . "	value='07'>07 - Jul</option>";
				$output .= "<option " . ($sel == 8 ? ' selected' : '') . "	value='08'>08 - Aug</option>";
				$output .= "<option " . ($sel == 9 ? ' selected' : '') . "	value='09'>09 - Sep</option>";
				$output .= "<option " . ($sel == 10 ? ' selected' : '') . "	 value='10'>10 - Oct</option>";
				$output .= "<option " . ($sel == 11 ? ' selected' : '') . "	 value='11'>11 - Nov</option>";
				$output .= "<option " . ($sel == 12 ? ' selected' : '') . "	 value='12'>12 - Dec</option>";

				return($output);
		}

		/**
		 * Use this to process any fields you added. Use the $_POST global,
		 *	and be sure to save it to both the $_SESSION and usermeta if logged in.
		 *	DO NOT save credit card details to usermeta as it's not PCI compliant.
		 *	Call mp()->cart_checkout_error($msg, $context); to handle errors. If no errors
		 *	it will redirect to the next step.
		 *
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function process_payment_form($cart, $shipping_info) {
				if (!is_email($_POST['email']))
						mp()->cart_checkout_error('Please enter a valid Email Address.', 'email');

				if (empty($_POST['name']))
						mp()->cart_checkout_error('Please enter your Full Name.', 'name');

				if (empty($_POST['address1']))
						mp()->cart_checkout_error('Please enter your Street Address.', 'address1');

				if (empty($_POST['city']))
						mp()->cart_checkout_error('Please enter your City.', 'city');

				if (($_POST['country'] == 'US' || $_POST['country'] == 'CA') && empty($_POST['state']))
						mp()->cart_checkout_error('Please enter your State/Province/Region.', 'state');

				if (!mp()->is_valid_zip($_POST['zip'], $_POST['country']))
						mp()->cart_checkout_error('Please enter a valid Zip/Postal Code.', 'zip');

				if (empty($_POST['country']) || strlen($_POST['country']) != 2)
						mp()->cart_checkout_error('Please enter your Country.', 'country');

				//for checkout plugins
				do_action('mp_billing_process');

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
						mp()->cart_checkout_error(__('Please select your credit card expiration date.', 'mp'), 'exp');
				}

				if (!isset($_POST['card_code']) || empty($_POST['card_code'])) {
						mp()->cart_checkout_error(__('Please enter your credit card security code', 'mp'), 'card_code');
				}

				if (!isset($_POST['card_num']) || empty($_POST['card_num'])) {
						mp()->cart_checkout_error(__('Please enter your credit card number', 'mp'), 'card_num');
				} else {
						if ($this->_get_card_type($_POST['card_num']) == "") {
								mp()->cart_checkout_error(__('Please enter a valid credit card number', 'mp'), 'card_num');
						}
				}

				if (!mp()->checkout_error) {
						if (
										($this->_get_card_type($_POST['card_num']) == "American Express" && strlen($_POST['card_code']) != 4) ||
										($this->_get_card_type($_POST['card_num']) != "American Express" && strlen($_POST['card_code']) != 3)
						) {
								mp()->cart_checkout_error(__('Please enter a valid credit card security code', 'mp'), 'card_code');
						}
				}

				if (!mp()->checkout_error) {
						$_SESSION['card_num'] = $_POST['card_num'];
						$_SESSION['card_code'] = $_POST['card_code'];
						$_SESSION['exp_month'] = $_POST['exp_month'];
						$_SESSION['exp_year'] = $_POST['exp_year'];

						mp()->generate_order_id();
				}
		}

		function _get_card_type($number) {
				$num_length = strlen($number);

				if ($num_length > 10 && preg_match('/[0-9]+/', $number) >= 1) {
						if ((substr($number, 0, 1) == '4') && (($num_length == 13) || ($num_length == 16))) {
								return "Visa";
						} else if ((substr($number, 0, 1) == '5' && ((substr($number, 1, 1) >= '1') && (substr($number, 1, 1) <= '5'))) && ($num_length == 16)) {
								return "Mastercard";
						} else if (substr($number, 0, 4) == "6011" && ($num_length == 16)) {
								return "Discover Card";
						} else if ((substr($number, 0, 1) == '3' && ((substr($number, 1, 1) == '4') || (substr($number, 1, 1) == '7'))) && ($num_length == 15)) {
								return "American Express";
						}
				}
				return "";
		}

		/**
		 * Return the chosen payment details here for final confirmation. You probably don't need
		 *	to post anything in the form as it should be in your $_SESSION var already.
		 *
		 * @param array $cart. Contains the cart contents for the current blog, global cart if mp()->global_cart is true
		 * @param array $shipping_info. Contains shipping info and email in case you need it
		 */
		function confirm_payment_form($cart, $shipping_info) {
				$meta = get_user_meta($current_user->ID, 'mp_billing_info', true);

				$email = (!empty($_SESSION['mp_billing_info']['email'])) ? $_SESSION['mp_billing_info']['email'] : (!empty($meta['email']) ? $meta['email'] : $_SESSION['mp_shipping_info']['email']);
				$name = (!empty($_SESSION['mp_billing_info']['name'])) ? $_SESSION['mp_billing_info']['name'] : (!empty($meta['name']) ? $meta['name'] : $_SESSION['mp_shipping_info']['name']);
				$address1 = (!empty($_SESSION['mp_billing_info']['address1'])) ? $_SESSION['mp_billing_info']['address1'] : (!empty($meta['address1']) ? $meta['address1'] : $_SESSION['mp_shipping_info']['address1']);
				$address2 = (!empty($_SESSION['mp_billing_info']['address2'])) ? $_SESSION['mp_billing_info']['address2'] : (!empty($meta['address2']) ? $meta['address2'] : $_SESSION['mp_shipping_info']['address2']);
				$city = (!empty($_SESSION['mp_billing_info']['city'])) ? $_SESSION['mp_billing_info']['city'] : (!empty($meta['city']) ? $meta['city'] : $_SESSION['mp_shipping_info']['city']);
				$state = (!empty($_SESSION['mp_billing_info']['state'])) ? $_SESSION['mp_billing_info']['state'] : (!empty($meta['state']) ? $meta['state'] : $_SESSION['mp_shipping_info']['state']);
				$zip = (!empty($_SESSION['mp_billing_info']['zip'])) ? $_SESSION['mp_billing_info']['zip'] : (!empty($meta['zip']) ? $meta['zip'] : $_SESSION['mp_shipping_info']['zip']);
				$country = (!empty($_SESSION['mp_billing_info']['country'])) ? $_SESSION['mp_billing_info']['country'] : (!empty($meta['country']) ? $meta['country'] : $_SESSION['mp_shipping_info']['country']);
				if (!$country)
						$country = mp_get_setting('base_country');
				$phone = (!empty($_SESSION['mp_billing_info']['phone'])) ? $_SESSION['mp_billing_info']['phone'] : (!empty($meta['phone']) ? $meta['phone'] : $_SESSION['mp_shipping_info']['phone']);

				$content = '';

				$content .= '<table class="mp_cart_billing">';
				$content .= '<thead><tr>';
				$content .= '<th>' . __('Billing Information:', 'mp') . '</th>';
				$content .= '<th align="right"><a href="' . mp_checkout_step_url('checkout') . '">' . __('&laquo; Edit', 'mp') . '</a></th>';
				$content .= '</tr></thead>';
				$content .= '<tbody>';
				$content .= '<tr>';
				$content .= '<td align="right">' . __('Email:', 'mp') . '</td><td>';
				$content .= esc_attr($email) . '</td>';
				$content .= '</tr>';
				$content .= '<tr>';
				$content .= '<td align="right">' . __('Full Name:', 'mp') . '</td><td>';
				$content .= esc_attr($name) . '</td>';
				$content .= '</tr>';
				$content .= '<tr>';
				$content .= '<td align="right">' . __('Address:', 'mp') . '</td>';
				$content .= '<td>' . esc_attr($address1) . '</td>';
				$content .= '</tr>';

				if ($address2) {
						$content .= '<tr>';
						$content .= '<td align="right">' . __('Address 2:', 'mp') . '</td>';
						$content .= '<td>' . esc_attr($address2) . '</td>';
						$content .= '</tr>';
				}

				$content .= '<tr>';
				$content .= '<td align="right">' . __('City:', 'mp') . '</td>';
				$content .= '<td>' . esc_attr($city) . '</td>';
				$content .= '</tr>';

				if ($state) {
						$content .= '<tr>';
						$content .= '<td align="right">' . __('State/Province/Region:', 'mp') . '</td>';
						$content .= '<td>' . esc_attr($state) . '</td>';
						$content .= '</tr>';
				}

				$content .= '<tr>';
				$content .= '<td align="right">' . __('Postal/Zip Code:', 'mp') . '</td>';
				$content .= '<td>' . esc_attr($zip) . '</td>';
				$content .= '</tr>';
				$content .= '<tr>';
				$content .= '<td align="right">' . __('Country:', 'mp') . '</td>';
				$content .= '<td>' . mp()->countries[$country] . '</td>';
				$content .= '</tr>';

				if ($phone) {
						$content .= '<tr>';
						$content .= '<td align="right">' . __('Phone Number:', 'mp') . '</td>';
						$content .= '<td>' . esc_attr($phone) . '</td>';
						$content .= '</tr>';
				}

				$content .= '<tr>';
				$content .= '<td align="right">' . __('Payment method:', 'mp') . '</td>';
				$content .= '<td>' . $this->_get_card_type($_SESSION['card_num']) . ' ending in ' . substr($_SESSION['card_num'], strlen($_SESSION['card_num']) - 4, 4) . '</td>';
				$content .= '</tr>';
				$content .= '</tbody>';
				$content .= '</table>';

				return $content;
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
				$timestamp = time();
				$billing_info = $_SESSION['mp_billing_info'];

				$payment = new MP_Gateway_Worker_AuthorizeNet_AIM($this->API_Endpoint, $this->get_setting('delim_data'),	$this->get_setting('delim_char'), $this->get_setting('encap_char'), $this->get_setting('api_credentials->api_user'), $this->get_setting('api_credentials->api_key'), ($this->get_setting('mode') == 'sandbox'));

				$payment->transaction($_SESSION['card_num']);

				$totals = array();
				$coupon_code = mp()->get_coupon_code();
				
				foreach ($cart as $product_id => $variations) {
					foreach ($variations as $variation => $data) {
						$sku = empty($data['SKU']) ? "{$product_id}_{$variation}" : $data['SKU'];
						//total on tax excluded
						$price = mp()->coupon_value_product($coupon_code, $data['price'] * $data['quantity'], $product_id);
						$totals[] = $price;
						//display as tax inclusive
						$payment->addLineItem($sku, substr($data['name'], 0, 31), substr($data['name'] . ' - ' . $data['url'], 0, 254), $data['quantity'], $data['price'], 1);
						$i++;
					}
				}
				$total = array_sum($totals);

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

				// Billing Info
				$payment->setParameter("x_card_code", $_SESSION['card_code']);
				$payment->setParameter("x_exp_date ", $_SESSION['exp_month'] . $_SESSION['exp_year']);
				$payment->setParameter("x_amount", $total);
				$payment->setParameter("x_currency_code", $this->currencyCode);

				// Order Info
				$payment->setParameter("x_description", "Order ID: " . $_SESSION['mp_order']);
				$payment->setParameter("x_invoice_num", $_SESSION['mp_order']);
				$payment->setParameter("x_test_request", false);	// this should NEVER be true, even in sandbox mode
				$payment->setParameter("x_duplicate_window", 30);

				// E-mail
				$payment->setParameter("x_header_email_receipt",	$this->get_setting('header_email_receipt'));
				$payment->setParameter("x_footer_email_receipt", $this->get_setting('footer_email_receipt'));
				$payment->setParameter("x_email_customer", strtoupper($this->get_setting('email_customer')));

				$_names = explode(" ", $billing_info['name']);
				if (isset($_names[0])) {
						$first_name = array_shift($_names);
				} else {
						$first_name = "";
				}

				if (isset($_names[0])) {
						$last_name = join(" ", $_names);
				} else {
						$last_name = "";
				}

				$address = $billing_info['address1'];

				if (!empty($billing_info['address2'])) {
						$address .= "\n" . $billing_info['address2'];
				}

				//Customer Info
				$payment->setParameter("x_first_name", $first_name);
				$payment->setParameter("x_last_name", $last_name);
				$payment->setParameter("x_address", $address);
				$payment->setParameter("x_city", $billing_info['city']);
				$payment->setParameter("x_state", $billing_info['state']);
				$payment->setParameter("x_country", $billing_info['country']);
				$payment->setParameter("x_zip", $billing_info['zip']);
				$payment->setParameter("x_phone", $billing_info['phone']);
				$payment->setParameter("x_email", $billing_info['email']);

				//only add shipping info if set
				if (!mp()->download_only_cart($cart) && isset($shipping_info['name'])) {
						$_names = explode(" ", $shipping_info['name']);
						if (isset($_names[0])) {
								$shipping_first_name = array_shift($_names);
						} else {
								$shipping_first_name = "";
						}

						if (isset($_names[0])) {
								$shipping_last_name = join(" ", $_names);
						} else {
								$shipping_last_name = "";
						}

						$shipping_address = $shipping_info['address1'];

						if (!empty($billing_info['address2'])) {
								$shipping_address .= "\n" . $shipping_info['address2'];
						}

						$payment->setParameter("x_ship_to_first_name", $shipping_first_name);
						$payment->setParameter("x_ship_to_last_name", $shipping_last_name);
						$payment->setParameter("x_ship_to_address", $shipping_address);
						$payment->setParameter("x_ship_to_city", $shipping_info['city']);
						$payment->setParameter("x_ship_to_state", $shipping_info['state']);
						$payment->setParameter("x_ship_to_country", $shipping_info['country']);
						$payment->setParameter("x_ship_to_zip", $shipping_info['zip']);
				}
				
				$payment->setParameter("x_customer_ip", $_SERVER['REMOTE_ADDR']);

				$payment->process();

        if ( $payment->isApproved() ) {
          $paid = ( $payment->isHeldForReview() ) ? false : true;

          $payment_info['gateway_public_name'] = $this->public_name;
          $payment_info['gateway_private_name'] = $this->admin_name;
          $payment_info['method'] = $payment->getMethod();
          $payment_info['status'][$timestamp] = ( $payment->isHeldForReview() ) ? __('held for review', 'mp') : __('paid', 'mp');
          $payment_info['total'] = $total;
          $payment_info['currency'] = $this->currencyCode;
          $payment_info['transaction_id'] = $payment->getTransactionID();

          //succesful payment, create our order now
          $result = $mp->create_order($_SESSION['mp_order'], $cart, $shipping_info, $payment_info, $paid);
				} else {
					$error = $payment->getResponseText();
					mp()->cart_checkout_error(sprintf(__('There was a problem finalizing your purchase. %s Please <a href="%s">go back and try again</a>.', 'mp'), $error, mp_checkout_step_url('checkout')));
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
		 */
		function order_confirmation_msg($content, $order) {
				;
				if ($order->post_status == 'order_received') {
						$content .= '<p>' . sprintf(__('Your credit card payment for this order totaling %s is not yet complete. Here is the latest status:', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total'])) . '</p>';
						$statuses = $order->mp_payment_info['status'];
						krsort($statuses); //sort with latest status at the top
						$status = reset($statuses);
						$timestamp = key($statuses);
						$content .= '<p><strong>' . mp()->format_date($timestamp) . ':</strong> ' . esc_html($status) . '</p>';
				} else {
						$content .= '<p>' . sprintf(__('Your credit card payment for this order totaling %s is complete. The credit card transaction number is <strong>%s</strong>.', 'mp'), mp()->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']), $order->mp_payment_info['transaction_id']) . '</p>';
				}
				return $content;
		}

		/**
		 * Runs before page load incase you need to run any scripts before loading the success message page
		 */
		function order_confirmation($order) {
				
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
			'desc' => __('Authorize.net AIM is a customizable payment processing solution that gives the merchant control over all the steps in processing a transaction. An SSL certificate is required to use this gateway. USD is the only currency supported by this gateway.', 'mp'),
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
			'desc' => __('You must login to Authorize.net merchant dashboard to obtain the API login ID and API transaction key. <a target="_blank" href="http://www.authorize.net/support/merchant/wwhelp/wwhimpl/js/html/wwhelp.htm">Instructions &raquo;</a>', 'mp'),
		));
		
		if ( $creds instanceof WPMUDEV_Field ) {
			$creds->add_field('text', array(
				'name' => 'api_user',
				'label' => array('text' => __('Login ID', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$creds->add_field('text', array(
				'name' => 'api_key',
				'label' => array('text' => __('Transaction Key', 'mp')),
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
		$metabox->add_field('section', array(
			'name' => 'section_advanced_settings',
			'title' => __('Advanced Settings', 'mp'),
			'subtitle' => __('Optional settings to control advanced options', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][delim_char]',
			'label' => array('text' => __('Delimeter Character', 'mp')),
			'desc' => __('Authorize.net default is ",". Otherwise, get this from your credit card processor. If the transactions are not going through, this character is most likely wrong.', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][encap_char]',
			'label' => array('text' => __('Encapsulation Character', 'mp')),
			'desc' => __('Authorize.net default is blank. Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong.', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
			'label' => array('text' => __('Email Customer (on success)', 'mp')),
			'default_value' => 'yes',
			'options' => array(
				'yes' => __('Yes', 'mp'),
				'no' => __('No', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][header_email_receipt]',
			'label' => array('text' => __('Customer Receipt Email Header', 'mp')),
			'desc' => __('This text will appear as the header of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
				'value' => 'yes',
				'action' => 'show'
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][footer_email_receipt]',
			'label' => array('text' => __('Customer Receipt Email Footer', 'mp')),
			'desc' => __('This text will appear as the footer of the email receipt sent to the customer.', 'mp'),
			'conditional' => array(
				'name' => 'gateways[' . $this->plugin_name . '][email_customer]',
				'value' => 'yes',
				'action' => 'show'
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][md5_hash]',
			'label' => array('text' => __('Security: MD5 Hash', 'mp')),
			'desc' => __('The payment gateway generated MD5 hash value that can be used to authenticate the transaction response. Not needed because responses are returned using an SSL connection.', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'gateways[' . $this->plugin_name . '][delim_data]',
			'label' => array('text' => __('Delim Data', 'mp')),
			'desc' => __('Request a delimited response from the payment gateway.', 'mp'),
			'default_value' => 'yes',
			'options' => array(
				'yes' => __('Yes', 'mp'),
				'no' => __('No', 'mp'),
			),
		));
		$metabox->add_field('text', array(
			'name' => 'gateways[' . $this->plugin_name . '][custom_api]',
			'label' => array('text' => __('Custom API URL', 'mp')),
			'desc' => __('Many other gateways have Authorize.net API emulators. To use one of these gateways input their API post url here.', 'mp'),
			'validation' => array(
				'url' => true,
			),
		));
	}
}

if (!class_exists('MP_Gateway_Worker_AuthorizeNet_AIM')) {

		class MP_Gateway_Worker_AuthorizeNet_AIM {

				var $login;
				var $transkey;
				var $params = array();
				var $results = array();
				var $line_items = array();
				var $approved = false;
				var $declined = false;
				var $held_for_review = false;
				var $error = true;
				var $method = "";
				var $fields;
				var $response;
				var $instances = 0;

				function __construct($url, $delim_data, $delim_char, $encap_char, $gw_username, $gw_tran_key, $gw_test_mode) {
						if ($this->instances == 0) {
								$this->url = $url;

								$this->params['x_delim_data'] = ($delim_data == 'yes') ? 'TRUE' : 'FALSE';
								$this->params['x_delim_char'] = $delim_char;
								$this->params['x_encap_char'] = $encap_char;
								$this->params['x_relay_response'] = "FALSE";
								$this->params['x_url'] = "FALSE";
								$this->params['x_version'] = "3.1";
								$this->params['x_method'] = "CC";
								$this->params['x_type'] = "AUTH_CAPTURE";
								$this->params['x_login'] = $gw_username;
								$this->params['x_tran_key'] = $gw_tran_key;
								$this->params['x_test_request'] = $gw_test_mode;

								$this->instances++;
						} else {
								return false;
						}
				}

				function transaction($cardnum) {
						$this->params['x_card_num'] = trim($cardnum);
				}
				
				function clean_string( $str, $length ) {
					//replace encoded characters with their non-encoded versions
					$search = array('&#8230;', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8226;', '&#8211;', '&#8212;');
					$replace = array('...', "'", "'", '"', '"', '•', '-', '-');
					$str = str_replace($find, $replace, $str);
					
					//remove all other entities
					$str = preg_replace("/&.{0,}?;/", '', $str);
					
					//shorten length
					$str = substr($str, 0, $length);
					
					return $str;
				}
				
				function addLineItem($id, $name, $description, $quantity, $price, $taxable = 0) {
					$id = $this->clean_string($id, 31);
					$name = $this->clean_string($name, 31);
					$description = $this->clean_string($description, 255);
					$this->line_items[] = "{$id}<|>{$name}<|>{$description}<|>{$quantity}<|>{$price}<|>{$taxable}";
				}

				function process($retries = 1) {
						;

						$this->_prepareParameters();
						$query_string = rtrim($this->fields, "&");

						$count = 0;
						while ($count < $retries) {
								$args['user-agent'] = "MarketPress/{mp()->version}: http://premium.wpmudev.org/project/e-commerce | Authorize.net AIM Plugin/{mp()->version}";
								$args['body'] = $query_string;
								$args['sslverify'] = false;
								$args['timeout'] = 30;

								//use built in WP http class to work with most server setups
								$response = wp_remote_post($this->url, $args);

								if (is_array($response) && isset($response['body'])) {
										$this->response = $response['body'];
								} else {
										$this->response = "";
										$this->error = true;
										return;
								}

								$this->parseResults();

                switch ( $this->getResultResponseFull() ) {
	                case 'Approved' :
	                	$this->approved = true;
                    $this->declined = false;
                    $this->error = false;
                    $this->method = $this->getMethod();
									break(2);
									
									case 'Declined' :
										$this->approved = false;
                    $this->declined = true;
                    $this->error = false;
                  break(2);
                  
                  case 'HeldForReview' :
                  	$this->approved = true;
                    $this->declined = false;
                    $this->error = false;
                    $this->held_for_review = true;
                  break(2);
                    
                  case 'Error' :
                  	$count ++;
                  break;
                }
						}
				}

				function parseResults() {
						$this->results = explode($this->params['x_delim_char'], $this->response);
				}

				function setParameter($param, $value) {
						$param = trim($param);
						$value = trim($value);
						$this->params[$param] = $value;
				}

				function setTransactionType($type) {
						$this->params['x_type'] = strtoupper(trim($type));
				}

				function _prepareParameters() {
						foreach ($this->params as $key => $value) {
								$this->fields .= "$key=" . urlencode($value) . "&";
						}
						for ($i = 0; $i < count($this->line_items); $i++) {
								$this->fields .= "x_line_item={$this->line_items[$i]}&";
						}
				}

				function getMethod() {
						if (isset($this->results[51])) {
								return str_replace($this->params['x_encap_char'], '', $this->results[51]);
						}
						return "";
				}

				function getGatewayResponse() {
						return str_replace($this->params['x_encap_char'], '', $this->results[0]);
				}

				function getResultResponseFull() {
						$response = array("", "Approved", "Declined", "Error", "HeldForReview");
						return $response[str_replace($this->params['x_encap_char'], '', $this->results[0])];
				}

				function isApproved() {
						return $this->approved;
				}

				function isDeclined() {
						return $this->declined;
				}

				function isError() {
						return $this->error;
				}
				
        function isHeldForReview() {
	      		return $this->held_for_review;
        }				

				function getResponseText() {
						return $this->results[3];
						$strip = array($this->params['x_delim_char'], $this->params['x_encap_char'], '|', ',');
						return str_replace($strip, '', $this->results[3]);
				}

				function getAuthCode() {
						return str_replace($this->params['x_encap_char'], '', $this->results[4]);
				}

				function getAVSResponse() {
						return str_replace($this->params['x_encap_char'], '', $this->results[5]);
				}

				function getTransactionID() {
						return str_replace($this->params['x_encap_char'], '', $this->results[6]);
				}

		}

}

//register payment gateway plugin
mp_register_gateway_plugin('MP_Gateway_AuthorizeNet_AIM', 'authorizenet-aim', __('Authorize.net AIM Checkout', 'mp'));