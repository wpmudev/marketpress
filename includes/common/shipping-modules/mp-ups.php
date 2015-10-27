<?php
/*
MarketPress UPS Calculated Shipping Plugin
Author: Arnold Bailey (Incsub)
*/

class MP_Shipping_UPS extends MP_Shipping_API_Calculated {
	//build of the plugin
	public $build = 2;

	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	public $plugin_name = 'ups';

	//public name of your method, for lists and such.
	public $public_name = '';

	//set to true if you need to use the shipping_metabox() method to add per-product shipping options
	public $use_metabox = true;

	//set to true if you want to add per-product extra shipping cost field
	public $use_extra = true;

	//set to true if you want to add per-product weight shipping field
	public $use_weight = true;

	//Test sandboxed URI for UPS Rates API
	public $sandbox_uri = 'https://wwwcie.ups.com/ups.app/xml/Rate';

	//Production Live URI for UPS Rates API
	public $production_uri = 'https://onlinetools.ups.com/ups.app/xml/Rate';

	// Defines the available shipping Services and their display names
	public $services = array();

	//Set to display any errors in the Rate calculations.
	private $rate_error = '';

	/**
	* Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	*/
	function on_creation() {
		//set name here to be able to translate
		$this->public_name = __('UPS', 'mp');

		//US Domestic services
		$this->services = array(
			'Next Day Air'           => new UPS_Service('01', __('Next Day Air', 'mp'),           __('(Next Day)', 'mp')),
			'2nd Day Air'            => new UPS_Service('02', __('2nd Day Air', 'mp'),            __('(2 Days)', 'mp')),
			'Ground'                 => new UPS_Service('03', __('Ground', 'mp'),                 __('(1-5 Days)', 'mp')),
			'3 Day Select'           => new UPS_Service('12', __('3 Day Select', 'mp'),           __('(3 Days)', 'mp')),
			'Next Day Air Saver'     => new UPS_Service('13', __('Next Day Air Saver', 'mp'),     __('(1 Day)', 'mp')),
			'Next Day Air Early AM'  => new UPS_Service('14', __('Next Day Air Early AM', 'mp'),  __('(1 Days)', 'mp')),
			'2nd Day Air AM'         => new UPS_Service('59', __('2nd Day Air AM', 'mp'),         __('(2 Days)', 'mp')),
			'Worldwide Express' 		 => new UPS_Service('07', __('Worldwide Express', 'mp'),      __('(1-3 Days)', 'mp')),
			'Worldwide Expedited'    => new UPS_Service('08', __('Worldwide Expedited', 'mp'),    __('(2-5 Days)', 'mp') ),
			'Standard'               => new UPS_Service('11', __('Standard', 'mp'),               __('(Scheduled)', 'mp') ),
			'Worldwide Express Plus' => new UPS_Service('54', __('Worldwide Express Plus', 'mp'), __('(1-3 Days)', 'mp') ),
			'Saver'                  => new UPS_Service('65', __('Saver', 'mp'),                  __('(1-5 Days)', 'mp') ),
		);
		
		//		//International Services
		//		$this->intl_services = array(
		//		'Worldwide Express' 		 => new UPS_Service('07', __('Worldwide Express', 'mp') ),
		//		'Worldwide Expedited'    => new UPS_Service('08', __('Worldwide Expedited', 'mp') ),
		//		'Standard'               => new UPS_Service('11', __('Standard', 'mp') ),
		//		'Worldwide Express Plus' => new UPS_Service('54', __('Worldwide Express Plus', 'mp') ),
		//		'Saver'                  => new UPS_Service('65', __('Saver', 'mp') ),
		//		);
	}

	function default_boxes(){
		// Initialize the default boxes if nothing there
		$boxes = $this->get_setting('boxes->name', array());
		
		if ( count($boxes) < 2 ) {
			return array(
				array(
					'name' => 'Small Express',
					'size' => '13x11x2',
					'weight' => '10',
				),
				array(
					'name' => 'Medium Express',
					'size' => '15x11x3',
					'weight' => '20',
				),
				array(
					'name' => 'Large Express',
					'size' => '18x13x3',
					'weight' => '30',
				),
				array(
					'name' => 'UPS 10KG',
					'size' => '17x13x11',
					'weight' => '22',
				),
				array(
					'name' => 'UPS 25KG',
					'size' => '19x17x14',
					'weight' => '55',
				),
			);
		}
		
		return array();
	}

