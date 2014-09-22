<?php

class MP_Store_Settings_Notifications {
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
			self::$_instance = new MP_Store_Settings_Notifications();
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
		add_action('init', array(&$this, 'init_metaboxes'));
	}
	
	/**
	 * Init metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-notifications',
			'screen_ids' => array('store-settings-notifications', 'store-settings_page_store-settings-notifications'),
			'title' => __('Notification Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'store_email',
			'label' => array('text' => __('Notification Email', 'mp')),
			'validation' => array(
				'email' => 1,
			),
		));
		$new_order = $metabox->add_field('complex', array(
			'name' => 'email[new_order]',
			'label' => array('text' => __('New Order', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $new_order instanceof WPMUDEV_Field ) {
			$new_order->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
			));
			$new_order->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
			));
		}
		
		$order_shipped = $metabox->add_field('complex', array(
			'name' => 'email[order_shipped]',
			'label' => array('text' => __('Order Shipped', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $order_shipped instanceof WPMUDEV_Field ) {
			$order_shipped->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
			));
			$order_shipped->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
			));
		}		
	}
}

MP_Store_Settings_Notifications::get_instance();