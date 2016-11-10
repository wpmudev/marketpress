<?php
if( ! defined('MP_TABLE_RATE_SHIPPING_INCLUDE_DOWNLOADS') ){
	define('MP_TABLE_RATE_SHIPPING_INCLUDE_DOWNLOADS', false);
}

class MP_Shipping_Table_Rate extends MP_Shipping_API {
	//build of the plugin
	var $build = 2;

	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'table_rate';

	//public name of your method, for lists and such.
	var $public_name = '';

	//set to true if you need to use the shipping_metabox() method to add per-product shipping options
	var $use_metabox = false;

	//set to true if you want to add per-product weight shipping field
	var $use_weight = false;

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//declare here for translation
		$this->public_name = __('Table Rate', 'mp');

		add_filter( 'wpmudev_field/get_value/shipping[table_rate][rates]', array( &$this, 'get_rates_value' ), 10, 4 );
		add_filter( 'wpmudev_field/sanitize_for_db', array( &$this, 'sanitize_rates' ), 10, 3);
	}

  /**
   * Get rates sorted by price from highest to lowest
   *
   * @since 3.0
   * @access public
   * @return array
   */
  public function get_rates() {
	  $rates = (array) $this->get_setting( 'rates', array() );
	  usort( $rates, array( &$this, 'sort_rates' ) );
	  return $rates;
  }

  /**
   * Filter the rates field value
   *
   * @since 3.0
   * @access public
   */
  public function get_rates_value( $value, $post_id, $raw, $field ) {
	  if ( is_array( $value ) ) {
	  	usort( $value, array( &$this, 'sort_rates' ) );
	  }

	  return $value;
  }

  /**
   * Sort rates from highest to lowest
   *
   * @since 3.0
   * @access public
   * @param array $a
   * @param array $b
   * @return int
   */
  public function sort_rates( $a, $b ) {
	  $mincost1 = (float) mp_arr_get_value( 'mincost', $a, 0 );
	  $mincost2 = (float) mp_arr_get_value( 'mincost', $b, 0 );

	  if ( $mincost1 == $mincost2 ) {
		  return 0;
	  }

	  return ( $mincost2 > $mincost1 ) ? 1 : -1;
  }

  /**
   * Initialize the settings metabox
   *
   * @since 3.0
   * @access public
   */
  public function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id' => $this->generate_metabox_id(),
			'page_slugs' => array(
				'store-settings-shipping',
				'store-settings_page_store-settings-shipping',
				'store-setup-wizard'
			),
			'title' => sprintf( __( '%s Settings', 'mp' ), $this->public_name ),
			'desc' => __( 'Be sure to enter a shipping price for every option or those customers may get free shipping. Don\'t worry about sorting as this will be done automatically upon saving.', 'mp' ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'action' => 'show',
				'name' => 'shipping[method]',
				'value' => 'table_rate',
			),
		));
		$layers = $metabox->add_field( 'repeater', array(
			'name' => $this->get_field_name( 'rates' ),
			'sortable' => false,
		) );

		if ( $layers instanceof WPMUDEV_Field ) {
			$layers->add_sub_field( 'text', array(
				'name' => 'mincost',
				'label' => array( 'text' => __( 'Cart Total', 'mp' ) ),
				'desc' => __( 'If cart total is greater than or equal to this value then the rates from that row will be used during checkout.', 'mp' ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0.01,
				),
			) );

			if ( 'US' == mp_get_setting( 'base_country') ) {
				$layers->add_sub_field( 'text', array(
					'name' => 'lower_48',
					'label' => array( 'text' => __( 'Lower 48 States', 'mp' ) ),
					'validation' => array(
						'required' => true,
						'number' => true,
						'min' => 0,
					),
				) );
				$layers->add_sub_field( 'text', array(
					'name' => 'hi_ak',
					'label' => array( 'text' => __( 'Hawaii and Alaska', 'mp' ) ),
					'validation' => array(
						'required' => true,
						'number' => true,
						'min' => 0,
					),
				) );
				$layers->add_sub_field( 'text', array(
					'name' => 'canada',
					'label' => array( 'text' => __( 'Canada', 'mp' ) ),
					'validation' => array(
						'required' => true,
						'number' => true,
						'min' => 0,
					),
				) );
			} else {
				$layers->add_sub_field( 'text', array(
					'name' => 'in_country',
					'label' => array( 'text' => __( 'In Country', 'mp' ) ),
					'validation' => array(
						'required' => true,
						'number' => true,
						'min' => 0,
					),
				) );

				if ( 'US' == mp_get_setting( 'base_country') ) {
					$layers->add_sub_field( 'text', array(
						'name' => 'usa',
						'label' => array( 'text' => __( 'United States', 'mp' ) ),
						'validation' => array(
							'required' => true,
							'number' => true,
							'min' => 0,
						),
					) );
				}

				if ( in_array( mp_get_setting( 'base_country' ), mp()->eu_countries ) ) {
					$layers->add_sub_field( 'text', array(
						'name' => 'eu',
						'label' => array( 'text' => __( 'European Union', 'mp' ) ),
						'validation' => array(
							'required' => true,
							'number' => true,
							'min' => 0,
						),
					) );
				}
			}

			$layers->add_sub_field( 'text', array(
				'name' => 'international',
				'label' => array( 'text' => __( 'International', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
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
	  $layers = mp_arr_get_value( 'shipping->table_rate', $settings );

	  if ( ! is_array( $layers ) ) {
			return $settings;
	  }

	  foreach ( $layers as $index => $layer ) {
		  //! TODO
	  }

	  return $settings;
	}

	/**
	 * Sanitize rates before saving
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/sanitize_for_db
	 */
	public function sanitize_rates( $value, $post_id, $field ) {
		if ( false === strpos( $field->args['name'], 'shipping[table_rate][rates]' ) ) {
			return $value;
		}

		return mp_display_currency( $value, 2 );
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
		* @return float $price
		*/
	function calculate_shipping( $price, $total, $cart, $address1, $address2, $city, $state, $zip, $country, $selected_option ) {
		if( ! MP_TABLE_RATE_SHIPPING_INCLUDE_DOWNLOADS ){
			$total = $cart->product_tangible_total(false);
		}
		$rates = $this->get_rates();
		$base_country = mp_get_setting( 'base_country' );

		$rate = array();
		//finding the right rate
		foreach ( $rates as $r ) {
			if ( $total >= mp_arr_get_value( 'mincost', $r, 0 ) ) {
				$rate = $r;
				break;
			}
		}
		//this cart doesn't meet the min cost, just return the price
		if ( empty( $rate ) ) {
			return $price;
		}

		switch ( $base_country ) {
			case 'US':
				if ( $country == 'US' ) {
					if ( $state == 'HI' || $state == 'AK' ) {
						$price = mp_arr_get_value( 'hi_ak', $rate, 0 );
					} else {
						$price = mp_arr_get_value( 'lower_48', $rate, 0 );
					}
				} elseif ( $country == 'CA' ) {
					$price = mp_arr_get_value( 'canada', $rate, 0 );
				} else {
					$price = mp_arr_get_value( 'international', $rate, 0 );
				}
			break;

		case 'CA':
			if ( $country == 'CA' ) {
				$price = mp_arr_get_value( 'in_country', $rate, 0 );
			} elseif ($country == 'US') {
				$price = mp_arr_get_value( 'usa', $rate, 0 );
			} else {
				$price = mp_arr_get_value( 'international', $rate, 0 );
			}
		break;

		default:
			if ( in_array( $base_country, mp()->eu_countries ) ) {
				//in european union
				if ( $base_country == $country ) {
					$price = mp_arr_get_value( 'in_country', $rate, 0 );
				} elseif ( in_array( $country, mp()->eu_countries) ) {
					$price = mp_arr_get_value( 'eu', $rate, 0 );
				} else {
					$price = mp_arr_get_value( 'international', $rate, 0 );
				}
			} else {
				//all other countries
				if ( $country == $base_country ) {
					$price = mp_arr_get_value( 'in_country', $rate, 0 );
				} else {
					$price = mp_arr_get_value( 'international', $rate, 0 );
				}
			}
		}

		return (float) $price;
	}
}

//register plugin - uncomment to register
MP_Shipping_API::register_plugin( 'MP_Shipping_Table_Rate', 'table_rate', __('Table Rate', 'mp') );