<?php
/*
  MarketPress Shipping Plugin Base Class
 */
if ( !class_exists( 'MP_Shipping_API' ) ) {

	class MP_Shipping_API {

		//build of the gateway plugin
		var $build = null;
		//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
		var $plugin_name = '';
		//public name of your method, for lists and such.
		var $public_name = '';
		//set to true if you need to use the shipping_metabox() method to add per-product shipping options
		var $use_metabox = false;
		//set to true if you want to add per-product weight shipping field
		var $use_weight = false;
		//refers to the current shipping weight
		var $weight = 0;

		/**
		 * Refers to the registered plugins set by register_plugin()
		 *
		 * @since 3.0
		 * @access private
		 * @var array
		 */
		private static $_plugins = array();

		/**
		 * Refers to the active plugins
		 *
		 * @since 3.0
		 * @access private
		 * @var array
		 */
		private static $_active_plugins = array();

		/**
		 * Refers to the plugins that are loaded for the admin only (we need to load all the plugins for their settings)
		 *
		 * @since 3.0
		 * @access private
		 * @var array
		 */
		private static $_active_plugins_admin = array();

		/**
		 * Registers a shipping plugin class
		 *
		 * @param string $plugin_name - the sanitized private name for your plugin
		 * @param string $class_name - the case sensitive name of your plugin class
		 * @param string $public_name - the public name of the plugin for lists and such
		 * @param bool $calculated - whether this is a calculated shipping module that can be selected by the user at checkout (UPS, USPS, FedEx, etc.)
		 */
		public static function register_plugin( $class_name, $plugin_name, $public_name, $calculated = false, $demo = false ) {
			self::$_plugins[ $plugin_name ] = array( $class_name, $public_name, $calculated, $demo );
		}

		/**
		 * Gets all of the registered plugins
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function get_plugins() {
			return self::$_plugins;
		}

		/**
		 * Loads the active plugins
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function load_active_plugins( $force = false ) {
			if ( !empty( self::$_active_plugins ) && !$force ) {
				// We already loaded the active plugins. No need to continue.
				return;
			}

			self::$_active_plugins = array();

			if ( is_admin() && !mp_doing_ajax( 'mp_update_checkout_data' ) ) {
				// In admin, load all shipping plugins so we can retrieve their settings
				foreach ( self::$_plugins as $code => $plugin ) {
					$class = $plugin[ 0 ];
					if ( class_exists( $class ) && !array_key_exists( $code, self::$_active_plugins ) ) {
						self::$_active_plugins_admin[ $code ] = new $class;
					}
				}

				return;
			}
			if ( mp_get_setting( 'shipping->method' ) == 'calculated' ) {
				//load just the calculated ones
				foreach ( self::$_plugins as $code => $plugin ) {
					if ( $plugin[ 2 ] ) {
						if ( mp_get_setting( "shipping->calc_methods->{$code}" ) && class_exists( $plugin[ 0 ] ) && !$plugin[ 3 ] ) {
							self::$_active_plugins[ $code ] = new $plugin[ 0 ];
						}
					}
				}
			} else {
				//load only selected shipping method
				$plugin	 = mp_arr_get_value( mp_get_setting( 'shipping->method' ), self::$_plugins );
				$class	 = $plugin[ 0 ];
				if ( class_exists( $class ) ) {
					self::$_active_plugins[ mp_get_setting( 'shipping->method' ) ] = new $class;
				}
			}

			do_action('mp/shipping_api/after_plugins_loaded');
		}

		/**
		 * Gets the active plugins
		 *
		 * @since 3.0
		 * @access public
		 * @return array
		 */
		public static function get_active_plugins() {
			return self::$_active_plugins;
		}

		/**
		 * Generats the appropriate metabox ID for the plugin
		 *
		 * @since 3.0
		 * @access private
		 * @return string
		 */
		public final function generate_metabox_id() {
			return 'mp-settings-shipping-plugin-' . strtolower( $this->plugin_name );
		}

		/*		 * **** Below are the public methods you may overwrite via a plugin ***** */

		/**
		 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
		 */
		function on_creation() {

		}

		/**
		 * Add additional shipping fields
		 *
		 * @since 3.0
		 * @access public
		 * @filter mp_checkout/address_fields_array
		 * @param array $fields
		 * @param string $type
		 */
		public function extra_shipping_field( $fields, $type ) {
			return $fields;
		}

		/**
		 * Use this function to return your calculated price as an integer or float
		 *
		 * @param int $price, always 0. Modify this and return
		 * @param float $total, cart total after any coupons and before tax
		 * @param array $cart, the contents of the shopping cart for advanced calculations
		 * @param string $address1
		 * @param string $address2
		 * @param string $city
		 * @param string $state, state/province/region
		 * @param string $zip, postal code
		 * @param string $country, ISO 3166-1 alpha-2 country code
		 * @param string $selected_option, if a calculated shipping module, passes the currently selected sub shipping option if set
		 *
		 * return float $price
		 */
		function calculate_shipping( $price, $total, $cart, $address1, $address2, $city, $state, $zip, $country,
							   $selected_option ) {
			//it is required to override this method
			wp_die( __( "You must override the calculate_shipping() method in your {$this->public_name} shipping plugin!", 'mp' ) );
		}

		/**
		 * For calculated shipping modules, use this method to return an associative array of the sub-options. The key will be what's saved as selected
		 *  in the session. Note the shipping parameters won't always be set. If they are, add the prices to the labels for each option.
		 *
		 * @param array $cart, the contents of the shopping cart for advanced calculations
		 * @param string $address1
		 * @param string $address2
		 * @param string $city
		 * @param string $state, state/province/region
		 * @param string $zip, postal code
		 * @param string $country, ISO 3166-1 alpha-2 country code
		 *
		 * return array $shipping_options
		 */
		function shipping_options( $cart, $address1, $address2, $city, $state, $zip, $country ) {
			$shipping_options = array();
			return $shipping_options;
		}

		/*		 * **** Do not override any of these private methods please! ***** */

		////////////////////////////////////////////////////////////////////

		function _weight_shipping_metabox( $shipping_meta, $settings ) {
			global $mp;

			echo '<p>';
			if ( $mp->get_setting( 'shipping->system' ) == 'metric' ) {
				?>
				<label><?php _e( 'Weight (Kilograms)', 'mp' ); ?>:<br />
					<input type="text" size="6" id="mp_shipping_weight" name="mp_shipping_weight" value="<?php echo isset( $shipping_meta[ 'weight' ] ) ? $shipping_meta[ 'weight' ] : '0'; ?>" />
				</label>
				<?php
			} else {
				if ( isset( $shipping_meta[ 'weight' ] ) ) {
					$pounds	 = intval( $shipping_meta[ 'weight' ] );
					$oz		 = floatval( ($shipping_meta[ 'weight' ] - $pounds) * 16 );
				} else {
					$pounds	 = $oz		 = '';
				}
				?>
				<?php _e( 'Product Weight:', 'mp' ); ?><br />
				<label><input type="text" size="2" name="mp_shipping_weight_pounds" value="<?php echo $pounds; ?>" /> <?php _e( 'Pounds', 'mp' ); ?></label><br />
				<label><input type="text" size="2" name="mp_shipping_weight_oz" value="<?php echo $oz; ?>" /> <?php _e( 'Ounces', 'mp' ); ?></label>
				<?php
			}
			echo '</p>';
		}

		function _weight_save_shipping_metabox( $shipping_meta ) {
			//process extra per item shipping
			if ( mp_get_setting( 'shipping->system' ) == 'metric' ) {
				$shipping_meta[ 'weight' ] = (!empty( $_POST[ 'mp_shipping_weight' ] )) ? round( $_POST[ 'mp_shipping_weight' ], 2 ) : 0;
			} else {
				$pounds					 = (!empty( $_POST[ 'mp_shipping_weight_pounds' ] )) ? floatval( $_POST[ 'mp_shipping_weight_pounds' ] ) : 0;
				$oz						 = (!empty( $_POST[ 'mp_shipping_weight_oz' ] )) ? floatval( $_POST[ 'mp_shipping_weight_oz' ] ) : 0;
				$oz						 = $oz / 16;
				$shipping_meta[ 'weight' ] = floatval( $pounds + $oz );
			}

			return $shipping_meta;
		}

		/**
		 * Set session variables and return free shipping array
		 *
		 * @since 3.0
		 * @access protected
		 * @return array
		 */
		protected function _free_shipping() {
			mp_update_session_value( 'mp_shipping_info->shipping_sub_option', __( 'Free Shipping', 'mp' ) );
			mp_update_session_value( 'mp_shipping_info->shipping_cost', 0 );
			return array(
				__( 'Free Shipping', 'mp' ) => __( 'Free Shipping - 0.00', 'mp' ),
			);
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

			return "shipping[{$this->plugin_name}]" . implode( $name_parts );
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
			return mp_get_setting( "shipping->" . $this->plugin_name . "->{$setting}", $default );
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
			return mp_get_network_setting( "shipping->" . $this->plugin_name . "->{$setting}", $default );
		}

		/**
		 * Determines if the gateway settings needs to be updated
		 *
		 * @since 3.0
		 * @access public
		 */
		public final function maybe_update() {
			if ( !is_null( $this->build ) && $this->build != $this->get_setting( 'build' ) ) {
				$settings = $this->update( get_option( 'mp_settings' ) );
				mp_push_to_array( $settings, 'shipping->' . $this->plugin_name . '->build', $this->build );
				update_option( 'mp_settings', $settings );
			}
		}

		/**
		 * Updates the plugin settings
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
			$this->on_creation();

			add_filter( 'mp_checkout/address_fields_array', array( &$this, 'extra_shipping_field' ), 10, 2 );
			add_filter( "mp_calculate_shipping_{$this->plugin_name}", array( &$this, 'calculate_shipping' ), 10, 10 );
			add_filter( "mp_shipping_options_{$this->plugin_name}", array( &$this, 'shipping_options' ), 10, 7 );

			if ( is_admin() ) {
				$this->init_settings_metabox();
			}
		}

	}

}

/**
 * Shipping handler class
 */
class MP_Shipping_Handler {

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Shipping_Handler();
		}
		return self::$_instance;
	}

	private function __construct() {
		add_filter( 'mp_checkout_shipping_field', array( &$this, 'extra_shipping_box' ), 99 ); //run last
		add_filter( 'mp_checkout_shipping_field_readonly', array( &$this, 'extra_shipping_box_label' ), 99 ); //run last
		add_action( 'mp_shipping_process', array( &$this, 'process_shipping_form' ) );
		add_filter( 'mp_shipping_method_lbl', array( &$this, 'filter_method_lbl' ) );
		add_action( 'wp_ajax_nopriv_mp-shipping-options', array( &$this, 'shipping_sub_options' ) );
		add_action( 'wp_ajax_mp-shipping-options', array( &$this, 'shipping_sub_options' ) );
		add_action( 'mp_shipping_metabox', array( &$this, 'extra_shipping_metabox' ), 10, 2 );
		add_filter( 'mp_save_shipping_meta', array( &$this, 'extra_save_shipping_metabox' ) );
	}

	function extra_shipping_box( $content ) {
		if ( self::$active_plugins && mp_get_setting( 'shipping->method' ) == 'calculated' ) {
			$content .= '<thead><tr>';
			$content .= '<th colspan="2">' . __( 'Choose a Shipping Method:', 'mp' ) . '</th>';
			$content .= '</tr></thead>';
			$content .= '<tr>';
			$content .= '<td align="right">' . __( 'Shipping Method:', 'mp' ) . '</td><td id="mp-shipping-select-td">';
			$content .= '<input type="hidden" name="action" value="mp-shipping-options" />';
			$content .= '<select name="shipping_option" id="mp-shipping-select">';
			$shipping_option = isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] : '';
			foreach ( self::$active_plugins as $plugin ) {
				$content .= '<option value="' . $plugin->plugin_name . '"' . selected( $shipping_option, $plugin->plugin_name, false ) . '>' . esc_attr( $plugin->public_name ) . '</option>';
			}
			$content .= '</select>';
			$content .= ' <span id="mp-shipping-select-holder">' . $this->shipping_sub_options() . '</span>';
			$content .= '</td></tr>';
		}
		return $content;
	}

	function extra_shipping_box_label( $content ) {
		if ( $mp->get_setting( 'shipping->method' ) == 'calculated' && isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ) && isset( self::$active_plugins[ $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ] ) ) {
			$label = self::$active_plugins[ $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ]->public_name;

			if ( isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_sub_option' ] ) )
				$label .= ' - ' . $_SESSION[ 'mp_shipping_info' ][ 'shipping_sub_option' ];

			$content .= '<tr>';
			$content .= '<td align="right">' . __( 'Shipping Method:', 'mp' ) . '</td>';
			$content .= '<td>' . esc_attr( $label ) . '</td>';
			$content .= '</tr>';
		}
		return $content;
	}

	function process_shipping_form() {
		if ( isset( $_POST[ 'shipping_option' ] ) )
			$_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] = trim( $_POST[ 'shipping_option' ] );
		if ( isset( $_POST[ 'shipping_sub_option' ] ) ) {
			$_SESSION[ 'mp_shipping_info' ][ 'shipping_sub_option' ] = trim( $_POST[ 'shipping_sub_option' ] );
		}
	}

	function shipping_sub_options() {
		$first		 = reset( self::$active_plugins );
		$selected	 = isset( $_POST[ 'shipping_option' ] ) ? $_POST[ 'shipping_option' ] : (isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] : $first->plugin_name);

		//get address
		$meta		 = get_user_meta( get_current_user_id(), 'mp_shipping_info', true );
		$address1	 = isset( $_POST[ 'address1' ] ) ? trim( stripslashes( $_POST[ 'address1' ] ) ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'address1' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'address1' ] : $meta[ 'address1' ]);
		$address2	 = isset( $_POST[ 'address2' ] ) ? trim( stripslashes( $_POST[ 'address2' ] ) ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'address2' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'address2' ] : $meta[ 'address2' ]);
		$city		 = isset( $_POST[ 'city' ] ) ? trim( stripslashes( $_POST[ 'city' ] ) ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'city' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'city' ] : $meta[ 'city' ]);
		$state		 = isset( $_POST[ 'state' ] ) ? trim( stripslashes( $_POST[ 'state' ] ) ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'state' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'state' ] : $meta[ 'state' ]);
		$zip		 = isset( $_POST[ 'zip' ] ) ? trim( stripslashes( $_POST[ 'zip' ] ) ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'zip' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'zip' ] : $meta[ 'zip' ]);
		$country	 = isset( $_POST[ 'country' ] ) ? trim( $_POST[ 'country' ] ) : (isset( $_SESSION[ 'mp_shipping_info' ][ 'country' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'country' ] : $meta[ 'country' ]);

		//Pick up any service specific fields
		do_action( 'mp_shipping_process' );

		$options = apply_filters( "mp_shipping_options_$selected", $mp->get_cart_contents(), $address1, $address2, $city, $state, $zip, $country );

		$content = '';
		if ( count( $options ) && !array_key_exists( 'error', $options ) ) {  //If one of the keys is 'error' then it contains an error message from calculated rates.
			if ( defined( 'DOING_AJAX' ) ) {
				header( 'Content-Type: text/html' );
			}

			$content .= '<select name="shipping_sub_option" size="' . max( count( $options ), 4 ) . '">'; //4 min because of safari
			//Make sure the $_SESSION suboption is still in the available rates
			$suboption	 = isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_sub_option' ] ) ? $_SESSION[ 'mp_shipping_info' ][ 'shipping_sub_option' ] : '';
			$suboption	 = array_key_exists( $suboption, $options ) ? $suboption : '';

			$ndx = 0;
			foreach ( $options as $key => $name ) {
				$selected = ($ndx == 0 && empty( $suboption ) ) ? true : ($suboption == $key); //Nothing selected pick the first one.
				$content .= '<option value="' . $key . '"' . selected( $selected, true, false ) . '>' . esc_attr( $name ) . '</option>';
				$ndx++;
			}
			$content .= '</select>';
		} else {
			if ( defined( 'DOING_AJAX' ) ) {
				header( 'Content-Type: application/json' );
				$content = json_encode( array( 'error' => $options[ 'error' ] ) );
			} else {
				$content .= $options[ 'error' ];
				$content .= '<input type="hidden" name="no_shipping_options" value="1" />';
			}
			$content .= apply_filters( 'mp_checkout_error_no_shipping_options', '' );
		}


		if ( defined( 'DOING_AJAX' ) )
			die( $content );
		else
			return $content;
	}

	function filter_method_lbl() {
		if ( isset( $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ) && isset( self::$active_plugins[ $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ] ) ) {
			return self::$active_plugins[ $_SESSION[ 'mp_shipping_info' ][ 'shipping_option' ] ]->public_name;
		}
	}

	function extra_shipping_metabox( $shipping_meta, $settings ) {
		global $mp;
		?>
		<p>
			<label><?php _e( 'Extra Shipping Cost', 'mp' ); ?>:<br />
		<?php echo mp_format_currency(); ?><input type="text" size="6" id="mp_extra_shipping_cost" name="mp_extra_shipping_cost" value="<?php echo!empty( $shipping_meta[ 'extra_cost' ] ) ? $mp->display_currency( $shipping_meta[ 'extra_cost' ] ) : '0.00'; ?>" />
			</label>
		</p>
		<?php
	}

	function extra_save_shipping_metabox( $shipping_meta ) {
		//process extra per item shipping
		$shipping_meta[ 'extra_cost' ] = (!empty( $_POST[ 'mp_extra_shipping_cost' ] )) ? round( $_POST[ 'mp_extra_shipping_cost' ], 2 ) : 0;
		return $shipping_meta;
	}

}

$GLOBALS[ 'mpsh' ] = MP_Shipping_Handler::get_instance();
