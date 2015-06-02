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
	 * Refers to the current checkout step
	 *
	 * @since 3.0
	 * @access protected
	 * @var string
	 */
	protected $_step = null;

	/**
	 * Refers to the current checkout step number
	 *
	 * @since 3.0
	 * @access protected
	 * @var int
	 */
	protected $_stepnum = 1;

	/**
	 * Refers to the checkout sections
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_sections = array();

	/**
	 * Refers to the checkout errors
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
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
		/**
		 * Filter the checkout sections array
		 *
		 * @since 3.0
		 * @param array The current sections array.
		 */
		$cart				 = mp_cart();
		$is_download_only	 = $cart->is_download_only();
		$this->_sections	 = apply_filters( 'mp_checkout/sections_array', array(
			'login-register'			 => __( 'Login/Register', 'mp' ),
			'billing-shipping-address'	 => (!mp()->download_only_cart( mp_cart() ) || mp_get_setting( 'tax->downloadable_address' ) ) ? __( 'Billing/Shipping Address', 'mp' ) : __( 'Billing', 'mp' ),
			'shipping'					 => __( 'Shipping Method', 'mp' ),
			'order-review-payment'		 => __( 'Review Order/Payment', 'mp' ),
		) );

		if ( !$this->_need_shipping_step() ) {
			unset( $this->_sections[ 'shipping' ] );
		}

		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_filter( 'mp_cart/after_cart_html', array( &$this, 'payment_form' ), 10, 3 );

		// Update checkout data
		add_action( 'wp_ajax_mp_update_checkout_data', array( &$this, 'ajax_update_checkout_data' ) );
		add_action( 'wp_ajax_nopriv_mp_update_checkout_data', array( &$this, 'ajax_update_checkout_data' ) );

		// Process checkout
		add_action( 'wp_ajax_mp_process_checkout', array( &$this, 'ajax_process_checkout' ) );
		add_action( 'wp_ajax_nopriv_mp_process_checkout', array( &$this, 'ajax_process_checkout' ) );

		// Maybe process checkout confirm
		add_action( 'wp', array( &$this, 'maybe_process_checkout_confirm' ) );
		add_action( 'wp', array( &$this, 'maybe_process_checkout' ) );
	}

	/**
	 * Determine if the shipping step is needed
	 *
	 * @since 3.0
	 * @access protected
	 * @return bool
	 */
	protected function _need_shipping_step() {
		$blog_ids			 = mp_cart()->get_blog_ids();
		$need_shipping_step	 = false;

		while ( 1 ) {
			if ( mp_cart()->is_global ) {
				$blog_id = array_shift( $blog_ids );
				mp_cart()->set_id( $blog_id );
			}

			if ( 'calculated' == mp_get_setting( 'shipping->method' ) ) {
				$need_shipping_step = true;
			}

			if ( (mp_cart()->is_global && false === current( $blog_ids )) || !mp_cart()->is_global ) {
				mp_cart()->reset_id();
				break;
			}
		}

		return $need_shipping_step;
	}

	/**
	 * Update shipping section
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _update_shipping_section() {

		$data = (array) mp_get_post_value( 'billing', array() );

		foreach ( $data as $key => $value ) {
			$value = trim( $value );
			mp_update_session_value( "mp_billing_info->{$key}", $value );
		}

		$enable_shipping_address = mp_get_post_value( 'enable_shipping_address' );
		mp_update_session_value( 'enable_shipping_address', $enable_shipping_address );

		if ( $enable_shipping_address ) {
			$data = (array) mp_get_post_value( 'shipping', array() );

			foreach ( $data as $key => $value ) {
				$value = trim( $value );
				mp_update_session_value( "mp_shipping_info->{$key}", $value );
			}
		} else {
			mp_update_session_value( 'mp_shipping_info', mp_get_session_value( 'mp_billing_info' ) );
		}
	}

	/**
	 * Update order review/payment section
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _update_order_review_payment_section() {
		$shipping_method = mp_get_setting( 'shipping->method' );
		$selected		 = (array) mp_get_post_value( 'shipping_method' );

		foreach ( $selected as $blog_id => $method ) {
			if ( 'calculated' == $shipping_method ) {
				list( $shipping_option, $shipping_sub_option ) = explode( '->', $method );

				if ( mp_cart()->is_global ) {
					mp_update_session_value( "mp_shipping_info->shipping_option->{$blog_id}", $shipping_option );
					mp_update_session_value( "mp_shipping_info->shipping_sub_option->{$blog_id}", $shipping_sub_option );
				} else {
					mp_update_session_value( 'mp_shipping_info->shipping_option', $shipping_option );
					mp_update_session_value( 'mp_shipping_info->shipping_sub_option', $shipping_sub_option );
				}
			} else {
				if ( mp_cart()->is_global ) {
					mp_update_session_value( "mp_shipping_info->shipping_option->{$blog_id}", $shipping_method );
					mp_update_session_value( "mp_shipping_info->shipping_sub_option->{$blog_id}", '' );
				} else {
					mp_update_session_value( 'mp_shipping_info->shipping_option', $shipping_method );
					mp_update_session_value( 'mp_shipping_info->shipping_sub_option', '' );
				}
			}
		}
	}

	/**
	 * Display address fields
	 *
	 * @since 3.0
	 * @access public
	 * @param string $type Either billing or shipping.
	 * @param bool $value_only Optional, whether the fields should display their values only. Defaults to false.
	 * @return string
	 */
	public function address_fields( $type, $value_only = false ) {
		$country = mp_get_user_address_part( 'country', $type );

		// Country list
		$allowed_countries = explode( ',', mp_get_setting( 'shipping->allowed_countries', '' ) );

		if ( mp_all_countries_allowed() ) {
			$all_countries		 = mp()->countries;
			$allowed_countries	 = array_keys( $all_countries );
		}

		$countries = array();
		
		foreach ( $allowed_countries as $_country ) {
			$countries[ $_country ] = mp()->countries[ $_country ];
		}

		// State/zip fields
		$state_zip_fields	 = array();
		$states				 = mp_get_states( $country );
		$state_zip_fields[]	 = array(
			'type'		 => 'select',
			'label'		 => __( 'State/Province', 'mp' ),
			'name'		 => $this->field_name( 'state', $type ),
			'options'	 => $states,
			'hidden'	 => ( empty( $states ) ),
			'value'		 => mp_get_user_address_part( 'state', $type ),
			'atts'		 => array(
				'class' => 'mp_select2_search',
			),
			'validation' => array(
				'required' => true,
			),
		);

		$state_zip_fields[] = array(
			'type'		 => 'text',
			'label'		 => mp_get_setting( 'zip_label' ),
			'name'		 => $this->field_name( 'zip', $type ),
			'value'		 => mp_get_user_address_part( 'zip', $type ),
			'hidden'	 => array_key_exists( $country, mp()->countries_no_postcode ),
			'validation' => array(
				'required' => true,
			),
		);

		$address_fields = array(
			array(
				'type'		 => 'complex',
				'label'		 => __( 'Name', 'mp' ),
				'validation' => array(
					'required' => true,
				),
				'subfields'	 => array(
					array(
						'type'	 => 'text',
						'label'	 => __( 'First', 'mp' ),
						'name'	 => $this->field_name( 'first_name', $type ),
						'value'	 => mp_get_user_address_part( 'first_name', $type ),
					),
					array(
						'type'	 => 'text',
						'label'	 => __( 'Last', 'mp' ),
						'name'	 => $this->field_name( 'last_name', $type ),
						'value'	 => mp_get_user_address_part( 'last_name', $type ),
					),
				),
			),
			array(
				'type'		 => 'text',
				'label'		 => __( 'Email Address', 'mp' ),
				'name'		 => $this->field_name( 'email', $type ),
				'value'		 => mp_get_user_address_part( 'email', $type ),
				'validation' => array(
					'required'	 => true,
					'email'		 => true,
				),
			),
			array(
				'type'	 => 'text',
				'label'	 => __( 'Company', 'mp' ),
				'name'	 => $this->field_name( 'company_name', $type ),
				'value'	 => mp_get_user_address_part( 'company_name', $type ),
			),
			array(
				'type'		 => 'text',
				'label'		 => __( 'Address Line 1', 'mp' ),
				'name'		 => $this->field_name( 'address1', $type ),
				'value'		 => mp_get_user_address_part( 'address1', $type ),
				'atts'		 => array(
					'placeholder' => __( 'Street address, P.O. box, company name, c/o', 'mp' ),
				),
				'validation' => array(
					'required' => true,
				),
			),
			array(
				'type'	 => 'text',
				'label'	 => __( 'Address Line 2', 'mp' ),
				'name'	 => $this->field_name( 'address2', $type ),
				'value'	 => mp_get_user_address_part( 'address2', $type ),
				'atts'	 => array(
					'placeholder' => __( 'Apartment, suite, unit, building, floor, etc', 'mp' ),
				),
			),
			array(
				'type'		 => 'text',
				'label'		 => __( 'Town/City', 'mp' ),
				'name'		 => $this->field_name( 'city', $type ),
				'value'		 => mp_get_user_address_part( 'city', $type ),
				'validation' => array(
					'required' => true,
				),
			),
			array(
				'type'		 => 'complex',
				'subfields'	 => $state_zip_fields,
			),
			array(
				'type'		 => 'select',
				'label'		 => __( 'Country', 'mp' ),
				'name'		 => $this->field_name( 'country', $type ),
				'options'	 => $countries,
				'value'		 => $country,
				'atts'		 => array(
					'class' => 'mp_select2_search',
				),
			),
			array(
				'type'	 => 'text',
				'label'	 => __( 'Phone', 'mp' ),
				'name'	 => $this->field_name( 'phone', $type ),
				'value'	 => mp_get_user_address_part( 'phone', $type ),
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
			$field[ 'value_only' ] = $value_only;

			if ( $value_only ) {
				$field[ 'label' ] = false;
			}

			$html .= '<div class="mp-checkout-form-row"' . (( mp_arr_get_value( 'hidden', $field ) ) ? ' style="display:none"' : '') . '>' . $this->form_field( $field ) . '</div>';
		}

		/**
		 * Filter address field html
		 *
		 * @since 3.0
		 * @param string The current html.
		 * @param string Either billing or shipping.
		 */
		return apply_filters( 'mp_checkout/address_fields', $html, $type );
	}

	/**
	 * Add checkout error
	 *
	 * @since 3.0
	 * @access public
	 * @param string $msg The error message.
	 * @param string $context The context of the error message.
	 */
	public function add_error( $msg, $context = 'general' ) {
		$msg = str_replace( '"', '\"', $msg ); //prevent double quotes from causing errors.

		if ( !isset( $this->_errors[ $context ] ) ) {
			$this->_errors[ $context ] = array();
		}

		$this->_errors[ $context ][] = $msg;
	}

	/**
	 * Get checkout errors
	 *
	 * @since 3.0
	 * @access public
	 * @param string $context Optional, the error context. Defaults to "general".
	 * @return array
	 */
	public function get_errors( $context = 'general' ) {
		$errors = mp_arr_get_value( $context, $this->_errors );

		/**
		 * Filter the error string
		 *
		 * @since 3.0
		 * @param array $errors The error array.
		 * @param string $context The error context.
		 */
		return (array) apply_filters( 'mp_checkout/get_errors', $errors, $context );
	}

	/**
	 * Process checkout
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_process_checkout, wp_ajax_nopriv_mp_process_checkout
	 */
	public function ajax_process_checkout() {
		if ( $payment_method = mp_get_post_value( 'payment_method' ) ) {
			$cart			 = mp_cart();
			$billing_info	 = mp_get_user_address( 'billing' );
			$shipping_info	 = mp_get_user_address( 'shipping' );

			/**
			 * For gateways to tie into and process payment
			 *
			 * @since 3.0
			 * @param MP_Cart $cart An MP_Cart object.
			 * @param array $billing_info An array of buyer billing info.
			 * @param array $shipping_info An array of buyer shipping info.
			 */
			do_action( 'mp_process_payment_' . $payment_method, $cart, $billing_info, $shipping_info );

			if ( $this->has_errors() ) {
				// There are errors - bail
				wp_send_json_error( array(
					'errors' => mp_arr_get_value( 'general', $this->_errors )
				) );
			}

			$order = wp_cache_get( 'order_object', 'mp' );
			wp_send_json_success( array( 'redirect_url' => $order->tracking_url( false ) ) );
		}

		wp_send_json_error( array(
			'errors' => array(
				'general' => __( 'An unknown error occurred. Please try again.', 'mp' ),
			),
		) );
	}

	/**
	 * Update checkout data
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_checkout_data, wp_ajax_nopriv_mp_update_checkout_data
	 */
	public function ajax_update_checkout_data() {
		$this->_update_shipping_section();
		$this->_update_order_review_payment_section();

		$sections = array(
			'mp-checkout-section-shipping'				 => $this->section_shipping(),
			'mp-checkout-section-order-review-payment'	 => $this->section_order_review_payment(),
		);

		wp_send_json_success( $sections );
	}

	/**
	 * Display checkout form
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 * 		Optional, an array of arguments.
	 *
	 * 		@type bool $echo Whether to echo or return. Defaults to echo.
	 * }
	 */
	public function display( $args = array() ) {
		$args = array_replace_recursive( array(
			'echo' => true,
		), $args );

		extract( $args );

		if ( !mp_cart()->has_items() ) {
			return sprintf( __( '<h3>Oops!</h3><p>Looks like you haven\'t added anything your cart. <a href="%s">Let\'s go shopping!</a></p>', 'mp' ), mp_store_page_url( 'products', false ) ) . '</p>';
		}

		$html = '
			<noscript>' . __( 'Javascript is required in order to checkout. Please enable Javascript in your browser and then refresh this page.', 'mp' ) . '</noscript>
			<form id="mp-checkout" class="clearfix' . (( get_query_var( 'mp_confirm_order_step' ) ) ? ' last-step' : '') . '" method="post" style="display:none" novalidate>' .
		wp_nonce_field( 'mp_process_checkout', 'mp_checkout_nonce', true, false );

		/* Loop through each section to determine if a particular section has errors.
		  If so, set that section as the current section */
		$visible_section = null;
		foreach ( $this->_sections as $section => $heading_text ) {
			if ( ( $this->has_errors( $section ) ) ) {
				$visible_section = $section;
				break;
			}
		}

		foreach ( $this->_sections as $section => $heading_text ) {
			$method		 = 'section_' . str_replace( '-', '_', $section );
			$this->_step = $section;

			if ( method_exists( $this, $method ) ) {
				$tmp_html = $this->$method();

				if ( empty( $tmp_html ) ) {
					continue;
				}

				$id		 = 'mp-checkout-section-' . $section;
				$classes = array( 'mp-checkout-section' );

				if ( !is_null( $visible_section ) ) {
					if ( $section == $visible_section ) {
						$classes[] = 'current';
					}
				} elseif ( get_query_var( 'mp_confirm_order_step' ) ) {
					if ( 'order-review-payment' == $section ) {
						$classes[] = 'current';
					}
				} elseif ( 1 === $this->_stepnum ) {
					$classes[] = 'current';
				}

				$html .= '
				<div id="' . $id . '" class="' . implode( ' ', $classes ) . '">';

				if ( !mp_doing_ajax( 'mp_update_checkout_data' ) ) {
					$link = ( get_query_var( 'mp_confirm_order_step' ) ) ? mp_store_page_url( 'checkout', false ) : 'javascript:;';
					$html .= $this->section_heading( $heading_text, $link, true );
				}

				$html .= '
					<div class="mp-checkout-section-errors' . (( $this->has_errors( $section ) ) ? ' show' : '') . '">' . $this->print_errors( $section, false ) . '</div>
					<div class="mp-checkout-section-content">' . $tmp_html . '</div>
				</div>';

				$this->_stepnum ++;
			}
		}

		$html .= '
			</form>';

		/**
		 * Filter the checkout form html
		 *
		 * @since 3.0
		 * @param string $html The current html.
		 * @param array $this->_sections An array of sections to display.
		 */
		$html = apply_filters( 'mp_checkout/display', $html, $this->_sections );

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
		if ( !mp_is_shop_page( 'checkout' ) ) {
			return;
		}

		wp_register_script( 'jquery-validate', mp_plugin_url( 'ui/js/jquery.validate.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'jquery-validate-methods', mp_plugin_url( 'ui/js/jquery.validate.methods.min.js' ), array( 'jquery', 'jquery-validate' ), MP_VERSION, true );
		wp_register_script( 'jquery-payment', mp_plugin_url( 'ui/js/jquery.payment.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-checkout', mp_plugin_url( 'ui/js/mp-checkout.js' ), array( 'jquery-payment', 'jquery-validate-methods' ), MP_VERSION, true );

		wp_localize_script( 'mp-checkout', 'mp_checkout_i18n', array(
			'cc_num'		 => __( 'Please enter a valid credit card number', 'mp' ),
			'cc_exp'		 => __( 'Please enter a valid card expiration', 'mp' ),
			'cc_cvc'		 => __( ' Please enter a valid card security code', 'mp' ),
			'cc_fullname'	 => __( 'Please enter a valid first and last name', 'mp' ),
			'errors'		 => __( '<h4>Oops! We found %d %s in the form below.</h4><p>Fields that have errors are highlighted in <span style="color:#d60303">red</span> below. Entering into a field will reveal the actual error that occurred.</p>', 'mp' ),
			'error_plural'	 => __( 'errors', 'mp' ),
			'error_singular' => __( 'error', 'mp' ),
		) );
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
		if ( !is_null( $prefix ) ) {
			return $prefix . '[' . $name . ']';
		}

		return $name;
	}

	/**
	 * Build form field html based upon values from an array
	 *
	 * @since 3.0
	 * @param array $field {
	 * 		An array of field properties
	 *
	 * 		@type string $type The type of field (e.g. text, password, textarea, etc)
	 * 		@type array $validation An array of validation rules. See http://jqueryvalidation.org/documentation/
	 * 		@type string $label The label of the form field
	 * 		@type string $name The name attribute of the field.
	 * 		@type array $atts An array of custom attributes.
	 * 		@type string $value The value of the field.
	 * 		@type array $subfields For complex fields, an array of subfields.
	 * 		@param array $options Required, if a select field.
	 * 		@param bool $value_only Whether the field should just display the value or the enter the field.
	 * }
	 * @return string
	 */
	public function form_field( $field ) {
		$atts		 = $html		 = $required	 = '';

		// Display label?
		if ( ($label = mp_arr_get_value( 'label', $field )) && 'checkbox' != mp_arr_get_value( 'type', $field, '' ) ) {
			$required = ( mp_arr_get_value( 'validation->required', $field ) ) ? ' <span class="mp-field-required">*</span>' : '';
			$html .= '
				<label>' . mp_arr_get_value( 'label', $field, '' ) . $required . '</label>';
		}

		// Convert validation arg into attributes
		foreach ( (array) mp_arr_get_value( 'validation', $field, array() ) as $key => $val ) {
			if ( is_bool( $val ) ) {
				$val = ( $val ) ? 'true' : 'false';
			}

			$val = mp_quote_it( $val );
			$atts .= " data-rule-{$key}={$val}";
		}

		// Get attributes
		$attributes = (array) mp_arr_get_value( 'atts', $field, array() );

		// Add ID attribute
		if ( false === mp_arr_get_value( 'id', $attributes ) ) {
			$attributes[ 'id' ] = 'mp-checkout-field-' . uniqid( true );
		}

		// Convert atts arg into attributes
		foreach ( $attributes as $key => $val ) {
			$val = mp_quote_it( $val );
			$atts .= " {$key}={$val}";
		}

		switch ( mp_arr_get_value( 'type', $field, '' ) ) {
			case 'text' :
			case 'password' :
			case 'hidden' :
			case 'checkbox' :
				if ( mp_arr_get_value( 'value_only', $field ) ) {
					$html .= mp_arr_get_value( 'value', $field, '' );
				} else {
					$html .= '
					<input name="' . mp_arr_get_value( 'name', $field, '' ) . '" type="' . mp_arr_get_value( 'type', $field, '' ) . '" value="' . mp_arr_get_value( 'value', $field, '' ) . '"' . $atts . ' />';

					if ( 'checkbox' == mp_arr_get_value( 'type', $field, '' ) ) {
						$html .= '<label for="' . $attributes[ 'id' ] . '">' . mp_arr_get_value( 'label', $field, '' ) . $required . '</label>';
					}
				}
				break;

			case 'select' :
				if ( mp_arr_get_value( 'value_only', $field ) ) {
					$html .= mp_arr_get_value( 'value', $field );
				} else {
					$atts .= ' autocomplete="off"';
					$html .= '
					<select name="' . mp_arr_get_value( 'name', $field, '' ) . '" ' . $atts . '>';

					$options = (array) mp_arr_get_value( 'options', $field, array() );
					foreach ( $options as $value => $label ) {
						$html .= '
						<option value="' . esc_attr( $value ) . '" ' . selected( $value, mp_arr_get_value( 'value', $field ), false ) . '>' . esc_attr( $label ) . '</option>';
					}

					$html .= '
					</select>';
				}
				break;

			case 'complex' :
				$html .= '
				<div class="mp-checkout-input-complex clearfix">';

				foreach ( (array) mp_arr_get_value( 'subfields', $field, array() ) as $subfield ) {
					$subfield[ 'value_only' ] = mp_arr_get_value( 'value_only', $field );

					$top_label	 = true;
					if ( (($label		 = mp_arr_get_value( 'label', $subfield )) && mp_arr_get_value( 'label', $field )) || $subfield[ 'value_only' ] ) {
						$top_label = false;
						unset( $subfield[ 'label' ] );
					}

					if ( $validation = mp_arr_get_value( 'validation', $field ) ) {
						$subfield[ 'validation' ] = (array) $validation;
					}

					$html .= '
					<div class="mp-checkout-column"' . (( mp_arr_get_value( 'hidden', $subfield ) ) ? ' style="display:none"' : '') . '>' .
					$this->form_field( $subfield );

					if ( !$top_label && !$subfield[ 'value_only' ] ) {
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
	 * Get the previous/next step html link
	 *
	 * @since 3.0
	 * @access public
	 * @param string $what Either "prev" or "next".
	 * @return string
	 */
	public function step_link( $what ) {
		$hash	 = $this->url_hash( $what );
		$text	 = '';
		$classes = array( 'mp-button', "mp-button-checkout-{$what}-step" );

		switch ( $what ) {
			case 'prev' :
				if ( 1 === $this->_stepnum ) {
					return false;
				}

				$text		 = __( '&laquo; Previous Step', 'mp' );
				$classes[]	 = 'mp-button-secondary';
				return '<a class="' . implode( ' ', $classes ) . '" href="' . $hash . '">' . $text . '</a>';
				break;

			case 'next' :
				$text		 = __( 'Next Step &raquo;', 'mp' );
				$classes[]	 = 'mp-button-medium';
				return '<button class="' . implode( ' ', $classes ) . '" type="submit">' . $text . '</button>';
				break;
		}

		return false;
	}

	/**
	 * Maybe process order
	 *
	 * @since 3.0
	 * @access public
	 * @action wp
	 */
	public function maybe_process_checkout() {
		if ( wp_verify_nonce( mp_get_post_value( 'mp_checkout_nonce' ), 'mp_process_checkout' ) ) {
			$payment_method	 = mp_get_post_value( 'payment_method' );
			$cart			 = mp_cart();
			$billing_info	 = mp_get_user_address( 'billing' );
			$shipping_info	 = mp_get_user_address( 'shipping' );

			// Save payment method to session
			mp_update_session_value( 'mp_payment_method', $payment_method );

			/**
			 * For gateways to tie into and process payment
			 *
			 * @since 3.0
			 * @param MP_Cart $cart An MP_Cart object.
			 * @param array $billing_info An array of buyer billing info.
			 * @param array $shipping_info An array of buyer shipping info.
			 */
			do_action( 'mp_process_payment_' . $payment_method, $cart, $billing_info, $shipping_info );
		}
	}

	/**
	 * Maybe process order confirmation
	 *
	 * @since 3.0
	 * @access public
	 * @action wp
	 */
	public function maybe_process_checkout_confirm() {
		if ( get_query_var( 'mp_confirm_order_step' ) ) {
			/**
			 * For gateways to tie into before page loads
			 *
			 * @since 3.0
			 */
			do_action( 'mp_checkout/confirm_order/' . mp_get_session_value( 'mp_payment_method', '' ) );
		}
	}

	/**
	 * Print errors (if applicable)
	 *
	 * @since 3.0
	 * @access public
	 * @param string $context Optional, the error context. Defaults to "general".
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function print_errors( $context = 'general', $echo = true ) {
		$error_string = '';

		if ( $errors = $this->get_errors( $context ) ) {
			$error_string .= '
				<h4>' . __( 'Oops! An error occurred while processing your payment.', 'mp' ) . '</h4>
				<ul>';

			foreach ( $errors as $error ) {
				$error_string .= $error;
			}

			$error_string .= '
				</ul>';
		}

		if ( $echo ) {
			echo $error_string;
		} else {
			return $error_string;
		}
	}

	/**
	 * Display payment form
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/after_cart_html
	 */
	public function payment_form( $html, $cart, $display_args ) {
		if ( $cart->is_editable || $display_args[ 'view' ] == 'order-status' ) {
			// Cart isn't editable - bail
			return $html;
		}

		/**
		 * Filter the payment form heading text
		 *
		 * @since 3.0
		 * @param string
		 */
		$heading = '<h3>' . apply_filters( 'mp_checkout/payment_form/heading_text', __( 'Payment', 'mp' ) ) . '</h3>';

		$html .= '
			<div id="mp-checkout-payment-form">' .
		$heading . '
				<div id="mp-checkout-payment-form-errors"></div>';

		if ( get_query_var( 'mp_confirm_order_step' ) ) {
			/**
			 * For gateways to tie into and display payment confirmation info
			 *
			 * @since 3.0
			 */
			$form = apply_filters( 'mp_checkout/confirm_order_html/' . mp_get_session_value( 'mp_payment_method', '' ), '' );
		} else {
			/**
			 * For gateways to tie into and display payment forms
			 *
			 * @since 3.0
			 * @param string
			 */
			$form = apply_filters( 'mp_checkout_payment_form', '' );

			if ( empty( $form ) ) {
				$html .= wpautop( __( 'There are no available gateways to process this payment.', 'mp' ) );
			} else {
				$html .= '<div id="mp-payment-options-list">' . mp_list_payment_options( false ) . '</div>';
			}
		}

		$html .= $form;
		$html .= '
			</div>';

		return $html;
	}

	/**
	 * Check if there are any errors
	 *
	 * @since 3.0
	 * @access public
	 * @param string $context Optional, the context of the errors. Defaults to "general".
	 * @return bool
	 */
	public function has_errors( $context = 'general' ) {
		return ( mp_arr_get_value( $context, $this->_errors ) ) ? true : false;
	}

	/**
	 * Display the billing/shipping address section
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function section_billing_shipping_address() {
		$shipping_addr			 = (array) mp_get_user_address( 'shipping' );
		$billing_addr			 = (array) mp_get_user_address( 'billing' );
		$enable_shipping_address = ( $shipping_addr !== $billing_addr );

		$html = '
			<div class="clearfix">
				<div id="mp-checkout-column-billing-info" class="mp-checkout-column' . (( $enable_shipping_address ) ? '' : ' fullwidth') . '">
					<h3>' . __( 'Billing', 'mp' ) . '</h3>' .
		$this->address_fields( 'billing' ) . '';

		$cart				 = mp_cart();
		$is_download_only	 = $cart->is_download_only();

		if ( !mp()->download_only_cart( mp_cart() ) || mp_get_setting( 'tax->downloadable_address' ) ) {
			$html .= '
					<div class="mp-checkout-form-row mp-checkbox-row">
						<label><input type="checkbox" name="enable_shipping_address" value="1" autocomplete="off" ' . checked( true, $enable_shipping_address, false ) . ' /> <span>' . __( 'Shipping address different than billing?', 'mp' ) . '</span></label>
					</div>
				';
		}

		$html .= '
			</div>
				<div id="mp-checkout-column-shipping-info" class="mp-checkout-column"' . (( $enable_shipping_address ) ? '' : ' style="display:none"') . '>
					<h3>' . __( 'Shipping', 'mp' ) . '</h3>' .
		$this->address_fields( 'shipping' ) . '';

		if ( mp_get_setting( 'special_instructions' ) == '1' ) {
			$html .= '<div class="mp-checkout-form-row">
					<label>' . __( 'Special Instructions', 'mp' ) . '</label>	
				    <textarea name="shipping[special_instructions]"></textarea>
				  </div>';
		}

		$html .= '

				</div>
			</div>
			<div class="clearfix mp-checkout-buttons">' .
		$this->step_link( 'prev' ) .
		$this->step_link( 'next' ) . '
			</div>';

		return $html;
	}

	/**
	 * Display a section heading
	 *
	 * @since 3.0
	 * @access public
	 * @param string $text Heading text.
	 * @param string $link Optional, the link url for the heading.
	 * @param bool $step Optional, whether to show the current step num next to the heading text.
	 * @return string
	 */
	public function section_heading( $text, $link = false, $step = false ) {
		$html = '
			<h2 class="mp-checkout-section-heading clearfix">';

		if ( $step ) {
			$html .= '
				<span class="mp-checkout-step-num">' . $this->_stepnum . '</span>';
		}

		if ( false !== $link ) {
			$html .= '
				<a href="' . $link . '" class="mp-checkout-section-heading-link">' . $text . '</a>';
		} else {
			$html .= '
				<span class="mp-checkout-section-heading-link">' . $text . '</span>';
		}

		$html .= '
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
		if ( !is_user_logged_in() && !MP_HIDE_LOGIN_OPTION ) {
			$html = wp_nonce_field( 'mp-login-nonce', 'mp_login_nonce', true, false ) . '
				<div class="clearfix">
					<div class="mp-checkout-column">
						<h4>' . __( 'Have an account?', 'mp' ) . '</h4>
						<p>' . __( 'Sign in to speed up the checkout process.', 'mp' ) . '</p>
						<div class="mp-checkout-form-row">
							<label for="mp-checkout-email">' . __( 'E-Mail Address/Username', 'mp' ) . '</label>
							<input type="text" name="mp_login_email" />
						</div>
						<div class="mp-checkout-form-row">
							<label for="mp-checkout-password">' . __( 'Password', 'mp' ) . '</label>
							<input type="password" name="mp_login_password" />
						</div>
						<button id="mp-button-checkout-login" type="submit" class="mp-button mp-button-medium">' . __( 'Login', 'mp' ) . '</button>
					</div>
					<div class="mp-checkout-column">
						<h4>' . __( 'First-time customer?', 'mp' ) . '</h4>
						<p>' . __( 'Proceed to checkout and you\'ll have an opportunity to create an account at the end.', 'mp' ) . '</p>
						<p><button type="submit" class="mp-button mp-button-medium mp-button-checkout-next-step">' . __( 'Continue as Guest', 'mp' ) . '</button></p>
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
	public function section_order_review_payment() {

		$cart				 = mp_cart();
		$is_download_only	 = $cart->is_download_only();

		$html = '
			<div class="clearfix">
				<div class="mp-checkout-column">
					<h3>' . __( 'Billing Address', 'mp' ) . '</h3>' .
		$this->address_fields( 'billing', true ) . '
				</div>';

		if ( !mp()->download_only_cart( mp_cart() ) || mp_get_setting( 'tax->downloadable_address' ) ) {
			$html .= '
				<div class="mp-checkout-column">
					<h3>' . __( 'Shipping Address', 'mp' ) . '</h3>' .
			$this->address_fields( 'shipping', true ) . '
				</div>';
		}

		$html .= '
			</div>
			<h3>' . __( 'Cart', 'mp' ) . '</h3>' .
		mp_cart()->display( array(
			'editable' => false
		) );

		/**
		 * Filter the section payment html
		 *
		 * @since 3.0
		 * @param string The current html.
		 */
		return apply_filters( 'mp_checkout/order_review', $html );
	}

	/**
	 * Display the shipping section
	 *
	 * @since 3.0
	 * @access public
	 */
	public function section_shipping() {
		if ( mp_cart()->is_download_only() || 0 == mp_cart()->shipping_weight() ) {
			return false;
		}

		$blog_ids	 = mp_cart()->get_blog_ids();
		$html		 = '';

		while ( 1 ) {
			if ( mp_cart()->is_global ) {
				$force	 = true;
				$blog_id = array_shift( $blog_ids );
				mp_cart()->set_id( $blog_id );
				MP_Shipping_API::load_active_plugins( true );
			}

			$active_plugins	 = MP_Shipping_API::get_active_plugins();
			$shipping_method = mp_get_setting( 'shipping->method' );

			if ( 'calculated' == $shipping_method ) {
				if ( mp_cart()->is_global ) {
					$html .= '
						<h3>' . get_option( 'blogname' ) . '</h3>';
				}

				foreach ( $active_plugins as $plugin ) {
					$html .= '
						<div class="mp-shipping-method">
							<h4>' . $plugin->public_name . '</h4>';

					$html .= mp_list_plugin_shipping_options( $plugin );
					$html .= '
						</div>';
				}
			}

			if ( (mp_cart()->is_global && false === current( $blog_ids )) || !mp_cart()->is_global ) {
				mp_cart()->reset_id();
				break;
			}
		}

		$html .= '
						<div class="clearfix mp-checkout-buttons">' .
		$this->step_link( 'prev' ) .
		$this->step_link( 'next' ) . '
						</div>';


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

	/**
	 * Get current/next url hash
	 *
	 * @since 3.0
	 * @access public
	 * @param string $what Either "prev" or "next".
	 * @return string
	 */
	public function url_hash( $what ) {
		$key = array_search( $this->_step, $this->_sections );

		switch ( $what ) {
			case 'next' :
				$slug = mp_arr_get_value( ($key + 1 ), $this->_sections, '' );
				break;

			case 'prev' :
				$slug = mp_arr_get_value( ($key - 1 ), $this->_sections, '' );
				break;
		}

		return '#' . $slug;
	}

}

MP_Checkout::get_instance();
