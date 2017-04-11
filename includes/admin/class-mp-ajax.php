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
		//check if an username already exist, for validation
		add_action( 'wp_ajax_nopriv_mp_check_if_username_exists', array( &$this, 'check_if_username_exists' ) );
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

		add_action( 'wp_ajax_ajax_add_new_variant', array( &$this, 'create_new_variation_draft' ) );

		add_action( 'wp_ajax_mp_remove_custom_shipping_method', array( &$this, 'mp_remove_custom_shipping_method' ) );
	}

	public function create_new_variation_draft() {
		$variation_post_draft = array(
			'post_title'	 => __( 'Variation Draft', 'mp' ),
			'post_content'	 => '',
			'post_status'	 => 'draft',
			'post_type'		 => MP_Product::get_variations_post_type(),
			'post_parent'	 => (int) $_POST[ 'parent_post_id' ],
		);

		$variation_post_draft_id = wp_insert_post( $variation_post_draft );

		$response				 = array();
		$response[ 'type' ]		 = true;
		$response[ 'post_id' ]	 = $variation_post_draft_id;
		echo json_encode( $response );
		exit;
	}

	public function variation_popup() {
		?>
		<div id="mp_more_popup_<?php echo isset( $_GET[ 'variation_id' ] ) ? $_GET[ 'variation_id' ] : ''; ?>" class="mp_more_popup">
			<div class="mp_popup_content">
				<?php
				$variation_id	 = (int) $_GET[ 'variation_id' ];
				$post_id		 = wp_get_post_parent_id( $variation_id );

				$product_type = get_post_meta( $post_id, 'product_type', true );

				$product_attributes			 = MP_Product_Attributes_Admin::get_product_attributes();
				$product_attributes_array	 = array();

				$args = array(
					'post_parent'	 => $post_id,
					'post_type'		 => MP_Product::get_variations_post_type(),
					'posts_per_page' => -1,
					'post_status'	 => 'publish',
					'orderby'		 => 'ID',
					'order'			 => 'ASC',
				);

				$children = get_children( $args, OBJECT );

				$variation_attributes = array();
				$variation_attributes_remaining = array();

				$first_post_id = 0;

				foreach ( $children as $child ) {
					if ( $first_post_id == 0 ) {
						$first_post_id = $child->ID;
					}

					foreach ( $product_attributes as $product_attribute ) {
						$product_attributes_array[ $product_attribute->attribute_id ] = $product_attribute->attribute_name;

						$child_terms = get_the_terms( /* $variation_id */$child->ID, 'product_attr_' . $product_attribute->attribute_id );
						
						if ( isset( $child_terms[ 0 ]->term_id ) && $child_terms[ 0 ]->name ) {
							$variation_attributes[ $product_attribute->attribute_id ][ $child_terms[ 0 ]->term_id ] = array( $product_attribute->attribute_id, $child_terms[ 0 ]->name );

							$used_taxonomy_ids[] = 'product_attr_' . $product_attribute->attribute_id;
						}
						else{	
							$variation_attributes_remaining[ $product_attribute->attribute_id ][ $child_terms[ 0 ]->term_id ] = array( $product_attribute->attribute_id, $child_terms[ 0 ]->name );
						}
					}
				}

				foreach( $variation_attributes_remaining as $variation_attribute_remaining_key => $variation_attribute_remaining ){

					if( in_array( 'product_attr_' . $variation_attribute_remaining_key, $used_taxonomy_ids ) ) unset( $variation_attributes_remaining[ $variation_attribute_remaining_key ] );

				}
				?>
				<form name="variation_popup" id="variation_popup">
					<?php do_action( 'mp_variation_popup_before_fields' ); ?>
					<?php if ( isset( $_GET[ 'new_variation' ] ) ) {
						?>
						<input type="hidden" id="new_variation" name="new_variation" value="yes" />
					<?php }
					?>
					<input type="hidden" name="action" value="edit_variation_post_data" />
					<input type="hidden" name="post_id" id="variation_id" value="<?php echo esc_attr( $variation_id ); ?>" />
					<input type="hidden" name="ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( "mp-ajax-nonce" ) ); ?>" />

					<div class="mp-product-field-25 mp-variation-field vtop mp-variation-image">
						<div class="wpmudev-field-label"><a href="#" class="remove_popup_image"><?php _e( 'Remove Image', 'mp' ); ?></a></div>
						<?php
						if ( has_post_thumbnail( $variation_id ) ) {
							echo get_the_post_thumbnail( $variation_id, array( 75, 75 ) );
						} else {
							global $mp;
							?>
							<img width="75" height="75" src="<?php echo $mp->plugin_url( '/includes/admin/ui/images/img-placeholder.jpg' ); ?>" />
						<?php }
						?>
					</div>

					<?php do_action( 'mp_variation_popup_after_image' ); ?>

					<div class="mp-product-field-75 mp-variation-field mp-product-field-last">
						<div class="wpmudev-field-label"><?php _e( 'SKU', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(Stock Keeping Unit)', 'mp' ); ?></span></div>
						<input type="text" name="sku" class="mp-product-field-98 mp-blank-bg" placeholder="<?php esc_attr_e( 'Enter SKU', 'mp' ); ?>" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'sku' ) ); ?>">

						<div class="wpmudev-field-label"><?php _e( 'Price', 'mp' ); ?><span class="required">*</span></div>
						<input type="text" name="regular_price" id="regular_price" class="mp-product-field-98 mp-blank-bg mp-numeric mp-required" placeholder="<?php esc_attr_e( 'Enter Price', 'mp' ); ?>" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'regular_price' ) ); ?>">
					</div>
					<?php do_action( 'mp_variation_popup_after_sku_and_price' ); ?>

					<?php if ( $product_type == 'external' ) {//show these fields only for External URL Products ?>
						<div class="mp-product-field-100 mp-variation-field">
							<div class="wpmudev-field-label"><?php _e( 'External Product URL', 'mp' ); ?><span class="required">*</span></div>
							<input type="text" name="external_url" id="external_url" class="mp-required" placeholder="<?php esc_attr_e( 'http://', 'mp' ); ?>" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'external_url' ) ); ?>">
						</div>
					<?php } ?>
					<?php do_action( 'mp_variation_popup_after_external_url' ); ?>

					<?php if ( $product_type == 'digital' ) {//show these fields only for Digital Products ?>
						<div class="mp-product-field-100 mp-variation-field">
							<div class="wpmudev-field-label"><?php _e( 'File URL', 'mp' ); ?><span class="required">*</span></div>
							<input type="text" name="file_url" id="file_url" class="mp-required" placeholder="<?php esc_attr_e( 'http://', 'mp' ); ?>" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'file_url' ) ); ?>">
							<input type="button" name="file_url_button" id="file_url_button" value="<?php echo esc_attr( __( 'Browse', 'mp' ) ); ?>" />
						</div>
					<?php } ?>
					<?php do_action( 'mp_variation_popup_after_file_url' ); ?>

					<?php
					foreach ( array_keys( $variation_attributes ) as $variation_attribute ) {
						$child_term	 = get_the_terms( $variation_id, 'product_attr_' . $variation_attribute );
						$child_term	 = isset( $child_term[ 0 ] ) ? $child_term[ 0 ] : '';
						?>
						<div class="mp-product-field-100 mp-variation-field">
							<div class="wpmudev-field-label"><?php echo $product_attributes_array[ $variation_attribute ]; ?><span class="required">*</span></div>
							<input type="text" name="product_attr_<?php echo esc_attr( $variation_attribute ); ?>" id="product_attr_<?php echo esc_attr( $variation_attribute ); ?>" class="mp-required" placeholder="<?php
							esc_attr_e( 'Enter ', 'mp' );
							echo esc_attr( $product_attributes_array[ $variation_attribute ] );
							?>" value="<?php echo is_object( $child_term ) ? esc_attr( $child_term->name ) : ''; ?>">
						</div>
					<?php } ?>
					<?php do_action( 'mp_variation_popup_after_attributes' ); ?>

					<?php if( is_array( $variation_attributes_remaining ) && ! empty( $variation_attributes_remaining ) ): ?>
					<h3><?php _e( 'Unused Attributes', 'mp' ); ?></h3>
					<?php					
					foreach ( array_keys( $variation_attributes_remaining ) as $variation_attribute ) {
						$child_term	 = get_the_terms( $variation_id, 'product_attr_' . $variation_attribute );
						$child_term	 = isset( $child_term[ 0 ] ) ? $child_term[ 0 ] : '';
						?>
						<div class="mp-product-field-100 mp-variation-field">
							<div class="wpmudev-field-label"><?php echo $product_attributes_array[ $variation_attribute ]; ?></div>
							<input type="text" name="product_attr_<?php echo esc_attr( $variation_attribute ); ?>" id="product_attr_<?php echo esc_attr( $variation_attribute ); ?>" class="mp-not-required" placeholder="<?php
							esc_attr_e( 'Enter ', 'mp' );
							echo esc_attr( $product_attributes_array[ $variation_attribute ] );
							?>" value="<?php echo is_object( $child_term ) ? esc_attr( $child_term->name ) : ''; ?>">
						</div>
					<?php } ?>
					<?php endif; ?>

					<?php if ( $product_type == 'physical' ) {//show these fields only for Physical Products ?>
						<div class="fieldset_check">
							<label>
								<input type="checkbox" name="has_per_order_limit" class="has_controller" <?php checked( true, intval( MP_Product::get_variation_meta( $variation_id, 'per_order_limit', 0 ) ) > 0, true ); ?>>
								<span><?php _e( 'Limit the Amount of Items per Order', 'mp' ); ?></span>
							</label>
							<fieldset id="fieldset_has_per_order_limit" class="has_area">
								<div class="wpmudev-field-label"><?php _e( 'Limit Per Order', 'mp' ); ?><span class="required">*</span></div>
								<input type="text" name="per_order_limit" id="per_order_limit" class="mp-product-field-98 mp-numeric" placeholder="<?php esc_attr_e( 'Unlimited', 'mp' ); ?>" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'per_order_limit' ) ); ?>">
							</fieldset>
						</div>
					<?php } ?>
					<?php do_action( 'mp_variation_popup_after_order_limit' ); ?>

					<div class="fieldset_check">
						<?php
						$has_sale = MP_Product::get_variation_meta( $variation_id, 'has_sale', 0 );
						?>
						<label>
							<input type="checkbox" name="has_sale" class="has_controller" <?php checked( 1, $has_sale, true ); ?>>
							<span><?php _e( 'Set up a Sale for this Product', 'mp' ); ?></span>
						</label>
						<fieldset id="fieldset_has_sale" class="has_area">
							<?php _e( 'Price', 'mp' ); ?><span class="required">*</span><input placeholder="<?php esc_attr_e( 'Enter Sale Price', 'mp' ); ?>" type="text" class="mp-numeric mp-required" name="sale_price[amount]" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'sale_price_amount' ) ); ?>"><br>
							<?php _e( 'Percentage Discount', 'mp' ); ?><span class="required">*</span><input placeholder="" type="text" class="mp-numeric mp-required" name="sale_price[percentage]" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'sale_price_percentage' ) ); ?>"><br>
							<?php _e( 'Start Date (if applicable)', 'mp' ); ?> <input name="sale_price[start_date]" type="text" class="mp-date" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'sale_price_start_date' ) ); ?>"><br>
							<?php _e( 'End Date (if applicable)', 'mp' ); ?> <input name="sale_price[end_date]" type="text" class="mp-date" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'sale_price_end_date' ) ); ?>">
						</fieldset>
					</div>
					<?php do_action( 'mp_variation_popup_after_sale' ); ?>

					<?php if ( $product_type == 'physical' || $product_type == 'digital' ) {//show these fields only for Physical and Digital Products  ?>
						<div class="fieldset_check">
							<?php
							$charge_tax = MP_Product::get_variation_meta( $variation_id, 'charge_tax', 0 );
							?>
							<label>
								<input type="checkbox" name="charge_tax" class="has_controller" <?php checked( 1, $charge_tax, true ); ?>>
								<span><?php _e( 'Charge Taxes (Special Rate)', 'mp' ); ?></span>
							</label>
							<fieldset id="fieldset_charge_tax" class="has_area">
								<div class="wpmudev-field-desc"><?php _e( 'If you would like this product to use a special tax rate, enter it here. If you omit the "%" symbol the rate will be calculated as a fixed amount for each of this product in the user\'s cart.', 'mp' ); ?></div>
								<?php _e( 'Special Tax Rate', 'mp' ); ?>
								<input placeholder="<?php esc_attr_e( 'Tax Rate', 'mp' ); ?>" type="text" name="special_tax_rate" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'special_tax_rate' ) ); ?>">
								<br>
							</fieldset>
						</div>
						<?php do_action( 'mp_variation_popup_after_tax' ); ?>
					<?php } ?>

					<?php if ( $product_type == 'physical' ) {//show these fields only for Physical and Digital Products  ?>
						<div class="fieldset_check">
							<?php
							$charge_shipping = MP_Product::get_variation_meta( $variation_id, 'charge_shipping', 0 );
							?>
							<label>
								<input type="checkbox" name="charge_shipping" class="has_controller" <?php checked( 1, $charge_shipping, true ); ?>>
								<span><?php _e( 'Charge Shipping', 'mp' ); ?></span>
							</label>
							<fieldset id="fieldset_has_sale" class="has_area">
								<?php if ( $product_type == 'physical' ) {//show these fields only for Physical Products  ?>
									<?php if ( 'metric' == mp_get_setting( 'shipping->system' ) ) { ?>
										<?php _e( 'Kilograms:', 'mp' ); ?> <input placeholder="" type="text" name="weight[pounds]" class="mp-numeric" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'weight_pounds' ) ); ?>"><br>
									<?php } else { ?>
										<?php _e( 'Pounds:', 'mp' ); ?> <input placeholder="" type="text" name="weight[pounds]" class="mp-numeric" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'weight_pounds' ) ); ?>"><br>
										<?php _e( 'Ounces:', 'mp' ); ?> <input name="weight[ounces]" type="text" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'weight_ounces' ) ); ?>" class="mp-numeric "><br>
										<?php
									}
								}
								?>
								<?php _e( 'Extra Shipping Cost (if applicable)', 'mp' ); ?> <input class="mp-numeric" name="weight[extra_shipping_cost]" type="text" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'weight_extra_shipping_cost' ) ); ?>">
							</fieldset>
						</div>
						<?php do_action( 'mp_variation_popup_after_shipping' ); ?>
					<?php } ?>

					<?php if ( $product_type == 'physical' || $product_type == 'digital' ) {//show these fields only for Physical and Digital Products    ?>
						<div class="fieldset_check">
							<?php
							$inventory_tracking = MP_Product::get_variation_meta( $variation_id, 'inventory_tracking', 0 );
							?>
							<label>
								<input type="checkbox" name="inventory_tracking" class="has_controller" <?php checked( 1, $inventory_tracking, true ); ?>>
								<span><?php _e( 'Track Product Inventory', 'mp' ); ?></span>
							</label>
							<fieldset id="fieldset_has_sale" class="has_area">
								<?php _e( 'Quantity:', 'mp' ); ?> <input placeholder="" type="text" name="inventory[inventory]" value="<?php echo esc_attr( MP_Product::get_variation_meta( $variation_id, 'inventory' ) ); ?>" class="mp-numeric mp-required"><br>
								<input name="inventory[out_of_stock_purchase]" type="checkbox" <?php checked( 1, MP_Product::get_variation_meta( $variation_id, 'inv_out_of_stock_purchase' ) ); ?> value="1"><?php _e( 'Allow this product to be purchased even if it\'s out of stock', 'mp' ); ?><br>
							</fieldset>
						</div>
						<?php do_action( 'mp_variation_popup_after_inventory_tracking' ); ?>
					<?php } ?>
					<div class="fieldset_check">
						<?php
						$has_variation_content	 = MP_Product::get_variation_meta( $variation_id, 'has_variation_content', 0 );
						$variation_content_type	 = MP_Product::get_variation_meta( $variation_id, 'variation_content_type', 'plain' );
						?>
						<label>
							<input type="checkbox" name="has_variation_content" class="has_controller" value="1" <?php checked( 1, $has_variation_content, true ); ?>>
							<span><?php _e( 'Additional Content / Information for this Variation', 'mp' ); ?></span>
						</label>
						<fieldset id="fieldset_has_variation_content" class="has_area">
							<?php $variation				 = get_post( $variation_id ); ?>
							<input type="radio" name="variation_content_type" class="variation_content_type" value="plain" <?php checked( 'plain', $variation_content_type, true ); ?>><?php _e( 'Plain Text Only', 'mp' ); ?>
							<textarea id="variation_content_type_plain" class="variation_content_type_plain" name="variation_content_type_plain"><?php echo esc_attr( strip_tags( $variation->post_content ) ); ?></textarea>
							<br /><br/>
							<input type="radio" name="variation_content_type" class="variation_content_type" value="html" <?php checked( 'html', $variation_content_type, true ); ?>><?php _e( 'HTML Markup', 'mp' ); ?><a class="button variation_description_button" id="variation_description_button" href="<?php echo admin_url( 'post.php?post=' . $variation_id . '&action=edit' ); ?>" target="_blank"><?php _e( 'Edit Description', 'mp' ); ?></a>
						</fieldset>
					</div>
					<?php do_action( 'mp_variation_popup_after_variation_content_type' ); ?>
				</form>

			</div>
			<div class="mp_popup_controls mp_more_controls">
				<span class="mp_ajax_response"></span>
				<a href="" id="save-variation-popup-data" class="button button-primary save-more-form"><?php _e( 'Save ', 'mp' ); ?></a>
				<a href="" class="preview button cancel"><?php _e( 'Cancel ', 'mp' ); ?></a>
			</div>
			<script>
				jQuery( 'body' ).trigger( 'mp-variation-popup-loaded' );
			</script>
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
		check_ajax_referer( 'mp-login-nonce', 'mp_login_nonce' );

		$error_message = __( 'Oops! You entered an invalid username/email and or password.', 'mp' );

		$user_login = mp_get_post_value( 'email ', '' );

		if ( is_email( $user_login ) ) {
			$user = get_user_by( 'email', $user_login );

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

		if ( !is_array( $post_ids ) || ! is_numeric( $price ) ) {
			die;
		}

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, 'regular_price', $price );
			update_post_meta( $post_id, 'sale_price_amount', $sale_price );
			
			if( ! empty( $sale_price ) && $sale_price > 0 ) {
				update_post_meta( $post_id, 'sort_price', $sale_price );
			} else {
				update_post_meta( $post_id, 'sort_price', $price );
			}
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
	 * Check if an username exists
	 *
	 * @since 3.0
	 * @access public
	 * @access wp_ajax_nopriv_mp_check_if_username_exists
	 */
	public function check_if_username_exists() {
		if ( username_exists( mp_get_request_value( 'account_username', '' ) ) ) {
			die( 'false' );
		}

		die( 'true' );
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

				$redirect_url = trailingslashit( mp_store_page_url( 'order_status', false ) ) . $order->get_id();
				
				//non-logged in user
				if ( $guest_email = mp_get_post_value( 'guest_email' ) ){
					$redirect_url = trailingslashit( $redirect_url ) . md5( $guest_email );
				}

				wp_send_json_success( array(
					'redirect_url' => $redirect_url,
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
		$post_order	 = mp_get_post_value( 'order' );

		list( $order_by, $order ) = explode( '-', $post_order );

		if ( session_id() == '' ) {
			session_start();
		}

		if ( isset( $post_order ) ) {
			$_SESSION[ 'mp_product_list_order_by' ]	 = $order_by;
			$_SESSION[ 'mp_product_list_order' ]	 = $order;
		} else {
			$order_by	 = $_SESSION[ 'mp_product_list_order_by' ];
			$order		 = $_SESSION[ 'mp_product_list_order' ];
		}

		if ( empty( $order ) ) {
			$order_by	 = $order		 = null;
		}


		//get_category
		$mp_product_list_args = array(
			'page'		 => $page,
			'order_by'	 => $order_by,
			'order'		 => (!is_null( $order ) ) ? strtoupper( $order ) : $order,
		);

		if ( isset( $category ) && $category > 0 ) {
			$cat								 = get_term( $category, 'product_category' );
			$mp_product_list_args[ 'category' ]	 = $cat->slug;
		}

		mp_list_products( $mp_product_list_args );

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

	/**
	 * Remove custom shipping method
	 *
	 * @since 3.0
	 * @access public
	 */
	public function mp_remove_custom_shipping_method() {
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		$id						 = mp_get_post_value( 'id' );
		$custom_shipping_method	 = mp_get_setting( 'shipping->custom_method', array() );
		unset( $custom_shipping_method[ $id ] );
		mp_update_setting( 'shipping->custom_method', $custom_shipping_method );

		wp_send_json( array(
			'status' => 'success'
		) );
	}

}

MP_Ajax::get_instance();
