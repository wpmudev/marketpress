<?php

/*
MarketPress USPS Calculated Shipping Plugin
Author: Arnold Bailey (Incsub)
*/

class MP_Shipping_USPS extends MP_Shipping_API_Calculated {
	//build of the plugin
	public $build = 2;

	//base zip
	public $base_zip = null;

	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	public $plugin_name = 'usps';

	//public name of your method, for lists and such.
	public $public_name = '';

	//set to true if you need to use the shipping_metabox() method to add per-product shipping options
	public $use_metabox = true;

	//set to true if you want to add per-product extra shipping cost field
	public $use_extra = true;

	//set to true if you want to add per-product weight shipping field
	public $use_weight = true;

	//Test sandboxed URI for USPS Rates API (Currently boken of RateV4 at USPS)
	public $test_uri = 'http://testing.shippingapis.com/ShippingAPITest.dll';

	//Production Live URI for USPS Rates API
	public $production_uri = 'http://production.shippingapis.com/ShippingAPI.dll';

	// Defines the available shipping Services and their display names
	public $services = '';

	// Maximum weight for a single Package
	public $max_weight = 70;

	public $weight = 0;
	public $pound = 0;
	public $ounces = 0;
	public $width = 0;
	public $length = 0;
	public $height = 0;
	public $girth = 0;
	public $machinable = 'true';
	public $size = 'REGULAR';
	public $origin_zip = '';
	public $destination_zip = '';

	public $domestic_handling = 0;
	public $intl_handling = 0;

	private $pkg_count = 0;
	private $pkg_weight = 0;
	private $pkg_max = 0;
	private $pkg_dims = array( 12, 12, 12 );

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//set name here to be able to translate
		$this->public_name = __( 'USPS', 'mp' );

		//Key is the enumeration for the USPS Services XML field
		$this->services = array(
			'Express Mail'                                         =>
				new USPS_Service( 3, __( 'Express Mail', 'mp' ), __( '(1-2 days)', 'mp' ) ),
			'Express Mail Hold For Pickup'                         =>
				new USPS_Service( 2, __( 'Express Mail Hold For Pickup', 'mp' ), __( '(1-2 days)', 'mp' ) ),
			'Express Mail Sunday/Holiday Delivery'                 =>
				new USPS_Service( 3, __( 'Express Mail Sunday/Holiday Delivery', 'mp' ), __( '(1-2 days)', 'mp' ) ),
			'Express Mail Flat Rate Boxes'                         =>
				new USPS_Service( 55, __( 'Express Mail Flat Rate Boxes', 'mp' ), __( '(1-2 days)', 'mp' ), 50 ),
			'Express Mail Flat Rate Boxes Hold For Pickup'         =>
				new USPS_Service( 56, __( 'Express Mail Flat Rate Boxes Hold For Pickup', 'mp' ), __( '(1-2 days)', 'mp' ), 50 ),
			'Express Mail Sunday/Holiday Delivery Flat Rate Boxes' =>
				new USPS_Service( 57, __( 'Express Mail Sunday/Holiday Delivery Flat Rate Boxes', 'mp' ), __( '(1-2 days)', 'mp' ) ),
			'Priority Mail'                                        =>
				new USPS_Service( 1, __( 'Priority Mail', 'mp' ), __( '(2-4) days', 'mp' ) ),
			'Priority Mail Large Flat Rate Box'                    =>
				new USPS_Service( 22, __( 'Priority Mail Large Flat Rate Box', 'mp' ), __( '(2-4 days)', 'mp' ), 30 ),
			'Priority Mail Medium Flat Rate Box'                   =>
				new USPS_Service( 17, __( 'Priority Mail Medium Flat Rate Box', 'mp' ), __( '(2-4 days)', 'mp' ), 20 ),
			'Priority Mail Small Flat Rate Box'                    =>
				new USPS_Service( 28, __( 'Priority Mail Small Flat Rate Box', 'mp' ), __( '(2-4 days)', 'mp' ), 3 ),
			'Padded Flat Rate Envelope'                            =>
				new USPS_Service( 29, __( 'Priority Mail Padded Flat Rate Envelope', 'mp' ), __( '(2-4 days)', 'mp' ), 2 ),
			'First-Class Mail Parcel'                              =>
				new USPS_Service( 0, __( 'First-Class Mail Parcel', 'mp' ), __( '(2-4 days)', 'mp' ) ),
			'Media Mail'                                           =>
				new USPS_Service( 6, __( 'Media Mail', 'mp' ), '' ),
			'Library Mail'                                         =>
				new USPS_Service( 7, __( 'Library Mail', 'mp' ), '' ),

		);

