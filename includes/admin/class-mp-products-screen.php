<?php

class MP_Products_Screen {
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
			self::$_instance = new MP_Products_Screen();
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
		add_action('admin_menu', array(&$this, 'remove_menu_items'), 999);
		//init metaboxes
		$this->init_metaboxes();		
	}
	
	/**
	 * Remove add-new submenu item from store admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function remove_menu_items() {
		remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
	}
	
	/**
	 * Initializes the coupon metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-variations-metabox',
			'title' => __('Variations'),
			'post_type' => 'product',
			'context' => 'normal',
		));
		$metabox->add_field('checkbox', array(
			'name' => 'has_variations',
			'message' => __('Does this product have variations?', 'mp'),
		));
	}
}

MP_Products_Screen::get_instance();