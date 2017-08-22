<?php

/**
 * Class MP_Product
 */
class MP_Product {

	/**
	 * Refers to the product's ID.
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $ID = null;

	/**
	 * Refers to the product's qty
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $qty = 1;

	/**
	 * Refers to the product default variation.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_default_variation = null;

	/**
	 * Refers to the product's variations.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_variations = null;

	/**
	 * Refers to the product's variation IDs
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_variation_ids = null;

	/**
	 * Refers to the product's attributes.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_attributes = null;

	/**
	 * Refers to if the product is on sale.
	 *
	 * @since 3.0
	 * @access protected
	 * @var bool
	 */
	protected $_on_sale = null;

	/**
	 * Refers to the product's price
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param array
	 */
	protected $_price = null;

	/**
	 * Refers to the product's internal WP_Post object.
	 *
	 * @since 3.0
	 * @access protected
	 * @type WP_Post
	 */
	protected $_post = null;

	/**
	 * Refers to the whether the product exists or not.
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_exists = null;

	/**
	 * Refers to the product's content tabs
	 *
	 * @since 3.0
	 * @access public
	 * @type array
	 */
	var $content_tabs = array();

	/**
	 * Get the internal post type for products.
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public static function get_post_type() {
		return mp_get_setting( 'product_post_type' ) == 'mp_product' ? 'mp_product' : 'product';
	}

	public static function get_variations_post_type() {
		return apply_filters( 'mp_product_variation_post_type', 'mp_product_variation' );
	}

	/**
	 * Display the lightbox for product variations
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_product_get_variations_lightbox, wp_ajax_nopriv_mp_product_get_variations_lightbox
	 */
	public static function ajax_display_variations_lightbox() {
		$product_id = mp_get_get_value( 'product_id' );
		$product    = new MP_Product( $product_id );

		if ( 0 == 0 ) {
			ob_start();
			do_action( 'mp_public/before_variations_lightbox_form', $product_id, $product ); ?>

			<section id="mp-product-<?php echo $product->ID; ?>-lightbox" itemscope
					 itemtype="http://schema.org/Product">
				<div class="mp_product mp_product_options mp_product_lightbox">

					<?php do_action( 'mp_public/before_variation_fields_lightbox_form', $product_id, $product ); ?>

					<div class="mp_product_options_image">
						<?php
						$product->image_custom( true, 'medium', array(
							'class' => 'mp_product_options_thumb',
						) ); ?>
					</div>
					<!-- end mp_product_options_image -->

					<div class="mp_product_options_details">

						<div class="mp_product_options_meta">
							<h3 class="mp_product_name" itemprop="name"><?php echo $product->post_title; ?></h3>

							<div class="mp_product_excerpt mp_product_options_excerpt">
								<p><?php echo $product->excerpt(); ?></p></div>
							<!-- end mp_product_options_excerpt -->
						</div>
						<!-- end mp_product_options_meta -->

						<div class="mp_product_options_callout">

							<form id="mp-product-options-callout-form"
								  class="mp_form mp_form-mp-product-options-callout" method="post"
								  data-ajax-url="<?php echo mp_get_ajax_url( 'admin-ajax.php?action=mp_update_cart' ); ?>"
								  action="<?php echo get_permalink( mp_get_setting( 'pages->cart' ) ); ?>">
								<input type="hidden" name="product_id" value="<?php echo $product->ID; ?>">
								<input type="hidden" name="product_qty_changed" value="0">
								<?php $product->display_price(); ?>
								<?php $product->attribute_fields(); ?>
								<?php if ( mp_get_setting( 'product_button_type' ) == 'addcart' ) : ?>
									<button class="mp_button mp_button-addcart" type="submit"
											name="addcart"><?php _e( 'Add To Cart', 'mp' ); ?></button>
								<?php elseif ( mp_get_setting( 'product_button_type' ) == 'buynow' ) :
									?>
									<button class="mp_button mp_button-buynow" type="submit"
											name="buynow"><?php _e( 'Buy Now', 'mp' ); ?></button>
								<?php endif; ?>
							</form>
							<!-- end mp-product-options-callout-form -->

						</div>
						<!-- end mp_product_options_callout -->

					</div>
					<!-- end mp_product_options_details -->

					<?php do_action( 'mp_public/after_variation_fields_lightbox_form', $product_id, $product ); ?>

				</div>
				<!-- end mp_product_options -->
			</section><!-- end mp-product-<?php echo $product->ID; ?>-lightbox -->
			<?php do_action( 'mp_public/after_variations_lightbox_form', $product_id, $product );

			$html = ob_get_clean();

			echo apply_filters( 'mp_display_variations_lightbox', $html, $product_id, $product );
		} // End if().

		die;
	}

