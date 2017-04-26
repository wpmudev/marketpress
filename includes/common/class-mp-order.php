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
	 *
	 * @param string $name The property name.
	 *
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
	 *
	 * @param int /string/WP_Post $order Optional, either a post id, order id or WP_Post object. If none are provided a new order will be created.
	 */
	public function __construct( $order = null ) {
		if ( ! is_null( $order ) ) {
			if ( $order instanceof WP_Post ) {
				$this->ID = $order->ID;
			} elseif ( is_numeric( $order ) ) {
				$this->ID = $order;
			} else {
				if( ! is_object( $order ) ) {
					$this->_order_id = $order;
				}
			}

			$this->_get_post();
		} else {
			$this->_generate_id();
		}
	}

	/**
	 * Convert legacy cart info from orders created in < 3.0
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param array $items Cart info from an order created in < 3.0.
	 *
	 * @return MP_Cart
	 */
	protected function _convert_legacy_cart( $items ) {
		$cart = new MP_Cart( false );

		if( !empty( $items ) && ( is_array( $items ) || is_object( $items ) ) ) {
			foreach ( $items as $product_id => $variations ) {
				if( !empty( $variations ) && is_array( $variations ) ) {
					foreach ( $variations as $variation_id => $product ) {
						$item = new MP_Product( $product_id );
						$item->set_price( array(
							'regular' => (float) $product['price'],
							'lowest'  => (float) $product['price'],
							'highest' => (float) $product['price'],
							'sale'    => array(
								'amount'     => false,
								'start_date' => false,
								'end_date'   => false,
								'days_left'  => false,
							),
						) );

						if ( isset( $product['download'] ) ) {
							$cart->download_count[ $product_id ] = mp_arr_get_value( 'download->downloaded', $product, 0 );
						}

						$cart->add_item( $product_id, $product['quantity'] );
					}
				}
			}
		}

		return $cart;
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
			$count    = $wpdb->get_var( $wpdb->prepare( "
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
		 *
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
			if ( $_post = wp_cache_get( $this->_order_id, 'mp_order' ) ) {
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
			}
		} else {
			$this->_post = get_post( $this->ID );
		}

		if ( is_null( $this->_post ) ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type != 'mp_order' ) {
			$this->_exists = false;
		} else {
			$this->_exists   = true;
			$this->ID        = $this->_post->ID;
			$this->_order_id = $this->_post->post_name;
			wp_cache_set( $this->_order_id, $this->_post, 'mp_order' );
		}
	}

	/**
	 * Send email to buyers
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param string $subject The email subject text.
	 * @param string $msg The email message text.
	 */
	protected function _send_email_to_buyers( $subject, $msg, $attachments = array() ) {
		$registration_email = mp_get_setting( 'email_registration_email', 0 );
		$current_user = wp_get_current_user();

		if( $registration_email && $current_user->user_email ) {

			mp_send_email( $current_user->user_email, $subject, $msg, $attachments );

		} else {

			$billing_email  = $this->get_meta( 'mp_billing_info->email', '' );
			$shipping_email = $this->get_meta( 'mp_shipping_info->email', '' );

			mp_send_email( $billing_email, $subject, $msg, $attachments );

			$send_shipping_email = apply_filters( 'mp_order/send_shipping_email', $billing_email != $shipping_email, $billing_email, $shipping_email );
			if ( $send_shipping_email ) {
				// Billing email is different than shipping email so let's send an email to the shipping email too
				mp_send_email( $shipping_email, $subject, $msg, $attachments );
			}

		}
	}

	/**
	 * Send new order notifications
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _send_new_order_notifications() {

		// We can't rely on cart's is_digital_only() because we have three scenarios here
		$has_downloads = $has_physical = false;
		$items = $this->get_cart()->get_items_as_objects();
		foreach ( $items as $product ) {
			if( $product->is_download() ) {
				$has_downloads = true;
			} else {
				$has_physical = true;
			}
		}

		$notification_kind = 'new_order';
		if($has_downloads && $has_physical) {
			$notification_kind = 'new_order_mixed';
		} else if( $has_downloads ) {
			$notification_kind = 'new_order_downloads';
		}

		$subject = mp_filter_email( $this, stripslashes( mp_get_setting( 'email->'.$notification_kind.'->subject' ) ) );
		$msg     = mp_filter_email( $this, nl2br( stripslashes( mp_get_setting( 'email->'.$notification_kind.'->text' ) ) ) );

		if ( has_filter( 'mp_order_notification_subject' ) ) {
			//trigger_error( 'The <strong>mp_order_notification_subject</strong> hook has been replaced with <strong>mp_order/notification_subject</strong> as of MP 3.0', E_USER_ERROR );
			error_log( 'The <strong>mp_order_notification_subject</strong> hook has been replaced with <strong>mp_order/notification_subject</strong> as of MP 3.0' );

			return false;
		}

		if ( has_filter( 'mp_order_notification_body' ) ) {
			//trigger_error( 'The <strong>mp_order_notification_body</strong> hook has been replaced with <strong>mp_order/notification_body</strong> as of MP 3.0', E_USER_ERROR );
			error_log( 'The <strong>mp_order_notification_body</strong> hook has been replaced with <strong>mp_order/notification_body</strong> as of MP 3.0' );

			return false;
		}

		if ( has_filter( 'mp_order_notification_' . mp_get_post_value( 'payment_method', '' ) ) ) {
			//trigger_error( 'The <strong>mp_order_notification_' . mp_get_post_value( 'payment_method', '' ) . '</strong> hook has been replaced with <strong>mp_order/notification_body/' . mp_get_post_value( 'payment_method', '' ) . '</strong> as of MP 3.0', E_USER_ERROR );
			error_log( 'The <strong>mp_order_notification_' . mp_get_post_value( 'payment_method', '' ) . '</strong> hook has been replaced with <strong>mp_order/notification_body/' . mp_get_post_value( 'payment_method', '' ) . '</strong> as of MP 3.0' );

			return false;
		}

		/**
		 * Filter the notification subject
		 *
		 * @since 3.0
		 *
		 * @param string $subject The current subject.
		 * @param MP_Order $this The current order object.
		 */
		$subject = apply_filters( 'mp_order/notification_subject', $subject, $this );

		/**
		 * Filter the notification message
		 *
		 * @since 3.0
		 *
		 * @param string $msg The current message.
		 * @param MP_Order $this The current order object.
		 */
		$msg         = apply_filters( 'mp_order/notification_body', $msg, $this );
		$msg         = apply_filters( 'mp_order/notification_body/' . mp_get_post_value( 'payment_method', '' ), $msg, $this );
		$attachments = apply_filters( 'mp_order/sendmail_attachments', array(), $this, 'new_order_client' );
		$this->_send_email_to_buyers( $subject, $msg, $attachments );

		$subject = mp_filter_email( $this, stripslashes( mp_get_setting( 'email->admin_order->subject', __( 'New Order Notification: ORDERID', 'mp' ) ) ) );
		$msg     = mp_filter_email( $this, nl2br( stripslashes( mp_get_setting( 'email->admin_order->text', __( "A new order (ORDERID) was created in your store:\n\n ORDERINFOSKU\n\n SHIPPINGINFO\n\n PAYMENTINFO\n\n", 'mp' ) ) ) ) );

		$subject = apply_filters( 'mp_order_notification_admin_subject', $subject, $this );

		/**
		 * Filter the admin order notification message
		 *
		 * @since 3.0
		 *
		 * @param string $msg
		 * @param MP_Order $order
		 */
		$msg         = apply_filters( 'mp_order_notification_admin_msg', $msg, $this );
		$attachments = apply_filters( 'mp_order/sendmail_attachments', array(), $this, 'new_order_admin' );
		mp_send_email( mp_get_store_email(), $subject, $msg, $attachments );
	}

	/**
	 * Send notification that the order has shipped
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _send_shipment_notification() {

		// We can't rely on cart's is_digital_only() because we have three scenarios here
		$has_downloads = $has_physical = false;
		$items = $this->get_cart()->get_items_as_objects();
		foreach ( $items as $product ) {
			if( $product->is_download() ) {
				$has_downloads = true;
			} else {
				$has_physical = true;
			}
		}

		$notification_kind = 'order_shipped';
		if($has_downloads && $has_physical) {
			$notification_kind = 'order_shipped_mixed';
		} else if( $has_downloads ) {
			$notification_kind = 'order_shipped_downloads';
		}

		$subject = stripslashes( mp_get_setting( 'email->'.$notification_kind.'->subject' ) );
		$msg     = nl2br( stripslashes( mp_get_setting( 'email->'.$notification_kind.'->text' ) ) );

		if ( has_filter( 'mp_shipped_order_notification_subject' ) ) {
			trigger_error( 'The <strong>mp_shipped_order_notification_subject</strong> hook has been replaced with <strong>mp_order/shipment_notification_subject</strong> as of MP 3.0', E_USER_ERROR );
		}

		if ( has_filter( 'mp_shipped_order_notification_body' ) ) {
			trigger_error( 'The <strong>mp_shipped_order_notification_body</strong> hook has been replaced with <strong>mp_order/shipment_notification_body</strong> as of MP 3.0', E_USER_ERROR );
		}

		if ( has_filter( 'mp_shipped_order_notification' ) ) {
			trigger_error( 'The <strong>mp_shipped_order_notification</strong> hook has been replaced with <strong>mp_order/shipment_notification</strong> as of MP 3.0', E_USER_ERROR );
		}

		/**
		 * Filter the shipment notification subject
		 *
		 * @since 3.0
		 *
		 * @param string $subject The email subject.
		 * @param MP_Order $this The current order object.
		 */
		$subject = apply_filters( 'mp_order/shipment_notification_subject', $subject, $this );
		$subject = mp_filter_email( $this, $subject );

		/**
		 * Filter the shipment notification body before string replacements happen
		 *
		 * @since 3.0
		 *
		 * @param string $msg The email message.
		 * @param MP_Order $this The current order object.
		 */
		$msg = apply_filters( 'mp_order/shipment_notification_body', $msg, $this );
		$msg = mp_filter_email( $this, $msg );

		/**
		 * Filter the shipment notification body after string replacements happen
		 *
		 * @since 3.0
		 *
		 * @param string $msg The email message.
		 * @param MP_Order $this The current order object.
		 */
		$msg         = apply_filters( 'mp_order/shipment_notification', $msg, $this );
		$attachments = apply_filters( 'mp_order/sendmail_attachments', array(), $this, 'order_shipped_client' );
		$this->_send_email_to_buyers( $subject, $msg, $attachments );
	}

	/**
	 * Change the order status
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $status The new order status.
	 * @param bool $update_post Whether to update the post status or not.
	 */
	public function change_status( $status, $update_post = false, $old_status = "" ) {
		$cache_key = 'order_status_changed_' . $status . '_' . $this->ID;
		if ( wp_cache_get( $cache_key, 'mp_order' ) ) {
			// Order status already updated - bail
			return;
		}
		wp_cache_set( $cache_key, true, 'mp_order' );

		$action = "mp_order_{$status}";

		if ( false === strpos( $status, 'order_' ) && 'trash' != $status ) {
			$status = 'order_' . $status;
		}

		// Increase sale if order come from trash
		// Check if order was trashed after the 'decrease_sales' fix
		// Prevent old trashed sales from being increased when restoring the order
		$sales_decreased = get_post_meta( $this->ID, 'mp_sales_decreased', true );
		if( $old_status == 'trash' && $sales_decreased == '1' )
			$this->increase_sales();

		switch ( $status ) {
			case 'order_received' :
				add_post_meta( $this->ID, 'mp_received_time', time(), true );
				break;

			case 'order_paid' :
				add_post_meta( $this->ID, 'mp_paid_time', time(), true );
				// As soon as a downloads-only order is paid... its "shipped"
				if ( $this->get_cart()->is_download_only() && $old_status != 'order_shipped' ) {
					$this->_send_shipment_notification();
				}
				break;

			case 'order_shipped' :
				add_post_meta( $this->ID, 'mp_shipped_time', time(), true );
				// Downloads-only orders should not reach the "order_shipped", but if it does (manually set) then
				// we must send the shipped notification only if the previous state is different than "order_paid",
				// because that's the default last-state for download orders and a notification should be sent before
				if ( ! $this->get_cart()->is_download_only() || $old_status != 'order_paid' ) {
					$this->_send_shipment_notification();
				}
				break;

			case 'order_closed' :
				add_post_meta( $this->ID, 'mp_closed_time', time(), true );
				break;

			case 'trash' :
				add_post_meta( $this->ID, 'mp_trashed_time', time(), true );
				update_post_meta( $this->ID, 'mp_sales_decreased' , 1 , true );
				$this->decrease_sales();
				$action = 'mp_order_trashed';
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
		 *
		 * @param MP_Order $this The current order object.
		 */
		do_action( $action, $this );

		// Update the order status
		if ( $update_post ) {
			wp_update_post( array(
				'ID'          => $this->ID,
				'post_status' => $status,
			) );
		}
	}

	/**
	 * Delete meta data
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $key_string The meta key to delete (e.g. meta_name->key1->key2)
	 */
	public function delete_meta( $key_string ) {
		$keys     = explode( '->', $key_string );
		$meta_key = array_shift( $keys );
		$meta     = get_post_meta( $this->ID, $meta_key, true );

		if ( count( $keys ) > 0 ) {
			mp_delete_from_array( $meta, implode( '->', $keys ) );
			update_post_meta( $this->ID, $meta_key, $meta );
		} else {
			delete_post_meta( $this->ID, $meta_key );
		}
	}

	/**
	 * Display the order details
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function details( $echo = true ) {

		$cart = $this->get_cart();

		$currency = $this->get_meta( 'mp_payment_info->currency', '' );
		$cart     = $this->get_meta( 'mp_cart_items' );
		if ( ! $cart ) {
			$cart = $this->get_meta( 'mp_cart_info' );
		}
		/**
		 * Filter the confirmation text
		 *
		 * @since 3.0
		 *
		 * @param string The current confirmation text.
		 * @param MP_Order The order object.
		 */
		$confirmation_text = apply_filters( 'mp_order/confirmation_text', '', $this );
		$confirmation_text = apply_filters( 'mp_order/confirmation_text/' . $this->get_meta( 'mp_payment_info->gateway_plugin_name' ), $confirmation_text, $this );

		$cart_contents = '';
		ob_start();
		?>
		<?php if ( is_array( $cart ) ): ?>
			<?php foreach ( $cart as $product_id => $items ): ?>
				<?php foreach ( $items as $variation_id => $item ): ?>
					<?php $product = ( $variation_id != 0 ) ?  new MP_Product( $variation_id ) : new MP_Product( $product_id );?>
					<div class="mp_cart_item" id="mp-cart-item-104">
						<div class="mp_cart_item_content mp_cart_item_content-thumb"><img
								src="<?php echo $product->image_url( false ) ?>"
								width="75" height="75" style="max-height: 75px;">
						</div>
						<!-- end mp_cart_item_content -->
						<div class="mp_cart_item_content mp_cart_item_content-title">
							<h2 class="mp_cart_item_title">
								<a href="<?php echo $item['url'] ?>"><?php echo $item['name'] ?></a>
							</h2>
							<?php
							$print_download_link = apply_filters( 'mp_order/print_download_link', $product->is_download() && mp_is_shop_page( 'order_status' ), $product, $product_id );
							if ( $print_download_link ) {

								//Handle multiple files
								$download_url = $product->download_url( get_query_var( 'mp_order_id' ), false );
								
								if ( is_array( $download_url ) ){
									//If we have more than one product file, we loop and add each to a new line
									foreach ( $download_url as $key => $value ){
										echo '<a target="_blank" href="' . $value . '">' . sprintf( __( 'Download %1$s', 'mp' ),( $key+1 ) ) . '</a><br/>';
									}

								} else {
									echo '<a target="_blank" href="' . $product->download_url( get_query_var( 'mp_order_id' ), false ) . '">' . __( 'Download', 'mp' ) . '</a>';
								}
							}
							?>
						</div>
						<!-- end mp_cart_item_content -->
						<div class="mp_cart_item_content mp_cart_item_content-price"><!-- MP Product Price -->
							<div class="mp_product_price" itemtype="http://schema.org/Offer" itemscope=""
							     itemprop="offers">
									<span class="mp_product_price-normal"
									      itemprop="price"><?php echo mp_format_currency( '', $item['price'] ) ?></span>
							</div>
							<!-- end mp_product_price -->
						</div>
						<!-- end mp_cart_item_content -->
						<div
							class="mp_cart_item_content mp_cart_item_content-qty"><?php echo $item['quantity'] ?>
						</div>
						<!-- end mp_cart_item_content --></div><!-- end mp_cart_item -->
				<?php endforeach; ?>
			<?php endforeach; ?>
		<?php else: ?>
			<?php
			$cart->display( array(
				'echo'     => true,
				'view'     => 'order-status',
				'editable' => false,
			) );
			?>
		<?php endif; ?>
		<?php

		$cart_contents = ob_get_clean();

		$html = '
			<!-- MP Single Order Details -->
			<section id="mp-single-order-details" class="mp_orders">
				<div class="mp_order_details">
					<div class="mp_order">' .
		        $this->header( false ) .
		        '</div><!-- end mp_order -->';
		if( ! empty( $confirmation_text ) ){
			$html .= '
				<div class="mp_order_confirmation_text">' .
		        	$confirmation_text .
		        '</div><!-- end mp_order_confirmation_text -->';
		}

		$html .= '
				<div class="mp_order_cart">' .
		        $cart_contents . '
					</div><!-- end mp_order_cart -->
					<div class="mp_order_address">' .
		        $this->get_addresses() . '
					</div><!-- end mp_order_address -->
				</div><!-- end mp_order_details -->
			</section><!-- end mp-single-order-details -->';

		/**
		 * Filter the order details
		 *
		 * @since 3.0
		 *
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
	 * Get an address html
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $type Either "billing" or "shipping".
	 * @param bool $editable Optional, whether the address fields should be editable. Defaults to false.
	 *
	 * @return string
	 */
	public function get_address( $type, $editable = false, $product_type = false ) {
		$states        = mp_get_states( $this->get_meta( "mp_{$type}_info->country" ) );
		$all_countries = mp_countries();

		if ( ! $editable ) {

			if( $product_type == 'digital' ) {
				$html = '' .
			        $this->get_name( $type ) . '<br />' .
					( ( $company_name = $this->get_meta( "mp_{$type}_info->company_name", '' ) ) ? $company_name . '<br />' : '' ) .
			        ( ( $phone = $this->get_meta( "mp_{$type}_info->phone", '' ) ) ? $phone . '<br />' : '' ) .
			        ( ( $email = $this->get_meta( "mp_{$type}_info->email", '' ) ) ? '<a href="mailto:' . antispambot( $email ) . '">' . antispambot( $email ) . '</a><br />' : '' );
			} else {
				$html = '' .
			        $this->get_name( $type ) . '<br />' .
					( ( $company_name = $this->get_meta( "mp_{$type}_info->company_name", '' ) ) ? $company_name . '<br />' : '' ) .
			        $this->get_meta( "mp_{$type}_info->address1", '' ) . '<br />' .
			        ( ( $address2 = $this->get_meta( "mp_{$type}_info->address2", '' ) ) ? $address2 . '<br />' : '' ) .
			        ( ( $city = $this->get_meta( "mp_{$type}_info->city", '' ) ) ? $city : '' ) .
			        ( ( ( $state = $this->get_meta( "mp_{$type}_info->state", '' ) ) && is_array( $states ) && isset( $states[$state] ) ) ? ', ' . $states[$state] . ' ' : ', ' ) .
			        ( ( $zip = $this->get_meta( "mp_{$type}_info->zip", '' ) ) ? $zip . '<br />' : '' ) .
					( ( ( $country = $this->get_meta( "mp_{$type}_info->country", '' ) ) && is_array( $all_countries ) && isset( $all_countries[$country] ) ) ? $all_countries[$country] . '<br />' : '' ) .
			        ( ( $phone = $this->get_meta( "mp_{$type}_info->phone", '' ) ) ? $phone . '<br />' : '' ) .
			        ( ( $email = $this->get_meta( "mp_{$type}_info->email", '' ) ) ? '<a href="mailto:' . antispambot( $email ) . '">' . antispambot( $email ) . '</a><br />' : '' );
			}
                        if ( $this->get_meta( 'mp_' . $type . '_info->special_instructions' ) ) {
				$html .= wordwrap( $this->get_meta( "mp_{$type}_info->special_instructions" ) ) . '<br />';
			}
		} else {
			$prefix = 'mp[' . $type . '_info]';

			$allowed_countries = mp_get_setting( 'shipping->allowed_countries', '' );

			// Country dropdown
			if( ! is_array( $allowed_countries ) ) {
				$allowed_countries = explode( ',', $allowed_countries );
			}

			$country_options   = '';

			if ( mp_all_countries_allowed() ) {
				$allowed_countries = array_keys( $all_countries );
			}

			foreach ( $allowed_countries as $country ) {
				$country_options .= '<option value="' . $country . '" ' . selected( $country, $this->get_meta( "mp_{$type}_info->country", '' ), false ) . '>' . $all_countries[ $country ] . '</option>' . "\n";
			}

			// State dropdown
			$state_options = '';
			if ( is_array( $states ) ) {
				foreach ( $states as $key => $val ) {
					$state_options .= '<option value="' . $key . '" ' . selected( $key, $this->get_meta( "mp_{$type}_info->state", '' ), false ) . '>' . $val . '</option>' . "\n";
				}
			}

			$html = '';

			$html .= '
				<table class="form-table">
					<tr>
						<th scope="row">' . __( 'First Name', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[first_name]" value="' . $this->get_meta( "mp_{$type}_info->first_name", '' ) . '" /></td>
					</tr>
					<tr>
						<th scope="row">' . __( 'Last Name', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[last_name]" value="' . $this->get_meta( "mp_{$type}_info->last_name", '' ) . '" /></td>
					</tr>
					<tr>
						<th scope="row">' . __( 'Company', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[company_name]" value="' . $this->get_meta( "mp_{$type}_info->company_name", '' ) . '" /></td>
					</tr>';
			if( $product_type != 'digital' ) {
				$html .= '
					<tr>
						<th scope="row">' . __( 'Address 1', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[address1]" value="' . $this->get_meta( "mp_{$type}_info->address1", '' ) . '" /></td>
					</tr>
					<tr>
						<th scope="row">' . __( 'Address 2', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[address2]" value="' . $this->get_meta( "mp_{$type}_info->address2", '' ) . '" /></td>
					</tr>
					<tr>
						<th scope="row">' . __( 'City', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[city]" value="' . $this->get_meta( "mp_{$type}_info->city", '' ) . '" /></td>
					</tr>';

				if ( is_array( $states ) ) {
					$html .= '
						<tr>
							<th scope="row">' . __( 'State', 'mp' ) . '</th>
							<td>
								<select class="mp-select2" name="' . $prefix . '[state]" style="width:100%">' . $state_options . '</select>
								<img src="' . admin_url( 'images/wpspin_light.gif' ) . '" alt="" style="display:none">
							</td>
						</tr>';
				}

				$html .= '
						<tr>
							<th scope="row">' . mp_get_setting( 'zip_label' ) . '</th>
							<td><input type="text" name="' . $prefix . '[zip]" value="' . $this->get_meta( "mp_{$type}_info->zip", '' ) . '"></td>
						</tr>
						<tr>
							<th scope="row">' . __( 'Country', 'mp' ) . '</th>
							<td><select class="mp-select2" name="' . $prefix . '[country]" style="width:100%">' . $country_options . '</select></td>
						</tr>';

			}

			$html .= '
					<tr>
						<th scope="row">' . __( 'Phone', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[phone]" value="' . $this->get_meta( "mp_{$type}_info->phone", '' ) . '"></td>
					</tr>
					<tr>
						<th scope="row">' . __( 'Email', 'mp' ) . '</th>
						<td><input type="text" name="' . $prefix . '[email]" value="' . $this->get_meta( "mp_{$type}_info->email", '' ) . '"></td>
					</tr>';
			if ( $this->get_meta( 'mp_' . $type . '_info->special_instructions' ) ) {
				$html .= '<tr>
						<th scope="row">' . __( 'Special Instructions', 'mp' ) . '</th>
						<td><textarea name="' . $prefix . '[special_instructions]">' . $this->get_meta( "mp_{$type}_info->special_instructions", '' ) . '</textarea></td>
					</tr>';
			}
			$html .= '
				</table>';
		}

		/**
		 * Filter the address html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current address html.
		 * @param string $type Either "billing" or "shipping".
		 * @param MP_Order $this The current order object.
		 */

		return apply_filters( 'mp_order/get_address', $html, $type, $this );
	}

	/**
	 * Get billing/shipping addresses
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $editable Optional, whether the address fields should be editable. Defaults to false.
	 */
	public function get_addresses( $editable = false ) {

		$html = '<div class="mp_customer_address">';

		if ( $this->get_cart()->is_download_only() && mp_get_setting( 'details_collection' ) == "contact" ) {
			$html .= '
				<div class="mp_content_col mp_content_col-one-half">
					<h4 class="mp_sub_title">' . __( 'Contact Details', 'mp' ) . '</h4>' .
					$this->get_address( 'billing', $editable, 'digital' ) .
					'</div>';
		} else {
			$html .= '
				<div class="mp_content_col mp_content_col-one-half">
					<h4 class="mp_sub_title">' . __( 'Billing Address', 'mp' ) . '</h4>' .
					$this->get_address( 'billing', $editable ) .
					( ( $editable ) ? '<p><a class="button" id="mp-order-copy-billing-address" href="javascript:;">' . __( 'Copy billing address to shipping address', 'mp' ) . '</a>' : '' ) . '
					</div>';
		}
		if ( ! $this->get_cart()->is_download_only() ) {
			$html .= '
				<div class="mp_content_col mp_content_col-one-half">
					<h4 class="mp_sub_title">' . __( 'Shipping Address', 'mp' ) . '</h4>' .
			         $this->get_address( 'shipping', $editable ) . '';

			$html .= '
				</div>';
		}

		$html .= '
		</div><!-- end mp_customer_address -->';

		/**
		 * Filter the addresses html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current address html.
		 * @param MP_Order $this The current order object.
		 */

		return apply_filters( 'mp_order/get_addresses', $html, $this );
	}

	/**
	 * Get the cart object from meta data
	 *
	 * @since 3.0
	 * @access public
	 * @return MP_Cart
	 */
	public function get_cart() {
		$cart = $this->get_meta( 'mp_cart_info' );

		if ( ! $cart instanceof MP_Cart ) {
			$cart = $this->_convert_legacy_cart( $cart );
			$this->update_meta('mp_cart_info', $cart);
		}

		return apply_filters( 'mp_order/get_cart', $cart, $this );
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
	 *
	 * @param string $name The name of the meta to retrieve.
	 *
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
	 *
	 * @param string $type Optional, either "shipping" or "billing". Defaults to "billing".
	 */
	public function get_name( $type = 'billing' ) {
		$fullname = $this->get_meta( "mp_{$type}_info->name" );
		if ( empty( $fullname ) ) {
			$fullname = $this->get_meta( "mp_shipping_info->name" );
		}

		return ( ! empty( $fullname ) ) ? $fullname : $this->get_meta( "mp_{$type}_info->first_name" ) . ' ' . $this->get_meta( "mp_{$type}_info->last_name" );
	}

	/**
	 * Display the order header
	 *
	 * @since 3.0
	 * @access public
	 */
	public function header( $echo = true ) {
		$html = '
			<h3 class="mp_order_head">' . __( 'Order #', 'mp' ) . ' ' . ( ( get_query_var( 'mp_order_id' ) ) ? $this->get_id() : '<a href="' . $this->tracking_url( false ) . '">' . $this->get_id() . '</a>' ) . '</h3>
			<div class="mp_order_detail" id="mp-order-detail-' . $this->ID . '">';

		// Currency
		$currency = $this->get_meta( 'mp_payment_info->currency', '' );

		// Cart
		$cart = $this->get_meta( 'mp_cart_info' );
		$is_download_only = false;

		if ( $cart instanceof MP_Cart ) {
			$tax_total      = $cart->tax_total( true );
			$shipping_total = $cart->shipping_total( true );
			$is_download_only = $cart->is_download_only();
		} else {
			$tax_total      = mp_format_currency( $currency, $this->get_meta( 'mp_tax_total', 0 ) );
			$shipping_total = mp_format_currency( $currency, $this->get_meta( 'mp_shipping_total', 0 ) );
		}

		// Currency
		$currency = $this->get_meta( 'mp_payment_info->currency', '' );

		// Received time
		$received = ( $time = $this->get_meta( 'mp_received_time' ) ) ? mp_format_date( $time, true ) : false;

		// Status
		$status       = __( 'Received', 'mp' );
		$status_extra = '';
		switch ( $this->_post->post_status ) {
			case 'order_shipped' :
				if( $is_download_only ) {
					$status = __( 'Finished', 'mp' );
				} else {
					$status = __( 'Shipped', 'mp' );
				}

				if ( $tracking_num = $this->get_meta( 'mp_shipping_info->tracking_num' ) ) {
					$status = $this->tracking_link( false );
				}
				break;

			case 'order_paid' :
				if( $is_download_only ) {
					$status = __( 'Finished', 'mp' );
				} else {
					$status = __( 'In Process', 'mp' );
				}
				break;

			case 'order_closed' :
				$status = __( 'Closed', 'mp' );
				break;
		}

		$tooltip_content = '
			<div class="mp_tooltip_content_item">
				<div class="mp_tooltip_content_item_label">' . __( 'Taxes:', 'mp' ) . '</div><!-- end mp_tooltip_content_item_label -->
				<div class="mp_tooltip_content_item_value">' . $tax_total . '</div><!-- end mp_tooltip_content_item_value -->
			</div><!-- end mp_tooltip_content_item -->';

		if( ! $this->get_cart()->is_download_only() ) {
			$tooltip_content .= '
				<div class="mp_tooltip_content_item">
					<div class="mp_tooltip_content_item_label">' . __( 'Shipping:', 'mp' ) . '</div><!-- end mp_tooltip_content_item_label -->
					<div class="mp_tooltip_content_item_value">' . $shipping_total . '</div><!-- end mp_tooltip_content_item_value -->
				</div><!-- end mp_tooltip_content_item -->
			';
		}

		/**
		 * Filter the order total tooltip content
		 *
		 * @since 3.0
		 *
		 * @param string $tooltip_content
		 * @param MP_Order $this The current order object.
		 */
		$tooltip_content = apply_filters( 'mp_order/tooltip_content_total', $tooltip_content, $this );

		$html .= '
			<div class="mp_order_detail_item"><h5>' . __( 'Order Received', 'mp' ) . '</h5> <span>' . $received . '</span></div><!-- end mp_order_detail_item -->
			<div class="mp_order_detail_item"><h5>' . __( 'Current Status', 'mp' ) . '</h5> <span>' . $status . '</span></div><!-- end mp_order_detail_item -->
			<div class="mp_order_detail_item">
				<h5>' . __( 'Total', 'mp' ) . '</h5>
				<a href="javascript:;" class="mp_tooltip">' . mp_format_currency( $currency, $this->get_meta( 'mp_order_total', '' ) ) . '</a>
				<div class="mp_tooltip_content">
					' . $tooltip_content . '
				</div><!-- end mp_tooltip_content -->
			</div><!-- end mp_order_detail_item -->';

		$html .= '
			</div><!-- end mp_order_detail -->';

		/**
		 * Filter the order header html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current order header html.
		 * @param MP_Order $this The current order object.
		 */
		$html = apply_filters( 'mp_order/header', $html, $this );

		$tracked = $this->get_meta( 'mp_ga_tracked' );

		if( !$tracked ) {
			$html .= mp_checkout()->create_ga_ecommerce(get_query_var( 'mp_order_id' ));
			add_post_meta( $this->ID, 'mp_ga_tracked', true, true );
		}

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Log IPN history status
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $status The status text to log.
	 */
	public function log_ipn_status( $status ) {
		wp_insert_comment( array(
			'comment_post_ID' => $this->ID,
			'comment_type'    => 'comment',
			'comment_content' => esc_html( $status ),
		) );
	}

	/**
	 * Save the order to the database
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $args {
	 *        An array of arguments
	 *
	 * @type MP_Cart $cart Required, a MP_Cart object.
	 * @type array $payment_info Required, an array of payment payment info.
	 * @type array $billing_info Optional, an array of billing info. Defaults to the current user billing info.
	 * @type array $shipping_info Optional, an array of shipping info. Defaults to the current user shipping info.
	 * @type bool $paid Optional, whether the order is paid or not. Defaults to false.
	 * @type int $user_id Optional, the user id for the order. Defaults to the current user.
	 * @type float $shipping_total Optional, the shipping total. Defaults to the calculated total.
	 * @type float $shipping_tax_total Optional, the tax amount for shipping. Defaults to the calculated total.
	 * @type float $tax_total Optional, the tax total. Defaults to the calculated total.
	 */
	public function save( $args ) {
		/**
		 * store the current blog id, after the mp_cart() running shipping_total,shipping_tax_total & tax_total
		 * the blog id will revert the main, in some case this is a bug
		 */
		$current_blog_id = get_current_blog_id();

		$args = array_replace_recursive( array(
			'cart'               => null,
			'payment_info'       => null,
			'billing_info'       => mp_get_user_address( 'billing' ),
			'shipping_info'      => mp_get_user_address( 'shipping' ),
			'paid'               => false,
			'user_id'            => get_current_user_id(),
			'shipping_total'     => mp_cart()->shipping_total( false ),
			'shipping_tax_total' => mp_cart()->shipping_tax_total( false ),
			'tax_total'          => mp_cart()->tax_total( false ),
		), $args );

		extract( $args );

		/**
		 * revert back to the current cart
		 * todo check if the single cart got ths bug too
		 */
		if ( mp_cart()->is_global ) {
			switch_to_blog( $current_blog_id );
		}

		// Check required fields
		if ( is_null( $cart ) || is_null( $payment_info ) ) {
			return false;
		}

		// Create new post
		$post_id = wp_insert_post( array(
			'post_title'   => $this->get_id(),
			'post_name'    => $this->get_id(),
			'post_content' => serialize( $cart->get_items() ) . serialize( $shipping_info ) . serialize( $billing_info ),
			// this is purely for search capabilities
			'post_status'  => ( $paid ) ? 'order_paid' : 'order_received',
			'post_type'    => 'mp_order',
		) );

		// Set the internal post object in case we need to use it right away
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->ID = $post_id;
		$this->_get_post();

		$items = $cart->get_items_as_objects();
		foreach ( $items as &$_item ) {
			/* make sure price is saved to product object so when retrieved later the
			  correct price is returned */
			$_item->get_price();
		}

		$order_shipping = mp_get_post_value( 'shipping' );
		if( isset( $order_shipping['special_instructions'] ) ){
			$shipping_info['special_instructions'] = $order_shipping['special_instructions'];
		}

		// Save cart info
		update_post_meta( $this->ID, 'mp_cart_info', $cart );
		update_post_meta( $this->ID, 'mp_cart_items', $cart->export_to_array() );
		// Save shipping info
		update_post_meta( $this->ID, 'mp_shipping_info', $shipping_info );
		// Save billing info
		update_post_meta( $this->ID, 'mp_billing_info', $billing_info );
		// Save payment info
		update_post_meta( $this->ID, 'mp_payment_info', $payment_info );
		// Save kind of user, because author_id is reset in subsecuent order edits
		update_post_meta( $this->ID, 'mp_user_kind', ( 0 === get_current_user_id() ? 'guest' : 'registered' ) );

		// Update user shipping billing info
		if ( $user_id ) {
			if ( get_user_meta( $user_id, 'mp_billing_info' ) ) {
				update_user_meta( $user_id, 'mp_billing_info', $billing_info );
				update_user_meta( $user_id, 'mp_shipping_info', $shipping_info );
			} else {
				/**
				 * First time save, WordPress will trigger an error as when it query the old meta
				 * the count('') == 1, so it trying to located the 0 index, which can cause order uncomplete
				 * force to silent
				 */
				@update_user_meta( $user_id, 'mp_billing_info', $billing_info );
				@update_user_meta( $user_id, 'mp_shipping_info', $shipping_info );
			}
		}

		$item_count = 0;

		foreach ( $items as $item ) {
			$item_count += $item->qty;

			if ( $item->get_meta( 'inventory_tracking' ) ) {
				$stock = $item->get_stock();

				// Update inventory
				$new_stock = ( $stock - $item->qty );

				$item->update_meta( 'inventory', $new_stock );
				$item->update_meta( 'inv_inventory', $new_stock );

				// Send low-stock notification if needed
				if ( $new_stock <= mp_get_setting( 'inventory_threshhold' ) ) {
					$item->low_stock_notification();
				}

				if ( mp_get_setting( 'inventory_remove' ) && $new_stock <= 0 ) {
					// Flag product as out of stock - @version 2.9.5.8
					wp_update_post( array(
						'ID'          => $item->ID,
						'post_status' => 'draft'
					) );
				}
			}

			// Update sales count
			$count = $item->get_meta( 'mp_sales_count', 0 );

			$count += $item->qty;

			//$item->update_meta( 'mp_sales_count', $count );
			update_post_meta( $item->ID, 'mp_sales_count', $count );

			if ( has_filter( 'mp_product_sale' ) ) {
				trigger_error( 'The <strong>mp_product_sale</strong> hook has been replaced by <strong>mp_order/product_sale</strong> as of MP 3.0.', E_USER_ERROR );
			}

			/**
			 * Fires after the sale of a product during checkout
			 *
			 * @since 3.0
			 *
			 * @param MP_Product $item The product that was sold.
			 * @param bool $paid Whether the associated order has been paid.
			 * @param int $order_id The order ID.
			 */
			do_action( 'mp_checkout/product_sale', $item, $paid, $this->get_id() );
		}

		// Payment info
		update_post_meta( $this->ID, 'mp_order_total', mp_arr_get_value( 'total', $payment_info ) );

		// Shipping totals
		update_post_meta( $this->ID, 'mp_shipping_total', $shipping_total );

		// Taxes
		update_post_meta( $this->ID, 'mp_shipping_tax', $shipping_tax_total );
		update_post_meta( $this->ID, 'mp_tax_total', $tax_total );
		update_post_meta( $this->ID, 'mp_tax_inclusive', mp_get_setting( 'tax->tax_inclusive' ) );
		update_post_meta( $this->ID, 'mp_tax_shipping', mp_get_setting( 'tax->tax_shipping' ) );

		// Number of items ordered
		update_post_meta( $this->ID, 'mp_order_items', $item_count );

		// Order time
		update_post_meta( $this->ID, 'mp_received_time', time() );

		// If applicable, update order status to paid
		if ( $paid ) {
			$this->change_status( 'order_paid', true );
		}

		// Update order history
		$orders    = mp_get_order_history( $user_id );
		$new_order = array(
			'id'    => $this->ID,
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
			$timestamp            = time();
			$orders[ $timestamp ] = $new_order;
			update_user_meta( $user_id, $key, $orders );
		} else {
			// Save to cookie
			$timestamp            = time();
			$orders[ $timestamp ] = $new_order;
			$expire               = time() + 31536000; // 1 year expire
			setcookie( $key, serialize( $orders ), $expire, COOKIEPATH, COOKIE_DOMAIN );
		}

		if ( has_filter( 'mp_new_order' ) ) {
			trigger_error( 'The <strong>mp_new_order</strong> hook has been replaced by <strong>mp_order/new_order</strong> as of MP 3.0.', E_USER_ERROR );
		}

		/**
		 * Fires when an order is created
		 *
		 * @since 3.0
		 *
		 * @param MP_Order $this The current order object.
		 */
		do_action( 'mp_order/new_order', $this );
		do_action( 'mp_product_created', $this->ID );//support for older integrations

		// Empty cart
		$cart->empty_cart();

		// Remove session variables
		if ( mp_get_session_value( 'mp_shipping_info' ) ) {
			foreach ( $_SESSION['mp_shipping_info'] as $key => $val ) {
				switch ( $key ) {
					case 'shipping_option' :
					case 'shipping_sub_option' :
					case 'shipping_cost' :
						unset( $_SESSION[ $key ] );
						break;
				}
			}
		}

		// Send new order email
		$this->_send_new_order_notifications();

		// If paid and the cart is only digital products mark it shipped
		if ( $paid && $cart->is_download_only() ) {
			$this->change_status( 'order_shipped', true );
		}

		// Cache the ID for later use
		wp_cache_set( 'order_object', $this, 'mp' );
	}

	/**
	 * Decrease Sales
	 *
	 * @since 3.0
	 * @access public
	 *
	 */
	public function decrease_sales(  ) {
		$items = $this->get_cart()->get_items_as_objects();

		foreach ( $items as $item ) {
			// Decrease sales count
			$count = $item->get_meta( 'mp_sales_count', 0 );
			$count = ($count - $item->qty > 0) ? $count - $item->qty : 0   ;
			update_post_meta( $item->ID, 'mp_sales_count', $count );
		}
	}

	/**
	 * Increase Sales
	 *
	 * @since 3.0
	 * @access public
	 *
	 */
	public function increase_sales(  ) {
		$items = $this->get_cart()->get_items_as_objects();

		foreach ( $items as $item ) {
			// Decrease sales count
			$count = $item->get_meta( 'mp_sales_count', 0 );
			$count += $item->qty;
			update_post_meta( $item->ID, 'mp_sales_count', $count );
		}
	}

	/**
	 * Get the order's shipment tracking url
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function tracking_link( $echo = true ) {
		$tracking_number = esc_attr( $this->get_meta( 'mp_shipping_info->tracking_num' ) );
		$method          = $this->get_meta( 'mp_shipping_info->method' );
		$tracking_link	 = $this->get_meta( 'mp_shipping_info->tracking_link' );

		if( ! empty( $tracking_link ) ) {
			$url =  $tracking_link;
		} else {
			switch ( strtoupper( $method ) ) {
				case 'UPS' :
					$url = 'http://wwwapps.ups.com/WebTracking/processInputRequest?sort_by=status&tracknums_displayed=1&TypeOfInquiryNumber=T&loc=en_us&InquiryNumber1=' . $tracking_number . '&track.x=0&track.y=0';
					break;

				case 'FedEx' :
					$url = 'http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=' . $tracking_number;
					break;

				case 'USPS' :
					$url = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_number;
					break;

				case 'DHL' :
					$url = 'http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB=' . $tracking_number;
					break;

				default :
					/**
					 * Filter the tracking link for methods that don't exists
					 *
					 * @since 3.0
					 *
					 * @param string $tracking_number
					 * @param string $method
					 */
					$url = apply_filters( 'mp_shipping_tracking_link', $tracking_number, $method );
					break;
			}
		}

		/**
		 * Filter the tracking link
		 *
		 * @since 3.0
		 *
		 * @param string $url
		 * @param string $tracking_number
		 * @param string $method
		 */
		$url = apply_filters( 'mp_order/tracking_link', $url, $tracking_number, $method );

		// At this point, if method is custom and $url was empty and no filters has been added then $url should be equal at $tracking_number

		if( $url == $tracking_number ) {
			$link = '<span>' . sprintf(__( 'Shipped: tracking code: %s', 'mp' ), $tracking_number ) . '</a>';
		} else {
			$link = '<a target="_blank" href="' . $url . '">' . __( 'Shipped: Track Shipment', 'mp' ) . '</a>';
		}

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
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function tracking_url( $echo = true, $blog_id = false ) {

		if( $blog_id !== false ) {
			$current_blog_id = get_current_blog_id();
			switch_to_blog( $blog_id );
		}

		$url = trailingslashit( mp_store_page_url( 'order_status', false ) . $this->get_id() );

		$user_id = get_current_user_id();

		// Append the email to the tracking URL for orders made by guest users (hashed so it's not sent directly in the URL)
		if( 'guest' === $this->get_meta( 'mp_user_kind', '' ) ) {
			$url .= md5( $this->get_meta( 'mp_billing_info->email', '' ) );
		}

		/**
		 * Filter the tracking URL
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param string $url The tracking URL.
		 */
		$url = apply_filters( 'wpml_marketpress_tracking_url', $url );

		/**
		 * Filter the status URL
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param string $url The status URL.
		 * @param MP_Order $this The current order object.
		 */
		$url = apply_filters( 'mp_order/status_url', $url, $this );

		if( $blog_id !== false ) {
			switch_to_blog( $current_blog_id );
		}

		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}

	/**
	 * Update meta data
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $key The meta key to update (e.g. meta_name->key1->key2)
	 * @param mixed $value
	 */
	public function update_meta( $key, $value ) {
		$keys     = explode( '->', $key );
		$meta_key = array_shift( $keys );
		$meta     = get_post_meta( $this->ID, $meta_key, true );

		if ( count( $keys ) > 0 ) {
			mp_push_to_array( $meta, implode( '->', $keys ), $value );
			update_post_meta( $this->ID, $meta_key, $meta );
		} else {
			update_post_meta( $this->ID, $meta_key, $value );
		}
	}

}
