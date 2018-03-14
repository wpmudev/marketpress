<?php
add_action( 'wp_ajax_mp_update_currency', array( 'MP_Store_Settings_General', 'ajax_mp_update_currency' ) );

class MP_Store_Settings_General {

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
			self::$_instance = new MP_Store_Settings_General();
		}
		return self::$_instance;
	}

	/**
	 * Gets an updated currency symbol based upon a given currency code
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_currency
	 */
	public static function ajax_mp_update_currency() {
		if ( check_ajax_referer( 'mp_update_currency', 'nonce', false ) ) {
			$currency = mp_format_currency( mp_get_get_value( 'currency' ) );
			wp_send_json_success( $currency );
		}

		wp_send_json_error();
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'wpmudev_field/print_scripts/base_country', array( &$this, 'update_states_dropdown' ) );
		add_action( 'wpmudev_field/print_scripts/currency', array( &$this, 'update_currency_symbol' ) );
		add_action( 'wpmudev_metabox/after_settings_metabox_saved', array( &$this, 'update_product_post_type' ) );
		add_action( 'init', array( &$this, 'init_metaboxes' ) );

		add_filter( 'wpmudev_field/format_value/tax[rate]', array( &$this, 'format_tax_rate_value' ), 10, 2 );
		add_filter( 'wpmudev_field/sanitize_for_db/tax[rate]', array( &$this, 'save_tax_rate_value' ), 10, 3 );

		foreach ( mp()->CA_provinces as $key => $value ) {
			add_filter( 'wpmudev_field/format_value/tax[canada_rate][' . $key . ']', array( &$this, 'format_tax_rate_value' ), 10, 2 );
			add_filter( 'wpmudev_field/sanitize_for_db/tax[canada_rate][' . $key . ']', array( &$this, 'save_tax_rate_value' ), 10, 3 );
		}
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_location_settings();
		$this->init_tax_settings();
		if( ! is_multisite() || ! mp_cart()->is_global ) $this->init_currency_settings();
		$this->init_digital_settings();
		$this->init_download_settings();
		$this->init_misc_settings();
		$this->init_advanced_settings();
	}

	/**
	 * Update the product post type
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_metabox/settings_metabox_saved
	 * @uses $wpdb
	 */
	public function update_product_post_type( $metabox ) {
		global $wpdb;

		if ( $metabox->args[ 'id' ] != 'mp-settings-general-advanced-settings' ) {
			return;
		}

		$new_product_post_type = mp_get_setting( 'product_post_type' );
		$old_product_post_type = $new_product_post_type == 'mp_product' ? 'product' : 'mp_product';

		// Check if there is at least 1 product with the old post type
		$check = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_type = '{$old_product_post_type}'", ARRAY_A );
		if ( null === $check ) {
			return;
		}

		$wpdb->update( $wpdb->posts, array( 'post_type' => $new_product_post_type ), array( 'post_type' => $old_product_post_type ) );
		update_option( 'mp_flush_rewrites', 1 );
	}

	/**
	 * Formats the tax rate value from decimal to percentage
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/get_value
	 * @return string
	 */
	public function format_tax_rate_value( $value, $field ) {
		return ( (float)$value * 100 );
	}

	/**
	 * Formats the tax rate value from percentage to decimal prior to saving to db
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/sanitize_for_db
	 * @return string
	 */
	public function save_tax_rate_value( $value, $post_id, $field ) {
		return ( $value > 0 ) ? ($value / 100) : 0;
	}

	/**
	 * Prints javascript for updating the currency symbol when user updates the currency value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts/currency
	 */
	public function update_currency_symbol( $field ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var $currency = $( 'select[name="currency"]' );

				$currency.on( 'change', function( e ) {
					var data = [
						{
							"name": "currency",
							"value": $(this).val()
						}, {
							"name": "action",
							"value": "mp_update_currency"
						}, {
							"name": "nonce",
							"value": "<?php echo wp_create_nonce( 'mp_update_currency' ); ?>"
						}
					];

					$currency.mp_select2( 'enable', false ).isWorking( true );

					$.get( ajaxurl, $.param( data ) ).done( function( resp ) {
						$currency.mp_select2( 'enable', true ).isWorking( false );

						if ( resp.success ) {
							$( '.mp-currency-symbol' ).html( resp.data );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Prints javascript for updating the base_province dropdown when user updates the base_country value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts/base_country
	 */
	public function update_states_dropdown( $field ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var $country = $( 'select[name="base_country"]' ),
					$state = $( 'select[name="base_province"]' );

				$country.on( 'change', function() {
					var data = {
						country: $country.val(),
						action: "mp_update_states_dropdown"
					};

					$country.mp_select2( 'enable', false ).isWorking( true );
					$state.mp_select2( 'enable', false );

					$.post( ajaxurl, data ).done( function( resp ) {
						$country.mp_select2( 'enable', true ).isWorking( false );
						$state.mp_select2( 'enable', true );

						if ( resp.success ) {
							$state.html( resp.data.states );
							$state.trigger( 'change' );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Init advanced settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_advanced_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-advanced-settings',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Advanced Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'			 => 'product_post_type',
			'label'			 => array( 'text' => __( 'Change product post type', 'mp' ) ),
			'desc'		 => __( 'If you are experiencing conflicts with other e-commerce plugins change this setting. This will change the internal post type of all your products. <strong>Please note that changing this option may break 3rd party themes or plugins.</strong>', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
			'default_value'	 => 'product',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'product'	 => __( 'product (default)', 'mp' ),
				'mp_product'	 => 'mp_product',
			),
		) );
	}

	/**
	 * Init download settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_download_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-downloads',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Download Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'max_downloads',
			'label'		 => array( 'text' => __( 'Maximum Downloads', 'mp' ) ),
			'desc'		 => __( 'How many times may a customer download a file they have purchased? (It\'s best to set this higher than one in case they have any problems downloading)', 'mp' ),
			'style'		 => 'width:50px;',
			'validation' => array(
				'required'	 => true,
				'digits'	 => true,
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'	 => 'use_alt_download_method',
			'label'	 => array( 'text' => __( 'Use Alternative Download Method?', 'mp' ) ),
			'desc'	 => __( 'If you\'re having issues downloading large files and have worked with your hosting provider to increase your memory limits, try enabling this - just keep in mind, it\'s not as secure!', 'mp' ),
		) );
	}

	/**
	 * Init misc settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_misc_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-misc',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Miscellaneous Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'inventory_threshhold',
			'label'		 => array( 'text' => __( 'Inventory Warning Threshold', 'mp' ) ),
			'desc'		 => __( 'At what low stock count do you want to be warned for products you have enabled inventory tracking for?', 'mp' ),
			'style'		 => 'width:50px;',
			'validation' => array(
				'required'	 => true,
				'digits'	 => true,
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'inventory_remove',
			'label'		 => array( 'text' => __( 'Hide Out of Stock Products?', 'mp' ) ),
			'desc'		 => __( 'This will set the product to draft if inventory of all variations is gone.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'force_login',
			'label'		 => array( 'text' => __( 'Force Login?', 'mp' ) ),
			'desc'		 => __( 'Whether or not customers must be registered and logged in to checkout. (Not recommended: Enabling this can lower conversions)', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'disable_cart',
			'label'		 => array( 'text' => __( 'Disable Cart?', 'mp' ) ),
			'desc'		 => __( 'This option turns MarketPress into more of a product listing plugin, disabling shopping carts, checkout, and order management. This is useful if you simply want to list items you can buy in a store somewhere else, optionally linking the "Buy Now" buttons to an external site. Some examples are a car dealership, or linking to songs/albums in itunes, or linking to products on another site with your own affiliate links.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'       => 'show_orders',
			'label'      => array( 'text' => __( 'Show admin Orders page?', 'mp' ) ),
			'desc'		 => __( 'If unchecked your Orders admin page will be hidden', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
			'conditional' => array(
				'name'   => 'disable_cart',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'disable_minicart',
			'label'		 => array( 'text' => __( 'Disable Mini Cart?', 'mp' ) ),
			'desc'		 => __( 'This option hide floating Mini Cart in top right corner.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_image',
			'label'         => array( 'text' => __( 'Show product image on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product image on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
			'default_value' => true,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_qty',
			'label'         => array( 'text' => __( 'Show product quantity on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product quantity on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
			'default_value' => true,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_price',
			'label'         => array( 'text' => __( 'Show product price on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product price on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'ga_ecommerce',
			'label'			 => array( 'text' => __( 'Google Analytics Ecommerce Tracking', 'mp' ) ),
			'desc'			 => __( 'If you already use Google Analytics for your website, you can track detailed ecommerce information by enabling this setting. Choose whether you are using the new asynchronous or old tracking code. Before Google Analytics can report ecommerce activity for your website, you must enable ecommerce tracking on the profile settings page for your website. Also keep in mind that some gateways do not reliably show the receipt page, so tracking may not be accurate in those cases. It is recommended to use the PayPal gateway for the most accurate data. <a target="_blank" href="http://analytics.blogspot.com/2009/05/how-to-use-ecommerce-tracking-in-google.html">More information &raquo;</a>', 'mp' ),
			'default_value'	 => 'none',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'none'		 => __( 'None', 'mp' ),
				'new'		 => __( 'New', 'mp' ),
				'old'		 => __( 'Old', 'mp' ),
				'universal'	 => __( 'Universal', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'special_instructions',
			'label'		 => array( 'text' => __( 'Show Special Instructions Field?', 'mp' ) ),
			'desc'		 => __( 'Enabling this field will display a textbox on the shipping checkout page for users to enter special instructions for their order. Useful for product personalization, etc.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
	}

	/**
	 * Init currency settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_currency_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-currency',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
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
		
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'price_format',
			'label'			 => array( 'text' => __( 'Price Format', 'mp' ) ),
			'default_value'	 => 'en',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'en'	 => '1,123.45',
				'eu'	 => '1.123,45',
				'frc'	 => '1 123,45',
				'frd'	 => '1 123.45',
			),
		) );
		
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'curr_decimal',
			'label'			 => array( 'text' => __( 'Show Decimal in Prices', 'mp' ) ),
			'default_value'	 => '1',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'0'	 => '100',
				'1'	 => '100.00',
			),
		) );
	}

	/**
	 * Init tax settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_tax_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-tax',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Tax Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'			 => 'tax[rate]',
			'label'			 => array( 'text' => __( 'Tax Rate', 'mp' ) ),
			'after_field'	 => '%',
			'style'			 => 'width:75px',
			'validation'	 => array(
				'number' => true,
			),
			/*'conditional'	 => array(
				'name'	 => 'base_country',
				'value'	 => 'CA',
				'action' => 'hide',
			),*/
		) );

		// Create field for each canadian province
		foreach ( mp()->CA_provinces as $key => $label ) {
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
			'name'	 => 'tax[label]',
			'label'	 => array( 'text' => __( 'Tax Label', 'mp' ) ),
			'style'	 => 'width:300px',
			'desc'	 => __( 'The label shown for the tax line item in the cart. Taxes, VAT, GST, etc.', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_shipping]',
			'label'		 => array( 'text' => __( 'Apply Tax To Shipping Fees?', 'mp' ) ),
			'desc'		 => __( 'Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_inclusive]',
			'label'		 => array( 'text' => __( 'Enter Prices Inclusive of Tax?', 'mp' ) ),
			'desc'		 => __( 'Enabling this option allows you to enter and show all prices inclusive of tax, while still listing the tax total as a line item in shopping carts. Please see your local tax laws.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[include_tax]',
			'label'		 => array( 'text' => __( 'Show Price + Tax?', 'mp' ) ),
			'desc'		 => __( 'Enabling this option will show Price + Tax, eg. if your price is 100 and your tax 20, your price will be 120', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_label]',
			'label'		 => array( 'text' => __( 'Display tax label?', 'mp' ) ),
			'desc'		 => __( 'Enabling this option will display label `excl. tax` or `incl. tax` after price', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'tax[tax_digital]',
			'label'		 => array( 'text' => __( 'Apply Tax to Digital Products?', 'mp' ) ),
			'desc'		 => __( 'Please see your local tax laws. Note if this is enabled and a downloadable only cart, rates will be the default for your base location.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		/*
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'tax[tax_based]',
			'label'			 => array( 'text' => __( 'Tax based on?', 'mp' ) ),
			'default_value'	 => 'store_tax',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'store_tax'	 => __( 'Apply tax based on store location', 'mp' ),
				'user_tax'	 => __( 'Apply tax based on customer location', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'tax[tax_digital]',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		*/
	}
	
	/**
	 * Init digital products settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_digital_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-digital',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Digital Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'download_order_limit',
			'label'		 => array( 'text' => __( 'Limit Digital Products Per-order?', 'mp' ) ),
			'desc'		 => __( 'This will prevent multiples of the same downloadable product form being added to the cart.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'details_collection',
			'label'			 => array( 'text' => __( 'Details Collection', 'mp' ) ),
			'default_value'	 => 'contact',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'full'		 => __( 'Full billing info', 'mp' ),
				'contact'		 => __( 'Only contact details', 'mp' ),
			),
		) );
		
	}

	/**
	 * Init location settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_location_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-general-location',
			'page_slugs'	 => array( 'store-settings', 'toplevel_page_store-settings' ),
			'title'			 => __( 'Location Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'			 => 'base_country',
			'placeholder'	 => __( 'Select a Country', 'mp' ),
			'multiple'		 => false,
			'label'			 => array( 'text' => __( 'Base Country', 'mp' ) ),
			'options'		 => array( '' => __( 'Select A Country', 'mp' ) ) + mp_countries(),
			'width'			 => 'element',
			'validation'	 => array(
				'required' => true,
			),
		) );

		$countries_with_states = array();
		foreach ( mp_countries() as $code => $country ) {
			if( property_exists( mp(), $code.'_provinces' ) ) {
				$countries_with_states[] = $code;
			}
		}
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
				'value'	 => $countries_with_states,
				'action' => 'show',
			),
			'validation'	 => array(
				'required' => true,
			),
		) );

		$countries_without_postcode = array_keys( mp()->countries_no_postcode );
		$metabox->add_field( 'text', array(
			'name'			 => 'base_zip',
			'label'			 => array( 'text' => __( 'Base Zip/Postal Code', 'mp' ) ),
			'style'			 => 'width:150px;',
			'custom'		 => array(
				'minlength' => 3,
			),
			'conditional'	 => array(
				'name'	 => 'base_country',
				'value'	 => $countries_without_postcode,
				'action' => 'hide',
			),
			'validation'	 => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'zip_label',
			'label'		 => array( 'text' => __( 'Zip/Postal Code Label', 'mp' ) ),
			'custom'	 => array(
				'style' => 'width:300px',
			),
			'validation' => array(
				'required' => true,
			),
		) );
	}

}

MP_Store_Settings_General::get_instance();
