<?php

class MP_Store_Settings_Payments {
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
			self::$_instance = new MP_Store_Settings_Payments();
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
		add_action('init', array(&$this, 'add_metaboxes'));
	}
	
	/**
	 * Add payment gateway settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-payments',
			'page_slugs' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => __('Payment Gateways', 'mp'),
			'option_name' => 'mp_settings',
			'order' => 1,
		));
		
		$gateways = MP_Gateway_API::get_gateways(true);
		$options = array();
		
		foreach ( $gateways as $slug => $gateway ) {
			$options[$slug] = $gateway[1];
		}
		
		$metabox->add_field('checkbox_group', array(
			'name' => 'gateways[allowed]',
			'label' => array('text' => __('Enabled Gateways', 'mp')),
			'desc' => __('Choose the gateway(s) that you would like to be available for checkout.', 'mp'),
			'options' => $options,
			'width' => '50%',
		));
	}
}

MP_Store_Settings_Payments::get_instance();