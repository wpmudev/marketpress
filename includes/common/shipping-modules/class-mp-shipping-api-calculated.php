<?php

if ( ! class_exists( 'MP_Shipping_API_Calculated' ) ) {
	class MP_Shipping_API_Calculated extends MP_Shipping_API {
		/**
		 * Get an inch measurement depending on the current setting of [shipping] [system]
		 *
		 * @param float $units
		 *
		 * @return float, Converted to the current units_used
		 */
		protected function _as_inches( $units ) {
			$units = (float) $units;

			if ( 'metric' == mp_get_setting( 'shipping->system' ) ) {
				$units = ( $units * 2.54 );
			}

			return round( $units, 2 );
		}

		/**
		 * Get pounds measurement depending on the current setting of [shipping] [system]
		 *
		 * @param float $units
		 *
		 * @return float, Converted to pounds
		 */
		protected function _as_pounds( $units ) {
			$units = (float) $units;

			if ( 'metric' == mp_get_setting( 'shipping->system' ) ) {
				$units = ( $units * 2.2 );
			}

			return round( $units, 2 );
		}

		/**
		 * Test the $_SESSION cart cookie and mp_shipping_info
		 *
		 * Check to to see if the data changed since last calculated. Returns false
		 * if the either the crc for cart or shipping info has changed.
		 *
		 * @return bool
		 */
		protected function _crc_ok() {
			//Assume it changed
			$result = false;

			//Check the shipping options to see if we already have a valid shipping price
			if ( false !== mp_get_session_value( 'mp_shipping_options->' . $this->plugin_name ) ) {
				/* We have a set of prices. Are they still valid?
				Did the cart change since last calculation? */
				if ( mp_get_session_value( 'mp_cart_crc' ) == $this->crc( mp_cart()->get_items() ) ) {
					//Did the shipping info change?
					if ( mp_get_session_value( 'mp_shipping_crc' ) == $this->crc( mp_get_user_address( 'shipping' ) ) ) {
						$result = true;
					}
				}
			}

			return $result;
		}

		/**
		 * Update CRCs and cached shipping options
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param array $shipping_option An array of unformatted shipping options.
		 */
		public function _crc_update( $shipping_options ) {
			mp_update_session_value( 'mp_shipping_options->' . $this->plugin_name, $shipping_options );
			mp_update_session_value( 'mp_cart_crc', $this->crc( mp_cart()->get_items() ) );
			mp_update_session_value( 'mp_shipping_crc', $this->crc( mp_get_user_address( 'shipping' ) ) );
		}

		/**
		 * Formats a choice for the shipping options dropdown
		 *
		 * @param array $shipping_option , a $this->services key
		 * @param float $price , the price to display
		 *
		 * @return string, Formatted string with shipping method name delivery time and price
		 */
		protected function _format_shipping_option( $shipping_option = '', $price = '', $delivery = '', $handling = '' ) {
			if ( $_option = mp_arr_get_value( $shipping_option, $this->services ) ) {
				$option = $_option->name;
			} elseif ( $_option = mp_arr_get_value( $shipping_option, $this->intl_services ) ) {
				$option = $_option->name;
			}

			$price    = is_numeric( $price ) ? $price : 0;
			$handling = is_numeric( $handling ) ? $handling : 0;
			$total    = ( $price + $handling );

			if ( mp_get_setting( 'tax->tax_inclusive' ) && mp_get_setting( 'tax->tax_shipping' ) ) {
				//$total = $this->shipping_tax_price( $total );
			}

			$option .= sprintf( __( ' %1$s - %2$s', 'mp' ), $delivery, mp_format_currency( '', $total ) );

			return $option;
		}

		/**
		 * Format an array of unformatted shipping options
		 *
		 * @since 3.0
		 * @access private
		 *
		 * @param array $unformatted The array of unformatted shipping options.
		 *
		 * @return array
		 */
		protected function _format_shipping_options( $unformatted ) {
			$shipping_options = array();
			foreach ( $unformatted as $service => $option ) {
				$shipping_options[ $service ] = $this->_format_shipping_option( $service, $option['rate'], $option['delivery'], $option['handling'] );
				//match it up if there is already a selection
				if ( ( $suboption = mp_get_session_value( 'mp_shipping_info->shipping_sub_option' ) ) && ( $suboption == $service || ( is_array( $suboption ) && in_array( $service, $suboption ) ) ) ) {
					mp_update_session_value( 'mp_shipping_info->shipping_cost', ( $option['rate'] + $option['handling'] ) );
				}
			}

			return $shipping_options;
		}

		/**
		 * Returns a the string describing the units of length for the [mp_shipping][system] in effect
		 *
		 * @return string
		 */
		protected function _get_units_length() {
			return ( mp_get_setting( 'shipping->system' ) == 'english' ) ? __( 'Inches', 'mp' ) : __( 'Centimeters', 'mp' );
		}

		/**
		 * Get a string describing the units of weight for the [mp_shipping][system] in effect
		 *
		 * @return string
		 */
		protected function _get_units_weight() {
			return ( mp_get_setting( 'shipping->system' ) == 'english' ) ? __( 'Pounds', 'mp' ) : __( 'Kilograms', 'mp' );
		}

		/**
		 * Parse XML string into DOMDocument object
		 *
		 * @since 3.0
		 * @access protected
		 *
		 * @param string $xml
		 *
		 * @return DOMDocument
		 */
		protected function _parse_xml( $xml ) {
			libxml_use_internal_errors( true );
			$dom               = new DOMDocument();
			$dom->encoding     = 'utf-8';
			$dom->formatOutput = true;
			$dom->loadHTML( $xml );
			libxml_clear_errors();

			return $dom;
		}

		/**
		 * Use this function to return your calculated price as an integer or float
		 *
		 * @param int $price , always 0. Modify this and return
		 * @param float $total , cart total after any coupons and before tax
		 * @param array $cart , the contents of the shopping cart for advanced calculations
		 * @param string $address1
		 * @param string $address2
		 * @param string $city
		 * @param string $state , state/province/region
		 * @param string $zip , postal code
		 * @param string $country , ISO 3166-1 alpha-2 country code
		 * @param string $selected_option , if a calculated shipping module, passes the currently selected sub shipping option if set
		 * return float $price
		 */
		public function calculate_shipping( $price, $total, $cart, $address1, $address2, $city, $state, $zip, $country, $selected_option ) {
			$this->shipping_options( $cart, $address1, $address2, $city, $state, $zip, $country );

			return (float) mp_get_session_value( 'mp_shipping_info->shipping_cost', 0 );
		}

		/**
		 * For uasort
		 */
		public function compare_rates( $a, $b ) {
			if ( $a['rate'] == $b['rate'] ) {
				return 0;
			}

			return ( $a['rate'] < $b['rate'] ) ? - 1 : 1;
		}

		/**
		 * Detect changes in shopping cart between calculations
		 *
		 * @param mixed $item to calculate CRC of
		 *
		 * @return CRC32 of the serialized item
		 */
		public function crc( $item = '' ) {
			return crc32( serialize( $item ) );
		}
	}
}