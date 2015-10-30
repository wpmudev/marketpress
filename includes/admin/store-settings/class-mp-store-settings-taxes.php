<?php
class MP_Store_Settings_Taxes {

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
			self::$_instance = new MP_Store_Settings_Taxes();
		}

		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'init', array( &$this, 'init_metaboxes' ) );

		add_filter( 'wpmudev_field/format_value/tax[rate]', array( &$this, 'format_tax_rate_value' ), 10, 2 );
		add_filter( 'wpmudev_field/sanitize_for_db/tax[rate]', array( &$this, 'save_tax_rate_value' ), 10, 3 );
		add_filter( 'wpmudev_field/after_field/tax[tax_tables]', array( $this, 'print_tags_scripts' ), 10, 2 );

		//cleanup table rates if a table removed
		add_action( 'wp_loaded', array( &$this, 'cleanup_tax_tables' ) );

		foreach ( mp()->canadian_provinces as $key => $value ) {
			add_filter( 'wpmudev_field/format_value/tax[canada_rate][' . $key . ']', array(
				&$this,
				'format_tax_rate_value'
			), 10, 2 );
			add_filter( 'wpmudev_field/sanitize_for_db/tax[canada_rate][' . $key . ']', array(
				&$this,
				'save_tax_rate_value'
			), 10, 3 );
		}
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_tax_settings();
		$this->init_shipping_tax_settings();
		$this->init_digital_tax_settings();
	}
	
	/**
	 * We will remove the data of a table if a tax table removed
	 */
	public function cleanup_tax_tables() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tables = mp_get_setting( 'tax->tax_tables' );
		$tables = json_decode( stripslashes( $tables ) );
		if ( ! is_array( $tables ) ) {
			$tables = array();
		}

		$slugs = array();
		$data  = mp_get_setting( 'tax->tables_data', array() );

		foreach ( $tables as $table ) {
			$slugs[] = str_replace( '-', '_', sanitize_title( $table ) );
		}

		foreach ( $data as $key => $val ) {
			if ( $key == 'standard' ) {
				continue;
			}
			if ( ! in_array( $key, $slugs ) ) {
				unset( $data[ $key ] );
			}
		}
		mp_update_setting( 'tax->tables_data', $data );
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
		return ( $value * 100 );
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
		return ( $value > 0 ) ? ( $value / 100 ) : 0;
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
			jQuery(document).ready(function ($) {
				var $currency = $('select[name="currency"]');

				$currency.on('change', function (e) {
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

					$currency.mp_select2('enable', false).isWorking(true);

					$.get(ajaxurl, $.param(data)).done(function (resp) {
						$currency.mp_select2('enable', true).isWorking(false);

						if (resp.success) {
							$('.mp-currency-symbol').html(resp.data);
						}
					});
				});
			});
		</script>
		<?php
	}

	public function init_shipping_tax_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-shipping-tax',
			'page_slugs'  => array('store-settings-taxes', 'store-settings_page_store-settings-taxes'),
			'title'       => __( 'Shipping Tax Settings', 'mp' ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_shipping]',
			'label'   => array( 'text' => __( 'Apply Tax to Shipping?', 'mp' ) ),
			'desc'    => __( 'Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
			'default_value' => 'true'
		) );
		$options = mp_tax()->get_table_rates();
		$metabox->add_field( 'advanced_select', array(
			'name'          => 'tax[shipping_tax_rate]',
			'label'         => array( 'text' => __( "Shipping Tax table", "mp" ) ),
			'width'         => 'element',
			'conditional'   => array(
				'name'   => 'tax[tax_shipping]',
				'value'  => 1,
				'action' => 'show',
			),
			'multiple'      => false,
			'options'       => $options,
			'default_value' => 'cart_items'
		) );
	}

	public function init_digital_tax_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-digital-tax',
			'page_slugs'  => array('store-settings-taxes', 'store-settings_page_store-settings-taxes'),
			'title'       => __( 'Digital Tax Settings', 'mp' ),
			'option_name' => 'mp_settings',
			'conditional' => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_digital]',
			'label'   => array( 'text' => __( 'Apply Tax to Digital Products?', 'mp' ) ),
			'desc'    => __( 'Please see your local tax laws. Note if this is disable and a downloadable only cart, only collect name and email.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
			'default_value' => 'true'
		) );
		$options = mp_tax()->get_table_rates();
		$metabox->add_field( 'advanced_select', array(
			'name'        => 'tax[dp_tax_rate]',
			'label'       => array( 'text' => __( "Digital Products Tax table", "mp" ) ),
			'width'       => 'element',
			'conditional' => array(
				'name'   => 'tax[tax_digital]',
				'value'  => 1,
				'action' => 'show',
			),
			'multiple'    => false,
			'options'     => $options,
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'          => 'tax[tax_dp_calculate_based]',
			'label'         => array( 'text' => __( "Calculate Digital Product Tax based on", "mp" ) ),
			'width'         => 'element',
			'conditional'   => array(
				'name'   => 'tax[tax_digital]',
				'value'  => 1,
				'action' => 'show',
			),
			'multiple'      => false,
			'options'       => array(
				'billing_address' => __( "Customer Billing Address", "mp" ),
				'store_address'   => __( "Store Address", "mp" )
			),
			'default_value' => mp_get_setting( 'tax->tax_calculate_based' )
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
			'id'          => 'mp-settings-general-tax',
			'page_slugs'  => array('store-settings-taxes', 'store-settings_page_store-settings-taxes'),
			'title'       => __( 'Tax Settings', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_enable]',
			'label'   => array( 'text' => __( 'Enable Taxes', 'mp' ) ),
			//'desc'    => __( 'Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'tax[tax_label]',
			'label'         => array( 'text' => __( 'Display tax label?', 'mp' ) ),
			'desc'          => __( 'Enabling this option will display label `excl. tax` or `incl. tax` after price', 'mp' ),
			'default_value' => 1,
			'conditional'   => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'tax[set_price_with_tax]',
			'label'         => array( 'text' => __( 'Enter prices with Tax', 'mp' ) ),
			'options'       => array(
				'inclusive' => __( 'Inclusive', 'mp' ),
				'exclusive' => __( 'Exclusive', 'mp' ),
			),
			'default_value' => 'inclusive',
			'desc'          => 'Choose how you want to enter prices for products.',
			'conditional'   => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'tax[show_price_with_tax]',
			'label'         => array( 'text' => __( 'Show prices with Tax', 'mp' ) ),
			'options'       => array(
				'inclusive' => __( 'Inclusive', 'mp' ),
				'exclusive' => __( 'Exclusive', 'mp' ),
			),
			'desc'          => 'Choose how you want to display prices of products on store. <strong>Inclusive:</strong> eg. if your price is 100 and your tax 20, your price will be 120 <strong>Exclusive:</strong> eg: if your price is 100 and your tax 20, your price will be 100',
			'default_value' => 'inclusive',
			'conditional'   => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => 'tax[cart_price_with_tax]',
			'label'         => array( 'text' => __( 'Show prices on cart/mini-cart', 'mp' ) ),
			'options'       => array(
				'inclusive' => __( 'Inclusive', 'mp' ),
				'exclusive' => __( 'Exclusive', 'mp' ),
			),
			'desc'          => 'Choose if you want to show prices on cart/mini-cart with tax inclusive or exclusive',
			'default_value' => 'inclusive',
			'conditional'   => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'advanced_select', array(
			'name'          => 'tax[tax_calculate_based]',
			'label'         => array( 'text' => __( "Calculate Tax based on", "mp" ) ),
			'width'         => 'element',
			'conditional'   => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
			'multiple'      => false,
			'options'       => array(
				'shipping_address' => __( "Customer Shipping Address", "mp" ),
				'billing_address'  => __( "Customer Billing Address", "mp" ),
				'store_address'    => __( "Store Address", "mp" )
			),
			'default_value' => 'store_address'
		) );

		$options = mp_tax()->get_table_rates();

		$metabox->add_field( 'textarea', array(
			'name'        => 'tax[tax_tables]',
			'placeholder' => __( 'Add new taxes table', 'mp' ),
			'label'       => array( 'text' => __( 'Additional Taxes tables', 'mp' ) ),
			'desc'         => 'You should save your settings before you are able to edit tables',
			'width'       => '100%',
			'conditional' => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		$tables = array(
			array(
				'active' => true,
				'label'  => __( "Standard", "mp" ),
				'slug'   => 'standard',
			),

		);

		foreach ( mp_tax()->get_table_rates() as $key => $et ) {
			if ( sanitize_title( $key ) == 'standard' ) {
				continue;
			}

			$tables[] = array(
				'label' => $et,
				'slug'  => sanitize_title( $et ),
			);
		}
		//add tabs
		$metabox->add_field( 'tab_labels', array(
			'name'        => 'tax_tabs',
			'tabs'        => $tables,
			'conditional' => array(
				'name'   => 'tax[tax_enable]',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		foreach ( $tables as $id => $table ) {
			$name          = 'tax[tables_data][' . str_replace( '-', '_', $table['slug'] ) . ']';
			$table_metabox = new WPMUDEV_Metabox( array(
				'id'                 => 'mp-settings-general-tax-table-' . $table['slug'],
				'page_slugs'  		 => array('store-settings-taxes', 'store-settings_page_store-settings-taxes'),
				'title'              => __( 'Tax Settings', 'mp' ),
				'option_name'        => 'mp_settings',
				'hook'               => 'wpmudev_tab_field_display_' . $table['slug'],
				'show_submit_button' => false,
				'conditional'        => array(
					'name'   => 'tax[tax_enable]',
					'value'  => 1,
					'action' => 'show',
				),
			) );

			$repeater = $table_metabox->add_field( 'repeater', array(
				'name'          => $name,
				'label'         => array( 'text' => sprintf( __( 'Tax Rates for the "%s" table', 'mp' ), $table['label'] ) ),
				'desc'          => __( 'Define tax rates for countries and states below. See here for available alpha-2 country codes.', 'mp' ),
				'add_row_label' => __( 'Insert Row', 'mp' ),
				'conditional'   => array(
					'name'   => 'tax[tax_enable]',
					'value'  => 1,
					'action' => 'show',
				),
			) );

			if ( $repeater instanceof WPMUDEV_Field ) {
				$repeater->add_sub_field( 'text', array(
					'name'          => 'country_code',
					'label'         => array( 'text' => __( 'Country Code', 'mp' ) ),
					'default_value' => '*',
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'state_code',
					'label'         => array( 'text' => __( 'State Code', 'mp' ) ),
					'default_value' => '*',
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'city',
					'label'         => array( 'text' => __( 'City Code', 'mp' ) ),
					'default_value' => '*',
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'zip',
					'label'         => array( 'text' => __( 'Postal Code', 'mp' ) ),
					'default_value' => '*',
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'rate',
					'label'         => array( 'text' => __( 'Rate', 'mp' ) ),
					'default_value' => '0',
					'class'         => 'tax_field tax_rate'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'display_name',
					'label'         => array( 'text' => __( 'Display Name', 'mp' ) ),
					'default_value' => __( "Tax", "mp" ),
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'text', array(
					'name'          => 'priority',
					'label'         => array( 'text' => __( 'Priority', 'mp' ) ),
					'default_value' => 1,
					'class'         => 'tax_field'
				) );
				$repeater->add_sub_field( 'checkbox', array(
					'name'  => 'compound',
					'label' => array( 'text' => __( 'Compound', 'mp' ) ),
				) );
				$repeater->add_sub_field( 'checkbox', array(
					'name'  => 'apply_shipping',
					'label' => array( 'text' => __( 'Shipping', 'mp' ) ),
					//'default_value' => 1
				) );
			}

			$metabox->add_field( 'tab', array(
				'name'        => 'tab_content_' . $id,
				'slug'        => $table['slug'],
				'tab_content' => $repeater
			) );
		}

		return;
		//deprecated
		$metabox->add_field( 'text', array(
			'name'        => 'tax[rate]',
			'label'       => array( 'text' => __( 'Tax Rate', 'mp' ) ),
			'after_field' => '%',
			'style'       => 'width:75px',
			'validation'  => array(
				'number' => true,
			),
			'conditional' => array(
				'name'   => 'base_country',
				'value'  => 'CA',
				'action' => 'hide',
			),
		) );

		// Create field for each canadian province
		foreach ( mp()->canadian_provinces as $key => $label ) {
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
			'name'  => 'tax[label]',
			'label' => array( 'text' => __( 'Tax Label', 'mp' ) ),
			'style' => 'width:300px',
			'desc'  => __( 'The label shown for the tax line item in the cart. Taxes, VAT, GST, etc.', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_shipping]',
			'label'   => array( 'text' => __( 'Apply Tax To Shipping Fees?', 'mp' ) ),
			'desc'    => __( 'Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_inclusive]',
			'label'   => array( 'text' => __( 'Enter Prices Inclusive of Tax?', 'mp' ) ),
			'desc'    => __( 'Enabling this option allows you to enter and show all prices inclusive of tax, while still listing the tax total as a line item in shopping carts. Please see your local tax laws.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'tax[tax_digital]',
			'label'   => array( 'text' => __( 'Apply Tax to Downloadable Products?', 'mp' ) ),
			'desc'    => __( 'Please see your local tax laws. Note if this is enabled and a downloadable only cart, rates will be the default for your base location.', 'mp' ),
			'message' => __( 'Yes', 'mp' ),
		) );
	}

	public function print_tags_scripts( $after, $field ) {
		wp_enqueue_script( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.core.js' ), array( 'jquery' ), MP_VERSION );
		wp_enqueue_script( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.autocomplete.js' ), array( 'jquery' ), MP_VERSION );
		wp_enqueue_script( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.tags.js' ), array( 'jquery' ), MP_VERSION );

		wp_enqueue_style( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.core.css' ), array(), MP_VERSION );
		wp_enqueue_style( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.autocomplete.css' ), array(), MP_VERSION );
		wp_enqueue_style( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.tags.css' ), array(), MP_VERSION );

		$content = $field->get_value( 'mp_settings' );
		if ( empty( $content ) ) {
			$content = json_encode( array() );
		}

		$content = stripslashes( $content );

		?>
		<script type="text/javascript">
			jQuery(function ($) {
				$('textarea[name="tax[tax_tables]"]').html('').textext({
					plugins: 'tags autocomplete',
					tagsItems:<?php echo $content ?>
				});

				$('.tax_field').each(function () {
					if ($(this).val().length == 0) {
						$(this).val($(this).data('default-value'));
						if ($(this).hasClass('tax_rate')) {
							$(this).val(0);
						}
					}
				})
			})
		</script>
		<?php

		return $after;
	}
}

MP_Store_Settings_Taxes::get_instance();
