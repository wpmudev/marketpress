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
		$html = '
			<div class="mp-checkout-form-row">
				<label>' . __('Country', 'mp') . '</label>' .
				$this->country_dropdown($type) . '
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Name', 'mp') . '</label>
				<div class="mp-checkout-input-complex clearfix">
					<label class="mp-checkout-column">
						<input type="text" name="' . $this->field_name('first_name', $type) . '" /><br />
						<span>' . __('First', 'mp') . '</span>
					</label>
					<label class="mp-checkout-column">
						<input type="text" name="' . $this->field_name('last_name', $type) . '" /><br />
						<span>' . __('Last', 'mp') . '</span>
					</label>
				</div>
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Company', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('company', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Address Line 1', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('address1', $type) . '" placeholder="' . __('Street address, P.O. box, company name, c/o', 'mp') . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Address Line 2', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('address2', $type) . '" placeholder="' . __('Apartment, suite, unit, building, floor, etc', 'mp') . '" />
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Town/City', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('city', $type) . '" />
			</div>
			<div class="mp-checkout-form-row">
				<div class="mp-checkout-input-complex clearfix">
					<div class="mp-checkout-column">
						 <label>' . __('State/Province', 'mp') . '</label>' .
						$this->province_field('US', 'billing') . '
					</div>
					<div class="mp-checkout-column">
						 <label>' . __('Post/Zip Code', 'mp') . '</label>
						 <input type="text" name="' . $this->field_name('zip', $type) . '" />
					</div>
				</div>
			</div>
			<div class="mp-checkout-form-row">
				<label>' . __('Phone', 'mp') . '</label>
				<input type="text" name="' . $this->field_name('phone', $type) . '" />
			</div>';
			
		if ( 'billing' == $type ) {
			$html .= '
			<div class="mp-checkout-form-row">
				<label><input type="checkbox" name="copy_to_shipping" value="1" /> ' . __('Shipping address different than billing?', 'mp') . '</label>
			</div>'; 
		}
		
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
			
		foreach ( mp_get_setting('shipping->allowed_countries', array()) as $code ) {
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
			'shipping-method',
			'payment-method',
			'order-review',
		));
		
		$html = '
			<div id="mp-checkout" class="clearfix">';
		
		foreach ( $sections as $section ) {
			$method = 'section_' . str_replace('-', '_', $section);
			
			if ( method_exists($this, $method) ) {
				$html .= '
				<div id="mp-checkout-section-' . $section . '" class="mp-checkout-section">' . $this->$method() . '</div>';	
			}
		}
		
		$html .= '
			</div>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
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
				<div id="mp-checkout-column-billing-address" class="mp-checkout-column">
					<h3>' . __('Billing Address', 'mp') . '</h3>' .
					$this->address_fields('billing') . '
				</div>
				<div id="mp-checkout-column-shipping-address" class="mp-checkout-column">
					<h3>' . __('Shipping Address', 'mp') . '</h3>' .
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
				<form action="' . admin_url('admin-ajax.php?action=mp_checkout_login') . '" method="post">
					<div class="mp-checkout-form-row">
						<label for="mp-checkout-email">' . __('E-Mail Address', 'mp') . '</label>
						<input type="text" name="email" />
					</div>
					<div class="mp-checkout-form-row">
						<label for="mp-checkout-password">' . __('Password', 'mp') . '</label>
						<input type="password" name="password" />
					</div>
					<button type="submit" class="mp-button">' . __('Login', 'mp') . '</label>
				</form>';
		}
		
		/**
		 * Filter the section login html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters('mp_checkout/section_login', $html);
	}
}

MP_Checkout::get_instance();