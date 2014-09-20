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
	 * @param string $where The where clause for the SQL statement. IMPORTANT: You must make sure your SQL is escaped/safe!
	 * @return array
	 */
	public function get( $where = '' ) {
		global $wpdb;
		
		if ( ! empty($where) ) {
			$where = " WHERE $where";
		}
		
		return $wpdb->get_results('SELECT * FROM ' . $this->get_table_name() . $where);
	}
	
	/**
	 * Generates a product attribute's slug from it's ID.
	 *
	 * @since 3.0
	 * @access public
	 * @param int $id The attribute's ID.
	 * @return string
	 */
	public function generate_slug( $id ) {
		return 'product_attr_' . $id;
	}
	
	/**
	 * Deletes a product attribute(s)
	 *
	 * @since 3.0
	 * @uses $wpdb
	 * @access public
	 * @param array/int $id The ID of the attribute to delete.
	 */
	public function delete( $id = false ) {
		global $wpdb;
		
		if ( empty($id) ) {
			// Require at least one ID to prevent accidental deleting of all attributes 
			return false;
		}
		
		if ( is_array($id) ) {
			$wpdb->query('DELETE FROM . ' . $this->get_table_name() . ' WHERE attribute_id IN (' . implode(',', $id) . ')');
		} else {
			$wpdb->delete($this->get_table_name(), array('attribute_id' => $ids));
		}
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
		$product_atts = MP_Product_Attributes::get_instance();
		
		foreach ( $atts as $att ) {
			register_taxonomy($product_atts->generate_slug($att->attribute_id), 'mp_product', array(
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