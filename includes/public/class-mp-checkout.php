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
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
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
		$country = mp_get_user_address_part('country', $type);
		$address_fields = array(
			array(
				'type' => 'complex',
				'label' => __('Name', 'mp'),
				'validation' => array(
					'required' => true,
				),
				'subfields' => array(
					array(
						'type' => 'text',
						'label' => __('First', 'mp'),
						'name' => $this->field_name( 'first_name', $type ),
						'value' => mp_get_user_address_part('first_name', $type),
					),
					array(
						'type' => 'text',
						'label' => __('Last', 'mp'),
						'name' => $this->field_name( 'last_name', $type ),
						'value' => mp_get_user_address_part('last_name', $type),
					),
				),
			),
			array(
				'type' => 'text',
				'label' => __('Company', 'mp'),
				'name' => $this->field_name( 'company_name', $type ),
				'value' => mp_get_user_address_part('company_name', $type),
			),
			array(
				'type' => 'text',
				'label' => __('Address Line 1', 'mp'),
				'name' => $this->field_name( 'address1', $type ),
				'value' => mp_get_user_address_part('address1', $type),
				'atts' => array(
					'placeholder' => __('Street address, P.O. box, company name, c/o', 'mp'),
				),
				'validation' => array(
					'required' => true,
				),
			),
			array(
				'type' => 'text',
				'label' => __('Address Line 2', 'mp'),
				'name' => $this->field_name( 'address2', $type ),
				'value' => mp_get_user_address_part('address2', $type),
				'atts' => array(
					'placeholder' => __('Apartment, suite, unit, building, floor, etc', 'mp'),
				),
			),
			array(
				'type' => 'text',
				'label' => __('Town/City', 'mp'),
				'name' => $this->field_name( 'city', $type ),
				'value' => mp_get_user_address_part('city', $type),
				'validation' => array(
					'required' => true,
				),
			),
			array(
				'type' => 'complex',
				'subfields' => array(
					array(
						'type' => 'text',
						'label' => __('State/Province', 'mp'),
						'name' => $this->field_name( 'state', $type ),
						'value' => mp_get_user_address_part('state', $type),
						'validation' => array(
							'required' => true,
						),
					),
					array(
						'type' => 'text',
						'label' => mp_get_setting('zip_label'),
						'name' => $this->field_name( 'zip', $type ),
						'value' => mp_get_user_address_part('zip', $type),
						'validation' => array(
							'required' => true,
						),
					),
				),
			),
			array(
				'type' => 'text',
				'label' => __('Phone', 'mp'),
				'name' => $this->field_name( 'phone', $type ),
				'value' => mp_get_user_address_part('phone', $type),
			),
		);
		
		/**
		 * Filter the address fields array
		 *
		 * @since 3.0
		 * @param array $address_fields The current address fields.
		 * @param string $type Either billing or shipping.
		 */
		$address_fields = (array) apply_filters( 'mp_checkout/address_fields_array', $address_fields, $type );
		
		$html = '';
		foreach ( $address_fields as $field ) {
			$html .= '<div class="mp-checkout-form-row">' . $this->form_field( $field ) . '</div>';
		}
		
		/*$html = '
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
			</div>';*/
			
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
		$args = array_replace_recursive( array(
			'echo' => true,
		), $args );
		
		extract( $args );
		
		/**
		 * Filter the checkout sections array
		 *
		 * @since 3.0
		 * @param array The current sections array.
		 */
		$sections = apply_filters( 'mp_checkout/sections_array', array(
			'login-register',
			'billing-shipping-address',
			'shipping',
			'payment',
			'order-review',
		) );
		
		$html = '
			<form id="mp-checkout" class="clearfix" method="post" novalidate>';
		
		foreach ( $sections as $section ) {
			$method = 'section_' . str_replace( '-', '_', $section );
			
			if ( method_exists( $this, $method ) ) {
				$tmp_html = $this->$method();
				
				if ( empty( $tmp_html ) ) {
					continue;
				}
				
				$html .= '
				<div id="mp-checkout-section-' . $section . '" class="mp-checkout-section">' . $tmp_html . '</div>';
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
		if ( ! mp_is_shop_page( 'checkout' ) ) {
			return;
		}
		
		wp_register_script( 'jquery-cycle', mp_plugin_url( 'ui/js/jquery.cycle.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'jquery-validate', mp_plugin_url( 'ui/js/jquery.validate.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'jquery-validate-methods', mp_plugin_url( 'ui/js/jquery.validate.methods.min.js' ), array( 'jquery', 'jquery-validate' ), MP_VERSION, true );
		wp_register_script( 'jquery-payment', mp_plugin_url( 'ui/js/jquery.payment.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-checkout', mp_plugin_url( 'ui/js/mp-checkout.js' ), array( 'jquery-payment', 'jquery-validate-methods', 'jquery-cycle' ), MP_VERSION, true );
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
	 * Build form field html based upon values from an array
	 *
	 * @since 3.0
	 * @param array $field {
	 *		An array of field properties
	 *
	 *		@type string $type The type of field (e.g. text, password, textarea, etc)
	 *		@type array $validation An array of validation rules. See http://jqueryvalidation.org/documentation/
	 *		@type string $label The label of the form field
	 *		@type string $name The name attribute of the field.
	 *		@type array $atts An array of custom attributes.
	 *		@type string $value The value of the field.
	 *		@type array $subfields For complex fields, an array of subfields.
	 * }
	 * @return string
	 */
	public function form_field( $field ) {
		$atts = $html = '';	
		
		// Display label?
		if ( $label = mp_arr_get_value( 'label', $field ) ) {
			$required = ( mp_arr_get_value( 'validation->required', $field ) ) ? ' <span class="mp-field-required">*</span>' : '';
			$html .= '
				<label>' . mp_arr_get_value( 'label', $field, '' ) . $required . '</label>';
		}
		
		// Convert validation arg into attributes
		foreach ( (array) mp_arr_get_value( 'validation', $field, array() ) as $key => $val ) {
			if ( is_bool( $val ) || is_int( $val ) ) {
				$atts .= " $key";
			} else {
				$val = mp_quote_it( $val );
				$atts .= " {$key}={$val}";
			}
		}
		
		// Convert atts arg into attributes
		foreach ( (array) mp_arr_get_value( 'atts', $field, array() ) as $key => $val ) {
			$val = mp_quote_it( $val );
			$atts .= " {$key}={$val}";
		}
	
		switch ( mp_arr_get_value( 'type', $field, '' ) ) {
			case 'text' :
			case 'password' :
			case 'hidden' :
				$html .= '
				<input name="' . mp_arr_get_value( 'name', $field, '' ) . '" type="' . mp_arr_get_value( 'type', $field, '' ) . '" value="' . mp_arr_get_value( 'value', $field, '' ) . '"' . $atts . ' />';
			break;
			
			case 'complex' :
				$html .= '
				<div class="mp-checkout-input-complex clearfix">';
				
				foreach ( (array) mp_arr_get_value( 'subfields', $field, array() ) as $subfield ) {
					$top_label = true;
					if ( ($label = mp_arr_get_value( 'label', $subfield )) && mp_arr_get_value( 'label', $field ) ) {
						$top_label = false;
						unset( $subfield['label'] );
					}
					
					if ( $validation = mp_arr_get_value( 'validation', $field ) ) {
						$subfield['validation'] = (array) $validation;
					}

					$html .= '
					<div class="mp-checkout-column">' .
						$this->form_field( $subfield );
					
					if ( ! $top_label ) {
						$html .= '
						<span>' . $label . '</span>';
					}
					
					$html .= '
					</div>';
				}
				
				$html .= '
				</div>';
			break;
		}
		
		return $html;
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
						<label><input type="checkbox" name="enable_shipping_address" value="1" autocomplete="off" ' . checked(1, wp_get_current_user()->get('mp_enable_shipping_address'), false) . ' /> <span>' . __('Shipping address different than billing?', 'mp') . '</span></label>
					</div>
				</div>
				<div id="mp-checkout-column-shipping-info" class="mp-checkout-column">
					<h3>' . __('Shipping', 'mp') . '</h3>' .
					$this->address_fields('shipping') . '
				</div>
			</div>
			<p><a class="mp-button mp-button-medium mp-button-checkout-next-step" href="#">' . __( 'Next Step', 'mp' ) . '</a></p>';
			
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
			$html = wp_nonce_field( 'mp-login-nonce', 'mp_login_nonce' ) .
				$this->section_heading( __('Login/Register', 'mp' ), true ) . '
				<div class="clearfix">
					<div class="mp-checkout-column" style="padding-right:25px">
						<h4>' . __( 'Have an account?', 'mp') . '</h4>
						<p>' . __( 'Sign in to speed up the checkout process.', 'mp' ) . '</p>
						<div class="mp-checkout-form-row">
							<label for="mp-checkout-email">' . __('E-Mail Address', 'mp') . '</label>
							<input type="text" name="mp_login_email" />
						</div>
						<div class="mp-checkout-form-row">
							<label for="mp-checkout-password">' . __( 'Password', 'mp' ) . '</label>
							<input type="password" name="mp_login_password" />
						</div>
						<button id="mp-button-checkout-login" type="submit" class="mp-button mp-button-medium">' . __( 'Login', 'mp' ) . '</label>
					</div>
					<div class="mp-checkout-column" style="padding-left:25px">
						<h4>' . __( 'First-time customer?', 'mp') . '</h4>
						<p>' . __( 'Proceed to checkout and you\'ll have an opportunity to create an account at the end.', 'mp' ) . '</p>
						<p><a class="mp-button mp-button-medium mp-button-checkout-next-step" href="#step2">' . __( 'Continue as Guest', 'mp' ) . '</a></p>
					</div>
				</div>';
		}
		/**
		 * Filter the section login html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters( 'mp_checkout/section_login', $html );
	}

	/**
	 * Display the order review section
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function section_order_review() {
		$html = '' .
			$this->section_heading(__('Review', 'mp'), true) .
			mp_cart()->display(array('editable' => false));
		
		/**
		 * Filter the section payment html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters('mp_checkout/order_review', $html);
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
		 * @param string
		 */
		$form = apply_filters('mp_checkout_payment_form', '');
		
		if ( empty($form) ) {
			$form = wpautop(__('There are no available gateways to process this payment.', 'mp'));
		} else {
			$html .= mp_list_payment_options(false);
		}
		
		$html .= $form;
		
		/**
		 * Filter the section payment html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */		
		$html = apply_filters('mp_checkout/section_payment', $html);
		
		return $html;
	}
	
	/**
	 * Display the shipping section
	 *
	 * @since 3.0
	 * @access public
	 */
	public function section_shipping() {
		$active_plugins = MP_Shipping_API::get_active_plugins();
		$shipping_method = mp_get_setting('shipping->method');
		
		$html = '' .
			$this->section_heading(__('Shipping', 'mp'), true);
		
		
		switch ( $shipping_method ) {
			case 'calculated' :
				foreach ( $active_plugins as $plugin ) {
					$html .= '
						<div class="mp-shipping-method">
							<h4>' . $plugin->public_name . '</h4>';
					
					$html .= mp_list_plugin_shipping_options( $plugin );
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
		 * @param string $html The current html.
		 * @param string $shipping_method The selected shipping method per settings (e.g. calculated, flat-rate, etc)
		 * @param array $active_plugins The currently active shipping plugins.
		 */
		return apply_filters( 'mp_checkout/section_shipping', $html, $shipping_method, $active_plugins );		
	}
}

MP_Checkout::get_instance();