	/**
	 * Update the product attributes based upon selection
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_product_update_attributes, wp_ajax_nopriv_mp_product_update_attributes
	 */
	public static function ajax_update_attributes() {
		$product_atts = MP_Product_Attributes::get_instance();
		$attributes   = $attributes_including_others = $filtered_atts = $taxonomies = $filtered_terms = array();

		$json = array(
			'out_of_stock' => false,
			'qty_in_stock' => 0,
			'image'        => false,
			'image_full'   => false,
			'description'  => false,
			'excerpt'      => false,
			'price'        => false,
		);

		$product_id  = mp_get_post_value( 'product_id' );
		$product     = new MP_Product( $product_id );
		$qty         = mp_get_post_value( 'product_quantity', 1 );
		$qty_changed = (bool) mp_get_post_value( 'product_qty_changed' );

		if ( ! $product->exists() ) {
			wp_send_json_error();
		}

		$all_atts = $product->get_attributes();

		foreach ( $_POST as $key => $val ) {
			if ( 0 === strpos( $key, MP_Product_Attributes::SLUGBASE ) && ! empty( $val ) ) {
				$taxonomies[]       = $key;
				$attributes[ $key ] = $val;
			}

			if ( false !== strpos( $key, MP_Product_Attributes::SLUGBASE ) && ! empty( $val ) ) {
				$attributes_including_others[ str_replace( 'other_', '', $key ) ] = $val;
			}
		}

		$variations = $product->get_variations_by_attributes( $attributes );

		// Filter out taxonomies that already have values and are still valid
		foreach ( $all_atts as $att ) {

			$slug = $product_atts->generate_slug( $att['id'] );
			if ( ! in_array( $slug, $taxonomies ) || $qty_changed ) {
				$filtered_atts[] = $slug;
			}
		}

		// Make sure all attribute terms are unique and in stock
		if ( count( $variations ) > 0 ) {
			foreach ( $variations as $variation ) {
				$json['status'] = 'variation loop';
				foreach ( $filtered_atts as $tax_slug ) {
					$terms = get_the_terms( $variation->ID, $tax_slug );
					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							if ( $variation->in_stock( $qty ) ) {

								$json['status']                                = 'in stock';
								$json['qty_in_stock']                          = $variation->get_stock();
								$filtered_terms[ $tax_slug ][ $term->term_id ] = $term;
							} elseif ( $qty_changed || ! $variation->in_stock( $qty ) ) {
								$json['status']       = 'out of stock';
								$json['qty_in_stock'] = $variation->get_stock();

								/**
								 * Filter the out of stock alert message
								 *
								 * @since 3.0
								 *
								 * @param string The default message.
								 * @param MP_Product The product that is out of stock.
								 */
								$json['out_of_stock'] = apply_filters( 'mp_product/out_of_stock_alert', sprintf( __( 'We\'re sorry, we only have %d of this item in stock right now.', 'mp' ), $json['qty_in_stock'] ), $product );
							}
						}
					}
				}
			}
		} else {
			$json['status']       = 'out of stock';
			$json['out_of_stock'] = apply_filters( 'mp_product/out_of_stock_alert', sprintf( __( 'We\'re sorry, we only have %d of this item in stock right now.', 'mp' ), $json['qty_in_stock'] ), $product );
		}

		// Format attribute terms for display
		foreach ( $filtered_terms as $tax_slug => $terms ) {
			$json[ $tax_slug ] = '';
			$index             = 0;
			$terms             = $product_atts->sort( $terms, false );
			foreach ( $terms as $term ) {
				$checked  = ( mp_get_post_value( $tax_slug ) == $term->term_id || mp_get_post_value( 'other_' . $tax_slug ) == $term->term_id ) ? true : false;
				$required = ( 0 == $index ) ? true : false;
				$json[ $tax_slug ] .= self::attribute_option( $term->term_id, $term->name, $tax_slug, $required, $checked );
				$index ++;
			}
		}

		//Attempt to get a unique product variation depending on user selection
		foreach ( $attributes_including_others as $attr_name => $attr_value ) {
			if ( isset( $filtered_terms[ $attr_name ] ) ) {
				if ( ! in_array( $attr_value, array_keys( $filtered_terms[ $attr_name ] ) ) ) {
						$attributes_including_others[ $attr_name ] = reset( array_keys( $filtered_terms[ $attr_name ] ) ) . '';
				}
			}
		}

		$selected_variation = $product->get_variations_by_attributes( $attributes_including_others );

		if ( isset( $selected_variation ) && is_array( $selected_variation ) && isset( $selected_variation[0] ) ) {
			$selected_variation = $selected_variation[0];
			$json['product_input']  = $selected_variation->attribute_input_fields( true, $qty );
			$json['in_stock']       = $selected_variation->in_stock( $qty );
			$json['out_of_stock']   = false;

			$json['image'] = $selected_variation->image_url( false, null, 'single' );
			$json['image_full'] = $selected_variation->image_url( false, 'full', 'single' );

			$json['description'] = $selected_variation->content( false );

			$json['excerpt'] = mp_get_the_excerpt( $selected_variation->ID, 18 );

			$json['price'] = $selected_variation->display_price( false );
		}

		wp_send_json_success( $json );
	}

	/**
	 * MP_Product constructor
	 *
	 * @since 3.0
	 * @access public
	 * @uses $post
	 *
	 * @param int|null|object|WP_Post $product Optional if in the loop.
	 * @param int|null $blog_id Optional to use when global cart
	 */
	public function __construct( $product = null, $blog_id = null ) {
		if ( is_null( $product ) && in_the_loop() ) {
			global $post;
			$product = $post;
		}

		if ( ! is_null( $blog_id ) && is_multisite() ) {
			$current_blog_id = get_current_blog_id();
			switch_to_blog( $blog_id );
		}

		$this->_get_post( $product );
		$this->_set_content_tabs( $this );

		if ( ! is_null( $blog_id ) && is_multisite() && isset( $current_blog_id ) ) {
			switch_to_blog( $current_blog_id );
		}
	}

	/**
	 * Display an attribute option
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param $term_id
	 * @param $term_name
	 * @param $tax_slug
	 * @param bool $required
	 * @param bool $selected
	 *
	 * @return mixed|string|void
	 */
	public static function attribute_option( $term_id, $term_name, $tax_slug, $required = false, $selected = false ) {
		$html = '<option id="mp_product_options_att_' . $term_id . '" value="' . $term_id . '"' . ( ( $selected ) ? ' selected' : '' ) . '>' . $term_name . '</option>';

		/**
		 * Filter the attribute option
		 *
		 * @since 3.0
		 *
		 * @param string $html
		 * @param int $term_id
		 * @param string $term_name
		 * @param string $tax_slug
		 * @param bool $required
		 */
		$html = apply_filters( 'mp_product/attribute_option', $html, $term_id, $term_name, $tax_slug, $required );

		return $html;
	}

	/**
	 * Return the maximum product qty allowed to add to the cart
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param int $product_id Optional
	 * @param bool $without_cart_quantity Optional, the attributes that should be selected by default.
	 * @param bool $return_reason Optional, if true, it will return an array containing the qty allowed and reason of limitation
	 *
	 * @return int/array
	 */
	public function max_product_quantity( $product_id = false, $without_cart_quantity = false , $return_reason = false ) {

		if( ! $product_id ){
			$product_id = $this->ID;
		}

		$id						= $product_id;
		$product 				= new MP_Product( $product_id );
		$cart_items				= mp_cart()->get_all_items();
		$max					= 100;
		$cart_quantity			= 0;
		$per_order_limit		= intval(get_post_meta( $id, 'per_order_limit', true ));
		$inventory				= get_post_meta( $id, 'inventory', true );
		$inventory_tracking    	= get_post_meta( $id, 'inventory_tracking', true );
		$out_of_stock_purchase 	= get_post_meta( $id, 'inv_out_of_stock_purchase', true );
		$reason = "order";

		if ( $product->is_download() && mp_get_setting( 'download_order_limit' ) == '1' ) {
				$per_order_limit = $max = 1;
		}

		/**
		 * Filter default max product order limit
		 *
		 * @since 3.0
		 *
		 * @param int $max
		 */
		$max = apply_filters( 'mp_cart/max_product_order_default', $max );

		if ( !$without_cart_quantity && isset( $cart_items[ get_current_blog_id() ][ $id ] ) ) {
			$cart_quantity = (int) $cart_items[ get_current_blog_id() ][ $id ];
		}

		if ( is_numeric($inventory) && $inventory_tracking && $out_of_stock_purchase !== '1' ) {
				$max = $inventory;
				$reason = "inventory";
		}

		if ( is_numeric( $per_order_limit ) && $per_order_limit > 0 && ( $per_order_limit < $inventory || !is_numeric( $inventory ) || $product->is_variation() ) ) {
			$max = $per_order_limit;
			$reason = "order";
		}

		$max = $max - $cart_quantity;

		if ( $max < 0 ) {
			$max = 0;
		}

		if ( $return_reason ) {
			return array( 'qty' => $max , 'reason' => $reason );
		}

		return (int) $max;
	}

	/**
	 * Display the attribute fields
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 * @param array $selected_atts Optional, the attributes that should be selected by default.
	 */
	public function attribute_fields( $echo = true, $selected_atts = array() ) {
		$product_atts = MP_Product_Attributes::get_instance();
		$filtered_atts = $filtered_terms = array();

		$atts = $this->get_attributes();

		if( !empty( $selected_atts ) ) {
			// Get variations with only first attributes forced
			$first_att = array_slice($selected_atts, 0, 1 );
			$variations = $this->get_variations_by_attributes( $first_att );

			// Filter out taxonomies that already have values and are still valid
			foreach ( $atts as $att ) {
				$slug = $product_atts->generate_slug( $att['id'] );
				$filtered_atts[] = $slug;
			}

			$selected_variation = false;
			// Make sure all attribute terms are unique and in stock
			if ( count( $variations ) > 0 ) {
				foreach ( $variations as $variation ) {
					$selected_variation = $variation;
					foreach ( $filtered_atts as $tax_slug ) {
						$terms = get_the_terms( $variation->ID, $tax_slug );
						if( ! empty( $terms ) ) {
							foreach ( $terms as $term ) {
								if ( $variation->in_stock( ) ) {
									$filtered_terms[ $tax_slug ][ $term->term_id ] = $term;
								}
							}
						}
					}
				}
			}
		}

		$html = '
			<div class="mp_product_options_atts">';

		foreach ( $atts as $slug => $att ) {
			/**
			 * Filter the default option label for the select field
			 *
			 * @since 3.0
			 * @access public
			 *
			 * @param string The default option label.
			 */
			$default_option_label = apply_filters( 'mp_product/attribute_fields/default_option_label', sprintf( __( 'Choose a %s', 'mp' ), $att['name'] ) );

			$html .= '
				<div class="mp_product_options_att">
					<strong class="mp_product_options_att_label">' . $att['name'] . '</strong>
					<div class="mp_product_options_att_input_label">
						<select id="mp_' . $slug . '" name="' . $slug . '" class="mp_select2 required" autocomplete="off">
							<option value="">' . $default_option_label . '</option>';

			$index = 0;
			foreach ( $att['terms'] as $term_id => $term_name ) {
				if( empty( $selected_atts ) || isset( $filtered_terms[ $slug ][ $term_id ] ) || array_search( $slug, array_keys( $atts) ) == 0  ){
					$required = ( $index == 0 );
					$checked  = ( $term_id == mp_arr_get_value( $slug, $selected_atts ) );
					$html .= $this->attribute_option( $term_id, $term_name, $slug, $required, $checked );
					$index ++;
				}
			}

			$html .= '
						</select>
					</div><!-- end mp_product_options_att_input_label -->
				</div><!-- end mp_product_options_att -->';
		}

		$input_id = 'mp_product_options_att_quantity';

		if ( ! isset( $selected_variation ) ) {
			$selected_variation = false;
		}

		$html .= '
				<div class="mp_product_options_att"' . ( ( mp_get_setting( 'show_quantity' ) ) ? '' : ' style="display:none"' ) . '>
					<strong class="mp_product_options_att_label">' . __( 'Quantity', 'mp' ) . '</strong>
					<div class="mp_form_field mp_product_options_att_field">
						'. $this->attribute_input_fields( true, false, $selected_variation ) .'
					</div><!-- end mp_product_options_att_field -->
				</div><!-- end mp_product_options_att -->
			</div><!-- end mp_product_options_atts -->';


		/**
		 * Filter the attribute fields
		 *
		 * @since 3.0
		 *
		 * @param string The current html.
		 * @param MP_Product The current MP_Product object.
		 */
		$html = apply_filters( 'mp_product/attribute_fields', $html, $this );
		$html = apply_filters( 'mp_product/attribute_fields/' . $this->ID, $html, $this );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	public function attribute_input_fields( $is_variation = false, $qty = false, $product = false ) {
		if( ! $product ){
			$product = new MP_Product( $this->ID );
		}

		$product_id = $product->ID;

		$input_id = 'mp_product_options_att_quantity';

		$per_order_limit = get_post_meta( $product_id, 'per_order_limit', true );

		$max              	= '';
		$product_quantity 	= ( $qty ) ? $qty : 1;
		$min_value 			= 1;
		$disabled 			= '';
		$error				= '';

		$max_max = $this->max_product_quantity( $product_id, false, false );			
		extract($this->max_product_quantity( $product_id, false, true ), EXTR_PREFIX_ALL, "max");

		$max = 'max="' . esc_attr( $max_qty ) . '" ';

		/**
		 * Filter the out of stock alert message
		 *
		 * @since 3.0
		 *
		 * @param string The default message.
		 * @param MP_Product The product that is out of stock.
		 */
		$out_of_stock_msg = apply_filters( 'mp_product/out_of_stock_alert', sprintf( __( 'We\'re sorry, we only have %d of this item in stock right now.', 'mp' ), $max_max ), $product );

		/**
		 * Filter the order limit alert message
		 *
		 * @since 3.0
		 *
		 * @param string The default message.
		 * @param MP_Product The product that is out of order limit.
		 */
		$order_limit_msg = apply_filters( 'mp_product/order_limit_alert', sprintf ( __( 'This product has an order limit of %d.', 'mp' ), $max_max ), $product );

		$max_msg = ( $max_reason == 'inventory' ) ? $out_of_stock_msg : $order_limit_msg;

		$max .= 'data-msg-max="' . $max_msg;

		$max_msg_2 = "";

		if( $max_max !== $max_qty ) {
			if( $max_qty > 0 ){
				$max_msg_2 = " " . __('You can only add {0} to cart.', 'mp');
			}
			else {
				$max_msg_2 = " " . __('You can not add more items to cart.', 'mp');
			}
		}

		$max .= $max_msg_2;

		$max .= '"';

		if ( $max_qty == 0 ) {
			$min_value        = ( !$is_variation ) ? 0 : 1;
			$product_quantity = ( $qty ) ? $qty : 0;
			if( !$is_variation ){
				$disabled	  = 'disabled';
			}
		}

		if( (! mp_doing_ajax() && ! $product->in_stock( 1, true ) ) || $max_qty == 0 ){
			$error = '<label class="mp_form_label mp_product_options_att_input_label" for="' . $input_id . '"><span id="mp_product_options_att_quantity-error" class="mp_form_input_error">' . $max_msg . $max_msg_2 . '</span></label>';
		}
		

		return $error . '<input id="' . $input_id . '" class="mp_form_input mp_form_input-qty required digits" min="' . esc_attr( $min_value ) . '" ' . $max . ' type="number" name="product_quantity" value="' . $product_quantity . '" ' . $disabled . '>';
	}

	/**
	 * Get a parent product if product is a variation
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @return false/MP_Product
	 */
	public function get_parent() {
		if ( is_null( $this->_post ) ) {
			return false;
		}
		if ( is_null( $this->_post->post_parent )|| $this->_post->post_parent == 0 ) {
			return false;
		}

		return new MP_Product( $this->_post->post_parent );
	}

	/**
	 * Get a specific variation by it's index
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param int $index Optional.
	 */
	public function get_variation( $index = 0 ) {
		$variations = $this->get_variations();

		return mp_arr_get_value( $index, $variations );
	}

	/**
	 * Get product default variation
	 *
	 * @since 3.0
	 * @access public
	 * @return false/MP_Product An MP_Product objects.
	 */
	public function get_default_variation() {
		if( ! $this->has_variations() ){
			return false;
		}

		if ( ! is_null( $this->_default_variation ) ) {
			return $this->_default_variation;
		}

		return $this->set_default_variation();
	}

	/**
	 * Set product default variation
	 *
	 * @since 3.0
	 * @access public
	 * @return false/MP_Product An MP_Product objects.
	 */
	public function set_default_variation() {
		if( ! $this->has_variations() ){
			return false;
		}

		if ( ! is_null( $this->_default_variation ) ) {
			return $this->_default_variation;
		}

		$default_variation = intval( $this->get_meta( 'default_variation', false ) );

		if ( ! is_null( $this->_variation_ids ) && $default_variation ) {
			$index = array_search( $default_variation,  $this->_variation_ids );
			if( $index && isset( $this->_variations[$index] ) ){
				$this->_default_variation = $this->_variations[ $index ];
				if( $index !== 0){
					unset($this->_variations[ $index ]);
					array_unshift($this->_variations, $this->_default_variation);
					unset($this->_variation_ids[ $index ]);
					array_unshift($this->_variation_ids, $default_variation);
				}
			}
		}

		return $this->_default_variation;
	}

	/**
	 * Get product variation ids
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_variation_ids() {
		if ( ! is_null( $this->_variation_ids ) ) {
			return $this->_variation_ids;
		}

		$this->get_variations();

		return $this->_variation_ids;
	}

	/**
	 * Get product variations
	 *
	 * @since 3.0
	 * @access public
	 * @return array An array of MP_Product objects.
	 */
	public function get_variations() {
		if ( ! is_null( $this->_variations ) ) {
			return $this->_variations;
		}

		$this->_variations = array();

		if ( $this->get_parent() != false ) {
			return $this->_variations;
		}

		if ( ! $this->get_meta( 'has_variations' ) ) {
			return $this->_variations;
		}

		$identifier = $this->ID;
		$transient_key = 'mp-get-variations-' . $identifier;

		//Check if variations data exist on transient, if not do the query and save result on transient
		//if ( false === ( $this->_variations = get_transient( $transient_key ) ) ) {
		if ( false == ( $this->_variations = get_transient( $transient_key ) ) ) {
			$this->_variations = array();
			$args = array(
				'post_type'      => MP_Product::get_variations_post_type(),
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_parent'    => $this->ID,
			);

			$query = new WP_Query( $args );

			$this->_variation_ids = array();

			while ( $query->have_posts() ) : $query->the_post();
				$this->_variations[] = $variation = new MP_Product();
			endwhile;

			//Save variations data on a transient, transient key is 'mp-get-variations-{product_id}'
			set_transient('mp-get-variations-' . $this->ID, $this->_variations, 12 * 60 * 60);
		}

		foreach ($this->_variations as $variation) {
			$this->_variation_ids[] = $variation->ID;
		}

		//WP will do an update_meta_cache query for each variations,
		//to avoid that, we pre-do a update_meta_cache for all variations in only one query.
		//
		update_meta_cache( 'post', $this->_variation_ids );

		// Resort _variations && _variation_ids arrays by putting default variation on the top.
		$this->set_default_variation();

		wp_reset_postdata();

		return $this->_variations;
	}

	/**
	 * Get variations by the given attributes
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $attributes An array of attribute arrays in $taxonomy => $term_id format.
	 * @param int If only one variation is desired set the index that you would like to retrieve.
	 *
	 * @return array/MP_Product
	 */
	public function get_variations_by_attributes( $attributes, $index = null ) {
		$identifier = $this->ID;
		$cache_key = 'mp-get-variations-by-attributes-' . $identifier;

		// Add attributes names to cache key to make it unqiue
		foreach ( $attributes as $attribute_key	 => $attribute_value ) {
			$cache_key .= '-' . $attribute_key;
		}

		//Check if data exist on cache, if not do the query and cache results
		$cache     = wp_cache_get( $cache_key, 'mp_product' );
		if ( false !== $cache ) {
			if ( is_null( $index ) ) {
				return $cache;
			} else {
				return mp_arr_get_value( $index, $cache );
			}
		}

		$tax_query = array();
		foreach ( $attributes as $taxonomy => $term_id ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'terms'    => $term_id,
			);
		}

		$query = new WP_Query( array(
			'post_type'      => MP_Product::get_variations_post_type(),
			'posts_per_page' => - 1,
			'post_parent'    => $this->ID,
			'tax_query'      => array( 'relation' => 'AND' ) + $tax_query,
		) );

		$variations = array();
		while ( $query->have_posts() ) : $query->the_post();
			$variations[] = new MP_Product();
		endwhile;

		wp_reset_postdata();
		wp_cache_set( $cache_key, $variations, 'mp_product' );

		return ( is_null( $index ) ) ? $variations : mp_arr_get_value( $index, $variations );
	}

	/**
	 * Get product weight
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param float $default The default weight if a weight isn't set for the product.
	 *
	 * @return float
	 */
	public function get_weight() {
		$weight = 0;

		if ( $this->is_download() ) {
			return $weight;
		}

		$lbs = (float) $this->get_meta( 'weight_pounds', 0 );
		$oz  = (float) $this->get_meta( 'weight_ounces', 0 );

		if ( $lbs || $oz ) {
			$weight = $lbs;

			if ( $oz > 0 ) {
				$weight += ( $oz / 16 );
			}
		}

		return $weight;
	}

	/**
	 * Get the product's ID for global cart
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public function global_id() {
		return mp_cart()->get_blog_id() . '.' . $this->ID;
	}

	/**
	 * Get the product price before tax
	 *
	 * @since 3.0
	 * @access public
	 * @return float
	 */
	public function before_tax_price() {
		$price = $this->get_price( 'lowest' );

		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
			// tax inclusve pricing is turned off - just return given price
			return $price;
		}

		$charge_tax = $this->get_meta( 'charge_tax' );
		$rate = $this->get_meta( 'special_tax_rate' );

		if ( empty( $charge_tax ) ) {
			$rate = mp_tax_rate();
		} else {
			if ( false !== strpos( $rate, '%' ) ) {
				// Special rate is a string percentage - convert to float value
				$rate = (float) preg_replace( '[^0-9.]', '', $rate );
				$rate = $rate / 100;
			} else {
				// Special rate is a fixed amount - simply subtract it from the item price
				return $price - $rate;
			}
		}

		return mp_before_tax_price( $price, $rate );
	}

	/*
	 * Displays the buy or add to cart button
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true.
	 * @param string $context Optional, options are list or single. Defaults to list.
	 * @param array $selected_atts Optional, the attributes that should be selected by default.
	 */

	public function buy_button( $echo = true, $context = 'list', $selected_atts = array(), $no_single = false, $mp_buy_button = false ) {
		$button = '';
		if ( $this->get_meta( 'product_type' ) == 'external' && ( $url = $this->get_meta( 'external_url' ) ) ) {
			$button = '<a class="mp_link-buynow" href="' . esc_url( $url ) . '">' . __( 'Buy Now &raquo;', 'mp' ) . '</a>';
		} elseif ( ! mp_get_setting( 'disable_cart' ) ) {
			$button = '<form id="mp-buy-product-' . $this->ID . '-form" class="mp_form mp_form-buy-product ' . ( $no_single ? 'mp_no_single' : '' ) . ' ' . ( $mp_buy_button ? 'mp_buy_button' : '' ) . '" method="post" data-ajax-url="' . mp_get_ajax_url( 'admin-ajax.php?action=mp_update_cart' ) . '" action="' . mp_cart_link( false, true ) . '">';

			if ( ! $this->in_stock() ) {
				$button .= '<span class="mp_no_stock">' . __( 'Out of Stock', 'mp' ) . '</span>';
			} else {
				$button .= '<input type="hidden" name="product_id" value="' . $this->ID . '">';
				$disabled = '';
				if( !$this->in_stock( 1, true ) ){
					$disabled	  = 'disabled';
				}
				if ( $context == 'list' ) {
					if ( $this->has_variations() ) {
						$button .= '<a class="mp_button mp_link-buynow mp_button-has_variations" data-href="' . admin_url( 'admin-ajax.php?action=mp_product_get_variations_lightbox&amp;product_id=' . $this->ID ) . '" href="' . $this->url( false ) . '">' . __( 'Choose Options', 'mp' ) . '</a>';
					} else if ( mp_get_setting( 'list_button_type' ) == 'addcart' ) {
						$button .= '<button ' . $disabled . ' class="mp_button mp_button-addcart" type="submit" name="addcart">' . __( 'Add To Cart', 'mp' ) . '</button>';
					} else if ( mp_get_setting( 'list_button_type' ) == 'buynow' ) {
						$button .= '<button ' . $disabled . ' class="mp_button mp_button-buynow" type="submit" name="buynow">' . __( 'Buy Now', 'mp' ) . '</button>';
					}
				} else {
					$button .= $this->attribute_fields( false, $selected_atts );

					if ( mp_get_setting( 'product_button_type' ) == 'addcart' ) {
						$button .= '<button ' . $disabled . ' class="mp_button mp_button-addcart" type="submit" name="addcart">' . __( 'Add To Cart', 'mp' ) . '</button>';
					} else if ( mp_get_setting( 'product_button_type' ) == 'buynow' ) {
						$button .= '<button ' . $disabled . ' class="mp_button mp_button-buynow" type="submit" name="buynow">' . __( 'Buy Now', 'mp' ) . '</button>';
					}
				}
			}

			$button .= '</form><!-- end mp-buy-product-form -->';
		}

		$button = apply_filters( 'mp_buy_button_tag', $button, $this->ID, $context, $selected_atts, $no_single );

		if ( $echo ) {
			echo $button;
		} else {
			return $button;
		}
	}

	/**
	 * Get the product content
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 */
	public function content( $echo = true ) {
		$content = $this->_post->post_content;

		if ( empty( $content ) && $this->is_variation() ) {
			$parent = new MP_Product( $this->post_parent );
			$content = $parent->post_content;
		}

		// $content = $this->_post->post_content;

		// if ( $this->has_variations() || $this->is_variation() ) {
		// 	$content = $this->get_variation()->post_content;
		// }

		$content = apply_filters( 'the_content', $content );

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

	/**
	 * Check if the product has content
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @return bool
	 */
	public function has_content() {
		if( !isset( $this->_post ) ){
			return false;
		}

		$content = $this->_post->post_content;

		if ( empty( $content ) && $this->is_variation() ) {
			$parent = new MP_Product( $this->post_parent );
			$content = $parent->post_content;
		}

		return ! empty( $content );
	}

	/**
	 * Get the product's excerpt
	 *
	 * @since 3.0
	 *
	 * @param string $excerpt_more Optional
	 * @param string $excerpt Optional
	 * @param string $content Optional
	 *
	 * @return string
	 */
	public function excerpt( $excerpt = null, $content = null, $excerpt_more = null ) {
		if ( is_null( $excerpt_more ) ) {
			$excerpt_more = ' <a class="mp_product_more_link" href="' . get_permalink( $this->ID ) . '">' . __( 'More Info &raquo;', 'mp' ) . '</a>';
		}

		if ( is_null( $excerpt ) ) {
			//this only uses in listing page, so we will use the main product excepts, not variants
			//$excerpt = $this->has_variations() ? $this->get_variation()->post_excerpt : $this->_post->post_excerpt;
			if ( $this->has_variations() ) {
				if ( strlen( $this->get_variation()->post_excerpt ) > 0 ) {
					$excerpt = $this->get_variation()->post_excerpt;
				} else {
					$excerpt = $this->_post->post_excerpt;
				}
			} else {
				$excerpt = $this->_post->post_excerpt;
			}
		}

		if ( is_null( $content ) ) {
			$content = $this->has_variations() ? $this->get_variation()->post_content : $this->_post->post_content;
		}

		if ( $excerpt ) {
			return apply_filters( 'get_the_excerpt', $excerpt ) . $excerpt_more;
		} else {
			$text           = strip_shortcodes( $content );
			$text           = str_replace( ']]>', ']]&gt;', $text );
			$text           = strip_tags( $text );
			$excerpt_length = mp_get_setting( 'excerpts_length', 55 );
			$words          = preg_split( "/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );

			if ( count( $words ) > $excerpt_length ) {
				array_pop( $words );
				$text = implode( ' ', $words );
				$text = $text . $excerpt_more;
			} else {
				$text = implode( ' ', $words );
			}

			$text = wpautop( $text );
		}

		if ( $this->has_variations() || $this->is_variation() ) {
			$text = mp_get_the_excerpt( $this->ID, apply_filters( 'mp_get_the_excerpt_length', 18 ), true );
		}

		/**
		 * Filter the product excerpt
		 *
		 * @since 3.0
		 *
		 * @param string $text
		 * @param string $excerpt
		 * @param string $content
		 * @param int $product_id
		 * @param string $excerpt_more Optional
		 */

		return apply_filters( 'mp_product/excerpt', $text, $excerpt, $content, $this->id, $excerpt_more );
	}

	/**
	 * Get the product's content tab labels
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 */
	public function content_tab_labels( $echo = true ) {
		$html = '
			<ul class="mp_product_tab_labels">';

		$index = 0;
		foreach ( $this->content_tabs as $slug => $label ) {
			$html .= '
				<li class="mp_product_tab_label' . ( ( $index == 0 ) ? ' current' : '' ) . '"><a class="mp_product_tab_label_link ' . esc_attr( $slug ) . '" href="#' . esc_attr( $slug ) . '-' . $this->ID . '">' . $label . '</a></li>';
			$index ++;
		}

		$html .= '
			</ul><!-- end mp_product_tab_labels -->';

		/**
		 * Filter the product tabs html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current HTML markup.
		 * @param MP_Product $this The current product object.
		 */
		$html = apply_filters( 'mp_product/content_tab_labels', $html, $this );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Get custom image tag
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 * @param string /int/array $size
	 * @param array $attributes
	 */
	public function image_custom( $echo = true, $size = 'large', $attributes = array() ) {
		$thumb_id   = ( $this->has_variations() ) ? get_post_thumbnail_id( $this->get_variation()->ID ) : get_post_thumbnail_id( $this->ID );
		$product_id = ( $this->has_variations() ) ? $this->get_variation()->ID : $this->ID;

		if ( isset( $attributes ) && isset( $attributes['show_thumbnail_placeholder'] ) ) {
			$show_thumbnail_placeholder = (bool) $attributes['show_thumbnail_placeholder'];
		} else {
			$show_thumbnail_placeholder = true;
		}

		$show_thumbnail_placeholder = (bool) apply_filters( 'mp_product_image_show_placeholder', $show_thumbnail_placeholder, $product_id );

		unset( $attributes['show_thumbnail_placeholder'] );

		if ( $intsize = intval( $size ) ) {
			$size = array( $intsize, $intsize );
		}

		if ( empty( $thumb_id ) ) {
			$heigt      = ( is_array( $size ) ) ? $intsize : get_option( 'thumbnail_size_h' );
			$attributes = array_merge( array(
				'src'    => apply_filters( 'mp_default_product_img', mp_plugin_url( 'ui/images/default-product.png' ) ),
				'width'  => ( is_array( $size ) ) ? $intsize : get_option( 'thumbnail_size_w' ),
				'height' => ( is_array( $size ) ) ? $intsize : get_option( 'thumbnail_size_h' ),
				'style'  => 'max-height: ' . $heigt . 'px;'  // Keeping it nice
			), $attributes );
		} else {
			$data       = wp_get_attachment_image_src( $thumb_id, $size, false );
			$attributes = array_merge( array(
				'src'    => $data[0],
				'width'  => $data[1],
				'height' => $data[2]
			), $attributes );
		}
		if ( ! empty( $thumb_id ) || $show_thumbnail_placeholder ) {
			$img = '<img' . mp_array_to_attributes( $attributes ) . '>';
		} else {
			$img = '';
		}

		if ( $echo ) {
			echo $img;
		} else {
			return $img;
		}
	}

	/**
	 * Get the display product price
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 */
	public function display_price( $echo = true, $context = '' ) {
		$price   = $this->get_price();


		$snippet = '<!-- MP Product Price --><div class="mp_product_price" itemtype="http://schema.org/Offer" itemscope="" itemprop="offers">';

		if ( $this->has_variations() ) {
			if ( $this->on_sale() ) {
				// Get price range
				if ( $price['lowest'] != $price['highest'] ) {
					$snippet .= '<span class="mp_product_price-sale">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ) . ' - ' . mp_format_currency( '', $this->manage_price_tax( $price['highest'] ) ) . $this->display_tax_string( false ) . '</span>';
				} else {
					$snippet .= '<span class="mp_product_price-sale">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ) . $this->display_tax_string( false ) . '</span>';
				}
				// Get sale price range
				if ( $price['lowest_regular'] != $price['highest_regular'] ) {
					$snippet .= '<span class="mp_product_price-normal mp_strikeout">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest_regular'] ) ) . ' - ' . mp_format_currency( '', $this->manage_price_tax( $price['highest_regular'] ) ) . $this->display_tax_string( false ) . '</span>';
				} else {
					$snippet .= '<span class="mp_product_price-normal mp_strikeout">' . mp_format_currency( '', $this->manage_price_tax( ( $price['regular'] * $this->qty ) ) ) . $this->display_tax_string( false ) . '</span>';
				}

			}
			else{
				// Get price range
				if ( $price['lowest'] != $price['highest'] ) {
					$snippet .= '<span class="mp_product_price-normal">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ) . ' - ' . mp_format_currency( '', $this->manage_price_tax( $price['highest'] ) ) . $this->display_tax_string( false ) . '</span>';
				} else {
					$snippet .= '<span class="mp_product_price-normal">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ) . $this->display_tax_string( false ) . '</span>';
				}
			}
		} elseif ( $this->on_sale() ) {
			$amt_off = mp_format_currency( '', ( $this->manage_price_tax( $price['highest'] ) - $this->manage_price_tax( $price['lowest'] ) ) * $this->qty ) . $this->display_tax_string( false );

			if ( $this->qty > 1 ) {
				$snippet .= '<span class="mp_product_price-extended">' . mp_format_currency( '', $this->manage_price_tax( ( $price['lowest'] * $this->qty ) ) ) . $this->display_tax_string( false ) . '</span>';
				$snippet .= '<span class="mp_product_price-each" itemprop="price">(' . sprintf( __( '%s each', 'mp' ), mp_format_currency( '', $this->manage_price_tax( $price['sale']['amount'] ) ) ) . ') ' . $this->display_tax_string( false ) . '</span>';
			} else {
				$snippet .= '<span class="mp_product_price-sale" itemprop="price">' . mp_format_currency( '', $this->manage_price_tax( $price['sale']['amount'] ) ) . $this->display_tax_string( false ) . '</span>';
			}

			$snippet .= '<span class="mp_product_price-normal mp_strikeout">' . mp_format_currency( '', $this->manage_price_tax( ( $price['regular'] * $this->qty ) ) ) . $this->display_tax_string( false ) . '</span>';

			/* if ( ($end_date	 = $price[ 'sale' ][ 'end_date' ]) && ($days_left	 = $price[ 'sale' ][ 'days_left' ]) ) {
			  $snippet .= '<strong class="mp_savings_amt">' . sprintf( __( 'You Save: %s', 'mp' ), $amt_off ) . sprintf( _n( ' - only 1 day left!', ' - only %s days left!', $days_left, 'mp' ), $days_left ) . '</strong>';
			  } else {
			  $snippet .= '<strong class="mp_savings_amt">' . sprintf( __( 'You Save: %s', 'mp' ), $amt_off ) . '</strong>';
			  } */
		} else {
			if ( $this->qty > 1 ) {
				$snippet .= '<span class="mp_product_price-extended">' . mp_format_currency( '', $this->manage_price_tax( ( $price['lowest'] * $this->qty ) ) ) . $this->display_tax_string( false ) . '</span>';
				$snippet .= '<span class="mp_product_price-each" itemprop="price">(' . sprintf( __( '%s each', 'mp' ), mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ) ) . ') ' . $this->display_tax_string( false ) . '</span>';
			} else {
				$snippet .= '<span class="mp_product_price-normal" itemprop="price">' . mp_format_currency( '', $this->manage_price_tax( $price['lowest'] ) ). $this->display_tax_string( false ) . '</span>';
			}
		}

		$snippet .= '</div><!-- end mp_product_price -->';

		/**
		 * Filter the display price of the product
		 *
		 * @since 3.0
		 *
		 * @param string The current display price text
		 * @param array The current price object
		 * @param int The product ID
		 */
		$snippet = apply_filters( 'mp_product/display_price', $snippet, $price, $this->ID );

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Add tax to the product price
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param double $price
	 *
	 * @return float|int
	 */
	public function manage_price_tax( $price ) {
		$tax_rate = mp_tax_rate();

		if( ! empty( $tax_rate ) ) {
			$tax_inclusive = mp_get_setting( 'tax->tax_inclusive', 0 );
			$include_tax_to_price = mp_get_setting( 'tax->include_tax', 1 );
			$special_tax = get_post_meta( $this->ID, 'charge_tax', true );
			$special_fixed_tax = false;

			//Set tax rate to special tax
			if( $special_tax ) {
				$tax_rate = get_post_meta( $this->ID, 'special_tax_rate', true );
				if( substr( $tax_rate, -1 ) == '%' ) {
					$tax_rate = rtrim( $tax_rate, "%" ) / 100;
				} else {
					$special_fixed_tax = true;
				}
			} else {
				$tax_rate = mp_get_setting( 'tax->rate', '' );
			}

			if ( ! $this->is_download() ) {
				//Price with Tax added
				if( $tax_inclusive != 1 && $include_tax_to_price == 1 ) {
					if( $special_fixed_tax ) {
						$price = $price + $tax_rate;
					} else {
						$price = $price + ($price * $tax_rate);
					}
				}
				//Price with Tax excluded
				if( $tax_inclusive == 1 && $include_tax_to_price != 1) {
					$taxDivisor = 1 + $tax_rate;
					$price = $price / $taxDivisor;
				}
			} elseif ( mp_get_setting( 'tax->tax_digital' ) && $this->is_download() ) {
				//Calculate price when special price & download product
				if( $tax_inclusive != 1 ) {
					if( $special_fixed_tax ) {
						$price = $price + $tax_rate;
					} else {
						$price = $price + ($price * $tax_rate);
					}
				}
			}
		} // End if().

		return $price;
	}

	/**
	 * Display (tax incl.) or (tax excl.)
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function display_tax_string( $echo = false ) {
		$tax_inclusive = mp_get_setting( 'tax->tax_inclusive', 0 );
		$include_tax_to_price = mp_get_setting( 'tax->include_tax', 1 );
		$tax_label = mp_get_setting( 'tax->tax_label', 0 );
		$string = '';

		if( $tax_label != 1) {
			return $string;
		}

		if( $tax_inclusive != 1 && $include_tax_to_price != 1 ) {
			$string = '<span class="exclusive_tax"> ' . __('(tax excl.)', 'mp') . '</span>';
		} elseif( $tax_inclusive == 1 ) {
			if( $include_tax_to_price != 1 )  {
				$string = '<span class="exclusive_tax"> ' . __('(tax excl.)', 'mp') . '</span>';
			} else {
				$string = '<span class="inclusve_tax"> ' . __('(tax incl.)', 'mp') . '</span>';
			}
		} elseif( $tax_inclusive != 1 && $include_tax_to_price == 1 ) {
			$string = '<span class="exclusive_tax"> ' . __('(tax incl.)', 'mp') . '</span>';
		}

		if ( $echo ) {
			echo $string;
		} else {
			return $string;
		}
	}

	/**
	 * Get the product's download url - if applicable
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $order_id The order ID for the download.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 *
	 * @return String/Array - The url or an array of URLs if the product has multiple files
	 */
	public function download_url( $order_id, $echo = true ) {
		$url = false;

		if ( $this->is_download() ) {
			$url = add_query_arg( 'orderid', $order_id, $this->url( false ) );

			//Check if the download has many files
			$files = $this->get_meta( 'file_url' );

			if( is_array( $files ) ){

				//If we have more than one produce file, add them to a list
				if( count( $files ) > 0 ){

					$file_urls 	= array();
					$count 		= 1;
					foreach( $files as $file_url ){
						$single_url 	= add_query_arg( 'orderid', $order_id, $this->url( false , $count) );
						$file_urls[] 	= $single_url;
						$count++;
					}
					$url = $file_urls;
				}

			}
			
		}

		/**
		 * Filter the product's download url
		 *
		 * @since 3.0
		 *
		 * @param string $url The current url.
		 * @param string $order_id The order ID.
		 */

		return apply_filters( 'mp_product/download_url', $url, $order_id );
	}

	/**
	 * Determine if product has variations
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function has_variations() {
		$variations = $this->get_variations();

		return ( ! empty( $variations ) );
	}

	/**
	 * Determine if product is featured
	 *
	 * @since 3.0.0.8
	 * @access public
	 * @return bool
	 */
	public function is_featured() {
		return $this->get_meta( 'featured' );
	}

	/**
	 * Determine if product is on sale
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function on_sale() {
		if ( ! is_null( $this->_on_sale ) ) {
			return $this->_on_sale;
		}

		$has_sale   = $this->get_meta( 'has_sale' );
		$sale_price = $this->get_meta( 'sale_price_amount' );
		$on_sale    = false;

		//If product is parent and has at least one sale variation then product is on sale
		if( $this->has_variations() ){
			$variations = $this->get_variations();
			foreach ($variations as $variation) {
				$on_sale |= $variation->on_sale();
			}
		}else{
			if ( $has_sale && $sale_price ) {
				$start_date = $this->get_meta( 'sale_price_start_date', false, true );
				$end_date   = $this->get_meta( 'sale_price_end_date', false, true );
				$time       = current_time( 'Y-m-d' );
				$on_sale    = true;

				if ( $start_date && $time < $start_date ) {
					$on_sale = false;
				} elseif ( $end_date && $time > $end_date ) {
					$on_sale = false;
				}
			}
		}

		/**
		 * Filter the on sale flag
		 *
		 * @since 3.0
		 *
		 * @param bool $on_sale The default on-sale flag.
		 * @param MP_Product $this The current product.
		 */
		$this->_on_sale = apply_filters( 'mp_product/on_sale', $on_sale, $this );

		return $this->_on_sale;
	}

	/**
	 * Get a product's price
	 *
	 * Will return the product's regular and sale price - if applicable.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $what Optional, the subset of the price array to be returned.
	 *
	 * @return array/float
	 */
	public function get_price( $what = null ) {
		if ( ! is_null( $this->_price ) ) {
			if ( ! is_null( $what ) ) {
				return mp_arr_get_value( $what, $this->_price );
			}

			return $this->_price;
		}

		$price = array(
			'regular' => (float) $this->get_meta( 'regular_price' ),
			'lowest'  => (float) $this->get_meta( 'regular_price' ),
			'highest' => (float) $this->get_meta( 'regular_price' ),
			'sale'    => array(
				'amount'     => false,
				'start_date' => false,
				'end_date'   => false,
				'days_left'  => false,
				'percentage' => false,
			),
		);

		if ( $this->has_variations() ) {
			$variations = $this->get_variations();
			$prices     = array();
			$variations_prices    = array();

			foreach ( $variations as $variation ) {
				$price = $variation->get_price();

				if ( $variation->on_sale() ) {
					$prices[] = $price['sale']['amount'];
					$variations_prices[] = $price['regular'];
				} else {
					$prices[] = $price['regular'];
				}
			}

			if( !empty($variations_prices) ) {
				$price['lowest_regular']  = (float) min( $variations_prices );
				$price['highest_regular'] = (float) max( $variations_prices );
			}

			$price['lowest']  = (float) min( $prices );
			$price['highest'] = (float) max( $prices );
		} elseif ( $this->on_sale() && ( $sale_price = $this->get_meta( 'sale_price_amount' ) ) ) {
			$start_date_obj = new DateTime( $this->get_meta( 'sale_price_start_date', date( 'Y-m-d' ), true ) );
			$days_left      = false;

			if ( method_exists( $start_date_obj, 'diff' ) ) { // The diff method is only available PHP version >= 5.3
				$end_date_obj = new DateTime( $this->get_meta( 'sale_price_end_date', date( 'Y-m-d' ), true ) );
				$diff         = $start_date_obj->diff( $end_date_obj );
				$days_left    = $diff->d;

				/**
				 * Filter the maximum number of days before the "only x days left" nag shows
				 *
				 * @since 3.0
				 *
				 * @param int The default number of days
				 */
				$days_limit = apply_filters( 'mp_product/get_price/days_left_limit', 7 );

				if ( $days_left > $days_limit ) {
					$days_left = false;
				}
			}

			$price['lowest'] = (float) $sale_price;
			$price['sale']   = array(
				'amount'     => (float) $sale_price,
				'start_date' => $this->get_meta( 'sale_price_start_date', false ),
				'end_date'   => $this->get_meta( 'sale_price_end_date', false ),
				'days_left'  => $days_left,
				'percentage' => $this->get_meta( 'sale_price_percentage', false ),
			);
		}

		/**
		 * Filter the price array
		 *
		 * @since 3.0
		 *
		 * @param array The current pricing array.
		 * @param MP_Product The current product.
		 */
		$this->_price = apply_filters( 'mp_product/get_price', $price, $this );

		if ( mp_arr_get_value( 'sale->amount', $price ) != mp_arr_get_value( 'sale->amount', $this->_price ) ) {
			/* Filter changed sale price so let's flip the on-sale flag so the sale
			  price will show up accordingly */
			$this->_on_sale = true;
		}

		if ( ! is_null( $what ) ) {
			return mp_arr_get_value( $what, $this->_price );
		}

		return $this->_price;
	}

	/**
	 * Get the product's lowest price
	 *
	 * @since 3.0
	 * @access public
	 * @return float The product's lowest price.
	 */
	public function price_lowest() {
		$price = $this->get_price();

		return $price['lowest'];
	}


    /**
     * Get related products
     *
     * @since 3.0
     *
     * @param array $args {
     *        Optional, an array of arguments.
     *
     *        @type string $relate_by Optional, how to relate the products - either category, tag, or both.
     *        @type bool $echo Optional, echo or return.
     *        @type int $limit . Optional, the number of products to retrieve.
     *        @type string $view . Optional, how to display related products - either grid or list.
     * }
     * @param bool $return_bool
     * @return bool|mixed|string|void
     */
	public function related_products( $args = array(), $return_bool = false ) {
		$html = '';
		$args = array_replace_recursive( array(
			'relate_by' => mp_get_setting( 'related_products->relate_by' ),
			'echo'      => false,
			'limit'     => mp_get_setting( 'related_products->show_limit' ),
			'view'      => mp_get_setting( 'related_products->view' ),
		), $args );

		extract( $args );

		$query_args = array(
			'post_type'      => MP_Product::get_post_type(),
			'posts_per_page' => intval( $limit ),
			'post__not_in'   => array( ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID )
		);

		$related_specified_products_enabled = true;

		$related_specified_products = $this->get_meta( 'related_products' );

		if ( is_array( $related_specified_products ) && $related_specified_products[0] == '' ) {
			$related_specified_products_enabled = false;
		}

		// If there are some manual related products for this item
		if ( '' !== $related_specified_products && $related_specified_products_enabled ) {
			$query_args['post__in'] = $related_specified_products;
		}
		// Else, try to see if there are some category and/or tag related products for this item
		else {
			$post_id = ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID;
			$count   = 0;

			if ( 'category' != $relate_by ) {
				$terms                     = get_the_terms( $post_id, 'product_tag' );
				$ids                       = isset( $terms ) && is_array( $terms ) && ! is_wp_error( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();

				// If the product has some tags, add these to the Query
				if ( !empty( $ids ) ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'product_tag',
						'terms'    => $ids,
					);
					$count ++;
				}
			}

			if ( 'tags' != $relate_by ) {
				$terms                     = get_the_terms( $post_id, 'product_category' );
				$ids                       = isset( $terms ) && is_array( $terms ) && ! is_wp_error( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();

				// If the product has some categories, add these to the Query
				if ( !empty( $ids ) ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'product_category',
						'terms'    => $ids,
					);
					$count ++;
				}
			}

			if ( $count > 1 ) {
				$query_args['tax_query']['relation'] = 'AND';
			}

			// There are no related products
			if ( $count === 0 ) {
				if ( $return_bool ) {
					return false;
				}
				else if ( ! $echo ) {
					return '';
				}
				else {
					echo '';
					return;
				}
			}
		}

		//Serialize $query_args to create a unqiue cache key for each related-products query
		$identifier = md5( maybe_serialize( $query_args ) );
		$cache_key = 'mp-related-products-' . $identifier;

		//Check if data exist on cache, if not do the query and cache results
		if ( false === ( $product_query = wp_cache_get( $cache_key, 'mp_product' ) ) ) {
			$product_query = new WP_Query( $query_args );
			wp_cache_set( $cache_key, $product_query, 'mp_product' );
		}

		if ( $product_query->have_posts() ) {
			if ( $return_bool ) {
				return true;
			} else {
				switch ( $view ) {
					case 'grid' :
						$html .= _mp_products_html_grid( $product_query, true );
						break;

					case 'list' :
						$html .= _mp_products_html_list( $product_query );
						break;
				}
			}
		} else {
			if ( $return_bool ) {
				return false;
			} else {
				$html .= wpautop( __( '<p class="mp_related_products_empty_message">There are no related products for this item.</p>', 'mp' ) );
			}
		}

		/**
		 * Filter the related products html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current html.
		 * @param MP_Product $this The current product object.
		 * @param WP_Query $product_query The WP_Query object used to populate the related products.
		 * @param array $args The array of arguments that were passed to the method.
		 */
		$html = apply_filters( 'mp_product/related_products', $html, $this, $product_query, $args );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Set price
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $price The pricing array.
	 */
	public function set_price( $price ) {
		$this->_price = $price;
	}

	/**
	 * Get the special tax amount for the item
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to return.
	 *
	 * @return float The special tax amount for the item. False if item doesn't have a special rate.
	 */
	public function special_tax_amt( $echo = false ) {
		$special_tax_rate = $this->get_meta( 'special_tax_rate', '' );

		if ( strlen( trim( $special_tax_rate ) ) == 0 || $this->get_meta( 'charge_tax' ) !== '1' ) {
			return false;
		}

		if ( false !== strpos( $special_tax_rate, '%' ) ) {
			$special_tax_rate = (float) preg_replace( '[^0-9.]', '', $special_tax_rate );
			$special_tax_rate = $special_tax_rate / 100;
			$is_fixed_amt     = false;
		} else {
			$special_tax_rate = (float) $special_tax_rate;
			$is_fixed_amt     = true;
		}

		/**
		 * Filter the special tax rate
		 *
		 * @since 3.0
		 *
		 * @param float $special_rate The current special tax rate.
		 * @param bool $is_fixed_amt Whether the special tax rate is a fixed amount or not.
		 * @param MP_Product $this The current product object.
		 */
		$special_tax_rate = (float) apply_filters( 'mp_product/special_tax_rate', $special_tax_rate, $is_fixed_amt, $this );

		if ( $is_fixed_amt ) {
			$special_tax_amt = $special_tax_rate;
		} else {
			$special_tax_amt = $this->before_tax_price() * $special_tax_rate;
		}

		/**
		 * Filter the special tax price
		 *
		 * @since 3.0
		 *
		 * @param float $special_tax_amt The current special tax price.
		 * @param float $special_tax_rate The current special tax rate.
		 * @param bool $is_fixed_amt Whether the special tax rate is a fixed amount or not.
		 * @param MP_Product $this The current product object.
		 */

		return (float) apply_filters( 'mp_product/special_tax_amt', $special_tax_amt, $special_tax_rate, $is_fixed_amt, $this );
	}

	/**
	 * Get the current inventory in stock
	 *
	 * @since 3.0
	 * @access public
	 * @return int/array Number of units in stock or an array containing the number of units in stock for each variation.
	 */
	public function get_stock() {
		if ( $this->has_variations() ) {
			$stock      = array();
			$variations = $this->get_variations();
			foreach ( $variations as $variation ) {
				$stock[ $variation->ID ] = $variation->get_stock();
			}
		} else {
			$stock = $this->get_meta( 'inventory', 0 );
		}

		return $stock;
	}

	/*
	 * Get the product image
	 *
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list, single, or widget
	 * @param int $size An optional width/height for the image if contect is widget
	 * @param string $align The alignment of the image. Defaults to settings.
	 */

	public function image( $echo = true, $context = 'list', $size = null, $align = null, $show_empty = true ) {

		if ( empty( $context ) ) {
			$context = 'single';
		}

		/**
		 * Filter the post_id used for the product image
		 *
		 * @since 3.0
		 *
		 * @param int $post_id
		 */
		$post_id = apply_filters( 'mp_product_image_id', $this->ID );

		if ( $post_id != $this->ID ) {
			$this->ID    = $post_id;
			$this->_post = $post = get_post( $post_id );
		}

		$image_post_id = $this->ID;

		if ( $this->has_variations() ) {
			$image_post_id = $this->get_variation()->ID;
		}

		$post_thumbnail_id = get_post_thumbnail_id( $image_post_id );

		$show_thumbnail_placeholder = apply_filters( 'mp_product_image_show_placeholder', mp_get_setting( 'show_thumbnail_placeholder' ), $post_id );
		if ( (int) $show_thumbnail_placeholder == 1 ) {
			//do nothing, placeholder image should be shown
		} else {
			if ( ( ! is_numeric( $post_thumbnail_id ) ) ) {
				return '';
			}
		}

		$class = $title = $link = $img_align = '';

		$img_classes = array( 'mp_product_image_' . $context, 'photo' );

		$title = esc_attr( $this->title( false ) );

		if ( ! is_null( $align ) && false === strpos( $align, 'align' ) ) {
			$align = 'align' . $align;
		}

		switch ( $context ) {
			case 'list' :
				if ( ! mp_get_setting( 'show_thumbnail' ) ) {
					return '';
				}

				//size
				if ( is_int( $size ) ) {
					$size = array( $size, $size );
				} else {
					if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
						$size = array(
							mp_get_setting( 'list_img_size_custom->width' ),
							mp_get_setting( 'list_img_size_custom->height' )
						);
					} else {
						$size = mp_get_setting( 'list_img_size' );
					}
				}

				$link       = get_permalink( $this->ID );
				$link_class = ' class="mp_product_img_link"';
				$img_align  = is_null( $align ) ? mp_get_setting( 'image_alignment_list' ) : $align;
				break;

			case 'floating-cart' :
				$img_classes = array( 'mp-floating-cart-item-image' );

				if ( is_int( $size ) ) {
					$size = array( $size, $size );
				} else {
					$size = array( 50, 50 );
				}
				break;

			case 'single' :

				// size
				if ( is_int( $size ) ) {
					$size = array( $size, $size );
				} else {
					if ( mp_get_setting( 'product_img_size' ) == 'custom' ) {
						$size = array(
							mp_get_setting( 'product_img_size_custom->width' ),
							mp_get_setting( 'product_img_size_custom->height' )
						);
					} else {
						$size = mp_get_setting( 'product_img_size' );
					}
				}

				/*if ( mp_get_setting( 'product_img_size' ) == 'custom' ) {
					$size = array( mp_get_setting( 'product_img_size_custom->width' ), mp_get_setting( 'product_img_size_custom->height' ) );
				} else {
					$size = mp_get_setting( 'product_img_size' );
				}*/

				if ( mp_get_setting( 'disable_large_image' ) ) {
					$link = false;
				} else {
					$link  = $this->image_url( false, 'fullsize', false );
					$title = __( 'View Larger Image &raquo;', 'mp' );
				}

				$link_class = ' class="mp_product_image_link mp_lightbox"';
				$img_align  = is_null( $align ) ? mp_get_setting( 'image_alignment_single' ) : $align;
				break;

			case 'widget' :
				//size
				if ( is_int( $size ) ) {
					$size = array( $size, $size );
				} else {
					$size = array( 50, 50 );
				}

				//link
				$link       = get_permalink( $post_id );
				$link_class = ' class="mp_img_link"';
				break;
		}

		$image = get_the_post_thumbnail( $image_post_id, $size, array(
			'itemprop' => 'image',
			'class'    => implode( ' ', $img_classes ),
			'title'    => $title
		) );

		if ( ( $context == 'single' || $context == 'list' ) && ! empty( $image ) ) {
			//if single case, we will get the better graphic
			$image_id          = get_post_thumbnail_id( $image_post_id );
			$image_orignal_url = wp_get_attachment_image_src( $image_id, 'full' );
			$image_url         = mp_resize_image( $image_id, $image_orignal_url[0], $size );
			if ( $image_url ) {
				$atts = '';
				foreach (
					array(
						'itemprop' => 'image',
						'class'    => implode( ' ', $img_classes ),
						'title'    => $title
					) as $key => $value
				) {
					$atts .= " $key=\"$value\" ";
				}
				$image = sprintf( '<img src="%s" %s />', $image_url[0], $atts );
			}
		}

		if ( empty( $image ) ) {
			$thumbnail_placeholder = mp_get_setting( 'thumbnail_placeholder' );

			$placeholder_image = ! empty( $thumbnail_placeholder ) ? $thumbnail_placeholder : mp_plugin_url( 'ui/images/default-product.png' );

			if ( $context == 'floating-cart' ) {
				$image = '<img width="' . $size[0] . '" height="' . $size[1] . '" class="' . implode( ' ', $img_classes ) . '" src="' . apply_filters( 'mp_default_product_img', $placeholder_image ) . '">';
			} else {
				if ( ! is_array( $size ) ) {
					$size = array( get_option( $size . '_size_w' ), get_option( $size . '_size_h' ) );
				}

				$img_classes[] = 'wp-post-image';
				$image         = '<img width="' . $size[0] . '" height="' . $size[1] . '" itemprop="image" title="' . esc_attr( $title ) . '" class="' . implode( ' ', $img_classes ) . '" src="' . apply_filters( 'mp_default_product_img', $placeholder_image ) . '">';
			}
		}

		//force ssl on images (if applicable) http://wp.mu/8s7
		if ( is_ssl() ) {
			$image = str_replace( 'http://', 'https://', $image );
		}

		$snippet = '
			<div itemscope class="hmedia">
				<div style="display:none"><span class="fn">' . get_the_title( get_post_thumbnail_id() ) . '</span></div>'; //

		if ( $link ) {
			$snippet .= '<a rel="enclosure" id="mp-product-image-' . $post_id . '"' . $link_class . ' href="' . $link . '">' . $image . '</a>';
		} else {
			$snippet .= $image;
		}

		$snippet .= '
			</div>';

		$snippet = apply_filters( 'mp_product_image', $snippet, $context, $post_id, $size );

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get the product image url
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 * @param string /int $size
	 * @param string $view Either single or list. Optional.
	 */
	public function image_url( $echo = true, $size = null, $view = null, $id = false ) {
		if ( is_null( $size ) ) {
			$img_size = mp_get_image_size( $view );
			$size     = ( $img_size['label'] == 'custom' ) ? array(
				$size['width'],
				$size['height']
			) : $img_size['label'];
		} elseif ( $thesize = intval( $size ) ) {
			$size = array( $thesize, $thesize );
		}

		$post_id = $this->ID;
		$img_url = '';
		if ( $this->has_variations() ) {
			$post_id = $this->get_variation()->ID;
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$img_id  = get_post_thumbnail_id( $id ? $id : $post_id );
			$img_src = wp_get_attachment_image_src( $img_id, $size );

			if ( is_array( $img_src ) ) {
				$img_url = array_shift( $img_src );
			}
		}

		if ( empty( $img_url ) && mp_get_setting( 'show_thumbnail_placeholder' , 1 ) ) {
			/**
			 * Filter the default image url
			 *
			 * @since 3.0
			 *
			 * @param string The current default image url.
			 */
			$img_url = apply_filters( 'mp_product/default_img_url', mp_plugin_url( 'ui/images/default-product.png' ) );
		}

		/**
		 * Filter the product image url
		 *
		 * @since 3.0
		 *
		 * @param string $img_url The current image url.
		 * @param string /int $size
		 * @param string $view Either single or list
		 * @param MP_Product $this The current product object.
		 */
		$img_url = apply_filters( 'mp_product/image_url', $img_url, $size, $view, $this );

		if ( $echo ) {
			echo $img_url;
		} else {
			return $img_url;
		}
	}

	/**
	 * Check if product is in stock
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param int $qty The quantity to check against.
	 *
	 * @return bool
	 */
	public function in_stock( $qty = 1, $include_cart = false ) {
		$has_stock = false;

		if( $include_cart ) {
			$cart_quantity = 0;
			$cart_items	= mp_cart()->get_all_items();
			if ( isset( $cart_items[ get_current_blog_id() ][ $this->ID ] ) ) {
				$cart_quantity = (int) $cart_items[ get_current_blog_id() ][ $this->ID ];
			}
			$qty = $cart_quantity + $qty;
		}

		if ( $this->is_variation() ) {
			$out_of_stock_purchase = $this->get_meta( 'inv_out_of_stock_purchase' );
			if ( $this->get_meta( 'inventory_tracking' ) && $out_of_stock_purchase !== '1' ) {
				$inventory = $this->get_meta( 'inventory', 0 );
				$has_stock = ( $inventory >= $qty );
			} else {
				$has_stock = true;
			}
		} /* if ( $this->has_variations() ) {
		  $out_of_stock_purchase	 = $this->get_meta( 'inv_out_of_stock_purchase' );
		  $variations				 = $this->get_variations();
		  foreach ( $variations as $variation ) {
		  if ( $variation->get_meta( 'inventory_tracking' ) && $out_of_stock_purchase !== '1' ) {
		  $inventory	 = $variation->get_meta( 'inventory', 0 );
		  $has_stock	 = ( $inventory >= $qty );
		  } else {
		  $has_stock = true;
		  break;
		  }
		  }
		  } */ else {
			$out_of_stock_purchase = $this->get_meta( 'inv_out_of_stock_purchase' );
			if ( $this->get_meta( 'inventory_tracking' ) && $out_of_stock_purchase !== '1' ) {
				$inventory = $this->get_meta( 'inventory', 0 );
				$has_stock = ( $inventory >= $qty );
			} else {
				$has_stock = true;
			}
		}

		return $has_stock;
	}

	/**
	 * Check if the product is a digital download
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function is_download() {
		$product_type = $this->get_meta( 'product_type' );
		if ( $this->is_variation() ) {
			$parent       = new MP_Product( $this->_post->post_parent );
			$product_type = $parent->get_meta( 'product_type' );
		}

		return ( 'digital' == $product_type ); //&& $this->get_meta( 'file_url' )
	}

	/**
	 * Check if the product is a variation of another product
	 *
	 * @since 3.0
	 * @access public
	 */
	public function is_variation() {
		if ( is_null( $this->_post ) ) {
			return false;
		}

		return ( $this->_post->post_type == MP_Product::get_variations_post_type() );
	}

	/**
	 * Send low-stock notification
	 *
	 * @since 3.0
	 * @access public
	 */
	public function low_stock_notification() {
		$stock = $this->get_stock();

		if ( $this->get_meta( 'mp_stock_email_sent' ) && $stock > 0 ) {
			// Already sent - bail
			return;
		}

		// Only send the email once - we set this before doing anything else to avoid race conditions
		$this->update_meta( 'mp_stock_email_sent', true );

		$name = $this->title( false );
		if ( $this->is_variation() ) {
			$name .= ': ' . $this->get_meta( 'name' );
		}

		$subject = __( 'Low Product Inventory Notification', 'mp' );
		$msg     = __( 'This message is being sent to notify you of low stock of a product in your online store according to your preferences.<br /><br />', 'mp' );

		$msg .= __( 'Product: %s', 'mp' );
		$msg .= __( 'Current Inventory: %s', 'mp' );
		$msg .= __( 'Link: %s<br /><br />', 'mp' );

		$msg .= __( 'Edit Product: %s', 'mp' );
		$msg .= __( 'Notification Preferences: %s', 'mp' );
		$msg = sprintf( $msg, $name, number_format_i18n( $stock ), $this->url( false ), $this->url_edit( false ), admin_url( 'admin.php?page=mp-settings-general-misc#mp-settings-general-misc' ) );

		/**
		 * Filter the low stock notification message
		 *
		 * @since 3.0
		 *
		 * @param string $msg The current message text.
		 * @param int $this ->ID The product's ID.
		 */
		$msg = apply_filters( 'mp_low_stock_notification', $msg, $this->ID );

		mp_send_email( mp_get_store_email(), $subject, $msg );
	}

	/**
	 * Get a product's attribute
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $attribute
	 *
	 * @return int The term id of the specified attribute. False if no terms exist.
	 */
	public function get_attribute( $attribute ) {
		$terms = get_the_terms( $this->ID, $attribute );

		return ( is_array( $terms ) ) ? array_shift( $terms ) : false;
	}

	/**
	 * Get the product attributes
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 * @return array
	 */
	public function get_attributes() {
		global $wpdb;

		if ( ! is_null( $this->_attributes ) ) {
			return $this->_attributes;
		}

		$mp_product_atts   = MP_Product_Attributes::get_instance();
		$all_atts          = $mp_product_atts->get();
		$this->_attributes = array();

		$ids = array( $this->ID );

		if ( $this->has_variations() ) {
			$ids = $this->get_variation_ids();
		}

		$post_id            = ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID;
		$product_categories = get_the_terms( $post_id, 'product_category' );

		if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
			$product_categories = wp_list_pluck( $product_categories, 'term_id' );
		} /* else {
		  $product_categories = array( 0 );
		  } */

		// Get all product attributes for this product and it's variations
		$attributes = $wpdb->get_col( "
			SELECT DISTINCT t2.taxonomy
			FROM {$wpdb->term_relationships} AS t1
			INNER JOIN {$wpdb->term_taxonomy} AS t2 ON t1.term_taxonomy_id = t2.term_taxonomy_id
			WHERE t1.object_id IN (" . implode( ',', $ids ) . ")
			AND t2.taxonomy LIKE '" . MP_Product_Attributes::SLUGBASE . "%'"
		);

		$table_name = $wpdb->prefix . 'mp_product_attributes_terms';

		foreach ( $attributes as $k => $attribute ) {
			$attribute_id = $mp_product_atts->get_id_from_slug( $attribute );
			/* if ( !empty( $product_categories ) ) {
			  $exists = $wpdb->get_var( $wpdb->prepare( "
			  SELECT COUNT(*)
			  FROM {$table_name}
			  WHERE (attribute_id = %d AND term_id IN (" . implode( ',', $product_categories ) . "))
			  OR NOT EXISTS (SELECT attribute_id FROM {$table_name} WHERE attribute_id = %d)", $attribute_id, $attribute_id
			  ) );


			  if ( !$exists ) {
			  unset( $attributes[ $k ] );
			  }
			  } */
		}

		$terms        = wp_get_object_terms( $ids, array_values( $attributes ) );
		$terms_sorted = $mp_product_atts->sort( $terms );
		$names        = array();
		foreach ( $terms_sorted as $tax_slug => $terms ) {
			$tax_id = $mp_product_atts->get_id_from_slug( $tax_slug );

			foreach ( $terms as $term ) {
				if ( $att = $mp_product_atts->get_one( $tax_id ) ) {
					if ( ! in_array( $term->taxonomy, $names ) ) {
						mp_push_to_array( $this->_attributes, "{$term->taxonomy}->id", $tax_id );
						mp_push_to_array( $this->_attributes, "{$term->taxonomy}->name", $att->attribute_name );
						$names[] = $att->attribute_name;
					}

					mp_push_to_array( $this->_attributes, "{$term->taxonomy}->terms->{$term->term_id}", $term->name );
				}
			}
		}

		return $this->_attributes;
	}

	public static function get_variation_meta( $variation_id, $meta_key = '', $default = '' ) {
		if ( $meta_key == '' ) {
			$meta_value = get_post_meta( $variation_id );
		} else {
			$meta_value = get_post_meta( $variation_id, $meta_key, true );
		}

		if ( ( is_array( $meta_value ) && empty( $meta_value ) ) || strlen( trim( $meta_value ) ) == 0 ) {
			$meta_value = $default;
		}

		return $meta_value;
	}

	/**
	 * Get product meta value
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 *
	 * @return mixed
	 */
	public function get_meta( $name, $default = false, $raw = false ) {
		if ( ! $this->exists() ) {
			return apply_filters( 'mp_meta/product', $default, $this->ID, $name );
		}

		$value = false;
		if ( function_exists( 'get_field_value' ) ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value( $name, $this->ID, $raw );
		}

		if ( $value !== false && $value !== '' ) {
			return apply_filters( 'mp_meta/product', $value, $this->ID, $name );
		}

		// Try to use regular post meta
		$meta_val = get_post_meta( $this->ID, $name, true );
		if ( $meta_val !== '' ) {
			return apply_filters( 'mp_meta/product', $meta_val, $this->ID, $name );
		}

		return apply_filters( 'mp_meta/product', $default, $this->ID, $name );
	}

	/**
	 * Display product meta value
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 *
	 * @return mixed
	 */
	public function meta( $name, $default = false, $raw = false ) {
		echo $this->get_meta( $name, $default, $raw );
	}

	/**
	 * Get the product's url
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 * @param bool/integer $count - the current file count for multiple products
	 */
	public function url( $echo = true , $count = false ) {
		if ( $this->is_variation() ) {
			$url = get_permalink( $this->_post->post_parent ) . 'variation/' . $this->ID;
		} else {
			$url = get_permalink( $this->ID );
		}

		//Add number of file in array if count is passed
		if ( $count ){
			$url = add_query_arg( 'numb', $count, $url );
		}

		/**
		 * Filter the product's URL
		 *
		 * @since 3.0
		 *
		 * @param string $url The product's current URL.
		 * @param MP_Product $this The current product object.
		 */
		$url = apply_filters( 'mp_product/url', $url, $this );

		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}

	/**
	 * Get the product's edit url
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo Optional, defaults to true.
	 */
	public function url_edit( $echo = true ) {
		if ( $this->is_variation() ) {
			$url = get_edit_post_link( $this->_post->post_parent );
		} else {
			$url = get_edit_post_link( $this->ID );
		}

		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}

	/**
	 * Update product meta
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $name The name of the meta to update.
	 * @param mixed $value The new value.
	 */
	public function update_meta( $name, $value ) {
		if ( field_exists( $name, $this->ID ) ) {
			update_field_value( $name, $value, $this->ID );
		} else {
			update_post_meta( $this->ID, $name, $value );
		}
	}

	/**
	 * Get Pinterest PinIt button
	 *
	 * @since 3.0
	 *
	 * @param string $context
	 * @param bool $echo
	 */
	public function pinit_button( $context = 'single_view', $echo = false ) {
		$setting = mp_get_setting( 'social->pinterest->show_pinit_button' );

		if ( empty( $setting ) || $setting == '' ) {
			$setting = 'off';
		}

		$single_view_allowed = false;

		if ( $setting == 'all_view' ) {
			$single_view_allowed = true;
		} else {
			$single_view_allowed = false;
		}

		if ( $setting == 'single_view' ) {
			$single_view_allowed = true;
		}

		if ( $setting == 'off' ) {
			return '';
		}

		if ( $setting == 'single_view' && $context == 'all_view' ) {
			return '';
		}

		if ( $single_view_allowed && $context == 'single_view' ) {

		} else {
			if ( $setting == 'off' && ( $setting != $context ) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );

		if ( ! $product->has_variations() ) {
			$image_info = wp_get_attachment_image_src( get_post_thumbnail_id( $this->ID ), 'large' );
		} else {
			$variation  = $product->get_variation( 0 );
			$image_info = wp_get_attachment_image_src( get_post_thumbnail_id( $variation->ID ), 'large' );
		}


		$count_pos = ( $pos = mp_get_setting( 'social->pinterest->show_pin_count' ) ) ? $pos : 'none';
		$url       = add_query_arg( array(
			'url'         => get_permalink( $this->ID ),
			'description' => get_the_title( $this->ID ),
		), '//www.pinterest.com/pin/create/button/' );

		if ( $media = mp_arr_get_value( '0', $image_info ) ) {
			$url = add_query_arg( 'media', $media, $url );
		}

		$snippet = apply_filters( 'mp_pinit_button_link', '<a class="mp_pin_button" target="_blank" href="' . $url . '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png"></a>', $this->ID, $context );

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get Twitter button
	 *
	 * @since 3.0
	 *
	 * @param string $context
	 * @param bool $echo
	 */
	public function twitter_button( $context = 'single_view', $echo = false ) {
		$setting = mp_get_setting( 'social->twitter->show_twitter_button' );

		if ( empty( $setting ) || $setting == '' ) {
			$setting = 'off';
		}

		$single_view_allowed = false;

		if ( $setting == 'all_view' ) {
			$single_view_allowed = true;
		} else {
			$single_view_allowed = false;
		}

		if ( $setting == 'single_view' ) {
			$single_view_allowed = true;
		}

		if ( $setting == 'off' ) {
			return '';
		}

		if ( $setting == 'single_view' && $context == 'all_view' ) {
			return '';
		}

		if ( $single_view_allowed && $context == 'single_view' ) {

		} else {
			if ( $setting == 'off' && ( $setting != $context ) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );
		$url     = get_permalink( $this->ID );

		$snippet = "<a href='https://twitter.com/share' class='twitter-share-button' data-url='" . $url . "' data-count='none'>" . __( 'Tweet', 'mp' ) . "</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get Facebook button
	 *
	 * @since 3.0
	 *
	 * @param string $context
	 * @param bool $echo
	 */
	public function facebook_like_button( $context = 'single_view', $echo = false ) {
		$setting        = mp_get_setting( 'social->facebook->show_facebook_like_button' );
		$setting_action = mp_get_setting( 'social->facebook->action' );

		if ( empty( $setting ) || $setting == '' ) {
			$setting = 'off';
		}

		$action = 'like';

		if ( isset( $setting_action ) && ! is_null( $setting_action ) ) {
			$action = $setting_action;
		} else {
			$action = 'recommend';
		}

		$show_share    = 'false';
		$setting_share = mp_get_setting( 'social->facebook->show_share' );

		if ( isset( $setting_share ) && ! is_null( $setting_share ) ) {
			$show_share = $setting_share == 1 ? 'true' : 'false';
		} else {
			$show_share = 'false';
		}

		$single_view_allowed = false;

		if ( $setting == 'all_view' ) {
			$single_view_allowed = true;
		} else {
			$single_view_allowed = false;
		}

		if ( $setting == 'single_view' ) {
			$single_view_allowed = true;
		}

		if ( $setting == 'off' ) {
			return '';
		}

		if ( $setting == 'single_view' && $context == 'all_view' ) {
			return '';
		}

		if ( $single_view_allowed && $context == 'single_view' ) {

		} else {
			if ( $setting == 'off' && ( $setting != $context ) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );
		$url     = get_permalink( $this->ID );

		//$snippet = apply_filters( 'mp_facebook_like_button_link', '<a target="_blank" href="' . $url . '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" /></a>', $this->ID, $context );

		$snippet = "<div id='fb-root'></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = '//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.4';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<div class='fb-like' data-href='" . $url . "' data-layout='button' data-action='" . $action . "' data-show-faces='false' data-share='" . $show_share . "'></div>
";
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get the product title
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param bool $echo
	 */
	public function title( $echo = true ) {
		if ( $this->is_variation() ) {
			$title = get_the_title( $this->_post->post_parent ) . ': ' . $this->get_meta( 'name' );
		} else {
			$title = $this->_post->post_title;
		}

		/**
		 * Filter the product title
		 *
		 * @since 3.0
		 *
		 * @param string $title The product title.
		 * @param MP_Product $this The current product object.
		 */
		$title = apply_filters( 'mp_product/title', $title, $this );

		if ( $echo ) {
			echo $title;
		} else {
			return $title;
		}
	}

	/**
	 * Check if a product exists
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function exists() {
		return $this->_exists;
	}

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
	 * Attempt to set the internal WP_Post object
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param int /object/WP_Post $product
	 */
	protected function _get_post( $product ) {
		$this->_post = get_post( $product );

		if ( is_null( $this->_post ) ) {
			$this->_exists = false;
		} elseif ( get_post_status ( $this->_post->ID ) != 'publish' && $this->_post->post_type != self::get_post_type() && $this->_post->post_type != MP_Product::get_variations_post_type() ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type == MP_Product::get_variations_post_type() && FALSE === get_post_status( $this->_post->post_parent ) ) { // Check if variations parent exist
			$this->_exists = false;
		} else {
			$this->_exists = true;
			$this->ID      = $this->_post->ID;
		}
	}

	/**
	 * Set content tabs
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _set_content_tabs( $product ) {

		if ( ! is_admin() ) {
			$tabs = array();

			if ( mp_get_setting( 'related_products->show' ) ) {

				$args = array(
					'relate_by' => mp_get_setting( 'related_products->relate_by' ),
					'echo'      => false,
					'limit'     => mp_get_setting( 'related_products->show_limit' ),
					'view'      => mp_get_setting( 'related_products->view' ),
				);

				$related_products = $product->related_products( $args, true );

				if ( $related_products !== false ) {
					$tabs['mp-related-products'] = __( 'Related Products', 'mp' );
				}
			}

			/**
			 * Filter the product tabs array
			 *
			 * @since 3.0
			 *
			 * @param array $tabs The default product tabs.
			 * @param MP_Product $this The current product object.
			 */
			$tabs = (array) apply_filters( 'mp_product/content_tabs_array', $tabs, $this );

			// Make sure product overview tab is always at the beginning if not empty
			if( $product->has_content() ){
				$tabs = array( 'mp-product-overview' => __( 'Description', 'mp' ) ) + $tabs;
			}

			$this->content_tabs = $tabs;
		}
	}

}