		$this->intl_services = array(
			'Express Mail International'                                    =>
				new USPS_Service( 1, __( 'Express Mail International', 'mp' ) ),
			'Express Mail International Flat Rate Boxes'                    =>
				new USPS_Service( 26, __( 'Express Mail International Flat Rate Boxes', 'mp' ), '', 50 ),
			'Priority Mail International'                                   =>
				new USPS_Service( 2, __( 'Priority Mail International', 'mp' ) ),
			'Priority Mail International Large Flat Rate Boxes'             =>
				new USPS_Service( 11, __( 'Priority Mail International Large Flat Rate Boxes', 'mp' ), '', 30 ),
			'Priority Mail International Medium Flat Rate Boxes'            =>
				new USPS_Service( 9, __( 'Priority Mail International Medium Flat Rate Boxes', 'mp' ), '', 20 ),
			'Priority Mail International Small Flat Rate Boxes'             =>
				new USPS_Service( 16, __( 'Priority Mail International Small Flat Rate Boxes', 'mp' ), '', 3 ),
			'Priority Mail Express International Padded Flat Rate Envelope' =>
				new USPS_Service( 27, __( 'Priority Mail Express International Padded Flat Rate Envelope', 'mp' ), __( '(3-5 days)', 'mp' ), 2 ),
			'First Class International Parcel'                              =>
				new USPS_Service( 15, __( 'First Class International Parcel', 'mp' ) ),
		);

