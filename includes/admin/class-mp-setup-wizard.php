<?php

class MP_Setup_Wizard {

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	//data https://xcitestudios.com/blog/2012/10/22/countrycurrency-list-for-c-json-php-and-html-select/
	private $country_currencies = array(
		"AF" => "AFN",
		"AL" => "ALL",
		"DZ" => "DZD",
		"AS" => "USD",
		"AD" => "EUR",
		"AO" => "AOA",
		"AI" => "XCD",
		"AG" => "XCD",
		"AR" => "ARP",
		"AM" => "AMD",
		"AW" => "AWG",
		"AU" => "AUD",
		"AT" => "EUR",
		"AZ" => "AZN",
		"BS" => "BSD",
		"BH" => "BHD",
		"BD" => "BDT",
		"BB" => "BBD",
		"BY" => "BYR",
		"BE" => "EUR",
		"BZ" => "BZD",
		"BJ" => "XOF",
		"BM" => "BMD",
		"BT" => "BTN",
		"BO" => "BOV",
		"BA" => "BAM",
		"BW" => "BWP",
		"BV" => "NOK",
		"BR" => "BRL",
		"IO" => "USD",
		"BN" => "BND",
		"BG" => "BGL",
		"BF" => "XOF",
		"BI" => "BIF",
		"KH" => "KHR",
		"CM" => "XAF",
		"CA" => "CAD",
		"CV" => "CVE",
		"KY" => "KYD",
		"CF" => "XAF",
		"TD" => "XAF",
		"CL" => "CLF",
		"CN" => "CNY",
		"CX" => "AUD",
		"CC" => "AUD",
		"CO" => "COU",
		"KM" => "KMF",
		"CG" => "XAF",
		"CD" => "CDF",
		"CK" => "NZD",
		"CR" => "CRC",
		"HR" => "HRK",
		"CU" => "CUP",
		"CY" => "EUR",
		"CZ" => "CZK",
		"CS" => "CSJ",
		"CI" => "XOF",
		"DK" => "DKK",
		"DJ" => "DJF",
		"DM" => "XCD",
		"DO" => "DOP",
		"EC" => "USD",
		"EG" => "EGP",
		"SV" => "USD",
		"GQ" => "EQE",
		"ER" => "ERN",
		"EE" => "EEK",
		"ET" => "ETB",
		"FK" => "FKP",
		"FO" => "DKK",
		"FJ" => "FJD",
		"FI" => "FIM",
		"FR" => "XFO",
		"GF" => "EUR",
		"PF" => "XPF",
		"TF" => "EUR",
		"GA" => "XAF",
		"GM" => "GMD",
		"GE" => "GEL",
		"DD" => "DDM",
		"DE" => "EUR",
		"GH" => "GHC",
		"GI" => "GIP",
		"GR" => "GRD",
		"GL" => "DKK",
		"GD" => "XCD",
		"GP" => "EUR",
		"GU" => "USD",
		"GT" => "GTQ",
		"GN" => "GNE",
		"GW" => "GWP",
		"GY" => "GYD",
		"HT" => "USD",
		"HM" => "AUD",
		"VA" => "EUR",
		"HN" => "HNL",
		"HK" => "HKD",
		"HU" => "HUF",
		"IS" => "ISJ",
		"IN" => "INR",
		"ID" => "IDR",
		"IR" => "IRR",
		"IQ" => "IQD",
		"IE" => "IEP",
		"IL" => "ILS",
		"IT" => "ITL",
		"JM" => "JMD",
		"JP" => "JPY",
		"JO" => "JOD",
		"KZ" => "KZT",
		"KE" => "KES",
		"KI" => "AUD",
		"KP" => "KPW",
		"KR" => "KRW",
		"KW" => "KWD",
		"KG" => "KGS",
		"LA" => "LAJ",
		"LV" => "LVL",
		"LB" => "LBP",
		"LS" => "ZAR",
		"LR" => "LRD",
		"LY" => "LYD",
		"LI" => "CHF",
		"LT" => "LTL",
		"LU" => "LUF",
		"MO" => "MOP",
		"MK" => "MKN",
		"MG" => "MGF",
		"MW" => "MWK",
		"MY" => "MYR",
		"MV" => "MVR",
		"ML" => "MAF",
		"MT" => "MTL",
		"MH" => "USD",
		"MQ" => "EUR",
		"MR" => "MRO",
		"MU" => "MUR",
		"YT" => "EUR",
		"MX" => "MXV",
		"FM" => "USD",
		"MD" => "MDL",
		"MC" => "MCF",
		"MN" => "MNT",
		"ME" => "EUR",
		"MS" => "XCD",
		"MA" => "MAD",
		"MZ" => "MZM",
		"MM" => "MMK",
		"NA" => "ZAR",
		"NR" => "AUD",
		"NP" => "NPR",
		"NL" => "NLG",
		"AN" => "ANG",
		"NC" => "XPF",
		"NZ" => "NZD",
		"NI" => "NIO",
		"NE" => "XOF",
		"NG" => "NGN",
		"NU" => "NZD",
		"NF" => "AUD",
		"MP" => "USD",
		"NO" => "NOK",
		"OM" => "OMR",
		"PK" => "PKR",
		"PW" => "USD",
		"PA" => "USD",
		"PG" => "PGK",
		"PY" => "PYG",
		"YD" => "YDD",
		"PE" => "PEH",
		"PH" => "PHP",
		"PN" => "NZD",
		"PL" => "PLN",
		"PT" => "TPE",
		"PR" => "USD",
		"QA" => "QAR",
		"RO" => "ROK",
		"RU" => "RUB",
		"RW" => "RWF",
		"RE" => "EUR",
		"SH" => "SHP",
		"KN" => "XCD",
		"LC" => "XCD",
		"PM" => "EUR",
		"VC" => "XCD",
		"WS" => "WST",
		"SM" => "EUR",
		"ST" => "STD",
		"SA" => "SAR",
		"SN" => "XOF",
		"RS" => "CSD",
		"SC" => "SCR",
		"SL" => "SLL",
		"SG" => "SGD",
		"SK" => "SKK",
		"SI" => "SIT",
		"SB" => "SBD",
		"SO" => "SOS",
		"ZA" => "ZAL",
		"ES" => "ESB",
		"LK" => "LKR",
		"SD" => "SDG",
		"SR" => "SRG",
		"SJ" => "NOK",
		"SZ" => "SZL",
		"SE" => "SEK",
		"CH" => "CHW",
		"SY" => "SYP",
		"TW" => "TWD",
		"TJ" => "TJR",
		"TZ" => "TZS",
		"TH" => "THB",
		"TL" => "USD",
		"TG" => "XOF",
		"TK" => "NZD",
		"TO" => "TOP",
		"TT" => "TTD",
		"TN" => "TND",
		"TR" => "TRY",
		"TM" => "TMM",
		"TC" => "USD",
		"TV" => "AUD",
		"SU" => "SUR",
		"UG" => "UGS",
		"UA" => "UAK",
		"AE" => "AED",
		"GB" => "GBP",
		"US" => "USS",
		"UM" => "USD",
		"UY" => "UYI",
		"UZ" => "UZS",
		"VU" => "VUV",
		"VE" => "VEB",
		"VN" => "VND",
		"VG" => "USD",
		"VI" => "USD",
		"WF" => "XPF",
		"EH" => "MAD",
		"YE" => "YER",
		"YU" => "YUM",
		"ZR" => "ZRZ",
		"ZM" => "ZMK",
		"ZW" => "ZWC"
	);

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Setup_Wizard();
		}

		return self::$_instance;
	}

	/**
	 * Display setup wizard nag message
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_notices
	 */
	public function nag_message() {
		if ( mp_get_setting( 'mp_setup_complete' ) || mp_get_get_value( 'page' ) == 'store-setup-wizard' || mp_get_get_value( 'page' ) == 'mp-db-update' || ! current_user_can( apply_filters( 'mp_store_settings_cap', 'manage_store_settings' ) ) ) {
			return;
		}
		?>
		<div class="error">
			<p><?php printf( __( 'MarketPress setup is not complete! <a class="button button-primary" href="%s">Run setup wizard</a>', 'mp' ), admin_url( 'admin.php?page=store-setup-wizard' ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * Init metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'                 => 'mp-quick-setup-wizard',
			'page_slugs'         => array( 'store-setup-wizard' ),
			'title'              => __( 'Quick Setup', 'mp' ),
			'option_name'        => 'mp_settings',
			'show_submit_button' => false,
		) );

		$metabox->add_field( 'quick_setup', array(
			'name'    => 'quick_setup',
			'label'   => '',
			'message' => __( 'Quick Setup', 'mp' ),
			'class'   => 'mp_quick_setup',
		) );

		$quick_setup_step = mp_get_get_value( 'quick_setup_step' );

		if ( isset( $quick_setup_step ) ) {

			/* Store Location */
			$metabox = new WPMUDEV_Metabox( array(
				'id'                 => 'mp-quick-setup-wizard-location',
				'page_slugs'         => array( 'store-setup-wizard' ),
				'title'              => __( 'Location', 'mp' ),
				'option_name'        => 'mp_settings',
				'show_submit_button' => false,
				'ajax_save'          => true
			) );

			$metabox->add_field( 'advanced_select', array(
				'name'        => 'base_country',
				'placeholder' => __( 'Select a Country', 'mp' ),
				'multiple'    => false,
				'label'       => array( 'text' => __( 'Base Country', 'mp' ) ),
				'options'     => array( '' => __( 'Select A Country', 'mp' ) ) + mp_countries(),
				'width'       => 'element',
				'validation'  => array(
					'required' => true,
				),
			) );

			$states = mp_get_states( mp_get_setting( 'base_country' ) );

			$countries_with_states = array();
			foreach ( mp_countries() as $code => $country ) {
				if( property_exists( mp(), $code.'_provinces' ) ) {
					$countries_with_states[] = $code;
				}
			}
			$metabox->add_field( 'advanced_select', array(
				'name'        => 'base_province',
				'placeholder' => __( 'Select a State/Province/Region', 'mp' ),
				'multiple'    => false,
				'label'       => array( 'text' => __( 'Base State/Province/Region', 'mp' ) ),
				'options'     => $states,
				'width'       => 'element',
				'conditional' => array(
					'name'   => 'base_country',
					'value'	 => $countries_with_states,
					'action' => 'show',
				),
				'validation'  => array(
					'required' => true,
				),
			) );

			$countries_without_postcode = array_keys( mp()->countries_no_postcode );
			$metabox->add_field( 'text', array(
				'name'        => 'base_zip',
				'label'       => array( 'text' => __( 'Base Zip/Postal Code', 'mp' ) ),
				'style'       => 'max-width: 150px;',
				'custom'      => array(
					'minlength' => 3,
				),
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => $countries_without_postcode,
					'action' => 'hide',
				),
				'validation'  => array(
					'required' => true,
				),
			) );

			$metabox->add_field( 'text', array(
				'name'       => 'zip_label',
				'label'      => array( 'text' => __( 'Zip/Postal Code Label', 'mp' ) ),
				'custom'     => array(
					'style' => 'max-width: 300px',
				),
				'validation' => array(
					'required' => true,
				),
			) );


			/* Sale to countries */
			$metabox = new WPMUDEV_Metabox( array(
				'id'          => 'mp-quick-setup-wizard-countries',
				'page_slugs'  => array( 'store-setup-wizard' ),
				'title'       => __( 'Countries', 'mp' ),
				'option_name' => 'mp_settings',
				'order'       => 1,
			) );

			// Target Countries
			$metabox->add_field( 'advanced_select', array(
				'name'                   => 'shipping[allowed_countries]',
				'label'                  => array( 'text' => __( 'Target Countries', 'mp' ) ),
				'desc'                   => __( 'These are the countries that you will ship to.', 'mp' ),
				'options'                => mp_popular_country_list() + array( 'all_countries' => __( 'All Countries', 'mp' ) ) + mp_country_list(),
				//all_countries|disabled
				'default_value'          => array( 'all_countries' => __( 'All Countries', 'mp' ) ),
				'placeholder'            => __( 'Choose Countries', 'mp' ),
				'format_dropdown_header' => '
				<ul class="select2-all-none">
					<li class="select2-none">' . __( 'None', 'mp' ) . '</li>
					<li class="select2-all">' . __( 'All', 'mp' ) . '</li>
					<li class="select2-eu" data-countries="' . implode( ',', mp()->eu_countries ) . '">' . __( 'EU', 'mp' ) . '</li>
				</ul>',
				'validation'             => array(
					'required' => true,
				),
			) );


			/* Currency options */
			$metabox = new WPMUDEV_Metabox( array(
				'id'          => 'mp-quick-setup-wizard-currency',
				'page_slugs'  => array( 'store-setup-wizard' ),
				'title'       => __( 'Currency Settings', 'mp' ),
				'option_name' => 'mp_settings',
			) );

			$currencies = mp()->currencies;
			$options    = array( '' => __( 'Select a Currency', 'mp' ) );

			foreach ( $currencies as $key => $value ) {
				$options[ $key ] = esc_attr( $value[0] ) . ' - ' . mp_format_currency( $key );
			}

			$metabox->add_field( 'advanced_select', array(
				'name'        => 'currency',
				'placeholder' => __( 'Select a Currency', 'mp' ),
				'multiple'    => false,
				'label'       => array( 'text' => __( 'Store Currency', 'mp' ) ),
				'options'     => $options,
				'width'       => 'element',
			) );
			$metabox->add_field( 'radio_group', array(
				'name'          => 'curr_symbol_position',
				'label'         => array( 'text' => __( 'Currency Symbol Position', 'mp' ) ),
				'default_value' => '1',
				'orientation'   => 'horizontal',
				'options'       => array(
					'1' => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>100',
					'2' => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span> 100',
					'3' => '100<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>',
					'4' => '100 <span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>',
				),
			) );


			/* Tax options */
			$metabox = new WPMUDEV_Metabox( array(
				'id'          => 'mp-quick-setup-wizard-tax',
				'page_slugs'  => array( 'store-setup-wizard' ),
				'title'       => __( 'Tax Settings', 'mp' ),
				'option_name' => 'mp_settings',
			) );

			$metabox->add_field( 'text', array(
				'name'        => 'tax[rate]',
				'label'       => array( 'text' => __( 'Default Tax Rate', 'mp' ) ),
				'after_field' => '%',
				'style'       => 'max-width:75px',
				'validation'  => array(
					'number' => true,
				),
				'placeholder' => '10',
				'conditional' => array(
					'name'   => 'base_country',
					'value'  => 'CA',
					'action' => 'hide',
				),
			) );

			// Create field for each canadian province
			foreach ( mp()->CA_provinces as $key => $label ) {
				$metabox->add_field( 'text', array(
					'name'        => 'tax[canada_rate][' . $key . ']',
					'desc'        => '<a target="_blank" href="http://en.wikipedia.org/wiki/Sales_taxes_in_Canada">' . __( 'Current Rates', 'mp' ) . '</a>',
					'label'       => array( 'text' => sprintf( __( '%s Tax Rate', 'mp' ), $label ) ),
					'custom'      => array( 'style' => 'width:75px' ),
					'after_field' => '%',
					'conditional' => array(
						'name'   => 'base_country',
						'value'  => 'CA',
						'action' => 'show',
					),
				) );
			}

			$metabox->add_field( 'text', array(
				'name'        => 'tax[label]',
				'label'       => array( 'text' => __( 'Tax Label', 'mp' ) ),
				'style'       => 'max-width: 300px',
				'placeholder' => __( 'I.e. Taxes, VAT, GST, etc', 'mp' )
			) );
			$metabox->add_field( 'checkbox', array(
				'name'    => 'tax[tax_shipping]',
				'label'   => array( 'text' => __( 'Apply Tax To Shipping Fees?', 'mp' ) ),
				'message' => __( 'Yes', 'mp' ),
				'class'   => 'mp-quick-field-inline-block'
			) );
			$metabox->add_field( 'checkbox', array(
				'name'    => 'tax[tax_inclusive]',
				'label'   => array( 'text' => __( 'Enter Prices Inclusive of Tax?', 'mp' ) ),
				'message' => __( 'Yes', 'mp' ),
				'class'   => 'mp-quick-field-inline-block'
			) );
			$metabox->add_field( 'checkbox', array(
				'name'    => 'tax[include_tax]',
				'label'   => array( 'text' => __( 'Show Price + Tax?', 'mp' ) ),
				'message' => __( 'Yes', 'mp' ),
				'class'   => 'mp-quick-field-inline-block'
			) );
			$metabox->add_field( 'checkbox', array(
				'name'    => 'tax[tax_digital]',
				'label'   => array( 'text' => __( 'Apply Tax to Downloadable Products?', 'mp' ) ),
				'message' => __( 'Yes', 'mp' ),
				'class'   => 'mp-quick-field-inline-block'
			) );

			// Measurement System

			$metabox = new WPMUDEV_Metabox( array(
				'id'          => 'mp-quick-setup-wizard-measurement-system',
				'page_slugs'  => array( 'store-setup-wizard' ),
				'title'       => __( 'Measurement System', 'mp' ),
				'option_name' => 'mp_settings',
			) );

			$metabox->add_field( 'radio_group', array(
				'name'          => 'shipping[system]',
				'options'       => array(
					'english' => __( 'Pounds', 'mp' ),
					'metric'  => __( 'Kilograms', 'mp' ),
				),
				'default_value' => 'english',
			) );

			$metabox = new WPMUDEV_Metabox( array(
				'id'                 => 'mp-quick-setup-is-wizard-shipping',
				'page_slugs'         => array( 'store-setup-wizard' ),
				'title'              => '',
				'option_name'        => '',
				'class'              => '',
				'hook'               => 'mp_wizard_shipping_section',
				'show_submit_button' => false
			) );
			$metabox->add_field( 'radio_group', array(
				'name'          => 'mp_charge_shipping',
				'options'       => array(
					'1' => __( 'Yes', 'mp' ),
					'0' => __( 'No', 'mp' ),
				),
				'label'         => array(
					'text' => __( 'I want to charge shipping', 'mp' ),
				),
				'default_value' => '0',
			) );
			$metabox = new WPMUDEV_Metabox( array(
				'id'                 => 'mp-quick-setup-wizard-shipping',
				'page_slugs'         => array( 'store-setup-wizard' ),
				'title'              => __( 'Measurement System', 'mp' ),
				'option_name'        => 'mp_settings',
				'class'              => '',
				'show_submit_button' => false
			) );

// Shipping Methods
			$options        = array( 'none' => __( 'No Shipping', 'mp' ) );
			$plugins        = MP_Shipping_API::get_plugins();
			$has_calculated = false;

			foreach ( $plugins as $code => $plugin ) {
				if ( $plugin[2] ) {
					$has_calculated = true;
					continue;
				}

				$options[ $code ] = $plugin[1];
			}

			if ( $has_calculated ) {
				$options['calculated'] = __( 'Calculated Options', 'mp' );
			}

			$metabox->add_field( 'radio_group', array(
				'name'          => 'shipping[method]',
				'label'         => array( 'text' => __( 'Shipping Method', 'mp' ) ),
				'options'       => $options,
				'default_value' => 'none',
			) );

			// Selected Calculated Shipping Options
			$options = array();
			foreach ( $plugins as $slug => $plugin ) {
				if ( $plugin[2] ) {
					$options[ $slug ] = $plugin[1];
				}
			}

			$metabox->add_field( 'checkbox_group', array(
				'name'               => 'shipping[calc_methods]',
				'label'              => array( 'text' => __( 'Select Shipping Options', 'mp' ) ),
				'desc'               => __( 'Select which calculated shipping methods the customer will be able to choose from.', 'mp' ),
				'options'            => $options,
				'use_options_values' => true,
				'conditional'        => array(
					'name'   => 'shipping[method]',
					'value'  => 'calculated',
					'action' => 'show',
				),
			) );

			//payment gateway
			$metabox = new WPMUDEV_Metabox( array(
				'id'                 => 'mp-quick-setup-wizard-payment',
				'page_slugs'         => array( 'store-setup-wizard' ),
				'title'              => __( 'Payment Gateways', 'mp' ),
				'option_name'        => 'mp_settings',
				'class'              => '',
				'show_submit_button' => false,
				'hook'               => 'mp_wizard_payment_gateway_section'
			) );

			$gateways = MP_Gateway_API::get_gateways( true );
			if ( isset( $gateways['manual_payments'] ) ) {
				$manual_payments = $gateways['manual_payments'];
				$options         = array(
					'manual_payments' => $manual_payments[1],
					'other'           => __( "Other Gateways", "mp" )
				);
			} else {
				//fallback to default
				$options = array(
					'manual_payments' => __( "Manual Payment", "mp" ),
					'other'           => __( "Other Gateways", "mp" )
				);
			}

			$metabox->add_field( 'radio_group', array(
				'name'          => 'wizard_payment',
				'label'         => array( 'text' => __( 'Setup your payment gateway', 'mp' ) ),
				'options'       => $options,
				'default_value' => 'manual_payments',
			) );


		}
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		//add_action( 'admin_notices', array( &$this, 'nag_message' ) );
		add_action( 'init', array( &$this, 'init_metaboxes' ) );
		add_action( 'wp_ajax_mp_preset_currency_base_country', array( &$this, 'determine_currency' ) );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'store-setup-wizard' ) {
			add_filter( 'wpmudev_metabox/init_args', array( &$this, 'update_settings_for_shipping_rule' ) );
			/**
			 * Payment widzard having 2 radios default gateway & other, we don't store this value inside db,
			 * use this hook for return the right
			 */
			add_filter( 'wpmudev_field/get_value/mp_charge_shipping', array(
				&$this,
				'determine_is_charge_shipping'
			), 10, 4 );
			add_filter( 'wpmudev_field/get_value/wizard_payment', array(
				&$this,
				'determine_is_use_paymentgateway'
			), 10, 4 );
			add_filter( 'wpmudev_metabox/after_settings_metabox_saved', array( &$this, 'maybe_save_manual_payment' ) );
		}
	}

	/**
	 * if payment gateway is manual payment, we need to save it to the gateway list
	 *
	 * @param $metabox
	 */
	public function maybe_save_manual_payment( $metabox ) {
		if ( $metabox->args['id'] == 'mp-quick-setup-wizard-payment' ) {
			foreach ( $metabox->fields as $field ) {
				$post_key = $field->get_post_key( $field->args['name'] );
				$value    = $field->get_post_value( $post_key );
				if ( $value == 'manual_payments' ) {
					$allowed                    = mp_get_setting( 'gateways->allowed' );
					$allowed['manual_payments'] = 1;
					mp_update_setting( 'gateways->allowed', $allowed );
				}
			}
		}
	}

	/**
	 * @param $value
	 * @param $post_id
	 * @param $raw
	 * @param $instance
	 *
	 * @return string
	 */
	public function determine_is_use_paymentgateway( $value, $post_id, $raw, $instance ) {
		$gateways = mp_get_setting( 'gateways->allowed', array() );
		unset( $gateways['manual_payments'] );
		foreach ( $gateways as $key => $val ) {
			if ( $val == 1 ) {
				$value = 'other';
				break;
			}
		}

		return $value;
	}

	/**
	 * If any shipping method configed, open the box instead of noshipping
	 *
	 * @param $value
	 * @param $post_id
	 * @param $raw
	 * @param $instance
	 *
	 * @return int
	 */
	public function determine_is_charge_shipping( $value, $post_id, $raw, $instance ) {
		//check if this already having shipping configed
		if ( mp_get_setting( 'shipping->method' ) && mp_get_setting( 'shipping->method' ) != 'none' ) {
			$value = 1;
		}

		return $value;
	}

	public function update_settings_for_shipping_rule( $args ) {
		$ids = array(
			'mp-settings-shipping-plugin-fedex',
			'mp-settings-shipping-plugin-flat_rate',
			'mp-settings-shipping-plugin-table_rate',
			'mp-settings-shipping-plugin-ups',
			'mp-settings-shipping-plugin-usps',
			'mp-settings-shipping-plugin-weight_rate',
			'mp-quick-setup-wizard-shipping',
			'mp-settings-shipping-plugin-pickup'
		);
		if ( $args['id'] == 'mp-quick-setup-wizard-shipping' ) {
			$args['hook'] = 'mp_wizard_shipping_rule_section';
		} elseif ( $args['id'] == 'mp-settings-payments' ) {
			$args['hook']         = 'mp_wizard_payment_gateway_details';
			$args['class']        = '';
			$args['page_slugs'][] = 'store-setup-wizard';
		} elseif ( in_array( $args['id'], $ids ) ) {
			$args['hook']               = '_mp_wizard_shipping_rule_section';
			$args['class']              = '';
			$args['show_submit_button'] = false;

		} elseif ( strpos( $args['id'], 'mp-settings-gateway' ) === 0 ) {
			if ( $args['id'] != 'mp-settings-gateway-free_orders' ) {
				$args['page_slugs'][]       = 'store-setup-wizard';
				$args['hook']               = 'mp_wizard_payment_gateway_details';
				$args['show_submit_button'] = false;
				//$args['class']              = 'mp-wizard-gateway-detail';
			}
		}
		return $args;
	}

	/**
	 * Find currency base on country
	 *
	 * @since 3.0
	 * @access public
	 */
	function determine_currency() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die;
		}

		$code = mp_get_post_value( 'country', '' );
		if ( empty( $code ) ) {
			die;
		}

		if ( isset( $this->country_currencies[ $code ] ) ) {
			echo $this->country_currencies[ $code ];
		}
		die;
	}

}

MP_Setup_Wizard::get_instance();
