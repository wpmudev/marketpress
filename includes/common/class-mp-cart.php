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
	 * Refers to the cart's items that are in the user's cart cookie, but are no longer available
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected $_items_unavailable = array( 'deleted' => array(), 'stock_issue' => array() );

	/**
	 * Refers to the current cart ID
	 *
	 * @since 3.0
	 * @access protected
	 * @var int
	 */
	protected $_id = null;

	/**
	 * Refers to the original cart ID
	 *
	 * @since 3.0
	 * @access protected
	 * @var int
	 */
	protected $_id_original = null;

	/**
	 * Refers to the cart cookie id
	 *
	 * @since 3.0
	 * @access protected
	 * @var string
	 */
	protected $_cookie_id = null;

	/**
	 * Refers to if the cart is download only
	 *
	 * @since 3.0
	 * @access protected
	 * @var bool
	 */
	protected $_is_download_only = null;

	/**
	 * Refers to the cart total
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_total = array();

	/**
	 * Refers to whether or not the cart is using cookies or not
	 *
	 * @since 3.0
	 * @access protected
	 * @var bool
	 */
	protected $_use_cookies = null;

	/**
	 * Refers to whether or not we're using global cart
	 *
	 * @since 3.0
	 * @access public
	 * @var bool
	 */
	public $is_global = false;

	/**
	 * Refers to if the current cart is editable
	 *
	 * @since 3.0
	 * @access public
	 * @type bool
	 */
	public $is_editable = true;

	/*	 * Refers to the number of times products have been downloaded
	 * 
	 *
	 * @since 3.0
	 * @access public
	 * @type array
	 */
	public $download_count = array();

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
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
		if ( $in_cart = $this->has_item( $item_id ) ) {
			$qty += $in_cart;
		}

		mp_push_to_array( $this->_items, $this->_id . '->' . $item_id, $qty );
		$this->_update_cart_cookie();
	}

	/**
	 * Enqueue admin styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_enqueue_scripts
	 */
	public function admin_enqueue_styles_scripts( $hook ) {
		if ( 'mp_order' == get_current_screen()->post_type && ('post.php' == $hook || 'edit.php' == $hook) ) {
			wp_enqueue_style( 'mp-frontend', mp_plugin_url( 'ui/css/frontend.css' ), false, MP_VERSION );
			wp_enqueue_style( 'mp-theme', mp_plugin_url( 'ui/themes/' . mp_get_setting( 'store_theme' ) . '.css' ), array( 'mp-frontend' ), MP_VERSION );
		}
	}

	/**
	 * Get cart price (takes into account tax rules)
	 *
	 * @since 3.0
	 * @access public
	 * @param float $price The individual product price.
	 * @param int $qty The quantity of the product.
	 * @return float
	 */
	public function cart_price( $price, $qty ) {
		$cart_price = ($price * $qty);

		if ( mp_get_setting( 'tax->tax_inclusive' ) ) {
			$tax_rate	 = mp_tax_rate();
			$cart_price	 = $cart_price / (1 + $tax_rate);
		}

		return $cart_price;
	}

	/**
	 * Get the cart's URL
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function cart_url() {
		$switched = false;
		if ( $this->is_global && mp_root_blog_id() != get_current_blog_id() ) {
			$switched = true;
		}

		if ( $switched ) {
			$switched = true;
			switch_to_blog( mp_root_blog_id() );
		}

		$url = mp_store_page_url( 'cart', false );

		if ( $switched ) {
			restore_current_blog();
		}

		return $url;
	}

	/**
	 * Update the cart (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_cart, wp_ajax_nopriv_mp_update_cart
	 */
	public function ajax_update_cart() {
		$item	 = $item_id = mp_get_post_value( 'product', null );
		$qty	 = mp_get_post_value( 'qty', 1 );

		if ( is_null( $item ) ) {
			wp_send_json_error();
		}

		if ( is_array( $item ) ) {
			if ( $product_id = mp_arr_get_value( 'product_id', $item ) ) {
				unset( $item[ 'product_id' ] );
				$product	 = new MP_Product( $product_id );
				if ( $variation	 = $product->get_variations_by_attributes( $item, 0 ) ) {
					$item_id = $variation->ID;
				}
			}
		}
		
		if ( is_null( $item_id ) ) {
			wp_send_json_error();
		}

		switch ( mp_get_post_value( 'cart_action' ) ) {
			case 'add_item' :
				$this->add_item( $item_id, $qty );
				wp_send_json_success( $this->floating_cart_html() );
				break;

			case 'update_item' :
				$this->update_item( $item_id, $qty );
				$product		 = new MP_Product( $item_id );
				$product->qty	 = $qty;
				wp_send_json_success( array(
					'product'	 => array( $item_id => $this->get_line_item( $product ) ),
					'cartmeta'	 => $this->cart_meta( false ),
				) );
				break;

			case 'remove_item' :
				$this->remove_item( $item_id );
				wp_send_json_success( array(
					'cartmeta'	 => $this->cart_meta( false ),
					'item_count' => $this->item_count( false, false ),
				) );
				break;
		}

		wp_send_json_error();
	}

	/**
	 * Convert an array of items to an array of MP_Product objects
	 *
	 * @since 3.0
	 * @access public
	 * @param array $items
	 * @return array
	 */
	protected function _convert_to_objects( $items ) {
		$cache_key	 = implode( ',', $items );
		$products	 = array();

		if ( $_posts = wp_cache_get( $cache_key, 'mp_cart' ) ) {
			$posts = $_posts;
		} else {
			$posts = get_posts( array(
				'post__in'		 => array_keys( $items ),
				'posts_per_page' => -1,
				'post_type'		 => array( MP_Product::get_post_type(), 'mp_product_variation' ),
				'post_status'	 => array( 'publish', 'out_of_stock', 'trash' ),
				'orderby'		 => 'post__in'
			) );
			wp_cache_set( $cache_key, $posts, 'mp_cart' );
		}

		foreach ( $posts as $post ) {
			$product		 = new MP_Product( $post );
			$product->qty	 = (float) array_shift( $items );
			$products[]		 = $product;
		}

		return $products;
	}

	/**
	 * Get cart cookie
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _get_cart_cookie() {
		if ( !$this->_use_cookies ) {
			// Not using cookies - bail
			return false;
		}

		$this->_cookie_id	 = 'mp_globalcart_' . COOKIEHASH;
		$this->_items = array( $this->_id => array() );

		if ( $cart_cookie = mp_get_cookie_value( $this->_cookie_id ) ) {
			$this->_items = unserialize( $cart_cookie );
		}
	}
	
	/**
	 * Check if a given item ID is a global item ID
	 *
	 * @since 3.0
	 * @access protected
	 * @param string/int $item_id
	 * @return bool
	 */
	protected function _is_global_item_id( $item_id ) {
		return ( $this->is_global && false !== strpos( $item_id, '.' ) );
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
	 * Get the ids of the blogs that contain items in the user's cart
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_blog_ids() {
		$carts = $this->get_all_items();
		
		foreach ( $carts as $cart_id => $items ) {
			if ( empty( $items ) ) {
				unset( $carts[ $cart_id ] );
			}
		}
		
		return array_keys( $carts );
	}

	/**
	 * Get the current blog id of the cart
	 *
	 * @since 3.0
	 * @access public
	 * @return int
	 */
	public function get_blog_id() {
		return $this->_id;
	}

	/**
	 * Get a single item quantity from the cart
	 *
	 * @since 3.0
	 * @access public
	 * @param int $item_id
	 * @return int The quantity in the cart. False if item doesn't exist in cart.
	 */
	public function get_item_qty( $item_id ) {
		if ( $qty = mp_arr_get_value( $this->_id . '->' . $item_id, $this->_items ) ) {
			return (int) $qty;
		}

		return false;
	}

	/**
	 * Get cart items
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_items() {
		$items = mp_arr_get_value( $this->_id, $this->_items, array() );
		return (array) $items;
	}

	/**
	 * Gets cart items as objects
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_items_as_objects() {
		$items = $this->get_items();
		return $this->_convert_to_objects( $items );
	}

	/**
	 * Get cart line item html
	 *
	 * @since 3.0
	 * @access public
	 * @param MP_Product/int $product The product to get the html for.
	 * @return string
	 */
	public function get_line_item( $product ) {
		if ( ! $product instanceof MP_Product ) {
			$product = new MP_Product( $product );
		}
		
		$id = ( $this->is_global ) ? $product->global_id() : $product->ID;

		/**
		 * Filter cart columns array
		 *
		 * @since 3.0
		 * @param array The cart columns array.
		 */
		$cart_columns = (array) apply_filters( 'mp_cart/cart_columns_array', array(
			'thumb',
			'title',
			'price',
			'qty',
		) );

		$html = '
			<div class="mp-cart-item clearfix" id="mp-cart-item-' . $id . '">';

		foreach ( $cart_columns as $column ) {
			$html .= '
				<div class="mp-cart-item-column mp-cart-item-column-' . $column . '">';

			switch ( $column ) {
				case 'thumb' :
					$column_html = $product->image_custom( false, 75 );
					break;

				case 'title' :
					$column_html = '<h2>' . $product->title( false ) . '</h2>';
					if ( !$this->is_editable && $product->is_download() && mp_is_shop_page( 'order_status' ) ) {
						$column_html .= '<a target="_blank" href="' . $product->download_url( get_query_var( 'mp_order_id' ), false ) . '">' . __( 'Download', 'mp' ) . '</a>';
					}
					break;

				case 'price' :
					$column_html = $product->display_price( false );
					break;

				case 'qty' :
					if ( $this->is_editable ) {
						$column_html = $this->dropdown_quantity( array(
							'echo'		 => false,
							'class'		 => 'mp_select2',
							'name'		 => 'mp_cart_qty[' . $product->ID . ']',
							'selected'	 => $product->qty,
						) ) . '<br />
						<a class="mp-cart-item-remove-link" href="javascript:mp_cart.removeItem(' . $id . ')">' . __( 'Remove', 'mp' ) . '</a>';
					} else {
						$column_html = $product->qty;
					}
					break;
			}

			/**
			 * Filter the column html
			 *
			 * @since 3.0
			 * @param string The current column html.
			 * @param string The current column slug.
			 * @param MP_Product The current product.
			 * @param MP_Cart The current cart object.
			 */
			$html .= apply_filters( 'mp_cart/column_html', $column_html, $column, $product, $this );

			$html .= '
				</div>';
		}

		$html .= '</div>';

		/**
		 * Filter the line item html
		 *
		 * @since 3.0
		 * @param string $html The current line html.
		 * @param MP_Product $product The current product object.
		 * @param MP_Cart $this The current cart object.
		 */
		return apply_filters( 'mp_cart/get_line_item', $html, $product, $this );
	}

	/**
	 * Display cart meta html
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to true.
	 */
	public function cart_meta( $echo = true ) {
		$html = '';

		if ( $this->is_editable ) {
			$zipcode = mp_get_current_user_zipcode();

			if ( empty( $zipcode ) ) {
				$header = __( 'Estimated Total', 'mp' );
			} else {
				$header = sprintf( __( 'Estimated Total for %s', 'mp' ), $zipcode );
			}

			/**
			 * Filter the header text
			 *
			 * @since 3.0
			 * @param string The current header text.
			 * @param MP_Cart The current cart object.
			 */
			$header = apply_filters( 'mp_cart/cart_meta/header', $header, $this );
		}

		$line = '
			<div id="mp-cart-meta">';

		if ( !empty( $header ) ) {
			$line .= '
				<div class="mp-cart-meta-header">' . $header . '</div>';
		}

		$line .= '
				<div id="mp-cart-meta-line-product-total" class="mp-cart-meta-line clearfix">
					<strong class="mp-cart-meta-line-label">' . __( 'Product Total', 'mp' ) . '</strong>
					<span class="mp-cart-meta-line-amount">' . $this->product_total( true, true ) . '</span>
				</div>';

		/**
		 * Filter the product total html
		 *
		 * @since 3.0
		 * @param string The current product total html.
		 * @param MP_Cart The current cart object.
		 */
		$html .= apply_filters( 'mp_cart/cart_meta/product_total', $line, $this );

		$line = '
				<div id="mp-cart-meta-line-shipping-total" class="mp-cart-meta-line clearfix">
					<strong class="mp-cart-meta-line-label">' . (( $this->is_editable ) ? __( 'Estimated Shipping', 'mp' ) : __( 'Shipping' )) . '</strong>
					<span class="mp-cart-meta-line-amount">' . $this->shipping_total( true ) . '</span>
				</div>';

		/**
		 * Filter the shipping total html
		 *
		 * @since 3.0
		 * @param string The current shipping total html.
		 * @param MP_Cart The current cart object.
		 */
		$html .= apply_filters( 'mp_cart/cart_meta/shipping_total', $line, $this );

		$line = '
				<div id="mp-cart-meta-line-estimated-tax" class="mp-cart-meta-line clearfix">
					<strong class="mp-cart-meta-line-label">' . (( $this->is_editable ) ? sprintf( __( 'Estimated %s', 'mp' ), mp_get_setting( 'tax->label' ) ) : mp_get_setting( 'tax->label' )) . '</strong>
					<span class="mp-cart-meta-line-amount">' . $this->tax_total( true, true ) . '</span>
				</div>';

		/**
		 * Filter the estimated tax html
		 *
		 * @since 3.0
		 * @param string The current estimated tax html.
		 * @param MP_Cart The current cart object.
		 */
		$html .= apply_filters( 'mp_cart/cart_meta/estimated_tax_line', $line, $this );

		$line = '		
				<div id="mp-cart-meta-line-order-total" class="mp-cart-meta-line clearfix">
					<strong class="mp-cart-meta-line-label">' . (( $this->is_editable ) ? __( 'Estimated Total', 'mp' ) : __( 'Order Total', 'mp' )) . '</strong>
					<span class="mp-cart-meta-line-amount">' . $this->total( true ) . '</span>
				</div>
			</div>';

		/**
		 * Filter the order total html
		 *
		 * @since 3.0
		 * @param string The current order total html.
		 * @param MP_Cart The current cart object.
		 */
		$html .= apply_filters( 'mp_cart/cart_meta/order_total', $line, $this );

		/**
		 * Filter the cart meta html
		 *
		 * @since 3.0
		 * @param string The current cart meta html.
		 * @param MP_Cart The current cart object.
		 */
		$html = apply_filters( 'mp_cart/cart_meta', $html, $this );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Display the cart contents
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 * 		Optional, an array of arguments.
	 *
	 * 		@type bool $echo Optional, whether to echo or return. Defaults to false.
	 * 		@type string $view Optional, the cart view.
	 * 		@type bool $editable Optional, whether the cart is editable. Defaults to true.
	 * }
	 */
	public function display( $args = array() ) {
		$html	 = '';
		$args	 = array_replace_recursive( array(
			'echo'		 => false,
			'view'		 => null,
			'editable'	 => true,
		), $args );

		extract( $args );

		$this->is_editable = $editable;

		if ( ! $this->has_items() ) {
			$message = sprintf( __( 'There are no items in your cart - <a href="%s">go add some</a>!', 'mp' ), mp_store_page_url( 'products', false ) );

			/**
			 * Filter the no items in your cart message
			 *
			 * @since 3.0
			 * @param string $message The default message.
			 */
			$message = apply_filters( 'mp_cart/no_items_message', $message );

			if ( $echo ) {
				echo $message;
				return;
			} {
				return $message;
			}
		}
		
		if ( $editable ) {
			$html .= '
				<form id="mp-cart-form" method="post">';
		} else {
			$html .= '
				<div id="mp-cart-form">';
		}

		/**
		 * Add html before cart
		 *
		 * @since 3.0
		 * @param string
		 * @param MP_Cart $this The current cart object.
		 * @param array $args The arguments that were passed to the display method.
		 */
		$before_cart_html = apply_filters( 'mp_cart/before_cart_html', '', $this, $args );

		if ( !empty( $before_cart_html ) ) {
			$html .= '
				<div id="mp-cart-before" class="clearfix">' . $before_cart_html . '</div>';
		}

		/**
		 * Filter the cart classes array
		 *
		 * @since 3.0
		 * @param array The default classes.
		 */
		$classes = (array) apply_filters( 'mp_cart/cart_classes', array(
			'mp-cart-default',
			( $this->is_global ) ? 'mp-cart-global' : '',
			( $editable ) ? 'mp-cart-editable' : 'mp-cart-readonly',
		) );

		$html .= '
				<div id="mp-cart" class="' . implode( ' ', $classes ) . '">';

		$blog_ids = $this->get_blog_ids();
		
		while ( 1 ) {
			if ( $this->is_global ) {
				$blog_id = array_shift( $blog_ids );
				$this->set_id( $blog_id );
				$html .= '
					<div id="mp-cart-store-' . $this->_id . '" class="mp-cart-store">
						<h3 class="mp-cart-store-name"><a href="' . get_home_url( $this->_id ) . '">' . get_option( 'blogname' ) . '</a></h3>
						<div class="mp-cart-store-items">';
			}
			
			$products = $this->get_items_as_objects();
			foreach ( $products as $product ) {
				$html .= $this->get_line_item( $product );
			}
			
			if ( $this->is_global ) {
				$html .= '
						</div>';
				
				/**
				 * Filter the html after each store cart items
				 *
				 * @since 3.0
				 * @param string $after_cart_store_items_html The current html.
				 * @param MP_Cart $this The current cart object.
				 * @param array $args The arguments that were passed to the display method.				 
				 */
				$after_cart_store_items_html = apply_filters( 'mp_cart/after_cart_store_html', '', $this, $args );
				$after_cart_store_items_html = apply_filters( 'mp_cart/after_cart_store_html/' . $blog_id, $after_cart_store_items_html, $this, $args ); 
				
				$html .= $after_cart_store_items_html;
				
				$html .= '
					</div>';
			}
			
			if ( ($this->is_global && false === current( $blog_ids )) || ! $this->is_global ) {
				$this->reset_id();
				break;
			}
		}

		$html .= '
				</div>';

		/**
		 * Filter html after cart
		 *
		 * @since 3.0
		 * @param string
		 * @param MP_Cart $this The current cart object.
		 * @param array $args The arguments that were passed to the display method.
		 */
		$after_cart_html = apply_filters( 'mp_cart/after_cart_html', '', $this, $args );

		if ( !empty( $after_cart_html ) ) {
			$html .= '
				<div id="mp-cart-after" class="clearfix">' . $after_cart_html . '</div>';
		}

		if ( $view != 'order-status' ) {
			$html .= '
					<div id="mp-cart-meta-wrap" class="clearfix">' .
			$this->cart_meta( false, $editable );

			$button_text	 = __( 'Submit Order' );
			$button_alt_text = __( 'Continue &raquo;' );

			if ( get_query_var( 'mp_confirm_order_step' ) ) {
				$tooltip_text = __( '<strong>You are about to submit your order!</strong><br />Please review your order details before continuing. You will be charged immediately upon clicking "Submit Order".', 'mp' );
			} else {
				$tooltip_text = __( '<strong>Secure Checkout</strong><br />Shopping is always safe and secure.', 'mp' );
			}

			/**
			 * Filter the checkout button tooltip text
			 *
			 * @since 3.0
			 * @param string The current tooltip text.
			 */
			$tooltip_text = apply_filters( 'mp_cart/checkout_button/tooltip_text', $tooltip_text );

			if ( $editable ) {
				$button_text	 = __( 'Checkout', 'mp' );
				$p_class		 = 'mp-secure-checkout-tooltip-text';
				$button_classes	 = array(
					'mp-button',
					'mp-button-checkout',
					'mp-button-padlock',
					'mp-button-large',
					((!empty( $tooltip_text ) ) ? 'mp-has-tooltip' : ''),
				);
			} elseif ( get_query_var( 'mp_confirm_order_step' ) ) {
				$button_classes = array(
					'mp-button',
					'mp-button-checkout',
					'mp-button-large',
					((!empty( $tooltip_text ) ) ? 'mp-has-tooltip' : ''),
				);
			} else {
				$button_classes = array(
					'mp-button',
					'mp-button-checkout',
					'mp-button-large',
				);
			}

			/**
			 * Filter the checkout button text
			 *
			 * @since 3.0
			 * @param string The current button text.
			 */
			$button_text = apply_filters( 'mp_cart/checkout_button/text', $button_text );

			/**
			 * Filter the checkout button classes
			 *
			 * @since 3.0
			 * @param array The current button classes.
			 * @param bool $editable Whether the cart is editable or not.
			 */
			$button_classes = apply_filters( 'mp_cart/checkout_button/classes', $button_classes, $editable );

			if ( $editable ) {
				$html .= '
						<a class="' . implode( ' ', $button_classes ) . '" href="' . mp_store_page_url( 'checkout', false ) . '">' . $button_text . '</a>';
			} else {
				$html .= '
						<button class="' . implode( ' ', $button_classes ) . '" type="submit" data-mp-alt-html="' . $button_alt_text . '">' . $button_text . '</button>';
			}

			if ( !empty( $tooltip_text ) ) {
				$html .= '
						<div class="mp-tooltip-content"><p' . (( empty( $p_class ) ) ? '' : ' class="mp-secure-checkout-tooltip-text"') . '>' . $tooltip_text . '</p></div>';
			}

			$html .= '
					</div>';
		}

		if ( $editable ) {
			$html .= '
			</form>';
		} else {
			$html .= '
			</div>';
		}

		/**
		 * Filter the cart contents html
		 *
		 * @since 3.0
		 * @param string $html The current html.
		 * @param MP_Cart $this The current MP_Cart object.
		 * @param array $args The array of arguments as passed to the method.
		 */
		$html = apply_filters( 'mp_cart/display', $html, $this, $args );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Display the item quantity dropdown
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 * 		Optional, an array of arguments.
	 *
	 * 		@type int $max Optional, the max quantity allowed. Defaults to 10.
	 * 		@type int $selected Optional, the selected option. Defaults to 1.
	 * 		@type bool $echo Optional, whether to echo or return. Defaults to true.
	 * }
	 */
	public function dropdown_quantity( $args = array() ) {
		/**
		 * Change the default max quantity allowed
		 *
		 * @since 3.0
		 * @param int The default maximum.
		 */
		$max		 = apply_filters( 'mp_cart/quantity_dropdown/max_default', 10 );
		$defaults	 = array(
			'max'		 => $max,
			'selected'	 => 1,
			'echo'		 => true,
			'name'		 => '',
			'class'		 => 'mp-cart-item-qty-field',
			'id'		 => '',
		);
		$args		 = array_replace_recursive( $defaults, $args );

		extract( $args );

		// Build select field attributes
		$attributes = mp_array_to_attributes( compact( 'name', 'class', 'id' ) );

		$html = '
			<select' . $attributes . '>';
		for ( $i = 1; $i <= $max; $i ++ ) {
			$html .= '
				<option value="' . $i . '" ' . selected( $i, $selected, false ) . '>' . number_format_i18n( $i, 0 ) . '</option>';
		}
		$html .= '
			</select>';

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
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
		 * @param MP_Cart $this The current cart object.
		 */
		do_action( 'mp_cart/before_empty_cart', $this );

		$this->_items[ $this->_id ] = array();
		$this->_update_cart_cookie();

		/**
		 * Fires right after the cart is emptied
		 *
		 * @since 3.0
		 * @param MP_Cart $this The current cart object.
		 */
		do_action( 'mp_cart/after_empty_cart', $this );
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

		if ( !mp_is_shop_page() ) {
			return;
		}

		// Styles
		wp_enqueue_style( 'colorbox', mp_plugin_url( 'ui/css/colorbox.css' ), false, MP_VERSION );

		// Scripts
		wp_register_script( 'jquery-validate', mp_plugin_url( 'ui/js/jquery.validate.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'jquery-validate-methods', mp_plugin_url( 'ui/js/jquery.validate.methods.min.js' ), array( 'jquery-validate' ), MP_VERSION, true );
		wp_register_script( 'ajaxq', mp_plugin_url( 'ui/js/ajaxq.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-cart', mp_plugin_url( 'ui/js/mp-cart.js' ), array( 'ajaxq', 'colorbox', 'jquery-validate' ), MP_VERSION, true );

		// Localize scripts
		wp_localize_script( 'mp-cart', 'mp_cart_i18n', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
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

		if ( (!mp_is_shop_page() || mp_is_shop_page( 'cart' ) || mp_is_shop_page( 'checkout' )) && !mp_doing_ajax() ) {
			return;
		}


		$html = '
		<div id="mp-floating-cart"' . (( $this->has_items() ) ? ' class="has-items"' : '') . '>
			<div id="mp-floating-cart-tab" class="clearfix"><span id="mp-floating-cart-total">' . $this->product_total( true ) . '</span> ' . $this->item_count( false ) . '</div>
			<div id="mp-floating-cart-contents">';


		if ( $this->has_items() ) {
			$blog_ids = $this->get_blog_ids();

			$html .= '
				<ul id="mp-floating-cart-items-list">';

			while ( 1 ) {
				if ( $this->is_global ) {
					$blog_id = array_shift( $blog_ids );
					$this->set_id( $blog_id );

					$html .= '
						<li class="mp-floating-cart-store-name"><a href="' . get_home_url( $this->_id ) . '">' . get_blog_option( $this->_id, 'blogname' ) . '</a></li>';
				}

				$items = $this->get_items();

				foreach ( $items as $item => $qty ) {
					$html .= $this->floating_cart_line_item_html( $item, $qty );
				}

				if ( ($this->is_global && false === current( $blog_ids ) ) || !$this->is_global ) {
					$this->reset_id();
					break;
				}
			}

			$html .= '
				</ul>
				<a id="mp-floating-cart-button" href="' . $this->cart_url() . '">' . __( 'View Cart', 'mp' ) . '</a>';
		} else {
			$html .= '
				<div id="mp-floating-cart-no-items">
					<p><strong>' . __( 'Your shopping cart is empty.', 'mp' ) . '</strong></p>
					<p>' . __( 'As you add browse items and add them to your add cart they will show up here.', 'mp' ) . '</p>
				</div>';
		}

		$html .= '
			</div>
		</div>';

		if ( !mp_doing_ajax() ) {
			$html .= '<span class="mp-ajax-loader" style="display:none"><img src="' . mp_plugin_url( 'ui/images/ajax-loader.gif' ) . '" alt="" /> ' . __( 'Adding...', 'mp' ) . '</span>';
		}

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Get the html markup for an individual line item for the floating cart
	 *
	 * @since 3.0
	 * @access public
	 * @param int $item_id The product's ID.
	 * @param int $qty The quantity of the product in the cart.
	 */
	public function floating_cart_line_item_html( $item_id, $qty ) {
		$product = new MP_Product( $item_id );

		$html = '
			<li class="mp-floating-cart-item" id="mp-floating-cart-item-' . $product->ID . '">
				<a class="mp-floating-cart-item-link" href="' . $product->url( false ) . '">' . $product->image( false, 'floating-cart', 50 ) . '
					<div class="mp-floating-cart-item-content">
						<h3 class="mp-floating-cart-item-title">' . $product->title( false ) . '</h3>
						<span class="mp-floating-cart-item-attribute"><strong>' . __( 'Quantity', 'mp' ) . ':</strong> <em>' . $qty . '</em></span>';

		// Display attributes
		if ( $product->is_variation() ) {
			$attributes = $product->get_attributes();
			foreach ( $attributes as $taxonomy => $att ) {
				$term = current( $att[ 'terms' ] );
				$html .= '
						<span class="mp-floating-cart-item-attribute"><strong>' . $att[ 'name' ] . ':</strong> <em>' . $term . '</em></span>';
			}
		}

		$html .= '
					</div>
				</a>
			</li>';

		/**
		 * Filter the floating cart line item HTML
		 *
		 * @since 3.0
		 * @param string $html The current line item html.
		 * @param MP_Product $product The current product object.
		 * @param int $qty The quantity of the product in the cart.
		 */
		return apply_filters( 'mp_cart/floating_cart_line_item_html', $html, $product, $qty );
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
		return mp_arr_get_value( $this->_id . '->' . $item_id, $this->_items, 0 );
	}

	/**
	 * Check if cart has items
	 *
	 * @since 3.0
	 * @access public
	 */
	public function has_items() {
		if ( $this->is_global ) {
			$carts = $this->get_all_items();
			foreach ( $carts as $blog_id => $items ) {
				if ( count( $items ) > 0 ) {
					return true;
				}
			}
		} else {
			$items		 = $this->get_items();
			if ( count( $items ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if cart contains only downloadable products
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function is_download_only() {
		if ( !is_null( $this->_is_download_only ) ) {
			return $this->_is_download_only;
		}
		
		$blog_ids = $this->get_blog_ids();
		$this->_is_download_only = true;
		
		while ( 1 ) {
			if ( $this->is_global ) {
				$blog_id = array_shift( $blog_ids );
				$this->set_id( $blog_id );
			}
			
			$items = $this->get_items();
			
			foreach ( $items as $item_id => $qty ) {
				$product = new MP_Product( $item_id );
				if ( ! $product->is_download() ) {
					$this->_is_download_only = false;
				}
			}
			
			if ( ($this->is_global && false === current( $blog_ids )) || ! $this->is_global || ! $this->_is_download_only ) {
				$this->reset_id();
				break;
			}
		}

		return $this->_is_download_only;
	}

	/**
	 * Display the item count
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 * @param bool $format Optional, whether for format or not. Defaults to true.
	 */
	public function item_count( $echo = true, $format = true ) {
		$blog_ids	 = $this->get_blog_ids();
		$numitems	 = 0;

		while ( 1 ) {
			if ( $this->is_global ) {
				$blog_id = array_shift( $blog_ids );
				$this->set_id( $blog_id );
			}

			$items = $this->get_items();

			foreach ( $items as $item_id => $qty ) {
				$numitems += $qty;
			}

			if ( ($this->is_global && false === current( $blog_ids )) || !$this->is_global ) {
				$this->reset_id();
				break;
			}
		}

		$snippet = $numitems;
		if ( $format ) {
			if ( $numitems == 0 ) {
				$snippet = __( '0 items', 'mp' );
			} else {
				$snippet = sprintf( _n( '1 item', '%s items', $numitems, 'mp' ), $numitems );
			}
		}

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get the product total
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $format Optional, whether to format the value or not. Defaults to false.
	 * @return float/string
	 */
	public function product_total( $format = false ) {
		if ( false === mp_arr_get_value( 'product', $this->_total ) ) {
			$total						 = 0;
			$blog_ids					 = $this->get_blog_ids();
			$this->_total[ 'product' ]	 = 0;

			while ( 1 ) {
				if ( $this->is_global ) {
					$blog_id = array_shift( $blog_ids );
					$this->set_id( $blog_id );
				}

				$items = $this->get_items_as_objects();

				foreach ( $items as $item ) {
					$price			 = $item->get_price( 'lowest' );
					$item_subtotal	 = ($price * $item->qty);
					$total += $item_subtotal;
				}

				/**
				 * Filter the product total
				 *
				 * @since 3.0
				 * @param float The cart total.
				 * @param MP_Cart The current cart object.
				 * @param array The current cart items.
				 */
				$this->_total[ 'product' ] += (float) apply_filters( 'mp_cart/product_total', $total, $items );

				if ( ($this->is_global && false === current( $blog_ids ) ) || !$this->is_global ) {
					$this->reset_id();
					break;
				}
			}
		}

		$total = mp_arr_get_value( 'product', $this->_total );

		if ( $format ) {
			return mp_format_currency( '', $total );
		} else {
			return (float) round( $total, 2 );
		}
	}

	/**
	 * Remove an item
	 *
	 * @since 3.0
	 * @access public
	 * @param int $item_id The item ID to remove.
	 */
	public function remove_item( $item_id ) {
		if ( $this->_is_global_item_id( $item_id ) ) {
			list( $blog_id, $item_id ) = explode( '.', $item_id );
			$this->set_id( $blog_id );
		}
		
		if ( mp_arr_get_value( $this->_id . '->' . $item_id, $this->_items ) ) {
			/**
			 * Fires right before an item has been removed from the cart
			 *
			 * @since 3.0
			 * @access public
			 * @param int The ID of the item that has been removed.
			 * @param int The ID of the site the item was removed from.
			 */
			do_action( 'mp_cart/before_remove_item', $item_id, $this->_id );
			
			unset( $this->_items[ $this->_id ][ $item_id ] );
			$this->_update_cart_cookie();
			
			/**
			 * Fires after an item has been removed from the cart
			 *
			 * @since 3.0
			 * @access public
			 * @param int The ID of the item that has been removed.
			 * @param int The ID of the site the item was removed from.
			 */
			do_action( 'mp_cart/after_remove_item', $item_id, $this->_id );
		}
		
		$this->reset_id();
	}

	/**
	 * Reset cart ID back to the original
	 *
	 * @since 3.0
	 * @access public
	 */
	public function reset_id() {
		if ( is_null( $this->_id_original ) ) {
			return;
		}
		
		$this->_id = $this->_id_original;
		$this->_id_original = null;
		
		if ( $this->is_global ) {
			switch_to_blog( $this->_id );
		}
	}

	/**
	 * Set the cart ID
	 *
	 * @since 3.0
	 * @access public
	 * @param int $id
	 */
	public function set_id( $id ) {
		if ( is_null( $this->_id_original ) ) {
			$this->_id_original = $this->_id;
		}

		$this->_id = $id;
		
		if ( $this->is_global ) {
			switch_to_blog( $this->_id );
		}
	}

	/**
	 * Get the amount of tax applied to the shipping total
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $format Optional, whether to format the returned value or not. Defaults to false.
	 * @return float/string
	 */
	public function shipping_tax_total( $format = false ) {
		$shipping_tax = 0;
		$shipping_price = $this->shipping_total();

		if ( mp_get_setting( 'tax->tax_shipping' ) && $shipping_price ) {
			if ( mp_get_setting( 'tax->tax_inclusive' ) ) {
				$shipping_tax = ($shipping_price - mp_before_tax_price( $shipping_price ));
			} else {
				$tax_rate = mp_tax_rate();
				$shipping_tax = ($shipping_price * $tax_rate);
			}
		}

		/**
		 * Filter the shipping tax amount
		 *
		 * @since 3.0
		 * @param int $shipping_tax The current shipping tax amount.
		 * @param float $shipping_price The current shipping price including tax
		 * @param MP_Cart $this The current cart object.
		 */
		$shipping_tax = (float) apply_filters( 'mp_cart/shipping_tax_amt', $shipping_tax, $shipping_price, $this );

		return ( $format ) ? mp_format_currency( '', $shipping_tax ) : $shipping_tax;
	}

	/**
	 * Get the calculated price for shipping
	 *
	 * @since 3.0
	 * @access public
	 * @return float The calculated price. False, if shipping address is not available
	 */
	public function shipping_total( $format = false ) {
		if ( false === mp_arr_get_value( 'shipping', $this->_total ) ) {
			$this->_total[ 'shipping' ] = 0;
			
			//get address
			$what = ( mp_get_user_address( 'shipping' ) != mp_get_user_address( 'billing' ) ) ? 'shipping' : 'billing';
			$address1 = mp_get_user_address_part( 'address1', $what );
			$address2 = mp_get_user_address_part( 'address2', $what );
			$city = mp_get_user_address_part( 'city', $what );
			$state = mp_get_user_address_part( 'state', $what );
			$zip = mp_get_user_address_part( 'zip', $what );
			$country = mp_get_user_address_part( 'country', $what );

			//check required fields
			if ( empty( $address1 ) || empty( $city ) || !mp_is_valid_zip( $zip, $country ) || empty( $country ) || !$this->has_items() ) {
				return false;
			}
			
			$blog_ids = $this->get_blog_ids();
			$shipping_plugins = MP_Shipping_API::get_active_plugins();
			
			while ( 1 ) {
				$selected_sub_option = mp_get_session_value( 'mp_shipping_info->shipping_sub_option', null );
				$selected_option = mp_get_session_value( 'mp_shipping_info->shipping_option' );				
				
				if ( $this->is_global ) {
					$blog_id = array_shift( $blog_ids );
					$this->set_id( $blog_id );
					$selected_sub_option = mp_get_session_value( "mp_shipping_info->shipping_sub_option->{$blog_id}", null );
					$selected_option = mp_get_session_value( "mp_shipping_info->shipping_option->{$blog_id}" );									
				}

				$products = $this->get_items_as_objects();
				$total = $this->product_total();

				//don't charge shipping if only digital products
				if ( $this->is_download_only() ) {
					$price = 0;
				} else if ( mp_get_setting( 'shipping->method' ) == 'calculated' && $selected_option ) {
					//shipping plugins tie into this to calculate their shipping cost
					$price = (float) apply_filters( 'mp_calculate_shipping_' . $selected_option, 0, $total, $this, $address1, $address2, $city, $state, $zip, $country, $selected_option );
				} else {
					//shipping plugins tie into this to calculate their shipping cost
					$price = (float) apply_filters( 'mp_calculate_shipping_' . mp_get_setting( 'shipping->method' ), 0, $total, $this, $address1, $address2, $city, $state, $zip, $country, $selected_option );
				}
				
				//calculate extra shipping
				foreach ( $products as $product ) {
					if ( !$product->is_download() ) {
						$price += $product->get_meta( 'weight_extra_shipping_cost' ) * $product->qty;
					}
				}
	
				if ( empty( $price ) ) {
					$price = 0;
				}

				$this->_total[ 'shipping' ] += $price;
				
				if ( ($this->is_global && false === current( $blog_ids )) || ! $this->is_global ) {
					$this->reset_id();
					break;
				}
			}
		}

		$shipping_total = mp_arr_get_value( 'shipping', $this->_total, 0 );

		if ( empty( $shipping_total ) ) {
			return '&mdash;';
		} else {
			if ( $format ) {
				return mp_format_currency( '', $shipping_total );
			} else {
				return round( $shipping_total, 2 );
			}
		}
	}

	/**
	 * Get the total shipping weight
	 *
	 * @since 3.0
	 * @access public
	 * @return float
	 */
	public function shipping_weight() {
		$blog_ids = $this->get_blog_ids();
		$weight = 0;
		
		while ( 1 ) {
			if ( $this->is_global ) {
				$blog_id = array_shift( $blog_ids );
				$this->set_id( $blog_id );	
			}
			
			$products = $this->get_items_as_objects();
			
			foreach ( $products as $product ) {
				$weight += $product->get_weight();
			}
			
			if ( ($this->is_global && false === current( $blog_ids )) || ! $this->is_global ) {
				$this->reset_id();
				break;
			}
		}

		/**
		 * Filter the cart shipping weight
		 *
		 * @since 3.0
		 * @access public
		 * @param float $weight The current shipping weight.
		 * @param MP_Cart $this The MP_Cart object.
		 */
		return (float) apply_filters( 'mp_cart/shipping_weight', $weight, $this );
	}

	/**
	 * Get the calculated price for taxes based on a bunch of foreign tax laws.
	 *
	 * @access public
	 * @param bool $format (optional) Format number as currency when returned.
	 * @param bool $format (optional) Estimate taxes if user hasn't entered their address yet.
	 * @return string/float 
	 */
	public function tax_total( $format = false, $estimate = false ) {
		if ( false === mp_arr_get_value( 'tax', $this->_total ) ) {
			$tax_amt = 0;
			
			//get address
			$state = mp_get_user_address_part( 'state', 'shipping' );
			$country = mp_get_user_address_part( 'country', 'shipping' );

			if ( $estimate ) {
				if ( empty( $country ) ) {
					$country = mp_get_setting( 'base_country' );
				}

				if ( empty( $state ) ) {
					$state = mp_get_setting( 'base_province' );
				}
			}
			
			if ( empty( $country ) || ! $this->has_items() ) {
				return false;
			}

			$blog_ids = $this->get_blog_ids();
			
			while ( 1 ) {
				$total = $special_total = 0;
				
				if ( $this->is_global ) {
					$blog_id = array_shift( $blog_ids );
					$this->set_id( $blog_id );
				}
				
				$items = $this->get_items_as_objects();
				foreach ( $items as $item ) {
					// If not taxing digital goods, skip them completely
					if ( ! mp_get_setting( 'tax->tax_digital' ) && $item->is_download() ) {
						continue;
					}
					
					if ( $special_tax_amt = $item->special_tax_amt() ) {
						$special_total += $special_tax_amt * $item->qty;
					} else {
						$total += $item->before_tax_price() * $item->qty;
					}
				}
				
				if ( ($total + $special_total) <= 0 ) {
					break;
				}
				
				// Calculate regular tax
				$tax_amt += ($total * mp_tax_rate());
				
				// Add in special tax
				$tax_amt += $special_total;
				
				// Add in shipping?
				$tax_amt += $this->shipping_tax_total();

				/**
				 * Filter the tax price
				 *
				 * @since 3.0
				 * @param float $tax_amt The calculated tax price.
				 * @param float $total The cart total.
				 * @param MP_Cart $this The current cart object.
				 * @param string $country The user's country.
				 * @param string $state $the user's state/province.
				 */
				$tax_amt = apply_filters( 'mp_tax_price', $tax_amt, $total, $this, $country, $state );
				$tax_amt = apply_filters( 'mp_cart/tax_total', $tax_amt, $total, $this, $country, $state );
				
				if ( ($this->is_global && false === current( $blog_ids )) || ! $this->is_global ) {
					$this->reset_id();
					break;
				}
			}
			
			$this->_total['tax'] = $tax_amt;
		}

		$tax_total = mp_arr_get_value( 'tax', $this->_total, 0 );

		if ( $format ) {
			return mp_format_currency( '', $tax_total );
		} else {
			return round( $tax_total, 2 );
		}
	}

	/**
	 * Get total
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $format Optional, whether to format or not. Defaults to false.
	 * @return float
	 */
	public function total( $format = false ) {
		if ( false === mp_arr_get_value( 'total', $this->_total ) ) {
			$total = ( $this->product_total() + $this->tax_total() + $this->shipping_total() );

			/**
			 * Filter the total
			 *
			 * @since 3.0
			 * @param float The current total.
			 * @param array An array containing all of the applicable cart subtotals (e.g. tax, shipping, etc)
			 * @param MP_Cart The current cart object.
			 */
			$total = apply_filters( 'mp_cart/total', $total, $this->_total, $this );

			$this->_total[ 'total' ] = $total;
		}

		$total = mp_arr_get_value( 'total', $this->_total, 0 );

		if ( $format ) {
			return mp_format_currency( '', $total );
		} else {
			return round( $total, 2 );
		}
	}

	/**
	 * Update an item quantity
	 *
	 * @since 3.0
	 * @access public
	 * @param int $item_id The item to update.
	 * @param int $qty The qty to update the item to.
	 */
	public function update_item( $item_id, $qty ) {
		mp_push_to_array( $this->_items, $this->_id . '->' . $item_id, $qty );
		$this->_update_cart_cookie();
	}

	/**
	 * Alert about unavailable items or stock issues
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_footer
	 */
	public function unavailable_items_alert() {
		if ( count( $this->_items_unavailable[ 'deleted' ] ) == 0 && count( $this->_items_unavailable[ 'stock_issue' ] ) == 0 ) {
			// No items unavailable or have stock issues - bail
			return;
		}

		$message = '';
		if ( count( $this->_items_unavailable[ 'deleted' ] ) != 0 ) {
			$message .= __( 'Some items in your cart are no longer available. We have removed these items from your cart automatically.', 'mp' ) . '\n\n';
		}

		if ( count( $this->_items_unavailable[ 'stock_issue' ] ) != 0 ) {
			$message .= __( 'Some items in your cart have fallen below the quantity you currently have in your cart. We have adjusted the quantity in your cart automatically.', 'mp' );
		}
		?>
		<script type="text/javascript">
			alert( "<?php echo $message; ?>" );
		</script>
		<?php
	}

	/**
	 * Update the cart cookie
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _update_cart_cookie() {
		if ( !$this->_use_cookies ) {
			// Not using cookies - bail
			return false;
		}

		$expire = strtotime( '+1 month' );
		if ( empty( $this->_items ) ) {
			if ( $cart_cookie = mp_get_cookie_value( $this->_cookie_id ) ) {
				$expire = strtotime( '-1 month' );
			} else {
				return;
			}
		}

		// Set the cookie domain
		$cookie_domain = COOKIE_DOMAIN;

		if ( mp_cart()->is_global && is_subdomain_install() ) {
			$cookie_domain = get_blog_details( mp_main_site_id() )->domain;
		}

		setcookie( $this->_cookie_id, serialize( $this->_items ), $expire, '/', $cookie_domain );
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 * @param bool $use_cookies Optional, whether the cart should use cookies or not. Defaults to true.
	 */
	public function __construct( $use_cookies = true ) {
		$this->_use_cookies = $use_cookies;

		if ( $this->_use_cookies ) {
			$this->set_id( get_current_blog_id() );
			$this->_get_cart_cookie();
		}

		// Enqueue styles/scripts
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );

		// Admin styles/scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ) );

		// Display the floating cart html
		add_action( 'wp_footer', array( &$this, 'floating_cart_html' ) );
		add_action( 'wp_footer', array( &$this, 'unavailable_items_alert' ) );

		// Ajax hooks
		add_action( 'wp_ajax_mp_update_cart', array( &$this, 'ajax_update_cart' ) );
		add_action( 'wp_ajax_nopriv_mp_update_cart', array( &$this, 'ajax_update_cart' ) );
	}

}

$GLOBALS[ 'mp_cart' ] = MP_Cart::get_instance();
