<?php

class MP_Shipping_FedEx extends MP_Shipping_API_Calculated {
	//build of the plugin
	public $build = 2;

	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	public $plugin_name = 'fedex';

	//public name of your method, for lists and such.
	public $public_name = '';

	//set to true if you need to use the shipping_metabox() method to add per-product shipping options
	public $use_metabox = true;

	//set to true if you want to add per-product weight shipping field
	public $use_weight = true;

	//set to true if you want to add per-product extra shipping cost field
	public $use_extra = true;

	public $sandbox_uri = 'https://wsbeta.fedex.com:443/web-services';

	public $production_uri = 'https://ws.fedex.com:443/web-services';

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//declare here for translation
		$this->public_name = __( 'FedEx', 'mp' );

		//get services
		$this->get_services();

		//make copy of settings
		$this->settings = get_option( 'mp_settings' );
	}

	/**
	 * Gets the available Fedex services.
	 *
	 * @since 3.0
	 * @access public
	 */
	function get_services() {
		$this->services = array(
			'FIRST_OVERNIGHT'      => new FedEx_Service( 'FIRST_OVERNIGHT', __( 'First Overnight', 'mp' ), __( '(1 Day am)', 'mp' ) ),
			'PRIORITY_OVERNIGHT'   => new FedEx_Service( 'PRIORITY_OVERNIGHT', __( 'Priority Overnight', 'mp' ), __( '(1 Day am )', 'mp' ) ),
			'STANDARD_OVERNIGHT'   => new FedEx_Service( 'STANDARD_OVERNIGHT', __( 'Standard Overnight', 'mp' ), __( '(1 Day)', 'mp' ) ),
			'FEDEX_2_DAY_AM'       => new FedEx_Service( 'FEDEX_2_DAY_AM', __( 'Fedex 2 Day AM', 'mp' ), __( '(2 Days am)', 'mp' ) ),
			'FEDEX_2_DAY'          => new FedEx_Service( 'FEDEX_2_DAY', __( 'Fedex 2 Day', 'mp' ), __( '(2 Days)', 'mp' ) ),
			'FEDEX_EXPRESS_SAVER'  => new FedEx_Service( 'FEDEX_EXPRESS_SAVER', __( 'Fedex Express Saver', 'mp' ), __( '(3 Days)', 'mp' ) ),
			'FEDEX_GROUND'         => new FedEx_Service( 'FEDEX_GROUND', __( 'Fedex Ground', 'mp' ), __( '(1-7 Days)', 'mp' ) ),
			'GROUND_HOME_DELIVERY' => new FedEx_Service( 'GROUND_HOME_DELIVERY', __( 'Ground Home Delivery', 'mp' ), __( '(1-5 Days)', 'mp' ) ),
			'SMART_POST'           => new FedEx_Service( 'SMART_POST', __( 'Smart Post', 'mp' ), __( '(2-7 Days)', 'mp' ) ),
		);

		$this->intl_services = array(
			'INTERNATIONAL_ECONOMY'               => new FedEx_Service( 'INTERNATIONAL_ECONOMY', __( 'International Economy', 'mp' ), __( '(5 Days)', 'mp' ) ),
			'INTERNATIONAL_FIRST'                 => new FedEx_Service( 'INTERNATIONAL_FIRST', __( 'International First', 'mp' ), __( '(1-3 Days)', 'mp' ) ),
			'INTERNATIONAL_PRIORITY'              => new FedEx_Service( 'INTERNATIONAL_PRIORITY', __( 'International Priority', 'mp' ), __( '(1-3 Days)', 'mp' ) ),
			'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => new FedEx_Service( 'EUROPE_FIRST_INTERNATIONAL_PRIORITY', __( 'Europe First International Priority', 'mp' ), __( '(Next Day)', 'mp' ) ),
		);

		return $this->services + $this->intl_services;
	}

	function default_boxes() {
		// Initialize the default boxes if nothing there
		if ( count( $this->get_setting( 'boxes->name', array() ) ) < 2 ) {
			return array(
				'name'   => array(
					0 => 'Small Box',
					1 => 'Medium Box',
					2 => 'Large Box',
					3 => 'FedEx 10KG',
					4 => 'FedEx 25KG',
				),
				'size'   => array(
					0 => '12x11x2',
					1 => '13x12x3',
					2 => '18x13x3',
					3 => '16x16x10',
					4 => '22x17x13',
				),
				'weight' => array(
					0 => '20',
					1 => '20',
					2 => '20',
					3 => '22',
					4 => '56',
				),
			);
		}

		return array();
	}

	/**
	 * Echo anything you want to add to the top of the shipping screen
	 */
	function before_shipping_form( $content ) {
		return $content;
	}

	/**
	 * Echo anything you want to add to the bottom of the shipping screen
	 */
	function after_shipping_form( $content ) {
		return $content;
	}

	/**
	 * Add additional shipping fields
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_checkout/address_fields_array
	 *
	 * @param array $fields
	 * @param string $type
	 */
	public function extra_shipping_field( $fields, $type ) {
		if ( ! $this->get_setting( 'commercial' ) ) {
			$fields[] = array(
				'type'  => 'hidden',
				'name'  => mp_checkout()->field_name( 'residential', $type ),
				'value' => 1,
			);
		} else {
			$fields[] = array(
				'type'  => 'checkbox',
				'label' => __( 'This is a residential address', 'mp' ),
				'name'  => mp_checkout()->field_name( 'residential', $type ),
				'value' => 1,
				'atts'  => array(
					'checked' => ( mp_get_user_address_part( 'residential', $type ) ),
				),
			);
		}

		/*$this->residential = true;
		if ( ! $this->get_setting('commercial') ) { //force residential
			$content .= '<input type="hidden" name="residential" value="1" />';
			$_SESSION['mp_shipping_info']['residential'] = true;
		} else {

			if ( $residential = mp_get_session_value('mp_shipping_info->residential') ) {
				$checked = $residential;
			} else {
				$checked = true; //default to checked
				$_SESSION['mp_shipping_info']['residential'] = true;
			}
			
			$this->residential = $checked;
			
			$content .= '<tr>
			<td>' . __('Residential Delivery', 'mp') . '</td>
			<td>
			<input type="hidden" name="residential" value="0" />
			<input id="mp_residential" type="checkbox" name="residential" value="1" ' . checked($checked, true, false) .' />
			<small><em>' . __('Check if delivery is to a residence.', 'mp') . '</em></small>
			</td>
			</tr>';
		}*/

		return $fields;
	}

	/**
	 * Use this to process any additional field you may add. Use the $_POST global,
	 *  and be sure to save it to both the cookie and usermeta if logged in.
	 */
	function process_shipping_form() {
		if ( isset( $_POST['residential'] ) ) {
			$_SESSION['mp_shipping_info']['residential'] = $_POST['residential'];
			$this->residential                           = $_SESSION['mp_shipping_info']['residential'];
		}
	}

	/**
	 * Updates the plugin settings
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function update( $settings ) {
		// Update services
		$services = array();
		foreach ( $this->get_setting( 'services', array() ) as $k => $v ) {
			if ( is_numeric( $v ) && $v ) {
				$services[ $k ] = $v;
			}
		}

		if ( ! empty( $services ) ) {
			mp_push_to_array( $settings, 'shipping->fedex->services', $services );
		}

		// Update boxes
		if ( $this->get_setting( 'boxes->name' ) ) {
			$boxes     = array();
			$old_boxes = $this->get_setting( 'boxes' );

			foreach ( $old_boxes['name'] as $idx => $val ) {
				if ( empty( $val ) ) {
					continue;
				}

				$boxes[] = array(
					'ID'     => $idx,
					'name'   => $val,
					'size'   => $old_boxes['size'][ $idx ],
					'weight' => $old_boxes['weight'][ $idx ],
				);
			}

			mp_push_to_array( $settings, 'shipping->fedex->boxes', $boxes );
		}

		return $settings;
	}

	/**
	 * Initialize the settings metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => $this->generate_metabox_id(),
			'page_slugs'  => array(
				'store-settings-shipping',
				'store-settings_page_store-settings-shipping',
				'store-setup-wizard'
			),
			'title'       => sprintf( __( '%s Settings', 'mp' ), $this->public_name ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'shipping[method]',
					'value' => 'calculated',
				),
				array(
					'name'  => 'shipping[calc_methods][fedex]',
					'value' => 'fedex',
				),
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => $this->get_field_name( 'mode' ),
			'label'         => array( 'text' => __( 'Mode', 'mp' ) ),
			'default_value' => 'sandbox',
			'options'       => array(
				'sandbox'    => __( 'Sandbox', 'mp' ),
				'production' => __( 'Production', 'mp' ),
			),
		) );
		$metabox->add_field( 'text', array(
			'name'       => $this->get_field_name( 'api_key' ),
			'label'      => array( 'text' => __( 'API Key', 'mp' ) ),
			'validation' => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'text', array(
			'name'       => $this->get_field_name( 'api_password' ),
			'label'      => array( 'text' => __( 'API Password', 'mp' ) ),
			'validation' => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'text', array(
			'name'       => $this->get_field_name( 'account' ),
			'label'      => array( 'text' => __( 'Account ID', 'mp' ) ),
			'validation' => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'text', array(
			'name'  => $this->get_field_name( 'meter' ),
			'label' => array( 'text' => __( 'Meter ID', 'mp' ) ),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => $this->get_field_name( 'dropoff' ),
			'label'         => array( 'text' => __( 'Dropoff Type', 'mp' ) ),
			'default_value' => 'REGULAR_PICKUP',
			'options'       => array(
				'REGULAR_PICKUP'          => __( 'Regular Pickup', 'mp' ),
				'BUSINESS_SERVICE_CENTER' => __( 'Business Service Center', 'mp' ),
				'DROP_BOX'                => __( 'Drop Box', 'mp' ),
				'REQUEST_COURIER'         => __( 'Request Courier', 'mp' ),
				'STATION'                 => __( 'Station', 'mp' ),
			),
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'     => $this->get_field_name( 'packaging' ),
			'label'    => array( 'text' => __( 'Default Packaging', 'mp' ) ),
			'multiple' => false,
			'options'  => array(
				'YOUR_PACKAGING' => __( 'Your Packaging', 'mp' ),
				'FEDEX TUBE'     => __( 'Fedex Tube', 'mp' ),
				'FEDEX_PAK'      => __( 'Fedex Pak', 'mp' ),
				'FEDEX_ENVELOPE' => __( 'Fedex Envelope', 'mp' ),
				'FEDEX_BOX'      => __( 'Fedex Box', 'mp' ),
				'FEDEX_25KG_BOX' => __( 'Fedex 25kg Box', 'mp' ),
				'FEDEX_10KG_BOX' => __( 'Fedex 10kg Box', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'  => $this->get_field_name( 'commercial' ),
			'label' => array( 'text' => __( 'Allow Commercial Delivery?', 'mp' ) ),
			'desc'  => __( 'When checked the customer can chose Residential or Commercial delivery with Residential the default. Unchecked it\'s only Residential rates.', 'mp' ),
		) );

		$services = array();
		foreach ( $this->services as $service => $detail ) {
			$services[ $service ] = $detail->name . ' ' . $detail->delivery;
		}

		$metabox->add_field( 'checkbox_group', array(
			'name'    => $this->get_field_name( 'services' ),
			'label'   => array( 'text' => __( 'Domestic Services', 'mp' ) ),
			'options' => $services,
			'width'   => '33.3%',
		) );

		$metabox->add_field( 'text', array(
			'name'          => $this->get_field_name( 'domestic_handling' ),
			'default_value' => '0.00',
			'label'         => array( 'text' => __( 'Handling Charge per Domestic Shipment', 'mp' ) ),
			'validation'    => array(
				'number' => true,
				'min'    => 0,
			),
		) );

		$services = array();
		foreach ( $this->intl_services as $service => $detail ) {
			$services[ $service ] = $detail->name . ' ' . $detail->delivery;
		}

		$metabox->add_field( 'checkbox_group', array(
			'name'    => $this->get_field_name( 'services' ),
			'label'   => array( 'text' => __( 'International Services', 'mp' ) ),
			'options' => $services,
			'width'   => '33.3%',
		) );

		$metabox->add_field( 'text', array(
			'name'          => $this->get_field_name( 'intl_handling' ),
			'default_value' => '0.00',
			'label'         => array( 'text' => __( 'Handling Charge per International Shipment', 'mp' ) ),
			'validation'    => array(
				'number' => true,
				'min'    => 0,
			),
		) );

		$default_boxes = $this->default_boxes();
		$boxes         = array();

		foreach ( mp_arr_get_value( 'boxes->name', $default_boxes, array() ) as $idx => $val ) {
			if ( empty( $val ) ) {
				continue;
			}

			$boxes[] = array(
				'ID'     => $idx,
				'name'   => $val,
				'size'   => $this->get_setting( "boxes->size->{$idx}" ),
				'weight' => $this->get_setting( "boxes->weight->{$idx}" ),
			);
		}

		$repeater = $metabox->add_field( 'repeater', array(
			'name'          => $this->get_field_name( 'boxes' ),
			'label'         => array( 'text' => __( 'Standard Boxes and Weight Limits', 'mp' ) ),
			'desc'          => __( 'Enter your standard box sizes as LengthxWidthxHeight ( 12x8x6 ) For each box defined enter the maximum weight it can contain. Total weight selects the box size used for calculating Shipping costs.', 'mp' ),
			'add_row_label' => __( 'Add Box', 'mp' ),
			'default_value' => $boxes,
		) );

		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field( 'text', array(
				'name'       => 'name',
				'label'      => array( 'text' => __( 'Name', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'       => 'size',
				'label'      => array( 'text' => sprintf( __( 'Size (%s)', 'mp' ), mp_dimension_label() ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'       => 'weight',
				'label'      => array( 'text' => sprintf( __( 'Max Weight (%s)', 'mp' ), mp_weight_label() ) ),
				'validation' => array(
					'required' => true,
				),
			) );
		}
	}

	/**
	 * Echo any per-product shipping fields you need to add to the product edit screen shipping metabox
	 *
	 * @param array $shipping_meta , the contents of the post meta. Use to retrieve any previously saved product meta
	 * @param array $settings , access saved settings via $settings array.
	 */
	function shipping_metabox( $shipping_meta, $settings ) {

	}

	/**
	 * Save any per-product shipping fields from the shipping metabox using update_post_meta
	 *
	 * @param array $shipping_meta , save anything from the $_POST global
	 * return array $shipping_meta
	 */
	function save_shipping_metabox( $shipping_meta ) {

		return $shipping_meta;
	}

	/**
	 * For calculated shipping modules, use this method to return an associative array of the sub-options. The key will be what's saved as selected
	 *  in the session. Note the shipping parameters won't always be set. If they are, add the prices to the labels for each option.
	 *
	 * @param array $cart , the contents of the shopping cart for advanced calculations
	 * @param string $address1
	 * @param string $address2
	 * @param string $city
	 * @param string $state , state/province/region
	 * @param string $zip , postal code
	 * @param string $country , ISO 3166-1 alpha-2 country code
	 *
	 * return array $shipping_options
	 */
	function shipping_options( $cart, $address1, $address2, $city, $state, $zip, $country ) {
		if ( $this->_crc_ok() && false !== ( $shipping_options = mp_get_session_value( 'mp_shipping_options->' . $this->plugin_name ) ) ) {
			// CRC is ok - just return the shipping options already stored in session
			return $this->_format_shipping_options( $shipping_options );
		}

		$shipping_options = array();

		$this->address1        = $address1;
		$this->address2        = $address2;
		$this->city            = $city;
		$this->state           = $state;
		$this->destination_zip = $zip;
		$this->country         = $country;
		$this->residential     = mp_get_session_value( 'mp_shipping_info->residential' );
		$this->weight          = $cart->shipping_weight();

		if ( $this->weight == 0 ) {
			// Nothing to ship
			return $this->_free_shipping();
		}

		// Got our totals  make sure we're in decimal pounds.
		$this->weight = $this->_as_pounds( $this->weight );

		//ups won't accept a zero weight Package
		$this->weight = ( $this->weight == 0 ) ? 0.1 : $this->weight;

		if ( in_array( mp_get_setting( 'base_country' ), array(
				'US',
				'UM',
				'AS',
				'FM',
				'GU',
				'MH',
				'MP',
				'PW',
				'PR',
				'PI'
			) ) && strlen( mp_get_setting( 'base_zip', '' ) ) > 5
		) {
			// Can't use zip+4
			$this->settings['base_zip'] = substr( $this->settings['base_zip'], 0, 5 );
		}

		if ( in_array( $this->country, array( 'US', 'UM', 'AS', 'FM', 'GU', 'MH', 'MP', 'PW', 'PR', 'PI' ) ) ) {
			// Can't use zip+4
			$this->destination_zip = substr( $this->destination_zip, 0, 5 );
		}
		if ( $this->country == $this->settings['base_country'] ) {
			$shipping_options = $this->rate_request();
		} else {
			$shipping_options = $this->rate_request( true );
		}

		return $shipping_options;
	}

	function packages( $dimensions, $weight ) {
		$height = ( empty( $dimensions[0] ) ) ? 0 : (int) $dimensions[0];
		$width  = ( empty( $dimensions[1] ) ) ? 0 : (int) $dimensions[1];
		$length = ( empty( $dimensions[2] ) ) ? 0 : (int) $dimensions[2];

		$count    = $this->pkg_count;
		$packages =
			'<v13:PackageCount>' . $count . '</v13:PackageCount>';

		for ( $i = 0; $i < $count; $i ++ ) {
			$packages .= '
			<v13:RequestedPackageLineItems>
				<v13:SequenceNumber>' . $count . '</v13:SequenceNumber>
				<v13:GroupNumber>1</v13:GroupNumber>
				<v13:GroupPackageCount>1</v13:GroupPackageCount>
				<v13:Weight>
					<v13:Units>LB</v13:Units>
					<v13:Value>' . $weight . '</v13:Value>
				</v13:Weight>
				<v13:Dimensions>
					<v13:Length>' . $length . '</v13:Length>
					<v13:Width>' . $width . '</v13:Width>
					<v13:Height>' . $height . '</v13:Height>
					<v13:Units>IN</v13:Units>
				</v13:Dimensions>
			</v13:RequestedPackageLineItems>';
		}

		return $packages;
	}

	/**
	 * rate_request - Makes the actual call to fedex
	 */
	function rate_request( $international = false ) {
		$shipping_options = $this->get_setting( 'services', array() );
		$services         = ( $international ) ? $this->intl_services : $this->services;

		//Filter out all options that aren't enabled in settings
		$shipping_options = array_filter( $shipping_options, create_function( '$val', 'return ($val == 1);' ) );

		//Filter out all options that aren't in the $services array
		foreach ( $services as $code => $service ) {
			if ( ! array_key_exists( $code, $shipping_options ) ) {
				unset( $services[ $code ] );
			}
		}

		//Assume equal size packages. Find the best matching box size
		$boxes     = $this->get_setting( 'boxes' );
		$box       = false;
		$index     = 1;
		$box_count = count( $boxes );

		foreach ( $boxes as $thebox ) {
			$box_weight = (float) $thebox['weight'];

			if ( $this->weight <= $box_weight || ( $index == $box_count && $box === false ) ) {
				$box = $thebox;
				break;
			}

			$index ++;
		}

		if ( $box['weight'] >= $this->weight ) {
			$this->pkg_count  = 1;
			$this->pkg_weight = $this->weight;
		} else {
			$this->pkg_count  = ceil( $this->weight / $box['weight'] ); // Avoid zero
			$this->pkg_weight = $this->weight / $this->pkg_count;
		}

		// Fixup pounds by converting multiples of 16 ounces to pounds
		$this->pounds = (int) $this->pkg_weight;
		$this->ounces = round( ( $this->pkg_weight - $this->pounds ) * 16 );

		//found our box
		$dims = explode( 'x', strtolower( $box['size'] ) );
		foreach ( $dims as &$dim ) {
			$dim = $this->_as_inches( $dim );
		}

		sort( $dims ); //Sort so two lowest values are used for Girth

		$packages = $this->packages( $dims, $this->pkg_weight );

		$xml_req = '
		<?xml version="1.0" encoding="UTF-8"?>
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v13="http://fedex.com/ws/rate/v13">
			<soapenv:Body>
				<v13:RateRequest>
					<v13:WebAuthenticationDetail>
						<v13:UserCredential>
							<v13:Key>' . $this->get_setting( 'api_key' ) . '</v13:Key>
							<v13:Password>' . $this->get_setting( 'api_password' ) . '</v13:Password>
						</v13:UserCredential>
					</v13:WebAuthenticationDetail>
					<v13:ClientDetail>
						<v13:AccountNumber>' . $this->get_setting( 'account' ) . '</v13:AccountNumber>
						<v13:MeterNumber>' . $this->get_setting( 'meter' ) . '</v13:MeterNumber>
					</v13:ClientDetail>
					<v13:TransactionDetail>
						<v13:CustomerTransactionId>Marketpress Rates Request</v13:CustomerTransactionId>
					</v13:TransactionDetail>
					<v13:Version>
						<v13:ServiceId>crs</v13:ServiceId>
						<v13:Major>13</v13:Major>
						<v13:Intermediate>0</v13:Intermediate>
						<v13:Minor>0</v13:Minor>
					</v13:Version>
					<v13:RequestedShipment>
						<v13:DropoffType>' . $this->get_setting( 'dropoff' ) . '</v13:DropoffType>
						<v13:PackagingType>' . $this->get_setting( 'packaging' ) . '</v13:PackagingType>
						<v13:PreferredCurrency>' . mp_get_setting( 'currency' ) . '</v13:PreferredCurrency>
						<v13:Shipper>
							<v13:Address>
								<v13:StateOrProvinceCode>' . mp_get_setting( 'base_province' ) . '</v13:StateOrProvinceCode>
								<v13:PostalCode>' . mp_get_setting( 'base_zip' ) . '</v13:PostalCode>
								<v13:CountryCode>' . mp_get_setting( 'base_country' ) . '</v13:CountryCode>
							</v13:Address>
						</v13:Shipper>
						<v13:Recipient>
							<v13:Address>
								<v13:StreetLines>' . $this->address1 . '</v13:StreetLines>
								<v13:StreetLines>' . $this->address2 . '</v13:StreetLines>
								<v13:City>' . $this->city . '</v13:City>
								<v13:StateOrProvinceCode>' . $this->state . '</v13:StateOrProvinceCode>
								<v13:PostalCode>' . $this->destination_zip . '</v13:PostalCode>
								<v13:CountryCode>' . $this->country . '</v13:CountryCode>
								<v13:Residential>' . ( ( ! empty( $this->residential ) || $this->get_setting( 'commercial' ) ) ? 'true' : 'false' ) . '</v13:Residential>
							</v13:Address>
						</v13:Recipient>
						<v13:ShippingChargesPayment>
							<v13:PaymentType>SENDER</v13:PaymentType>
							<v13:Payor>
								<v13:ResponsibleParty>
									<v13:AccountNumber>' . $this->get_setting( 'account' ) . '</v13:AccountNumber>
								</v13:ResponsibleParty>
							</v13:Payor>
						</v13:ShippingChargesPayment>
						<v13:RateRequestTypes>LIST</v13:RateRequestTypes>' .

		           $packages . '

					</v13:RequestedShipment>
				</v13:RateRequest>
			</soapenv:Body>
		</soapenv:Envelope>';

		// Remove all tabs and line breaks - otherwise Fedex won't be able to process request
		$xml_req = str_replace( array( "\t", "\r\n", "\n" ), '', $xml_req );

		//We have the XML make the call
		$url = ( 'sandbox' == $this->get_setting( 'mode' ) ) ? $this->sandbox_uri : $this->production_uri;

		$response = wp_remote_post( $url, array(
			'headers'   => array( 'Content-Type: text/xml' ),
			'body'      => $xml_req,
			'sslverify' => false,
			'timeout'   => 30
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => '<div class="mp_checkout_error">' . $response->get_error_message() . '</div>',
			);
		} else {
			$loaded = ( $response['response']['code'] == '200' );
			$body   = $response['body'];

			if ( ! $loaded ) {
				return array(
					'error' => '<div class="mp_checkout_error">FedEx: ' . $response['response']['code'] . "&mdash;" . $response['response']['message'] . '</div>',
				);
			}
		}

		$dom = $this->_parse_xml( $body );

		//Process the return XML

		//Clear any old price
		unset( $_SESSION['mp_shipping_info']['shipping_cost'] );

		$xpath = new DOMXPath( $dom );

		//Check for errors
		$nodes = $xpath->query( '//highestseverity' );
		if ( in_array( $nodes->item( 0 )->textContent, array( 'ERROR', 'FAILURE', 'WARNING' ) ) ) {
			$nodes            = $xpath->query( '//message' );
			$this->rate_error = $nodes->item( 0 )->textContent;

			return array(
				'error' => '<div class="mp_checkout_error">FedEx: ' . $this->rate_error . '</div>',
			);
		}

		$mp_shipping_options = array();
		foreach ( $services as $code => $service ) {
			$nodes = $xpath->query( '//ratereplydetails[servicetype="' . $code . '"]//totalnetcharge/amount' );

			$rate = 0;
			if ( ! is_null( $nodes->item( 0 ) ) ) {
				$rate = (float) $nodes->item( 0 )->textContent;
			}

			if ( 0 == $rate ) {
				// Not available for this combination
				unset( $mp_shipping_options[ $code ] );
			} else {
				$handling                     = ( $international ) ? (float) $this->get_setting( 'intl_handling' ) : (float) $this->get_setting( 'domestic_handling' );
				$handling                     = ( $handling * $this->pkg_count ); // Add handling times number of packages.
				$delivery                     = $service->delivery;
				$mp_shipping_options[ $code ] = array(
					'rate'     => $rate,
					'delivery' => $delivery,
					'handling' => $handling,
				);
			}
		}

		//Sort low to high rate
		uasort( $mp_shipping_options, array( $this, 'compare_rates' ) );

		//Update the session. Save the currently calculated CRCs
		$this->_crc_update( $mp_shipping_options );

		unset( $xpath );
		unset( $dom );

		return $this->_format_shipping_options( $mp_shipping_options );
	}
}

if ( ! class_exists( 'FedEx_Service' ) ):
	class FedEx_Service {
		public $code;
		public $name;
		public $delivery;
		public $rate;

		function __construct( $code, $name, $delivery, $rate = null ) {
			$this->code     = $code;
			$this->name     = $name;
			$this->delivery = $delivery;
			$this->rate     = $rate;
		}
	}
endif;

MP_Shipping_API::register_plugin( 'MP_Shipping_FedEx', 'fedex', __( 'FedEx (beta)', 'mp' ), true );
