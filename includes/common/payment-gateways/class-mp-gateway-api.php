<?php
if ( !class_exists( 'MP_Gateway_API' ) ) :

	class MP_Gateway_API {

		//build of the gateway plugin
		var $build					 = null;
		//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name				 = '';
		//name of your gateway, for the admin side.
		var $admin_name				 = '';
		//public name of your gateway, for lists and such.
		var $public_name				 = '';
		//url for an image for your checkout method. Displayed on method form
		var $method_img_url			 = '';
		//url for an submit button image for your checkout method. Displayed on checkout form if set
		var $method_button_img_url	 = '';
		//whether or not ssl is needed for checkout page
		var $force_ssl				 = false;
		//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
		var $ipn_url;
		//always contains the return url after processing checkout on a payment processor's site
		var $return_url;
		//always contains the cancel url after canceling checkout on a payment processor's site
		var $cancel_url;
		//only required for global capable gateways. The maximum stores that can checkout at once
		var $max_stores				 = 1;
		//if the gateway uses the order confirmation step during checkout (e.g. PayPal)
		var $use_confirmation_step	 = false;

		/**
		 * Refers to the registered gateways set by add_gateway()
		 *
		 * @since 3.0
		 * @access private
		 */
		private static $_gateways = array();

		/**
		 * Refers to the active gateways
		 *
		 * @since 3.0
		 * @access private
		 */
		private static $_active_gateways = array();

		/**
		 * Registers a gateway
		 *
		 * @since 3.0
		 * @access public
		 */
		public static function register_gateway( $plugin_name, $args ) {
			self::$_gateways[ $plugin_name ] = $args;
		}

		/**
		 * Gets all of the registered gateways
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function get_gateways() {
			/**
			 * Filter the list of registered gateways
			 *
			 * @since 3.0
			 * @param array An array of registered gateways.
			 */
			return apply_filters( 'mp_gateway_api/get_gateways', self::$_gateways );
		}

		/**
		 * Loads the active gateways
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function load_active_gateways() {

			if ( ! empty( self::$_active_gateways ) ) {
				// We already loaded the active gateways. No need to continue.
				return;
			}

			if (is_multisite() && mp_get_network_setting( 'global_cart' ) ) {
				//if this is global cart, we will need to get from network admin
				$gateways = mp_get_network_setting( 'global_gateway' );
			} else {
				$gateways = mp_get_setting( 'gateways' );
			}

			foreach ( self::get_gateways() as $code => $plugin ) {
				$class = $plugin[0];

				if ( ! class_exists( $class ) ) {
					continue;
				}

				//in global mode, we only load one gateway for all
				if (is_multisite() && mp_get_network_setting( 'global_cart' ) && $code == $gateways ) {
					self::$_active_gateways[ $code ] = new $class;
				} else {
					if ( is_admin() && ( 'store-settings-payments' == mp_get_get_value( 'page' ) || 'store-setup-wizard' == mp_get_get_value( 'page' ) ) ) {
						// load all gateways when in admin or quicksetup
						self::$_active_gateways[ $code ] = new $class;
					} elseif ( mp_arr_get_value( "allowed->{$code}", $gateways ) ) {
						self::$_active_gateways[ $code ] = new $class;
					}
				}
			}

			if ( 'store-settings-payments' !== mp_get_get_value( 'page' ) ) {
				self::$_active_gateways['free_orders'] = new MP_Gateway_FREE_Orders();
			}
		}

		/**
		 * Gets the active gateways
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function get_active_gateways() {
			return self::$_active_gateways;
		}

		/**
		 * Generats the appropriate metabox ID for the gateway
		 *
		 * @since 3.0
		 * @access private
		 */
		public final function generate_metabox_id() {
			if ( is_network_admin() ) {
				return 'mp-network-settings-gateway-' . strtolower( $this->plugin_name );
			} else {
				return 'mp-settings-gateway-' . strtolower( $this->plugin_name );
			}
		}

		/**
		 * Setup gateway - use instead of __construct()
		 *
		 * @since 3.0
		 * @access public
		 */
		public function on_creation() {

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
			return '';
		}

		/**
		 * Check if gateway is set to force SSL and, if so, redirect to SSL checkout
		 *
		 * @since 3.0
		 * @access public
		 * @action wp
		 * @uses $post
		 */
		public function maybe_force_ssl() {
			global $post;

			if ( $this->force_ssl && !is_ssl() && mp_is_shop_page( 'checkout' ) ) {
				$url = str_replace( 'http://', 'https://', get_permalink( $post->ID ) );
				wp_redirect( $url );
				exit;
			}
		}

		/**
		 * Maybe print checkout scripts
		 *
		 * @since 3.0
		 * @access public
		 * @action wp_footer
		 */
		final function maybe_print_checkout_scripts() {
			if ( mp_is_shop_page( 'checkout' ) ) {
				$this->print_checkout_scripts();
			}
		}

		/**
		 * Print checkout scripts
		 *
		 * @since 3.0
		 * @access public
		 */
		function print_checkout_scripts() {
			?>
			<script type="text/javascript">
				( function( $ ) {
					$( document ).on( 'mp_checkout_process_<?php echo $this->plugin_name; ?>', function( e, $form ) {
						marketpress.loadingOverlay( 'show' );
						$form.get( 0 ).submit();

					} );
				}( jQuery ) );
			</script>
		<?php
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
			wp_die( __( "You must override the process_payment() method in your {$this->admin_name} payment gateway plugin!", 'mp' ) );
		}

		/**
		 * Process order confirmation before page loads (e.g. verify callback data, etc)
		 *
		 * @since 3.0
		 * @access public
		 * @action mp_checkout/confirm_order/{plugin_name}
		 */
		public function process_confirm_order() {

		}

		/**
		 * Use to handle any payment returns to the ipn_url. Do not display anything here. If you encounter errors
		 *  return the proper headers. Exits after.
		 */
		function process_ipn_return() {

		}

		/**
		 * Get the default CC payment form
		 *
		 * @since 3.0
		 * @access protected
		 * @param bool $use_names Optional, whether to give the input names or not. Defaults to true.
		 * @return string
		 */
		protected function _cc_default_form( $use_names = true ) {
			$name	 = mp_get_user_address_part( 'first_name', 'billing' ) . ' ' . mp_get_user_address_part( 'last_name', 'billing' );
			$form	 = '
			<input type="hidden" id="mp-cc-name" name="mp_cc_name" value="' . esc_attr( $name ) . '">
			<div class="mp_checkout_field">
				<label class="mp_form_label">' . __( 'Card Number', 'mp' ) . ' <span class="mp_field_required">*</span></label>
				<input type="text" ' . (( $use_names ) ? 'name="mp_cc_num"' : 'id="mp-cc-num"' ) . ' pattern="\d*" autocomplete="cc-number" class="mp_form_input mp_form_input-cc-num mp-input-cc-num" data-rule-required="true" data-rule-cc-num="true">
			</div>
			<div class="mp_checkout_fields">
				<div class="mp_checkout_column mp_checkout_field">
					<label class="mp_form_label">' . __( 'Expiration', 'mp' ) . ' <span class="mp_field_required">*</span> <span class="mp_tooltip-help">' . __( 'Enter in <strong>MM/YYYY</strong> or <strong>MM/YY</strong> format', 'mp' ) . '</span></label>
					<input type="text" ' . (( $use_names ) ? 'name="mp_cc_exp"' : 'id="mp-cc-exp"' ) . ' autocomplete="cc-exp" class="mp_form_input mp_form_input-cc-exp mp-input-cc-exp" data-rule-required="true" data-rule-cc-exp="true">
				</div>
				<div class="mp_checkout_column mp_checkout_field">
					<label class="mp_form_label">' . __( 'Security Code ', 'mp' ) . ' <span class="mp_field_required">*</span> <span class="mp_tooltip-help"><img src="' . mp_plugin_url( 'ui/images/cvv_2.jpg' ) . '" alt="CVV2"></span></label>
					<input class="mp_form_input mp_form_input-cc-cvc mp-input-cc-cvc" type="text" ' . (( $use_names ) ? 'name="mp_cc_cvc"' : 'id="mp-cc-cvc"' ) . ' name="mp_cc_cvc" autocomplete="off" data-rule-required="true" data-rule-cc-cvc="true">
				</div>
			</div>';

			return $form;
		}

		/**
		 * Calculate the CRC of an item
		 *
		 * @since 3.0
		 * @access protected
		 * @param mixed $item
		 * @return string
		 */
		protected function _crc( $item ) {
			if ( is_array( $item ) ) {
				$item = serialize( $item );
			}

			return crc32( $item );
		}

		/**
		 * Generate the IPN URL
		 *
		 * @since 3.0
		 * @access protected
		 */
		protected function _generate_ipn_url() {
			$this->ipn_url = admin_url( 'admin-ajax.php?action=mp_process_ipn_return_' . $this->plugin_name );
		}

		/**
		 * Generate the cancel checkout url
		 *
		 * @since 3.0
		 * @access protected
		 */
		function _generate_checkout_cancel_url() {
			$this->cancel_url = add_query_arg( 'mp_checkout_cancel_' . $this->plugin_name, 1, mp_store_page_url( 'checkout', false ) );
		}

		/**
		 * Generate the checkout return url
		 *
		 * @since 3.0
		 * @access protected
		 */
		function _generate_checkout_return_url() {
			if ( $this->use_confirmation_step ) {
				$this->return_url = mp_store_page_url( 'confirm_order', false );
			} else {
				$this->return_url = admin_url( 'admin-ajax.php?action=mp_process_checkout_return_' . $this->plugin_name );
			}
		}

		//creates the payment method selections
		function _payment_form_wrapper( $content, $cart = null, $billing_info = null ) {
			if ( is_null( $cart ) ) {
				$cart = mp_cart();
			}

			if ( is_null( $billing_info ) ) {
				$billing_info = mp_get_user_address( 'billing' );
			}

			$hidden = (count( self::$_active_gateways ) > 1 && mp_get_session_value( 'mp_payment_method' ) != $this->plugin_name) ? ' style="display:none;"' : '';

			$content .= '<div class="mp_gateway_form" id="mp-gateway-form-' . $this->plugin_name . '"' . $hidden . '>';

			$content .= $this->payment_form( $cart, $billing_info );

			$content .= '</div>';

			return $content;
		}

		/**
		 * Initialize the settings metabox
		 *
		 * @since 3.0
		 * @access public
		 */
		public function init_settings_metabox() {
			// Override in child gateway
		}

		/**
		 * Initialize the network settings metabox
		 *
		 * @since 3.0
		 * @access public
		 */
		public function init_network_settings_metabox() {
			// Override in child gateway
		}

		/**
		 * Get the confirm order html
		 *
		 * @since 3.0
		 * @access public
		 * @filter mp_checkout/confirm_order_html/{plugin_name}
		 */
		public function confirm_order_html( $html ) {
			return sprintf( __( 'You have chosen to checkout using <strong>%s</strong>. Please confirm your order details and click "Submit Order" when finished.', 'mp' ), $this->public_name );
		}

		/**
		 * Generates an appropriate field name
		 *
		 * @since 3.0
		 * @access public
		 * @param string $name The name of the field (e.g. name->subname1->subname2).
		 * @return string
		 */
		public function get_field_name( $name ) {
			$name_parts = explode( '->', $name );

			foreach ( $name_parts as &$part ) {
				$part = '[' . $part . ']';
			}

			return "gateways[{$this->plugin_name}]" . implode( $name_parts );
		}

		/**
		 * Gets a setting specific to the gateway
		 *
		 * @since 3.0
		 * @access public
		 * @param string $setting
		 * @param mixed $default
		 * @return mixed
		 */
		public function get_setting( $setting, $default = false ) {
			return mp_get_setting( "gateways->" . $this->plugin_name . "->{$setting}", $default );
		}

		/**
		 * Gets a network setting specific to the gateway
		 *
		 * @since 3.0
		 * @access public
		 * @param string $setting
		 * @param mixed $default
		 * @return mixed
		 */
		public function get_network_setting( $setting, $default = false ) {
			return mp_get_network_setting( "gateways->" . $this->plugin_name . "->{$setting}", $default );
		}

		/**
		 * Determines if the gateway settings needs to be updated
		 *
		 * @since 3.0
		 * @access public
		 */
		public final function maybe_update() {
			if ( !is_null( $this->build ) && $this->build != $this->get_setting( 'build' ) ) {
				$old_settings											 = get_option( 'mp_settings' );
				$settings												 = $this->update( $old_settings );
				$settings[ 'gateways' ][ $this->plugin_name ][ 'build' ] = $this->build;
				update_option( 'mp_settings', $settings );
			}
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
			return $settings;
		}

		//DO NOT override the construct! instead use the on_creation() method.
		function __construct() {
			$this->maybe_update();

			$this->_generate_ipn_url();
			$this->_generate_checkout_cancel_url();
			$this->_generate_checkout_return_url();

			//run plugin construct
			$this->on_creation();

			//check required vars
			if ( empty( $this->plugin_name ) || empty( $this->admin_name ) || empty( $this->public_name ) ) {
				wp_die( __( "You must override all required vars in your {$this->admin_name} payment gateway plugin!", 'mp' ) );
			}

			add_filter( 'mp_checkout/confirm_order_html/' . $this->plugin_name, array( &$this, 'confirm_order_html' ) );
			add_filter( 'mp_checkout_payment_form', array( &$this, '_payment_form_wrapper' ), 10, 3 );
			add_action( 'mp_process_payment_' . $this->plugin_name, array( &$this, 'process_payment' ), 10, 3 );
			add_action( 'mp_checkout/confirm_order/' . $this->plugin_name, array( &$this, 'process_confirm_order' ) );
			add_action( 'wp_footer', array( &$this, 'maybe_print_checkout_scripts' ) );
			add_action( 'wp_ajax_nopriv_mp_process_ipn_return_' . $this->plugin_name, array( &$this, 'process_ipn_return' ) );
			add_action( 'wp_ajax_mp_process_ipn_return_' . $this->plugin_name, array( &$this, 'process_ipn_return' ) );
			add_action( 'wp_ajax_mp_process_checkout_return_' . $this->plugin_name, array( &$this, 'process_checkout_return' ) );
			add_action( 'wp_ajax_nopriv_mp_process_checkout_return_' . $this->plugin_name, array( &$this, 'process_checkout_return' ) );
			add_action( 'wp', array( &$this, 'maybe_force_ssl' ) );

			if ( is_admin() ) {
				$this->init_settings_metabox();
			}

			if ( is_network_admin() ) {
				$this->init_network_settings_metabox();
			}
		}

	}

endif;

if ( !function_exists( 'mp_register_gateway_plugin' ) ) :

	/**
	 * Use this function to register your gateway plugin class
	 *
	 * @param string $class_name - the case sensitive name of your plugin class
	 * @param string $plugin_name - the sanitized private name for your plugin
	 * @param string $admin_name - pretty name of your gateway, for the admin side.
	 * @param bool $global optional - whether the gateway supports global checkouts
	 */
	function mp_register_gateway_plugin( $class_name, $plugin_name, $admin_name, $global = false, $demo = false ) {
		if ( class_exists( $class_name ) ) {
			MP_Gateway_API::register_gateway( $plugin_name, array( $class_name, $admin_name, $global, $demo ) );
		} else {
			return false;
		}
	}
























endif;