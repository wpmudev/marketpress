<?php
	
class MP_Order {
	/**
	 * Refers to the internal post ID
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $ID = null;
	
	/**
	 * Refers to the order ID
	 *
	 * @since 3.0
	 * @access protected
	 * @var string
	 */
	protected $_order_id = null;

	/**
	 * Refers to the order's internal WP_Post object
	 *
	 * @since 3.0
	 * @access protected
	 * @type WP_Post
	 */
	protected $_post = null;

	/**
	 * Refers to the whether the order exists or not
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_exists = null;
	
	/**
	 * Refers to the order meta
	 *
	 * @since 3.0
	 * @access protected
	 * @type array
	 */
	protected $_meta = null;
	
	/**
	 * Attempt to get an internal WP_Post object property (e.g post_name, post_status, etc)
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The property name.
	 * @return string The property value or false if the property or post doesn't exist.
	 */
	public function __get( $name ) {
		if ( ! $this->exists() ) {
			return false;
		}
		
		if ( property_exists( $this->_post, $name ) ) {
			return $this->_post->$name;
		}
		
		return false;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access public
	 * @uses $post
	 * @param int/string $order Optional, either a post id or an order id. If neither are provided a new order will be created. 
	 */
	public function __construct( $order = null ) {
		if ( ! is_null( $order ) ) {
			if ( is_numeric( $order ) ) {
				$this->ID = $order;
			} else {
				$this->_order_id = $order;	
			}
			
			$this->_get_post();
		} else {
			$this->_generate_id();
		}
	}

	/**
	 * Generate a unique order id
	 *
	 * @since 3.0
	 * @access protected
	 * @return string
	 */
	protected function _generate_id() {
		if ( ! is_null( $this->_order_id ) ) {
			return $this->_order_id;
		}
		
		global $wpdb;

		$count = true;
		while ( $count ) {
			//make sure it's unique
			$order_id = substr( sha1( uniqid( '' ) ), rand( 1, 24 ), 12 );
			$count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*)
				FROM {$wpdb->posts}
				WHERE post_title = %s
				AND post_type = 'mp_order'", $order_id
			) );
		}
		
		/**
		 * Filter the order id
		 *
		 * It's VERY important to make sure order numbers are unique and not
		 * sequential.
		 *
		 * @since 3.0
		 * @param string $order_id
		 */
		$this->_order_id = apply_filters( 'mp_order_id', $order_id );
		
		return $this->_order_id;
	}

	/**
	 * Attempt to set the internal WP_Post object
	 *
	 * @since 3.0
	 * @access protected
	 * @uses $wpdb
	 */
	protected function _get_post() {
		global $wpdb;
		
		if ( is_null( $this->ID ) ) {
			if ( $_post = wp_cache_get( $this->_order_id, 'mp_order') ) {
				$this->_post = $_post;
			} else {
				$_post = $wpdb->get_row( $wpdb->prepare( "
					SELECT *
					FROM $wpdb->posts
					WHERE post_type = 'mp_order'
					AND post_status != 'trash'
					AND post_name = %s
					LIMIT 1
				", $this->_order_id ) );
				
				if ( ! empty( $_post ) ) {
					$this->_post = get_post( $_post );
				}
				
				wp_cache_set( $this->_order_id, $this->_post, 'mp_order' );
			}
		} else {
			$this->_post = get_post( $this->ID );
		}
		
		if ( is_null( $this->_post ) ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type != 'mp_order' ) {
			$this->_exists = false;
		} else {
			$this->_exists = true;
			$this->ID = $this->_post->ID;
		}
	}
	
	/**
	 * Send new order notifications
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _send_notifications() {
		$subject =  mp_filter_email( $this, nl2br( stripslashes( mp_get_setting( 'email->new_order->subject' ) ) ) );
		$msg = mp_filter_email( $this, nl2br( stripslashes( mp_get_setting( 'email->new_order->text' ) ) ) );
		
		if ( has_filter( 'mp_order_notification_subject' ) ) {
			trigger_error( 'The <strong>mp_order_notification_subject</strong> hook has been replaced with <strong>mp_order/notification_subject</strong> as of MP 3.0', E_USER_ERROR );
		}
		
		if ( has_filter( 'mp_order_notification_body' ) ) {
			trigger_error( 'The <strong>mp_order_notification_body</strong> hook has been replaced with <strong>mp_order/notification_body</strong> as of MP 3.0', E_USER_ERROR );
		}
		
		if ( has_filter( 'mp_order_notification_' . mp_get_post_value( 'payment_method', '' ) ) ) {
			trigger_error( 'The <strong>mp_order_notification_' . mp_get_post_value( 'payment_method', '' ) . '</strong> hook has been replaced with <strong>mp_order/notification_body/' . mp_get_post_value( 'payment_method', '' ) . '</strong> as of MP 3.0', E_USER_ERROR );
		}
		
		/**
		 * Filter the notification subject
		 *
		 * @since 3.0
		 * @param string $subject The current subject.
		 * @param MP_Order $this The current order object.
		 */
		$subject = apply_filters( 'mp_order/notification_subject', $subject, $this );
		
		/**
		 * Filter the notification message
		 *
		 * @since 3.0
		 * @param string $msg The current message.
		 * @param MP_Order $this The current order object.
		 */
		$msg = apply_filters( 'mp_order/notification_body', $msg, $this );
		$msg = apply_filters( 'mp_order/notification_body_' . mp_get_post_value( 'payment_method', '' ), $msg, $this );

		// Send email to buyer
		$billing_email = $this->get_meta( 'mp_billing_info->email', '' );
		$shipping_email = $this->get_meta( 'mp_shipping_info->email', '' );
		$email_sent = mp_send_email( $billing_email, $subject, $msg );
		
		if ( $billing_email != $shipping_email ) {
			// Billing email is different than shipping email so let's send an email to the shipping email too
			$email_sent = mp_send_email( $shipping_email, $subject, $msg );
		}
		
		// Send message to admin
		$subject = __('New Order Notification: ORDERID', 'mp');
		$msg = __('A new order (ORDERID) was created in your store:<br /><br />

ORDERINFOSKU<br /><br />
SHIPPINGINFO<br /><br />
PAYMENTINFO<br /><br />

You can manage this order here: %s', 'mp');

	 	$subject = mp_filter_email( $this, $subject );
	 	
	 	/**
	 	 * Filter the admin order notification subject
	 	 *
	 	 * @since 3.0
	 	 * @param string $subject
	 	 * @param MP_Order $this
	 	 */
		$subject = apply_filters( 'mp_order_notification_admin_subject', $subject, $this );
		
		$msg = mp_filter_email( $this, $msg, true );
		$msg = sprintf( $msg, admin_url('post.php?post=' . $this->ID . '&action=edit' ) );
		
		/**
		 * Filter the admin order notification message
		 *
		 * @since 3.0
		 * @param string $msg
		 * @param MP_Order $order
		 */
		$msg = apply_filters( 'mp_order_notification_admin_msg', $msg, $this );
		
		mp_send_email( mp_get_store_email(), $subject, $msg );
	}
	
	/**
	 * Change the order status
	 *
	 * @since 3.0
	 * @access public
	 */
	public function change_status( $status ) {
		$action = "mp_order_{$status}";
		$post_status = 'order_' . $status;
		
		switch ( $status ) {
			case 'received' :
				add_post_meta( $this->ID, 'mp_received_time', time(), true );
			break;
			
			case 'paid' :
				add_post_meta( $this->ID, 'mp_paid_time', time(), true );
			break;
			
			case 'shipped' :
				add_post_meta( $this->ID, 'mp_shipped_time', time(), true );
			break;
			
			case 'closed' :
				add_post_meta( $this->ID, 'mp_closed_time', time(), true );
			break;
			
			case 'trash' :
				add_post_meta( $this->ID, 'mp_trashed_time', time(), true );
				$action = 'mp_order_trashed';
				$post_status = 'trash';
			break;
			
			default :
				// Not a valid status - bail
				return false;
			break;
		}
		
		/**
		 * Fires when an order status is updated
		 *
		 * @since 3.0
		 * @param MP_Order $this The current order object.
		 */
		do_action( $action, $this );

		// Update the order status
		wp_update_post( array(
			'ID' => $this->ID,
			'post_status' => $status,
		) );
	}
	
	/**
	 * Display the order details
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function details( $echo = true ) {
		$html = '
			<div id="mp-order-details">
				<h3>' . __('Order #', 'mp' ) . ' ' . $this->get_id() . '</h3>
				<div id="mp-order-details-head" class="clearfix">';
		
		// Cart
		$cart = $this->get_meta( 'mp_cart_info' );
		
		// Currency
		$currency = $this->get_meta( 'mp_payment_info->currency', '' );
		
		// Received time
		$received = ( $time = $this->get_meta( 'mp_received_time' ) ) ? mp_format_date( $time, true ) : false;
		
		// Status
		$status = __( 'Received', 'mp' );
		$status_extra = '';
		switch ( $this->_post->post_status ) {
			case 'order_shipped' :
				$status = __( 'Shipped', 'mp' );
				if ( $tracking_num = $this->get_meta( 'mp_shipping_info->tracking_num' ) ) {
					$status = $this->tracking_link( false );
				}
			break;
			
			case 'order_paid' :
				$status = __( 'In Process', 'mp' );
			break;
			
			case 'order_closed' :
				$status = __( 'Closed', 'mp' );
			break;
		}
		
		$tooltip_content = '
			<div class="clearfix"><strong style="float:left;padding-right:15px;">' . __( 'Taxes:', 'mp' ) . '</strong><span style="float:right">' . $cart->tax_total( true ) . '</span></div>
			<div class="clearfix"><strong style="float:left;padding-right:15px;">' . __( 'Shipping:', 'mp' ) . '</strong><span style="float:right">' . $cart->shipping_total( true ) . '</span></div>';

		/**
		 * Filter the order total tooltip content
		 *
		 * @since 3.0
		 * @param string $tooltip_content
		 * @param MP_Order $this The current order object.
		 */
		$tooltip_content = apply_filters( 'mp_order/tooltip_content_total', $tooltip_content, $this );
		
		$html .= '
					<div class="mp-order-details-head-col"><strong>' . __( 'Order Received', 'mp' ) . '</strong> ' . $received . '</div>
					<div class="mp-order-details-head-col"><strong>' . __( 'Current Status', 'mp' ) . '</strong> ' . $status . '</div>
					<div class="mp-order-details-head-col">
						<strong>' . __( 'Total', 'mp' ) . '</strong>
						<a href="javascript:;" class="mp-has-tooltip">' . mp_format_currency( $currency, $this->get_meta( 'mp_order_total', '' ) ) . '</a>
						<div class="mp-tooltip-content">
							<div class="clearfix">' . $tooltip_content . '</div>
						</div>
					</div>';
					
		$html .= '
				</div>' .
			
				$cart->display( array( 'editable' => false, 'view' => 'order-status' ) ) . '
				
				<div class="clearfix">
					<div style="float:left;width:48%">
						<h4>Shipping Address</h4>' .
						$this->get_meta( 'mp_shipping_info->first_name', '' ) . ' ' . $this->get_meta( 'mp_shipping_info->last_name', '' ) . '<br />' .
						$this->get_meta( 'mp_shipping_info->address1', '' ) . '<br />' .
						(( $address2 = $this->get_meta( 'mp_shipping_info->address2', '' ) ) ? $address2 . '<br />' : '' ) .
						(( $city = $this->get_meta( 'mp_shipping_info->city', '' ) ) ? $city : '' ) .
						(( $state = $this->get_meta( 'mp_shipping_info->state', '' ) ) ? ', ' . $state . ' ' : '' ) .
						(( $zip = $this->get_meta( 'mp_shipping_info->zip', '' ) ) ? $zip . '<br />' : '' ) .
						(( $phone = $this->get_meta( 'mp_shipping_info->phone', '' ) ) ? $phone . '<br />' : '' ) .
						(( $email = $this->get_meta( 'mp_shipping_info->email', '' ) ) ? '<a href="mailto:' . antispambot( $email ) . '">' . antispambot( $email ) . '</a><br />' : '' ) . '
					</div>
					<div style="float:right;width:48%">
						<h4>Billing Address</h4>' .
						$this->get_meta( 'mp_billing_info->first_name', '' ) . ' ' . $this->get_meta( 'mp_billing_info->last_name', '' ) . '<br />' .
						$this->get_meta( 'mp_billing_info->address1', '' ) . '<br />' .
						(( $address2 = $this->get_meta( 'mp_billing_info->address2', '' ) ) ? $address2 . '<br />' : '' ) .
						(( $city = $this->get_meta( 'mp_billing_info->city', '' ) ) ? $city : '' ) .
						(( $state = $this->get_meta( 'mp_billing_info->state', '' ) ) ? ', ' . $state . ' ' : '' ) .
						(( $zip = $this->get_meta( 'mp_billing_info->zip', '' ) ) ? $zip . '<br />' : '' ) .
						(( $phone = $this->get_meta( 'mp_billing_info->phone', '' ) ) ? $phone . '<br />' : '' ) .
						(( $email = $this->get_meta( 'mp_billing_info->email', '' ) ) ? '<a href="mailto:' . antispambot( $email ) . '">' . antispambot( $email ) . '</a><br />' : '' ) . '
					</div>
				</div>
			</div>';
			
		/**
		 * Filter the order details
		 *
		 * @since 3.0
		 * @param string $html The current details.
		 * @param MP_Order $this The current order object.
		 */
		$html = apply_filters( 'mp_order/details', $html, $this );
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
	
		
		/**
	 * Check if a order exists
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function exists() {
		return $this->_exists;
	}
	
	/**
	 * Get the order ID
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function get_id() {
		return $this->_order_id;
	}
	
	/**
	 * Get order meta
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to retrieve.
	 * @return mixed
	 */
	public function get_meta( $name, $default = false ) {
		if ( is_null( $this->_meta ) ) {
			$meta = get_post_custom( $this->ID );
			foreach ( $meta as $key => $val ) {
				$this->_meta[ $key ] = maybe_unserialize( current( $val ) );
			}
		}
		
		return mp_arr_get_value( $name, $this->_meta, $default );
	}

	/**
	 * Get buyer's full name
	 *
	 * @since 3.0
	 * @access public
	 * @param string $type Optional, either "shipping" or "billing". Defaults to "billing".
	 */
	public function get_name( $type = 'billing' ) {
		$fullname = $this->get_meta( "mp_{$type}_info->name" );
		if ( empty($fullname) ) {
			$fullname = $this->get_meta( "mp_shipping_info->name" );
		}
		
		return ( ! empty( $fullname ) ) ? $fullname : $this->get_meta( "mp_{$type}_info->first_name" ) . ' ' . $this->get_meta( "mp_{$type}_info->last_name" );
	}
		
	/**
	 * Save the order to the database
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments
	 *
	 * 		@type MP_Cart $cart Required, a MP_Cart object.
	 *		@type array $payment_info Required, an array of payment payment info.
	 *		@type array $billing_info Optional, an array of billing info. Defaults to the current user billing info.
	 *		@type array $shipping_info Optional, an array of shipping info. Defaults to the current user shipping info.
	 *		@type bool $paid Optional, whether the order is paid or not. Defaults to false.
	 *		@type int $user_id Optional, the user id for the order. Defaults to the current user.
	 *		@type float $shipping_total Optional, the shipping total. Defaults to the calculated total.
	 *		@type float $shipping_tax_total Optional, the tax amount for shipping. Defaults to the calculated total.
	 *		@type float $tax_total Optional, the tax total. Defaults to the calculated total.
	 *		@type array $coupons Optional, an array of coupons that were used. Defaults to the cart coupons.
	 */
	public function save( $args ) {
		$args = array_replace_recursive( array(
			'cart' => null,
			'payment_info' => null,
			'billing_info' => mp_get_user_address( 'billing' ),
			'shipping_info' => mp_get_user_address( 'shipping' ),
			'paid' => false,
			'user_id' => get_current_user_id(),
			'shipping_total' => mp_cart()->shipping_total( false ),
			'shipping_tax_total' => mp_cart()->shipping_tax_total( false ),
			'tax_total' => mp_cart()->tax_total( false ),
			'coupons' => mp_coupons()->get_applied(),
		), $args );
		
		extract( $args );
		
		// Check required fields
		if ( is_null( $cart ) || is_null( $payment_info ) ) {
			return false;
		}
		
		// Create new post
		$post_id = wp_insert_post( array(
			'post_title' => $this->get_id(),
			'post_name' => $this->get_id(),
			'post_content' => serialize( $cart->get_items() ) . serialize( $shipping_info ) . serialize( $billing_info ), // this is purely for search capabilities
			'post_status' => ( $paid ) ? 'order_paid' : 'order_received',
			'post_type' => 'mp_order',
		) );
		
		// Set the internal post object in case we need to use it right away
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		
		$this->ID = $post_id;
		$this->_post = get_post( $post_id );

		$items = $cart->get_items_as_objects();
		foreach ( $items as &$item ) {
			/* make sure price is saved to product object so when retrieved later the
			correct price is returned */
			$item->get_price();
		}
		
		// Save cart info
		add_post_meta( $this->ID, 'mp_cart_info', $cart, true );
		// Save shipping info
		add_post_meta( $this->ID, 'mp_shipping_info', $shipping_info, true );
		// Save billing info
		add_post_meta( $this->ID, 'mp_billing_info', $billing_info, true );
		// Save payment info
		add_post_meta( $this->ID, 'mp_payment_info', $payment_info, true );
		
		$item_count = 0;
		foreach ( $items as $item ) {
			$item_count += $item->qty;
			
			if ( $item->get_meta( 'inventory_tracking' ) ) {
				$stock = $item->get_stock();
				
				// Update inventory
				$new_stock = ($stock - $item->qty);
				$item->update_meta( 'inventory', $new_stock );
				
				// Send low-stock notification if needed
				if ( $new_stock <= mp_get_setting( 'inventory_threshhold' ) ) {
					$item->low_stock_notification();
				}
				
				if ( mp_get_setting( 'inventory_remove') && $new_stock <= 0 ) { 
					// Flag product as out of stock - @version 2.9.5.8
					wp_update_post( array(
						'ID' => $this->ID,
						'post_status' => 'out_of_stock'
					) );
				}
			}
			
			// Update sales count
			$count = $item->get_meta( 'mp_sales_count', 0 );
			$count += $item->qty;
			$item->update_meta( 'mp_sales_count', $count );
			
			if ( has_filter( 'mp_product_sale') ) {
				trigger_error( 'The <strong>mp_product_sale</strong> hook has been replaced by <strong>mp_order/product_sale</strong> as of MP 3.0.', E_USER_ERROR );
			}
			
			/**
			 * Fires after the sale of a product during checkout
			 *
			 * @since 3.0
			 * @param MP_Product $item The product that was sold.
			 * @param bool $paid Whether the associated order has been paid.
			 */			
			do_action( 'mp_checkout/product_sale', $item, $paid );
		}
		
		// Payment info
		add_post_meta( $this->ID, 'mp_order_total', mp_arr_get_value( 'total', $payment_info ), true );
		
		// Shipping totals
		add_post_meta( $this->ID, 'mp_shipping_total', $shipping_total, true );
		
		// Taxes
		add_post_meta( $this->ID, 'mp_shipping_tax', $shipping_tax_total, true );
		add_post_meta( $this->ID, 'mp_tax_total', $tax_total, true );
		add_post_meta( $this->ID, 'mp_tax_inclusive', mp_get_setting( 'tax->tax_inclusive' ), true );
		add_post_meta( $this->ID, 'mp_tax_shipping', mp_get_setting( 'tax->tax_shipping' ), true );
		
		// Number of items ordered
		add_post_meta( $this->ID, 'mp_order_items', $item_count, true );
		
		// Order time
		add_post_meta( $this->ID, 'mp_received_time', time(), true );
		
		// If applicable, update order status to paid
		if ( $paid ) {
			$this->change_status( 'paid' );
		}

		// Update order history
		$orders = mp_get_order_history( $user_id );
		$new_order = array(
			'id' => $this->ID,
			'total' => mp_arr_get_value( 'total', $payment_info ),
		);
		
		if ( is_multisite() ) {
			global $blog_id;
			$key = 'mp_order_history_' . $blog_id;
		} else {
			$key = 'mp_order_history';
		}
		
		if ( $user_id ) {
			// Save to user meta
			$timestamp = time();
			$orders[ $timestamp ] = $new_order;
			update_user_meta( $user_id, $key, $orders );
		} else {
			// Save to cookie
			$timestamp = time();
			$orders[ $timestamp ] = $new_order;
			$expire = time() + 31536000; // 1 year expire
			setcookie( $key, serialize( $orders ), $expire, COOKIEPATH, COOKIEDOMAIN );
		}

		if ( has_filter( 'mp_new_order' ) ) {
			trigger_error( 'The <strong>mp_new_order</strong> hook has been replaced by <strong>mp_order/new_order</strong> as of MP 3.0.', E_USER_ERROR );
		}
		
		/**
		 * Fires when an order is created
		 *
		 * @since 3.0
		 * @param MP_Order $this The current order object.
		 */
		do_action( 'mp_order/new_order', $this );

		// Empty cart
		mp_cart()->empty_cart();		
		
		// Send new order email
		$this->_send_notifications();

		// If paid and the cart is only digital products mark it shipped
		if ( $paid && mp_cart()->is_download_only() ) {
			$this->change_status( 'shipped' );
		}

		// Cache the ID for later use
		wp_cache_set( 'order_object', $this, 'mp' );
	}
	
	/**
	 * Get the order's shipment tracking url
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function tracking_link( $echo = true ) {
		$tracking_number = esc_attr( $this->get_meta( 'mp_shipping_info->tracking_num' ) );
		$method = $this->get_meta( 'mp_shipping_info->method' );
		
		switch ( $method ) {
			case 'UPS' :
				$url = 'http://wwwapps.ups.com/WebTracking/processInputRequest?sort_by=status&tracknums_displayed=1&TypeOfInquiryNumber=T&loc=en_us&InquiryNumber1=' . $tracking_number . '&track.x=0&track.y=0';
			break;
			
			case 'FedEx' :
				$url = 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=' . $tracking_number;
			break;
			
			case 'USPS' :
				$url = 'http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=' . $tracking_number;
			break;
		
			default :
				/**
				 * Filter the tracking link for methods that don't exists
				 *
				 * @since 3.0
				 * @param string $tracking_number
				 * @param string $method
				 */
				$url = apply_filters( 'mp_shipping_tracking_link', $tracking_number, $method );
			break;
		}
		
		/**
		 * Filter the tracking link
		 *
		 * @since 3.0
		 * @param string $url
		 * @param string $tracking_number
		 * @param string $method
		 */
		$url = apply_filters( 'mp_order/tracking_link', $url, $tracking_number, $method );
		
		$link = '<a target="_blank" href="' . $url . '">' . __( 'Shipped: Track Shipment', 'mp' ) . '</a>';
		
		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}
	
	/**
	 * Get the order's internal tracking url
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function tracking_url( $echo = true ) {
		$url = trailingslashit( mp_store_page_url( 'order_status', false ) . $this->get_id() );
		
		/**
		 * Filter the tracking URL
		 *
		 * @since 3.0
		 * @access public
		 * @param string $url The tracking URL.
		 */
		$url = apply_filters( 'wpml_marketpress_tracking_url', $url );
		
		/**
		 * Filter the status URL
		 *
		 * @since 3.0
		 * @access public
		 * @param string $url The status URL.
		 * @param MP_Order $this The current order object.
		 */
		$url = apply_filters( 'mp_order/status_url', $url, $this );
		
		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}
}