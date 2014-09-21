<?php

add_action('wp_ajax_mp_update_states_dropdown', array('MP_Store_Settings_General', 'ajax_mp_update_states_dropdown'));
add_action('wp_ajax_mp_update_currency', array('MP_Store_Settings_General', 'ajax_mp_update_currency'));

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
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Store_Settings_General();
		}
		return self::$_instance;
	}
	
	/**
	 * Gets an updated list of states based upon the given country
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_states_dropdown
	 */
	public static function ajax_mp_update_states_dropdown() {
		if ( check_ajax_referer('mp_update_states_dropdown', 'nonce', false ) ) {
			$states = mp_get_states(mp_get_get_value('base_country'));
			wp_send_json_success($states);
		}
		
		wp_send_json_error();
	}

	/**
	 * Gets an updated currency symbol based upon a given currency code
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_currency
	 */
	public static function ajax_mp_update_currency() {
		if ( check_ajax_referer('mp_update_currency', 'nonce', false ) ) {
			$currency = mp_format_currency(mp_get_get_value('currency'));
			wp_send_json_success($currency);
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
		add_action('wpmudev_field/print_scripts/base_country', array(&$this, 'update_states_dropdown'));
		add_action('wpmudev_field/print_scripts/currency', array(&$this, 'update_currency_symbol'));
		add_filter('wpmudev_field/get_value/tax[rate]', array(&$this, 'get_tax_rate_value'), 10, 4);
		add_filter('wpmudev_field/sanitize_for_db/tax[rate]', array(&$this, 'save_tax_rate_value'), 10, 3);
		
		$this->init_location_settings();
		$this->init_tax_settings();
		$this->init_currency_settings();
		$this->init_misc_settings();
	}
	
	/**
	 * Formats the tax rate value from decimal to percentage
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/get_value/tax[rate]
	 * @return string
	 */
	public function get_tax_rate_value( $value, $post_id, $raw, $field ) {
		return ($value * 100);
	}
	
	/**
	 * Formats the tax rate value from percentage to decimal prior to saving to db
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/sanitize_for_db/tax[rate]
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
jQuery(document).ready(function($){
	var $currency = $('select[name="currency"]');
	
	$currency.on('change', function(e){
		var data = [
			{
				"name" : "currency",
				"value" : e.val
			},{
				"name" : "action",
				"value" : "mp_update_currency"
			},{
				"name" : "nonce",
				"value" : "<?php echo wp_create_nonce('mp_update_currency'); ?>"
			}
		];
		
		$currency.select2('enable', false).isWorking(true);

		$.get(ajaxurl, $.param(data)).done(function(resp){
			$currency.select2('enable', true).isWorking(false);
			
			if ( resp.success ) {
				$('.mp-currency-symbol').html(resp.data);
			}
		});
	});
});
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
jQuery(document).ready(function($){
	var $country = $('select[name="base_country"]'),
			$state = $('select[name="base_province"]');
			
	$country.on('change', function(e){
		var data = [
			{
				"name" : "base_country",
				"value" : e.val
			},{
				"name" : "action",
				"value" : "mp_update_states_dropdown"
			},{
				"name" : "nonce",
				"value" : "<?php echo wp_create_nonce('mp_update_states_dropdown'); ?>"
			}
		];
		
		$country.select2('enable', false).isWorking(true);
		$state.select2('enable', false);
		
		$.get(ajaxurl, $.param(data)).done(function(resp){
			$country.select2('enable', true).isWorking(false);
			$state.select2('enable', true);
			
			if ( resp.success ) {
				$state.empty();
				
				$.each(resp.data, function(val, text){
					var $option = $('<option></option>').attr('value', val).text(text);
					$state.append($option)
				});
				
				$state.trigger('change');
			}
		});
	});
});
</script>
		<?php
	}

	/**
	 * Init misc settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_misc_settings() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-general-misc',
			'screen_ids' => array('store-settings', 'toplevel_page_store-settings'),
			'title' => __('Miscellaneous Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'inventory_threshhold',
			'label' => array('text' => __('Inventory Warning Threshold', 'mp')),
			'desc' => __('At what low stock count do you want to be warned for products you have enabled inventory tracking for?', 'mp'),
			'style' => 'width:50px;',
			'validation' => array(
				'digits' => true,
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'inventory_remove',
			'label' => array('text' => __('Hide Out of Stock Products?', 'mp')),
			'desc' => __('This will set the product to draft if inventory of all variations is gone.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('text', array(
			'name' => 'max_downloads',
			'label' => array('text' => __('Maximum Downloads', 'mp')),
			'desc' => __('How many times may a customer download a file they have purchased? (It\'s best to set this higher than one in case they have any problems downloading)', 'mp'),
			'style' => 'width:50px;',
			'validation' => array(
				'digits' => true,
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'download_order_limit',
			'label' => array('text' => __('Limit Digital Products Per-order?', 'mp')),
			'desc' => __('This will prevent multiples of the same downloadable product form being added to the cart. Per-product custom limits will override this.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'force_login',
			'label' => array('text' => __('Force Login?', 'mp')),
			'desc' => __('Whether or not customers must be registered and logged in to checkout. (Not recommended: Enabling this can lower conversions)', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'disable_cart',
			'label' => array('text' => __('Disable Cart?', 'mp')),
			'desc' => __('This option turns MarketPress into more of a product listing plugin, disabling shopping carts, checkout, and order management. This is useful if you simply want to list items you can buy in a store somewhere else, optionally linking the "Buy Now" buttons to an external site. Some examples are a car dealership, or linking to songs/albums in itunes, or linking to products on another site with your own affiliate links.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'ga_ecommerce',
			'label' => array('text' => __('Google Analytics Ecommerce Tracking', 'mp')),
			'desc' => __('If you already use Google Analytics for your website, you can track detailed ecommerce information by enabling this setting. Choose whether you are using the new asynchronous or old tracking code. Before Google Analytics can report ecommerce activity for your website, you must enable ecommerce tracking on the profile settings page for your website. Also keep in mind that some gateways do not reliably show the receipt page, so tracking may not be accurate in those cases. It is recommended to use the PayPal gateway for the most accurate data. <a target="_blank" href="http://analytics.blogspot.com/2009/05/how-to-use-ecommerce-tracking-in-google.html">More information &raquo;</a>', 'mp'),
			'default_value' => 'none',
			'orientation' => 'horizontal',
			'options' => array(
				'none' => __('None', 'mp'),
				'new' => __('New', 'mp'),
				'old' => __('Old', 'mp'),
				'universal' => __('Universal', 'mp'),
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'special_instructions',
			'label' => array('text' => __('Show Special Instructions Field?', 'mp')),
			'desc' => sprintf(__('Enabling this field will display a textbox on the shipping checkout page for users to enter special instructions for their order. Useful for product personalization, etc. Note you may want to <a href="%s">adjust the message</a> on the shipping page.', 'mp'), admin_url('admin.php?page=store-settings-messaging')),
			'message' => __('Yes', 'mp'),
		));
	}

	/**
	 * Init currency settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_currency_settings() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-general-currency',
			'screen_ids' => array('store-settings', 'toplevel_page_store-settings'),
			'title' => __('Currency Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		
		$currencies = apply_filters('mp_currencies', mp()->currencies);
		$options = array('' => __('Select a Currency', 'mp'));
		
		foreach ( $currencies as $key => $value ) {
			$options[$key] = esc_attr($value[0]) . ' - ' . mp_format_currency($key);
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => 'currency',
			'placeholder' => __('Select a Currency', 'mp'),
			'multiple' => false,
			'label' => array('text' => __('Store Currency', 'mp')),
			'options' => $options,
			'width' => 'element',
		));
		$metabox->add_field('radio_group', array(
			'name' => 'curr_symbol_position',
			'label' => array('text' => __('Currency Symbol Position', 'mp')),
			'default_value' => '1',
			'orientation' => 'horizontal',
			'options' => array(
				'1' => '<span class="mp-currency-symbol">' . mp_format_currency(mp_get_setting('currency', 'USD')) . '</span>100',
				'2' => '<span class="mp-currency-symbol">' . mp_format_currency(mp_get_setting('currency', 'USD')) . '</span> 100', 
				'3' => '100<span class="mp-currency-symbol">' . mp_format_currency(mp_get_setting('currency', 'USD')) . '</span>',
				'4' => '100 <span class="mp-currency-symbol">' . mp_format_currency(mp_get_setting('currency', 'USD')) . '</span>',
			),
		));
	}

	/**
	 * Init tax settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_tax_settings() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-general-tax',
			'screen_ids' => array('store-settings', 'toplevel_page_store-settings'),
			'title' => __('Tax Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'tax[rate]',
			'label' => array('text' => __('Tax Rate', 'mp')),
			'after_field' => '%',
			'style' => 'width:75px',
			'validation' => array(
				'number' => true,
			),
		));
		$metabox->add_field('text', array(
			'name' => 'tax[label]',
			'label' => array('text' => __('Tax Label', 'mp')),
			'style' => 'width:300px',
			'desc' => __('The label shown for the tax line item in the cart. Taxes, VAT, GST, etc.', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'tax[tax_shipping]',
			'label' => array('text' => __('Apply Tax To Shipping Fees?', 'mp')),
			'desc' => __('Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'tax[tax_inclusive]',
			'label' => array('text' => __('Enter Prices Inclusive of Tax?', 'mp')),
			'desc' => __('Enabling this option allows you to enter and show all prices inclusive of tax, while still listing the tax total as a line item in shopping carts. Please see your local tax laws.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'tax[tax_digital]',
			'label' => array('text' => __('Apply Tax to Downloadable Products?', 'mp')),
			'desc' => __('Please see your local tax laws. Note if this is enabled and a downloadable only cart, rates will be the default for your base location.', 'mp'),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'tax[downloadable_address]',
			'label' => array('text' => __('Collect Address on Downloadable Only Cart?', 'mp')),
			'desc' => __('If you need to tax downloadable products and don\'t want to default to the rates to your base location, enable this to always collect the shipping address. ', 'mp'),
			'message' => __('Yes', 'mp'),
		));
	}
		
	/**
	 * Init location settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_location_settings() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-general-location',
			'screen_ids' => array('store-settings', 'toplevel_page_store-settings'),
			'title' => __('Location Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('advanced_select', array(
			'name' => 'base_country',
			'placeholder' => __('Select a Country', 'mp'),
			'multiple' => false,
			'label' => array('text' => __('Base Country', 'mp')),
			'options' => array('' => __('Select A Country')) + mp()->countries,
			'width' => 'element',
		));
		
		$states = mp_get_states(mp_get_setting('base_country'));
		$metabox->add_field('advanced_select', array(
			'name' => 'base_province',
			'placeholder' => __('Select a State/Province/Region', 'mp'),
			'multiple' => false,
			'label' => array('text' => __('Base State/Province/Region', 'mp')),
			'options' => $states,
			'width' => 'element',
			'conditional' => array(
				'name' => 'base_country',
				'value' => array('US','CA','GB','AU'),
				'action' => 'show',
			),
		));
		$metabox->add_field('text', array(
			'name' => 'base_zip',
			'label' => array('text' => __('Base Zip/Postal Code', 'mp')),
			'style' => 'width:150px;',
			'custom' => array(
				'minlength' => 3,
			),
			'conditional' => array(
				'name' => 'base_country',
				'value' => array('US','CA','GB','AU', 'UM','AS','FM','GU','MH','MP','PW','PR','PI'),
				'action' => 'show',
			),
		));
	}
}

MP_Store_Settings_General::get_instance();