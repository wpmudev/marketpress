<?php

class MP_Pages_Admin {
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
			self::$_instance = new MP_Pages_Admin();
		}
		return self::$_instance;
	}
	
	/**
	 * Init edit-page metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_page_settings_metabox();
	}
	
	/**
	 * Init the
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_page_settings_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-store-pages-metabox',
			'post_type' => 'page',
			'title' => __('Store Page Settings', 'mp'),
			'context' => 'side',
		));
		
		$options = array(
			'none' => __('None', 'mp'),
			'store' => __('Root Store Page', 'mp'),
			'checkout' => __('Checkout Page', 'mp'),
		);
		
		if ( is_multisite() && is_main_site() && is_super_admin() ) {
			$options['network_store_page'] = __('Network Store Page', 'mp');
		}
		
		$metabox->add_field('radio_group', array(
			'name' => 'store_page',
			'desc' => __('You can choose to make this page one of the following core store pages', 'mp'),
			'orientation' => 'vertical',
			'default_value' => 'none',
			'options' => $options,
		));
	}
	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('init', array(&$this, 'init_metaboxes'));
	}
}

MP_Pages_Admin::get_instance();