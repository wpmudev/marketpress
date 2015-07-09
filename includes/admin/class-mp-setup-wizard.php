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
		if ( mp_get_setting( 'mp_setup_complete' ) || mp_get_get_value( 'page' ) == 'store-setup-wizard' || mp_get_get_value( 'page' ) == 'mp-db-update' || !current_user_can( apply_filters( 'mp_store_settings_cap', 'manage_store_settings' ) ) ) {
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
			'id'				 => 'mp-quick-setup-wizard',
			'page_slugs'		 => array( 'store-setup-wizard' ),
			'title'				 => __( 'Quick Setup', 'mp' ),
			'option_name'		 => 'mp_settings',
			'show_submit_button' => false,
		) );

		$metabox->add_field( 'quick_setup', array(
			'name'		 => 'quick_setup',
			'label'		 => '',
			'message'	 => __( 'Quick Setup', 'mp' ),
			'class'		 => 'mp_quick_setup'
		) );

		$quick_setup_step = mp_get_get_value( 'quick_setup_step' );

		if ( isset( $quick_setup_step ) ) {

			/* Store Location */
			$metabox = new WPMUDEV_Metabox( array(
				'id'				 => 'mp-quick-setup-wizard-location',
				'page_slugs'		 => array( 'store-setup-wizard' ),
				'title'				 => __( 'Location', 'mp' ),
				'option_name'		 => 'mp_settings',
				'show_submit_button' => false,
			) );

			$metabox->add_field( 'advanced_select', array(
				'name'			 => 'base_country',
				'placeholder'	 => __( 'Select a Country', 'mp' ),
				'multiple'		 => false,
				'label'			 => array( 'text' => __( 'Base Country', 'mp' ) ),
				'options'		 => array( '' => __( 'Select A Country' ) ) + mp()->countries,
				'width'			 => 'element',
				'validation'	 => array(
					'required' => true,
				),
			) );

			$states = mp_get_states( mp_get_setting( 'base_country' ) );

			$metabox->add_field( 'advanced_select', array(
				'name'			 => 'base_province',
				'placeholder'	 => __( 'Select a State/Province/Region', 'mp' ),
				'multiple'		 => false,
				'label'			 => array( 'text' => __( 'Base State/Province/Region', 'mp' ) ),
				'options'		 => $states,
				'width'			 => 'element',
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => array( 'US', 'CA', 'GB', 'AU' ),
					'action' => 'show',
				),
				'validation'	 => array(
					'required' => true,
				),
			) );

			$metabox->add_field( 'text', array(
				'name'			 => 'base_zip',
				'label'			 => array( 'text' => __( 'Base Zip/Postal Code', 'mp' ) ),
				'style'			 => 'max-width: 150px;',
				'custom'		 => array(
					'minlength' => 3,
				),
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => array( 'US', 'CA', 'GB', 'AU', 'UM', 'AS', 'FM', 'GU', 'MH', 'MP', 'PW', 'PR', 'PI' ),
					'action' => 'show',
				),
				'validation'	 => array(
					'required' => true,
				),
			) );

			$metabox->add_field( 'text', array(
				'name'		 => 'zip_label',
				'label'		 => array( 'text' => __( 'Zip/Postal Code Label', 'mp' ) ),
				'custom'	 => array(
					'style' => 'max-width: 300px',
				),
				'validation' => array(
					'required' => true,
				),
			) );


			/* Sale to countries */
			$metabox = new WPMUDEV_Metabox( array(
				'id'			 => 'mp-quick-setup-wizard-countries',
				'page_slugs'	 => array( 'store-setup-wizard' ),
				'title'			 => __( 'Countries', 'mp' ),
				'option_name'	 => 'mp_settings',
				'order'			 => 1,
			) );

			// Target Countries
			$metabox->add_field( 'advanced_select', array(
				'name'					 => 'shipping[allowed_countries]',
				'label'					 => array( 'text' => __( 'Target Countries', 'mp' ) ),
				'desc'					 => __( 'These are the countries that you will ship to.', 'mp' ),
				'options'				 => mp_popular_country_list() + array( 'all_countries' => __( 'All Countries', 'mp' ) ) + mp_country_list(), //all_countries|disabled
				'default_value'			 => array( 'all_countries' => __( 'All Countries', 'mp' ) ),
				'placeholder'			 => __( 'Choose Countries', 'mp' ),
				'format_dropdown_header' => '
				<ul class="select2-all-none">
					<li class="select2-none">' . __( 'None', 'mp' ) . '</li>
					<li class="select2-all">' . __( 'All', 'mp' ) . '</li>
					<li class="select2-eu" data-countries="' . implode( ',', mp()->eu_countries ) . '">' . __( 'EU', 'mp' ) . '</li>
				</ul>',
				'validation' => array(
					'required' => true,
				),
			) );


			/* Currency options */
			$metabox = new WPMUDEV_Metabox( array(
				'id'			 => 'mp-quick-setup-wizard-currency',
				'page_slugs'	 => array( 'store-setup-wizard' ),
				'title'			 => __( 'Currency Settings', 'mp' ),
				'option_name'	 => 'mp_settings',
			) );

			$currencies	 = mp()->currencies;
			$options	 = array( '' => __( 'Select a Currency', 'mp' ) );

			foreach ( $currencies as $key => $value ) {
				$options[ $key ] = esc_attr( $value[ 0 ] ) . ' - ' . mp_format_currency( $key );
			}

			$metabox->add_field( 'advanced_select', array(
				'name'			 => 'currency',
				'placeholder'	 => __( 'Select a Currency', 'mp' ),
				'multiple'		 => false,
				'label'			 => array( 'text' => __( 'Store Currency', 'mp' ) ),
				'options'		 => $options,
				'width'			 => 'element',
			) );
			$metabox->add_field( 'radio_group', array(
				'name'			 => 'curr_symbol_position',
				'label'			 => array( 'text' => __( 'Currency Symbol Position', 'mp' ) ),
				'default_value'	 => '1',
				'orientation'	 => 'horizontal',
				'options'		 => array(
					'1'	 => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>100',
					'2'	 => '<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span> 100',
					'3'	 => '100<span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>',
					'4'	 => '100 <span class="mp-currency-symbol">' . mp_format_currency( mp_get_setting( 'currency', 'USD' ) ) . '</span>',
				),
			) );


			/* Tax options */
			$metabox = new WPMUDEV_Metabox( array(
				'id'			 => 'mp-quick-setup-wizard-tax',
				'page_slugs'	 => array( 'store-setup-wizard' ),
				'title'			 => __( 'Tax Settings', 'mp' ),
				'option_name'	 => 'mp_settings',
			) );

			$metabox->add_field( 'text', array(
				'name'			 => 'tax[rate]',
				'label'			 => array( 'text' => __( 'Default Tax Rate', 'mp' ) ),
				'after_field'	 => '%',
				'style'			 => 'max-width:75px',
				'validation'	 => array(
					'number' => true,
				),
				'placeholder'	 => '10',
				'conditional'	 => array(
					'name'	 => 'base_country',
					'value'	 => 'CA',
					'action' => 'hide',
				),
			) );

			// Create field for each canadian province
			foreach ( mp()->canadian_provinces as $key => $label ) {
				$metabox->add_field( 'text', array(
					'name'			 => 'tax[canada_rate][' . $key . ']',
					'desc'			 => '<a target="_blank" href="http://en.wikipedia.org/wiki/Sales_taxes_in_Canada">' . __( 'Current Rates', 'mp' ) . '</a>',
					'label'			 => array( 'text' => sprintf( __( '%s Tax Rate', 'mp' ), $label ) ),
					'custom'		 => array( 'style' => 'width:75px' ),
					'after_field'	 => '%',
					'conditional'	 => array(
						'name'	 => 'base_country',
						'value'	 => 'CA',
						'action' => 'show',
					),
				) );
			}

			$metabox->add_field( 'text', array(
				'name'			 => 'tax[label]',
				'label'			 => array( 'text' => __( 'Tax Label', 'mp' ) ),
				'style'			 => 'max-width: 300px',
				'placeholder'	 => __( 'I.e. Taxes, VAT, GST, etc', 'mp' )
			) );
			$metabox->add_field( 'checkbox', array(
				'name'		 => 'tax[tax_shipping]',
				'label'		 => array( 'text' => __( 'Apply Tax To Shipping Fees?', 'mp' ) ),
				'message'	 => __( 'Yes', 'mp' ),
				'class' => 'mp-quick-field-inline-block'
			) );
			$metabox->add_field( 'checkbox', array(
				'name'		 => 'tax[tax_inclusive]',
				'label'		 => array( 'text' => __( 'Enter Prices Inclusive of Tax?', 'mp' ) ),
				'message'	 => __( 'Yes', 'mp' ),
				'class' => 'mp-quick-field-inline-block'
			) );
			$metabox->add_field( 'checkbox', array(
				'name'		 => 'tax[tax_digital]',
				'label'		 => array( 'text' => __( 'Apply Tax to Downloadable Products?', 'mp' ) ),
				'message'	 => __( 'Yes', 'mp' ),
				'class' => 'mp-quick-field-inline-block'
			) );


			// Measurement System

			$metabox = new WPMUDEV_Metabox( array(
				'id'			 => 'mp-quick-setup-wizard-measurement-system',
				'page_slugs'	 => array( 'store-setup-wizard' ),
				'title'			 => __( 'Measurement System', 'mp' ),
				'option_name'	 => 'mp_settings',
			) );

			$metabox->add_field( 'radio_group', array(
				'name'			 => 'shipping[system]',
				'options'		 => array(
					'english'	 => __( 'Pounds', 'mp' ),
					'metric'	 => __( 'Kilograms', 'mp' ),
				),
				'default_value'	 => 'english',
			) );
		}


		/* $metabox = new WPMUDEV_Metabox( array(
		  'id'				 => 'mp-setup-wizard-basic-store-info',
		  'page_slugs'		 => array( 'store-setup-wizard' ),
		  'title'				 => __( 'Basic Store Info', 'mp' ),
		  'option_name'		 => 'mp_settings',
		  'show_submit_button' => false,
		  ) );
		  $metabox->add_field( 'radio_group', array(
		  'name'			 => 'store_type',
		  'label'			 => array( 'text' => __( 'What type of store would you like to setup?', 'mp' ) ),
		  'orientation'	 => 'vertical',
		  'options'		 => array(
		  'no_cart'	 => __( 'Product listing, without shopping cart etc. (eg. Amazon Affiliate links)', 'mp' ),
		  'digital'	 => __( 'Digital Download Products', 'mp' ),
		  'physical'	 => __( 'Physical Products', 'mp' ),
		  'mix'		 => __( 'Mix of Digital &amp; Physical Products', 'mp' ),
		  ),
		  'default_value'	 => 'physical',
		  ) );
		  $metabox->add_field( 'radio_group', array(
		  'name'			 => 'product_button_type',
		  'label'			 => array( 'text' => __( 'What type of checkout experience would you like to offer?', 'mp' ) ),
		  'orientation'	 => 'vertical',
		  'options'		 => array(
		  'addcart'	 => __( 'Keep buyer on current page after adding an item to their cart', 'mp' ),
		  'buynow'	 => __( 'Buyers are immediately taken to the checkout page after adding an item to their cart', 'mp' ),
		  ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'	 => 'has_csv_file',
		  'label'	 => array( 'text' => __( 'Do you have a CSV file to import products from?', 'mp' ) ),
		  ) );
		  $metabox->add_field( 'file', array(
		  'name'			 => 'csv_file',
		  'label'			 => array( 'text' => __( 'CSV File', 'mp' ) ),
		  'conditional'	 => array(
		  'name'	 => 'has_csv_file',
		  'value'	 => 1,
		  'action' => 'show',
		  ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'			 => 'collect_taxes',
		  'label'			 => array( 'text' => __( 'Do you need to collect taxes?', 'mp' ) ),
		  'conditional'	 => array(
		  'name'	 => 'store_type',
		  'value'	 => 'no_cart',
		  'action' => 'hide',
		  ),
		  ) );


		  // Location setup
		  $metabox = new WPMUDEV_Metabox( array(
		  'id'				 => 'mp-store-setup-location',
		  'page_slugs'		 => array( 'store-setup-wizard' ),
		  'title'				 => __( 'Store Location', 'mp' ),
		  'option_name'		 => 'mp_settings',
		  'show_submit_button' => false,
		  ) );
		  $metabox->add_field( 'advanced_select', array(
		  'name'			 => 'base_country',
		  'placeholder'	 => __( 'Select a Country', 'mp' ),
		  'multiple'		 => false,
		  'label'			 => array( 'text' => __( 'Base Country', 'mp' ) ),
		  'options'		 => array( '' => __( 'Select A Country' ) ) + mp()->countries,
		  'width'			 => 'element',
		  ) );
		  $states	 = mp_get_states( mp_get_setting( 'base_country' ) );
		  $metabox->add_field( 'advanced_select', array(
		  'name'			 => 'base_province',
		  'placeholder'	 => __( 'Select a State/Province/Region', 'mp' ),
		  'multiple'		 => false,
		  'label'			 => array( 'text' => __( 'Base State/Province/Region', 'mp' ) ),
		  'options'		 => $states,
		  'width'			 => 'element',
		  'conditional'	 => array(
		  'name'	 => 'base_country',
		  'value'	 => array( 'US', 'CA', 'GB', 'AU' ),
		  'action' => 'show',
		  ),
		  ) );
		  $metabox->add_field( 'text', array(
		  'name'			 => 'base_zip',
		  'label'			 => array( 'text' => __( 'Base Zip/Postal Code', 'mp' ) ),
		  'style'			 => 'width:150px;',
		  'custom'		 => array(
		  'minlength' => 3,
		  ),
		  'conditional'	 => array(
		  'name'	 => 'base_country',
		  'value'	 => array( 'US', 'CA', 'GB', 'AU', 'UM', 'AS', 'FM', 'GU', 'MH', 'MP', 'PW', 'PR', 'PI' ),
		  'action' => 'show',
		  ),
		  ) );

		  // Store pages
		  $metabox = new WPMUDEV_Metabox( array(
		  'id'			 => 'mp-setup-wizard-pages-slugs',
		  'page_slugs'	 => array( 'store-setup-wizard' ),
		  'title'			 => __( 'Store Pages &amp; Slugs', 'mp' ),
		  'option_name'	 => 'mp_settings',
		  ) );
		  $metabox->add_field( 'post_select', array(
		  'name'			 => 'pages[store]',
		  'label'			 => array( 'text' => __( 'Store Base', 'mp' ) ),
		  'desc'			 => __( 'This page will be used as the root for your store.', 'mp' ),
		  'query'			 => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
		  'placeholder'	 => __( 'Choose a Page', 'mp' ),
		  ) );
		  $metabox->add_field( 'post_select', array(
		  'name'			 => 'pages[products]',
		  'label'			 => array( 'text' => __( 'Products List', 'mp' ) ),
		  'query'			 => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
		  'placeholder'	 => __( 'Choose a Page', 'mp' ),
		  ) );
		  $metabox->add_field( 'post_select', array(
		  'name'			 => 'pages[cart]',
		  'label'			 => array( 'text' => __( 'Shopping Cart', 'mp' ) ),
		  'query'			 => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
		  'placeholder'	 => __( 'Choose a Page', 'mp' ),
		  ) );
		  $metabox->add_field( 'post_select', array(
		  'name'			 => 'pages[checkout]',
		  'label'			 => array( 'text' => __( 'Checkout', 'mp' ) ),
		  'query'			 => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
		  'placeholder'	 => __( 'Choose a Page', 'mp' ),
		  ) );
		  $metabox->add_field( 'post_select', array(
		  'name'			 => 'pages[order_status]',
		  'label'			 => array( 'text' => __( 'Order Status', 'mp' ) ),
		  'query'			 => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
		  'placeholder'	 => __( 'Choose a Page', 'mp' ),
		  ) );

		  // Tax setup
		  $metabox = new WPMUDEV_Metabox( array(
		  'id'			 => 'mp-setup-wizard-tax-info',
		  'page_slugs'	 => array( 'store-setup-wizard' ),
		  'title'			 => __( 'Setup Taxes', 'mp' ),
		  'option_name'	 => 'mp_settings',
		  'conditional'	 => array(
		  'name'	 => 'collect_taxes',
		  'value'	 => 1,
		  'action' => 'show',
		  ),
		  ) );
		  $metabox->add_field( 'text', array(
		  'name'			 => 'tax[rate]',
		  'label'			 => array( 'text' => __( 'Tax Rate', 'mp' ) ),
		  'after_field'	 => '%',
		  'style'			 => 'width:75px',
		  'validation'	 => array(
		  'number' => true,
		  ),
		  ) );
		  $metabox->add_field( 'text', array(
		  'name'	 => 'tax[label]',
		  'label'	 => array( 'text' => __( 'Tax Label', 'mp' ) ),
		  'style'	 => 'width:300px',
		  'desc'	 => __( 'The label shown for the tax line item in the cart. Taxes, VAT, GST, etc.', 'mp' ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'			 => 'tax[tax_shipping]',
		  'label'			 => array( 'text' => __( 'Apply Tax To Shipping Fees?', 'mp' ) ),
		  'desc'			 => __( 'Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp' ),
		  'message'		 => __( 'Yes', 'mp' ),
		  'conditional'	 => array(
		  'name'	 => 'store_type',
		  'value'	 => 'digital',
		  'action' => 'hide',
		  ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'		 => 'tax[tax_inclusive]',
		  'label'		 => array( 'text' => __( 'Enter Prices Inclusive of Tax?', 'mp' ) ),
		  'desc'		 => __( 'Enabling this option allows you to enter and show all prices inclusive of tax, while still listing the tax total as a line item in shopping carts. Please see your local tax laws.', 'mp' ),
		  'message'	 => __( 'Yes', 'mp' ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'			 => 'tax[tax_digital]',
		  'label'			 => array( 'text' => __( 'Apply Tax to Downloadable Products?', 'mp' ) ),
		  'desc'			 => __( 'Please see your local tax laws. Note if this is enabled and a downloadable only cart, rates will be the default for your base location.', 'mp' ),
		  'message'		 => __( 'Yes', 'mp' ),
		  'conditional'	 => array(
		  'name'	 => 'store_type',
		  'value'	 => 'physical',
		  'action' => 'hide',
		  ),
		  ) );
		  $metabox->add_field( 'checkbox', array(
		  'name'			 => 'tax[downloadable_address]',
		  'label'			 => array( 'text' => __( 'Collect Address on Downloadable Only Cart?', 'mp' ) ),
		  'desc'			 => __( 'If you need to tax downloadable products and don\'t want to default to the rates to your base location, enable this to always collect the shipping address. ', 'mp' ),
		  'message'		 => __( 'Yes', 'mp' ),
		  'conditional'	 => array(
		  'name'	 => 'store_type',
		  'value'	 => 'physical',
		  'action' => 'hide',
		  ),
		  ) ); */
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
	}

}

MP_Setup_Wizard::get_instance();
