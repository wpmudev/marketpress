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
	 * Update the cart (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_cart, wp_ajax_nopriv_mp_update_cart
	 */
	public function ajax_update_cart() {
		$item = mp_get_post_value('product', null);
		$qty = mp_get_post_value('qty', 1);
		$item_id = null;
		
		if ( is_null($item) ) {
			wp_send_json_error();
		}
		
		if ( is_array($item) ) {
			if ( $product_id = mp_arr_get_value('product_id', $item) ) {
				unset($item['product_id']);
				$product = new MP_Product($product_id);
				if ( $variation = $product->get_variations_by_attributes($item, 0) ) {
					$item_id = $variation->ID;
				}
			}
		}
		
		if ( is_null($item_id) ) {
			wp_send_json_error();
		}
		
		switch ( mp_get_post_value('cart_action') ) {
			case 'add_item' :
				$this->add_item($item_id, $qty);
				wp_send_json_success($this->floating_cart_html());
			break;
		}
		
		wp_send_json_error();
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
	 
		if ( $cart_cookie = mp_get_cookie_value($this->_cookie_id) ) {
			$global_cart = unserialize($cart_cookie);
		}
		
		$this->_items = $global_cart;
	 
		if ( $global ) {
			return $this->get_all_items();
		} else {
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
	 * Get the cart total
	 *
	 * @since 3.0
	 * @access public
	 * @return float
	 */
	public function get_total() {
		$items = $this->get_items();
		$total = 0;
		
		foreach ( $items as $item => $qty ) {
			$product = new MP_Product($item);
			$price_obj = $product->get_price();

			if ( $product->on_sale() ) {
				$total += ($price_obj['sale']['amount'] * $qty);
			} else {
				$total += ($price_obj['regular'] * $qty);
			}
		}
		
		return $total;
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
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_enqueue_scripts
	 * @uses $post
	 */
	public function enqueue_styles_scripts() {
		global $post;
		
		if ( ! mp_is_shop_page() || mp_get_setting('pages->cart') == $post->ID ) {
			return;
		}
		
		// Styles
		wp_enqueue_style('mp-cart', mp_plugin_url('ui/css/mp-cart.css'), false, MP_VERSION);
		wp_enqueue_style('colorbox', mp_plugin_url('ui/css/colorbox.css'), false, MP_VERSION);
		
		// Scripts
		wp_enqueue_script('jquery-validate', mp_plugin_url('ui/js/jquery.validate.min.js'), array('jquery'), MP_VERSION, true);
		wp_enqueue_script('jquery-validate-methods', mp_plugin_url('ui/js/jquery.validate.methods.min.js'), array('jquery-validate'), MP_VERSION, true);
		wp_enqueue_script('ajaxq', mp_plugin_url('ui/js/ajaxq.min.js'), array('jquery'), MP_VERSION, true);
		wp_enqueue_script('colorbox', mp_plugin_url('ui/js/jquery.colorbox-min.js'), array('jquery'), MP_VERSION, true);
		wp_enqueue_script('mp-cart', mp_plugin_url('ui/js/mp-cart.js'), array('ajaxq', 'colorbox', 'jquery-validate'), MP_VERSION, true);
		
		// Localize scripts
		wp_localize_script('mp-cart', 'mp_cart_i18n', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
		));
	}
	
	/**
	 * Display the floating cart html
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_footer
	 */
	public function floating_cart_html() {
		$echo = true;
		if ( mp_doing_ajax() ) {
			$echo = false;
		}
		
		if ( (! mp_is_shop_page() || mp_get_setting('pages->cart') == get_the_ID()) && ! mp_doing_ajax() ) {
			return;
		}
		
		$items = $this->get_items();
		$html = '
		<div id="mp-floating-cart"' . (( $this->has_items() ) ? ' class="has-items"' : '') . '>
			<div id="mp-floating-cart-tab" class="clearfix"><span id="mp-floating-cart-total">' . mp_format_currency('', $this->get_total()) . '</span> ' . $this->item_count(false) . '</div>
			<div id="mp-floating-cart-contents">';
	
		if ( $this->has_items() ) {
			$html .= '
				<ul id="mp-floating-cart-items-list">';
		
			foreach ( $items as $item => $qty ) {
				$product = new MP_Product($item);
			
				$html .= '
					<li class="mp-floating-cart-item">
						<a class="mp-floating-cart-item-link" href="' . $product->url(false) . '">' . $product->image(false, 'floating-cart', 50) . '
							<div class="mp-floating-cart-item-content">
								<h3 class="mp-floating-cart-item-title">' . $product->title(false) . '</h3>
								<span class="mp-floating-cart-item-attribute"><strong>' . __('Quantity', 'mp') . ':</strong> <em>' . $qty . '</em></span>';
				
				// Display attributes
				if ( $product->is_variation() ) {
					$attributes = $product->get_attributes();
					foreach ( $attributes as $taxonomy => $att ) {
						$term = current($att['terms']);
						$html .= '
									<span class="mp-floating-cart-item-attribute"><strong>' . $att['name'] . ':</strong> <em>' . $term . '</em></span>';
					}
				}
				
				$html .= '
							</div>
						</a>
					</li>';
			}
			
			$html .= '
				</ul>
				<a id="mp-floating-cart-button" href="' . get_permalink(mp_get_setting('pages->cart')) . '">' . __('View Cart', 'mp') . '</a>';
		} else {
			$html .= '
				<div id="mp-floating-cart-no-items">
					<p><strong>' . __('Your shopping cart is empty.', 'mp') . '</strong></p>
					<p>' . __('As you add browse items and add them to your add cart they will show up here.', 'mp') . '</p>
				</div>';
		}
	
		$html .= '
			</div>
		</div>';
		
		if ( ! mp_doing_ajax() ) {
			$html .= '<span class="mp-ajax-loader" style="display:none"><img src="' . mp_plugin_url('ui/images/ajax-loader.gif') . '" alt="" /> ' . __('Adding...' , 'mp') . '</span>';
		}
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
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
	 * Display the item count
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function item_count( $echo = true ) {
		$items = $this->get_items();
		$numitems = count($items);
		
		if ( $numitems == 0 ) {
			$snippet = __('0 items', 'mp');
		} else {
			$snippet = sprintf(_n('1 item', '%s items', $numitems, 'mp'), $numitems);
		}
		
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
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
		$expire = strtotime('+1 month');
		if ( empty($this->_items) ) {
			if ( $cart_cookie = mp_get_cookie_value($this->_cookie_id) ) {
				$expire = strotime('-1 month');
			} else {
				return;
			}
		}
		
		setcookie($this->_cookie_id, serialize($this->_items), $expire, COOKIEPATH, COOKIE_DOMAIN);
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
		
		// Enqueue styles/scripts
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_styles_scripts'));
		
		// Display the floating cart html
		add_action('wp_footer', array(&$this, 'floating_cart_html'));
		
		// Ajax hooks
		add_action('wp_ajax_mp_update_cart', array(&$this, 'ajax_update_cart'));
		add_action('wp_ajax_nopriv_mp_update_cart', array(&$this, 'ajax_update_cart'));
	}
}

$GLOBALS['mp_cart'] = MP_Cart::get_instance();