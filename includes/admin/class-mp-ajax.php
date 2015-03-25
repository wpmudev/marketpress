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
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Ajax();
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
		// Create store page
		add_action( 'wp_ajax_mp_create_store_page', array( &$this, 'create_store_page' ) );
		// Bulk edit products
		add_action( 'wp_ajax_mp_bulk_edit_products', array( &$this, 'bulk_edit_products' ) );
		// Change order status
		add_action( 'wp_ajax_mp_change_order_status', array( 'MP_Orders_Admin', 'ajax_change_order_status' ) );
		// Check if an email address exists
		add_action( 'wp_ajax_nopriv_mp_check_if_email_exists', array( &$this, 'check_if_email_exists' ) );
		// Create account
		add_action( 'wp_ajax_nopriv_mp_create_account', array( &$this, 'create_account' ) );
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
		//Get variation popup window content
		add_action( 'wp_ajax_mp_variation_popup', array( &$this, 'variation_popup' ) );
	}

	public function variation_popup() {
		?>
		<div id="mp_more_popup_<?php echo isset( $_GET[ 'variation_id' ] ) ? $_GET[ 'variation_id' ] : ''; ?>" class="mp_more_popup">
			<div class="mp_popup_content">
				<?php
				$variation_id	 = (int) $_GET[ 'variation_id' ];
				$post_id		 = wp_get_post_parent_id( $variation_id );

				$product_attributes			 = MP_Product_Attributes_Admin::get_product_attributes();
				$product_attributes_array	 = array();

				$args = array(
					'post_parent'	 => $post_id,
					'post_type'		 => 'mp_product_variation',
					'posts_per_page' => -1,
					'post_status'	 => 'publish'
				);

				$children = get_children( $args, OBJECT );

				$variation_attributes = array();

				foreach ( $children as $child ) {
					foreach ( $product_attributes as $product_attribute ) {
						$product_attributes_array[ $product_attribute->attribute_id ] = $product_attribute->attribute_name;

						$child_terms = get_the_terms( $variation_id, 'product_attr_' . $product_attribute->attribute_id );
						if ( isset( $child_terms[ 0 ]->term_id ) && $child_terms[ 0 ]->name ) {
							$variation_attributes[ $product_attribute->attribute_id ][ $child_terms[ 0 ]->term_id ] = array( $product_attribute->attribute_id, $child_terms[ 0 ]->name );
						}
					}
				}
				?>
				<form name="variation_popup" id="variation_popup">

					<div class="mp-product-field-50 mp-variation-field">
						<div class="wpmudev-field-label"><?php _e( 'SKU', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(Stock Keeping Unit)', 'mp' ); ?></span></div>
						<input type="text" name="sku" class="mp-product-field-98 mp-blank-bg" placeholder="<?php esc_attr_e( 'Enter SKU', 'mp' ); ?>" value="">
					</div>

					<div class="mp-product-field-50 mp-variation-field">
						<div class="wpmudev-field-label"><?php _e( 'Price', 'mp' ); ?><span class="required">*</span></div>
						<input type="text" name="regular_price" id="regular_price" class="mp-product-field-98 mp-blank-bg mp-numeric mp-required" placeholder="<?php esc_attr_e( 'Enter Price', 'mp' ); ?>" value="">
					</div>

					<?php foreach ( array_keys( $variation_attributes ) as $variation_attribute ) { ?>
						<div class="mp-product-field-100 mp-variation-field">
							<div class="wpmudev-field-label"><?php echo $product_attributes_array[ $variation_attribute ]; ?><span class="required">*</span></div>
							<input type="text" name="<?php echo $variation_attribute;?>" id="<?php echo $variation_attribute;?>" class="mp-required" placeholder="<?php esc_attr_e( 'Enter ', 'mp' ); echo $product_attributes_array[ $variation_attribute ];?>" value="">
						</div>
					<?php } ?>


					<div>

					</div>

				</form>

			</div>
			<div class="mp_popup_controls mp_more_controls">
				<a href="" class="button button-primary save-more-form"><?php _e( 'Save ', 'mp' ); ?></a>
				<a href="" class="preview button cancel"><?php _e( 'Cancel ', 'mp' ); ?></a>
			</div>
		</div>
		<?php
		exit;
	}

	/**
	 * Process ajax login
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_nopriv_mp_ajax_login
	 */
	public function ajax_login() {
		check_ajax_referer( 'mp-login-nonce ', 'mp_login_nonce' );

		$error_message = __( 'Oops!You entered an invalid username and or password ', 'mp' );

		$user_login = mp_get_post_value( 'email ', '' );

		if ( is_email( $user_login ) ) {
			$user = get_user_by( 'email ', $user_login );

			if ( !$user instanceof WP_User ) {
				wp_send_json_error( array(
					'message' => $error_message,
				) );
			}

			$user_login = $user->user_login;
		}

		$info = array(
			'user_login'	 => $user_login,
			'user_password'	 => mp_get_post_value( 'pass', '' ),
			'remember'		 => true,
		);

		$user_signon = wp_signon( $info, false );
		if ( is_wp_error( $user_signon ) ) {
			wp_send_json_error( array(
				'message' => $error_message,
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
		if ( !wp_verify_nonce( mp_get_post_value( 'nonce' ), 'bulk_edit_products' ) ) {
			die;
		}

		$post_ids	 = mp_get_post_value( 'post_ids' );
		$price		 = mp_get_post_value( 'price', '' );
		$sale_price	 = mp_get_post_value( 'sale_price', '' );

		if ( !is_array( $post_ids ) ) {
			die;
		}

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, 'regular_price', $price );
			update_post_meta( $post_id, 'sale_price_amount', $sale_price );
		}

		die;
	}

	/**
	 * Check  if an email address exists
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_nopriv_mp_check_if_email_exists
	 */
	public function check_if_email_exists() {
		if ( email_exists( mp_get_request_value( 'email', '' ) ) ) {
			die( 'false' );
		}

		die( 'true' );
	}

	/**
	 * Create account
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_nopriv_mp_create_account
	 */
	public function create_account() {
		if ( wp_verify_nonce( mp_get_post_value( 'mp_create_account_nonce' ), 'mp_create_account' ) ) {
			$user_login	 = uniqid( true );
			$user_id	 = wp_insert_user( array(
				'user_login' => $user_login,
				'user_email' => mp_get_post_value( 'email' ),
				'user_pass'	 => mp_get_post_value( 'password1' ),
				'first_name' => mp_get_post_value( 'name_first' ),
				'last_name'	 => mp_get_post_value( 'name_last' ),
				'role'		 => 'subscriber',
			) );

			if ( !is_wp_error( $user_id ) ) {
				$user_signon = wp_signon( array(
					'user_login'	 => $user_login,
					'user_password'	 => mp_get_post_value( 'password1' ),
					'remember'		 => true,
				), false );

				wp_send_json_success();
			}
		}

		wp_send_json_error( array(
			'message' => __( 'Oops!An unknown error occurred while creating your account. Please try again.', 'mp' ),
		) );
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

		$type	 = mp_get_get_value( 'type' );
		$post_id = mp_create_store_page( $type );

		wp_send_json_success( array(
			'post_id'		 => $post_id,
			'select2_value'	 => $post_id . '->' . get_the_title( $post_id ),
			'button_html'	 => '<a target = "_blank" class = "button mp-edit-page-button" href = "' . add_query_arg( array(
				'post'	 => $post_id,
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
	public function lookup_order() {
		if ( $order_id = mp_get_post_value( 'order_id' ) ) {
			$order = new MP_Order( $order_id );
			if ( $order->exists() ) {
				wp_send_json_success( array(
					'redirect_url' => trailingslashit( mp_store_page_url( 'order_status', false ) ) . $order->get_id(),
				) );
			}
		}

		wp_send_json_error( array( 'error_message' => __( 'Oops... we could not locate any orders by that ID. Please double check your order ID and try again.', 'mp' ),
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
		$page		 = mp_get_post_value( 'page', 1 );
		$per_page	 = mp_get_post_value( 'per_page', 1 );
		$category	 = mp_get_post_value( 'product_category' );
		list( $order_by, $order ) = explode( '-', mp_get_post_value( 'order' ) );

		if ( empty( $order ) ) {
			$order_by	 = $order		 = null;
		}

		mp_list_products( array
			(
			'page'		 => $page,
			'order_by'	 => $order_by,
			'order'		 => (!is_null( $order )

			) ?
			strtoupper( $order ) : $order,
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
		$states			 = false;
		$show_zipcode	 = true;

		if ( $country = mp_get_post_value( 'country' ) ) {
			$_states = mp_get_states( $country );

			if ( $_states ) {
				$states		 = '<option value="">' . __( 'Select One', 'mp' ) . '</option>';
				$selected	 = mp_get_user_address_part( 'state', mp_get_post_value( 'type' ) );
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

}

MP_Ajax::get_instance();








