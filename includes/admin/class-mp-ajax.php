<?php

class MP_Ajax {
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
			self::$_instance = new MP_Ajax();
		}
		return self::$_instance;
	}

	/**
	 * Process ajax login
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_nopriv_mp_ajax_login
	 */
	public function ajax_login() {
		check_ajax_referer( 'mp-login-nonce', 'mp_login_nonce' );
		
		$info = array(
			'user_login' => mp_get_post_value( 'email', '' ),
			'user_password' => mp_get_post_value( 'pass', '' ),
			'remember' => true,
		);
		
		$user_signon = wp_signon( $info, false );
		if ( is_wp_error( $user_signon ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid email address and/or password', 'mp' ),
			) );
		}
		
		wp_send_json_success();
	}
	
	/**
	 * Bulk edit products
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_bulk_edit_product
	 */
	public function bulk_edit_products() {
		if ( ! wp_verify_nonce(mp_get_post_value('nonce'), 'bulk_edit_products') ) {
			die;
		}
		
		$post_ids = mp_get_post_value('post_ids');
		$price = mp_get_post_value('price', '');
		$sale_price = mp_get_post_value('sale_price', '');
		
		if ( ! is_array($post_ids) ) {
			die;
		}
		
		foreach ( $post_ids as $post_id ) {
			update_post_meta($post_id, 'regular_price', $price);
			update_post_meta($post_id, 'sale_price_amount', $sale_price);
		}
		
		die;
	}

	/**
	 * Create a store page
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_create_store_page
	 */
	public function create_store_page() {
		check_admin_referer( 'mp_create_store_page' );
		
		$type = mp_get_get_value( 'type' );
		$post_id = mp_create_store_page( $type );
		
		wp_send_json_success( array(
			'post_id' => $post_id,
			'select2_value' => $post_id . '->' . get_the_title( $post_id ),
			'button_html' => '<a target="_blank" class="button mp-edit-page-button" href="' . add_query_arg( array(
				'post' => $post_id,
				'action' => 'edit',
			), get_admin_url( null, 'post.php' ) ) . '">' . __( 'Edit Page', 'mp' ) . '</a>',
		) );
	}	
	
	/**
	 * Look up an order by it's ID
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_lookup_order, wp_ajax_nopriv_mp_lookup_order
	 */
	public function lookup_order( ) {
		if ( $order_id = mp_get_post_value( 'order_id' ) ) {
			$order = new MP_Order( $order_id );
			if ( $order->exists() ) {
				wp_send_json_success( array(
					'redirect_url' => trailingslashit( mp_store_page_url( 'order_status', false ) ) . $order->get_id(),
				) );
			}
		}
		
		wp_send_json_error( array(
			'error_message' => __( 'Oops... we could not locate any orders by that ID. Please double check your order ID and try again.', 'mp' ),
		) );
	}
	
	/**
	 * Update product list
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_nopriv_mp_update_product_list, wp_ajax_mp_update_product_list
	 */
	public function update_product_list() {
		$page = mp_get_post_value( 'page', 1 );
		$per_page = mp_get_post_value( 'per_page', 1 );
		$category = mp_get_post_value( 'product_category' );
		list( $order_by, $order ) = explode( '-', mp_get_post_value( 'order' ) );
		
		if ( empty( $order ) ) {
			$order_by = $order = null;
		}
		
		mp_list_products( array(
			'page' => $page,
			'order_by' => $order_by,
			'order' => ( ! is_null( $order ) ) ? strtoupper( $order ) : $order,
		) );
		
		die;
	}

	/**
	 * Update state dropdown list and determine if zip code field should be shown
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_states_dropdown, wp_ajax_nopriv_mp_update_states_dropdown
	 */
	public function update_states_dropdown() {
		$states = false;
		$show_zipcode = true;
		
		if ( $country = mp_get_post_value( 'country' ) ) {
			$_states = mp_get_states( $country );
			
			if ( $_states ) {
				$states = '<option value="">' . __( 'Select One', 'mp' ) . '</option>';
				$selected = mp_get_user_address_part( 'state', mp_get_post_value( 'type' ) );
				foreach ( $_states as $val => $label ) {
					$states .= '<option value="' . $val . '" ' . selected( $selected, $val, false ) . '>' . $label . '</option>'; 
				}
			}
			
			if ( array_key_exists( $country, mp()->countries_no_postcode ) ) {
				$show_zipcode = false;
			}
		}
		
		wp_send_json_success( array( 'states' => $states, 'show_zipcode' => $show_zipcode ) );
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'wp_ajax_mp_create_store_page', array( &$this, 'create_store_page' ) );
		add_action( 'wp_ajax_mp_bulk_edit_products', array( &$this, 'bulk_edit_products' ) );
		add_action( 'wp_ajax_mp_change_order_status', array( 'MP_Orders_Admin', 'ajax_change_order_status' ) );
		// Get product variation colorbox
		add_action( 'wp_ajax_mp_product_get_variations_lightbox', array( 'MP_Product', 'ajax_display_variations_lightbox' ) );
		add_action( 'wp_ajax_nopriv_mp_product_get_variations_lightbox', array( 'MP_Product', 'ajax_display_variations_lightbox' ) );
		// Update product attributes
		add_action( 'wp_ajax_mp_product_update_attributes', array( 'MP_Product', 'ajax_update_attributes' ) );
		add_action( 'wp_ajax_nopriv_mp_product_update_attributes', array( 'MP_Product', 'ajax_update_attributes' ) );
		// Ajax login
		add_action( 'wp_ajax_nopriv_mp_ajax_login', array( &$this, 'ajax_login' ) );
		// Look up order
		add_action( 'wp_ajax_mp_lookup_order', array( &$this, 'lookup_order' ) );
		add_action( 'wp_ajax_nopriv_mp_lookup_order', array( &$this, 'lookup_order' ) );
		// Get state list
		add_action( 'wp_ajax_mp_update_states_dropdown', array( &$this, 'update_states_dropdown' ) );
		add_action( 'wp_ajax_nopriv_mp_update_states_dropdown', array( &$this, 'update_states_dropdown' ) );
		// Update product list
		add_action( 'wp_ajax_mp_update_product_list', array( &$this, 'update_product_list' ) );
		add_action( 'wp_ajax_nopriv_mp_update_product_list', array( &$this, 'update_product_list' ) );
	}
}

MP_Ajax::get_instance();