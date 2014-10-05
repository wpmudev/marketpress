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
	 * Refers to the cart cookie id
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected $_cookie_id = null;
	
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
		if ( $in_cart = $this->has_item($item_id) ) {
			$qty += $in_cart;
		}
		
		mp_push_to_array($this->_items, $this->_id . '->' . $item_id, $qty);
		$this->_update_cart_cookie();
	}
	
	/**
	 * Add an item to the cart (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_add_to_cart, wp_ajax_nopriv_mp_add_to_cart
	 */
	public function ajax_add_item() {
		$item_id = mp_get_post_value('product_id', null);
		$qty = mp_get_post_value('qty', 1);
		
		if ( is_null($item_id) ) {
			wp_send_json_error();
		}
		
		$this->add_item($item_id, $qty);
		
		wp_send_json_success();
	}
	
	/**
	 * Get cart cookie
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _get_cart_cookie( $global = false ) {
		$this->_cookie_id = 'mp_globalcart_' . COOKIEHASH;
		$global_cart = array($this->_id => array());
	 
		if ( $cart_cookie = mp_get_cookie_val($this->cookie_id) ) {
			$global_cart = unserialize($cart);
		}
	 
		$this->_items = $global_cart;
	 
		if ( $global ) {
			return $this->_items;
		} else {
	 		$this->set_id($blog_id);
	 		return $this->get_items();
		}
	}

	/**
	 * Get all cart items across all blogs
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_all_items() {
		return $this->_items;
	}
	
	/**
	 * Get a single item from the cart
	 *
	 * @since 3.0
	 * @access public
	 * @param 
	 */
	public function get_item( $item_id ) {
		
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
		$this->_update_cart_cookie();
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
	 * Update the cart cookie
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _update_cart_cookie() {
		setcookie($this->_cookie_id, serialize($this->_items), strtotime('+1 month'), COOKIEPATH, COOKIE_DOMAIN);
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		$this->set_id(get_current_blog_id());
		$this->_get_cart_cookie();
		
		// Ajax hooks
		add_action('wp_ajax_mp_add_to_cart', array(&$this, 'add_item'));
		add_action('wp_ajax_nopriv_mp_add_to_cart', array(&$this, 'add_item'));
	}
}

$GLOBALS['mp_cart'] = MP_Cart::get_instance();