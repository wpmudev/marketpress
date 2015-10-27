<?php

/**
 * This class will hold all calculation about the tax
 *
 * Find tax rates base on country, city, state
 * Calculate inclusive tax
 * Calculate exclusive tax
 *
 * Recalculate price tax
 *
 *
 * @author: Hoang Ngo
 */
class MP_Taxes {

	private static $_instance;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Taxes();
		}

		return self::$_instance;
	}

	/**
	 * This will return an array of taxes, which calculated base on the prices input
	 *
	 * @param $price
	 * @param bool|false $inclusive_tax , this is check if prices included tax
	 *
	 * @return array
	 */
	public function calculate( $price, $inclusive_tax = false, $applied_rates ) {
		$taxes = array();
		if ( $inclusive_tax ) {
			$taxes = $this->calc_inclusive_taxes( $price, $applied_rates );
		} else {
			$taxes = $this->calc_exclusive_taxes( $price, $applied_rates );
		}

		return apply_filters( 'mp/cacled_taxes', $taxes );
	}


	public function find_rates( $table_rate, $is_shipping = false ) {
		$address = $this->get_tax_address();
		if ( strlen( $address['country'] ) == 0 ) {
			return array();
		}

		$applied_rates = $this->find_matched_rate_taxes( $address['country'], $address['state'], $address['city'], $address['zip'], $table_rate );

		if ( $is_shipping ) {
			foreach ( $applied_rates as $key => $rate ) {
				if ( $rate['apply_shipping'] == 0 ) {
					unset( $applied_rates[ $key ] );
				}
			}
		}

		return $applied_rates;
	}

	/**
	 * Return an array of taxes amount, using original price
	 *
	 * @param $price
	 * @param $applied_rates
	 *
	 * @return array
	 */
	public function calc_exclusive_taxes( $price, $applied_rates ) {
		$taxes = array();
		foreach ( $applied_rates as $key => $ar ) {
			if ( $ar['compound'] == 1 ) {
				continue;
			}

			$amount = $price * ( $ar['rate'] / 100 );

			$amount = apply_filters( 'mp/taxes_tax_amount', $amount, $price, $key, $ar, $applied_rates );

			if ( ! isset( $taxes[ $key ] ) ) {
				$taxes[ $key ] = $amount;
			} else {
				$taxes[ $key ] += $amount;
			}
		}
		$price_with_tax_pre_compound = (float) $price + (float) array_sum( $taxes );

		foreach ( $applied_rates as $key => $ar ) {
			if ( $ar['compound'] == 0 ) {
				continue;
			}

			$amount = $price_with_tax_pre_compound * ( $ar['rate'] / 100 );
			$amount = apply_filters( 'mp/taxes_tax_amount', $amount, $price_with_tax_pre_compound, $key, $ar, $applied_rates );

			if ( ! isset( $normal_taxes[ $key ] ) ) {
				$taxes[ $key ] = $amount;
			} else {
				$taxes[ $key ] += $amount;
			}
		}

		return $taxes;
	}

	/**
	 * @param $price
	 * @param $applied_rates
	 *
	 * @return array
	 */
	public function calc_inclusive_taxes( $price, $applied_rates ) {
		//we will need to calculate the original price first
		$normal_rates = $compound_rates = 0;
		$taxes        = array();

		foreach ( $applied_rates as $ar ) {
			if ( $ar['compound'] == 1 ) {
				$compound_rates += $ar['rate'];
			} else {
				$normal_rates += $ar['rate'];
			}
		}

		$normal_rates       = $normal_rates / 100;
		$compound_rates     = $compound_rates / 100;
		$non_compound_price = $price / ( 1 + $compound_rates );
		$non_tax_price      = $non_compound_price / ( 1 + $normal_rates );

		return $this->calc_exclusive_taxes( $non_tax_price, $applied_rates );
	}

	public function get_store_address() {
		return $address = array(
			'country' => mp_get_setting( 'base_country' ),
			'state'   => mp_get_setting( 'base_province' ),
			'city'    => '',
			'zip'     => mp_get_setting( 'base_zip' ),
		);
	}

	public function get_billing_address() {
		//first we will need to check session
		$address_session = mp_get_session_value( 'mp_billing_info', null );
		$address         = get_user_meta( get_current_user_id(), 'mp_billing_info', true );
		if ( ! is_array( $address ) ) {
			$address = array(
				'country' => '',
				'state'   => '',
				'city'    => '',
				'zip'     => '',
			);
		}

		if ( is_array( $address_session ) ) {
			$address = wp_parse_args( $address_session, $address );
		}

		return $address;
	}

	public function get_tax_address() {
		$address_type = mp_get_setting( 'tax->tax_calculate_based' );
		$address      = array(
			'country' => '',
			'state'   => '',
			'city'    => '',
			'zip'     => '',
		);
		switch ( $address_type ) {
			case 'shipping_address':
				$address = $this->get_shipping_address();
				break;
			case 'store_address':
				$address = $this->get_store_address();
				break;
			case 'billing_address':
				$address = $this->get_billing_address();
				break;
		}

		return $address;
	}

	public function get_shipping_address() {
		$address_session = mp_get_session_value( 'mp_shipping_info', null );
		$address         = get_user_meta( get_current_user_id(), 'mp_shipping_info', true );
		if ( ! is_array( $address ) ) {
			$address = array(
				'country' => '',
				'state'   => '',
				'city'    => '',
				'zip'     => '',
			);
		}

		if ( is_array( $address_session ) ) {
			$address = wp_parse_args( $address_session, $address );
		}

		return $address;
	}

	/**
	 * This will return an array of match taxes rate
	 *
	 * @param $country
	 * @param $state
	 * @param $city
	 * @param $postal
	 * @param $table_rate
	 *
	 * @return array
	 */
	public function find_matched_rate_taxes( $country, $state, $city, $postal, $table_rate ) {
		//we got the address, now get the data table
		$data  = $this->get_table_data();
		$rates = isset( $data[ $table_rate ] ) ? $data[ $table_rate ] : array();
		//got the rates, now lookup with the address
		$applied_rates = array();

		foreach ( $rates as $rate ) {
			//the country code must match
			if ( strcmp( strtolower( $rate['country_code'] ), strtolower( $country ) ) == 0 ) {
				$catch_states = strtolower( $rate['state_code'] );
				$catch_city   = strtolower( $rate['city'] );
				$catch_zip    = array();
				//zip can having range, so we need to check
				foreach ( explode( '|', $rate['zip'] ) as $zip ) {
					if ( count( explode( '-', $zip ) ) == 2 ) {
						$range = explode( '-', $zip );
						for ( $i = $range[0]; $i <= $range[1]; $i ++ ) {
							$catch_zip[] = (int) $i;
						}
					} else {
						$catch_zip[] = (int) $zip;
					}
				}

				if ( ( $catch_states == '*' || strcmp( $catch_states, strtolower( $state ) ) === 0 )
				     && ( $catch_city == '*' || strcmp( $catch_city, strtolower( $city ) ) === 0 )
				     && ( in_array( '*', $catch_zip ) || in_array( $postal, $catch_zip ) )
				) {
					//catch here
					$applied_rates[] = $rate;
				}
			}
		}

		//we got the rates, now check the priority, only 1 priority level for each, example, we won't
		//have  multiple rate with priority 1
		$priority_picking = array();
		foreach ( $applied_rates as $key => $ar ) {
			if ( ! in_array( $ar['priority'], $priority_picking ) ) {
				$priority_picking[] = $ar['priority'];
			} else {
				unset( $applied_rates[ $key ] );
			}
		}

		return $applied_rates;
	}

	/**
	 * This is price included tax
	 *
	 * @param $price
	 */
	public function before_tax_price( $price ) {

	}

	/**
	 * @param $price - without tax
	 * @param string $table_rate - the table we will lookup
	 *
	 * @return mixed
	 */
	public function product_price_with_tax( $price, $table_rate = 'standard', $context = '' ) {
		$inclusive = false;
		if ( mp_get_setting( 'tax->set_price_with_tax' ) == 'inclusive' ) {
			$inclusive = true;
		}

		$address       = $this->get_tax_address();
		$applied_rates = $this->find_matched_rate_taxes( $address['country'], $address['state'], $address['city'], $address['zip'], $table_rate );

		if ( $context == 'cart' ) {
			$mode = mp_get_setting( 'tax->cart_price_with_tax' );
		} else {
			$mode = mp_get_setting( 'tax->show_price_with_tax' );
		}

		$taxes = array();
		if ( $inclusive && $mode == 'inclusive' ) {
			return $price;
		} elseif ( $inclusive && $mode == 'exclusive' ) {
			//we have to calculate the price, this case is price already included tax
			$taxes = $this->calculate( $price, true, $applied_rates );
		} elseif ( ! $inclusive && $mode == 'inclusive' ) {
			$taxes = $this->calculate( $price, false, $applied_rates );
		} elseif ( ! $inclusive && $mode == 'exclusive' ) {
			//tax wont include, also we don't display tax inside the product price, return original
			return $price;
		}

		$taxes = array_sum( $taxes );

		$new_price = ( $inclusive == true ) ? $price - $taxes : $price + $taxes;

		return round( $new_price, 2 );
	}

	/**
	 * Get the table rates
	 */
	public function get_table_rates() {
		$tables = mp_get_setting( 'tax->tax_tables' );
		$tables = json_decode( stripslashes( $tables ), true );
		if ( ! is_array( $tables ) ) {
			$tables = array();
		}
		$data = array(
			'standard' => __( "Standard Table", "mp" )
		);
		foreach ( $tables as $table ) {
			$data[ str_replace( '-', '_', sanitize_title( $table ) ) ] = $table;
		}

		return $data;
	}

	public function get_table_data() {
		return mp_get_setting( 'tax->tables_data' );
	}
}

if ( ! function_exists( 'mp_tax' ) ) {
	function mp_tax() {
		return MP_Taxes::get_instance();
	}
}