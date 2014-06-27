<?php

class MP_Product_Attributes {
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
			self::$_instance = new MP_Product_Attributes();
		}
		return self::$_instance;
	}

	/**
	 * Gets all product attributes
	 *
	 * @since 3.0
	 * @uses $wpdb
	 * @param string $where The where clause for the SQL statement
	 * @return array
	 */
	public function get( $where = '' ) {
		global $wpdb;
		$table = $this->get_table_name();
		return $wpdb->get_results("SELECT * FROM $table $where");
	}
	
	/**
	 * Gets the product attribute table name
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mp_product_attributes';
	}
	
	/**
	 * Registers the product attribute taxonomies
	 *
	 * @since 3.0
	 * @access public
	 */
	public function register() {
		$atts = $this->get();
		foreach ( $atts as $att ) {
			register_taxonomy($att->attribute_slug, 'product', array(
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'hierarchical' => true,
			));
		}
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	
	private function __construct() {
	}
}

MP_Product_Attributes::get_instance();