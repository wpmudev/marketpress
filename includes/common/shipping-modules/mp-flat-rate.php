<?php
/*
MarketPress Flat-Rate Shipping Plugin
Author: Aaron Edwards (Incsub)
*/
class MP_Shipping_Flat_Rate extends MP_Shipping_API {

  //private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'flat_rate';

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
    //set name here to be able to translate
    $this->public_name = __( 'Flat Rate', 'mp' );
    
    //format values
    add_filter( 'wpmudev_field/sanitize_for_db', array( &$this, 'format_input' ), 10, 3 );
	}
	
	/**
	 * Format input as decimal
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/sanitize_for_db
	 * @return string
	 */
	public function format_input( $value, $post_id, $field ) {
		if ( $field->args['name'] == 'shipping[flat_rate]' ) {
			foreach ( $value as &$val ) {
				$val = mp_display_currency( $val, 2 );
			}
		}
		
		return $value;
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
			'desc' => __( 'Be sure to enter a shipping price for every option or those customers may get free shipping.', 'mp' ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'action' => 'show',
				'name' => 'shipping[method]',
				'value' => 'flat_rate',
			),
		));
		$complex = $metabox->add_field( 'complex', array(
			'name' => 'shipping[flat_rate]',
		) );
		
		if ( ! $complex instanceof WPMUDEV_Field ) {
			return;
		}
			
		if ( 'US' == mp_get_setting( 'base_country') ) {
			$complex->add_field( 'text', array(
				'name' => 'lower_48',
				'label' => array( 'text' => __( 'Lower 48 States', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
			$complex->add_field( 'text', array(
				'name' => 'hi_ak',
				'label' => array( 'text' => __( 'Hawaii and Alaska', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
			$complex->add_field( 'text', array(
				'name' => 'canada',
				'label' => array( 'text' => __( 'Canada', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
		} else {
			$complex->add_field( 'text', array(
				'name' => 'in_country',
				'label' => array( 'text' => __( 'In Country', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
		}
		
		if ( 'CA' == mp_get_setting( 'base_country') ) { 
			$complex->add_field( 'text', array(
				'name' => 'usa',
				'label' => array( 'text' => __( 'United States', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
		}
		
		if ( in_array( mp_get_setting( 'base_country', '' ), mp()->eu_countries ) ) { 
			$complex->add_field( 'text', array(
				'name' => 'eu',
				'label' => array( 'text' => __( 'European Union', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			) );
		}
		
		$complex->add_field( 'text', array(
			'name' => 'international',
			'label' => array( 'text' => __( 'International', 'mp' ) ),
			'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
		) );
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
		switch ( mp_get_setting( 'base_country' ) ) {
			case 'US':
				if ( $country == 'US' ) {
					//price based on state
					if ( $state == 'HI' || $state == 'AK' ) {
						$price = $this->get_setting( 'hi_ak' );
					} else {
						$price = $this->get_setting( 'lower_48' );
					}
				} else if ( $country == 'CA' ) {
					$price = $this->get_setting( 'canada' );
				} else {
					$price = $this->get_setting( 'international' );
				}
			break;

			case 'CA':
				if ( $country == 'CA' ) {
					$price = $this->get_setting( 'in_country' );
				} elseif ( $country == 'US' ) {
					$price = $this->get_setting( 'usa' );
				} else {
					$price = $this->get_setting( 'international' );
				}
				break;

			default:
				if ( in_array( $this->get_setting( 'base_country' ), mp()->eu_countries ) ) {
					//in european union
					if ( $country == mp_get_setting( 'base_country' ) ) {
						$price = $this->get_setting( 'in_country' );
					} else if ( in_array( $country, mp()->eu_countries ) ) {
						$price = $this->get_setting( 'eu' );
					} else {
						$price = $this->get_setting( 'international' );
					}
				} else {
					//all other countries
					if ( $country == mp_get_setting( 'base_country' ) ) {
						$price = $this->get_setting( 'in_country' );
					} else {
						$price = $this->get_setting( 'international' );
					}
				}
			break;
		}
		
    return (float) $price;
  }
}

//register plugin
MP_Shipping_API::register_plugin( 'MP_Shipping_Flat_Rate', 'flat_rate', __('Flat Rate', 'mp') );