  /**
   * Initialize the settings metabox
   *
   * @since 3.0
   * @access public
   */
  public function init_settings_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => $this->generate_metabox_id(),
			'page_slugs' => array(
				'store-settings-shipping',
				'store-settings_page_store-settings-shipping',
				'store-setup-wizard'
			),
			'title' => sprintf(__('%s Settings', 'mp'), $this->public_name),
			'desc' => __('In order to use UPS, you will need a UPS Developer Kit access key and the UPS user ID and password associated with the access key.  Set these up for free <a href="https://www.ups.com/upsdeveloperkit" target="_blank">here</a>. If this information is missing or incorrect, an error will appear during the checkout process and the buyer will not be able to complete the transaction.', 'mp'),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'operator' => 'AND',
				'action' => 'show',
				array(
					'name' => 'shipping[method]',
					'value' => 'calculated',
				),
				array(
					'name' => 'shipping[calc_methods][ups]',
					'value' => 'ups',
				),
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => $this->get_field_name('sandbox'),
			'label' => array('text' => __('Use Sandbox Mode?', 'mp')),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('api_key'),
			'label' => array('text' => __('Developer Kit Access Key', 'mp')),
			'validation' => array(
				'required' => true,
			),
		)); 
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('user_id'),
			'label' => array('text' => __('User ID', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('password'),
			'label' => array('text' => __('Password', 'mp')),
			'validation' => array(
				'required' => true,
			),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('shipper_number'),
			'label' => array('text' => __('Shipper Number', 'mp')),
			'desc' => __('Required if using negotiated rates', 'mp'),
		));
		
		$services = array();
		foreach ( $this->services as $name => $service ) {
			$services[$name] = $service->name;	
		}
		
		$metabox->add_field('checkbox_group', array(
			'name' => $this->get_field_name('services'),
			'label' => array('text' => __('Offered Services', 'mp')),
			'options' => $services,
		));
		$metabox->add_field('radio_group', array(
			'name' => $this->get_field_name('pickup_type'),
			'label' => array('text' => __('Offered Services', 'mp')),
			'desc' => __('For the most accurate rates, please select the appropriate pick up type for your business.', 'mp'),
			'options' => array('01' => __('Daily Pickup', 'mp'), '03' => __('Customer Counter', 'mp'), '06' => __('One Time Pickup', 'mp')),
		));
		$metabox->add_field('text', array(
			'name' => $this->get_field_name('domestic_handling'),
			'label' => array('text' => __('Handling Charge Per Box Shipped', 'mp')),
			'default_value' => '0.00',
		));
		
		$boxes = $metabox->add_field('repeater', array(
			'name' => $this->get_field_name('boxes'),
			'label' => array('text' => __('Standard Boxes and Weight Limits', 'mp')),
			'default_value' => $this->default_boxes(),
			'desc' => __('Enter your standard box sizes as LengthxWidthxHeight (e.g. 12x8x6) For each box defined enter the maximum weight it can contain. <strong>Note: the shipping prices this plugin calculates are estimates. If they are consistently too low or too high, please check that the list of boxes above and the product weights are accurate and complete.</strong>', 'mp'),
			'add_row_label' => __('Add Box', 'mp'),
		));
		
		if ( $boxes instanceof WPMUDEV_Field ) {
			$boxes->add_sub_field('text', array(
				'name' => 'name',
				'label' => array('text' => __('Name', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$boxes->add_sub_field('text', array(
				'name' => 'size',
				'label' => array('text' => sprintf( __( 'Size (%s)', 'mp' ), mp_dimension_label() ) ),
				'validation' => array(
					'required' => true,
				),
			));
			$boxes->add_sub_field('text', array(
				'name' => 'weight',
				'label' => array( 'text' => sprintf( __( 'Max Weight (%s)', 'mp' ), mp_weight_label() ) ),
				'validation' => array(
					'required' => true,
					'number' => true,
					'min' => 0,
				),
			));
		}
  }

	/**
	* For calculated shipping modules, use this method to return an associative
	* array of the sub-options. The key will be what's saved as selected in the
	* session. Note the shipping parameters won't always be set. If they are, add
	* the prices to the labels for each option.
	*
	* @param array $cart The contents of the shopping cart for advanced calculations
	* @param string $address1
	* @param string $address2
	* @param string $city
	* @param string $state State/province/region
	* @param string $zip Postal code
	* @param string $country ISO 3166-1 alpha-2 country code
	* return array $shipping_options
	*/
	function shipping_options( $cart, $address1, $address2, $city, $state, $zip, $country ) {
		if ( $this->_crc_ok() && false !== ($shipping_options = mp_get_session_value('mp_shipping_options->' . $this->plugin_name)) ) {
			// CRC is ok - just return the shipping options already stored in session
			return $this->_format_shipping_options( $shipping_options );
		}
		
		$shipping_options = array();

		$this->address1 = $address1;
		$this->address2 = $address2;
		$this->city = $city;
		$this->state = $state;
		$this->destination_zip = $zip;
		$this->country = $country;
		$this->weight = $cart->shipping_weight();
		
		if( $this->weight == 0 ) {
			// Nothing to ship
			return $this->_free_shipping();
		}

		// Got our totals  make sure we're in decimal pounds.
		$this->weight = $this->_as_pounds( $this->weight );

		//ups won't accept a zero weight Package
		$this->weight = ($this->weight == 0) ? 0.1 : $this->weight;

		$max_weight = 75;

		//Properties should already be converted to weight in decimal pounds and Pounds and Ounces
		//Figure out how many boxes
		$this->pkg_count = ceil($this->weight / $max_weight); // Avoid zero
		// Equal size packages.
		$this->pkg_weight = $this->weight / $this->pkg_count;

		// Fixup pounds by converting multiples of 16 ounces to pounds
		$this->pounds = intval($this->pkg_weight);
		$this->ounces = round(($this->pkg_weight - $this->pounds) * 16);

		if ( $this->get_setting('base_country') == 'US' ) {
			// Can't use zip+4
			$this->base_zip = substr(mp_get_setting('base_zip'), 0, 5);
		}

		if ( $this->country == 'US' ) {
			// Can't use zip+4
			$this->destination_zip = substr($this->destination_zip, 0, 5);
		}
		
		$shipping_options = $this->rate_request();
		
		return $shipping_options;

	}

	/**
	* rate_request - Makes the actual call to UPS
	*/
	function rate_request() {
		$shipping_options = array_filter( $this->get_setting( 'services', array() ), create_function( '$val', 'return ($val == 1);' ) );

		//Assume equal size packages. Find the best matching box size
		$boxes = (array) $this->get_setting( 'boxes' );
		$box = $largest_box = false;
		$index = 1;
		$box_count = count( $boxes );
		
		if ( $box_count == 0 ) {
			return false;
		}

		foreach ( $boxes as $thebox ) {
			// Find largest box
			if ( $thebox['weight'] > $this->weight || ($index == $box_count && $box === false) ) {
				$largest_box = $thebox;
			}
			
			if ( floatval( $this->weight ) <= floatval( $thebox['weight'] ) || ( $index == $box_count && $box === false ) ) {
				$box = $thebox;
				break;
			}
			
			$index ++;
		}
		
		if ( $box['weight'] >= $this->weight ) {
			$this->pkg_count = 1;
			$this->pkg_weight = $this->weight;
		} else {
			$this->pkg_count = ceil( $this->weight / $box['weight'] ); // Avoid zero
			$this->pkg_weight = $this->weight / $this->pkg_count;
		}

		// Fix up pounds by converting multiples of 16 ounces to pounds
		$this->pounds = intval( $this->pkg_weight );
		$this->ounces = round( ($this->pkg_weight - $this->pounds) * 16 );

		//found our box
		$dims = explode( 'x', strtolower( $box['size'] ) );
		foreach($dims as &$dim) {
			$dim = $this->_as_inches( $dim );
		}

		sort( $dims ); //Sort so two lowest values are used for Girth

		//Build Authorization XML
		$auth_dom = new DOMDocument('1.0', 'utf-8');
		$auth_dom->formatOutput = true;
		$root = $auth_dom->appendChild($auth_dom->createElement('AccessRequest'));
		$root->setAttribute('xml:lang', 'en-US');
		$root->appendChild($auth_dom->createElement('AccessLicenseNumber', $this->get_setting('api_key')));
		$root->appendChild($auth_dom->createElement('UserId', $this->get_setting('user_id')));
		$root->appendChild($auth_dom->createElement('Password', $this->get_setting('password')));

		//Rate request XML
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$root = $dom->appendChild($dom->createElement('RatingServiceSelectionRequest'));
		$root->setAttribute('xml:lang', 'en-US');

		$request = $root->appendChild($dom->createElement('Request'));

		$transaction = $request->appendChild($dom->createElement('TransactionReference'));
		$transaction->appendChild($dom->createElement('CustomerContext','MarketPress Rate Request'));
		$transaction->appendChild($dom->createElement('XpciVersion','1.0001'));

		$request->appendChild($dom->createElement('RequestAction', 'Rate'));
		$request->appendChild($dom->createElement('RequestOption', 'Shop'));

		$pickup = $root->appendChild($dom->createElement('PickupType'));
		$pickup->appendChild($dom->createElement('Code', $this->get_setting('pickup_type')));

		//Shipper
		$shipment = $root->appendChild($dom->createElement('Shipment'));
		$shipment->appendChild($dom->createElement('NegotiatedRatesIndicator'));
		$shipper = $shipment->appendChild($dom->createElement('Shipper'));
		$shipper->appendChild($dom->createElement('ShipperNumber', htmlentities($this->get_setting('shipper_number'))));
		$address = $shipper->appendChild($dom->createElement('Address'));
		$address->appendChild($dom->createElement('StateProvinceCode', $this->get_setting('base_province')));
		$address->appendChild($dom->createElement('PostalCode', $this->get_setting('base_zip')));
		$address->appendChild($dom->createElement('CountryCode', $this->get_setting('base_country')));
		//Ship to
		$shipto = $shipment->appendChild($dom->createElement('ShipTo'));
		$address = $shipto->appendChild($dom->createElement('Address'));
		$address->appendChild($dom->createElement('AddressLine1', $this->address1));
		$address->appendChild($dom->createElement('AddressLine2', $this->address2));
		$address->appendChild($dom->createElement('City', $this->city));
		$address->appendChild($dom->createElement('StateProvinceCode', $this->state));
		$address->appendChild($dom->createElement('PostalCode', $this->destination_zip));
		$address->appendChild($dom->createElement('CountryCode', $this->country));
		//Ship from
		$shipfrom = $shipment->appendChild($dom->createElement('ShipFrom'));
		$address = $shipfrom->appendChild($dom->createElement('Address'));
		$address->appendChild($dom->createElement('StateProvinceCode', mp_get_setting('base_province')));
		$address->appendChild($dom->createElement('PostalCode', mp_get_setting('base_zip')));
		$address->appendChild($dom->createElement('CountryCode', mp_get_setting('base_country')));
		//Package
		$package = $shipment->appendChild($dom->createElement('Package'));

		$packaging_type = $package->appendChild($dom->createElement('PackagingType') );
		$packaging_type->appendChild($dom->createElement('Code', '00'));

		//Dimensions
		$dimensions = $package->appendChild($dom->createElement('Dimensions') );
		$uom = $dimensions->appendChild($dom->createElement('UnitOfMeasurement') );
		$uom->appendChild($dom->createElement('Code', ( mp_get_setting('shipping->system', 'english') == 'english' ) ? 'IN' : 'CM'));
		$dimensions->appendChild($dom->createElement('Length', $dims[1]) );
		$dimensions->appendChild($dom->createElement('Width', $dims[2]) );
		$dimensions->appendChild($dom->createElement('Height', $dims[0]) );
		//Weight
		$package_weight = $package->appendChild($dom->createElement('PackageWeight') );
		$uom = $package_weight->appendChild($dom->createElement('UnitOfMeasurement') );
		$uom->appendChild($dom->createElement('Code', ( mp_get_setting('shipping->system', 'english') == 'english' ) ? 'LBS' : 'KGS'));
		$package_weight->appendChild($dom->createElement('Weight', $this->pkg_weight) );


		//We have the XML make the call
		$url = ( $this->get_setting('sandbox') ) ? $this->sandbox_uri : $this->production_uri;

		$response = wp_remote_request($url, array(
			'headers' => array('Content-Type: text/xml'),
			'method' => 'POST',
			'body' => $auth_dom->saveXML() . $dom->saveXML(),
			'sslverify' => false,
		));

		if ( is_wp_error($response) ) {
			return array('error' => '<div class="mp_checkout_error">UPS: ' . $response->get_error_message() . '</div>');
		} else {
			$loaded = ( $response['response']['code'] == '200' );
			$body = $response['body'];
			if ( ! $loaded ) {
				return array('error' => '<div class="mp_checkout_error">UPS: ' . $response['response']['code'] . "&mdash;" . $response['response']['message'] . '</div>');
			}
		}

		$dom = $this->_parse_xml( $body );

		//Process the return XML
		//Clear any old price
		unset($_SESSION['mp_shipping_info']['shipping_cost']);

		$xpath = new DOMXPath($dom);

		//Check for errors
		$nodes = $xpath->query('//responsestatuscode');
		if( $nodes->item(0)->textContent == '0' ) {
			$nodes = $xpath->query('//errordescription');
			$this->rate_error = $nodes->item(0)->textContent;
			return array('error' => '<div class="mp_checkout_error">' . $this->rate_error . '</div>');
		}

		//Good to go
		//Make SESSION copy with just prices and delivery

		if (! is_array($shipping_options) ) $shipping_options = array();
		$mp_shipping_options = $shipping_options;
		
		foreach ( $shipping_options as $service => $option ) {
			$nodes = $xpath->query('//ratedshipment[service/code="' . $this->services[ $service ]->code . '"]/totalcharges/monetaryvalue');
			$node = $nodes->item(0);
			
			if ( is_null( $node ) ) {
				// This service isn't availble to the buyer's address
				unset( $mp_shipping_options[ $service ] );
				continue;
			}
			
			$rate = floatval( $node->nodeValue ) * $this->pkg_count;

			if ( $rate == 0) {  //Not available for this combination
				unset( $mp_shipping_options[ $service ] );
			} else {
				$handling = floatval($this->get_setting('domestic_handling')) * $this->pkg_count; // Add handling times number of packages.
				$delivery = $this->services[$service]->delivery;
				$mp_shipping_options[$service] = array('rate' => $rate, 'delivery' => $delivery, 'handling' => $handling);
			}
		}

		uasort( $mp_shipping_options, array( $this,'compare_rates' ) );

		//Update the session. Save the currently calculated CRCs
		$this->_crc_update( $mp_shipping_options );
		
		unset($xpath);
		unset($dom);

		return $this->_format_shipping_options( $mp_shipping_options );
	}
} //End MP_Shipping_UPS


if(! class_exists('UPS_Service') ):
class UPS_Service
{
	public $code;
	public $name;
	public $delivery;
	public $rate;

	function __construct($code, $name, $delivery, $rate = null)
	{
		$this->code = $code;
		$this->name = $name;
		$this->delivery = $delivery;
		$this->rate = $rate;

	}
}
endif;

if(! class_exists('Box_Size') ):
class Box_Size
{
	public $length;
	public $width;
	public $height;

	function __construct($length, $width, $height)
	{
		$this->length = $length;
		$this->width = $width;
		$this->height = $height;
	}
}
endif;

//register plugin only in US and US Possesions

$settings = get_option('mp_settings');

//if(in_array($settings['base_country'], array('US','UM','AS','FM','GU','MH','MP','PW','PR','PI')))
{
	MP_Shipping_API::register_plugin('MP_Shipping_UPS', 'ups', __('UPS (beta)', 'mp'), true);
}
