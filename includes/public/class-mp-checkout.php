<?php

class MP_Checkout {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Refers to the current checkout step number
	 *
	 * @since 3.0
	 * @access protected
	 * @var int
	 */
	protected $_step = 1;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Checkout();
		}
		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
	}
	
	/**
	 * Display address fields
	 *
	 * @since 3.0
	 * @access public
	 * @param string $type Either billing or shipping.
	 * @return string
	 */
	public function address_fields( $type ) {
		$user = wp_get_current_user();
		$meta = $user->get("mp_{$type}_info");
		$country = mp_get_user_address_part('country', $type);
		
		$html = '
			<div class="mp-checkout-form-row">
				<label>' . __('Country', 'mp') . '</label>' .
				$this->country_dropdown($type, $country) . '
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Name', 'mp') . '</label>
				<div class="mp-checkout-input-complex clearfix">
					<label class="mp-checkout-column">
						<input type="text" name="' . $this->field_name('first_name', $type) . '" value="' . mp_get_user_address_part('first_name', $type) . '" /><br />
						<span>' . __('First', 'mp') . '</span>
					</label>
					<label class="mp-checkout-column">
						<input type="text" name="' . $this->field_name('last_name', $type) . '" value="' . mp_get_user_address_part('last_name', $type) . '" /><br />
						<span>' . __('Last', 'mp') . '</span>
					</label>
				</div>
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Company', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('company', $type) . '" value="' . mp_get_user_address_part('company', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Address Line 1', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('address1', $type) . '" placeholder="' . __('Street address, P.O. box, company name, c/o', 'mp') . '" value="' . mp_get_user_address_part('address1', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Address Line 2', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('address2', $type) . '" placeholder="' . __('Apartment, suite, unit, building, floor, etc', 'mp') . '" value="' . mp_get_user_address_part('address2', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Town/City', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('city', $type) . '" value="' . mp_get_user_address_part('city', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<div class="mp-checkout-input-complex clearfix">
					<div class="mp-checkout-column">
						 <label>' . __('State/Province', 'mp') . '</label>' .
						$this->province_field($country, $type, mp_get_user_address_part('state', $type)) . '
					</div>
					<div class="mp-checkout-column">
						 <label>' . __('Post/Zip Code', 'mp') . '</label>
						 <input type="text" name="' . $this->field_name('zip', $type) . '" value="' . mp_get_user_address_part('zip', $type) . '" />
					</div>
				</div>
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Phone', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('phone', $type) . '" value="' . mp_get_user_address_part('phone', $type) . '" />
			</div>';
			
		/**
		 * Filter address field html
		 *
		 * @since 3.0
		 * @param string The current html.
		 * @param string Either billing or shipping.
		 */
		return apply_filters('mp_checkout/address_fields', $html, $type);
	}
	
	/**
	 * Display country dropdown
	 *
	 * @since 3.0
	 * @access public
	 * @param string $type Either billing or shipping.
	 * @param string $selected The selected country.
	 * @return string
	 */
	public function country_dropdown( $type, $selected = null ) {
		$html = '
			<select class="mp_select2_search" name="' . $this->field_name('country', $type) . '">';
		
		$countries = explode(',', mp_get_setting('shipping->allowed_countries', ''));
		
		foreach ( $countries as $code ) {
			$html .= '
				<option value="' . $code . '"' . selected($selected, $code, false) . '>' . esc_attr(mp()->countries[$code]) . '</option>';
		}
		
		$html .= '
			</select>';
			
		return $html;
	}
	
	/**
	 * Display checkout form
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 *		Optional, an array of arguments.
	 *
	 *		@type bool $echo Whether to echo or return. Defaults to echo.
	 * }
	 */
	public function display( $args = array() ) {
		extract($args);
		
		/**
		 * Filter the checkout sections array
		 *
		 * @since 3.0
		 * @param array The current sections array.
		 */
		$sections = apply_filters('mp_checkout/sections_array', array(
			'login-register',
			'billing-shipping-address',
			'shipping',
			'payment',
			'order-review',
		));
		
		$html = '
			<form id="mp-checkout" class="clearfix" method="post">';
		
		foreach ( $sections as $section ) {
			$method = 'section_' . str_replace('-', '_', $section);
			
			if ( method_exists($this, $method) ) {
				$html .= '
				<div id="mp-checkout-section-' . $section . '" class="mp-checkout-section">' . $this->$method() . '</div>';	
			}
		}
		
		$html .= '
			</form>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	/**
	 * Enqueue scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_scripts() {
		if ( mp_is_shop_page('checkout') ) {
			wp_enqueue_script('mp-checkout', mp_plugin_url('ui/js/mp-checkout.js'), array('jquery'), MP_VERSION, true);
		}
	}
	
	/**
	 * Get checkout field name
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The field name.
	 * @param string $prefix Optional, characters to prefix before the field name.
	 */
	public function field_name( $name, $prefix = null ) {
		if ( ! is_null($prefix) ) {
			return $prefix . '[' . $name . ']';
		}
		
		return $name;
	}
	
	/**
	 * Display state/province field
	 *
	 * @since 3.0
	 * @access public
	 * @param string $country A country.
	 * @param string $type Either billing or shipping.
	 * @param string $selected Optional, the selected country.
	 */
	public function province_field( $country, $type, $selected = null ) {
		if ( mp_doing_ajax() && ($_country = mp_get_post_value('country')) ) {
			$country = $_country;
		}

		$list = false;
		switch ( $country ) {
			case 'US' :
				$list = mp()->usa_states;
			break;
			
			case 'CA' :
				$list = mp()->canadian_provinces;
			break;
			
			case 'AU' :
				$list = mp()->australian_states;
			break;
		}	

		/**
		 * Filter the state/province list
		 *
		 * @since 3.0
		 * @param array $list The current state/province list.
		 * @param string $country The current country.
		 */
		$list = apply_filters('mp_checkout/province_field_list', $list, $country);
		
		$content = '';
		if ( false !== $list ) {			
			$content .= '<select class="mp_select2_search" name="' . $this->field_name('state', $type) . '">';
			foreach ( $list as $abbr => $label ) {
				$content .= '<option value="' . $abbr . '"' . selected($selected, $abbr, false) . '>' . esc_attr($label) . '</option>';
			}
			$content .= '</select>';
		} else {
			$content .= '<input name="state" type="text" value="' . esc_attr($selected) . '" />';
		}

		/**
		 * Filter the province field content
		 *
		 * @since 3.0
		 * @param string $content The current content.
		 * @param string $country The current country.
		 * @param string $selected The selected state/province.
		 */
		$content = apply_filters('mp_checkout/province_field', $content, $country, $selected);

		if ( mp_doing_ajax() ) {
			die($content);
		} else {
			return $content;
		}
	}
	
	/**
	 * Display the billing/shipping address section
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function section_billing_shipping_address() {
		$html = '' .
			$this->section_heading(__('Billing/Shipping Info', 'mp'), true) . '
			<div class="clearfix">
				<div id="mp-checkout-column-billing-info" class="mp-checkout-column">
					<h3>' . __('Billing', 'mp') . '</h3>' .
					$this->address_fields('billing') . '
					<div class="mp-checkout-form-row mp-checkbox-row">
						<label><input type="checkbox" name="enable_shipping_address" value="1" ' . checked(1, wp_get_current_user()->get('mp_enable_shipping_address'), false) . ' /> <span>' . __('Shipping address different than billing?', 'mp') . '</span></label>
					</div>
				</div>
				<div id="mp-checkout-column-shipping-info" class="mp-checkout-column">
					<h3>' . __('Shipping', 'mp') . '</h3>' .
					$this->address_fields('shipping') . '
				</div>
			</div>';
			
		return $html;
	}
	
	/**
	 * Display a section heading
	 *
	 * @since 3.0
	 * @access public
	 * @param string $text Heading text.
	 * @param bool $step Optional, whether to show the current step num next to the heading text.
	 * @return string
	 */
	public function section_heading( $text, $step = false ) {
		$html = '
			<h2 class="mp-checkout-section-heading clearfix">';
		
		if ( $step ) {
			$html .= '
				<span class="mp-checkout-step-num">' . $this->_step . '</span>';
			$this->_step ++;
		}
		
		$html .= $text . '
			</h2>';
			
		return $html;
	}
	
	/**
	 * Display the login/register section
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function section_login_register() {
		$html = '';
		
		if ( ! is_user_logged_in() && ! MP_HIDE_LOGIN_OPTION ) {
			$html = '' .
				$this->section_heading(__('Login/Register', 'mp'), true) . '
				<div class="mp-checkout-form-row">
					<label for="mp-checkout-email">' . __('E-Mail Address', 'mp') . '</label>
					<input type="text" name="email" />
				</div>
				<div class="mp-checkout-form-row">
					<label for="mp-checkout-password">' . __('Password', 'mp') . '</label>
					<input type="password" name="password" />
				</div>
				<button type="submit" class="mp-button">' . __('Login', 'mp') . '</label>';
		}
		
		/**
		 * Filter the section login html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters('mp_checkout/section_login', $html);
	}
	
	/**
	 * Display the payment section
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function section_payment() {
		$html = '' .
			$this->section_heading(__('Payment', 'mp'), true);
		
		/**
		 * Filter the section payment html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters('mp_checkout/section_payment', $html);
	}
	
	/**
	 * Display the shipping section
	 *
	 * @since 3.0
	 * @access public
	 */
	public function section_shipping() {
		$active_plugins = MP_Shipping_API::get_active_plugins();
		$items = mp_cart()->get_items();
		$address1 = mp_get_user_address_part('address1', 'shipping');
		$address2 = mp_get_user_address_part('address2', 'shipping');
		$city = mp_get_user_address_part('city', 'shipping');
		$state = mp_get_user_address_part('state', 'shipping');
		$zip = mp_get_user_address_part('zip', 'shipping');
		$country = mp_get_user_address_part('country', 'shipping');
		
		$html = '' .
			$this->section_heading(__('Shipping', 'mp'), true);
		
		
		switch ( mp_get_setting('shipping->method') ) {
			case 'calculated' :
				foreach ( $active_plugins as $plugin ) {
					$html .= '
						<div class="mp-shipping-method">
							<h4>' . $plugin->public_name . '</h4>';
					
					$options = $plugin->shipping_options($items, $address1, $address2, $city, $state, $zip, $country);
					foreach ( $options as $method => $label ) {
						$input_id = 'mp-shipping-option-' . $plugin->plugin_name . '-' . sanitize_title($method);
						$html .= '
							<label class="mp-shipping-option-label" for="' . $input_id . '">
								<input id="' . $input_id . '" type="radio" name="shipping_method" value="' . $plugin->plugin_name . '->' . $method . '" />
								<span></span>' . $label . '
							</label>';
					}
					$html .= '
						</div>';
				}
			break;
			
			default :
			break;
		}

		
		/**
		 * Filter the shipping section html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters('mp_checkout/section_shipping', $html);		
	}
}

MP_Checkout::get_instance();