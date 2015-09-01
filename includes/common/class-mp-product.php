<?php

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
		$product_id	 = mp_get_get_value( 'product_id' );
		$product	 = new MP_Product( $product_id );

		/* $image	 = 'single';
		  $title	 = true;
		  $content = 'full';
		  $meta	 = true;

		  if ( !$product->exists() ) {
		  die( __( 'The product specified could not be found', 'mp' ) );
		  }

		  $variation = false;

		  if ( $variation_id = $product->ID ) {
		  $variation = new MP_Product( $variation_id );
		  if ( !$variation->exists() ) {
		  $variation = false;
		  }
		  }

		  $has_image = false;
		  if ( !$product->has_variations() ) {
		  $values = get_post_meta( $product->ID, 'mp_product_images', true );
		  if ( $values ) {
		  $has_image = true;
		  }
		  } else {
		  $post_thumbnail_id = get_post_thumbnail_id( $product->ID );
		  if ( $post_thumbnail_id ) {
		  $has_image = true;
		  }
		  }

		  $image_alignment = mp_get_setting( 'image_alignment_single' );
		  ?>
		  <!-- MP Product Lightbox -->
		  <?php
		  $return			 = '
		  <!-- MP Single Product -->
		  <section id="mp-single-product" itemscope itemtype="http://schema.org/Product">
		  <div class="mp_product mp_single_product' . ($has_image ? ' mp_single_product-has-image mp_single_product-image-' . (!empty( $image_alignment ) ? $image_alignment : 'aligncenter') . '' : '') . ($product->has_variations() ? ' mp_single_product-has-variations' : '') . '">';

		  $content = 'full';
		  $values	 = get_post_meta( $product->ID, 'mp_product_images', true );

		  if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
		  $size = array( mp_get_setting( 'list_img_size_custom->width' ), mp_get_setting( 'list_img_size_custom->height' ) );
		  } else {
		  $size = mp_get_setting( 'list_img_size' );
		  }

		  if ( !$product->has_variations() ) {


		  if ( $values ) {
		  $return .= '<div class="mp_single_product_images">';

		  $return .= "<script>
		  jQuery(document).ready(function() {
		  jQuery('#mp-product-gallery').lightSlider({
		  gallery:true,
		  item:1,
		  loop:true,
		  thumbItem:5,
		  slideMargin:0,
		  enableDrag: true,
		  currentPagerPosition:'left',
		  onSliderLoad: function(el) {
		  el.lightGallery({
		  selector: '#mp-product-gallery .lslide'
		  });
		  }
		  });
		  });
		  </script>";

		  $return .= '<ul id="mp-product-gallery" class="mp_product_gallery">';

		  $values = explode( ',', $values );

		  foreach ( $values as $value ) {
		  $img_url = wp_get_attachment_image_src( $value, $size );
		  $return .= '<li data-thumb="' . $img_url[ 0 ] . '" data-src ="' . $img_url[ 0 ] . '"><img src="' . $img_url[ 0 ] . '"></li>';
		  }

		  $return .= '</ul><!-- end mp_product_gallery -->';

		  $return .= '</div><!-- end mp_single_product_images -->';
		  }
		  } else {
		  $return .= '<div class="mp_single_product_images">';
		  $return .= ( $variation ) ? $variation->image( false, $image ) : $product->image( false, $image );
		  $return .= '</div><!-- end mp_single_product_images -->';
		  }

		  $return .= '<div class="mp_single_product_details">';

		  $return .= '<span style="display:none" class="date updated">' . get_the_time( $product->ID ) . '</span>'; // mp_product_class(false, 'mp_product', $post->ID)

		  $return .= '<div class="mp_product_meta">';

		  if ( $title ) {
		  $return .= ' <h1 itemprop="name" class="mp_product_name entry-title"><a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a></h1>';
		  }

		  // Price
		  $return .= ( $variation ) ? $variation->display_price( false ) : $product->display_price( false );

		  // Excerpt
		  if ( !$variation ) {
		  $return .= '<div class="mp_product_excerpt">';
		  $return .= mp_get_the_excerpt( $product_id, apply_filters( 'mp_get_the_excerpt_length', 18 ) );
		  $return .= '</div><!-- end mp_product_excerpt -->';
		  } else {
		  $return .= '<div class="mp_product_excerpt mp_product_excerpt-variation">';
		  $return .= mp_get_the_excerpt( $variation_id, apply_filters( 'mp_get_the_excerpt_length', 18 ), true );
		  $return .= '</div><!-- end mp_product_excerpt -->';
		  }

		  $return .= '</div><!-- end mp_product_meta-->';

		  // Callout
		  $return .= '<div class="mp_product_callout">';

		  // Button
		  $selected_atts = array();

		  if ( $variation ) {
		  $atts = $variation->get_attributes();
		  foreach ( $atts as $slug => $att ) {
		  $selected_atts[ $slug ] = key( $att[ 'terms' ] );
		  }
		  }

		  $return .= $product->buy_button( false, 'single', $selected_atts );

		  $return .= '</div><!-- end mp_product_callout-->';

		  $return .= '</div><!-- end mp_single_product_details-->';

		  $return .= '
		  </div><!-- end mp_product/mp_single_product -->
		  </section><!-- end mp-single-product -->';
		  echo $return;

		 */
		?>
		<?php if ( 0 == 0 ) { ?>
			<section id="mp-product-<?php echo $product->ID; ?>-lightbox" itemscope itemtype="http://schema.org/Product">
				<div class="mp_product mp_product_options">

					<div class="mp_product_options_image">
						<?php $product->image_custom( true, 'medium', array( 'class' => 'mp_product_options_thumb' ) ); ?>
					</div><!-- end mp_product_options_image -->

					<div class="mp_product_options_details">

						<div class="mp_product_options_meta">
							<h3 class="mp_product_name" itemprop="name"><?php echo $product->post_title; ?></h3>
							<div class="mp_product_options_excerpt"><?php echo $product->excerpt(); ?></div><!-- end mp_product_options_excerpt -->
						</div><!-- end mp_product_options_meta -->

						<div class="mp_product_options_callout">

							<form id="mp-product-options-callout-form" class="mp_form mp_form-mp-product-options-callout" method="post" data-ajax-url="<?php echo admin_url( 'admin-ajax.php?action=mp_update_cart' ); ?>" action="<?php echo get_permalink( mp_get_setting( 'pages->cart' ) ); ?>">
								<input type="hidden" name="product_id" value="<?php echo $product->ID; ?>">
								<input type="hidden" name="product_qty_changed" value="0">
								<?php $product->display_price(); ?>
								<div class="mp_product_options_atts"><?php $product->attribute_fields(); ?></div><!-- end mp_product_options_atts -->
								<?php if ( mp_get_setting( 'product_button_type' ) == 'addcart' ) : ?>
									<button class="mp_button mp_button-addcart" type="submit" name="addcart"><?php _e( 'Add To Cart', 'mp' ); ?></button>
								<?php elseif ( mp_get_setting( 'product_button_type' ) == 'buynow' ) :
									?>
									<button class="mp_button mp_button-buynow" type="submit" name="buynow"><?php _e( 'Buy Now', 'mp' ); ?></button>
								<?php endif; ?>
							</form><!-- end mp-product-options-callout-form -->

						</div><!-- end mp_product_options_callout -->

					</div><!-- end mp_product_options_details -->

				</div><!-- end mp_product_options -->
			</section><!-- end mp-product-<?php echo $product->ID; ?>-lightbox -->
		<?php } ?>
		<?php
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
		$product_atts	 = MP_Product_Attributes::get_instance();
		$attributes		 = $filtered_atts	 = $taxonomies		 = $filtered_terms	 = array();

		$json = array(
			'out_of_stock'	 => false,
			'qty_in_stock'	 => 0,
			'image'			 => false,
			'description'	 => false,
			'excerpt'		 => false,
			'price'			 => false,
		);

		$product_id	 = mp_get_post_value( 'product_id' );
		$product	 = new MP_Product( $product_id );
		$qty		 = mp_get_post_value( 'product_quantity', 1 );
		$qty_changed = (bool) mp_get_post_value( 'product_qty_changed' );

		if ( !$product->exists() ) {
			wp_send_json_error();
		}

		$all_atts = $product->get_attributes();

		foreach ( $_POST as $key => $val ) {
			if ( false !== strpos( $key, $product_atts::SLUGBASE ) && !empty( $val ) ) {
				$taxonomies[]		 = $key;
				$attributes[ $key ]	 = $val;
			}
		}

		$variations = $product->get_variations_by_attributes( $attributes );

		// Filter out taxonomies that already have values and are still valid
		foreach ( $all_atts as $att ) {

			$slug = $product_atts->generate_slug( $att[ 'id' ] );
			if ( !in_array( $slug, $taxonomies ) || $qty_changed ) {
				$filtered_atts[] = $slug;
			}
		}

		// Make sure all attribute terms are unique and in stock
		if ( count( $variations ) > 0 ) {
			foreach ( $variations as $variation ) {
				$json[ 'status' ] = 'variation loop';
				foreach ( $filtered_atts as $tax_slug ) {
					$terms = get_the_terms( $variation->ID, $tax_slug );
					foreach ( $terms as $term ) {
						if ( $variation->in_stock( $qty ) ) {

							$json[ 'status' ]								 = 'in stock';
							$json[ 'qty_in_stock' ]							 = $variation->get_stock();
							$filtered_terms[ $tax_slug ][ $term->term_id ]	 = $term;
						} elseif ( $qty_changed || !$variation->in_stock( $qty ) ) {
							$json[ 'status' ]		 = 'out of stock';
							$json[ 'qty_in_stock' ]	 = $variation->get_stock();

							/**
							 * Filter the out of stock alert message
							 *
							 * @since 3.0
							 * @param string The default message.
							 * @param MP_Product The product that is out of stock.
							 */
							$json[ 'out_of_stock' ] = apply_filters( 'mp_product/out_of_stock_alert', sprintf( __( 'We\'re sorry, we only have %d of this item in stock right now.', 'mp' ), $json[ 'qty_in_stock' ] ), $product );
						}
					}
				}
			}
		} else {
			$json[ 'status' ]		 = 'out of stock';
			$json[ 'out_of_stock' ]	 = apply_filters( 'mp_product/out_of_stock_alert', sprintf( __( 'We\'re sorry, we only have %d of this item in stock right now.', 'mp' ), $json[ 'qty_in_stock' ] ), $product );
		}

		// Format attribute terms for display
		foreach ( $filtered_terms as $tax_slug => $terms ) {
			$json[ $tax_slug ]	 = '';
			$index				 = 0;
			$terms				 = $product_atts->sort( $terms, false );
			foreach ( $terms as $term ) {
				$checked	 = ( mp_get_post_value( $tax_slug ) == $term->term_id ) ? true : false;
				$required	 = ( $index == 0 ) ? true : false;
				$json[ $tax_slug ] .= self::attribute_option( $term->term_id, $term->name, $tax_slug, $required, $checked );
				$index ++;
			}
		}

		// Attempt to get a unique variation image depending on user selection
		$images = array();
		foreach ( $variations as $variation ) {
			$images[ $variation->image_url( false, null, 'single' ) ] = '';
		}
		if ( count( $images ) == 1 ) {
			$json[ 'image' ] = key( $images );
		}

		// Attempt to get a unique product description depending on user selection
		$descs = array();
		foreach ( $variations as $variation ) {
			$descs[ $variation->content( false ) ] = '';
		}
		if ( count( $descs ) == 1 ) {
			$json[ 'description' ] = key( $descs );
		}

		// Attempt to get a unique product excerpt depending on user selection
		$excerpts = array();
		foreach ( $variations as $variation ) {
			$excerpts[ $variation->excerpt() ] = '';
		}
		if ( count( $excerpts ) == 1 ) {
			$json[ 'excerpt' ] = key( $excerpts );
		}

		// Attempt to get a unique product price depending on user selection
		$prices = array();
		foreach ( $variations as $variation ) {
			$prices[ $variation->display_price( false ) ] = '';
		}
		if ( count( $prices ) == 1 ) {
			$json[ 'price' ] = key( $prices );
		}

		wp_send_json_success( $json );
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access public
	 * @uses $post
	 * @param int/object/WP_Post $product Optional if in the loop.
	 */
	public function __construct( $product = null ) {
		if ( is_null( $product ) && in_the_loop() ) {
			global $post;
			$product = $post;
		}

		$this->_get_post( $product );
		$this->_set_content_tabs( $this );
	}

	/**
	 * Display an attribute option
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function attribute_option( $term_id, $term_name, $tax_slug, $required = false, $selected = false ) {
		$html = '<option id="mp_product_options_att_' . $term_id . '" value="' . $term_id . '"' . (( $selected ) ? ' selected' : '') . '>' . $term_name . '</option>';

		/**
		 * Filter the attribute option
		 *
		 * @since 3.0
		 * @param string $html
		 * @param int $term_id
		 * @param string $term_name
		 * @param string $tax_slug
		 * @param bool $required		 
		 */
		$html = apply_filters( 'mp_product/attribute_option', $html, $term_id, $term_name, $tax_slug, $required );

		return $html;
	}

	public function max_product_quantity( $product_id = false, $without_cart_quantity = false ) {

		$id			 = $product_id ? product_id : $this->ID;
		$cart_items	 = mp_cart()->get_all_items();

		$max = apply_filters( 'mp_cart/max_product_order_default', 100 );

		$per_order_limit = get_post_meta( $id, 'per_order_limit', true );
		$per_order_limit = (int) $per_order_limit;

		$cart_quantity = (int) $cart_items[ get_current_blog_id() ][ $id ];

		$inventory				 = get_post_meta( $id, 'inventory', true );
		$inventory_tracking		 = get_post_meta( $id, 'inventory_tracking', true );
		$out_of_stock_purchase	 = get_post_meta( $id, 'inv_out_of_stock_purchase', true );

		if ( $inventory_tracking && $out_of_stock_purchase !== '1' ) {
			if ( $without_cart_quantity ) {
				$max = $inventory;
			} else {
				$max = $inventory - $cart_quantity;
			}
		}

		$per_order_limit = get_post_meta( $id, 'per_order_limit', true );

		if ( is_numeric( $per_order_limit ) ) {
			if ( $per_order_limit >= $max ) {
				//max is max, not the per order limit
			} else {
				$max = $per_order_limit;
			}
		}

		if ( $max < 0 ) {
			$max = 0;
		}

		return $max;
	}

	/**
	 * Display the attribute fields
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 * @param array $selected_atts Optional, the attributes that should be selected by default.
	 */
	public function attribute_fields( $echo = true, $selected_atts = array() ) {
		$atts = $this->get_attributes();

		$html = '
			<div class="mp_product_options_atts">';

		foreach ( $atts as $slug => $att ) {
			/**
			 * Filter the default option label for the select field
			 *
			 * @since 3.0
			 * @access public
			 * @param string The default option label.
			 */
			$default_option_label = apply_filters( 'mp_product/attribute_fields/default_option_label', sprintf( __( 'Choose a %s', 'mp' ), $att[ 'name' ] ) );

			$html .= '
				<div class="mp_product_options_att">
					<strong class="mp_product_options_att_label">' . $att[ 'name' ] . '</strong>
					<div class="mp_product_options_att_input_label">
						<select id="mp_' . $slug . '" name="' . $slug . '" class="mp_select2 required" autocomplete="off">
							<option value="">' . $default_option_label . '</option>';


			$index = 0;
			foreach ( $att[ 'terms' ] as $term_id => $term_name ) {
				$required	 = ( $index == 0 );
				$checked	 = ( $term_id == mp_arr_get_value( $slug, $selected_atts ) );
				$html .= $this->attribute_option( $term_id, $term_name, $slug, $required, $checked );
				$index ++;
			}

			$html .= '
						</select>
					</div><!-- end mp_product_options_att_input_label -->
				</div><!-- end mp_product_options_att -->';
		}

		$input_id = 'mp_product_options_att_quantity';

		$product = new MP_Product( $this->ID );

		if ( $product->is_download() && mp_get_setting( 'download_order_limit' ) == '1' ) {
			$disabled = 'disabled';
		} else {
			$disabled = '';
		}

		$per_order_limit = get_post_meta( $this->ID, 'per_order_limit', true );

		$max				 = '';
		$product_quantity	 = 1;

		if ( $product->has_variations() ) {
			
		} else {

			if ( is_numeric( $per_order_limit ) ) {
				$max = 'max="' . esc_attr( $per_order_limit ) . '"';
			}else{
                            $max = 'max="' . esc_attr( $this->max_product_quantity() ) . '"';
                        }

			$cart_items = mp_cart()->get_all_items();

			if ( isset( $cart_items[ get_current_blog_id() ] ) ) {
				if ( isset( $cart_items[ get_current_blog_id() ][ $this->ID ] ) ) {//item is located in the cart
					$cart_quantity = $cart_items[ get_current_blog_id() ][ $this->ID ];
					if ( is_numeric( $per_order_limit ) ) {
						$max_product_quantity = $per_order_limit - $cart_quantity;
						if ( $max_product_quantity == 0 ) {
							$product_quantity	 = 0;
							$max				 = 'max="' . esc_attr( $max_product_quantity ) . '"';
							$disabled			 = 'disabled';
						} else {
							$max = 'max="' . esc_attr( $max_product_quantity ) . '"';
						}
					} else {
						$max = 'max="' . esc_attr( $this->max_product_quantity() ) . '"';
					}
				}
			}
		}

		if ( $this->max_product_quantity() == 0 ) {
			$min_value			 = 0;
			$product_quantity	 = 0;
			$disabled			 = 'disabled';
		} else {
			$min_value = 1;
		}

		$html .= '
				<div class="mp_product_options_att"' . (( mp_get_setting( 'show_quantity' ) ) ? '' : ' style="display:none"') . '>
					<strong class="mp_product_options_att_label">' . __( 'Quantity', 'mp' ) . '</strong>
					<div class="mp_form_field mp_product_options_att_field">
						<label class="mp_form_label mp_product_options_att_input_label" for="' . $input_id . '"></label>
						<input id="' . $input_id . '" class="mp_form_input mp_form_input-qty required digits" min="' . esc_attr( $min_value ) . '" ' . $max . ' type="number" name="product_quantity" value="' . $product_quantity . '" ' . $disabled . '>
					</div><!-- end mp_product_options_att_field -->
				</div><!-- end mp_product_options_att -->
			</div><!-- end mp_product_options_atts -->';


		/**
		 * Filter the attribute fields
		 *
		 * @since 3.0
		 * @param string The current html.
		 * @param MP_Product The current MP_Product object.
		 */
		$html	 = apply_filters( 'mp_product/attribute_fields', $html, $this );
		$html	 = apply_filters( 'mp_product/attribute_fields/' . $this->ID, $html, $this );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Get a specific variation by it's index
	 *
	 * @since 3.0
	 * @access public
	 * @param int $index Optional.
	 */
	public function get_variation( $index = 0 ) {
		$variations = $this->get_variations();
		return mp_arr_get_value( $index, $variations );
	}

	/**
	 * Get product variation ids
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_variation_ids() {
		if ( !is_null( $this->_variation_ids ) ) {
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
		if ( !is_null( $this->_variations ) ) {
			return $this->_variations;
		}

		$this->_variations = array();
		if ( !$this->get_meta( 'has_variations' ) ) {
			return $this->_variations;
		}

		$query = new WP_Query( array(
			'post_type'		 => MP_Product::get_variations_post_type(),
			'posts_per_page' => -1,
			'orderby'		 => 'menu_order',
			'order'			 => 'ASC',
			'post_parent'	 => $this->ID,
		) );

		$this->_variation_ids = array();

		while ( $query->have_posts() ) : $query->the_post();
			$this->_variations[]	 = $variation				 = new MP_Product();
			$this->_variation_ids[]	 = $variation->ID;
		endwhile;

		wp_reset_postdata();

		return $this->_variations;
	}

	/**
	 * Get variations by the given attributes
	 *
	 * @since 3.0
	 * @access public
	 * @param array $attributes An array of attribute arrays in $taxonomy => $term_id format.
	 * @param int If only one variation is desired set the index that you would like to retrieve.
	 * @return array/MP_Product
	 */
	public function get_variations_by_attributes( $attributes, $index = null ) {
		$cache_key	 = 'get_variations_by_attributes_' . $this->ID;
		$cache		 = wp_cache_get( $cache_key, 'mp_product' );
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
				'taxonomy'	 => $taxonomy,
				'terms'		 => $term_id,
			);
		}

		$query = new WP_Query( array(
			'post_type'		 => MP_Product::get_variations_post_type(),
			'posts_per_page' => -1,
			'post_parent'	 => $this->ID,
			'tax_query'		 => array( 'relation' => 'AND' ) + $tax_query,
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
	 * @param float $default The default weight if a weight isn't set for the product.
	 * @return float
	 */
	public function get_weight() {
		$weight = 0;

		if ( $this->is_download() ) {
			return $weight;
		}

		$lbs = (float) $this->get_meta( 'weight_pounds', 0 );
		$oz	 = (float) $this->get_meta( 'weight_ounces', 0 );

		if ( $lbs || $oz ) {
			$weight = $lbs;

			if ( $oz > 0 ) {
				$weight += ($oz / 16);
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

		if ( !mp_get_setting( 'tax->tax_inclusive' ) ) {
			// tax inclusve pricing is turned off - just return given price
			return $price;
		}

		$rate = $this->get_meta( 'special_tax_rate' );

		if ( empty( $rate ) ) {
			$rate = mp_tax_rate();
		} else {
			if ( false !== strpos( $rate, '%' ) ) {
				// Special rate is a string percentage - convert to float value
				$rate	 = (float) preg_replace( '[^0-9.]', '', $rate );
				$rate	 = $rate / 100;
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

	public function buy_button( $echo = true, $context = 'list', $selected_atts = array(), $no_single = false ) {
		$button	 = '';
		if ( $this->get_meta( 'product_type' ) == 'external' && ($url	 = $this->get_meta( 'external_url' )) ) {
			$button = '<a class="mp_link-buynow" href="' . esc_url( $url ) . '">' . __( 'Buy Now &raquo;', 'mp' ) . '</a>';
		} elseif ( !mp_get_setting( 'disable_cart' ) ) {
			$button = '<form id="mp-buy-product-' . $this->ID . '-form" class="mp_form mp_form-buy-product ' . ($no_single ? 'mp_no_single' : '') . '" method="post" data-ajax-url="' . admin_url( 'admin-ajax.php?action=mp_update_cart' ) . '" action="' . mp_cart_link( false, true ) . '">';

			if ( !$this->in_stock() ) {
				$button .= '<span class="mp_no_stock">' . __( 'Out of Stock', 'mp' ) . '</span>';
			} else {
				$button .= '<input type="hidden" name="product_id" value="' . $this->ID . '">';

				if ( $context == 'list' ) {
					if ( $this->has_variations() ) {
						$button .= '<a class="mp_button mp_link-buynow mp_button-has_variations" data-href="' . admin_url( 'admin-ajax.php?action=mp_product_get_variations_lightbox&amp;product_id=' . $this->ID ) . '" href="' . $this->url( false ) . '">' . __( 'Choose Options', 'mp' ) . '</a>';
					} else if ( mp_get_setting( 'list_button_type' ) == 'addcart' ) {
						$button .= '<button class="mp_button mp_button-addcart" type="submit" name="addcart">' . __( 'Add To Cart', 'mp' ) . '</button>';
					} else if ( mp_get_setting( 'list_button_type' ) == 'buynow' ) {
						$button .= '<button class="mp_button mp_button-buynow" type="submit" name="buynow">' . __( 'Buy Now', 'mp' ) . '</button>';
					}
				} else {
					$button .= $this->attribute_fields( false, $selected_atts );

					if ( mp_get_setting( 'product_button_type' ) == 'addcart' ) {
						$button .= '<button class="mp_button mp_button-addcart" type="submit" name="addcart">' . __( 'Add To Cart', 'mp' ) . '</button>';
					} else if ( mp_get_setting( 'product_button_type' ) == 'buynow' ) {
						$button .= '<button class="mp_button mp_button-buynow" type="submit" name="buynow">' . __( 'Buy Now', 'mp' ) . '</button>';
					}
				}
			}

			$button .= '</form><!-- end mp-buy-product-form -->';
		}

		$button = apply_filters( 'mp_buy_button_tag', $button, $this->ID, $context );

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
	 * @param bool $echo
	 */
	public function content( $echo = true ) {
		$content = $this->_post->post_content;

		if ( $this->has_variations() || $this->is_variation() ) {

			$content = get_the_content( $this->ID ); //get_post_meta( $variation_id, 'description', true );

			$parent_post_id	 = wp_get_post_parent_id( $this->ID );
			$parent_post	 = get_post( $parent_post_id );
			if ( !empty( $parent_post->post_content ) && ($parent_post->post_content !== $content) ) {
				$content = $parent_post->post_content . "\r\n" . $content;
			}
		}

		$content = apply_filters( 'the_content', $content );

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

	/**
	 * Get the product's excerpt
	 *
	 * @since 3.0
	 * @param string $excerpt_more Optional
	 * @param string $excerpt Optional
	 * @param string $content Optional
	 * @return string
	 */
	public function excerpt( $excerpt = null, $content = null, $excerpt_more = null ) {
		if ( is_null( $excerpt_more ) ) {
			$excerpt_more = ' <a class="mp_product_more_link" href="' . get_permalink( $this->ID ) . '">' . __( 'More Info &raquo;', 'mp' ) . '</a>';
		}

		if ( is_null( $excerpt ) ) {
			$excerpt = $this->has_variations() ? $this->get_variation()->post_excerpt : $this->_post->post_excerpt;
		}

		if ( is_null( $content ) ) {
			$content = $this->has_variations() ? $this->get_variation()->post_content : $this->_post->post_content;
		}

		if ( $excerpt ) {
			return apply_filters( 'get_the_excerpt', $excerpt ) . $excerpt_more;
		} else {
			$text			 = strip_shortcodes( $content );
			$text			 = str_replace( ']]>', ']]&gt;', $text );
			$text			 = strip_tags( $text );
			$excerpt_length	 = apply_filters( 'excerpt_length', 55 );
			$words			 = preg_split( "/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );

			if ( count( $words ) > $excerpt_length ) {
				array_pop( $words );
				$text	 = implode( ' ', $words );
				$text	 = $text . $excerpt_more;
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
	 * @param bool $echo
	 */
	public function content_tab_labels( $echo = true ) {
		$html = '
			<ul class="mp_product_tab_labels">';

		$index = 0;
		foreach ( $this->content_tabs as $slug => $label ) {
			$html .= '
				<li class="mp_product_tab_label' . (( $index == 0 ) ? ' current' : '') . '"><a class="mp_product_tab_label_link ' . esc_attr( $slug ) . '" href="#' . esc_attr( $slug ) . '">' . $label . '</a></li>';
			$index ++;
		}

		$html .= '
			</ul><!-- end mp_product_tab_labels -->';

		/**
		 * Filter the product tabs html
		 *
		 * @since 3.0
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
	 * @param bool $echo
	 * @param string/int/array $size
	 * @param array $attributes
	 */
	public function image_custom( $echo = true, $size = 'large', $attributes = array() ) {
		$thumb_id = ( $this->has_variations() ) ? get_post_thumbnail_id( $this->get_variation()->ID ) : get_post_thumbnail_id( $this->ID );

		if ( isset( $attributes ) && isset( $attributes[ 'show_thumbnail_placeholder' ] ) ) {
			$show_thumbnail_placeholder = (bool) $attributes[ 'show_thumbnail_placeholder' ];
		} else {
			$show_thumbnail_placeholder = true;
		}

		unset( $attributes[ 'show_thumbnail_placeholder' ] );

		if ( $intsize = intval( $size ) ) {
			$size = array( $intsize, $intsize );
		}

		if ( empty( $thumb_id ) ) {
			$attributes = array_merge( array(
				'src'	 => apply_filters( 'mp_default_product_img', mp_plugin_url( 'ui/images/default-product.png' ) ),
				'width'	 => ( is_array( $size ) ) ? $intsize : get_option( 'thumbnail_size_w' ),
				'height' => ( is_array( $size ) ) ? $intsize : get_option( 'thumbnail_size_h' ),
			), $attributes );
		} else {
			$data		 = wp_get_attachment_image_src( $thumb_id, $size, false );
			$attributes	 = array_merge( array(
				'src'	 => $data[ 0 ],
				'width'	 => $data[ 1 ],
				'height' => $data[ 2 ]
			), $attributes );
		}
		if ( !empty( $thumb_id ) || $show_thumbnail_placeholder ) {
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
	 * @param bool $echo
	 */
	public function display_price( $echo = true ) {
		$price	 = $this->get_price();
		$snippet = '<!-- MP Product Price --><div class="mp_product_price" itemtype="http://schema.org/Offer" itemscope="" itemprop="offers">';

		if ( $this->has_variations() ) {
			// Get price range
			if ( $price[ 'lowest' ] != $price[ 'highest' ] ) {
				$snippet .= '<span class="mp_product_price-normal">' . mp_format_currency( '', $price[ 'lowest' ] ) . ' - ' . mp_format_currency( '', $price[ 'highest' ] ) . '</span>';
			} else {
				$snippet .= '<span class="mp_product_price-normal">' . mp_format_currency( '', $price[ 'lowest' ] ) . '</span>';
			}
		} elseif ( $this->on_sale() ) {
			$amt_off = mp_format_currency( '', ($price[ 'highest' ] - $price[ 'lowest' ]) * $this->qty );

			if ( $this->qty > 1 ) {
				$snippet .= '<span class="mp_product_price-extended">' . mp_format_currency( '', ($price[ 'lowest' ] * $this->qty ) ) . '</span>';
				$snippet .= '<span class="mp_product_price-each" itemprop="price">(' . sprintf( __( '%s each', 'mp' ), mp_format_currency( '', $price[ 'sale' ][ 'amount' ] ) ) . ')</span>';
			} else {
				$snippet .= '<span class="mp_product_price-sale" itemprop="price">' . mp_format_currency( '', $price[ 'sale' ][ 'amount' ] ) . '</span>';
			}

			$snippet .= '<span class="mp_product_price-normal mp_strikeout">' . mp_format_currency( '', ($price[ 'regular' ] * $this->qty ) ) . '</span>';

			/* if ( ($end_date	 = $price[ 'sale' ][ 'end_date' ]) && ($days_left	 = $price[ 'sale' ][ 'days_left' ]) ) {
			  $snippet .= '<strong class="mp_savings_amt">' . sprintf( __( 'You Save: %s', 'mp' ), $amt_off ) . sprintf( _n( ' - only 1 day left!', ' - only %s days left!', $days_left, 'mp' ), $days_left ) . '</strong>';
			  } else {
			  $snippet .= '<strong class="mp_savings_amt">' . sprintf( __( 'You Save: %s', 'mp' ), $amt_off ) . '</strong>';
			  } */
		} else {
			if ( $this->qty > 1 ) {
				$snippet .= '<span class="mp_product_price-extended">' . mp_format_currency( '', ($price[ 'lowest' ] * $this->qty ) ) . '</span>';
				$snippet .= '<span class="mp_product_price-each" itemprop="price">(' . sprintf( __( '%s each', 'mp' ), mp_format_currency( '', $price[ 'lowest' ] ) ) . ')</span>';
			} else {
				$snippet .= '<span class="mp_product_price-normal" itemprop="price">' . mp_format_currency( '', $price[ 'lowest' ] ) . '</span>';
			}
		}

		$snippet .= '</div><!-- end mp_product_price -->';

		/**
		 * Filter the display price of the product
		 *
		 * @since 3.0
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
	 * Get the product's download url - if applicable
	 *
	 * @since 3.0
	 * @access public
	 * @param string $order_id The order ID for the download.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function download_url( $order_id, $echo = true ) {
		$url = false;
		if ( $this->is_download() ) {
			$url = add_query_arg( 'orderid', $order_id, $this->url( false ) );
		}

		/**
		 * Filter the product's download url
		 *
		 * @since 3.0
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
		return (!empty( $variations ) );
	}

	/**
	 * Determine if product is on sale
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function on_sale() {
		if ( !is_null( $this->_on_sale ) ) {
			return $this->_on_sale;
		}

		$sale_price	 = $this->get_meta( 'sale_price_amount' );
		$on_sale	 = false;

		if ( $sale_price ) {
			$start_date	 = $this->get_meta( 'sale_price_start_date', false, true );
			$end_date	 = $this->get_meta( 'sale_price_end_date', false, true );
			$time		 = current_time( 'Y-m-d' );
			$on_sale	 = true;

			if ( $start_date && $time < $start_date ) {
				$on_sale = false;
			} elseif ( $end_date && $time > $end_date ) {
				$on_sale = false;
			}
		}

		/**
		 * Filter the on sale flag
		 *
		 * @since 3.0
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
	 * @param string $what Optional, the subset of the price array to be returned.
	 * @return array/float
	 */
	public function get_price( $what = null ) {
		if ( !is_null( $this->_price ) ) {
			if ( !is_null( $what ) ) {
				return mp_arr_get_value( $what, $this->_price );
			}

			return $this->_price;
		}

		$price = array(
			'regular'	 => (float) $this->get_meta( 'regular_price' ),
			'lowest'	 => (float) $this->get_meta( 'regular_price' ),
			'highest'	 => (float) $this->get_meta( 'regular_price' ),
			'sale'		 => array(
				'amount'	 => false,
				'start_date' => false,
				'end_date'	 => false,
				'days_left'	 => false,
			),
		);

		if ( $this->has_variations() ) {
			$variations	 = $this->get_variations();
			$prices		 = array();

			foreach ( $variations as $variation ) {
				$price = $variation->get_price();

				if ( $variation->on_sale() ) {
					$prices[] = $price[ 'sale' ][ 'amount' ];
				} else {
					$prices[] = $price[ 'regular' ];
				}
			}

			$price[ 'lowest' ]	 = (float) min( $prices );
			$price[ 'highest' ]	 = (float) max( $prices );
		} elseif ( $this->on_sale() && ($sale_price = $this->get_meta( 'sale_price_amount' )) ) {
			$start_date_obj	 = new DateTime( $this->get_meta( 'sale_price_start_date', date( 'Y-m-d' ), true ) );
			$days_left		 = false;

			if ( method_exists( $start_date_obj, 'diff' ) ) { // The diff method is only available PHP version >= 5.3
				$end_date_obj	 = new DateTime( $this->get_meta( 'sale_price_end_date', date( 'Y-m-d' ), true ) );
				$diff			 = $start_date_obj->diff( $end_date_obj );
				$days_left		 = $diff->d;

				/**
				 * Filter the maximum number of days before the "only x days left" nag shows
				 *
				 * @since 3.0
				 * @param int The default number of days
				 */
				$days_limit = apply_filters( 'mp_product/get_price/days_left_limit', 7 );

				if ( $days_left > $days_limit ) {
					$days_left = false;
				}
			}

			$price[ 'lowest' ]	 = (float) $sale_price;
			$price[ 'sale' ]	 = array(
				'amount'	 => (float) $sale_price,
				'start_date' => $this->get_meta( 'sale_price_start_date', false ),
				'end_date'	 => $this->get_meta( 'sale_price_end_date', false ),
				'days_left'	 => $days_left,
			);
		}

		/**
		 * Filter the price array
		 *
		 * @since 3.0
		 * @param array The current pricing array.
		 * @param MP_Product The current product.
		 */
		$this->_price = apply_filters( 'mp_product/get_price', $price, $this );

		if ( mp_arr_get_value( 'sale->amount', $price ) != mp_arr_get_value( 'sale->amount', $this->_price ) ) {
			/* Filter changed sale price so let's flip the on-sale flag so the sale
			  price will show up accordingly */
			$this->_on_sale = true;
		}

		if ( !is_null( $what ) ) {
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
		return $price[ 'lowest' ];
	}

	/**
	 * Get related products
	 *
	 * @since 3.0
	 * @param array $args {
	 * 		Optional, an array of arguments.
	 *
	 * 		@type string $relate_by Optional, how to relate the products - either category, tag, or both.
	 * 		@type bool $echo Optional, echo or return.
	 * 		@type int $limit. Optional, the number of products to retrieve.
	 * 		@type string $view. Optional, how to display related products - either grid or list.
	 * }
	 */
	public function related_products( $args = array(), $return_bool = false ) {
		$html	 = '';
		$args	 = array_replace_recursive( array(
			'relate_by'	 => mp_get_setting( 'related_products->relate_by' ),
			'echo'		 => false,
			'limit'		 => mp_get_setting( 'related_products->show_limit' ),
			'view'		 => mp_get_setting( 'related_products->view' ),
		), $args );

		extract( $args );

		$query_args = array(
			'post_type'		 => MP_Product::get_post_type(),
			'posts_per_page' => $limit,
			'post__not_in'	 => array( ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID )
		);

		$related_specified_products_enabled = true;

		$related_specified_products = $this->get_meta( 'related_products' );

		if ( is_array( $related_specified_products ) && $related_specified_products[ 0 ] == '' ) {
			$related_specified_products_enabled = false;
		}

		$related_products = '';

		if ( $related_products !== $this->get_meta( 'related_products' ) && $related_specified_products_enabled ) {
			$query_args[ 'post__in' ] = $related_products;
		} else {
			$post_id = ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID;
			$count	 = 0;

			if ( 'category' != $relate_by ) {
				$terms						 = get_the_terms( $post_id, 'product_tag' );
				$ids						 = isset( $terms ) && is_array( $terms ) && !is_wp_error( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();
				$query_args[ 'tax_query' ][] = array(
					'taxonomy'	 => 'product_tag',
					'terms'		 => $ids,
				);
				$count ++;
			}

			if ( 'tags' != $relate_by ) {
				$terms						 = get_the_terms( $post_id, 'product_category' );
				$ids						 = isset( $terms ) && is_array( $terms ) && !is_wp_error( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();
				$query_args[ 'tax_query' ][] = array(
					'taxonomy'	 => 'product_category',
					'terms'		 => $ids,
				);
				$count ++;
			}

			if ( $count > 1 ) {
				$query_args[ 'tax_query' ][ 'relation' ] = 'AND';
			}
		}

		$product_query = new WP_Query( $query_args );

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
	 * @param bool $echo Optional, whether to echo or return. Defaults to return.
	 * @return float The special tax amount for the item. False if item doesn't have a special rate.
	 */
	public function special_tax_amt( $echo = false ) {
		$special_tax_rate = $this->get_meta( 'special_tax_rate', '' );

		if ( empty( $special_tax_rate ) || $this->get_meta( 'charge_tax' ) !== '1' ) {
			return false;
		}

		if ( false !== strpos( $special_tax_rate, '%' ) ) {
			$special_tax_rate	 = (float) preg_replace( '[^0-9.]', '', $special_tax_rate );
			$special_tax_rate	 = $special_tax_rate / 100;
			$is_fixed_amt		 = false;
		} else {
			$special_tax_rate	 = (float) $special_tax_rate;
			$is_fixed_amt		 = true;
		}

		/**
		 * Filter the special tax rate
		 *
		 * @since 3.0
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
			$stock		 = array();
			$variations	 = $this->get_variations();
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
		/**
		 * Filter the post_id used for the product image
		 *
		 * @since 3.0
		 * @param int $post_id
		 */
		
		if ( empty( $context ) ) {
			$context = 'single';
		}

		$post_id = apply_filters( 'mp_product_image_id', $this->ID );

		if ( $post_id != $this->ID ) {
			$this->ID	 = $post_id;
			$this->_post = $post		 = get_post( $post_id );
		}

		$image_post_id = $this->ID;

		if ( $this->has_variations() ) {
			$image_post_id = $this->get_variation()->ID;
		}

		$post_thumbnail_id = get_post_thumbnail_id( $image_post_id );

		if ( mp_get_setting( 'show_thumbnail_placeholder' ) == '1' ) {
			//do nothing, placeholder image should be shown
		} else {
			if ( (!is_numeric( $post_thumbnail_id ) ) ) {
				return '';
			}
		}

		$class		 = $title		 = $link		 = $img_align	 = '';

		$img_classes = array( 'mp_product_image_' . $context, 'photo' );

		$title = esc_attr( $this->title( false ) );

		if ( !is_null( $align ) && false === strpos( $align, 'align' ) ) {
			$align = 'align' . $align;
		}

		switch ( $context ) {
			case 'list' :
				if ( !mp_get_setting( 'show_thumbnail' ) ) {
					return '';
				}

				//size
				if ( intval( $size ) ) {
					$size = array( intval( $size ), intval( $size ) );
				} else {
					if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
						$size = array( mp_get_setting( 'list_img_size_custom->width' ), mp_get_setting( 'list_img_size_custom->height' ) );
					} else {
						$size = mp_get_setting( 'list_img_size' );
					}
				}

				$link		 = get_permalink( $this->ID );
				$link_class	 = ' class="mp_product_img_link"';
				$img_align	 = is_null( $align ) ? mp_get_setting( 'image_alignment_list' ) : $align;
				break;

			case 'floating-cart' :
				$img_classes = array( 'mp-floating-cart-item-image' );

				if ( $size = intval( $size ) ) {
					$size = array( $size, $size );
				} else {
					$size = array( 50, 50 );
				}
				break;

			case 'single' :
			
				// size
				if ( intval( $size ) ) {
					$size = array( intval( $size ), intval( $size ) );
				} else {
					if ( mp_get_setting( 'product_img_size' ) == 'custom' ) {
						$size = array( mp_get_setting( 'product_img_size->width' ), mp_get_setting( 'product_img_size->height' ) );
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
					$link	 = $this->image_url( false, 'fullsize', false );
					$title	 = __( 'View Larger Image &raquo;', 'mp' );
				}

				$link_class	 = ' class="mp_product_image_link mp_lightbox"';
				$img_align	 = is_null( $align ) ? mp_get_setting( 'image_alignment_single' ) : $align;
				break;

			case 'widget' :
				//size
				if ( $size = intval( $size ) ) {
					$size = array( $size, $size );
				} else {
					$size = array( 50, 50 );
				}

				//link
				$link		 = get_permalink( $post_id );
				$link_class	 = ' class="mp_img_link"';
				break;
		}

		$image = get_the_post_thumbnail( $image_post_id, $size, array( 'itemprop' => 'image', 'class' => implode( ' ', $img_classes ), 'title' => $title ) );

		if ( empty( $image ) ) {
			$thumbnail_placeholder = mp_get_setting( 'thumbnail_placeholder' );

			$placeholder_image = !empty( $thumbnail_placeholder ) ? $thumbnail_placeholder : mp_plugin_url( 'ui/images/default-product.png' );

			if ( $context == 'floating-cart' ) {
				$image = '<img width="' . $size[ 0 ] . '" height="' . $size[ 1 ] . '" class="' . implode( ' ', $img_classes ) . '" src="' . apply_filters( 'mp_default_product_img', $placeholder_image ) . '">';
			} else {
				if ( !is_array( $size ) ) {
					$size = array( get_option( $size . '_size_w' ), get_option( $size . '_size_h' ) );
				}

				$img_classes[]	 = 'wp-post-image';
				$image			 = '<img width="' . $size[ 0 ] . '" height="' . $size[ 1 ] . '" itemprop="image" title="' . esc_attr( $title ) . '" class="' . implode( ' ', $img_classes ) . '" src="' . apply_filters( 'mp_default_product_img', $placeholder_image ) . '">';
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
			$snippet .= '<a rel="lightbox enclosure" id="mp-product-image-' . $post_id . '"' . $link_class . ' href="' . $link . '">' . $image . '</a>';
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
	 * @param bool $echo
	 * @param string/int $size
	 * @param string $view Either single or list. Optional.
	 */
	public function image_url( $echo = true, $size = null, $view = null, $id = false ) {


		if ( is_null( $size ) ) {
			$img_size	 = mp_get_image_size( $view );
			$size		 = ( $img_size[ 'label' ] == 'custom' ) ? array( $size[ 'width' ], $size[ 'height' ] ) : $img_size[ 'label' ];
		} elseif ( $thesize = intval( $size ) ) {
			$size = array( $thesize, $thesize );
		}

		$post_id = $this->ID;
		if ( $this->has_variations() ) {
			$post_id = $this->get_variation()->ID;
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$img_id	 = get_post_thumbnail_id( $id ? $id : $post_id  );
			$img_src = wp_get_attachment_image_src( $img_id, $size );
			$img_url = array_shift( $img_src );
		}

		if ( empty( $img_url ) ) {
			/**
			 * Filter the default image url
			 *
			 * @since 3.0
			 * @param string The current default image url.
			 */
			$img_url = apply_filters( 'mp_product/default_img_url', mp_plugin_url( 'ui/images/default-product.png' ) );
		}

		/**
		 * Filter the product image url
		 *
		 * @since 3.0
		 * @param string $img_url The current image url.
		 * @param string/int $size
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
	 * @param int $qty The quantity to check against.
	 * @return bool
	 */
	public function in_stock( $qty = 1 ) {
		$has_stock = false;

		if ( $this->is_variation() ) {
			$out_of_stock_purchase = $this->get_meta( 'inv_out_of_stock_purchase' );
			if ( $this->get_meta( 'inventory_tracking' ) && $out_of_stock_purchase !== '1' ) {
				$inventory	 = $this->get_meta( 'inventory', 0 );
				$has_stock	 = ( $inventory >= $qty );
			} else {
				$has_stock = true;
			}
		}

		/* if ( $this->has_variations() ) {
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
				$inventory	 = $this->get_meta( 'inventory', 0 );
				$has_stock	 = ( $inventory >= $qty );
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
			$parent			 = new MP_Product( $this->_post->post_parent );
			$product_type	 = $parent->get_meta( 'product_type' );
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
		$msg	 = __( 'This message is being sent to notify you of low stock of a product in your online store according to your preferences.

Product: %s
Current Inventory: %s
Link: %s

Edit Product: %s
Notification Preferences: %s', 'mp' );
		$msg	 = sprintf( $msg, $name, number_format_i18n( $stock ), $this->url( false ), $this->url_edit( false ), admin_url( 'admin.php?page=mp-settings-general-misc#mp-settings-general-misc' ) );

		/**
		 * Filter the low stock notification message
		 *
		 * @since 3.0
		 * @param string $msg The current message text.
		 * @param int $this->ID The product's ID.
		 */
		$msg = apply_filters( 'mp_low_stock_notification', $msg, $this->ID );

		mp_send_email( mp_get_store_email(), $subject, $msg );
	}

	/**
	 * Get a product's attribute
	 *
	 * @since 3.0
	 * @access public
	 * @param string $attribute
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

		if ( !is_null( $this->_attributes ) ) {
			return $this->_attributes;
		}

		$mp_product_atts	 = MP_Product_Attributes::get_instance();
		$all_atts			 = $mp_product_atts->get();
		$this->_attributes	 = array();

		$ids = array( $this->ID );

		if ( $this->has_variations() ) {
			$ids = $this->get_variation_ids();
		}

		$post_id			 = ( $this->is_variation() ) ? $this->_post->post_parent : $this->ID;
		$product_categories	 = get_the_terms( $post_id, 'product_category' );

		if ( !empty( $product_categories ) && !is_wp_error( $product_categories ) ) {
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
			AND t2.taxonomy LIKE '" . $mp_product_atts::SLUGBASE . "%'"
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

		$terms			 = wp_get_object_terms( $ids, array_values( $attributes ) );
		$terms_sorted	 = $mp_product_atts->sort( $terms );
		$names			 = array();
		foreach ( $terms_sorted as $tax_slug => $terms ) {
			$tax_id = $mp_product_atts->get_id_from_slug( $tax_slug );

			foreach ( $terms as $term ) {
				if ( $att = $mp_product_atts->get_one( $tax_id ) ) {
					if ( !in_array( $term->taxonomy, $names ) ) {
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

		if ( empty( $meta_value ) ) {
			$meta_value = $default;
		}

		return $meta_value;
	}

	/**
	 * Get product meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function get_meta( $name, $default = false, $raw = false ) {
		if ( !$this->exists() ) {
			return $default;
		}

		$value = false;
		if ( function_exists( 'get_field_value' ) ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value( $name, $this->ID, $raw );
		}

		if ( $value !== false && $value !== '' ) {
			return $value;
		}

		// Try to use regular post meta
		$meta_val = get_post_meta( $this->ID, $name, true );
		if ( $meta_val !== '' ) {
			return $meta_val;
		}

		return $default;
	}

	/**
	 * Display product meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
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
	 * @param bool $echo
	 */
	public function url( $echo = true ) {
		if ( $this->is_variation() ) {
			$url = get_permalink( $this->_post->post_parent ) . 'variation/' . $this->ID;
		} else {
			$url = get_permalink( $this->ID );
		}

		/**
		 * Filter the product's URL
		 *
		 * @since 3.0
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

		if ( $single_view_allowed && $context == 'single_view' ) {
			
		} else {
			if ( $setting == 'off' && ($setting != $context) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );

		if ( !$product->has_variations() ) {
			$image_info = wp_get_attachment_image_src( get_post_thumbnail_id( $this->ID ), 'large' );
		} else {
			$variation	 = $product->get_variation( 0 );
			$image_info	 = wp_get_attachment_image_src( get_post_thumbnail_id( $variation->ID ), 'large' );
		}


		$count_pos	 = ( $pos		 = mp_get_setting( 'social->pinterest->show_pin_count' ) ) ? $pos : 'none';
		$url		 = add_query_arg( array(
			'url'			 => get_permalink( $this->ID ),
			'description'	 => get_the_title( $this->ID ),
		), '//www.pinterest.com/pin/create/button/' );

		if ( $media = mp_arr_get_value( '0', $image_info ) ) {
			$url = add_query_arg( 'media', $media, $url );
		}

		$snippet = apply_filters( 'mp_pinit_button_link', '<a target="_blank" href="' . $url . '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png"></a>', $this->ID, $context );

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

		if ( $single_view_allowed && $context == 'single_view' ) {
			
		} else {
			if ( $setting == 'off' && ($setting != $context) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );
		$url	 = get_permalink( $this->ID );

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
	 * @param string $context
	 * @param bool $echo
	 */
	public function facebook_like_button( $context = 'single_view', $echo = false ) {
		$setting		 = mp_get_setting( 'social->facebook->show_facebook_like_button' );
		$setting_action	 = mp_get_setting( 'social->facebook->action' );

		if ( empty( $setting ) || $setting == '' ) {
			$setting = 'off';
		}

		$action = 'like';

		if ( isset( $setting_action ) && !is_null( $setting_action ) ) {
			$action = $setting_action;
		} else {
			$action = 'recommend';
		}

		$show_share		 = 'false';
		$setting_share	 = mp_get_setting( 'social->facebook->show_share' );

		if ( isset( $setting_share ) && !is_null( $setting_share ) ) {
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

		if ( $single_view_allowed && $context == 'single_view' ) {
			
		} else {
			if ( $setting == 'off' && ($setting != $context) ) {
				return '';
			}
		}

		$product = new MP_Product( $this->ID );
		$url	 = get_permalink( $this->ID );

		//$snippet = apply_filters( 'mp_facebook_like_button_link', '<a target="_blank" href="' . $url . '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" /></a>', $this->ID, $context );

		$snippet = "<div id='fb-root'></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = '//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.3';
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
	 * @param string $name The property name.
	 * @return string The property value or false if the property or post doesn't exist.
	 */
	public function __get( $name ) {
		if ( !$this->exists() ) {
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
	 * @param int/object/WP_Post $product
	 */
	protected function _get_post( $product ) {
		$this->_post = get_post( $product );

		if ( is_null( $this->_post ) ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type != self::get_post_type() && $this->_post->post_type != MP_Product::get_variations_post_type() ) {
			$this->_exists = false;
		} else {
			$this->_exists	 = true;
			$this->ID		 = $this->_post->ID;
		}
	}

	/**
	 * Set content tabs
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _set_content_tabs( $product ) {

		if ( !is_admin() ) {
			$tabs = array();

			if ( mp_get_setting( 'related_products->show' ) ) {

				$args = array(
					'relate_by'	 => mp_get_setting( 'related_products->relate_by' ),
					'echo'		 => false,
					'limit'		 => mp_get_setting( 'related_products->show_limit' ),
					'view'		 => mp_get_setting( 'related_products->view' ),
				);

				$related_products = $product->related_products( $args, true );

				if ( $related_products !== false ) {
					$tabs[ 'mp-related-products' ] = __( 'Related Products', 'mp' );
				}
			}

			/**
			 * Filter the product tabs array
			 *
			 * @since 3.0
			 * @param array $tabs The default product tabs.
			 * @param MP_Product $this The current product object.
			 */
			$tabs = (array) apply_filters( 'mp_product/content_tabs_array', $tabs, $this );

			// Make sure product overview tab is always at the beginning
			$tabs = array( 'mp-product-overview' => __( 'Description', 'mp' ) ) + $tabs;

			$this->content_tabs = $tabs;
		}
	}

}
