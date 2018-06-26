<?php
/**
 * Class responsible for GDPR compliance.
 *
 * Class MP_GDPR
 *
 * @since 3.2.9
 *
 * @package Marketpress
 */


/**
 * Class MP_GDPR is responsible for GDPR compliance
 *
 * @since 3.2.9
 */
class MP_GDPR {

	/**
	 * Singleton class instance.
	 *
	 * @var MP_GDPR|null
	 */
	private static $_instance = null;

	/**
	 * Get class instance.
	 *
	 * @return MP_GDPR|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_GDPR();
		}

		return self::$_instance;
	}

	/**
	 * MP_GDPR constructor.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks and filters related to GDPR.
	 */
	private function register_hooks() {
		// Register private policy text.
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );

		// Register data exporter provider.
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );

		// Register data eraser provider.
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ), 10 );
	}

	/**
	 * Register private policy text.
	 */
	public function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf( '<h3>%s</h3><p>%s</p><h3>%s</h3><p>%s</p><h3>%s</h3><p>%s</p>',
			__( 'Third parties', 'mp' ),
			__( 'This site might be using third party services and tools to process payments or/and
		           process shipping data when creating orders on the MarketPress store. These services include payment
		           gateways: 2Checkout, Authorize.net AIM, eWay shared payments, eWay Rapid 3.1 Payments, Mijireh,
		           Mollie, Paymill, PayPal, PIN, Simplify Commerce by MasterCard, Stripe and WePay; shipping gateways:
		           Fedex, UPS, USPS.', 'mp' ),
			__( 'Additional data', 'mp' ),
			__( 'The following information will be collected in order to process orders: your name,
		           username, email address, avatar and profile URLs, address and phone number. This data can be
		           exported and removed.', 'mp' ),
			__( 'Cookies', 'mp' ),
			__( 'In addition to standard WordPress session cookies, this site might be setting an
		           additional cookie to remember your cart settings. This cookie will last for one year.', 'mp' )
		);

		wp_add_privacy_policy_content(
			'MarketPress',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Register export provider.
	 *
	 * @param array $exporters  Array of registered export providers.
	 *
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['mp'] = array(
			'exporter_friendly_name' => __( 'MarketPress User Data', 'mp' ),
			'callback'               => array( $this, 'export_data' ),
		);

		return $exporters;
	}

	/**
	 * Register eraser provider.
	 *
	 * @param array $erasers  Array of registered eraser providers
	 *
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['mp'] = array(
			'eraser_friendly_name' => __( 'MarketPress User Data', 'mp' ),
			'callback'             => array( $this, 'erase_data' ),
		);

		return $erasers;
	}

	/**
	 * Personal data exporter function.
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public function export_data( $email_address, $page = 1 ) {
		$export_items = array();

		// Get customer shipping and billing info.
		$data = $this->get_customer_data( $email_address );
		if ( $data ) {
			$export_items[] = array(
				'group_id'    => 'mp_customer',
				'group_label' => __( 'Customer Data', 'mp' ),
				'item_id'     => 'user',
				'data'        => $data,
			);
		}

		// Get customer orders info.
		$order_data = $this->get_order_data( $email_address );
		if ( $order_data ) {
			foreach ( $order_data as $order ) {
				$export_items[] = array(
					'group_id'    => 'mp_orders',
					'group_label' => __( 'Orders Data', 'mp' ),
					'item_id'     => 'order_' . $order[0]['value'],
					'data'        => $order,
				);
			}
		}

		return array(
			'data' => $export_items,
			'done' => true,
		);
	}

	/**
	 * Erase private data.
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public function erase_data( $email_address, $page = 1 ) {
		$guest = false;
		$result = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$customer = get_user_by( 'email', $email_address );

		if ( ! $customer instanceof WP_User ) {
			global $wpdb;

			$orders = $wpdb->get_results( $wpdb->prepare(
				"SELECT DISTINCT post_id AS id FROM {$wpdb->prefix}postmeta WHERE meta_value LIKE '%%%s%%'",
				$email_address
			), ARRAY_A ); // db-call ok; no-cache ok.

			$guest = true;
		}

		if ( ! $guest ) {
			// Delete shipping and billing meta data for the user.
			delete_user_meta( $customer->ID, 'mp_shipping_info' );
			delete_user_meta( $customer->ID, 'mp_billing_info' );

			// Remove personal data from order.
			$orders = get_user_meta( $customer->ID, 'mp_order_history', true );

		}

		if ( ! isset( $orders ) || ! $orders ) {
			return $result;
		}

		foreach ( $orders as $order ) {
			$order_object = new MP_Order( $order['id'] );

			$new_post_content = serialize( $order_object->get_cart()->get_items() ) . serialize( get_user_meta( $customer->ID, 'mp_shipping_info' ) ) . serialize( get_user_meta( $customer->ID, 'mp_billing_info' ) );

			wp_update_post( array(
				'ID' => $order['id'],
				'post_content' => $new_post_content,
			) );

			update_post_meta( $order['id'], 'mp_shipping_info', '' );
			update_post_meta( $order['id'], 'mp_billing_info', '' );
		}

		$result['items_removed'] = true;
		return $result;
	}

	/**
	 * Get billing and shipping data for the user.
	 *
	 * @param string $email_address
	 *
	 * @return array|bool
	 */
	private function get_customer_data( $email_address ) {
		$customer = get_user_by( 'email', $email_address );

		if ( ! $customer instanceof WP_User ) {
			return false;
		}

		$data = array();

		$metas = array(
			'mp_billing_info' => array(
				'first_name'   => __( 'Billing First Name', 'mp' ),
				'last_name'    => __( 'Billing Last Name', 'mp' ),
				'email'        => __( 'Billing Email', 'mp' ),
				'company_name' => __( 'Billing Company Name', 'mp' ),
				'address1'     => __( 'Billing Address 1', 'mp' ),
				'address2'     => __( 'Billing Address 2', 'mp' ),
				'city'         => __( 'Billing City', 'mp' ),
				'state'        => __( 'Billing State', 'mp' ),
				'zip'          => __( 'Billing ZIP', 'mp' ),
				'country'      => __( 'Billing Country', 'mp' ),
				'phone'        => __( 'Billing Phone', 'mp' ),

			),
			'mp_shipping_info'  => array(
				'first_name'   => __( 'Shipping First Name', 'mp' ),
				'last_name'    => __( 'Shipping Last Name', 'mp' ),
				'email'        => __( 'Shipping Email', 'mp' ),
				'company_name' => __( 'Shipping Company Name', 'mp' ),
				'address1'     => __( 'Shipping Address 1', 'mp' ),
				'address2'     => __( 'Shipping Address 2', 'mp' ),
				'city'         => __( 'Shipping City', 'mp' ),
				'state'        => __( 'Shipping State', 'mp' ),
				'zip'          => __( 'Shipping ZIP', 'mp' ),
				'country'      => __( 'Shipping Country', 'mp' ),
				'phone'        => __( 'Shipping Phone', 'mp' ),

			),
		);

		foreach ( $metas as $meta => $fields ) {
			$values = get_user_meta( $customer->ID, $meta, true );

			if ( ! $values || ! is_array( $values ) ) {
				continue;
			}

			foreach ( $values as $item => $value ) {
				// If no value set or value is not supported - continue.
				if ( empty( $value ) || ! isset( $fields[ $item ] ) ) {
					continue;
				}

				$data[] = array(
					'name'  => $fields[ $item ],
					'value' => $value,
				);
			}
		} // End foreach().

		return $data;
	}

	/**
	 * Get customer orders data.
	 *
	 * @param string $email_address
	 *
	 * @return array|bool
	 */
	private function get_order_data( $email_address ) {
		$guest = false;
		$customer = get_user_by( 'email', $email_address );

		// If guest user - we need to find orders IDs where the email was used.
		if ( ! $customer instanceof WP_User ) {
			global $wpdb;

			$orders = $wpdb->get_results( $wpdb->prepare(
				"SELECT DISTINCT post_id AS id FROM {$wpdb->prefix}postmeta WHERE meta_value LIKE '%%%s%%'",
				$email_address
			), ARRAY_A ); // db-call ok; no-cache ok.

			// No orders - return.
			if ( ! $orders ) {
				return false;
			}

			$guest = true;
		}

		$data = array();

		$order_statuses = array(
			'order_received' => __( 'Received', 'mp' ),
			'order_paid'     => __( 'Paid', 'mp' ),
			'order_shipped'  => __( 'Shipped', 'mp' ),
			'order_closed'   => __( 'Closed', 'mp' ),
		);

		if ( ! $guest ) {
			$orders = get_user_meta( $customer->ID, 'mp_order_history', true );
		}

		// If no orders - return.
		if ( ! isset( $orders ) ) {
			return false;
		}

		foreach ( $orders as $order ) {
			$order_data = get_post( $order['id'] );
			$order_meta = get_post_meta( $order['id'] );

			$items = $order_meta['mp_cart_info'];
			$order_info = '';
			foreach ( $items as $item ) {
				/* @var MP_Cart $item */
				$cart = unserialize( $item )->export_to_array();

				$order_info = '';
				foreach ( $cart as $product ) {
					if ( ! isset( $product[0] ) || ! isset( $product[0]['name'] ) ) {
						continue;
					}

					$order_info .= $product[0]['name'] . ' x ' . $product[0]['quantity'] . '<br>';
				}
			}

			$mp_order = new MP_Order( $order['id'] );

			$data[] = array(
				array(
					'name'  => __( 'Order ID', 'mp' ),
					'value' => $order_data->post_title,
				),
				array(
					'name'  => __( 'Order Date', 'mp' ),
					'value' => date('Y-m-d H:i', $order_meta['mp_received_time'][0] ),
				),
				array(
					'name'  => __( 'Order Status', 'mp' ),
					'value' => $order_statuses[ $order_data->post_status ],
				),
				array(
					'name'  => __( 'Order Total', 'mp' ),
					'value' => $order_meta['mp_order_total'][0],
				),
				array(
					'name'  => __( 'Order Info', 'mp' ),
					'value' => $order_info,
				),
			);

			$billing_address = $mp_order->get_address( 'billing' );
			$shipping_address = $mp_order->get_address( 'shipping' );

			if ( ' <br /><br />, ' !== $billing_address ) {
				$data[][] = array(
					'name'  => __( 'Billing Address', 'mp' ),
					'value' => $billing_address,
				);
			}

			if ( ' <br /><br />, ' !== $shipping_address ) {
				$data[][] = array(
					'name'  => __( 'Shipping Address', 'mp' ),
					'value' => $shipping_address,
				);
			}
		} // End foreach().

		return $data;
	}

}