		$this->base_zip = mp_get_setting( 'base_zip' );
	}

	function default_boxes() {
		// Initialize the default boxes if nothing there
		$boxes = $this->get_setting( 'boxes', array() );
		if ( count( $boxes ) <= 1 ) {
			return array(
				array(
					'name'   => __( 'Flat Rate Small', 'mp' ),
					'size'   => '8x6x1.6',
					'weight' => '3',
				),
				array(
					'name'   => __( 'Flat Rate Medium 1', 'mp' ),
					'size'   => '11x8.5x5.5',
					'weight' => '20',
				),
				array(
					'name'   => __( 'Flat Rate Medium 2', 'mp' ),
					'size'   => '13.6x11.9.5x3.4',
					'weight' => '20',
				),
				array(
					'name'   => __( 'Flat Rate Large', 'mp' ),
					'size'   => '12x12x5.5',
					'weight' => '30',
				),
			);
		}
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
			'desc'        => __( 'Using this USPS Shipping calculator requires requesting an Ecommerce API Username and Password. Get your free set of credentials <a target="_blank" href="https://registration.shippingapis.com/">here &raquo;</a>. The password is no longer used for the API, just the username which you should enter below. The USPS test site has not yet been updated and currently doesn\'t work - you should just request activating your credentials with USPS and go live.', 'mp' ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'shipping[method]',
					'value' => 'calculated',
				),
				array(
					'name'  => 'shipping[calc_methods][usps]',
					'value' => 'usps',
				),
			),
		) );
		$metabox->add_field( 'text', array(
			'name'  => $this->get_field_name( 'api_username' ),
			'label' => array( 'text' => __( 'Username', 'mp' ) ),
		) );
		$metabox->add_field( 'text', array(
			'name'  => $this->get_field_name( 'max_weight' ),
			'label' => array( 'text' => __( 'Maximum Weight Per Package', 'mp' ) ),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => $this->get_field_name( 'online' ),
			'label'   => array( 'text' => __( 'Rates Request Type', 'mp' ) ),
			'options' => array(
				'online' => __( 'Online Rates', 'mp' ),
				'retail' => __( 'Retail Rates', 'mp' ),
			),
		) );

		$services = array();
		foreach ( $this->services as $service => $detail ) {
			$services[ $service ] = $detail->name . ' ' . $detail->delivery;
		}
		$metabox->add_field( 'checkbox_group', array(
			'name'    => $this->get_field_name( 'services' ),
			'label'   => array( 'text' => __( 'Offered Domestic Services', 'mp' ) ),
			'options' => $services,
			'width'   => '50%',
		) );

		$metabox->add_field( 'text', array(
			'name'  => $this->get_field_name( 'domestic_handling' ),
			'label' => array( 'text' => __( 'Handling Charge per Domestic Shipment', 'mp' ) ),
		) );

		$services = array();
		foreach ( $this->intl_services as $service => $detail ) {
			$services[ $service ] = $detail->name . ' ' . $detail->delivery;
		}
		$metabox->add_field( 'checkbox_group', array(
			'name'    => $this->get_field_name( 'intl_services' ),
			'label'   => array( 'text' => __( 'Offered International Services', 'mp' ) ),
			'options' => $services,
			'width'   => '50%',
		) );

		$metabox->add_field( 'text', array(
			'name'  => $this->get_field_name( 'intl_handling' ),
			'label' => array( 'text' => __( 'Handling Charge per International Shipment', 'mp' ) ),
		) );

		$boxes = $metabox->add_field( 'repeater', array(
			'name'          => $this->get_field_name( 'boxes' ),
			'label'         => array( 'text' => __( 'Standard Boxes and Weight Limits', 'mp' ) ),
			'default_value' => $this->default_boxes(),
			'desc'          => __( 'Enter your standard box sizes as LengthxWidthxHeight (e.g. 12x8x6) For each box defined enter the maximum weight it can contain. <strong>Note: the shipping prices this plugin calculates are estimates. If they are consistently too low or too high, please check that the list of boxes above and the product weights are accurate and complete.</strong>', 'mp' ),
			'add_row_label' => __( 'Add Box', 'mp' ),
		) );

		if ( $boxes instanceof WPMUDEV_Field ) {
			$boxes->add_sub_field( 'text', array(
				'name'  => 'name',
				'label' => array( 'text' => __( 'Name', 'mp' ) ),
			) );
			$boxes->add_sub_field( 'text', array(
				'name'  => 'size',
				'label' => array( 'text' => sprintf( __( 'Size (%s)', 'mp' ), mp_dimension_label() ) ),
			) );
			$boxes->add_sub_field( 'text', array(
				'name'  => 'weight',
				'label' => array( 'text' => sprintf( __( 'Max Weight (%s)', 'mp' ), mp_weight_label() ) ),
			) );
		}
	}

	/**
	 * Echo a settings meta box with whatever settings you need for you shipping module.
	 *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
	 *  You can access saved settings via $settings array.
	 */
	function shipping_settings_box( $settings ) {
		?>
		<div id="mp_usps_rate" class="postbox">
			<h3 class='hndle'><span><?php _e( 'USPS Settings', 'mp' ); ?></span></h3>

			<div class="inside">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><?php _e( 'USPS Offered Domestic Services', 'mp' ) ?></th>
						<td>
							<?php foreach ( $this->services as $service => $detail ): ?>
								<label>
									<input type="checkbox" name="mp[shipping][usps][services][<?php echo $service; ?>]"
									       value="1" <?php checked( $this->get_setting( "services->$service" ) ); ?> />&nbsp;<?php echo $detail->name . $detail->delivery; ?>
								</label>

								<?php
								if ( isset( $detail->max_weight ) ):
									$max_weight = $this->get_setting( "flat_weights->$service", $detail->max_weight );
									?>
									<?php _e( '@ Max', 'mp' ); ?> <input type="text" size="1"
									                                     name="mp[shipping][usps][flat_weights][<?php echo $service; ?>]"
									                                     value="<?php esc_attr_e( $max_weight, 'mp' ); ?>"/>
									<?php echo $this->_get_units_weight(); ?>
								<?php endif; ?>

								<br/>
							<?php endforeach; ?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e( 'Handling Charge per Domestic Shipment ', 'mp' ) ?></th>
						<td>
							<input type="text" name="mp[shipping][usps][domestic_handling]"
							       value="<?php echo esc_attr( $this->get_setting( 'domestic_handling', '0.00' ) ); ?>"
							       size="20" maxlength="20"/>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e( 'USPS Offered International Services', 'mp' ) ?></th>
						<td>
							<?php foreach ( $this->intl_services as $service => $detail ): ?>
								<label>
									<input type="checkbox"
									       name="mp[shipping][usps][intl_services][<?php echo $service; ?>]"
									       value="1" <?php checked( $this->get_setting( "intl_services->$service" ) ); ?> />&nbsp;<?php echo $detail->name; ?>
								</label>

								<?php
								if ( isset( $detail->max_weight ) ):
									$max_weight = $this->get_setting( "flat_weights->$service", $detail->max_weight );
									?>
									<?php _e( '@ Max', 'mp' ); ?> <input type="text" size="1"
									                                     name="mp[shipping][usps][flat_weights][<?php echo $service; ?>]"
									                                     value="<?php esc_attr_e( $max_weight, 'mp' ); ?>"/>
									<?php echo $this->_get_units_weight(); ?>
								<?php endif; ?>

								<br/>
							<?php endforeach; ?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e( 'Handling Charge per Interntional Shipment', 'mp' ) ?></th>
						<td>
							<input type="text" name="mp[shipping][usps][intl_handling]"
							       value="<?php echo esc_attr( $this->get_setting( 'intl_handling', '0.00' ) ); ?>"
							       size="20" maxlength="20"/>
						</td>
					</tr>

					<tr>
						<th scope="row" colspan="2">
							<?php _e( 'Standard Boxes and Weight Limits', 'mp' ) ?>
							<p>
									<span class="description">
										<?php _e( 'Enter your standard box sizes as LengthxWidthxHeight', 'mp' ) ?>
										( <b>12x8x6</b> )
										<?php _e( 'For each box defined enter the maximum weight it can contain.', 'mp' ) ?>
										<?php _e( 'Total weight selects the box size used for calculating Shipping costs.', 'mp' ) ?>
									</span>
							</p>
						</th>
					</tr>
					<tr>
						<td colspan="2">
							<table class="widefat" id="mp_shipping_boxes_table">
								<thead>
								<tr>
									<th scope="col" class="mp_box_name"><?php _e( 'Box Name', 'mp' ); ?></th>
									<th scope="col"
									    class="mp_box_dimensions"><?php _e( 'Box Dimensions', 'mp' ); ?></th>
									<th scope="col"
									    class="mp_box_weight"><?php _e( 'Max Weight per Box', 'mp' ); ?></th>
									<th scope="col" class="mp_box_remove"></th>
								</tr>
								</thead>
								<tbody>
								<?php
								$this->default_boxes();
								if ( $this->get_setting( 'boxes' ) ) {
									foreach ( $this->get_setting( 'boxes->name' ) as $key => $value ) {
										$this->box_row_html( $key );
									}
								}
								//Add blank line for new entries. The non numeric $key says it's not in the array.
								$this->box_row_html( '' );
								?>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php

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

		add_filter( 'mp_list_shipping_options', array( $this, 'after_mp_shipping_options'), 10, 2 );
		add_filter( 'mp_checkout_step_link', array( $this, 'checkout_step_link_handler'), 10, 4 );

		if ( $this->_crc_ok() && false !== ( $shipping_options = mp_get_session_value( 'mp_shipping_options->' . $this->plugin_name ) ) ) {
			if ( ! empty( $shipping_options ) ) {
				// CRC is ok - just return the shipping options already stored in session
				return $this->_format_shipping_options( $shipping_options );
			}
		}

		// Not ok then calculate them
		$settings = get_option( 'mp_settings' );

		$this->weight     = 0;
		$this->pound      = 0;
		$this->ounces     = 0;
		$this->width      = 0;
		$this->length     = 0;
		$this->height     = 0;
		$this->girth      = 0;
		$this->pkg_max    = 0;
		$this->machinable = 'true';
		$this->size       = 'REGULAR';

		$this->country         = $country;
		$this->destination_zip = $zip;

		$items = $cart->get_items();

		if ( is_array( $items ) ) {
			foreach ( $items as $product_id => $qty ) {
				$product       = new MP_Product( $product_id );
				$weight        = $product->get_weight();
				$this->pkg_max = max( $this->pkg_max, $weight );
				$this->weight += ( $weight * $qty );
			}
		}
		//If whole shipment is zero weight then there's nothing to ship. Return Free Shipping
		if ( $this->weight == 0 ) { //Nothing to ship
			$_SESSION['mp_shipping_info']['shipping_sub_option'] = __( 'Free Shipping', 'mp' );
			$_SESSION['mp_shipping_info']['shipping_cost']       = 0;

			return array( __( 'Free Shipping', 'mp' ) => __( 'Free Shipping - 0.00', 'mp' ) );
		}

		// Got our totals  make sure we're in decimal pounds.
		$this->weight  = $this->_as_pounds( $this->weight );
		$this->pkg_max = $this->_as_pounds( $this->pkg_max );

		//USPS won't accept a zero weight Package
		$this->weight = max( $this->weight, 0.1 );

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
		) ) ) {
			// Can't use zip+4
			$this->base_zip = substr( $this->base_zip, 0, 5 );
		}

		if ( in_array( $this->country, array( 'US', 'UM', 'AS', 'FM', 'GU', 'MH', 'MP', 'PW', 'PR', 'PI' ) ) ) {
			// Can't use zip+4
			$this->destination_zip = substr( $this->destination_zip, 0, 5 );
			$shipping_options      = $this->ratev4_request();
		} else {
			$shipping_options = $this->ratev2_request();
		}

		return $shipping_options;
	}

	/**
	 *  Filter when to display the next step button.
	 *
	 * @param $link
	 * @param $what
	 * @param $section
	 * @param $step_number
	 */
	function checkout_step_link_handler( $link, $what, $section, $step_number ){
		if ( $this->_crc_ok() && false !== ( $shipping_options = mp_get_session_value( 'mp_shipping_options->' . $this->plugin_name ) ) ) {
			if ( empty( $shipping_options ) ) {
				return '';
			}
		}

		return $link;
	}

	/**
	 * Add output after shipping options in checkout steps.
	 *
	 * @param $html
	 * @param $options
	 */
	function after_mp_shipping_options( $html, $options ){
		if( empty( $options) ){
			$html .= '<span class="mp_usps_after_shipping_options no_options_available">';
			$html .= __( 'There are no shipping services available for this type of package in your location.', 'mp' );
			$html .= '</span>';
		}

		return $html;
	}

	function calculate_packages() {
		//Assume equal size packages. Find the best matching box size
		$max_weight = (float) $this->get_setting( 'max_weight', 50 );
		$found      = - 1;
		$largest    = - 1.0;

		//See if it fits in one box
		foreach ( $this->get_setting( 'boxes', array() ) as $key => $box ) {
			$weight = $box['weight'];

			//Find largest
			if ( $weight > $largest ) {
				$largest = $weight;
				$found   = $key;
			}
			//If weight less
			if ( floatval( $this->weight ) <= floatval( $weight ) ) {
				$found = $key;
				break;
			}
		}

		$allowed_weight = min( $this->get_setting( "boxes->{$found}->weight", 0 ), $max_weight );

		if ( $allowed_weight >= $this->weight || $allowed_weight <= 0 ) {
			$this->pkg_count  = 1;
			$this->pkg_weight = $this->weight;
		} else {
			$this->pkg_count  = ceil( $this->weight / $allowed_weight ); // Avoid zero
			$this->pkg_weight = $this->weight / $this->pkg_count;
		}

		//found our box
		$this->pkg_dims = explode( 'x', strtolower( $this->get_setting( "boxes->{$found}->size" ) ) );
		foreach ( $this->pkg_dims as &$dim ) {
			$dim = $this->_as_inches( $dim );
		}
		sort( $this->pkg_dims ); //Sort so two lowest values are used for Girth

		// Fixup pounds by converting multiples of 16 ounces to pounds
		$this->pounds = intval( $this->pkg_weight );
		$this->ounces = round( ( $this->pkg_weight - $this->pounds ) * 16 );

		//If > 35 ponds it's a not machinable
		$this->machinable = ( $this->pkg_weight > 35 ) ? 'false' : $this->machinable;
		//If largest dimension > 12 inches it's not machinable
		$this->machinable = ( $this->pkg_dims[2] > 12 ) ? 'false' : $this->machinable;
	}

	/**
	 * For USPS RateV4 Request Takes the set of allowed Shipping options and mp_settings and makes the API call to USPS
	 * Return the set of valid shipping options for this order with prices added.
	 *
	 * @param array $shipping_options
	 * @param array $settings
	 *
	 * return array $shipping_options
	 */
	function ratev4_request() {
		$shipping_options = array_filter( $this->get_setting( 'services', array() ), create_function( '$enabled', 'return ( $enabled );' ) );

		if ( count( $shipping_options ) == 0 ) {
			//no services enabled - bail
			return array();
		}

		$this->calculate_packages();

		//Build XML. **Despite being XML the order of elements is important in a RateV4 request**
		$dom               = new DOMDocument( '1.0', 'utf-8' );
		$dom->formatOutput = true;
		$root              = $dom->appendChild( $dom->createElement( 'RateV4Request' ) );
		$root->setAttribute( 'USERID', $this->get_setting( 'api_username' ) );
		$root->appendChild( $dom->createElement( 'Revision', '2' ) );


		//foreach( $shipping_options as $service => $name)

		$service = ( $this->get_setting( 'online' ) == 'online' ) ? 'ONLINE' : 'ALL';
		$package = $root->appendChild( $dom->createElement( 'Package' ) );
		$package->setAttribute( 'ID', $service );
		$package->appendChild( $dom->createElement( 'Service', $service ) );
		$package->appendChild( $dom->createElement( 'ZipOrigination', $this->base_zip ) );
		$package->appendChild( $dom->createElement( 'ZipDestination', $this->destination_zip ) );
		$package->appendChild( $dom->createElement( 'Pounds', $this->pounds ) );
		$package->appendChild( $dom->createElement( 'Ounces', $this->ounces ) );

		// If greater than 12" it's a LARGE parcel otherwise REGULAR
		$this->size = ( $this->pkg_dims[2] > 12 ) ? 'LARGE' : 'REGULAR';

		$this->container = $this->size == 'LARGE' ? 'RECTANGULAR' : 'VARIABLE';

		$package->appendChild( $dom->createElement( 'Container', $this->container ) );

		$package->appendChild( $dom->createElement( 'Size', $this->size ) );

		if ( $this->size == 'LARGE' ) {
			$package->appendChild( $dom->createElement( 'Width', $this->pkg_dims[1] ) );
			$package->appendChild( $dom->createElement( 'Length', $this->pkg_dims[2] ) );
			$package->appendChild( $dom->createElement( 'Height', $this->pkg_dims[0] ) );
			$package->appendChild( $dom->createElement( 'Girth', 2 * ( $this->pkg_dims[0] + $this->pkg_dims[1] ) ) );

			$package->appendChild( $dom->createElement( 'Value', $total ) );  //For insurance?
		}

		$package->appendChild( $dom->createElement( 'Machinable', $this->machinable ) );

		//We have the XML make the call
		$url = $this->production_uri . '?API=RateV4&XML=' . urlencode( $dom->saveXML() );

		$response = wp_remote_request( $url, array( 'headers' => array( 'Content-Type: text/xml' ) ) );
		if ( is_wp_error( $response ) ) {
			return array( 'error' => '<div class="mp_checkout_error">' . $response->get_error_message() . '</div>' );
		} else {
			$loaded = ( $response['response']['code'] == '200' );
			$body   = $response['body'];
			if ( ! $loaded ) {
				return array( 'error' => '<div class="mp_checkout_error">' . $response['response']['code'] . "&mdash;" . $response['response']['message'] . '</div>' );
			}
		}

		if ( $loaded ) {

			libxml_use_internal_errors( true );
			$dom           = new DOMDocument();
			$dom->encoding = 'utf-8';
			$dom->loadHTML( $body );
			libxml_clear_errors();
		}

		//Process the return XML

		//Clear any old price
		unset( $_SESSION['mp_shipping_info']['shipping_cost'] );

		$xpath = new DOMXPath( $dom );

		//Make SESSION copy with just prices and delivery

		if ( ! is_array( $shipping_options ) ) {
			$shipping_options = array();
		}
		$mp_shipping_options = $shipping_options;

		foreach ( $shipping_options as $service => $option ) {

			$box_count = $this->pkg_count;

			//Check for flat rate boxes
			if ( isset( $this->services[ $service ]->max_weight ) ) { //Is it flat rate
				$max_weight = $this->_as_pounds( $this->get_setting( "flat_weights->$service" ) );
				if ( $this->pkg_max <= $max_weight ) {
					$box_count = ceil( $this->weight / $max_weight );
				}
			}

			$nodes = $xpath->query( '//postage[@classid="' . $this->services[ $service ]->code . '"]/rate' );
			
			if( is_object( $nodes->item( 0 ) ) ) {
				$nodeRate = $nodes->item( 0 )->textContent;
			} else {
				$nodeRate = 0;
			}
			
			$rate  = floatval( $nodeRate * $box_count );

			if ( $this->services[ $service ]->code == '0' ) {
				/* First class mail returns 4 sub types (Stamped Letter, Parcel,
				Large Envelope, Postcards). We need to get the PARCEL sub type or too low of
				a rate will get returned */
				$nodes_type = $xpath->query( '//postage[@classid="' . $this->services[ $service ]->code . '"]/mailservice' );

				for ( $i = 0; $i < $nodes_type->length; $i ++ ) {
					$type = $nodes_type->item( $i )->textContent;
					if ( strpos( $type, 'Parcel' ) !== false ) {
						$rate = floatval( $nodes->item( $i )->textContent ) * $box_count;
						break;
					}
				}
			}

			if ( $rate == 0 ) {  //Not available for this combination
				unset( $mp_shipping_options[ $service ] );
			} else {
				$handling                        = floatval( $this->get_setting( 'domestic_handling' ) ) * $box_count; // Add handling times number of packages.
				$delivery                        = $this->services[ $service ]->delivery;
				$mp_shipping_options[ $service ] = array(
					'rate'     => $rate,
					'delivery' => $delivery,
					'handling' => $handling
				);

				//match it up if there is already a selection
				if ( ! empty( $_SESSION['mp_shipping_info']['shipping_sub_option'] ) ) {
					if ( $_SESSION['mp_shipping_info']['shipping_sub_option'] == $service ) {
						$_SESSION['mp_shipping_info']['shipping_cost'] = $rate + $handling;
					}
				}
			}
		}

		uasort( $mp_shipping_options, array( $this, 'compare_rates' ) );

		//Update the session. Save the currently calculated CRCs
		$this->_crc_update( $mp_shipping_options );

		unset( $xpath );
		unset( $dom );

		return $this->_format_shipping_options( $mp_shipping_options );;
	}

	/**
	 * For USPS RateV4 Request Takes the set of allowed Shipping options and mp_settings and makes the API call to USPS
	 * Return the set of valid shipping options for this order with prices added.
	 *
	 * @param array $shipping_options
	 * @param array $settings
	 *
	 * return array $shipping_options
	 */
	function ratev2_request() {
		$shipping_options = array_filter( $this->get_setting( 'intl_services', array() ), create_function( '$val', 'return ($val == 1);' ) );

		if ( count( $shipping_options ) == 0 ) {
			//no services enabled - bail
			return array();
		}

		$this->calculate_packages();

		//Build XML. **Despite being XML the order of elements is important in a RateV2 request**
		$dom  = new DOMDocument( '1.0', 'utf-8' );
		$root = $dom->appendChild( $dom->createElement( 'IntlRateV2Request' ) );
		$root->setAttribute( 'USERID', $this->get_setting( 'api_username' ) );
		$root->appendChild( $dom->createElement( 'Revision', '2' ) );


		//foreach( $shipping_options as $service => $name)

		$mail_type = 'All'; //($this->usps_shipping['online'] == 'online') ? 'ONLINE' : 'ALL';
		$package   = $root->appendChild( $dom->createElement( 'Package' ) );
		$package->setAttribute( 'ID', $mail_type );
		$package->appendChild( $dom->createElement( 'Pounds', $this->pounds ) );
		$package->appendChild( $dom->createElement( 'Ounces', $this->ounces ) );

		$package->appendChild( $dom->createElement( 'Machinable', $this->machinable ) );
		$package->appendChild( $dom->createElement( 'MailType', $mail_type ) );

		$gxg = $dom->createElement( 'GXG' );
		$gxg->appendChild( $dom->createElement( 'POBoxFlag', $this->po_box ) );
		$gxg->appendChild( $dom->createElement( 'GiftFlag', $this->gift ) );
		//$package->appendChild($gxg);

		$package->appendChild( $dom->createElement( 'ValueOfContents', $total ) );  //For insurance?
		$countries = mp_countries();		
		$package->appendChild( $dom->createElement( 'Country', $countries[ $this->country ] ) );

		// If greater than 12" it's a LARGE parcel otherwise REGULAR
		$this->size = ( $this->pkg_dims[2] > 12 ) ? 'LARGE' : 'REGULAR';

		$this->container = 'RECTANGULAR';  //$this->size == 'LARGE' ? 'RECTANGULAR' : 'VARIABLE';

		$package->appendChild( $dom->createElement( 'Container', $this->container ) );
		$package->appendChild( $dom->createElement( 'Size', $this->size ) );

		//if($this->size == 'LARGE')
		{
			$package->appendChild( $dom->createElement( 'Width', $this->pkg_dims[1] ) );
			$package->appendChild( $dom->createElement( 'Length', $this->pkg_dims[2] ) );
			$package->appendChild( $dom->createElement( 'Height', $this->pkg_dims[0] ) );
			$package->appendChild( $dom->createElement( 'Girth', 2 * ( $this->pkg_dims[0] + $this->pkg_dims[1] ) ) );

		}
		$package->appendChild( $dom->createElement( 'OriginZip', $this->base_zip ) );
		$package->appendChild( $dom->createElement( 'CommercialFlag', 'N' ) );


		//We have the XML make the call
		$url = $this->production_uri . '?API=IntlRateV2&XML=' . urlencode( $dom->saveXML() );

		$response = wp_remote_request( $url, array( 'headers' => array( 'Content-Type: text/xml' ) ) );
		if ( is_wp_error( $response ) ) {
			return array( 'error' => '<div class="mp_checkout_error">' . $response->get_error_message() . '</div>' );
		} else {
			$loaded = ( $response['response']['code'] == '200' );
			$body   = $response['body'];
			if ( ! $loaded ) {
				return array( 'error' => '<div class="mp_checkout_error">' . $response['response']['code'] . "&mdash;" . $response['response']['message'] . '</div>' );
			}
		}

		$dom = $this->_parse_xml( $body );

		//Process the return XML

		//Clear any old price
		unset( $_SESSION['mp_shipping_info']['shipping_cost'] );

		$xpath = new DOMXPath( $dom );

		//Make SESSION copy with just prices
		if ( ! is_array( $shipping_options ) ) {
			$shipping_options = array();
		}

		$mp_shipping_options = $shipping_options;

		foreach ( $shipping_options as $service => $option ) {

			$box_count = $this->pkg_count;

			//Check for flat rate boxes
			if ( isset( $this->intl_services[ $service ]->max_weight ) ) { //Is it flat rate
				$max_weight = $this->_as_pounds( $this->get_setting( "flat_weights->$service" ) );
				if ( $this->pkg_max <= $max_weight ) {
					$box_count = ceil( $this->weight / $max_weight );
				}
			}

			$nodes = $xpath->query( '//service[@id="' . $this->intl_services[ $service ]->code . '"]/postage' );
			$rate  = floatval( $nodes->item( 0 )->textContent ) * $box_count;

			$nodes    = $xpath->query( '//service[@id="' . $this->intl_services[ $service ]->code . '"]/svccommitments' );
			$delivery = str_replace( ' ', '', $nodes->item( 0 )->textContent );
			$delivery = '(' . str_replace( 'businessdays', ') days', $delivery );

			if ( $rate == 0 ) {  //Not available for this combination
				unset( $mp_shipping_options[ $service ] );
			} else {
				$handling                        = floatval( $this->get_setting( 'intl_handling' ) ) * $box_count; // Add handling times number of packages.
				$mp_shipping_options[ $service ] = array(
					'rate'     => $rate,
					'delivery' => $delivery,
					'handling' => $handling
				);
			}
		}

		uasort( $mp_shipping_options, array( $this, 'compare_rates' ) );

		$shipping_options = $this->_format_shipping_options( $mp_shipping_options );

		//Update the session. Save the currently calculated CRCs
		$this->_crc_update( $mp_shipping_options );

		unset( $xpath );
		unset( $dom );

		return $shipping_options;
	}

	// Conversion Helpers
} //End MP_Shipping_USPS

if ( ! class_exists( 'USPS_Service' ) ):
	class USPS_Service {
		public $code;
		public $name;
		public $delivery;
		public $max_weight;

		function __construct( $code, $name, $delivery = '', $max_weight = null ) {
			$this->code       = $code;
			$this->name       = $name;
			$this->delivery   = $delivery;
			$this->max_weight = $max_weight;

		}
	}
endif;


//register plugin as calculated. Only in US and US Possesions
$settings = get_option( 'mp_settings' );
if ( in_array( $settings['base_country'], array(
	'US',
	'UM',
	'AS',
	'FM',
	'GU',
	'MH',
	'MP',
	'PW',
	'PR',
	'PI',
	'VI'
) ) ) {
	MP_Shipping_API::register_plugin( 'MP_Shipping_USPS', 'usps', __( 'USPS', 'mp' ), true );
}
