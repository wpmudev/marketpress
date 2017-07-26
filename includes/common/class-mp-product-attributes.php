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
	 * Refers to the current product attributes
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_atts = null;
	
	/**
	 * Refers to the base slug for all product attributes
	 *
	 * @since 3.0
	 * @access public
	 * @var string
	 */
	const SLUGBASE = 'product_attr_';
	
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
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	
	private function __construct() {
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
		
		// Force update of cache
		$this->_atts = null;
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
		
		if ( ! is_null($this->_atts) ) {
			return $this->_atts;
		}
		
		if ( ! empty($where) ) {
			$where = " WHERE $where";
		}

		$this->_atts = $wpdb->get_results('SELECT * FROM ' . $this->get_table_name() . $where);
		return $this->_atts;
	}
	
	/**
	 * Get an attribute ID from it's slug
	 *
	 * @since 3.0
	 * @access public
	 * @param string $slug
	 * @return int
	 */
	public function get_id_from_slug( $slug ) {
		return (int) str_replace(self::SLUGBASE, '', $slug);
	}
	
	/**
	 * Get product categories associated with a given attribute
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 * @param int $att_id An attribute ID.
	 * @return array
	 */
	public function get_associated_categories( $att_id ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mp_product_attributes_terms';
		$cache_key = 'associated_categories_' . $att_id;
		$results = wp_cache_get( $cache_key, 'mp_product_attributes' );
		
		if ( false === $results ) {
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT term_id
				FROM $table_name
				WHERE attribute_id = %d", $att_id
			) );
			wp_cache_set( $cache_key, $results, 'mp_product_attributes' );
		}
		
		return wp_list_pluck( $results, 'term_id' );
	}
	
	/**
	 * Get a single product attribute
	 *
	 * @since 3.0
	 * @access public
	 * @param int $id The ID of product attribute to fetch.
	 * @return object The product attribute or FALSE if a product attribute is not found for the given ID.
	 */
	public function get_one( $id ) {
		$atts = $this->get();
		foreach ( $atts as $att ) {
			if ( $att->attribute_id == $id ) {
				return $att;
			}
		}
		
		return false;
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
		return self::SLUGBASE . $id;
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
			register_taxonomy($product_atts->generate_slug($att->attribute_id), MP_Product::get_post_type(), array(
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'hierarchical' => true,
			));
		}
	}
	
	/**
	 * Sort attributes per settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $attributes
	 * @return array
	 */
	public function sort( $attributes, $grouping = true ) {
		$groups = array();
		
		// Fix wrong taxonomy bug
		if ( is_wp_error( $attributes ) ){
			return array();
		}
		// Put attributes into groups by taxonomy
		foreach ( $attributes as $attribute ) {
			$groups[$attribute->taxonomy][] = $attribute;
		}
		
		// Sort terms
		foreach ( $groups as $tax_slug => &$group ) {
			$tax_id = $this->get_id_from_slug($tax_slug);
			$tax = $this->get_one($tax_id);
			
			switch ( $tax->attribute_terms_sort_by ) {
				case 'ID' :
					if ( $tax->attribute_terms_sort_order == 'ASC' ) {
						usort($group, create_function('$a, $b', 'return ( $a->term_id == $b->term_id ) ? 0 : ( $a->term_id < $b->term_id ) ? -1 : 1;'));
					} else {
						usort($group, create_function('$a, $b', 'return ( $a->term_id == $b->term_id ) ? 0 : ( $a->term_id < $b->term_id ) ? 1 : -1;'));
					}
				break;
				
				case 'ALPHA' :
					if ( $tax->attribute_terms_sort_order == 'ASC' ) {
						usort($group, create_function('$a, $b', 'return ( $a->name == $b->name ) ? 0 : ( $a->name < $b->name ) ? -1 : 1;'));
					} else {
						usort($group, create_function('$a, $b', 'return ( $a->name == $b->name ) ? 0 : ( $a->name < $b->name ) ? 1 : -1;'));
					}
				break;
				
				case 'CUSTOM' :
					$this->sort_terms_by_custom_order($group);
				break;
			}
		}
		
		return ( $grouping ) ? $groups : array_shift($groups);
	}
	
	/**
	 * Sort terms by custom order
	 *
	 * @since 3.0
	 * @access public
	 * @param array $terms
	 */
	public function sort_terms_by_custom_order( &$terms ) {
		usort($terms, create_function('$a, $b', 'return ( $a->term_order == $b->term_order ) ? 0 : ( $a->term_order < $b->term_order ) ? -1 : 1;'));
	}
}

MP_Product_Attributes::get_instance();