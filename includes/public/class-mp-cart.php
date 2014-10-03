<?php

class MP_Cart {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Refers to the cart's items
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_items = array();
	
	/**
	 * Refers to the current cart ID
	 *
	 * @since 3.0
	 * @access protected
	 * @var int
	 */
	protected $_id = null;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Cart();
		}
		return self::$_instance;
	}
	
	/**
	 * Add an item to the cart
	 *
	 * @since 3.0
	 * @access public
	 * @param int $item_id The id of the item to add
	 * @param int $qty The quantity of the item
	 */
	public function add_item( $item_id, $qty = 1 ) {
		mp_push_to_array($this->_items, $this->_id . '->' . $item_id, $qty);
	}
	
	/**
	 * Get cart items
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_items() {
		return mp_arr_get_value($this->_id, $this->_items, array());
	}
	
	/**
	 * Empty cart
	 *
	 * @since 3.0
	 * @access public
	 */
	public function empty_cart() {
		/**
		 * Fires right before the cart is emptied
		 *
		 * @since 3.0
		 * @param int The cart id
		 * @param array The items in the cart before being emptied
		 */
		do_action('mp_cart/empty', $this->_id, $this->get_items());
		
		$this->_items[$this->_id] = array();
	}
	
	/**
	 * Check if cart has a specific item
	 *
	 * @since 3.0
	 * @access public
	 * @param $item_id The item ID
	 * @return int How many of the item are in the cart
	 */
	public function has_item( $item_id ) {
		return mp_arr_get_value($this->_id . '->' . $item_id, $this->_items, 0);
	}
	
	/**
	 * Check if cart has items
	 *
	 * @since 3.0
	 * @access public
	 */
	public function has_items() {
		$items = $this->get_items();
		return ( count($items) > 0 );
	}
	
	/**
	 * Set the cart id
	 *
	 * @since 3.0
	 * @access public
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->_id = $id;
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		$this->set_id(get_current_blog_id());
	}
}

$GLOBALS['mp_cart'] = MP_Cart::get_instance();